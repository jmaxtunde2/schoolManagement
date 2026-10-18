<?php

namespace App\Http\Controllers\Platform;

use App\Enums\ExamDifficulty;
use App\Enums\ExamResourceStatus;
use App\Enums\ExamResourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExamBank\ExamResourceRequest;
use App\Http\Requests\ExamBank\PlatformLevelRequest;
use App\Http\Requests\ExamBank\PlatformSubjectRequest;
use App\Models\ExamResource;
use App\Models\ExamResourceAttachment;
use App\Models\PlatformLevel;
use App\Models\PlatformSubject;
use App\Services\ExamBank\ExamFileDownloader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Administration de la banque d'épreuves au niveau plateforme.
 */
class ExamBankController extends Controller
{
    public function index(Request $request): Response
    {
        $resources = ExamResource::query()
            ->with(['level:id,name', 'subject:id,name', 'creator:id,name'])
            ->withCount('attachments')
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->query('level'), fn ($q, $level) => $q->where('platform_level_id', $level))
            ->when($request->query('subject'), fn ($q, $subject) => $q->where('platform_subject_id', $subject))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('difficulty'), fn ($q, $difficulty) => $q->where('difficulty', $difficulty))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ExamResource $resource) => $this->listPayload($resource));

        return Inertia::render('Platform/ExamBank/Index', [
            'resources' => $resources,
            'filters' => $request->only(['search', 'level', 'subject', 'type', 'difficulty', 'status']),
            'levels' => PlatformLevel::active()->get(['id', 'name']),
            'subjects' => PlatformSubject::active()->get(['id', 'name']),
            'types' => ExamResourceType::options(),
            'difficulties' => ExamDifficulty::options(),
            'statuses' => ExamResourceStatus::options(),
            'stats' => [
                'total' => ExamResource::count(),
                'published' => ExamResource::where('status', ExamResourceStatus::Published->value)->count(),
                'draft' => ExamResource::where('status', ExamResourceStatus::Draft->value)->count(),
                'archived' => ExamResource::where('status', ExamResourceStatus::Archived->value)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/ExamBank/Form', $this->formPayload());
    }

    public function store(ExamResourceRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['document_file', 'answer_key_file']);
        $data['school_id'] = null;
        $data['created_by'] = $request->user()->id;
        $data['published_at'] = $this->resolvePublishedAt($data['status'] ?? null);

        $resource = ExamResource::create($data);

        $this->storeAttachment($resource, $request->file('document_file'), ExamResourceAttachment::KIND_DOCUMENT);
        $this->storeAttachment($resource, $request->file('answer_key_file'), ExamResourceAttachment::KIND_ANSWER_KEY);

        return redirect()
            ->route('platform.exam-bank.show', $resource)
            ->with('success', 'Ressource ajoutée à la banque d\'épreuves.');
    }

    public function show(ExamResource $examResource): Response
    {
        $examResource->load(['level:id,name', 'subject:id,name', 'creator:id,name']);
        $examResource->increment('views_count');

        return Inertia::render('Platform/ExamBank/Show', [
            'resource' => $this->detailPayload($examResource),
            'statuses' => ExamResourceStatus::options(),
        ]);
    }

    public function edit(ExamResource $examResource): Response
    {
        $examResource->load('attachments');

        return Inertia::render('Platform/ExamBank/Form', [
            ...$this->formPayload(),
            'resource' => $this->detailPayload($examResource),
        ]);
    }

    public function update(ExamResourceRequest $request, ExamResource $examResource): RedirectResponse
    {
        Gate::authorize('update', $examResource);

        $data = $request->safe()->except(['document_file', 'answer_key_file']);
        $data['published_at'] = $this->resolvePublishedAt($data['status'] ?? null, $examResource);

        $examResource->update($data);

        if ($request->hasFile('document_file')) {
            $this->replaceAttachments($examResource, ExamResourceAttachment::KIND_DOCUMENT, $request->file('document_file'));
        }

        if ($request->hasFile('answer_key_file')) {
            $this->replaceAttachments($examResource, ExamResourceAttachment::KIND_ANSWER_KEY, $request->file('answer_key_file'));
        }

        return redirect()
            ->route('platform.exam-bank.show', $examResource)
            ->with('success', 'Ressource mise à jour.');
    }

    public function destroy(ExamResource $examResource): RedirectResponse
    {
        Gate::authorize('delete', $examResource);

        $examResource->attachments->each(function (ExamResourceAttachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        });

        $examResource->delete();

        return redirect()
            ->route('platform.exam-bank.index')
            ->with('success', 'Ressource archivée supprimée.');
    }

    public function updateStatus(Request $request, ExamResource $examResource): RedirectResponse
    {
        Gate::authorize('publish', $examResource);

        $validated = $request->validate([
            'status' => ['required', Rule::in(ExamResourceStatus::values())],
        ]);

        $status = ExamResourceStatus::from($validated['status']);

        $examResource->update([
            'status' => $status,
            'published_at' => $status === ExamResourceStatus::Published
                ? ($examResource->published_at ?? now())
                : null,
        ]);

        return back()->with('success', 'Statut de la ressource mis à jour.');
    }

    public function destroyAttachment(ExamResource $examResource, ExamResourceAttachment $attachment): RedirectResponse
    {
        Gate::authorize('update', $examResource);
        $this->ensureAttachmentBelongs($examResource, $attachment);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Fichier supprimé.');
    }

    public function downloadAttachment(
        ExamResource $examResource,
        ExamResourceAttachment $attachment,
        ExamFileDownloader $downloader,
    ): BinaryFileResponse|StreamedResponse {
        Gate::authorize('download', $examResource);
        $this->ensureAttachmentBelongs($examResource, $attachment);
        abort_unless($attachment->exists(), 404);

        $examResource->increment('downloads_count');

        return $downloader->download($attachment);
    }

    public function references(): Response
    {
        return Inertia::render('Platform/ExamBank/References', [
            'levels' => PlatformLevel::orderBy('position')->orderBy('name')->get(['id', 'name', 'code', 'position', 'is_active']),
            'subjects' => PlatformSubject::orderBy('position')->orderBy('name')->get(['id', 'name', 'code', 'color', 'position', 'is_active']),
        ]);
    }

    public function storeLevel(PlatformLevelRequest $request): RedirectResponse
    {
        PlatformLevel::create($request->validated());

        return back()->with('success', 'Niveau de référence ajouté.');
    }

    public function updateLevel(PlatformLevelRequest $request, PlatformLevel $platformLevel): RedirectResponse
    {
        $platformLevel->update($request->validated());

        return back()->with('success', 'Niveau de référence mis à jour.');
    }

    public function destroyLevel(PlatformLevel $platformLevel): RedirectResponse
    {
        Gate::authorize('create', ExamResource::class);

        $platformLevel->delete();

        return back()->with('success', 'Niveau de référence supprimé.');
    }

    public function storeSubject(PlatformSubjectRequest $request): RedirectResponse
    {
        PlatformSubject::create($request->validated());

        return back()->with('success', 'Matière de référence ajoutée.');
    }

    public function updateSubject(PlatformSubjectRequest $request, PlatformSubject $platformSubject): RedirectResponse
    {
        $platformSubject->update($request->validated());

        return back()->with('success', 'Matière de référence mise à jour.');
    }

    public function destroySubject(PlatformSubject $platformSubject): RedirectResponse
    {
        Gate::authorize('create', ExamResource::class);

        $platformSubject->delete();

        return back()->with('success', 'Matière de référence supprimée.');
    }

    private function formPayload(): array
    {
        return [
            'levels' => PlatformLevel::active()->get(['id', 'name']),
            'subjects' => PlatformSubject::active()->get(['id', 'name']),
            'types' => ExamResourceType::options(),
            'difficulties' => ExamDifficulty::options(),
            'statuses' => ExamResourceStatus::options(),
        ];
    }

    private function resolvePublishedAt(?string $status, ?ExamResource $resource = null): ?string
    {
        if ($status !== ExamResourceStatus::Published->value) {
            return null;
        }

        return $resource?->published_at ?? now();
    }

    private function storeAttachment(ExamResource $resource, $file, string $kind): void
    {
        if (! $file) {
            return;
        }

        $path = $file->store("exam-bank/{$resource->id}", 'local');

        $resource->attachments()->create([
            'kind' => $kind,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    private function replaceAttachments(ExamResource $resource, string $kind, $file): void
    {
        $resource->attachments()->where('kind', $kind)->get()->each(function (ExamResourceAttachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        });

        $this->storeAttachment($resource, $file, $kind);
    }

    private function ensureAttachmentBelongs(ExamResource $resource, ExamResourceAttachment $attachment): void
    {
        abort_unless($attachment->exam_resource_id === $resource->id, 404);
    }

    private function listPayload(ExamResource $resource): array
    {
        return [
            'id' => $resource->id,
            'title' => $resource->title,
            'description' => $resource->description,
            'type' => $resource->type->value,
            'type_label' => $resource->type->label(),
            'difficulty' => $resource->difficulty->value,
            'difficulty_label' => $resource->difficulty->label(),
            'status' => $resource->status->value,
            'status_label' => $resource->status->label(),
            'is_free' => $resource->is_free,
            'level' => $resource->level?->name,
            'subject' => $resource->subject?->name,
            'creator' => $resource->creator?->name,
            'attachments_count' => $resource->attachments_count ?? 0,
            'has_answer_key' => $resource->answerKeys()->exists(),
            'downloads_count' => $resource->downloads_count,
            'views_count' => $resource->views_count,
            'created_at' => $resource->created_at?->format('d/m/Y'),
            'updated_at' => $resource->updated_at?->format('d/m/Y'),
        ];
    }

    private function detailPayload(ExamResource $resource): array
    {
        return [
            ...$this->listPayload($resource),
            'duration_minutes' => $resource->duration_minutes,
            'academic_year' => $resource->academic_year,
            'period' => $resource->period,
            'content' => $resource->content,
            'platform_level_id' => $resource->platform_level_id,
            'platform_subject_id' => $resource->platform_subject_id,
            'published_at' => $resource->published_at?->format('d/m/Y H:i'),
            'attachments' => $resource->attachments()->get()->map(fn (ExamResourceAttachment $attachment) => [
                'id' => $attachment->id,
                'kind' => $attachment->kind,
                'is_answer_key' => $attachment->isAnswerKey(),
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'is_image' => $attachment->isImage(),
                'exists' => $attachment->exists(),
            ])->values(),
        ];
    }
}
