<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\ActivityLog;
use App\Services\Audit\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

trait LogsAdminActivity
{
    /**
     * Log an administrative activity.
     *
     * @param  array<string, mixed>|null  $beforeData
     * @param  array<string, mixed>|null  $afterData
     */
    protected function logActivity(
        string $action,
        ?Model $auditable = null,
        ?string $description = null,
        ?array $beforeData = null,
        ?array $afterData = null
    ): ActivityLog {
        return app(ActivityLogger::class)->log(
            action: $action,
            auditable: $auditable,
            description: $description,
            beforeData: $beforeData,
            afterData: $afterData,
        );
    }
}
