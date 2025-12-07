<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = config('app.supported_locales', [config('app.locale')]);
        $locale = $this->resolveLocale($request, $supported);

        app()->setLocale($locale);
        session()->put('locale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request, array $supported): string
    {
        $candidates = [
            optional($request->user())->getAttribute('locale'),
            session('locale'),
            config('app.locale'),
        ];

        foreach ($candidates as $candidate) {
            if ($this->isValidLocale($candidate, $supported)) {
                return $candidate;
            }
        }

        return config('app.locale');
    }

    private function isValidLocale(?string $locale, array $supported): bool
    {
        return is_string($locale) && in_array($locale, $supported, true);
    }
}
