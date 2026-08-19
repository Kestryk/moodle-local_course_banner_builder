<?php
/**
 * Disposable fixture for the IMG-06 add-image modal transform scenario.
 *
 * @package local_course_banner_builder
 */

define('CLI_SCRIPT', true);

$command = $argv[1] ?? '';
$moodleroot = $argv[2] ?? '';
$manifestpath = $argv[3] ?? '';

if ($moodleroot === '' || !is_file(rtrim($moodleroot, '/\\') . '/config.php')) {
    throw new RuntimeException('A valid Moodle root is required.');
}
require(rtrim($moodleroot, '/\\') . '/config.php');

global $DB, $USER;
$USER = get_admin();

$prefix = 'CCB QA IMG-06 ';
$emit = static function(array $value): void {
    echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
};

if ($command === 'setup') {
    $category = null;
    try {
        $category = core_course_category::create((object)[
            'name' => $prefix . gmdate('YmdHis'),
            'parent' => 0,
            'idnumber' => '',
            'description' => 'Disposable CCB IMG-06 add-image transform fixture.',
            'descriptionformat' => FORMAT_HTML,
            'visible' => 1,
        ]);
        $categoryid = (int)$category->id;
        \local_course_banner_builder\manager::save_category_settings(
            $categoryid,
            \local_course_banner_builder\manager::MODE_CUMULATIVE,
            \local_course_banner_builder\manager::FIT_MODE_ORIGINAL,
            \local_course_banner_builder\manager::FIT_SCOPE_SELF
        );
        $source = \local_course_banner_builder\manager::resolve_source(
            \local_course_banner_builder\manager::get_category_source_key($categoryid)
        );
        if (!$source) {
            throw new moodle_exception('Unable to resolve disposable IMG-06 source.');
        }
        $emit(['categoryid' => $categoryid, 'sourcekey' => $source->sourcekey]);
    } catch (Throwable $exception) {
        if ($category) {
            try {
                \local_course_banner_builder\manager::delete_category_content((int)$category->id, true);
                $category->delete_full(false);
            } catch (Throwable $cleanupexception) {
                debugging('IMG-06 fixture setup cleanup failed: ' . $cleanupexception->getMessage(), DEBUG_DEVELOPER);
            }
        }
        throw $exception;
    }
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new moodle_exception('Missing IMG-06 fixture manifest.');
    }
    $manifestjson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath));
    $manifest = json_decode($manifestjson, true, 512, JSON_THROW_ON_ERROR);
    $categoryid = (int)($manifest['categoryid'] ?? 0);
    $draftitemid = (int)($manifest['draftitemid'] ?? 0);
    if ($draftitemid > 0) {
        get_file_storage()->delete_area_files(
            context_user::instance($USER->id)->id,
            'user',
            'draft',
            $draftitemid
        );
    }
    if ($categoryid > 0 && $DB->record_exists('course_categories', ['id' => $categoryid])) {
        $record = $DB->get_record('course_categories', ['id' => $categoryid], 'id,name', MUST_EXIST);
        if (!str_starts_with((string)$record->name, $prefix)) {
            throw new moodle_exception('Refusing to remove a category not owned by IMG-06.');
        }
        if ($DB->count_records('course', ['category' => $categoryid]) !== 0) {
            throw new moodle_exception('Refusing to remove an IMG-06 category containing courses.');
        }
        \local_course_banner_builder\manager::delete_category_content($categoryid, true);
        core_course_category::get($categoryid, MUST_EXIST)->delete_full(false);
    }
    $categoryexists = $categoryid > 0 && $DB->record_exists('course_categories', ['id' => $categoryid]);
    $remainingelements = $categoryid > 0 ?
        $DB->count_records('local_course_banner_builder_elements', ['categoryid' => $categoryid]) :
        0;
    if ($categoryexists || $remainingelements !== 0) {
        throw new moodle_exception('IMG-06 fixture cleanup verification failed.');
    }
    $emit([
        'categoryid' => $categoryid,
        'categoryRemoved' => true,
        'draftItemRemoved' => $draftitemid > 0,
        'remainingElements' => 0,
    ]);
    exit(0);
}

throw new moodle_exception('Unknown IMG-06 fixture command.');
