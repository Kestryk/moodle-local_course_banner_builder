<?php
/**
 * Disposable fixture for the EED-CCB-2026-0042-0050 cumulative wave.
 *
 * It creates only a three-source category chain. The descendant receives two
 * ordinary image layers and one dynamic layer so the cumulative scenario owns
 * its source tree, IMG-08 edit target and source-preview data. Cleanup removes
 * every category, CCB element and draft file recorded in its manifest.
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
$prefix = 'CCB QA wave 0042-0050 ';
$emit = static function(array $value): void {
    echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
};

$removefixture = static function(array $manifest, bool $strict = true): array {
    global $DB, $USER;
    $categoryids = $manifest['categoryids'] ?? [];
    $draftitemids = $manifest['draftitemids'] ?? [];
    if (!is_array($categoryids) || !is_array($draftitemids) || ($strict &&
            (count(array_unique(array_map('intval', $categoryids))) !== 3 ||
            count(array_unique(array_map('intval', $draftitemids))) !== 3))) {
        throw new moodle_exception('The cumulative wave fixture manifest does not contain exactly three categories and drafts.');
    }
    $categoryids = array_values(array_unique(array_map('intval', $categoryids)));
    $draftitemids = array_values(array_unique(array_map('intval', $draftitemids)));
    if ($strict && ((int)($manifest['categoryid'] ?? 0) !== (int)$categoryids[2] ||
            (string)($manifest['sourcekey'] ?? '') !== 'category:' . $categoryids[2])) {
        throw new moodle_exception('The cumulative wave fixture manifest target is not the descendant source.');
    }
    foreach ($draftitemids as $draftitemid) {
        $draftitemid = (int)$draftitemid;
        if ($draftitemid > 0) {
            get_file_storage()->delete_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $draftitemid);
        }
    }
    $remaining = 0;
    foreach (array_reverse($categoryids) as $categoryid) {
        $categoryid = (int)$categoryid;
        if ($categoryid < 1 || !$DB->record_exists('course_categories', ['id' => $categoryid])) {
            continue;
        }
        $record = $DB->get_record('course_categories', ['id' => $categoryid], 'id,name', MUST_EXIST);
        if (!str_starts_with((string)$record->name, $GLOBALS['prefix'])) {
            throw new moodle_exception('Refusing to remove a category not owned by the cumulative wave.');
        }
        if ($DB->count_records('course', ['category' => $categoryid]) !== 0) {
            throw new moodle_exception('Refusing to remove a disposable category containing courses.');
        }
        \local_course_banner_builder\manager::delete_category_content($categoryid, true);
        core_course_category::get($categoryid, MUST_EXIST)->delete_full(false);
        if ($DB->record_exists('course_categories', ['id' => $categoryid])) {
            $remaining++;
        }
    }
    $elementcount = 0;
    foreach ($categoryids as $categoryid) {
        $elementcount += $DB->count_records('local_course_banner_builder_elements', ['categoryid' => (int)$categoryid]);
    }
    $draftremaining = 0;
    $usercontextid = context_user::instance($USER->id)->id;
    foreach ($draftitemids as $draftitemid) {
        $draftremaining += count(get_file_storage()->get_area_files($usercontextid, 'user', 'draft', (int)$draftitemid));
    }
    if ($remaining !== 0 || $elementcount !== 0 || $draftremaining !== 0) {
        throw new moodle_exception('Cumulative wave fixture cleanup verification failed.');
    }
    return ['categoriesRemoved' => true, 'remainingCategories' => 0, 'remainingElements' => 0, 'draftsRemoved' => $draftremaining === 0];
};

$createimage = static function(\stdClass $source, int $categoryid, string $name, string $colour,
        int $sortorder, bool $dynamic, array &$draftitemids): int {
    global $USER;
    $draftitemid = file_get_unused_draft_itemid();
    $draftitemids[] = $draftitemid;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 300" role="img">' .
        '<rect width="1200" height="300" fill="' . s($colour) . '"/>' .
        '<text x="600" y="170" fill="#ffffff" font-family="Arial, sans-serif" font-size="58" font-weight="700" text-anchor="middle">' .
        s($name) . '</text></svg>';
    get_file_storage()->create_file_from_string([
        'contextid' => context_user::instance($USER->id)->id,
        'component' => 'user', 'filearea' => 'draft', 'itemid' => $draftitemid,
        'filepath' => '/', 'filename' => 'ccb-wave-' . $sortorder . '.svg',
    ], $svg);
    return (int)\local_course_banner_builder\manager::save_source_banner($source, (object)[
        'categoryid' => $categoryid, 'sourcekey' => $source->sourcekey, 'elementid' => 0,
        'name' => $name, 'isenabled' => 1, 'sortorder' => $sortorder,
        'fitmodeoverride' => $sortorder === 0 ? 'cover' : '', 'positionanchor' => 'center',
        'offsettoppercent' => 0, 'offsetrightpercent' => 0, 'offsetbottompercent' => 0, 'offsetleftpercent' => 0,
        'customwidthpercent' => 100, 'customheightpercent' => 100, 'customsizekeepaspect' => 1,
        'dynamicimagesizeenabled' => $dynamic ? 1 : 0, 'imagecenterfixed' => 0,
        'imageaboveoverlayenabled' => $dynamic ? 1 : 0, 'imagecropenabled' => 0, 'imageopacity' => 100,
        'bannerimage_filemanager' => $draftitemid, 'multilayerdraftsettings' => '',
    ]);
};

if ($command === 'setup') {
    $manifest = ['categoryids' => [], 'draftitemids' => []];
    try {
        $suffix = gmdate('YmdHis');
        foreach (['Root source', 'Child source', 'Descendant source'] as $label) {
            $category = core_course_category::create((object)[
                'name' => $prefix . $label . ' ' . $suffix, 'parent' => 0, 'idnumber' => '',
                'description' => 'Disposable cumulative CCB wave fixture.', 'descriptionformat' => FORMAT_HTML, 'visible' => 1,
            ]);
            $manifest['categoryids'][] = (int)$category->id;
            \local_course_banner_builder\manager::save_category_settings((int)$category->id,
                \local_course_banner_builder\manager::MODE_CUMULATIVE,
                \local_course_banner_builder\manager::FIT_MODE_ORIGINAL,
                \local_course_banner_builder\manager::FIT_SCOPE_SELF);
        }
        [$rootid, $childid, $descendantid] = $manifest['categoryids'];
        $sources = array_map(static fn(int $id) => \local_course_banner_builder\manager::resolve_source(
            \local_course_banner_builder\manager::get_category_source_key($id)), [$rootid, $childid, $descendantid]);
        if (in_array(null, $sources, true)) {
            throw new moodle_exception('Unable to resolve the cumulative wave source chain.');
        }
        [$root, $child, $descendant] = $sources;
        if (!\local_course_banner_builder\manager::update_source_setting_field($child, 'sourceparentkey', (string)$root->sourcekey) ||
                !\local_course_banner_builder\manager::update_source_setting_field($descendant, 'sourceparentkey', (string)$child->sourcekey)) {
            throw new moodle_exception('Unable to persist the cumulative wave source chain.');
        }
        $first = $createimage($descendant, $descendantid, 'CCB QA wave image one', '#176B87', 0, false, $manifest['draftitemids']);
        $second = $createimage($descendant, $descendantid, 'CCB QA wave image two', '#7D3C98', 1, false, $manifest['draftitemids']);
        $locked = $createimage($descendant, $descendantid, 'CCB QA wave dynamic image', '#9A5B13', 2, true, $manifest['draftitemids']);
        $emit([
            'categoryid' => $descendantid, 'categoryids' => $manifest['categoryids'],
            'sourcekey' => (string)$descendant->sourcekey, 'rootkey' => (string)$root->sourcekey,
            'childkey' => (string)$child->sourcekey, 'reorderableIds' => [$first, $second], 'lockedId' => $locked,
            'draftitemids' => $manifest['draftitemids'],
        ]);
    } catch (Throwable $exception) {
        try { $removefixture($manifest, false); } catch (Throwable $cleanupException) { debugging($cleanupException->getMessage(), DEBUG_DEVELOPER); }
        throw $exception;
    }
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new moodle_exception('Missing cumulative wave fixture manifest.');
    }
    $manifest = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath)), true, 512, JSON_THROW_ON_ERROR);
    $emit($removefixture($manifest));
    exit(0);
}

throw new moodle_exception('Unknown cumulative wave fixture command.');
