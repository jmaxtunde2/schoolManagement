<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ParentAccessCode;
use App\Models\ParentAccessSession;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Services\ParentAccess\ParentAccessCodeService;
use App\Services\ParentAccess\ParentAccessNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ParentAccessController extends Controller
{
    private function resolveSchool(Request $request, School $school): School
    {
        return $request->routeIs('platform.*') ? $school : $request->user()->school;
    }

    public function index(Request $request, School $school): Response
    {
        $school = $this->resolveSchool($request, $school);

        $query = ParentGuardian::query()
            ->where('school_id', $school->id)
            ->with(['students' => function ($q) {
                $q->where('students.is_active', true);
            }])
            ->with(['user']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $parents = $query->paginate(20)->withQueryString();

        // Get access info for each parent
        $parents->getCollection()->transform(function ($parent) use ($school) {
            $latestCode = ParentAccessCode::query()
                ->where('parent_id', $parent->id)
                ->where('school_id', $school->id)
                ->latest()
                ->first();

            $activeSessions = ParentAccessSession::query()
                ->where('parent_id', $parent->id)
                ->where('school_id', $school->id)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->count();

            $status = 'non_activated';
            if ($latestCode) {
                if ($latestCode->isRevoked()) {
                    $status = 'revoked';
                } elseif ($latestCode->isUsed()) {
                    $status = 'activated';
                } elseif ($latestCode->isExpired()) {
                    $status = 'expired';
                } elseif ($latestCode->isValid()) {
                    $status = 'code_generated';
                }
            }

            return [
                'id' => $parent->id,
                'name' => $parent->name,
                'phone' => $parent->phone,
                'email' => $parent->email,
                'is_active' => $parent->is_active,
                'students_count' => $parent->students->count(),
                'students' => $parent->students->map(fn ($s) => ['name' => $s->full_name, 'class_name' => $s->classRoom?->name])->values(),
                'status' => $status,
                'latest_code' => $latestCode ? [
                    'id' => $latestCode->id,
                    'plain_code' => $latestCode->isValid() ? $latestCode->code : null,
                    'created_at' => $latestCode->created_at?->format('d/m/Y H:i'),
                    'expires_at' => $latestCode->expires_at?->format('d/m/Y H:i'),
                    'used_at' => $latestCode->used_at?->format('d/m/Y H:i'),
                    'is_revoked' => $latestCode->isRevoked(),
                ] : null,
                'active_sessions_count' => $activeSessions,
            ];
        });

        $routePrefix = match (true) {
            $request->routeIs('platform.*') => 'platform',
            $request->routeIs('censeur.*') => 'censeur',
            default => 'admin',
        };

        return Inertia::render('Admin/Parents/Access', [
            'parents' => $parents,
            'filters' => $request->only(['search']),
            'routePrefix' => $routePrefix,
            'schoolId' => $school->id,
        ]);
    }

    public function generate(
        Request $request,
        School $school,
        ParentGuardian $parent,
        ParentAccessCodeService $codeService,
        ParentAccessNotificationService $notifications
    ): RedirectResponse {
        $school = $this->resolveSchool($request, $school);
        if ((int) $parent->school_id !== (int) $school->id) {
            abort(403);
        }

        if (! $parent->students()->where('students.school_id', $school->id)->exists()) {
            return back()->withErrors(['message' => 'Ce parent ne possède aucun enfant dans cet établissement.']);
        }

        $academicYear = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->first();

        $result = $codeService->generateCode($parent, $school, $academicYear, $request->user()->id);
        $notification = $notifications->sendAccessCode($parent, $school, $result['plain_code']);

        $message = 'Code généré avec succès.';
        if (! $notifications->configured()) {
            $message .= " Envoi WhatsApp non configuré : le code n'a pas été envoyé.";
        } elseif ($notification['sent']) {
            $message .= ' Les deux messages WhatsApp (code de vérification et espace parent) ont été envoyés.';
        } else {
            $message .= " {$notification['sent_count']}/2 message(s) WhatsApp envoyé(s).";
        }

        $redirect = back()->with('success', $message)
            ->with('generated_code', $result['plain_code'])
            ->with('parent_name', $parent->name);

        if ($notifications->configured() && $notification['errors'] !== []) {
            $redirect->with('parent_access_bulk_errors', array_map(
                fn (string $error) => "{$parent->name} : {$error}",
                $notification['errors']
            ));
        }

        return $redirect;
    }

    public function regenerate(
        Request $request,
        School $school,
        ParentGuardian $parent,
        ParentAccessCodeService $codeService,
        ParentAccessNotificationService $notifications
    ): RedirectResponse {
        return $this->generate($request, $school, $parent, $codeService, $notifications);
    }

    /**
     * Génère un code pour tous les parents actifs de l'établissement et envoie
     * à chacun les deux modèles WhatsApp (code de vérification + espace parent)
     * lorsqu'un fournisseur est configuré.
     */
    public function generateAll(
        Request $request,
        School $school,
        ParentAccessCodeService $codeService,
        ParentAccessNotificationService $notifications
    ): RedirectResponse {
        $school = $this->resolveSchool($request, $school);

        $academicYear = AcademicYear::query()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->first();

        $parents = ParentGuardian::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereHas('students', function ($query) use ($school) {
                $query->where('students.school_id', $school->id)
                    ->where('students.is_active', true);
            })
            ->get();

        if ($parents->isEmpty()) {
            return back()->with('error', 'Aucun parent actif avec enfant dans cet établissement.');
        }

        $canSend = $notifications->configured();

        $generated = 0;
        $sent = 0;
        $failed = 0;
        $bulkErrors = [];

        foreach ($parents as $parent) {
            $result = $codeService->generateCode($parent, $school, $academicYear, $request->user()->id);
            $generated++;

            if (! $canSend) {
                continue;
            }

            $notification = $notifications->sendAccessCode($parent, $school, $result['plain_code']);

            if ($notification['sent']) {
                $sent++;

                continue;
            }

            $failed++;

            foreach ($notification['errors'] as $error) {
                $bulkErrors[] = "{$parent->name} : {$error}";
            }
        }

        if (! $canSend) {
            return back()
                ->with('success', "{$generated} code(s) généré(s). Les codes sont affichés dans le tableau.")
                ->with('error', "Envoi WhatsApp non configuré : les codes n'ont pas été envoyés aux parents.");
        }

        $summary = "{$generated} code(s) généré(s), {$sent} parent(s) notifié(s) par WhatsApp"
            .($failed > 0 ? ", {$failed} échec(s)" : '').'.';

        return back()
            ->with('success', $summary)
            ->with('parent_access_bulk_errors', array_slice($bulkErrors, 0, 30));
    }

    public function revokeCode(Request $request, School $school, ParentAccessCode $code): RedirectResponse
    {
        $school = $this->resolveSchool($request, $school);
        if ((int) $code->school_id !== (int) $school->id) {
            abort(403);
        }

        if (! $code->isUsed()) {
            $code->update(['revoked_at' => now()]);
        }

        return back()->with('success', 'Code révoqué avec succès.');
    }

    public function revokeSession(Request $request, School $school, ParentAccessSession $session): RedirectResponse
    {
        $school = $this->resolveSchool($request, $school);
        if ((int) $session->school_id !== (int) $school->id) {
            abort(403);
        }

        $session->update(['revoked_at' => now()]);

        return back()->with('success', 'Session révoquée avec succès.');
    }

    public function revokeAllSessions(Request $request, School $school, ParentGuardian $parent): RedirectResponse
    {
        $school = $this->resolveSchool($request, $school);
        if ((int) $parent->school_id !== (int) $school->id) {
            abort(403);
        }

        ParentAccessSession::query()
            ->where('parent_id', $parent->id)
            ->where('school_id', $school->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return back()->with('success', 'Toutes les sessions du parent ont été révoquées.');
    }

    public function print(Request $request, School $school, ParentGuardian $parent): Response
    {
        $school = $this->resolveSchool($request, $school);
        if ((int) $parent->school_id !== (int) $school->id) {
            abort(403);
        }

        $latestCode = ParentAccessCode::query()
            ->where('parent_id', $parent->id)
            ->where('school_id', $school->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        return Inertia::render('Admin/Parents/AccessPrint', [
            'school' => [
                'name' => $school->name,
                'logo_url' => $school->settings?->logo_url ?? null,
            ],
            'parent' => [
                'name' => $parent->name,
            ],
            'code' => $latestCode ? [
                'plain_code' => $latestCode->code,
                'expires_at' => $latestCode->expires_at?->format('d/m/Y H:i'),
            ] : null,
            'generated_code' => $latestCode?->code ?? session('generated_code'),
        ]);
    }
}
