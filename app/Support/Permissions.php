<?php

namespace App\Support;

class Permissions
{
    public static function groups(): array
    {
        return config('permissions', []);
    }

    public static function keys(): array
    {
        $keys = [];

        foreach (self::groups() as $group => $actions) {
            foreach ($actions as $action) {
                $keys[] = $group . '.' . $action;
            }
        }

        return $keys;
    }
}
