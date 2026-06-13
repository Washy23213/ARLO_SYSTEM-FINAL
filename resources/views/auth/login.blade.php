<x-layouts.app title="CMU OARL | Alumni Login">
    <style>
        :root {
            --login-ink: #10251a;
            --login-muted: #66756c;
            --login-line: #d7e0da;
            --login-paper: #ffffff;
            --login-mist: #eef4ef;
            --login-forest: #02681e;
            --login-deep: #00491e;
            --login-gold: #ffc600;
        }

        body {
            min-height: 100dvh;
            background:
                linear-gradient(115deg, rgba(255, 198, 0, 0.18) 0%, rgba(238, 244, 239, 0.84) 32%, rgba(225, 237, 230, 0.9) 100%) !important;
            font-family: var(--portal-font-family, "Plus Jakarta Sans", system-ui, sans-serif);
        }

        .login-shell {
            position: relative;
            width: 100%;
            min-height: 100dvh;
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(255, 255, 255, 0.42) 0 1px, transparent 1px 100%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.32) 0 1px, transparent 1px 100%);
            background-size: 46px 46px;
        }

        .login-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(0, 73, 30, 0.08), transparent 30%, rgba(255, 198, 0, 0.12)),
                linear-gradient(180deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.16));
        }

        .login-wrap {
            position: relative;
            z-index: 1;
            display: grid;
            width: min(1180px, 100%);
            min-height: 620px;
            grid-template-columns: minmax(0, 0.92fr) minmax(390px, 1.08fr);
            overflow: hidden;
            border: 1px solid rgba(8, 45, 24, 0.14);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 26px 70px rgba(8, 45, 24, 0.18);
        }

        .login-form-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 1.35rem;
            min-width: 0;
            padding: 3.2rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 251, 248, 0.96));
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 0.95rem;
        }

        .login-brand__mark {
            display: inline-flex;
            width: 3.35rem;
            height: 3.35rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid rgba(8, 45, 24, 0.14);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(8, 45, 24, 0.12);
        }

        .login-brand__mark img {
            width: 2.65rem;
            height: 2.65rem;
            object-fit: contain;
        }

        .login-brand__kicker,
        .login-visual-card__kicker {
            margin: 0;
            color: #946a0e;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .login-brand__name {
            margin: 0.14rem 0 0;
            color: var(--login-ink);
            font-size: 1.08rem;
            font-weight: 800;
            line-height: 1.15;
        }

        .login-header {
            max-width: 30rem;
        }

        .login-title {
            margin: 0;
            color: var(--login-ink);
            font-size: 2.45rem;
            font-weight: 800;
            letter-spacing: 0;
            line-height: 1.04;
        }

        .login-copy {
            margin: 0.8rem 0 0;
            color: var(--login-muted);
            font-size: 0.98rem;
            line-height: 1.7;
        }

        .login-status {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            border: 1px solid rgba(20, 87, 38, 0.22);
            border-radius: 8px;
            background: #edf8ef;
            padding: 0.88rem 0.95rem;
            color: #02681e;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.45;
        }

        .login-field {
            display: flex;
            min-height: 3.45rem;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid var(--login-line);
            border-radius: 8px;
            background: #fff;
            padding: 0 0.95rem;
            box-shadow: 0 7px 18px rgba(8, 45, 24, 0.05);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .login-field:focus-within {
            border-color: rgba(20, 87, 38, 0.7);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(20, 87, 38, 0.13), 0 12px 24px rgba(8, 45, 24, 0.1);
        }

        .login-field__icon {
            display: inline-flex;
            width: 2.15rem;
            height: 2.15rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #eef5f0;
            color: var(--login-forest);
            font-size: 1.1rem;
        }

        .login-input {
            min-width: 0;
            flex: 1;
            border: 0 !important;
            background: transparent !important;
            color: var(--login-ink);
            font-size: 0.98rem;
            font-weight: 600;
            outline: none !important;
            box-shadow: none !important;
        }

        .login-input::placeholder {
            color: #8b978f;
            font-weight: 600;
        }

        .login-toggle {
            display: inline-flex;
            width: 2.35rem;
            height: 2.35rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border: 1px solid transparent;
            border-radius: 8px;
            color: var(--login-deep);
            transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
        }

        .login-toggle:hover,
        .login-toggle:focus-visible {
            border-color: rgba(20, 87, 38, 0.18);
            background: #eef5f0;
            color: var(--login-forest);
            outline: none;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-top: 0.15rem;
            color: var(--login-ink);
            font-size: 0.9rem;
        }

        .login-check {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
            font-weight: 600;
        }

        .login-check input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--login-forest);
        }

        .nav-link-button {
            border: 0;
            background: transparent;
            color: var(--login-deep);
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 0;
        }

        .nav-link-button:hover,
        .nav-link-button:focus-visible {
            color: var(--login-forest);
            text-decoration: underline;
            outline: none;
        }

        .submit-btn {
            position: relative;
            display: inline-flex;
            min-height: 3.45rem;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            overflow: hidden;
            border: 1px solid rgba(8, 45, 24, 0.18);
            border-radius: 8px;
            background: linear-gradient(135deg, #00491e, #02681e);
            color: #fff;
            font-size: 0.98rem;
            font-weight: 800;
            box-shadow: 0 16px 32px rgba(20, 87, 38, 0.28);
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }

        .submit-btn::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 0%, rgba(255, 255, 255, 0.28) 48%, transparent 100%);
            transform: translateX(-120%);
            transition: transform 0.48s ease;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 36px rgba(20, 87, 38, 0.34);
            filter: saturate(1.04);
        }

        .submit-btn:hover::after {
            transform: translateX(120%);
        }

        .submit-btn span,
        .submit-btn i {
            position: relative;
            z-index: 1;
        }

        .login-signup-note {
            margin: 0;
            color: #4a5850;
            font-size: 0.92rem;
            text-align: center;
        }

        .login-signup-link {
            color: var(--login-deep);
            font-weight: 800;
        }

        .login-visual {
            position: relative;
            display: flex;
            min-width: 0;
            align-items: stretch;
            overflow: hidden;
            background: var(--login-deep);
        }

        .login-visual-image {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: saturate(0.92) contrast(1.05);
        }

        .login-visual::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background:
                linear-gradient(90deg, rgba(8, 45, 24, 0.52), rgba(8, 45, 24, 0.78)),
                linear-gradient(180deg, rgba(8, 45, 24, 0.18), rgba(8, 45, 24, 0.82));
            pointer-events: none;
        }

        .login-visual::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(255, 255, 255, 0.07) 0 1px, transparent 1px 100%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.06) 0 1px, transparent 1px 100%);
            background-size: 34px 34px;
        }

        .login-visual__content {
            position: relative;
            z-index: 3;
            display: flex;
            width: 100%;
            flex-direction: column;
            justify-content: space-between;
            gap: 2rem;
            padding: 2.4rem;
            color: #fff;
        }

        .login-visual-card {
            max-width: 31rem;
            border: 1px solid rgba(244, 230, 189, 0.22);
            border-radius: 8px;
            background: rgba(8, 45, 24, 0.62);
            padding: 1.35rem;
            box-shadow: 0 20px 46px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
        }

        .login-visual-card__title {
            margin: 0.42rem 0 0;
            color: #ffc600;
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: 0;
            line-height: 1.08;
        }

        .login-visual-card__copy {
            margin: 0.5rem 0 0;
            color: rgba(255, 255, 255, 0.76);
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .login-visual-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.7rem;
        }

        .login-visual-stat {
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.85rem;
        }

        .login-visual-stat strong {
            display: block;
            color: #fff;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .login-visual-stat span {
            display: block;
            margin-top: 0.25rem;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.76rem;
            line-height: 1.35;
        }

        @media (prefers-reduced-motion: reduce) {
            .login-field,
            .login-toggle,
            .submit-btn,
            .submit-btn::after {
                transition: none !important;
            }
        }

        @media (max-width: 980px) {
            .login-wrap {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .login-visual {
                display: none;
            }

            .login-form-panel {
                padding: 2.2rem;
            }
        }

        @media (max-width: 640px) {
            .login-shell {
                padding: 0.85rem;
            }

            .login-form-panel {
                padding: 1.45rem;
            }

            .login-title {
                font-size: 2rem;
            }

            .login-options {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="login-shell flex items-center justify-center p-5">
        <div class="login-wrap">
            <form id="loginForm" action="{{ route('login.submit') }}" method="POST" autocomplete="on" class="login-form-panel">
                @csrf

                <div class="login-brand" aria-label="CMU OARL">
                    <span class="login-brand__mark">
                        <img src="{{ asset('images/cmu-sidebar.png') }}" alt="CMU logo">
                    </span>
                    <span>
                        <span class="login-brand__kicker">Central Mindanao University</span>
                        <span class="login-brand__name block">Office of the Alumni Relations and Linkages (OARL)</span>
                    </span>
                </div>

                <div class="login-header">
                    <h1 class="login-title">Alumni Portal Login</h1>
                    <p class="login-copy">Sign in to access your account.</p>
                </div>

                @if(request()->query('registered') && !session('success'))
                    <div class="login-status">
                        <i class='bx bx-check-circle text-xl'></i>
                        <span>Registration successful. Sign in to continue.</span>
                    </div>
                @endif

                <label class="login-field" for="loginEmail">
                    <span class="login-field__icon" aria-hidden="true">
                        <i class='bx bx-envelope'></i>
                    </span>
                    <input
                        id="loginEmail"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Username or email"
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        required
                        class="login-input"
                    >
                </label>

                <label class="login-field" for="loginPassword">
                    <span class="login-field__icon" aria-hidden="true">
                        <i class='bx bx-lock-alt'></i>
                    </span>
                    <input
                        id="loginPassword"
                        type="password"
                        name="password"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                        class="login-input"
                    >
                    <button type="button" id="loginPasswordToggle" class="login-toggle" aria-label="Show password" aria-pressed="false">
                        <i class='bx bx-show text-xl' aria-hidden="true"></i>
                    </button>
                </label>

                <div class="login-options">
                    <label for="remember" class="login-check">
                        <input
                            id="remember"
                            type="checkbox"
                            name="remember"
                            value="1"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <span>Remember me</span>
                    </label>
                    <button type="button" class="nav-link-button login-forgot" data-nav-url="{{ route('password.request') }}">
                        Forgot password?
                    </button>
                </div>

                <button type="submit" class="submit-btn">
                    <i class='bx bx-log-in-circle text-xl' aria-hidden="true"></i>
                    <span>Sign in</span>
                </button>

                <p class="login-signup-note">
                    Don't have an account?
                    <button type="button" class="nav-link-button login-signup-link" data-nav-url="{{ route('signup') }}">
                        Sign up here
                    </button>
                </p>
            </form>

            <aside class="login-visual" aria-label="CMU OARL campus">
                <img src="{{ asset('images/login_img_fixed.png') }}" alt="Central Mindanao University building" class="login-visual-image">
                <div class="login-visual__content">
                    <div class="login-visual-card">
                        <p class="login-visual-card__kicker">CMU OARL</p>
                        <h2 class="login-visual-card__title">Office of the Alumni Relations and Linkages (OARL)</h2>
                        <p class="login-visual-card__copy">Official portal for alumni services and updates.</p>
                    </div>

                    <div class="login-visual-stats" aria-label="Portal highlights">
                        <div class="login-visual-stat">
                            <strong>Profile</strong>
                            <span>Records</span>
                        </div>
                        <div class="login-visual-stat">
                            <strong>Jobs</strong>
                            <span>Careers</span>
                        </div>
                        <div class="login-visual-stat">
                            <strong>Updates</strong>
                            <span>News</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-nav-url]').forEach(function (el) {
                el.addEventListener('click', function () {
                    const url = el.getAttribute('data-nav-url');
                    if (url) window.location.href = url;
                });
            });
        });
    </script>
</x-layouts.app>
