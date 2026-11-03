<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserManagementEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_admin_user_management(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_unchanged_status_is_idempotent_and_invalid_status_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $managedUser = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.status.update', $managedUser), ['is_active' => '1'])
            ->assertSessionHas('success', 'O status do usuário já estava atualizado.');

        $this->patch(route('admin.users.status.update', $managedUser), ['is_active' => 'active'])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($managedUser->fresh()->is_active);
    }

    public function test_repeated_password_setup_requests_are_throttled(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $managedUser = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.password.send', $managedUser))
            ->assertSessionHas('success');

        $this->post(route('admin.users.password.send', $managedUser))
            ->assertSessionHas('error', 'Um link foi enviado recentemente. Aguarde antes de solicitar outro.');

        Notification::assertSentToTimes($managedUser, SetPasswordNotification::class, 1);
    }
}
