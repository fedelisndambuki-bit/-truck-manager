<?php

namespace App\Services\Ai;

use App\Models\User;

/**
 * Ties the registry + router + period parser together: for a given question,
 * decides which authorized providers are eagerly relevant (compact context
 * sent up front) versus which are merely available as tools (for the model
 * to request via drill-down if the eager set wasn't enough) — never sends
 * every module on every question, and never exceeds the configured context
 * budget.
 */
class AiContextOrchestrator
{
    public function __construct(
        protected AiDataRegistry $registry,
        protected AiIntentRouter $router,
        protected AiPeriodParser $periodParser,
        protected AiQuestionClassifier $classifier,
        protected AiContributorAnalysis $contributors,
    ) {
    }

    /**
     * @return array{
     *     context: array<string, array>,
     *     tools: array,
     *     authorized_providers: array<string, \App\Contracts\AiDataProviderInterface>,
     *     modules_accessed: string[],
     *     denied_modules: array<string, string>,
     *     period: array{from: \Illuminate\Support\Carbon, to: \Illuminate\Support\Carbon, label: string},
     *     context_truncated: bool,
     *     depth: string,
     * }
     */
    public function build(User $user, ?string $industry, int $companyId, string $question): array
    {
        $depth = $this->classifier->classify($question);
        $industryApplicable = $this->registry->industryApplicableProviders($industry);
        $authorized = $this->registry->authorizedProviders($user, $industry);
        $period = $this->periodParser->parse($question);

        // Route against the full industry-applicable set (not yet
        // permission-filtered) so we can tell "wanted this but denied by
        // permission" apart from "this module doesn't apply here at all".
        $desiredKeys = $this->router->route($question, array_keys($industryApplicable));
        $deniedKeys = array_diff($desiredKeys, array_keys($authorized));
        $deniedModules = [];
        foreach ($deniedKeys as $key) {
            $deniedModules[$key] = $industryApplicable[$key]->label();
        }

        $relevantKeys = array_values(array_intersect($desiredKeys, array_keys($authorized)));

        // Diagnostic ("why did X increase?") questions need every relevant
        // provider's comparison data present at once to attribute the
        // change — truncating one away would silently break the very
        // cross-module cause analysis being asked for, so the char budget
        // is not applied for this depth (it already gets a larger token
        // budget downstream in AIService to narrate the extra data).
        $maxChars = $depth === 'diagnostic' ? PHP_INT_MAX : config('ai.max_context_chars', 6000);
        $usedChars = 0;
        $context = [];
        $includedKeys = [];
        $truncated = false;

        foreach ($relevantKeys as $key) {
            $provider = $authorized[$key] ?? null;
            if (! $provider) {
                continue;
            }

            $data = $provider->summary($companyId, $period);
            $encoded = json_encode($data);

            if ($usedChars > 0 && ($usedChars + strlen($encoded)) > $maxChars) {
                // Budget hit — stop adding eager providers; the model can
                // still reach this one via its tool definition below.
                $truncated = true;

                continue;
            }

            $context[$provider->label()] = $data;
            $includedKeys[] = $key;
            $usedChars += strlen($encoded);
        }

        if ($depth === 'diagnostic') {
            $comparisons = $this->extractComparisons($context);
            if (! empty($comparisons)) {
                $context['Main Contributors (ranked by size of change)'] = $this->contributors->rank($comparisons);
            }
        }

        return [
            'context' => $context,
            'tools' => $this->registry->toolsFor($authorized),
            'authorized_providers' => $authorized,
            'modules_accessed' => $includedKeys,
            'denied_modules' => $deniedModules,
            'period' => $period,
            'context_truncated' => $truncated,
            'depth' => $depth,
        ];
    }

    /**
     * Pulls every `*_comparison` block (the convention every provider
     * follows for a current-vs-previous metric) out of the assembled
     * context, named "Provider: metric", so AiContributorAnalysis can rank
     * them by size of change regardless of which providers were involved.
     *
     * @param array<string, array> $context
     * @return array<string, array>
     */
    private function extractComparisons(array $context): array
    {
        $comparisons = [];

        foreach ($context as $label => $data) {
            if (! is_array($data)) {
                continue;
            }

            foreach ($data as $fieldKey => $value) {
                if (str_ends_with((string) $fieldKey, '_comparison') && is_array($value) && array_key_exists('absolute_change', $value)) {
                    $name = $label.': '.($value['metric'] ?? $fieldKey);
                    $comparisons[$name] = $value;
                }
            }
        }

        return $comparisons;
    }
}
