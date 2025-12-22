<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Interfaces\PaymentGatewayAdapterInterface::class,
            \App\Services\Gateways\MockPaymentGatewayAdapter::class
        );

        $this->app->bind(
            \App\Interfaces\IssuerAdapterInterface::class,
            \App\Services\Adapters\MockIssuerAdapter::class
        );

        // Phase 4: SMS Provider Abstraction
        $this->app->bind(
            \App\Interfaces\SmsProviderInterface::class,
            \App\Services\Gateways\Sms\MockSmsProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
