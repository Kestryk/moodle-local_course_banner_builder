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
 * Initialise the slideshow administration page interactions.
 *
 * @module     local_course_banner_builder/slideshow_admin
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function () {

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('change', function (e) {
        var formatInput = e.target && e.target.closest ?
            e.target.closest('[data-banner-format-modal="1"] input[name="bannerformat"]') :
            null;
        if (!formatInput) {
            return;
        }
        var modal = formatInput.closest('[data-banner-format-modal="1"]');
        if (!modal) {
            return;
        }
        Array.prototype.slice.call(modal.querySelectorAll('.local-course-banner-builder-format-card')).forEach(function (card) {
            card.classList.remove('is-selected');
        });
        var selectedCard = formatInput.closest('.local-course-banner-builder-format-card');
        if (selectedCard) {
            selectedCard.classList.add('is-selected');
        }
    });
    var activeTip = null;
    var removeTip = function () {
        if (activeTip) {
            activeTip.remove();
            activeTip = null;
        }
    };
    var showTip = function (node) {
        removeTip();
        var content = node.getAttribute('data-content') || '';
        if (!content) {
            return;
        }
        activeTip = document.createElement('div');
        activeTip.className = 'popover local-course-banner-builder-hover-popover local-course-banner-builder-hover-popover--top show';
        activeTip.setAttribute('role', 'tooltip');
        activeTip.innerHTML = '<div class="popover-arrow"></div><div class="popover-body">' + content + '</div>';
        document.body.appendChild(activeTip);
        var rect = node.getBoundingClientRect();
        var tiprect = activeTip.getBoundingClientRect();
        var top = window.scrollY + rect.top - tiprect.height - 10;
        var left = window.scrollX + rect.left + ((rect.width - tiprect.width) / 2);
        activeTip.style.top = Math.max(window.scrollY + 8, top) + 'px';
        activeTip.style.left = Math.max(window.scrollX + 8, Math.min(window.scrollX + window.innerWidth - tiprect.width - 8, left)) + 'px';
    };
    document.querySelectorAll('[data-local-slideshow-help="1"]').forEach(function (node) {
        node.addEventListener('mouseenter', function () { showTip(node); });
        node.addEventListener('focus', function () { showTip(node); });
        node.addEventListener('mouseleave', removeTip);
        node.addEventListener('blur', removeTip);
        node.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    document.querySelectorAll('[data-local-slideshow-action-help="1"]').forEach(function (node) {
        node.addEventListener('mouseenter', function () { showTip(node); });
        node.addEventListener('mouseleave', removeTip);
        node.addEventListener('click', function () { removeTip(); });
    });
    document.querySelectorAll('[data-local-slideshow-toggle-button="1"]').forEach(function (button) {
        var input = document.querySelector(button.getAttribute('data-target-input'));
        var sync = function () {
            var enabled = input && input.value === '1';
            button.classList.toggle('btn-primary', enabled);
            button.classList.toggle('btn-outline-secondary', !enabled);
            button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
            var icon = button.querySelector('i');
            if (icon) {
                icon.className = 'fa ' + (enabled ? 'fa-toggle-on' : 'fa-toggle-off') + ' me-2';
            }
            var label = button.querySelector('span');
            if (label) {
                label.textContent = enabled ? button.getAttribute('data-label-on') : button.getAttribute('data-label-off');
            }
            var warning = input && input.form ? input.form.querySelector('[data-slideshow-banner-warning="1"]') : null;
            if (warning) {
                warning.hidden = !(enabled && warning.getAttribute('data-banner-active') === '0');
            }
        };
        button.addEventListener('click', function () {
            if (!input) {
                return;
            }
            input.value = input.value === '1' ? '0' : '1';
            sync();
        });
        sync();
    });
    var syncColourInput = function (input) {
        if (!input || input.type !== 'color') {
            return;
        }
        var value = /^#[0-9a-f]{6}$/i.test(input.value || '') ? input.value : '#000000';
        input.style.setProperty('--local-course-banner-builder-selected-color', value);
        input.style.backgroundColor = value;
    };
    document.querySelectorAll('[data-slideshow-color-input="1"]').forEach(function (colorInput) {
        var textInput = document.querySelector(colorInput.getAttribute('data-hex-target'));
        var normalise = function (value) {
            value = (value || '').trim();
            if (value.charAt(0) !== '#') {
                value = '#' + value;
            }
            return /^#[0-9a-fA-F]{6}$/.test(value) ? value.toUpperCase() : '';
        };
        if (textInput) {
            colorInput.addEventListener('input', function () {
                textInput.value = colorInput.value.toUpperCase();
                syncColourInput(colorInput);
            });
            textInput.addEventListener('input', function () {
                var value = normalise(textInput.value);
                if (value) {
                    colorInput.value = value;
                    syncColourInput(colorInput);
                    colorInput.dispatchEvent(new Event('input', {bubbles: true}));
                }
            });
        }
        colorInput.addEventListener('change', function () {
            syncColourInput(colorInput);
        });
        syncColourInput(colorInput);
    });
    document.querySelectorAll('input[type="color"]').forEach(syncColourInput);
    var slideshowSyncShadowVector = function (root, target) {
        if (!root || !target) {
            return;
        }
        var distanceInput = root.querySelector('[name="' + target + 'shadowdistance"]');
        var directionInput = root.querySelector('[name="' + target + 'shadowdirection"]');
        var distance = parseFloat(distanceInput && distanceInput.value ? distanceInput.value : '0') || 0;
        var direction = ((parseFloat(directionInput && directionInput.value ? directionInput.value : '90') || 0) * Math.PI) / 180;
        root.querySelectorAll('[data-slideshow-overlay-preview="1"]').forEach(function (preview) {
            preview.style.setProperty(
                '--local-course-banner-builder-slideshow-' + target + '-shadow-x',
                (Math.cos(direction) * distance).toFixed(2) + 'px'
            );
            preview.style.setProperty(
                '--local-course-banner-builder-slideshow-' + target + '-shadow-y',
                (Math.sin(direction) * distance).toFixed(2) + 'px'
            );
        });
        if (target === 'label') {
            root.querySelectorAll('[data-slideshow-label-sample="1"]').forEach(function (sample) {
                sample.style.setProperty(
                    '--local-course-banner-builder-slideshow-label-shadow-x',
                    (Math.cos(direction) * distance).toFixed(2) + 'px'
                );
                sample.style.setProperty(
                    '--local-course-banner-builder-slideshow-label-shadow-y',
                    (Math.sin(direction) * distance).toFixed(2) + 'px'
                );
            });
        }
    };
    var slideshowSyncDesignInput = function (input) {
        if (!input) {
            return;
        }
        var root = input.closest('[data-slideshow-overlay-settings="1"]');
        var syncLocalDesignValue = function (source) {
            var unit = source.getAttribute('data-slideshow-design-unit') || '';
            var outputSelector = source.getAttribute('data-slideshow-design-output') || '';
            var output = outputSelector ? document.querySelector(outputSelector) : null;
            var numberSelector = source.getAttribute('data-slideshow-design-number') || '';
            var number = numberSelector ? document.querySelector(numberSelector) : null;
            if (output) {
                output.textContent = (source.value || '0') + unit;
            }
            if (number && number !== document.activeElement) {
                number.value = source.value || '0';
            }
        };
        syncLocalDesignValue(input);
        var proxyFor = input.getAttribute('data-slideshow-side-proxy-for');
        if (proxyFor && root) {
            var realInput = root.querySelector('[name="' + proxyFor + '"]');
            if (realInput && realInput !== input) {
                realInput.value = input.value;
                realInput.dispatchEvent(new Event('input', {bubbles: true}));
                realInput.dispatchEvent(new Event('change', {bubbles: true}));
            }
        }
        var variable = input.getAttribute('data-slideshow-design-var');
        if (!root || !variable) {
            return;
        }
        var unit = input.getAttribute('data-slideshow-design-unit') || '';
        var value = input.value || '';
        if (variable.indexOf('label-text-scale') !== -1) {
            value = (Math.max(25, Math.min(160, parseFloat(value || '100'))) / 100).toFixed(2);
        } else if (variable.indexOf('-opacity') !== -1) {
            value = (Math.max(0, Math.min(100, parseFloat(value || '0'))) / 100).toFixed(2);
        } else if (unit) {
            value = value + unit;
        }
        root.querySelectorAll('[data-slideshow-overlay-preview="1"]').forEach(function (preview) {
            preview.style.setProperty(variable, value);
            if (variable.indexOf('shadow-color') !== -1) {
                var raw = String(value || '#000000').replace('#', '');
                var r = parseInt(raw.substring(0, 2), 16) || 0;
                var g = parseInt(raw.substring(2, 4), 16) || 0;
                var b = parseInt(raw.substring(4, 6), 16) || 0;
                preview.style.setProperty(
                    variable.replace('shadow-color', 'shadow-rgb'),
                    r + ', ' + g + ', ' + b
                );
            }
        });
        if (variable.indexOf('--local-course-banner-builder-slideshow-label-') === 0) {
            root.querySelectorAll('[data-slideshow-label-sample="1"]').forEach(function (sample) {
                sample.style.setProperty(variable, value);
            });
        }
        if (variable.indexOf('shadow-distance') !== -1 || variable.indexOf('shadow-direction') !== -1) {
            slideshowSyncShadowVector(root, variable.indexOf('-label-') !== -1 ? 'label' : 'action');
        }
    };
    document.querySelectorAll('[data-slideshow-design-number-for]').forEach(function (number) {
        var range = document.querySelector(number.getAttribute('data-slideshow-design-number-for') || '');
        number.addEventListener('input', function () {
            if (!range) {
                return;
            }
            var min = parseFloat(range.getAttribute('min') || '0');
            var max = parseFloat(range.getAttribute('max') || '100');
            var value = Math.max(min, Math.min(max, parseFloat(number.value || '0')));
            range.value = value;
            slideshowSyncDesignInput(range);
        });
    });
    document.querySelectorAll('[data-slideshow-side-proxy-for]').forEach(function (input) {
        var root = input.closest('[data-slideshow-overlay-settings="1"]');
        var realInput = root ? root.querySelector('[name="' + input.getAttribute('data-slideshow-side-proxy-for') + '"]') : null;
            if (realInput) {
                input.value = realInput.value || input.value;
                if (!input.getAttribute('data-default-value') && realInput.getAttribute('data-default-value')) {
                    input.setAttribute('data-default-value', realInput.getAttribute('data-default-value'));
                }
                if (input.type === 'color') {
                    syncColourInput(input);
                }
        }
        input.addEventListener('input', function () {
            slideshowSyncDesignInput(input);
        });
        input.addEventListener('change', function () {
            slideshowSyncDesignInput(input);
        });
    });
    document.querySelectorAll('[data-slideshow-design-input="1"]').forEach(function (input) {
        input.addEventListener('input', function () {
            slideshowSyncDesignInput(input);
        });
        input.addEventListener('change', function () {
            slideshowSyncDesignInput(input);
        });
        slideshowSyncDesignInput(input);
    });
    var slideshowSetSidePanelVisible = function (panel, visible) {
        if (!panel) {
            return;
        }
        if (panel.dataset.slideshowPanelTimer) {
            window.clearTimeout(parseInt(panel.dataset.slideshowPanelTimer, 10));
            delete panel.dataset.slideshowPanelTimer;
        }
        panel.dataset.slideshowPanelVisible = visible ? '1' : '0';
        if (visible) {
            panel.hidden = false;
            window.requestAnimationFrame(function () {
                panel.classList.remove('is-collapsed');
            });
            return;
        }
        panel.classList.add('is-collapsed');
        panel.dataset.slideshowPanelTimer = String(window.setTimeout(function () {
            if (panel.classList.contains('is-collapsed')) {
                panel.hidden = true;
            }
            delete panel.dataset.slideshowPanelTimer;
        }, 300));
    };
    var slideshowGetSidePanelRoot = function (node) {
        if (!node || !node.closest) {
            return null;
        }
        return node.closest('[data-slideshow-overlay-settings="1"]') ||
            node.closest('.local-course-banner-builder-slideshow-preview-modal') ||
            node.closest('.modal');
    };
    var slideshowSyncSidePanelButtons = function (root) {
        if (!root) {
            return;
        }
        root.querySelectorAll('[data-action="local-course-banner-builder-toggle-slideshow-side-panel"]').forEach(function (button) {
            var target = button.getAttribute('data-slideshow-side-panel-target');
            var panel = target ? root.querySelector('[data-slideshow-side-panel="' + target + '"]') : null;
            var active = !!(panel && (
                panel.dataset.slideshowPanelVisible === '1' ||
                (!panel.hidden && !panel.classList.contains('is-collapsed'))
            ));
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.classList.toggle('active', active);
            button.classList.toggle('local-course-banner-builder-source-preview-button--active', active);
            button.setAttribute('aria-expanded', active ? 'true' : 'false');
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };
    document.addEventListener('click', function (e) {
        var sideButton = e.target.closest('[data-action="local-course-banner-builder-toggle-slideshow-side-panel"]');
        if (!sideButton) {
            return;
        }
        e.preventDefault();
        var root = slideshowGetSidePanelRoot(sideButton);
        var target = sideButton.getAttribute('data-slideshow-side-panel-target');
        if (!root || !target) {
            return;
        }
        root.querySelectorAll('[data-slideshow-side-panel]').forEach(function (panel) {
            var isTarget = panel.getAttribute('data-slideshow-side-panel') === target;
            var isOpen = panel.dataset.slideshowPanelVisible === '1' ||
                (!panel.hidden && !panel.classList.contains('is-collapsed'));
            slideshowSetSidePanelVisible(panel, isTarget && !isOpen);
        });
        slideshowSyncSidePanelButtons(root);
    });
    document.querySelectorAll('[data-slideshow-overlay-settings="1"]').forEach(function (panel) {
        var color = panel.querySelector('[data-slideshow-overlay-color="1"]');
        var opacity = panel.querySelector('[data-slideshow-overlay-opacity="1"]');
        var previews = Array.prototype.slice.call(panel.querySelectorAll('[data-slideshow-overlay-preview="1"]'));
        var output = panel.querySelector('[data-slideshow-overlay-opacity-output="1"]');
        var sync = function () {
            if (!previews.length || !color || !opacity) {
                return;
            }
            var hex = color.value || '#000000';
            var raw = hex.replace('#', '');
            var r = parseInt(raw.substring(0, 2), 16) || 0;
            var g = parseInt(raw.substring(2, 4), 16) || 0;
            var b = parseInt(raw.substring(4, 6), 16) || 0;
            var percent = Math.max(0, Math.min(85, parseInt(opacity.value || '38', 10)));
            previews.forEach(function (preview) {
                preview.style.setProperty('--local-course-banner-builder-slideshow-overlay-rgb', r + ', ' + g + ', ' + b);
                preview.style.setProperty('--local-course-banner-builder-slideshow-overlay-opacity', (percent / 100).toFixed(2));
            });
            if (output) {
                output.textContent = percent + '%';
            }
        };
        if (color) {
            color.addEventListener('input', sync);
        }
        if (opacity) {
            opacity.addEventListener('input', sync);
        }
        sync();
    });
    document.querySelectorAll('[data-slideshow-label-color-settings="1"]').forEach(function (panel) {
        var sync = function (input) {
            if (!input) {
                return;
            }
            var variable = input.getAttribute('data-slideshow-label-var');
            var context = input.closest('[data-slideshow-overlay-settings="1"]') || document;
            if (!variable) {
                return;
            }
            context.querySelectorAll('[data-slideshow-overlay-preview="1"]').forEach(function (preview) {
                preview.style.setProperty(variable, input.value || '#000000');
                if (variable.indexOf('-shadow') !== -1) {
                    var raw = String(input.value || '#000000').replace('#', '');
                    var r = parseInt(raw.substring(0, 2), 16) || 0;
                    var g = parseInt(raw.substring(2, 4), 16) || 0;
                    var b = parseInt(raw.substring(4, 6), 16) || 0;
                    preview.style.setProperty(variable + '-rgb', r + ', ' + g + ', ' + b);
                }
            });
            var row = input.closest('.local-course-banner-builder-slideshow-label-color-row');
            var sample = row ? row.querySelector('[data-slideshow-label-sample="1"]') : null;
            if (sample) {
                sample.style.setProperty(variable, input.value || '#000000');
                if (variable.indexOf('-shadow') !== -1) {
                    var sampleRaw = String(input.value || '#000000').replace('#', '');
                    var sampleR = parseInt(sampleRaw.substring(0, 2), 16) || 0;
                    var sampleG = parseInt(sampleRaw.substring(2, 4), 16) || 0;
                    var sampleB = parseInt(sampleRaw.substring(4, 6), 16) || 0;
                    sample.style.setProperty(variable + '-rgb', sampleR + ', ' + sampleG + ', ' + sampleB);
                }
            }
        };
        panel.querySelectorAll('[data-slideshow-label-var]').forEach(function (input) {
            input.addEventListener('input', function () {
                sync(input);
            });
            sync(input);
        });
    });
    document.querySelectorAll('[data-slideshow-text-settings="1"]').forEach(function (panel) {
        var availableFonts = [
            'Arial', 'Trebuchet MS', 'Verdana', 'Tahoma', 'Georgia', 'Times New Roman', 'Garamond',
            'Palatino Linotype', 'Segoe UI', 'Helvetica Neue', 'Courier New', 'Lucida Console',
            'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Merriweather', 'Playfair Display'
        ];
        var getFormatSizeScale = function (format, kind) {
            if (format === 'standard') {
                return 1.24;
            }
            if (format === 'fullwidthtopcompact' || format === 'fullwidthtopinset') {
                if (kind === 'label') {
                    return 1;
                }
                return 0.78;
            }
            return 1;
        };
        var buildTitleSize = function (percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'title');
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqh, min(' + (28 * scale).toFixed(3) +
                'cqh, ' + (3.4 * scale).toFixed(3) + 'cqw), ' + (36 * scale).toFixed(3) + 'cqh)';
        };
        var buildBodySize = function (percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'body');
            return 'clamp(' + (5.5 * scale).toFixed(3) + 'cqh, min(' + (14 * scale).toFixed(3) +
                'cqh, ' + (1.7 * scale).toFixed(3) + 'cqw), ' + (19 * scale).toFixed(3) + 'cqh)';
        };
        var buildLabelSize = function (percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'label');
            return 'clamp(' + (3.5 * scale).toFixed(3) + 'cqh, min(' + (6.4 * scale).toFixed(3) +
                'cqh, ' + (0.82 * scale).toFixed(3) + 'cqw), ' + (8.4 * scale).toFixed(3) + 'cqh)';
        };
        var buildActionSize = function (percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'action');
            return 'clamp(' + (6 * scale).toFixed(3) + 'cqh, min(' + (13 * scale).toFixed(3) +
                'cqh, ' + (1.6 * scale).toFixed(3) + 'cqw), ' + (18 * scale).toFixed(3) + 'cqh)';
        };
        var buildActionWidth = function (percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'actionwidth');
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqw, ' + (18 * scale).toFixed(3) + 'cqw, ' + (34 * scale).toFixed(3) + 'cqw)';
        };
        var buildActionHeight = function (percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'actionheight');
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqh, min(' + (22 * scale).toFixed(3) +
                'cqh, ' + (2.7 * scale).toFixed(3) + 'cqw), ' + (34 * scale).toFixed(3) + 'cqh)';
        };
        var sync = function (input) {
            var variable = input.getAttribute('data-slideshow-text-var');
            var previews = Array.prototype.slice.call(panel.closest('[data-slideshow-overlay-settings="1"]').querySelectorAll('[data-slideshow-overlay-preview="1"]'));
            var output = panel.querySelector('[data-slideshow-text-output-for="' + input.name + '"]');
            var number = panel.querySelector('[data-slideshow-size-number-for="' + input.name + '"]');
            var value = input.value || '';
            if (output) {
                output.textContent = input.value + '%';
            }
            if (number && number !== document.activeElement) {
                number.value = input.value;
            }
            previews.forEach(function (preview) {
                var format = preview.getAttribute('data-banner-format') || '';
                if (input.getAttribute('data-slideshow-text-size') === 'title') {
                    value = buildTitleSize(input.value, format);
                } else if (input.getAttribute('data-slideshow-text-size') === 'body') {
                    value = buildBodySize(input.value, format);
                } else if (input.getAttribute('data-slideshow-text-size') === 'label') {
                    value = buildLabelSize(input.value, format);
                } else if (input.getAttribute('data-slideshow-text-size') === 'action') {
                    value = buildActionSize(input.value, format);
                } else if (input.getAttribute('data-slideshow-text-size') === 'actionwidth') {
                    value = buildActionWidth(input.value, format);
                } else if (input.getAttribute('data-slideshow-text-size') === 'actionheight') {
                    value = buildActionHeight(input.value, format);
                }
                preview.style.setProperty(variable, value);
            });
            if (input.getAttribute('data-slideshow-font-family') === '1') {
                input.style.fontFamily = input.value || 'inherit';
            }
            if (input.getAttribute('data-slideshow-text-size') === 'actionwidth' ||
                    input.getAttribute('data-slideshow-text-size') === 'actionheight') {
                return;
            }
        };
        panel.querySelectorAll('[data-slideshow-text-var]').forEach(function (input) {
            input.addEventListener('input', function () {
                sync(input);
            });
            input.addEventListener('change', function () {
                sync(input);
            });
            sync(input);
        });
        panel.querySelectorAll('[data-slideshow-size-number-for]').forEach(function (input) {
            var range = panel.querySelector('[name="' + input.getAttribute('data-slideshow-size-number-for') + '"]');
            input.addEventListener('input', function () {
                if (!range) {
                    return;
                }
                var value = Math.max(25, Math.min(100, parseInt(input.value || '100', 10)));
                range.value = value;
                sync(range);
            });
        });
        panel.querySelectorAll('[data-slideshow-font-family="1"]').forEach(function (select) {
            if (!document.fonts || typeof document.fonts.check !== 'function') {
                return;
            }
            Array.prototype.slice.call(select.options).forEach(function (option) {
                var raw = option.getAttribute('data-font-value') || '';
                if (!raw) {
                    return;
                }
                var family = availableFonts.find(function (candidate) {
                    return raw.indexOf(candidate) !== -1;
                });
                if (!family) {
                    return;
                }
                option.disabled = !document.fonts.check('16px "' + family + '"') && !document.fonts.check('16px ' + family);
            });
        });
    });
    var slideshowPreviewDefaults = {
        label: {x: 14, y: 10},
        title: {x: 50, y: 24},
        body: {x: 50, y: 54},
        action: {x: 50, y: 84}
    };
    var slideshowPreviewSelectionClass = 'local-course-banner-builder-slideshow-preview-draggable--selected';
    var slideshowPreviewDrag = null;
    var slideshowPreviewSizeInputs = {
        label: 'labelsize',
        title: 'titlefontsize',
        body: 'bodyfontsize',
        action: 'actionsize'
    };
    var slideshowPreviewReadJson = function (value, fallback) {
        try {
            return value ? JSON.parse(value) : fallback;
        } catch (error) {
            return fallback;
        }
    };
    var slideshowPreviewGetPreview = function (scope) {
        var root = scope && scope.closest ? scope.closest('[data-slideshow-overlay-settings="1"]') : null;
        return root ? root.querySelector('[data-slideshow-overlay-preview="1"][data-slideshow-preview-editor="1"]') : null;
    };
    var slideshowPreviewApplyPosition = function (preview, key, x, y) {
        if (!preview) {
            return;
        }
        x = Math.max(0, Math.min(100, parseFloat(x || 0)));
        y = Math.max(0, Math.min(100, parseFloat(y || 0)));
        preview.style.setProperty('--local-course-banner-builder-slideshow-' + key + '-x', x.toFixed(3) + '%');
        preview.style.setProperty('--local-course-banner-builder-slideshow-' + key + '-y', y.toFixed(3) + '%');
        var root = preview.closest('[data-slideshow-overlay-settings="1"]');
        if (root) {
            var inputX = root.querySelector('[data-slideshow-position-input="' + key + '-x"]');
            var inputY = root.querySelector('[data-slideshow-position-input="' + key + '-y"]');
            if (inputX) {
                inputX.value = x.toFixed(3);
            }
            if (inputY) {
                inputY.value = y.toFixed(3);
            }
        }
    };
    var slideshowNormaliseAlignment = function (value) {
        return ['left', 'center', 'right'].indexOf(value) !== -1 ? value : 'center';
    };
    var slideshowAlignmentTranslateX = function (value) {
        value = slideshowNormaliseAlignment(value);
        return '-50%';
    };
    var slideshowAlignmentFlexValue = function (value) {
        value = slideshowNormaliseAlignment(value);
        if (value === 'left') {
            return 'flex-start';
        }
        if (value === 'right') {
            return 'flex-end';
        }
        return 'center';
    };
    var slideshowSyncSideAlignmentButtons = function (panel, target) {
        if (!panel) {
            return;
        }
        var input = panel.querySelector('[data-slideshow-alignment-input="' + target + '"]');
        var value = input ? slideshowNormaliseAlignment(input.value || 'center') : 'center';
        panel.querySelectorAll('[data-slideshow-align-target="' + target + '"]').forEach(function (button) {
            var active = button.getAttribute('data-slideshow-align') === value;
            button.classList.toggle('active', active);
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };
    var slideshowApplyAlignment = function (panel, target, value) {
        if (!panel) {
            return;
        }
        target = ['title', 'body', 'label'].indexOf(target) !== -1 ? target : 'title';
        value = slideshowNormaliseAlignment(value);
        var variable = target === 'label'
            ? '--local-course-banner-builder-slideshow-label-items-align'
            : '--local-course-banner-builder-slideshow-' + target + '-text-align';
        var cssvalue = target === 'label' ? slideshowAlignmentFlexValue(value) : value;
        panel.querySelectorAll('[data-slideshow-overlay-preview="1"]').forEach(function (preview) {
            preview.style.setProperty(variable, cssvalue);
            if (target === 'label') {
                preview.style.setProperty(
                    '--local-course-banner-builder-slideshow-label-translate-x',
                    slideshowAlignmentTranslateX(value)
                );
            }
        });
        var input = panel.querySelector('[data-slideshow-alignment-input="' + target + '"]');
        if (input) {
            input.value = value;
        }
        slideshowSyncSideAlignmentButtons(panel, target);
        var preview = panel.querySelector('[data-slideshow-overlay-preview="1"][data-slideshow-preview-editor="1"]');
        if (preview) {
            slideshowPreviewSyncButtons(preview);
        }
    };
    var slideshowPreviewGuideThreshold = 5;
    var slideshowPreviewGuideMargin = 12;
    var slideshowPreviewEnsureGuideLayer = function (preview) {
        if (!preview) {
            return null;
        }
        var layer = preview.querySelector(':scope > [data-preview-guides-layer="1"]');
        if (!layer) {
            layer = document.createElement('div');
            layer.className = 'local-course-banner-builder-preview-guides';
            layer.setAttribute('data-preview-guides-layer', '1');
            layer.setAttribute('aria-hidden', 'true');
            preview.appendChild(layer);
        }
        return layer;
    };
    var slideshowPreviewClearGuides = function (preview) {
        var layer = preview ? preview.querySelector(':scope > [data-preview-guides-layer="1"]') : null;
        if (layer) {
            layer.innerHTML = '';
            layer.hidden = true;
        }
    };
    var slideshowPreviewIsSnapEnabled = function (preview) {
        var panel = preview ? preview.closest('[data-slideshow-overlay-settings="1"]') : null;
        return !(panel && panel.getAttribute('data-preview-snap-enabled') === '0');
    };
    var slideshowPreviewSyncSnapButtons = function (scope) {
        var root = scope || document;
        Array.prototype.slice.call(root.querySelectorAll('[data-action="local-course-banner-builder-toggle-slideshow-preview-snap"]')).forEach(function (button) {
            var preview = slideshowPreviewGetPreview(button);
            var enabled = slideshowPreviewIsSnapEnabled(preview);
            button.classList.remove('btn-primary');
            button.classList.remove('active');
            button.classList.remove('local-course-banner-builder-source-preview-button--active');
            button.classList.add('btn-outline-secondary');
            button.classList.toggle('local-course-banner-builder-preview-snap-disabled', !enabled);
            button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        });
    };
    var slideshowPreviewPulseButton = function (button) {
        if (!button) {
            return;
        }
        button.classList.remove('local-course-banner-builder-preview-action-feedback');
        void button.offsetWidth;
        button.classList.add('local-course-banner-builder-preview-action-feedback');
        window.setTimeout(function () {
            button.classList.remove('local-course-banner-builder-preview-action-feedback');
        }, 260);
    };
    var slideshowPreviewRectInFrame = function (frameRect, node) {
        var rect = node.getBoundingClientRect();
        return {
            left: rect.left - frameRect.left,
            top: rect.top - frameRect.top,
            right: rect.right - frameRect.left,
            bottom: rect.bottom - frameRect.top,
            centerX: rect.left - frameRect.left + (rect.width / 2),
            centerY: rect.top - frameRect.top + (rect.height / 2)
        };
    };
    var slideshowPreviewRectsOverlap = function (a, b, margin) {
        margin = margin || 0;
        return !(a.right + margin < b.left || a.left - margin > b.right ||
            a.bottom + margin < b.top || a.top - margin > b.bottom);
    };
    var slideshowPreviewAddGuide = function (layer, orientation, position, kind) {
        if (!layer || !isFinite(position)) {
            return;
        }
        var line = document.createElement('span');
        line.className = 'local-course-banner-builder-preview-guide local-course-banner-builder-preview-guide--' +
            orientation + ' local-course-banner-builder-preview-guide--' + kind;
        if (orientation === 'vertical') {
            line.style.left = position.toFixed(2) + 'px';
        } else {
            line.style.top = position.toFixed(2) + 'px';
        }
        layer.appendChild(line);
    };
    var slideshowPreviewMaybeAddGuide = function (layer, orientation, activeValue, targetValue, kind, seen) {
        if (Math.abs(activeValue - targetValue) > slideshowPreviewGuideThreshold) {
            return;
        }
        var key = orientation + ':' + Math.round(targetValue) + ':' + kind;
        if (seen[key]) {
            return;
        }
        seen[key] = true;
        slideshowPreviewAddGuide(layer, orientation, targetValue, kind);
    };
    var slideshowPreviewSnapCandidate = function (best, axis, delta, priority) {
        if (Math.abs(delta) > slideshowPreviewGuideThreshold) {
            return best;
        }
        var current = best[axis];
        if (!current || priority > current.priority ||
                (priority === current.priority && Math.abs(delta) < Math.abs(current.delta))) {
            best[axis] = {delta: delta, priority: priority};
        }
        return best;
    };
    var slideshowPreviewFindSnap = function (preview, active, rawRect) {
        if (!preview || !active || !rawRect) {
            return {dx: 0, dy: 0};
        }
        var frameRect = preview.getBoundingClientRect();
        var best = {};
        best = slideshowPreviewSnapCandidate(best, 'x', (frameRect.width / 2) - rawRect.centerX, 3);
        best = slideshowPreviewSnapCandidate(best, 'y', (frameRect.height / 2) - rawRect.centerY, 3);
        Array.prototype.slice.call(preview.querySelectorAll('[data-slideshow-preview-draggable]')).forEach(function (target) {
            if (target === active) {
                return;
            }
            var targetRect = slideshowPreviewRectInFrame(frameRect, target);
            if (slideshowPreviewRectsOverlap(rawRect, targetRect, slideshowPreviewGuideMargin)) {
                return;
            }
            [
                {value: rawRect.centerX, priority: 2},
                {value: rawRect.left, priority: 1},
                {value: rawRect.right, priority: 1}
            ].forEach(function (activeValue) {
                [
                    {value: targetRect.centerX, priority: activeValue.priority === 2 ? 2 : 1},
                    {value: targetRect.left, priority: 1},
                    {value: targetRect.right, priority: 1}
                ].forEach(function (targetValue) {
                    best = slideshowPreviewSnapCandidate(
                        best,
                        'x',
                        targetValue.value - activeValue.value,
                        Math.min(activeValue.priority, targetValue.priority)
                    );
                });
            });
            [
                {value: rawRect.centerY, priority: 2},
                {value: rawRect.top, priority: 1},
                {value: rawRect.bottom, priority: 1}
            ].forEach(function (activeValue) {
                [
                    {value: targetRect.centerY, priority: activeValue.priority === 2 ? 2 : 1},
                    {value: targetRect.top, priority: 1},
                    {value: targetRect.bottom, priority: 1}
                ].forEach(function (targetValue) {
                    best = slideshowPreviewSnapCandidate(
                        best,
                        'y',
                        targetValue.value - activeValue.value,
                        Math.min(activeValue.priority, targetValue.priority)
                    );
                });
            });
        });
        return {dx: best.x ? best.x.delta : 0, dy: best.y ? best.y.delta : 0};
    };
    var slideshowPreviewUpdateGuides = function (preview, active) {
        if (!preview || !active) {
            slideshowPreviewClearGuides(preview);
            return;
        }
        var layer = slideshowPreviewEnsureGuideLayer(preview);
        if (!layer) {
            return;
        }
        layer.innerHTML = '';
        layer.hidden = false;
        var frameRect = preview.getBoundingClientRect();
        var activeRect = slideshowPreviewRectInFrame(frameRect, active);
        var seen = {};
        slideshowPreviewMaybeAddGuide(
            layer, 'vertical', activeRect.centerX, frameRect.width / 2,
            'frame local-course-banner-builder-preview-guide--center', seen
        );
        slideshowPreviewMaybeAddGuide(
            layer, 'horizontal', activeRect.centerY, frameRect.height / 2,
            'frame local-course-banner-builder-preview-guide--center', seen
        );
        Array.prototype.slice.call(preview.querySelectorAll('[data-slideshow-preview-draggable]')).forEach(function (target) {
            if (target === active) {
                return;
            }
            var targetRect = slideshowPreviewRectInFrame(frameRect, target);
            if (slideshowPreviewRectsOverlap(activeRect, targetRect, slideshowPreviewGuideMargin)) {
                return;
            }
            [
                {value: activeRect.left, type: 'edge'},
                {value: activeRect.centerX, type: 'center'},
                {value: activeRect.right, type: 'edge'}
            ].forEach(function (activeValue) {
                [
                    {value: targetRect.left, type: 'edge'},
                    {value: targetRect.centerX, type: 'center'},
                    {value: targetRect.right, type: 'edge'}
                ].forEach(function (targetValue) {
                    slideshowPreviewMaybeAddGuide(
                        layer,
                        'vertical',
                        activeValue.value,
                        targetValue.value,
                        'peer local-course-banner-builder-preview-guide--' +
                            (activeValue.type === 'center' && targetValue.type === 'center' ? 'center' : 'edge'),
                        seen
                    );
                });
            });
            [
                {value: activeRect.top, type: 'edge'},
                {value: activeRect.centerY, type: 'center'},
                {value: activeRect.bottom, type: 'edge'}
            ].forEach(function (activeValue) {
                [
                    {value: targetRect.top, type: 'edge'},
                    {value: targetRect.centerY, type: 'center'},
                    {value: targetRect.bottom, type: 'edge'}
                ].forEach(function (targetValue) {
                    slideshowPreviewMaybeAddGuide(
                        layer,
                        'horizontal',
                        activeValue.value,
                        targetValue.value,
                        'peer local-course-banner-builder-preview-guide--' +
                            (activeValue.type === 'center' && targetValue.type === 'center' ? 'center' : 'edge'),
                        seen
                    );
                });
            });
        });
        if (!layer.children.length) {
            layer.hidden = true;
        }
    };
    var slideshowPreviewApplySize = function (preview, key, value, edge) {
        var root = preview ? preview.closest('[data-slideshow-overlay-settings="1"]') : null;
        var inputname = slideshowPreviewSizeInputs[key];
        if (key === 'action' && edge) {
            inputname = (edge === 'left' || edge === 'right') ? 'actionwidth' : 'actionheight';
        }
        if (!root || !inputname) {
            return;
        }
        value = Math.max(25, Math.min(100, Math.round(parseFloat(value || 100))));
            var range = root.querySelector('[name="' + inputname + '"]');
        if (range) {
            range.value = value;
            range.dispatchEvent(new Event('input', {bubbles: true}));
        }
    };
    var slideshowSyncLabelOrientation = function (panel, value) {
        if (!panel) {
            return;
        }
        value = value === 'column' ? 'column' : 'row';
        panel.querySelectorAll('[data-slideshow-overlay-preview="1"]').forEach(function (preview) {
            preview.style.setProperty('--local-course-banner-builder-slideshow-label-orientation', value);
        });
        var input = panel.querySelector('[data-slideshow-label-orientation-input]');
        if (input) {
            input.value = value;
        }
        panel.querySelectorAll('[data-slideshow-label-orientation-option]').forEach(function (button) {
            var active = button.getAttribute('data-slideshow-label-orientation-option') === value;
            button.classList.toggle('active', active);
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        panel.querySelectorAll('[data-action="local-course-banner-builder-toggle-slideshow-label-orientation"] i').forEach(function (icon) {
            icon.className = 'fa ' + (value === 'column' ? 'fa-grip-lines-vertical' : 'fa-grip-lines');
        });
    };
    var slideshowSyncCornerStyle = function (panel, target, value) {
        if (!panel) {
            return;
        }
        target = target === 'label' ? 'label' : 'action';
        value = value === 'square' ? 'square' : 'rounded';
        var variable = target === 'label'
            ? '--local-course-banner-builder-slideshow-label-radius'
            : '--local-course-banner-builder-slideshow-action-radius';
        panel.querySelectorAll('[data-slideshow-overlay-preview="1"]').forEach(function (preview) {
            preview.style.setProperty(variable, value === 'square' ? '0.28rem' : '999px');
        });
        panel.querySelectorAll('[data-slideshow-corner-input="' + target + '"]').forEach(function (input) {
            input.value = value;
        });
        panel.querySelectorAll('[data-slideshow-corner-option="' + target + '"]').forEach(function (button) {
            var active = button.getAttribute('data-slideshow-corner-value') === value;
            button.classList.toggle('active', active);
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        panel.querySelectorAll('[data-slideshow-corner-target="' + target + '"] i').forEach(function (icon) {
            icon.className = 'fa ' + (value === 'square' ? 'fa-square' : 'fa-circle');
        });
        if (target === 'label') {
            panel.querySelectorAll('[data-slideshow-label-sample="1"]').forEach(function (sample) {
                sample.style.setProperty(variable, value === 'square' ? '0.28rem' : '999px');
            });
        }
    };
    var slideshowGetTextDecoration = function (underline, strike) {
        var value = [];
        if (underline) {
            value.push('underline');
        }
        if (strike) {
            value.push('line-through');
        }
        return value.length ? value.join(' ') : 'none';
    };
    var slideshowApplyTextStyle = function (panel, target) {
        if (!panel) {
            return;
        }
        target = ['title', 'body', 'action', 'label'].indexOf(target) !== -1 ? target : 'title';
        var get = function (style) {
            var input = panel.querySelector('[data-slideshow-text-style-input="' + target + style + '"]');
            return input && input.value === '1';
        };
        var bold = get('bold');
        var italic = get('italic');
        var underline = get('underline');
        var strike = get('strike');
        var allcaps = get('allcaps');
        panel.querySelectorAll('[data-slideshow-overlay-preview="1"]').forEach(function (preview) {
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-font-weight',
                bold ? (target === 'title' ? '800' : '700') : '400');
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-font-style',
                italic ? 'italic' : 'normal');
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-text-decoration',
                slideshowGetTextDecoration(underline, strike));
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-text-transform',
                allcaps ? 'uppercase' : 'none');
        });
        ['bold', 'italic', 'underline', 'strike', 'allcaps'].forEach(function (style) {
            var input = panel.querySelector('[data-slideshow-text-style-input="' + target + style + '"]');
            var active = input && input.value === '1';
            panel.querySelectorAll('[data-slideshow-text-style-buttons="' + target + '"] [data-slideshow-text-style="' + style + '"]').forEach(function (button) {
                button.classList.toggle('active', active);
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-secondary', !active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        });
        var preview = panel.querySelector('[data-slideshow-overlay-preview="1"][data-slideshow-preview-editor="1"]');
        if (preview) {
            slideshowPreviewSyncButtons(preview);
        }
    };
    var slideshowPreviewCaptureTextStyles = function (panel) {
        var styles = {};
        if (!panel) {
            return styles;
        }
        panel.querySelectorAll('[data-slideshow-text-style-input]').forEach(function (input) {
            styles[input.getAttribute('data-slideshow-text-style-input')] = input.value === '1' ? '1' : '0';
        });
        return styles;
    };
    var slideshowPreviewApplyTextStyles = function (panel, styles) {
        if (!panel || !styles) {
            return;
        }
        panel.querySelectorAll('[data-slideshow-text-style-input]').forEach(function (input) {
            var key = input.getAttribute('data-slideshow-text-style-input');
            if (Object.prototype.hasOwnProperty.call(styles, key)) {
                input.value = styles[key] === '1' ? '1' : '0';
            }
        });
        ['title', 'body', 'action', 'label'].forEach(function (target) {
            slideshowApplyTextStyle(panel, target);
        });
    };
    var slideshowPreviewCaptureAlignments = function (panel) {
        var alignments = {};
        if (!panel) {
            return alignments;
        }
        panel.querySelectorAll('[data-slideshow-alignment-input]').forEach(function (input) {
            alignments[input.getAttribute('data-slideshow-alignment-input')] =
                slideshowNormaliseAlignment(input.value || 'center');
        });
        return alignments;
    };
    var slideshowPreviewApplyAlignments = function (panel, alignments) {
        if (!panel || !alignments) {
            return;
        }
        ['title', 'body', 'label'].forEach(function (target) {
            if (Object.prototype.hasOwnProperty.call(alignments, target)) {
                slideshowApplyAlignment(panel, target, alignments[target]);
            }
        });
    };
    var slideshowPreviewCaptureState = function (preview) {
        var panel = preview.closest('[data-slideshow-overlay-settings="1"]');
        return {
            label: {
                x: parseFloat((panel.querySelector('[data-slideshow-position-input="label-x"]') || {}).value || slideshowPreviewDefaults.label.x),
                y: parseFloat((panel.querySelector('[data-slideshow-position-input="label-y"]') || {}).value || slideshowPreviewDefaults.label.y)
            },
            title: {
                x: parseFloat((panel.querySelector('[data-slideshow-position-input="title-x"]') || {}).value || slideshowPreviewDefaults.title.x),
                y: parseFloat((panel.querySelector('[data-slideshow-position-input="title-y"]') || {}).value || slideshowPreviewDefaults.title.y)
            },
            body: {
                x: parseFloat((panel.querySelector('[data-slideshow-position-input="body-x"]') || {}).value || slideshowPreviewDefaults.body.x),
                y: parseFloat((panel.querySelector('[data-slideshow-position-input="body-y"]') || {}).value || slideshowPreviewDefaults.body.y)
            },
            action: {
                x: parseFloat((panel.querySelector('[data-slideshow-position-input="action-x"]') || {}).value || slideshowPreviewDefaults.action.x),
                y: parseFloat((panel.querySelector('[data-slideshow-position-input="action-y"]') || {}).value || slideshowPreviewDefaults.action.y)
            },
            textStyles: slideshowPreviewCaptureTextStyles(panel),
            alignments: slideshowPreviewCaptureAlignments(panel)
        };
    };
    var slideshowPreviewApplyState = function (preview, state) {
        ['label', 'title', 'body', 'action'].forEach(function (key) {
            if (state && state[key]) {
                slideshowPreviewApplyPosition(preview, key, state[key].x, state[key].y);
            }
        });
        slideshowPreviewApplyTextStyles(preview.closest('[data-slideshow-overlay-settings="1"]'), state ? state.textStyles : null);
        slideshowPreviewApplyAlignments(preview.closest('[data-slideshow-overlay-settings="1"]'), state ? state.alignments : null);
    };
    var slideshowPreviewSyncButtons = function (preview) {
        if (!preview) {
            return;
        }
        var root = preview.closest('[data-slideshow-overlay-settings="1"]');
        var undo = root.querySelector('[data-action="local-course-banner-builder-slideshow-preview-undo"]');
        var redo = root.querySelector('[data-action="local-course-banner-builder-slideshow-preview-redo"]');
        var recenter = root.querySelector('[data-action="local-course-banner-builder-slideshow-preview-recenter-element"]');
        var selected = preview.querySelector('.' + slideshowPreviewSelectionClass);
        var undoStack = slideshowPreviewReadJson(preview.dataset.previewUndoStack || '[]', []);
        var redoStack = slideshowPreviewReadJson(preview.dataset.previewRedoStack || '[]', []);
        if (undo) {
            undo.disabled = !undoStack.length;
        }
        if (redo) {
            redo.disabled = !redoStack.length;
        }
        if (recenter) {
            recenter.disabled = !selected;
        }
        var selectedKey = selected ? selected.getAttribute('data-slideshow-preview-draggable') : '';
        var textSelected = ['title', 'body', 'action', 'label'].indexOf(selectedKey) !== -1;
        root.querySelectorAll('[data-action="local-course-banner-builder-toggle-selected-slideshow-text-style"]').forEach(function (button) {
            button.classList.toggle('d-none', !textSelected);
            button.disabled = !textSelected;
            if (!textSelected) {
                slideshowPreviewSetToolbarButtonActive(button, false);
                return;
            }
            var style = button.getAttribute('data-slideshow-text-style');
            var input = root.querySelector('[data-slideshow-text-style-input="' + selectedKey + style + '"]');
            var active = input && input.value === '1';
            slideshowPreviewSetToolbarButtonActive(button, active);
        });
        var orientation = (root.querySelector('[data-slideshow-label-orientation-input]') || {}).value || 'row';
        var alignmentSelected = selectedKey === 'title' || selectedKey === 'body' ||
            (selectedKey === 'label' && orientation === 'column');
        root.querySelectorAll('[data-action="local-course-banner-builder-set-selected-slideshow-alignment"]').forEach(function (button) {
            if (button.hasAttribute('data-slideshow-align-target')) {
                return;
            }
            button.classList.toggle('d-none', !alignmentSelected);
            button.disabled = !alignmentSelected;
            if (!alignmentSelected) {
                slideshowPreviewSetToolbarButtonActive(button, false);
                return;
            }
            var input = root.querySelector('[data-slideshow-alignment-input="' + selectedKey + '"]');
            var active = input && input.value === button.getAttribute('data-slideshow-align');
            slideshowPreviewSetToolbarButtonActive(button, active);
        });
        slideshowPreviewSyncSnapButtons(root);
    };
    var slideshowPreviewUsesCircleActiveState = function (button) {
        if (!button) {
            return false;
        }
        var action = button.getAttribute('data-action') || '';
        return action === 'local-course-banner-builder-toggle-selected-slideshow-text-style' ||
            action === 'local-course-banner-builder-set-selected-slideshow-alignment';
    };
    var slideshowPreviewSetToolbarButtonActive = function (button, active) {
        if (!button) {
            return;
        }
        button.classList.toggle('active', active);
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-secondary', !active);
        button.classList.remove('local-course-banner-builder-source-preview-button--active');
        button.classList.toggle(
            'local-course-banner-builder-preview-toolbar-button--active',
            active && slideshowPreviewUsesCircleActiveState(button)
        );
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    };
    var slideshowPreviewSyncToolbarOnNextFrame = function (preview) {
        if (!preview) {
            return;
        }
        window.requestAnimationFrame(function () {
            slideshowPreviewSyncButtons(preview);
        });
    };
    var slideshowPreviewPushUndo = function (preview) {
        var undoStack = slideshowPreviewReadJson(preview.dataset.previewUndoStack || '[]', []);
        undoStack.push(slideshowPreviewCaptureState(preview));
        if (undoStack.length > 40) {
            undoStack = undoStack.slice(undoStack.length - 40);
        }
        preview.dataset.previewUndoStack = JSON.stringify(undoStack);
        preview.dataset.previewRedoStack = '[]';
        slideshowPreviewSyncButtons(preview);
    };
    var slideshowPreviewOpenSidePanelForSelection = function (root, key) {
        var target = {
            label: 'labelshape',
            title: 'titletext',
            body: 'bodytext',
            action: 'buttonshape'
        }[key];
        if (!root || !target) {
            return;
        }
        root.querySelectorAll('[data-slideshow-side-panel]').forEach(function (panel) {
            var active = panel.getAttribute('data-slideshow-side-panel') === target;
            slideshowSetSidePanelVisible(panel, active);
        });
        slideshowSyncSidePanelButtons(root);
    };
    var slideshowPreviewSelect = function (preview, key) {
        if (!preview) {
            return;
        }
        Array.prototype.slice.call(preview.querySelectorAll('[data-slideshow-preview-draggable]')).forEach(function (node) {
            node.classList.toggle(slideshowPreviewSelectionClass, node.getAttribute('data-slideshow-preview-draggable') === key);
        });
        slideshowPreviewSyncButtons(preview);
        slideshowPreviewOpenSidePanelForSelection(preview.closest('[data-slideshow-overlay-settings="1"]'), key);
    };
    document.querySelectorAll('[data-slideshow-overlay-preview="1"][data-slideshow-preview-editor="1"]').forEach(function (preview) {
        preview.dataset.previewUndoStack = '[]';
        preview.dataset.previewRedoStack = '[]';
        preview.addEventListener('pointerdown', function (event) {
            var target = event.target.closest('[data-slideshow-preview-draggable]');
            if (!target) {
                slideshowPreviewSelect(preview, '');
                return;
            }
            event.preventDefault();
            var key = target.getAttribute('data-slideshow-preview-draggable');
            slideshowPreviewSelect(preview, key);
            slideshowPreviewPushUndo(preview);
            var rect = preview.getBoundingClientRect();
            var state = slideshowPreviewCaptureState(preview);
            var handle = event.target.closest('[data-slideshow-preview-resize-handle]');
            if (handle) {
                event.stopPropagation();
                var root = preview.closest('[data-slideshow-overlay-settings="1"]');
                var inputname = slideshowPreviewSizeInputs[key];
                if (key === 'action') {
                    var edge = handle.getAttribute('data-slideshow-preview-resize-handle');
                    inputname = (edge === 'left' || edge === 'right') ? 'actionwidth' : 'actionheight';
                }
                var range = root && inputname ? root.querySelector('[name="' + inputname + '"]') : null;
                slideshowPreviewDrag = {
                    mode: 'resize',
                    preview: preview,
                    target: target,
                    key: key,
                    handle: handle.getAttribute('data-slideshow-preview-resize-handle'),
                    width: Math.max(1, rect.width),
                    height: Math.max(1, rect.height),
                    startX: event.clientX,
                    startY: event.clientY,
                    startSize: range ? parseFloat(range.value || '100') : 100
                };
                return;
            }
            slideshowPreviewDrag = {
                mode: 'move',
                preview: preview,
                target: target,
                key: key,
                width: Math.max(1, rect.width),
                height: Math.max(1, rect.height),
                startX: event.clientX,
                startY: event.clientY,
                originX: state[key].x,
                originY: state[key].y
            };
        });
        slideshowPreviewSyncButtons(preview);
    });
    document.addEventListener('keydown', function (event) {
        var keys = {
            ArrowLeft: [-1, 0],
            ArrowRight: [1, 0],
            ArrowUp: [0, -1],
            ArrowDown: [0, 1]
        };
        if (!Object.prototype.hasOwnProperty.call(keys, event.key)) {
            return;
        }
        var active = document.activeElement;
        if (active && (
                active.tagName === 'INPUT' || active.tagName === 'SELECT' || active.tagName === 'TEXTAREA' ||
                active.isContentEditable)) {
            return;
        }
        var selectedPreview = null;
        var selected = null;
        Array.prototype.slice.call(document.querySelectorAll('[data-slideshow-overlay-preview="1"][data-slideshow-preview-editor="1"]')).some(function (preview) {
            var candidate = preview.querySelector('.' + slideshowPreviewSelectionClass);
            if (candidate && preview.offsetParent !== null) {
                selectedPreview = preview;
                selected = candidate;
                return true;
            }
            return false;
        });
        if (!selectedPreview || !selected) {
            return;
        }
        event.preventDefault();
        var key = selected.getAttribute('data-slideshow-preview-draggable');
        var state = slideshowPreviewCaptureState(selectedPreview);
        if (!state[key]) {
            return;
        }
        var rect = selectedPreview.getBoundingClientRect();
        var step = event.shiftKey ? 10 : 1;
        var delta = keys[event.key];
        slideshowPreviewPushUndo(selectedPreview);
        slideshowPreviewApplyPosition(
            selectedPreview,
            key,
            state[key].x + ((delta[0] * step) / Math.max(1, rect.width)) * 100,
            state[key].y + ((delta[1] * step) / Math.max(1, rect.height)) * 100
        );
        slideshowPreviewUpdateGuides(selectedPreview, selected);
        window.setTimeout(function () {
            slideshowPreviewClearGuides(selectedPreview);
        }, 450);
    });
    document.querySelectorAll('[data-slideshow-label-orientation-option]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            slideshowSyncLabelOrientation(panel, button.getAttribute('data-slideshow-label-orientation-option'));
        });
    });
    document.querySelectorAll('[data-slideshow-label-orientation-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            slideshowSyncLabelOrientation(input.closest('[data-slideshow-overlay-settings="1"]'), input.value);
        });
        slideshowSyncLabelOrientation(input.closest('[data-slideshow-overlay-settings="1"]'), input.value);
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-toggle-slideshow-label-orientation"]').forEach(function (button) {
        button.addEventListener('pointerdown', function (e) {
            e.stopPropagation();
        });
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            var input = panel ? panel.querySelector('[data-slideshow-label-orientation-input]') : null;
            slideshowSyncLabelOrientation(panel, input && input.value === 'column' ? 'row' : 'column');
        });
    });
    document.querySelectorAll('[data-slideshow-corner-option]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            slideshowSyncCornerStyle(
                panel,
                button.getAttribute('data-slideshow-corner-option'),
                button.getAttribute('data-slideshow-corner-value')
            );
        });
    });
    document.querySelectorAll('[data-slideshow-corner-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            slideshowSyncCornerStyle(
                input.closest('[data-slideshow-overlay-settings="1"]'),
                input.getAttribute('data-slideshow-corner-input'),
                input.value
            );
        });
        slideshowSyncCornerStyle(
            input.closest('[data-slideshow-overlay-settings="1"]'),
            input.getAttribute('data-slideshow-corner-input'),
            input.value
        );
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-toggle-slideshow-corners"]').forEach(function (button) {
        button.addEventListener('pointerdown', function (e) {
            e.stopPropagation();
        });
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            var target = button.getAttribute('data-slideshow-corner-target') || 'label';
            var input = panel ? panel.querySelector('[data-slideshow-corner-input="' + target + '"]') : null;
            slideshowSyncCornerStyle(panel, target, input && input.value === 'square' ? 'rounded' : 'square');
        });
    });
    document.addEventListener('pointermove', function (event) {
        if (!slideshowPreviewDrag) {
            return;
        }
        if (slideshowPreviewDrag.mode === 'resize') {
            var resizeDeltaX = event.clientX - slideshowPreviewDrag.startX;
            var resizeDeltaY = event.clientY - slideshowPreviewDrag.startY;
            var direction = slideshowPreviewDrag.handle;
            var distance = 0;
            if (direction === 'left') {
                distance = -resizeDeltaX;
            } else if (direction === 'right') {
                distance = resizeDeltaX;
            } else if (direction === 'top') {
                distance = -resizeDeltaY;
            } else {
                distance = resizeDeltaY;
            }
            var axis = direction === 'left' || direction === 'right' ? slideshowPreviewDrag.width : slideshowPreviewDrag.height;
            var nextSize = slideshowPreviewDrag.startSize + ((distance / Math.max(1, axis)) * 100);
            slideshowPreviewApplySize(slideshowPreviewDrag.preview, slideshowPreviewDrag.key, nextSize, direction);
            slideshowPreviewUpdateGuides(slideshowPreviewDrag.preview, slideshowPreviewDrag.target);
            return;
        }
        var deltaX = ((event.clientX - slideshowPreviewDrag.startX) / slideshowPreviewDrag.width) * 100;
        var deltaY = ((event.clientY - slideshowPreviewDrag.startY) / slideshowPreviewDrag.height) * 100;
        var nextX = slideshowPreviewDrag.originX + deltaX;
        var nextY = slideshowPreviewDrag.originY + deltaY;
        if (slideshowPreviewIsSnapEnabled(slideshowPreviewDrag.preview)) {
            var frameRect = slideshowPreviewDrag.preview.getBoundingClientRect();
            var targetRect = slideshowPreviewDrag.target.getBoundingClientRect();
            var width = targetRect.width;
            var height = targetRect.height;
            var centerX = (nextX / 100) * frameRect.width;
            var centerY = (nextY / 100) * frameRect.height;
            var snap = slideshowPreviewFindSnap(slideshowPreviewDrag.preview, slideshowPreviewDrag.target, {
                left: centerX - (width / 2),
                top: centerY - (height / 2),
                right: centerX + (width / 2),
                bottom: centerY + (height / 2),
                centerX: centerX,
                centerY: centerY
            });
            nextX += (snap.dx / Math.max(1, frameRect.width)) * 100;
            nextY += (snap.dy / Math.max(1, frameRect.height)) * 100;
        }
        slideshowPreviewApplyPosition(
            slideshowPreviewDrag.preview,
            slideshowPreviewDrag.key,
            nextX,
            nextY
        );
        slideshowPreviewUpdateGuides(slideshowPreviewDrag.preview, slideshowPreviewDrag.target);
    });
    var slideshowPreviewStopDrag = function () {
        if (!slideshowPreviewDrag) {
            return;
        }
        slideshowPreviewSyncButtons(slideshowPreviewDrag.preview);
        slideshowPreviewClearGuides(slideshowPreviewDrag.preview);
        slideshowPreviewDrag = null;
    };
    document.addEventListener('pointerup', slideshowPreviewStopDrag);
    document.addEventListener('pointercancel', slideshowPreviewStopDrag);
    document.querySelectorAll('[data-action="local-course-banner-builder-reset-slideshow-overlay"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            if (!panel) {
                return;
            }
            var color = panel.querySelector('[data-slideshow-overlay-color="1"]');
            var opacity = panel.querySelector('[data-slideshow-overlay-opacity="1"]');
            if (color) {
                color.value = color.getAttribute('data-default-value') || '#000000';
                color.dispatchEvent(new Event('input', {bubbles: true}));
            }
            if (opacity) {
                opacity.value = opacity.getAttribute('data-default-value') || '38';
                opacity.dispatchEvent(new Event('input', {bubbles: true}));
            }
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-reset-slideshow-labels"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            if (!panel) {
                return;
            }
            panel.querySelectorAll('[data-slideshow-label-var][data-default-value]').forEach(function (input) {
                input.value = input.getAttribute('data-default-value') || '#000000';
                input.dispatchEvent(new Event('input', {bubbles: true}));
            });
            panel.querySelectorAll('[data-slideshow-text-size="label"][data-default-value]').forEach(function (input) {
                input.value = input.getAttribute('data-default-value') || '100';
                input.dispatchEvent(new Event('input', {bubbles: true}));
            });
            panel.querySelectorAll('[name="labeltextsize"][data-default-value]').forEach(function (input) {
                input.value = input.getAttribute('data-default-value') || '100';
                input.dispatchEvent(new Event('input', {bubbles: true}));
            });
            panel.querySelectorAll('[data-slideshow-design-input="1"][data-default-value]').forEach(function (input) {
                if (!input.name || input.name.indexOf('label') !== 0) {
                    return;
                }
                input.value = input.getAttribute('data-default-value') || input.value;
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
            var orientation = panel.querySelector('[data-slideshow-label-orientation-input]');
            if (orientation) {
                orientation.value = orientation.getAttribute('data-default-value') || 'row';
                orientation.dispatchEvent(new Event('change', {bubbles: true}));
            }
            var corners = panel.querySelector('[data-slideshow-corner-input="label"]');
            if (corners) {
                corners.value = corners.getAttribute('data-default-value') || 'rounded';
                corners.dispatchEvent(new Event('change', {bubbles: true}));
            }
            var alignment = panel.querySelector('[data-slideshow-alignment-input="label"]');
            if (alignment) {
                slideshowApplyAlignment(panel, 'label', alignment.getAttribute('data-default-value') || 'center');
            }
            var preview = slideshowPreviewGetPreview(button);
            if (preview) {
                slideshowPreviewApplyPosition(preview, 'label', slideshowPreviewDefaults.label.x, slideshowPreviewDefaults.label.y);
            }
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-reset-slideshow-text"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            var preview = slideshowPreviewGetPreview(button);
            if (!panel || !preview) {
                return;
            }
            panel.querySelectorAll('[data-slideshow-text-var][data-default-value]').forEach(function (input) {
                if (input.getAttribute('data-slideshow-text-size') === 'label') {
                    return;
                }
                input.value = input.getAttribute('data-default-value') || '';
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
            panel.querySelectorAll('[data-slideshow-design-input="1"][data-default-value]').forEach(function (input) {
                if (input.name && input.name.indexOf('label') === 0) {
                    return;
                }
                input.value = input.getAttribute('data-default-value') || input.value;
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
            panel.querySelectorAll('[data-slideshow-text-style-input][data-default-value]').forEach(function (input) {
                input.value = input.getAttribute('data-default-value') || '0';
                slideshowApplyTextStyle(panel, input.getAttribute('data-slideshow-text-style-target'));
            });
            panel.querySelectorAll('[data-slideshow-alignment-input][data-default-value]').forEach(function (input) {
                var target = input.getAttribute('data-slideshow-alignment-input');
                if (target === 'label') {
                    return;
                }
                slideshowApplyAlignment(panel, target, input.getAttribute('data-default-value') || 'center');
            });
            var actioncorners = panel.querySelector('[data-slideshow-corner-input="action"]');
            if (actioncorners) {
                actioncorners.value = actioncorners.getAttribute('data-default-value') || 'rounded';
                actioncorners.dispatchEvent(new Event('change', {bubbles: true}));
            }
            ['title', 'body', 'action'].forEach(function (key) {
                slideshowPreviewApplyPosition(preview, key, slideshowPreviewDefaults[key].x, slideshowPreviewDefaults[key].y);
            });
            slideshowPreviewSelect(preview, '');
        });
    });
    document.querySelectorAll('[data-slideshow-text-style-input]').forEach(function (input) {
        slideshowApplyTextStyle(input.closest('[data-slideshow-overlay-settings="1"]'), input.getAttribute('data-slideshow-text-style-target'));
    });
    document.querySelectorAll('[data-slideshow-alignment-input]').forEach(function (input) {
        slideshowApplyAlignment(
            input.closest('[data-slideshow-overlay-settings="1"]'),
            input.getAttribute('data-slideshow-alignment-input'),
            input.value || input.getAttribute('data-default-value') || 'center'
        );
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-toggle-slideshow-text-style"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            var target = button.getAttribute('data-slideshow-text-style-target');
            var style = button.getAttribute('data-slideshow-text-style');
            var input = panel ? panel.querySelector('[data-slideshow-text-style-input="' + target + style + '"]') : null;
            if (!input) {
                return;
            }
            var preview = slideshowPreviewGetPreview(button);
            if (preview) {
                slideshowPreviewPushUndo(preview);
            }
            input.value = input.value === '1' ? '0' : '1';
            slideshowApplyTextStyle(panel, target);
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-toggle-selected-slideshow-text-style"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var preview = slideshowPreviewGetPreview(button);
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            var selected = preview ? preview.querySelector('.' + slideshowPreviewSelectionClass) : null;
            var target = selected ? selected.getAttribute('data-slideshow-preview-draggable') : '';
            if (['title', 'body', 'action', 'label'].indexOf(target) === -1) {
                return;
            }
            var style = button.getAttribute('data-slideshow-text-style');
            var input = panel ? panel.querySelector('[data-slideshow-text-style-input="' + target + style + '"]') : null;
            if (!input) {
                return;
            }
            slideshowPreviewPushUndo(preview);
            input.value = input.value === '1' ? '0' : '1';
            slideshowApplyTextStyle(panel, target);
            slideshowPreviewSyncButtons(preview);
            slideshowPreviewSetToolbarButtonActive(button, input.value === '1');
            slideshowPreviewSyncToolbarOnNextFrame(preview);
            slideshowPreviewPulseButton(button);
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-set-selected-slideshow-alignment"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var preview = slideshowPreviewGetPreview(button);
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            var fixedTarget = button.getAttribute('data-slideshow-align-target') || '';
            var selected = preview ? preview.querySelector('.' + slideshowPreviewSelectionClass) : null;
            var target = fixedTarget || (selected ? selected.getAttribute('data-slideshow-preview-draggable') : '');
            if (['title', 'body', 'label'].indexOf(target) === -1) {
                return;
            }
            var orientation = (panel.querySelector('[data-slideshow-label-orientation-input]') || {}).value || 'row';
            if (target === 'label' && orientation !== 'column') {
                return;
            }
            if (preview) {
                slideshowPreviewPushUndo(preview);
            }
            slideshowApplyAlignment(panel, target, button.getAttribute('data-slideshow-align'));
            if (preview) {
                slideshowPreviewSyncButtons(preview);
                panel.querySelectorAll(
                    '[data-action="local-course-banner-builder-set-selected-slideshow-alignment"]'
                ).forEach(function (item) {
                    if (item.hasAttribute('data-slideshow-align-target')) {
                        return;
                    }
                    slideshowPreviewSetToolbarButtonActive(
                        item,
                        item.getAttribute('data-slideshow-align') === button.getAttribute('data-slideshow-align')
                    );
                });
                slideshowPreviewSetToolbarButtonActive(button, true);
                slideshowPreviewSyncToolbarOnNextFrame(preview);
            }
            slideshowPreviewPulseButton(button);
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-toggle-slideshow-preview-snap"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            if (!panel) {
                return;
            }
            var preview = slideshowPreviewGetPreview(button);
            var enabled = !slideshowPreviewIsSnapEnabled(preview);
            panel.setAttribute('data-preview-snap-enabled', enabled ? '1' : '0');
            slideshowPreviewSyncSnapButtons(panel);
            slideshowPreviewPulseButton(button);
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-reset-slideshow-all"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings="1"]');
            if (!panel) {
                return;
            }
            [
                'local-course-banner-builder-reset-slideshow-overlay',
                'local-course-banner-builder-reset-slideshow-text',
                'local-course-banner-builder-reset-slideshow-labels'
            ].forEach(function (action) {
                var target = panel.querySelector('[data-action="' + action + '"]');
                if (target && target !== button) {
                    target.click();
                }
            });
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-slideshow-preview-undo"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var preview = slideshowPreviewGetPreview(button);
            if (!preview) {
                return;
            }
            var undoStack = slideshowPreviewReadJson(preview.dataset.previewUndoStack || '[]', []);
            var redoStack = slideshowPreviewReadJson(preview.dataset.previewRedoStack || '[]', []);
            if (!undoStack.length) {
                return;
            }
            redoStack.push(slideshowPreviewCaptureState(preview));
            var state = undoStack.pop();
            preview.dataset.previewUndoStack = JSON.stringify(undoStack);
            preview.dataset.previewRedoStack = JSON.stringify(redoStack);
            slideshowPreviewApplyState(preview, state);
            slideshowPreviewSyncButtons(preview);
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-slideshow-preview-redo"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var preview = slideshowPreviewGetPreview(button);
            if (!preview) {
                return;
            }
            var undoStack = slideshowPreviewReadJson(preview.dataset.previewUndoStack || '[]', []);
            var redoStack = slideshowPreviewReadJson(preview.dataset.previewRedoStack || '[]', []);
            if (!redoStack.length) {
                return;
            }
            undoStack.push(slideshowPreviewCaptureState(preview));
            var state = redoStack.pop();
            preview.dataset.previewUndoStack = JSON.stringify(undoStack);
            preview.dataset.previewRedoStack = JSON.stringify(redoStack);
            slideshowPreviewApplyState(preview, state);
            slideshowPreviewSyncButtons(preview);
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-slideshow-preview-recenter-element"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var preview = slideshowPreviewGetPreview(button);
            if (!preview) {
                return;
            }
            var selected = preview.querySelector('.' + slideshowPreviewSelectionClass);
            if (!selected) {
                return;
            }
            var key = selected.getAttribute('data-slideshow-preview-draggable');
            slideshowPreviewPushUndo(preview);
            slideshowPreviewApplyPosition(preview, key, slideshowPreviewDefaults[key].x, slideshowPreviewDefaults[key].y);
            slideshowPreviewSyncButtons(preview);
            slideshowPreviewPulseButton(button);
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-slideshow-preview-recenter-all"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var preview = slideshowPreviewGetPreview(button);
            if (!preview) {
                return;
            }
            slideshowPreviewPushUndo(preview);
            slideshowPreviewApplyState(preview, slideshowPreviewDefaults);
            slideshowPreviewSelect(preview, '');
            slideshowPreviewPulseButton(button);
        });
    });
    document.querySelectorAll('.local-course-banner-builder-slideshow-preview-toolbar [data-toggle="popover"]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.popover) {
                window.jQuery(button).popover('hide');
            }
            button.removeAttribute('aria-describedby');
            document.querySelectorAll('.popover').forEach(function (popover) {
                popover.remove();
            });
        });
    });
    document.querySelectorAll('form.local-course-banner-builder-slideshow-card').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var submitter = event.submitter || document.activeElement;
            if (!submitter || !submitter.closest('.local-course-banner-builder-slideshow-actions') ||
                    submitter.name === 'defaultsettings') {
                return;
            }
            form.querySelectorAll('[data-slideshow-bulk-clone="1"]').forEach(function (node) {
                node.remove();
            });
            var saveAll = document.createElement('input');
            saveAll.type = 'hidden';
            saveAll.name = 'saveallslideshows';
            saveAll.value = '1';
            saveAll.setAttribute('data-slideshow-bulk-clone', '1');
            form.appendChild(saveAll);
            document.querySelectorAll('form.local-course-banner-builder-slideshow-card').forEach(function (source) {
                var contextInput = source.querySelector('input[name="context"]');
                var context = contextInput ? contextInput.value : '';
                if (!context) {
                    return;
                }
                source.querySelectorAll('input[name], select[name], textarea[name]').forEach(function (field) {
                    var name = field.name;
                    if (['sesskey', 'updateslideshow', 'context'].indexOf(name) !== -1) {
                        return;
                    }
                    if ((field.type === 'submit' || field.type === 'button' || field.type === 'reset') ||
                            (field.type === 'radio' && !field.checked)) {
                        return;
                    }
                    var clone = document.createElement('input');
                    clone.type = 'hidden';
                    clone.name = 'slideshowbulk[' + context + '][' + name + ']';
                    clone.value = field.type === 'checkbox' ? (field.checked ? (field.value || '1') : '0') : field.value;
                    clone.setAttribute('data-slideshow-bulk-clone', '1');
                    form.appendChild(clone);
                });
            });
        });
    });
    document.querySelectorAll('[data-action="local-course-banner-builder-save-all-slideshows"]').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            var form = document.querySelector('form.local-course-banner-builder-slideshow-card');
            if (!form) {
                return;
            }
            var actions = form.querySelector('.local-course-banner-builder-slideshow-actions') || form;
            var submitter = document.createElement('button');
            submitter.type = 'submit';
            submitter.className = 'sr-only';
            submitter.setAttribute('aria-hidden', 'true');
            submitter.setAttribute('tabindex', '-1');
            actions.appendChild(submitter);
            if (form.requestSubmit) {
                form.requestSubmit(submitter);
            } else {
                submitter.click();
            }
        });
    });
});

    return {
        init: function () {
            // The legacy page script is executed when this AMD module is loaded.
        }
    };
});
