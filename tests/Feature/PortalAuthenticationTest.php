<?php

namespace Tests\Feature;

use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_normalizes_email_and_redirects_each_role_to_its_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.test']);

        $this->post(route('login.store'), [
            'email' => '  ADMIN@EXAMPLE.TEST ',
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $reseller = $this->makeReseller();
        $this->post(route('logout'));
        $this->post(route('login.store'), [
            'email' => strtoupper($reseller->email),
            'password' => 'password',
        ])->assertRedirect(route('reseller.dashboard'));

        $this->assertAuthenticatedAs($reseller);
    }

    public function test_guests_see_login_and_authenticated_users_are_redirected_to_their_dashboard(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Acesse sua conta');

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_invalid_credentials_and_inactive_accounts_are_not_authenticated(): void
    {
        $activeUser = User::factory()->admin()->create();

        $this->post(route('login.store'), [
            'email' => $activeUser->email,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $inactiveUser = User::factory()->admin()->create(['is_active' => false]);
        $this->post(route('login.store'), [
            'email' => $inactiveUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_requires_a_valid_email_and_password(): void
    {
        $this->post(route('login.store'), [])
            ->assertSessionHasErrors(['email', 'password']);

        $this->post(route('login.store'), [
            'email' => 'not-an-email',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_limits_attempts_per_email_and_ip(): void
    {
        $payload = [
            'email' => 'locked@example.test',
            'password' => 'incorrect-password',
        ];

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('login.store'), $payload)->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            ...$payload,
            'email' => 'another@example.test',
        ])->assertSessionHasErrors('email');

        $this->post(route('login.store'), $payload)->assertStatus(429);
    }

    public function test_login_rejects_resellers_without_an_active_reseller_profile(): void
    {
        $missingProfile = User::factory()->reseller()->create();
        $this->post(route('login.store'), [
            'email' => $missingProfile->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($missingProfile)
            ->get(route('reseller.dashboard'))
            ->assertForbidden();
        $this->post(route('logout'));

        $inactiveProfileUser = User::factory()->reseller()->create();
        Reseller::query()->create([
            'user_id' => $inactiveProfileUser->id,
            'company_name' => 'Perfil inativo',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'credit_limit' => null,
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $inactiveProfileUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($inactiveProfileUser)
            ->get(route('reseller.dashboard'))
            ->assertForbidden();
    }

    public function test_logout_invalidates_the_session_and_profile_is_authenticated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee($admin->name);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    private function makeReseller(): User
    {
        $user = User::factory()->reseller()->create();
        Reseller::query()->create([
            'user_id' => $user->id,
            'company_name' => 'Revendedor de teste',
            'commercial_profile' => Reseller::PROFILE_GOLD,
            'credit_limit' => null,
            'is_active' => true,
        ]);

        return $user;
    }
}
