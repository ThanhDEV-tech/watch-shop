<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleIds = Role::query()
            ->whereIn('name', ['admin', 'instructor', 'student'])
            ->pluck('id', 'name');

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@edumarket.com'],
            [
                'role_id' => $roleIds->get('admin'),
                'name' => 'Admin EduMarket',
                'email' => 'admin@edumarket.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );

        $instructors = [
            ['email' => 'instructor1@edumarket.com', 'name' => 'Nguyễn Minh Quân'],
            ['email' => 'instructor2@edumarket.com', 'name' => 'Trần Thị Hạnh'],
            ['email' => 'instructor3@edumarket.com', 'name' => 'Lê Hoàng Nam'],
        ];

        foreach ($instructors as $instructor) {
            User::query()->firstOrCreate(
                ['email' => $instructor['email']],
                [
                    'role_id' => $roleIds->get('instructor'),
                    'name' => $instructor['name'],
                    'email' => $instructor['email'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );
        }

        $students = [
            ['email' => 'student1@edumarket.com', 'name' => 'Phạm Đức An'],
            ['email' => 'student2@edumarket.com', 'name' => 'Võ Minh Khoa'],
            ['email' => 'student3@edumarket.com', 'name' => 'Đặng Thu Thảo'],
            ['email' => 'student4@edumarket.com', 'name' => 'Ngô Nhật Minh'],
            ['email' => 'student5@edumarket.com', 'name' => 'Huỳnh Bảo Ngọc'],
            ['email' => 'student6@edumarket.com', 'name' => 'Lý Quang Huy'],
            ['email' => 'student7@edumarket.com', 'name' => 'Mai Hương Ly'],
            ['email' => 'student8@edumarket.com', 'name' => 'Tạ Thanh Tùng'],
            ['email' => 'student9@edumarket.com', 'name' => 'Bùi Ngọc Anh'],
            ['email' => 'student10@edumarket.com', 'name' => 'Đỗ Minh Trí'],
        ];

        foreach ($students as $student) {
            User::query()->firstOrCreate(
                ['email' => $student['email']],
                [
                    'role_id' => $roleIds->get('student'),
                    'name' => $student['name'],
                    'email' => $student['email'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );
        }
    }
}
