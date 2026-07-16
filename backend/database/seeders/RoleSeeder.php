<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrator'],
            ['name' => 'instructor', 'display_name' => 'Instructor'],
            ['name' => 'student', 'display_name' => 'Student'],
        ];

        foreach ($roles as $role) {
            Role::query()->firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
