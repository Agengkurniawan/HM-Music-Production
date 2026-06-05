<?php

namespace App\Http\Controllers;

use App\Models\DownloadSale;
use App\Models\MusicDemo;
use App\Models\StyleSampling;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $publishedStyles = StyleSampling::published();
        $publishedDemos = MusicDemo::published()->withYoutubeVideo();
        $currentUser = Auth::user();
        $activeSubscription = $currentUser?->activeSubscription()->first();
        $hasPremiumAccess = $activeSubscription !== null && (
            $activeSubscription->expires_at === null || $activeSubscription->expires_at->isFuture()
        );
        $demoLimit = 50;
        $demoPlays = (clone $publishedDemos)->sum('plays_count');
        $downloadCount = $currentUser
            ? DownloadSale::query()
                ->where('user_id', $currentUser->id)
                ->where('status', 'Completed')
                ->count()
            : 0;

        $stats = [
            'total_styles' => (clone $publishedStyles)->count(),
            'demo_played' => $demoPlays,
            'downloads' => $downloadCount,
            'premium_access' => $hasPremiumAccess,
            'demo_progress' => min(100, (int) round(($demoPlays / max($demoLimit, 1)) * 100)),
        ];

        $colors = ['#f97316', '#2563eb', '#0f766e', '#7c3aed'];
        $demos = MusicDemo::published()
            ->withYoutubeVideo()
            ->orderByDesc('is_trending')
            ->latest()
            ->take(4)
            ->get()
            ->values()
            ->map(fn (MusicDemo $demo, int $index) => [
                'title' => $demo->title,
                'genre' => $demo->genre,
                'bpm' => $demo->bpm,
                'duration' => $demo->duration,
                'source' => 'YouTube',
                'color' => $colors[$index] ?? '#2563eb',
                'img' => $demo->thumbnail_src,
            ]);

        $user = [
            'name' => $currentUser?->name ?? 'Producer',
            'email' => $currentUser?->email,
            'plan' => $activeSubscription?->package ?? $currentUser?->plan ?? 'Free Plan',
            'demo_limit' => $demoLimit,
            'is_premium' => $hasPremiumAccess,
            'expires_at' => $activeSubscription?->expires_at,
        ];

        $styles = StyleSampling::published()
            ->latest()
            ->take(3)
            ->get();

        return view('layouts.customers.dashboard', compact('stats', 'demos', 'styles', 'user'));
    }
}
