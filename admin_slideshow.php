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
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_course_banner_builder\manager;

require_login();
require_capability('local/course_banner_builder:manage', context_system::instance());
admin_externalpage_setup('local_course_banner_builder_slideshow');

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
            });
            textInput.addEventListener('input', function() {
                var value = normalise(textInput.value);
                if (value) {
                    colorInput.value = value;
                    colorInput.dispatchEvent(new Event('input', {bubbles: true}));
                }
            });
        }
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
            });
            var row = input.closest('.local-course-banner-builder-slideshow-label-color-row');
            var sample = row ? row.querySelector('[data-slideshow-label-sample=\"1\"]') : null;
            if (sample) {
                sample.style.setProperty(variable, input.value || '#000000');
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
        var getFormatSizeScale = function(format) {
            if (format === 'standard') {
                return 1.24;
            }
            if (format === 'fullwidthtopcompact') {
                return 0.78;
            }
            return 1;
        };
        var buildTitleSize = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '');
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqh, min(' + (28 * scale).toFixed(3) +
                'cqh, ' + (3.4 * scale).toFixed(3) + 'cqw), ' + (36 * scale).toFixed(3) + 'cqh)';
        };
        var buildBodySize = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '');
            return 'clamp(' + (5.5 * scale).toFixed(3) + 'cqh, min(' + (14 * scale).toFixed(3) +
                'cqh, ' + (1.7 * scale).toFixed(3) + 'cqw), ' + (19 * scale).toFixed(3) + 'cqh)';
        };
        var buildLabelSize = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '');
            return 'clamp(' + (5.8 * scale).toFixed(3) + 'cqh, min(' + (12.5 * scale).toFixed(3) +
                'cqh, ' + (1.55 * scale).toFixed(3) + 'cqw), ' + (17 * scale).toFixed(3) + 'cqh)';
        };
        var buildActionSize = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '');
            return 'clamp(' + (6 * scale).toFixed(3) + 'cqh, min(' + (13 * scale).toFixed(3) +
                'cqh, ' + (1.6 * scale).toFixed(3) + 'cqw), ' + (18 * scale).toFixed(3) + 'cqh)';
        };
        var buildActionWidth = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '');
            return 'clamp(' + (10 * scale).toFixed(3) + 'cqw, ' + (18 * scale).toFixed(3) + 'cqw, ' + (34 * scale).toFixed(3) + 'cqw)';
        };
        var buildActionHeight = function(percent, format) {
            var scale = Math.max(25, Math.min(100, parseInt(percent || '100', 10))) / 100;
            scale = scale * getFormatSizeScale(format || '');
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
                var root = panel.closest('[data-slideshow-overlay-settings=\"1\"]');
                var width = root ? root.querySelector('[name=\"actionwidth\"]') : null;
                var height = root ? root.querySelector('[name=\"actionheight\"]') : null;
                var font = root ? root.querySelector('[name=\"actionsize\"]') : null;
                if (width && height && font && document.activeElement !== font) {
                    font.value = Math.max(25, Math.min(100, Math.round((parseInt(width.value || '70', 10) + parseInt(height.value || '70', 10)) / 2)));
                    sync(font);
                }
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
        action: {x: 50, y: 74}
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
            button.classList.toggle('btn-primary', enabled);
            button.classList.toggle('btn-outline-secondary', !enabled);
            button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        });
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
        var input = panel.querySelector('[data-slideshow-corner-input=\"' + target + '\"]');
        if (input) {
            input.value = value;
        }
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
        target = target === 'body' ? 'body' : 'title';
        var get = function(style) {
            var input = panel.querySelector('[data-slideshow-text-style-input=\"' + target + style + '\"]');
            return input && input.value === '1';
        };
        var bold = get('bold');
        var italic = get('italic');
        var underline = get('underline');
        var strike = get('strike');
        panel.querySelectorAll('[data-slideshow-overlay-preview=\"1\"]').forEach(function(preview) {
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-font-weight',
                bold ? (target === 'title' ? '800' : '700') : '400');
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-font-style',
                italic ? 'italic' : 'normal');
            preview.style.setProperty('--local-course-banner-builder-slideshow-' + target + '-text-decoration',
                slideshowGetTextDecoration(underline, strike));
        });
        ['bold', 'italic', 'underline', 'strike'].forEach(function(style) {
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
        slideshowApplyTextStyle(panel, 'title');
        slideshowApplyTextStyle(panel, 'body');
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
            textStyles: slideshowPreviewCaptureTextStyles(panel)
        };
    };
    var slideshowPreviewApplyState = function(preview, state) {
        ['label', 'title', 'body', 'action'].forEach(function(key) {
            if (state && state[key]) {
                slideshowPreviewApplyPosition(preview, key, state[key].x, state[key].y);
            }
        });
        slideshowPreviewApplyTextStyles(preview.closest('[data-slideshow-overlay-settings=\"1\"]'), state ? state.textStyles : null);
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
        var textSelected = selectedKey === 'title' || selectedKey === 'body';
        root.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-selected-slideshow-text-style\"]').forEach(function(button) {
            button.classList.toggle('d-none', !textSelected);
            button.disabled = !textSelected;
            if (!textSelected) {
                button.classList.remove('active');
                return;
            }
            var style = button.getAttribute('data-slideshow-text-style');
            var input = root.querySelector('[data-slideshow-text-style-input=\"' + selectedKey + style + '\"]');
            var active = input && input.value === '1';
            button.classList.toggle('active', active);
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-secondary', !active);
        });
        slideshowPreviewSyncSnapButtons(root);
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
    var slideshowPreviewSelect = function(preview, key) {
        if (!preview) {
            return;
        }
        Array.prototype.slice.call(preview.querySelectorAll('[data-slideshow-preview-draggable]')).forEach(function(node) {
            node.classList.toggle(slideshowPreviewSelectionClass, node.getAttribute('data-slideshow-preview-draggable') === key);
        });
        slideshowPreviewSyncButtons(preview);
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
            panel.querySelectorAll('[data-slideshow-text-style-input][data-default-value]').forEach(function(input) {
                input.value = input.getAttribute('data-default-value') || '0';
                slideshowApplyTextStyle(panel, input.getAttribute('data-slideshow-text-style-target'));
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
            if (target !== 'title' && target !== 'body') {
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
        });
    });
    document.querySelectorAll('[data-action=\"local-course-banner-builder-reset-slideshow-all\"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var panel = button.closest('[data-slideshow-overlay-settings=\"1\"]');
            if (!panel) {
                return;
            }
            ['local-course-banner-builder-reset-slideshow-overlay', 'local-course-banner-builder-reset-slideshow-text', 'local-course-banner-builder-reset-slideshow-labels'].forEach(function(action) {
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
        'titlebold', 'titleitalic', 'titleunderline', 'titlestrike',
        'bodybold', 'bodyitalic', 'bodyunderline', 'bodystrike'] as $field) {
        $clean[$field] = empty($values[$field]) ? 0 : 1;
    }
    foreach (['delay', 'overlayopacity', 'titlefontsize', 'bodyfontsize', 'actionsize', 'actionwidth',
        'actionheight', 'labelsize'] as $field) {
        $clean[$field] = clean_param($values[$field] ?? 0, PARAM_INT);
    }
    foreach (['titlex', 'titley', 'bodyx', 'bodyy', 'actionx', 'actiony', 'labelx', 'labely'] as $field) {
        $clean[$field] = clean_param($values[$field] ?? 0, PARAM_FLOAT);
    }
    foreach (['overlaycolor', 'titlecolor', 'bodycolor', 'titlefontfamily', 'bodyfontfamily',
        'label_forums_background', 'label_forums_text',
        'label_siteannouncements_background', 'label_siteannouncements_text',
        'label_assignments_background', 'label_assignments_text',
        'label_quizzes_background', 'label_quizzes_text'] as $field) {
        $clean[$field] = clean_param($values[$field] ?? '', PARAM_RAW_TRIMMED);
    }
    foreach (['labelorientation', 'labelcorners', 'actioncorners'] as $field) {
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
        manager::set_slideshow_config($context, [
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
            'titlebold' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_TITLE_BOLD : optional_param('titlebold', 0, PARAM_BOOL),
            'titleitalic' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_TITLE_ITALIC : optional_param('titleitalic', 0, PARAM_BOOL),
            'titleunderline' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_TITLE_UNDERLINE : optional_param('titleunderline', 0, PARAM_BOOL),
            'titlestrike' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_TITLE_STRIKE : optional_param('titlestrike', 0, PARAM_BOOL),
            'bodybold' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_BODY_BOLD : optional_param('bodybold', 0, PARAM_BOOL),
            'bodyitalic' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_BODY_ITALIC : optional_param('bodyitalic', 0, PARAM_BOOL),
            'bodyunderline' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_BODY_UNDERLINE : optional_param('bodyunderline', 0, PARAM_BOOL),
            'bodystrike' => $defaulttext || $defaultall ? manager::SLIDESHOW_DEFAULT_BODY_STRIKE : optional_param('bodystrike', 0, PARAM_BOOL),
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
        ]);
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
                        'class' => 'modal-title',
                        'id' => $modalid . '-title',
                    ]) .
                    html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
                        'type' => 'button',
                        'class' => 'close',
                        'data-dismiss' => 'modal',
                        'data-bs-dismiss' => 'modal',
                        'aria-label' => get_string('closebuttontitle'),
                    ]),
                    'modal-header'
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
            $scale *= 0.78;
        }
    }
    if ($kind === 'title') {
        return 'clamp(' . round(10 * $scale, 3) . 'cqh, min(' . round(28 * $scale, 3) . 'cqh, ' .
            round(3.4 * $scale, 3) . 'cqw), ' .
            round(36 * $scale, 3) . 'cqh)';
    }
    if ($kind === 'label') {
        return 'clamp(' . round(5.8 * $scale, 3) . 'cqh, min(' . round(12.5 * $scale, 3) . 'cqh, ' .
            round(1.55 * $scale, 3) . 'cqw), ' .
            round(17 * $scale, 3) . 'cqh)';
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
        'data-slideshow-text-var' => $cssvar,
        'data-slideshow-font-family' => '1',
        'data-default-value' => $defaultvalue,
    ]);
    foreach ($options as $value => $label) {
        $select .= html_writer::tag('option', s($label), [
            'value' => $value,
            'selected' => (string)$value === $selected ? 'selected' : null,
            'data-font-value' => $value,
            'style' => $value !== '' ? 'font-family: ' . s($value) . ';' : null,
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
    $actionsize = max(25, min(100, (int)($config['actionsize'] ?? manager::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT)));
    $actionwidth = max(25, min(100, (int)($config['actionwidth'] ?? manager::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT)));
    $actionheight = max(25, min(100, (int)($config['actionheight'] ?? manager::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT)));
    $labelsize = max(25, min(100, (int)($config['labelsize'] ?? manager::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT)));
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
    $titlestyles = [
        'bold' => !empty($config['titlebold']),
        'italic' => !empty($config['titleitalic']),
        'underline' => !empty($config['titleunderline']),
        'strike' => !empty($config['titlestrike']),
    ];
    $bodystyles = [
        'bold' => !empty($config['bodybold']),
        'italic' => !empty($config['bodyitalic']),
        'underline' => !empty($config['bodyunderline']),
        'strike' => !empty($config['bodystrike']),
    ];
    $titlex = (float)($config['titlex'] ?? manager::SLIDESHOW_DEFAULT_TITLE_X);
    $titley = (float)($config['titley'] ?? manager::SLIDESHOW_DEFAULT_TITLE_Y);
    $bodyx = (float)($config['bodyx'] ?? manager::SLIDESHOW_DEFAULT_BODY_X);
    $bodyy = (float)($config['bodyy'] ?? manager::SLIDESHOW_DEFAULT_BODY_Y);
    $actionx = (float)($config['actionx'] ?? manager::SLIDESHOW_DEFAULT_ACTION_X);
    $actiony = (float)($config['actiony'] ?? manager::SLIDESHOW_DEFAULT_ACTION_Y);
    $labelx = (float)($config['labelx'] ?? manager::SLIDESHOW_DEFAULT_LABEL_X);
    $labely = (float)($config['labely'] ?? manager::SLIDESHOW_DEFAULT_LABEL_Y);
    $previewstyle = '--local-course-banner-builder-slideshow-overlay-rgb: ' .
        s((string)($config['overlayrgb'] ?? '0, 0, 0')) .
        '; --local-course-banner-builder-slideshow-overlay-opacity: ' . number_format($opacity / 100, 2, '.', '') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-size: ' .
        local_course_banner_builder_slideshow_font_clamp('title', $titlefontsize, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-size: ' .
        local_course_banner_builder_slideshow_font_clamp('body', $bodyfontsize, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-font-size: ' .
        local_course_banner_builder_slideshow_font_clamp('action', $actionsize, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-width: ' .
        local_course_banner_builder_slideshow_font_clamp('actionwidth', $actionwidth, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-height: ' .
        local_course_banner_builder_slideshow_font_clamp('actionheight', $actionheight, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-font-size: ' .
        local_course_banner_builder_slideshow_font_clamp('label', $labelsize, $bannerformat) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-orientation: ' . s($labelorientation) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-label-radius: ' .
        ($labelcorners === manager::SLIDESHOW_CORNER_SQUARE ? '0.28rem' : '999px') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-action-radius: ' .
        ($actioncorners === manager::SLIDESHOW_CORNER_SQUARE ? '0.28rem' : '999px') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-color: ' . s($titlecolor) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-color: ' . s($bodycolor) . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-family: ' .
        ($titlefontfamily !== '' ? s($titlefontfamily) : 'inherit') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-family: ' .
        ($bodyfontfamily !== '' ? s($bodyfontfamily) : 'inherit') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-weight: ' .
        ($titlestyles['bold'] ? '800' : '400') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-style: ' .
        ($titlestyles['italic'] ? 'italic' : 'normal') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-title-text-decoration: ' .
        (($titlestyles['underline'] || $titlestyles['strike']) ?
            trim(($titlestyles['underline'] ? 'underline ' : '') . ($titlestyles['strike'] ? 'line-through' : '')) : 'none') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-weight: ' .
        ($bodystyles['bold'] ? '700' : '400') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-style: ' .
        ($bodystyles['italic'] ? 'italic' : 'normal') . ';';
    $previewstyle .= ' --local-course-banner-builder-slideshow-body-text-decoration: ' .
        (($bodystyles['underline'] || $bodystyles['strike']) ?
            trim(($bodystyles['underline'] ? 'underline ' : '') . ($bodystyles['strike'] ? 'line-through' : '')) : 'none') . ';';
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
        $defaults = $target === 'title' ? [
            'bold' => manager::SLIDESHOW_DEFAULT_TITLE_BOLD,
            'italic' => manager::SLIDESHOW_DEFAULT_TITLE_ITALIC,
            'underline' => manager::SLIDESHOW_DEFAULT_TITLE_UNDERLINE,
            'strike' => manager::SLIDESHOW_DEFAULT_TITLE_STRIKE,
        ] : [
            'bold' => manager::SLIDESHOW_DEFAULT_BODY_BOLD,
            'italic' => manager::SLIDESHOW_DEFAULT_BODY_ITALIC,
            'underline' => manager::SLIDESHOW_DEFAULT_BODY_UNDERLINE,
            'strike' => manager::SLIDESHOW_DEFAULT_BODY_STRIKE,
        ];
        $icons = [
            'bold' => 'fa-bold',
            'italic' => 'fa-italic',
            'underline' => 'fa-underline',
            'strike' => 'fa-strikethrough',
        ];
        $labels = [
            'bold' => get_string('slideshowtextbold', 'local_course_banner_builder'),
            'italic' => get_string('slideshowtextitalic', 'local_course_banner_builder'),
            'underline' => get_string('slideshowtextunderline', 'local_course_banner_builder'),
            'strike' => get_string('slideshowtextstrike', 'local_course_banner_builder'),
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
                    get_string('slideshow:type:courseforum', 'local_course_banner_builder'),
                    'local-course-banner-builder-slideshow-label local-course-banner-builder-slideshow-label--forums'
                ) .
                html_writer::span('COURSE101', 'local-course-banner-builder-slideshow-label local-course-banner-builder-slideshow-label--course-shortname') .
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
    $modal .= html_writer::start_div('modal-dialog modal-xl', ['role' => 'document']);
    $modal .= html_writer::start_div('modal-content');
    $modal .= html_writer::start_div('modal-header');
    $modal .= html_writer::tag('h5',
        get_string('slideshowlargepreview', 'local_course_banner_builder') . ' - ' . $contextlabel,
        ['class' => 'modal-title']
    );
    $modal .= html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
        'type' => 'button',
        'class' => 'close',
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
        $previewtoolbarbutton('fa-undo', get_string('undopreviewchange', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-preview-undo', ['disabled' => 'disabled']) .
        $previewtoolbarbutton('fa-magnet', get_string('togglepreviewsnap', 'local_course_banner_builder'), 'local-course-banner-builder-toggle-slideshow-preview-snap', ['aria-pressed' => 'true']) .
        $previewtoolbarbutton('fa-crosshairs', get_string('recenterpreviewelement', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-preview-recenter-element', ['disabled' => 'disabled']) .
        $previewtoolbarbutton('fa-bullseye', get_string('recenterallpreviewelements', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-preview-recenter-all') .
        $previewtoolbarbutton('fa-bold', get_string('slideshowtextbold', 'local_course_banner_builder'), 'local-course-banner-builder-toggle-selected-slideshow-text-style', ['data-slideshow-text-style' => 'bold']) .
        $previewtoolbarbutton('fa-italic', get_string('slideshowtextitalic', 'local_course_banner_builder'), 'local-course-banner-builder-toggle-selected-slideshow-text-style', ['data-slideshow-text-style' => 'italic']) .
        $previewtoolbarbutton('fa-underline', get_string('slideshowtextunderline', 'local_course_banner_builder'), 'local-course-banner-builder-toggle-selected-slideshow-text-style', ['data-slideshow-text-style' => 'underline']) .
        $previewtoolbarbutton('fa-strikethrough', get_string('slideshowtextstrike', 'local_course_banner_builder'), 'local-course-banner-builder-toggle-selected-slideshow-text-style', ['data-slideshow-text-style' => 'strike']) .
        $previewtoolbarbutton('fa-redo', get_string('redopreviewchange', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-preview-redo', ['disabled' => 'disabled']),
        'local-course-banner-builder-source-preview-visibility-toggle-row local-course-banner-builder-slideshow-preview-toolbar'
    );
    $modalbody = html_writer::div(
        html_writer::div(get_string('slideshowpreviewcontext', 'local_course_banner_builder', $contextlabel),
            'text-muted local-course-banner-builder-admin-small-text mb-2') .
        $modalpreview,
        'local-course-banner-builder-slideshow-modal-preview'
    );

    $controls = html_writer::start_div('local-course-banner-builder-slideshow-overlay-controls');
    $controls .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'titlex', 'value' => number_format($titlex, 3, '.', ''), 'data-slideshow-position-input' => 'title-x', 'data-default-value' => number_format(manager::SLIDESHOW_DEFAULT_TITLE_X, 3, '.', '')]);
    $controls .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'titley', 'value' => number_format($titley, 3, '.', ''), 'data-slideshow-position-input' => 'title-y', 'data-default-value' => number_format(manager::SLIDESHOW_DEFAULT_TITLE_Y, 3, '.', '')]);
    $controls .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'bodyx', 'value' => number_format($bodyx, 3, '.', ''), 'data-slideshow-position-input' => 'body-x', 'data-default-value' => number_format(manager::SLIDESHOW_DEFAULT_BODY_X, 3, '.', '')]);
    $controls .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'bodyy', 'value' => number_format($bodyy, 3, '.', ''), 'data-slideshow-position-input' => 'body-y', 'data-default-value' => number_format(manager::SLIDESHOW_DEFAULT_BODY_Y, 3, '.', '')]);
    $controls .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'actionx', 'value' => number_format($actionx, 3, '.', ''), 'data-slideshow-position-input' => 'action-x', 'data-default-value' => number_format(manager::SLIDESHOW_DEFAULT_ACTION_X, 3, '.', '')]);
    $controls .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'actiony', 'value' => number_format($actiony, 3, '.', ''), 'data-slideshow-position-input' => 'action-y', 'data-default-value' => number_format(manager::SLIDESHOW_DEFAULT_ACTION_Y, 3, '.', '')]);
    $controls .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'labelx', 'value' => number_format($labelx, 3, '.', ''), 'data-slideshow-position-input' => 'label-x', 'data-default-value' => number_format(manager::SLIDESHOW_DEFAULT_LABEL_X, 3, '.', '')]);
    $controls .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'labely', 'value' => number_format($labely, 3, '.', ''), 'data-slideshow-position-input' => 'label-y', 'data-default-value' => number_format(manager::SLIDESHOW_DEFAULT_LABEL_Y, 3, '.', '')]);
    $controls .= html_writer::tag('label', get_string('slideshowoverlaycolor', 'local_course_banner_builder'), [
        'for' => $colorid,
        'class' => 'local-course-banner-builder-slideshow-overlay-color-label',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'color',
        'id' => $colorid,
        'name' => 'overlaycolor',
        'value' => $color,
        'class' => 'form-control local-course-banner-builder-slideshow-color-input local-course-banner-builder-slideshow-overlay-color-input',
        'data-slideshow-color-input' => '1',
        'data-hex-target' => '#slideshow-overlay-hex-' . $colorid,
        'data-slideshow-overlay-color' => '1',
        'data-default-value' => manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR,
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => 'slideshow-overlay-hex-' . $colorid,
        'value' => $color,
        'class' => 'form-control local-course-banner-builder-slideshow-hex-input local-course-banner-builder-slideshow-overlay-color-hex',
        'data-slideshow-hex-input' => '1',
        'aria-label' => get_string('slideshowoverlaycolor', 'local_course_banner_builder'),
    ]);
    $controls .= html_writer::tag('label', get_string('slideshowoverlayopacity', 'local_course_banner_builder'), [
        'for' => $opacityid,
        'class' => 'local-course-banner-builder-slideshow-overlay-opacity-label',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'range',
        'id' => $opacityid,
        'name' => 'overlayopacity',
        'min' => 0,
        'max' => 85,
        'step' => 1,
        'value' => $opacity,
        'class' => 'custom-range local-course-banner-builder-range local-course-banner-builder-slideshow-overlay-opacity-input',
        'data-slideshow-overlay-opacity' => '1',
        'data-default-value' => (string)(int)(manager::SLIDESHOW_DEFAULT_OVERLAY_OPACITY * 100),
    ]);
    $controls .= html_writer::div($opacity . '%', 'text-muted local-course-banner-builder-slideshow-opacity-output local-course-banner-builder-slideshow-overlay-opacity-output', [
        'data-slideshow-overlay-opacity-output' => '1',
    ]);
    $titlecolorid = 'slideshow-title-color-' . uniqid();
    $titlehexid = 'slideshow-title-hex-' . uniqid();
    $bodycolorid = 'slideshow-body-color-' . uniqid();
    $bodyhexid = 'slideshow-body-hex-' . uniqid();
    $controls .= html_writer::tag('h5', get_string('slideshowtextappearance', 'local_course_banner_builder'), [
        'class' => 'h6 mt-3 mb-1',
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
    $controls .= html_writer::div(get_string('slideshowviewbuttonheight', 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-text-title');
    $controls .= html_writer::empty_tag('input', [
        'type' => 'range',
        'name' => 'actionheight',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $actionheight,
        'class' => 'custom-range local-course-banner-builder-range',
        'data-slideshow-text-var' => '--local-course-banner-builder-slideshow-action-height',
        'data-slideshow-text-size' => 'actionheight',
        'data-default-value' => (string)manager::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT,
    ]);
    $controls .= html_writer::div($actionheight . '%', 'text-muted local-course-banner-builder-slideshow-opacity-output', [
        'data-slideshow-text-output-for' => 'actionheight',
    ]);
    $controls .= html_writer::empty_tag('input', [
        'type' => 'number',
        'min' => 25,
        'max' => 100,
        'step' => 1,
        'value' => $actionheight,
        'class' => 'form-control local-course-banner-builder-slideshow-size-number',
        'data-slideshow-size-number-for' => 'actionheight',
        'aria-label' => get_string('slideshowviewbuttonheight', 'local_course_banner_builder'),
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
    foreach (manager::get_default_slideshow_label_colors() as $type => $defaults) {
        $colors = $labelcolors[$type] ?? $defaults;
        $labelkey = $type === 'forums' ? 'slideshow:type:courseforum' :
            ($type === 'siteannouncements' ? 'slideshow:type:siteannouncements' :
                ($type === 'assignments' ? 'slideshow:type:assignment' : 'slideshow:type:quiz'));
        $backgroundid = 'slideshow-label-' . $type . '-background-' . uniqid();
        $textid = 'slideshow-label-' . $type . '-text-' . uniqid();
        $backgroundhexid = 'slideshow-label-' . $type . '-background-hex-' . uniqid();
        $texthexid = 'slideshow-label-' . $type . '-text-hex-' . uniqid();
        $sampleclass = 'local-course-banner-builder-slideshow-label local-course-banner-builder-slideshow-label--' .
            ($type === 'forums' ? 'forums' : $type);
        $samplestyle = '--local-course-banner-builder-slideshow-label-' . $type . '-bg: ' .
            s((string)($colors['background'] ?? $defaults['background'])) . ';' .
            '--local-course-banner-builder-slideshow-label-' . $type . '-color: ' .
            s((string)($colors['text'] ?? $defaults['text'])) . ';';
        $controls .= html_writer::start_div('local-course-banner-builder-slideshow-label-color-row');
        $controls .= html_writer::div(get_string($labelkey, 'local_course_banner_builder'), 'local-course-banner-builder-slideshow-label-color-title');
        $controls .= html_writer::tag('label', get_string('slideshowlabelbackground', 'local_course_banner_builder'), [
            'for' => $backgroundid,
            'class' => 'sr-only',
        ]);
        $controls .= html_writer::empty_tag('input', [
            'type' => 'color',
            'id' => $backgroundid,
            'name' => 'label_' . $type . '_background',
            'value' => (string)($colors['background'] ?? $defaults['background']),
            'class' => 'form-control local-course-banner-builder-slideshow-color-input',
            'data-slideshow-color-input' => '1',
            'data-hex-target' => '#' . $backgroundhexid,
            'data-slideshow-label-var' => '--local-course-banner-builder-slideshow-label-' . $type . '-bg',
            'data-default-value' => (string)$defaults['background'],
        ]);
        $controls .= html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => $backgroundhexid,
            'value' => (string)($colors['background'] ?? $defaults['background']),
            'class' => 'form-control local-course-banner-builder-slideshow-hex-input',
            'aria-label' => get_string('slideshowlabelbackground', 'local_course_banner_builder'),
        ]);
        $controls .= html_writer::tag('label', get_string('slideshowlabeltext', 'local_course_banner_builder'), [
            'for' => $textid,
            'class' => 'sr-only',
        ]);
        $controls .= html_writer::empty_tag('input', [
            'type' => 'color',
            'id' => $textid,
            'name' => 'label_' . $type . '_text',
            'value' => (string)($colors['text'] ?? $defaults['text']),
            'class' => 'form-control local-course-banner-builder-slideshow-color-input',
            'data-slideshow-color-input' => '1',
            'data-hex-target' => '#' . $texthexid,
            'data-slideshow-label-var' => '--local-course-banner-builder-slideshow-label-' . $type . '-color',
            'data-default-value' => (string)$defaults['text'],
        ]);
        $controls .= html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => $texthexid,
            'value' => (string)($colors['text'] ?? $defaults['text']),
            'class' => 'form-control local-course-banner-builder-slideshow-hex-input',
            'aria-label' => get_string('slideshowlabeltext', 'local_course_banner_builder'),
        ]);
        $controls .= html_writer::span(
            get_string($labelkey, 'local_course_banner_builder'),
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
    $modal .= html_writer::div($modalbody, 'modal-body');
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
