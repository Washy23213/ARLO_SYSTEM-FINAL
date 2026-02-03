<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;

// Home / Welcome page
Route::get('/', function () {
    return view('welcome');
});

// ==================== LOGIN ROUTES ====================
// Show login form
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// Handle login submission
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// ==================== REGISTRATION / SIGNUP ROUTE ====================
// Show registration form
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
// Handle registration form submission
Route::post('/signup', [AuthController::class, 'register'])->name('register.submit');

// ==================== DASHBOARD ROUTE ====================
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ==================== ALUMNI RECORDS ROUTE ====================
Route::get('/alumni-records', [DashboardController::class, 'alumniRecords'])->name('alumni.records');

// ==================== JOBS ROUTE ====================
Route::get('/jobs', [DashboardController::class, 'jobs'])->name('jobs');

// ==================== JOB DETAIL ROUTE ====================
Route::get('/jobs/{id}', [DashboardController::class, 'jobDetail'])->name('job.detail');

// ==================== ANNOUNCEMENT DETAIL ROUTE ====================
Route::get('/announcements/{id}', [DashboardController::class, 'announcementDetail'])->name('announcement.detail');

// ==================== SETTINGS ROUTE ====================
Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');

// ==================== PROFILE ROUTE ====================
Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');

// ==================== LOGOUT ROUTE ====================
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');

// ==================== ADMIN ROUTES ====================
Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
Route::get('/admin/alumni-management', [AdminController::class, 'alumniManagement'])->name('admin.alumni');
Route::get('/admin/job-management', [AdminController::class, 'jobManagement'])->name('admin.jobs');
Route::get('/admin/announcement-management', [AdminController::class, 'announcementManagement'])->name('admin.announcements');
Route::get('/admin/analytics', [AdminController::class, 'analytics'])->name('admin.analytics');
