<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoLessonSeeder extends Seeder
{
    public function run(): void
    {
        $seededCourses = 0;

        Course::query()
            ->where('status', 'approved')
            ->doesntHave('chapters')
            ->orderBy('id')
            ->each(function (Course $course) use (&$seededCourses): void {
                DB::transaction(function () use ($course): void {
                    $chapterDefinitions = [
                        [
                            'title' => 'Nền tảng '.$course->title,
                            'description' => 'Làm quen với các khái niệm và công cụ cốt lõi của khóa học.',
                            'lessons' => [
                                'Giới thiệu và mục tiêu khóa học',
                                'Thiết lập môi trường học tập',
                                'Các khái niệm nền tảng',
                            ],
                        ],
                        [
                            'title' => 'Thực hành '.$course->title,
                            'description' => 'Áp dụng kiến thức qua các ví dụ và bài thực hành mẫu.',
                            'lessons' => [
                                'Xây dựng ví dụ thực tế',
                                'Tổng kết và hướng phát triển tiếp theo',
                            ],
                        ],
                    ];

                    foreach ($chapterDefinitions as $chapterIndex => $definition) {
                        $chapter = $course->chapters()->create([
                            'title' => $definition['title'],
                            'description' => $definition['description'],
                            'position' => $chapterIndex + 1,
                        ]);

                        foreach ($definition['lessons'] as $lessonIndex => $lessonTitle) {
                            $chapter->lessons()->create([
                                'title' => $lessonTitle,
                                'content' => $this->lessonContent($course, $lessonTitle),
                                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                                'duration_seconds' => fake()->numberBetween(480, 1500),
                                'position' => $lessonIndex + 1,
                                'is_free_preview' => $chapterIndex === 0 && $lessonIndex === 0,
                            ]);
                        }
                    }
                });

                $seededCourses++;
            });

        $this->command?->info("Demo curriculum added to {$seededCourses} approved course(s).");
    }

    private function lessonContent(Course $course, string $lessonTitle): string
    {
        return "Bài học \"{$lessonTitle}\" thuộc khóa học \"{$course->title}\". "
            .'Nội dung mẫu này giúp kiểm thử Lesson Player, theo dõi tiến độ và ngữ cảnh AI. '
            .'Hãy xem video, đọc phần giải thích và đánh dấu hoàn thành khi bạn đã nắm được nội dung chính.';
    }
}
