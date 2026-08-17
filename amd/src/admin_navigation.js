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
 * Shared admin navigation behaviours.
 *
 * @module     local_course_banner_builder/admin_navigation
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['local_course_banner_builder/motion'], function(Motion) {
    var triggerSelector = '[data-easyedu-warning-popover], [data-easyedu-navigation-popover]';
    var popoverSelector = '.local-course-banner-builder-admin-navigation-popover';
    var counter = 0;

    var hidePopover = function() {
        Array.prototype.slice.call(document.querySelectorAll(popoverSelector)).forEach(function(popover) {
            var triggerId = popover.getAttribute('data-trigger-id');
            var trigger = triggerId ? document.getElementById(triggerId) : null;
            if (trigger) {
                trigger.removeAttribute('aria-describedby');
            }
            popover.remove();
        });
    };

    var placePopover = function(trigger, popover, placement) {
        var triggerRect = trigger.getBoundingClientRect();
        var popoverRect = popover.getBoundingClientRect();
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
        var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft || 0;
        var gap = 8;
        var top = triggerRect.bottom + scrollTop + gap;
        var left = triggerRect.left + scrollLeft + ((triggerRect.width - popoverRect.width) / 2);

        if (placement === 'top' || top + popoverRect.height > scrollTop + window.innerHeight - gap) {
            placement = 'top';
            top = triggerRect.top + scrollTop - popoverRect.height - gap;
        }

        var minLeft = scrollLeft + gap;
        var maxLeft = scrollLeft + Math.max(gap, window.innerWidth - popoverRect.width - gap);
        popover.classList.remove(
            'local-course-banner-builder-hover-popover--top',
            'local-course-banner-builder-hover-popover--bottom'
        );
        popover.classList.add('local-course-banner-builder-hover-popover--' + placement);
        var placedLeft = Math.max(minLeft, Math.min(maxLeft, left));
        var arrowLeft = (triggerRect.left + scrollLeft + (triggerRect.width / 2)) - placedLeft;
        arrowLeft = Math.max(12, Math.min(popoverRect.width - 12, arrowLeft));
        popover.style.setProperty('--local-course-banner-builder-popover-arrow-left', arrowLeft + 'px');
        popover.style.left = placedLeft + 'px';
        popover.style.top = Math.max(scrollTop + gap, top) + 'px';
    };

    var showPopover = function(trigger) {
        var isWarning = trigger.hasAttribute('data-easyedu-warning-popover');
        var content = isWarning ? trigger.getAttribute('data-easyedu-warning-popover') :
            trigger.getAttribute('data-easyedu-navigation-popover');
        if (!content) {
            return;
        }

        hidePopover();
        if (!trigger.id) {
            trigger.id = 'local-course-banner-builder-navigation-trigger-' + (++counter);
        }

        var placement = isWarning ? trigger.getAttribute('data-easyedu-warning-popover-placement') :
            trigger.getAttribute('data-easyedu-navigation-popover-placement');
        placement = placement || 'bottom';
        var popover = document.createElement('div');
        var popoverId = 'local-course-banner-builder-navigation-popover-' + (++counter);
        popover.id = popoverId;
        popover.className = 'popover local-course-banner-builder-hover-popover ' +
            'local-course-banner-builder-admin-navigation-popover ' +
            (isWarning ? 'local-course-banner-builder-hover-popover--warning ' : '') +
            'local-course-banner-builder-hover-popover--' + placement + ' show';
        popover.setAttribute('data-trigger-id', trigger.id);
        popover.setAttribute('role', 'tooltip');

        var arrow = document.createElement('div');
        arrow.className = 'popover-arrow';
        popover.appendChild(arrow);

        var body = document.createElement('div');
        body.className = 'popover-body';
        body.textContent = content;
        popover.appendChild(body);
        document.body.appendChild(popover);
        trigger.setAttribute('aria-describedby', popoverId);

        window.requestAnimationFrame(function() {
            placePopover(trigger, popover, placement);
        });
    };

    var getTrigger = function(target) {
        return target && target.closest ? target.closest(triggerSelector) : null;
    };

    var bindOptionsPanels = function() {
        Array.prototype.slice.call(document.querySelectorAll(
            '.local-course-banner-builder-options-panel'
        )).forEach(function(panel) {
            var summary = panel.querySelector('.local-course-banner-builder-options-panel-summary');
            var content = panel.querySelector('.local-course-banner-builder-options-panel-content');
            if (!summary || !content || panel.getAttribute('data-easyedu-options-panel-bound') === '1') {
                return;
            }
            panel.setAttribute('data-easyedu-options-panel-bound', '1');

            summary.addEventListener('click', function(event) {
                event.preventDefault();
                var targetState = panel.getAttribute('data-easyedu-options-panel-target-open');
                var opening = targetState === null ? !panel.open : targetState !== '1';
                panel.setAttribute('data-easyedu-options-panel-target-open', opening ? '1' : '0');
                if (opening) {
                    panel.open = true;
                    content.hidden = false;
                }

                var transition = opening ? Motion.expand(content) : Motion.collapse(content);
                transition.then(function(completed) {
                    if (!completed) {
                        return;
                    }
                    panel.removeAttribute('data-easyedu-options-panel-target-open');
                    if (!opening) {
                        panel.open = false;
                        content.hidden = false;
                    }
                });
            });
        });
    };

    var init = function() {
        if (document.documentElement.getAttribute('data-easyedu-navigation-popover-bound') === '1') {
            return;
        }
        document.documentElement.setAttribute('data-easyedu-navigation-popover-bound', '1');
        var root = document.querySelector('.local-course-banner-builder-admin');
        if (root) {
            Motion.init(root);
        }
        bindOptionsPanels();

        document.addEventListener('mouseover', function(event) {
            var trigger = getTrigger(event.target);
            if (trigger && (!event.relatedTarget || !trigger.contains(event.relatedTarget))) {
                showPopover(trigger);
            }
        });
        document.addEventListener('mouseout', function(event) {
            var trigger = getTrigger(event.target);
            if (trigger && (!event.relatedTarget || !trigger.contains(event.relatedTarget))) {
                hidePopover();
            }
        });
        document.addEventListener('focusin', function(event) {
            var trigger = getTrigger(event.target);
            if (trigger) {
                showPopover(trigger);
            }
        });
        document.addEventListener('focusout', function(event) {
            if (getTrigger(event.target)) {
                hidePopover();
            }
        });
        document.addEventListener('click', function(event) {
            if (getTrigger(event.target)) {
                hidePopover();
            }
        }, true);
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hidePopover();
            }
        });
        window.addEventListener('resize', hidePopover);
        window.addEventListener('scroll', hidePopover, true);
    };

    return {
        init: init
    };
});
