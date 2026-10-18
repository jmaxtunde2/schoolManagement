<?php

namespace App\Providers;

use App\Services\WebPush\LogPushProvider;
use App\Services\WebPush\MinishlinkPushProvider;
use App\Services\WebPush\PushProvider;
use Illuminate\Support\ServiceProvider;

class WebPushServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // WEBPUSH_PROVIDER=log (défaut) tant que les clés VAPID ne sont pas
        // configurées : le flux complet reste fonctionnel et testable.
        $this->app->bind(PushProvider::class, function () {
            return match (config('webpush.provider', 'log')) {
                'minishlink' => new MinishlinkPushProvider,
                default => new LogPushProvider,
            };
        });
    }
}
