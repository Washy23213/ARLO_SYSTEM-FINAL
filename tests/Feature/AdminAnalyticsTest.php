<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Services\AdminAnalyticsService;
use App\Support\SystemOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_analytics_page_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSeeText('Analytics & Reports', false);
        $response->assertSeeText('Curriculum Relevance to First Job', false);
        $response->assertSeeText('Time to First Job', false);
        $response->assertSeeText('Present Employment Status', false);
        $response->assertSeeText('Teaching Feedback', false);
        $response->assertSeeText('University Facilities', false);
        $response->assertSeeText('Overall Experience', false);
        $response->assertDontSeeText('Sign-Up Satisfaction Overview', false);
        $response->assertSeeText('Feedback Categories', false);
        $response->assertSeeText('registered accounts', false);
        $response->assertSeeText('CSV waiting for signup', false);
        $response->assertSeeText('employed alumni only', false);
        $response->assertSeeText('data completeness', false);
        $response->assertSeeText('How Many Have Registered?', false);
        $response->assertSeeText('How Many Have Not Yet Registered?', false);
        $response->assertSeeText('How Many Are Employed?', false);
        $response->assertSeeText('Data Completeness', false);
        $response->assertSeeText('CSV records waiting for signup', false);
        $response->assertSeeText('employed only', false);
        $response->assertSeeText('profile and employment fields completed', false);
        $response->assertDontSeeText('No graphs selected yet', false);
    }

    public function test_admin_analytics_defaults_to_common_alumni_graphs(): void
    {
        $payload = app(AdminAnalyticsService::class)->analyticsPayload([], true);

        $this->assertSame([
            'employmentTrendsChart',
            'tracedGraduatesChart',
            'industryChart',
            'employabilityChart',
            'globalDistributionChart',
            'reasonsChart',
            'collegeDistributionChart',
            'employmentStatusChart',
            'graduationYearChart',
        ], $payload['filters']['charts']);
        $this->assertFalse($payload['ui']['hasCustomChartSelection']);
    }

    public function test_analytics_degree_filter_includes_masteral_and_doctorate_programs(): void
    {
        $payload = app(AdminAnalyticsService::class)->analyticsPayload([], true);
        $degreeOptions = $payload['filterOptions']['degrees'];

        $graduatePrograms = collect(SystemOptions::programCatalogByDepartment())
            ->flatMap(fn (array $groups) => array_merge($groups['master'] ?? [], $groups['doctorate'] ?? []))
            ->unique()
            ->values();

        $this->assertNotEmpty($graduatePrograms);

        foreach ($graduatePrograms as $program) {
            $this->assertContains($program, $degreeOptions);
        }
    }

    public function test_registration_dropdown_source_includes_masteral_and_doctorate_programs(): void
    {
        $registrationScript = file_get_contents(resource_path('js/registration.js'));

        $graduatePrograms = collect(SystemOptions::programCatalogByDepartment())
            ->flatMap(fn (array $groups) => array_merge($groups['master'] ?? [], $groups['doctorate'] ?? []))
            ->unique()
            ->values();

        $this->assertIsString($registrationScript);
        $this->assertNotEmpty($graduatePrograms);

        foreach ($graduatePrograms as $program) {
            $this->assertStringContainsString($program, $registrationScript);
        }
    }

    public function test_admin_analytics_filters_can_be_requested_as_json_for_async_updates(): void
    {
        $this->seed();

        $admin = Admin::where('email', 'admin@cmu.edu.ph')->firstOrFail();

        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.analytics', [
            'user_growth_range' => 12,
            'applications_range' => 14,
            'sex' => 'Male',
            'college' => 'College of Information Sciences and Computing',
            'degree' => 'BS Information Technology',
            'graduation_year' => 2023,
            'employment_status' => 'employed',
            'industry' => 'Technology',
            'location' => 'Malaybalay City, Bukidnon',
            'salary_range' => '25,000.00 - 35,000.00',
        ]));

        $response->assertOk();
        $response->assertJsonPath('filters.user_growth_range', 12);
        $response->assertJsonPath('filters.applications_range', 14);
        $response->assertJsonPath('filters.sex', 'Male');
        $response->assertJsonPath('filters.college', 'College of Information Sciences and Computing');
        $response->assertJsonPath('filters.degree', 'BS Information Technology');
        $response->assertJsonPath('filters.graduation_year', 2023);
        $response->assertJsonPath('filters.employment_status', 'employed');
        $response->assertJsonPath('filters.industry', 'Technology');
        $response->assertJsonPath('filters.location', 'Malaybalay City, Bukidnon');
        $response->assertJsonPath('filters.salary_range', '25,000.00 - 35,000.00');
        $response->assertJsonPath('ui.userGrowthRangeLabel', 'Last 12 Months');
        $response->assertJsonPath('ui.applicationsRangeLabel', 'Last 14 Days');
        $response->assertJsonPath('refreshed', false);
        $response->assertSee('Report Filters');
    }

    public function test_admin_analytics_extended_column_filters_change_the_result_set(): void
    {
        $this->seed();

        $filters = [
            'college' => 'College of Information Sciences and Computing',
            'degree' => 'BS Information Technology',
            'sex' => 'Male',
            'graduation_year' => 2023,
            'employment_status' => 'employed',
            'industry' => 'Technology',
            'location' => 'Malaybalay City, Bukidnon',
            'salary_range' => '25,000.00 - 35,000.00',
        ];

        $payload = app(AdminAnalyticsService::class)->analyticsPayload($filters, true);

        $expectedUsers = DB::table('users')
            ->where('department', $filters['college'])
            ->where('degree', $filters['degree'])
            ->where('sex', $filters['sex'])
            ->where('year_graduated', $filters['graduation_year'])
            ->where('employed_after_grad', 'Yes')
            ->where('industry', $filters['industry'])
            ->where('location', $filters['location'])
            ->where('salary_range', $filters['salary_range'])
            ->count();

        $this->assertSame($expectedUsers, $payload['totalUsers']);
        $this->assertSame($filters['degree'], $payload['filters']['degree']);
        $this->assertSame($filters['sex'], $payload['filters']['sex']);
        $this->assertSame($filters['employment_status'], $payload['filters']['employment_status']);
        $this->assertSame($filters['industry'], $payload['filters']['industry']);
        $this->assertSame($filters['location'], $payload['filters']['location']);
        $this->assertSame($filters['salary_range'], $payload['filters']['salary_range']);
        $this->assertContains('BS Information Technology', $payload['filterOptions']['degrees']);
        $this->assertContains('Male', $payload['filterOptions']['sexes']);
        $this->assertContains('Female', $payload['filterOptions']['sexes']);
        $this->assertContains('Technology', $payload['filterOptions']['industries']);
    }

    public function test_admin_analytics_age_range_filter_limits_scope_by_birthdate(): void
    {
        $this->travelTo(now()->setDate(2026, 5, 21)->startOfDay());

        User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'birthdate' => now()->subYears(23)->toDateString(),
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
        ]);
        User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'birthdate' => now()->subYears(37)->toDateString(),
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
        ]);
        User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'birthdate' => null,
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
        ]);

        $payload = app(AdminAnalyticsService::class)->analyticsPayload([
            'age_from' => 18,
            'age_to' => 24,
        ], true);

        $this->assertSame(18, $payload['filters']['age_from']);
        $this->assertSame(24, $payload['filters']['age_to']);
        $this->assertSame('18-24', $payload['filters']['age_range']);
        $this->assertSame('18 - 24', $payload['ui']['selectedAgeRangeLabel']);
        $this->assertSame(1, $payload['totalUsers']);
        $this->assertSame(1, $payload['alumniAccountSummary']['analytics_matched']);

        $legacyPayload = app(AdminAnalyticsService::class)->analyticsPayload([
            'age_range' => '18-24',
        ], true);

        $this->assertSame(18, $legacyPayload['filters']['age_from']);
        $this->assertSame(24, $legacyPayload['filters']['age_to']);
        $this->assertSame(1, $legacyPayload['totalUsers']);
    }

    public function test_admin_analytics_async_filters_accept_age_range_from_and_to(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson(route('admin.analytics', [
            'age_from' => 25,
            'age_to' => 34,
        ]));

        $response->assertOk();
        $response->assertJsonPath('filters.age_from', 25);
        $response->assertJsonPath('filters.age_to', 34);
        $response->assertJsonPath('filters.age_range', '25-34');
        $response->assertJsonPath('ui.selectedAgeRangeLabel', '25 - 34');
        $response->assertSee('Age Range');
        $this->assertStringContainsString('name="age_from"', $response->json('html'));
        $this->assertStringContainsString('name="age_to"', $response->json('html'));
    }

    public function test_sample_seed_produces_multi_month_analytics_series(): void
    {
        $this->seed();

        $payload = app(AdminAnalyticsService::class)->analyticsPayload();

        $this->assertGreaterThan(1, count($payload['employmentTrends']['labels']));
        $this->assertTrue(collect($payload['employmentTrends']['selfEmployed'])->sum() > 0);
        $this->assertNotEmpty($payload['industryGrowth']['datasets']);
    }

    public function test_admin_analytics_payload_includes_extended_tracer_distributions(): void
    {
        User::factory()->admin()->create();

        $firstUser = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
        ]);
        $secondUser = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
        ]);

        DB::table('tracer_records')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $firstUser->id,
                'tracer_date' => now()->toDateString(),
                'employment_type' => 'Full-time',
                'curriculum_relevant_to_first_job' => true,
                'time_to_first_job' => '1 to 6 months',
                'present_employment_status' => 'Regular or Permanent',
                'facilities_satisfaction' => 'Satisfied',
                'teaching_faculty_enthusiastic' => 5,
                'teaching_concepts_clear' => 4,
                'teaching_faculty_knowledgeable' => 5,
                'teaching_commitment' => 4,
                'degree_program_quality' => 5,
                'overall_recommend_institution' => 5,
                'overall_objectives_met' => 4,
                'overall_university_experience' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $secondUser->id,
                'tracer_date' => now()->toDateString(),
                'employment_type' => 'Unemployed',
                'curriculum_relevant_to_first_job' => false,
                'time_to_first_job' => '7 to 11 months',
                'present_employment_status' => 'Contractual',
                'facilities_satisfaction' => 'Not Satisfied',
                'teaching_faculty_enthusiastic' => 3,
                'teaching_concepts_clear' => 2,
                'teaching_faculty_knowledgeable' => 4,
                'teaching_commitment' => 3,
                'degree_program_quality' => 3,
                'overall_recommend_institution' => 4,
                'overall_objectives_met' => 3,
                'overall_university_experience' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = app(AdminAnalyticsService::class)->analyticsPayload([], true);

        $this->assertSame(['Yes', 'No'], $payload['curriculumRelevanceData']['labels']);
        $this->assertSame([1, 1], $payload['curriculumRelevanceData']['data']);
        $this->assertSame(['1 to 6 months', '7 to 11 months'], $payload['firstJobTimeData']['labels']);
        $this->assertSame([1, 1], $payload['firstJobTimeData']['data']);
        $this->assertSame(['Regular or Permanent', 'Contractual'], $payload['presentEmploymentStatusData']['labels']);
        $this->assertSame([1, 1], $payload['presentEmploymentStatusData']['data']);
        $this->assertSame(['Satisfied', 'Not Satisfied'], $payload['facilitiesSatisfactionData']['labels']);
        $this->assertSame([1, 1], $payload['facilitiesSatisfactionData']['data']);
        $this->assertSame(
            ['Faculty enthusiasm', 'Explains concepts', 'Field knowledge', 'Teaching commitment', 'Degree program quality'],
            $payload['teachingFeedbackData']['labels']
        );
        $this->assertSame([4.0, 3.0, 4.5, 3.5, 4.0], $payload['teachingFeedbackData']['data']);
        $this->assertSame(
            ['Recommend institution', 'Objectives fulfilled', 'University experience'],
            $payload['overallExperienceData']['labels']
        );
        $this->assertSame([4.5, 3.5, 3.5], $payload['overallExperienceData']['data']);
        $this->assertSame(['Teaching Feedback', 'University Facilities', 'Overall Experience'], $payload['satisfactionRatingsData']['labels']);
        $this->assertSame([3.8, 2.0, 3.8], $payload['satisfactionRatingsData']['data']);
    }

    public function test_admin_can_export_employability_csv_with_tracer_numbers_and_name_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $alumni = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Jane',
            'lastname' => 'Tracer',
            'department' => 'College of Nursing',
            'degree' => 'BS Nursing',
            'year_graduated' => 2021,
            'employed_after_grad' => 'Yes',
            'location' => 'Valencia City, Bukidnon',
        ]);

        DB::table('tracer_records')->insert([
            'id' => (string) Str::uuid(),
            'tracer_number' => 'UTF-00770A',
            'user_id' => $alumni->id,
            'tracer_date' => now()->toDateString(),
            'employment_type' => 'Full-time',
            'current_company' => 'Valencia Polymedic General Hospital',
            'current_position' => 'Nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.export', [
            'report' => 'employability',
            'degree' => 'BS Nursing',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Tracer No.', $content);
        $this->assertStringContainsString('UTF-00770A', $content);
        $this->assertStringContainsString('Jane Tracer', $content);
        $this->assertStringContainsString('Names Summary', $content);
        $this->assertStringContainsString('Name of Company', $content);
        $this->assertStringContainsString('Address', $content);
        $this->assertStringContainsString('Valencia City, Bukidnon', $content);
    }

    public function test_admin_can_export_satisfaction_csv_with_comments_and_name_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $alumni = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Luis',
            'lastname' => 'Santos',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2023,
        ]);

        DB::table('tracer_records')->insert([
            'id' => (string) Str::uuid(),
            'tracer_number' => 'UTF-00828A',
            'user_id' => $alumni->id,
            'tracer_date' => now()->toDateString(),
            'employment_type' => 'Full-time',
            'facilities_satisfaction' => 'Satisfied',
            'facility_suggestions' => 'More laboratory equipment.',
            'employability_suggestions' => 'Add more internship partners.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.export', [
            'report' => 'satisfaction',
            'degree' => 'BS Information Technology',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Satisfaction Report', $content);
        $this->assertStringContainsString('UTF-00828A', $content);
        $this->assertStringContainsString('Luis Santos', $content);
        $this->assertStringContainsString('Satisfied', $content);
        $this->assertStringContainsString('More laboratory equipment.', $content);
        $this->assertStringContainsString('Names Summary', $content);
        $this->assertStringContainsString('Employment Status', $content);
    }

    public function test_admin_detailed_csv_exports_apply_sex_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $femaleAlumni = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Maria',
            'lastname' => 'Filtered',
            'sex' => 'Female',
            'degree' => 'BS Biology',
            'year_graduated' => 2022,
            'employed_after_grad' => 'Yes',
        ]);
        $maleAlumni = User::factory()->create([
            'is_admin' => false,
            'firstname' => 'Mark',
            'lastname' => 'Excluded',
            'sex' => 'Male',
            'degree' => 'BS Biology',
            'year_graduated' => 2022,
            'employed_after_grad' => 'Yes',
        ]);

        DB::table('tracer_records')->insert([
            [
                'id' => (string) Str::uuid(),
                'tracer_number' => 'UTF-FEMALE',
                'user_id' => $femaleAlumni->id,
                'tracer_date' => now()->toDateString(),
                'employment_type' => 'Full-time',
                'facilities_satisfaction' => 'Satisfied',
                'facility_suggestions' => 'Keep improving laboratory access.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'tracer_number' => 'UTF-MALE',
                'user_id' => $maleAlumni->id,
                'tracer_date' => now()->toDateString(),
                'employment_type' => 'Full-time',
                'facilities_satisfaction' => 'Satisfied',
                'facility_suggestions' => 'Excluded by sex filter.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        foreach (['employability', 'satisfaction'] as $report) {
            $response = $this->actingAs($admin)->get(route('admin.analytics.export', [
                'report' => $report,
                'degree' => 'BS Biology',
                'sex' => 'Female',
            ]));

            $response->assertOk();
            $content = $response->streamedContent();

            $this->assertStringContainsString('Sex: Female', $content);
            $this->assertStringContainsString('UTF-FEMALE', $content);
            $this->assertStringContainsString('Maria Filtered', $content);
            $this->assertStringNotContainsString('UTF-MALE', $content);
            $this->assertStringNotContainsString('Mark Excluded', $content);
        }
    }

    public function test_admin_analytics_and_exports_exclude_csv_only_alumni_records(): void
    {
        $admin = User::factory()->admin()->create();

        $registeredAlumni = User::factory()->create([
            'firstname' => 'Leah',
            'lastname' => 'Lim',
            'email' => 'leah.lim@example.com',
            'is_batch_imported' => false,
            'department' => 'College of Veterinary Medicine',
            'degree' => 'Doctor of Veterinary Medicine',
            'year_graduated' => 2025,
            'employed_after_grad' => 'No',
        ]);

        User::factory()->create([
            'firstname' => 'Jessa',
            'lastname' => 'Aquino',
            'email' => 'utf-00001a@oarl-batch.local',
            'is_batch_imported' => true,
            'department' => 'College of Veterinary Medicine',
            'degree' => 'Doctor of Veterinary Medicine',
            'year_graduated' => 2025,
            'employed_after_grad' => null,
        ]);

        DB::table('tracer_records')->insert([
            'id' => (string) Str::uuid(),
            'tracer_number' => 'UTF-00001A',
            'user_id' => $registeredAlumni->id,
            'tracer_date' => now()->toDateString(),
            'employment_type' => 'Unemployed',
            'challenges_faced' => 'Still applying',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = app(AdminAnalyticsService::class)->analyticsPayload([
            'college' => 'College of Veterinary Medicine',
            'degree' => 'Doctor of Veterinary Medicine',
            'graduation_year' => 2025,
        ], true);

        $this->assertSame(1, $payload['totalUsers']);
        $this->assertSame(1, $payload['alumniAccountSummary']['registered']);
        $this->assertSame(1, $payload['alumniAccountSummary']['not_registered']);
        $this->assertSame([1], $payload['tracedGraduates']['data']);
        $this->assertCount(1, $payload['csvNameSummary']);
        $this->assertSame('Leah Lim', $payload['csvNameSummary'][0]['name']);

        $response = $this->actingAs($admin)->get(route('admin.analytics.export', [
            'report' => 'employability',
            'college' => 'College of Veterinary Medicine',
            'degree' => 'Doctor of Veterinary Medicine',
            'graduation_year' => 2025,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Leah Lim', $content);
        $this->assertStringContainsString('UTF-00001A', $content);
        $this->assertStringNotContainsString('Jessa Aquino', $content);
    }

    public function test_admin_analytics_payload_includes_feedback_categories_for_filtered_alumni(): void
    {
        User::factory()->admin()->create();

        $matchingUser = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Engineering',
            'degree' => 'BS Civil Engineering',
            'year_graduated' => 2024,
            'industry' => 'Construction',
            'location' => 'Malaybalay City, Bukidnon',
            'salary_range' => '25,000.00 - 35,000.00',
            'employed_after_grad' => 'Yes',
        ]);

        $otherUser = User::factory()->create([
            'is_admin' => false,
            'department' => 'College of Information Sciences and Computing',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'industry' => 'Technology',
            'location' => 'Valencia City, Bukidnon',
            'salary_range' => '15,000.00 - 25,000.00',
            'employed_after_grad' => 'No',
        ]);

        DB::table('feedback')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $matchingUser->id,
                'subject' => 'Portal issue',
                'message' => 'The profile form is confusing.',
                'type' => 'Bug Report',
                'status' => 'Open',
                'priority' => 'Medium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $matchingUser->id,
                'subject' => 'Portal suggestion',
                'message' => 'Please improve the settings page.',
                'type' => 'Feature Request',
                'status' => 'Open',
                'priority' => 'Medium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $otherUser->id,
                'subject' => 'Other concern',
                'message' => 'This should be excluded by filters.',
                'type' => 'Support',
                'status' => 'Open',
                'priority' => 'Medium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('tracer_records')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $matchingUser->id,
                'tracer_date' => now()->toDateString(),
                'employment_type' => 'Full-time',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $otherUser->id,
                'tracer_date' => now()->toDateString(),
                'employment_type' => 'Unemployed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = app(AdminAnalyticsService::class)->analyticsPayload([
            'college' => 'College of Engineering',
            'degree' => 'BS Civil Engineering',
            'graduation_year' => 2024,
            'employment_status' => 'employed',
            'industry' => 'Construction',
            'location' => 'Malaybalay City, Bukidnon',
            'salary_range' => '25,000.00 - 35,000.00',
        ], true);

        $this->assertSame(['Bug Report', 'Feature Request'], $payload['feedbacksData']['labels']);
        $this->assertSame([1, 1], $payload['feedbacksData']['data']);
    }

    public function test_force_refresh_bypasses_the_cached_default_admin_analytics_payload(): void
    {
        Cache::forget('admin-analytics:payload:v20');

        $service = app(AdminAnalyticsService::class);

        User::factory()->admin()->create();

        $initialPayload = $service->analyticsPayload();

        User::factory()->create();

        $cachedPayload = $service->analyticsPayload();
        $refreshedPayload = $service->analyticsPayload([], true);

        $this->assertSame($initialPayload['totalUsers'], $cachedPayload['totalUsers']);
        $this->assertSame($initialPayload['totalUsers'] + 1, $refreshedPayload['totalUsers']);
    }

    public function test_employment_rate_does_not_exceed_one_hundred_percent_with_duplicate_latest_tracer_dates(): void
    {
        $admin = User::factory()->admin()->create();

        $user = User::factory()->create([
            'is_admin' => false,
            'employed_after_grad' => 'Yes',
        ]);

        DB::table('tracer_records')->insert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'tracer_date' => now()->toDateString(),
                'employment_type' => 'Full-time',
                'created_at' => now()->subSecond(),
                'updated_at' => now()->subSecond(),
            ],
            [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'tracer_date' => now()->toDateString(),
                'employment_type' => 'Full-time',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = app(AdminAnalyticsService::class)->analyticsPayload([], true);

        $this->assertSame(1, $payload['totalUsers']);
        $this->assertSame(1, $payload['employmentStatus']['employed']);
        $this->assertSame(100.0, $payload['employmentRate']);
        $this->actingAs($admin)->get(route('admin.analytics'))->assertSeeText('100%');
    }
}
