// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Shared EasyEdu desktop/responsive navigation controller.
 *
 * Consumers copy this module into their Moodle component namespace while
 * retaining the public data-attribute contract.
 *
 * @module     local_course_banner_builder/easyedu_navigation
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

const roots = new WeakMap();
const focusableSelector = [
    'a[href]:not([aria-disabled="true"])',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const getFocusable = panel => Array.from(panel.querySelectorAll(focusableSelector))
    .filter(element => !element.hidden && element.getClientRects().length > 0);

const setInert = (element, inert) => {
    if ('inert' in element) {
        element.inert = inert;
    }
    if (inert) {
        element.setAttribute('inert', '');
    } else {
        element.removeAttribute('inert');
    }
};

const resolveRoot = target => {
    if (target && target.nodeType === Node.ELEMENT_NODE) {
        return target;
    }
    if (typeof target === 'string') {
        return document.querySelector(target);
    }
    return document.querySelector('[data-easyedu-navigation]');
};

const bindDisclosure = root => {
    const rootId = root.id || 'easyedu-navigation';
    root.querySelectorAll('[data-easyedu-navigation-disclosure]').forEach(trigger => {
        const entry = trigger.closest('[data-easyedu-navigation-item-id]');
        const controlled = entry ? Array.from(entry.children)
            .find(element => element.matches('[data-easyedu-navigation-children]')) : null;
        if (!controlled) {
            return;
        }
        const presentation = trigger.closest('[data-easyedu-navigation-desktop]') ? 'desktop' : 'compact';
        const itemId = entry.getAttribute('data-easyedu-navigation-item-id');
        controlled.id = `${rootId}-${presentation}-${itemId}-children`;
        trigger.setAttribute('aria-controls', controlled.id);
    });

    root.addEventListener('click', event => {
        const trigger = event.target.closest('[data-easyedu-navigation-disclosure]');
        if (!trigger || !root.contains(trigger)) {
            return;
        }
        const entry = trigger.closest('[data-easyedu-navigation-item-id]');
        const controlled = entry ? Array.from(entry.children)
            .find(element => element.matches('[data-easyedu-navigation-children]')) : null;
        if (!controlled) {
            return;
        }
        const expanded = trigger.getAttribute('aria-expanded') !== 'true';
        trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        controlled.hidden = !expanded;
    });
};

const bindLabelReveal = root => {
    const entries = Array.from(root.querySelectorAll('[data-easyedu-navigation-label-reveal]'));
    const reveal = entry => entries.forEach(candidate => {
        candidate.classList.toggle('is-label-revealed', candidate === entry);
    });
    const clear = event => {
        const entry = event.currentTarget;
        if (!entry.contains(event.relatedTarget)) {
            entry.classList.remove('is-label-revealed');
        }
    };
    entries.forEach(entry => {
        entry.addEventListener('pointerenter', () => reveal(entry));
        entry.addEventListener('focusin', () => reveal(entry));
        entry.addEventListener('pointerleave', clear);
        entry.addEventListener('focusout', clear);
    });
};

const bindRoot = root => {
    if (!root || roots.has(root)) {
        return roots.get(root) || null;
    }

    const trigger = root.querySelector('[data-easyedu-navigation-open]');
    const panel = root.querySelector('[data-easyedu-navigation-panel]');
    const backdrop = root.querySelector('[data-easyedu-navigation-backdrop]');
    const closeButton = root.querySelector('[data-easyedu-navigation-close]');
    if (!trigger || !panel || !backdrop || !closeButton) {
        return null;
    }

    const anchorSelector = root.getAttribute('data-easyedu-navigation-anchor-selector');
    let positionFrame = 0;
    const isCompactAvailable = () => window.getComputedStyle(trigger).display !== 'none' &&
        trigger.getClientRects().length > 0;

    const syncTriggerPosition = () => {
        positionFrame = 0;
        let anchor = null;
        if (anchorSelector) {
            try {
                anchor = document.querySelector(anchorSelector);
            } catch (error) {
                anchor = null;
            }
        }
        if (!anchor || !anchor.getClientRects().length) {
            root.style.removeProperty('--easyedu-navigation-native-trigger-edge');
            return;
        }
        const edge = Math.max(0, Math.ceil(anchor.getBoundingClientRect().bottom));
        root.style.setProperty('--easyedu-navigation-native-trigger-edge', `${edge}px`);
    };

    const scheduleTriggerPosition = () => {
        if (!positionFrame) {
            positionFrame = window.requestAnimationFrame(syncTriggerPosition);
        }
    };

    const setOpen = (open, restoreFocus = true, force = false) => {
        if (open && !force && !isCompactAvailable()) {
            return;
        }
        panel.classList.toggle('is-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        backdrop.hidden = !open;
        setInert(panel, !open);
        document.documentElement.classList.toggle('easyedu-navigation-open', open);

        if (open) {
            const focusable = getFocusable(panel);
            const current = focusable.find(element => element.matches('[aria-current="page"]')) || closeButton ||
                focusable[0];
            if (current) {
                current.focus({preventScroll: true});
            }
        } else if (restoreFocus && document.documentElement.contains(trigger)) {
            trigger.focus({preventScroll: true});
        }
    };

    trigger.addEventListener('click', () => setOpen(true, true, true));
    trigger.addEventListener('keydown', event => {
        if (event.key !== 'Enter' && event.key !== ' ' && event.key !== 'Spacebar') {
            return;
        }
        event.preventDefault();
        setOpen(true, true, true);
    });
    closeButton.addEventListener('click', () => setOpen(false));
    backdrop.addEventListener('click', () => setOpen(false));
    panel.addEventListener('click', event => {
        if (event.target.closest('a[href]')) {
            setOpen(false, false);
            return;
        }
        if (event.target.closest('[data-easyedu-navigation-action]')) {
            window.setTimeout(() => {
                const focusMovedOutside = document.activeElement &&
                    !panel.contains(document.activeElement);
                setOpen(false, !focusMovedOutside);
            }, 0);
        }
    });
    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        const focusable = getFocusable(panel);
        if (!focusable.length) {
            event.preventDefault();
            closeButton.focus();
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    const onViewportChange = () => {
        if (!isCompactAvailable()) {
            setOpen(false, false);
        }
        scheduleTriggerPosition();
    };

    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', scheduleTriggerPosition, {passive: true});
    bindDisclosure(root);
    bindLabelReveal(root);
    syncTriggerPosition();
    setOpen(false, false);

    const controller = {
        close: restoreFocus => setOpen(false, restoreFocus !== false),
        open: () => setOpen(true),
        syncPosition: syncTriggerPosition,
    };
    roots.set(root, controller);
    return controller;
};

const init = target => {
    const root = resolveRoot(target);
    return bindRoot(root);
};

return {init: init};
});
