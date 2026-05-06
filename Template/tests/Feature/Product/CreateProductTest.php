<?php

use App\Infrastructure\Persistence\Product\ProductEloquentModel;
use App\Infrastructure\Persistence\User\UserEloquentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('POST /api/products', function (): void {
    it('creates a product and returns 201 with the new ID', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/products', [
                'name' => 'Test Widget',
                'sku' => 'W-001',
                'price_in_cents' => 2500,
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['id']);

        expect(ProductEloquentModel::withoutGlobalScopes()->count())->toBe(1);
    });

    it('returns 422 when name is missing', function (): void {
        $user = UserEloquentModel::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/products', ['sku' => 'W-001', 'price_in_cents' => 100])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns 401 for unauthenticated requests', function (): void {
        $this->postJson('/api/products', ['name' => 'Test'])->assertStatus(401);
    });
});
