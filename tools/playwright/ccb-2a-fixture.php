<?php
/**
 * CCB Batch 2A geometry fixture authority helper.
 *
 * All fixture writes use Moodle APIs or the CCB manager. Course 11 is moved to
 * the disposable category for the public/admin comparison and is restored to
 * its captured authoritative category during cleanup.
 */
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB, $USER;
// Moodle category/manager APIs dispatch events through the current user. A
// CLI bootstrap has no authenticated session, so use the local administrator
// identity explicitly for the bounded fixture transaction.
$USER = get_admin();

$command = $argv[1] ?? '';
$manifestpath = $argv[2] ?? '';
$categoryid = (int)($argv[3] ?? 0);
$courseid = 11;
$stablecategoryid = 3;
$allowedformats = [
    'standard', 'contentwide', 'fullwidthtop', 'fullwidthtopcompact', 'fullwidthtopinset',
];

$titlefields = [
    'enabled', 'x', 'y', 'fontsize', 'lineheight', 'fontfamily', 'color', 'align',
    'bold', 'italic', 'underline', 'strike', 'allcaps', 'aboveborder', 'aboveoverlay',
    'replacemoodletitle', 'frameenabled', 'frameshadowenabled', 'shadowenabled',
    'overlayenabled', 'frametype', 'stylemode', 'framecolor', 'framebordercolor',
    'frameshadowcolor', 'shadowcolor', 'frameopacity', 'frameborderwidth', 'frameradius',
    'framepadding', 'frameshadowopacity', 'frameshadowblur', 'frameshadowdistance',
    'frameshadowdirection', 'shadowopacity', 'shadowblur', 'shadowdistance',
    'shadowdirection', 'overlaycolor', 'overlayopacity', 'activitytitlemode',
];

$gettitleconfig = static function(string $context) use ($DB, $titlefields): array {
    $result = [];
    foreach ($titlefields as $field) {
        $name = 'bannertitle_' . $context . '_' . $field;
        $value = $DB->get_field('config_plugins', 'value', [
            'plugin' => 'local_course_banner_builder', 'name' => $name,
        ], IGNORE_MISSING);
        if ($value !== false && $value !== null) {
            $result[$field] = $value;
        }
    }
    return $result;
};

$snapshot = static function() use ($DB, $gettitleconfig, $courseid, $stablecategoryid): array {
    $course = $DB->get_record('course', ['id' => $courseid],
        'id,category,fullname,format,showgrades', MUST_EXIST);
    $cm = $DB->get_record('course_modules', ['id' => 12, 'course' => $courseid],
        'id,course', MUST_EXIST);
    // Keep the restoration snapshot independent from request/user state. The
    // CLI runner may execute while an authenticated browser session is active;
    // direct DB reads are sufficient for these immutable authorities and avoid
    // manager helpers that resolve the current Moodle user.
    $sourceSettings = $DB->get_record('local_course_banner_builder_order',
        ['categoryid' => $stablecategoryid], '*', IGNORE_MISSING);
    $elements = $DB->count_records('local_course_banner_builder_elements', ['categoryid' => $stablecategoryid]);
    return [
        'course' => (array)$course,
        'stableCategoryId' => $stablecategoryid,
        'activityCmid' => (int)$cm->id,
        'courseTitle' => $gettitleconfig('course'),
        'activityTitle' => $gettitleconfig('activity'),
        'coursebanneractivitiesenabled' => (int)(bool)$DB->get_field('config_plugins', 'value', [
            'plugin' => 'local_course_banner_builder', 'name' => 'coursebanneractivitiesenabled',
        ], IGNORE_MISSING),
        'coursebannerenabled' => (int)(bool)$DB->get_field('config_plugins', 'value', [
            'plugin' => 'local_course_banner_builder', 'name' => 'coursebannerenabled',
        ], IGNORE_MISSING),
        'coursebannerformat' => \local_course_banner_builder\manager::get_course_banner_format(),
        'stableSourceSettings' => $sourceSettings ? (array)$sourceSettings : [],
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
    $name = 'CCB QA disposable geometry 2A1 ' . gmdate('YmdHis');
    $category = null;
    try {
        $category = core_course_category::create((object)[
            'name' => $name,
            'parent' => 0,
            'idnumber' => '',
            'description' => 'Temporary CCB Batch 2A geometry fixture. Safe to remove after the run.',
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
        $temporarysource = \local_course_banner_builder\manager::resolve_source(
            \local_course_banner_builder\manager::get_category_source_key($categoryid)
        );
        $parentsource = \local_course_banner_builder\manager::resolve_source(
            \local_course_banner_builder\manager::get_category_source_key($stablecategoryid)
        );
        if (!$temporarysource || !$parentsource) {
            throw new \moodle_exception('Unable to resolve the temporary or stable CCB source.');
        }
        \local_course_banner_builder\manager::save_source_settings(
            $temporarysource,
            \local_course_banner_builder\manager::MODE_CUMULATIVE,
            \local_course_banner_builder\manager::FIT_MODE_ORIGINAL,
            \local_course_banner_builder\manager::FIT_SCOPE_SELF,
            \local_course_banner_builder\manager::CUSTOMFIELD_PRIORITY_CATEGORY,
            $parentsource->sourcekey
        );
        // The stable source already contains the approved border/image stack.
        // Inheriting it avoids creating a duplicate border in the source chain.
        $elementid = 0;
        move_courses([$courseid], $categoryid);
        $emit([
            'categoryid' => $categoryid,
            'elementid' => (int)$elementid,
            'name' => $name,
            'courseCategory' => $categoryid,
            'sourceCategoryId' => $categoryid,
        ]);
    } catch (\Throwable $exception) {
        if ($category) {
            try {
                $course = $DB->get_record('course', ['id' => $courseid], 'id,category');
                if ($course && (int)$course->category === (int)$category->id) {
                    move_courses([$courseid], $stablecategoryid);
                }
                \local_course_banner_builder\manager::delete_category_content((int)$category->id, true);
                $category->delete_full(false);
            } catch (\Throwable $cleanupException) {
                debugging('CCB 2A setup cleanup failed: ' . $cleanupException->getMessage(), DEBUG_DEVELOPER);
            }
        }
        throw $exception;
    }
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new \moodle_exception('Missing CCB 2A restoration manifest.');
    }
    $manifestjson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath));
    $manifest = json_decode($manifestjson, true, 512, JSON_THROW_ON_ERROR);
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $capturedCourse = (object)$manifest['course'];
    if ((int)$course->category !== $stablecategoryid) {
        if ((int)$course->category !== (int)($manifest['temporary']['categoryid'] ?? 0)) {
            throw new \moodle_exception('Stable fixture is in an unexpected category.');
        }
        move_courses([$courseid], $stablecategoryid);
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        rebuild_course_cache($courseid, true);
    }
    $course->fullname = $capturedCourse->fullname;
    $course->format = $capturedCourse->format;
    $course->showgrades = $capturedCourse->showgrades;
    update_course($course);

    foreach (['course', 'activity'] as $context) {
        foreach (($manifest[$context . 'Title'] ?? []) as $field => $value) {
            set_config('bannertitle_' . $context . '_' . $field, $value, 'local_course_banner_builder');
        }
    }
    set_config('coursebanneractivitiesenabled', (int)$manifest['coursebanneractivitiesenabled'],
        'local_course_banner_builder');
    set_config('coursebannerenabled', (int)$manifest['coursebannerenabled'],
        'local_course_banner_builder');
    \local_course_banner_builder\manager::set_course_banner_format((string)$manifest['coursebannerformat']);

    $temporaryid = (int)($manifest['temporary']['categoryid'] ?? $categoryid);
    $removed = false;
    if ($temporaryid > 0 && $DB->record_exists('course_categories', ['id' => $temporaryid])) {
        $temporarycourses = $DB->get_records('course', ['category' => $temporaryid], 'id ASC', 'id');
        if (count($temporarycourses) !== 0) {
            $unexpectedcourses = array_diff(array_map('intval', array_keys($temporarycourses)), [$courseid]);
            if ($unexpectedcourses) {
                throw new \moodle_exception('Temporary geometry category contains an unexpected course.');
            }
            move_courses([$courseid], $stablecategoryid);
            rebuild_course_cache($courseid, true);
        }
        $temporarycourses = $DB->get_records('course', ['category' => $temporaryid], 'id ASC', 'id');
        if (count($temporarycourses) !== 0) {
            throw new \moodle_exception('Temporary geometry category still contains courses.');
        }
        $temporary = core_course_category::get($temporaryid, MUST_EXIST);
        \local_course_banner_builder\manager::delete_category_content($temporaryid, true);
        $temporary->delete_full(false);
        $removed = true;
    }
    $exists = $DB->record_exists('course_categories', ['id' => $temporaryid]);
    $elements = $temporaryid > 0 ? $DB->count_records(
        'local_course_banner_builder_elements', ['categoryid' => $temporaryid]
    ) : 0;
    $finalcourse = $DB->get_record('course', ['id' => $courseid],
        'id,category,fullname,format,showgrades', MUST_EXIST);
    $finalbannerformat = \local_course_banner_builder\manager::get_course_banner_format();
    if ((int)$finalcourse->category !== $stablecategoryid ||
            $finalcourse->fullname !== $capturedCourse->fullname || $exists || $elements !== 0 ||
            $finalbannerformat !== (string)$manifest['coursebannerformat']) {
        throw new \moodle_exception('Independent geometry fixture cleanup verification failed.');
    }
    $emit([
        'courseid' => $courseid,
        'courseCategory' => (int)$finalcourse->category,
        'courseRestored' => true,
        'settingsRestored' => true,
        'courseBannerFormatRestored' => true,
        'temporaryCategoryRemoved' => !$exists,
        'relatedElements' => $elements,
        'removedDuringThisCall' => $removed,
    ]);
    exit(0);
}

if ($command === 'set-format') {
    $format = (string)($argv[2] ?? '');
    if (!in_array($format, $allowedformats, true)) {
        throw new \moodle_exception('Invalid CCB geometry format.');
    }
    \local_course_banner_builder\manager::set_course_banner_format($format);
    $emit(['coursebannerformat' => \local_course_banner_builder\manager::get_course_banner_format()]);
    exit(0);
}

if ($command === 'verify') {
    $course = $DB->get_record('course', ['id' => $courseid], 'id,category,fullname', MUST_EXIST);
    $categoryExists = $categoryid > 0 && $DB->record_exists('course_categories', ['id' => $categoryid]);
    $elements = $categoryid > 0 ? $DB->count_records(
        'local_course_banner_builder_elements', ['categoryid' => $categoryid]
    ) : 0;
    $emit(['course' => (array)$course, 'temporaryCategoryExists' => $categoryExists,
        'temporaryElements' => $elements]);
    exit(0);
}

throw new \moodle_exception('Unknown CCB 2A fixture helper command.');
