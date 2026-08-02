<?php

namespace App\Support;

use App\Models\HeaderNotificationRead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class HeaderNotificationReadState
{
    public function __construct(private readonly Request $request)
    {
    }

    public function unread(Collection $notifications, ?User $user): Collection
    {
        $keys = $notifications
            ->pluck('key')
            ->filter()
            ->values();

        if ($keys->isEmpty()) {
            return $notifications->values();
        }

        $readKeys = $this->readKeys($keys, $user);

        if ($readKeys->isEmpty()) {
            return $notifications->values();
        }

        return $notifications
            ->reject(fn (array $notification): bool => $readKeys->contains($notification['key'] ?? null))
            ->values();
    }

    public function markRead(string $notificationKey, ?User $user): void
    {
        $owner = $this->ownerAttributes($user);

        if ($owner === []) {
            return;
        }

        HeaderNotificationRead::updateOrCreate(
            [
                ...$owner,
                'notification_key' => $notificationKey,
            ],
            [
                'read_at' => now(),
            ],
        );
    }

    private function readKeys(Collection $keys, ?User $user): Collection
    {
        $owner = $this->ownerAttributes($user);

        if ($owner === []) {
            return collect();
        }

        return HeaderNotificationRead::query()
            ->where($owner)
            ->whereIn('notification_key', $keys)
            ->pluck('notification_key');
    }

    private function ownerAttributes(?User $user): array
    {
        if ($user) {
            return ['user_id' => $user->id];
        }

        if (! $this->request->hasSession()) {
            return [];
        }

        return ['session_id' => $this->request->session()->getId()];
    }
}
