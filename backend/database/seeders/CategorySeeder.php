<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Dress Watch',
                'slug' => 'dress-watch',
                'description' => 'Dong ho thanh lich cho cong so, su kien va trang phuc formal.',
                'icon' => 'watch',
                'accent_color' => '#BFA06A',
            ],
            [
                'name' => 'Sport Watch',
                'slug' => 'sport-watch',
                'description' => 'Thiet ke nang dong, de deo hang ngay va phu hop lich trinh di chuyen.',
                'icon' => 'fitness_center',
                'accent_color' => '#4C8C7A',
            ],
            [
                'name' => 'Casual Watch',
                'slug' => 'casual-watch',
                'description' => 'Dong ho de phoi do, can bang giua tinh ung dung va diem nhan ca nhan.',
                'icon' => 'routine',
                'accent_color' => '#D77A61',
            ],
            [
                'name' => 'Minimal Watch',
                'slug' => 'minimal-watch',
                'description' => 'Mat so gon, duong net sach va phong cach toi gian lau loi mot.',
                'icon' => 'radio_button_unchecked',
                'accent_color' => '#8C8C8C',
            ],
            [
                'name' => 'Sport-Casual',
                'slug' => 'sport-casual',
                'description' => 'Lua chon linh hoat giua van dong nhe, di lam va gap go cuoi tuan.',
                'icon' => 'explore',
                'accent_color' => '#476A8E',
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true],
            );
        }
    }
}
