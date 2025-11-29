<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BackupSetting;
use Illuminate\Database\Seeder;

class BackupSettingSeeder extends Seeder
{
    public function run(): void
    {
        BackupSetting::query()->firstOrCreate([], BackupSetting::defaults());
    }
}
