<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const STATUSES = ['Active', 'Suspended', 'Review'];

    private const PLANS = ['Free', 'Starter Monthly', 'Premium Monthly', 'Premium 90 Days', 'Premium Yearly', 'Studio Pro'];

    public function index(): View
    {
        return view('layouts.admin.admin-usermanagement', [
            'users' => User::with([
                    'activeSubscription',
                    'latestSubscription',
                    'latestPayment',
                    'latestSamplingRequest',
                    'latestDownloadSale',
                ])
                ->withCount(['samplingRequests', 'downloadSales', 'payments'])
                ->withSum('downloadSales', 'amount')
                ->where('role', 'customer')
                ->latest()
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->ensureCustomer($user);
        $request->session()->flash('admin_user_manage_id', $user->id);
        $request->session()->flash('admin_user_manage_tab', 'status-user-tab');

        $validated = $request->validateWithBag('adminUserStatus', [
            'status' => ['required', Rule::in(self::STATUSES)],
            'cancel_subscription' => ['nullable', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:240'],
        ]);

        $updates = [
            'status' => $validated['status'],
            'last_activity' => $this->statusActivityMessage($validated['status']),
        ];

        if (($validated['admin_note'] ?? null) !== null) {
            $updates['last_activity'] .= ': '.$validated['admin_note'];
        }

        if ($validated['status'] === 'Suspended' && $request->boolean('cancel_subscription')) {
            $user->subscriptions()
                ->where('status', 'Active')
                ->update(['status' => 'Cancelled']);

            $updates['plan'] = 'Free';
        }

        $user->update($updates);

        return back()->with('success', $user->name.' status updated to '.$validated['status'].'.');
    }

    public function syncAccess(User $user): RedirectResponse
    {
        $this->ensureCustomer($user);

        $subscription = $user->subscriptions()
            ->where('status', 'Pending')
            ->latest()
            ->first();

        $payment = $user->payments()
            ->where('status', 'Pending')
            ->latest()
            ->first();

        if (! $subscription && ! $payment) {
            return back()->withErrors([
                'access' => 'No legacy pending payment or subscription was found for '.$user->name.'.',
            ], 'adminUserAction');
        }

        $package = $subscription?->package ?: $payment?->package ?: 'Premium Monthly';
        $expiresAt = $this->defaultExpiryForPlan($package);

        if ($subscription) {
            $subscription->update([
                'status' => 'Active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
            ]);
        } else {
            $subscription = $user->subscriptions()->create([
                'package' => $package,
                'status' => 'Active',
                'starts_at' => now(),
                'expires_at' => $expiresAt,
            ]);
        }

        $payment?->update([
            'status' => 'Completed',
            'subscription_id' => $subscription->id,
        ]);

        $user->update([
            'status' => 'Active',
            'plan' => $package,
            'last_activity' => 'Legacy subscription access synced by admin',
        ]);

        return back()->with('success', $user->name.' subscription access synced.');
    }

    public function updatePlan(Request $request, User $user): RedirectResponse
    {
        $this->ensureCustomer($user);
        $request->session()->flash('admin_user_manage_id', $user->id);
        $request->session()->flash('admin_user_manage_tab', 'plan-user-tab');

        $validated = $request->validateWithBag('adminUserPlan', [
            'plan' => ['required', Rule::in(self::PLANS)],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $plan = $validated['plan'];

        if ($plan === 'Free') {
            $user->subscriptions()
                ->where('status', 'Active')
                ->update(['status' => 'Cancelled']);

            $user->update([
                'plan' => 'Free',
                'status' => 'Active',
                'last_activity' => 'Plan changed to Free by admin',
            ]);

            return back()->with('success', $user->name.' moved to Free plan.');
        }

        $expiresAt = isset($validated['expires_at'])
            ? Carbon::parse($validated['expires_at'])->endOfDay()
            : $this->defaultExpiryForPlan($plan);

        $user->subscriptions()
            ->where('status', 'Active')
            ->update(['status' => 'Cancelled']);

        $user->subscriptions()->create([
            'package' => $plan,
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'status' => 'Active',
        ]);

        $user->update([
            'plan' => $plan,
            'status' => 'Active',
            'last_activity' => 'Manual upgrade to '.$plan,
        ]);

        return back()->with('success', $user->name.' upgraded to '.$plan.'.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->ensureCustomer($user);
        $request->session()->flash('admin_user_manage_id', $user->id);
        $request->session()->flash('admin_user_manage_tab', 'password-user-tab');

        $validated = $request->validateWithBag('adminUserPassword', [
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.letters' => 'Password harus berisi minimal satu huruf.',
            'password.numbers' => 'Password harus berisi minimal satu angka.',
        ]);

        $user->update([
            'password' => $validated['password'],
            'last_activity' => 'Password reset by admin',
        ]);

        return back()->with('success', 'Password reset for '.$user->name.'.');
    }

    private function ensureCustomer(User $user): void
    {
        abort_unless($user->role === 'customer', 404);
    }

    private function defaultExpiryForPlan(string $plan): Carbon
    {
        return match ($plan) {
            'Premium Yearly' => now()->addYear(),
            'Premium 90 Days' => now()->addDays(90),
            'Studio Pro' => now()->addMonth(),
            default => now()->addMonth(),
        };
    }

    private function statusActivityMessage(string $status): string
    {
        return match ($status) {
            'Active' => 'Account activated by admin',
            'Suspended' => 'Account suspended by admin',
            'Review' => 'Account marked for review by admin',
            default => 'Account status updated by admin',
        };
    }
}
