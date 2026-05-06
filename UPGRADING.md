# Upgrading Guide

This document tracks breaking changes and migration steps between major template versions.

---

## Template Versioning Policy

The template itself is not versioned with SemVer. Instead, each project that scaffolds from this template
should pin the versions of its dependencies in `composer.json` and `package.json`. When you upgrade a
dependency in your project, consult this file for any template-level changes that should accompany it.

---

## PHP 8.3 → 8.4

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

## Angular 19 → 20

See [Frontend.md](documentation/Frontend.md) for current Angular 20 patterns. Key changes from Angular 19:

1. Signals API is stable — use `signal()` and `computed()` for reactive state
2. `@defer` blocks replace `*ngIf` lazy-loading patterns
3. Standalone components are the default — no `NgModule` required
4. `inject()` function preferred over constructor injection

---

## Dependency Version Policy

All PHP packages are pinned in `composer.json`. All JS packages are pinned in `package.json` with a
`pnpm-lock.yaml` lockfile. Do not use `^` version ranges for core framework packages — pin exactly.

When this template is updated to use newer versions, the `composer.json` and `package.json` in
`templates/` will reflect the new versions. Projects should update selectively, not by blindly pulling
the latest template.
