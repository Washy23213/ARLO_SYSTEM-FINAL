<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07522b">
    <meta name="description" content="Stay connected to Central Mindanao University. Share your graduate journey through the CMU OARL tracer system.">
    <title>CMU OARL | Your Journey Continues</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/css/welcome.css', 'resources/js/app.js'])
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="cmu-home">
    <a class="cmu-home__skip-link" href="#main-content">Skip to content</a>

    <header class="cmu-home__header">
        <div class="cmu-home__container cmu-home__header-inner">
            <a class="cmu-home__brand" href="{{ url('/') }}" aria-label="CMU OARL home">
                <span class="cmu-home__brand-mark">
                    <img src="{{ asset('images/cmu-sidebar.png') }}" alt="">
                </span>
                <span class="cmu-home__brand-copy">
                    <strong>Central Mindanao University</strong>
                    <span>Alumni Relations and Linkages Office</span>
                </span>
            </a>

            <nav class="cmu-home__nav" aria-label="Main navigation">
                <a href="#why-tracer">Why tracer</a>
                <a href="#how-it-works">How it works</a>
                <a href="{{ route('privacy-policy') }}">Privacy</a>
            </nav>

            <div class="cmu-home__header-actions">
                <a class="cmu-home__login" href="{{ route('login') }}">Log in</a>
                <a class="cmu-home__button cmu-home__button--small" href="{{ route('signup') }}">Create account</a>
            </div>
        </div>
    </header>

    <main id="main-content">
        <section class="cmu-home__hero">
            <div class="cmu-home__container cmu-home__hero-grid">
                <div class="cmu-home__hero-copy">
                    <p class="cmu-home__eyebrow"><span aria-hidden="true"></span> CMU Graduate Tracer System</p>
                    <h1>Your journey continues <em>beyond graduation.</em></h1>
                    <p class="cmu-home__lead">
                        Share your career journey and help Central Mindanao University build a better future for the next generation of graduates.
                    </p>

                    <div class="cmu-home__hero-actions">
                        <a class="cmu-home__button" href="{{ route('signup') }}">
                            Start your tracer survey
                            <i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
                        </a>
                        <a class="cmu-home__text-link" href="{{ route('login') }}">Already have an account? Log in</a>
                    </div>

                    <div class="cmu-home__privacy-note">
                        <span class="cmu-home__privacy-icon" aria-hidden="true"><i class="bx bx-lock-alt"></i></span>
                        <span>Your information is handled with care and confidentiality.</span>
                        <a href="{{ route('privacy-policy') }}">Learn about privacy</a>
                    </div>
                </div>

                <div class="cmu-home__hero-art" aria-label="Central Mindanao University and OARL">
                    <img src="{{ asset('images/login_img_fixed.png') }}" alt="Central Mindanao University campus and Alumni Relations and Linkages Office">
                    <div class="cmu-home__art-shade" aria-hidden="true"></div>
                    <div class="cmu-home__art-label">
                        <span class="cmu-home__art-label-mark"><img src="{{ asset('images/cmu-sidebar.png') }}" alt=""></span>
                        <span>
                            <small>ALUMNI RELATIONS AND LINKAGES OFFICE</small>
                            <strong>Your story is part of CMU’s future.</strong>
                        </span>
                    </div>
                    <div class="cmu-home__art-tag"><i class="bx bx-leaf" aria-hidden="true"></i> One CMU community</div>
                </div>
            </div>
        </section>

        <section class="cmu-home__features" id="why-tracer">
            <div class="cmu-home__container">
                <div class="cmu-home__section-heading">
                    <div>
                        <p class="cmu-home__section-kicker">Stay connected</p>
                        <h2>Your experience helps CMU move forward.</h2>
                    </div>
                    <p>Keep your alumni information current, share what you have learned after graduation, and stay connected with your university community.</p>
                </div>

                <div class="cmu-home__feature-grid">
                    <article class="cmu-home__feature-card">
                        <span class="cmu-home__feature-icon cmu-home__feature-icon--green"><i class="bx bx-user-check" aria-hidden="true"></i></span>
                        <h3>Stay connected</h3>
                        <p>Maintain your alumni profile and receive CMU OARL announcements, events, and career opportunities.</p>
                    </article>
                    <article class="cmu-home__feature-card">
                        <span class="cmu-home__feature-icon cmu-home__feature-icon--gold"><i class="bx bx-trending-up" aria-hidden="true"></i></span>
                        <h3>Track your journey</h3>
                        <p>Update your employment progress and revisit your tracer information from your personal alumni dashboard.</p>
                    </article>
                    <article class="cmu-home__feature-card">
                        <span class="cmu-home__feature-icon cmu-home__feature-icon--sage"><i class="bx bx-bulb" aria-hidden="true"></i></span>
                        <h3>Shape what comes next</h3>
                        <p>Your feedback helps the university understand graduate outcomes and improve support for future alumni.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="cmu-home__steps" id="how-it-works">
            <div class="cmu-home__container cmu-home__steps-layout">
                <div class="cmu-home__steps-intro">
                    <p class="cmu-home__section-kicker">A simple way to stay involved</p>
                    <h2>From your account to your alumni community.</h2>
                    <p>Create your account, complete your tracer information, then return whenever you need to update your profile or catch up on opportunities.</p>
                    <a class="cmu-home__text-link cmu-home__text-link--green" href="{{ route('signup') }}">Get started <i class="bx bx-right-arrow-alt" aria-hidden="true"></i></a>
                </div>

                <div class="cmu-home__step-list">
                    <article class="cmu-home__step">
                        <span>01</span>
                        <div><h3>Create your account</h3><p>Verify your email and set up your alumni sign-in.</p></div>
                    </article>
                    <article class="cmu-home__step">
                        <span>02</span>
                        <div><h3>Share your graduate journey</h3><p>Complete your profile and tell CMU about your experience after graduation.</p></div>
                    </article>
                    <article class="cmu-home__step">
                        <span>03</span>
                        <div><h3>Stay in touch</h3><p>Return to your dashboard for updates, job opportunities, and tracer information.</p></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="cmu-home__closing">
            <div class="cmu-home__container">
                <div class="cmu-home__closing-card">
                    <div>
                        <p class="cmu-home__section-kicker">Your next chapter is still connected to CMU</p>
                        <h2>Help us understand where your journey takes you.</h2>
                        <p>Join the alumni community and share the experience that can guide future graduates.</p>
                    </div>
                    <a class="cmu-home__button cmu-home__button--light" href="{{ route('signup') }}">Create your account <i class="bx bx-right-arrow-alt" aria-hidden="true"></i></a>
                </div>
            </div>
        </section>
    </main>

    <footer class="cmu-home__footer">
        <div class="cmu-home__container cmu-home__footer-inner">
            <div><strong>CMU OARL Alumni Portal</strong><span>Central Mindanao University</span></div>
            <nav aria-label="Footer navigation">
                <a href="{{ route('privacy-policy') }}">Privacy policy</a>
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('signup') }}">Create account</a>
            </nav>
        </div>
    </footer>
</body>
</html>
