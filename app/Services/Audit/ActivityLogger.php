<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Record an audit log entry for administrative or sensitive system actions (BE-007, NFR-09).
     *
     * @param  array<string, mixed>|null  $beforeData
     * @param  array<string, mixed>|null  $afterData
     */
    public function log(
        string $action,
        ?Model $auditable = null,
        ?string $description = null,
        ?array $beforeData = null,
        ?array $afterData = null,
        ?int $actorId = null
    ): ActivityLog {
        return ActivityLog::create([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable !== null ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description ?? $action,
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
