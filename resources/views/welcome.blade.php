<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CMU OARL | Alumni Tracer, Job Postings, and Employment Analytics</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #f4f8f1;
            --surface: rgba(255, 255, 255, 0.86);
            --surface-strong: #ffffff;
            --ink: #102317;
            --muted: #4d6254;
            --line: rgba(13, 69, 35, 0.12);
            --green-900: #00491e;
            --green-800: #00491e;
            --green-700: #02681e;
            --green-200: #dbeedc;
            --gold: #ffc600;
            --shadow: 0 28px 70px rgba(10, 45, 23, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(212, 169, 53, 0.18), transparent 24%),
                radial-gradient(circle at top right, rgba(30, 122, 52, 0.18), transparent 28%),
                linear-gradient(180deg, #eef7f0 0%, #f8fbf7 48%, #f1f7f2 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page-shell {
            position: relative;
            overflow: hidden;
        }

        .page-shell::before,
        .page-shell::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            filter: blur(10px);
        }

        .page-shell::before {
            top: 6rem;
            right: -8rem;
            width: 20rem;
            height: 20rem;
            background: rgba(30, 122, 52, 0.12);
        }

        .page-shell::after {
            left: -6rem;
            bottom: 10rem;
            width: 18rem;
            height: 18rem;
            background: rgba(212, 169, 53, 0.14);
        }

        .container {
            position: relative;
            z-index: 1;
            width: min(1180px, calc(100% - 2rem));
            margin: 0 auto;
        }

        .site-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .brand-mark {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 1rem;
            background:
                url('{{ asset('images/cmu-sidebar.png') }}') center/76% no-repeat,
                linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(221, 238, 224, 0.9));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 14px 28px rgba(11, 59, 31, 0.12);
            border: 1px solid rgba(11, 59, 31, 0.08);
        }

        .brand-copy strong {
            display: block;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            letter-spacing: 0.02em;
        }

        .brand-copy span {
            display: block;
            margin-top: 0.1rem;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .link-btn,
        .solid-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.9rem;
            padding: 0.8rem 1.2rem;
            border-radius: 999px;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .link-btn {
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.7);
            color: var(--green-900);
        }

        .solid-btn {
            background: linear-gradient(135deg, var(--green-900), var(--green-700));
            color: #fff;
            box-shadow: 0 14px 28px rgba(20, 87, 38, 0.22);
        }

        .link-btn:hover,
        .solid-btn:hover {
            transform: translateY(-1px);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.85fr);
            gap: 2rem;
            align-items: center;
            padding: 2.5rem 0 3.5rem;
        }

        .hero-copy {
            max-width: 40rem;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 0.95rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(11, 59, 31, 0.09);
            color: var(--green-800);
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: var(--gold);
            box-shadow: 0 0 0 4px rgba(212, 169, 53, 0.18);
        }

        .hero-title {
            margin: 1.25rem 0 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2.5rem, 6vw, 4.8rem);
            line-height: 0.97;
            letter-spacing: -0.05em;
        }

        .hero-title span {
            display: block;
            color: var(--green-700);
        }

        .hero-lead {
            font-size: 1.05rem;
            line-height: 1.75;
            color: var(--muted);
            max-width: 36rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
            margin-top: 1.75rem;
        }

        .ghost-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.9rem;
            padding: 0.8rem 1.2rem;
            border-radius: 999px;
            border: 1px solid rgba(11, 59, 31, 0.14);
            background: rgba(255, 255, 255, 0.64);
            color: var(--green-900);
            font-weight: 700;
        }

        .hero-panel {
            position: relative;
            padding: 1.2rem;
            border-radius: 2rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(236, 247, 239, 0.9));
            border: 1px solid rgba(11, 59, 31, 0.08);
            box-shadow: var(--shadow);
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 1rem 1rem auto auto;
            width: 5.5rem;
            height: 5.5rem;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212, 169, 53, 0.24), transparent 68%);
        }

        .hero-visual {
            overflow: hidden;
            border-radius: 1.5rem;
            border: 1px solid rgba(11, 59, 31, 0.08);
            background: linear-gradient(160deg, rgba(16, 61, 30, 0.96), rgba(35, 122, 60, 0.9));
            min-height: 23rem;
            position: relative;
        }

        .hero-visual img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            mix-blend-mode: screen;
            opacity: 0.28;
        }

        .visual-overlay {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 1rem;
            padding: 1.5rem;
            min-height: 23rem;
            align-content: space-between;
        }

        .mini-card {
            width: fit-content;
            max-width: 15rem;
            padding: 1rem;
            border-radius: 1.2rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #f4fff6;
            backdrop-filter: blur(8px);
        }

        .mini-card strong {
            display: block;
            font-size: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .mini-card span {
            display: block;
            margin-top: 0.45rem;
            font-size: 0.88rem;
            line-height: 1.5;
            color: rgba(244, 255, 246, 0.84);
        }

        .mini-card.alt {
            justify-self: end;
            background: rgba(255, 248, 230, 0.14);
            color: #fff6d8;
        }

        .feature-section,
        .steps-section,
        .cta-section {
            padding: 1.5rem 0 4rem;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: end;
            margin-bottom: 1.4rem;
        }

        .section-head h2 {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(1.9rem, 4vw, 2.7rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
        }

        .section-head p {
            margin: 0;
            max-width: 34rem;
            color: var(--muted);
            line-height: 1.7;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .feature-card,
        .step-card,
        .cta-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 1.6rem;
            box-shadow: 0 18px 36px rgba(16, 35, 23, 0.07);
        }

        .feature-card {
            padding: 1.35rem;
        }

        .feature-icon {
            display: inline-flex;
            width: 3rem;
            height: 3rem;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: linear-gradient(135deg, rgba(20, 87, 38, 0.12), rgba(212, 169, 53, 0.18));
            color: var(--green-900);
            font-weight: 800;
        }

        .feature-card h3,
        .step-card h3 {
            margin: 1rem 0 0.5rem;
            font-size: 1.05rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .feature-card p,
        .step-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            font-size: 0.95rem;
        }

        .job-highlight-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            margin-top: 1.35rem;
        }

        .job-highlight-chip {
            display: inline-flex;
            align-items: center;
            min-height: 2.25rem;
            padding: 0.55rem 0.85rem;
            border-radius: 999px;
            border: 1px solid rgba(11, 59, 31, 0.1);
            background: rgba(255, 255, 255, 0.7);
            color: var(--green-800);
            font-size: 0.82rem;
            font-weight: 800;
            box-shadow: 0 10px 22px rgba(16, 35, 23, 0.06);
            animation: jobChipPulse 4.5s ease-in-out infinite;
        }

        .job-highlight-chip:nth-child(2) {
            animation-delay: 0.4s;
        }

        .job-highlight-chip:nth-child(3) {
            animation-delay: 0.8s;
        }

        .job-highlight-chip:nth-child(4) {
            animation-delay: 1.2s;
        }

        @keyframes jobChipPulse {
            0%, 100% {
                transform: translateY(0);
                box-shadow: 0 10px 22px rgba(16, 35, 23, 0.06);
            }

            50% {
                transform: translateY(-4px);
                box-shadow: 0 16px 30px rgba(20, 87, 38, 0.14);
            }
        }

        .job-board-preview {
            position: relative;
            overflow: hidden;
            display: grid;
            gap: 0.85rem;
            min-height: 25rem;
            padding: 1.2rem;
            border-radius: 1.6rem;
            border: 1px solid rgba(11, 59, 31, 0.12);
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(238, 248, 241, 0.94)),
                linear-gradient(180deg, rgba(212, 169, 53, 0.1), rgba(255,255,255,0));
            box-shadow: 0 18px 36px rgba(16, 35, 23, 0.09);
        }

        .job-board-preview::before {
            content: "";
            position: absolute;
            inset: 0;
            border-top: 3px solid rgba(212, 169, 53, 0.7);
            transform: translateX(-100%);
            animation: boardScan 3.8s ease-in-out infinite;
        }

        .job-board-topline {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
        }

        .job-board-topline span {
            color: var(--green-800);
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .job-board-topline strong {
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
        }

        .job-board-stream {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 0.85rem;
        }

        .job-board-card {
            display: grid;
            gap: 0.7rem;
            padding: 1rem;
            border-radius: 1.1rem;
            border: 1px solid rgba(11, 59, 31, 0.1);
            background: #ffffff;
            box-shadow: 0 14px 28px rgba(16, 35, 23, 0.08);
            animation: jobCardLift 5s ease-in-out infinite;
        }

        .job-board-card:nth-child(2) {
            animation-delay: 0.6s;
        }

        .job-board-card:nth-child(3) {
            animation-delay: 1.2s;
        }

        .job-board-card-header {
            display: flex;
            justify-content: space-between;
            gap: 0.8rem;
            align-items: start;
        }

        .job-board-card h3 {
            margin: 0;
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.05rem;
        }

        .job-board-card p {
            margin: 0.25rem 0 0;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .job-badge {
            flex-shrink: 0;
            border-radius: 999px;
            background: rgba(30, 122, 52, 0.1);
            color: var(--green-800);
            padding: 0.4rem 0.6rem;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .job-status-rail {
            overflow: hidden;
            height: 0.45rem;
            border-radius: 999px;
            background: rgba(13, 69, 35, 0.1);
        }

        .job-status-rail span {
            display: block;
            width: 48%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--green-700), var(--gold));
            animation: railMove 2.8s ease-in-out infinite;
        }

        .job-board-note {
            position: relative;
            z-index: 1;
            margin: 0;
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .hero-panel .job-board-preview {
            min-height: 23rem;
            box-shadow: none;
        }

        @keyframes boardScan {
            0%, 18% {
                transform: translateX(-100%);
            }

            54%, 100% {
                transform: translateX(100%);
            }
        }

        @keyframes jobCardLift {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        @keyframes railMove {
            0%, 100% {
                transform: translateX(-12%);
            }

            50% {
                transform: translateX(122%);
            }
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .step-card {
            padding: 1.35rem;
        }

        .step-no {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            background: var(--green-900);
            color: #fff;
            font-weight: 800;
            box-shadow: 0 10px 20px rgba(11, 59, 31, 0.22);
        }

        .cta-card {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.6rem 1.7rem;
            background:
                linear-gradient(135deg, rgba(11, 59, 31, 0.95), rgba(30, 122, 52, 0.92)),
                linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0));
            color: #f4fff6;
        }

        .cta-card h2 {
            margin: 0 0 0.5rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(1.8rem, 3vw, 2.4rem);
            line-height: 1.05;
        }

        .cta-card p {
            margin: 0;
            color: rgba(244, 255, 246, 0.82);
            line-height: 1.7;
        }

        .cta-actions {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .cta-card .link-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.15);
        }

        .footer {
            padding: 0 0 2.5rem;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .footer-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(11, 59, 31, 0.08);
        }

        @media (max-width: 980px) {
            .hero,
            .feature-grid,
            .steps-grid,
            .cta-card {
                grid-template-columns: 1fr;
            }

            .section-head,
            .footer-row {
                flex-direction: column;
                align-items: start;
            }

            .hero {
                padding-top: 1.5rem;
            }
        }

        @media (max-width: 640px) {
            .site-header,
            .header-actions,
            .hero-actions,
            .cta-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .brand,
            .site-header {
                align-items: start;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .job-highlight-chip,
            .job-board-card,
            .job-board-preview::before,
            .job-status-rail span {
                animation: none;
            }

            .link-btn,
            .solid-btn {
                transition: none;
            }
        }
    </style>
</head>
<body>
<div class="page-shell">
    <div class="container">
        <header class="site-header">
            <a href="{{ url('/') }}" class="brand">
                <span class="brand-mark" aria-hidden="true"></span>
                <span class="brand-copy">
                    <strong>CMU OARL</strong>
                    <span>Alumni Tracer, Job Postings, and Employment Analytics</span>
                </span>
            </a>

            <div class="header-actions">
                <a href="{{ route('login') }}" class="link-btn">Log In</a>
                <a href="{{ route('signup') }}" class="solid-btn">Create Account</a>
            </div>
        </header>

        <main>
            <section class="hero">
                <div class="hero-copy">
                    <span class="eyebrow">Central Mindanao University</span>
                    <h1 class="hero-title">
                        Job postings for CMU alumni,
                        <span>reviewed opportunities in one portal.</span>
                    </h1>
                    <p class="hero-lead">
                        CMU OARL highlights approved job postings and alumni-submitted opportunities while keeping tracer records,
                        employment updates, and office announcements connected in one reliable portal.
                    </p>

                    <div class="hero-actions">
                        <a href="{{ route('signup') }}" class="solid-btn">Join the Alumni Portal</a>
                        <a href="{{ route('jobs') }}" class="ghost-btn">View Job Postings</a>
                    </div>

                    <div class="job-highlight-strip" aria-label="Job posting highlights">
                        <span class="job-highlight-chip">Approved openings</span>
                        <span class="job-highlight-chip">Admin reviewed</span>
                        <span class="job-highlight-chip">Alumni-submitted leads</span>
                        <span class="job-highlight-chip">Deadline tracking</span>
                    </div>

                </div>

                <div class="hero-panel">
                    <div class="job-board-preview" aria-label="Animated featured job posting board preview">
                        <div class="job-board-topline">
                            <span>Featured Job Board</span>
                            <strong>Visible First</strong>
                        </div>

                        <div class="job-board-stream">
                            <article class="job-board-card">
                                <div class="job-board-card-header">
                                    <div>
                                        <h3>Junior Accountant</h3>
                                        <p>Approved opening with deadline and employer contact details.</p>
                                    </div>
                                    <span class="job-badge">Published</span>
                                </div>
                                <div class="job-status-rail" aria-hidden="true"><span></span></div>
                            </article>

                            <article class="job-board-card">
                                <div class="job-board-card-header">
                                    <div>
                                        <h3>IT Support Associate</h3>
                                        <p>Alumni-submitted lead queued for administrator review.</p>
                                    </div>
                                    <span class="job-badge">Review</span>
                                </div>
                                <div class="job-status-rail" aria-hidden="true"><span></span></div>
                            </article>

                            <article class="job-board-card">
                                <div class="job-board-card-header">
                                    <div>
                                        <h3>Field Coordinator</h3>
                                        <p>Opportunity notice prepared for verified alumni visibility.</p>
                                    </div>
                                    <span class="job-badge">Tracked</span>
                                </div>
                                <div class="job-status-rail" aria-hidden="true"><span></span></div>
                            </article>
                        </div>

                        <div class="hero-actions">
                            <a href="{{ route('jobs') }}" class="solid-btn">Browse Jobs</a>
                            <a href="{{ route('signup') }}" class="link-btn">Create Account</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="feature-section">
                <div class="section-head">
                    <div>
                        <h2>Built around opportunity discovery</h2>
                    </div>
                    <p>
                        The portal still handles records and reports, but the landing page now leads with job postings, opportunity submission, and employment updates for alumni.
                    </p>
                </div>

                <div class="feature-grid">
                    <article class="feature-card">
                        <span class="feature-icon">01</span>
                        <h3>Alumni profile management</h3>
                        <p>Let graduates maintain current contact, education, and employment information without relying on scattered spreadsheets.</p>
                    </article>
                    <article class="feature-card">
                        <span class="feature-icon">02</span>
                        <h3>Job postings and announcements</h3>
                        <p>Publish approved openings, review alumni-submitted job opportunities, and keep office notices in a place alumni can revisit anytime.</p>
                    </article>
                    <article class="feature-card">
                        <span class="feature-icon">03</span>
                        <h3>Analytics for decision-making</h3>
                        <p>Track employment trends, industry distribution, tracer responses, and activity patterns through admin dashboards and reports.</p>
                    </article>
                </div>
            </section>

            <section class="steps-section">
                <div class="section-head">
                    <div>
                        <h2>How CMU OARL works</h2>
                    </div>
                    <p>
                        The portal supports a simple loop: alumni update records, the office publishes opportunities,
                        job postings reach verified graduates, and the institution gains a clearer view of graduate outcomes over time.
                    </p>
                </div>

                <div class="steps-grid">
                    <article class="step-card">
                        <span class="step-no">1</span>
                        <h3>Register and maintain your profile</h3>
                        <p>Create an account, complete alumni details, and keep employment information current so your record stays useful.</p>
                    </article>
                    <article class="step-card">
                        <span class="step-no">2</span>
                        <h3>Stay connected to opportunities</h3>
                        <p>Review announcements, check job postings, and respond to tracer-related updates from the Office of the Alumni Relations and Linkages (OARL).</p>
                    </article>
                    <article class="step-card">
                        <span class="step-no">3</span>
                        <h3>Generate institutional insight</h3>
                        <p>Administrators monitor growth, participation, and employment outcomes to support planning and reporting needs.</p>
                    </article>
                </div>
            </section>

            <section class="cta-section">
                <div class="cta-card">
                    <div>
                        <h2>Ready to join the CMU alumni portal?</h2>
                        <p>Start your account, keep your graduate record updated, browse job postings, and help strengthen CMU's alumni and employment data over time.</p>
                    </div>
                    <div class="cta-actions">
                        <a href="{{ route('signup') }}" class="solid-btn">Sign Up</a>
                        <a href="{{ route('login') }}" class="link-btn">Log In</a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-row">
                <span>CMU OARL Portal</span>
                <span>Built for alumni tracking, tracer studies, job postings, and employment analytics.</span>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
