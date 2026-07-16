<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateInstructorAvatars extends Command
{
    protected $signature = 'users:generate-avatars
        {--delay=1 : Seconds to wait between image generation requests}
        {--limit= : Maximum number of instructors to process in one run}
        {--force : Regenerate avatars for every instructor, including users who already have one}';

    protected $description = 'Generate instructor avatar portraits with Pollinations.ai.';

    public function handle(): int
    {
        $query = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'instructor'))
            ->orderBy('id');

        if (! $this->option('force')) {
            $query->where(fn ($query) => $query->whereNull('avatar')->orWhere('avatar', ''));
        }

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $instructors = $query->get();

        if ($instructors->isEmpty()) {
            $this->info($this->option('force')
                ? 'Không có instructor nào để sinh avatar.'
                : 'Không có instructor nào thiếu avatar.');

            return self::SUCCESS;
        }

        $successCount = 0;
        $failedCount = 0;
        $samplePaths = [];
        $delay = max(0, (int) $this->option('delay'));
        $modeLabel = $this->option('force') ? 'instructor cần tạo lại avatar' : 'instructor thiếu avatar';

        $this->info("Tìm thấy {$instructors->count()} {$modeLabel}.");

        foreach ($instructors as $instructor) {
            $prompt = $this->buildPrompt();
            $seed = random_int(1, 999999999);

            try {
                $this->line("Đang sinh avatar cho user #{$instructor->id}: {$instructor->name} (seed {$seed})");

                [$content, $source] = $this->generateImage($prompt, 512, 512, $seed);
                $path = "avatars/{$instructor->id}.jpg";

                Storage::disk('public')->put($path, $content);
                $instructor->forceFill(['avatar' => $path])->save();

                $successCount++;

                if (count($samplePaths) < 2) {
                    $samplePaths[] = Storage::disk('public')->path($path);
                }

                $this->info("✓ Đã lưu: {$path} ({$source})");
            } catch (Throwable $exception) {
                $failedCount++;
                report($exception);
                $this->error("✗ User #{$instructor->id} lỗi: {$exception->getMessage()}");
            }

            if ($delay > 0 && ! $instructor->is($instructors->last())) {
                sleep($delay);
            }
        }

        $this->newLine();
        $this->info("Hoàn tất. Thành công: {$successCount}. Lỗi: {$failedCount}.");

        if ($samplePaths !== []) {
            $this->line('Ảnh mẫu:');

            foreach ($samplePaths as $path) {
                $this->line("- {$path}");
            }
        }

        return $failedCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function buildPrompt(): string
    {
        $hairStyles = [
            'short hair',
            'curly hair',
            'long hair',
            'bald head',
            'wavy hair',
            'neatly styled hair',
        ];
        $ageRanges = [
            'young adult',
            'middle-aged adult',
            'experienced adult',
        ];
        $eyewear = [
            'with glasses',
            'without glasses',
        ];
        $expressions = [
            'warm smile',
            'confident look',
            'thoughtful expression',
            'friendly smile',
        ];
        $wardrobe = [
            'smart casual outfit',
            'dark blazer',
            'modern tech workplace outfit',
            'minimal professional outfit',
        ];

        $variant = [
            $ageRanges[array_rand($ageRanges)],
            $hairStyles[array_rand($hairStyles)],
            $eyewear[array_rand($eyewear)],
            $expressions[array_rand($expressions)],
            $wardrobe[array_rand($wardrobe)],
        ];

        return 'professional headshot portrait, software engineer instructor, '
            .implode(', ', $variant)
            .', studio lighting, dark navy background, professional photo, high quality, photorealistic, diverse, no text, no logo, no watermark';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function generateImage(string $prompt, int $width, int $height, int $seed): array
    {
        $endpoints = [
            [
                'name' => 'gen.pollinations.ai',
                'url' => 'https://gen.pollinations.ai/image/'.rawurlencode($prompt),
                'timeout' => 20,
                'query' => compact('width', 'height', 'seed'),
            ],
            [
                'name' => 'image.pollinations.ai',
                'url' => 'https://image.pollinations.ai/prompt/'.rawurlencode($prompt),
                'timeout' => 120,
                'query' => [
                    'width' => $width,
                    'height' => $height,
                    'seed' => $seed,
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
