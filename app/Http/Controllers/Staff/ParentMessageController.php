<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ParentMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Boîte de réception des messages parents (admin, censeur, secrétaire).
 * Lecture seule : chaque message consulté est marqué comme lu.
 */
class ParentMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $messages = ParentMessage::query()
            ->with(['parent:id,name,phone', 'student:id,first_name,last_name'])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ParentMessage $message) => [
                'id' => $message->id,
                'subject' => $message->subject,
                'excerpt' => str($message->body)->limit(90)->toString(),
                'parent' => $message->parent?->name,
                'phone' => $message->parent?->phone,
                'student' => $message->student?->full_name,
                'is_read' => $message->isRead(),
                'created_at' => $message->created_at?->translatedFormat('d M Y à H:i'),
                'url' => route('messages.show', $message->id),
            ]);

        return Inertia::render('Staff/Messages/Index', [
            'messages' => $messages,
            'unread' => ParentMessage::query()->where('status', ParentMessage::STATUS_NEW)->count(),
        ]);
    }

    public function show(Request $request, ParentMessage $message): Response
    {
        if (! $message->isRead()) {
            $message->update([
                'status' => ParentMessage::STATUS_READ,
                'read_at' => now(),
                'read_by' => $request->user()->id,
            ]);
        }

        $message->load(['parent:id,name,phone,email', 'student:id,first_name,last_name', 'reader:id,name']);

        return Inertia::render('Staff/Messages/Show', [
            'message' => [
                'id' => $message->id,
                'subject' => $message->subject,
                'body' => $message->body,
                'parent' => $message->parent?->name,
                'phone' => $message->parent?->phone,
                'email' => $message->parent?->email,
                'student' => $message->student?->full_name,
                'is_read' => $message->isRead(),
                'created_at' => $message->created_at?->translatedFormat('d M Y à H:i'),
                'read_at' => $message->read_at?->translatedFormat('d M Y à H:i'),
                'read_by' => $message->reader?->name,
            ],
        ]);
    }
}
