<?php

namespace App\Enums;

enum Role: string
{
    case PlatformAdmin = 'platform_admin';
    case Admin = 'admin';
    case Director = 'director';
    case Accountant = 'accountant';
    case Censeur = 'censeur';
    case Secretary = 'secretary';
    case Teacher = 'teacher';
    case Parent = 'parent';

    public function label(): string
    {
        return match ($this) {
            self::PlatformAdmin => 'Administrateur Coriyase',
            self::Admin => 'Administrateur',
            self::Director => 'Directeur',
            self::Accountant => 'Comptable',
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
            self::Admin => 'admin.dashboard',
            self::Director => 'director.dashboard',
            self::Accountant => 'accountant.dashboard',
            self::Censeur => 'censeur.dashboard',
            self::Secretary => 'secretary.dashboard',
            self::Teacher => 'teacher.dashboard',
            self::Parent => 'parent.dashboard',
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

    /**
     * Rôles que l'administrateur d'école peut attribuer dans Administration → Personnel.
     * `admin` est inclus : several établissements ont plus d'un administrateur.
     * `platform_admin` et `parent` en sont exclus (le parent est un compte
     * lié à un élève, pas un membre du personnel — voir GuardianController).
     */
    public function isAssignableStaff(): bool
    {
        return in_array($this, [
            self::Admin,
            self::Director,
            self::Accountant,
            self::Censeur,
            self::Secretary,
            self::Teacher,
        ], true);
    }

    /** @return array<int, self> */
    public static function assignableStaff(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role) => $role->isAssignableStaff()));
    }

    /** Rôle qui donne la maîtrise complète de l'établissement. */
    public function managesSchool(): bool
    {
        return $this === self::Admin;
    }
}
