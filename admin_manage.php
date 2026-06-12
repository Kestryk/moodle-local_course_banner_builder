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
 * @param array|null $previewdefinition
 * @return string
 */
function local_course_banner_builder_render_title_settings_modal(
    string $context,
    string $title,
    string $formaction,
    ?array $previewdefinition = null
): string {
    $prefix = 'bannertitle_' . $context . '_';
    $modalid = 'local-course-banner-builder-title-settings-' . $context . '-modal';
    $getconfig = function (string $name, $default) use ($prefix) {
        $value = get_config('local_course_banner_builder', $prefix . $name);
        return $value === false || $value === null || $value === '' ? $default : $value;
    };
    $hex = function ($value, string $default = '#FFFFFF'): string {
        $value = (string)$value;
        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtoupper($value) : $default;
    };
    $bool = function (string $name, bool $default = false) use ($getconfig): bool {
        return (bool)$getconfig($name, $default ? 1 : 0);
    };
    $num = function (string $name, float $default, float $min, float $max) use ($getconfig): float {
        return max($min, min($max, (float)$getconfig($name, $default)));
    };

    $values = [
        'enabled' => $bool('enabled'),
        'x' => $num('x', 50, 0, 100),
        'y' => $num('y', 50, 0, 100),
        'fontsize' => $num('fontsize', 100, 25, 480),
        'lineheight' => $num('lineheight', 105, 40, 540),
        'fontfamily' => (string)$getconfig('fontfamily', ''),
        'color' => $hex($getconfig('color', '#FFFFFF')),
        'align' => (string)$getconfig('align', 'center'),
        'bold' => $bool('bold', true),
        'italic' => $bool('italic'),
        'underline' => $bool('underline'),
        'strike' => $bool('strike'),
        'allcaps' => $bool('allcaps'),
        'aboveborder' => $bool('aboveborder', true),
        'aboveoverlay' => $bool('aboveoverlay', true),
        'replacemoodletitle' => $context === 'course' ? $bool('replacemoodletitle') : false,
        'frameenabled' => $bool('frameenabled'),
        'frametype' => (string)$getconfig('frametype', 'box'),
        'framecolor' => $hex($getconfig('framecolor', '#000000'), '#000000'),
        'frameopacity' => $num('frameopacity', 35, 0, 100),
        'framebordercolor' => $hex($getconfig('framebordercolor', '#FFFFFF')),
        'frameborderwidth' => $num('frameborderwidth', 0, 0, 10),
        'frameradius' => $num('frameradius', 12, 0, 80),
        'framepadding' => $num('framepadding', 18, 0, 240),
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
        'align' => 'center',
        'bold' => '1',
        'italic' => '0',
        'underline' => '0',
        'strike' => '0',
        'allcaps' => '0',
        'aboveborder' => '1',
        'aboveoverlay' => '1',
        'replacemoodletitle' => '0',
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
        'align',
        'bold',
        'italic',
        'underline',
        'strike',
        'allcaps',
        'aboveborder',
        'aboveoverlay',
        'replacemoodletitle',
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
    $readinheritstate = function (string $sourcecontext) use ($inheritfields, $defaulttitlestate, $hex, $fontoptions): array {
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
            } else if ($fieldname === 'align' && !in_array((string)$value, ['left', 'center', 'right'], true)) {
                $value = 'center';
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

    $field = function (string $label, string $control, string $class = 'mb-3'): string {
        return html_writer::div(
            html_writer::tag('label', $label, ['class' => 'form-label']) . $control,
            $class
        );
    };
    $checkbox = function (string $name, string $label, bool $checked) use ($modalid): string {
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
    $togglebutton = function (
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
    $slider = function (string $name, string $label, float $value, float $min, float $max, float $step = 0.1) use ($field): string {
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
            'local-course-banner-builder-title-slider ' .
                'local-course-banner-builder-title-slider--' . preg_replace('/[^a-z0-9_-]+/i', '', $name)
        );
        return $field($label, $control);
    };
    $color = function (string $name, string $label, string $value) use ($field): string {
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
    $styleinput = function (string $name, bool $active) use ($values): string {
        return html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $name,
            'value' => !empty($values[$name]) ? 1 : 0,
            'data-title-style-input' => $name,
            'data-title-control' => $name,
        ]);
    };
    $stylebutton = function (string $name, string $icon, string $label) use ($values): string {
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
    $resizehandles = static function (): string {
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
    $previewtoolbarbutton = static function (string $iconclass, string $label, string $action, array $extra = []): string {
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
    global $SITE;

    $previewcourse = get_string('previewcoursetitlelong', 'local_course_banner_builder');
    $previewcoursesingle = get_string('previewcoursetitle', 'local_course_banner_builder');
    $previewsitefallback = get_string('previewsitetitle', 'local_course_banner_builder');
    $previewsite = trim((string)($SITE->fullname ?? ''));
    if ($previewsite === '') {
        $previewsite = get_string('previewsitetitlelong', 'local_course_banner_builder');
    }
    $previewsitesingle = $previewsite !== '' ? $previewsite : $previewsitefallback;
    $previewactivity = $context === 'site' ? $previewsite :
        get_string('previewactivitytitlelong', 'local_course_banner_builder');
    $previewactivitysingle = $context === 'site' ? $previewsitesingle :
        get_string('previewactivitytitle', 'local_course_banner_builder');
    $previewcourse = str_replace(' / ', "\n", $previewcourse);
    if ($previewsite === get_string('previewsitetitlelong', 'local_course_banner_builder')) {
        $previewsite = str_replace(' / ', "\n", $previewsite);
    }
    $previewactivity = str_replace(' / ', "\n", $previewactivity);
    $previewcoursesingle = trim((string)(preg_split('/\R/u', $previewcourse)[0] ?? $previewcoursesingle));
    $previewsitesingle = trim((string)(preg_split('/\R/u', $previewsite)[0] ?? $previewsitesingle));
    $previewactivitysingle = trim((string)(preg_split('/\R/u', $previewactivity)[0] ?? $previewactivitysingle));
    if ($context === 'site') {
        $previewtitle = $previewsitesingle;
    } else if ($context === 'activity' && ($values['activitytitlemode'] ?? 'activity') === 'both') {
        $previewtitle = trim($previewcoursesingle . "\n" . $previewactivitysingle);
    } else {
        $previewtitle = $context === 'activity' ? $previewactivity : $previewcourse;
    }
    if ($context === 'activity') {
        $previewtitleparts = preg_split('/\R/u', (string)$previewtitle);
        $hascoursepreview = in_array($previewcoursesingle, $previewtitleparts, true);
        $hasactivitypreview = in_array($previewactivitysingle, $previewtitleparts, true);
        if ($hascoursepreview && $hasactivitypreview) {
            $previewtitle = trim($previewcoursesingle . "\n" . $previewactivitysingle);
        }
    }
    $titlecontextlayers = array_values(array_filter(
        (array)($previewdefinition['contextlayers'] ?? []),
        static function ($layer): bool {
            return is_array($layer) && ($layer['type'] ?? '') !== 'title';
        }
    ));
    $titlecontextlayershtml = '';
    foreach ($titlecontextlayers as $layer) {
        $titlecontextlayershtml .= local_course_banner_builder_render_title_preview_context_layer($layer);
    }
    $hascontextlayers = $titlecontextlayershtml !== '';
    $preview = html_writer::div(
        html_writer::div('', 'local-course-banner-builder-slideshow-admin-preview-backdrop') .
        $titlecontextlayershtml .
        html_writer::div('', 'local-course-banner-builder-slideshow-admin-preview-overlay') .
        html_writer::div(
            ($context === 'site' ? '' : html_writer::tag('button', html_writer::tag('i', '', [
                'class' => 'fa fa-grip-lines',
                'aria-hidden' => 'true',
            ]), [
                'type' => 'button',
                'class' => 'btn btn-link p-0 icon-no-margin local-course-banner-builder-preview-aspect-lock ' .
                    'local-course-banner-builder-title-line-toggle',
                'data-action' => 'local-course-banner-builder-toggle-title-preview-line-count',
                'aria-label' => get_string('bannertitlepreviewlines', 'local_course_banner_builder'),
                'title' => get_string('bannertitlepreviewlines', 'local_course_banner_builder'),
            ])) .
            html_writer::span(s($previewtitle), '', ['data-title-preview-label' => '1']) . $resizehandles(),
            'local-course-banner-builder-banner-title-overlay local-course-banner-builder-title-preview-text',
            [
            'data-title-preview-text' => '1',
            'data-title-preview-draggable' => '1',
            'data-title-preview-course' => $previewcourse,
            'data-title-preview-course-single' => $previewcoursesingle,
            'data-title-preview-site' => $previewsite,
            'data-title-preview-site-single' => $previewsitesingle,
            'data-title-preview-activity' => $previewactivity,
            'data-title-preview-activity-single' => $previewactivitysingle,
            'data-title-preview-line-count' => $context === 'site' ? '1' : '2',
            ]
        ),
        'local-course-banner-builder-slideshow-admin-preview local-course-banner-builder-title-preview-frame ' .
            $formatclass,
        [
            'data-title-preview-frame' => '1',
            'data-banner-format' => $bannerformat,
            'data-title-preview-has-context-layers' => $hascontextlayers ? '1' : '0',
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
        $stylebutton('strike', 'fa-strikethrough', get_string('bannertitlestrike', 'local_course_banner_builder')) .
        $stylebutton('allcaps', 'fa-font', get_string('bannertitleallcaps', 'local_course_banner_builder')) .
        $previewtoolbarbutton('fa-align-left', get_string('bannertextalign:left', 'local_course_banner_builder'),
            'local-course-banner-builder-set-title-align', [
                'data-title-align' => 'left',
                'aria-pressed' => $values['align'] === 'left' ? 'true' : 'false',
            ]) .
        $previewtoolbarbutton('fa-align-center', get_string('bannertextalign:center', 'local_course_banner_builder'),
            'local-course-banner-builder-set-title-align', [
                'data-title-align' => 'center',
                'aria-pressed' => $values['align'] === 'center' ? 'true' : 'false',
            ]) .
        $previewtoolbarbutton('fa-align-right', get_string('bannertextalign:right', 'local_course_banner_builder'),
            'local-course-banner-builder-set-title-align', [
                'data-title-align' => 'right',
                'aria-pressed' => $values['align'] === 'right' ? 'true' : 'false',
            ]) .
        $stylebutton('aboveborder', 'fa-level-up',
            get_string('bannertitleaboveborder', 'local_course_banner_builder')) .
        $stylebutton('aboveoverlay', 'fa-layer-group',
            get_string('bannertitleaboveoverlay', 'local_course_banner_builder')) .
        $previewtoolbarbutton('fa-redo', get_string('redopreviewchange', 'local_course_banner_builder'),
            'local-course-banner-builder-title-preview-redo', ['disabled' => 'disabled']),
        'local-course-banner-builder-source-preview-visibility-toggle-row local-course-banner-builder-slideshow-preview-toolbar local-course-banner-builder-title-preview-toolbar'
    );
    $hiddenstyles = $styleinput('bold', true) .
        $styleinput('italic', false) .
        $styleinput('underline', false) .
        $styleinput('strike', false) .
        $styleinput('allcaps', false) .
        $styleinput('aboveborder', true) .
        $styleinput('aboveoverlay', true) .
        html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'align',
            'value' => in_array((string)$values['align'], ['left', 'center', 'right'], true)
                ? (string)$values['align']
                : 'center',
            'data-title-control' => 'align',
        ]);
    $titlepanel = static function (string $key, string $content): string {
        return html_writer::div(
            $content,
            'local-course-banner-builder-preview-opacity-panel local-course-banner-builder-title-side-panel is-collapsed',
            [
                'data-title-side-panel' => $key,
                'hidden' => 'hidden',
            ]
        );
    };
    $titlepanelbutton = static function (string $key, string $icon, string $label): string {
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
    $titlecornerswitch = static function (float $radius): string {
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
    $frametypeswitch = function (string $current): string {
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
        ($hascontextlayers ? html_writer::tag('button', html_writer::tag('i', '', [
            'class' => 'fa fa-eye me-2',
            'aria-hidden' => 'true',
        ]) . html_writer::span(get_string('showotherlayers', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-title-context-layers',
            'data-title-context-toggle' => '1',
            'data-label-on' => get_string('hideotherlayers', 'local_course_banner_builder'),
            'data-label-off' => get_string('showotherlayers', 'local_course_banner_builder'),
            'data-icon-on' => 'fa-eye-slash',
            'data-icon-off' => 'fa-eye',
            'aria-pressed' => 'false',
        ]) : '') .
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
                $slider('framepadding', get_string('bannertitleframepadding', 'local_course_banner_builder'), $values['framepadding'], 0, 240)
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
        '',
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
                    'local-course-banner-builder-title-top-toggle-button ' .
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
    $replacemoodletitlecontrol = $context === 'course' ? html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'replacemoodletitle',
            'value' => $values['replacemoodletitle'] ? 1 : 0,
            'id' => $modalid . '-replacemoodletitle',
            'data-title-control' => 'replacemoodletitle',
        ]) .
        html_writer::div(
            html_writer::tag('button', html_writer::tag('i', '', [
                'class' => 'fa ' . ($values['replacemoodletitle'] ? 'fa-toggle-on' : 'fa-toggle-off') . ' me-2',
                'aria-hidden' => 'true',
            ]) . html_writer::span(
                $values['replacemoodletitle'] ?
                    get_string('bannertitlereplacemoodletitle:on', 'local_course_banner_builder') :
                    get_string('bannertitlereplacemoodletitle:off', 'local_course_banner_builder')
            ), [
                'type' => 'button',
                'class' => 'btn local-course-banner-builder-slideshow-enable-button ' .
                    'local-course-banner-builder-title-top-toggle-button ' .
                    ($values['replacemoodletitle'] ? 'btn-primary' : 'btn-outline-secondary'),
                'data-local-title-toggle-button' => '1',
                'data-target-input' => '#' . $modalid . '-replacemoodletitle',
                'data-label-on' => get_string('bannertitlereplacemoodletitle:on', 'local_course_banner_builder'),
                'data-label-off' => get_string('bannertitlereplacemoodletitle:off', 'local_course_banner_builder'),
                'aria-pressed' => $values['replacemoodletitle'] ? 'true' : 'false',
                'aria-label' => get_string('bannertitlereplacemoodletitle', 'local_course_banner_builder'),
            ]),
            'local-course-banner-builder-toggle-button-host'
        ),
        'local-course-banner-builder-title-top-secondary'
    ) : '';
    $titleenabledrow = html_writer::div(
        $enabletitlecontrol . $replacemoodletitlecontrol,
        'local-course-banner-builder-title-top-choice-row'
    );

    $editorcontent = html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'updatebannertitlesettings', 'value' => 1]) .
        html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'bannertitlecontext', 'value' => $context]) .
        $inheritchoicecontrol .
        $titleenabledrow .
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
                $slider('fontsize', get_string('bannertitletextsize', 'local_course_banner_builder'), $values['fontsize'], 25, 480, 0.1) .
                $slider('x', get_string('bannertitlex', 'local_course_banner_builder'), $values['x'], 0, 100, 0.1) .
                $slider('y', get_string('bannertitley', 'local_course_banner_builder'), $values['y'], 0, 100, 0.1),
                'local-course-banner-builder-title-control-grid'
            ) .
            html_writer::div(
                $slider(
                    'lineheight',
                    get_string('bannertitlelineheight', 'local_course_banner_builder'),
                    $values['lineheight'],
                    40,
                    540,
                    0.1
                ),
                'local-course-banner-builder-title-control-grid local-course-banner-builder-title-control-grid--single'
            ),
            'local-course-banner-builder-title-panel-section'
        );
    $footer = html_writer::div(
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
        'modal-footer local-course-banner-builder-submit-actions ' .
            'local-course-banner-builder-title-submit-actions local-course-banner-builder-slideshow-modal-footer'
    );
    $body = html_writer::tag('form',
        html_writer::div($editorcontent, 'local-course-banner-builder-title-editor-scroll') . $footer,
        [
            'method' => 'post',
            'action' => $formaction,
            'class' => 'local-course-banner-builder-title-editor-form',
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
 * Render one non-editable context layer inside a banner title preview modal.
 *
 * @param array $layer
 * @return string
 */
function local_course_banner_builder_render_title_preview_context_layer(array $layer): string {
    $type = (string)($layer['type'] ?? '');
    $zindex = (int)($layer['zindex'] ?? ((int)($layer['sortorder'] ?? 0) + 1));
    $attrs = [
        'data-title-preview-context-layer' => '1',
        'data-title-preview-context-layer-type' => $type,
        'data-preview-context-layer' => '1',
        'data-preview-layer-id' => (string)($layer['id'] ?? 0),
        'data-preview-inherited' => !empty($layer['isinherited']) ? '1' : '0',
        'data-preview-sortorder' => (string)($layer['sortorder'] ?? 0),
        'data-preview-zindex' => (string)$zindex,
        'hidden' => 'hidden',
        'aria-hidden' => 'true',
    ];

    if ($type === 'image') {
        $style = trim((string)($layer['wrapperstyle'] ?? ''));
        $attrs['class'] = 'local-course-banner-builder-preview-image-layer ' .
            'local-course-banner-builder-preview-image-layer--context ' .
            'local-course-banner-builder-title-preview-context-layer';
        $attrs['style'] = trim($style . ' z-index: ' . $zindex . ';');
        return html_writer::tag('div', html_writer::empty_tag('img', [
            'src' => (string)($layer['url'] ?? ''),
            'alt' => '',
            'class' => 'local-course-banner-builder-preview-image',
            'style' => (string)($layer['imagestyle'] ?? ''),
            'data-preview-image-tag' => '1',
            'draggable' => 'false',
        ]), $attrs);
    }

    if ($type === 'border') {
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
        $attrs['class'] = 'local-course-banner-builder-preview-border-layer ' .
            'local-course-banner-builder-preview-border-layer--context ' .
            'local-course-banner-builder-title-preview-context-layer';
        $attrs['style'] = trim((string)($layer['wrapperstyle'] ?? '') . ' z-index: ' . $zindex . ';');
        return html_writer::tag('div', $parts, $attrs);
    }

    if ($type === 'overlay') {
        $style = trim((string)($layer['wrapperstyle'] ?? ''));
        if ($style !== '' && !str_ends_with($style, ';')) {
            $style .= ';';
        }
        $attrs['class'] = 'local-course-banner-builder-banner-overlay-layer ' .
            'local-course-banner-builder-preview-overlay-layer--context ' .
            'local-course-banner-builder-title-preview-context-layer';
        $attrs['style'] = $style . ' z-index: ' . $zindex . ';';
        $attrs['data-preview-overlay-title-above'] = !empty($layer['overlaytitleabove']) ? '1' : '0';
        $attrs['data-preview-overlay-border-above'] = !empty($layer['overlayborderabove']) ? '1' : '0';
        return html_writer::tag('div', '', $attrs);
    }

    return '';
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
        'data-preview-above-overlay' => !empty($layer['imageaboveoverlayenabled']) ? '1' : '0',
        'data-preview-below-inherited' => !empty($layer['imagebelowinheritedenabled']) ? '1' : '0',
        'data-preview-above-inherited' => !empty($layer['imageaboveinheritedenabled']) ? '1' : '0',
        'data-preview-center-fixed' => !empty($layer['imagecenterfixed']) ? '1' : '0',
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
            'onpointerdown' => 'return window.localCourseBannerBuilderHandleModalResizeHandlePointerDown(event, this);',
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
                'onpointerdown' => 'return window.localCourseBannerBuilderHandleModalResizeHandlePointerDown(event, this);',
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
        (!empty($layer['enabled']) ? '' : ' local-course-banner-builder-source-preview-overlay--disabled') .
        ($isinherited ? ' local-course-banner-builder-source-preview-overlay--inherited' : '') .
        ($ishiddeninherited ? ' local-course-banner-builder-source-preview-overlay--hidden-inherited' : '');
    $attrs = [
        'class' => $classes,
        'style' => trim((string)($layer['wrapperstyle'] ?? '') . ' z-index: ' . (int)($layer['zindex'] ?? 1) . ';'),
        'data-source-preview-overlay' => '1',
        'data-source-preview-overlay-id' => (string)($layer['id'] ?? 0),
        'data-preview-overlay-title-above' => !empty($layer['overlaytitleabove']) ? '1' : '0',
        'data-preview-overlay-border-above' => !empty($layer['overlayborderabove']) ? '1' : '0',
        'data-source-preview-inherited' => $isinherited ? '1' : '0',
        'data-preview-enabled' => !empty($layer['enabled']) ? '1' : '0',
        'data-preview-sortorder' => (string)($layer['sortorder'] ?? 0),
        'data-preview-zindex' => (string)($layer['zindex'] ?? 1),
    ];
    return html_writer::tag('div', '', $attrs);
}

/**
 * Render one source visual editor contextual title layer.
 *
 * @param array $layer
 * @return string
 */
function local_course_banner_builder_render_source_visual_editor_title_layer(array $layer): string {
    $text = (string)($layer['text'] ?? '');
    $framestyle = trim((string)($layer['framestyle'] ?? ''));
    $ishighlight = (string)($layer['frametype'] ?? 'box') === 'highlight';
    $textscale = max(0.25, min(4.8, (float)($layer['textscale'] ?? 1)));
    $align = in_array((string)($layer['align'] ?? 'center'), ['left', 'center', 'right'], true)
        ? (string)$layer['align']
        : 'center';
    $scaledtext = static function (string $value) use ($textscale, $align): string {
        $origin = $align === 'left' ? 'left center' : ($align === 'right' ? 'right center' : 'center center');
        return html_writer::span($value === '' ? '&nbsp;' : s($value), '', [
            'style' => 'display: inline-block; line-height: 1; transform: scale(' . round($textscale, 4) .
                '); transform-origin: ' . $origin . ';',
        ]);
    };
    $content = '';

    if ($ishighlight && $framestyle !== '') {
        $lines = preg_split('/\R/u', $text);
        $lines = $lines === false ? [$text] : $lines;
        foreach ($lines as $index => $line) {
            $content .= html_writer::span($scaledtext((string)$line), '', [
                'style' => $framestyle,
                'data-title-highlight-line' => '1',
            ]);
            if ($index < count($lines) - 1) {
                $content .= html_writer::empty_tag('br');
            }
        }
    } else {
        $content = $scaledtext($text);
    }

    $classes = 'local-course-banner-builder-banner-title-overlay local-course-banner-builder-source-preview-title';
    if ($ishighlight) {
        $classes .= ' local-course-banner-builder-banner-title-overlay--highlight-frame';
    }

    return html_writer::div($content, $classes, [
        'style' => (string)($layer['style'] ?? ''),
        'data-source-preview-title' => '1',
        'data-source-preview-title-context' => (string)($layer['context'] ?? ''),
        'data-preview-enabled' => !empty($layer['enabled']) ? '1' : '0',
        'data-preview-zindex' => (string)($layer['zindex'] ?? 3010),
        'aria-hidden' => 'true',
    ]);
}

/**
 * Return adaptive labels for the source preview context toggle.
 *
 * @param bool $hasborder
 * @param bool $hasoverlay
 * @param bool $hastitle
 * @return array{hide:string,show:string}
 */
function local_course_banner_builder_get_source_preview_context_toggle_labels(
    bool $hasborder,
    bool $hasoverlay,
    bool $hastitle
): array {
    if ($hasborder && $hasoverlay && $hastitle) {
        return [
            'hide' => get_string('hidepreviewborderoverlaytitle', 'local_course_banner_builder'),
            'show' => get_string('showpreviewborderoverlaytitle', 'local_course_banner_builder'),
        ];
    }
    if ($hasborder && $hasoverlay) {
        return [
            'hide' => get_string('hidepreviewborderoverlay', 'local_course_banner_builder'),
            'show' => get_string('showpreviewborderoverlay', 'local_course_banner_builder'),
        ];
    }
    if ($hasborder && $hastitle) {
        return [
            'hide' => get_string('hidepreviewbordertitle', 'local_course_banner_builder'),
            'show' => get_string('showpreviewbordertitle', 'local_course_banner_builder'),
        ];
    }
    if ($hasoverlay && $hastitle) {
        return [
            'hide' => get_string('hidepreviewoverlaytitle', 'local_course_banner_builder'),
            'show' => get_string('showpreviewoverlaytitle', 'local_course_banner_builder'),
        ];
    }
    if ($hasoverlay) {
        return [
            'hide' => get_string('hideoverlaylayer', 'local_course_banner_builder'),
            'show' => get_string('showoverlaylayer', 'local_course_banner_builder'),
        ];
    }
    if ($hastitle) {
        return [
            'hide' => get_string('hidepreviewtitle', 'local_course_banner_builder'),
            'show' => get_string('showpreviewtitle', 'local_course_banner_builder'),
        ];
    }
    return [
        'hide' => get_string('hidepreviewborder', 'local_course_banner_builder'),
        'show' => get_string('showpreviewborder', 'local_course_banner_builder'),
    ];
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

    $buttoncontent = static function (string $iconclass, string $label): string {
        return html_writer::tag('i', '', [
            'class' => 'fa ' . $iconclass . ' me-2',
            'aria-hidden' => 'true',
        ]) . html_writer::span($label);
    };
    $iconbutton = static function (string $iconclass, string $label, string $action, array $extra = []): string {
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
    $haspreviewoverlay = false;
    $previewborderediturl = null;
    $previewoverlayediturl = null;
    $sourcehasdirectborder = \local_course_banner_builder\manager::source_has_border_layer($source);
    $sourcehasdirectoverlay = \local_course_banner_builder\manager::source_has_overlay_layer($source);
    $sourcehaschainborder = \local_course_banner_builder\manager::source_chain_has_border_layer($source);
    $sourcehaschainoverlay = \local_course_banner_builder\manager::source_chain_has_overlay_layer($source);
    $hasbordercontrol = $sourcehasdirectborder || $sourcehaschainborder;
    $hasoverlaycontrol = $sourcehasdirectoverlay || $sourcehaschainoverlay;
    $titlelayer = \local_course_banner_builder\manager::export_banner_title_preview_layer($source, false);
    $hastitlecontrol = $titlelayer !== null;
    $buildediturl = static function (int $layerid) use ($source): moodle_url {
        $editparams = [
            'elementid' => $layerid,
            'sourcekey' => (string)$source->sourcekey,
        ];
        if (!\local_course_banner_builder\manager::is_site_source($source) && !empty($source->categoryid)) {
            $editparams['categoryid'] = (int)$source->categoryid;
        }
        return new moodle_url(
            \local_course_banner_builder\manager::is_site_source($source)
                ? '/local/course_banner_builder/admin_site.php'
                : '/local/course_banner_builder/admin_manage.php',
            $editparams
        );
    };
    foreach (\local_course_banner_builder\manager::get_source_elements($source) as $element) {
        if ($sourcehasdirectborder && !$previewborderediturl && !empty($element->borderenabled)) {
            $previewborderediturl = $buildediturl((int)$element->id);
        }
        if (!$previewoverlayediturl && !empty($element->overlayenabled)) {
            $previewoverlayediturl = $buildediturl((int)$element->id);
        }
        if ($previewborderediturl && $previewoverlayediturl) {
            break;
        }
    }
    foreach ($definition['layers'] as $layer) {
        if (($layer['type'] ?? '') === 'border') {
            $haspreviewborder = true;
            $layershtml .= local_course_banner_builder_render_source_visual_editor_border_layer($layer);
            continue;
        }
        if (($layer['type'] ?? '') === 'overlay') {
            $haspreviewoverlay = true;
            $layershtml .= local_course_banner_builder_render_source_visual_editor_overlay_layer($layer);
            continue;
        }
        $layershtml .= local_course_banner_builder_render_source_visual_editor_image_layer($layer);
    }
    if ($titlelayer !== null) {
        $layershtml .= local_course_banner_builder_render_source_visual_editor_title_layer($titlelayer);
    }

    if (empty($definition['haslayers']) && $titlelayer === null) {
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
    $previewcontexttogglelabels = local_course_banner_builder_get_source_preview_context_toggle_labels(
        $hasbordercontrol,
        $hasoverlaycontrol,
        $hastitlecontrol
    );
    $previewtogglehidestring = $previewcontexttogglelabels['hide'];
    $previewtoggleshowstring = $previewcontexttogglelabels['show'];
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
        ($previewoverlayediturl ? html_writer::tag('button', $buttoncontent('fa-adjust', get_string('editoverlaylayer', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-source-preview-button',
                'data-edit-layer-url' => $previewoverlayediturl->out(false),
            ] + $disabledattributes
        ) : '') .
        (($hasbordercontrol || $hasoverlaycontrol || $hastitlecontrol) ? html_writer::tag('button', $buttoncontent('fa-eye-slash', $previewtogglehidestring), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-preview-border',
            'data-show-label' => $previewtoggleshowstring,
            'data-hide-label' => $previewtogglehidestring,
            'data-show-icon' => 'fa-eye',
            'data-hide-icon' => 'fa-eye-slash',
            'data-preview-border-visible' => '1',
            'aria-pressed' => 'true',
        ]) : '') .
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
        ]) .
        html_writer::tag('button', $buttoncontent('fa-layer-group', get_string('imageaboveoverlay', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-source-preview-above-overlay',
            'data-on-label' => get_string('imagebelowoverlay', 'local_course_banner_builder'),
            'data-off-label' => get_string('imageaboveoverlay', 'local_course_banner_builder'),
            'data-on-icon' => 'fa-clone',
            'data-off-icon' => 'fa-layer-group',
            'data-above-overlay-enabled' => '0',
        ]) .
        html_writer::tag('button', $buttoncontent('fa-level-down', get_string('imagebelowinheritedenabled', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-source-preview-below-inherited',
            'data-on-label' => get_string('imagewithinheritedorder', 'local_course_banner_builder'),
            'data-off-label' => get_string('imagebelowinheritedenabled', 'local_course_banner_builder'),
            'data-on-icon' => 'fa-level-up',
            'data-off-icon' => 'fa-level-down',
            'data-below-inherited-enabled' => '0',
        ]) .
        html_writer::tag('button', $buttoncontent('fa-layer-group', get_string('imageaboveinheritedenabled', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
            'data-action' => 'local-course-banner-builder-toggle-source-preview-above-inherited',
            'data-on-label' => get_string('imagewithinheritedorder', 'local_course_banner_builder'),
            'data-off-label' => get_string('imageaboveinheritedenabled', 'local_course_banner_builder'),
            'data-on-icon' => 'fa-level-down',
            'data-off-icon' => 'fa-layer-group',
            'data-above-inherited-enabled' => '0',
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
        $iconbutton('fa-thumb-tack', get_string('imagecenterfixed', 'local_course_banner_builder'), 'local-course-banner-builder-toggle-source-preview-center-fixed') .
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
        html_writer::tag('h4', get_string(
            \local_course_banner_builder\manager::is_site_source($source)
                ? 'sitebannersourcevisualeditor'
                : 'sourcevisualeditor',
            'local_course_banner_builder'
        ), [
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
        'data-source-has-direct-border' => $sourcehasdirectborder ? '1' : '0',
        'data-source-has-direct-overlay' => $sourcehasdirectoverlay ? '1' : '0',
        'data-source-preview-inherited-visible' => $useeffectivechain ? '1' : '0',
        ]
    );
}

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$sourcekey = optional_param('sourcekey', '', PARAM_TEXT);
$elementid = optional_param('elementid', 0, PARAM_INT);
$deleteelementid = optional_param('deleteelementid', 0, PARAM_INT);
$deletecategorycontent = optional_param('deletecategorycontent', 0, PARAM_INT);
$deletecategoryimages = optional_param('deletecategoryimages', 0, PARAM_INT);
$deletesourcecontent = optional_param('deletesourcecontent', '', PARAM_TEXT);
$deletesourceimages = optional_param('deletesourceimages', '', PARAM_TEXT);
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
 * @param string $context
 * @return void
 */
function local_course_banner_builder_render_banner_format_modal(
    string $formaction,
    string $currentformat,
    string $context
): void {
    $formats = \local_course_banner_builder\manager::get_banner_format_options();
    $currentformat = \local_course_banner_builder\manager::normalise_banner_format($currentformat);
    $context = $context === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE
        ? \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE
        : \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_COURSE;
    $modalid = 'local-course-banner-builder-banner-format-' . $context . '-modal';
    $titlekey = $context === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE
        ? 'sitebannerformatbutton'
        : 'coursebannerformatbutton';
    $cards = '';
    foreach ($formats as $format => $label) {
        $descriptionkey = match ($format) {
            \local_course_banner_builder\manager::BANNER_FORMAT_CONTENT_WIDE => 'bannerformat:contentwide_help',
            \local_course_banner_builder\manager::BANNER_FORMAT_FULLWIDTH_TOP => 'bannerformat:fullwidthtop_help',
            \local_course_banner_builder\manager::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT => 'bannerformat:fullwidthtopcompact_help',
            \local_course_banner_builder\manager::BANNER_FORMAT_FULLWIDTH_TOP_INSET => 'bannerformat:fullwidthtopinset_help',
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
        'id' => $modalid,
        'tabindex' => '-1',
        'role' => 'dialog',
        'aria-labelledby' => $modalid . '-title',
        'aria-hidden' => 'true',
    ]);
    echo html_writer::start_div('modal-dialog modal-xl', ['role' => 'document']);
    echo html_writer::start_div('modal-content');
    echo html_writer::start_div('modal-header d-flex align-items-center');
    echo html_writer::tag('h5', get_string($titlekey, 'local_course_banner_builder'), [
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
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $formaction,
    ]);
    echo html_writer::start_div('modal-body');
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savebannerformat', 'value' => 1]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'bannerformatcontext', 'value' => $context]);
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
    'bannertitlepreviewlines',
    'bannertitlepreviewlines:double',
    'bannertitlepreviewlines:single',
    'bordersides:all',
    'childborderlayersdisableconfirm',
    'childoverlaylayersdisableconfirm',
    'customsizekeepaspect',
    'cropimage',
    'croppedlayerthumbnail',
    'croppedlayerthumbnail_help',
    'deleteselectedlayer',
    'disabledlayerthumbnail',
    'editoverlaylayer',
    'editsourcebutton',
    'enablelayer',
    'enablelayer_help',
    'fetchedmodalnotfound',
    'fillbannerpreviewimage',
    'fittopreview',
    'hideimageinpreview',
    'hideotherlayers',
    'hideslideshowpreview',
    'hideoverlaylayer',
    'hidepreviewborder',
    'hidepreviewborderoverlay',
    'hidepreviewborderoverlaytitle',
    'hidepreviewbordertitle',
    'hidepreviewoverlaytitle',
    'hidepreviewtitle',
    'imageaboveborder',
    'imageaboveborderenabled',
    'imageaboveborderenabled_help',
    'imageaboveborderandoverlayenabled',
    'imageaboveborderandoverlayenabled_help',
    'imageaboveoverlay',
    'imageaboveoverlayenabled',
    'imageaboveoverlayenabled_help',
    'imagebelowinheritedenabled',
    'imagebelowinheritedenabled_help',
    'imageaboveinheritedenabled',
    'imageaboveinheritedenabled_help',
    'imagewithinheritedorder',
    'imagebelowborder',
    'imagebelowoverlay',
    'imagecenterfixed',
    'imagecenterfixed_help',
    'imagecenterfixedoff',
    'imagecenterfixedon',
    'imagelayeroptions',
    'imageopacity',
    'invaliddeletealllayersresponse',
    'invaliddeleteselectedlayerresponse',
    'keepaspectpreviewimage',
    'layertype:border',
    'layertype:image',
    'layertype:overlay',
    'layeroverlay',
    'overlayappearance',
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
    'overlaytarget_help',
    'overlaytarget:banner',
    'overlaytarget:both',
    'overlaytarget:slideshow',
    'overlaytitleabove',
    'previewactivitytitle',
    'previewcoursetitle',
    'previewsitetitle',
    'modalbodynotfound',
    'no',
    'nextimages',
    'previewunavailable',
    'previousimages',
    'pullforwardpreviewlayer',
    'pushbehindpreviewlayer',
    'recenterpreviewimage',
    'recenterallpreviewimages',
    'redopreviewchange',
    'savebannerlayers',
    'selectlayer',
    'showhideallimages',
    'showimageinpreview',
    'showotherlayers',
    'showslideshowpreview',
    'showoverlaylayer',
    'showpreviewborder',
    'showpreviewborderoverlay',
    'showpreviewborderoverlaytitle',
    'showpreviewbordertitle',
    'showpreviewoverlaytitle',
    'showpreviewtitle',
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
        set_config($prefix . 'fontsize', max(25, min(480, optional_param('fontsize', 100, PARAM_FLOAT))), 'local_course_banner_builder');
        set_config($prefix . 'lineheight', max(40, min(540, optional_param('lineheight', 105, PARAM_FLOAT))), 'local_course_banner_builder');
        set_config($prefix . 'fontfamily', $fontfamily, 'local_course_banner_builder');
        set_config($prefix . 'color', strtoupper($color), 'local_course_banner_builder');
        $align = optional_param('align', 'center', PARAM_ALPHA);
        set_config(
            $prefix . 'align',
            in_array($align, ['left', 'center', 'right'], true) ? $align : 'center',
            'local_course_banner_builder'
        );
        foreach ([
            'bold',
            'italic',
            'underline',
            'strike',
            'allcaps',
            'aboveborder',
            'aboveoverlay',
            'replacemoodletitle',
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
            'framepadding' => [18, 0, 240],
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
    $bannerformatcontext = optional_param('bannerformatcontext', $issitebanneradmin ? 'site' : 'course', PARAM_ALPHA);
    if ($bannerformatcontext === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE) {
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
    $currentisoverlaylayer = $currentelement && !empty($currentelement->overlayenabled);
}
$formmode = $elementid ? ($currentisborderlayer ? 'editborder' : ($currentisoverlaylayer ? 'editoverlay' : 'editimage')) : 'create';
$previewdefinition = $selectedsource
    ? \local_course_banner_builder\manager::export_modal_preview_definition($selectedsource, $elementid)
    : [];
$titlepreviewdefinition = $selectedsource
    ? \local_course_banner_builder\manager::export_modal_preview_definition($selectedsource, 0, true)
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
    $fieldvalue = required_param('fieldvalue', PARAM_TEXT);
    \local_course_banner_builder\manager::update_source_setting_field($selectedsource, $fieldname, $fieldvalue);
    redirect(
        new moodle_url($adminpagepath, $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($updatesourceparentfield && confirm_sesskey()) {
    $targetsourcekey = required_param('sourcekey', PARAM_TEXT);
    $fieldvalue = required_param('fieldvalue', PARAM_TEXT);
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
    $sourceparentkey = optional_param('sourceparentkey', '', PARAM_TEXT);
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
        if (optional_param($fieldname, null, PARAM_TEXT) !== null) {
            $defaultvalue = in_array($cropfield, ['widthpercent', 'heightpercent'], true) ? 100 : 0;
            $data->{$fieldname} = $cropfield === 'enabled' ?
                optional_param($fieldname, 0, PARAM_BOOL) :
                optional_param($fieldname, $defaultvalue, PARAM_FLOAT);
        }
    }
    $previewcropstate = optional_param('previewcropstate', '', PARAM_RAW);
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
    $ajaxmodalid = match ($formmode) {
        'editborder' => 'local-course-banner-builder-edit-border-layer-modal',
        'editoverlay' => 'local-course-banner-builder-edit-overlay-layer-modal',
        default => 'local-course-banner-builder-edit-image-layer-modal',
    };
    $ajaxmodaltitle = match ($formmode) {
        'editborder' => get_string('layerborder', 'local_course_banner_builder'),
        'editoverlay' => get_string('editoverlaylayer', 'local_course_banner_builder'),
        default => get_string('editimage', 'local_course_banner_builder'),
    };
    local_course_banner_builder_render_layer_modal($ajaxmodalid, $ajaxmodaltitle, function () use ($form) {
        $form->display();
    });
    exit;
}

$PAGE->requires->js_call_amd('local_course_banner_builder/admin_manage', 'init');

echo $OUTPUT->header();
echo html_writer::start_div(implode(' ', $adminclasses));

local_course_banner_builder_render_banner_format_modal(
    (new moodle_url($adminpagepath, $selectedsourceparams))->out(false),
    \local_course_banner_builder\manager::get_course_banner_format(),
    \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_COURSE
);
local_course_banner_builder_render_banner_format_modal(
    (new moodle_url($adminpagepath, $selectedsourceparams))->out(false),
    \local_course_banner_builder\manager::get_site_banner_format(),
    \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE
);
$formatbutton = static function (string $context): string {
    $issitecontext = $context === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE;
    $modalid = 'local-course-banner-builder-banner-format-' . ($issitecontext ? 'site' : 'course') . '-modal';
    return html_writer::tag(
        'button',
        html_writer::tag('i', '', ['class' => 'fa fa-columns me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string(
                $issitecontext ? 'sitebannerformatbutton' : 'coursebannerformatbutton',
                'local_course_banner_builder'
            )),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-admin-format-button',
            'data-toggle' => 'modal',
            'data-target' => '#' . $modalid,
            'data-bs-toggle' => 'modal',
            'data-bs-target' => '#' . $modalid,
        ]
    );
};
$deletepluginsettingsform = static function (string $action): string {
    return html_writer::tag(
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
            'action' => $action,
            'class' => 'd-inline local-course-banner-builder-admin-reset-form',
        ]
    );
};
$coursebannersurl = new moodle_url('/local/course_banner_builder/admin_manage.php');
$sitebannerurl = new moodle_url('/local/course_banner_builder/admin_site.php');
echo html_writer::div(
    html_writer::link(
        $coursebannersurl,
        html_writer::tag('i', '', ['class' => 'fa fa-image me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('managecoursebannersquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-admin-switch-button']
    ) .
    html_writer::link(
        $sitebannerurl,
        html_writer::tag('i', '', ['class' => 'fa fa-desktop me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('managesitebannerquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-admin-switch-button']
    ) .
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_slideshow.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-images me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('manageslideshowquick', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-admin-slideshow-button']
    ) .
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_transfer.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-right-left me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('transferconfig', 'local_course_banner_builder')),
        ['class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action']
    ) .
    $formatbutton(\local_course_banner_builder\manager::SLIDESHOW_CONTEXT_COURSE) .
    $formatbutton(\local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE) .
    $deletepluginsettingsform((new moodle_url($adminpagepath))->out(false)),
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
    $sitepreviewsource = \local_course_banner_builder\manager::resolve_source(
        \local_course_banner_builder\manager::SITE_SOURCE_KEY
    );
    echo local_course_banner_builder_render_title_settings_modal(
        'site',
        get_string('editsitebannertitle', 'local_course_banner_builder'),
        (new moodle_url($adminpagepath))->out(false),
        $sitepreviewsource ? \local_course_banner_builder\manager::export_modal_preview_definition($sitepreviewsource, 0, true) : null
    );
    echo html_writer::end_div();
} else {
    $coursebannersenabled = \local_course_banner_builder\manager::is_course_banner_enabled();
    $activitybannersenabled = \local_course_banner_builder\manager::course_banners_on_activity_pages_enabled();
    $defaultimagebannersenabled = \local_course_banner_builder\manager::course_default_image_banners_enabled();
    $customoverviewimagesenabled = \local_course_banner_builder\manager::course_custom_overview_images_enabled();
    $courseoptionhelpbutton = static function (string $label): string {
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
    ) . $courseoptionhelpbutton(get_string('coursebannersenabled_help', 'local_course_banner_builder'));
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
    ) . $courseoptionhelpbutton(get_string('courseactivitybannersenabled_help', 'local_course_banner_builder'));
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
    ) . $courseoptionhelpbutton(get_string('coursebannerdefaultimageenabled_help', 'local_course_banner_builder'));
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
        $courseoptionhelpbutton(get_string('coursecustomoverviewimageenabled_help', 'local_course_banner_builder')),
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
            (new moodle_url($adminpagepath, $selectedsourceparams))->out(false),
            $selectedsource ? $titlepreviewdefinition : null
        );
    }
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
        $sitesourcelabel = \local_course_banner_builder\manager::is_site_source($selectedsource);

        echo html_writer::start_div('modal fade', [
            'id' => 'local-course-banner-builder-source-settings-modal',
            'tabindex' => '-1',
            'role' => 'dialog',
            'aria-labelledby' => 'local-course-banner-builder-source-settings-modal-title',
            'aria-hidden' => 'true',
            'data-auto-open-source-settings' => empty($categorysettings->id) && !$issitebanneradmin ? '1' : '0',
        ]);
        echo html_writer::start_div('modal-dialog modal-lg', ['role' => 'document']);
        echo html_writer::start_div('modal-content');
        echo html_writer::start_div('modal-header d-flex align-items-center');
        echo html_writer::tag('h5', get_string(
            $sitesourcelabel ? 'sitebannersourcesettings' : 'categorysettings',
            'local_course_banner_builder'
        ), [
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
        $sourceparentoptions = \local_course_banner_builder\manager::get_configured_source_parent_options(
            (string)$selectedsource->sourcekey
        );
        $hassourceparentoptions = count($sourceparentoptions) > 1;
        $selectedparentkey = (string)($categorysettings->sourceparentkey ?? '');
        if ($selectedparentkey !== '' && !array_key_exists($selectedparentkey, $sourceparentoptions)) {
            $selectedparentkey = '';
        }
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
            'aria-disabled' => $hassourceparentoptions ? 'false' : 'true',
            'data-source-dropdown-label' => '1',
        ] + ($hassourceparentoptions ? [] : ['disabled' => 'disabled']));
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
                html_writer::span(get_string(
                    $sitesourcelabel ? 'savesitebannersourcesettings' : 'savecategorysettings',
                    'local_course_banner_builder'
                )),
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
            'local-course-banner-builder-edit-overlay-layer-modal' => get_string('editoverlaylayer', 'local_course_banner_builder'),
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
            'editoverlay' => 'local-course-banner-builder-edit-overlay-layer-modal',
            'editimage' => 'local-course-banner-builder-edit-image-layer-modal',
            default => 'local-course-banner-builder-add-layer-modal',
        };
        $layermodaltitle = match ($formmode) {
            'editborder' => get_string('editborder', 'local_course_banner_builder'),
            'editoverlay' => get_string('editoverlaylayer', 'local_course_banner_builder'),
            'editimage' => get_string('editimage', 'local_course_banner_builder'),
            default => get_string('addlayer', 'local_course_banner_builder'),
        };

        local_course_banner_builder_render_layer_modal($layermodalid, $layermodaltitle, function () use ($form) {
            $form->display();
        });
}

if ($selectedsource) {
    $selectedsourcecontext = \local_course_banner_builder\manager::export_selected_source($selectedsource);
    $selectedsourcecontext['sourcevisualeditorhtml'] = local_course_banner_builder_render_source_visual_editor(
        $selectedsource,
        !empty($sourcechainpreview)
    );
    $selectedsourcecontext['sourcelayerslistlabel'] = get_string('sourcelayerslist', 'local_course_banner_builder');
    $selectedsourcecontext['nosourcelayerslabel'] = get_string('nosourcelayers', 'local_course_banner_builder');
    $selectedsourcecontext['selectedsourcestatuslabel'] = get_string('selectedcategorystatus', 'local_course_banner_builder');
    $selectedsourcecontext['sourcesettingsshortlabel'] = get_string('sourcesettingsshort', 'local_course_banner_builder');
    if (\local_course_banner_builder\manager::is_site_source($selectedsource)) {
        $selectedsourcecontext['sourcelayerslistlabel'] = get_string(
            'sitebannersourcelayerslist',
            'local_course_banner_builder'
        );
        $selectedsourcecontext['nosourcelayerslabel'] = get_string(
            'nositebannersourcelayers',
            'local_course_banner_builder'
        );
        $selectedsourcecontext['selectedsourcestatuslabel'] = get_string(
            'selectedsitebannersourcestatus',
            'local_course_banner_builder'
        );
        $selectedsourcecontext['sourcesettingsshortlabel'] = get_string(
            'sitebannersourcesettingsshort',
            'local_course_banner_builder'
        );
    }
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
            html_writer::span($selectedsourcecontext['selectedsourcestatuslabel'], '', [
                'class' => 'local-course-banner-builder-section-title-text',
            ]),
            'local-course-banner-builder-section-heading'
        ) .
            html_writer::tag(
                'button',
                html_writer::tag('i', '', ['class' => 'fa fa-cog py-2 me-3', 'aria-hidden' => 'true']) .
                    html_writer::span($selectedsourcecontext['sourcesettingsshortlabel']),
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
