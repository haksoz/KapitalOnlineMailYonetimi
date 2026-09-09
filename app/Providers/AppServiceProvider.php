<?php

namespace App\Providers;

use App\Contracts\SubscriptionRepositoryInterface;
use App\Models\MailSetting;
use App\Models\Product;
use App\Models\Subscription;
use App\Observers\ProductObserver;
use App\Observers\SubscriptionObserver;
use App\Repositories\SubscriptionRepository;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        Subscription::observe(SubscriptionObserver::class);

        MailSetting::applyToRuntime();

        Queue::before(function (): void {
            MailSetting::applyToRuntime();
        });
    }
}
