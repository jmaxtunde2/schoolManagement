<?php

namespace App\Enums;

enum DemoRequestStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Scheduled = 'scheduled';
    case DemoDone = 'demo_done';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Contacted => 'Contactée',
            self::Scheduled => 'Planifiée',
            self::DemoDone => 'Démonstration effectuée',
            self::Approved => 'Approuvée',
            self::Rejected => 'Rejetée',
            self::Cancelled => 'Annulée',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Pending => 'Demande reçue, pas encore traitée.',
            self::Contacted => "L'école a été contactée.",
            self::Scheduled => 'Démonstration planifiée.',
            self::DemoDone => 'Démonstration réalisée.',
            self::Approved => 'Approuvée — activation sous paiement initial.',
            self::Rejected => 'Demande refusée.',
            self::Cancelled => 'Demande annulée.',
        };
    }

    /** Statut final : aucune transition ultérieure. */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Cancelled], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
