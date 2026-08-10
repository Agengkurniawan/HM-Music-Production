<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'subscription_price' => ['required', 'string', 'max:40', 'regex:/[0-9]/'],
            'plan_duration' => ['required', Rule::in(['30 Days', '90 Days', '1 Year'])],
            'midtrans_server_key' => ['nullable', 'string', 'max:180'],
            'midtrans_client_key' => ['nullable', 'string', 'max:180'],
            'midtrans_is_production' => ['required', Rule::in(['0', '1'])],
        ]);

        $price = (int) preg_replace('/\D+/', '', $validated['subscription_price']);

        if ($price < SiteSetting::DEFAULT_SUBSCRIPTION_PRICE) {
            throw ValidationException::withMessages([
                'subscription_price' => 'Harga subscription minimal Rp '.number_format(SiteSetting::DEFAULT_SUBSCRIPTION_PRICE, 0, ',', '.').'.',
            ]);
        }

        $updates = [
            'subscription_price' => (string) $price,
            'plan_duration' => $validated['plan_duration'],
            'midtrans_server_key' => $validated['midtrans_server_key'] ?? '',
            'midtrans_client_key' => $validated['midtrans_client_key'] ?? '',
            'midtrans_is_production' => $validated['midtrans_is_production'],
        ];

        SiteSetting::setMany($updates);

        return back()->with('success', 'Setting berhasil disimpan.');
    }
}
