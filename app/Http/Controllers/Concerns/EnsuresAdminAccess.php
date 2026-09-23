<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

trait EnsuresAdminAccess
{
    protected function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            throw new AuthorizationException('A área solicitada é exclusiva para administradores.');
        }
    }
}
