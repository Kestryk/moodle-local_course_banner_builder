// CCB-owned responsive Guide adapter. It does not move the Guide portal or
// validate steps: it only reserves the space occupied by visible bottom actions.
define([], function() {
    const isVisible = element => {
        if (!element || element.hidden) {
            return false;
        }
        const style = window.getComputedStyle(element);
        const rect = element.getBoundingClientRect();
        return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
    };

    const syncBottomObstruction = root => {
        if (!root || !window.matchMedia('(max-width: 64rem)').matches) {
            root && root.style.removeProperty('--easyedu-guide-bottom-obstruction');
            return;
        }
        const viewport = window.innerHeight || document.documentElement.clientHeight;
        const obstruction = Array.from(document.querySelectorAll('.local-course-banner-builder-bottom-actionbar'))
            .filter(isVisible)
            .map(element => element.getBoundingClientRect())
            .filter(rect => rect.bottom >= viewport - 2 && rect.top < viewport)
            .reduce((height, rect) => Math.max(height, viewport - Math.max(0, rect.top)), 0);
        root.style.setProperty('--easyedu-guide-bottom-obstruction', `${Math.ceil(obstruction)}px`);
    };

    const init = rootOrSelector => {
        const root = typeof rootOrSelector === 'string' ? document.querySelector(rootOrSelector) : rootOrSelector;
        if (!root || root.dataset.easyeduGuideCcbAdapterBound === '1') {
            return;
        }
        root.dataset.easyeduGuideCcbAdapterBound = '1';
        const sync = () => syncBottomObstruction(root);
        window.addEventListener('resize', sync, {passive: true});
        window.addEventListener('orientationchange', sync, {passive: true});
        window.addEventListener('scroll', sync, {passive: true});
        new ResizeObserver(sync).observe(document.body);
        sync();
    };

    return {init: init};
});
