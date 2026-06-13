<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\TracerRecord;
use App\Models\User;
use App\Notifications\EmploymentProofSubmittedNotification;
use App\Services\EmploymentProofReviewNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmploymentProofReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employment_proof_upload_notifies_alumni_admin_roles_and_super_admins(): void
    {
        Notification::fake();

        $admin = Admin::factory()->create();
        $alumniRecordsManager = Admin::factory()->alumniRecordsManager()->create();
        $jobManager = Admin::factory()->jobManager()->create();
        $superAdmin = SuperAdmin::factory()->create();
        $alumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
        ]);

        $record = TracerRecord::query()->create([
            'user_id' => $alumni->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'presently_employed' => 'Yes',
            'employment_proof_path' => 'tracer-employment-proofs/proof.pdf',
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'proof.pdf',
            'employment_proof_mime_type' => 'application/pdf',
            'employment_proof_size_bytes' => 2048,
            'employment_proof_uploaded_at' => now(),
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_PENDING,
            'contact_permission' => true,
        ]);

        $notified = app(EmploymentProofReviewNotificationService::class)->notifyPendingReview($alumni, $record);

        $this->assertSame(3, $notified);
        Notification::assertSentTo($admin, EmploymentProofSubmittedNotification::class);
        Notification::assertSentTo($alumniRecordsManager, EmploymentProofSubmittedNotification::class);
        Notification::assertSentTo($superAdmin, EmploymentProofSubmittedNotification::class);
        Notification::assertNotSentTo($jobManager, EmploymentProofSubmittedNotification::class);
    }

    public function test_admin_approval_marks_alumni_as_employed_from_the_verified_proof(): void
    {
        Storage::fake('private');

        $admin = User::factory()->admin()->create();
        $alumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'employed_after_grad' => null,
            'industry' => null,
            'current_position' => null,
            'company' => null,
            'salary_range' => null,
        ]);
        $proofPath = 'tracer-employment-proofs/admin-proof.pdf';

        Storage::disk('private')->put($proofPath, "%PDF-1.4\n% employment proof");

        $record = TracerRecord::query()->create([
            'user_id' => $alumni->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'current_position' => 'QA Engineer',
            'current_company' => 'CMU Tech',
            'industry' => 'Information Technology',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'presently_employed' => 'Yes',
            'present_job_monthly_earnings' => 'P20,000.00 to less than P30,000.00',
            'employment_proof_path' => $proofPath,
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'admin-proof.pdf',
            'employment_proof_mime_type' => 'application/pdf',
            'employment_proof_size_bytes' => strlen("%PDF-1.4\n% employment proof"),
            'employment_proof_uploaded_at' => now(),
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_PENDING,
            'contact_permission' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.alumni.proof.verify', ['alumni' => $alumni->id, 'record' => $record->id]));

        $response->assertRedirect(route('admin.alumni.show', $alumni->id));
        $this->assertDatabaseHas('tracer_records', [
            'id' => $record->id,
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_VERIFIED,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $alumni->id,
            'employed_after_grad' => 'Yes',
            'current_position' => 'QA Engineer',
            'company' => 'CMU Tech',
            'industry' => 'Information Technology',
            'salary_range' => 'P20,000.00 to less than P30,000.00',
        ]);
    }

    public function test_alumni_management_marks_and_sorts_pending_employment_proof_uploads(): void
    {
        $admin = User::factory()->admin()->create();
        $noProofAlumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'firstname' => 'No',
            'lastname' => 'Proof',
            'created_at' => now(),
        ]);
        $proofAlumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'firstname' => 'Pending',
            'lastname' => 'Proof',
            'created_at' => now()->subDays(5),
        ]);

        TracerRecord::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $proofAlumni->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'presently_employed' => 'Yes',
            'employment_proof_path' => 'tracer-employment-proofs/pending-proof.pdf',
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'pending-proof.pdf',
            'employment_proof_mime_type' => 'application/pdf',
            'employment_proof_size_bytes' => 2048,
            'employment_proof_uploaded_at' => now(),
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_PENDING,
            'contact_permission' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.alumni'));

        $response->assertOk();
        $response->assertSeeText('1 proof PDF pending');
        $response->assertSeeText('Employment PDF sent');
        $response->assertSeeText('Pending Review');
        $response->assertSeeTextInOrder(['Pending Proof', 'No Proof']);
    }

    public function test_reviewed_employment_proofs_show_change_labels_on_admin_details_page(): void
    {
        $admin = User::factory()->admin()->create();
        $alumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
        ]);

        TracerRecord::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $alumni->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'presently_employed' => 'Yes',
            'employment_proof_path' => 'tracer-employment-proofs/verified-proof.pdf',
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'verified-proof.pdf',
            'employment_proof_mime_type' => 'application/pdf',
            'employment_proof_size_bytes' => 2048,
            'employment_proof_uploaded_at' => now(),
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_VERIFIED,
            'contact_permission' => true,
        ]);

        TracerRecord::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $alumni->id,
            'tracer_date' => now()->subDay()->toDateString(),
            'is_employed' => false,
            'employment_type' => 'Unemployed',
            'presently_employed' => 'No',
            'employment_proof_path' => 'tracer-employment-proofs/rejected-proof.pdf',
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'rejected-proof.pdf',
            'employment_proof_mime_type' => 'application/pdf',
            'employment_proof_size_bytes' => 2048,
            'employment_proof_uploaded_at' => now()->subDay(),
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_REJECTED,
            'employment_proof_rejection_note' => 'Company name did not match the proof.',
            'contact_permission' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.alumni.show', $alumni->id));

        $response->assertOk();
        $response->assertSeeText('Verified');
        $response->assertSeeText('Rejected');
        $response->assertSeeText('Change to Verified');
        $response->assertSeeText('Change to Rejected');
        $response->assertSeeText('Company name did not match the proof.');
    }

    public function test_overdue_registered_alumni_without_any_uploaded_proof_are_marked_unemployed(): void
    {
        $overdueWithoutProof = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'employed_after_grad' => null,
            'created_at' => now()->subDays(8),
        ]);
        $recentWithoutProof = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'employed_after_grad' => null,
            'created_at' => now()->subDays(2),
        ]);
        $overdueWithProof = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'employed_after_grad' => null,
            'created_at' => now()->subDays(8),
        ]);

        TracerRecord::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $overdueWithProof->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'presently_employed' => 'Yes',
            'employment_proof_path' => 'tracer-employment-proofs/pending-proof.pdf',
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'pending-proof.pdf',
            'employment_proof_mime_type' => 'application/pdf',
            'employment_proof_size_bytes' => 2048,
            'employment_proof_uploaded_at' => now(),
            'employment_proof_status' => TracerRecord::EMPLOYMENT_PROOF_STATUS_PENDING,
            'contact_permission' => true,
        ]);

        $this->artisan('employment-proof:mark-overdue')
            ->expectsOutput('Marked 1 overdue alumni account(s) as unemployed.')
            ->assertExitCode(0);

        $this->assertSame('No', $overdueWithoutProof->fresh()->employed_after_grad);
        $this->assertNull($recentWithoutProof->fresh()->employed_after_grad);
        $this->assertNull($overdueWithProof->fresh()->employed_after_grad);
    }
}
