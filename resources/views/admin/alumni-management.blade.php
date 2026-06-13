@extends('admin.layout')

@section('title', 'Alumni Management')

@section('content')
@php
    $departmentOptions = \App\Support\SystemOptions::departments();
    $selectedDepartment = old('department', '');
    $selectedDegree = old('degree', '');
    $programCatalog = \App\Support\SystemOptions::programCatalogByDepartment();
    $degreeOptions = \App\Support\SystemOptions::programsForDepartment($selectedDepartment);
    $industryOptions = \App\Support\SystemOptions::industries();
    $locationOptions = \App\Support\SystemOptions::locations();
    $salaryOptions = \App\Support\SystemOptions::salaryRanges();
    $selectedSalaryRange = \App\Support\SystemOptions::salaryRangeLabel(old('salary_range'));
    $currentYear = (int) date('Y');
    $selectedAccountStatus = $selectedAccountStatus ?? '';
    $selectedImportBatch = $selectedImportBatch ?? '';
    $importBatchOptions = $importBatchOptions ?? collect();
    $selectedImportBatchRecord = $selectedImportBatch !== ''
        ? $importBatchOptions->firstWhere('id', $selectedImportBatch)
        : null;
    $selectedImportBatchLabel = $selectedImportBatchRecord
        ? (($selectedImportBatchRecord->source_file_name ?: $selectedImportBatchRecord->name) ?? 'Selected import batch')
        : null;
    $alumniCreateModalOpen = request()->boolean('create') || ($errors->any() && old('firstname') !== null);
    $batchImportColumns = \App\Services\AlumniBatchImportService::REQUIRED_COLUMNS;
    $batchImportErrorKeys = $errors->getBag('default')->keys();
    $batchImportModalOpen = collect($batchImportErrorKeys)->contains(fn ($key) => $key === 'batch_file' || $key === 'batch_files' || str_starts_with((string) $key, 'batch_files.')) || session()->has('batch_import_header_validation');
    $isMissingEmploymentStatusFilter = ($selectedStatus ?? '') === 'unknown';
    $selectedYearFromValue = $selectedYearFrom ?? '';
    $selectedYearToValue = $selectedYearTo ?? '';
    $selectedYearRangeLabel = null;

    if (filled($selectedYearFromValue) && filled($selectedYearToValue)) {
        $selectedYearRangeLabel = (string) $selectedYearFromValue === (string) $selectedYearToValue
            ? 'Batch '.$selectedYearFromValue
            : 'Batch '.$selectedYearFromValue.' to '.$selectedYearToValue;
    } elseif (filled($selectedYearFromValue)) {
        $selectedYearRangeLabel = 'Batch '.$selectedYearFromValue.' and newer';
    } elseif (filled($selectedYearToValue)) {
        $selectedYearRangeLabel = 'Batch '.$selectedYearToValue.' and older';
    }

    $needsUpdateFilterParams = array_filter([
        'search' => $search ?? '',
        'college' => $selectedCollege ?? '',
        'year_from' => $selectedYearFromValue,
        'year_to' => $selectedYearToValue,
        'status' => 'unknown',
        'import_batch' => $selectedImportBatch,
    ], static fn ($value) => filled($value));
    $needsUpdateRoute = route('admin.alumni', $needsUpdateFilterParams) . '#alumni-directory';
    $employmentProofStatusClasses = static function ($record): string {
        return match ($record?->employmentProofStatus()) {
            \App\Models\TracerRecord::EMPLOYMENT_PROOF_STATUS_VERIFIED => 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 border-emerald-200',
            \App\Models\TracerRecord::EMPLOYMENT_PROOF_STATUS_REJECTED => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 border-rose-200',
            \App\Models\TracerRecord::EMPLOYMENT_PROOF_STATUS_PENDING => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 border-amber-200',
            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 border-slate-200',
        };
    };
@endphp
@once
    <style>
        @keyframes alumni-file-upload-glow {
            0%, 100% {
                box-shadow: 0 18px 40px rgba(7, 24, 14, 0.08), inset 0 1px rgba(255, 255, 255, 0.8);
            }

            50% {
                box-shadow: 0 22px 54px rgba(16, 185, 129, 0.18), inset 0 1px rgba(255, 255, 255, 0.9);
            }
        }

        @keyframes alumni-file-upload-float {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-3px);
            }
        }

        .alumni-file-upload__dropzone {
            position: relative;
            overflow: hidden;
            border-color: rgba(148, 163, 184, 0.32);
            background:
                radial-gradient(circle at 10% 15%, rgba(16, 185, 129, 0.16), transparent 24%),
                linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(240, 253, 244, 0.72));
            box-shadow: 0 18px 40px rgba(7, 24, 14, 0.08), inset 0 1px rgba(255, 255, 255, 0.8);
            transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease, background 180ms ease;
        }

        .alumni-file-upload__dropzone button {
            position: relative;
            z-index: 20;
        }

        .alumni-file-upload__dropzone::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(115deg, transparent 0%, rgba(255, 255, 255, 0.7) 48%, transparent 100%);
            transform: translateX(-115%);
            transition: transform 620ms ease;
        }

        .alumni-file-upload__dropzone:hover,
        .alumni-file-upload__dropzone.is-dragging,
        .alumni-file-upload__dropzone:focus-within {
            transform: translateY(-2px);
            border-color: rgba(16, 185, 129, 0.52);
            animation: alumni-file-upload-glow 2.4s ease-in-out infinite;
        }

        .alumni-file-upload__dropzone:hover::before,
        .alumni-file-upload__dropzone.is-dragging::before,
        .alumni-file-upload__dropzone:focus-within::before {
            transform: translateX(115%);
        }

        .alumni-file-upload__dropzone.has-file {
            border-color: rgba(5, 150, 105, 0.58);
            background:
                radial-gradient(circle at 12% 16%, rgba(5, 150, 105, 0.18), transparent 25%),
                linear-gradient(135deg, rgba(236, 253, 245, 0.98), rgba(255, 255, 255, 0.92));
        }

        .alumni-file-upload__icon {
            animation: alumni-file-upload-float 2.8s ease-in-out infinite;
        }

        .admin-portal .dashboard-content .admin-alumni-filter-active-card {
            border-color: rgba(180, 83, 9, 0.58) !important;
            background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 52%, #ffffff 100%) !important;
            box-shadow: inset 0 0 0 2px rgba(245, 158, 11, 0.22), 0 18px 34px rgba(120, 53, 15, 0.14) !important;
        }

        .admin-portal .dashboard-content .admin-alumni-filter-active-card .portal-stat-card__meta {
            color: #92400e !important;
            font-weight: 800 !important;
        }

        .admin-portal .dashboard-content .admin-alumni-filter-active-chip {
            border-color: rgba(245, 158, 11, 0.42) !important;
            background: #fffbeb !important;
            color: #92400e !important;
        }

        .admin-portal .dashboard-content .admin-alumni-select--active {
            border-color: rgba(245, 158, 11, 0.62) !important;
            background-color: #fffbeb !important;
            box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.24), 0 10px 24px rgba(120, 53, 15, 0.1) !important;
        }

        .admin-portal .dashboard-content tr.admin-alumni-row--needs-update {
            background: linear-gradient(90deg, rgba(255, 251, 235, 0.98), rgba(255, 255, 255, 0.96)) !important;
            box-shadow: inset 4px 0 0 #ffc600 !important;
        }

        .admin-portal .dashboard-content tr.admin-alumni-row--needs-update:hover {
            background: linear-gradient(90deg, rgba(254, 243, 199, 0.95), rgba(255, 255, 255, 0.98)) !important;
        }

        .admin-portal .dashboard-content .admin-alumni-status--needs-update {
            border: 1px solid rgba(245, 158, 11, 0.45) !important;
            background: #fffbeb !important;
            color: #92400e !important;
            box-shadow: 0 8px 18px rgba(120, 53, 15, 0.08) !important;
        }

        @media (prefers-reduced-motion: reduce) {
            .alumni-file-upload__dropzone,
            .alumni-file-upload__icon,
            .alumni-file-upload__dropzone::before {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
@endonce
<div class="space-y-6">
    @if(session('created_alumni_credentials'))
    @php
        $createdCredentials = session('created_alumni_credentials');
    @endphp
    <div class="rounded-[1.5rem] border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-emerald-50 px-5 py-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Login Credentials Overview</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $createdCredentials['name'] ?? 'New Alumni Account' }}</h2>
                <p class="mt-1 text-sm text-slate-600">Share these details with the alumni before their first login. The password is shown here only once.</p>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="rounded-2xl border border-emerald-100 bg-white px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Email</p>
                <p class="mt-2 break-all font-mono text-sm text-slate-900">{{ $createdCredentials['email'] ?? 'N/A' }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-white px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Password</p>
                <p class="mt-2 break-all font-mono text-sm text-slate-900">{{ $createdCredentials['password'] ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('success'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ session('error') }}
    </div>
    @endif

    @if(session('batch_import_result'))
    @php
        $batchImportResult = session('batch_import_result');
    @endphp
    <div class="rounded-[1.5rem] border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-emerald-50 px-5 py-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Batch Import Complete</p>
        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-2xl border border-emerald-100 bg-white px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">New Records</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format((int) ($batchImportResult['imported'] ?? 0)) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-white px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Updated</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format((int) ($batchImportResult['updated'] ?? 0)) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-white px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Skipped</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ number_format(count($batchImportResult['skipped'] ?? [])) }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('batch_import_header_validation'))
    @php
        $headerValidation = session('batch_import_header_validation');
    @endphp
    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-5 text-sm text-amber-900 shadow-sm">
        <p class="font-bold">The uploaded file is missing required columns.</p>
        <p class="mt-1 text-sm">Extra columns are allowed, but every required template column must be present before the import can continue.</p>
        <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">Required columns</p>
                <p class="mt-1 font-mono text-xs">{{ implode(', ', $headerValidation['expected'] ?? $batchImportColumns) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">Missing columns</p>
                <p class="mt-1 font-mono text-xs">{{ implode(', ', $headerValidation['missing'] ?? []) ?: 'None' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">Uploaded columns</p>
                <p class="mt-1 font-mono text-xs">{{ implode(', ', $headerValidation['actual'] ?? []) ?: 'No header row found' }}</p>
            </div>
        </div>
        @if(! empty($headerValidation['ignored'] ?? $headerValidation['extra'] ?? []))
            <p class="mt-3 text-xs font-semibold">Extra columns detected and ignored: {{ implode(', ', $headerValidation['ignored'] ?? $headerValidation['extra'] ?? []) }}</p>
        @endif
    </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <button
            type="button"
            id="openAlumniBatchImportModal"
            data-tooltip="Import a graduation batch from an Excel CSV file"
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-base font-semibold text-emerald-800 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-100 hover:shadow-md"
            aria-haspopup="dialog"
            aria-controls="alumniBatchImportModal"
            aria-expanded="{{ $batchImportModalOpen ? 'true' : 'false' }}"
        >
            <i class='bx bx-upload text-2xl'></i>
            Add Batch
        </button>
        <button
            type="button"
            id="openAlumniCreateModal"
            data-tooltip="Create a new alumni record manually"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3.5 text-base font-semibold text-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-md"
            aria-haspopup="dialog"
            aria-controls="alumniCreateModal"
            aria-expanded="{{ $alumniCreateModalOpen ? 'true' : 'false' }}"
        >
            <i class='bx bx-plus text-2xl'></i>
            Add Alumni
        </button>
    </div>

    {{-- Statistics --}}
    <div
        class="portal-stat-grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4"
        data-alumni-stats
        data-alumni-stats-url="{{ route('admin.alumni.stats') }}"
    >
        <div data-tooltip="Total alumni matching current dataset" data-portal-card-link="{{ route('admin.alumni') }}#alumni-directory" role="link" tabindex="0" aria-label="Open the alumni directory" class="portal-stat-card portal-stat-card--blue">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon"><i class='bx bx-group text-2xl'></i></span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Total Alumni</p>
                    <p class="portal-stat-card__value" data-alumni-stat-value="total">{{ $alumni->total() }}</p>
                    <p class="portal-stat-card__meta">Current filtered results</p>
                </div>
            </div>
        </div>
        <div data-tooltip="New alumni records created this month in the current filter" data-portal-card-link="{{ route('admin.alumni') }}#alumni-directory" role="link" tabindex="0" aria-label="Open alumni records created this month" class="portal-stat-card portal-stat-card--green">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon"><i class='bx bx-trending-up text-2xl'></i></span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">New Alumni This Month</p>
                    <p class="portal-stat-card__value" data-alumni-stat-value="this_month">{{ $thisMonthAlumni }}</p>
                    <p class="portal-stat-card__meta">Created in {{ now()->format('M Y') }}</p>
                </div>
            </div>
        </div>
        <div data-tooltip="Alumni tagged as employed in the current filter" data-portal-card-link="{{ route('admin.alumni', ['status' => 'employed']) }}#alumni-directory" role="link" tabindex="0" aria-label="Show employed alumni" class="portal-stat-card portal-stat-card--purple">
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon"><i class='bx bx-check-circle text-2xl'></i></span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Employed</p>
                    <p class="portal-stat-card__value" data-alumni-stat-value="employed">{{ $activeAlumni }}</p>
                    <p class="portal-stat-card__meta">Marked employed</p>
                </div>
            </div>
        </div>
        <a
            href="{{ $needsUpdateRoute }}"
            data-portal-card-link="{{ $needsUpdateRoute }}"
            data-tooltip="Registered alumni accounts with no employment status yet. Click to list them."
            data-portal-skip-animation="true"
            class="portal-stat-card portal-stat-card--orange block no-underline focus:outline-none focus:ring-4 focus:ring-amber-100 {{ $isMissingEmploymentStatusFilter ? 'admin-alumni-filter-active-card' : '' }}"
            aria-label="Show alumni missing employment status"
            @if($isMissingEmploymentStatusFilter) aria-current="page" @endif
        >
            <div class="portal-stat-card__layout">
                <span class="portal-stat-card__icon"><i class='bx bx-time text-2xl'></i></span>
                <div class="portal-stat-card__content">
                    <p class="portal-stat-card__kicker">Missing Employment Status</p>
                    <p class="portal-stat-card__value" data-alumni-stat-value="needs_update">{{ $pendingAlumni }}</p>
                    <p class="portal-stat-card__meta">{{ $isMissingEmploymentStatusFilter ? 'Showing this list now' : 'Click to filter list' }}</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Filters and Search --}}
    <div data-tooltip="Use filters to narrow alumni records" class="mb-6 overflow-hidden rounded-[1.5rem] border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-gradient-to-r from-white via-slate-50 to-white px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Filter Alumni</p>
            <p class="mt-1 text-sm text-slate-600">Search and narrow the current alumni directory.</p>
            @if($isMissingEmploymentStatusFilter)
                <span class="admin-alumni-filter-active-chip mt-3 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-extrabold uppercase tracking-[0.12em]">
                    <i class='bx bx-filter-alt text-base'></i>
                    Showing missing employment status
                </span>
            @endif
            @if(filled($selectedImportBatchLabel))
                <span class="admin-alumni-filter-active-chip mt-3 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-extrabold uppercase tracking-[0.12em]">
                    <i class='bx bx-spreadsheet text-base'></i>
                    Batch: {{ $selectedImportBatchLabel }}
                </span>
            @endif
        </div>
        <form method="GET" action="{{ route('admin.alumni') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="space-y-5 px-5 py-5">
            @if($selectedAccountStatus !== '')
                <input type="hidden" name="account_status" value="{{ $selectedAccountStatus }}">
            @endif
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(210px,0.8fr)_minmax(230px,0.85fr)_minmax(250px,0.95fr)_minmax(180px,0.7fr)]">
                <div class="space-y-2">
                    <label for="alumniFilterSearch" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Keyword</label>
                    <input
                        id="alumniFilterSearch"
                        data-tooltip="Search by alumni name, degree, or related text"
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search alumni..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100"
                    >
                </div>
                <div class="space-y-2">
                    <label for="alumniFilterCollege" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">College</label>
                    <select id="alumniFilterCollege" data-tooltip="Filter records by college/department" name="college" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                        <option value="">All Colleges</option>
                        @foreach(($collegeOptions ?? collect()) as $college)
                            <option value="{{ $college }}" {{ ($selectedCollege ?? '') === $college ? 'selected' : '' }}>{{ $college }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <span class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Year Graduated</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label for="alumniFilterYearFrom" class="sr-only">Year graduated from</label>
                        <select id="alumniFilterYearFrom" data-tooltip="Start graduation year" name="year_from" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                            <option value="">From</option>
                            @foreach(($yearOptions ?? collect()) as $year)
                                <option value="{{ $year }}" {{ (string) $selectedYearFromValue === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                        <label for="alumniFilterYearTo" class="sr-only">Year graduated to</label>
                        <select id="alumniFilterYearTo" data-tooltip="End graduation year" name="year_to" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100">
                            <option value="">To</option>
                            @foreach(($yearOptions ?? collect()) as $year)
                                <option value="{{ $year }}" {{ (string) $selectedYearToValue === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="space-y-2">
                    <label for="alumniFilterImportBatch" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Import Batch</label>
                    <select id="alumniFilterImportBatch" data-tooltip="Filter records by uploaded CSV batch" name="import_batch" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 {{ filled($selectedImportBatch) ? 'admin-alumni-select--active' : '' }}" @disabled($importBatchOptions->isEmpty())>
                        <option value="">{{ $importBatchOptions->isEmpty() ? 'No imported batches yet' : 'All Import Batches' }}</option>
                        @foreach($importBatchOptions as $batchOption)
                            @php
                                $batchFileName = trim((string) ($batchOption->source_file_name ?? ''));
                                $batchName = trim((string) ($batchOption->name ?? ''));
                                $batchLabel = $batchFileName !== '' ? $batchFileName : ($batchName !== '' ? $batchName : 'Imported batch');
                                $batchDate = $batchOption->imported_at ? $batchOption->imported_at->format('M d, Y h:i A') : null;
                            @endphp
                            <option value="{{ $batchOption->id }}" {{ (string) $selectedImportBatch === (string) $batchOption->id ? 'selected' : '' }}>
                                {{ $batchLabel }}{{ $batchDate ? ' - '.$batchDate : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-2">
                    <label for="alumniFilterStatus" class="block text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Status</label>
                    <select id="alumniFilterStatus" data-tooltip="Filter records by employment status" name="status" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100 {{ $isMissingEmploymentStatusFilter ? 'admin-alumni-select--active' : '' }}">
                        <option value="">All Status</option>
                        <option value="employed" {{ ($selectedStatus ?? '') === 'employed' ? 'selected' : '' }}>Employed</option>
                        <option value="unemployed" {{ ($selectedStatus ?? '') === 'unemployed' ? 'selected' : '' }}>Unemployed</option>
                        <option value="unknown" {{ ($selectedStatus ?? '') === 'unknown' ? 'selected' : '' }}>Missing Employment Status</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">Use one or more filters to refine the alumni directory table.</p>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button data-tooltip="Apply selected filters to alumni results" type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                        <i class='bx bx-filter-alt text-base'></i>
                        Apply Filters
                    </button>
                    <a data-tooltip="Clear all filters and show full alumni list" href="{{ route('admin.alumni') }}" data-portal-preserve-scroll="true" data-portal-skip-animation="true" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50">
                        <i class='bx bx-reset text-base'></i>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Alumni Table --}}
    <div id="alumni-directory" class="scroll-mt-6 overflow-hidden rounded-[1.75rem] border border-emerald-950/10 bg-white/95 shadow-[0_24px_60px_rgba(7,24,14,0.13)]" data-alumni-directory>
        <div class="flex flex-col gap-4 border-b border-emerald-950/10 bg-gradient-to-r from-white via-emerald-50/80 to-amber-50/50 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700/80">Alumni Records</p>
                <h2 data-tooltip="Table of alumni records based on current filters" class="mt-1 text-2xl font-extrabold tracking-normal text-slate-950">Alumni Directory</h2>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3.5 py-2 text-sm font-semibold text-emerald-800 shadow-sm">
                    <i class='bx bx-list-ul text-lg'></i>
                    {{ number_format($alumni->total()) }} records
                </span>
                @if(filled($selectedYearRangeLabel))
                    <span class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm font-semibold text-amber-800 shadow-sm">
                        <i class='bx bx-calendar text-lg'></i>
                        {{ $selectedYearRangeLabel }}
                    </span>
                @endif
                @if(filled($selectedImportBatchLabel))
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-800 shadow-sm">
                        <i class='bx bx-spreadsheet text-lg'></i>
                        {{ $selectedImportBatchLabel }}
                    </span>
                @endif
                @if($isMissingEmploymentStatusFilter)
                    <span class="admin-alumni-filter-active-chip inline-flex items-center gap-2 rounded-full border px-3.5 py-2 text-sm font-semibold shadow-sm">
                        <i class='bx bx-error-circle text-lg'></i>
                        Missing employment status only
                    </span>
                @endif
                @if((int) ($pendingProofAlumni ?? 0) > 0)
                    <span data-tooltip="Pending employment proof PDFs are sorted to the top of this list" class="inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3.5 py-2 text-sm font-semibold text-amber-800 shadow-sm">
                        <i class='bx bxs-file-pdf text-lg'></i>
                        {{ number_format((int) $pendingProofAlumni) }} proof PDF pending
                    </span>
                @endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
                <thead class="bg-[#f3f7f3]">
                    <tr>
                        <th data-tooltip="Alumni full name" class="w-[30%] px-7 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Alumni</th>
                        <th data-tooltip="Alumni college or department" class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">College</th>
                        <th data-tooltip="Academic degree completed" class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Degree</th>
                        <th data-tooltip="Year the alumni graduated" class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Batch</th>
                        <th data-tooltip="Current employment status" class="px-5 py-4 text-left text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Status</th>
                        <th data-tooltip="Edit or delete this alumni record" class="px-7 py-4 text-right text-[0.68rem] font-bold uppercase tracking-[0.22em] text-emerald-950/70">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-950/10 bg-white/80">
                    @forelse($alumni as $alum)
                    @php
                        $first = trim((string)($alum->firstname ?? ''));
                        $last = trim((string)($alum->lastname ?? ''));
                        $fullName = trim($first . ' ' . $last) ?: 'N/A';
                        $initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
                        $initials = $initials !== '' ? $initials : 'NA';
                        $detailRoute = route('admin.alumni.show', $alum->id);
                        $alumniNumber = trim((string) ($alum->alumni_idnum ?? ''));
                        $studentNumber = trim((string) ($alum->student_idnum ?? ''));
                        $recordLabel = $studentNumber !== ''
                            ? "Student ID {$studentNumber}"
                            : ($alumniNumber !== '' ? "Alumni ID {$alumniNumber}" : 'CMU Alumni');
                        $rowImportBatch = $alum->relationLoaded('importBatch') ? $alum->importBatch : null;
                        $rowImportBatchLabel = $rowImportBatch
                            ? (trim((string) ($rowImportBatch->source_file_name ?? '')) ?: trim((string) ($rowImportBatch->name ?? '')))
                            : '';
                        $isCsvOnlyRecord = (bool) ($alum->is_batch_imported ?? false)
                            || str_ends_with((string) ($alum->email ?? ''), '@oarl-batch.local');
                        $employmentStatusText = $isCsvOnlyRecord ? 'Not Registered' : match ($alum->employed_after_grad ?? null) {
                            'Yes' => 'Employed',
                            'No' => 'Unemployed',
                            default => 'Missing Status',
                        };
                        $isMissingEmploymentStatus = $employmentStatusText === 'Missing Status';
                        $employmentBadgeClasses = match ($employmentStatusText) {
                            'Employed' => 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200',
                            'Unemployed' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
                            'Not Registered' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
                            default => 'admin-alumni-status--needs-update',
                        };
                        $canNotifyAlumni = ! $isCsvOnlyRecord;
                        $latestEmploymentProof = $alum->relationLoaded('tracerRecords') ? $alum->tracerRecords->first() : null;
                        $employmentProofUploadedAt = $latestEmploymentProof?->employment_proof_uploaded_at
                            ? \Carbon\Carbon::parse($latestEmploymentProof->employment_proof_uploaded_at)->format('M d, Y')
                            : null;
                        $employmentProofStatusLabel = $latestEmploymentProof?->employmentProofStatusLabel();
                    @endphp
                    <tr
                        class="portal-clickable-row group {{ $isMissingEmploymentStatusFilter && $isMissingEmploymentStatus ? 'admin-alumni-row--needs-update' : '' }}"
                        tabindex="0"
                        role="link"
                        aria-label="Open alumni profile for {{ $fullName }}"
                        onclick="window.location='{{ $detailRoute }}'"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location='{{ $detailRoute }}'; }"
                    >
                        <td class="px-7 py-5 align-top">
                            <div class="flex min-w-[22rem] items-start gap-4">
                                <span class="portal-record-avatar mt-0.5 inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border text-xs font-extrabold tracking-[0.08em]">
                                    {{ $initials }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-base font-extrabold leading-snug tracking-normal text-slate-950">{{ $fullName }}</p>
                                    <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $recordLabel }}</p>
                                    @if($rowImportBatchLabel !== '')
                                        <p class="mt-1 inline-flex max-w-full items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-[0.08em] text-emerald-800">
                                            <i class='bx bx-spreadsheet text-sm'></i>
                                            <span class="truncate">{{ $rowImportBatchLabel }}</span>
                                        </p>
                                    @endif
                                    @if($latestEmploymentProof)
                                        <a
                                            href="{{ route('admin.alumni.proof.download', ['alumni' => $alum, 'record' => $latestEmploymentProof]) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            onclick="event.stopPropagation()"
                                            data-tooltip="Open the employment proof PDF submitted by this alumni"
                                            class="mt-2 inline-flex max-w-full items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-extrabold {{ $employmentProofStatusClasses($latestEmploymentProof) }} transition-all hover:-translate-y-0.5 hover:shadow-sm"
                                        >
                                            <i class='bx bxs-file-pdf text-base'></i>
                                            <span class="truncate">Employment PDF sent</span>
                                            @if($employmentProofUploadedAt)
                                                <span class="text-[11px] font-semibold opacity-75">{{ $employmentProofUploadedAt }}</span>
                                            @endif
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-5 align-top text-sm font-semibold text-slate-700">
                            {{ $alum->department ?? 'N/A' }}
                        </td>
                        <td class="px-5 py-5 align-top text-sm font-semibold text-slate-600">
                            {{ $alum->degree ?? 'N/A' }}
                        </td>
                        <td class="px-5 py-5 align-top text-sm font-semibold text-slate-600">
                            {{ $alum->year_graduated ?? 'N/A' }}
                        </td>
                        <td class="px-5 py-5 align-top">
                            <div class="flex flex-col items-start gap-2">
                                <span data-tooltip="Current employment status" class="inline-flex rounded-full px-3 py-1.5 text-xs font-bold {{ $employmentBadgeClasses }}">
                                    {{ $employmentStatusText }}
                                </span>
                                @if($latestEmploymentProof)
                                    <span data-tooltip="Employment proof PDF review status" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.08em] {{ $employmentProofStatusClasses($latestEmploymentProof) }}">
                                        <i class='bx bx-file-find text-sm'></i>
                                        {{ $employmentProofStatusLabel }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="portal-row-actions px-7 py-5 align-top" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    title="{{ $canNotifyAlumni ? 'Send Notice, Reminder, or Warning to this registered alumni account' : 'This alumni is not registered yet and cannot receive portal notifications' }}"
                                    data-tooltip="{{ $canNotifyAlumni ? 'Send Notice, Reminder, or Warning to this alumni account' : 'This alumni is not registered yet and cannot receive portal notifications' }}"
                                    data-alumni-notify-open
                                    data-alumni-notify-action="{{ route('admin.alumni.notify', $alum->id) }}"
                                    data-alumni-notify-name="{{ $fullName }}"
                                    data-alumni-notify-email="{{ $alum->email }}"
                                    aria-label="{{ $canNotifyAlumni ? 'Send notification to ' . $fullName : 'Notification unavailable for unregistered alumni' }}"
                                    class="inline-flex h-10 items-center justify-center gap-2 rounded-full border px-3 shadow-sm transition-all {{ $canNotifyAlumni ? 'border-amber-200 bg-amber-50 text-amber-700 hover:-translate-y-0.5 hover:bg-amber-100 hover:shadow-md' : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400' }}"
                                    @disabled(! $canNotifyAlumni)
                                >
                                    <i class='bx bx-bell-plus text-lg'></i>
                                    <span class="text-xs font-extrabold">Notify</span>
                                </button>
                                <a
                                    data-tooltip="Edit this alumni record"
                                    href="{{ route('admin.alumni.edit', $alum->id) }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-800 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-emerald-100 hover:shadow-md"
                                >
                                    <i class='bx bx-edit text-lg'></i>
                                    <span class="sr-only">Edit alumni record</span>
                                </a>
                                <form
                                    action="{{ route('admin.alumni.delete', $alum->id) }}"
                                    method="POST"
                                    class="inline"
                                    data-confirm-submit="Delete this alumni record? This action cannot be undone."
                                    data-confirm-title="Delete alumni record"
                                    data-confirm-label="Delete Record"
                                    data-confirm-tone="danger"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button data-tooltip="Permanently delete this alumni record" type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-rose-100 hover:shadow-md">
                                        <i class='bx bx-trash text-lg'></i>
                                        <span class="sr-only">Delete alumni record</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10">
                            <div class="empty-state-card mx-auto max-w-lg">
                                <span class="empty-state-card__icon">
                                    <i class='bx bx-user-x'></i>
                                </span>
                                <p class="empty-state-card__title">No alumni records found</p>
                                <p class="empty-state-card__body">Try different filters or import an alumni batch.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination-footer :paginator="$alumni" data-alumni-pagination>
            {{ $alumni->fragment('alumni-directory')->links() }}
        </x-pagination-footer>
    </div>
</div>

<div
    id="alumniNotifyModal"
    class="fixed inset-0 z-50 hidden overflow-hidden"
    aria-hidden="true"
    data-alumni-notify-modal
>
    <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" data-alumni-notify-close></div>
    <div class="relative flex min-h-full items-center justify-center px-4 py-6">
        <div class="w-full max-w-2xl overflow-hidden rounded-[1.75rem] border border-white/80 bg-white shadow-[0_30px_90px_rgba(15,23,42,0.30)]">
            <div class="flex items-start justify-between gap-5 border-b border-slate-200 bg-gradient-to-r from-white via-amber-50/80 to-white px-6 py-6">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Direct Alumni Notification</p>
                    <h2 class="mt-2 text-2xl font-extrabold leading-tight text-slate-950">Send notification</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        This message will appear in the selected alumni account notification page.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition-all hover:-translate-y-0.5 hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Close notification modal"
                    data-alumni-notify-close
                >
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <form method="POST" action="#" class="space-y-5 px-6 py-6" data-alumni-notify-form>
                @csrf
                <div class="rounded-2xl border border-amber-100 bg-amber-50/70 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-800">Recipient</p>
                    <p class="mt-1 text-base font-extrabold text-slate-950" data-alumni-notify-recipient-name>Selected Alumni</p>
                    <p class="mt-1 break-all text-sm text-slate-600" data-alumni-notify-recipient-email></p>
                </div>

                <div class="grid gap-4 md:grid-cols-[13rem_minmax(0,1fr)]">
                    <div>
                        <label for="alumni_notification_level" class="block text-sm font-bold text-slate-900">Message type</label>
                        <select
                            id="alumni_notification_level"
                            name="level"
                            class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-100"
                            data-alumni-notify-level
                            required
                        >
                            <option value="notice">Notice</option>
                            <option value="reminder" selected>Reminder</option>
                            <option value="warning">Warning</option>
                        </select>
                    </div>
                    <div>
                        <label for="alumni_notification_title" class="block text-sm font-bold text-slate-900">Short title</label>
                        <input
                            id="alumni_notification_title"
                            type="text"
                            name="title"
                            maxlength="120"
                            value="Please update your alumni profile"
                            class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-100"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label for="alumni_notification_message" class="block text-sm font-bold text-slate-900">Full message</label>
                    <textarea
                        id="alumni_notification_message"
                        name="message"
                        rows="5"
                        maxlength="1000"
                        class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm leading-6 text-slate-800 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-100"
                        placeholder="Write the reminder, warning, or instruction for this alumni account."
                        required
                    ></textarea>
                    <p class="mt-2 text-xs text-slate-500">Use the short title for the notification heading and the full message for the detailed instruction.</p>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50" data-alumni-notify-close>
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex justify-center gap-2 rounded-2xl bg-amber-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm transition-all hover:bg-amber-700">
                        <i class='bx bx-send'></i>
                        Send Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div
    id="alumniBatchImportModal"
    class="fixed inset-0 z-50 overflow-hidden {{ $batchImportModalOpen ? '' : 'hidden' }}"
    aria-hidden="{{ $batchImportModalOpen ? 'false' : 'true' }}"
>
    <div id="alumniBatchImportModalBackdrop" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"></div>
    <div class="relative h-full overflow-y-auto overflow-x-hidden overscroll-contain">
        <div class="flex min-h-full items-start justify-center px-4 py-5 sm:px-6 sm:py-8 lg:items-center">
            <div class="flex w-full max-w-4xl flex-col overflow-hidden rounded-[2rem] border border-white/75 bg-white shadow-[0_30px_90px_rgba(15,23,42,0.30)]">
                <div class="flex items-start justify-between gap-5 border-b border-slate-200 bg-gradient-to-r from-white via-emerald-50/80 to-white px-6 py-6 sm:px-8">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Batch Import</p>
                        <h2 class="mt-2 text-3xl font-extrabold leading-tight text-slate-950">Add Alumni Batch</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Upload a CSV file with the required columns. The system compares the selected file with the template, ignores extra columns, then adds or updates matching alumni records.</p>
                    </div>
                    <button
                        type="button"
                        id="closeAlumniBatchImportModal"
                        class="inline-flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-100 hover:text-slate-700 hover:shadow-md"
                        aria-label="Close batch import"
                    >
                        <i class='bx bx-x text-2xl'></i>
                    </button>
                </div>

                <form action="{{ route('admin.alumni.import-batch') }}" method="POST" enctype="multipart/form-data" class="space-y-6 px-6 py-6 sm:px-8 sm:py-7">
                    @csrf

                    <div class="rounded-[1.35rem] border border-slate-200 bg-white p-5 text-sm shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                                    <i class='bx bx-spreadsheet text-xl'></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="font-extrabold text-slate-950">CSV import checklist</p>
                                    <p class="mt-1 leading-6 text-slate-600">The selected file will be checked before importing alumni records.</p>
                                </div>
                            </div>
                            <div class="grid min-w-0 gap-2 text-xs text-slate-600 sm:grid-cols-2 lg:w-[30rem]">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="font-extrabold uppercase tracking-[0.12em] text-slate-500">Required</p>
                                    <p class="mt-1 leading-5">Template columns must be present.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="font-extrabold uppercase tracking-[0.12em] text-slate-500">Ignored</p>
                                    <p class="mt-1 leading-5">Extra columns are skipped automatically.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <label for="alumniBatchFileInput" class="block text-sm font-extrabold text-slate-900">CSV file</label>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Choose a file to check it against the template before importing.</p>
                            </div>
                            <button
                                type="button"
                                id="viewAlumniBatchTemplate"
                                aria-controls="alumniBatchTemplatePreview"
                                aria-expanded="false"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 text-sm font-extrabold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-slate-50 hover:shadow-md"
                            >
                                <i id="alumniBatchTemplateToggleIcon" class='bx bx-show text-lg'></i>
                                <span id="alumniBatchTemplateToggleLabel">View Template</span>
                            </button>
                        </div>

                        <div
                            id="alumniBatchTemplatePreview"
                            class="rounded-[1.35rem] border border-emerald-100 bg-emerald-50/50 p-5"
                            data-alumni-batch-template-preview
                            hidden
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm font-extrabold text-slate-950">Template preview</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">Use these column titles when preparing the CSV file. Birthdate is used for stronger signup verification, while the student ID value may be left blank when unavailable.</p>
                                    <p class="mt-2 text-xs font-bold uppercase tracking-[0.12em] text-emerald-700">Header-only template</p>
                                </div>
                                <a
                                    href="{{ route('admin.alumni.batch-template') }}"
                                    data-native-nav
                                    class="inline-flex min-h-11 flex-shrink-0 items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-white px-5 text-sm font-extrabold text-emerald-800 shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-md"
                                >
                                    <i class='bx bx-download text-lg'></i>
                                    Download CSV
                                </a>
                            </div>

                            <div class="mt-4 max-h-[28rem] overflow-auto rounded-2xl border border-emerald-100 bg-white shadow-sm">
                                <table class="min-w-[68rem] divide-y divide-emerald-100 text-left text-xs">
                                    <thead class="sticky top-0 z-10 bg-emerald-50/95 backdrop-blur">
                                        <tr>
                                            @foreach($batchImportColumns as $column)
                                                <th class="px-3 py-3 font-extrabold uppercase tracking-[0.08em] text-emerald-900">{{ $column }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr>
                                            <td colspan="{{ count($batchImportColumns) }}" class="px-3 py-6 text-center text-sm font-semibold text-slate-500">
                                                No sample alumni rows are generated. The imported batch size will come from the CSV file selected by the admin.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="alumni-file-upload">
                            <div
                                class="alumni-file-upload__dropzone rounded-[1.35rem] border px-4 py-4 sm:px-5"
                                data-alumni-batch-file-dropzone
                            >
                                <input
                                    id="alumniBatchFileInput"
                                    name="batch_files[]"
                                    type="file"
                                    accept=".csv,text/csv"
                                    multiple
                                    required
                                    class="sr-only"
                                >
                                <span class="relative z-10 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-4 sm:grid-cols-[auto_minmax(0,1fr)_auto]">
                                    <span class="alumni-file-upload__icon inline-flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-700 text-white shadow-lg shadow-emerald-900/20">
                                        <i class='bx bx-cloud-upload text-3xl'></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-extrabold text-slate-950">Upload alumni CSV</span>
                                        <span class="mt-1 block truncate text-sm font-semibold text-slate-500" data-alumni-batch-file-name>No CSV selected yet. Maximum 3 files.</span>
                                    </span>
                                    <span class="col-span-2 flex flex-wrap items-center justify-start gap-2 sm:col-span-1 sm:justify-end">
                                        <button
                                            type="button"
                                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-white px-5 text-sm font-extrabold text-emerald-800 shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                            data-alumni-batch-file-action
                                        >
                                            <i class='bx bx-folder-open text-lg'></i>
                                            Choose File
                                        </button>
                                        <button
                                            type="button"
                                            class="hidden min-h-11 w-11 items-center justify-center rounded-2xl border border-rose-200 bg-white text-rose-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-rose-50 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-rose-100"
                                            data-alumni-batch-file-clear
                                            aria-label="Clear selected file"
                                        >
                                            <i class='bx bx-x text-xl'></i>
                                        </button>
                                    </span>
                                </span>
                            </div>
                        </div>
                        <p class="hidden text-sm font-semibold text-red-700" data-alumni-batch-file-error></p>
                        <div
                            id="alumniBatchTemplateComparison"
                            class="hidden rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm"
                            data-alumni-batch-comparison
                            aria-live="polite"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    id="alumniBatchComparisonIcon"
                                    class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600"
                                >
                                    <i class='bx bx-git-compare text-xl'></i>
                                </span>
                                <div class="min-w-0">
                                    <p id="alumniBatchComparisonTitle" class="text-sm font-extrabold text-slate-950">CSV file check</p>
                                    <p id="alumniBatchComparisonSummary" class="mt-1 text-sm leading-6 text-slate-600">Choose a CSV file to compare its columns with the required template.</p>
                                </div>
                            </div>
                            <div id="alumniBatchComparisonDetails" class="mt-4 space-y-3"></div>
                        </div>
                        <div class="flex items-start gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">
                            <i class='bx bx-info-circle mt-0.5 text-base text-emerald-700'></i>
                            <p>Upload CSV files only. If your file is `.xlsx`, open it in Excel and use Save As CSV before uploading. You can choose up to 3 files.</p>
                        </div>
                        <div>
                            @php
                                $batchFileErrors = collect($errors->getBag('default')->messages())
                                    ->filter(fn ($messages, $key) => $key === 'batch_file' || $key === 'batch_files' || str_starts_with((string) $key, 'batch_files.'))
                                    ->flatten();
                            @endphp
                            @foreach($batchFileErrors as $message)
                                <p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-start gap-3 rounded-[1.35rem] border border-slate-200 bg-slate-50/80 p-5">
                        <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm">
                            <i class='bx bx-check-shield text-xl'></i>
                        </span>
                        <div>
                            <p class="text-sm font-extrabold text-slate-950">After import</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Imported records will be added or updated, tagged with the uploaded CSV file, and made available for alumni signup verification. After import, the uploaded file will appear in the Import Batch filter.</p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse justify-end gap-3 border-t border-slate-200 pt-5 sm:flex-row">
                        <button type="button" id="cancelAlumniBatchImportModal" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 text-sm font-extrabold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" id="alumniBatchSubmitButton" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 text-sm font-extrabold text-white shadow-lg shadow-emerald-900/10 transition hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0 disabled:hover:bg-blue-600 disabled:hover:shadow-lg">
                            <i class='bx bx-upload'></i>
                            Import Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div
    id="alumniCreateModal"
    class="fixed inset-0 z-50 overflow-hidden {{ $alumniCreateModalOpen ? '' : 'hidden' }}"
    aria-hidden="{{ $alumniCreateModalOpen ? 'false' : 'true' }}"
>
    <div id="alumniCreateModalBackdrop" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"></div>
    <div class="relative h-full overflow-y-auto overflow-x-hidden overscroll-contain">
        <div class="flex min-h-full items-start justify-center px-5 py-6 sm:px-10 sm:py-8 lg:px-14 lg:items-center">
            <div class="relative flex w-full max-w-[56rem] max-h-[calc(100vh-2rem)] flex-col overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-[0_28px_80px_rgba(15,23,42,0.28)] sm:max-h-[calc(100vh-3rem)]">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-white via-emerald-50/70 to-white px-7 py-5 sm:px-8">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Alumni Record</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Add Alumni</h2>
                        <p class="mt-1 text-sm text-slate-600">Create a new alumni account without leaving the directory.</p>
                    </div>
                    <button
                        type="button"
                        id="closeAlumniCreateModal"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Close alumni form"
                    >
                        <i class='bx bx-x text-2xl'></i>
                    </button>
                </div>

                <div id="alumniCreateModalBody" class="flex-1 overflow-y-auto px-7 pb-6 pt-6 sm:px-8 sm:pt-7">
                    @if($errors->any() && old('firstname') !== null)
                        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <ul class="list-disc pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="alumniCreateForm" action="{{ route('admin.alumni.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-900">Account Information</h3>
                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">First Name</label>
                                    <input id="alumni_firstname_input" type="text" name="firstname" value="{{ old('firstname') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Last Name</label>
                                    <input id="alumni_lastname_input" type="text" name="lastname" value="{{ old('lastname') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>
                                <div class="md:col-span-2">
                                    <p id="alumniDuplicateCheckMessage" class="text-sm text-gray-500" aria-live="polite"></p>
                                    <div id="alumniNameMatchSuggestions" class="hidden"></div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                                    <input id="alumni_email_input" type="email" name="email" value="{{ old('email') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <p id="alumniEmailCheckMessage" class="mt-1 text-sm text-gray-500" aria-live="polite"></p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                                    <div class="relative">
                                        <input id="alumni_password_input" type="password" name="password" class="w-full rounded-md border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <button type="button" id="toggleAlumniPassword" class="absolute inset-y-0 right-2 inline-flex items-center text-gray-500 hover:text-gray-700" aria-label="Toggle password visibility">
                                            <i id="alumni_password_toggle_icon" class='bx bx-show text-lg'></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Confirm Password</label>
                                    <div class="relative">
                                        <input id="alumni_password_confirmation_input" type="password" name="password_confirmation" class="w-full rounded-md border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <button type="button" id="toggleAlumniPasswordConfirmation" class="absolute inset-y-0 right-2 inline-flex items-center text-gray-500 hover:text-gray-700" aria-label="Toggle password confirmation visibility">
                                            <i id="alumni_password_confirmation_toggle_icon" class='bx bx-show text-lg'></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-slate-900">Education and Employment</h3>
                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">College</label>
                                    <select id="alumni_modal_department_select" name="department" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select College</option>
                                        @foreach($departmentOptions as $option)
                                            <option value="{{ $option }}" {{ old('department') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Degree</label>
                                    <select id="alumni_modal_degree_select" name="degree" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" {{ $selectedDepartment === '' ? 'disabled' : '' }}>
                                        <option value="">{{ $selectedDepartment === '' ? 'Select college first' : 'Select Degree / Program' }}</option>
                                        @foreach($degreeOptions as $option)
                                            <option value="{{ $option }}" {{ old('degree') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                        @if($selectedDegree !== '' && !in_array($selectedDegree, $degreeOptions, true))
                                            <option value="{{ $selectedDegree }}" selected>{{ $selectedDegree }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Batch</label>
                                    <select id="alumni_year_graduated_select" name="year_graduated" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Year</option>
                                        @for($year = $currentYear; $year >= 1990; $year--)
                                            <option value="{{ $year }}" {{ (string) old('year_graduated') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Employment Status</label>
                                    <select id="alumni_modal_employment_status_select" name="employed_after_grad" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Unknown</option>
                                        <option value="Yes" {{ old('employed_after_grad') === 'Yes' ? 'selected' : '' }}>Employed</option>
                                        <option value="No" {{ old('employed_after_grad') === 'No' ? 'selected' : '' }}>Unemployed</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Industry</label>
                                    <select id="alumni_modal_industry_select" name="industry" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Industry</option>
                                        @foreach($industryOptions as $option)
                                            <option value="{{ $option }}" {{ old('industry') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Current Position</label>
                                    <input id="alumni_modal_current_position_input" type="text" name="current_position" value="{{ old('current_position') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Current Company / Business</label>
                                    <input id="alumni_modal_company_input" type="text" name="company" value="{{ old('company') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter company or business name">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Location</label>
                                    <select id="alumni_modal_location_select" name="location" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Location</option>
                                        @foreach($locationOptions as $option)
                                            <option value="{{ $option }}" {{ old('location') === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <div id="alumni_modal_specific_location_group" class="mt-3 {{ old('location') === 'Others' ? '' : 'hidden' }}">
                                        <label class="mb-1 block text-sm font-medium text-gray-700">Specific Location</label>
                                        <input id="alumni_modal_specific_location_input" type="text" name="specific_location" value="{{ old('specific_location') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter specific location">
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Salary Range</label>
                                    <select id="alumni_modal_salary_range_select" name="salary_range" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Salary Range</option>
                                        @foreach($salaryOptions as $option)
                                            <option value="{{ $option }}" {{ $selectedSalaryRange === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-col-reverse justify-end gap-3 border-t border-slate-200 pt-6 sm:flex-row">
                            <button type="button" id="cancelAlumniCreateModal" class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200">
                                Cancel
                            </button>
                            <button type="button" id="openAlumniCredentialOverview" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                                <i class='bx bx-user-plus'></i>
                                Create Alumni
                            </button>
                        </div>
                    </form>
                </div>

                <div id="alumniCredentialOverview" class="absolute inset-0 z-20 hidden bg-slate-950/35 backdrop-blur-sm">
                    <div class="flex h-full items-center justify-center p-4 sm:p-6">
                        <div class="w-full max-w-xl rounded-[1.5rem] border border-white/80 bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.22)]">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Login Credentials Overview</p>
                            <h3 class="mt-2 text-2xl font-bold text-slate-900">Review Before Creating</h3>
                            <p class="mt-1 text-sm text-slate-600">Double-check these login details before creating the alumni account.</p>

                            <div class="mt-5 grid grid-cols-1 gap-4">
                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Email</p>
                                    <p id="alumni_credentials_email_overview" class="mt-2 break-all font-mono text-sm text-slate-900">No email entered yet.</p>
                                </div>
                                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 px-4 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Password</p>
                                    <p id="alumni_credentials_password_overview" class="mt-2 break-all font-mono text-sm text-slate-900">No password entered yet.</p>
                                </div>
                            </div>

                            <p class="mt-4 text-xs text-slate-500">This overview helps the admin remember what will be shared with the alumni.</p>

                            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" id="closeAlumniCredentialOverview" class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-200">
                                    Back
                                </button>
                                <button type="button" id="confirmAlumniCredentialOverview" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-700">
                                    <i class='bx bx-check-circle'></i>
                                    Confirm Create Alumni
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function initializeAlumniNotifyModal() {
        const modal = document.querySelector('[data-alumni-notify-modal]');
        const form = document.querySelector('[data-alumni-notify-form]');
        const recipientName = document.querySelector('[data-alumni-notify-recipient-name]');
        const recipientEmail = document.querySelector('[data-alumni-notify-recipient-email]');
        const titleInput = document.getElementById('alumni_notification_title');
        const messageInput = document.getElementById('alumni_notification_message');
        const levelSelect = document.querySelector('[data-alumni-notify-level]');

        if (!modal || !form || modal.dataset.notifyReady === 'true') {
            return;
        }

        modal.dataset.notifyReady = 'true';

        const presets = {
            notice: {
                title: 'Account notice from CMU OARL',
                message: 'Please open your CMU OARL alumni portal account and review the latest account notice from the Alumni Relations and Linkages Office.',
            },
            reminder: {
                title: 'Please update your alumni profile',
                message: 'Please update your alumni profile and tracer information in the CMU OARL alumni portal so the office can maintain accurate graduate records and employment analytics.',
            },
            warning: {
                title: 'Important account reminder',
                message: 'Please review your alumni account information as soon as possible. Some details may be incomplete or outdated and may require correction for official tracer records.',
            },
        };

        const setPreset = (level, force = false) => {
            const preset = presets[level] || presets.notice;

            if (force || !titleInput.value.trim() || Object.values(presets).some((item) => item.title === titleInput.value.trim())) {
                titleInput.value = preset.title;
            }

            if (force || !messageInput.value.trim() || Object.values(presets).some((item) => item.message === messageInput.value.trim())) {
                messageInput.value = preset.message;
            }
        };

        const openModal = (trigger) => {
            const action = trigger.getAttribute('data-alumni-notify-action') || '#';
            const name = trigger.getAttribute('data-alumni-notify-name') || 'Selected Alumni';
            const email = trigger.getAttribute('data-alumni-notify-email') || '';

            form.setAttribute('action', action);
            recipientName.textContent = name;
            recipientEmail.textContent = email;
            levelSelect.value = 'reminder';
            setPreset('reminder', true);

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            window.setTimeout(() => titleInput?.focus({ preventScroll: true }), 80);
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-alumni-notify-open]');
            if (trigger) {
                event.preventDefault();
                event.stopPropagation();

                if (!trigger.disabled) {
                    openModal(trigger);
                }

                return;
            }

            if (event.target.closest('[data-alumni-notify-close]')) {
                event.preventDefault();
                closeModal();
            }
        }, { capture: true });

        levelSelect?.addEventListener('change', () => setPreset(levelSelect.value));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }

    function initializeAlumniBatchImportModal() {
        const modal = document.getElementById('alumniBatchImportModal');
        const openButton = document.getElementById('openAlumniBatchImportModal');
        const closeButton = document.getElementById('closeAlumniBatchImportModal');
        const cancelButton = document.getElementById('cancelAlumniBatchImportModal');
        const backdrop = document.getElementById('alumniBatchImportModalBackdrop');
        const templateToggleButton = document.getElementById('viewAlumniBatchTemplate');
        const templatePreview = document.getElementById('alumniBatchTemplatePreview');
        const templateToggleIcon = document.getElementById('alumniBatchTemplateToggleIcon');
        const templateToggleLabel = document.getElementById('alumniBatchTemplateToggleLabel');
        const batchFileInput = document.getElementById('alumniBatchFileInput');
        const batchFileDropzone = document.querySelector('[data-alumni-batch-file-dropzone]');
        const batchFileName = document.querySelector('[data-alumni-batch-file-name]');
        const batchFileAction = document.querySelector('[data-alumni-batch-file-action]');
        const batchFileClear = document.querySelector('[data-alumni-batch-file-clear]');
        const batchFileError = document.querySelector('[data-alumni-batch-file-error]');
        const batchSubmitButton = document.getElementById('alumniBatchSubmitButton');
        const batchComparison = document.getElementById('alumniBatchTemplateComparison');
        const batchComparisonIcon = document.getElementById('alumniBatchComparisonIcon');
        const batchComparisonTitle = document.getElementById('alumniBatchComparisonTitle');
        const batchComparisonSummary = document.getElementById('alumniBatchComparisonSummary');
        const batchComparisonDetails = document.getElementById('alumniBatchComparisonDetails');
        const templateColumns = @json($batchImportColumns);
        const normalizedTemplateColumns = templateColumns.map((column) => normalizeCsvHeader(column));
        let comparisonRequestId = 0;

        if (!modal || !openButton || !closeButton || !cancelButton || !backdrop) {
            return;
        }

        const lockPageScroll = () => {
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        };

        const unlockPageScroll = () => {
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        };

        const setTemplatePreviewVisible = (isVisible) => {
            if (!templateToggleButton || !templatePreview) {
                return;
            }

            templatePreview.hidden = !isVisible;
            templateToggleButton.setAttribute('aria-expanded', isVisible ? 'true' : 'false');

            if (templateToggleIcon) {
                templateToggleIcon.className = isVisible ? 'bx bx-hide text-lg' : 'bx bx-show text-lg';
            }

            if (templateToggleLabel) {
                templateToggleLabel.textContent = isVisible ? 'Hide Template' : 'View Template';
            }
        };

        const showBatchFileError = (message) => {
            if (!batchFileError) {
                return;
            }

            batchFileError.textContent = message;
            batchFileError.classList.remove('hidden');
        };

        const clearBatchFileError = () => {
            if (!batchFileError) {
                return;
            }

            batchFileError.textContent = '';
            batchFileError.classList.add('hidden');
        };

        function normalizeCsvHeader(value) {
            return String(value ?? '')
                .replace(/^\uFEFF/, '')
                .trim()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        }

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const setBatchSubmitEnabled = (isEnabled) => {
            if (!batchSubmitButton) {
                return;
            }

            batchSubmitButton.disabled = !isEnabled;
        };

        const resetBatchComparison = () => {
            comparisonRequestId++;

            if (batchComparison) {
                batchComparison.classList.add('hidden');
                batchComparison.className = 'hidden rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm';
            }

            if (batchComparisonIcon) {
                batchComparisonIcon.className = 'inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600';
                batchComparisonIcon.innerHTML = "<i class='bx bx-git-compare text-xl'></i>";
            }

            if (batchComparisonTitle) {
                batchComparisonTitle.textContent = 'CSV file check';
            }

            if (batchComparisonSummary) {
                batchComparisonSummary.textContent = 'Choose a CSV file to compare its columns with the required template.';
            }

            if (batchComparisonDetails) {
                batchComparisonDetails.innerHTML = '';
            }

            setBatchSubmitEnabled(false);
        };

        const parseCsvFirstRow = (text) => {
            const cells = [];
            let cell = '';
            let inQuotes = false;

            for (let index = 0; index < text.length; index++) {
                const character = text[index];
                const nextCharacter = text[index + 1];

                if (character === '"') {
                    if (inQuotes && nextCharacter === '"') {
                        cell += '"';
                        index++;
                    } else {
                        inQuotes = !inQuotes;
                    }

                    continue;
                }

                if (!inQuotes && character === ',') {
                    cells.push(cell);
                    cell = '';
                    continue;
                }

                if (!inQuotes && (character === '\n' || character === '\r')) {
                    if (character === '\r' && nextCharacter === '\n') {
                        index++;
                    }

                    cells.push(cell);
                    return cells;
                }

                cell += character;
            }

            cells.push(cell);

            return cells;
        };

        const compareCsvHeaders = (headers) => {
            const actualHeaders = headers.map((header) => String(header ?? '').trim());
            const actualNormalized = actualHeaders.map((header) => normalizeCsvHeader(header));
            const actualSet = new Set(actualNormalized.filter(Boolean));
            const expectedSet = new Set(normalizedTemplateColumns);
            const missing = templateColumns.filter((column, index) => !actualSet.has(normalizedTemplateColumns[index]));
            const extra = actualHeaders.filter((header, index) => {
                const normalized = actualNormalized[index] ?? '';

                return normalized !== '' && !expectedSet.has(normalized);
            });

            return {
                matched: templateColumns.length - missing.length,
                missing,
                extra,
                valid: missing.length === 0,
            };
        };

        const pillToneClasses = {
            slate: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
            emerald: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
            rose: 'bg-rose-50 text-rose-700 ring-1 ring-rose-100',
        };

        const resultToneClasses = {
            emerald: {
                border: 'border-slate-200',
                badge: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                status: 'Ready',
            },
            rose: {
                border: 'border-rose-100',
                badge: 'bg-rose-50 text-rose-700 ring-1 ring-rose-100',
                status: 'Needs columns',
            },
        };

        const comparisonPillList = (items, emptyLabel, tone = 'slate') => {
            const classes = pillToneClasses[tone] ?? pillToneClasses.slate;

            if (!items.length) {
                return `<span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ${classes}">${escapeHtml(emptyLabel)}</span>`;
            }

            return items.map((item) => `<span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ${classes}">${escapeHtml(item)}</span>`).join('');
        };

        const showBatchComparisonState = (state, summary, detailsHtml = '') => {
            if (!batchComparison) {
                return;
            }

            const stateClasses = {
                loading: {
                    panel: 'rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm',
                    icon: 'inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600',
                    iconHtml: "<i class='bx bx-loader-alt bx-spin text-xl'></i>",
                    title: 'Checking CSV file',
                },
                valid: {
                    panel: 'rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm',
                    icon: 'inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700',
                    iconHtml: "<i class='bx bx-check-shield text-xl'></i>",
                    title: 'CSV file check',
                },
                invalid: {
                    panel: 'rounded-[1.35rem] border border-rose-200 bg-white p-4 shadow-sm',
                    icon: 'inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-700',
                    iconHtml: "<i class='bx bx-error-circle text-xl'></i>",
                    title: 'CSV file check',
                },
            };

            const classes = stateClasses[state] ?? stateClasses.loading;
            batchComparison.className = classes.panel;
            batchComparison.classList.remove('hidden');

            if (batchComparisonIcon) {
                batchComparisonIcon.className = classes.icon;
                batchComparisonIcon.innerHTML = classes.iconHtml;
            }

            if (batchComparisonTitle) {
                batchComparisonTitle.textContent = classes.title;
            }

            if (batchComparisonSummary) {
                batchComparisonSummary.textContent = summary;
            }

            if (batchComparisonDetails) {
                batchComparisonDetails.innerHTML = detailsHtml;
            }
        };

        const compareBatchFiles = async (files) => {
            const selectedFiles = Array.from(files ?? []);

            if (selectedFiles.length === 0) {
                resetBatchComparison();
                return;
            }

            const requestId = ++comparisonRequestId;
            setBatchSubmitEnabled(false);
            showBatchComparisonState('loading', 'Checking column titles against the required template.');

            const results = await Promise.all(selectedFiles.map(async (file) => {
                try {
                    const headerText = await file.slice(0, 65536).text();
                    const headers = parseCsvFirstRow(headerText);
                    const comparison = compareCsvHeaders(headers);

                    return {
                        fileName: file.name,
                        headers,
                        ...comparison,
                    };
                } catch (error) {
                    return {
                        fileName: file.name,
                        headers: [],
                        matched: 0,
                        missing: templateColumns,
                        extra: [],
                        valid: false,
                        error: 'The file could not be read.',
                    };
                }
            }));

            if (requestId !== comparisonRequestId) {
                return;
            }

            const invalidResults = results.filter((result) => !result.valid);
            const totalExtra = results.reduce((total, result) => total + result.extra.length, 0);
            const detailHtml = results.map((result, resultIndex) => {
                const fileTone = result.valid ? 'emerald' : 'rose';
                const toneClasses = resultToneClasses[fileTone] ?? resultToneClasses.rose;
                const missingHtml = comparisonPillList(result.missing, 'None', result.valid ? 'slate' : 'rose');
                const extraHtml = comparisonPillList(result.extra, 'None', 'slate');
                const errorHtml = result.error ? `<p class="mt-2 text-xs font-bold text-rose-700">${escapeHtml(result.error)}</p>` : '';

                return `
                    <div class="rounded-2xl border ${toneClasses.border} bg-slate-50/70 px-4 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="break-all text-sm font-extrabold text-slate-950">${escapeHtml(result.fileName)}</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-extrabold ${toneClasses.badge}">${toneClasses.status}</span>
                                <button
                                    type="button"
                                    class="inline-flex min-h-9 items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 text-xs font-extrabold text-slate-600 shadow-sm transition hover:-translate-y-0.5 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-100"
                                    data-alumni-batch-remove-file="${resultIndex}"
                                    aria-label="Cancel upload for ${escapeHtml(result.fileName)}"
                                >
                                    <i class='bx bx-x text-base'></i>
                                    Cancel
                                </button>
                            </div>
                        </div>
                        <dl class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-3">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <dt class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Required columns</dt>
                                <dd class="mt-2 text-sm font-extrabold text-slate-900">${result.matched} of ${templateColumns.length} found</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <dt class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Missing columns</dt>
                                <dd class="mt-2 flex flex-wrap gap-2">${missingHtml}</dd>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <dt class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">Ignored columns</dt>
                                <dd class="mt-2 flex flex-wrap gap-2">${extraHtml}</dd>
                            </div>
                        </dl>
                        ${errorHtml}
                    </div>
                `;
            }).join('');

            if (invalidResults.length > 0) {
                const firstInvalid = invalidResults[0];
                showBatchComparisonState('invalid', `${firstInvalid.fileName} cannot be imported yet. Add the missing required columns shown below.`, detailHtml);
                setBatchSubmitEnabled(false);
                return;
            }

            const extraMessage = totalExtra > 0
                ? ' Columns outside the template will be skipped.'
                : '';
            showBatchComparisonState('valid', `Ready: all required columns were found.${extraMessage}`, detailHtml);
            setBatchSubmitEnabled(true);
        };

        const removeBatchFileAtIndex = (removeIndex) => {
            if (!batchFileInput) {
                return;
            }

            const files = Array.from(batchFileInput.files ?? []);

            if (!Number.isInteger(removeIndex) || removeIndex < 0 || removeIndex >= files.length) {
                return;
            }

            const remainingFiles = files.filter((file, index) => index !== removeIndex);

            try {
                const transfer = new DataTransfer();
                remainingFiles.forEach((file) => transfer.items.add(file));
                batchFileInput.files = transfer.files;
            } catch (error) {
                showBatchFileError('Unable to remove only one file in this browser. Please choose the CSV files again.');
                resetBatchFileInput();
                return;
            }

            clearBatchFileError();
            validateBatchFiles();
        };

        const resetBatchFileInput = () => {
            if (batchFileInput) {
                batchFileInput.value = '';
            }

            resetBatchComparison();
            setBatchFileDisplay([]);
        };

        const setBatchFileDisplay = (files) => {
            if (!batchFileName || !batchFileDropzone || !batchFileAction || !batchFileClear) {
                return;
            }

            const selectedFiles = Array.from(files ?? []);

            if (selectedFiles.length === 0) {
                batchFileName.textContent = 'No CSV selected yet. Maximum 3 files.';
                batchFileAction.innerHTML = "<i class='bx bx-folder-open text-lg'></i>Choose File";
                batchFileClear.classList.add('hidden');
                batchFileClear.classList.remove('inline-flex');
                batchFileDropzone.classList.remove('has-file');
                return;
            }

            const names = selectedFiles.map((file) => file.name).join(', ');
            batchFileName.textContent = names;
            batchFileAction.innerHTML = `<i class='bx bx-check-circle text-lg'></i>${selectedFiles.length === 1 ? 'File selected' : `${selectedFiles.length} files selected`}`;
            batchFileClear.classList.remove('hidden');
            batchFileClear.classList.add('inline-flex');
            batchFileDropzone.classList.add('has-file');
        };

        const validateBatchFiles = () => {
            const files = Array.from(batchFileInput?.files ?? []);

            clearBatchFileError();

            if (files.length === 0) {
                setBatchFileDisplay([]);
                resetBatchComparison();
                return;
            }

            if (files.length > 3) {
                showBatchFileError('You can upload a maximum of 3 CSV files at one time.');
                resetBatchFileInput();
                return;
            }

            const invalidFile = files.find((file) => !file.name.toLowerCase().endsWith('.csv'));

            if (invalidFile) {
                showBatchFileError(`${invalidFile.name} is not a CSV file. Please choose .csv files only.`);
                resetBatchFileInput();
                return;
            }

            setBatchFileDisplay(files);
            compareBatchFiles(files);
        };

        const showModal = () => {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            openButton.setAttribute('aria-expanded', 'true');
            lockPageScroll();
        };

        const hideModal = () => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            openButton.setAttribute('aria-expanded', 'false');
            unlockPageScroll();
        };

        openButton.addEventListener('click', showModal);
        closeButton.addEventListener('click', hideModal);
        cancelButton.addEventListener('click', hideModal);
        backdrop.addEventListener('click', hideModal);
        templateToggleButton?.addEventListener('click', () => {
            const shouldShow = templatePreview?.hidden ?? false;
            setTemplatePreviewVisible(shouldShow);
        });

        batchFileInput?.addEventListener('change', () => {
            validateBatchFiles();
        });

        batchFileAction?.addEventListener('click', () => {
            batchFileInput?.click();
        });

        batchFileClear?.addEventListener('click', () => {
            clearBatchFileError();
            resetBatchFileInput();
        });

        batchComparisonDetails?.addEventListener('click', (event) => {
            const target = event.target instanceof Element
                ? event.target
                : event.target?.parentElement;
            const removeButton = target?.closest('[data-alumni-batch-remove-file]');

            if (!removeButton || !batchComparisonDetails.contains(removeButton)) {
                return;
            }

            const removeIndex = Number.parseInt(removeButton.getAttribute('data-alumni-batch-remove-file') ?? '', 10);
            removeBatchFileAtIndex(removeIndex);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                hideModal();
            }
        });

        if (modal.getAttribute('aria-hidden') === 'false') {
            lockPageScroll();
        }

        setBatchSubmitEnabled(false);
    }

    function toggleAlumniPasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (!input || !icon) {
            return;
        }

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.className = isPassword ? 'bx bx-hide text-lg' : 'bx bx-show text-lg';
    }

    function initializeAlumniCreateModal() {
        const modal = document.getElementById('alumniCreateModal');
        const modalBody = document.getElementById('alumniCreateModalBody');
        const form = document.getElementById('alumniCreateForm');
        const openButton = document.getElementById('openAlumniCreateModal');
        const closeButton = document.getElementById('closeAlumniCreateModal');
        const cancelButton = document.getElementById('cancelAlumniCreateModal');
        const backdrop = document.getElementById('alumniCreateModalBackdrop');
        const credentialOverview = document.getElementById('alumniCredentialOverview');
        const openCredentialOverviewButton = document.getElementById('openAlumniCredentialOverview');
        const closeCredentialOverviewButton = document.getElementById('closeAlumniCredentialOverview');
        const confirmCredentialOverviewButton = document.getElementById('confirmAlumniCredentialOverview');
        const firstnameInput = document.getElementById('alumni_firstname_input');
        const lastnameInput = document.getElementById('alumni_lastname_input');
        const emailInput = document.getElementById('alumni_email_input');
        const yearGraduatedSelect = document.getElementById('alumni_year_graduated_select');
        const passwordInput = document.getElementById('alumni_password_input');
        const passwordToggle = document.getElementById('toggleAlumniPassword');
        const passwordConfirmationToggle = document.getElementById('toggleAlumniPasswordConfirmation');
        const emailCheckMessage = document.getElementById('alumniEmailCheckMessage');
        const duplicateCheckMessage = document.getElementById('alumniDuplicateCheckMessage');
        const nameMatchSuggestions = document.getElementById('alumniNameMatchSuggestions');
        const emailOverview = document.getElementById('alumni_credentials_email_overview');
        const passwordOverview = document.getElementById('alumni_credentials_password_overview');

        if (!modal || !form || !openButton || !closeButton || !cancelButton || !backdrop || modal.dataset.alumniLiveCheckReady === 'true') {
            return;
        }

        modal.dataset.alumniLiveCheckReady = 'true';

        let duplicateCheckTimeoutId = null;
        let duplicateCheckController = null;
        let duplicateCheckRequestToken = 0;
        let draftPersistTimeoutId = null;
        let lastAppliedDraftMarker = null;
        const createDraftStorageKey = 'cmu-arlo-admin-alumni-create-return-draft';
        const restorableFieldNames = [
            'firstname',
            'lastname',
            'email',
            'phone',
            'department',
            'degree',
            'year_graduated',
            'employed_after_grad',
            'industry',
            'current_position',
            'company',
            'location',
            'specific_location',
            'salary_range',
        ];

        const getStoredCreateDraft = () => {
            try {
                const rawValue = window.sessionStorage.getItem(createDraftStorageKey);

                if (!rawValue) {
                    return null;
                }

                const parsedValue = JSON.parse(rawValue);
                return parsedValue && typeof parsedValue === 'object' ? parsedValue : null;
            } catch (error) {
                return null;
            }
        };

        const hasCreateQueryParam = () => {
            const url = new URL(window.location.href);
            return url.searchParams.has('create');
        };

        const setCreateQueryParam = () => {
            const url = new URL(window.location.href);

            if (url.searchParams.get('create') === '1') {
                return;
            }

            url.searchParams.set('create', '1');
            window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
        };

        const clearStoredCreateDraft = () => {
            if (draftPersistTimeoutId) {
                clearTimeout(draftPersistTimeoutId);
                draftPersistTimeoutId = null;
            }

            try {
                window.sessionStorage.removeItem(createDraftStorageKey);
            } catch (error) {
                // Ignore storage access issues.
            }
        };

        const saveCreateDraftForReturn = () => {
            if (!form) {
                return;
            }

            if (draftPersistTimeoutId) {
                clearTimeout(draftPersistTimeoutId);
                draftPersistTimeoutId = null;
            }

            const values = {};

            restorableFieldNames.forEach((fieldName) => {
                const field = form.elements.namedItem(fieldName);

                if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
                    values[fieldName] = field.value;
                }
            });

            try {
                window.sessionStorage.setItem(createDraftStorageKey, JSON.stringify({
                    restoreOnReturn: true,
                    scrollTop: modalBody?.scrollTop || 0,
                    values,
                    storedAt: Date.now(),
                }));
            } catch (error) {
                // Ignore storage access issues.
            }
        };

        const queueCreateDraftSave = () => {
            if (modal.getAttribute('aria-hidden') !== 'false') {
                return;
            }

            if (draftPersistTimeoutId) {
                clearTimeout(draftPersistTimeoutId);
            }

            draftPersistTimeoutId = window.setTimeout(() => {
                setCreateQueryParam();
                saveCreateDraftForReturn();
            }, 120);
        };

        const setFieldValue = (fieldName, value) => {
            const field = form.elements.namedItem(fieldName);

            if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
                return;
            }

            field.value = value ?? '';
        };

        const draftValuesMatchCurrentForm = (storedValues = {}) => restorableFieldNames.every((fieldName) => {
            const field = form.elements.namedItem(fieldName);

            if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
                return true;
            }

            return String(field.value ?? '') === String(storedValues[fieldName] ?? '');
        });

        const restoreCreateDraftIfNeeded = () => {
            const storedDraft = getStoredCreateDraft();

            if (!storedDraft?.restoreOnReturn || !hasCreateQueryParam()) {
                return false;
            }

            const draftMarker = String(storedDraft.storedAt ?? '');
            if (draftMarker !== '' && draftMarker === lastAppliedDraftMarker && modal.getAttribute('aria-hidden') === 'false') {
                return false;
            }

            const storedValues = storedDraft.values && typeof storedDraft.values === 'object'
                ? storedDraft.values
                : {};
            const modalAlreadyOpen = modal.getAttribute('aria-hidden') === 'false';

            if (modalAlreadyOpen && draftValuesMatchCurrentForm(storedValues)) {
                lastAppliedDraftMarker = String(storedDraft.storedAt ?? '') || lastAppliedDraftMarker;
                return false;
            }

            setFieldValue('firstname', storedValues.firstname ?? '');
            setFieldValue('lastname', storedValues.lastname ?? '');
            setFieldValue('email', storedValues.email ?? '');
            setFieldValue('phone', storedValues.phone ?? '');
            setFieldValue('year_graduated', storedValues.year_graduated ?? '');
            setFieldValue('department', storedValues.department ?? '');
            form.elements.namedItem('department')?.dispatchEvent(new Event('change', { bubbles: true }));
            setFieldValue('degree', storedValues.degree ?? '');
            setFieldValue('employed_after_grad', storedValues.employed_after_grad ?? '');
            form.elements.namedItem('employed_after_grad')?.dispatchEvent(new Event('change', { bubbles: true }));

            const normalizedEmploymentStatus = String(storedValues.employed_after_grad ?? '').trim().toLowerCase();
            const employmentLocked = ['no', 'unemployed'].includes(normalizedEmploymentStatus);

            if (!employmentLocked) {
                setFieldValue('industry', storedValues.industry ?? '');
                setFieldValue('current_position', storedValues.current_position ?? '');
                setFieldValue('company', storedValues.company ?? '');
                setFieldValue('location', storedValues.location ?? '');
                form.elements.namedItem('location')?.dispatchEvent(new Event('change', { bubbles: true }));
                setFieldValue('specific_location', storedValues.specific_location ?? '');
                setFieldValue('salary_range', storedValues.salary_range ?? '');
            }

            if (!modalAlreadyOpen) {
                showModal();
            } else {
                lockPageScroll();
                setCreateQueryParam();
            }

            syncCredentialOverview();
            syncDuplicateIdleMessage();
            lastAppliedDraftMarker = draftMarker || String(Date.now());

            window.requestAnimationFrame(() => {
                if (modalBody && typeof storedDraft.scrollTop === 'number') {
                    modalBody.scrollTop = storedDraft.scrollTop;
                }

                runDuplicateCheck();
            });

            return true;
        };

        const syncCredentialOverview = () => {
            if (emailOverview) {
                const currentEmail = String(emailInput?.value || '').trim();
                emailOverview.textContent = currentEmail !== '' ? currentEmail : 'No email entered yet.';
            }

            if (passwordOverview) {
                const currentPassword = String(passwordInput?.value || '');
                passwordOverview.textContent = currentPassword !== '' ? currentPassword : 'No password entered yet.';
            }
        };

        const isValidEmailFormat = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

        const setInlineStatus = (target, status, message = '') => {
            if (!target) {
                return;
            }

            target.textContent = message;
            target.classList.remove('text-gray-500', 'text-green-700', 'text-red-600');

            if (status === 'valid') {
                target.classList.add('text-green-700');
                return;
            }

            if (status === 'invalid') {
                target.classList.add('text-red-600');
                return;
            }

            target.classList.add('text-gray-500');
        };

        const escapeHtml = (value) => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const renderNameMatches = (records = [], message = '') => {
            if (!nameMatchSuggestions) {
                return;
            }

            if (!Array.isArray(records) || records.length === 0) {
                nameMatchSuggestions.innerHTML = '';
                nameMatchSuggestions.classList.add('hidden');
                return;
            }

            const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
            const cards = records.map((record) => {
                const yearLabel = record?.year_graduated ? `Batch ${escapeHtml(record.year_graduated)}` : 'Batch not set';
                const departmentLabel = record?.department ? escapeHtml(record.department) : 'College not set';
                const emailLabel = record?.email ? escapeHtml(record.email) : 'No email';

                return `
                    <div class="rounded-2xl border border-amber-200 bg-white px-4 py-4 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">${escapeHtml(record?.name || 'Existing alumni record')}</p>
                                <p class="mt-1 text-xs text-slate-600">${yearLabel} | ${departmentLabel}</p>
                                <p class="mt-1 break-all text-xs text-slate-500">${emailLabel}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="${escapeHtml(record?.edit_url || '#')}"
                                    class="inline-flex items-center gap-1 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                                >
                                    <i class='bx bx-edit text-sm'></i>
                                    Edit
                                </a>
                                <a href="${escapeHtml(record?.show_url || '#')}" class="inline-flex items-center gap-1 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-100">
                                    <i class='bx bx-show text-sm'></i>
                                    View
                                </a>
                                <form
                                    method="POST"
                                    action="${escapeHtml(record?.delete_url || '#')}"
                                    class="inline"
                                    data-confirm-submit="Delete this alumni record? This action cannot be undone."
                                    data-confirm-title="Delete alumni record"
                                    data-confirm-label="Delete Record"
                                    data-confirm-tone="danger"
                                >
                                    <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                        <i class='bx bx-trash text-sm'></i>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            nameMatchSuggestions.innerHTML = `
                <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50/70 px-4 py-4">
                    <p class="text-sm font-semibold text-amber-900">Possible existing alumni records found</p>
                    <p class="mt-1 text-sm text-amber-800">${escapeHtml(message || 'Matching names were found. If this is the same alumni, you can edit or delete the existing record below.')}</p>
                    <div class="mt-4 space-y-3">
                        ${cards}
                    </div>
                </div>
            `;
            nameMatchSuggestions.classList.remove('hidden');
        };

        const buildDuplicateCheckSignature = () => JSON.stringify({
            firstname: String(firstnameInput?.value || '').trim().toLowerCase(),
            lastname: String(lastnameInput?.value || '').trim().toLowerCase(),
            email: String(emailInput?.value || '').trim().toLowerCase(),
            year_graduated: String(yearGraduatedSelect?.value || '').trim(),
        });

        const syncDuplicateIdleMessage = () => {
            const firstname = String(firstnameInput?.value || '').trim();
            const lastname = String(lastnameInput?.value || '').trim();
            const yearGraduated = String(yearGraduatedSelect?.value || '').trim();

            if (!firstname && !lastname) {
                setInlineStatus(duplicateCheckMessage, 'idle', '');
                return;
            }

            if (!firstname || !lastname || !yearGraduated) {
                setInlineStatus(duplicateCheckMessage, 'idle', '');
                return;
            }

            setInlineStatus(duplicateCheckMessage, 'idle', '');
        };

        const clearDuplicateValidation = () => {
            lastnameInput?.setCustomValidity('');
            yearGraduatedSelect?.setCustomValidity('');
        };

        const applyDuplicateValidation = (message = '') => {
            const validityMessage = message || '';
            lastnameInput?.setCustomValidity(validityMessage);
            yearGraduatedSelect?.setCustomValidity(validityMessage);
        };

        const applyEmailValidation = (message = '') => {
            emailInput?.setCustomValidity(message || '');
        };

        const abortDuplicateRequest = () => {
            if (duplicateCheckController) {
                duplicateCheckController.abort();
                duplicateCheckController = null;
            }
        };

        const runDuplicateCheck = async ({ force = false } = {}) => {
            const firstname = String(firstnameInput?.value || '').trim();
            const lastname = String(lastnameInput?.value || '').trim();
            const email = String(emailInput?.value || '').trim();
            const yearGraduated = String(yearGraduatedSelect?.value || '').trim();
            const emailReady = email !== '';
            const emailFormatValid = !emailReady || isValidEmailFormat(email);
            const nameMatchReady = firstname !== '' && lastname !== '';
            const duplicateReady = firstname !== '' && lastname !== '' && yearGraduated !== '';

            clearDuplicateValidation();

            if (!emailReady) {
                applyEmailValidation('');
                setInlineStatus(emailCheckMessage, 'idle', '');
            } else if (!emailFormatValid) {
                applyEmailValidation('Please enter a valid email format.');
                setInlineStatus(emailCheckMessage, 'invalid', 'Please enter a valid email format.');
                if (!nameMatchReady && !duplicateReady) {
                    if (force) {
                        emailInput?.reportValidity();
                    }
                    return false;
                }
            } else {
                applyEmailValidation('');
                setInlineStatus(emailCheckMessage, 'checking', 'Checking email availability...');
            }

            if (!duplicateReady) {
                clearDuplicateValidation();
                syncDuplicateIdleMessage();

                if (!nameMatchReady) {
                    renderNameMatches([]);
                }

                if (!nameMatchReady) {
                    if (!emailReady) {
                        return true;
                    }

                    if (!emailFormatValid) {
                        return !force;
                    }
                }
            } else {
                setInlineStatus(duplicateCheckMessage, 'checking', 'Checking for duplicate alumni records...');
            }

            if (!emailReady && !duplicateReady && !nameMatchReady) {
                return true;
            }

            const signature = buildDuplicateCheckSignature();
            duplicateCheckRequestToken += 1;
            const requestToken = duplicateCheckRequestToken;
            abortDuplicateRequest();
            duplicateCheckController = new AbortController();

            try {
                const params = new URLSearchParams({
                    firstname,
                    lastname,
                    email: emailFormatValid ? email : '',
                    year_graduated: yearGraduated,
                });

                const response = await fetch(`{{ route('admin.alumni.check-duplicates') }}?${params.toString()}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: duplicateCheckController.signal,
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const result = await response.json().catch(() => ({}));
                if (requestToken !== duplicateCheckRequestToken || signature !== buildDuplicateCheckSignature()) {
                    return false;
                }

                const emailExists = !!result?.email?.exists;
                const emailMessage = String(result?.email?.message || '');
                if (emailReady) {
                    if (emailExists) {
                        applyEmailValidation(emailMessage || 'This email is already registered.');
                        setInlineStatus(emailCheckMessage, 'invalid', emailMessage || 'This email is already registered.');
                    } else {
                        applyEmailValidation('');
                        setInlineStatus(emailCheckMessage, 'valid', emailMessage || 'Email is available.');
                    }
                }

                const nameMatchRecords = Array.isArray(result?.name_matches?.records)
                    ? result.name_matches.records
                    : [];
                const nameMatchMessage = String(result?.name_matches?.message || '');
                if (nameMatchReady) {
                    renderNameMatches(nameMatchRecords, nameMatchMessage);
                } else {
                    renderNameMatches([]);
                }

                const recordExists = !!result?.alumni_record?.exists;
                const recordMessage = String(result?.alumni_record?.message || '');
                if (duplicateReady) {
                    if (recordExists) {
                        applyDuplicateValidation(recordMessage || 'A duplicate alumni account already exists for this name and graduation year.');
                        setInlineStatus(
                            duplicateCheckMessage,
                            'invalid',
                            recordMessage || 'A duplicate alumni account already exists for this name and graduation year.'
                        );
                    } else {
                        clearDuplicateValidation();
                        setInlineStatus(
                            duplicateCheckMessage,
                            'valid',
                            recordMessage || 'No duplicate alumni record found for this name and graduation year.'
                        );
                    }
                } else {
                    clearDuplicateValidation();
                    syncDuplicateIdleMessage();
                }

                if (force) {
                    if (!emailFormatValid || emailExists) {
                        emailInput?.reportValidity();
                        return false;
                    }

                    if (recordExists) {
                        yearGraduatedSelect?.reportValidity();
                        return false;
                    }
                }

                return !emailExists && !recordExists;
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return false;
                }

                if (emailReady && isValidEmailFormat(email)) {
                    applyEmailValidation('');
                    setInlineStatus(emailCheckMessage, 'idle', 'Unable to validate email right now.');
                }

                if (!nameMatchReady) {
                    renderNameMatches([]);
                }

                if (duplicateReady) {
                    clearDuplicateValidation();
                    setInlineStatus(duplicateCheckMessage, 'idle', 'Unable to validate duplicate records right now.');
                } else {
                    syncDuplicateIdleMessage();
                }

                return true;
            }
        };

        const queueDuplicateCheck = () => {
            if (duplicateCheckTimeoutId) {
                clearTimeout(duplicateCheckTimeoutId);
            }

            duplicateCheckTimeoutId = setTimeout(() => {
                runDuplicateCheck();
            }, 350);
        };

        const showCredentialOverview = () => {
            if (!credentialOverview) {
                return;
            }

            syncCredentialOverview();
            credentialOverview.classList.remove('hidden');
        };

        const hideCredentialOverview = () => {
            credentialOverview?.classList.add('hidden');
        };

        const lockPageScroll = () => {
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        };

        const unlockPageScroll = () => {
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        };

        const releaseModalViewLock = () => {
            hideCredentialOverview();
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            openButton.setAttribute('aria-expanded', 'false');
            unlockPageScroll();
        };

        const clearCreateQueryParam = () => {
            const url = new URL(window.location.href);

            if (!url.searchParams.has('create')) {
                return;
            }

            url.searchParams.delete('create');
            window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
        };

        const showModal = () => {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            openButton.setAttribute('aria-expanded', 'true');
            setCreateQueryParam();
            lockPageScroll();
        };

        const hideModal = () => {
            releaseModalViewLock();
            lastAppliedDraftMarker = null;
            clearStoredCreateDraft();
            clearCreateQueryParam();
        };

        openButton.addEventListener('click', showModal);
        closeButton.addEventListener('click', hideModal);
        cancelButton.addEventListener('click', hideModal);
        backdrop.addEventListener('click', hideModal);
        openCredentialOverviewButton?.addEventListener('click', async () => {
            if (!form.reportValidity()) {
                return;
            }

            const passedDuplicateCheck = await runDuplicateCheck({ force: true });
            if (!passedDuplicateCheck) {
                return;
            }

            showCredentialOverview();
        });
        closeCredentialOverviewButton?.addEventListener('click', hideCredentialOverview);
        credentialOverview?.addEventListener('click', (event) => {
            if (event.target === credentialOverview) {
                hideCredentialOverview();
            }
        });
        confirmCredentialOverviewButton?.addEventListener('click', () => {
            hideCredentialOverview();
            form.requestSubmit();
        });
        nameMatchSuggestions?.addEventListener('click', (event) => {
            const actionLink = event.target instanceof Element
                ? event.target.closest('a[href]')
                : null;

            if (!actionLink || !nameMatchSuggestions.contains(actionLink)) {
                return;
            }

            saveCreateDraftForReturn();
            releaseModalViewLock();
        });
        nameMatchSuggestions?.addEventListener('submit', (event) => {
            if (!(event.target instanceof HTMLFormElement) || !nameMatchSuggestions.contains(event.target)) {
                return;
            }

            saveCreateDraftForReturn();
            releaseModalViewLock();
        });
        form.addEventListener('submit', clearStoredCreateDraft);
        form.addEventListener('input', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) {
                return;
            }

            if (!target.name || !restorableFieldNames.includes(target.name)) {
                return;
            }

            queueCreateDraftSave();
        });
        form.addEventListener('change', (event) => {
            const target = event.target;

            if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement || target instanceof HTMLTextAreaElement)) {
                return;
            }

            if (!target.name || !restorableFieldNames.includes(target.name)) {
                return;
            }

            queueCreateDraftSave();
        });
        modalBody?.addEventListener('scroll', queueCreateDraftSave, { passive: true });
        firstnameInput?.addEventListener('input', queueDuplicateCheck);
        lastnameInput?.addEventListener('input', queueDuplicateCheck);
        emailInput?.addEventListener('input', syncCredentialOverview);
        emailInput?.addEventListener('input', queueDuplicateCheck);
        emailInput?.addEventListener('blur', () => {
            runDuplicateCheck();
        });
        firstnameInput?.addEventListener('blur', () => {
            runDuplicateCheck();
        });
        lastnameInput?.addEventListener('blur', () => {
            runDuplicateCheck();
        });
        yearGraduatedSelect?.addEventListener('change', () => {
            runDuplicateCheck();
        });
        passwordInput?.addEventListener('input', syncCredentialOverview);
        passwordToggle?.addEventListener('click', () => toggleAlumniPasswordVisibility('alumni_password_input', 'alumni_password_toggle_icon'));
        passwordConfirmationToggle?.addEventListener('click', () => toggleAlumniPasswordVisibility('alumni_password_confirmation_input', 'alumni_password_confirmation_toggle_icon'));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && credentialOverview && !credentialOverview.classList.contains('hidden')) {
                hideCredentialOverview();
                return;
            }

            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                hideModal();
            }
        });

        if (modal.getAttribute('aria-hidden') === 'false') {
            lockPageScroll();
            setCreateQueryParam();
        }

        syncCredentialOverview();
        syncDuplicateIdleMessage();
        const pendingReturnDraft = !!getStoredCreateDraft()?.restoreOnReturn && hasCreateQueryParam();

        if (pendingReturnDraft) {
            window.requestAnimationFrame(restoreCreateDraftIfNeeded);
            return;
        }

        runDuplicateCheck();
    }

    function initializeAlumniPaginationScrollPreserver() {
        const pagination = document.querySelector('[data-alumni-pagination]');

        if (!pagination || pagination.dataset.alumniScrollPreserverReady === 'true') {
            return;
        }

        pagination.dataset.alumniScrollPreserverReady = 'true';

        const scrollStorageKey = 'cmu-arlo-portal-scroll-positions';

        const rememberDestinationScroll = (url) => {
            let state = {};

            try {
                const rawState = window.sessionStorage.getItem(scrollStorageKey);
                state = rawState ? JSON.parse(rawState) : {};

                if (!state || typeof state !== 'object') {
                    state = {};
                }
            } catch (error) {
                state = {};
            }

            const destination = new URL(url, window.location.href);
            const urlsToRemember = new Set([destination.toString()]);

            if (destination.hash) {
                destination.hash = '';
                urlsToRemember.add(destination.toString());
            }

            const scrollState = {
                top: Math.max(0, Math.round(Number(window.scrollY) || 0)),
                storedAt: Date.now(),
            };

            urlsToRemember.forEach((normalizedUrl) => {
                state[normalizedUrl] = scrollState;
            });

            try {
                const trimmedState = Object.fromEntries(
                    Object.entries(state)
                        .sort(([, left], [, right]) => Number(right?.storedAt || 0) - Number(left?.storedAt || 0))
                        .slice(0, 40)
                );

                window.sessionStorage.setItem(scrollStorageKey, JSON.stringify(trimmedState));
            } catch (error) {
                // Ignore browsers that block session storage.
            }
        };

        const markPaginationLinks = () => {
            pagination.querySelectorAll('a[href]').forEach((link) => {
                link.dataset.portalPreserveScroll = 'true';
                link.dataset.portalSkipAnimation = 'true';
            });
        };

        markPaginationLinks();

        pagination.addEventListener('click', (event) => {
            const target = event.target instanceof Element
                ? event.target
                : event.target?.parentElement;
            const link = target?.closest('a[href]');

            if (!link || !pagination.contains(link)) {
                return;
            }

            markPaginationLinks();
            rememberDestinationScroll(link.href);
        }, { capture: true });
    }

    function initializeAlumniStatsLiveRefresh() {
        const statsRoot = document.querySelector('[data-alumni-stats]');

        if (!statsRoot || statsRoot.dataset.alumniStatsLiveReady === 'true') {
            return;
        }

        statsRoot.dataset.alumniStatsLiveReady = 'true';

        const statsUrl = statsRoot.getAttribute('data-alumni-stats-url');
        let statsController = null;

        const updateStatValue = (key, value) => {
            const target = statsRoot.querySelector(`[data-alumni-stat-value="${key}"]`);

            if (!target) {
                return;
            }

            const formattedValue = new Intl.NumberFormat().format(Number(value) || 0);

            if (target.textContent.trim() === formattedValue) {
                return;
            }

            target.textContent = formattedValue;
            target.classList.add('scale-105');
            window.setTimeout(() => target.classList.remove('scale-105'), 180);
        };

        const refreshStats = async () => {
            if (!statsUrl || document.hidden) {
                return;
            }

            const url = new URL(statsUrl, window.location.origin);
            const params = new URLSearchParams(window.location.search);
            params.delete('page');
            url.search = params.toString();

            if (statsController) {
                statsController.abort();
            }

            statsController = new AbortController();

            try {
                const response = await fetch(url.toString(), {
                    method: 'GET',
                    signal: statsController.signal,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Unable to refresh alumni stats.');
                }

                const stats = await response.json();
                updateStatValue('total', stats.total);
                updateStatValue('this_month', stats.this_month);
                updateStatValue('employed', stats.employed);
                updateStatValue('needs_update', stats.needs_update);

            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }
            }
        };

        const refreshWhenVisible = () => {
            if (!document.hidden) {
                refreshStats();
            }
        };

        const intervalId = window.setInterval(refreshStats, 5000);
        document.addEventListener('visibilitychange', refreshWhenVisible);
        window.addEventListener('focus', refreshStats);
        refreshStats();

        const previousCleanup = window.__portalPageCleanup;
        window.__portalPageCleanup = () => {
            window.clearInterval(intervalId);
            document.removeEventListener('visibilitychange', refreshWhenVisible);
            window.removeEventListener('focus', refreshStats);
            statsController?.abort();

            if (typeof previousCleanup === 'function') {
                previousCleanup();
            }
        };
    }

    document.addEventListener('admin:page-ready', initializeAlumniBatchImportModal, { once: true });
    document.addEventListener('admin:page-ready', initializeAlumniCreateModal, { once: true });
    document.addEventListener('admin:page-ready', initializeAlumniNotifyModal, { once: true });
    document.addEventListener('admin:page-ready', initializeAlumniPaginationScrollPreserver, { once: true });
    document.addEventListener('admin:page-ready', initializeAlumniStatsLiveRefresh, { once: true });
</script>
@include('partials.department-program-form-script', [
    'programCatalog' => $programCatalog,
    'departmentProgramFieldConfig' => [
        'departmentSelectId' => 'alumni_modal_department_select',
        'degreeSelectId' => 'alumni_modal_degree_select',
        'selectedDegree' => $selectedDegree,
        'employmentStatusSelectId' => 'alumni_modal_employment_status_select',
        'employmentFieldIds' => [
            'alumni_modal_industry_select',
            'alumni_modal_current_position_input',
            'alumni_modal_company_input',
            'alumni_modal_salary_range_select',
        ],
        'locationSelectId' => 'alumni_modal_location_select',
        'specificLocationGroupId' => 'alumni_modal_specific_location_group',
        'specificLocationInputId' => 'alumni_modal_specific_location_input',
        'lockedEmploymentValues' => ['No', 'Unemployed'],
    ],
])
@endsection
