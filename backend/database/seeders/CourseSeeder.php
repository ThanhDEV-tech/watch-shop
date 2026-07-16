<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'instructor'))
            ->get();
        $categories = Category::query()->where('is_active', true)->get();

        if ($instructors->isEmpty() || $categories->isEmpty()) {
            return;
        }

        $courses = [
            [
                'title' => 'Laravel REST API từ cơ bản đến thực chiến',
                'slug' => 'laravel-rest-api-tu-co-ban-den-thuc-chien',
                'description' => 'Xây dựng API hiện đại với Laravel, authentication, validation và testing.',
                'price' => 1290000,
                'discount_price' => 999000,
                'level' => 'beginner',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'web-development',
                'instructor_email' => 'instructor1@edumarket.com',
                'chapters' => [
                    ['title' => 'Thiết lập project và cấu trúc API', 'lessons' => [
                        ['title' => 'Giới thiệu Laravel API', 'content' => 'Tạo project, cấu hình môi trường và hiểu luồng request-response.', 'duration_seconds' => 900, 'is_free_preview' => true],
                        ['title' => 'Routes, Controllers và Resource', 'content' => 'Định nghĩa routes và tạo controller để xử lý request.', 'duration_seconds' => 1200],
                        ['title' => 'Validation và Form Request', 'content' => 'Dùng Form Request để chuẩn hóa và kiểm tra dữ liệu đầu vào.', 'duration_seconds' => 1050],
                    ]],
                    ['title' => 'Authentication và bảo mật', 'lessons' => [
                        ['title' => 'Sử dụng Sanctum cho API', 'content' => 'Thiết lập token và bảo vệ route bằng middleware.', 'duration_seconds' => 1100],
                        ['title' => 'Role-based access control', 'content' => 'Phân quyền theo vai trò cho người dùng.', 'duration_seconds' => 1000],
                    ]],
                ],
            ],
            [
                'title' => 'Vue 3 Composition API chuyên sâu',
                'slug' => 'vue-3-composition-api-chuyen-sau',
                'description' => 'Tạo UI hiện đại với Vue 3, Pinia, composables và reusable components.',
                'price' => 1490000,
                'discount_price' => 1190000,
                'level' => 'intermediate',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'web-development',
                'instructor_email' => 'instructor2@edumarket.com',
                'chapters' => [
                    ['title' => 'State và composables', 'lessons' => [
                        ['title' => 'Ref, reactive và computed', 'content' => 'Quản lý state hiệu quả trong Vue 3.', 'duration_seconds' => 960],
                        ['title' => 'Tạo composables tái sử dụng', 'content' => 'Tách logic thành composables rõ ràng.', 'duration_seconds' => 1020],
                    ]],
                    ['title' => 'Pinia và lifecycle', 'lessons' => [
                        ['title' => 'Quản lý store với Pinia', 'content' => 'Structuring stores và truy cập dữ liệu linh hoạt.', 'duration_seconds' => 1080, 'is_free_preview' => true],
                        ['title' => 'Lifecycle hooks và SSR basics', 'content' => 'Hiểu vòng đời component và ứng dụng SSR.', 'duration_seconds' => 930],
                    ]],
                ],
            ],
            [
                'title' => 'React Native Mobile Engineering',
                'slug' => 'react-native-mobile-engineering',
                'description' => 'Xây dựng app mobile đa nền tảng với React Native và Expo.',
                'price' => 1790000,
                'discount_price' => 1490000,
                'level' => 'intermediate',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'mobile-development',
                'instructor_email' => 'instructor2@edumarket.com',
                'chapters' => [
                    ['title' => 'Cơ bản React Native', 'lessons' => [
                        ['title' => 'Expo và navigation', 'content' => 'Thiết lập project và điều hướng giữa các màn.', 'duration_seconds' => 990],
                        ['title' => 'Styling và reusable components', 'content' => 'Tạo giao diện và component tái sử dụng.', 'duration_seconds' => 900],
                    ]],
                    ['title' => 'API và state management', 'lessons' => [
                        ['title' => 'Gọi API và cache dữ liệu', 'content' => 'Kết nối backend và cache dữ liệu trên client.', 'duration_seconds' => 1050],
                        ['title' => 'Redux Toolkit cơ bản', 'content' => 'Quản lý state trong ứng dụng mobile.', 'duration_seconds' => 1000],
                    ]],
                ],
            ],
            [
                'title' => 'Python Data Science và Machine Learning',
                'slug' => 'python-data-science-va-machine-learning',
                'description' => 'Học phân tích dữ liệu, biểu đồ và các mô hình ML cơ bản.',
                'price' => 1990000,
                'discount_price' => 1590000,
                'level' => 'beginner',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'data-ai',
                'instructor_email' => 'instructor3@edumarket.com',
                'chapters' => [
                    ['title' => 'Pandas và phân tích dữ liệu', 'lessons' => [
                        ['title' => 'Dataframe và lọc dữ liệu', 'content' => 'Tìm hiểu cách phân tích tập dữ liệu bằng pandas.', 'duration_seconds' => 900],
                        ['title' => 'Visualize dữ liệu với matplotlib', 'content' => 'Biểu diễn dữ liệu bằng chart đơn giản.', 'duration_seconds' => 870],
                    ]],
                    ['title' => 'Model và đánh giá', 'lessons' => [
                        ['title' => 'Training model đầu tiên', 'content' => 'Xây dựng model và huấn luyện trên dữ liệu mẫu.', 'duration_seconds' => 1050],
                        ['title' => 'Đánh giá và cải tiến', 'content' => 'Đo lường độ chính xác và tối ưu mô hình.', 'duration_seconds' => 960],
                    ]],
                ],
            ],
            [
                'title' => 'DevOps với Docker và CI/CD',
                'slug' => 'devops-voi-docker-va-cicd',
                'description' => 'Tạo pipeline CI/CD và triển khai ứng dụng bằng Docker và GitHub Actions.',
                'price' => 1684000,
                'discount_price' => 1399000,
                'level' => 'intermediate',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'devops-cloud',
                'instructor_email' => 'instructor2@edumarket.com',
                'chapters' => [
                    ['title' => 'Docker cơ bản', 'lessons' => [
                        ['title' => 'Container và image', 'content' => 'Hiểu về container, image và Dockerfile.', 'duration_seconds' => 960],
                        ['title' => 'Compose và multi-container', 'content' => 'Chạy nhiều service cùng lúc bằng docker compose.', 'duration_seconds' => 900],
                    ]],
                    ['title' => 'CI/CD và deployment', 'lessons' => [
                        ['title' => 'Pipeline với GitHub Actions', 'content' => 'Tự động build và deploy ứng dụng.', 'duration_seconds' => 1020],
                        ['title' => 'Deploy lên VPS', 'content' => 'Triển khai dịch vụ lên môi trường production.', 'duration_seconds' => 980],
                    ]],
                ],
            ],
            [
                'title' => 'UI/UX Design cho sản phẩm số',
                'slug' => 'ui-ux-design-cho-san-pham-so',
                'description' => 'Thiết kế trải nghiệm người dùng cho web và mobile từ ý tưởng đến prototype.',
                'price' => 1590000,
                'discount_price' => 1290000,
                'level' => 'beginner',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'ui-ux-design',
                'instructor_email' => 'instructor3@edumarket.com',
                'chapters' => [
                    ['title' => 'Research và wireframe', 'lessons' => [
                        ['title' => 'User research basics', 'content' => 'Hiểu nhu cầu người dùng trước khi thiết kế.', 'duration_seconds' => 870],
                        ['title' => 'Wireframe và low fidelity', 'content' => 'Vẽ nhanh cấu trúc giao diện.', 'duration_seconds' => 840],
                    ]],
                    ['title' => 'Prototype và kiểm thử', 'lessons' => [
                        ['title' => 'Prototype tương tác', 'content' => 'Biến wireframe thành prototype có tương tác.', 'duration_seconds' => 930],
                        ['title' => 'Usability testing', 'content' => 'Thu thập phản hồi từ người dùng và cải tiến.', 'duration_seconds' => 910],
                    ]],
                ],
            ],
            [
                'title' => 'Next.js cho doanh nghiệp',
                'slug' => 'nextjs-cho-doanh-nghiep',
                'description' => 'Thiết kế ứng dụng web production-ready với Next.js và TypeScript.',
                'price' => 1890000,
                'discount_price' => 1490000,
                'level' => 'advanced',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'web-development',
                'instructor_email' => 'instructor1@edumarket.com',
                'chapters' => [
                    ['title' => 'Routing và layouts', 'lessons' => [
                        ['title' => 'App router và layouts', 'content' => 'Hiểu cách Next.js tổ chức route và layout.', 'duration_seconds' => 900],
                        ['title' => 'Server components basics', 'content' => 'Tận dụng server components cho hiệu năng.', 'duration_seconds' => 960],
                    ]],
                    ['title' => 'Performance và deployment', 'lessons' => [
                        ['title' => 'Image optimization và caching', 'content' => 'Tối ưu hình ảnh và cache cho sản phẩm thật.', 'duration_seconds' => 1020],
                        ['title' => 'Deploy trên Vercel', 'content' => 'Triển khai ứng dụng lên môi trường production.', 'duration_seconds' => 930],
                    ]],
                ],
            ],
            [
                'title' => 'Node.js và Express REST API nâng cao',
                'slug' => 'nodejs-va-express-rest-api-nang-cao',
                'description' => 'Tạo API production-ready với Node.js, Express và queue processing.',
                'price' => 1390000,
                'discount_price' => 1090000,
                'level' => 'intermediate',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'backend',
                'instructor_email' => 'instructor2@edumarket.com',
                'chapters' => [
                    ['title' => 'Express và middleware', 'lessons' => [
                        ['title' => 'Middleware và error handling', 'content' => 'Xử lý request và lỗi một cách chuyên nghiệp.', 'duration_seconds' => 930],
                        ['title' => 'Validation và request schema', 'content' => 'Dùng schema validation cho API.', 'duration_seconds' => 900],
                    ]],
                    ['title' => 'Queue và background jobs', 'lessons' => [
                        ['title' => 'Schedule jobs và worker', 'content' => 'Xử lý task nền bằng queue.', 'duration_seconds' => 990],
                        ['title' => 'Monitoring và logging', 'content' => 'Theo dõi và ghi log hiệu quả.', 'duration_seconds' => 930],
                    ]],
                ],
            ],
            [
                'title' => 'Design Systems với Figma và Tailwind',
                'slug' => 'design-systems-voi-figma-va-tailwind',
                'description' => 'Xây dựng design system và giao diện nhất quán cho sản phẩm.',
                'price' => 1290000,
                'discount_price' => 999000,
                'level' => 'beginner',
                'status' => 'pending',
                'category_slug' => 'ui-ux-design',
                'instructor_email' => 'instructor3@edumarket.com',
                'chapters' => [
                    ['title' => 'Design tokens và component library', 'lessons' => [
                        ['title' => 'Design tokens', 'content' => 'Tạo tokens và quy ước về màu sắc, spacing.', 'duration_seconds' => 840],
                        ['title' => 'Component system', 'content' => 'Tổ chức component theo design system.', 'duration_seconds' => 900],
                    ]],
                    ['title' => 'Tailwind và implementation', 'lessons' => [
                        ['title' => 'Tailwind cho UI nhất quán', 'content' => 'Áp dụng Tailwind để dựng giao diện nhanh.', 'duration_seconds' => 930],
                        ['title' => 'Accessibility review', 'content' => 'Kiểm tra accessibility cho component.', 'duration_seconds' => 900],
                    ]],
                ],
            ],
            [
                'title' => 'Kubernetes cho người mới bắt đầu',
                'slug' => 'kubernetes-cho-nguoi-moi-bat-dau',
                'description' => 'Hiểu các khái niệm cốt lõi của Kubernetes qua ví dụ thực tế.',
                'price' => 1990000,
                'discount_price' => 1690000,
                'level' => 'advanced',
                'status' => 'pending',
                'category_slug' => 'devops-cloud',
                'instructor_email' => 'instructor1@edumarket.com',
                'chapters' => [
                    ['title' => 'Khái niệm nền tảng', 'lessons' => [
                        ['title' => 'Pod, Service và Deployment', 'content' => 'Hiểu ba khái niệm cốt lõi trong Kubernetes.', 'duration_seconds' => 960],
                        ['title' => 'ConfigMaps và Secrets', 'content' => 'Quản lý cấu hình và bí mật trong cluster.', 'duration_seconds' => 900],
                    ]],
                    ['title' => 'Debug và scaling', 'lessons' => [
                        ['title' => 'Scaling và rollout', 'content' => 'Tăng giảm số pod và rollout updates.', 'duration_seconds' => 1000],
                        ['title' => 'Troubleshooting basics', 'content' => 'Khắc phục lỗi thường gặp trong deployment.', 'duration_seconds' => 930],
                    ]],
                ],
            ],
            [
                'title' => 'Fullstack E-commerce với Laravel + Vue',
                'slug' => 'fullstack-ecommerce-voi-laravel-vue',
                'description' => 'Xây dựng hệ thống thương mại điện tử từ frontend tới backend.',
                'price' => 2490000,
                'discount_price' => 1990000,
                'level' => 'advanced',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'web-development',
                'instructor_email' => 'instructor1@edumarket.com',
                'chapters' => [
                    ['title' => 'Backend và database', 'lessons' => [
                        ['title' => 'Model, migration và seeding', 'content' => 'Thiết kế database và seed dữ liệu ban đầu.', 'duration_seconds' => 900],
                        ['title' => 'Orders và payments', 'content' => 'Xử lý thanh toán và đơn hàng trong hệ thống.', 'duration_seconds' => 990],
                    ]],
                    ['title' => 'Frontend và UX', 'lessons' => [
                        ['title' => 'Dashboard và cart flow', 'content' => 'Dựng luồng mua hàng và dashboard cho người dùng.', 'duration_seconds' => 1020],
                        ['title' => 'Testing và deployment', 'content' => 'Kiểm thử và triển khai sản phẩm hoàn chỉnh.', 'duration_seconds' => 960],
                    ]],
                ],
            ],
            [
                'title' => 'Docker cho DevOps chuyên nghiệp',
                'slug' => 'docker-cho-devops-chuyen-nghiep',
                'description' => 'Khóa học dùng Docker để đóng gói service và triển khai production.',
                'price' => 1490000,
                'discount_price' => 1190000,
                'level' => 'intermediate',
                'status' => 'rejected',
                'reject_reason' => 'Nội dung cần bổ sung hơn về security và networking trước khi duyệt.',
                'category_slug' => 'devops-cloud',
                'instructor_email' => 'instructor2@edumarket.com',
                'chapters' => [
                    ['title' => 'Docker networking', 'lessons' => [
                        ['title' => 'Bridge network và port mapping', 'content' => 'Hiểu cách container giao tiếp với nhau.', 'duration_seconds' => 900],
                        ['title' => 'Volumes và persistence', 'content' => 'Lưu trữ dữ liệu bền vững giữa các container.', 'duration_seconds' => 870],
                    ]],
                    ['title' => 'Image hardening', 'lessons' => [
                        ['title' => 'Best practices cho Dockerfile', 'content' => 'Tối ưu Dockerfile để tăng security và hiệu suất.', 'duration_seconds' => 930],
                        ['title' => 'Scan image và vulnerability', 'content' => 'Sử dụng công cụ để quét lỗ hổng trong image.', 'duration_seconds' => 900],
                    ]],
                ],
            ],
            [
                'title' => 'Adobe Illustrator cho UI/UX',
                'slug' => 'adobe-illustrator-cho-ui-ux',
                'description' => 'Tạo hình ảnh minh họa, icon và moodboard cho sản phẩm số.',
                'price' => 990000,
                'discount_price' => 799000,
                'level' => 'beginner',
                'status' => 'approved',
                'published_at' => now(),
                'category_slug' => 'graphic-design',
                'instructor_email' => 'instructor3@edumarket.com',
                'chapters' => [
                    ['title' => 'Illustration cơ bản', 'lessons' => [
                        ['title' => 'Shape tools và đường cơ bản', 'content' => 'Làm quen với công cụ vẽ cơ bản trong Illustrator.', 'duration_seconds' => 900],
                        ['title' => 'Tạo icon và vector', 'content' => 'Tạo icon và hình ảnh vector cho product design.', 'duration_seconds' => 870],
                    ]],
                    ['title' => 'Branding và export', 'lessons' => [
                        ['title' => 'Tạo moodboard', 'content' => 'Xây dựng moodboard cho chiến dịch thiết kế.', 'duration_seconds' => 930],
                        ['title' => 'Export cho web và mobile', 'content' => 'Xuất file đúng chuẩn cho nhiều nền tảng.', 'duration_seconds' => 900],
                    ]],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $instructor = $instructors->firstWhere('email', $courseData['instructor_email']) ?? $instructors->first();
            $category = $categories->firstWhere('slug', $courseData['category_slug']) ?? $categories->first();

            $course = Course::query()->updateOrCreate(
                ['slug' => $courseData['slug']],
                [
                    'instructor_id' => $instructor->id,
                    'category_id' => $category->id,
                    'title' => $courseData['title'],
                    'slug' => $courseData['slug'],
                    'description' => $courseData['description'],
                    'price' => $courseData['price'],
                    'discount_price' => $courseData['discount_price'],
                    'level' => $courseData['level'],
                    'status' => $courseData['status'],
                    'reject_reason' => $courseData['reject_reason'] ?? null,
                    'published_at' => $courseData['published_at'] ?? null,
                    'rating_avg' => in_array($courseData['status'], ['approved']) ? 4.6 : 0,
                ],
            );

            $course->chapters()->delete();

            foreach ($courseData['chapters'] as $position => $chapterData) {
                $chapter = $course->chapters()->create([
                    'title' => $chapterData['title'],
                    'description' => 'Bài học thực hành phù hợp cho demo sản phẩm.',
                    'position' => $position + 1,
                ]);

                foreach ($chapterData['lessons'] as $lessonPosition => $lessonData) {
                    $chapter->lessons()->create([
                        'title' => $lessonData['title'],
                        'content' => $lessonData['content'],
                        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                        'duration_seconds' => $lessonData['duration_seconds'],
                        'position' => $lessonPosition + 1,
                        'is_free_preview' => $lessonData['is_free_preview'] ?? false,
                    ]);
                }
            }
        }
    }
}
