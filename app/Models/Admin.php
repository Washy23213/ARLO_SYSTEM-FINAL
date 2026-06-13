<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Admin extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_ALUMNI_RECORDS_MANAGER = 'alumni_records_manager';
    public const ROLE_JOB_MANAGER = 'job_manager';

    public const PERMISSION_VIEW_ADMIN_DASHBOARD = 'view_admin_dashboard';
    public const PERMISSION_MANAGE_ALUMNI = 'manage_alumni';
    public const PERMISSION_MANAGE_JOBS = 'manage_jobs';
    public const PERMISSION_MANAGE_ANNOUNCEMENTS = 'manage_announcements';
    public const PERMISSION_MANAGE_FEEDBACK = 'manage_feedback';
    public const PERMISSION_VIEW_ANALYTICS = 'view_analytics';
    public const PERMISSION_VIEW_AUDIT_LOGS = 'view_audit_logs';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_ALUMNI_RECORDS_MANAGER,
        self::ROLE_JOB_MANAGER,
    ];

    public const ROLE_LABELS = [
        self::ROLE_ADMIN => 'Administrator',
        self::ROLE_ALUMNI_RECORDS_MANAGER => 'Alumni Records Manager',
        self::ROLE_JOB_MANAGER => 'Job Manager',
    ];

    public const PERMISSION_LABELS = [
        self::PERMISSION_VIEW_ADMIN_DASHBOARD => 'View Admin Dashboard',
        self::PERMISSION_MANAGE_ALUMNI => 'Manage Alumni Records',
        self::PERMISSION_MANAGE_JOBS => 'Manage Jobs',
        self::PERMISSION_MANAGE_ANNOUNCEMENTS => 'Manage Announcements',
        self::PERMISSION_MANAGE_FEEDBACK => 'Manage Feedback',
        self::PERMISSION_VIEW_ANALYTICS => 'View Analytics',
        self::PERMISSION_VIEW_AUDIT_LOGS => 'View Audit Logs',
    ];

    public const ROLE_PERMISSIONS = [
        self::ROLE_ADMIN => [
            self::PERMISSION_VIEW_ADMIN_DASHBOARD,
            self::PERMISSION_MANAGE_ALUMNI,
            self::PERMISSION_MANAGE_JOBS,
            self::PERMISSION_MANAGE_ANNOUNCEMENTS,
            self::PERMISSION_MANAGE_FEEDBACK,
            self::PERMISSION_VIEW_ANALYTICS,
            self::PERMISSION_VIEW_AUDIT_LOGS,
        ],
        self::ROLE_ALUMNI_RECORDS_MANAGER => [
            self::PERMISSION_MANAGE_ALUMNI,
        ],
        self::ROLE_JOB_MANAGER => [
            self::PERMISSION_MANAGE_JOBS,
        ],
    ];

    protected ?string $pendingRoleName = null;

    protected $fillable = [
        'id',
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'role',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Admin $admin): void {
            $role = self::normalizeRoleName($admin->role ?? null) ?? self::ROLE_ADMIN;

            if (Schema::hasColumn('admins', 'role')) {
                $admin->attributes['role'] = $role;
            }

            $admin->pendingRoleName = $role;
        });

        static::saved(function (Admin $admin): void {
            $admin->syncPrimaryRole($admin->pendingRoleName);
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'admin_role')->withTimestamps();
    }

    public function getRoleAttribute(mixed $value): string
    {
        return self::normalizeRoleName($value) ?? $this->primaryRoleName();
    }

    public function setRoleAttribute(mixed $value): void
    {
        $role = self::normalizeRoleName($value) ?? self::ROLE_ADMIN;
        $this->pendingRoleName = $role;

        if (Schema::hasColumn('admins', 'role')) {
            $this->attributes['role'] = $role;
        }
    }

    public function isAdmin(): bool
    {
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return false;
    }

    public function isAlumni(): bool
    {
        return false;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->primaryRoleName()] ?? 'Administrator';
    }

    public function hasAnyRole(array $roles): bool
    {
        $roles = array_values(array_filter(
            $roles,
            fn (mixed $role): bool => self::normalizeRoleName($role) !== null,
        ));

        if ($roles === []) {
            return false;
        }

        foreach ($this->roleNames() as $roleName) {
            if (in_array($roleName, $roles, true)) {
                return true;
            }
        }

        return in_array($this->primaryRoleName(), $roles, true);
    }

    public function hasPermission(string $permission): bool
    {
        $permission = trim($permission);

        if ($permission === '') {
            return false;
        }

        if (self::rbacTablesAvailable() && $this->exists) {
            $hasAssignedRoles = $this->relationLoaded('roles')
                ? $this->roles->isNotEmpty()
                : $this->roles()->exists();

            if ($hasAssignedRoles) {
                return Permission::query()
                    ->where('permissions.name', $permission)
                    ->whereHas('roles.admins', fn ($query) => $query->whereKey($this->getKey()))
                    ->exists();
            }
        }

        foreach ($this->roleNames() as $roleName) {
            if (in_array($permission, self::permissionsForRole($roleName), true)) {
                return true;
            }
        }

        return in_array($permission, self::permissionsForRole($this->primaryRoleName()), true);
    }

    public function defaultRouteName(): string
    {
        return match ($this->primaryRoleName()) {
            self::ROLE_ALUMNI_RECORDS_MANAGER => 'admin.alumni',
            self::ROLE_JOB_MANAGER => 'admin.jobs',
            default => 'admin.dashboard',
        };
    }

    public function primaryRoleName(): string
    {
        if (self::normalizeRoleName($this->pendingRoleName) !== null) {
            return $this->pendingRoleName;
        }

        if (self::rbacTablesAvailable() && $this->exists) {
            $role = $this->relationLoaded('roles')
                ? $this->roles->first()
                : $this->roles()->orderBy('name')->first();

            if ($role instanceof Role) {
                return self::normalizeRoleName($role->name) ?? self::ROLE_ADMIN;
            }
        }

        return self::normalizeRoleName($this->attributes['role'] ?? null) ?? self::ROLE_ADMIN;
    }

    /**
     * @return list<string>
     */
    public function roleNames(): array
    {
        if (self::rbacTablesAvailable() && $this->exists) {
            $roles = $this->relationLoaded('roles')
                ? $this->roles->pluck('name')->all()
                : $this->roles()->pluck('name')->all();

            $normalizedRoles = array_values(array_unique(array_filter(
                array_map(fn (mixed $role): ?string => self::normalizeRoleName($role), $roles),
            )));

            if ($normalizedRoles !== []) {
                return $normalizedRoles;
            }
        }

        return [$this->primaryRoleName()];
    }

    public function syncPrimaryRole(?string $roleName = null): void
    {
        if (! $this->exists || ! self::rbacTablesAvailable()) {
            return;
        }

        $roleName = self::normalizeRoleName($roleName)
            ?? self::normalizeRoleName($this->attributes['role'] ?? null)
            ?? self::ROLE_ADMIN;
        $roleId = Role::query()->where('name', $roleName)->value('id');

        if (! is_string($roleId)) {
            return;
        }

        $this->roles()->sync([$roleId]);
        $this->unsetRelation('roles');
    }

    /**
     * @return list<string>
     */
    public static function permissionsForRole(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }

    public static function normalizeRoleName(mixed $role): ?string
    {
        if (! is_string($role)) {
            return null;
        }

        $role = trim($role);

        return in_array($role, self::ROLES, true) ? $role : null;
    }

    protected static function rbacTablesAvailable(): bool
    {
        try {
            return Schema::hasTable('roles')
                && Schema::hasTable('permissions')
                && Schema::hasTable('admin_role')
                && Schema::hasTable('role_permission');
        } catch (Throwable) {
            return false;
        }
    }
}
