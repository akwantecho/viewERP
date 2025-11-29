<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء دور super_admin إذا لم يكن موجودًا
        $adminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        // إنشاء السوبر أدمن
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'], // عدّل الإيميل
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password123'), // عدّل الباسورد
                'is_super' => true,
            ]
        );

        $superAdmin->syncRoles([$adminRole->name]);
    }
}
