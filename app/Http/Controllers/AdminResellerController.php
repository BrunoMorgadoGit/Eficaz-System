<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Models\Reseller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminResellerController extends Controller
{
    use EnsuresAdminAccess;

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);
        $resellers = Reseller::query()
            ->with('user')
            ->orderBy('company_name')
            ->paginate(15);

        return view('admin.resellers.index', compact('resellers'));
    }
}
