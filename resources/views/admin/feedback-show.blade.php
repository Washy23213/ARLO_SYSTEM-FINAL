@extends('admin.layout')

@section('title', 'Feedback Details')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.85fr)]">
        <div class="space-y-6">
            <div class="surface-card p-6">
                <div class="mb-5 border-b border-slate-200 pb-5">
                    <p class="text-xs uppercase tracking-[0.2em] text-emerald-700/80">Feedback Record</p>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $feedback->subject ?? 'Feedback Details' }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Message and status details.</p>
                </div>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Submitted By</p>
                        <p class="mt-1 text-gray-900 font-semibold">
                            {{ trim((string) ($feedback->submitter_firstname ?? '') . ' ' . (string) ($feedback->submitter_lastname ?? '')) ?: ($feedback->submitter_email ?? 'Anonymous / Removed User') }}
                        </p>
                        <p class="mt-1 break-all text-sm text-gray-500">{{ $feedback->submitter_email ?? 'No linked email' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Submitted On</p>
                        <p class="mt-1 text-gray-900 font-semibold">{{ $feedback->created_at ? \Carbon\Carbon::parse($feedback->created_at)->format('F j, Y h:i A') : 'N/A' }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ $feedback->created_at ? \Carbon\Carbon::parse($feedback->created_at)->diffForHumans() : '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Type</p>
                        <p class="mt-1 text-gray-900">{{ $feedback->type ?? 'General Feedback' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Last Updated</p>
                        <p class="mt-1 text-gray-900" data-admin-feedback-last-updated>{{ $feedback->updated_at ? \Carbon\Carbon::parse($feedback->updated_at)->format('F j, Y h:i A') : 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="surface-card p-6">
                <div class="mb-4">
                    <p class="section-kicker text-emerald-700/80">Message</p>
                    <h2 class="mt-2 text-xl font-semibold text-gray-900">Alumni Submission</h2>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                    <p class="whitespace-pre-line text-sm leading-7 text-slate-700">{{ $feedback->message ?? 'No message provided.' }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="surface-card p-6">
                <div class="mb-4">
                    <p class="section-kicker text-emerald-700/80">Monitoring State</p>
                    <h2 class="mt-2 text-xl font-semibold text-gray-900">Admin Actions</h2>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.feedback.update', $feedback->id) }}"
                    class="space-y-4"
                    data-admin-ajax-form
                    data-admin-ajax-kind="feedback"
                    data-confirm-submit="Save these feedback monitoring changes?"
                    data-confirm-title="Save feedback changes"
                    data-confirm-label="Save Changes"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="status" class="mb-2 block text-sm font-semibold text-gray-900">Status</label>
                        <select id="status" name="status" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach ($statusOptions as $statusOption)
                                <option value="{{ $statusOption }}" {{ old('status', $feedback->status) === $statusOption ? 'selected' : '' }}>{{ $statusOption }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="priority" class="mb-2 block text-sm font-semibold text-gray-900">Priority</label>
                        <select id="priority" name="priority" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach ($priorityOptions as $priorityOption)
                                <option value="{{ $priorityOption }}" {{ old('priority', $feedback->priority) === $priorityOption ? 'selected' : '' }}>{{ $priorityOption }}</option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="assigned_to" class="mb-2 block text-sm font-semibold text-gray-900">Assigned Admin</label>
                        <select id="assigned_to" name="assigned_to" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Unassigned</option>
                            @foreach ($assigneeOptions as $assigneeOption)
                                <option value="{{ $assigneeOption['id'] }}" {{ old('assigned_to', $feedback->assigned_to) === $assigneeOption['id'] ? 'selected' : '' }}>
                                    {{ $assigneeOption['label'] }}{{ !empty($assigneeOption['email']) ? ' (' . $assigneeOption['email'] . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700" data-admin-ajax-submit-button>
                        <i class='bx bx-save text-base'></i>
                        <span data-admin-ajax-submit-label>Save changes</span>
                    </button>
                </form>
            </div>

            <div class="surface-card p-6">
                <div class="mb-4">
                    <p class="section-kicker text-emerald-700/80">Current Snapshot</p>
                    <h2 class="mt-2 text-xl font-semibold text-gray-900">Tracking Details</h2>
                </div>
                <div class="space-y-4 text-sm text-gray-700">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                        <span class="text-gray-500">Current Status</span>
                        <span class="font-semibold text-gray-900" data-admin-feedback-current-status>{{ $feedback->status ?? 'Open' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                        <span class="text-gray-500">Current Priority</span>
                        <span class="font-semibold text-gray-900" data-admin-feedback-current-priority>{{ $feedback->priority ?? 'Medium' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3">
                        <span class="text-gray-500">Assigned To</span>
                        <span class="text-right font-semibold text-gray-900" data-admin-feedback-assigned-to>
                            {{ trim((string) ($feedback->assignee_firstname ?? '') . ' ' . (string) ($feedback->assignee_lastname ?? '')) ?: ($feedback->assignee_email ?? 'Unassigned') }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-500">Resolved At</span>
                        <span class="text-right font-semibold text-gray-900" data-admin-feedback-resolved-at>
                            {{ !empty($feedback->resolved_at) ? \Carbon\Carbon::parse($feedback->resolved_at)->format('F j, Y h:i A') : 'Not resolved yet' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
