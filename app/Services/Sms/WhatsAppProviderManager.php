<?php

namespace App\Services\Sms;

class WhatsAppProviderManager
{
    public function provider(): WhatsAppProviderInterface
    {
        return $this->providerFor();
    }

    public function providerFor(?string $driver = null): WhatsAppProviderInterface
    {
        return match ($this->normalizeDriver($driver)) {
            'wati' => app(WatiWhatsAppProvider::class),
            'twilio' => app(TwilioWhatsAppProvider::class),
            default => app(MetaWhatsAppProvider::class),
        };
    }

    public function driver(): string
    {
        $provider = strtolower(
            trim(
                (string) config('services.whatsapp.provider', 'meta')
            )
        );

        return match ($provider) {
            'wati' => 'wati',
            'twilio' => 'twilio',
            default => 'meta',
        };
    }

    public function configured(): bool
    {
        return $this->configuredFor();
    }

    public function configuredFor(?string $driver = null): bool
    {
        return match ($this->normalizeDriver($driver)) {
            'wati' => (bool) config('services.whatsapp.wati.enabled', false)
                && filled(config('services.whatsapp.wati.api_key')),

            'twilio' => filled(config('services.twilio.account_sid'))
                && filled(config('services.twilio.auth_token'))
                && filled(config('services.twilio.whatsapp_from')),

            default => filled(config('services.whatsapp.token'))
                && filled(config('services.whatsapp.phone_number_id')),
        };
    }

    private function normalizeDriver(?string $driver = null): string
    {
        $driver = strtolower(trim((string) ($driver ?? $this->driver())));

        return match ($driver) {
            'wati' => 'wati',
            'twilio' => 'twilio',
            default => 'meta',
        };
    }

    public function name(): string
    {
        return $this->driver();
    }
}
