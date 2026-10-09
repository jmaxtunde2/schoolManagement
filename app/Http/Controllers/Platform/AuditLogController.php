<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with(['user:id,name,email', 'auditable'])
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', "%{$a}%"))
            ->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->query('auditable_type'), fn ($q, $t) => $q->where('auditable_type', $t))
            ->when($request->query('date_from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->query('date_to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $actions = AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $auditableTypes = AuditLog::query()
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type');

        return Inertia::render('Platform/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->query('action'),
                'user_id' => $request->query('user_id'),
                'auditable_type' => $request->query('auditable_type'),
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
            ],
            'actions' => $actions,
            'auditableTypes' => $auditableTypes,
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $auditLog->load(['user:id,name,email', 'auditable']);

        return Inertia::render('Platform/AuditLogs/Show', [
            'log' => [
                'id' => $auditLog->id,
                'action' => $auditLog->action,
                'user' => $auditLog->user ? ['id' => $auditLog->user->id, 'name' => $auditLog->user->name, 'email' => $auditLog->user->email] : null,
                'auditable_type' => $auditLog->auditable_type,
                'auditable_id' => $auditLog->auditable_id,
                'metadata' => $auditLog->metadata,
                'created_at' => $auditLog->created_at->format('d/m/Y H:i:s'),
            ],
        ]);
    }
}