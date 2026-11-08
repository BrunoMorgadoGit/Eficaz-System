<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Http\Requests\UpdateCommercialPolicyRequest;
use App\Models\CommercialPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminCommercialPolicyController extends Controller
{
    use EnsuresAdminAccess;

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);
        $policies = CommercialPolicy::query()
            ->orderByRaw("CASE commercial_profile WHEN 'STANDARD' THEN 1 WHEN 'GOLD' THEN 2 WHEN 'PREMIUM' THEN 3 ELSE 4 END")
            ->get();

        return view('admin.commercial-policies.index', compact('policies'));
    }

    public function update(UpdateCommercialPolicyRequest $request, CommercialPolicy $commercialPolicy): RedirectResponse
    {
        $this->ensureAdmin($request);
        $commercialPolicy->update($request->validated());

        return redirect()->route('admin.commercial-policies.index')->with('success', 'Condições do grupo atualizadas. Novos orçamentos usarão as regras salvas.');
    }
}
