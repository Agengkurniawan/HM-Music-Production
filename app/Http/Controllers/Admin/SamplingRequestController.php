<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SamplingRequest;
use App\Models\StyleSampling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SamplingRequestController extends Controller
{
    public function index()
    {
        $requests = SamplingRequest::with(['user', 'styleSampling'])
            ->latest()
            ->get();

        return view('layouts.admin.admin-sampling-requests', [
            'requests' => $requests,
        ]);
    }

    public function downloadN27(SamplingRequest $samplingRequest): StreamedResponse|RedirectResponse
    {
        if (! $samplingRequest->n27Exists()) {
            return back()->withErrors([
                'n27_file' => 'The N27 file is not available yet.',
            ]);
        }

        return Storage::disk('public')->download(
            $samplingRequest->n27_file_path,
            $samplingRequest->n27_download_name,
        );
    }

    public function markPaid(Request $request, SamplingRequest $samplingRequest): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:0'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = $samplingRequest->payment;
        $samplingPackOption = $samplingRequest->pack_name
            ? StyleSampling::samplingRequestOption($samplingRequest->pack_name)
            : null;
        $paymentPackage = $samplingPackOption['label'] ?? $samplingRequest->product_name;

        if ($payment) {
            $payment->update([
                'package' => $paymentPackage,
                'amount' => $validated['amount'],
                'status' => 'Completed',
                'method' => 'Admin Confirmed Sampling Payment',
            ]);
        } else {
            $user = $samplingRequest->user;

            $payment = Payment::create([
                'user_id' => $user?->id,
                'subscription_id' => null,
                'customer_name' => $user?->name ?? 'Sampling Customer',
                'customer_email' => $user?->email ?? 'unknown@example.com',
                'customer_phone' => null,
                'package' => $paymentPackage,
                'amount' => $validated['amount'],
                'method' => 'Admin Confirmed Sampling Payment',
                'status' => 'Completed',
                'reference' => $this->makeSamplingPaymentReference(),
            ]);
        }

        $samplingRequest->update([
            'payment_id' => $payment->id,
            'amount' => $validated['amount'],
            'payment_status' => SamplingRequest::PAYMENT_PAID,
            'status' => $samplingRequest->has_n27_file
                ? SamplingRequest::STATUS_N27_UPLOADED
                : SamplingRequest::STATUS_PAID,
            'admin_notes' => $validated['admin_notes'] ?: $samplingRequest->admin_notes,
        ]);

        $samplingRequest->user?->update([
            'last_activity' => 'Sampling payment confirmed for '.$samplingRequest->order_reference,
        ]);

        return back()->with('success', 'Sampling payment confirmed. Customer can upload the N27 file now.');
    }

    public function markProcessing(SamplingRequest $samplingRequest): RedirectResponse
    {
        if ($samplingRequest->payment_status !== SamplingRequest::PAYMENT_PAID) {
            return back()->withErrors([
                'status' => 'Payment must be completed before processing this N27 request.',
            ]);
        }

        if (! $samplingRequest->has_n27_file) {
            return back()->withErrors([
                'status' => 'N27 file must be uploaded before processing can start.',
            ]);
        }

        $samplingRequest->update([
            'status' => SamplingRequest::STATUS_PROCESSING,
        ]);

        return back()->with('success', 'Sampling request marked as processing.');
    }

    public function saveDelivery(Request $request, SamplingRequest $samplingRequest): RedirectResponse
    {
        if ($samplingRequest->payment_status !== SamplingRequest::PAYMENT_PAID) {
            return back()->withErrors([
                'delivery' => 'Payment must be completed before sending the completed sampling link.',
            ]);
        }

        if (! $samplingRequest->has_n27_file) {
            return back()->withErrors([
                'delivery' => 'Upload and process the customer N27 file before sending a Google Drive link.',
            ]);
        }

        $validated = $request->validate([
            'google_drive_link' => ['required', 'url', 'max:500'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in([
                SamplingRequest::STATUS_READY,
                SamplingRequest::STATUS_COMPLETED,
            ])],
        ]);

        $samplingRequest->update([
            'google_drive_link' => $validated['google_drive_link'],
            'delivery_notes' => $validated['delivery_notes'] ?? null,
            'status' => $validated['status'],
            'delivered_at' => $samplingRequest->delivered_at ?: now(),
            'completed_at' => $validated['status'] === SamplingRequest::STATUS_COMPLETED
                ? now()
                : $samplingRequest->completed_at,
        ]);

        $samplingRequest->user?->update([
            'last_activity' => 'Sampling file ready for '.$samplingRequest->order_reference,
        ]);

        return back()->with('success', 'Google Drive sampling link saved and sent to the customer page.');
    }

    private function makeSamplingPaymentReference(): string
    {
        do {
            $reference = 'N27-PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
        } while (Payment::where('reference', $reference)->exists());

        return $reference;
    }
}
