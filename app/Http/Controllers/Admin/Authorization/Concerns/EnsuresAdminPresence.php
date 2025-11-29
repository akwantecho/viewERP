<?php

namespace App\Http\Controllers\Admin\Authorization\Concerns;

use App\Models\User;

trait EnsuresAdminPresence
{
    protected function adminPermissionName(): string
    {
        return 'roles.manage';
    }

    protected function hasAdminOutside(array $excludedUserIds = []): bool
    {
        if (User::where('is_super', true)->exists()) {
            return true;
        }

        $query = User::query();
        if (! empty($excludedUserIds)) {
            $query->whereNotIn('id', $excludedUserIds);
        }

        return $query->get()->contains(function (User $user) {
            return $user->hasPermission($this->adminPermissionName());
        });
    }
}
