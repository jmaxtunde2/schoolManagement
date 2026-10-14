<?php

namespace App\Enums;

enum CommunicationChannel: string
{
    case InApp = 'in_app';
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::InApp => 'Application',
            self::Sms => 'SMS',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'Email',
        };
    }

    /** Un canal téléphonique passe par un fournisseur SMS/WhatsApp. */
    public function isPhone(): bool
    {
        return $this === self::Sms || $this === self::Whatsapp;
    }

    public function isExternal(): bool
    {
        return $this !== self::InApp;
    }
}
