<?php

namespace App\Http\Controllers\Admin\Authorization;

use App\Http\Controllers\Admin\Authorization\Concerns\EnsuresAdminPresence;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    use EnsuresAdminPresence;

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $permissionNames = Permission::whereIn('id', $data['permissions'] ?? [])->pluck('name')->all();
        $role->syncPermissions($permissionNames);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('Role created successfully.'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin' && $request->input('name') !== $role->name) {
            throw ValidationException::withMessages([
                'name' => __('The super admin role name cannot be changed.'),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:roles,name,' . $role->id],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionNames = Permission::whereIn('id', $data['permissions'] ?? [])->pluck('name')->all();
        $roleLosesAdmin = $role->permissions->contains('name', $this->adminPermissionName())
            && ! in_array($this->adminPermissionName(), $permissionNames, true);

        if ($roleLosesAdmin) {
            $userIds = $role->users()->pluck('id')->all();
            if (! $this->hasAdminOutside($userIds)) {
                throw ValidationException::withMessages([
                    'permissions' => __('Cannot remove the admin permission because no other admin would remain.'),
                ]);
            }
        }

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($permissionNames);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('Role updated.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin') {
            throw ValidationException::withMessages([
                'roles' => __('The super admin role cannot be deleted.'),
            ]);
        }

        if ($role->permissions->contains('name', $this->adminPermissionName())) {
            $userIds = Role::find($role->id)?->users()->pluck('id')->all() ?? [];
            if (! $this->hasAdminOutside($userIds)) {
                throw ValidationException::withMessages([
                    'roles' => __('Cannot remove the last admin role. Assign admin privileges to another role or user first.'),
                ]);
            }
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', __('Role deleted.'));
    }
}
