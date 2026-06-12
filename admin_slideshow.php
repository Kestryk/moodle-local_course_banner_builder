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
$PAGE->requires->js_call_amd('local_course_banner_builder/slideshow_admin', 'init');

$deleteallpluginsettings = optional_param('deleteallpluginsettings', 0, PARAM_BOOL);
if ($deleteallpluginsettings && confirm_sesskey()) {
    manager::delete_all_plugin_configuration();
    redirect(
        new moodle_url('/local/course_banner_builder/admin_slideshow.php'),
        get_string('allpluginsettingsdeleted', 'local_course_banner_builder')
    );
}

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
    foreach (['delay', 'maxslides', 'siteannouncementdays', 'overlayopacity', 'titlefontsize', 'bodyfontsize', 'actionsize', 'actionwidth',
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
        $clean[$field] = clean_param($values[$field] ?? '', PARAM_TEXT);
    }
    foreach (manager::get_default_slideshow_label_colors() as $type => $defaults) {
        foreach (['background', 'text', 'border', 'shadow'] as $role) {
            $field = 'label_' . $type . '_' . $role;
            $clean[$field] = clean_param($values[$field] ?? ($defaults[$role] ?? ''), PARAM_TEXT);
        }
    }
    foreach (['labelorientation', 'labelcorners', 'actioncorners', 'titlealign', 'bodyalign', 'labelalign'] as $field) {
        $clean[$field] = clean_param($values[$field] ?? '', PARAM_ALPHA);
    }

    return $clean;
}

/**
 * Get bulk slideshow settings from a submitted Moodle form.
 *
 * Moodle optional_param_array() only supports flat arrays, while the bulk
 * slideshow form posts values grouped by slideshow context.
 *
 * @return array
 */
function local_course_banner_builder_get_submitted_slideshow_bulk(): array {
    $submitted = data_submitted();
    if (!$submitted || empty($submitted->slideshowbulk) || !is_array($submitted->slideshowbulk)) {
        return [];
    }

    $bulk = [];
    foreach ($submitted->slideshowbulk as $context => $values) {
        if (!is_array($values)) {
            continue;
        }
        $cleancontext = clean_param($context, PARAM_ALPHA);
        if ($cleancontext !== (string)$context) {
            continue;
        }

        $cleanvalues = [];
        foreach ($values as $field => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            if (!preg_match('/^[a-z0-9_]+$/i', (string)$field)) {
                continue;
            }
            $cleanvalues[(string)$field] = $value;
        }
        $bulk[$cleancontext] = $cleanvalues;
    }

    return $bulk;
}

if (optional_param('updateslideshow', 0, PARAM_BOOL) && confirm_sesskey()) {
    $context = required_param('context', PARAM_ALPHA);
    if (optional_param('saveallslideshows', 0, PARAM_BOOL)) {
        $bulk = local_course_banner_builder_get_submitted_slideshow_bulk();
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
            'maxslides' => optional_param('maxslides', manager::SLIDESHOW_DEFAULT_MAX_SLIDES, PARAM_INT),
            'siteannouncementdays' => optional_param(
                'siteannouncementdays',
                manager::SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS,
                PARAM_INT
            ),
            'arrows' => optional_param('arrows', 0, PARAM_BOOL),
            'dots' => optional_param('dots', 0, PARAM_BOOL),
            'overlaycolor' => $defaultoverlay
                ? manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR
                : ($defaultall
                    ? manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR
                    : optional_param('overlaycolor', manager::SLIDESHOW_DEFAULT_OVERLAY_COLOR, PARAM_TEXT)),
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
                : optional_param('titlecolor', manager::SLIDESHOW_DEFAULT_TITLE_COLOR, PARAM_TEXT),
            'bodycolor' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_BODY_COLOR
                : optional_param('bodycolor', manager::SLIDESHOW_DEFAULT_BODY_COLOR, PARAM_TEXT),
            'titlefontfamily' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY
                : optional_param('titlefontfamily', manager::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY, PARAM_TEXT),
            'bodyfontfamily' => $defaulttext || $defaultall
                ? manager::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY
                : optional_param('bodyfontfamily', manager::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY, PARAM_TEXT),
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
                    : optional_param('label_forums_background', $defaults['forums']['background'], PARAM_TEXT)),
            'label_forums_text' => $defaultlabels
                ? $defaults['forums']['text']
                : ($defaultall ? $defaults['forums']['text']
                    : optional_param('label_forums_text', $defaults['forums']['text'], PARAM_TEXT)),
            'label_siteannouncements_background' => $defaultlabels
                ? $defaults['siteannouncements']['background']
                : ($defaultall ? $defaults['siteannouncements']['background']
                    : optional_param('label_siteannouncements_background', $defaults['siteannouncements']['background'], PARAM_TEXT)),
            'label_siteannouncements_text' => $defaultlabels
                ? $defaults['siteannouncements']['text']
                : ($defaultall ? $defaults['siteannouncements']['text']
                    : optional_param('label_siteannouncements_text', $defaults['siteannouncements']['text'], PARAM_TEXT)),
            'label_assignments_background' => $defaultlabels
                ? $defaults['assignments']['background']
                : ($defaultall ? $defaults['assignments']['background']
                    : optional_param('label_assignments_background', $defaults['assignments']['background'], PARAM_TEXT)),
            'label_assignments_text' => $defaultlabels
                ? $defaults['assignments']['text']
                : ($defaultall ? $defaults['assignments']['text']
                    : optional_param('label_assignments_text', $defaults['assignments']['text'], PARAM_TEXT)),
            'label_quizzes_background' => $defaultlabels
                ? $defaults['quizzes']['background']
                : ($defaultall ? $defaults['quizzes']['background']
                    : optional_param('label_quizzes_background', $defaults['quizzes']['background'], PARAM_TEXT)),
            'label_quizzes_text' => $defaultlabels
                ? $defaults['quizzes']['text']
                : ($defaultall ? $defaults['quizzes']['text']
                    : optional_param('label_quizzes_text', $defaults['quizzes']['text'], PARAM_TEXT)),
        ];
        foreach ($defaults as $type => $colours) {
            foreach (['background', 'text', 'border', 'shadow'] as $role) {
                $slideshowvalues['label_' . $type . '_' . $role] = ($defaultlabels || $defaultall)
                    ? $colours[$role]
                    : optional_param('label_' . $type . '_' . $role, $colours[$role], PARAM_TEXT);
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
                    : optional_param($field, $defaultvalue, PARAM_TEXT);
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
            manager::BANNER_FORMAT_FULLWIDTH_TOP_INSET => 'bannerformat:fullwidthtopinset_help',
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
 * @param bool $asbutton
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
 * @param string $format
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
 * @param string $context
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
    $stylenumber = static function (string $field) use ($config, $styledefaults): int {
        return (int)($config[$field] ?? $styledefaults[$field]);
    };
    $stylestring = static function (string $field) use ($config, $styledefaults): string {
        return (string)($config[$field] ?? $styledefaults[$field]);
    };
    $stylergb = static function (string $hex): string {
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
    $previewtoolbarbutton = static function (string $iconclass, string $label, string $action, array $extra = []): string {
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
    $resizehandles = static function (): string {
        $html = '';
        foreach (['top', 'right', 'bottom', 'left'] as $edge) {
            $html .= html_writer::span('', 'local-course-banner-builder-preview-resize-handle local-course-banner-builder-preview-resize-handle--' . $edge, [
                'data-slideshow-preview-resize-handle' => $edge,
                'aria-hidden' => 'true',
            ]);
        }
        return $html;
    };
    $cornertoggle = static function (string $target, string $label): string {
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
    $textstylebuttons = static function (string $target, array $values): string {
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
    $textstyletoolbar = static function (string $target, array $values): string {
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
    $sidepanel = static function (string $key, string $content): string {
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
    $sidepanelbutton = static function (string $key, string $icon, string $label): string {
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
    $slidercontrol = static function (
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
    $colorcontrol = static function (string $name, string $label, string $value, string $cssvar, ?string $default = null): string {
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
    $proxycolorcontrol = static function (string $target, string $label, string $value): string {
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
    $fontcontrol = static function (
        string $name,
        string $label,
        string $value,
        string $cssvar,
        string $default = ''
    ): string {
        return html_writer::div($label, 'local-course-banner-builder-slideshow-side-title') .
            local_course_banner_builder_render_slideshow_font_select($name, $value, $cssvar, $default);
    };
    $proxyfontcontrol = static function (string $target, string $label, string $value): string {
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
    $cornerswitch = static function (string $target, string $inputname, string $current): string {
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
    $orientationbuttons = static function (string $current): string {
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
    $alignmentbuttons = static function (string $current, string $target = 'label'): string {
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
                html_writer::tag('p',
                    get_string('slideshowpreviewbody', 'local_course_banner_builder') .
                        html_writer::empty_tag('br') .
                        get_string('slideshowpreviewbodyline2', 'local_course_banner_builder'),
                    [
                    'class' => 'local-course-banner-builder-slideshow-body',
                    ]
                ) .
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
    $labelcolourfield = static function (
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
                    html_writer::span(get_string('slideshoweditappearancefor', 'local_course_banner_builder', $contextlabel)),
                [
                    'type' => 'button',
                    'class' => 'btn btn-primary local-course-banner-builder-slideshow-edit-appearance-button',
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
    $content .= html_writer::start_div('form-group local-course-banner-builder-slideshow-max-slides');
    $content .= html_writer::tag('label', get_string('slideshowmaxslides', 'local_course_banner_builder'), [
        'for' => 'slideshow-max-slides-' . $context,
    ]);
    $content .= html_writer::empty_tag('input', [
        'type' => 'number',
        'class' => 'form-control',
        'id' => 'slideshow-max-slides-' . $context,
        'name' => 'maxslides',
        'min' => 1,
        'max' => manager::SLIDESHOW_MAX_SLIDES,
        'step' => 1,
        'value' => (int)($config['maxslides'] ?? manager::SLIDESHOW_DEFAULT_MAX_SLIDES),
    ]);
    $content .= html_writer::div(get_string('slideshowmaxslides_help', 'local_course_banner_builder'), 'form-text text-muted');
    $content .= html_writer::end_div();
    $content .= html_writer::start_div('form-group local-course-banner-builder-slideshow-site-announcement-days');
    $content .= html_writer::tag('label', get_string('slideshowsiteannouncementdays', 'local_course_banner_builder'), [
        'for' => 'slideshow-site-announcement-days-' . $context,
    ]);
    $content .= html_writer::empty_tag('input', [
        'type' => 'number',
        'class' => 'form-control',
        'id' => 'slideshow-site-announcement-days-' . $context,
        'name' => 'siteannouncementdays',
        'min' => 1,
        'max' => 3650,
        'step' => 1,
        'value' => (int)($config['siteannouncementdays'] ?? manager::SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS),
    ]);
    $content .= html_writer::div(get_string('slideshowsiteannouncementdays_help', 'local_course_banner_builder'), 'form-text text-muted');
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

    $content .= local_course_banner_builder_render_slideshow_overlay_settings($config, $context);

    $content .= html_writer::div(
        html_writer::tag('button',
            html_writer::tag('i', '', ['class' => 'fa fa-rotate-left me-2', 'aria-hidden' => 'true']) .
                html_writer::span(get_string('slideshowdefaultsettingsfor', 'local_course_banner_builder', $title)),
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
$deletepluginsettingsform = html_writer::tag(
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
        'action' => (new moodle_url('/local/course_banner_builder/admin_slideshow.php'))->out(false),
        'class' => 'd-inline local-course-banner-builder-admin-reset-form',
    ]
);
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
    html_writer::link(
        new moodle_url('/local/course_banner_builder/admin_transfer.php'),
        html_writer::tag('i', '', ['class' => 'fa fa-right-left me-2', 'aria-hidden' => 'true']) .
            html_writer::span(get_string('transferconfig', 'local_course_banner_builder')),
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
    ) .
    $deletepluginsettingsform,
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
