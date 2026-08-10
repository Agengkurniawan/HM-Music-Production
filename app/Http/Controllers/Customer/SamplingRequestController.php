<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SamplingRequest;
use App\Models\StyleSampling;
use App\Services\MidtransSnapGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SamplingRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('pack_name')) {
            $request->merge([
                'pack_name' => StyleSampling::normalizeSamplingPackName((string) $request->input('pack_name')),
            ]);
        }

        $validated = $request->validateWithBag('samplingRequest', [
            'pack_name' => ['required', Rule::in(StyleSampling::samplingRequestPackNames())],
            'keyboard_storage_mb' => ['nullable', 'integer', 'min:1', 'max:4096'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $pack = StyleSampling::samplingRequestOption($validated['pack_name']);
        $price = (int) ($pack['price'] ?? StyleSampling::SAMPLING_REQUEST_PRICE);

        $samplingRequest = SamplingRequest::create([
            'user_id' => Auth::id(),
            'order_reference' => $this->makeSamplingReference(),
            'product_name' => $pack['label'],
            'pack_name' => $validated['pack_name'],
            'keyboard_storage_mb' => $validated['keyboard_storage_mb'] ?? null,
            'customer_notes' => $validated['customer_notes'] ?? null,
            'amount' => $price,
            'payment_status' => SamplingRequest::PAYMENT_PENDING,
            'status' => SamplingRequest::STATUS_PENDING_PAYMENT,
            'admin_notes' => StyleSampling::samplingRequestAdvice(
                $validated['pack_name'],
                $validated['keyboard_storage_mb'] ?? null,
            ),
        ]);

        $request->user()?->update([
            'last_activity' => 'Started sampling pack checkout '.$samplingRequest->order_reference,
        ]);

        return redirect()
            ->route('stylesampling', ['type' => 'sampling'])
            ->with('sampling_payment_request_id', $samplingRequest->id)
            ->with('success', 'Checkout sampling pack berhasil dibuat. '.StyleSampling::samplingRequestAdvice(
                $validated['pack_name'],
                $validated['keyboard_storage_mb'] ?? null,
            ).' Ringkasan pembayaran akan terbuka untuk melanjutkan pesanan.');
    }

    public function pay(Request $request, SamplingRequest $samplingRequest): RedirectResponse
    {
        abort_unless($samplingRequest->user_id === Auth::id(), 403);

        if ($samplingRequest->payment_status === SamplingRequest::PAYMENT_PAID) {
            return redirect()
                ->route('stylesampling', ['type' => 'sampling'])
                ->with('success', 'Pembayaran sampling sudah berhasil. Silakan upload file N27.');
        }

        if ($samplingRequest->amount <= 0) {
            $samplingRequest->forceFill([
                'amount' => StyleSampling::SAMPLING_REQUEST_PRICE,
            ])->save();
        }

        $gateway = app(MidtransSnapGateway::class);

        $payment = DB::transaction(function () use ($request, $samplingRequest, $gateway): Payment {
            $user = $request->user();
            $samplingPackOption = $samplingRequest->pack_name
                ? StyleSampling::samplingRequestOption($samplingRequest->pack_name)
                : null;

            if ($samplingRequest->payment?->status === 'Pending') {
                $samplingRequest->payment->update(['status' => 'Cancelled']);
            }

            $payment = Payment::create([
                'user_id' => $user?->id,
                'subscription_id' => null,
                'customer_name' => $user?->name ?? 'Sampling Customer',
                'customer_email' => $user?->email ?? 'unknown@example.com',
                'customer_phone' => null,
                'package' => $samplingPackOption['label'] ?? $samplingRequest->product_name,
                'amount' => $samplingRequest->amount,
                'method' => 'Midtrans Sampling '.$gateway->environmentLabel(),
                'status' => 'Pending',
                'reference' => $this->makePaymentReference(),
            ]);

            $samplingRequest->update([
                'payment_id' => $payment->id,
                'payment_status' => SamplingRequest::PAYMENT_PENDING,
                'status' => SamplingRequest::STATUS_PENDING_PAYMENT,
            ]);

            $user?->update([
                'last_activity' => 'Started sampling payment '.$samplingRequest->order_reference,
            ]);

            return $payment;
        });

        try {
            $snapTransaction = $gateway->createTransaction(
                $payment,
                $request->user(),
                'hm-sampling-pack',
            );
        } catch (RuntimeException $exception) {
            $payment->update(['status' => 'Failed']);

            throw ValidationException::withMessages([
                'payment' => $exception->getMessage(),
            ])->errorBag('samplingPayment');
        } catch (\Throwable $exception) {
            report($exception);

            $payment->update(['status' => 'Failed']);

            throw ValidationException::withMessages([
                'payment' => 'Pembayaran belum dapat dibuka. Silakan coba kembali atau hubungi admin.',
            ])->errorBag('samplingPayment');
        }

        $request->session()->put('pending_payment_reference', $payment->reference);

        return redirect()->away($snapTransaction['redirect_url']);
    }

    public function syncPayment(SamplingRequest $samplingRequest, MidtransSnapGateway $gateway): RedirectResponse
    {
        abort_unless($samplingRequest->user_id === Auth::id(), 403);

        if ($samplingRequest->payment_status === SamplingRequest::PAYMENT_PAID) {
            return redirect()
                ->route('stylesampling', ['type' => 'sampling'])
                ->with('success', 'Pembayaran sampling sudah berhasil. Form upload N27 sudah terbuka.');
        }

        $payment = $samplingRequest->payment;

        if (! $payment) {
            return redirect()
                ->route('stylesampling', ['type' => 'sampling'])
                ->withErrors([
                    'payment' => 'Belum ada transaksi untuk pesanan ini. Silakan lanjutkan pembayaran terlebih dahulu.',
                ], 'samplingPayment');
        }

        try {
            $payload = $gateway->transactionStatus($payment->reference);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'payment' => $exception->getMessage(),
            ])->errorBag('samplingPayment');
        } catch (\Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'payment' => 'Status pembayaran belum dapat diperiksa. Silakan coba beberapa saat lagi.',
            ])->errorBag('samplingPayment');
        }

        if ($gateway->completedStatus($payload) && ! $gateway->paymentAmountMatches($payment, $payload)) {
            return redirect()
                ->route('stylesampling', ['type' => 'sampling'])
                ->withErrors([
                    'payment' => 'Nominal pembayaran tidak sesuai dengan invoice sampling. Hubungi admin sebelum upload N27.',
                ], 'samplingPayment');
        }

        if ($gateway->completedStatus($payload)) {
            $this->completeSamplingPayment($samplingRequest, $payment);

            return redirect()
                ->route('stylesampling', ['type' => 'sampling'])
                ->with('success', 'Pembayaran sampling berhasil dikonfirmasi. Silakan upload file N27.');
        }

        $failedStatus = $gateway->failedStatus($payload);

        if ($failedStatus) {
            DB::transaction(function () use ($samplingRequest, $payment, $failedStatus): void {
                $payment->update(['status' => $failedStatus]);

                $samplingRequest->update([
                    'payment_status' => SamplingRequest::PAYMENT_PENDING,
                    'status' => SamplingRequest::STATUS_PENDING_PAYMENT,
                ]);
            });

            return redirect()
                ->route('stylesampling', ['type' => 'sampling'])
                ->withErrors([
                    'payment' => "Pembayaran berstatus {$failedStatus}. Silakan buat pembayaran baru.",
                ], 'samplingPayment');
        }

        return redirect()
            ->route('stylesampling', ['type' => 'sampling'])
            ->with('success', 'Pembayaran masih diproses. Setelah berhasil, klik Cek Status Pembayaran lagi agar upload N27 terbuka.');
    }

    public function uploadN27(Request $request, SamplingRequest $samplingRequest): RedirectResponse
    {
        abort_unless($samplingRequest->user_id === Auth::id(), 403);
        $request->session()->flash('sampling_upload_request_id', $samplingRequest->id);

        if (! $samplingRequest->can_upload_n27) {
            throw ValidationException::withMessages([
                'n27_file' => 'The N27 file can only be uploaded after the order is paid and before final delivery.',
            ])->errorBag('samplingUpload');
        }

        $request->validateWithBag('samplingUpload', [
            'n27_file' => ['required', 'file', 'max:102400'],
        ]);

        $file = $request->file('n27_file');

        if (strtolower($file->getClientOriginalExtension()) !== 'n27') {
            throw ValidationException::withMessages([
                'n27_file' => 'Please upload a valid .n27 file.',
            ])->errorBag('samplingUpload');
        }

        $oldPath = $samplingRequest->n27_file_path;
        $newPath = $file->store('sampling-requests/n27', 'public');

        try {
            $samplingRequest->update([
                'n27_file_path' => $newPath,
                'n27_original_name' => $file->getClientOriginalName(),
                'n27_uploaded_at' => now(),
                'status' => SamplingRequest::STATUS_N27_UPLOADED,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);

            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $samplingRequest->user?->update([
            'last_activity' => 'Uploaded N27 file for '.$samplingRequest->order_reference,
        ]);

        return redirect()
            ->route('stylesampling', ['type' => 'sampling'])
            ->with('success', 'N27 file uploaded. The admin can now process it in Yamaha Expansion Manager.');
    }

    private function completeSamplingPayment(SamplingRequest $samplingRequest, Payment $payment): void
    {
        DB::transaction(function () use ($samplingRequest, $payment): void {
            $payment->update(['status' => 'Completed']);

            $samplingRequest->update([
                'payment_id' => $payment->id,
                'payment_status' => SamplingRequest::PAYMENT_PAID,
                'status' => $samplingRequest->has_n27_file
                    ? SamplingRequest::STATUS_N27_UPLOADED
                    : SamplingRequest::STATUS_PAID,
            ]);

            $samplingRequest->user?->update([
                'last_activity' => 'Paid sampling '.$samplingRequest->order_reference.' via Midtrans',
            ]);
        });
    }

    private function makeSamplingReference(): string
    {
        do {
            $reference = 'N27-REQ-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (SamplingRequest::where('order_reference', $reference)->exists());

        return $reference;
    }

    private function makePaymentReference(): string
    {
        do {
            $reference = 'N27-PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
        } while (Payment::where('reference', $reference)->exists());

        return $reference;
    }
}
