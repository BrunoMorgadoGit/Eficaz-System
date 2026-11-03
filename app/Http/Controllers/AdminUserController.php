<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Http\Requests\StoreManagedUserRequest;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AdminUserController extends Controller
{
    use EnsuresAdminAccess;

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $users = User::query()
            ->with('reseller')
            ->orderByDesc('is_active')
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.users.create');
    }

    public function store(StoreManagedUserRequest $request): RedirectResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'password' => Str::random(64),
                'is_active' => true,
            ]);

            if ($user->isReseller()) {
                $user->reseller()->create([
                    'company_name' => $data['company_name'],
                    'commercial_profile' => Reseller::PROFILE_STANDARD,
                    'credit_limit' => null,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário cadastrado. Envie o link de definição de senha pela lista de usuários.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);
        $isActive = (bool) $validated['is_active'];

        $result = DB::transaction(function () use ($request, $user, $isActive): string {
            $adminUsers = collect();

            if ($user->isAdmin()) {
                // Lock admin rows in the same order so concurrent deactivations serialize.
                $adminUsers = User::query()
                    ->where('role', User::ROLE_ADMIN)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }

            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->is_active === $isActive) {
                return 'unchanged';
            }

            if (! $isActive && $lockedUser->is($request->user())) {
                return 'self';
            }

            if (! $isActive && $lockedUser->isAdmin()
                && $adminUsers->where('is_active', true)->count() <= 1) {
                return 'last_admin';
            }

            $lockedUser->update(['is_active' => $isActive]);

            if ($lockedUser->isReseller()) {
                $lockedUser->reseller()->update(['is_active' => $isActive]);
            }

            return 'updated';
        });

        if ($result === 'unchanged') {
            return back()->with('success', 'O status do usuário já estava atualizado.');
        }

        if ($result === 'self') {
            return back()->with('error', 'Você não pode inativar sua própria conta.');
        }

        if ($result === 'last_admin') {
            return back()->with('error', 'Não é possível inativar o último administrador ativo.');
        }

        return back()->with('success', $isActive
            ? 'Usuário reativado com sucesso.'
            : 'Usuário inativado e acesso bloqueado.');
    }

    public function sendPasswordSetupEmail(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        if (! $user->is_active) {
            return back()->with('error', 'Reative o usuário antes de enviar o link de definição de senha.');
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Link de definição de senha enviado para '.$user->email.'.');
        }

        return back()->with('error', $status === Password::RESET_THROTTLED
            ? 'Um link foi enviado recentemente. Aguarde antes de solicitar outro.'
            : 'Não foi possível enviar o link de definição de senha.');
    }
}
