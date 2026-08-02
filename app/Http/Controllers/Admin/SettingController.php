<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'subscription_price' => ['required', 'string', 'max:40', 'regex:/[0-9]/'],
            'plan_duration' => ['required', Rule::in(['30 Days', '90 Days', '1 Year'])],
            'merchant_key' => ['nullable', 'string', 'max:180'],
            'midtrans_client_key' => ['nullable', 'string', 'max:180'],
            'midtrans_is_production' => ['required', Rule::in(['0', '1'])],
        ]);

        $price = (int) preg_replace('/\D+/', '', $validated['subscription_price']);

        $updates = [
            'subscription_price' => (string) max($price, 0),
            'plan_duration' => $validated['plan_duration'],
            'merchant_key' => $validated['merchant_key'] ?? '',
            'midtrans_client_key' => $validated['midtrans_client_key'] ?? '',
            'midtrans_is_production' => $validated['midtrans_is_production'],
        ];

        SiteSetting::setMany($updates);

        return back()->with('success', 'Setting berhasil disimpan.');
    }
}
