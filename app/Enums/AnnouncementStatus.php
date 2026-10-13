<?php

namespace App\Enums;

enum AnnouncementStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sending = 'sending';
    case Sent = 'sent';
    case Partial = 'partial';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Scheduled => 'Programmée',
            self::Sending => 'En cours d\'envoi',
            self::Sent => 'Envoyée',
            self::Partial => 'Partiellement envoyée',
            self::Failed => 'Échec',
            self::Cancelled => 'Annulée',
        };
    }

    /** Tonalité utilisée par le composant Badge (slate, green, amber, red, blue). */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Scheduled => 'amber',
            self::Sending => 'blue',
            self::Sent => 'green',
            self::Partial => 'amber',
            self::Failed => 'red',
            self::Cancelled => 'slate',
        };
    }
}
