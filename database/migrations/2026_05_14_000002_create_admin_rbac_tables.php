<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var array<string, array{label: string, description: string}>
     */
    private array $roles = [
        'admin' => [
            'label' => 'Administrator',
            'description' => 'Full operational access to administrator modules.',
        ],
        'alumni_records_manager' => [
            'label' => 'Alumni Records Manager',
            'description' => 'Access limited to alumni records and CSV import workflows.',
        ],
        'job_manager' => [
            'label' => 'Job Manager',
            'description' => 'Access limited to job posting and job submission workflows.',
        ],
    ];

    /**
     * @var array<string, array{label: string, description: string}>
     */
    private array $permissions = [
        'view_admin_dashboard' => [
            'label' => 'View Admin Dashboard',
            'description' => 'Open the administrator dashboard overview.',
        ],
        'manage_alumni' => [
            'label' => 'Manage Alumni Records',
            'description' => 'Create, update, import, notify, and delete alumni records.',
        ],
        'manage_jobs' => [
            'label' => 'Manage Jobs',
            'description' => 'Create, update, delete, approve, and reject job postings.',
        ],
        'manage_announcements' => [
            'label' => 'Manage Announcements',
            'description' => 'Create, update, delete, and sync announcements.',
        ],
        'manage_feedback' => [
            'label' => 'Manage Feedback',
            'description' => 'Review and update alumni feedback records.',
        ],
        'view_analytics' => [
            'label' => 'View Analytics',
            'description' => 'View and export analytics reports.',
        ],
        'view_audit_logs' => [
            'label' => 'View Audit Logs',
            'description' => 'Review administrator audit activity.',
        ],
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'admin' => [
            'view_admin_dashboard',
            'manage_alumni',
            'manage_jobs',
            'manage_announcements',
            'manage_feedback',
            'view_analytics',
            'view_audit_logs',
        ],
        'alumni_records_manager' => [
            'manage_alumni',
        ],
        'job_manager' => [
            'manage_jobs',
        ],
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 80)->unique();
            $table->string('label', 120);
            $table->string('guard_name', 40)->default('admin')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('label', 140);
            $table->string('guard_name', 40)->default('admin')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_role', function (Blueprint $table) {
            $table->uuid('admin_id');
            $table->uuid('role_id');
            $table->timestamps();

            $table->primary(['admin_id', 'role_id']);
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        $roleIds = $this->seedRoles();
        $permissionIds = $this->seedPermissions();
        $this->seedRolePermissions($roleIds, $permissionIds);
        $this->backfillAdminRoles($roleIds);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('admin_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    /**
     * @return array<string, string>
     */
    private function seedRoles(): array
    {
        $now = now();
        $ids = [];

        foreach ($this->roles as $name => $role) {
            $existingId = DB::table('roles')->where('name', $name)->value('id');
            $id = is_string($existingId) ? $existingId : (string) Str::uuid();

            DB::table('roles')->updateOrInsert(
                ['name' => $name],
                [
                    'id' => $id,
                    'label' => $role['label'],
                    'guard_name' => 'admin',
                    'description' => $role['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $ids[$name] = $id;
        }

        return $ids;
    }

    /**
     * @return array<string, string>
     */
    private function seedPermissions(): array
    {
        $now = now();
        $ids = [];

        foreach ($this->permissions as $name => $permission) {
            $existingId = DB::table('permissions')->where('name', $name)->value('id');
            $id = is_string($existingId) ? $existingId : (string) Str::uuid();

            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'id' => $id,
                    'label' => $permission['label'],
                    'guard_name' => 'admin',
                    'description' => $permission['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $ids[$name] = $id;
        }

        return $ids;
    }

    /**
     * @param  array<string, string>  $roleIds
     * @param  array<string, string>  $permissionIds
     */
    private function seedRolePermissions(array $roleIds, array $permissionIds): void
    {
        $now = now();

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $roleId = $roleIds[$roleName] ?? null;

            if (! is_string($roleId)) {
                continue;
            }

            foreach ($permissions as $permissionName) {
                $permissionId = $permissionIds[$permissionName] ?? null;

                if (! is_string($permissionId)) {
                    continue;
                }

                DB::table('role_permission')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string, string>  $roleIds
     */
    private function backfillAdminRoles(array $roleIds): void
    {
        if (! Schema::hasTable('admins')) {
            return;
        }

        $now = now();

        DB::table('admins')
            ->orderBy('created_at')
            ->get(['id', 'role'])
            ->each(function (object $admin) use ($roleIds, $now): void {
                $roleName = is_string($admin->role ?? null) && isset($roleIds[$admin->role])
                    ? $admin->role
                    : 'admin';
                $roleId = $roleIds[$roleName] ?? null;

                if (! is_string($roleId)) {
                    return;
                }

                DB::table('admin_role')->updateOrInsert(
                    [
                        'admin_id' => $admin->id,
                        'role_id' => $roleId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            });
    }
};
