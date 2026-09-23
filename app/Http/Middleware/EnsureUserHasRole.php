<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Usage in a route group: EnsureUserHasRole::class . ':ADMIN'
     * or EnsureUserHasRole::class . ':REVENDEDOR'.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')->with('error', 'Faça login para continuar.');
        }

        $allowedRoles = array_map(
            static fn (string $role): string => strtoupper(trim($role)),
            $roles,
        );

        $userRole = $user->role instanceof \BackedEnum
            ? $user->role->value
            : (string) $user->role;

        if (! in_array(strtoupper($userRole), $allowedRoles, true)) {
            abort(403, 'Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }
}
