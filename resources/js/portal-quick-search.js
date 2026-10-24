const searchRoots = '[data-portal-quick-search]';

function initPortalQuickSearch() {
    document.querySelectorAll(searchRoots).forEach((root, rootIndex) => {
        if (root.dataset.quickSearchReady === 'true') {
            return;
        }

        const input = root.querySelector('[data-portal-quick-search-input]');
        const results = root.querySelector('[data-portal-quick-search-results]');
        const options = Array.from(root.querySelectorAll('[data-portal-quick-search-option]'));
        const emptyState = root.querySelector('[data-portal-quick-search-empty]');

        if (!(input instanceof HTMLInputElement) || !(results instanceof HTMLElement)) {
            return;
        }

        root.dataset.quickSearchReady = 'true';
        let activeIndex = -1;
        options.forEach((option, optionIndex) => {
            option.id ||= `portal-quick-search-${rootIndex}-${optionIndex}`;
        });

        const close = () => {
            results.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            options.forEach((option) => {
                option.classList.remove('is-active');
                option.setAttribute('aria-selected', 'false');
            });
            activeIndex = -1;
        };

        const render = () => {
            const terms = input.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
            const visibleOptions = [];

            options.forEach((option) => {
                const searchableText = (option.dataset.searchTerms || '').toLocaleLowerCase();
                const matches = terms.length === 0
                    ? visibleOptions.length < 5
                    : terms.every((term) => searchableText.includes(term));

                option.hidden = !matches;
                option.classList.remove('is-active');
                option.setAttribute('aria-selected', 'false');
                if (matches) {
                    visibleOptions.push(option);
                }
            });

            activeIndex = -1;
            if (emptyState) {
                emptyState.hidden = visibleOptions.length > 0;
            }
            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            input.removeAttribute('aria-activedescendant');
        };

        const selectActive = (nextIndex) => {
            const visibleOptions = options.filter((option) => !option.hidden);
            if (visibleOptions.length === 0) {
                activeIndex = -1;
                return;
            }

            activeIndex = (nextIndex + visibleOptions.length) % visibleOptions.length;
            visibleOptions.forEach((option, index) => {
                const active = index === activeIndex;
                option.classList.toggle('is-active', active);
                option.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            input.setAttribute('aria-activedescendant', visibleOptions[activeIndex].id);
        };

        input.addEventListener('focus', render);
        input.addEventListener('input', render);
        input.addEventListener('keydown', (event) => {
            const visibleOptions = options.filter((option) => !option.hidden);

            if (event.key === 'ArrowDown' && !results.hidden) {
                event.preventDefault();
                selectActive(activeIndex + 1);
            } else if (event.key === 'ArrowUp' && !results.hidden) {
                event.preventDefault();
                selectActive(activeIndex < 0 ? visibleOptions.length - 1 : activeIndex - 1);
            } else if (event.key === 'Enter' && !results.hidden && visibleOptions.length > 0) {
                event.preventDefault();
                const option = visibleOptions[activeIndex >= 0 ? activeIndex : 0];
                window.location.assign(option.href);
            } else if (event.key === 'Escape') {
                close();
            }
        });

        root.addEventListener('click', (event) => {
            const option = event.target instanceof Element
                ? event.target.closest('[data-portal-quick-search-option]')
                : null;

            if (!(option instanceof HTMLAnchorElement)) {
                return;
            }

            if (event instanceof MouseEvent && (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey)) {
                close();
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            window.location.assign(option.href);
        });

        document.addEventListener('pointerdown', (event) => {
            if (!root.contains(event.target)) {
                close();
            }
        });
    });
}

document.addEventListener('keydown', (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 'k') {
        return;
    }

    const input = document.querySelector('[data-portal-quick-search-input]');
    if (input instanceof HTMLInputElement) {
        event.preventDefault();
        input.focus();
        input.select();
    }
});

document.addEventListener('change', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const toggle = event.target.closest('[data-notification-column-toggle]');
    if (!(toggle instanceof HTMLInputElement)) {
        return;
    }

    const tablePanel = toggle.closest('#notification-records, [aria-labelledby="notificationsHeading"]');
    const columnName = toggle.dataset.notificationColumnToggle;
    if (!tablePanel || !columnName) {
        return;
    }

    tablePanel.querySelectorAll(`[data-notification-column="${columnName}"]`).forEach((column) => {
        column.hidden = !toggle.checked;
    });
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPortalQuickSearch, { once: true });
} else {
    initPortalQuickSearch();
}
