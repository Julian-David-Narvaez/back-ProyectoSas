<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Resend\Client as ResendClient;

class ResendServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ResendClient::class, function ($app) {
            $apiKey = config('services.resend.api_key');
            
            if (!$apiKey) {
                throw new \InvalidArgumentException('Resend API key not configured');
            }
            
            return \Resend::client($apiKey);
        });
        
        // Alias para facilitar el uso
        $this->app->alias(ResendClient::class, 'resend');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}