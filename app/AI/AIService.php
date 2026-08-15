<?php

namespace App\AI;

use Illuminate\Support\Facades\Http;

class AIService
{
    public static function ask($prompt)
    {
        $key = config('services.anthropic.key');
        if (empty($key)) {
            return 'AI is not configured. Please set ANTHROPIC_API_KEY in your .env file.';
        }

        $request = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
        ]);

        if (app()->environment('local')) {
            $request->withoutVerifying();
        }

        $response = $request->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 700,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]);

        if (!$response->successful()) {
            $body = $response->json();
            $msg = $body['error']['message'] ?? $response->reason();
            return 'Sorry, the AI service returned an error: ' . $msg;
        }

        $content = $response->json('content');
        if (empty($content) || !isset($content[0]['text'])) {
            return 'AI unavailable.';
        }

        return $content[0]['text'];
    }
}
