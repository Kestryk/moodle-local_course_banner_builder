// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Fail-open loading lifecycle for the Slideshow administration shell.
 *
 * @module     local_course_banner_builder/admin_slideshow_skeleton
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('local_course_banner_builder/admin_slideshow_skeleton', [], function() {
    var rootSelector = '[data-local-course-banner-builder-slideshow-skeleton="1"]';
    var liveSelector = '[data-local-course-banner-builder-slideshow-skeleton-live="1"]';
    var statusSelector = '[data-local-course-banner-builder-slideshow-skeleton-status="1"]';
    var quietDelay = 180;
    var failOpenDelay = 1500;

    /**
     * Runs a callback after a browser paint, with a timeout fallback.
     *
     * @param {Function} callback Callback to run.
     */
    var afterPaint = function(callback) {
        if (!window.requestAnimationFrame) {
            window.setTimeout(callback, 0);
            return;
        }

        window.requestAnimationFrame(function() {
            window.requestAnimationFrame(callback);
        });
    };

    /**
     * Gets the shell out of the initial DOM loading phase.
     *
     * @param {Function} callback Callback to run once DOM is available.
     */
    var onReady = function(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, {once: true});
            return;
        }

        callback();
    };

    /**
     * Updates the semantic and interaction state of one Slideshow shell.
     *
     * @param {Element} root Page shell.
     * @param {Element} live Live content wrapper.
     * @param {Element} status Live loading status.
     * @param {boolean} isLoading Whether the shell is loading.
     */
    var setState = function(root, live, status, isLoading) {
        root.setAttribute(
            'data-local-course-banner-builder-slideshow-skeleton-state',
            isLoading ? 'loading' : 'ready'
        );
        root.setAttribute('aria-busy', isLoading ? 'true' : 'false');

        if (live) {
            if (isLoading) {
                live.setAttribute('aria-hidden', 'true');
                live.setAttribute('inert', '');
            } else {
                live.removeAttribute('aria-hidden');
                live.removeAttribute('inert');
            }
        }

        if (status) {
            status.hidden = !isLoading;
        }
    };

    /**
     * Starts one deterministic, fail-open Slideshow shell lifecycle.
     *
     * @param {Element} root Page shell.
     */
    var initialise = function(root) {
        var live = root.querySelector(liveSelector);
        var status = root.querySelector(statusSelector);
        var observer = null;
        var quietTimer = null;
        var failOpenTimer = null;
        var released = false;

        var release = function() {
            if (released) {
                return;
            }

            released = true;
            if (observer) {
                observer.disconnect();
            }
            if (quietTimer) {
                window.clearTimeout(quietTimer);
            }
            if (failOpenTimer) {
                window.clearTimeout(failOpenTimer);
            }

            afterPaint(function() {
                setState(root, live, status, false);
            });
        };

        var scheduleRelease = function() {
            if (released) {
                return;
            }
            if (quietTimer) {
                window.clearTimeout(quietTimer);
            }
            quietTimer = window.setTimeout(release, quietDelay);
        };

        try {
            setState(root, live, status, true);

            if (!live) {
                release();
                return;
            }

            if (window.MutationObserver) {
                observer = new MutationObserver(scheduleRelease);
                observer.observe(live, {
                    attributes: true,
                    attributeFilter: ['class', 'hidden', 'style', 'aria-expanded'],
                    childList: true,
                    subtree: true,
                });
            }

            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(scheduleRelease, scheduleRelease);
            }

            failOpenTimer = window.setTimeout(release, failOpenDelay);
            scheduleRelease();
        } catch (error) {
            release();
        }
    };

    /**
     * Initialises all Slideshow page shells present in the document.
     */
    var init = function() {
        onReady(function() {
            Array.prototype.slice.call(document.querySelectorAll(rootSelector)).forEach(initialise);
        });
    };

    return {init: init};
});
