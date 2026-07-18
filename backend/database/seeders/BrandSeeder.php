<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Monarch Studio', 'country' => 'Viet Nam', 'description' => 'Thiet ke thanh lich, gia de tiep can cho nguoi tre di lam.'],
            ['name' => 'Aurora Time', 'country' => 'Singapore', 'description' => 'Dong ho toi gian voi tong mau sang, phu hop qua tang va phong cach cong so.'],
            ['name' => 'Lumen & Co.', 'country' => 'South Korea', 'description' => 'Bo suu tap hien dai, mat so ro net va day deo de phoi do hang ngay.'],
            ['name' => 'Noble Coast', 'country' => 'Malaysia', 'description' => 'Phong cach sport-casual, ben bi vua du cho lich trinh nang dong.'],
            ['name' => 'Velvet Hour', 'country' => 'Thailand', 'description' => 'Nhung mau dress watch mem mai, tinh te, uu tien cam giac deo nhe.'],
            ['name' => 'Urban Crest', 'country' => 'Indonesia', 'description' => 'Dong ho pho thi voi nhan dien gon, de ban, de tang va de dung lau dai.'],
        ];

        foreach ($brands as $brand) {
            $slug = str($brand['name'])->slug()->toString();

            Brand::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    ...$brand,
                    'slug' => $slug,
                    'logo' => "https://placehold.co/320x160/F8F5EF/111111?text=".rawurlencode($brand['name']),
                    'is_active' => true,
                ],
            );
        }
    }
}
