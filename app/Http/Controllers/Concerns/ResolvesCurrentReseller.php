<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

trait ResolvesCurrentReseller
{
    protected function currentReseller(Request $request): Reseller
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isReseller()) {
            throw new AuthorizationException('A área solicitada é exclusiva para revendedores.');
        }

        $reseller = $user->reseller;

        if (! $reseller || ! $reseller->is_active) {
            throw new AuthorizationException('Seu cadastro de revendedor não está ativo.');
        }

        return $reseller;
    }
}
