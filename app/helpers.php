<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

if (! function_exists('localized_url')) {
    function localized_url($locale)
    {
        $route = request()->route();
        $name = optional($route)->getName();
        $parameters = $route ? $route->parameters() : [];
        $parameters['locale'] = $locale;

        return route($name, $parameters);
    }
}
