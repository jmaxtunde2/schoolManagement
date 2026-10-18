<?php

namespace App\Http\Controllers;

use App\Enums\PreinscriptionStatus;
use App\Http\Requests\Preinscriptions\PublicPreinscriptionRequest;
use App\Models\ClassRoom;
use App\Models\Preinscription;
use App\Services\Tenancy\TenantResolver;
use App\Support\SchoolBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Préinscription en ligne (phase 7).
 *
 * Formulaire public déposé depuis le mini-site de l'établissement. La demande
 * n'crée aucun élève : elle est examinée puis validée par l'administration
 * (voir Admin\PreinscriptionController). L'établissement est toujours résolu
 * depuis le slug de l'URL — jamais depuis une valeur du formulaire.
 */
class PublicPreinscriptionController extends Controller
{
    public function show(Request $request, TenantResolver $resolver, string $slug): Response
    {
        $school = $resolver->resolveBySlug($slug);
        abort_unless($school && $school->is_active, 404);

        $branding = SchoolBranding::for($school);
        $setting = $school->settings()->withoutGlobalScopes()->first();

        $levels = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->distinct()
            ->orderBy('level')
            ->pluck('level')
            ->values();

        return Inertia::render('Public/Preinscription', [
            'school' => [
                'name' => $setting?->school_name ?: $school->name,
                'slogan' => $setting?->slogan,
                'logo_url' => $setting?->logo_url ?: $branding['logo_url'],
                'primary_color' => $setting?->primary_color ?: $branding['primary_color'],
                'secondary_color' => $setting?->secondary_color ?: $branding['secondary_color'],
                'accent_color' => $setting?->accent_color ?: $branding['accent_color'],
                'phone' => $setting?->phone,
                'email' => $setting?->email,
                'address' => $setting?->address,
                'city' => $setting?->city,
                'country' => $setting?->country,
                'public_url' => $school->publicUrl(),
            ],
            'levels' => $levels,
            'submitUrl' => route('public.school.preinscription.store', $slug),
        ]);
    }

    public function store(
        PublicPreinscriptionRequest $request,
        TenantResolver $resolver,
        string $slug
    ): RedirectResponse {
        $school = $resolver->resolveBySlug($slug);
        abort_unless($school && $school->is_active, 404);

        $documents = collect($request->file('documents') ?? [])
            ->filter()
            ->map(fn ($file) => $file->store("preinscriptions/{$school->id}", 'public'))
            ->values()
            ->all();

        Preinscription::create([
            'school_id' => $school->id,
            'first_name' => $request->validated('first_name'),
            'last_name' => $request->validated('last_name'),
            'birth_date' => $request->validated('birth_date'),
            'gender' => $request->validated('gender'),
            'requested_level' => $request->validated('requested_level'),
            'parent_name' => $request->validated('parent_name'),
            'parent_phone' => $request->validated('parent_phone'),
            'parent_email' => $request->validated('parent_email'),
            'parent_relationship' => $request->validated('parent_relationship'),
            'notes' => $request->validated('notes'),
            'documents' => $documents ?: null,
            'status' => PreinscriptionStatus::Pending,
        ]);

        return redirect()
            ->route('public.school.preinscription', $slug)
            ->with(
                'success',
                'Votre demande de préinscription a bien été envoyée. L’administration de l’établissement vous contactera.'
            );
    }
}
