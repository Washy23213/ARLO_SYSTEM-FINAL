<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_administrator_account_management_and_edit_role_field(): void
    {
        $superAdmin = SuperAdmin::factory()->create();
        $admin = Admin::factory()->create([
            'firstname' => 'Audit',
            'lastname' => 'Officer',
            'email' => 'audit.officer@example.com',
            'role' => Admin::ROLE_ADMIN,
        ]);

        $indexResponse = $this
            ->actingAs($superAdmin, 'super_admin')
            ->get(route('super-admin.admin-accounts.index'));

        $indexResponse->assertOk();
        $indexResponse->assertSee('Administrator Account Management');
        $indexResponse->assertSee('Audit Logs');
        $indexResponse->assertSee('audit.officer@example.com');

        $editResponse = $this->get(route('super-admin.admin-accounts.edit', $admin));

        $editResponse->assertOk();
        $editResponse->assertSee('name="role"', false);
        $editResponse->assertSee('Administrator');
        $editResponse->assertSee('Alumni Records Manager');
        $editResponse->assertSee('Job Manager');
        $editResponse->assertSee('Delete');
        $editResponse->assertSee(route('super-admin.admin-accounts.destroy', $admin), false);

        $updateResponse = $this->put(route('super-admin.admin-accounts.update', $admin), [
            'firstname' => 'Audit',
            'lastname' => 'Officer',
            'email' => 'audit.officer@example.com',
            'phone' => '09123456789',
            'password' => '',
            'password_confirmation' => '',
            'role' => Admin::ROLE_ALUMNI_RECORDS_MANAGER,
        ]);

        $updateResponse->assertRedirect(route('super-admin.admin-accounts.index'));
        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'role' => Admin::ROLE_ALUMNI_RECORDS_MANAGER,
        ]);

        $roleId = DB::table('roles')
            ->where('name', Admin::ROLE_ALUMNI_RECORDS_MANAGER)
            ->value('id');

        $this->assertDatabaseHas('admin_role', [
            'admin_id' => $admin->id,
            'role_id' => $roleId,
        ]);
        $this->assertTrue($admin->fresh()->hasPermission(Admin::PERMISSION_MANAGE_ALUMNI));
        $this->assertFalse($admin->fresh()->hasPermission(Admin::PERMISSION_MANAGE_JOBS));
        $this->assertDatabaseHas('system_logs', [
            'super_admin_id' => $superAdmin->id,
            'action' => 'super_admin.admin.update',
        ]);
    }

    public function test_super_admin_can_view_and_filter_audit_logs_interface(): void
    {
        $superAdmin = SuperAdmin::factory()->create([
            'firstname' => 'Root',
            'lastname' => 'Admin',
            'email' => 'root.admin@example.com',
        ]);
        $admin = Admin::factory()->create([
            'firstname' => 'Regular',
            'lastname' => 'Admin',
            'email' => 'regular.admin@example.com',
        ]);
        $alumni = User::factory()->create([
            'firstname' => 'Alumni',
            'lastname' => 'User',
            'email' => 'alumni.user@example.com',
        ]);

        DB::table('system_logs')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => null,
                'admin_id' => $admin->id,
                'super_admin_id' => null,
                'action' => 'admin.jobs.create',
                'description' => 'Created a job posting for audit review.',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'created_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $alumni->id,
                'admin_id' => null,
                'super_admin_id' => null,
                'action' => 'alumni.feedback.submit',
                'description' => 'Submitted feedback from the alumni portal.',
                'ip_address' => '127.0.0.2',
                'user_agent' => 'PHPUnit',
                'created_at' => now()->subHour(),
            ],
        ]);

        $response = $this
            ->actingAs($superAdmin, 'super_admin')
            ->get(route('super-admin.audit-logs', [
                'search' => 'job posting',
                'action' => 'admin.jobs.create',
            ]));

        $response->assertOk();
        $response->assertSee('Super Admin Audit Logs');
        $response->assertSee('Super Admin Activity Log');
        $response->assertSee('Admins Who Made Changes');
        $response->assertSee('Regular Admin');
        $response->assertSee('regular.admin@example.com');
        $response->assertSee('Created a job posting for audit review.');
        $response->assertDontSee('Submitted feedback from the alumni portal.');
    }

    public function test_super_admin_can_access_admin_workspace_modules_and_create_operational_records(): void
    {
        $superAdmin = SuperAdmin::factory()->create();

        $dashboardResponse = $this
            ->actingAs($superAdmin, 'super_admin')
            ->get(route('admin.dashboard'));

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Admin Accounts');
        $dashboardResponse->assertSee('Jobs');
        $dashboardResponse->assertSee('Notifications');
        $dashboardResponse->assertSee('Analytics');

        $this->get(route('admin.jobs'))->assertOk();
        $this->get(route('admin.announcements'))->assertOk();
        $this->get(route('admin.feedback'))->assertOk();
        $this->get(route('admin.analytics'))->assertOk();
        $this->get(route('admin.audit-logs'))->assertOk();

        $response = $this->post(route('admin.jobs.store'), [
            'title' => 'Super Admin Posted Job',
            'company' => 'CMU Tech',
            'type' => 'Full-time',
            'location' => 'Bukidnon',
            'description' => 'A complete operational job post created by the super admin account.',
            'requirements' => 'Laravel, SQL, and clear communication.',
            'contact_email' => 'jobs@example.com',
            'contact_phone' => '09123456789',
            'application_deadline' => now()->addDays(10)->toDateString(),
            'salary_range' => '25,000.00 - 34,999.00',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.jobs'));
        $this->assertDatabaseHas('jobs', [
            'title' => 'Super Admin Posted Job',
            'created_by' => null,
        ]);
        $this->assertDatabaseHas('system_logs', [
            'super_admin_id' => $superAdmin->id,
            'action' => 'admin.jobs.create',
        ]);
    }

    public function test_scoped_admin_roles_only_access_their_allowed_modules(): void
    {
        $alumniRecordsManager = Admin::factory()->alumniRecordsManager()->create();
        $jobManager = Admin::factory()->jobManager()->create();

        $this
            ->actingAs($alumniRecordsManager, 'admin')
            ->get(route('admin.alumni'))
            ->assertOk();

        $this
            ->get(route('admin.jobs'))
            ->assertRedirect(route('admin.alumni'));

        auth()->guard('admin')->logout();

        $this
            ->actingAs($jobManager, 'admin')
            ->get(route('admin.jobs'))
            ->assertOk();

        $this
            ->get(route('admin.alumni'))
            ->assertRedirect(route('admin.jobs'));
    }

    public function test_admin_role_tables_are_seeded_with_permissions(): void
    {
        $admin = Admin::factory()->create();
        $jobManager = Admin::factory()->jobManager()->create();

        $this->assertDatabaseHas('roles', [
            'name' => Admin::ROLE_ADMIN,
            'label' => 'Administrator',
        ]);
        $this->assertDatabaseHas('roles', [
            'name' => Admin::ROLE_JOB_MANAGER,
            'label' => 'Job Manager',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => Admin::PERMISSION_MANAGE_JOBS,
            'label' => 'Manage Jobs',
        ]);

        $this->assertTrue($admin->fresh()->hasPermission(Admin::PERMISSION_VIEW_ANALYTICS));
        $this->assertTrue($admin->fresh()->hasPermission(Admin::PERMISSION_MANAGE_JOBS));
        $this->assertTrue($jobManager->fresh()->hasPermission(Admin::PERMISSION_MANAGE_JOBS));
        $this->assertFalse($jobManager->fresh()->hasPermission(Admin::PERMISSION_VIEW_ANALYTICS));
    }
}
