<?php

declare(strict_types=1);

namespace App\Http\Services;

use Illuminate\Contracts\Auth\Authenticatable;

final class AccessContext
{
    private string $ownerId = '';

    private bool $isAdmin = false;

    public function setFromUser(Authenticatable $user): void
    {
        $this->ownerId = (string) $user->getAuthIdentifier();
        $this->isAdmin = method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function ownerId(): string
    {
        return $this->ownerId;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function isInitialized(): bool
    {
        return $this->ownerId !== '';
    }
}
