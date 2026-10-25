const searchRoots = '[data-portal-quick-search]';

function normalizeSearchText(value) {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase()
        .replace(/[^\p{L}\p{N}]+/gu, ' ')
        .trim();
}

function editDistance(left, right, cutoff) {
    if (Math.abs(left.length - right.length) > cutoff) {
        return cutoff + 1;
    }

    let previous = Array.from({ length: right.length + 1 }, (_, index) => index);
    for (let row = 1; row <= left.length; row += 1) {
        const current = [row];
        let rowMinimum = row;
        for (let column = 1; column <= right.length; column += 1) {
            current[column] = Math.min(
                current[column - 1] + 1,
                previous[column] + 1,
                previous[column - 1] + (left[row - 1] === right[column - 1] ? 0 : 1),
            );
            rowMinimum = Math.min(rowMinimum, current[column]);
        }
        if (rowMinimum > cutoff) {
            return cutoff + 1;
        }
        previous = current;
    }

    return previous[right.length];
}

function scoreSearchOption(option, queryTerms, normalizedQuery) {
    const searchableText = normalizeSearchText(option.dataset.searchTerms || option.textContent || '');
    const label = normalizeSearchText(option.querySelector('strong')?.textContent || '');
    const searchableWords = [...new Set(searchableText.split(/\s+/).filter(Boolean))];
    let score = 0;

    for (const queryTerm of queryTerms) {
        let best = 0;
        for (const word of searchableWords) {
            if (word === queryTerm) {
                best = Math.max(best, 100);
            } else if (word.startsWith(queryTerm)) {
                best = Math.max(best, 78);
            } else if (word.includes(queryTerm)) {
                best = Math.max(best, 58);
            } else if (queryTerm.length >= 4) {
                const distanceLimit = queryTerm.length >= 8 ? 2 : 1;
                const distance = editDistance(queryTerm, word, distanceLimit);
                if (distance <= distanceLimit) {
                    best = Math.max(best, 44 - (distance * 8));
                }
            }
        }

        if (best === 0) {
            return 0;
        }
        score += best;
    }

    if (label === normalizedQuery) {
        score += 60;
    } else if (label.startsWith(normalizedQuery)) {
        score += 30;
    } else if (searchableText.includes(normalizedQuery)) {
        score += 18;
    }

    return score;
}

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

        const getVisibleOptions = () => Array.from(results.querySelectorAll('[data-portal-quick-search-option]'))
            .filter((option) => !option.hidden);

        const render = () => {
            const normalizedQuery = normalizeSearchText(input.value);
            const queryTerms = normalizedQuery.split(/\s+/).filter(Boolean);
            const rankedOptions = queryTerms.length === 0
                ? []
                : options
                    .map((option) => ({ option, score: scoreSearchOption(option, queryTerms, normalizedQuery) }))
                    .filter((result) => result.score > 0)
                    .sort((left, right) => right.score - left.score)
                    .slice(0, 7);
            const visibleOptions = rankedOptions.map(({ option }) => option);

            options.forEach((option) => {
                option.hidden = !visibleOptions.includes(option);
                option.classList.remove('is-active');
                option.setAttribute('aria-selected', 'false');
            });
            visibleOptions.forEach((option) => results.insertBefore(option, emptyState || null));

            activeIndex = -1;
            if (emptyState) {
                emptyState.hidden = visibleOptions.length > 0 || queryTerms.length === 0;
            }
            results.hidden = queryTerms.length === 0;
            input.setAttribute('aria-expanded', results.hidden ? 'false' : 'true');
            input.removeAttribute('aria-activedescendant');
        };

        const selectActive = (nextIndex) => {
            const visibleOptions = getVisibleOptions();
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
            const visibleOptions = getVisibleOptions();

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
