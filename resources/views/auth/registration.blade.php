<x-layouts.app title="CMU OARL | Alumni Registration">
    @php
        $quickAgreementOptions = [
            5 => 'Strongly Agree',
            4 => 'Agree',
            3 => 'Neutral',
            2 => 'Disagree',
            1 => 'Strongly Disagree',
        ];
    @endphp
    <style>
        :root {
            --reg-ink: #102317;
            --reg-muted: #4d6254;
            --reg-line: rgba(0, 73, 30, 0.12);
            --reg-green-900: #00491e;
            --reg-green-700: #02681e;
            --reg-gold: #ffc600;
        }

        .registration-shell {
            min-height: 100vh;
            padding: 1.2rem;
            background:
                radial-gradient(circle at top left, rgba(255, 198, 0, 0.16), transparent 24%),
                radial-gradient(circle at top right, rgba(2, 104, 30, 0.2), transparent 28%),
                linear-gradient(180deg, #eef7f0 0%, #f8fbf7 48%, #f1f7f2 100%);
        }

        .registration-page {
            width: min(1200px, 100%);
            margin: 0 auto;
        }

        .registration-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.2rem;
        }

        .registration-brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .registration-brand-mark {
            width: 3.1rem;
            height: 3.1rem;
            border-radius: 0.9rem;
            background:
                url('{{ asset('images/cmu-sidebar.png') }}') center/76% no-repeat,
                linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(221, 238, 224, 0.9));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 14px 24px rgba(11, 59, 31, 0.12);
            border: 1px solid rgba(11, 59, 31, 0.08);
        }

        .registration-brand-copy strong {
            display: block;
            color: var(--reg-ink);
            font-size: 1.7rem;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .registration-brand-copy span {
            display: block;
            margin-top: 0.2rem;
            color: var(--reg-muted);
            font-size: 0.9rem;
        }

        .registration-actions {
            display: flex;
            gap: 0.6rem;
        }

        .registration-pill,
        .registration-pill--solid {
            border-radius: 999px;
            min-height: 2.8rem;
            padding: 0.7rem 1.25rem;
            border: 1px solid var(--reg-line);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .registration-pill {
            background: rgba(255, 255, 255, 0.72);
            color: var(--reg-green-900);
        }

        .registration-pill--solid {
            background: linear-gradient(135deg, var(--reg-green-900), var(--reg-green-700));
            border-color: rgba(11, 59, 31, 0.35);
            color: #fff;
            box-shadow: 0 12px 26px rgba(20, 87, 38, 0.22);
        }

        .registration-main {
            display: grid;
            grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.35fr);
            gap: 1.2rem;
            align-items: stretch;
        }

        .registration-hero {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border-radius: 1.6rem;
            border: 1px solid var(--reg-line);
            background: linear-gradient(165deg, rgba(17, 74, 38, 0.95), rgba(40, 136, 66, 0.9));
            background-size: 140% 140%;
            color: #f4fff6;
            padding: 1.7rem 1.5rem;
            box-shadow: 0 20px 40px rgba(16, 35, 23, 0.14);
            animation: regHeroGradientShift 12s ease-in-out infinite alternate;
        }

        .registration-hero::before {
            content: "";
            position: absolute;
            inset: -20%;
            background: radial-gradient(circle at 18% 24%, rgba(255, 255, 255, 0.1), transparent 52%);
            pointer-events: none;
            animation: regHeroFloat 9s ease-in-out infinite;
        }

        .registration-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 84% 14%, rgba(212, 169, 53, 0.26), transparent 32%);
            pointer-events: none;
            animation: regGlowPulse 7s ease-in-out infinite;
        }

        .registration-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.12);
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            animation:
                regFadeUp 0.7s ease-out both,
                regBadgePulse 4.8s ease-in-out 1s infinite;
        }

        .registration-eyebrow::before {
            content: "";
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background: var(--reg-gold);
            box-shadow: 0 0 0 4px rgba(212, 169, 53, 0.2);
        }

        .registration-hero h2 {
            margin: 1rem 0 0.8rem;
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            line-height: 0.96;
            letter-spacing: -0.04em;
            animation:
                regFadeUp 0.9s ease-out 0.12s both,
                regHeadlineFloat 6s ease-in-out 1.1s infinite alternate;
        }

        .registration-hero h2 span {
            color: #d8ffd5;
        }

        .registration-hero p {
            margin: 0;
            color: rgba(244, 255, 246, 0.86);
            line-height: 1.7;
            font-size: 0.95rem;
            max-width: 30rem;
            animation:
                regFadeUp 0.95s ease-out 0.2s both,
                regParagraphFloat 7.5s ease-in-out 1.2s infinite alternate;
        }

        .registration-hero > * {
            position: relative;
            z-index: 1;
        }

        .registration-hero-showcase {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            flex: 1;
            min-height: 24rem;
            margin-top: 1.6rem;
            padding: 1.5rem;
            border-radius: 1.55rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .registration-hero-showcase::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background:
                radial-gradient(circle at 50% 18%, rgba(255, 255, 255, 0.12), transparent 34%),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: auto, 28px 28px, 28px 28px;
            background-position: center;
            opacity: 0.55;
            pointer-events: none;
        }

        .registration-hero-showcase::after {
            content: "";
            position: absolute;
            left: 50%;
            bottom: 1.35rem;
            width: 18rem;
            height: 7rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(212, 169, 53, 0.18), rgba(212, 169, 53, 0));
            filter: blur(24px);
            opacity: 0.45;
            transform: translateX(-50%);
            pointer-events: none;
        }

        .registration-hero-badge {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: min(14rem, 100%);
            padding: 0.8rem 0.95rem;
            border-radius: 1.2rem;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(12px);
            box-shadow: 0 16px 26px rgba(7, 24, 14, 0.12);
        }

        .registration-hero-badge__mark {
            width: 2.7rem;
            height: 2.7rem;
            flex-shrink: 0;
            border-radius: 0.95rem;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(226, 241, 229, 0.86));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.45rem;
            box-shadow: 0 12px 22px rgba(8, 32, 18, 0.18);
        }

        .registration-hero-badge__mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .registration-hero-badge__copy strong {
            display: block;
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #f8fff9;
        }

        .registration-hero-badge__copy span {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.76rem;
            color: rgba(244, 255, 246, 0.82);
        }

        .registration-hero-preview {
            position: relative;
            z-index: 1;
            width: min(24rem, 100%);
            border-radius: 1.35rem;
            padding: 0.8rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            box-shadow: 0 20px 34px rgba(7, 24, 14, 0.18);
        }

        .registration-hero-preview::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08), transparent 38%);
            pointer-events: none;
        }

        .registration-hero-preview__chrome {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-bottom: 0.55rem;
        }

        .registration-hero-preview__chrome span {
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.72);
        }

        .registration-hero-preview__screen {
            overflow: hidden;
            border-radius: 1.05rem;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(6, 38, 17, 0.12);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .registration-hero-preview__image {
            display: block;
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            object-position: center center;
            filter: saturate(0.98) brightness(1.04) contrast(1.01);
        }

        .registration-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 1.6rem;
            overflow: hidden;
            box-shadow: 0 20px 45px rgba(16, 35, 23, 0.1);
        }

        .registration-card-header {
            background: linear-gradient(130deg, #00491e, #02681e);
            color: #f3fff6;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .registration-card-header h1 {
            margin: 0;
            font-size: 1.3rem;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .registration-card-header p {
            margin: 0.3rem 0 0;
            color: rgba(242, 255, 246, 0.82);
            font-size: 0.85rem;
        }

        .registration-home {
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            width: 2.4rem;
            height: 2.4rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .registration-progress {
            padding: 1rem 1.5rem 0;
        }

        .registration-step-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            margin-top: 0.85rem;
        }

        .registration-step-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2rem;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            border: 1px solid rgba(13, 69, 35, 0.12);
            background: #f5f8f4;
            color: #5d705f;
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1.2;
            transition:
                transform 0.18s ease,
                border-color 0.18s ease,
                background-color 0.18s ease,
                box-shadow 0.18s ease;
        }

        .registration-step-chip.is-active {
            background: linear-gradient(135deg, #00491e, #02681e);
            border-color: rgba(11, 59, 31, 0.35);
            color: #fff;
            box-shadow: 0 10px 22px rgba(20, 87, 38, 0.16);
        }

        .registration-step-chip.is-complete {
            background: rgba(30, 122, 52, 0.08);
            border-color: rgba(30, 122, 52, 0.18);
            color: #1d5f33;
        }

        button.registration-step-chip {
            cursor: pointer;
        }

        button.registration-step-chip.is-clickable:hover {
            border-color: rgba(30, 122, 52, 0.3);
            background: rgba(30, 122, 52, 0.12);
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(20, 87, 38, 0.1);
        }

        button.registration-step-chip:focus-visible {
            outline: 3px solid rgba(30, 122, 52, 0.22);
            outline-offset: 2px;
        }

        button.registration-step-chip:disabled {
            cursor: not-allowed;
            opacity: 0.55;
            transform: none;
            box-shadow: none;
        }

        #multiStepForm {
            padding: 1.5rem;
        }

        #multiStepForm fieldset.form-step {
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(251, 253, 249, 0.98), rgba(244, 248, 244, 0.95));
            padding: 1.1rem;
        }

        #multiStepForm fieldset.form-step + fieldset.form-step {
            margin-top: 1rem;
        }

        #multiStepForm legend {
            color: #00491e;
            margin-bottom: 0.3rem;
        }

        .registration-step-copy {
            margin: 0.35rem 0 0;
            color: var(--reg-muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .registration-block {
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(249, 252, 248, 0.96), rgba(243, 248, 243, 0.92));
            padding: 1.15rem;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.58),
                0 12px 28px rgba(16, 35, 23, 0.04);
        }

        .registration-block--flat,
        .registration-rating-card--flat,
        .registration-reveal-panel--flat,
        .registration-muted-panel--flat {
            padding: 0;
            border: none;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .registration-block h3 {
            margin: 0;
            color: #173b26;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .registration-block__hint {
            margin: 0.35rem 0 1rem;
            color: #5d705f;
            font-size: 0.84rem;
            line-height: 1.5;
            max-width: 40rem;
        }

        .registration-question,
        .registration-scale-card p,
        .registration-rating-card p,
        #multiStepForm .space-y-3 > label:not([for]),
        #multiStepForm .registration-reveal-panel > label:not([for]) {
            margin: 0;
            color: #173b26;
            font-size: 1.02rem;
            font-weight: 700;
            line-height: 1.45;
            letter-spacing: -0.01em;
        }

        .registration-inline-note {
            margin-top: 0.45rem;
            color: #5d705f;
            font-size: 0.78rem;
            line-height: 1.45;
            max-width: 42rem;
        }

        #multiStepForm label {
            color: #224132;
            font-weight: 600;
        }

        #multiStepForm input:not([type="checkbox"]):not([type="radio"]),
        #multiStepForm select,
        #multiStepForm textarea {
            background: #fbfdf9;
            border-color: rgba(13, 69, 35, 0.18);
        }

        #multiStepForm select,
        .registration-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2.6rem !important;
            background-image:
                linear-gradient(45deg, transparent 50%, #2f5f45 50%),
                linear-gradient(135deg, #2f5f45 50%, transparent 50%);
            background-position:
                calc(100% - 18px) calc(50% - 2px),
                calc(100% - 12px) calc(50% - 2px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            cursor: pointer;
        }

        #multiStepForm input:not([type="checkbox"]):not([type="radio"]):focus,
        #multiStepForm select:focus,
        #multiStepForm textarea:focus {
            border-color: #02681e !important;
            box-shadow: 0 0 0 3px rgba(30, 122, 52, 0.16) !important;
        }

        #multiStepForm input[type="checkbox"],
        #multiStepForm input[type="radio"] {
            accent-color: #9ca3af;
            background: #ffffff !important;
            box-shadow: none !important;
            border-color: #9ca3af !important;
        }

        #multiStepForm input[type="checkbox"]:checked,
        #multiStepForm input[type="radio"]:checked {
            accent-color: #166534;
        }

        #multiStepForm input[type="checkbox"]:focus,
        #multiStepForm input[type="radio"]:focus {
            outline: none !important;
            box-shadow: none !important;
            border-color: #9ca3af !important;
        }

        /* registration-only: hide native reveal so only custom toggle is visible */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none !important;
        }

        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-contacts-auto-fill-button,
        input[type="password"]::-webkit-caps-lock-indicator,
        input[type="password"]::-webkit-textfield-decoration-container {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        /* registration-only: make selects look like dropdowns with a visible caret */
        .registration-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #fff;
            background-image:
                linear-gradient(45deg, transparent 50%, #6b7280 50%),
                linear-gradient(135deg, #6b7280 50%, transparent 50%);
            background-position: calc(100% - 16px) 50%, calc(100% - 12px) 50%;
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            padding-right: 2.5rem;
        }

        .registration-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: stretch;
        }

        .registration-radio-group:has(> .registration-radio-option:nth-child(2):last-child) {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: min(18rem, 100%);
        }

        .registration-radio-group:has(> .registration-radio-option:nth-child(2):last-child) .registration-radio-option {
            justify-content: center;
        }

        .registration-radio-group:has(> .registration-radio-option:nth-child(3):last-child) {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            max-width: min(34rem, 100%);
        }

        .registration-radio-group:has(> .registration-radio-option:nth-child(3):last-child) .registration-radio-option {
            justify-content: center;
        }

        .registration-radio-option {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            min-height: 3.05rem;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 0.95rem;
            background: rgba(255, 255, 255, 0.92);
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                background-color 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.2s ease;
        }

        .registration-radio-group label {
            margin-right: 0;
        }

        .registration-radio-option:hover {
            border-color: rgba(30, 122, 52, 0.24);
            background: rgba(255, 255, 255, 0.98);
            transform: translateY(-1px);
        }

        .registration-radio-option span {
            color: #224132;
            font-weight: 700;
            line-height: 1.35;
        }

        .registration-radio-option:has(input:checked) {
            border-color: rgba(30, 122, 52, 0.42);
            background: rgba(30, 122, 52, 0.08);
            box-shadow: 0 10px 20px rgba(20, 87, 38, 0.08);
        }

        .registration-radio-option:has(input:disabled) {
            cursor: default;
        }

        .registration-radio-option:has(input:disabled):hover {
            transform: none;
        }

        .registration-radio-option:has(input:disabled:not(:checked)) {
            opacity: 0.7;
            border-color: rgba(13, 69, 35, 0.08);
            background: rgba(244, 248, 244, 0.9);
            box-shadow: none;
            transform: none;
        }

        .registration-radio-option:has(input:disabled:not(:checked)):hover {
            border-color: rgba(13, 69, 35, 0.1);
            background: rgba(255, 255, 255, 0.92);
            transform: none;
        }

        .registration-radio-group.registration-radio-group--compact-options {
            display: flex !important;
            flex-wrap: wrap;
            align-items: center;
            width: 100%;
            gap: 1.15rem;
            margin-top: 0.35rem;
            max-width: none !important;
        }

        .registration-radio-group.registration-radio-group--compact-options .registration-radio-option {
            min-height: auto;
            padding: 0;
            border: none;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            gap: 0.55rem;
            justify-content: flex-start;
        }

        .registration-radio-group.registration-radio-group--compact-options .registration-radio-option:hover,
        .registration-radio-group.registration-radio-group--compact-options .registration-radio-option:has(input:checked) {
            border-color: transparent;
            background: transparent;
            box-shadow: none;
            transform: none;
        }

        .registration-radio-group.registration-radio-group--compact-options .registration-radio-option span {
            font-weight: 600;
        }

        .registration-field-grid {
            display: grid;
            gap: 1rem 1.15rem;
            align-items: start;
        }

        .registration-field-grid > div {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            min-width: 0;
        }

        .registration-field-grid > .hidden {
            display: none;
        }

        .registration-mini-panel {
            padding: 1rem 1.05rem;
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 0.95rem;
            background: rgba(247, 250, 246, 0.88);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.62);
        }

        .registration-email-verify {
            position: relative;
            display: grid;
            gap: 0.65rem;
            padding: 0.8rem 0.9rem;
            border: 1px solid #d6e5da;
            border-radius: 0.75rem;
            background: #ffffff;
            box-shadow: 0 3px 10px rgba(15, 45, 25, 0.035);
            overflow: visible;
        }

        .registration-email-verify::before {
            display: none;
        }

        .registration-email-verify.is-verified {
            border-color: #9cc8a8;
            background: #fbfdfb;
        }

        .registration-email-verify.is-error {
            border-color: #e6b8b8;
        }

        .registration-email-verify__backdrop {
            position: fixed;
            z-index: 109;
            inset: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            background: rgba(7, 30, 15, 0.42);
            backdrop-filter: blur(3px);
            cursor: default;
        }

        .registration-email-verify.is-mini-view {
            position: fixed;
            z-index: 110;
            top: 50%;
            left: 50%;
            width: min(calc(100vw - 2rem), 32rem);
            max-height: min(calc(100vh - 2rem), 42rem);
            overflow-y: auto;
            overscroll-behavior: contain;
            transform: translate(-50%, -50%);
            margin: 0 !important;
            padding: 1rem;
            border-color: #cddfd2;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 24px 68px rgba(8, 34, 17, 0.24);
        }

        .registration-email-verify__close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 2rem;
            height: 2rem;
            padding: 0;
            border: 1px solid #dce9df;
            border-radius: 999px;
            background: #f7faf8;
            color: #315840;
            font-size: 1.15rem;
            line-height: 1;
        }

        .registration-email-verify__close:hover {
            border-color: #b9d1c0;
            background: #edf5ef;
            color: #0b5b2c;
        }

        .registration-email-verify__mini-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-top: 0.45rem;
            padding: 0.55rem 0.7rem;
            border: 1px solid #dce9df;
            border-radius: 0.6rem;
            background: #f7faf8;
            color: #43584b;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        .registration-email-verify__mini-trigger button {
            flex: 0 0 auto;
            padding: 0.38rem 0.65rem;
            border: 1px solid #cbdccf;
            border-radius: 0.45rem;
            background: #ffffff;
            color: #0b5b2c;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .registration-email-verify__mini-trigger button:hover {
            border-color: #9fb8a7;
            background: #f1f7f3;
        }

        body.email-verification-modal-open {
            overflow: hidden;
        }

        .registration-email-verify__top {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 0.1rem;
        }

        .registration-email-verify__identity {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            min-width: 0;
        }

        .registration-email-verify__icon {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: 1px solid #dbe7df;
            border-radius: 0.45rem;
            color: #0b5b2c;
            background: #f6faf7;
        }

        .registration-email-verify__icon i {
            font-size: 1.05rem;
            line-height: 1;
        }

        .registration-email-verify__heading {
            display: block;
            min-width: 0;
        }

        .registration-email-verify__title {
            display: block;
            color: #0b2615;
            font-size: 0.96rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .registration-email-verify__copy {
            display: block;
            margin: 0.16rem 0 0;
            color: #58695f;
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .registration-email-verify__actions,
        .registration-email-verify__code-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
        }

        .registration-email-verify__code-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.65rem;
        }

        .registration-email-verify__button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 2.15rem;
            border-radius: 0.45rem;
            padding: 0.52rem 0.78rem;
            font-size: 0.84rem;
            font-weight: 700;
            line-height: 1;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease,
                background-color 0.2s ease;
        }

        .registration-email-verify__button i {
            font-size: 1rem;
            line-height: 1;
        }

        .registration-email-verify__button--primary {
            color: #ffffff;
            border: 1px solid #084923;
            background: #0b5b2c;
            box-shadow: none;
        }

        .registration-email-verify__button--primary:hover {
            background: #084923;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(11, 91, 44, 0.16);
        }

        .registration-email-verify__button--secondary {
            color: #0b5b2c;
            border: 1px solid #cfded4;
            background: #ffffff;
            box-shadow: none;
        }

        .registration-email-verify__button--secondary:hover {
            border-color: #9fb8a7;
            background: #f7faf8;
            transform: translateY(-1px);
        }

        .registration-email-verify__button:disabled {
            cursor: not-allowed;
            opacity: 0.68;
            transform: none;
            box-shadow: none;
        }

        .registration-email-verify__code {
            position: relative;
            display: grid;
            gap: 0.55rem;
            padding: 0.75rem;
            border: 1px solid #dce9df;
            border-radius: 0.7rem;
            background: #f5f9f6;
        }

        .registration-email-verify__code label {
            margin: 0;
            color: #274032;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .registration-email-verify__otp {
            position: relative;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.4rem;
            width: min(100%, 23rem);
            justify-self: center;
        }

        .registration-email-verify__otp-digit {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            min-height: 2.7rem;
            border: 1px solid #cbdccf;
            border-radius: 0.5rem;
            background: #ffffff;
            color: #0b2615;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1;
            transition: border-color 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
        }

        .registration-email-verify__otp-digit.is-current {
            border-color: #0b5b2c;
            background: #f8fcf9;
            box-shadow: 0 0 0 2px rgba(11, 91, 44, 0.1);
        }

        .registration-email-verify__otp:focus-within .registration-email-verify__otp-digit {
            border-color: #74a884;
        }

        .registration-email-verify__otp:focus-within .registration-email-verify__otp-digit.is-current {
            border-color: #0b5b2c;
            background: #f1f8f3;
            box-shadow: 0 0 0 3px rgba(11, 91, 44, 0.12);
        }

        .registration-email-verify__otp.is-invalid:focus-within .registration-email-verify__otp-digit {
            border-color: #d77b7b;
            background: #fffafa;
            box-shadow: none;
        }

        .registration-email-verify__otp.is-invalid .registration-email-verify__otp-digit {
            border-color: #d77b7b;
            background: #fffafa;
        }

        .registration-email-verify__otp-input {
            position: absolute;
            z-index: 1;
            inset: 0;
            display: block;
            width: 100%;
            height: 100%;
            min-height: 0;
            margin: 0 !important;
            padding: 0;
            border: 0 !important;
            border-radius: 0.5rem;
            outline: 0;
            background: transparent !important;
            box-shadow: none !important;
            color: transparent !important;
            caret-color: transparent;
            cursor: text;
            font-size: 16px;
            letter-spacing: 0;
            opacity: 0;
        }

        .registration-email-verify__resend-button {
            justify-self: start;
            min-height: 1.5rem;
            padding: 0.15rem 0;
            border: 0;
            background: transparent;
            color: #0b5b2c;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.035em;
            text-transform: uppercase;
        }

        .registration-email-verify__resend-button:hover {
            color: #084923;
            text-decoration: underline;
            text-underline-offset: 0.18rem;
        }

        .registration-email-verify__code-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.45rem;
        }

        .registration-email-verify__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.55rem;
            padding-top: 0.05rem;
        }

        .registration-email-verify__support {
            display: grid;
            gap: 0.2rem;
            padding: 0.65rem 0.75rem;
            border: 1px solid #dce8df;
            border-radius: 0.7rem;
            background: #f7faf8;
            color: #43584b;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .registration-email-verify__support-title {
            color: #173b26;
            font-weight: 700;
        }

        .registration-email-verify__support-link {
            width: fit-content;
            text-decoration: underline;
            text-underline-offset: 0.15em;
        }

        .registration-email-verify__support-link:hover {
            color: #0b6b35;
        }

        .registration-email-verify__support-link:focus-visible {
            border-radius: 0.15rem;
            outline: 2px solid #0b6b35;
            outline-offset: 2px;
        }

        .registration-email-verify__message {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin: 0;
            padding: 0.35rem 0.55rem;
            border: 0;
            border-radius: 0.45rem;
            background: #f7faf8;
            color: #43584b;
            font-size: 0.8rem;
            line-height: 1.45;
        }

        .registration-email-verify__message:empty {
            display: none;
        }

        .registration-email-verify__message::before {
            content: "";
            flex-shrink: 0;
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 999px;
            background: #17854b;
            box-shadow: none;
        }

        .registration-email-verify.is-verified .registration-email-verify__message {
            border-color: #cde4d3;
            background: #f2faf4;
            color: #0b5b2c;
            font-weight: 600;
        }

        .registration-email-verify.is-error .registration-email-verify__message {
            border-color: #f0c9c9;
            background: #fff8f8;
            color: #b91c1c;
        }

        .registration-email-verify.is-error .registration-email-verify__message::before {
            background: #dc2626;
            box-shadow: none;
        }

        .registration-email-verify__timing {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem 0.75rem;
            margin: 0;
            color: #56695d;
            font-size: 0.82rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .registration-email-verify__timing::before {
            display: none;
        }

        .registration-email-verify__timing-item {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            min-height: auto;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #47594e;
        }

        .registration-email-verify__timing-item i {
            color: #0b5b2c;
            font-size: 1rem;
        }

        .registration-email-verify__timing-label {
            color: #66776c;
            font-weight: 600;
        }

        .registration-email-verify__timing-value {
            color: #173b26;
            font-weight: 700;
        }

        .registration-email-verify__timing-item--expired {
            border-color: transparent;
            background: transparent;
        }

        .registration-email-verify__timing-item--expired i,
        .registration-email-verify__timing-item--expired .registration-email-verify__timing-value {
            color: #b91c1c;
        }

        .registration-password-gate {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.95rem 1rem;
            border: 1px dashed rgba(20, 87, 38, 0.18);
            border-radius: 0.95rem;
            background: rgba(247, 250, 246, 0.9);
            color: #51665a;
            font-size: 0.9rem;
            line-height: 1.45;
        }

        .registration-password-gate i {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 999px;
            color: #0b5b2c;
            background: rgba(20, 87, 38, 0.1);
            font-size: 1.15rem;
        }

        .registration-newsletter-option {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 0.85rem;
            align-items: flex-start;
            padding: 0.9rem 1rem;
            border: 1px solid #dbe7df;
            border-radius: 0.5rem;
            background: #ffffff;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(15, 45, 25, 0.04);
        }

        .registration-newsletter-option:hover {
            border-color: rgba(30, 122, 52, 0.24);
        }

        .registration-newsletter-option__control {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.2rem;
            height: 2.2rem;
            border-radius: 0.72rem;
            background: rgba(30, 122, 52, 0.08);
            color: #00491e;
        }

        .registration-newsletter-option__control input {
            width: 1.05rem !important;
            height: 1.05rem !important;
            margin: 0;
        }

        .registration-newsletter-option__body {
            min-width: 0;
        }

        .registration-newsletter-option__header {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .registration-newsletter-option__title {
            color: #173b26;
            font-size: 0.98rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .registration-newsletter-option__copy {
            display: block;
            margin-top: 0.35rem;
            color: #5d705f;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .registration-email-verify.hidden,
        .registration-email-verify__backdrop.hidden,
        .registration-email-verify__mini-trigger.hidden,
        .registration-email-verify__code.hidden,
        .registration-email-verify__footer.hidden,
        .registration-email-verify__button.hidden,
        .registration-email-verify__close.hidden,
        .registration-email-verify__timing.hidden,
        .registration-password-gate.hidden {
            display: none;
        }

        @media (max-width: 640px) {
            .registration-email-verify__top {
                align-items: stretch;
                flex-direction: column;
            }

            .registration-email-verify.is-mini-view .registration-email-verify__top {
                align-items: flex-start;
                flex-direction: row;
            }

            .registration-email-verify.is-mini-view .registration-email-verify__actions {
                width: auto;
                flex: 0 0 auto;
                margin-left: auto;
            }

            .registration-email-verify__footer {
                align-items: flex-start;
                flex-direction: column;
            }

            .registration-email-verify__mini-trigger {
                align-items: flex-start;
                flex-direction: column;
            }

            .registration-email-verify__mini-trigger button {
                width: 100%;
            }

            .registration-email-verify__actions,
            .registration-email-verify__button {
                width: 100%;
            }

            .registration-email-verify__otp {
                gap: 0.35rem;
            }

            .registration-email-verify__otp-digit {
                min-height: 2.55rem;
                border-radius: 0.4rem;
                font-size: 1rem;
            }

            .registration-email-verify__resend-button {
                width: auto;
                align-self: flex-start;
                justify-content: flex-start;
            }

            .registration-email-verify__button {
                justify-content: center;
            }
        }

        .registration-mini-view {
            position: fixed;
            top: 50%;
            left: 50%;
            width: min(92vw, 38rem);
            max-height: min(82vh, 44rem);
            overflow-y: auto;
            transform: translate(-50%, -50%);
            padding: 1rem 1.05rem;
            border: 1px solid rgba(13, 69, 35, 0.11);
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(252, 254, 251, 0.98), rgba(246, 250, 245, 0.96));
            box-shadow:
                0 0 0 100vmax rgba(13, 31, 19, 0.18),
                0 28px 60px rgba(16, 35, 23, 0.16),
                inset 0 1px 0 rgba(255, 255, 255, 0.7),
                0 10px 22px rgba(16, 35, 23, 0.06);
            z-index: 80;
        }

        .registration-mini-view .registration-field-grid {
            gap: 0.9rem 1rem;
        }

        .registration-mini-view .registration-field-grid--2 {
            grid-template-columns: minmax(0, 1fr);
        }

        .registration-mini-view .registration-check-grid {
            gap: 0.8rem;
        }

        .registration-mini-view .registration-check-option {
            min-height: 3.55rem;
            padding: 0.8rem 0.9rem;
        }

        .registration-mini-view__title {
            margin: 0;
            color: #173b26;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .registration-mini-view__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.9rem;
            margin-bottom: 0.85rem;
        }

        .registration-mini-view__close {
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            border: 1px solid rgba(13, 69, 35, 0.12);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.94);
            color: #173b26;
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                background-color 0.2s ease,
                transform 0.2s ease;
        }

        .registration-mini-view__close:hover {
            border-color: rgba(30, 122, 52, 0.24);
            background: rgba(247, 250, 246, 0.98);
            transform: translateY(-1px);
        }

        .registration-mini-view__footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 0.9rem;
            border-top: 1px solid rgba(13, 69, 35, 0.08);
        }

        .registration-mini-view__done {
            min-height: 2.55rem;
            padding: 0.6rem 1.15rem;
            border: 1px solid rgba(30, 122, 52, 0.16);
            border-radius: 0.8rem;
            background: linear-gradient(135deg, #00491e, #02681e);
            color: #fff;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
            box-shadow: 0 12px 24px rgba(20, 87, 38, 0.18);
        }

        .registration-mini-view__done:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(20, 87, 38, 0.22);
        }

        .registration-mini-view__secondary {
            min-height: 2.55rem;
            padding: 0.6rem 1.05rem;
            border: 1px solid rgba(13, 69, 35, 0.12);
            border-radius: 0.8rem;
            background: rgba(255, 255, 255, 0.95);
            color: #173b26;
            font-weight: 700;
            line-height: 1;
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                background-color 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
            box-shadow: 0 10px 20px rgba(16, 35, 23, 0.08);
        }

        .registration-mini-view__secondary:hover {
            border-color: rgba(30, 122, 52, 0.22);
            background: rgba(247, 250, 246, 0.98);
            transform: translateY(-1px);
            box-shadow: 0 14px 24px rgba(16, 35, 23, 0.12);
        }

        .registration-mini-view__secondary[disabled],
        .registration-mini-view__done[disabled] {
            opacity: 0.68;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .registration-mini-view__review {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-top: 0.2rem;
            padding: 0.8rem 0.95rem;
            border: 1px solid rgba(13, 69, 35, 0.12);
            border-radius: 0.95rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 251, 247, 0.96));
            color: #173b26;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.25;
            text-align: left;
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                background-color 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .registration-mini-view__review:hover {
            border-color: rgba(30, 122, 52, 0.22);
            background: rgba(247, 250, 246, 0.98);
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(20, 87, 38, 0.08);
        }

        .registration-mini-view__review.hidden {
            display: none;
        }

        .registration-mini-view__review-copy {
            display: flex;
            flex-direction: column;
            gap: 0.18rem;
        }

        .registration-mini-view__review-label {
            color: #173b26;
            font-size: 0.95rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .registration-mini-view__review-note {
            color: #5d705f;
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.4;
        }

        .registration-mini-view__review-action {
            flex-shrink: 0;
            padding: 0.42rem 0.72rem;
            border: 1px solid rgba(30, 122, 52, 0.16);
            border-radius: 999px;
            background: rgba(30, 122, 52, 0.08);
            color: #00491e;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .registration-submit-overview__lead {
            margin: 0 0 0.25rem;
            color: #4f6353;
            font-size: 0.88rem;
            line-height: 1.55;
        }

        .registration-submit-overview__grid {
            display: grid;
            gap: 0.9rem;
        }

        .registration-submit-overview__item {
            padding: 0.9rem 1rem;
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 0.95rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 250, 246, 0.95));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        .registration-submit-overview__label {
            display: block;
            margin-bottom: 0.35rem;
            color: #5d705f;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .registration-submit-overview__value {
            color: #173b26;
            font-size: 0.98rem;
            font-weight: 800;
            line-height: 1.45;
            word-break: break-word;
        }

        .registration-submit-overview__value--secret {
            font-family: var(--portal-font-mono, ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace);
            letter-spacing: 0.05em;
        }

        .registration-submit-overview__password {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
        }

        .registration-submit-overview__toggle {
            flex-shrink: 0;
            min-height: 2.15rem;
            padding: 0.45rem 0.85rem;
            border: 1px solid rgba(30, 122, 52, 0.16);
            border-radius: 999px;
            background: rgba(30, 122, 52, 0.08);
            color: #00491e;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            cursor: pointer;
            transition:
                background-color 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease;
        }

        .registration-submit-overview__toggle:hover {
            border-color: rgba(30, 122, 52, 0.24);
            background: rgba(30, 122, 52, 0.12);
            transform: translateY(-1px);
        }

        .registration-submit-overview__toggle[disabled] {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none;
        }

        .registration-submit-overview__note {
            margin: 0;
            color: #4f6353;
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .registration-uniform-field > label {
            display: flex;
            align-items: flex-start;
            min-height: 3rem;
        }

        .registration-block.hidden,
        .registration-mini-panel.hidden,
        .registration-mini-view.hidden,
        .registration-field-grid.hidden,
        .registration-check-grid.hidden,
        .registration-scale-grid.hidden,
        .registration-reveal-panel.hidden {
            display: none;
        }

        .registration-reveal-panel {
            padding: 1rem;
            border: 1px dashed rgba(13, 69, 35, 0.14);
            border-radius: 1rem;
            background: rgba(247, 250, 246, 0.92);
        }

        .registration-reveal-panel__title {
            margin: 0 0 0.35rem;
            color: #173b26;
            font-size: 0.95rem;
            font-weight: 800;
        }

        .registration-reveal-panel__hint {
            margin: 0 0 0.9rem;
            color: #5d705f;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .registration-check-grid {
            display: grid;
            gap: 0.9rem;
        }

        .registration-check-option {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-height: 4.15rem;
            padding: 0.95rem 1rem;
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 0.9rem;
            background: rgba(255, 255, 255, 0.88);
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                background-color 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.2s ease;
        }

        .registration-check-option:hover {
            border-color: rgba(30, 122, 52, 0.24);
            background: rgba(255, 255, 255, 0.96);
            transform: translateY(-1px);
        }

        .registration-check-option span {
            color: #224132;
            font-weight: 600;
            line-height: 1.45;
        }

        .registration-check-option:has(input:checked) {
            border-color: rgba(30, 122, 52, 0.42);
            background: rgba(30, 122, 52, 0.08);
            box-shadow: 0 10px 20px rgba(20, 87, 38, 0.08);
        }

        .registration-compact-note {
            margin: 0;
            color: #5d705f;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        #multiStepForm input[type="file"] {
            width: 100%;
            padding: 0.7rem 0.8rem;
            border: 1px dashed rgba(13, 69, 35, 0.22);
            border-radius: 0.95rem;
            background: rgba(255, 255, 255, 0.94);
            color: #224132;
        }

        #multiStepForm input[type="file"]::file-selector-button {
            margin-right: 0.85rem;
            padding: 0.65rem 0.95rem;
            border: 1px solid rgba(13, 69, 35, 0.16);
            border-radius: 0.8rem;
            background: #f4f8f3;
            color: #173b26;
            font-weight: 700;
            cursor: pointer;
        }

        .registration-scale-grid {
            display: grid;
            gap: 0.9rem;
        }

        .registration-scale-card {
            padding: 0.95rem;
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 0.95rem;
            background: rgba(255, 255, 255, 0.84);
        }

        .registration-scale-card p {
            margin: 0 0 0.7rem;
        }

        .registration-rating-grid {
            display: grid;
            gap: 1.1rem;
        }

        .registration-rating-card {
            padding: 1.1rem;
            border: 1px solid rgba(13, 69, 35, 0.1);
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(247, 250, 246, 0.94));
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.72),
                0 10px 24px rgba(16, 35, 23, 0.04);
        }

        .registration-rating-card p {
            margin: 0 0 0.95rem;
        }

        #multiStepForm .registration-radio-error,
        #multiStepForm .registration-rating-card p.registration-radio-error {
            margin: 0.45rem 0 0;
            color: #dc2626;
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.45;
            letter-spacing: 0;
        }

        .registration-rating-options {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            width: 100%;
            gap: 1.15rem;
            margin-top: 0.35rem;
        }

        .registration-rating-option {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-height: auto;
            padding: 0;
            border: none;
            border-radius: 0;
            background: transparent;
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                background-color 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.2s ease;
        }

        .registration-rating-option:hover {
            border-color: transparent;
            background: transparent;
            box-shadow: none;
            transform: none;
        }

        .registration-rating-option span {
            color: #224132;
            font-weight: 600;
            line-height: 1.35;
        }

        .registration-rating-option:has(input:checked) {
            border-color: transparent;
            background: transparent;
            box-shadow: none;
        }

        .registration-muted-panel {
            padding: 1.05rem 1.1rem;
            border: 1px dashed rgba(13, 69, 35, 0.14);
            border-radius: 1rem;
            background: rgba(245, 248, 244, 0.92);
            color: #4d6254;
            line-height: 1.6;
        }

        @media (min-width: 640px) {
            .registration-rating-options--3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 768px) {
            .registration-field-grid--2,
            .registration-check-grid--2,
            .registration-scale-grid--2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .registration-rating-grid--2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .registration-rating-options--5 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1100px) {
            .registration-rating-options--5 {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }

        .registration-submit-btn {
            position: relative;
            overflow: hidden;
            min-height: 2.75rem;
            padding: 0.6rem 1.5rem;
            border-radius: 0.75rem;
            background: linear-gradient(130deg, #00491e, #02681e);
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.01em;
            box-shadow: 0 14px 28px rgba(20, 87, 38, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .registration-submit-btn::after {
            content: "";
            position: absolute;
            inset: 0;
            transform: translateX(-120%);
            background: linear-gradient(95deg, transparent, rgba(255, 255, 255, 0.28), transparent);
            transition: transform 0.55s ease;
        }

        .registration-submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(20, 87, 38, 0.3);
        }

        .registration-submit-btn:hover::after {
            transform: translateX(120%);
        }

        .registration-consent-overlay {
            position: fixed;
            inset: 0;
            z-index: 90;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(9, 25, 15, 0.58);
            backdrop-filter: blur(10px);
        }

        .registration-consent-overlay.hidden {
            display: none;
        }

        .registration-consent-dialog {
            width: min(42rem, 100%);
            overflow: hidden;
            border-radius: 1.5rem;
            border: 1px solid rgba(13, 69, 35, 0.14);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 26px 60px rgba(7, 24, 14, 0.28);
        }

        .registration-consent-dialog__header {
            padding: 1rem 1.35rem;
            background: linear-gradient(130deg, #00491e, #02681e);
            color: #f4fff6;
        }

        .registration-consent-dialog__eyebrow {
            margin: 0;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(244, 255, 246, 0.78);
        }

        .registration-consent-dialog__header h2 {
            margin: 0.45rem 0 0;
            font-size: 1.35rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }

        .registration-consent-dialog__body {
            padding: 1.35rem;
        }

        .registration-consent-dialog__lead {
            margin: 0;
            color: #486050;
            line-height: 1.65;
            font-size: 0.95rem;
        }

        .registration-consent-dialog__copy {
            margin-top: 1rem;
            border-radius: 1.1rem;
            border: 1px solid rgba(13, 69, 35, 0.1);
            background: linear-gradient(180deg, rgba(249, 252, 248, 0.98), rgba(243, 248, 243, 0.94));
            padding: 1rem 1rem 1.05rem;
        }

        .registration-consent-dialog__copy p {
            margin: 0;
            color: #2f4c3b;
            line-height: 1.7;
            font-size: 0.92rem;
        }

        .registration-consent-dialog__check {
            margin-top: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.8rem;
            border-radius: 1rem;
            border: 1px solid rgba(30, 122, 52, 0.16);
            background: rgba(30, 122, 52, 0.05);
            padding: 0.95rem 1rem;
            color: #224132;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .registration-consent-dialog__check input {
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .registration-consent-dialog__actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 0 1.35rem 1.35rem;
        }

        .registration-consent-dialog__actions .registration-pill--solid[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }

        .registration-consent-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-radius: 1rem;
            border: 1px solid rgba(30, 122, 52, 0.18);
            background: rgba(30, 122, 52, 0.06);
            padding: 0.95rem 1rem;
        }

        .registration-consent-status.hidden {
            display: none;
        }

        .registration-consent-status__copy strong {
            display: block;
            color: #173b26;
            font-size: 0.92rem;
        }

        .registration-consent-status__copy span {
            display: block;
            margin-top: 0.2rem;
            color: #5d705f;
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .registration-consent-status__action {
            border: none;
            background: transparent;
            color: #166534;
            font-weight: 700;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        @keyframes regFadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes regHeroGradientShift {
            0% {
                background-position: 0% 0%;
            }
            100% {
                background-position: 100% 100%;
            }
        }

        @keyframes regGlowPulse {
            0%, 100% {
                opacity: 0.7;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.04);
            }
        }

        @keyframes regHeroFloat {
            0%, 100% {
                transform: translate3d(0, 0, 0);
            }
            50% {
                transform: translate3d(2%, -2%, 0);
            }
        }

        @keyframes regHeadlineFloat {
            0% {
                transform: translate3d(0, 0, 0);
            }
            100% {
                transform: translate3d(0, -7px, 0);
            }
        }

        @keyframes regParagraphFloat {
            0% {
                transform: translate3d(0, 0, 0);
            }
            100% {
                transform: translate3d(0, -4px, 0);
            }
        }

        @keyframes regBadgePulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
            50% {
                box-shadow: 0 0 0 6px rgba(255, 255, 255, 0.08);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .registration-hero,
            .registration-hero::before,
            .registration-hero::after,
            .registration-eyebrow,
            .registration-hero h2,
            .registration-hero p,
            .registration-hero-showcase::after,
            .registration-hero-preview,
            .registration-hero-badge {
                animation: none !important;
            }
        }

        @media (max-width: 1024px) {
            .registration-main {
                grid-template-columns: 1fr;
            }

            .registration-hero-showcase {
                min-height: 20rem;
            }
        }

        @media (max-width: 700px) {
            .registration-topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .registration-actions {
                width: 100%;
            }

            .registration-pill,
            .registration-pill--solid {
                width: 100%;
            }

            .registration-brand-copy strong {
                font-size: 1.5rem;
            }

            .registration-hero {
                padding: 1.4rem 1.15rem;
            }

            .registration-hero-showcase {
                min-height: 17rem;
                margin-top: 1.2rem;
                padding: 1rem;
                gap: 1rem;
            }

            .registration-hero-badge {
                width: min(12.5rem, 100%);
                padding: 0.65rem 0.75rem;
            }

            .registration-hero-badge__mark {
                width: 2.35rem;
                height: 2.35rem;
                border-radius: 0.8rem;
            }

            .registration-hero-badge__copy strong {
                font-size: 0.82rem;
            }

            .registration-hero-preview {
                width: min(19rem, 100%);
                padding: 0.65rem;
                border-radius: 1.2rem;
            }
        }
    </style>
    <div class="registration-shell">
        <div class="registration-page">
            <header class="registration-topbar">
                <div class="registration-brand">
                    <span class="registration-brand-mark" aria-hidden="true"></span>
                    <span class="registration-brand-copy">
                        <strong>CMU OARL</strong>
                        <span>Alumni Tracer and Employment Analytics</span>
                    </span>
                </div>
            </header>

            <div class="registration-main">
                <aside class="registration-hero">
                    <span class="registration-eyebrow">Central Mindanao University</span>
                    <h2>
                        One portal for alumni records, <span>insights, and outreach.</span>
                    </h2>
                    <p>
                        Complete your registration to be part of the CMU OARL network. Your profile helps
                        the office keep tracer-ready records and generate reliable employment analytics.
                    </p>

                    <div class="registration-hero-showcase" aria-hidden="true">
                        <div class="registration-hero-badge">
                            <span class="registration-hero-badge__mark">
                                <img src="{{ asset('images/cmu-sidebar.png') }}" alt="">
                            </span>
                            <span class="registration-hero-badge__copy">
                                <strong>CMU OARL</strong>
                                <span>Campus portal</span>
                            </span>
                        </div>

                        <div class="registration-hero-preview">
                            <div class="registration-hero-preview__chrome">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <div class="registration-hero-preview__screen">
                                <img src="{{ asset('images/login_img_fixed.png') }}" alt="" class="registration-hero-preview__image">
                            </div>
                        </div>
                    </div>

                </aside>

                <section class="registration-card">
                    <div class="registration-card-header">
                        <div>
                            <h1>Alumni Registration</h1>
                            <p>Same details, grouped into fewer sections for a faster signup.</p>
                        </div>
                        <a href="{{ route('login') }}" class="registration-home" aria-label="Back to login">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 3.172 3 10v10a1 1 0 0 0 1 1h6v-7h4v7h6a1 1 0 0 0 1-1V10l-9-6.828Z"/>
                            </svg>
                        </a>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="registration-progress">
                        <div class="flex items-center justify-between text-xs font-semibold text-gray-500 mb-2">
                            <span id="stepText">Section 1 of 8</span>
                            <span id="stepTitle">Verification</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div id="progressBar" class="h-2 bg-[#1c4000] w-[15%] transition-all duration-300"></div>
                        </div>
                        <div id="stepChips" class="registration-step-chips" aria-label="Registration sections"></div>
                    </div>

                    {{-- Form --}}
                    <form
                        action="{{ route('register.submit') }}"
                        method="POST"
                        id="multiStepForm"
                        enctype="multipart/form-data"
                        data-email-verification-send-url="{{ route('signup.email-verification.send') }}"
                        data-email-verification-verify-url="{{ route('signup.email-verification.verify') }}"
                    >
                @csrf
                @php($privacyConsentAccepted = (bool) old('data_privacy_consent'))

                <div
                    id="privacyConsentOverlay"
                    class="registration-consent-overlay {{ $privacyConsentAccepted ? 'hidden' : '' }}"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="privacyConsentTitle"
                >
                    <div class="registration-consent-dialog">
                        <div class="registration-consent-dialog__header">
                            <p class="registration-consent-dialog__eyebrow">Before You Register</p>
                            <h2 id="privacyConsentTitle">Review the Data Privacy Consent first.</h2>
                        </div>
                        <div class="registration-consent-dialog__body">
                            <p class="registration-consent-dialog__lead">
                                Please read this notice before continuing with alumni verification and registration.
                            </p>
                            <div class="registration-consent-dialog__copy">
                                <p>
                                    I hereby consent and authorize the CMU Office of the Alumni Relations and Linkages (OARL) to collect, use, share, and disclose my personal data contained in this form and in other supporting documents for the purpose of alumni tracing and employability. I understand that, in compliance with the Data Privacy Act of 2012 (R.A. No. 10173), the Office of the Alumni Relations and Linkages (OARL) will strive to keep my information private and confidential and will retain my information solely for the fulfillment of the aforementioned purposes.
                                </p>
                            </div>
                            <label class="registration-consent-dialog__check" for="data_privacy_consent">
                                <input id="data_privacy_consent" type="checkbox" name="data_privacy_consent" value="1" {{ $privacyConsentAccepted ? 'checked' : '' }}>
                                <span>I agree to the Privacy Policy and confirm that I have read and agree to the Terms.</span>
                            </label>
                        </div>
                        <div class="registration-consent-dialog__actions">
                            <a href="{{ route('login') }}" class="registration-pill">Back to Login</a>
                            <button type="button" id="privacyConsentAgreeBtn" class="registration-pill--solid">
                                Agree and Continue
                            </button>
                        </div>
                    </div>
                </div>

                {{-- STEP 1: Verification --}}
                <fieldset class="form-step active space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Verification</legend>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="firstname" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" id="firstname" name="firstname" required class="mt-1">
                        </div>
                        <div>
                            <label for="middlename" class="block text-sm font-medium text-gray-700">Middle Name (Optional)</label>
                            <input type="text" id="middlename" name="middlename" maxlength="100" placeholder="e.g. Bautista" autocomplete="additional-name" class="mt-1">
                        </div>
                        <div>
                            <label for="lastname" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" id="lastname" name="lastname" required class="mt-1">
                        </div>
                        <div>
                            <label for="birthdate" class="block text-sm font-medium text-gray-700">Birthdate</label>
                            <div class="relative mt-1">
                                <input type="text" id="birthdate" required maxlength="10" inputmode="numeric" autocomplete="bday" placeholder="MM/DD/YYYY" data-max-date="{{ now()->toDateString() }}" aria-label="Birthdate in month, day, year format" class="mt-0 pr-12">
                                <input type="hidden" id="birthdateValue" name="birthdate">
                                <input type="date" id="birthdatePicker" max="{{ now()->toDateString() }}" tabindex="-1" aria-hidden="true" class="sr-only">
                                <button type="button" id="birthdatePickerButton" aria-label="Choose birthdate from calendar" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-700">
                                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3.5" y="5" width="17" height="15.5" rx="2" />
                                        <path stroke-linecap="round" d="M7.5 3.5v3M16.5 3.5v3M3.5 9h17" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                            <input type="text" id="suffix" name="suffix" class="mt-1">
                        </div>
                        <div>
                            <label for="studentIdnum" class="block text-sm font-medium text-gray-700">Student ID Number (Optional)</label>
                            <input type="text" id="studentIdnum" name="studentIdnum" maxlength="100" autocomplete="off" placeholder="Enter if available" class="mt-1">
                        </div>
                    </div>

                    <div>
                        <label for="collegeGraduated" class="block text-sm font-medium text-gray-700">College Graduated</label>
                        <select id="collegeGraduated" name="collegeGraduated" required class="mt-1"></select>
                    </div>

                    <div>
                        <label for="yearGraduated" class="block text-sm font-medium text-gray-700">Year Graduated</label>
                        <select id="yearGraduated" name="yearGraduated" required class="mt-1"></select>
                    </div>

                    <p class="registration-inline-note">
                        Your name, birthdate, college, graduation year, and optional student ID number will be checked against the CMU alumni master record before you can continue. Live detection will start once the required verification details are complete.
                    </p>
                    <p id="verificationStatusMessage" class="text-sm text-gray-500" aria-live="polite"></p>
                    <div id="privacyConsentStatus" class="registration-consent-status {{ $privacyConsentAccepted ? '' : 'hidden' }}">
                        <div class="registration-consent-status__copy">
                            <strong>Data Privacy Consent acknowledged</strong>
                            <span>You can continue with verification and the rest of the registration form.</span>
                        </div>
                        <button type="button" id="reopenPrivacyConsentBtn" class="registration-consent-status__action">Review</button>
                    </div>

                    <div class="flex justify-end pt-6">
                        <button id="verificationNextBtn" type="button" class="next-btn" disabled>Next</button>
                    </div>
                </fieldset>

                {{-- STEP 2: Set Account --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Set Account</legend>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" id="email" name="email" required class="mt-1">
                        <p id="emailCheckMessage" class="text-sm mt-1 text-gray-500"></p>
                        <div id="emailVerificationMiniTrigger" class="registration-email-verify__mini-trigger hidden" aria-hidden="true">
                            <span>A verification code was sent to your email.</span>
                            <button type="button" id="emailVerificationOpenBtn">Enter code</button>
                        </div>
                    </div>

                    <div id="accountPasswordLockedNotice" class="registration-password-gate">
                        <i class="bx bx-lock-alt" aria-hidden="true"></i>
                        <span>Verify your email address to continue to password setup.</span>
                    </div>

                    <div id="accountPasswordFields" class="hidden space-y-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="relative mt-1">
                                <input type="password" id="password" name="password" data-password-strength-target="registrationPasswordStrength" minlength="8" autocomplete="new-password" required class="pr-20">
                                <button
                                    type="button"
                                    class="registration-password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-sm text-green-700 hover:text-green-900"
                                    data-target="password"
                                    aria-label="Show password"
                                >
                                    Show
                                </button>
                            </div>
                            <div id="registrationPasswordStrength" class="strength-meter mt-3" aria-live="polite">
                                <div class="strength-meter__track">
                                    <div class="strength-meter__fill" data-password-strength-fill></div>
                                </div>
                                <div class="strength-meter__meta">
                                    <span class="strength-meter__label" data-password-strength-label>Empty</span>
                                    <span class="strength-meter__hint" data-password-strength-hint>Use at least 8 characters with a mix of letters, numbers, and symbols.</span>
                                </div>
                            </div>
                            <p id="passwordHint" class="mt-1 text-xs text-gray-500 hidden">Must be at least 8 characters.</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <div class="relative mt-1">
                                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required class="pr-20">
                                <button
                                    type="button"
                                    class="registration-password-toggle absolute right-3 top-1/2 -translate-y-1/2 text-sm text-green-700 hover:text-green-900"
                                    data-target="password_confirmation"
                                    aria-label="Show confirm password"
                                >
                                    Show
                                </button>
                            </div>
                        </div>

                    </div>

                    <button
                        type="button"
                        id="emailVerificationBackdrop"
                        class="registration-email-verify__backdrop hidden"
                        aria-hidden="true"
                        tabindex="-1"
                        aria-label="Close email verification"
                    ></button>
                    <div
                        id="emailVerificationPanel"
                        class="registration-email-verify hidden"
                        role="region"
                        aria-labelledby="emailVerificationTitle"
                        aria-describedby="emailVerificationCopy"
                        aria-hidden="true"
                    >
                        <div class="registration-email-verify__top">
                            <div class="registration-email-verify__identity">
                                <span class="registration-email-verify__icon" aria-hidden="true">
                                    <i class="bx bx-envelope"></i>
                                </span>
                                <span class="registration-email-verify__heading">
                                    <span id="emailVerificationTitle" class="registration-email-verify__title" tabindex="-1">Verify your email</span>
                                    <span id="emailVerificationCopy" class="registration-email-verify__copy">We will send a 6-digit code to confirm this address.</span>
                                </span>
                            </div>
                            <div class="registration-email-verify__actions">
                                <button
                                    type="button"
                                    id="sendEmailVerificationBtn"
                                    class="registration-email-verify__button registration-email-verify__button--primary"
                                >
                                    <i class="bx bx-send" aria-hidden="true"></i>
                                    <span data-email-verification-label>Send code</span>
                                </button>
                                <button
                                    type="button"
                                    id="emailVerificationCloseBtn"
                                    class="registration-email-verify__close hidden"
                                    aria-label="Close email verification"
                                >
                                    <i class="bx bx-x" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div id="emailVerificationCodeGroup" class="registration-email-verify__code hidden">
                            <label id="emailVerificationCodeLabel" for="emailVerificationCode">Verification code</label>
                            <div class="registration-email-verify__code-row">
                                <div id="emailVerificationOtp" class="registration-email-verify__otp" role="group" aria-label="Six-digit verification code">
                                    <span class="registration-email-verify__otp-digit" data-email-verification-digit aria-hidden="true"></span>
                                    <span class="registration-email-verify__otp-digit" data-email-verification-digit aria-hidden="true"></span>
                                    <span class="registration-email-verify__otp-digit" data-email-verification-digit aria-hidden="true"></span>
                                    <span class="registration-email-verify__otp-digit" data-email-verification-digit aria-hidden="true"></span>
                                    <span class="registration-email-verify__otp-digit" data-email-verification-digit aria-hidden="true"></span>
                                    <span class="registration-email-verify__otp-digit" data-email-verification-digit aria-hidden="true"></span>
                                    <input
                                        type="text"
                                        id="emailVerificationCode"
                                        class="registration-email-verify__otp-input"
                                        inputmode="numeric"
                                        maxlength="6"
                                        autocomplete="one-time-code"
                                        pattern="[0-9]{6}"
                                        aria-label="6-digit verification code"
                                        aria-describedby="emailVerificationCodeLabel emailVerificationMessage"
                                        spellcheck="false"
                                    >
                                </div>
                                <button
                                    type="button"
                                    id="resendEmailVerificationBtn"
                                    disabled
                                    class="registration-email-verify__button registration-email-verify__resend-button hidden"
                                >
                                    <i class="bx bx-refresh" aria-hidden="true"></i>
                                    <span data-email-verification-label>Resend code</span>
                                </button>
                                <button
                                    type="button"
                                    id="verifyEmailCodeBtn"
                                    class="registration-email-verify__button registration-email-verify__button--primary"
                                >
                                    <i class="bx bx-check-shield" aria-hidden="true"></i>
                                    <span data-email-verification-label>Confirm</span>
                                </button>
                            </div>
                        </div>
                        <p id="emailVerificationMessage" class="registration-email-verify__message" aria-live="polite"></p>
                        <div id="emailVerificationFooter" class="registration-email-verify__footer hidden">
                            <p id="emailVerificationTiming" class="registration-email-verify__timing hidden" aria-live="polite"></p>
                        </div>
                        <div class="registration-email-verify__support" aria-label="Contact support">
                            <a class="registration-email-verify__support-title registration-email-verify__support-link" href="mailto:cmuaai@cmu.edu.ph?subject=Alumni%20Portal%20Registration%20Help" aria-label="Email OARL at cmuaai@cmu.edu.ph">Need help? Contact OARL.</a>
                        </div>
                    </div>

                    <input type="hidden" name="newsletter_opt_in" value="0">
                    <label for="newsletter_opt_in" class="registration-newsletter-option">
                        <span class="registration-newsletter-option__control">
                            <input type="checkbox" id="newsletter_opt_in" name="newsletter_opt_in" value="1" checked>
                        </span>
                        <span class="registration-newsletter-option__body">
                            <span class="registration-newsletter-option__header">
                                <span class="registration-newsletter-option__title">Email updates</span>
                            </span>
                            <span class="registration-newsletter-option__copy">
                                Receive official CMU OARL announcements, alumni events, and career opportunities by email. You can update this preference in Profile settings.
                            </span>
                        </span>
                    </label>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button id="accountNextBtn" type="button" class="next-btn" disabled>Next</button>
                    </div>
                </fieldset>

                {{-- STEP 3: Personal Information --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Personal Information</legend>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="sex" class="block text-sm font-medium text-gray-700">Sex</label>
                            <select id="sex" name="sex" required class="mt-1">
                                <option value="">Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div>
                            <label for="civilstatus" class="block text-sm font-medium text-gray-700">Civil Status</label>
                            <select id="civilstatus" name="civilstatus" required class="mt-1">
                                <option value="">Select Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 4: Address --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Address</legend>

                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                        <select id="country" name="country" required class="mt-1"></select>
                        <div id="countryOtherContainer" class="hidden mt-3">
                            <label for="countryOther" class="block text-sm font-medium text-gray-700">Specify Country</label>
                            <input type="text" id="countryOther" name="countryOther" class="mt-1" placeholder="Enter country">
                        </div>
                    </div>

                    <div>
                        <label for="regionOfOrigin" class="block text-sm font-medium text-gray-700">Region of Origin</label>
                        <select id="regionOfOrigin" name="regionOfOrigin" required class="mt-1"></select>
                    </div>

                    <div>
                        <label for="municipalityCity" class="block text-sm font-medium text-gray-700">Municipality/City</label>
                        <input type="text" id="municipalityCity" name="municipalityCity" required class="mt-1" list="municipalityCityOptions" autocomplete="off" placeholder="Enter municipality/city">
                        <datalist id="municipalityCityOptions"></datalist>
                        <p id="municipalityCityHint" class="mt-2 hidden text-sm text-gray-500" aria-live="polite"></p>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 5: High School --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">High School Program</legend>
                    <select id="highschool" name="highschool" required class="hidden">
                        <option value="">Select Program</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <label for="yearGraduatedHS">Year Graduated:</label>
                    <select id="yearGraduatedHS" name="yearGraduatedHS" required>
                        <option value="">Select Year</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 6: Undergraduate --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Undergraduate Degree Data</legend>

                    <div class="space-y-4">
                        <p class="registration-question">Do you have an undergraduate degree from CMU?</p>

                        <div class="registration-radio-group">
                            <label class="registration-radio-option" for="hasUndergraduateDegreeYes">
                                <input type="radio" id="hasUndergraduateDegreeYes" name="hasUndergraduateDegree" value="Yes" required>
                                <span>Yes</span>
                            </label>
                            <label class="registration-radio-option" for="hasUndergraduateDegreeNo">
                                <input type="radio" id="hasUndergraduateDegreeNo" name="hasUndergraduateDegree" value="No" required>
                                <span>No</span>
                            </label>
                        </div>

                        <p class="registration-inline-note">
                            Select <strong>No</strong> if you are a CMU alumnus/alumna through a non-undergraduate program such as graduate, certificate, or other alumni-recognized studies.
                        </p>
                        <p id="undergraduateVerificationMessage" class="text-sm text-gray-500 hidden" aria-live="polite"></p>
                    </div>

                    <div id="undergraduateFields" class="hidden space-y-4">

                    <label for="department">College:</label>
                    <select id="department" name="department">
                        <option value="">Select College</option>
                    </select>
                    <p id="undergraduateCollegeMatchMessage" class="mt-2 hidden text-sm text-gray-500" aria-live="polite"></p>
                    <div id="departmentOtherContainer" class="hidden mt-2">
                        <label for="departmentOther">If Other, please specify:</label>
                        <input type="text" id="departmentOther" name="departmentOther" placeholder="Enter other college">
                    </div>

                    <label for="degree">Bachelor’s Degree Graduated:</label>
                    <select id="degree" name="degree">
                        <option value="">Select Degree</option>
                    </select>
                    <div id="degreeOtherContainer" class="hidden mt-2">
                        <label for="degreeOther">If Other, please specify:</label>
                        <input type="text" id="degreeOther" name="degreeOther" placeholder="Enter other degree">
                    </div>

                    <label for="major">Degree Major:</label>
                    <select id="major" name="major">
                        <option value="">Select Major</option>
                        <option value="Other">Other</option>
                    </select>

                    <div id="otherMajorContainer" class="hidden mt-2">
                        <label for="otherMajor">If Other, Please Specify:</label>
                        <input type="text" id="otherMajor" name="otherMajor" placeholder="Specify Major">
                    </div>

                    <label for="ugyearGraduated">Year Graduated:</label>
                    <select id="ugyearGraduated" name="ugyearGraduated">
                        <option value="">Select Year</option>
                    </select>

                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 7: Master --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Master’s Degree Data</legend>

                    <div class="space-y-4">
                        <p class="registration-question">Do you have a master's degree?</p>

                        <div class="registration-radio-group">
                            <label class="registration-radio-option" for="hasMasteralYes">
                                <input type="radio" id="hasMasteralYes" name="hasMasteral" value="Yes" required>
                                <span>Yes</span>
                            </label>
                            <label class="registration-radio-option" for="hasMasteralNo">
                                <input type="radio" id="hasMasteralNo" name="hasMasteral" value="No" required>
                                <span>No</span>
                            </label>
                        </div>
                    </div>

                    <div id="masterFields" class="hidden space-y-4">
                    <label for="master">Master of:</label>
                    <select id="master" name="master">
                        <option value="">Select Master's Program</option>
                        <option value="Other">Other</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div id="masterOtherContainer" class="hidden mt-3">
                        <label for="otherMaster">Specify Master's Program:</label>
                        <input type="text" id="otherMaster" name="otherMaster" placeholder="Enter other master's program">
                    </div>

                    <label for="mastmajor">Major in:</label>
                    <select id="mastmajor" name="mastmajor">
                        <option value="">Select Major</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div id="otherMastMajorContainer" class="hidden mt-2">
                        <label for="othermastMajor">Others (specify):</label>
                        <input type="text" id="othermastMajor" name="othermastMajor" placeholder="Enter other major">
                    </div>

                    <label for="mastyearGraduated">Year Graduated:</label>
                    <select id="mastyearGraduated" name="mastyearGraduated">
                        <option value="">Select Year</option>
                        <option value="N/A">N/A</option>
                    </select>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 8: Doctorate --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Doctorate Degree Data</legend>

                    <div class="space-y-4">
                        <p class="registration-question">Do you have a doctorate degree?</p>

                        <div class="registration-radio-group">
                            <label class="registration-radio-option" for="hasDoctorateYes">
                                <input type="radio" id="hasDoctorateYes" name="hasDoctorate" value="Yes" required>
                                <span>Yes</span>
                            </label>
                            <label class="registration-radio-option" for="hasDoctorateNo">
                                <input type="radio" id="hasDoctorateNo" name="hasDoctorate" value="No" required>
                                <span>No</span>
                            </label>
                        </div>
                    </div>

                    <div id="doctorateFields" class="hidden space-y-4">
                    <label for="doctorate">Doctor of:</label>
                    <select id="doctorate" name="doctorate">
                        <option value="">Select Doctorate Program</option>
                        <option value="Other">Other</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div id="doctorateOtherContainer" class="hidden mt-3">
                        <label for="otherDoctorate">Specify Doctorate Program:</label>
                        <input type="text" id="otherDoctorate" name="otherDoctorate" placeholder="Enter other doctorate program">
                    </div>

                    <label for="doctmajor">Major in:</label>
                    <select id="doctmajor" name="doctmajor">
                        <option value="">Select Major</option>
                        <option value="N/A">N/A</option>
                    </select>

                    <div id="otherDoctMajorContainer" class="hidden mt-2">
                        <label for="otherdoctMajor">Others (specify):</label>
                        <input type="text" id="otherdoctMajor" name="otherdoctMajor" placeholder="Enter other major">
                    </div>

                    <label for="doctyearGraduated">Year Graduated:</label>
                    <select id="doctyearGraduated" name="doctyearGraduated">
                        <option value="">Select Year</option>
                        <option value="N/A">N/A</option>
                    </select>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 9: Non-Degree --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Non-Degree Data</legend>

                    <div class="space-y-4">
                        <p class="registration-question">Have you completed a CMU non-degree program?</p>

                        <div class="registration-radio-group">
                            <label class="registration-radio-option" for="hasNonDegreeYes">
                                <input type="radio" id="hasNonDegreeYes" name="hasNonDegree" value="Yes" required>
                                <span>Yes</span>
                            </label>
                            <label class="registration-radio-option" for="hasNonDegreeNo">
                                <input type="radio" id="hasNonDegreeNo" name="hasNonDegree" value="No" required>
                                <span>No</span>
                            </label>
                        </div>
                        <p id="nondegreeVerificationMessage" class="text-sm text-gray-500 hidden" aria-live="polite"></p>
                    </div>

                    <div id="nondegreeFields" class="hidden space-y-4">
                        <label for="nondegree">Non-Degree Program Graduated:</label>
                        <select id="nondegree" name="nondegree">
                            <option value="">Select Program</option>
                            <option value="Other">Other</option>
                            <option value="N/A">N/A</option>
                        </select>

                        <div id="nondegreeOtherContainer" class="hidden mt-3">
                            <label for="nondegreeOther">If Other, specify:</label>
                            <input type="text" id="nondegreeOther" name="nondegreeOther" placeholder="Specify other program">
                        </div>

                        <label for="nondegreeyearGraduated">Year Graduated:</label>
                        <select id="nondegreeyearGraduated" name="nondegreeyearGraduated">
                            <option value="">Select Year</option>
                            <option value="N/A">N/A</option>
                        </select>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 10: Educational Background --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Educational Background</legend>

                    <div class="space-y-4">
                        <p class="registration-question">Did you take a professional exam or attend training after graduation?</p>

                        <div class="registration-radio-group">
                            <label class="registration-radio-option" for="hasEducationalBackgroundYes">
                                <input type="radio" id="hasEducationalBackgroundYes" name="hasEducationalBackground" value="Yes" required>
                                <span>Yes</span>
                            </label>
                            <label class="registration-radio-option" for="hasEducationalBackgroundNo">
                                <input type="radio" id="hasEducationalBackgroundNo" name="hasEducationalBackground" value="No" required>
                                <span>No</span>
                            </label>
                        </div>
                    </div>

                    <div id="educationBackgroundFields" class="hidden space-y-4">
                        <label>Professional Examination Passed:</label>
                        <div id="proExamContainer"></div>

                        <div id="proExamOtherContainer" class="hidden mt-3">
                            <label for="proExamOther">If Other, specify:</label>
                            <input type="text" id="proExamOther" name="proExamOther" placeholder="Enter other exam">
                        </div>

                        <label for="proExamyear">Year Taken:</label>
                        <select id="proExamyear" name="proExamyear">
                            <option value="">Select Year</option>
                            <option value="N/A">N/A</option>
                        </select>

                        <label for="proExamreason">Reason for Taking the Course:</label>
                        <select id="proExamreason" name="proExamreason"></select>

                        <label for="trainingcount">Number of Trainings Attended After Graduation:</label>
                        <select id="trainingcount" name="trainingcount"></select>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 11: Scholarship --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Scholarship Data</legend>
                    <div id="scholarshipContainer"></div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 12: Employment Data --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Employment Data</legend>

                    <div class="registration-block registration-block--flat space-y-4">
                        <h3>Curriculum and First Job</h3>
                        <div class="space-y-3">
                            <label>Was your college curriculum relevant to your first job? *</label>
                            <div id="curriculumRelevantContainer" class="registration-radio-group">
                                <label class="registration-radio-option" for="curriculumYes">
                                    <input type="radio" id="curriculumYes" name="curriculumRelevant" value="Yes" required>
                                    <span>Yes</span>
                                </label>
                                <label class="registration-radio-option" for="curriculumNo">
                                    <input type="radio" id="curriculumNo" name="curriculumRelevant" value="No" required>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>

                        <div id="curriculumYesContainer" class="hidden registration-mini-panel space-y-4">
                            <div>
                                <label>Useful competencies in your first job *</label>
                                <p class="registration-compact-note">You may choose more than one answer.</p>
                            </div>
                            <div id="firstJobCompetenciesContainer" class="registration-check-grid registration-check-grid--2"></div>
                            <div id="firstJobCompetenciesOtherContainer" class="hidden">
                                <label for="firstJobCompetenciesOther">Other competency *</label>
                                <input type="text" id="firstJobCompetenciesOther" name="firstJobCompetenciesOther" placeholder="Enter other competency">
                            </div>
                        </div>

                        <div id="curriculumNoContainer" class="hidden registration-mini-panel registration-field-grid">
                            <div>
                                <label for="curriculumNotRelevantReason">Reason *</label>
                                <select id="curriculumNotRelevantReason" name="curriculumNotRelevantReason">
                                    <option value="">Select reason</option>
                                </select>
                            </div>
                            <div id="curriculumNotRelevantReasonOtherContainer" class="hidden">
                                <label for="curriculumNotRelevantReasonOther">Other reason *</label>
                                <input type="text" id="curriculumNotRelevantReasonOther" name="curriculumNotRelevantReasonOther" placeholder="Enter other reason">
                            </div>
                        </div>
                    </div>

                    <div class="registration-block registration-block--flat space-y-4">
                        <h3>First Job Details</h3>
                        <div class="space-y-3">
                            <label>Were you employed after you graduated? *</label>
                            <div id="employedAfterGradContainer" class="registration-radio-group">
                                <label class="registration-radio-option" for="employedYes">
                                    <input type="radio" id="employedYes" name="employedAfterGrad" value="Yes" required>
                                    <span>Yes</span>
                                </label>
                                <label class="registration-radio-option" for="employedNo">
                                    <input type="radio" id="employedNo" name="employedAfterGrad" value="No" required>
                                    <span>No</span>
                                </label>
                            </div>
                        </div>

                        <div id="firstJobFields" class="hidden registration-mini-panel registration-field-grid registration-field-grid--2">
                            <div class="registration-uniform-field">
                                <label for="firstJobTime">Time to land first job *</label>
                                <select id="firstJobTime" name="firstJobTime">
                                    <option value="">Select time</option>
                                </select>
                            </div>
                            <div id="firstJobTimeOtherContainer" class="hidden">
                                <label for="firstJobTimeOther">Other time *</label>
                                <input type="text" id="firstJobTimeOther" name="firstJobTimeOther" placeholder="Enter other time">
                            </div>
                            <div class="registration-uniform-field">
                                <label for="firstJobRelated">Was your first job related to your course? *</label>
                                <select id="firstJobRelated" name="firstJobRelated">
                                    <option value="">Select answer</option>
                                </select>
                            </div>
                            <div class="md:col-span-2 registration-reveal-panel registration-reveal-panel--flat space-y-3">
                                <p class="registration-reveal-panel__title">Add more first job details?</p>
                                <div class="registration-radio-group">
                                    <label class="registration-radio-option" for="shareMoreFirstJobDetailsYes">
                                        <input type="radio" id="shareMoreFirstJobDetailsYes" name="shareMoreFirstJobDetails" value="Yes">
                                        <span>Yes</span>
                                    </label>
                                    <label class="registration-radio-option" for="shareMoreFirstJobDetailsNo">
                                        <input type="radio" id="shareMoreFirstJobDetailsNo" name="shareMoreFirstJobDetails" value="No">
                                        <span>No</span>
                                    </label>
                                </div>
                                <button type="button" id="firstJobExtendedFieldsReview" class="registration-mini-view__review hidden">
                                    <span class="registration-mini-view__review-copy">
                                        <span class="registration-mini-view__review-label">Review or edit saved details</span>
                                        <span class="registration-mini-view__review-note">Open the additional first job information you already filled out.</span>
                                    </span>
                                    <span class="registration-mini-view__review-action">Open</span>
                                </button>
                            </div>
                            <div id="firstJobExtendedFields" class="hidden md:col-span-2 registration-mini-view" role="dialog" aria-modal="true" aria-labelledby="firstJobExtendedFieldsTitle">
                                <div class="registration-mini-view__header">
                                    <p id="firstJobExtendedFieldsTitle" class="registration-mini-view__title">More first job details</p>
                                    <button type="button" id="firstJobExtendedFieldsClose" class="registration-mini-view__close" aria-label="Close more first job details">&times;</button>
                                </div>
                                <div class="registration-field-grid registration-field-grid--2">
                                    <div>
                                        <label for="firstJobHow">How did you find your first job?</label>
                                        <select id="firstJobHow" name="firstJobHow">
                                            <option value="">Select method</option>
                                        </select>
                                    </div>
                                    <div id="firstJobHowOtherContainer" class="hidden">
                                        <label for="firstJobHowOther">Other method *</label>
                                        <input type="text" id="firstJobHowOther" name="firstJobHowOther" placeholder="Enter other method">
                                    </div>
                                    <div>
                                        <label for="firstJobEarnings">First job monthly earnings</label>
                                        <select id="firstJobEarnings" name="firstJobEarnings">
                                            <option value="">Select earnings</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="firstJobDuration">How long did you stay in your first job?</label>
                                        <select id="firstJobDuration" name="firstJobDuration">
                                            <option value="">Select duration</option>
                                        </select>
                                    </div>
                                    <div id="firstJobDurationOtherContainer" class="hidden">
                                        <label for="firstJobDurationOther">Other duration *</label>
                                        <input type="text" id="firstJobDurationOther" name="firstJobDurationOther" placeholder="Enter other duration">
                                    </div>
                                </div>
                                <div class="registration-mini-view__footer">
                                    <button type="button" id="firstJobExtendedFieldsDone" class="registration-mini-view__done">Save and close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 13: Employment Status --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Employment Status</legend>

                    <div class="registration-block registration-block--flat space-y-4">
                        <h3>Current Employment</h3>
                        <div class="space-y-3">
                            <label>Are you presently employed? *</label>
                            <div id="presentlyEmployedContainer" class="registration-radio-group">
                                <label class="registration-radio-option" for="presentlyEmployedYes">
                                    <input type="radio" id="presentlyEmployedYes" name="presentlyEmployed" value="Yes" required>
                                    <span>Yes</span>
                                </label>
                                <label class="registration-radio-option" for="presentlyEmployedNo">
                                    <input type="radio" id="presentlyEmployedNo" name="presentlyEmployed" value="No" required>
                                    <span>No</span>
                                </label>
                                <label class="registration-radio-option" for="presentlyEmployedNever">
                                    <input type="radio" id="presentlyEmployedNever" name="presentlyEmployed" value="Never Employed" required>
                                    <span>Never Employed</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="employmentDetails" class="hidden registration-mini-panel space-y-6">
                        <div class="registration-block registration-block--flat space-y-4">
                            <h3>Current Work Snapshot</h3>
                            <div class="registration-field-grid registration-field-grid--2">
                                <div>
                                    <label for="presentEmploymentStatus">Present employment status *</label>
                                    <select id="presentEmploymentStatus" name="presentEmploymentStatus">
                                        <option value="">Select status</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="presentOccupation">Present occupation *</label>
                                    <input type="text" id="presentOccupation" name="presentOccupation" placeholder="e.g. Grade School Teacher">
                                </div>
                                <div>
                                    <label for="presentCompany">Company or organization with address *</label>
                                    <input type="text" id="presentCompany" name="presentCompany" placeholder="e.g. Central Mindanao University, Musuan Bukidnon">
                                </div>
                                <div>
                                    <label for="businessLine">Major line of business *</label>
                                    <select id="businessLine" name="businessLine">
                                        <option value="">Select business line</option>
                                    </select>
                                </div>
                                <div id="businessLineOtherContainer" class="hidden">
                                    <label for="businessLineOther">Other business line *</label>
                                    <input type="text" id="businessLineOther" name="businessLineOther" placeholder="Enter other business line">
                                </div>
                                <div>
                                    <label for="presentJobEarnings">Present job monthly earnings *</label>
                                    <select id="presentJobEarnings" name="presentJobEarnings">
                                        <option value="">Select earnings</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="employmentProof">Employment proof PDF</label>
                                    <input type="file" id="employmentProof" name="employmentProof" accept="application/pdf,.pdf">
                                    <p class="registration-compact-note">Upload 1 PDF file only. Maximum 10 MB. If you skip this now, upload it within 7 days.</p>
                                </div>
                            </div>
                        </div>

                        <div class="registration-block registration-block--flat space-y-4">
                            <h3>Additional Work Details</h3>
                            <div class="registration-reveal-panel registration-reveal-panel--flat space-y-3">
                                <div class="registration-radio-group">
                                    <label class="registration-radio-option" for="shareMoreEmploymentDetailsYes">
                                        <input type="radio" id="shareMoreEmploymentDetailsYes" name="shareMoreEmploymentDetails" value="Yes">
                                        <span>Yes</span>
                                    </label>
                                    <label class="registration-radio-option" for="shareMoreEmploymentDetailsNo">
                                        <input type="radio" id="shareMoreEmploymentDetailsNo" name="shareMoreEmploymentDetails" value="No">
                                        <span>No</span>
                                    </label>
                                </div>
                                <button type="button" id="employmentExtendedDetailsReview" class="registration-mini-view__review hidden">
                                    <span class="registration-mini-view__review-copy">
                                        <span class="registration-mini-view__review-label">Review or edit saved details</span>
                                        <span class="registration-mini-view__review-note">Open the additional work information you already filled out.</span>
                                    </span>
                                    <span class="registration-mini-view__review-action">Open</span>
                                </button>
                            </div>

                            <div id="employmentExtendedDetails" class="hidden registration-mini-view space-y-3" role="dialog" aria-modal="true" aria-labelledby="employmentExtendedDetailsTitle">
                                <div class="registration-mini-view__header">
                                    <p id="employmentExtendedDetailsTitle" class="registration-mini-view__title">More work details</p>
                                    <button type="button" id="employmentExtendedDetailsClose" class="registration-mini-view__close" aria-label="Close more work details">&times;</button>
                                </div>
                                <div class="registration-field-grid registration-field-grid--2">
                                    <div>
                                        <label for="directSupervisor">Employer or direct supervisor</label>
                                        <input type="text" id="directSupervisor" name="directSupervisor" placeholder="Enter name">
                                    </div>
                                    <div>
                                        <label for="employerEmail">Employer email address</label>
                                        <input type="email" id="employerEmail" name="employerEmail" placeholder="Enter email">
                                    </div>
                                    <div>
                                        <label for="jobLevel">Job level position *</label>
                                        <select id="jobLevel" name="jobLevel">
                                            <option value="">Select level</option>
                                        </select>
                                    </div>
                                    <div id="jobLevelOtherContainer" class="hidden">
                                        <label for="jobLevelOther">Other job level *</label>
                                        <input type="text" id="jobLevelOther" name="jobLevelOther" placeholder="Enter other job level">
                                    </div>
                                    <div>
                                        <label>Is this your first job after college? *</label>
                                        <div class="registration-radio-group">
                                            <label class="registration-radio-option" for="firstJobIsCurrentYes">
                                                <input type="radio" id="firstJobIsCurrentYes" name="firstJobIsCurrent" value="Yes">
                                                <span>Yes</span>
                                            </label>
                                            <label class="registration-radio-option" for="firstJobIsCurrentNo">
                                                <input type="radio" id="firstJobIsCurrentNo" name="firstJobIsCurrent" value="No">
                                                <span>No</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="registration-reveal-panel registration-reveal-panel--flat space-y-3">
                                    <div>
                                        <label>Reason for accepting the present job *</label>
                                        <p class="registration-compact-note">You may choose more than one answer.</p>
                                    </div>
                                    <div id="presentJobReasonsContainer" class="registration-check-grid registration-check-grid--2"></div>
                                    <div id="presentJobReasonsOtherContainer" class="hidden">
                                        <label for="presentJobReasonsOther">Other reason *</label>
                                        <input type="text" id="presentJobReasonsOther" name="presentJobReasonsOther" placeholder="Enter other reason">
                                    </div>
                                </div>

                                <div class="registration-reveal-panel registration-reveal-panel--flat space-y-3">
                                    <div>
                                        <label>Any awards received as an employee? *</label>
                                        <div class="registration-radio-group">
                                            <label class="registration-radio-option" for="hasEmployeeAwardsYes">
                                                <input type="radio" id="hasEmployeeAwardsYes" name="hasEmployeeAwards" value="Yes">
                                                <span>Yes</span>
                                            </label>
                                            <label class="registration-radio-option" for="hasEmployeeAwardsNo">
                                                <input type="radio" id="hasEmployeeAwardsNo" name="hasEmployeeAwards" value="No">
                                                <span>No</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div id="employeeAwardsContainer" class="hidden">
                                        <label for="employeeAwards">Awards received *</label>
                                        <textarea id="employeeAwards" name="employeeAwards" rows="3" placeholder="List your awards"></textarea>
                                    </div>
                                </div>
                                <div class="registration-mini-view__footer">
                                    <button type="button" id="employmentExtendedDetailsDone" class="registration-mini-view__done">Save and close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button type="button" class="next-btn">Next</button>
                    </div>
                </fieldset>

                {{-- STEP 14: Comments, Suggestions, and Submit --}}
                <fieldset class="form-step space-y-6">
                    <legend class="text-lg font-semibold text-[#003300]">Comments and Suggestions</legend>

                    <div class="registration-block registration-block--flat space-y-4">
                        <h3>Teaching Feedback</h3>
                        <div class="registration-rating-card registration-rating-card--flat">
                            <p>How would you rate the teaching quality of your program? *</p>
                            <div class="registration-rating-options registration-rating-options--5">
                                @foreach($quickAgreementOptions as $value => $label)
                                    <label class="registration-rating-option" for="teachingFeedbackSummary_{{ $value }}">
                                        <input
                                            type="radio"
                                            id="teachingFeedbackSummary_{{ $value }}"
                                            name="teachingFeedbackSummary"
                                            value="{{ $value }}"
                                            {{ old('teachingFeedbackSummary') == $value ? 'checked' : '' }}
                                            required
                                        >
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="registration-block registration-block--flat space-y-4">
                        <h3>Suggestions</h3>
                        <div class="space-y-4">
                            <div class="registration-reveal-panel registration-reveal-panel--flat space-y-3">
                                <div id="curriculumSuggestionsContainer" class="space-y-2">
                                    <label for="curriculumSuggestions" class="registration-reveal-panel__title">
                                        Please list down your comments and suggestions to further improve your course curriculum *
                                    </label>
                                    <textarea
                                        id="curriculumSuggestions"
                                        name="curriculumSuggestions"
                                        rows="4"
                                        placeholder="Write your comments or suggestions here"
                                        required
                                    >{{ old('curriculumSuggestions') }}</textarea>
                                </div>
                            </div>

                            <div class="registration-reveal-panel registration-reveal-panel--flat space-y-3">
                                <div id="employabilitySuggestionsContainer" class="space-y-2">
                                    <label for="employabilitySuggestions" class="registration-reveal-panel__title">
                                        Please list down your comments and suggestions to further improve the employability of your courses *
                                    </label>
                                    <textarea
                                        id="employabilitySuggestions"
                                        name="employabilitySuggestions"
                                        rows="4"
                                        placeholder="Write your comments or suggestions here"
                                        required
                                    >{{ old('employabilitySuggestions') }}</textarea>
                                </div>
                            </div>

                            <div class="registration-rating-card registration-rating-card--flat">
                                <p>University facilities *</p>
                                <div class="registration-rating-options registration-rating-options--3">
                                    @foreach(['Very Satisfied', 'Satisfied', 'Not Satisfied'] as $option)
                                        <label class="registration-rating-option" for="facilitiesSatisfaction_{{ \Illuminate\Support\Str::slug($option) }}">
                                            <input
                                                type="radio"
                                                id="facilitiesSatisfaction_{{ \Illuminate\Support\Str::slug($option) }}"
                                                name="facilitiesSatisfaction"
                                                value="{{ $option }}"
                                                {{ old('facilitiesSatisfaction') === $option ? 'checked' : '' }}
                                                required
                                            >
                                            <span>{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="registration-reveal-panel registration-reveal-panel--flat space-y-3">
                                <div id="facilitySuggestionsContainer" class="space-y-2">
                                    <label for="facilitySuggestions" class="registration-reveal-panel__title">
                                        Please list down your comments and suggestions to further improve the facilities of this university *
                                    </label>
                                    <textarea
                                        id="facilitySuggestions"
                                        name="facilitySuggestions"
                                        rows="4"
                                        placeholder="Write your comments or suggestions here"
                                        required
                                    >{{ old('facilitySuggestions') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="registration-block registration-block--flat space-y-4">
                        <h3>Overall Experience</h3>
                        <div class="registration-rating-card registration-rating-card--flat">
                            <p>How would you rate your overall CMU experience? *</p>
                            <div class="registration-rating-options registration-rating-options--5">
                                @foreach($quickAgreementOptions as $value => $label)
                                    <label class="registration-rating-option" for="overallExperienceSummary_{{ $value }}">
                                        <input
                                            type="radio"
                                            id="overallExperienceSummary_{{ $value }}"
                                            name="overallExperienceSummary"
                                            value="{{ $value }}"
                                            {{ old('overallExperienceSummary') == $value ? 'checked' : '' }}
                                            required
                                        >
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="registration-block registration-block--flat space-y-4">
                        <h3>Additional profile details</h3>
                        <div>
                            <label for="skills">Skills or certifications acquired</label>
                            <textarea id="skills" name="skills" rows="3" placeholder="List your skills or certifications" class="mt-1"></textarea>
                        </div>

                        <div>
                            <label for="achievements">Achievements or awards</label>
                            <textarea id="achievements" name="achievements" rows="3" placeholder="List your achievements" class="mt-1"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-between pt-6">
                        <button type="button" class="previous-btn">Previous</button>
                        <button id="submitBtn" type="submit" class="registration-submit-btn">
                            Submit
                        </button>
                    </div>

                    <div id="submitAccountOverviewModal" class="hidden registration-mini-view space-y-4" role="dialog" aria-modal="true" aria-labelledby="submitAccountOverviewTitle" aria-describedby="submitAccountOverviewLead">
                        <div class="registration-mini-view__header">
                            <p id="submitAccountOverviewTitle" class="registration-mini-view__title">Review your account before submitting</p>
                            <button type="button" id="submitAccountOverviewClose" class="registration-mini-view__close" aria-label="Close account overview">&times;</button>
                        </div>

                        <p id="submitAccountOverviewLead" class="registration-submit-overview__lead">
                            Take note of the login details you set in the account section. You will use these credentials after registration.
                        </p>

                        <div class="registration-submit-overview__grid">
                            <div class="registration-submit-overview__item">
                                <span class="registration-submit-overview__label">Account Email</span>
                                <span id="submitAccountOverviewEmail" class="registration-submit-overview__value"></span>
                            </div>

                            <div class="registration-submit-overview__item">
                                <span class="registration-submit-overview__label">OARL Updates</span>
                                <span id="submitAccountOverviewNewsletter" class="registration-submit-overview__value"></span>
                            </div>

                            <div class="registration-submit-overview__item">
                                <span class="registration-submit-overview__label">Password</span>
                                <div class="registration-submit-overview__password">
                                    <span id="submitAccountOverviewPassword" class="registration-submit-overview__value registration-submit-overview__value--secret"></span>
                                    <button type="button" id="submitAccountOverviewPasswordToggle" class="registration-submit-overview__toggle" aria-pressed="false">
                                        Show
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="registration-submit-overview__note">
                            Keep your email and password somewhere safe so you can sign in right away after the registration is complete.
                        </p>

                        <div class="registration-mini-view__footer">
                            <button type="button" id="submitAccountOverviewBack" class="registration-mini-view__secondary">Back</button>
                            <button type="button" id="submitAccountOverviewConfirm" class="registration-mini-view__done">Submit registration</button>
                        </div>
                    </div>
                </fieldset>
            </form>
                </section>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const consentInput = document.getElementById('data_privacy_consent');
            const consentOverlay = document.getElementById('privacyConsentOverlay');
            const consentStatus = document.getElementById('privacyConsentStatus');
            const consentAgreeButton = document.getElementById('privacyConsentAgreeBtn');
            const consentReviewButton = document.getElementById('reopenPrivacyConsentBtn');

            if (!consentInput || !consentOverlay || !consentAgreeButton) {
                return;
            }

            const syncConsentState = function () {
                const accepted = !!consentInput.checked;
                consentAgreeButton.disabled = !accepted;
                consentAgreeButton.setAttribute('aria-disabled', accepted ? 'false' : 'true');

                if (consentStatus) {
                    consentStatus.classList.toggle('hidden', !accepted || !consentOverlay.classList.contains('hidden'));
                }
            };

            const setConsentOverlayOpen = function (isOpen) {
                consentOverlay.classList.toggle('hidden', !isOpen);
                document.body.style.overflow = isOpen ? 'hidden' : '';

                if (consentStatus) {
                    consentStatus.classList.toggle('hidden', isOpen || !consentInput.checked);
                }
            };

            consentInput.addEventListener('change', syncConsentState);

            consentAgreeButton.addEventListener('click', function () {
                if (!consentInput.checked) {
                    return;
                }

                setConsentOverlayOpen(false);
                syncConsentState();
            });

            if (consentReviewButton) {
                consentReviewButton.addEventListener('click', function () {
                    setConsentOverlayOpen(true);
                    syncConsentState();
                });
            }

            syncConsentState();
            setConsentOverlayOpen(!consentInput.checked);
        });
    </script>
</x-layouts.app>
