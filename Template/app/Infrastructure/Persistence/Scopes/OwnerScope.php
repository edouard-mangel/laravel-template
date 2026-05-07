<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Scopes;

use App\Http\Services\AccessContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class OwnerScope implements Scope
{
    public function __construct(private readonly AccessContext $context) {}

    public function apply(Builder $builder, Model $model): void
    {
        if ($this->context->isAdmin()) {
            return;
        }

        if (! $this->context->isInitialized()) {
            return;
        }

        $builder->where($model->getTable().'.owner_id', $this->context->ownerId());
    }
}
