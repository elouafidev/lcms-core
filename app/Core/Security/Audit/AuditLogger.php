<?php

declare(strict_types=1);

namespace App\Core\Security\Audit;

use App\Core\Security\Audit\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Record an audit log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function log(
        string $action,
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): AuditLog {
        $actorId = $user !== null ? $user->id : Auth::id();
        $ip = request()->ip();
        $userAgent = request()->userAgent();

        /** @var AuditLog $log */
        $log = AuditLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);

        return $log;
    }
}
