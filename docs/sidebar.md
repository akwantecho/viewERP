# Sidebar Navigation Reference

## Overview
The application now renders a responsive sidebar (`resources/views/layouts/partials/sidebar.blade.php`) that reads its structure from `config/navigation.php`. The layout automatically loads the sidebar for authenticated users and collapses it into an off‑canvas drawer on small screens.

## Editing Navigation Items
1. Update `config/navigation.php` to add, remove, or reorder menu entries.
2. Each item uses the following keys:
   - `label`: Human readable text (wrapped in `__()` inside the view).
   - `route`: Named route. Links are skipped if the route does not exist.
   - `icon`: Key from the icon registry (see below).
   - `perm` (optional): Permission string checked via `auth()->user()->hasPermission()`.
   - `super_only` (optional): Boolean flag that restricts the item to super admins.
   - `children` (optional): Array of nested items beneath a `heading` entry. Entire groups are hidden if every child is filtered out.
3. After editing the config file, clear the config cache if it’s enabled (`php artisan config:clear`).

## Icons
Icons are stored centrally in `App\Support\IconRegistry`. Add new entries by extending the `$icons` array with a unique key and the desired SVG paths. Use the key inside `config/navigation.php`. The sidebar automatically renders the SVG via `IconRegistry::svg($key)`.

## Permission Checks
- Each item honours the `perm` value. When provided, links only render for users whose `hasPermission()` method returns `true` for that permission.
- Items marked `super_only` are limited to users with the `is_super` flag.
- Blade helpers `@canPerm` / `@canAny` continue to work elsewhere; the sidebar does not introduce new authorization mechanisms.

## RTL / LTR Support
The sidebar automatically mirrors padding, borders, and layout direction based on `app()->getLocale()`. To support a new RTL locale, set the application locale before rendering the layout and ensure translations exist for the labels.

## Responsive Behaviour
- `lg` and wider: sidebar stays fixed at 18rem width.
- `< lg`: sidebar becomes an off‑canvas drawer toggled by the hamburger button (`sidebarOpen` Alpine state).
- The overlay closes the drawer when clicked or when the close button is pressed.

## Adding New Links Example
```php
// config/navigation.php
return [
    // ...
    [
        'heading' => 'Settings',
        'children' => [
            [
                'label' => 'Branding',
                'route' => 'settings.branding',
                'icon'  => 'palette',
                'perm'  => 'settings.view',
            ],
        ],
    ],
];
```
Add the matching icon to `IconRegistry` if the key does not already exist.

## Tests
`tests/Feature/SidebarVisibilityTest.php` ensures that users lacking the `users.view` permission never see the “Users” navigation link, guarding against regressions when adjusting visibility logic.
