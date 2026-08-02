<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPasswordNotification extends Notification
{
    public function __construct(
        public readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Verifikasi reset password HM Music')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Kami menerima permintaan reset password untuk akun customer HM Music Anda.')
            ->line('Demi keamanan, password hanya bisa diganti setelah Anda membuka link verifikasi ini.')
            ->action('Reset Password', $url)
            ->line('Link ini berlaku 60 menit. Abaikan email ini jika Anda tidak meminta reset password.');
    }
}
