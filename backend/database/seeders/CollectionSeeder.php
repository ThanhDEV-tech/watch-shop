<?php

namespace Database\Seeders;

use App\Models\Collection;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $collections = [
            [
                'name' => 'Summer 2026',
                'slug' => 'summer-2026',
                'description' => 'Nhung mau dong ho sang, nhe va de deo trong mua he.',
                'start_date' => '2026-05-01',
                'end_date' => '2026-08-31',
            ],
            [
                'name' => 'Office Style',
                'slug' => 'office-style',
                'description' => 'Lua chon gon gu, chuyen nghiep cho di lam va gap khach hang.',
                'start_date' => null,
                'end_date' => null,
            ],
            [
                'name' => 'Evening Gifting',
                'slug' => 'evening-gifting',
                'description' => 'Dong ho co sac thai trang trong, phu hop dip sinh nhat, ky niem va tri an.',
                'start_date' => '2026-10-01',
                'end_date' => '2027-01-15',
            ],
            [
                'name' => 'Couple Watches',
                'slug' => 'couple-watches',
                'description' => 'Cac mau nam nu de di thanh cap ma van dep khi deo rieng.',
                'start_date' => null,
                'end_date' => null,
            ],
        ];

        foreach ($collections as $collection) {
            Collection::query()->updateOrCreate(['slug' => $collection['slug']], $collection);
        }
    }
}
