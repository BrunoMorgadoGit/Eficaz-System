<?php

namespace Tests\Unit;

use App\Http\Controllers\Concerns\EnsuresAdminAccess;
use App\Http\Controllers\Concerns\ResolvesCurrentReseller;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AccessMiddlewareTest extends TestCase
{
    #[DataProvider('authenticatedRoleRedirects')]
    public function test_authenticated_users_are_redirected_by_role(string $role, ?string $route): void
    {
        $request = $this->requestWithUser(new User(['role' => $role]));
        $nextResponse = new Response('next');

        $response = (new RedirectIfAuthenticated)->handle($request, fn () => $nextResponse);

        if ($route === null) {
            $this->assertSame($nextResponse, $response);

            return;
        }

        $this->assertSame(route($route), $response->getTargetUrl());
    }

    public static function authenticatedRoleRedirects(): array
    {
        return [
            'administrator' => [User::ROLE_ADMIN, 'admin.dashboard'],
            'reseller' => [User::ROLE_REVENDEDOR, 'reseller.dashboard'],
            'unknown role continues' => ['OPERADOR', null],
        ];
    }

    public function test_redirect_middleware_passes_through_for_guests(): void
    {
        $nextResponse = new Response('next');
        $request = $this->requestWithUser(null);

        $this->assertSame(
            $nextResponse,
            (new RedirectIfAuthenticated)->handle($request, fn () => $nextResponse),
        );
    }

    public function test_role_middleware_redirects_guests_and_allows_normalized_role_names(): void
    {
        $middleware = new EnsureUserHasRole;
        $guestResponse = $middleware->handle(
            $this->requestWithUser(null),
            fn () => new Response('should not run'),
            'ADMIN',
        );
        $this->assertSame(route('login'), $guestResponse->getTargetUrl());

        $nextResponse = new Response('allowed');
        $authenticatedResponse = $middleware->handle(
            $this->requestWithUser(new User(['role' => 'admin'])),
            fn () => $nextResponse,
            ' admin ',
        );
        $this->assertSame($nextResponse, $authenticatedResponse);
    }

    public function test_role_middleware_forbids_users_with_another_role(): void
    {
        try {
            (new EnsureUserHasRole)->handle(
                $this->requestWithUser(new User(['role' => User::ROLE_REVENDEDOR])),
                fn () => new Response('should not run'),
                User::ROLE_ADMIN,
            );
            $this->fail('A reseller must be forbidden from the admin area.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_admin_access_concern_accepts_only_administrators(): void
    {
        $concern = new class
        {
            use EnsuresAdminAccess;

            public function authorize(Request $request): void
            {
                $this->ensureAdmin($request);
            }
        };

        $concern->authorize($this->requestWithUser(new User(['role' => User::ROLE_ADMIN])));

        $this->expectException(AuthorizationException::class);
        $concern->authorize($this->requestWithUser(new User(['role' => User::ROLE_REVENDEDOR])));
    }

    public function test_current_reseller_concern_rejects_missing_users_and_other_roles(): void
    {
        $concern = $this->resellerResolver();

        try {
            $concern->resolve($this->requestWithUser(null));
            $this->fail('A guest cannot resolve a reseller profile.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('A área solicitada é exclusiva para revendedores.', $exception->getMessage());
        }

        $administrator = new User(['role' => User::ROLE_ADMIN]);
        $this->expectException(AuthorizationException::class);
        $concern->resolve($this->requestWithUser($administrator));
    }

    public function test_current_reseller_concern_rejects_missing_or_inactive_profiles(): void
    {
        $concern = $this->resellerResolver();
        $missingProfile = new User(['role' => User::ROLE_REVENDEDOR]);
        $missingProfile->setRelation('reseller', null);

        try {
            $concern->resolve($this->requestWithUser($missingProfile));
            $this->fail('A reseller user without a reseller profile must be rejected.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('Seu cadastro de revendedor não está ativo.', $exception->getMessage());
        }

        $inactiveProfileUser = new User(['role' => User::ROLE_REVENDEDOR]);
        $inactiveProfileUser->setRelation('reseller', new Reseller(['is_active' => false]));
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Seu cadastro de revendedor não está ativo.');
        $concern->resolve($this->requestWithUser($inactiveProfileUser));
    }

    public function test_current_reseller_concern_returns_an_active_profile(): void
    {
        $reseller = new Reseller(['is_active' => true]);
        $user = new User(['role' => User::ROLE_REVENDEDOR]);
        $user->setRelation('reseller', $reseller);
        $concern = $this->resellerResolver();

        $this->assertSame($reseller, $concern->resolve($this->requestWithUser($user)));
    }

    private function resellerResolver(): object
    {
        return new class
        {
            use ResolvesCurrentReseller;

            public function resolve(Request $request): Reseller
            {
                return $this->currentReseller($request);
            }
        };
    }

    private function requestWithUser(mixed $user): Request
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
