<?php

namespace App\Policies;

use App\Models\DemoRequest;
use App\Models\User;

/**
 * Les demandes de démonstration sont une entité PLATEFORME : elles n'ont pas
 * de school_id et ne relèvent que du Super Admin Cori.
 *
 * Aucun utilisateur d'école (admin, censeur…) ne doit y accéder, même par
 * force de l'URL.
 */
class DemoRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformAdmin();
    }

    public function view(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isPlatformAdmin();
    }

    /** Marquer comme contactée / planifiée / effectuée / approuvée / rejetée. */
    public function updateStatus(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isPlatformAdmin();
    }

    /** Notes internes et informations commerciales détaillées. */
    public function update(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isPlatformAdmin();
    }

    /** Demander le paiement initial (150 000 FCFA) après approbation. */
    public function requestPayment(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isPlatformAdmin();
    }

    /** Confirmer manuellement la réception du paiement initial. */
    public function confirmPayment(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isPlatformAdmin();
    }

    /** Activer l'école : création School + compte administrateur. */
    public function activate(User $user, DemoRequest $demoRequest): bool
    {
        return $user->isPlatformAdmin();
    }
}
