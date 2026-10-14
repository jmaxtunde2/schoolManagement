<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * Budgets et canaux de communication configurables par établissement.
 */
class SchoolCommunicationSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'sms_enabled',
        'sms_budget',
        'sms_balance',
        'sms_low_balance_threshold',
        'sms_reactivation_threshold',
        'whatsapp_enabled',
        'whatsapp_budget',
        'whatsapp_consumed',
        'whatsapp_low_budget_threshold',
        'email_enabled',
        'last_sms_balance_check_at',
    ];

    protected function casts(): array
    {
        return [
            'sms_enabled' => 'boolean',
            'sms_balance' => 'integer',
            'sms_budget' => 'integer',
            'sms_low_balance_threshold' => 'integer',
            'sms_reactivation_threshold' => 'integer',
            'whatsapp_enabled' => 'boolean',
            'whatsapp_budget' => 'decimal:2',
            'whatsapp_consumed' => 'decimal:2',
            'whatsapp_low_budget_threshold' => 'decimal:2',
            'email_enabled' => 'boolean',
            'last_sms_balance_check_at' => 'datetime',
        ];
    }

    public function smsConsumed(): int
    {
        return max(0, (int) $this->sms_budget - (int) $this->sms_balance);
    }

    public function whatsappRemaining(): float
    {
        return max(0, (float) $this->whatsapp_budget - (float) $this->whatsapp_consumed);
    }

    public function whatsappLow(): bool
    {
        return $this->whatsappRemaining() < (float) $this->whatsapp_low_budget_threshold;
    }

    public function smsLow(): bool
    {
        return (int) $this->sms_balance < (int) $this->sms_low_balance_threshold;
    }

    public function smsCritical(): bool
    {
        return (int) $this->sms_balance < (int) $this->sms_low_balance_threshold;
    }
}
