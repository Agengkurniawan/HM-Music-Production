<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadSale;
use Illuminate\View\View;

class DownloadSalesController extends Controller
{
    public function index(): View
    {
        return view('layouts.admin.admin-downloadsales', [
            'downloads' => DownloadSale::with(['user', 'styleSampling'])
                ->where('download_type', 'style')
                ->latest('downloaded_at')
                ->get(),
        ]);
    }
}
