<?php

namespace App\Services\Communication;

use App\Enums\CommunicationChannel;
use App\Services\Sms\MetaWhatsAppProvider;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SmsResponse;

/**
 * Transport concret branché sur les fournisseurs configurés dans le projet.
 * Le SMS utilise le fournisseur actif (SMS_PROVIDER) et WhatsApp passe par
 * la Meta WhatsApp Cloud API.
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
        return app(SmsProviderInterface::class);
    }
}
