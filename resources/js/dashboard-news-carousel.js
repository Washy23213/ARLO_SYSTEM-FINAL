const carouselSelector = '[data-dashboard-news-carousel]';

function initializeDashboardNewsCarousels() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    document.querySelectorAll(carouselSelector).forEach((carousel) => {
        if (carousel.dataset.newsCarouselReady === 'true') {
            return;
        }

        const viewport = carousel.querySelector('.alumni-dashboard-news-carousel__viewport');
        const track = carousel.querySelector('.alumni-dashboard-news-carousel__track');
        const originals = track
            ? Array.from(track.children).filter((item) => item.hasAttribute('data-news-carousel-item'))
            : [];

        if (!(viewport instanceof HTMLElement) || !(track instanceof HTMLElement) || originals.length < 2) {
            return;
        }

        carousel.dataset.newsCarouselReady = 'true';
        let clones = [];
        let resizeObserver;

        const removeClones = () => {
            clones.forEach((clone) => clone.remove());
            clones = [];
        };

        const update = () => {
            if (reduceMotion.matches) {
                carousel.classList.remove('is-animated');
                removeClones();
                track.style.removeProperty('--news-card-width');
                track.style.removeProperty('--news-slide-end');
                track.style.removeProperty('--news-slide-duration');
                originals.forEach((item) => {
                    item.style.removeProperty('flex-basis');
                    item.style.removeProperty('width');
                });
                return;
            }

            const columns = window.innerWidth >= 1024 ? 3 : (window.innerWidth >= 640 ? 2 : 1);
            if (originals.length < columns) {
                carousel.classList.remove('is-animated');
                removeClones();
                track.style.removeProperty('--news-card-width');
                track.style.removeProperty('--news-slide-end');
                track.style.removeProperty('--news-slide-duration');
                originals.forEach((item) => {
                    item.style.removeProperty('flex-basis');
                    item.style.removeProperty('width');
                });
                return;
            }

            if (clones.length === 0) {
                clones = originals.map((item) => {
                    const clone = item.cloneNode(true);
                    clone.setAttribute('aria-hidden', 'true');
                    clone.setAttribute('tabindex', '-1');
                    clone.setAttribute('data-news-carousel-clone', '');
                    clone.inert = true;
                    return clone;
                });
                track.append(...clones);
            }

            carousel.classList.add('is-animated');
            const gap = Number.parseFloat(window.getComputedStyle(track).columnGap) || 16;
            const cardWidth = Math.max(1, (viewport.clientWidth - (gap * (columns - 1))) / columns);
            track.style.setProperty('--news-card-width', `${cardWidth}px`);
            [...originals, ...clones].forEach((item) => {
                item.style.flexBasis = `${cardWidth}px`;
                item.style.width = `${cardWidth}px`;
            });

            const slideDistance = (cardWidth * originals.length) + (gap * originals.length);
            track.style.setProperty('--news-slide-end', `-${slideDistance}px`);
            track.style.setProperty('--news-slide-duration', `${Math.max(22, slideDistance / 48)}s`);
        };

        const motionPreferenceChanged = () => update();
        reduceMotion.addEventListener?.('change', motionPreferenceChanged);

        if ('ResizeObserver' in window) {
            resizeObserver = new ResizeObserver(update);
            resizeObserver.observe(viewport);
        } else {
            window.addEventListener('resize', update, { passive: true });
        }

        update();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDashboardNewsCarousels, { once: true });
} else {
    initializeDashboardNewsCarousels();
}
