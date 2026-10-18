<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\SubjectRequest;
use App\Models\ClassRoom;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(Request $request): Response
    {
        $isDirector = $request->user()->isDirector();

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => Subject::with('classes:id,name')
                ->when($isDirector, fn ($q) => $q->whereHas('classes', fn ($c) => $c->primary()))
                ->orderBy('name')
                ->get(),
            'classes' => ClassRoom::query()
                ->when($isDirector, fn ($q) => $q->primary())
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create(Arr::except($request->validated(), 'class_ids'));
        $subject->classes()->sync($request->input('class_ids', []));

        return back()->with('success', 'Matière créée.');
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update(Arr::except($request->validated(), 'class_ids'));
        $subject->classes()->sync($request->input('class_ids', []));

        return back()->with('success', 'Matière mise à jour.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('success', 'Matière supprimée.');
    }
}
