<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            CollectionSeeder::class,
            ProductSeeder::class,
            ProductVariantSeeder::class,
            ShippingZoneSeeder::class,
            UserSeeder::class,
        ]);
    }
}
