<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if (! $user->must_change_password) {
            return $next($request);
        }

        if ($request->isMethod('get') && ! $request->session()->has('password_prompted')) {
            $request->session()->put('password_prompted', true);
            $request->session()->flash('show_change_password_modal', true);
        }

        return $next($request);
    }
}
