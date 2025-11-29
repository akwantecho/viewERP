<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Support\Facades\Schema;

class ActivityController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('activities')) {
            $activities = collect();
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator($activities, 0, 20);
            return view('profile.activities', compact('paginator'))
                ->with('warning', 'Activities table not found. Please run migrations.');
        }

        $query = Activity::with('user')->latest();
        $paginator = $query->paginate(20);

        return view('profile.activities', compact('paginator'));
    }
}

