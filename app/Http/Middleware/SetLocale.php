<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        $locale = $request->route('locale')
            ?? optional($request->user())->preferred_locale
            ?? $request->getPreferredLanguage(['ar','en'])
            ?? config('app.locale');

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        if ($route = $request->route()) {
            // Remove the locale placeholder so controller method signatures
            // don't receive it as the first argument (keeps implicit bindings intact).
            $route->forgetParameter('locale');
        }

        return $next($request);
    }
}
