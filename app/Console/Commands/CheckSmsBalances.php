<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\Communication\SmsBalanceService;
use Illuminate\Console\Command;

/**
 * Vérifie le solde SMS fournisseur de chaque établissement et ajuste la
 * disponibilité du canal (avec hystérésis). À planifier chaque nuit.
 */
class CheckSmsBalances extends Command
{
    protected $signature = 'corischool:check-sms-balances';

    protected $description = 'Actualise le solde SMS des établissements et active/désactive les canaux';

    public function handle(SmsBalanceService $balances): int
    {
        $providerBalance = $balances->fetchBalance();
        $checked = 0;

        School::query()->each(function (School $school) use ($balances, $providerBalance, &$checked) {
            $balances->refresh($school, $providerBalance);
            $checked++;
        });

        $this->info($providerBalance === null
            ? "{$checked} établissement(s) vérifié(s) (solde fournisseur non configuré : solde local conservé)."
            : "{$checked} établissement(s) vérifié(s) ; solde fournisseur : {$providerBalance}.");

        return self::SUCCESS;
    }
}
