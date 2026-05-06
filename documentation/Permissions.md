# Permissions and Access Control

This template uses ownership-based access control: resources are filtered by owner, not by role.

---

## Core Concept

Every resource has an `owner_id`. The authenticated user can only see and modify resources where
`owner_id` matches their identity (or their organization's identity in multi-tenant setups).

Enforcement is done at two levels:
1. **Eloquent Global Scope** — filters all SELECT queries automatically
2. **Laravel Policies** — authorize mutations (create, update, delete)

---

## AccessContext

A per-request singleton that carries the authenticated user's ownership context.

```php
final class AccessContext
{
    private string $ownerId;
    private bool $isAdmin = false;

    public function setFromUser(User $user): void
    {
        $this->ownerId = $user->id;
        $this->isAdmin = $user->hasRole('admin');
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }
}
```

Bound as a singleton in `AppServiceProvider`:

```php
$this->app->singleton(AccessContext::class);
```

Populated in `AccessContextMiddleware` (runs on every authenticated request):

```php
final class AccessContextMiddleware
{
    public function __construct(private readonly AccessContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $this->context->setFromUser($request->user());
        }

        return $next($request);
    }
}
```

---

## Global Scope (OwnerScope)

Applied to every Eloquent model that is owned:

```php
final class OwnerScope implements Scope
{
    public function __construct(private readonly AccessContext $context) {}

    public function apply(Builder $builder, Model $model): void
    {
        if ($this->context->isAdmin()) {
            return; // Admin bypass
        }

        $builder->where($model->getTable() . '.owner_id', $this->context->ownerId());
    }
}
```

Resolved from the container (so it gets the singleton `AccessContext`):

```php
protected static function booted(): void
{
    static::addGlobalScope(app(OwnerScope::class));
}
```

**Effect:** Any `ProductEloquentModel::query()` call automatically adds `WHERE owner_id = ?`. No
developer can accidentally query all records — they'd have to explicitly remove the scope with
`withoutGlobalScope(OwnerScope::class)`.

---

## Laravel Policies

Policies authorize individual actions on resources.

```php
final class ProductPolicy
{
    public function update(User $user, ProductEloquentModel $product): bool
    {
        return $user->id === $product->owner_id;
    }

    public function delete(User $user, ProductEloquentModel $product): bool
    {
        return $user->id === $product->owner_id;
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create
    }
}
```

Register in `AuthServiceProvider`:

```php
protected $policies = [
    ProductEloquentModel::class => ProductPolicy::class,
];
```

Use in Form Requests:

```php
final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = ProductEloquentModel::findOrFail($this->route('id'));
        return $this->user()->can('update', $product);
    }
    // ...
}
```

---

## Admin Bypass

Admins skip the `OwnerScope` (they can see all records). They are still subject to policies unless
the policy explicitly allows admins:

```php
public function update(User $user, ProductEloquentModel $product): bool
{
    return $user->id === $product->owner_id || $user->hasRole('admin');
}
```

---

## Multi-Tenant Organization Ownership

If ownership is at the organization level (not user level), resolve the `owner_id` from the
user's organization in `AccessContextMiddleware`:

```php
$this->context->setOwnerId($request->user()->organization_id);
```

All resource queries will then be scoped to the organization without any other changes.

---

## What Passes Through Without Ownership Checks

- Public endpoints (no authentication required) — register these routes outside the `auth:sanctum`
  middleware group
- Admin-only endpoints — use the admin bypass in the scope + admin policy check
- System background jobs (queue workers) — run with a dedicated system context or disable the scope
  explicitly
