<?php

namespace Tests\Feature;

use App\Notifications\RegistrationEmailVerificationCode;
use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_is_redirected_to_login(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $response = $this
            ->withSession($this->verifiedRegistrationSession())
            ->post(route('register.submit'), $this->registrationPayload([
                'hasUndergraduateDegree' => 'Yes',
                'hasScholarship' => 'Yes',
                'scholarshipName' => 'CHED Full Merit',
                'scholarshipYear' => 2023,
                'scholarshipHelpedJob' => 'No',
                'presentlyEmployed' => 'Yes',
                'presentEmploymentStatus' => 'Regular or Permanent',
                'presentOccupation' => 'Accounting Associate',
                'presentCompany' => 'CMU Cooperative',
                'directSupervisor' => 'Ana Reyes',
                'employerEmail' => 'ana.reyes@example.com',
                'businessLine' => 'Education',
                'firstJobIsCurrent' => 'No',
                'employmentProof' => $this->fakePdfUpload('company-id.pdf'),
                'jobLevel' => 'Professional, Technical or Supervisory',
                'presentJobReasons' => ['Salaries & Benefits', 'Related to course or program of study'],
                'presentJobEarnings' => 'P20,000.00 to less than P30,000.00',
                'hasEmployeeAwards' => 'No',
                'newsletter_opt_in' => 0,
            ]));

        $response->assertRedirect(route('login', ['registered' => 1]));
        $this->assertGuest();

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue((bool) $user->data_privacy_consent);
        $this->assertNotNull($user->data_privacy_consented_at);
        $this->assertFalse((bool) $user->newsletter_opt_in);
        $this->assertTrue((bool) $user->has_scholarship);
        $this->assertSame('CHED Full Merit', $user->scholarship_name);
        $this->assertSame(2023, $user->scholarship_year_awarded);
        $this->assertFalse((bool) $user->scholarship_helped_get_job);
        $this->assertNull($user->current_position);
        $this->assertNull($user->company);
        $this->assertNull($user->industry);
        $this->assertNull($user->salary_range);
        $this->assertNull($user->employed_after_grad);

        $this->assertDatabaseHas('tracer_records', [
            'user_id' => $user->id,
            'curriculum_relevant_to_first_job' => true,
            'employed_after_graduation' => true,
            'time_to_first_job' => '1 to 6 months',
            'presently_employed' => 'Yes',
            'present_employment_status' => 'Regular or Permanent',
            'present_job_monthly_earnings' => 'P20,000.00 to less than P30,000.00',
            'facilities_satisfaction' => 'Satisfied',
        ]);

        $employmentProofRecord = DB::table('tracer_records')
            ->where('user_id', $user->id)
            ->select([
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
    }

    public function test_registration_persists_authoritative_verified_values_for_locked_fields(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $response = $this
            ->withSession($this->verifiedRegistrationSession(email: 'verified-lock@example.com'))
            ->post(route('register.submit'), $this->registrationPayload([
                'email' => 'verified-lock@example.com',
                'firstname' => 'jane',
                'lastname' => 'doe',
                'degree' => 'BSIT',
            ]));

        $response->assertRedirect(route('login', ['registered' => 1]));
        $this->assertGuest();

        $user = User::where('email', 'verified-lock@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Jane', $user->firstname);
        $this->assertSame('Doe', $user->lastname);
        $this->assertSame('College of Information Sciences and Computing', $user->department);
        $this->assertSame('BS Information Technology', $user->degree);
        $this->assertSame(2024, $user->year_graduated);
    }

    public function test_alumni_verification_matches_the_sample_excel_record(): void
    {
        $response = $this->postJson(route('signup.verify-alumni'), [
            'firstname' => 'Jane',
            'middlename' => '',
            'lastname' => 'Doe',
            'birthdate' => '2000-01-01',
            'collegeGraduated' => 'College of Information Sciences and Computing',
            'yearGraduated' => 2024,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('verification.firstname', 'Jane')
            ->assertJsonPath('verification.lastname', 'Doe')
            ->assertJsonPath('verification.collegeGraduated', 'College of Information Sciences and Computing')
            ->assertJsonPath('verification.degreeGraduated', 'BS Information Technology')
            ->assertJsonPath('verification.yearGraduated', '2024')
            ->assertSessionHas('registration_alumni_verification', function ($payload) {
                return is_array($payload)
                    && ($payload['firstname'] ?? null) === 'Jane'
                    && ($payload['lastname'] ?? null) === 'Doe'
                    && ($payload['college_graduated'] ?? null) === 'College of Information Sciences and Computing'
                    && ($payload['degree_graduated'] ?? null) === 'BS Information Technology'
                    && ($payload['year_graduated'] ?? null) === '2024';
            });
    }

    public function test_user_can_register_with_streamlined_employment_and_feedback_inputs(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        $payload = $this->registrationPayload([
            'email' => 'streamlined@example.com',
            'presentlyEmployed' => 'Yes',
            'presentEmploymentStatus' => 'Regular or Permanent',
            'presentOccupation' => 'Project Staff',
            'presentCompany' => 'CMU Alumni Office',
            'businessLine' => 'Education',
            'presentJobEarnings' => 'P20,000.00 to less than P30,000.00',
            'employmentProof' => $this->fakePdfUpload('employment-proof.pdf'),
            'shareMoreFirstJobDetails' => 'No',
            'firstJobHow' => null,
            'firstJobEarnings' => null,
            'firstJobDuration' => null,
            'shareMoreEmploymentDetails' => 'No',
            'directSupervisor' => null,
            'employerEmail' => null,
            'jobLevel' => null,
            'firstJobIsCurrent' => null,
            'presentJobReasons' => [],
            'hasEmployeeAwards' => null,
            'employeeAwards' => null,
            'teachingFeedbackSummary' => 4,
            'teachingFacultyEnthusiastic' => null,
            'teachingConceptsClear' => null,
            'teachingKnowledgeable' => null,
            'teachingCommitment' => null,
            'degreeProgramQuality' => null,
            'overallExperienceSummary' => 5,
            'overallRecommendInstitution' => null,
            'overallObjectivesMet' => null,
            'overallUniversityExperience' => null,
        ]);

        $response = $this
            ->withSession($this->verifiedRegistrationSession(email: $payload['email']))
            ->post(route('register.submit'), $payload);

        $response->assertRedirect(route('login', ['registered' => 1]));
        $this->assertGuest();

        $user = User::where('email', 'streamlined@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->current_position);
        $this->assertNull($user->company);
        $this->assertNull($user->industry);

        $this->assertDatabaseHas('tracer_records', [
            'user_id' => $user->id,
            'present_employment_status' => 'Regular or Permanent',
            'present_job_monthly_earnings' => 'P20,000.00 to less than P30,000.00',
            'teaching_faculty_enthusiastic' => 4,
            'teaching_concepts_clear' => 4,
            'degree_program_quality' => 4,
            'overall_recommend_institution' => 5,
            'overall_objectives_met' => 5,
            'overall_university_experience' => 5,
        ]);

        $this->assertDatabaseHas('tracer_records', [
            'user_id' => $user->id,
            'direct_supervisor_name' => null,
            'job_level' => null,
            'has_employee_awards' => null,
            'employment_proof_disk' => 'private',
            'employment_proof_original_name' => 'employment-proof.pdf',
            'employment_proof_status' => 'pending',
        ]);
    }

    public function test_alumni_verification_rejects_a_single_letter_middle_name(): void
    {
        $response = $this->postJson(route('signup.verify-alumni'), [
            'firstname' => 'John',
            'middlename' => 'A',
            'lastname' => 'Reyes',
            'birthdate' => '2000-01-01',
            'collegeGraduated' => 'College of Engineering',
            'yearGraduated' => 2021,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('middlename')
            ->assertJsonPath('errors.middlename.0', 'Please enter your full middle name.');
    }

    public function test_existing_alumni_lookup_returns_the_masked_email_message(): void
    {
        User::factory()->create([
            'firstname' => 'John',
            'lastname' => 'Reyes',
            'email' => 'washeley3@gmail.com',
            'year_graduated' => 2021,
        ]);

        $response = $this->getJson(route('signup.check-existing-alumni', [
            'firstname' => 'john',
            'lastname' => 'reyes',
            'yearGraduated' => 2021,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('message', 'This alumni record is already registered with the email "w***e***3@gmail.com". Please sign in or use password reset if you need access.');
    }

    public function test_alumni_verification_rejects_an_already_registered_record(): void
    {
        User::factory()->create([
            'firstname' => 'John',
            'lastname' => 'Reyes',
            'email' => 'washeley3@gmail.com',
            'year_graduated' => 2021,
        ]);

        $response = $this->postJson(route('signup.verify-alumni'), [
            'firstname' => 'John',
            'middlename' => 'Aquino',
            'lastname' => 'Reyes',
            'collegeGraduated' => 'College of Engineering',
            'yearGraduated' => 2021,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'This alumni record is already registered with the email "w***e***3@gmail.com". Please sign in or use password reset if you need access.')
            ->assertJsonValidationErrors('firstname')
            ->assertSessionMissing('registration_alumni_verification');
    }

    public function test_alumni_verification_matches_a_non_degree_record(): void
    {
        $response = $this->postJson(route('signup.verify-alumni'), [
            'firstname' => 'Carla',
            'middlename' => 'Dela Cruz',
            'lastname' => 'Morales',
            'collegeGraduated' => 'Non-Degree Programs',
            'yearGraduated' => 2015,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('verification.firstname', 'Carla')
            ->assertJsonPath('verification.lastname', 'Morales')
            ->assertJsonPath('verification.collegeGraduated', 'Non-Degree Programs')
            ->assertJsonPath('verification.degreeGraduated', 'Certificate in Teaching')
            ->assertJsonPath('verification.yearGraduated', '2015');
    }

    public function test_registration_requires_a_verified_alumni_record_first(): void
    {
        $response = $this
            ->from(route('signup'))
            ->post(route('register.submit'), $this->registrationPayload());

        $response
            ->assertRedirect(route('signup'))
            ->assertSessionHasErrors('firstname');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_email_verification_code_can_be_sent_and_confirmed(): void
    {
        Notification::fake();

        $email = 'jane.verify@example.com';

        $sendResponse = $this->postJson(route('signup.email-verification.send'), [
            'email' => $email,
        ]);

        $sendResponse
            ->assertOk()
            ->assertJsonPath('message', 'Verification code sent. Please check your email.')
            ->assertJsonPath('resend_after_seconds', 30)
            ->assertJsonPath('expires_in_seconds', 300);

        $verificationCode = null;

        Notification::assertSentOnDemand(
            RegistrationEmailVerificationCode::class,
            function (RegistrationEmailVerificationCode $notification, array $channels, object $notifiable) use ($email, &$verificationCode) {
                $verificationCode = $notification->code;

                return in_array('mail', $channels, true)
                    && ($notifiable->routes['mail'] ?? null) === $email
                    && preg_match('/^\d{6}$/', $notification->code) === 1;
            }
        );

        $this->assertNotNull($verificationCode);

        $verifyResponse = $this->postJson(route('signup.email-verification.verify'), [
            'email' => $email,
            'code' => $verificationCode,
        ]);

        $verifyResponse
            ->assertOk()
            ->assertJsonPath('message', 'Email verified.')
            ->assertSessionHas('registration_email_verification', function ($payload) use ($email) {
                return is_array($payload)
                    && ($payload['email'] ?? null) === $email
                    && filled($payload['verified_at'] ?? null);
            });
    }

    public function test_registration_email_verification_resend_waits_thirty_seconds(): void
    {
        Notification::fake();

        $email = 'jane.cooldown@example.com';

        $this->postJson(route('signup.email-verification.send'), [
            'email' => $email,
        ])->assertOk();

        $resendResponse = $this->postJson(route('signup.email-verification.send'), [
            'email' => $email,
        ]);

        $resendResponse
            ->assertStatus(429)
            ->assertJsonStructure(['resend_after_seconds', 'resend_available_at'])
            ->assertJsonValidationErrors('email');

        $this->assertGreaterThanOrEqual(25, $resendResponse->json('resend_after_seconds'));
        $this->assertLessThanOrEqual(30, $resendResponse->json('resend_after_seconds'));
    }

    public function test_registration_requires_a_verified_email_address(): void
    {
        $session = $this->verifiedRegistrationSession();
        unset($session['registration_email_verification']);

        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->withSession($session)
            ->post(route('register.submit'), $this->registrationPayload());

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Please verify your email address before completing registration.')
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_rejects_a_mismatched_undergraduate_college(): void
    {
        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->withSession($this->verifiedRegistrationSession())
            ->post(route('register.submit'), $this->registrationPayload([
                'department' => 'College of Engineering',
            ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'The selected college does not match the verified CMU alumni record.')
            ->assertJsonValidationErrors('department');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_requires_data_privacy_consent(): void
    {
        $payload = $this->registrationPayload();
        unset($payload['data_privacy_consent']);

        $response = $this
            ->withSession($this->verifiedRegistrationSession())
            ->post(route('register.submit'), $payload);

        $response->assertSessionHasErrors('data_privacy_consent');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_rejects_duplicate_alumni_identity_even_with_a_different_email(): void
    {
        User::factory()->create([
            'firstname' => 'John',
            'lastname' => 'Reyes',
            'email' => 'washeley3@gmail.com',
            'year_graduated' => 2021,
        ]);

        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withHeader('Accept', 'application/json')
            ->withSession($this->verifiedRegistrationSession([
                'firstname' => 'John',
                'middlename' => 'Aquino',
                'lastname' => 'Reyes',
                'college_graduated' => 'College of Engineering',
                'degree_graduated' => 'BS Civil Engineering',
                'year_graduated' => '2021',
                'record' => [
                    'first_name' => 'John',
                    'middle_name' => 'Aquino',
                    'last_name' => 'Reyes',
                    'college_graduated' => 'College of Engineering',
                    'degree_graduated' => 'BS Civil Engineering',
                    'year_graduated' => '2021',
                ],
            ], 'john.duplicate@example.com'))
            ->post(route('register.submit'), $this->registrationPayload([
                'firstname' => 'John',
                'middlename' => 'Aquino',
                'lastname' => 'Reyes',
                'collegeGraduated' => 'College of Engineering',
                'yearGraduated' => '2021',
                'email' => 'john.duplicate@example.com',
                'department' => 'College of Engineering',
                'degree' => 'BS Civil Engineering',
            ]));

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'This alumni record is already registered with the email "w***e***3@gmail.com". Please sign in or use password reset if you need access.')
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'john.duplicate@example.com']);
    }

    public function test_user_can_register_without_undergraduate_degree_details_when_marked_no(): void
    {
        $response = $this
            ->withSession($this->verifiedRegistrationSession(email: 'nongrad@example.com'))
            ->post(route('register.submit'), $this->registrationPayload([
                'email' => 'nongrad@example.com',
                'hasUndergraduateDegree' => 'No',
                'hasScholarship' => 'No',
                'department' => '',
                'degree' => '',
            ]));

        $response->assertRedirect(route('login', ['registered' => 1]));
        $this->assertGuest();

        $this->assertDatabaseHas('users', [
            'email' => 'nongrad@example.com',
            'department' => 'College of Information Sciences and Computing',
            'degree' => null,
        ]);
    }

    public function test_alumni_user_can_login_and_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'alumni@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'alumni@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_roles_separate_alumni_admin_and_super_admin(): void
    {
        $alumni = User::factory()->create();
        $admin = Admin::factory()->create();
        $superAdmin = SuperAdmin::factory()->create();

        $this->assertSame(User::ROLE_ALUMNI, $alumni->fresh()->role);
        $this->assertTrue($alumni->fresh()->isAlumni());
        $this->assertFalse($alumni->fresh()->isAdmin());

        $this->assertSame(Admin::ROLE_ADMIN, $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertFalse($admin->fresh()->isSuperAdmin());

        $this->assertTrue($superAdmin->fresh()->isAdmin());
        $this->assertTrue($superAdmin->fresh()->isSuperAdmin());
    }

    public function test_super_admin_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $superAdmin = SuperAdmin::factory()->create([
            'email' => 'super.admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'super.admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($superAdmin, 'super_admin');
        $this->assertDatabaseHas('system_logs', [
            'super_admin_id' => $superAdmin->id,
            'action' => 'auth.super_admin.login',
        ]);
    }

    public function test_login_with_remember_me_queues_recaller_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'remembered@example.com',
            'password' => bcrypt('password123'),
            'remember_token' => null,
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'remembered@example.com',
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_without_remember_me_does_not_queue_recaller_cookie(): void
    {
        $user = User::factory()->create([
            'email' => 'session-only@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'session-only@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertCookieMissing(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_rate_limited_after_repeated_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'locked@example.com',
            'password' => bcrypt('password123'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $response = $this->post(route('login.submit'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            $response->assertSessionHasErrors('email');
        }

        $lockedResponse = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $lockedResponse->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts.',
            session('errors')->first('email')
        );
    }

    public function test_non_admin_is_redirected_away_from_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => bcrypt('old-password123'),
        ]);

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');

        $token = Password::broker()->createToken($user);

        $resetResponse = $this->post(route('password.store'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $resetResponse->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_settings_password_update_requires_matching_current_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password123'),
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('settings'))
            ->post(route('settings.password.update'), [
                'settings_form' => 'password',
                'current_password' => 'wrong-password123',
                'new_password' => 'new-password123',
                'new_password_confirmation' => 'new-password123',
            ]);

        $response
            ->assertRedirect(route('settings'))
            ->assertSessionHasErrors(['current_password']);

        $user->refresh();

        $this->assertTrue(Hash::check('old-password123', $user->password));
        $this->assertFalse(Hash::check('new-password123', $user->password));
    }

    public function test_settings_password_update_saves_when_current_password_matches(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password123'),
        ]);

        $response = $this->actingAs($user)->post(route('settings.password.update'), [
            'settings_form' => 'password',
            'current_password' => 'old-password123',
            'new_password' => 'new-password123',
            'new_password_confirmation' => 'new-password123',
        ]);

        $response
            ->assertRedirect(route('settings'))
            ->assertSessionHas('success', 'Password updated successfully.');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_user_can_sign_out_other_devices_from_settings(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $otherSessionId = 'other-device-session';

        DB::table('sessions')->insert([
            'id' => $otherSessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0 Chrome',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->post(route('settings.sessions.logout-other'), [
            'current_password' => 'password123',
        ]);

        $response->assertRedirect(route('settings'));
        $this->assertDatabaseMissing('sessions', [
            'id' => $otherSessionId,
        ]);
    }

    public function test_settings_updates_account_details_without_overwriting_tracer_owned_fields(): void
    {
        $user = User::factory()->create([
            'firstname' => 'Mira',
            'lastname' => 'Santos',
            'email' => 'mira@example.com',
            'phone' => '09171234567',
            'department' => 'College of Agriculture',
            'degree' => 'BS Agriculture',
            'year_graduated' => 2022,
            'location' => 'Bukidnon',
            'skills' => 'MS Excel',
            'achievements' => 'Campus leadership award',
            'employed_after_grad' => 'Yes',
            'industry' => 'Information Technology',
            'current_position' => 'Systems Analyst',
            'company' => 'CMU Digital Office',
            'salary_range' => '25,000.00 - 34,999.00',
            'newsletter_opt_in' => false,
        ]);

        $response = $this->actingAs($user)->post(route('settings.profile.update'), [
            'firstname' => 'Maria',
            'lastname' => 'Santos',
            'email' => 'maria.santos@example.com',
            'phone' => '09991234567',
            'department' => 'College of Engineering',
            'degree' => 'BS Information Technology',
            'year_graduated' => 2024,
            'location' => 'Bukidnon',
            'skills' => 'Laravel, Reporting',
            'achievements' => 'Dean\'s Lister',
            'newsletter_opt_in' => 0,
            'profile_information_attestation' => 1,
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success', 'Profile updated successfully.');

        $user->refresh();

        $this->assertSame('Maria', $user->firstname);
        $this->assertSame('maria.santos@example.com', $user->email);
        $this->assertSame('College of Engineering', $user->department);
        $this->assertSame('BS Information Technology', $user->degree);
        $this->assertSame(2024, $user->year_graduated);
        $this->assertSame('Laravel, Reporting', $user->skills);
        $this->assertSame('Dean\'s Lister', $user->achievements);
        $this->assertFalse((bool) $user->newsletter_opt_in);

        $this->assertSame('Yes', $user->employed_after_grad);
        $this->assertSame('Information Technology', $user->industry);
        $this->assertSame('Systems Analyst', $user->current_position);
        $this->assertSame('CMU Digital Office', $user->company);
        $this->assertSame('25,000.00 - 34,999.00', $user->salary_range);
    }

    public function test_settings_page_focuses_on_security_and_account_actions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings'));

        $response->assertOk();
        $response->assertDontSee('Account Management');
        $response->assertSee('Account Controls');
        $response->assertSee('Account Summary');
        $response->assertSee('Password and sign-in');
        $response->assertSee('Signed-in devices');
        $response->assertSee('Sign-in security');
        $response->assertSee('Danger Zone');
        $response->assertSee('OARL updates');
        $response->assertDontSeeText('Visibility');
        $response->assertDontSeeText('Public basic profile');
        $response->assertDontSee('First Name');
        $response->assertDontSee('Save Information');
    }

    public function test_deleting_an_account_cascades_related_records(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $jobId = (string) Str::uuid();
        $applicationId = (string) Str::uuid();
        $tracerId = (string) Str::uuid();
        $feedbackId = (string) Str::uuid();
        $sessionId = 'account-delete-session';

        DB::table('jobs')->insert([
            'id' => $jobId,
            'title' => 'Backend Developer',
            'company' => 'CMU Tech',
            'description' => 'A job used to verify cascading deletes.',
            'requirements' => 'Laravel and SQL',
            'location' => 'Bukidnon',
            'type' => 'Full-time',
            'contact_email' => 'jobs@example.com',
            'is_active' => true,
            'applications_count' => 0,
            'views_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tracer_records')->insert([
            'id' => $tracerId,
            'user_id' => $user->id,
            'tracer_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('feedback')->insert([
            'id' => $feedbackId,
            'user_id' => $user->id,
            'subject' => 'Delete account test',
            'message' => 'Testing related record cleanup.',
            'type' => 'General',
            'status' => 'Open',
            'priority' => 'Medium',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('job_applications')->insert([
            'id' => $applicationId,
            'job_id' => $jobId,
            'user_id' => $user->id,
            'cover_letter' => 'This application should be removed with the account.',
            'status' => 'Pending',
            'applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.10',
            'user_agent' => 'Mozilla/5.0 Chrome',
            'payload' => 'test-payload',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'delete_account_verified_user_id' => (string) $user->id,
                'delete_account_verified_at' => now()->toDateTimeString(),
            ])
            ->post(route('settings.account.delete'), [
                'delete_confirmation' => 'DELETE',
            ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('tracer_records', ['id' => $tracerId]);
        $this->assertDatabaseMissing('feedback', ['id' => $feedbackId]);
        $this->assertDatabaseMissing('job_applications', ['id' => $applicationId]);
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
    }

    protected function registrationPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'firstname' => 'Jane',
            'middlename' => '',
            'lastname' => 'Doe',
            'birthdate' => '2000-01-01',
            'collegeGraduated' => 'College of Information Sciences and Computing',
            'yearGraduated' => '2024',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'hasUndergraduateDegree' => 'Yes',
            'hasScholarship' => 'No',
            'department' => 'College of Information Sciences and Computing',
            'degree' => 'BSIT',
            'curriculumRelevant' => 'Yes',
            'firstJobCompetencies' => ['Information Technology skills'],
            'employedAfterGrad' => 'Yes',
            'firstJobTime' => '1 to 6 months',
            'firstJobRelated' => 'Yes',
            'firstJobHow' => 'Information from friends',
            'firstJobEarnings' => 'P10,000.00 to less than P15,000.00',
            'firstJobDuration' => '1 year to less than 2 years',
            'presentlyEmployed' => 'No',
            'teachingFacultyEnthusiastic' => 4,
            'teachingConceptsClear' => 4,
            'teachingKnowledgeable' => 5,
            'teachingCommitment' => 4,
            'degreeProgramQuality' => 4,
            'curriculumSuggestions' => 'Continue updating the curriculum with practical industry tools and projects.',
            'employabilitySuggestions' => 'Add more internship partnerships and career readiness workshops.',
            'facilitiesSatisfaction' => 'Satisfied',
            'facilitySuggestions' => 'Improve laboratory equipment availability and student study areas.',
            'overallRecommendInstitution' => 4,
            'overallObjectivesMet' => 4,
            'overallUniversityExperience' => 4,
            'municipalityCity' => 'Malaybalay',
            'regionOfOrigin' => 'Bukidnon',
            'country' => 'Philippines',
            'data_privacy_consent' => 1,
            'newsletter_opt_in' => 1,
        ], $overrides);
    }

    protected function fakePdfUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 64, 'application/pdf');
    }

    protected function verifiedRegistrationSession(array $overrides = [], ?string $email = null): array
    {
        $verifiedEmail = Str::lower($email ?? 'jane@example.com');

        return [
            'registration_alumni_verification' => array_merge([
                'firstname' => 'Jane',
                'middlename' => '',
                'lastname' => 'Doe',
                'birthdate' => '2000-01-01',
                'college_graduated' => 'College of Information Sciences and Computing',
                'degree_graduated' => 'Bachelor of Science in Information Technology',
                'year_graduated' => '2024',
                'verified_at' => now()->toIso8601String(),
                'record' => [
                    'first_name' => 'Jane',
                    'middle_name' => '',
                    'last_name' => 'Doe',
                    'birthdate' => '2000-01-01',
                    'college_graduated' => 'College of Information Sciences and Computing',
                    'degree_graduated' => 'BS Information Technology',
                    'year_graduated' => '2024',
                ],
            ], $overrides),
            'registration_email_verification' => [
                'email' => $verifiedEmail,
                'code_hash' => Hash::make('123456'),
                'sent_at' => now()->toIso8601String(),
                'expires_at' => now()->addMinutes(15)->toIso8601String(),
                'verified_at' => now()->toIso8601String(),
            ],
        ];
    }
}
