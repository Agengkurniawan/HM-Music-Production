<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\Subscription;
use App\Models\StyleSampling;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    private const TEST_PAYMENT_METHOD = 'Test Checkout (Midtrans skipped)';

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $settings = SiteSetting::values();
        $premiumPrice = (int) ($settings['subscription_price'] ?? 29000);
        $planDuration = $settings['plan_duration'] ?? '30 Days';
        $premiumPackage = match ($planDuration) {
            '90 Days' => 'Premium 90 Days',
            '1 Year' => 'Premium Yearly',
            default => 'Premium Monthly',
        };

        $plans = [
            'free' => [
                'name' => 'Free',
                'price' => 0,
                'period' => 'forever',
                'period_label' => 'forever',
            ],
            'premium_monthly' => [
                'name' => $premiumPackage,
                'price' => $premiumPrice,
                'period' => $planDuration,
                'period_label' => match ($planDuration) {
                    '90 Days' => '/90 days',
                    '1 Year' => '/year',
                    default => '/month',
                },
            ],
        ];

        return view('layouts.customers.subcription', [
            'plans' => $plans,
            'settings' => $settings,
            'styleProducts' => StyleSampling::published()
                ->whereNotNull('style_file_path')
                ->latest()
                ->get(),
            'selectedPlan' => $request->query('plan', 'premium_monthly'),
            'openCheckout' => $request->boolean('checkout') || $request->query('checkout') === 'payment',
        ]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        abort_if($request->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:160',
                Rule::unique('users', 'email')->ignore($request->user()?->id),
            ],
            'password' => ['required', 'confirmed', 'min:8', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'package' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:0'],
            'method' => ['nullable', 'string', 'max:80'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $settings = SiteSetting::values();
        $expectedAmount = (int) ($settings['subscription_price'] ?? 29000);
        $planDuration = $settings['plan_duration'] ?? '30 Days';
        $expectedPackage = match ($planDuration) {
            '90 Days' => 'Premium 90 Days',
            '1 Year' => 'Premium Yearly',
            default => 'Premium Monthly',
        };

        if ((int) $validated['amount'] !== $expectedAmount || $validated['package'] !== $expectedPackage) {
            throw ValidationException::withMessages([
                'amount' => 'Subscription package or amount is not valid. Please refresh the checkout page.',
            ]);
        }

        $profilePhotoPath = $request->file('profile_photo')?->store('profile-photos', 'public');

        [$user, $payment] = DB::transaction(function () use ($validated, $planDuration, $profilePhotoPath): array {
            $userData = [
                'name' => $validated['name'],
                'password' => $validated['password'],
                'role' => 'customer',
                'status' => 'Active',
                'plan' => $validated['package'],
                'last_activity' => 'Subscription activated by test checkout',
            ];

            if ($profilePhotoPath) {
                $userData['profile_photo_path'] = $profilePhotoPath;
            }

            $user = User::updateOrCreate(
                ['email' => $validated['email']],
                $userData,
            );

            $user->subscriptions()
                ->where('status', 'Active')
                ->update(['status' => 'Cancelled']);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package' => $validated['package'],
                'starts_at' => now(),
                'expires_at' => $this->expiryForDuration($planDuration),
                'status' => 'Active',
            ]);

            $payment = Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'customer_name' => $validated['name'],
                'customer_email' => $validated['email'],
                'customer_phone' => $validated['phone'] ?? null,
                'package' => $validated['package'],
                'amount' => $validated['amount'],
                'method' => $validated['method'] ?? self::TEST_PAYMENT_METHOD,
                'status' => 'Completed',
                'reference' => 'TEST-PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            ]);

            return [$user, $payment];
        });

        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->regenerate();

        return redirect()
            ->route('login')
            ->withInput(['email' => $user->email])
            ->with('payment_reference', $payment->reference)
            ->with('success', 'Pembayaran berhasil. Akun sudah aktif dan subscription membuka download STY. Sampling voice pack dibeli terpisah sesuai pack yang dipakai style.');
    }

    private function expiryForDuration(string $duration)
    {
        return match ($duration) {
            '90 Days' => now()->addDays(90),
            '1 Year' => now()->addYear(),
            default => now()->addDays(30),
        };
    }
}
