<?php

namespace App\Providers;

use App\Services\Communication\CommunicationTransport;
use App\Services\Communication\ProviderCommunicationTransport;
use App\Services\Sms\ClickSendProvider;
use App\Services\Sms\EMcSmsProvider;
use App\Services\Sms\EsmsAfricaProvider;
use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\MetaWhatsAppProvider;
use App\Services\Sms\MultitexterProvider;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\WhatsAppProviderManager;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // SMS_PROVIDER=emc | multitexter | esms_africa | whatsapp | log
        $this->app->bind(SmsProviderInterface::class, function () {
            return match (config('services.sms.provider', 'emc')) {
                'emc' => new EMcSmsProvider,
                'clicksend' => new ClickSendProvider,
                'esms_africa' => new EsmsAfricaProvider,
                'whatsapp' => app(WhatsAppProviderManager::class)->provider(),
                'multitexter' => new MultitexterProvider,
                'log' => new LogSmsProvider,
                default => new EMcSmsProvider,
            };
        });

        $this->app->bind(CommunicationTransport::class, ProviderCommunicationTransport::class);
    }
}
