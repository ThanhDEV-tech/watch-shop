<?php

namespace App\Services;

use App\Exceptions\OpenAiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenAiService
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws OpenAiException
     */
    public function chat(string $systemPrompt, array $messages): string
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            throw new OpenAiException('OpenAI API is not configured.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(30)
                ->post(rtrim((string) config('services.openai.base_url'), '/').'/chat/completions', [
                    'model' => (string) config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ...$messages,
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ]);
        } catch (ConnectionException) {
            throw new OpenAiException('Unable to connect to OpenAI.');
        }

        if (! $response->successful()) {
            throw new OpenAiException('OpenAI returned an unsuccessful response.');
        }

        $content = trim((string) $response->json('choices.0.message.content'));

        if ($content === '') {
            throw new OpenAiException('OpenAI returned an empty response.');
        }

        return $content;
    }
}
