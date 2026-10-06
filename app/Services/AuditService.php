<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public function log(string $action, ?object $model = null, array $metadata = []): void
    {
        $request = app()->bound('request') ? app(Request::class) : null;
        AuditLog::create(['school_id' => auth()->user()?->school_id, 'user_id' => auth()->id(), 'action' => $action, 'auditable_type' => $model ? get_class($model) : null, 'auditable_id' => $model?->getKey(), 'metadata' => $metadata, 'ip_address' => $request?->ip(), 'user_agent' => $request?->userAgent()]);
    }
}
