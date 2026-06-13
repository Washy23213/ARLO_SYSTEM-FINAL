<?php

use App\Http\Controllers\Admin\AdminAlumniController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminAnnouncementController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFeedbackController;
use App\Http\Controllers\Admin\AdminJobController;
use App\Http\Controllers\Alumni\AlumniAnnouncementController;
use App\Http\Controllers\Alumni\AlumniDashboardController;
use App\Http\Controllers\Alumni\AlumniFeedbackController;
use App\Http\Controllers\Alumni\AlumniJobsController;
use App\Http\Controllers\Alumni\AlumniNotificationController;
use App\Http\Controllers\Alumni\AlumniProfileController;
use App\Http\Controllers\Alumni\AlumniSettingsController;
use App\Http\Controllers\Alumni\AlumniTracerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdmin\AdminAccountController as SuperAdminAccountController;
use App\Http\Controllers\SuperAdmin\SuperAdminAuditLogController;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Home / Welcome page
Route::get('/', function () {
    return view('welcome');
});

Route::view('/privacy-policy', 'legal.privacy-policy')->name('privacy-policy');
Route::view('/data-deletion', 'legal.data-deletion')->name('data-deletion');

// ==================== LOGIN ROUTES ====================
// Show login form
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');

// Handle login submission
Route::post('/login', [AuthController::class, 'login'])->name('login.submit')->middleware('guest');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request')->middleware('guest');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('guest');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset')->middleware('guest');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.store')->middleware('guest');

// ==================== REGISTRATION / SIGNUP ROUTE ====================
// Show registration form
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup')->middleware('guest');
Route::get('/signup/check-email', [AuthController::class, 'checkEmail'])->name('signup.check-email')->middleware('guest');
Route::get('/signup/check-existing-alumni', [AuthController::class, 'checkExistingAlumniAccount'])->name('signup.check-existing-alumni')->middleware('guest');
Route::post('/signup/email-verification/send', [AuthController::class, 'sendRegistrationEmailVerification'])->name('signup.email-verification.send')->middleware('guest');
Route::post('/signup/email-verification/verify', [AuthController::class, 'verifyRegistrationEmail'])->name('signup.email-verification.verify')->middleware('guest');
Route::post('/signup/verify-alumni', [AuthController::class, 'verifyAlumniRecord'])->name('signup.verify-alumni')->middleware('guest');
// Handle registration form submission
Route::post('/signup', [AuthController::class, 'register'])->name('register.submit')->middleware('guest');

Route::middleware(['auth', 'no_admin'])->group(function () {
    // ==================== DASHBOARD ROUTE ====================
    Route::get('/dashboard', [AlumniDashboardController::class, 'index'])->name('dashboard');
    Route::view('/help-desk', 'help-desk')->name('help-desk');
    Route::get('/tracer', [AlumniTracerController::class, 'index'])->name('tracer.index');
    Route::post('/tracer', [AlumniTracerController::class, 'store'])->name('tracer.store');
    Route::get('/tracer/{record}/employment-proof', [AlumniTracerController::class, 'downloadEmploymentProof'])->name('tracer.proof.download');

    // ==================== JOBS ROUTE ====================
    Route::get('/jobs', [AlumniJobsController::class, 'jobs'])->name('jobs');
    Route::post('/jobs/submit', [AlumniJobsController::class, 'submitJob'])->name('jobs.submit');

    // ==================== JOB DETAIL ROUTE ====================
    Route::get('/jobs/{id}', [AlumniJobsController::class, 'jobDetail'])->name('job.detail');

    // ==================== ANNOUNCEMENT ROUTES ====================
    Route::get('/announcements', fn () => redirect()->route('notifications.index'))->name('announcements.index');
    Route::get('/announcements/{id}', [AlumniAnnouncementController::class, 'announcementDetail'])->name('announcement.detail');

    // ==================== SETTINGS ROUTE ====================
    Route::get('/settings', [AlumniSettingsController::class, 'settings'])->name('settings');
    Route::post('/settings/profile', [AlumniSettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::post('/tracer/scholarship', [AlumniSettingsController::class, 'updateScholarship'])->name('tracer.scholarship.update');
    Route::post('/settings/password', [AlumniSettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::post('/settings/sessions/logout-other', [AlumniSettingsController::class, 'logoutOtherDevices'])->name('settings.sessions.logout-other');
    Route::post('/settings/delete-account/verify', [AlumniSettingsController::class, 'verifyDeleteAccount'])->name('settings.account.verify-delete');
    Route::post('/settings/delete-account', [AlumniSettingsController::class, 'deleteAccount'])->name('settings.account.delete');

    // ==================== PROFILE ROUTE ====================
    Route::get('/profile', [AlumniProfileController::class, 'profile'])->name('profile');

    Route::get('/feedback', [AlumniFeedbackController::class, 'feedbackForm'])->name('feedback.form');
    Route::post('/feedback', [AlumniFeedbackController::class, 'submitFeedback'])->name('feedback.submit');
    Route::get('/notifications', [AlumniNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/live-summary', [AlumniNotificationController::class, 'liveSummary'])->name('notifications.live-summary');
    Route::get('/notifications/{notification}', [AlumniNotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/{notification}/read', [AlumniNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [AlumniNotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

// ==================== LOGOUT ROUTE ====================
Route::post('/logout', function (Request $request) {
    $superAdmin = Auth::guard('super_admin')->user();
    $admin = Auth::guard('admin')->user();
    $user = Auth::guard('web')->user();

    if ($superAdmin) {
        app(AuditLogService::class)->log(
            (string) $superAdmin->id,
            'auth.super_admin.logout',
            "Super admin signed out ({$superAdmin->email}).",
            $request
        );
    } elseif ($admin) {
        app(AuditLogService::class)->log(
            (string) $admin->id,
            'auth.admin.logout',
            "Admin signed out ({$admin->email}).",
            $request
        );
    } elseif ($user) {
        app(AuditLogService::class)->log(
            (string) $user->id,
            'auth.alumni.logout',
            "Alumni signed out ({$user->email}).",
            $request
        );
    }

    Auth::guard('super_admin')->logout();
    Auth::guard('admin')->logout();
    Auth::guard('web')->logout();

    return redirect()
        ->route('login')
        ->with('logout', 'You are logged out.');
})->name('logout')->middleware('auth:web,admin,super_admin');

// ==================== SUPER ADMIN ROUTES ====================
Route::middleware(['super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::redirect('/dashboard', '/admin/dashboard')->name('dashboard');
        Route::get('/audit-logs', [SuperAdminAuditLogController::class, 'index'])->name('audit-logs');
        Route::resource('admin-accounts', SuperAdminAccountController::class)
            ->parameters(['admin-accounts' => 'adminAccount'])
            ->except(['show']);
    });

// ==================== SHARED ADMIN / SUPER ADMIN ACCOUNT ROUTES ====================
Route::middleware(['admin', 'admin_permission:manage_alumni'])->group(function () {
    Route::get('/admin/alumni-management', [AdminAlumniController::class, 'alumniManagement'])->name('admin.alumni');
    Route::get('/admin/alumni-management/stats', [AdminAlumniController::class, 'alumniStats'])->name('admin.alumni.stats');
    Route::get('/admin/alumni-management/check-duplicates', [AdminAlumniController::class, 'checkDuplicates'])->name('admin.alumni.check-duplicates');
    Route::get('/admin/alumni-management/batch-template', [AdminAlumniController::class, 'downloadBatchTemplate'])->name('admin.alumni.batch-template');
    Route::post('/admin/alumni-management/import-batch', [AdminAlumniController::class, 'importBatch'])->name('admin.alumni.import-batch');
    Route::get('/admin/alumni-management/create', [AdminAlumniController::class, 'alumniCreate'])->name('admin.alumni.create');
    Route::post('/admin/alumni-management', [AdminAlumniController::class, 'alumniStore'])->name('admin.alumni.store');
    Route::get('/admin/alumni-management/{alumni}/tracer-records/{record}/employment-proof', [AdminAlumniController::class, 'downloadEmploymentProof'])->name('admin.alumni.proof.download');
    Route::post('/admin/alumni-management/{alumni}/tracer-records/{record}/employment-proof/verify', [AdminAlumniController::class, 'verifyEmploymentProof'])->name('admin.alumni.proof.verify');
    Route::post('/admin/alumni-management/{alumni}/tracer-records/{record}/employment-proof/reject', [AdminAlumniController::class, 'rejectEmploymentProof'])->name('admin.alumni.proof.reject');
    Route::get('/admin/alumni-management/{id}', [AdminAlumniController::class, 'alumniShow'])->name('admin.alumni.show');
    Route::post('/admin/alumni-management/{id}/notify', [AdminAlumniController::class, 'notifyAlumni'])->name('admin.alumni.notify');
    Route::get('/admin/alumni-management/{id}/edit', [AdminAlumniController::class, 'alumniEdit'])->name('admin.alumni.edit');
    Route::put('/admin/alumni-management/{id}', [AdminAlumniController::class, 'alumniUpdate'])->name('admin.alumni.update');
    Route::delete('/admin/alumni-management/{id}', [AdminAlumniController::class, 'alumniDelete'])->name('admin.alumni.delete');
});

// ==================== ADMIN OPERATIONAL ROUTES ====================
Route::middleware(['regular_admin', 'admin_permission:view_admin_dashboard'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

Route::middleware(['regular_admin', 'admin_permission:manage_jobs'])->group(function () {
    Route::get('/admin/job-management', [AdminJobController::class, 'jobManagement'])->name('admin.jobs');
    Route::get('/admin/job-management/create', [AdminJobController::class, 'jobCreate'])->name('admin.jobs.create');
    Route::post('/admin/job-management', [AdminJobController::class, 'jobStore'])->name('admin.jobs.store');
    Route::get('/admin/job-management/{id}', [AdminJobController::class, 'jobShow'])->name('admin.jobs.show');
    Route::get('/admin/job-management/{id}/edit', [AdminJobController::class, 'jobEdit'])->name('admin.jobs.edit');
    Route::put('/admin/job-management/{id}', [AdminJobController::class, 'jobUpdate'])->name('admin.jobs.update');
    Route::delete('/admin/job-management/{id}', [AdminJobController::class, 'jobDelete'])->name('admin.jobs.delete');
    Route::post('/admin/job-submissions/{id}/approve', [AdminJobController::class, 'approveJobSubmission'])->name('admin.job-submissions.approve');
    Route::post('/admin/job-submissions/{id}/reject', [AdminJobController::class, 'rejectJobSubmission'])->name('admin.job-submissions.reject');
});

Route::middleware(['regular_admin', 'admin_permission:manage_announcements'])->group(function () {
    Route::get('/admin/announcement-management', [AdminAnnouncementController::class, 'announcementManagement'])->name('admin.announcements');
    Route::get('/admin/announcement-management/create', [AdminAnnouncementController::class, 'announcementCreate'])->name('admin.announcements.create');
    Route::post('/admin/announcement-management', [AdminAnnouncementController::class, 'announcementStore'])->name('admin.announcements.store');
    Route::post('/admin/announcement-management/sync-facebook', [AdminAnnouncementController::class, 'announcementSyncFacebookPage'])->name('admin.announcements.sync-facebook');
    Route::get('/admin/announcement-management/{id}', [AdminAnnouncementController::class, 'announcementShow'])->name('admin.announcements.show');
    Route::get('/admin/announcement-management/{id}/edit', [AdminAnnouncementController::class, 'announcementEdit'])->name('admin.announcements.edit');
    Route::put('/admin/announcement-management/{id}', [AdminAnnouncementController::class, 'announcementUpdate'])->name('admin.announcements.update');
    Route::delete('/admin/announcement-management/{id}', [AdminAnnouncementController::class, 'announcementDelete'])->name('admin.announcements.delete');
});

Route::middleware(['regular_admin', 'admin_permission:manage_feedback'])->group(function () {
    Route::get('/admin/feedback-management', [AdminFeedbackController::class, 'index'])->name('admin.feedback');
    Route::get('/admin/feedback-management/{id}', [AdminFeedbackController::class, 'show'])->name('admin.feedback.show');
    Route::put('/admin/feedback-management/{id}', [AdminFeedbackController::class, 'update'])->name('admin.feedback.update');
});

Route::middleware(['regular_admin', 'admin_permission:view_analytics'])->group(function () {
    Route::get('/admin/analytics/export/{report}', [AdminAnalyticsController::class, 'exportCsv'])
        ->whereIn('report', ['employability', 'satisfaction'])
        ->name('admin.analytics.export');
    Route::get('/admin/analytics', [AdminAnalyticsController::class, 'analytics'])->name('admin.analytics');
});

Route::middleware(['regular_admin', 'admin_permission:view_audit_logs'])->group(function () {
    Route::get('/admin/audit-logs', [AdminAuditLogController::class, 'index'])->name('admin.audit-logs');
});
