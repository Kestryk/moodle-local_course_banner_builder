<?php
/**
 * Disposable three-source chain for the parent-source modal scenario.
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

$removefixture = static function(array $categoryids): array {
    global $DB;
    $remaining = 0;
    foreach (array_reverse($categoryids) as $categoryid) {
        $categoryid = (int)$categoryid;
        if ($categoryid < 1 || !$DB->record_exists('course_categories', ['id' => $categoryid])) {
            continue;
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
    return ['categoriesRemoved' => $remaining === 0, 'remainingCategories' => $remaining];
};

if ($command === 'setup') {
    $categoryids = [];
    try {
        $suffix = gmdate('YmdHis');
        foreach (['Valid parent', 'Child source', 'Descendant source'] as $label) {
            $category = core_course_category::create((object)[
                'name' => 'CCB QA parent modal ' . $label . ' ' . $suffix,
                'parent' => 0,
                'idnumber' => '',
                'description' => 'Disposable CCB parent-source modal fixture.',
                'descriptionformat' => FORMAT_HTML,
                'visible' => 1,
            ]);
            $categoryids[] = (int)$category->id;
            \local_course_banner_builder\manager::save_category_settings(
                (int)$category->id,
                \local_course_banner_builder\manager::MODE_CUMULATIVE,
                \local_course_banner_builder\manager::FIT_MODE_ORIGINAL,
                \local_course_banner_builder\manager::FIT_SCOPE_SELF
            );
        }

        [$validid, $childid, $descendantid] = $categoryids;
        $valid = \local_course_banner_builder\manager::resolve_source(
            \local_course_banner_builder\manager::get_category_source_key($validid)
        );
        $child = \local_course_banner_builder\manager::resolve_source(
            \local_course_banner_builder\manager::get_category_source_key($childid)
        );
        $descendant = \local_course_banner_builder\manager::resolve_source(
            \local_course_banner_builder\manager::get_category_source_key($descendantid)
        );
        if (!$valid || !$child || !$descendant) {
            throw new moodle_exception('Unable to resolve the disposable parent-source chain.');
        }

        \local_course_banner_builder\manager::update_source_setting_field(
            $child,
            'sourceparentkey',
            (string)$valid->sourcekey
        );
        \local_course_banner_builder\manager::update_source_setting_field(
            $descendant,
            'sourceparentkey',
            (string)$child->sourcekey
        );

        $emit([
            'categoryids' => $categoryids,
            'childKey' => (string)$child->sourcekey,
            'validKey' => (string)$valid->sourcekey,
            'descendantKey' => (string)$descendant->sourcekey,
        ]);
    } catch (Throwable $exception) {
        try {
            $removefixture($categoryids);
        } catch (Throwable $cleanupexception) {
            debugging('CCB parent modal fixture cleanup failed: ' . $cleanupexception->getMessage(), DEBUG_DEVELOPER);
        }
        throw $exception;
    }
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new moodle_exception('Missing CCB parent-source fixture manifest.');
    }
    $manifestjson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath));
    $manifest = json_decode($manifestjson, true, 512, JSON_THROW_ON_ERROR);
    $emit($removefixture($manifest['categoryids'] ?? []));
    exit(0);
}

throw new moodle_exception('Unknown CCB parent-source fixture command.');
