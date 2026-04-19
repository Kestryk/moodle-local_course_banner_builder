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

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$elementid = optional_param('elementid', 0, PARAM_INT);
$deleteelementid = optional_param('deleteelementid', 0, PARAM_INT);
$deletecategorycontent = optional_param('deletecategorycontent', 0, PARAM_INT);
$deletecategoryimages = optional_param('deletecategoryimages', 0, PARAM_INT);
$confirmdeletecategory = optional_param('confirmdeletecategory', 0, PARAM_BOOL);
$confirmdeleteimages = optional_param('confirmdeleteimages', 0, PARAM_BOOL);
$savecategorysettings = optional_param('savecategorysettings', 0, PARAM_BOOL);
$updatelayerinline = optional_param('updatelayerinline', 0, PARAM_BOOL);
$updatelayersbulk = optional_param('updatelayersbulk', 0, PARAM_BOOL);
$deleteselectedlayers = optional_param('deleteselectedlayers', 0, PARAM_BOOL);

admin_externalpage_setup('local_course_banner_builder_manage');
require_capability('local/course_banner_builder:manage', context_system::instance());

$url = new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $categoryid]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('managebanners', 'local_course_banner_builder'));
$PAGE->set_heading(get_string('managebanners', 'local_course_banner_builder'));

$categoryoptions = \local_course_banner_builder\manager::get_category_source_options();
$customfieldoptions = \local_course_banner_builder\manager::get_enabled_customfield_source_options();

if ($deleteelementid && confirm_sesskey()) {
    \local_course_banner_builder\manager::delete_banner_element($deleteelementid);
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $categoryid]),
        get_string('bannerdeleted', 'local_course_banner_builder')
    );
}

if ($deleteselectedlayers && confirm_sesskey()) {
    $selectedelementids = optional_param_array('selectedelements', [], PARAM_INT);
    $deleted = \local_course_banner_builder\manager::delete_banner_elements($selectedelementids);
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $categoryid]),
        get_string('selectedlayersdeleted', 'local_course_banner_builder', $deleted)
    );
}

if ($deletecategoryimages) {
    $categoryforredirect = $categoryid ?: $deletecategoryimages;
    if ($confirmdeleteimages && confirm_sesskey()) {
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

if ($deletecategorycontent) {
    $categoryforredirect = $categoryid ?: $deletecategorycontent;
    if ($confirmdeletecategory && confirm_sesskey()) {
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

$selectedcategoryid = $categoryid;

if ($elementid) {
    $element = \local_course_banner_builder\manager::get_banner_element($elementid);
    if (!$element || (int)$element->categoryid !== $selectedcategoryid) {
        $elementid = 0;
    }
}

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
        new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $selectedcategoryid]),
        get_string('changessaved')
    );
}

if ($updatelayersbulk && confirm_sesskey() && $selectedcategoryid) {
    $layernames = optional_param_array('layername_inline', [], PARAM_TEXT);
    $sortorders = optional_param_array('sortorder_inline', [], PARAM_INT);
    $enabledlayers = optional_param_array('isenabled_inline', [], PARAM_BOOL);
    $fitmodeoverrides = optional_param_array('fitmodeoverride_inline', [], PARAM_ALPHA);
    \local_course_banner_builder\manager::update_banner_element_rows(
        $selectedcategoryid,
        $layernames,
        $sortorders,
        $enabledlayers,
        $fitmodeoverrides
    );
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $selectedcategoryid]),
        get_string('changessaved')
    );
}

$form = new \local_course_banner_builder\form\manage_banner_form(null, [
    'selectedcategoryid' => $selectedcategoryid,
    'elementid' => $elementid,
    'filemanageroptions' => \local_course_banner_builder\manager::get_filemanager_options(empty($elementid)),
    'uploadguidance' => \local_course_banner_builder\manager::get_upload_guidance(),
]);

if ($savecategorysettings && confirm_sesskey() && $selectedcategoryid) {
    $compositionmode = required_param('compositionmode', PARAM_ALPHA);
    $fitmode = required_param('fitmode', PARAM_ALPHA);
    $fitapplyscope = required_param('fitapplyscope', PARAM_ALPHA);
    \local_course_banner_builder\manager::save_category_settings(
        $selectedcategoryid,
        $compositionmode,
        $fitmode,
        $fitapplyscope
    );
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $selectedcategoryid]),
        get_string('changessaved')
    );
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/category.php', ['category' => 'local_course_banner_builder']));
}

if ($data = $form->get_data()) {
    \local_course_banner_builder\manager::save_category_banner($data);
    redirect(
        new moodle_url('/local/course_banner_builder/admin_manage.php', ['categoryid' => $data->categoryid]),
        get_string('changessaved')
    );
}

if ($selectedcategoryid) {
    $form->set_data(\local_course_banner_builder\manager::get_form_data($selectedcategoryid, $elementid, empty($elementid)));
}

$PAGE->requires->js_init_code("
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-action=\"local-course-banner-builder-summary-action\"]')) {
        e.stopPropagation();
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
        label.classList.add('active');
        label.classList.remove('btn-outline-secondary');
        label.classList.add('btn-primary');
    }
    localCourseBannerBuilderSyncSourceSubmit(dropdown);
    Array.prototype.slice.call(dropdown.querySelectorAll('[data-source-option]')).forEach(function(item) {
        item.classList.toggle('active', item === option);
    });
});

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
    var hasSelection = parseInt(input.value || '0', 10) > 0;
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
        field.classList.toggle('easyedu-input-disabled', isBulkUpload);
        if (isBulkUpload) {
            field.setAttribute('aria-disabled', 'true');
        } else {
            field.removeAttribute('aria-disabled');
        }
    });
}

function localCourseBannerBuilderSyncLayerSortOrders() {
    Array.prototype.slice.call(document.querySelectorAll('.local-course-banner-builder-layer-sortable')).forEach(function(tbody) {
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

function localCourseBannerBuilderSyncStickyHeader() {
    var holder = document.querySelector('.local-course-banner-builder-selected-source-sticky-holder');
    var header = document.querySelector('.local-course-banner-builder-selected-source-sticky');
    if (!holder || !header) {
        return;
    }

    var nav = document.querySelector('.navbar.fixed-top, header.fixed-top, #page-header.fixed-top');
    var top = nav ? Math.max(0, nav.getBoundingClientRect().bottom) : 0;
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

    holder.style.left = '0px';
    holder.style.right = rightSpace + 'px';
    holder.style.width = 'calc(100vw - ' + rightSpace + 'px)';
    holder.style.top = top + 'px';
    holder.style.setProperty('--easyedu-sticky-header-right-space', '0px');
    holder.style.minHeight = header.offsetHeight + 'px';
    document.body.style.setProperty('--local-course-banner-builder-sticky-header-space', header.offsetHeight + 'px');
}

document.addEventListener('DOMContentLoaded', function() {
    var filemanager = document.querySelector('#fitem_id_bannerimage_filemanager');
    localCourseBannerBuilderSyncSelectionButton();
    localCourseBannerBuilderSyncLayerSortOrders();
    localCourseBannerBuilderSyncBulkFields();
    localCourseBannerBuilderSyncStickyHeader();
    Array.prototype.slice.call(document.querySelectorAll('[data-source-dropdown]')).forEach(localCourseBannerBuilderSyncSourceSubmit);
    var params = new URLSearchParams(window.location.search);
    var settings = document.getElementById('local-course-banner-builder-source-settings');
    if (settings && parseInt(params.get('categoryid') || '0', 10) > 0) {
        window.setTimeout(function() {
            settings.scrollIntoView({behavior: 'smooth', block: 'start'});
        }, 120);
    }
    window.addEventListener('resize', localCourseBannerBuilderSyncStickyHeader, {passive: true});
    window.addEventListener('scroll', localCourseBannerBuilderSyncStickyHeader, {passive: true});
    if (typeof MutationObserver !== 'undefined') {
        var drawerObserver = new MutationObserver(localCourseBannerBuilderSyncStickyHeader);
        drawerObserver.observe(document.body, {attributes: true, childList: true, subtree: true, attributeFilter: ['class', 'style', 'aria-expanded']});
    }
    if (!filemanager || typeof MutationObserver === 'undefined') {
        return;
    }
    var observer = new MutationObserver(localCourseBannerBuilderSyncBulkFields);
    observer.observe(filemanager, {childList: true, subtree: true});
});
");

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managebanners', 'local_course_banner_builder'));

$selectedcategorylabel = '';
foreach ($categoryoptions as $option) {
    if ((int)$option['id'] === (int)$selectedcategoryid) {
        $selectedcategorylabel = $option['label'];
        break;
    }
}
if ($selectedcategorylabel !== '') {
    $deselectsourceurl = new moodle_url('/local/course_banner_builder/admin_manage.php');
    echo html_writer::div(
        html_writer::div(
            html_writer::div(
                html_writer::tag('span', get_string('selectedcategorystatus', 'local_course_banner_builder'), [
                    'class' => 'local-course-banner-builder-sticky-kicker',
                ]) .
                html_writer::link(
                    $deselectsourceurl,
                    get_string('deselectsource', 'local_course_banner_builder'),
                    ['class' => 'btn btn-outline-danger btn-sm local-course-banner-builder-sticky-deselect']
                ),
                'local-course-banner-builder-sticky-leading'
            ) .
            html_writer::tag('strong', s($selectedcategorylabel), [
                'class' => 'local-course-banner-builder-sticky-title',
            ]),
            'focus-navigation-buttons focus-navigation-buttons--floating easyedu-sticky-header local-course-banner-builder-selected-source-sticky'
        ),
        'focus-navigation-buttons-holder focus-navigation-buttons-holder--floating easyedu-sticky-header-holder local-course-banner-builder-selected-source-sticky-holder'
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
    $categorybuttonclass = 'btn btn-outline-secondary dropdown-toggle local-course-banner-builder-source-dropdown-toggle';
    if ($selectedcategoryid) {
        $categorybuttonclass = 'btn btn-primary dropdown-toggle local-course-banner-builder-source-dropdown-toggle active';
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

    echo html_writer::start_div('local-course-banner-builder-source-column local-course-banner-builder-source-column-disabled');
    echo html_writer::tag('h3', get_string('coursecustomfields', 'local_course_banner_builder'), ['class' => 'h5']);
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
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'id_customfieldid', 'value' => '']);
    echo html_writer::start_div('dropdown local-course-banner-builder-source-dropdown mb-2', [
        'data-source-dropdown' => 'customfield',
        'data-input' => '#id_customfieldid',
    ]);
    echo html_writer::tag('button', empty($customfieldoptions) ? get_string('choosecustomfielddefault', 'local_course_banner_builder') :
        get_string('choosecustomfielddefault', 'local_course_banner_builder'), [
        'type' => 'button',
        'id' => 'id_customfield_dropdown_button',
        'class' => 'btn btn-outline-secondary dropdown-toggle local-course-banner-builder-source-dropdown-toggle',
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
            echo html_writer::tag('button', s($option['label']), [
                'type' => 'button',
                'class' => 'dropdown-item local-course-banner-builder-source-option',
                'data-source-option' => 'customfield',
                'data-value' => $option['id'],
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
    echo html_writer::tag('div', get_string('customfieldscomingsoon', 'local_course_banner_builder'), ['class' => 'form-text text-muted']);
    echo html_writer::end_div();

    echo html_writer::end_div();

    if ($selectedcategoryid) {
        $categorysettings = \local_course_banner_builder\manager::get_category_settings($selectedcategoryid);

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
            'action' => (new moodle_url('/local/course_banner_builder/admin_manage.php', [
                'categoryid' => $selectedcategoryid,
            ]))->out(false),
            'class' => 'local-course-banner-builder-settings-form',
        ]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savecategorysettings', 'value' => 1]);

        echo html_writer::start_div('mb-3');
        echo html_writer::tag(
            'label',
            html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'disabled' => 'disabled',
            ]) . ' ' . get_string('customfieldpriority', 'local_course_banner_builder'),
            ['class' => 'd-block mb-2 text-muted']
        );
        echo html_writer::tag('div', get_string('customfieldpriority_help', 'local_course_banner_builder'), ['class' => 'form-text text-muted']);
        echo html_writer::end_div();

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
            \local_course_banner_builder\manager::get_fit_mode_options(),
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

        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('savecategorysettings', 'local_course_banner_builder'),
            'class' => 'btn btn-primary',
        ]);
        echo html_writer::end_tag('form');
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();

        echo html_writer::start_div('modal fade', [
            'id' => 'local-course-banner-builder-add-layer-modal',
            'tabindex' => '-1',
            'role' => 'dialog',
            'aria-labelledby' => 'local-course-banner-builder-add-layer-modal-title',
            'aria-hidden' => 'true',
        ]);
        echo html_writer::start_div('modal-dialog modal-xl', ['role' => 'document']);
        echo html_writer::start_div('modal-content');
        echo html_writer::start_div('modal-header');
        echo html_writer::tag('h5', get_string('addlayer', 'local_course_banner_builder'), [
            'class' => 'modal-title',
            'id' => 'local-course-banner-builder-add-layer-modal-title',
        ]);
        echo html_writer::tag('button', html_writer::span('&times;', '', ['aria-hidden' => 'true']), [
            'type' => 'button',
            'class' => 'close',
            'data-dismiss' => 'modal',
            'aria-label' => get_string('closebuttontitle'),
        ]);
        echo html_writer::end_div();
        echo html_writer::start_div('modal-body');
        $form->display();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

if ($selectedcategoryid) {
    echo html_writer::start_tag('details', ['class' => 'local-course-banner-builder-section mb-4', 'open' => 'open']);
    echo html_writer::tag(
        'summary',
        html_writer::span(get_string('selectedcategorystatus', 'local_course_banner_builder'), '', [
            'class' => 'local-course-banner-builder-section-title-text',
        ]) .
        html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'icon fa fa-cog fa-fw', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('sourcesettingsshort', 'local_course_banner_builder')),
            [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary btn-sm btn-border-dashed local-course-banner-builder-dashed-action',
                'data-action' => 'local-course-banner-builder-summary-action',
                'data-toggle' => 'modal',
                'data-target' => '#local-course-banner-builder-source-settings-modal',
            ]
        ),
        ['class' => 'local-course-banner-builder-section-summary local-course-banner-builder-section-summary-actions']
    );
    echo $OUTPUT->render_from_template(
        'local_course_banner_builder/admin_selected',
        \local_course_banner_builder\manager::export_selected_category($selectedcategoryid)
    );
    echo html_writer::end_tag('details');
}

echo html_writer::start_tag('details', ['class' => 'local-course-banner-builder-section mb-4', 'open' => 'open']);
echo html_writer::tag('summary', get_string('configuredcategories', 'local_course_banner_builder'));
echo $OUTPUT->render_from_template(
    'local_course_banner_builder/admin_manage',
    \local_course_banner_builder\manager::export_configured_categories()
);
echo html_writer::end_tag('details');

echo $OUTPUT->footer();
