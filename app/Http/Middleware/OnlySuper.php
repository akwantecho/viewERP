<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OnlySuper
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->is_super) {
            abort(403, 'Unauthorized');
        }
        return $next($request);
    }
}

