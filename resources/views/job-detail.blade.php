@extends('components.layouts.dashboard')

@section('title', 'Job Details')

@section('content')
<div class="pl-8 pr-6 pt-6 pb-6">
    <div class="mb-6">
        <div class="rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-teal-50 px-6 py-5 shadow-sm">
            <div class="flex items-start gap-4">
                <span class="mt-1 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                    <i class='bx bx-briefcase-alt-2 text-2xl'></i>
                </span>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $job['title'] }}</h1>
                    <p class="text-lg text-gray-600 mt-1">{{ $job['company'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-wrap justify-between items-start gap-4">
            <div class="flex-1">
                <div class="flex flex-wrap gap-6 text-sm text-gray-600 mb-4">
                    <span class="inline-flex items-center rounded-full bg-gray-50 px-3 py-1.5">
                        <i class="bx bx-map-pin mr-2 text-lg"></i>
                        {{ $job['location'] }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-50 px-3 py-1.5">
                        <i class="bx bx-time mr-2 text-lg"></i>
                        Posted {{ $job['posted'] }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-50 px-3 py-1.5">
                        <i class="bx bx-calendar mr-2 text-lg"></i>
                        Deadline: {{ $job['application_deadline'] }}
                    </span>
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-blue-700 ring-1 ring-blue-200">
                        <i class="bx bx-show mr-2 text-lg"></i>
                        Viewed <span class="ml-1 font-semibold">{{ number_format((int) ($job['views_count'] ?? 0)) }}</span> times
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                        {{ $job['type'] }}
                    </span>
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                        Active
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <!-- Job Description -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
            <div class="absolute right-0 top-0 h-28 w-28 rounded-full bg-emerald-100/40 blur-2xl"></div>
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-400 to-transparent"></div>
            <div class="mb-5 flex items-center gap-3">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                    <i class='bx bx-file text-2xl'></i>
                </span>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700/80">Overview</p>
                    <h2 class="text-2xl font-semibold text-gray-900">Job Description</h2>
                </div>
            </div>
            <div class="relative rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white px-5 py-4">
                <p class="text-[15px] leading-8 text-gray-700">{{ $job['description'] }}</p>
            </div>
        </section>

        <!-- Benefits -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
            <div class="absolute left-0 top-0 h-24 w-24 rounded-full bg-amber-100/40 blur-2xl"></div>
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-400 via-yellow-300 to-transparent"></div>
            <div class="mb-5 flex items-center gap-3">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                    <i class='bx bx-gift text-2xl'></i>
                </span>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700/80">Offer</p>
                    <h2 class="text-2xl font-semibold text-gray-900">Benefits & Perks</h2>
                </div>
            </div>
            <div class="space-y-3">
                @foreach($job['benefits'] as $benefit)
                <div class="flex items-start gap-3 rounded-2xl border border-amber-100 bg-gradient-to-r from-amber-50/90 to-white px-4 py-3 transition-all duration-300 hover:border-amber-200 hover:shadow-sm">
                    <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-amber-600 ring-1 ring-amber-200">
                        <i class="bx bx-star text-sm"></i>
                    </span>
                    <span class="text-sm leading-relaxed text-gray-700">{{ $benefit }}</span>
                </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Requirements -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
            <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-emerald-100/40 blur-2xl"></div>
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 via-emerald-300 to-transparent"></div>
            <div class="mb-5 flex items-center gap-3">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                    <i class='bx bx-list-check text-2xl'></i>
                </span>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700/80">Qualifications</p>
                    <h2 class="text-2xl font-semibold text-gray-900">Requirements</h2>
                </div>
            </div>
            <ul class="space-y-3">
                @foreach($job['requirements'] as $requirement)
                <li class="flex items-start gap-3 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50/90 to-white px-4 py-3 transition-all duration-300 hover:border-emerald-200 hover:shadow-sm">
                    <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-emerald-600 ring-1 ring-emerald-200">
                        <i class="bx bx-check text-sm"></i>
                    </span>
                    <span class="text-sm leading-relaxed text-gray-700">{{ $requirement }}</span>
                </li>
                @endforeach
            </ul>
        </section>

        <!-- Responsibilities -->
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
            <div class="absolute left-0 top-0 h-24 w-24 rounded-full bg-blue-100/40 blur-2xl"></div>
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 via-sky-300 to-transparent"></div>
            <div class="mb-5 flex items-center gap-3">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
                    <i class='bx bx-task text-2xl'></i>
                </span>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-700/80">Core Duties</p>
                    <h2 class="text-2xl font-semibold text-gray-900">Key Responsibilities</h2>
                </div>
            </div>
            <ul class="space-y-3">
                @foreach($job['responsibilities'] as $responsibility)
                <li class="flex items-start gap-3 rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50/90 to-white px-4 py-3 transition-all duration-300 hover:border-blue-200 hover:shadow-sm">
                    <span class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-blue-600 ring-1 ring-blue-200">
                        <i class="bx bx-right-arrow-alt text-sm"></i>
                    </span>
                    <span class="text-sm leading-relaxed text-gray-700">{{ $responsibility }}</span>
                </li>
                @endforeach
            </ul>
        </section>
    </div>

    <!-- Contact Information -->
    <div class="relative overflow-hidden bg-white rounded-3xl shadow-sm border border-slate-200 p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
        <div class="absolute right-0 top-0 h-28 w-28 rounded-full bg-emerald-100/35 blur-2xl"></div>
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-emerald-500 via-teal-400 to-transparent"></div>
        <div class="mb-5 flex items-center gap-3">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                <i class='bx bx-send text-2xl'></i>
            </span>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700/80">Employer Contact</p>
                <h2 class="text-2xl font-semibold text-gray-900">Job Opportunity Details</h2>
            </div>
        </div>
        <div class="relative rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-teal-50 p-5">
            <p class="text-sm leading-relaxed text-gray-700 mb-4">
                This portal shares the opportunity for alumni awareness. Applications are handled outside the system by the employer or contact person listed below.
            </p>
            <div class="inline-flex items-center rounded-xl bg-white border border-emerald-200 px-4 py-2.5 text-green-700 font-semibold shadow-sm">
                <i class="bx bx-envelope mr-2"></i>
                {{ $job['contact_email'] }}
            </div>
            <p class="text-sm text-gray-600 mt-4">
                Use the contact details provided by the employer if you want to inquire about this opening.
            </p>
        </div>
    </div>

</div>
@endsection
