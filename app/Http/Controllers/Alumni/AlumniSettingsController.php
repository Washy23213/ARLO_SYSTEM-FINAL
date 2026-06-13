<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\DeleteAccountRequest;
use App\Http\Requests\Alumni\LogoutOtherDevicesRequest;
use App\Http\Requests\Alumni\UpdateScholarshipRecordRequest;
use App\Http\Requests\Alumni\UpdateSettingsPasswordRequest;
use App\Http\Requests\Alumni\UpdateSettingsProfileRequest;
use App\Http\Requests\Alumni\VerifyDeleteAccountRequest;
use App\Services\AuditLogService;
use App\Support\SystemOptions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AlumniSettingsController extends Controller
{
    public function settings()
    {
        $sessions = collect();

        if (auth()->check() && Schema::hasTable('sessions')) {
            $currentSessionId = session()->getId();

            $sessions = DB::table('sessions')
                ->where('user_id', auth()->id())
                ->orderByDesc('last_activity')
                ->get()
                ->map(function ($session) use ($currentSessionId) {
                    $userAgent = (string) ($session->user_agent ?? '');

                    return [
                        'id' => $session->id,
                        'ip_address' => $session->ip_address ?: 'Unknown IP',
                        'browser' => str($userAgent)->contains('Firefox') ? 'Firefox'
                            : (str($userAgent)->contains('Edg') ? 'Edge'
                            : (str($userAgent)->contains('Chrome') ? 'Chrome'
                            : (str($userAgent)->contains('Safari') ? 'Safari' : 'Browser'))),
                        'device' => str($userAgent)->contains('Mobile') ? 'Mobile device' : 'Desktop device',
                        'last_seen' => Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                        'is_current' => $session->id === $currentSessionId,
                    ];
                });
        }

        return view('settings', compact('sessions'));
    }

    public function updateProfile(UpdateSettingsProfileRequest $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $validated = $request->validated();
        $normalizedDepartment = SystemOptions::canonical($validated['department'] ?? null, SystemOptions::departments());
        $normalizedDegree = SystemOptions::canonical($validated['degree'] ?? null, SystemOptions::programsForDepartment($normalizedDepartment))
            ?? SystemOptions::clean($validated['degree'] ?? null);

        $finalLocation = ($validated['location'] ?? null) === 'Others'
            ? ($validated['specific_location'] ?? null)
            : ($validated['location'] ?? null);

        $profileUpdates = [
            'firstname' => SystemOptions::clean($validated['firstname']),
            'lastname' => SystemOptions::clean($validated['lastname']),
            'email' => $validated['email'],
            'phone' => SystemOptions::clean($validated['phone'] ?? null),
            'department' => $normalizedDepartment,
            'degree' => $normalizedDegree,
            'year_graduated' => $validated['year_graduated'] ?? null,
            'location' => SystemOptions::canonical($finalLocation, SystemOptions::locations()),
            'skills' => SystemOptions::clean($validated['skills'] ?? null),
            'achievements' => SystemOptions::clean($validated['achievements'] ?? null),
        ];

        if (Schema::hasColumn('users', 'newsletter_opt_in') && $request->has('newsletter_opt_in')) {
            $profileUpdates['newsletter_opt_in'] = $request->boolean('newsletter_opt_in');
        }

        $user->update($profileUpdates);

        app(AuditLogService::class)->log(
            (string) $user->id,
            'alumni.profile.update',
            "Updated profile information for {$user->email} ({$user->id}).",
            $request
        );

        return redirect()->route('profile')->with('success', 'Profile updated successfully.');
    }

    public function updateScholarship(UpdateScholarshipRecordRequest $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        $validated = $request->validated();
        $hasScholarship = (bool) ($validated['has_scholarship'] ?? false);
        $selectedScholarshipName = $validated['scholarship_name'] ?? null;
        $scholarshipName = $hasScholarship
            ? ($selectedScholarshipName === 'Other'
                ? SystemOptions::clean($validated['scholarship_name_other'] ?? null)
                : SystemOptions::canonical($selectedScholarshipName, SystemOptions::scholarshipNames()))
            : null;

        $user->update([
            'has_scholarship' => $hasScholarship,
            'scholarship_name' => $scholarshipName,
            'scholarship_year_awarded' => $hasScholarship ? (int) $validated['scholarship_year_awarded'] : null,
            'scholarship_helped_get_job' => $hasScholarship
                ? (bool) ($validated['scholarship_helped_get_job'] ?? false)
                : null,
        ]);

        app(AuditLogService::class)->log(
            (string) $user->id,
            'alumni.scholarship.update',
            "Updated scholarship record for {$user->email} ({$user->id}).",
            $request
        );

        return redirect()
            ->to(route('tracer.index') . '#scholarship-record')
            ->with('success', 'Scholarship record updated successfully.');
    }

    public function updatePassword(UpdateSettingsPasswordRequest $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $validated = $request->validated();

        $user = auth()->user();
        if (!$user || !Hash::check($validated['current_password'], $user->password)) {
            return redirect()
                ->route('settings')
                ->withErrors(['current_password' => 'The current password does not match your account password.'])
                ->withInput(['settings_form' => 'password']);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        app(AuditLogService::class)->log(
            (string) $user->id,
            'alumni.password.update',
            "Updated account password for {$user->email} ({$user->id}).",
            $request
        );

        return redirect()->route('settings')->with('success', 'Password updated successfully.');
    }

    public function deleteAccount(DeleteAccountRequest $request)
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has ended. Please sign in again.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login');
        }

        $user = auth()->user();
        $verifiedUserId = (string) $request->session()->get('delete_account_verified_user_id', '');
        $verifiedAt = $request->session()->get('delete_account_verified_at');

        if ($verifiedUserId !== (string) $user->id || empty($verifiedAt) || now()->diffInMinutes(\Carbon\Carbon::parse($verifiedAt)) > 10) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Verification expired. Please verify your password again before deleting.',
                    'verification_required' => true,
                ], 422);
            }

            return redirect()->route('settings')->withErrors(['delete_account' => 'Verification expired. Please verify your password again before deleting.']);
        }
        if ($user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Admin accounts cannot be deleted from this page.',
                ], 403);
            }

            return redirect()->route('settings')->withErrors(['delete_account' => 'Admin accounts cannot be deleted from this page.']);
        }

        DB::transaction(function () use ($request, $user) {
            $userId = $user->id;

            app(AuditLogService::class)->log(
                (string) $userId,
                'alumni.account.delete',
                "Deleted own alumni account for {$user->email} ({$userId}).",
                $request
            );

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $userId)->delete();
            }

            DB::table('users')->where('id', $userId)->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your account has been deleted.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')->with('success', 'Your account has been deleted.');
    }

    public function verifyDeleteAccount(VerifyDeleteAccountRequest $request)
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has ended. Please sign in again.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login');
        }

        $validated = $request->validated();
        $user = auth()->user();

        if ($user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Admin accounts cannot be deleted from this page.',
                ], 403);
            }

            return redirect()->route('settings')->withErrors(['delete_account' => 'Admin accounts cannot be deleted from this page.']);
        }
        if (!Hash::check($validated['current_password'], $user->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Verification failed. Current password is incorrect.',
                ], 422);
            }

            return redirect()->route('settings')->withErrors(['delete_account' => 'Verification failed. Current password is incorrect.']);
        }

        $request->session()->put('delete_account_verified_user_id', (string) $user->id);
        $request->session()->put('delete_account_verified_at', now()->toDateTimeString());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password verified. Final confirmation is now available for 10 minutes.',
                'verified' => true,
                'verification_expires_in_minutes' => 10,
            ]);
        }

        return redirect()->route('settings')->with('success', 'Verification successful. You can now permanently delete your account within 10 minutes.');
    }

    public function logoutOtherDevices(LogoutOtherDevicesRequest $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $validated = $request->validated();

        $user = auth()->user();

        if (!$user || !Hash::check($validated['current_password'], $user->password)) {
            return redirect()->route('settings')->withErrors([
                'session_password' => 'Current password is incorrect.',
            ]);
        }

        Auth::logoutOtherDevices($validated['current_password']);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        app(AuditLogService::class)->log(
            (string) $user->id,
            'alumni.sessions.logout-other',
            "Signed out other active sessions for {$user->email} ({$user->id}).",
            $request
        );

        return redirect()->route('settings')->with('success', 'Signed out other devices successfully.');
    }
}
