<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendResetLinkRequest;
use App\Http\Requests\Auth\VerifyAlumniRecordRequest;
use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\TracerRecord;
use App\Models\User;
use App\Notifications\RegistrationEmailVerificationCode;
use App\Services\AlumniRecordVerificationService;
use App\Services\AuditLogService;
use App\Services\EmploymentProofReviewNotificationService;
use App\Support\EmploymentProofStorage;
use App\Support\SystemOptions;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    private const REGISTRATION_VERIFICATION_SESSION_KEY = 'registration_alumni_verification';
    private const REGISTRATION_VERIFICATION_TTL_MINUTES = 60;
    private const REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY = 'registration_email_verification';
    private const REGISTRATION_EMAIL_VERIFICATION_TTL_MINUTES = 5;
    private const REGISTRATION_EMAIL_RESEND_COOLDOWN_SECONDS = 30;

    public function showLogin()
    {
        return view('auth.login'); // your login Blade
    }

    public function login(LoginRequest $request)
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['email' => "Too many login attempts. Try again in {$seconds} seconds."])
                ->withInput($request->only('email', 'remember'));
        }

        $credentials = $request->safe()->only(['email', 'password']);
        $credentials['email'] = $this->normalizeEmailAddress($credentials['email'] ?? '');

        if (Auth::guard('super_admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            Auth::shouldUse('super_admin');

            $superAdmin = Auth::guard('super_admin')->user();
            if ($superAdmin) {
                app(AuditLogService::class)->log(
                    (string) $superAdmin->id,
                    'auth.super_admin.login',
                    "Super admin signed in ({$superAdmin->email}).",
                    $request
                );
            }

            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'You are logged in as Super Admin.');
        }

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            Auth::shouldUse('admin');

            $admin = Auth::guard('admin')->user();
            if ($admin) {
                app(AuditLogService::class)->log(
                    (string) $admin->id,
                    'auth.admin.login',
                    "Admin signed in ({$admin->email}).",
                    $request
                );
            }

            return redirect()
                ->route($admin?->defaultRouteName() ?? 'admin.dashboard')
                ->with('success', 'You are logged in as '.($admin?->roleLabel() ?? 'Administrator').'.');
        }

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            Auth::shouldUse('web');

            $user = Auth::guard('web')->user();
            if ($user) {
                app(AuditLogService::class)->log(
                    (string) $user->id,
                    'auth.alumni.login',
                    "Alumni signed in ({$user->email}).",
                    $request
                );
            }

            return redirect()
                ->route('dashboard')
                ->with('success', 'You are logged in to your alumni account.')
                ->with('show_latest_notification_preview', true);
        }

        RateLimiter::hit($throttleKey, 60);

        app(AuditLogService::class)->log(
            null,
            'auth.login.failed',
            'Failed login attempt for '.$request->string('email')->toString().'.',
            $request
        );

        return back()
            ->withErrors(['email' => 'Invalid credentials'])
            ->withInput($request->only('email', 'remember'));
    }

    public function showSignup()
    {
        return view('auth.registration'); // optional signup page
    }

    public function checkEmail(Request $request)
    {
        $email = trim((string) $request->query('email', ''));

        if ($email === '') {
            return response()->json([
                'available' => false,
                'valid_format' => false,
                'message' => 'Email is required.',
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'available' => false,
                'valid_format' => false,
                'message' => 'Please enter a valid email format.',
            ]);
        }

        $exists = $this->emailExists($email);

        return response()->json([
            'available' => !$exists,
            'valid_format' => true,
            'message' => $exists
                ? 'This email is already registered.'
                : 'Email is available.',
        ]);
    }

    public function sendRegistrationEmailVerification(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = $this->normalizeEmailAddress($validated['email']);

        if ($this->emailExists($email)) {
            return response()->json([
                'message' => 'This email is already registered.',
                'errors' => [
                    'email' => ['This email is already registered.'],
                ],
            ], 422);
        }

        $existingVerification = $request->session()->get(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);
        $cooldownSeconds = $this->registrationEmailResendCooldownSeconds($request, $email, is_array($existingVerification) ? $existingVerification : null);
        if ($cooldownSeconds > 0) {
            return response()->json([
                'message' => 'Please wait '.$this->formatVerificationDuration($cooldownSeconds).' before requesting another code.',
                'errors' => [
                    'email' => ['Please wait '.$this->formatVerificationDuration($cooldownSeconds).' before requesting another code.'],
                ],
                'resend_after_seconds' => $cooldownSeconds,
                'resend_available_at' => now()->addSeconds($cooldownSeconds)->toIso8601String(),
                'expires_at' => is_array($existingVerification) ? ($existingVerification['expires_at'] ?? null) : null,
            ], 429);
        }

        $throttleKey = 'registration-email-verification|'.$email.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => "Too many verification code requests. Try again in {$seconds} seconds.",
                'errors' => [
                    'email' => ["Too many verification code requests. Try again in {$seconds} seconds."],
                ],
            ], 429);
        }

        RateLimiter::hit($throttleKey, 300);

        $code = (string) random_int(100000, 999999);
        $sentAt = now();
        $expiresAt = $sentAt->copy()->addMinutes(self::REGISTRATION_EMAIL_VERIFICATION_TTL_MINUTES);
        $resendAvailableAt = $sentAt->copy()->addSeconds(self::REGISTRATION_EMAIL_RESEND_COOLDOWN_SECONDS);

        $request->session()->forget(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);
        $request->session()->put(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY, [
            'email' => $email,
            'code_hash' => Hash::make($code),
            'sent_at' => $sentAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'verified_at' => null,
        ]);

        try {
            Notification::route('mail', $email)->notify(
                new RegistrationEmailVerificationCode($code, self::REGISTRATION_EMAIL_VERIFICATION_TTL_MINUTES)
            );
        } catch (Throwable $exception) {
            report($exception);
            $request->session()->forget(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);

            $message = Str::contains($exception->getMessage(), 'Daily user sending limit exceeded')
                ? 'The Gmail sender account has reached its daily sending limit. Please try again later or use another mail sender account.'
                : 'We could not send the verification code right now. Please check the mail settings and try again.';

            return response()->json([
                'message' => $message,
                'errors' => [
                    'email' => [$message],
                ],
            ], 500);
        }

        return response()->json([
            'message' => 'Verification code sent. Please check your email.',
            'expires_in_minutes' => self::REGISTRATION_EMAIL_VERIFICATION_TTL_MINUTES,
            'expires_in_seconds' => self::REGISTRATION_EMAIL_VERIFICATION_TTL_MINUTES * 60,
            'expires_at' => $expiresAt->toIso8601String(),
            'resend_after_seconds' => self::REGISTRATION_EMAIL_RESEND_COOLDOWN_SECONDS,
            'resend_available_at' => $resendAvailableAt->toIso8601String(),
        ]);
    }

    public function verifyRegistrationEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);

        $email = $this->normalizeEmailAddress($validated['email']);
        $code = (string) $validated['code'];
        $payload = $request->session()->get(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);

        if (!is_array($payload) || !hash_equals((string) ($payload['email'] ?? ''), $email)) {
            return response()->json([
                'message' => 'Please request a verification code for this email first.',
                'errors' => [
                    'email' => ['Please request a verification code for this email first.'],
                ],
            ], 422);
        }

        if ($this->registrationEmailVerificationExpired($request, $payload)) {
            return response()->json([
                'message' => 'This verification code has expired. Please request a new one.',
                'errors' => [
                    'code' => ['This verification code has expired. Please request a new one.'],
                ],
            ], 422);
        }

        if (!Hash::check($code, (string) ($payload['code_hash'] ?? ''))) {
            $message = 'That code does not match the latest code sent to your email. Check your inbox or request a new code.';

            return response()->json([
                'message' => $message,
                'errors' => [
                    'code' => [$message],
                ],
            ], 422);
        }

        $payload['verified_at'] = now()->toIso8601String();
        $request->session()->put(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY, $payload);

        return response()->json([
            'message' => 'Email verified.',
        ]);
    }

    public function checkExistingAlumniAccount(Request $request)
    {
        $firstname = SystemOptions::clean((string) $request->query('firstname', ''));
        $lastname = SystemOptions::clean((string) $request->query('lastname', ''));
        $yearGraduated = $request->query('yearGraduated');
        $studentIdnum = SystemOptions::clean((string) $request->query('studentIdnum', ''));
        $birthdate = $this->normalizeDateString($request->query('birthdate'));

        if ($firstname === null || $lastname === null || !is_numeric((string) $yearGraduated)) {
            return response()->json([
                'exists' => false,
                'message' => null,
            ]);
        }

        $existingAlumniAccount = $this->findExistingAlumniAccount(
            $firstname,
            $lastname,
            $yearGraduated,
            $studentIdnum,
            $birthdate
        );

        return response()->json([
            'exists' => $existingAlumniAccount !== null,
            'message' => $existingAlumniAccount !== null
                ? $this->existingAlumniAccountMessage($existingAlumniAccount)
                : null,
        ]);
    }

    public function verifyAlumniRecord(
        VerifyAlumniRecordRequest $request,
        AlumniRecordVerificationService $verificationService
    )
    {
        $matchedRecord = $verificationService->verify($request->validated());

        if ($matchedRecord === null) {
            $request->session()->forget(self::REGISTRATION_VERIFICATION_SESSION_KEY);

            return response()->json([
                'message' => 'No matching CMU alumni record was found. Please review your name, birthdate, college, and graduation year.',
                'errors' => [
                    'firstname' => ['No matching CMU alumni record was found. Please review your name, birthdate, college, and graduation year.'],
                ],
            ], 422);
        }

        $existingAlumniAccount = $this->findExistingAlumniAccount(
            $matchedRecord['first_name'] ?? $request->string('firstname')->toString(),
            $matchedRecord['last_name'] ?? $request->string('lastname')->toString(),
            $matchedRecord['year_graduated'] ?? $request->input('yearGraduated'),
            $matchedRecord['student_idnum'] ?? $matchedRecord['student_number'] ?? $request->input('studentIdnum'),
            $matchedRecord['birthdate'] ?? $matchedRecord['birth_date'] ?? $matchedRecord['birthday'] ?? $request->input('birthdate'),
        );

        if ($existingAlumniAccount !== null) {
            $request->session()->forget(self::REGISTRATION_VERIFICATION_SESSION_KEY);

            return response()->json([
                'message' => $this->existingAlumniAccountMessage($existingAlumniAccount),
                'errors' => [
                    'firstname' => [$this->existingAlumniAccountMessage($existingAlumniAccount)],
                ],
            ], 422);
        }

        $resolvedCollegeGraduated = $verificationService->resolvedCollegeGraduated($matchedRecord);
        $resolvedDegreeGraduated = $verificationService->resolvedDegreeGraduated($matchedRecord);
        $verificationRecord = array_merge($matchedRecord, [
            'college_graduated' => $resolvedCollegeGraduated ?? ($matchedRecord['college_graduated'] ?? $matchedRecord['collegeGraduated'] ?? null),
            'degree_graduated' => $resolvedDegreeGraduated ?? ($matchedRecord['degree_graduated'] ?? $matchedRecord['degreeGraduated'] ?? null),
        ]);

        $verificationPayload = [
            'firstname' => $matchedRecord['first_name'] ?? $request->string('firstname')->toString(),
            'middlename' => $matchedRecord['middle_name'] ?? $request->input('middlename'),
            'lastname' => $matchedRecord['last_name'] ?? $request->string('lastname')->toString(),
            'student_idnum' => SystemOptions::clean($matchedRecord['student_idnum'] ?? $matchedRecord['student_number'] ?? $request->input('studentIdnum')),
            'student_number' => SystemOptions::clean($matchedRecord['student_number'] ?? $matchedRecord['student_idnum'] ?? $request->input('studentIdnum')),
            'birthdate' => $this->normalizeDateString($matchedRecord['birthdate'] ?? $matchedRecord['birth_date'] ?? $matchedRecord['birthday'] ?? $request->input('birthdate')),
            'college_graduated' => $verificationRecord['college_graduated'] ?? $request->string('collegeGraduated')->toString(),
            'degree_graduated' => $request->filled('degreeGraduated')
                ? ($verificationRecord['degree_graduated'] ?? $request->string('degreeGraduated')->toString())
                : ($verificationRecord['degree_graduated'] ?? null),
            'year_graduated' => $matchedRecord['year_graduated'] ?? $request->input('yearGraduated'),
            'verified_at' => now()->toIso8601String(),
            'record' => $verificationRecord,
        ];

        $request->session()->put(self::REGISTRATION_VERIFICATION_SESSION_KEY, $verificationPayload);

        return response()->json([
            'message' => 'CMU alumni record verified. You can continue with account registration.',
            'verification' => [
                'firstname' => $verificationPayload['firstname'],
                'middlename' => $verificationPayload['middlename'],
                'lastname' => $verificationPayload['lastname'],
                'studentIdnum' => $verificationPayload['student_idnum'],
                'birthdate' => $verificationPayload['birthdate'],
                'collegeGraduated' => $verificationPayload['college_graduated'],
                'degreeGraduated' => $verificationPayload['degree_graduated'],
                'yearGraduated' => $verificationPayload['year_graduated'],
            ],
        ]);
    }

    public function register(RegisterRequest $request, AlumniRecordVerificationService $verificationService)
    {
        $verifiedAlumni = $this->verifiedAlumniFromSession($request);
        if ($verifiedAlumni === null) {
            return $this->registrationError(
                $request,
                'firstname',
                'Please verify your alumni record against the CMU records before completing registration.'
            );
        }

        if (!$verificationService->matchesRecord($request->validated(), $verifiedAlumni)) {
            return $this->registrationError(
                $request,
                'firstname',
                'Your verification details do not match the verified CMU alumni record anymore. Please verify again.'
            );
        }

        $verifiedEmail = $this->normalizeEmailAddress($request->string('email')->toString());
        if (!$this->registrationEmailVerifiedFor($request, $verifiedEmail)) {
            return $this->registrationError(
                $request,
                'email',
                'Please verify your email address before completing registration.'
            );
        }

        $authoritativeVerifiedAlumni = $this->authoritativeVerifiedAlumni($verifiedAlumni);
        $hasUndergraduateDegree = $request->input('hasUndergraduateDegree') === 'Yes';
        $submittedDepartment = $hasUndergraduateDegree
            ? ($request->input('department') ?: $request->input('collegeGraduated'))
            : $request->input('collegeGraduated');
        if (
            $submittedDepartment !== null
            && $submittedDepartment !== ''
            && !$this->stringsMatch($submittedDepartment, $authoritativeVerifiedAlumni['college_graduated'] ?? null)
        ) {
            return $this->registrationError(
                $request,
                'department',
                'The selected college does not match the verified CMU alumni record.'
            );
        }

        $department = (string) ($authoritativeVerifiedAlumni['college_graduated'] ?? $submittedDepartment ?? '');
        if ($department === 'Other') {
            $department = (string) $request->departmentOther;
        }
        $department = SystemOptions::canonical($department, SystemOptions::departments());
        $degree = null;
        if ($hasUndergraduateDegree) {
            $degree = (string) ($authoritativeVerifiedAlumni['degree_graduated'] ?? $request->degree);
            if ($degree === 'Other') {
                $degree = (string) $request->degreeOther;
            }
        }
        $degree = SystemOptions::clean($degree);

        $hasScholarship = $request->input('hasScholarship') === 'Yes';
        $scholarshipName = null;
        $scholarshipYearAwarded = null;
        $scholarshipHelpedGetJob = null;

        if ($hasScholarship) {
            $scholarshipName = (string) $request->input('scholarshipName', '');
            if ($scholarshipName === 'Other') {
                $scholarshipName = (string) $request->input('scholarshipOther', '');
            }

            $scholarshipName = SystemOptions::clean($scholarshipName);
            $scholarshipYearAwarded = $request->filled('scholarshipYear')
                ? (int) $request->input('scholarshipYear')
                : null;
            $scholarshipHelpedGetJob = match ($request->input('scholarshipHelpedJob')) {
                'Yes' => true,
                'No' => false,
                default => null,
            };
        }

        $country = (string) $request->country;
        if ($country === 'Other') {
            $country = (string) $request->countryOther;
        }
        $locationParts = array_filter([
            trim((string) $request->municipalityCity),
            trim((string) $request->regionOfOrigin),
            trim((string) $country),
        ], fn ($value) => $value !== '');
        $location = !empty($locationParts) ? implode(', ', $locationParts) : null;

        $curriculumRelevant = match ($request->input('curriculumRelevant')) {
            'Yes' => true,
            'No' => false,
            default => null,
        };
        $firstJobCompetencies = $curriculumRelevant
            ? $this->normalizedSelections((array) $request->input('firstJobCompetencies', []))
            : [];
        $curriculumNotRelevantReason = $curriculumRelevant === false
            ? SystemOptions::clean((string) $request->input('curriculumNotRelevantReason', ''))
            : null;
        $curriculumNotRelevantReasonOther = $curriculumNotRelevantReason === 'Other'
            ? SystemOptions::clean((string) $request->input('curriculumNotRelevantReasonOther', ''))
            : null;

        $employedAfterGraduation = $request->input('employedAfterGrad') === 'Yes';
        $shareMoreFirstJobDetails = $employedAfterGraduation
            && (
                $request->input('shareMoreFirstJobDetails') === 'Yes'
                || $request->filled('firstJobHow')
                || $request->filled('firstJobEarnings')
                || $request->filled('firstJobDuration')
            );
        $timeToFirstJob = $employedAfterGraduation ? SystemOptions::clean((string) $request->input('firstJobTime', '')) : null;
        $timeToFirstJobOther = $timeToFirstJob === 'Other'
            ? SystemOptions::clean((string) $request->input('firstJobTimeOther', ''))
            : null;
        $firstJobRelated = $employedAfterGraduation
            ? SystemOptions::clean((string) $request->input('firstJobRelated', ''))
            : null;
        $firstJobFindingMethod = $shareMoreFirstJobDetails
            ? SystemOptions::clean((string) $request->input('firstJobHow', ''))
            : null;
        $firstJobFindingMethodOther = $firstJobFindingMethod === 'Other'
            ? SystemOptions::clean((string) $request->input('firstJobHowOther', ''))
            : null;
        $firstJobMonthlyEarnings = $shareMoreFirstJobDetails
            ? SystemOptions::firstJobEarningsLabel((string) $request->input('firstJobEarnings', ''))
            : null;
        $firstJobDuration = $shareMoreFirstJobDetails
            ? SystemOptions::clean((string) $request->input('firstJobDuration', ''))
            : null;
        $firstJobDurationOther = $firstJobDuration === 'Other'
            ? SystemOptions::clean((string) $request->input('firstJobDurationOther', ''))
            : null;

        $presentlyEmployed = (string) $request->input('presentlyEmployed', 'No');
        $hasEmploymentDetails = $presentlyEmployed === 'Yes';
        $shareMoreEmploymentDetails = $hasEmploymentDetails
            && (
                $request->input('shareMoreEmploymentDetails') === 'Yes'
                || $request->filled('directSupervisor')
                || $request->filled('employerEmail')
                || $request->filled('jobLevel')
                || $request->filled('firstJobIsCurrent')
                || !empty(array_filter((array) $request->input('presentJobReasons', [])))
                || $request->filled('hasEmployeeAwards')
                || $request->filled('employeeAwards')
            );
        $presentEmploymentStatus = $hasEmploymentDetails
            ? SystemOptions::clean((string) $request->input('presentEmploymentStatus', ''))
            : null;
        $currentEmploymentStatus = match (true) {
            !$hasEmploymentDetails => 'Unemployed',
            mb_strtolower((string) $presentEmploymentStatus) === 'self-employed' => 'Self-Employed',
            default => 'Employed',
        };

        $industry = $hasEmploymentDetails
            ? (string) ($request->input('businessLine') === 'Other'
                ? $request->input('businessLineOther')
                : $request->input('businessLine'))
            : null;
        $industry = SystemOptions::canonical($industry, SystemOptions::businessLines());
        $currentPosition = $hasEmploymentDetails ? (string) $request->input('presentOccupation', '') : null;
        $company = $hasEmploymentDetails ? (string) $request->input('presentCompany', '') : null;
        $salaryRange = $hasEmploymentDetails ? SystemOptions::presentJobEarningsLabel((string) $request->input('presentJobEarnings', '')) : null;
        $directSupervisorName = $shareMoreEmploymentDetails ? SystemOptions::clean((string) $request->input('directSupervisor', '')) : null;
        $employerEmail = $shareMoreEmploymentDetails ? SystemOptions::clean((string) $request->input('employerEmail', '')) : null;
        $isFirstJobAfterCollege = $shareMoreEmploymentDetails ? $this->booleanFromYesNo($request->input('firstJobIsCurrent')) : null;
        $employmentProofData = $hasEmploymentDetails && $request->hasFile('employmentProof')
            ? EmploymentProofStorage::storeUploadedFile($request->file('employmentProof'))
            : EmploymentProofStorage::emptyPayload();
        $employmentProofUploaded = $hasEmploymentDetails && $request->hasFile('employmentProof');
        $employmentProofWarning = null;
        if ($hasEmploymentDetails && ! $employmentProofUploaded) {
            $employmentProofWarning = 'Please upload your employment proof PDF within 7 days. Your account will be marked unemployed if no proof is uploaded by then.';
        } elseif ($employmentProofUploaded) {
            $employmentProofWarning = 'Your employment proof PDF is pending admin review. Your account will display as employed after approval.';
        }
        $jobLevel = $shareMoreEmploymentDetails ? SystemOptions::clean((string) $request->input('jobLevel', '')) : null;
        $jobLevelOther = $jobLevel === 'Other' ? SystemOptions::clean((string) $request->input('jobLevelOther', '')) : null;
        $presentJobReasons = $shareMoreEmploymentDetails
            ? $this->normalizedSelections((array) $request->input('presentJobReasons', []))
            : [];
        $presentJobReasonsOther = $shareMoreEmploymentDetails && in_array('Other', $presentJobReasons, true)
            ? SystemOptions::clean((string) $request->input('presentJobReasonsOther', ''))
            : null;
        $hasEmployeeAwards = $shareMoreEmploymentDetails ? $this->booleanFromYesNo($request->input('hasEmployeeAwards')) : null;
        $employeeAwards = $hasEmployeeAwards ? SystemOptions::clean($request->input('employeeAwards')) : null;
        $teachingFeedbackSummary = $request->filled('teachingFeedbackSummary')
            ? (int) $request->input('teachingFeedbackSummary')
            : null;
        $overallExperienceSummary = $request->filled('overallExperienceSummary')
            ? (int) $request->input('overallExperienceSummary')
            : null;
        $teachingFacultyEnthusiastic = $teachingFeedbackSummary ?? ($request->filled('teachingFacultyEnthusiastic') ? (int) $request->input('teachingFacultyEnthusiastic') : null);
        $teachingConceptsClear = $teachingFeedbackSummary ?? ($request->filled('teachingConceptsClear') ? (int) $request->input('teachingConceptsClear') : null);
        $teachingKnowledgeable = $teachingFeedbackSummary ?? ($request->filled('teachingKnowledgeable') ? (int) $request->input('teachingKnowledgeable') : null);
        $teachingCommitment = $teachingFeedbackSummary ?? ($request->filled('teachingCommitment') ? (int) $request->input('teachingCommitment') : null);
        $degreeProgramQuality = $teachingFeedbackSummary ?? ($request->filled('degreeProgramQuality') ? (int) $request->input('degreeProgramQuality') : null);
        $overallRecommendInstitution = $overallExperienceSummary ?? ($request->filled('overallRecommendInstitution') ? (int) $request->input('overallRecommendInstitution') : null);
        $overallObjectivesMet = $overallExperienceSummary ?? ($request->filled('overallObjectivesMet') ? (int) $request->input('overallObjectivesMet') : null);
        $overallUniversityExperience = $overallExperienceSummary ?? ($request->filled('overallUniversityExperience') ? (int) $request->input('overallUniversityExperience') : null);
        $curriculumSuggestions = SystemOptions::clean($request->input('curriculumSuggestions'));
        $hasCurriculumSuggestions = filled($curriculumSuggestions);
        $employabilitySuggestions = SystemOptions::clean($request->input('employabilitySuggestions'));
        $hasEmployabilitySuggestions = filled($employabilitySuggestions);
        $facilitySuggestions = SystemOptions::clean($request->input('facilitySuggestions'));
        $hasFacilitySuggestions = filled($facilitySuggestions);
        $facilitiesSatisfaction = SystemOptions::clean((string) $request->input('facilitiesSatisfaction', ''));

        $normalizedFirstname = $authoritativeVerifiedAlumni['firstname'] ?? SystemOptions::clean($request->firstname);
        $normalizedMiddlename = $authoritativeVerifiedAlumni['middlename'] ?? SystemOptions::clean($request->middlename);
        $normalizedLastname = $authoritativeVerifiedAlumni['lastname'] ?? SystemOptions::clean($request->lastname);
        $normalizedYearGraduated = $authoritativeVerifiedAlumni['year_graduated'] ?? $this->normalizeGraduationYear($request->yearGraduated);
        $normalizedSex = SystemOptions::clean($authoritativeVerifiedAlumni['sex'] ?? $request->input('sex'));
        $normalizedAlumniIdnum = SystemOptions::clean($authoritativeVerifiedAlumni['alumni_idnum'] ?? null);
        $normalizedStudentIdnum = SystemOptions::clean($authoritativeVerifiedAlumni['student_idnum'] ?? $authoritativeVerifiedAlumni['student_number'] ?? null);
        $normalizedBirthdate = $this->normalizeDateString($authoritativeVerifiedAlumni['birthdate'] ?? $request->input('birthdate'));
        $normalizedCourse = SystemOptions::clean($authoritativeVerifiedAlumni['course'] ?? $authoritativeVerifiedAlumni['college_graduated'] ?? null);
        $normalizedMajor = SystemOptions::clean($authoritativeVerifiedAlumni['major'] ?? $authoritativeVerifiedAlumni['degree_graduated'] ?? null);
        $normalizedDtGrad = SystemOptions::clean($authoritativeVerifiedAlumni['dt_grad'] ?? $authoritativeVerifiedAlumni['date_graduated'] ?? null);

        $existingAlumniAccount = $this->findExistingAlumniAccount(
            $authoritativeVerifiedAlumni['firstname'] ?? $normalizedFirstname,
            $authoritativeVerifiedAlumni['lastname'] ?? $normalizedLastname,
            $authoritativeVerifiedAlumni['year_graduated'] ?? $normalizedYearGraduated,
            $normalizedStudentIdnum,
            $normalizedBirthdate,
        );

        if ($existingAlumniAccount !== null) {
            return $this->registrationError(
                $request,
                'email',
                $this->existingAlumniAccountMessage($existingAlumniAccount)
            );
        }

        $claimableBatchAccount = $this->findClaimableBatchImportedAlumniAccount($authoritativeVerifiedAlumni);
        $newsletterOptIn = $request->has('newsletter_opt_in')
            ? $request->boolean('newsletter_opt_in')
            : true;

        // Create or claim the imported placeholder with alumni data.
        $userPayload = [
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // Personal Information
            'firstname' => $normalizedFirstname,
            'middlename' => $normalizedMiddlename,
            'lastname' => $normalizedLastname,
            'birthdate' => $normalizedBirthdate,
            'phone' => $request->phone,
            'sex' => $normalizedSex,
            'civil_status' => SystemOptions::clean($request->input('civilstatus')),
            // Education
            'department' => $department ?: null,
            'degree' => $degree ?: null,
            'course' => $normalizedCourse,
            'major' => $normalizedMajor,
            'dt_grad' => $normalizedDtGrad,
            'year_graduated' => $normalizedYearGraduated,
            'alumni_idnum' => $normalizedAlumniIdnum,
            'student_idnum' => $normalizedStudentIdnum,
            'employed_after_grad' => $hasEmploymentDetails ? null : 'No',
            // Additional
            'skills' => $request->skills,
            'achievements' => $request->achievements,
            'industry' => null,
            'current_position' => null,
            'company' => null,
            'location' => $location,
            'salary_range' => null,
            'role' => User::ROLE_ALUMNI,
            'is_admin' => false,
            'is_batch_imported' => false,
            'has_scholarship' => $hasScholarship,
            'scholarship_name' => $scholarshipName,
            'scholarship_year_awarded' => $scholarshipYearAwarded,
            'scholarship_helped_get_job' => $scholarshipHelpedGetJob,
            'data_privacy_consent' => true,
            'data_privacy_consented_at' => now(),
            'newsletter_opt_in' => $newsletterOptIn,
        ];

        $userPayload = array_intersect_key($userPayload, array_flip(Schema::getColumnListing('users')));

        if ($claimableBatchAccount !== null) {
            $claimableBatchAccount->fill($userPayload);
            $claimableBatchAccount->save();
            $user = $claimableBatchAccount->fresh() ?? $claimableBatchAccount;
        } else {
            $user = User::create($userPayload);
        }

        if (Schema::hasTable('tracer_records')) {
            $isEmployed = $currentEmploymentStatus === 'Unemployed' ? false : true;
            $employmentType = $currentEmploymentStatus;

            $tracerRecord = TracerRecord::query()->create([
                'user_id' => $user->id,
                'tracer_date' => now()->toDateString(),
                'is_employed' => $isEmployed,
                'employment_type' => $employmentType,
                'industry' => $industry,
                'current_position' => SystemOptions::clean($currentPosition),
                'current_company' => SystemOptions::clean($company),
                'curriculum_relevant_to_first_job' => $curriculumRelevant,
                'first_job_useful_competencies' => $firstJobCompetencies,
                'first_job_useful_competencies_other' => in_array('Other', $firstJobCompetencies, true)
                    ? SystemOptions::clean((string) $request->input('firstJobCompetenciesOther', ''))
                    : null,
                'curriculum_not_relevant_reason' => $curriculumNotRelevantReason,
                'curriculum_not_relevant_reason_other' => $curriculumNotRelevantReasonOther,
                'employed_after_graduation' => $employedAfterGraduation,
                'time_to_first_job' => $timeToFirstJob,
                'time_to_first_job_other' => $timeToFirstJobOther,
                'first_job_related_to_course' => $firstJobRelated,
                'first_job_finding_method' => $firstJobFindingMethod,
                'first_job_finding_method_other' => $firstJobFindingMethodOther,
                'first_job_monthly_earnings' => $firstJobMonthlyEarnings,
                'first_job_duration' => $firstJobDuration,
                'first_job_duration_other' => $firstJobDurationOther,
                'presently_employed' => $presentlyEmployed,
                'present_employment_status' => $presentEmploymentStatus,
                'direct_supervisor_name' => $directSupervisorName,
                'employer_email' => $employerEmail,
                'is_first_job_after_college' => $isFirstJobAfterCollege,
                'employment_proof_path' => $employmentProofData['employment_proof_path'],
                'employment_proof_disk' => $employmentProofData['employment_proof_disk'],
                'employment_proof_original_name' => $employmentProofData['employment_proof_original_name'],
                'employment_proof_mime_type' => $employmentProofData['employment_proof_mime_type'],
                'employment_proof_size_bytes' => $employmentProofData['employment_proof_size_bytes'],
                'employment_proof_uploaded_at' => $employmentProofData['employment_proof_uploaded_at'],
                'employment_proof_status' => $employmentProofData['employment_proof_status'],
                'employment_proof_reviewed_by_guard' => $employmentProofData['employment_proof_reviewed_by_guard'],
                'employment_proof_reviewed_by_id' => $employmentProofData['employment_proof_reviewed_by_id'],
                'employment_proof_reviewed_at' => $employmentProofData['employment_proof_reviewed_at'],
                'employment_proof_rejection_note' => $employmentProofData['employment_proof_rejection_note'],
                'job_level' => $jobLevel,
                'job_level_other' => $jobLevelOther,
                'present_job_acceptance_reasons' => $presentJobReasons,
                'present_job_acceptance_other' => $presentJobReasonsOther,
                'present_job_monthly_earnings' => $salaryRange,
                'has_employee_awards' => $hasEmployeeAwards,
                'employee_awards' => $employeeAwards,
                'teaching_faculty_enthusiastic' => $teachingFacultyEnthusiastic,
                'teaching_concepts_clear' => $teachingConceptsClear,
                'teaching_faculty_knowledgeable' => $teachingKnowledgeable,
                'teaching_commitment' => $teachingCommitment,
                'degree_program_quality' => $degreeProgramQuality,
                'has_curriculum_suggestions' => $hasCurriculumSuggestions,
                'curriculum_suggestions' => $curriculumSuggestions,
                'has_employability_suggestions' => $hasEmployabilitySuggestions,
                'employability_suggestions' => $employabilitySuggestions,
                'facilities_satisfaction' => $facilitiesSatisfaction,
                'has_facility_suggestions' => $hasFacilitySuggestions,
                'facility_suggestions' => $facilitySuggestions,
                'overall_recommend_institution' => $overallRecommendInstitution,
                'overall_objectives_met' => $overallObjectivesMet,
                'overall_university_experience' => $overallUniversityExperience,
            ]);

            if ($employmentProofUploaded) {
                app(EmploymentProofReviewNotificationService::class)->notifyPendingReview(
                    $user,
                    $tracerRecord->fresh() ?? $tracerRecord
                );
            }
        }

        $request->session()->forget([
            self::REGISTRATION_VERIFICATION_SESSION_KEY,
            self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY,
        ]);

        app(AuditLogService::class)->log(
            (string) $user->id,
            'auth.alumni.register',
            "Registered alumni account for {$user->email} ({$user->id}).",
            $request
        );

        if ($request->ajax()) {
            $request->session()->flash('success', 'Registration successful. Please log in using your new account.');
            if ($employmentProofWarning !== null) {
                $request->session()->flash('warning', $employmentProofWarning);
            }

            return response()->json([
                'message' => 'Registration successful. Please log in using your new account.',
                'warning' => $employmentProofWarning,
                'redirect' => route('login', ['registered' => 1]),
            ]);
        }

        $redirect = redirect()
            ->route('login', ['registered' => 1])
            ->with('success', 'Registration successful. Please log in using your new account.');

        if ($employmentProofWarning !== null) {
            $redirect->with('warning', $employmentProofWarning);
        }

        return $redirect;
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(SendResetLinkRequest $request)
    {
        try {
            $status = Password::broker($this->passwordBrokerForEmail($request->string('email')->toString()))
                ->sendResetLink([
                'email' => $request->string('email')->toString(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $message = Str::contains($exception->getMessage(), 'Daily user sending limit exceeded')
                ? 'The password reset link could not be sent because the Gmail sender account has reached its daily sending limit. Please try again later.'
                : 'We could not send the password reset link right now. Please check the mail settings and try again.';

            return back()
                ->withInput($request->only('email'))
                ->with('error', $message);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->with('warning', 'A password reset link was requested recently. Please wait at least one minute before requesting another link.');
        }

        if (! in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            report(new \RuntimeException("Password reset link request failed with status [{$status}]."));

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'We could not send the password reset link right now. Please try again later.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'If an account with that email exists, a secure password reset link has been sent. Please check your inbox.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::broker($this->passwordBrokerForEmail($request->string('email')->toString()))
            ->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($account) use ($request) {
                $account->forceFill([
                    'password' => Hash::make($request->input('password')),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($account));

                $isSuperAdmin = $account instanceof SuperAdmin;
                $isAdmin = $account instanceof Admin || $isSuperAdmin;

                app(AuditLogService::class)->log(
                    (string) $account->id,
                    'auth.password.reset',
                    'Reset password for '.($isSuperAdmin ? 'super admin' : ($isAdmin ? 'admin' : 'alumni'))." account {$account->email} ({$account->id}).",
                    $request
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset successfully.');
    }

    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->string('email')->toString()) . '|' . $request->ip();
    }

    protected function verifiedAlumniFromSession(Request $request): ?array
    {
        $payload = $request->session()->get(self::REGISTRATION_VERIFICATION_SESSION_KEY);

        if (!is_array($payload)) {
            return null;
        }

        $verifiedAt = $payload['verified_at'] ?? null;
        if (!is_string($verifiedAt) || trim($verifiedAt) === '') {
            $request->session()->forget(self::REGISTRATION_VERIFICATION_SESSION_KEY);

            return null;
        }

        try {
            $verifiedAtTime = Carbon::parse($verifiedAt);
        } catch (\Throwable $exception) {
            $request->session()->forget(self::REGISTRATION_VERIFICATION_SESSION_KEY);

            return null;
        }

        if ($verifiedAtTime->lt(now()->subMinutes(self::REGISTRATION_VERIFICATION_TTL_MINUTES))) {
            $request->session()->forget(self::REGISTRATION_VERIFICATION_SESSION_KEY);

            return null;
        }

        return $payload;
    }

    protected function registrationEmailVerifiedFor(Request $request, string $email): bool
    {
        $payload = $request->session()->get(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);

        if (!is_array($payload)) {
            return false;
        }

        if (!hash_equals((string) ($payload['email'] ?? ''), $this->normalizeEmailAddress($email))) {
            return false;
        }

        if (!is_string($payload['verified_at'] ?? null) || trim((string) $payload['verified_at']) === '') {
            return false;
        }

        return !$this->registrationEmailVerificationExpired($request, $payload);
    }

    protected function registrationEmailVerificationExpired(Request $request, array $payload): bool
    {
        $expiresAtValue = $payload['expires_at'] ?? null;
        if (!is_string($expiresAtValue) || trim($expiresAtValue) === '') {
            $request->session()->forget(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);

            return true;
        }

        try {
            $expiresAt = Carbon::parse($expiresAtValue);
        } catch (\Throwable $exception) {
            $request->session()->forget(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);

            return true;
        }

        if ($expiresAt->isPast()) {
            $request->session()->forget(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);

            return true;
        }

        return false;
    }

    protected function registrationEmailResendCooldownSeconds(Request $request, string $email, ?array $payload = null): int
    {
        $payload ??= $request->session()->get(self::REGISTRATION_EMAIL_VERIFICATION_SESSION_KEY);

        if (!is_array($payload) || !hash_equals((string) ($payload['email'] ?? ''), $this->normalizeEmailAddress($email))) {
            return 0;
        }

        $sentAtValue = $payload['sent_at'] ?? null;
        if (!is_string($sentAtValue) || trim($sentAtValue) === '') {
            return 0;
        }

        try {
            $resendAvailableAt = Carbon::parse($sentAtValue)->addSeconds(self::REGISTRATION_EMAIL_RESEND_COOLDOWN_SECONDS);
        } catch (\Throwable $exception) {
            return 0;
        }

        return max(0, $resendAvailableAt->timestamp - now()->timestamp);
    }

    protected function formatVerificationDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes <= 0) {
            return $remainingSeconds.' second'.($remainingSeconds === 1 ? '' : 's');
        }

        return $minutes.' minute'.($minutes === 1 ? '' : 's')
            .($remainingSeconds > 0 ? ' and '.$remainingSeconds.' second'.($remainingSeconds === 1 ? '' : 's') : '');
    }

    protected function normalizeEmailAddress(?string $email): string
    {
        return Str::lower(trim((string) $email));
    }

    protected function emailExists(?string $email): bool
    {
        $email = $this->normalizeEmailAddress($email);

        return User::whereRaw('LOWER(email) = ?', [$email])->exists()
            || Admin::whereRaw('LOWER(email) = ?', [$email])->exists()
            || SuperAdmin::whereRaw('LOWER(email) = ?', [$email])->exists();
    }

    protected function passwordBrokerForEmail(?string $email): string
    {
        $email = $this->normalizeEmailAddress($email);

        if (SuperAdmin::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return 'super_admins';
        }

        return Admin::whereRaw('LOWER(email) = ?', [$email])->exists() ? 'admins' : 'users';
    }

    protected function authoritativeVerifiedAlumni(array $verifiedAlumni): array
    {
        $record = $verifiedAlumni['record'] ?? null;
        $record = is_array($record) ? $record : [];

        return [
            'firstname' => SystemOptions::clean($record['first_name'] ?? $verifiedAlumni['firstname'] ?? null),
            'middlename' => SystemOptions::clean($record['middle_name'] ?? $verifiedAlumni['middlename'] ?? null),
            'lastname' => SystemOptions::clean($record['last_name'] ?? $verifiedAlumni['lastname'] ?? null),
            'college_graduated' => SystemOptions::clean($record['college_graduated'] ?? $verifiedAlumni['college_graduated'] ?? null),
            'degree_graduated' => SystemOptions::clean($record['degree_graduated'] ?? $verifiedAlumni['degree_graduated'] ?? null),
            'year_graduated' => $this->normalizeGraduationYear($record['year_graduated'] ?? $verifiedAlumni['year_graduated'] ?? null),
            'alumni_idnum' => SystemOptions::clean($record['alumni_idnum'] ?? $verifiedAlumni['alumni_idnum'] ?? null),
            'student_idnum' => SystemOptions::clean($record['student_idnum'] ?? $record['student_number'] ?? $verifiedAlumni['student_idnum'] ?? $verifiedAlumni['student_number'] ?? null),
            'student_number' => SystemOptions::clean($record['student_number'] ?? $record['student_idnum'] ?? $verifiedAlumni['student_number'] ?? $verifiedAlumni['student_idnum'] ?? null),
            'birthdate' => $this->normalizeDateString($record['birthdate'] ?? $record['birth_date'] ?? $record['birthday'] ?? $record['date_of_birth'] ?? $verifiedAlumni['birthdate'] ?? null),
            'course' => SystemOptions::clean($record['course'] ?? $record['college_graduated'] ?? $verifiedAlumni['course'] ?? $verifiedAlumni['college_graduated'] ?? null),
            'major' => SystemOptions::clean($record['major'] ?? $record['degree_graduated'] ?? $verifiedAlumni['major'] ?? $verifiedAlumni['degree_graduated'] ?? null),
            'dt_grad' => SystemOptions::clean($record['dt_grad'] ?? $record['date_graduated'] ?? $verifiedAlumni['dt_grad'] ?? $verifiedAlumni['date_graduated'] ?? null),
            'sex' => SystemOptions::clean($record['sex'] ?? $record['gender'] ?? $verifiedAlumni['sex'] ?? $verifiedAlumni['gender'] ?? null),
        ];
    }

    protected function stringsMatch(?string $left, ?string $right): bool
    {
        return mb_strtolower(SystemOptions::clean($left) ?? '') === mb_strtolower(SystemOptions::clean($right) ?? '');
    }

    protected function findExistingAlumniAccount(
        ?string $firstname,
        ?string $lastname,
        mixed $yearGraduated,
        ?string $studentIdnum = null,
        ?string $birthdate = null,
        bool $includeClaimableBatchImports = false
    ): ?User
    {
        $normalizedFirstname = SystemOptions::clean($firstname);
        $normalizedLastname = SystemOptions::clean($lastname);
        $normalizedStudentIdnum = SystemOptions::clean($studentIdnum);
        $normalizedBirthdate = $this->normalizeDateString($birthdate);

        if ($normalizedFirstname === null || $normalizedLastname === null) {
            return null;
        }

        if ($normalizedStudentIdnum !== null && Schema::hasColumn('users', 'student_idnum')) {
            $studentIdMatches = User::query()
                ->alumni()
                ->where('student_idnum', $normalizedStudentIdnum)
                ->orderBy('created_at')
                ->get();

            $studentIdMatch = $includeClaimableBatchImports
                ? $studentIdMatches->first()
                : $studentIdMatches->first(fn (User $user) => ! $this->isClaimableBatchImportedAccount($user));

            if ($studentIdMatch instanceof User) {
                return $studentIdMatch;
            }
        }

        $matches = User::query()
            ->alumni()
            ->whereRaw('LOWER(firstname) = ?', [mb_strtolower($normalizedFirstname)])
            ->whereRaw('LOWER(lastname) = ?', [mb_strtolower($normalizedLastname)])
            ->where('year_graduated', $this->normalizeGraduationYear($yearGraduated))
            ->when(
                $normalizedBirthdate !== null && Schema::hasColumn('users', 'birthdate'),
                fn ($query) => $query->whereDate('birthdate', $normalizedBirthdate)
            )
            ->orderBy('created_at')
            ->get();

        if ($includeClaimableBatchImports) {
            return $matches->first();
        }

        return $matches->first(fn (User $user) => ! $this->isClaimableBatchImportedAccount($user));
    }

    protected function findClaimableBatchImportedAlumniAccount(array $verifiedAlumni): ?User
    {
        $identityColumns = [
            'alumni_idnum' => SystemOptions::clean($verifiedAlumni['alumni_idnum'] ?? null),
            'student_idnum' => SystemOptions::clean($verifiedAlumni['student_idnum'] ?? $verifiedAlumni['student_number'] ?? null),
        ];

        foreach ($identityColumns as $column => $value) {
            if ($value === null || ! Schema::hasColumn('users', $column)) {
                continue;
            }

            $match = User::query()
                ->alumni()
                ->where($column, $value)
                ->orderBy('created_at')
                ->get()
                ->first(fn (User $user) => $this->isClaimableBatchImportedAccount($user));

            if ($match instanceof User) {
                return $match;
            }
        }

        $matches = User::query()
            ->alumni()
            ->whereRaw('LOWER(firstname) = ?', [mb_strtolower((string) ($verifiedAlumni['firstname'] ?? ''))])
            ->whereRaw('LOWER(lastname) = ?', [mb_strtolower((string) ($verifiedAlumni['lastname'] ?? ''))])
            ->where('year_graduated', $this->normalizeGraduationYear($verifiedAlumni['year_graduated'] ?? null))
            ->when(
                $this->normalizeDateString($verifiedAlumni['birthdate'] ?? null) !== null && Schema::hasColumn('users', 'birthdate'),
                fn ($query) => $query->whereDate('birthdate', $this->normalizeDateString($verifiedAlumni['birthdate'] ?? null))
            )
            ->orderBy('created_at')
            ->get();

        return $matches->first(fn (User $user) => $this->isClaimableBatchImportedAccount($user));
    }

    protected function isClaimableBatchImportedAccount(User $user): bool
    {
        if (! Schema::hasColumn('users', 'is_batch_imported')) {
            return false;
        }

        $email = mb_strtolower(trim((string) $user->email));

        return (bool) $user->is_batch_imported
            && Str::endsWith($email, '@oarl-batch.local');
    }

    protected function normalizeGraduationYear(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'N/A') {
            return null;
        }

        return (int) $value;
    }

    protected function normalizeDateString(mixed $value): ?string
    {
        $value = SystemOptions::clean($value === null ? null : (string) $value);

        if ($value === null) {
            return null;
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function existingAlumniAccountMessage(User $user): string
    {
        $maskedEmail = $this->maskEmailForDisplay($user->email);

        return 'This alumni record is already registered with the email "'
            . $maskedEmail
            . '". Please sign in or use password reset if you need access.';
    }

    protected function maskEmailForDisplay(?string $email): string
    {
        $normalizedEmail = trim((string) $email);
        if ($normalizedEmail === '') {
            return 'your existing email address';
        }

        $parts = explode('@', $normalizedEmail, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return $this->maskValuePreservingFirstMiddleLast($normalizedEmail);
        }

        return $this->maskValuePreservingFirstMiddleLast($parts[0]) . '@' . $parts[1];
    }

    protected function maskValuePreservingFirstMiddleLast(string $value): string
    {
        $length = mb_strlen($value);
        if ($length <= 3) {
            return $value;
        }

        $visibleIndexes = [
            0,
            intdiv($length - 1, 2),
            $length - 1,
        ];

        $masked = '';

        for ($index = 0; $index < $length; $index++) {
            $character = mb_substr($value, $index, 1);

            if (in_array($index, $visibleIndexes, true) || !preg_match('/[\pL\pN]/u', $character)) {
                $masked .= $character;
                continue;
            }

            $masked .= '*';
        }

        return $masked;
    }

    protected function registrationError(Request $request, string $field, string $message)
    {
        if ($request->ajax()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
            ], 422);
        }

        return back()
            ->withErrors([$field => $message])
            ->withInput();
    }

    protected function booleanFromYesNo(mixed $value): ?bool
    {
        return match ($value) {
            'Yes', 1, '1', true => true,
            'No', 0, '0', false => false,
            default => null,
        };
    }

    protected function normalizedSelections(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => SystemOptions::clean(is_string($value) ? $value : null))
            ->filter()
            ->unique(fn (?string $value) => mb_strtolower((string) $value))
            ->values()
            ->all();
    }
}

