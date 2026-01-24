<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Unit;
use App\Models\Floor;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ProjectDeletionService;


class ProjectController extends Controller
{
    public function __construct(
        private ProjectDeletionService $projectDeletion,
    ) {
        // Allow only super admin to access edit/update/saveStructure
        $this->middleware('super')->only(['create', 'store', 'edit', 'update', 'saveStructure', 'structureForm']);
    }
    public function index()
    {
        $projects = Project::withCount([
            'floors' => function ($query) {
                $query->has('units');
            },
            'units',
            'units as reserved_units_count' => function ($query) {
                $query->whereIn('status', ['reserved', 'sold']);
            },
            'units as available_units_count' => function ($query) {
                $query->where('status', 'available');
            },
        ])->get();

        return view('projects.index', compact('projects'));
    }



    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        // Normalize code to uppercase before validation to enforce case-insensitive uniqueness
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:projects,code',
            'notes' => 'nullable|string',
            'floors' => 'nullable|array',
            'floors.*.name' => 'required|string',
            'floors.*.units' => 'nullable|array',
            'floors.*.units.*.name' => 'nullable|string',
        ], [
            'code.unique' => 'Project code already exists. Please use a unique code.',
        ]);

        // Business rule: a project must contain at least one unit
        $unitsTotal = 0;
        foreach (($validated['floors'] ?? []) as $floorData) {
            $units = $floorData['units'] ?? [];
            foreach ($units as $u) {
                if (trim((string) ($u['name'] ?? '')) !== '') {
                    $unitsTotal++;
                }
            }
        }
        if ($unitsTotal === 0) {
            return back()
                ->withErrors(['units' => 'At least one unit is required to create a project.'])
                ->withInput();
        }

        // Count only floors that have units
        $floorsWithUnits = 0;
        foreach (($validated['floors'] ?? []) as $floorData) {
            $units = $floorData['units'] ?? [];
            if (!empty($units) && count($units) > 0) {
                $floorsWithUnits++;
            }
        }

        $project = Project::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'notes' => $validated['notes'] ?? null,
            'floors_count' => $floorsWithUnits,
        ]);

        if (!empty($validated['floors'])) {
            foreach ($validated['floors'] as $floorData) {
                // Skip floors with no units
                $units = $floorData['units'] ?? [];
                if (empty($units) || count($units) === 0) {
                    continue;
                }

                $floor = $project->floors()->create([
                    'name' => $floorData['name'],
                ]);

                foreach ($units as $i => $unitData) {
                    $floor->units()->create([
                        'unit_code' => $this->generateUniqueUnitCode($project->code, $floor->name),
                        'status' => 'available',
                    ]);
                }
            }
        }

        return redirect()->route('projects.show', $project->id)
                         ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        // Count only floors that have units
        $project->loadCount(['floors' => function ($query) {
            $query->has('units');
        }]);

        // Paginate units for this project; keep query string for per_page
        $perPage = (int) request('per_page', 20);
        if ($perPage <= 0) { $perPage = 20; }

        // Get all units with floors to sort them properly
        $allUnits = $project->units()->with('floor')->get();

        // Custom sort: L -> G -> 1 -> 2 -> 3 -> etc., then by unit_code
        $sortedUnits = $allUnits->sort(function($a, $b) {
            $floorA = strtoupper($a->floor->name ?? '');
            $floorB = strtoupper($b->floor->name ?? '');

            // Custom priority for floors
            $getPriority = function($floorName) {
                if ($floorName === 'L') return ['0', 0];
                if ($floorName === 'G') return ['1', 0];
                if (is_numeric($floorName)) return ['2', (int)$floorName];
                return ['3', $floorName];
            };

            [$priorityA, $valueA] = $getPriority($floorA);
            [$priorityB, $valueB] = $getPriority($floorB);

            // Compare priorities first
            if ($priorityA !== $priorityB) {
                return strcmp($priorityA, $priorityB);
            }

            // If same priority, compare values
            if ($valueA !== $valueB) {
                return $valueA <=> $valueB;
            }

            // If same floor, sort by unit_code
            return strcmp($a->unit_code, $b->unit_code);
        })->values();

        // Manually paginate the sorted collection
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
        $units = new \Illuminate\Pagination\LengthAwarePaginator(
            $sortedUnits->forPage($currentPage, $perPage),
            $sortedUnits->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );

        return view('projects.show', compact('project', 'units'));
    }

    public function edit(Project $project)
    {
        $project->load(['floors.units']);
        $paymentCount = Payment::whereIn(
            'booking_id',
            Booking::where('project_id', $project->id)->select('id')
        )->count();

        return view('projects.edit', compact('project', 'paymentCount'));
    }

    public function update(Request $request, Project $project)
    {
        // Normalize code to uppercase before validation for consistent uniqueness
        if ($request->has('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:projects,code,' . $project->id,
            'notes' => 'nullable|string',
            'floors' => 'sometimes|array',
            'floors.*.id' => 'nullable|integer',
            'floors.*.name' => 'required_with:floors|string|max:255',
            'floors.*.delete' => 'nullable|boolean',
            'floors.*.units' => 'sometimes|array',
            'floors.*.units.*.id' => 'nullable|integer',
            'floors.*.units.*.unit_code' => 'nullable|string|max:100',
            'floors.*.units.*.delete' => 'nullable|boolean',
        ], [
            'code.unique' => 'Project code already exists. Please use a unique code.',
        ]);

        $errors = [];
        $success = [];

        \DB::transaction(function () use ($project, $validated, &$errors) {
            // Update project basic info
            $project->update([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'notes' => $validated['notes'] ?? null,
            ]);

            $project->load(['floors.units']);
            $existingFloors = $project->floors->keyBy('id');

            foreach (($validated['floors'] ?? []) as $fIdx => $floorData) {
                $floorId = $floorData['id'] ?? null;
                $deleteFloor = (bool) ($floorData['delete'] ?? false);
                $floorName = $floorData['name'] ?? null;

                if ($floorId) {
                    // Existing floor
                    $floor = $existingFloors->get((int) $floorId);
                    if (!$floor) { continue; }

                    if ($deleteFloor) {
                        // Do not allow deleting floor that has reserved/sold units
                        $hasLockedUnits = $floor->units()->whereIn('status', ['reserved','sold'])->exists();
                        if ($hasLockedUnits) {
                            $errors[] = "Cannot delete floor '{$floor->name}' with reserved/sold units.";
                        } else {
                            // Delete all available units, then floor
                            $deletedUnits = $floor->units()->where('status', 'available')->count();
                            $floor->units()->where('status', 'available')->delete();
                            $fname = $floor->name;
                            $floor->delete();
                            $success[] = "Deleted floor '{$fname}'" . ($deletedUnits ? " and {$deletedUnits} available units" : '') . '.';
                        }
                        continue;
                    }

                    // Update floor name
                    if ($floorName && $floorName !== $floor->name) {
                        $floor->update(['name' => $floorName]);
                    }

                    // Handle units for existing floor
                    $existingUnits = $floor->units->keyBy('id');
                    foreach (($floorData['units'] ?? []) as $uIdx => $unitData) {
                        $unitId = $unitData['id'] ?? null;
                        $deleteUnit = (bool) ($unitData['delete'] ?? false);
                        $newCode = trim($unitData['unit_code'] ?? '');

                        if ($unitId) {
                            $unit = $existingUnits->get((int) $unitId);
                            if (!$unit) { continue; }

                            if ($deleteUnit) {
                                if ($unit->status === 'available') {
                                    $uc = $unit->unit_code;
                                    $unit->delete();
                                    $success[] = "Deleted unit {$uc}.";
                                } else {
                                    $errors[] = "Cannot delete unit {$unit->unit_code} (status: {$unit->status}).";
                                }
                                continue;
                            }

                            // Update unit code if available
                            if ($newCode !== '' && $newCode !== $unit->unit_code) {
                                if ($unit->status !== 'available') {
                                    $errors[] = "Cannot edit unit {$unit->unit_code} (status: {$unit->status}).";
                                } elseif (\App\Models\Unit::where('unit_code', $newCode)->where('id', '!=', $unit->id)->exists()) {
                                    $errors[] = "Unit code '{$newCode}' already exists.";
                                } else {
                                    $unit->update(['unit_code' => $newCode]);
                                }
                            }
                        } else {
                            // New unit row
                            $auto = ($newCode === '' || strtoupper($newCode) === 'AUTO');
                            if ($auto) {
                                $newCode = $this->generateUniqueUnitCode($project->code, $floor->name);
                            }
                            if ($newCode !== '') {
                                if (\App\Models\Unit::where('unit_code', $newCode)->exists()) {
                                    $errors[] = "Unit code '{$newCode}' already exists.";
                                } else {
                                    $floor->units()->create([
                                        'unit_code' => $newCode,
                                        'status' => 'available',
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    // New floor
                    if (!$floorName) { continue; }
                    $newFloor = $project->floors()->create(['name' => $floorName]);

                    foreach (($floorData['units'] ?? []) as $unitData) {
                        $newCode = trim($unitData['unit_code'] ?? '');
                        $auto = ($newCode === '' || strtoupper($newCode) === 'AUTO');
                        if ($auto) {
                            $newCode = $this->generateUniqueUnitCode($project->code, $newFloor->name);
                        }
                        if ($newCode === '') { continue; }
                        if (\App\Models\Unit::where('unit_code', $newCode)->exists()) {
                            $errors[] = "Unit code '{$newCode}' already exists.";
                            continue;
                        }
                        $newFloor->units()->create([
                            'unit_code' => $newCode,
                            'status' => 'available',
                        ]);
                    }
                }
            }
        });

        // log activity (always record an update attempt by superadmin)
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                \App\Models\Activity::create([
                    'user_id' => optional(request()->user())->id,
                    'action' => 'project.update',
                    'entity_type' => \App\Models\Project::class,
                    'entity_id' => $project->id,
                    'meta' => ['errors' => count($errors)],
                    'ip' => request()->ip(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while logging project.update activity', [
                'location'   => __METHOD__,
                'class'      => static::class,
                'project_id' => $project->id ?? null,
                'message'    => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            report($e);
        }

        if (!empty($errors)) {
            $redir = redirect()->route('projects.edit', $project->id)->withErrors($errors)->with('warning', 'Project updated with some notices.');
            if (!empty($success)) { $redir->with('success', implode("\n", $success)); }
            return $redir;
        }

        if (!empty($success)) {
            return redirect()->route('projects.edit', $project->id)->with('success', implode("\n", $success));
        }

        return redirect()->route('projects.edit', $project->id)->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        try {
            $summary = $this->projectDeletion->delete($project);
        } catch (\Throwable $e) {
            Log::error('Failed to delete project', [
                'location'   => __METHOD__,
                'class'      => static::class,
                'project_id' => $project->id ?? null,
                'user_id'    => optional(request()->user())->id,
                'message'    => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('projects.edit', $project->id)
                ->with('error', __('projects.delete.error'));
        }

        $message = __('projects.delete.success', [
            'name' => $project->name,
            'payments' => $summary['payments_deleted'] ?? 0,
        ]);

        return redirect()->route('projects.index')->with('success', $message);
    }

   public function saveStructure(Request $request, Project $project)
{
    $validated = $request->validate([
        'floors' => 'required|array',
        'floors.*.name' => 'required|string',
        'floors.*.units' => 'required|array',
        'floors.*.units.*.name' => 'required|string',
    ]);

    foreach ($validated['floors'] as $floorData) {
        $floor = $project->floors()->create(['name' => $floorData['name']]);

        foreach ($floorData['units'] as $unitData) {
            $unitCode = ($unitData['name'] === 'AUTO')
                ? $this->generateUniqueUnitCode($project->code, $floor->name)
                : $unitData['name'];

            $floor->units()->create([
                'unit_code' => $unitCode,
                'status'    => 'available',
            ]);
        }
    }

    return redirect()->route('projects.show', $project->id)
                     ->with('success', 'Structure saved successfully.');
}

    private function generateUniqueUnitCode($projectCode, $floorName)
    {
        $index = 1;

        do {
            $code = strtoupper($projectCode) . '-' . strtoupper($floorName) . '-' . str_pad($index, 2, '0', STR_PAD_LEFT);
            $exists = Unit::where('unit_code', $code)->exists();
            $index++;
        } while ($exists);

        return $code;
    }


    public function statement(Project $project)
    {
        // Eager-load both installments and payments to avoid N+1 and compute totals accurately
        $units = $project->units()->with(['floor', 'booking.installments', 'booking.payments'])->get();

        return view('reports.statement', compact('project', 'units'));
    }
}
