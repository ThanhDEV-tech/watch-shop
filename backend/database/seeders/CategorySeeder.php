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
                'name' => 'Backend',
                'slug' => 'backend',
                'description' => 'Xây dựng API, hệ thống phân tán và kiến trúc server-side hiện đại.',
                'icon' => 'cloud',
                'accent_color' => '#4ADE80',
                'is_active' => true,
            ],
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Frontend và backend cho các sản phẩm web hiện đại.',
                'icon' => 'code',
                'accent_color' => '#FF6B4A',
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Development',
                'slug' => 'mobile-development',
                'description' => 'Phát triển ứng dụng di động đa nền tảng và native.',
                'icon' => 'smartphone',
                'accent_color' => '#A78BFA',
                'is_active' => true,
            ],
            [
                'name' => 'Data & AI',
                'slug' => 'data-ai',
                'description' => 'Phân tích dữ liệu, machine learning và trí tuệ nhân tạo.',
                'icon' => 'psychology',
                'accent_color' => '#F472B6',
                'is_active' => true,
            ],
            [
                'name' => 'DevOps & Cloud',
                'slug' => 'devops-cloud',
                'description' => 'Triển khai, vận hành, CI/CD và hạ tầng cloud.',
                'icon' => 'terminal',
                'accent_color' => '#38BDF8',
                'is_active' => true,
            ],
            [
                'name' => 'UI/UX Design',
                'slug' => 'ui-ux-design',
                'description' => 'Thiết kế giao diện và trải nghiệm người dùng cho sản phẩm số.',
                'icon' => 'palette',
                'accent_color' => '#FBBF24',
                'is_active' => true,
            ],
            [
                'name' => 'Graphic Design',
                'slug' => 'graphic-design',
                'description' => 'Thiết kế đồ họa và hình ảnh thương hiệu cho sản phẩm số.',
                'icon' => 'brush',
                'accent_color' => '#FDBA74',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
