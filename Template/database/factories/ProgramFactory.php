<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProgramEloquentModel>
 */
final class ProgramFactory extends Factory
{
    protected $model = ProgramEloquentModel::class;

    private array $genres = ['drama', 'comedy', 'documentary', 'thriller', 'animation'];

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'duration_minutes' => $this->faker->numberBetween(30, 180),
            'genre' => $this->faker->randomElement($this->genres),
            'owner_id' => (string) Str::uuid(),
        ];
    }

    public function documentary(): self
    {
        return $this->state(['genre' => 'documentary']);
    }

    public function ownedBy(string $ownerId): self
    {
        return $this->state(['owner_id' => $ownerId]);
    }
}
