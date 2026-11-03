<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_set_a_password(): void
    {
        $user = User::factory()->admin()->create(['is_active' => false]);
        $originalPassword = $user->password;

        $this->post(route('password.update'), [
            'token' => 'valid-looking-token',
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_nonexistent_user_cannot_set_a_password(): void
    {
        $this->post(route('password.update'), [
            'token' => 'valid-looking-token',
            'email' => 'missing@example.test',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasErrors('email');
    }

    public function test_expired_or_used_setup_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'unknown-token',
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_password_setup_requires_a_confirmed_password_of_the_minimum_length(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'unknown-token',
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }

    public function test_password_setup_notification_contains_the_user_specific_link_and_expiration(): void
    {
        $user = User::factory()->create(['name' => 'Ana Teste', 'email' => 'ana@example.test']);
        $notification = new SetPasswordNotification('setup-token');
        $mail = $notification->toMail($user);

        $this->assertSame(['mail'], $notification->via($user));
        $this->assertSame('Defina sua senha de acesso à Eficaz B2B', $mail->subject);
        $this->assertSame('Olá, Ana Teste!', $mail->greeting);
        $this->assertSame('Definir minha senha', $mail->actionText);
        $this->assertSame(route('password.reset', [
            'token' => 'setup-token',
            'email' => $user->email,
        ]), $mail->actionUrl);
        $this->assertContains('Por segurança, este link expira em 60 minutos.', $mail->outroLines);
    }
}
