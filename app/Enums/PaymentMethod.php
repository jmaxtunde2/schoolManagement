<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Manual = 'manual';
    case MobileMoney = 'mobile_money';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Confirmation manuelle',
            self::MobileMoney => 'Mobile Money',
            self::Card => 'Carte bancaire',
            self::BankTransfer => 'Virement bancaire',
            self::Cash => 'Espèces',
            self::Other => 'Autre',
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