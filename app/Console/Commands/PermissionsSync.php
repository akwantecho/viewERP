<?php

namespace App\Console\Commands;

use App\Support\Permissions;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

class PermissionsSync extends Command
{
    protected $signature = 'permissions:sync {--guard=web : Guard name to associate with the permissions}';

    protected $description = 'Ensure permissions defined in config/permissions.php exist in the database';

    public function handle(PermissionRegistrar $registrar): int
    {
        $guard = $this->option('guard') ?: 'web';
        $created = 0;

        foreach (Permissions::keys() as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
            if ($permission->wasRecentlyCreated) {
                $created++;
                $this->line("Created permission {$name} ({$guard})");
            }
        }

        $registrar->forgetCachedPermissions();
        $this->info("Sync complete. {$created} permissions created.");

        return self::SUCCESS;
    }
}
