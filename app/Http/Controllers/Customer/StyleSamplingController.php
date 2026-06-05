<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DownloadSale;
use App\Models\SamplingRequest;
use App\Models\StyleSampling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StyleSamplingController extends Controller
{
    public function index(Request $request): View
    {
        $assetType = $request->query('type', 'style');

        if (! in_array($assetType, ['style', 'sampling'], true)) {
            $assetType = 'style';
        }

        $stylePacks = collect(StyleSampling::PACKS);
        $samplingPackOptions = StyleSampling::samplingRequestOptions();
        $samplingPackNames = collect(array_keys($samplingPackOptions));
        $stylePackNames = $stylePacks;
        $styleCategories = collect(StyleSampling::CUSTOMER_STYLE_CATEGORIES);

        $selectedCategory = $assetType === 'style' ? $request->query('category') : null;
        $selectedPack = $request->query('pack');

        if ($selectedCategory && ! $styleCategories->contains($selectedCategory)) {
            $selectedCategory = null;
        }

        if ($assetType === 'style' && $selectedPack && ! $stylePackNames->contains($selectedPack)) {
            $selectedPack = null;
        }

        if ($assetType === 'sampling' && $selectedPack && ! $samplingPackNames->contains($selectedPack)) {
            $selectedPack = null;
        }

        $query = StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->whereIn('category', StyleSampling::CUSTOMER_STYLE_CATEGORIES)
            ->latest();

        if ($selectedCategory) {
            $query->where('category', (string) $selectedCategory);
        }

        if ($assetType === 'style' && $selectedPack) {
            $packCategories = StyleSampling::styleCategoriesForPack((string) $selectedPack);

            if ($packCategories !== []) {
                $query->whereIn('category', $packCategories);
            } else {
                $query->where('pack', (string) $selectedPack);
            }
        }

        $samplingRequests = SamplingRequest::with('styleSampling')
            ->where('user_id', Auth::id())
            ->latest();

        if ($assetType === 'sampling' && $selectedPack) {
            $samplingRequests->where('pack_name', (string) $selectedPack);
        }

        return view('layouts.customers.stylesampling', [
            'styles' => $assetType === 'style' ? $query->get() : collect(),
            'samplingRequests' => $samplingRequests->get(),
            'styleCategories' => $styleCategories,
            'stylePacks' => $stylePacks,
            'samplingPackOptions' => $samplingPackOptions,
            'selectedCategory' => $selectedCategory,
            'selectedPack' => $selectedPack,
            'assetType' => $assetType,
            'isSubscribed' => $this->hasActiveSubscription($request->user()),
        ]);
    }

    public function downloadStyle(StyleSampling $styleSampling): StreamedResponse|RedirectResponse
    {
        abort_unless($styleSampling->status === 'Published', 404);

        if (! $this->hasActiveSubscription(Auth::user())) {
            return redirect()
                ->route('subcription')
                ->withErrors([
                    'subscription' => 'Subscription premium diperlukan untuk download STY. Sampling voice pack tetap dibeli terpisah sesuai pack yang dipakai style.',
                ]);
        }

        $path = $styleSampling->style_file_path;
        $filename = $styleSampling->display_style_name;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return back()->withErrors([
                'download' => 'The requested download file is not available yet.',
            ]);
        }

        $this->recordDownload($styleSampling, $filename);

        return Storage::disk('public')->download($path, $filename);
    }

    private function recordDownload(StyleSampling $styleSampling, string $filename): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        DownloadSale::create([
            'user_id' => $user->id,
            'style_sampling_id' => $styleSampling->id,
            'download_type' => 'style',
            'file_name' => $filename,
            'style_name' => $styleSampling->name,
            'status' => 'Completed',
            'amount' => 0,
            'downloaded_at' => now(),
        ]);

        $styleSampling->increment('downloads_count');
    }

    private function hasActiveSubscription($user): bool
    {
        $subscription = $user?->activeSubscription()->first();

        return $subscription !== null
            && ($subscription->expires_at === null || $subscription->expires_at->isFuture());
    }
}
