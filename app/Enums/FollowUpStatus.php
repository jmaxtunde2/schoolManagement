<?php

namespace App\Enums;

enum FollowUpStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planifiée',
            self::InProgress => 'En cours',
            self::Done => 'Terminée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Planned => 'amber',
            self::InProgress => 'blue',
            self::Done => 'green',
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
