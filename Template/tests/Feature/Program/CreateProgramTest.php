<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/programs', function (): void {
    it('creates a program and returns 201 with id', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/programs', [
                'title' => 'Planet Earth III',
                'description' => 'A stunning nature documentary.',
                'duration_minutes' => 60,
                'genre' => 'documentary',
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['id']);

        expect(ProgramEloquentModel::withoutGlobalScopes()->count())->toBe(1);
    });

    it('returns 422 when title is missing', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/programs', [
                'duration_minutes' => 60,
                'genre' => 'documentary',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    it('returns 422 when duration_minutes is zero', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/programs', [
                'title' => 'Test',
                'duration_minutes' => 0,
                'genre' => 'drama',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duration_minutes']);
    });

    it('returns 201 when description is omitted', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/programs', [
                'title' => 'The Wire',
                'duration_minutes' => 55,
                'genre' => 'drama',
            ])
            ->assertStatus(201);
    });

    it('returns 401 for unauthenticated requests', function (): void {
        $this->postJson('/api/programs', ['title' => 'Test'])->assertStatus(401);
    });
});
