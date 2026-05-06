# Specification Pattern

Specifications are composable query predicates that encapsulate filtering logic.

---

## What Is a Specification?

A Specification represents a business rule that can be used to filter a collection. Specifications
can be combined with `and()`, `or()`, and `not()` operators.

```php
interface SpecificationInterface
{
    public function isSatisfiedBy(mixed $candidate): bool;
    public function toQueryBuilder(Builder $query): Builder;
    public function and(SpecificationInterface $other): self;
    public function or(SpecificationInterface $other): self;
    public function not(): self;
}
```

---

## Use Cases

Specifications shine when:
- The same filter logic is used in multiple queries
- Filters can be combined in different combinations
- Business rules need to be testable without a database

---

## Example: Product Specifications

```php
final class ActiveProductSpecification extends AbstractSpecification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate instanceof Product && $candidate->isActive();
    }

    public function toQueryBuilder(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}

final class PriceRangeSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly int $minCents,
        private readonly int $maxCents,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $candidate instanceof Product
            && $candidate->price()->valueInCents >= $this->minCents
            && $candidate->price()->valueInCents <= $this->maxCents;
    }

    public function toQueryBuilder(Builder $query): Builder
    {
        return $query
            ->where('price_in_cents', '>=', $this->minCents)
            ->where('price_in_cents', '<=', $this->maxCents);
    }
}
```

---

## Composing Specifications

```php
$activeAndAffordable = (new ActiveProductSpecification())
    ->and(new PriceRangeSpecification(100, 5000));

// Use in a repository or finder
$query = ProductEloquentModel::query();
$query = $activeAndAffordable->toQueryBuilder($query);
$results = $query->get();
```

---

## Abstract Base Class

```php
abstract class AbstractSpecification implements SpecificationInterface
{
    public function and(SpecificationInterface $other): self
    {
        return new AndSpecification($this, $other);
    }

    public function or(SpecificationInterface $other): self
    {
        return new OrSpecification($this, $other);
    }

    public function not(): self
    {
        return new NotSpecification($this);
    }
}

final class AndSpecification extends AbstractSpecification
{
    public function __construct(
        private readonly SpecificationInterface $left,
        private readonly SpecificationInterface $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        return $this->left->isSatisfiedBy($candidate)
            && $this->right->isSatisfiedBy($candidate);
    }

    public function toQueryBuilder(Builder $query): Builder
    {
        return $this->right->toQueryBuilder(
            $this->left->toQueryBuilder($query)
        );
    }
}
```

---

## When to Use Specifications vs. Scopes

| Specifications | Eloquent Scopes |
|---------------|----------------|
| Composable at runtime | Defined at model definition time |
| Domain-aware (can check domain objects) | Eloquent/SQL only |
| Testable without DB | Require DB for testing |
| Verbose | Concise |

Use **Specifications** when the filtering logic is complex, combinatorial, or shared across
domain and persistence layers. Use **Eloquent Scopes** for simple, always-on query modifications
that don't need to be composed.

---

## Integration with Finders

```php
final class ProductFinder
{
    public function findBySpecification(SpecificationInterface $spec): Collection
    {
        $query = ProductEloquentModel::query();
        $query = $spec->toQueryBuilder($query);
        return $query->get()->map(fn ($m) => $this->toDto($m));
    }
}

// Usage in a Query class
$spec = (new ActiveProductSpecification())
    ->and(new PriceRangeSpecification(100, 5000));

$products = $finder->findBySpecification($spec);
```
