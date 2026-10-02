<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/AcademicYears/Index', [
            'years' => AcademicYear::orderByDesc('name')->get(),
        ]);
    }

    public function store(AcademicYearRequest $request): RedirectResponse
    {
        AcademicYear::create($request->validated());

        return back()->with('success', 'Année scolaire créée.');
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->update($request->validated());

        return back()->with('success', 'Année scolaire mise à jour.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->delete();

        return back()->with('success', 'Année scolaire supprimée.');
    }
}
