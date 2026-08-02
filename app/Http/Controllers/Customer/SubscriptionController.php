<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SamplingRequest;
use App\Models\SiteSetting;
use App\Models\Subscription;
use App\Models\StyleSampling;
use App\Models\User;
use App\Services\MidtransSnapGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class SubscriptionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $settings = SiteSetting::values();
        $premiumPrice = (int) ($settings['subscription_price'] ?? SiteSetting::DEFAULT_SUBSCRIPTION_PRICE);
        $planDuration = $settings['plan_duration'] ?? '30 Days';
        $premiumPackage = match ($planDuration) {
            '90 Days' => 'Premium 90 Days',
            '1 Year' => 'Premium Yearly',
            default => 'Premium Monthly',
        };
        $currentUser = $request->user();
        $currentSubscription = $currentUser?->activeSubscription()->first();
        $hasActiveSubscription = $this->hasActiveSubscription($currentUser);

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
            'currentSubscription' => $currentSubscription,
            'hasActiveSubscription' => $hasActiveSubscription,
        ]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        abort_if($request->user()?->role === 'admin', 403);

        $usesSocialLogin = $request->user()?->hasSocialLogin() ?? false;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:160',
            ],
            'password' => [Rule::requiredIf(! $usesSocialLogin), 'nullable', 'confirmed', 'min:8', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'package' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:0'],
            'method' => ['nullable', 'string', 'max:80'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $settings = SiteSetting::values();
        $expectedAmount = (int) ($settings['subscription_price'] ?? SiteSetting::DEFAULT_SUBSCRIPTION_PRICE);
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

        $existingUser = $this->resolveCheckoutUser($request, $validated);
        $isRenewal = $existingUser !== null;
        $profilePhotoPath = $request->file('profile_photo')?->store('profile-photos', 'public');

        $gateway = app(MidtransSnapGateway::class);

        [$user, $payment, $subscription] = DB::transaction(function () use ($validated, $profilePhotoPath, $existingUser, $isRenewal, $gateway): array {
            $userData = [
                'name' => $validated['name'],
                'role' => 'customer',
                'status' => 'Active',
                'plan' => $existingUser?->plan ?: 'Free',
                'last_activity' => $isRenewal
                    ? 'Subscription payment started via Midtrans'
                    : 'Subscription registration started via Midtrans',
            ];

            if ($profilePhotoPath) {
                $userData['profile_photo_path'] = $profilePhotoPath;
            }

            if ($existingUser) {
                $user = $existingUser;
                $user->update($userData);
            } else {
                $user = User::create([
                    ...$userData,
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                ]);
            }

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package' => $validated['package'],
                'starts_at' => null,
                'expires_at' => null,
                'status' => 'Pending',
            ]);

            $payment = Payment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'customer_name' => $validated['name'],
                'customer_email' => $validated['email'],
                'customer_phone' => $validated['phone'] ?? null,
                'package' => $validated['package'],
                'amount' => $validated['amount'],
                'method' => 'Midtrans Snap '.$gateway->environmentLabel(),
                'status' => 'Pending',
                'reference' => 'HM-SUB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5)),
            ]);

            return [$user, $payment, $subscription];
        });

        try {
            $snapTransaction = $gateway->createTransaction($payment, $user, 'hm-premium-monthly');
        } catch (RuntimeException $exception) {
            $payment->update(['status' => 'Failed']);
            $subscription->update(['status' => 'Cancelled']);

            throw ValidationException::withMessages([
                'method' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            $payment->update(['status' => 'Failed']);
            $subscription->update(['status' => 'Cancelled']);

            throw ValidationException::withMessages([
                'method' => 'Midtrans checkout belum bisa dibuat. Periksa konfigurasi Server Key dan koneksi payment gateway.',
            ]);
        }

        $request->session()->put('pending_payment_reference', $payment->reference);

        return redirect()->away($snapTransaction['redirect_url']);
    }

    public function midtransFinish(Request $request, MidtransSnapGateway $gateway): RedirectResponse
    {
        $reference = $request->query('order_id') ?: $request->session()->pull('pending_payment_reference');

        if (! $reference) {
            return redirect()
                ->route('subcription')
                ->withErrors(['payment' => 'Reference pembayaran tidak ditemukan.']);
        }

        $payment = Payment::with('user', 'subscription', 'samplingRequest')->where('reference', $reference)->first();

        if (! $payment) {
            return redirect()
                ->route('subcription')
                ->withErrors(['payment' => 'Payment tidak ditemukan.']);
        }

        try {
            $status = $gateway->transactionStatus($payment->reference);
            $this->applyMidtransStatus($payment, $status, verifySignature: false);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $payment->refresh();

        if ($payment->status === 'Completed') {
            Auth::login($payment->user);
            $request->session()->regenerate();

            if ($payment->samplingRequest) {
                return redirect()
                    ->route('stylesampling', ['type' => 'sampling'])
                    ->with('payment_reference', $payment->reference)
                    ->with('success', 'Pembayaran sampling berhasil. Upload N27 sudah terbuka.');
            }

            return redirect()
                ->route('stylesampling', ['type' => 'style'])
                ->with('payment_reference', $payment->reference)
                ->with('success', 'Pembayaran berhasil. Subscription STY sudah aktif untuk download style.');
        }

        $pendingMessage = $payment->samplingRequest
            ? 'Pembayaran sampling sedang diproses oleh Midtrans. Upload N27 aktif otomatis setelah payment berhasil.'
            : 'Pembayaran sedang diproses oleh Midtrans. Akses STY aktif otomatis setelah payment berhasil.';

        return redirect()
            ->route($payment->samplingRequest ? 'stylesampling' : 'subcription', $payment->samplingRequest ? ['type' => 'sampling'] : [])
            ->with('payment_reference', $payment->reference)
            ->with('success', $pendingMessage);
    }

    public function midtransNotification(Request $request, MidtransSnapGateway $gateway): JsonResponse|Response
    {
        $payload = $request->all();

        if (! $gateway->notificationSignatureIsValid($payload)) {
            return response('Invalid signature.', 403);
        }

        $payment = Payment::with('user', 'subscription', 'samplingRequest')
            ->where('reference', $payload['order_id'] ?? null)
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        if (! $gateway->paymentAmountMatches($payment, $payload)) {
            return response()->json(['message' => 'Payment amount does not match.'], 422);
        }

        $this->applyMidtransStatus($payment, $payload);

        return response()->json(['message' => 'OK']);
    }

    private function resolveCheckoutUser(Request $request, array $validated): ?User
    {
        $existingUser = User::whereRaw('LOWER(email) = ?', [Str::lower($validated['email'])])->first();
        $currentUser = $request->user();

        if ($currentUser) {
            if (! $existingUser || ! $existingUser->is($currentUser)) {
                throw ValidationException::withMessages([
                    'email' => 'Gunakan email akun yang sedang login untuk memperpanjang subscription.',
                ]);
            }
        }

        if (! $existingUser) {
            return null;
        }

        if ($existingUser->role !== 'customer') {
            throw ValidationException::withMessages([
                'email' => 'Email ini tidak dapat dipakai untuk subscription customer.',
            ]);
        }

        if ($existingUser->status === 'Suspended') {
            throw ValidationException::withMessages([
                'email' => 'Akun ini sedang suspended. Hubungi admin sebelum memperpanjang subscription.',
            ]);
        }

        if (! ($currentUser?->hasSocialLogin() ?? false) && ! Hash::check($validated['password'] ?? '', $existingUser->password)) {
            throw ValidationException::withMessages([
                'password' => 'Email sudah terdaftar. Masukkan password akun tersebut untuk memperpanjang subscription.',
            ]);
        }

        return $existingUser;
    }

    private function applyMidtransStatus(Payment $payment, array $payload, bool $verifySignature = true): void
    {
        $gateway = app(MidtransSnapGateway::class);

        if ($verifySignature && ! $gateway->notificationSignatureIsValid($payload)) {
            return;
        }

        if (! $gateway->paymentAmountMatches($payment, $payload)) {
            return;
        }

        if ($gateway->completedStatus($payload)) {
            $this->completePayment($payment);

            return;
        }

        $failedStatus = $gateway->failedStatus($payload);

        if ($failedStatus) {
            DB::transaction(function () use ($payment, $failedStatus): void {
                $payment->update(['status' => $failedStatus]);

                if ($payment->subscription?->status === 'Pending') {
                    $payment->subscription->update(['status' => 'Cancelled']);
                }

                if ($payment->samplingRequest?->payment_status === SamplingRequest::PAYMENT_PENDING) {
                    $payment->samplingRequest->update([
                        'status' => SamplingRequest::STATUS_PENDING_PAYMENT,
                    ]);
                }
            });
        }
    }

    private function completePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $payment->refresh();

            if ($payment->status === 'Completed') {
                return;
            }

            $user = $payment->user()->lockForUpdate()->first();

            if (! $user) {
                return;
            }

            $samplingRequest = $payment->samplingRequest()->lockForUpdate()->first();

            if ($samplingRequest) {
                $payment->update(['status' => 'Completed']);

                $samplingRequest->update([
                    'payment_status' => SamplingRequest::PAYMENT_PAID,
                    'status' => $samplingRequest->has_n27_file
                        ? SamplingRequest::STATUS_N27_UPLOADED
                        : SamplingRequest::STATUS_PAID,
                ]);

                $user->update([
                    'last_activity' => 'Paid sampling '.$samplingRequest->order_reference.' via Midtrans',
                ]);

                return;
            }

            $subscription = $this->activateOrRenewSubscription(
                $user,
                $payment->package,
                $this->durationForPackage($payment->package),
                $payment->subscription,
            );

            $payment->update([
                'subscription_id' => $subscription->id,
                'status' => 'Completed',
            ]);

            $user->update([
                'plan' => $payment->package,
                'last_activity' => 'Subscription payment completed via Midtrans',
            ]);
        });
    }

    private function activateOrRenewSubscription(User $user, string $package, string $duration, ?Subscription $pendingSubscription = null): Subscription
    {
        $subscription = $user->activeSubscription()->first();
        $baseDate = $subscription?->expires_at?->isFuture()
            ? $subscription->expires_at->copy()
            : now();
        $expiresAt = $this->expiryForDuration($duration, $baseDate);

        if ($subscription) {
            $subscription->update([
                'package' => $package,
                'starts_at' => $subscription->expires_at?->isFuture()
                    ? ($subscription->starts_at ?? now())
                    : now(),
                'expires_at' => $expiresAt,
                'status' => 'Active',
            ]);

            if ($pendingSubscription && ! $pendingSubscription->is($subscription)) {
                $pendingSubscription->update(['status' => 'Cancelled']);
            }
        } else {
            $subscription = $pendingSubscription ?: new Subscription(['user_id' => $user->id]);

            $subscription->fill([
                'user_id' => $user->id,
                'package' => $package,
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'status' => 'Active',
            ])->save();
        }

        $user->subscriptions()
            ->where('id', '!=', $subscription->id)
            ->where('status', 'Active')
            ->update(['status' => 'Cancelled']);

        return $subscription->refresh();
    }

    private function hasActiveSubscription($user): bool
    {
        $subscription = $user?->activeSubscription()->first();

        return $subscription !== null
            && ($subscription->expires_at === null || $subscription->expires_at->isFuture());
    }

    private function expiryForDuration(string $duration, $baseDate = null)
    {
        $baseDate ??= now();

        return match ($duration) {
            '90 Days' => $baseDate->copy()->addDays(90),
            '1 Year' => $baseDate->copy()->addYear(),
            default => $baseDate->copy()->addDays(30),
        };
    }

    private function durationForPackage(string $package): string
    {
        return match ($package) {
            'Premium 90 Days' => '90 Days',
            'Premium Yearly' => '1 Year',
            default => '30 Days',
        };
    }
}
