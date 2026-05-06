<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Program;

use App\Infrastructure\Persistence\Scopes\OwnerScope;
use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProgramEloquentModel extends Model
{
    use HasFactory;

    protected $table = 'programs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'title', 'description', 'duration_minutes', 'genre', 'owner_id',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(app(OwnerScope::class));
    }

    protected static function newFactory(): ProgramFactory
    {
        return ProgramFactory::new();
    }
}
