@extends('components.layouts.dashboard')

@section('title', 'Help Desk')

@section('content')
@php
    $quickActions = [
        [
            'title' => 'Complete your profile',
            'copy' => 'Update your contact details, college, degree, batch, location, and skills so your alumni record stays current.',
            'route' => route('profile'),
            'label' => 'Open Profile',
            'icon' => 'bx-id-card',
            'tone' => 'emerald',
            'image' => asset('images/features/profile.jpg'),
        ],
        [
            'title' => 'Submit tracer information',
            'copy' => 'Record your latest employment status, work details, income range, proof of employment, and tracer responses.',
            'route' => route('tracer.index'),
            'label' => 'Open Tracer',
            'icon' => 'bx-spreadsheet',
            'tone' => 'gold',
            'image' => asset('images/features/tracer.jpg'),
        ],
        [
            'title' => 'Share a job opportunity',
            'copy' => 'Share a job opportunity for fellow alumni. OARL reviews each post before it appears in the portal.',
            'route' => route('jobs'),
            'label' => 'Open Jobs',
            'icon' => 'bx-briefcase-alt-2',
            'tone' => 'emerald',
            'image' => asset('images/features/jobs.jpg'),
        ],
    ];

    $firstSteps = [
        ['label' => 'Complete your alumni profile before submitting updates.', 'icon' => 'bx-check-circle'],
        ['label' => 'Update your tracer record whenever employment details change.', 'icon' => 'bx-edit-alt'],
        ['label' => 'Read notifications for announcements, review results, and direct OARL messages.', 'icon' => 'bx-bell'],
    ];

    $videoGuides = [
        [
            'title' => 'Profile setup guide',
            'copy' => 'Use this first if you are new, returning after a long time, or unsure where to begin.',
            'video_path' => 'videos/help-desk/getting-started.webm',
            'poster' => asset('images/features/profile.jpg'),
            'route' => route('profile'),
            'label' => 'Open Profile',
            'icon' => 'bx-play-circle',
            'steps' => [
                'Open Profile and check your contact number, email, college, program, batch, address, and skills.',
                'Save corrections before moving to Tracer so OARL can match your alumni record correctly.',
                'Return to the dashboard and check if the profile completion reminder is cleared.',
            ],
        ],
        [
            'title' => 'Tracer and proof guide',
            'copy' => 'Follow this when your employment status, employer, position, or proof document needs to be updated.',
            'video_path' => 'videos/help-desk/tracer-and-proof.webm',
            'poster' => asset('images/features/tracer.jpg'),
            'route' => route('tracer.index'),
            'label' => 'Open Tracer',
            'icon' => 'bx-play-circle',
            'steps' => [
                'Open Tracer and answer the required graduate tracer questions carefully.',
                'If employed, enter your company, position, work location, employment type, and income range.',
                'Upload employment proof when requested, then wait for OARL to review the submitted record.',
            ],
        ],
        [
            'title' => 'Updates and support guide',
            'copy' => 'Use this when you need announcements, job post results, account notices, or direct support from OARL.',
            'video_path' => 'videos/help-desk/updates-and-support.webm',
            'poster' => asset('images/features/notifications.jpg'),
            'route' => route('notifications.index'),
            'label' => 'Open Notifications',
            'icon' => 'bx-play-circle',
            'steps' => [
                'Open Notifications to read OARL announcements, direct messages, and job post review results.',
                'Open Feedback if something is wrong with your account, tracer record, job post, or portal access.',
                'Choose the closest feedback category and describe the issue clearly so OARL can respond faster.',
            ],
        ],
    ];

    $workflowSteps = [
        [
            'number' => '01',
            'title' => 'Complete your alumni profile',
            'copy' => 'Keep your contact number, college, program, batch, location, and skills updated so OARL records stay accurate.',
            'route' => route('profile'),
            'label' => 'Update Profile',
            'icon' => 'bx-id-card',
            'tone' => 'emerald',
        ],
        [
            'number' => '02',
            'title' => 'Submit your tracer record',
            'copy' => 'Save your latest employment status, employer details, work location, income range, and tracer responses.',
            'route' => route('tracer.index'),
            'label' => 'Open Tracer',
            'icon' => 'bx-spreadsheet',
            'tone' => 'gold',
        ],
        [
            'number' => '03',
            'title' => 'Attach proof when employed',
            'copy' => 'If employment proof is required, upload the supporting document through Tracer so OARL can verify the record.',
            'route' => route('tracer.index'),
            'label' => 'Add Proof',
            'icon' => 'bx-file',
            'tone' => 'amber',
        ],
        [
            'number' => '04',
            'title' => 'Check announcements and results',
            'copy' => 'Open Notifications for OARL announcements, job post review results, account notices, and direct messages.',
            'route' => route('notifications.index'),
            'label' => 'Open Notifications',
            'icon' => 'bx-bell',
            'tone' => 'sky',
        ],
        [
            'number' => '05',
            'title' => 'Share job leads responsibly',
            'copy' => 'Submit job opportunities for fellow alumni. OARL reviews each post before it becomes visible in the portal.',
            'route' => route('jobs'),
            'label' => 'Open Jobs',
            'icon' => 'bx-briefcase-alt-2',
            'tone' => 'emerald',
        ],
        [
            'number' => '06',
            'title' => 'Protect your account access',
            'copy' => 'Use Settings to change your password, review signed-in devices, and keep access to your account secure.',
            'route' => route('settings'),
            'label' => 'Open Settings',
            'icon' => 'bx-lock-alt',
            'tone' => 'slate',
        ],
    ];

    $toneClasses = [
        'emerald' => 'bg-emerald-50 text-emerald-800 ring-emerald-100',
        'gold' => 'bg-amber-50 text-amber-800 ring-amber-100',
        'sky' => 'bg-sky-50 text-sky-800 ring-sky-100',
        'amber' => 'bg-yellow-50 text-yellow-800 ring-yellow-100',
        'slate' => 'bg-slate-100 text-slate-800 ring-slate-200',
    ];
@endphp

<div class="space-y-6">
    <section class="grid gap-4 lg:grid-cols-[1.25fr_0.75fr] lg:items-stretch">
        <div class="surface-card p-6">
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-[#9a6800]">Alumni Help Desk</p>
            <h2 class="mt-3 text-3xl font-black leading-tight text-[#072714]">Welcome, CMU alumni. Start here when the portal feels confusing.</h2>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600">
                This help desk explains what to do first, where each task belongs, and when to contact OARL. Follow the guides below in order if you are unsure how to update your record, submit tracer details, check announcements, share job opportunities, or ask for help.
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <span class="alumni-pill alumni-pill--gold">
                    <i class='bx bx-video'></i>
                    Video guides
                </span>
                <span class="alumni-pill">
                    <i class='bx bx-check-shield'></i>
                    Step-by-step support
                </span>
            </div>
        </div>

        <div class="rounded-[1.5rem] border border-[#0B3B1F]/10 bg-[#0B3B1F] px-5 py-5 text-white shadow-sm">
            <p class="text-xs font-bold uppercase tracking-[0.22em] text-[#F4E6BD]">Start Here</p>
            <div class="mt-4 space-y-3">
                @foreach ($firstSteps as $step)
                    <div class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/10 px-3 py-3">
                        <span class="mt-0.5 inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-white/15 text-[#F4E6BD]">
                            <i class='bx {{ $step['icon'] }} text-lg'></i>
                        </span>
                        <p class="text-sm leading-6 text-white/90">{{ $step['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="space-y-4">
        <div class="alumni-panel-header">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Video Guides</p>
                <h2 class="alumni-panel-title mt-2">Watch the real portal guide, then follow the steps</h2>
                <p class="alumni-panel-copy">Each video uses actual ARLO screens with click markers so alumni can follow the same pages they see after signing in.</p>
            </div>
            <span class="alumni-pill alumni-pill--gold">
                <i class='bx bx-play-circle'></i>
                Alumni tutorials
            </span>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            @foreach ($videoGuides as $guide)
                @php
                    $videoExists = file_exists(public_path($guide['video_path']));
                @endphp
                <article class="surface-card overflow-hidden p-0">
                    <div class="relative aspect-video overflow-hidden bg-[#0B3B1F]">
                        @if ($videoExists)
                            <video controls preload="metadata" poster="{{ $guide['poster'] }}" class="h-full w-full object-cover">
                                <source src="{{ asset($guide['video_path']) }}" type="video/webm">
                                Your browser does not support the video guide.
                            </video>
                        @else
                            <div class="absolute inset-0 bg-cover bg-center" style="background-image: linear-gradient(135deg, rgba(7, 39, 20, 0.72), rgba(11, 59, 31, 0.3)), url('{{ $guide['poster'] }}');"></div>
                            <div class="relative flex h-full flex-col justify-between p-5 text-white">
                                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-[#F4E6BD] ring-1 ring-white/20">
                                    <i class='bx {{ $guide['icon'] }} text-3xl'></i>
                                </span>
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.2em] text-[#F4E6BD]">Tutorial preview</p>
                                    <h3 class="mt-2 text-xl font-black leading-tight">{{ $guide['title'] }}</h3>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-extrabold leading-tight text-slate-950">{{ $guide['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $guide['copy'] }}</p>
                        <ol class="mt-4 space-y-3">
                            @foreach ($guide['steps'] as $index => $guideStep)
                                <li class="grid grid-cols-[auto_1fr] gap-3 text-sm leading-6 text-slate-700">
                                    <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-50 text-xs font-black text-emerald-700 ring-1 ring-emerald-100">{{ $index + 1 }}</span>
                                    <span>{{ $guideStep }}</span>
                                </li>
                            @endforeach
                        </ol>
                        <a href="{{ $guide['route'] }}" class="mt-5 inline-flex w-full items-center justify-center rounded-xl border border-[#0B3B1F]/15 bg-white px-4 py-2.5 text-sm font-bold text-[#0B3B1F] shadow-sm hover:bg-[#EEF4EF]">
                            {{ $guide['label'] }}
                            <i class='bx bx-right-arrow-alt ml-2 text-lg'></i>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="space-y-4">
        <div class="alumni-panel-header">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Core Portal Tasks</p>
                <h2 class="alumni-panel-title mt-2">Start with the essentials</h2>
                <p class="alumni-panel-copy">These are the main actions alumni usually need first.</p>
            </div>
            <span class="alumni-pill">
                <i class='bx bx-grid-alt'></i>
                Main modules
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3" aria-label="Help desk modules">
            @foreach ($quickActions as $action)
                @php $toneClass = $toneClasses[$action['tone']] ?? $toneClasses['slate']; @endphp
                <article class="surface-card flex h-full flex-col justify-between overflow-hidden p-0">
                    <img src="{{ $action['image'] }}" alt="{{ $action['title'] }} illustration" loading="lazy" decoding="async" class="portal-feature-card__image">
                    <div class="flex flex-1 flex-col justify-between p-5">
                    <div>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl ring-1 {{ $toneClass }}">
                            <i class='bx {{ $action['icon'] }} text-2xl'></i>
                        </span>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $action['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $action['copy'] }}</p>
                    </div>
                    <a href="{{ $action['route'] }}" class="mt-5 inline-flex items-center justify-center rounded-xl border border-[#0B3B1F]/15 bg-white px-4 py-2.5 text-sm font-bold text-[#0B3B1F] shadow-sm hover:bg-[#EEF4EF]">
                        {{ $action['label'] }}
                        <i class='bx bx-right-arrow-alt ml-2 text-lg'></i>
                    </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="space-y-4">
        <div class="alumni-panel-header">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Important Steps</p>
                <h2 class="alumni-panel-title mt-2">Alumni workflow to follow</h2>
                <p class="alumni-panel-copy">Follow this order when you are updating your record or checking portal activity.</p>
            </div>
            <span class="alumni-pill alumni-pill--gold">
                <i class='bx bx-list-check'></i>
                Step guide
            </span>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            @foreach ($workflowSteps as $step)
                @php $toneClass = $toneClasses[$step['tone']] ?? $toneClasses['slate']; @endphp
                <a href="{{ $step['route'] }}" class="group block overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                    <div class="flex items-center justify-between border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-emerald-50/40 px-5 py-4">
                        <span class="text-xs font-black uppercase tracking-[0.22em] text-[#0B3B1F]">Step {{ $step['number'] }}</span>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl ring-1 {{ $toneClass }}">
                            <i class='bx {{ $step['icon'] }} text-2xl'></i>
                        </span>
                    </div>
                    <div class="p-5">
                        <h3 class="text-base font-extrabold leading-snug text-slate-950">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['copy'] }}</p>
                        <span class="mt-4 inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.18em] text-emerald-700">
                            {{ $step['label'] }}
                            <i class='bx bx-right-arrow-alt text-lg transition group-hover:translate-x-1'></i>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-[0.85fr_1.15fr]">
        <div class="surface-card border-amber-100 bg-amber-50/70 p-5">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-amber-700 ring-1 ring-amber-100">
                <i class='bx bx-info-circle text-2xl'></i>
            </span>
            <h2 class="mt-4 text-lg font-bold text-amber-950">OARL reviews public posts</h2>
            <p class="mt-2 text-sm leading-6 text-amber-900/90">
                Job opportunities posted by alumni are reviewed before publication. This keeps shared opportunities appropriate, traceable, and useful for other alumni.
            </p>
        </div>

        <div class="surface-card p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-emerald-700">Need more help?</p>
                    <h2 class="mt-2 text-lg font-bold text-slate-900">Send an alumni concern to OARL</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Use the feedback form for alumni account questions, tracer concerns, job posting issues, or system problems.</p>
                </div>
                <a href="{{ route('feedback.form') }}" class="inline-flex items-center justify-center rounded-xl bg-[#862633] px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-[#701f2a]">
                    <i class='bx bx-message-rounded-dots mr-2 text-lg'></i>
                    Contact OARL
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
