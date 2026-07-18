<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@watchora.vn';

    public const ADMIN_PASSWORD = 'password';

    public function run(): void
    {
        $roleIds = Role::query()
            ->whereIn('name', ['admin', 'customer'])
            ->pluck('id', 'name');

        User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'role_id' => $roleIds->get('admin'),
                'name' => 'Watchora Admin',
                'password' => Hash::make(self::ADMIN_PASSWORD),
                'phone' => '0900000001',
                'is_active' => true,
            ],
        );

        $customers = [
            ['email' => 'linh.nguyen@example.test', 'name' => 'Linh Nguyen', 'phone' => '0900000101'],
            ['email' => 'minh.tran@example.test', 'name' => 'Minh Tran', 'phone' => '0900000102'],
            ['email' => 'mai.hoang@example.test', 'name' => 'Mai Hoang', 'phone' => '0900000103'],
        ];

        foreach ($customers as $customer) {
            User::query()->updateOrCreate(
                ['email' => $customer['email']],
                [
                    ...$customer,
                    'role_id' => $roleIds->get('customer'),
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );
        }
    }
}
