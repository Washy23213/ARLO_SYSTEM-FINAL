@php
    $adminAccount = $adminAccount ?? null;
    $isEditing = $adminAccount !== null;
    $existingAdminNames = $existingAdminNames ?? [];
    $roleOptions = \App\Models\Admin::ROLE_LABELS;
    $selectedRole = old('role', $adminAccount?->role ?? \App\Models\Admin::ROLE_ADMIN);
@endphp

@if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form id="adminAccountForm" method="POST" action="{{ $formAction }}" class="rounded-[1.5rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
    @csrf
    @if(($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label for="admin_firstname" class="block text-sm font-bold text-slate-700">First Name</label>
            <input
                id="admin_firstname"
                name="firstname"
                type="text"
                value="{{ old('firstname', $adminAccount?->firstname) }}"
                required
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
            >
        </div>

        <div>
            <label for="admin_lastname" class="block text-sm font-bold text-slate-700">Last Name</label>
            <input
                id="admin_lastname"
                name="lastname"
                type="text"
                value="{{ old('lastname', $adminAccount?->lastname) }}"
                required
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
            >
        </div>

        <div>
            <label for="admin_email" class="block text-sm font-bold text-slate-700">Email</label>
            <input
                id="admin_email"
                name="email"
                type="email"
                value="{{ old('email', $adminAccount?->email) }}"
                required
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
            >
        </div>

        <div>
            <label for="admin_phone" class="block text-sm font-bold text-slate-700">Phone</label>
            <input
                id="admin_phone"
                name="phone"
                type="text"
                value="{{ old('phone', $adminAccount?->phone) }}"
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
            >
        </div>

        <div>
            <label for="admin_password" class="block text-sm font-bold text-slate-700">Password</label>
            <input
                id="admin_password"
                name="password"
                type="password"
                minlength="8"
                @if($passwordRequired ?? false) required @endif
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
            >
            @unless($passwordRequired ?? false)
                <p class="mt-2 text-xs text-slate-500">Leave blank to keep the current password.</p>
            @endunless
        </div>

        <div>
            <label for="admin_password_confirmation" class="block text-sm font-bold text-slate-700">Confirm Password</label>
            <input
                id="admin_password_confirmation"
                name="password_confirmation"
                type="password"
                minlength="8"
                @if($passwordRequired ?? false) required @endif
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
            >
        </div>

        <div class="md:col-span-2">
            <label for="admin_role" class="block text-sm font-bold text-slate-700">Assigned Role</label>
            <select
                id="admin_role"
                name="role"
                required
                class="mt-2 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-800 outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"
            >
                @foreach($roleOptions as $roleValue => $roleLabel)
                    <option value="{{ $roleValue }}" @selected($selectedRole === $roleValue)>{{ $roleLabel }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
        @if($isEditing)
            <button
                type="submit"
                form="deleteAdminAccountForm"
                data-tooltip="Delete this admin account"
                onclick="return confirm('Delete this admin account? This will remove administrative access for this user.');"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-bold text-rose-700 transition hover:bg-rose-100 sm:mr-auto"
            >
                <i class='bx bx-trash text-lg'></i>
                Delete
            </button>
        @endif
        <a href="{{ route('super-admin.admin-accounts.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
            Cancel
        </a>
        <button
            type="{{ $isEditing ? 'submit' : 'button' }}"
            @unless($isEditing) id="openAdminAccountOverview" @endunless
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800"
        >
            <i class='bx bx-save text-lg'></i>
            {{ $submitLabel }}
        </button>
    </div>
</form>

@if($isEditing)
    <form id="deleteAdminAccountForm" method="POST" action="{{ route('super-admin.admin-accounts.destroy', $adminAccount) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endif

@unless($isEditing)
    <div
        id="adminNameWarningModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4 py-6 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="adminNameWarningTitle"
    >
        <div class="w-full max-w-2xl overflow-hidden rounded-[1.5rem] border border-amber-200 bg-white shadow-2xl">
            <div class="border-b border-amber-100 bg-amber-50 px-6 py-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Possible Duplicate Name</p>
                <h2 id="adminNameWarningTitle" class="mt-2 text-2xl font-black text-slate-950">First name or last name already exists</h2>
                <p class="mt-1 text-sm leading-6 text-amber-950">
                    Another admin account already uses part of this name. Names can repeat, but review this before creating the account.
                </p>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div id="adminNameWarningDetails" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold leading-6 text-amber-950"></div>
                <p class="text-sm leading-6 text-slate-600">
                    The email address must still be unique. If this is a different staff member, you can continue and review the full admin login details next.
                </p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    id="closeAdminNameWarning"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-100"
                >
                    Back to Edit
                </button>
                <button
                    type="button"
                    id="continueAdminNameWarning"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-amber-600 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-700"
                >
                    <i class='bx bx-error-circle text-lg'></i>
                    Continue Anyway
                </button>
            </div>
        </div>
    </div>

    <div
        id="adminAccountOverviewModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4 py-6 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="adminAccountOverviewTitle"
    >
        <div class="w-full max-w-3xl overflow-hidden rounded-[1.5rem] border border-emerald-200 bg-white shadow-2xl">
            <div class="border-b border-emerald-100 bg-emerald-50 px-6 py-5">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Admin Account Overview</p>
                <h2 id="adminAccountOverviewTitle" class="mt-2 text-2xl font-black text-slate-950">Review admin login details</h2>
                <p class="mt-1 text-sm leading-6 text-slate-700">
                    Confirm this regular administrator account before saving it. This does not create another Super Admin.
                </p>
            </div>

            <div class="space-y-4 px-6 py-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Admin Name</p>
                    <p id="adminOverviewName" class="mt-1 text-xl font-black text-slate-950">Admin Account</p>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Email</p>
                        <p id="adminOverviewEmail" class="mt-2 break-all font-mono text-sm text-slate-950">N/A</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">Password</p>
                        <p id="adminOverviewPassword" class="mt-2 break-all font-mono text-sm text-slate-950">N/A</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Assigned Role</p>
                    <p id="adminOverviewRole" class="mt-2 text-sm font-black text-emerald-950">Administrator</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    id="closeAdminAccountOverview"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-100"
                >
                    Back to Edit
                </button>
                <button
                    type="button"
                    id="confirmAdminAccountCreate"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800"
                >
                    <i class='bx bx-check-circle text-lg'></i>
                    Confirm Create Admin
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('adminAccountForm');
            const openButton = document.getElementById('openAdminAccountOverview');
            const overviewModal = document.getElementById('adminAccountOverviewModal');
            const nameWarningModal = document.getElementById('adminNameWarningModal');
            const closeOverviewButton = document.getElementById('closeAdminAccountOverview');
            const closeNameWarningButton = document.getElementById('closeAdminNameWarning');
            const continueNameWarningButton = document.getElementById('continueAdminNameWarning');
            const confirmButton = document.getElementById('confirmAdminAccountCreate');
            const nameWarningDetails = document.getElementById('adminNameWarningDetails');
            const existingAdminNames = @json($existingAdminNames);

            if (!form || !openButton || !overviewModal || !nameWarningModal || !closeOverviewButton || !closeNameWarningButton || !continueNameWarningButton || !confirmButton || !nameWarningDetails) {
                return;
            }

            const fields = {
                firstname: form.elements.firstname,
                lastname: form.elements.lastname,
                email: form.elements.email,
                password: form.elements.password,
                passwordConfirmation: form.elements.password_confirmation,
                role: form.elements.role,
            };
            let nameWarningAccepted = false;

            const setText = (id, value) => {
                const target = document.getElementById(id);
                if (target) {
                    target.textContent = value || 'N/A';
                }
            };

            const fieldValue = (field) => (field?.value || '').trim();
            const normalizedValue = (field) => fieldValue(field).toLowerCase();
            const selectedRoleLabel = () => fields.role?.selectedOptions?.[0]?.textContent?.trim() || 'Administrator';

            const showDialog = (dialog) => {
                dialog.classList.remove('hidden');
                dialog.classList.add('flex');
                document.documentElement.classList.add('overflow-hidden');
                document.body.classList.add('overflow-hidden');
            };

            const hideDialog = (dialog) => {
                dialog.classList.add('hidden');
                dialog.classList.remove('flex');

                if (overviewModal.classList.contains('hidden') && nameWarningModal.classList.contains('hidden')) {
                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            };

            const validateFormForReview = () => {
                fields.passwordConfirmation.setCustomValidity('');

                if (!form.reportValidity()) {
                    return false;
                }

                const password = fieldValue(fields.password);
                const passwordConfirmation = fieldValue(fields.passwordConfirmation);

                if (password !== passwordConfirmation) {
                    fields.passwordConfirmation.setCustomValidity('Passwords do not match.');
                    fields.passwordConfirmation.reportValidity();
                    [fields.password, fields.passwordConfirmation].forEach((field) => field?.addEventListener('input', () => {
                        fields.passwordConfirmation.setCustomValidity('');
                    }, { once: true }));
                    return false;
                }

                return true;
            };

            const matchingNames = () => {
                const firstname = normalizedValue(fields.firstname);
                const lastname = normalizedValue(fields.lastname);

                return existingAdminNames.filter((admin) => {
                    return (firstname && admin.firstname === firstname) || (lastname && admin.lastname === lastname);
                });
            };

            const renderNameWarning = (matches) => {
                const firstName = fieldValue(fields.firstname);
                const lastName = fieldValue(fields.lastname);
                const matchList = matches.map((admin) => admin.name).join(', ');
                const matchedParts = [];

                if (matches.some((admin) => admin.firstname === firstName.toLowerCase())) {
                    matchedParts.push(`first name "${firstName}"`);
                }

                if (matches.some((admin) => admin.lastname === lastName.toLowerCase())) {
                    matchedParts.push(`last name "${lastName}"`);
                }

                nameWarningDetails.textContent = `The ${matchedParts.join(' and ')} already appears in: ${matchList}. Do you still want to create this admin account?`;
            };

            const showOverview = () => {
                const password = fieldValue(fields.password);
                const fullName = `${fieldValue(fields.firstname)} ${fieldValue(fields.lastname)}`.trim();

                setText('adminOverviewName', fullName || 'Admin Account');
                setText('adminOverviewEmail', fieldValue(fields.email));
                setText('adminOverviewPassword', password);
                setText('adminOverviewRole', selectedRoleLabel());

                showDialog(overviewModal);
            };

            const startCreateReview = () => {
                if (!validateFormForReview()) {
                    return;
                }

                const matches = matchingNames();

                if (matches.length > 0 && !nameWarningAccepted) {
                    renderNameWarning(matches);
                    showDialog(nameWarningModal);
                    return;
                }

                showOverview();
            };

            [fields.firstname, fields.lastname].forEach((field) => {
                field?.addEventListener('input', () => {
                    nameWarningAccepted = false;
                });
            });

            openButton.addEventListener('click', startCreateReview);
            closeOverviewButton.addEventListener('click', () => hideDialog(overviewModal));
            closeNameWarningButton.addEventListener('click', () => hideDialog(nameWarningModal));
            continueNameWarningButton.addEventListener('click', () => {
                nameWarningAccepted = true;
                hideDialog(nameWarningModal);
                showOverview();
            });

            [overviewModal, nameWarningModal].forEach((dialog) => {
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog) {
                        hideDialog(dialog);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                if (!overviewModal.classList.contains('hidden')) {
                    hideDialog(overviewModal);
                } else if (!nameWarningModal.classList.contains('hidden')) {
                    hideDialog(nameWarningModal);
                }
            });

            confirmButton.addEventListener('click', () => {
                confirmButton.disabled = true;
                confirmButton.classList.add('cursor-not-allowed', 'opacity-70');
                form.requestSubmit();
            });
        });
    </script>
@endunless
