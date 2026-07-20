<?php

namespace App\Services;

use App\Exceptions\OpenAiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
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

        $response = $this->sendWithRetry($apiKey, $systemPrompt, $messages);

        if (! $response->successful()) {
            throw new OpenAiException('Hệ thống đang bận, vui lòng thử lại sau.');
        }

        $content = trim((string) $response->json('choices.0.message.content'));

        if ($content === '') {
            throw new OpenAiException('OpenAI returned an empty response.');
        }

        return $content;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws OpenAiException
     */
    private function sendWithRetry(string $apiKey, string $systemPrompt, array $messages): Response
    {
        $delaysInSeconds = [0, 1, 2];
        $lastResponse = null;

        foreach ($delaysInSeconds as $attempt => $delay) {
            if ($delay > 0) {
                sleep($delay);
            }

            try {
                $response = $this->sendRequest($apiKey, $systemPrompt, $messages);
            } catch (ConnectionException) {
                if ($attempt === array_key_last($delaysInSeconds)) {
                    throw new OpenAiException('Hệ thống đang bận, vui lòng thử lại sau.');
                }

                continue;
            }

            if (! $this->shouldRetry($response) || $attempt === array_key_last($delaysInSeconds)) {
                return $response;
            }

            $lastResponse = $response;
        }

        if ($lastResponse instanceof Response) {
            return $lastResponse;
        }

        throw new OpenAiException('Hệ thống đang bận, vui lòng thử lại sau.');
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws ConnectionException
     */
    private function sendRequest(string $apiKey, string $systemPrompt, array $messages): Response
    {
        return Http::withToken($apiKey)
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
    }

    private function shouldRetry(Response $response): bool
    {
        return in_array($response->status(), [429, 500, 502, 503, 504], true);
    }
}
