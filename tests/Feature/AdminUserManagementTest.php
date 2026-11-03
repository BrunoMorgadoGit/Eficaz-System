<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureActiveAccount;
use App\Models\Reseller;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_administrator_and_a_reseller(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Adicionar usuário')
            ->assertSee('Usuários');

        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Cargo / perfil');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => '  Ana Administradora  ',
                'email' => ' ANA@EXAMPLE.TEST ',
                'role' => User::ROLE_ADMIN,
            ])
            ->assertRedirect(route('admin.users.index'));

        $createdAdmin = User::query()->where('email', 'ana@example.test')->sole();
        $this->assertSame('Ana Administradora', $createdAdmin->name);
        $this->assertTrue($createdAdmin->is_active);
        $this->assertFalse(Hash::check('password', $createdAdmin->password));
        $this->assertDatabaseMissing('resellers', ['user_id' => $createdAdmin->id]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Carlos Revendedor',
                'email' => 'carlos@example.test',
                'role' => User::ROLE_REVENDEDOR,
                'company_name' => 'Distribuidora Carlos',
            ])
            ->assertRedirect(route('admin.users.index'));

        $resellerUser = User::query()->where('email', 'carlos@example.test')->sole();
        $this->assertDatabaseHas('resellers', [
            'user_id' => $resellerUser->id,
            'company_name' => 'Distribuidora Carlos',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'is_active' => true,
        ]);
    }

    public function test_reseller_cannot_access_user_management_and_company_is_required_for_reseller(): void
    {
        $admin = User::factory()->admin()->create();
        $reseller = User::factory()->reseller()->create();

        $this->actingAs($reseller)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Revendedor sem empresa',
                'email' => 'sem-empresa@example.test',
                'role' => User::ROLE_REVENDEDOR,
            ])
            ->assertSessionHasErrors('company_name');

        $this->assertDatabaseMissing('users', ['email' => 'sem-empresa@example.test']);
    }

    public function test_admin_can_send_setup_link_and_user_can_set_a_password(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Nova Pessoa',
            'email' => 'nova@example.test',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.password.send', $user))
            ->assertRedirect();

        Notification::assertSentTo($user, SetPasswordNotification::class);
        $notification = Notification::sent($user, SetPasswordNotification::class)->first();
        $this->assertNotNull($notification);

        $this->get(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'SenhaNova123!',
            'password_confirmation' => 'SenhaNova123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('SenhaNova123!', $user->fresh()->password));
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'SenhaNova123!',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_inactivating_reseller_disables_both_accounts_and_blocks_access(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $resellerUser = User::factory()->reseller()->create();
        $reseller = Reseller::query()->create([
            'user_id' => $resellerUser->id,
            'company_name' => 'Empresa Inativada',
            'commercial_profile' => Reseller::PROFILE_STANDARD,
            'credit_limit' => null,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.users.status.update', $resellerUser), ['is_active' => 0])
            ->assertRedirect();

        $this->assertFalse($resellerUser->fresh()->is_active);
        $this->assertFalse($reseller->fresh()->is_active);

        $this->actingAs($resellerUser->fresh())
            ->get(route('reseller.dashboard'))
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $resellerUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->post(route('admin.users.password.send', $resellerUser))
            ->assertSessionHas('error');
        Notification::assertNothingSent();

        $this->actingAs($admin)
            ->patch(route('admin.users.status.update', $resellerUser), ['is_active' => 1])
            ->assertRedirect();

        $this->assertTrue($resellerUser->fresh()->is_active);
        $this->assertTrue($reseller->fresh()->is_active);
    }

    public function test_admin_cannot_inactivate_self_or_last_active_administrator(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.status.update', $admin), ['is_active' => 0])
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);

    }

    public function test_overlapping_admin_deactivations_preserve_the_last_active_administrator(): void
    {
        $firstAdmin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create();

        $this->actingAs($firstAdmin)
            ->patch(route('admin.users.status.update', $secondAdmin), ['is_active' => 0])
            ->assertSessionHas('success');

        // Simulate a request that passed active-account middleware before the first update committed.
        $this->withoutMiddleware(EnsureActiveAccount::class)
            ->actingAs($secondAdmin->fresh())
            ->patch(route('admin.users.status.update', $firstAdmin), ['is_active' => 0])
            ->assertSessionHas('error');

        $this->assertTrue($firstAdmin->fresh()->is_active);
        $this->assertFalse($secondAdmin->fresh()->is_active);
        $this->assertSame(1, User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->count());
    }
}
