<?php

namespace App\Providers;

use App\Support\HeaderNotificationFactory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.header', function ($view): void {
            $notifications = app(HeaderNotificationFactory::class)
                ->forCustomer(auth()->user());

            $view->with([
                'headerNotifications' => $notifications,
                'headerNotificationCount' => $notifications->count(),
            ]);
        });

        View::composer('components.admin-header', function ($view): void {
            $notifications = app(HeaderNotificationFactory::class)
                ->forAdmin();

            $view->with([
                'headerNotifications' => $notifications,
                'headerNotificationCount' => $notifications->count(),
            ]);
        });
    }
}
