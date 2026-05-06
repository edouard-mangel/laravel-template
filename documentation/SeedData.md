# Seed Data

Factories for test data, seeders for development data.

---

## Two Types of Data Generation

| | Factory | Seeder |
|--|---------|--------|
| Purpose | Test data (per-test) | Development/demo data (one-time) |
| Location | `database/factories/` | `database/seeders/` |
| Used in | Pest tests | `php artisan db:seed` |
| Scope | Realistic, randomized | Specific, predictable |

---

## Factories

Factories generate realistic data using Faker. One factory per Eloquent model.

```php
// database/factories/ProductFactory.php
final class ProductFactory extends Factory
{
    protected $model = ProductEloquentModel::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->words(nb: 3, asText: true),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'price_in_cents' => $this->faker->numberBetween(100, 100000),
            'owner_id' => (string) Str::uuid(),
        ];
    }

    // State methods for specific scenarios
    public function expensive(): self
    {
        return $this->state(['price_in_cents' => 1000000]);
    }

    public function free(): self
    {
        return $this->state(['price_in_cents' => 0]);
    }

    public function ownedBy(UserEloquentModel $user): self
    {
        return $this->state(['owner_id' => $user->id]);
    }
}
```

### Factory Registration

Register factories in `AppServiceProvider` or let Laravel auto-discover them:

```php
// Models resolve factories automatically when the factory class
// follows the naming convention: {Model}Factory → {Model}EloquentModelFactory
// Override with the $factory property if the naming differs.
```

---

## Development Seeder

The `DevDataSeeder` creates predictable data for local development. It is idempotent — running it
twice should not create duplicates:

```php
// database/seeders/DevDataSeeder.php
final class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user (idempotent)
        $admin = UserEloquentModel::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'id' => '00000000-0000-0000-0000-000000000001',
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        // Create demo products
        $products = [
            ['name' => 'Widget Pro', 'sku' => 'WP-001', 'price_in_cents' => 2999],
            ['name' => 'Gadget Plus', 'sku' => 'GP-001', 'price_in_cents' => 4999],
            ['name' => 'Thing Standard', 'sku' => 'TS-001', 'price_in_cents' => 999],
        ];

        foreach ($products as $productData) {
            ProductEloquentModel::firstOrCreate(
                ['sku' => $productData['sku']],
                array_merge($productData, [
                    'id' => (string) Str::uuid(),
                    'owner_id' => $admin->id,
                ])
            );
        }

        $this->command->info('Dev data seeded successfully.');
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=DevDataSeeder
php artisan migrate:fresh --seed  # Fresh database with all seeders
```

---

## Reference Data (Migrations vs Seeders)

For data that is required for the application to function (e.g., enum-like categories, permission
definitions), use migrations rather than seeders:

```php
// In a migration
public function up(): void
{
    Schema::create('categories', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('slug')->unique();
        $table->string('name');
    });

    // Reference data that belongs to the schema version
    DB::table('categories')->insert([
        ['id' => '00000000-0000-0000-0000-000000000001', 'slug' => 'electronics', 'name' => 'Electronics'],
        ['id' => '00000000-0000-0000-0000-000000000002', 'slug' => 'clothing', 'name' => 'Clothing'],
    ]);
}
```

**Why in migrations?** Reference data is tied to a specific schema version. If the schema changes,
the reference data migration changes with it. Putting it in a seeder decouples data from schema
in a way that causes drift.

---

## Factories in Tests

```php
uses(RefreshDatabase::class);

it('lists products for the authenticated user', function (): void {
    $user = UserEloquentModel::factory()->create();

    // Create 3 products owned by the user
    ProductEloquentModel::factory()
        ->count(3)
        ->ownedBy($user)
        ->create();

    // Create 2 products owned by another user (should not appear)
    ProductEloquentModel::factory()->count(2)->create();

    $response = $this->actingAs($user)->getJson('/api/products');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});
```

The `RefreshDatabase` trait wraps each test in a transaction that is rolled back after the test.
No manual cleanup needed.
