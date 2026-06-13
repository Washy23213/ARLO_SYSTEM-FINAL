<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Concerns\AdminControllerSupport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SuperAdminAuditLogController extends Controller
{
    use AdminControllerSupport;

    public function index(Request $request)
    {
        Auth::shouldUse('super_admin');

        $search = trim((string) $request->query('search', ''));
        $selectedAction = trim((string) $request->query('action', ''));

        $logsQuery = DB::table('system_logs as sl')
            ->leftJoin('users as u', 'u.id', '=', 'sl.user_id')
            ->leftJoin('admins as a', 'a.id', '=', 'sl.admin_id')
            ->leftJoin('super_admins as sa', 'sa.id', '=', 'sl.super_admin_id')
            ->select(
                'sl.id',
                'sl.action',
                'sl.description',
                'sl.ip_address',
                'sl.user_agent',
                'sl.created_at',
                DB::raw('COALESCE(sa.firstname, a.firstname, u.firstname) as firstname'),
                DB::raw('COALESCE(sa.lastname, a.lastname, u.lastname) as lastname'),
                DB::raw('COALESCE(sa.email, a.email, u.email) as email')
            );

        if ($search !== '') {
            $this->applyCaseInsensitiveSearch(
                $logsQuery,
                $search,
                ['sl.action', 'sl.description', 'sl.ip_address', 'u.email', 'u.firstname', 'u.lastname', 'a.email', 'a.firstname', 'a.lastname', 'sa.email', 'sa.firstname', 'sa.lastname'],
                [['u.firstname', 'u.lastname'], ['a.firstname', 'a.lastname'], ['sa.firstname', 'sa.lastname']]
            );
        }

        if ($selectedAction !== '') {
            $logsQuery->where('sl.action', $selectedAction);
        }

        $logs = $logsQuery
            ->orderByDesc('sl.created_at')
            ->paginate(20)
            ->withQueryString();

        $actionOptions = DB::table('system_logs')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $latestEntry = $this->latestAuditLogEntry();
        $activeAdminActors = $this->activeAdminActors();

        $summary = [
            'totalLogs' => DB::table('system_logs')->count(),
            'todayLogs' => DB::table('system_logs')->where('created_at', '>=', now()->startOfDay())->count(),
            'activeAdmins' => count($activeAdminActors),
            'activeAdminActors' => $activeAdminActors,
            'latestEntryAt' => $latestEntry?->created_at,
            'latestActor' => $this->auditActorLabel($latestEntry),
            'latestActionLabel' => $this->auditActionLabel($latestEntry?->action),
        ];

        return view('admin.audit-logs', [
            'logs' => $logs,
            'search' => $search,
            'selectedAction' => $selectedAction,
            'actionOptions' => $actionOptions,
            'summary' => $summary,
            'auditLogPageTitle' => 'Super Admin Audit Logs',
            'auditLogHeading' => 'Super Admin Activity Log',
            'auditLogRoute' => route('super-admin.audit-logs'),
            'auditLogResetRoute' => route('super-admin.audit-logs'),
        ]);
    }

    protected function latestAuditLogEntry(): ?object
    {
        return DB::table('system_logs as sl')
            ->leftJoin('users as u', 'u.id', '=', 'sl.user_id')
            ->leftJoin('admins as a', 'a.id', '=', 'sl.admin_id')
            ->leftJoin('super_admins as sa', 'sa.id', '=', 'sl.super_admin_id')
            ->select(
                'sl.action',
                'sl.created_at',
                DB::raw('COALESCE(sa.firstname, a.firstname, u.firstname) as firstname'),
                DB::raw('COALESCE(sa.lastname, a.lastname, u.lastname) as lastname'),
                DB::raw('COALESCE(sa.email, a.email, u.email) as email')
            )
            ->orderByDesc('sl.created_at')
            ->first();
    }

    protected function activeAdminActors(): array
    {
        return DB::table('system_logs as sl')
            ->leftJoin('users as u', 'u.id', '=', 'sl.user_id')
            ->leftJoin('admins as a', 'a.id', '=', 'sl.admin_id')
            ->leftJoin('super_admins as sa', 'sa.id', '=', 'sl.super_admin_id')
            ->where('sl.created_at', '>=', now()->subDays(7))
            ->where(function ($query): void {
                $query
                    ->whereNotNull('sl.admin_id')
                    ->orWhereNotNull('sl.super_admin_id');

                if (Schema::hasColumn('users', 'role')) {
                    $query->orWhereIn('u.role', User::ADMIN_ROLES);
                } elseif (Schema::hasColumn('users', 'is_admin')) {
                    $query->orWhere('u.is_admin', true);
                }
            })
            ->select(
                'sl.user_id',
                'sl.admin_id',
                'sl.super_admin_id',
                'sl.action',
                'sl.created_at',
                'u.role as user_role',
                DB::raw('COALESCE(sa.firstname, a.firstname, u.firstname) as firstname'),
                DB::raw('COALESCE(sa.lastname, a.lastname, u.lastname) as lastname'),
                DB::raw('COALESCE(sa.email, a.email, u.email) as email')
            )
            ->orderByDesc('sl.created_at')
            ->get()
            ->unique(fn (object $row): string => $this->auditActorKey($row))
            ->map(fn (object $row): array => [
                'name' => $this->auditActorLabel($row),
                'email' => $row->email ?? 'No linked account',
                'role' => $this->auditActorRoleLabel($row),
                'latestAction' => $this->auditActionLabel($row->action) ?? 'Recorded action',
                'lastSeenAt' => $row->created_at,
            ])
            ->values()
            ->all();
    }

    protected function auditActorKey(object $entry): string
    {
        if (! empty($entry->super_admin_id)) {
            return 'super_admin:'.$entry->super_admin_id;
        }

        if (! empty($entry->admin_id)) {
            return 'admin:'.$entry->admin_id;
        }

        if (! empty($entry->user_id)) {
            return 'user:'.$entry->user_id;
        }

        return 'system:'.($entry->email ?? 'unknown');
    }

    protected function auditActorRoleLabel(object $entry): string
    {
        if (! empty($entry->super_admin_id) || ($entry->user_role ?? null) === User::ROLE_SUPER_ADMIN) {
            return 'Super Admin';
        }

        return 'Administrator';
    }

    protected function auditActorLabel(?object $entry): string
    {
        if (! $entry) {
            return 'N/A';
        }

        $name = trim((string) (($entry->firstname ?? '') . ' ' . ($entry->lastname ?? '')));

        return $name !== '' ? $name : ($entry->email ?? 'System');
    }

    protected function auditActionLabel(?string $action): ?string
    {
        $action = trim((string) $action);

        if ($action === '') {
            return null;
        }

        $action = preg_replace('/^(admin|alumni|auth)\./', '', $action) ?? $action;

        return ucwords(str_replace(['.', '_', '-'], ' ', $action));
    }
}
