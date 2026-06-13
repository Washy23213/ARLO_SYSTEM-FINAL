<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use App\Notifications\JobSubmissionApprovedNotification;
use App\Support\PortalIndicatorState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlumniPortalFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_alumni_can_apply_for_an_active_job(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $jobId = (string) Str::uuid();

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'QA Analyst',
            'company' => 'CMU Tech',
            'description' => 'A quality assurance role with enough detail for testing.',
            'requirements' => 'Testing and documentation',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('job.apply', $jobId), [
            'cover_letter' => 'I am interested in this role and have relevant experience.',
            'resume_url' => 'https://example.com/resume.pdf',
        ]);

        $response->assertRedirect(route('job.detail', $jobId));
        $this->assertDatabaseHas('job_applications', [
            'job_id' => $jobId,
            'user_id' => $user->id,
            'status' => 'Pending',
        ]);
        $this->assertDatabaseHas('jobs', [
            'id' => $jobId,
            'applications_count' => 1,
        ]);
    }

    public function test_viewing_a_job_detail_increments_the_database_view_counter(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $jobId = (string) Str::uuid();

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Backend Developer',
            'company' => 'CMU Tech',
            'description' => 'A backend role with enough detail for view-count testing.',
            'requirements' => 'Laravel and PostgreSQL',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('job.detail', $jobId));

        $response->assertOk();
        $response->assertSeeTextInOrder(['Viewed', '1', 'times']);
        $this->assertDatabaseHas('jobs', [
            'id' => $jobId,
            'views_count' => 1,
        ]);
    }

    public function test_jobs_page_shows_active_job_opportunities(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $activeJobId = (string) Str::uuid();
        $inactiveJobId = (string) Str::uuid();
        $archivedJobId = (string) Str::uuid();

        DB::table('jobs')->insert([
            [
                'id' => $activeJobId,
                'title' => 'Data Analyst',
                'company' => 'CMU Careers',
                'description' => 'Analyze alumni career data and prepare reports.',
                'requirements' => 'Reporting experience',
                'location' => 'Bukidnon',
                'type' => 'Full-time',
                'contact_email' => 'jobs@example.com',
                'is_active' => true,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'archived_at' => null,
            ],
            [
                'id' => $inactiveJobId,
                'title' => 'Inactive Role',
                'company' => 'CMU Careers',
                'description' => 'This inactive role should stay hidden.',
                'requirements' => 'None',
                'location' => 'Bukidnon',
                'type' => 'Full-time',
                'contact_email' => 'jobs@example.com',
                'is_active' => false,
                'views_count' => 0,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
                'archived_at' => null,
            ],
            [
                'id' => $archivedJobId,
                'title' => 'Archived Role',
                'company' => 'CMU Careers',
                'description' => 'This archived role should stay hidden.',
                'requirements' => 'None',
                'location' => 'Bukidnon',
                'type' => 'Full-time',
                'contact_email' => 'jobs@example.com',
                'is_active' => true,
                'views_count' => 0,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
                'archived_at' => now()->subDay(),
            ],
        ]);

        $response = $this->actingAs($user)->get(route('jobs'));

        $response->assertOk();
        $response->assertSee('Current Job Opportunities');
        $response->assertSee('Data Analyst');
        $response->assertSee('CMU Careers');
        $response->assertSee(route('job.detail', $activeJobId), false);
        $response->assertDontSee('Inactive Role');
        $response->assertDontSee('Archived Role');
    }

    public function test_alumni_can_submit_feedback(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->post(route('feedback.submit'), [
            'subject' => 'Portal suggestion',
            'type' => 'Feature Request',
            'message' => 'Please add a way to bookmark job opportunities for later.',
        ]);

        $response->assertRedirect(route('feedback.form'));
        $this->assertDatabaseHas('feedback', [
            'user_id' => $user->id,
            'subject' => 'Portal suggestion',
            'type' => 'Feature Request',
            'status' => 'Open',
        ]);
    }

    public function test_feedback_form_separates_feedback_categories_for_alumni(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('feedback.form'));

        $response->assertOk();
        $response->assertSee('Choose the right feedback category first');
        $response->assertSee('Report a portal problem');
        $response->assertSee('Suggest an improvement');
        $response->assertSee('Share a general comment');
        $response->assertSee('Ask for help');
    }

    public function test_alumni_can_view_published_announcement(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Career Fair',
            'content' => 'Join the upcoming campus-wide career fair event.',
            'type' => 'Event',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('announcement.detail', $announcementId));

        $response->assertOk();
        $response->assertSee('Career Fair');
        $response->assertSeeTextInOrder(['Viewed', '1', 'times']);
        $response->assertSee('Read');
        $this->assertDatabaseHas('announcements', [
            'id' => $announcementId,
            'views_count' => 1,
        ]);
        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcementId,
            'user_id' => $user->id,
        ]);
    }

    public function test_alumni_announcements_route_redirects_to_notifications_and_still_shows_visible_updates(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Engineering',
            'year_graduated' => 2024,
        ]);
        $generalAnnouncementId = (string) Str::uuid();
        $facebookAnnouncementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            [
                'id' => $generalAnnouncementId,
                'title' => 'Career Fair Bulletin',
                'content' => 'The latest alumni career fair schedule is now available in the portal.',
                'type' => 'Event',
                'priority' => 'High',
                'target_audience' => 'Alumni',
                'target_department' => null,
                'is_published' => true,
                'expires_at' => null,
                'facebook_post_id' => null,
                'facebook_post_url' => null,
                'views_count' => 0,
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ],
            [
                'id' => $facebookAnnouncementId,
                'title' => 'Official Facebook Page Update',
                'content' => 'This update was imported from the official Facebook Page and should also appear on the alumni side.',
                'type' => 'Update',
                'priority' => 'Medium',
                'target_audience' => 'All',
                'target_department' => null,
                'is_published' => true,
                'expires_at' => null,
                'views_count' => 0,
                'facebook_post_id' => '123456789_111',
                'facebook_post_url' => 'https://www.facebook.com/123456789/posts/111',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Hidden Draft Announcement',
                'content' => 'This should stay hidden from alumni.',
                'type' => 'Update',
                'priority' => 'Low',
                'target_audience' => 'All',
                'target_department' => null,
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
                'title' => 'Education Office Only',
                'content' => 'This should not appear for engineering alumni.',
                'type' => 'Update',
                'priority' => 'Medium',
                'target_audience' => 'Alumni',
                'target_department' => 'College of Education',
                'is_published' => true,
                'expires_at' => null,
                'facebook_post_id' => null,
                'facebook_post_url' => null,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'title' => 'Expired Announcement',
                'content' => 'This should not appear because it already expired.',
                'type' => 'Alert',
                'priority' => 'High',
                'target_audience' => 'All',
                'target_department' => null,
                'is_published' => true,
                'expires_at' => now()->subMinute(),
                'facebook_post_id' => null,
                'facebook_post_url' => null,
                'views_count' => 0,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $response = $this->actingAs($user)->get(route('announcements.index'));

        $response->assertRedirect(route('notifications.index'));

        $feedResponse = $this->actingAs($user)->get(route('notifications.index'));

        $feedResponse->assertOk();
        $feedResponse->assertSee('Recent activity');
        $feedResponse->assertSee('Career Fair Bulletin');
        $feedResponse->assertSee('Official Facebook Page Update');
        $feedResponse->assertSee('bxl-facebook-circle', false);
        $feedResponse->assertSee(route('announcement.detail', $facebookAnnouncementId), false);
        $feedResponse->assertDontSee('Hidden Draft Announcement');
        $feedResponse->assertDontSee('Education Office Only');
        $feedResponse->assertDontSee('Expired Announcement');
    }

    public function test_draft_expired_and_archived_announcements_are_hidden_from_alumni(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $draftId = (string) Str::uuid();
        $expiredId = (string) Str::uuid();
        $archivedId = (string) Str::uuid();

        DB::table('announcements')->insert([
            [
                'id' => $draftId,
                'title' => 'Draft Notice',
                'content' => 'This draft should not be visible.',
                'type' => 'Update',
                'priority' => 'Medium',
                'target_audience' => 'Alumni',
                'is_published' => false,
                'expires_at' => null,
                'archived_at' => null,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $expiredId,
                'title' => 'Expired Notice',
                'content' => 'This expired item should not be visible.',
                'type' => 'Alert',
                'priority' => 'Medium',
                'target_audience' => 'Alumni',
                'is_published' => true,
                'expires_at' => now()->subDay(),
                'archived_at' => null,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $archivedId,
                'title' => 'Archived Notice',
                'content' => 'This archived item should not be visible.',
                'type' => 'News',
                'priority' => 'Low',
                'target_audience' => 'Alumni',
                'is_published' => true,
                'expires_at' => null,
                'archived_at' => now(),
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user)->get(route('announcement.detail', $draftId))->assertNotFound();
        $this->actingAs($user)->get(route('announcement.detail', $expiredId))->assertNotFound();
        $this->actingAs($user)->get(route('announcement.detail', $archivedId))->assertNotFound();
    }

    public function test_viewing_an_announcement_marks_the_matching_notification_as_read(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Tracer Schedule',
            'content' => 'Please review the latest schedule for tracer submissions this semester.',
            'type' => 'Update',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new AnnouncementPublishedNotification(
            $announcementId,
            'Tracer Schedule',
            'Please review the latest schedule for tracer submissions this semester.',
            'Update',
            'High'
        ));

        $notificationId = $user->unreadNotifications()->value('id');

        $this->assertNotNull($notificationId);

        $response = $this->actingAs($user)->get(route('announcement.detail', $announcementId));

        $response->assertOk();
        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'notifiable_id' => $user->id,
        ]);
        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }

    public function test_alumni_can_view_the_notifications_page_with_multiple_notification_types(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $announcementId = (string) Str::uuid();
        $jobId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Career Center Bulletin',
            'content' => 'A new bulletin is available for upcoming alumni activities and deadlines.',
            'type' => 'News',
            'priority' => 'Medium',
            'target_audience' => 'Alumni',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Platform Engineer',
            'company' => 'CMU Tech',
            'description' => 'A job posting used to test database-backed notification history.',
            'requirements' => 'Laravel and cloud experience',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new AnnouncementPublishedNotification(
            $announcementId,
            'Career Center Bulletin',
            'A new bulletin is available for upcoming alumni activities and deadlines.',
            'News',
            'Medium'
        ));
        $user->notify(new JobSubmissionApprovedNotification(
            'Platform Engineer',
            'CMU Tech',
            $jobId
        ));

        $response = $this->actingAs($user)->get(route('notifications.index', ['status' => 'unread']));

        $response->assertOk();
        $response->assertSee('Recent activity');
        $response->assertSee('Career Center Bulletin');
        $response->assertSee('Job submission approved');
        $response->assertSee('Unread');
    }

    public function test_alumni_notification_indicator_clears_after_opening_the_notifications_page(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $jobId = (string) Str::uuid();

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Data Analyst',
            'company' => 'CMU Insights',
            'description' => 'A notification-triggering job used for sidebar indicator tests.',
            'requirements' => 'SQL and reporting',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new JobSubmissionApprovedNotification(
            'Data Analyst',
            'CMU Insights',
            $jobId
        ));

        $this->actingAs($user);

        $response = $this
            ->withSession([
                PortalIndicatorState::sessionKeyFor(PortalIndicatorState::ALUMNI_NOTIFICATIONS, $user->id) => now()->subDay()->toIso8601String(),
            ])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">2<\/span>/s',
            $response->getContent()
        );

        $this->get(route('notifications.index'))->assertOk();

        $cleared = $this->get(route('dashboard'));
        $cleared->assertOk();
        $this->assertDoesNotMatchRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $cleared->getContent()
        );

        $this->travel(1)->seconds();

        $user->notify(new JobSubmissionApprovedNotification(
            'Data Analyst',
            'CMU Insights',
            $jobId
        ));

        $renewed = $this->get(route('dashboard'));
        $renewed->assertOk();
        $this->assertMatchesRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">1<\/span>/s',
            $renewed->getContent()
        );
    }

    public function test_alumni_notification_indicator_stays_cleared_after_logout_and_login_without_new_notifications(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $jobId = (string) Str::uuid();

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Records Assistant',
            'company' => 'CMU Alumni Office',
            'description' => 'A notification-triggering job used for login persistence tests.',
            'requirements' => 'Organization and communication',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new JobSubmissionApprovedNotification(
            'Records Assistant',
            'CMU Alumni Office',
            $jobId
        ));

        $this->actingAs($user);

        $beforeOpeningNotifications = $this
            ->withSession([
                PortalIndicatorState::sessionKeyFor(PortalIndicatorState::ALUMNI_NOTIFICATIONS, $user->id) => now()->subDay()->toIso8601String(),
            ])
            ->get(route('dashboard'));

        $beforeOpeningNotifications->assertOk();
        $this->assertMatchesRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">2<\/span>/s',
            $beforeOpeningNotifications->getContent()
        );

        $this->get(route('notifications.index'))->assertOk();
        $this->post(route('logout'))->assertRedirect('/login');

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $afterLogin = $this->get(route('dashboard'));
        $afterLogin->assertOk();
        $this->assertDoesNotMatchRegularExpression(
            '/Notifications<\/span>\s*<\/span>\s*<span class="alumni-sidebar-link__badge">\d+<\/span>/s',
            $afterLogin->getContent()
        );
    }

    public function test_dashboard_surfaces_personal_overview_modules(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'location' => 'Bukidnon',
            'phone' => '09171234567',
            'skills' => 'Laravel, SQL',
        ]);
        $announcementId = (string) Str::uuid();
        $jobId = (string) Str::uuid();

        DB::table('tracer_records')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'current_position' => 'Systems Analyst',
            'current_company' => 'CMU Digital Office',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'industry' => 'Information Technology',
            'job_satisfaction' => 4,
            'skills_used' => 'Reporting, SQL, systems analysis',
            'recommendations' => 'Add more role-specific workshops.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Campus Recruiter',
            'company' => 'Career Link PH',
            'description' => 'A job posting used to verify the notifications content hub.',
            'requirements' => 'Employer engagement and alumni relations',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_applications')->insert([
            'id' => (string) Str::uuid(),
            'job_id' => $jobId,
            'user_id' => $user->id,
            'cover_letter' => 'I would like to apply for this role.',
            'resume_url' => 'https://example.com/resume.pdf',
            'status' => 'Pending',
            'applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Graduation Tracer Reminder',
            'content' => 'Please complete the latest tracer submission before the posted deadline.',
            'type' => 'Update',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new AnnouncementPublishedNotification(
            $announcementId,
            'Graduation Tracer Reminder',
            'Please complete the latest tracer submission before the posted deadline.',
            'Update',
            'High'
        ));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Profile');
        $response->assertSee('Tracer Form');
        $response->assertSee('Job applications');
        $response->assertSee('My Latest Employment Record');
        $response->assertSee('Updates');
        $response->assertSee('Recent notifications');
        $response->assertSee('Graduation Tracer Reminder');
        $response->assertSee('Systems Analyst');
        $response->assertSee('Waiting for review');
        $response->assertSee('1 new update.');
        $response->assertDontSee('Quick Actions');
        $response->assertDontSee('Update Tracer');
        $response->assertDontSee('Open Tracer');
        $response->assertDontSee('View Profile');
        $response->assertDontSee('Review Profile');
        $response->assertDontSee('Privacy & Settings');
        $response->assertDontSee('Send Feedback');
        $response->assertDontSee('Browse the alumni directory and discover connections.');
        $response->assertDontSee('Job Opportunities');
        $response->assertDontSee('General Announcements');
    }

    public function test_targeted_announcements_only_show_for_matching_alumni(): void
    {
        $matchingUser = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Engineering',
            'year_graduated' => 2023,
        ]);
        $nonMatchingUser = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Education',
            'year_graduated' => 2021,
        ]);
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Engineering Batch 2023 Reminder',
            'content' => 'This update is only for engineering alumni from batch 2023.',
            'type' => 'Update',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'target_department' => 'College of Engineering',
            'target_graduation_year' => 2023,
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $matchingFeed = $this->actingAs($matchingUser)->get(route('notifications.index'));
        $matchingFeed->assertOk();
        $matchingFeed->assertSee('Engineering Batch 2023 Reminder');

        $nonMatchingFeed = $this->actingAs($nonMatchingUser)->get(route('notifications.index'));
        $nonMatchingFeed->assertOk();
        $nonMatchingFeed->assertDontSee('Engineering Batch 2023 Reminder');

        $this->actingAs($matchingUser)
            ->get(route('announcement.detail', $announcementId))
            ->assertOk();

        $this->actingAs($nonMatchingUser)
            ->get(route('announcement.detail', $announcementId))
            ->assertNotFound();
    }

    public function test_notifications_page_merges_announcements_and_job_listings(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $announcementId = (string) Str::uuid();
        $jobId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Registrar Notice',
            'content' => 'A new registrar notice is available for alumni review.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'Alumni',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Hidden Jobs Module Listing',
            'company' => 'Career Link PH',
            'description' => 'This job should stay in the jobs module instead of the notifications page.',
            'requirements' => 'Employer engagement and alumni relations',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Recent activity');
        $response->assertSee('Registrar Notice');
        $response->assertSee('Hidden Jobs Module Listing');
        $response->assertDontSee('General Announcements');
    }

    public function test_opening_a_notification_marks_it_as_read_and_redirects_to_the_target_page(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Alumni Event Update',
            'content' => 'The alumni office has published a fresh event update for this month.',
            'type' => 'Event',
            'priority' => 'High',
            'target_audience' => 'Alumni',
            'is_published' => true,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new AnnouncementPublishedNotification(
            $announcementId,
            'Alumni Event Update',
            'The alumni office has published a fresh event update for this month.',
            'Event',
            'High'
        ));

        $notificationId = $user->unreadNotifications()->value('id');

        $response = $this->actingAs($user)->get(route('notifications.open', $notificationId));

        $response->assertRedirect(route('announcement.detail', $announcementId, false));
        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }

    public function test_notifications_page_hides_archived_announcement_notifications(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Archived Facebook Update',
            'content' => 'This Facebook-synced announcement is no longer available.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => false,
            'archived_at' => now(),
            'facebook_post_id' => '233393753185586_122280627044194106',
            'facebook_post_url' => 'https://www.facebook.com/story.php?story_fbid=122280627044194106&id=233393753185586',
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new AnnouncementPublishedNotification(
            $announcementId,
            'Archived Facebook Update',
            'This Facebook-synced announcement is no longer available.',
            'Update',
            'Medium'
        ));

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertDontSee('Archived Facebook Update');
        $response->assertSee('No notifications here yet');
    }

    public function test_opening_archived_announcement_notification_returns_to_notifications(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Deleted Facebook Source',
            'content' => 'The source Facebook post was deleted.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => false,
            'archived_at' => now(),
            'facebook_post_id' => '233393753185586_122280627044194106',
            'facebook_post_url' => 'https://www.facebook.com/story.php?story_fbid=122280627044194106&id=233393753185586',
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new AnnouncementPublishedNotification(
            $announcementId,
            'Deleted Facebook Source',
            'The source Facebook post was deleted.',
            'Update',
            'Medium'
        ));

        $notificationId = $user->unreadNotifications()->value('id');

        $response = $this->actingAs($user)->get(route('notifications.open', $notificationId));

        $response->assertRedirect(route('notifications.index'));
        $response->assertSessionHas('error', 'This announcement is no longer available.');
        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }

    public function test_notification_open_normalizes_old_tunnel_links_to_current_internal_paths(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $announcementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            'id' => $announcementId,
            'title' => 'Current Portal Update',
            'content' => 'This announcement should open even when the stored URL used an old tunnel host.',
            'type' => 'Update',
            'priority' => 'Medium',
            'target_audience' => 'All',
            'is_published' => true,
            'archived_at' => null,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new AnnouncementPublishedNotification(
            $announcementId,
            'Current Portal Update',
            'This announcement should open even when the stored URL used an old tunnel host.',
            'Update',
            'Medium'
        ));

        $notification = $user->unreadNotifications()->firstOrFail();
        $payload = $notification->data;
        $payload['url'] = "https://old-testing-domain.trycloudflare.com/announcements/{$announcementId}";
        DB::table('notifications')
            ->where('id', $notification->id)
            ->update(['data' => json_encode($payload)]);

        $response = $this->actingAs($user)->get(route('notifications.open', $notification->id));

        $response->assertRedirect(route('announcement.detail', $announcementId, false));
    }

    public function test_alumni_can_mark_an_individual_notification_as_read(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $jobId = (string) Str::uuid();

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Data Analyst',
            'company' => 'CMU Insights',
            'description' => 'A job posting used to verify the individual notification read action.',
            'requirements' => 'SQL and dashboards',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new JobSubmissionApprovedNotification(
            'Data Analyst',
            'CMU Insights',
            $jobId
        ));

        $notificationId = $user->unreadNotifications()->value('id');

        $response = $this->actingAs($user)->post(route('notifications.read', $notificationId));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Notification marked as read.');
        $this->assertNotNull(DB::table('notifications')->where('id', $notificationId)->value('read_at'));
    }

    public function test_alumni_can_mark_all_notifications_as_read(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create(['is_admin' => false]);
        $firstAnnouncementId = (string) Str::uuid();
        $secondAnnouncementId = (string) Str::uuid();

        DB::table('announcements')->insert([
            [
                'id' => $firstAnnouncementId,
                'title' => 'Announcement One',
                'content' => 'First published announcement for notification testing.',
                'type' => 'News',
                'priority' => 'Medium',
                'target_audience' => 'Alumni',
                'is_published' => true,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $secondAnnouncementId,
                'title' => 'Announcement Two',
                'content' => 'Second published announcement for notification testing.',
                'type' => 'Alert',
                'priority' => 'High',
                'target_audience' => 'Alumni',
                'is_published' => true,
                'views_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $user->notify(new AnnouncementPublishedNotification(
            $firstAnnouncementId,
            'Announcement One',
            'First published announcement for notification testing.',
            'News',
            'Medium'
        ));
        $user->notify(new AnnouncementPublishedNotification(
            $secondAnnouncementId,
            'Announcement Two',
            'Second published announcement for notification testing.',
            'Alert',
            'High'
        ));

        $response = $this->actingAs($user)->post(route('notifications.read-all'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Notifications marked as read.');
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_alumni_dashboard_loads_successfully(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Profile');
        $response->assertSee('Tracer Form');
        $response->assertSee('Job applications');
        $response->assertSee('Updates');
        $response->assertDontSee('Quick Actions');
        $response->assertDontSee('Update Tracer');
        $response->assertDontSee('View Profile');
        $response->assertDontSee('Review Profile');
        $response->assertDontSee('Browse the alumni directory and discover connections.');
        $response->assertDontSee('Employment Trends');
        $response->assertDontSee('Traced Graduates');
        $response->assertDontSee('Refine Dashboard Reports');
        $response->assertDontSee('Job Opportunities');
        $response->assertDontSee('General Announcements');
    }

    public function test_dashboard_checklist_links_missing_items_to_guided_update_pages(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'phone' => null,
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'employed_after_grad' => null,
            'location' => 'Bukidnon',
            'skills' => null,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(e(route('profile', ['from' => 'overview', 'focus_field' => 'phone']) . '#profile-phone-field'), false);
        $response->assertSee(e(route('profile', ['from' => 'overview', 'focus_field' => 'skills']) . '#profile-skills-field'), false);
        $response->assertSee(e(route('tracer.index', ['from' => 'overview', 'focus_field' => 'employment_summary']) . '#tracer-form'), false);
        $response->assertSee(e(route('tracer.index', ['from' => 'overview', 'focus_field' => 'latest_tracer_record']) . '#tracer-form'), false);
        $response->assertSee('Open Profile');
        $response->assertSee('Open Tracer');
    }

    public function test_alumni_can_save_a_tracer_record_from_the_dedicated_module(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $user = User::factory()->create([
            'is_admin' => false,
            'employed_after_grad' => null,
            'industry' => null,
            'current_position' => null,
            'company' => null,
            'salary_range' => null,
        ]);

        $response = $this->actingAs($user)->post(route('tracer.store'), [
            'employment_type' => 'Full-time',
            'current_position' => 'Systems Analyst',
            'current_company' => 'CMU Digital Office',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'industry' => 'Information Technology',
            'job_satisfaction' => 4,
            'skills_used' => 'Reporting, SQL, systems analysis',
            'curriculum_relevant_to_first_job' => 1,
            'first_job_useful_competencies' => ['Information Technology skills', 'Problem-solving skills'],
            'employed_after_graduation' => 1,
            'time_to_first_job' => '1 to 6 months',
            'first_job_related_to_course' => 'Yes',
            'first_job_finding_method' => 'Information from friends',
            'first_job_monthly_earnings' => 'P10,000.00 to less than P15,000.00',
            'first_job_duration' => '1 year to less than 2 years',
            'presently_employed' => 'Yes',
            'present_employment_status' => 'Regular or Permanent',
            'direct_supervisor_name' => 'Ana Reyes',
            'employer_email' => 'ana.reyes@example.com',
            'is_first_job_after_college' => 0,
            'employment_proof' => $this->fakePdfUpload('company-id.pdf'),
            'job_level' => 'Professional, Technical or Supervisory',
            'present_job_acceptance_reasons' => ['Salaries & Benefits', 'Related to course or program of study'],
            'present_job_monthly_earnings' => 'P20,000.00 to less than P30,000.00',
            'has_employee_awards' => 0,
            'teaching_faculty_enthusiastic' => 4,
            'teaching_concepts_clear' => 4,
            'teaching_faculty_knowledgeable' => 5,
            'teaching_commitment' => 4,
            'degree_program_quality' => 4,
            'has_curriculum_suggestions' => 1,
            'curriculum_suggestions' => 'Add more applied systems work.',
            'has_employability_suggestions' => 0,
            'facilities_satisfaction' => 'Satisfied',
            'has_facility_suggestions' => 1,
            'facility_suggestions' => 'Improve laboratory equipment.',
            'overall_recommend_institution' => 4,
            'overall_objectives_met' => 4,
            'overall_university_experience' => 5,
            'further_education' => 0,
            'education_level' => '',
            'challenges_faced' => 'Finding a role aligned to specialization.',
            'recommendations' => 'Add more tracer follow-up reminders.',
            'contact_permission' => 1,
            'information_attestation' => 1,
            'data_privacy_consent' => 1,
        ]);

        $response->assertRedirect(route('tracer.index'));
        $response->assertSessionHas('success', 'Saved. Your employment proof PDF is pending admin review before your account displays as employed.');

        $this->assertTrue(
            DB::table('tracer_records')
                ->where('user_id', $user->id)
                ->where('employment_type', 'Full-time')
                ->where('current_position', 'Systems Analyst')
                ->where('current_company', 'CMU Digital Office')
                ->where('industry', 'Information Technology')
                ->where('job_satisfaction', 4)
                ->where('curriculum_relevant_to_first_job', true)
                ->where('time_to_first_job', '1 to 6 months')
                ->where('presently_employed', 'Yes')
                ->where('present_employment_status', 'Regular or Permanent')
                ->where('facilities_satisfaction', 'Satisfied')
                ->whereDate('tracer_date', now()->toDateString())
                ->exists()
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'employed_after_grad' => null,
            'current_position' => null,
            'company' => null,
            'industry' => null,
            'salary_range' => null,
            'data_privacy_consent' => true,
        ]);
        $this->assertNotNull($user->fresh()->data_privacy_consented_at);

        $employmentProofRecord = DB::table('tracer_records')
            ->where('user_id', $user->id)
            ->select([
                'id',
                'employment_proof_path',
                'employment_proof_disk',
                'employment_proof_original_name',
                'employment_proof_mime_type',
                'employment_proof_size_bytes',
                'employment_proof_uploaded_at',
                'employment_proof_status',
            ])
            ->first();

        $this->assertNotNull($employmentProofRecord);
        $this->assertNotNull($employmentProofRecord->employment_proof_path);
        $this->assertSame('private', $employmentProofRecord->employment_proof_disk);
        $this->assertSame('company-id.pdf', $employmentProofRecord->employment_proof_original_name);
        $this->assertSame('application/pdf', $employmentProofRecord->employment_proof_mime_type);
        $this->assertNotNull($employmentProofRecord->employment_proof_size_bytes);
        $this->assertNotNull($employmentProofRecord->employment_proof_uploaded_at);
        $this->assertSame('pending', $employmentProofRecord->employment_proof_status);
        Storage::disk('private')->assertExists($employmentProofRecord->employment_proof_path);
        Storage::disk('public')->assertMissing($employmentProofRecord->employment_proof_path);

        $downloadResponse = $this->actingAs($user)->get(route('tracer.proof.download', $employmentProofRecord->id));
        $downloadResponse->assertNotFound();
    }

    public function test_tracer_store_keeps_contact_permission_locked_on(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'data_privacy_consent' => true,
        ]);

        $response = $this->actingAs($user)->post(route('tracer.store'), $this->completeTracerPayload([
            'employment_type' => 'Unemployed',
            'current_position' => null,
            'current_company' => null,
            'monthly_income' => null,
            'industry' => null,
            'employed_after_graduation' => 0,
            'time_to_first_job' => null,
            'first_job_related_to_course' => null,
            'first_job_finding_method' => null,
            'first_job_monthly_earnings' => null,
            'first_job_duration' => null,
            'presently_employed' => 'No',
            'present_employment_status' => null,
            'is_first_job_after_college' => null,
            'job_level' => null,
            'present_job_monthly_earnings' => null,
            'has_employee_awards' => null,
            'further_education' => 0,
            'contact_permission' => 0,
            'information_attestation' => 1,
        ]));

        $response->assertRedirect(route('tracer.index'));

        $this->assertDatabaseHas('tracer_records', [
            'user_id' => $user->id,
            'employment_type' => 'Unemployed',
            'contact_permission' => true,
        ]);
    }

    public function test_admin_added_alumni_must_complete_required_tracer_analytics_questions(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'data_privacy_consent' => true,
            'employed_after_grad' => null,
            'industry' => null,
            'current_position' => null,
            'company' => null,
            'salary_range' => null,
        ]);

        $response = $this->actingAs($user)->post(route('tracer.store'), [
            'employment_type' => 'Full-time',
            'current_position' => 'Junior Analyst',
            'current_company' => 'CMU Digital Services',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'industry' => 'Information Technology',
            'further_education' => 0,
            'contact_permission' => 1,
            'information_attestation' => 1,
        ]);

        $response->assertSessionHasErrors([
            'job_satisfaction',
            'skills_used',
            'curriculum_relevant_to_first_job',
            'employed_after_graduation',
            'presently_employed',
            'facilities_satisfaction',
            'challenges_faced',
            'recommendations',
        ]);

        $this->assertDatabaseMissing('tracer_records', [
            'user_id' => $user->id,
            'current_position' => 'Junior Analyst',
        ]);
    }

    public function test_alumni_proof_download_route_blocks_other_alumni_but_links_appear_for_the_owner(): void
    {
        Storage::fake('private');

        $owner = User::factory()->create(['is_admin' => false]);
        $otherAlumni = User::factory()->create(['is_admin' => false]);
        $proofPath = 'tracer-employment-proofs/protected-proof.png';

        Storage::disk('private')->put($proofPath, 'fake-image-content');

        $recordId = (string) Str::uuid();

        DB::table('tracer_records')->insert([
            'id' => $recordId,
            'user_id' => $owner->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'current_position' => 'Developer',
            'current_company' => 'CMU Tech',
            'presently_employed' => 'Yes',
            'employment_proof_path' => $proofPath,
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'protected-proof.png',
            'employment_proof_mime_type' => 'image/png',
            'employment_proof_size_bytes' => strlen('fake-image-content'),
            'employment_proof_uploaded_at' => now(),
            'employment_proof_status' => 'verified',
            'employment_proof_reviewed_by_guard' => 'admin',
            'employment_proof_reviewed_by_id' => (string) Str::uuid(),
            'employment_proof_reviewed_at' => now(),
            'contact_permission' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tracerPage = $this->actingAs($owner)->get(route('tracer.index'));
        $tracerPage->assertOk();
        $tracerPage->assertSee(route('tracer.proof.download', $recordId), false);
        $tracerPage->assertDontSee('/storage/' . $proofPath, false);

        $profilePage = $this->actingAs($owner)->get(route('profile'));
        $profilePage->assertOk();
        $profilePage->assertSee(route('tracer.proof.download', $recordId), false);
        $profilePage->assertDontSee('/storage/' . $proofPath, false);

        $allowedResponse = $this->actingAs($owner)->get(route('tracer.proof.download', $recordId));
        $allowedResponse->assertOk();
        $allowedResponse->assertHeader('content-type', 'image/png');

        $blockedResponse = $this->actingAs($otherAlumni)->get(route('tracer.proof.download', $recordId));
        $blockedResponse->assertNotFound();
    }

    public function test_tracer_page_displays_saved_history_entries(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        DB::table('tracer_records')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'tracer_date' => now()->subMonths(1)->toDateString(),
                'is_employed' => true,
                'employment_type' => 'Part-time',
                'current_position' => 'Research Assistant',
                'current_company' => 'CMU Office',
                'monthly_income' => 'P10,000.00 to less than P20,000.00',
                'industry' => 'Education',
                'job_satisfaction' => 3,
                'further_education' => false,
                'education_level' => null,
                'contact_permission' => true,
                'created_at' => now()->subMonths(1),
                'updated_at' => now()->subMonths(1),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'tracer_date' => now()->toDateString(),
                'is_employed' => true,
                'employment_type' => 'Self-Employed',
                'current_position' => 'Business Owner',
                'current_company' => 'Green Harvest Trading',
                'monthly_income' => 'P40,000.00 to less than P50,000.00',
                'industry' => 'Agriculture',
                'job_satisfaction' => 5,
                'further_education' => true,
                'education_level' => "Master's Degree",
                'contact_permission' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($user)->get(route('tracer.index'));

        $response->assertOk();
        $response->assertSee('Tracer Update Form');
        $response->assertSee('Tracer History');
        $response->assertSee('Green Harvest Trading');
        $response->assertSee('Research Assistant');
        $response->assertSee('Reset Changes');
        $response->assertSee('I confirm this information is true and accurate.');
        $response->assertSee('New Company');
        $response->assertSee('New Position');
        $response->assertSee('Status Changed');
        $response->assertSee('Baseline Record');
        $response->assertDontSee('Edit Latest');
        $response->assertDontSee('Add New');
        $response->assertSee('Edit Entry');
    }

    public function test_tracer_history_only_shows_entries_when_summary_fields_change(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        DB::table('tracer_records')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'tracer_date' => '2026-04-22',
                'is_employed' => false,
                'employment_type' => 'Unemployed',
                'current_position' => null,
                'current_company' => null,
                'monthly_income' => null,
                'industry' => null,
                'job_satisfaction' => 2,
                'skills_used' => 'Initial skills',
                'further_education' => false,
                'education_level' => null,
                'contact_permission' => true,
                'created_at' => '2026-04-22 09:00:00',
                'updated_at' => '2026-04-22 09:00:00',
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'tracer_date' => '2026-04-23',
                'is_employed' => false,
                'employment_type' => 'Unemployed',
                'current_position' => null,
                'current_company' => null,
                'monthly_income' => null,
                'industry' => null,
                'job_satisfaction' => 5,
                'skills_used' => 'Updated skills only',
                'further_education' => false,
                'education_level' => null,
                'contact_permission' => true,
                'created_at' => '2026-04-23 09:00:00',
                'updated_at' => '2026-04-23 09:00:00',
            ],
        ]);

        $response = $this->actingAs($user)->get(route('tracer.index'));

        $response->assertOk();
        $response->assertSee('1 entries');
        $response->assertSee('Apr 22, 2026');
        $response->assertDontSee('Apr 23, 2026');
        $response->assertSee('This is the earliest tracer entry on file.');
        $this->assertSame(1, substr_count($response->getContent(), 'Edit Entry'));
    }

    public function test_tracer_page_shows_scholarship_details_saved_on_the_account(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'has_scholarship' => true,
            'scholarship_name' => 'ACEF - GIAHEP',
            'scholarship_year_awarded' => 2021,
            'scholarship_helped_get_job' => false,
        ]);

        $response = $this->actingAs($user)->get(route('tracer.index'));

        $response->assertOk();
        $response->assertSee('Scholarship Record');
        $response->assertSee('Saved on your alumni account. If you missed this during registration, you can update it here.');
        $response->assertSee('Edit Scholarship Record');
        $response->assertSee('I confirm this information is true and accurate.');
        $response->assertSee('ACEF - GIAHEP');
        $response->assertSee('2021');
        $response->assertSee('Helped You Get a Job After Graduation');
        $response->assertSee('No');
    }

    public function test_alumni_can_add_a_missing_scholarship_record_from_tracer_page(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'has_scholarship' => false,
            'scholarship_name' => null,
            'scholarship_year_awarded' => null,
            'scholarship_helped_get_job' => null,
        ]);

        $response = $this->actingAs($user)->post(route('tracer.scholarship.update'), [
            '_scholarship_form' => 1,
            'has_scholarship' => 1,
            'scholarship_name' => 'ACEF - GIAHEP',
            'scholarship_year_awarded' => 2021,
            'scholarship_helped_get_job' => 0,
            'scholarship_attestation' => 1,
        ]);

        $response->assertRedirect(route('tracer.index') . '#scholarship-record');
        $response->assertSessionHas('success', 'Scholarship record updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'has_scholarship' => true,
            'scholarship_name' => 'ACEF - GIAHEP',
            'scholarship_year_awarded' => 2021,
            'scholarship_helped_get_job' => false,
        ]);
    }

    public function test_alumni_can_clear_a_scholarship_record_from_tracer_page(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'has_scholarship' => true,
            'scholarship_name' => 'CHED Full Merit',
            'scholarship_year_awarded' => 2020,
            'scholarship_helped_get_job' => true,
        ]);

        $response = $this->actingAs($user)->post(route('tracer.scholarship.update'), [
            '_scholarship_form' => 1,
            'has_scholarship' => 0,
            'scholarship_attestation' => 1,
        ]);

        $response->assertRedirect(route('tracer.index') . '#scholarship-record');
        $response->assertSessionHas('success', 'Scholarship record updated successfully.');

        $user->refresh();

        $this->assertFalse((bool) $user->has_scholarship);
        $this->assertNull($user->scholarship_name);
        $this->assertNull($user->scholarship_year_awarded);
        $this->assertNull($user->scholarship_helped_get_job);
    }

    public function test_tracer_and_profile_show_registration_note_for_blank_answers(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'skills' => null,
            'achievements' => null,
        ]);

        DB::table('tracer_records')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => false,
            'employment_type' => 'Unemployed',
            'current_position' => null,
            'current_company' => null,
            'monthly_income' => null,
            'industry' => null,
            'job_satisfaction' => null,
            'further_education' => false,
            'education_level' => null,
            'skills_used' => null,
            'challenges_faced' => null,
            'recommendations' => null,
            'contact_permission' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tracerResponse = $this->actingAs($user)->get(route('tracer.index'));

        $tracerResponse->assertOk();
        $tracerResponse->assertSee('Complete the required alumni analytics questions.');
        $tracerResponse->assertSee('data-tracer-jump-target="job_satisfaction"', false);
        $tracerResponse->assertSee('data-tracer-jump-target="skills_used"', false);
        $tracerResponse->assertSeeInOrder([
            'data-tracer-analytics-target="job_satisfaction"',
            'data-tracer-analytics-missing="true"',
        ], false);
        $tracerResponse->assertSee('Some registration questions are still blank on your account.');
        $tracerResponse->assertSee('You did not answer this in the registration. You can update it now.');
        $tracerResponse->assertSee('You did not answer this in the registration. Set Employment Type to Employed or Self-Employed if you want to update this field.');
        $tracerResponse->assertSee('You did not answer this in the registration. Check &quot;I am currently pursuing further education or training.&quot; if you want to update this field.', false);

        $profileResponse = $this->actingAs($user)->get(route('profile'));

        $profileResponse->assertOk();
        $profileResponse->assertSee('You did not answer this in the registration. You can update it now.');
    }

    public function test_tracer_warning_flags_legacy_industry_values_not_in_dropdown(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        DB::table('tracer_records')->insert($this->completeTracerRecord([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'industry' => 'Legacy Technology',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $response = $this->actingAs($user)->get(route('tracer.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'data-tracer-analytics-target="industry"',
            'data-tracer-analytics-missing="true"',
        ], false);
    }

    public function test_alumni_can_edit_an_existing_tracer_entry(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $recordId = (string) Str::uuid();

        DB::table('tracer_records')->insert([
            'id' => $recordId,
            'user_id' => $user->id,
            'tracer_date' => now()->subMonth()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'current_position' => 'Junior Software Developer',
            'current_company' => 'Bukidnon Tech Solutions',
            'monthly_income' => 'P20,000.00 to less than P30,000.00',
            'industry' => 'Technology',
            'job_satisfaction' => 4,
            'further_education' => false,
            'education_level' => null,
            'contact_permission' => true,
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ]);

        $editPage = $this->actingAs($user)->get(route('tracer.index', ['edit' => $recordId]));

        $editPage->assertOk();
        $editPage->assertSee('Save as New History Entry');
        $editPage->assertSee('tracer-form-shell--editing', false);
        $editPage->assertSee('data-editing="true"', false);

        $response = $this->actingAs($user)->post(route('tracer.store'), $this->completeTracerPayload([
            'record_id' => $recordId,
            'employment_type' => 'Full-time',
            'current_position' => 'Software Engineer',
            'current_company' => 'Bukidnon Tech Solutions',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'industry' => 'Technology',
            'job_satisfaction' => 5,
            'skills_used' => 'Laravel, SQL, API development',
            'further_education' => 0,
            'education_level' => '',
            'challenges_faced' => 'Balancing multiple releases.',
            'recommendations' => 'Add more backend workshops.',
            'contact_permission' => 1,
            'information_attestation' => 1,
            'data_privacy_consent' => 1,
        ]));

        $response->assertRedirect(route('tracer.index'));
        $response->assertSessionHas('success', 'Saved. This is now your latest tracer record.');
        $this->assertDatabaseHas('tracer_records', [
            'id' => $recordId,
            'current_position' => 'Junior Software Developer',
            'monthly_income' => 'P20,000.00 to less than P30,000.00',
            'job_satisfaction' => 4,
        ]);
        $this->assertTrue(
            DB::table('tracer_records')
                ->where('current_position', 'Software Engineer')
                ->where('current_company', 'Bukidnon Tech Solutions')
                ->where('monthly_income', 'P30,000.00 to less than P40,000.00')
                ->where('job_satisfaction', 5)
                ->whereDate('tracer_date', now()->toDateString())
                ->exists()
        );
        $this->assertSame(2, DB::table('tracer_records')->where('user_id', $user->id)->count());
    }

    public function test_alumni_cannot_save_edit_without_any_changes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $recordId = (string) Str::uuid();
        $tracerDate = now()->subMonth()->toDateString();

        DB::table('tracer_records')->insert($this->completeTracerRecord([
            'id' => $recordId,
            'user_id' => $user->id,
            'tracer_date' => $tracerDate,
            'employment_type' => 'Full-time',
            'current_position' => 'Junior Software Developer',
            'current_company' => 'Bukidnon Tech Solutions',
            'monthly_income' => 'P20,000.00 to less than P30,000.00',
            'industry' => 'Technology',
            'job_satisfaction' => 4,
            'skills_used' => 'Laravel, SQL, debugging',
            'challenges_faced' => 'Balancing deadlines.',
            'recommendations' => 'More backend workshops.',
            'presently_employed' => 'Yes',
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ]));

        $response = $this->actingAs($user)->post(route('tracer.store'), $this->completeTracerPayload([
            'record_id' => $recordId,
            'employment_type' => 'Full-time',
            'current_position' => 'Junior Software Developer',
            'current_company' => 'Bukidnon Tech Solutions',
            'monthly_income' => 'P20,000.00 to less than P30,000.00',
            'industry' => 'Technology',
            'job_satisfaction' => 4,
            'skills_used' => 'Laravel, SQL, debugging',
            'further_education' => 0,
            'education_level' => '',
            'challenges_faced' => 'Balancing deadlines.',
            'recommendations' => 'More backend workshops.',
            'contact_permission' => 1,
            'information_attestation' => 1,
            'data_privacy_consent' => 1,
        ]));

        $response->assertRedirect(route('tracer.index', ['edit' => $recordId]));
        $response->assertSessionHas('warning', 'You did not change anything yet.');
        $response->assertSessionHas('tracer_skip_autoscroll', true);
        $this->assertSame(1, DB::table('tracer_records')->where('user_id', $user->id)->count());
    }

    public function test_alumni_ajax_save_returns_a_warning_without_refresh_when_nothing_changed(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        DB::table('tracer_records')->insert($this->completeTracerRecord([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tracer_date' => now()->subDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $response = $this
            ->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('tracer.store'), $this->completeTracerPayload());

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You did not change anything yet.',
        ]);
        $this->assertSame(1, DB::table('tracer_records')->where('user_id', $user->id)->count());
    }

    public function test_alumni_cannot_save_latest_tracer_without_any_changes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        DB::table('tracer_records')->insert($this->completeTracerRecord([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tracer_date' => now()->subDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $response = $this->actingAs($user)->post(route('tracer.store'), $this->completeTracerPayload());

        $response->assertRedirect(route('tracer.index'));
        $response->assertSessionHas('warning', 'You did not change anything yet.');
        $this->assertSame(1, DB::table('tracer_records')->where('user_id', $user->id)->count());
    }

    public function test_minor_tracer_updates_do_not_change_visible_history(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $baselineDate = now()->subDay();

        DB::table('tracer_records')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tracer_date' => $baselineDate->toDateString(),
            'is_employed' => false,
            'employment_type' => 'Unemployed',
            'current_position' => null,
            'current_company' => null,
            'monthly_income' => null,
            'industry' => null,
            'job_satisfaction' => 2,
            'skills_used' => 'Initial skills',
            'further_education' => false,
            'education_level' => null,
            'challenges_faced' => 'Initial challenge',
            'recommendations' => 'Initial recommendation',
            'presently_employed' => 'No',
            'contact_permission' => true,
            'created_at' => $baselineDate->copy()->setTime(9, 0),
            'updated_at' => $baselineDate->copy()->setTime(9, 0),
        ]);

        $saveResponse = $this->actingAs($user)->post(route('tracer.store'), $this->completeTracerPayload([
            'employment_type' => 'Unemployed',
            'presently_employed' => 'No',
            'job_satisfaction' => 5,
            'skills_used' => 'Updated skills only',
            'further_education' => 0,
            'education_level' => '',
            'challenges_faced' => 'Updated challenge only',
            'recommendations' => 'Updated recommendation only',
            'contact_permission' => 1,
            'information_attestation' => 1,
            'data_privacy_consent' => 1,
        ]));

        $saveResponse->assertRedirect(route('tracer.index'));
        $saveResponse->assertSessionHas('success', 'Saved. This is now your latest tracer record.');
        $this->assertSame(2, DB::table('tracer_records')->where('user_id', $user->id)->count());

        $historyResponse = $this->actingAs($user)->get(route('tracer.index'));

        $historyResponse->assertOk();
        $historyResponse->assertSee('1 entries');
        $historyResponse->assertSee($baselineDate->format('M j, Y'));
        $this->assertSame(1, substr_count($historyResponse->getContent(), 'Edit Entry'));
    }

    public function test_alumni_ajax_save_returns_updated_tracer_page_without_refresh(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'employed_after_grad' => null,
            'industry' => null,
            'current_position' => null,
            'company' => null,
            'salary_range' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('tracer.store'), $this->completeTracerPayload());

        $response->assertOk();
        $response->assertJson([
            'message' => 'Saved. This is now your latest tracer record.',
        ]);
        $this->assertIsString($response->json('page'));
        $this->assertStringContainsString('data-tracer-page-root', $response->json('page'));
        $this->assertStringContainsString('Tracer History', $response->json('page'));
        $this->assertDatabaseHas('tracer_records', [
            'user_id' => $user->id,
            'employment_type' => 'Full-time',
            'current_position' => 'Systems Analyst',
        ]);
    }

    public function test_tracer_form_keeps_the_most_recently_saved_record_after_save(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'data_privacy_consent' => true,
            'employed_after_grad' => null,
            'industry' => null,
            'current_position' => null,
            'company' => null,
            'salary_range' => null,
        ]);

        DB::table('tracer_records')->insert($this->completeTracerRecord([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tracer_date' => now()->addDay()->toDateString(),
            'current_position' => 'Old Incomplete Analyst',
            'current_company' => 'Old Incomplete Company',
            'industry' => null,
            'job_satisfaction' => null,
            'skills_used' => null,
            'curriculum_relevant_to_first_job' => null,
            'first_job_useful_competencies' => null,
            'employed_after_graduation' => null,
            'presently_employed' => null,
            'job_level' => null,
            'present_job_monthly_earnings' => null,
            'has_employee_awards' => null,
            'facilities_satisfaction' => null,
            'challenges_faced' => null,
            'recommendations' => null,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]));

        DB::table('tracer_records')->insert($this->completeTracerRecord([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tracer_date' => now()->toDateString(),
            'current_position' => 'Saved Systems Analyst',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]));

        $page = $this->actingAs($user)->get(route('tracer.index'));

        $page->assertOk();
        $this->assertStringContainsString('value="Saved Systems Analyst"', $page->getContent());
        $this->assertMatchesRegularExpression(
            '/<select name="job_satisfaction"[^>]*>.*?<option value="4" selected>/s',
            $page->getContent()
        );

        $response = $this
            ->actingAs($user)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('tracer.store'), $this->completeTracerPayload([
                'current_position' => 'Freshly Saved Analyst',
            ]));

        $response->assertOk();
        $response->assertJson([
            'message' => 'Saved. This is now your latest tracer record.',
        ]);
        $this->assertStringContainsString('value="Freshly Saved Analyst"', $response->json('page'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_position' => 'Freshly Saved Analyst',
            'company' => 'CMU Digital Office',
            'industry' => 'Information Technology',
        ]);
        $this->assertDatabaseHas('system_logs', [
            'user_id' => $user->id,
            'action' => 'alumni.tracer.update',
        ]);
    }

    public function test_tracer_form_remembers_saved_no_answers(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'data_privacy_consent' => true,
        ]);

        DB::table('tracer_records')->insert($this->completeTracerRecord([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'curriculum_relevant_to_first_job' => false,
            'first_job_useful_competencies' => [],
            'curriculum_not_relevant_reason' => 'The curriculum was too general',
            'employed_after_graduation' => false,
            'time_to_first_job' => null,
            'first_job_related_to_course' => null,
            'first_job_finding_method' => null,
            'first_job_monthly_earnings' => null,
            'first_job_duration' => null,
            'is_first_job_after_college' => false,
            'has_employee_awards' => false,
            'has_curriculum_suggestions' => false,
            'curriculum_suggestions' => null,
            'has_employability_suggestions' => false,
            'employability_suggestions' => null,
            'has_facility_suggestions' => false,
            'facility_suggestions' => null,
        ]));

        $response = $this->actingAs($user)->get(route('tracer.index'));

        $response->assertOk();
        foreach ([
            'curriculum_relevant_to_first_job',
            'employed_after_graduation',
            'is_first_job_after_college',
            'has_employee_awards',
            'has_curriculum_suggestions',
            'has_employability_suggestions',
            'has_facility_suggestions',
        ] as $field) {
            $this->assertMatchesRegularExpression(
                '/name="' . preg_quote($field, '/') . '" value="0" checked/',
                $response->getContent()
            );
        }
    }

    public function test_tracer_history_is_limited_to_ten_entries(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'data_privacy_consent' => true,
        ]);

        for ($index = 0; $index < 12; $index++) {
            DB::table('tracer_records')->insert($this->completeTracerRecord([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'tracer_date' => now()->subDays($index)->toDateString(),
                'current_position' => 'History Position ' . $index,
                'industry' => $index % 2 === 0 ? 'Information Technology' : 'Education',
                'created_at' => now()->subDays($index),
                'updated_at' => now()->subDays($index),
            ]));
        }

        $response = $this->actingAs($user)->get(route('tracer.index'));

        $response->assertOk();
        $response->assertSee('10 of 12 entries');
        $this->assertSame(10, substr_count($response->getContent(), 'Edit Entry'));
        $response->assertSee(now()->format('M j, Y'));
        $response->assertDontSee(now()->subDays(11)->format('M j, Y'));
    }

    public function test_profile_page_uses_real_account_fields_and_latest_tracer_data(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Mira',
            'lastname' => 'Santos',
            'email' => 'mira.santos@example.com',
            'phone' => '09171234567',
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'location' => 'Bukidnon',
            'salary_range' => '35,000.00 - 49,999.00',
            'company' => 'CloudWorks PH',
            'skills' => 'Laravel, PostgreSQL, reporting',
            'achievements' => 'Dean\'s Lister',
            'profile_visibility' => 'public_basic',
        ]);

        DB::table('tracer_records')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'current_position' => 'Data Engineer',
            'current_company' => 'CloudWorks PH',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'industry' => 'Information Technology',
            'job_satisfaction' => 4,
            'curriculum_relevant_to_first_job' => true,
            'first_job_useful_competencies' => json_encode(['Information Technology skills', 'Problem-solving skills']),
            'time_to_first_job' => '1 to 6 months',
            'presently_employed' => 'Yes',
            'present_employment_status' => 'Regular or Permanent',
            'facilities_satisfaction' => 'Satisfied',
            'curriculum_suggestions' => 'Add more cloud computing projects',
            'skills_used' => 'ETL, SQL, dashboarding',
            'challenges_faced' => 'Balancing deadlines',
            'recommendations' => 'Offer more cloud workshops',
            'employment_proof_path' => 'tracer-employment-proofs/cloudworks-proof.png',
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'cloudworks-proof.png',
            'employment_proof_mime_type' => 'image/png',
            'employment_proof_size_bytes' => 2048,
            'employment_proof_uploaded_at' => now(),
            'employment_proof_status' => 'verified',
            'employment_proof_reviewed_by_guard' => 'admin',
            'employment_proof_reviewed_by_id' => (string) Str::uuid(),
            'employment_proof_reviewed_at' => now(),
            'further_education' => false,
            'education_level' => null,
            'contact_permission' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertOk();
        $response->assertDontSee('Profile & Employment Snapshot', false);
        $response->assertSee('Mira Santos');
        $response->assertSee('College of Engineering');
        $response->assertSee('Data Engineer');
        $response->assertSee('CloudWorks PH');
        $response->assertSee('Regular or Permanent');
        $response->assertSee('1 to 6 months');
        $response->assertSee('Satisfied');
        $response->assertSee('Information Technology skills');
        $response->assertSee('Laravel, PostgreSQL, reporting');
        $response->assertSee('cloudworks-proof.png');
        $response->assertSee(route('tracer.proof.download', DB::table('tracer_records')->where('user_id', $user->id)->value('id')), false);
        $response->assertDontSeeText('Public Basic');
        $response->assertDontSeeText('Visibility');
        $response->assertDontSeeText('Newsletter');
        $response->assertSee('Update Profile Details');
        $response->assertSee('Save Information');
        $response->assertSee('I confirm this information is true and accurate.');
        $response->assertDontSee('Student ID');
        $response->assertDontSee('Professional Exam Passed');
    }

    public function test_profile_page_highlights_the_requested_field_when_opened_from_overview(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'phone' => null,
        ]);

        $response = $this->actingAs($user)->get(route('profile', [
            'from' => 'overview',
            'focus_field' => 'phone',
        ]));

        $response->assertOk();
        $response->assertDontSeeText('You are now in Profile');
        $response->assertDontSeeText('Update Phone number in the highlighted field below.');
        $response->assertDontSeeText('Return to Overview');
        $response->assertSee('data-profile-field="phone"', false);
        $response->assertSee('data-guided-active="true"', false);
    }

    public function test_profile_page_highlights_missing_completion_fields(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'phone' => null,
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'location' => 'Bukidnon',
            'skills' => null,
            'employed_after_grad' => 'Yes',
        ]);

        DB::table('tracer_records')->insert($this->completeTracerRecord([
            'user_id' => $user->id,
        ]));

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertOk();
        $response->assertSee('75%');
        $response->assertSee('Missing profile fields are highlighted below.');
        $response->assertSee('id="profile-phone-field"', false);
        $response->assertSee('id="profile-skills-field"', false);
        $response->assertSee('data-profile-completion-missing="true"', false);
        $response->assertSee('Phone number is missing for profile completeness.');
        $response->assertSee('Skills are missing for profile completeness.');
        $response->assertSee('Show field');
        $response->assertSee('!border-amber-400', false);
    }

    public function test_profile_page_highlights_missing_tracer_completion_source(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'phone' => '09171234567',
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'location' => 'Bukidnon',
            'skills' => 'Laravel, reporting, SQL',
            'employed_after_grad' => 'Yes',
        ]);

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertOk();
        $response->assertSee('88%');
        $response->assertDontSee('Missing profile fields are highlighted below.');
        $response->assertSee('data-profile-tracer-completion-missing="true"', false);
        $response->assertSee('Tracer information is still affecting your profile completeness.');
        $response->assertSee('Open Tracer');
        $response->assertSee(e(route('tracer.index', ['from' => 'overview', 'focus_field' => 'latest_tracer_record']) . '#tracer-form'), false);
        $response->assertSee('!border-amber-300', false);
    }

    public function test_tracer_page_shows_guided_message_when_opened_from_overview(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->get(route('tracer.index', [
            'from' => 'overview',
            'focus_field' => 'latest_tracer_record',
        ]));

        $response->assertOk();
        $response->assertSeeText('You are now in Tracer');
        $response->assertSeeText('Add your first tracer record in the highlighted form below.');
        $response->assertSeeText('Return to Overview');
        $response->assertSee('data-guided-active="true"', false);
    }

    protected function completeTracerPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'employment_type' => 'Full-time',
            'current_position' => 'Systems Analyst',
            'current_company' => 'CMU Digital Office',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'industry' => 'Information Technology',
            'job_satisfaction' => 4,
            'skills_used' => 'Reporting, SQL, systems analysis',
            'curriculum_relevant_to_first_job' => 1,
            'first_job_useful_competencies' => ['Information Technology skills', 'Problem-solving skills'],
            'employed_after_graduation' => 1,
            'time_to_first_job' => '1 to 6 months',
            'first_job_related_to_course' => 'Yes',
            'first_job_finding_method' => 'Information from friends',
            'first_job_monthly_earnings' => 'P10,000.00 to less than P15,000.00',
            'first_job_duration' => '1 year to less than 2 years',
            'presently_employed' => 'Yes',
            'present_employment_status' => 'Regular or Permanent',
            'direct_supervisor_name' => 'Ana Reyes',
            'employer_email' => 'ana.reyes@example.com',
            'is_first_job_after_college' => 0,
            'job_level' => 'Professional, Technical or Supervisory',
            'present_job_acceptance_reasons' => ['Salaries & Benefits', 'Related to course or program of study'],
            'present_job_monthly_earnings' => 'P20,000.00 to less than P30,000.00',
            'has_employee_awards' => 0,
            'teaching_faculty_enthusiastic' => 4,
            'teaching_concepts_clear' => 4,
            'teaching_faculty_knowledgeable' => 5,
            'teaching_commitment' => 4,
            'degree_program_quality' => 4,
            'has_curriculum_suggestions' => 1,
            'curriculum_suggestions' => 'Add more applied systems work.',
            'has_employability_suggestions' => 1,
            'employability_suggestions' => 'Add more internship partnerships.',
            'facilities_satisfaction' => 'Satisfied',
            'has_facility_suggestions' => 1,
            'facility_suggestions' => 'Improve laboratory equipment.',
            'overall_recommend_institution' => 4,
            'overall_objectives_met' => 4,
            'overall_university_experience' => 5,
            'further_education' => 0,
            'education_level' => '',
            'challenges_faced' => 'Finding a role aligned to specialization.',
            'recommendations' => 'Add more tracer follow-up reminders.',
            'contact_permission' => 1,
            'information_attestation' => 1,
            'data_privacy_consent' => 1,
        ], $overrides);
    }

    protected function fakePdfUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 64, 'application/pdf');
    }

    protected function completeTracerRecord(array $overrides = []): array
    {
        $record = array_replace([
            'id' => (string) Str::uuid(),
            'tracer_date' => now()->toDateString(),
            'is_employed' => true,
            'employment_type' => 'Full-time',
            'current_position' => 'Systems Analyst',
            'current_company' => 'CMU Digital Office',
            'monthly_income' => 'P30,000.00 to less than P40,000.00',
            'industry' => 'Information Technology',
            'job_satisfaction' => 4,
            'skills_used' => 'Reporting, SQL, systems analysis',
            'curriculum_relevant_to_first_job' => true,
            'first_job_useful_competencies' => ['Information Technology skills', 'Problem-solving skills'],
            'first_job_useful_competencies_other' => null,
            'curriculum_not_relevant_reason' => null,
            'curriculum_not_relevant_reason_other' => null,
            'employed_after_graduation' => true,
            'time_to_first_job' => '1 to 6 months',
            'time_to_first_job_other' => null,
            'first_job_related_to_course' => 'Yes',
            'first_job_finding_method' => 'Information from friends',
            'first_job_finding_method_other' => null,
            'first_job_monthly_earnings' => 'P10,000.00 to less than P15,000.00',
            'first_job_duration' => '1 year to less than 2 years',
            'first_job_duration_other' => null,
            'presently_employed' => 'Yes',
            'present_employment_status' => 'Regular or Permanent',
            'direct_supervisor_name' => 'Ana Reyes',
            'employer_email' => 'ana.reyes@example.com',
            'is_first_job_after_college' => false,
            'employment_proof_path' => null,
            'employment_proof_disk' => null,
            'employment_proof_original_name' => null,
            'employment_proof_mime_type' => null,
            'employment_proof_size_bytes' => null,
            'employment_proof_uploaded_at' => null,
            'job_level' => 'Professional, Technical or Supervisory',
            'job_level_other' => null,
            'present_job_acceptance_reasons' => ['Salaries & Benefits', 'Related to course or program of study'],
            'present_job_acceptance_other' => null,
            'present_job_monthly_earnings' => 'P20,000.00 to less than P30,000.00',
            'has_employee_awards' => false,
            'employee_awards' => null,
            'teaching_faculty_enthusiastic' => 4,
            'teaching_concepts_clear' => 4,
            'teaching_faculty_knowledgeable' => 5,
            'teaching_commitment' => 4,
            'degree_program_quality' => 4,
            'has_curriculum_suggestions' => true,
            'curriculum_suggestions' => 'Add more applied systems work.',
            'has_employability_suggestions' => true,
            'employability_suggestions' => 'Add more internship partnerships.',
            'facilities_satisfaction' => 'Satisfied',
            'has_facility_suggestions' => true,
            'facility_suggestions' => 'Improve laboratory equipment.',
            'overall_recommend_institution' => 4,
            'overall_objectives_met' => 4,
            'overall_university_experience' => 5,
            'further_education' => false,
            'education_level' => null,
            'challenges_faced' => 'Finding a role aligned to specialization.',
            'recommendations' => 'Add more tracer follow-up reminders.',
            'contact_permission' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        foreach (['first_job_useful_competencies', 'present_job_acceptance_reasons'] as $jsonColumn) {
            if (is_array($record[$jsonColumn] ?? null)) {
                $record[$jsonColumn] = json_encode($record[$jsonColumn]);
            }
        }

        return $record;
    }
}
