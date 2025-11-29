<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Project;
use Illuminate\Http\Request;

class FloorController extends Controller
{
   public function create(Project $project)
{
    return view('floors.create', compact('project'));
}

public function store(Request $request, Project $project)
{
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $project->floors()->create([
        'name' => $request->name,
    ]);

    return redirect()->route('projects.show', $project->id)->with('success', 'Floor added.');
}

}