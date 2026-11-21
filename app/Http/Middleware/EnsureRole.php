<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->is_active) {
            abort(403);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403);
        }

        if ($user->role !== 'super_admin' && $user->company_id === null) {
            abort(403);
        }

        return $next($request);
    }
}
