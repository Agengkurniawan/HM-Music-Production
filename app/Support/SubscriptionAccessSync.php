<?php

namespace App\Support;

use App\Models\Subscription;

class SubscriptionAccessSync
{
    public function syncExpired(): int
    {
        $expiredSubscriptions = Subscription::with('user')
            ->where('status', 'Active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $synced = 0;

        foreach ($expiredSubscriptions as $subscription) {
            $user = $subscription->user;

            if (! $user) {
                continue;
            }

            $hasOtherActiveSubscription = $user->subscriptions()
                ->where('id', '!=', $subscription->id)
                ->where('status', 'Active')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                })
                ->exists();

            if ($hasOtherActiveSubscription) {
                continue;
            }

            if ($user->plan !== 'Free') {
                $user->update([
                    'plan' => 'Free',
                    'last_activity' => 'Subscription expired automatically',
                ]);

                $synced++;
            }
        }

        return $synced;
    }
}
