<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected string $guard_name = 'web';

    // الحقول القابلة للإدخال
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super',
    ];

    // الحقول التي يجب إخفاؤها عند التحويل إلى JSON
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function hasPermission(string $perm): bool
    {
        if ($this->is_super || $this->hasRole('super_admin')) {
            return true;
        }

        try {
            return $this->hasPermissionTo($perm);
        } catch (PermissionDoesNotExist $e) {
            return false;
        }
    }

}
