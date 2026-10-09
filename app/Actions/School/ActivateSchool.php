<?php

namespace App\Actions\School;

use App\Enums\LicenseStatus;
use App\Enums\LicenseType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\DemoRequest;
use App\Models\License;
use App\Models\Payment;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Services\AuditService;
use App\Support\SchoolBranding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Activation d'une école après une demande de démonstration approuvée.
 *
 * C'est le terminus du parcours commercial, le tout dans une SEULE transaction :
 *
 *   approved + paiement initial confirmé
 *                 ↓
 *          School créée
 *                 ↓
 *          Payment initial enregistré (confirmé, 150 000 FCFA)
 *                 ↓
 *          License initiale créée et active
 *                 ↓
 *          compte administrateur provisionné
 *                 ↓
 *          demande marquée « activée »
 *
 * Si une étape échoue, rien n'est persisté — aucune école orpheline, aucun
 * paiement fictif. `academic_year_id` de la licence reste null : l'année
 * scolaire est créée en aval (onboarding / transition d'année).
 */
class ActivateSchool
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * @param  array{admin_name: string, admin_email: string, admin_password?: string|null}  $admin
     * @return array{school: School, admin: User, license: License, payment: Payment, temporary_password: string}
     */
    public function handle(DemoRequest $demoRequest, array $admin): array
    {
        abort_unless($demoRequest->canBeActivated(), 422, 'Cette demande ne peut pas encore être activée.');

        $password = $admin['admin_password'] ?? Str::password(12);
        $amount = (int) config('cori.billing.initial_amount');
        $currency = (string) config('cori.billing.currency', 'XOF');
        $paymentReference = 'CORI-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));

        $result = DB::transaction(function () use (
            $demoRequest,
            $admin,
            $password,
            $amount,
            $currency,
            $paymentReference
        ) {
            $school = School::create([
                'name' => $demoRequest->school_name,
                'is_active' => true,
            ]);

            // SchoolSetting porte school_id (et non l'inverse) : on l'écrit
            // explicitement, le scope global étant piloté par la session.
            SchoolSetting::withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'school_name' => $demoRequest->school_name,
                'address' => $demoRequest->address,
                'phone' => $demoRequest->phone,
                'email' => $demoRequest->email,
            ]);

            // User n'a ni `school_id` ni `role` fillable : on force l'écriture,
            // exactement comme le faisait RegisterSchool.
            $adminUser = new User;
            $adminUser->forceFill([
                'school_id' => $school->id,
                'name' => $admin['admin_name'],
                'email' => strtolower($admin['admin_email']),
                'password' => Hash::make($password),
                'role' => Role::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ])->save();

            // Licence initiale : activation + première année, 150 000 FCFA.
            // Le paiement ayant déjà été attesté, la licence naît active.
            $license = License::withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'academic_year_id' => null,
                'type' => LicenseType::Initial,
                'amount' => $amount,
                'currency' => $currency,
                'status' => LicenseStatus::Active,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'paid_at' => now(),
                'payment_reference' => $paymentReference,
                'created_by' => auth()->id(),
                'activated_by' => auth()->id(),
                'notes' => 'Licence initiale — activation '.$school->name.'.',
            ]);

            // Paiement initial correspondant, confirmé manuellement.
            $payment = Payment::withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'license_id' => $license->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => PaymentStatus::Confirmed,
                'method' => PaymentMethod::Manual,
                'reference' => $paymentReference,
                'metadata' => ['demo_request_id' => $demoRequest->id, 'kind' => 'initial'],
                'paid_at' => now(),
                'confirmed_at' => now(),
                'created_by' => auth()->id(),
                'confirmed_by' => auth()->id(),
            ]);

            $demoRequest->forceFill([
                'school_id' => $school->id,
                'activated_at' => now(),
                'activated_by' => auth()->id(),
            ])->save();

            Cache::forget(SchoolBranding::cacheKey($school->id));

            $this->audit->log('demo_request.activated', $demoRequest, [
                'school_id' => $school->id,
                'admin_user_id' => $adminUser->id,
                'admin_email' => $adminUser->email,
                'license_id' => $license->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
            ]);

            return [
                'school' => $school->fresh(),
                'admin' => $adminUser,
                'license' => $license,
                'payment' => $payment,
            ];
        });

        return [
            'school' => $result['school'],
            'admin' => $result['admin'],
            'license' => $result['license'],
            'payment' => $result['payment'],
            'temporary_password' => $password,
        ];
    }
}