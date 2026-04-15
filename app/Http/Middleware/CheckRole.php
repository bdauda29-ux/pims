<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 * This middleware intercepts requests and checks if the logged-in user
 * has one of the required roles to access the route.
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string[]  ...$roles  The list of allowed roles (e.g., 'Main Admin', 'User')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Ensure the user is logged in
        if (! $request->user()) {
            return redirect('login');
        }

        // 2. Check if the user's role is in the list of allowed roles
        // Beginners note: 'in_array' checks if a value exists in an array.
        if (! in_array($request->user()->role, $roles)) {
            // If not authorized, abort with a 403 Forbidden error.
            abort(403, 'You do not have permission to access this page.');
        }

        // 3. If all checks pass, proceed to the next step (the controller)
        return $next($request);
    }
}
