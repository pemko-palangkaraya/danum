<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        // Permission checks must use the current persisted RBAC assignment.
        // This is important for custom tenant roles: the HTTP request may carry
        // a different Eloquent instance than the one used by the caller before
        // actingAs(), so do not rely on a previously-loaded relationship state.
        $user->load('customRole.permissions');

        abort_unless($user->hasPermission($permission), 403);

        return $next($request);
    }
}
