<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        $statusPriority = [
            'Expiring Soon' => 0,
            'Expired' => 1,
            'Active' => 2,
            'Pending Payment' => 3,
            'Cancelled' => 4,
        ];

        $subscriptions = Subscription::with(['user', 'latestPayment'])
            ->latest()
            ->get()
            ->sortBy(function (Subscription $subscription) use ($statusPriority): int {
                $expiryTimestamp = $subscription->expires_at?->timestamp ?? 9999999999;

                return (($statusPriority[$subscription->lifecycle_status] ?? 99) * 10000000000) + $expiryTimestamp;
            })
            ->values();

        $activeSubscriptions = $subscriptions->filter(fn (Subscription $subscription): bool => $subscription->lifecycle_status === 'Active');
        $expiringSoonSubscriptions = $subscriptions->filter(fn (Subscription $subscription): bool => $subscription->lifecycle_status === 'Expiring Soon');
        $activeAccessSubscriptions = $activeSubscriptions->merge($expiringSoonSubscriptions);

        return view('layouts.admin.admin-subcription', [
            'subscriptions' => $subscriptions,
            'subscriptionSummary' => [
                'active' => $activeAccessSubscriptions->count(),
                'expiring_soon' => $expiringSoonSubscriptions->count(),
                'expired' => $subscriptions->filter(fn (Subscription $subscription): bool => $subscription->lifecycle_status === 'Expired')->count(),
                'cancelled' => $subscriptions->filter(fn (Subscription $subscription): bool => $subscription->lifecycle_status === 'Cancelled')->count(),
                'active_amount' => $activeAccessSubscriptions->sum(fn (Subscription $subscription): int => (int) ($subscription->latestPayment?->amount ?? 0)),
            ],
        ]);
    }

    public function renew(Subscription $subscription): RedirectResponse
    {
        $subscription->loadMissing('latestPayment');

        $previousLifecycle = $subscription->lifecycle_status;

        if ($previousLifecycle === 'Active') {
            return back()->withErrors([
                'subscription' => 'Subscription is still active. Manual extension is available only inside the renewal window.',
            ]);
        }

        $baseDate = $subscription->status === 'Active' && $subscription->expires_at?->isFuture()
            ? $subscription->expires_at->copy()
            : now();

        $subscription->update([
            'status' => 'Active',
            'starts_at' => $subscription->starts_at && $subscription->status === 'Active'
                ? $subscription->starts_at
                : now(),
            'expires_at' => $this->expiryForPlan($subscription->package, $baseDate),
        ]);

        $subscription->user?->update(['plan' => $subscription->package, 'status' => 'Active']);

        $paymentIds = Payment::where('subscription_id', $subscription->id)->pluck('id');

        Payment::whereIn('id', $paymentIds)->update([
            'status' => 'Completed',
        ]);

        $message = match ($previousLifecycle) {
            'Pending Payment' => 'Legacy pending subscription activated successfully.',
            'Expiring Soon' => 'Subscription extended successfully.',
            'Cancelled' => 'Subscription restored successfully.',
            default => 'Subscription renewed successfully.',
        };

        return back()->with('success', $message);
    }

    public function suspend(Subscription $subscription): RedirectResponse
    {
        $previousLifecycle = $subscription->lifecycle_status;

        $subscription->update(['status' => 'Cancelled']);
        $subscription->user?->update(['plan' => 'Free']);

        $message = $previousLifecycle === 'Expired'
            ? 'Expired subscription closed and user moved to Free.'
            : 'Subscription cancelled.';

        return back()->with('success', $message);
    }

    private function expiryForPlan(string $package, Carbon $baseDate): Carbon
    {
        return match ($package) {
            'Premium Yearly' => $baseDate->copy()->addYear(),
            'Premium 90 Days' => $baseDate->copy()->addDays(90),
            default => $baseDate->copy()->addMonth(),
        };
    }
}
