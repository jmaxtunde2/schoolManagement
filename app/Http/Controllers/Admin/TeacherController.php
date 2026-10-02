<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\TeacherRequest;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\AuditService;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Teachers/Index', [
            'teachers' => Teacher::with(['user:id,name,email,is_active', 'assignments.classRoom:id,name', 'assignments.subject:id,name'])
                ->latest()
                ->get()
                ->map(fn (Teacher $t) => [
                    'id' => $t->id,
                    'name' => $t->user->name,
                    'email' => $t->user->email,
                    'phone' => $t->phone,
                    'is_active' => $t->user->is_active,
                    'assignments' => $t->assignments->map(fn ($a) => [
                        'class' => $a->classRoom->name,
                        'subject' => $a->subject->name,
                    ]),
                ]),
            'classes' => ClassRoom::orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(TeacherRequest $request): RedirectResponse
    {
        $schoolId = $request->user()->school_id;

        DB::transaction(function () use ($request, $schoolId) {
            $user = User::create([
                'school_id' => $schoolId,
                'name' => $request->string('name'),
                'email' => $request->string('email')->lower(),
                'password' => Hash::make($request->string('password')),
            ]);
            $user->forceFill(['school_id' => $schoolId, 'role' => Role::Teacher, 'is_active' => true])->save();

            $teacher = Teacher::create(['school_id' => $schoolId, 'user_id' => $user->id, 'phone' => $request->input('phone')]);

            $this->syncAssignments($teacher, $request->input('assignments', []));
        });

        return back()->with('success', 'Enseignant ajouté.');
    }

    public function update(TeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        DB::transaction(function () use ($request, $teacher) {
            $teacher->user->update([
                'name' => $request->string('name'),
                'email' => $request->string('email')->lower(),
                'is_active' => $request->boolean('is_active', true),
                ...($request->filled('password') ? ['password' => Hash::make($request->string('password'))] : []),
            ]);

            $teacher->update(['phone' => $request->input('phone')]);

            $this->syncAssignments($teacher, $request->input('assignments', []));
        });

        return back()->with('success', 'Enseignant mis à jour.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        if ($teacher->evaluations()->exists()) {
            $teacher->user->update(['is_active' => false]);

            return back()->with('error', "Cet enseignant a déjà des évaluations : il a été désactivé plutôt que supprimé, afin de conserver l'historique des notes.");
        }

        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            $teacher->delete();
            $user?->delete();
        });

        return back()->with('success', 'Enseignant supprimé.');
    }

    private function syncAssignments(Teacher $teacher, array $assignments): void
    {
        $teacher->assignments()->delete();

        foreach (collect($assignments)->unique(fn ($a) => $a['class_id'].'-'.$a['subject_id']) as $a) {
            $teacher->assignments()->create([
                'class_id' => $a['class_id'],
                'subject_id' => $a['subject_id'],
            ]);
        }
    }
}
