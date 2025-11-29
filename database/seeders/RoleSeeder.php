<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Support\Permissions;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'super_admin' => Permissions::keys(),
            'manager' => [
                'users.view',
                'projects.view',
                'projects.create',
                'projects.update',
                'bookings.view',
                'bookings.create',
                'bookings.delete',
                'payments.view',
                'payments.create',
                'payments.update',
                'documents.view',
                'documents.upload',
                'reports.totalStatement.view',
            ],
            'staff' => [
                'projects.view',
                'bookings.view',
                'payments.view',
                'documents.view',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }
    }
}
