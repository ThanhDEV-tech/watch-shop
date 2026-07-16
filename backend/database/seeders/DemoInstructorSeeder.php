<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoInstructorSeeder extends Seeder
{
    public function run(): void
    {
        $instructorRole = Role::query()->firstOrCreate(
            ['name' => 'instructor'],
            ['display_name' => 'Instructor'],
        );

        $instructors = [
            [
                'email' => 'mobile.nguyen@edumarket.com',
                'name' => 'Nguyễn Anh Khoa',
                'bio' => 'Giảng viên mobile engineering, tập trung vào Flutter, React Native và quy trình phát hành ứng dụng thực tế.',
            ],
            [
                'email' => 'ux.linh@edumarket.com',
                'name' => 'Lê Mỹ Linh',
                'bio' => 'Product designer hướng dẫn research, wireframe, prototype và kiểm thử trải nghiệm người dùng cho sản phẩm số.',
            ],
            [
                'email' => 'design.bao@edumarket.com',
                'name' => 'Phạm Quốc Bảo',
                'bio' => 'Graphic designer chuyên branding, icon system và minh họa số cho đội ngũ sản phẩm công nghệ.',
            ],
            [
                'email' => 'cloud.han@edumarket.com',
                'name' => 'Hoàng Gia Hân',
                'bio' => 'Cloud engineer chia sẻ kinh nghiệm triển khai hệ thống cloud native, CI/CD và observability.',
            ],
        ];

        foreach ($instructors as $instructor) {
            User::query()->updateOrCreate(
                ['email' => $instructor['email']],
                [
                    'role_id' => $instructorRole->id,
                    'name' => $instructor['name'],
                    'password' => Hash::make('password'),
                    'bio' => $instructor['bio'],
                    'is_active' => true,
                ],
            );
        }

        $this->seedCourses();
    }

    private function seedCourses(): void
    {
        $courses = [
            [
                'instructor_email' => 'mobile.nguyen@edumarket.com',
                'category_slug' => 'mobile-development',
                'title' => 'Flutter Mobile Apps từ UI đến Store',
                'slug' => 'flutter-mobile-apps-tu-ui-den-store',
                'description' => 'Xây dựng ứng dụng Flutter hoàn chỉnh, tối ưu UI responsive và chuẩn bị phát hành lên app store.',
                'price' => 1590000,
                'discount_price' => 1290000,
                'level' => 'intermediate',
            ],
            [
                'instructor_email' => 'mobile.nguyen@edumarket.com',
                'category_slug' => 'mobile-development',
                'title' => 'React Native Performance Patterns',
                'slug' => 'react-native-performance-patterns',
                'description' => 'Tối ưu hiệu năng app React Native, xử lý navigation, cache dữ liệu và debug trải nghiệm mobile.',
                'price' => 1490000,
                'discount_price' => 1190000,
                'level' => 'advanced',
            ],
            [
                'instructor_email' => 'ux.linh@edumarket.com',
                'category_slug' => 'ui-ux-design',
                'title' => 'UX Research và Prototype Thực Chiến',
                'slug' => 'ux-research-va-prototype-thuc-chien',
                'description' => 'Từ phỏng vấn người dùng đến prototype tương tác, học cách biến insight thành giao diện rõ ràng.',
                'price' => 1390000,
                'discount_price' => 1090000,
                'level' => 'beginner',
            ],
            [
                'instructor_email' => 'ux.linh@edumarket.com',
                'category_slug' => 'ui-ux-design',
                'title' => 'Design Portfolio cho Product Designer',
                'slug' => 'design-portfolio-cho-product-designer',
                'description' => 'Xây dựng case study, storytelling và portfolio thể hiện tư duy thiết kế sản phẩm.',
                'price' => 990000,
                'discount_price' => 799000,
                'level' => 'beginner',
            ],
            [
                'instructor_email' => 'design.bao@edumarket.com',
                'category_slug' => 'graphic-design',
                'title' => 'Brand Identity với Illustrator và Figma',
                'slug' => 'brand-identity-voi-illustrator-va-figma',
                'description' => 'Thiết kế nhận diện thương hiệu, icon set và guideline hình ảnh cho sản phẩm số.',
                'price' => 1290000,
                'discount_price' => 990000,
                'level' => 'intermediate',
            ],
            [
                'instructor_email' => 'design.bao@edumarket.com',
                'category_slug' => 'graphic-design',
                'title' => 'Visual Design cho Landing Page Công Nghệ',
                'slug' => 'visual-design-cho-landing-page-cong-nghe',
                'description' => 'Tạo hệ thống hình ảnh, màu sắc và layout landing page đồng bộ với sản phẩm công nghệ.',
                'price' => 1190000,
                'discount_price' => 890000,
                'level' => 'beginner',
            ],
            [
                'instructor_email' => 'cloud.han@edumarket.com',
                'category_slug' => 'devops-cloud',
                'title' => 'Google Cloud Deployment cho Web App',
                'slug' => 'google-cloud-deployment-cho-web-app',
                'description' => 'Triển khai web app lên Google Cloud, cấu hình database, logging và monitoring cơ bản.',
                'price' => 1790000,
                'discount_price' => 1390000,
                'level' => 'intermediate',
            ],
            [
                'instructor_email' => 'cloud.han@edumarket.com',
                'category_slug' => 'devops-cloud',
                'title' => 'CI/CD Pipeline cho Team Nhỏ',
                'slug' => 'cicd-pipeline-cho-team-nho',
                'description' => 'Thiết kế pipeline build, test, deploy đơn giản nhưng chắc chắn cho startup và team sản phẩm.',
                'price' => 1490000,
                'discount_price' => 1190000,
                'level' => 'intermediate',
            ],
        ];

        foreach ($courses as $courseData) {
            $instructor = User::query()->where('email', $courseData['instructor_email'])->first();
            $category = Category::query()->where('slug', $courseData['category_slug'])->first();

            if (! $instructor || ! $category) {
                continue;
            }

            $course = Course::query()->updateOrCreate(
                ['slug' => $courseData['slug']],
                [
                    'instructor_id' => $instructor->id,
                    'category_id' => $category->id,
                    'title' => $courseData['title'],
                    'description' => $courseData['description'],
                    'content' => 'Khóa học demo được thiết kế để bổ sung dữ liệu instructor spotlight và danh mục chuyên môn.',
                    'requirements' => ['Biết sử dụng máy tính cơ bản', 'Sẵn sàng thực hành theo từng bài học'],
                    'price' => $courseData['price'],
                    'discount_price' => $courseData['discount_price'],
                    'level' => $courseData['level'],
                    'status' => 'approved',
                    'reject_reason' => null,
                    'rating_avg' => 4.7,
                    'published_at' => now(),
                ],
            );

            $course->chapters()->delete();

            $chapter = $course->chapters()->create([
                'title' => 'Bắt đầu với '.$courseData['title'],
                'description' => 'Nền tảng và bài thực hành đầu tiên.',
                'position' => 1,
            ]);

            $chapter->lessons()->createMany([
                [
                    'title' => 'Tổng quan lộ trình',
                    'content' => 'Giới thiệu mục tiêu khóa học, công cụ cần chuẩn bị và cách học hiệu quả.',
                    'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'duration_seconds' => 840,
                    'position' => 1,
                    'is_free_preview' => true,
                ],
                [
                    'title' => 'Bài thực hành đầu tiên',
                    'content' => 'Áp dụng kiến thức vào một bài thực hành nhỏ để kiểm tra quy trình làm việc.',
                    'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'duration_seconds' => 1020,
                    'position' => 2,
                    'is_free_preview' => false,
                ],
            ]);
        }
    }
}
