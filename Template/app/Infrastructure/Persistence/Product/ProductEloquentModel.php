<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

use App\Infrastructure\Persistence\Scopes\OwnerScope;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductEloquentModel extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'sku', 'price_in_cents', 'owner_id',
    ];

    protected $casts = [
        'price_in_cents' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(app(OwnerScope::class));
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
