<?php

namespace App\Http\Controllers;

use App\Enums\ExamDifficulty;
use App\Enums\ExamResourceType;
use App\Models\ExamResource;
use App\Models\ExamResourceAttachment;
use App\Models\PlatformLevel;
use App\Models\PlatformSubject;
use App\Services\ExamBank\ExamFileDownloader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Consultation de la banque d'épreuves depuis un établissement
 * (enseignant, administrateur). Le même contrôleur est exposé sous les
 * préfixes `admin` et `teacher` ; le frontend résout les URLs grâce au
 * prop `routePrefix`.
 */
class ExamBankController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ExamResource::class);
        $user = $request->user();

        $resources = ExamResource::query()
            ->visibleTo($user)
            ->with(['level:id,name', 'subject:id,name', 'attachments'])
            ->withCount(['attachments', 'answerKeys'])
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
            ->latest('id')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ExamResource $resource) => $this->cardPayload($resource, $user));

        return Inertia::render('ExamBank/Index', [
            'resources' => $resources,
            'filters' => $request->only(['search', 'level', 'subject', 'type', 'difficulty']),
            'levels' => PlatformLevel::active()->get(['id', 'name']),
            'subjects' => PlatformSubject::active()->get(['id', 'name']),
            'types' => ExamResourceType::options(),
            'difficulties' => ExamDifficulty::options(),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function show(Request $request, ExamResource $examResource): Response
    {
        Gate::authorize('view', $examResource);
        $user = $request->user();

        $examResource->load(['level:id,name', 'subject:id,name', 'attachments']);
        $examResource->increment('views_count');

        $attachments = $examResource->attachments;
        $canAnswerKey = Gate::allows('downloadAnswerKey', $examResource);

        return Inertia::render('ExamBank/Show', [
            'resource' => [
                ...$this->cardPayload($examResource, $user),
                'content' => $examResource->content,
                'academic_year' => $examResource->academic_year,
                'period' => $examResource->period,
                'published_at' => $examResource->published_at?->format('d/m/Y'),
                'documents' => $attachments
                    ->where('kind', ExamResourceAttachment::KIND_DOCUMENT)
                    ->map(fn (ExamResourceAttachment $a) => $this->attachmentPayload($a))
                    ->values(),
                'answer_keys' => $canAnswerKey
                    ? $attachments
                        ->where('kind', ExamResourceAttachment::KIND_ANSWER_KEY)
                        ->map(fn (ExamResourceAttachment $a) => $this->attachmentPayload($a))
                        ->values()
                    : [],
            ],
            'canAnswerKey' => $canAnswerKey,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    public function download(
        ExamResource $examResource,
        ExamResourceAttachment $attachment,
        ExamFileDownloader $downloader,
    ): BinaryFileResponse|StreamedResponse {
        Gate::authorize('download', $examResource);
        $this->ensureAttachmentBelongs($examResource, $attachment);
        abort_if($attachment->isAnswerKey(), 404);
        abort_unless($attachment->exists(), 404);

        $examResource->increment('downloads_count');

        return $downloader->download($attachment);
    }

    public function downloadAnswerKey(
        ExamResource $examResource,
        ExamResourceAttachment $attachment,
        ExamFileDownloader $downloader,
    ): BinaryFileResponse|StreamedResponse {
        Gate::authorize('downloadAnswerKey', $examResource);
        $this->ensureAttachmentBelongs($examResource, $attachment);
        abort_unless($attachment->isAnswerKey(), 404);
        abort_unless($attachment->exists(), 404);

        return $downloader->download($attachment);
    }

    private function ensureAttachmentBelongs(ExamResource $resource, ExamResourceAttachment $attachment): void
    {
        abort_unless($attachment->exam_resource_id === $resource->id, 404);
    }

    private function routePrefix(Request $request): string
    {
        $name = $request->route()?->getName() ?? 'admin.exam-bank.index';

        return explode('.', $name)[0] ?: 'admin';
    }

    private function cardPayload(ExamResource $resource, $user): array
    {
        $attachments = $resource->relationLoaded('attachments')
            ? $resource->attachments
            : $resource->attachments()->get();

        $document = $attachments->firstWhere('kind', ExamResourceAttachment::KIND_DOCUMENT);
        $answerKey = $attachments->firstWhere('kind', ExamResourceAttachment::KIND_ANSWER_KEY);

        return [
            'id' => $resource->id,
            'title' => $resource->title,
            'description' => $resource->description,
            'type' => $resource->type->value,
            'type_label' => $resource->type->label(),
            'difficulty' => $resource->difficulty->value,
            'difficulty_label' => $resource->difficulty->label(),
            'duration_minutes' => $resource->duration_minutes,
            'is_free' => $resource->is_free,
            'is_platform_resource' => $resource->isPlatformResource(),
            'level' => $resource->level?->name,
            'subject' => $resource->subject?->name,
            'documents_count' => $resource->attachments_count ?? $attachments->where('kind', ExamResourceAttachment::KIND_DOCUMENT)->count(),
            'has_answer_key' => ($resource->answer_keys_count ?? $attachments->where('kind', ExamResourceAttachment::KIND_ANSWER_KEY)->count()) > 0,
            'primary_document_id' => $document?->id,
            'primary_answer_key_id' => $answerKey?->id,
            'can_download_answer_key' => Gate::forUser($user)->allows('downloadAnswerKey', $resource),
        ];
    }

    private function attachmentPayload(ExamResourceAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'kind' => $attachment->kind,
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'is_image' => $attachment->isImage(),
            'exists' => $attachment->exists(),
        ];
    }
}
