<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chat-completion provider chain for every AI feature in this app (the
 * fleet/construction/quarry chat assistant, and each vertical's
 * AiInsightsService narration): Groq (Llama 3.3 70B) is tried first for its
 * speed, and OpenRouter (Nemotron 70B) is used automatically if Groq errors,
 * times out, or isn't configured. This is the ONLY class that ever calls
 * Groq/OpenRouter directly — business modules and AI data providers never
 * touch the HTTP layer themselves.
 */
class AiProviderService
{
    private const TIMEOUT = 45;

    /**
     * Single-shot prompt-in/text-out call — used by the three
     * {Fleet,Construction,Quarry}\AiInsightsService narrators, which just
     * want plain text back for one detected insight, not a tool-calling
     * conversation.
     *
     * @return array{text: string, tokens: int, provider: string}
     */
    public function generate(string $prompt, int $maxOutputTokens = 400, float $temperature = 0.2): array
    {
        $result = $this->chat([['role' => 'user', 'content' => $prompt]], [], $maxOutputTokens, $temperature);

        $text = trim((string) ($result['message']['content'] ?? ''));

        if ($text === '') {
            throw new \Exception('AI provider returned an empty response.');
        }

        return ['text' => $text, 'tokens' => $result['tokens'], 'provider' => $result['provider']];
    }

    /**
     * Full chat-completion call with optional OpenAI-style tool
     * definitions. Returns the raw assistant message — which may contain
     * `content`, `tool_calls`, or both — so the caller (AIService) can run
     * a bounded tool-call loop for drill-down questions.
     *
     * @return array{message: array, tokens: int, provider: string}
     */
    public function chat(array $messages, array $tools = [], int $maxOutputTokens = 400, float $temperature = 0.2): array
    {
        try {
            return $this->call(
                'https://api.groq.com/openai/v1/chat/completions',
                config('services.groq.key'),
                config('services.groq.model'),
                $messages,
                $tools,
                $maxOutputTokens,
                $temperature,
                'groq'
            );
        } catch (\Throwable $e) {
            Log::info('AiProviderService: Groq unavailable, falling back to OpenRouter.', ['error' => $e->getMessage()]);

            return $this->call(
                'https://openrouter.ai/api/v1/chat/completions',
                config('services.openrouter.key'),
                config('services.openrouter.model'),
                $messages,
                $tools,
                $maxOutputTokens,
                $temperature,
                'openrouter'
            );
        }
    }

    private function call(
        string $url,
        ?string $apiKey,
        ?string $model,
        array $messages,
        array $tools,
        int $maxOutputTokens,
        float $temperature,
        string $providerName
    ): array {
        if (empty($apiKey)) {
            throw new \Exception(ucfirst($providerName).' is not configured.');
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxOutputTokens,
            'temperature' => $temperature,
        ];

        if (! empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = Http::timeout(self::TIMEOUT)->withToken($apiKey)->post($url, $payload);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? $response->reason();

            throw new \Exception(ucfirst($providerName).' error: '.$response->status().' - '.$message);
        }

        $message = $response->json('choices.0.message') ?? [];
        $tokens = (int) ($response->json('usage.total_tokens') ?? 0);

        $hasContent = filled($message['content'] ?? null);
        $hasToolCalls = ! empty($message['tool_calls']);

        if (! $hasContent && ! $hasToolCalls) {
            // Some models/backends occasionally return HTTP 200 with a null
            // message and no tool_calls when given a larger tool schema —
            // observed live with several providers combined. Retrying the
            // exact same prompt once WITHOUT tools recovers a real answer
            // (using only the eager context already given, no drill-down)
            // rather than surfacing a generic failure for a question the
            // model could otherwise have answered fine.
            if (! empty($tools)) {
                Log::info(ucfirst($providerName).': empty response with tools enabled, retrying once without tools.');

                return $this->call($url, $apiKey, $model, $messages, [], $maxOutputTokens, $temperature, $providerName);
            }

            throw new \Exception(ucfirst($providerName).' returned an empty response.');
        }

        return ['message' => $message, 'tokens' => $tokens, 'provider' => $providerName];
    }
}
