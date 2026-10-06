<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Staff\UpdateStaffPhoto;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffMemberRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Administration → Personnel.
 *
 * Complète `Admin\UserController` (qui reste le point d'entrée historique) avec
 * une gestion de personnel complète : identité prénom/nom, téléphone, rôle,
 * photo, statut actif/inactif et identifiants de connexion.
 *
 * Isolation : toutes les lectures passent par `school_id = utilisateur connecté`
 * et les écritures par `forceFill`, donc un ID d'une autre école soumis dans
 * l'URL ne peut jamais être ciblé.
 */
class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $schoolId = $request->user()->school_id;
        $currentUserId = $request->user()->id;

        $staff = User::query()
            ->where('school_id', $schoolId)
            ->whereIn('role', array_map(fn (Role $role) => $role->value, Role::assignableStaff()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->string('status') === 'active');
            })
            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'director' THEN 2 WHEN 'censeur' THEN 3 WHEN 'secretary' THEN 4 WHEN 'accountant' THEN 5 ELSE 6 END")
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'email', 'phone', 'role', 'is_active', 'photo_path', 'two_factor_confirmed_at'])
            ->map(fn (User $member) => $this->present($member, $currentUserId));

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
            'roles' => collect(Role::assignableStaff())->map(fn (Role $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ])->values(),
            'counts' => [
                'total' => $staff->count(),
                'active' => $staff->where('is_active', true)->count(),
                'inactive' => $staff->where('is_active', false)->count(),
                'teachers' => $staff->where('role', Role::Teacher->value)->count(),
            ],
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function store(StaffMemberRequest $request, AuditService $audit, UpdateStaffPhoto $photo): RedirectResponse
    {
        $schoolId = $request->user()->school_id;
        $role = Role::from($request->validated('role'));

        $member = DB::transaction(function () use ($request, $schoolId, $role) {
            $user = new User;
            $user->forceFill([
                // school_id vient du serveur, jamais du formulaire.
                'school_id' => $schoolId,
                'name' => $request->fullName(),
                'email' => $request->validated('email'),
                'phone' => $request->input('phone'),
                'password' => $request->hashedPassword(),
                'role' => $role,
                'is_active' => $request->boolean('is_active'),
                'email_verified_at' => now(),
            ])->save();

            // Un enseignant doit avoir une fiche `teachers` : c'est elle qui porte
            // les affectations classes/matières et le lien vers les évaluations.
            if ($role === Role::Teacher) {
                Teacher::create([
                    'school_id' => $schoolId,
                    'user_id' => $user->id,
                    'phone' => $request->input('phone'),
                ]);
            }

            return $user;
        });

        $photo->handle($member, $request->file('photo'), $request->boolean('remove_photo'));

        $audit->log('staff.created', $member, ['role' => $role->value]);

        return back()->with('success', "{$role->label()} ajouté au personnel de l'établissement.");
    }

    public function update(StaffMemberRequest $request, User $staff, AuditService $audit, UpdateStaffPhoto $photo): RedirectResponse
    {
        $this->assertSameSchool($request, $staff);

        // Un membre ne peut pas retirer son propre accès, sinon l'établissement
        // resterait sans administrateur connecté.
        abort_if(
            $staff->id === $request->user()->id && ! $request->boolean('is_active'),
            422,
            'Vous ne pouvez pas désactiver votre propre compte.',
        );

        $role = Role::from($request->validated('role'));

        DB::transaction(function () use ($request, $staff, $role) {
            $staff->forceFill([
                'name' => $request->fullName(),
                'email' => $request->validated('email'),
                'phone' => $request->input('phone'),
                'role' => $role,
                'is_active' => $request->boolean('is_active'),
            ])->save();

            if ($password = $request->hashedPassword()) {
                $staff->forceFill(['password' => $password])->save();
            }

            $teacher = $staff->teacher()->first();

            if ($role === Role::Teacher && ! $teacher) {
                $teacher = Teacher::create([
                    'school_id' => $staff->school_id,
                    'user_id' => $staff->id,
                    'phone' => $request->input('phone'),
                ]);
            }

            if ($teacher) {
                $teacher->forceFill(['phone' => $request->input('phone')])->save();
            }
        });

        $photo->handle($staff, $request->file('photo'), $request->boolean('remove_photo'));

        $audit->log('staff.updated', $staff, ['role' => $role->value, 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Membre du personnel mis à jour.');
    }

    /**
     * Activation / désactivation rapide depuis la liste.
     *
     * Même règle que la modification : on ne se désactive pas soi-même.
     */
    public function toggle(Request $request, User $staff, AuditService $audit): RedirectResponse
    {
        $this->assertSameSchool($request, $staff);

        abort_if($staff->id === $request->user()->id, 422, 'Vous ne pouvez pas désactiver votre propre compte.');

        $staff->forceFill(['is_active' => ! $staff->is_active])->save();

        $audit->log('staff.access_updated', $staff, ['is_active' => $staff->is_active]);

        return back()->with('success', $staff->is_active
            ? 'Accès rétabli pour ce membre du personnel.'
            : 'Accès suspendu pour ce membre du personnel.');
    }

    /**
     * Suppression définitive.
     *
     * Un membre ayant déjà produit des notes ou des bulletins n'est jamais effacé :
     * l'historique académique doit rester lisible, on le désactive à la place.
     */
    public function destroy(Request $request, User $staff, AuditService $audit): RedirectResponse
    {
        $this->assertSameSchool($request, $staff);

        abort_if($staff->id === $request->user()->id, 422, 'Vous ne pouvez pas supprimer votre propre compte.');

        $hasHistory = $staff->teacher?->evaluations()->exists()
            || $staff->teacher?->assignments()->exists()
            || $staff->teacher?->classes()->exists();

        if ($hasHistory) {
            $staff->forceFill(['is_active' => false])->save();

            return back()->with(
                'error',
                'Ce membre du personnel est rattaché à des classes ou à des évaluations : il a été désactivé plutôt que supprimé, afin de conserver l’historique des notes.'
            );
        }

        DB::transaction(function () use ($staff) {
            $staff->teacher?->delete();
            $staff->delete();
        });

        $audit->log('staff.deleted', $staff, ['role' => $staff->role->value]);

        return back()->with('success', 'Membre du personnel supprimé.');
    }

    /** Double garde : school_id strict + exclusion de l'utilisateur connecté. */
    private function assertSameSchool(Request $request, User $staff): void
    {
        abort_unless((int) $staff->school_id === (int) $request->user()->school_id, 403);
    }

    private function present(User $member, int $currentUserId): array
    {
        [$first, $last] = array_pad(preg_split('/\s+/', trim($member->name), 2) ?: [], 2, null);

        return [
            'id' => $member->id,
            'first_name' => $first ?: $member->name,
            'last_name' => $last ?? '—',
            'full_name' => $member->name,
            'email' => $member->email,
            'phone' => $member->phone,
            'role' => $member->role->value,
            'role_label' => $member->role->label(),
            'is_active' => $member->is_active,
            'photo_url' => $member->photoUrl(),
            'two_factor_configured' => $member->two_factor_confirmed_at !== null,
            'is_self' => $member->id === $currentUserId,
        ];
    }
}
