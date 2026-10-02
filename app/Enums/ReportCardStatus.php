<?php

namespace App\Enums;

enum ReportCardStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Generated => 'Généré',
            self::Published => 'Publié',
        };
    }
}