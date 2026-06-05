<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MusicDemo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DemoController extends Controller
{
    public function index(): View
    {
        $demos = MusicDemo::whereIn('genre', MusicDemo::GENRES)
            ->orderByDesc('is_trending')
            ->latest()
            ->get();

        return view('layouts.admin.admin-demo', [
            'demos' => $demos,
            'genres' => MusicDemo::GENRES,
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'youtube_url' => $this->youtubeUrlRules(required: true),
            'installation_youtube_url' => $this->youtubeUrlRules(required: false),
            'genre' => ['required', Rule::in(MusicDemo::GENRES)],
            'bpm' => ['required', 'integer', 'min:1', 'max:300'],
            'duration' => ['required', 'regex:/^\d{1,3}:[0-5]\d$/'],
            'key_signature' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(MusicDemo::STATUSES)],
        ]);

        $demo = new MusicDemo([
            'title' => $validated['title'],
            'youtube_url' => MusicDemo::normalizeYoutubeUrl($validated['youtube_url']),
            'installation_youtube_url' => $this->normalizeOptionalYoutubeUrl($validated['installation_youtube_url'] ?? null),
            'genre' => $validated['genre'],
            'bpm' => $validated['bpm'],
            'duration' => $validated['duration'],
            'key_signature' => $validated['key_signature'] ?? null,
            'status' => $validated['status'],
            'is_trending' => $request->boolean('trending'),
        ]);

        $demo->save();

        return back()->with('success', 'YouTube demo published successfully.');
    }

    public function update(Request $request, MusicDemo $demo): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'youtube_url' => $this->youtubeUrlRules(required: true),
            'installation_youtube_url' => $this->youtubeUrlRules(required: false),
            'genre' => ['required', Rule::in(MusicDemo::GENRES)],
            'bpm' => ['required', 'integer', 'min:1', 'max:300'],
            'duration' => ['required', 'regex:/^\d{1,3}:[0-5]\d$/'],
            'key_signature' => ['nullable', 'string', 'max:40'],
            'status' => ['required', Rule::in(MusicDemo::STATUSES)],
        ]);

        $demo->fill([
            'title' => $validated['title'],
            'youtube_url' => MusicDemo::normalizeYoutubeUrl($validated['youtube_url']),
            'installation_youtube_url' => $this->normalizeOptionalYoutubeUrl($validated['installation_youtube_url'] ?? null),
            'genre' => $validated['genre'],
            'bpm' => $validated['bpm'],
            'duration' => $validated['duration'],
            'key_signature' => $validated['key_signature'] ?? null,
            'status' => $validated['status'],
            'is_trending' => $request->boolean('trending'),
        ]);

        $demo->save();

        return back()->with('success', 'YouTube demo updated successfully.');
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
        $demo->delete();

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

    private function normalizeOptionalYoutubeUrl(?string $url): ?string
    {
        return filled($url) ? MusicDemo::normalizeYoutubeUrl($url) : null;
    }
}
