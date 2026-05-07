<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns all feature flags for an authenticated user', function (): void {
    $user = UserEloquentModel::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/features')
        ->assertStatus(200)
        ->assertJsonStructure(['new-dashboard', 'beta-export'])
        ->assertJsonFragment(['new-dashboard' => true]);
});

it('returns 401 for unauthenticated requests', function (): void {
    $this->getJson('/api/features')->assertStatus(401);
});
