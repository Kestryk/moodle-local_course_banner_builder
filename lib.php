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
 * Library callbacks.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Whether a generated plugin file URL is being opened as the main browser document.
 *
 * @return bool
 */
function local_course_banner_builder_is_document_file_request(): bool {
    $fetchdest = strtolower((string)($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
    if ($fetchdest === 'document') {
        return true;
    }
    if ($fetchdest === 'image' || $fetchdest === 'style' || $fetchdest === 'script') {
        return false;
    }

    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return $accept !== '' && strpos($accept, 'text/html') !== false;
}

/**
 * Redirect away from generated image URLs when Moodle stored one as a login wantsurl.
 *
 * @return void
 */
function local_course_banner_builder_redirect_document_file_request(): void {
    global $CFG, $SESSION;

    if (!local_course_banner_builder_is_document_file_request()) {
        return;
    }

    if (isset($SESSION->wantsurl) && strpos((string)$SESSION->wantsurl, '/local_course_banner_builder/') !== false) {
        unset($SESSION->wantsurl);
    }

    $target = new moodle_url($CFG->wwwroot . '/my/');
    redirect($target, '', 0);
}

/**
 * Serve banner image files.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool|void
 */
function local_course_banner_builder_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload,
        array $options = []) {
    if ($filearea === 'coursecard') {
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return false;
        }

        local_course_banner_builder_redirect_document_file_request();

        $filename = array_pop($args);
        if (!$filename) {
            return false;
        }

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_course_banner_builder', $filearea, 0, '/', $filename);
        if (!$file || $file->is_directory()) {
            send_file_not_found();
        }

        send_stored_file($file, 60 * 60, 0, $forcedownload, $options);
    }

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'bannerimage') {
        return false;
    }

    require_login();
    local_course_banner_builder_redirect_document_file_request();

    $itemid = (int)array_shift($args);
    $filepath = '/' . implode('/', array_slice($args, 0, -1)) . '/';
    $filename = array_pop($args);
    if (!$itemid || !$filename) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_course_banner_builder', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 60 * 60, 0, $forcedownload, $options);
}
