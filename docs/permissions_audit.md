# Authorization Audit

## Approach Overview
- **Implementation type:** `spatie/laravel-permission` with the standard middleware (`permission:...`) and model traits registered on `app/Models/User`.
- **Data model:**
  - `roles` / `permissions` tables managed by Spatie models.
  - Pivot tables `role_user` (model assignments) and `permission_role` (role permissions). Direct `permission_user` assignments have been removed; users inherit access exclusively through roles.
  - Optional `is_super` boolean still exists for emergency access and is checked inside `AuthServiceProvider` + `User::hasPermission()`.
- **Permission registry:** Centralized at `config/permissions.php` and surfaced via `App\Support\Permissions`. Seeders and the `permissions:sync` artisan command ensure database rows mirror this config.

## Enforcement Points
- **Middleware aliases** (registered in `bootstrap/app.php`):
  - `permission` → `Spatie\Permission\Middleware\PermissionMiddleware`.
  - `super` → `App\Http\Middleware\OnlySuper`.
- **Controllers using middleware:** Only `UserController` wires permission middleware in its constructor. `PermissionController` wraps every action with `super`. Other controllers (Bookings, Projects, Payments, etc.) rely solely on `auth` middleware; only a handful of routes (e.g. payment deletion, booking deletion, permissions CRUD) add authorization.
- **Blade helpers:** Views now rely on Laravel's native `@can` / `@canany` directives and the `Gate::before` hook handles `is_super` shortcuts.
- **Manual checks:** No use of Laravel policies/gates. Controllers rarely call `$this->authorize(...)`; most business logic assumes that route middleware already restricted access.

## Gaps & Inconsistencies
1. **Routes without backend guards:** Large areas (projects, bookings CRUD aside from delete, documents, reports, installments, etc.) are reachable by any authenticated user because no `permission:*` middleware is applied. UI buttons might hide actions, but API/HTTP calls remain unprotected.
2. **Mixed concerns:** Some checks live in controllers (`UserController::__construct`), others on individual routes (`routes/web.php`), and others exclusively in Blade. This inconsistency makes it easy to forget enforcing permissions on new endpoints.
3. **No policy/gate layer:** There is no `AuthServiceProvider` or `app/Policies` directory. As a result, model-specific rules (e.g., “user can only edit bookings within their project”) cannot be expressed centrally.
4. **`is_super` shortcuts:** Super users bypass every middleware, but views still run additional permission checks. Any bug that forgets to guard `is_super` (e.g., mass-assignable `is_super`) would grant full access.
5. **Permission caching:** `User::hasPermission()` builds an in-memory cache per request but never flushes if `permissions()` is re-synced mid-request. That is acceptable but should be documented.
6. *(Addressed)* Permissions are seeded deterministically from `config/permissions.php`, so typos now surface during seeding/CI instead of silently creating orphan rows.

## Recommendations (Non-breaking)
1. **Document required middleware usage:** Create a checklist (or even route macro) ensuring every write action adds an explicit permission middleware. Short-term, add comments or TODOs in `routes/web.php` identifying unprotected routes.
2. **Introduce centralized gates/policies:** Even without third-party packages, registering policies in a reinstated `AuthServiceProvider` would allow `$this->authorize` checks directly inside controllers while keeping middleware as a first line of defense.
3. **Clarify super-admin creation:** Move `is_super` assignment to guarded logic (e.g., seeder or dedicated admin UI) and hide the flag from mass-assignment. Also document that `is_super` bypasses everything so reviewers know to audit that code path carefully.
4. **Route/UI parity audit:** For each major module (bookings, payments, documents, reports), list the required permission(s) and ensure both the routes and the UI reference the same constant or helper rather than string literals sprinkled throughout the codebase.
7. **Route/UI parity audit:** For each major module (bookings, payments, documents, reports), list the required permission(s) and ensure both the routes and the UI reference the same constant or helper rather than string literals sprinkled throughout the codebase.

## Files & References
- Models: `app/Models/User.php`, `Role.php`, `Permission.php`.
- Middleware: `app/Http/Middleware/{CheckPermission,PermissionAny,PermissionAll,OnlySuper}.php`.
- Blade helpers: `app/Providers/AppServiceProvider.php` (storage + shared services; authorization now uses native `@can`).
- Registry: `config/permissions.php` + `app/Support/Permissions.php`.
- Routes: `routes/web.php` (only a subset uses permission middleware).
- Seeders: `database/seeders/SuperAdminSeeder.php` (provisions super admin role/user).

This document reflects the current state without altering any behavior.
