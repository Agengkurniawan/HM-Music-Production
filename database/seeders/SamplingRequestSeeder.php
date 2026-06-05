<?php

namespace Database\Seeders;

use App\Models\SamplingRequest;
use App\Models\StyleSampling;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SamplingRequestSeeder extends Seeder
{
    public function run(): void
    {
        self::seed();
    }

    public static function seed(?Collection $users = null, ?Collection $styles = null, bool $reset = true): Collection
    {
        if ($reset) {
            SamplingRequest::query()->delete();
        }

        $users ??= User::where('role', 'customer')->latest()->take(5)->get();
        $styles ??= StyleSampling::whereNotNull('style_file_path')->latest()->take(5)->get();

        if ($users->isEmpty() || $styles->isEmpty()) {
            return collect();
        }

        return $users->values()->take(5)->map(function (User $user, int $index) use ($styles): SamplingRequest {
            $style = $styles[$index % $styles->count()];
            $packOption = StyleSampling::samplingRequestOption($style->pack);
            $keyboardStorageMb = [512, 768, 1024, 640, 768][$index];
            $reference = 'N27-'.now()->format('Ymd').'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $status = [
                SamplingRequest::STATUS_PAID,
                SamplingRequest::STATUS_N27_UPLOADED,
                SamplingRequest::STATUS_PROCESSING,
                SamplingRequest::STATUS_READY,
                SamplingRequest::STATUS_COMPLETED,
            ][$index];

            $hasN27 = in_array($status, [
                SamplingRequest::STATUS_N27_UPLOADED,
                SamplingRequest::STATUS_PROCESSING,
                SamplingRequest::STATUS_READY,
                SamplingRequest::STATUS_COMPLETED,
            ], true);
            $isReady = in_array($status, [
                SamplingRequest::STATUS_READY,
                SamplingRequest::STATUS_COMPLETED,
            ], true);

            $n27Path = null;
            $n27Filename = null;

            if ($hasN27) {
                $n27Filename = str($reference.'-'.$user->name)->slug('-')->toString().'.n27';
                $n27Path = "sampling-requests/n27/{$n27Filename}";

                Storage::disk('public')->put(
                    $n27Path,
                    "Temporary Yamaha N27 placeholder for {$user->name} / {$style->name}.\n",
                );
            }

            return SamplingRequest::create([
                'user_id' => $user->id,
                'style_sampling_id' => $style->id,
                'order_reference' => $reference,
                'product_name' => $packOption['label'],
                'pack_name' => $style->pack,
                'keyboard_storage_mb' => $keyboardStorageMb,
                'customer_notes' => 'Contoh: connect voice kit '.$style->category.' untuk keyboard '.$keyboardStorageMb.' MB.',
                'amount' => (int) ($packOption['price'] ?? StyleSampling::SAMPLING_REQUEST_PRICE),
                'payment_status' => SamplingRequest::PAYMENT_PAID,
                'status' => $status,
                'n27_file_path' => $n27Path,
                'n27_original_name' => $n27Filename,
                'n27_uploaded_at' => $hasN27 ? now()->subHours(18 - ($index * 2)) : null,
                'google_drive_link' => $isReady ? 'https://drive.google.com/file/d/demo-'.$reference.'/view' : null,
                'delivery_notes' => $isReady ? 'Final Yamaha Expansion Manager export is ready. Open the Drive link and download the completed sampling file.' : null,
                'delivered_at' => $isReady ? now()->subHours(3) : null,
                'completed_at' => $status === SamplingRequest::STATUS_COMPLETED ? now()->subHour() : null,
                'admin_notes' => StyleSampling::samplingRequestAdvice($style->pack, $keyboardStorageMb),
            ]);
        });
    }
}
