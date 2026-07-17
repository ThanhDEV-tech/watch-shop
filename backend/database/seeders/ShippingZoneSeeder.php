<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['name' => 'Nội thành', 'fee' => 20000, 'display_order' => 1],
            ['name' => 'Ngoại thành', 'fee' => 35000, 'display_order' => 2],
            ['name' => 'Tỉnh/thành khác', 'fee' => 45000, 'display_order' => 3],
        ];

        foreach ($zones as $zone) {
            ShippingZone::query()->updateOrCreate(
                ['name' => $zone['name']],
                $zone + ['is_active' => true],
            );
        }
    }
}
