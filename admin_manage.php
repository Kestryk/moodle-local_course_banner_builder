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
$updatelayerinline = optional_param('updatelayerinline', 0, PARAM_BOOL);
$updatelayersbulk = optional_param('updatelayersbulk', 0, PARAM_BOOL);
$deleteselectedlayers = optional_param('deleteselectedlayers', 0, PARAM_BOOL);

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
$PAGE->navbar->add(get_string('managebanners', 'local_course_banner_builder'), new moodle_url('/local/course_banner_builder/admin_manage.php'));

if ($selectedsource) {
    $PAGE->navbar->add($selectedsource->label, $url);
}

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

if ($updatesourcesettingfield && confirm_sesskey() && $selectedsource) {
    $fieldname = required_param('fieldname', PARAM_ALPHAEXT);
    $fieldvalue = required_param('fieldvalue', PARAM_ALPHAEXT);
    \local_course_banner_builder\manager::update_source_setting_field($selectedsource, $fieldname, $fieldvalue);
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
    $fitapplyscope = required_param('fitapplyscope', PARAM_ALPHA);
    $customfieldpriority = optional_param(
        'customfieldpriority',
        \local_course_banner_builder\manager::CUSTOMFIELD_PRIORITY_CATEGORY,
        PARAM_ALPHA
    );
    \local_course_banner_builder\manager::save_source_settings(
        $selectedsource,
        $compositionmode,
        $fitmode,
        $fitapplyscope,
        $customfieldpriority
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

    var toggle = e.target.closest('[data-action=\"local-course-banner-builder-toggle-selection\"]');
    if (!toggle) {
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

document.addEventListener('change', function(e) {
    if (e.target.closest('.local-course-banner-builder-layer-select')) {
        localCourseBannerBuilderSyncSelectionButton();
    }
    if (e.target.matches('[data-layer-position-anchor=\"1\"]')) {
        localCourseBannerBuilderSyncOffsetFields();
    }
});

document.addEventListener('keydown', function(e) {
    var select = e.target.closest('[data-inline-setting-select]');
    if (!select) {
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
        input.classList.add('theme-easyedu-range');
        var describedby = input.getAttribute('aria-describedby');
        var output = document.createElement('span');
        output.className = 'theme-easyedu-range-output';
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
            input.value = String(localCourseBannerBuilderNormaliseNumericValue(input.value, 0));
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
    var borderToggle = layerScope.querySelector('[data-border-toggle=\"1\"][type=\"checkbox\"]');
    var showCurrentBorder = !!(borderToggle && borderToggle.checked);
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
        styles.push('width: ' + options.customWidth + '%;');
        if (options.keepAspect) {
            styles.push('height: auto;', 'max-height: ' + options.customHeight + '%;');
        } else {
            styles.push('height: ' + options.customHeight + '%;');
        }
    } else {
        var originalWidthPercent = options.naturalWidth > 0 ? Math.max(0, (options.naturalWidth / 1600) * 100) : 100;
        styles.push('width: ' + originalWidthPercent + '%;', 'height: auto;', 'max-width: 100%;', 'max-height: 100%;');
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

function localCourseBannerBuilderGetPreviewImageUrl(form, layer) {
    if (!form || !layer) {
        return '';
    }
    var filemanager = form.querySelector('#fitem_id_bannerimage_filemanager');
    if (filemanager) {
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

    currentLayers.forEach(function(layer) {
        var previewRoot = layer.closest('[data-layer-banner-preview=\"1\"]');
        var image = layer.querySelector('[data-preview-image-tag=\"1\"]');
        if (!previewRoot || !image) {
            return;
        }

        var defaultFitMode = previewRoot.getAttribute('data-default-fitmode') || 'bannerfit';
        var storedFitMode = layer.getAttribute('data-preview-fitmode') || '';
        var fitMode = fitOverride && fitOverride.value ? fitOverride.value : (storedFitMode || defaultFitMode);
        var anchor = anchorInput && anchorInput.value ? anchorInput.value : (layer.getAttribute('data-preview-anchor') || 'center');
        var offsets = {
            top: String(localCourseBannerBuilderNormaliseNumericValue(offsetTopInput && offsetTopInput.value ? offsetTopInput.value : (layer.getAttribute('data-preview-offset-top') || '0'), 0)) + '%',
            right: String(localCourseBannerBuilderNormaliseNumericValue(offsetRightInput && offsetRightInput.value ? offsetRightInput.value : (layer.getAttribute('data-preview-offset-right') || '0'), 0)) + '%',
            bottom: String(localCourseBannerBuilderNormaliseNumericValue(offsetBottomInput && offsetBottomInput.value ? offsetBottomInput.value : (layer.getAttribute('data-preview-offset-bottom') || '0'), 0)) + '%',
            left: String(localCourseBannerBuilderNormaliseNumericValue(offsetLeftInput && offsetLeftInput.value ? offsetLeftInput.value : (layer.getAttribute('data-preview-offset-left') || '0'), 0)) + '%'
        };
        var customWidth = Math.max(0, Math.min(100, localCourseBannerBuilderNormaliseNumericValue(widthInput && widthInput.value ? widthInput.value : (layer.getAttribute('data-preview-custom-width') || '100'), 100)));
        var customHeight = Math.max(0, Math.min(100, localCourseBannerBuilderNormaliseNumericValue(heightInput && heightInput.value ? heightInput.value : (layer.getAttribute('data-preview-custom-height') || '100'), 100)));
        var keepAspect = keepAspectInput ? keepAspectInput.checked : (layer.getAttribute('data-preview-keep-aspect') === '1');
        var naturalWidth = localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-width') || image.naturalWidth || '0', 0);
        var naturalHeight = localCourseBannerBuilderNormaliseNumericValue(layer.getAttribute('data-preview-natural-height') || image.naturalHeight || '0', 0);
        var dynamicImage = layer.getAttribute('data-preview-dynamic-image') === '1';
        var imageUrl = localCourseBannerBuilderGetPreviewImageUrl(form, layer);
        var sortOrder = Math.max(0, parseInt(sortOrderInput && sortOrderInput.value ? sortOrderInput.value : (layer.getAttribute('data-preview-sortorder') || '0'), 10) || 0);
        var storedZIndex = parseInt(layer.getAttribute('data-preview-zindex') || '0', 10) || 0;
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
        } else if (fitMode === 'bannerfit') {
            layerStyles.push('inset: 0;');
            imageStyles.push('object-fit: fill;');
        } else if (fitMode === 'cover') {
            layerStyles.push('inset: 0;');
            imageStyles.push('object-fit: contain;', 'object-position: ' + objectPosition + ';');
        } else if (fitMode === 'custom') {
            layerStyles.push('width: ' + customWidth + '%;', 'height: ' + customHeight + '%;');
            localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
            imageStyles.push('object-fit: ' + (keepAspect ? 'contain' : 'fill') + ';', 'object-position: ' + objectPosition + ';');
        } else {
            var originalWidthPercent = naturalWidth > 0 ? Math.max(0, (naturalWidth / 1600) * 100) : 100;
            layerStyles.push('width: ' + originalWidthPercent + '%;', 'max-width: 100%;', 'max-height: 100%;');
            localCourseBannerBuilderAppendPreviewPositionStyles(layerStyles, anchor, offsets);
            imageStyles.push('height: auto;', 'max-width: 100%;', 'max-height: 100%;', 'object-fit: contain;', 'object-position: ' + objectPosition + ';');
        }

        layer.style.cssText = layerStyles.join(' ');
        image.style.cssText = imageStyles.join(' ');
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
}

var localCourseBannerBuilderPreviewInteraction = null;

function localCourseBannerBuilderRoundPreviewPercent(value) {
    return Math.round(Math.max(0, Math.min(100, value)) * 10) / 10;
}

function localCourseBannerBuilderSetPreviewFieldValue(field, value) {
    if (!field) {
        return;
    }
    field.value = String(localCourseBannerBuilderRoundPreviewPercent(value));
    field.dispatchEvent(new Event('input', {bubbles: true}));
    field.dispatchEvent(new Event('change', {bubbles: true}));
}

function localCourseBannerBuilderEnsurePreviewCustomMode(form) {
    if (!form) {
        return false;
    }
    var fitOverride = form.querySelector('#id_fitmodeoverride');
    var anchorInput = form.querySelector('[data-layer-position-anchor=\"1\"]');
    if (!fitOverride || !anchorInput) {
        return false;
    }
    if (fitOverride.value !== 'custom') {
        fitOverride.value = 'custom';
        fitOverride.dispatchEvent(new Event('change', {bubbles: true}));
    }
    if (anchorInput.value !== 'top-left') {
        anchorInput.value = 'top-left';
        anchorInput.dispatchEvent(new Event('change', {bubbles: true}));
    }
    return true;
}

function localCourseBannerBuilderClampPreviewSize(state, widthPercent, heightPercent) {
    var maxWidth = Math.max(1, 200 - state.leftPercent);
    var maxHeight = Math.max(1, 200 - state.topPercent);
    widthPercent = Math.max(1, Math.min(maxWidth, widthPercent));
    heightPercent = Math.max(1, Math.min(maxHeight, heightPercent));
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
    leftPercent = Math.max(-state.widthPercent, Math.min(100, leftPercent));
    topPercent = Math.max(-state.heightPercent, Math.min(100, topPercent));
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
}

function localCourseBannerBuilderHandlePreviewPointerMove(event) {
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
    if (!localCourseBannerBuilderEnsurePreviewCustomMode(form)) {
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
        var syncPreview = function() {
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
                localCourseBannerBuilderStartPreviewInteraction(event, 'drag', currentLayer);
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

document.addEventListener('pointermove', localCourseBannerBuilderHandlePreviewPointerMove);
document.addEventListener('pointerup', localCourseBannerBuilderStopPreviewInteraction);
document.addEventListener('pointercancel', localCourseBannerBuilderStopPreviewInteraction);

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
        });
        borderToggle.dataset.layerBound = '1';
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
        layerForm.addEventListener('submit', localCourseBannerBuilderValidateLayerForm);
        layerForm.dataset.layerBound = '1';
    }
}

function localCourseBannerBuilderInitPopovers(scope) {
    if (typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.popover) {
        return;
    }
    var root = scope || document;
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
    }
    select.disabled = !editing;
    if (editing) {
        window.setTimeout(function() {
            select.focus();
        }, 0);
    }
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
    localCourseBannerBuilderBindLayerFormEvents(form);
    localCourseBannerBuilderBindLayerPreviewEvents(form);
    localCourseBannerBuilderSyncDetailsCollapseIcons(form);
}

function localCourseBannerBuilderLoadCreateLayerModal() {
    var modal = document.getElementById('local-course-banner-builder-add-layer-modal');
    if (!modal) {
        return;
    }
    localCourseBannerBuilderRestoreCreateLayerModal();
    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(modal).modal('show');
        window.jQuery(modal).one('shown.bs.modal', function() {
            var form = modal.querySelector('form.mform');
            localCourseBannerBuilderSyncLayerInputModes(form);
            localCourseBannerBuilderSyncBorderPreview(form);
            localCourseBannerBuilderSyncDetailsCollapseIcons(modal);
        });
    }
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
    localCourseBannerBuilderAlignModalActionButtons(modal);
    localCourseBannerBuilderEnhanceAccordions(modal);
    localCourseBannerBuilderSyncDetailsCollapseIcons(modal);
    localCourseBannerBuilderInitPopovers(modal);
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
            button.style.marginLeft = 'auto';
            button.style.display = 'inline-flex';
        });
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
        if (typeof window.jQuery !== 'undefined') {
            window.jQuery(targetmodal).modal('show');
            window.jQuery(targetmodal).one('shown.bs.modal', function() {
                localCourseBannerBuilderPrepareDynamicLayerModal(targetmodal);
            });
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
    holder.style.left = '0px';
    holder.style.right = rightSpace + 'px';
    holder.style.width = 'calc(100vw - ' + rightSpace + 'px)';
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
            e.preventDefault();
            e.stopPropagation();
            localCourseBannerBuilderLoadCreateLayerModal();
        });
    });
    var params = new URLSearchParams(window.location.search);
    var settings = document.getElementById('local-course-banner-builder-source-settings');
    if (settings && (params.get('sourcekey') || parseInt(params.get('categoryid') || '0', 10) > 0)) {
        window.setTimeout(function() {
            settings.scrollIntoView({behavior: 'smooth', block: 'start'});
        }, 120);
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
        echo html_writer::tag('button', s($option['label']), $attributes);
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
            echo html_writer::tag('button', s($option['label']), [
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
            $categorysettings->fitmode ?? \local_course_banner_builder\manager::FIT_MODE_BANNER,
            false,
            ['id' => 'id_fitmode', 'class' => 'custom-select mt-2']
        );
        echo html_writer::tag('div', get_string('fitmode_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted mt-2']);
        echo html_writer::end_div();

        echo html_writer::start_div('mb-3');
        echo html_writer::label(get_string('fitapplyscope', 'local_course_banner_builder'), 'id_fitapplyscope');
        echo html_writer::select(
            \local_course_banner_builder\manager::get_fit_apply_scope_options(),
            'fitapplyscope',
            $categorysettings->fitapplyscope ?? \local_course_banner_builder\manager::FIT_SCOPE_SELF,
            false,
            ['id' => 'id_fitapplyscope', 'class' => 'custom-select mt-2']
        );
        echo html_writer::tag('div', get_string('fitapplyscope_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted mt-2']);
        echo html_writer::end_div();

        echo html_writer::start_div('local-course-banner-builder-submit-actions');
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('savecategorysettings', 'local_course_banner_builder'),
            'class' => 'btn btn-primary',
        ]);
        echo html_writer::end_div();
        echo html_writer::end_tag('form');
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();

        if ($formmode === 'create') {
            foreach ([
                'local-course-banner-builder-edit-border-layer-modal' => get_string('layerborder', 'local_course_banner_builder'),
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
            'editborder' => get_string('layerborder', 'local_course_banner_builder'),
            'editimage' => get_string('editimage', 'local_course_banner_builder'),
            default => get_string('addlayer', 'local_course_banner_builder'),
        };

        local_course_banner_builder_render_layer_modal($layermodalid, $layermodaltitle, function() use ($form) {
            $form->display();
        });
    }
}

if ($selectedsource) {
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
                ]
            ),
        ['class' => 'local-course-banner-builder-section-summary local-course-banner-builder-section-summary-actions']
    );
    echo $OUTPUT->render_from_template(
        'local_course_banner_builder/admin_selected',
        \local_course_banner_builder\manager::export_selected_source($selectedsource)
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

echo $OUTPUT->footer();
