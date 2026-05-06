<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Product\ProductEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductEloquentModel>
 */
final class ProductFactory extends Factory
{
    protected $model = ProductEloquentModel::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-###??')),
            'price_in_cents' => $this->faker->numberBetween(100, 100000),
            'owner_id' => (string) Str::uuid(),
        ];
    }

    public function expensive(): self
    {
        return $this->state(['price_in_cents' => 100000]);
    }

    public function ownedBy(string $ownerId): self
    {
        return $this->state(['owner_id' => $ownerId]);
    }
}
