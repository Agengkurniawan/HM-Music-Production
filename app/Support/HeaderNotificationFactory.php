<?php

namespace App\Support;

use App\Models\DownloadSale;
use App\Models\Payment;
use App\Models\SamplingRequest;
use App\Models\StyleSampling;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HeaderNotificationFactory
{
    public function forCustomer(?User $user): Collection
    {
        $notifications = collect();

        if ($user) {
            $this->addCustomerSamplingNotifications($notifications, $user);
            $this->addCustomerSubscriptionNotifications($notifications, $user);
        }

        StyleSampling::published()
            ->whereNotNull('style_file_path')
            ->latest('updated_at')
            ->take(4)
            ->get()
            ->each(function (StyleSampling $style) use ($notifications): void {
                $notifications->push($this->make(
                    'style',
                    'New style update',
                    "{$style->name} is available in {$style->category}.",
                    route('stylesampling', ['type' => 'style', 'category' => $style->category]),
                    $style->updated_at,
                ));
            });

        return $this->sortAndLimit($notifications);
    }

    public function forAdmin(): Collection
    {
        $notifications = collect();

        User::query()
            ->where('role', 'customer')
            ->whereIn('status', ['Review', 'Pending'])
            ->latest()
            ->take(3)
            ->get()
            ->each(function (User $user) use ($notifications): void {
                $notifications->push($this->make(
                    'customer',
                    'Customer account pending',
                    "{$user->name} has a pending account state.",
                    route('admin.usermanagement'),
                    $user->created_at,
                ));
            });

        SamplingRequest::with('user')
            ->where('payment_status', SamplingRequest::PAYMENT_PAID)
            ->whereIn('status', [
                SamplingRequest::STATUS_PAID,
                SamplingRequest::STATUS_N27_UPLOADED,
                SamplingRequest::STATUS_PROCESSING,
            ])
            ->latest('updated_at')
            ->take(4)
            ->get()
            ->each(function (SamplingRequest $request) use ($notifications): void {
                $customerName = $request->user?->name ?: 'Unknown customer';
                $title = $request->status === SamplingRequest::STATUS_N27_UPLOADED
                    ? 'N27 file ready to process'
                    : 'N27 request waiting';

                $notifications->push($this->make(
                    'n27',
                    $title,
                    "{$request->order_reference} from {$customerName} for {$request->product_name}.",
                    route('admin.sampling-requests'),
                    $request->updated_at,
                ));
            });

        Subscription::with('user')
            ->where('status', 'Pending')
            ->latest()
            ->take(3)
            ->get()
            ->each(function (Subscription $subscription) use ($notifications): void {
                $customerName = $subscription->user?->name ?: 'A customer';

                $notifications->push($this->make(
                    'payment',
                    'Subscription access needs sync',
                    "{$customerName} has a legacy pending {$subscription->package} record.",
                    route('admin.subcription'),
                    $subscription->created_at,
                ));
            });

        Payment::with('user')
            ->where('status', 'Pending')
            ->latest()
            ->take(3)
            ->get()
            ->each(function (Payment $payment) use ($notifications): void {
                $notifications->push($this->make(
                    'payment',
                    'Payment needs attention',
                    "{$payment->reference} for {$payment->customer_name} is still pending.",
                    route('admin.subcription'),
                    $payment->created_at,
                ));
            });

        $todayDownloads = DownloadSale::whereDate('downloaded_at', today())->count();

        if ($todayDownloads > 0) {
            $notifications->push($this->make(
                'download',
                'Downloads today',
                "{$todayDownloads} style file downloads have been recorded today.",
                route('admin.downloadsales'),
                now(),
            ));
        }

        return $this->sortAndLimit($notifications);
    }

    private function addCustomerSamplingNotifications(Collection $notifications, User $user): void
    {
        $user->samplingRequests()
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->each(function (SamplingRequest $request) use ($notifications): void {
                if ($request->is_ready) {
                    $notifications->push($this->make(
                        'n27',
                        'Your N27 request is ready',
                        "{$request->product_name} has a delivery link from the admin.",
                        route('stylesampling', ['type' => 'sampling']),
                        $request->delivered_at ?: $request->updated_at,
                    ));

                    return;
                }

                if ($request->status === SamplingRequest::STATUS_PROCESSING) {
                    $notifications->push($this->make(
                        'n27',
                        'Your N27 request is processing',
                        "{$request->product_name} is being prepared by the admin team.",
                        route('stylesampling', ['type' => 'sampling']),
                        $request->updated_at,
                    ));

                    return;
                }

                if ($request->status === SamplingRequest::STATUS_N27_UPLOADED) {
                    $fileName = $request->n27_original_name ?: 'Your N27 file';

                    $notifications->push($this->make(
                        'n27',
                        'N27 file received',
                        "{$fileName} was uploaded for {$request->product_name}.",
                        route('stylesampling', ['type' => 'sampling']),
                        $request->n27_uploaded_at ?: $request->updated_at,
                    ));

                    return;
                }

                if ($request->payment_status === SamplingRequest::PAYMENT_PENDING) {
                    $notifications->push($this->make(
                        'payment',
                        'Payment is not completed',
                        "{$request->order_reference} will continue after payment is completed.",
                        route('stylesampling', ['type' => 'sampling']),
                        $request->updated_at,
                    ));
                }
            });
    }

    private function addCustomerSubscriptionNotifications(Collection $notifications, User $user): void
    {
        $subscription = $user->activeSubscription()->first();

        if (! $subscription) {
            $notifications->push($this->make(
                'subscription',
                'Premium access available',
                'Unlock STY downloads. Expansion sampling stays by request per pack.',
                route('subcription'),
                now()->subMinute(),
            ));

            return;
        }

        if ($subscription->expires_at?->between(now(), now()->addDays(7))) {
            $notifications->push($this->make(
                'subscription',
                'Subscription ending soon',
                "{$subscription->package} expires {$subscription->expires_at->diffForHumans()}.",
                route('subcription'),
                $subscription->expires_at,
            ));
        }
    }

    private function make(
        string $type,
        string $title,
        string $message,
        string $url,
        ?CarbonInterface $date = null,
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'date' => $date ?: now(),
            'time' => ($date ?: now())->diffForHumans(),
        ];
    }

    private function sortAndLimit(Collection $notifications): Collection
    {
        return $notifications
            ->sortByDesc(fn (array $notification) => $notification['date'])
            ->take(8)
            ->values();
    }
}
