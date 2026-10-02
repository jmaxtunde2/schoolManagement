<?php

namespace App\Providers;

use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\EsmsAfricaProvider;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // SMS_PROVIDER=log (défaut) tant qu'aucun fournisseur réel n'est branché.
        $this->app->bind(SmsProviderInterface::class, function () {
            return match (config('services.sms.provider', 'log')) {
                'esms_africa' => new EsmsAfricaProvider,
                default => new LogSmsProvider,
            };
        });
    }
}
