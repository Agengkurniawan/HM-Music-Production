<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadSale;
use App\Models\MusicDemo;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $trendingSongs = MusicDemo::query()
            ->withPlayableMedia()
            ->whereIn('genre', MusicDemo::genreOptions())
            ->orderByDesc('is_trending')
            ->orderByDesc('plays_count')
            ->take(4)
            ->get()
            ->values()
            ->map(fn (MusicDemo $demo, int $index) => [
                'rank' => $index + 1,
                'title' => $demo->title,
                'artist' => 'HM Studio',
                'plays' => number_format($demo->plays_count),
                'revenue' => $demo->is_trending ? 'Trending' : ($demo->youtube_video_id ? 'YouTube' : 'MP4'),
            ]);
        $topTrendingSong = $trendingSongs->first();

        $totalUsers = User::where('role', 'customer')->count();
        $activeSubscriptions = Subscription::query()
            ->where('status', 'Active')
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->count();
        $totalDownloads = DownloadSale::where('status', 'Completed')->count();
        $totalDemoPlays = MusicDemo::whereIn('genre', MusicDemo::genreOptions())->sum('plays_count');
        $monthlyRevenue = Payment::where('status', 'Completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $subscriptionMonths = $this->subscriptionMonths();
        $downloadData = $this->weeklyDownloads();
        $demoPlays = $this->demoPerformance();

        return view('layouts.admin.admin-dashboard', [
            'stats' => [
                [
                    'label' => 'Total Users',
                    'value' => number_format($totalUsers),
                    'trend' => number_format(User::where('role', 'customer')->whereDate('created_at', today())->count()).' today',
                    'note' => 'customer accounts',
                    'theme' => 'violet',
                    'icon' => 'users',
                ],
                [
                    'label' => 'Active Subscriptions',
                    'value' => number_format($activeSubscriptions),
                    'trend' => number_format(Subscription::where('status', 'Active')->whereDate('created_at', today())->count()).' today',
                    'note' => 'active access',
                    'theme' => 'blue',
                    'icon' => 'card',
                ],
                [
                    'label' => 'Total Downloads',
                    'value' => number_format($totalDownloads),
                    'trend' => number_format(DownloadSale::where('status', 'Completed')->whereDate('downloaded_at', today())->count()).' today',
                    'note' => 'style files',
                    'theme' => 'green',
                    'icon' => 'download',
                ],
                [
                    'label' => 'Total Demos Played',
                    'value' => number_format($totalDemoPlays),
                    'trend' => ($topTrendingSong['plays'] ?? '0').' top',
                    'note' => 'customer play events',
                    'theme' => 'orange',
                    'icon' => 'play',
                ],
                [
                    'label' => 'Monthly Revenue',
                    'value' => 'Rp '.number_format((int) $monthlyRevenue, 0, ',', '.'),
                    'trend' => number_format(Payment::where('status', 'Completed')->whereDate('created_at', today())->count()).' payments today',
                    'note' => now()->format('M Y').' completed',
                    'theme' => 'red',
                    'icon' => 'revenue',
                ],
                [
                    'label' => "This Week's Trending Song",
                    'value' => $topTrendingSong['title'] ?? 'No trending demo',
                    'trend' => ($topTrendingSong['plays'] ?? '0').' plays',
                    'note' => 'top performer',
                    'theme' => 'dark',
                    'icon' => 'trend',
                ],
            ],
            'subscriptionMonths' => $subscriptionMonths,
            'subscriptionGrowthTotal' => number_format(collect($subscriptionMonths)->sum('value')).' users',
            'downloadData' => $downloadData,
            'weeklyDownloadTotal' => number_format(collect($downloadData)->sum('raw')).' downloads',
            'demoPlays' => $demoPlays,
            'demoPlayTotal' => number_format(MusicDemo::whereIn('genre', MusicDemo::genreOptions())->sum('plays_count')).' plays',
            'topTrendingSong' => $topTrendingSong,
            'trendingSongs' => $trendingSongs,
        ]);
    }

    private function subscriptionMonths(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $monthsAgo): Carbon => now()->startOfMonth()->subMonths($monthsAgo));

        $values = $months->map(fn (Carbon $month): int => Subscription::whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->count());
        $max = max($values->max(), 1);

        return $months->values()
            ->map(fn (Carbon $month, int $index): array => [
                'label' => $month->format('M'),
                'value' => $values[$index],
                'height' => max(8, (int) round(($values[$index] / $max) * 100)),
            ])
            ->all();
    }

    private function weeklyDownloads(): array
    {
        $days = collect(range(6, 0))
            ->map(fn (int $daysAgo): Carbon => now()->startOfDay()->subDays($daysAgo));
        $values = $days->map(fn (Carbon $day): int => DownloadSale::where('status', 'Completed')
            ->whereDate('downloaded_at', $day)
            ->count());
        $max = max($values->max(), 1);

        return $days->values()
            ->map(fn (Carbon $day, int $index): array => [
                'label' => $day->format('D'),
                'value' => number_format($values[$index]),
                'raw' => $values[$index],
                'height' => max(8, (int) round(($values[$index] / $max) * 100)),
            ])
            ->all();
    }

    private function demoPerformance(): array
    {
        $demos = MusicDemo::whereIn('genre', MusicDemo::genreOptions())
            ->withPlayableMedia()
            ->orderByDesc('plays_count')
            ->take(5)
            ->get();
        $maxPlays = max((int) $demos->max('plays_count'), 1);

        return $demos
            ->map(fn (MusicDemo $demo): array => [
                'title' => $demo->title,
                'genre' => $demo->display_genre,
                'plays' => number_format($demo->plays_count),
                'percent' => max(4, (int) round(($demo->plays_count / $maxPlays) * 100)),
            ])
            ->all();
    }
}
