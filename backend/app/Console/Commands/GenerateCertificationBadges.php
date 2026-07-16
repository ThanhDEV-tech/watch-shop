<?php

namespace App\Console\Commands;

use App\Models\Certification;
use App\Services\CertificationBadgeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateCertificationBadges extends Command
{
    protected $signature = 'certifications:generate-badges
        {--delay=1 : Seconds to wait between image generation requests}
        {--limit= : Maximum number of certifications to process in one run}';

    protected $description = 'Generate missing certification badge background images with Pollinations.ai.';

    public function __construct(private readonly CertificationBadgeService $badgeService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Certification::query()
            ->where(fn ($query) => $query->whereNull('badge_image')->orWhere('badge_image', ''))
            ->orderBy('id');

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        $certifications = $query->get();

        if ($certifications->isEmpty()) {
            $this->info('Không có certification nào thiếu badge image.');

            return self::SUCCESS;
        }

        $successCount = 0;
        $failedCount = 0;
        $samplePaths = [];
        $delay = max(0, (int) $this->option('delay'));

        $this->info("Tìm thấy {$certifications->count()} certification thiếu badge image.");

        foreach ($certifications as $certification) {
            try {
                $this->line("Đang sinh badge cho certification #{$certification->id}: {$certification->name}");

                $path = $this->badgeService->generate($certification);
                $successCount++;

                if (count($samplePaths) < 2) {
                    $samplePaths[] = Storage::disk('public')->path($path);
                }

                $this->info("✓ Đã lưu: {$path}");
            } catch (Throwable $exception) {
                $failedCount++;
                report($exception);
                $this->error("✗ Certification #{$certification->id} lỗi: {$exception->getMessage()}");
            }

            if ($delay > 0 && ! $certification->is($certifications->last())) {
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
}
