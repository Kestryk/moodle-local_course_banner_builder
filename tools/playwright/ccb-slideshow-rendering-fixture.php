<?php
/**
 * Disposable configuration fixture for the CCB Slideshow rendering audit.
 *
 * The fixture owns a temporary course and only the plugin configuration keys
 * prefixed with slideshow_course_ or slideshow_site_. Its cleanup restores the
 * exact pre-run records before removing the temporary course.
 *
 * @package local_course_banner_builder
 */

define('CLI_SCRIPT', true);

$moodleroot = getenv('EASYEDU_MOODLE_ROOT');
if ($moodleroot === false || $moodleroot === '' || !is_file($moodleroot . '/config.php')) {
    throw new RuntimeException('EASYEDU_MOODLE_ROOT must identify a Moodle root containing config.php.');
}

require($moodleroot . '/config.php');
require_once($moodleroot . '/course/lib.php');

global $DB, $USER;
$USER = get_admin();

$command = $argv[1] ?? '';
$manifestpath = $argv[2] ?? '';
$plugin = 'local_course_banner_builder';

/**
 * Emit one machine-readable result without exposing credentials.
 *
 * @param array $value Result payload.
 */
function local_course_banner_builder_slideshow_fixture_emit(array $value): void {
    echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
}

/**
 * Capture only the Slideshow configuration records that this fixture may alter.
 *
 * @return array<int, array{name:string,value:string}>
 */
function local_course_banner_builder_slideshow_fixture_snapshot(): array {
    global $DB;

    $records = $DB->get_records_select(
        'config_plugins',
        'plugin = :plugin AND (name LIKE :courseprefix OR name LIKE :siteprefix)',
        [
            'plugin' => 'local_course_banner_builder',
            'courseprefix' => 'slideshow_course_%',
            'siteprefix' => 'slideshow_site_%',
        ],
        '',
        'id, name, value'
    );

    return array_values(array_map(static fn(stdClass $record): array => [
        'name' => (string)$record->name,
        'value' => (string)$record->value,
    ], $records));
}

/**
 * Restore precisely the configuration captured before setup.
 *
 * @param array<int, array{name:string,value:string}> $snapshot Prior records.
 */
function local_course_banner_builder_slideshow_fixture_restore(array $snapshot): void {
    global $DB;

    $expected = [];
    foreach ($snapshot as $record) {
        $expected[$record['name']] = $record['value'];
    }
    $current = local_course_banner_builder_slideshow_fixture_snapshot();
    foreach ($current as $record) {
        if (!array_key_exists($record['name'], $expected)) {
            unset_config($record['name'], 'local_course_banner_builder');
        }
    }
    foreach ($expected as $name => $value) {
        set_config($name, $value, 'local_course_banner_builder');
    }
}

/**
 * Return an intentionally contrasting but valid Slideshow configuration.
 *
 * @param string $context Slideshow context.
 * @return array<string, mixed>
 */
function local_course_banner_builder_slideshow_fixture_profile(string $context): array {
    $site = $context === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE;
    return [
        'enabled' => 1,
        'forums' => 1,
        'siteannouncements' => $site ? 1 : 0,
        'assignments' => 1,
        'quizzes' => 1,
        'autoplay' => 0,
        'delay' => 7000,
        'maxslides' => 15,
        'siteannouncementdays' => 60,
        'arrows' => 1,
        'dots' => 1,
        'overlaycolor' => $site ? '#16324F' : '#4B1D3F',
        'overlayopacity' => 45,
        'titlefontsize' => 75,
        'bodyfontsize' => 60,
        'bodylineheight' => 130,
        'actionsize' => 72,
        'actionwidth' => 58,
        'actionheight' => 86,
        'actioncorners' => 'rounded',
        'labelsize' => 88,
        'labeltextsize' => 105,
        'labelorientation' => 'row',
        'labelcorners' => 'rounded',
        'titlecolor' => '#FFFFFF',
        'bodycolor' => '#FFF4E6',
        'titlealign' => 'center',
        'bodyalign' => 'center',
        'labelalign' => 'left',
        'titlebold' => 1,
        'labelbold' => 1,
        'labelallcaps' => 1,
        'titlex' => 50,
        'titley' => 25,
        'bodyx' => 50,
        'bodyy' => 54,
        'actionx' => 50,
        'actiony' => 84,
        'labelx' => 14,
        'labely' => 10,
    ];
}

if ($command === 'setup') {
    $snapshot = local_course_banner_builder_slideshow_fixture_snapshot();
    $courseid = 0;
    try {
        $course = create_course((object)[
            'fullname' => 'CCB QA Slideshow ' . gmdate('YmdHis'),
            'shortname' => 'CCB-QA-SLIDESHOW-' . gmdate('His'),
            'category' => core_course_category::get_default()->id,
            'visible' => 0,
            'summary' => 'Disposable Course Banner Builder Slideshow validation fixture.',
            'summaryformat' => FORMAT_PLAIN,
        ]);
        $courseid = (int)$course->id;
        foreach ([
            \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_COURSE,
            \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE,
        ] as $context) {
            \local_course_banner_builder\manager::set_slideshow_config(
                $context,
                local_course_banner_builder_slideshow_fixture_profile($context)
            );
        }
        local_course_banner_builder_slideshow_fixture_emit([
            'courseid' => $courseid,
            'snapshot' => $snapshot,
            'courseMaxSlides' => 15,
            'siteAnnouncementDays' => 60,
        ]);
    } catch (Throwable $exception) {
        local_course_banner_builder_slideshow_fixture_restore($snapshot);
        if ($courseid > 0 && $DB->record_exists('course', ['id' => $courseid])) {
            delete_course($courseid, false);
        }
        throw $exception;
    }
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new RuntimeException('Missing Slideshow fixture manifest.');
    }
    $manifestjson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath));
    $manifest = json_decode($manifestjson, true, 512, JSON_THROW_ON_ERROR);
    $courseid = (int)($manifest['courseid'] ?? 0);
    $snapshot = $manifest['snapshot'] ?? null;
    if ($courseid < 1 || !is_array($snapshot)) {
        throw new RuntimeException('Invalid Slideshow fixture manifest.');
    }
    local_course_banner_builder_slideshow_fixture_restore($snapshot);
    if ($DB->record_exists('course', ['id' => $courseid])) {
        delete_course($courseid, false);
    }
    local_course_banner_builder_slideshow_fixture_emit([
        'courseid' => $courseid,
        'courseRemoved' => !$DB->record_exists('course', ['id' => $courseid]),
        'slideshowConfigRestored' => local_course_banner_builder_slideshow_fixture_snapshot() === $snapshot,
    ]);
    exit(0);
}

throw new RuntimeException('Unknown Slideshow fixture command.');
