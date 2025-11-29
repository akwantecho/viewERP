<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SidebarVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_link_hidden_when_permission_missing(): void
    {
        $role = Role::create(['name' => 'agent', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'name' => 'Limited Agent',
            'email' => 'agent@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole($role);

        Permission::create(['name' => 'users.view', 'guard_name' => 'web']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('href="' . e(route('users.index')) . '"', false);
    }
}
