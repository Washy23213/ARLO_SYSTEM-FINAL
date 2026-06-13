<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditLogService
{
    public function log(?string $userId, string $action, ?string $description = null, ?Request $request = null): void
    {
        if (!Schema::hasTable('system_logs')) {
            return;
        }

        $userIdColumn = $userId;
        $adminIdColumn = null;
        $superAdminIdColumn = null;

        if (
            $userId !== null
            && Schema::hasColumn('system_logs', 'admin_id')
            && Auth::guard('admin')->check()
            && (string) Auth::guard('admin')->id() === (string) $userId
        ) {
            $adminIdColumn = $userId;
            $userIdColumn = null;
        }

        if (
            $userId !== null
            && Schema::hasColumn('system_logs', 'super_admin_id')
            && Auth::guard('super_admin')->check()
            && (string) Auth::guard('super_admin')->id() === (string) $userId
        ) {
            $superAdminIdColumn = $userId;
            $userIdColumn = null;
            $adminIdColumn = null;
        }

        $payload = [
            'id' => (string) Str::uuid(),
            'user_id' => $userIdColumn,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('system_logs', 'admin_id')) {
            $payload['admin_id'] = $adminIdColumn;
        }

        if (Schema::hasColumn('system_logs', 'super_admin_id')) {
            $payload['super_admin_id'] = $superAdminIdColumn;
        }

        DB::table('system_logs')->insert($payload);
    }
}
