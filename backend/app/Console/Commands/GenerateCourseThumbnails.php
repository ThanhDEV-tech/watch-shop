<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateCourseThumbnails extends Command
{
    protected $signature = 'courses:generate-thumbnails
        {--delay=1 : Seconds to wait between image generation requests}
        {--limit= : Maximum number of courses to process in one run}';

    protected $description = 'Generate missing course thumbnails with Pollinations.ai.';

    public function handle(): int
    {
        $query = Course::query()
            ->with('category:id,name')
            ->where(fn ($query) => $query
                ->whereNull('thumbnail')
                ->orWhere('thumbnail', '')
            )
            ->orderBy('id');

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $courses = $query->get();

        if ($courses->isEmpty()) {
            $this->info('Không có course nào thiếu thumbnail.');

            return self::SUCCESS;
        }

        $successCount = 0;
        $failedCount = 0;
        $samplePaths = [];
        $delay = max(0, (int) $this->option('delay'));

        $this->info("Tìm thấy {$courses->count()} course thiếu thumbnail.");

        foreach ($courses as $course) {
            $prompt = $this->buildPrompt($course);

            try {
                $this->line("Đang sinh ảnh cho course #{$course->id}: {$course->title}");

                [$content, $source] = $this->generateImage($prompt);

                $path = "course-thumbnails/{$course->id}.jpg";

                Storage::disk('public')->put($path, $content);
                $course->forceFill(['thumbnail' => $path])->save();

                $successCount++;

                if (count($samplePaths) < 2) {
                    $samplePaths[] = Storage::disk('public')->path($path);
                }

                $this->info("✓ Đã lưu: {$path} ({$source})");
            } catch (Throwable $exception) {
                $failedCount++;
                report($exception);
                $this->error("✗ Course #{$course->id} lỗi: {$exception->getMessage()}");
            }

            if ($delay > 0 && ! $course->is($courses->last())) {
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

    private function buildPrompt(Course $course): string
    {
        $categoryName = $course->category?->name ?? 'software engineering';
        $source = Str::lower($course->title.' '.$categoryName);

        $concepts = collect([
            'docker' => 'docker containers',
            'kubernetes' => 'kubernetes cluster',
            'ci/cd' => 'CI/CD pipeline',
            'devops' => 'DevOps automation',
            'cloud' => 'cloud infrastructure',
            'aws' => 'AWS cloud architecture',
            'azure' => 'Azure cloud services',
            'laravel' => 'Laravel API architecture',
            'php' => 'PHP backend engineering',
            'api' => 'REST API architecture',
            'vue' => 'Vue.js frontend interface',
            'javascript' => 'JavaScript application interface',
            'react' => 'React component interface',
            'ui' => 'UI/UX design system',
            'ux' => 'user experience wireframes',
            'mobile' => 'mobile app engineering',
            'android' => 'mobile app engineering',
            'ios' => 'mobile app engineering',
            'ai' => 'AI data visualization',
            'data' => 'data analytics dashboard',
            'security' => 'cybersecurity shield',
            'pmp' => 'project management roadmap',
        ])
            ->filter(fn (string $concept, string $keyword) => Str::contains($source, $keyword))
            ->values()
            ->take(4);

        $topic = $concepts->isNotEmpty()
            ? $concepts->implode(', ')
            : "{$course->title}, {$categoryName}";

        return "{$topic} course thumbnail illustration, professional software learning marketplace, dark navy background, orange accent, minimalist tech illustration, no text, no watermark";
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function generateImage(string $prompt): array
    {
        $endpoints = [
            [
                'name' => 'gen.pollinations.ai',
                'url' => 'https://gen.pollinations.ai/image/'.rawurlencode($prompt),
                'timeout' => 15,
                'query' => [
                    'width' => 800,
                    'height' => 450,
                ],
            ],
            [
                'name' => 'image.pollinations.ai',
                'url' => 'https://image.pollinations.ai/prompt/'.rawurlencode($prompt),
                'timeout' => 120,
                'query' => [
                    'width' => 800,
                    'height' => 450,
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
