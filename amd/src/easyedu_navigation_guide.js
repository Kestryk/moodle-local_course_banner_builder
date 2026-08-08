// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Optional bridge between the shared Navigation and Guide contracts.
 *
 * The complete Guide root is portalled to document.body so viewport-fixed
 * surfaces cannot be captured by an off-canvas containing block. Only
 * interactive launcher projections are rendered in the Navigation slots.
 *
 * @module     local_course_banner_builder/easyedu_navigation_guide
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['local_course_banner_builder/easyedu_navigation'], function(Navigation) {

const bridges = new WeakMap();

const resolveRoot = target => {
    if (target && target.nodeType === Node.ELEMENT_NODE) {
        return target;
    }
    if (typeof target === 'string') {
        return document.querySelector(target);
    }
    return document.querySelector('[data-easyedu-navigation]');
};

const preserveThemeTokens = guideRoot => {
    const computed = window.getComputedStyle(guideRoot);
    const originals = [];
    const copied = new Set();

    for (let index = 0; index < computed.length; index++) {
        const property = computed.item(index);
        if (!property.startsWith('--easyedu-') || copied.has(property)) {
            continue;
        }
        copied.add(property);
        originals.push({
            name: property,
            priority: guideRoot.style.getPropertyPriority(property),
            value: guideRoot.style.getPropertyValue(property),
        });
        guideRoot.style.setProperty(property, computed.getPropertyValue(property).trim());
    }

    return () => originals.forEach(original => {
        if (original.value) {
            guideRoot.style.setProperty(original.name, original.value, original.priority);
        } else {
            guideRoot.style.removeProperty(original.name);
        }
    });
};

const createLauncher = (sourceLauncher, presentation, activate) => {
    const launcher = sourceLauncher.cloneNode(true);
    launcher.querySelectorAll('[id]').forEach(element => element.removeAttribute('id'));
    launcher.removeAttribute('id');
    launcher.removeAttribute('aria-controls');
    launcher.removeAttribute('aria-expanded');
    launcher.removeAttribute('data-easyedu-guide-open');
    launcher.removeAttribute('hidden');
    launcher.setAttribute('data-easyedu-navigation-guide-launcher', presentation);
    launcher.addEventListener('click', activate);
    return launcher;
};

const bindBridge = (navigationRoot, navigationController) => {
    if (!navigationRoot || bridges.has(navigationRoot) || !document.body) {
        return navigationRoot ? bridges.get(navigationRoot) || null : null;
    }
    if (!navigationController || typeof navigationController.close !== 'function') {
        return null;
    }

    const source = navigationRoot.querySelector('[data-easyedu-navigation-guide-source]');
    const compactSlot = navigationRoot.querySelector('[data-easyedu-navigation-guide-slot]');
    const guideRoot = source ? source.querySelector('[data-easyedu-guide-root]') : null;
    const sourceLauncher = guideRoot ? guideRoot.querySelector('[data-easyedu-guide-open]') : null;
    if (!source || !compactSlot || !guideRoot || !sourceLauncher) {
        return null;
    }

    const marker = document.createComment('easyedu-navigation-guide-portal');
    const sourceLauncherWasHidden = sourceLauncher.hasAttribute('hidden');
    const portalAttribute = guideRoot.getAttribute('data-easyedu-guide-portalled');
    const restoreThemeTokens = preserveThemeTokens(guideRoot);
    guideRoot.parentNode.insertBefore(marker, guideRoot);
    document.body.appendChild(guideRoot);
    guideRoot.setAttribute('data-easyedu-guide-portalled', '1');
    sourceLauncher.hidden = true;

    const activate = presentation => event => {
        event.preventDefault();
        if (presentation === 'compact') {
            navigationController.close(true);
        }
        sourceLauncher.click();
    };
    const desktopLauncher = createLauncher(sourceLauncher, 'desktop', activate('desktop'));
    const compactLauncher = createLauncher(sourceLauncher, 'compact', activate('compact'));
    source.appendChild(desktopLauncher);
    compactSlot.appendChild(compactLauncher);

    const teardown = () => {
        desktopLauncher.remove();
        compactLauncher.remove();
        if (marker.parentNode) {
            marker.parentNode.insertBefore(guideRoot, marker);
            marker.remove();
        }
        sourceLauncher.hidden = sourceLauncherWasHidden;
        if (portalAttribute === null) {
            guideRoot.removeAttribute('data-easyedu-guide-portalled');
        } else {
            guideRoot.setAttribute('data-easyedu-guide-portalled', portalAttribute);
        }
        restoreThemeTokens();
        bridges.delete(navigationRoot);
    };

    const controller = {destroy: teardown};
    bridges.set(navigationRoot, controller);
    return controller;
};

const destroy = target => {
    const root = resolveRoot(target);
    const controller = root ? bridges.get(root) : null;
    if (controller) {
        controller.destroy();
    }
};

const init = (target, navigationController) => bindBridge(
    resolveRoot(target),
    navigationController || Navigation.init(target)
);

return {destroy: destroy, init: init};
});
