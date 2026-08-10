<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\StyleCatalogUpdated;
use App\Models\StyleSampling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StyleSamplingController extends Controller
{
    public function index(): View
    {
        return view('layouts.admin.admin-stylesampling', [
            'styles' => StyleSampling::whereIn('category', StyleSampling::CATEGORIES)->latest()->get(),
            'categories' => StyleSampling::CATEGORIES,
            'packs' => StyleSampling::PACKS,
            'statuses' => StyleSampling::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedPacks = array_merge(StyleSampling::PACKS, array_keys(StyleSampling::LEGACY_PACK_ALIASES));

        $validated = $request->validateWithBag('uploadStyle', [
            'name' => ['required', 'string', 'max:140'],
            'category' => ['required', Rule::in(StyleSampling::CATEGORIES)],
            'pack' => ['required', Rule::in($allowedPacks)],
            'description' => ['nullable', 'string', 'max:1000'],
            'style_file' => ['required', 'file', 'max:51200'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $styleFile = $request->file('style_file');

        if (! $styleFile instanceof UploadedFile || ! $this->hasAllowedStyleExtension($styleFile)) {
            throw ValidationException::withMessages([
                'style_file' => 'The style file must be a .sty, .prs, or .sst file.',
            ])->errorBag('uploadStyle');
        }

        $styleSampling = new StyleSampling([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'pack' => StyleSampling::normalizeSamplingPackName($validated['pack']),
            'access' => 'Premium',
            'status' => $request->boolean('published') ? 'Published' : 'Draft',
            'description' => $validated['description'] ?? null,
            'file_size' => $this->formatFileSize($styleFile->getSize()),
        ]);

        $styleSampling->style_file_path = $styleFile->store('styles/style-files', 'public');
        $styleSampling->style_filename = $styleFile->getClientOriginalName();

        if ($request->hasFile('cover_image')) {
            $styleSampling->cover_image_path = $request->file('cover_image')->store('styles/covers', 'public');
        }

        $styleSampling->save();

        $this->notifyCustomersAboutStyle($styleSampling, 'added');

        return back()->with('success', 'Style uploaded successfully.');
    }

    public function update(Request $request, StyleSampling $styleSampling): RedirectResponse
    {
        $request->session()->flash('admin_style_edit_id', $styleSampling->id);
        $validated = $request->validateWithBag('editStyle', [
            'name' => ['required', 'string', 'max:140'],
            'category' => ['required', Rule::in(StyleSampling::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $styleSampling->update($validated);

        $this->notifyCustomersAboutStyle($styleSampling, 'updated');

        return back()->with('success', 'Style sampling updated successfully.');
    }

    public function activate(StyleSampling $styleSampling): RedirectResponse
    {
        $missing = [];

        if (! $styleSampling->pack) {
            $missing[] = 'expansion pack';
        }

        if (! $styleSampling->has_style_file) {
            $missing[] = 'STY style file';
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'status' => 'Cannot publish yet. Missing: '.implode(', ', $missing).'.',
            ])->errorBag('styleAction');
        }

        $wasPublished = $styleSampling->status === 'Published';
        $styleSampling->update(['status' => 'Published']);

        if (! $wasPublished) {
            $this->notifyCustomersAboutStyle($styleSampling, 'added');
        }

        return back()->with('success', 'Style sampling activated.');
    }

    public function deactivate(StyleSampling $styleSampling): RedirectResponse
    {
        $styleSampling->update(['status' => 'Draft']);

        return back()->with('success', 'Style sampling moved to draft.');
    }

    public function destroy(StyleSampling $styleSampling): RedirectResponse
    {
        $storedFiles = array_filter([
            $styleSampling->style_file_path,
            $styleSampling->audio_path,
            $styleSampling->preview_path,
            $styleSampling->cover_image_path,
        ]);

        $styleSampling->delete();

        if ($storedFiles !== []) {
            Storage::disk('public')->delete($storedFiles);
        }

        return back()->with('success', 'Style sampling deleted successfully.');
    }

    private function hasAllowedStyleExtension(UploadedFile $file): bool
    {
        return in_array(Str::lower($file->getClientOriginalExtension()), ['sty', 'prs', 'sst'], true);
    }

    private function formatFileSize(int|false $bytes): string
    {
        if (! $bytes) {
            return '0 KB';
        }

        if ($bytes < 1024 * 1024) {
            return max(1, (int) ceil($bytes / 1024)).' KB';
        }

        return round($bytes / 1024 / 1024, 1).' MB';
    }

    private function notifyCustomersAboutStyle(StyleSampling $styleSampling, string $action): void
    {
    if ($styleSampling->status !== 'Published') {
        return;
    }

    try {
        User::query()
            ->where('role', 'customer')
            ->where('status', '<>', 'Suspended')
            ->whereNotNull('email')
            ->chunkById(100, function ($customers) use ($styleSampling, $action): void {
                Notification::send($customers, new StyleCatalogUpdated($styleSampling, $action));
            });
    } catch (\Throwable $e) {
        report($e);
    }
}
}
