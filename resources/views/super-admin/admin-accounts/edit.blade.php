@extends('admin.layout')

@section('title', 'Edit Admin Account')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-6 py-6">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Super Admin Account Control</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Edit Admin Account</h1>
        <p class="mt-2 text-sm text-emerald-950">Update the regular administrator account details. Leave the password fields blank to keep the current password.</p>
    </div>

    @include('super-admin.admin-accounts.partials.form', [
        'adminAccount' => $adminAccount,
        'formAction' => route('super-admin.admin-accounts.update', $adminAccount),
        'formMethod' => 'PUT',
        'submitLabel' => 'Save Changes',
        'passwordRequired' => false,
    ])
</div>
@endsection
