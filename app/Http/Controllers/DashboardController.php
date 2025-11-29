<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Customer;
use App\Models\Project;

class DashboardController extends Controller
{
    public function index()
    {
        $soldUnits = Unit::where('status', 'sold')->count();
        $availableUnits = Unit::where('status', 'available')->count();
        $customersCount = Customer::count();
        $projectsCount = Project::count();
    

        return view('dashboard', compact('soldUnits', 'availableUnits', 'customersCount', 'projectsCount'));
    }
}


