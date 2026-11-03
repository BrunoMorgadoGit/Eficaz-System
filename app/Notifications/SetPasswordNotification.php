<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $expiration = (int) config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Defina sua senha de acesso à Eficaz B2B')
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line('Seu acesso ao portal Eficaz B2B foi cadastrado.')
            ->line('Use o botão abaixo para criar sua senha pessoal.')
            ->action('Definir minha senha', $url)
            ->line("Por segurança, este link expira em {$expiration} minutos.")
            ->line('Se você não esperava este convite, ignore esta mensagem.');
    }
}
