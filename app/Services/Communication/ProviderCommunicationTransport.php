<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Services\Sms\EsmsAfricaProvider;
use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\MetaWhatsAppProvider;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SmsResponse;

/**
 * Transport concret branché sur les fournisseurs configurés dans le projet.
 * Réutilise l'intégration eSMS Africa existante pour le SMS et la Meta
 * WhatsApp Cloud API pour WhatsApp.
 */
class ProviderCommunicationTransport implements CommunicationTransport
{
    public function send(CommunicationChannel $channel, string $recipient, string $message): SmsResponse
    {
        return match ($channel) {
            CommunicationChannel::Sms => $this->smsProvider()->send($recipient, $message),
            CommunicationChannel::Whatsapp => (new MetaWhatsAppProvider)->send($recipient, $message),
            default => SmsResponse::failure('Canal externe non pris en charge.'),
        };
    }

    private function smsProvider(): SmsProviderInterface
    {
        return match (config('services.sms.provider', 'log')) {
            'esms_africa' => new EsmsAfricaProvider,
            default => new LogSmsProvider,
        };
    }
}
