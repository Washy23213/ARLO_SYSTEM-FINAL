@extends('admin.layout')

@section('title', 'Add Admin Account')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-6 py-6">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">Super Admin Account Control</p>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Add Admin Account</h1>
        <p class="mt-2 text-sm text-emerald-950">Create a regular administrator login for OARL staff. This does not create another Super Admin.</p>
    </div>

    @include('super-admin.admin-accounts.partials.form', [
        'adminAccount' => null,
        'formAction' => route('super-admin.admin-accounts.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'Create Admin',
        'passwordRequired' => true,
        'existingAdminNames' => $existingAdminNames ?? [],
    ])
</div>
@endsection
