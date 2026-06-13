<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminAccountController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $admins = Admin::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . mb_strtolower($search) . '%';

                $query->where(function ($searchQuery) use ($like) {
                    $searchQuery
                        ->whereRaw('LOWER(firstname) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(lastname) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$like]);
                });
            })
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->paginate(15)
            ->withQueryString();

        return view('super-admin.admin-accounts.index', [
            'admins' => $admins,
            'search' => $search,
            'adminCount' => Admin::query()->count(),
            'alumniCount' => User::query()->alumni()->count(),
        ]);
    }

    public function create()
    {
        $existingRegularAdminNames = Admin::query()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get(['firstname', 'lastname'])
            ->map(function (Admin $admin) {
                $firstname = trim((string) $admin->firstname);
                $lastname = trim((string) $admin->lastname);

                return [
                    'firstname' => mb_strtolower($firstname),
                    'lastname' => mb_strtolower($lastname),
                    'name' => (trim($firstname . ' ' . $lastname) ?: 'Admin Account') . ' (Admin)',
                ];
            });
        $existingSuperAdminNames = SuperAdmin::query()
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get(['firstname', 'lastname'])
            ->map(function (SuperAdmin $superAdmin) {
                $firstname = trim((string) $superAdmin->firstname);
                $lastname = trim((string) $superAdmin->lastname);

                return [
                    'firstname' => mb_strtolower($firstname),
                    'lastname' => mb_strtolower($lastname),
                    'name' => (trim($firstname . ' ' . $lastname) ?: 'Super Admin Account') . ' (Super Admin)',
                ];
            });

        $existingAdminNames = $existingRegularAdminNames
            ->merge($existingSuperAdminNames)
            ->filter(fn (array $admin) => $admin['firstname'] !== '' || $admin['lastname'] !== '')
            ->values()
            ->all();

        return view('super-admin.admin-accounts.create', [
            'existingAdminNames' => $existingAdminNames,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedAdminAccount($request);

        $plainPassword = (string) $validated['password'];

        $admin = Admin::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $this->normalizeEmail($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($plainPassword),
            'role' => $validated['role'],
            'email_verified_at' => now(),
        ]);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'super_admin.admin.create',
            "Created admin account for {$admin->email} ({$admin->id}).",
            $request
        );

        return redirect()
            ->route('super-admin.admin-accounts.index')
            ->with('success', 'Admin account created successfully.');
    }

    public function edit(Admin $adminAccount)
    {
        return view('super-admin.admin-accounts.edit', [
            'adminAccount' => $adminAccount,
        ]);
    }

    public function update(Request $request, Admin $adminAccount)
    {
        $validated = $this->validatedAdminAccount($request, $adminAccount);

        $payload = [
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $this->normalizeEmail($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make((string) $validated['password']);
        }

        $adminAccount->update($payload);

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'super_admin.admin.update',
            "Updated admin account for {$adminAccount->email} ({$adminAccount->id}).",
            $request
        );

        return redirect()
            ->route('super-admin.admin-accounts.index')
            ->with('success', 'Admin account updated successfully.');
    }

    public function destroy(Request $request, Admin $adminAccount)
    {
        $email = $adminAccount->email;
        $id = (string) $adminAccount->id;

        $adminAccount->delete();

        app(AuditLogService::class)->log(
            (string) auth()->id(),
            'super_admin.admin.delete',
            "Deleted admin account for {$email} ({$id}).",
            $request
        );

        return redirect()
            ->route('super-admin.admin-accounts.index')
            ->with('success', 'Admin account deleted successfully.');
    }

    protected function validatedAdminAccount(Request $request, ?Admin $admin = null): array
    {
        $passwordRules = $admin
            ? ['nullable', 'confirmed', 'min:8']
            : ['required', 'confirmed', 'min:8'];
        $emailRule = Rule::unique('admins', 'email');

        if ($admin) {
            $emailRule->ignore($admin->id);
        }

        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                $emailRule,
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => $passwordRules,
            'role' => ['required', Rule::in(Admin::ROLES)],
        ]);

        $email = $this->normalizeEmail($validated['email']);
        $adminEmailExists = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->when($admin, fn ($query) => $query->where('id', '!=', $admin->id))
            ->exists();
        $userEmailExists = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();
        $superAdminEmailExists = SuperAdmin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        if ($adminEmailExists || $userEmailExists || $superAdminEmailExists) {
            throw ValidationException::withMessages([
                'email' => $userEmailExists
                    ? 'This email is already used by an alumni account.'
                    : ($superAdminEmailExists
                        ? 'This email is already used by a super admin account.'
                        : 'This email is already used by another admin account.'),
            ]);
        }

        return $validated;
    }

    protected function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
