<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Services\AccessContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AccessContextMiddleware
{
    public function __construct(private readonly AccessContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            $this->context->setFromUser($request->user());
        }

        return $next($request);
    }
}
