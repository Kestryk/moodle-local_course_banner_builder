<?php
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
 * Manage banner slideshows.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// phpcs:disable moodle.Files.LineLength.TooLong -- This admin page contains large inline JS preview editor templates.

use local_course_banner_builder\manager;

require_login();
require_capability('local/course_banner_builder:manage', context_system::instance());
try {
    admin_externalpage_setup('local_course_banner_builder_slideshow');
} catch (\moodle_exception $exception) {
    if ($exception->errorcode !== 'sectionerror') {
        throw $exception;
    }
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/local/course_banner_builder/admin_slideshow.php'));
    $PAGE->set_pagelayout('admin');
    $PAGE->set_title(get_string('manageslideshow', 'local_course_banner_builder'));
    $PAGE->set_heading(get_string('pluginname', 'local_course_banner_builder'));
}

$PAGE->requires->css('/local/course_banner_builder/styles.css');
$PAGE->requires->js_init_code("
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('change', function(e) {
        var formatInput = e.target && e.target.closest ?
            e.target.closest('[data-banner-format-modal=\"1\"] input[name=\"bannerformat\"]') :
            null;
        if (!formatInput) {
            return;
        }
        var modal = formatInput.closest('[data-banner-format-modal=\"1\"]');
        if (!modal) {
            return;
        }
        Array.prototype.slice.call(modal.querySelectorAll('.local-course-banner-builder-format-card')).forEach(function(card) {
            card.classList.remove('is-selected');
        });
        var selectedCard = formatInput.closest('.local-course-banner-builder-format-card');
        if (selectedCard) {
            selectedCard.classList.add('is-selected');
        }
    });
    var activeTip = null;
    var removeTip = function() {
        if (activeTip) {
            activeTip.remove();
            activeTip = null;
        }
    };
    var showTip = function(node) {
        removeTip();
        var content = node.getAttribute('data-content') || '';
        if (!content) {
            return;
        }
        activeTip = document.createElement('div');
        activeTip.className = 'popover local-course-banner-builder-hover-popover local-course-banner-builder-hover-popover--top show';
        activeTip.setAttribute('role', 'tooltip');
        activeTip.innerHTML = '<div class=\"popover-arrow\"></div><div class=\"popover-body\">' + content + '</div>';
        document.body.appendChild(activeTip);
        var rect = node.getBoundingClientRect();
        var tiprect = activeTip.getBoundingClientRect();
        var top = window.scrollY + rect.top - tiprect.height - 10;
        var left = window.scrollX + rect.left + ((rect.width - tiprect.width) / 2);
        activeTip.style.top = Math.max(window.scrollY + 8, top) + 'px';
        activeTip.style.left = Math.max(window.scrollX + 8, Math.min(window.scrollX + window.innerWidth - tiprect.width - 8, left)) + 'px';
    };
    document.querySelectorAll('[data-local-slideshow-help=\"1\"]').forEach(function(node) {
        node.addEventListener('mouseenter', function() { showTip(node); });
        node.addEventListener('focus', function() { showTip(node); });
        node.addEventListener('mouseleave', removeTip);
        node.addEventListener('blur', removeTip);
        node.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });
    document.querySelectorAll('[data-local-slideshow-action-help=\"1\"]').forEach(function(node) {
        node.addEventListener('mouseenter', function() { showTip(node); });
        node.addEventListener('mouseleave', removeTip);
        node.addEventListener('click', function() { removeTip(); });
    });
    document.querySelectorAll('[data-local-slideshow-toggle-button=\"1\"]').forEach(function(button) {
        var input = document.querySelector(button.getAttribute('data-target-input'));
        var sync = function() {
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
            var warning = input && input.form ? input.form.querySelector('[data-slideshow-banner-warning=\"1\"]') : null;
            if (warning) {
                warning.hidden = !(enabled && warning.getAttribute('data-banner-active') === '0');
            }
        };
        button.addEventListener('click', function() {
            if (!input) {
                return;
            }
            input.value = input.value === '1' ? '0' : '1';
            sync();
        });
        sync();
    });
    var syncColourInput = function(input) {
        if (!input || input.type !== 'color') {
            return;
        }
        var value = /^#[0-9a-f]{6}$/i.test(input.value || '') ? input.value : '#000000';
        input.style.setProperty('--local-course-banner-builder-selected-color', value);
        input.style.backgroundColor = value;
    };
    document.querySelectorAll('[data-slideshow-color-input=\"1\"]').forEach(function(colorInput) {
        var textInput = document.querySelector(colorInput.getAttribute('data-hex-target'));
        var normalise = function(value) {
            value = (value || '').trim();
            if (value.charAt(0) !== '#') {
                value = '#' + value;
            }
            return /^#[0-9a-fA-F]{6}$/.test(value) ? value.toUpperCase() : '';
        };
        if (textInput) {
            colorInput.addEventListener('input', function() {
                textInput.value = colorInput.value.toUpperCase();
                syncColourInput(colorInput);
            });
            textInput.addEventListener('input', function() {
                var value = normalise(textInput.value);
                if (value) {
                    colorInput.value = value;
                    syncColourInput(colorInput);
                    colorInput.dispatchEvent(new Event('input', {bubbles: true}));
                }
            });
        }
        colorInput.addEventListener('change', function() {
            syncColourInput(colorInput);
        });
        syncColourInput(colorInput);
    });
    document.querySelectorAll('input[type=\"color\"]').forEach(syncColourInput);
    var slideshowSyncShadowVector = function(root, target) {
        if (!root || !target) {
            return;
        }
        var distanceInput = root.querySelector('[name=\"' + target + 'shadowdistance\"]');
        var directionInput = root.querySelector('[name=\"' + target + 'shadowdirection\"]');
        var distance = parseFloat(distanceInput && distanceInput.value ? distanceInput.value : '0') || 0;
        var direction = ((parseFloat(directionInput && directionInput.value ? directionInput.value : '90') || 0) * Math.PI) / 180;
        root.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]').forEach(function(preview) {
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
            root.querySelectorAll('[data-slideshow-label-sample=\"1\"]').forEach(function(sample) {
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
    var slideshowSyncDesignInput = function(input) {
        if (!input) {
            return;
        }
        var root = input.closest('[data-slideshow-overlay-settings=\"1\"]');
        var syncLocalDesignValue = function(source) {
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
            var realInput = root.querySelector('[name=\"' + proxyFor + '\"]');
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
        root.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]').forEach(function(preview) {
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
            root.querySelectorAll('[data-slideshow-label-sample=\"1\"]').forEach(function(sample) {
                sample.style.setProperty(variable, value);
            });
        }
        if (variable.indexOf('shadow-distance') !== -1 || variable.indexOf('shadow-direction') !== -1) {
            slideshowSyncShadowVector(root, variable.indexOf('-label-') !== -1 ? 'label' : 'action');
        }
    };
    document.querySelectorAll('[data-slideshow-design-number-for]').forEach(function(number) {
        var range = document.querySelector(number.getAttribute('data-slideshow-design-number-for') || '');
        number.addEventListener('input', function() {
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
    document.querySelectorAll('[data-slideshow-side-proxy-for]').forEach(function(input) {
        var root = input.closest('[data-slideshow-overlay-settings=\"1\"]');
        var realInput = root ? root.querySelector('[name=\"' + input.getAttribute('data-slideshow-side-proxy-for') + '\"]') : null;
            if (realInput) {
                input.value = realInput.value || input.value;
                if (!input.getAttribute('data-default-value') && realInput.getAttribute('data-default-value')) {
                    input.setAttribute('data-default-value', realInput.getAttribute('data-default-value'));
                }
                if (input.type === 'color') {
                    syncColourInput(input);
                }
        }
        input.addEventListener('input', function() {
            slideshowSyncDesignInput(input);
        });
        input.addEventListener('change', function() {
            slideshowSyncDesignInput(input);
        });
    });
    document.querySelectorAll('[data-slideshow-design-input=\"1\"]').forEach(function(input) {
        input.addEventListener('input', function() {
            slideshowSyncDesignInput(input);
        });
        input.addEventListener('change', function() {
            slideshowSyncDesignInput(input);
        });
        slideshowSyncDesignInput(input);
    });
    var slideshowSetSidePanelVisible = function(panel, visible) {
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
            window.requestAnimationFrame(function() {
                panel.classList.remove('is-collapsed');
            });
            return;
        }
        panel.classList.add('is-collapsed');
        panel.dataset.slideshowPanelTimer = String(window.setTimeout(function() {
            if (panel.classList.contains('is-collapsed')) {
                panel.hidden = true;
            }
            delete panel.dataset.slideshowPanelTimer;
        }, 300));
    };
    var slideshowGetSidePanelRoot = function(node) {
        if (!node || !node.closest) {
            return null;
        }
        return node.closest('[data-slideshow-overlay-settings=\"1\"]') ||
            node.closest('.local-course-banner-builder-slideshow-preview-modal') ||
            node.closest('.modal');
    };
    var slideshowSyncSidePanelButtons = function(root) {
        if (!root) {
            return;
        }
        root.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-slideshow-side-panel\"]').forEach(function(button) {
            var target = button.getAttribute('data-slideshow-side-panel-target');
            var panel = target ? root.querySelector('[data-slideshow-side-panel=\"' + target + '\"]') : null;
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
    document.addEventListener('click', function(e) {
        var sideButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-slideshow-side-panel\"]');
        if (!sideButton) {
            return;
        }
        e.preventDefault();
        var root = slideshowGetSidePanelRoot(sideButton);
        var target = sideButton.getAttribute('data-slideshow-side-panel-target');
        if (!root || !target) {
            return;
        }
        root.querySelectorAll('[data-slideshow-side-panel]').forEach(function(panel) {
            var isTarget = panel.getAttribute('data-slideshow-side-panel') === target;
            var isOpen = panel.dataset.slideshowPanelVisible === '1' ||
                (!panel.hidden && !panel.classList.contains('is-collapsed'));
            slideshowSetSidePanelVisible(panel, isTarget && !isOpen);
        });
        slideshowSyncSidePanelButtons(root);
    });
    document.querySelectorAll('[data-slideshow-overlay-settings=\"1\"]').forEach(function(panel) {
        var color = panel.querySelector('[data-slideshow-overlay-color=\"1\"]');
        var opacity = panel.querySelector('[data-slideshow-overlay-opacity=\"1\"]');
        var previews = Array.prototype.slice.call(panel.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]'));
        var output = panel.querySelector('[data-slideshow-overlay-opacity-output=\"1\"]');
        var sync = function() {
            if (!previews.length || !color || !opacity) {
                return;
            }
            var hex = color.value || '#000000';
            var raw = hex.replace('#', '');
            var r = parseInt(raw.substring(0, 2), 16) || 0;
            var g = parseInt(raw.substring(2, 4), 16) || 0;
            var b = parseInt(raw.substring(4, 6), 16) || 0;
            var percent = Math.max(0, Math.min(85, parseInt(opacity.value || '38', 10)));
            previews.forEach(function(preview) {
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
    document.querySelectorAll('[data-slideshow-label-color-settings=\"1\"]').forEach(function(panel) {
        var sync = function(input) {
            if (!input) {
                return;
            }
            var variable = input.getAttribute('data-slideshow-label-var');
            var context = input.closest('[data-slideshow-overlay-settings=\"1\"]') || document;
            if (!variable) {
                return;
            }
            context.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]').forEach(function(preview) {
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
            var sample = row ? row.querySelector('[data-slideshow-label-sample=\"1\"]') : null;
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
        panel.querySelectorAll('[data-slideshow-label-var]').forEach(function(input) {
            input.addEventListener('input', function() {
                sync(input);
            });
            sync(input);
        });
    });
    document.querySelectorAll('[data-slideshow-text-settings=\"1\"]').forEach(function(panel) {
        var availableFonts = [
            'Arial', 'Trebuchet MS', 'Verdana', 'Tahoma', 'Georgia', 'Times New Roman', 'Garamond',
            'Palatino Linotype', 'Segoe UI', 'Helvetica Neue', 'Courier New', 'Lucida Console',
            'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Merriweather', 'Playfair Display'
        ];
        var getFormatSizeScale = function(format, kind) {
            if (format === 'standard') {
                return 1.24;
            }
            if (format === 'fullwidthtopcompact') {
                if (kind === 'label') {
                    return 1;
                }
                return 0.78;
            }
            return 1;
        };
        var buildTitleSize = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'title');
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqh, min(' + (28 * scale).toFixed(3) +
                'cqh, ' + (3.4 * scale).toFixed(3) + 'cqw), ' + (36 * scale).toFixed(3) + 'cqh)';
        };
        var buildBodySize = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'body');
            return 'clamp(' + (5.5 * scale).toFixed(3) + 'cqh, min(' + (14 * scale).toFixed(3) +
                'cqh, ' + (1.7 * scale).toFixed(3) + 'cqw), ' + (19 * scale).toFixed(3) + 'cqh)';
        };
        var buildLabelSize = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'label');
            return 'clamp(' + (3.5 * scale).toFixed(3) + 'cqh, min(' + (6.4 * scale).toFixed(3) +
                'cqh, ' + (0.82 * scale).toFixed(3) + 'cqw), ' + (8.4 * scale).toFixed(3) + 'cqh)';
        };
        var buildActionSize = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'action');
            return 'clamp(' + (6 * scale).toFixed(3) + 'cqh, min(' + (13 * scale).toFixed(3) +
                'cqh, ' + (1.6 * scale).toFixed(3) + 'cqw), ' + (18 * scale).toFixed(3) + 'cqh)';
        };
        var buildActionWidth = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'actionwidth');
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqw, ' + (18 * scale).toFixed(3) + 'cqw, ' + (34 * scale).toFixed(3) + 'cqw)';
        };
        var buildActionHeight = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '', 'actionheight');
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqh, min(' + (22 * scale).toFixed(3) +
                'cqh, ' + (2.7 * scale).toFixed(3) + 'cqw), ' + (34 * scale).toFixed(3) + 'cqh)';
        };
        var sync = function(input) {
            var variable = input.getAttribute('data-slideshow-text-var');
            var previews = Array.prototype.slice.call(panel.closest('[data-slideshow-overlay-settings=\"1\"]').querySelectorAll('[data-slideshow-overlay-preview=\"1\"]'));
            var output = panel.querySelector('[data-slideshow-text-output-for=\"' + input.name + '\"]');
            var number = panel.querySelector('[data-slideshow-size-number-for=\"' + input.name + '\"]');
            var value = input.value || '';
            if (output) {
                output.textContent = input.value + '%';
            }
            if (number && number !== document.activeElement) {
                number.value = input.value;
            }
            previews.forEach(function(preview) {
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
        panel.querySelectorAll('[data-slideshow-text-var]').forEach(function(input) {
            input.addEventListener('input', function() {
                sync(input);
            });
            input.addEventListener('change', function() {
                sync(input);
            });
            sync(input);
        });
        panel.querySelectorAll('[data-slideshow-size-number-for]').forEach(function(input) {
            var range = panel.querySelector('[name=\"' + input.getAttribute('data-slideshow-size-number-for') + '\"]');
            input.addEventListener('input', function() {
                if (!range) {
                    return;
                }
                var value = Math.max(25, Math.min(100, parseInt(input.value || '100', 10)));
                range.value = value;
                sync(range);
            });
        });
        panel.querySelectorAll('[data-slideshow-font-family=\"1\"]').forEach(function(select) {
            if (!document.fonts || typeof document.fonts.check !== 'function') {
                return;
            }
            Array.prototype.slice.call(select.options).forEach(function(option) {
                var raw = option.getAttribute('data-font-value') || '';
                if (!raw) {
                    return;
                }
                var family = availableFonts.find(function(candidate) {
                    return raw.indexOf(candidate) !== -1;
                });
                if (!family) {
                    return;
                }
                option.disabled = !document.fonts.check('16px \"' + family + '\"') && !document.fonts.check('16px ' + family);
            });
        });
    });
    var slideshowPreviewDefaults = {
        label: {x: 14, y: 10},
        title: {x: 50, y: 32},
        body: {x: 50, y: 43},
        action: {x: 50, y: 80}
    };
    var slideshowPreviewSelectionClass = 'local-course-banner-builder-slideshow-preview-draggable--selected';
    var slideshowPreviewDrag = null;
    var slideshowPreviewSizeInputs = {
        label: 'labelsize',
        title: 'titlefontsize',
        body: 'bodyfontsize',
        action: 'actionsize'
    };
    var slideshowPreviewReadJson = function(value, fallback) {
        try {
            return value ? JSON.parse(value) : fallback;
        } catch (error) {
            return fallback;
        }
    };
    var slideshowPreviewGetPreview = function(scope) {
        var root = scope && scope.closest ? scope.closest('[data-slideshow-overlay-settings=\"1\"]') : null;
        return root ? root.querySelector('[data-slideshow-overlay-preview=\"1\"][data-slideshow-preview-editor=\"1\"]') : null;
    };
    var slideshowPreviewApplyPosition = function(preview, key, x, y) {
        if (!preview) {
            return;
        }
        x = Math.max(0, Math.min(100, parseFloat(x || 0)));
        y = Math.max(0, Math.min(100, parseFloat(y || 0)));
        preview.style.setProperty('--local-course-banner-builder-slideshow-' + key + '-x', x.toFixed(3) + '%');
        preview.style.setProperty('--local-course-banner-builder-slideshow-' + key + '-y', y.toFixed(3) + '%');
        var root = preview.closest('[data-slideshow-overlay-settings=\"1\"]');
        if (root) {
            var inputX = root.querySelector('[data-slideshow-position-input=\"' + key + '-x\"]');
            var inputY = root.querySelector('[data-slideshow-position-input=\"' + key + '-y\"]');
            if (inputX) {
                inputX.value = x.toFixed(3);
            }
            if (inputY) {
                inputY.value = y.toFixed(3);
            }
        }
    };
    var slideshowNormaliseAlignment = function(value) {
        return ['left', 'center', 'right'].indexOf(value) !== -1 ? value : 'center';
    };
    var slideshowAlignmentTranslateX = function(value) {
        value = slideshowNormaliseAlignment(value);
        return '-50%';
    };
    var slideshowAlignmentFlexValue = function(value) {
        value = slideshowNormaliseAlignment(value);
        if (value === 'left') {
            return 'flex-start';
        }
        if (value === 'right') {
            return 'flex-end';
        }
        return 'center';
    };
    var slideshowSyncSideAlignmentButtons = function(panel, target) {
        if (!panel) {
            return;
        }
        var input = panel.querySelector('[data-slideshow-alignment-input=\"' + target + '\"]');
        var value = input ? slideshowNormaliseAlignment(input.value || 'center') : 'center';
        panel.querySelectorAll('[data-slideshow-align-target=\"' + target + '\"]').forEach(function(button) {
            var active = button.getAttribute('data-slideshow-align') === value;
            button.classList.toggle('active', active);
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };
    var slideshowApplyAlignment = function(panel, target, value) {
        if (!panel) {
            return;
        }
        target = ['title', 'body', 'label'].indexOf(target) !== -1 ? target : 'title';
        value = slideshowNormaliseAlignment(value);
        var variable = target === 'label'
            ? '--local-course-banner-builder-slideshow-label-items-align'
            : '--local-course-banner-builder-slideshow-' + target + '-text-align';
        var cssvalue = target === 'label' ? slideshowAlignmentFlexValue(value) : value;
        panel.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]').forEach(function(preview) {
            preview.style.setProperty(variable, cssvalue);
            if (target === 'label') {
                preview.style.setProperty(
                    '--local-course-banner-builder-slideshow-label-translate-x',
                    slideshowAlignmentTranslateX(value)
                );
            }
        });
        var input = panel.querySelector('[data-slideshow-alignment-input=\"' + target + '\"]');
        if (input) {
            input.value = value;
        }
        slideshowSyncSideAlignmentButtons(panel, target);
        var preview = panel.querySelector('[data-slideshow-overlay-preview=\"1\"][data-slideshow-preview-editor=\"1\"]');
        if (preview) {
            slideshowPreviewSyncButtons(preview);
        }
    };
    var slideshowPreviewGuideThreshold = 5;
    var slideshowPreviewGuideMargin = 12;
    var slideshowPreviewEnsureGuideLayer = function(preview) {
        if (!preview) {
            return null;
        }
        var layer = preview.querySelector(':scope > [data-preview-guides-layer=\"1\"]');
        if (!layer) {
            layer = document.createElement('div');
            layer.className = 'local-course-banner-builder-preview-guides';
            layer.setAttribute('data-preview-guides-layer', '1');
            layer.setAttribute('aria-hidden', 'true');
            preview.appendChild(layer);
        }
        return layer;
    };
    var slideshowPreviewClearGuides = function(preview) {
        var layer = preview ? preview.querySelector(':scope > [data-preview-guides-layer=\"1\"]') : null;
        if (layer) {
            layer.innerHTML = '';
            layer.hidden = true;
        }
    };
    var slideshowPreviewIsSnapEnabled = function(preview) {
        var panel = preview ? preview.closest('[data-slideshow-overlay-settings=\"1\"]') : null;
        return !(panel && panel.getAttribute('data-preview-snap-enabled') === '0');
    };
    var slideshowPreviewSyncSnapButtons = function(scope) {
        var root = scope || document;
        Array.prototype.slice.call(root.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-slideshow-preview-snap\"]')).forEach(function(button) {
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
    var slideshowPreviewPulseButton = function(button) {
        if (!button) {
            return;
        }
        button.classList.remove('local-course-banner-builder-preview-action-feedback');
        void button.offsetWidth;
        button.classList.add('local-course-banner-builder-preview-action-feedback');
        window.setTimeout(function() {
            button.classList.remove('local-course-banner-builder-preview-action-feedback');
        }, 260);
    };
    var slideshowPreviewRectInFrame = function(frameRect, node) {
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
    var slideshowPreviewRectsOverlap = function(a, b, margin) {
        margin = margin || 0;
        return !(a.right + margin < b.left || a.left - margin > b.right ||
            a.bottom + margin < b.top || a.top - margin > b.bottom);
    };
    var slideshowPreviewAddGuide = function(layer, orientation, position, kind) {
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
    var slideshowPreviewMaybeAddGuide = function(layer, orientation, activeValue, targetValue, kind, seen) {
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
    var slideshowPreviewSnapCandidate = function(best, axis, delta, priority) {
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
    var slideshowPreviewFindSnap = function(preview, active, rawRect) {
        if (!preview || !active || !rawRect) {
            return {dx: 0, dy: 0};
        }
        var frameRect = preview.getBoundingClientRect();
        var best = {};
        best = slideshowPreviewSnapCandidate(best, 'x', (frameRect.width / 2) - rawRect.centerX, 3);
        best = slideshowPreviewSnapCandidate(best, 'y', (frameRect.height / 2) - rawRect.centerY, 3);
        Array.prototype.slice.call(preview.querySelectorAll('[data-slideshow-preview-draggable]')).forEach(function(target) {
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
            ].forEach(function(activeValue) {
                [
                    {value: targetRect.centerX, priority: activeValue.priority === 2 ? 2 : 1},
                    {value: targetRect.left, priority: 1},
                    {value: targetRect.right, priority: 1}
                ].forEach(function(targetValue) {
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
            ].forEach(function(activeValue) {
                [
                    {value: targetRect.centerY, priority: activeValue.priority === 2 ? 2 : 1},
                    {value: targetRect.top, priority: 1},
                    {value: targetRect.bottom, priority: 1}
                ].forEach(function(targetValue) {
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
    var slideshowPreviewUpdateGuides = function(preview, active) {
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
        Array.prototype.slice.call(preview.querySelectorAll('[data-slideshow-preview-draggable]')).forEach(function(target) {
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
            ].forEach(function(activeValue) {
                [
                    {value: targetRect.left, type: 'edge'},
                    {value: targetRect.centerX, type: 'center'},
                    {value: targetRect.right, type: 'edge'}
                ].forEach(function(targetValue) {
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
            ].forEach(function(activeValue) {
                [
                    {value: targetRect.top, type: 'edge'},
                    {value: targetRect.centerY, type: 'center'},
                    {value: targetRect.bottom, type: 'edge'}
                ].forEach(function(targetValue) {
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
    var slideshowPreviewApplySize = function(preview, key, value, edge) {
        var root = preview ? preview.closest('[data-slideshow-overlay-settings=\"1\"]') : null;
        var inputname = slideshowPreviewSizeInputs[key];
        if (key === 'action' && edge) {
            inputname = (edge === 'left' || edge === 'right') ? 'actionwidth' : 'actionheight';
        }
        if (!root || !inputname) {
            return;
        }
        value = Math.max(25, Math.min(100, Math.round(parseFloat(value || 100))));
            var range = root.querySelector('[name=\"' + inputname + '\"]');
        if (range) {
            range.value = value;
            range.dispatchEvent(new Event('input', {bubbles: true}));
        }
    };
    var slideshowSyncLabelOrientation = function(panel, value) {
        if (!panel) {
            return;
        }
        value = value === 'column' ? 'column' : 'row';
        panel.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]').forEach(function(preview) {
            preview.style.setProperty('--local-course-banner-builder-slideshow-label-orientation', value);
        });
        var input = panel.querySelector('[data-slideshow-label-orientation-input]');
        if (input) {
            input.value = value;
        }
        panel.querySelectorAll('[data-slideshow-label-orientation-option]').forEach(function(button) {
            var active = button.getAttribute('data-slideshow-label-orientation-option') === value;
            button.classList.toggle('active', active);
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        panel.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-slideshow-label-orientation\"] i').forEach(function(icon) {
            icon.className = 'fa ' + (value === 'column' ? 'fa-grip-lines-vertical' : 'fa-grip-lines');
        });
    };
    var slideshowSyncCornerStyle = function(panel, target, value) {
        if (!panel) {
            return;
        }
        target = target === 'label' ? 'label' : 'action';
        value = value === 'square' ? 'square' : 'rounded';
        var variable = target === 'label'
            ? '--local-course-banner-builder-slideshow-label-radius'
            : '--local-course-banner-builder-slideshow-action-radius';
        panel.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]').forEach(function(preview) {
            preview.style.setProperty(variable, value === 'square' ? '0.28rem' : '999px');
        });
        panel.querySelectorAll('[data-slideshow-corner-input=\"' + target + '\"]').forEach(function(input) {
            input.value = value;
        });
        panel.querySelectorAll('[data-slideshow-corner-option=\"' + target + '\"]').forEach(function(button) {
            var active = button.getAttribute('data-slideshow-corner-value') === value;
            button.classList.toggle('active', active);
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        panel.querySelectorAll('[data-slideshow-corner-target=\"' + target + '\"] i').forEach(function(icon) {
            icon.className = 'fa ' + (value === 'square' ? 'fa-square' : 'fa-circle');
        });
        if (target === 'label') {
            panel.querySelectorAll('[data-slideshow-label-sample=\"1\"]').forEach(function(sample) {
                sample.style.setProperty(variable, value === 'square' ? '0.28rem' : '999px');
            });
        }
    };
    var slideshowGetTextDecoration = function(underline, strike) {
        var value = [];
        if (underline) {
            value.push('underline');
        }
        if (strike) {
            value.push('line-through');
        }
        return value.length ? value.join(' ') : 'none';
    };
    var slideshowApplyTextStyle = function(panel, target) {
        if (!panel) {
            return;
        }
        target = ['title', 'body', 'action', 'label'].indexOf(target) !== -1 ? target : 'title';
        var get = function(style) {
            var input = panel.querySelector('[data-slideshow-text-style-input=\"' + target + style + '\"]');
            return input && input.value === '1';
        };
        var bold = get('bold');
        var italic = get('italic');
        var underline = get('underline');
        var strike = get('strike');
        var allcaps = get('allcaps');
        panel.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]').forEach(function(preview) {
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-font-weight',
                bold ? (target === 'title' ? '800' : '700') : '400');
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-font-style',
                italic ? 'italic' : 'normal');
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-text-decoration',
                slideshowGetTextDecoration(underline, strike));
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-text-transform',
                allcaps ? 'uppercase' : 'none');
        });
        ['bold', 'italic', 'underline', 'strike', 'allcaps'].forEach(function(style) {
            var input = panel.querySelector('[data-slideshow-text-style-input=\"' + target + style + '\"]');
            var active = input && input.value === '1';
            panel.querySelectorAll('[data-slideshow-text-style-buttons=\"' + target + '\"] [data-slideshow-text-style=\"' + style + '\"]').forEach(function(button) {
                button.classList.toggle('active', active);
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-secondary', !active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        });
        var preview = panel.querySelector('[data-slideshow-overlay-preview=\"1\"][data-slideshow-preview-editor=\"1\"]');
        if (preview) {
            slideshowPreviewSyncButtons(preview);
        }
    };
    var slideshowPreviewCaptureTextStyles = function(panel) {
        var styles = {};
        if (!panel) {
            return styles;
        }
        panel.querySelectorAll('[data-slideshow-text-style-input]').forEach(function(input) {
            styles[input.getAttribute('data-slideshow-text-style-input')] = input.value === '1' ? '1' : '0';
        });
        return styles;
    };
    var slideshowPreviewApplyTextStyles = function(panel, styles) {
        if (!panel || !styles) {
            return;
        }
        panel.querySelectorAll('[data-slideshow-text-style-input]').forEach(function(input) {
            var key = input.getAttribute('data-slideshow-text-style-input');
            if (Object.prototype.hasOwnProperty.call(styles, key)) {
                input.value = styles[key] === '1' ? '1' : '0';
            }
        });
        ['title', 'body', 'action', 'label'].forEach(function(target) {
            slideshowApplyTextStyle(panel, target);
        });
    };
    var slideshowPreviewCaptureAlignments = function(panel) {
        var alignments = {};
        if (!panel) {
            return alignments;
        }
        panel.querySelectorAll('[data-slideshow-alignment-input]').forEach(function(input) {
            alignments[input.getAttribute('data-slideshow-alignment-input')] =
                slideshowNormaliseAlignment(input.value || 'center');
        });
        return alignments;
    };
    var slideshowPreviewApplyAlignments = function(panel, alignments) {
        if (!panel || !alignments) {
            return;
        }
        ['title', 'body', 'label'].forEach(function(target) {
            if (Object.prototype.hasOwnProperty.call(alignments, target)) {
                slideshowApplyAlignment(panel, target, alignments[target]);
            }
        });
    };
    var slideshowPreviewCaptureState = function(preview) {
        var panel = preview.closest('[data-slideshow-overlay-settings=\"1\"]');
        return {
            label: {
                x: parseFloat((panel.querySelector('[data-slideshow-position-input=\"label-x\"]') || {}).value || slideshowPreviewDefaults.label.x),
                y: parseFloat((panel.querySelector('[data-slideshow-position-input=\"label-y\"]') || {}).value || slideshowPreviewDefaults.label.y)
            },
            title: {
                x: parseFloat((panel.querySelector('[data-slideshow-position-input=\"title-x\"]') || {}).value || slideshowPreviewDefaults.title.x),
                y: parseFloat((panel.querySelector('[data-slideshow-position-input=\"title-y\"]') || {}).value || slideshowPreviewDefaults.title.y)
            },
            body: {
                x: parseFloat((panel.querySelector('[data-slideshow-position-input=\"body-x\"]') || {}).value || slideshowPreviewDefaults.body.x),
                y: parseFloat((panel.querySelector('[data-slideshow-position-input=\"body-y\"]') || {}).value || slideshowPreviewDefaults.body.y)
            },
            action: {
                x: parseFloat((panel.querySelector('[data-slideshow-position-input=\"action-x\"]') || {}).value || slideshowPreviewDefaults.action.x),
                y: parseFloat((panel.querySelector('[data-slideshow-position-input=\"action-y\"]') || {}).value || slideshowPreviewDefaults.action.y)
            },
            textStyles: slideshowPreviewCaptureTextStyles(panel),
            alignments: slideshowPreviewCaptureAlignments(panel)
        };
    };
    var slideshowPreviewApplyState = function(preview, state) {
        ['label', 'title', 'body', 'action'].forEach(function(key) {
            if (state && state[key]) {
                slideshowPreviewApplyPosition(preview, key, state[key].x, state[key].y);
            }
        });
        slideshowPreviewApplyTextStyles(preview.closest('[data-slideshow-overlay-settings=\"1\"]'), state ? state.textStyles : null);
        slideshowPreviewApplyAlignments(preview.closest('[data-slideshow-overlay-settings=\"1\"]'), state ? state.alignments : null);
    };
    var slideshowPreviewSyncButtons = function(preview) {
        if (!preview) {
            return;
        }
        var root = preview.closest('[data-slideshow-overlay-settings=\"1\"]');
        var undo = root.querySelector('[data-action=\"local-course-banner-builder-slideshow-preview-undo\"]');
        var redo = root.querySelector('[data-action=\"local-course-banner-builder-slideshow-preview-redo\"]');
        var recenter = root.querySelector('[data-action=\"local-course-banner-builder-slideshow-preview-recenter-element\"]');
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
        root.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-selected-slideshow-text-style\"]').forEach(function(button) {
            button.classList.toggle('d-none', !textSelected);
            button.disabled = !textSelected;
            if (!textSelected) {
                slideshowPreviewSetToolbarButtonActive(button, false);
                return;
            }
            var style = button.getAttribute('data-slideshow-text-style');
            var input = root.querySelector('[data-slideshow-text-style-input=\"' + selectedKey + style + '\"]');
            var active = input && input.value === '1';
            slideshowPreviewSetToolbarButtonActive(button, active);
        });
        var orientation = (root.querySelector('[data-slideshow-label-orientation-input]') || {}).value || 'row';
        var alignmentSelected = selectedKey === 'title' || selectedKey === 'body' ||
            (selectedKey === 'label' && orientation === 'column');
        root.querySelectorAll('[data-action=\"local-course-banner-builder-set-selected-slideshow-alignment\"]').forEach(function(button) {
            if (button.hasAttribute('data-slideshow-align-target')) {
                return;
            }
            button.classList.toggle('d-none', !alignmentSelected);
            button.disabled = !alignmentSelected;
            if (!alignmentSelected) {
                slideshowPreviewSetToolbarButtonActive(button, false);
                return;
            }
            var input = root.querySelector('[data-slideshow-alignment-input=\"' + selectedKey + '\"]');
            var active = input && input.value === button.getAttribute('data-slideshow-align');
            slideshowPreviewSetToolbarButtonActive(button, active);
        });
        slideshowPreviewSyncSnapButtons(root);
    };
    var slideshowPreviewUsesCircleActiveState = function(button) {
        if (!button) {
            return false;
        }
        var action = button.getAttribute('data-action') || '';
        return action === 'local-course-banner-builder-toggle-selected-slideshow-text-style' ||
            action === 'local-course-banner-builder-set-selected-slideshow-alignment';
    };
    var slideshowPreviewSetToolbarButtonActive = function(button, active) {
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
    var slideshowPreviewSyncToolbarOnNextFrame = function(preview) {
        if (!preview) {
            return;
        }
        window.requestAnimationFrame(function() {
            slideshowPreviewSyncButtons(preview);
        });
    };
    var slideshowPreviewPushUndo = function(preview) {
        var undoStack = slideshowPreviewReadJson(preview.dataset.previewUndoStack || '[]', []);
        undoStack.push(slideshowPreviewCaptureState(preview));
        if (undoStack.length > 40) {
            undoStack = undoStack.slice(undoStack.length - 40);
        }
        preview.dataset.previewUndoStack = JSON.stringify(undoStack);
        preview.dataset.previewRedoStack = '[]';
        slideshowPreviewSyncButtons(preview);
    };
    var slideshowPreviewOpenSidePanelForSelection = function(root, key) {
        var target = {
            label: 'labelshape',
            title: 'titletext',
            body: 'bodytext',
            action: 'buttonshape'
        }[key];
        if (!root || !target) {
            return;
        }
        root.querySelectorAll('[data-slideshow-side-panel]').forEach(function(panel) {
            var active = panel.getAttribute('data-slideshow-side-panel') === target;
            slideshowSetSidePanelVisible(panel, active);
        });
        slideshowSyncSidePanelButtons(root);
    };
    var slideshowPreviewSelect = function(preview, key) {
        if (!preview) {
            return;
        }
        Array.prototype.slice.call(preview.querySelectorAll('[data-slideshow-preview-draggable]')).forEach(function(node) {
            node.classList.toggle(slideshowPreviewSelectionClass, node.getAttribute('data-slideshow-preview-draggable') === key);
        });
        slideshowPreviewSyncButtons(preview);
        slideshowPreviewOpenSidePanelForSelection(preview.closest('[data-slideshow-overlay-settings=\"1\"]'), key);
    };
    document.querySelectorAll('[data-slideshow-overlay-preview=\"1\"][data-slideshow-preview-editor=\"1\"]').forEach(function(preview) {
        preview.dataset.previewUndoStack = '[]';
        preview.dataset.previewRedoStack = '[]';
        preview.addEventListener('pointerdown', function(event) {
            var target = event.target.closest('[data-slideshow-preview-draggable]');
            if (!target) {
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
                var root = preview.closest('[data-slideshow-overlay-settings=\"1\"]');
                var inputname = slideshowPreviewSizeInputs[key];
                if (key === 'action') {
                    var edge = handle.getAttribute('data-slideshow-preview-resize-handle');
                    inputname = (edge === 'left' || edge === 'right') ? 'actionwidth' : 'actionheight';
                }
                var range = root && inputname ? root.querySelector('[name=\"' + inputname + '\"]') : null;
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
    document.addEventListener('keydown', function(event) {
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
        Array.prototype.slice.call(document.querySelectorAll('[data-slideshow-overlay-preview=\"1\"][data-slideshow-preview-editor=\"1\"]')).some(function(preview) {
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
        window.setTimeout(function() {
            slideshowPreviewClearGuides(selectedPreview);
        }, 450);
    });
    document.querySelectorAll('[data-slideshow-label-orientation-option]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            slideshowSyncLabelOrientation(panel, button.getAttribute('data-slideshow-label-orientation-option'));
        });
    });
    document.querySelectorAll('[data-slideshow-label-orientation-input]').forEach(function(input) {
        input.addEventListener('change', function() {
            slideshowSyncLabelOrientation(input.closest('[data-slideshow-overlay-settings=\"1\"]'), input.value);
        });
        slideshowSyncLabelOrientation(input.closest('[data-slideshow-overlay-settings=\"1\"]'), input.value);
    });
    document.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-slideshow-label-orientation\"]').forEach(function(button) {
        button.addEventListener('pointerdown', function(e) {
            e.stopPropagation();
        });
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            var input = panel ? panel.querySelector('[data-slideshow-label-orientation-input]') : null;
            slideshowSyncLabelOrientation(panel, input && input.value === 'column' ? 'row' : 'column');
        });
    });
    document.querySelectorAll('[data-slideshow-corner-option]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            slideshowSyncCornerStyle(
                panel,
                button.getAttribute('data-slideshow-corner-option'),
                button.getAttribute('data-slideshow-corner-value')
            );
        });
    });
    document.querySelectorAll('[data-slideshow-corner-input]').forEach(function(input) {
        input.addEventListener('change', function() {
            slideshowSyncCornerStyle(
                input.closest('[data-slideshow-overlay-settings=\"1\"]'),
                input.getAttribute('data-slideshow-corner-input'),
                input.value
            );
        });
        slideshowSyncCornerStyle(
            input.closest('[data-slideshow-overlay-settings=\"1\"]'),
            input.getAttribute('data-slideshow-corner-input'),
            input.value
        );
    });
    document.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-slideshow-corners\"]').forEach(function(button) {
        button.addEventListener('pointerdown', function(e) {
            e.stopPropagation();
        });
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            var target = button.getAttribute('data-slideshow-corner-target') || 'label';
            var input = panel ? panel.querySelector('[data-slideshow-corner-input=\"' + target + '\"]') : null;
            slideshowSyncCornerStyle(panel, target, input && input.value === 'square' ? 'rounded' : 'square');
        });
    });
    document.addEventListener('pointermove', function(event) {
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
    var slideshowPreviewStopDrag = function() {
        if (!slideshowPreviewDrag) {
            return;
        }
        slideshowPreviewSyncButtons(slideshowPreviewDrag.preview);
        slideshowPreviewClearGuides(slideshowPreviewDrag.preview);
        slideshowPreviewDrag = null;
    };
    document.addEventListener('pointerup', slideshowPreviewStopDrag);
    document.addEventListener('pointercancel', slideshowPreviewStopDrag);
    document.querySelectorAll('[data-action=\"local-course-banner-builder-reset-slideshow-overlay\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            if (!panel) {
                return;
            }
            var color = panel.querySelector('[data-slideshow-overlay-color=\"1\"]');
            var opacity = panel.querySelector('[data-slideshow-overlay-opacity=\"1\"]');
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-reset-slideshow-labels\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            if (!panel) {
                return;
            }
            panel.querySelectorAll('[data-slideshow-label-var][data-default-value]').forEach(function(input) {
                input.value = input.getAttribute('data-default-value') || '#000000';
                input.dispatchEvent(new Event('input', {bubbles: true}));
            });
            panel.querySelectorAll('[data-slideshow-text-size=\"label\"][data-default-value]').forEach(function(input) {
                input.value = input.getAttribute('data-default-value') || '100';
                input.dispatchEvent(new Event('input', {bubbles: true}));
            });
            panel.querySelectorAll('[name=\"labeltextsize\"][data-default-value]').forEach(function(input) {
                input.value = input.getAttribute('data-default-value') || '100';
                input.dispatchEvent(new Event('input', {bubbles: true}));
            });
            panel.querySelectorAll('[data-slideshow-design-input=\"1\"][data-default-value]').forEach(function(input) {
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
            var corners = panel.querySelector('[data-slideshow-corner-input=\"label\"]');
            if (corners) {
                corners.value = corners.getAttribute('data-default-value') || 'rounded';
                corners.dispatchEvent(new Event('change', {bubbles: true}));
            }
            var alignment = panel.querySelector('[data-slideshow-alignment-input=\"label\"]');
            if (alignment) {
                slideshowApplyAlignment(panel, 'label', alignment.getAttribute('data-default-value') || 'center');
            }
            var preview = slideshowPreviewGetPreview(button);
            if (preview) {
                slideshowPreviewApplyPosition(preview, 'label', slideshowPreviewDefaults.label.x, slideshowPreviewDefaults.label.y);
            }
        });
    });
    document.querySelectorAll('[data-action=\"local-course-banner-builder-reset-slideshow-text\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            var preview = slideshowPreviewGetPreview(button);
            if (!panel || !preview) {
                return;
            }
            panel.querySelectorAll('[data-slideshow-text-var][data-default-value]').forEach(function(input) {
                if (input.getAttribute('data-slideshow-text-size') === 'label') {
                    return;
                }
                input.value = input.getAttribute('data-default-value') || '';
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
            panel.querySelectorAll('[data-slideshow-design-input=\"1\"][data-default-value]').forEach(function(input) {
                if (input.name && input.name.indexOf('label') === 0) {
                    return;
                }
                input.value = input.getAttribute('data-default-value') || input.value;
                input.dispatchEvent(new Event('input', {bubbles: true}));
                input.dispatchEvent(new Event('change', {bubbles: true}));
            });
            panel.querySelectorAll('[data-slideshow-text-style-input][data-default-value]').forEach(function(input) {
                input.value = input.getAttribute('data-default-value') || '0';
                slideshowApplyTextStyle(panel, input.getAttribute('data-slideshow-text-style-target'));
            });
            panel.querySelectorAll('[data-slideshow-alignment-input][data-default-value]').forEach(function(input) {
                var target = input.getAttribute('data-slideshow-alignment-input');
                if (target === 'label') {
                    return;
                }
                slideshowApplyAlignment(panel, target, input.getAttribute('data-default-value') || 'center');
            });
            var actioncorners = panel.querySelector('[data-slideshow-corner-input=\"action\"]');
            if (actioncorners) {
                actioncorners.value = actioncorners.getAttribute('data-default-value') || 'rounded';
                actioncorners.dispatchEvent(new Event('change', {bubbles: true}));
            }
            ['title', 'body', 'action'].forEach(function(key) {
                slideshowPreviewApplyPosition(preview, key, slideshowPreviewDefaults[key].x, slideshowPreviewDefaults[key].y);
            });
            slideshowPreviewSelect(preview, '');
        });
    });
    document.querySelectorAll('[data-slideshow-text-style-input]').forEach(function(input) {
        slideshowApplyTextStyle(input.closest('[data-slideshow-overlay-settings=\"1\"]'), input.getAttribute('data-slideshow-text-style-target'));
    });
    document.querySelectorAll('[data-slideshow-alignment-input]').forEach(function(input) {
        slideshowApplyAlignment(
            input.closest('[data-slideshow-overlay-settings=\"1\"]'),
            input.getAttribute('data-slideshow-alignment-input'),
            input.value || input.getAttribute('data-default-value') || 'center'
        );
    });
    document.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-slideshow-text-style\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            var target = button.getAttribute('data-slideshow-text-style-target');
            var style = button.getAttribute('data-slideshow-text-style');
            var input = panel ? panel.querySelector('[data-slideshow-text-style-input=\"' + target + style + '\"]') : null;
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-selected-slideshow-text-style\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var preview = slideshowPreviewGetPreview(button);
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            var selected = preview ? preview.querySelector('.' + slideshowPreviewSelectionClass) : null;
            var target = selected ? selected.getAttribute('data-slideshow-preview-draggable') : '';
            if (['title', 'body', 'action', 'label'].indexOf(target) === -1) {
                return;
            }
            var style = button.getAttribute('data-slideshow-text-style');
            var input = panel ? panel.querySelector('[data-slideshow-text-style-input=\"' + target + style + '\"]') : null;
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-set-selected-slideshow-alignment\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var preview = slideshowPreviewGetPreview(button);
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
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
                    '[data-action=\"local-course-banner-builder-set-selected-slideshow-alignment\"]'
                ).forEach(function(item) {
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-slideshow-preview-snap\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-reset-slideshow-all\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            if (!panel) {
                return;
            }
            [
                'local-course-banner-builder-reset-slideshow-overlay',
                'local-course-banner-builder-reset-slideshow-text',
                'local-course-banner-builder-reset-slideshow-labels'
            ].forEach(function(action) {
                var target = panel.querySelector('[data-action=\"' + action + '\"]');
                if (target && target !== button) {
                    target.click();
                }
            });
        });
    });
    document.querySelectorAll('[data-action=\"local-course-banner-builder-slideshow-preview-undo\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-slideshow-preview-redo\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-slideshow-preview-recenter-element\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-slideshow-preview-recenter-all\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
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
    document.querySelectorAll('.local-course-banner-builder-slideshow-preview-toolbar [data-toggle=\"popover\"]').forEach(function(button) {
        button.addEventListener('click', function() {
            if (window.jQuery && window.jQuery.fn && window.jQuery.fn.popover) {
                window.jQuery(button).popover('hide');
            }
            button.removeAttribute('aria-describedby');
            document.querySelectorAll('.popover').forEach(function(popover) {
                popover.remove();
            });
        });
    });
    document.querySelectorAll('form.local-course-banner-builder-slideshow-card').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            var submitter = event.submitter || document.activeElement;
            if (!submitter || !submitter.closest('.local-course-banner-builder-slideshow-actions') ||
                    submitter.name === 'defaultsettings') {
                return;
            }
            form.querySelectorAll('[data-slideshow-bulk-clone=\"1\"]').forEach(function(node) {
                node.remove();
            });
            var saveAll = document.createElement('input');
            saveAll.type = 'hidden';
            saveAll.name = 'saveallslideshows';
            saveAll.value = '1';
            saveAll.setAttribute('data-slideshow-bulk-clone', '1');
            form.appendChild(saveAll);
            document.querySelectorAll('form.local-course-banner-builder-slideshow-card').forEach(function(source) {
                var contextInput = source.querySelector('input[name=\"context\"]');
                var context = contextInput ? contextInput.value : '';
                if (!context) {
                    return;
                }
                source.querySelectorAll('input[name], select[name], textarea[name]').forEach(function(field) {
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
    document.querySelectorAll('[data-action=\"local-course-banner-builder-save-all-slideshows\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
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
");

/**
 * Clean one bulk-posted slideshow form payload.
 *
 * @param array $values
 * @return array
 */
function local_course_banner_builder_clean_slideshow_values(array $values): array {
    $clean = [];
    foreach (['enabled', 'forums', 'siteannouncements', 'assignments', 'quizzes', 'autoplay', 'arrows', 'dots',
        'titlebold', 'titleitalic', 'titleunderline', 'titlestrike', 'titleallcaps',
        'bodybold', 'bodyitalic', 'bodyunderline', 'bodystrike', 'bodyallcaps',
        'actionbold', 'actionitalic', 'actionunderline', 'actionstrike', 'actionallcaps',
        'labelbold', 'labelitalic', 'labelunderline', 'labelstrike', 'labelallcaps'] as $field) {
        $clean[$field] = empty($values[$field]) ? 0 : 1;
    }
    foreach (['delay', 'overlayopacity', 'titlefontsize', 'bodyfontsize', 'actionsize', 'actionwidth',
        'actionheight', 'labelsize', 'labeltextsize',
        'actionopacity', 'actionborderwidth', 'actionradius', 'actionpadding',
        'actionshadowopacity', 'actionshadowblur', 'actionshadowdistance', 'actionshadowdirection',
        'labelopacity', 'labelborderwidth', 'labelradius', 'labelpadding',
        'labelshadowopacity', 'labelshadowblur', 'labelshadowdistance', 'labelshadowdirection'] as $field) {
        $clean[$field] = clean_param($values[$field] ?? 0, PARAM_INT);
    }
    $clean['bodylineheight'] = clean_param($values['bodylineheight'] ?? 0, PARAM_FLOAT);
    foreach (['titlex', 'titley', 'bodyx', 'bodyy', 'actionx', 'actiony', 'labelx', 'labely'] as $field) {
        $clean[$field] = clean_param($values[$field] ?? 0, PARAM_FLOAT);
    }
    foreach (['overlaycolor', 'titlecolor', 'bodycolor', 'titlefontfamily', 'bodyfontfamily',
        'actionbackgroundcolor', 'actionbordercolor', 'actionshadowcolor', 'actionfontfamily', 'actiontextcolor',
        'labelbackgroundcolor', 'labelbordercolor', 'labelshadowcolor', 'labelfontfamily', 'labeltextcolor',
        'label_forums_background', 'label_forums_text',
        'label_siteannouncements_background', 'label_siteannouncements_text',
        'label_assignments_background', 'label_assignments_text',
        'label_quizzes_background', 'label_quizzes_text'] as $field) {
        $clean[$field] = clean_param($values[$field] ?? '', PARAM_RAW_TRIMMED);
    }
    foreach (manager::get_default_slideshow_label_colors() as $type => $defaults) {
        foreach (['background', 'text', 'border', 'shadow'] as $role) {
            $field = 'label_' . $type . '_' . $role;
            $clean[$field] = clean_param($values[$field] ?? ($defaults[$role] ?? ''), PARAM_RAW_TRIMMED);
        }
    }
    foreach (['labelorientation', 'labelcorners', 'actioncorners', 'titlealign', 'bodyalign', 'labelalign'] as $field) {
        $clean[$field] = clean_param($values[$field] ?? '', PARAM_ALPHA);
    }

    return $clean;
}

if (optional_param('updateslideshow', 0, PARAM_BOOL) && confirm_sesskey()) {
    $context = required_param('context', PARAM_ALPHA);
    if (optional_param('saveallslideshows', 0, PARAM_BOOL)) {
        $bulk = $_POST['slideshowbulk'] ?? [];
        foreach ([manager::SLIDESHOW_CONTEXT_COURSE, manager::SLIDESHOW_CONTEXT_SITE] as $bulkcontext) {
            if (!empty($bulk[$bulkcontext]) && is_array($bulk[$bulkcontext])) {
                manager::set_slideshow_config(
                    $bulkcontext,
                    local_course_banner_builder_clean_slideshow_values($bulk[$bulkcontext])
                );
            }
        }
    } else if (optional_param('defaultsettings', 0, PARAM_BOOL)) {
        manager::reset_slideshow_config($context);
    } else {
        $defaults = manager::get_default_slideshow_label_colors();
        $defaultoverlay = optional_param('defaultoverlaysettings', 0, PARAM_BOOL);
        $defaulttext = optional_param('defaulttextsettings', 0, PARAM_BOOL);
        $defaultlabels = optional_param('defaultlabelsettings', 0, PARAM_BOOL);
        $defaultall = optional_param('defaultallsettings', 0, PARAM_BOOL);
        $styledefaults = manager::get_default_slideshow_style_values();
        $slideshowvalues = [
            'enabled' => optional_param('enabled', 0, PARAM_BOOL),
            'forums' => optional_param('forums', 0, PARAM_BOOL),
            'siteannouncements' => optional_param('siteannouncements', 0, PARAM_BOOL),
            'assignments' => optional_param('assignments', 0, PARAM_BOOL),
            'quizzes' => optional_param('quizzes', 0, PARAM_BOOL),
            'autoplay' => optional_param('autoplay', 0, PARAM_BOOL),
            'delay' => optional_param('delay', manager::SLIDESHOW_DEFAULT_DELAY, PARAM_INT),
            'arrows' => optional_param('arrows', 0, PARAM_BOOL),
            'dots' => optional_param('dots', 0, PARAM_BOOL),
            'overlaycolor' => $defaultoverlay
                ? manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR
                : ($defaultall
                    ? manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR
                    : optional_param('overlaycolor', manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR, PARAM_RAW_TRIMMED)),
            'overlayopacity' => $defaultoverlay
                ? (int)(manager::SLIDESHOW_DEFAULT_OVERLAY_OPACITY * 100)
                : ($defaultall
                    ? (int)(manager::SLIDESHOW_DEFAULT_OVERLAY_OPACITY * 100)
                    : optional_param('overlayopacity', (int)(manager::SLIDESHOW_DEFAULT_OVERLAY_OPACITY * 100), PARAM_INT)),
            'titlefontsize' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT
                : optional_param('titlefontsize', manager::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT, PARAM_INT),
            'bodyfontsize' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT
                : optional_param('bodyfontsize', manager::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT, PARAM_INT),
            'bodylineheight' => $defaulttext || $defaultall
                ? (float)$styledefaults['bodylineheight']
                : optional_param('bodylineheight', (float)$styledefaults['bodylineheight'], PARAM_FLOAT),
            'actionsize' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT
                : optional_param('actionsize', manager::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT, PARAM_INT),
            'actionwidth' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT
                : optional_param('actionwidth', manager::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT, PARAM_INT),
            'actionheight' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT
                : optional_param('actionheight', manager::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT, PARAM_INT),
            'actioncorners' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_ACTION_CORNERS
                : optional_param('actioncorners', manager::SLIDESHOW_DEFAULT_ACTION_CORNERS, PARAM_ALPHA),
            'labelsize' => $defaultlabels || $defaultall
                ? manager::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT
                : optional_param('labelsize', manager::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT, PARAM_INT),
            'labeltextsize' => $defaultlabels || $defaultall
                ? 100
                : optional_param('labeltextsize', 100, PARAM_INT),
            'labelorientation' => $defaultlabels || $defaultall
                ? manager::SLIDESHOW_DEFAULT_LABEL_ORIENTATION
                : optional_param('labelorientation', manager::SLIDESHOW_DEFAULT_LABEL_ORIENTATION, PARAM_ALPHA),
            'labelcorners' => $defaultlabels || $defaultall
                ? manager::SLIDESHOW_DEFAULT_LABEL_CORNERS
                : optional_param('labelcorners', manager::SLIDESHOW_DEFAULT_LABEL_CORNERS, PARAM_ALPHA),
            'titlecolor' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_TITLE_COLOR
                : optional_param('titlecolor', manager::SLIDESHOW_DEFAULT_TITLE_COLOR, PARAM_RAW_TRIMMED),
            'bodycolor' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_BODY_COLOR
                : optional_param('bodycolor', manager::SLIDESHOW_DEFAULT_BODY_COLOR, PARAM_RAW_TRIMMED),
            'titlefontfamily' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY
                : optional_param('titlefontfamily', manager::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY, PARAM_RAW_TRIMMED),
            'bodyfontfamily' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY
                : optional_param('bodyfontfamily', manager::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY, PARAM_RAW_TRIMMED),
            'titlealign' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_TITLE_ALIGN
                : optional_param('titlealign', manager::SLIDESHOW_DEFAULT_TITLE_ALIGN, PARAM_ALPHA),
            'bodyalign' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_BODY_ALIGN
                : optional_param('bodyalign', manager::SLIDESHOW_DEFAULT_BODY_ALIGN, PARAM_ALPHA),
            'labelalign' => $defaultlabels || $defaultall
                ? manager::SLIDESHOW_DEFAULT_LABEL_ALIGN
                : optional_param('labelalign', manager::SLIDESHOW_DEFAULT_LABEL_ALIGN, PARAM_ALPHA),
            'titlebold' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_TITLE_BOLD : optional_param('titlebold', 0, PARAM_BOOL),
            'titleitalic' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_TITLE_ITALIC : optional_param('titleitalic', 0, PARAM_BOOL),
            'titleunderline' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_TITLE_UNDERLINE : optional_param('titleunderline', 0, PARAM_BOOL),
            'titlestrike' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_TITLE_STRIKE : optional_param('titlestrike', 0, PARAM_BOOL),
            'titleallcaps' => $defaulttext || $defaultall ? false : optional_param('titleallcaps', 0, PARAM_BOOL),
            'bodybold' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_BODY_BOLD : optional_param('bodybold', 0, PARAM_BOOL),
            'bodyitalic' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_BODY_ITALIC : optional_param('bodyitalic', 0, PARAM_BOOL),
            'bodyunderline' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_BODY_UNDERLINE : optional_param('bodyunderline', 0, PARAM_BOOL),
            'bodystrike' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_BODY_STRIKE : optional_param('bodystrike', 0, PARAM_BOOL),
            'bodyallcaps' => $defaulttext || $defaultall ? false : optional_param('bodyallcaps', 0, PARAM_BOOL),
            'actionbold' => $defaulttext || $defaultall ? true : optional_param('actionbold', 0, PARAM_BOOL),
            'actionitalic' => $defaulttext || $defaultall ? false : optional_param('actionitalic', 0, PARAM_BOOL),
            'actionunderline' => $defaulttext || $defaultall ? false : optional_param('actionunderline', 0, PARAM_BOOL),
            'actionstrike' => $defaulttext || $defaultall ? false : optional_param('actionstrike', 0, PARAM_BOOL),
            'actionallcaps' => $defaulttext || $defaultall ? false : optional_param('actionallcaps', 0, PARAM_BOOL),
            'labelbold' => $defaultlabels || $defaultall ? true : optional_param('labelbold', 0, PARAM_BOOL),
            'labelitalic' => $defaultlabels || $defaultall ? false : optional_param('labelitalic', 0, PARAM_BOOL),
            'labelunderline' => $defaultlabels || $defaultall ? false : optional_param('labelunderline', 0, PARAM_BOOL),
            'labelstrike' => $defaultlabels || $defaultall ? false : optional_param('labelstrike', 0, PARAM_BOOL),
            'labelallcaps' => $defaultlabels || $defaultall ? true : optional_param('labelallcaps', 1, PARAM_BOOL),
            'titlex' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_TITLE_X
                : optional_param('titlex', manager::SLIDESHOW_DEFAULT_TITLE_X, PARAM_FLOAT),
            'titley' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_TITLE_Y
                : optional_param('titley', manager::SLIDESHOW_DEFAULT_TITLE_Y, PARAM_FLOAT),
            'bodyx' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_BODY_X
                : optional_param('bodyx', manager::SLIDESHOW_DEFAULT_BODY_X, PARAM_FLOAT),
            'bodyy' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_BODY_Y
                : optional_param('bodyy', manager::SLIDESHOW_DEFAULT_BODY_Y, PARAM_FLOAT),
            'actionx' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_ACTION_X
                : optional_param('actionx', manager::SLIDESHOW_DEFAULT_ACTION_X, PARAM_FLOAT),
            'actiony' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_ACTION_Y
                : optional_param('actiony', manager::SLIDESHOW_DEFAULT_ACTION_Y, PARAM_FLOAT),
            'labelx' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_LABEL_X
                : optional_param('labelx', manager::SLIDESHOW_DEFAULT_LABEL_X, PARAM_FLOAT),
            'labely' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_LABEL_Y
                : optional_param('labely', manager::SLIDESHOW_DEFAULT_LABEL_Y, PARAM_FLOAT),
            'label_forums_background' => $defaultlabels
                ? $defaults['forums']['background']
                : ($defaultall ? $defaults['forums']['background']
                    : optional_param('label_forums_background', $defaults['forums']['background'], PARAM_RAW_TRIMMED)),
            'label_forums_text' => $defaultlabels
                ? $defaults['forums']['text']
                : ($defaultall ? $defaults['forums']['text']
                    : optional_param('label_forums_text', $defaults['forums']['text'], PARAM_RAW_TRIMMED)),
            'label_siteannouncements_background' => $defaultlabels
                ? $defaults['siteannouncements']['background']
                : ($defaultall ? $defaults['siteannouncements']['background']
                    : optional_param('label_siteannouncements_background', $defaults['siteannouncements']['background'], PARAM_RAW_TRIMMED)),
            'label_siteannouncements_text' => $defaultlabels
                ? $defaults['siteannouncements']['text']
                : ($defaultall ? $defaults['siteannouncements']['text']
                    : optional_param('label_siteannouncements_text', $defaults['siteannouncements']['text'], PARAM_RAW_TRIMMED)),
            'label_assignments_background' => $defaultlabels
                ? $defaults['assignments']['background']
                : ($defaultall ? $defaults['assignments']['background']
                    : optional_param('label_assignments_background', $defaults['assignments']['background'], PARAM_RAW_TRIMMED)),
            'label_assignments_text' => $defaultlabels
                ? $defaults['assignments']['text']
                : ($defaultall ? $defaults['assignments']['text']
                    : optional_param('label_assignments_text', $defaults['assignments']['text'], PARAM_RAW_TRIMMED)),
            'label_quizzes_background' => $defaultlabels
                ? $defaults['quizzes']['background']
                : ($defaultall ? $defaults['quizzes']['background']
                    : optional_param('label_quizzes_background', $defaults['quizzes']['background'], PARAM_RAW_TRIMMED)),
            'label_quizzes_text' => $defaultlabels
                ? $defaults['quizzes']['text']
                : ($defaultall ? $defaults['quizzes']['text']
                    : optional_param('label_quizzes_text', $defaults['quizzes']['text'], PARAM_RAW_TRIMMED)),
        ];
        foreach ($defaults as $type => $colours) {
            foreach (['background', 'text', 'border', 'shadow'] as $role) {
                $slideshowvalues['label_' . $type . '_' . $role] = ($defaultlabels || $defaultall)
                    ? $colours[$role]
                    : optional_param('label_' . $type . '_' . $role, $colours[$role], PARAM_RAW_TRIMMED);
            }
        }
        foreach ($styledefaults as $field => $defaultvalue) {
            if (is_int($defaultvalue)) {
                $slideshowvalues[$field] = ($defaulttext || $defaultlabels || $defaultall)
                    ? $defaultvalue
                    : optional_param($field, $defaultvalue, PARAM_INT);
            } else {
                $slideshowvalues[$field] = ($defaulttext || $defaultlabels || $defaultall)
                    ? $defaultvalue
                    : optional_param($field, $defaultvalue, PARAM_RAW_TRIMMED);
            }
        }
        manager::set_slideshow_config($context, $slideshowvalues);
    }
    redirect(new moodle_url('/local/course_banner_builder/admin_slideshow.php'), get_string('slideshowsettingssaved', 'local_course_banner_builder'));
}

if (optional_param('savebannerformat', 0, PARAM_BOOL) && confirm_sesskey()) {
    $formatcontext = required_param('bannerformatcontext', PARAM_ALPHA);
    $bannerformat = required_param('bannerformat', PARAM_ALPHAEXT);
    if ($formatcontext === manager::SLIDESHOW_CONTEXT_SITE) {
        manager::set_site_banner_format($bannerformat);
    } else {
        manager::set_course_banner_format($bannerformat);
    }
    redirect(new moodle_url('/local/course_banner_builder/admin_slideshow.php'), get_string('changessaved'));
}

/**
 * Render a small hover help icon.
 *
 * @param string $content
 * @return string
 */
function local_course_banner_builder_slideshow_help(string $content): string {
    return html_writer::tag('button', '?', [
        'type' => 'button',
        'class' => 'btn btn-link p-0 icon-no-margin local-course-banner-builder-help-dot',
        'data-local-slideshow-help' => '1',
        'data-content' => html_writer::div(html_writer::tag('p', $content), 'no-overflow'),
        'aria-label' => get_string('help'),
    ]);
}

/**
 * Render a banner format modal from the slideshow admin without leaving the page.
 *
 * @param string $context
 * @param string $currentformat
 * @return string
 */
function local_course_banner_builder_render_slideshow_banner_format_modal(
    string $context,
    string $currentformat
): string {
    $formats = manager::get_banner_format_options();
    $currentformat = manager::normalise_banner_format($currentformat);
    $modalid = 'local-course-banner-builder-slideshow-' . $context . '-format-modal';
    $cards = '';

    foreach ($formats as $format => $label) {
        $descriptionkey = match ($format) {
            manager::BANNER_FORMAT_CONTENT_WIDE => 'bannerformat:contentwide_help',
            manager::BANNER_FORMAT_FULLWIDTH_TOP => 'bannerformat:fullwidthtop_help',
            manager::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT => 'bannerformat:fullwidthtopcompact_help',
            default => 'bannerformat:standard_help',
        };
        $radioattrs = [
            'type' => 'radio',
            'name' => 'bannerformat',
            'value' => $format,
        ];
        if ($format === $currentformat) {
            $radioattrs['checked'] = 'checked';
        }
        $skeletonclasses = 'local-course-banner-builder-format-skeleton local-course-banner-builder-format-skeleton--' . $format;
        $skeleton = html_writer::div('', 'local-course-banner-builder-format-skeleton-nav') .
            html_writer::div('', 'local-course-banner-builder-format-skeleton-title') .
            html_writer::div('', 'local-course-banner-builder-format-skeleton-breadcrumb') .
            html_writer::div(
                html_writer::div('', 'local-course-banner-builder-format-skeleton-block local-course-banner-builder-format-skeleton-block--left') .
                html_writer::div(
                    html_writer::div('', 'local-course-banner-builder-format-skeleton-banner') .
                    html_writer::div('', 'local-course-banner-builder-format-skeleton-line') .
                    html_writer::div('', 'local-course-banner-builder-format-skeleton-line local-course-banner-builder-format-skeleton-line--short') .
                    html_writer::div('', 'local-course-banner-builder-format-skeleton-content'),
                    'local-course-banner-builder-format-skeleton-main'
                ) .
                html_writer::div('', 'local-course-banner-builder-format-skeleton-block local-course-banner-builder-format-skeleton-block--right'),
                'local-course-banner-builder-format-skeleton-page'
            );
        $cards .= html_writer::tag(
            'label',
            html_writer::empty_tag('input', $radioattrs) .
            html_writer::div(
                html_writer::div($skeleton, $skeletonclasses) .
                html_writer::div(
                    html_writer::tag('strong', s($label)) .
                    html_writer::div(get_string($descriptionkey, 'local_course_banner_builder'), 'text-muted'),
                    'local-course-banner-builder-format-card-copy'
                ),
                'local-course-banner-builder-format-card-body'
            ),
            ['class' => 'local-course-banner-builder-format-card' . ($format === $currentformat ? ' is-selected' : '')]
        );
    }

    $title = get_string(
        $context === manager::SLIDESHOW_CONTEXT_SITE ? 'sitebannerformatbutton' : 'coursebannerformatbutton',
        'local_course_banner_builder'
    );
    $form = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => (new moodle_url('/local/course_banner_builder/admin_slideshow.php'))->out(false),
    ]);
    $form .= html_writer::div(
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savebannerformat', 'value' => 1]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'bannerformatcontext', 'value' => $context]) .
        html_writer::div($cards, 'local-course-banner-builder-format-grid'),
        'modal-body'
    );
    $form .= html_writer::div(
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-save me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('savebannerformat', 'local_course_banner_builder')),
            ['type' => 'submit', 'class' => 'btn btn-primary local-course-banner-builder-source-settings-submit']
        ),
        'modal-footer local-course-banner-builder-format-modal-footer'
    );
    $form .= html_writer::end_tag('form');

    return html_writer::div(
        html_writer::div(
            html_writer::div(
                html_writer::div(
                    html_writer::tag('h5', $title, [
                        'class' => 'modal-title flex-grow-1',
                        'id' => $modalid . '-title',
                    ]) .
                    html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
                        'type' => 'button',
                        'class' => 'close ml-auto ms-auto',
                        'data-dismiss' => 'modal',
                        'data-bs-dismiss' => 'modal',
                        'aria-label' => get_string('closebuttontitle'),
                    ]),
                    'modal-header d-flex align-items-center'
                ) .
                $form,
                'modal-content'
            ),
            'modal-dialog modal-xl',
            ['role' => 'document']
        ),
        'modal fade',
        [
            'id' => $modalid,
            'tabindex' => '-1',
            'role' => 'dialog',
            'aria-labelledby' => $modalid . '-title',
            'aria-hidden' => 'true',
            'data-banner-format-modal' => '1',
        ]
    );
}

/**
 * Render one toggle checkbox as a compact card row.
 *
 * @param string $name
 * @param string $label
 * @param bool $checked
 * @param string $help
 * @return string
 */
function local_course_banner_builder_slideshow_toggle(
    string $name,
    string $label,
    bool $checked,
    string $help = '',
    bool $asbutton = false
): string {
    $id = 'local-course-banner-builder-slideshow-' . preg_replace('/[^a-z0-9_-]+/i', '-', $name) . '-' . uniqid();
    if ($asbutton) {
        $html = html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => $checked ? 1 : 0,
            'id' => $id,
        ]);
        $html .= html_writer::tag('button',
            html_writer::tag('i', '', [
                'class' => 'fa ' . ($checked ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
                'aria-hidden' => 'true',
            ]) . html_writer::span($checked ? get_string('enabled', 'local_course_banner_builder') : get_string('disabled', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'class' => 'btn local-course-banner-builder-slideshow-enable-button ' .
                    ($checked ? 'btn-primary' : 'btn-outline-secondary'),
                'data-local-slideshow-toggle-button' => '1',
                'data-target-input' => '#' . $id,
                'data-label-on' => get_string('enabled', 'local_course_banner_builder'),
                'data-label-off' => get_string('disabled', 'local_course_banner_builder'),
                'aria-pressed' => $checked ? 'true' : 'false',
            ]
        );
        if ($help !== '') {
            $html .= local_course_banner_builder_slideshow_help($help);
        }
        return html_writer::div($html, 'local-course-banner-builder-slideshow-toggle-button-row');
    }

    $html = html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => $name,
        'value' => 1,
        'id' => $id,
        'class' => 'form-check-input',
        'checked' => $checked ? 'checked' : null,
    ]);
    $html .= html_writer::tag('label', $label, [
        'class' => 'form-check-label',
        'for' => $id,
    ]);
    if ($help !== '') {
        $html .= local_course_banner_builder_slideshow_help($help);
    }

    return html_writer::div($html, 'form-check local-course-banner-builder-slideshow-toggle');
}

/**
 * Build one responsive slideshow font-size clamp from the default CSS values.
 *
 * @param string $kind title|body
 * @param int $percent
 * @return string
 */
function local_course_banner_builder_slideshow_font_clamp(string $kind, int $percent, string $format = ''): string {
    $scale = max(25, min(100, $percent)) / 100;
    if ($format !== '') {
        $format = manager::normalise_banner_format($format);
        if ($format === manager::BANNER_FORMAT_STANDARD) {
            $scale *= 1.24;
        } else if ($format === manager::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT) {
            $scale *= $kind === 'label' ? 1.0 : 0.78;
        }
    }
    if ($kind === 'title') {
        return 'clamp(' . round(10 * $scale, 3) . 'cqh, min(' . round(28 * $scale, 3) . 'cqh, ' .
            round(3.4 * $scale, 3) . 'cqw), ' .
            round(36 * $scale, 3) . 'cqh)';
    }
    if ($kind === 'label') {
        return 'clamp(' . round(3.5 * $scale, 3) . 'cqh, min(' . round(6.4 * $scale, 3) . 'cqh, ' .
            round(0.82 * $scale, 3) . 'cqw), ' .
            round(8.4 * $scale, 3) . 'cqh)';
    }
    if ($kind === 'action') {
        return 'clamp(' . round(6 * $scale, 3) . 'cqh, min(' . round(13 * $scale, 3) . 'cqh, ' .
            round(1.6 * $scale, 3) . 'cqw), ' .
            round(18 * $scale, 3) . 'cqh)';
    }
    if ($kind === 'actionwidth') {
        return 'clamp(' . round(10 * $scale, 3) . 'cqw, ' . round(18 * $scale, 3) . 'cqw, ' .
            round(34 * $scale, 3) . 'cqw)';
    }
    if ($kind === 'actionheight') {
        return 'clamp(' . round(10 * $scale, 3) . 'cqh, min(' . round(22 * $scale, 3) . 'cqh, ' .
            round(2.7 * $scale, 3) . 'cqw), ' .
            round(34 * $scale, 3) . 'cqh)';
    }
    return 'clamp(' . round(5.5 * $scale, 3) . 'cqh, min(' . round(14 * $scale, 3) . 'cqh, ' .
        round(1.7 * $scale, 3) . 'cqw), ' .
        round(19 * $scale, 3) . 'cqh)';
}

/**
 * Render a simple font family select.
 *
 * @param string $name
 * @param string $selected
 * @param string $cssvar
 * @param string $defaultvalue
 * @return string
 */
function local_course_banner_builder_render_slideshow_font_select(
    string $name,
    string $selected,
    string $cssvar,
    string $defaultvalue = ''
): string {
    $options = manager::get_slideshow_font_family_options();
    $select = html_writer::start_tag('select', [
        'name' => $name,
        'class' => 'custom-select',
        'style' => 'font-weight: 700;',
        'data-slideshow-text-var' => $cssvar,
        'data-slideshow-font-family' => '1',
        'data-default-value' => $defaultvalue,
    ]);
    foreach ($options as $value => $label) {
        $select .= html_writer::tag('option', s($label), [
            'value' => $value,
            'selected' => (string)$value === $selected ? 'selected' : null,
            'data-font-value' => $value,
            'style' => ($value !== '' ? 'font-family: ' . s($value) . '; ' : '') . 'font-weight: 700;',
        ]);
    }
    $select .= html_writer::end_tag('select');
    return $select;
}

/**
 * Render overlay visual settings.
 *
 * @param array $config
 * @return string
 */
function local_course_banner_builder_render_slideshow_overlay_settings(array $config, string $context = ''): string {
    $opacity = (int)round(((float)($config['overlayopacity'] ?? manager::SLIDESHOW_DEFAULT_OVERLAY_OPACITY)) * 100);
    $color = (string)($config['overlaycolor'] ?? manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR);
    $colorid = 'slideshow-overlay-color-' . uniqid();
    $opacityid = 'slideshow-overlay-opacity-' . uniqid();
    $modalid = 'local-course-banner-builder-slideshow-preview-modal-' . uniqid();
    $context = $context === manager::SLIDESHOW_CONTEXT_SITE ? manager::SLIDESHOW_CONTEXT_SITE : manager::SLIDESHOW_CONTEXT_COURSE;
    $bannerformat = $context === manager::SLIDESHOW_CONTEXT_SITE
        ? manager::get_site_banner_format()
        : manager::get_course_banner_format();
    $bannerformat = manager::normalise_banner_format($bannerformat);
    $formatclass = 'local-course-banner-builder-slideshow-admin-preview--format-' .
        preg_replace('/[^a-z0-9_-]+/i', '', $bannerformat);
    $contextlabel = get_string($context === manager::SLIDESHOW_CONTEXT_SITE ? 'slideshowsitebanner' : 'slideshowcoursebanner',
        'local_course_banner_builder');
    $labelcolors = $config['labelcolors'] ?? manager::get_default_slideshow_label_colors();
    $titlefontsize = max(25, min(100, (int)($config['titlefontsize'] ?? manager::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT)));
    $bodyfontsize = max(25, min(100, (int)($config['bodyfontsize'] ?? manager::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT)));
        $bodylineheight = max(80, min(200, (float)($config['bodylineheight'] ?? 135)));
    $actionsize = max(25, min(100, (int)($config['actionsize'] ?? manager::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT)));
    $actionwidth = max(25, min(100, (int)($config['actionwidth'] ?? manager::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT)));
    $actionheight = max(25, min(100, (int)($config['actionheight'] ?? manager::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT)));
    $labelsize = max(25, min(100, (int)($config['labelsize'] ?? manager::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT)));
    $labeltextsize = max(25, min(160, (int)($config['labeltextsize'] ?? 100)));
    $actioncorners = (string)($config['actioncorners'] ?? manager::SLIDESHOW_DEFAULT_ACTION_CORNERS);
    $actioncorners = $actioncorners === manager::SLIDESHOW_CORNER_SQUARE
        ? manager::SLIDESHOW_CORNER_SQUARE
        : manager::SLIDESHOW_CORNER_ROUNDED;
    $labelcorners = (string)($config['labelcorners'] ?? manager::SLIDESHOW_DEFAULT_LABEL_CORNERS);
    $labelcorners = $labelcorners === manager::SLIDESHOW_CORNER_SQUARE
        ? manager::SLIDESHOW_CORNER_SQUARE
        : manager::SLIDESHOW_CORNER_ROUNDED;
    $labelorientation = (string)($config['labelorientation'] ?? manager::SLIDESHOW_DEFAULT_LABEL_ORIENTATION);
    $labelorientation = $labelorientation === manager::SLIDESHOW_LABEL_ORIENTATION_COLUMN
        ? manager::SLIDESHOW_LABEL_ORIENTATION_COLUMN
        : manager::SLIDESHOW_LABEL_ORIENTATION_ROW;
    $titlecolor = (string)($config['titlecolor'] ?? manager::SLIDESHOW_DEFAULT_TITLE_COLOR);
    $bodycolor = (string)($config['bodycolor'] ?? manager::SLIDESHOW_DEFAULT_BODY_COLOR);
    $titlefontfamily = (string)($config['titlefontfamily'] ?? manager::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY);
    $bodyfontfamily = (string)($config['bodyfontfamily'] ?? manager::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY);
    $styledefaults = manager::get_default_slideshow_style_values();
    $stylenumber = static function(string $field) use ($config, $styledefaults): int {
        return (int)($config[$field] ?? $styledefaults[$field]);
    };
    $stylestring = static function(string $field) use ($config, $styledefaults): string {
        return (string)($config[$field] ?? $styledefaults[$field]);
    };
    $stylergb = static function(string $hex): string {
        $hex = ltrim($hex, '#');
        if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return '0, 0, 0';
        }
        return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
    };
    $titlealign = (string)($config['titlealign'] ?? manager::SLIDESHOW_DEFAULT_TITLE_ALIGN);
    $bodyalign = (string)($config['bodyalign'] ?? manager::SLIDESHOW_DEFAULT_BODY_ALIGN);
    $labelalign = (string)($config['labelalign'] ?? manager::SLIDESHOW_DEFAULT_LABEL_ALIGN);
    $titlealign = in_array($titlealign, ['left', 'center', 'right'], true) ? $titlealign : 'center';
    $bodyalign = in_array($bodyalign, ['left', 'center', 'right'], true) ? $bodyalign : 'center';
    $labelalign = in_array($labelalign, ['left', 'center', 'right'], true) ? $labelalign : 'center';
    $labeltranslatex = '-50%';
    $labelitemsalign = [
        'left' => 'flex-start',
        'center' => 'center',
        'right' => 'flex-end',
    ][$labelalign];
    $titlestyles = [
        'bold' => !empty($config['titlebold']),
        'italic' => !empty($config['titleitalic']),
        'underline' => !empty($config['titleunderline']),
        'strike' => !empty($config['titlestrike']),
        'allcaps' => !empty($config['titleallcaps']),
    ];
    $bodystyles = [
        'bold' => !empty($config['bodybold']),
        'italic' => !empty($config['bodyitalic']),
        'underline' => !empty($config['bodyunderline']),
        'strike' => !empty($config['bodystrike']),
        'allcaps' => !empty($config['bodyallcaps']),
    ];
    $actionstyles = [
        'bold' => !empty($config['actionbold']),
        'italic' => !empty($config['actionitalic']),
        'underline' => !empty($config['actionunderline']),
        'strike' => !empty($config['actionstrike']),
        'allcaps' => !empty($config['actionallcaps']),
    ];
    $labelstyles = [
        'bold' => !empty($config['labelbold']),
        'italic' => !empty($config['labelitalic']),
        'underline' => !empty($config['labelunderline']),
        'strike' => !empty($config['labelstrike']),
        'allcaps' => array_key_exists('labelallcaps', $config) ? !empty($config['labelallcaps']) : true,
    ];
    $titlex = (float)($config['titlex'] ?? manager::SLIDESHOW_DEFAULT_TITLE_X);
    $titley = (float)($config['titley'] ?? manager::SLIDESHOW_DEFAULT_TITLE_Y);
    $bodyx = (float)($config['bodyx'] ?? manager::SLIDESHOW_DEFAULT_BODY_X);
    $bodyy = (float)($config['bodyy'] ?? manager::SLIDESHOW_DEFAULT_BODY_Y);
    $actionx = (float)($config['actionx'] ?? manager::SLIDESHOW_DEFAULT_ACTION_X);
    $actiony = (float)($config['actiony'] ?? manager::SLIDESHOW_DEFAULT_ACTION_Y);
    $labelx = (float)($config['labelx'] ?? manager::SLIDESHOW_DEFAULT_LABEL_X);
    $labely = (float)($config['labely'] ?? manager::SLIDESHOW_DEFAULT_LABEL_Y);
    if ($bannerformat === manager::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT &&
            abs($labely - manager::SLIDESHOW_DEFAULT_LABEL_Y) < 0.001) {
        $labely = 18.0;
    }
    $previewstyle = '--local-course-banner-builder-slideshow-overlay-rgb: ' .
        s((string)($config['overlayrgb'] ?? '0, 0, 0')) .
        '; --local-course-banner-builder-slideshow-overlay-opacity: ' . number_format($opacity / 100, 2, '.', '') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-size: ' .
        local_course_banner_builder_slideshow_font_clamp('title', $titlefontsize, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-size: ' .
        local_course_banner_builder_slideshow_font_clamp('body', $bodyfontsize, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-line-height: ' . $bodylineheight . '%;';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-font-size: ' .
        local_course_banner_builder_slideshow_font_clamp('action', $actionsize, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-width: ' .
        local_course_banner_builder_slideshow_font_clamp('actionwidth', $actionwidth, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-height: ' .
        local_course_banner_builder_slideshow_font_clamp('actionheight', $actionheight, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-font-size: ' .
        local_course_banner_builder_slideshow_font_clamp('label', $labelsize, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-text-scale: ' .
        number_format($labeltextsize / 100, 2, '.', '') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-orientation: ' . s($labelorientation) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-radius: ' .
        ($labelcorners === manager::SLIDESHOW_CORNER_SQUARE ? '0.28rem' : '999px') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-radius: ' .
        ($actioncorners === manager::SLIDESHOW_CORNER_SQUARE ? '0.28rem' : '999px') . ';';
    foreach (['action', 'label'] as $target) {
        $shadowdistance = $stylenumber($target . 'shadowdistance');
        $shadowdirection = deg2rad($stylenumber($target . 'shadowdirection'));
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-opacity: ' .
            number_format($stylenumber($target . 'opacity') / 100, 2, '.', '') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-border-width: ' .
            $stylenumber($target . 'borderwidth') . 'px;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-radius: ' .
            $stylenumber($target . 'radius') . 'px;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-padding: ' .
            $stylenumber($target . 'padding') . 'px;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-opacity: ' .
            number_format($stylenumber($target . 'shadowopacity') / 100, 2, '.', '') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-blur: ' .
            $stylenumber($target . 'shadowblur') . 'px;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-x: ' .
            number_format(cos($shadowdirection) * $shadowdistance, 2, '.', '') . 'px;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-y: ' .
            number_format(sin($shadowdirection) * $shadowdistance, 2, '.', '') . 'px;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-background: ' .
            s($stylestring($target . 'backgroundcolor')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-border-color: ' .
            s($stylestring($target . 'bordercolor')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-color: ' .
            s($stylestring($target . 'shadowcolor')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-rgb: ' .
            s($stylergb($stylestring($target . 'shadowcolor'))) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-font-family: ' .
            ($stylestring($target . 'fontfamily') !== '' ? s($stylestring($target . 'fontfamily')) : 'inherit') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-text-color: ' .
            s($stylestring($target . 'textcolor')) . ';';
    }
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-color: ' . s($titlecolor) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-color: ' . s($bodycolor) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-family: ' .
        ($titlefontfamily !== '' ? s($titlefontfamily) : 'inherit') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-family: ' .
        ($bodyfontfamily !== '' ? s($bodyfontfamily) : 'inherit') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-text-align: ' . s($titlealign) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-text-align: ' . s($bodyalign) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-translate-x: ' . s($labeltranslatex) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-items-align: ' . s($labelitemsalign) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-weight: ' .
        ($titlestyles['bold'] ? '800' : '400') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-style: ' .
        ($titlestyles['italic'] ? 'italic' : 'normal') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-text-decoration: ' .
        (($titlestyles['underline'] || $titlestyles['strike']) ?
            trim(($titlestyles['underline'] ? 'underline ' : '') . ($titlestyles['strike'] ? 'line-through' : '')) : 'none') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-text-transform: ' .
        ($titlestyles['allcaps'] ? 'uppercase' : 'none') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-weight: ' .
        ($bodystyles['bold'] ? '700' : '400') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-style: ' .
        ($bodystyles['italic'] ? 'italic' : 'normal') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-text-decoration: ' .
        (($bodystyles['underline'] || $bodystyles['strike']) ?
            trim(($bodystyles['underline'] ? 'underline ' : '') . ($bodystyles['strike'] ? 'line-through' : '')) : 'none') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-text-transform: ' .
        ($bodystyles['allcaps'] ? 'uppercase' : 'none') . ';';
    foreach (['action' => $actionstyles, 'label' => $labelstyles] as $target => $styles) {
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-font-weight: ' .
            ($styles['bold'] ? '700' : '400') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-font-style: ' .
            ($styles['italic'] ? 'italic' : 'normal') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-text-decoration: ' .
            (($styles['underline'] || $styles['strike']) ?
                trim(($styles['underline'] ? 'underline ' : '') . ($styles['strike'] ? 'line-through' : '')) : 'none') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-text-transform: ' .
            ($styles['allcaps'] ? 'uppercase' : 'none') . ';';
    }
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-x: ' . number_format($titlex, 3, '.', '') . '%;';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-y: ' . number_format($titley, 3, '.', '') . '%;';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-x: ' . number_format($bodyx, 3, '.', '') . '%;';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-y: ' . number_format($bodyy, 3, '.', '') . '%;';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-x: ' . number_format($actionx, 3, '.', '') . '%;';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-y: ' . number_format($actiony, 3, '.', '') . '%;';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-x: ' . number_format($labelx, 3, '.', '') . '%;';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-y: ' . number_format($labely, 3, '.', '') . '%;';
    foreach ($labelcolors as $type => $colors) {
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-bg: ' .
            s((string)($colors['background'] ?? '#000000')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-color: ' .
            s((string)($colors['text'] ?? '#FFFFFF')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-border: ' .
            s((string)($colors['border'] ?? '#FFFFFF')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-shadow: ' .
            s((string)($colors['shadow'] ?? '#000000')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-shadow-rgb: ' .
            s($stylergb((string)($colors['shadow'] ?? '#000000'))) . ';';
    }
    $previewtoolbarbutton = static function(string $iconclass, string $label, string $action, array $extra = []): string {
        return html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa ' . $iconclass, 'aria-hidden' => 'true']) .
                html_writer::span($label, 'sr-only'),
            [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-visibility-toggle',
                'data-action' => $action,
                'data-local-slideshow-action-help' => '1',
                'data-content' => '<div class="no-overflow"><p>' . s($label) . '</p></div>',
                'aria-label' => $label,
            ] + $extra
        );
    };
    $resizehandles = static function(): string {
        $html = '';
        foreach (['top', 'right', 'bottom', 'left'] as $edge) {
            $html .= html_writer::span('', 'local-course-banner-builder-preview-resize-handle local-course-banner-builder-preview-resize-handle--' . $edge, [
                'data-slideshow-preview-resize-handle' => $edge,
                'aria-hidden' => 'true',
            ]);
        }
        return $html;
    };
    $cornertoggle = static function(string $target, string $label): string {
        return html_writer::tag('button', html_writer::tag('i', '', [
            'class' => 'fa fa-square',
            'aria-hidden' => 'true',
        ]), [
            'type' => 'button',
            'class' => 'btn btn-link p-0 icon-no-margin local-course-banner-builder-preview-aspect-lock local-course-banner-builder-slideshow-corner-toggle',
            'data-action' => 'local-course-banner-builder-toggle-slideshow-corners',
            'data-slideshow-corner-target' => $target,
            'aria-label' => $label,
            'title' => $label,
        ]);
    };
    $textstylebuttons = static function(string $target, array $values): string {
        if ($target === 'title') {
            $defaults = [
                'bold' => manager::SLIDESHOW_DEFAULT_TITLE_BOLD,
                'italic' => manager::SLIDESHOW_DEFAULT_TITLE_ITALIC,
                'underline' => manager::SLIDESHOW_DEFAULT_TITLE_UNDERLINE,
                'strike' => manager::SLIDESHOW_DEFAULT_TITLE_STRIKE,
                'allcaps' => false,
            ];
        } else if ($target === 'body') {
            $defaults = [
                'bold' => manager::SLIDESHOW_DEFAULT_BODY_BOLD,
                'italic' => manager::SLIDESHOW_DEFAULT_BODY_ITALIC,
                'underline' => manager::SLIDESHOW_DEFAULT_BODY_UNDERLINE,
                'strike' => manager::SLIDESHOW_DEFAULT_BODY_STRIKE,
                'allcaps' => false,
            ];
        } else if ($target === 'label') {
            $defaults = [
                'bold' => true,
                'italic' => false,
                'underline' => false,
                'strike' => false,
                'allcaps' => true,
            ];
        } else {
            $defaults = [
                'bold' => true,
                'italic' => false,
                'underline' => false,
                'strike' => false,
                'allcaps' => false,
            ];
        }
        $icons = [
            'bold' => 'fa-bold',
            'italic' => 'fa-italic',
            'underline' => 'fa-underline',
            'strike' => 'fa-strikethrough',
            'allcaps' => 'fa-font',
        ];
        $labels = [
            'bold' => get_string('slideshowtextbold', 'local_course_banner_builder'),
            'italic' => get_string('slideshowtextitalic', 'local_course_banner_builder'),
            'underline' => get_string('slideshowtextunderline', 'local_course_banner_builder'),
            'strike' => get_string('slideshowtextstrike', 'local_course_banner_builder'),
            'allcaps' => get_string('slideshowtextallcaps', 'local_course_banner_builder'),
        ];
        $html = html_writer::start_div('btn-group local-course-banner-builder-slideshow-text-style-buttons', [
            'role' => 'group',
            'data-slideshow-text-style-buttons' => $target,
        ]);
        foreach ($icons as $style => $icon) {
            $active = !empty($values[$style]);
            $html .= html_writer::tag('button', html_writer::tag('i', '', [
                'class' => 'fa ' . $icon,
                'aria-hidden' => 'true',
            ]) . html_writer::span($labels[$style], 'sr-only'), [
                'type' => 'button',
                'class' => 'btn btn-sm ' . ($active ? 'btn-primary active' : 'btn-outline-secondary'),
                'data-action' => 'local-course-banner-builder-toggle-slideshow-text-style',
                'data-slideshow-text-style-target' => $target,
                'data-slideshow-text-style' => $style,
                'aria-label' => $labels[$style],
                'aria-pressed' => $active ? 'true' : 'false',
            ]);
        }
        $html .= html_writer::end_div();
        foreach ($icons as $style => $unused) {
            $html .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $target . $style,
                'value' => !empty($values[$style]) ? 1 : 0,
                'data-slideshow-text-style-input' => $target . $style,
                'data-slideshow-text-style-target' => $target,
                'data-slideshow-text-style' => $style,
                'data-default-value' => !empty($defaults[$style]) ? 1 : 0,
            ]);
        }
        return $html;
    };
    $textstyletoolbar = static function(string $target, array $values): string {
        $icons = [
            'bold' => 'fa-bold',
            'italic' => 'fa-italic',
            'underline' => 'fa-underline',
            'strike' => 'fa-strikethrough',
            'allcaps' => 'fa-font',
        ];
        $labels = [
            'bold' => get_string('slideshowtextbold', 'local_course_banner_builder'),
            'italic' => get_string('slideshowtextitalic', 'local_course_banner_builder'),
            'underline' => get_string('slideshowtextunderline', 'local_course_banner_builder'),
            'strike' => get_string('slideshowtextstrike', 'local_course_banner_builder'),
            'allcaps' => get_string('slideshowtextallcaps', 'local_course_banner_builder'),
        ];
        $html = html_writer::start_div('btn-group local-course-banner-builder-slideshow-text-style-buttons', [
            'role' => 'group',
            'data-slideshow-text-style-buttons' => $target,
        ]);
        foreach ($icons as $style => $icon) {
            $active = !empty($values[$style]);
            $html .= html_writer::tag('button', html_writer::tag('i', '', [
                'class' => 'fa ' . $icon,
                'aria-hidden' => 'true',
            ]) . html_writer::span($labels[$style], 'sr-only'), [
                'type' => 'button',
                'class' => 'btn btn-sm ' . ($active ? 'btn-primary active' : 'btn-outline-secondary'),
                'data-action' => 'local-course-banner-builder-toggle-slideshow-text-style',
                'data-slideshow-text-style-target' => $target,
                'data-slideshow-text-style' => $style,
                'aria-label' => $labels[$style],
                'aria-pressed' => $active ? 'true' : 'false',
            ]);
        }
        return $html . html_writer::end_div();
    };
    $sidepanel = static function(string $key, string $content): string {
        return html_writer::div(
            $content,
            'local-course-banner-builder-preview-opacity-panel ' .
                'local-course-banner-builder-slideshow-side-panel is-collapsed',
            [
                'data-slideshow-side-panel' => $key,
                'data-slideshow-text-settings' => '1',
                'hidden' => 'hidden',
            ]
        );
    };
    $sidepanelbutton = static function(string $key, string $icon, string $label): string {
        return html_writer::tag('button', html_writer::tag('i', '', [
            'class' => 'fa ' . $icon . ' me-2',
            'aria-hidden' => 'true',
        ]) . html_writer::span($label), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-slideshow-side-panel',
            'data-slideshow-side-panel-target' => $key,
            'aria-expanded' => 'false',
        ]);
    };
    $slidercontrol = static function(
        string $name,
        string $label,
        float $value,
        int $min,
        int $max,
        string $cssvar,
        string $unit = '',
        ?string $proxyfor = null,
        ?float $defaultvalue = null,
        float $step = 1
    ): string {
        $inputid = $name . '-input-' . uniqid();
        $outputid = $name . '-output-' . uniqid();
        $numberid = $name . '-number-' . uniqid();
        $displayunit = $unit !== '' || strpos($cssvar, '-opacity') === false ? $unit : '%';
        $attrs = [
            'type' => 'range',
            'id' => $inputid,
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'value' => $value,
            'class' => 'custom-range local-course-banner-builder-range',
            'data-slideshow-design-input' => '1',
            'data-slideshow-design-var' => $cssvar,
            'data-slideshow-design-unit' => $displayunit,
            'data-slideshow-design-output' => '#' . $outputid,
            'data-slideshow-design-number' => '#' . $numberid,
            'data-default-value' => (string)($defaultvalue ?? $value),
        ];
        if ($proxyfor) {
            $attrs['data-slideshow-side-proxy-for'] = $proxyfor;
            unset($attrs['data-slideshow-design-var']);
        } else {
            $attrs['name'] = $name;
        }
        return html_writer::div($label, 'local-course-banner-builder-slideshow-side-title') .
            html_writer::div(
                html_writer::empty_tag('input', $attrs) .
                html_writer::tag('output', $value . $displayunit, [
                    'id' => $outputid,
                    'class' => 'text-muted local-course-banner-builder-slideshow-opacity-output',
                    'for' => $inputid,
                ]) .
                html_writer::empty_tag('input', [
                    'type' => 'number',
                    'id' => $numberid,
                    'min' => $min,
                    'max' => $max,
                    'step' => $step,
                    'value' => $value,
                    'class' => 'form-control form-control-sm local-course-banner-builder-slideshow-side-number',
                    'data-slideshow-design-number-for' => '#' . $inputid,
                    'aria-label' => $label,
                ]),
                'local-course-banner-builder-slideshow-side-slider'
            );
    };
    $colorcontrol = static function(string $name, string $label, string $value, string $cssvar, ?string $default = null): string {
        $id = $name . '-' . uniqid();
        $hexid = $name . '-hex-' . uniqid();
        return html_writer::tag('label', $label, ['for' => $id, 'class' => 'local-course-banner-builder-slideshow-side-title']) .
            html_writer::empty_tag('input', [
                'type' => 'color',
                'id' => $id,
                'name' => $name,
                'value' => $value,
                'class' => 'form-control local-course-banner-builder-slideshow-color-input',
                'data-slideshow-color-input' => '1',
                'data-hex-target' => '#' . $hexid,
                'data-slideshow-design-input' => '1',
                'data-slideshow-design-var' => $cssvar,
                'data-default-value' => $default ?? $value,
            ]) .
            html_writer::empty_tag('input', [
                'type' => 'text',
                'id' => $hexid,
                'value' => $value,
                'class' => 'form-control local-course-banner-builder-slideshow-hex-input',
                'aria-label' => $label,
            ]);
    };
    $proxycolorcontrol = static function(string $target, string $label, string $value): string {
        $id = $target . '-proxy-' . uniqid();
        $hexid = $target . '-proxy-hex-' . uniqid();
        return html_writer::tag('label', $label, ['for' => $id, 'class' => 'local-course-banner-builder-slideshow-side-title']) .
            html_writer::empty_tag('input', [
                'type' => 'color',
                'id' => $id,
                'value' => $value,
                'class' => 'form-control local-course-banner-builder-slideshow-color-input',
                'data-slideshow-color-input' => '1',
                'data-hex-target' => '#' . $hexid,
                'data-slideshow-side-proxy-for' => $target,
            ]) .
            html_writer::empty_tag('input', [
                'type' => 'text',
                'id' => $hexid,
                'value' => $value,
                'class' => 'form-control local-course-banner-builder-slideshow-hex-input',
                'aria-label' => $label,
            ]);
    };
    $fontcontrol = static function(
        string $name,
        string $label,
        string $value,
        string $cssvar,
        string $default = ''
    ): string {
        return html_writer::div($label, 'local-course-banner-builder-slideshow-side-title') .
            local_course_banner_builder_render_slideshow_font_select($name, $value, $cssvar, $default);
    };
    $proxyfontcontrol = static function(string $target, string $label, string $value): string {
        $html = html_writer::div($label, 'local-course-banner-builder-slideshow-side-title');
        $html .= html_writer::start_tag('select', [
            'class' => 'form-control local-course-banner-builder-font-family-select',
            'style' => 'font-weight: 700;',
            'data-slideshow-side-proxy-for' => $target,
            'data-slideshow-font-family' => '1',
        ]);
        foreach (manager::get_slideshow_font_family_options() as $optionvalue => $optionlabel) {
            $attrs = ['value' => $optionvalue, 'data-font-value' => $optionvalue];
            $attrs['style'] = ($optionvalue !== '' ? 'font-family: ' . s($optionvalue) . '; ' : '') .
                'font-weight: 700;';
            if ($optionvalue === $value) {
                $attrs['selected'] = 'selected';
            }
            $html .= html_writer::tag('option', s($optionlabel), $attrs);
        }
        return $html . html_writer::end_tag('select');
    };
    $cornerswitch = static function(string $target, string $inputname, string $current): string {
        $html = html_writer::div(get_string('slideshowtogglecorners', 'local_course_banner_builder'),
            'local-course-banner-builder-slideshow-side-title');
        $html .= html_writer::start_div('btn-group local-course-banner-builder-slideshow-corner-buttons', ['role' => 'group']);
        foreach ([manager::SLIDESHOW_CORNER_ROUNDED => 'slideshowcornersrounded',
            manager::SLIDESHOW_CORNER_SQUARE => 'slideshowcornerssquare'] as $value => $stringkey) {
            $html .= html_writer::tag('button', get_string($stringkey, 'local_course_banner_builder'), [
                'type' => 'button',
                'class' => 'btn btn-sm ' . ($current === $value ? 'btn-primary active' : 'btn-outline-secondary'),
                'data-slideshow-corner-option' => $target,
                'data-slideshow-corner-value' => $value,
            ]);
        }
        $html .= html_writer::end_div();
        $html .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'value' => $current,
            'data-slideshow-corner-input' => $target,
            'data-default-value' => $target === 'label'
                ? manager::SLIDESHOW_DEFAULT_LABEL_CORNERS
                : manager::SLIDESHOW_DEFAULT_ACTION_CORNERS,
        ]);
        return $html;
    };
    $orientationbuttons = static function(string $current): string {
        $html = html_writer::div(get_string('slideshowlabelorientation', 'local_course_banner_builder'),
            'local-course-banner-builder-slideshow-side-title');
        $html .= html_writer::start_div('btn-group local-course-banner-builder-slideshow-label-orientation-buttons', [
            'role' => 'group',
            'data-slideshow-label-orientation-buttons' => '1',
        ]);
        foreach ([manager::SLIDESHOW_LABEL_ORIENTATION_ROW => 'slideshowlabelorientationrow',
            manager::SLIDESHOW_LABEL_ORIENTATION_COLUMN => 'slideshowlabelorientationcolumn'] as $value => $stringkey) {
            $html .= html_writer::tag('button', get_string($stringkey, 'local_course_banner_builder'), [
                'type' => 'button',
                'class' => 'btn btn-sm ' . ($current === $value ? 'btn-primary active' : 'btn-outline-secondary'),
                'data-slideshow-label-orientation-option' => $value,
            ]);
        }
        return $html . html_writer::end_div();
    };
    $alignmentbuttons = static function(string $current, string $target = 'label'): string {
        $html = html_writer::div(get_string('slideshowlabelalignment', 'local_course_banner_builder'),
            'local-course-banner-builder-slideshow-side-title');
        $html .= html_writer::start_div('btn-group local-course-banner-builder-slideshow-label-alignment-buttons', [
            'role' => 'group',
        ]);
        foreach ([
            manager::SLIDESHOW_ALIGN_LEFT => ['fa-align-left', 'slideshowalignleft'],
            manager::SLIDESHOW_ALIGN_CENTER => ['fa-align-center', 'slideshowaligncenter'],
            manager::SLIDESHOW_ALIGN_RIGHT => ['fa-align-right', 'slideshowalignright'],
        ] as $value => $definition) {
            $html .= html_writer::tag('button', html_writer::tag('i', '', [
                'class' => 'fa ' . $definition[0],
                'aria-hidden' => 'true',
            ]) . html_writer::span(get_string($definition[1], 'local_course_banner_builder'), 'sr-only'), [
                'type' => 'button',
                'class' => 'btn btn-sm ' . ($current === $value ? 'btn-primary active' : 'btn-outline-secondary'),
                'data-action' => 'local-course-banner-builder-set-selected-slideshow-alignment',
                'data-slideshow-align' => $value,
                'data-slideshow-align-target' => $target,
                'aria-label' => get_string($definition[1], 'local_course_banner_builder'),
            ]);
        }
        return $html . html_writer::end_div();
    };

    $previewlabelicon = $context === manager::SLIDESHOW_CONTEXT_SITE ? 'fa-bullhorn' : 'fa-comments';
    $previewlabelkey = $context === manager::SLIDESHOW_CONTEXT_SITE
        ? 'slideshow:type:siteannouncements'
        : 'slideshow:type:courseforum';
    $previewlabelclass = $context === manager::SLIDESHOW_CONTEXT_SITE ? 'siteannouncements' : 'forums';
    $previewsecondarylabel = $context === manager::SLIDESHOW_CONTEXT_SITE ? 'CAT2' : 'COURSE101';

    $previewcontent = html_writer::div('', 'local-course-banner-builder-slideshow-admin-preview-backdrop') .
        html_writer::div('', 'local-course-banner-builder-slideshow-admin-preview-overlay') .
        html_writer::div(
            html_writer::div(
                html_writer::tag('button', html_writer::tag('i', '', [
                    'class' => 'fa fa-grip-lines',
                    'aria-hidden' => 'true',
                ]), [
                    'type' => 'button',
                    'class' => 'btn btn-link p-0 icon-no-margin local-course-banner-builder-preview-aspect-lock local-course-banner-builder-slideshow-label-orientation-toggle',
                    'data-action' => 'local-course-banner-builder-toggle-slideshow-label-orientation',
                    'aria-label' => get_string('slideshowtogglelabelorientation', 'local_course_banner_builder'),
                    'title' => get_string('slideshowtogglelabelorientation', 'local_course_banner_builder'),
                ]) .
                $cornertoggle('label', get_string('slideshowtogglecorners', 'local_course_banner_builder')) .
                html_writer::span(
                    html_writer::tag('i', '', [
                        'class' => 'fa ' . $previewlabelicon . ' local-course-banner-builder-slideshow-label-icon',
                        'aria-hidden' => 'true',
                    ]) .
                    html_writer::span(get_string($previewlabelkey, 'local_course_banner_builder')),
                    'local-course-banner-builder-slideshow-label local-course-banner-builder-slideshow-label--' .
                        $previewlabelclass
                ) .
                html_writer::span(
                    html_writer::span($previewsecondarylabel),
                    'local-course-banner-builder-slideshow-label ' .
                        'local-course-banner-builder-slideshow-label--course-shortname'
                ) .
                    $resizehandles(),
                'local-course-banner-builder-slideshow-labels',
                ['data-slideshow-preview-draggable' => 'label']
            ) .
            html_writer::div(
                html_writer::tag('h3', get_string('slideshowpreviewtitle', 'local_course_banner_builder'), ['class' => 'local-course-banner-builder-slideshow-title']) .
                    $resizehandles(),
                'local-course-banner-builder-slideshow-title-block',
                ['data-slideshow-preview-draggable' => 'title']
            ) .
            html_writer::div(
                html_writer::tag('p', get_string('slideshowpreviewmeta', 'local_course_banner_builder'), [
                    'class' => 'local-course-banner-builder-slideshow-meta',
                ]) .
                html_writer::tag('p', get_string('slideshowpreviewbody', 'local_course_banner_builder'), [
                    'class' => 'local-course-banner-builder-slideshow-body',
                ]) .
                    $resizehandles(),
                'local-course-banner-builder-slideshow-body-block',
                ['data-slideshow-preview-draggable' => 'body']
            ) .
            html_writer::div(
                html_writer::tag('button', get_string('slideshowview', 'local_course_banner_builder'), [
                    'type' => 'button',
                    'class' => 'btn local-course-banner-builder-slideshow-action',
                    'data-slideshow-preview-no-action' => '1',
                ]) .
                    $cornertoggle('action', get_string('slideshowtogglecorners', 'local_course_banner_builder')) .
                    $resizehandles(),
                'local-course-banner-builder-slideshow-action-wrap',
                ['data-slideshow-preview-draggable' => 'action']
            ),
            'local-course-banner-builder-slideshow-admin-preview-content local-course-banner-builder-slideshow-slide is-active'
        );

    $modal = html_writer::start_div('modal fade local-course-banner-builder-slideshow-preview-modal', [
        'id' => $modalid,
        'tabindex' => '-1',
        'role' => 'dialog',
        'aria-hidden' => 'true',
        'data-backdrop' => 'static',
        'data-bs-backdrop' => 'static',
    ]);
    $slideshowmodaldialogclass = 'modal-dialog modal-xl local-course-banner-builder-title-modal-dialog ' .
        'local-course-banner-builder-slideshow-modal-dialog';
    $modal .= html_writer::start_div($slideshowmodaldialogclass, ['role' => 'document']);
    $modal .= html_writer::start_div('modal-content');
    $modal .= html_writer::start_div('modal-header d-flex align-items-center');
    $modal .= html_writer::tag('h5',
        get_string('slideshowlargepreview', 'local_course_banner_builder') . ' - ' . $contextlabel,
        ['class' => 'modal-title flex-grow-1']
    );
    $modal .= html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
        'type' => 'button',
        'class' => 'close ml-auto ms-auto',
        'data-dismiss' => 'modal',
        'data-bs-dismiss' => 'modal',
        'aria-label' => get_string('closebuttontitle'),
    ]);
    $modal .= html_writer::end_div();
    $modalpreview = html_writer::div($previewcontent,
        'local-course-banner-builder-slideshow-admin-preview local-course-banner-builder-slideshow-admin-preview--large ' .
            $formatclass,
        [
            'data-slideshow-overlay-preview' => '1',
            'data-slideshow-preview-editor' => '1',
            'data-banner-format' => $bannerformat,
            'style' => $previewstyle,
        ]
    );
    $modalpreview .= html_writer::div(
        $previewtoolbarbutton(
            'fa-undo',
            get_string('undopreviewchange', 'local_course_banner_builder'),
            'local-course-banner-builder-slideshow-preview-undo',
            ['disabled' => 'disabled']
        ) .
        $previewtoolbarbutton(
            'fa-magnet',
            get_string('togglepreviewsnap', 'local_course_banner_builder'),
            'local-course-banner-builder-toggle-slideshow-preview-snap',
            ['aria-pressed' => 'true']
        ) .
        $previewtoolbarbutton(
            'fa-crosshairs',
            get_string('recenterpreviewelement', 'local_course_banner_builder'),
            'local-course-banner-builder-slideshow-preview-recenter-element',
            ['disabled' => 'disabled']
        ) .
        $previewtoolbarbutton(
            'fa-bullseye',
            get_string('recenterallpreviewelements', 'local_course_banner_builder'),
            'local-course-banner-builder-slideshow-preview-recenter-all'
        ) .
        $previewtoolbarbutton(
            'fa-bold',
            get_string('slideshowtextbold', 'local_course_banner_builder'),
            'local-course-banner-builder-toggle-selected-slideshow-text-style',
            ['data-slideshow-text-style' => 'bold']
        ) .
        $previewtoolbarbutton(
            'fa-italic',
            get_string('slideshowtextitalic', 'local_course_banner_builder'),
            'local-course-banner-builder-toggle-selected-slideshow-text-style',
            ['data-slideshow-text-style' => 'italic']
        ) .
        $previewtoolbarbutton(
            'fa-underline',
            get_string('slideshowtextunderline', 'local_course_banner_builder'),
            'local-course-banner-builder-toggle-selected-slideshow-text-style',
            ['data-slideshow-text-style' => 'underline']
        ) .
        $previewtoolbarbutton(
            'fa-strikethrough',
            get_string('slideshowtextstrike', 'local_course_banner_builder'),
            'local-course-banner-builder-toggle-selected-slideshow-text-style',
            ['data-slideshow-text-style' => 'strike']
        ) .
        $previewtoolbarbutton(
            'fa-font',
            get_string('slideshowtextallcaps', 'local_course_banner_builder'),
            'local-course-banner-builder-toggle-selected-slideshow-text-style',
            ['data-slideshow-text-style' => 'allcaps']
        ) .
        $previewtoolbarbutton(
            'fa-align-left',
            get_string('slideshowalignleft', 'local_course_banner_builder'),
            'local-course-banner-builder-set-selected-slideshow-alignment',
            ['data-slideshow-align' => manager::SLIDESHOW_ALIGN_LEFT]
        ) .
        $previewtoolbarbutton(
            'fa-align-center',
            get_string('slideshowaligncenter', 'local_course_banner_builder'),
            'local-course-banner-builder-set-selected-slideshow-alignment',
            ['data-slideshow-align' => manager::SLIDESHOW_ALIGN_CENTER]
        ) .
        $previewtoolbarbutton(
            'fa-align-right',
            get_string('slideshowalignright', 'local_course_banner_builder'),
            'local-course-banner-builder-set-selected-slideshow-alignment',
            ['data-slideshow-align' => manager::SLIDESHOW_ALIGN_RIGHT]
        ) .
        $previewtoolbarbutton(
            'fa-redo',
            get_string('redopreviewchange', 'local_course_banner_builder'),
            'local-course-banner-builder-slideshow-preview-redo',
            ['disabled' => 'disabled']
        ),
        'local-course-banner-builder-source-preview-visibility-toggle-row local-course-banner-builder-slideshow-preview-toolbar'
    );
    $slideshowactions = html_writer::div(
        $sidepanel(
            'overlay',
            $proxycolorcontrol(
                'overlaycolor',
                get_string('slideshowoverlaycolor', 'local_course_banner_builder'),
                $color
            ) .
            $slidercontrol(
                'overlayopacityproxy',
                get_string('slideshowoverlayopacity', 'local_course_banner_builder'),
                $opacity,
                0,
                85,
                '--local-course-banner-builder-slideshow-overlay-opacity',
                '%',
                'overlayopacity'
            )
        ) .
        $sidepanelbutton('overlay', 'fa-adjust', get_string('slideshowoverlaysettings', 'local_course_banner_builder')) .
        $sidepanel(
            'titletext',
            $slidercontrol('titlefontsizeproxy', get_string('slideshowtitletext', 'local_course_banner_builder'),
                $titlefontsize, 25, 100, '--local-course-banner-builder-slideshow-title-font-size', '%', 'titlefontsize') .
            $proxyfontcontrol('titlefontfamily', get_string('slideshowtitlefontfamily', 'local_course_banner_builder'), $titlefontfamily) .
            $proxycolorcontrol('titlecolor', get_string('slideshowtitlecolor', 'local_course_banner_builder'), $titlecolor) .
            html_writer::div(get_string('slideshowtextformat', 'local_course_banner_builder'),
                'local-course-banner-builder-slideshow-side-title') .
            $textstyletoolbar('title', $titlestyles)
        ) .
        $sidepanelbutton('titletext', 'fa-heading', get_string('slideshowtitletext', 'local_course_banner_builder')) .
        $sidepanel(
            'bodytext',
            $slidercontrol('bodyfontsizeproxy', get_string('slideshowbodytext', 'local_course_banner_builder'),
                $bodyfontsize, 25, 100, '--local-course-banner-builder-slideshow-body-font-size', '%', 'bodyfontsize') .
            $slidercontrol('bodylineheight', get_string('slideshowbodylineheight', 'local_course_banner_builder'),
                $bodylineheight, 80, 200, '--local-course-banner-builder-slideshow-body-line-height',
                '%', null, (float)$styledefaults['bodylineheight'], 0.1) .
            $proxyfontcontrol('bodyfontfamily', get_string('slideshowbodyfontfamily', 'local_course_banner_builder'), $bodyfontfamily) .
            $proxycolorcontrol('bodycolor', get_string('slideshowbodycolor', 'local_course_banner_builder'), $bodycolor) .
            html_writer::div(get_string('slideshowtextformat', 'local_course_banner_builder'),
                'local-course-banner-builder-slideshow-side-title') .
            $textstyletoolbar('body', $bodystyles)
        ) .
        $sidepanelbutton('bodytext', 'fa-align-left', get_string('slideshowbodytext', 'local_course_banner_builder')) .
        $sidepanel(
            'buttonshape',
            $slidercontrol('actionopacity', get_string('slideshowbuttonopacity', 'local_course_banner_builder'),
                $stylenumber('actionopacity'), 0, 100, '--local-course-banner-builder-slideshow-action-opacity',
                '', null, (int)$styledefaults['actionopacity']) .
            $slidercontrol('actionwidthproxy', get_string('slideshowviewbuttonwidth', 'local_course_banner_builder'),
                $actionwidth, 25, 100, '--local-course-banner-builder-slideshow-action-width', '%', 'actionwidth',
                manager::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT) .
            $slidercontrol('actionborderwidth', get_string('slideshowbuttonborderwidth', 'local_course_banner_builder'),
                $stylenumber('actionborderwidth'), 0, 20, '--local-course-banner-builder-slideshow-action-border-width',
                'px', null, (int)$styledefaults['actionborderwidth']) .
            $cornerswitch('action', 'actioncorners', $actioncorners) .
            $slidercontrol('actionradius', get_string('slideshowbuttoncornerradius', 'local_course_banner_builder'),
                $stylenumber('actionradius'), 0, 120, '--local-course-banner-builder-slideshow-action-radius',
                'px', null, (int)$styledefaults['actionradius']) .
            $slidercontrol('actionpadding', get_string('slideshowbuttonpadding', 'local_course_banner_builder'),
                $stylenumber('actionpadding'), 0, 48, '--local-course-banner-builder-slideshow-action-padding',
                'px', null, (int)$styledefaults['actionpadding']) .
            $colorcontrol('actionbackgroundcolor', get_string('slideshowbuttonbackgroundcolor', 'local_course_banner_builder'),
                $stylestring('actionbackgroundcolor'), '--local-course-banner-builder-slideshow-action-background',
                (string)$styledefaults['actionbackgroundcolor']) .
            $colorcontrol('actionbordercolor', get_string('slideshowbuttonbordercolor', 'local_course_banner_builder'),
                $stylestring('actionbordercolor'), '--local-course-banner-builder-slideshow-action-border-color',
                (string)$styledefaults['actionbordercolor'])
        ) .
        $sidepanelbutton('buttonshape', 'fa-square', get_string('slideshowbuttonshape', 'local_course_banner_builder')) .
        $sidepanel(
            'buttonshadow',
            $slidercontrol('actionshadowopacity', get_string('slideshowbuttonshadowopacity', 'local_course_banner_builder'),
                $stylenumber('actionshadowopacity'), 0, 100,
                '--local-course-banner-builder-slideshow-action-shadow-opacity', '', null,
                (int)$styledefaults['actionshadowopacity']) .
            $slidercontrol('actionshadowblur', get_string('slideshowbuttonshadowblur', 'local_course_banner_builder'),
                $stylenumber('actionshadowblur'), 0, 80,
                '--local-course-banner-builder-slideshow-action-shadow-blur', 'px', null,
                (int)$styledefaults['actionshadowblur']) .
            $slidercontrol('actionshadowdistance', get_string('slideshowbuttonshadowdistance', 'local_course_banner_builder'),
                $stylenumber('actionshadowdistance'), 0, 60,
                '--local-course-banner-builder-slideshow-action-shadow-distance', '', null,
                (int)$styledefaults['actionshadowdistance']) .
            $slidercontrol('actionshadowdirection', get_string('slideshowbuttonshadowdirection', 'local_course_banner_builder'),
                $stylenumber('actionshadowdirection'), 0, 360,
                '--local-course-banner-builder-slideshow-action-shadow-direction', '', null,
                (int)$styledefaults['actionshadowdirection']) .
            $colorcontrol('actionshadowcolor', get_string('slideshowbuttonshadowcolor', 'local_course_banner_builder'),
                $stylestring('actionshadowcolor'), '--local-course-banner-builder-slideshow-action-shadow-color',
                (string)$styledefaults['actionshadowcolor'])
        ) .
        $sidepanelbutton('buttonshadow', 'fa-clone', get_string('slideshowbuttonshadow', 'local_course_banner_builder')) .
        $sidepanel(
            'buttontext',
            $slidercontrol('actionsizeproxy', get_string('slideshowbuttontextsize', 'local_course_banner_builder'),
                $actionsize, 25, 100, '--local-course-banner-builder-slideshow-action-font-size', '%', 'actionsize',
                manager::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT) .
            $fontcontrol('actionfontfamily', get_string('slideshowbuttonfontfamily', 'local_course_banner_builder'),
                $stylestring('actionfontfamily'), '--local-course-banner-builder-slideshow-action-font-family',
                (string)$styledefaults['actionfontfamily']) .
            $colorcontrol('actiontextcolor', get_string('slideshowbuttontextcolor', 'local_course_banner_builder'),
                $stylestring('actiontextcolor'), '--local-course-banner-builder-slideshow-action-text-color',
                (string)$styledefaults['actiontextcolor']) .
            html_writer::div(get_string('slideshowtextformat', 'local_course_banner_builder'),
                'local-course-banner-builder-slideshow-side-title') .
            $textstylebuttons('action', $actionstyles)
        ) .
        $sidepanelbutton('buttontext', 'fa-font', get_string('slideshowbuttontext', 'local_course_banner_builder')) .
        $sidepanel(
            'labelshape',
            $slidercontrol('labelopacity', get_string('slideshowlabelsopacity', 'local_course_banner_builder'),
                $stylenumber('labelopacity'), 0, 100, '--local-course-banner-builder-slideshow-label-opacity',
                '', null, (int)$styledefaults['labelopacity']) .
            $slidercontrol('labelsizeproxy', get_string('slideshowlabelsize', 'local_course_banner_builder'),
                $labelsize, 25, 100, '--local-course-banner-builder-slideshow-label-font-size', '%', 'labelsize',
                manager::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT) .
            $orientationbuttons($labelorientation) .
            $alignmentbuttons($labelalign) .
            $slidercontrol('labelborderwidth', get_string('slideshowlabelsborderwidth', 'local_course_banner_builder'),
                $stylenumber('labelborderwidth'), 0, 20, '--local-course-banner-builder-slideshow-label-border-width',
                'px', null, (int)$styledefaults['labelborderwidth']) .
            $cornerswitch('label', 'labelcorners', $labelcorners) .
            $slidercontrol('labelradius', get_string('slideshowlabelscornerradius', 'local_course_banner_builder'),
                $stylenumber('labelradius'), 0, 120, '--local-course-banner-builder-slideshow-label-radius',
                'px', null, (int)$styledefaults['labelradius']) .
            $slidercontrol('labelpadding', get_string('slideshowlabelspadding', 'local_course_banner_builder'),
                $stylenumber('labelpadding'), 0, 48, '--local-course-banner-builder-slideshow-label-padding',
                'px', null, (int)$styledefaults['labelpadding'])
        ) .
        $sidepanelbutton('labelshape', 'fa-tags', get_string('slideshowlabelsshape', 'local_course_banner_builder')) .
        $sidepanel(
            'labelshadow',
            $slidercontrol('labelshadowopacity', get_string('slideshowlabelsshadowopacity', 'local_course_banner_builder'),
                $stylenumber('labelshadowopacity'), 0, 100,
                '--local-course-banner-builder-slideshow-label-shadow-opacity', '', null,
                (int)$styledefaults['labelshadowopacity']) .
            $slidercontrol('labelshadowblur', get_string('slideshowlabelsshadowblur', 'local_course_banner_builder'),
                $stylenumber('labelshadowblur'), 0, 80,
                '--local-course-banner-builder-slideshow-label-shadow-blur', 'px', null,
                (int)$styledefaults['labelshadowblur']) .
            $slidercontrol('labelshadowdistance', get_string('slideshowlabelsshadowdistance', 'local_course_banner_builder'),
                $stylenumber('labelshadowdistance'), 0, 60,
                '--local-course-banner-builder-slideshow-label-shadow-distance', '', null,
                (int)$styledefaults['labelshadowdistance']) .
            $slidercontrol('labelshadowdirection', get_string('slideshowlabelsshadowdirection', 'local_course_banner_builder'),
                $stylenumber('labelshadowdirection'), 0, 360,
                '--local-course-banner-builder-slideshow-label-shadow-direction', '', null,
                (int)$styledefaults['labelshadowdirection'])
        ) .
        $sidepanelbutton('labelshadow', 'fa-clone', get_string('slideshowlabelsshadow', 'local_course_banner_builder')) .
        $sidepanel(
            'labeltext',
            $slidercontrol('labeltextsize', get_string('slideshowlabeltextsize', 'local_course_banner_builder'),
                $labeltextsize, 25, 160, '--local-course-banner-builder-slideshow-label-text-scale', '%', null, 100) .
            $fontcontrol('labelfontfamily', get_string('slideshowlabelsfontfamily', 'local_course_banner_builder'),
                $stylestring('labelfontfamily'), '--local-course-banner-builder-slideshow-label-font-family',
                (string)$styledefaults['labelfontfamily']) .
            html_writer::div(get_string('slideshowtextformat', 'local_course_banner_builder'),
                'local-course-banner-builder-slideshow-side-title') .
            $textstylebuttons('label', $labelstyles)
        ) .
        $sidepanelbutton('labeltext', 'fa-font', get_string('slideshowlabelstext', 'local_course_banner_builder')),
        'local-course-banner-builder-title-side-actions local-course-banner-builder-slideshow-side-actions ' .
            'local-course-banner-builder-modal-preview-action-list'
    );
    $modalbody = html_writer::div(
        html_writer::div($modalpreview, 'local-course-banner-builder-slideshow-preview-main') .
            $slideshowactions,
        'local-course-banner-builder-slideshow-modal-preview'
    );

    $controls = html_writer::start_div('local-course-banner-builder-slideshow-overlay-controls');
    $positioninputs = [
        ['titlex', $titlex, 'title-x', manager::SLIDESHOW_DEFAULT_TITLE_X],
        ['titley', $titley, 'title-y', manager::SLIDESHOW_DEFAULT_TITLE_Y],
        ['bodyx', $bodyx, 'body-x', manager::SLIDESHOW_DEFAULT_BODY_X],
        ['bodyy', $bodyy, 'body-y', manager::SLIDESHOW_DEFAULT_BODY_Y],
        ['actionx', $actionx, 'action-x', manager::SLIDESHOW_DEFAULT_ACTION_X],
        ['actiony', $actiony, 'action-y', manager::SLIDESHOW_DEFAULT_ACTION_Y],
        ['labelx', $labelx, 'label-x', manager::SLIDESHOW_DEFAULT_LABEL_X],
        ['labely', $labely, 'label-y', manager::SLIDESHOW_DEFAULT_LABEL_Y],
    ];
    foreach ($positioninputs as [$name, $value, $inputkey, $default]) {
        $controls .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => number_format($value, 3, '.', ''),
            'data-slideshow-position-input' => $inputkey,
            'data-default-value' => number_format($default, 3, '.', ''),
        ]);
    }
    foreach ([
        ['titlealign', $titlealign, 'title', manager::SLIDESHOW_DEFAULT_TITLE_ALIGN],
        ['bodyalign', $bodyalign, 'body', manager::SLIDESHOW_DEFAULT_BODY_ALIGN],
        ['labelalign', $labelalign, 'label', manager::SLIDESHOW_DEFAULT_LABEL_ALIGN],
    ] as [$name, $value, $target, $default]) {
        $controls .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
            'data-slideshow-alignment-input' => $target,
            'data-default-value' => $default,
        ]);
    }
    $controls .= html_writer::tag('label', get_string('slideshowoverlaycolor', 'local_course_banner_builder'), [
        'for' => $colorid,
        'class' => 'local-course-banner-builder-slideshow-overlay-color-label ' .
            'local-course-banner-builder-slideshow-overlay-control',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'color',
        'id' => $colorid,
        'name' => 'overlaycolor',
        'value' => $color,
        'class' => 'form-control local-course-banner-builder-slideshow-color-input ' .
            'local-course-banner-builder-slideshow-overlay-color-input ' .
            'local-course-banner-builder-slideshow-overlay-control',
        'data-slideshow-color-input' => '1',
        'data-hex-target' => '#slideshow-overlay-hex-' . $colorid,
        'data-slideshow-overlay-color' => '1',
        'data-default-value' => manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR,
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => 'slideshow-overlay-hex-' . $colorid,
        'value' => $color,
        'class' => 'form-control local-course-banner-builder-slideshow-hex-input ' .
            'local-course-banner-builder-slideshow-overlay-color-hex ' .
            'local-course-banner-builder-slideshow-overlay-control',
        'data-slideshow-hex-input' => '1',
        'aria-label' => get_string('slideshowoverlaycolor', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::tag('label', get_string('slideshowoverlayopacity', 'local_course_banner_builder'), [
        'for' => $opacityid,
        'class' => 'local-course-banner-builder-slideshow-overlay-opacity-label ' .
            'local-course-banner-builder-slideshow-overlay-control',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'range',
        'id' => $opacityid,
        'name' => 'overlayopacity',
        'min' => 0,
        'max' => 85,
        'step' => 1,
        'value' => $opacity,
        'class' => 'custom-range local-course-banner-builder-range ' .
            'local-course-banner-builder-slideshow-overlay-opacity-input ' .
            'local-course-banner-builder-slideshow-overlay-control',
        'data-slideshow-overlay-opacity' => '1',
        'data-default-value' => (string)(int)(manager::SLIDESHOW_DEFAULT_OVERLAY_OPACITY * 100),
    ]);
    $controls .= html_writer::div($opacity . '%',
        'text-muted local-course-banner-builder-slideshow-opacity-output ' .
            'local-course-banner-builder-slideshow-overlay-opacity-output ' .
            'local-course-banner-builder-slideshow-overlay-control',
        [
        'data-slideshow-overlay-opacity-output' => '1',
        ]
    );
    $titlecolorid = 'slideshow-title-color-' . uniqid();
    $titlehexid = 'slideshow-title-hex-' . uniqid();
    $bodycolorid = 'slideshow-body-color-' . uniqid();
    $bodyhexid = 'slideshow-body-hex-' . uniqid();
    $controls .= html_writer::tag('h5', get_string('slideshowtextappearance', 'local_course_banner_builder'), [
        'class' => 'h6 mt-3 mb-1 local-course-banner-builder-slideshow-legacy-controls-heading',
    ]);
    $controls .= html_writer::start_div('local-course-banner-builder-slideshow-text-settings', [
        'data-slideshow-text-settings' => '1',
    ]);
    $controls .= html_writer::div(get_string('slideshowtitletext', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::empty_tag('input', [
        'type' => 'range',
        'name' => 'titlefontsize',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $titlefontsize,
        'class' => 'custom-range local-course-banner-builder-range',
        'data-slideshow-text-var' => '--local-course-banner-builder-slideshow-title-font-size',
        'data-slideshow-text-size' => 'title',
        'data-default-value' => (string)manager::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT,
    ]);
    $controls .= html_writer::div($titlefontsize . '%', 'text-muted local-course-banner-builder-slideshow-opacity-output', [
        'data-slideshow-text-output-for' => 'titlefontsize',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'number',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $titlefontsize,
        'class' => 'form-control local-course-banner-builder-slideshow-size-number',
        'data-slideshow-size-number-for' => 'titlefontsize',
        'aria-label' => get_string('slideshowtitletext', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'color',
        'id' => $titlecolorid,
        'name' => 'titlecolor',
        'value' => $titlecolor,
        'class' => 'form-control local-course-banner-builder-slideshow-color-input',
        'data-slideshow-color-input' => '1',
        'data-hex-target' => '#' . $titlehexid,
        'data-slideshow-text-var' => '--local-course-banner-builder-slideshow-title-color',
        'data-default-value' => manager::SLIDESHOW_DEFAULT_TITLE_COLOR,
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => $titlehexid,
        'value' => $titlecolor,
        'class' => 'form-control local-course-banner-builder-slideshow-hex-input',
        'aria-label' => get_string('slideshowtitlecolor', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::div(get_string('slideshowtitlefontfamily', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= local_course_banner_builder_render_slideshow_font_select(
        'titlefontfamily',
        $titlefontfamily,
        '--local-course-banner-builder-slideshow-title-font-family',
        manager::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY
    );
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-opacity-output');
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-overlay-spacer');
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-overlay-spacer');
    $controls .= html_writer::div(get_string('slideshowtextformat', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= $textstylebuttons('title', $titlestyles);
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-opacity-output');
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-overlay-spacer');
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-overlay-spacer');
    $controls .= html_writer::div(get_string('slideshowbodytext', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::empty_tag('input', [
        'type' => 'range',
        'name' => 'bodyfontsize',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $bodyfontsize,
        'class' => 'custom-range local-course-banner-builder-range',
        'data-slideshow-text-var' => '--local-course-banner-builder-slideshow-body-font-size',
        'data-slideshow-text-size' => 'body',
        'data-default-value' => (string)manager::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT,
    ]);
    $controls .= html_writer::div($bodyfontsize . '%', 'text-muted local-course-banner-builder-slideshow-opacity-output', [
        'data-slideshow-text-output-for' => 'bodyfontsize',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'number',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $bodyfontsize,
        'class' => 'form-control local-course-banner-builder-slideshow-size-number',
        'data-slideshow-size-number-for' => 'bodyfontsize',
        'aria-label' => get_string('slideshowbodytext', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'color',
        'id' => $bodycolorid,
        'name' => 'bodycolor',
        'value' => $bodycolor,
        'class' => 'form-control local-course-banner-builder-slideshow-color-input',
        'data-slideshow-color-input' => '1',
        'data-hex-target' => '#' . $bodyhexid,
        'data-slideshow-text-var' => '--local-course-banner-builder-slideshow-body-color',
        'data-default-value' => manager::SLIDESHOW_DEFAULT_BODY_COLOR,
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => $bodyhexid,
        'value' => $bodycolor,
        'class' => 'form-control local-course-banner-builder-slideshow-hex-input',
        'aria-label' => get_string('slideshowbodycolor', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::div(get_string('slideshowbodyfontfamily', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= local_course_banner_builder_render_slideshow_font_select(
        'bodyfontfamily',
        $bodyfontfamily,
        '--local-course-banner-builder-slideshow-body-font-family',
        manager::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY
    );
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-opacity-output');
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-overlay-spacer');
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-overlay-spacer');
    $controls .= html_writer::div(get_string('slideshowtextformat', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= $textstylebuttons('body', $bodystyles);
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-opacity-output');
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-overlay-spacer');
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-overlay-spacer');
    $controls .= html_writer::div(get_string('slideshowviewbuttonsize', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::empty_tag('input', [
        'type' => 'range',
        'name' => 'actionsize',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $actionsize,
        'class' => 'custom-range local-course-banner-builder-range',
        'data-slideshow-text-var' => '--local-course-banner-builder-slideshow-action-font-size',
        'data-slideshow-text-size' => 'action',
        'data-default-value' => (string)manager::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT,
    ]);
    $controls .= html_writer::div($actionsize . '%', 'text-muted local-course-banner-builder-slideshow-opacity-output', [
        'data-slideshow-text-output-for' => 'actionsize',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'number',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $actionsize,
        'class' => 'form-control local-course-banner-builder-slideshow-size-number',
        'data-slideshow-size-number-for' => 'actionsize',
        'aria-label' => get_string('slideshowviewbuttonsize', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::div(get_string('slideshowviewbuttonwidth', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::empty_tag('input', [
        'type' => 'range',
        'name' => 'actionwidth',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $actionwidth,
        'class' => 'custom-range local-course-banner-builder-range',
        'data-slideshow-text-var' => '--local-course-banner-builder-slideshow-action-width',
        'data-slideshow-text-size' => 'actionwidth',
        'data-default-value' => (string)manager::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT,
    ]);
    $controls .= html_writer::div($actionwidth . '%', 'text-muted local-course-banner-builder-slideshow-opacity-output', [
        'data-slideshow-text-output-for' => 'actionwidth',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'number',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $actionwidth,
        'class' => 'form-control local-course-banner-builder-slideshow-size-number',
        'data-slideshow-size-number-for' => 'actionwidth',
        'aria-label' => get_string('slideshowviewbuttonwidth', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'actionheight',
        'value' => $actionheight,
    ]);
    $controls .= html_writer::div(get_string('slideshowviewbuttoncorners', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::start_div('btn-group local-course-banner-builder-slideshow-corner-buttons', ['role' => 'group']);
    foreach ([manager::SLIDESHOW_CORNER_ROUNDED => 'slideshowcornersrounded',
        manager::SLIDESHOW_CORNER_SQUARE => 'slideshowcornerssquare'] as $value => $stringkey) {
        $controls .= html_writer::tag('button', get_string($stringkey, 'local_course_banner_builder'), [
            'type' => 'button',
            'class' => 'btn btn-sm ' . ($actioncorners === $value ? 'btn-primary active' : 'btn-outline-secondary'),
            'data-slideshow-corner-option' => 'action',
            'data-slideshow-corner-value' => $value,
        ]);
    }
    $controls .= html_writer::end_div();
    $controls .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'actioncorners',
        'value' => $actioncorners,
        'data-slideshow-corner-input' => 'action',
        'data-default-value' => manager::SLIDESHOW_DEFAULT_ACTION_CORNERS,
    ]);
    $controls .= html_writer::end_div();
    $controls .= html_writer::tag('h5', get_string('slideshowlabelcolors', 'local_course_banner_builder'), [
        'class' => 'h6 mt-2 mb-1',
    ]);
    $controls .= html_writer::start_div('local-course-banner-builder-slideshow-label-layout-settings', [
        'data-slideshow-label-layout-settings' => '1',
        'data-slideshow-text-settings' => '1',
    ]);
    $controls .= html_writer::div(get_string('slideshowlabelsize', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::empty_tag('input', [
        'type' => 'range',
        'name' => 'labelsize',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $labelsize,
        'class' => 'custom-range local-course-banner-builder-range',
        'data-slideshow-text-var' => '--local-course-banner-builder-slideshow-label-font-size',
        'data-slideshow-text-size' => 'label',
        'data-default-value' => (string)manager::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT,
    ]);
    $controls .= html_writer::div($labelsize . '%', 'text-muted local-course-banner-builder-slideshow-opacity-output', [
        'data-slideshow-text-output-for' => 'labelsize',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'number',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $labelsize,
        'class' => 'form-control local-course-banner-builder-slideshow-size-number',
        'data-slideshow-size-number-for' => 'labelsize',
        'aria-label' => get_string('slideshowlabelsize', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::div(get_string('slideshowlabelorientation', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::start_div('btn-group local-course-banner-builder-slideshow-label-orientation-buttons', [
        'role' => 'group',
        'data-slideshow-label-orientation-buttons' => '1',
    ]);
    foreach ([manager::SLIDESHOW_LABEL_ORIENTATION_ROW => 'slideshowlabelorientationrow',
        manager::SLIDESHOW_LABEL_ORIENTATION_COLUMN => 'slideshowlabelorientationcolumn'] as $value => $stringkey) {
        $controls .= html_writer::tag('button', get_string($stringkey, 'local_course_banner_builder'), [
            'type' => 'button',
            'class' => 'btn btn-sm ' . ($labelorientation === $value ? 'btn-primary active' : 'btn-outline-secondary'),
            'data-slideshow-label-orientation-option' => $value,
        ]);
    }
    $controls .= html_writer::end_div();
    $controls .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'labelorientation',
        'value' => $labelorientation,
        'data-slideshow-label-orientation-input' => '1',
        'data-default-value' => manager::SLIDESHOW_DEFAULT_LABEL_ORIENTATION,
    ]);
    $controls .= html_writer::div(get_string('slideshowlabelcorners', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::start_div('btn-group local-course-banner-builder-slideshow-corner-buttons', ['role' => 'group']);
    foreach ([manager::SLIDESHOW_CORNER_ROUNDED => 'slideshowcornersrounded',
        manager::SLIDESHOW_CORNER_SQUARE => 'slideshowcornerssquare'] as $value => $stringkey) {
        $controls .= html_writer::tag('button', get_string($stringkey, 'local_course_banner_builder'), [
            'type' => 'button',
            'class' => 'btn btn-sm ' . ($labelcorners === $value ? 'btn-primary active' : 'btn-outline-secondary'),
            'data-slideshow-corner-option' => 'label',
            'data-slideshow-corner-value' => $value,
        ]);
    }
    $controls .= html_writer::end_div();
    $controls .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'labelcorners',
        'value' => $labelcorners,
        'data-slideshow-corner-input' => 'label',
        'data-default-value' => manager::SLIDESHOW_DEFAULT_LABEL_CORNERS,
    ]);
    $controls .= html_writer::end_div();
    $controls .= html_writer::start_div('local-course-banner-builder-slideshow-label-colors', [
        'data-slideshow-label-color-settings' => '1',
    ]);
    $labeldefaults = manager::get_default_slideshow_label_colors();
    if ($context !== manager::SLIDESHOW_CONTEXT_SITE) {
        unset($labeldefaults['courseorigin']);
    }
    $labelcolourfield = static function(
        string $type,
        string $role,
        string $label,
        array $colors,
        array $defaults
    ): string {
        $id = 'slideshow-label-' . $type . '-' . $role . '-' . uniqid();
        $hexid = 'slideshow-label-' . $type . '-' . $role . '-hex-' . uniqid();
        $suffix = $role === 'background' ? 'bg' : ($role === 'text' ? 'color' : $role);
        return html_writer::tag('label', $label, [
            'for' => $id,
            'class' => 'sr-only',
        ]) .
        html_writer::empty_tag('input', [
            'type' => 'color',
            'id' => $id,
            'name' => 'label_' . $type . '_' . $role,
            'value' => (string)($colors[$role] ?? $defaults[$role]),
            'class' => 'form-control local-course-banner-builder-slideshow-color-input',
            'data-slideshow-color-input' => '1',
            'data-hex-target' => '#' . $hexid,
            'data-slideshow-label-var' => '--local-course-banner-builder-slideshow-label-' . $type . '-' . $suffix,
            'data-default-value' => (string)$defaults[$role],
        ]) .
        html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => $hexid,
            'value' => (string)($colors[$role] ?? $defaults[$role]),
            'class' => 'form-control local-course-banner-builder-slideshow-hex-input',
            'aria-label' => $label,
        ]);
    };
    $labelcolorheadings = [
        get_string('slideshowlabelbackground', 'local_course_banner_builder'),
        get_string('slideshowlabeltext', 'local_course_banner_builder'),
        get_string('slideshowlabelsbordercolor', 'local_course_banner_builder'),
        get_string('slideshowlabelsshadowcolor', 'local_course_banner_builder'),
    ];
    $controls .= html_writer::start_div(
        'local-course-banner-builder-slideshow-label-color-row ' .
            'local-course-banner-builder-slideshow-label-color-row--heading'
    );
    $controls .= html_writer::div('', 'local-course-banner-builder-slideshow-label-color-title');
    foreach ($labelcolorheadings as $heading) {
        $controls .= html_writer::div($heading, 'local-course-banner-builder-slideshow-label-color-heading');
    }
    $controls .= html_writer::div(
        get_string('preview'),
        'local-course-banner-builder-slideshow-label-color-heading ' .
            'local-course-banner-builder-slideshow-label-color-heading--sample'
    );
    $controls .= html_writer::end_div();
    $sampleicons = [
        'forums' => 'fa-comments',
        'siteannouncements' => 'fa-bullhorn',
        'assignments' => 'fa-tasks',
        'quizzes' => 'fa-question-circle',
    ];
    foreach ($labeldefaults as $type => $defaults) {
        $colors = $labelcolors[$type] ?? $defaults;
        $labelkey = $type === 'forums' ? 'slideshow:type:courseforum' :
            ($type === 'siteannouncements' ? 'slideshow:type:siteannouncements' :
                ($type === 'assignments' ? 'slideshow:type:assignment' :
                    ($type === 'courseorigin' ? 'slideshow:type:courseorigin' : 'slideshow:type:quiz')));
        $sampleclass = 'local-course-banner-builder-slideshow-label local-course-banner-builder-slideshow-label--' .
            ($type === 'courseorigin' ? 'course-shortname' : ($type === 'forums' ? 'forums' : $type));
        $samplestyle = '--local-course-banner-builder-slideshow-label-' . $type . '-bg: ' .
            s((string)($colors['background'] ?? $defaults['background'])) . ';' .
            '--local-course-banner-builder-slideshow-label-' . $type . '-color: ' .
            s((string)($colors['text'] ?? $defaults['text'])) . ';' .
            '--local-course-banner-builder-slideshow-label-' . $type . '-border: ' .
            s((string)($colors['border'] ?? $defaults['border'])) . ';' .
            '--local-course-banner-builder-slideshow-label-' . $type . '-shadow: ' .
            s((string)($colors['shadow'] ?? $defaults['shadow'])) . ';' .
            '--local-course-banner-builder-slideshow-label-' . $type . '-shadow-rgb: ' .
            s($stylergb((string)($colors['shadow'] ?? $defaults['shadow']))) . ';';
        $controls .= html_writer::start_div('local-course-banner-builder-slideshow-label-color-row');
        $controls .= html_writer::div(get_string($labelkey, 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-label-color-title');
        $controls .= $labelcolourfield($type, 'background', get_string('slideshowlabelbackground', 'local_course_banner_builder'),
            $colors, $defaults);
        $controls .= $labelcolourfield($type, 'text', get_string('slideshowlabeltext', 'local_course_banner_builder'),
            $colors, $defaults);
        $controls .= $labelcolourfield($type, 'border', get_string('slideshowlabelsbordercolor', 'local_course_banner_builder'),
            $colors, $defaults);
        $controls .= $labelcolourfield($type, 'shadow', get_string('slideshowlabelsshadowcolor', 'local_course_banner_builder'),
            $colors, $defaults);
        $samplecontent = '';
        if (!empty($sampleicons[$type])) {
            $samplecontent .= html_writer::tag('i', '', [
                'class' => 'fa ' . $sampleicons[$type] . ' local-course-banner-builder-slideshow-label-icon',
                'aria-hidden' => 'true',
            ]);
        }
        $samplecontent .= html_writer::span(get_string($labelkey, 'local_course_banner_builder'));
        $controls .= html_writer::span(
            $samplecontent,
            $sampleclass . ' local-course-banner-builder-slideshow-label-color-sample',
            [
                'style' => $samplestyle,
                'data-slideshow-label-sample' => '1',
            ]
        );
        $controls .= html_writer::end_div();
    }
    $controls .= html_writer::end_div();
    $controls .= html_writer::end_div();
    $modalbody .= html_writer::div($controls, 'local-course-banner-builder-slideshow-modal-settings');
    $modal .= html_writer::div($modalbody, 'modal-body local-course-banner-builder-title-modal-body local-course-banner-builder-slideshow-modal-body');
    $modal .= html_writer::div(
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-rotate-left me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('slideshowdefaultoverlaysettings', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'data-action' => 'local-course-banner-builder-reset-slideshow-overlay',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-compact-save-button',
            ]
        ) .
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-rotate-left me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('slideshowdefaulttextsettings', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'data-action' => 'local-course-banner-builder-reset-slideshow-text',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-compact-save-button',
            ]
        ) .
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-rotate-left me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('slideshowdefaultlabelsettings', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'data-action' => 'local-course-banner-builder-reset-slideshow-labels',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-compact-save-button',
            ]
        ) .
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-rotate-left me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('slideshowdefaultallsettings', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'data-action' => 'local-course-banner-builder-reset-slideshow-all',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-compact-save-button',
            ]
        ) .
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-save me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('saveslideshowsettings', 'local_course_banner_builder')),
            ['type' => 'submit', 'class' => 'btn btn-primary local-course-banner-builder-compact-save-button']
        ),
        'modal-footer local-course-banner-builder-slideshow-modal-footer'
    );
    $modal .= html_writer::end_div() . html_writer::end_div() . html_writer::end_div();

    return html_writer::div(
        html_writer::div(
            html_writer::tag('button',
                html_writer::tag('i', '', ['class' => 'fa fa-palette me-2', 'aria-hidden' => 'true']) .
                    html_writer::span(get_string('slideshoweditappearance', 'local_course_banner_builder')),
                [
                    'type' => 'button',
                    'class' => 'btn btn-outline-secondary local-course-banner-builder-compact-save-button',
                    'data-toggle' => 'modal',
                    'data-bs-toggle' => 'modal',
                    'data-target' => '#' . $modalid,
                    'data-bs-target' => '#' . $modalid,
                ]
            ),
            'local-course-banner-builder-slideshow-preview-launch'
        ) .
        $modal,
        'local-course-banner-builder-slideshow-overlay-panel',
        ['data-slideshow-overlay-settings' => '1']
    );
}

/**
 * Render one slideshow configuration panel.
 *
 * @param string $context
 * @return string
 */
function local_course_banner_builder_render_slideshow_form(string $context): string {
    $config = manager::get_slideshow_config($context);
    $iscourse = $context === manager::SLIDESHOW_CONTEXT_COURSE;
    $title = get_string($iscourse ? 'slideshowcoursebanner' : 'slideshowsitebanner', 'local_course_banner_builder');
    $desc = get_string($iscourse ? 'slideshowcoursebanner_desc' : 'slideshowsitebanner_desc', 'local_course_banner_builder');

    $content = html_writer::tag('h3', $title, ['class' => 'h5 mb-1']);
    $content .= html_writer::tag('p', $desc, ['class' => 'text-muted mb-3 local-course-banner-builder-admin-small-text']);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updateslideshow', 'value' => 1]);
    $content .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'context', 'value' => $context]);

    $content .= html_writer::start_div('local-course-banner-builder-slideshow-grid');
    $content .= html_writer::start_div('local-course-banner-builder-slideshow-section');
    $content .= html_writer::tag('h4', get_string('slideshowdisplay', 'local_course_banner_builder'), ['class' => 'h6']);
    $content .= local_course_banner_builder_slideshow_toggle(
        'enabled',
        get_string('slideshowenabled', 'local_course_banner_builder'),
        !empty($config['enabled']),
        get_string('slideshowenabled_help', 'local_course_banner_builder'),
        true
    );
    $bannerenabled = $iscourse ? manager::is_course_banner_enabled() : manager::is_site_banner_enabled();
    $content .= html_writer::div(
        get_string(
            $iscourse ? 'slideshowcoursebannerdisabledwarning' : 'slideshowsitebannerdisabledwarning',
            'local_course_banner_builder'
        ),
        'alert alert-warning py-2 px-3 mt-2',
        [
            'data-slideshow-banner-warning' => '1',
            'data-banner-active' => $bannerenabled ? '1' : '0',
            'hidden' => !empty($config['enabled']) && !$bannerenabled ? null : 'hidden',
        ]
    );
    $content .= local_course_banner_builder_slideshow_toggle(
        'forums',
        get_string($iscourse ? 'slideshowforumscourse' : 'slideshowforumssite', 'local_course_banner_builder'),
        !empty($config['forums'])
    );
    if ($iscourse) {
        $content .= local_course_banner_builder_slideshow_toggle(
            'siteannouncements',
            get_string('slideshowsiteannouncementscourse', 'local_course_banner_builder'),
            !empty($config['siteannouncements']),
            get_string('slideshowsiteannouncementscourse_help', 'local_course_banner_builder')
        );
    }
    $content .= local_course_banner_builder_slideshow_toggle(
        'assignments',
        get_string($iscourse ? 'slideshowassignmentscourse' : 'slideshowassignmentssite', 'local_course_banner_builder'),
        !empty($config['assignments']),
        get_string('slideshowstudentonly_help', 'local_course_banner_builder')
    );
    $content .= local_course_banner_builder_slideshow_toggle(
        'quizzes',
        get_string($iscourse ? 'slideshowquizzescourse' : 'slideshowquizzessite', 'local_course_banner_builder'),
        !empty($config['quizzes']),
        get_string('slideshowstudentonly_help', 'local_course_banner_builder')
    );
    $content .= html_writer::end_div();

    $content .= html_writer::start_div('local-course-banner-builder-slideshow-section');
    $content .= html_writer::tag('h4', get_string('slideshowcontrols', 'local_course_banner_builder'), ['class' => 'h6']);
    $content .= local_course_banner_builder_slideshow_toggle(
        'autoplay',
        get_string('slideshowautoplay', 'local_course_banner_builder'),
        !empty($config['autoplay'])
    );
    $content .= html_writer::start_div('form-group local-course-banner-builder-slideshow-delay');
    $content .= html_writer::tag('label', get_string('slideshowdelay', 'local_course_banner_builder'), ['for' => 'slideshow-delay-' . $context]);
    $content .= html_writer::empty_tag('input', [
        'type' => 'number',
        'class' => 'form-control',
        'id' => 'slideshow-delay-' . $context,
        'name' => 'delay',
        'min' => 1000,
        'max' => 60000,
        'step' => 250,
        'value' => (int)$config['delay'],
    ]);
    $content .= html_writer::div(get_string('slideshowdelay_help', 'local_course_banner_builder'), 'form-text text-muted');
    $content .= html_writer::end_div();
    $content .= local_course_banner_builder_slideshow_toggle(
        'arrows',
        get_string('slideshowarrows', 'local_course_banner_builder'),
        !empty($config['arrows'])
    );
    $content .= local_course_banner_builder_slideshow_toggle(
        'dots',
        get_string('slideshowdots', 'local_course_banner_builder'),
        !empty($config['dots'])
    );
    $content .= html_writer::end_div();
    $content .= html_writer::end_div();

    $content .= html_writer::tag('h4', get_string('slideshowoverlaysettings', 'local_course_banner_builder'), ['class' => 'h6 mt-3']);
    $content .= local_course_banner_builder_render_slideshow_overlay_settings($config, $context);

    $content .= html_writer::div(
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-rotate-left me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('slideshowdefaultsettings', 'local_course_banner_builder')),
            [
                'type' => 'submit',
                'name' => 'defaultsettings',
                'value' => 1,
                'class' => 'btn btn-outline-secondary local-course-banner-builder-compact-save-button',
            ]
        ),
        'local-course-banner-builder-slideshow-actions'
    );

    return html_writer::tag('form', $content, [
        'method' => 'post',
        'action' => (new moodle_url('/local/course_banner_builder/admin_slideshow.php'))->out(false),
        'class' => 'local-course-banner-builder-slideshow-card',
    ]);
}

echo $OUTPUT->header();

echo html_writer::start_div('local-course-banner-builder-admin local-course-banner-builder-admin--native local-course-banner-builder-slideshow-admin');
$courseformatmodal = local_course_banner_builder_render_slideshow_banner_format_modal(
    manager::SLIDESHOW_CONTEXT_COURSE,
    manager::get_course_banner_format()
);
$siteformatmodal = local_course_banner_builder_render_slideshow_banner_format_modal(
    manager::SLIDESHOW_CONTEXT_SITE,
    manager::get_site_banner_format()
);
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_manage.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-image me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('managecoursebannersquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action']
    ) .
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_site.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-desktop me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('managesitebannerquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action']
    ) .
    html_writer::tag(
        'button',
        html_writer::tag('i', '', ['class' => 'fa fa-columns me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('sitebannerformatbutton', 'local_course_banner_builder')),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action',
            'data-toggle' => 'modal',
            'data-target' => '#local-course-banner-builder-slideshow-site-format-modal',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#local-course-banner-builder-slideshow-site-format-modal',
        ]
    ) .
    html_writer::tag(
        'button',
        html_writer::tag('i', '', ['class' => 'fa fa-columns me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('coursebannerformatbutton', 'local_course_banner_builder')),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action',
            'data-toggle' => 'modal',
            'data-target' => '#local-course-banner-builder-slideshow-course-format-modal',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#local-course-banner-builder-slideshow-course-format-modal',
        ]
    ),
    'local-course-banner-builder-admin-switcher mb-3'
);
echo $siteformatmodal . $courseformatmodal;

echo $OUTPUT->heading(get_string('manageslideshow', 'local_course_banner_builder'));
echo html_writer::tag('p', get_string('manageslideshow_desc', 'local_course_banner_builder'), [
    'class' => 'text-muted local-course-banner-builder-admin-small-text',
]);

echo html_writer::div(
    local_course_banner_builder_render_slideshow_form(manager::SLIDESHOW_CONTEXT_COURSE) .
    local_course_banner_builder_render_slideshow_form(manager::SLIDESHOW_CONTEXT_SITE),
    'local-course-banner-builder-slideshow-admin-grid'
);
echo html_writer::div(
    html_writer::tag('button',
        html_writer::tag('i', '', ['class' => 'fa fa-save me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('saveslideshowsettings', 'local_course_banner_builder')),
        [
            'type' => 'button',
            'class' => 'btn btn-primary local-course-banner-builder-compact-save-button',
            'data-action' => 'local-course-banner-builder-save-all-slideshows',
        ]
    ),
    'local-course-banner-builder-slideshow-global-actions'
);

echo html_writer::end_div();
echo $OUTPUT->footer();
