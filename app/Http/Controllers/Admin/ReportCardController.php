<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Academic\GenerateReportCards;
use App\Actions\Academic\PublishReportCard;
use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Models\ReportCard;
use App\Services\Academic\ReportCardPdfService;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCardController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ReportCard::class);
        $cards = ReportCard::query()
            ->with(['student:id,first_name,last_name,matricule', 'classRoom:id,name', 'period:id,name', 'academicYear:id,name'])
            ->when($request->user()->isTeacher(), function ($query) use ($request) {
                $classIds = $request->user()->teacher?->classes()->pluck('classes.id') ?? collect();
                $query->whereIn('class_room_id', $classIds);
            })
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_room_id', $request->integer('class_id')))
            ->when($request->filled('period_id'), fn ($query) => $query->where('academic_period_id', $request->integer('period_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (ReportCard $reportCard) => [
                'id' => $reportCard->id,
                'version' => $reportCard->version,
                'status' => $reportCard->status->value,
                'general_average' => (float) $reportCard->general_average,
                'rank' => $reportCard->rank,
                'total_students' => $reportCard->total_students,
                'updated_at' => $reportCard->updated_at?->toDateTimeString(),
                'student' => [
                    'id' => $reportCard->student->id,
                    'first_name' => $reportCard->student->first_name,
                    'last_name' => $reportCard->student->last_name,
                    'matricule' => $reportCard->student->matricule,
                ],
                'class_room' => $reportCard->classRoom
                    ? ['id' => $reportCard->classRoom->id, 'name' => $reportCard->classRoom->name]
                    : null,
                'period' => $reportCard->period
                    ? ['id' => $reportCard->period->id, 'name' => $reportCard->period->name]
                    : null,
                'academic_year' => $reportCard->academicYear
                    ? ['id' => $reportCard->academicYear->id, 'name' => $reportCard->academicYear->name]
                    : null,
            ]);

        $classesQuery = ClassRoom::query()->orderBy('name');
        if ($request->user()->isTeacher()) {
            $assignedClassIds = $request->user()->teacher?->classes()->pluck('classes.id') ?? collect();
            $classesQuery->whereIn('id', $assignedClassIds);
        }

        return Inertia::render('Admin/Academic/ReportCards/Index', [
            'reportCards' => $cards,
            'classes' => $classesQuery->get(['id', 'name']),
            'periods' => AcademicPeriod::query()->with('academicYear:id,name')->orderByDesc('academic_year_id')->orderBy('position')->get(['id', 'academic_year_id', 'name', 'position'])->map(fn ($period) => ['id' => $period->id, 'name' => $period->name, 'academic_year' => $period->academicYear?->name]),
            'filters' => $request->only(['class_id', 'period_id', 'status']),
            'routePrefix' => $request->routeIs('censeur.*')
                ? 'censeur'
                : ($request->routeIs('teacher.*')
                    ? 'teacher'
                    : ($request->routeIs('secretary.*') ? 'secretary' : 'admin')),
        ]);
    }

    public function show(Request $request, ReportCard $reportCard): Response
    {
        Gate::authorize('view', $reportCard);
        $reportCard->load(['items.subject', 'student.classRoom', 'classRoom', 'academicYear', 'period']);

        return Inertia::render('Admin/Academic/ReportCards/Show', [
            'reportCard' => $this->serialize($reportCard),
            'routePrefix' => $request->routeIs('parent.*')
                ? 'parent'
                : ($request->routeIs('censeur.*')
                    ? 'censeur'
                    : ($request->routeIs('teacher.*')
                        ? 'teacher'
                        : ($request->routeIs('secretary.*') ? 'secretary' : 'admin'))),
            'canEditComments' => Gate::allows('updateComments', $reportCard),
            'canPublish' => Gate::allows('publish', $reportCard),
            'canRegenerate' => Gate::allows('regenerate', $reportCard),
        ]);
    }

    public function updateComments(Request $request, ReportCard $reportCard): RedirectResponse
    {
        Gate::authorize('updateComments', $reportCard);
        $data = $request->validate([
            'appreciation' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.teacher_comment' => ['nullable', 'string', 'max:1000'],
            'items.*.appreciation' => ['nullable', 'string', 'max:255'],
        ]);

        $reportCard->update(['appreciation' => $data['appreciation'] ?? null]);
        foreach ($data['items'] as $itemData) {
            $reportCard->items()->whereKey($itemData['id'])->update([
                'teacher_comment' => $itemData['teacher_comment'] ?? null,
                'appreciation' => $itemData['appreciation'] ?? null,
            ]);
        }

        app(AuditService::class)->log('report_card.comments_updated', $reportCard, [
            'student_id' => $reportCard->student_id,
            'version' => $reportCard->version,
        ]);

        return back()->with('success', 'Les appréciations ont été enregistrées.');
    }

    public function publish(Request $request, ReportCard $reportCard, PublishReportCard $action): RedirectResponse
    {
        Gate::authorize('publish', $reportCard);

        try {
            $action->handle($reportCard, $request->user()->id);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Bulletin publié et transmis aux parents.');
    }

    public function regenerate(Request $request, ReportCard $reportCard, GenerateReportCards $action): RedirectResponse
    {
        Gate::authorize('regenerate', $reportCard);
        $classRoom = ClassRoom::withoutGlobalScopes()->where('school_id', $request->user()->school_id)->findOrFail($reportCard->class_room_id);
        $period = AcademicPeriod::withoutGlobalScopes()->where('school_id', $request->user()->school_id)->findOrFail($reportCard->academic_period_id);

        try {
            $result = $action->handle(
                $request->user()->school,
                $classRoom,
                $period,
                $request->user()->id,
                $reportCard->student_id,
                true
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route(
            $request->routeIs('censeur.*')
                ? 'censeur.report-cards.show'
                : ($request->routeIs('teacher.*') ? 'teacher.report-cards.show' : 'admin.report-cards.show'),
            $result['report_cards'][0]->id
        )->with('success', 'Une nouvelle version du bulletin a été générée.');
    }

    public function download(Request $request, ReportCard $reportCard, ReportCardPdfService $pdf, AuditService $audit): StreamedResponse
    {
        Gate::authorize('view', $reportCard);
        abort_unless($reportCard->status->value === 'published' || ! $request->user()->isParent(), 403);

        $path = $reportCard->pdf_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            $bytes = $pdf->render($reportCard);
            $path = "report-cards/{$reportCard->school_id}/{$reportCard->id}-v{$reportCard->version}.pdf";
            Storage::disk('local')->put($path, $bytes);
            $reportCard->update(['pdf_path' => $path, 'pdf_generated_at' => now()]);
            $audit->log('report_card.pdf_generated', $reportCard, ['version' => $reportCard->version]);
        }

        $audit->log('report_card.downloaded', $reportCard, ['version' => $reportCard->version]);

        $absolutePath = Storage::disk('local')->path($path);

        return response()->streamDownload(function () use ($absolutePath): void {
            $stream = fopen($absolutePath, 'rb');
            if ($stream !== false) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 'bulletin-'.$reportCard->student_id.'-v'.$reportCard->version.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function serialize(ReportCard $reportCard): array
    {
        return [
            'id' => $reportCard->id,
            'version' => $reportCard->version,
            'status' => $reportCard->status->value,
            'general_average' => (float) $reportCard->general_average,
            'rank' => $reportCard->rank,
            'total_students' => $reportCard->total_students,
            'appreciation' => $reportCard->appreciation,
            'attendance_summary' => $reportCard->attendance_summary ?? [],
            'snapshot' => $reportCard->snapshot ?? [],
            'generated_at' => $reportCard->generated_at?->toDateTimeString(),
            'published_at' => $reportCard->published_at?->toDateTimeString(),
            'items' => $reportCard->items->map(fn ($item) => [
                'id' => $item->id,
                'subject_id' => $item->subject_id,
                'subject_name' => $item->subject_name ?: $item->subject?->name,
                'coefficient' => (float) $item->coefficient,
                'average' => $item->average !== null ? (float) $item->average : null,
                'evaluation_count' => $item->evaluation_count,
                'rank' => $item->rank,
                'appreciation' => $item->appreciation,
                'teacher_comment' => $item->teacher_comment,
            ])->values(),
        ];
    }
}