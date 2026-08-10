<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MusicDemo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DemoController extends Controller
{
    public function index(): View
    {
        $demos = MusicDemo::whereIn('genre', MusicDemo::genreOptions())
            ->orderByDesc('is_trending')
            ->latest()
            ->get();

        return view('layouts.admin.admin-demo', [
            'demos' => $demos,
            'genres' => MusicDemo::genreOptions(),
            'demoSummary' => [
                'total' => $demos->count(),
                'published' => $demos->where('status', 'Published')->count(),
                'draft' => $demos->where('status', 'Draft')->count(),
                'trending' => $demos->where('is_trending', true)->count(),
                'plays' => $demos->sum('plays_count'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('demoCreate', [
            'title' => ['required', 'string', 'max:120'],
            'youtube_url' => $this->youtubeUrlRules(required: false),
            'genre' => ['nullable', Rule::in(MusicDemo::genreOptions())],
            'bpm' => ['nullable', 'integer', 'min:1', 'max:300'],
            'duration' => ['nullable', 'regex:/^\d{1,3}:[0-5]\d$/'],
            'key_signature' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(MusicDemo::STATUSES)],
            'installation_video' => ['nullable', 'file', 'mimes:mp4', 'max:204800'],
        ]);

        $installationVideo = $request->file('installation_video');
        if (blank($validated['youtube_url'] ?? null) && ! ($installationVideo instanceof UploadedFile)) {
            return back()
                ->withErrors(['youtube_url' => 'Tambahkan link YouTube atau upload video MP4.'], 'demoCreate')
                ->withInput();
        }

        $demo = new MusicDemo([
            'title' => $validated['title'],
            'youtube_url' => filled($validated['youtube_url'] ?? null)
                ? MusicDemo::normalizeYoutubeUrl($validated['youtube_url'])
                : null,
            'genre' => $validated['genre'] ?? MusicDemo::GENRE_NONE,
            'bpm' => $validated['bpm'] ?? 0,
            'duration' => $validated['duration'] ?? '0:00',
            'key_signature' => $validated['key_signature'] ?? null,
            'status' => $validated['status'],
            'is_trending' => $request->boolean('trending'),
        ]);

        if ($installationVideo instanceof UploadedFile) {
            $demo->installation_video_path = $installationVideo->store('demos/videos', 'public');
        }

        $demo->save();

        return back()->with('success', 'Demo saved successfully.');
    }

    public function update(Request $request, MusicDemo $demo): RedirectResponse
    {
        $request->session()->flash('admin_demo_edit_id', $demo->id);
        $validated = $request->validateWithBag('demoEdit', [
            'title' => ['required', 'string', 'max:120'],
            'youtube_url' => $this->youtubeUrlRules(required: false),
            'genre' => ['nullable', Rule::in(MusicDemo::genreOptions())],
            'bpm' => ['nullable', 'integer', 'min:1', 'max:300'],
            'duration' => ['nullable', 'regex:/^\d{1,3}:[0-5]\d$/'],
            'key_signature' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(MusicDemo::STATUSES)],
            'installation_video' => ['nullable', 'file', 'mimes:mp4', 'max:204800'],
            'remove_installation_video' => ['nullable', 'boolean'],
        ]);

        $oldInstallationVideoPath = $demo->installation_video_path;
        $installationVideo = $request->file('installation_video');
        $keepsExistingMp4 = filled($demo->installation_video_path) && ! $request->boolean('remove_installation_video');

        if (
            blank($validated['youtube_url'] ?? null)
            && ! ($installationVideo instanceof UploadedFile)
            && ! $keepsExistingMp4
        ) {
            return back()
                ->withErrors(['youtube_url' => 'Tambahkan link YouTube atau upload video MP4 sebelum menyimpan.'], 'demoEdit')
                ->withInput();
        }

        $demo->fill([
            'title' => $validated['title'],
            'youtube_url' => filled($validated['youtube_url'] ?? null)
                ? MusicDemo::normalizeYoutubeUrl($validated['youtube_url'])
                : null,
            'genre' => $validated['genre'] ?? MusicDemo::GENRE_NONE,
            'bpm' => $validated['bpm'] ?? 0,
            'duration' => $validated['duration'] ?? '0:00',
            'key_signature' => $validated['key_signature'] ?? null,
            'status' => $validated['status'],
            'is_trending' => $request->boolean('trending'),
        ]);

        if ($installationVideo instanceof UploadedFile) {
            $demo->installation_video_path = $installationVideo->store('demos/videos', 'public');
        } elseif ($request->boolean('remove_installation_video')) {
            $demo->installation_video_path = null;
        }

        $demo->save();

        if ($oldInstallationVideoPath && $oldInstallationVideoPath !== $demo->installation_video_path) {
            Storage::disk('public')->delete($oldInstallationVideoPath);
        }

        return back()->with('success', 'Demo updated successfully.');
    }

    public function toggleTrending(Request $request, MusicDemo $demo): RedirectResponse
    {
        $request->validate([
            'is_trending' => ['required', 'boolean'],
        ]);

        $demo->update([
            'is_trending' => $request->boolean('is_trending'),
        ]);

        return back()->with('success', $demo->is_trending
            ? 'Demo marked as trending.'
            : 'Demo removed from trending.');
    }

    public function destroy(MusicDemo $demo): RedirectResponse
    {
        $installationVideoPath = $demo->installation_video_path;

        $demo->delete();

        if ($installationVideoPath) {
            Storage::disk('public')->delete($installationVideoPath);
        }

        return back()->with('success', 'Demo deleted successfully.');
    }

    private function youtubeUrlRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'url',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (! MusicDemo::extractYoutubeVideoId((string) $value)) {
                    $fail('The YouTube URL must point to a valid YouTube video.');
                }
            },
        ];
    }

}
