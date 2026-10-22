<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\School;
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
        $school = $resolver->resolve($request);

        if ($school) {
            return $this->renderSchool($request, $school);
        }

        /*
         * Aucun établissement résolu. Deux cas distincts :
         * - le domaine est revendiqué par une école mais pas encore vérifié : son site
         *   public n'est pas prêt, on ne substitue donc pas la landing CoriSchool ;
         * - on est sur l'hôte de la plateforme : c'est la page d'accueil de CoriSchool.
         */
        if ($resolver->hasSchoolDomain($request->getHost())) {
            return redirect()->route('login');
        }

        return app(CoriSchoolLandingController::class)();
    }

    public function bySlug(
        Request $request,
        TenantResolver $resolver,
        string $slug
    ): Response|RedirectResponse {
        if (($user = $request->user()) && ! $request->boolean('preview')) {
            return redirect()->route($user->role->homeRoute());
        }

        $school = $resolver->resolveBySlug($slug);
        abort_unless($school, 404);

        return $this->renderSchool($request, $school);
    }

    public function index(Request $request): Response
    {
        $locale = $request->get('locale', 'fr');
        $request->session()->put('locale', $locale);

        $query = School::query()
            ->where('is_active', true)
            ->with(['settings'])
            ->withCount(['students' => fn ($q) => $q->where('is_active', true)])
            ->withCount(['teachers' => fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('is_active', true))])
            ->withCount('classRooms');

        // Search by name
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by school type
        if ($request->filled('school_type')) {
            $query->whereHas('settings', fn ($q) => $q->where('school_type', $request->string('school_type')->toString()));
        }

        // Filter by country
        if ($request->filled('country')) {
            $query->whereHas('settings', fn ($q) => $q->where('country', $request->string('country')->toString()));
        }

        // Filter by city
        if ($request->filled('city')) {
            $query->whereHas('settings', fn ($q) => $q->where('city', $request->string('city')->toString()));
        }

        // Filter by minimum students
        if ($request->filled('min_students')) {
            $query->having('students_count', '>=', (int) $request->string('min_students')->toString());
        }

        // Filter by bilingual (FR/EN)
        if ($request->boolean('bilingual')) {
            $query->whereHas('settings', fn ($q) => $q->whereJsonContains('languages', 'fr')->whereJsonContains('languages', 'en'));
        }

        // Sort
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        switch ($sort) {
            case 'name':
                $query->orderBy('name', $direction);
                break;
            case 'students':
                $query->orderBy('students_count', $direction);
                break;
            case 'teachers':
                $query->orderBy('teachers_count', $direction);
                break;
            case 'classes':
                $query->orderBy('class_rooms_count', $direction);
                break;
            case 'newest':
                $query->latest('id');
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        $perPage = min(max((int) $request->integer('per_page', 12), 6), 48);
        $schools = $query->paginate($perPage)->withQueryString();

        // Filter by quartier
        if ($request->filled('quartier')) {
            $query->whereHas('settings', fn ($q) => $q->where('quartier', $request->string('quartier')->toString()));
        }

        // Filter by program
        if ($request->filled('program')) {
            $query->whereHas('settings', fn ($q) => $q->whereJsonContains('programs', $request->string('program')->toString()));
        }

        // Get filter options
        $schoolTypes = SchoolSetting::SCHOOL_TYPES;
        $countries = SchoolSetting::query()
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->pluck('country')
            ->sort()
            ->values();
        $cities = SchoolSetting::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->pluck('city')
            ->sort()
            ->values();
        $quartiers = SchoolSetting::query()
            ->whereNotNull('quartier')
            ->where('quartier', '!=', '')
            ->distinct()
            ->pluck('quartier')
            ->sort()
            ->values();
        $programs = SchoolSetting::query()
            ->whereNotNull('programs')
            ->where('programs', '!=', '[]')
            ->pluck('programs')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        // Transform schools for the view
        $schoolsData = $schools->getCollection()->map(function ($school) {
            $setting = $school->settings;
            $publicSite = data_get($setting?->options, 'public_site', []);
            $heroImagePath = $publicSite['hero_image_path'] ?? null;
            $heroImageUrl = $heroImagePath
                ? Storage::disk('public')->url($heroImagePath)
                : null;
            $languages = $setting?->languages ?? [];
            $programs = $setting?->programs ?? [];

            return [
                'id' => $school->id,
                'name' => $setting?->school_name ?: $school->name,
                'slug' => $school->slug,
                'short_name' => $setting?->short_name,
                'slogan' => $setting?->slogan,
                'description' => $setting?->description,
                'school_type' => $setting?->school_type,
                'school_type_label' => $setting?->school_type ? (SchoolSetting::SCHOOL_TYPES[$setting->school_type] ?? $setting->school_type) : null,
                'city' => $setting?->city,
                'quartier' => $setting?->quartier,
                'country' => $setting?->country,
                'address' => $setting?->address,
                'phone' => $setting?->phone,
                'email' => $setting?->email,
                'website' => $setting?->website,
                'logo_url' => $setting?->logo_url,
                'hero_image_url' => $heroImageUrl,
                'primary_color' => $setting?->primary_color ?: '#059669',
                'secondary_color' => $setting?->secondary_color ?: '#2563EB',
                'accent_color' => $setting?->accent_color ?: '#D97706',
                'founded_year' => $setting?->founded_year,
                'students_count' => $school->students_count,
                'teachers_count' => $school->teachers_count,
                'classes_count' => $school->class_rooms_count,
                'public_url' => $school->publicUrl(),
                'languages' => $languages,
                'programs' => $programs,
                'is_bilingual' => in_array('fr', $languages) && in_array('en', $languages),
            ];
        });

        return Inertia::render('Public/SchoolListing', [
            'schools' => [
                'data' => $schoolsData,
                'current_page' => $schools->currentPage(),
                'last_page' => $schools->lastPage(),
                'per_page' => $schools->perPage(),
                'total' => $schools->total(),
                'from' => $schools->firstItem(),
                'to' => $schools->lastItem(),
            ],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'school_type' => $request->string('school_type')->toString(),
                'country' => $request->string('country')->toString(),
                'city' => $request->string('city')->toString(),
                'quartier' => $request->string('quartier')->toString(),
                'program' => $request->string('program')->toString(),
                'min_students' => $request->string('min_students')->toString(),
                'bilingual' => $request->boolean('bilingual'),
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
            'options' => [
                'school_types' => $schoolTypes,
                'countries' => $countries,
                'cities' => $cities,
                'quartiers' => $quartiers,
                'programs' => $programs,
            ],
            'locale' => $locale,
        ]);
    }

    private function renderSchool(Request $request, ?School $school): Response|RedirectResponse
    {
        if (! $school) {
            return redirect()->route('login');
        }

        abort_unless($school->is_active, 404);

        $preview = $request->boolean('preview');
        $previewDashboardUrl = $preview && $request->user()
            ? route($request->user()->role->homeRoute())
            : null;

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

        $tuitionFees = collect($publicSite['tuition_fees'] ?? [])
            ->filter(fn ($fee) => is_array($fee) && filled($fee['label'] ?? null))
            ->map(fn ($fee) => [
                'label' => Str::limit(strip_tags((string) $fee['label']), 100, ''),
                'amount' => is_string($fee['amount'] ?? null) && filled($fee['amount'])
                    ? Str::limit(strip_tags($fee['amount']), 40, '')
                    : null,
                'note' => is_string($fee['note'] ?? null) && filled($fee['note'])
                    ? Str::limit(strip_tags($fee['note']), 200, '')
                    : null,
            ])
            ->take(10)
            ->values();

        $curriculum = collect($publicSite['curriculum'] ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['title'] ?? null))
            ->map(fn ($item) => [
                'title' => Str::limit(strip_tags((string) $item['title']), 100, ''),
                'description' => is_string($item['description'] ?? null) && filled($item['description'])
                    ? Str::limit(strip_tags($item['description']), 800, '')
                    : null,
            ])
            ->take(8)
            ->values();

        $calendar = collect($publicSite['calendar'] ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['label'] ?? null))
            ->map(fn ($item) => [
                'label' => Str::limit(strip_tags((string) $item['label']), 120, ''),
                'date' => is_string($item['date'] ?? null) && filled($item['date'])
                    ? Str::limit(strip_tags($item['date']), 120, '')
                    : null,
                'description' => is_string($item['description'] ?? null) && filled($item['description'])
                    ? Str::limit(strip_tags($item['description']), 300, '')
                    : null,
            ])
            ->take(20)
            ->values();

        $tuitionCurrency = is_string($publicSite['tuition_currency'] ?? null)
            ? Str::limit(strip_tags($publicSite['tuition_currency']), 12, '')
            : null;
        $tuitionNote = is_string($publicSite['tuition_note'] ?? null)
            ? Str::limit(strip_tags($publicSite['tuition_note']), 300, '')
            : null;
        $curriculumIntro = is_string($publicSite['curriculum_intro'] ?? null)
            ? Str::limit(strip_tags($publicSite['curriculum_intro']), 1000, '')
            : null;
        $calendarIntro = is_string($publicSite['calendar_intro'] ?? null)
            ? Str::limit(strip_tags($publicSite['calendar_intro']), 1000, '')
            : null;

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
        $heroImageUrl = $heroImagePath
            ? Storage::disk('public')->url($heroImagePath)
            : null;
        $website = $setting?->website;
        if (! in_array(parse_url((string) $website, PHP_URL_SCHEME), ['http', 'https'], true)) {
            $website = null;
        }

        $verifiedDomain = $school->domains()
            ->where('verified', true)
            ->orderByDesc('is_primary')
            ->value('domain');
        $customDomainUrl = $verifiedDomain ? 'https://'.$verifiedDomain : null;

        $canonicalUrl = $school->publicUrl();
        $platformUrl = rtrim((string) config('app.url'), '/');
        $logoUrl = $setting?->logo_url ?: $branding['logo_url'];

        $metaDescription = Str::limit(
            strip_tags($description ?: ($setting?->slogan ?: "Bienvenue à {$name}.")),
            170,
            ''
        );

        $metaTitle = $setting?->slogan
            ? $name.' – '.strip_tags($setting->slogan)
            : $name;

        $postalAddress = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $setting?->address,
            'addressLocality' => $setting?->city,
            'addressCountry' => $setting?->country,
        ], fn ($value) => filled($value));

        $structuredData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => $name,
            'alternateName' => $setting?->short_name ?: null,
            'url' => $canonicalUrl,
            'logo' => $logoUrl,
            'image' => $heroImageUrl,
            'description' => $metaDescription,
            'slogan' => $setting?->slogan,
            'foundingDate' => $foundedYear ? (string) $foundedYear : null,
            'address' => count($postalAddress) > 1 ? $postalAddress : null,
            'telephone' => $setting?->phone,
            'email' => $setting?->email,
            'numberOfStudents' => $stats['students'] ?: null,
            'sameAs' => $website ? [$website] : null,
        ], fn ($value) => filled($value) || is_array($value));

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
                'results_headline' => is_string($publicSite['results_headline'] ?? null)
                    ? Str::limit(strip_tags($publicSite['results_headline']), 160, '')
                    : null,
                'results_note' => is_string($publicSite['results_note'] ?? null)
                    ? Str::limit(strip_tags($publicSite['results_note']), 160, '')
                    : null,
                'values' => $values,
                'logo_url' => $logoUrl,
                'hero_image_url' => $heroImageUrl,
                'primary_color' => $setting?->primary_color ?: '#059669',
                'secondary_color' => $setting?->secondary_color ?: '#2563EB',
                'accent_color' => $setting?->accent_color ?: '#D97706',
                'address' => $setting?->address,
                'city' => $setting?->city,
                'country' => $setting?->country,
                'phone' => $setting?->phone,
                'email' => $setting?->email,
                'website' => $website,
                'custom_domain_url' => $customDomainUrl,
                'founded_year' => $foundedYear,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'canonical_url' => $canonicalUrl,
                'public_url' => $canonicalUrl,
                'platform_url' => $platformUrl,
                'demo_url' => $platformUrl.'/demander-une-demonstration',
            ],
            'structuredData' => $structuredData,
            'features' => $features,
            'stats' => $stats,
            'testimonials' => $testimonials,
            'gallery' => $gallery,
            'tuition' => [
                'currency' => $tuitionCurrency,
                'note' => $tuitionNote,
                'fees' => $tuitionFees,
            ],
            'curriculum' => [
                'intro' => $curriculumIntro,
                'items' => $curriculum,
            ],
            'calendar' => [
                'intro' => $calendarIntro,
                'items' => $calendar,
            ],
            'preview' => $preview,
            'preview_dashboard_url' => $previewDashboardUrl,
            'preinscription_url' => route('public.school.preinscription', $school->slug),
        ]);
    }
}
