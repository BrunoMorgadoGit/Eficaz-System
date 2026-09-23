<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

        return match (strtoupper($role)) {
            'ADMIN' => redirect()->route('admin.dashboard'),
            'REVENDEDOR' => redirect()->route('reseller.dashboard'),
            default => $next($request),
        };
    }
}
