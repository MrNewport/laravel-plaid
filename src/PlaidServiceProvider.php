<?php

namespace MrNewport\LaravelPlaid;

use Illuminate\Support\ServiceProvider;

class PlaidServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/plaid.php',
            'plaid'
        );

        $this->app->singleton(PlaidClient::class, function ($app) {
            return new PlaidClient($app['config']['plaid']);
        });

        $this->app->singleton(Plaid::class, function ($app) {
            return new Plaid($app->make(PlaidClient::class));
        });

        $this->app->alias(Plaid::class, 'plaid');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/plaid.php' => config_path('plaid.php'),
            ], 'plaid-config');
        }
    }

    public function provides(): array
    {
        return [
            PlaidClient::class,
            Plaid::class,
            'plaid',
        ];
    }
}