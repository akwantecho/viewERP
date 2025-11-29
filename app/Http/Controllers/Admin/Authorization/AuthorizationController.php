<?php

namespace App\Http\Controllers\Admin\Authorization;

use App\Http\Controllers\Controller;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthorizationController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::with('permissions:id,name')->orderBy('name')->get();
        $selectedRole = null;

        if ($request->filled('role')) {
            $selectedRole = Role::with('permissions:id,name')->find($request->input('role'));
        }

        if ($request->get('mode') === 'create') {
            $selectedRole = null;
        }

        $permissionIdsByKey = Permission::pluck('id', 'name');

        return view('admin.authorization.index', [
            'roles' => $roles,
            'permissionGroups' => Permissions::groups(),
            'permissionIdsByKey' => $permissionIdsByKey,
            'selectedRole' => $selectedRole,
        ]);
    }

    public function syncRegistry(): RedirectResponse
    {
        Artisan::call('permissions:sync');
        Artisan::call('permission:cache-reset');

        return back()->with('success', __('Permissions registry synced.'));
    }
}
