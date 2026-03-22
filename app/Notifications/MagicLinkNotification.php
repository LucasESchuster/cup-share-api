<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly int $expiresInMinutes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url')."/auth/callback?token={$this->token}";

        return (new MailMessage)
            ->subject('Seu link de acesso — Cup Share')
            ->greeting('Olá!')
            ->line('Clique no botão abaixo para acessar sua conta. O link expira em '.$this->expiresInMinutes.' minutos.')
            ->action('Acessar minha conta', $url)
            ->line('Se você não solicitou este link, ignore este e-mail.')
            ->salutation('Cup Share');
    }
}
