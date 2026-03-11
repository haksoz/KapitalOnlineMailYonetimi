<?php

namespace App\Providers;

use App\Contracts\SubscriptionRepositoryInterface;
use App\Models\MailSetting;
use App\Repositories\SubscriptionRepository;
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
        $this->applyMailSettingsFromDatabase();
    }

    /**
     * Veritabanındaki mail ayarları (use_custom ise) config'e uygulanır; otomatik mailler bu ayarlarla gider.
     */
    private function applyMailSettingsFromDatabase(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('mail_settings')) {
                return;
            }
            $overrides = MailSetting::toMailConfig();
            if ($overrides === []) {
                return;
            }
            $current = config('mail');
            $current['mailers'] = array_merge($current['mailers'] ?? [], $overrides['mailers'] ?? []);
            $current['default'] = $overrides['default'] ?? $current['default'];
            if (! empty($overrides['from'])) {
                $current['from'] = $overrides['from'];
            }
            config(['mail' => $current]);
        } catch (\Throwable) {
            // Migrations not run yet or table missing
        }
    }
}
