<?php

namespace Database\Seeders;

use App\Models\Certification;
use Illuminate\Database\Seeder;

class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        $certifications = [
            [
                'name' => 'AWS Certified Solutions Architect',
                'provider' => 'AWS',
                'description' => 'Xác nhận khả năng thiết kế hệ thống phân tán an toàn, linh hoạt và tối ưu chi phí trên AWS.',
                'icon' => 'cloud_done',
                'accent_color' => '#FF9900',
                'exam_info' => 'Kỳ thi tập trung vào thiết kế kiến trúc bảo mật, khả năng phục hồi, hiệu năng cao và tối ưu chi phí trên nền tảng AWS.',
                'external_link' => null,
            ],
            [
                'name' => 'CompTIA Security+',
                'provider' => 'CompTIA',
                'description' => 'Chứng chỉ nền tảng về an ninh mạng, quản trị rủi ro và ứng phó sự cố bảo mật.',
                'icon' => 'shield',
                'accent_color' => '#C8102E',
                'exam_info' => 'Nội dung thi bao gồm các mối đe dọa, kiến trúc bảo mật, vận hành an ninh, quản trị rủi ro và mật mã học cơ bản.',
                'external_link' => null,
            ],
            [
                'name' => 'Professional Cloud Architect',
                'provider' => 'Google Cloud',
                'description' => 'Đánh giá năng lực thiết kế, triển khai và quản lý giải pháp trên Google Cloud.',
                'icon' => 'cloud',
                'accent_color' => '#4285F4',
                'exam_info' => 'Kỳ thi đánh giá kỹ năng thiết kế kiến trúc cloud, bảo mật, tuân thủ, tối ưu quy trình và đảm bảo độ tin cậy của giải pháp.',
                'external_link' => null,
            ],
            [
                'name' => 'Project Management Professional (PMP)',
                'provider' => 'PMI',
                'description' => 'Chứng nhận năng lực lãnh đạo dự án theo phương pháp predictive, agile và hybrid.',
                'icon' => 'workspace_premium',
                'accent_color' => '#A100FF',
                'exam_info' => 'Kỳ thi bao quát ba nhóm năng lực People, Process và Business Environment trong quản lý dự án hiện đại.',
                'external_link' => null,
            ],
            [
                'name' => 'Azure Developer Associate',
                'provider' => 'Microsoft',
                'description' => 'Xác nhận kỹ năng xây dựng, triển khai và vận hành ứng dụng, dịch vụ trên Microsoft Azure.',
                'icon' => 'developer_mode',
                'accent_color' => '#0078D4',
                'exam_info' => 'Nội dung tập trung vào phát triển giải pháp Azure, lưu trữ, bảo mật, giám sát và tích hợp dịch vụ của bên thứ ba.',
                'external_link' => null,
            ],
        ];

        foreach ($certifications as $certification) {
            Certification::query()->updateOrCreate(
                ['name' => $certification['name'], 'provider' => $certification['provider']],
                $certification,
            );
        }
    }
}
