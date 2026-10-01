<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public static function log(
        string $action,
        string $module,
        ?string $description = null,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): ?ActivityLog {
        try {
            $user = Auth::user();

            return ActivityLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'module' => $module,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Prevent activity logging failure from breaking primary request workflow
            report($e);
            return null;
        }
    }
}
