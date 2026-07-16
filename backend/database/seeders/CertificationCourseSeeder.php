<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Certification;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CertificationCourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'instructor'))
            ->first();

        $devopsCategory = Category::query()->where('slug', 'devops-cloud')->first();
        $backendCategory = Category::query()->where('slug', 'backend')->first();

        if (! $instructor || ! $devopsCategory) {
            return;
        }

        $courses = [
            [
                'title' => 'AWS Certified Solutions Architect - Lộ trình ôn thi thực chiến',
                'description' => 'Chuẩn bị cho chứng chỉ AWS Solutions Architect với kiến trúc cloud, IAM, networking, storage và các tình huống thiết kế hệ thống.',
                'content' => 'Khóa học tập trung vào cách đọc yêu cầu đề thi, chọn dịch vụ AWS phù hợp, thiết kế hệ thống bền vững và tối ưu chi phí theo đúng tinh thần kỳ thi Solutions Architect.',
                'price' => 2490000,
                'discount_price' => 1990000,
                'level' => 'intermediate',
                'category_id' => $devopsCategory->id,
                'chapters' => [
                    ['title' => 'Nền tảng AWS cho kỳ thi', 'lessons' => [
                        ['title' => 'Cấu trúc đề thi và chiến lược học', 'content' => 'Tìm hiểu domain chính của kỳ thi, cách phân bổ thời gian học và đọc câu hỏi tình huống.', 'duration_seconds' => 960, 'is_free_preview' => true],
                        ['title' => 'IAM, VPC và bảo mật nền tảng', 'content' => 'Ôn tập IAM policy, security group, subnet, route table và các mô hình mạng thường gặp.', 'duration_seconds' => 1320],
                    ]],
                    ['title' => 'Thiết kế kiến trúc AWS', 'lessons' => [
                        ['title' => 'High availability với EC2, ALB và Auto Scaling', 'content' => 'Thiết kế kiến trúc nhiều AZ, cân bằng tải và tự động mở rộng theo nhu cầu.', 'duration_seconds' => 1260],
                        ['title' => 'S3, RDS và chiến lược backup', 'content' => 'Chọn dịch vụ lưu trữ đúng bài toán và thiết kế phục hồi dữ liệu an toàn.', 'duration_seconds' => 1180],
                    ]],
                ],
            ],
            [
                'title' => 'CompTIA Security+ căn bản đến luyện đề',
                'description' => 'Học nền tảng bảo mật, quản trị rủi ro, threat modeling, cryptography và luyện câu hỏi theo phong cách Security+.',
                'content' => 'Khóa học giúp học viên nắm vững ngôn ngữ bảo mật cốt lõi, hiểu tình huống thực tế và luyện phản xạ chọn đáp án trong kỳ thi Security+.',
                'price' => 1890000,
                'discount_price' => 1490000,
                'level' => 'beginner',
                'category_id' => $backendCategory?->id ?? $devopsCategory->id,
                'chapters' => [
                    ['title' => 'Security foundation', 'lessons' => [
                        ['title' => 'CIA triad, risk và threat actor', 'content' => 'Nắm các khái niệm nền tảng về bảo mật, rủi ro và tác nhân đe dọa.', 'duration_seconds' => 900, 'is_free_preview' => true],
                        ['title' => 'Authentication, authorization và identity', 'content' => 'Phân biệt xác thực, phân quyền, MFA, SSO và các mô hình quản lý danh tính.', 'duration_seconds' => 1080],
                    ]],
                    ['title' => 'Luyện đề Security+', 'lessons' => [
                        ['title' => 'Network security scenarios', 'content' => 'Phân tích tình huống firewall, IDS/IPS, segmentation và secure protocol.', 'duration_seconds' => 1200],
                        ['title' => 'Cryptography và incident response', 'content' => 'Ôn tập mã hóa, hashing, certificate và quy trình ứng phó sự cố.', 'duration_seconds' => 1140],
                    ]],
                ],
            ],
            [
                'title' => 'Google Cloud Professional Cloud Architect Bootcamp',
                'description' => 'Thiết kế giải pháp trên Google Cloud, tối ưu reliability, security, operation và cost cho kỳ thi Professional Cloud Architect.',
                'content' => 'Bootcamp đi qua các case study thường gặp, kiến trúc GKE/Cloud Run, IAM, networking, data services và phương pháp chọn đáp án trong đề thi Google Cloud.',
                'price' => 2590000,
                'discount_price' => 2090000,
                'level' => 'advanced',
                'category_id' => $devopsCategory->id,
                'chapters' => [
                    ['title' => 'Google Cloud architecture essentials', 'lessons' => [
                        ['title' => 'Exam guide và case study mindset', 'content' => 'Đọc exam guide, nhận diện keyword trong case study và lập kế hoạch ôn thi.', 'duration_seconds' => 980, 'is_free_preview' => true],
                        ['title' => 'IAM, organization và networking', 'content' => 'Thiết kế quyền truy cập, project structure, VPC và hybrid connectivity.', 'duration_seconds' => 1240],
                    ]],
                    ['title' => 'Modern workload trên GCP', 'lessons' => [
                        ['title' => 'GKE, Cloud Run và serverless patterns', 'content' => 'So sánh các mô hình compute và chọn dịch vụ phù hợp theo yêu cầu.', 'duration_seconds' => 1320],
                        ['title' => 'Observability, cost và reliability', 'content' => 'Thiết kế monitoring, alerting, budget và kiến trúc chịu lỗi.', 'duration_seconds' => 1160],
                    ]],
                ],
            ],
            [
                'title' => 'PMP Project Management Professional - Agile & Hybrid',
                'description' => 'Ôn PMP theo ba domain People, Process, Business Environment, kết hợp predictive, agile và hybrid project management.',
                'content' => 'Khóa học giúp học viên hiểu tư duy quản lý dự án hiện đại, luyện tình huống ra quyết định và chuẩn bị cho kỳ thi PMP với ví dụ gần thực tế.',
                'price' => 2290000,
                'discount_price' => 1790000,
                'level' => 'intermediate',
                'category_id' => $devopsCategory->id,
                'chapters' => [
                    ['title' => 'PMP mindset', 'lessons' => [
                        ['title' => 'People domain và servant leadership', 'content' => 'Hiểu vai trò PM, stakeholder engagement và cách xử lý xung đột đội nhóm.', 'duration_seconds' => 1020, 'is_free_preview' => true],
                        ['title' => 'Predictive, agile và hybrid lifecycle', 'content' => 'Phân biệt vòng đời dự án và chọn cách quản trị phù hợp từng bối cảnh.', 'duration_seconds' => 1180],
                    ]],
                    ['title' => 'Luyện tình huống PMP', 'lessons' => [
                        ['title' => 'Risk, scope và change control', 'content' => 'Luyện các tình huống thay đổi phạm vi, quản trị rủi ro và ưu tiên hành động.', 'duration_seconds' => 1260],
                        ['title' => 'Business value và exam strategy', 'content' => 'Liên hệ quyết định dự án với giá trị kinh doanh và chiến thuật làm bài.', 'duration_seconds' => 1100],
                    ]],
                ],
            ],
            [
                'title' => 'Azure Developer Associate AZ-204 thực chiến',
                'description' => 'Chuẩn bị cho AZ-204 với Azure App Service, Functions, Storage, Security, Monitoring và tích hợp dịch vụ cloud.',
                'content' => 'Khóa học hướng dẫn xây dựng ứng dụng cloud-native trên Azure, tập trung vào kỹ năng developer cần cho chứng chỉ Azure Developer Associate.',
                'price' => 2190000,
                'discount_price' => 1690000,
                'level' => 'intermediate',
                'category_id' => $devopsCategory->id,
                'chapters' => [
                    ['title' => 'Azure development foundation', 'lessons' => [
                        ['title' => 'AZ-204 exam map và môi trường Azure', 'content' => 'Nắm phạm vi kỳ thi, setup môi trường và các dịch vụ developer thường dùng.', 'duration_seconds' => 900, 'is_free_preview' => true],
                        ['title' => 'App Service, Functions và deployment slots', 'content' => 'Triển khai ứng dụng, serverless function và quản lý deployment an toàn.', 'duration_seconds' => 1210],
                    ]],
                    ['title' => 'Security, storage và monitoring', 'lessons' => [
                        ['title' => 'Azure Storage, Cosmos DB và cache', 'content' => 'Chọn dịch vụ lưu trữ và tối ưu dữ liệu cho ứng dụng cloud.', 'duration_seconds' => 1160],
                        ['title' => 'Managed Identity, Key Vault và Application Insights', 'content' => 'Bảo mật secret, truy cập dịch vụ và quan sát ứng dụng production.', 'duration_seconds' => 1280],
                    ]],
                ],
            ],
        ];

        foreach ($courses as $courseData) {
            $slug = Str::slug($courseData['title']);

            $course = Course::withTrashed()->updateOrCreate(
                ['slug' => $slug],
                [
                    'instructor_id' => $instructor->id,
                    'category_id' => $courseData['category_id'],
                    'title' => $courseData['title'],
                    'slug' => $slug,
                    'description' => $courseData['description'],
                    'content' => $courseData['content'],
                    'price' => $courseData['price'],
                    'discount_price' => $courseData['discount_price'],
                    'level' => $courseData['level'],
                    'status' => 'approved',
                    'reject_reason' => null,
                    'published_at' => now(),
                    'rating_avg' => 4.7,
                ],
            );

            if ($course->trashed()) {
                $course->restore();
            }

            $course->chapters()->delete();

            foreach ($courseData['chapters'] as $chapterPosition => $chapterData) {
                $chapter = $course->chapters()->create([
                    'title' => $chapterData['title'],
                    'description' => 'Nội dung ôn chứng chỉ được thiết kế theo lộ trình thực chiến.',
                    'position' => $chapterPosition + 1,
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

            $certification = $this->matchingCertification($course->title);

            if ($certification) {
                $course->certifications()->syncWithoutDetaching([$certification->id]);
            }
        }
    }

    private function matchingCertification(string $courseTitle): ?Certification
    {
        $normalizedTitle = Str::lower($courseTitle);

        return match (true) {
            str_contains($normalizedTitle, 'aws') => Certification::query()->where('provider', 'AWS')->first(),
            str_contains($normalizedTitle, 'comptia') || str_contains($normalizedTitle, 'security+') => Certification::query()->where('provider', 'CompTIA')->first(),
            str_contains($normalizedTitle, 'google cloud') => Certification::query()->where('provider', 'Google Cloud')->first(),
            str_contains($normalizedTitle, 'pmp') => Certification::query()->where('provider', 'PMI')->first(),
            str_contains($normalizedTitle, 'azure') => Certification::query()->where('provider', 'Microsoft')->first(),
            default => null,
        };
    }
}
