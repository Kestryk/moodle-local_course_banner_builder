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
    const OVERLAY_PROCESSED_ATTRIBUTE = 'data-course-banner-builder-overlays-processed';
    const OVERLAY_CONTAINER_CLASS = 'local-course-banner-builder-fixed-overlays';

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
        '.local-course-banner-builder'
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
    let scheduled = false;

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
     * @returns {String}
     */
    const getCardUrl = function(courseId) {
        return Config.wwwroot + '/local/course_banner_builder/card.php?courseid=' + encodeURIComponent(courseId);
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
        image.onload = function() {
            callback(url);
        };
        image.src = url;
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

        const cardUrl = getCardUrl(courseId);
        target.setAttribute(PROCESSED_ATTRIBUTE, courseId);
        applyWhenLoadable(cardUrl, function(loadableUrl) {
            target.style.backgroundImage = 'url("' + loadableUrl + '")';
            target.style.backgroundPosition = 'center center';
            target.style.backgroundSize = 'cover';
            target.style.backgroundRepeat = 'no-repeat';
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

        const cardUrl = getCardUrl(courseId);
        target.setAttribute(PROCESSED_ATTRIBUTE, courseId);
        applyWhenLoadable(cardUrl, function(loadableUrl) {
            target.src = loadableUrl;
            target.style.objectFit = 'cover';
            target.style.objectPosition = 'center center';
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
     * @returns {HTMLImageElement}
     */
    const buildOverlayImage = function(overlay) {
        const image = document.createElement('img');
        image.className = 'local-course-banner-builder-fixed-overlay-image';
        image.src = overlay.url;
        image.alt = '';
        image.loading = 'lazy';
        image.setAttribute('aria-hidden', 'true');
        image.setAttribute('style', IMAGE_INLINE_STYLE + ';' + (overlay.style || ''));
        return image;
    };

    /**
     * Add overlay images to one banner target.
     *
     * @param {Element} target
     * @param {Array} overlays
     * @param {String|Number} courseId
     */
    const applyOverlaysToTarget = function(target, overlays, courseId, force) {
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
                if (!overlays.length) {
                    return;
                }

                document.querySelectorAll(HEADER_BANNER_TARGETS).forEach(function(target) {
                    applyOverlaysToTarget(target, overlays, courseId, true);
                });
            })
            .catch(function() {
                // The generated PNG remains a safe fallback if overlays cannot be loaded.
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
        window.setTimeout(scan, 100);
    };

    /**
     * Initialise the course card enhancer.
     */
    const init = function(currentCourseId) {
        scan();
        applyHeaderOverlays(currentCourseId || getCurrentCourseId());

        if (observer) {
            return;
        }

        observer = new MutationObserver(scheduleScan);
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    };

    return {
        init: init
    };
});
