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
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

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
    echo html_writer::start_div('modal-header');
    echo html_writer::tag('h5', $title, [
        'class' => 'modal-title',
        'id' => $modalid . '-title',
    ]);
    echo html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
        'type' => 'button',
        'class' => 'close',
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
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

/**
 * Render one source visual editor image layer.
 *
 * @param array $layer
 * @return string
 */
function local_course_banner_builder_render_source_visual_editor_image_layer(array $layer): string {
    $attributes = [
        'class' => 'local-course-banner-builder-preview-image-layer local-course-banner-builder-source-preview-layer' .
            (!empty($layer['enabled']) ? '' : ' local-course-banner-builder-source-preview-layer--disabled'),
        'style' => trim((string)($layer['wrapperstyle'] ?? '') . ' z-index: ' . (int)($layer['zindex'] ?? 1) . ';'),
        'data-source-preview-layer' => '1',
        'data-source-preview-layer-id' => (string)($layer['id'] ?? 0),
        'data-source-preview-editable' => !empty($layer['editable']) ? '1' : '0',
        'role' => 'button',
        'tabindex' => '0',
        'data-preview-fitmode' => (string)($layer['fitmode'] ?? ''),
        'data-preview-anchor' => (string)($layer['positionanchor'] ?? \local_course_banner_builder\manager::POSITION_CENTER),
        'data-preview-custom-width' => (string)($layer['customwidthpercent'] ?? 100),
        'data-preview-custom-height' => (string)($layer['customheightpercent'] ?? 100),
        'data-preview-keep-aspect' => !empty($layer['customsizekeepaspect']) ? '1' : '0',
        'data-preview-dynamic-image' => !empty($layer['dynamicimagesizeenabled']) ? '1' : '0',
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

    $content = html_writer::empty_tag('img', [
        'src' => (string)($layer['url'] ?? ''),
        'alt' => '',
        'class' => 'local-course-banner-builder-preview-image',
        'style' => (string)($layer['imagestyle'] ?? ''),
        'data-preview-image-tag' => '1',
        'draggable' => 'false',
    ]);

    if (!empty($layer['editable'])) {
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
    $wrapperattrs = [
        'class' => 'local-course-banner-builder-preview-border-layer local-course-banner-builder-source-preview-border',
        'style' => trim((string)($layer['wrapperstyle'] ?? '') . ' z-index: ' . (int)($layer['zindex'] ?? 1) . ';'),
        'data-source-preview-border' => '1',
        'data-preview-sortorder' => (string)($layer['sortorder'] ?? 0),
        'data-preview-zindex' => (string)($layer['zindex'] ?? 1),
    ];

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
 * Render the selected source visual editor.
 *
 * @param stdClass $source
 * @return string
 */
function local_course_banner_builder_render_source_visual_editor(\stdClass $source): string {
    $definition = \local_course_banner_builder\manager::export_source_visual_editor_definition($source);
    $sourcesettings = \local_course_banner_builder\manager::get_source_settings($source);
    $hassourcesettings = !empty($sourcesettings->id);
    $disabledattributes = $hassourcesettings ? [] : [
        'disabled' => 'disabled',
        'aria-disabled' => 'true',
        'data-container' => 'body',
        'data-toggle' => 'popover',
        'data-placement' => 'top',
        'data-content' => '<div class="no-overflow"><p>' . s(get_string('sourcesettingsrequiredbeforelayers', 'local_course_banner_builder')) . '</p></div>',
        'data-html' => 'true',
        'data-trigger' => 'hover focus',
    ];

    $buttoncontent = static function(string $iconclass, string $label): string {
        return html_writer::tag('i', '', [
            'class' => 'fa ' . $iconclass . ' me-2',
            'aria-hidden' => 'true',
        ]) . html_writer::span($label);
    };

    $layershtml = '';
    foreach ($definition['layers'] as $layer) {
        if (($layer['type'] ?? '') === 'border') {
            $layershtml .= local_course_banner_builder_render_source_visual_editor_border_layer($layer);
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

    return html_writer::div(
        html_writer::tag('h4', get_string('sourcevisualeditor', 'local_course_banner_builder'), [
            'class' => 'h6 local-course-banner-builder-table-title mb-3',
        ]) .
        html_writer::div(
            html_writer::div(
                html_writer::div(
                    html_writer::div(
                        $layershtml,
                        'local-course-banner-builder-border-preview-frame local-course-banner-builder-border-preview-frame--moodle',
                        [
                            'data-source-preview-frame' => '1',
                            'data-default-fitmode' => (string)($definition['defaultfitmode'] ?? \local_course_banner_builder\manager::FIT_MODE_BANNER),
                        ]
                    ),
                    'local-course-banner-builder-source-preview-surface'
                ),
                'local-course-banner-builder-source-preview-canvas'
            ) .
            html_writer::div(
                html_writer::tag('button', $buttoncontent('fa-eye-slash', get_string('hidepreviewborder', 'local_course_banner_builder')), [
                    'type' => 'button',
                    'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
                    'data-action' => 'local-course-banner-builder-toggle-preview-border',
                    'data-show-label' => get_string('showpreviewborder', 'local_course_banner_builder'),
                    'data-hide-label' => get_string('hidepreviewborder', 'local_course_banner_builder'),
                    'data-show-icon' => 'fa-eye',
                    'data-hide-icon' => 'fa-eye-slash',
                    'data-preview-border-visible' => '1',
                    'aria-pressed' => 'true',
                ]) .
                html_writer::tag('button', $buttoncontent('fa-crosshairs', get_string('recenterpreviewimage', 'local_course_banner_builder')), [
                    'type' => 'button',
                    'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button local-course-banner-builder-source-preview-recenter',
                    'data-action' => 'local-course-banner-builder-recenter-source-preview-image',
                ]) .
                html_writer::tag('button', $buttoncontent('fa-bullseye', get_string('recenterallpreviewimages', 'local_course_banner_builder')), [
                    'type' => 'button',
                    'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
                    'data-action' => 'local-course-banner-builder-recenter-all-source-preview-images',
                ]) .
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
                html_writer::tag('button', $buttoncontent('fa-arrow-down', get_string('pushbehindpreviewlayer', 'local_course_banner_builder')), [
                    'type' => 'button',
                    'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
                    'data-action' => 'local-course-banner-builder-push-source-preview-layer-behind',
                ]) .
                html_writer::tag('button', $buttoncontent('fa-arrow-up', get_string('pullforwardpreviewlayer', 'local_course_banner_builder')), [
                    'type' => 'button',
                    'class' => 'btn btn-outline-secondary local-course-banner-builder-source-preview-button',
                    'data-action' => 'local-course-banner-builder-pull-source-preview-layer-forward',
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
                html_writer::tag('button', $buttoncontent('fa-trash-can', get_string('deleteselectedlayer', 'local_course_banner_builder')), [
                    'type' => 'button',
                    'class' => 'btn btn-danger local-course-banner-builder-source-preview-button',
                    'data-action' => 'local-course-banner-builder-delete-selected-preview-layer',
                    'data-confirm-message' => get_string('deleteselectedlayersconfirm', 'local_course_banner_builder'),
                    'hidden' => 'hidden',
                ]) .
                html_writer::tag('button', $buttoncontent('fa-save', get_string('savepreviewchanges', 'local_course_banner_builder')), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary local-course-banner-builder-source-preview-button',
                    'form' => 'local-course-banner-builder-source-preview-save-form',
                ] + $disabledattributes) .
                html_writer::tag('button', $buttoncontent('fa-plus', get_string('addlayer', 'local_course_banner_builder')),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-outline-secondary local-course-banner-builder-dashed-action local-course-banner-builder-source-preview-button local-course-banner-builder-source-preview-button--full local-course-banner-builder-add-layer-primary',
                        'data-toggle' => 'modal',
                        'data-target' => '#local-course-banner-builder-add-layer-modal',
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#local-course-banner-builder-add-layer-modal',
                    ] + $disabledattributes
                ),
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
$deletealllayersajax = optional_param('deletealllayersajax', 0, PARAM_BOOL);
$deletepreviewlayerajax = optional_param('deletepreviewlayerajax', 0, PARAM_INT);

admin_externalpage_setup('local_course_banner_builder_manage');
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

$url = new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('managebanners', 'local_course_banner_builder'));
$PAGE->set_heading(get_string('managebanners', 'local_course_banner_builder'));
$PAGE->requires->css('/local/course_banner_builder/styles.css');
$PAGE->navbar->add(get_string('managebanners', 'local_course_banner_builder'), new moodle_url('/local/course_banner_builder/admin_manage.php'));

if ($selectedsource) {
    $PAGE->navbar->add($selectedsource->label, $url);
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

$themename = strtolower((string)($PAGE->theme->name ?? ''));
$adminclasses = [
    'local-course-banner-builder-admin',
    $themename === 'easyedu' ? 'local-course-banner-builder-admin--easyedu' : 'local-course-banner-builder-admin--native',
];

$categoryoptions = \local_course_banner_builder\manager::get_category_source_options();
$customfieldoptions = \local_course_banner_builder\manager::get_enabled_customfield_source_options();

if ($deleteelementid && confirm_sesskey()) {
    \local_course_banner_builder\manager::delete_banner_element($deleteelementid);
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
        get_string('bannerdeleted', 'local_course_banner_builder')
    );
}

if ($deleteselectedlayers && confirm_sesskey()) {
    $selectedelementids = optional_param_array('selectedelements', [], PARAM_INT);
    $deleted = \local_course_banner_builder\manager::delete_banner_elements($selectedelementids);
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
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
            new moodle_url('/local/course_banner_builder/admin_manage.php', ['sourcekey' => $sourcetodelete->sourcekey]),
            get_string('categoryimagesdeleted', 'local_course_banner_builder')
        );
    }

    $continueurl = new moodle_url('/local/course_banner_builder/admin_manage.php', [
        'sourcekey' => $deletesourceimages,
        'deletesourceimages' => $deletesourceimages,
        'confirmdeleteimages' => 1,
        'sesskey' => sesskey(),
    ]);
    $cancelurl = new moodle_url('/local/course_banner_builder/admin_manage.php', ['sourcekey' => $deletesourceimages]);

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
            new moodle_url('/local/course_banner_builder/admin_manage.php', ['sourcekey' => $sourcetodelete->sourcekey]),
            get_string('categorycontentdeleted', 'local_course_banner_builder')
        );
    }

    $continueurl = new moodle_url('/local/course_banner_builder/admin_manage.php', [
        'sourcekey' => $deletesourcecontent,
        'deletesourcecontent' => $deletesourcecontent,
        'confirmdeletecategory' => 1,
        'sesskey' => sesskey(),
    ]);
    $cancelurl = new moodle_url('/local/course_banner_builder/admin_manage.php', ['sourcekey' => $deletesourcecontent]);

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
if ($elementid) {
    $currentelement = \local_course_banner_builder\manager::get_banner_element($elementid);
    $currentisborderlayer = $currentelement &&
        !\local_course_banner_builder\manager::get_banner_image_file($currentelement) &&
        !empty($currentelement->borderenabled);
}
$formmode = $elementid ? ($currentisborderlayer ? 'editborder' : 'editimage') : 'create';
$previewdefinition = $selectedsource
    ? \local_course_banner_builder\manager::export_modal_preview_definition($selectedsource, $elementid)
    : [];
$borderconflictstate = $selectedsource
    ? \local_course_banner_builder\manager::get_source_border_conflict_state(
        $selectedsource,
        $formmode === 'create' ? 0 : $elementid
    )
    : ['blocked' => false, 'messagekey' => 'sourcealreadyhasborder', 'inlinekey' => 'sourcealreadyhasborderinline'];

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
        new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
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
        new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($updatepreviewlayers && confirm_sesskey() && $selectedsource) {
    $storedsettings = \local_course_banner_builder\manager::get_source_settings($selectedsource);
    if (empty($storedsettings->id)) {
        redirect(
            new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
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
        new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($updatesourcesettingfield && confirm_sesskey() && $selectedsource) {
    $fieldname = required_param('fieldname', PARAM_ALPHAEXT);
    $fieldvalue = required_param('fieldvalue', PARAM_RAW_TRIMMED);
    \local_course_banner_builder\manager::update_source_setting_field($selectedsource, $fieldname, $fieldvalue);
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
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
        new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
        get_string('changessaved')
    );
}

$form = new \local_course_banner_builder\form\manage_banner_form(null, [
    'selectedcategoryid' => $selectedcategoryid,
    'sourcekey' => $selectedsourcekey,
    'elementid' => $elementid,
    'sourcehasborderlayer' => !empty($borderconflictstate['blocked']) ? 1 : 0,
    'borderconflictmessage' => get_string((string)($borderconflictstate['messagekey'] ?? 'sourcealreadyhasborder'), 'local_course_banner_builder'),
    'borderconflictmessageinline' => get_string((string)($borderconflictstate['inlinekey'] ?? 'sourcealreadyhasborderinline'), 'local_course_banner_builder'),
    'currentisborderlayer' => $currentisborderlayer,
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
        new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
        get_string('changessaved')
    );
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/category.php', ['category' => 'local_course_banner_builder']));
}

if ($data = $form->get_data()) {
    if ($selectedsource) {
        $storedsettings = \local_course_banner_builder\manager::get_source_settings($selectedsource);
        if (empty($storedsettings->id)) {
            redirect(
                new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams),
                get_string('sourcesettingsrequiredbeforelayers', 'local_course_banner_builder'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }
    }
    \local_course_banner_builder\manager::save_category_banner($data);
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', ['sourcekey' => $data->sourcekey]),
        get_string('changessaved')
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
document.addEventListener('click', function(e) {
    var dismissmodal = e.target.closest('[data-dismiss=\"modal\"], [data-bs-dismiss=\"modal\"]');
    if (dismissmodal) {
        var modal = dismissmodal.closest('.modal');
        if (modal) {
            e.preventDefault();
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
        localCourseBannerBuilderRecenterPreviewImage(recenterbutton.closest('form.mform, .mform, .modal'));
        return;
    }

    var recenterSourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-recenter-source-preview-image\"]');
    if (recenterSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderRecenterSourcePreviewImage(recenterSourcePreviewButton);
        return;
    }

    var recenterAllSourcePreviewButton = e.target.closest('[data-action=\"local-course-banner-builder-recenter-all-source-preview-images\"]');
    if (recenterAllSourcePreviewButton) {
        e.preventDefault();
        localCourseBannerBuilderRecenterAllSourcePreviewImages(recenterAllSourcePreviewButton);
        return;
    }

    var pushBehindButton = e.target.closest('[data-action=\"local-course-banner-builder-push-source-preview-layer-behind\"]');
    if (pushBehindButton) {
        e.preventDefault();
        localCourseBannerBuilderPushSelectedSourcePreviewLayerBehind(pushBehindButton);
        return;
    }

    var pullForwardButton = e.target.closest('[data-action=\"local-course-banner-builder-pull-source-preview-layer-forward\"]');
    if (pullForwardButton) {
        e.preventDefault();
        localCourseBannerBuilderPullSelectedSourcePreviewLayerForward(pullForwardButton);
        return;
    }

    var keepAspectButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-source-preview-keep-aspect\"]');
    if (keepAspectButton) {
        e.preventDefault();
        var keepAspectRoot = keepAspectButton.closest('[data-source-visual-editor=\"1\"]');
        var keepAspectLayer = keepAspectRoot ? (
            keepAspectRoot.querySelector('.local-course-banner-builder-source-preview-layer--selected') ||
            keepAspectRoot.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]')
        ) : null;
        if (keepAspectRoot && keepAspectLayer) {
            var keepAspectState = localCourseBannerBuilderGetSourcePreviewLayerState(keepAspectLayer);
            if (keepAspectState) {
                keepAspectState.customsizekeepaspect = !keepAspectState.customsizekeepaspect;
                localCourseBannerBuilderSetSourcePreviewLayerState(keepAspectLayer, keepAspectState);
                localCourseBannerBuilderSyncSourcePreviewLayer(keepAspectRoot, keepAspectLayer);
                localCourseBannerBuilderUpdateSourcePreviewRow(keepAspectRoot, keepAspectState);
                localCourseBannerBuilderSyncSourcePreviewPayload(keepAspectRoot);
                localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(keepAspectRoot);
            }
        }
        return;
    }

    var topLayerButton = e.target.closest('[data-action=\"local-course-banner-builder-toggle-source-preview-top-layer\"]');
    if (topLayerButton) {
        e.preventDefault();
        var topLayerRoot = topLayerButton.closest('[data-source-visual-editor=\"1\"]');
        var selectedLayer = topLayerRoot ? (
            topLayerRoot.querySelector('.local-course-banner-builder-source-preview-layer--selected') ||
            topLayerRoot.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]')
        ) : null;
        if (topLayerRoot && selectedLayer) {
            var state = localCourseBannerBuilderGetSourcePreviewLayerState(selectedLayer);
            if (state) {
                state.dynamicimagesizeenabled = !state.dynamicimagesizeenabled;
                localCourseBannerBuilderSetSourcePreviewLayerState(selectedLayer, state);
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
                nextVisible ? (togglePreviewBorderButton.getAttribute('data-hide-label') || 'Hide border') : (togglePreviewBorderButton.getAttribute('data-show-label') || 'Show border')
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

    var sourcePreviewRoot = e.target.closest('[data-source-visual-editor=\"1\"]');
    if (sourcePreviewRoot) {
        var sourceLayer = localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint(sourcePreviewRoot, e.clientX, e.clientY) ||
            (e.target.closest ? e.target.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null);
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

document.addEventListener('dblclick', function(e) {
    var sourcePreviewRoot = e.target.closest('[data-source-visual-editor=\"1\"]');
    var sourceLayer = sourcePreviewRoot ?
        (localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint(sourcePreviewRoot, e.clientX, e.clientY) ||
            (e.target.closest ? e.target.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null)) :
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
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        localCourseBannerBuilderSelectSourcePreviewLayer(
            sourceLayer.closest('[data-source-visual-editor=\"1\"]'),
            sourceLayer
        );
    }
});

document.addEventListener('change', function(e) {
    if (e.target.closest('.local-course-banner-builder-layer-select')) {
        localCourseBannerBuilderSyncSelectionButton();
    }
    if (e.target.matches('[data-layer-position-anchor=\"1\"]')) {
        localCourseBannerBuilderSyncOffsetFields();
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

document.addEventListener('pointerdown', function(e) {
    var sourcePreviewRoot = e.target.closest('[data-source-visual-editor=\"1\"]');
    if (!sourcePreviewRoot) {
        return;
    }
    var resizeHandle = e.target.closest('[data-source-preview-layer=\"1\"] [data-preview-resize-handle=\"1\"]');
    var targetLayer = localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint(sourcePreviewRoot, e.clientX, e.clientY) ||
        (e.target.closest ? e.target.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null);
    if (!targetLayer || targetLayer.getAttribute('data-source-preview-editable') !== '1') {
        return;
    }
    localCourseBannerBuilderSelectSourcePreviewLayer(sourcePreviewRoot, targetLayer);
    var selectedLayer = targetLayer;
    if (resizeHandle) {
        e.preventDefault();
        selectedLayer.setAttribute('data-preview-active-edge', resizeHandle.getAttribute('data-preview-resize-edge') || '');
        localCourseBannerBuilderStartSourcePreviewInteraction(
            e,
            resizeHandle.getAttribute('data-preview-resize-mode') === 'edge' ? 'resize-edge' : 'resize',
            selectedLayer
        );
        return;
    }
    if (e.target.closest('[data-preview-image-tag=\"1\"]') || e.target === selectedLayer) {
        e.preventDefault();
        localCourseBannerBuilderStartSourcePreviewInteraction(e, 'drag', selectedLayer);
    }
});

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

    if (hasSelection && selectedOption) {
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
    localCourseBannerBuilderGetSourceChainDescendants(key).forEach(function(row) {
        row.hidden = collapsed;
        if (collapsed) {
            var childToggle = row.querySelector('[data-action=\"local-course-banner-builder-toggle-source-chain\"]');
            if (childToggle) {
                localCourseBannerBuilderToggleSourceChain(childToggle, true);
            }
        }
    });
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
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-hidden', 'true');
    modal.innerHTML =
        '<div class=\"modal-dialog modal-xl\" role=\"document\">' +
            '<div class=\"modal-content\">' +
                '<div class=\"modal-header\">' +
                    '<h5 class=\"modal-title\">Source preview</h5>' +
                    '<button type=\"button\" class=\"close\" data-dismiss=\"modal\" data-bs-dismiss=\"modal\" aria-label=\"Close\">' +
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
    if (!url) {
        return;
    }
    var modal = localCourseBannerBuilderEnsureSourceChainPreviewModal();
    var body = modal.querySelector('[data-source-chain-preview-modal-body=\"1\"]');
    if (body) {
        body.innerHTML = '<div class=\"text-center p-4\"><span class=\"loading-icon icon-no-margin\"><i class=\"icon fa fa-spinner fa-spin fa-fw\" aria-hidden=\"true\"></i></span></div>';
    }
    localCourseBannerBuilderShowModal(modal);
    fetch(url, {
        credentials: 'same-origin',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).then(function(response) {
        if (!response.ok) {
            throw new Error('Unable to load source preview');
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
            body.innerHTML = '<p class=\"text-muted mb-0\">Preview unavailable.</p>';
            return;
        }
        Array.prototype.slice.call(panel.querySelectorAll('.local-course-banner-builder-source-preview-controls, form')).forEach(function(node) {
            node.remove();
        });
        body.innerHTML = '';
        body.appendChild(document.importNode(panel, true));
        localCourseBannerBuilderInitSourceVisualEditor(body);
        localCourseBannerBuilderInitPopovers(body);
    }).catch(function(error) {
        if (body) {
            body.innerHTML = '<p class=\"text-danger mb-0\">' + (error.message || 'Unable to load source preview') + '</p>';
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
    var borderSection = layerForm ? layerForm.querySelector('[data-border-section=\"1\"]') : null;
    var borderSummary = borderSection ? borderSection.querySelector('summary') : null;
    var advancedSection = layerForm ? layerForm.querySelector('[data-layer-advanced-section=\"1\"]') : null;
    var hasExistingImageInput = layerForm ? layerForm.querySelector('#id_hasexistingimage') : null;
    var currentIsBorderLayerInput = layerForm ? layerForm.querySelector('#id_currentisborderlayer') : null;
    var sourceHasBorderLayerInput = layerForm ? layerForm.querySelector('#id_sourcehasborderlayer') : null;
    var elementIdInput = layerForm ? layerForm.querySelector('#id_elementid') : null;
    if (!filemanager || !borderToggle || !borderSection) {
        return;
    }

    var hasFiles = filemanager.querySelectorAll('.fp-file:not(.fp-folder)').length > 0;
    var hasExistingImage = !!(hasExistingImageInput && parseInt(hasExistingImageInput.value || '0', 10) > 0);
    var currentIsBorderLayer = !!(currentIsBorderLayerInput && parseInt(currentIsBorderLayerInput.value || '0', 10) > 0);
    var sourceHasBorderLayer = !!(sourceHasBorderLayerInput && parseInt(sourceHasBorderLayerInput.value || '0', 10) > 0);
    var createBorderLocked = !!(borderSection && parseInt(borderSection.getAttribute('data-create-border-locked') || '0', 10) > 0);
    var editBorderLocked = !!(layerForm && layerForm.getAttribute('data-edit-border-locked') === '1');
    var hasExistingElement = !!(elementIdInput && parseInt(elementIdInput.value || '0', 10) > 0);
    var hasImage = hasFiles || hasExistingImage;
    var isBorderOnly = borderToggle.checked && !hasImage;
    var isExistingBorderLayer = hasExistingElement && (currentIsBorderLayer || editBorderLocked);
    var sourceBlocksNewBorder = (sourceHasBorderLayer || createBorderLocked) && !isExistingBorderLayer;
    localCourseBannerBuilderPlaceExistingBorderNote(layerForm, existingBorderNote, borderToggle);

    localCourseBannerBuilderSetDisabledState(filemanager, isBorderOnly, '[data-banner-filemanager=\"1\"]');
    localCourseBannerBuilderSetDisabledState(borderSection, hasImage, '[data-border-toggle=\"1\"]');
    localCourseBannerBuilderSetDisabledState(advancedSection, borderToggle.checked, '');
    if (sourceBlocksNewBorder && borderToggle.checked) {
        borderToggle.checked = false;
    }
    borderToggle.disabled = hasImage || isExistingBorderLayer || sourceBlocksNewBorder;
    borderToggle.setAttribute('aria-disabled', (hasImage || isExistingBorderLayer || sourceBlocksNewBorder) ? 'true' : 'false');
    if (isExistingBorderLayer) {
        borderToggle.checked = true;
        var borderToggleWrapper = borderToggle.closest('.form-check, .fitem, .felement');
        if (borderToggleWrapper) {
            borderToggleWrapper.classList.add('local-course-banner-builder-option-disabled');
        }
    }
    if (filemanagerItem) {
        filemanagerItem.hidden = isBorderOnly;
    }
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
        borderSection.classList.toggle('local-course-banner-builder-disabled', !!sourceBlocksNewBorder);
        if (sourceBlocksNewBorder) {
            borderSection.open = false;
        }
    }
    if (borderSummary) {
        borderSummary.setAttribute('aria-disabled', sourceBlocksNewBorder ? 'true' : 'false');
        borderSummary.tabIndex = sourceBlocksNewBorder ? -1 : 0;
    }
    localCourseBannerBuilderSyncBorderAccordion(layerForm);
    localCourseBannerBuilderSyncBorderPreview(layerForm);
}

function localCourseBannerBuilderSyncOffsetFields(scope) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope);
    var anchor = layerForm ? layerForm.querySelector('[data-layer-position-anchor=\"1\"]') : null;
    if (!anchor) {
        return;
    }
    var fitOverride = layerForm.querySelector('#id_fitmodeoverride');
    var previewRoot = layerForm.querySelector('[data-layer-banner-preview=\"1\"]');
    var defaultFitMode = previewRoot ? (previewRoot.getAttribute('data-default-fitmode') || 'bannerfit') : 'bannerfit';
    var effectiveFitMode = fitOverride && fitOverride.value ? fitOverride.value : defaultFitMode;
    var isBannerFill = effectiveFitMode === 'bannerfit';
    var anchorWrapper = anchor.closest('.fitem, .form-group, .mb-3');
    anchor.disabled = isBannerFill;
    if (anchorWrapper) {
        anchorWrapper.hidden = isBannerFill;
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
        var isVisible = !isBannerFill && visibleSides.indexOf(side) !== -1;
        input.disabled = !isVisible;
        if (wrapper) {
            wrapper.hidden = !isVisible;
        }
        if (sliderWrapper) {
            sliderWrapper.hidden = !isVisible;
        }
    });
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
    if (isCustom && keepAspect.checked && heightInput.value !== widthInput.value) {
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
        if (slider.dataset.percentSliderBound === '1') {
            return;
        }

        var targetSelector = '#' + slider.getAttribute('data-percent-slider-for');
        var targetInput = layerForm.querySelector(targetSelector);
        var output = layerForm.querySelector('[data-percent-slider-output-for=\"' + slider.getAttribute('data-percent-slider-for') + '\"]');
        if (!targetInput) {
            return;
        }

        var syncFromInput = function() {
            var fallbackValue = localCourseBannerBuilderNormaliseNumericValue(targetInput.getAttribute('data-number-min') || slider.min || '0', 0);
            var value = localCourseBannerBuilderNormaliseNumericValue(targetInput.value, fallbackValue);
            slider.value = String(value);
            if (output) {
                output.textContent = String(value) + (slider.getAttribute('data-range-suffix') || '%');
            }
        };

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

function localCourseBannerBuilderSyncLinkedCustomSizeInputs(scope, sourceInput) {
    var layerForm = localCourseBannerBuilderGetLayerScope(scope || sourceInput);
    if (!layerForm || !sourceInput) {
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
        if (input.dataset.rangeReady === '1') {
            return;
        }
        input.type = 'range';
        input.min = input.getAttribute('data-range-min') || '0';
        input.max = input.getAttribute('data-range-max') || '1';
        input.step = input.getAttribute('data-range-step') || '0.01';
        input.classList.add('local-course-banner-builder-range');
        input.classList.add('theme-easyedu-range');
        var describedby = input.getAttribute('aria-describedby');
        var output = document.createElement('span');
        output.className = 'local-course-banner-builder-range-output theme-easyedu-range-output';
        output.setAttribute('aria-live', 'polite');
        input.insertAdjacentElement('afterend', output);
        if (describedby) {
            input.setAttribute('aria-describedby', describedby);
        }
        var sync = function() {
            output.textContent = input.value + (input.getAttribute('data-range-suffix') || '');
        };
        input.addEventListener('input', sync);
        sync();
        input.dataset.rangeReady = '1';
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
        if (!textInput || !pickerInput) {
            return;
        }
        var syncPickerFromText = function() {
            pickerInput.value = localCourseBannerBuilderNormaliseHexColor(textInput.value, pickerInput.value || '#56B9C0');
            pickerInput.disabled = !!textInput.disabled;
        };
        if (!textInput.dataset.colorPickerBound) {
            textInput.addEventListener('input', syncPickerFromText);
            textInput.addEventListener('change', syncPickerFromText);
            textInput.dataset.colorPickerBound = '1';
        }
        if (!pickerInput.dataset.colorPickerBound) {
            pickerInput.addEventListener('input', function() {
                textInput.value = pickerInput.value.toUpperCase();
                textInput.dispatchEvent(new Event('input', {bubbles: true}));
            });
            pickerInput.addEventListener('change', function() {
                textInput.value = pickerInput.value.toUpperCase();
                textInput.dispatchEvent(new Event('change', {bubbles: true}));
            });
            pickerInput.dataset.colorPickerBound = '1';
        }
        syncPickerFromText();
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
        Array.prototype.slice.call(frame.querySelectorAll('[data-preview-current-border=\"1\"]')).forEach(function(borderLayer) {
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
        var radiusPixels = rounded ? Math.max(8, previewWidth) : 0;
        var radiusValue = radiusPixels + 'px';
        var cutoutValue = (previewWidth + radiusPixels) + 'px';
        frame.style.setProperty('--local-course-banner-builder-preview-top-width', sides.top ? (previewWidth + 'px') : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-right-width', sides.right ? (previewWidth + 'px') : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-width', sides.bottom ? (previewWidth + 'px') : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-left-width', sides.left ? (previewWidth + 'px') : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-top-left-radius', (rounded && sides.top && sides.left) ? radiusValue : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-top-right-radius', (rounded && sides.top && sides.right) ? radiusValue : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-right-radius', (rounded && sides.bottom && sides.right) ? radiusValue : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-left-radius', (rounded && sides.bottom && sides.left) ? radiusValue : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-top-left-offset', (sides.top && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-top-right-offset', (sides.top && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-left-offset', (sides.bottom && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-right-offset', (sides.bottom && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-right-top-offset', (sides.top && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-right-bottom-offset', (sides.bottom && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-left-top-offset', (sides.top && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-left-bottom-offset', (sides.bottom && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-top-left-corner-size', (sides.top && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-top-right-corner-size', (sides.top && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-right-corner-size', (sides.bottom && sides.right) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-left-corner-size', (sides.bottom && sides.left) ? (rounded ? cutoutValue : (previewWidth + 'px')) : '0px');
        frame.style.setProperty('--local-course-banner-builder-preview-top-left-fade-start', (radiusPixels + (previewWidth * (fade / 100))) + 'px');
        frame.style.setProperty('--local-course-banner-builder-preview-top-right-fade-start', (radiusPixels + (previewWidth * (fade / 100))) + 'px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-right-fade-start', (radiusPixels + (previewWidth * (fade / 100))) + 'px');
        frame.style.setProperty('--local-course-banner-builder-preview-bottom-left-fade-start', (radiusPixels + (previewWidth * (fade / 100))) + 'px');
        frame.style.setProperty('--local-course-banner-builder-preview-color-solid', solid);
        frame.style.setProperty('--local-course-banner-builder-preview-color-transparent', transparent);
        frame.style.setProperty('--local-course-banner-builder-preview-fade-stop', fadeStop);

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
        styles.push('width: ' + customBox.width + '%;');
        styles.push('height: ' + customBox.height + '%;');
        styles.push('object-fit: ' + (options.keepAspect ? 'contain' : 'fill') + ';');
        styles.push('object-position: ' + objectPosition + ';');
    } else {
        var originalBox = localCourseBannerBuilderGetOriginalPreviewBox(options.naturalWidth || 0, options.naturalHeight || 0);
        styles.push('width: ' + originalBox.width + '%;', 'height: auto;');
        styles.push('object-fit: none;', 'object-position: ' + objectPosition + ';');
    }

    if (fitMode !== 'bannerfit') {
        localCourseBannerBuilderAppendPreviewPositionStyles(styles, anchor, options.offsets);
    } else {
        styles.push('left: 0;', 'top: 0;');
    }
    return styles.join(' ');
}

function localCourseBannerBuilderAppendPreviewPositionStyles(styles, anchor, offsets) {
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
    if (!keepAspect || naturalWidth <= 0 || naturalHeight <= 0) {
        return {
            width: customWidth,
            height: customHeight
        };
    }

    var imageAspect = naturalWidth / naturalHeight;
    var bannerAspect = 1600 / 400;
    var heightFromWidth = customWidth * (bannerAspect / imageAspect);

    return {
        width: customWidth,
        height: heightFromWidth
    };
}

function localCourseBannerBuilderGetContainedPreviewBox(naturalWidth, naturalHeight) {
    if (naturalWidth <= 0 || naturalHeight <= 0) {
        return {width: 100, height: 100};
    }
    var imageAspect = naturalWidth / naturalHeight;
    var bannerAspect = 1600 / 400;
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

function localCourseBannerBuilderGetOriginalPreviewBox(naturalWidth, naturalHeight) {
    return {
        width: naturalWidth > 0 ? Math.max(0, (naturalWidth / 1600) * 100) : 100,
        height: naturalHeight > 0 ? Math.max(0, (naturalHeight / 400) * 100) : 100
    };
}

function localCourseBannerBuilderGetPreviewImageUrl(form, layer) {
    if (!form || !layer) {
        return '';
    }
    var filemanager = form.querySelector('#fitem_id_bannerimage_filemanager');
    if (filemanager) {
        if (typeof form.dataset.activeDraftIndex !== 'undefined' && form.dataset.activeDraftIndex !== '') {
            var files = localCourseBannerBuilderGetDraftPreviewFiles(form);
            var activeFile = files.find(function(file) {
                return String(file.index) === String(form.dataset.activeDraftIndex);
            });
            if (activeFile && activeFile.url) {
                return activeFile.url;
            }
        }
        var image = filemanager.querySelector('img[src*=\"/draftfile.php/\"], img[src*=\"/pluginfile.php/\"]');
        if (image && image.getAttribute('src')) {
            return image.getAttribute('src');
        }
        var link = filemanager.querySelector('a[href*=\"/draftfile.php/\"], a[href*=\"/pluginfile.php/\"]');
        if (link && link.getAttribute('href')) {
            return link.getAttribute('href');
        }
    }
    return layer.getAttribute('data-preview-current-url') || '';
}

function localCourseBannerBuilderSyncContextPreviewVisibility(scope) {
    var layerScope = localCourseBannerBuilderGetLayerScope(scope);
    if (!layerScope) {
        return;
    }
    Array.prototype.slice.call(layerScope.querySelectorAll('[data-layer-banner-preview=\"1\"]')).forEach(function(preview) {
        var toggle = preview.parentElement ? preview.parentElement.querySelector('[data-preview-context-toggle=\"1\"]') : null;
        var showContext = !toggle || toggle.checked;
        Array.prototype.slice.call(preview.querySelectorAll('[data-preview-context-layer=\"1\"]')).forEach(function(layer) {
            layer.hidden = !showContext;
        });
    });
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
        var offsets = {
            top: String(localCourseBannerBuilderNormaliseNumericValue(preferStoredState ? (layer.getAttribute('data-preview-offset-top') || '0') : (offsetTopInput && offsetTopInput.value ? offsetTopInput.value : (layer.getAttribute('data-preview-offset-top') || '0')), 0)) + '%',
            right: String(localCourseBannerBuilderNormaliseNumericValue(preferStoredState ? (layer.getAttribute('data-preview-offset-right') || '0') : (offsetRightInput && offsetRightInput.value ? offsetRightInput.value : (layer.getAttribute('data-preview-offset-right') || '0')), 0)) + '%',
            bottom: String(localCourseBannerBuilderNormaliseNumericValue(preferStoredState ? (layer.getAttribute('data-preview-offset-bottom') || '0') : (offsetBottomInput && offsetBottomInput.value ? offsetBottomInput.value : (layer.getAttribute('data-preview-offset-bottom') || '0')), 0)) + '%',
            left: String(localCourseBannerBuilderNormaliseNumericValue(preferStoredState ? (layer.getAttribute('data-preview-offset-left') || '0') : (offsetLeftInput && offsetLeftInput.value ? offsetLeftInput.value : (layer.getAttribute('data-preview-offset-left') || '0')), 0)) + '%'
        };
        var customWidth = Math.max(0, Math.min(300, localCourseBannerBuilderNormaliseNumericValue(preferStoredState ? (layer.getAttribute('data-preview-custom-width') || '100') : (widthInput && widthInput.value ? widthInput.value : (layer.getAttribute('data-preview-custom-width') || '100')), 100)));
        var customHeight = Math.max(0, Math.min(300, localCourseBannerBuilderNormaliseNumericValue(preferStoredState ? (layer.getAttribute('data-preview-custom-height') || '100') : (heightInput && heightInput.value ? heightInput.value : (layer.getAttribute('data-preview-custom-height') || '100')), 100)));
        var keepAspect = preferStoredState ? (layer.getAttribute('data-preview-keep-aspect') === '1') :
            (keepAspectInput ? keepAspectInput.checked : (layer.getAttribute('data-preview-keep-aspect') === '1'));
        var naturalWidth = localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-width') || image.naturalWidth || '0', 0);
        var naturalHeight = localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-height') || image.naturalHeight || '0', 0);
        var aboveBorder = layer.getAttribute('data-preview-dynamic-image') === '1';
        var dynamicImage = false;
        var imageUrl = localCourseBannerBuilderGetPreviewImageUrl(form, layer);
        var sortOrder = Math.max(0, parseInt(sortOrderInput && sortOrderInput.value ? sortOrderInput.value : (layer.getAttribute('data-preview-sortorder') || '0'), 10) || 0);
        var storedZIndex = aboveBorder ? 2000 : (parseInt(layer.getAttribute('data-preview-zindex') || '0', 10) || 0);
        var effectiveZIndex = localCourseBannerBuilderGetPreviewZIndex(sortOrder, storedZIndex);
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
                    'width: ' + dynamicCustomBox.width + '%;',
                    'height: ' + dynamicCustomBox.height + '%;',
                    'z-index: ' + effectiveZIndex + ';'
                ];
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
                        localCourseBannerBuilderGetContainedPreviewBox(naturalWidth, naturalHeight) :
                        localCourseBannerBuilderGetOriginalPreviewBox(naturalWidth, naturalHeight);
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
                        offsets: offsets
                    })];
                }
            }
        } else if (fitMode === 'bannerfit') {
            layerStyles.push('inset: 0;');
            imageStyles.push('object-fit: fill;');
        } else if (fitMode === 'cover') {
            if (naturalWidth > 0 && naturalHeight > 0) {
                var containedBox = localCourseBannerBuilderGetContainedPreviewBox(naturalWidth, naturalHeight);
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
            layerStyles.push('width: ' + customBox.width + '%;', 'height: ' + customBox.height + '%;');
            localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
            imageStyles.push('object-fit: ' + (keepAspect ? 'contain' : 'fill') + ';', 'object-position: ' + objectPosition + ';');
        } else {
            var originalPreviewBox = localCourseBannerBuilderGetOriginalPreviewBox(naturalWidth, naturalHeight);
            layerStyles.push('width: ' + originalPreviewBox.width + '%;');
            localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
            imageStyles.push('height: auto;', 'object-fit: none;', 'object-position: ' + objectPosition + ';');
        }

        layer.style.cssText = layerStyles.join(' ');
        image.style.cssText = imageStyles.join(' ');
        if (!image.dataset.previewLoadBound) {
            image.addEventListener('load', function() {
                layer.setAttribute('data-preview-natural-width', String(image.naturalWidth || 0));
                layer.setAttribute('data-preview-natural-height', String(image.naturalHeight || 0));
                localCourseBannerBuilderSyncCurrentImagePreview(form);
            });
            image.dataset.previewLoadBound = '1';
        }
        if (imageUrl) {
            if (image.getAttribute('src') !== imageUrl) {
                image.setAttribute('src', imageUrl);
            }
            layer.hidden = false;
        } else {
            layer.hidden = true;
        }
    });
}

function localCourseBannerBuilderRefreshCurrentPreviewLayer(form) {
    if (!form) {
        return;
    }
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"]');
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
        var sortOrder = Math.max(0, parseInt(sortOrderInput && sortOrderInput.value ? sortOrderInput.value : (currentBorder.getAttribute('data-preview-sortorder') || '0'), 10) || 0);
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
        localCourseBannerBuilderApplyLayerFormPreviewState(form, settings[activeIndex]);
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
    var layer = root.querySelector('.local-course-banner-builder-source-preview-layer--selected') ||
        root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]');
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

function localCourseBannerBuilderPushSelectedSourcePreviewLayerBehind(scope) {
    var root = scope && scope.closest ? scope.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var layer = root.querySelector('.local-course-banner-builder-source-preview-layer--selected');
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
    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
}

function localCourseBannerBuilderPullSelectedSourcePreviewLayerForward(scope) {
    var root = scope && scope.closest ? scope.closest('[data-source-visual-editor=\"1\"]') : null;
    if (!root) {
        return;
    }
    var layer = root.querySelector('.local-course-banner-builder-source-preview-layer--selected');
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
    var layer = root.querySelector('.local-course-banner-builder-source-preview-layer--selected') ||
        root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]');
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
    var layer = root.querySelector('.local-course-banner-builder-source-preview-layer--selected') ||
        root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]');
    var enabled = !!(layer && layer.getAttribute('data-preview-keep-aspect') === '1');
    button.setAttribute('data-keep-aspect-enabled', enabled ? '1' : '0');
    localCourseBannerBuilderSetActionButtonContent(
        button,
        enabled ? (button.getAttribute('data-on-icon') || 'fa-expand') : (button.getAttribute('data-off-icon') || 'fa-link'),
        enabled ? (button.getAttribute('data-on-label') || 'Allow stretch') : (button.getAttribute('data-off-label') || 'Keep proportions')
    );
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
    var hasLayer = !!(layer && layer.getAttribute('data-source-preview-layer-id'));
    button.hidden = !hasLayer;
    if (hasLayer) {
        button.setAttribute('data-layer-id', layer.getAttribute('data-source-preview-layer-id') || '');
    } else {
        button.removeAttribute('data-layer-id');
    }
}

function localCourseBannerBuilderDeleteSelectedPreviewLayer(button) {
    var root = button ? button.closest('[data-source-visual-editor=\"1\"]') : null;
    var layer = root ? root.querySelector('.local-course-banner-builder-source-preview-layer--selected') : null;
    var layerId = layer ? (layer.getAttribute('data-source-preview-layer-id') || '') : '';
    if (!root || !layerId) {
        return;
    }
    var message = button.getAttribute('data-confirm-message') || 'Are you sure?';
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
            throw new Error('Unable to delete selected layer');
        }
        return response.json();
    }).then(function(data) {
        if (!data || !data.success || typeof data.html !== 'string') {
            throw new Error('Invalid delete-selected-layer response');
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
    }).catch(function(error) {
        window.console.error(error);
        window.alert(error.message || 'Unable to delete selected layer');
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
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetRightInput = form.querySelector('#id_offsetrightpercent');
    var offsetBottomInput = form.querySelector('#id_offsetbottompercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"]');
    var image = currentLayer ? currentLayer.querySelector('[data-preview-image-tag=\"1\"]') : null;

    return {
        fitmodeoverride: fitOverride ? (fitOverride.value || '') : '',
        positionanchor: anchorInput ? (anchorInput.value || 'center') : 'center',
        customwidthpercent: localCourseBannerBuilderNormaliseNumericValue(widthInput ? widthInput.value : '100', 100),
        customheightpercent: localCourseBannerBuilderNormaliseNumericValue(heightInput ? heightInput.value : '100', 100),
        customsizekeepaspect: !!(keepAspectInput && keepAspectInput.checked),
        dynamicimagesizeenabled: !!(dynamicInput && dynamicInput.checked),
        offsettoppercent: localCourseBannerBuilderNormaliseNumericValue(offsetTopInput ? offsetTopInput.value : '0', 0),
        offsetrightpercent: localCourseBannerBuilderNormaliseNumericValue(offsetRightInput ? offsetRightInput.value : '0', 0),
        offsetbottompercent: localCourseBannerBuilderNormaliseNumericValue(offsetBottomInput ? offsetBottomInput.value : '0', 0),
        offsetleftpercent: localCourseBannerBuilderNormaliseNumericValue(offsetLeftInput ? offsetLeftInput.value : '0', 0),
        imagewidth: currentLayer ? localCourseBannerBuilderNormaliseNumericValue(currentLayer.getAttribute('data-preview-natural-width') || (image ? image.naturalWidth : '0'), 0) : 0,
        imageheight: currentLayer ? localCourseBannerBuilderNormaliseNumericValue(currentLayer.getAttribute('data-preview-natural-height') || (image ? image.naturalHeight : '0'), 0) : 0,
        url: currentLayer ? (currentLayer.getAttribute('data-preview-current-url') || (image ? image.getAttribute('src') : '')) : ''
    };
}

function localCourseBannerBuilderSyncCurrentLayerDataFromForm(form) {
    if (!form) {
        return;
    }
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"]');
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
    currentLayer.setAttribute('data-preview-offset-top', String(state.offsettoppercent ?? 0));
    currentLayer.setAttribute('data-preview-offset-right', String(state.offsetrightpercent ?? 0));
    currentLayer.setAttribute('data-preview-offset-bottom', String(state.offsetbottompercent ?? 0));
    currentLayer.setAttribute('data-preview-offset-left', String(state.offsetleftpercent ?? 0));
    currentLayer.setAttribute('data-preview-natural-width', String(state.imagewidth ?? 0));
    currentLayer.setAttribute('data-preview-natural-height', String(state.imageheight ?? 0));
    currentLayer.setAttribute('data-preview-current-url', state.url || '');
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
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetRightInput = form.querySelector('#id_offsetrightpercent');
    var offsetBottomInput = form.querySelector('#id_offsetbottompercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"]');
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
        currentLayer.setAttribute('data-preview-offset-top', String(state.offsettoppercent ?? 0));
        currentLayer.setAttribute('data-preview-offset-right', String(state.offsetrightpercent ?? 0));
        currentLayer.setAttribute('data-preview-offset-bottom', String(state.offsetbottompercent ?? 0));
        currentLayer.setAttribute('data-preview-offset-left', String(state.offsetleftpercent ?? 0));
        currentLayer.setAttribute('data-preview-natural-width', String(state.imagewidth ?? 0));
        currentLayer.setAttribute('data-preview-natural-height', String(state.imageheight ?? 0));
        currentLayer.setAttribute('data-preview-current-url', state.url || '');
    }
    if (image && state.url) {
        image.setAttribute('src', state.url);
    }

    localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
    localCourseBannerBuilderSyncCustomSizeFields(form);
    localCourseBannerBuilderSyncOffsetFields(form);
    localCourseBannerBuilderBindPercentSliders(form);
    form.dataset.previewUserChanged = '0';
    localCourseBannerBuilderSyncLayerBannerPreview(form);
}

function localCourseBannerBuilderGetDraftPreviewFiles(form) {
    var filemanager = form ? form.querySelector('#fitem_id_bannerimage_filemanager') : null;
    if (!filemanager) {
        return [];
    }
    return Array.prototype.slice.call(filemanager.querySelectorAll('.fp-file:not(.fp-folder)')).map(function(item, index) {
        var link = item.querySelector('a[href*=\"/draftfile.php/\"], a[href*=\"/pluginfile.php/\"]');
        var image = item.querySelector('img[src*=\"/draftfile.php/\"], img[src*=\"/pluginfile.php/\"]');
        var nameNode = item.querySelector('.fp-filename, .fp-pathbar, .fp-thumbnail .fp-filename');
        var url = link && link.getAttribute('href') ? link.getAttribute('href') : (image ? (image.getAttribute('src') || '') : '');
        return {
            index: index,
            url: url,
            name: nameNode ? nameNode.textContent.trim() : ('Layer ' + (index + 1))
        };
    }).filter(function(file) {
        return !!file.url && file.url.indexOf('/theme/image.php') === -1;
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

function localCourseBannerBuilderRenderDraftUploadPreview(form) {
    if (!form) {
        return;
    }
    var files = localCourseBannerBuilderGetDraftPreviewFiles(form);
    var previewRoot = form.querySelector('[data-layer-banner-preview=\"1\"]');
    var frame = previewRoot ? previewRoot.querySelector('[data-banner-preview-frame=\"1\"]') : null;
    var currentLayer = form.querySelector('[data-preview-current-image=\"1\"]');
    if (!previewRoot || !frame || !currentLayer) {
        return;
    }
    var host = previewRoot.querySelector('[data-preview-draft-layer-host=\"1\"]');
    if (!host) {
        host = document.createElement('div');
        host.setAttribute('data-preview-draft-layer-host', '1');
        frame.appendChild(host);
    }
    host.innerHTML = '';

    if (!files.length) {
        currentLayer.hidden = true;
        currentLayer.removeAttribute('data-preview-current-url');
        var currentImage = currentLayer.querySelector('[data-preview-image-tag=\"1\"]');
        if (currentImage) {
            currentImage.removeAttribute('src');
        }
        form.dataset.activeDraftIndex = '';
        localCourseBannerBuilderSetDraftPreviewSettings(form, {});
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
            settings[file.index] = localCourseBannerBuilderReadLayerFormPreviewState(form) || {};
            settings[file.index].fitmodeoverride = 'cover';
            settings[file.index].positionanchor = 'center';
            settings[file.index].customwidthpercent = 100;
            settings[file.index].customheightpercent = 100;
            settings[file.index].customsizekeepaspect = true;
            settings[file.index].offsettoppercent = 0;
            settings[file.index].offsetrightpercent = 0;
            settings[file.index].offsetbottompercent = 0;
            settings[file.index].offsetleftpercent = 0;
            settings[file.index].url = file.url;
        }
        settings[file.index].url = file.url;
    });

    files.forEach(function(file) {
        if (String(file.index) === activeIndex) {
            return;
        }
        var layerState = settings[file.index] || {};
        var layer = document.createElement('div');
        layer.className = 'local-course-banner-builder-preview-image-layer local-course-banner-builder-preview-image-layer--context local-course-banner-builder-preview-draft-layer';
        layer.setAttribute('data-preview-draft-layer', '1');
        layer.setAttribute('data-draft-index', String(file.index));
        layer.setAttribute('data-preview-fitmode', layerState.fitmodeoverride || '');
        layer.setAttribute('data-preview-anchor', layerState.positionanchor || 'center');
        layer.setAttribute('data-preview-custom-width', String(layerState.customwidthpercent ?? 100));
        layer.setAttribute('data-preview-custom-height', String(layerState.customheightpercent ?? 100));
        layer.setAttribute('data-preview-keep-aspect', layerState.customsizekeepaspect ? '1' : '0');
        layer.setAttribute('data-preview-dynamic-image', layerState.dynamicimagesizeenabled ? '1' : '0');
        layer.setAttribute('data-preview-offset-top', String(layerState.offsettoppercent ?? 0));
        layer.setAttribute('data-preview-offset-right', String(layerState.offsetrightpercent ?? 0));
        layer.setAttribute('data-preview-offset-bottom', String(layerState.offsetbottompercent ?? 0));
        layer.setAttribute('data-preview-offset-left', String(layerState.offsetleftpercent ?? 0));
        layer.setAttribute('data-preview-natural-width', String(layerState.imagewidth ?? 0));
        layer.setAttribute('data-preview-natural-height', String(layerState.imageheight ?? 0));
        layer.setAttribute('data-preview-current-url', file.url);
        layer.innerHTML = '<img src=\"' + file.url + '\" alt=\"\" class=\"local-course-banner-builder-preview-image\" data-preview-image-tag=\"1\" draggable=\"false\">';
        host.appendChild(layer);
        localCourseBannerBuilderSyncStandalonePreviewLayer(previewRoot, layer);
    });

    localCourseBannerBuilderSetDraftPreviewSettings(form, settings);
    localCourseBannerBuilderApplyLayerFormPreviewState(form, settings[activeIndex]);
    currentLayer.hidden = false;
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
}

function localCourseBannerBuilderSaveActiveDraftPreviewState(form) {
    if (!form || !form.dataset.activeDraftIndex) {
        return;
    }
    var settings = localCourseBannerBuilderGetDraftPreviewSettings(form);
    var index = String(form.dataset.activeDraftIndex);
    var state = localCourseBannerBuilderReadLayerFormPreviewState(form);
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
    form.dataset.activeDraftIndex = String(index);
    var settings = localCourseBannerBuilderGetDraftPreviewSettings(form);
    if (settings[index]) {
        localCourseBannerBuilderApplyLayerFormPreviewState(form, settings[index]);
    }
    localCourseBannerBuilderRenderDraftUploadPreview(form);
}

function localCourseBannerBuilderSyncDraftUploadPreview(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form || String(form.querySelector('#id_elementid') ? form.querySelector('#id_elementid').value || '0' : '0') !== '0') {
        return;
    }
    if (!form.querySelector('#id_multilayerdraftsettings')) {
        return;
    }
    if (form.dataset.syncingDraftPreview === '1') {
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
    var customWidth = Math.max(0, Math.min(300, localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-width') || '100', 100)));
    var customHeight = Math.max(0, Math.min(300, localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-height') || '100', 100)));
    var keepAspect = layer.getAttribute('data-preview-keep-aspect') === '1';
    var dynamicImage = false;
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
            layerStyles.push('width: ' + dynamicCustomBox.width + '%;', 'height: ' + dynamicCustomBox.height + '%;');
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
                offsets: offsets
            })];
            layerStyles = ['position: absolute;', 'inset: 0;', 'overflow: hidden;', 'z-index: ' + (parseInt(layer.getAttribute('data-preview-zindex') || '1', 10) || 1) + ';'];
        }
    } else if (fitMode === 'bannerfit') {
        layerStyles.push('inset: 0;');
        imageStyles.push('object-fit: fill;');
    } else if (fitMode === 'cover') {
        var containedBox = localCourseBannerBuilderGetContainedPreviewBox(naturalWidth, naturalHeight);
        layerStyles.push('width: ' + containedBox.width + '%;', 'height: ' + containedBox.height + '%;');
        localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
        imageStyles.push('object-fit: fill;', 'object-position: ' + objectPosition + ';');
    } else if (fitMode === 'custom') {
        var customBox = localCourseBannerBuilderGetCustomPreviewBox(customWidth, customHeight, naturalWidth, naturalHeight, keepAspect);
        layerStyles.push('width: ' + customBox.width + '%;', 'height: ' + customBox.height + '%;');
        localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
        imageStyles.push('object-fit: ' + (keepAspect ? 'contain' : 'fill') + ';', 'object-position: ' + objectPosition + ';');
    } else {
        var originalBox = localCourseBannerBuilderGetOriginalPreviewBox(naturalWidth, naturalHeight);
        layerStyles.push('width: ' + originalBox.width + '%;');
        localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
        imageStyles.push('height: auto;', 'object-fit: none;', 'object-position: ' + objectPosition + ';');
    }

    layer.style.cssText = layerStyles.join(' ');
    image.style.cssText = imageStyles.join(' ');
    if (!image.dataset.previewLoadBound) {
        image.addEventListener('load', function() {
            layer.setAttribute('data-preview-natural-width', String(image.naturalWidth || 0));
            layer.setAttribute('data-preview-natural-height', String(image.naturalHeight || 0));
            localCourseBannerBuilderSyncStandalonePreviewLayer(previewRoot, layer);
        });
        image.dataset.previewLoadBound = '1';
    }
}

function localCourseBannerBuilderEnsurePreviewCustomMode(form, layer, frame) {
    if (!form) {
        return false;
    }
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    var widthInput = form.querySelector('#id_customwidthpercent');
    var heightInput = form.querySelector('#id_customheightpercent');
    var offsetTopInput = form.querySelector('#id_offsettoppercent');
    var offsetLeftInput = form.querySelector('#id_offsetleftpercent');
    if (!fitOverride || !anchorInput || !widthInput || !heightInput || !offsetTopInput || !offsetLeftInput) {
        return false;
    }

    var widthValue = localCourseBannerBuilderNormaliseNumericValue(widthInput.value || '100', 100);
    var heightValue = localCourseBannerBuilderNormaliseNumericValue(heightInput.value || '100', 100);
    var offsetLeftValue = localCourseBannerBuilderNormaliseNumericValue(offsetLeftInput.value || '0', 0);
    var offsetTopValue = localCourseBannerBuilderNormaliseNumericValue(offsetTopInput.value || '0', 0);
    if (layer && frame) {
        var frameRect = frame.getBoundingClientRect();
        var layerRect = layer.getBoundingClientRect();
        if (frameRect.width > 0 && frameRect.height > 0 && layerRect.width > 0 && layerRect.height > 0) {
            widthValue = localCourseBannerBuilderRoundPreviewPercent((layerRect.width / frameRect.width) * 100);
            heightValue = localCourseBannerBuilderRoundPreviewPercent((layerRect.height / frameRect.height) * 100);
            offsetLeftValue = localCourseBannerBuilderRoundPreviewPercent(((layerRect.left - frameRect.left) / frameRect.width) * 100);
            offsetTopValue = localCourseBannerBuilderRoundPreviewPercent(((layerRect.top - frameRect.top) / frameRect.height) * 100);
        }
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
    widthPercent = Math.max(1, Math.min(300, widthPercent));
    heightPercent = Math.max(1, Math.min(300, heightPercent));
    return {
        width: widthPercent,
        height: heightPercent
    };
}

function localCourseBannerBuilderApplyPreviewDrag(state, event) {
    var deltaX = event.clientX - state.startX;
    var deltaY = event.clientY - state.startY;
    var leftPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startLeftPx + deltaX) / state.frameWidth) * 100);
    var topPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startTopPx + deltaY) / state.frameHeight) * 100);
    leftPercent = Math.max(-300, Math.min(300, leftPercent));
    topPercent = Math.max(-300, Math.min(300, topPercent));
    localCourseBannerBuilderSetPreviewFieldValue(state.offsetLeftInput, leftPercent);
    localCourseBannerBuilderSetPreviewFieldValue(state.offsetTopInput, topPercent);
}

function localCourseBannerBuilderApplyPreviewResize(state, event) {
    var deltaX = event.clientX - state.startX;
    var deltaY = event.clientY - state.startY;
    var widthPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startWidthPx + deltaX) / state.frameWidth) * 100);
    var heightPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startHeightPx + deltaY) / state.frameHeight) * 100);

    if (state.mode === 'resize-edge') {
        switch (state.edge) {
            case 'left':
                widthPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startWidthPx - deltaX) / state.frameWidth) * 100);
                var nextLeft = localCourseBannerBuilderRoundPreviewPercent(((state.startLeftPx + deltaX) / state.frameWidth) * 100);
                nextLeft = Math.max(-widthPercent, Math.min(state.startLeftPercent + state.startWidthPercent - 1, nextLeft));
                widthPercent = Math.max(1, state.startLeftPercent + state.startWidthPercent - nextLeft);
                localCourseBannerBuilderSetPreviewFieldValue(state.offsetLeftInput, nextLeft);
                break;
            case 'right':
                widthPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startWidthPx + deltaX) / state.frameWidth) * 100);
                break;
            case 'top':
                heightPercent = localCourseBannerBuilderRoundPreviewPercent(((state.startHeightPx - deltaY) / state.frameHeight) * 100);
                var nextTop = localCourseBannerBuilderRoundPreviewPercent(((state.startTopPx + deltaY) / state.frameHeight) * 100);
                nextTop = Math.max(-heightPercent, Math.min(state.startTopPercent + state.startHeightPercent - 1, nextTop));
                heightPercent = Math.max(1, state.startTopPercent + state.startHeightPercent - nextTop);
                localCourseBannerBuilderSetPreviewFieldValue(state.offsetTopInput, nextTop);
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

    localCourseBannerBuilderSetPreviewFieldValue(state.widthInput, widthPercent);
    localCourseBannerBuilderSetPreviewFieldValue(state.heightInput, heightPercent);
}

function localCourseBannerBuilderStopPreviewInteraction() {
    if (localCourseBannerBuilderPreviewInteraction && localCourseBannerBuilderPreviewInteraction.form) {
        var currentLayer = localCourseBannerBuilderPreviewInteraction.form.querySelector('[data-preview-current-layer=\"1\"]');
        if (currentLayer) {
            currentLayer.removeAttribute('data-preview-active-edge');
        }
    }
    localCourseBannerBuilderPreviewInteraction = null;
    localCourseBannerBuilderPendingPreviewInteraction = null;
}

function localCourseBannerBuilderHandlePreviewPointerMove(event) {
    if (!localCourseBannerBuilderPreviewInteraction && localCourseBannerBuilderPendingPreviewInteraction) {
        var pending = localCourseBannerBuilderPendingPreviewInteraction;
        var deltaX = event.clientX - pending.startX;
        var deltaY = event.clientY - pending.startY;
        if (Math.abs(deltaX) >= 3 || Math.abs(deltaY) >= 3) {
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
    if (!localCourseBannerBuilderEnsurePreviewCustomMode(form, layer, frame)) {
        return;
    }

    window.setTimeout(function() {
        var refreshedFrame = layer.closest('[data-banner-preview-frame=\"1\"]');
        var frameRect = refreshedFrame ? refreshedFrame.getBoundingClientRect() : null;
        var layerRect = layer.getBoundingClientRect();
        if (!frameRect || !frameRect.width || !frameRect.height || layer.hidden) {
            return;
        }
        localCourseBannerBuilderPreviewInteraction = {
            mode: mode,
            edge: layer.getAttribute('data-preview-active-edge') || '',
            form: form,
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
    }, 0);
}

function localCourseBannerBuilderBindLayerPreviewEvents(scope) {
    var form = localCourseBannerBuilderGetLayerScope(scope);
    if (!form) {
        return;
    }
    if (!form.dataset.previewEventsBound) {
        var syncPreview = function(event) {
            var target = event && event.target ? event.target : null;
            if (target && target.closest && target.closest('[data-preview-context-toggle=\"1\"]')) {
                localCourseBannerBuilderSyncCurrentLayerDataFromForm(form);
                localCourseBannerBuilderSyncLayerBannerPreview(form);
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
            var resizeHandle = event.target.closest('[data-preview-resize-handle=\"1\"]');
            var currentLayer = event.target.closest('[data-preview-current-layer=\"1\"]');
            if (!currentLayer || currentLayer.hidden) {
                return;
            }
            if (resizeHandle) {
                event.preventDefault();
                currentLayer.setAttribute('data-preview-active-edge', resizeHandle.getAttribute('data-preview-resize-edge') || '');
                localCourseBannerBuilderStartPreviewInteraction(
                    event,
                    resizeHandle.getAttribute('data-preview-resize-mode') === 'edge' ? 'resize-edge' : 'resize',
                    currentLayer
                );
                return;
            }
            if (event.target.closest('[data-preview-image-tag=\"1\"]') || event.target === currentLayer) {
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
            localCourseBannerBuilderSyncLayerBannerPreview(form);
        });
        observer.observe(filemanager, {childList: true, subtree: true, attributes: true});
        filemanager.dataset.previewObserverBound = '1';
    }

    localCourseBannerBuilderSyncLayerBannerPreview(form);
}

var localCourseBannerBuilderSourcePreviewInteraction = null;

function localCourseBannerBuilderGetSourceVisualEditorRoots(scope) {
    var root = scope || document;
    if (root.nodeType === 1 && root.matches && root.matches('[data-source-visual-editor=\"1\"]')) {
        return [root];
    }
    return Array.prototype.slice.call(root.querySelectorAll ? root.querySelectorAll('[data-source-visual-editor=\"1\"]') : []);
}

function localCourseBannerBuilderGetSourcePreviewLayerState(layer) {
    if (!layer) {
        return null;
    }
    return {
        id: String(layer.getAttribute('data-source-preview-layer-id') || ''),
        fitmodeoverride: layer.getAttribute('data-preview-fitmode') || 'custom',
        positionanchor: layer.getAttribute('data-preview-anchor') || 'center',
        customwidthpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-width') || '100', 100),
        customheightpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-custom-height') || '100', 100),
        customsizekeepaspect: layer.getAttribute('data-preview-keep-aspect') === '1',
        dynamicimagesizeenabled: layer.getAttribute('data-preview-dynamic-image') === '1',
        offsettoppercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-top') || '0', 0),
        offsetrightpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-right') || '0', 0),
        offsetbottompercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-bottom') || '0', 0),
        offsetleftpercent: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-offset-left') || '0', 0),
        imagewidth: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-width') || '0', 0),
        imageheight: localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-height') || '0', 0),
        url: layer.getAttribute('data-preview-current-url') || '',
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
    var alphaIndex = ((pixelY * alphaData.width) + pixelX) * 4 + 3;
    return (alphaData.data[alphaIndex] || 0) > 10;
}

function localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint(root, clientX, clientY) {
    if (!root || typeof document.elementsFromPoint !== 'function') {
        return root && root.querySelector ? root.querySelector('.local-course-banner-builder-source-preview-layer--selected') : null;
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
    for (var i = 0; i < elements.length; i++) {
        var layer = elements[i].closest ? elements[i].closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null;
        if (layer && root.contains(layer) && localCourseBannerBuilderIsSourcePreviewLayerOpaqueAtPoint(layer, clientX, clientY)) {
            return layer;
        }
    }

    var allLayers = Array.prototype.slice.call(root.querySelectorAll('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]'));
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

    state.fitmodeoverride = 'custom';
    state.positionanchor = 'top-left';
    state.offsetrightpercent = 0;
    state.offsetbottompercent = 0;
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

function localCourseBannerBuilderSetSourcePreviewLayerState(layer, state) {
    if (!layer || !state) {
        return;
    }
    layer.setAttribute('data-preview-fitmode', state.fitmodeoverride || 'custom');
    layer.setAttribute('data-preview-anchor', state.positionanchor || 'center');
    layer.setAttribute('data-preview-custom-width', String(state.customwidthpercent ?? 100));
    layer.setAttribute('data-preview-custom-height', String(state.customheightpercent ?? 100));
    layer.setAttribute('data-preview-keep-aspect', state.customsizekeepaspect ? '1' : '0');
    layer.setAttribute('data-preview-dynamic-image', state.dynamicimagesizeenabled ? '1' : '0');
    layer.setAttribute('data-preview-offset-top', String(state.offsettoppercent ?? 0));
    layer.setAttribute('data-preview-offset-right', String(state.offsetrightpercent ?? 0));
    layer.setAttribute('data-preview-offset-bottom', String(state.offsetbottompercent ?? 0));
    layer.setAttribute('data-preview-offset-left', String(state.offsetleftpercent ?? 0));
    layer.setAttribute('data-preview-natural-width', String(state.imagewidth ?? 0));
    layer.setAttribute('data-preview-natural-height', String(state.imageheight ?? 0));
    layer.setAttribute('data-preview-current-url', state.url || '');
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
            label: 'Custom size',
            value: localCourseBannerBuilderRoundPreviewPercent(state.customwidthpercent) + '% x ' +
                localCourseBannerBuilderRoundPreviewPercent(state.customheightpercent) + '%'
        },
        {
            label: 'Spacing',
            value: 'Top ' + localCourseBannerBuilderRoundPreviewPercent(state.offsettoppercent) + '%, Left ' +
                localCourseBannerBuilderRoundPreviewPercent(state.offsetleftpercent) + '%'
        }
    ];
    items.push({
        label: 'Keep proportions',
        value: state.customsizekeepaspect ? 'Yes' : 'No'
    });
    if (state.dynamicimagesizeenabled) {
        items.push({label: 'Image above border', value: 'Yes'});
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
}

function localCourseBannerBuilderSelectSourcePreviewLayer(root, layer) {
    if (!root || !layer || layer.getAttribute('data-source-preview-editable') !== '1') {
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
    localCourseBannerBuilderSyncSourcePreviewDeleteButton(root);
}

function localCourseBannerBuilderInitSourceVisualEditor(scope) {
    localCourseBannerBuilderGetSourceVisualEditorRoots(scope).forEach(function(root) {
        if (!root.dataset.sourcePreviewBound) {
            root.addEventListener('click', function(event) {
                var layer = localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint(root, event.clientX, event.clientY) ||
                    (event.target.closest ? event.target.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null);
                if (layer && root.contains(layer)) {
                    localCourseBannerBuilderSelectSourcePreviewLayer(root, layer);
                }
            });
            root.addEventListener('dblclick', function(event) {
                var layer = localCourseBannerBuilderGetTopSourcePreviewLayerAtPoint(root, event.clientX, event.clientY) ||
                    (event.target.closest ? event.target.closest('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]') : null);
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
        var selected = root.querySelector('.local-course-banner-builder-source-preview-layer--selected') ||
            root.querySelector('[data-source-preview-layer=\"1\"][data-source-preview-editable=\"1\"]');
        if (selected) {
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
                borderVisible ? (borderToggleButton.getAttribute('data-hide-label') || 'Hide border') : (borderToggleButton.getAttribute('data-show-label') || 'Show border')
            );
        }
        localCourseBannerBuilderSyncSourcePreviewTopLayerButton(root);
        localCourseBannerBuilderSyncSourcePreviewKeepAspectButton(root);
        localCourseBannerBuilderSyncSourcePreviewOrder(root);
        localCourseBannerBuilderSyncSourcePreviewPayload(root);
    });
}

function localCourseBannerBuilderStartSourcePreviewInteraction(event, mode, layer) {
    if (!layer || event.button !== 0) {
        return;
    }
    var root = layer.closest('[data-source-visual-editor=\"1\"]');
    var frame = root ? root.querySelector('[data-source-preview-frame=\"1\"]') : null;
    var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
    if (!root || !frame || !image) {
        return;
    }
    if (!localCourseBannerBuilderEnsureSourcePreviewCustomMode(root, layer, frame)) {
        return;
    }
    var frameRect = frame.getBoundingClientRect();
    var layerRect = layer.getBoundingClientRect();
    if (!frameRect.width || !frameRect.height) {
        return;
    }
    localCourseBannerBuilderSourcePreviewInteraction = {
        mode: mode,
        edge: layer.getAttribute('data-preview-active-edge') || '',
        root: root,
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
        startState: localCourseBannerBuilderGetSourcePreviewLayerState(layer)
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
        nextState.offsetleftpercent = Math.max(-300, Math.min(300,
            localCourseBannerBuilderRoundPreviewPercent(((interaction.startLeftPx + deltaX) / interaction.frameWidth) * 100)
        ));
        nextState.offsettoppercent = Math.max(-300, Math.min(300,
            localCourseBannerBuilderRoundPreviewPercent(((interaction.startTopPx + deltaY) / interaction.frameHeight) * 100)
        ));
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

        widthPercent = Math.max(1, Math.min(300, widthPercent));
        heightPercent = Math.max(1, Math.min(300, heightPercent));
        if (state.customsizekeepaspect) {
            var aspectRatio = interaction.startWidthPx > 0 && interaction.startHeightPx > 0 ?
                (interaction.startWidthPx / interaction.startHeightPx) : 1;
            heightPercent = (widthPercent * interaction.frameWidth) / (Math.max(1, aspectRatio) * interaction.frameHeight);
            heightPercent = Math.max(1, Math.min(300, heightPercent));
        }
        nextState.customwidthpercent = widthPercent;
        nextState.customheightpercent = heightPercent;
    }

    localCourseBannerBuilderSetSourcePreviewLayerState(interaction.layer, nextState);
    localCourseBannerBuilderSyncSourcePreviewLayer(interaction.root, interaction.layer);
    localCourseBannerBuilderUpdateSourcePreviewRow(interaction.root, nextState);
    localCourseBannerBuilderSyncSourcePreviewPayload(interaction.root);
}

function localCourseBannerBuilderStopSourcePreviewInteraction() {
    if (localCourseBannerBuilderSourcePreviewInteraction && localCourseBannerBuilderSourcePreviewInteraction.layer) {
        localCourseBannerBuilderSourcePreviewInteraction.layer.removeAttribute('data-preview-active-edge');
    }
    localCourseBannerBuilderSourcePreviewInteraction = null;
}

document.addEventListener('pointermove', localCourseBannerBuilderHandlePreviewPointerMove);
document.addEventListener('pointerup', localCourseBannerBuilderStopPreviewInteraction);
document.addEventListener('pointercancel', localCourseBannerBuilderStopPreviewInteraction);
document.addEventListener('pointermove', localCourseBannerBuilderHandleSourcePreviewPointerMove);
document.addEventListener('pointerup', localCourseBannerBuilderStopSourcePreviewInteraction);
document.addEventListener('pointercancel', localCourseBannerBuilderStopSourcePreviewInteraction);

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
    var dashInput = layerForm ? layerForm.querySelector('[data-border-dash-length=\"1\"]') : null;
    if (!styleInput || !dashInput) {
        return;
    }
    var wrapper = dashInput.closest('.fitem, .form-group, .mb-3');
    var show = styleInput.value === 'dashed';
    dashInput.disabled = !show;
    if (wrapper) {
        wrapper.classList.toggle('local-course-banner-builder-option-disabled', !show);
        wrapper.setAttribute('aria-hidden', 'false');
    }
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
    var valid = borderToggle.checked || hasExistingImage || filemanager.querySelectorAll('.fp-file:not(.fp-folder)').length > 0;
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
            localCourseBannerBuilderSyncOffsetFields(layerForm);
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
        });
        borderToggle.dataset.layerBound = '1';
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

    Array.prototype.slice.call(layerForm.querySelectorAll('#id_bordercolor, #id_borderwidth, #id_borderopacity, #id_borderfade, #id_borderstyle, [data-border-dash-length=\"1\"], [data-border-inner-rounded=\"1\"][type=\"checkbox\"]')).forEach(function(input) {
        if (!input || input.dataset.layerBound) {
            return;
        }
        input.addEventListener('input', function() {
            localCourseBannerBuilderSyncBorderPreview(layerForm);
        });
        input.addEventListener('change', function() {
            localCourseBannerBuilderSyncDashedControls(layerForm);
            localCourseBannerBuilderSyncBorderPreview(layerForm);
        });
        input.dataset.layerBound = '1';
    });

    if (layerForm && !layerForm.dataset.layerBound) {
        layerForm.addEventListener('submit', function(event) {
            localCourseBannerBuilderSaveActiveDraftPreviewState(layerForm);
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
    input.dispatchEvent(new Event('change', {bubbles: true}));
}

function localCourseBannerBuilderSetActionButtonContent(button, iconClass, label) {
    if (!button) {
        return;
    }
    button.innerHTML = '<i class=\"fa ' + iconClass + ' me-2\" aria-hidden=\"true\"></i><span>' + label + '</span>';
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
}

function localCourseBannerBuilderEnhanceBinaryOptionButtons(form) {
    if (!form) {
        return;
    }
    var enabledInput = form.querySelector('#id_isenabled');
    var enabledRow = enabledInput ? enabledInput.closest('.fitem, .form-group, .mb-3, .row') : null;
    if (enabledRow && enabledRow.parentNode && enabledRow.parentNode.firstChild !== enabledRow) {
        enabledRow.parentNode.insertBefore(enabledRow, enabledRow.parentNode.firstChild);
    }
    [
        {selector: '#id_isenabled', icon: 'fa-toggle-on'},
        {selector: '#id_borderenabled', icon: 'fa-square-plus'}
    ].forEach(function(config) {
        var input = form.querySelector(config.selector);
        if (!input || input.type === 'hidden') {
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
        var host = row.querySelector('[data-toggle-button-host-for=\"#' + input.id + '\"]');
        if (!host) {
            host = document.createElement('div');
            host.className = 'local-course-banner-builder-toggle-button-host';
            host.setAttribute('data-toggle-button-host-for', '#' + input.id);
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button';
            button.setAttribute('data-toggle-button-for', '#' + input.id);
            var label = row.querySelector('label[for=\"' + input.id + '\"]');
            localCourseBannerBuilderSetActionButtonContent(button, config.icon, (label ? label.textContent.trim() : input.name));
            button.addEventListener('click', function() {
                localCourseBannerBuilderToggleCheckboxInput(input);
            });
            host.appendChild(button);
            var target = row.querySelector('.felement, .col-md-9');
            if (target) {
                target.insertBefore(host, target.firstChild);
            }
        }
        localCourseBannerBuilderSyncBinaryOptionButton(input);
    });
}

function localCourseBannerBuilderSyncModalPreviewActionButtons(form) {
    if (!form) {
        return;
    }
    var filemanager = form.querySelector('#fitem_id_bannerimage_filemanager');
    var draftFiles = localCourseBannerBuilderGetDraftPreviewFiles(form);
    var hasFiles = !!(draftFiles.length || (filemanager && filemanager.querySelectorAll('.fp-file:not(.fp-folder)').length));
    var hasExistingImageInput = form.querySelector('#id_hasexistingimage');
    var currentIsBorderLayerInput = form.querySelector('#id_currentisborderlayer');
    var elementIdInput = form.querySelector('#id_elementid');
    var borderToggle = form.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    var hasExistingImage = !!(hasExistingImageInput && parseInt(hasExistingImageInput.value || '0', 10) > 0);
    var isExistingBorderLayer = !!(currentIsBorderLayerInput && parseInt(currentIsBorderLayerInput.value || '0', 10) > 0);
    var hasExistingElement = !!(elementIdInput && parseInt(elementIdInput.value || '0', 10) > 0);
    var isEditImageLayer = hasExistingElement && !isExistingBorderLayer;
    var hasImage = hasFiles || hasExistingImage || isEditImageLayer;
    var isBorderOnly = !!(borderToggle && borderToggle.checked && !hasFiles && !hasExistingImage);
    var submitProxy = form.querySelector('[data-modal-preview-submit-proxy=\"1\"]');

    Array.prototype.slice.call(form.querySelectorAll('[data-preview-action-bound-input]')).forEach(function(button) {
        var selector = button.getAttribute('data-preview-action-bound-input');
        var input = selector ? form.querySelector(selector) : null;
        if (!input) {
            return;
        }
        var group = button.getAttribute('data-preview-action-group') || 'shared';
        var visible = false;
        if (group === 'image') {
            visible = hasImage && !isBorderOnly;
        } else if (group === 'border') {
            visible = isBorderOnly || isExistingBorderLayer;
        } else {
            visible = hasImage || isBorderOnly || isExistingBorderLayer;
        }
        button.hidden = !visible;
        var checked = !!input.checked;
        button.classList.toggle('btn-primary', checked);
        button.classList.toggle('btn-outline-secondary', !checked);
        if (button.hasAttribute('data-preview-action-label-on') && button.hasAttribute('data-preview-action-label-off')) {
            localCourseBannerBuilderSetActionButtonContent(
                button,
                checked ? (button.getAttribute('data-preview-action-icon-on') || 'fa-check') : (button.getAttribute('data-preview-action-icon-off') || 'fa-check'),
                checked ? button.getAttribute('data-preview-action-label-on') : button.getAttribute('data-preview-action-label-off')
            );
        }
    });
    Array.prototype.slice.call(form.querySelectorAll(
        '[data-modal-preview-action-list=\"1\"] [data-preview-action-group]:not([data-preview-action-bound-input])'
    )).forEach(function(button) {
        if (button.hasAttribute('data-modal-preview-submit-proxy')) {
            return;
        }
        var group = button.getAttribute('data-preview-action-group') || 'shared';
        if (group === 'image') {
            button.hidden = !(hasImage && !isBorderOnly);
        } else if (group === 'border') {
            button.hidden = !(isBorderOnly || isExistingBorderLayer);
        } else {
            button.hidden = !(hasImage || isBorderOnly || isExistingBorderLayer);
        }
    });
    if (submitProxy) {
        var canSave = hasExistingElement || hasImage || isBorderOnly;
        submitProxy.disabled = !canSave;
        submitProxy.classList.toggle('disabled', !canSave);
        submitProxy.setAttribute('aria-disabled', canSave ? 'false' : 'true');
    }
    var host = form.querySelector('[data-modal-preview-action-list=\"1\"]');
    if (host) {
        localCourseBannerBuilderLayoutModalPreviewActionButtons(host);
    }
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

function localCourseBannerBuilderEnhanceModalPreviewActions(form) {
    if (!form) {
        return;
    }
    var panel = form.querySelector('.local-course-banner-builder-banner-preview-panel');
    if (!panel) {
        return;
    }
    var helpText = panel.querySelector('.form-text.text-muted');
    var host = panel.querySelector('[data-modal-preview-action-list=\"1\"]');
    if (!host) {
        host = document.createElement('div');
        host.className = 'local-course-banner-builder-modal-preview-action-list';
        host.setAttribute('data-modal-preview-action-list', '1');
        if (helpText && helpText.parentNode === panel) {
            panel.insertBefore(host, helpText);
        } else {
            panel.appendChild(host);
        }
    }
    host.innerHTML = '';
    form.classList.add('local-course-banner-builder-has-preview-proxy');

    var recenterButton = panel.querySelector('[data-action=\"local-course-banner-builder-recenter-preview-image\"]');
    if (recenterButton) {
        recenterButton.classList.remove('btn-sm');
        recenterButton.classList.add('local-course-banner-builder-source-preview-button');
        recenterButton.setAttribute('data-preview-action-group', 'image');
        localCourseBannerBuilderSetActionButtonContent(
            recenterButton,
            'fa-crosshairs',
            recenterButton.textContent.trim()
        );
        host.appendChild(recenterButton);
    }

    var recenterAllButton = document.createElement('button');
    recenterAllButton.type = 'button';
    recenterAllButton.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button';
    recenterAllButton.setAttribute('data-preview-action-group', 'image');
    localCourseBannerBuilderSetActionButtonContent(recenterAllButton, 'fa-bullseye', 'Recenter all images');
    recenterAllButton.addEventListener('click', function() {
        localCourseBannerBuilderRecenterAllLayerPreviewImages(form);
    });
    host.appendChild(recenterAllButton);

    var contextToggle = panel.querySelector('[data-preview-context-toggle=\"1\"]');
    var contextToolbar = panel.querySelector('.local-course-banner-builder-banner-preview-toolbar');
    if (contextToolbar) {
        contextToolbar.style.display = 'none';
    }
    if (contextToggle) {
        var contextButton = document.createElement('button');
        contextButton.type = 'button';
        contextButton.className = 'btn btn-outline-secondary local-course-banner-builder-source-preview-button';
        contextButton.setAttribute('data-preview-action-bound-input', '#'+ contextToggle.id);
        contextButton.setAttribute('data-preview-action-label-on', 'Hide other layers');
        contextButton.setAttribute('data-preview-action-label-off', 'Show other layers');
        contextButton.setAttribute('data-preview-action-icon-on', 'fa-eye-slash');
        contextButton.setAttribute('data-preview-action-icon-off', 'fa-eye');
        contextButton.setAttribute('data-preview-action-group', 'shared');
        contextButton.addEventListener('click', function() {
            localCourseBannerBuilderToggleCheckboxInput(contextToggle);
        });
        host.appendChild(contextButton);
    }

    [
        {
            selector: '#id_dynamicimagesizeenabled',
            on: 'Image above border',
            off: 'Image below border',
            iconon: 'fa-level-up',
            iconoff: 'fa-level-down',
            group: 'image'
        },
        {
            selector: '#id_customsizekeepaspect',
            on: 'Keep original dimensions',
            off: 'Allow stretch',
            iconon: 'fa-link',
            iconoff: 'fa-expand',
            group: 'image'
        },
        {
            selector: '#id_borderinnerrounded',
            on: 'Round inner corners',
            off: 'Sharp inner corners',
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
            localCourseBannerBuilderToggleCheckboxInput(input);
        });
        host.appendChild(button);
    });

    var submitField = form.querySelector('.fitem_actionbuttons input[type=\"submit\"], .fitem_actionbuttons button[type=\"submit\"], .local-course-banner-builder-submit-actions input[type=\"submit\"], .local-course-banner-builder-submit-actions button[type=\"submit\"]');
    if (submitField) {
        Array.prototype.slice.call(form.querySelectorAll('.fitem_actionbuttons, #fgroup_id_buttonar, .local-course-banner-builder-submit-actions')).forEach(function(row) {
            row.hidden = true;
            row.setAttribute('aria-hidden', 'true');
            row.classList.add('local-course-banner-builder-modal-original-submit');
        });
        var submitLabel = submitField.value || submitField.textContent.trim() || 'Save layers';
        var submitButton = document.createElement('button');
        submitButton.type = 'submit';
        submitButton.className = 'btn btn-primary local-course-banner-builder-source-preview-button';
        submitButton.setAttribute('data-modal-preview-submit-proxy', '1');
        localCourseBannerBuilderSetActionButtonContent(submitButton, 'fa-save', submitLabel);
        host.appendChild(submitButton);
    }

    localCourseBannerBuilderLayoutModalPreviewActionButtons(host);
    localCourseBannerBuilderSyncModalPreviewActionButtons(form);
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
        allButton.textContent = 'All';
        allButton.addEventListener('click', function() {
            var allInput = form.querySelector('[data-border-side-all=\"1\"][type=\"checkbox\"]');
            localCourseBannerBuilderToggleCheckboxInput(allInput);
        });
        picker.appendChild(allButton);
        source.parentNode.insertBefore(picker, source.nextSibling);
    }
    localCourseBannerBuilderSyncBorderSidePicker(form);
}

function localCourseBannerBuilderInitPopovers(scope) {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.popover) {
        return;
    }
    var root = scope || document;
    var isNativeAdmin = !!document.querySelector('.local-course-banner-builder-admin--native');
    window.jQuery(root).find('[data-toggle=\"popover\"]').each(function() {
        var element = window.jQuery(this);
        try {
            element.popover('dispose');
        } catch (e) {
            // Ignore dispose errors on non-initialized nodes.
        }
        var config = {
            container: element.data('container') || 'body',
            placement: element.data('placement') || 'right',
            content: element.attr('data-content') || '',
            html: String(element.attr('data-html')) === 'true',
            trigger: element.attr('data-trigger') || 'focus',
            title: element.attr('data-original-title') || element.attr('title') || ''
        };
        var template = element.attr('data-template');
        if (typeof template === 'string' && template !== '') {
            config.template = template;
        } else if (isNativeAdmin) {
            config.template = '<div class=\"popover local-course-banner-builder-admin-popover\" role=\"tooltip\">' +
                '<div class=\"arrow\"></div><h3 class=\"popover-header\"></h3><div class=\"popover-body\"></div></div>';
        }
        try {
            element.popover(config);
        } catch (e) {
            window.console.error(e);
        }
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

function localCourseBannerBuilderHideModal(modal) {
    if (!modal) {
        return;
    }
    if (typeof window.jQuery !== 'undefined' && typeof window.jQuery(modal).modal === 'function') {
        window.jQuery(modal).modal('hide');
        return;
    }
    if (window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modal).hide();
        return;
    }
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
    Array.prototype.slice.call(document.querySelectorAll('.modal-backdrop.local-course-banner-builder-modal-backdrop')).forEach(function(backdrop) {
        backdrop.remove();
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
    form.dataset.activeDraftIndex = '';
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
    localCourseBannerBuilderSyncDashedControls(form);
    localCourseBannerBuilderSyncLayerInputModes(form);
    localCourseBannerBuilderSyncBorderPreview(form);
    localCourseBannerBuilderSyncDraftUploadPreview(form);
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
            localCourseBannerBuilderSyncDetailsCollapseIcons(modal);
        });
    } else {
        var form = modal.querySelector('form.mform');
        localCourseBannerBuilderSyncLayerInputModes(form);
        localCourseBannerBuilderSyncBorderPreview(form);
        localCourseBannerBuilderEnhanceBinaryOptionButtons(form);
        localCourseBannerBuilderEnhanceModalPreviewActions(form);
        localCourseBannerBuilderEnhanceBorderSidePicker(form);
        localCourseBannerBuilderSyncDetailsCollapseIcons(modal);
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
    window.requestAnimationFrame(function() {
        localCourseBannerBuilderRefreshCurrentPreviewLayer(form);
        window.requestAnimationFrame(function() {
            localCourseBannerBuilderRefreshCurrentPreviewLayer(form);
        });
    });
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
    localCourseBannerBuilderSyncStickyHeader();
}

function localCourseBannerBuilderDeleteAllLayers(button) {
    if (!button) {
        return;
    }
    var form = document.getElementById('local-course-banner-builder-delete-all-layers');
    if (!form) {
        return;
    }
    var message = button.getAttribute('data-confirm-message') || 'Are you sure?';
    if (!window.confirm(message)) {
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
            throw new Error('Unable to delete all layers');
        }
        return response.json();
    }).then(function(data) {
        if (!data || !data.success || typeof data.html !== 'string') {
            throw new Error('Invalid delete-all-layers response');
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
    }).catch(function(error) {
        window.console.error(error);
    });
}

function localCourseBannerBuilderEnhanceAccordions(root) {
    Array.prototype.slice.call((root || document).querySelectorAll('details.local-course-banner-builder-upload-accordion, details.local-course-banner-builder-advanced-accordion, details.local-course-banner-builder-section')).forEach(function(details) {
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
            throw new Error('Unable to load layer form');
        }
        return response.text();
    }).then(function(response) {
        var html = response;
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var fetchedmodal = localCourseBannerBuilderFindFetchedLayerModal(doc);
        if (!fetchedmodal) {
            throw new Error('Fetched modal not found');
        }

        if (fetchedmodal.id === 'local-course-banner-builder-add-layer-modal') {
            throw new Error('Unexpected create modal returned for layer edit');
            return;
        }

        var targetmodal = document.getElementById(fetchedmodal.id);
        if (!targetmodal) {
            throw new Error('Target modal not found');
        }

        var fetchedtitle = fetchedmodal.querySelector('.modal-title');
        var fetchedbody = fetchedmodal.querySelector('.modal-body');
        var targettitle = targetmodal.querySelector('.modal-title');
        var targetbody = targetmodal.querySelector('.modal-body');
        if (!fetchedbody || !targetbody) {
            throw new Error('Modal body not found');
        }

        if (fetchedtitle && targettitle) {
            targettitle.textContent = fetchedtitle.textContent;
        }
        targetbody.innerHTML = fetchedbody.innerHTML;

        var form = targetbody.querySelector('form.mform');
        if (form && fetchedmodal.id === 'local-course-banner-builder-edit-border-layer-modal') {
            form.setAttribute('data-edit-border-locked', '1');
            var borderToggle = form.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
            if (borderToggle) {
                borderToggle.checked = true;
                borderToggle.disabled = true;
                borderToggle.setAttribute('aria-disabled', 'true');
            }
            var sidesValue = form.querySelector('#id_bordersidesvalue');
            if (sidesValue) {
                form.dataset.borderSidesValue = sidesValue.value;
            }
        }

        localCourseBannerBuilderPrepareDynamicLayerModal(targetmodal);
        if (localCourseBannerBuilderShowModal(targetmodal) && typeof window.jQuery !== 'undefined') {
            window.jQuery(targetmodal).one('shown.bs.modal', function() {
                localCourseBannerBuilderPrepareDynamicLayerModal(targetmodal);
                var shownForm = targetmodal.querySelector('form.mform');
                window.setTimeout(function() {
                    localCourseBannerBuilderRefreshCurrentPreviewLayer(shownForm);
                }, 30);
                window.setTimeout(function() {
                    localCourseBannerBuilderRefreshCurrentPreviewLayer(shownForm);
                }, 120);
            });
        } else {
            localCourseBannerBuilderPrepareDynamicLayerModal(targetmodal);
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
        localCourseBannerBuilderEnhanceModalPreviewActions(createLayerForm);
        localCourseBannerBuilderEnhanceBorderSidePicker(createLayerForm);
    }
    localCourseBannerBuilderInitSourceVisualEditor(document);
    localCourseBannerBuilderSyncDetailsCollapseIcons();
    localCourseBannerBuilderAlignModalActionButtons(document);
    localCourseBannerBuilderEnhanceAccordions(document);
    localCourseBannerBuilderInitPopovers(document);
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

$selectedcategorylabel = '';
foreach ($categoryoptions as $option) {
    if ((int)$option['id'] === (int)$selectedcategoryid) {
        $selectedcategorylabel = $option['label'];
        break;
    }
}
if ($selectedcategorylabel !== '') {
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
} else if ($selectedsource) {
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
                    'data-trigger' => 'hover focus',
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
                    'data-trigger' => 'hover focus',
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
                        'data-trigger' => 'hover focus',
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
        echo html_writer::start_div('modal-header');
        echo html_writer::tag('h5', get_string('categorysettings', 'local_course_banner_builder'), [
            'class' => 'modal-title',
            'id' => 'local-course-banner-builder-source-settings-modal-title',
        ]);
        echo html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
            'type' => 'button',
            'class' => 'close',
            'data-dismiss' => 'modal',
            'data-bs-dismiss' => 'modal',
            'aria-label' => get_string('closebuttontitle'),
        ]);
        echo html_writer::end_div();
        echo html_writer::start_div('modal-body');
        echo html_writer::start_div('local-course-banner-builder-settings');
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => (new moodle_url('/local/course_banner_builder/admin_manage.php', $selectedsourceparams))->out(false),
            'class' => 'local-course-banner-builder-settings-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savecategorysettings', 'value' => 1]);

        if ($selectedsource->type === \local_course_banner_builder\manager::SOURCE_TYPE_CUSTOMFIELD) {
            echo html_writer::start_div('mb-3');
            echo html_writer::label(get_string('customfieldpriority', 'local_course_banner_builder'), 'id_customfieldpriority');
            echo html_writer::select(
                \local_course_banner_builder\manager::get_customfield_priority_options(),
                'customfieldpriority',
                $categorysettings->customfieldpriority ?? \local_course_banner_builder\manager::CUSTOMFIELD_PRIORITY_CATEGORY,
                false,
                ['id' => 'id_customfieldpriority', 'class' => 'custom-select mt-2']
            );
            echo html_writer::tag('div', get_string('customfieldpriority_help', 'local_course_banner_builder'), [
                'class' => 'form-text text-muted mt-2',
            ]);
            echo html_writer::end_div();
        }

        echo html_writer::start_div('mb-3');
        echo html_writer::label(get_string('compositionmode', 'local_course_banner_builder'), 'id_compositionmode');
        echo html_writer::select(
            \local_course_banner_builder\manager::get_composition_mode_options(),
            'compositionmode',
            $categorysettings->compositionmode ?? \local_course_banner_builder\manager::MODE_CUMULATIVE,
            false,
            ['id' => 'id_compositionmode', 'class' => 'custom-select mt-2']
        );
        echo html_writer::tag('div', get_string('compositionmode_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted mt-2']);
        echo html_writer::end_div();

        echo html_writer::start_div('mb-3');
        echo html_writer::label(get_string('fitmode', 'local_course_banner_builder'), 'id_fitmode');
        echo html_writer::select(
            \local_course_banner_builder\manager::get_editable_fit_mode_options(),
            'fitmode',
            $categorysettings->fitmode ?? \local_course_banner_builder\manager::FIT_MODE_ORIGINAL,
            false,
            ['id' => 'id_fitmode', 'class' => 'custom-select mt-2']
        );
        echo html_writer::tag('div', get_string('fitmode_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted mt-2']);
        echo html_writer::end_div();

        echo html_writer::start_div('mb-3');
        $sourceparentoptions = \local_course_banner_builder\manager::get_source_parent_options((string)$selectedsource->sourcekey);
        $selectedparentkey = (string)($categorysettings->sourceparentkey ?? '');
        if (!empty($categorysettings->sourceisroot)) {
            $selectedparentkey = '';
        }
        $selectedparentlabel = $sourceparentoptions[$selectedparentkey] ?? $sourceparentoptions[''] ?? '';
        echo html_writer::label(get_string('sourceparentkey', 'local_course_banner_builder'), 'id_sourceparentkey');
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sourceparentkey',
            'id' => 'id_sourceparentkey',
            'value' => $selectedparentkey,
        ]);
        echo html_writer::start_div('dropdown local-course-banner-builder-source-dropdown mt-2', [
            'data-source-dropdown' => 'sourceparent',
            'data-input' => '#id_sourceparentkey',
        ]);
        echo html_writer::tag('button', s($selectedparentlabel), [
            'type' => 'button',
            'id' => 'id_sourceparentkey_dropdown_button',
            'class' => 'btn btn-outline-primary dropdown-toggle local-course-banner-builder-source-dropdown-toggle w-100 text-left',
            'data-toggle' => 'dropdown',
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

        echo html_writer::start_div('local-course-banner-builder-submit-actions');
        echo html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-save me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('savecategorysettings', 'local_course_banner_builder')),
            [
            'type' => 'submit',
            'class' => 'btn btn-primary',
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
}

if ($selectedsource) {
    $selectedsourcecontext = \local_course_banner_builder\manager::export_selected_source($selectedsource);
    $selectedsourcecontext['sourcevisualeditorhtml'] = local_course_banner_builder_render_source_visual_editor($selectedsource);
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
    \local_course_banner_builder\manager::export_configured_categories()
);
echo html_writer::end_tag('details');

echo html_writer::end_div();
echo $OUTPUT->footer();
