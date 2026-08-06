<?php
/**
 * Add idempotent, readable CCB visual-review layers to the active course source.
 *
 * This is a supervised Moodle 5.1 fixture helper. It appends only layers whose
 * names are not already present; it never replaces or deletes existing layers.
 */
define('CLI_SCRIPT', true);

$configpath = getenv('EASYEDU_MOODLE_CONFIG');
if (!$configpath || !is_file($configpath)) {
    fwrite(STDERR, "EASYEDU_MOODLE_CONFIG must point to the Moodle config.php file.\n");
    exit(2);
}

require($configpath);
require_once($CFG->dirroot . '/course/lib.php');

global $DB, $USER;
$USER = get_admin();

$courseid = (int)($argv[1] ?? 11);
$requestedcategoryid = (int)($argv[2] ?? 0);
$course = $DB->get_record('course', ['id' => $courseid], 'id,category,fullname', MUST_EXIST);
$categoryid = $requestedcategoryid > 0 ? $requestedcategoryid : (int)$course->category;
$category = core_course_category::get($categoryid, MUST_EXIST);
$source = \local_course_banner_builder\manager::resolve_source(
    \local_course_banner_builder\manager::get_category_source_key($categoryid)
);
if (!$source) {
    throw new moodle_exception('Unable to resolve the course category source.');
}

$fixtures = [
    [
        'name' => 'CCB QA readable - blue base - 128px policy',
        'filename' => 'ccb-qa-readable-blue-base.svg',
        'svg' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 420" role="img" aria-labelledby="title desc">
  <title id="title">CCB QA readable blue base</title>
  <desc id="desc">Readable visual fixture for Course Banner Builder responsive review.</desc>
  <defs><linearGradient id="bg" x1="0" x2="1"><stop stop-color="#12385c"/><stop offset="1" stop-color="#167d91"/></linearGradient></defs>
  <rect width="1600" height="420" fill="url(#bg)"/>
  <path d="M0 320 L360 0 M220 420 L620 0 M900 420 L1300 0 M1230 420 L1600 90" stroke="#ffffff" stroke-opacity=".14" stroke-width="30"/>
  <text x="800" y="155" fill="#ffffff" font-family="Arial, sans-serif" font-size="68" font-weight="700" text-anchor="middle">CCB TEST • COURSE 11</text>
  <text x="800" y="235" fill="#e8fbff" font-family="Arial, sans-serif" font-size="42" text-anchor="middle">Readable banner fixture • minimum height 128px</text>
  <text x="800" y="320" fill="#ffd166" font-family="Arial, sans-serif" font-size="34" font-weight="700" text-anchor="middle">SOURCE CATEGORY 3 • BASE LAYER</text>
</svg>
SVG
    ],
    [
        'name' => 'CCB QA readable - alignment markers - 100 200',
        'filename' => 'ccb-qa-readable-alignment-markers.svg',
        'svg' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 420" role="img" aria-labelledby="title desc">
  <title id="title">CCB QA alignment markers</title>
  <desc id="desc">Transparent alignment markers for banner geometry review.</desc>
  <rect x="34" y="34" width="1532" height="352" rx="22" fill="none" stroke="#ffd166" stroke-width="8"/>
  <path d="M800 34 V386 M34 210 H1566" stroke="#ffd166" stroke-opacity=".8" stroke-width="4" stroke-dasharray="18 14"/>
  <circle cx="800" cy="210" r="22" fill="#ffd166" stroke="#12385c" stroke-width="8"/>
  <g fill="#ffffff" font-family="Arial, sans-serif" font-size="34" font-weight="700">
    <text x="72" y="92">LEFT</text><text x="1430" y="92">RIGHT</text>
    <text x="690" y="82">TOP</text><text x="690" y="365">BOTTOM</text>
  </g>
  <text x="800" y="184" fill="#ffffff" font-family="Arial, sans-serif" font-size="30" text-anchor="middle">CENTER / SAFE AREA</text>
  <text x="800" y="256" fill="#ffd166" font-family="Arial, sans-serif" font-size="30" font-weight="700" text-anchor="middle">ZOOM CHECK: 100% • 200%</text>
</svg>
SVG
    ],
    [
        'name' => 'CCB QA readable - contrast label - activity context',
        'filename' => 'ccb-qa-readable-contrast-label.svg',
        'svg' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 420" role="img" aria-labelledby="title desc">
  <title id="title">CCB QA contrast and context label</title>
  <desc id="desc">Readable contrast label for activity banner review.</desc>
  <rect x="250" y="112" width="1100" height="196" rx="34" fill="#071522" fill-opacity=".82" stroke="#ffffff" stroke-width="5"/>
  <text x="800" y="190" fill="#ffffff" font-family="Arial, sans-serif" font-size="54" font-weight="700" text-anchor="middle">H1 / H2 CONTRAST CHECK</text>
  <text x="800" y="260" fill="#b9f3ff" font-family="Arial, sans-serif" font-size="34" text-anchor="middle">Activity title stays semantic • image remains decorative</text>
</svg>
SVG
    ],
];

$existing = [];
foreach (\local_course_banner_builder\manager::get_source_elements($source) as $element) {
    $existing[(string)$element->name] = (int)$element->id;
}

$fs = get_file_storage();
$usercontext = context_user::instance($USER->id);
$added = [];
$already = [];
foreach ($fixtures as $fixture) {
    if (isset($existing[$fixture['name']])) {
        $already[] = ['name' => $fixture['name'], 'elementid' => $existing[$fixture['name']]];
        continue;
    }

    $draftitemid = file_get_unused_draft_itemid();
    $fs->create_file_from_string([
        'contextid' => $usercontext->id,
        'component' => 'user',
        'filearea' => 'draft',
        'itemid' => $draftitemid,
        'filepath' => '/',
        'filename' => $fixture['filename'],
    ], $fixture['svg']);

    $data = (object)[
        'categoryid' => $categoryid,
        'sourcekey' => $source->sourcekey,
        'elementid' => 0,
        'name' => $fixture['name'],
        'isenabled' => 1,
        'sortorder' => 9999,
        'fitmodeoverride' => '',
        'positionanchor' => 'center',
        'offsettoppercent' => 0,
        'offsetrightpercent' => 0,
        'offsetbottompercent' => 0,
        'offsetleftpercent' => 0,
        'customwidthpercent' => 100,
        'customheightpercent' => 100,
        'customsizekeepaspect' => 1,
        'dynamicimagesizeenabled' => 0,
        'imagecenterfixed' => 0,
        'imageaboveoverlayenabled' => 1,
        'imageopacity' => 1,
        'imagecropenabled' => 0,
        'bannerimage_filemanager' => $draftitemid,
        'multilayerdraftsettings' => '',
    ];
    $elementid = \local_course_banner_builder\manager::save_source_banner($source, $data);
    $added[] = ['name' => $fixture['name'], 'elementid' => (int)$elementid, 'filename' => $fixture['filename']];
    $existing[$fixture['name']] = (int)$elementid;
}

echo json_encode([
    'courseid' => $courseid,
    'courseCategoryId' => $categoryid,
    'courseName' => $course->fullname,
    'sourcekey' => $source->sourcekey,
    'added' => $added,
    'alreadyPresent' => $already,
    'totalSourceElements' => count(\local_course_banner_builder\manager::get_source_elements($source)),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
