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

        MusicDemoCatalogSeeder::seed(reset: false);

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
                'amount' => 0,
                'downloaded_at' => now()->subDays($index)->setTime(10 + $index, 15),
            ]);
        }
    }
}
