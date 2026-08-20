<?php
/**
 * Disposable CCB layer-row visual validation fixture.
 *
 * Creates an isolated category source containing two reorderable image layers
 * and one dynamic locked layer. Cleanup removes the complete category source.
 */
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');

global $DB, $USER;
$USER = get_admin();

$command = $argv[1] ?? '';
$manifestpath = $argv[2] ?? '';

$emit = static function(array $value): void {
    echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
};

$createimage = static function(\stdClass $source, int $categoryid, string $name, string $colour,
        int $sortorder, bool $dynamic = false, bool $aboveoverlay = false): int {
    global $USER;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 300" role="img">' .
        '<rect width="1200" height="300" fill="' . s($colour) . '"/>' .
        '<text x="600" y="170" fill="#ffffff" font-family="Arial, sans-serif" font-size="58" ' .
        'font-weight="700" text-anchor="middle">' . s($name) . '</text></svg>';
    $draftitemid = file_get_unused_draft_itemid();
    get_file_storage()->create_file_from_string([
        'contextid' => context_user::instance($USER->id)->id,
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $draftitemid,
        'filepath' => '/',
        'filename' => 'ccb-layer-row-' . $sortorder . '.svg',
    ], $svg);
    $data = (object)[
        'categoryid' => $categoryid,
        'sourcekey' => $source->sourcekey,
        'elementid' => 0,
        'name' => $name,
        'isenabled' => 1,
        'sortorder' => $sortorder,
        // Keep one ordinary layer on a non-default fit mode so the layer-list
        // contextual help control is rendered and can be validated.
        'fitmodeoverride' => $sortorder === 0 ? 'cover' : '',
        'positionanchor' => 'center',
        'offsettoppercent' => 0,
        'offsetrightpercent' => 0,
        'offsetbottompercent' => 0,
        'offsetleftpercent' => 0,
        'customwidthpercent' => 100,
        'customheightpercent' => 100,
        'customsizekeepaspect' => 1,
        'dynamicimagesizeenabled' => $dynamic ? 1 : 0,
        'imagecenterfixed' => 0,
        'imageaboveoverlayenabled' => $aboveoverlay ? 1 : 0,
        'imagecropenabled' => 0,
        'imageopacity' => 1,
        'bannerimage_filemanager' => $draftitemid,
        'multilayerdraftsettings' => '',
    ];
    return (int)\local_course_banner_builder\manager::save_source_banner($source, $data);
};

$createborder = static function(\stdClass $source, int $categoryid): int {
    $data = (object)[
        'categoryid' => $categoryid,
        'sourcekey' => $source->sourcekey,
        'elementid' => 0,
        'name' => 'CCB QA row border',
        'isenabled' => 1,
        'borderenabled' => 1,
        'bordercolor' => '#176B87',
        'borderwidth' => 2.5,
        'borderopacity' => 100,
        'borderfade' => 0,
        'borderstyle' => 'solid',
        'borderdashlength' => 24,
        'bordersidesvalue' => 'top,right,bottom,left',
        'borderinnerrounded' => 1,
    ];
    return (int)\local_course_banner_builder\manager::save_source_banner($source, $data);
};

$createoverlay = static function(\stdClass $source, int $categoryid): int {
    $data = (object)[
        'categoryid' => $categoryid,
        'sourcekey' => $source->sourcekey,
        'elementid' => 0,
        'name' => 'CCB QA row overlay',
        'isenabled' => 1,
        'overlayenabled' => 1,
        'overlaytarget' => 'both',
        'overlaybannercolor' => '#1D3557',
        'overlaybanneropacity' => 25,
        'overlayslideshowcolor' => '#1D3557',
        'overlayslideshowopacity' => 25,
        'overlaytitleabove' => 1,
        'overlayborderabove' => 1,
    ];
    return (int)\local_course_banner_builder\manager::save_source_banner($source, $data);
};

if ($command === 'setup') {
    $category = null;
    try {
        $category = core_course_category::create((object)[
            'name' => 'CCB QA object-row cells ' . gmdate('YmdHis'),
            'parent' => 0,
            'idnumber' => '',
            'description' => 'Disposable CCB object-row-cells validation fixture.',
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
            throw new moodle_exception('Unable to resolve disposable layer-row source.');
        }
        $first = $createimage($source, $categoryid, 'CCB QA row one', '#176b87', 0);
        $second = $createimage($source, $categoryid, 'CCB QA row two', '#7d3c98', 1);
        $locked = $createimage($source, $categoryid, 'CCB QA row locked dynamic', '#9a5b13', 2, true, true);
        $border = $createborder($source, $categoryid);
        $overlay = $createoverlay($source, $categoryid);
        $emit([
            'categoryid' => $categoryid,
            'sourcekey' => $source->sourcekey,
            'reorderableIds' => [$first, $second],
            'lockedId' => $locked,
            'borderId' => $border,
            'overlayId' => $overlay,
        ]);
    } catch (\Throwable $exception) {
        if ($category) {
            try {
                \local_course_banner_builder\manager::delete_category_content((int)$category->id, true);
                $category->delete_full(false);
            } catch (\Throwable $cleanupException) {
                debugging('CCB object-row fixture setup cleanup failed: ' . $cleanupException->getMessage(), DEBUG_DEVELOPER);
            }
        }
        throw $exception;
    }
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new moodle_exception('Missing CCB object-row fixture manifest.');
    }
    $manifestjson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath));
    $manifest = json_decode($manifestjson, true, 512, JSON_THROW_ON_ERROR);
    $categoryid = (int)($manifest['categoryid'] ?? 0);
    if ($categoryid < 1 || !$DB->record_exists('course_categories', ['id' => $categoryid])) {
        throw new moodle_exception('Disposable CCB object-row category is missing.');
    }
    $courses = $DB->count_records('course', ['category' => $categoryid]);
    if ($courses !== 0) {
        throw new moodle_exception('Refusing to remove a disposable category containing courses.');
    }
    \local_course_banner_builder\manager::delete_category_content($categoryid, true);
    core_course_category::get($categoryid, MUST_EXIST)->delete_full(false);
    $exists = $DB->record_exists('course_categories', ['id' => $categoryid]);
    $elements = $DB->count_records('local_course_banner_builder_elements', ['categoryid' => $categoryid]);
    if ($exists || $elements !== 0) {
        throw new moodle_exception('CCB object-row fixture cleanup verification failed.');
    }
    $emit(['categoryid' => $categoryid, 'categoryRemoved' => true, 'remainingElements' => 0]);
    exit(0);
}

throw new moodle_exception('Unknown CCB object-row fixture command.');
