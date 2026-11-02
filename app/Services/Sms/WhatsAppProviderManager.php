<?php

namespace App\Services\Sms;

class WhatsAppProviderManager
{
    public function provider(): WhatsAppProviderInterface
    {
        return $this->driver() === 'wati'
            ? app(WatiWhatsAppProvider::class)
            : app(MetaWhatsAppProvider::class);
    }

    public function driver(): string
    {
        $provider = strtolower(trim((string) config('services.whatsapp.provider', 'meta')));

        return $provider === 'wati' ? 'wati' : 'meta';
    }

    public function configured(): bool
    {
        if ($this->driver() === 'wati') {
            return (bool) config('services.whatsapp.wati.enabled', false)
                && filled(config('services.whatsapp.wati.api_key'));
        }

        return filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    public function name(): string
    {
        return $this->driver() === 'wati' ? 'wati' : 'whatsapp';
    }
}
