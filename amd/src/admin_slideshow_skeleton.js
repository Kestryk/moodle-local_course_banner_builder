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
 * Signals that the Slideshow administration controllers have been scheduled.
 *
 * @module     local_course_banner_builder/admin_slideshow_skeleton
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('local_course_banner_builder/admin_slideshow_skeleton', [], function() {
    var rootSelector = '[data-local-course-banner-builder-slideshow-skeleton="1"]';
    var readyAttribute = 'data-local-course-banner-builder-slideshow-ready';

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
     * Initialises all Slideshow page shells present in the document.
     */
    var init = function() {
        onReady(function() {
            afterPaint(function() {
                Array.prototype.slice.call(document.querySelectorAll(rootSelector)).forEach(function(root) {
                    root.setAttribute(readyAttribute, '1');
                });
            });
        });
    };

    return {init: init};
});
