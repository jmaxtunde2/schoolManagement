<?php

namespace App\Services\Communication;

use App\Services\Sms\MetaWhatsAppProvider;
use App\Services\Sms\SmsResponse;
use App\Services\Sms\SupportsDocuments;

class WhatsAppProvider extends MetaWhatsAppProvider
{
    // Reusable WhatsAppProvider that fits into the existing communication/notification architecture
    // Delegates to Meta WhatsApp Cloud API implementation

    public function send(string $phone, string $message, ?array $metadata = null): SmsResponse
    {
        return parent::send($phone, $message, $metadata);
    }
}
