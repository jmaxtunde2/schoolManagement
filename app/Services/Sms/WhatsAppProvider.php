<?php

namespace App\Services\Sms;

class WhatsAppProvider extends MetaWhatsAppProvider
{
    public function name(): string
    {
        return 'whatsapp';
    }

    public function send(string $phone, string $message, ?array $metadata = null): SmsResponse
    {
        return parent::send($phone, $message, $metadata);
    }
}
