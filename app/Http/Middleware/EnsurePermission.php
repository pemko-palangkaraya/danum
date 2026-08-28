<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $resolved = Permission::tryFrom($permission);

        abort_unless($resolved !== null && $request->user()?->hasPermission($resolved), 403);

        return $next($request);
    }
}
