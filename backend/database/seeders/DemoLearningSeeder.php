<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoLearningSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::query()->whereHas('role', fn ($query) => $query->where('name', 'student'))->get();
        $courses = Course::query()->where('status', 'approved')->get();

        if ($students->isEmpty() || $courses->isEmpty()) {
            return;
        }

        $demoStudent = $students->first();
        $courseSample = $courses->take(3);

        foreach ($courseSample as $index => $course) {
            $order = Order::query()->create([
                'code' => 'DEMO-' . strtoupper(Str::random(6)),
                'user_id' => $demoStudent->id,
                'total_amount' => $course->price,
                'status' => 'paid',
                'paid_at' => now()->subDays(3 - $index),
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'course_id' => $course->id,
                'price' => $course->price,
            ]);

            Payment::query()->create([
                'order_id' => $order->id,
                'method' => 'vnpay',
                'status' => 'success',
                'amount' => $course->price,
                'paid_at' => $order->paid_at,
            ]);

            $enrollment = Enrollment::query()->create([
                'user_id' => $demoStudent->id,
                'course_id' => $course->id,
                'order_id' => $order->id,
                'progress_percent' => $index === 0 ? 60 : ($index === 1 ? 30 : 10),
                'enrolled_at' => now()->subDays(2 - $index),
            ]);

            $lessons = Lesson::query()->whereHas('chapter', fn ($query) => $query->where('course_id', $course->id))->get();
            $lessonsToComplete = $lessons->take(min(2, $lessons->count()));

            foreach ($lessonsToComplete as $lesson) {
                LessonProgress::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                    'is_completed' => true,
                    'completed_at' => now()->subHours(2),
                ]);
            }

            Review::query()->firstOrCreate(
                ['user_id' => $demoStudent->id, 'course_id' => $course->id],
                [
                    'rating' => $index === 0 ? 5 : 4,
                    'comment' => $index === 0
                        ? 'Khóa học rất thực tế và dễ theo dõi.'
                        : 'Tư duy và nội dung khá rõ ràng, phù hợp cho người mới.',
                ],
            );
        }

        $secondStudent = $students->skip(1)->first();
        if ($secondStudent && $courses->count() > 3) {
            $enrolledCourse = $courses->get(3);
            $order = Order::query()->create([
                'code' => 'DEMO-' . strtoupper(Str::random(6)),
                'user_id' => $secondStudent->id,
                'total_amount' => $enrolledCourse->price,
                'status' => 'paid',
                'paid_at' => now()->subDay(),
            ]);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'course_id' => $enrolledCourse->id,
                'price' => $enrolledCourse->price,
            ]);

            Payment::query()->create([
                'order_id' => $order->id,
                'method' => 'vnpay',
                'status' => 'success',
                'amount' => $enrolledCourse->price,
                'paid_at' => $order->paid_at,
            ]);

            $enrollment = Enrollment::query()->create([
                'user_id' => $secondStudent->id,
                'course_id' => $enrolledCourse->id,
                'order_id' => $order->id,
                'progress_percent' => 20,
                'enrolled_at' => now()->subDay(),
            ]);

            $firstLesson = Lesson::query()->whereHas('chapter', fn ($query) => $query->where('course_id', $enrolledCourse->id))->first();
            if ($firstLesson) {
                LessonProgress::query()->create([
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $firstLesson->id,
                    'is_completed' => true,
                    'completed_at' => now()->subHours(4),
                ]);
            }
        }
    }
}
