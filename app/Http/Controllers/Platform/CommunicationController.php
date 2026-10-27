<?php

namespace App\Http\Controllers\Platform;

use App\Enums\CommunicationChannel;
use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\School;
use App\Models\SchoolCommunicationSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Suivi transversale de la communication (multi-établissements).
 * Réservé à l'administrateur plateforme.
 */
class CommunicationController extends Controller
{
    public function index(Request $request): Response
    {
        $settings = SchoolCommunicationSetting::withoutGlobalScopes()->get()->keyBy('school_id');

        $schools = School::query()
            ->when($request->query('search'), fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->when($request->query('alert'), function ($query) {
                $ids = SchoolCommunicationSetting::withoutGlobalScopes()
                    ->whereColumn('sms_balance', '<', 'sms_low_balance_threshold')
                    ->orWhereColumn('whatsapp_budget', '<', 'whatsapp_consumed')
                    ->pluck('school_id');

                $query->whereIn('id', $ids);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $messageStats = Notification::withoutGlobalScopes()
            ->whereIn('school_id', $schools->pluck('id'))
            ->selectRaw("school_id,
                sum(case when status in ('sent', 'delivered') then 1 else 0 end) as sent,
                sum(case when status = 'failed' then 1 else 0 end) as failed")
            ->groupBy('school_id')
            ->get()
            ->keyBy('school_id');

        return Inertia::render('Platform/Communication/Index', [
            'kpis' => $this->kpis(),
            'schools' => $schools->through(fn (School $school) => $this->schoolRow(
                $school,
                $settings->get($school->id),
                $messageStats->get($school->id)
            )),
            'filters' => [
                'search' => $request->query('search'),
                'alert' => $request->query('alert'),
            ],
        ]);
    }

    public function history(Request $request): Response
    {
        $notifications = Notification::withoutGlobalScopes()
            ->with([
                'student:id,first_name,last_name',
                'guardian:id,name,phone',
                'batch:id,notification_type',
            ])
            ->when($request->filled('school'), fn ($query) => $query->where('school_id', $request->integer('school')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('channel'), fn ($query) => $query->where('channel', $request->string('channel')))
            ->when($request->filled('type'), fn ($query) => $query->where('notification_type', $request->string('type')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Platform/Communication/History', [
            'notifications' => $notifications->through(fn (Notification $notification) => [
                'id' => $notification->id,
                'school' => $notification->school?->name,
                'student' => $notification->student?->full_name,
                'parent' => $notification->guardian?->name,
                'channel' => $notification->channel,
                'channel_label' => CommunicationChannel::tryFrom((string) $notification->channel)?->label() ?? $notification->channel,
                'notification_type' => $notification->notification_type,
                'status' => $notification->status?->value,
                'status_label' => $notification->status?->label(),
                'provider' => $notification->provider,
                'error' => $notification->error,
                'cost' => $notification->estimated_cost === null
                    ? null
                    : (float) $notification->estimated_cost,
                'currency' => $notification->cost_currency,
                'created_at' => $notification->created_at?->format('d/m/Y H:i'),
            ]),
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'channels' => collect(CommunicationChannel::cases())->map(fn ($channel) => [
                'value' => $channel->value,
                'label' => $channel->label(),
            ]),
            'statuses' => collect(NotificationStatus::cases())->map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
            'filters' => $request->only(['school', 'status', 'channel', 'type', 'from', 'to']),
        ]);
    }

    /**
     * @return array<string, int|float>
     */
    private function kpis(): array
    {
        $aggregate = SchoolCommunicationSetting::withoutGlobalScopes()
            ->selectRaw('sum(sms_budget) as sms_budget, sum(sms_balance) as sms_balance, sum(whatsapp_budget) as whatsapp_budget, sum(whatsapp_consumed) as whatsapp_consumed')
            ->first();

        $messages = Notification::withoutGlobalScopes()
            ->selectRaw("
                sum(case when status in ('sent', 'delivered') then 1 else 0 end) as sent,
                sum(case when status = 'delivered' then 1 else 0 end) as delivered,
                sum(case when status = 'failed' then 1 else 0 end) as failed")
            ->first();

        $smsBudget = (int) ($aggregate->sms_budget ?? 0);
        $smsBalance = (int) ($aggregate->sms_balance ?? 0);
        $whatsappBudget = (float) ($aggregate->whatsapp_budget ?? 0);
        $whatsappConsumed = (float) ($aggregate->whatsapp_consumed ?? 0);

        return [
            'total_schools' => School::count(),
            'sms_budget' => $smsBudget,
            'sms_consumed' => max(0, $smsBudget - $smsBalance),
            'sms_remaining' => $smsBalance,
            'whatsapp_budget' => round($whatsappBudget, 2),
            'whatsapp_consumed' => round($whatsappConsumed, 2),
            'whatsapp_remaining' => round(max(0, $whatsappBudget - $whatsappConsumed), 2),
            'messages_sent' => (int) ($messages->sent ?? 0),
            'messages_delivered' => (int) ($messages->delivered ?? 0),
            'messages_failed' => (int) ($messages->failed ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function schoolRow(School $school, ?SchoolCommunicationSetting $settings, $stats): array
    {
        $smsBudget = (int) ($settings?->sms_budget ?? 0);
        $smsBalance = (int) ($settings?->sms_balance ?? 0);
        $whatsappBudget = (float) ($settings?->whatsapp_budget ?? 0);
        $whatsappConsumed = (float) ($settings?->whatsapp_consumed ?? 0);

        $smsLow = $settings && (int) $settings->sms_balance < (int) $settings->sms_low_balance_threshold;
        $whatsappLow = $settings && ($whatsappBudget - $whatsappConsumed) < (float) $settings->whatsapp_low_budget_threshold;

        return [
            'id' => $school->id,
            'name' => $school->name,
            'sms_balance' => $smsBalance,
            'sms_budget' => $smsBudget,
            'sms_consumed' => max(0, $smsBudget - $smsBalance),
            'whatsapp_budget' => round($whatsappBudget, 2),
            'whatsapp_consumed' => round($whatsappConsumed, 2),
            'messages_sent' => (int) ($stats->sent ?? 0),
            'messages_failed' => (int) ($stats->failed ?? 0),
            'channel_status' => $this->channelStatus($settings),
            'alert' => $smsLow || $whatsappLow ? ($smsLow ? 'sms' : 'whatsapp') : null,
            'last_balance_check' => $settings?->last_sms_balance_check_at?->format('d/m/Y H:i'),
        ];
    }

    private function channelStatus(?SchoolCommunicationSetting $settings): string
    {
        if (! $settings) {
            return 'disabled';
        }

        return match (true) {
            $settings->sms_enabled => 'sms',
            $settings->whatsapp_enabled => 'whatsapp',
            $settings->email_enabled => 'email',
            default => 'disabled',
        };
    }
}
