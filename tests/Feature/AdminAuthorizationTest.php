<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'roles.manage', 'guard_name' => 'web']);
    }

    public function test_non_admin_cannot_access_authorization_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.authorization.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_role(): void
    {
        $admin = User::factory()->create(['is_super' => true]);
        $permission = Permission::create(['name' => 'projects.view', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->post(route('admin.authorization.roles.store'), [
                'name' => 'quality',
                'permissions' => [$permission->id],
            ])
            ->assertRedirect();

        $roleId = Role::query()->where('name', 'quality')->value('id');
        $this->assertNotNull($roleId);
        $this->assertDatabaseHas('permission_role', [
            'role_id' => $roleId,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_super_admin_can_assign_roles_to_user_via_user_controller(): void
    {
        $admin = User::factory()->create(['is_super' => true]);
        $user = User::factory()->create();

        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'projects.view', 'guard_name' => 'web']);
        $role->syncPermissions([$permission->name]);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [$role->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('role_user', [
            'role_id' => $role->id,
            'model_id' => $user->id,
            'model_type' => User::class,
        ]);

        $this->assertTrue($user->fresh()->hasRole('manager', 'web'));
        $this->assertTrue($user->fresh()->hasPermissionTo('projects.view', 'web'));
    }
}
