<?php

namespace App\Services\Sms;

class WhatsAppProvider extends MetaWhatsAppProvider
{
    public function name(): string
    {
        return 'whatsapp';
    }
}
