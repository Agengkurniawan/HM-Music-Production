<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DownloadSale;
use App\Models\SamplingRequest;
use App\Models\StyleSampling;
use App\Services\AI\HybridStyleSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StyleSamplingController extends Controller
{
    private const AI_SEARCH_SESSION_KEY = 'stylesampling_ai_search_result';

    public function index(Request $request): View
    {
        $state = $request->session()->get(self::AI_SEARCH_SESSION_KEY);

        if (is_array($state)) {
            $category = is_string($state['category'] ?? null) ? $state['category'] : null;
            $pack = is_string($state['pack'] ?? null) ? $state['pack'] : null;

            return $this->renderCatalog(
                $request,
                $this->restoreAiResults($state['results'] ?? [], $category, $pack),
                is_string($state['query'] ?? null) ? $state['query'] : null,
                is_string($state['error'] ?? null) ? $state['error'] : null,
                is_string($state['empty_message'] ?? null) ? $state['empty_message'] : null,
                is_array($state['understanding'] ?? null) ? $state['understanding'] : null,
                $category,
                $pack,
            );
        }

        return $this->renderCatalog($request);
    }

    public function aiSearch(Request $request, HybridStyleSearchService $search): RedirectResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:300'],
            'category' => ['nullable', 'string', 'in:'.implode(',', StyleSampling::CUSTOMER_STYLE_CATEGORIES)],
            'pack' => ['nullable', 'string', 'in:'.implode(',', StyleSampling::PACKS)],
        ]);
        $category = filled($validated['category'] ?? null) ? (string) $validated['category'] : null;
        $pack = filled($validated['pack'] ?? null) ? (string) $validated['pack'] : null;

        try {
            $result = $search->search((string) $validated['query'], $category, $pack);

            $this->flashAiSearch(
                $request,
                (string) $validated['query'],
                $result->styles,
                $result->styles->isEmpty() ? $result->emptyMessage : null,
                $result->understanding,
                $category,
                $pack,
            );

            return redirect()->route('stylesampling');
        } catch (\Throwable $exception) {
            report($exception);

            $this->flashAiSearch($request, (string) $validated['query'], category: $category, pack: $pack, error: 'AI Smart Search sedang tidak tersedia. Silakan gunakan pencarian biasa.');

            return redirect()->route('stylesampling');
        }
    }

    /** @param Collection<int, StyleSampling> $styles @param array<string, mixed>|null $understanding */
    private function flashAiSearch(
        Request $request,
        string $query,
        ?Collection $styles = null,
        ?string $emptyMessage = null,
        ?array $understanding = null,
        ?string $category = null,
        ?string $pack = null,
        ?string $error = null,
    ): void {
        $request->session()->flash(self::AI_SEARCH_SESSION_KEY, [
            'query' => trim($query),
            'results' => ($styles ?? collect())->map(fn (StyleSampling $style): array => [
                'id' => $style->getKey(),
                'similarity' => is_numeric($style->ai_similarity) ? (float) $style->ai_similarity : null,
                'match_label' => is_string($style->ai_match_label) ? $style->ai_match_label : null,
            ])->values()->all(),
            'empty_message' => $emptyMessage,
            'understanding' => collect($understanding ?? [])->only([
                'intent', 'artist', 'song_title', 'context', 'semantic_query', 'original_query', 'fallback',
                'identification_confidence', 'identification_source',
            ])->all(),
            'category' => $category,
            'pack' => $pack,
            'error' => $error,
        ]);
    }

    /** @param mixed $resultRows @return Collection<int, StyleSampling> */
    private function restoreAiResults(mixed $resultRows, ?string $category = null, ?string $pack = null): Collection
    {
        if (! is_array($resultRows)) {
            return collect();
        }

        $rows = collect($resultRows)
            ->filter(fn (mixed $row): bool => is_array($row) && is_numeric($row['id'] ?? null))
            ->values();
        $ids = $rows->pluck('id')->map(fn (mixed $id): int => (int) $id)->unique()->values();

        $query = StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->whereIn('category', StyleSampling::CUSTOMER_STYLE_CATEGORIES)
            ->whereIn('id', $ids);

        if ($category !== null && in_array($category, StyleSampling::CUSTOMER_STYLE_CATEGORIES, true)) {
            $query->where('category', $category);
        }

        if ($pack !== null && in_array($pack, StyleSampling::PACKS, true)) {
            $packCategories = StyleSampling::styleCategoriesForPack($pack);

            if ($packCategories !== []) {
                $query->whereIn('category', $packCategories);
            } else {
                $query->where('pack', $pack);
            }
        }

        $stylesById = $query->get()
            ->keyBy(fn (StyleSampling $style): int => (int) $style->getKey());

        return $rows->map(function (array $row) use ($stylesById): ?StyleSampling {
            $style = $stylesById->get((int) $row['id']);

            if (! $style) {
                return null;
            }

            $style->setAttribute('ai_similarity', is_numeric($row['similarity'] ?? null)
                ? (float) $row['similarity']
                : null);
            $style->setAttribute('ai_match_label', is_string($row['match_label'] ?? null)
                ? $row['match_label']
                : null);

            return $style;
        })->filter()->unique('id')->values();
    }

    /** @param Collection<int, StyleSampling>|null $aiResults */
    private function renderCatalog(
        Request $request,
        ?Collection $aiResults = null,
        ?string $aiQuery = null,
        ?string $aiError = null,
        ?string $aiEmptyMessage = null,
        ?array $aiUnderstanding = null,
        ?string $aiCategory = null,
        ?string $aiPack = null,
    ): View {
        $assetType = $aiResults !== null ? 'style' : $request->query('type', 'style');

        if (! in_array($assetType, ['style', 'sampling'], true)) {
            $assetType = 'style';
        }

        $stylePacks = collect(StyleSampling::PACKS);
        $samplingPackOptions = StyleSampling::samplingRequestOptions();
        $samplingPackNames = collect(array_keys($samplingPackOptions));
        $stylePackNames = $stylePacks;
        $styleCategories = collect(StyleSampling::CUSTOMER_STYLE_CATEGORIES);

        $selectedCategory = $assetType === 'style'
            ? ($aiResults !== null ? $aiCategory : $request->query('category'))
            : null;
        $selectedPack = $aiResults !== null ? $aiPack : $request->query('pack');

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

        $samplingRequests = SamplingRequest::with(['styleSampling', 'payment'])
            ->where('user_id', Auth::id())
            ->latest();

        if ($assetType === 'sampling' && $selectedPack) {
            $samplingRequests->where('pack_name', (string) $selectedPack);
        }

        return view('layouts.customers.stylesampling', [
            'styles' => $assetType === 'style' ? ($aiResults ?? $query->get()) : collect(),
            'samplingRequests' => $samplingRequests->get(),
            'styleCategories' => $styleCategories,
            'stylePacks' => $stylePacks,
            'samplingPackOptions' => $samplingPackOptions,
            'selectedCategory' => $selectedCategory,
            'selectedPack' => $selectedPack,
            'assetType' => $assetType,
            'isSubscribed' => $this->hasActiveSubscription($request->user()),
            'aiSearchEnabled' => (bool) config('services.ai_search.enabled'),
            'aiSearchPerformed' => $aiResults !== null,
            'aiQuery' => $aiQuery,
            'aiError' => $aiError,
            'aiEmptyMessage' => $aiEmptyMessage,
            'aiUnderstanding' => $aiUnderstanding,
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
                ], 'subscriptionAccess');
        }

        $path = $styleSampling->style_file_path;
        $filename = $styleSampling->display_style_name;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return back()->withErrors([
                'download' => 'The requested download file is not available yet.',
            ], 'styleDownload');
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
