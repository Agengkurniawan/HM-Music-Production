<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('layouts.admin.admin-setting', [
            'settings' => SiteSetting::values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'banner_title' => ['required', 'string', 'max:160'],
            'homepage_banner' => ['nullable', 'image', 'max:5120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'subscription_price' => ['required', 'string', 'max:40', 'regex:/[0-9]/'],
            'plan_duration' => ['required', Rule::in(['30 Days', '90 Days', '1 Year'])],
            'payment_gateway' => ['required', Rule::in(['Midtrans', 'Xendit', 'Manual Bank Transfer'])],
            'merchant_key' => ['nullable', 'string', 'max:180'],
            'instagram' => ['nullable', 'url', 'max:180'],
            'youtube' => ['nullable', 'url', 'max:180'],
            'smtp_host' => ['nullable', 'string', 'max:120'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['required', Rule::in(['TLS', 'SSL', 'None'])],
        ]);

        $settings = SiteSetting::values();
        $price = (int) preg_replace('/\D+/', '', $validated['subscription_price']);

        $updates = [
            'banner_title' => $validated['banner_title'],
            'subscription_price' => (string) max($price, 0),
            'plan_duration' => $validated['plan_duration'],
            'payment_gateway' => $validated['payment_gateway'],
            'merchant_key' => $validated['merchant_key'] ?? '',
            'instagram' => $validated['instagram'] ?? '',
            'youtube' => $validated['youtube'] ?? '',
            'smtp_host' => $validated['smtp_host'] ?? '',
            'smtp_port' => (string) ($validated['smtp_port'] ?? ''),
            'smtp_encryption' => $validated['smtp_encryption'],
        ];

        foreach (['homepage_banner', 'logo'] as $fileKey) {
            if ($request->hasFile($fileKey)) {
                $oldPath = $settings[$fileKey] ?? null;

                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }

                $updates[$fileKey] = $request->file($fileKey)->store('settings', 'public');
            }
        }

        SiteSetting::setMany($updates);

        return back()->with('success', 'Settings saved successfully.');
    }
}
