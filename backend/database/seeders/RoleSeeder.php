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
            ['name' => 'customer', 'display_name' => 'Customer'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['name' => $role['name']], $role);
        }

        Role::query()
            ->whereNotIn('name', ['admin', 'customer'])
            ->whereDoesntHave('users')
            ->delete();
    }
}
