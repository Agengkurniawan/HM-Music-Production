<?php

namespace App\Providers;

use App\Contracts\CatalogEnrichmentProviderInterface;
use App\Contracts\EmbeddingProviderInterface;
use App\Contracts\QueryUnderstandingProviderInterface;
use App\Services\AI\GeminiCatalogEnrichmentProvider;
use App\Services\AI\GeminiEmbeddingProvider;
use App\Support\HeaderNotificationFactory;
use App\Support\HeaderNotificationReadState;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmbeddingProviderInterface::class, GeminiEmbeddingProvider::class);
        $this->app->bind(CatalogEnrichmentProviderInterface::class, GeminiCatalogEnrichmentProvider::class);
        $this->app->bind(QueryUnderstandingProviderInterface::class, GeminiCatalogEnrichmentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.header', function ($view): void {
            $allNotifications = app(HeaderNotificationFactory::class)
                ->forCustomer(auth()->user());
            $notifications = app(HeaderNotificationReadState::class)
                ->unread($allNotifications, auth()->user());

            $view->with([
                'headerNotifications' => $notifications,
                'headerNotificationCount' => $notifications->count(),
            ]);
        });

        View::composer('components.admin-header', function ($view): void {
            $allNotifications = app(HeaderNotificationFactory::class)
                ->forAdmin();
            $notifications = app(HeaderNotificationReadState::class)
                ->unread($allNotifications, auth()->user());

            $view->with([
                'headerNotifications' => $notifications,
                'headerNotificationCount' => $notifications->count(),
            ]);
        });
    }
}
