<?php

if (! function_exists('supported_locales')) {
    function supported_locales(): array
    {
        return config('app.supported_locales', [config('app.locale')]);
    }
}
