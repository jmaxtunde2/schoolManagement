<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $r)
    {
        $parent = $r->user()->parentGuardian()->with(['students.classRoom'])->firstOrFail();

        return Inertia::render('Parent/Dashboard', ['parent' => ['name' => $parent->name, 'phone' => $parent->phone, 'email' => $parent->email], 'children' => $parent->students->map(fn ($s) => ['id' => $s->id, 'name' => $s->full_name, 'matricule' => $s->matricule, 'class_name' => $s->classRoom?->name, 'birth_date' => $s->birth_date?->format('d/m/Y')])]);
    }
}
