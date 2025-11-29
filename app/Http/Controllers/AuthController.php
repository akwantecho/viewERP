<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            // log activity
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                    \App\Models\Activity::create([
                        'user_id' => optional(Auth::user())->id,
                        'action' => 'auth.login',
                        'entity_type' => null,
                        'entity_id' => null,
                        'meta' => ['email' => $request->input('email')],
                        'ip' => $request->ip(),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while logging auth.login activity', [
                    'location' => __METHOD__,
                    'class'    => static::class,
                    'user_id'  => optional(Auth::user())->id,
                    'message'  => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);

                report($e);
            }
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'المعلومات غير صحيحة.',
        ]);
    }

    public function logout(Request $request)
    {
        // record before logout
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                \App\Models\Activity::create([
                    'user_id' => optional(Auth::user())->id,
                    'action' => 'auth.logout',
                    'meta' => [],
                    'ip' => $request->ip(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while logging auth.logout activity', [
                'location' => __METHOD__,
                'class'    => static::class,
                'user_id'  => optional(Auth::user())->id,
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            report($e);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
