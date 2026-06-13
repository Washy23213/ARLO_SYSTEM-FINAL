<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use App\Notifications\JobSubmissionApprovedNotification;
use App\Notifications\JobSubmissionRejectedNotification;
use App\Services\AlumniBatchImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.facebook.page_sync_enabled' => false]);
        config(['services.alumni_records_json_path' => storage_path('framework/testing/alumni-records/'.Str::uuid().'.json')]);
    }

    protected function adminUser(): User
    {
        return User::factory()->admin()->create();
    }

    protected function alumniBatchCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, AlumniBatchImportService::REQUIRED_COLUMNS);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    public function test_admin_can_create_update_and_delete_alumni(): void
    {
        $admin = $this->adminUser();

        $createResponse = $this->actingAs($admin)->post(route('admin.alumni.store'), [
            'firstname' => 'John',
            'lastname' => 'Smith',
            'email' => 'john.smith@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '09123456789',
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2022,
            'employed_after_grad' => 'Yes',
            'industry' => 'Information Technology',
            'current_position' => 'Developer',
            'company' => 'CMU Tech',
            'location' => 'Bukidnon',
            'salary_range' => '25,000.00 - 34,999.00',
        ]);

        $createResponse->assertRedirect(route('admin.alumni'));
        $this->assertDatabaseHas('users', [
            'email' => 'john.smith@example.com',
            'firstname' => 'John',
        ]);

        $alumni = User::where('email', 'john.smith@example.com')->firstOrFail();

        $updateResponse = $this->actingAs($admin)->put(route('admin.alumni.update', $alumni->id), [
            'firstname' => 'Johnny',
            'lastname' => 'Smith',
            'email' => 'johnny.smith@example.com',
            'phone' => '09999999999',
            'department' => 'College of Business and Management',
            'degree' => 'BS Business Administration',
            'year_graduated' => 2023,
            'employed_after_grad' => 'No',
            'industry' => 'Government',
            'current_position' => 'Coordinator',
            'company' => 'CMU Office',
            'location' => 'Others',
            'specific_location' => 'Valencia City',
            'salary_range' => '15,000.00 - 24,999.00',
        ]);

        $updateResponse->assertRedirect(route('admin.alumni'));
        $this->assertDatabaseHas('users', [
            'id' => $alumni->id,
            'firstname' => 'Johnny',
            'email' => 'johnny.smith@example.com',
            'location' => 'Valencia City',
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.alumni.delete', $alumni->id));

        $deleteResponse->assertRedirect(route('admin.alumni'));
        $this->assertDatabaseMissing('users', [
            'id' => $alumni->id,
        ]);
    }

    public function test_admin_alumni_missing_employment_status_card_filters_records(): void
    {
        $admin = $this->adminUser();

        $missingStatusAlumni = User::factory()->create([
            'firstname' => 'Mia',
            'lastname' => 'Missing',
            'email' => 'mia.missing@example.com',
            'department' => 'College of Veterinary Medicine',
            'degree' => 'Doctor of Veterinary Medicine',
            'year_graduated' => 2026,
            'employed_after_grad' => null,
        ]);

        User::factory()->create([
            'firstname' => 'Eli',
            'lastname' => 'Employed',
            'email' => 'eli.employed@example.com',
            'department' => 'College of Veterinary Medicine',
            'degree' => 'Doctor of Veterinary Medicine',
            'year_graduated' => 2026,
            'employed_after_grad' => 'Yes',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.alumni'));

        $response->assertOk();
        $response->assertSee('Missing Employment Status');
        $response->assertSee(route('admin.alumni', ['status' => 'unknown']) . '#alumni-directory', false);

        $filteredResponse = $this->actingAs($admin)->get(route('admin.alumni', ['status' => 'unknown']));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('aria-current="page"', false);
        $filteredResponse->assertSee('Showing missing employment status');
        $filteredResponse->assertSee('Missing employment status only');
        $filteredResponse->assertSee('class="portal-clickable-row group admin-alumni-row--needs-update"', false);
        $filteredResponse->assertSee('Mia');
        $filteredResponse->assertSee('Missing');
        $filteredResponse->assertSee('Missing Status');
        $filteredResponse->assertDontSee('Eli');
        $this->assertSame('mia.missing@example.com', $missingStatusAlumni->fresh()->email);
    }

    public function test_admin_can_import_up_to_three_csv_batch_files(): void
    {
        $admin = $this->adminUser();

        $firstFile = UploadedFile::fake()->createWithContent('batch-one.csv', $this->alumniBatchCsv([
            ['1', 'ALU-001', 'STU-001', 'Reyes', 'Ana', '', '1999-01-15', 'College of Information Sciences and Computing', 'Bachelor of Science in Information Technology', '2026-06-15'],
        ]));
        $secondFile = UploadedFile::fake()->createWithContent('batch-two.csv', $this->alumniBatchCsv([
            ['1', 'ALU-002', 'STU-002', 'Santos', 'Ben', '', '1998-02-20', 'College of Agriculture', 'Bachelor of Science in Agribusiness Management', '2026-06-15'],
        ]));

        $response = $this->actingAs($admin)->post(route('admin.alumni.import-batch'), [
            'batch_files' => [$firstFile, $secondFile],
        ]);

        $response->assertRedirect(route('admin.alumni', ['year' => '2026']));
        $response->assertSessionHas('success', 'Batch import completed for 2 CSV files. Imported 2 new alumni and updated 0 existing alumni. These records are now available for alumni registration verification.');
        $this->assertDatabaseHas('users', [
            'alumni_idnum' => 'ALU-001',
            'firstname' => 'Ana',
            'lastname' => 'Reyes',
            'is_batch_imported' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'alumni_idnum' => 'ALU-002',
            'firstname' => 'Ben',
            'lastname' => 'Santos',
            'is_batch_imported' => true,
        ]);
    }

    public function test_admin_batch_import_rejects_non_csv_files(): void
    {
        $admin = $this->adminUser();
        $file = UploadedFile::fake()->createWithContent('batch.xlsx', $this->alumniBatchCsv([
            ['1', 'ALU-003', 'STU-003', 'Cruz', 'Mia', '', '1997-03-25', 'College of Engineering', 'Bachelor of Science in Civil Engineering', '2026-06-15'],
        ]));

        $response = $this->actingAs($admin)->from(route('admin.alumni'))->post(route('admin.alumni.import-batch'), [
            'batch_files' => [$file],
        ]);

        $response->assertRedirect(route('admin.alumni'));
        $response->assertSessionHasErrors('batch_files.0');
        $this->assertDatabaseMissing('users', [
            'alumni_idnum' => 'ALU-003',
        ]);
    }

    public function test_admin_batch_import_allows_maximum_three_files(): void
    {
        $admin = $this->adminUser();
        $files = collect(range(1, 4))
            ->map(fn ($number) => UploadedFile::fake()->createWithContent("batch-{$number}.csv", $this->alumniBatchCsv([
                [$number, "ALU-10{$number}", "STU-10{$number}", "Batch{$number}", 'Test', '', '1996-04-10', 'College of Engineering', 'Bachelor of Science in Civil Engineering', '2026-06-15'],
            ])))
            ->all();

        $response = $this->actingAs($admin)->from(route('admin.alumni'))->post(route('admin.alumni.import-batch'), [
            'batch_files' => $files,
        ]);

        $response->assertRedirect(route('admin.alumni'));
        $response->assertSessionHasErrors('batch_files');
        $this->assertDatabaseMissing('users', [
            'alumni_idnum' => 'ALU-101',
        ]);
    }

    public function test_admin_batch_import_ignores_extra_csv_columns(): void
    {
        $admin = $this->adminUser();
        $handle = fopen('php://temp', 'rb+');

        fputcsv($handle, array_merge(AlumniBatchImportService::REQUIRED_COLUMNS, ['NOTES', 'EXTRA_FIELD']));
        fputcsv($handle, ['1', 'ALU-004', 'STU-004', 'Garcia', 'Lia', 'Mendoza', '1999-05-12', 'College of Arts and Sciences', 'Bachelor of Science in Biology', '2026-06-15', 'Should be ignored', 'Also ignored']);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $file = UploadedFile::fake()->createWithContent('batch-extra-columns.csv', (string) $csv);

        $response = $this->actingAs($admin)->post(route('admin.alumni.import-batch'), [
            'batch_files' => [$file],
        ]);

        $response->assertRedirect(route('admin.alumni', ['year' => '2026']));
        $this->assertDatabaseHas('users', [
            'alumni_idnum' => 'ALU-004',
            'student_idnum' => 'STU-004',
            'firstname' => 'Lia',
            'lastname' => 'Garcia',
            'birthdate' => '1999-05-12',
            'is_batch_imported' => true,
        ]);
    }

    public function test_admin_alumni_update_can_return_json_for_inline_editing(): void
    {
        $admin = $this->adminUser();
        $alumni = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Jamie',
            'lastname' => 'Lee',
            'email' => 'jamie.lee@example.com',
            'year_graduated' => 2022,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->put(route('admin.alumni.update', $alumni->id), [
                'firstname' => 'Jamie',
                'lastname' => 'Lopez',
                'email' => 'jamie.lopez@example.com',
                'phone' => '09123456789',
                'department' => 'College of Engineering',
                'degree' => 'BS Information Technology',
                'year_graduated' => 2022,
                'employed_after_grad' => 'Yes',
                'industry' => 'Information Technology',
                'current_position' => 'Developer',
                'company' => 'CMU Tech',
                'location' => 'Bukidnon',
                'salary_range' => '25,000.00 - 34,999.00',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Alumni record updated successfully.',
                'displayName' => 'Jamie Lopez',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $alumni->id,
            'lastname' => 'Lopez',
            'email' => 'jamie.lopez@example.com',
        ]);
    }

    public function test_admin_alumni_update_assigns_a_fresh_tracer_number_when_syncing_history(): void
    {
        $admin = $this->adminUser();
        $alumni = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Mara',
            'lastname' => 'Santos',
            'email' => 'mara.santos@example.com',
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'employed_after_grad' => 'Yes',
            'industry' => 'Information Technology',
            'current_position' => 'Developer',
            'company' => 'CMU Tech',
            'location' => 'Bukidnon',
            'salary_range' => '25,000.00 - 34,999.00',
        ]);

        DB::table('tracer_records')->insert([
            'id' => (string) Str::uuid(),
            'tracer_number' => 'UTF-00002A',
            'user_id' => $alumni->id,
            'tracer_date' => now()->subDay()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'current_position' => 'Developer',
            'current_company' => 'CMU Tech',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'industry' => 'Information Technology',
            'further_education' => false,
            'contact_permission' => true,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.alumni.update', $alumni->id), [
            'firstname' => 'Mara',
            'lastname' => 'Santos',
            'email' => 'mara.santos@example.com',
            'phone' => '09123456789',
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'employed_after_grad' => 'Yes',
            'industry' => 'Information Technology',
            'current_position' => 'Senior Developer',
            'company' => 'CMU Tech',
            'location' => 'Bukidnon',
            'salary_range' => '35,000.00 - 44,999.00',
        ]);

        $response->assertRedirect(route('admin.alumni'));
        $this->assertSame(2, DB::table('tracer_records')->where('user_id', $alumni->id)->count());
        $this->assertDatabaseHas('tracer_records', [
            'user_id' => $alumni->id,
            'tracer_number' => 'UTF-00002A',
            'current_position' => 'Developer',
        ]);
        $this->assertDatabaseHas('tracer_records', [
            'user_id' => $alumni->id,
            'tracer_number' => 'UTF-00003A',
            'current_position' => 'Senior Developer',
        ]);
    }

    public function test_admin_can_create_update_and_delete_job(): void
    {
        $admin = $this->adminUser();

        $createResponse = $this->actingAs($admin)->post(route('admin.jobs.store'), [
            'title' => 'Junior Developer',
            'company' => 'CMU Tech',
            'type' => 'Full-time',
            'location' => 'Bukidnon',
            'description' => 'This is a test job description with enough detail.',
            'requirements' => 'Laravel, SQL, communication',
            'contact_email' => 'jobs@example.com',
            'contact_phone' => '09123456789',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'salary_range' => '25,000.00 - 34,999.00',
            'is_active' => '1',
        ]);

        $createResponse->assertRedirect(route('admin.jobs'));
        $job = DB::table('jobs')->where('title', 'Junior Developer')->first();
        $this->assertNotNull($job);

        $updateResponse = $this->actingAs($admin)->put(route('admin.jobs.update', $job->id), [
            'title' => 'Senior Developer',
            'company' => 'CMU Tech',
            'type' => 'Contract',
            'location' => 'Others',
            'specific_location' => 'Cagayan de Oro',
            'description' => 'This is an updated test job description with enough detail.',
            'requirements' => 'Laravel, leadership, architecture',
            'contact_email' => 'hiring@example.com',
            'contact_phone' => '09999999999',
            'application_deadline' => now()->addDays(14)->toDateString(),
            'salary_range' => '40,000.00 - 50,000.00',
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.jobs'));
        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'title' => 'Senior Developer',
            'location' => 'Cagayan de Oro',
            'contact_email' => 'hiring@example.com',
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.jobs.delete', $job->id));

        $deleteResponse->assertRedirect(route('admin.jobs'));
        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
        ]);
        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_job_update_can_return_json_for_inline_editing(): void
    {
        $admin = $this->adminUser();
        $jobId = (string) Str::uuid();

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Junior Developer',
            'company' => 'CMU Tech',
            'type' => 'Full-time',
            'location' => 'Bukidnon',
            'description' => 'This is a test job description with enough detail.',
            'requirements' => 'Laravel, SQL, communication',
            'contact_email' => 'jobs@example.com',
            'contact_phone' => '09123456789',
            'application_deadline' => now()->addDays(7)->toDateString(),
            'salary_range' => '25,000.00 - 34,999.00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->put(route('admin.jobs.update', $jobId), [
                'title' => 'Senior Developer',
                'company' => 'CMU Tech',
                'type' => 'Contract',
                'location' => 'Others',
                'specific_location' => 'Cagayan de Oro',
                'description' => 'This is an updated test job description with enough detail.',
                'requirements' => 'Laravel, leadership, architecture',
                'contact_email' => 'hiring@example.com',
                'contact_phone' => '09999999999',
                'application_deadline' => now()->addDays(14)->toDateString(),
                'salary_range' => '40,000.00 - 50,000.00',
                'is_active' => '1',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Job updated successfully.',
            ]);

        $this->assertDatabaseHas('jobs', [
            'id' => $jobId,
            'title' => 'Senior Developer',
            'location' => 'Cagayan de Oro',
            'contact_email' => 'hiring@example.com',
        ]);
    }

    public function test_admin_can_create_update_and_delete_announcement(): void
    {
        $admin = $this->adminUser();

        $createResponse = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Career Fair',
            'content' => 'This is a test announcement content for the upcoming career fair.',
            'type' => 'Event',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'target_department' => 'College of Engineering',
            'target_graduation_year' => 2023,
            'event_date' => now()->addDays(10)->toDateString(),
            'event_location' => 'Main Campus',
            'expires_at' => now()->addDays(30)->toDateString(),
            'is_published' => '1',
        ]);

        $createResponse->assertRedirect(route('admin.announcements'));
        $announcement = DB::table('announcements')->where('title', 'Career Fair')->first();
        $this->assertNotNull($announcement);
        $this->assertSame('College of Engineering', $announcement->target_department);
        $this->assertSame(2023, (int) $announcement->target_graduation_year);

        $updateResponse = $this->actingAs($admin)->put(route('admin.announcements.update', $announcement->id), [
            'title' => 'Career Fair 2026',
            'content' => 'This is updated announcement content for the career fair event.',
            'type' => 'Event',
            'priority' => 'Urgent',
            'target_audience' => 'All',
            'target_department' => 'College of Education',
            'target_graduation_year' => 2024,
            'event_date' => now()->addDays(12)->toDateString(),
            'event_location' => 'Others',
            'specific_event_location' => 'University Auditorium',
            'expires_at' => now()->addDays(40)->toDateString(),
            'is_published' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.announcements'));
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Career Fair 2026',
            'event_location' => 'University Auditorium',
            'target_department' => 'College of Education',
            'target_graduation_year' => 2024,
        ]);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.announcements.delete', $announcement->id));

        $deleteResponse->assertRedirect(route('admin.announcements'));
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
        ]);
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'is_published' => false,
        ]);
    }

    public function test_admin_announcement_list_marks_records_read_after_opening_detail(): void
    {
        $admin = $this->adminUser();
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Read Status Bulletin',
            'content' => 'This announcement verifies the admin read badge.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $listResponse = $this->actingAs($admin)->get(route('admin.announcements'));

        $listResponse->assertOk();
        $listResponse->assertSee('Unread');
        $listResponse->assertViewHas('announcements', function ($announcements) use ($announcementId) {
            $announcement = $announcements->getCollection()->firstWhere('id', $announcementId);

            return $announcement !== null && $announcement->is_read === false;
        });

        $detailResponse = $this->actingAs($admin)->get(route('admin.announcements.show', $announcementId));

        $detailResponse->assertOk();
        $detailResponse->assertSee('Read Status');
        $this->assertDatabaseMissing('announcement_reads', [
            'announcement_id' => $announcementId,
            'user_id' => $admin->id,
        ]);

        $updatedListResponse = $this->actingAs($admin)->get(route('admin.announcements'));

        $updatedListResponse->assertOk();
        $updatedListResponse->assertViewHas('announcements', function ($announcements) use ($announcementId) {
            $announcement = $announcements->getCollection()->firstWhere('id', $announcementId);

            return $announcement !== null && $announcement->is_read === false;
        });
    }

    public function test_admin_notification_management_shows_actionable_stat_cards(): void
    {
        $admin = $this->adminUser();
        User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'is_batch_imported' => false,
            'newsletter_opt_in' => true,
            'email' => 'registered.recipient@example.com',
        ]);
        User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'is_batch_imported' => true,
            'newsletter_opt_in' => true,
            'email' => 'csv.only@example.com',
        ]);

        DB::table('announcements')->insert([
            [
                'id' => (string) Str::uuid(),
                'title' => 'Saved Draft Notice',
                'content' => 'This notification still needs to be reviewed before publishing.',
                'type' => 'Update',
                'priority' => 'Medium',
                'target_audience' => 'All',
                'is_published' => false,
                'expires_at' => null,
                'facebook_post_id' => null,
                'facebook_post_url' => null,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Urgent Active Notice',
                'content' => 'This notification is published and should remain visible.',
                'type' => 'Alert',
                'priority' => 'Urgent',
                'target_audience' => 'All',
                'is_published' => true,
                'expires_at' => now()->addDay(),
                'facebook_post_id' => '123456789_999',
                'facebook_post_url' => 'https://www.facebook.com/123456789/posts/999',
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Expired Urgent Notice',
                'content' => 'This notification should not count as active high priority.',
                'type' => 'Alert',
                'priority' => 'Urgent',
                'target_audience' => 'All',
                'is_published' => true,
                'expires_at' => now()->subDay(),
                'facebook_post_id' => null,
                'facebook_post_url' => null,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.announcements'));

        $response->assertOk()
            ->assertSee('Visible to Alumni')
            ->assertSee('Published and current')
            ->assertSee('Priority Notices')
            ->assertSee('High or urgent')
            ->assertSee('Facebook Synced')
            ->assertSee('Linked FB posts')
            ->assertViewHas('notificationRecordsCount', 3)
            ->assertViewHas('activeAnnouncements', 1)
            ->assertViewHas('priorityNoticeCount', 1)
            ->assertViewHas('facebookSyncedCount', 1);
    }

    public function test_admin_announcement_update_can_return_json_for_inline_editing(): void
    {
        $admin = $this->adminUser();
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Career Fair',
            'content' => 'This is a test announcement content for the upcoming career fair.',
            'type' => 'Event',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'target_department' => 'College of Engineering',
            'target_graduation_year' => 2023,
            'event_date' => now()->addDays(10)->toDateString(),
            'event_location' => 'Main Campus',
            'expires_at' => now()->addDays(30)->toDateString(),
            'is_published' => true,
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->put(route('admin.announcements.update', $announcementId), [
                'title' => 'Career Fair 2026',
                'content' => 'This is updated announcement content for the career fair event.',
                'type' => 'Event',
                'priority' => 'Urgent',
                'target_audience' => 'All',
                'target_department' => 'College of Education',
                'target_graduation_year' => 2024,
                'event_date' => now()->addDays(12)->toDateString(),
                'event_location' => 'Others',
                'specific_event_location' => 'University Auditorium',
                'expires_at' => now()->addDays(40)->toDateString(),
                'is_published' => '1',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Announcement updated successfully.',
            ]);

        $this->assertDatabaseHas('announcements', [
            'id' => $announcementId,
            'title' => 'Career Fair 2026',
            'event_location' => 'University Auditorium',
            'target_department' => 'College of Education',
            'target_graduation_year' => 2024,
        ]);
    }

    public function test_publishing_an_announcement_can_share_to_facebook_page_when_enabled(): void
    {
        config([
            'services.facebook.page_sync_enabled' => true,
            'services.facebook.graph_version' => 'v25.0',
            'services.facebook.page_id' => '123456789',
            'services.facebook.page_access_token' => 'page-token',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'id' => '123456789_987654321',
            ], 200),
        ]);

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Facebook Live Briefing',
            'content' => 'This announcement should also be shared to the official Facebook page.',
            'type' => 'Update',
            'priority' => 'High',
            'target_audience' => 'All',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.announcements'));
        $response->assertSessionHas('success', 'Announcement created successfully. Shared to Facebook Page.');
        $this->assertDatabaseHas('announcements', [
            'title' => 'Facebook Live Briefing',
            'is_published' => true,
            'facebook_post_id' => '123456789_987654321',
            'facebook_post_url' => 'https://www.facebook.com/story.php?story_fbid=987654321&id=123456789',
        ]);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v25.0/123456789/feed'
                && $request['access_token'] === 'page-token'
                && str_contains((string) $request['message'], 'Facebook Live Briefing');
        });
    }

    public function test_facebook_page_sync_failure_does_not_block_announcement_creation(): void
    {
        config([
            'services.facebook.page_sync_enabled' => true,
            'services.facebook.graph_version' => 'v25.0',
            'services.facebook.page_id' => '123456789',
            'services.facebook.page_access_token' => 'invalid-token',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                ],
            ], 400),
        ]);

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Fallback Publish',
            'content' => 'The announcement should still save even if the Facebook token is rejected.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.announcements'));
        $response->assertSessionHas('success', 'Announcement created successfully. Facebook Page sync failed.');
        $this->assertDatabaseHas('announcements', [
            'title' => 'Fallback Publish',
            'is_published' => true,
        ]);
        Http::assertSentCount(1);
    }

    public function test_updating_a_published_announcement_can_update_the_existing_facebook_post(): void
    {
        config([
            'services.facebook.page_sync_enabled' => true,
            'services.facebook.graph_version' => 'v25.0',
            'services.facebook.page_id' => '123456789',
            'services.facebook.page_access_token' => 'page-token',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $admin = $this->adminUser();
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Facebook-synced Announcement',
            'content' => 'Original synchronized content.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => true,
            'published_at' => now()->subHour(),
            'views_count' => 0,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
            'facebook_post_id' => '123456789_555',
            'facebook_last_synced_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->put(route('admin.announcements.update', $announcementId), [
            'title' => 'Facebook-synced Announcement Revised',
            'content' => 'Revised content that should update the existing Facebook post as well.',
            'type' => 'Update',
            'priority' => 'High',
            'target_audience' => 'All',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.announcements'));
        $response->assertSessionHas('success', 'Announcement updated successfully. Updated Facebook Page post.');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v25.0/123456789_555'
                && $request['access_token'] === 'page-token'
                && str_contains((string) $request['message'], 'Facebook-synced Announcement Revised');
        });
    }

    public function test_admin_can_import_recent_facebook_page_posts_into_announcements_and_notify_alumni(): void
    {
        config(['mail.default' => 'array']);
        config([
            'services.facebook.page_sync_enabled' => true,
            'services.facebook.graph_version' => 'v25.0',
            'services.facebook.page_id' => '123456789',
            'services.facebook.page_access_token' => 'page-token',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => '123456789_777',
                        'message' => "Campus briefing update\n\nThe alumni office will hold a briefing tomorrow at 2 PM.",
                        'permalink_url' => 'https://www.facebook.com/123456789/posts/777',
                        'created_time' => '2026-04-23T08:15:00+0000',
                        'updated_time' => '2026-04-23T08:45:00+0000',
                        'full_picture' => 'https://example.com/post-image.jpg',
                    ],
                ],
            ], 200),
        ]);

        $admin = $this->adminUser();
        $alumni = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($admin)->post(route('admin.announcements.sync-facebook'));

        $response->assertRedirect(route('admin.announcements'));
        $response->assertSessionHas('success', 'Facebook sync completed. Imported 1 new post and updated 0 existing announcements.');
        $this->assertDatabaseHas('announcements', [
            'facebook_post_id' => '123456789_777',
            'title' => 'Campus briefing update',
            'is_published' => true,
            'facebook_post_url' => 'https://www.facebook.com/story.php?story_fbid=777&id=123456789',
        ]);
        $this->assertDatabaseHas('notifications', [
            'type' => AnnouncementPublishedNotification::class,
            'notifiable_id' => $alumni->id,
        ]);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://graph.facebook.com/v25.0/123456789/posts');
        });
    }

    public function test_admin_facebook_sync_can_follow_paging_to_backfill_multiple_page_results(): void
    {
        config([
            'services.facebook.page_sync_enabled' => true,
            'services.facebook.graph_version' => 'v25.0',
            'services.facebook.page_id' => '123456789',
            'services.facebook.page_access_token' => 'page-token',
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::sequence()
                ->push([
                    'data' => [
                        [
                            'id' => '123456789_901',
                            'message' => "Backfill batch one\n\nImported from the first page of results.",
                            'permalink_url' => 'https://www.facebook.com/123456789/posts/901',
                            'created_time' => '2026-04-20T08:15:00+0000',
                            'updated_time' => '2026-04-20T08:15:00+0000',
                        ],
                    ],
                    'paging' => [
                        'next' => 'https://graph.facebook.com/v25.0/123456789/posts?after=batch-2',
                    ],
                ], 200)
                ->push([
                    'data' => [
                        [
                            'id' => '123456789_902',
                            'message' => "Backfill batch two\n\nImported from the second page of results.",
                            'permalink_url' => 'https://www.facebook.com/123456789/posts/902',
                            'created_time' => '2026-04-19T07:45:00+0000',
                            'updated_time' => '2026-04-19T07:45:00+0000',
                        ],
                    ],
                ], 200),
        ]);

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.announcements.sync-facebook'), [
            'limit' => 2,
        ]);

        $response->assertRedirect(route('admin.announcements'));
        $response->assertSessionHas('success', 'Facebook sync completed. Imported 2 new posts and updated 0 existing announcements.');
        $this->assertDatabaseHas('announcements', [
            'facebook_post_id' => '123456789_901',
            'title' => 'Backfill batch one',
        ]);
        $this->assertDatabaseHas('announcements', [
            'facebook_post_id' => '123456789_902',
            'title' => 'Backfill batch two',
        ]);
        Http::assertSentCount(2);
    }

    public function test_facebook_import_updates_existing_announcement_without_creating_a_duplicate_and_backfills_missing_notifications(): void
    {
        config(['mail.default' => 'array']);
        config([
            'services.facebook.page_sync_enabled' => true,
            'services.facebook.graph_version' => 'v25.0',
            'services.facebook.page_id' => '123456789',
            'services.facebook.page_access_token' => 'page-token',
        ]);

        $announcementId = (string) Str::uuid();
        $alumni = User::factory()->create(['is_admin' => false]);

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Older Facebook Title',
            'content' => 'Older content.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'views_count' => 0,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
            'facebook_post_id' => '123456789_888',
            'facebook_post_url' => 'https://www.facebook.com/123456789/posts/888',
            'facebook_last_synced_at' => now()->subDays(2),
        ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => '123456789_888',
                        'message' => "Updated Facebook title\n\nFresh content from the page.",
                        'permalink_url' => 'https://www.facebook.com/123456789/posts/888',
                        'created_time' => '2026-04-21T08:15:00+0000',
                        'updated_time' => '2026-04-23T09:30:00+0000',
                    ],
                ],
            ], 200),
        ]);

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.announcements.sync-facebook'));

        $response->assertRedirect(route('admin.announcements'));
        $response->assertSessionHas('success', 'Facebook sync completed. Imported 0 new posts and updated 1 existing announcement.');

        $this->assertSame(1, DB::table('announcements')->where('facebook_post_id', '123456789_888')->count());
        $this->assertDatabaseHas('announcements', [
            'id' => $announcementId,
            'title' => 'Updated Facebook title',
        ]);
        $this->assertDatabaseHas('notifications', [
            'type' => AnnouncementPublishedNotification::class,
            'notifiable_id' => $alumni->id,
        ]);
    }

    public function test_facebook_sync_archives_local_announcements_when_source_post_is_unavailable_on_facebook(): void
    {
        config([
            'services.facebook.page_sync_enabled' => true,
            'services.facebook.graph_version' => 'v25.0',
            'services.facebook.page_id' => '123456789',
            'services.facebook.page_access_token' => 'page-token',
        ]);

        $existingPostId = (string) Str::uuid();
        $missingPostId = (string) Str::uuid();

        DB::table('announcements')->insert([
            [
                'id' => $existingPostId,
                'title' => 'Still on Facebook',
                'content' => 'This post still exists on Facebook.',
                'type' => 'Update',
                'priority' => 'Medium',
                'target_audience' => 'All',
                'is_published' => true,
                'published_at' => now()->subDays(2),
                'views_count' => 0,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
                'facebook_post_id' => '123456789_111',
                'facebook_post_url' => 'https://www.facebook.com/story.php?story_fbid=111&id=123456789',
                'facebook_last_synced_at' => now()->subDays(2),
            ],
            [
                'id' => $missingPostId,
                'title' => 'Deleted on Facebook',
                'content' => 'This post was removed directly on Facebook.',
                'type' => 'Update',
                'priority' => 'Medium',
                'target_audience' => 'All',
                'is_published' => true,
                'published_at' => now()->subDays(3),
                'views_count' => 0,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
                'facebook_post_id' => '123456789_222',
                'facebook_post_url' => 'https://www.facebook.com/story.php?story_fbid=222&id=123456789',
                'facebook_last_synced_at' => now()->subDays(3),
            ],
        ]);

        Http::fake(function ($request) {
            if (str_starts_with($request->url(), 'https://graph.facebook.com/v25.0/123456789/posts')) {
                return Http::response([
                    'data' => [
                        [
                            'id' => '123456789_111',
                            'message' => "Still on Facebook\n\nFresh content from the live feed.",
                            'permalink_url' => 'https://www.facebook.com/123456789/posts/111',
                            'created_time' => '2026-04-23T08:15:00+0000',
                            'updated_time' => '2026-04-23T08:45:00+0000',
                        ],
                    ],
                ], 200);
            }

            if (str_starts_with($request->url(), 'https://graph.facebook.com/v25.0/123456789_222')) {
                return Http::response([
                    'error' => [
                        'message' => 'Unsupported get request. Object with ID does not exist, cannot be loaded due to missing permissions, or does not support this operation.',
                        'type' => 'GraphMethodException',
                        'code' => 100,
                        'error_subcode' => 33,
                    ],
                ], 400);
            }

            return Http::response([], 404);
        });

        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('admin.announcements.sync-facebook'), [
            'limit' => 100,
        ]);

        $response->assertRedirect(route('admin.announcements'));
        $response->assertSessionHas('success', 'Facebook sync completed. Imported 0 new posts and updated 1 existing announcement. Archived 1 missing Facebook post.');

        $this->assertDatabaseHas('announcements', [
            'id' => $existingPostId,
            'is_published' => true,
            'archived_at' => null,
        ]);
        $this->assertDatabaseHas('announcements', [
            'id' => $missingPostId,
            'is_published' => false,
        ]);
        $this->assertNotNull(DB::table('announcements')->where('id', $missingPostId)->value('archived_at'));
    }

    public function test_publishing_an_announcement_notifies_alumni_without_duplicate_resends(): void
    {
        config(['mail.default' => 'array']);

        $admin = $this->adminUser();
        $alumniOne = User::factory()->create(['is_admin' => false]);
        $alumniTwo = User::factory()->create(['is_admin' => false]);

        $createResponse = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Graduate Tracer Update',
            'content' => 'Please review the updated graduate tracer schedule and participation reminders.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'Alumni',
            'is_published' => '1',
        ]);

        $createResponse->assertRedirect(route('admin.announcements'));
        $createResponse->assertSessionHas('success', 'Announcement created successfully. Notified 2 alumni.');

        $announcement = DB::table('announcements')->where('title', 'Graduate Tracer Update')->firstOrFail();

        $this->assertSame(2, DB::table('notifications')->count());
        $this->assertSame(
            2,
            DB::table('notifications')
                ->where('type', AnnouncementPublishedNotification::class)
                ->whereIn('notifiable_id', [$alumniOne->id, $alumniTwo->id])
                ->count()
        );
        $this->assertSame(
            0,
            DB::table('notifications')
                ->where('notifiable_id', $admin->id)
                ->count()
        );

        $updateResponse = $this->actingAs($admin)->put(route('admin.announcements.update', $announcement->id), [
            'title' => 'Graduate Tracer Update Revised',
            'content' => 'The graduate tracer reminder has been refreshed with a clearer participation timeline.',
            'type' => 'Update',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'is_published' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.announcements'));
        $updateResponse->assertSessionHas('success', 'Announcement updated successfully.');
        $this->assertSame(2, DB::table('notifications')->count());
    }

    public function test_publishing_a_targeted_announcement_only_notifies_matching_alumni(): void
    {
        config(['mail.default' => 'array']);

        $admin = $this->adminUser();
        $matchingAlumni = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Engineering',
            'year_graduated' => 2023,
        ]);
        $differentCollegeAlumni = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Education',
            'year_graduated' => 2023,
        ]);
        $differentYearAlumni = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Engineering',
            'year_graduated' => 2022,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Engineering Batch 2023 Briefing',
            'content' => 'Only engineering alumni from batch 2023 should receive this campus update.',
            'type' => 'Update',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'target_department' => 'College of Engineering',
            'target_graduation_year' => 2023,
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.announcements'));
        $response->assertSessionHas('success', 'Announcement created successfully. Notified 1 alumni.');

        $announcement = DB::table('announcements')->where('title', 'Engineering Batch 2023 Briefing')->firstOrFail();

        $this->assertSame('College of Engineering', $announcement->target_department);
        $this->assertSame(2023, (int) $announcement->target_graduation_year);
        $this->assertSame(
            1,
            DB::table('notifications')
                ->where('type', AnnouncementPublishedNotification::class)
                ->where('notifiable_id', $matchingAlumni->id)
                ->count()
        );
        $this->assertSame(
            0,
            DB::table('notifications')
                ->where('type', AnnouncementPublishedNotification::class)
                ->whereIn('notifiable_id', [$differentCollegeAlumni->id, $differentYearAlumni->id])
                ->count()
        );
    }

    public function test_admin_cannot_create_duplicate_alumni_with_same_name_and_year(): void
    {
        $admin = $this->adminUser();

        User::factory()->create([
            'firstname' => 'Jane',
            'lastname' => 'Doe',
            'email' => 'jane.original@example.com',
            'year_graduated' => 2021,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.alumni.create'))
            ->post(route('admin.alumni.store'), [
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'email' => 'jane.duplicate@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'year_graduated' => 2021,
            ]);

        $response->assertRedirect(route('admin.alumni.create'));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', [
            'email' => 'jane.duplicate@example.com',
        ]);
    }

    public function test_admin_can_approve_an_alumni_job_submission(): void
    {
        $admin = $this->adminUser();
        $alumni = User::factory()->create();
        Notification::fake();

        $submissionId = DB::table('job_submissions')->insertGetId([
            'user_id' => $alumni->id,
            'title' => 'Platform Engineer',
            'company' => 'CMU Tech',
            'type' => 'Full-time',
            'location' => 'Bukidnon',
            'contact_email' => 'jobs@example.com',
            'application_deadline' => now()->addDays(10)->toDateString(),
            'description' => 'Alumni-submitted job post ready for admin review.',
            'requirements' => 'Laravel and infrastructure',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.job-submissions.approve', $submissionId));

        $response->assertRedirect(route('admin.jobs'));
        $this->assertDatabaseHas('job_submissions', [
            'id' => $submissionId,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('jobs', [
            'title' => 'Platform Engineer',
            'company' => 'CMU Tech',
            'created_by' => $admin->id,
        ]);
        Notification::assertSentTo($alumni, JobSubmissionApprovedNotification::class);
    }

    public function test_admin_can_reject_an_alumni_job_submission_and_notify_the_submitter(): void
    {
        $admin = $this->adminUser();
        $alumni = User::factory()->create();
        Notification::fake();

        $submissionId = DB::table('job_submissions')->insertGetId([
            'user_id' => $alumni->id,
            'title' => 'Data Analyst',
            'company' => 'CMU Insights',
            'type' => 'Full-time',
            'location' => 'Bukidnon',
            'contact_email' => 'jobs@example.com',
            'application_deadline' => now()->addDays(10)->toDateString(),
            'description' => 'A submission that will be rejected during the test run.',
            'requirements' => 'SQL and dashboards',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.job-submissions.reject', $submissionId), [
            'admin_notes' => 'Please add a clearer application deadline and complete contact details.',
        ]);

        $response->assertRedirect(route('admin.jobs'));
        $this->assertDatabaseHas('job_submissions', [
            'id' => $submissionId,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'admin_notes' => 'Please add a clearer application deadline and complete contact details.',
        ]);
        Notification::assertSentTo(
            $alumni,
            JobSubmissionRejectedNotification::class,
            function (JobSubmissionRejectedNotification $notification) use ($alumni) {
                $mail = $notification->toMail($alumni);

                return str_contains($mail->subject, 'needs revision')
                    && collect($mail->introLines)->contains(
                        'Admin notes: Please add a clearer application deadline and complete contact details.'
                    );
            }
        );
    }

    public function test_admin_search_pages_work_case_insensitively_without_postgres_specific_operators(): void
    {
        $admin = $this->adminUser();

        User::factory()->create([
            'firstname' => 'Taylor',
            'lastname' => 'Crossing',
            'email' => 'taylor.crossing@example.com',
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
        ]);
        User::factory()->create([
            'firstname' => 'Maria',
            'lastname' => 'Santos',
            'email' => 'maria.santos@example.com',
            'department' => 'College of Education',
            'degree' => 'BS Education',
        ]);

        DB::table('jobs')->insert([
            [
                'id' => (string) Str::uuid(),
                'title' => 'Data Platform Engineer',
                'company' => 'AgriGrow Mindanao',
                'description' => 'Build and maintain alumni analytics pipelines.',
                'requirements' => 'SQL and Laravel',
                'location' => 'Malaybalay City',
                'type' => 'Full-time',
                'contact_email' => 'careers@agrigrow.test',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Campus Registrar Assistant',
                'company' => 'Central Records Office',
                'description' => 'Handle student documents and office support.',
                'requirements' => 'Attention to detail',
                'location' => 'Musuan',
                'type' => 'Contract',
                'contact_email' => 'careers@records.test',
                'is_active' => true,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        DB::table('announcements')->insert([
            [
                'id' => (string) Str::uuid(),
                'title' => 'Graduate Tracer Reminder',
                'content' => 'Please complete the tracer survey before Friday.',
                'type' => 'Update',
                'priority' => 'Medium',
                'target_audience' => 'Alumni',
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Library Renovation Notice',
                'content' => 'The reading hall will be closed this weekend.',
                'type' => 'News',
                'priority' => 'Low',
                'target_audience' => 'All',
                'is_published' => true,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $alumniResponse = $this->actingAs($admin)->get(route('admin.alumni', ['search' => 'TAYLOR CROSS']));
        $alumniResponse->assertOk();
        $alumniResponse->assertSee('Taylor Crossing');
        $alumniResponse->assertViewHas('alumni', function ($alumni) {
            return $alumni->count() === 1
                && $alumni->first()->firstname === 'Taylor'
                && $alumni->first()->lastname === 'Crossing';
        });

        $jobsResponse = $this->actingAs($admin)->get(route('admin.jobs', ['search' => 'platform']));
        $jobsResponse->assertOk();
        $jobsResponse->assertSee('Job Posts Listed');
        $jobsResponse->assertSee('Open Job Posts');
        $jobsResponse->assertSee('Pending Alumni Requests');
        $jobsResponse->assertSee('Approved or Rejected');
        $jobsResponse->assertSee('Data Platform Engineer');
        $jobsResponse->assertViewHas('jobPostsCount', 2);
        $jobsResponse->assertViewHas('activeJobsCount', 2);
        $jobsResponse->assertViewHas('pendingSubmissionCount', 0);
        $jobsResponse->assertViewHas('reviewedSubmissionCount', 0);
        $jobsResponse->assertViewHas('jobs', function ($jobs) {
            return $jobs->count() === 1
                && $jobs->first()->title === 'Data Platform Engineer';
        });

        $announcementsResponse = $this->actingAs($admin)->get(route('admin.announcements', ['search' => 'TRACER']));
        $announcementsResponse->assertOk();
        $announcementsResponse->assertSee('Graduate Tracer Reminder');
        $announcementsResponse->assertViewHas('announcements', function ($announcements) {
            return $announcements->count() === 1
                && $announcements->first()->title === 'Graduate Tracer Reminder';
        });
    }

    public function test_admin_can_view_tracer_history_on_alumni_details_page(): void
    {
        $admin = $this->adminUser();
        $alumni = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Alyssa',
            'lastname' => 'Rivera',
        ]);

        DB::table('tracer_records')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $alumni->id,
                'tracer_date' => now()->subMonths(2)->toDateString(),
                'is_employed' => true,
                'employment_type' => 'Full-time',
                'current_position' => 'QA Engineer',
                'current_company' => 'CMU Tech',
                'monthly_income' => 'P20,000.00 to less than P30,000.00',
                'industry' => 'Information Technology',
                'job_satisfaction' => 4,
                'further_education' => false,
                'education_level' => null,
                'contact_permission' => true,
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subMonths(2),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $alumni->id,
                'tracer_date' => now()->subMonth()->toDateString(),
                'is_employed' => true,
                'employment_type' => 'Self-Employed',
                'current_position' => 'Consultant',
                'current_company' => 'Rivera Solutions',
                'monthly_income' => 'P40,000.00 to less than P50,000.00',
                'industry' => 'Business Services',
                'job_satisfaction' => 5,
                'further_education' => true,
                'education_level' => "Master's Degree",
                'contact_permission' => true,
                'created_at' => now()->subMonth(),
                'updated_at' => now()->subMonth(),
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.alumni.show', $alumni->id));

        $response->assertOk();
        $response->assertSee('Edit Record');
        $response->assertSee('Delete');
        $response->assertSee(route('admin.alumni.edit', $alumni->id), false);
        $response->assertSee(route('admin.alumni.delete', $alumni->id), false);
        $response->assertSee('Tracer History');
        $response->assertSee('Latest Tracer Snapshot');
        $response->assertSee('Rivera Solutions');
        $response->assertSee('QA Engineer');
    }

    public function test_admin_can_open_an_alumnis_protected_employment_proof_from_the_details_page(): void
    {
        Storage::fake('private');

        $admin = $this->adminUser();
        $alumni = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Alyssa',
            'lastname' => 'Rivera',
        ]);
        $proofPath = 'tracer-employment-proofs/admin-proof.png';

        Storage::disk('private')->put($proofPath, 'admin-visible-proof');

        $recordId = (string) Str::uuid();

        DB::table('tracer_records')->insert([
            'id' => $recordId,
            'user_id' => $alumni->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'current_position' => 'QA Engineer',
            'current_company' => 'CMU Tech',
            'job_satisfaction' => 4,
            'skills_used' => 'Testing and documentation',
            'presently_employed' => 'Yes',
            'employment_proof_path' => $proofPath,
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'admin-proof.png',
            'employment_proof_mime_type' => 'image/png',
            'employment_proof_size_bytes' => strlen('admin-visible-proof'),
            'employment_proof_uploaded_at' => now(),
            'contact_permission' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $detailsPage = $this->actingAs($admin)->get(route('admin.alumni.show', $alumni->id));
        $detailsPage->assertOk();
        $detailsPage->assertSee('View Employment Proof');
        $detailsPage->assertSee('Pending Review');
        $detailsPage->assertSee('Verify Proof');
        $detailsPage->assertSee('Reject Proof');
        $detailsPage->assertSee('admin-proof.png');
        $detailsPage->assertSee(route('admin.alumni.proof.download', ['alumni' => $alumni->id, 'record' => $recordId]), false);

        $downloadResponse = $this->actingAs($admin)->get(route('admin.alumni.proof.download', ['alumni' => $alumni->id, 'record' => $recordId]));
        $downloadResponse->assertOk();
        $downloadResponse->assertHeader('content-type', 'image/png');
        $this->assertStringContainsString('inline', (string) $downloadResponse->headers->get('content-disposition'));

        $verifyResponse = $this
            ->actingAs($admin)
            ->post(route('admin.alumni.proof.verify', ['alumni' => $alumni->id, 'record' => $recordId]));

        $verifyResponse->assertRedirect(route('admin.alumni.show', $alumni->id));
        $this->assertDatabaseHas('tracer_records', [
            'id' => $recordId,
            'employment_proof_status' => 'verified',
            'employment_proof_reviewed_by_guard' => 'web',
            'employment_proof_reviewed_by_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $admin->id,
            'action' => 'admin.alumni.proof.verify',
        ]);
    }
}
