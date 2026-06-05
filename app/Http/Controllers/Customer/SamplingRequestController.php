<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SamplingRequest;
use App\Models\StyleSampling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SamplingRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
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
            ).' Modal pembayaran Midtrans akan terbuka untuk melanjutkan checkout.');
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

        DB::transaction(function () use ($request, $samplingRequest): void {
            $user = $request->user();
            $samplingPackOption = $samplingRequest->pack_name
                ? StyleSampling::samplingRequestOption($samplingRequest->pack_name)
                : null;

            $payment = Payment::create([
                'user_id' => $user?->id,
                'subscription_id' => null,
                'customer_name' => $user?->name ?? 'Sampling Customer',
                'customer_email' => $user?->email ?? 'unknown@example.com',
                'customer_phone' => null,
                'package' => $samplingPackOption['label'] ?? $samplingRequest->product_name,
                'amount' => $samplingRequest->amount,
                'method' => 'Midtrans Sampling Checkout',
                'status' => 'Completed',
                'reference' => $this->makePaymentReference(),
            ]);

            $samplingRequest->update([
                'payment_id' => $payment->id,
                'payment_status' => SamplingRequest::PAYMENT_PAID,
                'status' => $samplingRequest->has_n27_file
                    ? SamplingRequest::STATUS_N27_UPLOADED
                    : SamplingRequest::STATUS_PAID,
            ]);

            $user?->update([
                'last_activity' => 'Paid sampling '.$samplingRequest->order_reference,
            ]);
        });

        return redirect()
            ->route('stylesampling', ['type' => 'sampling'])
            ->with('success', 'Pembayaran sampling pack via Midtrans berhasil. Upload N27 sudah terbuka agar admin bisa connect voice kit ke keyboard.');
    }

    public function uploadN27(Request $request, SamplingRequest $samplingRequest): RedirectResponse
    {
        abort_unless($samplingRequest->user_id === Auth::id(), 403);

        if (! $samplingRequest->can_upload_n27) {
            throw ValidationException::withMessages([
                'n27_file' => 'The N27 file can only be uploaded after the order is paid and before final delivery.',
            ]);
        }

        $request->validate([
            'n27_file' => ['required', 'file', 'max:102400'],
        ]);

        $file = $request->file('n27_file');

        if (strtolower($file->getClientOriginalExtension()) !== 'n27') {
            throw ValidationException::withMessages([
                'n27_file' => 'Please upload a valid .n27 file.',
            ]);
        }

        $samplingRequest->update([
            'n27_file_path' => $file->store('sampling-requests/n27', 'public'),
            'n27_original_name' => $file->getClientOriginalName(),
            'n27_uploaded_at' => now(),
            'status' => SamplingRequest::STATUS_N27_UPLOADED,
        ]);

        $samplingRequest->user?->update([
            'last_activity' => 'Uploaded N27 file for '.$samplingRequest->order_reference,
        ]);

        return redirect()
            ->route('stylesampling', ['type' => 'sampling'])
            ->with('success', 'N27 file uploaded. The admin can now process it in Yamaha Expansion Manager.');
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
