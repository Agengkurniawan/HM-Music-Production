<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\MusicDemo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DemoController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $isVerifiedAdmin = $user?->role === 'admin'
            && $user->email_verified_at !== null
            && Str::lower($user->email) === Str::lower((string) config('hm.admin_email'));

        if ($isVerifiedAdmin) {
            return redirect()->route('admin.dashboard');
        }

        return view('layouts.customers.demo', [
            'demos' => MusicDemo::published()
                ->withPlayableMedia()
                ->whereIn('genre', MusicDemo::genreOptions())
                ->orderByDesc('is_trending')
                ->latest()
                ->get(),
            'categories' => ['All', ...MusicDemo::genreOptions()],
            'hasActiveSubscription' => $this->hasActiveSubscription($user),
        ]);
    }

    public function recordPlay(Request $request, MusicDemo $musicDemo): JsonResponse
    {
        abort_unless(
            $musicDemo->status === 'Published'
            && ($musicDemo->youtube_video_id || filled($musicDemo->installation_video_path)),
            404
        );

        $musicDemo->increment('plays_count');
        $musicDemo->refresh();

        $request->user()?->update([
            'last_activity' => 'Played '.$musicDemo->title.' demo',
        ]);

        return response()->json([
            'plays_count' => $musicDemo->plays_count,
        ]);
    }

    private function hasActiveSubscription($user): bool
    {
        $subscription = $user?->activeSubscription()->first();

        return $subscription !== null
            && ($subscription->expires_at === null || $subscription->expires_at->isFuture());
    }
}
