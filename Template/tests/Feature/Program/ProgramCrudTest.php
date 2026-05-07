<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Program\ProgramEloquentModel;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('GET /api/programs', function (): void {
    it('returns paginated list of programs', function (): void {
        $user = UserEloquentModel::factory()->create();
        ProgramEloquentModel::factory()->count(3)->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/programs')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page']]);
    });

    it('only returns programs owned by the authenticated user', function (): void {
        $owner = UserEloquentModel::factory()->create();
        $other = UserEloquentModel::factory()->create();

        ProgramEloquentModel::factory()->count(2)->create(['owner_id' => $owner->id]);
        ProgramEloquentModel::factory()->count(3)->create(['owner_id' => $other->id]);

        $response = $this->actingAs($owner)
            ->getJson('/api/programs')
            ->assertStatus(200);

        expect($response->json('meta.total'))->toBe(2);
    });

    it('returns 401 for unauthenticated requests', function (): void {
        $this->getJson('/api/programs')->assertStatus(401);
    });
});

describe('GET /api/programs/:id', function (): void {
    it('returns the program with data wrapper', function (): void {
        $user = UserEloquentModel::factory()->create();
        $program = ProgramEloquentModel::factory()->create([
            'owner_id' => $user->id,
            'title' => 'Planet Earth III',
            'genre' => 'documentary',
            'duration_minutes' => 60,
        ]);

        $this->actingAs($user)
            ->getJson("/api/programs/{$program->id}")
            ->assertStatus(200)
            ->assertJson(['data' => [
                'title' => 'Planet Earth III',
                'genre' => 'documentary',
                'duration_minutes' => 60,
            ]]);
    });

    it('returns 404 for another user\'s program', function (): void {
        $owner = UserEloquentModel::factory()->create();
        $other = UserEloquentModel::factory()->create();
        $program = ProgramEloquentModel::factory()->create(['owner_id' => $other->id]);

        $this->actingAs($owner)
            ->getJson("/api/programs/{$program->id}")
            ->assertStatus(404);
    });

    it('returns 404 for a non-existent id', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/programs/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    });
});

describe('PUT /api/programs/:id', function (): void {
    it('updates a program and returns 204', function (): void {
        $user = UserEloquentModel::factory()->create();
        $program = ProgramEloquentModel::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->putJson("/api/programs/{$program->id}", [
                'title' => 'Updated Title',
                'duration_minutes' => 90,
            ])
            ->assertStatus(204);

        expect(ProgramEloquentModel::withoutGlobalScopes()->find($program->id)->title)
            ->toBe('Updated Title');
    });

    it('returns 404 when updating another user\'s program', function (): void {
        $owner = UserEloquentModel::factory()->create();
        $other = UserEloquentModel::factory()->create();
        $program = ProgramEloquentModel::factory()->create(['owner_id' => $other->id]);

        $this->actingAs($owner)
            ->putJson("/api/programs/{$program->id}", ['title' => 'Hijack'])
            ->assertStatus(404);
    });
});

describe('DELETE /api/programs/:id', function (): void {
    it('deletes a program and returns 204', function (): void {
        $user = UserEloquentModel::factory()->create();
        $program = ProgramEloquentModel::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/programs/{$program->id}")
            ->assertStatus(204);

        expect(ProgramEloquentModel::withoutGlobalScopes()->find($program->id))->toBeNull();
    });

    it('returns 404 when deleting another user\'s program', function (): void {
        $owner = UserEloquentModel::factory()->create();
        $other = UserEloquentModel::factory()->create();
        $program = ProgramEloquentModel::factory()->create(['owner_id' => $other->id]);

        $this->actingAs($owner)
            ->deleteJson("/api/programs/{$program->id}")
            ->assertStatus(404);
    });
});
