<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if ($request->user() instanceof User) {
            return $this->dashboardRedirect($request->user());
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user instanceof User) {
            $this->logoutSession($request);

            throw ValidationException::withMessages([
                'email' => 'Não foi possível iniciar sua sessão.',
            ]);
        }

        if ($user->isReseller()) {
            $user->loadMissing('reseller');

            if (! $user->reseller || ! $user->reseller->is_active) {
                $this->logoutSession($request);

                throw ValidationException::withMessages([
                    'email' => 'Seu cadastro de revendedor não está ativo.',
                ]);
            }
        }

        if (! $user->isAdmin() && ! $user->isReseller()) {
            $this->logoutSession($request);

            throw ValidationException::withMessages([
                'email' => 'Seu perfil não possui acesso ao portal.',
            ]);
        }

        return $this->dashboardRedirect($user);
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->logoutSession($request);

        return redirect()->route('login')->with('success', 'Sessão encerrada com sucesso.');
    }

    public function profile(Request $request): View
    {
        return view('profile', ['user' => $request->user()]);
    }

    private function dashboardRedirect(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('reseller.dashboard');
    }

    private function logoutSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
