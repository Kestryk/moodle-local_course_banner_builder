// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Replace generated banner images by generated card thumbnails in native cards.
 *
 * @module     local_course_banner_builder/coursecards
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/config'], function(Config) {
    const GENERATED_BANNER_MARKER = 'course_banner_builder_auto_';
    const COURSE_FILEAREA_MARKER = '/course/overviewfiles/';
    const PROCESSED_ATTRIBUTE = 'data-course-banner-builder-card-processed';
    const PENDING_ATTRIBUTE = 'data-course-banner-builder-card-pending';
    const OVERLAY_PROCESSED_ATTRIBUTE = 'data-course-banner-builder-overlays-processed';
    const NATIVE_BANNER_PROCESSED_ATTRIBUTE = 'data-course-banner-builder-native-banner-processed';
    const OVERLAY_CONTAINER_CLASS = 'local-course-banner-builder-fixed-overlays';
    const NATIVE_BANNER_CLASS = 'local-course-banner-builder-native-course-banner';
    const COURSE_CARD_THUMB_CLASS = 'local-course-banner-builder-course-card-thumb';
    const COURSE_CARD_ROOT_CLASS = 'local-course-banner-builder-course-card-root';

    const BACKGROUND_TARGETS = [
        '.dashboard-card-img',
        '.dashboard-list-img',
        '.card-img',
        '.card-img-top',
        '.courseimage',
        '[style*="background-image"]'
    ].join(',');

    const ROOT_SELECTORS = [
        '.dashboard-card',
        '.course-card',
        '.coursebox',
        '.card',
        '[data-region="course-content"]'
    ].join(',');

    const HEADER_BANNER_TARGETS = [
        '#page-header .page-header-banner',
        '.page-header-banner',
        '.course-banner',
        '.course-header-banner',
        '[data-region="course-banner"]',
        '.local-course-banner-builder'
    ].join(',');

    const EXISTING_THEME_BANNER_TARGETS = [
        '#page-header .page-header-banner',
        '.page-header-banner',
        '.course-banner',
        '.course-header-banner',
        '[data-region="course-banner"]',
        '.local-course-banner-builder:not(.' + NATIVE_BANNER_CLASS + ')'
    ].join(',');

    const HOST_INLINE_STYLE = [
        'position:relative',
        'overflow:hidden'
    ].join(';');

    const CONTAINER_INLINE_STYLE = [
        'position:absolute',
        'top:0',
        'right:0',
        'bottom:0',
        'left:0',
        'z-index:5',
        'overflow:hidden',
        'pointer-events:none'
    ].join(';');

    const IMAGE_INLINE_STYLE = [
        'position:absolute',
        'display:block',
        'max-width:none',
        'object-fit:contain',
        'pointer-events:none'
    ].join(';');

    let observer = null;
    let cardObserver = null;
    let cardVisibilityCallbacks = typeof WeakMap !== 'undefined' ? new WeakMap() : null;
    let scheduled = false;
    let currentCourseOptions = {};
    let currentCourseId = 0;

    /**
     * Extract a URL from a CSS background-image declaration.
     *
     * @param {String} backgroundImage
     * @returns {String|null}
     */
    const extractBackgroundUrl = function(backgroundImage) {
        if (!backgroundImage || backgroundImage === 'none') {
            return null;
        }

        const match = backgroundImage.match(/url\((['"]?)(.*?)\1\)/);
        return match ? match[2] : null;
    };

    /**
     * Check if a URL is one of the generated banner images managed by the plugin.
     *
     * @param {String|null} url
     * @returns {Boolean}
     */
    const isManagedBannerUrl = function(url) {
        return !!url &&
            url.indexOf(COURSE_FILEAREA_MARKER) !== -1 &&
            url.indexOf(GENERATED_BANNER_MARKER) !== -1;
    };

    /**
     * Get the course id exposed by Moodle card markup.
     *
     * @param {Element} element
     * @returns {String|null}
     */
    const getCourseId = function(element) {
        return element.getAttribute('data-course-id') ||
            element.getAttribute('data-courseid') ||
            null;
    };

    /**
     * Build the stable card-thumbnail URL exposed by this plugin.
     *
     * @param {String} courseId
     * @param {Boolean} square
     * @returns {String}
     */
    const getCardUrl = function(courseId, square) {
        const url = Config.wwwroot + '/local/course_banner_builder/card.php?courseid=' + encodeURIComponent(courseId);
        return square ? url + '&variant=square' : url;
    };

    /**
     * Whether a Moodle card thumbnail target is rendered in a square coursebox.
     *
     * @param {Element} target
     * @returns {Boolean}
     */
    const isSquareCourseBoxTarget = function(target) {
        const rect = target.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
            const ratio = rect.width / rect.height;
            return ratio >= 0.85 && ratio <= 1.15;
        }

        const root = target.closest(ROOT_SELECTORS);
        if (!root) {
            return false;
        }

        const rootRect = root.getBoundingClientRect();
        if (rootRect.width <= 0 || rootRect.height <= 0) {
            return !!target.closest('.coursebox');
        }

        const rootRatio = rootRect.width / rootRect.height;
        return rootRatio >= 0.85 && rootRatio <= 1.15;
    };

    /**
     * Build the generated 4:1 banner URL exposed by this plugin.
     *
     * @param {String|Number} courseId
     * @returns {String}
     */
    const getBannerUrl = function(courseId) {
        return Config.wwwroot + '/local/course_banner_builder/banner.php?courseid=' + encodeURIComponent(courseId);
    };

    /**
     * Build the positioned overlays URL.
     *
     * @param {String|Number} courseId
     * @returns {String}
     */
    const getOverlaysUrl = function(courseId) {
        return Config.wwwroot + '/local/course_banner_builder/overlays.php?courseid=' + encodeURIComponent(courseId);
    };

    /**
     * Fallback course id extraction for pages where the hook cannot expose one.
     *
     * @returns {String|Number}
     */
    const getCurrentCourseId = function() {
        const bodyClass = document.body && document.body.className ?
            document.body.className.match(/(?:^|\s)course-(\d+)(?:\s|$)/) :
            null;
        if (bodyClass && bodyClass[1]) {
            return bodyClass[1];
        }

        const query = new URLSearchParams(window.location.search);
        return query.get('id') || 0;
    };

    /**
     * Preload a thumbnail and apply it only if it exists.
     *
     * @param {String} url
     * @param {Function} callback
     */
    const applyWhenLoadable = function(url, callback) {
        const image = new Image();
        image.decoding = 'async';
        image.loading = 'lazy';
        image.onload = function() {
            callback(url);
        };
        image.src = url;
    };

    /**
     * Run a thumbnail replacement only when the card is close to the viewport.
     *
     * @param {Element} target
     * @param {Function} callback
     */
    const runWhenCardIsNearViewport = function(target, callback) {
        const rect = target.getBoundingClientRect();
        const margin = 700;
        if (!rect.width || !rect.height || (rect.bottom >= -margin && rect.top <= window.innerHeight + margin)) {
            callback();
            return;
        }

        if (!('IntersectionObserver' in window) || !cardVisibilityCallbacks) {
            callback();
            return;
        }

        if (!cardObserver) {
            cardObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (!entry.isIntersecting && entry.intersectionRatio <= 0) {
                        return;
                    }
                    const observed = entry.target;
                    const observedCallback = cardVisibilityCallbacks.get(observed);
                    cardObserver.unobserve(observed);
                    cardVisibilityCallbacks.delete(observed);
                    observed.removeAttribute(PENDING_ATTRIBUTE);
                    if (observedCallback) {
                        observedCallback();
                    }
                });
            }, {rootMargin: margin + 'px 0px'});
        }

        if (target.getAttribute(PENDING_ATTRIBUTE) === '1') {
            return;
        }

        target.setAttribute(PENDING_ATTRIBUTE, '1');
        cardVisibilityCallbacks.set(target, callback);
        cardObserver.observe(target);
    };

    /**
     * Add stable styling hooks to the native card thumbnail surface.
     *
     * @param {Element} target
     * @param {Element} root
     */
    const markCardThumbnail = function(target, root) {
        target.classList.add(COURSE_CARD_THUMB_CLASS);
        if (root) {
            root.classList.add(COURSE_CARD_ROOT_CLASS);
        }
    };

    /**
     * Replace a background image when it points to a generated banner.
     *
     * @param {Element} target
     * @param {String} courseId
     */
    const replaceBackground = function(target, courseId) {
        if (target.getAttribute(PROCESSED_ATTRIBUTE) === courseId) {
            return;
        }

        const inlineUrl = extractBackgroundUrl(target.style.backgroundImage);
        const computedUrl = inlineUrl || extractBackgroundUrl(window.getComputedStyle(target).backgroundImage);
        if (!isManagedBannerUrl(computedUrl)) {
            return;
        }

        const cardUrl = getCardUrl(courseId, isSquareCourseBoxTarget(target));
        target.setAttribute(PROCESSED_ATTRIBUTE, courseId);
        runWhenCardIsNearViewport(target, function() {
            applyWhenLoadable(cardUrl, function(loadableUrl) {
                markCardThumbnail(target, target.closest(ROOT_SELECTORS));
                target.style.backgroundImage = 'url("' + loadableUrl + '")';
                target.style.backgroundPosition = 'center center';
                target.style.backgroundSize = 'cover';
                target.style.backgroundRepeat = 'no-repeat';
            });
        });
    };

    /**
     * Replace an image source when it points to a generated banner.
     *
     * @param {HTMLImageElement} target
     * @param {String} courseId
     */
    const replaceImageSource = function(target, courseId) {
        if (target.getAttribute(PROCESSED_ATTRIBUTE) === courseId || !isManagedBannerUrl(target.src)) {
            return;
        }

        const cardUrl = getCardUrl(courseId, isSquareCourseBoxTarget(target));
        target.setAttribute(PROCESSED_ATTRIBUTE, courseId);
        runWhenCardIsNearViewport(target, function() {
            applyWhenLoadable(cardUrl, function(loadableUrl) {
                markCardThumbnail(target, target.closest(ROOT_SELECTORS));
                target.loading = 'lazy';
                target.decoding = 'async';
                target.src = loadableUrl;
                target.style.objectFit = 'cover';
                target.style.objectPosition = 'center center';
            });
        });
    };

    /**
     * Process one element exposing a course id.
     *
     * @param {Element} courseElement
     */
    const processCourseElement = function(courseElement) {
        const courseId = getCourseId(courseElement);
        if (!courseId) {
            return;
        }

        const root = courseElement.closest(ROOT_SELECTORS) || courseElement;
        if (root.matches(BACKGROUND_TARGETS)) {
            replaceBackground(root, courseId);
        }
        root.querySelectorAll(BACKGROUND_TARGETS).forEach(function(target) {
            replaceBackground(target, courseId);
        });
        root.querySelectorAll('img').forEach(function(target) {
            replaceImageSource(target, courseId);
        });
    };

    /**
     * Replace thumbnails currently present in the page.
     */
    const scan = function() {
        scheduled = false;
        document.querySelectorAll('[data-course-id], [data-courseid]').forEach(processCourseElement);
    };

    /**
     * Build one overlay image element.
     *
     * @param {Object} overlay
     * @returns {HTMLDivElement}
     */
    const buildOverlayImage = function(overlay) {
        const wrapper = document.createElement('div');
        wrapper.className = 'local-course-banner-builder-fixed-overlay';
        wrapper.setAttribute('aria-hidden', 'true');
        wrapper.setAttribute('style', overlay.wrapperstyle || '');

        const image = document.createElement('img');
        image.className = 'local-course-banner-builder-fixed-overlay-image';
        image.src = overlay.url;
        image.alt = '';
        image.loading = 'lazy';
        image.setAttribute('aria-hidden', 'true');
        image.setAttribute('style', (overlay.imagestyle || (IMAGE_INLINE_STYLE + ';' + (overlay.style || ''))));
        wrapper.appendChild(image);
        return wrapper;
    };

    /**
     * Build one border overlay element.
     *
     * @param {Object} border
     * @returns {HTMLDivElement}
     */
    const buildBorderOverlay = function(border) {
        const element = document.createElement('div');
        element.className = 'local-course-banner-builder-fixed-border';
        element.setAttribute('aria-hidden', 'true');
        element.setAttribute('style', border.wrapperstyle || border.boxstyle || border.style || '');

        [
            ['top', 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-top'],
            ['right', 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-right'],
            ['bottom', 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-bottom'],
            ['left', 'local-course-banner-builder-fixed-border-side local-course-banner-builder-fixed-border-side-left'],
        ].forEach(function(entry) {
            const side = document.createElement('div');
            side.className = entry[1];
            side.setAttribute('aria-hidden', 'true');
            side.setAttribute('style', (border.sidestyles && border.sidestyles[entry[0]]) || '');
            element.appendChild(side);
        });

        [
            'top-left',
            'top-right',
            'bottom-right',
            'bottom-left'
        ].forEach(function(name) {
            const corner = document.createElement('div');
            corner.className = 'local-course-banner-builder-fixed-border-corner local-course-banner-builder-fixed-border-corner-' + name;
            corner.setAttribute('aria-hidden', 'true');
            element.appendChild(corner);
        });

        const hole = document.createElement('div');
        hole.className = 'local-course-banner-builder-fixed-border-hole';
        hole.setAttribute('aria-hidden', 'true');
        element.appendChild(hole);

        return element;
    };

    /**
     * Add overlay images to one banner target.
     *
     * @param {Element} target
     * @param {Array} overlays
     * @param {String|Number} courseId
     * @param {?Object} border
     */
    const applyOverlaysToTarget = function(target, overlays, courseId, force, border) {
        if (target.getAttribute(OVERLAY_PROCESSED_ATTRIBUTE) === String(courseId)) {
            return;
        }

        if (target.closest('.page-header-banner-border-hole')) {
            return;
        }

        if (target.querySelector(':scope > .page-header-banner-overlays')) {
            target.setAttribute(OVERLAY_PROCESSED_ATTRIBUTE, String(courseId));
            return;
        }

        const inlineUrl = extractBackgroundUrl(target.style.backgroundImage);
        const computedUrl = inlineUrl || extractBackgroundUrl(window.getComputedStyle(target).backgroundImage);
        if (!force && !isManagedBannerUrl(computedUrl) && !target.classList.contains('local-course-banner-builder')) {
            return;
        }

        target.classList.add('local-course-banner-builder-overlay-host');
        target.style.cssText += ';' + HOST_INLINE_STYLE;
        let container = target.querySelector(':scope > .' + OVERLAY_CONTAINER_CLASS);
        if (!container) {
            container = document.createElement('div');
            container.className = OVERLAY_CONTAINER_CLASS;
            container.setAttribute('aria-hidden', 'true');
            container.setAttribute('style', CONTAINER_INLINE_STYLE);
            target.appendChild(container);
        }

        container.replaceChildren();
        if (force && border) {
            container.appendChild(buildBorderOverlay(border));
        }
        overlays.forEach(function(overlay) {
            container.appendChild(buildOverlayImage(overlay));
        });
        target.setAttribute(OVERLAY_PROCESSED_ATTRIBUTE, String(courseId));
    };

    /**
     * Fetch and inject course header overlays.
     *
     * @param {String|Number} courseId
     */
    const applyHeaderOverlays = function(courseId) {
        if (!courseId) {
            return;
        }

        fetch(getOverlaysUrl(courseId), {credentials: 'same-origin'})
            .then(function(response) {
                return response.ok ? response.json() : null;
            })
            .then(function(payload) {
                const overlays = payload && Array.isArray(payload.overlays) ? payload.overlays : [];
                const border = payload && payload.border ? payload.border : null;
                if (!overlays.length && !border) {
                    return;
                }

                document.querySelectorAll(HEADER_BANNER_TARGETS).forEach(function(target) {
                    applyOverlaysToTarget(target, overlays, courseId, true, border);
                });
            })
            .catch(function() {
                // The generated PNG remains a safe fallback if overlays cannot be loaded.
            });
    };

    /**
     * Check whether the active theme already exposes a course banner surface.
     *
     * @returns {Boolean}
     */
    const hasExistingThemeBanner = function() {
        return Array.prototype.slice.call(document.querySelectorAll(EXISTING_THEME_BANNER_TARGETS)).some(function(target) {
            if (target.classList.contains(NATIVE_BANNER_CLASS)) {
                return false;
            }

            const rect = target.getBoundingClientRect();
            const styles = window.getComputedStyle(target);
            if (rect.width <= 0 || rect.height < 40 || styles.display === 'none' || styles.visibility === 'hidden') {
                return false;
            }

            const backgroundUrl = extractBackgroundUrl(target.style.backgroundImage) ||
                extractBackgroundUrl(styles.backgroundImage);
            return !!backgroundUrl || !!target.querySelector('img, picture, [style*="background-image"]');
        });
    };

    /**
     * Find the safest insertion point for a native Moodle course banner.
     *
     * @returns {Element|null}
     */
    const getNativeBannerInsertionPoint = function(format) {
        const courseHeader = document.querySelector('#course-header');
        if (courseHeader && format !== 'fullwidthtop' && format !== 'fullwidthtopcompact') {
            return {
                element: courseHeader,
                method: 'append'
            };
        }

        const pageHeader = document.querySelector('#page-header');
        if (pageHeader) {
            return {
                element: pageHeader,
                method: (format === 'fullwidthtop' || format === 'fullwidthtopcompact') ? 'beforebegin' : 'afterend'
            };
        }

        const fallback = document.querySelector('[data-region="mainpage"]') ||
            document.querySelector('#region-main') ||
            document.querySelector('main');
        if (!fallback) {
            return null;
        }

        return {
            element: fallback,
            method: 'beforebegin'
        };
    };

    /**
     * Inject a 4:1 course banner when the current theme does not already render one.
     *
     * @param {String|Number} courseId
     * @param {Object} options
     */
    const injectNativeCourseBanner = function(courseId, options) {
        if (
            !courseId ||
            !options ||
            !options.enabled ||
            !options.allowNativeBanner ||
            options.themeHasBanner ||
            hasExistingThemeBanner()
        ) {
            return;
        }

        const existingBanner = document.querySelector('.' + NATIVE_BANNER_CLASS);
        if (existingBanner && document.body.contains(existingBanner)) {
            applyHeaderOverlays(courseId);
            return;
        }

        const format = ['contentwide', 'fullwidthtop', 'fullwidthtopcompact'].indexOf(options.bannerFormat) !== -1 ?
            options.bannerFormat :
            'standard';
        const insertion = getNativeBannerInsertionPoint(format);
        if (!insertion || !insertion.element ||
            (
                insertion.element.getAttribute(NATIVE_BANNER_PROCESSED_ATTRIBUTE) === String(courseId) &&
                insertion.element.querySelector('.' + NATIVE_BANNER_CLASS)
            )
        ) {
            return;
        }

        const insertBanner = function(loadableUrl) {
            if (hasExistingThemeBanner() ||
                (
                    insertion.element.getAttribute(NATIVE_BANNER_PROCESSED_ATTRIBUTE) === String(courseId) &&
                    insertion.element.querySelector('.' + NATIVE_BANNER_CLASS)
                )) {
                return;
            }

            const banner = document.createElement('div');
            banner.className = 'local-course-banner-builder ' + NATIVE_BANNER_CLASS + ' ' +
                NATIVE_BANNER_CLASS + '--format-' + format;
            banner.setAttribute('data-course-banner-builder-native', '1');
            banner.setAttribute('data-banner-format', format);
            banner.setAttribute('aria-hidden', 'true');
            if (loadableUrl) {
                banner.style.backgroundImage = 'url("' + loadableUrl + '")';
            }

            if (insertion.method === 'append') {
                insertion.element.replaceChildren(banner);
            } else {
                insertion.element.insertAdjacentElement(insertion.method, banner);
            }
            insertion.element.setAttribute(NATIVE_BANNER_PROCESSED_ATTRIBUTE, String(courseId));
            applyHeaderOverlays(courseId);
        };

        if (!options.hasNativeBackground) {
            insertBanner('');
            return;
        }

        const bannerUrl = getBannerUrl(courseId);
        applyWhenLoadable(bannerUrl, function(loadableUrl) {
            insertBanner(loadableUrl);
        });
    };

    /**
     * Debounce scans caused by AJAX course-card rendering.
     */
    const scheduleScan = function() {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.setTimeout(function() {
            scan();
            if (currentCourseOptions.allowNativeBanner) {
                injectNativeCourseBanner(currentCourseId, currentCourseOptions);
            }
            if (currentCourseOptions.allowCourseHeaderOverlays) {
                applyHeaderOverlays(currentCourseId);
            }
            scheduled = false;
        }, 100);
    };

    /**
     * Initialise the course card enhancer.
     */
    const init = function(currentCourseId, options) {
        options = options || {};
        const courseId = currentCourseId || getCurrentCourseId();
        currentCourseOptions = options;
        currentCourseId = courseId;
        if (!options.enabled) {
            return;
        }

        scan();
        if (options.allowNativeBanner) {
            injectNativeCourseBanner(courseId, options);
        }
        if (options.allowCourseHeaderOverlays) {
            applyHeaderOverlays(courseId);
        }

        if (observer) {
            return;
        }

        observer = new MutationObserver(scheduleScan);
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        window.setTimeout(scheduleScan, 250);
        window.setTimeout(scheduleScan, 1000);
    };

    return {
        init: init
    };
});
