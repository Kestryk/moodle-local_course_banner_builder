<?php
/**
 * Disposable two-image fixture for the CROP-08 transformation-history scenario.
 *
 * The element is created through the CCB manager, then receives a second
 * file in its owned file area.  Opening its edit form makes Moodle prepare one
 * temporary draft containing exactly these two existing images; CROP-08 never
 * adds or deletes a Filemanager file itself.
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

$prefix = 'CCB QA CROP-08 ';
$emit = static function(array $value): void {
    echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
};

$removefixture = static function(array $manifest) use ($prefix): array {
    global $DB, $USER;
    $draftitemids = array_unique(array_filter([
        (int)($manifest['draftitemid'] ?? 0),
        (int)($manifest['seedDraftItemId'] ?? 0),
    ]));
    foreach ($draftitemids as $draftitemid) {
        get_file_storage()->delete_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $draftitemid);
    }
    $categoryid = (int)($manifest['categoryid'] ?? 0);
    if ($categoryid > 0 && $DB->record_exists('course_categories', ['id' => $categoryid])) {
        $record = $DB->get_record('course_categories', ['id' => $categoryid], 'id,name', MUST_EXIST);
        if (!str_starts_with((string)$record->name, $prefix)) {
            throw new moodle_exception('Refusing to remove a category not owned by CROP-08.');
        }
        if ($DB->count_records('course', ['category' => $categoryid]) !== 0) {
            throw new moodle_exception('Refusing to remove a CROP-08 category containing courses.');
        }
        \local_course_banner_builder\manager::delete_category_content($categoryid, true);
        core_course_category::get($categoryid, MUST_EXIST)->delete_full(false);
    }
    $categoryexists = $categoryid > 0 && $DB->record_exists('course_categories', ['id' => $categoryid]);
    $remainingelements = $categoryid > 0 ?
        $DB->count_records('local_course_banner_builder_elements', ['categoryid' => $categoryid]) : 0;
    if ($categoryexists || $remainingelements !== 0) {
        throw new moodle_exception('CROP-08 fixture cleanup verification failed.');
    }
    return [
        'categoryRemoved' => true,
        'draftItemRemoved' => count($draftitemids) > 0,
        'remainingElements' => 0,
    ];
};

if ($command === 'setup') {
    $category = null;
    try {
        $category = core_course_category::create((object)[
            'name' => $prefix . gmdate('YmdHis'),
            'parent' => 0,
            'idnumber' => '',
            'description' => 'Disposable CCB CROP-08 transformation-history fixture.',
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
            throw new moodle_exception('Unable to resolve disposable CROP-08 source.');
        }

        $fs = get_file_storage();
        $usercontext = context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $firstsvg = '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900"><rect width="1600" height="900" fill="#155e75"/><circle cx="450" cy="450" r="300" fill="#facc15"/></svg>';
        $fs->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'crop-08-first.svg',
        ], $firstsvg);
        $data = (object)[
            'categoryid' => $categoryid,
            'sourcekey' => $source->sourcekey,
            'elementid' => 0,
            'name' => 'CCB QA CROP-08 two-image layer',
            'isenabled' => 1,
            'sortorder' => 0,
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
        $element = \local_course_banner_builder\manager::get_banner_element($elementid);
        if (!$element || (int)$element->fileitemid < 1) {
            throw new moodle_exception('Unable to create the CROP-08 image layer.');
        }
        $secondsvg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="1200"><rect width="1200" height="1200" fill="#7c3aed"/><path d="M150 1000 L600 160 L1050 1000Z" fill="#fef3c7"/></svg>';
        $fs->create_file_from_string([
            'contextid' => context_system::instance()->id,
            'component' => 'local_course_banner_builder',
            'filearea' => \local_course_banner_builder\manager::FILEAREA,
            'itemid' => (int)$element->fileitemid,
            'filepath' => '/',
            'filename' => 'crop-08-second.svg',
        ], $secondsvg);
        $files = $fs->get_area_files(
            context_system::instance()->id,
            'local_course_banner_builder',
            \local_course_banner_builder\manager::FILEAREA,
            (int)$element->fileitemid,
            'filename ASC',
            false
        );
        if (count($files) !== 2) {
            throw new moodle_exception('CROP-08 fixture must create exactly two element images.');
        }
        $emit([
            'categoryid' => $categoryid,
            'elementid' => (int)$elementid,
            'sourcekey' => (string)$source->sourcekey,
            'modalPath' => '/local/course_banner_builder/admin_manage.php?sourcekey=' . rawurlencode((string)$source->sourcekey),
            'filecount' => count($files),
            'seedDraftItemId' => $draftitemid,
        ]);
    } catch (Throwable $exception) {
        if ($category) {
            try {
                $removefixture(['categoryid' => (int)$category->id]);
            } catch (Throwable $cleanupexception) {
                debugging('CROP-08 fixture setup cleanup failed: ' . $cleanupexception->getMessage(), DEBUG_DEVELOPER);
            }
        }
        throw $exception;
    }
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new moodle_exception('Missing CROP-08 fixture manifest.');
    }
    $manifestjson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath));
    $manifest = json_decode($manifestjson, true, 512, JSON_THROW_ON_ERROR);
    $emit($removefixture($manifest));
    exit(0);
}

throw new moodle_exception('Unknown CROP-08 fixture command.');
