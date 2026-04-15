<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAbility
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if (! $user->hasAbility($ability)) {
            abort(403);
        }

        return $next($request);
    }
}
