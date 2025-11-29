<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Floor;
use App\Models\Unit;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::create([
            'name' => 'Palm Residences',
            'code' => 'PRJ001',
        ]);

        $floors = ['L', 'G', '1', '2', '3'];

        foreach ($floors as $index => $name) {
            $floor = Floor::create([
                'project_id' => $project->id,
                'name' => $name,
            ]);

            for ($i = 1; $i <= 4; $i++) {
                Unit::create([
                    'floor_id' => $floor->id,
                    'unit_code' => "{$project->code}-{$name}-{$i}",
                    'status' => 'available',
                    'base_price' => 20000,
                ]);
            }
        }
    }
}
