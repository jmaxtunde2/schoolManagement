<?php

namespace App\Enums;

enum Role: string
{
    case PlatformAdmin = 'platform_admin';
    case Admin = 'admin';
    case Censeur = 'censeur';
    case Secretary = 'secretary';
    case Teacher = 'teacher';
    case Parent = 'parent';

    public function label(): string
    {
        return match ($this) {
            self::PlatformAdmin => 'Administrateur Coriyase',
            self::Admin => 'Administrateur',
            self::Censeur => 'Censeur',
            self::Secretary => 'Secrétaire',
            self::Teacher => 'Enseignant',
            self::Parent => 'Parent',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::PlatformAdmin => 'platform.dashboard',
            self::Admin, self::Censeur, self::Secretary, self::Teacher, self::Parent => match ($this) {
                self::Censeur => 'censeur.dashboard',
                self::Secretary => 'secretary.dashboard',
                default => $this === self::Admin ? 'admin.dashboard' : ($this === self::Parent ? 'parent.dashboard' : 'teacher.dashboard'),
            },
        };
    }

    public function isSchoolStaff(): bool
    {
        return $this !== self::PlatformAdmin;
    }

    public function isPlatformAdmin(): bool
    {
        return $this === self::PlatformAdmin;
    }
}
