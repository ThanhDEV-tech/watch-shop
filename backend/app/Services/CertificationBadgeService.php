<?php

namespace App\Services;

use App\Models\Certification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CertificationBadgeService
{
    public function generate(Certification $certification): string
    {
        $prompt = $this->buildPrompt($certification);
        [$content] = $this->generateImage($prompt, 800, 450);
        $path = "certification-badges/{$certification->id}.jpg";

        Storage::disk('public')->put($path, $content);
        $certification->forceFill(['badge_image' => $path])->save();

        return $path;
    }

    public function buildPrompt(Certification $certification): string
    {
        $accentColor = $certification->accent_color ?: '#FF6B4A';
        $source = Str::lower($certification->provider.' '.$certification->name);

        $topic = match (true) {
            Str::contains($source, ['aws', 'azure', 'google cloud', 'cloud']) => 'cloud computing technology illustration, servers and network nodes',
            Str::contains($source, ['security', 'comptia']) => 'cybersecurity shield illustration, secure network nodes and encrypted data streams',
            Str::contains($source, ['pmp', 'project', 'pmi']) => 'project management workflow illustration, kanban board and timeline abstract',
            default => 'professional technology certification badge illustration, abstract software engineering symbols',
        };

        return "{$topic}, {$accentColor} glowing accents, dark navy background, futuristic tech art, no text, no logo, no watermark";
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function generateImage(string $prompt, int $width, int $height): array
    {
        $endpoints = [
            [
                'name' => 'gen.pollinations.ai',
                'url' => 'https://gen.pollinations.ai/image/'.rawurlencode($prompt),
                'timeout' => 20,
                'query' => compact('width', 'height'),
            ],
            [
                'name' => 'image.pollinations.ai',
                'url' => 'https://image.pollinations.ai/prompt/'.rawurlencode($prompt),
                'timeout' => 120,
                'query' => [
                    'width' => $width,
                    'height' => $height,
                    'nologo' => 'true',
                ],
            ],
        ];

        $errors = [];

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout($endpoint['timeout'])
                    ->retry(1, 750)
                    ->get($endpoint['url'], $endpoint['query']);

                if (! $response->successful()) {
                    throw new \RuntimeException("HTTP {$response->status()}");
                }

                $content = $response->body();
                $contentType = strtolower((string) $response->header('Content-Type'));

                if ($content === '' || ! Str::startsWith($contentType, 'image/')) {
                    throw new \RuntimeException("Response không phải ảnh hợp lệ. Content-Type: {$contentType}");
                }

                return [$content, $endpoint['name']];
            } catch (Throwable $exception) {
                $errors[] = "{$endpoint['name']}: {$exception->getMessage()}";
            }
        }

        throw new \RuntimeException('Không thể sinh ảnh từ Pollinations. '.implode(' | ', $errors));
    }
}
