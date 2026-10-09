<?php

namespace App\Enums;

enum LicenseStatus: string
{
    case Pending = 'pending';
    case PaymentPending = 'payment_pending';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En préparation',
            self::PaymentPending => 'Paiement en attente',
            self::Active => 'Active',
            self::Expired => 'Expirée',
            self::Cancelled => 'Annulée',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Pending => 'Licence en cours de préparation.',
            self::PaymentPending => 'En attente du règlement.',
            self::Active => 'L\'école peut utiliser CoriSchool.',
            self::Expired => 'La période d\'utilisation est terminée.',
            self::Cancelled => 'Licence annulée.',
        };
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
}