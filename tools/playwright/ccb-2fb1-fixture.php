<?php
/**
 * CCB Batch 2F-B.1 fixture authority helper.
 *
 * This is intentionally a Moodle CLI bootstrap: all mutations go through
 * Moodle category/course APIs or the CCB manager, never through SQL strings.
 */
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$command = $argv[1] ?? '';
$manifestpath = $argv[2] ?? '';
$courseid = 11;
$stablecategoryid = 3;
$categoryid = (int)($argv[3] ?? 0);

$titlefields = [
    'enabled', 'x', 'y', 'fontsize', 'lineheight', 'fontfamily', 'color', 'align',
    'bold', 'italic', 'underline', 'strike', 'allcaps', 'aboveborder', 'aboveoverlay',
    'replacemoodletitle', 'frameenabled', 'frameshadowenabled', 'shadowenabled',
    'overlayenabled', 'frametype', 'stylemode', 'framecolor', 'framebordercolor',
    'frameshadowcolor', 'shadowcolor', 'overlaycolor', 'frameopacity', 'frameborderwidth',
    'frameradius', 'framepadding', 'frameshadowopacity', 'frameshadowblur',
    'frameshadowdistance', 'frameshadowdirection', 'shadowopacity', 'shadowblur',
    'shadowdistance', 'shadowdirection', 'overlayopacity', 'activitytitlemode',
];

$gettitleconfig = static function(string $context) use ($titlefields): array {
    $result = [];
    foreach ($titlefields as $field) {
        $name = 'bannertitle_' . $context . '_' . $field;
        $value = get_config('local_course_banner_builder', $name);
        if ($value !== false && $value !== null) {
            $result[$field] = $value;
        }
    }
    return $result;
};

$snapshot = static function() use ($DB, $gettitleconfig, $courseid, $stablecategoryid): array {
    $course = $DB->get_record('course', ['id' => $courseid], 'id,category,fullname,format,showgrades', MUST_EXIST);
    $cm = $DB->get_record('course_modules', ['id' => 12, 'course' => $courseid], 'id,course', MUST_EXIST);
    $source = \local_course_banner_builder\manager::resolve_source(
        \local_course_banner_builder\manager::get_category_source_key($stablecategoryid)
    );
    $sourceSettings = $source ? (array)\local_course_banner_builder\manager::get_source_settings($source) : [];
    $elements = $source ? count(\local_course_banner_builder\manager::get_source_elements($source)) : 0;
    return [
        'course' => (array)$course,
        'stableCategoryId' => $stablecategoryid,
        'activityCmid' => (int)$cm->id,
        'courseTitle' => $gettitleconfig('course'),
        'activityTitle' => $gettitleconfig('activity'),
        'coursebanneractivitiesenabled' => (int)(bool)get_config('local_course_banner_builder', 'coursebanneractivitiesenabled'),
        'coursebannerenabled' => (int)(bool)get_config('local_course_banner_builder', 'coursebannerenabled'),
        'stableSourceSettings' => $sourceSettings,
        'stableSourceElementCount' => $elements,
        'capturedAt' => gmdate('c'),
    ];
};

$emit = static function(array $value): void {
    echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
};

if ($command === 'snapshot') {
    $emit($snapshot());
    exit(0);
}

if ($command === 'setup') {
    $name = 'CCB QA disposable source 2FB1 ' . gmdate('YmdHis');
    $category = core_course_category::create((object)[
        'name' => $name,
        'parent' => 0,
        'idnumber' => '',
        'description' => 'Temporary CCB QA source. Safe to remove after the run.',
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
    $data = (object)[
        'categoryid' => $categoryid,
        'sourcekey' => \local_course_banner_builder\manager::get_category_source_key($categoryid),
        'elementid' => 0,
        'name' => 'CCB QA temporary overlay',
        'isenabled' => 1,
        'overlayenabled' => 1,
        'overlaytarget' => 'both',
        'overlaybannercolor' => '#000000',
        'overlaybanneropacity' => 35,
        'overlayslideshowcolor' => '#000000',
        'overlayslideshowopacity' => 35,
        'overlaytitleabove' => 1,
        'overlayborderabove' => 1,
        'bannerimage_filemanager' => 0,
        'multilayerdraftsettings' => '',
    ];
    $elementid = \local_course_banner_builder\manager::save_category_banner($data);
    $emit(['categoryid' => $categoryid, 'elementid' => (int)$elementid, 'name' => $name]);
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new \moodle_exception('Missing CCB restoration manifest.');
    }
    $manifestjson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath));
    $manifest = json_decode($manifestjson, true, 512, JSON_THROW_ON_ERROR);
    $course = $DB->get_record('course', ['id' => $courseid], 'id,category,fullname,format,showgrades', MUST_EXIST);
    core_course_category::get($stablecategoryid, MUST_EXIST);
    if ((int)$course->category !== $stablecategoryid) {
        if ((int)$course->category !== (int)($manifest['temporary']['categoryid'] ?? 0)) {
            throw new \moodle_exception('Stable fixture is in an unexpected category.');
        }
        move_courses([$courseid], $stablecategoryid);
    }
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $capturedCourse = (object)$manifest['course'];
    $course->fullname = $capturedCourse->fullname;
    $course->format = $capturedCourse->format;
    $course->showgrades = $capturedCourse->showgrades;
    update_course($course);

    foreach (['course', 'activity'] as $context) {
        foreach (($manifest[$context . 'Title'] ?? []) as $field => $value) {
            set_config('bannertitle_' . $context . '_' . $field, $value, 'local_course_banner_builder');
        }
    }
    set_config('coursebanneractivitiesenabled', (int)$manifest['coursebanneractivitiesenabled'], 'local_course_banner_builder');
    set_config('coursebannerenabled', (int)$manifest['coursebannerenabled'], 'local_course_banner_builder');

    $temporaryid = (int)($manifest['temporary']['categoryid'] ?? $categoryid);
    $removed = false;
    if ($temporaryid > 0 && $DB->record_exists('course_categories', ['id' => $temporaryid])) {
        $temporary = core_course_category::get($temporaryid, MUST_EXIST);
        if (count($temporary->get_courses(['recursive' => true, 'summary' => false])) !== 0) {
            throw new \moodle_exception('Temporary category still contains courses.');
        }
        \local_course_banner_builder\manager::delete_category_content($temporaryid, true);
        $temporary->delete_full(false);
        $removed = true;
    }
    $exists = $DB->record_exists('course_categories', ['id' => $temporaryid]);
    $elements = $temporaryid > 0 ? $DB->count_records('local_course_banner_builder_elements', ['categoryid' => $temporaryid]) : 0;
    $finalcourse = $DB->get_record('course', ['id' => $courseid], 'id,category,fullname,format,showgrades', MUST_EXIST);
    if ((int)$finalcourse->category !== $stablecategoryid || $finalcourse->fullname !== $capturedCourse->fullname || $exists || $elements !== 0) {
        throw new \moodle_exception('Independent fixture cleanup verification failed.');
    }
    $emit([
        'courseid' => $courseid,
        'courseCategory' => (int)$finalcourse->category,
        'courseRestored' => true,
        'settingsRestored' => true,
        'temporaryCategoryRemoved' => !$exists,
        'relatedElements' => $elements,
        'removedDuringThisCall' => $removed,
    ]);
    exit(0);
}

if ($command === 'verify') {
    $course = $DB->get_record('course', ['id' => $courseid], 'id,category,fullname', MUST_EXIST);
    $categoryExists = $categoryid > 0 && $DB->record_exists('course_categories', ['id' => $categoryid]);
    $elements = $categoryid > 0 ? $DB->count_records('local_course_banner_builder_elements', ['categoryid' => $categoryid]) : 0;
    $emit(['course' => (array)$course, 'temporaryCategoryExists' => $categoryExists, 'temporaryElements' => $elements]);
    exit(0);
}

throw new \moodle_exception('Unknown CCB fixture helper command.');
