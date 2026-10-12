<?php

namespace App\Support;

class ReservedSchoolSlugs
{
    /**
     * Segments de premier niveau déjà utilisés par l'application. Un slug
     * d'établissement ne doit jamais pouvoir les masquer via la route
     * publique `/{slug}`.
     *
     * @var list<string>
     */
    public const LIST = [
        'admin',
        'auth',
        'censeur',
        'comptabilite',
        'creer-mon-ecole',
        'demander-une-demonstration',
        'direction',
        'ecole',
        'login',
        'notifications',
        'parent',
        'platform',
        'profile',
        'secretary',
        'surveillant',
        'teacher',
        'up',
        'storage',
        'settings',
        'build',
        'api',
        'sanctum',
        'livewire',
        'robots.txt',
        'sitemap.xml',
        'favicon.ico',
        'well-known',
        'www',
        'mail',
        'ns1',
        'ns2',
        'cpanel',
        'webmail',
    ];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique(self::LIST));
    }
}
