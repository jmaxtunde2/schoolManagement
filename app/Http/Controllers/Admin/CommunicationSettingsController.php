<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Communication\CommunicationSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paramètres de communication de l'établissement (canaux et seuils).
 * Les budgets alloués sont gérés par CoriSchool et affichés en lecture seule.
 */
class CommunicationSettingsController extends Controller
{
    public function edit(Request $request, CommunicationSettingsService $settings): Response
    {
        $school = $request->user()->school;
        $setting = $settings->for($school);

        return Inertia::render('Settings/Communication', [
            'settings' => [
                'sms_enabled' => (bool) $setting->sms_enabled,
                'sms_budget' => (int) $setting->sms_budget,
                'sms_balance' => (int) $setting->sms_balance,
                'sms_consumed' => $setting->smsConsumed(),
                'sms_low_balance_threshold' => (int) $setting->sms_low_balance_threshold,
                'sms_reactivation_threshold' => (int) $setting->sms_reactivation_threshold,
                'whatsapp_enabled' => (bool) $setting->whatsapp_enabled,
                'whatsapp_budget' => (float) $setting->whatsapp_budget,
                'whatsapp_consumed' => (float) $setting->whatsapp_consumed,
                'whatsapp_remaining' => $setting->whatsappRemaining(),
                'whatsapp_low_budget_threshold' => (float) $setting->whatsapp_low_budget_threshold,
                'email_enabled' => (bool) $setting->email_enabled,
                'last_sms_balance_check_at' => $setting->last_sms_balance_check_at?->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function update(Request $request, CommunicationSettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'sms_enabled' => ['boolean'],
            'whatsapp_enabled' => ['boolean'],
            'email_enabled' => ['boolean'],
            'sms_low_balance_threshold' => ['required', 'integer', 'min:0'],
            'sms_reactivation_threshold' => ['required', 'integer', 'min:0', 'gte:sms_low_balance_threshold'],
            'whatsapp_low_budget_threshold' => ['required', 'numeric', 'min:0'],
        ]);

        $settings->for($request->user()->school)->update([
            'sms_enabled' => $request->boolean('sms_enabled'),
            'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
            'email_enabled' => $request->boolean('email_enabled'),
            'sms_low_balance_threshold' => $data['sms_low_balance_threshold'],
            'sms_reactivation_threshold' => $data['sms_reactivation_threshold'],
            'whatsapp_low_budget_threshold' => $data['whatsapp_low_budget_threshold'],
        ]);

        return back()->with('success', 'Paramètres de communication enregistrés.');
    }
}
