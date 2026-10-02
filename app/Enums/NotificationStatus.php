<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Queued => 'En file',
            self::Sent => 'Envoyé',
            self::Delivered => 'Livré',
            self::Failed => 'Échec',
        };
    }
}
