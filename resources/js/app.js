import './bootstrap';
import 'boxicons/css/boxicons.min.css';
import './login';
import './registration';
import './portal-quick-search';
import './dashboard-news-carousel';
import Chart from 'chart.js/auto';

window.Chart = Chart;
const ALUMNI_PAGE_READY_EVENT = 'alumni:page-ready';
const ADMIN_PAGE_READY_EVENT = 'admin:page-ready';
const PORTAL_PAGE_READY_EVENT = 'portal:page-ready';
const PORTAL_SCROLL_STORAGE_KEY = 'cmu-arlo-portal-scroll-positions';
const nativeWindowAlert = typeof window.alert === 'function'
    ? window.alert.bind(window)
    : null;

let portalNavigationController = null;
let portalNavigationToken = 0;
let portalShellNavigationBound = false;
let portalSidebarViewportCleanup = null;
let portalScrollTrackingBound = false;
let portalScrollSaveTimeoutId = null;
let toggleableRadiosBound = false;
let portalRadioValidationBound = false;
let scrollJumpTargetsBound = false;
let confirmedSubmitFormsBound = false;
let confirmableActionsBound = false;
let portalCardLinksBound = false;
let portalConfirmationState = null;
let liveNotificationPollingTimer = null;
let liveNotificationPollingEndpoint = '';
let liveNotificationPreviewTimer = null;
let liveNotificationPreviewLastAutoKey = '';
let liveNotificationPreviewLoginPending = null;

function submitConfirmationMessage(form, submitter = null) {
    const submitterMessage = submitter instanceof HTMLElement
        ? submitter.dataset.confirmSubmit
        : '';

    return submitterMessage || form?.dataset?.confirmSubmit || '';
}

function inferConfirmationTone(message = '', fallbackTone = '') {
    if (fallbackTone) {
        return fallbackTone;
    }

    return /\b(delete|archive|reject|remove|discard|sign out|logout)\b/i.test(message)
        ? 'danger'
        : 'default';
}

function confirmationDefaultsForTone(tone = 'default') {
    if (tone === 'danger') {
        return {
            icon: 'bx-error-circle',
            confirmLabel: 'Confirm',
            title: 'Confirm sensitive action',
        };
    }

    return {
        icon: 'bx-check-shield',
        confirmLabel: 'Continue',
        title: 'Confirm action',
    };
}

function ensurePortalConfirmationDialog() {
    if (portalConfirmationState?.dialog instanceof HTMLElement) {
        return portalConfirmationState;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'fixed inset-0 z-[120] hidden items-center justify-center px-4 py-6';
    wrapper.dataset.portalConfirmDialog = 'true';
    wrapper.setAttribute('aria-hidden', 'true');
    wrapper.innerHTML = `
        <div class="absolute inset-0 bg-slate-950/55 backdrop-blur-sm" data-portal-confirm-cancel></div>
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl ring-1 ring-slate-950/5" role="dialog" aria-modal="true" aria-labelledby="portalConfirmTitle" aria-describedby="portalConfirmMessage">
            <div class="flex items-start gap-4 border-b border-slate-100 bg-gradient-to-br from-white to-slate-50 px-5 py-5">
                <span data-portal-confirm-icon-shell class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-700">
                    <i data-portal-confirm-icon class='bx bx-check-shield text-2xl'></i>
                </span>
                <div class="min-w-0">
                    <h2 id="portalConfirmTitle" data-portal-confirm-title class="text-base font-bold text-slate-950">Confirm action</h2>
                    <p id="portalConfirmMessage" data-portal-confirm-message class="mt-1 text-sm leading-6 text-slate-600">Continue with this action?</p>
                </div>
            </div>
            <div class="flex flex-col-reverse gap-3 bg-white px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" data-portal-confirm-cancel class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    Cancel
                </button>
                <button type="button" data-portal-confirm-accept class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200">
                    <i data-portal-confirm-accept-icon class='bx bx-check text-lg'></i>
                    <span data-portal-confirm-label>Continue</span>
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(wrapper);

    const state = {
        dialog: wrapper,
        title: wrapper.querySelector('[data-portal-confirm-title]'),
        message: wrapper.querySelector('[data-portal-confirm-message]'),
        icon: wrapper.querySelector('[data-portal-confirm-icon]'),
        iconShell: wrapper.querySelector('[data-portal-confirm-icon-shell]'),
        acceptButton: wrapper.querySelector('[data-portal-confirm-accept]'),
        acceptIcon: wrapper.querySelector('[data-portal-confirm-accept-icon]'),
        acceptLabel: wrapper.querySelector('[data-portal-confirm-label]'),
        cancelControls: wrapper.querySelectorAll('[data-portal-confirm-cancel]'),
        lastFocused: null,
        resolver: null,
    };

    const close = (confirmed) => {
        if (!state.resolver) {
            return;
        }

        const resolve = state.resolver;
        state.resolver = null;
        wrapper.classList.add('hidden');
        wrapper.classList.remove('flex');
        wrapper.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');

        if (state.lastFocused instanceof HTMLElement && document.contains(state.lastFocused)) {
            state.lastFocused.focus({ preventScroll: true });
        }

        resolve(Boolean(confirmed));
    };

    state.acceptButton?.addEventListener('click', () => close(true));
    state.cancelControls.forEach((control) => {
        control.addEventListener('click', () => close(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || wrapper.classList.contains('hidden')) {
            return;
        }

        event.preventDefault();
        close(false);
    });

    portalConfirmationState = state;
    return state;
}

function showPortalConfirmation(options = {}) {
    const message = String(options.message || '').trim();
    if (!message) {
        return Promise.resolve(true);
    }

    const tone = inferConfirmationTone(message, options.tone || '');
    const defaults = confirmationDefaultsForTone(tone);
    const state = ensurePortalConfirmationDialog();

    if (state.resolver) {
        state.resolver(false);
        state.resolver = null;
    }

    state.lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    const danger = tone === 'danger';

    state.title.textContent = options.title || defaults.title;
    state.message.textContent = message;
    state.icon.className = `bx ${options.icon || defaults.icon} text-2xl`;
    state.acceptIcon.className = `bx ${danger ? 'bx-trash' : 'bx-check'} text-lg`;
    state.acceptLabel.textContent = options.confirmLabel || defaults.confirmLabel;

    state.iconShell.className = danger
        ? 'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-700'
        : 'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-700';
    state.acceptButton.className = danger
        ? 'inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-200'
        : 'inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200';

    state.dialog.classList.remove('hidden');
    state.dialog.classList.add('flex');
    state.dialog.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');

    window.requestAnimationFrame(() => {
        state.acceptButton?.focus({ preventScroll: true });
    });

    return new Promise((resolve) => {
        state.resolver = resolve;
    });
}

window.portalConfirm = showPortalConfirmation;

async function confirmSubmitAction(form, submitter = null) {
    const message = submitConfirmationMessage(form, submitter);
    if (!message) {
        return true;
    }

    const title = submitter instanceof HTMLElement
        ? (submitter.dataset.confirmTitle || form?.dataset?.confirmTitle)
        : form?.dataset?.confirmTitle;
    const confirmLabel = submitter instanceof HTMLElement
        ? (submitter.dataset.confirmLabel || form?.dataset?.confirmLabel)
        : form?.dataset?.confirmLabel;
    const tone = submitter instanceof HTMLElement
        ? (submitter.dataset.confirmTone || form?.dataset?.confirmTone)
        : form?.dataset?.confirmTone;

    return showPortalConfirmation({
        message,
        title,
        confirmLabel,
        tone,
    });
}

function getRadioFromPointerTarget(target) {
    if (!(target instanceof Element)) {
        return null;
    }

    if (target.matches('input[type="radio"]')) {
        return target;
    }

    const label = target.closest('label');
    if (!(label instanceof HTMLLabelElement)) {
        return null;
    }

    const control = label.control || label.querySelector('input[type="radio"]');
    return control instanceof HTMLInputElement && control.type === 'radio' ? control : null;
}

function setRadioToggleState(radio) {
    if (!(radio instanceof HTMLInputElement) || radio.type !== 'radio') {
        return;
    }

    radio.dataset.toggleWasChecked = radio.checked ? 'true' : 'false';
}

function dispatchRadioToggleEvents(radio) {
    radio.dispatchEvent(new Event('input', { bubbles: true }));
    radio.dispatchEvent(new Event('change', { bubbles: true }));
}

function initToggleableRadios() {
    if (toggleableRadiosBound) {
        return;
    }

    document.addEventListener('pointerdown', (event) => {
        const radio = getRadioFromPointerTarget(event.target);
        setRadioToggleState(radio);
    }, true);

    document.addEventListener('keydown', (event) => {
        const radio = event.target;

        if (!(radio instanceof HTMLInputElement) || radio.type !== 'radio' || radio.disabled) {
            return;
        }

        if (![' ', 'Spacebar'].includes(event.key)) {
            return;
        }

        if (!radio.checked) {
            return;
        }

        event.preventDefault();
        radio.checked = false;
        setRadioToggleState(radio);
        dispatchRadioToggleEvents(radio);
    }, true);

    document.addEventListener('click', (event) => {
        const radio = event.target;

        if (!(radio instanceof HTMLInputElement) || radio.type !== 'radio' || radio.disabled) {
            return;
        }

        if (radio.dataset.toggleWasChecked !== 'true') {
            setRadioToggleState(radio);
            return;
        }

        radio.checked = false;
        setRadioToggleState(radio);
        dispatchRadioToggleEvents(radio);
    }, true);

    document.addEventListener('change', (event) => {
        const radio = event.target;
        setRadioToggleState(radio);
    }, true);

    toggleableRadiosBound = true;
}

function getScrollJumpDestination(targetKey) {
    if (!targetKey) {
        return null;
    }

    return Array.from(document.querySelectorAll('[data-scroll-jump-destination]'))
        .find((element) => element.dataset.scrollJumpDestination === targetKey) || null;
}

function initScrollJumpTargets() {
    if (scrollJumpTargetsBound) {
        return;
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest('[data-scroll-jump-target]')
            : null;

        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        const destination = getScrollJumpDestination(trigger.dataset.scrollJumpTarget);
        if (!(destination instanceof HTMLElement)) {
            return;
        }

        if (!trigger.matches('a')) {
            event.preventDefault();
        }

        destination.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block: 'center',
        });

        destination.classList.remove('scroll-jump-focus');
        void destination.offsetWidth;
        destination.classList.add('scroll-jump-focus');

        if (!destination.hasAttribute('tabindex')) {
            destination.setAttribute('tabindex', '-1');
        }

        window.setTimeout(() => {
            destination.focus({ preventScroll: true });
        }, 120);

        window.setTimeout(() => {
            destination.classList.remove('scroll-jump-focus');
        }, 1400);
    });

    scrollJumpTargetsBound = true;
}

function initConfirmedSubmitForms() {
    if (confirmedSubmitFormsBound) {
        return;
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.matches('[data-admin-ajax-form]')) {
            return;
        }

        if (form.dataset.confirmBypassed === 'true') {
            delete form.dataset.confirmBypassed;
            return;
        }

        if (!submitConfirmationMessage(form, event.submitter)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const confirmed = await confirmSubmitAction(form, event.submitter);
        if (!confirmed) {
            return;
        }

        form.dataset.confirmBypassed = 'true';
        const submitter = event.submitter;
        const canResubmitWithSubmitter = submitter instanceof HTMLButtonElement
            || submitter instanceof HTMLInputElement;

        if (canResubmitWithSubmitter && typeof form.requestSubmit === 'function') {
            form.requestSubmit(event.submitter);
            return;
        }

        form.submit();
    }, true);

    confirmedSubmitFormsBound = true;
}

function confirmationOptionsFromElement(element) {
    const message = element.dataset.confirmAction
        || element.dataset.confirmNavigation
        || element.dataset.confirmClick
        || '';

    return {
        message,
        title: element.dataset.confirmTitle || '',
        confirmLabel: element.dataset.confirmLabel || '',
        tone: element.dataset.confirmTone || '',
    };
}

function navigateAfterConfirmedLink(link) {
    if (link.target && link.target !== '_self') {
        window.open(link.href, link.target);
        return;
    }

    if (isPortalShellActive() && isNavigableShellLink(link)) {
        navigatePortalShell(link.href, {
            preserveScroll: link.dataset.portalPreserveScroll === 'true',
            skipAnimation: link.dataset.portalSkipAnimation === 'true',
        });
        return;
    }

    window.location.assign(link.href);
}

function initConfirmableActions() {
    if (confirmableActionsBound) {
        return;
    }

    document.addEventListener('click', async (event) => {
        if (isModifiedNavigationEvent(event)) {
            return;
        }

        const trigger = event.target instanceof Element
            ? event.target.closest('[data-confirm-action], [data-confirm-navigation], [data-confirm-click]')
            : null;

        if (!(trigger instanceof HTMLElement)) {
            return;
        }

        if (trigger.dataset.confirmBypassed === 'true') {
            delete trigger.dataset.confirmBypassed;
            return;
        }

        const options = confirmationOptionsFromElement(trigger);
        if (!options.message) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const confirmed = await showPortalConfirmation(options);
        if (!confirmed) {
            return;
        }

        trigger.dataset.confirmBypassed = 'true';

        if (trigger instanceof HTMLAnchorElement && trigger.href) {
            navigateAfterConfirmedLink(trigger);
            window.setTimeout(() => {
                delete trigger.dataset.confirmBypassed;
            }, 0);
            return;
        }

        trigger.click();
        window.setTimeout(() => {
            delete trigger.dataset.confirmBypassed;
        }, 0);
    }, true);

    confirmableActionsBound = true;
}

function cardLinkTargetFromEventTarget(target) {
    if (!(target instanceof Element)) {
        return null;
    }

    const card = target.closest('[data-portal-card-link]');

    if (!(card instanceof HTMLElement)) {
        return null;
    }

    const interactiveTarget = target.closest('a, button, input, select, textarea, label, summary, [role="button"], [data-portal-card-ignore]');

    if (interactiveTarget instanceof HTMLElement && interactiveTarget !== card) {
        return null;
    }

    return card;
}

function openPortalCardLink(card, event = null) {
    const href = String(card?.dataset?.portalCardLink || '').trim();

    if (!href) {
        return;
    }

    if (event instanceof MouseEvent && (event.metaKey || event.ctrlKey)) {
        window.open(href, '_blank', 'noopener');
        return;
    }

    window.location.href = href;
}

function initPortalCardLinks() {
    if (portalCardLinksBound) {
        return;
    }

    document.addEventListener('click', (event) => {
        const card = cardLinkTargetFromEventTarget(event.target);

        if (!card || card instanceof HTMLAnchorElement) {
            return;
        }

        openPortalCardLink(card, event);
    });

    document.addEventListener('keydown', (event) => {
        if (!['Enter', ' '].includes(event.key)) {
            return;
        }

        const card = cardLinkTargetFromEventTarget(event.target);

        if (!card || card instanceof HTMLAnchorElement) {
            return;
        }

        event.preventDefault();
        openPortalCardLink(card, event);
    });

    portalCardLinksBound = true;
}

function liveNotificationBadgeLabel(count) {
    const value = Number(count) || 0;

    if (value <= 0) {
        return '';
    }

    return value > 99 ? '99+' : String(value);
}

function setLiveNotificationBadge(badge, count) {
    if (!(badge instanceof HTMLElement)) {
        return;
    }

    const label = liveNotificationBadgeLabel(count);
    badge.textContent = label;
    badge.hidden = !label;
    badge.classList.toggle('hidden', !label);
}

function setLiveNotificationIcon(icon, unreadCount) {
    if (!(icon instanceof HTMLElement)) {
        return;
    }

    const hasUnread = (Number(unreadCount) || 0) > 0;
    icon.classList.remove('bx-bell', 'bxs-bell-ring');
    icon.classList.add(hasUnread ? 'bxs-bell-ring' : 'bx-bell');
}

function liveNotificationPreviewKey(latest) {
    if (!latest || !latest.title) {
        return '';
    }

    return [
        latest.url || '',
        latest.title || '',
        latest.category || '',
        latest.time_label || '',
    ].join('|');
}

function shouldShowLiveNotificationPreviewOnLogin() {
    if (liveNotificationPreviewLoginPending === null) {
        liveNotificationPreviewLoginPending = document.body?.dataset?.liveNotificationPreviewOnLogin === 'true';
    }

    return liveNotificationPreviewLoginPending;
}

function applyLiveNotificationPreview(latest, unreadCount) {
    const autoPreviewKey = liveNotificationPreviewKey(latest);
    const hasUnread = (Number(unreadCount) || 0) > 0;
    const loginPreviewRequested = Boolean(autoPreviewKey) && shouldShowLiveNotificationPreviewOnLogin();
    const unreadPreviewRequested = Boolean(autoPreviewKey) && hasUnread && autoPreviewKey !== liveNotificationPreviewLastAutoKey;
    const canAutoShow = loginPreviewRequested || unreadPreviewRequested;

    if (liveNotificationPreviewTimer) {
        window.clearTimeout(liveNotificationPreviewTimer);
        liveNotificationPreviewTimer = null;
    }

    document.querySelectorAll('[data-live-notification-preview]').forEach((preview) => {
        if (!(preview instanceof HTMLElement)) {
            return;
        }

        const hasLatest = latest && latest.title;
        preview.classList.toggle('hidden', !hasLatest);
        preview.classList.toggle('has-latest', Boolean(hasLatest));
        preview.classList.toggle('is-visible', Boolean(canAutoShow));

        if (!hasLatest) {
            return;
        }

        if (preview instanceof HTMLAnchorElement && latest.url) {
            preview.href = latest.url;
        }

        const icon = preview.querySelector('[data-live-notification-preview-icon]');
        if (icon instanceof HTMLElement) {
            icon.className = `bx ${latest.icon || 'bx-bell'}`;
        }

        const category = preview.querySelector('[data-live-notification-preview-category]');
        if (category instanceof HTMLElement) {
            category.textContent = latest.category || 'Latest update';
        }

        const time = preview.querySelector('[data-live-notification-preview-time]');
        if (time instanceof HTMLElement) {
            time.textContent = latest.time_label || 'Just now';
        }

        const title = preview.querySelector('[data-live-notification-preview-title]');
        if (title instanceof HTMLElement) {
            title.textContent = latest.title || 'Open notifications';
        }

        const summary = preview.querySelector('[data-live-notification-preview-summary]');
        if (summary instanceof HTMLElement) {
            summary.textContent = latest.summary || 'See the newest update in your portal.';
        }
    });

    if (!canAutoShow) {
        return;
    }

    liveNotificationPreviewLastAutoKey = autoPreviewKey;
    if (loginPreviewRequested) {
        liveNotificationPreviewLoginPending = false;
        if (document.body?.dataset) {
            document.body.dataset.liveNotificationPreviewOnLogin = 'false';
        }
    }

    liveNotificationPreviewTimer = window.setTimeout(() => {
        document.querySelectorAll('[data-live-notification-preview]').forEach((preview) => {
            preview.classList.remove('is-visible');
        });
        liveNotificationPreviewTimer = null;
    }, 3000);
}

function applyLiveNotificationSummary(summary = {}) {
    const total = Number(summary.total) || 0;
    const unread = Number(summary.unread) || 0;
    const latest = summary.latest || null;
    const displayCount = unread > 0 ? unread : total;

    document.querySelectorAll('[data-live-notification-count]').forEach((element) => {
        element.textContent = String(displayCount);
    });

    document.querySelectorAll('[data-live-notification-badge]').forEach((badge) => {
        setLiveNotificationBadge(badge, unread);
    });

    document.querySelectorAll('[data-live-notification-icon]').forEach((icon) => {
        setLiveNotificationIcon(icon, unread);
    });

    document.querySelectorAll('[data-live-notification-copy]').forEach((element) => {
        element.textContent = unread > 0
            ? 'Review items that still need your attention.'
            : (total > 0 ? 'All updates have been reviewed.' : 'No announcements or updates yet.');
    });

    document.querySelectorAll('[data-live-notification-latest]').forEach((element) => {
        element.textContent = latest?.title
            ? `Latest: ${latest.title}`
            : 'Open updates to see announcements, jobs, and account activity.';
    });

    document.querySelectorAll('[data-live-notification-button]').forEach((button) => {
        const suffix = unread > 0 ? `${unread} unread ${unread === 1 ? 'update' : 'updates'}` : 'no unread updates';
        button.setAttribute('aria-label', `Open updates center, ${suffix}`);
        button.classList.toggle('has-unread', unread > 0);
    });

    applyLiveNotificationPreview(latest, unread);
}

function liveNotificationEndpoint() {
    const source = document.querySelector('[data-live-notification-endpoint]');
    return source instanceof HTMLElement ? source.dataset.liveNotificationEndpoint || '' : '';
}

async function refreshLiveNotificationSummary(endpoint) {
    if (!endpoint) {
        return;
    }

    try {
        const response = await fetch(endpoint, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            return;
        }

        applyLiveNotificationSummary(await response.json());
    } catch (error) {
        // Keep the current server-rendered count if live refresh is unavailable.
    }
}

function initLiveNotificationSummary() {
    const endpoint = liveNotificationEndpoint();

    if (liveNotificationPollingTimer) {
        window.clearInterval(liveNotificationPollingTimer);
        liveNotificationPollingTimer = null;
        liveNotificationPollingEndpoint = '';
    }

    if (!endpoint || !document.querySelector('[data-live-notification-count], [data-live-notification-badge], [data-live-notification-button]')) {
        return;
    }

    liveNotificationPollingEndpoint = endpoint;
    refreshLiveNotificationSummary(endpoint);
    liveNotificationPollingTimer = window.setInterval(() => {
        refreshLiveNotificationSummary(liveNotificationPollingEndpoint);
    }, 30000);
}

function isPortalContext() {
    return document.body.classList.contains('alumni-portal')
        || document.body.classList.contains('admin-portal');
}

function getPortalRadioGroupMembers(radio) {
    if (!(radio instanceof HTMLInputElement) || radio.type !== 'radio' || !radio.name) {
        return [];
    }

    const root = radio.form || document;
    return Array.from(root.querySelectorAll('input[type="radio"]')).filter((input) =>
        input instanceof HTMLInputElement
        && input.name === radio.name
        && !input.disabled
        && !input.closest('[hidden], .hidden')
    );
}

function getPortalRadioGroupAnchor(radio) {
    const form = radio.form || document.body;
    let candidate = radio.closest('label')?.parentElement || radio.parentElement;

    while (candidate instanceof HTMLElement && candidate !== form && candidate !== document.body) {
        const matchingRadios = Array.from(candidate.querySelectorAll('input[type="radio"]')).filter((input) =>
            input instanceof HTMLInputElement && input.name === radio.name
        );

        if (matchingRadios.length > 1) {
            return candidate;
        }

        candidate = candidate.parentElement;
    }

    return radio.closest('fieldset') || radio.parentElement || radio;
}

function getPortalRadioErrorScope(radio) {
    const anchor = getPortalRadioGroupAnchor(radio);
    return anchor.parentElement || anchor;
}

function portalRadioErrorKey(radio) {
    return `portal-radio:${radio.name}`;
}

function findPortalFieldError(scope, key) {
    if (!(scope instanceof HTMLElement) || !key) {
        return null;
    }

    return Array.from(scope.querySelectorAll('[data-portal-field-error-for]')).find((errorEl) =>
        errorEl.getAttribute('data-portal-field-error-for') === key
    ) || null;
}

function setPortalRadioError(radio, message = 'Please select an option.') {
    const anchor = getPortalRadioGroupAnchor(radio);
    const scope = getPortalRadioErrorScope(radio);
    const key = portalRadioErrorKey(radio);

    if (!(anchor instanceof HTMLElement) || !(scope instanceof HTMLElement)) {
        return;
    }

    let errorEl = findPortalFieldError(scope, key);
    if (!errorEl) {
        errorEl = document.createElement('p');
        errorEl.className = 'portal-field-error portal-radio-error';
        errorEl.dataset.portalFieldErrorFor = key;
        errorEl.setAttribute('role', 'alert');
        anchor.insertAdjacentElement('afterend', errorEl);
    }

    errorEl.textContent = message;
    errorEl.hidden = false;

    getPortalRadioGroupMembers(radio).forEach((input) => {
        input.setAttribute('aria-invalid', 'true');
    });
}

function clearPortalRadioError(radio) {
    const scope = getPortalRadioErrorScope(radio);
    const key = portalRadioErrorKey(radio);

    const errorEl = findPortalFieldError(scope, key);
    if (errorEl) {
        errorEl.textContent = '';
        errorEl.hidden = true;
    }

    getPortalRadioGroupMembers(radio).forEach((input) => {
        input.removeAttribute('aria-invalid');
    });
}

function isRequiredPortalRadioGroupMissing(radio) {
    const group = getPortalRadioGroupMembers(radio);
    if (!group.length || !group.some((input) => input.required)) {
        return false;
    }

    return !group.some((input) => input.checked);
}

function firstMissingPortalRadioGroup(form) {
    const seen = new Set();
    const radios = Array.from(form.querySelectorAll('input[type="radio"]')).filter((input) =>
        input instanceof HTMLInputElement
        && input.name
        && !input.disabled
        && !input.closest('[hidden], .hidden')
    );

    for (const radio of radios) {
        if (seen.has(radio.name)) {
            continue;
        }

        seen.add(radio.name);

        if (isRequiredPortalRadioGroupMissing(radio)) {
            return radio;
        }
    }

    return null;
}

function focusPortalRadioGroup(radio) {
    const anchor = getPortalRadioGroupAnchor(radio);
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (anchor instanceof HTMLElement) {
        anchor.scrollIntoView({
            behavior: prefersReducedMotion ? 'auto' : 'smooth',
            block: 'center',
        });
    }

    window.setTimeout(() => {
        try {
            radio.focus({ preventScroll: true });
        } catch (error) {}
    }, 80);
}

function initPortalRadioValidation() {
    if (portalRadioValidationBound) {
        return;
    }

    document.addEventListener('change', (event) => {
        if (!isPortalContext()) {
            return;
        }

        const radio = event.target;
        if (!(radio instanceof HTMLInputElement) || radio.type !== 'radio') {
            return;
        }

        if (isRequiredPortalRadioGroupMissing(radio)) {
            setPortalRadioError(radio);
        } else {
            clearPortalRadioError(radio);
        }
    }, true);

    document.addEventListener('submit', (event) => {
        if (!isPortalContext() || event.defaultPrevented) {
            return;
        }

        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const missingRadio = firstMissingPortalRadioGroup(form);
        if (!missingRadio) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        setPortalRadioError(missingRadio);
        focusPortalRadioGroup(missingRadio);
    }, true);

    portalRadioValidationBound = true;
}

function bindFlashMessage(message) {
    if (!(message instanceof HTMLElement) || message.dataset.flashBound === 'true') {
        return;
    }

    const dismissButton = message.querySelector('[data-flash-dismiss]');
    const removeMessage = () => {
        if (message.dataset.flashClosing === 'true') {
            return;
        }

        message.dataset.flashClosing = 'true';
        message.style.opacity = '0';
        message.style.transform = 'translateX(calc(100% + 1.5rem))';
        window.setTimeout(() => {
            message.remove();
        }, 460);
    };

    if (dismissButton) {
        dismissButton.addEventListener('click', removeMessage);
    }

    window.setTimeout(removeMessage, 5000);
    message.dataset.flashBound = 'true';
}

function initFlashMessages() {
    document.querySelectorAll('[data-flash-message]').forEach((message) => {
        bindFlashMessage(message);
    });
}

function inferFlashType(message = '') {
    const value = String(message).toLowerCase();

    if (/\b(success|saved|updated|created|sent|approved|verified|completed)\b/.test(value)) {
        return 'success';
    }

    if (/\b(warning|already|required|missing|please fill|please select|no changes)\b/.test(value)) {
        return 'warning';
    }

    if (/\b(error|failed|unable|invalid|denied|rejected|problem|try again)\b/.test(value)) {
        return 'error';
    }

    return 'info';
}

function flashMeta(type = 'info', title = '') {
    const normalizedType = ['success', 'warning', 'error', 'logout', 'info'].includes(type)
        ? type
        : 'info';

    const defaults = {
        success: {
            icon: 'bx-check-circle',
            label: 'Success',
            title: 'Success',
            role: 'status',
        },
        warning: {
            icon: 'bx-info-circle',
            label: 'Alert',
            title: 'Please check this',
            role: 'alert',
        },
        error: {
            icon: 'bx-error-circle',
            label: 'Notice',
            title: 'Something went wrong',
            role: 'alert',
        },
        logout: {
            icon: 'bx-error-circle',
            label: 'Notice',
            title: 'Notice',
            role: 'alert',
        },
        info: {
            icon: 'bx-info-circle',
            label: 'Notice',
            title: 'Notice',
            role: 'alert',
        },
    };

    return {
        type: normalizedType,
        ...defaults[normalizedType],
        title: title || defaults[normalizedType].title,
    };
}

function ensurePortalFlashStack() {
    if (!document.body) {
        return null;
    }

    let overlay = document.querySelector('.portal-flash-overlay');
    if (!(overlay instanceof HTMLElement)) {
        overlay = document.createElement('div');
        overlay.className = 'portal-flash-overlay fixed right-3 top-3 z-[100] sm:right-6 sm:top-6';
        document.body.appendChild(overlay);
    }

    let stack = overlay.querySelector('[data-flash-stack]');
    if (!(stack instanceof HTMLElement)) {
        stack = document.createElement('div');
        stack.className = 'flash-stack';
        stack.dataset.flashStack = 'true';
        stack.setAttribute('aria-live', 'polite');
        overlay.appendChild(stack);
    }

    return stack;
}

function showPortalFlash(message, options = {}) {
    const text = String(message || '').trim();
    if (!text) {
        return;
    }

    const type = options.type || inferFlashType(text);
    const meta = flashMeta(type, options.title);
    const stack = ensurePortalFlashStack();

    if (!(stack instanceof HTMLElement)) {
        nativeWindowAlert?.(text);
        return;
    }

    const flash = document.createElement('div');
    flash.className = `flash-message flash-message--${meta.type}`;
    flash.dataset.flashMessage = 'true';
    flash.setAttribute('role', meta.role);

    const accent = document.createElement('span');
    accent.className = 'flash-message__accent';
    accent.setAttribute('aria-hidden', 'true');

    const icon = document.createElement('div');
    icon.className = 'flash-message__icon';
    icon.innerHTML = `<i class='bx ${meta.icon}'></i>`;

    const content = document.createElement('div');
    content.className = 'flash-message__content';

    const eyebrow = document.createElement('div');
    eyebrow.className = 'flash-message__eyebrow';

    const label = document.createElement('span');
    label.className = 'flash-message__label';
    label.textContent = meta.label;
    eyebrow.appendChild(label);

    const title = document.createElement('p');
    title.className = 'flash-message__title';
    title.textContent = meta.title;

    const body = document.createElement('p');
    body.className = 'flash-message__body';
    body.textContent = text;

    content.append(eyebrow, title, body);

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'flash-message__close';
    close.dataset.flashDismiss = 'true';
    close.setAttribute('aria-label', 'Dismiss message');
    close.innerHTML = "<i class='bx bx-x'></i>";

    flash.append(accent, icon, content, close);
    stack.prepend(flash);
    bindFlashMessage(flash);

    Array.from(stack.querySelectorAll('[data-flash-message]')).slice(4).forEach((oldMessage) => {
        oldMessage.remove();
    });

    return flash;
}

function showNativeAlert(message, type = 'info', title = '') {
    showPortalFlash(message, { type, title });
}

window.showPortalFlash = showPortalFlash;
window.portalFlash = showPortalFlash;

if (!window.__portalStyledAlertBound) {
    window.alert = (message) => {
        showPortalFlash(message);
    };
    window.__portalStyledAlertBound = true;
}

function getFirstInvalidField(form) {
    return Array.from(form.elements).find((field) => (
        field instanceof HTMLElement
        && typeof field.checkValidity === 'function'
        && !field.disabled
        && !field.checkValidity()
    ));
}

function serializeManagedForm(form) {
    const formData = new FormData(form);
    const searchParams = new URLSearchParams();

    formData.forEach((value, key) => {
        if (key === '_token' || key === '_method' || value instanceof File) {
            return;
        }

        searchParams.append(key, String(value));
    });

    return searchParams.toString();
}

function setManagedFormSubmitState(form, submitting) {
    const submitButton = form.querySelector('[data-admin-ajax-submit-button]');
    const submitLabel = form.querySelector('[data-admin-ajax-submit-label]');

    if (!(submitButton instanceof HTMLButtonElement) || !(submitLabel instanceof HTMLElement)) {
        return;
    }

    if (!submitButton.dataset.defaultLabel) {
        submitButton.dataset.defaultLabel = submitLabel.textContent.trim();
    }

    submitButton.disabled = submitting;
    submitButton.classList.toggle('opacity-80', submitting);
    submitButton.classList.toggle('cursor-wait', submitting);
    submitLabel.textContent = submitting ? 'Saving...' : submitButton.dataset.defaultLabel;
}

function getManagedNoChangesMessage(form) {
    let message = form.querySelector('[data-admin-no-changes-message]');
    if (message instanceof HTMLElement) {
        return message;
    }

    const submitButton = form.querySelector('[data-admin-ajax-submit-button]');
    const actions = submitButton?.parentElement;
    if (!(actions instanceof HTMLElement)) {
        return null;
    }

    message = document.createElement('div');
    message.dataset.adminNoChangesMessage = 'true';
    message.setAttribute('role', 'alert');
    message.setAttribute('aria-hidden', 'true');
    message.setAttribute('tabindex', '-1');
    message.className = 'hidden min-w-[240px] flex-1 rounded-xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-left text-sm text-amber-900 shadow-sm ring-1 ring-amber-100/70 focus:outline-none focus:ring-2 focus:ring-amber-300';
    message.innerHTML = `
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                <i class='bx bx-info-circle text-lg'></i>
            </span>
            <div>
                <p class="font-semibold text-amber-950">No changes found.</p>
                <p class="mt-1 leading-6">Please edit at least one field before saving this record.</p>
            </div>
        </div>
    `;

    if (!actions.matches('form')) {
        actions.classList.add('flex-wrap', 'items-start');
        actions.insertBefore(message, actions.firstElementChild);
    } else {
        actions.insertBefore(message, submitButton);
    }

    return message;
}

function setManagedNoChangesMessageVisible(form, visible, shouldFocus = false) {
    const message = getManagedNoChangesMessage(form);
    if (!(message instanceof HTMLElement)) {
        return;
    }

    message.classList.toggle('hidden', !visible);
    message.setAttribute('aria-hidden', visible ? 'false' : 'true');

    if (visible && shouldFocus) {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.requestAnimationFrame(() => {
            message.scrollIntoView({
                behavior: prefersReducedMotion ? 'auto' : 'smooth',
                block: 'center',
            });
            message.focus({ preventScroll: true });
        });
    }
}

function applyAdminAjaxPayload(form, data) {
    if (form.dataset.adminAjaxKind === 'alumni' && typeof data?.displayName === 'string') {
        const recordName = document.querySelector('[data-admin-alumni-record-name]');
        if (recordName) {
            recordName.textContent = `Record: ${data.displayName}`;
        }
    }

    if (form.dataset.adminAjaxKind === 'feedback' && data?.feedback) {
        const feedback = data.feedback;
        const updates = [
            ['[data-admin-feedback-last-updated]', feedback.updatedAtLabel],
            ['[data-admin-feedback-current-status]', feedback.statusLabel],
            ['[data-admin-feedback-current-priority]', feedback.priorityLabel],
            ['[data-admin-feedback-assigned-to]', feedback.assignedToLabel],
            ['[data-admin-feedback-resolved-at]', feedback.resolvedAtLabel],
        ];

        updates.forEach(([selector, value]) => {
            const element = document.querySelector(selector);
            if (element && typeof value === 'string') {
                element.textContent = value;
            }
        });
    }
}

async function handleAdminAjaxFormSubmit(event) {
    event.preventDefault();

    const form = event.currentTarget;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const invalidField = getFirstInvalidField(form);
    if (invalidField) {
        invalidField.focus();
        showNativeAlert(
            invalidField.validity?.valueMissing
                ? 'Please fill out all required fields.'
                : (invalidField.validationMessage || 'Please fill out all required fields.'),
            'warning'
        );
        return;
    }

    const currentSnapshot = serializeManagedForm(form);
    if (currentSnapshot === form.dataset.initialSnapshot) {
        setManagedNoChangesMessageVisible(form, true, true);
        return;
    }

    if (!await confirmSubmitAction(form, event.submitter)) {
        return;
    }

    setManagedNoChangesMessageVisible(form, false);
    setManagedFormSubmitState(form, true);

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const message = data?.errors
                ? Object.values(data.errors).flat()[0]
                : data?.message;

            showNativeAlert(message || 'Unable to save your changes right now.', 'error');
            return;
        }

        form.dataset.initialSnapshot = serializeManagedForm(form);
        applyAdminAjaxPayload(form, data);
        setManagedNoChangesMessageVisible(form, false);
        showNativeAlert(data?.message || 'Changes saved successfully.', 'success');
        window.setTimeout(() => {
            window.location.reload();
        }, 900);
    } catch (error) {
        showNativeAlert('Unable to save your changes right now.', 'error');
    } finally {
        setManagedFormSubmitState(form, false);
    }
}

function initAjaxAdminForms(root = document) {
    root.querySelectorAll('[data-admin-ajax-form]').forEach((form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.adminAjaxBound !== 'true') {
            form.addEventListener('submit', handleAdminAjaxFormSubmit);
            form.addEventListener('input', () => {
                if (serializeManagedForm(form) !== form.dataset.initialSnapshot) {
                    setManagedNoChangesMessageVisible(form, false);
                }
            });
            form.addEventListener('change', () => {
                if (serializeManagedForm(form) !== form.dataset.initialSnapshot) {
                    setManagedNoChangesMessageVisible(form, false);
                }
            });
            form.dataset.adminAjaxBound = 'true';
        }

        form.dataset.initialSnapshot = serializeManagedForm(form);
    });
}

function getPasswordStrengthMeta(password) {
    let score = 0;
    const checks = [
        password.length >= 8,
        /[A-Z]/.test(password),
        /[a-z]/.test(password),
        /[0-9]/.test(password),
        /[^A-Za-z0-9]/.test(password),
    ];

    checks.forEach((passed) => {
        if (passed) score += 1;
    });

    if (!password) {
        return { width: 0, label: 'Empty', hint: 'Use at least 8 characters.', color: '#cbd5e1' };
    }
    if (score <= 2) {
        return { width: 28, label: 'Weak', hint: 'Add uppercase, numbers, or symbols.', color: '#ef4444' };
    }
    if (score === 3) {
        return { width: 56, label: 'Fair', hint: 'Good start. Mix more character types.', color: '#ffc600' };
    }
    if (score === 4) {
        return { width: 78, label: 'Strong', hint: 'Nice. One more unique element makes it stronger.', color: '#02681e' };
    }

    return { width: 100, label: 'Very strong', hint: 'This password is well balanced.', color: '#00491e' };
}

function initPasswordStrengthMeters() {
    document.querySelectorAll('[data-password-strength-target]').forEach((input) => {
        const meterId = input.getAttribute('data-password-strength-target');
        const meter = document.getElementById(meterId);
        if (!meter) {
            return;
        }

        const fill = meter.querySelector('[data-password-strength-fill]');
        const label = meter.querySelector('[data-password-strength-label]');
        const hint = meter.querySelector('[data-password-strength-hint]');
        if (!fill || !label || !hint) {
            return;
        }

        const updateMeter = () => {
            const meta = getPasswordStrengthMeta(input.value || '');
            fill.style.width = `${meta.width}%`;
            fill.style.backgroundColor = meta.color;
            label.textContent = meta.label;
            hint.textContent = meta.hint;
        };

        updateMeter();
        input.addEventListener('input', updateMeter);
        input.addEventListener('focus', updateMeter);
    });
}

function initPersistentSidebar() {
    const toggleButton = document.getElementById('sidebarToggle');
    const mobileToggleButton = document.getElementById('mobileSidebarToggle');
    const mobileOverlay = document.getElementById('mobileSidebarOverlay');
    const storageKey = document.body.dataset.sidebarStorageKey;
    const isAlumniPortal = document.body.classList.contains('alumni-portal');
    const isAdminPortal = document.body.classList.contains('admin-portal');
    const portalLinkSelector = isAdminPortal ? '.admin-sidebar a' : '.alumni-sidebar a';

    if (!storageKey) {
        return;
    }

    const isDesktopViewport = () => window.matchMedia('(min-width: 1024px)').matches;
    const desktopViewport = window.matchMedia('(min-width: 1024px)');

    const applyState = (collapsed) => {
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        if (toggleButton) {
            toggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            toggleButton.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggleButton.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            toggleButton.dataset.tooltip = collapsed ? 'Show sidebar' : 'Hide sidebar';

            const toggleIcon = toggleButton.querySelector('i');
            if (toggleIcon) {
                toggleIcon.className = `bx ${collapsed ? 'bx-menu' : 'bx-menu-alt-left'} text-xl`;
            }
        }
    };

    const setMobileOpen = (isOpen) => {
        document.body.classList.toggle('mobile-sidebar-open', isOpen);

        if (mobileToggleButton) {
            mobileToggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    };

    if (typeof portalSidebarViewportCleanup === 'function') {
        portalSidebarViewportCleanup();
        portalSidebarViewportCleanup = null;
    }

    if (isAlumniPortal || isAdminPortal) {
        const syncPortalSidebarState = () => {
            document.body.classList.remove('sidebar-hover-open');

            if (isDesktopViewport()) {
                const savedState = window.localStorage.getItem(storageKey) === 'true';
                applyState(savedState);
                setMobileOpen(false);
                return;
            }

            applyState(false);
            setMobileOpen(false);
        };

        syncPortalSidebarState();

        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                if (!isDesktopViewport()) {
                    setMobileOpen(false);
                    return;
                }

                const collapsed = !document.body.classList.contains('sidebar-collapsed');
                applyState(collapsed);
                window.localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
            });
        }

        if (mobileToggleButton) {
            mobileToggleButton.addEventListener('click', () => {
                setMobileOpen(!document.body.classList.contains('mobile-sidebar-open'));
            });
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', () => {
                setMobileOpen(false);
            });
        }

        document.querySelectorAll(portalLinkSelector).forEach((link) => {
            link.addEventListener('click', () => {
                if (!isDesktopViewport()) {
                    setMobileOpen(false);
                }
            });
        });

        const handleViewportChange = () => {
            syncPortalSidebarState();
        };

        if (typeof desktopViewport.addEventListener === 'function') {
            desktopViewport.addEventListener('change', handleViewportChange);
            portalSidebarViewportCleanup = () => {
                desktopViewport.removeEventListener('change', handleViewportChange);
            };
        } else if (typeof desktopViewport.addListener === 'function') {
            desktopViewport.addListener(handleViewportChange);
            portalSidebarViewportCleanup = () => {
                desktopViewport.removeListener(handleViewportChange);
            };
        }

        return;
    }

    if (isDesktopViewport()) {
        const savedState = window.localStorage.getItem(storageKey) === 'true';
        applyState(savedState);
        setMobileOpen(false);
    } else {
        applyState(false);
        setMobileOpen(false);
    }

    if (toggleButton) {
        toggleButton.addEventListener('click', () => {
            if (!isDesktopViewport()) {
                setMobileOpen(false);
                return;
            }

            const collapsed = !document.body.classList.contains('sidebar-collapsed');
            applyState(collapsed);
            window.localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
        });
    }

    if (mobileToggleButton) {
        mobileToggleButton.addEventListener('click', () => {
            setMobileOpen(!document.body.classList.contains('mobile-sidebar-open'));
        });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', () => {
            setMobileOpen(false);
        });
    }

    document.querySelectorAll('.alumni-sidebar a').forEach((link) => {
        link.addEventListener('click', () => {
            if (!isDesktopViewport()) {
                setMobileOpen(false);
            }
        });
    });
}

function dispatchPortalPageReady(mode, detail = {}) {
    if (!mode) {
        return;
    }

    const payload = { ...detail, mode };
    document.dispatchEvent(new CustomEvent(PORTAL_PAGE_READY_EVENT, { detail: payload }));

    if (mode === 'alumni') {
        document.dispatchEvent(new CustomEvent(ALUMNI_PAGE_READY_EVENT, { detail: payload }));
    }

    if (mode === 'admin') {
        document.dispatchEvent(new CustomEvent(ADMIN_PAGE_READY_EVENT, { detail: payload }));
    }
}

function initSharedPortalUi() {
    initFlashMessages();
    initPasswordStrengthMeters();
    initToggleableRadios();
    initPortalRadioValidation();
    initScrollJumpTargets();
    initConfirmedSubmitForms();
    initConfirmableActions();
    initPortalCardLinks();
    initPersistentSidebar();
    initPortalScrollTracking();
    initLiveNotificationSummary();
}

function getActivePortalMode(doc = document) {
    const hasShell = Boolean(doc.querySelector('.dashboard-shell'));

    if (!hasShell) {
        return null;
    }

    if (doc.querySelector('.alumni-sidebar') && !doc.querySelector('.admin-sidebar')) {
        return 'alumni';
    }

    if (doc.querySelector('.admin-sidebar')) {
        return 'admin';
    }

    return null;
}

function getPortalSelectors(mode) {
    return {
        sidebar: mode === 'admin' ? '.admin-sidebar' : '.alumni-sidebar',
        containers: mode === 'admin'
            ? '.admin-sidebar, .dashboard-shell'
            : '.alumni-sidebar, .dashboard-shell',
    };
}

function isPortalShellActive(mode = null, doc = document) {
    const activeMode = getActivePortalMode(doc);

    if (!activeMode) {
        return false;
    }

    return mode ? activeMode === mode : true;
}

function isNavigableShellLink(link) {
    if (!link || link.dataset.nativeNav !== undefined || link.hasAttribute('download')) {
        return false;
    }

    const href = link.getAttribute('href');

    if (!href || href.startsWith('#')) {
        return false;
    }

    if (link.target && link.target !== '_self') {
        return false;
    }

    const url = new URL(href, window.location.href);

    if (!['http:', 'https:'].includes(url.protocol) || url.origin !== window.location.origin) {
        return false;
    }

    return true;
}

function isModifiedNavigationEvent(event) {
    return event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey;
}

function copyScriptAttributes(sourceScript, targetScript) {
    Array.from(sourceScript.attributes).forEach((attribute) => {
        targetScript.setAttribute(attribute.name, attribute.value);
    });
}

function rerunInlineScripts(root) {
    if (!root) {
        return;
    }

    root.querySelectorAll('script').forEach((script) => {
        const type = (script.getAttribute('type') || '').trim().toLowerCase();
        const executableTypes = new Set(['', 'text/javascript', 'application/javascript', 'module']);

        if (!executableTypes.has(type)) {
            return;
        }

        const replacement = document.createElement('script');
        copyScriptAttributes(script, replacement);
        replacement.textContent = script.textContent;
        script.replaceWith(replacement);
    });
}

function setShellLoadingState(isLoading) {
    const mode = getActivePortalMode();
    const sidebarSelector = mode ? getPortalSelectors(mode).sidebar : '.alumni-sidebar, .admin-sidebar';

    document.querySelector('.dashboard-shell')?.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    document.querySelector(sidebarSelector)?.setAttribute('aria-busy', isLoading ? 'true' : 'false');
}

function normalizePortalScrollUrl(url) {
    try {
        return new URL(url, window.location.href).toString();
    } catch (error) {
        return String(url || window.location.href || '');
    }
}

function getPortalScrollState() {
    try {
        const rawState = window.sessionStorage.getItem(PORTAL_SCROLL_STORAGE_KEY);
        if (!rawState) {
            return {};
        }

        const parsedState = JSON.parse(rawState);
        return parsedState && typeof parsedState === 'object' ? parsedState : {};
    } catch (error) {
        return {};
    }
}

function setPortalScrollState(state) {
    try {
        const trimmedEntries = Object.entries(state || {})
            .sort(([, left], [, right]) => Number(right?.storedAt || 0) - Number(left?.storedAt || 0))
            .slice(0, 40);

        window.sessionStorage.setItem(
            PORTAL_SCROLL_STORAGE_KEY,
            JSON.stringify(Object.fromEntries(trimmedEntries)),
        );
    } catch (error) {
        // Ignore storage access issues.
    }
}

function rememberPortalScrollPosition(url = window.location.href, scrollTop = window.scrollY) {
    const normalizedUrl = normalizePortalScrollUrl(url);
    if (normalizedUrl === '') {
        return;
    }

    const nextState = getPortalScrollState();
    nextState[normalizedUrl] = {
        top: Math.max(0, Math.round(Number(scrollTop) || 0)),
        storedAt: Date.now(),
    };

    setPortalScrollState(nextState);
}

function restorePortalScrollPosition(url = window.location.href) {
    const normalizedUrl = normalizePortalScrollUrl(url);
    const storedTop = Number(getPortalScrollState()?.[normalizedUrl]?.top);

    if (!Number.isFinite(storedTop)) {
        return false;
    }

    window.requestAnimationFrame(() => {
        window.scrollTo({ top: storedTop, left: 0, behavior: 'auto' });
    });

    return true;
}

function initPortalScrollTracking() {
    if (portalScrollTrackingBound) {
        return;
    }

    const queueScrollSave = () => {
        if (!isPortalShellActive()) {
            return;
        }

        if (portalScrollSaveTimeoutId) {
            window.clearTimeout(portalScrollSaveTimeoutId);
        }

        portalScrollSaveTimeoutId = window.setTimeout(() => {
            rememberPortalScrollPosition(window.location.href, window.scrollY);
            portalScrollSaveTimeoutId = null;
        }, 120);
    };

    window.addEventListener('scroll', queueScrollSave, { passive: true });
    window.addEventListener('pagehide', () => {
        if (!isPortalShellActive()) {
            return;
        }

        if (portalScrollSaveTimeoutId) {
            window.clearTimeout(portalScrollSaveTimeoutId);
            portalScrollSaveTimeoutId = null;
        }

        rememberPortalScrollPosition(window.location.href, window.scrollY);
    });

    portalScrollTrackingBound = true;
}

async function navigatePortalShell(targetUrl, options = {}) {
    const destination = new URL(targetUrl, window.location.href);
    const mode = getActivePortalMode();

    if (!mode) {
        window.location.assign(destination.toString());
        return;
    }

    rememberPortalScrollPosition(window.location.href, window.scrollY);
    document.body.classList.remove('mobile-sidebar-open');
    const currentSidebar = mode ? document.querySelector(getPortalSelectors(mode).sidebar) : null;
    const currentSidebarScrollTop = currentSidebar instanceof HTMLElement
        ? currentSidebar.scrollTop
        : 0;

    portalNavigationToken += 1;
    const requestToken = portalNavigationToken;

    if (portalNavigationController) {
        portalNavigationController.abort();
    }

    portalNavigationController = new AbortController();
    setShellLoadingState(true);

    try {
        const response = await fetch(destination.toString(), {
            headers: {
                'Accept': 'text/html, application/xhtml+xml',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: portalNavigationController.signal,
        });

        const contentType = (response.headers.get('content-type') || '').toLowerCase();
        if (!response.ok || !contentType.includes('text/html')) {
            throw new Error(`Shell navigation failed for ${destination} with status ${response.status}.`);
        }

        const html = await response.text();
        const parser = new DOMParser();
        const nextDocument = parser.parseFromString(html, 'text/html');
        const selectors = getPortalSelectors(mode);
        const nextSidebar = nextDocument.querySelector(selectors.sidebar);
        const nextShell = nextDocument.querySelector('.dashboard-shell');

        if (!nextSidebar || !nextShell) {
            window.location.assign(response.url || destination.toString());
            return;
        }

        if (typeof window.__portalPageCleanup === 'function') {
            try {
                window.__portalPageCleanup();
            } catch (cleanupError) {
                console.error(cleanupError);
            } finally {
                window.__portalPageCleanup = null;
            }
        }

        document.querySelector(selectors.sidebar)?.replaceWith(nextSidebar);
        document.querySelector('.dashboard-shell')?.replaceWith(nextShell);
        if (currentSidebarScrollTop > 0) {
            window.requestAnimationFrame(() => {
                nextSidebar.scrollTop = currentSidebarScrollTop;
            });
        }

        if (nextDocument.title) {
            document.title = nextDocument.title;
        }

        rerunInlineScripts(nextSidebar);
        rerunInlineScripts(nextShell);
        initSharedPortalUi();

        const finalUrl = response.url || destination.toString();
        if (!options.historyNavigation) {
            window.history.pushState({ alumniShell: true }, '', finalUrl);
        }

        const restoredScroll = options.preserveScroll
            ? restorePortalScrollPosition(finalUrl)
            : false;

        if (!restoredScroll) {
            window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
        }

        dispatchPortalPageReady(mode, {
            url: finalUrl,
            navigation: true,
        });
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error(error);
        window.location.assign(destination.toString());
    } finally {
        if (requestToken === portalNavigationToken) {
            portalNavigationController = null;
            setShellLoadingState(false);
        }
    }
}

function initPortalShellNavigation() {
    if (portalShellNavigationBound || !isPortalShellActive()) {
        return;
    }

    portalShellNavigationBound = true;

    document.addEventListener('click', (event) => {
        if (!isPortalShellActive() || isModifiedNavigationEvent(event)) {
            return;
        }

        const mode = getActivePortalMode();
        if (!mode) {
            return;
        }

        const link = event.target.closest('a');
        if (!link || !link.closest(getPortalSelectors(mode).containers) || !isNavigableShellLink(link)) {
            return;
        }

        const destination = new URL(link.href, window.location.href);
        const current = new URL(window.location.href);

        if (destination.pathname === current.pathname && destination.search === current.search && destination.hash === current.hash) {
            return;
        }

        event.preventDefault();

        const isSidebarLink = Boolean(link.closest(getPortalSelectors(mode).sidebar));
        const preserveScroll = link.dataset.portalPreserveScroll === 'true' || isSidebarLink;
        if (preserveScroll) {
            rememberPortalScrollPosition(destination.toString(), window.scrollY);
        }

        navigatePortalShell(destination.toString(), {
            preserveScroll,
            skipAnimation: link.dataset.portalSkipAnimation === 'true',
        });
    });

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented || !isPortalShellActive()) {
            return;
        }

        const mode = getActivePortalMode();
        if (!mode) {
            return;
        }

        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'get'
            || form.dataset.nativeNav !== undefined
            || form.matches('[data-dashboard-filter-form]')
            || form.matches('[data-admin-analytics-filter-form]')) {
            return;
        }

        if (!form.closest(getPortalSelectors(mode).containers)) {
            return;
        }

        event.preventDefault();

        const action = form.getAttribute('action') || window.location.href;
        const destination = new URL(action, window.location.href);
        const formData = new FormData(form);
        const searchParams = new URLSearchParams();

        formData.forEach((value, key) => {
            if (typeof value !== 'string' || value.trim() === '') {
                return;
            }

            searchParams.append(key, value);
        });

        destination.search = searchParams.toString();

        const preserveScroll = form.dataset.portalPreserveScroll === 'true';
        if (preserveScroll) {
            rememberPortalScrollPosition(destination.toString(), window.scrollY);
        }

        navigatePortalShell(destination.toString(), {
            preserveScroll,
            skipAnimation: form.dataset.portalSkipAnimation === 'true',
        });
    });

    window.addEventListener('popstate', () => {
        if (!isPortalShellActive()) {
            return;
        }

        navigatePortalShell(window.location.href, {
            historyNavigation: true,
            preserveScroll: true,
            skipAnimation: true,
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSharedPortalUi();
    initPortalShellNavigation();

    document.querySelectorAll('[data-profile-reminder]').forEach((dialog) => {
        if (!(dialog instanceof HTMLDialogElement) || dialog.open) {
            return;
        }

        dialog.showModal();
        dialog.querySelector('[data-profile-reminder-close]')?.focus();
        dialog.querySelectorAll('[data-profile-reminder-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });

    const mode = getActivePortalMode();
    if (mode) {
        dispatchPortalPageReady(mode, {
            url: window.location.href,
            navigation: false,
        });
    }
});

document.addEventListener(ADMIN_PAGE_READY_EVENT, () => {
    window.requestAnimationFrame(() => {
        initAjaxAdminForms(document);
    });
});
