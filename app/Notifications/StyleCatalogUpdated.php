<?php

namespace App\Notifications;

use App\Models\StyleSampling;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StyleCatalogUpdated extends Notification
{
    public function __construct(
        public readonly StyleSampling $styleSampling,
        public readonly string $action,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionText = match ($this->action) {
            'updated' => 'diperbarui',
            default => 'ditambahkan',
        };

        return (new MailMessage)
            ->subject('HM Music style '.$actionText.': '.$this->styleSampling->name)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Style '.$this->styleSampling->name.' baru saja '.$actionText.' di katalog HM Music.')
            ->line('Kategori: '.$this->styleSampling->category)
            ->line('Pack: '.($this->styleSampling->pack ?: 'By request'))
            ->action('Open Style Catalog', route('stylesampling', [
                'type' => 'style',
                'category' => $this->styleSampling->category,
            ]))
            ->line('Subscription membuka download STY. Sampling voice pack tetap dibeli terpisah bila style membutuhkan voice kit.');
    }
}
