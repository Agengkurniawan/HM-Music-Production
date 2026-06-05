<?php

namespace Database\Seeders;

use App\Models\DownloadSale;
use App\Models\MusicDemo;
use App\Models\SamplingRequest;
use App\Models\StyleSampling;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DownloadSale::query()->delete();
        Subscription::query()->delete();
        MusicDemo::query()->delete();
        SamplingRequest::query()->delete();
        StyleSampling::query()->delete();
        User::query()->delete();

        $users = collect([
            ['name' => 'Andi Pratama', 'email' => 'andi.pratama@email.com', 'plan' => 'Premium Monthly', 'status' => 'Active', 'last_activity' => 'Downloaded HM Dangdut Raya'],
            ['name' => 'Siti Rahma', 'email' => 'siti.rahma@email.com', 'plan' => 'Premium Yearly', 'status' => 'Active', 'last_activity' => 'Renewed subscription'],
            ['name' => 'Bima Santoso', 'email' => 'bima.santoso@email.com', 'plan' => 'Starter Monthly', 'status' => 'Suspended', 'last_activity' => 'Payment failed'],
            ['name' => 'Rina Lestari', 'email' => 'rina.lestari@email.com', 'plan' => 'Free', 'status' => 'Active', 'last_activity' => 'Played Gamelan Modern preview'],
            ['name' => 'Daffa Nugraha', 'email' => 'daffa.nugraha@email.com', 'plan' => 'Studio Pro', 'status' => 'Review', 'last_activity' => 'Password reset requested'],
        ])->map(fn (array $user) => User::factory()->create([
            ...$user,
            'role' => 'customer',
        ]));

        User::factory()->create([
            'name' => 'Admin',
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'status' => 'Active',
            'plan' => 'Internal',
        ]);

        MusicDemo::insert([
            [
                'title' => 'Dangdut Raya',
                'genre' => 'Dangdut',
                'bpm' => 140,
                'duration' => '3:24',
                'key_signature' => 'C Minor',
                'status' => 'Published',
                'is_trending' => true,
                'youtube_url' => 'https://www.youtube.com/watch?v=M7lc1UVf-VE',
                'plays_count' => 3200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Campursari Senja',
                'genre' => 'Campursari',
                'bpm' => 128,
                'duration' => '4:12',
                'key_signature' => 'A Minor',
                'status' => 'Draft',
                'is_trending' => false,
                'youtube_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'plays_count' => 2100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Gamelan Modern',
                'genre' => 'Gamelan',
                'bpm' => 104,
                'duration' => '3:52',
                'key_signature' => 'D Minor',
                'status' => 'Published',
                'is_trending' => true,
                'youtube_url' => 'https://www.youtube.com/watch?v=ysz5S6PUM-U',
                'plays_count' => 1700,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Koplo Hits',
                'genre' => 'Koplo',
                'bpm' => 120,
                'duration' => '2:58',
                'key_signature' => 'G Minor',
                'status' => 'Published',
                'is_trending' => false,
                'youtube_url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
                'plays_count' => 1450,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Pop Melayu',
                'genre' => 'Pop',
                'bpm' => 112,
                'duration' => '3:46',
                'key_signature' => 'E Minor',
                'status' => 'Published',
                'is_trending' => false,
                'youtube_url' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                'plays_count' => 980,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $styles = StyleSamplingCatalogSeeder::seed(reset: false);
        SamplingRequestSeeder::seed($users, $styles, reset: false);

        $subscriptionData = [
            ['package' => 'Premium Monthly', 'starts_at' => now()->subMonth(), 'expires_at' => now()->addMonth(), 'status' => 'Active'],
            ['package' => 'Premium Yearly', 'starts_at' => now()->subMonths(4), 'expires_at' => now()->addMonths(8), 'status' => 'Active'],
            ['package' => 'Starter Monthly', 'starts_at' => now()->subMonths(2), 'expires_at' => now()->subMonth(), 'status' => 'Expired'],
            ['package' => 'Premium Monthly', 'starts_at' => now()->subMonths(3), 'expires_at' => now()->subMonths(2), 'status' => 'Cancelled'],
            ['package' => 'Studio Pro', 'starts_at' => now()->subWeeks(2), 'expires_at' => now()->addWeeks(2), 'status' => 'Active'],
        ];

        foreach ($users as $index => $user) {
            Subscription::create([
                'user_id' => $user->id,
                ...$subscriptionData[$index],
            ]);
        }

        foreach ($users->take(5) as $index => $user) {
            $style = $styles[$index % $styles->count()];

            DownloadSale::create([
                'user_id' => $user->id,
                'style_sampling_id' => $style->id,
                'file_name' => $style->display_style_name,
                'style_name' => $style->name,
                'status' => ['Completed', 'Completed', 'Pending', 'Completed', 'Failed'][$index],
                'amount' => $style->access === 'Premium' ? [79000, 0, 59000, 0, 89000][$index] : 0,
                'downloaded_at' => now()->subDays($index)->setTime(10 + $index, 15),
            ]);
        }
    }
}
