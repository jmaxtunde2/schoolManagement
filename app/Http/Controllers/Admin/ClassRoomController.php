<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Students\BuildGenderDistribution;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\ClassRoomRequest;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ClassRoomController extends Controller
{
    public function index(BuildGenderDistribution $genderDistribution): Response
    {
        return Inertia::render('Admin/Classes/Index', [
            'classes' => ClassRoom::withCount('students')->orderBy('name')->get(),
            'genderDistribution' => $genderDistribution->handle(),
        ]);
    }

    public function store(ClassRoomRequest $request): RedirectResponse
    {
        ClassRoom::create($request->validated());

        return back()->with('success', 'Classe créée.');
    }

    public function update(ClassRoomRequest $request, ClassRoom $class): RedirectResponse
    {
        $class->update($request->validated());

        return back()->with('success', 'Classe mise à jour.');
    }

    public function destroy(ClassRoom $class): RedirectResponse
    {
        $class->delete();

        return back()->with('success', 'Classe supprimée.');
    }
}
