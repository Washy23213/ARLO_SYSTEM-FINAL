@extends('components.layouts.dashboard')

@section('title', 'Settings')

@section('content')
@php
    $profileUser = auth()->user();
    $sessions = collect($sessions ?? []);
    $otherSessions = $sessions->where('is_current', false);
    $sessionCount = max($sessions->count(), 1);
    $sessionCountLabel = $sessionCount.' '.\Illuminate\Support\Str::plural('device', $sessionCount);
    $deleteVerifiedAt = session('delete_account_verified_at');
    $deleteVerifiedUser = (string) session('delete_account_verified_user_id', '');
    $currentUserId = auth()->check() ? (string) auth()->id() : '';
    $deleteVerified = $deleteVerifiedAt
        && $deleteVerifiedUser === $currentUserId
        && now()->diffInMinutes(\Carbon\Carbon::parse($deleteVerifiedAt)) <= 10;
    $activeSettingsForm = old('settings_form');
    $deleteGuardLabel = $deleteVerified ? 'Ready for final step' : 'Locked until verification';
    $deleteVerificationModalShouldOpen = !$deleteVerified
        && ($activeSettingsForm === 'delete_account_verify' || ($activeSettingsForm === null && $errors->has('delete_account')));
    $deleteConfirmationModalShouldOpen = $deleteVerified
        && ($activeSettingsForm === 'delete_account_confirm' || $errors->has('delete_confirmation') || $errors->has('delete_account'));
    $passwordModalShouldOpen = $activeSettingsForm === 'password'
        && ($errors->has('current_password') || $errors->has('new_password') || $errors->has('new_password_confirmation'));
@endphp

<style>
    .settings-page {
        --settings-line: rgba(11, 59, 31, 0.1);
        --settings-line-strong: rgba(20, 87, 38, 0.18);
        --settings-wash: rgba(248, 250, 247, 0.92);
        --settings-shadow: 0 22px 42px rgba(7, 24, 14, 0.12);
    }

    .settings-page .settings-surface {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.82);
        background:
            radial-gradient(circle at top right, rgba(200, 154, 45, 0.12), transparent 26%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(251, 248, 241, 0.94));
        box-shadow: var(--settings-shadow);
    }

    .settings-page .settings-surface::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            linear-gradient(120deg, rgba(255, 255, 255, 0.56), transparent 38%),
            repeating-linear-gradient(90deg, rgba(11, 59, 31, 0.025) 0 1px, transparent 1px 34px);
        opacity: 0.55;
    }

    .settings-page .settings-surface > * {
        position: relative;
        z-index: 1;
    }

    .settings-page .settings-surface--profile {
        background:
            radial-gradient(circle at top right, rgba(200, 154, 45, 0.14), transparent 24%),
            radial-gradient(circle at bottom left, rgba(20, 87, 38, 0.08), transparent 24%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 247, 0.95));
    }

    .settings-page .settings-surface--security {
        background:
            radial-gradient(circle at top right, rgba(20, 87, 38, 0.11), transparent 28%),
            radial-gradient(circle at bottom left, rgba(200, 154, 45, 0.12), transparent 24%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 247, 0.95));
    }

    .settings-page .alumni-panel-header {
        gap: 1.15rem;
    }

    .settings-page .alumni-panel-title {
        font-size: 1.3rem;
    }

    .settings-page .alumni-panel-copy {
        max-width: 40rem;
        color: #5e7569;
    }

    .settings-page .alumni-pill {
        border: 1px solid rgba(11, 59, 31, 0.08);
        background: rgba(255, 255, 255, 0.82);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.76), 0 10px 24px rgba(7, 24, 14, 0.08);
    }

    .settings-page .settings-top-grid {
        display: grid;
        gap: 1.25rem;
    }

    .settings-page .settings-quick-access,
    .settings-page .settings-overview {
        display: grid;
        gap: 1rem;
        align-content: start;
    }

    .settings-page .settings-tools-panel {
        display: grid;
        gap: 1rem;
    }

    .settings-page .settings-tools-grid {
        display: grid;
        gap: 0.85rem;
    }

    .settings-page .settings-tool-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.85rem;
        width: 100%;
        padding: 0.95rem 1rem;
        border: 1px solid rgba(11, 59, 31, 0.08);
        border-radius: 1.15rem;
        background:
            radial-gradient(circle at top right, rgba(200, 154, 45, 0.08), transparent 28%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 250, 248, 0.98));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.82);
        color: inherit;
        text-align: left;
        text-decoration: none;
        appearance: none;
        cursor: pointer;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .settings-page .settings-tool-card:hover {
        border-color: rgba(20, 87, 38, 0.18);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.82), 0 14px 28px rgba(7, 24, 14, 0.08);
    }

    .settings-page .settings-tool-card:focus-visible {
        outline: 0;
        border-color: rgba(20, 87, 38, 0.22);
        box-shadow: 0 0 0 4px rgba(20, 87, 38, 0.12), 0 14px 28px rgba(7, 24, 14, 0.08);
    }

    .settings-page .settings-guided-highlight {
        position: relative;
        z-index: 2;
        border-color: rgba(16, 185, 129, 0.42) !important;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.14), 0 14px 28px rgba(7, 24, 14, 0.08) !important;
    }

    .settings-page input.settings-guided-highlight,
    .settings-page select.settings-guided-highlight,
    .settings-page textarea.settings-guided-highlight,
    .settings-page button.settings-guided-highlight,
    .settings-page .settings-password-modal__card.settings-guided-highlight {
        background-color: #ffffff;
    }

    .settings-page .settings-tool-card--primary {
        border-color: rgba(20, 87, 38, 0.12);
        background:
            radial-gradient(circle at top right, rgba(20, 87, 38, 0.1), transparent 30%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(242, 248, 244, 0.98));
    }

    .settings-page .settings-tool-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.8rem;
        height: 2.8rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, rgba(20, 87, 38, 0.14), rgba(200, 154, 45, 0.16));
        box-shadow: inset 0 0 0 1px rgba(11, 59, 31, 0.08), 0 8px 18px rgba(7, 24, 14, 0.06);
        color: #00491e;
        font-size: 1.1rem;
    }

    .settings-page .settings-tool-body {
        display: grid;
        gap: 0.18rem;
        min-width: 0;
    }

    .settings-page .settings-tool-title {
        color: #173122;
        font-size: 0.95rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .settings-page .settings-tool-copy {
        color: #60786c;
        font-size: 0.82rem;
        line-height: 1.45;
    }

    .settings-page .settings-tool-arrow {
        color: #02681e;
        font-size: 1.2rem;
    }

    .settings-page .settings-tools-note {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.75rem 0.95rem;
        border: 1px solid rgba(11, 59, 31, 0.08);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.84);
        color: #4f685b;
        font-size: 0.83rem;
        line-height: 1.45;
    }

    .settings-page .settings-overview-card {
        padding: 1rem 1.1rem;
        border: 1px solid rgba(11, 59, 31, 0.08);
        border-radius: 1.25rem;
        background:
            radial-gradient(circle at top right, rgba(20, 87, 38, 0.08), transparent 28%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(247, 250, 248, 0.98));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.82);
    }

    .settings-page .settings-overview-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .settings-page .settings-overview-row + .settings-overview-row {
        margin-top: 0.85rem;
        padding-top: 0.85rem;
        border-top: 1px solid rgba(11, 59, 31, 0.08);
    }

    .settings-page .settings-overview-label {
        color: #5f7569;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .settings-page .settings-overview-value {
        color: #1f3428;
        font-size: 0.95rem;
        font-weight: 700;
        text-align: right;
        word-break: break-word;
    }

    .settings-page .settings-overview-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 0.85rem;
        border: 1px solid rgba(20, 87, 38, 0.08);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.88);
        color: #02681e;
        font-size: 0.82rem;
        font-weight: 700;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.76);
    }

    .settings-page .settings-password-panel {
        display: grid;
        gap: 1.1rem;
    }

    .settings-page .settings-password-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .settings-page .settings-password-trigger {
        min-height: 3.15rem;
        padding-inline: 1.15rem;
        border-radius: 1rem;
        border: 1px solid rgba(20, 87, 38, 0.16);
        background: linear-gradient(135deg, #00491e, #02681e);
        color: #fff;
        box-shadow: 0 16px 28px rgba(20, 87, 38, 0.22);
    }

    .settings-page .settings-password-trigger:hover {
        background: linear-gradient(135deg, #00491e, #02681e);
        color: #fff;
        box-shadow: 0 18px 30px rgba(20, 87, 38, 0.26);
    }

    .settings-page .settings-password-trigger:focus-visible {
        outline: 0;
        box-shadow: 0 0 0 4px rgba(20, 87, 38, 0.14), 0 18px 30px rgba(20, 87, 38, 0.24);
    }

    .settings-page .settings-password-summary {
        display: grid;
        gap: 0.85rem;
    }

    .settings-page .settings-password-summary-item {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.85rem;
        padding: 0.95rem 1rem;
        border: 1px solid rgba(11, 59, 31, 0.08);
        border-radius: 1.15rem;
        background: rgba(255, 255, 255, 0.84);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.82);
    }

    .settings-page .settings-password-summary-label {
        color: #173122;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .settings-page .settings-password-summary-copy {
        margin-top: 0.2rem;
        color: #60786c;
        font-size: 0.86rem;
        line-height: 1.5;
    }

    .settings-page .settings-password-modal {
        position: fixed;
        inset: 0;
        z-index: 60;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.2rem;
    }

    .settings-page .settings-password-modal.hidden {
        display: none;
    }

    .settings-page .settings-password-modal__backdrop {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at top right, rgba(20, 87, 38, 0.16), transparent 28%),
            rgba(7, 24, 14, 0.5);
        backdrop-filter: blur(8px);
    }

    .settings-page .settings-password-modal__card {
        position: relative;
        z-index: 1;
        width: min(100%, 37rem);
        max-height: calc(100vh - 2.4rem);
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 1.45rem;
        background:
            radial-gradient(circle at top right, rgba(200, 154, 45, 0.14), transparent 24%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.985), rgba(251, 248, 241, 0.965));
        box-shadow: 0 34px 78px rgba(7, 24, 14, 0.24);
        color: #132319;
    }

    .settings-page .settings-password-modal__visual {
        position: relative;
        min-height: 5.35rem;
        padding: 0.95rem 0.95rem 0;
        background:
            radial-gradient(circle at 18% 12%, rgba(255, 255, 255, 0.26), transparent 20%),
            radial-gradient(circle at 82% 16%, rgba(200, 154, 45, 0.24), transparent 20%),
            linear-gradient(135deg, rgba(7, 39, 20, 0.98), rgba(15, 74, 43, 0.94) 56%, rgba(30, 122, 52, 0.88));
    }

    .settings-page .settings-password-modal__visual::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0.5;
        background:
            linear-gradient(120deg, rgba(255, 255, 255, 0.12), transparent 42%),
            repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.08) 0 1px, transparent 1px 32px);
    }

    .settings-page .settings-password-modal__visual::before {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 2.35rem;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.96));
    }

    .settings-page .settings-password-modal__close {
        position: absolute;
        top: 0.85rem;
        right: 0.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.8rem;
        height: 2.8rem;
        z-index: 2;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.18);
        transition: background-color 0.18s ease, transform 0.18s ease, border-color 0.18s ease;
    }

    .settings-page .settings-password-modal__close:hover {
        border-color: rgba(255, 255, 255, 0.34);
        background: rgba(255, 255, 255, 0.24);
        transform: translateY(-1px);
    }

    .settings-page .settings-password-modal__art {
        position: absolute;
        left: 50%;
        bottom: -1.45rem;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        z-index: 2;
        gap: 0.35rem;
    }

    .settings-page .settings-password-modal__art-main,
    .settings-page .settings-password-modal__art-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
    }

    .settings-page .settings-password-modal__art-main {
        width: 4.35rem;
        height: 4.35rem;
        border: 5px solid rgba(255, 255, 255, 0.96);
        background:
            radial-gradient(circle at top right, rgba(200, 154, 45, 0.22), transparent 30%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(244, 249, 245, 0.96));
        box-shadow: 0 16px 28px rgba(7, 24, 14, 0.18);
        color: #02681e;
    }

    .settings-page .settings-password-modal__art-main i {
        font-size: 1.8rem;
    }

    .settings-page .settings-password-modal__art-badge {
        width: 2.2rem;
        height: 2.2rem;
        margin-left: -0.65rem;
        margin-top: 1.25rem;
        border: 3px solid rgba(255, 255, 255, 0.96);
        background: linear-gradient(135deg, #ffc600, #ffe173);
        box-shadow: 0 10px 20px rgba(132, 95, 8, 0.2);
    }

    .settings-page .settings-password-modal__art-badge i {
        font-size: 1.1rem;
        color: #fff;
    }

    .settings-page .settings-password-modal__body {
        padding: 2.25rem 1.45rem 1.25rem;
    }

    .settings-page .settings-password-modal__title {
        color: #132319;
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1.08;
        letter-spacing: -0.03em;
    }

    .settings-page .settings-password-modal__copy {
        margin-top: 0.45rem;
        color: #4f675b;
        font-size: 0.92rem;
        line-height: 1.6;
    }

    .settings-page .settings-password-modal__divider {
        margin: 1.05rem 0 0.95rem;
        border-top: 1px solid rgba(11, 59, 31, 0.12);
    }

    .settings-page .settings-password-modal__section-title {
        color: #173122;
        font-size: 1.02rem;
        font-weight: 800;
        line-height: 1.3;
    }

    .settings-page .settings-password-modal__list {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.7rem;
    }

    .settings-page .settings-password-modal__list-item {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 0.65rem;
        align-items: start;
        color: #284134;
        font-size: 0.87rem;
        line-height: 1.45;
    }

    .settings-page .settings-password-modal__list-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.8rem;
        height: 1.8rem;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(20, 87, 38, 0.12), rgba(200, 154, 45, 0.16));
        color: #02681e;
        font-size: 0.9rem;
        box-shadow: inset 0 0 0 1px rgba(11, 59, 31, 0.08);
    }

    .settings-page .settings-password-modal__form {
        margin-top: 1rem;
    }

    .settings-page .settings-password-modal__field {
        display: grid;
        gap: 0.45rem;
        align-content: start;
    }

    .settings-page .settings-password-modal__field label {
        color: #274032;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .settings-page .settings-password-modal .settings-security-form input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=range]):not([type=file]) {
        color: #173122;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 250, 247, 0.96));
        border-color: rgba(11, 59, 31, 0.12);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.92), 0 10px 20px rgba(7, 24, 14, 0.06);
    }

    .settings-page .settings-password-modal .settings-security-form input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=range]):not([type=file]):hover {
        border-color: rgba(20, 87, 38, 0.2);
        background: #fff;
    }

    .settings-page .settings-password-modal .settings-security-form input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=range]):not([type=file]):focus {
        border-color: rgba(20, 87, 38, 0.58);
        background: #fff;
        box-shadow: 0 0 0 4px rgba(20, 87, 38, 0.12), 0 14px 24px rgba(7, 24, 14, 0.08);
    }

    .settings-page .settings-password-modal .settings-security-form input::placeholder {
        color: #88a092;
    }

    .settings-page .settings-password-modal .settings-strength-meter {
        border-color: rgba(11, 59, 31, 0.08);
        background: rgba(248, 250, 247, 0.88);
    }

    .settings-page .settings-password-modal .settings-strength-meter .strength-meter__track {
        background: rgba(11, 59, 31, 0.1);
    }

    .settings-page .settings-password-modal .settings-strength-meter .strength-meter__label {
        color: #173122;
    }

    .settings-page .settings-password-modal .settings-strength-meter .strength-meter__hint {
        color: #647a6f;
    }

    .settings-page .settings-password-modal__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: stretch;
        gap: 0.75rem;
        margin-top: 0.1rem;
        padding-top: 0.1rem;
    }

    .settings-page .settings-password-modal__actions > * {
        flex: 1 1 0;
    }

    .settings-page .settings-password-modal__primary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        min-height: 3rem;
        border: 1px solid rgba(11, 59, 31, 0.12);
        border-radius: 1rem;
        background: linear-gradient(135deg, #00491e, #02681e);
        color: #fff;
        font-weight: 700;
        box-shadow: 0 14px 24px rgba(11, 59, 31, 0.2);
    }

    .settings-page .settings-password-modal__primary:hover {
        background: linear-gradient(135deg, #00491e, #02681e);
    }

    .settings-page .settings-password-modal__primary.settings-password-modal__primary--danger {
        border-color: rgba(220, 38, 38, 0.24);
        background: linear-gradient(135deg, #dc2626, #ef4444);
        box-shadow: 0 14px 24px rgba(220, 38, 38, 0.22);
    }

    .settings-page .settings-password-modal__primary.settings-password-modal__primary--danger:hover {
        background: linear-gradient(135deg, #b91c1c, #dc2626);
    }

    .settings-page .settings-password-modal__secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 3rem;
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.9);
        color: #02681e;
        border: 1px solid rgba(11, 59, 31, 0.12);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.7), 0 10px 18px rgba(7, 24, 14, 0.06);
    }

    .settings-page .settings-password-modal--delete .settings-password-modal__backdrop {
        background:
            radial-gradient(circle at top right, rgba(185, 28, 28, 0.16), transparent 28%),
            rgba(15, 23, 42, 0.54);
    }

    .settings-page .settings-password-modal--delete .settings-password-modal__card {
        width: min(100%, 30rem);
        background:
            radial-gradient(circle at top right, rgba(251, 191, 36, 0.14), transparent 24%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.985), rgba(255, 248, 241, 0.965));
    }

    .settings-page .settings-password-modal--delete .settings-password-modal__visual {
        min-height: 5.5rem;
        background:
            radial-gradient(circle at 18% 12%, rgba(255, 255, 255, 0.22), transparent 20%),
            radial-gradient(circle at 82% 16%, rgba(251, 191, 36, 0.22), transparent 20%),
            linear-gradient(135deg, rgba(127, 29, 29, 0.98), rgba(154, 52, 18, 0.94) 56%, rgba(180, 83, 9, 0.88));
    }

    .settings-page .settings-password-modal--delete .settings-password-modal__art-main {
        color: #9a3412;
    }

    .settings-page .settings-password-modal--delete .settings-password-modal__art-badge {
        background: linear-gradient(135deg, #ffc600, #ffe173);
    }

    .settings-page .settings-password-modal--delete .settings-password-modal__title {
        font-size: 1.55rem;
    }

    .settings-page .settings-password-modal--delete .settings-password-modal__copy {
        max-width: 28rem;
    }

    .settings-page .settings-password-modal--delete .settings-password-modal__list-icon {
        color: #9a3412;
        background: linear-gradient(135deg, rgba(217, 119, 6, 0.12), rgba(220, 38, 38, 0.12));
    }

    .settings-page .settings-field-error {
        color: #b91c1c;
        font-size: 0.82rem;
        font-weight: 600;
        line-height: 1.45;
    }

    .settings-page .settings-danger-zone {
        position: relative;
        display: grid;
        gap: 1.5rem;
        padding: 1.5rem;
        overflow: hidden;
        border: 1px solid rgba(127, 29, 29, 0.12);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(250, 248, 244, 0.96));
        box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
    }

    .settings-page .settings-danger-zone::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, #b91c1c 0%, #ffc600 100%);
    }

    .settings-page .settings-danger-zone::after {
        content: "";
        position: absolute;
        inset: auto -10% -35% auto;
        width: 18rem;
        height: 18rem;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(251, 191, 36, 0.14), transparent 70%);
        pointer-events: none;
    }

    .settings-page .settings-danger-zone__header,
    .settings-page .settings-danger-zone__grid {
        position: relative;
        z-index: 1;
    }

    .settings-page .settings-danger-zone__header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .settings-page .settings-danger-zone__eyebrow {
        color: #991b1b;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .settings-page .settings-danger-zone__title {
        margin-top: 0.45rem;
        color: #0f172a;
        font-size: 1.45rem;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.02em;
    }

    .settings-page .settings-danger-zone__status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        min-height: 2.7rem;
        padding: 0.65rem 1rem;
        border: 1px solid rgba(154, 52, 18, 0.16);
        border-radius: 999px;
        background: rgba(255, 250, 245, 0.96);
        color: #9a3412;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.8);
    }

    .settings-page .settings-danger-zone__status.is-ready {
        border-color: rgba(22, 101, 52, 0.16);
        background: rgba(240, 253, 244, 0.96);
        color: #166534;
    }

    .settings-page .settings-danger-zone__grid {
        display: grid;
        gap: 1.15rem;
    }

    .settings-page .settings-danger-zone__panel,
    .settings-page .settings-danger-zone__step {
        padding: 1.35rem;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1.35rem;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.05);
    }

    .settings-page .settings-danger-zone__panel {
        display: grid;
        gap: 1rem;
    }

    .settings-page .settings-danger-zone__panel-title {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.35;
    }

    .settings-page .settings-danger-zone__panel-copy {
        margin-top: -0.5rem;
        color: #64748b;
        font-size: 0.88rem;
        line-height: 1.6;
    }

    .settings-page .settings-danger-zone__meta {
        display: grid;
    }

    .settings-page .settings-danger-zone__meta-row {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 0.95rem;
        align-items: start;
        padding: 0.95rem 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
    }

    .settings-page .settings-danger-zone__meta-row:first-child {
        padding-top: 0;
    }

    .settings-page .settings-danger-zone__meta-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .settings-page .settings-danger-zone__meta-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 0.95rem;
        background: linear-gradient(180deg, rgba(255, 247, 237, 0.98), rgba(254, 242, 242, 0.94));
        color: #b45309;
        box-shadow: inset 0 0 0 1px rgba(217, 119, 6, 0.12);
    }

    .settings-page .settings-danger-zone__meta-label {
        color: #0f172a;
        font-size: 0.92rem;
        font-weight: 800;
        line-height: 1.4;
    }

    .settings-page .settings-danger-zone__meta-copy {
        margin-top: 0.24rem;
        color: #64748b;
        font-size: 0.89rem;
        line-height: 1.62;
    }

    .settings-page .settings-danger-zone__note {
        padding: 0.95rem 1rem;
        border: 1px solid rgba(180, 83, 9, 0.14);
        border-radius: 1rem;
        background: rgba(255, 248, 241, 0.96);
        color: #92400e;
        font-size: 0.85rem;
        line-height: 1.6;
    }

    .settings-page .settings-danger-zone__step {
        display: grid;
        gap: 1rem;
        align-content: start;
        border-color: rgba(180, 83, 9, 0.12);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(255, 250, 245, 0.96));
    }

    .settings-page .settings-danger-zone__progress {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .settings-page .settings-danger-zone__progress-item {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.8rem 0.9rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 1rem;
        background: rgba(248, 250, 252, 0.88);
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.4;
    }

    .settings-page .settings-danger-zone__progress-item.is-current {
        border-color: rgba(180, 83, 9, 0.2);
        background: rgba(255, 247, 237, 0.96);
        color: #9a3412;
    }

    .settings-page .settings-danger-zone__progress-item.is-complete {
        border-color: rgba(22, 101, 52, 0.16);
        background: rgba(240, 253, 244, 0.96);
        color: #166534;
    }

    .settings-page .settings-danger-zone__progress-index {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.96);
        color: currentColor;
        font-size: 0.78rem;
        font-weight: 800;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.18);
    }

    .settings-page .settings-danger-zone__step-header {
        display: flex;
        align-items: flex-start;
        gap: 0.95rem;
    }

    .settings-page .settings-danger-zone__step-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.8rem;
        height: 2.8rem;
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(255, 247, 237, 0.98), rgba(240, 253, 244, 0.92));
        color: #9a3412;
        box-shadow: inset 0 0 0 1px rgba(180, 83, 9, 0.1);
    }

    .settings-page .settings-danger-zone__step-eyebrow {
        color: #9a3412;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
    }

    .settings-page .settings-danger-zone__step-title {
        margin-top: 0.25rem;
        color: #0f172a;
        font-size: 1.02rem;
        font-weight: 800;
        line-height: 1.3;
    }

    .settings-page .settings-danger-zone__step-copy {
        margin-top: 0.25rem;
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .settings-page .settings-danger-zone__feedback {
        padding: 0.9rem 1rem;
        border-radius: 1rem;
        font-size: 0.86rem;
        line-height: 1.6;
        font-weight: 600;
    }

    .settings-page .settings-danger-zone__feedback--error {
        border: 1px solid rgba(185, 28, 28, 0.12);
        background: rgba(254, 242, 242, 0.94);
        color: #991b1b;
    }

    .settings-page .settings-danger-zone__feedback--success {
        border: 1px solid rgba(22, 101, 52, 0.12);
        background: rgba(240, 253, 244, 0.94);
        color: #166534;
    }

    .settings-page .settings-danger-zone__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .settings-page .settings-danger-zone__actions > * {
        width: 100%;
    }

    .settings-page .settings-danger-zone__form {
        margin-top: 0.1rem;
        gap: 1rem;
    }

    .settings-page .settings-danger-zone__form-field {
        display: grid;
        gap: 0.45rem;
    }

    .settings-page .settings-danger-zone__form-label {
        color: #274032;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-size: 0.76rem;
        font-weight: 800;
    }

    .settings-page .settings-danger-zone__field-hint {
        color: #64748b;
        font-size: 0.82rem;
        line-height: 1.55;
    }

    .settings-page .settings-danger-zone__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        min-height: 3.1rem;
        padding-inline: 1rem 1.15rem;
        border-radius: 1rem;
        font-weight: 800;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }

    .settings-page .settings-danger-zone__button:hover {
        transform: translateY(-1px);
    }

    .settings-page .settings-danger-zone__button--start {
        color: #fff;
        border: 1px solid rgba(20, 87, 38, 0.16);
        background: linear-gradient(135deg, #00491e, #02681e);
        box-shadow: 0 14px 26px rgba(20, 87, 38, 0.22);
    }

    .settings-page .settings-danger-zone__button--start:hover {
        background: linear-gradient(135deg, #00491e, #02681e);
        box-shadow: 0 16px 30px rgba(20, 87, 38, 0.26);
    }

    .settings-page .settings-danger-zone__button--delete {
        border: 1px solid rgba(185, 28, 28, 0.14);
        background: linear-gradient(135deg, #b91c1c, #dc2626);
        box-shadow: 0 14px 28px rgba(185, 28, 28, 0.18);
    }

    .settings-page .settings-danger-zone__button--delete:hover {
        background: linear-gradient(135deg, #991b1b, #b91c1c);
    }

    .settings-page .settings-danger-zone__notice {
        border: 1px solid rgba(180, 83, 9, 0.14);
        border-radius: 1rem;
        background: rgba(255, 247, 237, 0.94);
        color: #9a3412;
        font-size: 0.87rem;
        line-height: 1.6;
    }

    @media (min-width: 1100px) {
        .settings-page .settings-danger-zone__grid {
            grid-template-columns: minmax(0, 1.12fr) minmax(21rem, 0.88fr);
            align-items: start;
        }
    }

    @media (max-width: 767px) {
        .settings-page .settings-danger-zone {
            padding: 1.2rem;
        }

        .settings-page .settings-danger-zone__progress {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    .settings-page .settings-profile-form {
        gap: 1.1rem;
    }

    .settings-page .alumni-form-grid--two {
        gap: 1rem 1.1rem;
    }

    .settings-page .alumni-form-field {
        gap: 0.55rem;
    }

    .settings-page .alumni-form-field > label {
        color: #274032;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        font-size: 0.75rem;
        font-weight: 800;
    }

    .settings-page .alumni-form-field input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=range]):not([type=file]),
    .settings-page .alumni-form-field textarea,
    .settings-page .settings-security-form input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=range]):not([type=file]),
    .settings-page .settings-security-form textarea,
    .settings-page .portal-select {
        width: 100%;
        min-height: 3.35rem;
        padding: 0.95rem 1.05rem;
        border: 1px solid rgba(11, 59, 31, 0.12);
        border-radius: 1.1rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 250, 247, 0.96));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.9), 0 12px 24px rgba(7, 24, 14, 0.06);
    }

    .settings-page .portal-select {
        padding-right: 4rem !important;
    }

    .settings-page .alumni-form-field textarea {
        min-height: 8.5rem;
        padding-top: 1rem;
        resize: vertical;
    }

    .settings-page .portal-select-wrap,
    .settings-page .tracer-select-wrap {
        border-radius: 1.1rem;
    }

    .settings-page .portal-select-wrap__icon {
        width: 3rem;
        border-left-color: rgba(11, 59, 31, 0.1);
        background: linear-gradient(180deg, rgba(248, 250, 247, 0.98), rgba(239, 245, 240, 0.98));
    }

    .settings-page .alumni-form-field__hint {
        color: #627a6f;
        font-size: 0.82rem;
    }

    .settings-page .settings-submit-row {
        margin-top: 0.2rem;
        border-top-color: rgba(11, 59, 31, 0.09);
        background: linear-gradient(180deg, rgba(247, 250, 248, 0), rgba(247, 250, 248, 0.95));
    }

    .settings-page .settings-security-stack {
        display: grid;
        gap: 1rem;
        max-width: 68rem;
        margin-inline: auto;
    }

    .settings-page .settings-security-block {
        padding: 1.15rem;
        border: 1px solid var(--settings-line);
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top right, rgba(200, 154, 45, 0.08), transparent 22%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(247, 250, 248, 0.98));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.8);
    }

    .settings-page .settings-section-title {
        color: #355245;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        font-size: 0.8rem;
        font-weight: 800;
    }

    .settings-page .settings-section-copy {
        margin-top: 0.45rem;
        color: #627a6f;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .settings-page .settings-security-form {
        margin-top: 1rem;
        display: grid;
        gap: 0.95rem;
    }

    .settings-page .settings-security-form--device {
        max-width: 30rem;
        padding-top: 0.25rem;
    }

    .settings-page .settings-session-list {
        display: grid;
        gap: 0.85rem;
        margin-top: 1rem;
    }

    .settings-page .settings-session-card,
    .settings-page .settings-session-empty {
        border: 1px solid rgba(11, 59, 31, 0.1);
        border-radius: 1.15rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 247, 0.96));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.82), 0 10px 22px rgba(7, 24, 14, 0.05);
    }

    .settings-page .settings-session-card__inner {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.9rem;
        padding: 0.95rem 1rem;
    }

    .settings-page .settings-session-card__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.65rem;
        height: 2.65rem;
        border-radius: 0.95rem;
        background: rgba(20, 87, 38, 0.1);
        color: #02681e;
        font-size: 1.1rem;
    }

    .settings-page .settings-session-card__body {
        min-width: 0;
    }

    .settings-page .settings-session-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.06);
        color: #334155;
        font-size: 0.75rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .settings-page .settings-session-badge--current {
        background: rgba(16, 185, 129, 0.13);
        color: #047857;
    }

    .settings-page .settings-session-empty {
        padding: 1rem;
        color: #5f7569;
        font-size: 0.9rem;
    }

    .settings-page .settings-security-form--password {
        gap: 1rem;
    }

    .settings-page .settings-password-grid {
        display: grid;
        gap: 0.95rem 1rem;
        align-items: start;
    }

    .settings-page .settings-strength-meter {
        margin-top: 0.1rem;
        padding: 0.95rem 1rem;
        border: 1px solid rgba(11, 59, 31, 0.08);
        border-radius: 1.15rem;
        background: rgba(248, 250, 247, 0.88);
    }

    .settings-page .settings-strength-meter .strength-meter__track {
        height: 0.55rem;
        background: rgba(11, 59, 31, 0.1);
    }

    .settings-page .settings-strength-meter .strength-meter__meta {
        margin-top: 0.65rem;
        font-size: 0.82rem;
    }

    .settings-page .settings-action-button {
        min-height: 3.45rem;
        border-radius: 1.15rem;
        box-shadow: 0 14px 26px rgba(7, 24, 14, 0.14);
    }

    .settings-page .alumni-progress-track {
        height: 0.95rem;
        border: 1px solid rgba(11, 59, 31, 0.08);
        box-shadow: inset 0 1px 2px rgba(7, 24, 14, 0.05);
    }

    .settings-page .alumni-checklist-item {
        border-radius: 1.15rem;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.58);
    }

    @media (min-width: 768px) {
        .settings-page .settings-tools-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .settings-page .settings-password-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .settings-page .settings-top-grid {
            grid-template-columns: minmax(0, 1.7fr) minmax(320px, 0.95fr);
            align-items: stretch;
        }
    }

    @media (min-width: 1280px) {
        .settings-page .settings-security-stack {
            grid-template-columns: minmax(0, 1fr);
            align-items: start;
        }

        .settings-page #password-settings {
            max-width: 52rem;
            width: 100%;
            margin-inline: auto;
        }

        .settings-page #device-sessions {
            width: 100%;
        }
    }

    @media (max-width: 767px) {
        .settings-page .settings-tool-card {
            grid-template-columns: minmax(0, 1fr);
        }

        .settings-page .settings-security-block {
            padding: 1rem;
            border-radius: 1.15rem;
        }

        .settings-page .settings-session-card__inner {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .settings-page .settings-session-badge {
            grid-column: 2;
            justify-self: start;
        }

        .settings-page .settings-password-modal {
            padding: 0.8rem;
        }

        .settings-page .settings-password-modal__card {
            border-radius: 1.25rem;
        }

        .settings-page .settings-password-modal__visual {
            min-height: 5rem;
        }

        .settings-page .settings-password-modal__art {
            bottom: -1.35rem;
        }

        .settings-page .settings-password-modal__art-main {
            width: 4rem;
            height: 4rem;
        }

        .settings-page .settings-password-modal__art-main i {
            font-size: 1.65rem;
        }

        .settings-page .settings-password-modal__art-badge {
            width: 2.15rem;
            height: 2.15rem;
            margin-left: -0.55rem;
            margin-top: 1.2rem;
        }

        .settings-page .settings-password-modal__body {
            padding: 2.2rem 1rem 1rem;
        }

        .settings-page .settings-password-modal__title {
            font-size: 1.5rem;
        }

        .settings-page .settings-password-modal__actions > * {
            flex-basis: 100%;
        }

        .settings-page .settings-danger-zone__meta-copy {
            text-align: left;
        }

        .settings-page .settings-submit-row {
            padding-top: 1rem;
        }

        .settings-page .alumni-panel-title {
            font-size: 1.15rem;
        }
    }

    .settings-page {
        --settings-forest: #00491e;
        --settings-forest-bright: #02681e;
        --settings-gold: #ffc600;
        --settings-gold-soft: rgba(255, 198, 0, 0.22);
        --settings-sky: #02681e;
        --settings-rose: #ffc600;
    }

    .settings-page .settings-surface {
        isolation: isolate;
        border-color: rgba(11, 59, 31, 0.14);
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(251, 248, 241, 0.94) 52%, rgba(240, 247, 242, 0.96)),
            repeating-linear-gradient(90deg, rgba(11, 59, 31, 0.028) 0 1px, transparent 1px 42px);
        box-shadow: 0 24px 54px rgba(7, 24, 14, 0.13);
    }

    .settings-page .settings-surface::before {
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.52), transparent 34%),
            repeating-linear-gradient(0deg, rgba(11, 59, 31, 0.028) 0 1px, transparent 1px 42px),
            repeating-linear-gradient(90deg, rgba(11, 59, 31, 0.026) 0 1px, transparent 1px 42px);
        opacity: 0.48;
    }

    .settings-page .settings-surface::after {
        content: "";
        position: absolute;
        z-index: 0;
        inset: 0 0 auto;
        height: 5px;
        background: linear-gradient(90deg, var(--settings-forest), var(--settings-gold), var(--settings-sky));
        opacity: 0.92;
        pointer-events: none;
    }

    .settings-page .settings-surface--command::after {
        background: linear-gradient(90deg, var(--settings-forest), var(--settings-gold), var(--settings-rose));
    }

    .settings-page .settings-surface--summary::after {
        background: linear-gradient(90deg, var(--settings-sky), var(--settings-forest), var(--settings-gold));
    }

    .settings-page .settings-surface--security::after {
        background: linear-gradient(90deg, var(--settings-forest), var(--settings-forest-bright), var(--settings-gold));
    }

    .settings-page .settings-section-heading,
    .settings-page .settings-security-title,
    .settings-page .settings-overview-head {
        display: flex;
        align-items: flex-start;
        gap: 0.95rem;
        min-width: 0;
    }

    .settings-page .settings-section-emblem,
    .settings-page .settings-security-title__icon,
    .settings-page .settings-overview-emblem {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        color: #fff;
        background: linear-gradient(135deg, var(--settings-forest), var(--settings-forest-bright));
        box-shadow: 0 14px 26px rgba(7, 24, 14, 0.2);
    }

    .settings-page .settings-section-emblem i,
    .settings-page .settings-security-title__icon i,
    .settings-page .settings-overview-emblem i {
        font-size: 1.35rem;
    }

    .settings-page .settings-tool-card {
        --tool-accent: linear-gradient(180deg, var(--settings-forest), var(--settings-gold));
        --tool-icon-bg: linear-gradient(135deg, rgba(20, 87, 38, 0.14), rgba(200, 154, 45, 0.2));
        --tool-icon-color: var(--settings-forest);
        position: relative;
        min-height: 6.55rem;
        overflow: hidden;
        border-color: rgba(11, 59, 31, 0.14);
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(247, 250, 248, 0.94)),
            repeating-linear-gradient(135deg, rgba(11, 59, 31, 0.035) 0 1px, transparent 1px 14px);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.9), 0 12px 24px rgba(7, 24, 14, 0.07);
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .settings-page .settings-tool-card::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 0.38rem;
        background: var(--tool-accent);
    }

    .settings-page .settings-tool-card::after {
        content: "";
        position: absolute;
        right: 0.85rem;
        bottom: 0.75rem;
        width: 3rem;
        height: 0.32rem;
        border-radius: 999px;
        background: var(--tool-accent);
        opacity: 0.2;
    }

    .settings-page .settings-tool-card:hover {
        transform: translateY(-3px);
        border-color: rgba(20, 87, 38, 0.28);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.92), 0 18px 36px rgba(7, 24, 14, 0.12);
    }

    .settings-page .settings-tool-card:hover .settings-tool-arrow {
        transform: translateX(3px);
    }

    .settings-page .settings-tool-card--password {
        --tool-accent: linear-gradient(180deg, var(--settings-forest), var(--settings-forest-bright));
        --tool-icon-bg: linear-gradient(135deg, rgba(2, 104, 30, 0.12), rgba(255, 198, 0, 0.18));
        --tool-icon-color: var(--settings-forest);
    }

    .settings-page .settings-tool-card--devices {
        --tool-accent: linear-gradient(180deg, var(--settings-sky), var(--settings-forest));
        --tool-icon-bg: linear-gradient(135deg, rgba(0, 73, 30, 0.1), rgba(2, 104, 30, 0.12));
        --tool-icon-color: var(--settings-forest);
    }

    .settings-page .settings-tool-card--profile {
        --tool-accent: linear-gradient(180deg, var(--settings-gold), var(--settings-rose));
        --tool-icon-bg: linear-gradient(135deg, #fff7df, #f7e5ea);
        --tool-icon-color: var(--settings-rose);
    }

    .settings-page .settings-tool-icon {
        border-radius: 0.95rem;
        background: var(--tool-icon-bg);
        color: var(--tool-icon-color);
        box-shadow: inset 0 0 0 1px rgba(11, 59, 31, 0.08), 0 10px 20px rgba(7, 24, 14, 0.08);
    }

    .settings-page .settings-tool-arrow {
        position: relative;
        z-index: 1;
        transition: transform 0.18s ease;
    }

    .settings-page .settings-overview-emblem {
        color: var(--settings-forest);
        background: linear-gradient(135deg, #ffffff, var(--settings-gold-soft));
        box-shadow: inset 0 0 0 1px rgba(200, 154, 45, 0.24), 0 14px 24px rgba(132, 95, 8, 0.12);
    }

    .settings-page .settings-section-heading,
    .settings-page .settings-security-title {
        flex: 1 1 auto;
    }

    .settings-page .settings-overview-head {
        align-items: center;
        justify-content: flex-start;
    }

    .settings-page .settings-overview-head .settings-overview-status {
        margin-top: 0.7rem;
    }

    .settings-page .settings-overview-card {
        display: grid;
        gap: 0.7rem;
        padding: 0.75rem;
        border-color: rgba(11, 59, 31, 0.12);
        background: rgba(255, 255, 255, 0.78);
        backdrop-filter: blur(8px);
    }

    .settings-page .settings-overview-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 0.95rem;
        border: 1px solid rgba(11, 59, 31, 0.08);
        border-radius: 1rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 247, 0.92));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.8);
    }

    .settings-page .settings-overview-row + .settings-overview-row {
        margin-top: 0;
        padding-top: 0.9rem;
        border-top: 1px solid rgba(11, 59, 31, 0.08);
    }

    .settings-page .settings-overview-label-wrap {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
    }

    .settings-page .settings-overview-row-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 0.8rem;
        color: var(--settings-forest);
        background: rgba(11, 59, 31, 0.08);
    }

    .settings-page .settings-overview-value {
        max-width: 16rem;
        color: #07180e;
        font-weight: 800;
    }

    .settings-page .settings-security-hero {
        align-items: center;
        padding: 1.05rem;
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 1.25rem;
        background:
            linear-gradient(135deg, rgba(7, 39, 20, 0.98), rgba(15, 74, 43, 0.94)),
            repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.08) 0 1px, transparent 1px 34px);
        box-shadow: 0 18px 34px rgba(7, 24, 14, 0.18);
    }

    .settings-page .settings-security-title__icon {
        color: var(--settings-forest);
        background: linear-gradient(135deg, #ffffff, var(--settings-gold-soft));
        box-shadow: inset 0 0 0 1px rgba(200, 154, 45, 0.18), 0 14px 24px rgba(0, 0, 0, 0.14);
    }

    .settings-page .settings-security-hero .alumni-panel-title {
        color: #fff;
    }

    .settings-page .settings-security-hero .alumni-panel-copy {
        color: rgba(255, 255, 255, 0.76);
    }

    .settings-page .settings-security-hero .alumni-pill {
        color: #f7f9f7;
        border-color: rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.12);
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.12);
    }

    .settings-page .settings-security-stack {
        max-width: 74rem;
        gap: 1.2rem;
    }

    .settings-page .settings-security-block {
        position: relative;
        overflow: hidden;
        border-color: rgba(11, 59, 31, 0.12);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 247, 0.94));
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.88), 0 16px 34px rgba(7, 24, 14, 0.08);
    }

    .settings-page .settings-security-block::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 4px;
        background: linear-gradient(90deg, var(--settings-gold), var(--settings-forest), var(--settings-sky));
    }

    .settings-page .settings-security-block--password .settings-password-header {
        padding: 1.1rem;
        border-radius: 1.15rem;
        background:
            linear-gradient(135deg, rgba(7, 39, 20, 0.98), rgba(20, 87, 38, 0.94)),
            repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.08) 0 1px, transparent 1px 32px);
        color: #fff;
        box-shadow: inset 0 1px rgba(255, 255, 255, 0.12), 0 18px 30px rgba(7, 24, 14, 0.16);
    }

    .settings-page .settings-security-block--password .settings-section-title {
        color: var(--settings-gold-soft);
    }

    .settings-page .settings-security-block--password .settings-section-copy {
        color: rgba(255, 255, 255, 0.78);
    }

    .settings-page .settings-security-block--password .settings-password-trigger {
        color: var(--settings-forest);
        border-color: rgba(255, 255, 255, 0.82);
        background: linear-gradient(135deg, #ffffff, #f7faf8);
        box-shadow: 0 16px 28px rgba(0, 0, 0, 0.18);
    }

    .settings-page .settings-security-block--password .settings-password-trigger:hover {
        color: #072714;
        background: linear-gradient(135deg, #ffffff, rgba(255, 198, 0, 0.22));
        box-shadow: 0 18px 34px rgba(0, 0, 0, 0.22);
    }

    .settings-page .settings-password-summary {
        margin-top: 1.05rem;
    }

    .settings-page .settings-password-summary-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        padding: 1rem;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 247, 0.94)),
            repeating-linear-gradient(135deg, rgba(11, 59, 31, 0.025) 0 1px, transparent 1px 16px);
    }

    .settings-page .settings-password-summary-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.65rem;
        height: 2.65rem;
        border-radius: 0.95rem;
        color: var(--settings-forest);
        background: linear-gradient(135deg, #e8f7ee, #ffffff);
        box-shadow: inset 0 0 0 1px rgba(11, 59, 31, 0.08);
    }

    .settings-page .settings-password-summary-item--requirement .settings-password-summary-icon {
        color: var(--settings-rose);
        background: linear-gradient(135deg, #fff7df, #f7e5ea);
    }

    .settings-page .settings-session-card,
    .settings-page .settings-session-empty {
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 247, 0.94)),
            repeating-linear-gradient(90deg, rgba(37, 99, 235, 0.024) 0 1px, transparent 1px 30px);
    }

    .settings-page .settings-session-card__icon {
        color: #00491e;
        background: linear-gradient(135deg, #eaf3ff, #e8f7ee);
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.08);
    }

    @media (min-width: 860px) {
        .settings-page .settings-password-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .settings-page .settings-section-heading,
        .settings-page .settings-security-title,
        .settings-page .settings-overview-head {
            gap: 0.75rem;
        }

        .settings-page .settings-section-emblem,
        .settings-page .settings-security-title__icon,
        .settings-page .settings-overview-emblem {
            width: 2.65rem;
            height: 2.65rem;
            border-radius: 0.9rem;
        }

        .settings-page .settings-tool-card {
            grid-template-columns: auto minmax(0, 1fr) auto;
            min-height: auto;
        }

        .settings-page .settings-overview-row {
            grid-template-columns: minmax(0, 1fr);
        }

        .settings-page .settings-overview-value {
            max-width: none;
            text-align: left;
        }

        .settings-page .settings-security-hero,
        .settings-page .settings-security-block--password .settings-password-header {
            align-items: flex-start;
        }

        .settings-page .settings-password-summary-item {
            grid-template-columns: auto minmax(0, 1fr);
        }

        .settings-page .settings-password-summary-item .alumni-pill {
            grid-column: 2;
            justify-self: start;
        }
    }
</style>

<div class="settings-page w-full max-w-none space-y-6">
            <section class="settings-top-grid">
                <div class="surface-card settings-surface settings-surface--command settings-quick-access p-5">
                    <div class="settings-tools-panel">
                        <div class="alumni-panel-header">
                            <div class="settings-section-heading">
                                <span class="settings-section-emblem" aria-hidden="true">
                                    <i class='bx bx-slider-alt'></i>
                                </span>
                                <div>
                                    <p class="settings-section-title">Account Controls</p>
                                    <h2 class="alumni-panel-title mt-2">Essential settings</h2>
                                </div>
                            </div>
                            <span class="alumni-pill">
                                <i class='bx bx-check-shield text-base'></i>
                                Protected
                            </span>
                        </div>

                        <div class="settings-tools-grid">
                            <button type="button" class="settings-tool-card settings-tool-card--primary settings-tool-card--password" data-settings-scroll-target="password-settings" data-settings-focus-target="openPasswordModal">
                                <span class="settings-tool-icon" aria-hidden="true">
                                    <i class='bx bx-key'></i>
                                </span>
                                <span class="settings-tool-body">
                                    <span class="settings-tool-title">Password and sign-in</span>
                                    <span class="settings-tool-copy">Update your password using a secure modal.</span>
                                </span>
                                <i class='bx bx-chevron-right settings-tool-arrow' aria-hidden="true"></i>
                            </button>

                            <button type="button" class="settings-tool-card settings-tool-card--devices" data-settings-scroll-target="device-sessions" data-settings-focus-target="{{ $otherSessions->isNotEmpty() ? 'logoutOtherDevicesPassword' : 'device-sessions' }}">
                                <span class="settings-tool-icon" aria-hidden="true">
                                    <i class='bx bx-devices'></i>
                                </span>
                                <span class="settings-tool-body">
                                    <span class="settings-tool-title">Signed-in devices</span>
                                    <span class="settings-tool-copy">{{ $sessionCountLabel }} connected to this account.</span>
                                </span>
                                <i class='bx bx-chevron-right settings-tool-arrow' aria-hidden="true"></i>
                            </button>

                            <a href="{{ route('profile') }}" class="settings-tool-card settings-tool-card--profile">
                                <span class="settings-tool-icon" aria-hidden="true">
                                    <i class='bx bx-user-circle'></i>
                                </span>
                                <span class="settings-tool-body">
                                    <span class="settings-tool-title">Profile information</span>
                                    <span class="settings-tool-copy">Review your contact details, newsletter preference, and alumni record.</span>
                                </span>
                                <i class='bx bx-chevron-right settings-tool-arrow' aria-hidden="true"></i>
                            </a>

                        </div>

                    </div>
                </div>

                <aside class="surface-card settings-surface settings-surface--summary settings-overview p-5">
                    <div class="settings-overview-head">
                        <span class="settings-overview-emblem" aria-hidden="true">
                            <i class='bx bx-shield-quarter'></i>
                        </span>
                        <div class="min-w-0">
                            <p class="settings-section-title">Account Summary</p>
                            <span class="settings-overview-status">
                                <i class='bx bx-check-circle text-base'></i>
                                Active account
                            </span>
                        </div>
                    </div>
                    <div class="settings-overview-card">
                        <div class="settings-overview-row">
                            <span class="settings-overview-label-wrap">
                                <span class="settings-overview-row-icon" aria-hidden="true">
                                    <i class='bx bx-envelope'></i>
                                </span>
                                <span class="settings-overview-label">Email address</span>
                            </span>
                            <span class="settings-overview-value">{{ $profileUser?->email ?? 'No email set' }}</span>
                        </div>
                        <div class="settings-overview-row">
                            <span class="settings-overview-label-wrap">
                                <span class="settings-overview-row-icon" aria-hidden="true">
                                    <i class='bx bx-devices'></i>
                                </span>
                                <span class="settings-overview-label">Devices</span>
                            </span>
                            <span class="settings-overview-value">{{ $sessionCountLabel }}</span>
                        </div>
                        <div class="settings-overview-row">
                            <span class="settings-overview-label-wrap">
                                <span class="settings-overview-row-icon" aria-hidden="true">
                                    <i class='bx bx-envelope-open'></i>
                                </span>
                                <span class="settings-overview-label">OARL updates</span>
                            </span>
                            <span class="settings-overview-value">{{ ($profileUser?->newsletter_opt_in ?? true) ? 'Subscribed' : 'Not subscribed' }}</span>
                        </div>
                        <div class="settings-overview-row">
                            <span class="settings-overview-label-wrap">
                                <span class="settings-overview-row-icon" aria-hidden="true">
                                    <i class='bx bx-lock-alt'></i>
                                </span>
                                <span class="settings-overview-label">Delete protection</span>
                            </span>
                            <span id="deleteGuardLabel" class="settings-overview-value">{{ $deleteGuardLabel }}</span>
                        </div>
                    </div>
                </aside>
            </section>

            <section class="surface-card settings-surface settings-surface--security p-6">
                <div class="alumni-panel-header settings-security-hero">
                    <div class="settings-security-title">
                        <span class="settings-security-title__icon" aria-hidden="true">
                            <i class='bx bx-fingerprint'></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="alumni-panel-title">Sign-in security</h2>
                            <p class="alumni-panel-copy">Review password controls and active device sessions for this account.</p>
                        </div>
                    </div>
                    <span class="alumni-pill">
                        <i class='bx bx-check-shield text-base'></i>
                        Secure account
                    </span>
                </div>

                <div class="settings-security-stack mt-6">
                    <div id="password-settings" class="settings-security-block settings-security-block--password scroll-mt-24">
                        <div class="settings-password-panel">
                            <div class="settings-password-header">
                                <div>
                                    <h3 class="settings-section-title">Password and sign-in</h3>
                                    <p class="settings-section-copy">Use a strong password and update it whenever account access may be at risk.</p>
                                </div>
                                <button type="button" id="openPasswordModal" class="alumni-button-secondary settings-password-trigger" aria-expanded="{{ $passwordModalShouldOpen ? 'true' : 'false' }}" aria-controls="passwordChangeModal">
                                    <i class='bx bx-lock-alt text-lg'></i>
                                    Change Password
                                </button>
                            </div>

                            <div class="settings-password-summary">
                                <div class="settings-password-summary-item settings-password-summary-item--protection">
                                    <span class="settings-password-summary-icon" aria-hidden="true">
                                        <i class='bx bx-shield-quarter'></i>
                                    </span>
                                    <div>
                                        <p class="settings-password-summary-label">Password protection</p>
                                        <p class="settings-password-summary-copy">Password verification is required before sensitive account changes.</p>
                                    </div>
                                    <span class="alumni-pill">
                                        <i class='bx bx-check-shield text-base'></i>
                                        Active
                                    </span>
                                </div>
                                <div class="settings-password-summary-item settings-password-summary-item--requirement">
                                    <span class="settings-password-summary-icon" aria-hidden="true">
                                        <i class='bx bx-key'></i>
                                    </span>
                                    <div>
                                        <p class="settings-password-summary-label">Update requirement</p>
                                        <p class="settings-password-summary-copy">Current password and a confirmed new password are required.</p>
                                    </div>
                                    <span class="alumni-pill alumni-pill--gold">
                                        <i class='bx bx-check-circle text-base'></i>
                                        Required
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="device-sessions" class="settings-security-block settings-security-block--devices scroll-mt-24" tabindex="-1">
                        <h3 class="settings-section-title">Signed-in Devices</h3>
                        <p class="settings-section-copy">Review where your account is open and sign out other devices when needed.</p>

                        <div class="settings-session-list">
                            @forelse($sessions as $session)
                                <div class="settings-session-card">
                                    <div class="settings-session-card__inner">
                                        <span class="settings-session-card__icon" aria-hidden="true">
                                            <i class='bx bx-laptop'></i>
                                        </span>
                                        <div class="settings-session-card__body">
                                            <p class="text-sm font-semibold text-slate-900">{{ $session['browser'] }} | {{ $session['device'] }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $session['ip_address'] }} | Last active {{ $session['last_seen'] }}</p>
                                        </div>
                                        <span class="settings-session-badge {{ $session['is_current'] ? 'settings-session-badge--current' : '' }}">
                                            {{ $session['is_current'] ? 'This device' : 'Other device' }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="settings-session-empty">
                                    You are signed in on this device only.
                                </div>
                            @endforelse
                        </div>

                        @if($otherSessions->isNotEmpty())
                            <form action="{{ route('settings.sessions.logout-other') }}" method="POST" class="settings-security-form settings-security-form--device">
                                @csrf
                                <input type="hidden" name="settings_form" value="logout_other_devices">
                                <input id="logoutOtherDevicesPassword" type="password" name="current_password" placeholder="Current password" required>
                                <button type="submit" class="alumni-button-secondary settings-action-button w-full">
                                    <i class='bx bx-log-out-circle text-lg'></i>
                                    Sign Out Other Devices
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </section>

            <section id="danger-zone" class="surface-card settings-danger-zone scroll-mt-24" tabindex="-1">
                <div class="settings-danger-zone__header">
                    <div>
                        <p class="settings-danger-zone__eyebrow">Danger Zone</p>
                        <h2 class="settings-danger-zone__title">Close Account</h2>
                    </div>
                    <span id="deleteStatusBadge" class="settings-danger-zone__status {{ $deleteVerified ? 'is-ready' : '' }}">
                        <i id="deleteStatusBadgeIcon" class='bx {{ $deleteVerified ? 'bx-check-shield' : 'bx-lock-alt' }} text-base'></i>
                        <span id="deleteStatusBadgeText">{{ $deleteVerified ? 'Final confirmation unlocked' : 'Verification required' }}</span>
                    </span>
                </div>

                <div class="settings-danger-zone__grid">
                    <div class="settings-danger-zone__panel">
                        <p class="settings-danger-zone__panel-title">What to expect</p>
                        <p class="settings-danger-zone__panel-copy">Account closure follows a controlled two-step process to reduce accidental or unauthorized deletion.</p>
                        <div class="settings-danger-zone__meta">
                            <div class="settings-danger-zone__meta-row">
                                <span class="settings-danger-zone__meta-icon">
                                    <i class='bx bx-log-out-circle text-lg'></i>
                                </span>
                                <div>
                                    <p class="settings-danger-zone__meta-label">Portal access ends immediately</p>
                                    <p class="settings-danger-zone__meta-copy">You will no longer be able to sign in to the alumni portal with this account after deletion is completed.</p>
                                </div>
                            </div>
                            <div class="settings-danger-zone__meta-row">
                                <span class="settings-danger-zone__meta-icon">
                                    <i class='bx bx-data text-lg'></i>
                                </span>
                                <div>
                                    <p class="settings-danger-zone__meta-label">Account closure is permanent</p>
                                    <p class="settings-danger-zone__meta-copy">This action permanently removes your account access and related alumni portal records.</p>
                                </div>
                            </div>
                            <div class="settings-danger-zone__meta-row">
                                <span class="settings-danger-zone__meta-icon">
                                    <i class='bx bx-check-shield text-lg'></i>
                                </span>
                                <div>
                                    <p class="settings-danger-zone__meta-label">Password verification is required</p>
                                    <p class="settings-danger-zone__meta-copy">We will ask for your current password before the final delete option becomes available.</p>
                                </div>
                            </div>
                        </div>
                        <div class="settings-danger-zone__note">
                            If you only need better security, use <strong>Password &amp; Sign-in</strong> above instead of closing the account.
                        </div>
                    </div>

                    <div id="deleteVerificationStep" class="settings-danger-zone__step {{ $deleteVerified ? 'hidden' : '' }}">
                        <div class="settings-danger-zone__progress" aria-hidden="true">
                            <div class="settings-danger-zone__progress-item is-current">
                                <span class="settings-danger-zone__progress-index">1</span>
                                <span>Password verification</span>
                            </div>
                            <div class="settings-danger-zone__progress-item">
                                <span class="settings-danger-zone__progress-index">2</span>
                                <span>Delete confirmation</span>
                            </div>
                        </div>

                        <div class="settings-danger-zone__step-header">
                            <span class="settings-danger-zone__step-icon">
                                <i class='bx bx-check-shield text-lg'></i>
                            </span>
                            <div>
                                <p class="settings-danger-zone__step-eyebrow">Step 1 of 2</p>
                                <h3 class="settings-danger-zone__step-title">Verify account ownership</h3>
                                <p class="settings-danger-zone__step-copy">Open a secure mini view to confirm your current password and unlock the final account-closure confirmation.</p>
                            </div>
                        </div>

                        <div class="settings-danger-zone__actions">
                            <button type="button" id="startDeleteVerification" class="settings-danger-zone__button settings-danger-zone__button--start" aria-expanded="{{ $deleteVerificationModalShouldOpen ? 'true' : 'false' }}" aria-controls="deleteVerificationModal" aria-haspopup="dialog">
                                <i class='bx bx-user-check text-lg'></i>
                                Start Verification
                            </button>
                        </div>
                    </div>

                    <div id="deleteConfirmationStep" class="settings-danger-zone__step {{ $deleteVerified ? '' : 'hidden' }}">
                        <div class="settings-danger-zone__progress" aria-hidden="true">
                            <div class="settings-danger-zone__progress-item is-complete">
                                <span class="settings-danger-zone__progress-index">
                                    <i class='bx bx-check text-base'></i>
                                </span>
                                <span>Password verification</span>
                            </div>
                            <div class="settings-danger-zone__progress-item is-current">
                                <span class="settings-danger-zone__progress-index">2</span>
                                <span>Delete confirmation</span>
                            </div>
                        </div>

                        <div class="settings-danger-zone__step-header">
                            <span class="settings-danger-zone__step-icon">
                                <i class='bx bx-trash text-lg'></i>
                            </span>
                            <div>
                                <p class="settings-danger-zone__step-eyebrow">Step 2 of 2</p>
                                <h3 class="settings-danger-zone__step-title">Confirm permanent deletion</h3>
                                <p class="settings-danger-zone__step-copy">Verification is complete. Open the delete confirmation mini view to finish closing this account. This confirmation stays active for 10 minutes.</p>
                            </div>
                        </div>

                        <div id="deleteConfirmationStepError" class="settings-danger-zone__feedback settings-danger-zone__feedback--error {{ $deleteVerified && $errors->has('delete_account') ? '' : 'hidden' }}">
                            {{ $deleteVerified ? $errors->first('delete_account') : '' }}
                        </div>

                        <div id="deleteConfirmationStepSuccess" class="settings-danger-zone__feedback settings-danger-zone__feedback--success {{ $deleteVerified ? '' : 'hidden' }}">
                            Password verified. Final confirmation is now available for 10 minutes.
                        </div>

                        <div class="settings-danger-zone__notice px-4 py-3">
                            Final check: type <strong>DELETE</strong> exactly as shown inside the mini view to enable permanent account removal.
                        </div>

                        <div class="settings-danger-zone__actions">
                            <button type="button" id="openDeleteConfirmation" class="settings-danger-zone__button settings-danger-zone__button--delete" aria-expanded="{{ $deleteConfirmationModalShouldOpen ? 'true' : 'false' }}" aria-controls="deleteConfirmationModal" aria-haspopup="dialog">
                                <i class='bx bx-trash text-lg'></i>
                                Open Delete Confirmation
                            </button>
                        </div>
                    </div>
                </div>
            </section>

<div id="deleteVerificationModal" class="settings-password-modal settings-password-modal--delete {{ $deleteVerificationModalShouldOpen ? '' : 'hidden' }}" aria-hidden="{{ $deleteVerificationModalShouldOpen ? 'false' : 'true' }}">
    <div class="settings-password-modal__backdrop" data-delete-modal-close></div>

    <div class="settings-password-modal__card" role="dialog" aria-modal="true" aria-labelledby="deleteVerificationModalTitle">
        <div class="settings-password-modal__visual">
            <button type="button" class="settings-password-modal__close" data-delete-modal-close aria-label="Close verification view">
                <i class='bx bx-x text-2xl'></i>
            </button>
            <div class="settings-password-modal__art" aria-hidden="true">
                <span class="settings-password-modal__art-main">
                    <i class='bx bx-check-shield'></i>
                </span>
                <span class="settings-password-modal__art-badge">
                    <i class='bx bx-lock-alt'></i>
                </span>
            </div>
        </div>

        <div class="settings-password-modal__body">
            <h3 id="deleteVerificationModalTitle" class="settings-password-modal__title">Verify account ownership</h3>
            <p class="settings-password-modal__copy">Enter your current password in this secure mini view to unlock the final account-closure confirmation.</p>

            <div class="settings-password-modal__divider"></div>

            <h4 class="settings-password-modal__section-title">Before you continue</h4>
            <div class="settings-password-modal__list">
                <div class="settings-password-modal__list-item">
                    <span class="settings-password-modal__list-icon">
                        <i class='bx bx-user-check'></i>
                    </span>
                    <span>We verify that the request is being made by the current account owner.</span>
                </div>
                <div class="settings-password-modal__list-item">
                    <span class="settings-password-modal__list-icon">
                        <i class='bx bx-timer'></i>
                    </span>
                    <span>Once verified, the final delete confirmation stays available for 10 minutes.</span>
                </div>
            </div>

            <form id="deleteVerificationForm" action="{{ route('settings.account.verify-delete') }}" method="POST" class="settings-security-form settings-password-modal__form" aria-hidden="false">
                @csrf
                <input type="hidden" name="settings_form" value="delete_account_verify">

                <div id="deleteVerificationModalFeedback" class="settings-danger-zone__feedback settings-danger-zone__feedback--error {{ (!$deleteVerified && ($errors->has('delete_account') || $errors->has('current_password'))) ? '' : 'hidden' }}">
                    {{ !$deleteVerified ? ($errors->first('current_password') ?: $errors->first('delete_account')) : '' }}
                </div>

                <div class="settings-password-modal__field">
                    <label for="deleteVerificationModalCurrentPassword">Current Password</label>
                    <input id="deleteVerificationModalCurrentPassword" type="password" name="current_password" placeholder="Enter your current password" autocomplete="current-password" required>
                    <p class="settings-danger-zone__field-hint">We use this once to verify account ownership before enabling permanent account closure.</p>
                </div>

                <div class="settings-password-modal__actions">
                    <button type="button" class="settings-password-modal__secondary" data-delete-modal-close>Cancel</button>
                    <button type="submit" id="deleteVerificationSubmit" class="settings-password-modal__primary">
                        <i class='bx bx-check-shield text-lg'></i>
                        Verify Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="deleteConfirmationModal" class="settings-password-modal settings-password-modal--delete {{ $deleteConfirmationModalShouldOpen ? '' : 'hidden' }}" aria-hidden="{{ $deleteConfirmationModalShouldOpen ? 'false' : 'true' }}">
    <div class="settings-password-modal__backdrop" data-delete-confirmation-modal-close></div>

    <div class="settings-password-modal__card" role="dialog" aria-modal="true" aria-labelledby="deleteConfirmationModalTitle">
        <div class="settings-password-modal__visual">
            <button type="button" class="settings-password-modal__close" data-delete-confirmation-modal-close aria-label="Close delete confirmation view">
                <i class='bx bx-x text-2xl'></i>
            </button>
            <div class="settings-password-modal__art" aria-hidden="true">
                <span class="settings-password-modal__art-main">
                    <i class='bx bx-trash'></i>
                </span>
                <span class="settings-password-modal__art-badge">
                    <i class='bx bx-error-circle'></i>
                </span>
            </div>
        </div>

        <div class="settings-password-modal__body">
            <h3 id="deleteConfirmationModalTitle" class="settings-password-modal__title">Confirm permanent deletion</h3>
            <p class="settings-password-modal__copy">This secure mini view is the final checkpoint before your alumni portal account is permanently removed.</p>

            <div class="settings-password-modal__divider"></div>

            <h4 class="settings-password-modal__section-title">Before you continue</h4>
            <div class="settings-password-modal__list">
                <div class="settings-password-modal__list-item">
                    <span class="settings-password-modal__list-icon">
                        <i class='bx bx-timer'></i>
                    </span>
                    <span>This final confirmation stays active for 10 minutes after password verification.</span>
                </div>
                <div class="settings-password-modal__list-item">
                    <span class="settings-password-modal__list-icon">
                        <i class='bx bx-x-circle'></i>
                    </span>
                    <span>Type <strong>DELETE</strong> exactly as shown to permanently close your account.</span>
                </div>
            </div>

            <form id="deleteConfirmationForm" action="{{ route('settings.account.delete') }}" method="POST" class="settings-security-form settings-password-modal__form" aria-hidden="false">
                @csrf
                <input type="hidden" name="settings_form" value="delete_account_confirm">

                <div id="deleteConfirmationModalFeedback" class="settings-danger-zone__feedback settings-danger-zone__feedback--error {{ ($deleteVerified && ($errors->has('delete_account') || $errors->has('delete_confirmation'))) ? '' : 'hidden' }}">
                    {{ $deleteVerified ? ($errors->first('delete_confirmation') ?: $errors->first('delete_account')) : '' }}
                </div>

                <div class="settings-password-modal__field">
                    <label for="deleteAccountConfirmation">Type DELETE to confirm</label>
                    <input id="deleteAccountConfirmation" type="text" name="delete_confirmation" placeholder="DELETE" autocomplete="off" spellcheck="false" required>
                    <p class="settings-danger-zone__field-hint">This confirmation is case-sensitive and permanently closes the account once submitted.</p>
                </div>

                <div class="settings-password-modal__actions">
                    <button type="button" class="settings-password-modal__secondary" data-delete-confirmation-modal-close>Cancel</button>
                    <button type="submit" id="deleteConfirmationSubmit" class="settings-password-modal__primary settings-password-modal__primary--danger">
                        <i class='bx bx-x-circle text-lg'></i>
                        Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="passwordChangeModal" class="settings-password-modal {{ $passwordModalShouldOpen ? '' : 'hidden' }}" aria-hidden="{{ $passwordModalShouldOpen ? 'false' : 'true' }}">
    <div class="settings-password-modal__backdrop" data-password-modal-close></div>

    <div class="settings-password-modal__card" role="dialog" aria-modal="true" aria-labelledby="passwordModalTitle">
        <div class="settings-password-modal__visual">
            <button type="button" class="settings-password-modal__close" data-password-modal-close aria-label="Close password view">
                <i class='bx bx-x text-2xl'></i>
            </button>
            <div class="settings-password-modal__art" aria-hidden="true">
                <span class="settings-password-modal__art-main">
                    <i class='bx bx-key'></i>
                </span>
                <span class="settings-password-modal__art-badge">
                    <i class='bx bx-lock-alt'></i>
                </span>
            </div>
        </div>

        <div class="settings-password-modal__body">
            <h3 id="passwordModalTitle" class="settings-password-modal__title">Update your password</h3>
            <p class="settings-password-modal__copy">Keep your alumni account protected with one quick password update.</p>

            <div class="settings-password-modal__divider"></div>

            <h4 class="settings-password-modal__section-title">Before you save</h4>
            <div class="settings-password-modal__list">
                <div class="settings-password-modal__list-item">
                    <span class="settings-password-modal__list-icon">
                        <i class='bx bx-check-shield'></i>
                    </span>
                    <span>Enter your current password so we can verify it is really you.</span>
                </div>
                <div class="settings-password-modal__list-item">
                    <span class="settings-password-modal__list-icon">
                        <i class='bx bx-lock-open-alt'></i>
                    </span>
                    <span>Choose a new password with at least 8 characters for stronger account security.</span>
                </div>
                <div class="settings-password-modal__list-item">
                    <span class="settings-password-modal__list-icon">
                        <i class='bx bx-check-circle'></i>
                    </span>
                    <span>Confirm the new password before saving your updated sign-in details.</span>
                </div>
            </div>

            <form action="{{ route('settings.password.update') }}" method="POST" class="settings-security-form settings-security-form--password settings-password-modal__form">
                @csrf
                <input type="hidden" name="settings_form" value="password">

                <div class="settings-password-modal__field">
                    <label for="passwordModalCurrentPassword">Current Password</label>
                    <input id="passwordModalCurrentPassword" type="password" name="current_password" autocomplete="current-password" required>
                    @error('current_password')
                        <p class="settings-field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="settings-password-grid">
                    <div class="settings-password-modal__field">
                        <label for="passwordModalNewPassword">New Password</label>
                        <input id="passwordModalNewPassword" type="password" name="new_password" data-password-strength-target="settingsPasswordStrength" autocomplete="new-password" required>
                        @error('new_password')
                            <p class="settings-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="settings-password-modal__field">
                        <label for="passwordModalNewPasswordConfirmation">Confirm New Password</label>
                        <input id="passwordModalNewPasswordConfirmation" type="password" name="new_password_confirmation" autocomplete="new-password" required>
                        @error('new_password_confirmation')
                            <p class="settings-field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div id="settingsPasswordStrength" class="strength-meter settings-strength-meter">
                    <div class="strength-meter__track">
                        <div class="strength-meter__fill" data-password-strength-fill></div>
                    </div>
                    <div class="strength-meter__meta">
                        <span class="strength-meter__label" data-password-strength-label>Empty</span>
                        <span class="strength-meter__hint" data-password-strength-hint>Use at least 8 characters.</span>
                    </div>
                </div>

                <div class="settings-password-modal__actions">
                    <button type="button" class="settings-password-modal__secondary" data-password-modal-close>Cancel</button>
                    <button type="submit" class="settings-password-modal__primary">
                        Save New Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<script>
    (function () {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const guidedHighlightTimers = new WeakMap();

        const highlightElement = function (element) {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            element.classList.remove('settings-guided-highlight');
            void element.offsetWidth;
            element.classList.add('settings-guided-highlight');

            const existingTimer = guidedHighlightTimers.get(element);
            if (existingTimer) {
                window.clearTimeout(existingTimer);
            }

            const timer = window.setTimeout(function () {
                element.classList.remove('settings-guided-highlight');
                guidedHighlightTimers.delete(element);
            }, prefersReducedMotion ? 1200 : 2200);

            guidedHighlightTimers.set(element, timer);
        };

        const focusElement = function (element) {
            if (!(element instanceof HTMLElement) || typeof element.focus !== 'function') {
                return;
            }

            element.focus({ preventScroll: true });
        };

        const bindSettingsScrollAction = function () {
            const scrollButtons = document.querySelectorAll('[data-settings-scroll-target]');

            scrollButtons.forEach(function (button) {
                if (button.dataset.settingsScrollBound === 'true') {
                    return;
                }

                button.dataset.settingsScrollBound = 'true';
                button.addEventListener('click', function () {
                    const sectionId = button.getAttribute('data-settings-scroll-target');
                    const focusId = button.getAttribute('data-settings-focus-target');
                    const section = sectionId ? document.getElementById(sectionId) : null;

                    if (!section) {
                        return;
                    }

                    section.scrollIntoView({
                        behavior: prefersReducedMotion ? 'auto' : 'smooth',
                        block: 'start',
                    });

                    highlightElement(section);

                    const focusTarget = focusId ? document.getElementById(focusId) : section;

                    window.setTimeout(function () {
                        if (!(focusTarget instanceof HTMLElement)) {
                            return;
                        }

                        focusElement(focusTarget);
                        highlightElement(focusTarget);
                    }, prefersReducedMotion ? 0 : 280);
                });
            });
        };

        const bindSettingsModal = function (options) {
            const modal = document.getElementById(options.modalId);
            const modalCard = modal ? modal.querySelector('.settings-password-modal__card') : null;
            const openButtons = (options.openButtonIds || []).map(function (id) {
                return document.getElementById(id);
            }).filter(Boolean);
            const focusTarget = document.getElementById(options.focusTargetId);

            if (!modal || !openButtons.length) {
                return {
                    open: function () {},
                    close: function () {},
                    element: modal,
                };
            }

            const closeButtons = modal.querySelectorAll(options.closeSelector);
            let lastFocus = null;
            const setExpanded = function (value) {
                openButtons.forEach(function (button) {
                    button.setAttribute('aria-expanded', value);
                });
            };

            const openModal = function () {
                lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : openButtons[0];
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                setExpanded('true');
                document.body.style.overflow = 'hidden';

                window.setTimeout(function () {
                    if (modalCard instanceof HTMLElement) {
                        highlightElement(modalCard);
                    }

                    if (focusTarget) {
                        focusElement(focusTarget);
                        highlightElement(focusTarget);
                    }
                }, 30);
            };

            const closeModal = function () {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                setExpanded('false');
                document.body.style.overflow = '';

                if (lastFocus && typeof lastFocus.focus === 'function') {
                    lastFocus.focus();
                } else {
                    openButtons[0].focus();
                }
            };

            openButtons.forEach(function (button) {
                button.dataset.settingsModalFocusTarget = options.focusTargetId || '';
                button.addEventListener('click', openModal);
            });
            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            if (options.openOnLoad) {
                openModal();
            }

            return {
                open: openModal,
                close: closeModal,
                element: modal,
            };
        };

        const passwordModalController = bindSettingsModal({
            modalId: 'passwordChangeModal',
            openButtonIds: ['openPasswordModal'],
            focusTargetId: 'passwordModalCurrentPassword',
            closeSelector: '[data-password-modal-close]',
            openOnLoad: {{ $passwordModalShouldOpen ? 'true' : 'false' }},
        });

        const deleteVerificationModalController = bindSettingsModal({
            modalId: 'deleteVerificationModal',
            openButtonIds: ['startDeleteVerification'],
            focusTargetId: 'deleteVerificationModalCurrentPassword',
            closeSelector: '[data-delete-modal-close]',
            openOnLoad: {{ $deleteVerificationModalShouldOpen ? 'true' : 'false' }},
        });

        const deleteConfirmationModalController = bindSettingsModal({
            modalId: 'deleteConfirmationModal',
            openButtonIds: ['openDeleteConfirmation'],
            focusTargetId: 'deleteAccountConfirmation',
            closeSelector: '[data-delete-confirmation-modal-close]',
            openOnLoad: {{ $deleteConfirmationModalShouldOpen ? 'true' : 'false' }},
        });

        const deleteGuardLabel = document.getElementById('deleteGuardLabel');
        const deleteStatusBadge = document.getElementById('deleteStatusBadge');
        const deleteStatusBadgeIcon = document.getElementById('deleteStatusBadgeIcon');
        const deleteStatusBadgeText = document.getElementById('deleteStatusBadgeText');
        const deleteVerificationStep = document.getElementById('deleteVerificationStep');
        const deleteConfirmationStep = document.getElementById('deleteConfirmationStep');
        const deleteConfirmationStepSuccess = document.getElementById('deleteConfirmationStepSuccess');
        const deleteConfirmationStepError = document.getElementById('deleteConfirmationStepError');
        const deleteVerificationForm = document.getElementById('deleteVerificationForm');
        const deleteVerificationFeedback = document.getElementById('deleteVerificationModalFeedback');
        const deleteVerificationSubmit = document.getElementById('deleteVerificationSubmit');
        const deleteVerificationPasswordInput = document.getElementById('deleteVerificationModalCurrentPassword');
        const deleteConfirmationForm = document.getElementById('deleteConfirmationForm');
        const deleteConfirmationFeedback = document.getElementById('deleteConfirmationModalFeedback');
        const deleteConfirmationSubmit = document.getElementById('deleteConfirmationSubmit');
        const deleteConfirmationInput = document.getElementById('deleteAccountConfirmation');

        const extractAjaxMessage = function (data, fallbackMessage) {
            if (data && data.errors) {
                const firstError = Object.values(data.errors).flat()[0];
                if (firstError) {
                    return firstError;
                }
            }

            return data?.message || fallbackMessage;
        };

        const setDeleteFeedback = function (element, message = '') {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            element.textContent = message;
            element.classList.toggle('hidden', !message);
        };

        const setDeleteSubmitState = function (button, isBusy, defaultHtml) {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            if (!button.dataset.defaultHtml) {
                button.dataset.defaultHtml = defaultHtml || button.innerHTML;
            }

            button.disabled = isBusy;
            button.setAttribute('aria-busy', isBusy ? 'true' : 'false');
            button.innerHTML = isBusy
                ? "<span>Processing...</span>"
                : button.dataset.defaultHtml;
        };

        const setDeleteFlowVerifiedState = function (isVerified, successMessage = '') {
            if (deleteVerificationStep) {
                deleteVerificationStep.classList.toggle('hidden', isVerified);
            }

            if (deleteConfirmationStep) {
                deleteConfirmationStep.classList.toggle('hidden', !isVerified);
            }

            if (deleteGuardLabel) {
                deleteGuardLabel.textContent = isVerified ? 'Ready for final step' : 'Locked until verification';
            }

            if (deleteStatusBadge) {
                deleteStatusBadge.classList.toggle('is-ready', isVerified);
            }

            if (deleteStatusBadgeIcon) {
                deleteStatusBadgeIcon.classList.remove('bx-check-shield', 'bx-lock-alt');
                deleteStatusBadgeIcon.classList.add(isVerified ? 'bx-check-shield' : 'bx-lock-alt');
            }

            if (deleteStatusBadgeText) {
                deleteStatusBadgeText.textContent = isVerified ? 'Final confirmation unlocked' : 'Verification required';
            }

            if (deleteConfirmationStepSuccess) {
                deleteConfirmationStepSuccess.textContent = successMessage || 'Password verified. Final confirmation is now available for 10 minutes.';
                deleteConfirmationStepSuccess.classList.toggle('hidden', !isVerified);
            }

            if (!isVerified) {
                setDeleteFeedback(deleteConfirmationStepError, '');
            }
        };

        if (deleteVerificationForm instanceof HTMLFormElement) {
            deleteVerificationForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (!deleteVerificationForm.reportValidity()) {
                    return;
                }

                setDeleteFeedback(deleteVerificationFeedback, '');
                setDeleteSubmitState(deleteVerificationSubmit, true);

                try {
                    const response = await fetch(deleteVerificationForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(deleteVerificationForm),
                        credentials: 'same-origin',
                    });

                    const data = await response.json().catch(function () { return {}; });

                    if (!response.ok) {
                        const message = extractAjaxMessage(data, 'Unable to verify your password right now.');
                        setDeleteFeedback(deleteVerificationFeedback, message);

                        if (data?.redirect) {
                            window.location.assign(data.redirect);
                            return;
                        }

                        if (deleteVerificationPasswordInput instanceof HTMLElement) {
                            focusElement(deleteVerificationPasswordInput);
                        }

                        return;
                    }

                    deleteVerificationForm.reset();
                    setDeleteFeedback(deleteVerificationFeedback, '');
                    setDeleteFeedback(deleteConfirmationFeedback, '');
                    setDeleteFeedback(deleteConfirmationStepError, '');
                    setDeleteFlowVerifiedState(true, data?.message || '');
                    deleteVerificationModalController.close();
                    deleteConfirmationModalController.open();
                } catch (error) {
                    setDeleteFeedback(deleteVerificationFeedback, 'Unable to verify your password right now.');
                } finally {
                    setDeleteSubmitState(deleteVerificationSubmit, false);
                }
            });
        }

        if (deleteConfirmationForm instanceof HTMLFormElement) {
            deleteConfirmationForm.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (!deleteConfirmationForm.reportValidity()) {
                    return;
                }

                setDeleteFeedback(deleteConfirmationFeedback, '');
                setDeleteFeedback(deleteConfirmationStepError, '');
                setDeleteSubmitState(deleteConfirmationSubmit, true);

                try {
                    const response = await fetch(deleteConfirmationForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(deleteConfirmationForm),
                        credentials: 'same-origin',
                    });

                    const data = await response.json().catch(function () { return {}; });

                    if (!response.ok) {
                        const message = extractAjaxMessage(data, 'Unable to delete your account right now.');
                        setDeleteFeedback(deleteConfirmationFeedback, message);
                        setDeleteFeedback(deleteConfirmationStepError, message);

                        if (data?.redirect) {
                            window.location.assign(data.redirect);
                            return;
                        }

                        if (data?.verification_required) {
                            setDeleteFlowVerifiedState(false);
                            deleteConfirmationModalController.close();
                            deleteVerificationModalController.open();
                            return;
                        }

                        if (deleteConfirmationInput instanceof HTMLElement) {
                            focusElement(deleteConfirmationInput);
                        }

                        return;
                    }

                    if (data?.redirect) {
                        window.location.assign(data.redirect);
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    const message = 'Unable to delete your account right now.';
                    setDeleteFeedback(deleteConfirmationFeedback, message);
                    setDeleteFeedback(deleteConfirmationStepError, message);
                } finally {
                    setDeleteSubmitState(deleteConfirmationSubmit, false);
                }
            });
        }

        setDeleteFlowVerifiedState({{ $deleteVerified ? 'true' : 'false' }});

        bindSettingsScrollAction();
    })();

</script>
@endsection
