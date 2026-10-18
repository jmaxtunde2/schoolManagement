<?php

namespace App\Enums;

enum FollowUpType: string
{
    case Academic = 'academic';
    case Attendance = 'attendance';
    case Behaviour = 'behaviour';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Academic => 'Pédagogique',
            self::Attendance => 'Assiduité',
            self::Behaviour => 'Comportement',
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
