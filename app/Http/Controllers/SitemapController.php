<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\Tenancy\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Plan de site XML : sur l'hôte de la plateforme, on référence la landing
     * CoriSchool et chaque établissement actif ; sur le domaine d'un
     * établissement, seule son propre site est référencé.
     */
    public function __invoke(Request $request, TenantResolver $resolver): Response
    {
        $school = $resolver->resolve($request);
        $urls = [];

        if ($school) {
            $urls[] = $request->getSchemeAndHttpHost().'/';
        } else {
            $urls[] = url('/');

            School::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['slug'])
                ->each(function (School $school) use (&$urls): void {
                    $urls[] = route('public.school.slug', $school->slug);
                });
        }

        $entries = collect($urls)
            ->filter()
            ->unique()
            ->map(fn (string $loc) => '  <url><loc>'.htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES).'</loc></url>')
            ->implode("\n");

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$entries."\n"
            .'</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
