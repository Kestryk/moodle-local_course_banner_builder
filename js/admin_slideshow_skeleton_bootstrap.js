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
 * Starts the Slideshow page Skeleton before RequireJS initialises.
 *
 * The server renders the loading state. This small classic bootstrap owns the
 * bounded visual handoff so live controls can never paint before the Skeleton.
 *
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
(function() {
    'use strict';

    var rootSelector = '[data-local-course-banner-builder-slideshow-skeleton="1"]';
    var liveSelector = '[data-local-course-banner-builder-slideshow-skeleton-live="1"]';
    var skeletonSelector = '[data-local-course-banner-builder-slideshow-skeleton-placeholder="1"]';
    var statusSelector = '[data-local-course-banner-builder-slideshow-skeleton-status="1"]';
    var stateAttribute = 'data-local-course-banner-builder-slideshow-skeleton-state';
    var readyAttribute = 'data-local-course-banner-builder-slideshow-ready';
    var revealAttribute = 'data-local-course-banner-builder-slideshow-reveal-duration';
    var quietPeriod = 240;
    var minimumVisiblePeriod = 1200;
    var failOpenDelay = 1500;

    /**
     * Runs a callback after a browser paint.
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
     * Returns a valid transition duration for one shell.
     *
     * @param {Element} root Page shell.
     * @returns {number} Duration in milliseconds.
     */
    var getRevealDuration = function(root) {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return 0;
        }

        var duration = parseInt(root.getAttribute(revealAttribute), 10);
        return Number.isFinite(duration) && duration >= 0 ? duration : 180;
    };

    /**
     * Releases one shell immediately if its required markup is incomplete.
     *
     * @param {Element} root Page shell.
     * @param {?Element} live Live content wrapper.
     * @param {?Element} skeleton Skeleton placeholder.
     * @param {?Element} status Accessible status.
     */
    var forceReveal = function(root, live, skeleton, status) {
        root.setAttribute(stateAttribute, 'degraded');
        root.setAttribute('aria-busy', 'false');
        root.classList.remove('is-action-busy');

        if (skeleton) {
            skeleton.hidden = true;
        }
        if (live) {
            live.removeAttribute('aria-hidden');
            live.removeAttribute('inert');
        }
        if (status) {
            status.hidden = true;
        }
    };

    /**
     * Installs the bounded EasyStud-compatible lifecycle on one server shell.
     *
     * @param {Element} root Page shell.
     */
    var initialise = function(root) {
        if (root.getAttribute('data-local-course-banner-builder-slideshow-bootstrap') === '1') {
            return;
        }
        root.setAttribute('data-local-course-banner-builder-slideshow-bootstrap', '1');

        var live = root.querySelector(liveSelector);
        var skeleton = root.querySelector(skeletonSelector);
        var status = root.querySelector(statusSelector);
        var revealDuration = getRevealDuration(root);
        var startedAt = window.performance && window.performance.now ? window.performance.now() : Date.now();
        var contentObserver = null;
        var readyObserver = null;
        var quietTimer = null;
        var failOpenTimer = null;
        var transitionTimer = null;
        var readyObserved = false;
        var transitionStarted = false;

        if (!live || !skeleton) {
            forceReveal(root, live, skeleton, status);
            return;
        }

        root.setAttribute(stateAttribute, 'loading');
        root.setAttribute('aria-busy', 'true');
        root.classList.add('is-action-busy');
        live.setAttribute('aria-hidden', 'true');
        live.setAttribute('inert', '');
        skeleton.hidden = false;
        if (status) {
            status.hidden = false;
        }

        /**
         * Clears observers and pending stability timers.
         */
        var cleanup = function() {
            if (contentObserver) {
                contentObserver.disconnect();
                contentObserver = null;
            }
            if (readyObserver) {
                readyObserver.disconnect();
                readyObserver = null;
            }
            if (quietTimer) {
                window.clearTimeout(quietTimer);
                quietTimer = null;
            }
            if (failOpenTimer) {
                window.clearTimeout(failOpenTimer);
                failOpenTimer = null;
            }
        };

        /**
         * Completes the semantic and interaction handoff.
         *
         * @param {string} finalState Ready or degraded.
         */
        var complete = function(finalState) {
            root.setAttribute(stateAttribute, finalState);
            root.setAttribute('aria-busy', 'false');
            root.classList.remove(
                'is-action-busy',
                'is-slideshow-skeleton-exiting',
                'is-slideshow-content-entering',
                'is-slideshow-content-entered'
            );
            live.removeAttribute('aria-hidden');
            live.removeAttribute('inert');
            if (status) {
                status.hidden = true;
            }
        };

        /**
         * Fades the Skeleton out before fading the live page in.
         *
         * @param {string} finalState Ready or degraded.
         */
        var reveal = function(finalState) {
            if (transitionStarted) {
                return;
            }
            transitionStarted = true;
            cleanup();
            root.classList.add('is-slideshow-skeleton-exiting');

            transitionTimer = window.setTimeout(function() {
                skeleton.hidden = true;
                root.classList.remove('is-slideshow-skeleton-exiting');
                root.classList.add('is-slideshow-content-entering');

                afterPaint(function() {
                    root.classList.add('is-slideshow-content-entered');
                    transitionTimer = window.setTimeout(function() {
                        complete(finalState);
                    }, revealDuration);
                });
            }, revealDuration);
        };

        /**
         * Restarts the quiet timer while respecting the minimum visible time.
         */
        var scheduleStableReveal = function() {
            if (transitionStarted || !readyObserved) {
                return;
            }
            if (quietTimer) {
                window.clearTimeout(quietTimer);
            }

            var now = window.performance && window.performance.now ? window.performance.now() : Date.now();
            var minimumRemaining = Math.max(0, minimumVisiblePeriod - (now - startedAt));
            quietTimer = window.setTimeout(function() {
                reveal('ready');
            }, Math.max(quietPeriod, minimumRemaining));
        };

        /**
         * Starts visual-stability observation after the AMD readiness signal.
         */
        var observeVisualStability = function() {
            if (readyObserved || transitionStarted) {
                return;
            }
            readyObserved = true;

            if (window.MutationObserver) {
                contentObserver = new MutationObserver(scheduleStableReveal);
                contentObserver.observe(live, {
                    attributes: true,
                    attributeFilter: ['aria-expanded', 'class', 'hidden', 'style'],
                    childList: true,
                    subtree: true,
                });
            }

            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(scheduleStableReveal, scheduleStableReveal);
            }
            scheduleStableReveal();
        };

        try {
            if (root.getAttribute(readyAttribute) === '1') {
                observeVisualStability();
            } else if (window.MutationObserver) {
                readyObserver = new MutationObserver(function() {
                    if (root.getAttribute(readyAttribute) === '1') {
                        observeVisualStability();
                    }
                });
                readyObserver.observe(root, {attributes: true, attributeFilter: [readyAttribute]});
            }

            failOpenTimer = window.setTimeout(function() {
                reveal('degraded');
            }, failOpenDelay);
        } catch (error) {
            if (transitionTimer) {
                window.clearTimeout(transitionTimer);
            }
            cleanup();
            forceReveal(root, live, skeleton, status);
        }
    };

    var start = function() {
        Array.prototype.slice.call(document.querySelectorAll(rootSelector)).forEach(initialise);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, {once: true});
    } else {
        start();
    }
}());
