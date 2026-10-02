<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\SchoolPublicGalleryItem;
use App\Models\SchoolPublicTestimonial;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\Tenancy\TenantResolver;
use App\Support\SchoolBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicSchoolController extends Controller
{
    public function __invoke(Request $request, TenantResolver $resolver): Response|RedirectResponse
    {
        return $this->renderSchool($request, $resolver->resolve($request));
    }

    public function bySlug(
        Request $request,
        TenantResolver $resolver,
        string $slug
    ): Response|RedirectResponse {
        if ($user = $request->user()) {
            return redirect()->route($user->role->homeRoute());
        }

        $school = $resolver->resolveBySlug($slug);
        abort_unless($school, 404);

        return $this->renderSchool($request, $school);
    }

    private function renderSchool(Request $request, ?\App\Models\School $school): Response|RedirectResponse
    {
        if (! $school) {
            return redirect()->route('login');
        }

        abort_unless($school->is_active, 404);

        $setting = SchoolSetting::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->first();
        $publicSite = data_get($setting?->options, 'public_site', []);
        $publicSite = is_array($publicSite) ? $publicSite : [];
        $branding = SchoolBranding::for($school);
        $name = $setting?->school_name ?: $branding['name'];
        $description = $setting?->description;

        $features = collect($publicSite['features'] ?? [])
            ->filter(fn ($feature) => is_array($feature) && is_string($feature['title'] ?? null) && filled($feature['title']))
            ->map(fn ($feature) => [
                'title' => Str::limit(strip_tags($feature['title']), 100, ''),
                'description' => is_string($feature['description'] ?? null)
                    ? Str::limit(strip_tags($feature['description']), 500, '')
                    : '',
            ])
            ->take(6)
            ->values();

        $values = collect($publicSite['values'] ?? [])
            ->filter(fn ($value) => is_string($value) && filled($value))
            ->map(fn ($value) => Str::limit(strip_tags($value), 120, ''))
            ->take(5)
            ->values();

        $testimonials = SchoolPublicTestimonial::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(12)
            ->get(['id', 'name', 'relationship', 'message', 'photo_path'])
            ->map(fn (SchoolPublicTestimonial $testimonial) => [
                'name' => $testimonial->name,
                'relationship' => $testimonial->relationship,
                'message' => $testimonial->message,
                'photo_url' => $testimonial->photo_url,
            ])
            ->values();

        $gallery = SchoolPublicGalleryItem::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(18)
            ->get(['id', 'image_path', 'title', 'description', 'alt_text'])
            ->map(fn (SchoolPublicGalleryItem $item) => [
                'image_url' => $item->image_url,
                'title' => $item->title,
                'description' => $item->description,
                'alt_text' => $item->alt_text,
            ])
            ->values();

        $foundedYear = $setting?->founded_year;
        $stats = [
            'students' => Student::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->where('is_active', true)
                ->count(),
            'teachers' => Teacher::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->count(),
            'classes' => ClassRoom::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->count(),
            'years_experience' => $foundedYear && $foundedYear < now()->year
                ? now()->year - $foundedYear
                : null,
        ];

        $heroImagePath = $publicSite['hero_image_path'] ?? null;
        $website = $setting?->website;
        if (! in_array(parse_url((string) $website, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $website = null;
        }

        $verifiedDomain = $school->domains()
            ->where('verified', true)
            ->orderByDesc('is_primary')
            ->value('domain');

        $metaDescription = Str::limit(
            strip_tags($description ?: ($setting?->slogan ?: "Bienvenue à {$name}.")),
            170,
            ''
        );

        return Inertia::render('Public/SchoolLanding', [
            'school' => [
                'name' => $name,
                'short_name' => $setting?->short_name ?: $name,
                'slogan' => $setting?->slogan,
                'description' => $description,
                'mission' => is_string($publicSite['mission'] ?? null)
                    ? strip_tags($publicSite['mission'])
                    : null,
                'vision' => is_string($publicSite['vision'] ?? null)
                    ? strip_tags($publicSite['vision'])
                    : null,
                'values' => $values,
                'logo_url' => $setting?->logo_url ?: $branding['logo_url'],
                'hero_image_url' => $heroImagePath
                    ? Storage::disk('public')->url($heroImagePath)
                    : null,
                'primary_color' => $setting?->primary_color ?: '#059669',
                'secondary_color' => $setting?->secondary_color ?: '#2563EB',
                'accent_color' => $setting?->accent_color ?: '#D97706',
                'address' => $setting?->address,
                'city' => $setting?->city,
                'country' => $setting?->country,
                'phone' => $setting?->phone,
                'email' => $setting?->email,
                'website' => $website,
                'founded_year' => $foundedYear,
                'meta_description' => $metaDescription,
                'canonical_url' => $request->url(),
                'public_url' => $verifiedDomain ? 'https://'.$verifiedDomain : null,
            ],
            'features' => $features,
            'stats' => $stats,
            'testimonials' => $testimonials,
            'gallery' => $gallery,
        ]);
    }
}