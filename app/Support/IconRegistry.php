<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

class IconRegistry
{
    protected static array $icons = [
        'home' => '<path d="M3 9.5 12 3l9 6.5v10a1.5 1.5 0 0 1-1.5 1.5h-5.5v-6h-4v6H4.5A1.5 1.5 0 0 1 3 19.5z" stroke-width="1.6"/>',
        'building' => '<path d="M4 21V5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5V21M4 21h16M4 21H2m18 0h2M8 21v-4m4 4v-4m4 4v-4m-8-4h.01m3.99 0h.01M12 9h.01m-3.99 0h.01m7.98 0h.01" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m22 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 1 1 0 7.75M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'layers' => '<path d="m3 7 9-5 9 5-9 5-9-5zm0 5 9 5 9-5m-9 5v6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'bell' => '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9m6 13a2.5 2.5 0 0 1-2.45-2h4.9A2.5 2.5 0 0 1 12 21z" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'receipt' => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2zM9 7h6M9 12h6" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'credit-card' => '<path d="M3 7h18v10H3zM3 11h18" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'shield' => '<path d="M12 3 4.5 5v6c0 5.25 3.75 9.75 7.5 10 3.75-.25 7.5-4.75 7.5-10V5z" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'shield-check' => '<path d="M12 3 4.5 5v6c0 5.25 3.75 9.75 7.5 10 3.75-.25 7.5-4.75 7.5-10V5z" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'user' => '<path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.5 20.25a7.5 7.5 0 0 1 15 0" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'settings' => '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
        'logout' => '<path d="M15.75 6.75V4.5A2.25 2.25 0 0 0 13.5 2.25h-6A2.25 2.25 0 0 0 5.25 4.5v15A2.25 2.25 0 0 0 7.5 21.75h6a2.25 2.25 0 0 0 2.25-2.25V17.25" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.75 12H21m0 0-3-3m3 3-3 3" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'palette' => '<path d="M12 3a9 9 0 1 0 9 9c0-1.1-.9-2-2-2h-1.5a2 2 0 0 1-1.9-2.63A3 3 0 0 0 12 3zM7.5 16h.01M6 10h.01M9 6h.01m6-.5h.01M17 10h.01" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/><path d="M3 8.5c0 1.66 4.03 3 9 3s9-1.34 9-3"/>',
        'x' => '<path d="M6 6l12 12M6 18L18 6" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'default' => '<circle cx="12" cy="12" r="9"/>',
    ];

    public static function svg(string $name, string $classes = 'w-5 h-5'): HtmlString
    {
        $path = self::$icons[$name] ?? self::$icons['default'];
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="%s">%s</svg>',
            e($classes),
            $path
        );

        return new HtmlString($svg);
    }
}
