<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente d\'approbation',
            self::Approved => 'Approuvée',
            self::Rejected => 'Rejetée',
        };
    }
}