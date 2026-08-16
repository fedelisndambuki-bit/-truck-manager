<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\AiUsageLog;
use App\Models\Company;
use App\Models\User;
use App\Services\Ai\AiContextOrchestrator;
use App\Services\Ai\AiDataRegistry;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected ?string $lastProviderUsed = null;

    public function __construct(
        protected AiProviderService $aiProvider,
        protected AiContextOrchestrator $orchestrator,
        protected AiDataRegistry $registry,
    ) {
    }

    private function modelNameFor(?string $provider): ?string
    {
        return match ($provider) {
            'groq' => config('services.groq.model'),
            'openrouter' => config('services.openrouter.model'),
            default => null,
        };
    }

    /**
     * Send an arbitrary custom prompt straight to the model with no context
     * building/logging beyond the same usage tracking every other call gets
     * — for callers (e.g. TyreManagement's smart insights card) that already
     * assemble their own prompt/data and just want raw text back, matching
     * the old App\AI\AIService::ask() signature/shape.
     */
    public function askRaw(string $prompt, ?int $companyId = null): string
    {
        try {
            return $this->callAi($prompt, false, $companyId);
        } catch (\Throwable $e) {
            Log::info('AIService::askRaw: AI provider unavailable.', ['error' => $e->getMessage()]);

            return 'AI unavailable.';
        }
    }

    public function processRequest(int $companyId, int $userId, string $question): array
    {
        $startTime = microtime(true);
        $user = User::find($userId);
        $isSuperAdmin = $user?->hasRole('Super Admin') ?? false;

        try {
            $company = Company::find($companyId);
            $industry = $isSuperAdmin ? null : $company?->industry;
            $companyName = $company->name ?? 'Your Company';

            $provider = null;

            if ($isSuperAdmin) {
                $contextData = $this->getSuperAdminSummary();
                $prompt = $this->buildPrompt($question, $contextData, $companyName, true, null);
                $response = $this->callAi($prompt, false, $companyId, $userId);
                $provider = $this->lastProviderUsed;
                $modulesAccessed = ['platform'];
                $providersInvoked = [];
                $periodLabel = null;
            } else {
                $built = $this->orchestrator->build($user, $industry, $companyId, $question);

                if (! empty($built['denied_modules'])) {
                    // Deterministic refusal — never let the model attempt to
                    // answer from data it was never given, and never rely on
                    // it to "decide" to refuse; the permission check already
                    // happened, so the response is decided here too.
                    $deniedLabels = implode(', ', array_values($built['denied_modules']));
                    $response = "You don't have permission to view {$deniedLabels} data. Please contact your administrator if you need access.";
                    $modulesAccessed = [];
                    $providersInvoked = [];
                    $periodLabel = $built['period']['label'];
                } else {
                    $systemPrompt = $this->getSystemPrompt($built['context'], $companyName, false, $industry, $built['depth']);
                    $maxOutputTokens = $built['depth'] === 'simple'
                        ? (int) config('ai.max_output_tokens', 400)
                        : (int) config('ai.max_output_tokens_analytical', 1400);

                    [$response, $tokensUsed, $provider, $invokedKeys] = $this->runToolLoop(
                        $systemPrompt,
                        $question,
                        $built['tools'],
                        $built['authorized_providers'],
                        $companyId,
                        $maxOutputTokens
                    );

                    $modulesAccessed = array_values(array_unique(array_merge($built['modules_accessed'], $invokedKeys)));
                    $providersInvoked = array_values(array_unique($invokedKeys));
                    $periodLabel = $built['period']['label'];

                    AiUsageLog::create([
                        'company_id' => $companyId,
                        'user_id' => $userId,
                        'tokens_used' => $tokensUsed,
                        'response_ms' => (int) round((microtime(true) - $startTime) * 1000),
                        'provider' => $provider,
                    ]);
                }

                $contextData = $built['context'];
            }

            $responseTime = round((microtime(true) - $startTime) * 1000);

            $this->logAiRequest(
                $companyId,
                $userId,
                $question,
                $response,
                $contextData,
                $responseTime,
                'success',
                null,
                $modulesAccessed,
                $providersInvoked,
                $periodLabel,
                $this->modelNameFor($provider)
            );

            return [
                'success' => true,
                'response' => $response,
                'response_time_ms' => $responseTime,
                'fleet_data' => $contextData,
            ];
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logAiRequest($companyId, $userId, $question, null, null, $responseTime, 'error', $e->getMessage());

            return [
                'success' => false,
                'error' => 'Sorry, I ran into a problem answering that. Please try again in a moment.',
                'response_time_ms' => $responseTime,
            ];
        }
    }

    private function getSuperAdminSummary(): array
    {
        $cacheKey = 'super_admin_summary';

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () {
            return [
                'role' => 'Super Admin',
                'total_companies' => \App\Models\Company::count(),
                'total_users' => \App\Models\User::count(),
                'total_plans' => \App\Models\Plan::count(),
                'total_subscriptions' => \App\Models\Subscription::where('status', 'active')->count(),
                'platform_settings' => \App\Models\PlatformSetting::count(),
                'active_modules' => \App\Models\Module::where('is_active', true)->count(),
                'recent_logs' => \App\Models\AiLog::count(),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    public function generateDailyInsights(int $companyId, int $userId): array
    {
        $startTime = microtime(true);
        $user = User::find($userId);
        $isSuperAdmin = $user?->hasRole('Super Admin') ?? false;

        try {
            $company = Company::find($companyId);
            $industry = $isSuperAdmin ? null : $company?->industry;
            $companyName = $company->name ?? 'Your Company';

            if ($isSuperAdmin) {
                $contextData = $this->getSuperAdminSummary();
                $question = 'Generate a platform-wide health summary and 2 operational suggestions for the Super Admin.';
            } else {
                $question = match ($industry) {
                    'construction' => 'Generate today\'s construction portfolio summary and 2 suggestions.',
                    'quarry' => 'Generate today\'s quarry operations summary and 2 suggestions.',
                    default => 'Generate today\'s fleet performance summary and 2 suggestions.',
                };
                $built = $this->orchestrator->build($user, $industry, $companyId, $question);
                $contextData = $built['context'];
            }

            $prompt = $this->buildPrompt($question, $contextData, $companyName, $isSuperAdmin, $industry);
            $response = $this->callAi($prompt, true, $companyId, $userId);
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logAiRequest(
                $companyId,
                $userId,
                '[auto] daily_insights',
                $response,
                $contextData,
                $responseTime,
                'success',
                null,
                [],
                [],
                null,
                $this->modelNameFor($this->lastProviderUsed)
            );

            return [
                'success' => true,
                'response' => $response,
                'response_time_ms' => $responseTime,
                'fleet_data' => $contextData,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'AI unavailable.'];
        }
    }

    /**
     * Bounded tool-calling conversation: send the compact eager context +
     * available tool definitions, and if the model requests a tool, dispatch
     * it through the registry (which re-applies the same permission/company
     * scoping as the initial context) and loop — up to config('ai.max_tool_rounds')
     * times — so drill-down questions ("which vehicles?") can fetch more
     * specific data without every question paying for every module upfront.
     *
     * @param array<string, \App\Contracts\AiDataProviderInterface> $authorizedProviders
     * @return array{0: string, 1: int, 2: string, 3: string[]} [responseText, totalTokens, provider, invokedProviderKeys]
     */
    private function runToolLoop(string $systemPrompt, string $question, array $tools, array $authorizedProviders, int $companyId, ?int $maxOutputTokens = null): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question],
        ];

        $maxRounds = max(1, (int) config('ai.max_tool_rounds', 3));
        $maxOutputTokens = $maxOutputTokens ?? (int) config('ai.max_output_tokens', 400);
        $totalTokens = 0;
        $provider = 'openrouter';
        $invokedKeys = [];

        $nudged = false;

        for ($round = 0; $round < $maxRounds; $round++) {
            $result = $this->aiProvider->chat($messages, $tools, $maxOutputTokens);
            $totalTokens += $result['tokens'];
            $provider = $result['provider'];
            $message = $result['message'];

            if (empty($message['tool_calls'])) {
                $text = trim((string) ($message['content'] ?? ''));

                // Observed live: instead of using the real tool-calling
                // mechanism, the model sometimes narrates a fake function
                // call as plain text (a JSON blob describing what it "would"
                // call). If we can parse a genuine tool name out of it,
                // actually execute it rather than showing raw JSON/prose
                // about calling a tool to the user.
                $fakeCall = $round < $maxRounds - 1 ? $this->extractNarratedToolCall($text, $tools) : null;
                if ($fakeCall) {
                    $key = $this->providerKeyForToolName($fakeCall['name'], $authorizedProviders);
                    if ($key) {
                        $invokedKeys[] = $key;
                    }

                    $toolResult = $this->registry->callTool($fakeCall['name'], $fakeCall['arguments'], $companyId, $authorizedProviders);

                    $messages[] = ['role' => 'assistant', 'content' => $text];
                    $messages[] = ['role' => 'user', 'content' => 'Here is the result of that: '.json_encode($toolResult).'. Answer my original question now using this data, in your own words — do not show me JSON or describe a function call.'];

                    continue;
                }

                if (($this->isSubstantive($text) && ! $this->looksLikeNarratedFunctionCall($text)) || $nudged) {
                    return [$text !== '' ? $text : 'AI unavailable.', $totalTokens, $provider, $invokedKeys];
                }

                // Either only the trailing SUGGESTIONS line came back (the
                // model treated a visible tool result as answer enough), or
                // it described a function call it didn't actually make and
                // we couldn't parse a valid tool out of it. One nudge to
                // actually answer directly resolves both reliably.
                $nudged = true;
                $messages[] = ['role' => 'assistant', 'content' => $message['content'] ?? ''];
                $messages[] = ['role' => 'user', 'content' => 'That wasn\'t a direct answer. Please answer my original question above in your own words now, using the data already provided — do not describe a function call or show JSON.'];

                continue;
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $message['content'] ?? null,
                'tool_calls' => $message['tool_calls'],
            ];

            foreach ($message['tool_calls'] as $toolCall) {
                $toolName = (string) ($toolCall['function']['name'] ?? '');
                $arguments = json_decode((string) ($toolCall['function']['arguments'] ?? '{}'), true) ?: [];

                $key = $this->providerKeyForToolName($toolName, $authorizedProviders);
                if ($key) {
                    $invokedKeys[] = $key;
                }

                $toolResult = $this->registry->callTool($toolName, $arguments, $companyId, $authorizedProviders);

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'] ?? '',
                    'content' => json_encode($toolResult),
                ];
            }
        }

        // Ran out of rounds without a final answer — force one more call
        // with tools disabled so the model must answer from what it has.
        $result = $this->aiProvider->chat($messages, [], $maxOutputTokens);
        $totalTokens += $result['tokens'];
        $text = trim((string) ($result['message']['content'] ?? ''));

        return [$text !== '' ? $text : 'AI unavailable.', $totalTokens, $result['provider'], $invokedKeys];
    }

    /**
     * A reply consisting of only the trailing "SUGGESTIONS: ..." line (or
     * nothing at all) isn't a real answer — strip that line and require
     * some actual content to remain.
     */
    private function isSubstantive(string $text): bool
    {
        $withoutSuggestions = trim((string) preg_replace('/SUGGESTIONS:.*/is', '', $text));

        return mb_strlen($withoutSuggestions) >= 10;
    }

    /**
     * Detects the "I would call this function: {...}" narration pattern
     * some models fall back to instead of using real tool-calling, and — if
     * the name matches one of the tools actually offered this round —
     * extracts it so it can be executed for real.
     *
     * @return array{name: string, arguments: array}|null
     */
    private function extractNarratedToolCall(string $text, array $tools): ?array
    {
        if (! preg_match('/\{\s*"name"\s*:\s*"([a-zA-Z0-9_]+)"\s*,\s*"parameters"\s*:\s*(\{.*?\})\s*\}/s', $text, $m)) {
            return null;
        }

        $toolNames = array_column(array_column($tools, 'function'), 'name');
        if (! in_array($m[1], $toolNames, true)) {
            return null;
        }

        $arguments = json_decode($m[2], true);

        return ['name' => $m[1], 'arguments' => is_array($arguments) ? $arguments : []];
    }

    /**
     * Broader, non-parsing signal for the same failure mode — catches cases
     * where the model describes calling a function in prose without
     * producing JSON clean enough for extractNarratedToolCall() to parse.
     */
    private function looksLikeNarratedFunctionCall(string $text): bool
    {
        return (bool) preg_match('/function call|"name"\s*:\s*"[a-z_]+"|\bget_[a-z_]+\(/i', $text);
    }

    /**
     * @param array<string, \App\Contracts\AiDataProviderInterface> $authorizedProviders
     */
    private function providerKeyForToolName(string $toolName, array $authorizedProviders): ?string
    {
        foreach ($authorizedProviders as $key => $provider) {
            foreach ($provider->tools() as $tool) {
                if (($tool['function']['name'] ?? null) === $toolName) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function buildPrompt(string $userQuestion, array $contextData, string $companyName, bool $isSuperAdmin = false, ?string $industry = null): string
    {
        return $this->getSystemPrompt($contextData, $companyName, $isSuperAdmin, $industry)."\n\nUser Question: ".trim($userQuestion);
    }

    private function getSystemPrompt(array $contextData, string $companyName, bool $isSuperAdmin = false, ?string $industry = null, string $depth = 'simple'): string
    {
        $contextJson = json_encode($contextData, JSON_PRETTY_PRINT);
        $date = now()->format('l, F j, Y H:i');

        if ($isSuperAdmin) {
            return <<<PROMPT
You are the Super Admin Intelligence Assistant for the Truck Manager Platform.
Current Date: {$date}
Platform Global Data: {$contextJson}

Guidelines:
1. You provide high-level insights across all companies, plans, and system modules.
2. Be extremely concise. Use bullet points for stats.
3. Your role is platform health, scaling, and billing oversight.
4. AT THE END of your response, provide exactly 2 short follow-up questions for a Super Admin.
   Format: SUGGESTIONS: [Question 1] | [Question 2]
PROMPT;
        }

        [$persona, $dataLabel, $availableCommands] = match ($industry) {
            'construction' => ['Construction AI Assistant', 'Construction Data', "/projects [active|delayed|completed], /project [name], /site [name], /subcontractor [name], /safety, /materials, /insights daily"],
            'quarry' => ['Quarry AI Assistant', 'Quarry Data', "/production today, /dispatch queue, /blast upcoming, /stockpile low, /weighbridge queue, /safety incidents, /insights daily"],
            default => ['Fleet AI Assistant', 'Fleet Data', "/trips [active|scheduled|completed], /truck [plate], /fuel, /maintenance, /driver [name], /invoices, /predict [plate], /fraud, /insights"],
        };

        $sharedGuidelines = <<<TEXT
- Only use the numbers given to you above (or from a tool call) — never invent or estimate a figure yourself. Simple arithmetic on numbers already given above (e.g. profit = revenue − expenses) is fine and encouraged — that does not need a tool call.
- If a "_comparison" block shows previous_value as 0 with a null percentage_change, that means Tanaki does not have enough historical data for that comparison yet — say so plainly rather than inventing a percentage.
- If you need more specific data than what's shown above (e.g. a single vehicle's details, a deeper breakdown), use the real tool-calling mechanism to call one of the available tools. NEVER write out a function name or a JSON block like {"name": ..., "parameters": ...} as part of your visible reply — if you decide to call a tool, actually call it; do not describe or narrate calling one.
- Only ever reference the exact slash commands listed above, exactly as written, and only if none of the data above already answers the question. Never suggest a command not in that list.
- ALWAYS write a real answer to the user's question first, in your own words — even after calling a tool. A tool result being visible in the conversation is not itself an answer; you must summarize it for the user. NEVER send a reply that is only the suggestions line with no answer above it.
TEXT;

        if ($depth === 'simple') {
            return <<<PROMPT
You are a fast, professional {$persona} for {$companyName}.
Current Date: {$date}
{$dataLabel}: {$contextJson}
Available slash commands (this is the COMPLETE and ONLY list — never invent, pluralize, or guess a different command; if none of these fit what the user needs, say so in plain English instead of suggesting a command): {$availableCommands}

This is a simple factual question — answer concisely, in a sentence or short bullet list. Do not force a long structured report onto a simple question.

Guidelines:
{$sharedGuidelines}
- Be concise. Use bullet points for stats.
- AT THE END of your response, provide exactly 2 short follow-up questions.
  Format: SUGGESTIONS: [Question 1] | [Question 2]
PROMPT;
        }

        $depthInstruction = match ($depth) {
            'diagnostic' => 'This is a diagnostic "why" question. Lead with the "Main Contributors" ranking above if one is present — it is already sorted by size of change, so do not re-rank it yourself. Explain what likely caused the change using the real comparisons given, using language like "correlates with"/"is associated with"/"may indicate" rather than asserting certainty about causation.',
            'comparative' => 'This is a comparative question. Lead with the actual current vs previous values and the already-computed percentage change from the "_comparison" blocks above — never recompute a percentage yourself.',
            'executive' => 'This is a broad executive/overall-performance question spanning multiple modules above. Give a genuine cross-module summary, not just one module\'s numbers.',
            default => 'This question calls for real analysis, not a one-line answer — use the comparison, trend and ranking data already computed above.',
        };

        return <<<PROMPT
You are a fast, professional {$persona} for {$companyName}.
Current Date: {$date}
{$dataLabel}: {$contextJson}
Available slash commands (this is the COMPLETE and ONLY list — never invent, pluralize, or guess a different command; if none of these fit what the user needs, say so in plain English instead of suggesting a command): {$availableCommands}

{$depthInstruction}

Structure your response using Markdown headings, but ONLY the sections below that are actually relevant to this question — never force an empty or irrelevant section in:
### Summary — short, direct answer to the question
### Comparison — current vs previous period, with the percentage change already computed above
### Trend — direction, highest/lowest period, any notable spikes/drops already flagged above
### Main Contributors — if a ranked contributor list is present above, lead with it
### Key Findings — what the data indicates
### Cross-Module Insights — connect modules if more than one is present above
### Recommendations — practical next steps
### Data Used — name which of the modules above were consulted

Guidelines:
{$sharedGuidelines}
- AT THE END of your response, provide exactly 2 short follow-up questions.
  Format: SUGGESTIONS: [Question 1] | [Question 2]
PROMPT;
    }

    private function callAi(string $prompt, bool $isLongResponse = false, ?int $companyId = null, ?int $userId = null): string
    {
        $start = microtime(true);

        $result = $this->aiProvider->generate($prompt, $isLongResponse ? 1000 : 400);
        $this->lastProviderUsed = $result['provider'];

        // The provider reports real token usage — logged per call so
        // AiUsageLogResource reflects genuine usage.
        if ($companyId) {
            AiUsageLog::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'tokens_used' => $result['tokens'],
                'response_ms' => (int) round((microtime(true) - $start) * 1000),
                'provider' => $result['provider'],
            ]);
        }

        return $result['text'] !== '' ? $result['text'] : 'AI unavailable.';
    }

    /**
     * No-op today — context is built fresh per-question by the providers
     * rather than cached, so there's nothing to invalidate. Kept as a public
     * method since AiPanel::refreshSession() still calls it.
     */
    public function clearContextCache(int $companyId): void
    {
        //
    }

    private function logAiRequest(
        $companyId,
        $userId,
        $question,
        $response,
        $fleetData,
        $responseTime,
        $status,
        $errorMessage = null,
        array $modulesAccessed = [],
        array $providersInvoked = [],
        ?string $dateRangeUsed = null,
        ?string $modelUsed = null
    ): void {
        AiLog::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'question' => $question,
            'response' => $response,
            'fleet_data' => $fleetData,
            'modules_accessed' => $modulesAccessed,
            'providers_invoked' => $providersInvoked,
            'date_range_used' => $dateRangeUsed ? ['label' => $dateRangeUsed] : null,
            'ai_model_used' => $modelUsed,
            'response_time_ms' => $responseTime,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }
}
