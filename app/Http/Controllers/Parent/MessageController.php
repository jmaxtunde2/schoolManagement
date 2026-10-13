<?php

namespace App\Http\Controllers\Parent;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\ParentMessage;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Espace parent — messages adressés à l'établissement.
 * Diffusion uniquement interne (cloche de notification), sans SMS ni e-mail.
 */
class MessageController extends Controller
{
    public function index(Request $request): Response
    {
        $parent = $this->parent($request);

        $messages = ParentMessage::query()
            ->where('parent_id', $parent->id)
            ->with('student:id,first_name,last_name')
            ->latest()
            ->get()
            ->map(fn (ParentMessage $message) => [
                'id' => $message->id,
                'subject' => $message->subject,
                'body' => $message->body,
                'student' => $message->student?->full_name,
                'status' => $message->status,
                'is_read' => $message->isRead(),
                'created_at' => $message->created_at?->translatedFormat('d M Y à H:i'),
            ])
            ->values();

        return Inertia::render('Parent/Messages/Index', [
            'messages' => $messages,
            'students' => $this->children($parent),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $parent = $this->parent($request);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'student_id' => ['nullable', 'integer'],
        ]);

        $studentId = $data['student_id'] ?? null;

        if ($studentId !== null && ! $parent->students()->where('students.id', $studentId)->exists()) {
            abort(403);
        }

        $message = ParentMessage::create([
            'parent_id' => $parent->id,
            'student_id' => $studentId,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => ParentMessage::STATUS_NEW,
        ]);

        $this->notifyStaff($message, $parent);

        return back()->with('success', "Votre message a bien été transmis à l'établissement.");
    }

    /** Crée une notification interne (cloche) pour les admin, censeur et secrétaire de l'école. */
    private function notifyStaff(ParentMessage $message, ParentGuardian $parent): void
    {
        $staffIds = User::withoutGlobalScopes()
            ->where('school_id', $message->school_id)
            ->whereIn('role', [Role::Admin->value, Role::Censeur->value, Role::Secretary->value])
            ->where('is_active', true)
            ->pluck('id');

        foreach ($staffIds as $userId) {
            UserNotification::withoutGlobalScopes()->create([
                'school_id' => $message->school_id,
                'user_id' => $userId,
                'type' => 'parent_message.received',
                'title' => 'Nouveau message de '.$parent->name,
                'message' => $message->subject,
                'data' => [
                    'url' => route('messages.show', $message->id, absolute: false),
                    'message_id' => $message->id,
                ],
            ]);
        }
    }

    private function parent(Request $request): ParentGuardian
    {
        return $request->user()->parentGuardian()->firstOrFail();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function children(ParentGuardian $parent): Collection
    {
        return $parent->students()
            ->orderBy('students.id')
            ->get(['students.id', 'students.first_name', 'students.last_name'])
            ->map(fn ($student) => ['id' => $student->id, 'name' => $student->full_name])
            ->values();
    }
}
