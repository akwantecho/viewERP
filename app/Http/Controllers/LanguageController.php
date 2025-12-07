<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LanguageController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $locales = config('app.supported_locales', [config('app.locale')]);

        $data = $request->validate([
            'locale' => ['required', Rule::in($locales)],
        ]);

        $locale = $data['locale'];

        session()->put('locale', $locale);

        $user = $request->user();
        if ($user && Schema::hasColumn($user->getTable(), 'locale')) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
