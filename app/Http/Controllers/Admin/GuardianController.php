<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reference\GuardianRequest;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class GuardianController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Guardians/Index', [
            'guardians' => ParentGuardian::with('students:id,first_name,last_name')->orderBy('name')->get(),
            'students' => Student::orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(GuardianRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $guardian = ParentGuardian::create(Arr::except($data, ['student_ids', 'password']));
        $user = User::create(['name' => $guardian->name, 'email' => strtolower($guardian->email), 'password' => Hash::make($data['password'])]);
        $user->forceFill(['school_id' => $request->user()->school_id, 'role' => Role::Parent, 'is_active' => true])->save();
        $guardian->update(['user_id' => $user->id]);
        $guardian->students()->sync($request->input('student_ids', []));

        return back()->with('success', 'Parent ajouté.');
    }

    public function update(GuardianRequest $request, ParentGuardian $guardian): RedirectResponse
    {
        $data = $request->validated();
        $guardian->update(Arr::except($data, ['student_ids', 'password']));
        if (! empty($data['password']) && $guardian->user) {
            $guardian->user->update(['password' => Hash::make($data['password'])]);
        }
        $guardian->students()->sync($request->input('student_ids', []));

        return back()->with('success', 'Parent mis à jour.');
    }

    public function destroy(ParentGuardian $guardian): RedirectResponse
    {
        $guardian->delete();

        return back()->with('success', 'Parent supprimé.');
    }
}
