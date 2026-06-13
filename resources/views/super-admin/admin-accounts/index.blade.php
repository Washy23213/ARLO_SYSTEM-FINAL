@extends('admin.layout')

@section('title', 'Administrator Account Management')

@section('content')
<div class="space-y-6">
    <div class="portal-stat-grid grid-cols-1 md:grid-cols-3">
        <div data-tooltip="Regular administrator accounts created by the Super Admin" data-portal-card-link="{{ route('super-admin.admin-accounts.index') }}#admin-account-records" role="link" tabindex="0" aria-label="Open administrator account records" class="portal-stat-card portal-stat-card--green">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-id-card text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Admin Accounts</p>
                    <p class="portal-stat-card__value">{{ number_format($adminCount ?? 0) }}</p>
                    <p class="portal-stat-card__meta">Regular logins</p>
                </div>
            </div>
        </div>

        <div data-tooltip="Alumni records available for account maintenance" data-portal-card-link="{{ route('admin.alumni') }}#alumni-directory" role="link" tabindex="0" aria-label="Open alumni accounts" class="portal-stat-card portal-stat-card--blue">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-group text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Alumni Accounts</p>
                    <p class="portal-stat-card__value">{{ number_format($alumniCount ?? 0) }}</p>
                    <p class="portal-stat-card__meta">Roster and registered</p>
                </div>
            </div>
        </div>

        <div data-tooltip="Super Admin access is limited to account control, alumni account maintenance, and audit log monitoring" data-portal-card-link="{{ route('super-admin.audit-logs') }}#audit-log-records" role="link" tabindex="0" aria-label="Open super admin audit logs" class="portal-stat-card portal-stat-card--orange">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon">
                    <i class='bx bx-shield-quarter text-2xl'></i>
                </span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Super Admin Scope</p>
                    <p class="portal-stat-card__value text-2xl">Account Control</p>
                    <p class="portal-stat-card__meta">Accounts and audit logs</p>
                </div>
            </div>
        </div>
    </div>

    <div id="admin-account-records" class="scroll-mt-6 rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Account Control</p>
                <h2 class="mt-1 text-2xl font-black text-slate-950">Administrator Account Management</h2>
                <p class="mt-1 text-sm text-slate-600">Create, edit, or delete regular administrator login accounts.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('admin.alumni') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    <i class='bx bx-group text-lg'></i>
                    Alumni Accounts
                </a>
                <a href="{{ route('super-admin.admin-accounts.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800">
                    <i class='bx bx-user-plus text-lg'></i>
                    Add Admin
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('super-admin.admin-accounts.index') }}" class="border-b border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Search admin name, email, or phone..."
                    class="min-h-11 flex-1 rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
                >
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-bold text-white transition hover:bg-slate-800">
                    <i class='bx bx-search text-lg'></i>
                    Search
                </button>
                @if(($search ?? '') !== '')
                    <a href="{{ route('super-admin.admin-accounts.index') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        <i class='bx bx-reset text-lg'></i>
                        Reset
                    </a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Admin</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Contact</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Role</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($admins as $adminAccount)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-950">{{ trim($adminAccount->firstname . ' ' . $adminAccount->lastname) ?: 'Admin Account' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Created {{ optional($adminAccount->created_at)->format('M d, Y') ?? 'N/A' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="break-all text-sm font-semibold text-slate-800">{{ $adminAccount->email }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $adminAccount->phone ?: 'No phone number' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">{{ $adminAccount->roleLabel() }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        data-tooltip="Edit this admin account"
                                        href="{{ route('super-admin.admin-accounts.edit', $adminAccount) }}"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-800 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-emerald-100 hover:shadow-md"
                                    >
                                        <i class='bx bx-edit text-lg'></i>
                                        <span class="sr-only">Edit admin account</span>
                                    </a>
                                    <form method="POST" action="{{ route('super-admin.admin-accounts.destroy', $adminAccount) }}" onsubmit="return confirm('Delete this admin account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            data-tooltip="Delete this admin account"
                                            type="submit"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-rose-100 hover:shadow-md"
                                        >
                                            <i class='bx bx-trash text-lg'></i>
                                            <span class="sr-only">Delete admin account</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <p class="font-bold text-slate-800">No regular admin accounts found.</p>
                                <p class="mt-1 text-sm text-slate-500">Create an admin account to give office staff access to the admin workspace.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination-footer :paginator="$admins" class="border-slate-200 px-5">
            {{ $admins->links() }}
        </x-pagination-footer>
    </div>
</div>
@endsection
