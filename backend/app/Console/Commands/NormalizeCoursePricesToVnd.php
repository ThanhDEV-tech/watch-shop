<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Command;

class NormalizeCoursePricesToVnd extends Command
{
    protected $signature = 'courses:normalize-vnd-prices';

    protected $description = 'Chuẩn hóa giá các khóa học demo sang VNĐ';

    public function handle(): int
    {
        $updated = 0;
        $preserved = 0;

        Course::query()
            ->withTrashed()
            ->orderBy('id')
            ->each(function (Course $course) use (&$updated, &$preserved): void {
                if (str_starts_with($course->title, 'VNPay Sandbox Course')) {
                    $preserved++;

                    return;
                }

                $price = (199 + (($course->id * 137) % 1792)) * 1000;
                $discountPrice = $course->discount_price !== null
                    ? (int) (floor($price * 0.8 / 1000) * 1000)
                    : null;

                $course->update([
                    'price' => $price,
                    'discount_price' => $discountPrice,
                ]);

                $updated++;
            });

        $this->info("Updated {$updated} course(s) to VND prices.");
        $this->info("Preserved {$preserved} VNPay Sandbox Course record(s).");

        return self::SUCCESS;
    }
}
