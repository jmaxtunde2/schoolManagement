<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Students\UpdateStudentPhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\StudentRequest;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $students = Student::with(['classRoom:id,name', 'guardians:id,name,phone,phone_secondary'])
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->integer('class_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q) => $q->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term)->orWhere('matricule', 'like', $term));
            })
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'classes' => ClassRoom::orderBy('name')->get(['id', 'name']),
            'guardians' => ParentGuardian::orderBy('name')->get(['id', 'name', 'phone', 'phone_secondary']),
            'filters' => $request->only(['class_id', 'search']),
        ]);
    }

    public function store(StudentRequest $request, UpdateStudentPhoto $photo): RedirectResponse
    {
        // La photo n'est pas une colonne Student::create : elle est traitée par
        // l'action, qui gère le disque `public` et la suppression de l'ancienne.
        $student = Student::create(Arr::except($request->validated(), ['guardian_ids', 'photo', 'remove_photo']));
        $student->guardians()->sync($request->input('guardian_ids', []));

        $photo->handle($student, $request->file('photo'), $request->boolean('remove_photo'));

        return back()->with('success', 'Élève ajouté.');
    }

    public function update(StudentRequest $request, Student $student, UpdateStudentPhoto $photo): RedirectResponse
    {
        $student->update(Arr::except($request->validated(), ['guardian_ids', 'photo', 'remove_photo']));
        $student->guardians()->sync($request->input('guardian_ids', []));

        $photo->handle($student, $request->file('photo'), $request->boolean('remove_photo'));

        return back()->with('success', 'Élève mis à jour.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        // Le fichier photo est supprimé par l'événement `deleted` de Student.
        $student->delete();

        return back()->with('success', 'Élève supprimé.');
    }
}
