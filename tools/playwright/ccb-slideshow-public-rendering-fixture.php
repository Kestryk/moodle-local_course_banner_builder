<?php
/**
 * Disposable public-source fixture for the CCB Slideshow rendering audit.
 *
 * The fixture creates one hidden Moodle course with its native Announcements
 * forum, one real forum discussion and one manual Student enrolment for the
 * administrator used by the supervised test. It also enables only the CCB
 * configuration required for a native generated course banner and Course
 * Slideshow. Cleanup restores the exact captured configuration and deletes
 * the complete disposable course.
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
require_once($moodleroot . '/course/modlib.php');
require_once($moodleroot . '/lib/enrollib.php');
require_once($moodleroot . '/mod/forum/lib.php');
require_once($moodleroot . '/mod/assign/lib.php');

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
function local_course_banner_builder_slideshow_public_fixture_emit(array $value): void {
    echo json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), PHP_EOL;
}

/**
 * Capture exactly the plugin configuration records that this fixture changes.
 *
 * @return array<int, array{name:string,value:string}>
 */
function local_course_banner_builder_slideshow_public_fixture_snapshot_config(): array {
    global $DB;

    $names = [
        'enabled',
        'coursebannerenabled',
        'coursebannerdefaultimageenabled',
    ];
    [$insql, $params] = $DB->get_in_or_equal($names, SQL_PARAMS_NAMED, 'fixtureconfig');
    $params['plugin'] = 'local_course_banner_builder';
    $params['courseprefix'] = 'slideshow_course_%';
    $params['siteprefix'] = 'slideshow_site_%';
    $records = $DB->get_records_select(
        'config_plugins',
        "plugin = :plugin AND (name LIKE :courseprefix OR name LIKE :siteprefix OR name {$insql})",
        $params,
        'name ASC',
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
function local_course_banner_builder_slideshow_public_fixture_restore_config(array $snapshot): void {
    $expected = [];
    foreach ($snapshot as $record) {
        $expected[$record['name']] = $record['value'];
    }

    foreach (local_course_banner_builder_slideshow_public_fixture_snapshot_config() as $record) {
        if (!array_key_exists($record['name'], $expected)) {
            unset_config($record['name'], 'local_course_banner_builder');
        }
    }
    foreach ($expected as $name => $value) {
        set_config($name, $value, 'local_course_banner_builder');
    }
}

/**
 * Return a deliberately readable Course Slideshow profile for public coverage.
 *
 * @return array<string, mixed>
 */
function local_course_banner_builder_slideshow_public_fixture_profile(): array {
    return [
        'enabled' => 1,
        'forums' => 1,
        'siteannouncements' => 0,
        'assignments' => 0,
        'quizzes' => 0,
        'autoplay' => 0,
        'delay' => 7000,
        'maxslides' => 15,
        'arrows' => 1,
        'dots' => 1,
        'overlaycolor' => '#16324F',
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

/**
 * Return the saved Course profile with only the non-visual test prerequisites.
 *
 * This is used by the admin/public parity gate. It deliberately preserves the
 * saved styling, typography, positions, colours and dimensions so the public
 * course renders the same Course configuration that the administrator sees.
 *
 * @return array{profile:array<string,mixed>,forced:array<string,mixed>,saved:array<string,mixed>}
 */
function local_course_banner_builder_slideshow_public_fixture_parity_profile(
    string $context,
    array $forced
): array {
    $saved = \local_course_banner_builder\manager::get_slideshow_config($context);
    $profile = $saved;
    // The manager returns its canonical opacity as a fraction, while its write API accepts a percent.
    $profile['overlayopacity'] = (float)$saved['overlayopacity'] * 100;
    $forced['maxslides'] = max(2, (int)$saved['maxslides']);
    foreach ($forced as $name => $value) {
        $profile[$name] = $value;
    }

    return [
        'profile' => $profile,
        'forced' => $forced,
        'saved' => $saved,
    ];
}

/**
 * Add one real, upcoming assignment to a disposable source course.
 *
 * @param stdClass $course Disposable source course.
 * @return array{assignmentid:int,cmid:int,title:string}
 */
function local_course_banner_builder_slideshow_public_fixture_add_assignment(stdClass $course): array {
    global $DB;

    [, , , , $moduleinfo] = prepare_new_moduleinfo_data($course, 'assign', 0);
    $title = 'CCB public Slideshow external-source assignment';
    $moduleinfo->name = $title;
    $moduleinfo->introeditor = [
        'text' => 'Disposable assignment used to verify the Slideshow source-course label.',
        'format' => FORMAT_HTML,
        'itemid' => 0,
    ];
    $moduleinfo->intro = $moduleinfo->introeditor['text'];
    $moduleinfo->introformat = FORMAT_HTML;
    $moduleinfo->duedate = time() + DAYSECS;
    $moduleinfo->cutoffdate = 0;
    $moduleinfo->allowsubmissionsfromdate = 0;
    $moduleinfo->grade = 100;
    $moduleinfo->submissiondrafts = 0;
    $moduleinfo->requiresubmissionstatement = 0;
    $moduleinfo->sendnotifications = 0;
    $moduleinfo->sendlatenotifications = 0;
    $moduleinfo->sendstudentnotifications = 0;
    $moduleinfo->teamsubmission = 0;
    $moduleinfo->requireallteammemberssubmit = 0;
    $moduleinfo->blindmarking = 0;
    $moduleinfo->hidegrader = 0;
    $moduleinfo->markingworkflow = 0;
    $moduleinfo->markingallocation = 0;
    $moduleinfo->attemptreopenmethod = 'none';
    $moduleinfo->maxattempts = -1;
    $module = add_moduleinfo($moduleinfo, $course);
    if (!$module || empty($module->instance) || empty($module->coursemodule)) {
        throw new RuntimeException('The public Slideshow fixture could not create its external assignment.');
    }
    $assignment = $DB->get_record('assign', ['id' => (int)$module->instance], 'id, name', MUST_EXIST);

    return [
        'assignmentid' => (int)$assignment->id,
        'cmid' => (int)$module->coursemodule,
        'title' => (string)$assignment->name,
    ];
}

/**
 * Confirm that the Site Slideshow exposes the external assignment and its source course label.
 *
 * @param string $title Exact fixture assignment title.
 * @param string $coursesource Shortname of the disposable source course.
 * @return int Number of matching public Site slides.
 */
function local_course_banner_builder_slideshow_public_fixture_site_secondary_slide_count(
    string $title,
    string $coursesource
): int {
    $payload = \local_course_banner_builder\manager::get_site_slideshow_payload();
    $matching = array_filter($payload['slides'] ?? [], static function (array $slide) use ($title, $coursesource): bool {
        return ($slide['type'] ?? '') === \local_course_banner_builder\manager::SLIDESHOW_TYPE_ASSIGNMENTS &&
            ($slide['title'] ?? '') === $title &&
            ($slide['secondaryLabel'] ?? '') === $coursesource;
    });

    return count($matching);
}

/**
 * Enrol the active administrator explicitly with the Student role.
 *
 * @param stdClass $course Disposable course.
 * @return array{adminid:int,enrolmentid:int,roleid:int}
 */
function local_course_banner_builder_slideshow_public_fixture_enrol_admin_as_student(stdClass $course): array {
    global $DB, $USER;

    $manual = enrol_get_plugin('manual');
    if (!$manual) {
        throw new RuntimeException('The manual enrolment plugin is unavailable for the public Slideshow fixture.');
    }
    $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', IGNORE_MISSING);
    if (!$instance) {
        $instanceid = $manual->add_default_instance($course);
        if (!$instanceid) {
            throw new RuntimeException('The public Slideshow fixture could not add a manual enrolment instance.');
        }
        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
    }
    $studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id, shortname', MUST_EXIST);
    $manual->enrol_user($instance, $USER->id, $studentrole->id);
    $enrolment = $DB->get_record(
        'user_enrolments',
        ['enrolid' => $instance->id, 'userid' => $USER->id],
        'id',
        MUST_EXIST
    );

    return [
        'adminid' => (int)$USER->id,
        'enrolmentid' => (int)$enrolment->id,
        'roleid' => (int)$studentrole->id,
    ];
}

/**
 * Add one real forum announcement to the disposable course.
 *
 * @param stdClass $course Disposable course.
 * @return array{discussionid:int,forumid:int,postid:int,title:string}
 */
function local_course_banner_builder_slideshow_public_fixture_add_announcement(stdClass $course): array {
    global $DB, $USER;

    $forum = $DB->get_record('forum', ['course' => $course->id, 'type' => 'news'], '*', IGNORE_MISSING);
    if (!$forum) {
        throw new RuntimeException('The disposable course has no native Announcements forum.');
    }

    $title = 'CCB public Slideshow fixture announcement';
    $discussion = (object)[
        'course' => (int)$course->id,
        'forum' => (int)$forum->id,
        'name' => $title,
        'message' => '<p>This real Moodle announcement verifies the public Course Slideshow source.</p>',
        'messageformat' => FORMAT_HTML,
        'messagetrust' => 0,
        'mailnow' => false,
        'groupid' => -1,
    ];
    $discussionid = (int)forum_add_discussion($discussion, null, null, $USER->id);
    $stored = $DB->get_record('forum_discussions', ['id' => $discussionid], 'id, firstpost', MUST_EXIST);

    return [
        'discussionid' => (int)$stored->id,
        'forumid' => (int)$forum->id,
        'postid' => (int)$stored->firstpost,
        'title' => $title,
    ];
}

/**
 * Confirm that the created discussion is exposed as a real forum slide.
 *
 * @param stdClass $course Disposable course.
 * @param string $title Exact fixture announcement title.
 * @return int Number of matching public forum slides.
 */
function local_course_banner_builder_slideshow_public_fixture_forum_slide_count(stdClass $course, string $title): int {
    $payload = \local_course_banner_builder\manager::get_course_slideshow_payload($course);
    $matching = array_filter($payload['slides'] ?? [], static function (array $slide) use ($title): bool {
        return ($slide['type'] ?? '') === \local_course_banner_builder\manager::SLIDESHOW_TYPE_FORUMS &&
            ($slide['title'] ?? '') === $title;
    });

    return count($matching);
}

if ($command === 'setup' || $command === 'setup-parity' || $command === 'setup-site-secondary-parity') {
    $snapshot = local_course_banner_builder_slideshow_public_fixture_snapshot_config();
    $courseid = 0;
    try {
        $secondaryparity = $command === 'setup-site-secondary-parity';
        $parity = $command === 'setup-parity' || $secondaryparity;
        $context = $secondaryparity
            ? \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE
            : \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_COURSE;
        $forced = $secondaryparity ? [
            'enabled' => 1,
            'forums' => 0,
            'siteannouncements' => 0,
            'assignments' => 1,
            'quizzes' => 0,
            'autoplay' => 0,
            'arrows' => 1,
            'dots' => 1,
        ] : [
            'enabled' => 1,
            'forums' => 1,
            'siteannouncements' => 0,
            'assignments' => 0,
            'quizzes' => 0,
            'autoplay' => 0,
            'arrows' => 1,
            'dots' => 1,
        ];
        $profile = $parity
            ? local_course_banner_builder_slideshow_public_fixture_parity_profile($context, $forced)
            : ['profile' => local_course_banner_builder_slideshow_public_fixture_profile(), 'forced' => [], 'saved' => []];
        set_config('enabled', 1, $plugin);
        set_config('coursebannerenabled', 1, $plugin);
        set_config('coursebannerdefaultimageenabled', 1, $plugin);
        \local_course_banner_builder\manager::set_slideshow_config(
            $context,
            $profile['profile']
        );

        $suffix = gmdate('YmdHis');
        $course = create_course((object)[
            'fullname' => $secondaryparity
                ? 'CCB QA Slideshow source course ' . $suffix
                : 'CCB QA Public Slideshow ' . $suffix,
            'shortname' => $secondaryparity
                ? 'CCB-QA-SOURCE-' . $suffix
                : 'CCB-QA-PUBLIC-SLIDESHOW-' . $suffix,
            'category' => core_course_category::get_default()->id,
            'visible' => $secondaryparity ? 1 : 0,
            'format' => 'topics',
            'newsitems' => $secondaryparity ? 0 : 1,
            'summary' => $secondaryparity
                ? 'Disposable source course for the CCB Site Slideshow provenance-label validation.'
                : 'Disposable Course Banner Builder public Slideshow validation fixture.',
            'summaryformat' => FORMAT_PLAIN,
        ]);
        $courseid = (int)$course->id;
        $enrolment = local_course_banner_builder_slideshow_public_fixture_enrol_admin_as_student($course);
        if ($secondaryparity) {
            $assignment = local_course_banner_builder_slideshow_public_fixture_add_assignment($course);
            $secondaryslidecount = local_course_banner_builder_slideshow_public_fixture_site_secondary_slide_count(
                $assignment['title'],
                (string)$course->shortname
            );
            if ($secondaryslidecount !== 1) {
                throw new RuntimeException('The Site Slideshow fixture did not expose its external assignment source label.');
            }
            local_course_banner_builder_slideshow_public_fixture_emit([
                'fixtureKind' => 'site-secondary-parity',
                'courseid' => $courseid,
                'snapshot' => $snapshot,
                'adminid' => $enrolment['adminid'],
                'enrolmentid' => $enrolment['enrolmentid'],
                'studentroleid' => $enrolment['roleid'],
                'assignmentid' => $assignment['assignmentid'],
                'assignmentcmid' => $assignment['cmid'],
                'assignmentTitle' => $assignment['title'],
                'sourceCourseShortname' => (string)$course->shortname,
                'siteSecondarySlideCount' => $secondaryslidecount,
                'parityMode' => true,
                'savedSiteConfig' => $profile['saved'],
                'savedSlideshowConfig' => $profile['saved'],
                'forcedRuntimeValues' => $profile['forced'],
            ]);
            exit(0);
        }
        $announcement = local_course_banner_builder_slideshow_public_fixture_add_announcement($course);
        $forumslidecount = local_course_banner_builder_slideshow_public_fixture_forum_slide_count(
            $course,
            $announcement['title']
        );
        if ($forumslidecount !== 1) {
            throw new RuntimeException('The public Slideshow fixture did not expose its real forum announcement.');
        }

        local_course_banner_builder_slideshow_public_fixture_emit([
            'fixtureKind' => 'course-forum',
            'courseid' => $courseid,
            'snapshot' => $snapshot,
            'adminid' => $enrolment['adminid'],
            'enrolmentid' => $enrolment['enrolmentid'],
            'studentroleid' => $enrolment['roleid'],
            'forumid' => $announcement['forumid'],
            'discussionid' => $announcement['discussionid'],
            'postid' => $announcement['postid'],
            'announcementTitle' => $announcement['title'],
            'forumSlideCount' => $forumslidecount,
            'parityMode' => $parity,
            'savedCourseConfig' => $profile['saved'],
            'savedSlideshowConfig' => $profile['saved'],
            'forcedRuntimeValues' => $profile['forced'],
        ]);
    } catch (Throwable $exception) {
        local_course_banner_builder_slideshow_public_fixture_restore_config($snapshot);
        if ($courseid > 0 && $DB->record_exists('course', ['id' => $courseid])) {
            delete_course($courseid, false);
        }
        throw $exception;
    }
    exit(0);
}

if ($command === 'cleanup') {
    if ($manifestpath === '' || !is_file($manifestpath)) {
        throw new RuntimeException('Missing public Slideshow fixture manifest.');
    }
    $manifestjson = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($manifestpath));
    $manifest = json_decode($manifestjson, true, 512, JSON_THROW_ON_ERROR);
    $fixturekind = (string)($manifest['fixtureKind'] ?? 'course-forum');
    $courseid = (int)($manifest['courseid'] ?? 0);
    $snapshot = $manifest['snapshot'] ?? null;
    $discussionid = (int)($manifest['discussionid'] ?? 0);
    $assignmentid = (int)($manifest['assignmentid'] ?? 0);
    $enrolmentid = (int)($manifest['enrolmentid'] ?? 0);
    $expectsforum = $fixturekind === 'course-forum';
    $expectsassignment = $fixturekind === 'site-secondary-parity';
    if ($courseid < 1 || !is_array($snapshot) || $enrolmentid < 1 ||
        ($expectsforum && $discussionid < 1) || ($expectsassignment && $assignmentid < 1)) {
        throw new RuntimeException('Invalid public Slideshow fixture manifest.');
    }

    local_course_banner_builder_slideshow_public_fixture_restore_config($snapshot);
    if ($DB->record_exists('course', ['id' => $courseid])) {
        delete_course($courseid, false);
    }
    local_course_banner_builder_slideshow_public_fixture_emit([
        'courseid' => $courseid,
        'fixtureKind' => $fixturekind,
        'courseRemoved' => !$DB->record_exists('course', ['id' => $courseid]),
        'forumDiscussionRemoved' => !$expectsforum || !$DB->record_exists('forum_discussions', ['id' => $discussionid]),
        'assignmentRemoved' => !$expectsassignment || !$DB->record_exists('assign', ['id' => $assignmentid]),
        'studentEnrolmentRemoved' => !$DB->record_exists('user_enrolments', ['id' => $enrolmentid]),
        'publicSlideshowConfigRestored' =>
            local_course_banner_builder_slideshow_public_fixture_snapshot_config() === $snapshot,
    ]);
    exit(0);
}

throw new RuntimeException('Unknown public Slideshow fixture command.');
