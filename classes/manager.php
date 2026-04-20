<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_course_banner_builder;

defined('MOODLE_INTERNAL') || die();

/**
 * Banner manager helpers.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** @var string */
    public const FILEAREA = 'bannerimage';
    /** @var string */
    public const CARD_FILEAREA = 'coursecard';
    /** @var string */
    public const MANAGED_OVERVIEW_PREFIX = 'course_banner_builder_auto';
    /** @var string */
    public const MANAGED_CARD_PREFIX = 'course_banner_builder_card';
    /** @var string */
    public const MODE_CUMULATIVE = 'cumulative';
    /** @var string */
    public const MODE_RANDOM = 'random';
    /** @var string */
    public const FIT_MODE_BANNER = 'bannerfit';
    /** @var string */
    public const FIT_MODE_ORIGINAL = 'original';
    /** @var string */
    public const FIT_SCOPE_SELF = 'self';
    /** @var string */
    public const FIT_SCOPE_DESCENDANTS = 'descendants';
    /** @var int */
    protected const ADMIN_THUMB_LIMIT = 3;
    /** @var int */
    protected const DEFAULT_CANVAS_WIDTH = 1600;
    /** @var int */
    protected const DEFAULT_CANVAS_HEIGHT = 400;
    /** @var int */
    protected const CARD_CANVAS_WIDTH = 800;
    /** @var int */
    protected const CARD_CANVAS_HEIGHT = 600;
    /** @var int */
    public const CONFIG_EXPORT_VERSION = 1;

    /** @var array */
    protected const HIERARCHY_ROW_CLASSES = [
        'local-course-banner-builder-depth-0',
        'local-course-banner-builder-depth-1 table-light',
        'local-course-banner-builder-depth-2 table-info',
        'local-course-banner-builder-depth-3 table-primary',
    ];

    /** @var array */
    protected const HIERARCHY_GROUP_COLOR_CLASSES = [
        'local-course-banner-builder-group-primary',
        'local-course-banner-builder-group-info',
        'local-course-banner-builder-group-success',
        'local-course-banner-builder-group-purple',
        'local-course-banner-builder-group-teal',
    ];

    /** @var int */
    protected const SOURCE_LABEL_MAX_LENGTH = 96;

    /** @var int */
    protected const SOURCE_NAME_MAX_LENGTH = 32;

    /**
     * Build default category settings without persisting them.
     *
     * @param int $categoryid
     * @return \stdClass
     */
    protected static function get_default_category_settings(int $categoryid): \stdClass {
        return (object)[
            'id' => 0,
            'categoryid' => $categoryid,
            'elementids' => null,
            'coursecustomfieldid' => null,
            'compositionmode' => self::MODE_RANDOM,
            'fitmode' => self::FIT_MODE_ORIGINAL,
            'fitapplyscope' => self::FIT_SCOPE_DESCENDANTS,
            'timemodified' => 0,
        ];
    }

    /**
     * Fill missing settings with defaults.
     *
     * @param \stdClass $record
     * @param int $categoryid
     * @return \stdClass
     */
    protected static function normalise_category_settings(\stdClass $record, int $categoryid): \stdClass {
        $defaults = self::get_default_category_settings($categoryid);
        foreach ($defaults as $property => $value) {
            if (!property_exists($record, $property) || $record->{$property} === null || $record->{$property} === '') {
                $record->{$property} = $value;
            }
        }

        return $record;
    }

    /**
     * Check whether a field exists in a table.
     *
     * @param string $table
     * @param string $field
     * @return bool
     */
    protected static function table_field_exists(string $table, string $field): bool {
        global $DB;
        static $cache = [];

        $key = $table . ':' . $field;
        if (!array_key_exists($key, $cache)) {
            $dbman = $DB->get_manager();
            $cache[$key] = $dbman->field_exists(new \xmldb_table($table), new \xmldb_field($field));
        }

        return $cache[$key];
    }

    /**
     * Returns category choices.
     *
     * @return array
     */
    public static function get_category_options(): array {
        $options = [];
        foreach (self::get_category_source_options() as $option) {
            $options[$option['id']] = $option['label'];
        }
        return $options;
    }

    /**
     * Returns detailed category source choices for the admin selector.
     *
     * @return array
     */
    public static function get_category_source_options(): array {
        global $DB;

        $records = $DB->get_records('course_categories', null, 'sortorder, id', 'id, name, path, depth, sortorder');
        $recordsbyid = [];
        foreach ($records as $record) {
            $recordsbyid[(int)$record->id] = $record;
        }

        $usedcategoryids = self::get_used_category_ids();
        $items = [];
        foreach ($records as $record) {
            $pathids = array_values(array_filter(array_map('intval', explode('/', trim((string)$record->path, '/')))));
            $pathsort = implode('/', array_map(static function(int $pathid): string {
                return sprintf('%010d', $pathid);
            }, $pathids));
            $depth = max(0, count($pathids) - 1);

            $pathnames = [];
            $fullpathnames = [];
            foreach ($pathids as $pathid) {
                if (!empty($recordsbyid[$pathid])) {
                    $pathname = format_string($recordsbyid[$pathid]->name);
                    $pathnames[] = self::shorten_source_label($pathname, self::SOURCE_NAME_MAX_LENGTH);
                    $fullpathnames[] = $pathname;
                }
            }

            $name = format_string($record->name);
            $pathlabel = self::format_source_path_label($pathnames);
            $prefix = str_repeat('|', $depth);
            if ($prefix !== '') {
                $prefix .= ' ';
            }

            $label = $prefix . $name;
            if ($pathlabel !== '') {
                $label .= ' (' . $pathlabel . ')';
            }

            $isused = in_array((int)$record->id, $usedcategoryids, true);
            if ($isused) {
                $label = get_string('usedsourceprefix', 'local_course_banner_builder') . ' ' . $label;
            }

            $items[] = [
                'id' => (int)$record->id,
                'label' => $label,
                'title' => $name . ' (' . implode(' > ', $fullpathnames) . ')',
                'searchtext' => \core_text::strtolower($name . ' ' . implode(' ', $pathnames)),
                'isused' => $isused,
                'pathsort' => $pathsort,
            ];
        }

        usort($items, static function(array $a, array $b): int {
            return $a['pathsort'] <=> $b['pathsort'];
        });

        return $items;
    }

    /**
     * Returns ids of categories already configured as banner sources.
     *
     * @return array
     */
    protected static function get_used_category_ids(): array {
        global $DB;

        $ids = [];
        $elementids = $DB->get_fieldset_select(
            'local_course_banner_elements',
            'DISTINCT categoryid',
            'categoryid IS NOT NULL'
        );
        $settingids = $DB->get_fieldset_select(
            'local_course_banner_order',
            'DISTINCT categoryid',
            'categoryid IS NOT NULL'
        );

        foreach (array_merge($elementids, $settingids) as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Format a compact path label.
     *
     * @param array $pathnames
     * @return string
     */
    protected static function format_source_path_label(array $pathnames): string {
        $count = count($pathnames);
        if ($count === 0) {
            return '';
        }

        if ($count <= 3) {
            return implode(' > ', $pathnames);
        }

        return $pathnames[0] . ' > ... > ' . $pathnames[$count - 1];
    }

    /**
     * Shorten long source labels without breaking multibyte characters.
     *
     * @param string $label
     * @param int $maxlength
     * @return string
     */
    protected static function shorten_source_label(string $label, int $maxlength): string {
        $label = trim($label);
        if (\core_text::strlen($label) <= $maxlength) {
            return $label;
        }

        return \core_text::substr($label, 0, max(1, $maxlength - 3)) . '...';
    }

    /**
     * Returns selectable course custom fields for future banner sources.
     *
     * @return array
     */
    public static function get_course_customfield_options(): array {
        global $DB;

        $sql = "SELECT f.id, f.name, f.shortname, f.type, c.name AS categoryname
                  FROM {customfield_field} f
                  JOIN {customfield_category} c ON c.id = f.categoryid
                 WHERE c.component = :component
                   AND c.area = :area
                   AND f.type IN (:texttype, :selecttype)
              ORDER BY c.sortorder, f.sortorder, f.name";
        $records = $DB->get_records_sql($sql, [
            'component' => 'core_course',
            'area' => 'course',
            'texttype' => 'text',
            'selecttype' => 'select',
        ]);

        $options = [];
        foreach ($records as $record) {
            $name = format_string($record->name);
            $categoryname = format_string($record->categoryname);
            $type = get_string('customfieldtype:' . $record->type, 'local_course_banner_builder');
            $options[(int)$record->id] = $name . ' (' . $categoryname . ' - ' . $type . ')';
        }

        return $options;
    }

    /**
     * Returns custom field source choices enabled in plugin settings.
     *
     * @return array
     */
    public static function get_enabled_customfield_source_options(): array {
        $enabled = get_config('local_course_banner_builder', 'enabledcustomfields');
        $enabledids = array_filter(array_map('intval', explode(',', (string)$enabled)));
        $alloptions = self::get_course_customfield_options();
        $items = [];

        foreach ($enabledids as $id) {
            if (!isset($alloptions[$id])) {
                continue;
            }
            $items[] = [
                'id' => $id,
                'label' => self::shorten_source_label($alloptions[$id], self::SOURCE_LABEL_MAX_LENGTH),
                'title' => $alloptions[$id],
                'searchtext' => \core_text::strtolower($alloptions[$id]),
            ];
        }

        return $items;
    }

    /**
     * Returns available composition modes.
     *
     * @return array
     */
    public static function get_composition_mode_options(): array {
        return [
            self::MODE_CUMULATIVE => get_string('compositionmode:cumulative', 'local_course_banner_builder'),
            self::MODE_RANDOM => get_string('compositionmode:random', 'local_course_banner_builder'),
        ];
    }

    /**
     * Returns available fit mode options.
     *
     * @return array
     */
    public static function get_fit_mode_options(): array {
        return [
            self::FIT_MODE_BANNER => get_string('fitmode:bannerfit', 'local_course_banner_builder'),
            self::FIT_MODE_ORIGINAL => get_string('fitmode:original', 'local_course_banner_builder'),
        ];
    }

    /**
     * Returns available fit scope options.
     *
     * @return array
     */
    public static function get_fit_apply_scope_options(): array {
        return [
            self::FIT_SCOPE_SELF => get_string('fitapplyscope:self', 'local_course_banner_builder'),
            self::FIT_SCOPE_DESCENDANTS => get_string('fitapplyscope:descendants', 'local_course_banner_builder'),
        ];
    }

    /**
     * Fetch or create category settings.
     *
     * @param int $categoryid
     * @return \stdClass
     */
    public static function get_category_settings(int $categoryid): \stdClass {
        global $DB;

        $record = $DB->get_record('local_course_banner_order', ['categoryid' => $categoryid]);
        if ($record) {
            return self::normalise_category_settings($record, $categoryid);
        }

        return self::get_default_category_settings($categoryid);
    }

    /**
     * Fetch or create category settings.
     *
     * @param int $categoryid
     * @return \stdClass
     */
    public static function get_or_create_category_settings(int $categoryid): \stdClass {
        global $DB;

        $record = self::get_category_settings($categoryid);
        if (!empty($record->id)) {
            return $record;
        }

        $record->timemodified = time();
        if (!self::table_field_exists('local_course_banner_order', 'compositionmode')) {
            unset($record->compositionmode);
        }
        if (!self::table_field_exists('local_course_banner_order', 'fitmode')) {
            unset($record->fitmode);
        }
        if (!self::table_field_exists('local_course_banner_order', 'fitapplyscope')) {
            unset($record->fitapplyscope);
        }

        $record->id = $DB->insert_record('local_course_banner_order', $record);
        return self::normalise_category_settings($record, $categoryid);
    }

    /**
     * Save category settings.
     *
     * @param int $categoryid
     * @param string $compositionmode
     * @return void
     */
    public static function save_category_settings(
        int $categoryid,
        string $compositionmode,
        string $fitmode,
        string $fitapplyscope
    ): void {
        global $DB;

        $record = self::get_or_create_category_settings($categoryid);
        $modes = array_keys(self::get_composition_mode_options());
        if (!in_array($compositionmode, $modes, true)) {
            $compositionmode = self::MODE_RANDOM;
        }
        $fitmodes = array_keys(self::get_fit_mode_options());
        if (!in_array($fitmode, $fitmodes, true)) {
            $fitmode = self::FIT_MODE_ORIGINAL;
        }
        $scopes = array_keys(self::get_fit_apply_scope_options());
        if (!in_array($fitapplyscope, $scopes, true)) {
            $fitapplyscope = self::FIT_SCOPE_DESCENDANTS;
        }

        if (self::table_field_exists('local_course_banner_order', 'compositionmode')) {
            $record->compositionmode = $compositionmode;
        }
        if (self::table_field_exists('local_course_banner_order', 'fitmode')) {
            $record->fitmode = $fitmode;
        }
        if (self::table_field_exists('local_course_banner_order', 'fitapplyscope')) {
            $record->fitapplyscope = $fitapplyscope;
        }
        $record->timemodified = time();
        $DB->update_record('local_course_banner_order', $record);

        self::sync_courses_for_category_tree($categoryid);
    }

    /**
     * Returns an existing banner element by id.
     *
     * @param int $elementid
     * @return \stdClass|null
     */
    public static function get_banner_element(int $elementid): ?\stdClass {
        global $DB;

        $record = $DB->get_record('local_course_banner_elements', ['id' => $elementid]);
        return $record ?: null;
    }

    /**
     * Returns all banner elements for a category.
     *
     * @param int $categoryid
     * @param bool $enabledonly
     * @return array
     */
    public static function get_category_elements(int $categoryid, bool $enabledonly = false): array {
        global $DB;

        $params = [
            'categoryid' => $categoryid,
            'elementtype' => 'background_image',
        ];
        if ($enabledonly) {
            $params['isenabled'] = 1;
        }

        return $DB->get_records('local_course_banner_elements', $params, 'sortorder ASC, id ASC');
    }

    /**
     * Create a new empty category element.
     *
     * @param int $categoryid
     * @return \stdClass
     */
    protected static function create_category_element(int $categoryid): \stdClass {
        global $DB;

        $now = time();
        $record = (object)[
            'categoryid' => $categoryid,
            'customfieldid' => null,
            'elementtype' => 'background_image',
            'name' => '',
            'sortorder' => self::get_next_sortorder($categoryid),
            'fileitemid' => 0,
            'isenabled' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $DB->insert_record('local_course_banner_elements', $record);
        $record->fileitemid = $record->id;
        $DB->update_record('local_course_banner_elements', $record);

        return $record;
    }

    /**
     * Get next sort order in a category.
     *
     * @param int $categoryid
     * @return int
     */
    protected static function get_next_sortorder(int $categoryid): int {
        global $DB;

        $max = $DB->get_field_sql(
            'SELECT MAX(sortorder) FROM {local_course_banner_elements} WHERE categoryid = :categoryid',
            ['categoryid' => $categoryid]
        );

        return ((int)$max) + 1;
    }

    /**
     * Shift existing layer orders to make room for a new layer.
     *
     * @param int $categoryid
     * @param int $sortorder
     * @return void
     */
    protected static function make_sortorder_room(int $categoryid, int $sortorder): void {
        global $DB;

        $records = self::get_category_elements($categoryid);
        foreach ($records as $record) {
            if ((int)$record->sortorder < $sortorder) {
                continue;
            }
            $record->sortorder = (int)$record->sortorder + 1;
            $record->timemodified = time();
            $DB->update_record('local_course_banner_elements', $record);
        }
    }

    /**
     * Normalize layer orders so they always run from 0 to n.
     *
     * @param array $records
     * @return void
     */
    protected static function normalize_element_sortorders(array $records): void {
        global $DB;

        usort($records, static function(\stdClass $a, \stdClass $b): int {
            $sortcompare = ((int)$a->sortorder) <=> ((int)$b->sortorder);
            if ($sortcompare !== 0) {
                return $sortcompare;
            }
            return ((int)$a->id) <=> ((int)$b->id);
        });

        $now = time();
        foreach ($records as $index => $record) {
            if ((int)$record->sortorder === $index) {
                continue;
            }
            $record->sortorder = $index;
            $record->timemodified = $now;
            $DB->update_record('local_course_banner_elements', $record);
        }
    }

    /**
     * Get files currently stored in the user draft area for this upload.
     *
     * @param int $draftitemid
     * @return array
     */
    protected static function get_draft_files(int $draftitemid): array {
        global $USER;

        if (empty($draftitemid)) {
            return [];
        }

        $usercontext = \context_user::instance($USER->id);
        $fs = get_file_storage();
        return $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id ASC', false);
    }

    /**
     * Copy one draft file into a banner element file area.
     *
     * @param \stdClass $record
     * @param \stored_file $draftfile
     * @return void
     */
    protected static function copy_draft_file_to_element(\stdClass $record, \stored_file $draftfile): void {
        $context = \context_system::instance();
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_course_banner_builder', self::FILEAREA, $record->fileitemid);
        $fs->create_file_from_storedfile([
            'contextid' => $context->id,
            'component' => 'local_course_banner_builder',
            'filearea' => self::FILEAREA,
            'itemid' => $record->fileitemid,
            'filepath' => '/',
            'filename' => $draftfile->get_filename(),
        ], $draftfile);
    }

    /**
     * Store binary content in one banner element file area.
     *
     * @param \stdClass $record
     * @param string $filename
     * @param string $content
     * @return void
     */
    protected static function store_content_in_element(\stdClass $record, string $filename, string $content): void {
        $context = \context_system::instance();
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_course_banner_builder', self::FILEAREA, $record->fileitemid);
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'local_course_banner_builder',
            'filearea' => self::FILEAREA,
            'itemid' => $record->fileitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);
    }

    /**
     * Build an automatic layer name from a source file.
     *
     * @param \stored_file $file
     * @return string
     */
    protected static function get_automatic_layer_name(\stored_file $file): string {
        $filename = pathinfo($file->get_filename(), PATHINFO_FILENAME);
        return trim((string)$filename);
    }

    /**
     * Save category banner form data.
     *
     * @param \stdClass $data
     * @return int
     */
    public static function save_category_banner(\stdClass $data): int {
        global $DB;

        $categoryid = (int)$data->categoryid;
        $elementid = (int)($data->elementid ?? 0);
        $draftfiles = self::get_draft_files((int)($data->bannerimage_filemanager ?? 0));

        if (!$elementid && count($draftfiles) > 1) {
            $createdids = [];
            $nextsortorder = self::get_next_sortorder($categoryid);
            foreach ($draftfiles as $draftfile) {
                $record = self::create_category_element($categoryid);
                $record->name = self::get_automatic_layer_name($draftfile);
                $record->sortorder = $nextsortorder++;
                $record->isenabled = empty($data->isenabled) ? 0 : 1;
                $record->timemodified = time();
                $DB->update_record('local_course_banner_elements', $record);
                self::copy_draft_file_to_element($record, $draftfile);
                $createdids[] = (int)$record->id;
            }

            self::sync_courses_for_category_tree($categoryid);
            return (int)reset($createdids);
        }

        $record = $elementid ? self::get_banner_element($elementid) : null;
        if (!$record) {
            $record = self::create_category_element($categoryid);
        }

        $context = \context_system::instance();
        $record->categoryid = $categoryid;
        $record->name = trim((string)($data->name ?? ''));
        if ($record->name === '' && !empty($draftfiles)) {
            $record->name = self::get_automatic_layer_name(reset($draftfiles));
        }
        $record->sortorder = max(0, (int)($data->sortorder ?? 0));
        if (!$elementid) {
            self::make_sortorder_room($categoryid, $record->sortorder);
        }
        $record->isenabled = empty($data->isenabled) ? 0 : 1;
        $record->timemodified = time();
        $DB->update_record('local_course_banner_elements', $record);

        file_save_draft_area_files(
            $data->bannerimage_filemanager,
            $context->id,
            'local_course_banner_builder',
            self::FILEAREA,
            $record->fileitemid,
            self::get_filemanager_options()
        );

        self::normalize_element_sortorders(self::get_category_elements($categoryid));
        self::sync_courses_for_category_tree($categoryid);
        return (int)$record->id;
    }

    /**
     * Update editable layer metadata from the summary table.
     *
     * @param int $elementid
     * @param string $name
     * @param int $sortorder
     * @param bool $enabled
     * @return void
     */
    public static function update_banner_element_metadata(int $elementid, string $name, int $sortorder, bool $enabled): void {
        global $DB;

        $record = self::get_banner_element($elementid);
        if (!$record) {
            return;
        }

        $record->name = trim($name);
        $record->sortorder = max(0, $sortorder);
        $record->isenabled = $enabled ? 1 : 0;
        $record->timemodified = time();
        $DB->update_record('local_course_banner_elements', $record);
        self::normalize_element_sortorders(self::get_category_elements((int)$record->categoryid));
        self::sync_courses_for_category_tree((int)$record->categoryid);
    }

    /**
     * Update editable layer metadata and fit override from the summary table.
     *
     * @param int $elementid
     * @param string $name
     * @param int $sortorder
     * @param bool $enabled
     * @param string|null $fitmodeoverride
     * @return void
     */
    public static function update_banner_element_row(
        int $elementid,
        string $name,
        int $sortorder,
        bool $enabled,
        ?string $fitmodeoverride
    ): void {
        global $DB;

        $record = self::get_banner_element($elementid);
        if (!$record) {
            return;
        }

        $allowedmodes = array_merge([''], array_keys(self::get_fit_mode_options()));
        if (!in_array((string)$fitmodeoverride, $allowedmodes, true)) {
            $fitmodeoverride = null;
        }
        $sourcefitmode = self::get_category_settings((int)$record->categoryid)->fitmode ?? self::FIT_MODE_BANNER;
        if ($fitmodeoverride === $sourcefitmode) {
            $fitmodeoverride = '';
        }

        $record->name = trim($name);
        $record->sortorder = max(0, $sortorder);
        $record->isenabled = $enabled ? 1 : 0;
        if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
            $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
        }
        $record->timemodified = time();
        $DB->update_record('local_course_banner_elements', $record);
        self::normalize_element_sortorders(self::get_category_elements((int)$record->categoryid));
        self::sync_courses_for_category_tree((int)$record->categoryid);
    }

    /**
     * Update editable layer metadata for all rows in a selected source.
     *
     * @param int $categoryid
     * @param array $names
     * @param array $sortorders
     * @param array $enabled
     * @param array $fitmodeoverrides
     * @return void
     */
    public static function update_banner_element_rows(
        int $categoryid,
        array $names,
        array $sortorders,
        array $enabled,
        array $fitmodeoverrides
    ): void {
        global $DB;

        $records = self::get_category_elements($categoryid);
        if (empty($records)) {
            return;
        }

        $allowedmodes = array_merge([''], array_keys(self::get_fit_mode_options()));
        $sourcefitmode = self::get_category_settings($categoryid)->fitmode ?? self::FIT_MODE_BANNER;
        $now = time();
        foreach ($records as $record) {
            $elementid = (int)$record->id;
            $fitmodeoverride = (string)($fitmodeoverrides[$elementid] ?? '');
            if (!in_array($fitmodeoverride, $allowedmodes, true)) {
                $fitmodeoverride = '';
            }
            if ($fitmodeoverride === $sourcefitmode) {
                $fitmodeoverride = '';
            }

            $record->name = trim((string)($names[$elementid] ?? $record->name));
            $record->sortorder = max(0, (int)($sortorders[$elementid] ?? $record->sortorder));
            $record->isenabled = !empty($enabled[$elementid]) ? 1 : 0;
            if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
                $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
            }
            $record->timemodified = $now;
            $DB->update_record('local_course_banner_elements', $record);
        }

        self::normalize_element_sortorders(self::get_category_elements($categoryid));
        self::sync_courses_for_category_tree($categoryid);
    }

    /**
     * Delete one banner element and its files.
     *
     * @param int $elementid
     * @return void
     */
    public static function delete_banner_element(int $elementid): void {
        global $DB;

        $record = self::get_banner_element($elementid);
        if (!$record) {
            return;
        }

        $context = \context_system::instance();
        $fs = get_file_storage();
        $fs->delete_area_files(
            $context->id,
            'local_course_banner_builder',
            self::FILEAREA,
            $record->fileitemid
        );
        $DB->delete_records('local_course_banner_elements', ['id' => $record->id]);

        self::sync_courses_for_category_tree((int)$record->categoryid);
    }

    /**
     * Delete all content and rules for a category.
     *
     * @param int $categoryid
     * @return void
     */
    public static function delete_category_content(int $categoryid): void {
        global $DB;

        self::delete_category_images($categoryid, false);
        $DB->delete_records('local_course_banner_order', ['categoryid' => $categoryid]);
        self::sync_courses_for_category_tree($categoryid);
    }

    /**
     * Delete all images for a category while optionally syncing affected courses.
     *
     * @param int $categoryid
     * @param bool $sync
     * @return void
     */
    public static function delete_category_images(int $categoryid, bool $sync = true): void {
        global $DB;

        foreach (self::get_category_elements($categoryid) as $element) {
            $context = \context_system::instance();
            $fs = get_file_storage();
            $fs->delete_area_files(
                $context->id,
                'local_course_banner_builder',
                self::FILEAREA,
                $element->fileitemid
            );
        }

        $DB->delete_records('local_course_banner_elements', ['categoryid' => $categoryid]);
        if ($sync) {
            self::sync_courses_for_category_tree($categoryid);
        }
    }

    /**
     * Delete selected layer elements.
     *
     * @param array $elementids
     * @return int
     */
    public static function delete_banner_elements(array $elementids): int {
        $deleted = 0;
        foreach (array_unique(array_map('intval', $elementids)) as $elementid) {
            if (!$elementid || !self::get_banner_element($elementid)) {
                continue;
            }
            self::delete_banner_element($elementid);
            $deleted++;
        }
        return $deleted;
    }

    /**
     * Prepare filemanager defaults.
     *
     * @param int $categoryid
     * @param int $elementid
     * @param bool $allowmultiple
     * @return \stdClass
     */
    public static function get_form_data(int $categoryid, int $elementid = 0, bool $allowmultiple = true): \stdClass {
        $context = \context_system::instance();
        $record = $elementid ? self::get_banner_element($elementid) : null;
        if (!$record) {
            $record = (object)[
                'id' => 0,
                'categoryid' => $categoryid,
                'name' => '',
                'sortorder' => self::get_next_sortorder($categoryid),
                'isenabled' => 1,
                'fileitemid' => 0,
            ];
        }

        $data = (object)[
            'elementid' => (int)$record->id,
            'categoryid' => $categoryid,
            'name' => $record->name ?? '',
            'sortorder' => (int)$record->sortorder,
            'isenabled' => (int)$record->isenabled,
        ];

        file_prepare_standard_filemanager(
            $data,
            'bannerimage',
            self::get_filemanager_options($allowmultiple),
            $context,
            'local_course_banner_builder',
            self::FILEAREA,
            (int)$record->fileitemid
        );

        return $data;
    }

    /**
     * Filemanager options.
     *
     * @return array
     */
    public static function get_filemanager_options(bool $allowmultiple = true): array {
        global $CFG;

        return [
            'maxfiles' => $allowmultiple ? 20 : 1,
            'subdirs' => 0,
            'maxbytes' => $CFG->maxbytes,
            'accepted_types' => ['web_image'],
        ];
    }

    /**
     * Return upload and render guidance for the admin form.
     *
     * @return string
     */
    public static function get_upload_guidance(): string {
        global $CFG, $PAGE;

        require_once($CFG->dirroot . '/course/lib.php');

        $maxbytes = (int)($CFG->maxbytes ?? 0);
        $overviewmaxbytes = $maxbytes;
        $overviewtypes = get_string('webimages', 'local_course_banner_builder');
        if (function_exists('course_overviewfiles_options')) {
            $options = course_overviewfiles_options((object)['id' => 0]);
            if (!empty($options)) {
                $overviewmaxbytes = (int)($options['maxbytes'] ?? $maxbytes);
                $acceptedtypes = $options['accepted_types'] ?? ['web_image'];
                $overviewtypes = is_array($acceptedtypes) ? implode(', ', $acceptedtypes) : (string)$acceptedtypes;
            }
        }

        $ratio = self::DEFAULT_CANVAS_WIDTH . ':' . self::DEFAULT_CANVAS_HEIGHT . ' (4:1)';
        $theme = $PAGE->theme->name ?? get_string('unknown', 'local_course_banner_builder');
        $themedetails = get_string('renderratio:default', 'local_course_banner_builder');
        if ($theme === 'easyedu') {
            $themedetails = get_string('renderratio:easyedu', 'local_course_banner_builder');
        }

        $data = (object)[
            'maxbytes' => display_size($maxbytes),
            'overviewmaxbytes' => display_size($overviewmaxbytes),
            'overviewtypes' => $overviewtypes,
            'canvas' => self::DEFAULT_CANVAS_WIDTH . ' x ' . self::DEFAULT_CANVAS_HEIGHT . ' px',
            'ratio' => $ratio,
            'theme' => $theme,
            'themedetails' => $themedetails,
        ];

        return get_string('uploadguidance', 'local_course_banner_builder', $data);
    }

    /**
     * Build data for the configured banner list.
     *
     * @return array
     */
    public static function export_configured_categories(): array {
        global $DB;

        $categoryids = $DB->get_fieldset_select('local_course_banner_elements', 'DISTINCT categoryid', 'categoryid IS NOT NULL');
        $items = [];
        foreach ($categoryids as $categoryindex => $categoryid) {
            $elements = self::get_category_elements((int)$categoryid);
            if (empty($elements)) {
                continue;
            }

            $previewrecord = null;
            foreach ($elements as $element) {
                if (self::get_banner_image_url($element)) {
                    $previewrecord = $element;
                    break;
                }
            }
            $imageurl = $previewrecord ? self::get_banner_image_url($previewrecord) : null;

            try {
                $category = \core_course_category::get((int)$categoryid, IGNORE_MISSING);
            } catch (\moodle_exception $e) {
                $category = null;
            }

            if (!$category) {
                continue;
            }

            $pathids = array_filter(array_map('intval', explode('/', trim((string)$category->path, '/'))));
            $pathsort = implode('/', array_map(static function(int $pathid): string {
                return sprintf('%010d', $pathid);
            }, $pathids));
            $depth = max(0, count($pathids) - 1);
            $rowclasses = self::HIERARCHY_ROW_CLASSES;
            $rowclass = $rowclasses[min($depth, count($rowclasses) - 1)];
            $settings = self::get_category_settings((int)$categoryid);
            $compositionmode = $settings->compositionmode ?? self::MODE_RANDOM;
            $fitmode = $settings->fitmode ?? self::FIT_MODE_ORIGINAL;
            $fitapplyscope = $settings->fitapplyscope ?? self::FIT_SCOPE_DESCENDANTS;
            $isisolated = $depth > 0 && $fitapplyscope === self::FIT_SCOPE_SELF;
            if ($isisolated) {
                $rowclass .= ' local-course-banner-builder-source-isolated table-warning';
            }

            $items[] = [
                'categoryname' => format_string(strip_tags($category->get_nested_name())),
                'pathsort' => $pathsort,
                'rootcategoryid' => reset($pathids) ?: (int)$categoryid,
                'hierarchylevel' => $depth,
                'hierarchyarrows' => str_repeat('-> ', $depth),
                'hierarchylabel' => self::get_hierarchy_relation_label($depth),
                'isisolated' => $isisolated,
                'shortcircuithelp' => get_string('sourceshortcircuited', 'local_course_banner_builder'),
                'shortcircuitpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('sourceshortcircuited', 'local_course_banner_builder') . '</p></div>',
                'rowclass' => $rowclass,
                'layercount' => count($elements),
                'compositionmodelabel' => self::get_composition_mode_options()[$compositionmode] ?? $compositionmode,
                'fitmodelabel' => self::get_fit_mode_options()[$fitmode] ?? $fitmode,
                'fitapplyscopelabel' => self::get_fit_apply_scope_options()[$fitapplyscope] ?? $fitapplyscope,
                'thumbnails' => self::export_element_thumbnails($elements),
                'hasmorethumbnails' => count($elements) > self::ADMIN_THUMB_LIMIT,
                'morethumbnailscount' => max(0, count($elements) - self::ADMIN_THUMB_LIMIT),
                'editurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'categoryid' => $categoryid,
                ]))->out(false),
                'categoryadminurl' => (new \moodle_url('/course/management.php', [
                    'categoryid' => $categoryid,
                ]))->out(false),
                'deletecategoryurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'categoryid' => $categoryid,
                    'deletecategorycontent' => $categoryid,
                    'sesskey' => sesskey(),
                ]))->out(false),
                'deletecategoryimagesurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'categoryid' => $categoryid,
                    'deletecategoryimages' => $categoryid,
                    'sesskey' => sesskey(),
                ]))->out(false),
            ];
        }

        usort($items, static function(array $a, array $b): int {
            return $a['pathsort'] <=> $b['pathsort'];
        });
        self::apply_hierarchy_group_classes($items);

        return [
            'hasitems' => !empty($items),
            'items' => $items,
        ];
    }

    /**
     * Add visual grouping classes for configured source hierarchy rows.
     *
     * @param array $items
     * @return void
     */
    protected static function apply_hierarchy_group_classes(array &$items): void {
        $palette = self::HIERARCHY_GROUP_COLOR_CLASSES;
        $previousrootid = null;
        $previouscolorindex = null;
        $currentcolorclass = $palette[0];

        foreach ($items as $index => &$item) {
            $rootid = (int)($item['rootcategoryid'] ?? 0);
            if ($rootid !== $previousrootid) {
                $colorindex = abs((int)crc32((string)$rootid)) % count($palette);
                if ($previouscolorindex !== null && $colorindex === $previouscolorindex) {
                    $colorindex = ($colorindex + 1) % count($palette);
                }
                $currentcolorclass = $palette[$colorindex];
                $previouscolorindex = $colorindex;
            }

            $previousitem = $items[$index - 1] ?? null;
            $nextitem = $items[$index + 1] ?? null;
            $isgroupstart = !$previousitem || (int)($previousitem['rootcategoryid'] ?? 0) !== $rootid;
            $isgroupend = !$nextitem || (int)($nextitem['rootcategoryid'] ?? 0) !== $rootid;

            $item['rowclass'] .= ' ' . $currentcolorclass;
            if ($isgroupstart) {
                $item['rowclass'] .= ' local-course-banner-builder-group-start';
            }
            if ($isgroupend) {
                $item['rowclass'] .= ' local-course-banner-builder-group-end';
            }

            $previousrootid = $rootid;
        }
        unset($item);
    }

    /**
     * Get a generic hierarchy label without repeating category names.
     *
     * @param int $depth
     * @return string
     */
    protected static function get_hierarchy_relation_label(int $depth): string {
        if ($depth <= 0) {
            return get_string('rootcategory', 'local_course_banner_builder');
        }

        if ($depth === 1) {
            return get_string('hierarchychild', 'local_course_banner_builder');
        }

        $label = str_repeat(get_string('hierarchychildprefix', 'local_course_banner_builder'), $depth);
        $label .= get_string('hierarchychildbase', 'local_course_banner_builder');

        return get_string('hierarchydescendant', 'local_course_banner_builder', $label);
    }

    /**
     * Returns banner image URL for a stored record.
     *
     * @param \stdClass $record
     * @return \moodle_url|null
     */
    public static function get_banner_image_url(\stdClass $record): ?\moodle_url {
        $file = self::get_banner_image_file($record);
        if (!$file) {
            return null;
        }

        return \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }

    /**
     * Returns banner image stored file for a record.
     *
     * @param \stdClass $record
     * @return \stored_file|null
     */
    public static function get_banner_image_file(\stdClass $record): ?\stored_file {
        $context = \context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'local_course_banner_builder',
            self::FILEAREA,
            $record->fileitemid,
            'itemid, filepath, filename',
            false
        );

        if (empty($files)) {
            return null;
        }

        return reset($files);
    }

    /**
     * Get effective banner layers for a course.
     *
     * @param \stdClass $course
     * @return array
     */
    public static function get_banner_layers_for_course(\stdClass $course): array {
        if (empty($course->category)) {
            return [];
        }

        $records = self::sort_layer_specs(self::get_enabled_category_elements_for_course($course));
        $layers = [];
        foreach ($records as $index => $layer) {
            $imageurl = self::get_banner_image_url($layer['record']);
            if (!$imageurl) {
                continue;
            }
            $layers[] = [
                'index' => $index + 1,
                'url' => $imageurl->out(false),
            ];
        }

        return $layers;
    }

    /**
     * Get the effective banner image URL for a course.
     *
     * @param \stdClass $course
     * @return \moodle_url|null
     */
    public static function get_banner_image_url_for_course(\stdClass $course): ?\moodle_url {
        $layers = self::get_banner_layers_for_course($course);
        if (empty($layers)) {
            return null;
        }

        return new \moodle_url($layers[count($layers) - 1]['url']);
    }

    /**
     * Sync all courses in a category tree.
     *
     * @param int $categoryid
     * @return void
     */
    public static function sync_courses_for_category_tree(int $categoryid): void {
        global $DB;

        if (!$categoryid) {
            return;
        }

        $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id,path', IGNORE_MISSING);
        if (!$category) {
            return;
        }

        $categorypath = trim((string)$category->path, '/');
        if ($categorypath === '') {
            return;
        }

        $likepath = $DB->sql_like('path', ':path', false, false);
        $categoryids = $DB->get_fieldset_select(
            'course_categories',
            'id',
            'id = :categoryid OR ' . $likepath,
            [
                'categoryid' => $categoryid,
                'path' => '/' . $categorypath . '/%',
            ]
        );
        [$insql, $params] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
        $courses = $DB->get_records_select('course', 'category ' . $insql, $params, '', 'id, category');

        foreach ($courses as $course) {
            try {
                self::sync_course_overview_image($course);
            } catch (\Throwable $e) {
                debugging('Course banner sync failed for course ' . $course->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Sync all configured category banners to courses.
     *
     * @return void
     */
    public static function sync_all_courses_from_category_banners(): void {
        global $DB;

        $categoryids = $DB->get_fieldset_select(
            'local_course_banner_elements',
            'DISTINCT categoryid',
            'categoryid IS NOT NULL'
        );

        foreach ($categoryids as $categoryid) {
            self::sync_courses_for_category_tree((int)$categoryid);
        }
    }

    /**
     * Synchronise the managed overview image for one course.
     *
     * @param \stdClass $course
     * @return void
     */
    public static function sync_course_overview_image(\stdClass $course): void {
        if (empty($course->id) || empty($course->category)) {
            return;
        }

        $context = \context_course::instance($course->id);
        self::delete_managed_course_overview_images($context->id);
        self::delete_managed_course_card_images($context->id);

        if (self::course_has_custom_overview_image($context->id)) {
            self::purge_course_caches();
            return;
        }

        $records = self::get_enabled_category_elements_for_course($course);
        if (empty($records)) {
            self::purge_course_caches();
            return;
        }

        $revision = self::get_layers_revision($records);
        $sourcefile = null;
        $generatedfilepath = null;
        if (count($records) === 1 && ($records[0]['fitmode'] ?? self::FIT_MODE_BANNER) === self::FIT_MODE_ORIGINAL) {
            $sourcefile = self::get_banner_image_file($records[0]['record']);
        } else {
            $generatedfilepath = self::build_course_banner_image($records, $course->id);
        }

        if (!$sourcefile && !$generatedfilepath) {
            self::purge_course_caches();
            return;
        }

        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => self::MANAGED_OVERVIEW_PREFIX . '_' . $revision . '.png',
        ];

        $fs = get_file_storage();
        if ($sourcefile) {
            $extension = pathinfo($sourcefile->get_filename(), PATHINFO_EXTENSION);
            $fileinfo['filename'] = self::MANAGED_OVERVIEW_PREFIX . '_' . $revision . ($extension ? '.' . $extension : '.png');
            $fs->create_file_from_storedfile($fileinfo, $sourcefile);
        } else {
            $fs->create_file_from_pathname($fileinfo, $generatedfilepath);
        }

        $cardfilepath = self::build_course_card_image($records, $course->id);
        if ($cardfilepath) {
            $fs->create_file_from_pathname([
                'contextid' => $context->id,
                'component' => 'local_course_banner_builder',
                'filearea' => self::CARD_FILEAREA,
                'itemid' => 0,
                'filepath' => '/',
                'filename' => self::MANAGED_CARD_PREFIX . '_' . $revision . '.png',
            ], $cardfilepath);
        }
        self::purge_course_caches();
    }

    /**
     * Delete plugin-managed overview images from a course.
     *
     * @param int $contextid
     * @return void
     */
    public static function delete_managed_course_overview_images(int $contextid): void {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'course', 'overviewfiles', 0, 'filename', false);
        foreach ($files as $file) {
            if (str_starts_with($file->get_filename(), self::MANAGED_OVERVIEW_PREFIX)) {
                $file->delete();
            }
        }
    }

    /**
     * Delete plugin-managed card thumbnails from a course context.
     *
     * @param int $contextid
     * @return void
     */
    public static function delete_managed_course_card_images(int $contextid): void {
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, 'local_course_banner_builder', self::CARD_FILEAREA, 0);
    }

    /**
     * Get the generated course card thumbnail URL when available.
     *
     * @param int $courseid
     * @return \moodle_url|null
     */
    public static function get_course_card_image_url(int $courseid): ?\moodle_url {
        if (!$courseid) {
            return null;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'local_course_banner_builder',
            self::CARD_FILEAREA,
            0,
            'filename DESC',
            false
        );

        foreach ($files as $file) {
            if ($file->is_valid_image() && str_starts_with($file->get_filename(), self::MANAGED_CARD_PREFIX)) {
                return \moodle_url::make_pluginfile_url(
                    $context->id,
                    'local_course_banner_builder',
                    self::CARD_FILEAREA,
                    0,
                    '/',
                    $file->get_filename()
                );
            }
        }

        return null;
    }

    /**
     * Determine whether the course already has a custom overview image.
     *
     * @param int $contextid
     * @return bool
     */
    public static function course_has_custom_overview_image(int $contextid): bool {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'course', 'overviewfiles', 0, 'filename', false);
        foreach ($files as $file) {
            if (!$file->is_valid_image()) {
                continue;
            }
            if (!str_starts_with($file->get_filename(), self::MANAGED_OVERVIEW_PREFIX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a deterministic revision for generated course overview files.
     *
     * @param array $layerspecs
     * @return string
     */
    protected static function get_layers_revision(array $layerspecs): string {
        $layerspecs = self::sort_layer_specs($layerspecs);
        $parts = [];
        foreach ($layerspecs as $position => $layerspec) {
            $record = $layerspec['record'];
            $file = self::get_banner_image_file($record);
            $parts[] = implode(':', [
                $position,
                (int)($layerspec['categoryorder'] ?? 0),
                (int)$record->id,
                (int)$record->categoryid,
                (int)$record->sortorder,
                (int)$record->timemodified,
                (string)($layerspec['fitmode'] ?? ''),
                $file ? $file->get_contenthash() : '',
            ]);
        }

        return substr(sha1(implode('|', $parts)), 0, 12);
    }

    /**
     * Purge course-related caches after overview image changes.
     *
     * @return void
     */
    protected static function purge_course_caches(): void {
        \cache_helper::purge_by_event('changesincourse');
    }

    /**
     * Export the selected category current state for the admin page.
     *
     * @param int $categoryid
     * @return array
     */
    public static function export_selected_category(int $categoryid): array {
        if (!$categoryid) {
            return [
                'hasselection' => false,
            ];
        }

        $category = \core_course_category::get($categoryid, IGNORE_MISSING);
        $settings = self::get_category_settings($categoryid);
        $hasstoredsettings = !empty($settings->id);
        $compositionmode = $settings->compositionmode ?? self::MODE_RANDOM;
        $elements = [];
        foreach (self::get_category_elements($categoryid) as $record) {
            $imageurl = self::get_banner_image_url($record);
            $formid = 'layer-inline-' . $record->id;
            $fitoverride = '';
            if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
                $fitoverride = (string)($record->fitmodeoverride ?? '');
            }
            $sourcefitmode = $settings->fitmode ?? self::FIT_MODE_BANNER;
            if ($fitoverride === $sourcefitmode) {
                $fitoverride = '';
            }
            $elements[] = [
                'id' => (int)$record->id,
                'formid' => $formid,
                'name' => $record->name ?: get_string('bannerimage', 'local_course_banner_builder') . ' #' . $record->id,
                'sortorder' => (int)$record->sortorder,
                'enabledlabel' => $record->isenabled ? get_string('yes') : get_string('no'),
                'enabledchecked' => (bool)$record->isenabled,
                'imageurl' => $imageurl ? $imageurl->out(false) : '',
                'categoryid' => $categoryid,
                'sesskey' => sesskey(),
                'fitoverrideoptions' => self::export_fit_override_options($fitoverride),
                'hasfitoverride' => $fitoverride !== '',
                'fitoverridehelp' => get_string('fitoverridehelp', 'local_course_banner_builder'),
                'fitoverridecellclass' => $fitoverride !== '' ? 'local-course-banner-builder-override-cell' : '',
                'editurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'categoryid' => $categoryid,
                    'elementid' => $record->id,
                ]))->out(false),
                'deleteurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'categoryid' => $categoryid,
                    'deleteelementid' => $record->id,
                    'sesskey' => sesskey(),
                ]))->out(false),
            ];
        }
        $hasconfiguration = $hasstoredsettings || !empty($elements);

        return [
            'hasselection' => (bool)$category,
            'categoryid' => $categoryid,
            'categoryname' => $category ? format_string(strip_tags($category->get_nested_name())) : '',
            'hasconfiguration' => $hasconfiguration,
            'compositionmodelabel' => self::get_category_mode_label($categoryid),
            'iscumulative' => $compositionmode === self::MODE_CUMULATIVE,
            'fitmodelabel' => self::get_category_fit_mode_label($categoryid),
            'fitapplyscopelabel' => self::get_category_fit_scope_label($categoryid),
            'deletecategoryurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                'categoryid' => $categoryid,
                'deletecategorycontent' => $categoryid,
            ]))->out(false),
            'deletecategoryimagesurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                'categoryid' => $categoryid,
                'deletecategoryimages' => $categoryid,
            ]))->out(false),
            'candeletecategory' => $hasconfiguration,
            'candeleteimages' => !empty($elements),
            'sesskey' => sesskey(),
            'haselements' => !empty($elements),
            'elements' => $elements,
        ];
    }

    /**
     * Get all enabled category elements for a course from root to leaf.
     *
     * @param \stdClass $course
     * @return array
     */
    public static function get_enabled_category_elements_for_course(\stdClass $course): array {
        if (empty($course->category)) {
            return [];
        }

        $targetcategoryid = (int)$course->category;
        $categoryids = self::get_category_chain($targetcategoryid);
        $startindex = 0;
        foreach ($categoryids as $index => $categoryid) {
            $settings = self::get_category_settings($categoryid);
            if (($settings->fitapplyscope ?? self::FIT_SCOPE_SELF) !== self::FIT_SCOPE_SELF) {
                continue;
            }

            $startindex = $index;
        }

        $categoryids = array_slice($categoryids, $startindex);
        $boundarycategoryid = $categoryids[0] ?? 0;
        $records = [];
        foreach ($categoryids as $categoryindex => $categoryid) {
            $settings = self::get_category_settings($categoryid);
            if (
                $categoryid !== $targetcategoryid &&
                $categoryid !== $boundarycategoryid &&
                ($settings->fitapplyscope ?? self::FIT_SCOPE_SELF) === self::FIT_SCOPE_SELF
            ) {
                continue;
            }

            $categoryrecords = [];
            foreach (self::get_category_elements($categoryid, true) as $record) {
                if (!self::get_banner_image_file($record)) {
                    continue;
                }
                $categoryrecords[] = $record;
            }

            if (empty($categoryrecords)) {
                continue;
            }

            $compositionmode = $settings->compositionmode ?? self::MODE_CUMULATIVE;
            if ($compositionmode === self::MODE_RANDOM) {
                $selectedrecord = $categoryrecords[random_int(0, count($categoryrecords) - 1)];
                $records[] = [
                    'record' => $selectedrecord,
                    'fitmode' => self::get_effective_fit_mode_for_record($selectedrecord, $targetcategoryid),
                    'categoryorder' => $categoryindex,
                ];
            } else {
                foreach ($categoryrecords as $record) {
                    $records[] = [
                        'record' => $record,
                        'fitmode' => self::get_effective_fit_mode_for_record($record, $targetcategoryid),
                        'categoryorder' => $categoryindex,
                    ];
                }
            }
        }

        return self::sort_layer_specs($records);
    }

    /**
     * Sort layer specs in the exact draw order used by generated course banners.
     *
     * Lower sort orders are painted first. Higher sort orders are painted later
     * and therefore appear above previous layers.
     *
     * @param array $layerspecs
     * @return array
     */
    protected static function sort_layer_specs(array $layerspecs): array {
        usort($layerspecs, static function(array $a, array $b): int {
            $arecord = $a['record'];
            $brecord = $b['record'];
            $categorycompare = ((int)($a['categoryorder'] ?? 0)) <=> ((int)($b['categoryorder'] ?? 0));
            if ($categorycompare !== 0) {
                return $categorycompare;
            }

            $sortcompare = ((int)$arecord->sortorder) <=> ((int)$brecord->sortorder);
            if ($sortcompare !== 0) {
                return $sortcompare;
            }

            return ((int)$arecord->id) <=> ((int)$brecord->id);
        });

        return $layerspecs;
    }

    /**
     * Returns the human label of the category mode.
     *
     * @param int $categoryid
     * @return string
     */
    protected static function get_category_mode_label(int $categoryid): string {
        $mode = self::get_category_settings($categoryid)->compositionmode ?? self::MODE_CUMULATIVE;
        $labels = self::get_composition_mode_options();
        return $labels[$mode] ?? $labels[self::MODE_RANDOM];
    }

    /**
     * Returns the human label of the category fit mode.
     *
     * @param int $categoryid
     * @return string
     */
    protected static function get_category_fit_mode_label(int $categoryid): string {
        $mode = self::get_category_settings($categoryid)->fitmode ?? self::FIT_MODE_BANNER;
        $labels = self::get_fit_mode_options();
        return $labels[$mode] ?? $labels[self::FIT_MODE_ORIGINAL];
    }

    /**
     * Returns the human label of the category fit scope.
     *
     * @param int $categoryid
     * @return string
     */
    protected static function get_category_fit_scope_label(int $categoryid): string {
        $scope = self::get_category_settings($categoryid)->fitapplyscope ?? self::FIT_SCOPE_SELF;
        $labels = self::get_fit_apply_scope_options();
        return $labels[$scope] ?? $labels[self::FIT_SCOPE_SELF];
    }

    /**
     * Export element thumbnails for the admin listing.
     *
     * @param array $elements
     * @return array
     */
    protected static function export_element_thumbnails(array $elements): array {
        $thumbnails = [];
        $count = 0;
        foreach ($elements as $element) {
            $imageurl = self::get_banner_image_url($element);
            if (!$imageurl) {
                continue;
            }

            $thumbnails[] = [
                'imageurl' => $imageurl->out(false),
                'name' => $element->name ?: get_string('bannerimage', 'local_course_banner_builder') . ' #' . $element->id,
            ];
            $count++;
            if ($count >= self::ADMIN_THUMB_LIMIT) {
                break;
            }
        }

        return $thumbnails;
    }

    /**
     * Determine effective fit mode for a layer category on a target course category.
     *
     * @param int $layercategoryid
     * @param int $targetcategoryid
     * @return string
     */
    protected static function get_effective_fit_mode_for_record(\stdClass $record, int $targetcategoryid): string {
        $fitmodeoverride = '';
        if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
            $fitmodeoverride = (string)($record->fitmodeoverride ?? '');
        }
        if ($fitmodeoverride !== '') {
            return $fitmodeoverride;
        }

        $settings = self::get_category_settings((int)$record->categoryid);
        $fitmode = $settings->fitmode ?? self::FIT_MODE_BANNER;
        $scope = $settings->fitapplyscope ?? self::FIT_SCOPE_SELF;

        if ((int)$record->categoryid === $targetcategoryid) {
            return $fitmode;
        }

        if ($scope === self::FIT_SCOPE_DESCENDANTS) {
            return $fitmode;
        }

        return self::FIT_MODE_ORIGINAL;
    }

    /**
     * Export select options for the layer fit override.
     *
     * @param string $selected
     * @return array
     */
    protected static function export_fit_override_options(string $selected): array {
        $options = [[
            'value' => '',
            'label' => get_string('fitoverride:categorydefault', 'local_course_banner_builder'),
            'selected' => $selected === '',
        ]];

        foreach (self::get_fit_mode_options() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $selected === $value,
            ];
        }

        return $options;
    }

    /**
     * Delete all plugin configuration.
     *
     * @return void
     */
    public static function delete_all_configuration(): void {
        global $DB;

        $categoryids = $DB->get_fieldset_select('local_course_banner_elements', 'DISTINCT categoryid', 'categoryid IS NOT NULL');
        foreach ($DB->get_records('local_course_banner_elements') as $element) {
            self::delete_banner_element((int)$element->id);
        }
        $DB->delete_records('local_course_banner_order');
        foreach (array_unique(array_map('intval', $categoryids)) as $categoryid) {
            self::sync_courses_for_category_tree($categoryid);
        }
    }

    /**
     * Export current plugin configuration as a versioned array.
     *
     * @return array
     */
    public static function export_configuration(): array {
        global $DB, $CFG;

        $export = [
            'schema' => self::CONFIG_EXPORT_VERSION,
            'archiveformat' => 'json-with-embedded-images',
            'ziparchiveplanned' => true,
            'pluginversion' => $CFG->version,
            'exportedat' => time(),
            'categories' => [],
        ];

        $categoryids = $DB->get_fieldset_select('local_course_banner_elements', 'DISTINCT categoryid', 'categoryid IS NOT NULL');
        foreach ($categoryids as $categoryid) {
            $categoryrecord = $DB->get_record('course_categories', ['id' => $categoryid], 'id,name,idnumber,path,parent');
            if (!$categoryrecord) {
                continue;
            }

            $settings = self::get_category_settings((int)$categoryid);
            $elements = [];
            foreach (self::get_category_elements((int)$categoryid) as $element) {
                $file = self::get_banner_image_file($element);
                $elements[] = [
                    'name' => $element->name,
                    'sortorder' => (int)$element->sortorder,
                    'isenabled' => (int)$element->isenabled,
                    'fitmodeoverride' => self::table_field_exists('local_course_banner_elements', 'fitmodeoverride') ?
                        ($element->fitmodeoverride ?? null) : null,
                    'filename' => $file ? $file->get_filename() : null,
                    'archivepath' => $file ? 'images/category_' . $categoryid . '/element_' . $element->id . '_' .
                        clean_filename($file->get_filename()) : null,
                    'contentbase64' => $file ? base64_encode($file->get_content()) : null,
                ];
            }

            $pathnames = [];
            $pathids = array_filter(array_map('intval', explode('/', trim((string)$categoryrecord->path, '/'))));
            foreach ($pathids as $pathid) {
                $pathcategory = $DB->get_record('course_categories', ['id' => $pathid], 'name');
                if ($pathcategory) {
                    $pathnames[] = $pathcategory->name;
                }
            }

            $export['categories'][] = [
                'idnumber' => $categoryrecord->idnumber,
                'name' => $categoryrecord->name,
                'pathnames' => $pathnames,
                'settings' => [
                    'compositionmode' => $settings->compositionmode ?? self::MODE_RANDOM,
                    'fitmode' => $settings->fitmode ?? self::FIT_MODE_ORIGINAL,
                    'fitapplyscope' => $settings->fitapplyscope ?? self::FIT_SCOPE_DESCENDANTS,
                ],
                'elements' => $elements,
            ];
        }

        return $export;
    }

    /**
     * Resolve one exported category to a local category id.
     *
     * @param array $categorydata
     * @return int
     */
    protected static function resolve_import_category_id(array $categorydata, bool $createifmissing = true): int {
        global $DB;

        if (!empty($categorydata['idnumber'])) {
            $match = $DB->get_record('course_categories', ['idnumber' => $categorydata['idnumber']], 'id');
            if ($match) {
                return (int)$match->id;
            }
        }

        $parentid = 0;
        foreach (($categorydata['pathnames'] ?? []) as $pathname) {
            $match = $DB->get_record('course_categories', ['name' => $pathname, 'parent' => $parentid], 'id');
            if ($match) {
                $parentid = (int)$match->id;
                continue;
            }

            if (!$createifmissing) {
                return 0;
            }

            $newcategory = \core_course_category::create([
                'name' => (string)$pathname,
                'parent' => $parentid,
            ]);
            $parentid = (int)$newcategory->id;
        }

        if ($parentid && !empty($categorydata['idnumber'])) {
            $record = $DB->get_record('course_categories', ['id' => $parentid], '*', IGNORE_MISSING);
            if ($record && empty($record->idnumber)) {
                $record->idnumber = (string)$categorydata['idnumber'];
                $DB->update_record('course_categories', $record);
            }
        }

        return $parentid;
    }

    /**
     * Import a configuration export.
     *
     * @param string $json
     * @param bool $replaceall
     * @return array
     */
    public static function import_configuration(string $json, bool $replaceall = false): array {
        global $DB;

        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['schema']) || empty($data['categories']) || !is_array($data['categories'])) {
            throw new \coding_exception('Invalid course banner builder import payload.');
        }

        if ($replaceall) {
            self::delete_all_configuration();
        }

        $imported = 0;
        foreach ($data['categories'] as $categorydata) {
            $categoryid = self::resolve_import_category_id($categorydata);
            if (!$categoryid) {
                continue;
            }

            self::delete_category_content($categoryid);
            self::save_category_settings(
                $categoryid,
                (string)($categorydata['settings']['compositionmode'] ?? self::MODE_RANDOM),
                (string)($categorydata['settings']['fitmode'] ?? self::FIT_MODE_ORIGINAL),
                (string)($categorydata['settings']['fitapplyscope'] ?? self::FIT_SCOPE_DESCENDANTS)
            );

            foreach (($categorydata['elements'] ?? []) as $elementdata) {
                $record = self::create_category_element($categoryid);
                $record->name = (string)($elementdata['name'] ?? '');
                $record->sortorder = max(0, (int)($elementdata['sortorder'] ?? 0));
                $record->isenabled = empty($elementdata['isenabled']) ? 0 : 1;
                if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
                    $record->fitmodeoverride = $elementdata['fitmodeoverride'] ?? null;
                }
                $record->timemodified = time();
                $DB->update_record('local_course_banner_elements', $record);

                if (!empty($elementdata['contentbase64']) && !empty($elementdata['filename'])) {
                    self::store_content_in_element(
                        $record,
                        (string)$elementdata['filename'],
                        base64_decode((string)$elementdata['contentbase64'])
                    );
                }
            }

            self::sync_courses_for_category_tree($categoryid);
            $imported++;
        }

        return ['importedcategories' => $imported];
    }

    /**
     * Determine canvas dimensions for generated banners.
     *
     * @param array $layerspecs
     * @return array
     */
    protected static function get_canvas_dimensions(array $layerspecs): array {
        return [self::DEFAULT_CANVAS_WIDTH, self::DEFAULT_CANVAS_HEIGHT];
    }

    /**
     * Build the category chain from root to leaf.
     *
     * @param int $categoryid
     * @return array
     */
    protected static function get_category_chain(int $categoryid): array {
        global $DB;

        $chain = [];

        while ($categoryid > 0) {
            $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id,parent', IGNORE_MISSING);
            if (!$category) {
                break;
            }

            array_unshift($chain, (int)$category->id);
            $categoryid = (int)$category->parent;
        }

        return $chain;
    }

    /**
     * Build a composite banner image from category layers.
     *
     * @param array $layerspecs
     * @param int $courseid
     * @return string|null
     */
    protected static function build_course_banner_image(array $layerspecs, int $courseid): ?string {
        if (!function_exists('imagecreatetruecolor') || empty($layerspecs)) {
            return null;
        }
        raise_memory_limit(MEMORY_EXTRA);

        return self::build_composite_image(
            $layerspecs,
            self::DEFAULT_CANVAS_WIDTH,
            self::DEFAULT_CANVAS_HEIGHT,
            'course_' . $courseid . '_banner.png',
            false
        );
    }

    /**
     * Build a thumbnail-friendly course card image from category layers.
     *
     * @param array $layerspecs
     * @param int $courseid
     * @return string|null
     */
    protected static function build_course_card_image(array $layerspecs, int $courseid): ?string {
        if (!function_exists('imagecreatetruecolor') || empty($layerspecs)) {
            return null;
        }
        raise_memory_limit(MEMORY_EXTRA);

        return self::build_composite_image(
            $layerspecs,
            self::CARD_CANVAS_WIDTH,
            self::CARD_CANVAS_HEIGHT,
            'course_' . $courseid . '_card.png',
            true
        );
    }

    /**
     * Build a composite image from layer specs.
     *
     * @param array $layerspecs
     * @param int $width
     * @param int $height
     * @param string $filename
     * @param bool $cardmode
     * @return string|null
     */
    protected static function build_composite_image(
        array $layerspecs,
        int $width,
        int $height,
        string $filename,
        bool $cardmode = false
    ): ?string {
        $canvas = null;
        $layerspecs = self::sort_layer_specs($layerspecs);

        foreach ($layerspecs as $layerspec) {
            $record = $layerspec['record'];
            $fitmode = $layerspec['fitmode'] ?? self::FIT_MODE_BANNER;
            $file = self::get_banner_image_file($record);
            if (!$file) {
                continue;
            }

            $loadedlayer = self::load_layer_image($file);
            $layer = $loadedlayer['image'] ?? null;
            if (!$layer) {
                continue;
            }

            $layerwidth = imagesx($layer);
            $layerheight = imagesy($layer);

            if (!$canvas) {
                $canvas = imagecreatetruecolor($width, $height);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            }

            imagealphablending($canvas, true);
            if ($fitmode === self::FIT_MODE_BANNER) {
                if ($cardmode) {
                    self::copy_layer_cover($canvas, $layer, $width, $height, $layerwidth, $layerheight);
                } else {
                    imagecopyresampled($canvas, $layer, 0, 0, 0, 0, $width, $height, $layerwidth, $layerheight);
                }
            } else {
                $destinationx = (int)floor(($width - $layerwidth) / 2);
                $destinationy = (int)floor(($height - $layerheight) / 2);
                imagecopy($canvas, $layer, $destinationx, $destinationy, 0, 0, $layerwidth, $layerheight);
            }
            imagedestroy($layer);
            if (!empty($loadedlayer['tempfile'])) {
                @unlink($loadedlayer['tempfile']);
            }
        }

        if (!$canvas) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $tempdir = make_temp_directory('local_course_banner_builder');
        $filepath = $tempdir . DIRECTORY_SEPARATOR . $filename;
        imagepng($canvas, $filepath);
        imagedestroy($canvas);

        return $filepath;
    }

    /**
     * Copy a layer so it covers the target canvas without distorting its ratio.
     *
     * @param resource|\GdImage $canvas
     * @param resource|\GdImage $layer
     * @param int $width
     * @param int $height
     * @param int $layerwidth
     * @param int $layerheight
     * @return void
     */
    protected static function copy_layer_cover($canvas, $layer, int $width, int $height, int $layerwidth, int $layerheight): void {
        if ($layerwidth <= 0 || $layerheight <= 0) {
            return;
        }

        $scale = max($width / $layerwidth, $height / $layerheight);
        $targetwidth = (int)ceil($layerwidth * $scale);
        $targetheight = (int)ceil($layerheight * $scale);
        $destinationx = (int)floor(($width - $targetwidth) / 2);
        $destinationy = (int)floor(($height - $targetheight) / 2);

        imagecopyresampled(
            $canvas,
            $layer,
            $destinationx,
            $destinationy,
            0,
            0,
            $targetwidth,
            $targetheight,
            $layerwidth,
            $layerheight
        );
    }

    /**
     * Load a stored image file into GD while avoiding an additional in-memory file content string.
     *
     * @param \stored_file $file
     * @return array{image:mixed,tempfile:string|null}|array
     */
    protected static function load_layer_image(\stored_file $file): array {
        $tempfile = $file->copy_content_to_temp('local_course_banner_builder', 'layer_');
        if (!$tempfile) {
            return [];
        }

        $mimetype = $file->get_mimetype();
        $image = null;
        if ($mimetype === 'image/jpeg' || $mimetype === 'image/pjpeg') {
            $image = @imagecreatefromjpeg($tempfile);
        } else if ($mimetype === 'image/png') {
            $image = @imagecreatefrompng($tempfile);
        } else if ($mimetype === 'image/gif') {
            $image = @imagecreatefromgif($tempfile);
        } else if ($mimetype === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($tempfile);
        }

        if (!$image) {
            @unlink($tempfile);
            return [];
        }

        return [
            'image' => $image,
            'tempfile' => $tempfile,
        ];
    }
}
