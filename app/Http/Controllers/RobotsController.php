<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * robots.txt dynamique : autorise l'exploration des pages publiques,
     * écarte les espaces authentifiés et pointe vers le plan de site.
     */
    public function __invoke(Request $request): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /direction',
            'Disallow: /censeur',
            'Disallow: /comptabilite',
            'Disallow: /secretary',
            'Disallow: /surveillant',
            'Disallow: /teacher',
            'Disallow: /parent',
            'Disallow: /platform',
            'Disallow: /profile',
            'Disallow: /notifications',
            'Disallow: /login',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
