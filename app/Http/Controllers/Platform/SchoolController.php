<?php

namespace App\Http\Controllers\Platform;

use App\Actions\School\RegisterSchool;
use App\Actions\Settings\UpdatePublicSiteSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePublicSiteSettingsRequest;
use App\Http\Requests\Timetable\TimetableRequest;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolPublicGalleryItem;
use App\Models\SchoolPublicTestimonial;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use App\Services\AuditService;
use App\Support\PublicSiteResults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function index(Request $request): Response
    {
        $schools = School::query()
            ->withCount(['users', 'students', 'classRooms'])
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->query('status'), fn ($q, $s) => $q->where('is_active', $s === 'active'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Schools/Index', [
            'schools' => $schools,
            'filters' => [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Schools/Create');
    }

    public function store(Request $request, RegisterSchool $registerSchool): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'short_name' => 'required|string|max:50',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'quartier' => 'nullable|string|max:255',
            'department' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:5',
            'programs' => 'nullable|array',
            'programs.*' => 'string|max:100',
            'academic_year_name' => 'required|string|max:50',
            'academic_year_starts_on' => 'required|date',
            'academic_year_ends_on' => 'required|date|after:academic_year_starts_on',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
            'admin_phone' => 'nullable|string|max:30',
        ]);

        $schoolData = [
            'school_name' => $validated['school_name'],
            'short_name' => $validated['short_name'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'quartier' => $validated['quartier'] ?? null,
            'department' => $validated['department'],
            'country' => $validated['country'],
            'phone' => $validated['phone'],
            'primary_color' => $validated['primary_color'] ?? '#047857',
            'secondary_color' => $validated['secondary_color'] ?? '#0F766E',
            'accent_color' => $validated['accent_color'] ?? '#D97706',
            'languages' => $validated['languages'] ?? ['fr'],
            'programs' => $validated['programs'] ?? [],
            'academic_year_name' => $validated['academic_year_name'],
            'academic_year_starts_on' => $validated['academic_year_starts_on'],
            'academic_year_ends_on' => $validated['academic_year_ends_on'],
        ];

        $adminData = [
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => $validated['admin_password'],
            'phone' => $validated['admin_phone'],
        ];

        $result = $registerSchool->handle($schoolData, $adminData);

        app(AuditService::class)->log('school.created_by_platform_admin', $result['school'], [
            'by' => $request->user()->id,
        ]);

        return redirect()
            ->route('platform.schools.show', $result['school'])
            ->with('success', 'École « '.$result['school']->name.' » créée et activée. Identifiants administrateur : '.$result['admin']->email.' / '.$validated['admin_password']);
    }

    public function show(School $school): Response
    {
        $school->loadCount(['users', 'students', 'teachers', 'classRooms', 'academicYears']);

        $admins = User::query()
            ->where('school_id', $school->id)
            ->where('role', 'admin')
            ->get(['id', 'name', 'email', 'is_active']);

        $license = $school->licenses()->latest()->first();
        $latestPayments = $school->payments()->latest()->take(5)->get(['id', 'amount', 'status', 'reference', 'created_at']);

        return Inertia::render('Platform/Schools/Show', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
                'is_active' => $school->is_active,
                'created_at' => $school->created_at->format('d/m/Y'),
                'counts' => [
                    'users' => $school->users_count,
                    'students' => $school->students_count,
                    'teachers' => $school->teachers_count,
                    'classes' => $school->class_rooms_count,
                    'academic_years' => $school->academic_years_count,
                ],
            ],
            'admins' => $admins,
            'license' => $license ? [
                'id' => $license->id,
                'type' => $license->type->value,
                'type_label' => $license->type->label(),
                'status' => $license->status->value,
                'status_label' => $license->status->label(),
                'starts_at' => $license->starts_at?->format('d/m/Y'),
                'ends_at' => $license->ends_at?->format('d/m/Y'),
                'amount' => $license->amount,
                'payment_reference' => $license->payment_reference,
                'is_expired' => $license->isExpired(),
                'days_remaining' => $license->daysRemaining(),
            ] : null,
            'latestPayments' => $latestPayments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'reference' => $p->reference,
                'created_at' => $p->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    /**
     * Mini-site public - Édition par l'admin plateforme
     */
    public function editPublicSite(School $school): Response
    {
        $setting = $school->settingsOrNew();
        $publicSite = data_get($setting->options, 'public_site', []);
        $publicSite = is_array($publicSite) ? $publicSite : [];
        $verifiedDomain = $school->domains()
            ->where('verified', true)
            ->orderByDesc('is_primary')
            ->value('domain');

        return Inertia::render('Platform/Schools/PublicSite', [
            'school' => [
                'id' => $school->id,
                'name' => $setting->school_name ?: $school->name,
                'slogan' => $setting->slogan,
                'logo_url' => $setting->logo_url,
                'primary_color' => $setting->primary_color,
                'secondary_color' => $setting->secondary_color,
                'address' => $setting->address,
                'phone' => $setting->phone,
                'email' => $setting->email,
                'slug' => $school->slug,
                'public_url' => $school->publicUrl(),
                'public_url_root' => rtrim((string) config('app.url'), '/'),
                'custom_domain_url' => $verifiedDomain
                    ? 'https://'.$verifiedDomain
                    : null,
            ],
            'publicSite' => [
                'mission' => $publicSite['mission'] ?? '',
                'vision' => $publicSite['vision'] ?? '',
                'values' => $publicSite['values'] ?? [],
                'features' => $publicSite['features'] ?? [],
                'results' => PublicSiteResults::normalize(
                    $publicSite['results'] ?? [],
                    $publicSite['results_headline'] ?? null,
                    $publicSite['results_note'] ?? null,
                ),
                'results_headline' => $publicSite['results_headline'] ?? '',
                'results_note' => $publicSite['results_note'] ?? '',
                'tuition_currency' => $publicSite['tuition_currency'] ?? '',
                'tuition_note' => $publicSite['tuition_note'] ?? '',
                'tuition_fees' => $publicSite['tuition_fees'] ?? [],
                'curriculum_intro' => $publicSite['curriculum_intro'] ?? '',
                'curriculum' => $publicSite['curriculum'] ?? [],
                'calendar_intro' => $publicSite['calendar_intro'] ?? '',
                'calendar' => $publicSite['calendar'] ?? [],
                'hero_image_url' => ! empty($publicSite['hero_image_path'])
                    ? Storage::disk('public')->url($publicSite['hero_image_path'])
                    : null,
            ],
            'testimonials' => SchoolPublicTestimonial::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (SchoolPublicTestimonial $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'relationship' => $item->relationship,
                    'message' => $item->message,
                    'photo_url' => $item->photo_url,
                    'is_active' => $item->is_active,
                    'sort_order' => $item->sort_order,
                ]),
            'gallery' => SchoolPublicGalleryItem::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (SchoolPublicGalleryItem $item) => [
                    'id' => $item->id,
                    'image_url' => $item->image_url,
                    'title' => $item->title,
                    'description' => $item->description,
                    'alt_text' => $item->alt_text,
                    'is_active' => $item->is_active,
                    'sort_order' => $item->sort_order,
                ]),
        ]);
    }

    public function updatePublicSite(
        UpdatePublicSiteSettingsRequest $request,
        UpdatePublicSiteSettings $action,
        School $school,
        AuditService $audit,
    ): RedirectResponse {
        $action->handle(
            $school,
            $request->safe()->except(['hero_image', 'remove_hero_image']),
            $request->file('hero_image'),
            $request->boolean('remove_hero_image')
        );

        $audit->log('settings.updated', $school, [
            'scope' => 'public_site',
            'by_platform_admin' => true,
        ]);

        return back()->with('success', 'Le contenu public a été enregistré.');
    }

    /**
     * Emploi du temps - Affichage par l'admin plateforme
     */
    public function showTimetable(Request $request, School $school): Response
    {
        $academicYears = AcademicYear::where('school_id', $school->id)->orderByDesc('starts_on')->get(['id', 'name', 'is_current']);

        $selectedYearId = $request->integer('academic_year_id')
            ?: $academicYears->firstWhere('is_current', true)?->id
            ?: $academicYears->first()?->id;

        $slots = Timetable::with([
            'classRoom:id,name',
            'subject:id,name',
            'teacher.user:id,name,photo_path',
            'room:id,name',
        ])
            ->where('school_id', $school->id)
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->integer('class_id')))
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Timetable $slot) => $this->presentSlot($slot));

        $teachers = Teacher::query()
            ->where('school_id', $school->id)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->with([
                'user:id,name,is_active',
                'assignments.subject:id,name',
            ])
            ->get()
            ->filter(fn (Teacher $teacher) => $teacher->user !== null)
            ->map(fn (Teacher $teacher) => [
                'id' => $teacher->id,
                'name' => $teacher->user->name,
                'is_active' => $teacher->user->is_active,
                'subject_ids' => $teacher->assignments
                    ->pluck('subject_id')
                    ->unique()
                    ->values()
                    ->all(),
            ])
            ->sortBy('name')
            ->values();

        return Inertia::render('Platform/Schools/Timetable', [
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'slug' => $school->slug,
            ],
            'slots' => $slots,
            'days' => collect(Timetable::days())->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            'academicYears' => $academicYears->map(fn ($year) => [
                'id' => $year->id,
                'name' => $year->name,
                'is_current' => $year->is_current,
            ])->values(),
            'selectedAcademicYearId' => $selectedYearId,
            'classes' => ClassRoom::where('school_id', $school->id)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('school_id', $school->id)->orderBy('name')->get(['id', 'name']),
            'rooms' => Room::where('school_id', $school->id)->orderBy('building')->orderBy('name')->get(['id', 'name', 'building']),
            'teachers' => $teachers,
            'filters' => $request->only(['academic_year_id', 'class_id']),
        ]);
    }

    public function storeTimetableSlot(TimetableRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('create', Timetable::class);

        Timetable::create(array_merge($request->validated(), ['school_id' => $school->id]));

        return back()->with('success', 'Créneau ajouté à l\'emploi du temps.');
    }

    public function updateTimetableSlot(TimetableRequest $request, School $school, Timetable $timetable): RedirectResponse
    {
        Gate::authorize('update', $timetable);

        if ($timetable->school_id !== $school->id) {
            abort(403);
        }

        $timetable->update($request->validated());

        return back()->with('success', 'Créneau mis à jour.');
    }

    public function destroyTimetableSlot(School $school, Timetable $timetable): RedirectResponse
    {
        Gate::authorize('delete', $timetable);

        if ($timetable->school_id !== $school->id) {
            abort(403);
        }

        $timetable->delete();

        return back()->with('success', 'Créneau supprimé.');
    }

    private function presentSlot(Timetable $slot): array
    {
        return [
            'id' => $slot->id,
            'day_of_week' => $slot->day_of_week,
            'day_label' => $slot->day_label,
            'starts_at' => $slot->formatTime($slot->starts_at),
            'ends_at' => $slot->formatTime($slot->ends_at),
            'time_range' => $slot->time_range,
            'room' => $slot->room,
            'class_id' => $slot->class_id,
            'class_name' => $slot->classRoom?->name,
            'subject_id' => $slot->subject_id,
            'subject_name' => $slot->subject?->name,
            'teacher_id' => $slot->teacher_id,
            'teacher_name' => $slot->teacher?->user?->name,
            'teacher_photo_url' => $slot->teacher?->user?->photoUrl(),
            'room_id' => $slot->room_id,
            'room_name' => $slot->room?->name,
            'academic_year_id' => $slot->academic_year_id,
        ];
    }

    public function toggle(School $school, Request $request): RedirectResponse
    {
        $school->update(['is_active' => ! $school->is_active]);

        app(AuditService::class)->log('school.toggled', $school, [
            'new_status' => $school->is_active ? 'active' : 'suspended',
            'by' => $request->user()->id,
        ]);

        return back()->with('success', $school->is_active
            ? "École « {$school->name} » réactivée."
            : "École « {$school->name} » suspendue.");
    }

    public function destroy(School $school, Request $request): RedirectResponse
    {
        $name = $school->name;
        $school->delete();

        app(AuditService::class)->log('school.deleted', $school, [
            'name' => $name,
            'by' => $request->user()->id,
        ]);

        return redirect()->route('platform.schools.index')
            ->with('success', "École « {$name} » supprimée.");
    }
}