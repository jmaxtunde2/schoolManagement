<?php

namespace App\Services\Communication;

use App\Models\School;
use App\Models\SchoolCommunicationSetting;

/**
 * Accès aux réglages de communication d'un établissement, avec des
 * valeurs par défaut créées à la première lecture.
 */
class CommunicationSettingsService
{
    public function for(School $school): SchoolCommunicationSetting
    {
        return SchoolCommunicationSetting::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id],
            [
                'sms_enabled' => true,
                'sms_budget' => 3000,
                'sms_balance' => 3000,
                'sms_low_balance_threshold' => 500,
                'sms_reactivation_threshold' => 1000,
                'whatsapp_enabled' => true,
                'whatsapp_budget' => 5,
                'whatsapp_consumed' => 0,
                'whatsapp_low_budget_threshold' => 1,
                'email_enabled' => true,
            ]
        );
    }
}
