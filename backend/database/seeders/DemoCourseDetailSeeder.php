<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCourseDetailSeeder extends Seeder
{
    public function run(): void
    {
        Course::query()
            ->where(function ($query) {
                $query->whereNull('requirements')
                    ->orWhere('requirements', '[]');
            })
            ->get()
            ->each(fn (Course $course) => $course->update([
                'requirements' => [
                    'Có máy tính cá nhân và kết nối internet ổn định.',
                    'Sẵn sàng thực hành theo từng bài học.',
                    'Hiểu kiến thức lập trình cơ bản là một lợi thế.',
                ],
            ]));

        User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'instructor'))
            ->whereNull('bio')
            ->get()
            ->each(fn (User $user) => $user->update([
                'bio' => 'Giảng viên EduMarket với kinh nghiệm thực chiến, tập trung vào bài học rõ ràng, dễ áp dụng và gắn với dự án thật.',
            ]));
    }
}
