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
 * Manage banner images.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// phpcs:disable moodle.Files.LineLength.TooLong -- This admin page contains large inline JS selectors and UI templates.

/**
 * Render one layer modal.
 *
 * @param string $modalid
 * @param string $title
 * @param callable|null $bodyrenderer
 * @param array $attributes
 * @return void
 */
function local_course_banner_builder_render_layer_modal(
    string $modalid,
    string $title,
    ?callable $bodyrenderer = null,
    array $attributes = []
): void {
    echo html_writer::start_div('modal fade', [
        'id' => $modalid,
        'tabindex' => '-1',
        'role' => 'dialog',
        'aria-labelledby' => $modalid . '-title',
        'aria-hidden' => 'true',
    ] + $attributes);
    echo html_writer::start_div('modal-dialog modal-xl local-course-banner-builder-layer-modal-dialog', ['role' => 'document']);
    echo html_writer::start_div('modal-content local-course-banner-builder-layer-modal-content');
    echo html_writer::start_div('modal-header d-flex align-items-center');
    echo html_writer::tag('h5', $title, [
        'class' => 'modal-title flex-grow-1',
        'id' => $modalid . '-title',
    ]);
    echo html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
        'type' => 'button',
        'class' => 'close ml-auto ms-auto',
        'data-dismiss' => 'modal',
        'data-bs-dismiss' => 'modal',
        'aria-label' => get_string('closebuttontitle'),
    ]);
    echo html_writer::end_div();
    echo html_writer::start_div('modal-body local-course-banner-builder-layer-modal-body');
    if ($bodyrenderer) {
        $bodyrenderer();
    }
    echo html_writer::end_div();
    echo html_writer::div('', 'modal-footer local-course-banner-builder-slideshow-modal-footer ' .
        'local-course-banner-builder-layer-modal-footer', [
            'data-layer-modal-footer' => '1',
        ]);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

/**
 * Render banner title settings modal.
 *
 * @param string $context
 * @param string $title
 * @param string $formaction
 * @return string
 */
function local_course_banner_builder_render_title_settings_modal(
    string $context,
    string $title,
    string $formaction
): string {
    $prefix = 'bannertitle_' . $context . '_';
    $modalid = 'local-course-banner-builder-title-settings-' . $context . '-modal';
    $getconfig = function(string $name, $default) use ($prefix) {
        $value = get_config('local_course_banner_builder', $prefix . $name);
        return $value === false || $value === null || $value === '' ? $default : $value;
    };
    $hex = function($value, string $default = '#FFFFFF'): string {
        $value = (string)$value;
        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtoupper($value) : $default;
    };
    $bool = function(string $name, bool $default = false) use ($getconfig): bool {
        return (bool)$getconfig($name, $default ? 1 : 0);
    };
    $num = function(string $name, float $default, float $min, float $max) use ($getconfig): float {
        return max($min, min($max, (float)$getconfig($name, $default)));
    };

    $values = [
        'enabled' => $bool('enabled'),
        'x' => $num('x', 50, 0, 100),
        'y' => $num('y', 50, 0, 100),
        'fontsize' => $num('fontsize', 100, 25, 160),
        'lineheight' => $num('lineheight', 105, 80, 180),
        'fontfamily' => (string)$getconfig('fontfamily', ''),
        'color' => $hex($getconfig('color', '#FFFFFF')),
        'align' => (string)$getconfig('align', 'center'),
        'bold' => $bool('bold', true),
        'italic' => $bool('italic'),
        'underline' => $bool('underline'),
        'allcaps' => $bool('allcaps'),
        'aboveborder' => $bool('aboveborder', true),
        'frameenabled' => $bool('frameenabled'),
        'frametype' => (string)$getconfig('frametype', 'box'),
        'framecolor' => $hex($getconfig('framecolor', '#000000'), '#000000'),
        'frameopacity' => $num('frameopacity', 35, 0, 100),
        'framebordercolor' => $hex($getconfig('framebordercolor', '#FFFFFF')),
        'frameborderwidth' => $num('frameborderwidth', 0, 0, 10),
        'frameradius' => $num('frameradius', 12, 0, 80),
        'framepadding' => $num('framepadding', 18, 0, 80),
        'frameshadowenabled' => $bool('frameshadowenabled'),
        'frameshadowcolor' => $hex($getconfig('frameshadowcolor', '#000000'), '#000000'),
        'frameshadowopacity' => $num('frameshadowopacity', 25, 0, 100),
        'frameshadowblur' => $num('frameshadowblur', 14, 0, 80),
        'frameshadowdistance' => $num('frameshadowdistance', 6, 0, 50),
        'frameshadowdirection' => $num('frameshadowdirection', 135, 0, 360),
        'shadowenabled' => $bool('shadowenabled', true),
        'shadowcolor' => $hex($getconfig('shadowcolor', '#000000'), '#000000'),
        'shadowopacity' => $num('shadowopacity', 55, 0, 100),
        'shadowblur' => $num('shadowblur', 10, 0, 60),
        'shadowdistance' => $num('shadowdistance', 4, 0, 40),
        'shadowdirection' => $num('shadowdirection', 135, 0, 360),
        'overlayenabled' => $bool('overlayenabled'),
        'overlaycolor' => $hex($getconfig('overlaycolor', '#000000'), '#000000'),
        'overlayopacity' => $num('overlayopacity', 25, 0, 100),
        'activitytitlemode' => (string)$getconfig('activitytitlemode', 'activity'),
        'stylemode' => (string)$getconfig('stylemode', 'custom'),
    ];
    $values['frametype'] = in_array($values['frametype'], ['box', 'highlight'], true) ? $values['frametype'] : 'box';
    $values['stylemode'] = in_array($values['stylemode'], ['site', 'course', 'activity', 'custom'], true)
        ? $values['stylemode']
        : 'custom';
    $activitytitlemodes = ['activity', 'course', 'both', 'none'];
    $values['activitytitlemode'] = in_array($values['activitytitlemode'], $activitytitlemodes, true)
        ? $values['activitytitlemode']
        : 'activity';
    $defaulttitlestate = [
        'x' => '50',
        'y' => '50',
        'fontsize' => '100',
        'lineheight' => '105',
        'fontfamily' => '',
        'color' => '#FFFFFF',
        'bold' => '1',
        'italic' => '0',
        'underline' => '0',
        'allcaps' => '0',
        'aboveborder' => '1',
        'frameenabled' => '0',
        'frametype' => 'box',
        'framecolor' => '#000000',
        'frameopacity' => '35',
        'framebordercolor' => '#FFFFFF',
        'frameborderwidth' => '0',
        'frameradius' => '12',
        'framepadding' => '18',
        'frameshadowenabled' => '0',
        'frameshadowcolor' => '#000000',
        'frameshadowopacity' => '25',
        'frameshadowblur' => '14',
        'frameshadowdistance' => '6',
        'frameshadowdirection' => '135',
        'shadowenabled' => '1',
        'shadowcolor' => '#000000',
        'shadowopacity' => '55',
        'shadowblur' => '10',
        'shadowdistance' => '4',
        'shadowdirection' => '135',
        'overlayenabled' => '0',
        'overlaycolor' => '#000000',
        'overlayopacity' => '25',
        'stylemode' => 'custom',
    ];
    $fontoptions = \local_course_banner_builder\manager::get_slideshow_font_family_options();
    $inheritfields = [
        'x',
        'y',
        'fontsize',
        'lineheight',
        'fontfamily',
        'color',
        'bold',
        'italic',
        'underline',
        'allcaps',
        'aboveborder',
        'frameenabled',
        'frametype',
        'framecolor',
        'frameopacity',
        'framebordercolor',
        'frameborderwidth',
        'frameradius',
        'framepadding',
        'frameshadowenabled',
        'frameshadowcolor',
        'frameshadowopacity',
        'frameshadowblur',
        'frameshadowdistance',
        'frameshadowdirection',
        'shadowenabled',
        'shadowcolor',
        'shadowopacity',
        'shadowblur',
        'shadowdistance',
        'shadowdirection',
        'overlayenabled',
        'overlaycolor',
        'overlayopacity',
    ];
    $readinheritstate = function(string $sourcecontext) use ($inheritfields, $defaulttitlestate, $hex, $fontoptions): array {
        $sourceprefix = 'bannertitle_' . $sourcecontext . '_';
        $state = [];
        foreach ($inheritfields as $fieldname) {
            $value = get_config('local_course_banner_builder', $sourceprefix . $fieldname);
            if ($value === false || $value === null || $value === '') {
                $value = $defaulttitlestate[$fieldname] ?? '';
            }
            if (in_array($fieldname, ['color', 'framecolor', 'framebordercolor', 'frameshadowcolor', 'shadowcolor', 'overlaycolor'], true)) {
                $value = $hex($value, $defaulttitlestate[$fieldname] ?? '#FFFFFF');
            } else if ($fieldname === 'fontfamily' && !array_key_exists((string)$value, $fontoptions)) {
                $value = '';
            } else if ($fieldname === 'frametype' && !in_array((string)$value, ['box', 'highlight'], true)) {
                $value = 'box';
            }
            $state[$fieldname] = (string)$value;
        }
        return $state;
    };
    $inheritstates = [
        'site' => $readinheritstate('site'),
        'course' => $readinheritstate('course'),
        'activity' => $readinheritstate('activity'),
    ];
    $bannerformat = \local_course_banner_builder\manager::normalise_banner_format(
        $context === 'site'
            ? \local_course_banner_builder\manager::get_site_banner_format()
            : \local_course_banner_builder\manager::get_course_banner_format()
    );
    $formatclass = 'local-course-banner-builder-slideshow-admin-preview--format-' .
        preg_replace('/[^a-z0-9_-]+/i', '', $bannerformat);

    $select = html_writer::start_tag('select', [
        'name' => 'fontfamily',
        'class' => 'custom-select local-course-banner-builder-font-family-select',
        'style' => 'font-weight: 700;',
    ]);
    foreach ($fontoptions as $value => $label) {
        $select .= html_writer::tag('option', s($label), [
            'value' => $value,
            'selected' => $value === $values['fontfamily'] ? 'selected' : null,
            'style' => ($value !== '' ? 'font-family: ' . s($value) . '; ' : '') . 'font-weight: 700;',
        ]);
    }
    $select .= html_writer::end_tag('select');

    $activitytitlemodeoptions = '';
    foreach ($activitytitlemodes as $option) {
        $activitytitlemodeoptions .= html_writer::tag(
            'option',
            get_string('bannertitleactivitymode:' . $option, 'local_course_banner_builder'),
            [
                'value' => $option,
                'selected' => $option === $values['activitytitlemode'] ? 'selected' : null,
            ]
        );
    }

    $field = function(string $label, string $control, string $class = 'mb-3'): string {
        return html_writer::div(
            html_writer::tag('label', $label, ['class' => 'form-label']) . $control,
            $class
        );
    };
    $checkbox = function(string $name, string $label, bool $checked) use ($modalid): string {
        $id = $modalid . '-' . $name;
        return html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'name' => $name,
                'value' => 1,
                'class' => 'form-check-input',
                'id' => $id,
                'checked' => $checked ? 'checked' : null,
                'data-title-control' => $name,
            ]) .
            html_writer::tag('label', $label, ['class' => 'form-check-label', 'for' => $id]),
            'form-check mb-3'
        );
    };
    $togglebutton = function(
        string $name,
        string $label,
        bool $checked,
        ?string $labelon = null,
        ?string $labeloff = null
    ) use ($modalid): string {
        $id = $modalid . '-' . $name;
        $labelon = $labelon ?? get_string('enabled', 'local_course_banner_builder');
        $labeloff = $labeloff ?? get_string('disabled', 'local_course_banner_builder');
        $button = html_writer::tag('button', html_writer::tag('i', '', [
            'class' => 'fa ' . ($checked ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
            'aria-hidden' => 'true',
        ]) . html_writer::span($checked ? $labelon : $labeloff), [
            'type' => 'button',
            'class' => 'btn local-course-banner-builder-slideshow-enable-button ' .
                ($checked ? 'btn-primary' : 'btn-outline-secondary'),
            'data-local-title-toggle-button' => '1',
            'data-target-input' => '#' . $id,
            'data-label-on' => $labelon,
            'data-label-off' => $labeloff,
            'aria-pressed' => $checked ? 'true' : 'false',
        ]);
        return html_writer::div(
            html_writer::div($label, 'local-course-banner-builder-slideshow-text-title') .
            html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $name,
                'value' => $checked ? 1 : 0,
                'id' => $id,
                'data-title-control' => $name,
            ]) .
            html_writer::div($button, 'local-course-banner-builder-slideshow-toggle-button-row'),
            'mb-3'
        );
    };
    $slider = function(string $name, string $label, float $value, float $min, float $max, float $step = 1) use ($field): string {
        $unit = in_array(
            $name,
            ['fontsize', 'lineheight', 'x', 'y', 'frameopacity', 'frameshadowopacity', 'shadowopacity', 'overlayopacity']
        )
            ? '%'
            : (strpos($name, 'direction') !== false ? 'deg' : 'px');
        $control = html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'range',
                'name' => $name . '_slider',
                'value' => $value,
                'min' => $min,
                'max' => $max,
                'step' => $step,
                'class' => 'local-course-banner-builder-range theme-easyedu-range',
                'data-title-range-for' => $name,
            ]) .
            html_writer::tag('output', $value . $unit, [
                'class' => 'text-muted local-course-banner-builder-title-slider-output',
                'data-title-output-for' => $name,
            ]) .
            html_writer::empty_tag('input', [
                'type' => 'number',
                'name' => $name,
                'value' => $value,
                'min' => $min,
                'max' => $max,
                'step' => $step,
                'class' => 'form-control form-control-sm',
                'data-title-number-for' => $name,
                'data-title-control' => $name,
                'data-title-output-unit' => $unit,
            ]),
            'local-course-banner-builder-title-slider'
        );
        return $field($label, $control);
    };
    $color = function(string $name, string $label, string $value) use ($field): string {
        $control = html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'color',
                'name' => $name . '_picker',
                'value' => $value,
                'class' => 'form-control form-control-color local-course-banner-builder-color-input',
                'data-title-color-picker-for' => $name,
            ]) .
            html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => $name,
                'value' => $value,
                'class' => 'form-control',
                'pattern' => '^#[0-9A-Fa-f]{6}$',
                'data-title-color-text-for' => $name,
                'data-title-control' => $name,
            ]),
            'local-course-banner-builder-title-color-field'
        );
        return $field($label, $control);
    };
    $styleinput = function(string $name, bool $active) use ($values): string {
        return html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => !empty($values[$name]) ? 1 : 0,
            'data-title-style-input' => $name,
            'data-title-control' => $name,
        ]);
    };
    $stylebutton = function(string $name, string $icon, string $label) use ($values): string {
        $active = !empty($values[$name]);
        return html_writer::tag('button', html_writer::tag('i', '', [
            'class' => 'fa ' . $icon,
            'aria-hidden' => 'true',
        ]) . html_writer::span($label, 'sr-only'), [
            'type' => 'button',
            'class' => 'btn ' . ($active ? 'btn-primary active' : 'btn-outline-secondary') .
                ' local-course-banner-builder-source-preview-visibility-toggle local-course-banner-builder-title-toolbar-button',
            'data-action' => 'local-course-banner-builder-toggle-title-style',
            'data-title-style' => $name,
            'data-toggle' => 'popover',
            'data-trigger' => 'hover',
            'data-placement' => 'top',
            'data-html' => 'true',
            'data-content' => '<div class="no-overflow"><p>' . s($label) . '</p></div>',
            'data-local-course-banner-builder-popover-label' => $label,
            'aria-label' => $label,
            'aria-pressed' => $active ? 'true' : 'false',
        ]);
    };
    $resizehandles = static function(): string {
        $html = '';
        foreach (['top', 'right', 'bottom', 'left'] as $edge) {
            $html .= html_writer::span(
                '',
                'local-course-banner-builder-preview-resize-handle ' .
                    'local-course-banner-builder-preview-resize-handle--' . $edge,
                [
                    'data-title-preview-resize-handle' => $edge,
                    'aria-hidden' => 'true',
                ]
            );
        }
        return $html;
    };
    $previewtoolbarbutton = static function(string $iconclass, string $label, string $action, array $extra = []): string {
        $buttonclass = $extra['class'] ??
            'btn btn-outline-secondary local-course-banner-builder-source-preview-visibility-toggle';
        unset($extra['class']);
        return html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa ' . $iconclass, 'aria-hidden' => 'true']) .
                html_writer::span($label, 'sr-only'),
            [
                'type' => 'button',
                'class' => $buttonclass,
                'data-action' => $action,
                'data-toggle' => 'popover',
                'data-trigger' => 'hover',
                'data-placement' => 'top',
                'data-html' => 'true',
                'data-content' => '<div class="no-overflow"><p>' . s($label) . '</p></div>',
                'data-local-course-banner-builder-popover-label' => $label,
                'aria-label' => $label,
            ] + $extra
        );
    };
    $previewcourse = get_string('previewcoursetitlelong', 'local_course_banner_builder');
    $previewsite = get_string('previewsitetitlelong', 'local_course_banner_builder');
    $previewactivity = $context === 'site' ? $previewsite :
        get_string('previewactivitytitlelong', 'local_course_banner_builder');
    $previewcourse = str_replace(' / ', "\n", $previewcourse);
    $previewsite = str_replace(' / ', "\n", $previewsite);
    $previewactivity = str_replace(' / ', "\n", $previewactivity);
    $previewtitle = $context === 'site' ? $previewsite : ($context === 'activity'
        ? $previewactivity
        : $previewcourse);
    $preview = html_writer::div(
        html_writer::div('', 'local-course-banner-builder-slideshow-admin-preview-backdrop') .
        html_writer::div('', 'local-course-banner-builder-slideshow-admin-preview-overlay') .
        html_writer::div('', 'local-course-banner-builder-title-preview-overlay', [
            'data-title-preview-overlay' => '1',
        ]) .
        html_writer::div(
            html_writer::span(s($previewtitle), '', ['data-title-preview-label' => '1']) . $resizehandles(),
            'local-course-banner-builder-banner-title-overlay local-course-banner-builder-title-preview-text',
            [
            'data-title-preview-text' => '1',
            'data-title-preview-draggable' => '1',
            'data-title-preview-course' => $previewcourse,
            'data-title-preview-activity' => $previewactivity,
            ]
        ),
        'local-course-banner-builder-slideshow-admin-preview local-course-banner-builder-title-preview-frame ' .
            $formatclass,
        [
            'data-title-preview-frame' => '1',
            'data-banner-format' => $bannerformat,
        ]
    );
    $toolbar = html_writer::div(
        $previewtoolbarbutton('fa-undo', get_string('undopreviewchange', 'local_course_banner_builder'),
            'local-course-banner-builder-title-preview-undo', ['disabled' => 'disabled']) .
        $previewtoolbarbutton('fa-magnet', get_string('togglepreviewsnap', 'local_course_banner_builder'),
            'local-course-banner-builder-toggle-title-preview-snap', [
                'class' => 'btn btn-primary local-course-banner-builder-source-preview-visibility-toggle',
                'aria-pressed' => 'true',
            ]) .
        $previewtoolbarbutton('fa-crosshairs', get_string('recenterpreviewelement', 'local_course_banner_builder'),
            'local-course-banner-builder-title-preview-recenter') .
        $stylebutton('bold', 'fa-bold', get_string('bannertitlebold', 'local_course_banner_builder')) .
        $stylebutton('italic', 'fa-italic', get_string('bannertitleitalic', 'local_course_banner_builder')) .
        $stylebutton('underline', 'fa-underline', get_string('bannertitleunderline', 'local_course_banner_builder')) .
        $stylebutton('allcaps', 'fa-font', get_string('bannertitleallcaps', 'local_course_banner_builder')) .
        $stylebutton('aboveborder', 'fa-layer-group',
            get_string('bannertitleaboveborder', 'local_course_banner_builder')) .
        $previewtoolbarbutton('fa-redo', get_string('redopreviewchange', 'local_course_banner_builder'),
            'local-course-banner-builder-title-preview-redo', ['disabled' => 'disabled']),
        'local-course-banner-builder-source-preview-visibility-toggle-row local-course-banner-builder-slideshow-preview-toolbar local-course-banner-builder-title-preview-toolbar'
    );
    $hiddenstyles = $styleinput('bold', true) .
        $styleinput('italic', false) .
        $styleinput('underline', false) .
        $styleinput('allcaps', false) .
        $styleinput('aboveborder', true);
    $titlepanel = static function(string $key, string $content): string {
        return html_writer::div(
            $content,
            'local-course-banner-builder-preview-opacity-panel local-course-banner-builder-title-side-panel is-collapsed',
            [
                'data-title-side-panel' => $key,
                'hidden' => 'hidden',
            ]
        );
    };
    $titlepanelbutton = static function(string $key, string $icon, string $label): string {
        return html_writer::tag('button', html_writer::tag('i', '', [
            'class' => 'fa ' . $icon . ' me-2',
            'aria-hidden' => 'true',
        ]) . html_writer::span($label), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-title-side-panel',
            'data-title-side-panel-target' => $key,
            'aria-expanded' => 'false',
        ]);
    };
    $titlecornerswitch = static function(float $radius): string {
        $html = html_writer::div(get_string('slideshowtogglecorners', 'local_course_banner_builder'),
            'local-course-banner-builder-title-slider-label');
        $html .= html_writer::start_div('btn-group local-course-banner-builder-title-corner-buttons', ['role' => 'group']);
        foreach ([
            'rounded' => ['slideshowcornersrounded', 18],
            'square' => ['slideshowcornerssquare', 0],
        ] as $value => $options) {
            $active = ($value === 'rounded' && $radius > 0) || ($value === 'square' && $radius <= 0);
            $html .= html_writer::tag('button', get_string($options[0], 'local_course_banner_builder'), [
                'type' => 'button',
                'class' => 'btn btn-sm ' . ($active ? 'btn-primary active' : 'btn-outline-secondary'),
                'data-action' => 'local-course-banner-builder-set-title-frame-corners',
                'data-title-frame-radius' => (string)$options[1],
            ]);
        }
        return $html . html_writer::end_div();
    };
    $frametypeswitch = function(string $current): string {
        $html = html_writer::div(
            get_string('bannertitleframetype', 'local_course_banner_builder'),
            'local-course-banner-builder-title-slider-label'
        );
        $html .= html_writer::start_div('btn-group local-course-banner-builder-title-frame-type-buttons', [
            'role' => 'group',
        ]);
        foreach ([
            'box' => 'bannertitleframetype:box',
            'highlight' => 'bannertitleframetype:highlight',
        ] as $value => $stringkey) {
            $active = $value === $current;
            $html .= html_writer::tag('button', get_string($stringkey, 'local_course_banner_builder'), [
                'type' => 'button',
                'class' => 'btn btn-sm ' . ($active ? 'btn-primary active' : 'btn-outline-secondary'),
                'data-action' => 'local-course-banner-builder-set-title-frame-type',
                'data-title-frame-type' => $value,
                'aria-pressed' => $active ? 'true' : 'false',
            ]);
        }
        $html .= html_writer::end_div();
        $html .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'frametype',
            'value' => $current,
            'data-title-control' => 'frametype',
        ]);
        return html_writer::div($html, 'mb-3');
    };
    $inheritchoices = $context === 'course'
        ? ['site' => 'bannertitleinherit:site', 'activity' => 'bannertitleinherit:activity', 'custom' => 'bannertitleinherit:custom']
        : ($context === 'activity'
            ? ['course' => 'bannertitleinherit:course', 'site' => 'bannertitleinherit:site', 'custom' => 'bannertitleinherit:custom']
            : ['course' => 'bannertitleinherit:course', 'activity' => 'bannertitleinherit:activity', 'custom' => 'bannertitleinherit:custom']);
    $inheritchoicehtml = html_writer::div(
        get_string('bannertitleinherit', 'local_course_banner_builder'),
        'local-course-banner-builder-title-slider-label'
    );
    $inheritchoicehtml .= html_writer::start_div('btn-group local-course-banner-builder-title-inherit-buttons', [
        'role' => 'group',
        'data-title-style-mode-buttons' => '1',
    ]);
    foreach ($inheritchoices as $value => $stringkey) {
        $active = $value === $values['stylemode'];
        $inheritchoicehtml .= html_writer::tag('button', get_string($stringkey, 'local_course_banner_builder'), [
            'type' => 'button',
            'class' => 'btn btn-sm ' . ($active ? 'btn-primary active' : 'btn-outline-secondary'),
            'data-action' => 'local-course-banner-builder-apply-title-style-mode',
            'data-title-style-mode' => $value,
            'aria-pressed' => $active ? 'true' : 'false',
        ]);
    }
    $inheritchoicehtml .= html_writer::end_div();
    $inheritchoicehtml .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'stylemode',
        'value' => $values['stylemode'],
        'data-title-control' => 'stylemode',
    ]);
    $inheritchoicecontrol = html_writer::div(
        $inheritchoicehtml,
        'local-course-banner-builder-title-inherit-choice mb-2'
    );
    $titleactions = html_writer::div(
        $titlepanel(
            'frameshapes',
            $togglebutton('frameenabled', get_string('bannertitleframe', 'local_course_banner_builder'), $values['frameenabled']) .
                $frametypeswitch((string)$values['frametype']) .
                $color('framecolor', get_string('bannertitleframecolor', 'local_course_banner_builder'), $values['framecolor']) .
                $color('framebordercolor', get_string('bannertitleframebordercolor', 'local_course_banner_builder'), $values['framebordercolor']) .
                $slider('frameopacity', get_string('bannertitleframeopacity', 'local_course_banner_builder'), $values['frameopacity'], 0, 100) .
                $slider('frameborderwidth', get_string('bannertitleframeborderwidth', 'local_course_banner_builder'), $values['frameborderwidth'], 0, 10, 0.5) .
                $titlecornerswitch((float)$values['frameradius']) .
                $slider('frameradius', get_string('bannertitleframeradius', 'local_course_banner_builder'), $values['frameradius'], 0, 80) .
                $slider('framepadding', get_string('bannertitleframepadding', 'local_course_banner_builder'), $values['framepadding'], 0, 80)
        ) .
        $titlepanelbutton('frameshapes', 'fa-vector-square', get_string('bannertitleframeshape', 'local_course_banner_builder')) .
        $titlepanel(
            'frameshadow',
            $togglebutton('frameshadowenabled', get_string('bannertitleframeshadow', 'local_course_banner_builder'), $values['frameshadowenabled']) .
                html_writer::div(
                    get_string('bannertitleframeshadowrequiresframe', 'local_course_banner_builder'),
                    'form-text text-muted local-course-banner-builder-title-frame-shadow-note',
                    ['data-title-frame-shadow-note' => '1']
                ) .
                $color('frameshadowcolor', get_string('bannertitleframeshadowcolor', 'local_course_banner_builder'), $values['frameshadowcolor']) .
                $slider('frameshadowopacity', get_string('bannertitleframeshadowopacity', 'local_course_banner_builder'), $values['frameshadowopacity'], 0, 100) .
                $slider('frameshadowblur', get_string('bannertitleframeshadowblur', 'local_course_banner_builder'), $values['frameshadowblur'], 0, 80) .
                $slider('frameshadowdistance', get_string('bannertitleframeshadowdistance', 'local_course_banner_builder'), $values['frameshadowdistance'], 0, 50) .
                $slider('frameshadowdirection', get_string('bannertitleframeshadowdirection', 'local_course_banner_builder'), $values['frameshadowdirection'], 0, 360)
        ) .
        $titlepanelbutton('frameshadow', 'fa-clone', get_string('bannertitleframeshadow', 'local_course_banner_builder')) .
        $titlepanel(
            'textshadow',
            $togglebutton('shadowenabled', get_string('bannertitleshadow', 'local_course_banner_builder'), $values['shadowenabled']) .
                $color('shadowcolor', get_string('bannertitleshadowcolor', 'local_course_banner_builder'), $values['shadowcolor']) .
                $slider('shadowopacity', get_string('bannertitleshadowopacity', 'local_course_banner_builder'), $values['shadowopacity'], 0, 100) .
                $slider('shadowblur', get_string('bannertitleshadowblur', 'local_course_banner_builder'), $values['shadowblur'], 0, 60) .
                $slider('shadowdistance', get_string('bannertitleshadowdistance', 'local_course_banner_builder'), $values['shadowdistance'], 0, 40) .
                $slider('shadowdirection', get_string('bannertitleshadowdirection', 'local_course_banner_builder'), $values['shadowdirection'], 0, 360)
        ) .
        $titlepanelbutton('textshadow', 'fa-moon', get_string('bannertitleshadow', 'local_course_banner_builder')) .
        $titlepanel(
            'overlay',
            $togglebutton('overlayenabled', get_string('bannertitleoverlay', 'local_course_banner_builder'), $values['overlayenabled']) .
                $color('overlaycolor', get_string('bannertitleoverlaycolor', 'local_course_banner_builder'), $values['overlaycolor']) .
                $slider('overlayopacity', get_string('bannertitleoverlayopacity', 'local_course_banner_builder'), $values['overlayopacity'], 0, 100)
        ) .
        $titlepanelbutton('overlay', 'fa-adjust', get_string('bannertitleoverlay', 'local_course_banner_builder')),
        'local-course-banner-builder-title-side-actions local-course-banner-builder-modal-preview-action-list'
    );
    $enabletitlecontrol = html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'enabled',
            'value' => $values['enabled'] ? 1 : 0,
            'id' => $modalid . '-enabled',
            'data-title-control' => 'enabled',
        ]) .
        html_writer::div(
            html_writer::tag('button', html_writer::tag('i', '', [
                'class' => 'fa ' . ($values['enabled'] ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
                'aria-hidden' => 'true',
            ]) . html_writer::span(get_string('enabletitle', 'local_course_banner_builder')), [
                'type' => 'button',
                'class' => 'btn local-course-banner-builder-slideshow-enable-button ' .
                    ($values['enabled'] ? 'btn-primary' : 'btn-outline-secondary'),
                'data-local-title-toggle-button' => '1',
                'data-target-input' => '#' . $modalid . '-enabled',
                'data-label-on' => get_string('enabletitle', 'local_course_banner_builder'),
                'data-label-off' => get_string('enabletitle', 'local_course_banner_builder'),
                'aria-pressed' => $values['enabled'] ? 'true' : 'false',
            ]),
            'local-course-banner-builder-toggle-button-host'
        ),
        'local-course-banner-builder-title-top-choice local-course-banner-builder-layer-type-choice ' .
            'local-course-banner-builder-layer-type-choice--with-enable local-course-banner-builder-layer-type-choice--no-toggle'
    );

    $body = html_writer::tag('form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatebannertitlesettings', 'value' => 1]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'bannertitlecontext', 'value' => $context]) .
        $inheritchoicecontrol .
        $enabletitlecontrol .
        html_writer::div(
            html_writer::div($preview . $toolbar, 'local-course-banner-builder-title-preview-main') . $titleactions,
            'local-course-banner-builder-title-preview-panel'
        ) .
        $hiddenstyles .
        html_writer::div(
            ($context === 'activity' ? $field(
                get_string('bannertitleactivitymode', 'local_course_banner_builder'),
                html_writer::tag('select', $activitytitlemodeoptions, [
                    'name' => 'activitytitlemode',
                    'class' => 'custom-select',
                    'data-title-control' => 'activitytitlemode',
                ])
            ) : '') .
            html_writer::div(
                $field(get_string('bannertitlefontfamily', 'local_course_banner_builder'), $select) .
                $color('color', get_string('bannertitlecolor', 'local_course_banner_builder'), $values['color']),
                'local-course-banner-builder-title-control-grid'
            ) .
            html_writer::div(
                $slider('fontsize', get_string('bannertitlesize', 'local_course_banner_builder'), $values['fontsize'], 25, 160) .
                $slider(
                    'lineheight',
                    get_string('bannertitlelineheight', 'local_course_banner_builder'),
                    $values['lineheight'],
                    80,
                    180
                ) .
                $slider('x', get_string('bannertitlex', 'local_course_banner_builder'), $values['x'], 0, 100, 0.1) .
                $slider('y', get_string('bannertitley', 'local_course_banner_builder'), $values['y'], 0, 100, 0.1),
                'local-course-banner-builder-title-control-grid'
            ),
            'local-course-banner-builder-title-panel-section'
        ) .
        html_writer::div(
            html_writer::tag('button', html_writer::tag('i', '', [
                'class' => 'fa fa-rotate-left me-2',
                'aria-hidden' => 'true',
            ]) . html_writer::span(get_string('slideshowdefaultsettings', 'local_course_banner_builder')), [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-compact-save-button ' .
                    'local-course-banner-builder-title-footer-button',
                'data-action' => 'local-course-banner-builder-reset-title-style',
                'data-default-title-state' => json_encode($defaulttitlestate),
            ]) .
            html_writer::tag('button', html_writer::tag('i', '', [
                'class' => 'fa fa-save me-2',
                'aria-hidden' => 'true',
            ]) . html_writer::span(get_string('savechanges')), [
                'type' => 'submit',
                'class' => 'btn btn-primary local-course-banner-builder-compact-save-button ' .
                    'local-course-banner-builder-title-footer-button',
            ]),
            'local-course-banner-builder-submit-actions local-course-banner-builder-title-submit-actions ' .
                'local-course-banner-builder-slideshow-modal-footer'
        ),
        [
            'method' => 'post',
            'action' => $formaction,
            'data-banner-title-editor' => '1',
            'data-title-preview-snap-enabled' => '1',
            'data-title-current-context' => $context,
            'data-title-inherit-states' => json_encode($inheritstates),
        ]
    );

    return html_writer::div(
        html_writer::div(
            html_writer::div(
                html_writer::div(
                    html_writer::tag('h5', $title, ['class' => 'modal-title flex-grow-1']) .
                    html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
                        'type' => 'button',
                        'class' => 'close ml-auto ms-auto',
                        'data-dismiss' => 'modal',
                        'data-bs-dismiss' => 'modal',
                        'aria-label' => get_string('closebuttontitle'),
                    ]),
                    'modal-header d-flex align-items-center'
                ) .
                html_writer::div($body, 'modal-body local-course-banner-builder-title-modal-body'),
                'modal-content local-course-banner-builder-layer-modal-content'
            ),
            'modal-dialog modal-xl local-course-banner-builder-layer-modal-dialog ' .
                'local-course-banner-builder-title-modal-dialog',
            ['role' => 'document']
        ),
        'modal fade local-course-banner-builder-modal local-course-banner-builder-title-settings-modal',
        ['id' => $modalid, 'tabindex' => '-1', 'role' => 'dialog', 'aria-hidden' => 'true']
    );
}

/**
 * Render one source visual editor image layer.
 *
 * @param array $layer
 * @return string
 */
function local_course_banner_builder_render_source_visual_editor_image_layer(array $layer): string {
    $isinherited = !empty($layer['isinherited']);
    $ishiddeninherited = !empty($layer['hiddeninherited']);
    $classes = 'local-course-banner-builder-preview-image-layer local-course-banner-builder-source-preview-layer' .
        (!empty($layer['enabled']) ? '' : ' local-course-banner-builder-source-preview-layer--disabled') .
        ($isinherited ? ' local-course-banner-builder-source-preview-layer--inherited' : '') .
        ($ishiddeninherited ? ' local-course-banner-builder-source-preview-layer--hidden-inherited' : '');
    $attributes = [
        'class' => $classes,
        'style' => trim((string)($layer['wrapperstyle'] ?? '') . ' z-index: ' . (int)($layer['zindex'] ?? 1) . ';'),
        'data-source-preview-layer' => '1',
        'data-source-preview-layer-id' => (string)($layer['id'] ?? 0),
        'data-source-preview-editable' => !empty($layer['editable']) ? '1' : '0',
        'data-source-preview-inherited' => $isinherited ? '1' : '0',
        'data-preview-enabled' => !empty($layer['enabled']) ? '1' : '0',
        'data-preview-fitmode' => (string)($layer['fitmode'] ?? ''),
        'data-preview-anchor' => (string)($layer['positionanchor'] ?? \local_course_banner_builder\manager::POSITION_CENTER),
        'data-preview-custom-width' => (string)($layer['customwidthpercent'] ?? 100),
        'data-preview-custom-height' => (string)($layer['customheightpercent'] ?? 100),
        'data-preview-keep-aspect' => !empty($layer['customsizekeepaspect']) ? '1' : '0',
        'data-preview-dynamic-image' => !empty($layer['dynamicimagesizeenabled']) ? '1' : '0',
        'data-preview-image-opacity' => (string)($layer['imageopacity'] ?? 100),
        'data-preview-crop-enabled' => !empty($layer['imagecropenabled']) ? '1' : '0',
        'data-preview-crop-left' => (string)($layer['imagecropleftpercent'] ?? 0),
        'data-preview-crop-top' => (string)($layer['imagecroptoppercent'] ?? 0),
        'data-preview-crop-width' => (string)($layer['imagecropwidthpercent'] ?? 100),
        'data-preview-crop-height' => (string)($layer['imagecropheightpercent'] ?? 100),
        'data-preview-offset-top' => (string)($layer['offsettoppercent'] ?? 0),
        'data-preview-offset-right' => (string)($layer['offsetrightpercent'] ?? 0),
        'data-preview-offset-bottom' => (string)($layer['offsetbottompercent'] ?? 0),
        'data-preview-offset-left' => (string)($layer['offsetleftpercent'] ?? 0),
        'data-preview-natural-width' => (string)($layer['imagewidth'] ?? 0),
        'data-preview-natural-height' => (string)($layer['imageheight'] ?? 0),
        'data-preview-current-url' => (string)($layer['url'] ?? ''),
        'data-preview-zindex' => (string)($layer['zindex'] ?? 1),
        'data-preview-sortorder' => (string)($layer['sortorder'] ?? 0),
    ];
    if (!empty($layer['editable'])) {
        $attributes['role'] = 'button';
        $attributes['tabindex'] = '0';
    }
    if ($ishiddeninherited) {
        $attributes['aria-hidden'] = 'true';
    }

    $content = html_writer::empty_tag('img', [
        'src' => (string)($layer['url'] ?? ''),
        'alt' => '',
        'class' => 'local-course-banner-builder-preview-image',
        'style' => (string)($layer['imagestyle'] ?? ''),
        'data-preview-image-tag' => '1',
        'draggable' => 'false',
    ]);

    if (!empty($layer['editable'])) {
        $aspectlocklabel = !empty($layer['customsizekeepaspect'])
            ? get_string('allowstretchpreviewimage', 'local_course_banner_builder')
            : get_string('keepaspectpreviewimage', 'local_course_banner_builder');
        $content .= html_writer::tag('button', html_writer::tag('i', '', [
            'class' => 'fa ' . (!empty($layer['customsizekeepaspect']) ? 'fa-expand' : 'fa-link'),
            'aria-hidden' => 'true',
        ]), [
            'type' => 'button',
            'class' => 'btn btn-link p-0 icon-no-margin local-course-banner-builder-preview-aspect-lock',
            'data-action' => 'local-course-banner-builder-toggle-preview-aspect-lock',
            'data-preview-aspect-lock' => '1',
            'data-local-course-banner-builder-hover-popover' => '1',
            'data-placement' => 'top',
            'data-content' => '<div class="no-overflow"><p>' . s($aspectlocklabel) . '</p></div>',
            'data-html' => 'true',
            'aria-label' => $aspectlocklabel,
        ]);
        $content .= html_writer::span('', 'local-course-banner-builder-preview-resize-handle', [
            'data-preview-resize-handle' => '1',
            'data-preview-resize-mode' => 'corner',
            'data-preview-resize-edge' => 'bottom-right',
            'aria-hidden' => 'true',
        ]);
        foreach ([
            'top' => 'local-course-banner-builder-preview-resize-handle--top',
            'right' => 'local-course-banner-builder-preview-resize-handle--right',
            'bottom' => 'local-course-banner-builder-preview-resize-handle--bottom',
            'left' => 'local-course-banner-builder-preview-resize-handle--left',
        ] as $edge => $class) {
            $content .= html_writer::span('', 'local-course-banner-builder-preview-resize-handle ' . $class, [
                'data-preview-resize-handle' => '1',
                'data-preview-resize-mode' => 'edge',
                'data-preview-resize-edge' => $edge,
                'aria-hidden' => 'true',
            ]);
        }
    }

    return html_writer::tag('div', $content, $attributes);
}

/**
 * Render one source visual editor border layer.
 *
 * @param array $layer
 * @return string
 */
function local_course_banner_builder_render_source_visual_editor_border_layer(array $layer): string {
    $isinherited = !empty($layer['isinherited']);
    $ishiddeninherited = !empty($layer['hiddeninherited']);
    $classes = 'local-course-banner-builder-preview-border-layer local-course-banner-builder-source-preview-border' .
        (!empty($layer['enabled']) ? '' : ' local-course-banner-builder-source-preview-border--disabled') .
        ($isinherited ? ' local-course-banner-builder-source-preview-border--inherited' : '') .
        ($ishiddeninherited ? ' local-course-banner-builder-source-preview-border--hidden-inherited' : '');
    $wrapperattrs = [
        'class' => $classes,
        'style' => trim((string)($layer['wrapperstyle'] ?? '') . ' z-index: ' . (int)($layer['zindex'] ?? 1) . ';'),
        'data-source-preview-border' => '1',
        'data-source-preview-border-id' => (string)($layer['id'] ?? 0),
        'data-source-preview-inherited' => $isinherited ? '1' : '0',
        'data-preview-enabled' => !empty($layer['enabled']) ? '1' : '0',
        'data-preview-sortorder' => (string)($layer['sortorder'] ?? 0),
        'data-preview-zindex' => (string)($layer['zindex'] ?? 1),
    ];
    if ($ishiddeninherited) {
        $wrapperattrs['aria-hidden'] = 'true';
    }

    $parts = '';
    foreach (['top', 'right', 'bottom', 'left'] as $side) {
        $parts .= html_writer::span('', 'local-course-banner-builder-border-preview-side local-course-banner-builder-border-preview-side-' . $side, [
            'style' => (string)(($layer['sidestyles'] ?? [])[$side] ?? ''),
        ]);
    }
    foreach (['top-left', 'top-right', 'bottom-right', 'bottom-left'] as $corner) {
        $parts .= html_writer::span('', 'local-course-banner-builder-border-preview-corner local-course-banner-builder-border-preview-corner-' . $corner);
    }
    $parts .= html_writer::span('', 'local-course-banner-builder-border-preview-hole');

    return html_writer::tag('div', $parts, $wrapperattrs);
}

/**
 * Render one source visual editor overlay layer.
 *
 * @param array $layer
 * @return string
 */
function local_course_banner_builder_render_source_visual_editor_overlay_layer(array $layer): string {
    $isinherited = !empty($layer['isinherited']);
    $ishiddeninherited = !empty($layer['hiddeninherited']);
    $classes = 'local-course-banner-builder-banner-overlay-layer local-course-banner-builder-source-preview-overlay' .
        (!empty($layer['enabled']) ? '' : ' local-course-banner-builder-source-preview-border--disabled') .
        ($isinherited ? ' local-course-banner-builder-source-preview-border--inherited' : '') .
        ($ishiddeninherited ? ' local-course-banner-builder-source-preview-border--hidden-inherited' : '');
    $attrs = [
        'class' => $classes,
        'style' => trim((string)($layer['wrapperstyle'] ?? '') . ' z-index: ' . (int)($layer['zindex'] ?? 1) . ';'),
        'data-source-preview-overlay' => '1',
        'data-source-preview-overlay-id' => (string)($layer['id'] ?? 0),
        'data-source-preview-inherited' => $isinherited ? '1' : '0',
        'data-preview-enabled' => !empty($layer['enabled']) ? '1' : '0',
        'data-preview-sortorder' => (string)($layer['sortorder'] ?? 0),
        'data-preview-zindex' => (string)($layer['zindex'] ?? 1),
        'aria-hidden' => 'true',
    ];
    if ($ishiddeninherited) {
        $attrs['hidden'] = 'hidden';
    }

    return html_writer::tag('div', '', $attrs);
}

/**
 * Render the selected source visual editor.
 *
 * @param stdClass $source
 * @param bool $useeffectivechain
 * @return string
 */
function local_course_banner_builder_render_source_visual_editor(\stdClass $source, bool $useeffectivechain = false): string {
    $currentdefinition = \local_course_banner_builder\manager::export_source_visual_editor_definition($source);
    $chaindefinition = \local_course_banner_builder\manager::export_source_chain_visual_editor_definition($source);
    $definition = $currentdefinition;
    $hasinheritedlayers = false;
    if ($useeffectivechain) {
        $definition = $chaindefinition;
    } else {
        $definition['layers'] = $currentdefinition['layers'];
        foreach ($chaindefinition['layers'] as $layer) {
            if (empty($layer['isinherited'])) {
                continue;
            }
            $layer['editable'] = false;
            $layer['hiddeninherited'] = true;
            $definition['layers'][] = $layer;
            $hasinheritedlayers = true;
        }
        $definition['haslayers'] = !empty($definition['layers']);
    }
    $sourcesettings = \local_course_banner_builder\manager::get_source_settings($source);
    $bannerformat = \local_course_banner_builder\manager::normalise_banner_format(
        \local_course_banner_builder\manager::is_site_source($source)
            ? \local_course_banner_builder\manager::get_site_banner_format()
            : \local_course_banner_builder\manager::get_course_banner_format()
    );
    $bannerformatclass = 'local-course-banner-builder-border-preview-frame--format-' .
        preg_replace('/[^a-z0-9_-]+/i', '', $bannerformat);
    $hassourcesettings = !empty($sourcesettings->id);
    $disabledattributes = $hassourcesettings ? [] : [
        'disabled' => 'disabled',
        'aria-disabled' => 'true',
        'data-container' => 'body',
        'data-toggle' => 'popover',
        'data-placement' => 'top',
        'data-content' => '<div class="no-overflow"><p>' . s(get_string('sourcesettingsrequiredbeforelayers', 'local_course_banner_builder')) . '</p></div>',
        'data-html' => 'true',
        'data-trigger' => 'hover',
    ];

    $buttoncontent = static function(string $iconclass, string $label): string {
        return html_writer::tag('i', '', [
            'class' => 'fa ' . $iconclass . ' me-2',
            'aria-hidden' => 'true',
        ]) . html_writer::span($label);
    };
    $iconbutton = static function(string $iconclass, string $label, string $action, array $extra = []): string {
        return html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa ' . $iconclass, 'aria-hidden' => 'true']) .
                html_writer::span($label, 'sr-only'),
            [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-visibility-toggle',
                'data-action' => $action,
                'data-toggle' => 'popover',
                'data-trigger' => 'hover',
                'data-placement' => 'top',
                'data-html' => 'true',
                'data-content' => '<div class="no-overflow"><p>' . s($label) . '</p></div>',
                'aria-label' => $label,
            ] + $extra
        );
    };

    $layershtml = '';
    $haspreviewborder = false;
    $previewborderediturl = null;
    foreach ($currentdefinition['layers'] as $layer) {
        if (($layer['type'] ?? '') !== 'border' || empty($layer['id'])) {
            continue;
        }

        $editparams = [
            'elementid' => (int)$layer['id'],
            'sourcekey' => (string)$source->sourcekey,
        ];
        if (!\local_course_banner_builder\manager::is_site_source($source) && !empty($source->categoryid)) {
            $editparams['categoryid'] = (int)$source->categoryid;
        }
        $previewborderediturl = new moodle_url(
            \local_course_banner_builder\manager::is_site_source($source)
                ? '/local/course_banner_builder/admin_site.php'
                : '/local/course_banner_builder/admin_manage.php',
            $editparams
        );
        break;
    }
    foreach ($definition['layers'] as $layer) {
        if (($layer['type'] ?? '') === 'border') {
            $haspreviewborder = true;
            $layershtml .= local_course_banner_builder_render_source_visual_editor_border_layer($layer);
            continue;
        }
        if (($layer['type'] ?? '') === 'overlay') {
            $layershtml .= local_course_banner_builder_render_source_visual_editor_overlay_layer($layer);
            continue;
        }
        $layershtml .= local_course_banner_builder_render_source_visual_editor_image_layer($layer);
    }

    if (empty($definition['haslayers'])) {
        $layershtml .= html_writer::div(
            get_string('sourcevisualeditorempty', 'local_course_banner_builder'),
            'local-course-banner-builder-source-preview-empty-overlay',
            ['data-source-preview-empty' => '1']
        );
    }

    $hiddenform = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => '',
        'class' => 'local-course-banner-builder-source-preview-save-form',
        'id' => 'local-course-banner-builder-source-preview-save-form',
    ]);
    $hiddenform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $hiddenform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'value' => (int)($source->categoryid ?? 0)]);
    $hiddenform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sourcekey', 'value' => (string)$source->sourcekey]);
    $hiddenform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatepreviewlayers', 'value' => '1']);
    $hiddenform .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'previewlayerpayload',
        'value' => '',
        'data-source-preview-payload' => '1',
    ]);
    $hiddenform .= html_writer::end_tag('form');
    $borderbuttonattributes = $haspreviewborder ? [] : [
        'disabled' => 'disabled',
        'aria-disabled' => 'true',
    ];
    $deleteallbuttonattributes = !empty($currentdefinition['haslayers']) ? [] : [
        'disabled' => 'disabled',
        'aria-disabled' => 'true',
    ];
    $inheritedbuttonattributes = $hasinheritedlayers ? [] : [
        'disabled' => 'disabled',
        'aria-disabled' => 'true',
        'hidden' => 'hidden',
    ];
    $israndommode = (string)($sourcesettings->compositionmode ?? \local_course_banner_builder\manager::MODE_CUMULATIVE) ===
        \local_course_banner_builder\manager::MODE_RANDOM;
    $randomnotice = $israndommode ? html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-random', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('sourcevisualeditorrandomnotice', 'local_course_banner_builder')),
        'local-course-banner-builder-source-preview-random-notice'
    ) : '';
    $imageopacitypanel = html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'range',
            'class' => 'local-course-banner-builder-range theme-easyedu-range',
            'min' => '0',
            'max' => '100',
            'step' => '1',
            'value' => '100',
            'data-source-preview-opacity-range' => '1',
            'aria-label' => get_string('imageopacity', 'local_course_banner_builder'),
        ]) .
        html_writer::empty_tag('input', [
            'type' => 'number',
            'class' => 'form-control form-control-sm',
            'min' => '0',
            'max' => '100',
            'step' => '1',
            'value' => '100',
            'data-source-preview-opacity-number' => '1',
            'aria-label' => get_string('imageopacity', 'local_course_banner_builder'),
        ]),
        'local-course-banner-builder-preview-opacity-panel',
        ['data-source-preview-opacity-panel' => '1', 'hidden' => 'hidden']
    );
    $previewcontrols = html_writer::tag('button', $buttoncontent('fa-plus', get_string('addlayer', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'class' => implode(' ', [
                    'btn btn-outline-secondary local-course-banner-builder-dashed-action',
                    'local-course-banner-builder-source-preview-button',
                    'local-course-banner-builder-add-layer-primary',
                    'local-course-banner-builder-add-layer-primary--top',
                ]),
                'data-toggle' => 'modal',
                'data-target' => '#local-course-banner-builder-add-layer-modal',
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#local-course-banner-builder-add-layer-modal',
            ] + $disabledattributes
        ) .
        ($previewborderediturl ? html_writer::tag('button', $buttoncontent('fa-border-all', get_string('editborderlayer', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-source-preview-button',
                'data-edit-layer-url' => $previewborderediturl->out(false),
            ] + $disabledattributes
        ) : '') .
        html_writer::tag('button', $buttoncontent('fa-eye-slash', get_string('hidepreviewborder', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-preview-border',
            'data-show-label' => get_string('showpreviewborder', 'local_course_banner_builder'),
            'data-hide-label' => get_string('hidepreviewborder', 'local_course_banner_builder'),
            'data-show-icon' => 'fa-eye',
            'data-hide-icon' => 'fa-eye-slash',
            'data-preview-border-visible' => $haspreviewborder ? '1' : '0',
            'aria-pressed' => 'true',
        ] + $borderbuttonattributes) .
        html_writer::tag('button', $buttoncontent('fa-eye', get_string('showinheritedlayers', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-source-preview-inherited-layers',
            'data-show-label' => get_string('showinheritedlayers', 'local_course_banner_builder'),
            'data-hide-label' => get_string('hideinheritedlayers', 'local_course_banner_builder'),
            'data-show-icon' => 'fa-eye',
            'data-hide-icon' => 'fa-eye-slash',
            'data-inherited-visible' => '0',
            'aria-pressed' => 'false',
        ] + $inheritedbuttonattributes) .
        html_writer::tag('button', $buttoncontent('fa-link', get_string('keepaspectpreviewimage', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-source-preview-keep-aspect',
            'data-on-label' => get_string('allowstretchpreviewimage', 'local_course_banner_builder'),
            'data-off-label' => get_string('keepaspectpreviewimage', 'local_course_banner_builder'),
            'data-on-icon' => 'fa-expand',
            'data-off-icon' => 'fa-link',
            'data-keep-aspect-enabled' => '0',
        ]) .
        $imageopacitypanel .
        html_writer::tag('button', $buttoncontent('fa-adjust', get_string('toggleimageopacity', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-source-preview-opacity',
            'aria-expanded' => 'false',
        ]) .
        html_writer::tag('button', $buttoncontent('fa-level-up', get_string('pushtopreviewborder', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-source-preview-top-layer',
            'data-on-label' => get_string('keeppreviewbelowborder', 'local_course_banner_builder'),
            'data-off-label' => get_string('pushtopreviewborder', 'local_course_banner_builder'),
            'data-on-icon' => 'fa-level-down',
            'data-off-icon' => 'fa-level-up',
            'data-top-layer-enabled' => '0',
        ]);
    $previewiconactions =
        $iconbutton('fa-undo', get_string('undopreviewchange', 'local_course_banner_builder'), 'local-course-banner-builder-undo-source-preview-change', [
            'disabled' => 'disabled',
        ]) .
        $iconbutton('fa-magnet', get_string('togglepreviewsnap', 'local_course_banner_builder'), 'local-course-banner-builder-toggle-preview-snap', [
            'aria-pressed' => 'true',
            'class' => 'btn btn-primary local-course-banner-builder-source-preview-visibility-toggle',
        ]) .
        $iconbutton('fa-crosshairs', get_string('recenterpreviewimage', 'local_course_banner_builder'), 'local-course-banner-builder-recenter-source-preview-image') .
        $iconbutton('fa-bullseye', get_string('recenterallpreviewimages', 'local_course_banner_builder'), 'local-course-banner-builder-recenter-all-source-preview-images') .
        $iconbutton('fa-compress', get_string('fittopreview', 'local_course_banner_builder'), 'local-course-banner-builder-fit-source-preview-image') .
        $iconbutton('fa-arrows-alt', get_string('fillbannerpreviewimage', 'local_course_banner_builder'), 'local-course-banner-builder-fill-source-preview-image') .
        $iconbutton('fa-crop', get_string('cropimage', 'local_course_banner_builder'), 'local-course-banner-builder-toggle-source-preview-crop') .
        $iconbutton('fa-arrow-down', get_string('pushbehindpreviewlayer', 'local_course_banner_builder'), 'local-course-banner-builder-push-source-preview-layer-behind') .
        $iconbutton('fa-arrow-up', get_string('pullforwardpreviewlayer', 'local_course_banner_builder'), 'local-course-banner-builder-pull-source-preview-layer-forward') .
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-eye-slash', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('showhideallpreviewimages', 'local_course_banner_builder'), 'sr-only'),
            [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-visibility-toggle',
                'data-action' => 'local-course-banner-builder-toggle-all-source-preview-images',
                'data-toggle' => 'popover',
                'data-trigger' => 'hover',
                'data-placement' => 'top',
                'data-html' => 'true',
                'data-content' => '<div class="no-overflow"><p>' .
                    s(get_string('showhideallpreviewimages', 'local_course_banner_builder')) .
                    '</p></div>',
                'aria-label' => get_string('showhideallpreviewimages', 'local_course_banner_builder'),
                'hidden' => 'hidden',
            ]
        ) .
        $iconbutton('fa-redo', get_string('redopreviewchange', 'local_course_banner_builder'), 'local-course-banner-builder-redo-source-preview-change', [
            'disabled' => 'disabled',
        ]);
    $previewprimaryactions = html_writer::tag('button', $buttoncontent('fa-save', get_string('savepreviewchanges', 'local_course_banner_builder')), [
            'type' => 'submit',
            'class' => 'btn btn-primary local-course-banner-builder-source-preview-button',
            'form' => 'local-course-banner-builder-source-preview-save-form',
        ] + $disabledattributes) .
        html_writer::tag('button', $buttoncontent('fa-trash-can', get_string('deleteselectedlayer', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-danger local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-delete-selected-preview-layer',
            'data-confirm-message' => get_string('deleteselectedlayersconfirm', 'local_course_banner_builder'),
            'hidden' => 'hidden',
        ]) .
        html_writer::tag('button', $buttoncontent('fa-trash-can', get_string('deletealllayers', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-danger local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-delete-all-layers',
            'data-confirm-title' => get_string('confirm', 'moodle'),
            'data-confirm-message' => get_string('deletealllayersconfirm', 'local_course_banner_builder'),
        ] + $disabledattributes + $deleteallbuttonattributes);

    return html_writer::div(
        html_writer::tag('h4', get_string('sourcevisualeditor', 'local_course_banner_builder'), [
            'class' => 'h6 local-course-banner-builder-table-title mb-3',
        ]) .
        html_writer::div(
            html_writer::div(
                html_writer::div(
                    html_writer::div(
                        $layershtml,
                        'local-course-banner-builder-border-preview-frame local-course-banner-builder-border-preview-frame--moodle ' .
                            $bannerformatclass,
                        [
                            'data-source-preview-frame' => '1',
                            'data-default-fitmode' => (string)($definition['defaultfitmode'] ?? \local_course_banner_builder\manager::FIT_MODE_BANNER),
                            'data-banner-format' => $bannerformat,
                        ]
                    ),
                    'local-course-banner-builder-source-preview-surface'
                ) .
                html_writer::div(
                    $previewiconactions,
                    'local-course-banner-builder-source-preview-visibility-toggle-row',
                    [
                        'data-source-preview-visibility-toggle-row' => '1',
                        'hidden' => 'hidden',
                    ]
                ) .
                html_writer::div('', 'local-course-banner-builder-source-preview-filmstrip', [
                    'data-source-preview-filmstrip' => '1',
                    'aria-label' => get_string('bannerimage', 'local_course_banner_builder'),
                ]) .
                html_writer::div(
                    $randomnotice .
                    html_writer::div($previewprimaryactions, 'local-course-banner-builder-source-preview-primary-actions'),
                    'local-course-banner-builder-source-preview-bottom-row'
                ),
                'local-course-banner-builder-source-preview-canvas'
            ) .
            html_writer::div(
                $previewcontrols,
                'local-course-banner-builder-source-preview-controls'
            ),
            'local-course-banner-builder-source-preview-layout'
        ) .
        $hiddenform,
        'local-course-banner-builder-source-preview-panel',
        [
        'data-source-visual-editor' => '1',
        'data-sourcekey' => (string)$source->sourcekey,
        'data-has-source-settings' => $hassourcesettings ? '1' : '0',
        'data-source-preview-inherited-visible' => $useeffectivechain ? '1' : '0',
        ]
    );
}

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$sourcekey = optional_param('sourcekey', '', PARAM_RAW_TRIMMED);
$elementid = optional_param('elementid', 0, PARAM_INT);
$deleteelementid = optional_param('deleteelementid', 0, PARAM_INT);
$deletecategorycontent = optional_param('deletecategorycontent', 0, PARAM_INT);
$deletecategoryimages = optional_param('deletecategoryimages', 0, PARAM_INT);
$deletesourcecontent = optional_param('deletesourcecontent', '', PARAM_RAW_TRIMMED);
$deletesourceimages = optional_param('deletesourceimages', '', PARAM_RAW_TRIMMED);
$confirmdeletecategory = optional_param('confirmdeletecategory', 0, PARAM_BOOL);
$confirmdeleteimages = optional_param('confirmdeleteimages', 0, PARAM_BOOL);
$savecategorysettings = optional_param('savecategorysettings', 0, PARAM_BOOL);
$updatesourcesettingfield = optional_param('updatesourcesettingfield', 0, PARAM_BOOL);
$updatesourceparentfield = optional_param('updatesourceparentfield', 0, PARAM_BOOL);
$updatelayerinline = optional_param('updatelayerinline', 0, PARAM_BOOL);
$updatelayersbulk = optional_param('updatelayersbulk', 0, PARAM_BOOL);
$updatepreviewlayers = optional_param('updatepreviewlayers', 0, PARAM_BOOL);
$deleteselectedlayers = optional_param('deleteselectedlayers', 0, PARAM_BOOL);
$deleteselectedlayersajax = optional_param('deleteselectedlayersajax', 0, PARAM_BOOL);
$deletealllayersajax = optional_param('deletealllayersajax', 0, PARAM_BOOL);
$deletepreviewlayerajax = optional_param('deletepreviewlayerajax', 0, PARAM_INT);
$sourcechainpreview = optional_param('sourcechainpreview', 0, PARAM_BOOL);
$updatesitebannerenabled = optional_param('updatesitebannerenabled', 0, PARAM_BOOL);
$updatecoursebannerenabled = optional_param('updatecoursebannerenabled', 0, PARAM_BOOL);
$updatecourseactivitybanners = optional_param('updatecourseactivitybanners', 0, PARAM_BOOL);
$updatecoursedefaultimagebanners = optional_param('updatecoursedefaultimagebanners', 0, PARAM_BOOL);
$updatecoursecustomoverviewimages = optional_param('updatecoursecustomoverviewimages', 0, PARAM_BOOL);
$forcecourseoverviewreplacement = optional_param('forcecourseoverviewreplacement', 0, PARAM_BOOL);
$updatebannertitlesettings = optional_param('updatebannertitlesettings', 0, PARAM_BOOL);
$savebannerformat = optional_param('savebannerformat', 0, PARAM_BOOL);
$deleteallpluginsettings = optional_param('deleteallpluginsettings', 0, PARAM_BOOL);

$issitebanneradmin = defined('LOCAL_COURSE_BANNER_BUILDER_SITE_ADMIN') && LOCAL_COURSE_BANNER_BUILDER_SITE_ADMIN;
$adminpageid = $issitebanneradmin ? 'local_course_banner_builder_site' : 'local_course_banner_builder_manage';
$adminpagepath = $issitebanneradmin
    ? '/local/course_banner_builder/admin_site.php'
    : '/local/course_banner_builder/admin_manage.php';
$adminpagetitle = get_string($issitebanneradmin ? 'managesitebanner' : 'managebanners', 'local_course_banner_builder');
if ($issitebanneradmin) {
    $categoryid = 0;
    $sourcekey = \local_course_banner_builder\manager::SITE_SOURCE_KEY;
}

/**
 * Render the shared banner format picker modal.
 *
 * @param string $formaction
 * @param string $currentformat
 * @return void
 */
function local_course_banner_builder_render_banner_format_modal(string $formaction, string $currentformat): void {
    $formats = \local_course_banner_builder\manager::get_banner_format_options();
    $currentformat = \local_course_banner_builder\manager::normalise_banner_format($currentformat);
    $cards = '';
    foreach ($formats as $format => $label) {
        $descriptionkey = match ($format) {
            \local_course_banner_builder\manager::BANNER_FORMAT_CONTENT_WIDE => 'bannerformat:contentwide_help',
            \local_course_banner_builder\manager::BANNER_FORMAT_FULLWIDTH_TOP => 'bannerformat:fullwidthtop_help',
            \local_course_banner_builder\manager::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT => 'bannerformat:fullwidthtopcompact_help',
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

    echo html_writer::start_div('modal fade', [
        'id' => 'local-course-banner-builder-banner-format-modal',
        'tabindex' => '-1',
        'role' => 'dialog',
        'aria-labelledby' => 'local-course-banner-builder-banner-format-modal-title',
        'aria-hidden' => 'true',
    ]);
    echo html_writer::start_div('modal-dialog modal-xl', ['role' => 'document']);
    echo html_writer::start_div('modal-content');
    echo html_writer::start_div('modal-header d-flex align-items-center');
    echo html_writer::tag('h5', get_string('bannerformatmodal', 'local_course_banner_builder'), [
        'class' => 'modal-title flex-grow-1',
        'id' => 'local-course-banner-builder-banner-format-modal-title',
    ]);
    echo html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
        'type' => 'button',
        'class' => 'close ml-auto ms-auto',
        'data-dismiss' => 'modal',
        'data-bs-dismiss' => 'modal',
        'aria-label' => get_string('closebuttontitle'),
    ]);
    echo html_writer::end_div();
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $formaction,
    ]);
    echo html_writer::start_div('modal-body');
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savebannerformat', 'value' => 1]);
    echo html_writer::div($cards, 'local-course-banner-builder-format-grid');
    echo html_writer::end_div();
    echo html_writer::div(
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-save me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('savebannerformat', 'local_course_banner_builder')),
            ['type' => 'submit', 'class' => 'btn btn-primary local-course-banner-builder-source-settings-submit']
        ),
        'modal-footer local-course-banner-builder-format-modal-footer'
    );
    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

try {
    admin_externalpage_setup($adminpageid);
} catch (\Exception $e) {
    $errorcode = property_exists($e, 'errorcode') ? (string)$e->errorcode : '';
    if ($errorcode !== 'sectionerror') {
        throw $e;
    }
    require_login();
    $PAGE->set_pagelayout('admin');
}
require_capability('local/course_banner_builder:manage', context_system::instance());

$selectedsource = \local_course_banner_builder\manager::resolve_source_from_request($sourcekey, $categoryid);
$selectedsourcekey = $selectedsource ? $selectedsource->sourcekey : '';
$selectedcategoryid = ($selectedsource && $selectedsource->type === \local_course_banner_builder\manager::SOURCE_TYPE_CATEGORY) ?
    (int)$selectedsource->categoryid : 0;
$selectedcustomfieldsourcekey = ($selectedsource && $selectedsource->type === \local_course_banner_builder\manager::SOURCE_TYPE_CUSTOMFIELD) ?
    $selectedsource->sourcekey : '';
$selectedsourceparams = $selectedsourcekey !== '' ? ['sourcekey' => $selectedsourcekey] : [];
if ($selectedcategoryid) {
    $selectedsourceparams['categoryid'] = $selectedcategoryid;
}
if ($issitebanneradmin) {
    $selectedsourceparams = [];
}

$url = new moodle_url($adminpagepath, $selectedsourceparams);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title($adminpagetitle);
$PAGE->set_heading($adminpagetitle);
$PAGE->requires->css('/local/course_banner_builder/styles.css');
$PAGE->requires->strings_for_js([
    'allowstretchpreviewimage',
    'applycropimage',
    'areyousure',
    'bannerslideshow',
    'borderinnerrounded',
    'borderstyle',
    'bordersides:all',
    'childborderlayersdisableconfirm',
    'childoverlaylayersdisableconfirm',
    'customsizekeepaspect',
    'cropimage',
    'deleteselectedlayer',
    'disabledlayerthumbnail',
    'editsourcebutton',
    'enablelayer',
    'enablelayer_help',
    'fetchedmodalnotfound',
    'fillbannerpreviewimage',
    'fittopreview',
    'hideimageinpreview',
    'hideotherlayers',
    'hidepreviewborder',
    'imageaboveborder',
    'imagebelowborder',
    'imagelayeroptions',
    'imageopacity',
    'invaliddeletealllayersresponse',
    'invaliddeleteselectedlayerresponse',
    'keepaspectpreviewimage',
    'layertype:border',
    'layertype:image',
    'layertype:overlay',
    'layeroverlay',
    'overlaybannerappearance',
    'overlayborderabove',
    'overlaycolor',
    'overlayopacity',
    'overlaysettings',
    'overlayslideshowappearance',
    'overlaystylemode',
    'overlaystylemode:custom',
    'overlaystylemode:inherit',
    'overlaytarget',
    'overlaytarget:banner',
    'overlaytarget:both',
    'overlaytarget:slideshow',
    'overlaytitleabove',
    'previewcoursetitle',
    'modalbodynotfound',
    'no',
    'nextimages',
    'previewunavailable',
    'previousimages',
    'recenterpreviewimage',
    'recenterallpreviewimages',
    'redopreviewchange',
    'savebannerlayers',
    'selectlayer',
    'showhideallimages',
    'showimageinpreview',
    'showotherlayers',
    'showpreviewborder',
    'sharpinnercorners',
    'sourcealreadyhasoverlayinline',
    'summarycustomsize',
    'summarykeepaspect',
    'summaryleft',
    'summaryspacing',
    'summarytop',
    'targetmodalnotfound',
    'toggleimageopacity',
    'togglepreviewsnap',
    'unabletodeletealllayers',
    'unabletodeleteselectedlayer',
    'unabletoloadlayerform',
    'unabletoloadsourcepreview',
    'undopreviewchange',
    'unexpectedcreatemodalreturned',
    'yes',
], 'local_course_banner_builder');
$PAGE->navbar->add($adminpagetitle, new moodle_url($adminpagepath));

if ($selectedsource) {
    $PAGE->navbar->add($selectedsource->label, $url);
}

if ($deleteallpluginsettings && confirm_sesskey()) {
    \local_course_banner_builder\manager::delete_all_plugin_configuration();
    redirect(new moodle_url($adminpagepath), get_string('allpluginsettingsdeleted', 'local_course_banner_builder'));
}

if ($deletealllayersajax && confirm_sesskey() && $selectedsource) {
    \local_course_banner_builder\manager::delete_source_images($selectedsource);
    $updatedcontext = \local_course_banner_builder\manager::export_selected_source($selectedsource);
    $updatedcontext['sourcevisualeditorhtml'] = local_course_banner_builder_render_source_visual_editor($selectedsource);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $OUTPUT->render_from_template('local_course_banner_builder/admin_selected', $updatedcontext),
    ]);
    exit;
}

if ($issitebanneradmin && $updatesitebannerenabled && confirm_sesskey()) {
    set_config(
        'sitebannerenabled',
        optional_param('sitebannerenabled', 0, PARAM_BOOL) ? 1 : 0,
        'local_course_banner_builder'
    );
    redirect(new moodle_url($adminpagepath), get_string('changessaved'));
}

if (!$issitebanneradmin && $updatecoursebannerenabled && confirm_sesskey()) {
    $enabled = optional_param('coursebannerenabled', 0, PARAM_BOOL) ? 1 : 0;
    set_config('coursebannerenabled', $enabled, 'local_course_banner_builder');
    if (!$enabled) {
        set_config('coursebanneractivitiesenabled', 0, 'local_course_banner_builder');
    }
    redirect(new moodle_url($adminpagepath, $selectedsourceparams), get_string('changessaved'));
}

if (!$issitebanneradmin && $updatecourseactivitybanners && confirm_sesskey()) {
    if (!\local_course_banner_builder\manager::is_course_banner_enabled()) {
        set_config('coursebanneractivitiesenabled', 0, 'local_course_banner_builder');
        redirect(new moodle_url($adminpagepath, $selectedsourceparams), get_string('changessaved'));
    }
    set_config(
        'coursebanneractivitiesenabled',
        optional_param('coursebanneractivitiesenabled', 0, PARAM_BOOL) ? 1 : 0,
        'local_course_banner_builder'
    );
    redirect(new moodle_url($adminpagepath, $selectedsourceparams), get_string('changessaved'));
}

if (!$issitebanneradmin && $updatecoursedefaultimagebanners && confirm_sesskey()) {
    if (!\local_course_banner_builder\manager::is_course_banner_enabled()) {
        set_config('coursebannerdefaultimageenabled', 0, 'local_course_banner_builder');
        redirect(new moodle_url($adminpagepath, $selectedsourceparams), get_string('changessaved'));
    }
    set_config(
        'coursebannerdefaultimageenabled',
        optional_param('coursebannerdefaultimageenabled', 0, PARAM_BOOL) ? 1 : 0,
        'local_course_banner_builder'
    );
    redirect(new moodle_url($adminpagepath, $selectedsourceparams), get_string('changessaved'));
}

if (!$issitebanneradmin && $updatecoursecustomoverviewimages && confirm_sesskey()) {
    set_config(
        'coursecustomoverviewimagesenabled',
        optional_param('coursecustomoverviewimagesenabled', 0, PARAM_BOOL) ? 1 : 0,
        'local_course_banner_builder'
    );
    redirect(new moodle_url($adminpagepath, $selectedsourceparams), get_string('changessaved'));
}

if (!$issitebanneradmin && $forcecourseoverviewreplacement && confirm_sesskey()) {
    $processed = \local_course_banner_builder\manager::force_course_overview_image_replacement();
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('courseoverviewreplacementforced', 'local_course_banner_builder', $processed)
    );
}

if ($updatebannertitlesettings && confirm_sesskey()) {
    $context = required_param('bannertitlecontext', PARAM_ALPHA);
    $allowedcontexts = $issitebanneradmin ? ['site'] : ['course', 'activity'];
    if (in_array($context, $allowedcontexts, true)) {
        $prefix = 'bannertitle_' . $context . '_';
        $fontfamily = optional_param('fontfamily', '', PARAM_TEXT);
        $fontoptions = \local_course_banner_builder\manager::get_slideshow_font_family_options();
        if ($fontfamily !== '' && !array_key_exists($fontfamily, $fontoptions)) {
            $fontfamily = '';
        }
        $color = optional_param('color', '#FFFFFF', PARAM_TEXT);
        if (!preg_match('/^#[0-9a-f]{6}$/i', $color)) {
            $color = '#FFFFFF';
        }
        set_config($prefix . 'enabled', optional_param('enabled', 0, PARAM_BOOL) ? 1 : 0, 'local_course_banner_builder');
        set_config($prefix . 'x', max(0, min(100, optional_param('x', 50, PARAM_FLOAT))), 'local_course_banner_builder');
        set_config($prefix . 'y', max(0, min(100, optional_param('y', 50, PARAM_FLOAT))), 'local_course_banner_builder');
        set_config($prefix . 'fontsize', max(25, min(160, optional_param('fontsize', 100, PARAM_INT))), 'local_course_banner_builder');
        set_config($prefix . 'lineheight', max(80, min(180, optional_param('lineheight', 105, PARAM_INT))), 'local_course_banner_builder');
        set_config($prefix . 'fontfamily', $fontfamily, 'local_course_banner_builder');
        set_config($prefix . 'color', strtoupper($color), 'local_course_banner_builder');
        foreach ([
            'bold',
            'italic',
            'underline',
            'allcaps',
            'aboveborder',
            'frameenabled',
            'frameshadowenabled',
            'shadowenabled',
            'overlayenabled',
        ] as $flag) {
            set_config($prefix . $flag, optional_param($flag, 0, PARAM_BOOL) ? 1 : 0, 'local_course_banner_builder');
        }
        $frametype = optional_param('frametype', 'box', PARAM_ALPHA);
        set_config(
            $prefix . 'frametype',
            in_array($frametype, ['box', 'highlight'], true) ? $frametype : 'box',
            'local_course_banner_builder'
        );
        $stylemode = optional_param('stylemode', 'custom', PARAM_ALPHA);
        set_config(
            $prefix . 'stylemode',
            in_array($stylemode, ['site', 'course', 'activity', 'custom'], true) ? $stylemode : 'custom',
            'local_course_banner_builder'
        );
        foreach (['framecolor', 'framebordercolor', 'frameshadowcolor', 'shadowcolor', 'overlaycolor'] as $hexfield) {
            $hexvalue = optional_param($hexfield, $hexfield === 'framebordercolor' ? '#FFFFFF' : '#000000', PARAM_TEXT);
            if (!preg_match('/^#[0-9a-f]{6}$/i', $hexvalue)) {
                $hexvalue = $hexfield === 'framebordercolor' ? '#FFFFFF' : '#000000';
            }
            set_config($prefix . $hexfield, strtoupper($hexvalue), 'local_course_banner_builder');
        }
        $ranges = [
            'frameopacity' => [35, 0, 100],
            'frameborderwidth' => [0, 0, 10],
            'frameradius' => [12, 0, 80],
            'framepadding' => [18, 0, 80],
            'frameshadowopacity' => [25, 0, 100],
            'frameshadowblur' => [14, 0, 80],
            'frameshadowdistance' => [6, 0, 50],
            'frameshadowdirection' => [135, 0, 360],
            'shadowopacity' => [55, 0, 100],
            'shadowblur' => [10, 0, 60],
            'shadowdistance' => [4, 0, 40],
            'shadowdirection' => [135, 0, 360],
            'overlayopacity' => [25, 0, 100],
        ];
        foreach ($ranges as $field => $limits) {
            set_config(
                $prefix . $field,
                max($limits[1], min($limits[2], optional_param($field, $limits[0], PARAM_FLOAT))),
                'local_course_banner_builder'
            );
        }
        set_config($prefix . 'align', 'center', 'local_course_banner_builder');
        if ($context === 'activity') {
            $activitytitlemode = optional_param('activitytitlemode', 'activity', PARAM_ALPHA);
            set_config(
                $prefix . 'activitytitlemode',
                in_array($activitytitlemode, ['activity', 'course', 'both', 'none'], true) ? $activitytitlemode : 'activity',
                'local_course_banner_builder'
            );
        }
    }
    redirect(new moodle_url($adminpagepath, $selectedsourceparams), get_string('changessaved'));
}

if ($savebannerformat && confirm_sesskey()) {
    $bannerformat = required_param('bannerformat', PARAM_ALPHAEXT);
    if ($issitebanneradmin) {
        \local_course_banner_builder\manager::set_site_banner_format($bannerformat);
    } else {
        \local_course_banner_builder\manager::set_course_banner_format($bannerformat);
    }
    redirect(new moodle_url($adminpagepath, $selectedsourceparams), get_string('changessaved'));
}

if ($deletepreviewlayerajax && confirm_sesskey() && $selectedsource) {
    \local_course_banner_builder\manager::delete_banner_element($deletepreviewlayerajax);
    $updatedcontext = \local_course_banner_builder\manager::export_selected_source($selectedsource);
    $updatedcontext['sourcevisualeditorhtml'] = local_course_banner_builder_render_source_visual_editor($selectedsource);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $OUTPUT->render_from_template('local_course_banner_builder/admin_selected', $updatedcontext),
    ]);
    exit;
}

if ($deleteselectedlayersajax && confirm_sesskey() && $selectedsource) {
    $selectedelementids = optional_param_array('selectedelements', [], PARAM_INT);
    \local_course_banner_builder\manager::delete_banner_elements($selectedelementids);
    $updatedcontext = \local_course_banner_builder\manager::export_selected_source($selectedsource);
    $updatedcontext['sourcevisualeditorhtml'] = local_course_banner_builder_render_source_visual_editor($selectedsource);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $OUTPUT->render_from_template('local_course_banner_builder/admin_selected', $updatedcontext),
    ]);
    exit;
}

$themename = strtolower((string)($PAGE->theme->name ?? ''));
$adminclasses = [
    'local-course-banner-builder-admin',
    $themename === 'easyedu' ? 'local-course-banner-builder-admin--easyedu' : 'local-course-banner-builder-admin--native',
    $issitebanneradmin ? 'local-course-banner-builder-admin--site' : 'local-course-banner-builder-admin--course',
];

$categoryoptions = \local_course_banner_builder\manager::get_category_source_options();
$customfieldoptions = \local_course_banner_builder\manager::get_enabled_customfield_source_options();

if ($deleteelementid && confirm_sesskey()) {
    \local_course_banner_builder\manager::delete_banner_element($deleteelementid);
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('bannerdeleted', 'local_course_banner_builder')
    );
}

if ($deleteselectedlayers && confirm_sesskey()) {
    $selectedelementids = optional_param_array('selectedelements', [], PARAM_INT);
    $deleted = \local_course_banner_builder\manager::delete_banner_elements($selectedelementids);
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('selectedlayersdeleted', 'local_course_banner_builder', $deleted)
    );
}

if ($deletecategoryimages) {
    $categoryforredirect = $categoryid ?: $deletecategoryimages;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
        \local_course_banner_builder\manager::delete_category_images($deletecategoryimages);
        redirect(
            new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $categoryforredirect]),
            get_string('categoryimagesdeleted', 'local_course_banner_builder')
        );
    }

    $continueurl = new moodle_url('/local/course_banner_builder/admin_manage.php', [
        'categoryid' => $categoryforredirect,
        'deletecategoryimages' => $deletecategoryimages,
        'confirmdeleteimages' => 1,
        'sesskey' => sesskey(),
    ]);
    $cancelurl = new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $categoryforredirect]);

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('deletecategoryimagesconfirm', 'local_course_banner_builder'),
        $continueurl,
        $cancelurl
    );
    echo $OUTPUT->footer();
    exit;
}

if ($deletesourceimages !== '') {
    $sourcetodelete = \local_course_banner_builder\manager::resolve_source($deletesourceimages);
    if ($sourcetodelete && $_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
        \local_course_banner_builder\manager::delete_source_images($sourcetodelete);
        redirect(
            new moodle_url($adminpagepath, $issitebanneradmin ? [] : ['sourcekey' => $sourcetodelete->sourcekey]),
            get_string('categoryimagesdeleted', 'local_course_banner_builder')
        );
    }

    $continueurl = new moodle_url($adminpagepath, [
        'sourcekey' => $deletesourceimages,
        'deletesourceimages' => $deletesourceimages,
        'confirmdeleteimages' => 1,
        'sesskey' => sesskey(),
    ]);
    $cancelurl = new moodle_url($adminpagepath, $issitebanneradmin ? [] : ['sourcekey' => $deletesourceimages]);

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deletecategoryimagesconfirm', 'local_course_banner_builder'), $continueurl, $cancelurl);
    echo $OUTPUT->footer();
    exit;
}

if ($deletecategorycontent) {
    $categoryforredirect = $categoryid ?: $deletecategorycontent;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
        \local_course_banner_builder\manager::delete_category_content($deletecategorycontent);
        redirect(
            new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $categoryforredirect]),
            get_string('categorycontentdeleted', 'local_course_banner_builder')
        );
    }

    $continueurl = new moodle_url('/local/course_banner_builder/admin_manage.php', [
        'categoryid' => $categoryforredirect,
        'deletecategorycontent' => $deletecategorycontent,
        'confirmdeletecategory' => 1,
        'sesskey' => sesskey(),
    ]);
    $cancelurl = new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $categoryforredirect]);

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('deletecategorycontentconfirm', 'local_course_banner_builder'),
        $continueurl,
        $cancelurl
    );
    echo $OUTPUT->footer();
    exit;
}

if ($deletesourcecontent !== '') {
    $sourcetodelete = \local_course_banner_builder\manager::resolve_source($deletesourcecontent);
    if ($sourcetodelete && $_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
        \local_course_banner_builder\manager::delete_source_content($sourcetodelete);
        redirect(
            new moodle_url($adminpagepath, $issitebanneradmin ? [] : ['sourcekey' => $sourcetodelete->sourcekey]),
            get_string('categorycontentdeleted', 'local_course_banner_builder')
        );
    }

    $continueurl = new moodle_url($adminpagepath, [
        'sourcekey' => $deletesourcecontent,
        'deletesourcecontent' => $deletesourcecontent,
        'confirmdeletecategory' => 1,
        'sesskey' => sesskey(),
    ]);
    $cancelurl = new moodle_url($adminpagepath, $issitebanneradmin ? [] : ['sourcekey' => $deletesourcecontent]);

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deletecategorycontentconfirm', 'local_course_banner_builder'), $continueurl, $cancelurl);
    echo $OUTPUT->footer();
    exit;
}

if ($elementid) {
    $element = \local_course_banner_builder\manager::get_banner_element($elementid);
    $elementsource = $element ? \local_course_banner_builder\manager::resolve_source(
        \local_course_banner_builder\manager::get_record_source_key($element)
    ) : null;
    if (!$element || !$selectedsource || !$elementsource || $elementsource->sourcekey !== $selectedsourcekey) {
        $elementid = 0;
    }
}

$currentisborderlayer = false;
$currentisoverlaylayer = false;
if ($elementid) {
    $currentelement = \local_course_banner_builder\manager::get_banner_element($elementid);
    $currentisborderlayer = $currentelement &&
        !\local_course_banner_builder\manager::get_banner_image_file($currentelement) &&
        !empty($currentelement->borderenabled);
    $currentisoverlaylayer = $currentelement &&
        !\local_course_banner_builder\manager::get_banner_image_file($currentelement) &&
        !empty($currentelement->overlayenabled);
}
$formmode = $elementid ? ($currentisborderlayer ? 'editborder' : ($currentisoverlaylayer ? 'editoverlay' : 'editimage')) : 'create';
$previewdefinition = $selectedsource
    ? \local_course_banner_builder\manager::export_modal_preview_definition($selectedsource, $elementid)
    : [];
$borderconflictstate = $selectedsource
    ? \local_course_banner_builder\manager::get_source_border_conflict_state(
        $selectedsource,
        $formmode === 'create' ? 0 : $elementid
    )
    : ['blocked' => false, 'messagekey' => 'sourcealreadyhasborder', 'inlinekey' => 'sourcealreadyhasborderinline'];
$activechildborderlayers = $selectedsource
    ? \local_course_banner_builder\manager::count_active_child_source_border_layers($selectedsource)
    : 0;
$overlayconflictstate = $selectedsource
    ? \local_course_banner_builder\manager::get_source_overlay_conflict_state(
        $selectedsource,
        $formmode === 'create' ? 0 : $elementid
    )
    : ['blocked' => false, 'messagekey' => 'sourcealreadyhasoverlay', 'inlinekey' => 'sourcealreadyhasoverlayinline'];
$activechildoverlaylayers = $selectedsource
    ? \local_course_banner_builder\manager::count_active_child_source_overlay_layers($selectedsource)
    : 0;

if ($updatelayerinline && confirm_sesskey()) {
    $inlineelementid = required_param('elementid', PARAM_INT);
    $layername = required_param('layername_inline', PARAM_TEXT);
    $sortorder = required_param('sortorder_inline', PARAM_INT);
    $enabled = optional_param('isenabled_inline', 0, PARAM_BOOL);
    $fitmodeoverride = optional_param('fitmodeoverride_inline', '', PARAM_ALPHA);
    \local_course_banner_builder\manager::update_banner_element_row(
        $inlineelementid,
        $layername,
        $sortorder,
        (bool)$enabled,
        $fitmodeoverride
    );
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($updatelayersbulk && confirm_sesskey() && $selectedsource) {
    $layernames = optional_param_array('layername_inline', [], PARAM_TEXT);
    $sortorders = optional_param_array('sortorder_inline', [], PARAM_INT);
    $enabledlayers = optional_param_array('isenabled_inline', [], PARAM_BOOL);
    $fitmodeoverrides = optional_param_array('fitmodeoverride_inline', [], PARAM_ALPHA);
    \local_course_banner_builder\manager::update_source_element_rows(
        $selectedsource,
        $layernames,
        $sortorders,
        $enabledlayers,
        $fitmodeoverrides
    );
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($updatepreviewlayers && confirm_sesskey() && $selectedsource) {
    $storedsettings = \local_course_banner_builder\manager::get_source_settings($selectedsource);
    if (empty($storedsettings->id)) {
        redirect(
            new moodle_url($adminpagepath, $selectedsourceparams),
            get_string('sourcesettingsrequiredbeforelayers', 'local_course_banner_builder'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }
    $payload = optional_param('previewlayerpayload', '', PARAM_RAW);
    $payloaddata = json_decode($payload, true);
    \local_course_banner_builder\manager::update_source_visual_editor_layers(
        $selectedsource,
        is_array($payloaddata) ? $payloaddata : []
    );
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($updatesourcesettingfield && confirm_sesskey() && $selectedsource) {
    $fieldname = required_param('fieldname', PARAM_ALPHAEXT);
    $fieldvalue = required_param('fieldvalue', PARAM_RAW_TRIMMED);
    \local_course_banner_builder\manager::update_source_setting_field($selectedsource, $fieldname, $fieldvalue);
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($updatesourceparentfield && confirm_sesskey()) {
    $targetsourcekey = required_param('sourcekey', PARAM_RAW_TRIMMED);
    $fieldvalue = required_param('fieldvalue', PARAM_RAW_TRIMMED);
    $targetsource = \local_course_banner_builder\manager::resolve_source($targetsourcekey);
    if ($targetsource) {
        \local_course_banner_builder\manager::update_source_setting_field($targetsource, 'sourceparentkey', $fieldvalue);
    }
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('changessaved')
    );
}

$form = new \local_course_banner_builder\form\manage_banner_form(null, [
    'selectedcategoryid' => $selectedcategoryid,
    'sourcekey' => $selectedsourcekey,
    'elementid' => $elementid,
    'sourcehasborderlayer' => !empty($borderconflictstate['blocked']) ? 1 : 0,
    'sourcehasoverlaylayer' => !empty($overlayconflictstate['blocked']) ? 1 : 0,
    'borderconflictmessage' => get_string((string)($borderconflictstate['messagekey'] ?? 'sourcealreadyhasborder'), 'local_course_banner_builder'),
    'borderconflictmessageinline' => get_string((string)($borderconflictstate['inlinekey'] ?? 'sourcealreadyhasborderinline'), 'local_course_banner_builder'),
    'overlayconflictmessage' => get_string((string)($overlayconflictstate['messagekey'] ?? 'sourcealreadyhasoverlay'), 'local_course_banner_builder'),
    'overlayconflictmessageinline' => get_string((string)($overlayconflictstate['inlinekey'] ?? 'sourcealreadyhasoverlayinline'), 'local_course_banner_builder'),
    'activechildborderlayers' => $activechildborderlayers,
    'activechildoverlaylayers' => $activechildoverlaylayers,
    'currentisborderlayer' => $currentisborderlayer,
    'currentisoverlaylayer' => $currentisoverlaylayer,
    'issitebanneradmin' => $issitebanneradmin,
    'slideshowoverlaydefault' => \local_course_banner_builder\manager::get_slideshow_config(
        $issitebanneradmin
            ? \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE
            : \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_COURSE
    ),
    'formmode' => $formmode,
    'filemanageroptions' => \local_course_banner_builder\manager::get_filemanager_options(empty($elementid)),
    'uploadguidance' => \local_course_banner_builder\manager::get_upload_guidance(),
    'showmoodlepreview' => ($PAGE->theme->name ?? '') !== 'easyedu',
    'previewdefinition' => $previewdefinition,
]);

if ($savecategorysettings && confirm_sesskey() && $selectedsource) {
    $compositionmode = required_param('compositionmode', PARAM_ALPHA);
    $fitmode = required_param('fitmode', PARAM_ALPHA);
    $fitapplyscope = \local_course_banner_builder\manager::FIT_SCOPE_SELF;
    $customfieldpriority = optional_param(
        'customfieldpriority',
        \local_course_banner_builder\manager::CUSTOMFIELD_PRIORITY_CATEGORY,
        PARAM_ALPHA
    );
    $sourceparentkey = optional_param('sourceparentkey', '', PARAM_RAW_TRIMMED);
    $sourceisroot = optional_param('sourceisroot', 0, PARAM_BOOL);
    if (\local_course_banner_builder\manager::is_site_source($selectedsource)) {
        $customfieldpriority = \local_course_banner_builder\manager::CUSTOMFIELD_PRIORITY_CATEGORY;
        $sourceparentkey = '';
        $sourceisroot = 1;
    }
    if ($sourceparentkey === '') {
        $sourceisroot = 1;
    }
    \local_course_banner_builder\manager::save_source_settings(
        $selectedsource,
        $compositionmode,
        $fitmode,
        $fitapplyscope,
        $customfieldpriority,
        $sourceparentkey,
        (bool)$sourceisroot,
        false
    );
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($form->is_cancelled()) {
    redirect(new moodle_url($adminpagepath, $selectedsourceparams));
}

if ($data = $form->get_data()) {
    if ($selectedsource) {
        $storedsettings = \local_course_banner_builder\manager::get_source_settings($selectedsource);
        if (empty($storedsettings->id)) {
            redirect(
                new moodle_url($adminpagepath, $selectedsourceparams),
                get_string('sourcesettingsrequiredbeforelayers', 'local_course_banner_builder'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }
    }
    foreach (['enabled', 'leftpercent', 'toppercent', 'widthpercent', 'heightpercent'] as $cropfield) {
        $fieldname = 'imagecrop' . $cropfield;
        if (array_key_exists($fieldname, $_POST)) {
            $defaultvalue = in_array($cropfield, ['widthpercent', 'heightpercent'], true) ? 100 : 0;
            $data->{$fieldname} = $cropfield === 'enabled' ?
                optional_param($fieldname, 0, PARAM_BOOL) :
                optional_param($fieldname, $defaultvalue, PARAM_FLOAT);
        }
    }
    $previewcropstate = optional_param('previewcropstate', '', PARAM_RAW_TRIMMED);
    if ($previewcropstate !== '') {
        $cropstate = json_decode($previewcropstate, true);
        if (is_array($cropstate)) {
            foreach (['enabled', 'leftpercent', 'toppercent', 'widthpercent', 'heightpercent'] as $cropfield) {
                $fieldname = 'imagecrop' . $cropfield;
                if (array_key_exists($fieldname, $cropstate)) {
                    $data->{$fieldname} = $cropfield === 'enabled' ? (empty($cropstate[$fieldname]) ? 0 : 1) :
                        (float)$cropstate[$fieldname];
                }
            }
        }
    }
    \local_course_banner_builder\manager::save_category_banner($data);
    $disabledborderlayers = (int)($data->disabledchildborderlayers ?? 0);
    $disabledoverlaylayers = (int)($data->disabledchildoverlaylayers ?? 0);
    $redirectmessage = get_string('changessaved');
    $redirecttype = \core\output\notification::NOTIFY_SUCCESS;
    if ($disabledborderlayers > 0) {
        $redirectmessage = get_string('childborderlayersdisablednotice', 'local_course_banner_builder', $disabledborderlayers);
        $redirecttype = \core\output\notification::NOTIFY_WARNING;
    } else if ($disabledoverlaylayers > 0) {
        $redirectmessage = get_string('childoverlaylayersdisablednotice', 'local_course_banner_builder', $disabledoverlaylayers);
        $redirecttype = \core\output\notification::NOTIFY_WARNING;
    }
    redirect(
        new moodle_url($adminpagepath, $issitebanneradmin ? [] : ['sourcekey' => $data->sourcekey]),
        $redirectmessage,
        null,
        $redirecttype
    );
}

if ($selectedsource) {
    $form->set_data(\local_course_banner_builder\manager::get_source_form_data($selectedsource, $elementid, empty($elementid)));
}

$isxmlhttprequest = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
if ($isxmlhttprequest && $selectedsource && $elementid) {
    $ajaxmodalid = $formmode === 'editborder' ?
        'local-course-banner-builder-edit-border-layer-modal' :
        'local-course-banner-builder-edit-image-layer-modal';
    $ajaxmodaltitle = $formmode === 'editborder' ?
        get_string('layerborder', 'local_course_banner_builder') :
        get_string('editimage', 'local_course_banner_builder');
    local_course_banner_builder_render_layer_modal($ajaxmodalid, $ajaxmodaltitle, function() use ($form) {
        $form->display();
    });
    exit;
}

$PAGE->requires->js_init_code("
document.addEventListener('change', function(e) {
    var formatInput = e.target && e.target.closest ?
        e.target.closest('#local-course-banner-builder-banner-format-modal input[name=\"bannerformat\"]') :
        null;
    if (!formatInput) {
        return;
    }
    var modal = formatInput.closest('#local-course-banner-builder-banner-format-modal');
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

document.addEventListener('click', function(e) {
    var popoverTrigger = e.target && e.target.closest ?
        e.target.closest('[data-toggle=\"popover\"], [data-bs-toggle=\"popover\"]') :
        null;
    if (!popoverTrigger) {
        return;
    }
    localCourseBannerBuilderDismissOpenPopovers(popoverTrigger);
    if (popoverTrigger.getAttribute('data-toggle') === 'popover' ||
            popoverTrigger.getAttribute('data-bs-toggle') === 'popover') {
        popoverTrigger.setAttribute('data-local-course-banner-builder-popover-toggle-suspended', '1');
        var hadDataToggle = popoverTrigger.getAttribute('data-toggle') === 'popover';
        var hadBsToggle = popoverTrigger.getAttribute('data-bs-toggle') === 'popover';
        if (hadDataToggle) {
            popoverTrigger.removeAttribute('data-toggle');
        }
        if (hadBsToggle) {
            popoverTrigger.removeAttribute('data-bs-toggle');
        }
        window.setTimeout(function() {
            if (popoverTrigger.getAttribute('data-local-course-banner-builder-popover-toggle-suspended') === '1') {
                if (hadDataToggle) {
                    popoverTrigger.setAttribute('data-toggle', 'popover');
                }
                if (hadBsToggle) {
                    popoverTrigger.setAttribute('data-bs-toggle', 'popover');
                }
                popoverTrigger.removeAttribute('data-local-course-banner-builder-popover-toggle-suspended');
            }
            localCourseBannerBuilderDismissOpenPopovers(popoverTrigger);
        }, 0);
        window.setTimeout(function() {
            localCourseBannerBuilderDismissOpenPopovers(popoverTrigger);
        }, 80);
    }
}, true);

document.addEventListener('focusin', function(e) {
    var popoverTrigger = e.target && e.target.closest ?
        e.target.closest('[data-toggle=\"popover\"], [data-bs-toggle=\"popover\"]') :
        null;
    if (popoverTrigger) {
        localCourseBannerBuilderDismissOpenPopovers(popoverTrigger);
    }
}, true);

document.addEventListener('click', function(e) {
    var helpSummary = e.target && e.target.closest ?
        e.target.closest('.local-course-banner-builder-help-bubble > summary') :
        null;
    if (!helpSummary) {
        return;
    }
    e.preventDefault();
    var details = helpSummary.closest('details');
    if (details) {
        details.open = true;
    }
}, true);

document.addEventListener('keydown', function(e) {
    var helpSummary = e.target && e.target.closest ?
        e.target.closest('.local-course-banner-builder-help-bubble > summary') :
        null;
    if (!helpSummary || (e.key !== 'Enter' && e.key !== ' ')) {
        return;
    }
    e.preventDefault();
    var details = helpSummary.closest('details');
    if (details) {
        details.open = true;
    }
}, true);

document.addEventListener('toggle', function(e) {
    if (e.target && e.target.matches && e.target.matches('.local-course-banner-builder-help-bubble') && !e.target.open) {
        e.target.open = true;
    }
}, true);

document.addEventListener('click', function(e) {
    var aspectLockButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-preview-aspect-lock\"]');
    if (aspectLockButton) {
        e.preventDefault();
        e.stopPropagation();
        localCourseBannerBuilderTogglePreviewAspectLock(aspectLockButton);
        return;
    }

    var previewToolbarAction = e.target.closest('[data-action]');
    if (localCourseBannerBuilderIsOneShotPreviewToolbarAction(previewToolbarAction)) {
        localCourseBannerBuilderTriggerPreviewActionFeedback(previewToolbarAction);
    }

    var addLayerTrigger = e.target.closest(
        '[data-target=\"#local-course-banner-builder-add-layer-modal\"],' +
        '[data-bs-target=\"#local-course-banner-builder-add-layer-modal\"],' +
        'a[href=\"#local-course-banner-builder-add-layer-modal\"]'
    );
    if (addLayerTrigger) {
        if (localCourseBannerBuilderLoadCreateLayerModal()) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') {
                e.stopImmediatePropagation();
            }
            return;
        }
    }

    var dismissmodal = e.target.closest('[data-dismiss=\"modal\"], [data-bs-dismiss=\"modal\"]');
    if (dismissmodal) {
        var modal = dismissmodal.closest('.modal');
        if (modal) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') {
                e.stopImmediatePropagation();
            }
            localCourseBannerBuilderHideModal(modal);
            return;
        }
    }

    if (e.target.closest('[data-action=\"local-course-banner-builder-summary-action\"]')) {
        e.stopPropagation();
    }

    var editlayerbutton = e.target.closest('[data-edit-layer-url]');
    if (editlayerbutton) {
        e.preventDefault();
        localCourseBannerBuilderLoadLayerModal(editlayerbutton.getAttribute('data-edit-layer-url'));
        return;
    }

    var editbutton = e.target.closest('[data-action=\"local-course-banner-builder-inline-edit\"]');
    if (editbutton) {
        e.preventDefault();
        var editrow = editbutton.closest('[data-inline-setting-row]');
        if (editrow) {
            localCourseBannerBuilderToggleInlineSetting(editrow, true);
        }
        return;
    }

    var cancelbutton = e.target.closest('[data-action=\"local-course-banner-builder-inline-cancel\"]');
    if (cancelbutton) {
        e.preventDefault();
        var cancelrow = cancelbutton.closest('[data-inline-setting-row]');
        if (cancelrow) {
            localCourseBannerBuilderToggleInlineSetting(cancelrow, false);
        }
        return;
    }

    var recenterbutton = e.target.closest('[data-action=\"local-course-banner-builder-recenter-preview-image\"]');
    if (recenterbutton) {
        e.preventDefault();
        localCourseBannerBuilderPushModalPreviewHistoryFromControl(recenterbutton);
        localCourseBannerBuilderRecenterPreviewImage(recenterbutton.closest('form.mform, .mform, .modal'));
        return;
    }

    var recenterSourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-recenter-source-preview-image\"]');
    if (recenterSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderPushSourcePreviewHistoryFromControl(recenterSourcePreviewButton);
        localCourseBannerBuilderRecenterSourcePreviewImage(recenterSourcePreviewButton);
        return;
    }

    var recenterAllSourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-recenter-all-source-preview-images\"]');
    if (recenterAllSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderPushSourcePreviewHistoryFromControl(recenterAllSourcePreviewButton);
        localCourseBannerBuilderRecenterAllSourcePreviewImages(recenterAllSourcePreviewButton);
        return;
    }

    var fitSourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-fit-source-preview-image\"]');
    if (fitSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderPushSourcePreviewHistoryFromControl(fitSourcePreviewButton);
        localCourseBannerBuilderFitSelectedSourcePreviewImageToFrame(fitSourcePreviewButton);
        return;
    }

    var fillSourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-fill-source-preview-image\"]');
    if (fillSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderPushSourcePreviewHistoryFromControl(fillSourcePreviewButton);
        localCourseBannerBuilderFillSelectedSourcePreviewImageToBanner(fillSourcePreviewButton);
        return;
    }

    var fitLayerPreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-fit-layer-preview-image\"]');
    if (fitLayerPreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderPushModalPreviewHistoryFromControl(fitLayerPreviewButton);
        localCourseBannerBuilderFitSelectedLayerPreviewImageToFrame(fitLayerPreviewButton);
        return;
    }

    var fillLayerPreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-fill-layer-preview-image\"]');
    if (fillLayerPreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderPushModalPreviewHistoryFromControl(fillLayerPreviewButton);
        localCourseBannerBuilderFillSelectedLayerPreviewImageToBanner(fillLayerPreviewButton);
        return;
    }

    var pushModalLayerButton = e.target.closest(
        '[data-action=\"local-course-banner-builder-push-modal-preview-layer-behind\"]'
    );
    if (pushModalLayerButton) {
        e.preventDefault();
        localCourseBannerBuilderPushModalPreviewHistoryFromControl(pushModalLayerButton);
        localCourseBannerBuilderMoveModalPreviewLayerDepth(pushModalLayerButton, -1);
        return;
    }

    var pullModalLayerButton = e.target.closest(
        '[data-action=\"local-course-banner-builder-pull-modal-preview-layer-forward\"]'
    );
    if (pullModalLayerButton) {
        e.preventDefault();
        localCourseBannerBuilderPushModalPreviewHistoryFromControl(pullModalLayerButton);
        localCourseBannerBuilderMoveModalPreviewLayerDepth(pullModalLayerButton, 1);
        return;
    }

    var applyPreviewCropButton = e.target.closest('[data-action=\"local-course-banner-builder-apply-preview-crop\"]');
    if (applyPreviewCropButton) {
        e.preventDefault();
        e.stopPropagation();
        var applySourceMode = !!applyPreviewCropButton.closest('[data-source-visual-editor=\"1\"]');
        localCourseBannerBuilderApplyCropEditor(applyPreviewCropButton, applySourceMode);
        return;
    }

    var cancelPreviewCropButton = e.target.closest('[data-action=\"local-course-banner-builder-cancel-preview-crop\"]');
    if (cancelPreviewCropButton) {
        e.preventDefault();
        e.stopPropagation();
        var cancelSourceMode = !!cancelPreviewCropButton.closest('[data-source-visual-editor=\"1\"]');
        localCourseBannerBuilderCancelCropEditor(cancelPreviewCropButton, cancelSourceMode);
        return;
    }

    var sourceCropButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-source-preview-crop\"]');
    if (sourceCropButton) {
        e.preventDefault();
        localCourseBannerBuilderToggleCropEditor(sourceCropButton, true);
        return;
    }

    var applySourceCropButton = e.target.closest('[data-action=\"local-course-banner-builder-apply-source-preview-crop\"]');
    if (applySourceCropButton) {
        e.preventDefault();
        localCourseBannerBuilderApplyCropEditor(applySourceCropButton, true);
        return;
    }

    var modalCropButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-modal-preview-crop\"]');
    if (modalCropButton) {
        e.preventDefault();
        localCourseBannerBuilderToggleCropEditor(modalCropButton, false);
        return;
    }

    var applyModalCropButton = e.target.closest('[data-action=\"local-course-banner-builder-apply-modal-preview-crop\"]');
    if (applyModalCropButton) {
        e.preventDefault();
        localCourseBannerBuilderApplyCropEditor(applyModalCropButton, false);
        return;
    }

    var undoSourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-undo-source-preview-change\"]');
    if (undoSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderUndoSourcePreviewChange(undoSourcePreviewButton);
        return;
    }

    var redoSourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-redo-source-preview-change\"]');
    if (redoSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderRedoSourcePreviewChange(redoSourcePreviewButton);
        return;
    }

    var undoModalPreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-undo-modal-preview-change\"]');
    if (undoModalPreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderUndoModalPreviewChange(undoModalPreviewButton);
        return;
    }

    var redoModalPreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-redo-modal-preview-change\"]');
    if (redoModalPreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderRedoModalPreviewChange(redoModalPreviewButton);
        return;
    }

    var previewSnapButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-preview-snap\"]');
    if (previewSnapButton) {
        e.preventDefault();
        localCourseBannerBuilderTogglePreviewSnap(previewSnapButton);
        return;
    }

    var sourcePreviewFilmstripScrollButton = e.target.closest('[data-action=\"local-course-banner-builder-scroll-source-preview-filmstrip\"]');
    if (sourcePreviewFilmstripScrollButton) {
        e.preventDefault();
        localCourseBannerBuilderScrollSourcePreviewFilmstrip(sourcePreviewFilmstripScrollButton);
        return;
    }

    var sourcePreviewVisibilityButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-source-preview-thumbnail-visibility\"]');
    if (sourcePreviewVisibilityButton) {
        e.preventDefault();
        e.stopPropagation();
        localCourseBannerBuilderToggleSourcePreviewThumbnailVisibility(sourcePreviewVisibilityButton);
        return;
    }

    var allSourcePreviewVisibilityButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-all-source-preview-images\"]');
    if (allSourcePreviewVisibilityButton) {
        e.preventDefault();
        localCourseBannerBuilderToggleAllSourcePreviewImageVisibility(allSourcePreviewVisibilityButton);
        return;
    }

    var inheritedSourcePreviewButton = e.target.closest(
        '[data-action=\"local-course-banner-builder-toggle-source-preview-inherited-layers\"]'
    );
    if (inheritedSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderToggleSourcePreviewInheritedLayers(inheritedSourcePreviewButton);
        return;
    }

    var sourcePreviewThumbButton = e.target.closest('[data-action=\"local-course-banner-builder-select-source-preview-thumbnail\"]');
    if (sourcePreviewThumbButton) {
        e.preventDefault();
        localCourseBannerBuilderSelectSourcePreviewThumbnail(sourcePreviewThumbButton);
        return;
    }

    var pushBehindButton = e.target.closest('[data-action=\"local-course-banner-builder-push-source-preview-layer-behind\"]');
    if (pushBehindButton) {
        e.preventDefault();
        localCourseBannerBuilderPushSourcePreviewHistoryFromControl(pushBehindButton);
        localCourseBannerBuilderPushSelectedSourcePreviewLayerBehind(pushBehindButton);
        return;
    }

    var pullForwardButton = e.target.closest('[data-action=\"local-course-banner-builder-pull-source-preview-layer-forward\"]');
    if (pullForwardButton) {
        e.preventDefault();
        localCourseBannerBuilderPushSourcePreviewHistoryFromControl(pullForwardButton);
        localCourseBannerBuilderPullSelectedSourcePreviewLayerForward(pullForwardButton);
        return;
    }

    var keepAspectButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-source-preview-keep-aspect\"]');
    if (keepAspectButton) {
        e.preventDefault();
        var keepAspectRoot = keepAspectButton.closest('[data-source-visual-editor=\"1\"]');
        var keepAspectLayer = keepAspectRoot ? localCourseBannerBuilderGetSelectedSourcePreviewLayer(keepAspectRoot) : null;
        if (keepAspectRoot && keepAspectLayer) {
            var keepAspectState = localCourseBannerBuilderGetSourcePreviewLayerState(keepAspectLayer);
            if (keepAspectState) {
                localCourseBannerBuilderPushSourcePreviewHistory(keepAspectRoot);
                if (!keepAspectState.customsizekeepaspect &&
                        localCourseBannerBuilderApplyClosestKeepAspectToSourcePreview(keepAspectRoot, keepAspectLayer)) {
                    return;
                }
                keepAspectState.customsizekeepaspect = false;
                localCourseBannerBuilderSetSourcePreviewLayerState(keepAspectLayer, keepAspectState);
                localCourseBannerBuilderSyncSourcePreviewLayer(keepAspectRoot, keepAspectLayer);
                localCourseBannerBuilderUpdateSourcePreviewRow(keepAspectRoot, keepAspectState);
                localCourseBannerBuilderSyncSourcePreviewPayload(keepAspectRoot);
                localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(keepAspectRoot);
            }
        }
        return;
    }

    var sourceOpacityButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-source-preview-opacity\"]');
    if (sourceOpacityButton) {
        e.preventDefault();
        var sourceOpacityRoot = sourceOpacityButton.closest('[data-source-visual-editor=\"1\"]');
        var sourceOpacityPanel = sourceOpacityRoot ?
            sourceOpacityRoot.querySelector('[data-source-preview-opacity-panel=\"1\"]') :
            null;
        if (sourceOpacityPanel) {
            localCourseBannerBuilderToggleOpacityPanel(sourceOpacityPanel, sourceOpacityButton);
            localCourseBannerBuilderSyncSourcePreviewOpacityButton(sourceOpacityRoot);
        }
        return;
    }

    var topLayerButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-source-preview-top-layer\"]');
    if (topLayerButton) {
        e.preventDefault();
        var topLayerRoot = topLayerButton.closest('[data-source-visual-editor=\"1\"]');
        var selectedLayer = topLayerRoot ? localCourseBannerBuilderGetSelectedSourcePreviewLayer(topLayerRoot) : null;
        if (topLayerRoot && selectedLayer) {
            var state = localCourseBannerBuilderGetSourcePreviewLayerState(selectedLayer);
            if (state) {
                localCourseBannerBuilderPushSourcePreviewHistory(topLayerRoot);
                state.dynamicimagesizeenabled = !state.dynamicimagesizeenabled;
                localCourseBannerBuilderSetSourcePreviewLayerState(selectedLayer, state);
                if (state.dynamicimagesizeenabled) {
                    localCourseBannerBuilderMoveSourcePreviewLayerRowToTop(state.id);
                }
                localCourseBannerBuilderSyncSourcePreviewOrder(topLayerRoot);
                localCourseBannerBuilderSyncSourcePreviewLayer(topLayerRoot, selectedLayer);
                localCourseBannerBuilderUpdateSourcePreviewRow(topLayerRoot, state);
                localCourseBannerBuilderSyncSourcePreviewPayload(topLayerRoot);
                localCourseBannerBuilderSyncSourcePreviewTopLayerButton(topLayerRoot);
                localCourseBannerBuilderSelectSourcePreviewLayer(topLayerRoot, selectedLayer);
            }
        }
        return;
    }

    var deleteSelectedPreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-delete-selected-preview-layer\"]');
    if (deleteSelectedPreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderDeleteSelectedPreviewLayer(deleteSelectedPreviewButton);
        return;
    }

    var deleteSelectedDraftButton = e.target.closest('[data-action=\"local-course-banner-builder-delete-selected-draft-preview-layer\"]');
    if (deleteSelectedDraftButton) {
        e.preventDefault();
        localCourseBannerBuilderDeleteSelectedDraftPreviewLayer(deleteSelectedDraftButton);
        return;
    }

    var rootSourceButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-root-source\"]');
    if (rootSourceButton) {
        e.preventDefault();
        localCourseBannerBuilderToggleRootSource(rootSourceButton);
        return;
    }

    var chainToggle = e.target.closest('[data-action=\"local-course-banner-builder-toggle-source-chain\"]');
    if (chainToggle) {
        e.preventDefault();
        localCourseBannerBuilderToggleSourceChain(chainToggle);
        return;
    }

    var allChainToggle = e.target.closest('[data-action=\"local-course-banner-builder-toggle-all-source-chains\"]');
    if (allChainToggle) {
        e.preventDefault();
        localCourseBannerBuilderToggleAllSourceChains(allChainToggle);
        return;
    }

    var sourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-show-source-chain-preview\"]');
    if (sourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderDismissOpenPopovers(sourcePreviewButton);
        localCourseBannerBuilderShowSourceChainPreview(sourcePreviewButton);
        return;
    }

    var togglePreviewBorderButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-preview-border\"]');
    if (togglePreviewBorderButton) {
        var previewPanel = togglePreviewBorderButton.closest('[data-source-visual-editor=\"1\"]');
        if (previewPanel) {
            var isVisible = togglePreviewBorderButton.getAttribute('data-preview-border-visible') !== '0';
            var nextVisible = !isVisible;
            previewPanel.setAttribute('data-preview-border-hidden', nextVisible ? '0' : '1');
            togglePreviewBorderButton.setAttribute('data-preview-border-visible', nextVisible ? '1' : '0');
            togglePreviewBorderButton.setAttribute('aria-pressed', nextVisible ? 'true' : 'false');
            localCourseBannerBuilderSetActionButtonContent(
                togglePreviewBorderButton,
                nextVisible ? (togglePreviewBorderButton.getAttribute('data-hide-icon') || 'fa-eye-slash') : (togglePreviewBorderButton.getAttribute('data-show-icon') || 'fa-eye'),
                nextVisible ?
                    (togglePreviewBorderButton.getAttribute('data-hide-label') ||
                        localCourseBannerBuilderGetJsString('hidepreviewborder', 'Hide border')) :
                    (togglePreviewBorderButton.getAttribute('data-show-label') ||
                        localCourseBannerBuilderGetJsString('showpreviewborder', 'Show border'))
            );
        }
        return;
    }

    var deleteAllLayersButton = e.target.closest('[data-action=\"local-course-banner-builder-delete-all-layers\"]');
    if (deleteAllLayersButton) {
        e.preventDefault();
        localCourseBannerBuilderDeleteAllLayers(deleteAllLayersButton);
        return;
    }

    var deleteSelectedLayersButton = e.target.closest(
        '[data-action=\"local-course-banner-builder-delete-selected-layers\"]'
    );
    if (deleteSelectedLayersButton) {
        e.preventDefault();
        localCourseBannerBuilderDeleteSelectedLayers(deleteSelectedLayersButton);
        return;
    }

    var sourcePreviewRoot = e.target.closest('[data-source-visual-editor=\"1\"]');
    if (sourcePreviewRoot && !localCourseBannerBuilderIsSourcePreviewReadonly(sourcePreviewRoot)) {
        var sourceLayer = localCourseBannerBuilderGetSourcePreviewEventLayer(sourcePreviewRoot, e);
        if (sourceLayer) {
            localCourseBannerBuilderSelectSourcePreviewLayer(sourcePreviewRoot, sourceLayer);
        }
    }

    var clickedDraftLayer = e.target.closest('[data-preview-draft-layer=\"1\"]');
    if (clickedDraftLayer) {
        e.preventDefault();
        localCourseBannerBuilderSelectDraftPreviewLayer(
            localCourseBannerBuilderGetLayerScope(clickedDraftLayer),
            clickedDraftLayer.getAttribute('data-draft-index') || '0'
        );
        return;
    }

    var toggle = e.target.closest('[data-action=\"local-course-banner-builder-toggle-selection\"]');
    if (!toggle) {
        if (localCourseBannerBuilderIsNativeAdmin() && e.target.classList && e.target.classList.contains('modal')) {
            localCourseBannerBuilderHideModal(e.target);
        }
        return;
    }
    var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.local-course-banner-builder-layer-select'));
    if (!checkboxes.length) {
        return;
    }
    var allChecked = checkboxes.every(function(checkbox) {
        return checkbox.checked;
    });
    var shouldCheck = !allChecked;
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = shouldCheck;
    });
    localCourseBannerBuilderSyncSelectionButton();
});

document.addEventListener('keydown', function(e) {
    var key = e.key || e.keyCode;
    if (key !== 'Escape' && key !== 'Esc' && key !== 27) {
        return;
    }
    var modals = Array.prototype.slice.call(document.querySelectorAll('.modal'));
    var modal = modals.reverse().find(function(node) {
        return node && (
            node.classList.contains('show') ||
            node.getAttribute('aria-modal') === 'true' ||
            node.style.display === 'block'
        );
    });
    if (!modal) {
        return;
    }
    e.preventDefault();
    e.stopPropagation();
    if (typeof e.stopImmediatePropagation === 'function') {
        e.stopImmediatePropagation();
    }
    localCourseBannerBuilderHideModal(modal);
}, true);

document.addEventListener('shown.bs.modal', function(e) {
    var modal = e.target && e.target.closest ? e.target.closest('.modal') : null;
    if (!modal || !modal.id || modal.id.indexOf('local-course-banner-builder-') !== 0) {
        return;
    }
    var form = modal.querySelector('form.mform');
    if (!form) {
        return;
    }
    localCourseBannerBuilderUpgradeRanges();
    localCourseBannerBuilderUpgradeNumberInputs();
    localCourseBannerBuilderUpgradeColorPickers(modal);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncLayerInputModes(form);
    localCourseBannerBuilderSyncBorderPreview(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
    localCourseBannerBuilderEnhanceModalPreviewActions(form);
    localCourseBannerBuilderEnhanceBorderSidePicker(form);
    localCourseBannerBuilderSyncDetailsCollapseIcons(modal);
    localCourseBannerBuilderPrimeHelpBubbles(modal);
}, true);

document.addEventListener('dblclick', function(e) {
    var sourcePreviewRoot = e.target.closest('[data-source-visual-editor=\"1\"]');
    if (sourcePreviewRoot && localCourseBannerBuilderIsSourcePreviewReadonly(sourcePreviewRoot)) {
        return;
    }
    var sourceLayer = sourcePreviewRoot ?
        localCourseBannerBuilderGetSourcePreviewEventLayer(sourcePreviewRoot, e) :
        null;
    if (sourceLayer && sourcePreviewRoot) {
        e.preventDefault();
        localCourseBannerBuilderSelectSourcePreviewLayer(sourcePreviewRoot, sourceLayer);
        return;
    }

    var draftLayer = e.target.closest('[data-preview-draft-layer=\"1\"]');
    if (draftLayer) {
        e.preventDefault();
        localCourseBannerBuilderSelectDraftPreviewLayer(
            localCourseBannerBuilderGetLayerScope(draftLayer),
            draftLayer.getAttribute('data-draft-index') || '0'
        );
    }
});

document.addEventListener('keydown', function(e) {
    var sourceLayer = e.target.closest ? e.target.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null;
    if (!sourceLayer) {
        return;
    }
    if (localCourseBannerBuilderIsSourcePreviewReadonly(sourceLayer.closest('[data-source-visual-editor=\"1\"]'))) {
        return;
    }
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        localCourseBannerBuilderSelectSourcePreviewLayer(
            sourceLayer.closest('[data-source-visual-editor=\"1\"]'),
            sourceLayer
        );
    }
});

document.addEventListener('keydown', function(e) {
    if (!localCourseBannerBuilderIsPreviewNudgeEvent(e)) {
        return;
    }
    var keys = {
        ArrowLeft: [-1, 0],
        ArrowRight: [1, 0],
        ArrowUp: [0, -1],
        ArrowDown: [0, 1]
    };
    var delta = keys[e.key];
    var step = e.shiftKey ? 10 : 1;
    if (!localCourseBannerBuilderNudgeActivePreviewSelection(delta[0] * step, delta[1] * step, !e.repeat)) {
        return;
    }
    e.preventDefault();
    e.stopPropagation();
}, true);

document.addEventListener('change', function(e) {
    if (e.target.closest('.local-course-banner-builder-layer-select')) {
        localCourseBannerBuilderSyncSelectionButton();
    }
    if (e.target.name && e.target.name.indexOf('isenabled_inline[') === 0) {
        localCourseBannerBuilderSyncSourcePreviewEnabledState(e.target);
    }
    if (e.target.name && e.target.name.indexOf('fitmodeoverride_inline[') === 0) {
        localCourseBannerBuilderSyncSourcePreviewFitOverrideState(e.target);
    }
    if (e.target.matches('[data-layer-position-anchor=\"1\"]')) {
        localCourseBannerBuilderApplyLayerPositionAnchorChange(e.target);
    }
    if (e.target.matches('[data-preview-context-toggle=\"1\"]')) {
        var contextForm = localCourseBannerBuilderGetLayerScope(e.target);
        localCourseBannerBuilderSyncContextPreviewVisibility(contextForm || e.target);
        localCourseBannerBuilderSyncModalPreviewActionButtons(contextForm);
    }
    if (e.target.closest('form.mform') && e.target.closest('#local-course-banner-builder-add-layer-modal')) {
        localCourseBannerBuilderSaveActiveDraftPreviewState(e.target.closest('form.mform'));
    }
});

document.addEventListener('keydown', function(e) {
    var select = e.target.closest('[data-inline-setting-select]');
    if (!select) {
        if (e.key === 'Escape' && localCourseBannerBuilderIsNativeAdmin()) {
            var modal = localCourseBannerBuilderGetOpenModal();
            if (modal) {
                e.preventDefault();
                localCourseBannerBuilderHideModal(modal);
            }
        }
        return;
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        var form = select.closest('form');
        if (form) {
            form.requestSubmit();
        }
        return;
    }
    if (e.key === 'Escape') {
        e.preventDefault();
        var row = select.closest('[data-inline-setting-row]');
        if (row) {
            localCourseBannerBuilderToggleInlineSetting(row, false);
        }
    }
});

document.addEventListener('submit', function(e) {
    var form = e.target && e.target.closest ? e.target.closest('form') : null;
    if (!form || !form.closest('[class*=\"local-course-banner-builder\"]')) {
        return;
    }
    if (form.dataset.formSubmitting === '1') {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') {
            e.stopImmediatePropagation();
        }
        return;
    }
    form.dataset.formSubmitting = '1';
    if (form.matches && form.matches('form.mform')) {
        localCourseBannerBuilderPrepareLayerFormForSubmit(form);
    }
    Array.prototype.slice.call(form.querySelectorAll(
        'input[type=\"submit\"], button[type=\"submit\"], [data-modal-preview-submit-proxy=\"1\"]'
    )).forEach(function(button) {
        button.disabled = true;
        button.classList.add('disabled');
        button.setAttribute('aria-disabled', 'true');
    });
    Array.prototype.slice.call(document.querySelectorAll('form')).forEach(function(node) {
        node.dataset.formSubmitted = 'true';
        node.dataset.formDirty = 'false';
    });
}, true);

document.addEventListener('pointerdown', function(e) {
    if (localCourseBannerBuilderStartCropInteraction(e)) {
        return;
    }
    if (e.target.closest('[data-action=\"local-course-banner-builder-toggle-preview-aspect-lock\"]')) {
        return;
    }
    var sourcePreviewRoot = e.target.closest('[data-source-visual-editor=\"1\"]');
    if (!sourcePreviewRoot || localCourseBannerBuilderIsSourcePreviewReadonly(sourcePreviewRoot)) {
        return;
    }
    var resizeHandle = localCourseBannerBuilderGetSelectedSourcePreviewResizeHandleAtPoint(sourcePreviewRoot, e.clientX, e.clientY) ||
        e.target.closest('[data-source-preview-layer=\"1\"] [data-preview-resize-handle=\"1\"]');
    var targetLayer = resizeHandle ? resizeHandle.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null;
    targetLayer = targetLayer || localCourseBannerBuilderGetSourcePreviewEventLayer(sourcePreviewRoot, e);
    if (!targetLayer || targetLayer.getAttribute('data-source-preview-editable') !== '1') {
        return;
    }
    localCourseBannerBuilderSelectSourcePreviewLayer(sourcePreviewRoot, targetLayer);
    localCourseBannerBuilderSuppressNextSourcePreviewClick();
    var selectedLayer = targetLayer;
    if (resizeHandle) {
        e.preventDefault();
        e.stopPropagation();
        selectedLayer.setAttribute('data-preview-active-edge', resizeHandle.getAttribute('data-preview-resize-edge') || '');
        localCourseBannerBuilderStartSourcePreviewInteraction(
            e,
            resizeHandle.getAttribute('data-preview-resize-mode') === 'edge' ? 'resize-edge' : 'resize',
            selectedLayer
        );
        return;
    }
    var clickedImage = e.target.closest('[data-preview-image-tag=\"1\"]');
    if (selectedLayer.classList.contains('local-course-banner-builder-source-preview-layer--selected') &&
            selectedLayer.contains(e.target)) {
        e.preventDefault();
        e.stopPropagation();
        localCourseBannerBuilderStartSourcePreviewInteraction(e, 'drag', selectedLayer);
        return;
    }
    if (clickedImage && selectedLayer.contains(clickedImage) &&
            localCourseBannerBuilderIsSourcePreviewLayerOpaqueAtPoint(selectedLayer, e.clientX, e.clientY)) {
        e.preventDefault();
        e.stopPropagation();
        localCourseBannerBuilderStartSourcePreviewInteraction(e, 'drag', selectedLayer);
        return;
    }
    e.preventDefault();
    e.stopPropagation();
});

document.addEventListener('pointerdown', function(e) {
    localCourseBannerBuilderStartCropInteraction(e);
}, true);

document.addEventListener('dragstart', function(e) {
    var row = e.target.closest('.local-course-banner-builder-layer-row[draggable=\"true\"]');
    if (!row) {
        e.preventDefault();
        return;
    }
    row.classList.add('local-course-banner-builder-layer-row-dragging');
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', 'layer-row');
        e.dataTransfer.setDragImage(row, 24, 24);
    }
});

document.addEventListener('dragend', function(e) {
    var row = e.target.closest('.local-course-banner-builder-layer-row');
    if (row) {
        row.classList.remove('local-course-banner-builder-layer-row-dragging');
    }
    localCourseBannerBuilderSyncLayerSortOrders();
});

document.addEventListener('dragover', function(e) {
    var tbody = e.target.closest('.local-course-banner-builder-layer-sortable[data-layer-sortable=\"1\"]');
    if (!tbody) {
        return;
    }
    var dragging = tbody.querySelector('.local-course-banner-builder-layer-row-dragging');
    if (!dragging) {
        return;
    }
    e.preventDefault();
    var target = e.target.closest('.local-course-banner-builder-layer-row');
    if (!target || target === dragging) {
        return;
    }
    var rect = target.getBoundingClientRect();
    var insertAfter = e.clientY > rect.top + (rect.height / 2);
    tbody.insertBefore(dragging, insertAfter ? target.nextSibling : target);
    localCourseBannerBuilderEnforceLockedLayerOrder(tbody);
    localCourseBannerBuilderSyncLayerSortOrders();
});

document.addEventListener('drop', function(e) {
    var tbody = e.target.closest('.local-course-banner-builder-layer-sortable[data-layer-sortable=\"1\"]');
    if (!tbody) {
        return;
    }
    e.preventDefault();
    Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row-dragging')).forEach(function(row) {
        row.classList.remove('local-course-banner-builder-layer-row-dragging');
    });
    localCourseBannerBuilderEnforceLockedLayerOrder(tbody);
    localCourseBannerBuilderSyncLayerSortOrders();
});

document.addEventListener('input', function(e) {
    var sourceOpacityInput = e.target.closest('[data-source-preview-opacity-range], [data-source-preview-opacity-number]');
    if (sourceOpacityInput) {
        var sourceOpacityRoot = sourceOpacityInput.closest('[data-source-visual-editor=\"1\"]');
        var sourceOpacityLayer = sourceOpacityRoot ?
            localCourseBannerBuilderGetSelectedSourcePreviewLayer(sourceOpacityRoot) :
            null;
        var sourceOpacityPanel = sourceOpacityInput.closest('[data-source-preview-opacity-panel=\"1\"]');
        if (sourceOpacityRoot && sourceOpacityLayer && sourceOpacityPanel) {
            var sourceOpacityValue = localCourseBannerBuilderClampPercent(sourceOpacityInput.value, 100);
            localCourseBannerBuilderSetOpacityPanelValue(sourceOpacityPanel, sourceOpacityValue);
            var sourceOpacityState = localCourseBannerBuilderGetSourcePreviewLayerState(sourceOpacityLayer);
            if (sourceOpacityState) {
                sourceOpacityState.imageopacity = sourceOpacityValue;
                localCourseBannerBuilderSetSourcePreviewLayerState(sourceOpacityLayer, sourceOpacityState);
                localCourseBannerBuilderSyncSourcePreviewLayer(sourceOpacityRoot, sourceOpacityLayer);
                localCourseBannerBuilderUpdateSourcePreviewRow(sourceOpacityRoot, sourceOpacityState);
                localCourseBannerBuilderSyncSourcePreviewPayload(sourceOpacityRoot);
            }
        }
        return;
    }

    var search = e.target.closest('[data-action=\"local-course-banner-builder-filter-sources\"]');
    if (!search) {
        return;
    }
    var target = document.querySelector(search.getAttribute('data-target'));
    if (!target) {
        return;
    }
    var query = search.value.toLowerCase().trim();
    var visibleCount = 0;
    Array.prototype.slice.call(target.querySelectorAll('[data-source-option]')).forEach(function(option) {
        var matches = !query || (option.getAttribute('data-search') || option.textContent).toLowerCase().indexOf(query) !== -1;
        option.hidden = !matches;
        if (matches) {
            visibleCount++;
        }
    });
    var counter = document.querySelector(search.getAttribute('data-counter'));
    if (counter) {
        counter.textContent = visibleCount;
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.local-course-banner-builder-source-dropdown-search')) {
        e.stopPropagation();
        return;
    }

    var option = e.target.closest('[data-source-option]');
    if (!option || option.disabled) {
        return;
    }
    var dropdown = option.closest('[data-source-dropdown]');
    if (!dropdown) {
        return;
    }
    var input = document.querySelector(dropdown.getAttribute('data-input'));
    var label = dropdown.querySelector('[data-source-dropdown-label]');
    if (input) {
        input.value = option.getAttribute('data-value');
    }
    if (label) {
        label.textContent = option.getAttribute('data-label') || option.textContent.trim();
    }
    if ((dropdown.getAttribute('data-source-dropdown') || '') === 'sourceparent') {
        var rootInput = document.querySelector('#id_sourceisroot');
        var rootButton = document.querySelector('[data-action=\"local-course-banner-builder-toggle-root-source\"][data-input=\"#id_sourceisroot\"]');
        var hasParent = (option.getAttribute('data-value') || '') !== '';
        if (rootInput) {
            rootInput.value = hasParent ? '0' : '1';
        }
        if (rootButton) {
            rootButton.classList.toggle('btn-primary', !hasParent);
            rootButton.classList.toggle('btn-outline-secondary', hasParent);
            rootButton.setAttribute('aria-pressed', hasParent ? 'false' : 'true');
        }
    }
    localCourseBannerBuilderSyncSourceDropdownButton(dropdown);
    localCourseBannerBuilderSyncSourceSubmit(dropdown);
    Array.prototype.slice.call(dropdown.querySelectorAll('[data-source-option]')).forEach(function(item) {
        item.classList.toggle('active', item === option);
    });
});

function localCourseBannerBuilderSyncSourceDropdownButton(dropdown) {
    if (!dropdown) {
        return;
    }
    var input = document.querySelector(dropdown.getAttribute('data-input'));
    var label = dropdown.querySelector('[data-source-dropdown-label]');
    if (!input || !label) {
        return;
    }
    var selectedValue = input.value || '';
    var hasSelection = selectedValue !== '' && selectedValue !== '0';
    var escapedValue = window.CSS && CSS.escape ? CSS.escape(selectedValue) : selectedValue.replace(/\"/g, '\\\\\"');
    var selectedOption = dropdown.querySelector('[data-source-option][data-value=\"' + escapedValue + '\"]');

    if (selectedOption) {
        label.textContent = selectedOption.getAttribute('data-label') || selectedOption.textContent.trim();
    }

    label.classList.toggle('active', hasSelection);
    label.classList.toggle('is-selected', hasSelection);
}

function localCourseBannerBuilderToggleRootSource(button) {
    var inputSelector = button ? button.getAttribute('data-input') : '';
    var input = inputSelector ? document.querySelector(inputSelector) : null;
    if (!input) {
        return;
    }
    var enabled = input.value !== '1';
    input.value = enabled ? '1' : '0';
    if (enabled) {
        var form = button.closest('form') || document;
        var parentInput = form.querySelector('#id_sourceparentkey');
        var parentDropdown = form.querySelector('[data-source-dropdown=\"sourceparent\"]');
        if (parentInput) {
            parentInput.value = '';
        }
        if (parentDropdown) {
            localCourseBannerBuilderSyncSourceDropdownButton(parentDropdown);
            Array.prototype.slice.call(parentDropdown.querySelectorAll('[data-source-option]')).forEach(function(item) {
                item.classList.toggle('active', (item.getAttribute('data-value') || '') === '');
            });
            localCourseBannerBuilderSyncSourceSubmit(parentDropdown);
        }
    }
    button.classList.toggle('btn-primary', enabled);
    button.classList.toggle('btn-outline-secondary', !enabled);
    button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
}

function localCourseBannerBuilderGetSourceChainDescendants(parentKey) {
    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-source-chain-row=\"1\"]'));
    var descendants = [];
    var collect = function(key) {
        rows.forEach(function(row) {
            if ((row.getAttribute('data-source-chain-parent') || '') !== key) {
                return;
            }
            descendants.push(row);
            collect(row.getAttribute('data-source-chain-key') || '');
        });
    };
    collect(parentKey);
    return descendants;
}

function localCourseBannerBuilderGetSourceChainRowsByKey() {
    var map = {};
    Array.prototype.slice.call(document.querySelectorAll('[data-source-chain-row=\"1\"]')).forEach(function(row) {
        var key = row.getAttribute('data-source-chain-key') || '';
        if (key) {
            map[key] = row;
        }
    });
    return map;
}

function localCourseBannerBuilderSyncSourceChainRowVisibility() {
    var rowsByKey = localCourseBannerBuilderGetSourceChainRowsByKey();
    Array.prototype.slice.call(document.querySelectorAll('[data-source-chain-row=\"1\"]')).forEach(function(row) {
        var parentKey = row.getAttribute('data-source-chain-parent') || '';
        var isHidden = false;

        while (parentKey) {
            var parentRow = rowsByKey[parentKey];
            if (!parentRow) {
                break;
            }
            var parentToggle = parentRow.querySelector('[data-action=\"local-course-banner-builder-toggle-source-chain\"]');
            if (parentToggle && parentToggle.getAttribute('aria-expanded') === 'false') {
                isHidden = true;
                break;
            }
            parentKey = parentRow.getAttribute('data-source-chain-parent') || '';
        }

        row.hidden = isHidden;
    });
}

function localCourseBannerBuilderToggleSourceChain(button, forceCollapsed) {
    var key = button ? (button.getAttribute('data-source-chain-toggle') || '') : '';
    if (!key) {
        return;
    }
    var currentlyExpanded = button.getAttribute('aria-expanded') !== 'false';
    var collapsed = typeof forceCollapsed === 'boolean' ? forceCollapsed : currentlyExpanded;
    button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    var icon = button.querySelector('.fa');
    if (icon) {
        icon.classList.toggle('fa-chevron-right', collapsed);
        icon.classList.toggle('fa-chevron-down', !collapsed);
    }
    localCourseBannerBuilderSyncSourceChainRowVisibility();
}

function localCourseBannerBuilderToggleAllSourceChains(button) {
    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-source-chain-row=\"1\"]'));
    var anyExpanded = Array.prototype.slice.call(document.querySelectorAll(
        '[data-action=\"local-course-banner-builder-toggle-source-chain\"][aria-expanded=\"true\"]'
    )).length > 0;
    var collapse = anyExpanded;
    Array.prototype.slice.call(document.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-source-chain\"]')).forEach(function(toggle) {
        localCourseBannerBuilderToggleSourceChain(toggle, collapse);
    });
    if (!collapse) {
        rows.forEach(function(row) {
            row.hidden = false;
        });
    } else {
        localCourseBannerBuilderSyncSourceChainRowVisibility();
    }
    if (button) {
        var icon = button.querySelector('.fa');
        var label = button.querySelector('span');
        if (icon) {
            icon.classList.toggle('fa-compress', collapse);
            icon.classList.toggle('fa-expand', !collapse);
        }
        if (label) {
            label.textContent = collapse ?
                (button.getAttribute('data-expand-label') || 'Expand all') :
                (button.getAttribute('data-collapse-label') || 'Collapse all');
        }
    }
}

function localCourseBannerBuilderEnsureSourceChainPreviewModal() {
    var modal = document.getElementById('local-course-banner-builder-source-chain-preview-modal');
    if (modal) {
        return modal;
    }
    modal = document.createElement('div');
    modal.id = 'local-course-banner-builder-source-chain-preview-modal';
    modal.className = 'modal fade local-course-banner-builder-source-chain-preview-modal';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML =
        '<div class=\"modal-dialog modal-xl\" role=\"document\">' +
            '<div class=\"modal-content\">' +
                '<div class=\"modal-header d-flex align-items-center\">' +
                    '<h5 class=\"modal-title flex-grow-1\">Source preview</h5>' +
                    '<button type=\"button\" class=\"close ml-auto ms-auto\" data-dismiss=\"modal\" data-bs-dismiss=\"modal\" aria-label=\"Close\">' +
                        '<span aria-hidden=\"true\">&times;</span>' +
                    '</button>' +
                '</div>' +
                '<div class=\"modal-body\" data-source-chain-preview-modal-body=\"1\"></div>' +
            '</div>' +
        '</div>';
    document.body.appendChild(modal);
    return modal;
}

function localCourseBannerBuilderShowSourceChainPreview(button) {
    var url = button ? button.getAttribute('data-preview-url') : '';
    var editUrl = button ? button.getAttribute('data-edit-url') : '';
    var editLabel = button ? button.getAttribute('data-edit-label') : '';
    if (!url) {
        return;
    }
    var modal = localCourseBannerBuilderEnsureSourceChainPreviewModal();
    var body = modal.querySelector('[data-source-chain-preview-modal-body=\"1\"]');
    if (body) {
        body.innerHTML = '<div class=\"text-center p-4\">' +
            '<span class=\"loading-icon icon-no-margin\">' +
            '<i class=\"icon fa fa-spinner fa-spin fa-fw\" aria-hidden=\"true\"></i>' +
            '</span></div>';
    }
    localCourseBannerBuilderShowModal(modal);
    fetch(url, {
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).then(function(response) {
        if (!response.ok) {
            throw new Error(localCourseBannerBuilderGetJsString(
                'unabletoloadsourcepreview',
                'Unable to load source preview'
            ));
        }
        return response.text();
    }).then(function(html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var panel = doc.querySelector('[data-source-visual-editor=\"1\"]');
        if (!body) {
            return;
        }
        if (!panel) {
            body.innerHTML = '<p class=\"text-muted mb-0\">' +
                localCourseBannerBuilderGetJsString('previewunavailable', 'Preview unavailable.') +
                '</p>';
            return;
        }
        Array.prototype.slice.call(panel.querySelectorAll(
            '.local-course-banner-builder-source-preview-controls,' +
            '.local-course-banner-builder-source-preview-primary-actions,' +
            '.local-course-banner-builder-source-preview-bottom-row,' +
            '.local-course-banner-builder-source-preview-filmstrip,' +
            'form'
        )).forEach(function(node) {
            node.remove();
        });
        panel.setAttribute('data-source-preview-readonly', '1');
        panel.classList.add('local-course-banner-builder-source-preview-readonly');
        body.innerHTML = '';
        body.appendChild(document.importNode(panel, true));
        if (editUrl) {
            var actionBar = document.createElement('div');
            actionBar.className = 'local-course-banner-builder-source-chain-preview-actions';
            var editButton = document.createElement('a');
            editButton.className = 'btn btn-primary local-course-banner-builder-source-preview-button';
            editButton.href = editUrl;
            var editIcon = document.createElement('i');
            editIcon.className = 'fa fa-pen me-2';
            editIcon.setAttribute('aria-hidden', 'true');
            var editText = document.createElement('span');
            editText.textContent = editLabel || localCourseBannerBuilderGetJsString('editsourcebutton', 'Edit source');
            editButton.appendChild(editIcon);
            editButton.appendChild(editText);
            actionBar.appendChild(editButton);
            body.appendChild(actionBar);
        }
        localCourseBannerBuilderInitSourceVisualEditor(body);
        localCourseBannerBuilderInitPopovers(body);
    }).catch(function(error) {
        if (body) {
            body.innerHTML = '<p class=\"text-danger mb-0\">' + (error.message ||
                localCourseBannerBuilderGetJsString('unabletoloadsourcepreview', 'Unable to load source preview')) + '</p>';
        }
    });
}

function localCourseBannerBuilderSyncSourceSubmit(dropdown) {
    if (!dropdown) {
        return;
    }
    var form = dropdown.closest('form');
    var input = document.querySelector(dropdown.getAttribute('data-input'));
    var submit = form ? form.querySelector('[data-action=\"local-course-banner-builder-submit-source\"]') : null;
    if (!submit || !input) {
        return;
    }
    var selectedValue = input.value || '';
    var hasSelection = selectedValue !== '' && selectedValue !== '0';
    submit.disabled = !hasSelection;
    submit.classList.toggle('disabled', !hasSelection);
    submit.setAttribute('aria-disabled', hasSelection ? 'false' : 'true');
}

function localCourseBannerBuilderSyncSelectionButton() {
    var toggle = document.querySelector('[data-action=\"local-course-banner-builder-toggle-selection\"]');
    if (!toggle) {
        return;
    }
    var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.local-course-banner-builder-layer-select'));
    var allChecked = checkboxes.length > 0 && checkboxes.every(function(checkbox) {
        return checkbox.checked;
    });
    toggle.textContent = allChecked ? toggle.getAttribute('data-deselect-all-label') : toggle.getAttribute('data-select-all-label');
}

function localCourseBannerBuilderSyncSourcePreviewEnabledState(input) {
    if (!input || !input.name) {
        return;
    }
    var match = input.name.match(/^isenabled_inline\\[(.+)\\]$/);
    if (!match) {
        return;
    }
    var layerId = match[1];
    var enabled = !!input.checked;
    Array.prototype.slice.call(document.querySelectorAll('[data-source-visual-editor=\"1\"]')).forEach(function(root) {
        var layer = root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-layer-id=\"' + layerId + '\"]');
        if (layer) {
            layer.setAttribute('data-preview-enabled', enabled ? '1' : '0');
            layer.classList.toggle('local-course-banner-builder-source-preview-layer--disabled', !enabled);
            if (!enabled) {
                layer.classList.remove('local-course-banner-builder-source-preview-layer--selected');
                layer.removeAttribute('data-preview-current-layer');
            }
        }

        var border = root.querySelector('[data-source-preview-border=\"1\"][data-source-preview-border-id=\"' + layerId + '\"]');
        if (border) {
            border.setAttribute('data-preview-enabled', enabled ? '1' : '0');
            border.classList.toggle('local-course-banner-builder-source-preview-border--disabled', !enabled);
        }

        if (layer && enabled && !root.querySelector('.local-course-banner-builder-source-preview-layer--selected')) {
            localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
        } else if (layer && !enabled) {
            var next = localCourseBannerBuilderGetFirstEnabledSourcePreviewLayer(root);
            if (next) {
                localCourseBannerBuilderSelectSourcePreviewLayer(root, next);
            }
        }
        localCourseBannerBuilderSyncSourcePreviewDeleteButton(root);
        localCourseBannerBuilderSyncSourcePreviewFitButton(root);
        localCourseBannerBuilderSyncSourcePreviewFillButton(root);
        localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
        localCourseBannerBuilderSyncSourcePreviewTopLayerButton(root);
        localCourseBannerBuilderSyncSourcePreviewThumbnails(root);
        localCourseBannerBuilderSyncSourcePreviewPayload(root);
    });
}

function localCourseBannerBuilderSyncSourcePreviewFitOverrideState(select) {
    if (!select || !select.name) {
        return;
    }
    var match = select.name.match(/^fitmodeoverride_inline\[(.+)\]$/);
    if (!match) {
        return;
    }
    var layerId = match[1];
    Array.prototype.slice.call(document.querySelectorAll('[data-source-visual-editor=\"1\"]')).forEach(function(root) {
        var layer = root.querySelector(
            '[data-source-preview-layer=\"1\"][data-source-preview-layer-id=\"' + layerId + '\"]'
        );
        if (!layer || localCourseBannerBuilderIsSourcePreviewReadonly(root)) {
            return;
        }
        var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
        if (!state) {
            return;
        }
        state.fitmodeoverride = select.value || '';
        if (state.fitmodeoverride === 'cover' || state.fitmodeoverride === 'original') {
            state.customsizekeepaspect = true;
        } else if (state.fitmodeoverride === 'bannerfit' || state.fitmodeoverride === '') {
            state.customsizekeepaspect = false;
        }
        localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
        localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
        localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
        localCourseBannerBuilderSyncSourcePreviewPayload(root);
        localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
        localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
    });
}

function localCourseBannerBuilderSyncBulkFields() {
    var filemanager = document.querySelector('#fitem_id_bannerimage_filemanager');
    var namefield = document.querySelector('#id_name');
    var sortfield = document.querySelector('#id_sortorder');
    if (!filemanager || !namefield || !sortfield) {
        return;
    }

    var files = filemanager.querySelectorAll('.fp-file:not(.fp-folder)');
    var isBulkUpload = files.length > 1;
    [namefield, sortfield].forEach(function(field) {
        field.disabled = isBulkUpload;
        field.classList.toggle('local-course-banner-builder-input-disabled', isBulkUpload);
        if (isBulkUpload) {
            field.setAttribute('aria-disabled', 'true');
        } else {
            field.removeAttribute('aria-disabled');
        }
    });
}

function localCourseBannerBuilderSyncLayerSortOrders() {
    Array.prototype.slice.call(document.querySelectorAll('.local-course-banner-builder-layer-sortable')).forEach(function(tbody) {
        localCourseBannerBuilderEnforceLockedLayerOrder(tbody);
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row'));
        rows.forEach(function(row, index) {
            var input = row.querySelector('.local-course-banner-builder-sort-input');
            var display = row.querySelector('[data-sort-display]');
            if (input) {
                input.value = index;
            }
            if (display) {
                display.textContent = index;
            }
        });
    });
    Array.prototype.slice.call(document.querySelectorAll('[data-source-visual-editor=\"1\"]')).forEach(function(root) {
        localCourseBannerBuilderSyncSourcePreviewOrder(root);
        localCourseBannerBuilderSyncSourcePreviewDeleteButton(root);
        localCourseBannerBuilderSyncSourcePreviewFitButton(root);
        localCourseBannerBuilderSyncSourcePreviewFillButton(root);
        localCourseBannerBuilderSyncSourcePreviewThumbnails(root);
    });
}

function localCourseBannerBuilderEnforceLockedLayerOrder(tbody) {
    if (!tbody) {
        return;
    }
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row'));
    var normalRows = rows.filter(function(row) {
        return !row.classList.contains('local-course-banner-builder-layer-row--border');
    });
    var borderRows = rows.filter(function(row) {
        return row.classList.contains('local-course-banner-builder-layer-row--border');
    });

    normalRows.concat(borderRows).forEach(function(row) {
        tbody.appendChild(row);
    });
}

function localCourseBannerBuilderGetLayerScope(scope) {
    if (scope && scope.matches && scope.matches('form.mform')) {
        return scope;
    }
    if (scope && scope.closest) {
        var parentForm = scope.closest('form.mform');
        if (parentForm) {
            return parentForm;
        }
    }
    var visibleModalForm = document.querySelector('.modal.show form.mform');
    if (visibleModalForm) {
        return visibleModalForm;
    }
    return document.querySelector('#local-course-banner-builder-add-layer-modal form.mform, form.mform');
}

function localCourseBannerBuilderSyncBorderSideGroup(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerScope) {
        return;
    }
    var allToggle = layerScope.querySelector('[data-border-side-all=\"1\"][type=\"checkbox\"]');
    var sideToggles = Array.prototype.slice.call(layerScope.querySelectorAll('[data-border-side][type=\"checkbox\"]'));
    if (!allToggle || !sideToggles.length) {
        return;
    }
    var enabledSides = sideToggles.filter(function(input) {
        return !input.disabled;
    });
    allToggle.checked = enabledSides.length > 0 && enabledSides.every(function(input) {
        return input.checked;
    });
    localCourseBannerBuilderSyncBorderSidesValue(layerScope);
}

function localCourseBannerBuilderGetBorderSides(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    var sides = {};
    ['top', 'right', 'bottom', 'left'].forEach(function(side) {
        var input = layerScope ? layerScope.querySelector('[data-border-side=\"' + side + '\"][type=\"checkbox\"]') : null;
        sides[side] = !!(input && input.checked && !input.disabled);
    });
    return sides;
}

function localCourseBannerBuilderSyncBorderSidesValue(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    var hiddenInput = layerScope ? layerScope.querySelector('#id_bordersidesvalue') : null;
    if (!hiddenInput) {
        return;
    }
    var selected = [];
    ['top', 'right', 'bottom', 'left'].forEach(function(side) {
        var input = layerScope.querySelector('[data-border-side=\"' + side + '\"][type=\"checkbox\"]');
        if (input && input.checked) {
            selected.push(side);
        }
    });
    hiddenInput.value = selected.join(',');
}

function localCourseBannerBuilderApplyStoredBorderSides(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    var hiddenInput = layerScope ? layerScope.querySelector('#id_bordersidesvalue') : null;
    if (!hiddenInput) {
        return;
    }
    var raw = (hiddenInput.value || '').trim();
    var wanted = raw ? raw.split(',').map(function(side) {
        return side.trim();
    }).filter(Boolean) : [];

    Array.prototype.slice.call(layerScope.querySelectorAll('[data-border-side][type=\"checkbox\"]')).forEach(function(input) {
        var side = input.getAttribute('data-border-side');
        input.checked = wanted.indexOf(side) !== -1;
    });
    var allToggle = layerScope.querySelector('[data-border-side-all=\"1\"][type=\"checkbox\"]');
    if (allToggle) {
        allToggle.checked = ['top', 'right', 'bottom', 'left'].every(function(side) {
            return wanted.indexOf(side) !== -1;
        });
    }
    localCourseBannerBuilderSyncBorderSidesValue(layerScope);
    localCourseBannerBuilderSyncBorderSideGroup(layerScope);
    localCourseBannerBuilderSyncBorderRoundedField(layerScope);
}

function localCourseBannerBuilderPopulateBorderSidesFromValue(rawValue, scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerScope) {
        return;
    }
    var wanted = (rawValue || '').trim();
    var sides = wanted ? wanted.split(',').map(function(side) {
        return side.trim();
    }).filter(Boolean) : [];

    Array.prototype.slice.call(layerScope.querySelectorAll('[data-border-side][type=\"checkbox\"]')).forEach(function(input) {
        var side = input.getAttribute('data-border-side');
        input.checked = sides.indexOf(side) !== -1;
    });

    var allToggle = layerScope.querySelector('[data-border-side-all=\"1\"][type=\"checkbox\"]');
    if (allToggle) {
        allToggle.checked = ['top', 'right', 'bottom', 'left'].every(function(side) {
            return sides.indexOf(side) !== -1;
        });
    }

    var hiddenInput = layerScope.querySelector('#id_bordersidesvalue');
    if (hiddenInput) {
        hiddenInput.value = sides.join(',');
    }
    localCourseBannerBuilderSyncBorderSideGroup(layerScope);
    localCourseBannerBuilderSyncBorderRoundedField(layerScope);
}

function localCourseBannerBuilderExtractBorderSidesFromForm(form) {
    if (!form) {
        return [];
    }
    var sides = [];
    ['top', 'right', 'bottom', 'left'].forEach(function(side) {
        var selector = '[data-border-side=\"' + side + '\"][type=\"checkbox\"], input[type=\"checkbox\"][name=\"bordersidesgroup[bordersides[' + side + ']]\"]';
        var input = form.querySelector(selector);
        if (input && input.checked) {
            sides.push(side);
        }
    });
    return sides;
}

function localCourseBannerBuilderApplyStoredBorderStateDeferred(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    localCourseBannerBuilderApplyStoredBorderSides(layerScope);
    window.setTimeout(function() {
        localCourseBannerBuilderApplyStoredBorderSides(layerScope);
    }, 0);
    window.requestAnimationFrame(function() {
        localCourseBannerBuilderApplyStoredBorderSides(layerScope);
    });
}

function localCourseBannerBuilderSetBorderSidesFromAll(checked, scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    Array.prototype.slice.call(layerScope.querySelectorAll('[data-border-side][type=\"checkbox\"]')).forEach(function(input) {
        if (!input.disabled) {
            input.checked = checked;
        }
    });
}

function localCourseBannerBuilderSyncBorderRoundedField(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    var roundedInput = layerScope ? layerScope.querySelector('[data-border-inner-rounded=\"1\"][type=\"checkbox\"]') : null;
    if (!roundedInput) {
        return;
    }
    var wrapper = roundedInput.closest('.fitem, .form-group, .mb-3');
    var sides = localCourseBannerBuilderGetBorderSides(layerScope);
    var showRounded = (sides.top && sides.left) ||
        (sides.top && sides.right) ||
        (sides.bottom && sides.left) ||
        (sides.bottom && sides.right);

    roundedInput.disabled = !showRounded;
    if (!showRounded) {
        roundedInput.checked = false;
    }
    if (wrapper) {
        wrapper.classList.toggle('local-course-banner-builder-option-disabled', !showRounded);
        wrapper.setAttribute('aria-hidden', 'false');
    }
}

function localCourseBannerBuilderSyncBorderAccordion(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    var borderAccordion = layerScope ? layerScope.querySelector('[data-border-accordion=\"1\"]') : null;
    var borderToggle = layerScope ? layerScope.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]') : null;
    if (!borderAccordion || !borderToggle) {
        return;
    }
    if (borderToggle.checked) {
        borderAccordion.open = true;
    }
}

function localCourseBannerBuilderSetDisabledState(container, disabled, exceptSelector) {
    if (!container) {
        return;
    }
    container.classList.toggle('local-course-banner-builder-is-disabled', !!disabled);
    container.setAttribute('aria-disabled', disabled ? 'true' : 'false');
}

function localCourseBannerBuilderPlaceExistingBorderNote(layerForm, note, borderToggle) {
    if (!layerForm || !note || !borderToggle || note.dataset.inlinePlaced === '1') {
        return;
    }
    var toggleItem = borderToggle.closest('.fitem, .form-group, .mb-3, .row');
    var target = toggleItem ? (toggleItem.querySelector('.felement, .col-md-9, .form-check') || toggleItem) : null;
    if (!target) {
        return;
    }
    target.appendChild(note);
    note.dataset.inlinePlaced = '1';
    var originalItem = layerForm.querySelector('#fitem_id_borderenabled_existing_notice');
    if (originalItem) {
        originalItem.hidden = true;
    }
}

function localCourseBannerBuilderMoveLayerPreviewToTop(layerForm) {
    if (!layerForm) {
        return;
    }
    var previewItem = layerForm.querySelector('#fitem_id_layerpreview, #fitem_id_imagepreview, #fitem_id_borderpreview');
    if (!previewItem || !previewItem.parentNode) {
        return;
    }
    var layerTypeItem = layerForm.querySelector('#fitem_id_layertypechoice');
    if (layerTypeItem && !layerTypeItem.hidden && layerTypeItem.offsetParent !== null &&
            layerTypeItem.parentNode === previewItem.parentNode) {
        var firstVisibleForType = Array.prototype.slice.call(layerForm.children).find(function(child) {
            return child !== previewItem && child !== layerTypeItem && child.offsetParent !== null;
        });
        previewItem.parentNode.insertBefore(previewItem, firstVisibleForType || layerForm.firstChild);
        localCourseBannerBuilderEnsureLayerTypeChoice(layerForm, previewItem);
        localCourseBannerBuilderMoveLayerEssentialsUnderPreview(layerForm, previewItem);
        return;
    }
    if (layerForm.dataset.previewMovedToTop === '1') {
        localCourseBannerBuilderEnsureLayerTypeChoice(layerForm, previewItem);
        return;
    }
    var firstVisible = Array.prototype.slice.call(layerForm.children).find(function(child) {
        return child !== previewItem && child.offsetParent !== null;
    });
    previewItem.parentNode.insertBefore(previewItem, firstVisible || layerForm.firstChild);
    layerForm.dataset.previewMovedToTop = '1';
    localCourseBannerBuilderEnsureLayerTypeChoice(layerForm, previewItem);
    localCourseBannerBuilderMoveLayerEssentialsUnderPreview(layerForm, previewItem);
}

function localCourseBannerBuilderCreateLayerTypeButton(label, icon, type) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-outline-secondary';
    button.setAttribute('data-layer-type-option', type);
    button.setAttribute('aria-pressed', 'false');
    button.innerHTML = '<i class=\"fa ' + icon + ' me-2\" aria-hidden=\"true\"></i><span>' + label + '</span>';
    return button;
}

function localCourseBannerBuilderCreateLayerTypeChoice(layerForm, borderToggle) {
    var item = document.createElement('div');
    var target = document.createElement('div');
    var choice = document.createElement('div');
    var group = document.createElement('div');
    var imageButton = localCourseBannerBuilderCreateLayerTypeButton(
        localCourseBannerBuilderGetJsString('layertype:image', 'Image'),
        'fa-image',
        'image'
    );
    var borderButton = localCourseBannerBuilderCreateLayerTypeButton(
        localCourseBannerBuilderGetJsString('layertype:border', 'Border'),
        'fa-border-all',
        'border'
    );
    var overlayButton = localCourseBannerBuilderCreateLayerTypeButton(
        localCourseBannerBuilderGetJsString('layertype:overlay', 'Overlay'),
        'fa-adjust',
        'overlay'
    );
    var warning = document.createElement('span');
    var overlayWarning = document.createElement('span');
    item.id = 'fitem_id_layertypechoice';
    item.className = 'mb-3 row fitem femptylabel local-course-banner-builder-layer-type-choice-fallback';
    target.className = 'col-md-9 felement';
    choice.className = 'local-course-banner-builder-layer-type-choice';
    group.className = 'btn-group local-course-banner-builder-layer-type-toggle';
    group.setAttribute('role', 'group');
    group.setAttribute('data-layer-type-toggle', '1');
    warning.className = 'local-course-banner-builder-layer-type-warning text-danger d-none';
    warning.setAttribute('data-layer-type-border-warning', '1');
    overlayWarning.className = 'local-course-banner-builder-layer-type-warning text-danger d-none';
    overlayWarning.setAttribute('data-layer-type-overlay-warning', '1');
    overlayWarning.textContent = localCourseBannerBuilderGetJsString(
        'sourcealreadyhasoverlayinline',
        'An overlay layer already exists in this source'
    );
    [imageButton, borderButton].forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            localCourseBannerBuilderSelectLayerType(layerForm, button.getAttribute('data-layer-type-option'), true);
        });
        button.dataset.layerTypeBound = '1';
        group.appendChild(button);
    });
    [overlayButton].forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            localCourseBannerBuilderSelectLayerType(layerForm, 'overlay', true);
        });
        button.dataset.layerTypeBound = '1';
        group.appendChild(button);
    });
    choice.appendChild(group);
    choice.appendChild(warning);
    choice.appendChild(overlayWarning);
    target.appendChild(choice);
    item.appendChild(target);
    return item;
}

function localCourseBannerBuilderIsEditLayerForm(layerForm) {
    var elementIdInput = layerForm ? layerForm.querySelector('#id_elementid') : null;
    return !!(elementIdInput && parseInt(elementIdInput.value || '0', 10) > 0);
}

function localCourseBannerBuilderIsEditBorderForm(layerForm) {
    var currentIsBorderLayerInput = layerForm ? layerForm.querySelector('#id_currentisborderlayer') : null;
    return !!(localCourseBannerBuilderIsEditLayerForm(layerForm) &&
        ((currentIsBorderLayerInput && parseInt(currentIsBorderLayerInput.value || '0', 10) > 0) ||
        layerForm.getAttribute('data-edit-border-locked') === '1'));
}

function localCourseBannerBuilderIsEditOverlayForm(layerForm) {
    var currentIsOverlayLayerInput = layerForm ? layerForm.querySelector('#id_currentisoverlaylayer') : null;
    return !!(localCourseBannerBuilderIsEditLayerForm(layerForm) &&
        currentIsOverlayLayerInput && parseInt(currentIsOverlayLayerInput.value || '0', 10) > 0);
}

function localCourseBannerBuilderIsEditImageForm(layerForm) {
    return !!(localCourseBannerBuilderIsEditLayerForm(layerForm) &&
        !localCourseBannerBuilderIsEditBorderForm(layerForm) &&
        !localCourseBannerBuilderIsEditOverlayForm(layerForm));
}

function localCourseBannerBuilderGetOverlayToggle(layerForm) {
    if (!layerForm) {
        return null;
    }
    return layerForm.querySelector('#id_overlayenabled[type=\"checkbox\"]') ||
        layerForm.querySelector('input[name=\"overlayenabled\"][type=\"checkbox\"]') ||
        layerForm.querySelector('[data-overlay-toggle=\"1\"][type=\"checkbox\"]') ||
        layerForm.querySelector('#id_overlayenabled[type=\"hidden\"]') ||
        layerForm.querySelector('input[name=\"overlayenabled\"][type=\"hidden\"]') ||
        layerForm.querySelector('[data-overlay-toggle=\"1\"][type=\"hidden\"]') ||
        layerForm.querySelector('[data-overlay-toggle=\"1\"]');
}

function localCourseBannerBuilderCreateOverlayFallbackField(container, label, input) {
    var title = document.createElement('label');
    title.className = 'local-course-banner-builder-slideshow-side-title';
    title.textContent = label;
    if (input.id) {
        title.setAttribute('for', input.id);
    }
    container.appendChild(title);
    container.appendChild(input);
    return input;
}

function localCourseBannerBuilderSetSegmentedChoice(buttons, activeValue, attribute) {
    buttons.forEach(function(button) {
        var active = button.getAttribute(attribute) === activeValue;
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-secondary', !active);
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function localCourseBannerBuilderCreateSegmentedChoice(options, activeValue, attribute, onSelect) {
    var group = document.createElement('div');
    group.className = 'btn-group local-course-banner-builder-overlay-segmented-choice';
    group.setAttribute('role', 'group');
    options.forEach(function(option) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-secondary';
        button.setAttribute(attribute, option.value);
        button.textContent = option.label;
        button.addEventListener('click', function(event) {
            event.preventDefault();
            onSelect(option.value);
        });
        group.appendChild(button);
    });
    localCourseBannerBuilderSetSegmentedChoice(
        Array.prototype.slice.call(group.querySelectorAll('button')),
        activeValue,
        attribute
    );
    return group;
}

function localCourseBannerBuilderEnsureOverlayFallbackControls(layerForm) {
    if (!layerForm || layerForm.querySelector('[data-overlay-section=\"1\"]')) {
        return;
    }
    var section = document.createElement('details');
    var summary = document.createElement('summary');
    var summaryTitle = document.createElement('span');
    section.className = 'local-course-banner-builder-advanced-accordion local-course-banner-builder-overlay-accordion';
    section.setAttribute('data-overlay-section', '1');
    section.setAttribute('data-overlay-accordion', '1');
    section.setAttribute('data-create-overlay-locked', '0');
    section.setAttribute(
        'data-overlay-site-only',
        document.querySelector('.local-course-banner-builder-admin--site') ? '1' : '0'
    );
    section.setAttribute('data-overlay-inherit-color', '#000000');
    section.setAttribute('data-overlay-inherit-opacity', '38');
    section.hidden = true;
    summaryTitle.className = 'local-course-banner-builder-border-summary-title';
    summaryTitle.textContent = localCourseBannerBuilderGetJsString('overlaysettings', 'Overlay settings');
    summary.appendChild(summaryTitle);
    section.appendChild(summary);

    if (!localCourseBannerBuilderGetOverlayToggle(layerForm)) {
        var overlayEnabled = document.createElement('input');
        overlayEnabled.type = 'hidden';
        overlayEnabled.id = 'id_overlayenabled';
        overlayEnabled.name = 'overlayenabled';
        overlayEnabled.value = '0';
        overlayEnabled.setAttribute('data-overlay-toggle', '1');
        section.appendChild(overlayEnabled);
    }

    if (!layerForm.querySelector('#id_overlaytarget')) {
        var target = document.createElement('select');
        target.id = 'id_overlaytarget';
        target.name = 'overlaytarget';
        target.className = 'form-control custom-select';
        [
            ['both', 'Banner and slideshow'],
            ['banner', 'Banner only'],
            ['slideshow', 'Slideshow only']
        ].forEach(function(optionData) {
            var option = document.createElement('option');
            option.value = optionData[0];
            option.textContent = optionData[1];
            if (optionData[0] === 'both') {
                option.selected = true;
            }
            target.appendChild(option);
        });
        localCourseBannerBuilderCreateOverlayFallbackField(
            section,
            localCourseBannerBuilderGetJsString('overlaytarget', 'Overlay target'),
            target
        );
    }

    [
        ['overlaybanner', localCourseBannerBuilderGetJsString('overlaybannerappearance', 'Banner overlay'), '25'],
        ['overlayslideshow', localCourseBannerBuilderGetJsString('overlayslideshowappearance', 'Slideshow overlay'), '38']
    ].forEach(function(config) {
        var prefix = config[0];
        var heading = document.createElement('div');
        heading.className = 'local-course-banner-builder-slideshow-side-title mt-2';
        heading.textContent = config[1];
        section.appendChild(heading);
        if (!layerForm.querySelector('#id_' + prefix + 'color')) {
            var color = document.createElement('input');
            color.type = 'text';
            color.id = 'id_' + prefix + 'color';
            color.name = prefix + 'color';
            color.className = 'form-control';
            color.value = '#000000';
            color.setAttribute('data-overlay-color-text', prefix);
            localCourseBannerBuilderCreateOverlayFallbackField(
                section,
                localCourseBannerBuilderGetJsString('overlaycolor', 'Overlay color'),
                color
            );
        }
        if (!layerForm.querySelector('#id_' + prefix + 'opacity')) {
            var opacity = document.createElement('input');
            opacity.type = 'number';
            opacity.id = 'id_' + prefix + 'opacity';
            opacity.name = prefix + 'opacity';
            opacity.className = 'form-control';
            opacity.min = '0';
            opacity.max = '100';
            opacity.step = '1';
            opacity.value = config[2];
            localCourseBannerBuilderCreateOverlayFallbackField(
                section,
                localCourseBannerBuilderGetJsString('overlayopacity', 'Overlay opacity'),
                opacity
            );
        }
    });

    [
        ['overlaytitleabove', localCourseBannerBuilderGetJsString('overlaytitleabove', 'Titles above overlay')],
        ['overlayborderabove', localCourseBannerBuilderGetJsString('overlayborderabove', 'Borders above overlay')]
    ].forEach(function(config) {
        if (layerForm.querySelector('#id_' + config[0])) {
            return;
        }
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = config[0];
        hidden.value = '0';
        var wrapper = document.createElement('label');
        var checkbox = document.createElement('input');
        wrapper.className = 'form-check d-flex align-items-center gap-2 mb-2';
        checkbox.type = 'checkbox';
        checkbox.id = 'id_' + config[0];
        checkbox.name = config[0];
        checkbox.className = 'form-check-input';
        checkbox.value = '1';
        checkbox.checked = true;
        wrapper.appendChild(checkbox);
        wrapper.appendChild(document.createTextNode(config[1]));
        section.appendChild(hidden);
        section.appendChild(wrapper);
    });

    var host = layerForm.querySelector('[data-modal-preview-action-list=\"1\"]');
    if (host) {
        host.appendChild(section);
    } else {
        layerForm.appendChild(section);
    }
}

function localCourseBannerBuilderReadOverlayToggle(layerForm) {
    if (layerForm && layerForm.dataset.selectedLayerType === 'overlay') {
        return true;
    }
    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(layerForm);
    if (!overlayToggle) {
        return false;
    }
    return overlayToggle.type === 'checkbox' ? overlayToggle.checked : overlayToggle.value === '1';
}

function localCourseBannerBuilderSetOverlayToggle(layerForm, checked) {
    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(layerForm);
    if (!overlayToggle || overlayToggle.disabled) {
        return false;
    }
    Array.prototype.slice.call(layerForm.querySelectorAll(
        '#id_overlayenabled, input[name=\"overlayenabled\"], [data-overlay-toggle=\"1\"]'
    )).forEach(function(input) {
        if (!input || input.disabled) {
            return;
        }
        if (input.type === 'checkbox') {
            input.checked = !!checked;
        } else if (typeof input.value !== 'undefined') {
            input.value = checked ? '1' : '0';
        }
    });
    overlayToggle.dispatchEvent(new Event('change', {bubbles: true}));
    return true;
}

function localCourseBannerBuilderGetSelectedLayerType(layerForm) {
    if (!layerForm) {
        return 'image';
    }
    if (layerForm.dataset.selectedLayerType) {
        return layerForm.dataset.selectedLayerType;
    }
    var borderToggle = layerForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    if (localCourseBannerBuilderReadOverlayToggle(layerForm)) {
        layerForm.dataset.selectedLayerType = 'overlay';
    } else if (borderToggle && borderToggle.checked) {
        layerForm.dataset.selectedLayerType = 'border';
    } else {
        layerForm.dataset.selectedLayerType = 'image';
    }
    return layerForm.dataset.selectedLayerType;
}

function localCourseBannerBuilderSetSelectedLayerType(layerForm, type) {
    if (!layerForm) {
        return;
    }
    layerForm.dataset.selectedLayerType = ['image', 'border', 'overlay'].indexOf(type) === -1 ? 'image' : type;
}

function localCourseBannerBuilderOpenModalSidePanel(form, key) {
    var host = form ? form.querySelector('[data-modal-preview-action-list=\"1\"]') : null;
    var panel = key ? form.querySelector('[data-modal-preview-side-panel-key=\"' + key + '\"]') : null;
    var button = key ? form.querySelector('[data-modal-preview-side-panel-target=\"' + key + '\"]') : null;
    if (!host || !panel || !button || button.hidden || button.disabled) {
        return;
    }
    localCourseBannerBuilderCloseOtherModalSidePanels(host, panel);
    localCourseBannerBuilderToggleOpacityPanel(panel, button, true);
}

function localCourseBannerBuilderSelectLayerType(layerForm, nextType, openPanel) {
    if (!layerForm) {
        return;
    }
    if (nextType === 'overlay') {
        localCourseBannerBuilderEnsureOverlayFallbackControls(layerForm);
    }
    var borderToggle = layerForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(layerForm);
    if (!borderToggle) {
        return;
    }
    if (nextType === 'border' && borderToggle.disabled) {
        localCourseBannerBuilderSyncLayerTypeChoice(layerForm);
        return;
    }
    if (nextType === 'overlay' && (!overlayToggle || overlayToggle.disabled)) {
        localCourseBannerBuilderSyncLayerTypeChoice(layerForm);
        return;
    }
    localCourseBannerBuilderSetSelectedLayerType(layerForm, nextType);
    borderToggle.checked = nextType === 'border';
    localCourseBannerBuilderSetOverlayToggle(layerForm, nextType === 'overlay');
    borderToggle.dispatchEvent(new Event('change', {bubbles: true}));
    if (nextType === 'border') {
        var borderAccordion = layerForm.querySelector('[data-border-accordion=\"1\"]');
        if (borderAccordion && !borderAccordion.classList.contains('local-course-banner-builder-disabled')) {
            borderAccordion.open = true;
        }
    } else if (nextType === 'overlay') {
        var overlayAccordion = layerForm.querySelector('[data-overlay-accordion=\"1\"]');
        if (overlayAccordion && !overlayAccordion.classList.contains('local-course-banner-builder-disabled')) {
            overlayAccordion.open = true;
        }
    }
    localCourseBannerBuilderSyncLayerInputModes(layerForm);
    localCourseBannerBuilderSyncModalPreviewActionButtons(layerForm);
    if (openPanel && nextType === 'border') {
        localCourseBannerBuilderOpenModalSidePanel(layerForm, 'borderstyle');
    } else if (openPanel && nextType === 'overlay') {
        localCourseBannerBuilderOpenModalSidePanel(layerForm, 'overlaystyle');
    }
}

document.addEventListener('click', function(e) {
    var button = e.target.closest('[data-layer-type-option]');
    if (!button) {
        return;
    }
    var layerForm = button.closest('form.mform');
    if (!layerForm) {
        return;
    }
    e.preventDefault();
    e.stopPropagation();
    localCourseBannerBuilderSelectLayerType(
        layerForm,
        button.getAttribute('data-layer-type-option') || 'image',
        true
    );
}, true);

function localCourseBannerBuilderEnsureLayerTypeChoice(layerForm, previewItem) {
    if (!layerForm || !previewItem || !previewItem.parentNode) {
        return null;
    }
    var layerTypeItem = layerForm.querySelector('#fitem_id_layertypechoice');
    var borderToggle = layerForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    var choice = layerForm.querySelector('[data-layer-top-choice-row=\"1\"]');
    if (!choice) {
        choice = document.createElement('div');
        choice.className = 'local-course-banner-builder-layer-type-choice';
        choice.setAttribute('data-layer-top-choice-row', '1');
    }
    var group = choice.querySelector('[data-layer-type-toggle=\"1\"]');
    if (!group && layerTypeItem) {
        group = layerTypeItem.querySelector('[data-layer-type-toggle=\"1\"]');
    }
    var warning = choice.querySelector('[data-layer-type-border-warning=\"1\"]');
    if (!warning && layerTypeItem) {
        warning = layerTypeItem.querySelector('[data-layer-type-border-warning=\"1\"]');
    }
    var overlayWarning = choice.querySelector('[data-layer-type-overlay-warning=\"1\"]');
    if (!overlayWarning && layerTypeItem) {
        overlayWarning = layerTypeItem.querySelector('[data-layer-type-overlay-warning=\"1\"]');
    }
    if (!group && borderToggle && !localCourseBannerBuilderIsEditLayerForm(layerForm)) {
        var fallback = localCourseBannerBuilderCreateLayerTypeChoice(layerForm, borderToggle);
        group = fallback.querySelector('[data-layer-type-toggle=\"1\"]');
        warning = fallback.querySelector('[data-layer-type-border-warning=\"1\"]');
    }
    if (group && group.parentNode !== choice) {
        choice.appendChild(group);
    }
    if (warning && warning.parentNode !== choice) {
        choice.appendChild(warning);
    }
    if (overlayWarning && overlayWarning.parentNode !== choice) {
        choice.appendChild(overlayWarning);
    }
    if (choice.parentNode !== previewItem.parentNode || choice.nextSibling !== previewItem) {
        previewItem.parentNode.insertBefore(choice, previewItem);
    }
    if (layerTypeItem) {
        layerTypeItem.hidden = true;
        layerTypeItem.setAttribute('aria-hidden', 'true');
    }
    choice.classList.toggle('local-course-banner-builder-layer-type-choice--edit', localCourseBannerBuilderIsEditLayerForm(layerForm));
    choice.classList.toggle('local-course-banner-builder-layer-type-choice--no-toggle', !group);
    localCourseBannerBuilderSyncLayerTypeChoice(layerForm);
    return choice;
}

function localCourseBannerBuilderEmbedLayerTypeChoiceInPreview(layerForm, previewItem) {
    localCourseBannerBuilderEnsureLayerTypeChoice(layerForm, previewItem);
}

function localCourseBannerBuilderMoveLayerEssentialsUnderPreview(layerForm, previewItem) {
    if (!layerForm || !previewItem || !previewItem.parentNode) {
        return;
    }
    var parent = previewItem.parentNode;
    var anchor = previewItem.nextSibling;
    var identityRow = layerForm.querySelector('[data-layer-identity-row=\"1\"]');
    if (!identityRow) {
        identityRow = document.createElement('div');
        identityRow.className = 'local-course-banner-builder-layer-identity-row';
        identityRow.setAttribute('data-layer-identity-row', '1');
    }
    ['#fitem_id_name', '#fitem_id_sortorder'].forEach(function(selector) {
        var item = layerForm.querySelector(selector);
        if (item && item.parentNode !== identityRow) {
            identityRow.appendChild(item);
        }
    });
    if (identityRow.children.length && (identityRow.parentNode !== parent || identityRow.nextSibling !== anchor)) {
        parent.insertBefore(identityRow, anchor);
    }
    anchor = identityRow.parentNode === parent ? identityRow.nextSibling : anchor;
    ['#fitem_id_bannerimage_filemanager'].forEach(function(selector) {
        var item = layerForm.querySelector(selector);
        if (item && item !== previewItem && (item.parentNode !== parent || item.nextSibling !== anchor)) {
            parent.insertBefore(item, anchor);
        }
        if (item && item.parentNode === parent) {
            anchor = item.nextSibling;
        }
    });
    localCourseBannerBuilderEmbedEnabledToggleInLayerTypeChoice(layerForm);
}

function localCourseBannerBuilderEmbedEnabledToggleInLayerTypeChoice(form) {
    var previewItem = form ? form.querySelector('#fitem_id_layerpreview, #fitem_id_imagepreview, #fitem_id_borderpreview') : null;
    var choice = localCourseBannerBuilderEnsureLayerTypeChoice(form, previewItem);
    var enabledInput = form ? form.querySelector('#id_isenabled') : null;
    var enabledRow = enabledInput ? enabledInput.closest('.fitem, .form-group, .mb-3, .row') : null;
    var enabledHost = form ? form.querySelector('[data-toggle-button-host-for=\"#id_isenabled\"]') : null;
    var enabledButton = enabledHost ? enabledHost.querySelector('[data-toggle-button-for=\"#id_isenabled\"]') : null;
    if (!choice || !enabledInput || !enabledHost || !enabledButton) {
        return;
    }
    enabledButton.setAttribute('data-label-on', localCourseBannerBuilderGetJsString('enablelayer', 'Enable layer'));
    enabledButton.setAttribute('data-label-off', localCourseBannerBuilderGetJsString('enablelayer', 'Enable layer'));
    var help = localCourseBannerBuilderGetJsString(
        'enablelayer_help',
        'When enabled, this layer is included in previews and generated banners.'
    );
    enabledButton.setAttribute('data-toggle', 'popover');
    enabledButton.setAttribute('data-trigger', 'hover');
    enabledButton.setAttribute('data-placement', 'top');
    enabledButton.setAttribute('data-html', 'true');
    enabledButton.setAttribute('data-content', '<div class=\"no-overflow\"><p>' + help + '</p></div>');
    enabledButton.setAttribute('data-local-course-banner-builder-popover-label', help);
    enabledButton.setAttribute('aria-label', help);
    if (enabledHost.parentNode !== choice) {
        choice.insertBefore(enabledHost, choice.firstChild);
    }
    var inputHost = choice.querySelector('[data-layer-enable-input-host=\"1\"]');
    if (!inputHost) {
        inputHost = document.createElement('span');
        inputHost.className = 'local-course-banner-builder-toggle-button-source';
        inputHost.setAttribute('data-layer-enable-input-host', '1');
        choice.insertBefore(inputHost, enabledHost.nextSibling);
    }
    var enabledInputs = [];
    if (enabledRow) {
        Array.prototype.slice.call(enabledRow.querySelectorAll('input[name=\"isenabled\"]')).forEach(function(input) {
            if (enabledInputs.indexOf(input) === -1) {
                enabledInputs.push(input);
            }
        });
    }
    if (enabledInputs.indexOf(enabledInput) === -1) {
        enabledInputs.push(enabledInput);
    }
    enabledInputs.sort(function(a, b) {
        if (a.type === b.type) {
            return 0;
        }
        return a.type === 'hidden' ? -1 : 1;
    }).forEach(function(input) {
        if (input.parentNode !== inputHost || inputHost.lastChild !== input) {
            inputHost.appendChild(input);
        }
    });
    if (typeof localCourseBannerBuilderInitPopovers === 'function') {
        localCourseBannerBuilderInitPopovers(enabledButton);
    }
    choice.classList.add('local-course-banner-builder-layer-type-choice--with-enable');
    if (enabledRow) {
        enabledRow.classList.add('local-course-banner-builder-layer-enable-row-hidden');
        enabledRow.setAttribute('aria-hidden', 'true');
    }
    choice.classList.toggle(
        'local-course-banner-builder-layer-type-choice--no-toggle',
        !choice.querySelector('[data-layer-type-toggle=\"1\"]')
    );
    localCourseBannerBuilderSyncBinaryOptionButton(enabledInput);
}

function localCourseBannerBuilderSyncLayerTypeChoice(layerForm) {
    if (!layerForm) {
        return;
    }
    var borderToggle = layerForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(layerForm);
    var layerTypeToggle = layerForm.querySelector('[data-layer-type-toggle=\"1\"]');
    if (!borderToggle || !layerTypeToggle) {
        return;
    }
    var borderToggleItem = borderToggle.closest('.fitem, .form-group, .mb-3, .row');
    if (borderToggleItem) {
        borderToggleItem.hidden = true;
        borderToggleItem.setAttribute('aria-hidden', 'true');
    }
    var selectedType = localCourseBannerBuilderGetSelectedLayerType(layerForm);
    Array.prototype.slice.call(layerTypeToggle.querySelectorAll('[data-layer-type-option]')).forEach(function(button) {
        var type = button.getAttribute('data-layer-type-option');
        var active = type === selectedType;
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-secondary', !active);
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        if (type === 'border') {
            button.disabled = !!borderToggle.disabled;
            button.setAttribute('aria-disabled', borderToggle.disabled ? 'true' : 'false');
        } else if (type === 'overlay' && overlayToggle) {
            button.disabled = !!overlayToggle.disabled;
            button.setAttribute('aria-disabled', overlayToggle.disabled ? 'true' : 'false');
        }
    });
    var warning = layerForm.querySelector('[data-layer-type-border-warning=\"1\"]');
    if (warning) {
        var borderSection = layerForm.querySelector('[data-border-section=\"1\"]');
        var sourceHasBorderLayerInput = layerForm.querySelector('#id_sourcehasborderlayer');
        var currentIsBorderLayerInput = layerForm.querySelector('#id_currentisborderlayer');
        var elementIdInput = layerForm.querySelector('#id_elementid');
        var sourceHasBorderLayer = !!(sourceHasBorderLayerInput &&
            parseInt(sourceHasBorderLayerInput.value || '0', 10) > 0);
        var createBorderLocked = !!(borderSection &&
            parseInt(borderSection.getAttribute('data-create-border-locked') || '0', 10) > 0);
        var currentIsBorderLayer = !!(currentIsBorderLayerInput &&
            parseInt(currentIsBorderLayerInput.value || '0', 10) > 0);
        var hasExistingElement = !!(elementIdInput && parseInt(elementIdInput.value || '0', 10) > 0);
        var sourceBlocksNewBorder = (sourceHasBorderLayer || createBorderLocked) &&
            !(hasExistingElement && currentIsBorderLayer);
        warning.classList.toggle('d-none', !sourceBlocksNewBorder || borderToggle.checked);
    }
    var overlayWarning = layerForm.querySelector('[data-layer-type-overlay-warning=\"1\"]');
    if (overlayWarning) {
        var overlaySection = layerForm.querySelector('[data-overlay-section=\"1\"]');
        var sourceHasOverlayLayerInput = layerForm.querySelector('#id_sourcehasoverlaylayer');
        var currentIsOverlayLayerInput = layerForm.querySelector('#id_currentisoverlaylayer');
        var overlayElementIdInput = layerForm.querySelector('#id_elementid');
        var sourceHasOverlayLayer = !!(sourceHasOverlayLayerInput &&
            parseInt(sourceHasOverlayLayerInput.value || '0', 10) > 0);
        var createOverlayLocked = !!(overlaySection &&
            parseInt(overlaySection.getAttribute('data-create-overlay-locked') || '0', 10) > 0);
        var currentIsOverlayLayer = !!(currentIsOverlayLayerInput &&
            parseInt(currentIsOverlayLayerInput.value || '0', 10) > 0);
        var hasOverlayElement = !!(overlayElementIdInput && parseInt(overlayElementIdInput.value || '0', 10) > 0);
        var sourceBlocksNewOverlay = (sourceHasOverlayLayer || createOverlayLocked) &&
            !(hasOverlayElement && currentIsOverlayLayer);
        overlayWarning.classList.toggle('d-none', !sourceBlocksNewOverlay || selectedType === 'overlay');
    }
    var imageSection = layerForm.querySelector('[data-image-options-section=\"1\"]');
    if (imageSection) {
        var imageOptionsButton = layerForm.querySelector('[data-modal-preview-side-panel-target=\"imageoptions\"]');
        var isImageType = selectedType === 'image';
        if (imageSection.getAttribute('data-modal-preview-side-panel') === '1') {
            if (!isImageType) {
                imageSection.hidden = true;
                imageSection.classList.add('is-collapsed');
            }
        } else {
            imageSection.hidden = !isImageType;
        }
        if (imageOptionsButton) {
            imageOptionsButton.hidden = !isImageType;
            imageOptionsButton.disabled = false;
            imageOptionsButton.classList.remove('disabled');
            imageOptionsButton.setAttribute('aria-disabled', 'false');
            if (!isImageType) {
                imageOptionsButton.classList.remove('btn-primary', 'active');
                imageOptionsButton.classList.add('btn-outline-secondary');
                imageOptionsButton.setAttribute('aria-expanded', 'false');
            }
        }
    }
}

function localCourseBannerBuilderSyncLayerInputModes(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    var filemanager = layerForm ? layerForm.querySelector('#fitem_id_bannerimage_filemanager') : null;
    var filemanagerItem = filemanager;
    var filemanagerNote = layerForm ? layerForm.querySelector('[data-border-filemanager-note=\"1\"]') : null;
    var filemanagerNoteItem = filemanagerNote ? filemanagerNote.closest('.fitem, .form-group, .mb-3') : null;
    var existingBorderNote = layerForm ? layerForm.querySelector('[data-border-existing-note=\"1\"]') : null;
    var existingBorderNoteItem = existingBorderNote ? existingBorderNote.closest('.fitem, .form-group, .mb-3') : null;
    var existingBorderInlineNote = layerForm ? layerForm.querySelector('[data-border-existing-note-inline=\"1\"]') : null;
    var borderToggle = layerForm ? layerForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]') : null;
    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(layerForm);
    var borderSection = layerForm ? layerForm.querySelector('[data-border-section=\"1\"]') : null;
    var overlaySection = layerForm ? layerForm.querySelector('[data-overlay-section=\"1\"]') : null;
    var borderSummary = borderSection ? borderSection.querySelector('summary') : null;
    var advancedSection = layerForm ? layerForm.querySelector('[data-layer-advanced-section=\"1\"]') : null;
    var hasExistingImageInput = layerForm ? layerForm.querySelector('#id_hasexistingimage') : null;
    var currentIsBorderLayerInput = layerForm ? layerForm.querySelector('#id_currentisborderlayer') : null;
    var currentIsOverlayLayerInput = layerForm ? layerForm.querySelector('#id_currentisoverlaylayer') : null;
    var sourceHasBorderLayerInput = layerForm ? layerForm.querySelector('#id_sourcehasborderlayer') : null;
    var sourceHasOverlayLayerInput = layerForm ? layerForm.querySelector('#id_sourcehasoverlaylayer') : null;
    var elementIdInput = layerForm ? layerForm.querySelector('#id_elementid') : null;
    if (!filemanager || !borderToggle || !borderSection) {
        return;
    }

    var hasFiles = localCourseBannerBuilderGetVisibleDraftFileItems(layerForm).length > 0;
    var hasExistingImage = !!(hasExistingImageInput && parseInt(hasExistingImageInput.value || '0', 10) > 0);
    var currentIsBorderLayer = !!(currentIsBorderLayerInput && parseInt(currentIsBorderLayerInput.value || '0', 10) > 0);
    var currentIsOverlayLayer = !!(currentIsOverlayLayerInput && parseInt(currentIsOverlayLayerInput.value || '0', 10) > 0);
    var sourceHasBorderLayer = !!(sourceHasBorderLayerInput && parseInt(sourceHasBorderLayerInput.value || '0', 10) > 0);
    var sourceHasOverlayLayer = !!(sourceHasOverlayLayerInput && parseInt(sourceHasOverlayLayerInput.value || '0', 10) > 0);
    var createBorderLocked = !!(borderSection && parseInt(borderSection.getAttribute('data-create-border-locked') || '0', 10) > 0);
    var createOverlayLocked = !!(overlaySection && parseInt(overlaySection.getAttribute('data-create-overlay-locked') || '0', 10) > 0);
    var editBorderLocked = !!(layerForm && layerForm.getAttribute('data-edit-border-locked') === '1');
    var hasExistingElement = !!(elementIdInput && parseInt(elementIdInput.value || '0', 10) > 0);
    var hasImage = hasFiles || hasExistingImage;
    var selectedLayerType = localCourseBannerBuilderGetSelectedLayerType(layerForm);
    var overlayChecked = selectedLayerType === 'overlay';
    var isOverlayOnly = !!(overlayChecked && !hasImage);
    var isBorderOnly = selectedLayerType === 'border' && !hasImage && !isOverlayOnly;
    var isExistingBorderLayer = hasExistingElement && (currentIsBorderLayer || editBorderLocked);
    var isExistingOverlayLayer = hasExistingElement && currentIsOverlayLayer;
    var sourceBlocksNewBorder = (sourceHasBorderLayer || createBorderLocked) && !isExistingBorderLayer;
    var sourceBlocksNewOverlay = (sourceHasOverlayLayer || createOverlayLocked) && !isExistingOverlayLayer;
    localCourseBannerBuilderPlaceExistingBorderNote(layerForm, existingBorderNote, borderToggle);

    localCourseBannerBuilderSetDisabledState(filemanager, isBorderOnly || isOverlayOnly, '[data-banner-filemanager=\"1\"]');
    localCourseBannerBuilderSetDisabledState(borderSection, hasImage || isOverlayOnly, '[data-border-toggle=\"1\"]');
    localCourseBannerBuilderSetDisabledState(overlaySection, hasImage || isBorderOnly, '[data-overlay-toggle=\"1\"]');
    localCourseBannerBuilderSetDisabledState(advancedSection, borderToggle.checked || isOverlayOnly, '');
    if (sourceBlocksNewBorder && borderToggle.checked) {
        borderToggle.checked = false;
        localCourseBannerBuilderSetSelectedLayerType(layerForm, 'image');
    }
    if (overlayToggle && sourceBlocksNewOverlay && overlayChecked) {
        localCourseBannerBuilderSetSelectedLayerType(layerForm, 'image');
        localCourseBannerBuilderSetOverlayToggle(layerForm, false);
        isOverlayOnly = false;
    }
    borderToggle.disabled = hasImage || isExistingBorderLayer || sourceBlocksNewBorder;
    borderToggle.setAttribute('aria-disabled', (hasImage || isExistingBorderLayer || sourceBlocksNewBorder) ? 'true' : 'false');
    if (overlayToggle && overlayToggle.type === 'checkbox') {
        overlayToggle.disabled = hasImage || isExistingOverlayLayer || sourceBlocksNewOverlay;
        overlayToggle.setAttribute(
            'aria-disabled',
            (hasImage || isExistingOverlayLayer || sourceBlocksNewOverlay) ? 'true' : 'false'
        );
    }
    if (isExistingOverlayLayer && overlayToggle) {
        if (overlayToggle.type === 'checkbox') {
            overlayToggle.checked = true;
        } else {
            overlayToggle.value = '1';
        }
    }
    if (isExistingBorderLayer) {
        borderToggle.checked = true;
        var borderToggleWrapper = borderToggle.closest('.form-check, .fitem, .felement');
        if (borderToggleWrapper) {
            borderToggleWrapper.classList.add('local-course-banner-builder-option-disabled');
        }
    }
    if (filemanagerItem) {
        filemanagerItem.hidden = isBorderOnly || isOverlayOnly;
    }
    var sortOrderInput = layerForm.querySelector('#id_sortorder');
    var sortOrderItem = sortOrderInput ? sortOrderInput.closest('.fitem, .form-group, .mb-3, .row') : null;
    if (sortOrderInput) {
        sortOrderInput.disabled = isBorderOnly || isOverlayOnly || isExistingBorderLayer || isExistingOverlayLayer;
        sortOrderInput.setAttribute(
            'aria-disabled',
            sortOrderInput.disabled ? 'true' : 'false'
        );
    }
    if (sortOrderItem) {
        sortOrderItem.classList.toggle(
            'local-course-banner-builder-option-disabled',
            isBorderOnly || isOverlayOnly || isExistingBorderLayer || isExistingOverlayLayer
        );
    }
    [
        layerForm.querySelector('#id_fitmodeoverride'),
        layerForm.querySelector('[data-layer-position-anchor=\"1\"]')
    ].forEach(function(input) {
        var item = input ? input.closest('.fitem, .form-group, .mb-3, .row') : null;
        if (item) {
            item.hidden = isBorderOnly || isOverlayOnly;
            item.setAttribute('aria-hidden', (isBorderOnly || isOverlayOnly) ? 'true' : 'false');
        }
    });
    if (filemanagerNoteItem) {
        filemanagerNoteItem.hidden = !isBorderOnly;
    }
    if (existingBorderNoteItem && (!existingBorderNote || existingBorderNote.dataset.inlinePlaced !== '1')) {
        existingBorderNoteItem.hidden = !sourceBlocksNewBorder;
    }
    if (existingBorderNote) {
        existingBorderNote.classList.toggle('d-none', !sourceBlocksNewBorder);
    }
    if (existingBorderInlineNote) {
        existingBorderInlineNote.classList.toggle('d-none', !sourceBlocksNewBorder);
    }
    if (borderSection) {
        borderSection.hidden = isOverlayOnly;
        borderSection.setAttribute('aria-hidden', isOverlayOnly ? 'true' : 'false');
        borderSection.classList.toggle('local-course-banner-builder-disabled', !!sourceBlocksNewBorder);
        if (sourceBlocksNewBorder) {
            borderSection.open = false;
        }
    }
    if (overlaySection) {
        overlaySection.hidden = isBorderOnly || (!isOverlayOnly && !isExistingOverlayLayer);
        overlaySection.setAttribute('aria-hidden', overlaySection.hidden ? 'true' : 'false');
        overlaySection.classList.toggle('local-course-banner-builder-disabled', !!sourceBlocksNewOverlay);
        if (sourceBlocksNewOverlay) {
            overlaySection.open = false;
        }
        if (isOverlayOnly || isExistingOverlayLayer) {
            overlaySection.classList.remove('is-collapsed');
            overlaySection.open = true;
        }
    }
    if (borderSummary) {
        borderSummary.setAttribute('aria-disabled', sourceBlocksNewBorder ? 'true' : 'false');
        borderSummary.tabIndex = sourceBlocksNewBorder ? -1 : 0;
    }
    localCourseBannerBuilderSyncLayerTypeChoice(layerForm);
    localCourseBannerBuilderSyncBorderAccordion(layerForm);
    localCourseBannerBuilderSyncBorderPreview(layerForm);
}

function localCourseBannerBuilderSyncOffsetFields(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    var anchor = layerForm ? layerForm.querySelector('[data-layer-position-anchor=\"1\"]') : null;
    if (!anchor) {
        return;
    }
    var borderToggle = layerForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    var hasExistingImageInput = layerForm.querySelector('#id_hasexistingimage');
    var hasExistingImage = !!(hasExistingImageInput && parseInt(hasExistingImageInput.value || '0', 10) > 0);
    var filemanager = layerForm.querySelector('#fitem_id_bannerimage_filemanager');
    var hasFiles = localCourseBannerBuilderGetVisibleDraftFileItems(layerForm).length > 0;
    var isBorderOnly = !!(borderToggle && borderToggle.checked && !hasExistingImage && !hasFiles);
    var fitOverride = layerForm.querySelector('#id_fitmodeoverride');
    var previewRoot = layerForm.querySelector('[data-layer-banner-preview=\"1\"]');
    var defaultFitMode = previewRoot ? (previewRoot.getAttribute('data-default-fitmode') || 'bannerfit') : 'bannerfit';
    var effectiveFitMode = fitOverride && fitOverride.value ? fitOverride.value : defaultFitMode;
    var isBannerFill = effectiveFitMode === 'bannerfit';
    var anchorWrapper = anchor.closest('.fitem, .form-group, .mb-3');
    anchor.disabled = isBannerFill || isBorderOnly;
    if (anchorWrapper) {
        anchorWrapper.hidden = isBannerFill || isBorderOnly;
    }
    var offsetHelp = layerForm.querySelector('#fitem_id_layeroffsethelp, [id^=\"fitem_id_layeroffsethelp\"]');
    if (offsetHelp) {
        offsetHelp.hidden = isBannerFill;
    }
    var visibleSidesByAnchor = {
        'center': [],
        'top': ['top'],
        'bottom': ['bottom'],
        'left': ['left'],
        'right': ['right'],
        'top-left': ['top', 'left'],
        'top-right': ['top', 'right'],
        'bottom-left': ['bottom', 'left'],
        'bottom-right': ['bottom', 'right']
    };
    var visibleSides = visibleSidesByAnchor[anchor.value] || [];
    Array.prototype.slice.call(layerForm.querySelectorAll('[data-offset-side]')).forEach(function(input) {
        var side = input.getAttribute('data-offset-side');
        var wrapper = input.closest('.fitem, .form-group, .mb-3');
        var sliderWrapper = layerForm.querySelector('[data-percent-slider-wrapper-for=\"' + input.id + '\"]');
        var isVisible = !isBannerFill && !isBorderOnly && visibleSides.indexOf(side) !== -1;
        input.disabled = !isVisible;
        if (wrapper) {
            wrapper.hidden = !isVisible;
        }
        if (sliderWrapper) {
            sliderWrapper.hidden = !isVisible;
        }
    });
}

function localCourseBannerBuilderApplyLayerPositionAnchorChange(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerForm) {
        return;
    }
    layerForm.dataset.previewUserChanged = '1';
    ['offsettoppercent', 'offsetrightpercent', 'offsetbottompercent', 'offsetleftpercent'].forEach(function(name) {
        var field = layerForm.querySelector('#id_' + name);
        if (!field) {
            return;
        }
        field.value = '0';
        field.dispatchEvent(new Event('input', {bubbles: true}));
        field.dispatchEvent(new Event('change', {bubbles: true}));
    });
    localCourseBannerBuilderSyncOffsetFields(layerForm);
    localCourseBannerBuilderSyncCurrentLayerDataFromForm(layerForm);
    localCourseBannerBuilderSyncLayerBannerPreview(layerForm);
    localCourseBannerBuilderSaveActiveDraftPreviewState(layerForm);
}

function localCourseBannerBuilderNormaliseNumericValue(value, fallback) {
    if (value === null || value === undefined) {
        return fallback;
    }
    var normalised = String(value).replace(',', '.').trim();
    if (normalised === '') {
        return fallback;
    }
    var parsed = parseFloat(normalised);
    return isNaN(parsed) ? fallback : parsed;
}

function localCourseBannerBuilderClampPercent(value, fallback) {
    var parsed = localCourseBannerBuilderNormaliseNumericValue(value, fallback);
    return Math.max(0, Math.min(100, parsed));
}

function localCourseBannerBuilderClampCropSize(value) {
    var parsed = localCourseBannerBuilderNormaliseNumericValue(value, 100);
    return Math.max(1, Math.min(100, parsed));
}

function localCourseBannerBuilderNormaliseCropState(state) {
    var cropEnabled = !!(state && (
        state.imagecropenabled ||
        state.enabled ||
        state['imagecropenabled'] === '1' ||
        state['enabled'] === '1'
    ));
    var cropLeft = state && typeof state.imagecropleftpercent !== 'undefined' ?
        state.imagecropleftpercent :
        (state ? state.left : 0);
    var cropTop = state && typeof state.imagecroptoppercent !== 'undefined' ?
        state.imagecroptoppercent :
        (state ? state.top : 0);
    var cropWidth = state && typeof state.imagecropwidthpercent !== 'undefined' ?
        state.imagecropwidthpercent :
        (state ? state.width : 100);
    var cropHeight = state && typeof state.imagecropheightpercent !== 'undefined' ?
        state.imagecropheightpercent :
        (state ? state.height : 100);
    var crop = {
        enabled: cropEnabled,
        left: localCourseBannerBuilderClampPercent(cropLeft, 0),
        top: localCourseBannerBuilderClampPercent(cropTop, 0),
        width: localCourseBannerBuilderClampCropSize(cropWidth),
        height: localCourseBannerBuilderClampCropSize(cropHeight)
    };
    crop.left = Math.max(0, Math.min(100 - crop.width, crop.left));
    crop.top = Math.max(0, Math.min(100 - crop.height, crop.top));
    crop.enabled = crop.enabled && (crop.left > 0 || crop.top > 0 || crop.width < 100 || crop.height < 100);
    return crop;
}

function localCourseBannerBuilderApplyCropToImageStyles(imageStyles, state, fillWrapper) {
    var crop = localCourseBannerBuilderNormaliseCropState(state);
    if (!crop.enabled) {
        return;
    }
    if (fillWrapper) {
        imageStyles.push(
            'position: absolute;',
            'top: 0;',
            'left: 0;',
            'right: auto;',
            'bottom: auto;',
            'flex: 0 0 auto;',
            'width: ' + (10000 / crop.width).toFixed(6) + '%;',
            'height: ' + (10000 / crop.height).toFixed(6) + '%;',
            'max-width: none;',
            'transform: translate(-' + crop.left.toFixed(6) + '%, -' + crop.top.toFixed(6) + '%);',
            'transform-origin: top left;'
        );
        return;
    }
    var right = Math.max(0, 100 - crop.left - crop.width);
    var bottom = Math.max(0, 100 - crop.top - crop.height);
    imageStyles.push(
        'clip-path: inset(' + crop.top.toFixed(6) + '% ' + right.toFixed(6) + '% ' +
            bottom.toFixed(6) + '% ' + crop.left.toFixed(6) + '%);'
    );
}

function localCourseBannerBuilderApplyCropToLayerImage(layer, state) {
    var image = layer ? layer.querySelector('[data-preview-image-tag=\"1\"]') : null;
    if (!image) {
        return;
    }
    var crop = localCourseBannerBuilderNormaliseCropState(state);
    if (!crop.enabled) {
        image.style.removeProperty('clip-path');
        image.style.removeProperty('-webkit-clip-path');
        return;
    }
    var right = Math.max(0, 100 - crop.left - crop.width);
    var bottom = Math.max(0, 100 - crop.top - crop.height);
    var clip = 'inset(' + crop.top.toFixed(6) + '% ' + right.toFixed(6) + '% ' +
        bottom.toFixed(6) + '% ' + crop.left.toFixed(6) + '%)';
    image.style.clipPath = clip;
    image.style.webkitClipPath = clip;
}

function localCourseBannerBuilderReadPreviewCropState(form, layer, preferStoredState) {
    var enabledInput = localCourseBannerBuilderGetCropInput(form, 'imagecropenabled');
    var leftInput = localCourseBannerBuilderGetCropInput(form, 'imagecropleftpercent');
    var topInput = localCourseBannerBuilderGetCropInput(form, 'imagecroptoppercent');
    var widthInput = localCourseBannerBuilderGetCropInput(form, 'imagecropwidthpercent');
    var heightInput = localCourseBannerBuilderGetCropInput(form, 'imagecropheightpercent');
    var readCrop = function(attribute, input, fallback) {
        if (preferStoredState) {
            return layer ? (layer.getAttribute(attribute) || fallback) : fallback;
        }
        return input && input.value !== '' ? input.value : (layer ? (layer.getAttribute(attribute) || fallback) : fallback);
    };
    return {
        imagecropenabled: preferStoredState ?
            !!(layer && layer.getAttribute('data-preview-crop-enabled') === '1') :
            !!(enabledInput && enabledInput.value === '1'),
        imagecropleftpercent: readCrop('data-preview-crop-left', leftInput, '0'),
        imagecroptoppercent: readCrop('data-preview-crop-top', topInput, '0'),
        imagecropwidthpercent: readCrop('data-preview-crop-width', widthInput, '100'),
        imagecropheightpercent: readCrop('data-preview-crop-height', heightInput, '100')
    };
}

var localCourseBannerBuilderCropInteraction = null;

function localCourseBannerBuilderGetCropInput(form, name) {
    if (!form) {
        return null;
    }
    return form.querySelector('#id_' + name) || form.querySelector('[name=\"' + name + '\"]');
}

function localCourseBannerBuilderSetPreviewCropState(layer, state) {
    if (!layer) {
        return;
    }
    var crop = localCourseBannerBuilderNormaliseCropState(state);
    layer.setAttribute('data-preview-crop-enabled', crop.enabled ? '1' : '0');
    layer.setAttribute('data-preview-crop-left', String(localCourseBannerBuilderRoundPreviewPercent(crop.left)));
    layer.setAttribute('data-preview-crop-top', String(localCourseBannerBuilderRoundPreviewPercent(crop.top)));
    layer.setAttribute('data-preview-crop-width', String(localCourseBannerBuilderRoundPreviewPercent(crop.width)));
    layer.setAttribute('data-preview-crop-height', String(localCourseBannerBuilderRoundPreviewPercent(crop.height)));
    localCourseBannerBuilderUpdateCropSelectionFrame(layer, crop);

    var form = layer.closest ? layer.closest('form.mform') : null;
    if (!form && !(layer.closest && layer.closest('[data-source-visual-editor=\"1\"]'))) {
        form = localCourseBannerBuilderGetLayerScope(layer);
    }
    if (form) {
        var nextCropState = {
            imagecropenabled: layer.getAttribute('data-preview-crop-enabled') === '1',
            imagecropleftpercent: layer.getAttribute('data-preview-crop-left'),
            imagecroptoppercent: layer.getAttribute('data-preview-crop-top'),
            imagecropwidthpercent: layer.getAttribute('data-preview-crop-width'),
            imagecropheightpercent: layer.getAttribute('data-preview-crop-height')
        };
        localCourseBannerBuilderWriteCropStateToForm(form, nextCropState);
        localCourseBannerBuilderGetCropInput(form, 'imagecropenabled').value =
            layer.getAttribute('data-preview-crop-enabled');
        localCourseBannerBuilderGetCropInput(form, 'imagecropleftpercent').value =
            layer.getAttribute('data-preview-crop-left');
        localCourseBannerBuilderGetCropInput(form, 'imagecroptoppercent').value =
            layer.getAttribute('data-preview-crop-top');
        localCourseBannerBuilderGetCropInput(form, 'imagecropwidthpercent').value =
            layer.getAttribute('data-preview-crop-width');
        localCourseBannerBuilderGetCropInput(form, 'imagecropheightpercent').value =
            layer.getAttribute('data-preview-crop-height');
        form.dataset.previewUserChanged = '1';
        localCourseBannerBuilderApplyCropToLayerImage(layer, nextCropState);
        localCourseBannerBuilderSaveActiveDraftPreviewState(form);
        localCourseBannerBuilderSyncModalPreviewCropButtons(form);
        return;
    }

    var root = layer.closest('[data-source-visual-editor=\"1\"]');
    if (root) {
        var sourceState = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
        if (sourceState) {
            sourceState.imagecropenabled = layer.getAttribute('data-preview-crop-enabled') === '1';
            sourceState.imagecropleftpercent = layer.getAttribute('data-preview-crop-left');
            sourceState.imagecroptoppercent = layer.getAttribute('data-preview-crop-top');
            sourceState.imagecropwidthpercent = layer.getAttribute('data-preview-crop-width');
            sourceState.imagecropheightpercent = layer.getAttribute('data-preview-crop-height');
            localCourseBannerBuilderSetSourcePreviewLayerState(layer, sourceState);
            localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
            localCourseBannerBuilderUpdateSourcePreviewRow(root, sourceState);
            localCourseBannerBuilderSyncSourcePreviewPayload(root);
            localCourseBannerBuilderSyncSourcePreviewCropButtons(root);
        }
    }
}

function localCourseBannerBuilderGetEditableCurrentPreviewImage(form) {
    if (!form) {
        return null;
    }
    return form.querySelector(
        '[data-preview-current-image=\"1\"][data-preview-current-layer=\"1\"]:not([data-preview-context-layer=\"1\"])'
    ) || form.querySelector(
        '[data-preview-current-image=\"1\"]:not([data-preview-context-layer=\"1\"])'
    ) || form.querySelector('[data-preview-current-image=\"1\"]');
}

function localCourseBannerBuilderGetLayerFormPreviewPanel(form) {
    if (!form) {
        return null;
    }
    var modalContent = form.closest ? form.closest('.modal-content') : null;
    var modal = form.closest ? form.closest('.modal') : null;
    return form.querySelector('.local-course-banner-builder-banner-preview-panel') ||
        (modalContent ? modalContent.querySelector('.local-course-banner-builder-banner-preview-panel') : null) ||
        (modal ? modal.querySelector('.local-course-banner-builder-banner-preview-panel') : null);
}

function localCourseBannerBuilderGetLayerFormPreviewImage(form) {
    var panel = localCourseBannerBuilderGetLayerFormPreviewPanel(form);
    if (panel) {
        var panelLayer = panel.querySelector(
            '[data-preview-current-image=\"1\"][data-preview-current-layer=\"1\"]:not([data-preview-context-layer=\"1\"])'
        ) || panel.querySelector(
            '[data-preview-current-image=\"1\"]:not([data-preview-context-layer=\"1\"])'
        ) || panel.querySelector('[data-preview-current-image=\"1\"]');
        if (panelLayer) {
            return panelLayer;
        }
    }
    return localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
}

function localCourseBannerBuilderSyncLayerFormFromCurrentPreviewLayer(form) {
    if (!form) {
        return;
    }
    var currentLayer = localCourseBannerBuilderGetLayerFormPreviewImage(form);
    if (!currentLayer || currentLayer.hidden) {
        return;
    }
    var state = localCourseBannerBuilderReadLayerPreviewStateFromLayer(currentLayer);
    if (!state) {
        return;
    }
    localCourseBannerBuilderApplyLayerFormPreviewState(form, state);
}

function localCourseBannerBuilderMoveModalPreviewLayerDepth(control, direction) {
    var form = localCourseBannerBuilderGetLayerScope(control);
    var layer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    if (!form || !layer || layer.hidden) {
        return;
    }
    var state = localCourseBannerBuilderReadLayerPreviewStateFromLayer(layer);
    if (!state) {
        return;
    }
    var delta = direction > 0 ? 1 : -1;
    var nextSortOrder = Math.max(0, (parseInt(state.sortorder || '0', 10) || 0) + delta);
    var sortOrderInput = form.querySelector('#id_sortorder');
    state.sortorder = nextSortOrder;
    state.zindex = Math.max(1, (parseInt(state.zindex || '1', 10) || 1) + delta);
    if (sortOrderInput) {
        sortOrderInput.value = String(nextSortOrder);
    }
    localCourseBannerBuilderApplyLayerFormPreviewState(form, state);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
}

function localCourseBannerBuilderWriteCropStateToForm(form, state) {
    if (!form || !state) {
        return;
    }
    var crop = localCourseBannerBuilderNormaliseCropState(state);
    var values = {
        imagecropenabled: crop.enabled ? '1' : '0',
        imagecropleftpercent: String(localCourseBannerBuilderRoundPreviewPercent(crop.left)),
        imagecroptoppercent: String(localCourseBannerBuilderRoundPreviewPercent(crop.top)),
        imagecropwidthpercent: String(localCourseBannerBuilderRoundPreviewPercent(crop.width)),
        imagecropheightpercent: String(localCourseBannerBuilderRoundPreviewPercent(crop.height))
    };
    Object.keys(values).forEach(function(name) {
        var input = form.querySelector('[name=\"' + name + '\"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            form.appendChild(input);
        }
        input.value = values[name];
    });
    var payload = form.querySelector('[name=\"previewcropstate\"]');
    if (!payload) {
        payload = document.createElement('input');
        payload.type = 'hidden';
        payload.name = 'previewcropstate';
        form.appendChild(payload);
    }
    payload.value = JSON.stringify({
        imagecropenabled: values.imagecropenabled === '1',
        imagecropleftpercent: values.imagecropleftpercent,
        imagecroptoppercent: values.imagecroptoppercent,
        imagecropwidthpercent: values.imagecropwidthpercent,
        imagecropheightpercent: values.imagecropheightpercent
    });
    form.dataset.previewCropState = payload.value;
}

function localCourseBannerBuilderPrepareLayerFormForSubmit(form) {
    if (!form) {
        return;
    }
    var selectedLayerType = localCourseBannerBuilderGetSelectedLayerType(form);
    localCourseBannerBuilderSetOverlayToggle(form, selectedLayerType === 'overlay');
    var borderToggle = form.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    if (borderToggle) {
        borderToggle.checked = selectedLayerType === 'border';
    }
    var currentLayer = localCourseBannerBuilderGetLayerFormPreviewImage(form);
    var cropState = form.dataset.previewCropState ?
        localCourseBannerBuilderReadJson(form.dataset.previewCropState, null) :
        (currentLayer ? localCourseBannerBuilderReadLayerPreviewStateFromLayer(currentLayer) : null);
    if (cropState) {
        localCourseBannerBuilderWriteCropStateToForm(form, cropState);
    }
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
}

function localCourseBannerBuilderShouldConfirmChildBorderDisable(form) {
    if (!form) {
        return false;
    }
    var childBorderCountInput = form.querySelector('#id_activechildborderlayers');
    var childBorderCount = childBorderCountInput ? parseInt(childBorderCountInput.value || '0', 10) : 0;
    if (childBorderCount <= 0) {
        return false;
    }
    var borderInput = form.querySelector('#id_borderenabled');
    var enabledInput = form.querySelector('#id_isenabled');
    var borderEnabled = !!(borderInput && (
        borderInput.type === 'checkbox' ? borderInput.checked : borderInput.value === '1'
    ));
    var layerEnabled = !enabledInput || enabledInput.checked || enabledInput.value === '1';
    return borderEnabled && layerEnabled;
}

function localCourseBannerBuilderGetChildBorderDisableConfirmMessage(form) {
    var childBorderCountInput = form ? form.querySelector('#id_activechildborderlayers') : null;
    var childBorderCount = childBorderCountInput ? parseInt(childBorderCountInput.value || '0', 10) : 0;
    var message = localCourseBannerBuilderGetJsString(
        'childborderlayersdisableconfirm',
        'Active border layer(s) in child sources will be disabled before this parent border is saved.'
    );
    return message.replace('{$a}', String(Math.max(0, childBorderCount)));
}

function localCourseBannerBuilderShouldConfirmChildOverlayDisable(form) {
    if (!form) {
        return false;
    }
    var childOverlayCountInput = form.querySelector('#id_activechildoverlaylayers');
    var childOverlayCount = childOverlayCountInput ? parseInt(childOverlayCountInput.value || '0', 10) : 0;
    if (childOverlayCount <= 0) {
        return false;
    }
    var overlayInput = form.querySelector('#id_overlayenabled');
    var enabledInput = form.querySelector('#id_isenabled');
    var overlayEnabled = !!(overlayInput && (
        overlayInput.type === 'checkbox' ? overlayInput.checked : overlayInput.value === '1'
    ));
    var layerEnabled = !enabledInput || enabledInput.checked || enabledInput.value === '1';
    return overlayEnabled && layerEnabled;
}

function localCourseBannerBuilderGetChildOverlayDisableConfirmMessage(form) {
    var childOverlayCountInput = form ? form.querySelector('#id_activechildoverlaylayers') : null;
    var childOverlayCount = childOverlayCountInput ? parseInt(childOverlayCountInput.value || '0', 10) : 0;
    var message = localCourseBannerBuilderGetJsString(
        'childoverlaylayersdisableconfirm',
        'Active overlay layer(s) in child sources will be disabled before this parent overlay is saved.'
    );
    return message.replace('{$a}', String(Math.max(0, childOverlayCount)));
}

function localCourseBannerBuilderGetPreviewCropState(layer) {
    if (!layer) {
        return localCourseBannerBuilderNormaliseCropState(null);
    }
    return localCourseBannerBuilderNormaliseCropState({
        imagecropenabled: layer.getAttribute('data-preview-crop-enabled') === '1',
        imagecropleftpercent: layer.getAttribute('data-preview-crop-left') || '0',
        imagecroptoppercent: layer.getAttribute('data-preview-crop-top') || '0',
        imagecropwidthpercent: layer.getAttribute('data-preview-crop-width') || '100',
        imagecropheightpercent: layer.getAttribute('data-preview-crop-height') || '100'
    });
}

function localCourseBannerBuilderUpdateCropSelectionFrame(layer, crop) {
    if (!layer) {
        return;
    }
    var enabled = crop && crop.enabled;
    var previewRoot = layer.closest(
        '[data-layer-banner-preview=\"1\"], [data-source-preview-frame=\"1\"], [data-source-preview-frame-moodle=\"1\"]'
    );
    var fitMode = layer.getAttribute('data-preview-fitmode') ||
        (previewRoot ? (previewRoot.getAttribute('data-default-fitmode') || '') : '');
    var showFullSelection = enabled && fitMode === 'bannerfit' &&
        !layer.querySelector('[data-preview-crop-editor=\"1\"]');
    var selection = showFullSelection ? {
        left: 0,
        top: 0,
        width: 100,
        height: 100
    } : crop;
    var right = enabled ? Math.max(0, 100 - selection.left - selection.width) : 0;
    var bottom = enabled ? Math.max(0, 100 - selection.top - selection.height) : 0;
    layer.style.setProperty('--local-course-banner-builder-crop-selection-left', (enabled ? selection.left : 0) + '%');
    layer.style.setProperty('--local-course-banner-builder-crop-selection-top', (enabled ? selection.top : 0) + '%');
    layer.style.setProperty('--local-course-banner-builder-crop-selection-right', right + '%');
    layer.style.setProperty('--local-course-banner-builder-crop-selection-bottom', bottom + '%');
    layer.style.setProperty('--local-course-banner-builder-crop-selection-width', (enabled ? selection.width : 100) + '%');
    layer.style.setProperty('--local-course-banner-builder-crop-selection-height', (enabled ? selection.height : 100) + '%');
    layer.style.setProperty(
        '--local-course-banner-builder-crop-selection-center-x',
        (enabled ? (selection.left + (selection.width / 2)) : 50) + '%'
    );
    layer.style.setProperty(
        '--local-course-banner-builder-crop-selection-center-y',
        (enabled ? (selection.top + (selection.height / 2)) : 50) + '%'
    );
}

function localCourseBannerBuilderEnsureCropEditor(layer) {
    if (!layer) {
        return null;
    }
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    if (!image || !(image.currentSrc || image.getAttribute('src'))) {
        return null;
    }
    var editor = layer.querySelector('[data-preview-crop-editor=\"1\"]');
    if (!editor) {
        editor = document.createElement('div');
        editor.className = 'local-course-banner-builder-crop-editor';
        editor.setAttribute('data-preview-crop-editor', '1');
        editor.innerHTML =
            '<div class=\"local-course-banner-builder-crop-actions\" data-preview-crop-actions=\"1\">' +
            '<button type=\"button\" class=\"btn btn-primary local-course-banner-builder-crop-apply\" ' +
            'data-action=\"local-course-banner-builder-apply-preview-crop\" aria-label=\"' +
            localCourseBannerBuilderGetJsString('applycropimage', 'Apply crop') + '\">' +
            '<i class=\"fa fa-check\" aria-hidden=\"true\"></i></button>' +
            '<button type=\"button\" class=\"btn btn-outline-secondary local-course-banner-builder-crop-cancel\" ' +
            'data-action=\"local-course-banner-builder-cancel-preview-crop\" aria-label=\"' +
            localCourseBannerBuilderGetJsString('cancel', 'Cancel') + '\">' +
            '<i class=\"fa fa-times\" aria-hidden=\"true\"></i></button>' +
            '</div>' +
            '<div class=\"local-course-banner-builder-crop-box\" data-preview-crop-box=\"1\">' +
            '<span data-preview-crop-handle=\"n\"></span><span data-preview-crop-handle=\"e\"></span>' +
            '<span data-preview-crop-handle=\"s\"></span><span data-preview-crop-handle=\"w\"></span>' +
            '<span data-preview-crop-handle=\"nw\"></span><span data-preview-crop-handle=\"ne\"></span>' +
            '<span data-preview-crop-handle=\"sw\"></span><span data-preview-crop-handle=\"se\"></span>' +
            '</div>';
        layer.appendChild(editor);
    }
    layer.classList.add('local-course-banner-builder-preview-image-layer--crop-editing');
    localCourseBannerBuilderRefreshCropEditor(layer);
    return editor;
}

function localCourseBannerBuilderRefreshCropEditor(layer) {
    var editor = layer ? layer.querySelector('[data-preview-crop-editor=\"1\"]') : null;
    var box = editor ? editor.querySelector('[data-preview-crop-box=\"1\"]') : null;
    if (!box) {
        return;
    }
    var crop = localCourseBannerBuilderGetPreviewCropState(layer);
    box.style.left = crop.left + '%';
    box.style.top = crop.top + '%';
    box.style.width = crop.width + '%';
    box.style.height = crop.height + '%';
}

function localCourseBannerBuilderRemoveCropEditor(layer) {
    var editor = layer ? layer.querySelector('[data-preview-crop-editor=\"1\"]') : null;
    if (editor) {
        editor.remove();
    }
    if (layer) {
        layer.classList.remove('local-course-banner-builder-preview-image-layer--crop-editing');
    }
}

function localCourseBannerBuilderToggleCropEditor(control, sourceMode) {
    var layer = null;
    if (sourceMode) {
        var root = control.closest('[data-source-visual-editor=\"1\"]');
        layer = root ? localCourseBannerBuilderGetSelectedSourcePreviewLayer(root) : null;
        if (root) {
            localCourseBannerBuilderPushSourcePreviewHistory(root);
        }
    } else {
        var form = localCourseBannerBuilderGetLayerScope(control);
        layer = localCourseBannerBuilderGetLayerFormPreviewImage(form);
        localCourseBannerBuilderPushModalPreviewHistoryFromControl(control);
    }
    if (!layer || layer.hidden) {
        return;
    }
    var active = !!layer.querySelector('[data-preview-crop-editor=\"1\"]');
    if (active) {
        localCourseBannerBuilderCancelCropEditor(control, sourceMode);
    } else {
        var crop = localCourseBannerBuilderGetPreviewCropState(layer);
        layer.dataset.previewCropBefore = JSON.stringify({
            imagecropenabled: crop.enabled,
            imagecropleftpercent: crop.left,
            imagecroptoppercent: crop.top,
            imagecropwidthpercent: crop.width,
            imagecropheightpercent: crop.height
        });
        crop.imagecropenabled = true;
        localCourseBannerBuilderSetPreviewCropState(layer, {
            imagecropenabled: true,
            imagecropleftpercent: crop.left,
            imagecroptoppercent: crop.top,
            imagecropwidthpercent: crop.width,
            imagecropheightpercent: crop.height
        });
        localCourseBannerBuilderEnsureCropEditor(layer);
    }
    if (sourceMode) {
        localCourseBannerBuilderSyncSourcePreviewCropButtons(control.closest('[data-source-visual-editor=\"1\"]'));
    } else {
        localCourseBannerBuilderSyncModalPreviewCropButtons(localCourseBannerBuilderGetLayerScope(control));
    }
}

function localCourseBannerBuilderGetCropControlLayer(control, sourceMode) {
    if (!control || !control.closest) {
        return null;
    }
    var directLayer = sourceMode ?
        control.closest('[data-source-preview-layer=\"1\"]') :
        control.closest('[data-preview-current-image=\"1\"]');
    if (directLayer) {
        return directLayer;
    }
    if (sourceMode) {
        var root = control.closest('[data-source-visual-editor=\"1\"]');
        return root ? localCourseBannerBuilderGetSelectedSourcePreviewLayer(root) : null;
    }
    var form = localCourseBannerBuilderGetLayerScope(control);
    return localCourseBannerBuilderGetLayerFormPreviewImage(form);
}

function localCourseBannerBuilderApplyCropEditor(control, sourceMode) {
    var root = null;
    var form = null;
    var layer = localCourseBannerBuilderGetCropControlLayer(control, sourceMode);
    if (sourceMode) {
        root = control.closest('[data-source-visual-editor=\"1\"]');
    } else {
        form = localCourseBannerBuilderGetLayerScope(control);
    }
    if (!layer) {
        return;
    }
    var crop = localCourseBannerBuilderGetPreviewCropState(layer);
    localCourseBannerBuilderSetPreviewCropState(layer, {
        imagecropenabled: crop.enabled,
        imagecropleftpercent: crop.left,
        imagecroptoppercent: crop.top,
        imagecropwidthpercent: crop.width,
        imagecropheightpercent: crop.height
    });
    delete layer.dataset.previewCropBefore;
    localCourseBannerBuilderRemoveCropEditor(layer);
    localCourseBannerBuilderUpdateCropSelectionFrame(layer, crop);
    if (sourceMode) {
        if (root) {
            localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
            localCourseBannerBuilderSyncSourcePreviewPayload(root);
            localCourseBannerBuilderSyncSourcePreviewCropButtons(root);
        }
    } else {
        if (form) {
            localCourseBannerBuilderWriteCropStateToForm(form, {
                imagecropenabled: crop.enabled,
                imagecropleftpercent: crop.left,
                imagecroptoppercent: crop.top,
                imagecropwidthpercent: crop.width,
                imagecropheightpercent: crop.height
            });
            localCourseBannerBuilderSyncCurrentImagePreview(form);
            localCourseBannerBuilderSaveActiveDraftPreviewState(form);
            localCourseBannerBuilderSyncModalPreviewCropButtons(form);
        }
    }
}

function localCourseBannerBuilderCancelCropEditor(control, sourceMode) {
    var layer = localCourseBannerBuilderGetCropControlLayer(control, sourceMode);
    if (sourceMode) {
        var root = control.closest('[data-source-visual-editor=\"1\"]');
    } else {
        var form = localCourseBannerBuilderGetLayerScope(control);
    }
    if (!layer) {
        return;
    }
    var previous = localCourseBannerBuilderReadJson(layer.dataset.previewCropBefore || '{}', {});
    if (Object.keys(previous).length) {
        localCourseBannerBuilderSetPreviewCropState(layer, previous);
    }
    delete layer.dataset.previewCropBefore;
    localCourseBannerBuilderRemoveCropEditor(layer);
    localCourseBannerBuilderUpdateCropSelectionFrame(layer, localCourseBannerBuilderGetPreviewCropState(layer));
    if (sourceMode) {
        localCourseBannerBuilderSyncSourcePreviewCropButtons(control.closest('[data-source-visual-editor=\"1\"]'));
    } else {
        localCourseBannerBuilderSyncModalPreviewCropButtons(localCourseBannerBuilderGetLayerScope(control));
    }
}

function localCourseBannerBuilderStartCropInteraction(event) {
    var cropActionButton = event.target.closest('[data-action=\"local-course-banner-builder-apply-preview-crop\"], ' +
        '[data-action=\"local-course-banner-builder-cancel-preview-crop\"]');
    if (cropActionButton) {
        event.preventDefault();
        event.stopPropagation();
        var sourceMode = !!cropActionButton.closest('[data-source-visual-editor=\"1\"]');
        if (cropActionButton.getAttribute('data-action') === 'local-course-banner-builder-apply-preview-crop') {
            localCourseBannerBuilderApplyCropEditor(cropActionButton, sourceMode);
        } else {
            localCourseBannerBuilderCancelCropEditor(cropActionButton, sourceMode);
        }
        return true;
    }
    var box = event.target.closest('[data-preview-crop-box=\"1\"]');
    var handle = event.target.closest('[data-preview-crop-handle]');
    if (!box && !handle) {
        return false;
    }
    var layer = event.target.closest('[data-preview-current-image=\"1\"], [data-source-preview-layer=\"1\"]');
    if (!layer) {
        return false;
    }
    event.preventDefault();
    event.stopPropagation();
    if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
    }
    var crop = localCourseBannerBuilderGetPreviewCropState(layer);
    localCourseBannerBuilderCropInteraction = {
        layer: layer,
        mode: handle ? (handle.getAttribute('data-preview-crop-handle') || 'se') : 'move',
        startX: event.clientX,
        startY: event.clientY,
        startCrop: crop,
        rect: layer.getBoundingClientRect(),
        aspectRatio: Math.max(0.01, (crop.width * layer.getBoundingClientRect().width) /
            Math.max(1, crop.height * layer.getBoundingClientRect().height))
    };
    return true;
}

function localCourseBannerBuilderConstrainCropResize(crop, interaction) {
    var rect = interaction.rect;
    var mode = interaction.mode;
    if (['n', 's', 'e', 'w'].indexOf(mode) !== -1) {
        return crop;
    }
    var aspect = interaction.aspectRatio || 1;
    var start = interaction.startCrop;
    var centerX = start.left + (start.width / 2);
    var centerY = start.top + (start.height / 2);
    var widthFromHeight = function(height) {
        return (height * aspect * rect.height) / Math.max(1, rect.width);
    };
    var heightFromWidth = function(width) {
        return (width * rect.width) / Math.max(0.01, aspect * rect.height);
    };

    if (mode === 'n' || mode === 's') {
        crop.width = widthFromHeight(crop.height);
        crop.left = centerX - (crop.width / 2);
    } else if (mode === 'e' || mode === 'w') {
        crop.height = heightFromWidth(crop.width);
        crop.top = centerY - (crop.height / 2);
    } else if (mode !== 'move') {
        var proposedHeight = heightFromWidth(crop.width);
        if (proposedHeight > 100 || proposedHeight > crop.height * 1.5) {
            crop.width = widthFromHeight(crop.height);
        } else {
            crop.height = proposedHeight;
        }
        if (mode.indexOf('n') !== -1) {
            crop.top = start.top + start.height - crop.height;
        }
        if (mode.indexOf('w') !== -1) {
            crop.left = start.left + start.width - crop.width;
        }
    }
    return crop;
}

function localCourseBannerBuilderHandleCropPointerMove(event) {
    var interaction = localCourseBannerBuilderCropInteraction;
    if (!interaction) {
        return;
    }
    event.preventDefault();
    var rect = interaction.rect;
    if (!rect.width || !rect.height) {
        return;
    }
    var deltaX = ((event.clientX - interaction.startX) / rect.width) * 100;
    var deltaY = ((event.clientY - interaction.startY) / rect.height) * 100;
    var crop = Object.assign({}, interaction.startCrop);
    if (interaction.mode === 'move') {
        crop.left += deltaX;
        crop.top += deltaY;
    } else {
        if (interaction.mode.indexOf('w') !== -1) {
            crop.left += deltaX;
            crop.width -= deltaX;
        }
        if (interaction.mode.indexOf('e') !== -1) {
            crop.width += deltaX;
        }
        if (interaction.mode.indexOf('n') !== -1) {
            crop.top += deltaY;
            crop.height -= deltaY;
        }
        if (interaction.mode.indexOf('s') !== -1) {
            crop.height += deltaY;
        }
        crop = localCourseBannerBuilderConstrainCropResize(crop, interaction);
    }
    crop.imagecropenabled = true;
    crop.imagecropleftpercent = crop.left;
    crop.imagecroptoppercent = crop.top;
    crop.imagecropwidthpercent = crop.width;
    crop.imagecropheightpercent = crop.height;
    localCourseBannerBuilderSetPreviewCropState(interaction.layer, crop);
    localCourseBannerBuilderRefreshCropEditor(interaction.layer);
}

function localCourseBannerBuilderStopCropInteraction() {
    localCourseBannerBuilderCropInteraction = null;
}

function localCourseBannerBuilderSyncCustomSizeFields(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    var fitOverride = layerForm ? layerForm.querySelector('#id_fitmodeoverride') : null;
    var widthInput = layerForm ? layerForm.querySelector('#id_customwidthpercent') : null;
    var heightInput = layerForm ? layerForm.querySelector('#id_customheightpercent') : null;
    var keepAspect = layerForm ? layerForm.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]') : null;
    if (!fitOverride || !widthInput || !heightInput || !keepAspect) {
        return;
    }

    var isCustom = fitOverride.value === 'custom';
    var widthWrapper = widthInput.closest('.fitem, .form-group, .mb-3');
    var heightWrapper = heightInput.closest('.fitem, .form-group, .mb-3');
    var keepAspectWrapper = keepAspect.closest('.fitem, .form-group, .mb-3');
    var widthSliderWrapper = layerForm.querySelector('[data-percent-slider-wrapper-for=\"id_customwidthpercent\"]');
    var heightSliderWrapper = layerForm.querySelector('[data-percent-slider-wrapper-for=\"id_customheightpercent\"]');
    var widthSlider = layerForm.querySelector('[data-percent-slider-for=\"id_customwidthpercent\"]');
    var heightSlider = layerForm.querySelector('[data-percent-slider-for=\"id_customheightpercent\"]');

    widthInput.disabled = !isCustom;
    keepAspect.disabled = !isCustom;
    heightInput.disabled = !isCustom || keepAspect.checked;
    var isPreviewInteractionPreparing = layerForm.dataset.previewInteractionStarting === '1' ||
        layerForm.dataset.previewApplyingInteraction === '1';
    if (isCustom && keepAspect.checked && heightInput.value !== widthInput.value && !isPreviewInteractionPreparing) {
        heightInput.value = widthInput.value;
        if (heightSlider) {
            heightSlider.value = widthInput.value;
        }
        var heightOutput = layerForm.querySelector('[data-percent-slider-output-for=\"id_customheightpercent\"]');
        if (heightOutput) {
            heightOutput.textContent = widthInput.value + '%';
        }
    }

    [widthWrapper, heightWrapper, keepAspectWrapper].forEach(function(wrapper) {
        if (wrapper) {
            wrapper.hidden = !isCustom;
        }
    });
    if (widthSliderWrapper) {
        widthSliderWrapper.hidden = !isCustom;
    }
    if (heightSliderWrapper) {
        heightSliderWrapper.hidden = !isCustom;
    }
    if (widthSlider) {
        widthSlider.disabled = widthInput.disabled;
    }
    if (heightSlider) {
        heightSlider.disabled = heightInput.disabled;
    }
}

function localCourseBannerBuilderBindPercentSliders(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerForm) {
        return;
    }

    Array.prototype.slice.call(layerForm.querySelectorAll('[data-percent-slider-for]')).forEach(function(slider) {
        var targetSelector = '#' + slider.getAttribute('data-percent-slider-for');
        var targetInput = layerForm.querySelector(targetSelector);
        var output = layerForm.querySelector(
            '[data-percent-slider-output-for=\"' + slider.getAttribute('data-percent-slider-for') + '\"]'
        );
        if (!targetInput) {
            return;
        }

        var syncFromInput = function() {
            var fallbackValue = localCourseBannerBuilderNormaliseNumericValue(
                targetInput.getAttribute('data-number-min') || slider.min || '0',
                0
            );
            var value = localCourseBannerBuilderNormaliseNumericValue(targetInput.value, fallbackValue);
            slider.value = String(value);
            if (output) {
                output.textContent = String(value) + (slider.getAttribute('data-range-suffix') || '%');
            }
        };

        if (slider.dataset.percentSliderBound === '1') {
            syncFromInput();
            return;
        }

        var syncFromSlider = function() {
            targetInput.value = slider.value;
            localCourseBannerBuilderSyncLinkedCustomSizeInputs(layerForm, targetInput);
            if (output) {
                output.textContent = slider.value + (slider.getAttribute('data-range-suffix') || '%');
            }
            targetInput.dispatchEvent(new Event('input', {bubbles: true}));
            targetInput.dispatchEvent(new Event('change', {bubbles: true}));
        };

        slider.addEventListener('input', syncFromSlider);
        slider.addEventListener('change', syncFromSlider);
        targetInput.addEventListener('input', syncFromInput);
        targetInput.addEventListener('change', syncFromInput);
        syncFromInput();
        slider.dataset.percentSliderBound = '1';
    });
}

function localCourseBannerBuilderSyncPercentSliderValues(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerForm) {
        return;
    }
    Array.prototype.slice.call(layerForm.querySelectorAll('[data-percent-slider-for]')).forEach(function(slider) {
        var targetid = slider.getAttribute('data-percent-slider-for');
        var targetInput = targetid ? layerForm.querySelector('#' + localCourseBannerBuilderEscapeSelectorId(targetid)) : null;
        var output = targetid ? layerForm.querySelector('[data-percent-slider-output-for=\"' + targetid + '\"]') : null;
        if (!targetInput) {
            return;
        }
        var fallbackValue = localCourseBannerBuilderNormaliseNumericValue(
            targetInput.getAttribute('data-number-min') || slider.min || '0',
            0
        );
        var value = localCourseBannerBuilderNormaliseNumericValue(targetInput.value, fallbackValue);
        slider.value = String(value);
        if (output) {
            output.textContent = String(value) + (slider.getAttribute('data-range-suffix') || '%');
        }
    });
}

function localCourseBannerBuilderSyncLinkedCustomSizeInputs(scope, sourceInput) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope || sourceInput);
    if (!layerForm || !sourceInput) {
        return;
    }
    if (layerForm.dataset.previewApplyingInteraction === '1' ||
            layerForm.dataset.previewInteractionStarting === '1') {
        return;
    }

    var keepAspectInput = layerForm.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    if (!keepAspectInput || !keepAspectInput.checked) {
        return;
    }

    var widthInput = layerForm.querySelector('#id_customwidthpercent');
    var heightInput = layerForm.querySelector('#id_customheightpercent');
    if (!widthInput || !heightInput) {
        return;
    }

    var counterpart = sourceInput === widthInput ? heightInput : (sourceInput === heightInput ? widthInput : null);
    if (!counterpart || counterpart.value === sourceInput.value) {
        return;
    }

    counterpart.value = sourceInput.value;
    counterpart.dispatchEvent(new Event('input', {bubbles: true}));
    counterpart.dispatchEvent(new Event('change', {bubbles: true}));
}

function localCourseBannerBuilderUpgradeRanges() {
    Array.prototype.slice.call(document.querySelectorAll('input[data-range-upgrade=\"1\"]')).forEach(function(input) {
        input.type = 'range';
        input.min = input.getAttribute('data-range-min') || '0';
        input.max = input.getAttribute('data-range-max') || '1';
        input.step = input.getAttribute('data-range-step') || '0.01';
        input.classList.add('local-course-banner-builder-range');
        input.classList.add('theme-easyedu-range');
        var describedby = input.getAttribute('aria-describedby');
        var output = input.nextElementSibling &&
            input.nextElementSibling.classList &&
            input.nextElementSibling.classList.contains('local-course-banner-builder-range-output') ?
            input.nextElementSibling :
            null;
        if (!output) {
            output = document.createElement('span');
            output.className = 'local-course-banner-builder-range-output theme-easyedu-range-output';
            output.setAttribute('aria-live', 'polite');
            input.insertAdjacentElement('afterend', output);
        }
        if (describedby) {
            input.setAttribute('aria-describedby', describedby);
        }
        var sync = function() {
            output.textContent = input.value + (input.getAttribute('data-range-suffix') || '');
        };
        if (input.dataset.rangeReady !== '1') {
            input.addEventListener('input', sync);
            input.dataset.rangeReady = '1';
        }
        sync();
    });
}

function localCourseBannerBuilderUpgradeNumberInputs() {
    Array.prototype.slice.call(document.querySelectorAll('input[data-upgrade-number=\"1\"]')).forEach(function(input) {
        if (input.dataset.numberReady === '1') {
            return;
        }
        input.type = 'number';
        input.min = input.getAttribute('data-number-min') || '0';
        input.max = input.getAttribute('data-number-max') || '';
        input.step = input.getAttribute('data-number-step') || '1';
        var suffix = input.getAttribute('data-field-suffix');
        if (suffix && input.parentElement && !input.parentElement.querySelector('.local-course-banner-builder-field-suffix')) {
            var suffixNode = document.createElement('span');
            suffixNode.className = 'local-course-banner-builder-field-suffix';
            suffixNode.textContent = suffix;
            input.insertAdjacentElement('afterend', suffixNode);
        }
        var normalise = function() {
            if (input.value === '') {
                return;
            }
            var value = localCourseBannerBuilderNormaliseNumericValue(input.value, 0);
            var min = input.min !== '' ? localCourseBannerBuilderNormaliseNumericValue(input.min, value) : value;
            var max = input.max !== '' ? localCourseBannerBuilderNormaliseNumericValue(input.max, value) : value;
            if (input.min !== '') {
                value = Math.max(min, value);
            }
            if (input.max !== '') {
                value = Math.min(max, value);
            }
            input.value = String(value);
        };
        input.addEventListener('change', normalise);
        input.addEventListener('blur', normalise);
        input.dataset.numberReady = '1';
    });
}

function localCourseBannerBuilderNormaliseHexColor(value, fallback) {
    var color = (value || '').trim();
    if (/^#([0-9a-f]{3})$/i.test(color)) {
        return ('#' + color.slice(1).split('').map(function(part) {
            return part + part;
        }).join('')).toUpperCase();
    }
    if (/^#([0-9a-f]{6})$/i.test(color)) {
        return color.toUpperCase();
    }
    return (fallback || '#56B9C0').toUpperCase();
}

function localCourseBannerBuilderUpgradeColorPickers(scope) {
    Array.prototype.slice.call((scope || document).querySelectorAll('form.mform')).forEach(function(form) {
        var textInput = form.querySelector('#id_bordercolor');
        var pickerInput = form.querySelector('[data-border-color-picker=\"1\"]');
        if (textInput && pickerInput) {
            var syncPickerFromText = function() {
                pickerInput.value = localCourseBannerBuilderNormaliseHexColor(textInput.value, pickerInput.value || '#56B9C0');
                pickerInput.disabled = !!textInput.disabled;
                localCourseBannerBuilderSyncColourInput(pickerInput);
            };
            if (!textInput.dataset.colorPickerBound) {
                textInput.addEventListener('input', syncPickerFromText);
                textInput.addEventListener('change', syncPickerFromText);
                textInput.dataset.colorPickerBound = '1';
            }
            if (!pickerInput.dataset.colorPickerBound) {
                pickerInput.addEventListener('input', function() {
                    textInput.value = pickerInput.value.toUpperCase();
                    localCourseBannerBuilderSyncColourInput(pickerInput);
                    textInput.dispatchEvent(new Event('input', {bubbles: true}));
                });
                pickerInput.addEventListener('change', function() {
                    textInput.value = pickerInput.value.toUpperCase();
                    localCourseBannerBuilderSyncColourInput(pickerInput);
                    textInput.dispatchEvent(new Event('change', {bubbles: true}));
                });
                pickerInput.dataset.colorPickerBound = '1';
            }
            syncPickerFromText();
        }

        Array.prototype.slice.call(form.querySelectorAll('[data-overlay-color-text]')).forEach(function(overlayTextInput) {
            var key = overlayTextInput.getAttribute('data-overlay-color-text');
            var overlayPickerInput = key ? form.querySelector('[data-overlay-color-picker=\"' + key + '\"]') : null;
            if (!overlayPickerInput) {
                return;
            }
            var syncOverlayPickerFromText = function() {
                overlayPickerInput.value = localCourseBannerBuilderNormaliseHexColor(
                    overlayTextInput.value,
                    overlayPickerInput.value || '#000000'
                );
                overlayPickerInput.disabled = !!overlayTextInput.disabled;
                localCourseBannerBuilderSyncColourInput(overlayPickerInput);
            };
            if (!overlayTextInput.dataset.overlayColorPickerBound) {
                overlayTextInput.addEventListener('input', syncOverlayPickerFromText);
                overlayTextInput.addEventListener('change', syncOverlayPickerFromText);
                overlayTextInput.dataset.overlayColorPickerBound = '1';
            }
            if (!overlayPickerInput.dataset.overlayColorPickerBound) {
                overlayPickerInput.addEventListener('input', function() {
                    overlayTextInput.value = overlayPickerInput.value.toUpperCase();
                    localCourseBannerBuilderSyncColourInput(overlayPickerInput);
                    overlayTextInput.dispatchEvent(new Event('input', {bubbles: true}));
                });
                overlayPickerInput.addEventListener('change', function() {
                    overlayTextInput.value = overlayPickerInput.value.toUpperCase();
                    localCourseBannerBuilderSyncColourInput(overlayPickerInput);
                    overlayTextInput.dispatchEvent(new Event('change', {bubbles: true}));
                });
                overlayPickerInput.dataset.overlayColorPickerBound = '1';
            }
            syncOverlayPickerFromText();
        });
    });
}

function localCourseBannerBuilderParseColor(value, fallbackAlpha) {
    var color = (value || '').trim();
    var fallback = {
        red: 255,
        green: 255,
        blue: 255,
        alpha: fallbackAlpha
    };
    var shortHex = /^#([0-9a-f]{3})$/i.exec(color);
    if (shortHex) {
        return {
            red: parseInt(shortHex[1][0] + shortHex[1][0], 16),
            green: parseInt(shortHex[1][1] + shortHex[1][1], 16),
            blue: parseInt(shortHex[1][2] + shortHex[1][2], 16),
            alpha: fallbackAlpha
        };
    }
    var hex = /^#([0-9a-f]{6})$/i.exec(color);
    if (hex) {
        return {
            red: parseInt(hex[1].slice(0, 2), 16),
            green: parseInt(hex[1].slice(2, 4), 16),
            blue: parseInt(hex[1].slice(4, 6), 16),
            alpha: fallbackAlpha
        };
    }
    var rgba = /^rgba?\(([^)]+)\)$/i.exec(color);
    if (rgba) {
        var parts = rgba[1].split(',').map(function(part) {
            return part.trim();
        });
        if (parts.length >= 3) {
            return {
                red: parseInt(parts[0], 10) || 255,
                green: parseInt(parts[1], 10) || 255,
                blue: parseInt(parts[2], 10) || 255,
                alpha: parts.length >= 4 ? Math.max(0, Math.min(1, localCourseBannerBuilderNormaliseNumericValue(parts[3], 0))) * fallbackAlpha : fallbackAlpha
            };
        }
    }
    return fallback;
}

function localCourseBannerBuilderSyncBorderPreview(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    var frames = Array.prototype.slice.call(layerScope ? layerScope.querySelectorAll('[data-border-preview-frame=\"1\"]') : []);
    if (!frames.length) {
        return;
    }

    var colorInput = layerScope.querySelector('#id_bordercolor');
    var widthInput = layerScope.querySelector('#id_borderwidth');
    var transparencyInput = layerScope.querySelector('#id_borderopacity');
    var fadeInput = layerScope.querySelector('#id_borderfade');
    var styleInput = layerScope.querySelector('#id_borderstyle');
    var dashLengthInput = layerScope.querySelector('[data-border-dash-length=\"1\"]');
    var roundedInput = layerScope.querySelector('[data-border-inner-rounded=\"1\"][type=\"checkbox\"]');
    var borderToggleCheckbox = layerScope.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    var borderToggleHidden = layerScope.querySelector('[data-border-toggle=\"1\"][type=\"hidden\"]');
    var showCurrentBorder = !!(borderToggleCheckbox && borderToggleCheckbox.checked) ||
        !!(borderToggleHidden && borderToggleHidden.value === '1');
    var sides = localCourseBannerBuilderGetBorderSides(layerScope);
    var widthPercent = Math.max(0, Math.min(100, localCourseBannerBuilderNormaliseNumericValue(widthInput && widthInput.value ? widthInput.value : '0', 0)));
    var transparency = Math.max(0, Math.min(100, localCourseBannerBuilderNormaliseNumericValue(transparencyInput && transparencyInput.value ? transparencyInput.value : '0', 0)));
    var opacity = 1 - (transparency / 100);
    var fade = Math.max(0, Math.min(100, localCourseBannerBuilderNormaliseNumericValue(fadeInput && fadeInput.value ? fadeInput.value : '0', 0)));
    var color = colorInput && colorInput.value ? colorInput.value : '#FFFFFF';
    var colorInfo = localCourseBannerBuilderParseColor(color, opacity);
    var solid = 'rgba(' + colorInfo.red + ', ' + colorInfo.green + ', ' + colorInfo.blue + ', ' + colorInfo.alpha + ')';
    var transparent = 'rgba(' + colorInfo.red + ', ' + colorInfo.green + ', ' + colorInfo.blue + ', 0)';
    var fadeStop = Math.max(0, 100 - fade) + '%';
    var isDashed = !!(styleInput && styleInput.value === 'dashed');
    var dashLength = Math.max(4, Math.min(80, localCourseBannerBuilderNormaliseNumericValue(dashLengthInput && dashLengthInput.value ? dashLengthInput.value : '24', 24)));
    var dashGap = Math.max(2, Math.round(dashLength * 0.7));
    var rounded = !!(roundedInput && roundedInput.checked);

    frames.forEach(function(frame) {
        var currentBorderLayers = Array.prototype.slice.call(frame.querySelectorAll('[data-preview-current-border=\"1\"]'));
        currentBorderLayers.forEach(function(borderLayer) {
            borderLayer.hidden = !showCurrentBorder;
        });
        if (!showCurrentBorder) {
            return;
        }
        var frameRect = frame.getBoundingClientRect();
        var previewReference = Math.max(0, Math.min(frameRect.width || 0, frameRect.height || 0));
        var previewWidth = widthPercent > 0 && previewReference > 0
            ? Math.max(1, Math.round(previewReference * widthPercent / 100))
            : 0;
        var topWidth = sides.top ? previewWidth : 0;
        var rightWidth = sides.right ? previewWidth : 0;
        var bottomWidth = sides.bottom ? previewWidth : 0;
        var leftWidth = sides.left ? previewWidth : 0;
        var horizontalRadiusLimit = Math.max(0, ((frameRect.width || 0) - leftWidth - rightWidth) / 2);
        var verticalRadiusLimit = Math.max(0, ((frameRect.height || 0) - topWidth - bottomWidth) / 2);
        var radiusPixels = rounded ? Math.max(0, Math.min(Math.max(8, previewWidth), horizontalRadiusLimit, verticalRadiusLimit)) : 0;
        var radiusValue = radiusPixels + 'px';
        var cutoutValue = (previewWidth + radiusPixels) + 'px';
        var variableTargets = [frame].concat(currentBorderLayers);
        var setPreviewVariable = function(name, value) {
            variableTargets.forEach(function(target) {
                target.style.setProperty(name, value);
            });
        };
        setPreviewVariable('--local-course-banner-builder-preview-top-width', sides.top ? (previewWidth + 'px') : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-right-width', sides.right ? (previewWidth + 'px') : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-bottom-width', sides.bottom ? (previewWidth + 'px') : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-left-width', sides.left ? (previewWidth + 'px') : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-top-left-radius', (rounded && sides.top && sides.left) ? radiusValue : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-top-right-radius', (rounded && sides.top && sides.right) ? radiusValue : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-bottom-right-radius', (rounded && sides.bottom && sides.right) ? radiusValue : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-bottom-left-radius', (rounded && sides.bottom && sides.left) ? radiusValue : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-top-left-offset', (sides.top && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-top-right-offset', (sides.top && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-bottom-left-offset', (sides.bottom && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-bottom-right-offset', (sides.bottom && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-right-top-offset', (sides.top && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-right-bottom-offset', (sides.bottom && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-left-top-offset', (sides.top && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-left-bottom-offset', (sides.bottom && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-top-left-corner-size', (sides.top && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-top-right-corner-size', (sides.top && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable(
            '--local-course-banner-builder-preview-bottom-right-corner-size',
            (sides.bottom && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px'
        );
        setPreviewVariable('--local-course-banner-builder-preview-bottom-left-corner-size', (sides.bottom && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        setPreviewVariable('--local-course-banner-builder-preview-top-left-fade-start', (radiusPixels + (previewWidth * (fade / 100))) + 'px');
        setPreviewVariable('--local-course-banner-builder-preview-top-right-fade-start', (radiusPixels + (previewWidth * (fade / 100))) + 'px');
        setPreviewVariable('--local-course-banner-builder-preview-bottom-right-fade-start', (radiusPixels + (previewWidth * (fade / 100))) + 'px');
        setPreviewVariable('--local-course-banner-builder-preview-bottom-left-fade-start', (radiusPixels + (previewWidth * (fade / 100))) + 'px');
        setPreviewVariable('--local-course-banner-builder-preview-color-solid', solid);
        setPreviewVariable('--local-course-banner-builder-preview-color-transparent', transparent);
        setPreviewVariable('--local-course-banner-builder-preview-fade-stop', fadeStop);

        Array.prototype.slice.call(frame.querySelectorAll('[data-border-preview-side]')).forEach(function(sideElement) {
            var side = sideElement.getAttribute('data-border-preview-side');
            var direction = {
                top: 'to bottom',
                right: 'to left',
                bottom: 'to top',
                left: 'to right'
            }[side];

            if (isDashed) {
                sideElement.style.backgroundImage = (side === 'top' || side === 'bottom')
                    ? 'repeating-linear-gradient(to right, ' + solid + ' 0 ' + dashLength + 'px, transparent ' + dashLength + 'px ' + (dashLength + dashGap) + 'px)'
                    : 'repeating-linear-gradient(to bottom, ' + solid + ' 0 ' + dashLength + 'px, transparent ' + dashLength + 'px ' + (dashLength + dashGap) + 'px)';
                sideElement.style.webkitMaskImage = 'linear-gradient(' + direction + ', #000 0%, #000 ' + fadeStop + ', transparent 100%)';
                sideElement.style.maskImage = 'linear-gradient(' + direction + ', #000 0%, #000 ' + fadeStop + ', transparent 100%)';
            } else {
                sideElement.style.backgroundImage = 'linear-gradient(' + direction + ', ' + solid + ' 0%, ' + solid + ' ' + fadeStop + ', ' + transparent + ' 100%)';
                sideElement.style.webkitMaskImage = '';
                sideElement.style.maskImage = '';
            }
        });
    });
}

function localCourseBannerBuilderGetPreviewObjectPosition(anchor) {
    switch (anchor) {
        case 'top':
            return 'center top';
        case 'bottom':
            return 'center bottom';
        case 'left':
            return 'left center';
        case 'right':
            return 'right center';
        case 'top-left':
            return 'left top';
        case 'top-right':
            return 'right top';
        case 'bottom-left':
            return 'left bottom';
        case 'bottom-right':
            return 'right bottom';
        default:
            return 'center center';
    }
}

function localCourseBannerBuilderGetPreviewZIndex(sortOrder, storedZIndex) {
    var priority = 0;
    if (storedZIndex >= 2000) {
        priority = 2;
    } else if (storedZIndex >= 1000) {
        priority = 1;
    }
    return (priority * 1000) + sortOrder + 1;
}

function localCourseBannerBuilderGetDraftPreviewZIndex(index) {
    var draftIndex = parseInt(index, 10);
    if (isNaN(draftIndex)) {
        draftIndex = 0;
    }
    return draftIndex + 1;
}

function localCourseBannerBuilderBuildDynamicPreviewImageStyle(options) {
    var styles = [
        'position: absolute;',
        'display: block;',
        'max-width: none;',
        'pointer-events: none;'
    ];
    var anchor = options.anchor || 'center';
    var fitMode = options.fitMode || 'bannerfit';
    var objectPosition = localCourseBannerBuilderGetPreviewObjectPosition(anchor);

    styles.push('aspect-ratio: ' + Math.max(1, options.naturalWidth || 1) + ' / ' + Math.max(1, options.naturalHeight || 1) + ';');

    if (fitMode === 'bannerfit') {
        styles.push('width: 100%;', 'height: 100%;');
    } else if (fitMode === 'cover') {
        styles.push('width: 100%;', 'height: 100%;', 'object-fit: contain;', 'object-position: ' + objectPosition + ';');
    } else if (fitMode === 'custom') {
        var customBox = localCourseBannerBuilderGetCustomPreviewBox(
            options.customWidth,
            options.customHeight,
            options.naturalWidth || 0,
            options.naturalHeight || 0,
            options.keepAspect
        );
        localCourseBannerBuilderAppendPreviewBoxStyles(styles, customBox);
        styles.push('object-fit: ' + (options.keepAspect ? 'contain' : 'fill') + ';');
        styles.push('object-position: ' + objectPosition + ';');
    } else {
        var originalBox = localCourseBannerBuilderGetOriginalPreviewBox(
            options.naturalWidth || 0,
            options.naturalHeight || 0,
            options.previewAspect || 0
        );
        styles.push('width: ' + originalBox.width + '%;', 'height: ' + originalBox.height + '%;');
        styles.push('object-fit: fill;', 'object-position: ' + objectPosition + ';');
    }

    if (fitMode !== 'bannerfit') {
        localCourseBannerBuilderAppendPreviewPositionStyles(styles, anchor, options.offsets);
    } else {
        styles.push('left: 0;', 'top: 0;');
    }
    return styles.join(' ');
}

function localCourseBannerBuilderAppendPreviewPositionStyles(styles, anchor, offsets) {
    styles.push('left: auto;', 'right: auto;', 'top: auto;', 'bottom: auto;', 'transform: none;');
    switch (anchor) {
        case 'top':
            styles.push('left: 50%;', 'top: ' + offsets.top + ';', 'transform: translateX(-50%);');
            break;
        case 'bottom':
            styles.push('left: 50%;', 'bottom: ' + offsets.bottom + ';', 'transform: translateX(-50%);');
            break;
        case 'left':
            styles.push('left: ' + offsets.left + ';', 'top: 50%;', 'transform: translateY(-50%);');
            break;
        case 'right':
            styles.push('right: ' + offsets.right + ';', 'top: 50%;', 'transform: translateY(-50%);');
            break;
        case 'top-left':
            styles.push('left: ' + offsets.left + ';', 'top: ' + offsets.top + ';');
            break;
        case 'top-right':
            styles.push('right: ' + offsets.right + ';', 'top: ' + offsets.top + ';');
            break;
        case 'bottom-left':
            styles.push('left: ' + offsets.left + ';', 'bottom: ' + offsets.bottom + ';');
            break;
        case 'bottom-right':
            styles.push('right: ' + offsets.right + ';', 'bottom: ' + offsets.bottom + ';');
            break;
        default:
            styles.push('left: 50%;', 'top: 50%;', 'transform: translate(-50%, -50%);');
            break;
    }
}

function localCourseBannerBuilderGetCustomPreviewBox(customWidth, customHeight, naturalWidth, naturalHeight, keepAspect) {
    if (keepAspect && naturalWidth > 0 && naturalHeight > 0) {
        var imageAspect = naturalWidth / naturalHeight;
        return {
            width: customWidth,
            height: customHeight,
            widthCss: 'min(' + customWidth + '%, ' + (customHeight * imageAspect).toFixed(6) + 'cqh)',
            heightCss: 'auto',
            aspectRatio: Math.max(1, naturalWidth) + ' / ' + Math.max(1, naturalHeight)
        };
    }

    return {
        width: customWidth,
        height: customHeight,
        widthCss: customWidth + '%',
        heightCss: customHeight + '%'
    };
}

function localCourseBannerBuilderGetLayerEffectiveImageAspect(layer) {
    if (!layer) {
        return 0;
    }
    var naturalWidth = localCourseBannerBuilderNormaliseNumericValue(
        layer.getAttribute('data-preview-natural-width') || '0',
        0
    );
    var naturalHeight = localCourseBannerBuilderNormaliseNumericValue(
        layer.getAttribute('data-preview-natural-height') || '0',
        0
    );
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    if ((naturalWidth <= 0 || naturalHeight <= 0) && image) {
        naturalWidth = image.naturalWidth || 0;
        naturalHeight = image.naturalHeight || 0;
    }
    if (naturalWidth <= 0 || naturalHeight <= 0) {
        return 0;
    }
    var crop = localCourseBannerBuilderNormaliseCropState({
        imagecropenabled: layer.getAttribute('data-preview-crop-enabled') === '1',
        imagecropleftpercent: layer.getAttribute('data-preview-crop-left') || '0',
        imagecroptoppercent: layer.getAttribute('data-preview-crop-top') || '0',
        imagecropwidthpercent: layer.getAttribute('data-preview-crop-width') || '100',
        imagecropheightpercent: layer.getAttribute('data-preview-crop-height') || '100'
    });
    return (naturalWidth * (crop.enabled ? crop.width / 100 : 1)) /
        Math.max(1, naturalHeight * (crop.enabled ? crop.height / 100 : 1));
}

function localCourseBannerBuilderGetClosestAspectPreviewBox(widthPercent, heightPercent, frameAspect, imageAspect) {
    if (widthPercent <= 0 || heightPercent <= 0 || frameAspect <= 0 || imageAspect <= 0) {
        return {
            width: widthPercent,
            height: heightPercent
        };
    }
    var heightForWidth = (widthPercent * frameAspect) / imageAspect;
    var widthForHeight = (heightPercent * imageAspect) / frameAspect;
    var keepWidthDelta = Math.abs(heightForWidth - heightPercent);
    var keepHeightDelta = Math.abs(widthForHeight - widthPercent);
    if (keepWidthDelta <= keepHeightDelta) {
        return {
            width: widthPercent,
            height: heightForWidth
        };
    }
    return {
        width: widthForHeight,
        height: heightPercent
    };
}

function localCourseBannerBuilderApplyClosestKeepAspectToLayerForm(form, layer) {
    if (!form || !layer) {
        return false;
    }
    var frame = layer.closest('[data-banner-preview-frame=\"1\"]');
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    var keepAspectInput = form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    if (!frame || !fitOverride || !anchorInput || !widthInput || !heightInput ||
            !offsetTopInput || !offsetLeftInput || !keepAspectInput) {
        return false;
    }
    var frameRect = frame.getBoundingClientRect();
    var visualLayer = localCourseBannerBuilderGetDraftSelectionVisualLayer(form, layer);
    var layerRect = (visualLayer || layer).getBoundingClientRect();
    if (!frameRect.width || !frameRect.height || !layerRect.width || !layerRect.height) {
        return false;
    }
    var currentWidth = (layerRect.width / frameRect.width) * 100;
    var currentHeight = (layerRect.height / frameRect.height) * 100;
    var imageAspect = localCourseBannerBuilderGetLayerEffectiveImageAspect(layer) ||
        (layerRect.width / Math.max(1, layerRect.height));
    var box = localCourseBannerBuilderGetClosestAspectPreviewBox(
        currentWidth,
        currentHeight,
        frameRect.width / frameRect.height,
        imageAspect
    );
    var centerLeft = ((layerRect.left - frameRect.left) / frameRect.width) * 100 + (currentWidth / 2);
    var centerTop = ((layerRect.top - frameRect.top) / frameRect.height) * 100 + (currentHeight / 2);
    var widthValue = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, box.width));
    var heightValue = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, box.height));

    form.dataset.previewApplyingInteraction = '1';
    try {
        keepAspectInput.checked = true;
        fitOverride.value = 'custom';
        anchorInput.value = 'top-left';
        widthInput.value = String(localCourseBannerBuilderRoundPreviewPercent(widthValue));
        heightInput.value = String(localCourseBannerBuilderRoundPreviewPercent(heightValue));
        offsetLeftInput.value = String(localCourseBannerBuilderRoundPreviewPercent(centerLeft - (widthValue / 2)));
        offsetTopInput.value = String(localCourseBannerBuilderRoundPreviewPercent(centerTop - (heightValue / 2)));
        form.dataset.previewUserChanged = '1';
        localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
        localCourseBannerBuilderSyncCustomSizeFields(form);
        localCourseBannerBuilderSyncOffsetFields(form);
        localCourseBannerBuilderBindPercentSliders(form);
        localCourseBannerBuilderSyncLayerBannerPreview(form);
    } finally {
        form.dataset.previewApplyingInteraction = '0';
    }
    return true;
}

function localCourseBannerBuilderApplyClosestKeepAspectToSourcePreview(root, layer) {
    if (!root || !layer) {
        return false;
    }
    var frame = root.querySelector('[data-source-preview-frame=\"1\"]');
    var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
    if (!frame || !state) {
        return false;
    }
    var frameRect = frame.getBoundingClientRect();
    var layerRect = layer.getBoundingClientRect();
    if (!frameRect.width || !frameRect.height || !layerRect.width || !layerRect.height) {
        return false;
    }

    var currentWidth = (layerRect.width / frameRect.width) * 100;
    var currentHeight = (layerRect.height / frameRect.height) * 100;
    var imageAspect = localCourseBannerBuilderGetLayerEffectiveImageAspect(layer) ||
        (layerRect.width / Math.max(1, layerRect.height));
    var box = localCourseBannerBuilderGetClosestAspectPreviewBox(
        currentWidth,
        currentHeight,
        frameRect.width / frameRect.height,
        imageAspect
    );
    var centerLeft = ((layerRect.left - frameRect.left) / frameRect.width) * 100 + (currentWidth / 2);
    var centerTop = ((layerRect.top - frameRect.top) / frameRect.height) * 100 + (currentHeight / 2);
    var widthValue = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, box.width));
    var heightValue = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, box.height));

    state.fitmodeoverride = 'custom';
    state.positionanchor = 'top-left';
    state.customsizekeepaspect = true;
    state.customwidthpercent = localCourseBannerBuilderRoundPreviewPercent(widthValue);
    state.customheightpercent = localCourseBannerBuilderRoundPreviewPercent(heightValue);
    state.offsetleftpercent = localCourseBannerBuilderRoundPreviewPercent(centerLeft - (widthValue / 2));
    state.offsettoppercent = localCourseBannerBuilderRoundPreviewPercent(centerTop - (heightValue / 2));
    state.offsetrightpercent = 0;
    state.offsetbottompercent = 0;
    localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
    localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
    localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
    localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
    return true;
}

function localCourseBannerBuilderAppendPreviewBoxStyles(styles, box) {
    styles.push('width: ' + (box.widthCss || (box.width + '%')) + ';');
    styles.push('height: ' + (box.heightCss || (box.height + '%')) + ';');
    if (box.aspectRatio) {
        styles.push('aspect-ratio: ' + box.aspectRatio + ';');
    }
}

function localCourseBannerBuilderGetPreviewFrameAspect(previewRoot) {
    if (!previewRoot) {
        return 1600 / 400;
    }
    var frame = previewRoot.matches && (
        previewRoot.matches('[data-banner-preview-frame=\"1\"]') ||
        previewRoot.matches('[data-source-preview-frame=\"1\"]')
    ) ? previewRoot : previewRoot.querySelector('[data-banner-preview-frame=\"1\"], [data-source-preview-frame=\"1\"]');
    frame = frame || previewRoot;
    var rect = frame.getBoundingClientRect ? frame.getBoundingClientRect() : null;
    if (rect && rect.width > 0 && rect.height > 0) {
        return rect.width / rect.height;
    }
    var className = frame.className ? String(frame.className) : '';
    if (className.indexOf('--format-fullwidthtopcompact') !== -1) {
        return 8;
    }
    if (className.indexOf('--format-contentwide') !== -1 || className.indexOf('--format-fullwidthtop') !== -1) {
        return 5;
    }
    return 1600 / 400;
}

function localCourseBannerBuilderGetContainedPreviewBox(naturalWidth, naturalHeight, previewAspect) {
    if (naturalWidth <= 0 || naturalHeight <= 0) {
        return {width: 100, height: 100};
    }
    var imageAspect = naturalWidth / naturalHeight;
    var bannerAspect = previewAspect && previewAspect > 0 ? previewAspect : (1600 / 400);
    if (imageAspect >= bannerAspect) {
        return {
            width: 100,
            height: 100 * (bannerAspect / imageAspect)
        };
    }
    return {
        width: 100 * (imageAspect / bannerAspect),
        height: 100
    };
}

function localCourseBannerBuilderGetOriginalPreviewBox(naturalWidth, naturalHeight, previewAspect) {
    var bannerAspect = previewAspect && previewAspect > 0 ? previewAspect : (1600 / 400);
    var baseWidth = 1600;
    var baseHeight = baseWidth / bannerAspect;
    return {
        width: naturalWidth > 0 ? Math.max(0, (naturalWidth / baseWidth) * 100) : 100,
        height: naturalHeight > 0 ? Math.max(0, (naturalHeight / baseHeight) * 100) : 100
    };
}

function localCourseBannerBuilderNormaliseDraftPreviewUrl(url) {
    if (!url) {
        return '';
    }
    var parts = String(url).split('?');
    if (parts.length < 2) {
        return String(url);
    }
    var params = new URLSearchParams(parts.slice(1).join('?'));
    params.delete('preview');
    params.delete('thumb');
    params.delete('oid');
    var query = params.toString();
    return parts[0] + (query ? '?' + query : '');
}

function localCourseBannerBuilderGetDraftPreviewFileIdentity(url, fallback) {
    var identity = localCourseBannerBuilderNormaliseDraftPreviewUrl(url || '');
    return identity || String(fallback || '');
}

function localCourseBannerBuilderGetDraftPreviewIndexMap(form) {
    return localCourseBannerBuilderReadJson(
        form && form.dataset ? (form.dataset.draftPreviewIndexMap || '{}') : '{}',
        {}
    );
}

function localCourseBannerBuilderSetDraftPreviewIndexMap(form, map) {
    if (!form || !form.dataset) {
        return;
    }
    form.dataset.draftPreviewIndexMap = JSON.stringify(map || {});
}

function localCourseBannerBuilderGetStableDraftPreviewIndex(form, url, fallback) {
    if (!form || !form.dataset) {
        return String(fallback || 0);
    }
    var identity = localCourseBannerBuilderGetDraftPreviewFileIdentity(url, fallback);
    var map = localCourseBannerBuilderGetDraftPreviewIndexMap(form);
    if (identity && typeof map[identity] !== 'undefined') {
        return String(map[identity]);
    }
    var next = parseInt(form.dataset.draftPreviewNextStableIndex || '0', 10) || 0;
    Object.keys(map).forEach(function(key) {
        var mappedIndex = parseInt(map[key], 10);
        if (!isNaN(mappedIndex) && mappedIndex >= next) {
            next = mappedIndex + 1;
        }
    });
    map[identity] = String(next);
    form.dataset.draftPreviewNextStableIndex = String(next + 1);
    localCourseBannerBuilderSetDraftPreviewIndexMap(form, map);
    return String(next);
}

function localCourseBannerBuilderIsDraftFilemanagerBusy(form) {
    var filemanager = form ? form.querySelector('#fitem_id_bannerimage_filemanager') : null;
    if (!filemanager) {
        return false;
    }
    var container = filemanager.querySelector('.filemanager, .filemanager-container') || filemanager;
    if (container.classList && (
            container.classList.contains('fm-updating') ||
            container.classList.contains('fm-loading') ||
            container.classList.contains('fm-uploading'))) {
        return true;
    }
    return Array.prototype.slice.call(filemanager.querySelectorAll(
        '.fp-img-downloading,' +
        '.dndupload-progressbars,' +
        '.dndupload-progress-outer,' +
        '.dndupload-progress-inner'
    )).some(function(node) {
        var style = window.getComputedStyle ? window.getComputedStyle(node) : null;
        return !node.hidden &&
            (!style || (style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0')) &&
            (node.offsetParent !== null || node.getClientRects().length > 0);
    });
}

function localCourseBannerBuilderGetVisibleDraftFileItems(form) {
    var filemanager = form ? form.querySelector('#fitem_id_bannerimage_filemanager') : null;
    if (!filemanager) {
        return [];
    }
    return Array.prototype.slice.call(filemanager.querySelectorAll('.fp-file:not(.fp-folder)')).filter(function(item) {
        return item.dataset.previewDeleted !== '1' && !item.hidden && item.getAttribute('aria-hidden') !== 'true';
    });
}

function localCourseBannerBuilderGetDraftPreviewFilesFromCache(form) {
    var cached = localCourseBannerBuilderReadJson(
        form && form.dataset ? (form.dataset.draftPreviewAjaxFiles || '[]') : '[]',
        []
    );
    if (!Array.isArray(cached)) {
        return [];
    }
    return cached.filter(function(file) {
        return file && file.url && !localCourseBannerBuilderIsDraftPreviewUrlSuppressed(form, file.url);
    }).map(function(file, order) {
        var stableIndex = localCourseBannerBuilderGetStableDraftPreviewIndex(form, file.url, order);
        return Object.assign({}, file, {
            index: parseInt(stableIndex, 10),
            fileorder: order
        });
    });
}

function localCourseBannerBuilderSetDraftPreviewFilesCache(form, files) {
    if (!form || !form.dataset) {
        return;
    }
    form.dataset.draftPreviewAjaxFiles = JSON.stringify(files || []);
}

function localCourseBannerBuilderClearDraftPreviewFilesCache(form) {
    if (!form || !form.dataset) {
        return;
    }
    delete form.dataset.draftPreviewAjaxFiles;
}

function localCourseBannerBuilderRefreshDraftPreviewFileList(form) {
    var itemIdInput = form ? form.querySelector('#id_bannerimage_filemanager') : null;
    if (!form || !itemIdInput || !itemIdInput.value || form.dataset.draftPreviewAjaxLoading === '1' ||
            !(window.M && M.cfg && M.cfg.wwwroot)) {
        return;
    }
    form.dataset.draftPreviewAjaxLoading = '1';
    var body = new URLSearchParams();
    body.set('sesskey', (M.cfg && M.cfg.sesskey) ? M.cfg.sesskey : '');
    body.set('itemid', itemIdInput.value);
    body.set('filepath', '/');
    fetch(M.cfg.wwwroot + '/repository/draftfiles_ajax.php?action=list', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
        body: body.toString()
    }).then(function(response) {
        return response.ok ? response.json() : null;
    }).then(function(data) {
        var list = data && Array.isArray(data.list) ? data.list : [];
        var files = [];
        list.forEach(function(file) {
            if (!file || file.type === 'folder' || file.children) {
                return;
            }
            var url = file.url || file.source || file.thumbnail || '';
            url = localCourseBannerBuilderNormaliseDraftPreviewUrl(url);
            if (!url || url.indexOf('/theme/image.php') !== -1) {
                return;
            }
            files.push({
                index: files.length,
                url: url,
                name: file.fullname || file.filename || file.title || ('Layer ' + (files.length + 1)),
                item: null,
                deleted: false,
                width: file.thumbnail_width || 0,
                height: file.thumbnail_height || 0
            });
        });
        localCourseBannerBuilderSetDraftPreviewFilesCache(form, files);
        form.dataset.draftPreviewSignature = '';
        form.dataset.draftPreviewRenderedActive = '';
        localCourseBannerBuilderSyncDraftUploadPreview(form);
        localCourseBannerBuilderSyncModalPreviewActionButtons(form);
    }).catch(function() {
        localCourseBannerBuilderSetDraftPreviewFilesCache(form, []);
    }).finally(function() {
        form.dataset.draftPreviewAjaxLoading = '0';
    });
}

function localCourseBannerBuilderClampPreviewOffset(value) {
    return Math.max(-1000, Math.min(1000, value));
}

function localCourseBannerBuilderIsPersistedImageLayerEdit(form) {
    if (!form) {
        return false;
    }
    var existingImageInput = form.querySelector('#id_hasexistingimage');
    var elementIdInput = form.querySelector('#id_elementid');
    var currentIsBorderLayerInput = form.querySelector('#id_currentisborderlayer');
    var hasExistingImage = !!(existingImageInput && parseInt(existingImageInput.value || '0', 10) > 0);
    var hasElement = !!(elementIdInput && parseInt(elementIdInput.value || '0', 10) > 0);
    var isBorderLayer = !!(
        currentIsBorderLayerInput &&
        parseInt(currentIsBorderLayerInput.value || '0', 10) > 0
    );

    return hasExistingImage || (hasElement && !isBorderLayer);
}

function localCourseBannerBuilderRememberCurrentPreviewImageUrl(form, url) {
    if (!form || !url) {
        return;
    }
    form.dataset.currentPreviewImageUrl = String(url);
}

function localCourseBannerBuilderForgetCurrentPreviewImageUrl(form) {
    if (!form) {
        return;
    }
    delete form.dataset.currentPreviewImageUrl;
}

function localCourseBannerBuilderDraftFilemanagerHasVisibleContent(form) {
    var filemanager = form ? form.querySelector('#fitem_id_bannerimage_filemanager') : null;
    if (!filemanager) {
        return false;
    }
    var container = filemanager.querySelector('.filemanager, .filemanager-container') || filemanager;
    if (container.classList && (
            container.classList.contains('fm-noitems') ||
            container.classList.contains('fm-nofiles'))) {
        return false;
    }
    return !!filemanager.querySelector(
        '.fp-file:not(.fp-folder):not([hidden]):not([aria-hidden=\"true\"]),' +
        '.fp-filename-icon,' +
        '.fp-tableview tbody tr,' +
        '.fp-treeview .ygtvitem'
    );
}

function localCourseBannerBuilderGetPreviewImageUrl(form, layer) {
    if (!form || !layer) {
        return '';
    }
    var filemanager = form.querySelector('#fitem_id_bannerimage_filemanager');
    if (filemanager) {
        var draftFiles = localCourseBannerBuilderIsDraftFilemanagerBusy(form) ?
            null :
            localCourseBannerBuilderGetDraftPreviewFiles(form);
        if (typeof form.dataset.activeDraftIndex !== 'undefined' && form.dataset.activeDraftIndex !== '') {
            var files = draftFiles || [];
            var activeFile = files.find(function(file) {
                return String(file.index) === String(form.dataset.activeDraftIndex);
            });
            if (activeFile && activeFile.url) {
                return activeFile.url;
            }
        }
        var link = filemanager.matches && filemanager.matches('a[href*=\"/draftfile.php/\"], a[href*=\"/pluginfile.php/\"]') ?
            filemanager : filemanager.querySelector('a[href*=\"/draftfile.php/\"], a[href*=\"/pluginfile.php/\"]');
        if (link && link.getAttribute('href')) {
            return localCourseBannerBuilderNormaliseDraftPreviewUrl(link.getAttribute('href'));
        }
        var image = filemanager.querySelector('img[src*=\"/draftfile.php/\"], img[src*=\"/pluginfile.php/\"]');
        if (image && image.getAttribute('src')) {
            return localCourseBannerBuilderNormaliseDraftPreviewUrl(image.getAttribute('src'));
        }
        if (!localCourseBannerBuilderIsPersistedImageLayerEdit(form) &&
                draftFiles !== null &&
                !draftFiles.length &&
                !localCourseBannerBuilderDraftFilemanagerHasVisibleContent(form)) {
            localCourseBannerBuilderForgetCurrentPreviewImageUrl(form);
            return '';
        }
    }
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    var fallbackUrl = layer.getAttribute('data-preview-current-url') ||
        (image ? (image.getAttribute('src') || '') : '') ||
        form.dataset.currentPreviewImageUrl ||
        '';
    if (fallbackUrl) {
        localCourseBannerBuilderRememberCurrentPreviewImageUrl(form, fallbackUrl);
    }
    return fallbackUrl;
}

function localCourseBannerBuilderGetPreviewContextToggle(preview, layerScope) {
    var hosts = [];
    if (preview && preview.closest) {
        hosts.push(preview.closest('.local-course-banner-builder-banner-preview-panel'));
        hosts.push(preview.closest('form.mform'));
        hosts.push(preview.closest('.modal'));
    }
    if (preview && preview.parentElement) {
        hosts.push(preview.parentElement);
    }
    if (layerScope) {
        hosts.push(layerScope);
    }

    for (var i = 0; i < hosts.length; i++) {
        if (!hosts[i] || !hosts[i].querySelector) {
            continue;
        }
        var toggle = hosts[i].querySelector('[data-preview-context-toggle=\"1\"]');
        if (toggle) {
            return toggle;
        }
    }
    return null;
}

function localCourseBannerBuilderSyncContextPreviewVisibility(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerScope) {
        return;
    }
    Array.prototype.slice.call(layerScope.querySelectorAll('[data-layer-banner-preview=\"1\"]')).forEach(function(preview) {
        var toggle = localCourseBannerBuilderGetPreviewContextToggle(preview, layerScope);
        var showContext = !toggle || toggle.checked;
        Array.prototype.slice.call(preview.querySelectorAll('[data-preview-context-layer=\"1\"]')).forEach(function(layer) {
            layer.hidden = !showContext;
        });
    });
}

function localCourseBannerBuilderUpdatePreviewAspectLockButton(layer) {
    if (!layer) {
        return;
    }
    var button = layer.querySelector('[data-preview-aspect-lock=\"1\"]');
    if (!button) {
        return;
    }
    var enabled = layer.getAttribute('data-preview-keep-aspect') === '1';
    var icon = button.querySelector('.fa');
    if (icon) {
        icon.classList.toggle('fa-expand', enabled);
        icon.classList.toggle('fa-link', !enabled);
    }
    var label = enabled ?
        localCourseBannerBuilderGetJsString('allowstretchpreviewimage', '') :
        localCourseBannerBuilderGetJsString('keepaspectpreviewimage', '');
    button.setAttribute('aria-label', label);
    button.setAttribute('data-local-course-banner-builder-hover-popover', '1');
    button.setAttribute('data-placement', 'top');
    button.setAttribute('data-html', 'true');
    button.setAttribute('data-local-course-banner-builder-popover-label', label);
    button.setAttribute('data-content', '<div class=no-overflow><p>' + label + '</p></div>');
    button.setAttribute('title', '');
    button.removeAttribute('data-toggle');
    button.removeAttribute('data-bs-toggle');
    button.removeAttribute('data-trigger');
    button.removeAttribute('data-bs-trigger');
    button.removeAttribute('data-original-title');
    button.removeAttribute('data-bs-original-title');
    button.classList.toggle('local-course-banner-builder-preview-aspect-lock--locked', enabled);
    if (typeof localCourseBannerBuilderInitPopovers === 'function') {
        localCourseBannerBuilderInitPopovers(button);
    }
    window.requestAnimationFrame(function() {
        var rect = layer.getBoundingClientRect();
        var hidden = !rect.width || !rect.height || rect.width < 42 || rect.height < 42;
        layer.classList.toggle('local-course-banner-builder-preview-image-layer--aspect-lock-hidden', hidden);
    });
}

function localCourseBannerBuilderTogglePreviewAspectLock(button) {
    if (!button) {
        return;
    }
    var sourceRoot = button.closest('[data-source-visual-editor=\"1\"]');
    if (sourceRoot) {
        if (localCourseBannerBuilderIsSourcePreviewReadonly(sourceRoot)) {
            return;
        }
        var sourceLayer = button.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') ||
            localCourseBannerBuilderGetSelectedSourcePreviewLayer(sourceRoot);
        if (!sourceLayer) {
            return;
        }
        localCourseBannerBuilderSelectSourcePreviewLayer(sourceRoot, sourceLayer);
        var sourceState = localCourseBannerBuilderGetSourcePreviewLayerState(sourceLayer);
        if (!sourceState) {
            return;
        }
        localCourseBannerBuilderPushSourcePreviewHistory(sourceRoot);
        if (!sourceState.customsizekeepaspect &&
                localCourseBannerBuilderApplyClosestKeepAspectToSourcePreview(sourceRoot, sourceLayer)) {
            return;
        }
        sourceState.customsizekeepaspect = false;
        localCourseBannerBuilderSetSourcePreviewLayerState(sourceLayer, sourceState);
        localCourseBannerBuilderSyncSourcePreviewLayer(sourceRoot, sourceLayer);
        localCourseBannerBuilderUpdateSourcePreviewRow(sourceRoot, sourceState);
        localCourseBannerBuilderSyncSourcePreviewPayload(sourceRoot);
        localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(sourceRoot);
        return;
    }

    var form = localCourseBannerBuilderGetLayerScope(button);
    if (!form) {
        return;
    }
    var draftLayer = button.closest('[data-preview-draft-layer=\"1\"]');
    if (draftLayer) {
        var draftIndex = draftLayer.getAttribute('data-draft-index');
        if (draftIndex !== null) {
            localCourseBannerBuilderSelectDraftPreviewLayer(form, draftIndex);
        }
    }
    var currentLayer = form.querySelector('[data-preview-current-layer=\"1\"]');
    var keepAspectInput = form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    if (!currentLayer || !keepAspectInput) {
        return;
    }
    if (!keepAspectInput.checked &&
            localCourseBannerBuilderApplyClosestKeepAspectToLayerForm(form, currentLayer)) {
        currentLayer.setAttribute('data-preview-keep-aspect', '1');
        localCourseBannerBuilderUpdatePreviewAspectLockButton(currentLayer);
        localCourseBannerBuilderSyncDraftUploadPreview(form);
        localCourseBannerBuilderSaveActiveDraftPreviewState(form);
        localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        return;
    }
    keepAspectInput.checked = !keepAspectInput.checked;
    keepAspectInput.dispatchEvent(new Event('change', {bubbles: true}));
    form.dataset.previewUserChanged = '1';
    currentLayer.setAttribute('data-preview-keep-aspect', keepAspectInput.checked ? '1' : '0');
    localCourseBannerBuilderUpdatePreviewAspectLockButton(currentLayer);
    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    localCourseBannerBuilderSyncDraftUploadPreview(form);
}

function localCourseBannerBuilderSyncCurrentImagePreview(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form) {
        return;
    }

    var currentLayers = Array.prototype.slice.call(form.querySelectorAll('[data-preview-current-image=\"1\"]'));
    if (!currentLayers.length) {
        return;
    }

    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var sortOrderInput = form.querySelector('#id_sortorder');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    var keepAspectInput = form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetRightInput = form.querySelector('#id_offsetrightpercent');
    var offsetBottomInput = form.querySelector('#id_offsetbottompercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    var preferStoredState = form.dataset.previewUserChanged !== '1';

    currentLayers.forEach(function(layer) {
        var previewRoot = layer.closest('[data-layer-banner-preview=\"1\"]');
        var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
        if (!previewRoot || !image) {
            return;
        }

        var defaultFitMode = previewRoot.getAttribute('data-default-fitmode') || 'bannerfit';
        var storedFitMode = layer.getAttribute('data-preview-fitmode') || '';
        var fitMode = preferStoredState ? (storedFitMode || defaultFitMode) :
            (fitOverride && fitOverride.value ? fitOverride.value : (storedFitMode || defaultFitMode));
        var anchor = preferStoredState ? (layer.getAttribute('data-preview-anchor') || 'center') :
            (anchorInput && anchorInput.value ? anchorInput.value : (layer.getAttribute('data-preview-anchor') || 'center'));
        var readPreviewNumber = function(attribute, input, fallback) {
            var value = preferStoredState ?
                (layer.getAttribute(attribute) || fallback) :
                (input && input.value ? input.value : (layer.getAttribute(attribute) || fallback));
            return localCourseBannerBuilderNormaliseNumericValue(value, Number(fallback) || 0);
        };
        var offsets = {
            top: String(readPreviewNumber('data-preview-offset-top', offsetTopInput, '0')) + '%',
            right: String(readPreviewNumber('data-preview-offset-right', offsetRightInput, '0')) + '%',
            bottom: String(readPreviewNumber('data-preview-offset-bottom', offsetBottomInput, '0')) + '%',
            left: String(readPreviewNumber('data-preview-offset-left', offsetLeftInput, '0')) + '%'
        };
        var customWidth = Math.max(
            0,
            Math.min(localCourseBannerBuilderCustomSizePercentMax, readPreviewNumber(
                'data-preview-custom-width',
                widthInput,
                '100'
            ))
        );
        var customHeight = Math.max(
            0,
            Math.min(localCourseBannerBuilderCustomSizePercentMax, readPreviewNumber(
                'data-preview-custom-height',
                heightInput,
                '100'
            ))
        );
        var keepAspect = preferStoredState ? (layer.getAttribute('data-preview-keep-aspect') === '1') :
            (keepAspectInput ? keepAspectInput.checked : (layer.getAttribute('data-preview-keep-aspect') === '1'));
        var imageOpacityInput = form.querySelector('#id_imageopacity');
        var imageOpacity = preferStoredState ?
            localCourseBannerBuilderClampPercent(layer.getAttribute('data-preview-image-opacity') || '100', 100) :
            localCourseBannerBuilderClampPercent(
                imageOpacityInput && imageOpacityInput.value !== '' ?
                    imageOpacityInput.value :
                    (layer.getAttribute('data-preview-image-opacity') || '100'),
                100
            );
        var cropState = localCourseBannerBuilderReadPreviewCropState(form, layer, preferStoredState);
        var naturalWidth = localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-width') || image.naturalWidth || '0', 0);
        var naturalHeight = localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-height') || image.naturalHeight || '0', 0);
        if ((naturalWidth <= 0 || naturalHeight <= 0) && image.complete && image.naturalWidth && image.naturalHeight) {
            naturalWidth = image.naturalWidth;
            naturalHeight = image.naturalHeight;
            layer.setAttribute('data-preview-natural-width', String(naturalWidth));
            layer.setAttribute('data-preview-natural-height', String(naturalHeight));
            if (layer.hasAttribute('data-preview-draft-layer')) {
                var draftSettings = localCourseBannerBuilderGetDraftPreviewSettings(form);
                var draftIndex = layer.getAttribute('data-draft-index');
                if (draftIndex !== null && draftSettings[draftIndex]) {
                    draftSettings[draftIndex].imagewidth = naturalWidth;
                    draftSettings[draftIndex].imageheight = naturalHeight;
                    localCourseBannerBuilderSetDraftPreviewSettings(form, draftSettings);
                }
            }
        }
        var aboveBorder = layer.getAttribute('data-preview-dynamic-image') === '1';
        var dynamicImage = false;
        var imageUrl = localCourseBannerBuilderGetPreviewImageUrl(form, layer);
        var draftLayerIndex = layer.hasAttribute('data-preview-draft-layer') ?
            parseInt(layer.getAttribute('data-draft-index') || layer.getAttribute('data-preview-sortorder') || '0', 10) : NaN;
        var sortOrder = isNaN(draftLayerIndex) ?
            Math.max(0, parseInt(sortOrderInput && sortOrderInput.value ? sortOrderInput.value : (layer.getAttribute('data-preview-sortorder') || '0'), 10) || 0) :
            Math.max(0, draftLayerIndex);
        var storedZIndex = aboveBorder ? 2000 : (
            parseInt(
                layer.getAttribute('data-preview-zindex') ||
                (isNaN(draftLayerIndex) ? '0' : String(localCourseBannerBuilderGetDraftPreviewZIndex(draftLayerIndex))),
                10
            ) || 0
        );
        var effectiveZIndex = !isNaN(draftLayerIndex) && !aboveBorder ?
            (storedZIndex || localCourseBannerBuilderGetDraftPreviewZIndex(draftLayerIndex)) :
            localCourseBannerBuilderGetPreviewZIndex(sortOrder, storedZIndex);
        var objectPosition = localCourseBannerBuilderGetPreviewObjectPosition(anchor);
        var layerStyles = [
            'position: absolute;',
            'display: flex;',
            'align-items: stretch;',
            'justify-content: stretch;',
            'overflow: hidden;',
            'z-index: ' + effectiveZIndex + ';'
        ];
        var imageStyles = [
            'display: block;',
            'width: 100%;',
            'height: 100%;'
        ];

        if (dynamicImage) {
            if (fitMode === 'custom') {
                var dynamicCustomBox = localCourseBannerBuilderGetCustomPreviewBox(
                    customWidth,
                    customHeight,
                    naturalWidth,
                    naturalHeight,
                    keepAspect
                );
                layerStyles = [
                    'position: absolute;',
                    'display: flex;',
                    'align-items: stretch;',
                    'justify-content: stretch;',
                    'overflow: hidden;',
                    'z-index: ' + effectiveZIndex + ';'
                ];
                localCourseBannerBuilderAppendPreviewBoxStyles(layerStyles, dynamicCustomBox);
                localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
                imageStyles = [
                    'display: block;',
                    'width: 100%;',
                    'height: 100%;',
                    'object-fit: ' + (keepAspect ? 'contain' : 'fill') + ';',
                    'object-position: ' + objectPosition + ';'
                ];
            } else {
                if ((fitMode === 'cover' || fitMode === 'original') && naturalWidth > 0 && naturalHeight > 0) {
                    var dynamicVisibleBox = fitMode === 'cover' ?
                        localCourseBannerBuilderGetContainedPreviewBox(naturalWidth, naturalHeight, localCourseBannerBuilderGetPreviewFrameAspect(previewRoot)) :
                        localCourseBannerBuilderGetOriginalPreviewBox(
                            naturalWidth,
                            naturalHeight,
                            localCourseBannerBuilderGetPreviewFrameAspect(previewRoot)
                        );
                    layerStyles = [
                        'position: absolute;',
                        'display: flex;',
                        'align-items: stretch;',
                        'justify-content: stretch;',
                        'overflow: hidden;',
                        'width: ' + dynamicVisibleBox.width + '%;',
                        'height: ' + dynamicVisibleBox.height + '%;',
                        'z-index: ' + effectiveZIndex + ';'
                    ];
                    localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
                    imageStyles = [
                        'display: block;',
                        'width: 100%;',
                        'height: 100%;',
                        'object-fit: fill;',
                        'object-position: ' + objectPosition + ';'
                    ];
                } else {
                    layerStyles = [
                        'position: absolute;',
                        'inset: 0;',
                        'overflow: hidden;',
                        'z-index: ' + effectiveZIndex + ';'
                    ];
                    imageStyles = [localCourseBannerBuilderBuildDynamicPreviewImageStyle({
                        anchor: anchor,
                        fitMode: fitMode,
                        customWidth: customWidth,
                        customHeight: customHeight,
                        keepAspect: keepAspect,
                        naturalWidth: naturalWidth,
                        naturalHeight: naturalHeight,
                        previewAspect: localCourseBannerBuilderGetPreviewFrameAspect(previewRoot),
                        offsets: offsets
                    })];
                }
            }
        } else if (fitMode === 'bannerfit') {
            layerStyles.push('inset: 0;');
            imageStyles.push('object-fit: fill;');
        } else if (fitMode === 'cover') {
            if (naturalWidth > 0 && naturalHeight > 0) {
                var containedBox = localCourseBannerBuilderGetContainedPreviewBox(
                    naturalWidth,
                    naturalHeight,
                    localCourseBannerBuilderGetPreviewFrameAspect(previewRoot)
                );
                layerStyles.push('width: ' + containedBox.width + '%;', 'height: ' + containedBox.height + '%;');
                localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
                imageStyles.push('object-fit: fill;', 'object-position: ' + objectPosition + ';');
            } else {
                layerStyles.push('inset: 0;');
                imageStyles.push('object-fit: contain;', 'object-position: ' + objectPosition + ';');
            }
        } else if (fitMode === 'custom') {
            var customBox = localCourseBannerBuilderGetCustomPreviewBox(
                customWidth,
                customHeight,
                naturalWidth,
                naturalHeight,
                keepAspect
            );
            localCourseBannerBuilderAppendPreviewBoxStyles(layerStyles, customBox);
            localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
            imageStyles.push('object-fit: ' + (keepAspect ? 'contain' : 'fill') + ';', 'object-position: ' + objectPosition + ';');
        } else {
            var originalPreviewBox = localCourseBannerBuilderGetOriginalPreviewBox(
                naturalWidth,
                naturalHeight,
                localCourseBannerBuilderGetPreviewFrameAspect(previewRoot)
            );
            layerStyles.push('width: ' + originalPreviewBox.width + '%;', 'height: ' + originalPreviewBox.height + '%;');
            localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
            imageStyles.push('object-fit: fill;', 'object-position: ' + objectPosition + ';');
        }

        imageStyles.push('opacity: ' + (imageOpacity / 100).toFixed(3) + ';');
        localCourseBannerBuilderApplyCropToImageStyles(imageStyles, cropState, fitMode === 'bannerfit');
        if (form.dataset.draftPreviewSwitching === '1' && layer.hasAttribute('data-preview-draft-selection-overlay')) {
            layerStyles.push('visibility: hidden;');
        }
        layer.style.cssText = layerStyles.join(' ');
        localCourseBannerBuilderUpdateCropSelectionFrame(layer, localCourseBannerBuilderNormaliseCropState(cropState));
        image.style.cssText = imageStyles.join(' ');
        layer.setAttribute('data-preview-keep-aspect', keepAspect ? '1' : '0');
        localCourseBannerBuilderUpdatePreviewAspectLockButton(layer);
        localCourseBannerBuilderMirrorDraftSelectionVisual(form, layer);
        if (!image.dataset.previewLoadBound) {
            image.addEventListener('load', function() {
                layer.setAttribute('data-preview-natural-width', String(image.naturalWidth || 0));
                layer.setAttribute('data-preview-natural-height', String(image.naturalHeight || 0));
                localCourseBannerBuilderSyncCurrentImagePreview(form);
                localCourseBannerBuilderSyncModalPreviewActionButtons(form);
            });
            image.dataset.previewLoadBound = '1';
        }
        if (imageUrl) {
            if (image.getAttribute('src') !== imageUrl) {
                image.setAttribute('src', imageUrl);
            }
            image.hidden = false;
            layer.hidden = false;
            layer.setAttribute('data-preview-current-url', imageUrl);
            layer.setAttribute('data-preview-current-layer', '1');
            localCourseBannerBuilderRememberCurrentPreviewImageUrl(form, imageUrl);
        } else if (localCourseBannerBuilderIsPersistedImageLayerEdit(form)) {
            layer.hidden = false;
            layer.setAttribute('data-preview-current-layer', '1');
        } else {
            layer.hidden = true;
            layer.removeAttribute('data-preview-current-layer');
        }
    });
}

function localCourseBannerBuilderRefreshCurrentPreviewLayer(form) {
    if (!form) {
        return;
    }
    var currentLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    var image = currentLayer ? currentLayer.querySelector('[data-preview-image-tag=\"1\"]') : null;
    if (!currentLayer || !image) {
        return;
    }
    if (image.complete) {
        currentLayer.setAttribute('data-preview-natural-width', String(image.naturalWidth || 0));
        currentLayer.setAttribute('data-preview-natural-height', String(image.naturalHeight || 0));
    }
    localCourseBannerBuilderSyncCurrentImagePreview(form);
}

function localCourseBannerBuilderSyncLayerBannerPreview(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerScope) {
        return;
    }
    var sortOrderInput = layerScope.querySelector('#id_sortorder');
    var currentBorder = layerScope.querySelector('[data-preview-current-border=\"1\"]');
    if (currentBorder) {
        var sortOrderValue = sortOrderInput && sortOrderInput.value ?
            sortOrderInput.value :
            (currentBorder.getAttribute('data-preview-sortorder') || '0');
        var sortOrder = Math.max(0, parseInt(sortOrderValue, 10) || 0);
        var storedZIndex = parseInt(currentBorder.getAttribute('data-preview-zindex') || '0', 10) || 0;
        currentBorder.style.zIndex = String(localCourseBannerBuilderGetPreviewZIndex(sortOrder, storedZIndex));
    }
    localCourseBannerBuilderSyncContextPreviewVisibility(layerScope);
    localCourseBannerBuilderSyncCurrentImagePreview(layerScope);
    localCourseBannerBuilderSyncDraftUploadPreview(layerScope);
    localCourseBannerBuilderSyncModalPreviewActionButtons(layerScope);
    localCourseBannerBuilderSyncBorderSidePicker(layerScope);
}

var localCourseBannerBuilderPreviewInteraction = null;
var localCourseBannerBuilderPendingPreviewInteraction = null;

function localCourseBannerBuilderRoundPreviewPercent(value) {
    return Math.round(value * 10) / 10;
}

function localCourseBannerBuilderSetPreviewFieldValue(field, value) {
    if (!field) {
        return;
    }
    field.value = String(localCourseBannerBuilderRoundPreviewPercent(value));
    field.dispatchEvent(new Event('input', {bubbles: true}));
    field.dispatchEvent(new Event('change', {bubbles: true}));
}

function localCourseBannerBuilderRunPreviewInteractionFieldBatch(state, callback) {
    var form = state ? state.form : null;
    if (form) {
        form.dataset.previewApplyingInteraction = '1';
    }
    try {
        callback();
    } finally {
        if (form) {
            form.dataset.previewApplyingInteraction = '0';
            localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
            localCourseBannerBuilderSyncLayerBannerPreview(form);
        }
    }
}

function localCourseBannerBuilderRecenterPreviewImage(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form) {
        return;
    }
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetRightInput = form.querySelector('#id_offsetrightpercent');
    var offsetBottomInput = form.querySelector('#id_offsetbottompercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    if (anchorInput) {
        anchorInput.value = 'center';
        anchorInput.dispatchEvent(new Event('change', {bubbles: true}));
    }
    [offsetTopInput, offsetRightInput, offsetBottomInput, offsetLeftInput].forEach(function(field) {
        localCourseBannerBuilderSetPreviewFieldValue(field, 0);
    });
    localCourseBannerBuilderSyncLayerBannerPreview(form);
}

function localCourseBannerBuilderRecenterAllLayerPreviewImages(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form) {
        return;
    }
    var settings = localCourseBannerBuilderGetDraftPreviewSettings(form);
    Object.keys(settings).forEach(function(index) {
        settings[index].positionanchor = 'center';
        settings[index].offsettoppercent = 0;
        settings[index].offsetrightpercent = 0;
        settings[index].offsetbottompercent = 0;
        settings[index].offsetleftpercent = 0;
    });
    localCourseBannerBuilderSetDraftPreviewSettings(form, settings);

    var activeIndex = typeof form.dataset.activeDraftIndex !== 'undefined' ? String(form.dataset.activeDraftIndex) : '';
    if (activeIndex !== '' && settings[activeIndex]) {
        localCourseBannerBuilderRenderDraftUploadPreview(form);
        return;
    }

    localCourseBannerBuilderRecenterPreviewImage(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
}

function localCourseBannerBuilderRecenterSourcePreviewImage(scope) {
    var root = scope && scope.closest ? scope.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    if (!layer) {
        return;
    }
    var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
    if (!state) {
        return;
    }
    state.positionanchor = 'center';
    state.offsettoppercent = 0;
    state.offsetrightpercent = 0;
    state.offsetbottompercent = 0;
    state.offsetleftpercent = 0;
    localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
    localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
    localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
}

function localCourseBannerBuilderRecenterAllSourcePreviewImages(scope) {
    var root = scope && scope.closest ? scope.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]')).forEach(function(layer) {
        var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
        if (!state) {
            return;
        }
        state.positionanchor = 'center';
        state.offsettoppercent = 0;
        state.offsetrightpercent = 0;
        state.offsetbottompercent = 0;
        state.offsetleftpercent = 0;
        localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
        localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
        localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
    });
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
}

function localCourseBannerBuilderApplyFitToLayerFormPreview(form) {
    if (!form) {
        return;
    }
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    var keepAspectInput = form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetRightInput = form.querySelector('#id_offsetrightpercent');
    var offsetBottomInput = form.querySelector('#id_offsetbottompercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    if (fitOverride) {
        fitOverride.value = 'cover';
    }
    if (anchorInput) {
        anchorInput.value = 'center';
    }
    if (widthInput) {
        widthInput.value = '100';
    }
    if (heightInput) {
        heightInput.value = '100';
    }
    if (keepAspectInput) {
        keepAspectInput.checked = true;
    }
    [offsetTopInput, offsetRightInput, offsetBottomInput, offsetLeftInput].forEach(function(field) {
        if (field) {
            field.value = '0';
        }
    });
    form.dataset.previewUserChanged = '1';
    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
}

function localCourseBannerBuilderApplyFillBannerToLayerFormPreview(form) {
    if (!form) {
        return;
    }
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    var keepAspectInput = form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetRightInput = form.querySelector('#id_offsetrightpercent');
    var offsetBottomInput = form.querySelector('#id_offsetbottompercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    var previewRoot = form.querySelector('[data-layer-banner-preview=\"1\"], [data-source-preview-frame=\"1\"], .local-course-banner-builder-border-preview-frame');
    var defaultFitMode = previewRoot ? (previewRoot.getAttribute('data-default-fitmode') || '') : '';
    if (fitOverride) {
        fitOverride.value = defaultFitMode === 'bannerfit' ? '' : 'bannerfit';
    }
    if (anchorInput) {
        anchorInput.value = 'center';
    }
    if (widthInput) {
        widthInput.value = '100';
    }
    if (heightInput) {
        heightInput.value = '100';
    }
    if (keepAspectInput) {
        keepAspectInput.checked = false;
    }
    [offsetTopInput, offsetRightInput, offsetBottomInput, offsetLeftInput].forEach(function(field) {
        if (field) {
            field.value = '0';
        }
    });
    form.dataset.previewUserChanged = '1';
    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
}

function localCourseBannerBuilderFitSelectedLayerPreviewImageToFrame(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form) {
        return;
    }
    var currentLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    if (!currentLayer || currentLayer.hidden) {
        return;
    }
    localCourseBannerBuilderApplyFitToLayerFormPreview(form);
}

function localCourseBannerBuilderFillSelectedLayerPreviewImageToBanner(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form) {
        return;
    }
    var currentLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    if (!currentLayer || currentLayer.hidden) {
        return;
    }
    localCourseBannerBuilderApplyFillBannerToLayerFormPreview(form);
}

function localCourseBannerBuilderFitSelectedSourcePreviewImageToFrame(scope) {
    var root = scope && scope.closest ? scope.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    if (!layer) {
        return;
    }
    var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
    if (!state) {
        return;
    }
    state.fitmodeoverride = 'cover';
    state.positionanchor = 'center';
    state.customwidthpercent = 100;
    state.customheightpercent = 100;
    state.customsizekeepaspect = true;
    state.offsettoppercent = 0;
    state.offsetrightpercent = 0;
    state.offsetbottompercent = 0;
    state.offsetleftpercent = 0;
    localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
    localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
    localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
    localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
    localCourseBannerBuilderSyncSourcePreviewFitButton(root);
    localCourseBannerBuilderSyncSourcePreviewFillButton(root);
    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
}

function localCourseBannerBuilderFillSelectedSourcePreviewImageToBanner(scope) {
    var root = scope && scope.closest ? scope.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    if (!layer) {
        return;
    }
    var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
    if (!state) {
        return;
    }
    var frame = root.querySelector('[data-source-preview-frame=\"1\"]');
    var defaultFitMode = frame ? (frame.getAttribute('data-default-fitmode') || '') : '';
    state.fitmodeoverride = defaultFitMode === 'bannerfit' ? '' : 'bannerfit';
    state.positionanchor = 'center';
    state.customwidthpercent = 100;
    state.customheightpercent = 100;
    state.customsizekeepaspect = false;
    state.offsettoppercent = 0;
    state.offsetrightpercent = 0;
    state.offsetbottompercent = 0;
    state.offsetleftpercent = 0;
    localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
    localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
    localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
    localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
    localCourseBannerBuilderSyncSourcePreviewFitButton(root);
    localCourseBannerBuilderSyncSourcePreviewFillButton(root);
    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
}

function localCourseBannerBuilderPushSelectedSourcePreviewLayerBehind(scope) {
    var root = scope && scope.closest ? scope.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    if (!layer) {
        return;
    }
    var layerId = layer.getAttribute('data-source-preview-layer-id') || '';
    if (!layerId) {
        return;
    }
    var tbody = document.querySelector('.local-course-banner-builder-layer-sortable');
    if (!tbody) {
        return;
    }
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row'));
    var currentRow = rows.find(function(row) {
        var checkbox = row.querySelector('.local-course-banner-builder-layer-select');
        return checkbox && checkbox.value === layerId;
    });
    if (!currentRow) {
        return;
    }
    var movableRows = rows.filter(function(row) {
        return !row.classList.contains('local-course-banner-builder-layer-row--border');
    });
    var currentIndex = movableRows.indexOf(currentRow);
    if (currentIndex <= 0) {
        return;
    }
    var previousRow = movableRows[currentIndex - 1];
    if (!previousRow) {
        return;
    }
    tbody.insertBefore(currentRow, previousRow);
    localCourseBannerBuilderEnforceLockedLayerOrder(tbody);
    localCourseBannerBuilderSyncLayerSortOrders();
    localCourseBannerBuilderSyncSourcePreviewOrder(root);
    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
}

function localCourseBannerBuilderPullSelectedSourcePreviewLayerForward(scope) {
    var root = scope && scope.closest ? scope.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    if (!layer) {
        return;
    }
    var layerId = layer.getAttribute('data-source-preview-layer-id') || '';
    if (!layerId) {
        return;
    }
    var tbody = document.querySelector('.local-course-banner-builder-layer-sortable');
    if (!tbody) {
        return;
    }
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row'));
    var currentRow = rows.find(function(row) {
        var checkbox = row.querySelector('.local-course-banner-builder-layer-select');
        return checkbox && checkbox.value === layerId;
    });
    if (!currentRow) {
        return;
    }
    var movableRows = rows.filter(function(row) {
        return !row.classList.contains('local-course-banner-builder-layer-row--border');
    });
    var currentIndex = movableRows.indexOf(currentRow);
    if (currentIndex === -1 || currentIndex >= movableRows.length - 1) {
        return;
    }
    var nextRow = movableRows[currentIndex + 1];
    if (!nextRow) {
        return;
    }
    tbody.insertBefore(nextRow, currentRow);
    localCourseBannerBuilderEnforceLockedLayerOrder(tbody);
    localCourseBannerBuilderSyncLayerSortOrders();
    localCourseBannerBuilderSyncSourcePreviewOrder(root);
    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
}

function localCourseBannerBuilderSyncSourcePreviewTopLayerButton(root) {
    if (!root) {
        return;
    }
    var button = root.querySelector('[data-action=\"local-course-banner-builder-toggle-source-preview-top-layer\"]');
    if (!button) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    var enabled = !!(layer && layer.getAttribute('data-preview-dynamic-image') === '1');
    button.setAttribute('data-top-layer-enabled', enabled ? '1' : '0');
    button.classList.toggle('btn-primary', enabled);
    button.classList.toggle('btn-outline-secondary', !enabled);
    button.classList.toggle('local-course-banner-builder-source-preview-button--active', enabled);
    localCourseBannerBuilderSetActionButtonContent(
        button,
        enabled ? (button.getAttribute('data-on-icon') || 'fa-level-down') : (button.getAttribute('data-off-icon') || 'fa-level-up'),
        enabled ? (button.getAttribute('data-on-label') || 'Keep below border') : (button.getAttribute('data-off-label') || 'Push above border')
    );
}

function localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root) {
    if (!root) {
        return;
    }
    var button = root.querySelector('[data-action=\"local-course-banner-builder-toggle-source-preview-keep-aspect\"]');
    if (!button) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    var enabled = !!(layer && layer.getAttribute('data-preview-keep-aspect') === '1');
    button.setAttribute('data-keep-aspect-enabled', enabled ? '1' : '0');
    localCourseBannerBuilderSetActionButtonContent(
        button,
        enabled ? (button.getAttribute('data-on-icon') || 'fa-expand') : (button.getAttribute('data-off-icon') || 'fa-link'),
        enabled ?
            (button.getAttribute('data-on-label') ||
                localCourseBannerBuilderGetJsString('allowstretchpreviewimage', '')) :
            (button.getAttribute('data-off-label') ||
                localCourseBannerBuilderGetJsString('keepaspectpreviewimage', ''))
    );
}

function localCourseBannerBuilderSyncSourcePreviewOpacityButton(root) {
    if (!root) {
        return;
    }
    var button = root.querySelector('[data-action=\"local-course-banner-builder-toggle-source-preview-opacity\"]');
    var panel = root.querySelector('[data-source-preview-opacity-panel=\"1\"]');
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    var enabled = !!(layer && layer.getAttribute('data-source-preview-editable') === '1');
    if (button) {
        button.disabled = !enabled;
        button.classList.toggle('disabled', !enabled);
        button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        if (panel) {
            var panelOpen = enabled && !panel.hidden;
            button.classList.toggle('btn-primary', panelOpen);
            button.classList.toggle('btn-outline-secondary', !panelOpen);
            button.classList.toggle('active', panelOpen);
            button.classList.toggle('local-course-banner-builder-source-preview-button--active', panelOpen);
            button.setAttribute('aria-expanded', panelOpen ? 'true' : 'false');
        }
    }
    if (panel) {
        if (!enabled) {
            panel.hidden = true;
            if (button) {
                button.setAttribute('aria-expanded', 'false');
                button.classList.remove('btn-primary', 'active');
                button.classList.remove('local-course-banner-builder-source-preview-button--active');
                button.classList.add('btn-outline-secondary');
            }
            return;
        }
        localCourseBannerBuilderSetOpacityPanelValue(
            panel,
            layer.getAttribute('data-preview-image-opacity') || '100'
        );
    }
}

function localCourseBannerBuilderSyncSourcePreviewFitButton(root) {
    if (!root) {
        return;
    }
    var button = root.querySelector('[data-action=\"local-course-banner-builder-fit-source-preview-image\"]');
    if (!button) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    var enabled = !!(layer && layer.getAttribute('data-source-preview-editable') === '1');
    button.disabled = !enabled;
    button.classList.toggle('disabled', !enabled);
    button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
}

function localCourseBannerBuilderSyncSourcePreviewFillButton(root) {
    if (!root) {
        return;
    }
    var button = root.querySelector('[data-action=\"local-course-banner-builder-fill-source-preview-image\"]');
    if (!button) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    var enabled = !!(layer && layer.getAttribute('data-source-preview-editable') === '1');
    button.disabled = !enabled;
    button.classList.toggle('disabled', !enabled);
    button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
}

function localCourseBannerBuilderSyncSourcePreviewCropButtons(root) {
    if (!root) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    var enabled = !!(layer && layer.getAttribute('data-source-preview-editable') === '1');
    var active = !!(enabled && layer.querySelector('[data-preview-crop-editor=\"1\"]'));
    var toggle = root.querySelector('[data-action=\"local-course-banner-builder-toggle-source-preview-crop\"]');
    var apply = root.querySelector('[data-action=\"local-course-banner-builder-apply-source-preview-crop\"]');
    if (toggle) {
        toggle.disabled = !enabled;
        toggle.classList.toggle('disabled', !enabled);
        toggle.classList.toggle('btn-primary', active);
        toggle.classList.toggle('btn-outline-secondary', !active);
        toggle.classList.toggle('active', active);
        toggle.classList.remove(
            'local-course-banner-builder-crop-applied',
            'local-course-banner-builder-preview-toolbar-button--active'
        );
        toggle.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        toggle.setAttribute('aria-pressed', active ? 'true' : 'false');
    }
    if (apply) {
        apply.hidden = !active;
        apply.disabled = !active;
        apply.setAttribute('aria-disabled', active ? 'false' : 'true');
    }
}

function localCourseBannerBuilderSyncSourcePreviewOrderButtons(root) {
    if (!root) {
        return;
    }
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    var enabled = !!(layer && layer.getAttribute('data-source-preview-editable') === '1');
    Array.prototype.slice.call(root.querySelectorAll([
        '[data-action=\"local-course-banner-builder-push-source-preview-layer-behind\"]',
        '[data-action=\"local-course-banner-builder-pull-source-preview-layer-forward\"]'
    ].join(','))).forEach(function(button) {
        button.disabled = !enabled;
        button.classList.toggle('disabled', !enabled);
        button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    });
}

function localCourseBannerBuilderSyncSourcePreviewDeleteButton(root) {
    if (!root) {
        return;
    }
    var button = root.querySelector('[data-action=\"local-course-banner-builder-delete-selected-preview-layer\"]');
    if (!button) {
        return;
    }
    var layer = root.querySelector('.local-course-banner-builder-source-preview-layer--selected');
    if (!localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer)) {
        layer = null;
    }
    var hasLayer = !!(layer && layer.getAttribute('data-source-preview-layer-id'));
    button.hidden = !hasLayer;
    if (hasLayer) {
        button.setAttribute('data-layer-id', layer.getAttribute('data-source-preview-layer-id') || '');
    } else {
        button.removeAttribute('data-layer-id');
    }
}

function localCourseBannerBuilderGetSourcePreviewThumbnailSignature(layers) {
    return (layers || []).map(function(layer) {
        var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
        return [
            layer.getAttribute('data-source-preview-layer-id') || '',
            image ? (image.currentSrc || image.getAttribute('src') || '') : '',
            localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer) ? '1' : '0'
        ].join(':');
    }).join('|');
}

function localCourseBannerBuilderUpdateSourcePreviewThumbnailSelection(root) {
    if (!root) {
        return;
    }
    var filmstrip = root.querySelector('[data-source-preview-filmstrip=\"1\"]');
    var track = filmstrip ? filmstrip.querySelector('[data-source-preview-filmstrip-track=\"1\"]') : null;
    if (!filmstrip || !track) {
        return;
    }
    var selected = root.querySelector('.local-course-banner-builder-source-preview-layer--selected');
    var selectedId = selected ? (selected.getAttribute('data-source-preview-layer-id') || '') : '';
    var activeButton = null;
    Array.prototype.slice.call(track.querySelectorAll('[data-source-preview-thumbnail-id]')).forEach(function(button) {
        var isActive = selectedId !== '' && button.getAttribute('data-source-preview-thumbnail-id') === selectedId &&
            !button.classList.contains('is-disabled');
        var layerId = button.getAttribute('data-source-preview-thumbnail-id') || '';
        var layer = layerId ? root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-layer-id=\"' + layerId + '\"]') : null;
        var isHiddenInPreview = !!(layer && localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer));
        button.classList.toggle('is-active', isActive);
        button.classList.toggle('is-hidden-in-preview', isHiddenInPreview);
        localCourseBannerBuilderSyncSourcePreviewThumbnailVisibilityButton(button, !isHiddenInPreview);
        if (isActive) {
            activeButton = button;
        }
    });
    if (activeButton && activeButton.scrollIntoView) {
        activeButton.scrollIntoView({behavior: 'smooth', inline: 'center', block: 'nearest'});
    }
    localCourseBannerBuilderUpdateSourcePreviewFilmstripNav(filmstrip);
    localCourseBannerBuilderUpdateSourcePreviewVisibilityToggle(root);
}

function localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer) {
    return !!(layer && layer.classList &&
        layer.classList.contains('local-course-banner-builder-source-preview-layer--hidden-in-preview'));
}

function localCourseBannerBuilderSetSourcePreviewLayerVisibleInPreview(root, layer, visible) {
    if (!root || !layer) {
        return;
    }
    layer.classList.toggle('local-course-banner-builder-source-preview-layer--hidden-in-preview', !visible);
    layer.setAttribute('data-source-preview-hidden-in-preview', visible ? '0' : '1');
    if (!visible) {
        layer.classList.remove('local-course-banner-builder-source-preview-layer--selected');
        layer.removeAttribute('data-preview-current-layer');
        layer.removeAttribute('data-preview-active-edge');
    }
}

function localCourseBannerBuilderSyncSourcePreviewThumbnailVisibilityButton(thumbnail, visible) {
    var button = thumbnail ? thumbnail.querySelector('[data-source-preview-thumbnail-visibility]') : null;
    if (!button) {
        return;
    }
    button.setAttribute('aria-pressed', visible ? 'false' : 'true');
    button.setAttribute(
        'aria-label',
        visible ?
            localCourseBannerBuilderGetJsString('hideimageinpreview', 'Hide image in preview') :
            localCourseBannerBuilderGetJsString('showimageinpreview', 'Show image in preview')
    );
    button.innerHTML = visible ?
        '<i class=\"fa fa-eye\" aria-hidden=\"true\"></i>' :
        '<i class=\"fa fa-eye-slash\" aria-hidden=\"true\"></i>';
}

function localCourseBannerBuilderUpdateSourcePreviewVisibilityToggle(root) {
    if (!root) {
        return;
    }
    var row = root.querySelector('[data-source-preview-visibility-toggle-row=\"1\"]');
    var button = row ? row.querySelector('[data-action=\"local-course-banner-builder-toggle-all-source-preview-images\"]') : null;
    if (!row || !button) {
        return;
    }
    var layers = Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]')).filter(function(layer) {
        var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
        return !!(image && (image.currentSrc || image.getAttribute('src')));
    });
    row.hidden = !layers.length;
    button.hidden = !layers.length;
    if (!layers.length) {
        return;
    }
    var anyVisible = layers.some(function(layer) {
        return !localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer);
    });
    button.setAttribute('aria-pressed', anyVisible ? 'false' : 'true');
    var toggleLabel = localCourseBannerBuilderGetJsString('showhideallimages', 'Show / hide all images');
    button.innerHTML = anyVisible ?
        '<i class=\"fa fa-eye-slash\" aria-hidden=\"true\"></i><span class=\"sr-only\">' + toggleLabel + '</span>' :
        '<i class=\"fa fa-eye\" aria-hidden=\"true\"></i><span class=\"sr-only\">' + toggleLabel + '</span>';
}

function localCourseBannerBuilderToggleSourcePreviewThumbnailVisibility(button) {
    var thumbnail = button ? button.closest('[data-source-preview-thumbnail-id]') : null;
    var root = button ? button.closest('[data-source-visual-editor=\"1\"]') : null;
    var layerId = thumbnail ? (thumbnail.getAttribute('data-source-preview-thumbnail-id') || '') : '';
    var layer = root && layerId ? root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-layer-id=\"' + layerId + '\"]') : null;
    if (!root || !thumbnail || !layer) {
        return;
    }
    var visible = localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer);
    localCourseBannerBuilderSetSourcePreviewLayerVisibleInPreview(root, layer, visible);
    thumbnail.classList.toggle('is-hidden-in-preview', !visible);
    localCourseBannerBuilderSyncSourcePreviewThumbnailVisibilityButton(thumbnail, visible);
    localCourseBannerBuilderSyncSourcePreviewTopLayerButton(root);
    localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
    localCourseBannerBuilderSyncSourcePreviewOpacityButton(root);
    localCourseBannerBuilderSyncSourcePreviewFitButton(root);
    localCourseBannerBuilderSyncSourcePreviewFillButton(root);
    localCourseBannerBuilderSyncSourcePreviewOrderButtons(root);
    localCourseBannerBuilderSyncSourcePreviewDeleteButton(root);
    localCourseBannerBuilderUpdateSourcePreviewVisibilityToggle(root);
}

function localCourseBannerBuilderToggleAllSourcePreviewImageVisibility(button) {
    var root = button ? button.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var layers = Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]')).filter(function(layer) {
        var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
        return !!(image && (image.currentSrc || image.getAttribute('src')));
    });
    if (!layers.length) {
        return;
    }
    var shouldHide = layers.some(function(layer) {
        return !localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer);
    });
    layers.forEach(function(layer) {
        localCourseBannerBuilderSetSourcePreviewLayerVisibleInPreview(root, layer, !shouldHide);
    });
    localCourseBannerBuilderUpdateSourcePreviewThumbnailSelection(root);
    localCourseBannerBuilderSyncSourcePreviewTopLayerButton(root);
    localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
    localCourseBannerBuilderSyncSourcePreviewOpacityButton(root);
    localCourseBannerBuilderSyncSourcePreviewFitButton(root);
    localCourseBannerBuilderSyncSourcePreviewFillButton(root);
    localCourseBannerBuilderSyncSourcePreviewCropButtons(root);
    localCourseBannerBuilderSyncSourcePreviewDeleteButton(root);
}

function localCourseBannerBuilderSetSourcePreviewInheritedLayersVisible(root, visible) {
    if (!root) {
        return;
    }
    root.setAttribute('data-source-preview-inherited-visible', visible ? '1' : '0');
    Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-inherited=\"1\"]')).forEach(function(layer) {
        layer.classList.toggle('local-course-banner-builder-source-preview-layer--hidden-inherited', !visible);
        layer.classList.toggle('local-course-banner-builder-source-preview-border--hidden-inherited', !visible);
        layer.setAttribute('aria-hidden', visible ? 'false' : 'true');
        if (!visible && layer.classList.contains('local-course-banner-builder-source-preview-layer--selected')) {
            layer.classList.remove('local-course-banner-builder-source-preview-layer--selected');
            layer.removeAttribute('data-preview-current-layer');
            layer.removeAttribute('data-preview-active-edge');
        }
    });
    localCourseBannerBuilderSyncSourcePreviewInheritedLayersButton(root);
}

function localCourseBannerBuilderSyncSourcePreviewInheritedLayersButton(root) {
    if (!root) {
        return;
    }
    var button = root.querySelector(
        '[data-action=\"local-course-banner-builder-toggle-source-preview-inherited-layers\"]'
    );
    if (!button) {
        return;
    }
    var hasInherited = !!root.querySelector('[data-source-preview-inherited=\"1\"]');
    button.hidden = !hasInherited;
    button.disabled = !hasInherited;
    button.classList.toggle('disabled', !hasInherited);
    button.setAttribute('aria-disabled', hasInherited ? 'false' : 'true');
    if (!hasInherited) {
        return;
    }
    var visible = root.getAttribute('data-source-preview-inherited-visible') === '1';
    button.setAttribute('data-inherited-visible', visible ? '1' : '0');
    button.setAttribute('aria-pressed', visible ? 'true' : 'false');
    localCourseBannerBuilderSetActionButtonContent(
        button,
        visible ? (button.getAttribute('data-hide-icon') || 'fa-eye-slash') :
            (button.getAttribute('data-show-icon') || 'fa-eye'),
        visible ? (button.getAttribute('data-hide-label') || '') :
            (button.getAttribute('data-show-label') || '')
    );
}

function localCourseBannerBuilderToggleSourcePreviewInheritedLayers(button) {
    var root = button ? button.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var visible = root.getAttribute('data-source-preview-inherited-visible') === '1';
    localCourseBannerBuilderSetSourcePreviewInheritedLayersVisible(root, !visible);
}

function localCourseBannerBuilderSyncSourcePreviewThumbnails(root) {
    if (!root) {
        return;
    }
    var filmstrip = root.querySelector('[data-source-preview-filmstrip=\"1\"]');
    if (!filmstrip) {
        return;
    }
    var layers = Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]')).filter(function(layer) {
        var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
        return !!(image && (image.currentSrc || image.getAttribute('src')));
    });
    var selected = root.querySelector('.local-course-banner-builder-source-preview-layer--selected');
    var signature = localCourseBannerBuilderGetSourcePreviewThumbnailSignature(layers);
    if (filmstrip.dataset.sourcePreviewFilmstripSignature === signature &&
            filmstrip.querySelector('[data-source-preview-filmstrip-track=\"1\"]')) {
        filmstrip.hidden = !layers.length;
        localCourseBannerBuilderUpdateSourcePreviewThumbnailSelection(root);
        return;
    }
    filmstrip.innerHTML = '';
    filmstrip.hidden = !layers.length;
    filmstrip.dataset.sourcePreviewFilmstripSignature = signature;
    if (!layers.length) {
        return;
    }
    var previousButton = document.createElement('button');
    previousButton.type = 'button';
    previousButton.className = 'btn btn-link local-course-banner-builder-source-preview-filmstrip-nav local-course-banner-builder-source-preview-filmstrip-nav--previous';
    previousButton.setAttribute('data-action', 'local-course-banner-builder-scroll-source-preview-filmstrip');
    previousButton.setAttribute('data-source-preview-filmstrip-direction', '-1');
    previousButton.setAttribute('aria-label', localCourseBannerBuilderGetJsString('previousimages', ''));
    previousButton.innerHTML = '<i class=\"icon fa fa-chevron-left fa-fw\" aria-hidden=\"true\"></i>';
    var track = document.createElement('div');
    track.className = 'local-course-banner-builder-source-preview-filmstrip-track';
    track.setAttribute('data-source-preview-filmstrip-track', '1');
    var nextButton = document.createElement('button');
    nextButton.type = 'button';
    nextButton.className = 'btn btn-link local-course-banner-builder-source-preview-filmstrip-nav local-course-banner-builder-source-preview-filmstrip-nav--next';
    nextButton.setAttribute('data-action', 'local-course-banner-builder-scroll-source-preview-filmstrip');
    nextButton.setAttribute('data-source-preview-filmstrip-direction', '1');
    nextButton.setAttribute('aria-label', localCourseBannerBuilderGetJsString('nextimages', ''));
    nextButton.innerHTML = '<i class=\"icon fa fa-chevron-right fa-fw\" aria-hidden=\"true\"></i>';
    filmstrip.appendChild(previousButton);
    filmstrip.appendChild(track);
    filmstrip.appendChild(nextButton);
    layers.forEach(function(layer, index) {
        var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
        var layerId = layer.getAttribute('data-source-preview-layer-id') || '';
        var isDisabled = !localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer);
        var disabledLabel = localCourseBannerBuilderGetJsString('disabledlayerthumbnail', '');
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'local-course-banner-builder-source-preview-thumbnail';
        button.setAttribute('data-action', 'local-course-banner-builder-select-source-preview-thumbnail');
        button.setAttribute('data-source-preview-thumbnail-id', layerId);
        button.setAttribute(
            'aria-label',
            localCourseBannerBuilderGetJsString('selectlayer', '') + ' ' + (index + 1)
        );
        button.classList.toggle('is-active', layer === selected && !isDisabled);
        button.classList.toggle('is-disabled', isDisabled);
        button.classList.toggle('is-hidden-in-preview', localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer));
        var thumb = document.createElement('img');
        thumb.src = image.currentSrc || image.getAttribute('src') || '';
        thumb.alt = '';
        button.appendChild(thumb);
        var visibilityButton = document.createElement('span');
        visibilityButton.setAttribute('role', 'button');
        visibilityButton.setAttribute('tabindex', '0');
        visibilityButton.className = 'local-course-banner-builder-source-preview-thumbnail-visibility';
        visibilityButton.setAttribute('data-action', 'local-course-banner-builder-toggle-source-preview-thumbnail-visibility');
        visibilityButton.setAttribute('data-source-preview-thumbnail-visibility', '1');
        visibilityButton.setAttribute('aria-pressed',
            localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer) ? 'true' : 'false');
        visibilityButton.setAttribute('aria-label',
            localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer) ?
                localCourseBannerBuilderGetJsString('showimageinpreview', '') :
                localCourseBannerBuilderGetJsString('hideimageinpreview', ''));
        visibilityButton.innerHTML = localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer) ?
            '<i class=\"fa fa-eye-slash\" aria-hidden=\"true\"></i>' :
            '<i class=\"fa fa-eye\" aria-hidden=\"true\"></i>';
        button.appendChild(visibilityButton);
        if (isDisabled) {
            button.setAttribute('data-toggle', 'popover');
            button.setAttribute('data-placement', 'top');
            button.setAttribute('data-html', 'true');
            button.setAttribute('data-trigger', 'hover');
            button.setAttribute('data-content', '<div class=\"no-overflow\"><p>' + disabledLabel + '</p></div>');
            button.setAttribute('aria-label', disabledLabel);
            var badge = document.createElement('span');
            badge.className = 'local-course-banner-builder-source-preview-thumbnail-disabled-badge';
            badge.textContent = 'D';
            button.appendChild(badge);
        }
        track.appendChild(button);
    });
    var addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'local-course-banner-builder-source-preview-thumbnail ' +
        'local-course-banner-builder-source-preview-thumbnail--add';
    addButton.setAttribute('aria-label', localCourseBannerBuilderGetJsString('addlayer', 'Add one or more layer(s)'));
    if (root.getAttribute('data-has-source-settings') === '0') {
        addButton.disabled = true;
        addButton.setAttribute('aria-disabled', 'true');
    } else {
        addButton.setAttribute('data-toggle', 'modal');
        addButton.setAttribute('data-target', '#local-course-banner-builder-add-layer-modal');
        addButton.setAttribute('data-bs-toggle', 'modal');
        addButton.setAttribute('data-bs-target', '#local-course-banner-builder-add-layer-modal');
    }
    addButton.innerHTML = '<i class=\"fa fa-plus\" aria-hidden=\"true\"></i>';
    track.appendChild(addButton);
    track.addEventListener('scroll', function() {
        localCourseBannerBuilderUpdateSourcePreviewFilmstripNav(filmstrip);
    }, {passive: true});
    window.requestAnimationFrame(function() {
        var active = track.querySelector('.local-course-banner-builder-source-preview-thumbnail.is-active');
        if (active && active.scrollIntoView) {
            active.scrollIntoView({behavior: 'smooth', inline: 'center', block: 'nearest'});
        }
        localCourseBannerBuilderUpdateSourcePreviewFilmstripNav(filmstrip);
    });
    localCourseBannerBuilderInitPopovers(filmstrip);
    localCourseBannerBuilderUpdateSourcePreviewVisibilityToggle(root);
}

function localCourseBannerBuilderUpdateSourcePreviewFilmstripNav(filmstrip) {
    if (!filmstrip) {
        return;
    }
    var track = filmstrip.querySelector('[data-source-preview-filmstrip-track=\"1\"]');
    var previousButton = filmstrip.querySelector('[data-source-preview-filmstrip-direction=\"-1\"]');
    var nextButton = filmstrip.querySelector('[data-source-preview-filmstrip-direction=\"1\"]');
    if (!track || !previousButton || !nextButton) {
        return;
    }
    var isOverflowing = track.scrollWidth > track.clientWidth + 2;
    filmstrip.classList.toggle('is-overflowing', isOverflowing);
    previousButton.classList.remove('is-hidden');
    nextButton.classList.remove('is-hidden');
    previousButton.setAttribute('aria-hidden', 'false');
    nextButton.setAttribute('aria-hidden', 'false');
    previousButton.disabled = !isOverflowing || track.scrollLeft <= 1;
    nextButton.disabled = !isOverflowing || (track.scrollLeft + track.clientWidth >= track.scrollWidth - 1);
}

function localCourseBannerBuilderScrollSourcePreviewFilmstrip(button) {
    var filmstrip = button ? button.closest('[data-source-preview-filmstrip=\"1\"]') : null;
    var track = filmstrip ? filmstrip.querySelector('[data-source-preview-filmstrip-track=\"1\"]') : null;
    if (!filmstrip || !track) {
        return;
    }
    var direction = parseInt(button.getAttribute('data-source-preview-filmstrip-direction') || '1', 10) || 1;
    var scrollAmount = Math.max(120, Math.round(track.clientWidth * 0.72));
    var maxScrollLeft = Math.max(0, track.scrollWidth - track.clientWidth);
    var targetScrollLeft = Math.max(0, Math.min(maxScrollLeft, track.scrollLeft + (direction * scrollAmount)));
    if (direction < 0 && targetScrollLeft < scrollAmount * 0.45) {
        targetScrollLeft = 0;
    }
    if (direction > 0 && (maxScrollLeft - targetScrollLeft) < scrollAmount * 0.45) {
        targetScrollLeft = maxScrollLeft;
    }
    track.scrollTo({
        left: targetScrollLeft,
        behavior: 'smooth'
    });
    window.setTimeout(function() {
        localCourseBannerBuilderUpdateSourcePreviewFilmstripNav(filmstrip);
    }, 280);
}

function localCourseBannerBuilderSelectSourcePreviewThumbnail(button) {
    var root = button ? button.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    if (button.classList && button.classList.contains('is-disabled')) {
        return;
    }
    var layerId = button.getAttribute('data-source-preview-thumbnail-id') || '';
    var layer = layerId ? root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-layer-id=\"' + layerId + '\"]') : null;
    if (layer && localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer)) {
        localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
        localCourseBannerBuilderUpdateSourcePreviewThumbnailSelection(root);
    }
}

function localCourseBannerBuilderDeleteSelectedPreviewLayer(button) {
    var root = button ? button.closest('[data-source-visual-editor=\"1\"]') : null;
    var layer = root ? root.querySelector('.local-course-banner-builder-source-preview-layer--selected') : null;
    var layerId = layer ? (layer.getAttribute('data-source-preview-layer-id') || '') : '';
    if (!root || !layerId) {
        return;
    }
    var message = button.getAttribute('data-confirm-message') ||
        localCourseBannerBuilderGetJsString('areyousure', 'Are you sure?');
    if (!window.confirm(message)) {
        return;
    }
    var formData = new FormData();
    formData.append('sesskey', M.cfg.sesskey || '');
    formData.append('sourcekey', root.getAttribute('data-sourcekey') || '');
    formData.append('deletepreviewlayerajax', layerId);
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).then(function(response) {
        if (!response.ok) {
            throw new Error(localCourseBannerBuilderGetJsString(
                'unabletodeleteselectedlayer',
                'Unable to delete selected layer'
            ));
        }
        return response.json();
    }).then(function(data) {
        if (!data || !data.success || typeof data.html !== 'string') {
            throw new Error(localCourseBannerBuilderGetJsString(
                'invaliddeleteselectedlayerresponse',
                'Invalid delete-selected-layer response'
            ));
        }
        var host = document.querySelector('[data-selected-source-content=\"1\"]');
        if (!host) {
            return;
        }
        host.innerHTML = data.html;
        var replacement = host.querySelector('[data-selected-source-content=\"1\"]');
        if (replacement) {
            host.replaceWith(replacement);
            host = replacement;
        }
        localCourseBannerBuilderRehydrateSelectedSourceContent(host);
        localCourseBannerBuilderRemoveLayerFromLayerModals(layerId);
    }).catch(function(error) {
        window.console.error(error);
        window.alert(error.message || localCourseBannerBuilderGetJsString(
            'unabletodeleteselectedlayer',
            'Unable to delete selected layer'
        ));
    });
}

function localCourseBannerBuilderReadLayerFormPreviewState(form) {
    if (!form) {
        return null;
    }
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    var keepAspectInput = form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    var dynamicInput = form.querySelector('#id_dynamicimagesizeenabled');
    var imageOpacityInput = form.querySelector('#id_imageopacity');
    var cropEnabledInput = form.querySelector('#id_imagecropenabled');
    var cropLeftInput = form.querySelector('#id_imagecropleftpercent');
    var cropTopInput = form.querySelector('#id_imagecroptoppercent');
    var cropWidthInput = form.querySelector('#id_imagecropwidthpercent');
    var cropHeightInput = form.querySelector('#id_imagecropheightpercent');
    if (imageOpacityInput && imageOpacityInput.value === '') {
        imageOpacityInput.value = '100';
    }
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetRightInput = form.querySelector('#id_offsetrightpercent');
    var offsetBottomInput = form.querySelector('#id_offsetbottompercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    var currentLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    var image = currentLayer ? currentLayer.querySelector('[data-preview-image-tag=\"1\"]') : null;

    return {
        fitmodeoverride: fitOverride ? (fitOverride.value || '') : '',
        positionanchor: anchorInput ? (anchorInput.value || 'center') : 'center',
        customwidthpercent: localCourseBannerBuilderNormaliseNumericValue(widthInput ? widthInput.value : '100', 100),
        customheightpercent: localCourseBannerBuilderNormaliseNumericValue(heightInput ? heightInput.value : '100', 100),
        customsizekeepaspect: !!(keepAspectInput && keepAspectInput.checked),
        dynamicimagesizeenabled: !!(dynamicInput && dynamicInput.checked),
        imageopacity: localCourseBannerBuilderClampPercent(imageOpacityInput ? imageOpacityInput.value : '100', 100),
        imagecropenabled: !!(cropEnabledInput && cropEnabledInput.value === '1'),
        imagecropleftpercent: localCourseBannerBuilderClampPercent(cropLeftInput ? cropLeftInput.value : '0', 0),
        imagecroptoppercent: localCourseBannerBuilderClampPercent(cropTopInput ? cropTopInput.value : '0', 0),
        imagecropwidthpercent: localCourseBannerBuilderClampCropSize(cropWidthInput ? cropWidthInput.value : '100'),
        imagecropheightpercent: localCourseBannerBuilderClampCropSize(cropHeightInput ? cropHeightInput.value : '100'),
        offsettoppercent: localCourseBannerBuilderNormaliseNumericValue(offsetTopInput ? offsetTopInput.value : '0', 0),
        offsetrightpercent: localCourseBannerBuilderNormaliseNumericValue(offsetRightInput ? offsetRightInput.value : '0', 0),
        offsetbottompercent: localCourseBannerBuilderNormaliseNumericValue(offsetBottomInput ? offsetBottomInput.value : '0', 0),
        offsetleftpercent: localCourseBannerBuilderNormaliseNumericValue(offsetLeftInput ? offsetLeftInput.value : '0', 0),
        imagewidth: currentLayer ? localCourseBannerBuilderNormaliseNumericValue(
            currentLayer.getAttribute('data-preview-natural-width') || (image ? image.naturalWidth : '0'),
            0
        ) : 0,
        imageheight: currentLayer ? localCourseBannerBuilderNormaliseNumericValue(
            currentLayer.getAttribute('data-preview-natural-height') || (image ? image.naturalHeight : '0'),
            0
        ) : 0,
        url: currentLayer ? (currentLayer.getAttribute('data-preview-current-url') || (image ? image.getAttribute('src') : '')) : ''
    };
}

function localCourseBannerBuilderReadLayerPreviewStateFromLayer(layer) {
    if (!layer) {
        return null;
    }
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    return {
        fitmodeoverride: layer.getAttribute('data-preview-fitmode') || '',
        positionanchor: layer.getAttribute('data-preview-anchor') || 'center',
        customwidthpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-width') || '100', 100),
        customheightpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-height') || '100', 100),
        customsizekeepaspect: layer.getAttribute('data-preview-keep-aspect') === '1',
        dynamicimagesizeenabled: layer.getAttribute('data-preview-dynamic-image') === '1',
        imageopacity: localCourseBannerBuilderClampPercent(layer.getAttribute('data-preview-image-opacity') || '100', 100),
        imagecropenabled: layer.getAttribute('data-preview-crop-enabled') === '1',
        imagecropleftpercent: localCourseBannerBuilderClampPercent(layer.getAttribute('data-preview-crop-left') || '0', 0),
        imagecroptoppercent: localCourseBannerBuilderClampPercent(layer.getAttribute('data-preview-crop-top') || '0', 0),
        imagecropwidthpercent: localCourseBannerBuilderClampCropSize(layer.getAttribute('data-preview-crop-width') || '100'),
        imagecropheightpercent: localCourseBannerBuilderClampCropSize(layer.getAttribute('data-preview-crop-height') || '100'),
        offsettoppercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-top') || '0', 0),
        offsetrightpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-right') || '0', 0),
        offsetbottompercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-bottom') || '0', 0),
        offsetleftpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-left') || '0', 0),
        imagewidth: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-width') || (image ? image.naturalWidth : '0'), 0),
        imageheight: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-height') || (image ? image.naturalHeight : '0'), 0),
        url: layer.getAttribute('data-preview-current-url') || (image ? image.getAttribute('src') : ''),
        sortorder: parseInt(layer.getAttribute('data-preview-sortorder') || layer.getAttribute('data-draft-index') || '0', 10) || 0,
        zindex: parseInt(layer.getAttribute('data-preview-zindex') || '', 10) ||
            localCourseBannerBuilderGetDraftPreviewZIndex(layer.getAttribute('data-draft-index') || '0')
    };
}

function localCourseBannerBuilderSyncCurrentLayerDataFromForm(form) {
    if (!form) {
        return;
    }
    var currentLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    if (!currentLayer) {
        return;
    }
    var state = localCourseBannerBuilderReadLayerFormPreviewState(form);
    if (!state) {
        return;
    }
    currentLayer.setAttribute('data-preview-fitmode', state.fitmodeoverride || '');
    currentLayer.setAttribute('data-preview-anchor', state.positionanchor || 'center');
    currentLayer.setAttribute('data-preview-custom-width', String(state.customwidthpercent ?? 100));
    currentLayer.setAttribute('data-preview-custom-height', String(state.customheightpercent ?? 100));
    currentLayer.setAttribute('data-preview-keep-aspect', state.customsizekeepaspect ? '1' : '0');
    currentLayer.setAttribute('data-preview-dynamic-image', state.dynamicimagesizeenabled ? '1' : '0');
    currentLayer.setAttribute('data-preview-image-opacity', String(state.imageopacity ?? 100));
    currentLayer.setAttribute('data-preview-crop-enabled', state.imagecropenabled ? '1' : '0');
    currentLayer.setAttribute('data-preview-crop-left', String(state.imagecropleftpercent ?? 0));
    currentLayer.setAttribute('data-preview-crop-top', String(state.imagecroptoppercent ?? 0));
    currentLayer.setAttribute('data-preview-crop-width', String(state.imagecropwidthpercent ?? 100));
    currentLayer.setAttribute('data-preview-crop-height', String(state.imagecropheightpercent ?? 100));
    localCourseBannerBuilderUpdateCropSelectionFrame(currentLayer, localCourseBannerBuilderNormaliseCropState(state));
    currentLayer.setAttribute('data-preview-offset-top', String(state.offsettoppercent ?? 0));
    currentLayer.setAttribute('data-preview-offset-right', String(state.offsetrightpercent ?? 0));
    currentLayer.setAttribute('data-preview-offset-bottom', String(state.offsetbottompercent ?? 0));
    currentLayer.setAttribute('data-preview-offset-left', String(state.offsetleftpercent ?? 0));
    currentLayer.setAttribute('data-preview-natural-width', String(state.imagewidth ?? 0));
    currentLayer.setAttribute('data-preview-natural-height', String(state.imageheight ?? 0));
    var currentImage = currentLayer.querySelector('[data-preview-image-tag=\"1\"]');
    var currentUrl = state.url || currentLayer.getAttribute('data-preview-current-url') ||
        (currentImage ? (currentImage.getAttribute('src') || '') : '');
    if (currentUrl) {
        currentLayer.setAttribute('data-preview-current-url', currentUrl);
    }
}

function localCourseBannerBuilderApplyLayerFormPreviewState(form, state) {
    if (!form || !state) {
        return;
    }
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    var keepAspectInput = form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    var dynamicInput = form.querySelector('#id_dynamicimagesizeenabled');
    var imageOpacityInput = form.querySelector('#id_imageopacity');
    var cropEnabledInput = form.querySelector('#id_imagecropenabled');
    var cropLeftInput = form.querySelector('#id_imagecropleftpercent');
    var cropTopInput = form.querySelector('#id_imagecroptoppercent');
    var cropWidthInput = form.querySelector('#id_imagecropwidthpercent');
    var cropHeightInput = form.querySelector('#id_imagecropheightpercent');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetRightInput = form.querySelector('#id_offsetrightpercent');
    var offsetBottomInput = form.querySelector('#id_offsetbottompercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    var currentLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    var image = currentLayer ? currentLayer.querySelector('[data-preview-image-tag=\"1\"]') : null;

    if (fitOverride) {
        fitOverride.value = state.fitmodeoverride || '';
    }
    if (anchorInput) {
        anchorInput.value = state.positionanchor || 'center';
    }
    if (widthInput) {
        widthInput.value = String(state.customwidthpercent ?? 100);
    }
    if (heightInput) {
        heightInput.value = String(state.customheightpercent ?? 100);
    }
    if (keepAspectInput) {
        keepAspectInput.checked = !!state.customsizekeepaspect;
    }
    if (dynamicInput) {
        dynamicInput.checked = !!state.dynamicimagesizeenabled;
    }
    if (imageOpacityInput) {
        imageOpacityInput.value = String(state.imageopacity ?? 100);
        localCourseBannerBuilderSetOpacityPanelValue(
            form.querySelector('[data-preview-opacity-panel=\"modal\"]'),
            state.imageopacity ?? 100
        );
    }
    if (cropEnabledInput) {
        cropEnabledInput.value = state.imagecropenabled ? '1' : '0';
    }
    if (cropLeftInput) {
        cropLeftInput.value = String(state.imagecropleftpercent ?? 0);
    }
    if (cropTopInput) {
        cropTopInput.value = String(state.imagecroptoppercent ?? 0);
    }
    if (cropWidthInput) {
        cropWidthInput.value = String(state.imagecropwidthpercent ?? 100);
    }
    if (cropHeightInput) {
        cropHeightInput.value = String(state.imagecropheightpercent ?? 100);
    }
    if (offsetTopInput) {
        offsetTopInput.value = String(state.offsettoppercent ?? 0);
    }
    if (offsetRightInput) {
        offsetRightInput.value = String(state.offsetrightpercent ?? 0);
    }
    if (offsetBottomInput) {
        offsetBottomInput.value = String(state.offsetbottompercent ?? 0);
    }
    if (offsetLeftInput) {
        offsetLeftInput.value = String(state.offsetleftpercent ?? 0);
    }
    if (currentLayer) {
        currentLayer.setAttribute('data-preview-fitmode', state.fitmodeoverride || '');
        currentLayer.setAttribute('data-preview-anchor', state.positionanchor || 'center');
        currentLayer.setAttribute('data-preview-custom-width', String(state.customwidthpercent ?? 100));
        currentLayer.setAttribute('data-preview-custom-height', String(state.customheightpercent ?? 100));
        currentLayer.setAttribute('data-preview-keep-aspect', state.customsizekeepaspect ? '1' : '0');
        currentLayer.setAttribute('data-preview-dynamic-image', state.dynamicimagesizeenabled ? '1' : '0');
        currentLayer.setAttribute('data-preview-image-opacity', String(state.imageopacity ?? 100));
        currentLayer.setAttribute('data-preview-crop-enabled', state.imagecropenabled ? '1' : '0');
        currentLayer.setAttribute('data-preview-crop-left', String(state.imagecropleftpercent ?? 0));
        currentLayer.setAttribute('data-preview-crop-top', String(state.imagecroptoppercent ?? 0));
        currentLayer.setAttribute('data-preview-crop-width', String(state.imagecropwidthpercent ?? 100));
        currentLayer.setAttribute('data-preview-crop-height', String(state.imagecropheightpercent ?? 100));
        localCourseBannerBuilderUpdateCropSelectionFrame(
            currentLayer,
            localCourseBannerBuilderNormaliseCropState(state)
        );
        currentLayer.setAttribute('data-preview-offset-top', String(state.offsettoppercent ?? 0));
        currentLayer.setAttribute('data-preview-offset-right', String(state.offsetrightpercent ?? 0));
        currentLayer.setAttribute('data-preview-offset-bottom', String(state.offsetbottompercent ?? 0));
        currentLayer.setAttribute('data-preview-offset-left', String(state.offsetleftpercent ?? 0));
        currentLayer.setAttribute('data-preview-natural-width', String(state.imagewidth ?? 0));
        currentLayer.setAttribute('data-preview-natural-height', String(state.imageheight ?? 0));
        var currentUrl = state.url || currentLayer.getAttribute('data-preview-current-url') ||
            (image ? (image.getAttribute('src') || '') : '');
        if (currentUrl) {
            currentLayer.setAttribute('data-preview-current-url', currentUrl);
        }
        if (typeof state.sortorder !== 'undefined') {
            currentLayer.setAttribute('data-preview-sortorder', String(state.sortorder));
        }
        if (typeof state.zindex !== 'undefined') {
            currentLayer.setAttribute('data-preview-zindex', String(state.zindex));
        }
    }
    if (image && state.url) {
        image.setAttribute('src', state.url);
        image.hidden = false;
        if (image.complete && image.naturalWidth && image.naturalHeight) {
            state.imagewidth = image.naturalWidth;
            state.imageheight = image.naturalHeight;
            if (currentLayer) {
                currentLayer.setAttribute('data-preview-natural-width', String(state.imagewidth));
                currentLayer.setAttribute('data-preview-natural-height', String(state.imageheight));
            }
        }
    }

    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    form.dataset.previewUserChanged = '0';
    localCourseBannerBuilderSyncLayerBannerPreview(form);
}

function localCourseBannerBuilderGetDraftFileInfo(file, form) {
    var url = file && file.url ? String(file.url) : '';
    var itemIdInput = form ? form.querySelector('#id_bannerimage_filemanager') : null;
    var info = {
        itemid: itemIdInput ? (itemIdInput.value || '') : '',
        filepath: '/',
        filename: ''
    };
    var match = url.match(/\\/draftfile\\.php\\/[^\\/]+\\/user\\/draft\\/([^\\/]+)\\/(.*)$/);
    if (match) {
        info.itemid = info.itemid || decodeURIComponent(match[1]);
        var path = (match[2] || '').split('?')[0].split('#')[0];
        var parts = path.split('/').filter(function(part) {
            return part !== '';
        });
        if (parts.length) {
            info.filename = decodeURIComponent(parts.pop());
            info.filepath = parts.length ? ('/' + parts.map(function(part) {
                return decodeURIComponent(part);
            }).join('/') + '/') : '/';
        }
    }
    if (!info.filename && file && file.name) {
        info.filename = file.name;
    }
    return info;
}

function localCourseBannerBuilderDeleteDraftFileFromServer(file, form) {
    var info = localCourseBannerBuilderGetDraftFileInfo(file, form);
    if (!info.itemid || !info.filename || !(window.M && M.cfg && M.cfg.wwwroot)) {
        return Promise.resolve(false);
    }
    var postDraftAction = function(action, body) {
        return fetch(M.cfg.wwwroot + '/repository/draftfiles_ajax.php?action=' + action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: body.toString()
        }).then(function(response) {
            if (!response.ok) {
                return false;
            }
            return response.text().then(function(text) {
                if (!text) {
                    return true;
                }
                try {
                    return JSON.parse(text) !== false;
                } catch (error) {
                    return true;
                }
            });
        }).catch(function() {
            return false;
        });
    };
    var body = new URLSearchParams();
    body.set('sesskey', (M.cfg && M.cfg.sesskey) ? M.cfg.sesskey : '');
    body.set('itemid', info.itemid);
    body.set('filepath', info.filepath || '/');
    body.set('filename', info.filename);
    return postDraftAction('delete', body).then(function(deleted) {
        if (deleted) {
            return true;
        }
        var selectedBody = new URLSearchParams();
        selectedBody.set('sesskey', (M.cfg && M.cfg.sesskey) ? M.cfg.sesskey : '');
        selectedBody.set('itemid', info.itemid);
        selectedBody.set('selected', JSON.stringify([{
            filepath: info.filepath || '/',
            filename: info.filename
        }]));
        return postDraftAction('deleteselected', selectedBody);
    });
}

function localCourseBannerBuilderScheduleDraftPreviewRefresh(form, delays) {
    if (!form) {
        return;
    }
    (delays || [220, 700, 1300]).forEach(function(delay) {
        window.setTimeout(function() {
            localCourseBannerBuilderMoveLayerPreviewToTop(form);
            localCourseBannerBuilderSyncLayerInputModes(form);
            localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
            localCourseBannerBuilderSyncLayerBannerPreview(form);
            localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        }, delay);
    });
}

function localCourseBannerBuilderScheduleAllOpenDraftPreviewRefresh() {
    Array.prototype.slice.call(document.querySelectorAll(
        '#local-course-banner-builder-add-layer-modal form.mform,' +
        '#local-course-banner-builder-edit-image-layer-modal form.mform,' +
        '#local-course-banner-builder-edit-border-layer-modal form.mform'
    )).forEach(function(form) {
        localCourseBannerBuilderScheduleDraftPreviewRefresh(form);
    });
}

function localCourseBannerBuilderSuppressDeletedDraftPreviewUrl(form, url) {
    if (!form || !url) {
        return;
    }
    form.dataset.deletedDraftPreviewUrl = localCourseBannerBuilderNormaliseDraftPreviewUrl(url);
    form.dataset.deletedDraftPreviewUntil = String(Date.now() + 2200);
}

function localCourseBannerBuilderIsDraftPreviewUrlSuppressed(form, url) {
    if (!form || !url || !form.dataset.deletedDraftPreviewUrl) {
        return false;
    }
    var until = parseInt(form.dataset.deletedDraftPreviewUntil || '0', 10) || 0;
    if (until && Date.now() > until) {
        delete form.dataset.deletedDraftPreviewUrl;
        delete form.dataset.deletedDraftPreviewUntil;
        return false;
    }
    return localCourseBannerBuilderNormaliseDraftPreviewUrl(url) === form.dataset.deletedDraftPreviewUrl;
}

function localCourseBannerBuilderMarkDraftFileItemDeleted(form, item) {
    if (!form || !item) {
        return;
    }
    var settings = localCourseBannerBuilderGetDraftPreviewSettings(form);
    var link = item.matches && item.matches('a[href*=\"/draftfile.php/\"], a[href*=\"/pluginfile.php/\"]') ?
        item : item.querySelector('a[href*=\"/draftfile.php/\"], a[href*=\"/pluginfile.php/\"]');
    var image = item.querySelector('img[src*=\"/draftfile.php/\"], img[src*=\"/pluginfile.php/\"]');
    var nameNode = item.querySelector('.fp-filename, .fp-pathbar, .fp-thumbnail .fp-filename');
    var url = link && link.getAttribute('href') ? link.getAttribute('href') :
        (image ? (image.getAttribute('src') || '') : '');
    url = localCourseBannerBuilderNormaliseDraftPreviewUrl(url);
    var fallbackIndex = Array.prototype.indexOf.call(
        form.querySelectorAll('#fitem_id_bannerimage_filemanager .fp-file:not(.fp-folder)'),
        item
    );
    var stableIndex = localCourseBannerBuilderGetStableDraftPreviewIndex(form, url, fallbackIndex);
    item.dataset.previewDraftIndex = stableIndex;
    item.dataset.previewDraftIdentity = localCourseBannerBuilderGetDraftPreviewFileIdentity(url, fallbackIndex);
    settings[stableIndex] = settings[stableIndex] || {};
    settings[stableIndex].deleted = true;
    settings[stableIndex].deletedurl = url;
    settings[stableIndex].deletedfilename = nameNode ? nameNode.textContent.trim() : '';
    localCourseBannerBuilderSuppressDeletedDraftPreviewUrl(form, url);
    item.dataset.previewDeleted = '1';
    item.hidden = true;
    item.setAttribute('aria-hidden', 'true');
    if (String(form.dataset.activeDraftIndex || '') === String(stableIndex)) {
        form.dataset.activeDraftIndex = '';
    }
    localCourseBannerBuilderSetDraftPreviewSettings(form, settings);
}

function localCourseBannerBuilderMarkDraftFilemanagerSelectedFilesDeleted(form) {
    if (!form) {
        return;
    }
    var filemanager = form.querySelector('#fitem_id_bannerimage_filemanager');
    if (!filemanager) {
        return;
    }
    var selected = Array.prototype.slice.call(filemanager.querySelectorAll(
        '.fp-file.fp-file-selected:not(.fp-folder),' +
        '.fp-file.selected:not(.fp-folder),' +
        '.fp-file.active:not(.fp-folder),' +
        '.fp-file[aria-selected=\"true\"]:not(.fp-folder)'
    ));
    if (!selected.length && form.dataset.activeDraftIndex) {
        selected = Array.prototype.slice.call(filemanager.querySelectorAll('.fp-file:not(.fp-folder)')).filter(function(item) {
            return String(item.dataset.previewDraftIndex || '') === String(form.dataset.activeDraftIndex);
        });
    }
    selected.forEach(function(item) {
        localCourseBannerBuilderMarkDraftFileItemDeleted(form, item);
    });
    if (!localCourseBannerBuilderGetDraftPreviewFiles(form).length) {
        localCourseBannerBuilderClearDraftPreviewState(form, true);
        localCourseBannerBuilderPruneDraftFilemanagerEmptyState(form);
        localCourseBannerBuilderSyncLayerInputModes(form);
        localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
        localCourseBannerBuilderSyncModalPreviewActionButtons(form);
    }
}

function localCourseBannerBuilderMarkDraftFilemanagerDeletionClick(target) {
    if (!target || !target.closest) {
        return;
    }
    var deleteControl = target.closest(
        '.fp-file-delete,' +
        '.fp-dlg-butdelete,' +
        '.fp-dlg-butconfirm,' +
        '.fp-delete,' +
        '[id^=\"fm-delete\"],' +
        '[data-action=\"delete\"]'
    );
    if (deleteControl) {
        Array.prototype.slice.call(document.querySelectorAll(
            '#local-course-banner-builder-add-layer-modal form.mform,' +
            '#local-course-banner-builder-edit-image-layer-modal form.mform'
        )).forEach(function(form) {
            localCourseBannerBuilderMarkDraftFilemanagerSelectedFilesDeleted(form);
        });
        localCourseBannerBuilderScheduleAllOpenDraftPreviewRefresh();
    }
}

document.addEventListener('click', function(e) {
    localCourseBannerBuilderMarkDraftFilemanagerDeletionClick(e.target);
}, true);

/*
 * Moodle's filemanager updates itself asynchronously after its own file dialog
 * delete action. Keep the layer preview in lock-step with that widget.
 */
document.addEventListener('change', function(e) {
    if (e.target && e.target.closest && e.target.closest('#fitem_id_bannerimage_filemanager')) {
        localCourseBannerBuilderScheduleAllOpenDraftPreviewRefresh();
    }
}, true);

function localCourseBannerBuilderClearDraftPreviewState(form, keepSettings) {
    if (!form) {
        return;
    }
    if (!keepSettings) {
        localCourseBannerBuilderSetDraftPreviewSettings(form, {});
    }
    form.dataset.activeDraftIndex = '';
    form.dataset.draftPreviewSignature = '';
    form.dataset.draftPreviewRenderedActive = '';
    form.dataset.previewUserChanged = '0';
    localCourseBannerBuilderForgetCurrentPreviewImageUrl(form);
    localCourseBannerBuilderClearDraftPreviewFilesCache(form);
    var currentLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    var currentImage = currentLayer ? currentLayer.querySelector('[data-preview-image-tag=\"1\"]') : null;
    if (currentLayer) {
        currentLayer.hidden = true;
        currentLayer.style.display = 'none';
        localCourseBannerBuilderRemoveCropEditor(currentLayer);
        currentLayer.removeAttribute('data-preview-current-url');
        currentLayer.removeAttribute('data-preview-current-layer');
        currentLayer.removeAttribute('data-preview-draft-selection-overlay');
        currentLayer.removeAttribute('data-preview-draft-layer');
        currentLayer.removeAttribute('data-draft-index');
        currentLayer.classList.remove('local-course-banner-builder-preview-draft-layer');
    }
    if (currentImage) {
        currentImage.hidden = true;
        currentImage.removeAttribute('src');
        currentImage.style.display = 'none';
    }
    var host = form.querySelector('[data-preview-draft-layer-host=\"1\"]');
    if (host) {
        host.innerHTML = '';
    }
}

function localCourseBannerBuilderPruneDraftFilemanagerEmptyState(form) {
    var filemanager = form ? form.querySelector('#fitem_id_bannerimage_filemanager') : null;
    if (!filemanager) {
        return;
    }
    var visibleFiles = filemanager.querySelectorAll('.fp-file:not(.fp-folder):not([hidden]):not([aria-hidden=\"true\"])');
    var container = filemanager.querySelector('.filemanager, .filemanager-container') || filemanager;
    if (!container || !container.classList) {
        return;
    }
    if (!visibleFiles.length) {
        container.classList.add('fm-noitems');
        container.classList.add('fm-nofiles');
        container.classList.remove('fm-maxfiles');
    } else {
        container.classList.remove('fm-noitems');
        container.classList.remove('fm-nofiles');
    }
}

function localCourseBannerBuilderReindexDraftPreviewSettings(form, oldActiveIndex) {
    if (!form) {
        return [];
    }
    var visibleItems = Array.prototype.slice.call(
        form.querySelectorAll('#fitem_id_bannerimage_filemanager .fp-file:not(.fp-folder)')
    ).filter(function(item) {
        return item.dataset.previewDeleted !== '1' && !item.hidden && item.getAttribute('aria-hidden') !== 'true';
    });
    var files = localCourseBannerBuilderGetDraftPreviewFiles(form);
    if (!files.some(function(file) {
            return String(file.index) === String(oldActiveIndex || '');
        })) {
        form.dataset.activeDraftIndex = files.length ? String(files[0].index) : '';
    } else {
        form.dataset.activeDraftIndex = String(oldActiveIndex || '');
    }
    return visibleItems;
}

function localCourseBannerBuilderGetDraftPreviewFiles(form) {
    var filemanager = form ? form.querySelector('#fitem_id_bannerimage_filemanager') : null;
    if (!filemanager) {
        return [];
    }
    if (localCourseBannerBuilderIsDraftFilemanagerBusy(form)) {
        return [];
    }
    var domItems = Array.prototype.slice.call(filemanager.querySelectorAll('.fp-file:not(.fp-folder)'));
    if (!domItems.length) {
        if (!localCourseBannerBuilderDraftFilemanagerHasVisibleContent(form)) {
            localCourseBannerBuilderSetDraftPreviewFilesCache(form, []);
            return [];
        }
        localCourseBannerBuilderRefreshDraftPreviewFileList(form);
        return localCourseBannerBuilderGetDraftPreviewFilesFromCache(form);
    }
    localCourseBannerBuilderClearDraftPreviewFilesCache(form);
    return domItems.map(function(item, index) {
        var link = item.matches && item.matches('a[href*=\"/draftfile.php/\"], a[href*=\"/pluginfile.php/\"]') ?
            item : item.querySelector('a[href*=\"/draftfile.php/\"], a[href*=\"/pluginfile.php/\"]');
        var image = item.querySelector('img[src*=\"/draftfile.php/\"], img[src*=\"/pluginfile.php/\"]');
        var nameNode = item.querySelector('.fp-filename, .fp-pathbar, .fp-thumbnail .fp-filename');
        var url = link && link.getAttribute('href') ? link.getAttribute('href') : (image ? (image.getAttribute('src') || '') : '');
        url = localCourseBannerBuilderNormaliseDraftPreviewUrl(url);
        var stableIndex = localCourseBannerBuilderGetStableDraftPreviewIndex(form, url, index);
        item.dataset.previewDraftIndex = stableIndex;
        item.dataset.previewDraftIdentity = localCourseBannerBuilderGetDraftPreviewFileIdentity(url, index);
        var deleted = item.dataset.previewDeleted === '1' ||
            localCourseBannerBuilderIsDraftPreviewUrlSuppressed(form, url);
        if (deleted && item.dataset.previewDeleted !== '1') {
            item.dataset.previewDeleted = '1';
        }
        if (item.hidden !== deleted) {
            item.hidden = deleted;
        }
        if (item.getAttribute('aria-hidden') !== (deleted ? 'true' : 'false')) {
            item.setAttribute('aria-hidden', deleted ? 'true' : 'false');
        }
        return {
            index: parseInt(stableIndex, 10),
            url: url,
            name: nameNode ? nameNode.textContent.trim() : ('Layer ' + (index + 1)),
            item: item,
            deleted: deleted
        };
    }).filter(function(file) {
        return !file.deleted && !!file.url && file.url.indexOf('/theme/image.php') === -1;
    });
}

function localCourseBannerBuilderGetDraftPreviewSettings(form) {
    var input = form ? form.querySelector('#id_multilayerdraftsettings') : null;
    if (!input || !input.value) {
        return {};
    }
    try {
        var parsed = JSON.parse(input.value);
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
        return {};
    }
}

function localCourseBannerBuilderSetDraftPreviewSettings(form, settings) {
    var input = form ? form.querySelector('#id_multilayerdraftsettings') : null;
    if (!input) {
        return;
    }
    input.value = JSON.stringify(settings || {});
}

function localCourseBannerBuilderGetDraftPreviewSignature(files) {
    return (files || []).map(function(file) {
        return String(file.index) + ':' + String(file.url || '');
    }).join('|');
}

function localCourseBannerBuilderEnsureDraftPreviewSelection(form, files) {
    if (!form || !files.length) {
        return;
    }
    if (typeof form.dataset.activeDraftIndex === 'undefined' || form.dataset.activeDraftIndex === '') {
        form.dataset.activeDraftIndex = String(files[0].index);
    }
    if (!files.some(function(file) { return String(file.index) === String(form.dataset.activeDraftIndex); })) {
        form.dataset.activeDraftIndex = String(files[0].index);
    }
}

function localCourseBannerBuilderMirrorDraftSelectionVisual(form, layer) {
    if (!form || !layer || !layer.hasAttribute('data-preview-draft-selection-overlay')) {
        return;
    }
    var draftIndex = layer.getAttribute('data-draft-index');
    if (draftIndex === null || draftIndex === '') {
        return;
    }
    var visualLayer = form.querySelector(
        '[data-preview-draft-visual-layer=\"1\"][data-draft-index=\"' + draftIndex + '\"]'
    );
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    var visualImage = visualLayer ? visualLayer.querySelector('[data-preview-image-tag=\"1\"]') : null;
    if (!visualLayer || !visualImage || !image) {
        return;
    }
    [
        'data-preview-fitmode',
        'data-preview-anchor',
        'data-preview-custom-width',
        'data-preview-custom-height',
        'data-preview-keep-aspect',
        'data-preview-dynamic-image',
        'data-preview-image-opacity',
        'data-preview-crop-enabled',
        'data-preview-crop-left',
        'data-preview-crop-top',
        'data-preview-crop-width',
        'data-preview-crop-height',
        'data-preview-offset-top',
        'data-preview-offset-right',
        'data-preview-offset-bottom',
        'data-preview-offset-left',
        'data-preview-natural-width',
        'data-preview-natural-height',
        'data-preview-current-url',
        'data-preview-sortorder',
        'data-preview-zindex'
    ].forEach(function(attribute) {
        if (layer.hasAttribute(attribute)) {
            visualLayer.setAttribute(attribute, layer.getAttribute(attribute));
        }
    });
    visualLayer.style.cssText = layer.style.cssText;
    visualLayer.style.visibility = '';
    localCourseBannerBuilderUpdateCropSelectionFrame(visualLayer, localCourseBannerBuilderGetPreviewCropState(layer));
    visualLayer.style.zIndex = String(parseInt(layer.getAttribute('data-preview-zindex') || '1', 10) || 1);
    if (visualImage.getAttribute('src') !== (image.getAttribute('src') || '')) {
        visualImage.setAttribute('src', image.getAttribute('src') || '');
    }
    visualImage.hidden = image.hidden;
    visualImage.style.cssText = image.style.cssText;
    visualLayer.hidden = layer.hidden;
}

function localCourseBannerBuilderGetDraftSelectionVisualLayer(form, layer) {
    if (!form || !layer || !layer.hasAttribute('data-preview-draft-selection-overlay')) {
        return null;
    }
    var draftIndex = layer.getAttribute('data-draft-index');
    if (draftIndex === null || draftIndex === '') {
        return null;
    }
    return form.querySelector('[data-preview-draft-visual-layer=\"1\"][data-draft-index=\"' + draftIndex + '\"]');
}

function localCourseBannerBuilderApplyDraftVisualLayerState(form, previewRoot, layer, file, layerState) {
    if (!form || !previewRoot || !layer || !file) {
        return;
    }
    layer.className = 'local-course-banner-builder-preview-image-layer local-course-banner-builder-preview-image-layer--context local-course-banner-builder-preview-draft-layer';
    layer.setAttribute('data-preview-draft-layer', '1');
    layer.setAttribute('data-preview-draft-visual-layer', '1');
    layer.setAttribute('data-draft-index', String(file.index));
    layer.setAttribute('data-preview-fitmode', layerState.fitmodeoverride || '');
    layer.setAttribute('data-preview-anchor', layerState.positionanchor || 'center');
    layer.setAttribute('data-preview-custom-width', String(layerState.customwidthpercent ?? 100));
    layer.setAttribute('data-preview-custom-height', String(layerState.customheightpercent ?? 100));
    layer.setAttribute('data-preview-keep-aspect', layerState.customsizekeepaspect ? '1' : '0');
    layer.setAttribute('data-preview-dynamic-image', layerState.dynamicimagesizeenabled ? '1' : '0');
    layer.setAttribute('data-preview-image-opacity', String(layerState.imageopacity ?? 100));
    layer.setAttribute('data-preview-crop-enabled', layerState.imagecropenabled ? '1' : '0');
    layer.setAttribute('data-preview-crop-left', String(layerState.imagecropleftpercent ?? 0));
    layer.setAttribute('data-preview-crop-top', String(layerState.imagecroptoppercent ?? 0));
    layer.setAttribute('data-preview-crop-width', String(layerState.imagecropwidthpercent ?? 100));
    layer.setAttribute('data-preview-crop-height', String(layerState.imagecropheightpercent ?? 100));
    layer.setAttribute('data-preview-offset-top', String(layerState.offsettoppercent ?? 0));
    layer.setAttribute('data-preview-offset-right', String(layerState.offsetrightpercent ?? 0));
    layer.setAttribute('data-preview-offset-bottom', String(layerState.offsetbottompercent ?? 0));
    layer.setAttribute('data-preview-offset-left', String(layerState.offsetleftpercent ?? 0));
    layer.setAttribute('data-preview-natural-width', String(layerState.imagewidth ?? 0));
    layer.setAttribute('data-preview-natural-height', String(layerState.imageheight ?? 0));
    layer.setAttribute('data-preview-current-url', file.url);
    layer.setAttribute('data-preview-sortorder', String(layerState.sortorder ?? file.index));
    layer.setAttribute('data-preview-zindex', String(layerState.zindex ?? localCourseBannerBuilderGetDraftPreviewZIndex(file.index)));

    var draftImage = layer.querySelector('[data-preview-image-tag=\"1\"]');
    if (!draftImage) {
        draftImage = document.createElement('img');
        draftImage.alt = '';
        draftImage.className = 'local-course-banner-builder-preview-image';
        draftImage.setAttribute('data-preview-image-tag', '1');
        draftImage.setAttribute('draggable', 'false');
        layer.appendChild(draftImage);
    }
    if (draftImage.getAttribute('src') !== file.url) {
        draftImage.setAttribute('src', file.url);
    }
    draftImage.hidden = false;
    if (!draftImage.dataset.previewLoadBound) {
        draftImage.addEventListener('load', function() {
            var latestSettings = localCourseBannerBuilderGetDraftPreviewSettings(form);
            var latestState = latestSettings[file.index] || {};
            latestState.imagewidth = draftImage.naturalWidth || 0;
            latestState.imageheight = draftImage.naturalHeight || 0;
            latestState.url = file.url;
            latestSettings[file.index] = latestState;
            layer.setAttribute('data-preview-natural-width', String(latestState.imagewidth || 0));
            layer.setAttribute('data-preview-natural-height', String(latestState.imageheight || 0));
            localCourseBannerBuilderSetDraftPreviewSettings(form, latestSettings);
            localCourseBannerBuilderSyncStandalonePreviewLayer(previewRoot, layer);
            localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        });
        draftImage.dataset.previewLoadBound = '1';
    }
    if (draftImage.complete && draftImage.naturalWidth && draftImage.naturalHeight) {
        layerState.imagewidth = draftImage.naturalWidth;
        layerState.imageheight = draftImage.naturalHeight;
        layer.setAttribute('data-preview-natural-width', String(layerState.imagewidth || 0));
        layer.setAttribute('data-preview-natural-height', String(layerState.imageheight || 0));
    }
    localCourseBannerBuilderSyncStandalonePreviewLayer(previewRoot, layer);
    localCourseBannerBuilderUpdatePreviewAspectLockButton(layer);
}

function localCourseBannerBuilderRenderDraftUploadPreview(form) {
    if (!form) {
        return;
    }
    if (localCourseBannerBuilderIsDraftFilemanagerBusy(form)) {
        return;
    }
    if (form.dataset.renderingDraftPreview === '1') {
        return;
    }
    form.dataset.renderingDraftPreview = '1';
    try {
    var files = localCourseBannerBuilderGetDraftPreviewFiles(form);
    localCourseBannerBuilderPruneDraftFilemanagerEmptyState(form);
    var previewRoot = form.querySelector('[data-layer-banner-preview=\"1\"]');
    var frame = previewRoot ? previewRoot.querySelector('[data-banner-preview-frame=\"1\"]') : null;
    var currentLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    if (!previewRoot || !frame || !currentLayer) {
        return;
    }
    var host = previewRoot.querySelector('[data-preview-draft-layer-host=\"1\"]');
    if (!host) {
        host = document.createElement('div');
        host.setAttribute('data-preview-draft-layer-host', '1');
        frame.appendChild(host);
    }
    var filesByIndex = {};
    files.forEach(function(file) {
        filesByIndex[String(file.index)] = true;
    });
    Array.prototype.slice.call(host.querySelectorAll('[data-preview-draft-visual-layer=\"1\"]')).forEach(function(layer) {
        if (!filesByIndex[String(layer.getAttribute('data-draft-index') || '')]) {
            layer.remove();
        }
    });

    if (!files.length) {
        currentLayer.hidden = true;
        currentLayer.style.display = 'none';
        localCourseBannerBuilderRemoveCropEditor(currentLayer);
        currentLayer.removeAttribute('data-preview-current-url');
        currentLayer.removeAttribute('data-preview-draft-layer');
        currentLayer.removeAttribute('data-preview-draft-selection-overlay');
        currentLayer.removeAttribute('data-draft-index');
        currentLayer.classList.remove('local-course-banner-builder-preview-draft-layer');
        var currentImage = currentLayer.querySelector('[data-preview-image-tag=\"1\"]');
        if (currentImage) {
            currentImage.hidden = true;
            currentImage.removeAttribute('src');
            currentImage.style.display = 'none';
        }
        form.dataset.activeDraftIndex = '';
        form.dataset.draftPreviewSignature = '';
        form.dataset.draftPreviewRenderedActive = '';
        localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        return;
    }

    var settings = localCourseBannerBuilderGetDraftPreviewSettings(form);
    localCourseBannerBuilderEnsureDraftPreviewSelection(form, files);
    var activeIndex = String(form.dataset.activeDraftIndex);
    var activeFile = files.find(function(file) {
        return String(file.index) === activeIndex;
    }) || files[0];
    activeIndex = String(activeFile.index);
    form.dataset.activeDraftIndex = activeIndex;

    files.forEach(function(file) {
        if (!settings[file.index]) {
            settings[file.index] = {};
            settings[file.index].fitmodeoverride = 'cover';
            settings[file.index].positionanchor = 'center';
            settings[file.index].customwidthpercent = 100;
            settings[file.index].customheightpercent = 100;
            settings[file.index].customsizekeepaspect = true;
            settings[file.index].imageopacity = 100;
            settings[file.index].imagecropenabled = false;
            settings[file.index].imagecropleftpercent = 0;
            settings[file.index].imagecroptoppercent = 0;
            settings[file.index].imagecropwidthpercent = 100;
            settings[file.index].imagecropheightpercent = 100;
            settings[file.index].offsettoppercent = 0;
            settings[file.index].offsetrightpercent = 0;
            settings[file.index].offsetbottompercent = 0;
            settings[file.index].offsetleftpercent = 0;
            settings[file.index].sortorder = file.index;
            settings[file.index].zindex = localCourseBannerBuilderGetDraftPreviewZIndex(file.index);
            settings[file.index].url = file.url;
        }
        settings[file.index].deleted = false;
        settings[file.index].url = file.url;
        if (typeof settings[file.index].sortorder === 'undefined') {
            settings[file.index].sortorder = file.index;
        }
        if (typeof settings[file.index].zindex === 'undefined') {
            settings[file.index].zindex = localCourseBannerBuilderGetDraftPreviewZIndex(file.index);
        }
        if (!settings[file.index].imagewidth) {
            settings[file.index].imagewidth = file.width || 0;
        }
        if (!settings[file.index].imageheight) {
            settings[file.index].imageheight = file.height || 0;
        }
    });

    files.forEach(function(file) {
        var layerState = settings[file.index] || {};
        var layer = host.querySelector('[data-preview-draft-visual-layer=\"1\"][data-draft-index=\"' + file.index + '\"]');
        if (!layer) {
            layer = document.createElement('div');
            host.appendChild(layer);
        }
        localCourseBannerBuilderApplyDraftVisualLayerState(form, previewRoot, layer, file, layerState);
    });

    localCourseBannerBuilderSetDraftPreviewSettings(form, settings);
    form.dataset.draftPreviewSignature = localCourseBannerBuilderGetDraftPreviewSignature(files);
    form.dataset.draftPreviewRenderedActive = activeIndex;
    currentLayer.setAttribute('data-preview-draft-layer', '1');
    currentLayer.setAttribute('data-preview-draft-selection-overlay', '1');
    currentLayer.setAttribute('data-draft-index', activeIndex);
    currentLayer.setAttribute('data-preview-sortorder', String(settings[activeIndex].sortorder ?? activeIndex));
    currentLayer.setAttribute(
        'data-preview-zindex',
        String(settings[activeIndex].zindex ?? localCourseBannerBuilderGetDraftPreviewZIndex(activeIndex))
    );
    localCourseBannerBuilderApplyLayerFormPreviewState(form, settings[activeIndex]);
    currentLayer.classList.add('local-course-banner-builder-preview-draft-layer');
    currentLayer.hidden = false;
    localCourseBannerBuilderMirrorDraftSelectionVisual(form, currentLayer);
    if (form.dataset.draftPreviewSwitching === '1') {
        var currentImage = currentLayer.querySelector('[data-preview-image-tag=\"1\"]');
        var revealSelection = function() {
            currentLayer.style.visibility = '';
            delete form.dataset.draftPreviewSwitching;
        };
        if (currentImage && !currentImage.complete) {
            currentImage.addEventListener('load', revealSelection, {once: true});
            currentImage.addEventListener('error', revealSelection, {once: true});
            window.setTimeout(revealSelection, 700);
        } else {
            window.requestAnimationFrame(revealSelection);
        }
    }
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
    } finally {
        form.dataset.renderingDraftPreview = '0';
    }
}

function localCourseBannerBuilderSaveActiveDraftPreviewState(form) {
    if (!form || !form.dataset.activeDraftIndex) {
        return;
    }
    var settings = localCourseBannerBuilderGetDraftPreviewSettings(form);
    var index = String(form.dataset.activeDraftIndex);
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"][data-preview-draft-layer=\"1\"]');
    var state = currentLayer && String(currentLayer.getAttribute('data-draft-index') || '') === index ?
        localCourseBannerBuilderReadLayerPreviewStateFromLayer(currentLayer) :
        localCourseBannerBuilderReadLayerFormPreviewState(form);
    if (!state) {
        return;
    }
    settings[index] = state;
    localCourseBannerBuilderSetDraftPreviewSettings(form, settings);
}

function localCourseBannerBuilderSelectDraftPreviewLayer(form, index) {
    if (!form) {
        return;
    }
    if (String(form.dataset.activeDraftIndex || '') === String(index)) {
        return;
    }
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"][data-preview-draft-layer=\"1\"]');
    if (currentLayer) {
        currentLayer.style.visibility = 'hidden';
        form.dataset.draftPreviewSwitching = '1';
    }
    form.dataset.activeDraftIndex = String(index);
    localCourseBannerBuilderRenderDraftUploadPreview(form);
}

function localCourseBannerBuilderDeleteSelectedDraftPreviewLayer(button) {
    var form = localCourseBannerBuilderGetLayerScope(button);
    if (!form || !form.dataset.activeDraftIndex) {
        return;
    }
    var activeIndex = String(form.dataset.activeDraftIndex);
    var files = localCourseBannerBuilderGetDraftPreviewFiles(form);
    var activeFile = files.find(function(file) {
        return String(file.index) === activeIndex;
    });
    if (!activeFile) {
        return;
    }
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
    var settings = localCourseBannerBuilderGetDraftPreviewSettings(form);
    settings[activeIndex] = settings[activeIndex] || {};
    settings[activeIndex].deleted = true;
    settings[activeIndex].deletedurl = activeFile.url || '';
    settings[activeIndex].deletedfilename = localCourseBannerBuilderGetDraftFileInfo(activeFile, form).filename || activeFile.name || '';
    localCourseBannerBuilderSuppressDeletedDraftPreviewUrl(form, activeFile.url || '');
    if (activeFile.item) {
        activeFile.item.dataset.previewDeleted = '1';
        activeFile.item.hidden = true;
        activeFile.item.setAttribute('aria-hidden', 'true');
        activeFile.item.remove();
    }
    localCourseBannerBuilderSetDraftPreviewSettings(form, settings);
    var remaining = localCourseBannerBuilderGetDraftPreviewFiles(form);
    form.dataset.activeDraftIndex = remaining.length ? String(remaining[0].index) : '';
    form.dataset.previewUserChanged = '0';
    form.dataset.draftPreviewSignature = '';
    form.dataset.draftPreviewRenderedActive = '';
    var deletePromise = localCourseBannerBuilderDeleteDraftFileFromServer(activeFile, form);
    localCourseBannerBuilderScheduleDraftPreviewRefresh(form, [120, 450, 900, 1500]);
    if (!remaining.length) {
        var currentLayer = form.querySelector('[data-preview-current-image=\"1\"]');
        var currentImage = currentLayer ? currentLayer.querySelector('[data-preview-image-tag=\"1\"]') : null;
        if (currentLayer) {
            currentLayer.hidden = true;
            currentLayer.style.display = 'none';
            localCourseBannerBuilderRemoveCropEditor(currentLayer);
            currentLayer.removeAttribute('data-preview-current-url');
            currentLayer.removeAttribute('data-preview-current-layer');
            currentLayer.removeAttribute('data-preview-draft-layer');
            currentLayer.removeAttribute('data-preview-draft-selection-overlay');
            currentLayer.removeAttribute('data-draft-index');
            currentLayer.classList.remove('local-course-banner-builder-preview-draft-layer');
        }
        if (currentImage) {
            currentImage.hidden = true;
            currentImage.removeAttribute('src');
            currentImage.style.display = 'none';
        }
        var host = form.querySelector('[data-preview-draft-layer-host=\"1\"]');
        if (host) {
            host.innerHTML = '';
        }
        localCourseBannerBuilderClearDraftPreviewState(form, true);
        localCourseBannerBuilderPruneDraftFilemanagerEmptyState(form);
        localCourseBannerBuilderSyncLayerInputModes(form);
        localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
        localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        deletePromise.then(function(deleted) {
            if (deleted) {
                localCourseBannerBuilderPruneDraftFilemanagerEmptyState(form);
                localCourseBannerBuilderSyncLayerInputModes(form);
                localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
                localCourseBannerBuilderSyncModalPreviewActionButtons(form);
                localCourseBannerBuilderScheduleDraftPreviewRefresh(form, [150, 600]);
            }
        });
        return;
    }
    form.dataset.syncingDraftPreview = '1';
    localCourseBannerBuilderRenderDraftUploadPreview(form);
    form.dataset.syncingDraftPreview = '0';
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
    deletePromise.then(function(deleted) {
        if (!deleted) {
            return;
        }
        var currentActiveIndex = form.dataset.activeDraftIndex || '';
        localCourseBannerBuilderReindexDraftPreviewSettings(form, currentActiveIndex);
        form.dataset.draftPreviewSignature = '';
        form.dataset.draftPreviewRenderedActive = '';
        localCourseBannerBuilderRenderDraftUploadPreview(form);
        localCourseBannerBuilderScheduleDraftPreviewRefresh(form, [150, 600]);
    });
}

function localCourseBannerBuilderSyncDraftUploadPreview(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form || String(form.querySelector('#id_elementid') ? form.querySelector('#id_elementid').value || '0' : '0') !== '0') {
        return;
    }
    if (!form.querySelector('#id_multilayerdraftsettings')) {
        return;
    }
    if (localCourseBannerBuilderIsDraftFilemanagerBusy(form)) {
        if (form.dataset.draftPreviewBusyRetry !== '1') {
            form.dataset.draftPreviewBusyRetry = '1';
            window.setTimeout(function() {
                form.dataset.draftPreviewBusyRetry = '0';
                localCourseBannerBuilderSyncDraftUploadPreview(form);
            }, 450);
        }
        return;
    }
    if (form.dataset.renderingDraftPreview === '1') {
        return;
    }
    if (form.dataset.syncingDraftPreview === '1') {
        return;
    }
    if (form.dataset.previewInteractionStarting === '1') {
        return;
    }
    if (localCourseBannerBuilderPreviewInteraction && localCourseBannerBuilderPreviewInteraction.form === form) {
        return;
    }
    var files = localCourseBannerBuilderGetDraftPreviewFiles(form);
    localCourseBannerBuilderPruneDraftFilemanagerEmptyState(form);
    var signature = localCourseBannerBuilderGetDraftPreviewSignature(files);
    var activeIndex = typeof form.dataset.activeDraftIndex !== 'undefined' ? String(form.dataset.activeDraftIndex) : '';
    if (signature === (form.dataset.draftPreviewSignature || '') &&
            activeIndex === (form.dataset.draftPreviewRenderedActive || '') &&
            form.querySelector('[data-preview-draft-layer-host=\"1\"]')) {
        localCourseBannerBuilderSaveActiveDraftPreviewState(form);
        localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        return;
    }
    form.dataset.syncingDraftPreview = '1';
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
    localCourseBannerBuilderRenderDraftUploadPreview(form);
    form.dataset.syncingDraftPreview = '0';
}

function localCourseBannerBuilderSyncStandalonePreviewLayer(previewRoot, layer) {
    if (!previewRoot || !layer) {
        return;
    }
    var fitMode = layer.getAttribute('data-preview-fitmode') || (previewRoot.getAttribute('data-default-fitmode') || 'bannerfit');
    var anchor = layer.getAttribute('data-preview-anchor') || 'center';
    var customWidth = Math.max(
        0,
        Math.min(
            localCourseBannerBuilderCustomSizePercentMax,
            localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-width') || '100', 100)
        )
    );
    var customHeight = Math.max(
        0,
        Math.min(
            localCourseBannerBuilderCustomSizePercentMax,
            localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-height') || '100', 100)
        )
    );
    var keepAspect = layer.getAttribute('data-preview-keep-aspect') === '1';
    var dynamicImage = false;
    var imageOpacity = localCourseBannerBuilderClampPercent(
        layer.getAttribute('data-preview-image-opacity') || '100',
        100
    ) / 100;
    var naturalWidth = localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-width') || '0', 0);
    var naturalHeight = localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-height') || '0', 0);
    var offsets = {
        top: String(localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-top') || '0', 0)) + '%',
        right: String(localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-right') || '0', 0)) + '%',
        bottom: String(localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-bottom') || '0', 0)) + '%',
        left: String(localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-left') || '0', 0)) + '%'
    };
    var objectPosition = localCourseBannerBuilderGetPreviewObjectPosition(anchor);
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    if (!image) {
        return;
    }
    if ((naturalWidth <= 0 || naturalHeight <= 0) && image.complete && image.naturalWidth && image.naturalHeight) {
        naturalWidth = image.naturalWidth;
        naturalHeight = image.naturalHeight;
        layer.setAttribute('data-preview-natural-width', String(naturalWidth));
        layer.setAttribute('data-preview-natural-height', String(naturalHeight));
    }

    var layerStyles = [
        'position: absolute;',
        'display: flex;',
        'align-items: stretch;',
        'justify-content: stretch;',
        'overflow: hidden;',
        'z-index: ' + (parseInt(layer.getAttribute('data-preview-zindex') || '1', 10) || 1) + ';'
    ];
    var imageStyles = ['display: block;', 'width: 100%;', 'height: 100%;'];

    if (dynamicImage) {
        if (fitMode === 'custom') {
            var dynamicCustomBox = localCourseBannerBuilderGetCustomPreviewBox(customWidth, customHeight, naturalWidth, naturalHeight, keepAspect);
            localCourseBannerBuilderAppendPreviewBoxStyles(layerStyles, dynamicCustomBox);
            localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
            imageStyles.push('object-fit: ' + (keepAspect ? 'contain' : 'fill') + ';', 'object-position: ' + objectPosition + ';');
        } else {
            imageStyles = [localCourseBannerBuilderBuildDynamicPreviewImageStyle({
                anchor: anchor,
                fitMode: fitMode,
                customWidth: customWidth,
                customHeight: customHeight,
                keepAspect: keepAspect,
                naturalWidth: naturalWidth,
                naturalHeight: naturalHeight,
                previewAspect: localCourseBannerBuilderGetPreviewFrameAspect(previewRoot),
                offsets: offsets
            })];
            layerStyles = ['position: absolute;', 'inset: 0;', 'overflow: hidden;', 'z-index: ' + (parseInt(layer.getAttribute('data-preview-zindex') || '1', 10) || 1) + ';'];
        }
    } else if (fitMode === 'bannerfit') {
        layerStyles.push('inset: 0;');
        imageStyles.push('object-fit: fill;');
    } else if (fitMode === 'cover') {
        var containedBox = localCourseBannerBuilderGetContainedPreviewBox(
            naturalWidth,
            naturalHeight,
            localCourseBannerBuilderGetPreviewFrameAspect(previewRoot)
        );
        layerStyles.push('width: ' + containedBox.width + '%;', 'height: ' + containedBox.height + '%;');
        localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
        imageStyles.push('object-fit: fill;', 'object-position: ' + objectPosition + ';');
    } else if (fitMode === 'custom') {
        var customBox = localCourseBannerBuilderGetCustomPreviewBox(customWidth, customHeight, naturalWidth, naturalHeight, keepAspect);
        localCourseBannerBuilderAppendPreviewBoxStyles(layerStyles, customBox);
        localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
        imageStyles.push('object-fit: ' + (keepAspect ? 'contain' : 'fill') + ';', 'object-position: ' + objectPosition + ';');
    } else {
        var originalBox = localCourseBannerBuilderGetOriginalPreviewBox(
            naturalWidth,
            naturalHeight,
            localCourseBannerBuilderGetPreviewFrameAspect(previewRoot)
        );
        layerStyles.push('width: ' + originalBox.width + '%;', 'height: ' + originalBox.height + '%;');
        localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
        imageStyles.push('object-fit: fill;', 'object-position: ' + objectPosition + ';');
    }

    imageStyles.push('opacity: ' + imageOpacity.toFixed(3) + ';');
    localCourseBannerBuilderApplyCropToImageStyles(imageStyles, {
        imagecropenabled: layer.getAttribute('data-preview-crop-enabled') === '1',
        imagecropleftpercent: layer.getAttribute('data-preview-crop-left') || '0',
        imagecroptoppercent: layer.getAttribute('data-preview-crop-top') || '0',
        imagecropwidthpercent: layer.getAttribute('data-preview-crop-width') || '100',
        imagecropheightpercent: layer.getAttribute('data-preview-crop-height') || '100'
    }, fitMode === 'bannerfit');
    layer.style.cssText = layerStyles.join(' ');
    localCourseBannerBuilderUpdateCropSelectionFrame(layer, localCourseBannerBuilderGetPreviewCropState(layer));
    image.style.cssText = imageStyles.join(' ');
    localCourseBannerBuilderUpdatePreviewAspectLockButton(layer);
    if (!image.dataset.previewLoadBound) {
        image.addEventListener('load', function() {
            layer.setAttribute('data-preview-natural-width', String(image.naturalWidth || 0));
            layer.setAttribute('data-preview-natural-height', String(image.naturalHeight || 0));
            localCourseBannerBuilderSyncStandalonePreviewLayer(previewRoot, layer);
        });
        image.dataset.previewLoadBound = '1';
    }
}

function localCourseBannerBuilderEnsurePreviewDragMode(form, layer, frame) {
    if (!form || !layer || !frame) {
        return false;
    }
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    if (!anchorInput || !offsetTopInput || !offsetLeftInput) {
        return false;
    }
    var frameRect = frame.getBoundingClientRect();
    var visualLayer = localCourseBannerBuilderGetDraftSelectionVisualLayer(form, layer);
    var layerRect = (visualLayer || layer).getBoundingClientRect();
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    if (!frameRect.width || !frameRect.height || !layerRect.width || !layerRect.height) {
        return false;
    }
    var offsetLeftValue = localCourseBannerBuilderRoundPreviewPercent(((layerRect.left - frameRect.left) / frameRect.width) * 100);
    var offsetTopValue = localCourseBannerBuilderRoundPreviewPercent(((layerRect.top - frameRect.top) / frameRect.height) * 100);
    if (fitOverride && widthInput && heightInput) {
        widthInput.value = String(localCourseBannerBuilderRoundPreviewPercent((layerRect.width / frameRect.width) * 100));
        heightInput.value = String(localCourseBannerBuilderRoundPreviewPercent((layerRect.height / frameRect.height) * 100));
        fitOverride.value = 'custom';
    }
    anchorInput.value = 'top-left';
    offsetLeftInput.value = String(offsetLeftValue);
    offsetTopInput.value = String(offsetTopValue);
    form.dataset.previewUserChanged = '1';
    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    return true;
}

function localCourseBannerBuilderEnsurePreviewCustomMode(form, layer, frame) {
    if (!form) {
        return false;
    }
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    var keepAspectInput = form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    if (!fitOverride || !anchorInput || !widthInput || !heightInput || !offsetTopInput || !offsetLeftInput) {
        return false;
    }
    var previewRoot = layer.closest('[data-banner-preview=\"1\"]');
    var currentFitMode = layer.getAttribute('data-preview-fitmode') ||
        (previewRoot ? (previewRoot.getAttribute('data-default-fitmode') || 'bannerfit') : 'bannerfit');
    var shouldKeepAspect = !!(keepAspectInput && (keepAspectInput.checked || currentFitMode === 'original' || currentFitMode === 'cover'));

    var widthValue = localCourseBannerBuilderNormaliseNumericValue(widthInput.value || '100', 100);
    var heightValue = localCourseBannerBuilderNormaliseNumericValue(heightInput.value || '100', 100);
    var offsetLeftValue = localCourseBannerBuilderNormaliseNumericValue(offsetLeftInput.value || '0', 0);
    var offsetTopValue = localCourseBannerBuilderNormaliseNumericValue(offsetTopInput.value || '0', 0);
    if (layer && frame) {
        var frameRect = frame.getBoundingClientRect();
        var visualLayer = localCourseBannerBuilderGetDraftSelectionVisualLayer(form, layer);
        var layerRect = (visualLayer || layer).getBoundingClientRect();
        if (frameRect.width > 0 && frameRect.height > 0 && layerRect.width > 0 && layerRect.height > 0) {
            widthValue = localCourseBannerBuilderRoundPreviewPercent((layerRect.width / frameRect.width) * 100);
            heightValue = localCourseBannerBuilderRoundPreviewPercent((layerRect.height / frameRect.height) * 100);
            offsetLeftValue = localCourseBannerBuilderRoundPreviewPercent(((layerRect.left - frameRect.left) / frameRect.width) * 100);
            offsetTopValue = localCourseBannerBuilderRoundPreviewPercent(((layerRect.top - frameRect.top) / frameRect.height) * 100);
        }
    }

    if (keepAspectInput) {
        keepAspectInput.checked = shouldKeepAspect;
    }
    widthInput.value = String(widthValue);
    heightInput.value = String(heightValue);
    offsetLeftInput.value = String(offsetLeftValue);
    offsetTopInput.value = String(offsetTopValue);
    fitOverride.value = 'custom';
    anchorInput.value = 'top-left';
    form.dataset.previewUserChanged = '1';
    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    return true;
}

function localCourseBannerBuilderClampPreviewSize(state, widthPercent, heightPercent) {
    widthPercent = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, widthPercent));
    heightPercent = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, heightPercent));
    return {
        width: widthPercent,
        height: heightPercent
    };
}

var localCourseBannerBuilderPreviewGuideThreshold = 5;
var localCourseBannerBuilderPreviewGuideMargin = 12;
var localCourseBannerBuilderPreviewGuidePeerLimit = 4;
var localCourseBannerBuilderCustomSizePercentMax = 1000;

function localCourseBannerBuilderEnsurePreviewGuideLayer(frame) {
    if (!frame) {
        return null;
    }
    var layer = frame.querySelector(':scope > [data-preview-guides-layer=\"1\"]');
    if (!layer) {
        layer = document.createElement('div');
        layer.className = 'local-course-banner-builder-preview-guides';
        layer.setAttribute('data-preview-guides-layer', '1');
        layer.setAttribute('aria-hidden', 'true');
        frame.appendChild(layer);
    }
    return layer;
}

function localCourseBannerBuilderClearPreviewGuides(frame) {
    if (!frame) {
        return;
    }
    var layer = frame.querySelector(':scope > [data-preview-guides-layer=\"1\"]');
    if (layer) {
        layer.innerHTML = '';
        layer.hidden = true;
    }
}

function localCourseBannerBuilderGetPreviewGuideLayer(node) {
    if (!node) {
        return null;
    }
    if (node.matches && node.matches(
            '[data-source-preview-layer=\"1\"], [data-preview-current-layer=\"1\"], [data-preview-draft-layer=\"1\"]'
    )) {
        return node;
    }
    return node.closest ? node.closest(
        '[data-source-preview-layer=\"1\"], [data-preview-current-layer=\"1\"], [data-preview-draft-layer=\"1\"]'
    ) : null;
}

function localCourseBannerBuilderGetPreviewGuideFitMode(layer) {
    if (!layer) {
        return '';
    }
    var previewRoot = layer.closest(
        '[data-layer-banner-preview=\"1\"], [data-source-preview-frame=\"1\"], [data-source-preview-frame-moodle=\"1\"]'
    );
    return layer.getAttribute('data-preview-fitmode') ||
        (previewRoot ? (previewRoot.getAttribute('data-default-fitmode') || '') : '');
}

function localCourseBannerBuilderGetPreviewGuideCrop(layer) {
    if (!layer) {
        return {enabled: false, left: 0, top: 0, width: 100, height: 100};
    }
    var crop = localCourseBannerBuilderNormaliseCropState({
        imagecropenabled: layer.getAttribute('data-preview-crop-enabled') === '1',
        imagecropleftpercent: layer.getAttribute('data-preview-crop-left') || '0',
        imagecroptoppercent: layer.getAttribute('data-preview-crop-top') || '0',
        imagecropwidthpercent: layer.getAttribute('data-preview-crop-width') || '100',
        imagecropheightpercent: layer.getAttribute('data-preview-crop-height') || '100'
    });
    return crop.enabled && localCourseBannerBuilderGetPreviewGuideFitMode(layer) !== 'bannerfit' ?
        crop :
        {enabled: false, left: 0, top: 0, width: 100, height: 100};
}

function localCourseBannerBuilderApplyPreviewGuideCropRect(rect, layer) {
    var crop = localCourseBannerBuilderGetPreviewGuideCrop(layer);
    if (!crop.enabled) {
        return rect;
    }
    var width = typeof rect.width !== 'undefined' ? rect.width : (rect.right - rect.left);
    var height = typeof rect.height !== 'undefined' ? rect.height : (rect.bottom - rect.top);
    var left = rect.left + (width * crop.left / 100);
    var top = rect.top + (height * crop.top / 100);
    width = width * crop.width / 100;
    height = height * crop.height / 100;
    return {
        left: left,
        top: top,
        right: left + width,
        bottom: top + height,
        width: width,
        height: height,
        centerX: left + (width / 2),
        centerY: top + (height / 2)
    };
}

function localCourseBannerBuilderGetRectInFrame(frameRect, node) {
    var layer = localCourseBannerBuilderGetPreviewGuideLayer(node);
    var rect = (layer || node).getBoundingClientRect();
    var frameRelative = {
        left: rect.left - frameRect.left,
        top: rect.top - frameRect.top,
        right: rect.right - frameRect.left,
        bottom: rect.bottom - frameRect.top,
        width: rect.width,
        height: rect.height,
        centerX: rect.left - frameRect.left + (rect.width / 2),
        centerY: rect.top - frameRect.top + (rect.height / 2)
    };
    return localCourseBannerBuilderApplyPreviewGuideCropRect(frameRelative, layer);
}

function localCourseBannerBuilderGetPreviewGuideRawRect(rawRect, layer) {
    if (!rawRect) {
        return rawRect;
    }
    var rect = {
        left: rawRect.left,
        top: rawRect.top,
        right: rawRect.right,
        bottom: rawRect.bottom,
        width: typeof rawRect.width !== 'undefined' ? rawRect.width : (rawRect.right - rawRect.left),
        height: typeof rawRect.height !== 'undefined' ? rawRect.height : (rawRect.bottom - rawRect.top),
        centerX: rawRect.centerX,
        centerY: rawRect.centerY
    };
    if (typeof rect.centerX === 'undefined') {
        rect.centerX = rect.left + (rect.width / 2);
    }
    if (typeof rect.centerY === 'undefined') {
        rect.centerY = rect.top + (rect.height / 2);
    }
    return localCourseBannerBuilderApplyPreviewGuideCropRect(rect, layer);
}

function localCourseBannerBuilderGetLayerRectInFrame(frameRect, node) {
    var rect = node.getBoundingClientRect();
    return {
        left: rect.left - frameRect.left,
        top: rect.top - frameRect.top,
        right: rect.right - frameRect.left,
        bottom: rect.bottom - frameRect.top,
        width: rect.width,
        height: rect.height,
        centerX: rect.left - frameRect.left + (rect.width / 2),
        centerY: rect.top - frameRect.top + (rect.height / 2)
    };
}

function localCourseBannerBuilderPreviewRectsOverlap(a, b, margin) {
    margin = margin || 0;
    return !(a.right + margin < b.left || a.left - margin > b.right ||
        a.bottom + margin < b.top || a.top - margin > b.bottom);
}

function localCourseBannerBuilderIsPreviewSnapEnabled(scope) {
    var host = scope && scope.closest ?
        (scope.closest('[data-preview-snap-enabled]') ||
            scope.closest('[data-source-visual-editor=\"1\"]') ||
            localCourseBannerBuilderGetLayerScope(scope)) :
        null;
    if (!host) {
        host = scope;
    }
    return !(host && host.getAttribute && host.getAttribute('data-preview-snap-enabled') === '0');
}

function localCourseBannerBuilderSyncPreviewSnapButtons(scope) {
    var root = scope || document;
    Array.prototype.slice.call(root.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-preview-snap\"]')).forEach(function(button) {
        var host = button.closest('[data-source-visual-editor=\"1\"]') || localCourseBannerBuilderGetLayerScope(button);
        var enabled = localCourseBannerBuilderIsPreviewSnapEnabled(host || button);
        button.classList.remove('btn-primary');
        button.classList.remove('active');
        button.classList.remove('local-course-banner-builder-source-preview-button--active');
        button.classList.add('btn-outline-secondary');
        button.classList.toggle('local-course-banner-builder-preview-snap-disabled', !enabled);
        button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    });
}

function localCourseBannerBuilderTogglePreviewSnap(button) {
    var host = button ? (button.closest('[data-source-visual-editor=\"1\"]') || localCourseBannerBuilderGetLayerScope(button)) : null;
    if (!host) {
        return;
    }
    var enabled = !localCourseBannerBuilderIsPreviewSnapEnabled(host);
    host.setAttribute('data-preview-snap-enabled', enabled ? '1' : '0');
    Array.prototype.slice.call(host.querySelectorAll(
        '[data-source-preview-frame=\"1\"], [data-banner-preview-frame=\"1\"]'
    )).forEach(function(frame) {
        frame.setAttribute('data-preview-snap-enabled', enabled ? '1' : '0');
    });
    Array.prototype.slice.call(host.querySelectorAll(
        '[data-action=\"local-course-banner-builder-toggle-preview-snap\"]'
    )).forEach(function(item) {
        item.classList.remove('local-course-banner-builder-preview-snap-pulse');
        void item.offsetWidth;
        item.classList.add('local-course-banner-builder-preview-snap-pulse');
    });
    localCourseBannerBuilderSyncPreviewSnapButtons(host);
}

function localCourseBannerBuilderIsOneShotPreviewToolbarAction(button) {
    if (!button || button.disabled || !button.closest) {
        return false;
    }
    if (!button.closest([
        '.local-course-banner-builder-source-preview-visibility-toggle-row',
        '[data-modal-preview-icon-row=\"1\"]',
        '.local-course-banner-builder-slideshow-preview-toolbar',
        '.local-course-banner-builder-title-preview-toolbar'
    ].join(','))) {
        return false;
    }
    var action = button.getAttribute('data-action') || '';
    if (action.indexOf('toggle-') !== -1 || action.indexOf('set-selected-') !== -1) {
        return false;
    }
    return !!action;
}

function localCourseBannerBuilderTriggerPreviewActionFeedback(button) {
    if (!button) {
        return;
    }
    button.classList.remove('local-course-banner-builder-preview-action-feedback');
    void button.offsetWidth;
    button.classList.add('local-course-banner-builder-preview-action-feedback');
}

function localCourseBannerBuilderTitleHexToRgba(hex, opacity) {
    hex = /^#[0-9a-f]{6}$/i.test(hex || '') ? hex : '#000000';
    opacity = Math.max(0, Math.min(100, parseFloat(opacity || '0'))) / 100;
    return 'rgba(' + parseInt(hex.slice(1, 3), 16) + ', ' +
        parseInt(hex.slice(3, 5), 16) + ', ' +
        parseInt(hex.slice(5, 7), 16) + ', ' + opacity.toFixed(3) + ')';
}

function localCourseBannerBuilderSyncColourInput(input) {
    if (!input || input.type !== 'color') {
        return;
    }
    var value = /^#[0-9a-f]{6}$/i.test(input.value || '') ? input.value : '#000000';
    input.style.setProperty('--local-course-banner-builder-selected-color', value);
    input.style.backgroundColor = value;
}

function localCourseBannerBuilderBindColourInputs(scope) {
    Array.prototype.slice.call((scope || document).querySelectorAll('input[type=\"color\"]')).forEach(function(input) {
        if (input.dataset.courseBannerBuilderColourBound === '1') {
            localCourseBannerBuilderSyncColourInput(input);
            return;
        }
        input.dataset.courseBannerBuilderColourBound = '1';
        input.addEventListener('input', function() {
            localCourseBannerBuilderSyncColourInput(input);
        });
        input.addEventListener('change', function() {
            localCourseBannerBuilderSyncColourInput(input);
        });
        localCourseBannerBuilderSyncColourInput(input);
    });
}

function localCourseBannerBuilderReadTitleValue(form, name, fallback) {
    var input = form ? form.querySelector('[name=\"' + name + '\"]') : null;
    if (!input) {
        return fallback;
    }
    if (input.type === 'checkbox') {
        return input.checked;
    }
    return input.value === '' ? fallback : input.value;
}

function localCourseBannerBuilderReadTitleFlag(form, name, fallback) {
    var value = localCourseBannerBuilderReadTitleValue(form, name, fallback ? '1' : '0');
    return value === true || value === '1' || value === 1 || value === 'true';
}

function localCourseBannerBuilderSetTitleField(form, name, value) {
    var input = form ? form.querySelector('[name=\"' + name + '\"]') : null;
    if (!input) {
        return;
    }
    input.value = value;
    var range = form.querySelector('[data-title-range-for=\"' + name + '\"]');
    var number = form.querySelector('[data-title-number-for=\"' + name + '\"]');
    if (range) {
        range.value = value;
    }
    if (number) {
        number.value = value;
    }
    localCourseBannerBuilderSyncTitleSliderOutput(form, name, value);
}

function localCourseBannerBuilderSyncTitleSliderOutput(form, name, value) {
    var output = form ? form.querySelector('[data-title-output-for=\"' + name + '\"]') : null;
    var number = form ? form.querySelector('[data-title-number-for=\"' + name + '\"]') : null;
    if (!output) {
        return;
    }
    output.textContent = value + (number ? (number.getAttribute('data-title-output-unit') || '') : '');
}

function localCourseBannerBuilderSyncTitleColorPickers(form) {
    Array.prototype.slice.call(form.querySelectorAll('[data-title-color-picker-for]')).forEach(function(picker) {
        var text = form.querySelector('[data-title-color-text-for=\"' +
            picker.getAttribute('data-title-color-picker-for') + '\"]');
        if (text && /^#[0-9a-f]{6}$/i.test(text.value || '')) {
            picker.value = text.value;
        }
        localCourseBannerBuilderSyncColourInput(picker);
    });
}

function localCourseBannerBuilderCaptureTitleState(form) {
    var state = {};
    Array.prototype.slice.call(form.querySelectorAll('[data-title-control]')).forEach(function(input) {
        state[input.name] = input.type === 'checkbox' ? input.checked : input.value;
    });
    return state;
}

function localCourseBannerBuilderReadTitleInheritStates(form) {
    var states;
    try {
        states = JSON.parse(form.getAttribute('data-title-inherit-states') || '{}');
    } catch (error) {
        states = {};
    }
    Array.prototype.slice.call(document.querySelectorAll('[data-banner-title-editor=\"1\"]')).forEach(function(editor) {
        var context = editor.getAttribute('data-title-current-context') || '';
        if (context) {
            states[context] = localCourseBannerBuilderCaptureTitleState(editor);
        }
    });
    return states;
}

function localCourseBannerBuilderApplyTitleState(form, state) {
    Object.keys(state || {}).forEach(function(name) {
        var input = form.querySelector('[name=\"' + name + '\"]');
        if (!input) {
            return;
        }
        if (input.type === 'checkbox') {
            input.checked = state[name] === true || state[name] === '1' || state[name] === 1 || state[name] === 'true';
        } else {
            input.value = state[name];
        }
        var range = form.querySelector('[data-title-range-for=\"' + name + '\"]');
        var number = form.querySelector('[data-title-number-for=\"' + name + '\"]');
        if (range) {
            range.value = state[name];
        }
        if (number) {
            number.value = state[name];
        }
        localCourseBannerBuilderSyncTitleSliderOutput(form, name, state[name]);
    });
    localCourseBannerBuilderSyncTitleColorPickers(form);
    localCourseBannerBuilderSyncTitleToggleButtons(form);
    localCourseBannerBuilderSyncTitleFrameTypeButtons(form);
    localCourseBannerBuilderSetTitleStyleMode(form, localCourseBannerBuilderReadTitleValue(form, 'stylemode', 'custom'));
    localCourseBannerBuilderUpdateTitlePreview(form);
}

function localCourseBannerBuilderSyncTitleFrameTypeButtons(form) {
    var current = localCourseBannerBuilderReadTitleValue(form, 'frametype', 'box');
    Array.prototype.slice.call(form.querySelectorAll('[data-action=\"local-course-banner-builder-set-title-frame-type\"]')).forEach(function(button) {
        var active = button.getAttribute('data-title-frame-type') === current;
        button.classList.toggle('active', active);
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-secondary', !active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function localCourseBannerBuilderPushTitleUndo(form) {
    var undo = JSON.parse(form.dataset.titlePreviewUndoStack || '[]');
    undo.push(localCourseBannerBuilderCaptureTitleState(form));
    form.dataset.titlePreviewUndoStack = JSON.stringify(undo);
    form.dataset.titlePreviewRedoStack = '[]';
    localCourseBannerBuilderSyncTitleToolbar(form);
}

function localCourseBannerBuilderSyncTitleToggleButtons(form) {
    var frameEnabledInput = form.querySelector('[name=\"frameenabled\"][data-title-control=\"frameenabled\"]');
    var frameEnabled = !!(frameEnabledInput && frameEnabledInput.value === '1');
    Array.prototype.slice.call(form.querySelectorAll('[data-local-title-toggle-button=\"1\"]')).forEach(function(button) {
        var input = form.querySelector(button.getAttribute('data-target-input'));
        var enabled = !!(input && input.value === '1');
        var isFrameShadow = !!(input && input.name === 'frameshadowenabled');
        var icon = button.querySelector('.fa');
        var label = button.querySelector('span');
        if (isFrameShadow && !frameEnabled) {
            enabled = false;
            if (input) {
                input.value = '0';
            }
        }
        button.disabled = isFrameShadow && !frameEnabled;
        button.classList.toggle('disabled', button.disabled);
        button.classList.toggle('btn-primary', enabled);
        button.classList.toggle('btn-outline-secondary', !enabled);
        button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        button.setAttribute('aria-disabled', button.disabled ? 'true' : 'false');
        if (icon) {
            icon.className = 'fa ' + (enabled ? 'fa-toggle-on' : 'fa-toggle-off') + ' me-2';
        }
        if (label) {
            label.textContent = enabled ? button.getAttribute('data-label-on') : button.getAttribute('data-label-off');
        }
    });
    Array.prototype.slice.call(form.querySelectorAll('[data-title-frame-shadow-note=\"1\"]')).forEach(function(note) {
        note.hidden = frameEnabled;
    });
    Array.prototype.slice.call(form.querySelectorAll('[data-action=\"local-course-banner-builder-toggle-title-style\"]')).forEach(function(button) {
        var input = form.querySelector('[data-title-style-input=\"' + button.getAttribute('data-title-style') + '\"]');
        var active = !!(input && input.value === '1');
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-secondary', !active);
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function localCourseBannerBuilderSetTitleStyleMode(form, mode) {
    var input = form.querySelector('[name=\"stylemode\"][data-title-control=\"stylemode\"]');
    if (input) {
        input.value = mode;
    }
    Array.prototype.slice.call(form.querySelectorAll('[data-title-style-mode]')).forEach(function(button) {
        var active = button.getAttribute('data-title-style-mode') === mode;
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-secondary', !active);
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function localCourseBannerBuilderMarkTitleStyleCustom(form) {
    if (form && form.dataset.titleApplyingInheritedStyle !== '1') {
        localCourseBannerBuilderSetTitleStyleMode(form, 'custom');
    }
}

function localCourseBannerBuilderSyncTitleSnapButton(form) {
    var button = form.querySelector('[data-action=\"local-course-banner-builder-toggle-title-preview-snap\"]');
    var enabled = form.getAttribute('data-title-preview-snap-enabled') !== '0';
    if (!button) {
        return;
    }
    button.classList.remove('btn-primary');
    button.classList.remove('active');
    button.classList.remove('local-course-banner-builder-source-preview-button--active');
    button.classList.add('btn-outline-secondary');
    button.classList.toggle('local-course-banner-builder-preview-snap-disabled', !enabled);
    button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
}

function localCourseBannerBuilderToggleTitleSnap(form, button) {
    var enabled = form.getAttribute('data-title-preview-snap-enabled') === '0';
    form.setAttribute('data-title-preview-snap-enabled', enabled ? '1' : '0');
    if (button) {
        button.classList.remove('local-course-banner-builder-preview-snap-pulse');
        void button.offsetWidth;
        button.classList.add('local-course-banner-builder-preview-snap-pulse');
    }
    localCourseBannerBuilderSyncTitleSnapButton(form);
}

function localCourseBannerBuilderSnapTitlePercent(form, value, axis, frame) {
    if (form.getAttribute('data-title-preview-snap-enabled') === '0') {
        return value;
    }
    if (Math.abs(value - 50) > 2) {
        return value;
    }
    if (frame) {
        var guideLayer = localCourseBannerBuilderEnsurePreviewGuideLayer(frame);
        localCourseBannerBuilderAddPreviewGuide(
            guideLayer,
            axis === 'x' ? 'vertical' : 'horizontal',
            axis === 'x' ? frame.clientWidth / 2 : frame.clientHeight / 2,
            'frame local-course-banner-builder-preview-guide--center'
        );
    }
    return 50;
}

function localCourseBannerBuilderUpdateTitleGuides(form, frame) {
    if (!form || !frame || form.getAttribute('data-title-preview-snap-enabled') === '0') {
        return;
    }
    localCourseBannerBuilderClearPreviewGuides(frame);
    var guideLayer = localCourseBannerBuilderEnsurePreviewGuideLayer(frame);
    if (guideLayer) {
        guideLayer.hidden = false;
    }
    localCourseBannerBuilderAddPreviewGuide(
        guideLayer,
        'vertical',
        frame.clientWidth / 2,
        'frame local-course-banner-builder-preview-guide--center'
    );
    localCourseBannerBuilderAddPreviewGuide(
        guideLayer,
        'horizontal',
        frame.clientHeight / 2,
        'frame local-course-banner-builder-preview-guide--center'
    );
}

function localCourseBannerBuilderSyncTitleToolbar(form) {
    var undo = JSON.parse(form.dataset.titlePreviewUndoStack || '[]');
    var redo = JSON.parse(form.dataset.titlePreviewRedoStack || '[]');
    var undoButton = form.querySelector('[data-action=\"local-course-banner-builder-title-preview-undo\"]');
    var redoButton = form.querySelector('[data-action=\"local-course-banner-builder-title-preview-redo\"]');
    if (undoButton) {
        undoButton.disabled = undo.length === 0;
    }
    if (redoButton) {
        redoButton.disabled = redo.length === 0;
    }
}

function localCourseBannerBuilderToggleTitleSidePanel(form, button) {
    if (!form || !button) {
        return;
    }
    var panelKey = button.getAttribute('data-title-side-panel-target');
    var panel = panelKey ? form.querySelector('[data-title-side-panel=\"' + panelKey + '\"]') : null;
    if (!panel) {
        return;
    }
    var shouldOpen = panel.hidden || panel.classList.contains('is-collapsed');
    Array.prototype.slice.call(form.querySelectorAll('[data-title-side-panel]')).forEach(function(candidate) {
        var key = candidate.getAttribute('data-title-side-panel');
        var candidateButton = form.querySelector('[data-title-side-panel-target=\"' + key + '\"]');
        if (candidate !== panel) {
            localCourseBannerBuilderToggleOpacityPanel(candidate, candidateButton, false);
            if (candidateButton) {
                candidateButton.classList.remove('btn-primary');
                candidateButton.classList.add('btn-outline-secondary');
            }
        }
    });
    localCourseBannerBuilderToggleOpacityPanel(panel, button, shouldOpen);
    button.classList.toggle('btn-primary', shouldOpen);
    button.classList.toggle('btn-outline-secondary', !shouldOpen);
}

function localCourseBannerBuilderUpdateTitlePreview(form) {
    if (!form || !form.matches('[data-banner-title-editor=\"1\"]')) {
        return;
    }
    var previewText = form.querySelector('[data-title-preview-text=\"1\"]');
    var previewLabel = previewText ? previewText.querySelector('[data-title-preview-label=\"1\"]') : null;
    var overlay = form.querySelector('[data-title-preview-overlay=\"1\"]');
    if (!previewText || !previewLabel) {
        return;
    }
    var enabled = localCourseBannerBuilderReadTitleFlag(form, 'enabled', false);
    var x = localCourseBannerBuilderReadTitleValue(form, 'x', '50');
    var y = localCourseBannerBuilderReadTitleValue(form, 'y', '50');
    var size = localCourseBannerBuilderReadTitleValue(form, 'fontsize', '100');
    var lineHeight = Math.max(80, Math.min(180,
        parseFloat(localCourseBannerBuilderReadTitleValue(form, 'lineheight', '105')) || 105
    ));
    var color = localCourseBannerBuilderReadTitleValue(form, 'color', '#FFFFFF');
    var font = localCourseBannerBuilderReadTitleValue(form, 'fontfamily', '');
    var mode = localCourseBannerBuilderReadTitleValue(form, 'activitytitlemode', 'activity');
    var titleText = previewText.getAttribute('data-title-preview-activity') || previewLabel.textContent;
    if (mode === 'course') {
        titleText = previewText.getAttribute('data-title-preview-course') || '';
    } else if (mode === 'both') {
        titleText = (previewText.getAttribute('data-title-preview-course') || '') +
            String.fromCharCode(10) +
            (previewText.getAttribute('data-title-preview-activity') || '');
    }
    var highlightFrame = localCourseBannerBuilderReadTitleValue(form, 'frametype', 'box') === 'highlight';
    previewText.hidden = !enabled || mode === 'none';
    previewText.style.left = x + '%';
    previewText.style.top = y + '%';
    previewText.style.color = color;
    previewText.style.fontSize = 'clamp(' + (8 * size / 100).toFixed(3) + 'cqh, ' +
        (4 * size / 100).toFixed(3) + 'cqw, ' + (28 * size / 100).toFixed(3) + 'cqh)';
    previewText.style.fontFamily = font || 'inherit';
    previewText.style.fontWeight = localCourseBannerBuilderReadTitleFlag(form, 'bold', false) ? '800' : '500';
    previewText.style.fontStyle = localCourseBannerBuilderReadTitleFlag(form, 'italic', false) ? 'italic' : 'normal';
    previewText.style.textDecoration = localCourseBannerBuilderReadTitleFlag(form, 'underline', false) ? 'underline' : 'none';
    previewText.style.textTransform = localCourseBannerBuilderReadTitleFlag(form, 'allcaps', false) ? 'uppercase' : 'none';
    previewText.style.lineHeight = lineHeight + '%';
    previewLabel.style.lineHeight = 'inherit';
    previewText.style.textAlign = 'center';
    previewText.style.whiteSpace = 'pre';
    previewText.style.zIndex = localCourseBannerBuilderReadTitleFlag(form, 'aboveborder', true) ? '80' : '18';
    previewText.classList.toggle(
        'local-course-banner-builder-title-preview-text--highlight-frame',
        highlightFrame
    );
    previewLabel.textContent = '';
    if (highlightFrame) {
        var newline = String.fromCharCode(10);
        var lines = String(titleText || '')
            .split(String.fromCharCode(13) + newline).join(newline)
            .split(String.fromCharCode(13)).join(newline)
            .split(newline);
        lines.forEach(function(line, index) {
            var lineNode = document.createElement('span');
            lineNode.textContent = line || '\u00a0';
            previewLabel.appendChild(lineNode);
            if (index < lines.length - 1) {
                previewLabel.appendChild(document.createElement('br'));
            }
        });
    } else {
        previewLabel.textContent = titleText;
    }
    previewLabel.style.background = '';
    previewLabel.style.border = '';
    previewLabel.style.borderRadius = '';
    previewLabel.style.padding = '';
    previewLabel.style.boxShadow = '';
    Array.prototype.slice.call(previewLabel.querySelectorAll('span')).forEach(function(lineNode) {
        lineNode.style.background = '';
        lineNode.style.border = '';
        lineNode.style.borderRadius = '';
        lineNode.style.padding = '';
        lineNode.style.boxShadow = '';
    });

    if (localCourseBannerBuilderReadTitleFlag(form, 'frameenabled', false)) {
        var frameTargets = highlightFrame ?
            Array.prototype.slice.call(previewLabel.querySelectorAll('span')) :
            [previewText];
        if (highlightFrame) {
            previewText.style.background = 'transparent';
            previewText.style.border = '0';
            previewText.style.borderRadius = '0';
            previewText.style.padding = '0';
            previewText.style.boxShadow = 'none';
        }
        var frameBackground = localCourseBannerBuilderTitleHexToRgba(
            localCourseBannerBuilderReadTitleValue(form, 'framecolor', '#000000'),
            localCourseBannerBuilderReadTitleValue(form, 'frameopacity', '35')
        );
        var frameBorder = localCourseBannerBuilderReadTitleValue(form, 'frameborderwidth', '0') + 'px solid ' +
            localCourseBannerBuilderReadTitleValue(form, 'framebordercolor', '#FFFFFF');
        var frameRadius = localCourseBannerBuilderReadTitleValue(form, 'frameradius', '12') + 'px';
        var padding = parseFloat(localCourseBannerBuilderReadTitleValue(form, 'framepadding', '18')) || 0;
        var framePadding = (padding / 2) + 'px ' + padding + 'px';
        var frameShadow = 'none';
        if (localCourseBannerBuilderReadTitleFlag(form, 'frameshadowenabled', false)) {
            var frameDistance = parseFloat(localCourseBannerBuilderReadTitleValue(form, 'frameshadowdistance', '6')) || 0;
            var frameAngle = (parseFloat(localCourseBannerBuilderReadTitleValue(form, 'frameshadowdirection', '135')) || 0) *
                Math.PI / 180;
            var frameBlur = parseFloat(localCourseBannerBuilderReadTitleValue(form, 'frameshadowblur', '14')) || 0;
            frameShadow = (Math.cos(frameAngle) * frameDistance).toFixed(2) + 'px ' +
                (Math.sin(frameAngle) * frameDistance).toFixed(2) + 'px ' + frameBlur + 'px ' +
                localCourseBannerBuilderTitleHexToRgba(
                    localCourseBannerBuilderReadTitleValue(form, 'frameshadowcolor', '#000000'),
                    localCourseBannerBuilderReadTitleValue(form, 'frameshadowopacity', '25')
                );
        }
        frameTargets.forEach(function(frameTarget) {
            frameTarget.style.background = frameBackground;
            frameTarget.style.border = frameBorder;
            frameTarget.style.borderRadius = frameRadius;
            frameTarget.style.padding = framePadding;
            frameTarget.style.boxShadow = frameShadow;
        });
    } else {
        previewText.style.background = 'transparent';
        previewText.style.border = '0';
        previewText.style.borderRadius = '0';
        previewText.style.padding = '0';
        previewText.style.boxShadow = 'none';
    }

    if (localCourseBannerBuilderReadTitleFlag(form, 'shadowenabled', false)) {
        var distance = parseFloat(localCourseBannerBuilderReadTitleValue(form, 'shadowdistance', '4')) || 0;
        var angle = (parseFloat(localCourseBannerBuilderReadTitleValue(form, 'shadowdirection', '135')) || 0) * Math.PI / 180;
        var blur = parseFloat(localCourseBannerBuilderReadTitleValue(form, 'shadowblur', '10')) || 0;
        previewText.style.textShadow = (Math.cos(angle) * distance).toFixed(2) + 'px ' +
            (Math.sin(angle) * distance).toFixed(2) + 'px ' + blur + 'px ' +
            localCourseBannerBuilderTitleHexToRgba(
                localCourseBannerBuilderReadTitleValue(form, 'shadowcolor', '#000000'),
                localCourseBannerBuilderReadTitleValue(form, 'shadowopacity', '55')
            );
    } else {
        previewText.style.textShadow = 'none';
    }

    if (overlay) {
        overlay.hidden = !localCourseBannerBuilderReadTitleFlag(form, 'overlayenabled', false);
        overlay.style.background = localCourseBannerBuilderTitleHexToRgba(
            localCourseBannerBuilderReadTitleValue(form, 'overlaycolor', '#000000'),
            localCourseBannerBuilderReadTitleValue(form, 'overlayopacity', '25')
        );
    }
}

function localCourseBannerBuilderBindTitleEditor(form) {
    if (!form || form.dataset.titleEditorBound === '1') {
        return;
    }
    form.dataset.titleEditorBound = '1';
    form.addEventListener('input', function(event) {
        var target = event.target;
        if (target && target.matches('[data-title-range-for]')) {
            var rangeName = target.getAttribute('data-title-range-for');
            var number = form.querySelector('[data-title-number-for=\"' + rangeName + '\"]');
            if (number) {
                number.value = target.value;
            }
            localCourseBannerBuilderSyncTitleSliderOutput(form, rangeName, target.value);
        }
        if (target && target.matches('[data-title-number-for]')) {
            var numberName = target.getAttribute('data-title-number-for');
            var range = form.querySelector('[data-title-range-for=\"' + numberName + '\"]');
            if (range) {
                range.value = target.value;
            }
            localCourseBannerBuilderSyncTitleSliderOutput(form, numberName, target.value);
        }
        if (target && target.matches('[data-title-color-picker-for]')) {
            var text = form.querySelector('[data-title-color-text-for=\"' + target.getAttribute('data-title-color-picker-for') + '\"]');
            if (text) {
                text.value = target.value.toUpperCase();
            }
            localCourseBannerBuilderSyncColourInput(target);
        }
        if (target && target.matches('[data-title-color-text-for]')) {
            var picker = form.querySelector('[data-title-color-picker-for=\"' + target.getAttribute('data-title-color-text-for') + '\"]');
            if (picker && /^#[0-9a-f]{6}$/i.test(target.value)) {
                picker.value = target.value;
                localCourseBannerBuilderSyncColourInput(picker);
            }
        }
        if (target && target.matches('[data-title-control], [data-title-range-for], [data-title-number-for], [data-title-color-picker-for], [data-title-color-text-for]') &&
                target.name !== 'stylemode') {
            localCourseBannerBuilderMarkTitleStyleCustom(form);
        }
        localCourseBannerBuilderUpdateTitlePreview(form);
    }, true);
    form.addEventListener('change', function(event) {
        if (event.target && event.target.matches('[data-title-control]') && event.target.name !== 'stylemode') {
            localCourseBannerBuilderMarkTitleStyleCustom(form);
        }
        localCourseBannerBuilderUpdateTitlePreview(form);
    }, true);
    form.addEventListener('click', function(event) {
        var toggleButton = event.target.closest('[data-local-title-toggle-button=\"1\"]');
        var styleButton = event.target.closest('[data-action=\"local-course-banner-builder-toggle-title-style\"]');
        var sidePanelButton = event.target.closest('[data-action=\"local-course-banner-builder-toggle-title-side-panel\"]');
        var snapButton = event.target.closest('[data-action=\"local-course-banner-builder-toggle-title-preview-snap\"]');
        var titleCornerButton = event.target.closest('[data-action=\"local-course-banner-builder-set-title-frame-corners\"]');
        var frameTypeButton = event.target.closest('[data-action=\"local-course-banner-builder-set-title-frame-type\"]');
        var styleModeButton = event.target.closest('[data-action=\"local-course-banner-builder-apply-title-style-mode\"]');
        var undoButton = event.target.closest('[data-action=\"local-course-banner-builder-title-preview-undo\"]');
        var redoButton = event.target.closest('[data-action=\"local-course-banner-builder-title-preview-redo\"]');
        var recenterButton = event.target.closest('[data-action=\"local-course-banner-builder-title-preview-recenter\"]');
        var resetButton = event.target.closest('[data-action=\"local-course-banner-builder-reset-title-style\"]');
        var input;
        var stack;
        var redo;
        var state;
        var defaults;
        if (toggleButton) {
            event.preventDefault();
            if (toggleButton.disabled || toggleButton.getAttribute('aria-disabled') === 'true') {
                return;
            }
            input = form.querySelector(toggleButton.getAttribute('data-target-input'));
            if (!input) {
                return;
            }
            localCourseBannerBuilderPushTitleUndo(form);
            input.value = input.value === '1' ? '0' : '1';
            localCourseBannerBuilderSyncTitleToggleButtons(form);
            localCourseBannerBuilderMarkTitleStyleCustom(form);
            localCourseBannerBuilderUpdateTitlePreview(form);
            return;
        }
        if (styleButton) {
            event.preventDefault();
            input = form.querySelector('[data-title-style-input=\"' + styleButton.getAttribute('data-title-style') + '\"]');
            if (!input) {
                return;
            }
            localCourseBannerBuilderPushTitleUndo(form);
            input.value = input.value !== '1' ? '1' : '0';
            localCourseBannerBuilderSyncTitleToggleButtons(form);
            localCourseBannerBuilderMarkTitleStyleCustom(form);
            localCourseBannerBuilderUpdateTitlePreview(form);
            return;
        }
        if (styleModeButton) {
            event.preventDefault();
            var mode = styleModeButton.getAttribute('data-title-style-mode') || 'custom';
            if (mode !== 'custom') {
                var states = localCourseBannerBuilderReadTitleInheritStates(form);
                if (states[mode]) {
                    localCourseBannerBuilderPushTitleUndo(form);
                    form.dataset.titleApplyingInheritedStyle = '1';
                    localCourseBannerBuilderApplyTitleState(form, states[mode]);
                    form.dataset.titleApplyingInheritedStyle = '0';
                }
            }
            localCourseBannerBuilderSetTitleStyleMode(form, mode);
            localCourseBannerBuilderUpdateTitlePreview(form);
            return;
        }
        if (sidePanelButton) {
            event.preventDefault();
            localCourseBannerBuilderToggleTitleSidePanel(form, sidePanelButton);
            return;
        }
        if (snapButton) {
            event.preventDefault();
            localCourseBannerBuilderToggleTitleSnap(form, snapButton);
            return;
        }
        if (titleCornerButton) {
            event.preventDefault();
            localCourseBannerBuilderPushTitleUndo(form);
            localCourseBannerBuilderSetTitleField(form, 'frameradius', titleCornerButton.getAttribute('data-title-frame-radius') || '0');
            localCourseBannerBuilderMarkTitleStyleCustom(form);
            Array.prototype.slice.call(form.querySelectorAll('[data-action=\"local-course-banner-builder-set-title-frame-corners\"]')).forEach(function(button) {
                var active = button === titleCornerButton;
                button.classList.toggle('active', active);
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-secondary', !active);
            });
            localCourseBannerBuilderUpdateTitlePreview(form);
            return;
        }
        if (frameTypeButton) {
            event.preventDefault();
            localCourseBannerBuilderPushTitleUndo(form);
            localCourseBannerBuilderSetTitleField(form, 'frametype', frameTypeButton.getAttribute('data-title-frame-type') || 'box');
            localCourseBannerBuilderMarkTitleStyleCustom(form);
            Array.prototype.slice.call(form.querySelectorAll('[data-action=\"local-course-banner-builder-set-title-frame-type\"]')).forEach(function(button) {
                var active = button === frameTypeButton;
                button.classList.toggle('active', active);
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-secondary', !active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            localCourseBannerBuilderUpdateTitlePreview(form);
            return;
        }
        if (recenterButton) {
            event.preventDefault();
            localCourseBannerBuilderPushTitleUndo(form);
            localCourseBannerBuilderSetTitleField(form, 'x', '50');
            localCourseBannerBuilderSetTitleField(form, 'y', '50');
            localCourseBannerBuilderMarkTitleStyleCustom(form);
            localCourseBannerBuilderUpdateTitlePreview(form);
            return;
        }
        if (resetButton) {
            event.preventDefault();
            try {
                defaults = JSON.parse(resetButton.getAttribute('data-default-title-state') || '{}');
            } catch (error) {
                defaults = {};
            }
            localCourseBannerBuilderPushTitleUndo(form);
            localCourseBannerBuilderApplyTitleState(form, defaults);
            localCourseBannerBuilderSetTitleStyleMode(form, 'custom');
            return;
        }
        if (undoButton) {
            event.preventDefault();
            stack = JSON.parse(form.dataset.titlePreviewUndoStack || '[]');
            if (!stack.length) {
                return;
            }
            redo = JSON.parse(form.dataset.titlePreviewRedoStack || '[]');
            redo.push(localCourseBannerBuilderCaptureTitleState(form));
            state = stack.pop();
            form.dataset.titlePreviewUndoStack = JSON.stringify(stack);
            form.dataset.titlePreviewRedoStack = JSON.stringify(redo);
            localCourseBannerBuilderApplyTitleState(form, state);
            localCourseBannerBuilderSyncTitleToolbar(form);
            return;
        }
        if (redoButton) {
            event.preventDefault();
            redo = JSON.parse(form.dataset.titlePreviewRedoStack || '[]');
            if (!redo.length) {
                return;
            }
            stack = JSON.parse(form.dataset.titlePreviewUndoStack || '[]');
            stack.push(localCourseBannerBuilderCaptureTitleState(form));
            state = redo.pop();
            form.dataset.titlePreviewUndoStack = JSON.stringify(stack);
            form.dataset.titlePreviewRedoStack = JSON.stringify(redo);
            localCourseBannerBuilderApplyTitleState(form, state);
            localCourseBannerBuilderSyncTitleToolbar(form);
        }
    });
    var dragState = null;
    form.addEventListener('pointerdown', function(event) {
        var target = event.target.closest('[data-title-preview-draggable=\"1\"]');
        var frame = form.querySelector('[data-title-preview-frame=\"1\"]');
        if (!target || !frame || event.button !== 0) {
            return;
        }
        event.preventDefault();
        target.classList.add('local-course-banner-builder-slideshow-preview-draggable--selected');
        localCourseBannerBuilderPushTitleUndo(form);
        var frameRect = frame.getBoundingClientRect();
        var handle = event.target.closest('[data-title-preview-resize-handle]');
        if (handle) {
            dragState = {
                mode: 'resize',
                frame: frame,
                target: target,
                handle: handle.getAttribute('data-title-preview-resize-handle'),
                startX: event.clientX,
                startY: event.clientY,
                frameWidth: Math.max(1, frameRect.width),
                frameHeight: Math.max(1, frameRect.height),
                startSize: parseFloat(localCourseBannerBuilderReadTitleValue(form, 'fontsize', '100')) || 100
            };
            if (target.setPointerCapture) {
                target.setPointerCapture(event.pointerId);
            }
            return;
        }
        dragState = {
            mode: 'move',
            frame: frame,
            target: target,
            startX: event.clientX,
            startY: event.clientY,
            frameWidth: Math.max(1, frameRect.width),
            frameHeight: Math.max(1, frameRect.height),
            originX: parseFloat(localCourseBannerBuilderReadTitleValue(form, 'x', '50')) || 50,
            originY: parseFloat(localCourseBannerBuilderReadTitleValue(form, 'y', '50')) || 50
        };
        if (target.setPointerCapture) {
            target.setPointerCapture(event.pointerId);
        }
        localCourseBannerBuilderUpdateTitleGuides(form, frame);
    });
    form.addEventListener('pointermove', function(event) {
        if (!dragState) {
            return;
        }
        if (dragState.mode === 'resize') {
            var direction = dragState.handle;
            var distance = direction === 'left' ? dragState.startX - event.clientX :
                (direction === 'right' ? event.clientX - dragState.startX :
                    (direction === 'top' ? dragState.startY - event.clientY : event.clientY - dragState.startY));
            var axis = direction === 'left' || direction === 'right' ? dragState.frameWidth : dragState.frameHeight;
            var nextSize = Math.max(25, Math.min(160, dragState.startSize + ((distance / axis) * 120)));
            localCourseBannerBuilderSetTitleField(form, 'fontsize', nextSize.toFixed(1));
            localCourseBannerBuilderUpdateTitlePreview(form);
            return;
        }
        var nextX = Math.max(0, Math.min(100, dragState.originX +
            ((event.clientX - dragState.startX) / dragState.frameWidth) * 100));
        var nextY = Math.max(0, Math.min(100, dragState.originY +
            ((event.clientY - dragState.startY) / dragState.frameHeight) * 100));
        localCourseBannerBuilderClearPreviewGuides(dragState.frame);
        nextX = localCourseBannerBuilderSnapTitlePercent(form, nextX, 'x', dragState.frame);
        nextY = localCourseBannerBuilderSnapTitlePercent(form, nextY, 'y', dragState.frame);
        ['x', 'y'].forEach(function(name) {
            var value = name === 'x' ? nextX : nextY;
            var number = form.querySelector('[data-title-number-for=\"' + name + '\"]');
            var range = form.querySelector('[data-title-range-for=\"' + name + '\"]');
            if (number) {
                number.value = value.toFixed(1);
            }
            if (range) {
                range.value = value.toFixed(1);
            }
        });
        localCourseBannerBuilderUpdateTitlePreview(form);
        localCourseBannerBuilderUpdateTitleGuides(form, dragState.frame);
    });
    var stopTitleDrag = function(event) {
        if (!dragState) {
            return;
        }
        if (dragState.target.releasePointerCapture && event && event.pointerId !== undefined) {
            dragState.target.releasePointerCapture(event.pointerId);
        }
        localCourseBannerBuilderClearPreviewGuides(dragState.frame);
        dragState = null;
    };
    form.addEventListener('pointerup', stopTitleDrag);
    form.addEventListener('pointercancel', stopTitleDrag);
    localCourseBannerBuilderSyncTitleToggleButtons(form);
    localCourseBannerBuilderSyncTitleFrameTypeButtons(form);
    localCourseBannerBuilderSetTitleStyleMode(form, localCourseBannerBuilderReadTitleValue(form, 'stylemode', 'custom'));
    localCourseBannerBuilderSyncTitleSnapButton(form);
    localCourseBannerBuilderSyncTitleToolbar(form);
    localCourseBannerBuilderSyncTitleColorPickers(form);
    localCourseBannerBuilderUpdateTitlePreview(form);
}

function localCourseBannerBuilderBindTitleEditors(scope) {
    Array.prototype.slice.call((scope || document).querySelectorAll('[data-banner-title-editor=\"1\"]')).forEach(
        localCourseBannerBuilderBindTitleEditor
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        localCourseBannerBuilderBindColourInputs(document);
        localCourseBannerBuilderBindTitleEditors(document);
    }, {once: true});
} else {
    localCourseBannerBuilderBindColourInputs(document);
    localCourseBannerBuilderBindTitleEditors(document);
}

function localCourseBannerBuilderAddPreviewGuide(layer, orientation, position, kind) {
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
}

function localCourseBannerBuilderMaybeAddPreviewGuide(layer, orientation, activeValue, targetValue, kind, seen) {
    if (Math.abs(activeValue - targetValue) > localCourseBannerBuilderPreviewGuideThreshold) {
        return;
    }
    var key = orientation + ':' + Math.round(targetValue) + ':' + kind;
    if (seen[key]) {
        return;
    }
    seen[key] = true;
    localCourseBannerBuilderAddPreviewGuide(layer, orientation, targetValue, kind);
}

function localCourseBannerBuilderPreviewSnapCandidate(best, axis, delta, priority) {
    if (Math.abs(delta) > localCourseBannerBuilderPreviewGuideThreshold) {
        return best;
    }
    var current = best[axis];
    if (!current || priority > current.priority ||
            (priority === current.priority && Math.abs(delta) < Math.abs(current.delta))) {
        best[axis] = {
            delta: delta,
            priority: priority
        };
    }
    return best;
}

function localCourseBannerBuilderGetPreviewGuideTargets(frame, activeLayer, targetSelector, options, activeRect) {
    options = options || {};
    var targets = Array.prototype.slice.call(frame.querySelectorAll(targetSelector || '[data-preview-image-tag=\"1\"]'))
        .map(function(node) {
            return node.closest('[data-source-preview-layer=\"1\"], [data-preview-current-layer=\"1\"], [data-preview-draft-layer=\"1\"]') || node;
        })
        .filter(function(node, index, list) {
            return node && node !== activeLayer && !node.hidden && list.indexOf(node) === index &&
                !(node.classList && node.classList.contains('local-course-banner-builder-source-preview-layer--hidden-in-preview')) &&
                !(node.classList && node.classList.contains('local-course-banner-builder-source-preview-layer--disabled'));
        });

    if (options.limitPeers && (targets.length + 1) > localCourseBannerBuilderPreviewGuidePeerLimit) {
        return [];
    }

    if (activeRect) {
        var frameRect = frame.getBoundingClientRect();
        targets = targets.filter(function(target) {
            return !localCourseBannerBuilderPreviewRectsOverlap(
                activeRect,
                localCourseBannerBuilderGetRectInFrame(frameRect, target),
                localCourseBannerBuilderPreviewGuideMargin
            );
        });
    }
    return targets;
}

function localCourseBannerBuilderFindPreviewSnap(frame, activeLayer, rawRect, targetSelector, options) {
    if (!frame || !activeLayer || !rawRect) {
        return {dx: 0, dy: 0};
    }
    var frameRect = frame.getBoundingClientRect();
    var activeGuideRect = localCourseBannerBuilderGetPreviewGuideRawRect(rawRect, activeLayer);
    var best = {};
    best = localCourseBannerBuilderPreviewSnapCandidate(best, 'x', (frameRect.width / 2) - activeGuideRect.centerX, 3);
    best = localCourseBannerBuilderPreviewSnapCandidate(best, 'y', (frameRect.height / 2) - activeGuideRect.centerY, 3);

    localCourseBannerBuilderGetPreviewGuideTargets(frame, activeLayer, targetSelector, options, activeGuideRect).forEach(function(target) {
        var targetRect = localCourseBannerBuilderGetRectInFrame(frameRect, target);
        [
            {value: activeGuideRect.centerX, priority: 2},
            {value: activeGuideRect.left, priority: 1},
            {value: activeGuideRect.right, priority: 1}
        ].forEach(function(activeValue) {
            [
                {value: targetRect.centerX, priority: activeValue.priority === 2 ? 2 : 1},
                {value: targetRect.left, priority: 1},
                {value: targetRect.right, priority: 1}
            ].forEach(function(targetValue) {
                best = localCourseBannerBuilderPreviewSnapCandidate(
                    best,
                    'x',
                    targetValue.value - activeValue.value,
                    Math.min(activeValue.priority, targetValue.priority)
                );
            });
        });
        [
            {value: activeGuideRect.centerY, priority: 2},
            {value: activeGuideRect.top, priority: 1},
            {value: activeGuideRect.bottom, priority: 1}
        ].forEach(function(activeValue) {
            [
                {value: targetRect.centerY, priority: activeValue.priority === 2 ? 2 : 1},
                {value: targetRect.top, priority: 1},
                {value: targetRect.bottom, priority: 1}
            ].forEach(function(targetValue) {
                best = localCourseBannerBuilderPreviewSnapCandidate(
                    best,
                    'y',
                    targetValue.value - activeValue.value,
                    Math.min(activeValue.priority, targetValue.priority)
                );
            });
        });
    });
    return {
        dx: best.x ? best.x.delta : 0,
        dy: best.y ? best.y.delta : 0
    };
}

function localCourseBannerBuilderUpdatePreviewGuides(frame, activeLayer, targetSelector, options) {
    if (!frame || !activeLayer || activeLayer.hidden) {
        localCourseBannerBuilderClearPreviewGuides(frame);
        return;
    }
    options = options || {};
    var guideLayer = localCourseBannerBuilderEnsurePreviewGuideLayer(frame);
    if (!guideLayer) {
        return;
    }
    guideLayer.innerHTML = '';
    guideLayer.hidden = false;

    var frameRect = frame.getBoundingClientRect();
    var activeRect = localCourseBannerBuilderGetRectInFrame(frameRect, activeLayer);
    var seen = {};
    localCourseBannerBuilderMaybeAddPreviewGuide(
        guideLayer, 'vertical', activeRect.centerX, frameRect.width / 2, 'frame local-course-banner-builder-preview-guide--center', seen
    );
    localCourseBannerBuilderMaybeAddPreviewGuide(
        guideLayer, 'horizontal', activeRect.centerY, frameRect.height / 2, 'frame local-course-banner-builder-preview-guide--center', seen
    );

    localCourseBannerBuilderGetPreviewGuideTargets(frame, activeLayer, targetSelector, options, activeRect).forEach(function(target) {
        var targetRect = localCourseBannerBuilderGetRectInFrame(frameRect, target);
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
                localCourseBannerBuilderMaybeAddPreviewGuide(
                    guideLayer,
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
                localCourseBannerBuilderMaybeAddPreviewGuide(
                    guideLayer,
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

    if (!guideLayer.children.length) {
        guideLayer.hidden = true;
    }
}

function localCourseBannerBuilderApplyPreviewDrag(state, event) {
    var deltaX = event.clientX - state.startX;
    var deltaY = event.clientY - state.startY;
    var nextLeftPx = state.startLeftPx + deltaX;
    var nextTopPx = state.startTopPx + deltaY;
    if (localCourseBannerBuilderIsPreviewSnapEnabled(state.form)) {
        var snap = localCourseBannerBuilderFindPreviewSnap(
            state.frame,
            state.layer,
            {
                left: nextLeftPx,
                top: nextTopPx,
                right: nextLeftPx + state.startWidthPx,
                bottom: nextTopPx + state.startHeightPx,
                centerX: nextLeftPx + (state.startWidthPx / 2),
                centerY: nextTopPx + (state.startHeightPx / 2)
            },
            '[data-preview-image-tag=\"1\"]',
            {limitPeers: false}
        );
        nextLeftPx += snap.dx;
        nextTopPx += snap.dy;
    }
    var leftPercent = localCourseBannerBuilderRoundPreviewPercent((nextLeftPx / state.frameWidth) * 100);
    var topPercent = localCourseBannerBuilderRoundPreviewPercent((nextTopPx / state.frameHeight) * 100);
    leftPercent = localCourseBannerBuilderClampPreviewOffset(leftPercent);
    topPercent = localCourseBannerBuilderClampPreviewOffset(topPercent);
    localCourseBannerBuilderRunPreviewInteractionFieldBatch(state, function() {
        localCourseBannerBuilderSetPreviewFieldValue(state.offsetLeftInput, leftPercent);
        localCourseBannerBuilderSetPreviewFieldValue(state.offsetTopInput, topPercent);
    });
    localCourseBannerBuilderUpdatePreviewGuides(
        state.frame,
        state.layer,
        '[data-preview-image-tag=\"1\"]',
        {limitPeers: false}
    );
}

function localCourseBannerBuilderApplyPreviewResize(state, event) {
    var deltaX = event.clientX - state.startX;
    var deltaY = event.clientY - state.startY;
    if (state.layer && state.layer.hasAttribute('data-preview-draft-selection-overlay')) {
        var visualLayer = localCourseBannerBuilderGetDraftSelectionVisualLayer(state.form, state.layer);
        if (visualLayer) {
            state.layer.style.cssText = visualLayer.style.cssText;
            state.layer.style.zIndex = '9000';
        }
    }
    var widthPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startWidthPx + deltaX) / state.frameWidth) * 100);
    var heightPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startHeightPx + deltaY) / state.frameHeight) * 100);
    var nextLeft = null;
    var nextTop = null;

    if (state.mode === 'resize-edge') {
        switch (state.edge) {
            case 'left':
                widthPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startWidthPx - deltaX) / state.frameWidth) * 100);
                nextLeft = localCourseBannerBuilderRoundPreviewPercent(((state.startLeftPx + deltaX) / state.frameWidth) * 100);
                nextLeft = Math.max(-widthPercent, Math.min(state.startLeftPercent + state.startWidthPercent - 1, nextLeft));
                widthPercent = Math.max(1, state.startLeftPercent + state.startWidthPercent - nextLeft);
                break;
            case 'right':
                widthPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startWidthPx + deltaX) / state.frameWidth) * 100);
                break;
            case 'top':
                heightPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startHeightPx - deltaY) / state.frameHeight) * 100);
                nextTop = localCourseBannerBuilderRoundPreviewPercent(((state.startTopPx + deltaY) / state.frameHeight) * 100);
                nextTop = Math.max(-heightPercent, Math.min(state.startTopPercent + state.startHeightPercent - 1, nextTop));
                heightPercent = Math.max(1, state.startTopPercent + state.startHeightPercent - nextTop);
                break;
            case 'bottom':
                heightPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startHeightPx + deltaY) / state.frameHeight) * 100);
                break;
        }
    }

    if (state.keepAspect) {
        var aspectRatio = state.startWidthPx > 0 && state.startHeightPx > 0 ? (state.startWidthPx / state.startHeightPx) : 1;
        widthPercent = Math.max(1, widthPercent);
        heightPercent = (widthPercent * state.frameWidth) / (Math.max(1, aspectRatio) * state.frameHeight);
        var clampedAspect = localCourseBannerBuilderClampPreviewSize(state, widthPercent, heightPercent);
        if (clampedAspect.height !== heightPercent) {
            heightPercent = clampedAspect.height;
            widthPercent = (heightPercent * aspectRatio * state.frameHeight) / state.frameWidth;
        } else {
            widthPercent = clampedAspect.width;
            heightPercent = (widthPercent * state.frameWidth) / (Math.max(1, aspectRatio) * state.frameHeight);
        }
    } else {
        var clamped = localCourseBannerBuilderClampPreviewSize(state, widthPercent, heightPercent);
        widthPercent = clamped.width;
        heightPercent = clamped.height;
    }

    localCourseBannerBuilderRunPreviewInteractionFieldBatch(state, function() {
        if (nextLeft !== null) {
            localCourseBannerBuilderSetPreviewFieldValue(state.offsetLeftInput, nextLeft);
        }
        if (nextTop !== null) {
            localCourseBannerBuilderSetPreviewFieldValue(state.offsetTopInput, nextTop);
        }
        localCourseBannerBuilderSetPreviewFieldValue(state.widthInput, widthPercent);
        localCourseBannerBuilderSetPreviewFieldValue(state.heightInput, heightPercent);
    });
    localCourseBannerBuilderUpdatePreviewGuides(
        state.frame,
        state.layer,
        '[data-preview-image-tag=\"1\"]',
        {limitPeers: false}
    );
}

function localCourseBannerBuilderStopPreviewInteraction() {
    var pendingLayer = !localCourseBannerBuilderPreviewInteraction && localCourseBannerBuilderPendingPreviewInteraction ?
        localCourseBannerBuilderPendingPreviewInteraction.layer :
        null;
    var form = localCourseBannerBuilderPreviewInteraction ?
        localCourseBannerBuilderPreviewInteraction.form :
        localCourseBannerBuilderGetLayerScope(pendingLayer);
    var frame = localCourseBannerBuilderPreviewInteraction ?
        localCourseBannerBuilderPreviewInteraction.frame :
        (pendingLayer ? pendingLayer.closest('[data-banner-preview-frame=\"1\"]') : null);
    if (form) {
        var currentLayer = pendingLayer || form.querySelector('[data-preview-current-layer=\"1\"]');
        if (currentLayer) {
            currentLayer.removeAttribute('data-preview-active-edge');
        }
    }
    localCourseBannerBuilderPreviewInteraction = null;
    localCourseBannerBuilderPendingPreviewInteraction = null;
    if (form) {
        localCourseBannerBuilderSaveActiveDraftPreviewState(form);
        localCourseBannerBuilderSyncModalPreviewActionButtons(form);
    }
    localCourseBannerBuilderClearPreviewGuides(frame);
}

function localCourseBannerBuilderHandlePreviewPointerMove(event) {
    if (!localCourseBannerBuilderPreviewInteraction && localCourseBannerBuilderPendingPreviewInteraction) {
        var pending = localCourseBannerBuilderPendingPreviewInteraction;
        var deltaX = event.clientX - pending.startX;
        var deltaY = event.clientY - pending.startY;
        var threshold = pending.mode === 'resize' || pending.mode === 'resize-edge' ? 4 : 8;
        if (Math.abs(deltaX) >= threshold || Math.abs(deltaY) >= threshold) {
            localCourseBannerBuilderPendingPreviewInteraction = null;
            localCourseBannerBuilderStartPreviewInteraction(pending.event, pending.mode, pending.layer);
        }
    }
    var state = localCourseBannerBuilderPreviewInteraction;
    if (!state) {
        return;
    }
    event.preventDefault();
    if (state.mode === 'resize' || state.mode === 'resize-edge') {
        localCourseBannerBuilderApplyPreviewResize(state, event);
        return;
    }
    localCourseBannerBuilderApplyPreviewDrag(state, event);
}

function localCourseBannerBuilderStartPreviewInteraction(event, mode, layer) {
    if (!layer || event.button !== 0) {
        return;
    }
    var form = localCourseBannerBuilderGetLayerScope(layer);
    var frame = layer.closest('[data-banner-preview-frame=\"1\"]');
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    var widthInput = form ? form.querySelector('#id_customwidthpercent') : null;
    var heightInput = form ? form.querySelector('#id_customheightpercent') : null;
    var keepAspectInput = form ? form.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]') : null;
    var offsetTopInput = form ? form.querySelector('#id_offsettoppercent') : null;
    var offsetLeftInput = form ? form.querySelector('#id_offsetleftpercent') : null;
    if (!form || !frame || !image || !widthInput || !heightInput || !offsetTopInput || !offsetLeftInput) {
        return;
    }
    form.dataset.previewInteractionStarting = '1';
    localCourseBannerBuilderSyncModalImageOpacityInputFromLayer(form);
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var previewRoot = layer.closest('[data-banner-preview=\"1\"]');
    var currentFitMode = layer.getAttribute('data-preview-fitmode') ||
        (fitOverride && fitOverride.value ? fitOverride.value : '') ||
        (previewRoot ? (previewRoot.getAttribute('data-default-fitmode') || 'bannerfit') : 'bannerfit');
    var prepared = mode === 'drag' ?
        localCourseBannerBuilderEnsurePreviewDragMode(form, layer, frame) :
        localCourseBannerBuilderEnsurePreviewCustomMode(form, layer, frame);
    if (!prepared) {
        form.dataset.previewInteractionStarting = '0';
        return;
    }

    window.setTimeout(function() {
        var refreshedFrame = layer.closest('[data-banner-preview-frame=\"1\"]');
        var frameRect = refreshedFrame ? refreshedFrame.getBoundingClientRect() : null;
        var visualLayer = localCourseBannerBuilderGetDraftSelectionVisualLayer(form, layer);
        var layerRect = (visualLayer || layer).getBoundingClientRect();
        if (!frameRect || !frameRect.width || !frameRect.height || layer.hidden) {
            form.dataset.previewInteractionStarting = '0';
            return;
        }
        localCourseBannerBuilderPreviewInteraction = {
            mode: mode,
            edge: layer.getAttribute('data-preview-active-edge') || '',
            form: form,
            frame: refreshedFrame,
            layer: layer,
            frameWidth: frameRect.width,
            frameHeight: frameRect.height,
            startX: event.clientX,
            startY: event.clientY,
            startLeftPx: layerRect.left - frameRect.left,
            startTopPx: layerRect.top - frameRect.top,
            startWidthPx: layerRect.width,
            startHeightPx: layerRect.height,
            widthPercent: localCourseBannerBuilderRoundPreviewPercent((layerRect.width / frameRect.width) * 100),
            heightPercent: localCourseBannerBuilderRoundPreviewPercent((layerRect.height / frameRect.height) * 100),
            startWidthPercent: localCourseBannerBuilderRoundPreviewPercent((layerRect.width / frameRect.width) * 100),
            startHeightPercent: localCourseBannerBuilderRoundPreviewPercent((layerRect.height / frameRect.height) * 100),
            leftPercent: localCourseBannerBuilderRoundPreviewPercent(((layerRect.left - frameRect.left) / frameRect.width) * 100),
            topPercent: localCourseBannerBuilderRoundPreviewPercent(((layerRect.top - frameRect.top) / frameRect.height) * 100),
            startLeftPercent: localCourseBannerBuilderRoundPreviewPercent(((layerRect.left - frameRect.left) / frameRect.width) * 100),
            startTopPercent: localCourseBannerBuilderRoundPreviewPercent(((layerRect.top - frameRect.top) / frameRect.height) * 100),
            widthInput: widthInput,
            heightInput: heightInput,
            offsetTopInput: offsetTopInput,
            offsetLeftInput: offsetLeftInput,
            keepAspect: !!(keepAspectInput && keepAspectInput.checked)
        };
        form.dataset.previewInteractionStarting = '0';
    }, 0);
}

function localCourseBannerBuilderGetLayerPreviewFrame(form) {
    if (!form) {
        return null;
    }
    var previewRoot = form.querySelector('[data-layer-banner-preview=\"1\"]');
    return previewRoot ? previewRoot.querySelector('[data-banner-preview-frame=\"1\"]') : null;
}

function localCourseBannerBuilderIsLayerPreviewImageLayerVisible(layer) {
    return !!(layer && !layer.hidden && layer.querySelector('[data-preview-image-tag=\"1\"]'));
}

function localCourseBannerBuilderGetCurrentLayerPreviewResizeHandleAtPoint(form, clientX, clientY) {
    var currentLayer = form ? form.querySelector('[data-preview-current-layer=\"1\"]') : null;
    return localCourseBannerBuilderGetPreviewResizeHandleAtPoint(currentLayer, clientX, clientY);
}

function localCourseBannerBuilderGetLayerPreviewZIndex(layer) {
    if (!layer) {
        return 0;
    }
    var inlineZIndex = parseInt(layer.getAttribute('data-preview-zindex') || '', 10);
    if (!isNaN(inlineZIndex)) {
        return inlineZIndex;
    }
    var styleZIndex = parseInt(layer.style && layer.style.zIndex ? layer.style.zIndex : '', 10);
    if (!isNaN(styleZIndex)) {
        return styleZIndex;
    }
    var computedZIndex = window.getComputedStyle ? parseInt(window.getComputedStyle(layer).zIndex || '', 10) : NaN;
    return isNaN(computedZIndex) ? 0 : computedZIndex;
}

function localCourseBannerBuilderGetTopLayerPreviewImageLayerAtPoint(form, clientX, clientY) {
    var frame = localCourseBannerBuilderGetLayerPreviewFrame(form);
    if (!form || !frame) {
        return null;
    }
    var frameRect = frame.getBoundingClientRect();
    if (clientX < frameRect.left || clientX > frameRect.right || clientY < frameRect.top || clientY > frameRect.bottom) {
        return null;
    }

    var currentLayer = form.querySelector('[data-preview-current-layer=\"1\"]');
    if (localCourseBannerBuilderIsLayerPreviewImageLayerVisible(currentLayer) &&
            localCourseBannerBuilderIsPointOnPreviewLayerSelectionChrome(currentLayer, clientX, clientY)) {
        return currentLayer;
    }

    if (typeof document.elementsFromPoint === 'function') {
        var elements = document.elementsFromPoint(clientX, clientY);
        var transparentFallback = null;
        for (var i = 0; i < elements.length; i++) {
            var layer = elements[i].closest ?
                elements[i].closest('[data-preview-current-layer=\"1\"], [data-preview-draft-layer=\"1\"]') :
                null;
            if (layer && form.contains(layer) && localCourseBannerBuilderIsLayerPreviewImageLayerVisible(layer)) {
                if (localCourseBannerBuilderIsSourcePreviewLayerOpaqueAtPoint(layer, clientX, clientY)) {
                    return layer;
                }
                if (!transparentFallback) {
                    transparentFallback = layer;
                }
            }
        }
        if (transparentFallback) {
            return transparentFallback;
        }
    }

    var allLayers = Array.prototype.slice.call(
        frame.querySelectorAll('[data-preview-current-layer=\"1\"], [data-preview-draft-layer=\"1\"]')
    ).filter(localCourseBannerBuilderIsLayerPreviewImageLayerVisible);
    allLayers.sort(function(a, b) {
        return localCourseBannerBuilderGetLayerPreviewZIndex(b) - localCourseBannerBuilderGetLayerPreviewZIndex(a);
    });
    for (var j = 0; j < allLayers.length; j++) {
        if (localCourseBannerBuilderIsSourcePreviewLayerOpaqueAtPoint(allLayers[j], clientX, clientY)) {
            return allLayers[j];
        }
    }
    return null;
}

function localCourseBannerBuilderBindLayerPreviewEvents(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form) {
        return;
    }
    if (!form.dataset.previewEventsBound) {
        var syncPreview = function(event) {
            var target = event && event.target ? event.target : null;
            if (form.dataset.previewApplyingInteraction === '1') {
                return;
            }
            if (target && target.closest && target.closest('[data-preview-context-toggle=\"1\"]')) {
                localCourseBannerBuilderSyncContextPreviewVisibility(form);
                localCourseBannerBuilderSyncModalPreviewActionButtons(form);
                return;
            }
            if (target && target.closest && target.closest('[data-preview-opacity-panel=\"modal\"]')) {
                localCourseBannerBuilderApplyModalImageOpacity(form, target.value || '100');
                return;
            }
            if (target && target.matches && target.matches('#id_imageopacity')) {
                localCourseBannerBuilderApplyModalImageOpacity(form, target.value || '100');
                return;
            }
            if (target && target.matches && target.matches('#id_isenabled')) {
                localCourseBannerBuilderSyncBinaryOptionButton(target);
                return;
            }
            if (!(target && target.matches && target.matches('#id_dynamicimagesizeenabled'))) {
                form.dataset.previewUserChanged = '1';
            }
            localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
            localCourseBannerBuilderSyncLayerBannerPreview(form);
        };
        form.addEventListener('input', syncPreview, true);
        form.addEventListener('change', syncPreview, true);
        form.addEventListener('pointerdown', function(event) {
            if (event.target.closest('[data-action=\"local-course-banner-builder-toggle-preview-aspect-lock\"]')) {
                return;
            }
            var resizeHandle = localCourseBannerBuilderGetCurrentLayerPreviewResizeHandleAtPoint(form, event.clientX, event.clientY) ||
                event.target.closest('[data-preview-resize-handle=\"1\"]');
            var currentLayer = resizeHandle ?
                resizeHandle.closest('[data-preview-current-layer=\"1\"]') :
                localCourseBannerBuilderGetTopLayerPreviewImageLayerAtPoint(form, event.clientX, event.clientY);
            if (!currentLayer || currentLayer.hidden) {
                return;
            }
            if (currentLayer.hasAttribute('data-preview-draft-layer') &&
                    !currentLayer.hasAttribute('data-preview-current-layer')) {
                event.preventDefault();
                event.stopPropagation();
                localCourseBannerBuilderSelectDraftPreviewLayer(form, currentLayer.getAttribute('data-draft-index') || '0');
                return;
            }
            if (resizeHandle) {
                event.preventDefault();
                event.stopPropagation();
                currentLayer.setAttribute('data-preview-active-edge', resizeHandle.getAttribute('data-preview-resize-edge') || '');
                localCourseBannerBuilderPendingPreviewInteraction = {
                    event: {
                        button: event.button,
                        clientX: event.clientX,
                        clientY: event.clientY
                    },
                    mode: resizeHandle.getAttribute('data-preview-resize-mode') === 'edge' ? 'resize-edge' : 'resize',
                    layer: currentLayer,
                    startX: event.clientX,
                    startY: event.clientY
                };
                return;
            }
            var clickedImage = event.target.closest('[data-preview-image-tag=\"1\"]');
            if (currentLayer.hasAttribute('data-preview-current-layer') && currentLayer.contains(event.target)) {
                event.preventDefault();
                event.stopPropagation();
                localCourseBannerBuilderPendingPreviewInteraction = {
                    event: {
                        button: event.button,
                        clientX: event.clientX,
                        clientY: event.clientY
                    },
                    mode: 'drag',
                    layer: currentLayer,
                    startX: event.clientX,
                    startY: event.clientY
                };
                return;
            }
            if ((clickedImage && currentLayer.contains(clickedImage)) || event.target === currentLayer ||
                    localCourseBannerBuilderIsPointOnPreviewLayerSelectionChrome(currentLayer, event.clientX, event.clientY)) {
                event.preventDefault();
                localCourseBannerBuilderPendingPreviewInteraction = {
                    event: {
                        button: event.button,
                        clientX: event.clientX,
                        clientY: event.clientY
                    },
                    mode: 'drag',
                    layer: currentLayer,
                    startX: event.clientX,
                    startY: event.clientY
                };
            }
        });
        form.dataset.previewEventsBound = '1';
    }

    var filemanager = form.querySelector('#fitem_id_bannerimage_filemanager');
    if (filemanager && !filemanager.dataset.previewObserverBound) {
        var observer = new MutationObserver(function() {
            if (filemanager.dataset.previewObserverQueued === '1') {
                return;
            }
            filemanager.dataset.previewObserverQueued = '1';
            window.setTimeout(function() {
                filemanager.dataset.previewObserverQueued = '0';
                localCourseBannerBuilderSyncLayerInputModes(form);
                localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
                localCourseBannerBuilderSyncLayerBannerPreview(form);
            }, localCourseBannerBuilderIsDraftFilemanagerBusy(form) ? 500 : 120);
        });
        observer.observe(filemanager, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['href', 'src', 'class', 'style', 'hidden', 'aria-hidden', 'data-preview-deleted']
        });
        filemanager.dataset.previewObserverBound = '1';
    }

    localCourseBannerBuilderSyncLayerBannerPreview(form);
}

var localCourseBannerBuilderSourcePreviewInteraction = null;
var localCourseBannerBuilderSuppressSourcePreviewClickUntil = 0;

function localCourseBannerBuilderGetSourceVisualEditorRoots(scope) {
    var root = scope || document;
    if (root.nodeType === 1 && root.matches && root.matches('[data-source-visual-editor=\"1\"]')) {
        return [root];
    }
    return Array.prototype.slice.call(root.querySelectorAll ? root.querySelectorAll('[data-source-visual-editor=\"1\"]') : []);
}

function localCourseBannerBuilderIsSourcePreviewReadonly(root) {
    return !!(root && root.getAttribute && root.getAttribute('data-source-preview-readonly') === '1');
}

function localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer) {
    return !!(layer && layer.getAttribute('data-preview-enabled') !== '0' &&
        !(layer.classList && layer.classList.contains('local-course-banner-builder-source-preview-layer--disabled')));
}

function localCourseBannerBuilderGetFirstEnabledSourcePreviewLayer(root) {
    if (!root) {
        return null;
    }
    return Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]'))
        .find(function(layer) {
            return localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer);
        }) || null;
}

function localCourseBannerBuilderGetSelectedSourcePreviewLayer(root) {
    if (!root) {
        return null;
    }
    var selected = root.querySelector('.local-course-banner-builder-source-preview-layer--selected');
    return localCourseBannerBuilderIsSourcePreviewLayerEnabled(selected) ?
        selected :
        localCourseBannerBuilderGetFirstEnabledSourcePreviewLayer(root);
}

function localCourseBannerBuilderGetSelectedSourcePreviewResizeHandleAtPoint(root, clientX, clientY) {
    if (!root) {
        return null;
    }
    var selected = root.querySelector('.local-course-banner-builder-source-preview-layer--selected');
    if (!localCourseBannerBuilderIsSourcePreviewLayerEnabled(selected) ||
            selected.getAttribute('data-source-preview-editable') !== '1') {
        return null;
    }
    return localCourseBannerBuilderGetPreviewResizeHandleAtPoint(selected, clientX, clientY);
}

function localCourseBannerBuilderGetPreviewResizeHandleAtPoint(layer, clientX, clientY) {
    if (!layer || layer.hidden) {
        return null;
    }
    var handles = Array.prototype.slice.call(layer.querySelectorAll('[data-preview-resize-handle=\"1\"]'));
    for (var i = 0; i < handles.length; i++) {
        var rect = handles[i].getBoundingClientRect();
        var tolerance = 6;
        if (rect.width <= 0 || rect.height <= 0) {
            continue;
        }
        if (
            clientX >= rect.left - tolerance &&
            clientX <= rect.right + tolerance &&
            clientY >= rect.top - tolerance &&
            clientY <= rect.bottom + tolerance
        ) {
            return handles[i];
        }
    }
    return null;
}

function localCourseBannerBuilderIsPointOnPreviewLayerSelectionChrome(layer, clientX, clientY) {
    return !!localCourseBannerBuilderGetPreviewResizeHandleAtPoint(layer, clientX, clientY);
}

function localCourseBannerBuilderSuppressNextSourcePreviewClick() {
    localCourseBannerBuilderSuppressSourcePreviewClickUntil = Date.now() + 500;
}

function localCourseBannerBuilderGetSourcePreviewLayerState(layer) {
    if (!layer) {
        return null;
    }
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    return {
        id: String(layer.getAttribute('data-source-preview-layer-id') || ''),
        isenabled: layer.getAttribute('data-preview-enabled') !== '0',
        fitmodeoverride: layer.hasAttribute('data-preview-fitmode') ? layer.getAttribute('data-preview-fitmode') : 'custom',
        positionanchor: layer.getAttribute('data-preview-anchor') || 'center',
        customwidthpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-width') || '100', 100),
        customheightpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-height') || '100', 100),
        customsizekeepaspect: layer.getAttribute('data-preview-keep-aspect') === '1',
        dynamicimagesizeenabled: layer.getAttribute('data-preview-dynamic-image') === '1',
        imageopacity: localCourseBannerBuilderClampPercent(layer.getAttribute('data-preview-image-opacity') || '100', 100),
        imagecropenabled: layer.getAttribute('data-preview-crop-enabled') === '1',
        imagecropleftpercent: localCourseBannerBuilderClampPercent(layer.getAttribute('data-preview-crop-left') || '0', 0),
        imagecroptoppercent: localCourseBannerBuilderClampPercent(layer.getAttribute('data-preview-crop-top') || '0', 0),
        imagecropwidthpercent: localCourseBannerBuilderClampCropSize(layer.getAttribute('data-preview-crop-width') || '100'),
        imagecropheightpercent: localCourseBannerBuilderClampCropSize(layer.getAttribute('data-preview-crop-height') || '100'),
        offsettoppercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-top') || '0', 0),
        offsetrightpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-right') || '0', 0),
        offsetbottompercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-bottom') || '0', 0),
        offsetleftpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-left') || '0', 0),
        imagewidth: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-width') || '0', 0),
        imageheight: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-height') || '0', 0),
        url: layer.getAttribute('data-preview-current-url') || (image ? (image.getAttribute('src') || '') : ''),
        zindex: parseInt(layer.getAttribute('data-preview-zindex') || '1', 10) || 1
    };
}

var localCourseBannerBuilderPreviewImageAlphaCache = new Map();

function localCourseBannerBuilderGetPreviewImageAlphaCacheKey(image) {
    if (!image) {
        return '';
    }
    return (image.currentSrc || image.getAttribute('src') || '') + '|' +
        String(image.naturalWidth || 0) + 'x' + String(image.naturalHeight || 0);
}

function localCourseBannerBuilderGetPreviewImageAlphaData(image) {
    if (!image || !image.naturalWidth || !image.naturalHeight) {
        return null;
    }
    var cacheKey = localCourseBannerBuilderGetPreviewImageAlphaCacheKey(image);
    if (!cacheKey) {
        return null;
    }
    if (localCourseBannerBuilderPreviewImageAlphaCache.has(cacheKey)) {
        return localCourseBannerBuilderPreviewImageAlphaCache.get(cacheKey);
    }
    try {
        var canvas = document.createElement('canvas');
        canvas.width = image.naturalWidth;
        canvas.height = image.naturalHeight;
        var context = canvas.getContext('2d', {willReadFrequently: true});
        if (!context) {
            return null;
        }
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        var data = context.getImageData(0, 0, canvas.width, canvas.height).data;
        var payload = {
            width: canvas.width,
            height: canvas.height,
            data: data
        };
        localCourseBannerBuilderPreviewImageAlphaCache.set(cacheKey, payload);
        return payload;
    } catch (error) {
        return null;
    }
}

function localCourseBannerBuilderIsSourcePreviewLayerOpaqueAtPoint(layer, clientX, clientY) {
    if (!layer) {
        return false;
    }
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    if (!image) {
        return true;
    }
    var layerRect = layer.getBoundingClientRect();
    if (clientX < layerRect.left || clientX > layerRect.right || clientY < layerRect.top || clientY > layerRect.bottom) {
        return false;
    }
    var alphaData = localCourseBannerBuilderGetPreviewImageAlphaData(image);
    if (!alphaData) {
        return true;
    }

    var localX = clientX - layerRect.left;
    var localY = clientY - layerRect.top;
    var pixelX = Math.max(0, Math.min(alphaData.width - 1, Math.floor((localX / Math.max(1, layerRect.width)) * alphaData.width)));
    var pixelY = Math.max(0, Math.min(alphaData.height - 1, Math.floor((localY / Math.max(1, layerRect.height)) * alphaData.height)));
    var sampleRadius = Math.max(
        1,
        Math.ceil(Math.max(alphaData.width / Math.max(1, layerRect.width), alphaData.height / Math.max(1, layerRect.height)) * 3)
    );
    var alphaThreshold = 2;
    for (var y = Math.max(0, pixelY - sampleRadius); y <= Math.min(alphaData.height - 1, pixelY + sampleRadius); y++) {
        for (var x = Math.max(0, pixelX - sampleRadius); x <= Math.min(alphaData.width - 1, pixelX + sampleRadius); x++) {
            var alphaIndex = ((y * alphaData.width) + x) * 4 + 3;
            if ((alphaData.data[alphaIndex] || 0) > alphaThreshold) {
                return true;
            }
        }
    }
    return false;
}

function localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint(root, clientX, clientY) {
    if (!root || typeof document.elementsFromPoint !== 'function') {
        return localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    }
    var frame = root.querySelector('[data-source-preview-frame=\"1\"]');
    if (!frame) {
        return null;
    }
    var frameRect = frame.getBoundingClientRect();
    if (clientX < frameRect.left || clientX > frameRect.right || clientY < frameRect.top || clientY > frameRect.bottom) {
        return null;
    }

    var elements = document.elementsFromPoint(clientX, clientY);
    var transparentFallback = null;
    for (var i = 0; i < elements.length; i++) {
        var layer = elements[i].closest ? elements[i].closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null;
        if (layer && root.contains(layer) && localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer) &&
                !localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer)) {
            if (localCourseBannerBuilderIsSourcePreviewLayerOpaqueAtPoint(layer, clientX, clientY)) {
                return layer;
            }
            if (!transparentFallback) {
                transparentFallback = layer;
            }
        }
    }

    var allLayers = Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]'))
        .filter(function(layer) {
            return localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer) &&
                !localCourseBannerBuilderIsSourcePreviewLayerHiddenInPreview(layer);
        });
    allLayers.sort(function(a, b) {
        var aZ = parseInt(a.getAttribute('data-preview-zindex') || '0', 10) || 0;
        var bZ = parseInt(b.getAttribute('data-preview-zindex') || '0', 10) || 0;
        return bZ - aZ;
    });
    for (var j = 0; j < allLayers.length; j++) {
        var candidate = allLayers[j];
        if (localCourseBannerBuilderIsSourcePreviewLayerOpaqueAtPoint(candidate, clientX, clientY)) {
            return candidate;
        }
    }

    return transparentFallback;
}

function localCourseBannerBuilderGetSourcePreviewEventLayer(root, event) {
    if (!root || !event) {
        return null;
    }
    var layer = localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint(root, event.clientX, event.clientY);
    if (layer) {
        return layer;
    }
    if (typeof document.elementsFromPoint !== 'function' && event.target && event.target.closest) {
        return event.target.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]');
    }
    return null;
}

function localCourseBannerBuilderEnsureSourcePreviewCustomMode(root, layer, frame) {
    if (!root || !layer || !frame) {
        return false;
    }
    var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
    if (!state) {
        return false;
    }
    var frameRect = frame.getBoundingClientRect();
    var layerRect = layer.getBoundingClientRect();
    if (!frameRect.width || !frameRect.height || !layerRect.width || !layerRect.height) {
        return false;
    }

    var previousFitMode = state.fitmodeoverride || 'custom';
    state.fitmodeoverride = 'custom';
    state.positionanchor = 'top-left';
    state.offsetrightpercent = 0;
    state.offsetbottompercent = 0;
    if (previousFitMode === 'original' || previousFitMode === 'cover') {
        state.customsizekeepaspect = true;
    }
    state.customwidthpercent = localCourseBannerBuilderRoundPreviewPercent((layerRect.width / frameRect.width) * 100);
    state.customheightpercent = localCourseBannerBuilderRoundPreviewPercent((layerRect.height / frameRect.height) * 100);
    state.offsetleftpercent = localCourseBannerBuilderRoundPreviewPercent(((layerRect.left - frameRect.left) / frameRect.width) * 100);
    state.offsettoppercent = localCourseBannerBuilderRoundPreviewPercent(((layerRect.top - frameRect.top) / frameRect.height) * 100);
    localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
    localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
    localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
    return true;
}

function localCourseBannerBuilderIsPreviewNudgeEvent(event) {
    if (!event || !Object.prototype.hasOwnProperty.call({
            ArrowLeft: true,
            ArrowRight: true,
            ArrowUp: true,
            ArrowDown: true
        }, event.key)) {
        return false;
    }
    if (event.altKey || event.ctrlKey || event.metaKey) {
        return false;
    }
    var target = event.target;
    if (!target || !target.closest) {
        return true;
    }
    return !target.closest(
        'input, textarea, select, button, a, [contenteditable=\"true\"], .filemanager, .fp-content'
    );
}

function localCourseBannerBuilderIsPreviewNudgeVisible(node) {
    return !!(node && node.getClientRects && node.getClientRects().length &&
        window.getComputedStyle(node).visibility !== 'hidden');
}

function localCourseBannerBuilderGetActivePreviewModal() {
    var modals = Array.prototype.slice.call(document.querySelectorAll('.modal'));
    return modals.reverse().find(function(modal) {
        return localCourseBannerBuilderIsPreviewNudgeVisible(modal) && (
            modal.classList.contains('show') ||
            modal.classList.contains('in') ||
            modal.getAttribute('aria-modal') === 'true' ||
            modal.style.display === 'block'
        );
    }) || null;
}

function localCourseBannerBuilderFlashPreviewGuides(frame, layer, targetSelector, options) {
    if (!frame || !layer) {
        return;
    }
    localCourseBannerBuilderUpdatePreviewGuides(frame, layer, targetSelector, options);
    window.clearTimeout(frame.localCourseBannerBuilderNudgeGuideTimer);
    frame.localCourseBannerBuilderNudgeGuideTimer = window.setTimeout(function() {
        localCourseBannerBuilderClearPreviewGuides(frame);
    }, 450);
}

function localCourseBannerBuilderNudgeSourcePreviewLayer(root, deltaX, deltaY, pushHistory) {
    if (!root || localCourseBannerBuilderIsSourcePreviewReadonly(root)) {
        return false;
    }
    var frame = root.querySelector('[data-source-preview-frame=\"1\"]');
    var layer = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    if (!frame || !layer || layer.getAttribute('data-source-preview-editable') !== '1') {
        return false;
    }
    var frameRect = frame.getBoundingClientRect();
    if (!frameRect.width || !frameRect.height) {
        return false;
    }
    if (pushHistory) {
        localCourseBannerBuilderPushSourcePreviewHistory(root);
    }
    var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
    if (!state) {
        return false;
    }
    if (state.fitmodeoverride !== 'custom' || state.positionanchor !== 'top-left') {
        localCourseBannerBuilderEnsureSourcePreviewCustomMode(root, layer, frame);
        state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
        if (!state) {
            return false;
        }
    }
    state.fitmodeoverride = 'custom';
    state.positionanchor = 'top-left';
    state.offsetrightpercent = 0;
    state.offsetbottompercent = 0;
    state.offsetleftpercent = localCourseBannerBuilderClampPreviewOffset(
        state.offsetleftpercent + ((deltaX / frameRect.width) * 100)
    );
    state.offsettoppercent = localCourseBannerBuilderClampPreviewOffset(
        state.offsettoppercent + ((deltaY / frameRect.height) * 100)
    );
    state.offsetleftpercent = localCourseBannerBuilderRoundPreviewPercent(state.offsetleftpercent);
    state.offsettoppercent = localCourseBannerBuilderRoundPreviewPercent(state.offsettoppercent);
    localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
    localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
    localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
    localCourseBannerBuilderFlashPreviewGuides(
        frame,
        layer,
        '[data-source-preview-layer=\"1\"]',
        {limitPeers: true}
    );
    return true;
}

function localCourseBannerBuilderNudgeLayerModalPreview(form, deltaX, deltaY, pushHistory) {
    if (!form || !form.querySelector('[data-layer-banner-preview=\"1\"]')) {
        return false;
    }
    var layer = localCourseBannerBuilderGetLayerFormPreviewImage(form);
    var frame = layer ? layer.closest('[data-banner-preview-frame=\"1\"]') : null;
    if (!layer || !frame || layer.hidden || !layer.querySelector('[data-preview-image-tag=\"1\"]')) {
        return false;
    }
    var frameRect = frame.getBoundingClientRect();
    if (!frameRect.width || !frameRect.height) {
        return false;
    }
    if (pushHistory) {
        localCourseBannerBuilderPushModalPreviewHistory(form);
    }
    var leftInput = form.querySelector('#id_offsetleftpercent');
    var topInput = form.querySelector('#id_offsettoppercent');
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    if (!leftInput || !topInput || !fitOverride || !anchorInput) {
        return false;
    }
    if (fitOverride.value !== 'custom' || anchorInput.value !== 'top-left') {
        localCourseBannerBuilderEnsurePreviewDragMode(form, layer, frame);
    }
    var left = localCourseBannerBuilderNormaliseNumericValue(leftInput.value || '0', 0);
    var top = localCourseBannerBuilderNormaliseNumericValue(topInput.value || '0', 0);
    localCourseBannerBuilderSetPreviewFieldValue(
        leftInput,
        localCourseBannerBuilderClampPreviewOffset(left + ((deltaX / frameRect.width) * 100))
    );
    localCourseBannerBuilderSetPreviewFieldValue(
        topInput,
        localCourseBannerBuilderClampPreviewOffset(top + ((deltaY / frameRect.height) * 100))
    );
    form.dataset.previewUserChanged = '1';
    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
    localCourseBannerBuilderFlashPreviewGuides(
        frame,
        layer,
        '[data-preview-image-tag=\"1\"]',
        {limitPeers: false}
    );
    return true;
}

function localCourseBannerBuilderNudgeTitlePreview(form, deltaX, deltaY, pushHistory) {
    if (!form || !form.matches('[data-banner-title-editor=\"1\"]')) {
        return false;
    }
    var frame = form.querySelector('[data-title-preview-frame=\"1\"]');
    var title = form.querySelector(
        '[data-title-preview-text=\"1\"].local-course-banner-builder-slideshow-preview-draggable--selected'
    );
    if (!frame || !title || title.hidden) {
        return false;
    }
    var frameRect = frame.getBoundingClientRect();
    if (!frameRect.width || !frameRect.height) {
        return false;
    }
    if (pushHistory) {
        localCourseBannerBuilderPushTitleUndo(form);
    }
    var nextX = Math.max(0, Math.min(100,
        (parseFloat(localCourseBannerBuilderReadTitleValue(form, 'x', '50')) || 50) +
            ((deltaX / frameRect.width) * 100)
    ));
    var nextY = Math.max(0, Math.min(100,
        (parseFloat(localCourseBannerBuilderReadTitleValue(form, 'y', '50')) || 50) +
            ((deltaY / frameRect.height) * 100)
    ));
    localCourseBannerBuilderClearPreviewGuides(frame);
    nextX = localCourseBannerBuilderSnapTitlePercent(form, nextX, 'x', frame);
    nextY = localCourseBannerBuilderSnapTitlePercent(form, nextY, 'y', frame);
    localCourseBannerBuilderSetTitleField(form, 'x', nextX.toFixed(1));
    localCourseBannerBuilderSetTitleField(form, 'y', nextY.toFixed(1));
    title.classList.add('local-course-banner-builder-slideshow-preview-draggable--selected');
    localCourseBannerBuilderUpdateTitlePreview(form);
    localCourseBannerBuilderUpdateTitleGuides(form, frame);
    window.clearTimeout(frame.localCourseBannerBuilderTitleNudgeGuideTimer);
    frame.localCourseBannerBuilderTitleNudgeGuideTimer = window.setTimeout(function() {
        localCourseBannerBuilderClearPreviewGuides(frame);
    }, 450);
    return true;
}

function localCourseBannerBuilderNudgeActivePreviewSelection(deltaX, deltaY, pushHistory) {
    var active = document.activeElement;
    var activeForm = active && active.closest ? active.closest('form') : null;
    if (activeForm && localCourseBannerBuilderNudgeTitlePreview(activeForm, deltaX, deltaY, pushHistory)) {
        return true;
    }
    if (activeForm && localCourseBannerBuilderNudgeLayerModalPreview(activeForm, deltaX, deltaY, pushHistory)) {
        return true;
    }

    var modal = localCourseBannerBuilderGetActivePreviewModal();
    if (modal) {
        var titleForm = modal.querySelector('[data-banner-title-editor=\"1\"]');
        if (localCourseBannerBuilderNudgeTitlePreview(titleForm, deltaX, deltaY, pushHistory)) {
            return true;
        }
        var layerForm = modal.querySelector('form.mform');
        if (localCourseBannerBuilderNudgeLayerModalPreview(layerForm, deltaX, deltaY, pushHistory)) {
            return true;
        }
    }

    var activeRoot = active && active.closest ? active.closest('[data-source-visual-editor=\"1\"]') : null;
    if (activeRoot && localCourseBannerBuilderNudgeSourcePreviewLayer(activeRoot, deltaX, deltaY, pushHistory)) {
        return true;
    }
    return Array.prototype.slice.call(document.querySelectorAll('[data-source-visual-editor=\"1\"]')).some(function(root) {
        return localCourseBannerBuilderIsPreviewNudgeVisible(root) &&
            root.querySelector('.local-course-banner-builder-source-preview-layer--selected') &&
            localCourseBannerBuilderNudgeSourcePreviewLayer(root, deltaX, deltaY, pushHistory);
    });
}

function localCourseBannerBuilderGetSourcePreviewCustomInteractionState(layer, frame) {
    if (!layer || !frame) {
        return null;
    }
    var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
    if (!state) {
        return null;
    }
    var frameRect = frame.getBoundingClientRect();
    var layerRect = layer.getBoundingClientRect();
    if (!frameRect.width || !frameRect.height || !layerRect.width || !layerRect.height) {
        return state;
    }

    var previousFitMode = state.fitmodeoverride || 'custom';
    var nextState = Object.assign({}, state, {
        fitmodeoverride: 'custom',
        positionanchor: 'top-left',
        offsetrightpercent: 0,
        offsetbottompercent: 0,
        customwidthpercent: localCourseBannerBuilderRoundPreviewPercent((layerRect.width / frameRect.width) * 100),
        customheightpercent: localCourseBannerBuilderRoundPreviewPercent((layerRect.height / frameRect.height) * 100),
        offsetleftpercent: localCourseBannerBuilderRoundPreviewPercent(((layerRect.left - frameRect.left) / frameRect.width) * 100),
        offsettoppercent: localCourseBannerBuilderRoundPreviewPercent(((layerRect.top - frameRect.top) / frameRect.height) * 100)
    });
    if (previousFitMode === 'original' || previousFitMode === 'cover') {
        nextState.customsizekeepaspect = true;
    }
    return nextState;
}

function localCourseBannerBuilderSetSourcePreviewLayerState(layer, state) {
    if (!layer || !state) {
        return;
    }
    layer.setAttribute('data-preview-fitmode',
        typeof state.fitmodeoverride === 'string' ? state.fitmodeoverride : 'custom');
    if (Object.prototype.hasOwnProperty.call(state, 'isenabled')) {
        layer.setAttribute('data-preview-enabled', state.isenabled ? '1' : '0');
        layer.classList.toggle('local-course-banner-builder-source-preview-layer--disabled', !state.isenabled);
    }
    layer.setAttribute('data-preview-anchor', state.positionanchor || 'center');
    layer.setAttribute('data-preview-custom-width', String(state.customwidthpercent ?? 100));
    layer.setAttribute('data-preview-custom-height', String(state.customheightpercent ?? 100));
    layer.setAttribute('data-preview-keep-aspect', state.customsizekeepaspect ? '1' : '0');
    layer.setAttribute('data-preview-dynamic-image', state.dynamicimagesizeenabled ? '1' : '0');
    layer.setAttribute('data-preview-image-opacity', String(state.imageopacity ?? 100));
    layer.setAttribute('data-preview-crop-enabled', state.imagecropenabled ? '1' : '0');
    layer.setAttribute('data-preview-crop-left', String(state.imagecropleftpercent ?? 0));
    layer.setAttribute('data-preview-crop-top', String(state.imagecroptoppercent ?? 0));
    layer.setAttribute('data-preview-crop-width', String(state.imagecropwidthpercent ?? 100));
    layer.setAttribute('data-preview-crop-height', String(state.imagecropheightpercent ?? 100));
    localCourseBannerBuilderUpdateCropSelectionFrame(layer, localCourseBannerBuilderNormaliseCropState(state));
    layer.setAttribute('data-preview-offset-top', String(state.offsettoppercent ?? 0));
    layer.setAttribute('data-preview-offset-right', String(state.offsetrightpercent ?? 0));
    layer.setAttribute('data-preview-offset-bottom', String(state.offsetbottompercent ?? 0));
    layer.setAttribute('data-preview-offset-left', String(state.offsetleftpercent ?? 0));
    layer.setAttribute('data-preview-natural-width', String(state.imagewidth ?? 0));
    layer.setAttribute('data-preview-natural-height', String(state.imageheight ?? 0));
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    var currentUrl = state.url || layer.getAttribute('data-preview-current-url') ||
        (image ? (image.getAttribute('src') || '') : '');
    if (currentUrl) {
        layer.setAttribute('data-preview-current-url', currentUrl);
    }
    localCourseBannerBuilderUpdatePreviewAspectLockButton(layer);
}

function localCourseBannerBuilderBuildSourcePreviewPayload(root) {
    var payload = {};
    Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]')).forEach(function(layer) {
        var state = localCourseBannerBuilderGetSourcePreviewLayerState(layer);
        if (!state || !state.id) {
            return;
        }
        var sortInput = document.querySelector('input[name=\"sortorder_inline[' + state.id + ']\"]');
        if (sortInput) {
            state.sortorder = parseInt(sortInput.value || '0', 10) || 0;
        }
        state.zindex = parseInt(layer.getAttribute('data-preview-zindex') || '0', 10) || 0;
        payload[state.id] = state;
    });
    return payload;
}

function localCourseBannerBuilderSyncSourcePreviewPayload(root) {
    if (!root) {
        return;
    }
    var input = root.querySelector('[data-source-preview-payload=\"1\"]');
    if (!input) {
        return;
    }
    input.value = JSON.stringify(localCourseBannerBuilderBuildSourcePreviewPayload(root));
    if (root.dataset.sourcePreviewUndoStack) {
        localCourseBannerBuilderUpdateSourcePreviewHistoryButtons(root);
    }
}

function localCourseBannerBuilderReadJson(value, fallback) {
    try {
        return JSON.parse(value);
    } catch (error) {
        return fallback;
    }
}

function localCourseBannerBuilderBuildSourcePreviewHistorySnapshot(root) {
    if (!root) {
        return '';
    }
    var order = [];
    var tbody = document.querySelector('.local-course-banner-builder-layer-sortable');
    if (tbody) {
        Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row')).forEach(function(row) {
            var checkbox = row.querySelector('.local-course-banner-builder-layer-select');
            if (checkbox && checkbox.value) {
                order.push(checkbox.value);
            }
        });
    }
    return JSON.stringify({
        layers: localCourseBannerBuilderBuildSourcePreviewPayload(root),
        order: order
    });
}

function localCourseBannerBuilderUpdateSourcePreviewHistoryButtons(root) {
    if (!root) {
        return;
    }
    var undo = root.querySelector('[data-action=\"local-course-banner-builder-undo-source-preview-change\"]');
    var redo = root.querySelector('[data-action=\"local-course-banner-builder-redo-source-preview-change\"]');
    var undoStack = localCourseBannerBuilderReadJson(root.dataset.sourcePreviewUndoStack || '[]', []);
    var redoStack = localCourseBannerBuilderReadJson(root.dataset.sourcePreviewRedoStack || '[]', []);
    var currentSnapshot = localCourseBannerBuilderBuildSourcePreviewHistorySnapshot(root);
    var canUndo = undoStack.length > 1 || (undoStack.length === 1 && undoStack[0] !== currentSnapshot);
    if (undo) {
        undo.disabled = !canUndo;
        undo.classList.toggle('disabled', undo.disabled);
        undo.setAttribute('aria-disabled', undo.disabled ? 'true' : 'false');
    }
    if (redo) {
        redo.disabled = redoStack.length < 1;
        redo.classList.toggle('disabled', redo.disabled);
        redo.setAttribute('aria-disabled', redo.disabled ? 'true' : 'false');
    }
}

function localCourseBannerBuilderEnsureSourcePreviewHistory(root) {
    if (!root) {
        return;
    }
    var undoStack = localCourseBannerBuilderReadJson(root.dataset.sourcePreviewUndoStack || '[]', []);
    if (!undoStack.length) {
        undoStack.push(localCourseBannerBuilderBuildSourcePreviewHistorySnapshot(root));
        root.dataset.sourcePreviewUndoStack = JSON.stringify(undoStack);
        root.dataset.sourcePreviewRedoStack = '[]';
    }
    localCourseBannerBuilderUpdateSourcePreviewHistoryButtons(root);
}

function localCourseBannerBuilderPushSourcePreviewHistory(root) {
    if (!root) {
        return;
    }
    localCourseBannerBuilderEnsureSourcePreviewHistory(root);
    var undoStack = localCourseBannerBuilderReadJson(root.dataset.sourcePreviewUndoStack || '[]', []);
    var snapshot = localCourseBannerBuilderBuildSourcePreviewHistorySnapshot(root);
    if (undoStack[undoStack.length - 1] !== snapshot) {
        undoStack.push(snapshot);
        if (undoStack.length > 40) {
            undoStack.shift();
        }
        root.dataset.sourcePreviewUndoStack = JSON.stringify(undoStack);
    }
    root.dataset.sourcePreviewRedoStack = '[]';
    localCourseBannerBuilderUpdateSourcePreviewHistoryButtons(root);
}

function localCourseBannerBuilderPushSourcePreviewHistoryFromControl(control) {
    var root = control && control.closest ? control.closest('[data-source-visual-editor=\"1\"]') : null;
    if (root) {
        localCourseBannerBuilderPushSourcePreviewHistory(root);
    }
}

function localCourseBannerBuilderApplySourcePreviewHistorySnapshot(root, snapshot) {
    if (!root || !snapshot) {
        return;
    }
    var data = localCourseBannerBuilderReadJson(snapshot, null);
    if (!data || !data.layers) {
        return;
    }
    var tbody = document.querySelector('.local-course-banner-builder-layer-sortable');
    if (tbody && Array.isArray(data.order) && data.order.length) {
        data.order.forEach(function(layerId) {
            var row = Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row')).find(function(candidate) {
                var checkbox = candidate.querySelector('.local-course-banner-builder-layer-select');
                return checkbox && checkbox.value === String(layerId);
            });
            if (row) {
                tbody.appendChild(row);
            }
        });
        localCourseBannerBuilderEnforceLockedLayerOrder(tbody);
        localCourseBannerBuilderSyncLayerSortOrders();
    }
    Object.keys(data.layers).forEach(function(layerId) {
        var layer = root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-layer-id=\"' + layerId + '\"]');
        var state = data.layers[layerId];
        if (!layer || !state) {
            return;
        }
        localCourseBannerBuilderSetSourcePreviewLayerState(layer, state);
        localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
        localCourseBannerBuilderUpdateSourcePreviewRow(root, state);
    });
    localCourseBannerBuilderSyncSourcePreviewOrder(root);
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
    localCourseBannerBuilderSyncSourcePreviewThumbnails(root);
    var selected = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
    if (selected) {
        localCourseBannerBuilderSelectSourcePreviewLayer(root, selected);
    }
}

function localCourseBannerBuilderUndoSourcePreviewChange(control) {
    var root = control && control.closest ? control.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    localCourseBannerBuilderEnsureSourcePreviewHistory(root);
    var undoStack = localCourseBannerBuilderReadJson(root.dataset.sourcePreviewUndoStack || '[]', []);
    var redoStack = localCourseBannerBuilderReadJson(root.dataset.sourcePreviewRedoStack || '[]', []);
    var currentSnapshot = localCourseBannerBuilderBuildSourcePreviewHistorySnapshot(root);
    if (undoStack[undoStack.length - 1] !== currentSnapshot) {
        undoStack.push(currentSnapshot);
    }
    if (undoStack.length <= 1) {
        root.dataset.sourcePreviewUndoStack = JSON.stringify(undoStack);
        localCourseBannerBuilderUpdateSourcePreviewHistoryButtons(root);
        return;
    }
    redoStack.push(undoStack.pop());
    var snapshot = undoStack[undoStack.length - 1];
    root.dataset.sourcePreviewUndoStack = JSON.stringify(undoStack);
    root.dataset.sourcePreviewRedoStack = JSON.stringify(redoStack);
    localCourseBannerBuilderApplySourcePreviewHistorySnapshot(root, snapshot);
    localCourseBannerBuilderUpdateSourcePreviewHistoryButtons(root);
}

function localCourseBannerBuilderRedoSourcePreviewChange(control) {
    var root = control && control.closest ? control.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    localCourseBannerBuilderEnsureSourcePreviewHistory(root);
    var undoStack = localCourseBannerBuilderReadJson(root.dataset.sourcePreviewUndoStack || '[]', []);
    var redoStack = localCourseBannerBuilderReadJson(root.dataset.sourcePreviewRedoStack || '[]', []);
    if (!redoStack.length) {
        return;
    }
    var snapshot = redoStack.pop();
    undoStack.push(snapshot);
    root.dataset.sourcePreviewUndoStack = JSON.stringify(undoStack);
    root.dataset.sourcePreviewRedoStack = JSON.stringify(redoStack);
    localCourseBannerBuilderApplySourcePreviewHistorySnapshot(root, snapshot);
    localCourseBannerBuilderUpdateSourcePreviewHistoryButtons(root);
}

function localCourseBannerBuilderUpdateSourcePreviewRow(root, state) {
    if (!root || !state || !state.id) {
        return;
    }
    var row = document.querySelector('.local-course-banner-builder-layer-row .local-course-banner-builder-layer-select[value=\"' + state.id + '\"]');
    row = row ? row.closest('.local-course-banner-builder-layer-row') : null;
    var fitSelect = document.querySelector('select[name=\"fitmodeoverride_inline[' + state.id + ']\"]');
    if (fitSelect) {
        var nextFitMode = state.fitmodeoverride || '';
        if (Array.prototype.some.call(fitSelect.options, function(option) { return option.value === nextFitMode; })) {
            fitSelect.value = nextFitMode;
        }
    }
    var cell = fitSelect ? fitSelect.closest('.local-course-banner-builder-fit-override-cell') :
        (row ? row.querySelector('.local-course-banner-builder-fit-override-cell') : null);
    if (!cell) {
        return;
    }
    var summary = cell.querySelector('.local-course-banner-builder-border-summary');
    if (!summary) {
        summary = document.createElement('div');
        summary.className = 'local-course-banner-builder-border-summary mt-2';
        summary.innerHTML = '<dl class=\"local-course-banner-builder-border-summary-list\"></dl>';
        cell.appendChild(summary);
    }
    var list = summary.querySelector('.local-course-banner-builder-border-summary-list');
    if (!list) {
        return;
    }
    var items = [
        {
            label: localCourseBannerBuilderGetJsString('summarycustomsize', 'Custom size'),
            value: localCourseBannerBuilderRoundPreviewPercent(state.customwidthpercent) + '% x ' +
                localCourseBannerBuilderRoundPreviewPercent(state.customheightpercent) + '%'
        },
        {
            label: localCourseBannerBuilderGetJsString('summaryspacing', 'Spacing'),
            value: localCourseBannerBuilderGetJsString('summarytop', 'Top') + ' ' +
                localCourseBannerBuilderRoundPreviewPercent(state.offsettoppercent) + '%, ' +
                localCourseBannerBuilderGetJsString('summaryleft', 'Left') + ' ' +
                localCourseBannerBuilderRoundPreviewPercent(state.offsetleftpercent) + '%'
        }
    ];
    items.push({
        label: localCourseBannerBuilderGetJsString('summarykeepaspect', 'Keep proportions'),
        value: state.customsizekeepaspect ?
            localCourseBannerBuilderGetJsString('yes', 'Yes') :
            localCourseBannerBuilderGetJsString('no', 'No')
    });
    if (localCourseBannerBuilderClampPercent(state.imageopacity, 100) < 100) {
        items.push({
            label: localCourseBannerBuilderGetJsString('imageopacity', 'Image opacity'),
            value: localCourseBannerBuilderRoundPreviewPercent(state.imageopacity) + '%'
        });
    }
    if (state.dynamicimagesizeenabled) {
        items.push({
            label: localCourseBannerBuilderGetJsString('imageaboveborder', 'Image above border'),
            value: localCourseBannerBuilderGetJsString('yes', 'Yes')
        });
    }
    list.innerHTML = items.map(function(item) {
        return '<div><dt>' + item.label + '</dt><dd>' + item.value + '</dd></div>';
    }).join('');
    if (row) {
        var preview = row.querySelector('.local-course-banner-builder-admin-preview');
        if (preview) {
            preview.classList.toggle('local-course-banner-builder-admin-preview--dynamic', !!state.dynamicimagesizeenabled);
        }
    }
}

function localCourseBannerBuilderMoveSourcePreviewLayerRowToTop(layerId) {
    if (!layerId) {
        return;
    }
    var tbody = document.querySelector('.local-course-banner-builder-layer-sortable');
    if (!tbody) {
        return;
    }
    var checkbox = tbody.querySelector('.local-course-banner-builder-layer-row .local-course-banner-builder-layer-select[value=\"' + layerId + '\"]');
    var row = checkbox ? checkbox.closest('.local-course-banner-builder-layer-row') : null;
    if (!row) {
        return;
    }
    var borderRow = Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row--border')).pop();
    if (borderRow && borderRow !== row) {
        tbody.insertBefore(row, borderRow);
    } else {
        tbody.appendChild(row);
    }
    Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row')).forEach(function(layerRow, index) {
        var input = layerRow.querySelector('input[name^=\"sortorder_inline\"]');
        if (input) {
            input.value = String(index);
        }
    });
}

function localCourseBannerBuilderSyncSourcePreviewLayer(root, layer) {
    if (!root || !layer) {
        return;
    }
    localCourseBannerBuilderSyncStandalonePreviewLayer(root.querySelector('[data-source-preview-frame=\"1\"]') || root, layer);
}

function localCourseBannerBuilderSyncSourcePreviewOrder(root) {
    if (!root) {
        return;
    }
    var tbody = document.querySelector('.local-course-banner-builder-layer-sortable');
    if (!tbody) {
        localCourseBannerBuilderSyncSourcePreviewPayload(root);
        return;
    }
    var rows = Array.prototype.slice.call(tbody.querySelectorAll('.local-course-banner-builder-layer-row'));
    rows.forEach(function(row, index) {
        var checkbox = row.querySelector('.local-course-banner-builder-layer-select');
        if (!checkbox) {
            return;
        }
        var layerId = checkbox.value || '';
        var previewLayer = root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-layer-id=\"' + layerId + '\"]');
        if (previewLayer) {
            var priority = previewLayer.getAttribute('data-preview-dynamic-image') === '1' ? 2 : 0;
            var zindex = (priority * 1000) + index + 1;
            previewLayer.setAttribute('data-preview-sortorder', String(index));
            previewLayer.setAttribute('data-preview-zindex', String(zindex));
            previewLayer.style.zIndex = String(zindex);
            localCourseBannerBuilderSyncSourcePreviewLayer(root, previewLayer);
            return;
        }
        var previewBorder = root.querySelector('[data-source-preview-border=\"1\"]');
        if (previewBorder && row.classList.contains('local-course-banner-builder-layer-row--border')) {
            var borderZIndex = 1000 + index + 1;
            previewBorder.setAttribute('data-preview-sortorder', String(index));
            previewBorder.setAttribute('data-preview-zindex', String(borderZIndex));
            previewBorder.style.zIndex = String(borderZIndex);
        }
    });
    Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"]')).forEach(function(layer) {
        localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
    });
    localCourseBannerBuilderSyncSourcePreviewPayload(root);
    localCourseBannerBuilderSyncSourcePreviewThumbnails(root);
}

function localCourseBannerBuilderSelectSourcePreviewLayer(root, layer) {
    if (!root || !layer || layer.getAttribute('data-source-preview-editable') !== '1' ||
            !localCourseBannerBuilderIsSourcePreviewLayerEnabled(layer)) {
        return;
    }
    Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"]')).forEach(function(node) {
        var selected = node === layer;
        node.classList.toggle('local-course-banner-builder-source-preview-layer--selected', selected);
        if (selected) {
            node.setAttribute('data-preview-current-layer', '1');
        } else {
            node.removeAttribute('data-preview-current-layer');
            node.removeAttribute('data-preview-active-edge');
        }
    });
    localCourseBannerBuilderSyncSourcePreviewTopLayerButton(root);
    localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
    localCourseBannerBuilderSyncSourcePreviewOpacityButton(root);
    localCourseBannerBuilderSyncSourcePreviewFitButton(root);
    localCourseBannerBuilderSyncSourcePreviewFillButton(root);
    localCourseBannerBuilderSyncSourcePreviewDeleteButton(root);
    localCourseBannerBuilderUpdateSourcePreviewThumbnailSelection(root);
}

function localCourseBannerBuilderInitSourceVisualEditor(scope) {
    localCourseBannerBuilderGetSourceVisualEditorRoots(scope).forEach(function(root) {
        var isReadonly = localCourseBannerBuilderIsSourcePreviewReadonly(root);
        if (!root.dataset.sourcePreviewBound) {
            root.addEventListener('click', function(event) {
                if (localCourseBannerBuilderIsSourcePreviewReadonly(root)) {
                    return;
                }
                if (Date.now() < localCourseBannerBuilderSuppressSourcePreviewClickUntil) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                var layer = localCourseBannerBuilderGetSourcePreviewEventLayer(root, event);
                if (layer && root.contains(layer)) {
                    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
                }
            });
            root.addEventListener('dblclick', function(event) {
                if (localCourseBannerBuilderIsSourcePreviewReadonly(root)) {
                    return;
                }
                var layer = localCourseBannerBuilderGetSourcePreviewEventLayer(root, event);
                if (layer && root.contains(layer)) {
                    event.preventDefault();
                    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
                }
            });
            root.dataset.sourcePreviewBound = '1';
        }
        Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"]')).forEach(function(layer) {
            localCourseBannerBuilderSyncSourcePreviewLayer(root, layer);
        });
        var selected = localCourseBannerBuilderGetSelectedSourcePreviewLayer(root);
        if (selected && !isReadonly) {
            localCourseBannerBuilderSelectSourcePreviewLayer(root, selected);
        }
        var borderToggleButton = root.querySelector('[data-action=\"local-course-banner-builder-toggle-preview-border\"]');
        if (borderToggleButton) {
            var borderVisible = root.getAttribute('data-preview-border-hidden') !== '1';
            borderToggleButton.setAttribute('data-preview-border-visible', borderVisible ? '1' : '0');
            borderToggleButton.setAttribute('aria-pressed', borderVisible ? 'true' : 'false');
            localCourseBannerBuilderSetActionButtonContent(
                borderToggleButton,
                borderVisible ? (borderToggleButton.getAttribute('data-hide-icon') || 'fa-eye-slash') : (borderToggleButton.getAttribute('data-show-icon') || 'fa-eye'),
                borderVisible ?
                    (borderToggleButton.getAttribute('data-hide-label') ||
                        localCourseBannerBuilderGetJsString('hidepreviewborder', '')) :
                    (borderToggleButton.getAttribute('data-show-label') ||
                        localCourseBannerBuilderGetJsString('showpreviewborder', ''))
            );
        }
        localCourseBannerBuilderSyncSourcePreviewTopLayerButton(root);
        localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
        localCourseBannerBuilderSyncSourcePreviewOpacityButton(root);
        localCourseBannerBuilderSyncSourcePreviewFitButton(root);
        localCourseBannerBuilderSyncSourcePreviewFillButton(root);
        localCourseBannerBuilderSyncSourcePreviewCropButtons(root);
        localCourseBannerBuilderSyncSourcePreviewOrderButtons(root);
        localCourseBannerBuilderSyncSourcePreviewOrder(root);
        localCourseBannerBuilderSyncSourcePreviewPayload(root);
        localCourseBannerBuilderSyncSourcePreviewThumbnails(root);
        localCourseBannerBuilderSyncSourcePreviewInheritedLayersButton(root);
        localCourseBannerBuilderEnsureSourcePreviewHistory(root);
        localCourseBannerBuilderSyncPreviewSnapButtons(root);
    });
}

function localCourseBannerBuilderStartSourcePreviewInteraction(event, mode, layer) {
    if (!layer || event.button !== 0) {
        return;
    }
    var root = layer.closest('[data-source-visual-editor=\"1\"]');
    var frame = root ? root.querySelector('[data-source-preview-frame=\"1\"]') : null;
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    if (!root || !frame || !image || localCourseBannerBuilderIsSourcePreviewReadonly(root)) {
        return;
    }
    localCourseBannerBuilderPushSourcePreviewHistory(root);
    var frameRect = frame.getBoundingClientRect();
    var layerRect = layer.getBoundingClientRect();
    if (!frameRect.width || !frameRect.height) {
        return;
    }
    var startState = localCourseBannerBuilderGetSourcePreviewCustomInteractionState(layer, frame);
    if (!startState) {
        return;
    }
    localCourseBannerBuilderSourcePreviewInteraction = {
        mode: mode,
        edge: layer.getAttribute('data-preview-active-edge') || '',
        root: root,
        frame: frame,
        layer: layer,
        thresholdPassed: mode !== 'drag',
        frameWidth: frameRect.width,
        frameHeight: frameRect.height,
        startX: event.clientX,
        startY: event.clientY,
        startLeftPx: layerRect.left - frameRect.left,
        startTopPx: layerRect.top - frameRect.top,
        startWidthPx: layerRect.width,
        startHeightPx: layerRect.height,
        startState: startState
    };
    if (layer.setPointerCapture && event.pointerId !== undefined) {
        try {
            layer.setPointerCapture(event.pointerId);
        } catch (error) {
            // Ignore pointer capture failures and keep document-level listeners.
        }
    }
}

function localCourseBannerBuilderHandleSourcePreviewPointerMove(event) {
    var interaction = localCourseBannerBuilderSourcePreviewInteraction;
    if (!interaction) {
        return;
    }
    event.preventDefault();
    var deltaX = event.clientX - interaction.startX;
    var deltaY = event.clientY - interaction.startY;
    if (!interaction.thresholdPassed) {
        if (Math.abs(deltaX) < 3 && Math.abs(deltaY) < 3) {
            return;
        }
        interaction.thresholdPassed = true;
    }
    var state = interaction.startState;
    var nextState = Object.assign({}, state, {
        fitmodeoverride: 'custom',
        positionanchor: 'top-left',
        offsetrightpercent: 0,
        offsetbottompercent: 0
    });

    if (interaction.mode === 'drag') {
        var nextLeftPx = interaction.startLeftPx + deltaX;
        var nextTopPx = interaction.startTopPx + deltaY;
        if (localCourseBannerBuilderIsPreviewSnapEnabled(interaction.root)) {
            var snap = localCourseBannerBuilderFindPreviewSnap(
                interaction.frame,
                interaction.layer,
                {
                    left: nextLeftPx,
                    top: nextTopPx,
                    right: nextLeftPx + interaction.startWidthPx,
                    bottom: nextTopPx + interaction.startHeightPx,
                    centerX: nextLeftPx + (interaction.startWidthPx / 2),
                    centerY: nextTopPx + (interaction.startHeightPx / 2)
                },
                '[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]',
                {limitPeers: true}
            );
            nextLeftPx += snap.dx;
            nextTopPx += snap.dy;
        }
        nextState.offsetleftpercent = localCourseBannerBuilderClampPreviewOffset(
            localCourseBannerBuilderRoundPreviewPercent((nextLeftPx / interaction.frameWidth) * 100)
        );
        nextState.offsettoppercent = localCourseBannerBuilderClampPreviewOffset(
            localCourseBannerBuilderRoundPreviewPercent((nextTopPx / interaction.frameHeight) * 100)
        );
    } else {
        var widthPercent = localCourseBannerBuilderRoundPreviewPercent(((interaction.startWidthPx + deltaX) / interaction.frameWidth) * 100);
        var heightPercent = localCourseBannerBuilderRoundPreviewPercent(((interaction.startHeightPx + deltaY) / interaction.frameHeight) * 100);
        if (interaction.mode === 'resize-edge') {
            switch (interaction.edge) {
                case 'left':
                    widthPercent = localCourseBannerBuilderRoundPreviewPercent(((interaction.startWidthPx - deltaX) / interaction.frameWidth) * 100);
                    nextState.offsetleftpercent = localCourseBannerBuilderRoundPreviewPercent(((interaction.startLeftPx + deltaX) / interaction.frameWidth) * 100);
                    break;
                case 'top':
                    heightPercent = localCourseBannerBuilderRoundPreviewPercent(((interaction.startHeightPx - deltaY) / interaction.frameHeight) * 100);
                    nextState.offsettoppercent = localCourseBannerBuilderRoundPreviewPercent(((interaction.startTopPx + deltaY) / interaction.frameHeight) * 100);
                    break;
                case 'right':
                    break;
                case 'bottom':
                    break;
            }
        }

        widthPercent = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, widthPercent));
        heightPercent = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, heightPercent));
        if (state.customsizekeepaspect) {
            var aspectRatio = interaction.startWidthPx > 0 && interaction.startHeightPx > 0 ?
                (interaction.startWidthPx / interaction.startHeightPx) : 1;
            heightPercent = (widthPercent * interaction.frameWidth) / (Math.max(1, aspectRatio) * interaction.frameHeight);
            heightPercent = Math.max(1, Math.min(localCourseBannerBuilderCustomSizePercentMax, heightPercent));
        }
        nextState.customwidthpercent = widthPercent;
        nextState.customheightpercent = heightPercent;
    }

    localCourseBannerBuilderSetSourcePreviewLayerState(interaction.layer, nextState);
    localCourseBannerBuilderSyncSourcePreviewLayer(interaction.root, interaction.layer);
    localCourseBannerBuilderUpdateSourcePreviewRow(interaction.root, nextState);
    localCourseBannerBuilderSyncSourcePreviewPayload(interaction.root);
    localCourseBannerBuilderUpdatePreviewGuides(
        interaction.frame,
        interaction.layer,
        '[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]',
        {limitPeers: true}
    );
}

function localCourseBannerBuilderStopSourcePreviewInteraction() {
    var frame = localCourseBannerBuilderSourcePreviewInteraction ?
        localCourseBannerBuilderSourcePreviewInteraction.frame : null;
    if (localCourseBannerBuilderSourcePreviewInteraction && localCourseBannerBuilderSourcePreviewInteraction.layer) {
        localCourseBannerBuilderSourcePreviewInteraction.layer.removeAttribute('data-preview-active-edge');
    }
    localCourseBannerBuilderSourcePreviewInteraction = null;
    localCourseBannerBuilderClearPreviewGuides(frame);
}

document.addEventListener('pointermove', localCourseBannerBuilderHandlePreviewPointerMove);
document.addEventListener('pointerup', localCourseBannerBuilderStopPreviewInteraction);
document.addEventListener('pointercancel', localCourseBannerBuilderStopPreviewInteraction);
document.addEventListener('pointermove', localCourseBannerBuilderHandleSourcePreviewPointerMove);
document.addEventListener('pointerup', localCourseBannerBuilderStopSourcePreviewInteraction);
document.addEventListener('pointercancel', localCourseBannerBuilderStopSourcePreviewInteraction);
document.addEventListener('pointermove', localCourseBannerBuilderHandleCropPointerMove);
document.addEventListener('pointerup', localCourseBannerBuilderStopCropInteraction);
document.addEventListener('pointercancel', localCourseBannerBuilderStopCropInteraction);

function localCourseBannerBuilderSyncDetailsCollapseIcons(scope) {
    var root = scope || document;
    Array.prototype.slice.call(root.querySelectorAll('details')).forEach(function(details) {
        var icon = details.querySelector('summary [data-local-details-toggle-icon=\"1\"], summary .icons-collapse-expand');
        if (!icon) {
            return;
        }
        icon.classList.toggle('collapsed', !details.open);
        if (!details.dataset.collapseIconBound) {
            details.addEventListener('toggle', function() {
                icon.classList.toggle('collapsed', !details.open);
            });
            details.dataset.collapseIconBound = '1';
        }
    });
}

function localCourseBannerBuilderSyncDashedControls(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    var styleInput = layerForm ? layerForm.querySelector('#id_borderstyle') : null;
    if (!styleInput) {
        return;
    }
    var enabled = styleInput.value === 'dashed' && !styleInput.disabled;
    var dashSelectors = [
        '[data-border-dash-length=\"1\"]',
        '[data-percent-slider-for=\"id_borderdashlength\"]',
        '[data-modal-side-range-number-for=\"id_borderdashlength\"]'
    ];
    Array.prototype.slice.call(layerForm.querySelectorAll(dashSelectors.join(','))).forEach(function(input) {
        input.disabled = !enabled;
        input.classList.toggle('local-course-banner-builder-input-disabled', !enabled);
        var wrapper = input.closest(
            '.fitem, .form-group, .mb-3, .local-course-banner-builder-linked-range-wrapper'
        );
        if (wrapper) {
            wrapper.classList.toggle('local-course-banner-builder-option-disabled', !enabled);
            wrapper.setAttribute('aria-hidden', 'false');
        }
    });
    Array.prototype.slice.call(layerForm.querySelectorAll([
        '[data-percent-slider-wrapper-for=\"id_borderdashlength\"]',
        '[data-modal-side-range-title=\"id_borderdashlength\"]'
    ].join(','))).forEach(function(element) {
        element.classList.toggle('local-course-banner-builder-option-disabled', !enabled);
        element.setAttribute('aria-hidden', 'false');
    });
}

function localCourseBannerBuilderApplyFetchedFormValues(sourceForm, targetForm) {
    if (!sourceForm || !targetForm) {
        return;
    }

    Array.prototype.slice.call(targetForm.querySelectorAll('input, select, textarea')).forEach(function(targetField) {
        if (!targetField.name) {
            return;
        }
        if (targetField.type === 'checkbox') {
            var sourceCheckbox = Array.prototype.slice.call(sourceForm.querySelectorAll('input[type=\"checkbox\"]')).find(function(input) {
                return input.name === targetField.name;
            });
            if (sourceCheckbox) {
                targetField.checked = !!sourceCheckbox.checked;
            }
            return;
        }

        if (targetField.type === 'radio') {
            var sourceRadio = Array.prototype.slice.call(sourceForm.querySelectorAll('input[type=\"radio\"]')).find(function(input) {
                return input.name === targetField.name && input.value === targetField.value;
            });
            if (sourceRadio) {
                targetField.checked = !!sourceRadio.checked;
            }
            return;
        }

        var sourceField = Array.prototype.slice.call(sourceForm.elements).find(function(input) {
            return input.name === targetField.name;
        });
        if (sourceField) {
            targetField.value = sourceField.value;
        }
    });
}

function localCourseBannerBuilderIsFetchedBorderOnlyForm(sourceForm) {
    if (!sourceForm) {
        return false;
    }

    var borderToggle = sourceForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    var hasExistingImageInput = sourceForm.querySelector('#id_hasexistingimage');
    var hasExistingImage = !!(hasExistingImageInput && parseInt(hasExistingImageInput.value || '0', 10) > 0);

    return !!(borderToggle && borderToggle.checked && !hasExistingImage);
}

function localCourseBannerBuilderValidateLayerForm(e) {
    var layerForm = e && e.target ? localCourseBannerBuilderGetLayerScope(e.target) : localCourseBannerBuilderGetLayerScope();
    var filemanager = layerForm ? layerForm.querySelector('#fitem_id_bannerimage_filemanager') : null;
    var borderToggle = layerForm ? layerForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]') : null;
    var hasExistingImageInput = layerForm ? layerForm.querySelector('#id_hasexistingimage') : null;
    var warning = layerForm ? layerForm.querySelector('[data-layer-validation-warning=\"1\"]') : null;
    if (!filemanager || !borderToggle || !warning) {
        return true;
    }
    localCourseBannerBuilderSyncBorderSidesValue(layerForm);
    var hasExistingImage = !!(hasExistingImageInput && parseInt(hasExistingImageInput.value || '0', 10) > 0);
    var selectedLayerType = localCourseBannerBuilderGetSelectedLayerType(layerForm);
    var valid = selectedLayerType === 'overlay' ||
        borderToggle.checked ||
        hasExistingImage ||
        localCourseBannerBuilderGetVisibleDraftFileItems(layerForm).length > 0;
    warning.classList.toggle('d-none', valid);
    if (!valid && e) {
        e.preventDefault();
        e.stopPropagation();
    }
    return valid;
}

function localCourseBannerBuilderBindLayerFormEvents(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerForm) {
        return;
    }
    var fitOverride = layerForm.querySelector('#id_fitmodeoverride');
    if (fitOverride && !fitOverride.dataset.layerBound) {
        fitOverride.addEventListener('change', function() {
            localCourseBannerBuilderSyncCustomSizeFields(layerForm);
            localCourseBannerBuilderSyncOffsetFields(layerForm);
        });
        fitOverride.dataset.layerBound = '1';
    }

    var keepAspect = layerForm.querySelector('[data-custom-size-keep-aspect=\"1\"][type=\"checkbox\"]');
    if (keepAspect && !keepAspect.dataset.layerBound) {
        keepAspect.addEventListener('change', function() {
            localCourseBannerBuilderSyncCustomSizeFields(layerForm);
            if (keepAspect.checked) {
                var widthInput = layerForm.querySelector('#id_customwidthpercent');
                if (widthInput) {
                    localCourseBannerBuilderSyncLinkedCustomSizeInputs(layerForm, widthInput);
                }
            }
        });
        keepAspect.dataset.layerBound = '1';
    }

    var dynamicSize = layerForm.querySelector('#id_dynamicimagesizeenabled');
    if (dynamicSize && !dynamicSize.dataset.layerBound) {
        dynamicSize.addEventListener('change', function() {
            localCourseBannerBuilderSyncCustomSizeFields(layerForm);
        });
        dynamicSize.dataset.layerBound = '1';
    }

    var imageOpacity = layerForm.querySelector('#id_imageopacity');
    if (imageOpacity && !imageOpacity.dataset.layerBound) {
        imageOpacity.addEventListener('input', function() {
            localCourseBannerBuilderApplyModalImageOpacity(layerForm, imageOpacity.value || '100');
        });
        imageOpacity.dataset.layerBound = '1';
    }

    var customWidth = layerForm.querySelector('#id_customwidthpercent');
    if (customWidth && !customWidth.dataset.layerBound) {
        customWidth.addEventListener('input', function() {
            localCourseBannerBuilderSyncLinkedCustomSizeInputs(layerForm, customWidth);
        });
        customWidth.dataset.layerBound = '1';
    }

    var customHeight = layerForm.querySelector('#id_customheightpercent');
    if (customHeight && !customHeight.dataset.layerBound) {
        customHeight.addEventListener('input', function() {
            localCourseBannerBuilderSyncLinkedCustomSizeInputs(layerForm, customHeight);
        });
        customHeight.dataset.layerBound = '1';
    }

    var anchor = layerForm.querySelector('[data-layer-position-anchor=\"1\"]');
    if (anchor && !anchor.dataset.layerBound) {
        anchor.addEventListener('change', function() {
            localCourseBannerBuilderApplyLayerPositionAnchorChange(layerForm);
        });
        anchor.dataset.layerBound = '1';
    }

    var borderToggle = layerForm.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    if (borderToggle && !borderToggle.dataset.layerBound) {
        borderToggle.addEventListener('change', function() {
            localCourseBannerBuilderSyncLayerInputModes(layerForm);
            localCourseBannerBuilderSyncDashedControls(layerForm);
            localCourseBannerBuilderSyncBorderPreview(layerForm);
            localCourseBannerBuilderSyncBinaryOptionButton(borderToggle);
            localCourseBannerBuilderSyncModalPreviewActionButtons(layerForm);
        });
        borderToggle.dataset.layerBound = '1';
    }

    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(layerForm);
    if (overlayToggle && !overlayToggle.dataset.layerBound) {
        overlayToggle.addEventListener('change', function() {
            localCourseBannerBuilderSyncLayerInputModes(layerForm);
            localCourseBannerBuilderSyncModalPreviewActionButtons(layerForm);
            localCourseBannerBuilderSyncBinaryOptionButton(overlayToggle);
        });
        overlayToggle.dataset.layerBound = '1';
    }

    var enabledToggle = layerForm.querySelector('#id_isenabled[type=\"checkbox\"]');
    if (enabledToggle && !enabledToggle.dataset.layerBound) {
        enabledToggle.addEventListener('change', function() {
            localCourseBannerBuilderSyncBinaryOptionButton(enabledToggle);
        });
        enabledToggle.dataset.layerBound = '1';
    }

    var allBorderSides = layerForm.querySelector('[data-border-side-all=\"1\"][type=\"checkbox\"]');
    if (allBorderSides && !allBorderSides.dataset.layerBound) {
        allBorderSides.addEventListener('change', function() {
            var form = allBorderSides.closest('form.mform');
            localCourseBannerBuilderSetBorderSidesFromAll(allBorderSides.checked, form);
            localCourseBannerBuilderSyncBorderSideGroup(form);
            localCourseBannerBuilderSyncBorderRoundedField(form);
            localCourseBannerBuilderSyncBorderPreview(form);
        });
        allBorderSides.dataset.layerBound = '1';
    }

    Array.prototype.slice.call(layerForm.querySelectorAll('[data-border-side][type=\"checkbox\"]')).forEach(function(input) {
        if (input.dataset.layerBound) {
            return;
        }
        input.addEventListener('change', function() {
            var form = input.closest('form.mform');
            localCourseBannerBuilderSyncBorderSidesValue(form);
            localCourseBannerBuilderSyncBorderSideGroup(form);
            localCourseBannerBuilderSyncBorderRoundedField(form);
            localCourseBannerBuilderSyncBorderPreview(form);
        });
        input.dataset.layerBound = '1';
    });

    var borderAccordion = layerForm.querySelector('[data-border-accordion=\"1\"]');
    if (borderAccordion && !borderAccordion.dataset.layerBound) {
        var summary = borderAccordion.querySelector('summary');
        if (summary) {
            summary.addEventListener('click', function(e) {
                if (borderAccordion.classList.contains('local-course-banner-builder-disabled')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }
        borderAccordion.dataset.layerBound = '1';
    }

    Array.prototype.slice.call(layerForm.querySelectorAll('[data-layer-type-option]')).forEach(function(button) {
        if (button.dataset.layerTypeBound) {
            return;
        }
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var form = button.closest('form.mform');
            var nextType = button.getAttribute('data-layer-type-option');
            localCourseBannerBuilderSelectLayerType(form, nextType, true);
        });
        button.dataset.layerTypeBound = '1';
    });

    Array.prototype.slice.call(layerForm.querySelectorAll([
        '#id_bordercolor',
        '#id_borderwidth',
        '#id_borderopacity',
        '#id_borderfade',
        '#id_borderstyle',
        '[data-border-dash-length=\"1\"]',
        '[data-border-inner-rounded=\"1\"][type=\"checkbox\"]'
    ].join(','))).forEach(function(input) {
        if (!input || input.dataset.layerBound) {
            return;
        }
        input.addEventListener('input', function() {
            localCourseBannerBuilderSyncBorderPreview(layerForm);
        });
        input.addEventListener('change', function() {
            localCourseBannerBuilderSyncDashedControls(layerForm);
            localCourseBannerBuilderSyncBorderStyleChoice(layerForm);
            localCourseBannerBuilderSyncBorderPreview(layerForm);
        });
        input.dataset.layerBound = '1';
    });

    Array.prototype.slice.call(layerForm.querySelectorAll([
        '#id_overlaytarget',
        '#id_overlaybannercolor',
        '#id_overlaybanneropacity',
        '#id_overlayslideshowcolor',
        '#id_overlayslideshowopacity',
        '#id_overlaytitleabove',
        '#id_overlayborderabove',
        '[data-overlay-color-picker]'
    ].join(','))).forEach(function(input) {
        if (!input || input.dataset.layerBound) {
            return;
        }
        input.addEventListener('input', function() {
            localCourseBannerBuilderSyncModalOverlayPreview(layerForm);
        });
        input.addEventListener('change', function() {
            localCourseBannerBuilderSyncModalOverlayPreview(layerForm);
        });
        input.dataset.layerBound = '1';
    });

    if (layerForm && !layerForm.dataset.layerBound) {
        layerForm.addEventListener('submit', function(event) {
            localCourseBannerBuilderPrepareLayerFormForSubmit(layerForm);
            localCourseBannerBuilderValidateLayerForm(event);
        });
        layerForm.dataset.layerBound = '1';
    }
}

function localCourseBannerBuilderToggleCheckboxInput(input) {
    if (!input || input.disabled) {
        return;
    }
    input.checked = !input.checked;
    localCourseBannerBuilderSyncBinaryOptionButton(input);
    input.dispatchEvent(new Event('change', {bubbles: true}));
}

function localCourseBannerBuilderSetActionButtonContent(button, iconClass, label) {
    if (!button) {
        return;
    }
    button.innerHTML = '<i class=\"fa ' + iconClass + ' me-2\" aria-hidden=\"true\"></i><span>' + label + '</span>';
}

function localCourseBannerBuilderGetJsString(key, fallback) {
    if (window.M && M.util && typeof M.util.get_string === 'function') {
        try {
            return M.util.get_string(key, 'local_course_banner_builder');
        } catch (error) {
            return fallback;
        }
    }
    return fallback;
}

function localCourseBannerBuilderSyncBinaryOptionButton(input) {
    if (!input || !input.form) {
        return;
    }
    var button = input.form.querySelector('[data-toggle-button-for=\"#' + input.id + '\"]');
    if (!button) {
        return;
    }
    var checked = !!input.checked;
    button.disabled = !!input.disabled;
    button.classList.toggle('btn-primary', checked);
    button.classList.toggle('btn-outline-secondary', !checked);
    button.classList.toggle('disabled', !!input.disabled);
    localCourseBannerBuilderSetActionButtonContent(
        button,
        checked ? 'fa-toggle-on' : 'fa-toggle-off',
        checked ? (button.getAttribute('data-label-on') || 'Enabled') :
            (button.getAttribute('data-label-off') || 'Disabled')
    );
}

function localCourseBannerBuilderEnhanceBinaryOptionButtons(form) {
    if (!form) {
        return;
    }
    localCourseBannerBuilderMoveLayerPreviewToTop(form);
    var enabledInput = form.querySelector('#id_isenabled');
    var enabledRow = enabledInput ? enabledInput.closest('.fitem, .form-group, .mb-3, .row') : null;
    var previewRow = form.querySelector('#fitem_id_layerpreview, #fitem_id_imagepreview, #fitem_id_borderpreview');
    if (enabledRow && enabledRow.parentNode && enabledRow.parentNode.firstChild !== enabledRow &&
            !form.querySelector('[data-layer-top-choice-row=\"1\"]')) {
        enabledRow.parentNode.insertBefore(enabledRow, previewRow ? previewRow.nextSibling : enabledRow.parentNode.firstChild);
    }
    [
        {selector: '#id_isenabled', icon: 'fa-toggle-on', useenabledlabels: true},
        {selector: '#id_borderenabled', icon: 'fa-toggle-off'}
    ].forEach(function(config) {
        var input = form.querySelector(config.selector);
        if (!input || input.type === 'hidden') {
            return;
        }
        if (config.selector === '#id_borderenabled' && form.querySelector('[data-layer-type-toggle=\"1\"]')) {
            return;
        }
        var row = input.closest('.fitem, .form-group, .mb-3, .row');
        if (!row) {
            return;
        }
        row.classList.add('local-course-banner-builder-toggle-button-row');
        var source = input.closest('.form-check, .custom-control, .checkbox') || input.parentElement;
        if (source) {
            source.classList.add('local-course-banner-builder-toggle-button-source');
        }
        var host = form.querySelector('[data-toggle-button-host-for=\"#' + input.id + '\"]') ||
            row.querySelector('[data-toggle-button-host-for=\"#' + input.id + '\"]');
        if (!host) {
            host = document.createElement('div');
            host.className = 'local-course-banner-builder-toggle-button-host';
            host.setAttribute('data-toggle-button-host-for', '#' + input.id);
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-secondary local-course-banner-builder-slideshow-enable-button';
            button.setAttribute('data-toggle-button-for', '#' + input.id);
            var label = row.querySelector('label[for=\"' + input.id + '\"]');
            var labelText = label ? label.textContent.trim() : input.name;
            button.setAttribute('data-label-on', config.useenabledlabels ? 'Enabled' : labelText);
            button.setAttribute('data-label-off', config.useenabledlabels ? 'Disabled' : labelText);
            localCourseBannerBuilderSetActionButtonContent(button, config.icon, (label ? label.textContent.trim() : input.name));
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                localCourseBannerBuilderToggleCheckboxInput(input);
            });
            host.appendChild(button);
        }
        if (host.parentNode !== row.querySelector('.felement, .col-md-9')) {
            var target = row.querySelector('.felement, .col-md-9');
            if (target && host.parentNode !== target) {
                target.insertBefore(host, target.firstChild);
            }
        }
        localCourseBannerBuilderSyncBinaryOptionButton(input);
    });
    localCourseBannerBuilderEmbedEnabledToggleInLayerTypeChoice(form);
}

function localCourseBannerBuilderSyncModalPreviewActionButtons(form) {
    if (!form) {
        return;
    }
    var filemanager = form.querySelector('#fitem_id_bannerimage_filemanager');
    var isFilemanagerBusy = localCourseBannerBuilderIsDraftFilemanagerBusy(form);
    var draftFiles = isFilemanagerBusy ? [] : localCourseBannerBuilderGetDraftPreviewFiles(form);
    var visibleFileItems = (!isFilemanagerBusy && filemanager) ? Array.prototype.slice.call(filemanager.querySelectorAll('.fp-file:not(.fp-folder)')).filter(function(item) {
        return item.dataset.previewDeleted !== '1' && !item.hidden && item.getAttribute('aria-hidden') !== 'true';
    }) : [];
    var hasFiles = !!(draftFiles.length || visibleFileItems.length);
    var hasExistingImageInput = form.querySelector('#id_hasexistingimage');
    var currentIsBorderLayerInput = form.querySelector('#id_currentisborderlayer');
    var elementIdInput = form.querySelector('#id_elementid');
    var borderToggle = form.querySelector(
        '[data-border-toggle=\"1\"][type=\"checkbox\"], [data-border-toggle=\"1\"][type=\"hidden\"]'
    );
    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(form);
    var currentPreviewLayer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    var currentPreviewImage = currentPreviewLayer ? currentPreviewLayer.querySelector('[data-preview-image-tag=\"1\"]') : null;
    var hasCurrentPreviewImage = !!(currentPreviewLayer && !currentPreviewLayer.hidden &&
        ((currentPreviewLayer.getAttribute('data-preview-current-url') || '') ||
        (currentPreviewImage && currentPreviewImage.getAttribute('src'))));
    var hasExistingImage = !!(hasExistingImageInput && parseInt(hasExistingImageInput.value || '0', 10) > 0);
    var isExistingBorderLayer = !!(currentIsBorderLayerInput && parseInt(currentIsBorderLayerInput.value || '0', 10) > 0);
    var hasExistingElement = !!(elementIdInput && parseInt(elementIdInput.value || '0', 10) > 0);
    var isEditImageLayer = hasExistingElement && !isExistingBorderLayer;
    var hasImage = hasFiles || hasExistingImage || isEditImageLayer || hasCurrentPreviewImage;
    var borderToggleChecked = !!(borderToggle &&
        (borderToggle.type === 'checkbox' ? borderToggle.checked : borderToggle.value === '1'));
    var selectedLayerType = localCourseBannerBuilderGetSelectedLayerType(form);
    var overlayToggleChecked = selectedLayerType === 'overlay';
    var isBorderOnly = !!(selectedLayerType === 'border' && !hasFiles && !hasExistingImage);
    var isOverlayOnly = !!(overlayToggleChecked && !hasFiles && !hasExistingImage);
    var submitProxy = form.querySelector('[data-modal-preview-submit-proxy=\"1\"]');
    var deleteDraftButton = form.querySelector('[data-action=\"local-course-banner-builder-delete-selected-draft-preview-layer\"]');
    var activeDraftIndex = typeof form.dataset.activeDraftIndex !== 'undefined' ? String(form.dataset.activeDraftIndex) : '';
    var hasActiveDraft = activeDraftIndex !== '' && draftFiles.some(function(file) {
        return String(file.index) === activeDraftIndex;
    });

    localCourseBannerBuilderEnsureModalImageControls(form);
    localCourseBannerBuilderEnsureModalOpacityControls(form);
    localCourseBannerBuilderEnsureModalBorderControls(form);
    localCourseBannerBuilderEnsureModalOverlayControls(form);
    localCourseBannerBuilderSyncModalOverlayPreview(form);
    localCourseBannerBuilderEnsureBorderStyleChoice(form);

    Array.prototype.slice.call(form.querySelectorAll('[data-preview-action-bound-input]')).forEach(function(button) {
        var selector = button.getAttribute('data-preview-action-bound-input');
        var input = selector ? form.querySelector(selector) : null;
        if (!input) {
            return;
        }
        var group = button.getAttribute('data-preview-action-group') || 'shared';
        var visible = false;
        if (group === 'image') {
            visible = !isBorderOnly && !isOverlayOnly;
        } else if (group === 'border') {
            visible = isBorderOnly || isExistingBorderLayer;
        } else if (group === 'overlay') {
            visible = isOverlayOnly;
        } else {
            visible = hasImage || isBorderOnly || isExistingBorderLayer || isOverlayOnly;
        }
        if (input.matches && input.matches('[data-preview-context-toggle=\"1\"]')) {
            visible = true;
        }
        button.hidden = !visible;
        var disabled = group === 'image' && !hasImage;
        button.disabled = disabled;
        button.classList.toggle('disabled', disabled);
        button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        var checked = !!input.checked;
        button.classList.toggle('btn-primary', checked);
        button.classList.toggle('btn-outline-secondary', !checked);
        button.classList.toggle('active', checked);
        button.classList.toggle('local-course-banner-builder-source-preview-button--active', checked);
        button.setAttribute('aria-pressed', checked ? 'true' : 'false');
        if (button.hasAttribute('data-preview-action-label-on') && button.hasAttribute('data-preview-action-label-off')) {
            localCourseBannerBuilderSetActionButtonContent(
                button,
                checked ? (button.getAttribute('data-preview-action-icon-on') || 'fa-check') : (button.getAttribute('data-preview-action-icon-off') || 'fa-check'),
                checked ? button.getAttribute('data-preview-action-label-on') : button.getAttribute('data-preview-action-label-off')
            );
        }
    });
    Array.prototype.slice.call(form.querySelectorAll(
        '[data-modal-preview-action-list=\"1\"] [data-preview-action-group]:not([data-preview-action-bound-input]),' +
        '[data-modal-preview-icon-row=\"1\"] [data-preview-action-group]:not([data-preview-action-bound-input])'
    )).forEach(function(button) {
        if (!button.matches || !button.matches('button')) {
            return;
        }
        if (button.hasAttribute('data-modal-preview-submit-proxy')) {
            return;
        }
        var group = button.getAttribute('data-preview-action-group') || 'shared';
        if (group === 'image') {
            button.hidden = isBorderOnly || isOverlayOnly;
            button.disabled = !hasImage;
            button.classList.toggle('disabled', !hasImage);
            button.setAttribute('aria-disabled', hasImage ? 'false' : 'true');
        } else if (group === 'border') {
            button.hidden = !(isBorderOnly || isExistingBorderLayer);
        } else if (group === 'overlay') {
            button.hidden = !isOverlayOnly;
        } else {
            button.hidden = !(hasImage || isBorderOnly || isExistingBorderLayer || isOverlayOnly);
            if (button.getAttribute('data-action') === 'local-course-banner-builder-toggle-preview-snap' &&
                    (isBorderOnly || isExistingBorderLayer || isOverlayOnly)) {
                button.hidden = true;
            }
        }
    });
    Array.prototype.slice.call(form.querySelectorAll('[data-preview-opacity-panel=\"modal\"]')).forEach(function(panel) {
        if (!(hasImage && !isBorderOnly)) {
            var button = form.querySelector('[data-action=\"local-course-banner-builder-toggle-modal-preview-opacity\"]');
            localCourseBannerBuilderToggleOpacityPanel(panel, button, false);
        }
    });
    Array.prototype.slice.call(form.querySelectorAll('[data-modal-preview-side-panel=\"1\"]')).forEach(function(panel) {
        var group = panel.getAttribute('data-preview-action-group') || 'shared';
        var visible = group === 'border'
            ? (isBorderOnly || isExistingBorderLayer)
            : (group === 'overlay' ? isOverlayOnly : (!isBorderOnly && !isOverlayOnly));
        if (!visible) {
            panel.hidden = true;
            panel.classList.add('is-collapsed');
            var key = panel.getAttribute('data-modal-preview-side-panel-key');
            var button = key ? form.querySelector('[data-modal-preview-side-panel-target=\"' + key + '\"]') : null;
            if (button) {
                button.classList.remove('btn-primary', 'active');
                button.classList.add('btn-outline-secondary');
                button.setAttribute('aria-expanded', 'false');
            }
        }
    });
    if (submitProxy) {
        var canSave = hasExistingElement || hasImage || isBorderOnly || isOverlayOnly;
        submitProxy.disabled = !canSave;
        submitProxy.classList.toggle('disabled', !canSave);
        submitProxy.setAttribute('aria-disabled', canSave ? 'false' : 'true');
    }
    if (deleteDraftButton) {
        var canDeleteDraft = !hasExistingElement && !isBorderOnly && hasActiveDraft;
        deleteDraftButton.hidden = !canDeleteDraft;
        deleteDraftButton.disabled = !canDeleteDraft;
        deleteDraftButton.classList.toggle('disabled', !canDeleteDraft);
        deleteDraftButton.setAttribute('aria-disabled', canDeleteDraft ? 'false' : 'true');
    }
    var fitButton = form.querySelector('[data-action=\"local-course-banner-builder-fit-layer-preview-image\"]');
    if (fitButton) {
        var canFitImage = hasImage && !isBorderOnly && !!currentPreviewLayer && !currentPreviewLayer.hidden;
        fitButton.disabled = !canFitImage;
        fitButton.classList.toggle('disabled', !canFitImage);
        fitButton.setAttribute('aria-disabled', canFitImage ? 'false' : 'true');
    }
    var fillButton = form.querySelector('[data-action=\"local-course-banner-builder-fill-layer-preview-image\"]');
    if (fillButton) {
        var canFillImage = hasImage && !isBorderOnly && !!currentPreviewLayer && !currentPreviewLayer.hidden;
        fillButton.disabled = !canFillImage;
        fillButton.classList.toggle('disabled', !canFillImage);
        fillButton.setAttribute('aria-disabled', canFillImage ? 'false' : 'true');
    }
    localCourseBannerBuilderSyncModalPreviewCropButtons(form);
    localCourseBannerBuilderUpdateModalPreviewHistoryButtons(form);
    var host = form.querySelector('[data-modal-preview-action-list=\"1\"]');
    if (host) {
        localCourseBannerBuilderLayoutModalPreviewActionButtons(host);
    }
    var iconHost = form.querySelector('[data-modal-preview-icon-row=\"1\"]');
    if (iconHost) {
        var visibleIcons = Array.prototype.slice.call(iconHost.querySelectorAll('button')).filter(function(button) {
            return !button.hidden;
        });
        iconHost.hidden = !visibleIcons.length;
    }
    localCourseBannerBuilderSyncPreviewSnapButtons(form);
}

function localCourseBannerBuilderLayoutModalPreviewActionButtons(host) {
    if (!host) {
        return;
    }
    var buttons = Array.prototype.slice.call(host.querySelectorAll('.local-course-banner-builder-source-preview-button'));
    buttons.forEach(function(button) {
        button.classList.remove('local-course-banner-builder-source-preview-button--full');
    });
    var visibleButtons = buttons.filter(function(button) {
        return !button.hidden;
    });
    if (visibleButtons.length > 0 && (visibleButtons.length % 2) === 1) {
        visibleButtons[visibleButtons.length - 1].classList.add('local-course-banner-builder-source-preview-button--full');
    }
}

function localCourseBannerBuilderSyncModalPreviewCropButtons(form) {
    if (!form) {
        return;
    }
    var layer = localCourseBannerBuilderGetEditableCurrentPreviewImage(form);
    var hasImage = !!(layer && !layer.hidden && layer.querySelector('[data-preview-image-tag=\"1\"]'));
    var active = !!(hasImage && layer.querySelector('[data-preview-crop-editor=\"1\"]'));
    var toggle = form.querySelector('[data-action=\"local-course-banner-builder-toggle-modal-preview-crop\"]');
    var apply = form.querySelector('[data-action=\"local-course-banner-builder-apply-modal-preview-crop\"]');
    if (toggle) {
        toggle.disabled = !hasImage;
        toggle.classList.toggle('disabled', !hasImage);
        toggle.classList.toggle('btn-primary', active);
        toggle.classList.toggle('btn-outline-secondary', !active);
        toggle.classList.toggle('active', active);
        toggle.classList.remove(
            'local-course-banner-builder-crop-applied',
            'local-course-banner-builder-preview-toolbar-button--active'
        );
        toggle.setAttribute('aria-disabled', hasImage ? 'false' : 'true');
        toggle.setAttribute('aria-pressed', active ? 'true' : 'false');
    }
    if (apply) {
        apply.hidden = !active;
        apply.disabled = !active;
        apply.setAttribute('aria-disabled', active ? 'false' : 'true');
    }
}

function localCourseBannerBuilderSetPreviewIconPopover(button, label, placement) {
    if (!button) {
        return;
    }
    button.setAttribute('data-toggle', 'popover');
    button.setAttribute('data-trigger', 'hover');
    button.setAttribute('data-placement', placement || 'top');
    button.setAttribute('data-html', 'true');
    button.setAttribute('data-content', '<div class=\"no-overflow\"><p>' + label + '</p></div>');
    button.setAttribute('aria-label', label);
}

function localCourseBannerBuilderConfigurePreviewIconButton(button, iconClass, label) {
    if (!button) {
        return null;
    }
    button.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-visibility-toggle';
    button.innerHTML = '<i class=\"fa ' + iconClass + '\" aria-hidden=\"true\"></i><span class=\"sr-only\">' +
        label + '</span>';
    localCourseBannerBuilderSetPreviewIconPopover(button, label, 'top');
    return button;
}

function localCourseBannerBuilderCreatePreviewIconButton(action, iconClass, label, group) {
    var button = document.createElement('button');
    button.type = 'button';
    button.setAttribute('data-action', action);
    button.setAttribute('data-preview-action-group', group || 'shared');
    return localCourseBannerBuilderConfigurePreviewIconButton(button, iconClass, label);
}

function localCourseBannerBuilderCreateImageOpacityPanel(prefix, value) {
    var panel = document.createElement('div');
    panel.className = 'local-course-banner-builder-preview-opacity-panel';
    panel.hidden = true;
    panel.setAttribute('data-preview-opacity-panel', prefix);

    var range = document.createElement('input');
    range.type = 'range';
    range.className = 'local-course-banner-builder-range theme-easyedu-range';
    range.min = '0';
    range.max = '100';
    range.step = '1';
    range.value = String(localCourseBannerBuilderClampPercent(value, 100));
    range.setAttribute('data-preview-opacity-range', prefix);
    range.setAttribute('aria-label', localCourseBannerBuilderGetJsString('imageopacity', 'Image opacity'));

    var number = document.createElement('input');
    number.type = 'number';
    number.className = 'form-control form-control-sm';
    number.min = '0';
    number.max = '100';
    number.step = '1';
    number.value = range.value;
    number.setAttribute('data-preview-opacity-number', prefix);
    number.setAttribute('aria-label', localCourseBannerBuilderGetJsString('imageopacity', 'Image opacity'));

    panel.appendChild(range);
    panel.appendChild(number);
    return panel;
}

function localCourseBannerBuilderToggleOpacityPanel(panel, button, forceOpen) {
    if (!panel) {
        return;
    }
    var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : panel.hidden;
    if (shouldOpen) {
        panel.hidden = false;
        panel.classList.add('is-collapsed');
        window.requestAnimationFrame(function() {
            panel.classList.remove('is-collapsed');
        });
    } else {
        panel.classList.add('is-collapsed');
        window.setTimeout(function() {
            if (panel.classList.contains('is-collapsed')) {
                panel.hidden = true;
            }
        }, 190);
    }
    if (button) {
        button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        button.classList.toggle('btn-primary', shouldOpen);
        button.classList.toggle('btn-outline-secondary', !shouldOpen);
        button.classList.toggle('active', shouldOpen);
        button.classList.toggle('local-course-banner-builder-source-preview-button--active', shouldOpen);
    }
}

function localCourseBannerBuilderSetOpacityPanelValue(panel, value) {
    if (!panel) {
        return;
    }
    value = localCourseBannerBuilderClampPercent(value, 100);
    var range = panel.querySelector('[data-preview-opacity-range], [data-source-preview-opacity-range]');
    var number = panel.querySelector('[data-preview-opacity-number], [data-source-preview-opacity-number]');
    if (range) {
        range.value = String(value);
    }
    if (number) {
        number.value = String(value);
    }
}

function localCourseBannerBuilderSyncModalImageOpacityInputFromLayer(form) {
    if (!form) {
        return;
    }
    var imageOpacityInput = form.querySelector('#id_imageopacity');
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"]');
    if (!imageOpacityInput || !currentLayer) {
        return;
    }
    var layerOpacity = currentLayer.getAttribute('data-preview-image-opacity');
    if (layerOpacity === null || layerOpacity === '') {
        return;
    }
    var layerValue = localCourseBannerBuilderClampPercent(layerOpacity, 100);
    var inputValue = imageOpacityInput.value === '' ?
        100 :
        localCourseBannerBuilderClampPercent(imageOpacityInput.value, 100);
    var inputLooksDefault = imageOpacityInput.value === '' || inputValue === 100;
    if (inputLooksDefault && layerValue !== inputValue) {
        imageOpacityInput.value = String(layerValue);
        localCourseBannerBuilderSetOpacityPanelValue(
            form.querySelector('[data-preview-opacity-panel=\"modal\"]'),
            layerValue
        );
    }
}

function localCourseBannerBuilderApplyModalImageOpacity(form, value) {
    if (!form) {
        return;
    }
    value = localCourseBannerBuilderClampPercent(value, 100);
    var imageOpacityInput = form.querySelector('#id_imageopacity');
    if (imageOpacityInput) {
        imageOpacityInput.value = String(value);
    }
    localCourseBannerBuilderSetOpacityPanelValue(
        form.querySelector('[data-preview-opacity-panel=\"modal\"]'),
        value
    );

    var opacityStyle = (value / 100).toFixed(3);
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"]');
    if (currentLayer) {
        currentLayer.setAttribute('data-preview-image-opacity', String(value));
        currentLayer.style.opacity = '';
        var currentImage = currentLayer.querySelector('[data-preview-image-tag=\"1\"]');
        if (currentImage) {
            currentImage.style.opacity = opacityStyle;
        }
    }

    if (form.dataset.activeDraftIndex) {
        var activeIndex = String(form.dataset.activeDraftIndex);
        var settings = localCourseBannerBuilderGetDraftPreviewSettings(form);
        settings[activeIndex] = settings[activeIndex] || {};
        settings[activeIndex].imageopacity = value;
        localCourseBannerBuilderSetDraftPreviewSettings(form, settings);
    }
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
}

function localCourseBannerBuilderEscapeSelectorId(id) {
    if (window.CSS && CSS.escape) {
        return CSS.escape(id);
    }
    return String(id || '').replace(/([ #;?%&,.+*~':\"!^$[\\]()=>|\/@])/g, '\\$1');
}

function localCourseBannerBuilderBuildModalPreviewSnapshot(form) {
    if (!form) {
        return '';
    }
    var fields = [];
    Array.prototype.slice.call(form.querySelectorAll('input, select, textarea')).forEach(function(field) {
        if (!field.id || field.type === 'file' || field.type === 'submit' || field.type === 'button') {
            return;
        }
        if (field.name === 'sesskey' || field.id.indexOf('filemanager') !== -1 || field.name === 'bannerimage') {
            return;
        }
        fields.push({
            id: field.id,
            type: field.type || field.tagName.toLowerCase(),
            value: field.value,
            checked: !!field.checked
        });
    });
    return JSON.stringify(fields);
}

function localCourseBannerBuilderEnsureModalPreviewHistory(form) {
    if (!form) {
        return;
    }
    var undoStack = localCourseBannerBuilderReadJson(form.dataset.modalPreviewUndoStack || '[]', []);
    if (!undoStack.length) {
        undoStack.push(localCourseBannerBuilderBuildModalPreviewSnapshot(form));
        form.dataset.modalPreviewUndoStack = JSON.stringify(undoStack);
        form.dataset.modalPreviewRedoStack = '[]';
    }
    localCourseBannerBuilderUpdateModalPreviewHistoryButtons(form);
}

function localCourseBannerBuilderPushModalPreviewHistory(form) {
    if (!form) {
        return;
    }
    localCourseBannerBuilderEnsureModalPreviewHistory(form);
    var undoStack = localCourseBannerBuilderReadJson(form.dataset.modalPreviewUndoStack || '[]', []);
    var snapshot = localCourseBannerBuilderBuildModalPreviewSnapshot(form);
    if (undoStack[undoStack.length - 1] !== snapshot) {
        undoStack.push(snapshot);
        if (undoStack.length > 40) {
            undoStack.shift();
        }
        form.dataset.modalPreviewUndoStack = JSON.stringify(undoStack);
    }
    form.dataset.modalPreviewRedoStack = '[]';
    localCourseBannerBuilderUpdateModalPreviewHistoryButtons(form);
}

function localCourseBannerBuilderPushModalPreviewHistoryFromControl(control) {
    var form = localCourseBannerBuilderGetLayerScope(control);
    if (form) {
        localCourseBannerBuilderPushModalPreviewHistory(form);
    }
}

function localCourseBannerBuilderApplyModalPreviewSnapshot(form, snapshot) {
    if (!form || !snapshot) {
        return;
    }
    var fields = localCourseBannerBuilderReadJson(snapshot, []);
    fields.forEach(function(state) {
        var field = state.id ? form.querySelector('#' + localCourseBannerBuilderEscapeSelectorId(state.id)) : null;
        if (!field) {
            return;
        }
        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = !!state.checked;
        } else {
            field.value = state.value;
        }
        field.dispatchEvent(new Event('input', {bubbles: true}));
        field.dispatchEvent(new Event('change', {bubbles: true}));
    });
    form.dataset.previewUserChanged = '1';
    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncLayerBannerPreview(form);
    localCourseBannerBuilderSaveActiveDraftPreviewState(form);
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
}

function localCourseBannerBuilderUpdateModalPreviewHistoryButtons(form) {
    if (!form) {
        return;
    }
    var undo = form.querySelector('[data-action=\"local-course-banner-builder-undo-modal-preview-change\"]');
    var redo = form.querySelector('[data-action=\"local-course-banner-builder-redo-modal-preview-change\"]');
    var undoStack = localCourseBannerBuilderReadJson(form.dataset.modalPreviewUndoStack || '[]', []);
    var redoStack = localCourseBannerBuilderReadJson(form.dataset.modalPreviewRedoStack || '[]', []);
    var currentSnapshot = localCourseBannerBuilderBuildModalPreviewSnapshot(form);
    var canUndo = undoStack.length > 1 || (undoStack.length === 1 && undoStack[0] !== currentSnapshot);
    if (undo) {
        undo.disabled = !canUndo;
        undo.classList.toggle('disabled', undo.disabled);
        undo.setAttribute('aria-disabled', undo.disabled ? 'true' : 'false');
    }
    if (redo) {
        redo.disabled = redoStack.length < 1;
        redo.classList.toggle('disabled', redo.disabled);
        redo.setAttribute('aria-disabled', redo.disabled ? 'true' : 'false');
    }
}

function localCourseBannerBuilderEnsureModalOpacityControls(form) {
    if (!form) {
        return;
    }
    var host = form.querySelector('[data-modal-preview-action-list=\"1\"]');
    var imageOpacityInput = form.querySelector('#id_imageopacity');
    if (!host) {
        return;
    }
    if (!imageOpacityInput) {
        imageOpacityInput = document.createElement('input');
        imageOpacityInput.type = 'hidden';
        imageOpacityInput.name = 'imageopacity';
        imageOpacityInput.id = 'id_imageopacity';
        imageOpacityInput.setAttribute('data-image-opacity-input', '1');
        imageOpacityInput.value = '100';
        form.appendChild(imageOpacityInput);
    }
    if (imageOpacityInput.value === '') {
        imageOpacityInput.value = '100';
    }
    localCourseBannerBuilderSyncModalImageOpacityInputFromLayer(form);
    if (host.querySelector('[data-action=\"local-course-banner-builder-toggle-modal-preview-opacity\"]')) {
        localCourseBannerBuilderSetOpacityPanelValue(
            host.querySelector('[data-preview-opacity-panel=\"modal\"]'),
            imageOpacityInput.value || '100'
        );
        return;
    }

    var opacityPanel = localCourseBannerBuilderCreateImageOpacityPanel(
        'modal',
        imageOpacityInput.value || '100'
    );
    opacityPanel.setAttribute('data-preview-action-group', 'image');
    host.appendChild(opacityPanel);

    var opacityButton = document.createElement('button');
    opacityButton.type = 'button';
    opacityButton.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button';
    opacityButton.setAttribute('data-action', 'local-course-banner-builder-toggle-modal-preview-opacity');
    opacityButton.setAttribute('data-preview-action-group', 'image');
    opacityButton.setAttribute('aria-expanded', 'false');
    localCourseBannerBuilderSetActionButtonContent(
        opacityButton,
        'fa-adjust',
        localCourseBannerBuilderGetJsString('toggleimageopacity', 'Adjust image opacity')
    );
    opacityButton.addEventListener('click', function() {
        var shouldOpen = opacityPanel.hidden || opacityPanel.classList.contains('is-collapsed');
        localCourseBannerBuilderCloseOtherModalSidePanels(host, opacityPanel);
        localCourseBannerBuilderToggleOpacityPanel(opacityPanel, opacityButton, shouldOpen);
        localCourseBannerBuilderSetOpacityPanelValue(opacityPanel, imageOpacityInput.value || '100');
    });
    host.appendChild(opacityButton);

    var syncModalOpacity = function(sourceInput, pushHistory) {
        var value = localCourseBannerBuilderClampPercent(
            sourceInput ? sourceInput.value : imageOpacityInput.value,
            100
        );
        if (pushHistory) {
            localCourseBannerBuilderPushModalPreviewHistory(form);
        }
        localCourseBannerBuilderApplyModalImageOpacity(form, value);
    };
    Array.prototype.slice.call(opacityPanel.querySelectorAll('input')).forEach(function(input) {
        input.addEventListener('input', function() {
            syncModalOpacity(input, false);
        });
        input.addEventListener('change', function() {
            syncModalOpacity(input, true);
        });
    });
}

function localCourseBannerBuilderCloseOtherModalSidePanels(host, activePanel) {
    Array.prototype.slice.call(host.querySelectorAll(
        '[data-modal-preview-side-panel=\"1\"], [data-preview-opacity-panel=\"modal\"]'
    )).forEach(function(panel) {
        if (panel !== activePanel) {
            var key = panel.getAttribute('data-modal-preview-side-panel-key');
            var button = key ? host.querySelector('[data-modal-preview-side-panel-target=\"' + key + '\"]') : null;
            if (!button && panel.getAttribute('data-preview-opacity-panel') === 'modal') {
                button = host.querySelector('[data-action=\"local-course-banner-builder-toggle-modal-preview-opacity\"]');
            }
            localCourseBannerBuilderToggleOpacityPanel(panel, button, false);
        }
    });
}

function localCourseBannerBuilderEnhanceModalSidePanelLinkedRanges(panel) {
    if (!panel) {
        return;
    }
    Array.prototype.slice.call(panel.querySelectorAll('[data-percent-slider-wrapper-for]')).forEach(function(wrapper) {
        var targetid = wrapper.getAttribute('data-percent-slider-wrapper-for');
        var target = targetid ? panel.querySelector('#' + localCourseBannerBuilderEscapeSelectorId(targetid)) : null;
        var slider = wrapper.querySelector('[data-percent-slider-for=\"' + targetid + '\"]');
        if (!target || !slider) {
            return;
        }
        var targetrow = target.closest('.fitem, .form-group, .mb-3, .row');
        if (targetrow) {
            var label = targetrow.querySelector('label, .col-form-label');
            var previous = wrapper.previousElementSibling;
            var hastitle = previous && previous.matches &&
                previous.matches('[data-modal-side-range-title=\"' + targetid + '\"]');
            if (label && !hastitle) {
                var title = document.createElement('div');
                title.className = 'local-course-banner-builder-slideshow-side-title';
                title.setAttribute('data-modal-side-range-title', targetid);
                title.textContent = label.textContent.trim();
                wrapper.parentNode.insertBefore(title, wrapper);
            }
            targetrow.classList.add('local-course-banner-builder-modal-side-target-hidden');
        }
        if (!wrapper.querySelector('[data-modal-side-range-number-for=\"' + targetid + '\"]')) {
            var number = document.createElement('input');
            number.type = 'number';
            number.className = 'form-control form-control-sm local-course-banner-builder-slideshow-side-number';
            number.min = target.getAttribute('data-number-min') || slider.min || '';
            number.max = target.getAttribute('data-number-max') || slider.max || '';
            number.step = target.getAttribute('data-number-step') || slider.step || '1';
            number.value = target.value || slider.value || '';
            number.setAttribute('data-modal-side-range-number-for', targetid);
            number.setAttribute('aria-label', target.getAttribute('aria-label') || target.name || targetid);
            wrapper.appendChild(number);
            number.addEventListener('input', function() {
                target.value = number.value;
                target.dispatchEvent(new Event('input', {bubbles: true}));
            });
            number.addEventListener('change', function() {
                target.value = number.value;
                target.dispatchEvent(new Event('change', {bubbles: true}));
            });
        }
        var syncNumber = function() {
            var number = wrapper.querySelector('[data-modal-side-range-number-for=\"' + targetid + '\"]');
            if (number) {
                number.value = target.value || slider.value || '';
            }
        };
        if (wrapper.dataset.modalSideRangeBound !== '1') {
            wrapper.dataset.modalSideRangeBound = '1';
            slider.addEventListener('input', syncNumber);
            slider.addEventListener('change', syncNumber);
            target.addEventListener('input', syncNumber);
            target.addEventListener('change', syncNumber);
        }
        syncNumber();
    });
}

function localCourseBannerBuilderEnsureModalImageControls(form) {
    if (!form) {
        return;
    }
    var host = form.querySelector('[data-modal-preview-action-list=\"1\"]');
    var imageAccordion = form.querySelector('[data-image-options-section=\"1\"]');
    if (!host || !imageAccordion) {
        return;
    }
    var existingButton = host.querySelector('[data-modal-preview-side-panel-target=\"imageoptions\"]');
    imageAccordion.classList.add('local-course-banner-builder-modal-side-panel');
    imageAccordion.classList.add('local-course-banner-builder-image-options-side-panel');
    imageAccordion.setAttribute('data-modal-preview-side-panel', '1');
    imageAccordion.setAttribute('data-modal-preview-side-panel-key', 'imageoptions');
    imageAccordion.setAttribute('data-preview-action-group', 'image');
    if (!existingButton) {
        imageAccordion.hidden = true;
        imageAccordion.classList.add('is-collapsed');
    }
    if (existingButton) {
        if (imageAccordion.parentNode !== host) {
            host.insertBefore(imageAccordion, existingButton);
        }
        var borderToggle = form.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
        existingButton.hidden = !!(borderToggle && borderToggle.checked);
        existingButton.disabled = false;
        existingButton.classList.remove('disabled');
        existingButton.setAttribute('aria-disabled', 'false');
        localCourseBannerBuilderEnhanceModalSidePanelLinkedRanges(imageAccordion);
        return;
    }

    host.appendChild(imageAccordion);
    localCourseBannerBuilderEnhanceModalSidePanelLinkedRanges(imageAccordion);

    var imageOptionsButton = document.createElement('button');
    imageOptionsButton.type = 'button';
    imageOptionsButton.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button ' +
        'local-course-banner-builder-modal-image-options-button';
    imageOptionsButton.setAttribute('data-modal-preview-side-panel-target', 'imageoptions');
    imageOptionsButton.setAttribute('data-preview-action-group', 'image');
    imageOptionsButton.setAttribute('aria-expanded', 'false');
    imageOptionsButton.disabled = true;
    imageOptionsButton.classList.add('disabled');
    imageOptionsButton.setAttribute('aria-disabled', 'true');
    localCourseBannerBuilderSetActionButtonContent(
        imageOptionsButton,
        'fa-sliders',
        localCourseBannerBuilderGetJsString('imagelayeroptions', 'Image layer options')
    );
    imageOptionsButton.addEventListener('click', function() {
        if (imageOptionsButton.disabled) {
            return;
        }
        var shouldOpen = imageAccordion.hidden || imageAccordion.classList.contains('is-collapsed');
        localCourseBannerBuilderCloseOtherModalSidePanels(host, imageAccordion);
        localCourseBannerBuilderToggleOpacityPanel(imageAccordion, imageOptionsButton, shouldOpen);
    });
    host.appendChild(imageOptionsButton);
}

function localCourseBannerBuilderEnsureModalOverlayControls(form) {
    if (!form) {
        return;
    }
    var host = form.querySelector('[data-modal-preview-action-list=\"1\"]');
    var overlayAccordion = form.querySelector('[data-overlay-accordion=\"1\"]');
    if (!host || !overlayAccordion) {
        return;
    }
    var existingButton = host.querySelector('[data-modal-preview-side-panel-target=\"overlaystyle\"]');
    overlayAccordion.classList.add('local-course-banner-builder-modal-side-panel');
    overlayAccordion.classList.add('local-course-banner-builder-overlay-style-side-panel');
    overlayAccordion.setAttribute('data-modal-preview-side-panel', '1');
    overlayAccordion.setAttribute('data-modal-preview-side-panel-key', 'overlaystyle');
    overlayAccordion.setAttribute('data-preview-action-group', 'overlay');
    var summaryTitle = overlayAccordion.querySelector('.local-course-banner-builder-border-summary-title');
    if (summaryTitle) {
        summaryTitle.textContent = localCourseBannerBuilderGetJsString('overlaysettings', 'Overlay settings');
    }
    localCourseBannerBuilderEnhanceOverlaySidePanelControls(form, overlayAccordion);
    if (!existingButton) {
        overlayAccordion.hidden = true;
        overlayAccordion.classList.add('is-collapsed');
    }
    if (existingButton) {
        if (overlayAccordion.parentNode !== host) {
            host.insertBefore(overlayAccordion, existingButton);
        }
        localCourseBannerBuilderEnhanceModalSidePanelLinkedRanges(overlayAccordion);
        return;
    }

    host.appendChild(overlayAccordion);
    localCourseBannerBuilderEnhanceModalSidePanelLinkedRanges(overlayAccordion);

    var overlayButton = document.createElement('button');
    overlayButton.type = 'button';
    overlayButton.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button';
    overlayButton.setAttribute('data-modal-preview-side-panel-target', 'overlaystyle');
    overlayButton.setAttribute('data-preview-action-group', 'overlay');
    overlayButton.setAttribute('aria-expanded', 'false');
    localCourseBannerBuilderSetActionButtonContent(
        overlayButton,
        'fa-adjust',
        localCourseBannerBuilderGetJsString('overlaysettings', 'Overlay settings')
    );
    overlayButton.addEventListener('click', function() {
        var shouldOpen = overlayAccordion.hidden || overlayAccordion.classList.contains('is-collapsed');
        localCourseBannerBuilderCloseOtherModalSidePanels(host, overlayAccordion);
        localCourseBannerBuilderToggleOpacityPanel(overlayAccordion, overlayButton, shouldOpen);
    });
    host.appendChild(overlayButton);
}

function localCourseBannerBuilderGetOverlayControlRow(input) {
    return input ? input.closest('.fitem, .form-group, .mb-3, .row') : null;
}

function localCourseBannerBuilderSetOverlayStyleMode(form, mode) {
    var group = form ? form.querySelector('[data-overlay-style-mode-choice=\"1\"]') : null;
    if (!group) {
        return;
    }
    localCourseBannerBuilderSetSegmentedChoice(
        Array.prototype.slice.call(group.querySelectorAll('[data-overlay-style-mode-option]')),
        mode,
        'data-overlay-style-mode-option'
    );
}

function localCourseBannerBuilderMarkOverlayCustom(form) {
    localCourseBannerBuilderSetOverlayStyleMode(form, 'custom');
}

function localCourseBannerBuilderApplyOverlayInheritance(form) {
    var accordion = form ? form.querySelector('[data-overlay-accordion=\"1\"]') : null;
    if (!accordion) {
        return;
    }
    var color = accordion.getAttribute('data-overlay-inherit-color') || '#000000';
    var opacity = accordion.getAttribute('data-overlay-inherit-opacity') || '38';
    ['overlaybanner', 'overlayslideshow'].forEach(function(prefix) {
        var colorInput = form.querySelector('#id_' + prefix + 'color');
        var colorPicker = form.querySelector('[data-overlay-color-picker=\"' + prefix + '\"]');
        var opacityInput = form.querySelector('#id_' + prefix + 'opacity');
        if (colorInput) {
            colorInput.value = color;
            colorInput.dispatchEvent(new Event('input', {bubbles: true}));
            colorInput.dispatchEvent(new Event('change', {bubbles: true}));
        }
        if (colorPicker) {
            colorPicker.value = color;
            colorPicker.dispatchEvent(new Event('input', {bubbles: true}));
        }
        if (opacityInput) {
            opacityInput.value = opacity;
            opacityInput.dispatchEvent(new Event('input', {bubbles: true}));
            opacityInput.dispatchEvent(new Event('change', {bubbles: true}));
        }
    });
    localCourseBannerBuilderSetOverlayStyleMode(form, 'inherit');
    localCourseBannerBuilderSyncModalOverlayPreview(form);
}

function localCourseBannerBuilderEnhanceOverlaySidePanelControls(form, overlayAccordion) {
    if (!form || !overlayAccordion || overlayAccordion.dataset.overlayControlsEnhanced === '1') {
        return;
    }
    overlayAccordion.dataset.overlayControlsEnhanced = '1';
    var overlayEnabled = localCourseBannerBuilderGetOverlayToggle(form);
    var overlayEnabledRow = localCourseBannerBuilderGetOverlayControlRow(overlayEnabled);
    if (overlayEnabledRow) {
        overlayEnabledRow.hidden = true;
        overlayEnabledRow.setAttribute('aria-hidden', 'true');
    }

    var modeWrap = document.createElement('div');
    modeWrap.className = 'local-course-banner-builder-overlay-mode-wrap mb-2';
    var modeTitle = document.createElement('div');
    modeTitle.className = 'local-course-banner-builder-slideshow-side-title';
    modeTitle.textContent = localCourseBannerBuilderGetJsString('overlaystylemode', 'Overlay style');
    var modeChoice = localCourseBannerBuilderCreateSegmentedChoice(
        [
            {value: 'inherit', label: localCourseBannerBuilderGetJsString('overlaystylemode:inherit', 'Inherit slideshow overlay')},
            {value: 'custom', label: localCourseBannerBuilderGetJsString('overlaystylemode:custom', 'Custom style')}
        ],
        'custom',
        'data-overlay-style-mode-option',
        function(value) {
            if (value === 'inherit') {
                localCourseBannerBuilderApplyOverlayInheritance(form);
            } else {
                localCourseBannerBuilderSetOverlayStyleMode(form, 'custom');
            }
        }
    );
    modeChoice.setAttribute('data-overlay-style-mode-choice', '1');
    modeWrap.appendChild(modeTitle);
    modeWrap.appendChild(modeChoice);
    var firstBodyNode = Array.prototype.slice.call(overlayAccordion.children).find(function(child) {
        return child.tagName && child.tagName.toLowerCase() !== 'summary';
    });
    overlayAccordion.insertBefore(modeWrap, firstBodyNode || null);

    var target = form.querySelector('#id_overlaytarget');
    var targetRow = localCourseBannerBuilderGetOverlayControlRow(target);
    if (target) {
        if (overlayAccordion.getAttribute('data-overlay-site-only') === '1' ||
                target.getAttribute('data-overlay-site-target') === '1') {
            target.value = 'banner';
            if (targetRow) {
                targetRow.hidden = true;
                targetRow.setAttribute('aria-hidden', 'true');
            }
        } else if (!overlayAccordion.querySelector('[data-overlay-target-choice=\"1\"]')) {
            var targetTitle = document.createElement('div');
            targetTitle.className = 'local-course-banner-builder-slideshow-side-title';
            targetTitle.textContent = localCourseBannerBuilderGetJsString('overlaytarget', 'Overlay target');
            var targetChoice = localCourseBannerBuilderCreateSegmentedChoice(
                [
                    {value: 'banner', label: localCourseBannerBuilderGetJsString('overlaytarget:banner', 'Banner only')},
                    {value: 'slideshow', label: localCourseBannerBuilderGetJsString('overlaytarget:slideshow', 'Slideshow only')},
                    {value: 'both', label: localCourseBannerBuilderGetJsString('overlaytarget:both', 'Banner and slideshow')}
                ],
                target.value || 'both',
                'data-overlay-target-option',
                function(value) {
                    target.value = value;
                    target.dispatchEvent(new Event('change', {bubbles: true}));
                    localCourseBannerBuilderSetSegmentedChoice(
                        Array.prototype.slice.call(targetChoice.querySelectorAll('[data-overlay-target-option]')),
                        value,
                        'data-overlay-target-option'
                    );
                    localCourseBannerBuilderMarkOverlayCustom(form);
                }
            );
            targetChoice.setAttribute('data-overlay-target-choice', '1');
            if (targetRow) {
                targetRow.hidden = true;
                targetRow.setAttribute('aria-hidden', 'true');
                overlayAccordion.insertBefore(targetTitle, targetRow.nextSibling);
                overlayAccordion.insertBefore(targetChoice, targetTitle.nextSibling);
            }
        }
    }

    Array.prototype.slice.call(overlayAccordion.querySelectorAll('[data-overlay-color-text]')).forEach(function(input) {
        input.classList.add('local-course-banner-builder-slideshow-hex-input');
        input.addEventListener('input', function() {
            localCourseBannerBuilderMarkOverlayCustom(form);
        });
    });
    Array.prototype.slice.call(overlayAccordion.querySelectorAll('[data-overlay-color-picker]')).forEach(function(input) {
        input.classList.add('local-course-banner-builder-slideshow-color-input');
        input.addEventListener('input', function() {
            localCourseBannerBuilderMarkOverlayCustom(form);
        });
    });
    Array.prototype.slice.call(overlayAccordion.querySelectorAll('#id_overlaybanneropacity, #id_overlayslideshowopacity')).forEach(function(input) {
        input.addEventListener('input', function() {
            localCourseBannerBuilderMarkOverlayCustom(form);
        });
    });

    ['overlaytitleabove', 'overlayborderabove'].forEach(function(name) {
        var input = form.querySelector('#id_' + name + '[type=\"checkbox\"]');
        var row = localCourseBannerBuilderGetOverlayControlRow(input);
        if (!input || !row || overlayAccordion.querySelector('[data-overlay-toggle-button-for=\"#id_' + name + '\"]')) {
            return;
        }
        row.hidden = true;
        row.setAttribute('aria-hidden', 'true');
        var title = document.createElement('div');
        title.className = 'local-course-banner-builder-slideshow-side-title';
        title.textContent = (row.textContent || '').trim() || name;
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm local-course-banner-builder-slideshow-enable-button';
        button.setAttribute('data-overlay-toggle-button-for', '#id_' + name);
        var sync = function() {
            var checked = !!input.checked;
            button.classList.toggle('btn-primary', checked);
            button.classList.toggle('btn-outline-secondary', !checked);
            localCourseBannerBuilderSetActionButtonContent(button, checked ? 'fa-toggle-on' : 'fa-toggle-off', title.textContent);
            button.setAttribute('aria-pressed', checked ? 'true' : 'false');
        };
        button.addEventListener('click', function(event) {
            event.preventDefault();
            input.checked = !input.checked;
            input.dispatchEvent(new Event('change', {bubbles: true}));
            localCourseBannerBuilderMarkOverlayCustom(form);
            sync();
        });
        input.addEventListener('change', sync);
        sync();
        overlayAccordion.insertBefore(title, row.nextSibling);
        overlayAccordion.insertBefore(button, title.nextSibling);
    });
}

function localCourseBannerBuilderSyncModalOverlayPreview(form) {
    if (!form) {
        return;
    }
    var previewRoot = form.querySelector('[data-layer-banner-preview=\"1\"]');
    if (!previewRoot) {
        return;
    }
    var previewFrame = previewRoot.querySelector('[data-banner-preview-frame=\"1\"], [data-border-preview-frame=\"1\"]') || previewRoot;
    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(form);
    var target = form.querySelector('#id_overlaytarget');
    var colorInput = form.querySelector('#id_overlaybannercolor');
    var opacityInput = form.querySelector('#id_overlaybanneropacity');
    var borderAboveInput = form.querySelector('#id_overlayborderabove');
    var titleAboveInput = form.querySelector('#id_overlaytitleabove');
    var overlayAccordion = form.querySelector('[data-overlay-accordion=\"1\"]');
    var overlayEnabled = localCourseBannerBuilderGetSelectedLayerType(form) === 'overlay' ||
        !!(overlayToggle && (overlayToggle.type === 'checkbox' ? overlayToggle.checked : overlayToggle.value === '1'));
    var targetValue = target ? target.value : 'both';
    var show = overlayEnabled && targetValue !== 'slideshow';
    var overlay = previewRoot.querySelector('[data-layer-overlay-preview=\"1\"]');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'local-course-banner-builder-banner-overlay-layer';
        overlay.setAttribute('data-layer-overlay-preview', '1');
    }
    if (overlay.parentNode !== previewFrame) {
        previewFrame.insertBefore(overlay, previewFrame.firstChild);
    }
    overlay.hidden = !show;
    if (show) {
        overlay.style.backgroundColor = localCourseBannerBuilderTitleHexToRgba(
            colorInput ? colorInput.value : '#000000',
            opacityInput ? opacityInput.value : '25'
        );
        overlay.style.zIndex = borderAboveInput && borderAboveInput.checked ? '1001' : '3001';
    }
    var titlePreview = previewFrame.querySelector('[data-layer-overlay-title-preview=\"1\"]');
    if (!titlePreview) {
        titlePreview = document.createElement('div');
        titlePreview.className = 'local-course-banner-builder-banner-title-overlay local-course-banner-builder-layer-overlay-title-preview';
        titlePreview.setAttribute('data-layer-overlay-title-preview', '1');
        previewFrame.appendChild(titlePreview);
    }
    var showTitle = show && overlayAccordion &&
        overlayAccordion.getAttribute('data-overlay-title-preview-enabled') === '1';
    titlePreview.hidden = !showTitle;
    if (showTitle) {
        titlePreview.textContent = overlayAccordion.getAttribute('data-overlay-title-preview-text') ||
            localCourseBannerBuilderGetJsString('previewcoursetitle', 'Course title');
        titlePreview.style.zIndex = titleAboveInput && titleAboveInput.checked ? '3010' : '69';
    }
}

function localCourseBannerBuilderEnsureModalBorderControls(form) {
    if (!form) {
        return;
    }
    var host = form.querySelector('[data-modal-preview-action-list=\"1\"]');
    var borderAccordion = form.querySelector('[data-border-accordion=\"1\"]');
    if (!host || !borderAccordion) {
        return;
    }
    var existingButton = host.querySelector('[data-modal-preview-side-panel-target=\"borderstyle\"]');
    borderAccordion.classList.add('local-course-banner-builder-modal-side-panel');
    borderAccordion.classList.add('local-course-banner-builder-border-style-side-panel');
    borderAccordion.setAttribute('data-modal-preview-side-panel', '1');
    borderAccordion.setAttribute('data-modal-preview-side-panel-key', 'borderstyle');
    borderAccordion.setAttribute('data-preview-action-group', 'border');
    if (!existingButton) {
        borderAccordion.hidden = true;
        borderAccordion.classList.add('is-collapsed');
    }
    if (existingButton) {
        if (borderAccordion.parentNode !== host) {
            host.insertBefore(borderAccordion, existingButton);
        }
        localCourseBannerBuilderEnhanceModalSidePanelLinkedRanges(borderAccordion);
        return;
    }

    host.appendChild(borderAccordion);
    localCourseBannerBuilderEnhanceModalSidePanelLinkedRanges(borderAccordion);

    var borderStyleButton = document.createElement('button');
    borderStyleButton.type = 'button';
    borderStyleButton.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button';
    borderStyleButton.setAttribute('data-modal-preview-side-panel-target', 'borderstyle');
    borderStyleButton.setAttribute('data-preview-action-group', 'border');
    borderStyleButton.setAttribute('aria-expanded', 'false');
    localCourseBannerBuilderSetActionButtonContent(
        borderStyleButton,
        'fa-border-all',
        localCourseBannerBuilderGetJsString('borderstyle', 'Border style')
    );
    borderStyleButton.addEventListener('click', function() {
        var shouldOpen = borderAccordion.hidden || borderAccordion.classList.contains('is-collapsed');
        localCourseBannerBuilderCloseOtherModalSidePanels(host, borderAccordion);
        localCourseBannerBuilderToggleOpacityPanel(borderAccordion, borderStyleButton, shouldOpen);
    });
    host.appendChild(borderStyleButton);
}

function localCourseBannerBuilderSyncBorderStyleChoice(form) {
    var select = form ? form.querySelector('#id_borderstyle') : null;
    var group = form ? form.querySelector('[data-border-style-choice=\"1\"]') : null;
    if (!select || !group) {
        return;
    }
    Array.prototype.slice.call(group.querySelectorAll('[data-border-style-option]')).forEach(function(button) {
        var active = button.getAttribute('data-border-style-option') === select.value;
        button.disabled = !!select.disabled;
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('active', active);
        button.classList.toggle('btn-outline-secondary', !active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function localCourseBannerBuilderEnsureBorderStyleChoice(form) {
    var select = form ? form.querySelector('#id_borderstyle') : null;
    if (!select) {
        return;
    }
    if (select.dataset.borderStyleChoiceBound === '1') {
        localCourseBannerBuilderSyncBorderStyleChoice(form);
        return;
    }
    var group = document.createElement('div');
    group.className = 'btn-group local-course-banner-builder-border-style-choice';
    group.setAttribute('role', 'group');
    group.setAttribute('data-border-style-choice', '1');
    Array.prototype.slice.call(select.options).forEach(function(option) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-outline-secondary';
        button.setAttribute('data-border-style-option', option.value);
        button.setAttribute('aria-pressed', option.selected ? 'true' : 'false');
        button.textContent = option.textContent || option.value;
        button.addEventListener('click', function() {
            if (select.disabled) {
                return;
            }
            select.value = option.value;
            select.dispatchEvent(new Event('change', {bubbles: true}));
            localCourseBannerBuilderSyncBorderStyleChoice(form);
        });
        group.appendChild(button);
    });
    select.classList.add('local-course-banner-builder-visually-hidden-select');
    select.setAttribute('aria-hidden', 'true');
    select.parentNode.insertBefore(group, select.nextSibling);
    select.dataset.borderStyleChoiceBound = '1';
    localCourseBannerBuilderSyncBorderStyleChoice(form);
}

function localCourseBannerBuilderEnsureLayerModalFooter(form, submitLabel) {
    if (!form) {
        return;
    }
    Array.prototype.slice.call(form.querySelectorAll('input[type=\"submit\"], button[type=\"submit\"]')).forEach(
        function(button) {
            if (!button.hasAttribute('data-modal-preview-submit-proxy')) {
                button.hidden = true;
                button.setAttribute('aria-hidden', 'true');
                button.classList.add('local-course-banner-builder-modal-original-submit');
            }
        }
    );
    if (!form.id) {
        form.id = 'local-course-banner-builder-layer-form-' + Math.random().toString(36).slice(2);
    }
    if (form.dataset.layerSubmitPrepareBound !== '1') {
        form.addEventListener('submit', function() {
            localCourseBannerBuilderPrepareLayerFormForSubmit(form);
        });
        form.dataset.layerSubmitPrepareBound = '1';
    }
    var modalContent = form.closest('.modal-content');
    var modalBody = form.closest('.modal-body');
    var footer = modalContent ? modalContent.querySelector('[data-layer-modal-footer=\"1\"]') : null;
    if (!footer) {
        footer = document.createElement('div');
        footer.className = 'modal-footer local-course-banner-builder-slideshow-modal-footer ' +
            'local-course-banner-builder-layer-modal-footer';
        footer.setAttribute('data-layer-modal-footer', '1');
        if (modalContent && modalBody && modalBody.parentNode === modalContent) {
            modalContent.insertBefore(footer, modalBody.nextSibling);
        } else {
            form.appendChild(footer);
        }
    }
    footer.innerHTML = '';
    var submitButton = document.createElement('button');
    submitButton.type = 'button';
    submitButton.className = 'btn btn-primary local-course-banner-builder-compact-save-button';
    submitButton.setAttribute('data-modal-preview-submit-proxy', '1');
    var submitForm = function() {
        if (form.dataset.formSubmitRequested === '1' || form.dataset.formSubmitting === '1') {
            return;
        }
        form.dataset.formSubmitRequested = '1';
        submitButton.disabled = true;
        submitButton.classList.add('disabled');
        submitButton.setAttribute('aria-disabled', 'true');
        localCourseBannerBuilderPrepareLayerFormForSubmit(form);
        var originalSubmit = form.querySelector(
            'input[type=\"submit\"][name=\"submitbutton\"],' +
            'button[type=\"submit\"]:not([data-modal-preview-submit-proxy])'
        );
        if (form.requestSubmit) {
            form.requestSubmit(originalSubmit || undefined);
        } else if (originalSubmit && originalSubmit.click) {
            originalSubmit.click();
        } else {
            form.submit();
        }
    };
    submitButton.addEventListener('click', function(e) {
        e.preventDefault();
        if (submitButton.disabled) {
            return;
        }
        if (localCourseBannerBuilderShouldConfirmChildBorderDisable(form) &&
                form.dataset.childBorderDisableConfirmed !== '1') {
            localCourseBannerBuilderConfirmAction(
                localCourseBannerBuilderGetChildBorderDisableConfirmMessage(form)
            ).then(function(confirmed) {
                if (!confirmed) {
                    return;
                }
                form.dataset.childBorderDisableConfirmed = '1';
                submitForm();
            });
            return;
        }
        if (localCourseBannerBuilderShouldConfirmChildOverlayDisable(form) &&
                form.dataset.childOverlayDisableConfirmed !== '1') {
            localCourseBannerBuilderConfirmAction(
                localCourseBannerBuilderGetChildOverlayDisableConfirmMessage(form)
            ).then(function(confirmed) {
                if (!confirmed) {
                    return;
                }
                form.dataset.childOverlayDisableConfirmed = '1';
                submitForm();
            });
            return;
        }
        delete form.dataset.childBorderDisableConfirmed;
        delete form.dataset.childOverlayDisableConfirmed;
        submitForm();
    });
    localCourseBannerBuilderSetActionButtonContent(
        submitButton,
        'fa-save',
        submitLabel || localCourseBannerBuilderGetJsString('savebannerlayers', 'Save layers')
    );
    footer.appendChild(submitButton);
}

function localCourseBannerBuilderUndoModalPreviewChange(control) {
    var form = localCourseBannerBuilderGetLayerScope(control);
    if (!form) {
        return;
    }
    localCourseBannerBuilderEnsureModalPreviewHistory(form);
    var undoStack = localCourseBannerBuilderReadJson(form.dataset.modalPreviewUndoStack || '[]', []);
    var redoStack = localCourseBannerBuilderReadJson(form.dataset.modalPreviewRedoStack || '[]', []);
    var currentSnapshot = localCourseBannerBuilderBuildModalPreviewSnapshot(form);
    if (undoStack[undoStack.length - 1] !== currentSnapshot) {
        undoStack.push(currentSnapshot);
    }
    if (undoStack.length <= 1) {
        form.dataset.modalPreviewUndoStack = JSON.stringify(undoStack);
        localCourseBannerBuilderUpdateModalPreviewHistoryButtons(form);
        return;
    }
    redoStack.push(undoStack.pop());
    var snapshot = undoStack[undoStack.length - 1];
    form.dataset.modalPreviewUndoStack = JSON.stringify(undoStack);
    form.dataset.modalPreviewRedoStack = JSON.stringify(redoStack);
    localCourseBannerBuilderApplyModalPreviewSnapshot(form, snapshot);
}

function localCourseBannerBuilderRedoModalPreviewChange(control) {
    var form = localCourseBannerBuilderGetLayerScope(control);
    if (!form) {
        return;
    }
    localCourseBannerBuilderEnsureModalPreviewHistory(form);
    var undoStack = localCourseBannerBuilderReadJson(form.dataset.modalPreviewUndoStack || '[]', []);
    var redoStack = localCourseBannerBuilderReadJson(form.dataset.modalPreviewRedoStack || '[]', []);
    if (!redoStack.length) {
        return;
    }
    var snapshot = redoStack.pop();
    undoStack.push(snapshot);
    form.dataset.modalPreviewUndoStack = JSON.stringify(undoStack);
    form.dataset.modalPreviewRedoStack = JSON.stringify(redoStack);
    localCourseBannerBuilderApplyModalPreviewSnapshot(form, snapshot);
}

function localCourseBannerBuilderEnhanceModalPreviewActions(form) {
    if (!form) {
        return;
    }
    var panel = form.querySelector('.local-course-banner-builder-banner-preview-panel');
    if (!panel) {
        return;
    }
    var helpText = panel.querySelector('.form-text.text-muted');
    var previewBlock = panel.querySelector('[data-layer-banner-preview=\"1\"], .local-course-banner-builder-border-preview, .local-course-banner-builder-banner-preview');
    var iconHost = panel.querySelector('[data-modal-preview-icon-row=\"1\"]');
    if (!iconHost) {
        iconHost = document.createElement('div');
        iconHost.className = 'local-course-banner-builder-source-preview-visibility-toggle-row local-course-banner-builder-modal-preview-icon-row';
        iconHost.setAttribute('data-modal-preview-icon-row', '1');
        if (previewBlock && previewBlock.parentNode === panel) {
            previewBlock.insertAdjacentElement('afterend', iconHost);
        } else {
            panel.insertBefore(iconHost, panel.firstChild);
        }
    }

    var host = form.querySelector('[data-modal-preview-action-list=\"1\"]');
    if (!host) {
        host = document.createElement('div');
        host.className = 'local-course-banner-builder-modal-preview-action-list';
        host.setAttribute('data-modal-preview-action-list', '1');
        form.appendChild(host);
    } else if (host.parentNode !== form) {
        form.appendChild(host);
    }
    form.classList.add('local-course-banner-builder-has-preview-proxy');
    var hasImageOptionsSection = !!form.querySelector('[data-image-options-section=\"1\"]');
    var hasBorderOptionsSection = !!form.querySelector('[data-border-accordion=\"1\"]');
    var imageOptionsMissing = hasImageOptionsSection &&
        !host.querySelector('[data-modal-preview-side-panel-target=\"imageoptions\"]');
    var borderStyleMissing = hasBorderOptionsSection &&
        !host.querySelector('[data-modal-preview-side-panel-target=\"borderstyle\"]');
    if (form.dataset.modalPreviewActionsEnhanced === '1' && (imageOptionsMissing || borderStyleMissing)) {
        delete form.dataset.modalPreviewActionsEnhanced;
    }
    if (form.dataset.modalPreviewActionsEnhanced === '1') {
        localCourseBannerBuilderEnsureModalImageControls(form);
        localCourseBannerBuilderEnsureModalOpacityControls(form);
        localCourseBannerBuilderEnsureModalBorderControls(form);
        localCourseBannerBuilderEnsureLayerModalFooter(form);
        localCourseBannerBuilderLayoutModalPreviewActionButtons(host);
        localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        localCourseBannerBuilderEnsureModalPreviewHistory(form);
        localCourseBannerBuilderInitPopovers(panel);
        return;
    }
    iconHost.innerHTML = '';
    var preservedBorderAccordion = host.querySelector('[data-border-accordion=\"1\"]');
    if (preservedBorderAccordion) {
        form.appendChild(preservedBorderAccordion);
    }
    var preservedImageAccordion = host.querySelector('[data-image-options-section=\"1\"]');
    if (preservedImageAccordion) {
        form.appendChild(preservedImageAccordion);
    }
    host.innerHTML = '';
    form.dataset.modalPreviewActionsEnhanced = '1';

    iconHost.appendChild(localCourseBannerBuilderCreatePreviewIconButton(
        'local-course-banner-builder-undo-modal-preview-change',
        'fa-undo',
        localCourseBannerBuilderGetJsString('undopreviewchange', 'Undo'),
        'shared'
    ));

    iconHost.appendChild(localCourseBannerBuilderCreatePreviewIconButton(
        'local-course-banner-builder-toggle-preview-snap',
        'fa-magnet',
        localCourseBannerBuilderGetJsString('togglepreviewsnap', 'Snap to alignment guides'),
        'shared'
    ));

    var recenterButton = panel.querySelector('[data-action=\"local-course-banner-builder-recenter-preview-image\"]');
    if (!recenterButton) {
        recenterButton = localCourseBannerBuilderCreatePreviewIconButton(
            'local-course-banner-builder-recenter-preview-image',
            'fa-crosshairs',
            localCourseBannerBuilderGetJsString('recenterpreviewimage', 'Recenter image'),
            'image'
        );
    } else {
        recenterButton.setAttribute('data-preview-action-group', 'image');
        localCourseBannerBuilderConfigurePreviewIconButton(
            recenterButton,
            'fa-crosshairs',
            localCourseBannerBuilderGetJsString('recenterpreviewimage', 'Recenter image')
        );
    }
    iconHost.appendChild(recenterButton);

    iconHost.appendChild(localCourseBannerBuilderCreatePreviewIconButton(
        'local-course-banner-builder-fit-layer-preview-image',
        'fa-compress',
        localCourseBannerBuilderGetJsString('fittopreview', 'Fit to preview'),
        'image'
    ));

    iconHost.appendChild(localCourseBannerBuilderCreatePreviewIconButton(
        'local-course-banner-builder-fill-layer-preview-image',
        'fa-arrows-alt',
        localCourseBannerBuilderGetJsString('fillbannerpreviewimage', 'Fill banner'),
        'image'
    ));

    iconHost.appendChild(localCourseBannerBuilderCreatePreviewIconButton(
        'local-course-banner-builder-push-modal-preview-layer-behind',
        'fa-arrow-down',
        localCourseBannerBuilderGetJsString('pushbehindpreviewlayer', 'Send backward'),
        'image'
    ));

    iconHost.appendChild(localCourseBannerBuilderCreatePreviewIconButton(
        'local-course-banner-builder-pull-modal-preview-layer-forward',
        'fa-arrow-up',
        localCourseBannerBuilderGetJsString('pullforwardpreviewlayer', 'Bring forward'),
        'image'
    ));

    iconHost.appendChild(localCourseBannerBuilderCreatePreviewIconButton(
        'local-course-banner-builder-toggle-modal-preview-crop',
        'fa-crop',
        localCourseBannerBuilderGetJsString('cropimage', 'Crop image'),
        'image'
    ));

    iconHost.appendChild(localCourseBannerBuilderCreatePreviewIconButton(
        'local-course-banner-builder-redo-modal-preview-change',
        'fa-redo',
        localCourseBannerBuilderGetJsString('redopreviewchange', 'Redo'),
        'shared'
    ));

    var deleteDraftButton = document.createElement('button');
    deleteDraftButton.type = 'button';
    deleteDraftButton.className = 'btn btn-danger local-course-banner-builder-source-preview-button';
    deleteDraftButton.setAttribute('data-action', 'local-course-banner-builder-delete-selected-draft-preview-layer');
    deleteDraftButton.setAttribute('data-preview-action-group', 'image');
    localCourseBannerBuilderSetActionButtonContent(
        deleteDraftButton,
        'fa-trash-can',
        localCourseBannerBuilderGetJsString('deleteselectedlayer', 'Delete selected layer')
    );
    host.appendChild(deleteDraftButton);

    var contextToggle = panel.querySelector('[data-preview-context-toggle=\"1\"]');
    var contextToolbar = panel.querySelector('.local-course-banner-builder-banner-preview-toolbar');
    if (contextToolbar) {
        contextToolbar.style.display = 'none';
    }
    if (!contextToggle) {
        contextToggle = document.createElement('input');
        contextToggle.type = 'checkbox';
        contextToggle.id = 'local-course-banner-builder-context-toggle-' + Math.random().toString(36).slice(2);
        contextToggle.setAttribute('data-preview-context-toggle', '1');
        contextToggle.hidden = true;
        panel.appendChild(contextToggle);
    }
    if (contextToggle) {
        var contextButton = document.createElement('button');
        contextButton.type = 'button';
        contextButton.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button ' +
            'local-course-banner-builder-modal-context-button';
        contextButton.setAttribute('data-preview-action-bound-input', '#'+ contextToggle.id);
        contextButton.setAttribute(
            'data-preview-action-label-on',
            localCourseBannerBuilderGetJsString('hideotherlayers', 'Hide other layers')
        );
        contextButton.setAttribute(
            'data-preview-action-label-off',
            localCourseBannerBuilderGetJsString('showotherlayers', 'Show other layers')
        );
        contextButton.setAttribute('data-preview-action-icon-on', 'fa-eye-slash');
        contextButton.setAttribute('data-preview-action-icon-off', 'fa-eye');
        contextButton.setAttribute('data-preview-action-group', 'shared');
        contextButton.addEventListener('click', function() {
            localCourseBannerBuilderToggleCheckboxInput(contextToggle);
            localCourseBannerBuilderSyncContextPreviewVisibility(form);
            localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        });
        host.appendChild(contextButton);
    }

    localCourseBannerBuilderEnsureModalImageControls(form);
    localCourseBannerBuilderEnsureModalOpacityControls(form);
    localCourseBannerBuilderEnsureModalBorderControls(form);

    [
        {
            selector: '#id_dynamicimagesizeenabled',
            on: localCourseBannerBuilderGetJsString('imageaboveborder', 'Image above border'),
            off: localCourseBannerBuilderGetJsString('imagebelowborder', 'Image below border'),
            iconon: 'fa-level-up',
            iconoff: 'fa-level-down',
            group: 'image'
        },
        {
            selector: '#id_customsizekeepaspect',
            on: localCourseBannerBuilderGetJsString('customsizekeepaspect', 'Keep original dimensions'),
            off: localCourseBannerBuilderGetJsString('allowstretchpreviewimage', 'Allow stretch'),
            iconon: 'fa-link',
            iconoff: 'fa-expand',
            group: 'image'
        },
        {
            selector: '#id_borderinnerrounded',
            on: localCourseBannerBuilderGetJsString('borderinnerrounded', 'Round inner corners'),
            off: localCourseBannerBuilderGetJsString('sharpinnercorners', 'Sharp inner corners'),
            iconon: 'fa-circle-o',
            iconoff: 'fa-square-o',
            group: 'border'
        }
    ].forEach(function(config) {
        var input = form.querySelector(config.selector);
        if (!input) {
            return;
        }
        var row = input.closest('.fitem, .form-group, .mb-3, .row');
        if (row) {
            row.classList.add('local-course-banner-builder-border-side-picker-source');
        }
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button';
        button.setAttribute('data-preview-action-bound-input', config.selector);
        button.setAttribute('data-preview-action-label-on', config.on);
        button.setAttribute('data-preview-action-label-off', config.off);
        button.setAttribute('data-preview-action-icon-on', config.iconon);
        button.setAttribute('data-preview-action-icon-off', config.iconoff);
        button.setAttribute('data-preview-action-group', config.group);
        button.addEventListener('click', function() {
            localCourseBannerBuilderPushModalPreviewHistory(form);
            if (config.selector === '#id_customsizekeepaspect') {
                localCourseBannerBuilderTogglePreviewAspectLock(button);
                return;
            }
            var currentState = config.selector === '#id_dynamicimagesizeenabled' ?
                localCourseBannerBuilderReadLayerPreviewStateFromLayer(
                    localCourseBannerBuilderGetEditableCurrentPreviewImage(form)
                ) :
                null;
            input.checked = !input.checked;
            input.dispatchEvent(new Event('change', {bubbles: true}));
            if (currentState) {
                currentState.dynamicimagesizeenabled = !!input.checked;
                localCourseBannerBuilderApplyLayerFormPreviewState(form, currentState);
            } else {
                localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
            }
            localCourseBannerBuilderSyncLayerBannerPreview(form);
            localCourseBannerBuilderSaveActiveDraftPreviewState(form);
            localCourseBannerBuilderSyncModalPreviewActionButtons(form);
        });
        host.appendChild(button);
    });

    var submitField = form.querySelector([
        '.fitem_actionbuttons input[type=\"submit\"]',
        '.fitem_actionbuttons button[type=\"submit\"]',
        '.local-course-banner-builder-submit-actions input[type=\"submit\"]',
        '.local-course-banner-builder-submit-actions button[type=\"submit\"]'
    ].join(','));
    var submitLabel = localCourseBannerBuilderGetJsString('savebannerlayers', 'Save layers');
    if (submitField) {
        Array.prototype.slice.call(form.querySelectorAll(
            '.fitem_actionbuttons, #fgroup_id_buttonar, .local-course-banner-builder-submit-actions'
        )).forEach(function(row) {
            row.hidden = true;
            row.setAttribute('aria-hidden', 'true');
            row.classList.add('local-course-banner-builder-modal-original-submit');
        });
        submitLabel = submitField.value || submitField.textContent.trim() || submitLabel;
        var oldFooter = form.querySelector('[data-layer-modal-footer=\"1\"]');
        if (oldFooter) {
            oldFooter.remove();
        }
    }
    localCourseBannerBuilderEnsureLayerModalFooter(form, submitLabel);

    localCourseBannerBuilderLayoutModalPreviewActionButtons(host);
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
    localCourseBannerBuilderEnsureModalPreviewHistory(form);
    localCourseBannerBuilderInitPopovers(panel);
}

function localCourseBannerBuilderSyncBorderSidePicker(form) {
    if (!form) {
        return;
    }
    var picker = form.querySelector('[data-border-side-picker=\"1\"]');
    if (!picker) {
        return;
    }
    Array.prototype.slice.call(picker.querySelectorAll('[data-border-side-button]')).forEach(function(button) {
        var side = button.getAttribute('data-border-side-button');
        var input = form.querySelector('[data-border-side=\"' + side + '\"][type=\"checkbox\"]');
        var active = !!(input && input.checked);
        button.classList.toggle('is-active', active);
        button.classList.toggle('btn-primary', active);
        button.classList.toggle('btn-outline-secondary', !active);
    });
    var allButton = picker.querySelector('[data-border-side-all-button=\"1\"]');
    var allInput = form.querySelector('[data-border-side-all=\"1\"][type=\"checkbox\"]');
    if (allButton && allInput) {
        var allActive = !!allInput.checked;
        allButton.classList.toggle('is-active', allActive);
        allButton.classList.toggle('btn-primary', allActive);
        allButton.classList.toggle('btn-outline-secondary', !allActive);
    }
}

function localCourseBannerBuilderEnhanceBorderSidePicker(form) {
    if (!form) {
        return;
    }
    var sideInputs = Array.prototype.slice.call(form.querySelectorAll('[data-border-side][type=\"checkbox\"]'));
    if (!sideInputs.length) {
        return;
    }
    var source = sideInputs[0].closest('.fitem, .form-group, .mb-3, .row');
    if (!source) {
        return;
    }
    source.classList.add('local-course-banner-builder-border-side-picker-source');
    var sourceGroup = form.querySelector('#fgroup_id_bordersidesgroup, #fitem_id_bordersidesgroup');
    if (sourceGroup) {
        sourceGroup.classList.add('local-course-banner-builder-border-side-picker-source');
    }
    var picker = form.querySelector('[data-border-side-picker=\"1\"]');
    if (!picker) {
        picker = document.createElement('div');
        picker.className = 'local-course-banner-builder-border-side-picker mt-2';
        picker.setAttribute('data-border-side-picker', '1');
        ['top', 'right', 'bottom', 'left'].forEach(function(side) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-secondary local-course-banner-builder-border-side-button';
            button.setAttribute('data-border-side-button', side);
            button.textContent = side.charAt(0).toUpperCase() + side.slice(1);
            button.addEventListener('click', function() {
                var input = form.querySelector('[data-border-side=\"' + side + '\"][type=\"checkbox\"]');
                localCourseBannerBuilderToggleCheckboxInput(input);
            });
            picker.appendChild(button);
        });
        var allButton = document.createElement('button');
        allButton.type = 'button';
        allButton.className = 'btn btn-outline-secondary local-course-banner-builder-border-side-button local-course-banner-builder-border-side-picker-all';
        allButton.setAttribute('data-border-side-all-button', '1');
        allButton.textContent = localCourseBannerBuilderGetJsString('bordersides:all', '');
        allButton.addEventListener('click', function() {
            var allInput = form.querySelector('[data-border-side-all=\"1\"][type=\"checkbox\"]');
            localCourseBannerBuilderToggleCheckboxInput(allInput);
        });
        picker.appendChild(allButton);
        source.parentNode.insertBefore(picker, source.nextSibling);
    }
    localCourseBannerBuilderSyncBorderSidePicker(form);
}

var localCourseBannerBuilderHoverPopoverCounter = 0;

function localCourseBannerBuilderGetPopoverElements(scope) {
    var root = scope || document;
    var selector = [
        '[data-toggle=\"popover\"]',
        '[data-bs-toggle=\"popover\"]',
        '[data-local-course-banner-builder-hover-popover=\"1\"]'
    ].join(',');
    var nodes = [];
    if (root && root.nodeType === 1 && root.matches && root.matches(selector)) {
        nodes.push(root);
    }
    if (root && root.querySelectorAll) {
        nodes = nodes.concat(Array.prototype.slice.call(root.querySelectorAll(selector)));
    }
    return nodes;
}

function localCourseBannerBuilderGetPopoverTrigger(target) {
    if (!target || !target.closest) {
        return null;
    }
    return target.closest(
        '[data-toggle=\"popover\"], [data-bs-toggle=\"popover\"], ' +
        '[data-local-course-banner-builder-hover-popover=\"1\"]'
    );
}

function localCourseBannerBuilderDisposeBootstrapPopover(node) {
    if (!node) {
        return;
    }
    if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.popover) {
        try {
            window.jQuery(node).popover('dispose');
        } catch (e) {
            // Ignore dispose errors on non-initialized nodes.
        }
    }
    if (window.bootstrap && window.bootstrap.Popover && typeof window.bootstrap.Popover.getInstance === 'function') {
        try {
            var instance = window.bootstrap.Popover.getInstance(node);
            if (instance) {
                instance.dispose();
            }
        } catch (e) {
            // Ignore Bootstrap 5 dispose errors.
        }
    }
}

function localCourseBannerBuilderPrepareHoverPopoverNode(node) {
    if (!node) {
        return;
    }
    localCourseBannerBuilderDisposeBootstrapPopover(node);
    var label = node.getAttribute('title') ||
        node.getAttribute('data-original-title') ||
        node.getAttribute('data-bs-original-title') ||
        node.getAttribute('aria-label') ||
        '';
    if (label) {
        node.setAttribute('data-local-course-banner-builder-popover-label', label);
    }
    node.setAttribute('data-trigger', 'manual');
    node.setAttribute('data-bs-trigger', 'manual');
    node.setAttribute('title', '');
    node.removeAttribute('data-original-title');
    node.removeAttribute('data-bs-original-title');
    node.removeAttribute('aria-describedby');
}

function localCourseBannerBuilderGetHoverPopoverPayload(node) {
    var content = node.getAttribute('data-content') || node.getAttribute('data-bs-content') || '';
    var html = node.getAttribute('data-html') === 'true' || node.getAttribute('data-bs-html') === 'true';
    if (!content) {
        content = node.getAttribute('data-local-course-banner-builder-popover-label') ||
            node.getAttribute('aria-label') ||
            node.getAttribute('title') ||
            '';
        html = false;
    }
    return {
        content: content,
        html: html,
        placement: node.getAttribute('data-placement') || node.getAttribute('data-bs-placement') || 'top'
    };
}

function localCourseBannerBuilderPlaceHoverPopover(node, popover, placement) {
    if (!node || !popover) {
        return;
    }
    var triggerRect = node.getBoundingClientRect();
    var popoverRect = popover.getBoundingClientRect();
    var gap = 8;
    var scrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
    var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft || 0;
    var top;
    var left;

    if (placement === 'left') {
        top = triggerRect.top + scrollTop + ((triggerRect.height - popoverRect.height) / 2);
        left = triggerRect.left + scrollLeft - popoverRect.width - gap;
    } else if (placement === 'right') {
        top = triggerRect.top + scrollTop + ((triggerRect.height - popoverRect.height) / 2);
        left = triggerRect.right + scrollLeft + gap;
    } else if (placement === 'bottom') {
        top = triggerRect.bottom + scrollTop + gap;
        left = triggerRect.left + scrollLeft + ((triggerRect.width - popoverRect.width) / 2);
    } else {
        top = triggerRect.top + scrollTop - popoverRect.height - gap;
        left = triggerRect.left + scrollLeft + ((triggerRect.width - popoverRect.width) / 2);
        if (top < scrollTop + gap) {
            top = triggerRect.bottom + scrollTop + gap;
        }
    }

    var minLeft = scrollLeft + gap;
    var maxLeft = scrollLeft + Math.max(gap, window.innerWidth - popoverRect.width - gap);
    popover.style.top = Math.max(scrollTop + gap, top) + 'px';
    popover.style.left = Math.max(minLeft, Math.min(maxLeft, left)) + 'px';
}

function localCourseBannerBuilderShowHoverPopover(node) {
    if (!node || node.disabled) {
        return;
    }
    localCourseBannerBuilderPrepareHoverPopoverNode(node);
    var payload = localCourseBannerBuilderGetHoverPopoverPayload(node);
    if (!payload.content) {
        return;
    }
    localCourseBannerBuilderDismissOpenPopovers();

    var popover = document.createElement('div');
    var id = 'local-course-banner-builder-hover-popover-' + (++localCourseBannerBuilderHoverPopoverCounter);
    popover.id = id;
    popover.className = 'popover local-course-banner-builder-hover-popover ' +
        'local-course-banner-builder-hover-popover--' + payload.placement + ' show';
    popover.setAttribute('role', 'tooltip');
    popover.setAttribute('aria-hidden', 'true');

    var arrow = document.createElement('div');
    arrow.className = 'popover-arrow';
    popover.appendChild(arrow);

    var body = document.createElement('div');
    body.className = 'popover-body';
    if (payload.html) {
        body.innerHTML = payload.content;
    } else {
        body.textContent = payload.content;
    }
    popover.appendChild(body);
    document.body.appendChild(popover);
    node.setAttribute('aria-describedby', id);

    window.requestAnimationFrame(function() {
        localCourseBannerBuilderPlaceHoverPopover(node, popover, payload.placement);
    });
}

function localCourseBannerBuilderHideHoverPopover(node) {
    Array.prototype.slice.call(document.querySelectorAll('.local-course-banner-builder-hover-popover')).forEach(function(popover) {
        popover.remove();
    });
    if (node && node.removeAttribute) {
        node.removeAttribute('aria-describedby');
    }
}

function localCourseBannerBuilderHandlePopoverMouseEnter(event) {
    localCourseBannerBuilderShowHoverPopover(event.currentTarget);
}

function localCourseBannerBuilderHandlePopoverMouseLeave(event) {
    localCourseBannerBuilderHideHoverPopover(event.currentTarget);
}

function localCourseBannerBuilderHandlePopoverFocus(event) {
    localCourseBannerBuilderHideHoverPopover(event.currentTarget);
    if (event.currentTarget && event.currentTarget.blur) {
        event.currentTarget.blur();
    }
}

function localCourseBannerBuilderHandlePopoverClick(event) {
    localCourseBannerBuilderHideHoverPopover(event.currentTarget);
    if (event.currentTarget && !event.currentTarget.hasAttribute('data-action')) {
        event.preventDefault();
    }
    if (event.currentTarget && event.currentTarget.blur) {
        event.currentTarget.blur();
    }
}

function localCourseBannerBuilderBindDelegatedPopovers() {
    if (document.documentElement.getAttribute('data-local-course-banner-builder-popover-delegated') === '1') {
        return;
    }
    document.documentElement.setAttribute('data-local-course-banner-builder-popover-delegated', '1');
    document.addEventListener('mouseover', function(event) {
        var trigger = localCourseBannerBuilderGetPopoverTrigger(event.target);
        if (!trigger || (event.relatedTarget && trigger.contains(event.relatedTarget))) {
            return;
        }
        localCourseBannerBuilderShowHoverPopover(trigger);
    });
    document.addEventListener('mouseout', function(event) {
        var trigger = localCourseBannerBuilderGetPopoverTrigger(event.target);
        if (!trigger || (event.relatedTarget && trigger.contains(event.relatedTarget))) {
            return;
        }
        localCourseBannerBuilderHideHoverPopover(trigger);
    });
    document.addEventListener('click', function(event) {
        var trigger = localCourseBannerBuilderGetPopoverTrigger(event.target);
        if (!trigger) {
            return;
        }
        localCourseBannerBuilderHideHoverPopover(trigger);
        if (!trigger.hasAttribute('data-action')) {
            event.preventDefault();
        }
    }, true);
    document.addEventListener('focusin', function(event) {
        var trigger = localCourseBannerBuilderGetPopoverTrigger(event.target);
        if (trigger) {
            localCourseBannerBuilderHideHoverPopover(trigger);
        }
    }, true);
}

function localCourseBannerBuilderInitPopovers(scope) {
    localCourseBannerBuilderBindDelegatedPopovers();
    localCourseBannerBuilderGetPopoverElements(scope).forEach(function(node) {
        localCourseBannerBuilderPrepareHoverPopoverNode(node);
    });
}

function localCourseBannerBuilderPrimeHelpBubbles(scope) {
    var root = scope || document;
    var bubbles = [];
    if (root.matches && root.matches('.local-course-banner-builder-help-bubble')) {
        bubbles.push(root);
    }
    bubbles = bubbles.concat(Array.prototype.slice.call(
        root.querySelectorAll ? root.querySelectorAll('.local-course-banner-builder-help-bubble') : []
    ));
    bubbles.forEach(function(details) {
        details.open = true;
        var summary = details.querySelector('summary');
        if (summary) {
            summary.removeAttribute('title');
        }
    });
}

function localCourseBannerBuilderDismissOpenPopovers(activeElement) {
    if (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.popover) {
        try {
            window.jQuery('[data-toggle=\"popover\"], [data-bs-toggle=\"popover\"]').popover('hide');
        } catch (e) {
            // Ignore hide errors on nodes not initialized by Bootstrap.
        }
    }
    if (window.bootstrap && window.bootstrap.Popover && typeof window.bootstrap.Popover.getInstance === 'function') {
        localCourseBannerBuilderGetPopoverElements(document).forEach(function(node) {
            try {
                var instance = window.bootstrap.Popover.getInstance(node);
                if (instance) {
                    instance.hide();
                }
            } catch (e) {
                // Ignore Bootstrap 5 hide errors.
            }
        });
    }
    if (activeElement && activeElement.getAttribute) {
        var describedBy = activeElement.getAttribute('aria-describedby');
        if (describedBy) {
            var describedNode = document.getElementById(describedBy);
            if (describedNode && describedNode.classList.contains('popover')) {
                describedNode.remove();
            }
            activeElement.removeAttribute('aria-describedby');
        }
        if (activeElement.blur) {
            activeElement.blur();
        }
    }
    Array.prototype.slice.call(document.querySelectorAll('.local-course-banner-builder-hover-popover')).forEach(function(popover) {
        popover.remove();
    });
}

function localCourseBannerBuilderToggleInlineSetting(row, editing) {
    if (!row) {
        return;
    }
    row.classList.toggle('is-editing', !!editing);
    var select = row.querySelector('[data-inline-setting-select]');
    if (!select) {
        return;
    }
    if (!editing) {
        select.value = select.getAttribute('data-initial-value') || select.value;
        var dropdown = row.querySelector('[data-source-dropdown]');
        if (dropdown) {
            localCourseBannerBuilderSyncSourceDropdownButton(dropdown);
        }
    }
    select.disabled = !editing;
    if (editing) {
        window.setTimeout(function() {
            var dropdownButton = row.querySelector('[data-source-dropdown-label]');
            if (select.type === 'hidden' && dropdownButton) {
                dropdownButton.focus();
            } else {
                select.focus();
            }
        }, 0);
    }
}

function localCourseBannerBuilderShowModal(modal) {
    if (!modal) {
        return false;
    }
    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery(modal).modal === 'function') {
        window.jQuery(modal).modal('show');
        return true;
    }
    if (window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modal).show();
        return true;
    }
    modal.style.display = 'block';
    modal.removeAttribute('aria-hidden');
    modal.setAttribute('aria-modal', 'true');
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    if (!document.querySelector('.modal-backdrop.local-course-banner-builder-modal-backdrop')) {
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show local-course-banner-builder-modal-backdrop';
        document.body.appendChild(backdrop);
    }
    return true;
}

function localCourseBannerBuilderIsNativeAdmin() {
    return !!document.querySelector('.local-course-banner-builder-admin--native');
}

function localCourseBannerBuilderGetOpenModal() {
    var modals = Array.prototype.slice.call(document.querySelectorAll('.modal.show'));
    return modals.length ? modals[modals.length - 1] : null;
}

function localCourseBannerBuilderForceHideModal(modal) {
    if (!modal) {
        return;
    }
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');
    modal.removeAttribute('role');
    if (document.activeElement && modal.contains(document.activeElement)) {
        try {
            document.activeElement.blur();
        } catch (e) {
            // Ignore focus cleanup failures from browser-managed controls.
        }
    }
    if (!document.querySelector('.modal.show')) {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
        Array.prototype.slice.call(document.querySelectorAll('.modal-backdrop')).forEach(function(backdrop) {
            backdrop.remove();
        });
    }
}

function localCourseBannerBuilderHideModal(modal) {
    if (!modal) {
        return;
    }
    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery(modal).modal === 'function') {
        window.jQuery(modal).modal('hide');
        window.setTimeout(function() {
            if (modal.classList.contains('show') || modal.style.display === 'block') {
                localCourseBannerBuilderForceHideModal(modal);
                return;
            }
            if (!document.querySelector('.modal.show')) {
                document.body.classList.remove('modal-open');
            }
        }, 240);
        return;
    }
    if (window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modal).hide();
        window.setTimeout(function() {
            if (modal.classList.contains('show') || modal.style.display === 'block') {
                localCourseBannerBuilderForceHideModal(modal);
                return;
            }
            if (!document.querySelector('.modal.show')) {
                document.body.classList.remove('modal-open');
            }
        }, 240);
        return;
    }
    localCourseBannerBuilderForceHideModal(modal);
}

function localCourseBannerBuilderConfirmAction(message) {
    return new Promise(function(resolve) {
        var oldModal = document.getElementById('local-course-banner-builder-confirm-action-modal');
        if (oldModal) {
            oldModal.remove();
        }

        var modal = document.createElement('div');
        modal.id = 'local-course-banner-builder-confirm-action-modal';
        modal.className = 'modal fade local-course-banner-builder-confirm-action-modal';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');

        var dialog = document.createElement('div');
        dialog.className = 'modal-dialog modal-dialog-centered';
        dialog.setAttribute('role', 'document');
        modal.appendChild(dialog);

        var content = document.createElement('div');
        content.className = 'modal-content local-course-banner-builder-layer-modal-content';
        dialog.appendChild(content);

        var header = document.createElement('div');
        header.className = 'modal-header d-flex align-items-center';
        content.appendChild(header);

        var title = document.createElement('h5');
        title.className = 'modal-title flex-grow-1';
        title.textContent = localCourseBannerBuilderGetJsString('areyousure', '');
        header.appendChild(title);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'close ml-auto ms-auto';
        close.setAttribute('aria-label', localCourseBannerBuilderGetJsString('no', ''));
        close.innerHTML = '<span aria-hidden=\"true\">&times;</span>';
        header.appendChild(close);

        var body = document.createElement('div');
        body.className = 'modal-body';
        body.textContent = message || localCourseBannerBuilderGetJsString('areyousure', '');
        content.appendChild(body);

        var footer = document.createElement('div');
        footer.className = 'modal-footer';
        content.appendChild(footer);

        var cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-outline-secondary';
        cancel.textContent = localCourseBannerBuilderGetJsString('no', '');
        footer.appendChild(cancel);

        var confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'btn btn-danger';
        confirm.textContent = localCourseBannerBuilderGetJsString('yes', '');
        footer.appendChild(confirm);

        var settled = false;
        var finish = function(value) {
            if (settled) {
                return;
            }
            settled = true;
            localCourseBannerBuilderHideModal(modal);
            window.setTimeout(function() {
                modal.remove();
            }, 180);
            resolve(value);
        };

        close.addEventListener('click', function() {
            finish(false);
        });
        cancel.addEventListener('click', function() {
            finish(false);
        });
        confirm.addEventListener('click', function() {
            finish(true);
        });
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                finish(false);
            }
        });
        document.body.appendChild(modal);
        localCourseBannerBuilderShowModal(modal);
        confirm.focus();
    });
}

function localCourseBannerBuilderRestoreCreateLayerModal() {
    var modal = document.getElementById('local-course-banner-builder-add-layer-modal');
    if (!modal) {
        return;
    }
    var title = modal.querySelector('.modal-title');
    if (title && modal.dataset.defaultTitle !== undefined) {
        title.textContent = modal.dataset.defaultTitle;
    }
    var form = modal.querySelector('form.mform');
    if (!form) {
        return;
    }
    form.removeAttribute('data-edit-border-locked');
    localCourseBannerBuilderSetSelectedLayerType(form, 'image');
    form.dataset.activeDraftIndex = '';
    delete form.dataset.modalPreviewActionsEnhanced;
    delete form.dataset.previewMovedToTop;
    delete form.dataset.borderSidesValue;
    var snapshot = {};
    try {
        snapshot = modal.dataset.createDefaults ? JSON.parse(modal.dataset.createDefaults) : {};
    } catch (e) {
        snapshot = {};
    }

    Array.prototype.slice.call(form.querySelectorAll('input, select, textarea')).forEach(function(field) {
        if (!field.name || !(field.name in snapshot)) {
            return;
        }
        if (field.type === 'checkbox') {
            field.checked = !!snapshot[field.name];
        } else {
            field.value = snapshot[field.name];
        }
    });

    var borderAccordion = form.querySelector('[data-border-accordion=\"1\"]');
    if (borderAccordion) {
        borderAccordion.open = false;
    }

    var elementIdInput = form.querySelector('#id_elementid');
    if (elementIdInput) {
        elementIdInput.value = '0';
    }
    var hasExistingImageInput = form.querySelector('#id_hasexistingimage');
    if (hasExistingImageInput) {
        hasExistingImageInput.value = '0';
    }
    var currentIsBorderLayerInput = form.querySelector('#id_currentisborderlayer');
    if (currentIsBorderLayerInput) {
        currentIsBorderLayerInput.value = '0';
    }
    var currentIsOverlayLayerInput = form.querySelector('#id_currentisoverlaylayer');
    if (currentIsOverlayLayerInput) {
        currentIsOverlayLayerInput.value = '0';
    }
    var borderSidesValueInput = form.querySelector('#id_bordersidesvalue');
    if (borderSidesValueInput) {
        borderSidesValueInput.value = 'top,right,bottom,left';
    }
    var multiDraftSettingsInput = form.querySelector('#id_multilayerdraftsettings');
    if (multiDraftSettingsInput) {
        multiDraftSettingsInput.value = '';
    }
    var borderToggle = form.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    if (borderToggle) {
        borderToggle.checked = false;
        var sourceHasBorderLayerInput = form.querySelector('#id_sourcehasborderlayer');
        var sourceHasBorderLayer = !!(sourceHasBorderLayerInput && parseInt(sourceHasBorderLayerInput.value || '0', 10) > 0);
        borderToggle.disabled = sourceHasBorderLayer;
        borderToggle.setAttribute('aria-disabled', sourceHasBorderLayer ? 'true' : 'false');
        var borderToggleWrapper = borderToggle.closest('.form-check, .fitem, .felement');
        if (borderToggleWrapper) {
            borderToggleWrapper.classList.remove('local-course-banner-builder-option-disabled');
        }
    }
    var overlayToggle = localCourseBannerBuilderGetOverlayToggle(form);
    if (overlayToggle) {
        if (overlayToggle.type === 'checkbox') {
            overlayToggle.checked = false;
        } else {
            overlayToggle.value = '0';
        }
        var sourceHasOverlayLayerInput = form.querySelector('#id_sourcehasoverlaylayer');
        var sourceHasOverlayLayer = !!(
            sourceHasOverlayLayerInput && parseInt(sourceHasOverlayLayerInput.value || '0', 10) > 0
        );
        overlayToggle.disabled = sourceHasOverlayLayer;
        overlayToggle.setAttribute('aria-disabled', sourceHasOverlayLayer ? 'true' : 'false');
    }
    localCourseBannerBuilderSyncLayerTypeChoice(form);
    var filemanagerItem = form.querySelector('#fitem_id_bannerimage_filemanager');
    if (filemanagerItem) {
        filemanagerItem.hidden = false;
        filemanagerItem.classList.remove('local-course-banner-builder-is-disabled');
        filemanagerItem.setAttribute('aria-disabled', 'false');
    }
    var filemanagerNote = form.querySelector('[data-border-filemanager-note=\"1\"]');
    var filemanagerNoteItem = filemanagerNote ? filemanagerNote.closest('.fitem, .form-group, .mb-3') : null;
    if (filemanagerNoteItem) {
        filemanagerNoteItem.hidden = true;
    }

    localCourseBannerBuilderSyncBulkFields();
    localCourseBannerBuilderPopulateBorderSidesFromValue('top,right,bottom,left', form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncBorderSideGroup(form);
    localCourseBannerBuilderSyncBorderRoundedField(form);
    localCourseBannerBuilderSyncBorderSidesValue(form);
    localCourseBannerBuilderUpgradeRanges();
    localCourseBannerBuilderUpgradeNumberInputs();
    localCourseBannerBuilderUpgradeColorPickers(form);
    localCourseBannerBuilderBindColourInputs(form);
    localCourseBannerBuilderSyncDashedControls(form);
    localCourseBannerBuilderSyncLayerInputModes(form);
    localCourseBannerBuilderSyncBorderPreview(form);
    localCourseBannerBuilderSyncDraftUploadPreview(form);
    localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
    localCourseBannerBuilderEnhanceModalPreviewActions(form);
    localCourseBannerBuilderEnhanceBorderSidePicker(form);
    localCourseBannerBuilderBindLayerFormEvents(form);
    localCourseBannerBuilderBindLayerPreviewEvents(form);
    localCourseBannerBuilderSyncDetailsCollapseIcons(form);
}

function localCourseBannerBuilderLoadCreateLayerModal() {
    var modal = document.getElementById('local-course-banner-builder-add-layer-modal');
    if (!modal) {
        return false;
    }
    localCourseBannerBuilderRestoreCreateLayerModal();
    var opened = localCourseBannerBuilderShowModal(modal);
    if (opened && typeof window.jQuery !== 'undefined') {
        window.jQuery(modal).one('shown.bs.modal', function() {
            var form = modal.querySelector('form.mform');
            localCourseBannerBuilderSyncLayerInputModes(form);
            localCourseBannerBuilderSyncBorderPreview(form);
            localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
            localCourseBannerBuilderEnhanceModalPreviewActions(form);
            localCourseBannerBuilderEnhanceBorderSidePicker(form);
            localCourseBannerBuilderBindColourInputs(form);
            localCourseBannerBuilderSyncDetailsCollapseIcons(modal);
            localCourseBannerBuilderPrimeHelpBubbles(modal);
        });
    } else {
        var form = modal.querySelector('form.mform');
        localCourseBannerBuilderSyncLayerInputModes(form);
        localCourseBannerBuilderSyncBorderPreview(form);
        localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
        localCourseBannerBuilderEnhanceModalPreviewActions(form);
        localCourseBannerBuilderEnhanceBorderSidePicker(form);
        localCourseBannerBuilderBindColourInputs(form);
        localCourseBannerBuilderSyncDetailsCollapseIcons(modal);
        localCourseBannerBuilderPrimeHelpBubbles(modal);
    }
    return opened;
}

function localCourseBannerBuilderFindFetchedLayerModal(doc) {
    return doc.getElementById('local-course-banner-builder-edit-border-layer-modal') ||
        doc.getElementById('local-course-banner-builder-edit-image-layer-modal') ||
        doc.getElementById('local-course-banner-builder-add-layer-modal');
}

function localCourseBannerBuilderPrepareDynamicLayerModal(modal) {
    var form = modal ? modal.querySelector('form.mform') : null;
    if (!form) {
        return;
    }
    form.dataset.previewUserChanged = '0';
    localCourseBannerBuilderSyncBulkFields();
    localCourseBannerBuilderApplyStoredBorderStateDeferred(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    localCourseBannerBuilderSyncBorderSideGroup(form);
    localCourseBannerBuilderSyncBorderRoundedField(form);
    localCourseBannerBuilderSyncBorderSidesValue(form);
    localCourseBannerBuilderUpgradeRanges();
    localCourseBannerBuilderUpgradeNumberInputs();
    localCourseBannerBuilderUpgradeColorPickers(modal);
    localCourseBannerBuilderBindColourInputs(modal);
    localCourseBannerBuilderSyncDashedControls(form);
    localCourseBannerBuilderSyncBorderPreview(form);
    localCourseBannerBuilderSyncLayerInputModes(form);
    localCourseBannerBuilderBindLayerFormEvents(form);
    localCourseBannerBuilderBindLayerPreviewEvents(form);
    localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
    localCourseBannerBuilderEnhanceModalPreviewActions(form);
    localCourseBannerBuilderEnhanceBorderSidePicker(form);
    localCourseBannerBuilderAlignModalActionButtons(modal);
    localCourseBannerBuilderEnhanceAccordions(modal);
    localCourseBannerBuilderSyncDetailsCollapseIcons(modal);
    localCourseBannerBuilderInitPopovers(modal);
    localCourseBannerBuilderPrimeHelpBubbles(modal);
    window.requestAnimationFrame(function() {
        localCourseBannerBuilderRefreshCurrentPreviewLayer(form);
        window.requestAnimationFrame(function() {
            localCourseBannerBuilderRefreshCurrentPreviewLayer(form);
        });
    });
}

function localCourseBannerBuilderSafelyPrepareDynamicLayerModal(modal) {
    try {
        localCourseBannerBuilderPrepareDynamicLayerModal(modal);
    } catch (error) {
        window.console.error(error);
    }
}

function localCourseBannerBuilderAlignModalActionButtons(root) {
    var scope = root || document;
    var modals = [];
    if (scope.nodeType === 1 && scope.matches && scope.matches('.modal[id^=\"local-course-banner-builder-\"]')) {
        modals.push(scope);
    }
    modals = modals.concat(Array.prototype.slice.call(scope.querySelectorAll ? scope.querySelectorAll('.modal[id^=\"local-course-banner-builder-\"]') : []));

    modals.forEach(function(modal) {
        Array.prototype.slice.call(modal.querySelectorAll('form input[type=\"submit\"], form button[type=\"submit\"]')).forEach(function(button) {
            if (button.closest('[data-modal-preview-action-list=\"1\"]') || button.hasAttribute('data-modal-preview-submit-proxy')) {
                return;
            }
            var row = button.closest('.fitem_actionbuttons, #fgroup_id_buttonar, .fitem, .form-group, .mb-3, .row, .text-end');
            if (!row || row.tagName === 'FORM') {
                row = button.parentElement;
            }
            if (!row || row.tagName === 'FORM') {
                return;
            }
            row.classList.add('local-course-banner-builder-submit-actions');
            row.style.display = 'flex';
            row.style.justifyContent = 'flex-end';
            row.style.alignItems = 'center';
            row.style.width = '100%';
            row.style.maxWidth = '100%';
            row.style.marginLeft = 'auto';
            button.style.marginLeft = 'auto';
            button.style.display = 'inline-flex';
        });
    });

    Array.prototype.slice.call(scope.querySelectorAll ? scope.querySelectorAll('.modal[id^=\"local-course-banner-builder-\"] .fitem_actionbuttons') : []).forEach(function(row) {
        var rowItem = row.closest('.fitem, .form-group, .mb-3, .row');
        if (rowItem) {
            rowItem.style.display = 'flex';
            rowItem.style.justifyContent = 'flex-end';
            rowItem.style.width = '100%';
        }
        row.style.display = 'flex';
        row.style.justifyContent = 'flex-end';
        row.style.alignItems = 'center';
        row.style.width = '100%';
        row.style.marginLeft = 'auto';
        Array.prototype.slice.call(row.querySelectorAll('.felement, .col-md-9, fieldset, .fgroup, #fgroup_id_buttonar')).forEach(function(container) {
            container.style.display = 'flex';
            container.style.justifyContent = 'flex-end';
            container.style.alignItems = 'center';
            container.style.width = '100%';
            container.style.maxWidth = '100%';
            container.style.flex = '0 0 100%';
            container.style.marginLeft = 'auto';
        });
        Array.prototype.slice.call(row.querySelectorAll('input[type=\"submit\"], button[type=\"submit\"]')).forEach(function(button) {
            if (button.closest('[data-modal-preview-action-list=\"1\"]') || button.hasAttribute('data-modal-preview-submit-proxy')) {
                return;
            }
            button.style.marginLeft = 'auto';
            button.style.display = 'inline-flex';
        });
    });
}

function localCourseBannerBuilderRehydrateSelectedSourceContent(root) {
    var scope = root || document;
    localCourseBannerBuilderSyncSelectionButton();
    localCourseBannerBuilderSyncLayerSortOrders();
    localCourseBannerBuilderSyncBulkFields();
    localCourseBannerBuilderInitSourceVisualEditor(scope);
    localCourseBannerBuilderSyncDetailsCollapseIcons(scope);
    localCourseBannerBuilderEnhanceAccordions(scope);
    localCourseBannerBuilderInitPopovers(scope);
    localCourseBannerBuilderPrimeHelpBubbles(scope);
    localCourseBannerBuilderSyncStickyHeader();
}

function localCourseBannerBuilderSyncLayerModalAfterContextChange(form) {
    if (!form) {
        return;
    }
    localCourseBannerBuilderSyncContextPreviewVisibility(form);
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
}

function localCourseBannerBuilderRemoveLayerFromLayerModals(layerId) {
    if (!layerId) {
        return;
    }
    var escapedLayerId = localCourseBannerBuilderEscapeSelectorId(String(layerId));
    var touchedForms = [];
    Array.prototype.slice.call(document.querySelectorAll(
        '.modal[id^=\"local-course-banner-builder-\"] [data-preview-layer-id=\"' + escapedLayerId + '\"]'
    )).forEach(function(layer) {
        var form = localCourseBannerBuilderGetLayerScope(layer);
        if (form && touchedForms.indexOf(form) === -1) {
            touchedForms.push(form);
        }
        layer.remove();
    });
    touchedForms.forEach(localCourseBannerBuilderSyncLayerModalAfterContextChange);
}

function localCourseBannerBuilderRemoveOwnSourceLayersFromLayerModals() {
    var touchedForms = [];
    Array.prototype.slice.call(document.querySelectorAll(
        '.modal[id^=\"local-course-banner-builder-\"] [data-preview-context-layer=\"1\"]:not([data-preview-inherited=\"1\"])'
    )).forEach(function(layer) {
        var form = localCourseBannerBuilderGetLayerScope(layer);
        if (form && touchedForms.indexOf(form) === -1) {
            touchedForms.push(form);
        }
        layer.remove();
    });
    touchedForms.forEach(localCourseBannerBuilderSyncLayerModalAfterContextChange);
}

function localCourseBannerBuilderDeleteAllLayers(button) {
    if (!button) {
        return;
    }
    var form = document.getElementById('local-course-banner-builder-delete-all-layers');
    if (!form) {
        return;
    }
    var message = button.getAttribute('data-confirm-message') ||
        localCourseBannerBuilderGetJsString('areyousure', '');
    localCourseBannerBuilderConfirmAction(message).then(function(confirmed) {
        if (!confirmed) {
            return;
        }
        var formData = new FormData(form);
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function(response) {
            if (!response.ok) {
                throw new Error(localCourseBannerBuilderGetJsString(
                    'unabletodeletealllayers',
                    'Unable to delete all layers'
                ));
            }
            return response.json();
        }).then(function(data) {
            if (!data || !data.success || typeof data.html !== 'string') {
                throw new Error(localCourseBannerBuilderGetJsString(
                    'invaliddeletealllayersresponse',
                    'Invalid delete-all-layers response'
                ));
            }
            var host = document.querySelector('[data-selected-source-content=\"1\"]');
            if (!host) {
                return;
            }
            host.innerHTML = data.html;
            var replacement = host.querySelector('[data-selected-source-content=\"1\"]');
            if (replacement) {
                host.replaceWith(replacement);
                host = replacement;
            }
            localCourseBannerBuilderRehydrateSelectedSourceContent(host);
            localCourseBannerBuilderRemoveOwnSourceLayersFromLayerModals();
        }).catch(function(error) {
            window.console.error(error);
        });
    });
}

function localCourseBannerBuilderReplaceSelectedSourceContentFromDeleteResponse(data) {
    if (!data || !data.success || typeof data.html !== 'string') {
        throw new Error(localCourseBannerBuilderGetJsString(
            'invaliddeleteselectedlayerresponse',
            'Invalid delete-selected-layer response'
        ));
    }
    var host = document.querySelector('[data-selected-source-content=\"1\"]');
    if (!host) {
        return null;
    }
    host.innerHTML = data.html;
    var replacement = host.querySelector('[data-selected-source-content=\"1\"]');
    if (replacement) {
        host.replaceWith(replacement);
        host = replacement;
    }
    localCourseBannerBuilderRehydrateSelectedSourceContent(host);
    return host;
}

function localCourseBannerBuilderDeleteSelectedLayers(button) {
    if (!button) {
        return;
    }
    var form = document.getElementById('local-course-banner-builder-bulk-delete');
    if (!form) {
        return;
    }
    var selected = document.querySelectorAll(
        'input[name=\"selectedelements[]\"][form=\"local-course-banner-builder-bulk-delete\"]:checked'
    );
    if (!selected.length) {
        return;
    }
    var message = button.getAttribute('data-confirm-message') ||
        localCourseBannerBuilderGetJsString('areyousure', '');
    localCourseBannerBuilderConfirmAction(message).then(function(confirmed) {
        if (!confirmed) {
            return;
        }
        var formData = new FormData(form);
        formData.delete('deleteselectedlayers');
        formData.append('deleteselectedlayersajax', '1');
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function(response) {
            if (!response.ok) {
                throw new Error(localCourseBannerBuilderGetJsString(
                    'unabletodeleteselectedlayer',
                    'Unable to delete selected layer'
                ));
            }
            return response.json();
        }).then(function(data) {
            localCourseBannerBuilderReplaceSelectedSourceContentFromDeleteResponse(data);
        }).catch(function(error) {
            window.console.error(error);
            window.alert(error.message || localCourseBannerBuilderGetJsString(
                'unabletodeleteselectedlayer',
                'Unable to delete selected layer'
            ));
        });
    });
}

function localCourseBannerBuilderEnhanceAccordions(root) {
    Array.prototype.slice.call((root || document).querySelectorAll([
        'details.local-course-banner-builder-upload-accordion',
        'details.local-course-banner-builder-advanced-accordion',
        'details.local-course-banner-builder-section'
    ].join(','))).forEach(function(details) {
        var summary = details.querySelector(':scope > summary');
        if (!summary) {
            return;
        }
        var icon = summary.querySelector('[data-accordion-chevron=\"1\"], [data-local-details-toggle-icon=\"1\"], .icons-collapse-expand');
        if (!icon) {
            icon = document.createElement('span');
            icon.setAttribute('data-accordion-chevron', '1');
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = '';
            var firstChild = summary.firstChild;
            summary.insertBefore(icon, firstChild);
        }
        icon.setAttribute('data-accordion-chevron', '1');
        icon.setAttribute('aria-hidden', 'true');
        icon.className = 'btn btn-icon me-2 icons-collapse-expand local-course-banner-builder-collapse-icon';
        if (!icon.querySelector('.expanded-icon')) {
            icon.innerHTML = '<span class=\"expanded-icon icon-no-margin p-2\"><i class=\"icon fa fa-chevron-down fa-fw\" aria-hidden=\"true\"></i></span>' +
                '<span class=\"collapsed-icon icon-no-margin p-2\"><i class=\"icon fa fa-chevron-right fa-fw\" aria-hidden=\"true\"></i></span>';
        }
        icon.removeAttribute('style');
        icon.classList.toggle('collapsed', !details.hasAttribute('open'));
        if (!details.dataset.chevronBound) {
            details.addEventListener('toggle', function() {
                var toggleIcon = summary.querySelector('[data-accordion-chevron=\"1\"], [data-local-details-toggle-icon=\"1\"], .icons-collapse-expand');
                if (toggleIcon) {
                    toggleIcon.style.removeProperty('transform');
                    toggleIcon.classList.toggle('collapsed', !details.hasAttribute('open'));
                }
            });
            details.dataset.chevronBound = '1';
        }
    });
}

function localCourseBannerBuilderLoadLayerModal(url) {
    if (!url) {
        return;
    }
    fetch(url, {
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).then(function(response) {
        if (!response.ok) {
            throw new Error(localCourseBannerBuilderGetJsString('unabletoloadlayerform', ''));
        }
        return response.text();
    }).then(function(response) {
        var html = response;
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var fetchedmodal = localCourseBannerBuilderFindFetchedLayerModal(doc);
        if (!fetchedmodal) {
            throw new Error(localCourseBannerBuilderGetJsString('fetchedmodalnotfound', ''));
        }

        if (fetchedmodal.id === 'local-course-banner-builder-add-layer-modal') {
            throw new Error(localCourseBannerBuilderGetJsString('unexpectedcreatemodalreturned', ''));
            return;
        }

        var targetmodal = document.getElementById(fetchedmodal.id);
        if (!targetmodal) {
            throw new Error(localCourseBannerBuilderGetJsString('targetmodalnotfound', ''));
        }

        var fetchedtitle = fetchedmodal.querySelector('.modal-title');
        var fetchedbody = fetchedmodal.querySelector('.modal-body');
        var targettitle = targetmodal.querySelector('.modal-title');
        var targetbody = targetmodal.querySelector('.modal-body');
        if (!fetchedbody || !targetbody) {
            throw new Error(localCourseBannerBuilderGetJsString('modalbodynotfound', ''));
        }

        if (fetchedtitle && targettitle) {
            targettitle.textContent = fetchedtitle.textContent;
        }
        targetbody.innerHTML = fetchedbody.innerHTML;

        var form = targetbody.querySelector('form.mform');
        if (form && fetchedmodal.id === 'local-course-banner-builder-edit-image-layer-modal') {
            form.setAttribute('data-layer-modal-mode', 'editimage');
            var hasExistingImage = form.querySelector('#id_hasexistingimage');
            if (hasExistingImage) {
                hasExistingImage.value = '1';
            }
        }
        if (form && fetchedmodal.id === 'local-course-banner-builder-edit-border-layer-modal') {
            form.setAttribute('data-layer-modal-mode', 'editborder');
            form.setAttribute('data-edit-border-locked', '1');
            var currentIsBorderLayer = form.querySelector('#id_currentisborderlayer');
            if (currentIsBorderLayer) {
                currentIsBorderLayer.value = '1';
            }
            var borderToggle = form.querySelector(
                '[data-border-toggle=\"1\"][type=\"checkbox\"], [data-border-toggle=\"1\"][type=\"hidden\"]'
            );
            if (borderToggle) {
                if (borderToggle.type === 'checkbox') {
                    borderToggle.checked = true;
                    borderToggle.disabled = true;
                    borderToggle.setAttribute('aria-disabled', 'true');
                } else {
                    borderToggle.value = '1';
                }
            }
            var sidesValue = form.querySelector('#id_bordersidesvalue');
            if (sidesValue) {
                form.dataset.borderSidesValue = sidesValue.value;
            }
        }

        localCourseBannerBuilderSafelyPrepareDynamicLayerModal(targetmodal);
        if (localCourseBannerBuilderShowModal(targetmodal) && typeof window.jQuery !== 'undefined') {
            window.jQuery(targetmodal).one('shown.bs.modal', function() {
                localCourseBannerBuilderSafelyPrepareDynamicLayerModal(targetmodal);
                var shownForm = targetmodal.querySelector('form.mform');
                window.setTimeout(function() {
                    localCourseBannerBuilderRefreshCurrentPreviewLayer(shownForm);
                }, 30);
                window.setTimeout(function() {
                    localCourseBannerBuilderRefreshCurrentPreviewLayer(shownForm);
                }, 120);
            });
        } else {
            localCourseBannerBuilderSafelyPrepareDynamicLayerModal(targetmodal);
            var visibleForm = targetmodal.querySelector('form.mform');
            window.setTimeout(function() {
                localCourseBannerBuilderRefreshCurrentPreviewLayer(visibleForm);
            }, 30);
            window.setTimeout(function() {
                localCourseBannerBuilderRefreshCurrentPreviewLayer(visibleForm);
            }, 120);
        }
    }).catch(function(error) {
        window.console.error(error);
    });
}

function localCourseBannerBuilderSyncStickyHeader() {
    var holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
    var header = document.querySelector('.local-course-banner-builder-selected-source-sticky');
    var breadcrumbnav = document.querySelector('#page-header .page-header-nav');
    if (breadcrumbnav) {
        var breadcrumbHeight = breadcrumbnav.offsetHeight + 'px';
        if (document.body.style.getPropertyValue('--local-course-banner-builder-breadcrumb-height') !== breadcrumbHeight) {
            document.body.style.setProperty('--local-course-banner-builder-breadcrumb-height', breadcrumbHeight);
        }
    }
    if (!holder || !header) {
        return;
    }
    var snapOffset = 2;
    var nav = document.querySelector('.navbar.fixed-top, header.fixed-top, #page-header.fixed-top');
    var navbottom = nav ? Math.max(0, nav.getBoundingClientRect().bottom) : 0;
    var top = navbottom;
    var breadcrumbbottom = navbottom;
    if (breadcrumbnav) {
        breadcrumbbottom = Math.max(navbottom, breadcrumbnav.getBoundingClientRect().bottom);
        top = Math.max(navbottom, breadcrumbbottom - snapOffset);
    }

    if (!holder.dataset.stickyAnchorTop) {
        holder.dataset.stickyAnchorTop = String(holder.getBoundingClientRect().top + window.scrollY);
    }
    var anchortop = parseFloat(holder.dataset.stickyAnchorTop || '0');
    var shouldFloat = (anchortop - window.scrollY) <= (breadcrumbbottom + snapOffset);

    if (!shouldFloat) {
        holder.classList.remove('focus-navigation-buttons-holder--floating');
        header.classList.remove('focus-navigation-buttons--floating');
        holder.style.left = '';
        holder.style.right = '';
        holder.style.width = '';
        holder.style.top = '';
        holder.style.bottom = '';
        holder.style.minHeight = '0px';
        header.style.left = '';
        header.style.right = '';
        header.style.width = '';
        header.style.top = '';
        if (document.body.style.getPropertyValue('--local-course-banner-builder-sticky-header-space') !== '0px') {
            document.body.style.setProperty('--local-course-banner-builder-sticky-header-space', '0px');
        }
        return;
    }
    var rightSpace = 0;
    var page = document.getElementById('page');
    var rightDrawerOpen = document.body.classList.contains('drawer-open-right') ||
        (page && page.classList.contains('show-drawer-right'));
    var rightDrawerSelectors = [
        '#theme_boost-drawers-blocks',
        '#theme_boost-drawers-blocks.show',
        '.drawer.drawer-right',
        '.drawer.drawer-right.show',
        '[data-region=\"right-hand-drawer\"]:not(.hidden)',
        '[data-region=\"right-hand-drawer\"].drawer.show',
        '[data-region=\"right-hand-drawer\"].drawer'
    ];
    rightDrawerSelectors.forEach(function(selector) {
        var drawer = document.querySelector(selector);
        if (!drawer) {
            return;
        }
        var rect = drawer.getBoundingClientRect();
        var style = window.getComputedStyle(drawer);
        var visible = rect.width > 0 &&
            rect.right > 0 &&
            rect.left < window.innerWidth &&
            style.display !== 'none' &&
            style.visibility !== 'hidden' &&
            !drawer.classList.contains('hidden');
        if (visible) {
            rightSpace = Math.max(0, window.innerWidth - rect.left);
        } else if (rightDrawerOpen) {
            rightSpace = Math.max(rightSpace, drawer.offsetWidth || rect.width || 0);
        }
    });
    holder.classList.add('focus-navigation-buttons-holder--floating');
    header.classList.add('focus-navigation-buttons--floating');
    var left = 0;
    var rightBoundary = window.innerWidth;
    var rightDrawerToggle = document.querySelector(
        '.drawer-toggler.drawer-right-toggle, button[data-toggler=\"drawers\"][data-target=\"theme_boost-drawers-blocks\"]'
    );
    if (rightDrawerToggle) {
        var toggleRect = rightDrawerToggle.getBoundingClientRect();
        var toggleVisible = toggleRect.width > 0 && toggleRect.height > 0 && toggleRect.left < window.innerWidth;
        if (toggleVisible) {
            rightBoundary = Math.min(rightBoundary, Math.max(left, toggleRect.left - 12));
        }
    }
    if (rightSpace > 0) {
        rightBoundary = Math.min(rightBoundary, window.innerWidth - rightSpace);
    }
    var width = Math.max(0, rightBoundary - left);
    holder.style.left = left + 'px';
    holder.style.right = '';
    holder.style.width = width + 'px';
    holder.style.top = top + 'px';
    holder.style.bottom = 'auto';
    header.style.left = '';
    header.style.right = '';
    header.style.width = '100%';
    header.style.top = '';
    header.style.setProperty('--local-course-banner-builder-sticky-header-right-space', '0px');
    holder.style.minHeight = header.offsetHeight + 'px';
    var stickySpace = header.offsetHeight + 'px';
    if (document.body.style.getPropertyValue('--local-course-banner-builder-sticky-header-space') !== stickySpace) {
        document.body.style.setProperty('--local-course-banner-builder-sticky-header-space', stickySpace);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var filemanager = document.querySelector('#fitem_id_bannerimage_filemanager');
    var addlayermodal = document.getElementById('local-course-banner-builder-add-layer-modal');
    if (addlayermodal) {
        var defaulttitle = addlayermodal.querySelector('.modal-title');
        addlayermodal.dataset.defaultTitle = defaulttitle ? defaulttitle.textContent : '';
        var createDefaults = {};
        Array.prototype.slice.call(addlayermodal.querySelectorAll('form.mform input, form.mform select, form.mform textarea')).forEach(function(field) {
            if (!field.name) {
                return;
            }
            if (field.type === 'checkbox') {
                createDefaults[field.name] = !!field.checked;
            } else {
                createDefaults[field.name] = field.value;
            }
        });
        addlayermodal.dataset.createDefaults = JSON.stringify(createDefaults);
    }
    Array.prototype.slice.call(document.querySelectorAll('[data-dynamic-layer-modal=\"1\"]')).forEach(function(dynamicmodal) {
        if (typeof window.jQuery === 'undefined') {
            return;
        }
        window.jQuery(dynamicmodal).on('hidden.bs.modal', function() {
            var body = dynamicmodal.querySelector('.modal-body');
            if (body) {
                body.innerHTML = '';
            }
        });
    });
    localCourseBannerBuilderSyncSelectionButton();
    localCourseBannerBuilderSyncLayerSortOrders();
    localCourseBannerBuilderSyncBulkFields();
    localCourseBannerBuilderApplyStoredBorderStateDeferred();
    localCourseBannerBuilderSyncOffsetFields();
    localCourseBannerBuilderSyncCustomSizeFields();
    localCourseBannerBuilderBindPercentSliders();
    localCourseBannerBuilderSyncBorderSideGroup();
    localCourseBannerBuilderSyncBorderRoundedField();
    localCourseBannerBuilderUpgradeRanges();
    localCourseBannerBuilderUpgradeNumberInputs();
    localCourseBannerBuilderUpgradeColorPickers(document);
    localCourseBannerBuilderSyncDashedControls();
    localCourseBannerBuilderSyncLayerInputModes();
    localCourseBannerBuilderSyncBorderPreview();
    localCourseBannerBuilderBindLayerFormEvents();
    localCourseBannerBuilderBindLayerPreviewEvents();
    var createLayerForm = addlayermodal ? addlayermodal.querySelector('form.mform') : null;
    if (createLayerForm) {
        localCourseBannerBuilderEnhanceBinaryOptionButtons(createLayerForm);
        localCourseBannerBuilderEnhanceModalPreviewActions(createLayerForm);
        localCourseBannerBuilderEnhanceBorderSidePicker(createLayerForm);
    }
    localCourseBannerBuilderInitSourceVisualEditor(document);
    localCourseBannerBuilderSyncDetailsCollapseIcons();
    localCourseBannerBuilderAlignModalActionButtons(document);
    localCourseBannerBuilderEnhanceAccordions(document);
    localCourseBannerBuilderInitPopovers(document);
    localCourseBannerBuilderPrimeHelpBubbles(document);
    if (!document.documentElement.dataset.localCourseBannerBuilderFilmstripResizeBound) {
        window.addEventListener('resize', function() {
            Array.prototype.slice.call(document.querySelectorAll('[data-source-preview-filmstrip=\"1\"]')).forEach(function(filmstrip) {
                localCourseBannerBuilderUpdateSourcePreviewFilmstripNav(filmstrip);
            });
        });
        document.documentElement.dataset.localCourseBannerBuilderFilmstripResizeBound = '1';
    }
    localCourseBannerBuilderSyncStickyHeader();
    Array.prototype.slice.call(document.querySelectorAll('[data-source-dropdown]')).forEach(function(dropdown) {
        localCourseBannerBuilderSyncSourceDropdownButton(dropdown);
        localCourseBannerBuilderSyncSourceSubmit(dropdown);
    });
    Array.prototype.slice.call(document.querySelectorAll('[data-target=\"#local-course-banner-builder-add-layer-modal\"]')).forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (localCourseBannerBuilderLoadCreateLayerModal()) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
    var params = new URLSearchParams(window.location.search);
    var settings = document.getElementById('local-course-banner-builder-source-settings');
    if (settings && (params.get('sourcekey') || parseInt(params.get('categoryid') || '0', 10) > 0)) {
        window.setTimeout(function() {
            settings.scrollIntoView({behavior: 'smooth', block: 'start'});
        }, 120);
    }
    var autoSettingsModal = document.querySelector('[data-auto-open-source-settings=\"1\"]');
    if (autoSettingsModal) {
        window.setTimeout(function() {
            localCourseBannerBuilderShowModal(autoSettingsModal);
        }, 160);
    }
    if (params.get('openformatmodal') === '1') {
        window.setTimeout(function() {
            localCourseBannerBuilderShowModal(document.getElementById('local-course-banner-builder-banner-format-modal'));
        }, 160);
    }
    window.addEventListener('resize', localCourseBannerBuilderSyncStickyHeader, {passive: true});
    window.addEventListener('resize', function() {
        localCourseBannerBuilderSyncBorderPreview();
        localCourseBannerBuilderSyncLayerBannerPreview();
    }, {passive: true});
    window.addEventListener('scroll', localCourseBannerBuilderSyncStickyHeader, {passive: true});
    if (typeof MutationObserver !== 'undefined') {
        var drawerObserver = new MutationObserver(localCourseBannerBuilderSyncStickyHeader);
        var body = document.body;
        if (body) {
            drawerObserver.observe(body, {attributes: true, attributeFilter: ['class']});
        }
        Array.prototype.slice.call(document.querySelectorAll(
            '#page, #theme_boost-drawers-blocks, [data-region=\"right-hand-drawer\"], .drawer, .navbar.fixed-top, header.fixed-top, #page-header.fixed-top'
        )).forEach(function(target) {
            drawerObserver.observe(target, {attributes: true, attributeFilter: ['class', 'style', 'aria-expanded']});
        });
    }
    if (!filemanager || typeof MutationObserver === 'undefined') {
        return;
    }
    var observer = new MutationObserver(function() {
        localCourseBannerBuilderSyncBulkFields();
        localCourseBannerBuilderSyncLayerInputModes();
    });
    observer.observe(filemanager, {childList: true, subtree: true});
});
");

echo $OUTPUT->header();
echo html_writer::start_div(implode(' ', $adminclasses));

$switchurl = new moodle_url($issitebanneradmin
    ? '/local/course_banner_builder/admin_manage.php'
    : '/local/course_banner_builder/admin_site.php');
$switchlabel = get_string(
    $issitebanneradmin ? 'managecoursebannersquick' : 'managesitebannerquick',
    'local_course_banner_builder'
);
$currentbannerformat = $issitebanneradmin
    ? \local_course_banner_builder\manager::get_site_banner_format()
    : \local_course_banner_builder\manager::get_course_banner_format();
local_course_banner_builder_render_banner_format_modal(
    (new moodle_url($adminpagepath, $selectedsourceparams))->out(false),
    $currentbannerformat
);
$formatbutton = html_writer::tag(
    'button',
    html_writer::tag('i', '', ['class' => 'fa fa-columns me-2', 'aria-hidden' => 'true']) .
        html_writer::span(get_string('bannerformatbutton', 'local_course_banner_builder')),
    [
        'type' => 'button',
        'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-admin-format-button',
        'data-toggle' => 'modal',
        'data-target' => '#local-course-banner-builder-banner-format-modal',
        'data-bs-toggle' => 'modal',
        'data-bs-target' => '#local-course-banner-builder-banner-format-modal',
    ]
);
echo html_writer::div(
    html_writer::link(
        $switchurl,
        html_writer::tag('i', '', ['class' => 'fa fa-exchange me-2', 'aria-hidden' => 'true']) .
            html_writer::span($switchlabel),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-admin-switch-button']
    ) .
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_slideshow.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-images me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('manageslideshowquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-admin-slideshow-button']
    ) .
    $formatbutton .
    html_writer::tag(
        'form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'deleteallpluginsettings', 'value' => 1]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa fa-trash-can me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('deleteallpluginsettings', 'local_course_banner_builder')),
            [
                'type' => 'submit',
                'class' => 'btn btn-outline-danger local-course-banner-builder-dashed-action local-course-banner-builder-admin-reset-button',
                'data-modal' => 'confirmation',
                'data-modal-title' => get_string('confirm', 'moodle'),
                'data-modal-content' => get_string('deleteallpluginsettingsconfirm', 'local_course_banner_builder'),
                'data-modal-yes-button' => get_string('delete', 'moodle'),
            ]
        ),
        [
            'method' => 'post',
            'action' => (new moodle_url($adminpagepath))->out(false),
            'class' => 'd-inline local-course-banner-builder-admin-reset-form',
        ]
    ),
    'local-course-banner-builder-admin-switcher mb-3'
);

if ($issitebanneradmin) {
    $siteenabled = (bool)get_config('local_course_banner_builder', 'sitebannerenabled');
    echo html_writer::start_div('local-course-banner-builder-site-banner-status mb-4');
    echo html_writer::tag('h3', get_string('sitebannerstatus', 'local_course_banner_builder'), ['class' => 'h5']);
    echo html_writer::start_div('local-course-banner-builder-course-banner-status-actions');
    echo html_writer::tag('form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatesitebannerenabled', 'value' => 1]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sitebannerenabled', 'value' => $siteenabled ? 0 : 1]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa ' . ($siteenabled ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
                'aria-hidden' => 'true',
            ]) .
            html_writer::span(get_string(
                $siteenabled ? 'sitebannerenabledtoggleoff' : 'sitebannerenabledtoggleon',
                'local_course_banner_builder'
            )),
            [
                'type' => 'submit',
                'class' => 'btn ' . ($siteenabled ? 'btn-primary' : 'btn-outline-secondary') .
                    ' local-course-banner-builder-dashed-action local-course-banner-builder-site-enable-button',
            ]
        ),
        [
            'method' => 'post',
            'action' => (new moodle_url($adminpagepath))->out(false),
        ]
    );
    echo html_writer::tag('button',
        html_writer::tag('i', '', ['class' => 'fa fa-font me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('editsitebannertitle', 'local_course_banner_builder')),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action',
            'data-toggle' => 'modal',
            'data-target' => '#local-course-banner-builder-title-settings-site-modal',
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#local-course-banner-builder-title-settings-site-modal',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::div(
        get_string($siteenabled ? 'sitebannerenabledon' : 'sitebannerenabledoff', 'local_course_banner_builder'),
        'form-text text-muted mt-2'
    );
    echo local_course_banner_builder_render_title_settings_modal(
        'site',
        get_string('editsitebannertitle', 'local_course_banner_builder'),
        (new moodle_url($adminpagepath))->out(false)
    );
    echo html_writer::end_div();
} else {
    $coursebannersenabled = \local_course_banner_builder\manager::is_course_banner_enabled();
    $activitybannersenabled = \local_course_banner_builder\manager::course_banners_on_activity_pages_enabled();
    $defaultimagebannersenabled = \local_course_banner_builder\manager::course_default_image_banners_enabled();
    $customoverviewimagesenabled = \local_course_banner_builder\manager::course_custom_overview_images_enabled();
    $courseoptionhelpbutton = static function(string $label): string {
        return html_writer::tag('button', '?', [
            'type' => 'button',
            'class' => 'btn btn-link p-0 icon-no-margin local-course-banner-builder-help-dot',
            'data-toggle' => 'popover',
            'data-trigger' => 'hover',
            'data-placement' => 'top',
            'data-html' => 'true',
            'data-content' => '<div class="no-overflow"><p>' . s($label) . '</p></div>',
            'data-local-course-banner-builder-popover-label' => $label,
            'aria-label' => get_string('help'),
        ]);
    };
    echo html_writer::start_div('local-course-banner-builder-site-banner-status mb-4');
    echo html_writer::tag('h3', get_string('coursebannerstatus', 'local_course_banner_builder'), ['class' => 'h5']);
    echo html_writer::start_div('local-course-banner-builder-course-banner-status-actions');
    echo html_writer::tag('form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatecoursebannerenabled', 'value' => 1]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'coursebannerenabled', 'value' => $coursebannersenabled ? 0 : 1]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa ' . ($coursebannersenabled ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
                'aria-hidden' => 'true',
            ]) .
            html_writer::span(get_string(
                $coursebannersenabled ? 'coursebannerstoggleoff' : 'coursebannerstoggleon',
                'local_course_banner_builder'
            )),
            [
                'type' => 'submit',
                'class' => 'btn ' . ($coursebannersenabled ? 'btn-primary' : 'btn-outline-secondary') .
                    ' local-course-banner-builder-dashed-action',
            ]
        ),
        ['method' => 'post', 'action' => (new moodle_url($adminpagepath, $selectedsourceparams))->out(false)]
    );
    echo html_writer::tag('form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatecourseactivitybanners', 'value' => 1]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'coursebanneractivitiesenabled', 'value' => $activitybannersenabled ? 0 : 1]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa ' . ($activitybannersenabled ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
                'aria-hidden' => 'true',
            ]) .
            html_writer::span(get_string(
                $activitybannersenabled ? 'courseactivitybannerstoggleoff' : 'courseactivitybannerstoggleon',
                'local_course_banner_builder'
            )),
            [
                'type' => 'submit',
                'disabled' => $coursebannersenabled ? null : 'disabled',
                'class' => 'btn ' . ($activitybannersenabled ? 'btn-primary' : 'btn-outline-secondary') .
                    ' local-course-banner-builder-dashed-action',
            ]
        ),
        ['method' => 'post', 'action' => (new moodle_url($adminpagepath, $selectedsourceparams))->out(false)]
    );
    echo html_writer::tag('form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatecoursedefaultimagebanners', 'value' => 1]) .
        html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'coursebannerdefaultimageenabled',
            'value' => $defaultimagebannersenabled ? 0 : 1,
        ]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa ' . ($defaultimagebannersenabled ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
                'aria-hidden' => 'true',
            ]) .
            html_writer::span(get_string(
                $defaultimagebannersenabled ? 'coursebannerdefaultimagetoggleoff' : 'coursebannerdefaultimagetoggleon',
                'local_course_banner_builder'
            )),
            [
                'type' => 'submit',
                'disabled' => $coursebannersenabled ? null : 'disabled',
                'class' => 'btn ' . ($defaultimagebannersenabled ? 'btn-primary' : 'btn-outline-secondary') .
                    ' local-course-banner-builder-dashed-action',
            ]
        ),
        ['method' => 'post', 'action' => (new moodle_url($adminpagepath, $selectedsourceparams))->out(false)]
    );
    echo html_writer::tag('form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatecoursecustomoverviewimages', 'value' => 1]) .
        html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'coursecustomoverviewimagesenabled',
            'value' => $customoverviewimagesenabled ? 0 : 1,
        ]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', [
                'class' => 'fa ' . ($customoverviewimagesenabled ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
                'aria-hidden' => 'true',
            ]) .
            html_writer::span(get_string(
                $customoverviewimagesenabled ? 'coursecustomoverviewimagetoggleoff' : 'coursecustomoverviewimagetoggleon',
                'local_course_banner_builder'
            )),
            [
                'type' => 'submit',
                'class' => 'btn ' . ($customoverviewimagesenabled ? 'btn-primary' : 'btn-outline-secondary') .
                    ' local-course-banner-builder-dashed-action',
            ]
        ) .
        $courseoptionhelpbutton(get_string('coursecustomoverviewimagehelp', 'local_course_banner_builder')),
        [
            'method' => 'post',
            'action' => (new moodle_url($adminpagepath, $selectedsourceparams))->out(false),
            'class' => 'local-course-banner-builder-toggle-button-row',
        ]
    );
    echo html_writer::tag('form',
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'forcecourseoverviewreplacement', 'value' => 1]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa fa-arrows-rotate me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('forcecourseoverviewreplacement', 'local_course_banner_builder')),
            [
                'type' => 'submit',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action',
            ]
        ) .
        $courseoptionhelpbutton(get_string('forcecourseoverviewreplacementhelp', 'local_course_banner_builder')),
        [
            'method' => 'post',
            'action' => (new moodle_url($adminpagepath, $selectedsourceparams))->out(false),
            'class' => 'local-course-banner-builder-toggle-button-row',
        ]
    );
    foreach ([
        'course' => 'editcoursebannertitle',
        'activity' => 'editactivitybannertitle',
    ] as $titlecontext => $stringkey) {
        echo html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-font me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string($stringkey, 'local_course_banner_builder')),
            [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action',
                'data-toggle' => 'modal',
                'data-target' => '#local-course-banner-builder-title-settings-' . $titlecontext . '-modal',
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#local-course-banner-builder-title-settings-' . $titlecontext . '-modal',
            ]
        );
    }
    echo html_writer::end_div();
    foreach ([
        'course' => 'editcoursebannertitle',
        'activity' => 'editactivitybannertitle',
    ] as $titlecontext => $stringkey) {
        echo local_course_banner_builder_render_title_settings_modal(
            $titlecontext,
            get_string($stringkey, 'local_course_banner_builder'),
            (new moodle_url($adminpagepath, $selectedsourceparams))->out(false)
        );
    }
    echo html_writer::div(
        get_string($coursebannersenabled ? 'coursebannersenabledon' : 'coursebannersenabledoff', 'local_course_banner_builder'),
        'form-text text-muted mt-2'
    );
    echo html_writer::div(
        get_string(
            $activitybannersenabled ? 'courseactivitybannersenabledon' : 'courseactivitybannersenabledoff',
            'local_course_banner_builder'
        ),
        'form-text text-muted mt-1'
    );
    echo html_writer::div(
        get_string(
            $defaultimagebannersenabled ? 'coursebannerdefaultimageenabledon' : 'coursebannerdefaultimageenabledoff',
            'local_course_banner_builder'
        ),
        'form-text text-muted mt-1'
    );
    echo html_writer::div(
        get_string(
            $customoverviewimagesenabled ? 'coursecustomoverviewimageenabledon' : 'coursecustomoverviewimageenabledoff',
            'local_course_banner_builder'
        ),
        'form-text text-muted mt-1'
    );
    echo html_writer::end_div();
}

$selectedcategorylabel = '';
foreach ($categoryoptions as $option) {
    if ((int)$option['id'] === (int)$selectedcategoryid) {
        $selectedcategorylabel = $option['label'];
        break;
    }
}
if (!$issitebanneradmin && $selectedcategorylabel !== '') {
    $deselectsourceurl = new moodle_url('/local/course_banner_builder/admin_manage.php');
    $stickyleft = html_writer::div(
        html_writer::tag('span', get_string('selectedcategorystatus', 'local_course_banner_builder'), [
            'class' => 'local-course-banner-builder-sticky-kicker',
        ]) .
        html_writer::tag('strong', s($selectedcategorylabel), [
            'class' => 'local-course-banner-builder-sticky-title',
        ]),
        'local-course-banner-builder-sticky-leading'
    );
    $stickyright = html_writer::link(
        $deselectsourceurl,
        get_string('deselectsource', 'local_course_banner_builder'),
        ['class' => 'btn btn-outline-danger btn-sm local-course-banner-builder-sticky-deselect']
    );
    echo html_writer::div(
        html_writer::div(
            $stickyleft . $stickyright,
            'focus-navigation-buttons local-course-banner-builder-selected-source-sticky d-flex justify-content-between align-items-center w-100'
        ),
        'focus-navigation-buttons-holder local-course-banner-builder-selected-source-sticky-holder'
    );
} else if (!$issitebanneradmin && $selectedsource) {
    $deselectsourceurl = new moodle_url('/local/course_banner_builder/admin_manage.php');
    $stickyleft = html_writer::div(
        html_writer::tag('span', get_string('selectedcategorystatus', 'local_course_banner_builder'), [
            'class' => 'local-course-banner-builder-sticky-kicker',
        ]) .
        html_writer::tag('strong', s($selectedsource->label), [
            'class' => 'local-course-banner-builder-sticky-title',
        ]),
        'local-course-banner-builder-sticky-leading'
    );
    $stickyright = html_writer::link(
        $deselectsourceurl,
        get_string('deselectsource', 'local_course_banner_builder'),
        ['class' => 'btn btn-outline-danger btn-sm local-course-banner-builder-sticky-deselect']
    );
    echo html_writer::div(
        html_writer::div(
            $stickyleft . $stickyright,
            'focus-navigation-buttons local-course-banner-builder-selected-source-sticky d-flex justify-content-between align-items-center w-100'
        ),
        'focus-navigation-buttons-holder local-course-banner-builder-selected-source-sticky-holder'
    );
}

if (!$issitebanneradmin) {
    if (empty($categoryoptions)) {
        echo $OUTPUT->notification(get_string('nocategoryselected', 'local_course_banner_builder'), 'info');
    } else {
        echo html_writer::start_div('local-course-banner-builder-source-picker mb-4');

        echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => (new moodle_url('/local/course_banner_builder/admin_manage.php'))->out(false),
        'class' => 'local-course-banner-builder-source-form',
        ]);
        echo html_writer::start_div('local-course-banner-builder-source-column');
        $categorymanagementurl = new moodle_url('/course/management.php');
        $categorymanagementhelp = get_string('managecategorieslink_help', 'local_course_banner_builder');
        echo html_writer::tag(
        'h3',
        html_writer::span(get_string('categories', 'local_course_banner_builder')) .
            html_writer::link(
                $categorymanagementurl,
                html_writer::tag('i', '', [
                    'class' => 'icon fa fa-sitemap fa-fw',
                    'aria-hidden' => 'true',
                ]),
                [
                    'class' => 'btn btn-link p-0 icon-no-margin local-course-banner-builder-admin-link',
                    'role' => 'button',
                    'data-container' => 'body',
                    'data-toggle' => 'popover',
                    'data-placement' => 'right',
                    'data-content' => '<div class="no-overflow"><p>' . s($categorymanagementhelp) . '</p></div>',
                    'data-html' => 'true',
                    'data-trigger' => 'hover',
                    'aria-label' => $categorymanagementhelp,
                    'title' => get_string('managecategorieslink', 'local_course_banner_builder'),
                ]
            ),
        ['class' => 'h5 local-course-banner-builder-source-title']
        );
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'id' => 'id_categoryid', 'value' => $selectedcategoryid]);
        $selectedcategorybuttonlabel = $selectedcategorylabel ?: get_string('choosecategorydefault', 'local_course_banner_builder');
        $categorybuttonclass = 'btn btn-outline-primary dropdown-toggle local-course-banner-builder-source-dropdown-toggle';
        if ($selectedcategoryid) {
            $categorybuttonclass .= ' active is-selected';
        }
        echo html_writer::start_div('dropdown local-course-banner-builder-source-dropdown mb-2', [
        'data-source-dropdown' => 'category',
        'data-input' => '#id_categoryid',
        ]);
        echo html_writer::tag('button', s($selectedcategorybuttonlabel), [
        'type' => 'button',
        'id' => 'id_category_dropdown_button',
        'class' => $categorybuttonclass,
        'data-toggle' => 'dropdown',
        'data-bs-toggle' => 'dropdown',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
        'data-source-dropdown-label' => '1',
        ]);
        echo html_writer::start_div('dropdown-menu local-course-banner-builder-source-dropdown-menu', [
        'aria-labelledby' => 'id_category_dropdown_button',
        ]);
        echo html_writer::start_div('local-course-banner-builder-source-dropdown-search');
        echo html_writer::empty_tag('input', [
        'type' => 'search',
        'id' => 'id_category_search',
        'class' => 'form-control',
        'placeholder' => get_string('searchcategoriesplaceholder', 'local_course_banner_builder'),
        'data-action' => 'local-course-banner-builder-filter-sources',
        'data-target' => '#local-course-banner-builder-category-options',
        'data-counter' => '#local-course-banner-builder-category-count',
        ]);
        echo html_writer::end_div();
        echo html_writer::start_div('local-course-banner-builder-source-dropdown-options', [
        'id' => 'local-course-banner-builder-category-options',
        ]);
        foreach ($categoryoptions as $option) {
            $attributes = [
            'type' => 'button',
            'class' => 'dropdown-item local-course-banner-builder-source-option',
            'data-source-option' => 'category',
            'data-value' => $option['id'],
            'data-label' => $option['label'],
            'data-search' => $option['searchtext'],
            'title' => $option['title'] ?? $option['label'],
            ];
            if ((int)$option['id'] === (int)$selectedcategoryid) {
                $attributes['class'] .= ' active';
            }
            if (!empty($option['isused'])) {
                $attributes['class'] .= ' local-course-banner-builder-source-used';
            }
            $optioncontent = html_writer::span(s($option['label']), 'local-course-banner-builder-source-option-label');
            if (!empty($option['isused'])) {
                $optioncontent .= html_writer::span(
                html_writer::tag('i', '', ['class' => 'fa fa-check', 'aria-hidden' => 'true']),
                'local-course-banner-builder-source-used-icon',
                [
                    'role' => 'button',
                    'data-container' => 'body',
                    'data-toggle' => 'popover',
                    'data-placement' => 'left',
                    'data-content' => '<div class="no-overflow"><p>' .
                        s(get_string('sourcealreadyconfiguredhelp', 'local_course_banner_builder')) . '</p></div>',
                    'data-html' => 'true',
                    'data-trigger' => 'hover',
                    'tabindex' => '0',
                    'aria-label' => get_string('sourcealreadyconfiguredhelp', 'local_course_banner_builder'),
                ]
                );
            }
            echo html_writer::tag('button', $optioncontent, $attributes);
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::tag(
        'div',
        get_string('matchingcategories', 'local_course_banner_builder') . ' ' .
            html_writer::span((string)count($categoryoptions), '', ['id' => 'local-course-banner-builder-category-count']),
        ['class' => 'form-text text-muted mb-2']
        );
        $selectsourcebuttonattributes = [
        'type' => 'submit',
        'value' => get_string('selectcategory', 'local_course_banner_builder'),
        'class' => 'btn btn-secondary',
        'data-action' => 'local-course-banner-builder-submit-source',
        'aria-disabled' => $selectedcategoryid ? 'false' : 'true',
        ];
        if (!$selectedcategoryid) {
            $selectsourcebuttonattributes['disabled'] = 'disabled';
        }
        echo html_writer::empty_tag('input', $selectsourcebuttonattributes);
        echo html_writer::end_div();
        echo html_writer::end_tag('form');

        echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => (new moodle_url('/local/course_banner_builder/admin_manage.php'))->out(false),
        'class' => 'local-course-banner-builder-source-form',
        ]);
        echo html_writer::start_div('local-course-banner-builder-source-column');
        echo html_writer::tag('h3', get_string('coursecustomfields', 'local_course_banner_builder'), [
        'class' => 'h5 local-course-banner-builder-source-title local-course-banner-builder-source-title--plain',
        ]);
        $customfieldsearchattributes = [
        'type' => 'search',
        'id' => 'id_customfield_search',
        'class' => 'form-control mb-2',
        'placeholder' => get_string('searchcustomfieldsplaceholder', 'local_course_banner_builder'),
        'data-action' => 'local-course-banner-builder-filter-sources',
        'data-target' => '#local-course-banner-builder-customfield-options',
        'data-counter' => '#local-course-banner-builder-customfield-count',
        ];
        if (empty($customfieldoptions)) {
            $customfieldsearchattributes['disabled'] = 'disabled';
        }
        echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sourcekey',
        'id' => 'id_customfieldid',
        'value' => $selectedcustomfieldsourcekey,
        ]);
        $selectedcustomfieldlabel = '';
        foreach ($customfieldoptions as $option) {
            if (($option['sourcekey'] ?? '') === $selectedcustomfieldsourcekey) {
                $selectedcustomfieldlabel = $option['label'];
                break;
            }
        }
        echo html_writer::start_div('dropdown local-course-banner-builder-source-dropdown mb-2', [
        'data-source-dropdown' => 'customfield',
        'data-input' => '#id_customfieldid',
        ]);
        echo html_writer::tag('button', $selectedcustomfieldlabel ?: get_string('choosecustomfielddefault', 'local_course_banner_builder'), [
        'type' => 'button',
        'id' => 'id_customfield_dropdown_button',
        'class' => 'btn btn-outline-primary dropdown-toggle local-course-banner-builder-source-dropdown-toggle' .
            ($selectedcustomfieldsourcekey ? ' active is-selected' : ''),
        'data-toggle' => 'dropdown',
        'data-bs-toggle' => 'dropdown',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
        'data-source-dropdown-label' => '1',
        ]);
        echo html_writer::start_div('dropdown-menu local-course-banner-builder-source-dropdown-menu', [
        'aria-labelledby' => 'id_customfield_dropdown_button',
        ]);
        echo html_writer::start_div('local-course-banner-builder-source-dropdown-search');
        echo html_writer::empty_tag('input', $customfieldsearchattributes);
        echo html_writer::end_div();
        echo html_writer::start_div('local-course-banner-builder-source-dropdown-options', [
        'id' => 'local-course-banner-builder-customfield-options',
        ]);
        if (empty($customfieldoptions)) {
            echo html_writer::tag('span', get_string('nocustomfieldsourceenabled', 'local_course_banner_builder'), [
            'class' => 'dropdown-item disabled',
            ]);
        } else {
            foreach ($customfieldoptions as $option) {
                $optionclass = 'dropdown-item local-course-banner-builder-source-option';
                if (($option['sourcekey'] ?? '') === $selectedcustomfieldsourcekey) {
                    $optionclass .= ' active';
                }
                if (!empty($option['isused'])) {
                    $optionclass .= ' local-course-banner-builder-source-used';
                }
                $optioncontent = html_writer::span(s($option['label']), 'local-course-banner-builder-source-option-label');
                if (!empty($option['isused'])) {
                    $optioncontent .= html_writer::span(
                    html_writer::tag('i', '', ['class' => 'fa fa-check', 'aria-hidden' => 'true']),
                    'local-course-banner-builder-source-used-icon',
                    [
                        'role' => 'button',
                        'data-container' => 'body',
                        'data-toggle' => 'popover',
                        'data-placement' => 'left',
                        'data-content' => '<div class="no-overflow"><p>' .
                            s(get_string('sourcealreadyconfiguredhelp', 'local_course_banner_builder')) . '</p></div>',
                        'data-html' => 'true',
                        'data-trigger' => 'hover',
                        'tabindex' => '0',
                        'aria-label' => get_string('sourcealreadyconfiguredhelp', 'local_course_banner_builder'),
                    ]
                    );
                }
                echo html_writer::tag('button', $optioncontent, [
                'type' => 'button',
                'class' => $optionclass,
                'data-source-option' => 'customfield',
                'data-value' => $option['sourcekey'],
                'data-label' => $option['label'],
                'data-search' => $option['searchtext'],
                'title' => $option['title'] ?? $option['label'],
                ]);
            }
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::tag(
        'div',
        get_string('matchingcustomfields', 'local_course_banner_builder') . ' ' .
            html_writer::span((string)count($customfieldoptions), '', ['id' => 'local-course-banner-builder-customfield-count']),
        ['class' => 'form-text text-muted mb-2']
        );
        $selectcustomfieldbuttonattributes = [
        'type' => 'submit',
        'value' => get_string('selectcustomfieldsource', 'local_course_banner_builder'),
        'class' => 'btn btn-secondary',
        'data-action' => 'local-course-banner-builder-submit-source',
        'aria-disabled' => $selectedcustomfieldsourcekey ? 'false' : 'true',
        ];
        if (!$selectedcustomfieldsourcekey) {
            $selectcustomfieldbuttonattributes['disabled'] = 'disabled';
        }
        echo html_writer::empty_tag('input', $selectcustomfieldbuttonattributes);
        echo html_writer::end_div();
        echo html_writer::end_tag('form');

        echo html_writer::end_div();
    }
}

if ($selectedsource) {
        $categorysettings = \local_course_banner_builder\manager::get_source_settings($selectedsource);

        echo html_writer::start_div('modal fade', [
            'id' => 'local-course-banner-builder-source-settings-modal',
            'tabindex' => '-1',
            'role' => 'dialog',
            'aria-labelledby' => 'local-course-banner-builder-source-settings-modal-title',
            'aria-hidden' => 'true',
            'data-auto-open-source-settings' => empty($categorysettings->id) ? '1' : '0',
        ]);
        echo html_writer::start_div('modal-dialog modal-lg', ['role' => 'document']);
        echo html_writer::start_div('modal-content');
        echo html_writer::start_div('modal-header d-flex align-items-center');
        echo html_writer::tag('h5', get_string('categorysettings', 'local_course_banner_builder'), [
            'class' => 'modal-title flex-grow-1',
            'id' => 'local-course-banner-builder-source-settings-modal-title',
        ]);
        echo html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
            'type' => 'button',
            'class' => 'close ml-auto ms-auto',
            'data-dismiss' => 'modal',
            'data-bs-dismiss' => 'modal',
            'aria-label' => get_string('closebuttontitle'),
        ]);
        echo html_writer::end_div();
        echo html_writer::start_div('modal-body');
        echo html_writer::start_div('local-course-banner-builder-settings');
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new moodle_url($adminpagepath, $selectedsourceparams))->out(false),
            'class' => 'local-course-banner-builder-settings-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savecategorysettings', 'value' => 1]);

    if ($selectedsource->type === \local_course_banner_builder\manager::SOURCE_TYPE_CUSTOMFIELD) {
        echo html_writer::start_div('mb-3 local-course-banner-builder-source-settings-field');
        echo html_writer::label(get_string('customfieldpriority', 'local_course_banner_builder'), 'id_customfieldpriority', [
            'class' => 'd-block',
        ]);
        echo html_writer::select(
            \local_course_banner_builder\manager::get_customfield_priority_options(),
            'customfieldpriority',
            $categorysettings->customfieldpriority ?? \local_course_banner_builder\manager::CUSTOMFIELD_PRIORITY_CATEGORY,
            false,
            ['id' => 'id_customfieldpriority', 'class' => 'custom-select w-100']
        );
        echo html_writer::tag('div', get_string('customfieldpriority_help', 'local_course_banner_builder'), [
            'class' => 'form-text text-muted mt-2',
        ]);
        echo html_writer::end_div();
    }

        echo html_writer::start_div('mb-3 local-course-banner-builder-source-settings-field');
        echo html_writer::label(get_string('compositionmode', 'local_course_banner_builder'), 'id_compositionmode', [
            'class' => 'd-block',
        ]);
        echo html_writer::select(
            \local_course_banner_builder\manager::get_composition_mode_options(),
            'compositionmode',
            $categorysettings->compositionmode ?? \local_course_banner_builder\manager::MODE_CUMULATIVE,
            false,
            ['id' => 'id_compositionmode', 'class' => 'custom-select w-100']
        );
        echo html_writer::tag('div', get_string('compositionmode_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted mt-2']);
        echo html_writer::end_div();

        echo html_writer::start_div('mb-3 local-course-banner-builder-source-settings-field');
        echo html_writer::label(get_string('fitmode', 'local_course_banner_builder'), 'id_fitmode', [
            'class' => 'd-block',
        ]);
        echo html_writer::select(
            \local_course_banner_builder\manager::get_editable_fit_mode_options(),
            'fitmode',
            $categorysettings->fitmode ?? \local_course_banner_builder\manager::FIT_MODE_ORIGINAL,
            false,
            ['id' => 'id_fitmode', 'class' => 'custom-select w-100']
        );
        echo html_writer::tag('div', get_string('fitmode_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted mt-2']);
        echo html_writer::end_div();

    if (!\local_course_banner_builder\manager::is_site_source($selectedsource)) {
        echo html_writer::start_div('mb-3 local-course-banner-builder-source-settings-field');
        $sourceparentoptions = \local_course_banner_builder\manager::get_source_parent_options((string)$selectedsource->sourcekey);
        $selectedparentkey = (string)($categorysettings->sourceparentkey ?? '');
        if (!empty($categorysettings->sourceisroot)) {
            $selectedparentkey = '';
        }
        $selectedparentlabel = $sourceparentoptions[$selectedparentkey] ?? $sourceparentoptions[''] ?? '';
        echo html_writer::label(get_string('sourceparentkey', 'local_course_banner_builder'), 'id_sourceparentkey', [
            'class' => 'd-block',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sourceparentkey',
            'id' => 'id_sourceparentkey',
            'value' => $selectedparentkey,
        ]);
        echo html_writer::start_div('dropdown local-course-banner-builder-source-dropdown', [
            'data-source-dropdown' => 'sourceparent',
            'data-input' => '#id_sourceparentkey',
        ]);
        echo html_writer::tag('button', s($selectedparentlabel), [
            'type' => 'button',
            'id' => 'id_sourceparentkey_dropdown_button',
            'class' => 'btn btn-outline-primary dropdown-toggle local-course-banner-builder-source-dropdown-toggle w-100 text-left',
            'data-toggle' => 'dropdown',
            'data-bs-toggle' => 'dropdown',
            'aria-haspopup' => 'true',
            'aria-expanded' => 'false',
            'data-source-dropdown-label' => '1',
        ]);
        echo html_writer::start_div('dropdown-menu local-course-banner-builder-source-dropdown-menu', [
            'aria-labelledby' => 'id_sourceparentkey_dropdown_button',
        ]);
        echo html_writer::start_div('local-course-banner-builder-source-dropdown-search');
        echo html_writer::empty_tag('input', [
            'type' => 'search',
            'class' => 'form-control',
            'placeholder' => get_string('searchsourcesplaceholder', 'local_course_banner_builder'),
            'data-action' => 'local-course-banner-builder-filter-sources',
            'data-target' => '#local-course-banner-builder-sourceparent-options',
        ]);
        echo html_writer::end_div();
        echo html_writer::start_div('local-course-banner-builder-source-dropdown-options', [
            'id' => 'local-course-banner-builder-sourceparent-options',
        ]);
        foreach ($sourceparentoptions as $sourceparentkey => $sourceparentlabel) {
            $optionclass = 'dropdown-item local-course-banner-builder-source-option';
            if ((string)$sourceparentkey === $selectedparentkey) {
                $optionclass .= ' active';
            }
            echo html_writer::tag('button', s((string)$sourceparentlabel), [
                'type' => 'button',
                'class' => $optionclass,
                'data-source-option' => 'sourceparent',
                'data-value' => (string)$sourceparentkey,
                'data-label' => (string)$sourceparentlabel,
                'data-search' => \core_text::strtolower((string)$sourceparentlabel),
            ]);
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::tag('div', get_string('sourceparentkey_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted mt-2']);
        echo html_writer::end_div();

        echo html_writer::start_div('mb-3');
        $sourceisroot = !empty($categorysettings->sourceisroot) || $selectedparentkey === '';
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sourceisroot',
            'id' => 'id_sourceisroot',
            'value' => $sourceisroot ? '1' : '0',
        ]);
        echo html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa fa-sitemap me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('sourceisroot', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'class' => 'btn ' . ($sourceisroot ? 'btn-primary' : 'btn-outline-secondary') . ' local-course-banner-builder-source-preview-button',
                'data-action' => 'local-course-banner-builder-toggle-root-source',
                'data-input' => '#id_sourceisroot',
                'data-on-label' => get_string('sourceisroot', 'local_course_banner_builder'),
                'data-off-label' => get_string('sourceisroot', 'local_course_banner_builder'),
            ]
        );
        echo html_writer::tag('div', get_string('sourceisroot_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted mt-2']);
        echo html_writer::end_div();
    }

        echo html_writer::start_div('local-course-banner-builder-submit-actions local-course-banner-builder-source-settings-submit-actions');
        echo html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-save me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('savecategorysettings', 'local_course_banner_builder')),
            [
            'type' => 'submit',
            'class' => 'btn btn-primary local-course-banner-builder-source-settings-submit',
            ]
        );
        echo html_writer::end_div();
        echo html_writer::end_tag('form');
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();

    if ($formmode === 'create') {
        foreach ([
            'local-course-banner-builder-edit-border-layer-modal' => get_string('editborder', 'local_course_banner_builder'),
            'local-course-banner-builder-edit-image-layer-modal' => get_string('editimage', 'local_course_banner_builder'),
        ] as $emptymodalid => $emptymodaltitle) {
            local_course_banner_builder_render_layer_modal(
                $emptymodalid,
                $emptymodaltitle,
                null,
                ['data-dynamic-layer-modal' => '1']
            );
        }
    }

        $layermodalid = match ($formmode) {
            'editborder' => 'local-course-banner-builder-edit-border-layer-modal',
            'editimage' => 'local-course-banner-builder-edit-image-layer-modal',
            default => 'local-course-banner-builder-add-layer-modal',
        };
        $layermodaltitle = match ($formmode) {
            'editborder' => get_string('editborder', 'local_course_banner_builder'),
            'editimage' => get_string('editimage', 'local_course_banner_builder'),
            default => get_string('addlayer', 'local_course_banner_builder'),
        };

        local_course_banner_builder_render_layer_modal($layermodalid, $layermodaltitle, function() use ($form) {
            $form->display();
        });
}

if ($selectedsource) {
    $selectedsourcecontext = \local_course_banner_builder\manager::export_selected_source($selectedsource);
    $selectedsourcecontext['sourcevisualeditorhtml'] = local_course_banner_builder_render_source_visual_editor(
        $selectedsource,
        !empty($sourcechainpreview)
    );
    echo html_writer::start_tag('details', ['class' => 'local-course-banner-builder-section mb-4', 'open' => 'open']);
    echo html_writer::tag(
        'summary',
        html_writer::span(
            html_writer::span(
                html_writer::span(
                    $OUTPUT->pix_icon('t/expandedchevron', get_string('collapse', 'core')),
                    'expanded-icon icon-no-margin p-2'
                ) .
                html_writer::span(
                    html_writer::span($OUTPUT->pix_icon('t/collapsedchevron', get_string('expand', 'core')), 'dir-rtl-hide') .
                    html_writer::span($OUTPUT->pix_icon('t/collapsedchevron_rtl', get_string('expand', 'core')), 'dir-ltr-hide'),
                    'collapsed-icon icon-no-margin p-2'
                ),
                'btn btn-icon me-2 icons-collapse-expand local-course-banner-builder-collapse-icon',
                [
                'data-accordion-chevron' => '1',
                'aria-hidden' => 'true',
                ]
            ) .
            html_writer::span(get_string('selectedcategorystatus', 'local_course_banner_builder'), '', [
                'class' => 'local-course-banner-builder-section-title-text',
            ]),
            'local-course-banner-builder-section-heading'
        ) .
            html_writer::tag(
                'button',
                html_writer::tag('i', '', ['class' => 'fa fa-cog py-2 me-3', 'aria-hidden' => 'true']) .
                    html_writer::span(get_string('sourcesettingsshort', 'local_course_banner_builder')),
                [
                    'type' => 'button',
                    'class' => 'btn btn-outline-secondary btn-sm local-course-banner-builder-dashed-action ' .
                        'local-course-banner-builder-settings-action',
                    'data-action' => 'local-course-banner-builder-summary-action',
                    'data-toggle' => 'modal',
                    'data-target' => '#local-course-banner-builder-source-settings-modal',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#local-course-banner-builder-source-settings-modal',
                ]
        ),
        ['class' => 'local-course-banner-builder-section-summary local-course-banner-builder-section-summary-actions']
    );
    echo $OUTPUT->render_from_template(
        'local_course_banner_builder/admin_selected',
        $selectedsourcecontext
    );
    echo html_writer::end_tag('details');
}

if (!$issitebanneradmin) {
    echo html_writer::start_tag('details', ['class' => 'local-course-banner-builder-section mb-4', 'open' => 'open']);
    echo html_writer::tag(
        'summary',
        html_writer::span(
            html_writer::span(
                html_writer::span(
                    $OUTPUT->pix_icon('t/expandedchevron', get_string('collapse', 'core')),
                    'expanded-icon icon-no-margin p-2'
                ) .
                html_writer::span(
                    html_writer::span($OUTPUT->pix_icon('t/collapsedchevron', get_string('expand', 'core')), 'dir-rtl-hide') .
                    html_writer::span($OUTPUT->pix_icon('t/collapsedchevron_rtl', get_string('expand', 'core')), 'dir-ltr-hide'),
                    'collapsed-icon icon-no-margin p-2'
                ),
                'btn btn-icon me-2 icons-collapse-expand local-course-banner-builder-collapse-icon',
                [
                'data-accordion-chevron' => '1',
                'aria-hidden' => 'true',
                ]
            ) .
            html_writer::span(get_string('configuredcategories', 'local_course_banner_builder'), '', [
                'class' => 'local-course-banner-builder-section-title-text',
            ]),
            'local-course-banner-builder-section-heading'
        )
    );
    echo $OUTPUT->render_from_template(
        'local_course_banner_builder/admin_manage',
        \local_course_banner_builder\manager::export_configured_categories($selectedsourcekey)
    );
    echo html_writer::end_tag('details');
}

echo html_writer::end_div();
echo $OUTPUT->footer();
