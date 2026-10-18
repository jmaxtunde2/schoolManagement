<?php

namespace App\Services\Alerts;

use App\Enums\AlertPriority;
use App\Enums\Role;
use App\Models\User;
use App\Models\UserNotification;

/**
 * Persiste les alertes sous forme de notifications internes.
 *
 * La déduplication se fait par clé d'alerte + semaine ISO : une même alerte
 * n'est jamais réémise tant qu'elle n'a pas été lue, ce qui empêche la
 * multiplication des notifications d'une semaine sur l'autre.
 */
class AlertNotifier
{
    public function deliver(User $user, array $alert, bool $force = false): bool
    {
        $priority = $alert['priority'] instanceof AlertPriority
            ? $alert['priority']
            : AlertPriority::from((string) $alert['priority']);

        $prefix = $user->role === Role::Director ? 'director' : 'admin';
        $linkRoute = isset($alert['route']) ? "{$prefix}.{$alert['route']}" : null;
        $key = "{$alert['key']}:".now()->format('o-W');

        $payload = [
            'priority' => $priority->value,
            'alert_key' => $key,
            'link_route' => $linkRoute,
            'link_label' => $alert['link_label'] ?? 'Voir le détail',
        ];

        $existing = UserNotification::query()
            ->where('school_id', $user->school_id)
            ->where('user_id', $user->id)
            ->where('type', $alert['type'])
            ->where('data->alert_key', $key)
            ->first();

        if ($existing !== null) {
            if (! $force) {
                return false;
            }

            $existing->update([
                'title' => $alert['title'],
                'message' => $alert['message'],
                'data' => $payload,
                'read_at' => null,
            ]);

            return true;
        }

        UserNotification::create([
            'school_id' => $user->school_id,
            'user_id' => $user->id,
            'type' => $alert['type'],
            'title' => $alert['title'],
            'message' => $alert['message'],
            'data' => $payload,
        ]);

        return true;
    }
}
