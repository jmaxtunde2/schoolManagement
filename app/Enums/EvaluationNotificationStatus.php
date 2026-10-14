<?php

namespace App\Enums;

/**
 * Cycle de vie de la notification parentale d'une évaluation validée.
 *
 * validated → pending_notification → included_in_batch → queued → sent → delivered
 * en cas d'échec : failed.
 */
enum EvaluationNotificationStatus: string
{
    case Pending = 'pending_notification';
    case Included = 'included_in_batch';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de notification',
            self::Included => 'Incluse dans un lot',
            self::Queued => 'En file d\'envoi',
            self::Sent => 'Envoyée',
            self::Delivered => 'Livrée',
            self::Failed => 'Échec',
        };
    }
}
