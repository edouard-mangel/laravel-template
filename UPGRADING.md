# Upgrading Guide

This document tracks breaking changes and migration steps between major template versions.

---

## Template Versioning Policy

The template itself is not versioned with SemVer. Instead, each project that scaffolds from this template
should pin the versions of its dependencies in `composer.json` and `package.json`. When you upgrade a
dependency in your project, consult this file for any template-level changes that should accompany it.

---

## PHP 8.3 → 8.4

**Required upgrade** — The resolved Laravel 12 package set (as of 2026) requires PHP 8.4+. Projects
scaffolded from this template must run PHP 8.4 or later. `composer.json` declares `"php": "^8.4"`.

**Readonly properties promotion** — PHP 8.4 adds property hooks. Value objects using readonly classes
are unaffected, but you may wish to replace custom `__get` magic with property hooks.

No breaking changes to the template architecture.

---

## Laravel 11 → 12

**Skeleton changes** — Laravel 12 restructures the default application skeleton. Key changes if upgrading
an existing project (not using this template):

1. `app/Http/Kernel.php` is removed — middleware is registered in `bootstrap/app.php`
2. `routes/web.php` and `routes/api.php` are now registered in `bootstrap/app.php`
3. The `providers` array in `config/app.php` is removed — use service provider auto-discovery

This template targets Laravel 12 from the start. No migration needed for new projects.

---

## Pest PHP 2 → 3

**Dataset syntax changed** — `it()->with()` is now `dataset()`. Update test files accordingly.

**Arch testing API** — `arch()` assertions have a new fluent API in Pest 3. See
[Testing.md](documentation/Testing.md) for the current patterns.

---

## Scaffolding Lessons (discovered during Template/ build)

These corrections are already applied in `Template/`. Document them here so future aggregates
get them right from the start.

### 1. `readonly class` can only extend `readonly class`

PHP 8.2+ enforces that a `readonly` class can only extend another `readonly` class.
`ValueObject` must be declared `abstract readonly`:

```php
// WRONG
abstract class ValueObject {}

// CORRECT
abstract readonly class ValueObject {}
```

### 2. Finders need an interface in the Application layer

`GetProductByIdQuery` and `GetProductsQuery` must not import `ProductFinder` directly from
Infrastructure — that breaks the dependency rule. The fix is a finder interface in Application:

```
app/Application/Contracts/Finders/ProductFinderInterface.php   ← declare here
app/Infrastructure/Persistence/Product/ProductFinder.php       ← implement here
app/Infrastructure/Providers/InfrastructureServiceProvider.php ← bind here
```

### 3. `newFactory()` override required for non-standard model paths

Eloquent's `HasFactory` trait resolves factory names from model class paths. Models in
`App\Infrastructure\Persistence\` will not be found automatically. Always override:

```php
protected static function newFactory(): ProductFactory
{
    return ProductFactory::new();
}
```

### 4. Critical config files required at boot

These must exist or Laravel will error on first request:

| File | Why critical |
|------|-------------|
| `config/session.php` | Sanctum stateful auth reads from this |
| `config/cors.php` | Cross-origin from Angular (`:4200 → :8000`) |
| `config/logging.php` | Log channel resolution fails without it |
| `config/sanctum.php` | Token config and stateful domains |

### 5. `DatabaseSeeder.php` is the entry point

`php artisan db:seed` calls `DatabaseSeeder::run()`, not individual seeders.
Always create this base seeder that orchestrates the rest.

---

## Dependency Version Policy

All PHP packages are pinned in `composer.json`. All JS packages are pinned in `package.json` with a
`package-lock.json` or `pnpm-lock.yaml` lockfile. Do not use `^` version ranges for core framework packages — pin exactly.

When this template is updated to use newer versions, the `composer.json` and `package.json` in
`templates/` will reflect the new versions. Projects should update selectively, not by blindly pulling
the latest template.
