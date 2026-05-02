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
    /** @var string Debug log filename for temporary banner builder diagnostics. */
    protected const DEBUG_LOG_FILE = 'local_course_banner_builder_debug.log';
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
    public const FIT_MODE_COVER = 'cover';
    /** @var string */
    public const FIT_MODE_ORIGINAL = 'original';
    /** @var string */
    public const FIT_MODE_CUSTOM = 'custom';
    /** @var string */
    public const FIT_SCOPE_SELF = 'self';
    /** @var string */
    public const FIT_SCOPE_DESCENDANTS = 'descendants';
    /** @var string */
    public const SOURCE_TYPE_CATEGORY = 'category';
    /** @var string */
    public const SOURCE_TYPE_CUSTOMFIELD = 'customfield';
    /** @var string */
    public const CUSTOMFIELD_PRIORITY_CATEGORY = 'category';
    /** @var string */
    public const CUSTOMFIELD_PRIORITY_CUSTOMFIELD = 'customfield';
    /** @var string */
    public const CUSTOMFIELD_PRIORITY_APPEND = 'append';
    /** @var string */
    public const POSITION_CENTER = 'center';
    /** @var string */
    public const POSITION_TOP = 'top';
    /** @var string */
    public const POSITION_BOTTOM = 'bottom';
    /** @var string */
    public const POSITION_LEFT = 'left';
    /** @var string */
    public const POSITION_RIGHT = 'right';
    /** @var string */
    public const POSITION_TOP_LEFT = 'top-left';
    /** @var string */
    public const POSITION_TOP_RIGHT = 'top-right';
    /** @var string */
    public const POSITION_BOTTOM_LEFT = 'bottom-left';
    /** @var string */
    public const POSITION_BOTTOM_RIGHT = 'bottom-right';
    /** @var string */
    public const BORDER_STYLE_SOLID = 'solid';
    /** @var string */
    public const BORDER_STYLE_DASHED = 'dashed';
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
     * Build a stable source key for a category source.
     *
     * @param int $categoryid
     * @return string
     */
    public static function get_category_source_key(int $categoryid): string {
        return self::SOURCE_TYPE_CATEGORY . ':' . max(0, $categoryid);
    }

    /**
     * Build a stable source key for one enabled custom field value.
     *
     * @param int $fieldid
     * @param string $value
     * @return string
     */
    public static function get_customfield_source_key(int $fieldid, string $value): string {
        return self::SOURCE_TYPE_CUSTOMFIELD . ':' . max(0, $fieldid) . ':' . sha1(self::normalise_customfield_value($value));
    }

    /**
     * Normalise custom field values before comparing/storing them.
     *
     * @param string $value
     * @return string
     */
    protected static function normalise_customfield_value(string $value): string {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /**
     * Clean one custom field value for UI display/search without changing stored source keys.
     *
     * @param string $value
     * @return string
     */
    protected static function clean_customfield_display_value(string $value): string {
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /**
     * Source key for a stored record, including legacy category-only rows.
     *
     * @param \stdClass $record
     * @return string
     */
    public static function get_record_source_key(\stdClass $record): string {
        if (self::table_field_exists('local_course_banner_elements', 'sourcekey') && !empty($record->sourcekey)) {
            return (string)$record->sourcekey;
        }
        if (self::table_field_exists('local_course_banner_order', 'sourcekey') && !empty($record->sourcekey)) {
            return (string)$record->sourcekey;
        }
        return self::get_category_source_key((int)($record->categoryid ?? 0));
    }

    /**
     * Build default category settings without persisting them.
     *
     * @param int $categoryid
     * @return \stdClass
     */
    protected static function get_default_category_settings(int $categoryid): \stdClass {
        $record = (object)[
            'id' => 0,
            'categoryid' => $categoryid,
            'sourcetype' => self::SOURCE_TYPE_CATEGORY,
            'sourcekey' => self::get_category_source_key($categoryid),
            'customfieldvalue' => null,
            'elementids' => null,
            'coursecustomfieldid' => null,
            'compositionmode' => self::MODE_RANDOM,
            'fitmode' => self::FIT_MODE_ORIGINAL,
            'fitapplyscope' => self::FIT_SCOPE_SELF,
            'customfieldpriority' => self::CUSTOMFIELD_PRIORITY_CATEGORY,
            'sourceparentkey' => '',
            'sourceisroot' => 0,
            'sourceinheritchildren' => 0,
            'timemodified' => 0,
        ];
        return $record;
    }

    /**
     * Build default settings for any source without persisting them.
     *
     * @param \stdClass $source
     * @return \stdClass
     */
    protected static function get_default_source_settings(\stdClass $source): \stdClass {
        $categoryid = $source->type === self::SOURCE_TYPE_CATEGORY ? (int)$source->categoryid : null;
        $settings = self::get_default_category_settings((int)($categoryid ?? 0));
        $settings->categoryid = $categoryid;
        $settings->sourcetype = $source->type;
        $settings->sourcekey = $source->sourcekey;
        $settings->coursecustomfieldid = $source->customfieldid ?? null;
        $settings->customfieldvalue = $source->customfieldvalue ?? null;
        $settings->fitapplyscope = self::FIT_SCOPE_SELF;
        $settings->customfieldpriority = self::CUSTOMFIELD_PRIORITY_CATEGORY;
        return $settings;
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
     * Fill missing source settings with defaults.
     *
     * @param \stdClass $record
     * @param \stdClass $source
     * @return \stdClass
     */
    protected static function normalise_source_settings(\stdClass $record, \stdClass $source): \stdClass {
        $defaults = self::get_default_source_settings($source);
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
                   AND f.type IN (:texttype, :textareatype, :selecttype)
              ORDER BY c.sortorder, f.sortorder, f.name";
        $records = $DB->get_records_sql($sql, [
            'component' => 'core_course',
            'area' => 'course',
            'texttype' => 'text',
            'textareatype' => 'textarea',
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
     * Returns custom field value source choices enabled in plugin settings.
     *
     * Text fields are exposed from distinct non-empty course values. Select fields
     * are exposed from their configured options, because the option itself is the
     * stable banner source.
     *
     * @return array
     */
    public static function get_enabled_customfield_source_options(): array {
        global $DB;

        $enabled = get_config('local_course_banner_builder', 'enabledcustomfields');
        $enabledids = array_filter(array_map('intval', explode(',', (string)$enabled)));
        if (empty($enabledids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($enabledids, SQL_PARAMS_NAMED);
        $records = $DB->get_records_select('customfield_field', 'id ' . $insql, $params, '', 'id,name,shortname,type,configdata');
        $usedkeys = self::get_used_source_keys();
        $items = [];

        foreach ($enabledids as $fieldid) {
            if (empty($records[$fieldid])) {
                continue;
            }
            $field = $records[$fieldid];
            $fieldname = format_string($field->name);
            foreach (self::get_customfield_source_values($field) as $rawvalue => $displayvalue) {
                $sourcekey = self::get_customfield_source_key((int)$field->id, (string)$rawvalue);
                $displaylabel = self::clean_customfield_display_value((string)$displayvalue);
                $label = $fieldname . ': ' . $displaylabel;
                $isused = isset($usedkeys[$sourcekey]);

                $items[] = [
                    'id' => $sourcekey,
                    'sourcekey' => $sourcekey,
                    'fieldid' => (int)$field->id,
                    'value' => (string)$rawvalue,
                    'label' => self::shorten_source_label($label, self::SOURCE_LABEL_MAX_LENGTH),
                    'title' => $label,
                    'searchtext' => \core_text::strtolower($fieldname . ' ' . $displaylabel),
                    'isused' => $isused,
                ];
            }
        }

        return $items;
    }

    /**
     * Get used source keys from configured elements/settings.
     *
     * @return array
     */
    protected static function get_used_source_keys(): array {
        global $DB;

        $keys = [];
        if (self::table_field_exists('local_course_banner_elements', 'sourcekey')) {
            foreach ($DB->get_fieldset_select('local_course_banner_elements', 'DISTINCT sourcekey', 'sourcekey IS NOT NULL') as $key) {
                if ($key !== '') {
                    $keys[(string)$key] = true;
                }
            }
        }
        if (self::table_field_exists('local_course_banner_order', 'sourcekey')) {
            foreach ($DB->get_fieldset_select('local_course_banner_order', 'DISTINCT sourcekey', 'sourcekey IS NOT NULL') as $key) {
                if ($key !== '') {
                    $keys[(string)$key] = true;
                }
            }
        }
        foreach (self::get_used_category_ids() as $categoryid) {
            $keys[self::get_category_source_key((int)$categoryid)] = true;
        }

        return $keys;
    }

    /**
     * Return all known source options for parent-chain selectors.
     *
     * @param string $excludekey
     * @return array
     */
    public static function get_source_parent_options(string $excludekey = ''): array {
        $options = ['' => get_string('sourcechain:none', 'local_course_banner_builder')];

        foreach (self::get_category_source_options() as $option) {
            $key = self::get_category_source_key((int)$option['id']);
            if ($key === $excludekey) {
                continue;
            }
            $options[$key] = get_string('sourcechain:categoryprefix', 'local_course_banner_builder') . ' ' . $option['label'];
        }

        foreach (self::get_enabled_customfield_source_options() as $option) {
            $key = (string)($option['sourcekey'] ?? '');
            if ($key === '' || $key === $excludekey) {
                continue;
            }
            $options[$key] = get_string('sourcechain:customfieldprefix', 'local_course_banner_builder') . ' ' .
                (string)($option['title'] ?? $option['label'] ?? $key);
        }

        return $options;
    }

    /**
     * Return selectable values for one enabled custom field.
     *
     * @param \stdClass $field
     * @return array raw value => display value
     */
    protected static function get_customfield_source_values(\stdClass $field): array {
        global $DB;

        $values = [];
        if ($field->type === 'select') {
            foreach (self::get_select_field_options($field) as $rawvalue => $label) {
                $values[(string)$rawvalue] = $label;
            }
            return $values;
        }

        if (!in_array($field->type, ['text', 'textarea'], true)) {
            return [];
        }

        $sql = "SELECT DISTINCT COALESCE(NULLIF(charvalue, ''), NULLIF(shortcharvalue, ''), NULLIF(value, '')) AS fieldvalue
                  FROM {customfield_data}
                 WHERE fieldid = :fieldid
                   AND COALESCE(NULLIF(charvalue, ''), NULLIF(shortcharvalue, ''), NULLIF(value, '')) IS NOT NULL
              ORDER BY fieldvalue";
        $records = $DB->get_records_sql($sql, ['fieldid' => (int)$field->id]);
        foreach ($records as $record) {
            $value = self::normalise_customfield_value((string)$record->fieldvalue);
            if ($value !== '') {
                $values[$value] = $value;
            }
        }

        return $values;
    }

    /**
     * Extract select options from Moodle custom field configdata.
     *
     * @param \stdClass $field
     * @return array raw stored value => label
     */
    protected static function get_select_field_options(\stdClass $field): array {
        $config = json_decode((string)($field->configdata ?? ''), true);
        $options = [];
        $lines = preg_split('/\R/u', (string)($config['options'] ?? '')) ?: [];
        foreach ($lines as $index => $line) {
            $label = trim($line);
            if ($label === '') {
                continue;
            }
            // Moodle stores custom select values as a 1-based option index. 0 means no selection.
            $options[(string)($index + 1)] = $label;
        }
        return $options;
    }

    /**
     * Return the display label for a custom field source value.
     *
     * @param \stdClass $field
     * @param string $rawvalue
     * @return string
     */
    protected static function get_customfield_value_label(\stdClass $field, string $rawvalue): string {
        if ($field->type === 'select') {
            $options = self::get_select_field_options($field);
            return $options[$rawvalue] ?? $rawvalue;
        }
        return self::clean_customfield_display_value($rawvalue);
    }

    /**
     * Return whether front-end display enhancements are enabled.
     *
     * @return bool
     */
    public static function is_display_enabled(): bool {
        $enabled = get_config('local_course_banner_builder', 'enabled');
        return $enabled === false ? true : (bool)$enabled;
    }

    /**
     * Heuristically detect themes that already provide a course banner surface.
     *
     * @param string|null $themename
     * @return bool
     */
    public static function theme_seems_to_provide_course_banner(?string $themename = null): bool {
        global $CFG, $PAGE;

        $themename = strtolower((string)($themename ?? ($PAGE->theme->name ?? '')));
        if ($themename === '') {
            return false;
        }
        if ($themename === 'easyedu') {
            return true;
        }

        $themedir = $CFG->dirroot . '/theme/' . $themename;
        if (!is_dir($themedir)) {
            return false;
        }

        $patterns = ['page-header-banner', 'course-banner', 'course_header_banner', 'courseheaderbanner'];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themedir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getSize() > 524288) {
                continue;
            }
            $extension = strtolower($file->getExtension());
            if (!in_array($extension, ['php', 'mustache', 'scss', 'css', 'js'], true)) {
                continue;
            }
            $content = @file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }
            foreach ($patterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Resolve a source from request parameters.
     *
     * @param string $sourcekey
     * @param int $categoryid
     * @return \stdClass|null
     */
    public static function resolve_source_from_request(string $sourcekey = '', int $categoryid = 0): ?\stdClass {
        $sourcekey = clean_param($sourcekey, PARAM_RAW_TRIMMED);
        if ($sourcekey === '' && $categoryid > 0) {
            $sourcekey = self::get_category_source_key($categoryid);
        }
        return self::resolve_source($sourcekey);
    }

    /**
     * Resolve a source key to metadata.
     *
     * @param string $sourcekey
     * @return \stdClass|null
     */
    public static function resolve_source(string $sourcekey): ?\stdClass {
        global $DB;

        $sourcekey = trim($sourcekey);
        if (preg_match('/^category:(\d+)$/', $sourcekey, $matches)) {
            $categoryid = (int)$matches[1];
            if (!$categoryid || !$DB->record_exists('course_categories', ['id' => $categoryid])) {
                return null;
            }
            $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id,name,path,parent', IGNORE_MISSING);
            return (object)[
                'type' => self::SOURCE_TYPE_CATEGORY,
                'sourcekey' => self::get_category_source_key($categoryid),
                'categoryid' => $categoryid,
                'customfieldid' => null,
                'customfieldvalue' => null,
                'label' => $category ? self::get_category_nested_name_from_record($category) : '',
            ];
        }

        if (preg_match('/^customfield:(\d+):([a-f0-9]{40})$/', $sourcekey, $matches)) {
            $fieldid = (int)$matches[1];
            $field = $DB->get_record('customfield_field', ['id' => $fieldid], 'id,name,type,configdata', IGNORE_MISSING);
            if (!$field || !in_array($field->type, ['text', 'textarea', 'select'], true)) {
                return self::resolve_stored_customfield_source($sourcekey);
            }

            foreach (self::get_customfield_source_values($field) as $rawvalue => $displayvalue) {
                if (self::get_customfield_source_key($fieldid, (string)$rawvalue) !== $sourcekey) {
                    continue;
                }
                return (object)[
                    'type' => self::SOURCE_TYPE_CUSTOMFIELD,
                    'sourcekey' => $sourcekey,
                    'categoryid' => null,
                    'customfieldid' => $fieldid,
                    'customfieldvalue' => (string)$rawvalue,
                    'label' => format_string($field->name) . ': ' . self::clean_customfield_display_value((string)$displayvalue),
                ];
            }

            return self::resolve_stored_customfield_source($sourcekey);
        }

        return null;
    }

    /**
     * Resolve a custom field source that may no longer be present in field options.
     *
     * @param string $sourcekey
     * @return \stdClass|null
     */
    protected static function resolve_stored_customfield_source(string $sourcekey): ?\stdClass {
        global $DB;

        $record = null;
        if (self::table_field_exists('local_course_banner_order', 'sourcekey')) {
            $record = $DB->get_record('local_course_banner_order', ['sourcekey' => $sourcekey], '*', IGNORE_MISSING);
        }
        if (!$record && self::table_field_exists('local_course_banner_elements', 'sourcekey')) {
            $record = $DB->get_record('local_course_banner_elements', ['sourcekey' => $sourcekey], '*', IGNORE_MISSING);
        }
        if (!$record || empty($record->customfieldid)) {
            return null;
        }

        $field = $DB->get_record('customfield_field', ['id' => $record->customfieldid], 'id,name,type,configdata', IGNORE_MISSING);
        $rawvalue = (string)($record->customfieldvalue ?? '');
        $label = $field ? format_string($field->name) . ': ' . self::get_customfield_value_label($field, $rawvalue) : $rawvalue;
        return (object)[
            'type' => self::SOURCE_TYPE_CUSTOMFIELD,
            'sourcekey' => $sourcekey,
            'categoryid' => null,
            'customfieldid' => (int)$record->customfieldid,
            'customfieldvalue' => $rawvalue,
            'label' => $label,
        ];
    }

    /**
     * Priority choices for custom field sources.
     *
     * @return array
     */
    public static function get_customfield_priority_options(): array {
        return [
            self::CUSTOMFIELD_PRIORITY_CATEGORY => get_string('customfieldpriority:category', 'local_course_banner_builder'),
            self::CUSTOMFIELD_PRIORITY_CUSTOMFIELD => get_string('customfieldpriority:customfield', 'local_course_banner_builder'),
            self::CUSTOMFIELD_PRIORITY_APPEND => get_string('customfieldpriority:append', 'local_course_banner_builder'),
        ];
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
    public static function get_fit_mode_options(bool $includecustom = false): array {
        $options = [
            self::FIT_MODE_BANNER => get_string('fitmode:bannerfit', 'local_course_banner_builder'),
            self::FIT_MODE_COVER => get_string('fitmode:cover', 'local_course_banner_builder'),
            self::FIT_MODE_ORIGINAL => get_string('fitmode:original', 'local_course_banner_builder'),
        ];
        if ($includecustom) {
            $options[self::FIT_MODE_CUSTOM] = get_string('fitmode:custom', 'local_course_banner_builder');
        }
        return $options;
    }

    /**
     * Returns fit mode options that are still editable in the UI.
     *
     * @param bool $includecustom
     * @return array
     */
    public static function get_editable_fit_mode_options(bool $includecustom = false): array {
        $options = [
            self::FIT_MODE_BANNER => get_string('fitmode:bannerfit', 'local_course_banner_builder'),
            self::FIT_MODE_COVER => get_string('fitmode:cover', 'local_course_banner_builder'),
            self::FIT_MODE_ORIGINAL => get_string('fitmode:original', 'local_course_banner_builder'),
        ];
        if ($includecustom) {
            $options[self::FIT_MODE_CUSTOM] = get_string('fitmode:custom', 'local_course_banner_builder');
        }
        return $options;
    }

    /**
     * Returns available layer anchor positions.
     *
     * @return array
     */
    public static function get_position_anchor_options(): array {
        return [
            self::POSITION_CENTER => get_string('positionanchor:center', 'local_course_banner_builder'),
            self::POSITION_TOP => get_string('positionanchor:top', 'local_course_banner_builder'),
            self::POSITION_BOTTOM => get_string('positionanchor:bottom', 'local_course_banner_builder'),
            self::POSITION_LEFT => get_string('positionanchor:left', 'local_course_banner_builder'),
            self::POSITION_RIGHT => get_string('positionanchor:right', 'local_course_banner_builder'),
            self::POSITION_TOP_LEFT => get_string('positionanchor:top-left', 'local_course_banner_builder'),
            self::POSITION_TOP_RIGHT => get_string('positionanchor:top-right', 'local_course_banner_builder'),
            self::POSITION_BOTTOM_LEFT => get_string('positionanchor:bottom-left', 'local_course_banner_builder'),
            self::POSITION_BOTTOM_RIGHT => get_string('positionanchor:bottom-right', 'local_course_banner_builder'),
        ];
    }

    /**
     * Returns available border styles.
     *
     * @return array
     */
    public static function get_border_style_options(): array {
        return [
            self::BORDER_STYLE_SOLID => get_string('borderstyle:solid', 'local_course_banner_builder'),
            self::BORDER_STYLE_DASHED => get_string('borderstyle:dashed', 'local_course_banner_builder'),
        ];
    }

    /**
     * Normalise one layer position anchor.
     *
     * @param string $anchor
     * @return string
     */
    protected static function normalise_position_anchor(string $anchor): string {
        $allowed = array_keys(self::get_position_anchor_options());
        return in_array($anchor, $allowed, true) ? $anchor : self::POSITION_CENTER;
    }

    /**
     * Normalise one border style token.
     *
     * @param string $style
     * @return string
     */
    protected static function normalise_border_style(string $style): string {
        $allowed = array_keys(self::get_border_style_options());
        return in_array($style, $allowed, true) ? $style : self::BORDER_STYLE_SOLID;
    }

    /**
     * Clamp a percentage-like value used for offsets around the banner.
     *
     * @param float $value
     * @param float $min
     * @param float $max
     * @return float
     */
    protected static function normalise_percentage(float $value, float $min = 0.0, float $max = 300.0): float {
        return max($min, min($max, $value));
    }

    /**
     * Clamp a float to the [0, 1] range.
     *
     * @param float $value
     * @param float $default
     * @return float
     */
    protected static function normalise_unit_float(float $value, float $default = 0.0): float {
        if (!is_finite($value)) {
            return $default;
        }
        return max(0.0, min(1.0, $value));
    }

    /**
     * Keep a supported color string format for borders.
     *
     * @param string $color
     * @return string
     */
    protected static function normalise_color_string(string $color): string {
        $color = trim($color);
        if ($color === '') {
            return '#FFFFFF';
        }
        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color)) {
            return $color;
        }
        if (preg_match('/^rgba?\(\s*[-\d.,%\s]+\)$/i', $color)) {
            return $color;
        }
        return '#FFFFFF';
    }

    /**
     * Normalise the list of border sides.
     *
     * @param array $sides
     * @return array
     */
    protected static function normalise_border_sides(array $sides): array {
        $allowed = ['top', 'right', 'bottom', 'left'];
        $clean = [];
        foreach ($sides as $side) {
            $side = trim((string)$side);
            if ($side === 'all') {
                return $allowed;
            }
            if (in_array($side, $allowed, true)) {
                $clean[$side] = $side;
            }
        }
        return array_values($clean ?: $allowed);
    }

    /**
     * Normalise one border width percentage.
     *
     * @param float $value
     * @return float
     */
    protected static function normalise_border_width_percent(float $value): float {
        return max(0.0, min(100.0, $value));
    }

    /**
     * Convert one stored border width percentage into pixels for a target box.
     *
     * @param float $percent
     * @param int $width
     * @param int $height
     * @return int
     */
    protected static function get_border_width_pixels(float $percent, int $width, int $height): int {
        $percent = self::normalise_border_width_percent($percent);
        $reference = max(0, min($width, $height));
        if ($percent <= 0 || $reference <= 0) {
            return 0;
        }

        return max(1, (int)round($reference * $percent / 100));
    }

    /**
     * Format one stored border width percentage for display.
     *
     * @param float $value
     * @return string
     */
    protected static function format_border_width_percent(float $value): string {
        return rtrim(rtrim(sprintf('%.2F', self::normalise_border_width_percent($value)), '0'), '.');
    }

    /**
     * Extract border side selections from the layer form payload.
     *
     * @param \stdClass $data
     * @return array
     */
    protected static function extract_border_sides_from_form_data(\stdClass $data): array {
        $postedgroup = $_POST['bordersidesgroup'] ?? null;
        if (is_array($postedgroup) && !empty($postedgroup)) {
            $sides = [];
            foreach ($postedgroup as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $nestedkey => $nestedvalue) {
                        if (empty($nestedvalue)) {
                            continue;
                        }
                        if (preg_match('/bordersides\[(.+?)\]?$/', (string)$nestedkey, $matches)) {
                            $sides[] = $matches[1];
                            continue;
                        }
                        $sides[] = (string)$nestedkey;
                    }
                    continue;
                }
                if (empty($value)) {
                    continue;
                }
                if (preg_match('/bordersides\[(.+?)\]?$/', (string)$key, $matches)) {
                    $sides[] = $matches[1];
                    continue;
                }
                $sides[] = (string)$key;
            }
            if (!empty($sides)) {
                return self::normalise_border_sides($sides);
            }
        }

        $rawvalue = trim((string)($data->bordersidesvalue ?? ''));
        if ($rawvalue !== '') {
            return self::normalise_border_sides(array_filter(array_map('trim', explode(',', $rawvalue))));
        }
        if (property_exists($data, 'bordersidesvalue')) {
            return [];
        }

        if (!empty($_POST['bordersidesvalue'])) {
            return self::normalise_border_sides(array_filter(array_map('trim', explode(',', (string)$_POST['bordersidesvalue']))));
        }
        if (array_key_exists('bordersidesvalue', $_POST)) {
            return [];
        }

        if (!empty($data->bordersides) && is_array($data->bordersides)) {
            return self::normalise_border_sides(array_keys(array_filter($data->bordersides)));
        }

        $rawgroup = $data->bordersidesgroup ?? [];
        if (is_object($rawgroup)) {
            $rawgroup = (array)$rawgroup;
        }

        if (is_array($rawgroup) && !empty($rawgroup)) {
            $sides = [];
            foreach ($rawgroup as $key => $value) {
                if (is_object($value)) {
                    $value = (array)$value;
                }

                if (is_array($value)) {
                    foreach ($value as $nestedkey => $nestedvalue) {
                        if (empty($nestedvalue)) {
                            continue;
                        }
                        if (preg_match('/bordersides\[(.+)\]/', (string)$nestedkey, $matches)) {
                            $sides[] = $matches[1];
                            continue;
                        }
                        $sides[] = (string)$nestedkey;
                    }
                    continue;
                }

                if (empty($value)) {
                    continue;
                }
                if (preg_match('/bordersides\[(.+)\]/', (string)$key, $matches)) {
                    $sides[] = $matches[1];
                    continue;
                }
                $sides[] = (string)$key;
            }

            return !empty($sides) ? self::normalise_border_sides($sides) : [];
        }

        return ['top', 'right', 'bottom', 'left'];
    }

    /**
     * Write a temporary debug line for banner builder form submissions.
     *
     * @param string $label
     * @param mixed $payload
     * @return void
     */
    protected static function debug_log(string $label, $payload): void {
        global $CFG;

        $filepath = $CFG->tempdir . DIRECTORY_SEPARATOR . self::DEBUG_LOG_FILE;
        $line = '[' . date('c') . '] ' . $label . ': ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($filepath, $line, FILE_APPEND);
    }

    /**
     * Build set_data defaults for the border side checkbox group.
     *
     * @param array $bordersides
     * @return array
     */
    protected static function get_border_sides_group_defaults(array $bordersides): array {
        $defaults = [
            'bordersides[all]' => 0,
            'bordersides[top]' => 0,
            'bordersides[right]' => 0,
            'bordersides[bottom]' => 0,
            'bordersides[left]' => 0,
        ];
        foreach ($bordersides as $side) {
            $defaults['bordersides[' . $side . ']'] = 1;
        }
        return $defaults;
    }

    /**
     * Build CSS-ready solid and transparent colors from one color string.
     *
     * @param string $color
     * @param float $opacity
     * @return array<string,string>
     */
    protected static function build_css_color_pair(string $color, float $opacity): array {
        $rgba = self::parse_color_to_rgba($color);
        if ($rgba === null) {
            return [
                'solid' => 'rgba(255, 255, 255, ' . $opacity . ')',
                'transparent' => 'rgba(255, 255, 255, 0)',
            ];
        }

        [$red, $green, $blue, $alpha] = $rgba;
        $baseopacity = 1 - ($alpha / 127);
        $effectiveopacity = max(0.0, min(1.0, $baseopacity * $opacity));

        return [
            'solid' => 'rgba(' . $red . ', ' . $green . ', ' . $blue . ', ' . round($effectiveopacity, 3) . ')',
            'transparent' => 'rgba(' . $red . ', ' . $green . ', ' . $blue . ', 0)',
        ];
    }

    /**
     * Return inner corner radii for touching border sides.
     *
     * @param array $sides
     * @param string $radius
     * @return array<string,string>
     */
    protected static function get_border_corner_radii(array $sides, string $radius): array {
        $hasside = array_fill_keys($sides, true);
        return [
            'top-left' => (!empty($hasside['top']) && !empty($hasside['left'])) ? $radius : '0px',
            'top-right' => (!empty($hasside['top']) && !empty($hasside['right'])) ? $radius : '0px',
            'bottom-right' => (!empty($hasside['bottom']) && !empty($hasside['right'])) ? $radius : '0px',
            'bottom-left' => (!empty($hasside['bottom']) && !empty($hasside['left'])) ? $radius : '0px',
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

        if (self::table_field_exists('local_course_banner_order', 'sourcekey')) {
            $record = $DB->get_record('local_course_banner_order', ['sourcekey' => self::get_category_source_key($categoryid)]);
        } else {
            $record = $DB->get_record('local_course_banner_order', ['categoryid' => $categoryid]);
        }
        if ($record) {
            return self::normalise_category_settings($record, $categoryid);
        }

        return self::get_default_category_settings($categoryid);
    }

    /**
     * Fetch source settings without creating a row.
     *
     * @param \stdClass $source
     * @return \stdClass
     */
    public static function get_source_settings(\stdClass $source): \stdClass {
        global $DB;

        if ($source->type === self::SOURCE_TYPE_CATEGORY) {
            return self::get_category_settings((int)$source->categoryid);
        }

        $record = null;
        if (self::table_field_exists('local_course_banner_order', 'sourcekey')) {
            $record = $DB->get_record('local_course_banner_order', ['sourcekey' => $source->sourcekey], '*', IGNORE_MISSING);
        }
        if ($record) {
            return self::normalise_source_settings($record, $source);
        }

        return self::get_default_source_settings($source);
    }

    /**
     * Fetch or create category settings.
     *
     * @param int $categoryid
     * @return \stdClass
     */
    public static function get_or_create_category_settings(int $categoryid): \stdClass {
        global $DB;

        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            return self::get_default_category_settings($categoryid);
        }
        return self::get_or_create_source_settings($source);
    }

    /**
     * Fetch or create settings for any source.
     *
     * @param \stdClass $source
     * @return \stdClass
     */
    public static function get_or_create_source_settings(\stdClass $source): \stdClass {
        global $DB;

        $record = self::get_source_settings($source);
        if (!empty($record->id)) {
            return $record;
        }

        $record->timemodified = time();
        if (!self::table_field_exists('local_course_banner_order', 'sourcetype')) {
            unset($record->sourcetype);
        }
        if (!self::table_field_exists('local_course_banner_order', 'sourcekey')) {
            unset($record->sourcekey);
        }
        if (!self::table_field_exists('local_course_banner_order', 'customfieldvalue')) {
            unset($record->customfieldvalue);
        }
        if (!self::table_field_exists('local_course_banner_order', 'customfieldpriority')) {
            unset($record->customfieldpriority);
        }
        if (!self::table_field_exists('local_course_banner_order', 'compositionmode')) {
            unset($record->compositionmode);
        }
        if (!self::table_field_exists('local_course_banner_order', 'fitmode')) {
            unset($record->fitmode);
        }
        if (!self::table_field_exists('local_course_banner_order', 'fitapplyscope')) {
            unset($record->fitapplyscope);
        }
        if (!self::table_field_exists('local_course_banner_order', 'sourceparentkey')) {
            unset($record->sourceparentkey);
        }
        if (!self::table_field_exists('local_course_banner_order', 'sourceisroot')) {
            unset($record->sourceisroot);
        }
        if (!self::table_field_exists('local_course_banner_order', 'sourceinheritchildren')) {
            unset($record->sourceinheritchildren);
        }

        $record->id = $DB->insert_record('local_course_banner_order', $record);
        return self::normalise_source_settings($record, $source);
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
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            return;
        }
        self::save_source_settings($source, $compositionmode, $fitmode, $fitapplyscope, self::CUSTOMFIELD_PRIORITY_CATEGORY);
    }

    /**
     * Save source settings.
     *
     * @param \stdClass $source
     * @param string $compositionmode
     * @param string $fitmode
     * @param string $fitapplyscope
     * @param string $customfieldpriority
     * @return void
     */
    public static function save_source_settings(
        \stdClass $source,
        string $compositionmode,
        string $fitmode,
        string $fitapplyscope,
        string $customfieldpriority = self::CUSTOMFIELD_PRIORITY_CATEGORY,
        string $sourceparentkey = '',
        bool $sourceisroot = false,
        bool $sourceinheritchildren = false
    ): void {
        global $DB;

        $record = self::get_or_create_source_settings($source);
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
            $fitapplyscope = self::FIT_SCOPE_SELF;
        }
        $fitapplyscope = self::FIT_SCOPE_SELF;
        $priorities = array_keys(self::get_customfield_priority_options());
        if (!in_array($customfieldpriority, $priorities, true)) {
            $customfieldpriority = self::CUSTOMFIELD_PRIORITY_CATEGORY;
        }
        $sourceparentkey = trim($sourceparentkey);
        if ($sourceparentkey === $source->sourcekey || !self::resolve_source($sourceparentkey)) {
            $sourceparentkey = '';
        }
        if ($sourceisroot) {
            $sourceparentkey = '';
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
        if (self::table_field_exists('local_course_banner_order', 'sourcetype')) {
            $record->sourcetype = $source->type;
        }
        if (self::table_field_exists('local_course_banner_order', 'sourcekey')) {
            $record->sourcekey = $source->sourcekey;
        }
        if (self::table_field_exists('local_course_banner_order', 'customfieldvalue')) {
            $record->customfieldvalue = $source->customfieldvalue ?? null;
        }
        if (self::table_field_exists('local_course_banner_order', 'customfieldpriority')) {
            $record->customfieldpriority = $customfieldpriority;
        }
        if (self::table_field_exists('local_course_banner_order', 'sourceparentkey')) {
            $record->sourceparentkey = $sourceparentkey;
        }
        if (self::table_field_exists('local_course_banner_order', 'sourceisroot')) {
            $record->sourceisroot = $sourceisroot ? 1 : 0;
        }
        if (self::table_field_exists('local_course_banner_order', 'sourceinheritchildren')) {
            $record->sourceinheritchildren = 0;
        }
        $record->timemodified = time();
        $DB->update_record('local_course_banner_order', $record);

        self::sync_courses_for_source($source);
    }

    /**
     * Save one editable source setting from the summary panel.
     *
     * @param \stdClass $source
     * @param string $fieldname
     * @param string $value
     * @return void
     */
    public static function update_source_setting_field(\stdClass $source, string $fieldname, string $value): void {
        $settings = self::get_source_settings($source);
        $compositionmode = $settings->compositionmode ?? self::MODE_RANDOM;
        $fitmode = $settings->fitmode ?? self::FIT_MODE_BANNER;
        $fitapplyscope = self::FIT_SCOPE_SELF;
        $customfieldpriority = $settings->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY;
        $sourceparentkey = (string)($settings->sourceparentkey ?? '');
        $sourceisroot = !empty($settings->sourceisroot);
        $sourceinheritchildren = false;

        switch ($fieldname) {
            case 'compositionmode':
                $compositionmode = $value;
                break;
            case 'fitmode':
                $fitmode = $value;
                break;
            case 'sourceparentkey':
                $sourceparentkey = $value;
                $sourceisroot = trim($value) === '';
                break;
            case 'customfieldpriority':
                $customfieldpriority = $value;
                break;
            default:
                return;
        }

        self::save_source_settings(
            $source,
            $compositionmode,
            $fitmode,
            $fitapplyscope,
            $customfieldpriority,
            $sourceparentkey,
            $sourceisroot,
            false
        );
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
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            return [];
        }
        return self::get_source_elements($source, $enabledonly);
    }

    /**
     * Returns all banner elements for a source.
     *
     * @param \stdClass $source
     * @param bool $enabledonly
     * @return array
     */
    public static function get_source_elements(\stdClass $source, bool $enabledonly = false): array {
        global $DB;

        $params = [
            'elementtype' => 'background_image',
        ];
        if (self::table_field_exists('local_course_banner_elements', 'sourcekey')) {
            $params['sourcekey'] = $source->sourcekey;
        } else {
            $params['categoryid'] = (int)($source->categoryid ?? 0);
        }
        if ($enabledonly) {
            $params['isenabled'] = 1;
        }

        $records = $DB->get_records('local_course_banner_elements', $params, 'sortorder ASC, id ASC');
        if (empty($records)) {
            return [];
        }

        $sorted = array_values($records);
        usort($sorted, [self::class, 'compare_source_elements']);
        return $sorted;
    }

    /**
     * Create a new empty category element.
     *
     * @param int $categoryid
     * @return \stdClass
     */
    protected static function create_category_element(int $categoryid): \stdClass {
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            throw new \coding_exception('Cannot create banner element for an invalid category source.');
        }
        return self::create_source_element($source);
    }

    /**
     * Create a new empty source element.
     *
     * @param \stdClass $source
     * @return \stdClass
     */
    protected static function create_source_element(\stdClass $source): \stdClass {
        global $DB;

        $now = time();
        $record = (object)[
            'categoryid' => $source->type === self::SOURCE_TYPE_CATEGORY ? (int)$source->categoryid : null,
            'customfieldid' => $source->customfieldid ?? null,
            'elementtype' => 'background_image',
            'name' => '',
            'sortorder' => self::get_next_sortorder_for_source($source),
            'fileitemid' => 0,
            'isenabled' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
            $record->fitmodeoverride = null;
        }
        if (self::table_field_exists('local_course_banner_elements', 'positionanchor')) {
            $record->positionanchor = self::POSITION_CENTER;
        }
        if (self::table_field_exists('local_course_banner_elements', 'offsettoppercent')) {
            $record->offsettoppercent = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'offsetrightpercent')) {
            $record->offsetrightpercent = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'offsetbottompercent')) {
            $record->offsetbottompercent = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'offsetleftpercent')) {
            $record->offsetleftpercent = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'customwidthpercent')) {
            $record->customwidthpercent = 100;
        }
        if (self::table_field_exists('local_course_banner_elements', 'customheightpercent')) {
            $record->customheightpercent = 100;
        }
        if (self::table_field_exists('local_course_banner_elements', 'customsizekeepaspect')) {
            $record->customsizekeepaspect = 1;
        }
        if (self::table_field_exists('local_course_banner_elements', 'dynamicimagesizeenabled')) {
            $record->dynamicimagesizeenabled = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderenabled')) {
            $record->borderenabled = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'bordercolor')) {
            $record->bordercolor = '#56B9C0';
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderwidth')) {
            $record->borderwidth = 2.5;
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderopacity')) {
            $record->borderopacity = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderfade')) {
            $record->borderfade = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderstyle')) {
            $record->borderstyle = self::BORDER_STYLE_SOLID;
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderdashlength')) {
            $record->borderdashlength = 24;
        }
        if (self::table_field_exists('local_course_banner_elements', 'bordersides')) {
            $record->bordersides = 'top,right,bottom,left';
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderinnerrounded')) {
            $record->borderinnerrounded = 0;
        }
        if (self::table_field_exists('local_course_banner_elements', 'sourcetype')) {
            $record->sourcetype = $source->type;
        }
        if (self::table_field_exists('local_course_banner_elements', 'sourcekey')) {
            $record->sourcekey = $source->sourcekey;
        }
        if (self::table_field_exists('local_course_banner_elements', 'customfieldvalue')) {
            $record->customfieldvalue = $source->customfieldvalue ?? null;
        }
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
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            return 0;
        }
        return self::get_next_sortorder_for_source($source);
    }

    /**
     * Get next sort order in a source.
     *
     * @param \stdClass $source
     * @return int
     */
    protected static function get_next_sortorder_for_source(\stdClass $source): int {
        global $DB;

        if (self::table_field_exists('local_course_banner_elements', 'sourcekey')) {
            $max = $DB->get_field_sql(
                'SELECT MAX(sortorder) FROM {local_course_banner_elements} WHERE sourcekey = :sourcekey',
                ['sourcekey' => $source->sourcekey]
            );
        } else {
            $max = $DB->get_field_sql(
                'SELECT MAX(sortorder) FROM {local_course_banner_elements} WHERE categoryid = :categoryid',
                ['categoryid' => (int)($source->categoryid ?? 0)]
            );
        }

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
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if ($source) {
            self::make_source_sortorder_room($source, $sortorder);
        }
    }

    /**
     * Shift existing layer orders to make room for a new source layer.
     *
     * @param \stdClass $source
     * @param int $sortorder
     * @return void
     */
    protected static function make_source_sortorder_room(\stdClass $source, int $sortorder): void {
        global $DB;

        $records = self::get_source_elements($source);
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

        usort($records, [self::class, 'compare_source_elements']);

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
    public static function get_draft_files(int $draftitemid): array {
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
     * Build an automatic border layer name for one source.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return string
     */
    protected static function get_automatic_border_name(\stdClass $source, int $excludeid = 0): string {
        $count = 0;
        foreach (self::get_source_elements($source) as $element) {
            if ($excludeid > 0 && (int)$element->id === $excludeid) {
                continue;
            }
            if (!empty($element->borderenabled)) {
                $count++;
            }
        }
        $sourcelabel = trim((string)($source->label ?? $source->sourcekey ?? 'Source'));
        return $sourcelabel . ' Bordure ' . ($count + 1);
    }

    /**
     * Detect whether a source already contains another border layer.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return bool
     */
    public static function source_has_border_layer(\stdClass $source, int $excludeid = 0): bool {
        return self::get_source_border_layer_record($source, $excludeid) !== null;
    }

    /**
     * Get the first border layer stored directly in one source.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return \stdClass|null
     */
    protected static function get_source_border_layer_record(\stdClass $source, int $excludeid = 0): ?\stdClass {
        foreach (self::get_source_elements($source) as $element) {
            if ($excludeid > 0 && (int)$element->id === $excludeid) {
                continue;
            }
            if (!empty($element->borderenabled)) {
                return $element;
            }
        }
        return null;
    }

    /**
     * Get the category ids that inherit from, or are inherited by, one category source.
     *
     * @param \stdClass $source
     * @return int[]
     */
    protected static function get_inherited_chain_category_ids_for_source(\stdClass $source): array {
        global $DB;

        if (($source->type ?? self::SOURCE_TYPE_CATEGORY) !== self::SOURCE_TYPE_CATEGORY || empty($source->categoryid)) {
            return [];
        }

        $sourcecategoryid = (int)$source->categoryid;
        $category = $DB->get_record('course_categories', ['id' => $sourcecategoryid], 'id,path', IGNORE_MISSING);
        if (!$category) {
            return [];
        }

        $categoryids = [];
        foreach (self::get_category_chain($sourcecategoryid) as $categoryid) {
            if ($categoryid !== $sourcecategoryid) {
                $categoryids[] = $categoryid;
            }
        }

        $descendants = $DB->get_fieldset_select(
            'course_categories',
            'id',
            'path LIKE :pathlike AND id <> :categoryid',
            [
                'pathlike' => (string)$category->path . '/%',
                'categoryid' => $sourcecategoryid,
            ],
            'path ASC'
        );
        foreach ($descendants as $descendantid) {
            $descendantid = (int)$descendantid;
            if (in_array($sourcecategoryid, self::get_category_chain($descendantid), true)) {
                $categoryids[] = $descendantid;
            }
        }

        return array_values(array_unique(array_filter($categoryids)));
    }

    /**
     * Get course category ids where a custom-field-value source applies.
     *
     * @param \stdClass $source
     * @return int[]
     */
    protected static function get_category_ids_for_customfield_source(\stdClass $source): array {
        global $DB;

        if (($source->type ?? '') !== self::SOURCE_TYPE_CUSTOMFIELD || empty($source->customfieldid)) {
            return [];
        }

        $field = $DB->get_record(
            'customfield_field',
            ['id' => (int)$source->customfieldid],
            'id,name,type,configdata',
            IGNORE_MISSING
        );
        if (!$field) {
            return [];
        }

        $categories = [];
        $datarecords = $DB->get_records('customfield_data', ['fieldid' => (int)$field->id], '', 'id,instanceid,fieldid,intvalue,value,charvalue,shortcharvalue');
        foreach ($datarecords as $datarecord) {
            if (self::extract_customfield_data_value($field, $datarecord) !== (string)($source->customfieldvalue ?? '')) {
                continue;
            }
            $categoryid = (int)$DB->get_field('course', 'category', ['id' => (int)$datarecord->instanceid], IGNORE_MISSING);
            if ($categoryid > 0) {
                $categories[] = $categoryid;
            }
        }

        return array_values(array_unique($categories));
    }

    /**
     * Build the effective category chain after source short-circuit rules.
     *
     * @param int $targetcategoryid
     * @return int[]
     */
    protected static function get_effective_category_chain_for_target(int $targetcategoryid): array {
        return $targetcategoryid > 0 ? [$targetcategoryid] : [];
    }

    /**
     * Find a border layer in the effective category chain for one target category.
     *
     * @param int $targetcategoryid
     * @param int $excludeid
     * @param int $excludedcategoryid
     * @return array|null
     */
    protected static function get_category_chain_border_layer_for_target(
        int $targetcategoryid,
        int $excludeid = 0,
        int $excludedcategoryid = 0
    ): ?array {
        foreach ([$targetcategoryid] as $categoryid) {
            if ($excludedcategoryid > 0 && $categoryid === $excludedcategoryid) {
                continue;
            }
            $chainsource = self::resolve_source(self::get_category_source_key($categoryid));
            if (!$chainsource) {
                continue;
            }
            $borderrecord = self::get_source_border_layer_record($chainsource, $excludeid);
            if ($borderrecord) {
                return [
                    'source' => $chainsource,
                    'record' => $borderrecord,
                ];
            }
        }

        return null;
    }

    /**
     * Return the explicit manual parent chain for one source.
     *
     * @param \stdClass $source
     * @param bool $includecurrent
     * @return \stdClass[]
     */
    protected static function get_explicit_source_parent_chain(\stdClass $source, bool $includecurrent = false): array {
        $chain = [];
        $visited = [];
        $current = $source;

        if ($includecurrent) {
            $chain[] = $current;
        }

        while (!empty($current->sourcekey) && empty($visited[(string)$current->sourcekey])) {
            $visited[(string)$current->sourcekey] = true;
            $settings = self::get_source_settings($current);
            if (!empty($settings->sourceisroot)) {
                break;
            }

            $parentkey = trim((string)($settings->sourceparentkey ?? ''));
            if ($parentkey === '') {
                break;
            }

            $parent = self::resolve_source($parentkey);
            if (!$parent || empty($parent->sourcekey) || !empty($visited[(string)$parent->sourcekey])) {
                break;
            }

            $chain[] = $parent;
            $current = $parent;
        }

        return $chain;
    }

    /**
     * Find a border layer in the inherited category source chain.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return array|null
     */
    public static function get_source_chain_border_layer(\stdClass $source, int $excludeid = 0): ?array {
        foreach (self::get_explicit_source_parent_chain($source, false) as $chainsource) {
            $borderrecord = self::get_source_border_layer_record($chainsource, $excludeid);
            if ($borderrecord) {
                return [
                    'source' => $chainsource,
                    'record' => $borderrecord,
                ];
            }
        }

        return null;
    }

    /**
     * Detect whether a category source chain already contains a border layer.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return bool
     */
    public static function source_chain_has_border_layer(\stdClass $source, int $excludeid = 0): bool {
        return self::get_source_chain_border_layer($source, $excludeid) !== null;
    }

    /**
     * Return border conflict state for one source.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return array
     */
    public static function get_source_border_conflict_state(\stdClass $source, int $excludeid = 0): array {
        $insource = self::source_has_border_layer($source, $excludeid);
        $inchain = !$insource && self::source_chain_has_border_layer($source, $excludeid);

        return [
            'blocked' => $insource || $inchain,
            'isinchain' => $inchain,
            'messagekey' => $inchain ? 'sourcechainalreadyhasborder' : 'sourcealreadyhasborder',
            'inlinekey' => $inchain ? 'sourcechainalreadyhasborderinline' : 'sourcealreadyhasborderinline',
        ];
    }

    /**
     * Whether one record is a border-only layer.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function is_border_only_layer(\stdClass $record): bool {
        return !self::get_banner_image_file($record) && !empty($record->borderenabled);
    }

    /**
     * Whether one image layer should be rendered above the border.
     *
     * The existing dynamic flag is reused as the persisted "top layer" toggle
     * to avoid a schema migration while the UI moves away from the old
     * dynamic/non-dynamic distinction.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function is_top_image_layer(\stdClass $record): bool {
        return !self::is_border_only_layer($record) && !empty($record->dynamicimagesizeenabled);
    }

    /**
     * Return the special render priority for one layer.
     *
     * @param \stdClass $record
     * @return int
     */
    protected static function get_layer_priority(\stdClass $record): int {
        if (self::is_border_only_layer($record)) {
            return 1;
        }
        if (self::is_top_image_layer($record)) {
            return 2;
        }
        return 0;
    }

    /**
     * Whether the layer ordering is locked in the admin table.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function is_locked_order_layer(\stdClass $record): bool {
        return self::is_border_only_layer($record);
    }

    /**
     * Return a stable z-index for preview rendering.
     *
     * @param \stdClass $record
     * @return int
     */
    protected static function get_preview_layer_zindex(\stdClass $record): int {
        return (self::get_layer_priority($record) * 1000) + (int)($record->sortorder ?? 0) + 1;
    }

    /**
     * Compare two source elements for display and persistence ordering.
     *
     * Normal image layers follow their sort order, and border-only layers stay last.
     *
     * @param \stdClass $a
     * @param \stdClass $b
     * @return int
     */
    protected static function compare_source_elements(\stdClass $a, \stdClass $b): int {
        $apriority = self::get_layer_priority($a);
        $bpriority = self::get_layer_priority($b);
        if ($apriority !== $bpriority) {
            return $apriority <=> $bpriority;
        }

        $sortcompare = ((int)$a->sortorder) <=> ((int)$b->sortorder);
        if ($sortcompare !== 0) {
            return $sortcompare;
        }

        return ((int)$a->id) <=> ((int)$b->id);
    }

    /**
     * Save category banner form data.
     *
     * @param \stdClass $data
     * @return int
     */
    public static function save_category_banner(\stdClass $data): int {
        $source = self::resolve_source_from_request((string)($data->sourcekey ?? ''), (int)$data->categoryid);
        if (!$source) {
            throw new \coding_exception('Cannot save banner for an invalid source.');
        }
        return self::save_source_banner($source, $data);
    }

    /**
     * Save source banner form data.
     *
     * @param \stdClass $source
     * @param \stdClass $data
     * @return int
     */
    public static function save_source_banner(\stdClass $source, \stdClass $data): int {
        global $DB;

        $elementid = (int)($data->elementid ?? 0);
        $draftfiles = self::get_draft_files((int)($data->bannerimage_filemanager ?? 0));
        $multidraftsettings = self::extract_multi_draft_settings($data->multilayerdraftsettings ?? '');
        if ($elementid && !empty($data->currentisborderlayer)) {
            $data->borderenabled = 1;
        }
        $hasborder = !empty($data->borderenabled);
        self::debug_log('save_source_banner_input', [
            'sourcekey' => $source->sourcekey ?? '',
            'elementid' => $elementid,
            'hasborder' => $hasborder ? 1 : 0,
            'bordersidesvalue_data' => $data->bordersidesvalue ?? null,
            'bordersidesgroup_data' => $data->bordersidesgroup ?? null,
            'post_bordersidesvalue' => $_POST['bordersidesvalue'] ?? null,
            'post_bordersidesgroup' => $_POST['bordersidesgroup'] ?? null,
        ]);
        $borderconflict = self::get_source_border_conflict_state($source, $elementid);
        if ($hasborder && !empty($borderconflict['blocked'])) {
            throw new \moodle_exception((string)$borderconflict['messagekey'], 'local_course_banner_builder');
        }

        if (!$elementid && count($draftfiles) > 1) {
            $createdids = [];
            $nextsortorder = self::get_next_sortorder_for_source($source);
            foreach (array_values($draftfiles) as $draftindex => $draftfile) {
                $record = self::create_source_element($source);
                $record->name = self::get_automatic_layer_name($draftfile);
                $record->sortorder = $nextsortorder++;
                $record->isenabled = empty($data->isenabled) ? 0 : 1;
                $layerdata = self::apply_multi_draft_layer_settings($data, $multidraftsettings[$draftindex] ?? []);
                self::apply_element_display_settings($record, $layerdata, $source);
                $record->timemodified = time();
                $DB->update_record('local_course_banner_elements', $record);
                self::copy_draft_file_to_element($record, $draftfile);
                $createdids[] = (int)$record->id;
            }

            self::sync_courses_for_source($source);
            return (int)reset($createdids);
        }

        $record = $elementid ? self::get_banner_element($elementid) : null;
        if (!$record) {
            $record = self::create_source_element($source);
        }

        $context = \context_system::instance();
        $record->categoryid = $source->type === self::SOURCE_TYPE_CATEGORY ? (int)$source->categoryid : null;
        $record->customfieldid = $source->customfieldid ?? null;
        if (self::table_field_exists('local_course_banner_elements', 'sourcetype')) {
            $record->sourcetype = $source->type;
        }
        if (self::table_field_exists('local_course_banner_elements', 'sourcekey')) {
            $record->sourcekey = $source->sourcekey;
        }
        if (self::table_field_exists('local_course_banner_elements', 'customfieldvalue')) {
            $record->customfieldvalue = $source->customfieldvalue ?? null;
        }
        $record->name = trim((string)($data->name ?? ''));
        if ($record->name === '' && !empty($draftfiles)) {
            $record->name = self::get_automatic_layer_name(reset($draftfiles));
        } else if ($record->name === '' && $hasborder) {
            $record->name = self::get_automatic_border_name($source, $elementid);
        }
        $record->sortorder = $hasborder
            ? self::get_next_sortorder_for_source($source)
            : max(0, (int)($data->sortorder ?? 0));
        if (!$elementid && !$hasborder) {
            self::make_source_sortorder_room($source, $record->sortorder);
        }
        $record->isenabled = empty($data->isenabled) ? 0 : 1;
        self::apply_element_display_settings($record, $data, $source);
        $record->timemodified = time();
        $DB->update_record('local_course_banner_elements', $record);

        if (isset($data->bannerimage_filemanager)) {
            file_save_draft_area_files(
                $data->bannerimage_filemanager,
                $context->id,
                'local_course_banner_builder',
                self::FILEAREA,
                $record->fileitemid,
                self::get_filemanager_options()
            );
        }

        self::normalize_element_sortorders(self::get_source_elements($source));
        self::sync_courses_for_source($source);
        return (int)$record->id;
    }

    /**
     * Parse the per-draft preview settings payload submitted by the create modal.
     *
     * @param string $payloadjson
     * @return array
     */
    protected static function extract_multi_draft_settings(string $payloadjson): array {
        if ($payloadjson === '') {
            return [];
        }

        $payload = json_decode($payloadjson, true);
        if (!is_array($payload)) {
            return [];
        }

        $settings = [];
        foreach ($payload as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $settings[(int)$index] = $row;
        }

        ksort($settings);
        return $settings;
    }

    /**
     * Merge one multi-upload preview state onto the shared create form data.
     *
     * @param \stdClass $data
     * @param array $settings
     * @return \stdClass
     */
    protected static function apply_multi_draft_layer_settings(\stdClass $data, array $settings): \stdClass {
        $layerdata = clone $data;

        if (array_key_exists('fitmodeoverride', $settings)) {
            $layerdata->fitmodeoverride = (string)$settings['fitmodeoverride'];
        }
        if (array_key_exists('positionanchor', $settings)) {
            $layerdata->positionanchor = (string)$settings['positionanchor'];
        }
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $property = 'offset' . $side . 'percent';
            if (array_key_exists($property, $settings)) {
                $layerdata->{$property} = (float)$settings[$property];
            }
        }
        if (array_key_exists('customwidthpercent', $settings)) {
            $layerdata->customwidthpercent = (float)$settings['customwidthpercent'];
        }
        if (array_key_exists('customheightpercent', $settings)) {
            $layerdata->customheightpercent = (float)$settings['customheightpercent'];
        }
        if (array_key_exists('customsizekeepaspect', $settings)) {
            $layerdata->customsizekeepaspect = empty($settings['customsizekeepaspect']) ? 0 : 1;
        }
        if (array_key_exists('dynamicimagesizeenabled', $settings)) {
            $layerdata->dynamicimagesizeenabled = empty($settings['dynamicimagesizeenabled']) ? 0 : 1;
        }

        return $layerdata;
    }

    /**
     * Apply advanced display settings from the edit form onto an element record.
     *
     * @param \stdClass $record
     * @param \stdClass $data
     * @param \stdClass $source
     * @return void
     */
    protected static function apply_element_display_settings(\stdClass $record, \stdClass $data, \stdClass $source): void {
        $allowedfitmodes = array_merge([''], array_keys(self::get_fit_mode_options(true)));
        $fitmodeoverride = (string)($data->fitmodeoverride ?? '');
        if (!in_array($fitmodeoverride, $allowedfitmodes, true)) {
            $fitmodeoverride = '';
        }

        $sourcefitmode = self::get_source_settings($source)->fitmode ?? self::FIT_MODE_BANNER;
        if ($fitmodeoverride === $sourcefitmode) {
            $fitmodeoverride = '';
        }

        if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride') && property_exists($data, 'fitmodeoverride')) {
            $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
        }

        if (self::table_field_exists('local_course_banner_elements', 'positionanchor') && property_exists($data, 'positionanchor')) {
            $record->positionanchor = self::normalise_position_anchor((string)($data->positionanchor ?? self::POSITION_CENTER));
        }

        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $property = 'offset' . $side . 'percent';
            if (self::table_field_exists('local_course_banner_elements', $property) && property_exists($data, $property)) {
                $record->{$property} = self::normalise_percentage((float)($data->{$property} ?? 0), -300.0, 300.0);
            }
        }
        if (self::table_field_exists('local_course_banner_elements', 'customwidthpercent') &&
                property_exists($data, 'customwidthpercent')) {
            $record->customwidthpercent = self::normalise_percentage((float)($data->customwidthpercent ?? 100), 0.0, 300.0);
        }
        if (self::table_field_exists('local_course_banner_elements', 'customheightpercent') &&
                property_exists($data, 'customheightpercent')) {
            $record->customheightpercent = self::normalise_percentage((float)($data->customheightpercent ?? 100), 0.0, 300.0);
        }
        if (self::table_field_exists('local_course_banner_elements', 'customsizekeepaspect') &&
                property_exists($data, 'customsizekeepaspect')) {
            $record->customsizekeepaspect = empty($data->customsizekeepaspect) ? 0 : 1;
        }
        if (!empty($record->customsizekeepaspect) &&
                ($fitmodeoverride === self::FIT_MODE_CUSTOM ||
                    (empty($fitmodeoverride) && $sourcefitmode === self::FIT_MODE_CUSTOM)) &&
                self::table_field_exists('local_course_banner_elements', 'customheightpercent')) {
            $record->customheightpercent = $record->customwidthpercent ?? 100;
        }
        if (self::table_field_exists('local_course_banner_elements', 'dynamicimagesizeenabled') &&
                property_exists($data, 'dynamicimagesizeenabled')) {
            $record->dynamicimagesizeenabled = empty($data->dynamicimagesizeenabled) ? 0 : 1;
        }

        if (self::table_field_exists('local_course_banner_elements', 'borderenabled') && property_exists($data, 'borderenabled')) {
            $record->borderenabled = empty($data->borderenabled) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_elements', 'bordercolor') && property_exists($data, 'bordercolor')) {
            $record->bordercolor = self::normalise_color_string((string)($data->bordercolor ?? '#FFFFFF'));
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderwidth') && property_exists($data, 'borderwidth')) {
            $record->borderwidth = self::normalise_border_width_percent((float)($data->borderwidth ?? 0));
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderopacity') && property_exists($data, 'borderopacity')) {
            $record->borderopacity = self::normalise_unit_float(((float)($data->borderopacity ?? 0)) / 100, 0);
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderfade') && property_exists($data, 'borderfade')) {
            $record->borderfade = self::normalise_unit_float(((float)($data->borderfade ?? 0)) / 100, 0);
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderstyle') && property_exists($data, 'borderstyle')) {
            $record->borderstyle = self::normalise_border_style((string)($data->borderstyle ?? self::BORDER_STYLE_SOLID));
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderdashlength') &&
                property_exists($data, 'borderdashlength')) {
            $record->borderdashlength = max(4, min(80, (int)round((float)($data->borderdashlength ?? 24))));
        }
        if (self::table_field_exists('local_course_banner_elements', 'bordersides') &&
                (property_exists($data, 'bordersidesvalue') || property_exists($data, 'bordersidesgroup'))) {
            $sides = self::extract_border_sides_from_form_data($data);
            self::debug_log('save_source_banner_sides', [
                'sourcekey' => $source->sourcekey ?? '',
                'elementid' => (int)($record->id ?? 0),
                'resolvedsides' => $sides,
            ]);
            $record->bordersides = implode(',', self::normalise_border_sides($sides));
        }
        if (self::table_field_exists('local_course_banner_elements', 'borderinnerrounded') &&
                property_exists($data, 'borderinnerrounded')) {
            $record->borderinnerrounded = empty($data->borderinnerrounded) ? 0 : 1;
        }
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

        $source = self::resolve_source(self::get_record_source_key($record));
        $record->name = trim($name);
        $record->sortorder = self::is_border_only_layer($record)
            ? self::get_next_sortorder_for_source($source ?: self::resolve_source(self::get_record_source_key($record)))
            : max(0, $sortorder);
        $record->isenabled = $enabled ? 1 : 0;
        $record->timemodified = time();
        $DB->update_record('local_course_banner_elements', $record);
        if ($source) {
            self::normalize_element_sortorders(self::get_source_elements($source));
            self::sync_courses_for_source($source);
        }
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

        $allowedmodes = array_merge([''], array_keys(self::get_fit_mode_options(true)));
        if (!in_array((string)$fitmodeoverride, $allowedmodes, true)) {
            $fitmodeoverride = null;
        }
        $source = self::resolve_source(self::get_record_source_key($record));
        $sourcefitmode = $source ? (self::get_source_settings($source)->fitmode ?? self::FIT_MODE_BANNER) : self::FIT_MODE_BANNER;
        if ($fitmodeoverride === $sourcefitmode) {
            $fitmodeoverride = '';
        }

        $record->name = trim($name);
        $record->sortorder = self::is_border_only_layer($record)
            ? self::get_next_sortorder_for_source($source ?: self::resolve_source(self::get_record_source_key($record)))
            : max(0, $sortorder);
        $record->isenabled = $enabled ? 1 : 0;
        if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
            $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
        }
        $record->timemodified = time();
        $DB->update_record('local_course_banner_elements', $record);
        if ($source) {
            self::normalize_element_sortorders(self::get_source_elements($source));
            self::sync_courses_for_source($source);
        }
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
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            return;
        }
        self::update_source_element_rows($source, $names, $sortorders, $enabled, $fitmodeoverrides);
    }

    /**
     * Update editable layer metadata for all rows in a selected source.
     *
     * @param \stdClass $source
     * @param array $names
     * @param array $sortorders
     * @param array $enabled
     * @param array $fitmodeoverrides
     * @return void
     */
    public static function update_source_element_rows(
        \stdClass $source,
        array $names,
        array $sortorders,
        array $enabled,
        array $fitmodeoverrides
    ): void {
        global $DB;

        $records = self::get_source_elements($source);
        if (empty($records)) {
            return;
        }

        $allowedmodes = array_merge([''], array_keys(self::get_fit_mode_options(true)));
        $sourcefitmode = self::get_source_settings($source)->fitmode ?? self::FIT_MODE_BANNER;
        $now = time();
        foreach ($records as $record) {
            $elementid = (int)$record->id;
            $currentfitmodeoverride = self::table_field_exists('local_course_banner_elements', 'fitmodeoverride') ?
                (string)($record->fitmodeoverride ?? '') : '';
            $fitmodeoverride = array_key_exists($elementid, $fitmodeoverrides) ?
                (string)$fitmodeoverrides[$elementid] : $currentfitmodeoverride;
            if (!in_array($fitmodeoverride, $allowedmodes, true)) {
                $fitmodeoverride = $currentfitmodeoverride;
            }
            if ($fitmodeoverride === $sourcefitmode) {
                $fitmodeoverride = '';
            }

            $record->name = trim((string)($names[$elementid] ?? $record->name));
            $record->sortorder = self::is_border_only_layer($record)
                ? self::get_next_sortorder_for_source($source)
                : max(0, (int)($sortorders[$elementid] ?? $record->sortorder));
            $record->isenabled = !empty($enabled[$elementid]) ? 1 : 0;
            if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
                $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
            }
            $record->timemodified = $now;
            $DB->update_record('local_course_banner_elements', $record);
        }

        self::normalize_element_sortorders(self::get_source_elements($source));
        self::sync_all_courses_from_category_banners();
    }

    /**
     * Persist manual drag/resize updates from the selected source visual editor.
     *
     * @param \stdClass $source
     * @param array $payload
     * @return void
     */
    public static function update_source_visual_editor_layers(\stdClass $source, array $payload): void {
        global $DB;

        if (empty($payload)) {
            return;
        }

        $records = self::get_source_elements($source);
        if (empty($records)) {
            return;
        }

        $sourcefitmode = self::get_source_settings($source)->fitmode ?? self::FIT_MODE_BANNER;
        $now = time();

        foreach ($records as $record) {
            $elementid = (int)$record->id;
            if (!isset($payload[$elementid]) || !is_array($payload[$elementid])) {
                continue;
            }
            if (!self::get_banner_image_file($record) || self::is_border_only_layer($record)) {
                continue;
            }

            $layer = $payload[$elementid];
            if (array_key_exists('sortorder', $layer)) {
                $record->sortorder = max(0, (int)$layer['sortorder']);
            }
            $fitmodeoverride = (string)($layer['fitmodeoverride'] ?? self::FIT_MODE_CUSTOM);
            if (!in_array($fitmodeoverride, array_keys(self::get_fit_mode_options(true)), true)) {
                $fitmodeoverride = self::FIT_MODE_CUSTOM;
            }
            if ($fitmodeoverride === $sourcefitmode) {
                $fitmodeoverride = '';
            }

            if (self::table_field_exists('local_course_banner_elements', 'fitmodeoverride')) {
                $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
            }
            if (self::table_field_exists('local_course_banner_elements', 'positionanchor')) {
                $record->positionanchor = self::normalise_position_anchor(
                    (string)($layer['positionanchor'] ?? self::POSITION_TOP_LEFT)
                );
            }
            if (self::table_field_exists('local_course_banner_elements', 'customwidthpercent')) {
                $record->customwidthpercent = self::normalise_percentage((float)($layer['customwidthpercent'] ?? 100), 0.0, 300.0);
            }
            if (self::table_field_exists('local_course_banner_elements', 'customheightpercent')) {
                $record->customheightpercent = self::normalise_percentage((float)($layer['customheightpercent'] ?? 100), 0.0, 300.0);
            }
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $property = 'offset' . $side . 'percent';
                if (!self::table_field_exists('local_course_banner_elements', $property)) {
                    continue;
                }
                $defaultvalue = in_array($side, ['top', 'left'], true) ? 0 : 0;
                $record->{$property} = self::normalise_percentage((float)($layer[$property] ?? $defaultvalue), -300.0, 300.0);
            }
            if (self::table_field_exists('local_course_banner_elements', 'customsizekeepaspect') &&
                    array_key_exists('customsizekeepaspect', $layer)) {
                $record->customsizekeepaspect = empty($layer['customsizekeepaspect']) ? 0 : 1;
            }
            if (self::table_field_exists('local_course_banner_elements', 'dynamicimagesizeenabled') &&
                    array_key_exists('dynamicimagesizeenabled', $layer)) {
                $record->dynamicimagesizeenabled = empty($layer['dynamicimagesizeenabled']) ? 0 : 1;
            }

            $record->timemodified = $now;
            $DB->update_record('local_course_banner_elements', $record);
        }

        self::normalize_element_sortorders(self::get_source_elements($source));
        self::sync_courses_for_source($source);
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

        $source = self::resolve_source(self::get_record_source_key($record));
        if ($source) {
            self::sync_courses_for_source($source);
        }
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
        if (self::table_field_exists('local_course_banner_order', 'sourcekey')) {
            $DB->delete_records('local_course_banner_order', ['sourcekey' => self::get_category_source_key($categoryid)]);
        } else {
            $DB->delete_records('local_course_banner_order', ['categoryid' => $categoryid]);
        }
        self::sync_courses_for_category_tree($categoryid);
    }

    /**
     * Delete all content and rules for a source.
     *
     * @param \stdClass $source
     * @return void
     */
    public static function delete_source_content(\stdClass $source): void {
        global $DB;

        if ($source->type === self::SOURCE_TYPE_CATEGORY) {
            self::delete_category_content((int)$source->categoryid);
            return;
        }

        self::delete_source_images($source, false);
        if (self::table_field_exists('local_course_banner_order', 'sourcekey')) {
            $DB->delete_records('local_course_banner_order', ['sourcekey' => $source->sourcekey]);
        }
        self::sync_courses_for_source($source);
    }

    /**
     * Delete all images for a category while optionally syncing affected courses.
     *
     * @param int $categoryid
     * @param bool $sync
     * @return void
     */
    public static function delete_category_images(int $categoryid, bool $sync = true): void {
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            return;
        }
        self::delete_source_images($source, $sync);
    }

    /**
     * Delete all images for a source while optionally syncing affected courses.
     *
     * @param \stdClass $source
     * @param bool $sync
     * @return void
     */
    public static function delete_source_images(\stdClass $source, bool $sync = true): void {
        global $DB;

        foreach (self::get_source_elements($source) as $element) {
            $context = \context_system::instance();
            $fs = get_file_storage();
            $fs->delete_area_files(
                $context->id,
                'local_course_banner_builder',
                self::FILEAREA,
                $element->fileitemid
            );
        }

        if (self::table_field_exists('local_course_banner_elements', 'sourcekey')) {
            $DB->delete_records('local_course_banner_elements', ['sourcekey' => $source->sourcekey]);
        } else {
            $DB->delete_records('local_course_banner_elements', ['categoryid' => (int)($source->categoryid ?? 0)]);
        }
        if ($sync) {
            self::sync_courses_for_source($source);
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
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            $source = (object)[
                'type' => self::SOURCE_TYPE_CATEGORY,
                'sourcekey' => self::get_category_source_key($categoryid),
                'categoryid' => $categoryid,
                'customfieldid' => null,
                'customfieldvalue' => null,
            ];
        }
        return self::get_source_form_data($source, $elementid, $allowmultiple);
    }

    /**
     * Export one live banner preview definition for add/edit layer modals.
     *
     * @param \stdClass $source
     * @param int $elementid
     * @return array
     */
    public static function export_modal_preview_definition(\stdClass $source, int $elementid = 0): array {
        $contextlayers = [];
        $currentlayer = null;
        $sourcesettings = self::get_source_settings($source);
        $defaultfitmode = (string)($sourcesettings->fitmode ?? self::FIT_MODE_BANNER);

        foreach (self::get_preview_layer_specs_for_source($source) as $layerspec) {
            $record = $layerspec['record'];
            $isinherited = (string)self::get_record_source_key($record) !== (string)$source->sourcekey;
            if ($elementid > 0 && (int)$record->id === $elementid) {
                $currentlayer = self::export_modal_preview_layer($layerspec, false, false);
                continue;
            }

            $layer = self::export_modal_preview_layer($layerspec, true, $isinherited);
            if ($layer !== null) {
                $contextlayers[] = $layer;
            }
        }

        return [
            'defaultfitmode' => $defaultfitmode,
            'hascontextlayers' => !empty($contextlayers),
            'contextlayers' => $contextlayers,
            'currentlayer' => $currentlayer,
        ];
    }

    /**
     * Export the selected source layers for the admin visual editor.
     *
     * @param \stdClass $source
     * @return array
     */
    public static function export_source_visual_editor_definition(\stdClass $source): array {
        $layers = [];
        $defaultfitmode = (string)(self::get_source_settings($source)->fitmode ?? self::FIT_MODE_BANNER);

        foreach (self::get_source_elements($source) as $record) {
            $fitmode = self::get_effective_fit_mode_for_record($record, (int)($source->categoryid ?? 0));
            if (self::get_banner_image_file($record)) {
                $layer = self::export_modal_preview_image_layer($record, $fitmode, false, false);
                if ($layer) {
                    $layer['enabled'] = !empty($record->isenabled);
                    $layer['editable'] = true;
                    $layers[] = $layer;
                }
                continue;
            }

            if (!empty($record->borderenabled)) {
                $layer = self::export_modal_preview_border_layer($record, false, false);
                $layer['enabled'] = !empty($record->isenabled);
                $layer['editable'] = false;
                $layers[] = $layer;
            }
        }

        return [
            'defaultfitmode' => $defaultfitmode,
            'haslayers' => !empty($layers),
            'layers' => $layers,
        ];
    }

    /**
     * Prepare filemanager defaults for any source.
     *
     * @param \stdClass $source
     * @param int $elementid
     * @param bool $allowmultiple
     * @return \stdClass
     */
    public static function get_source_form_data(\stdClass $source, int $elementid = 0, bool $allowmultiple = true): \stdClass {
        $context = \context_system::instance();
        $record = $elementid ? self::get_banner_element($elementid) : null;
        if (!$record) {
            $record = (object)[
                'id' => 0,
                'categoryid' => $source->categoryid ?? 0,
                'sourcekey' => $source->sourcekey,
                'name' => '',
                'sortorder' => self::get_next_sortorder_for_source($source),
                'isenabled' => 1,
                'fitmodeoverride' => '',
                'positionanchor' => self::POSITION_CENTER,
                'offsettoppercent' => 0,
                'offsetrightpercent' => 0,
                'offsetbottompercent' => 0,
                'offsetleftpercent' => 0,
                'customwidthpercent' => 100,
                'customheightpercent' => 100,
                'customsizekeepaspect' => 1,
                'dynamicimagesizeenabled' => 0,
                'borderenabled' => 0,
                'bordercolor' => '#56B9C0',
                'borderwidth' => 2.5,
                'borderopacity' => 0,
                'borderfade' => 0,
                'borderstyle' => self::BORDER_STYLE_SOLID,
                'borderdashlength' => 24,
                'bordersides' => 'top,right,bottom,left',
                'borderinnerrounded' => 0,
                'fileitemid' => 0,
            ];
        }

        if (is_array($record->bordersides ?? null)) {
            $bordersides = array_filter(array_map('trim', $record->bordersides));
        } else {
            $bordersides = array_filter(array_map('trim', explode(',', (string)($record->bordersides ?? 'top,right,bottom,left'))));
        }
        $allbordersides = ['top', 'right', 'bottom', 'left'];
        if (!array_diff($allbordersides, $bordersides)) {
            array_unshift($bordersides, 'all');
        }

        $data = (object)[
            'elementid' => (int)$record->id,
            'categoryid' => (int)($source->categoryid ?? 0),
            'sourcekey' => $source->sourcekey,
            'hasexistingimage' => self::get_banner_image_file($record) ? 1 : 0,
            'currentisborderlayer' => (!self::get_banner_image_file($record) && !empty($record->borderenabled)) ? 1 : 0,
            'sourcehasborderlayer' => !empty(self::get_source_border_conflict_state($source, (int)$record->id)['blocked']) ? 1 : 0,
            'bordersidesvalue' => implode(',', array_values(array_filter($bordersides, static function(string $side): bool {
                return $side !== 'all';
            }))),
            'name' => $record->name ?? '',
            'sortorder' => (int)$record->sortorder,
            'isenabled' => (int)$record->isenabled,
            'fitmodeoverride' => (string)($record->fitmodeoverride ?? ''),
            'positionanchor' => (string)($record->positionanchor ?? self::POSITION_CENTER),
            'offsettoppercent' => (float)($record->offsettoppercent ?? 0),
            'offsetrightpercent' => (float)($record->offsetrightpercent ?? 0),
            'offsetbottompercent' => (float)($record->offsetbottompercent ?? 0),
            'offsetleftpercent' => (float)($record->offsetleftpercent ?? 0),
            'customwidthpercent' => (float)($record->customwidthpercent ?? 100),
            'customheightpercent' => (float)($record->customheightpercent ?? 100),
            'customsizekeepaspect' => (int)($record->customsizekeepaspect ?? 1),
            'dynamicimagesizeenabled' => (int)($record->dynamicimagesizeenabled ?? 0),
            'borderenabled' => (int)($record->borderenabled ?? 0),
            'bordercolor' => (string)($record->bordercolor ?? '#56B9C0'),
            'borderwidth' => (float)($record->borderwidth ?? 2.5),
            'borderopacity' => (float)round(((float)($record->borderopacity ?? 0)) * 100, 2),
            'borderfade' => (float)round(((float)($record->borderfade ?? 0)) * 100, 2),
            'borderstyle' => (string)($record->borderstyle ?? self::BORDER_STYLE_SOLID),
            'borderdashlength' => (int)($record->borderdashlength ?? 24),
            'bordersides' => $bordersides,
            'bordersidesgroup' => self::get_border_sides_group_defaults($bordersides),
            'borderinnerrounded' => (int)($record->borderinnerrounded ?? 0),
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

        $categoryids = array_unique(array_merge(
            array_map('intval', $DB->get_fieldset_select('local_course_banner_elements', 'DISTINCT categoryid', 'categoryid IS NOT NULL')),
            array_map('intval', $DB->get_fieldset_select('local_course_banner_order', 'DISTINCT categoryid', 'categoryid IS NOT NULL'))
        ));
        $items = [];
        foreach ($categoryids as $categoryindex => $categoryid) {
            $elements = self::get_category_elements((int)$categoryid);
            $settings = self::get_category_settings((int)$categoryid);
            $hasstoredsettings = !empty($settings->id);
            if (empty($elements) && !$hasstoredsettings) {
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

            $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id,name,path,parent', IGNORE_MISSING);
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
            $compositionmode = $settings->compositionmode ?? self::MODE_RANDOM;
            $fitmode = $settings->fitmode ?? self::FIT_MODE_ORIGINAL;
            $thumbnails = self::export_element_thumbnails($elements);
            $bordercount = self::count_border_elements($elements);
            $parentkey = (string)($settings->sourceparentkey ?? '');
            $isroot = !empty($settings->sourceisroot) || $parentkey === '';
            $parentsource = (!$isroot && $parentkey !== '') ? self::resolve_source($parentkey) : null;
            $parentdisplayvalue = $isroot
                ? get_string('rootcategory', 'local_course_banner_builder')
                : ($parentsource->label ?? get_string('sourcechain:none', 'local_course_banner_builder'));

            $items[] = [
                'categoryname' => self::get_category_nested_name_from_record($category),
                'sourcetypelabel' => get_string('sourcetypecategory', 'local_course_banner_builder'),
                'sourcekey' => self::get_category_source_key((int)$categoryid),
                'sourceparentkey' => $isroot ? '' : $parentkey,
                'sourceisroot' => $isroot,
                'categoryid' => (int)$categoryid,
                'pathsort' => $pathsort,
                'rootcategoryid' => reset($pathids) ?: (int)$categoryid,
                'hierarchylevel' => $depth,
                'hierarchyarrows' => str_repeat('-> ', $depth),
                'hierarchylabel' => self::get_hierarchy_relation_label($depth),
                'parentfieldid' => 'local-course-banner-builder-configured-parent-' . (int)$categoryid,
                'parentoptionsid' => 'local-course-banner-builder-configured-parent-options-' . (int)$categoryid,
                'parentdisplayvalue' => $parentdisplayvalue,
                'parentselectedvalue' => $isroot ? '' : $parentkey,
                'parentoptions' => self::export_inline_setting_options(
                    self::get_source_parent_options(self::get_category_source_key((int)$categoryid)),
                    $isroot ? '' : $parentkey
                ),
                'isisolated' => false,
                'shortcircuithelp' => get_string('sourceshortcircuithelp', 'local_course_banner_builder'),
                'shortcircuitpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('sourceshortcircuithelp', 'local_course_banner_builder') . '</p></div>',
                'rowclass' => $rowclass,
                'layercount' => count($elements),
                'layercountdisplay' => (string)count($elements),
                'compositionmodelabel' => self::get_composition_mode_options()[$compositionmode] ?? $compositionmode,
                'fitmodelabel' => self::get_fit_mode_options()[$fitmode] ?? $fitmode,
                'thumbnails' => $thumbnails,
                'hasthumbnails' => !empty($thumbnails),
                'hasbordercaption' => !empty($thumbnails) && $bordercount > 0,
                'bordercaption' => self::format_additional_border_label($bordercount),
                'hasmorethumbnails' => count($elements) > self::ADMIN_THUMB_LIMIT,
                'morethumbnailscount' => max(0, count($elements) - self::ADMIN_THUMB_LIMIT),
                'nothumbnailslabel' => self::format_no_thumbnail_label($bordercount),
                'nothumbnailsisborderlabel' => $bordercount > 0,
                'sesskey' => sesskey(),
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
                'saveparentfield' => 1,
            ];
        }

        if (self::table_field_exists('local_course_banner_elements', 'sourcekey')) {
            $sourcekeys = array_unique(array_merge(
                $DB->get_fieldset_select(
                    'local_course_banner_elements',
                    'DISTINCT sourcekey',
                    'sourcetype = :sourcetype AND sourcekey IS NOT NULL',
                    ['sourcetype' => self::SOURCE_TYPE_CUSTOMFIELD]
                ),
                $DB->get_fieldset_select(
                    'local_course_banner_order',
                    'DISTINCT sourcekey',
                    'sourcetype = :sourcetype AND sourcekey IS NOT NULL',
                    ['sourcetype' => self::SOURCE_TYPE_CUSTOMFIELD]
                )
            ));
            foreach ($sourcekeys as $sourcekey) {
                $source = self::resolve_source((string)$sourcekey);
                if (!$source) {
                    continue;
                }
                $elements = self::get_source_elements($source);
                $settings = self::get_source_settings($source);
                $hasstoredsettings = !empty($settings->id);
                if (empty($elements) && !$hasstoredsettings) {
                    continue;
                }
                $previewrecord = null;
                foreach ($elements as $element) {
                    if (self::get_banner_image_url($element)) {
                        $previewrecord = $element;
                        break;
                    }
                }
                $compositionmode = $settings->compositionmode ?? self::MODE_RANDOM;
                $fitmode = $settings->fitmode ?? self::FIT_MODE_ORIGINAL;
                $thumbnails = self::export_element_thumbnails($elements);
                $bordercount = self::count_border_elements($elements);
                $parentkey = (string)($settings->sourceparentkey ?? '');
                $isroot = !empty($settings->sourceisroot) || $parentkey === '';
                $parentsource = (!$isroot && $parentkey !== '') ? self::resolve_source($parentkey) : null;
                $parentdisplayvalue = $isroot
                    ? get_string('rootcategory', 'local_course_banner_builder')
                    : ($parentsource->label ?? get_string('sourcechain:none', 'local_course_banner_builder'));
                $items[] = [
                    'categoryname' => $source->label,
                    'sourcetypelabel' => get_string('sourcetypecustomfield', 'local_course_banner_builder'),
                    'sourcekey' => $source->sourcekey,
                    'sourceparentkey' => $isroot ? '' : $parentkey,
                    'sourceisroot' => $isroot,
                    'categoryid' => 0,
                    'pathsort' => 'zz-customfield-' . $source->sourcekey,
                    'rootcategoryid' => crc32($source->sourcekey),
                    'hierarchylevel' => 0,
                    'hierarchyarrows' => '',
                    'hierarchylabel' => get_string('coursecustomfields', 'local_course_banner_builder'),
                    'parentfieldid' => 'local-course-banner-builder-configured-parent-' . md5($source->sourcekey),
                    'parentoptionsid' => 'local-course-banner-builder-configured-parent-options-' . md5($source->sourcekey),
                    'parentdisplayvalue' => $parentdisplayvalue,
                    'parentselectedvalue' => $isroot ? '' : $parentkey,
                    'parentoptions' => self::export_inline_setting_options(
                        self::get_source_parent_options($source->sourcekey),
                        $isroot ? '' : $parentkey
                    ),
                    'isisolated' => false,
                    'shortcircuithelp' => '',
                    'shortcircuitpopovercontent' => '',
                    'rowclass' => 'local-course-banner-builder-depth-0',
                    'layercount' => count($elements),
                    'layercountdisplay' => (string)count($elements),
                    'compositionmodelabel' => self::get_composition_mode_options()[$compositionmode] ?? $compositionmode,
                    'fitmodelabel' => self::get_fit_mode_options()[$fitmode] ?? $fitmode,
                    'thumbnails' => $thumbnails,
                    'hasthumbnails' => !empty($thumbnails),
                    'hasbordercaption' => !empty($thumbnails) && $bordercount > 0,
                    'bordercaption' => self::format_additional_border_label($bordercount),
                    'hasmorethumbnails' => count($elements) > self::ADMIN_THUMB_LIMIT,
                    'morethumbnailscount' => max(0, count($elements) - self::ADMIN_THUMB_LIMIT),
                    'nothumbnailslabel' => self::format_no_thumbnail_label($bordercount),
                    'nothumbnailsisborderlabel' => $bordercount > 0,
                    'sesskey' => sesskey(),
                    'editurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                        'sourcekey' => $source->sourcekey,
                    ]))->out(false),
                    'categoryadminurl' => (new \moodle_url('/course/customfield.php'))->out(false),
                    'deletecategoryurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                        'sourcekey' => $source->sourcekey,
                        'deletesourcecontent' => $source->sourcekey,
                        'sesskey' => sesskey(),
                    ]))->out(false),
                    'deletecategoryimagesurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                        'sourcekey' => $source->sourcekey,
                        'deletesourceimages' => $source->sourcekey,
                        'sesskey' => sesskey(),
                    ]))->out(false),
                    'saveparentfield' => 1,
                ];
            }
        }

        self::apply_source_chain_order($items);
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
        $chaincolors = [
            '#0f6cbf',
            '#198754',
            '#0dcaf0',
            '#c7661a',
            '#20c997',
        ];

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

            $depth = max(0, (int)($item['hierarchylevel'] ?? 0));
            $chaincolor = $chaincolors[$previouscolorindex ?? 0] ?? $chaincolors[0];
            $backgroundalpha = min(0.28, 0.06 + ($depth * 0.055));
            $borderalpha = min(1, 0.82 + ($depth * 0.06));
            $item['rowstyle'] = self::build_chain_row_style($chaincolor, $backgroundalpha, $borderalpha);

            $previousrootid = $rootid;
        }
        unset($item);
    }

    /**
     * Build CSS custom properties for one configured-source row.
     *
     * @param string $hexcolor
     * @param float $backgroundalpha
     * @param float $borderalpha
     * @return string
     */
    protected static function build_chain_row_style(string $hexcolor, float $backgroundalpha, float $borderalpha): string {
        [$red, $green, $blue] = self::hex_to_rgb($hexcolor);
        return '--local-course-banner-chain-bg: rgba(' . $red . ', ' . $green . ', ' . $blue . ', ' .
            number_format($backgroundalpha, 3, '.', '') . ');' .
            '--local-course-banner-chain-border: rgba(' . $red . ', ' . $green . ', ' . $blue . ', ' .
            number_format($borderalpha, 3, '.', '') . ');';
    }

    /**
     * Convert a hex color to RGB.
     *
     * @param string $hexcolor
     * @return array
     */
    protected static function hex_to_rgb(string $hexcolor): array {
        $color = ltrim(trim($hexcolor), '#');
        if (strlen($color) === 3) {
            $color = preg_replace('/(.)/', '$1$1', $color);
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return [15, 108, 191];
        }
        return [
            hexdec(substr($color, 0, 2)),
            hexdec(substr($color, 2, 2)),
            hexdec(substr($color, 4, 2)),
        ];
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
     * Build a readable category path name directly from DB records.
     *
     * @param \stdClass $category
     * @return string
     */
    protected static function get_category_nested_name_from_record(\stdClass $category): string {
        global $DB;

        $pathids = array_filter(array_map('intval', explode('/', trim((string)$category->path, '/'))));
        $names = [];
        foreach ($pathids as $pathid) {
            $pathcategory = $DB->get_record('course_categories', ['id' => $pathid], 'name', IGNORE_MISSING);
            if ($pathcategory) {
                $names[] = format_string($pathcategory->name);
            }
        }

        return implode(' / ', $names);
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
     * Get positioned image overlays for the course header banner.
     *
     * These layers are intentionally kept out of the generated overview PNG and
     * rendered by the browser so their placement can adapt to the displayed banner.
     *
     * @param \stdClass $course
     * @return array
     */
    public static function get_course_header_image_overlays(\stdClass $course): array {
        if (empty($course->category)) {
            return [];
        }

        $records = self::sort_layer_specs(self::get_enabled_category_elements_for_course($course));
        $overlays = [];
        foreach ($records as $index => $layerspec) {
            $record = $layerspec['record'];
            $fitmode = $layerspec['fitmode'] ?? self::FIT_MODE_BANNER;
            $file = self::get_banner_image_file($record);
            if (!$file || !self::should_render_as_native_course_header_overlay_by_index($records, $index)) {
                continue;
            }

            $styles = self::build_native_course_header_overlay_styles($record, $file, $fitmode);
            if (empty($styles['wrapperstyle']) || empty($styles['imagestyle'])) {
                continue;
            }

            $imageurl = self::get_banner_image_url($record);
            if (!$imageurl) {
                continue;
            }

            $overlays[] = [
                'index' => $index + 1,
                'url' => $imageurl->out(false),
                'wrapperstyle' => $styles['wrapperstyle'] . ' z-index: ' . self::get_preview_layer_zindex($record) . ';',
                'imagestyle' => $styles['imagestyle'],
            ];
        }

        return $overlays;
    }

    /**
     * Order configured sources by explicit source-chain parentage.
     *
     * @param array $items
     * @return void
     */
    protected static function apply_source_chain_order(array &$items): void {
        $bykey = [];
        foreach ($items as $item) {
            $key = (string)($item['sourcekey'] ?? '');
            if ($key !== '') {
                $bykey[$key] = $item;
            }
        }

        $children = [];
        foreach ($items as $item) {
            $key = (string)($item['sourcekey'] ?? '');
            $parentkey = (string)($item['sourceparentkey'] ?? '');
            if ($key === '' || $parentkey === '' || !isset($bykey[$parentkey])) {
                continue;
            }
            $children[$parentkey][] = $key;
        }

        $sortkeys = static function(array &$keys) use ($bykey): void {
            usort($keys, static function(string $a, string $b) use ($bykey): int {
                $aname = (string)($bykey[$a]['categoryname'] ?? $a);
                $bname = (string)($bykey[$b]['categoryname'] ?? $b);
                $namecompare = strcasecmp($aname, $bname);
                if ($namecompare !== 0) {
                    return $namecompare;
                }
                return $a <=> $b;
            });
        };
        foreach ($children as &$childkeys) {
            $sortkeys($childkeys);
        }
        unset($childkeys);

        $branchlabels = [];
        $assignbranch = function(string $parentkey, string $label) use (&$assignbranch, &$branchlabels, $children): void {
            foreach ($children[$parentkey] ?? [] as $childkey) {
                $branchlabels[$childkey] = $label;
                $assignbranch($childkey, $label);
            }
        };
        foreach ($children as $parentkey => $childkeys) {
            if (count($childkeys) <= 1) {
                continue;
            }
            $parentname = (string)($bykey[$parentkey]['categoryname'] ?? $parentkey);
            foreach (array_values($childkeys) as $branchindex => $childkey) {
                $branchlabel = get_string('sourcechainbranch', 'local_course_banner_builder', (object)[
                    'number' => $branchindex + 1,
                    'source' => $parentname,
                ]);
                $branchlabels[$childkey] = $branchlabel;
                $assignbranch($childkey, $branchlabel);
            }
        }

        $roots = [];
        foreach ($items as $item) {
            $key = (string)($item['sourcekey'] ?? '');
            $parentkey = (string)($item['sourceparentkey'] ?? '');
            if ($key === '' || !empty($item['sourceisroot']) || $parentkey === '' || !isset($bykey[$parentkey])) {
                $roots[] = $key;
            }
        }
        $roots = array_values(array_unique(array_filter($roots)));
        $sortkeys($roots);

        $ordered = [];
        $visited = [];
        $rootindex = 0;
        $walk = function(string $key, int $level, int $rootid) use (
            &$walk,
            &$ordered,
            &$visited,
            $children,
            $bykey,
            $branchlabels
        ): void {
            if (isset($visited[$key]) || empty($bykey[$key])) {
                return;
            }
            $visited[$key] = true;
            $item = $bykey[$key];
            $childkeys = $children[$key] ?? [];
            $parentkey = (string)($item['sourceparentkey'] ?? '');
            $item['hierarchylevel'] = $level;
            $item['hierarchyarrows'] = str_repeat('| ', $level);
            $item['hierarchylevelmarkers'] = [];
            for ($levelmarker = 0; $levelmarker < $level; $levelmarker++) {
                $item['hierarchylevelmarkers'][] = [
                    'style' => 'left: ' . number_format(0.78 + ($levelmarker * 0.36), 2, '.', '') . 'rem;',
                ];
            }
            $item['hierarchylabel'] = $level === 0
                ? get_string('rootcategory', 'local_course_banner_builder')
                : '';
            $item['isrootrow'] = $level === 0;
            $item['haschildren'] = !empty($childkeys);
            $item['sourcekeyhash'] = md5($key);
            $item['parentkeyhash'] = $parentkey !== '' ? md5($parentkey) : '';
            $item['hasbranchsplit'] = count($childkeys) > 1;
            $item['branchsplithelp'] = get_string('sourcechainsplithelp', 'local_course_banner_builder');
            $item['branchsplitpopovercontent'] = '<div class="no-overflow"><p>' .
                get_string('sourcechainsplithelp', 'local_course_banner_builder') . '</p></div>';
            $item['hasbranchlabel'] = !empty($branchlabels[$key]);
            $item['branchlabel'] = $branchlabels[$key] ?? '';
            $item['hashierarchybadge'] = !empty($item['hasbranchsplit']) || !empty($item['hasbranchlabel']);
            $item['rootcategoryid'] = $rootid;
            $rowclasses = self::HIERARCHY_ROW_CLASSES;
            $item['rowclass'] = preg_replace('/\blocal-course-banner-builder-depth-\d+\b/', '', (string)($item['rowclass'] ?? ''));
            $item['rowclass'] = trim($item['rowclass'] . ' ' . $rowclasses[min($level, count($rowclasses) - 1)]);
            $ordered[] = $item;
            foreach ($childkeys as $childkey) {
                $walk($childkey, $level + 1, $rootid);
            }
        };

        foreach ($roots as $rootkey) {
            $rootindex++;
            $rootid = abs((int)crc32((string)$rootkey)) ?: $rootindex;
            $walk((string)$rootkey, 0, $rootid);
        }

        foreach (array_keys($bykey) as $key) {
            if (!isset($visited[$key])) {
                $rootindex++;
                $walk((string)$key, 0, abs((int)crc32((string)$key)) ?: $rootindex);
            }
        }

        $items = $ordered;
    }

    /**
     * Get the hierarchy-cell parent label for a source key.
     *
     * @param string $parentkey
     * @return string
     */
    protected static function get_source_parent_hierarchy_label(string $parentkey): string {
        $source = self::resolve_source($parentkey);
        return $source ? $source->label : get_string('sourcechain:none', 'local_course_banner_builder');
    }

    /**
     * Whether the native Moodle banner should keep a generated background image.
     *
     * @param \stdClass $course
     * @return bool
     */
    public static function course_has_native_banner_background(\stdClass $course): bool {
        if (empty($course->category)) {
            return false;
        }

        $records = self::sort_layer_specs(self::get_enabled_category_elements_for_course($course));
        foreach ($records as $index => $layerspec) {
            $record = $layerspec['record'];
            if (!self::get_banner_image_file($record)) {
                continue;
            }
            if (!self::should_render_as_native_course_header_overlay_by_index($records, $index)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a course has at least one enabled layer in its applicable source chain.
     *
     * @param \stdClass $course
     * @return bool
     */
    public static function course_has_applicable_banner_layers(\stdClass $course): bool {
        if (empty($course->id) || empty($course->category)) {
            return false;
        }

        foreach (self::get_enabled_category_elements_for_course($course) as $layerspec) {
            if (self::get_banner_image_file($layerspec['record'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if one image layer should be repeated as an HTML header overlay.
     *
     * Dynamic image layers are always rendered as HTML overlays. To preserve
     * sort order above them, any later image layers are also repeated as HTML
     * overlays in the header so they can sit above the dynamic ones.
     *
     * @param array $sortedlayerspecs
     * @param int $index
     * @return bool
     */
    protected static function should_render_as_course_header_overlay(array $sortedlayerspecs, int $index): bool {
        $layerspec = $sortedlayerspecs[$index] ?? null;
        if (!$layerspec) {
            return false;
        }

        $record = $layerspec['record'];
        if (self::is_border_only_layer($record) || !self::get_banner_image_file($record)) {
            return false;
        }

        if (self::is_html_positioned_layer($record, (string)($layerspec['fitmode'] ?? self::FIT_MODE_BANNER))) {
            return true;
        }

        for ($cursor = 0; $cursor < $index; $cursor++) {
            $previous = $sortedlayerspecs[$cursor]['record'] ?? null;
            if ($previous && self::is_dynamic_image_layer($previous)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if one image layer should be rendered as a native Moodle HTML overlay,
     * including any later image layers that must stay above an earlier overlay layer.
     *
     * @param array $sortedlayerspecs
     * @param int $index
     * @return bool
     */
    protected static function should_render_as_native_course_header_overlay_by_index(array $sortedlayerspecs, int $index): bool {
        $layerspec = $sortedlayerspecs[$index] ?? null;
        if (!$layerspec) {
            return false;
        }

        $record = $layerspec['record'];
        $fitmode = (string)($layerspec['fitmode'] ?? self::FIT_MODE_BANNER);
        if (!self::get_banner_image_file($record) || self::is_border_only_layer($record)) {
            return false;
        }

        if (self::should_render_as_native_course_header_overlay($record, $fitmode)) {
            return true;
        }

        for ($cursor = 0; $cursor < $index; $cursor++) {
            $previousspec = $sortedlayerspecs[$cursor] ?? null;
            if (!$previousspec) {
                continue;
            }
            $previousrecord = $previousspec['record'] ?? null;
            $previousfitmode = (string)($previousspec['fitmode'] ?? self::FIT_MODE_BANNER);
            if ($previousrecord && self::should_render_as_native_course_header_overlay($previousrecord, $previousfitmode)) {
                return true;
            }
        }

        return false;
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
     * Sync courses affected by a source.
     *
     * @param \stdClass $source
     * @return void
     */
    public static function sync_courses_for_source(\stdClass $source): void {
        if ($source->type === self::SOURCE_TYPE_CATEGORY) {
            self::sync_courses_for_category_tree((int)$source->categoryid);
            return;
        }

        self::sync_all_courses_from_category_banners();
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

        if (self::table_field_exists('local_course_banner_elements', 'sourcetype')) {
            $hascustomfieldsources = $DB->record_exists('local_course_banner_elements', [
                'sourcetype' => self::SOURCE_TYPE_CUSTOMFIELD,
            ]);
            if ($hascustomfieldsources) {
                $courses = $DB->get_records('course', null, '', 'id, category');
                foreach ($courses as $course) {
                    try {
                        self::sync_course_overview_image($course);
                    } catch (\Throwable $e) {
                        debugging('Course banner sync failed for course ' . $course->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                    }
                }
            }
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
        if (
            count($records) === 1 &&
            ($records[0]['fitmode'] ?? self::FIT_MODE_BANNER) === self::FIT_MODE_ORIGINAL &&
            self::get_banner_image_file($records[0]['record']) &&
            !self::should_render_as_course_header_overlay($records, 0)
        ) {
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
     * Get the generated course banner image URL when available.
     *
     * @param int $courseid
     * @return \moodle_url|null
     */
    public static function get_course_banner_image_url(int $courseid): ?\moodle_url {
        if (!$courseid) {
            return null;
        }

        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], 'id,category', IGNORE_MISSING);
        if (!$course || !self::course_has_applicable_banner_layers($course)) {
            return null;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'filename DESC', false);
        foreach ($files as $file) {
            if ($file->is_valid_image() && str_starts_with($file->get_filename(), self::MANAGED_OVERVIEW_PREFIX)) {
                return \moodle_url::make_pluginfile_url(
                    $context->id,
                    'course',
                    'overviewfiles',
                    null,
                    '/',
                    $file->get_filename()
                );
            }
        }

        return null;
    }

    /**
     * Get the generated course banner stored file when available.
     *
     * @param int $courseid
     * @return \stored_file|null
     */
    public static function get_course_banner_image_file(int $courseid): ?\stored_file {
        if (!$courseid) {
            return null;
        }

        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], 'id,category', IGNORE_MISSING);
        if (!$course || !self::course_has_applicable_banner_layers($course)) {
            return null;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'filename DESC', false);
        foreach ($files as $file) {
            if ($file->is_valid_image() && str_starts_with($file->get_filename(), self::MANAGED_OVERVIEW_PREFIX)) {
                return $file;
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
                self::get_record_source_key($record),
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
        $source = self::resolve_source(self::get_category_source_key($categoryid));
        if (!$source) {
            return ['hasselection' => false];
        }
        return self::export_selected_source($source);
    }

    /**
     * Export the selected source current state for the admin page.
     *
     * @param \stdClass $source
     * @return array
     */
    public static function export_selected_source(\stdClass $source): array {
        $categoryid = (int)($source->categoryid ?? 0);
        $settings = self::get_source_settings($source);
        $hasstoredsettings = !empty($settings->id);
        $compositionmode = $settings->compositionmode ?? self::MODE_RANDOM;
        $elements = [];
        foreach (self::get_source_elements($source) as $record) {
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
            $bordersummary = self::export_border_summary($record);
            $layersummary = self::export_layer_display_summary($record);
            $isborderlayer = self::is_border_only_layer($record);
            $isdynamiclayer = self::is_top_image_layer($record);
            $islockedlayer = self::is_locked_order_layer($record);
            $rowclass = 'local-course-banner-builder-layer-row';
            if ($isborderlayer) {
                $rowclass .= ' local-course-banner-builder-layer-row--border';
            }
            $orderlocklabel = $isborderlayer
                ? get_string('borderlayerlockedorderlabel', 'local_course_banner_builder')
                : get_string('dynamiclayerlockedorderlabel', 'local_course_banner_builder');
            $orderlockhelp = $isborderlayer
                ? get_string('borderlayerlockedorderhelp', 'local_course_banner_builder')
                : get_string('dynamiclayerlockedorderhelp', 'local_course_banner_builder');
            $elements[] = [
                'id' => (int)$record->id,
                'rowclass' => $rowclass,
                'formid' => $formid,
                'name' => $record->name ?: get_string('bannerimage', 'local_course_banner_builder') . ' #' . $record->id,
                'sortorder' => (int)$record->sortorder,
                'isborderlayer' => $isborderlayer,
                'isdynamiclayer' => $isdynamiclayer,
                'isreorderable' => !$islockedlayer,
                'hasorderlockhelp' => $islockedlayer,
                'orderlocklabel' => $orderlocklabel,
                'orderlockpopovercontent' => '<div class="no-overflow"><p>' .
                    $orderlockhelp .
                    '</p></div>',
                'enabledlabel' => $record->isenabled ? get_string('yes') : get_string('no'),
                'enabledchecked' => (bool)$record->isenabled,
                'imageurl' => $imageurl ? $imageurl->out(false) : '',
                'dynamiclabel' => get_string('topimageenabled', 'local_course_banner_builder'),
                'dynamicpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('topimageenabled_help_short', 'local_course_banner_builder') .
                    '</p></div>',
                'categoryid' => $categoryid,
                'sourcekey' => $source->sourcekey,
                'sesskey' => sesskey(),
                'showfitoverrideselect' => !$isborderlayer,
                'fitoverrideoptions' => $isborderlayer ? [] : self::export_fit_override_options($fitoverride, $sourcefitmode),
                'hasfitoverride' => !$isborderlayer && $fitoverride !== '',
                'fitoverridehelp' => get_string('fitoverridehelp', 'local_course_banner_builder'),
                'fitoverridecellclass' => (!$isborderlayer && $fitoverride !== '') ? 'local-course-banner-builder-override-cell' : '',
                'fitoverridecellstyle' => self::get_layer_override_cell_style($record, $fitoverride !== ''),
                'fitoverridedisplay' => $isborderlayer
                    ? get_string('nooverridepossibleonborders', 'local_course_banner_builder')
                    : '',
                'haslayersummary' => !empty($layersummary),
                'layersummaryitems' => $layersummary,
                'hasbordersummary' => !empty($bordersummary),
                'bordersummarytitle' => get_string('bordertitle', 'local_course_banner_builder'),
                'bordersummaryitems' => $bordersummary,
                'editurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'sourcekey' => $source->sourcekey,
                    'elementid' => $record->id,
                ]))->out(false),
                'deleteurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'sourcekey' => $source->sourcekey,
                    'deleteelementid' => $record->id,
                    'sesskey' => sesskey(),
                ]))->out(false),
            ];
        }
        $chainborderlayer = self::source_has_border_layer($source) ? [] : self::export_source_chain_border_row($source);
        $hasconfiguration = $hasstoredsettings || !empty($elements);

        $summaryfields = [
            [
                'fieldname' => 'compositionmode',
                'fieldid' => 'local-course-banner-builder-summary-compositionmode',
                'label' => get_string('compositionmode', 'local_course_banner_builder'),
                'displayvalue' => self::get_source_mode_label($source),
                'helptext' => get_string('compositionmode_help', 'local_course_banner_builder'),
                'options' => self::export_inline_setting_options(
                    self::get_composition_mode_options(),
                    $compositionmode
                ),
            ],
            [
                'fieldname' => 'fitmode',
                'fieldid' => 'local-course-banner-builder-summary-fitmode',
                'label' => get_string('fitmode', 'local_course_banner_builder'),
                'displayvalue' => self::get_source_fit_mode_label($source),
                'helptext' => get_string('fitmode_help', 'local_course_banner_builder'),
                'options' => self::export_inline_setting_options(
                    self::get_editable_fit_mode_options(),
                    $settings->fitmode ?? self::FIT_MODE_BANNER
                ),
            ],
        ];

        $parentkey = (string)($settings->sourceparentkey ?? '');
        $parentsource = $parentkey !== '' ? self::resolve_source($parentkey) : null;
        $summaryfields[] = [
            'fieldname' => 'sourceparentkey',
            'fieldid' => 'local-course-banner-builder-summary-sourceparentkey',
            'optionsid' => 'local-course-banner-builder-summary-sourceparentkey-options',
            'label' => get_string('sourceparentkey', 'local_course_banner_builder'),
            'displayvalue' => !empty($settings->sourceisroot)
                ? get_string('sourceisroot', 'local_course_banner_builder')
                : ($parentsource->label ?? get_string('sourcechain:none', 'local_course_banner_builder')),
            'helptext' => get_string('sourceparentkey_help', 'local_course_banner_builder'),
            'selectedvalue' => $parentkey,
            'usesourcedropdown' => true,
            'options' => self::export_inline_setting_options(
                self::get_source_parent_options($source->sourcekey),
                $parentkey
            ),
        ];

        if ($source->type === self::SOURCE_TYPE_CUSTOMFIELD) {
            $summaryfields[] = [
                'fieldname' => 'customfieldpriority',
                'fieldid' => 'local-course-banner-builder-summary-customfieldpriority',
                'label' => get_string('customfieldpriority', 'local_course_banner_builder'),
                'displayvalue' => self::get_customfield_priority_options()[
                    $settings->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY
                ] ?? '',
                'helptext' => get_string('customfieldpriority_help', 'local_course_banner_builder'),
                'options' => self::export_inline_setting_options(
                    self::get_customfield_priority_options(),
                    $settings->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY
                ),
            ];
        }

        return [
            'hasselection' => true,
            'categoryid' => $categoryid,
            'sourcekey' => $source->sourcekey,
            'categoryname' => $source->label,
            'hasconfiguration' => $hasconfiguration,
            'compositionmodelabel' => self::get_source_mode_label($source),
            'iscumulative' => $compositionmode === self::MODE_CUMULATIVE,
            'fitmodelabel' => self::get_source_fit_mode_label($source),
            'deletecategoryurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                'sourcekey' => $source->sourcekey,
                'deletesourcecontent' => $source->sourcekey,
            ]))->out(false),
            'deletecategoryimagesurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                'sourcekey' => $source->sourcekey,
                'deletesourceimages' => $source->sourcekey,
            ]))->out(false),
            'candeletecategory' => $hasconfiguration,
            'candeleteimages' => !empty($elements),
            'sesskey' => sesskey(),
            'haselements' => !empty($elements),
            'haslayertable' => true,
            'elements' => $elements,
            'haschainborderlayer' => !empty($chainborderlayer),
            'chainborderlayer' => $chainborderlayer,
            'summaryfields' => $summaryfields,
            'hassummaryfields' => !empty($summaryfields),
        ];
    }

    /**
     * Export the inherited border row shown in the selected source layer table.
     *
     * @param \stdClass $source
     * @return array
     */
    protected static function export_source_chain_border_row(\stdClass $source): array {
        $chainborder = self::get_source_chain_border_layer($source);
        if (!$chainborder) {
            return [];
        }

        $bordersource = $chainborder['source'];
        $borderrecord = $chainborder['record'];
        $bordersummary = self::export_border_summary($borderrecord);

        return [
            'rowclass' => 'local-course-banner-builder-layer-row--border local-course-banner-builder-layer-row--chain-border',
            'actionlabel' => get_string('chainborderexistinglabel', 'local_course_banner_builder'),
            'name' => $borderrecord->name ?: get_string('bannerimage', 'local_course_banner_builder') . ' #' . $borderrecord->id,
            'sourcelabel' => $bordersource->label ?? $bordersource->sourcekey,
            'enabledlabel' => $borderrecord->isenabled ? get_string('yes') : get_string('no'),
            'hasbordersummary' => !empty($bordersummary),
            'bordersummarytitle' => get_string('bordertitle', 'local_course_banner_builder'),
            'bordersummaryitems' => $bordersummary,
            'sourceediturl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                'sourcekey' => $bordersource->sourcekey,
            ]))->out(false),
        ];
    }

    /**
     * Build one background style for the layer override summary cell.
     *
     * @param \stdClass $record
     * @param bool $hasoverridecontent
     * @return string
     */
    protected static function get_layer_override_cell_style(\stdClass $record, bool $hasoverridecontent): string {
        if (!$hasoverridecontent) {
            return '';
        }

        if (self::is_border_only_layer($record)) {
            $rgb = self::parse_color_to_rgba((string)($record->bordercolor ?? '#56B9C0'));
            if ($rgb) {
                return 'background-color: rgba(' . $rgb[0] . ', ' . $rgb[1] . ', ' . $rgb[2] . ', 0.14);';
            }
        }

        return 'background-color: rgba(201, 102, 26, 0.12);';
    }

    /**
     * Whether one image layer explicitly uses the custom-size fit override.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function layer_uses_custom_fit_override(\stdClass $record): bool {
        return (string)($record->fitmodeoverride ?? '') === self::FIT_MODE_CUSTOM;
    }

    /**
     * Get all enabled category elements for a course from root to leaf.
     *
     * @param \stdClass $course
     * @return array
     */
    public static function get_enabled_category_elements_for_course(\stdClass $course): array {
        $categoryrecords = self::get_category_layer_specs_for_course($course);
        $customfieldrecords = self::get_customfield_layer_specs_for_course($course);
        if (empty($customfieldrecords)) {
            return $categoryrecords;
        }

        $settings = self::get_source_settings($customfieldrecords[0]['source']);
        $hasmanualchain = !empty($settings->id) && (
            !empty($settings->sourceisroot) || trim((string)($settings->sourceparentkey ?? '')) !== ''
        );
        if ($hasmanualchain) {
            return $customfieldrecords;
        }

        $priority = $settings->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY;
        if ($priority === self::CUSTOMFIELD_PRIORITY_CUSTOMFIELD) {
            return $customfieldrecords;
        }
        if ($priority === self::CUSTOMFIELD_PRIORITY_APPEND) {
            if (empty($categoryrecords)) {
                return $customfieldrecords;
            }

            $maxcategoryorder = 0;
            foreach ($categoryrecords as $layerspec) {
                $maxcategoryorder = max($maxcategoryorder, (int)($layerspec['categoryorder'] ?? 0));
            }

            foreach ($customfieldrecords as $index => $layerspec) {
                $customfieldrecords[$index]['categoryorder'] = $maxcategoryorder + 1;
            }

            return self::sort_layer_specs(array_merge($categoryrecords, $customfieldrecords));
        }

        return empty($categoryrecords) ? $customfieldrecords : $categoryrecords;
    }

    /**
     * Get the first matching custom-field-value source with active image layers.
     *
     * @param \stdClass $course
     * @return array
     */
    protected static function get_customfield_layer_specs_for_course(\stdClass $course): array {
        if (empty($course->id)) {
            return [];
        }

        $source = self::get_matching_customfield_source_for_course($course);
        if (!$source) {
            return [];
        }
        return self::sort_layer_specs(
            self::get_layer_specs_for_source_chain($source, (int)($course->category ?? 0), 0)
        );
    }

    /**
     * Resolve the first enabled custom-field-value source matching a course.
     *
     * @param \stdClass $course
     * @return \stdClass|null
     */
    protected static function get_matching_customfield_source_for_course(\stdClass $course): ?\stdClass {
        global $DB;

        $enabled = get_config('local_course_banner_builder', 'enabledcustomfields');
        $enabledids = array_filter(array_map('intval', explode(',', (string)$enabled)));
        if (empty($enabledids)) {
            return null;
        }

        [$insql, $params] = $DB->get_in_or_equal($enabledids, SQL_PARAMS_NAMED);
        $fields = $DB->get_records_select('customfield_field', 'id ' . $insql, $params, '', 'id,name,type,configdata');
        $dataparams = $params;
        $dataparams['courseid'] = (int)$course->id;
        $data = $DB->get_records_select('customfield_data', 'instanceid = :courseid AND fieldid ' . $insql, $dataparams);

        $databyfield = [];
        foreach ($data as $record) {
            $databyfield[(int)$record->fieldid] = $record;
        }

        foreach ($enabledids as $fieldid) {
            if (empty($fields[$fieldid]) || empty($databyfield[$fieldid])) {
                continue;
            }
            $field = $fields[$fieldid];
            $record = $databyfield[$fieldid];
            $rawvalue = self::extract_customfield_data_value($field, $record);
            if ($rawvalue === '') {
                continue;
            }

            $source = self::resolve_source(self::get_customfield_source_key($fieldid, $rawvalue));
            if (!$source || empty(self::get_source_elements($source, true))) {
                continue;
            }
            return $source;
        }

        return null;
    }

    /**
     * Whether the managed overview banner for a course should preserve its
     * original proportions in the header display.
     *
     * This matches the "single layer + original fit" case used when syncing
     * overview files, which is exactly the scenario that looks stretched when
     * forced into the header banner frame.
     *
     * @param int $courseid
     * @return bool
     */
    public static function course_overview_uses_fit_display(int $courseid): bool {
        global $DB;

        if ($courseid <= 0) {
            return false;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'id,category', IGNORE_MISSING);
        if (!$course || empty($course->category)) {
            return false;
        }

        $records = self::get_enabled_category_elements_for_course($course);
        if (empty($records)) {
            return false;
        }

        foreach ($records as $record) {
            $layerrecord = $record['record'];
            if (!self::get_banner_image_file($layerrecord)) {
                continue;
            }
            $fitmode = $record['fitmode'] ?? self::FIT_MODE_BANNER;
            if (in_array($fitmode, [self::FIT_MODE_CUSTOM, self::FIT_MODE_ORIGINAL], true)) {
                return true;
            }
            if (
                self::normalise_percentage((float)($layerrecord->customwidthpercent ?? 100)) !== 100.0 ||
                self::normalise_percentage((float)($layerrecord->customheightpercent ?? 100)) !== 100.0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a dynamic header border overlay definition for border-only layers.
     *
     * @param int $courseid
     * @return array|null
     */
    public static function get_course_header_border_overlay(int $courseid): ?array {
        global $DB;

        if ($courseid <= 0) {
            return null;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'id,category', IGNORE_MISSING);
        if (!$course || empty($course->category)) {
            return null;
        }

        $records = self::get_enabled_category_elements_for_course($course);
        if (empty($records)) {
            return null;
        }

        $borderrecord = null;
        foreach ($records as $layerspec) {
            $record = $layerspec['record'];
            if (empty($record->borderenabled) || self::get_banner_image_file($record)) {
                continue;
            }
            $borderrecord = $record;
        }

        if (!$borderrecord) {
            return null;
        }

        $widthpercent = self::normalise_border_width_percent((float)($borderrecord->borderwidth ?? 0));
        $widthpixels = self::get_border_width_pixels($widthpercent, self::DEFAULT_CANVAS_WIDTH, self::DEFAULT_CANVAS_HEIGHT);
        if ($widthpixels <= 0) {
            return null;
        }

        $sides = self::normalise_border_sides(explode(',', (string)($borderrecord->bordersides ?? 'top,right,bottom,left')));
        $activewidth = $widthpixels . 'px';
        $zerowidth = '0px';
        $style = self::normalise_border_style((string)($borderrecord->borderstyle ?? self::BORDER_STYLE_SOLID));
        $color = self::normalise_color_string((string)($borderrecord->bordercolor ?? '#FFFFFF'));
        $opacity = self::get_effective_border_opacity((float)($borderrecord->borderopacity ?? 0));
        $fade = self::normalise_unit_float((float)($borderrecord->borderfade ?? 0), 0);
        $csscolors = self::build_css_color_pair($color, $opacity);
        $radiuspixels = !empty($borderrecord->borderinnerrounded) ? $widthpixels : 0;
        $cutoutpixels = !empty($borderrecord->borderinnerrounded) ? ($widthpixels + $radiuspixels) : $widthpixels;
        $radius = $radiuspixels . 'px';
        $cutout = $cutoutpixels . 'px';
        $squareoffset = $activewidth;
        $corners = self::get_border_corner_radii($sides, $radius);
        $dashlength = max(4, min(80, (int)round((float)($borderrecord->borderdashlength ?? 24))));
        $dashgap = max(2, (int)round($dashlength * 0.7));

        $stylestring = implode(' ', [
            '--page-header-border-top-width: ' . (in_array('top', $sides, true) ? $activewidth : $zerowidth) . ';',
            '--page-header-border-right-width: ' . (in_array('right', $sides, true) ? $activewidth : $zerowidth) . ';',
            '--page-header-border-bottom-width: ' . (in_array('bottom', $sides, true) ? $activewidth : $zerowidth) . ';',
            '--page-header-border-left-width: ' . (in_array('left', $sides, true) ? $activewidth : $zerowidth) . ';',
            '--page-header-border-top-left-offset: ' . (in_array('top', $sides, true) && in_array('left', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--page-header-border-top-right-offset: ' . (in_array('top', $sides, true) && in_array('right', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--page-header-border-bottom-left-offset: ' . (in_array('bottom', $sides, true) && in_array('left', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--page-header-border-bottom-right-offset: ' . (in_array('bottom', $sides, true) && in_array('right', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--page-header-border-right-top-offset: ' . (in_array('top', $sides, true) && in_array('right', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--page-header-border-right-bottom-offset: ' . (in_array('bottom', $sides, true) && in_array('right', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--page-header-border-left-top-offset: ' . (in_array('top', $sides, true) && in_array('left', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--page-header-border-left-bottom-offset: ' . (in_array('bottom', $sides, true) && in_array('left', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--page-header-border-top-left-corner-size: ' . (in_array('top', $sides, true) && in_array('left', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $activewidth) : $zerowidth) . ';',
            '--page-header-border-top-right-corner-size: ' . (in_array('top', $sides, true) && in_array('right', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $activewidth) : $zerowidth) . ';',
            '--page-header-border-bottom-right-corner-size: ' . (in_array('bottom', $sides, true) && in_array('right', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $activewidth) : $zerowidth) . ';',
            '--page-header-border-bottom-left-corner-size: ' . (in_array('bottom', $sides, true) && in_array('left', $sides, true) ? (!empty($borderrecord->borderinnerrounded) ? $cutout : $activewidth) : $zerowidth) . ';',
            '--page-header-border-top-left-fade-start: ' . ($radiuspixels + ($widthpixels * $fade)) . 'px;',
            '--page-header-border-top-right-fade-start: ' . ($radiuspixels + ($widthpixels * $fade)) . 'px;',
            '--page-header-border-bottom-right-fade-start: ' . ($radiuspixels + ($widthpixels * $fade)) . 'px;',
            '--page-header-border-bottom-left-fade-start: ' . ($radiuspixels + ($widthpixels * $fade)) . 'px;',
            '--page-header-border-color: ' . $color . ';',
            '--page-header-border-style: ' . ($style === self::BORDER_STYLE_DASHED ? 'dashed' : 'solid') . ';',
            '--page-header-border-opacity: ' . $opacity . ';',
            '--page-header-border-color-solid: ' . $csscolors['solid'] . ';',
            '--page-header-border-color-transparent: ' . $csscolors['transparent'] . ';',
            '--page-header-border-fade-stop: ' . max(0, min(100, (int)round((1 - $fade) * 100))) . '%;',
            '--page-header-border-dash-length: ' . $dashlength . 'px;',
            '--page-header-border-dash-gap: ' . $dashgap . 'px;',
            '--page-header-border-top-left-radius: ' . $corners['top-left'] . ';',
            '--page-header-border-top-right-radius: ' . $corners['top-right'] . ';',
            '--page-header-border-bottom-right-radius: ' . $corners['bottom-right'] . ';',
            '--page-header-border-bottom-left-radius: ' . $corners['bottom-left'] . ';',
        ]);

        return [
            'style' => $stylestring,
            'wrapperstyle' => self::build_preview_border_wrapper_style($borderrecord),
            'sidestyles' => self::build_preview_border_side_styles($borderrecord),
            'boxstyle' => implode(' ', [
                'position: absolute;',
                'inset: 0;',
                'pointer-events: none;',
                'z-index: 6;',
                'box-sizing: border-box;',
                'border-top-width: ' . (in_array('top', $sides, true) ? $activewidth : $zerowidth) . ';',
                'border-right-width: ' . (in_array('right', $sides, true) ? $activewidth : $zerowidth) . ';',
                'border-bottom-width: ' . (in_array('bottom', $sides, true) ? $activewidth : $zerowidth) . ';',
                'border-left-width: ' . (in_array('left', $sides, true) ? $activewidth : $zerowidth) . ';',
                'border-style: ' . ($style === self::BORDER_STYLE_DASHED ? 'dashed' : 'solid') . ';',
                'border-color: ' . $csscolors['solid'] . ';',
                'border-top-left-radius: ' . $corners['top-left'] . ';',
                'border-top-right-radius: ' . $corners['top-right'] . ';',
                'border-bottom-right-radius: ' . $corners['bottom-right'] . ';',
                'border-bottom-left-radius: ' . $corners['bottom-left'] . ';',
            ]),
            'isdashed' => $style === self::BORDER_STYLE_DASHED,
        ];
    }

    /**
     * Extract the stored comparable value from Moodle custom field data.
     *
     * @param \stdClass $field
     * @param \stdClass $data
     * @return string
     */
    protected static function extract_customfield_data_value(\stdClass $field, \stdClass $data): string {
        if ($field->type === 'select') {
            $value = (string)($data->intvalue ?? $data->value ?? '');
            return ((int)$value > 0) ? (string)(int)$value : '';
        }

        if (in_array($field->type, ['text', 'textarea'], true)) {
            return self::normalise_customfield_value((string)($data->charvalue ?? $data->shortcharvalue ?? $data->value ?? ''));
        }

        return '';
    }

    /**
     * Get all enabled category elements for a course from root to leaf.
     *
     * @param \stdClass $course
     * @return array
     */
    protected static function get_category_layer_specs_for_course(\stdClass $course): array {
        if (empty($course->category)) {
            return [];
        }

        $targetcategoryid = (int)$course->category;
        $categoryids = self::get_effective_category_chain_for_target($targetcategoryid);
        $records = [];
        foreach ($categoryids as $categoryindex => $categoryid) {
            $chainsource = self::resolve_source(self::get_category_source_key($categoryid));
            if (!$chainsource) {
                continue;
            }
            $records = array_merge(
                $records,
                self::get_layer_specs_for_source_chain($chainsource, $targetcategoryid, $categoryindex)
            );
        }

        return self::sort_layer_specs($records);
    }

    protected static function get_layer_specs_for_source_chain(
        \stdClass $source,
        int $targetcategoryid,
        int $categoryorder,
        array $visited = []
    ): array {
        $sourcekey = (string)($source->sourcekey ?? '');
        if ($sourcekey === '' || isset($visited[$sourcekey])) {
            return [];
        }
        $visited[$sourcekey] = true;

        $settings = self::get_source_settings($source);
        $layers = [];
        $parentkey = trim((string)($settings->sourceparentkey ?? ''));
        if ($parentkey !== '' && empty($settings->sourceisroot)) {
            $parentsource = self::resolve_source($parentkey);
            if ($parentsource) {
                $layers = array_merge(
                    $layers,
                    self::get_layer_specs_for_source_chain($parentsource, $targetcategoryid, $categoryorder - 1, $visited)
                );
            }
        }

        $records = [];
        foreach (self::get_source_elements($source, true) as $record) {
            if (!self::get_banner_image_file($record) && empty($record->borderenabled)) {
                continue;
            }
            $records[] = $record;
        }
        if (empty($records)) {
            return $layers;
        }

        $compositionmode = $settings->compositionmode ?? self::MODE_CUMULATIVE;
        if ($compositionmode === self::MODE_RANDOM) {
            $imagerecords = [];
            $borderrecords = [];
            foreach ($records as $record) {
                if (self::get_banner_image_file($record)) {
                    $imagerecords[] = $record;
                } else if (!empty($record->borderenabled)) {
                    $borderrecords[] = $record;
                }
            }
            if (!empty($imagerecords)) {
                $selectedrecord = self::select_random_source_record($imagerecords, $source, $targetcategoryid);
                $layers[] = [
                    'record' => $selectedrecord,
                    'fitmode' => self::get_effective_fit_mode_for_record($selectedrecord, $targetcategoryid),
                    'categoryorder' => $categoryorder,
                    'source' => $source,
                ];
            }
            foreach ($borderrecords as $record) {
                $layers[] = [
                    'record' => $record,
                    'fitmode' => self::get_effective_fit_mode_for_record($record, $targetcategoryid),
                    'categoryorder' => $categoryorder,
                    'source' => $source,
                ];
            }
            return $layers;
        }

        foreach ($records as $record) {
            $layers[] = [
                'record' => $record,
                'fitmode' => self::get_effective_fit_mode_for_record($record, $targetcategoryid),
                'categoryorder' => $categoryorder,
                'source' => $source,
            ];
        }

        return $layers;
    }

    /**
     * Pick a stable random-mode image for one source/course render.
     *
     * @param array $imagerecords
     * @param \stdClass $source
     * @param int $targetcategoryid
     * @return \stdClass
     */
    protected static function select_random_source_record(array $imagerecords, \stdClass $source, int $targetcategoryid): \stdClass {
        $count = count($imagerecords);
        if ($count <= 1) {
            return reset($imagerecords);
        }

        usort($imagerecords, static function(\stdClass $a, \stdClass $b): int {
            $sortcompare = ((int)$a->sortorder) <=> ((int)$b->sortorder);
            if ($sortcompare !== 0) {
                return $sortcompare;
            }
            return ((int)$a->id) <=> ((int)$b->id);
        });

        $seed = (string)($source->sourcekey ?? '') . ':' . $targetcategoryid;
        $index = abs((int)crc32($seed)) % $count;
        return $imagerecords[$index];
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
            $apriority = self::get_layer_priority($arecord);
            $bpriority = self::get_layer_priority($brecord);
            if ($apriority !== $bpriority) {
                return $apriority <=> $bpriority;
            }
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
     * Returns the human label of the source mode.
     *
     * @param \stdClass $source
     * @return string
     */
    protected static function get_source_mode_label(\stdClass $source): string {
        $mode = self::get_source_settings($source)->compositionmode ?? self::MODE_CUMULATIVE;
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
     * Returns the human label of the source fit mode.
     *
     * @param \stdClass $source
     * @return string
     */
    protected static function get_source_fit_mode_label(\stdClass $source): string {
        $mode = self::get_source_settings($source)->fitmode ?? self::FIT_MODE_BANNER;
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
     * Returns the human label of the source fit scope.
     *
     * @param \stdClass $source
     * @return string
     */
    protected static function get_source_fit_scope_label(\stdClass $source): string {
        $scope = self::get_source_settings($source)->fitapplyscope ?? self::FIT_SCOPE_SELF;
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
                'isdynamic' => !empty($element->dynamicimagesizeenabled),
                'dynamiclabel' => get_string('topimageenabled', 'local_course_banner_builder'),
                'dynamicpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('topimageenabled_help_short', 'local_course_banner_builder') .
                    '</p></div>',
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

        $source = self::resolve_source(self::get_record_source_key($record));
        $settings = $source ? self::get_source_settings($source) : self::get_category_settings((int)$record->categoryid);
        return $settings->fitmode ?? self::FIT_MODE_ORIGINAL;
    }

    /**
     * Export select options for the layer fit override.
     *
     * @param string $selected
     * @return array
     */
    protected static function export_fit_override_options(string $selected, string $sourcefitmode = ''): array {
        $options = [[
            'value' => '',
            'label' => get_string('fitoverride:categorydefault', 'local_course_banner_builder'),
            'selected' => $selected === '',
        ]];

        foreach (self::get_editable_fit_mode_options($selected === self::FIT_MODE_CUSTOM) as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
                'disabled' => ($sourcefitmode !== '' && $value === $sourcefitmode) || $value === self::FIT_MODE_CUSTOM,
                'selected' => $selected === $value,
            ];
        }

        return $options;
    }

    /**
     * Export a readable display summary for one image layer row.
     *
     * @param \stdClass $record
     * @return array
     */
    protected static function export_layer_display_summary(\stdClass $record): array {
        if (!self::get_banner_image_file($record)) {
            return [];
        }

        $items = [];
        $hascustomsize = self::layer_uses_custom_fit_override($record) && (
            self::normalise_percentage((float)($record->customwidthpercent ?? 100)) !== 100.0 ||
            self::normalise_percentage((float)($record->customheightpercent ?? 100)) !== 100.0
        );
        if ($hascustomsize) {
            $sizevalue = self::format_css_percentage((float)($record->customwidthpercent ?? 100)) . ' x ' .
                self::format_css_percentage((float)($record->customheightpercent ?? 100));
            if (!empty($record->customsizekeepaspect)) {
                $sizevalue .= ' (' . get_string('customsizekeepaspect', 'local_course_banner_builder') . ')';
            }
            $items[] = [
                'label' => get_string('customsizesummary', 'local_course_banner_builder'),
                'value' => $sizevalue,
            ];
        }

        $offsets = [];
        if (self::layer_uses_custom_fit_override($record)) {
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $value = self::normalise_percentage((float)($record->{'offset' . $side . 'percent'} ?? 0));
                if ($value <= 0) {
                    continue;
                }
                $offsets[] = get_string('bordersides:' . $side, 'local_course_banner_builder') . ' ' .
                    self::format_css_percentage($value);
            }
        }
        if (!empty($offsets)) {
            $items[] = [
                'label' => get_string('layeroffsetsummary', 'local_course_banner_builder'),
                'value' => implode(', ', $offsets),
            ];
        }

        if (!empty($record->dynamicimagesizeenabled)) {
            $items[] = [
                'label' => get_string('topimageenabled', 'local_course_banner_builder'),
                'value' => get_string('yes'),
            ];
        }

        return $items;
    }

    /**
     * Export a readable border summary for one layer row.
     *
     * @param \stdClass $record
     * @return array
     */
    protected static function export_border_summary(\stdClass $record): array {
        if (empty($record->borderenabled)) {
            return [];
        }

        $sides = self::normalise_border_sides(explode(',', (string)($record->bordersides ?? 'top,right,bottom,left')));
        $sidesvalue = count($sides) === 4
            ? get_string('bordersides:all', 'local_course_banner_builder')
            : implode(', ', array_map(static function(string $side): string {
                return get_string('bordersides:' . $side, 'local_course_banner_builder');
            }, $sides));

        return [
            [
                'label' => get_string('bordercolor', 'local_course_banner_builder'),
                'value' => (string)($record->bordercolor ?? '#FFFFFF'),
            ],
            [
                'label' => get_string('borderwidth', 'local_course_banner_builder'),
                'value' => self::format_border_width_percent((float)($record->borderwidth ?? 0)) . ' %',
            ],
            [
                'label' => get_string('borderstyle', 'local_course_banner_builder'),
                'value' => self::get_border_style_options()[(string)($record->borderstyle ?? self::BORDER_STYLE_SOLID)]
                    ?? (string)($record->borderstyle ?? self::BORDER_STYLE_SOLID),
            ],
            [
                'label' => get_string('borderdashlength', 'local_course_banner_builder'),
                'value' => (string)((int)($record->borderdashlength ?? 24)) . ' px',
            ],
            [
                'label' => get_string('bordersides', 'local_course_banner_builder'),
                'value' => $sidesvalue,
            ],
            [
                'label' => get_string('borderopacity', 'local_course_banner_builder'),
                'value' => (string)round((float)($record->borderopacity ?? 0) * 100) . '%',
            ],
            [
                'label' => get_string('borderfade', 'local_course_banner_builder'),
                'value' => (string)round((float)($record->borderfade ?? 0) * 100) . '%',
            ],
        ];
    }

    /**
     * Export select options for inline source setting editors.
     *
     * @param array $options
     * @param string $selected
     * @return array
     */
    protected static function export_inline_setting_options(array $options, string $selected): array {
        $data = [];
        foreach ($options as $value => $label) {
            $data[] = [
                'value' => $value,
                'label' => $label,
                'selected' => (string)$value === $selected,
            ];
        }

        return $data;
    }

    /**
     * Build the effective preview layer list for one admin source.
     *
     * @param \stdClass $source
     * @return array
     */
    protected static function get_preview_layer_specs_for_source(\stdClass $source): array {
        $layers = [];

        if (($source->type ?? self::SOURCE_TYPE_CATEGORY) === self::SOURCE_TYPE_CATEGORY) {
            $targetcategoryid = (int)($source->categoryid ?? 0);
            if ($targetcategoryid <= 0) {
                return [];
            }

            $categoryids = self::get_effective_category_chain_for_target($targetcategoryid);
            foreach ($categoryids as $categoryindex => $categoryid) {
                $chainsource = self::resolve_source(self::get_category_source_key($categoryid));
                if ($chainsource) {
                    $layers = array_merge(
                        $layers,
                        self::get_layer_specs_for_source_chain($chainsource, $targetcategoryid, $categoryindex)
                    );
                }
            }

            return self::sort_layer_specs($layers);
        }

        return self::sort_layer_specs(self::get_layer_specs_for_source_chain($source, 0, 0));
    }

    /**
     * Export one preview layer.
     *
     * @param array $layerspec
     * @param bool $iscontext
     * @param bool $isinherited
     * @return array|null
     */
    protected static function export_modal_preview_layer(array $layerspec, bool $iscontext, bool $isinherited): ?array {
        $record = $layerspec['record'];
        if (self::get_banner_image_file($record)) {
            return self::export_modal_preview_image_layer(
                $record,
                (string)($layerspec['fitmode'] ?? self::FIT_MODE_BANNER),
                $iscontext,
                $isinherited
            );
        }

        if (!empty($record->borderenabled)) {
            return self::export_modal_preview_border_layer($record, $iscontext, $isinherited);
        }

        return null;
    }

    /**
     * Export one image preview layer.
     *
     * @param \stdClass $record
     * @param string $fitmode
     * @param bool $iscontext
     * @param bool $isinherited
     * @return array|null
     */
    protected static function export_modal_preview_image_layer(
        \stdClass $record,
        string $fitmode,
        bool $iscontext,
        bool $isinherited
    ): ?array {
        $file = self::get_banner_image_file($record);
        $imageurl = self::get_banner_image_url($record);
        if (!$file || !$imageurl) {
            return null;
        }

        $styles = self::build_modal_preview_image_layer_styles($record, $fitmode, $file);
        $imageinfo = $file->get_imageinfo();

        return [
            'type' => 'image',
            'id' => (int)($record->id ?? 0),
            'name' => trim((string)($record->name ?? '')),
            'sortorder' => (int)($record->sortorder ?? 0),
            'zindex' => self::get_preview_layer_zindex($record),
            'url' => $imageurl->out(false),
            'wrapperstyle' => $styles['wrapperstyle'],
            'imagestyle' => $styles['imagestyle'],
            'fitmode' => $fitmode,
            'positionanchor' => (string)($record->positionanchor ?? self::POSITION_CENTER),
            'customwidthpercent' => (float)($record->customwidthpercent ?? 100),
            'customheightpercent' => (float)($record->customheightpercent ?? 100),
            'customsizekeepaspect' => !empty($record->customsizekeepaspect),
            'dynamicimagesizeenabled' => !empty($record->dynamicimagesizeenabled),
            'offsettoppercent' => (float)($record->offsettoppercent ?? 0),
            'offsetrightpercent' => (float)($record->offsetrightpercent ?? 0),
            'offsetbottompercent' => (float)($record->offsetbottompercent ?? 0),
            'offsetleftpercent' => (float)($record->offsetleftpercent ?? 0),
            'imagewidth' => (int)($imageinfo['width'] ?? 0),
            'imageheight' => (int)($imageinfo['height'] ?? 0),
            'iscontext' => $iscontext,
            'isinherited' => $isinherited,
        ];
    }

    /**
     * Export one border preview layer.
     *
     * @param \stdClass $record
     * @param bool $iscontext
     * @param bool $isinherited
     * @return array
     */
    protected static function export_modal_preview_border_layer(
        \stdClass $record,
        bool $iscontext,
        bool $isinherited
    ): array {
        return [
            'type' => 'border',
            'id' => (int)($record->id ?? 0),
            'name' => trim((string)($record->name ?? '')),
            'sortorder' => (int)($record->sortorder ?? 0),
            'zindex' => self::get_preview_layer_zindex($record),
            'wrapperstyle' => self::build_preview_border_wrapper_style($record),
            'sidestyles' => self::build_preview_border_side_styles($record),
            'iscontext' => $iscontext,
            'isinherited' => $isinherited,
        ];
    }

    /**
     * Build responsive admin preview styles for one image layer.
     *
     * @param \stdClass $record
     * @param string $fitmode
     * @param \stored_file|null $file
     * @return array{wrapperstyle:string,imagestyle:string}
     */
    protected static function build_modal_preview_image_layer_styles(
        \stdClass $record,
        string $fitmode,
        ?\stored_file $file
    ): array {
        $anchor = self::normalise_position_anchor((string)($record->positionanchor ?? self::POSITION_CENTER));
        $wrapperstyles = [
            'position: absolute;',
            'display: flex;',
            'align-items: stretch;',
            'justify-content: stretch;',
            'overflow: hidden;',
        ];
        $imagestyles = [
            'display: block;',
            'width: 100%;',
            'height: 100%;',
        ];

        if ($fitmode === self::FIT_MODE_BANNER) {
            $wrapperstyles[] = 'inset: 0;';
            $imagestyles[] = 'object-fit: fill;';
        } else if ($fitmode === self::FIT_MODE_COVER) {
            $box = $file ? self::get_native_overlay_box_percentages($record, $file, $fitmode) : null;
            if ($box) {
                $wrapperstyles[] = 'width: ' . self::format_css_percentage((float)$box['width']) . ';';
                $wrapperstyles[] = 'height: ' . self::format_css_percentage((float)$box['height']) . ';';
                $wrapperstyles = array_merge($wrapperstyles, self::get_html_overlay_position_styles($record, $anchor));
            } else {
                $wrapperstyles[] = 'inset: 0;';
            }
            $imagestyles[] = 'object-fit: fill;';
            $imagestyles[] = 'object-position: ' . self::get_css_object_position_for_anchor($anchor) . ';';
        } else if ($fitmode === self::FIT_MODE_CUSTOM) {
            $box = $file ? self::get_native_overlay_box_percentages($record, $file, $fitmode) : null;
            if (!empty($record->customsizekeepaspect) && $box) {
                $wrapperstyles[] = 'width: ' . self::format_css_percentage((float)$box['width']) . ';';
                $wrapperstyles[] = 'height: ' . self::format_css_percentage((float)$box['height']) . ';';
            } else {
                $wrapperstyles[] = 'width: ' . self::format_css_percentage((float)($record->customwidthpercent ?? 100)) . ';';
                $wrapperstyles[] = 'height: ' . self::format_css_percentage((float)($record->customheightpercent ?? 100)) . ';';
            }
            $wrapperstyles = array_merge($wrapperstyles, self::get_html_overlay_position_styles($record, $anchor));
            $imagestyles[] = 'object-fit: ' . (!empty($record->customsizekeepaspect) ? 'contain' : 'fill') . ';';
            $imagestyles[] = 'object-position: ' . self::get_css_object_position_for_anchor($anchor) . ';';
        } else {
            $imageinfo = $file ? $file->get_imageinfo() : [];
            $imagewidth = (int)($imageinfo['width'] ?? 0);
            if ($imagewidth <= 0) {
                $imagewidth = self::DEFAULT_CANVAS_WIDTH;
            }

            $wrapperstyles[] = 'width: ' . self::format_css_percent($imagewidth, self::DEFAULT_CANVAS_WIDTH) . ';';
            $wrapperstyles = array_merge($wrapperstyles, self::get_html_overlay_position_styles($record, $anchor));
            $imagestyles[] = 'height: auto;';
            $imagestyles[] = 'object-fit: none;';
            $imagestyles[] = 'object-position: ' . self::get_css_object_position_for_anchor($anchor) . ';';
        }

        return [
            'wrapperstyle' => implode(' ', $wrapperstyles),
            'imagestyle' => implode(' ', $imagestyles),
        ];
    }

    /**
     * Build wrapper CSS variables for one preview border layer.
     *
     * @param \stdClass $record
     * @return string
     */
    protected static function build_preview_border_wrapper_style(\stdClass $record): string {
        $widthpercent = self::normalise_border_width_percent((float)($record->borderwidth ?? 0));
        $widthpixels = self::get_border_width_pixels($widthpercent, self::DEFAULT_CANVAS_WIDTH, self::DEFAULT_CANVAS_HEIGHT);
        $sides = self::normalise_border_sides(explode(',', (string)($record->bordersides ?? 'top,right,bottom,left')));
        $opacity = self::get_effective_border_opacity((float)($record->borderopacity ?? 0));
        $fade = self::normalise_unit_float((float)($record->borderfade ?? 0), 0);
        $color = self::normalise_color_string((string)($record->bordercolor ?? '#FFFFFF'));
        $csscolors = self::build_css_color_pair($color, $opacity);
        $width = max(0, $widthpixels) . 'px';
        $zerowidth = '0px';
        $radiuspixels = !empty($record->borderinnerrounded) ? max(8, $widthpixels) : 0;
        $radius = $radiuspixels . 'px';
        $cutout = ($widthpixels + $radiuspixels) . 'px';
        $squareoffset = $width;

        return implode(' ', [
            'position: absolute;',
            'inset: 0;',
            '--local-course-banner-builder-preview-top-width: ' . (in_array('top', $sides, true) ? $width : $zerowidth) . ';',
            '--local-course-banner-builder-preview-right-width: ' . (in_array('right', $sides, true) ? $width : $zerowidth) . ';',
            '--local-course-banner-builder-preview-bottom-width: ' . (in_array('bottom', $sides, true) ? $width : $zerowidth) . ';',
            '--local-course-banner-builder-preview-left-width: ' . (in_array('left', $sides, true) ? $width : $zerowidth) . ';',
            '--local-course-banner-builder-preview-top-left-radius: ' . (!empty($record->borderinnerrounded) && in_array('top', $sides, true) && in_array('left', $sides, true) ? $radius : '0px') . ';',
            '--local-course-banner-builder-preview-top-right-radius: ' . (!empty($record->borderinnerrounded) && in_array('top', $sides, true) && in_array('right', $sides, true) ? $radius : '0px') . ';',
            '--local-course-banner-builder-preview-bottom-right-radius: ' . (!empty($record->borderinnerrounded) && in_array('bottom', $sides, true) && in_array('right', $sides, true) ? $radius : '0px') . ';',
            '--local-course-banner-builder-preview-bottom-left-radius: ' . (!empty($record->borderinnerrounded) && in_array('bottom', $sides, true) && in_array('left', $sides, true) ? $radius : '0px') . ';',
            '--local-course-banner-builder-preview-top-left-offset: ' . (in_array('top', $sides, true) && in_array('left', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-top-right-offset: ' . (in_array('top', $sides, true) && in_array('right', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-bottom-left-offset: ' . (in_array('bottom', $sides, true) && in_array('left', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-bottom-right-offset: ' . (in_array('bottom', $sides, true) && in_array('right', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-right-top-offset: ' . (in_array('top', $sides, true) && in_array('right', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-right-bottom-offset: ' . (in_array('bottom', $sides, true) && in_array('right', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-left-top-offset: ' . (in_array('top', $sides, true) && in_array('left', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-left-bottom-offset: ' . (in_array('bottom', $sides, true) && in_array('left', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $squareoffset) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-top-left-corner-size: ' . (in_array('top', $sides, true) && in_array('left', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $width) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-top-right-corner-size: ' . (in_array('top', $sides, true) && in_array('right', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $width) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-bottom-right-corner-size: ' . (in_array('bottom', $sides, true) && in_array('right', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $width) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-bottom-left-corner-size: ' . (in_array('bottom', $sides, true) && in_array('left', $sides, true) ? (!empty($record->borderinnerrounded) ? $cutout : $width) : $zerowidth) . ';',
            '--local-course-banner-builder-preview-top-left-fade-start: ' . ($radiuspixels + ($widthpixels * $fade)) . 'px;',
            '--local-course-banner-builder-preview-top-right-fade-start: ' . ($radiuspixels + ($widthpixels * $fade)) . 'px;',
            '--local-course-banner-builder-preview-bottom-right-fade-start: ' . ($radiuspixels + ($widthpixels * $fade)) . 'px;',
            '--local-course-banner-builder-preview-bottom-left-fade-start: ' . ($radiuspixels + ($widthpixels * $fade)) . 'px;',
            '--local-course-banner-builder-preview-color-solid: ' . $csscolors['solid'] . ';',
            '--local-course-banner-builder-preview-color-transparent: ' . $csscolors['transparent'] . ';',
            '--local-course-banner-builder-preview-fade-stop: ' . max(0, min(100, (int)round((1 - $fade) * 100))) . '%;',
        ]);
    }

    /**
     * Build per-side inline styles for one preview border layer.
     *
     * @param \stdClass $record
     * @return array
     */
    protected static function build_preview_border_side_styles(\stdClass $record): array {
        $opacity = self::get_effective_border_opacity((float)($record->borderopacity ?? 0));
        $fade = self::normalise_unit_float((float)($record->borderfade ?? 0), 0);
        $color = self::normalise_color_string((string)($record->bordercolor ?? '#FFFFFF'));
        $csscolors = self::build_css_color_pair($color, $opacity);
        $solid = $csscolors['solid'];
        $transparent = $csscolors['transparent'];
        $fadestop = max(0, min(100, (int)round((1 - $fade) * 100))) . '%';
        $isdashed = self::normalise_border_style((string)($record->borderstyle ?? self::BORDER_STYLE_SOLID)) === self::BORDER_STYLE_DASHED;
        $dashlength = max(4, min(80, (int)round((float)($record->borderdashlength ?? 24))));
        $dashgap = max(2, (int)round($dashlength * 0.7));

        $styles = [];
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $direction = match ($side) {
                'top' => 'to bottom',
                'right' => 'to left',
                'bottom' => 'to top',
                default => 'to right',
            };

            if ($isdashed) {
                $gradientdirection = in_array($side, ['top', 'bottom'], true) ? 'to right' : 'to bottom';
                $styles[$side] = 'background-image: repeating-linear-gradient(' . $gradientdirection . ', ' .
                    $solid . ' 0 ' . $dashlength . 'px, transparent ' . $dashlength . 'px ' . ($dashlength + $dashgap) .
                    'px); -webkit-mask-image: linear-gradient(' . $direction . ', #000 0%, #000 ' . $fadestop .
                    ', transparent 100%); mask-image: linear-gradient(' . $direction . ', #000 0%, #000 ' .
                    $fadestop . ', transparent 100%);';
            } else {
                $styles[$side] = 'background-image: linear-gradient(' . $direction . ', ' . $solid . ' 0%, ' .
                    $solid . ' ' . $fadestop . ', ' . $transparent . ' 100%);';
            }
        }

        return $styles;
    }

    /**
     * Count border-enabled elements inside one source.
     *
     * @param array $elements
     * @return int
     */
    protected static function count_border_elements(array $elements): int {
        $count = 0;
        foreach ($elements as $element) {
            if (!empty($element->borderenabled)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Build the admin layer count label for configured sources.
     *
     * @param int $layercount
     * @param int $bordercount
     * @return string
     */
    protected static function format_layer_count_display(int $layercount, int $bordercount): string {
        return (string)$layercount;
    }

    /**
     * Build the fallback label when a source has no image thumbnails to show.
     *
     * @param int $bordercount
     * @return string
     */
    protected static function format_no_thumbnail_label(int $bordercount): string {
        if ($bordercount > 0) {
            return $bordercount . ' ' . get_string(
                $bordercount > 1 ? 'borderlayerplural' : 'borderlayersingular',
                'local_course_banner_builder'
            );
        }

        return get_string('nolayeradded', 'local_course_banner_builder');
    }

    /**
     * Build the compact border caption shown below image thumbnails.
     *
     * @param int $bordercount
     * @return string
     */
    protected static function format_additional_border_label(int $bordercount): string {
        if ($bordercount <= 0) {
            return '';
        }

        return get_string('additionalborderlayers', 'local_course_banner_builder', $bordercount);
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

        $layerspecs = self::sort_layer_specs($layerspecs);
        $overviewlayers = [];
        foreach ($layerspecs as $index => $layerspec) {
            $record = $layerspec['record'];
            if (self::get_banner_image_file($record) && self::should_render_as_native_course_header_overlay_by_index($layerspecs, $index)) {
                continue;
            }
            if (self::get_banner_image_file($record) || !empty($record->borderenabled)) {
                $overviewlayers[] = $layerspec;
            }
        }

        if (empty($overviewlayers)) {
            return self::build_blank_image(
                self::DEFAULT_CANVAS_WIDTH,
                self::DEFAULT_CANVAS_HEIGHT,
                'course_' . $courseid . '_banner.png'
            );
        }

        return self::build_composite_image(
            $overviewlayers,
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
     * Build a fully transparent placeholder image.
     *
     * @param int $width
     * @param int $height
     * @param string $filename
     * @return string|null
     */
    protected static function build_blank_image(int $width, int $height, string $filename): ?string {
        $canvas = imagecreatetruecolor($width, $height);
        if (!$canvas) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);

        $tempdir = make_temp_directory('local_course_banner_builder');
        $filepath = $tempdir . DIRECTORY_SEPARATOR . $filename;
        imagepng($canvas, $filepath);
        imagedestroy($canvas);

        return $filepath;
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
            if (!$file && empty($record->borderenabled)) {
                continue;
            }

            if (!$canvas) {
                $canvas = imagecreatetruecolor($width, $height);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            }

            if (!$file) {
                if (!empty($record->borderenabled)) {
                    self::draw_layer_border($canvas, $record, ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height], $width, $height);
                }
                continue;
            }

            $loadedlayer = self::load_layer_image($file);
            $layer = $loadedlayer['image'] ?? null;
            if (!$layer) {
                if (!empty($record->borderenabled)) {
                    self::draw_layer_border($canvas, $record, ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height], $width, $height);
                }
                continue;
            }

            $layerwidth = imagesx($layer);
            $layerheight = imagesy($layer);

            imagealphablending($canvas, true);
            $bounds = self::render_layer_on_canvas(
                $canvas,
                $layer,
                $record,
                $fitmode,
                $width,
                $height,
                $layerwidth,
                $layerheight,
                $cardmode
            );
            self::draw_layer_border($canvas, $record, $bounds, $width, $height);
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
     * Whether an image layer should be rendered as an HTML overlay in course headers.
     *
     * @param \stdClass $record
     * @param string $fitmode
     * @return bool
     */
    protected static function is_html_positioned_layer(\stdClass $record, string $fitmode): bool {
        return !self::is_border_only_layer($record) && self::get_banner_image_file($record);
    }

    /**
     * Whether one layer should be rendered as a native Moodle HTML overlay.
     *
     * Native Moodle does not have the same banner surface as EasyEdu, so custom
     * positioned layers must stay as HTML overlays to remain visible inside the
     * 4:1 frame.
     *
     * @param \stdClass $record
     * @param string $fitmode
     * @return bool
     */
    protected static function should_render_as_native_course_header_overlay(\stdClass $record, string $fitmode): bool {
        if (!self::get_banner_image_file($record) || self::is_border_only_layer($record)) {
            return false;
        }

        return true;
    }

    /**
     * Build responsive CSS for one HTML-positioned image layer.
     *
     * @param \stdClass $record
     * @param \stored_file $file
     * @param string $fitmode
     * @return string
     */
    protected static function build_html_positioned_layer_style(
        \stdClass $record,
        \stored_file $file,
        string $fitmode
    ): string {
        $imageinfo = $file->get_imageinfo();
        $layerwidth = (int)($imageinfo['width'] ?? 0);
        $layerheight = (int)($imageinfo['height'] ?? 0);
        if ($layerwidth <= 0 || $layerheight <= 0) {
            return '';
        }

        $anchor = self::normalise_position_anchor((string)($record->positionanchor ?? self::POSITION_CENTER));
        $styles = [];
        $styles[] = 'aspect-ratio: ' . $layerwidth . ' / ' . $layerheight . ';';

        if ($fitmode === self::FIT_MODE_BANNER) {
            $styles[] = 'width: 100%;';
            $styles[] = 'height: 100%;';
            $styles[] = 'object-fit: fill;';
        } else if ($fitmode === self::FIT_MODE_COVER) {
            $styles[] = 'width: 100%;';
            $styles[] = 'height: 100%;';
            $styles[] = 'object-fit: contain;';
            $styles[] = 'object-position: ' . self::get_css_object_position_for_anchor($anchor) . ';';
        } else if ($fitmode === self::FIT_MODE_CUSTOM) {
            $styles[] = 'width: ' . self::format_css_percentage((float)($record->customwidthpercent ?? 100)) . ';';
            if (!empty($record->customsizekeepaspect)) {
                $styles[] = 'height: auto;';
                $styles[] = 'max-height: ' . self::format_css_percentage((float)($record->customheightpercent ?? 100)) . ';';
            } else {
                $styles[] = 'height: ' . self::format_css_percentage((float)($record->customheightpercent ?? 100)) . ';';
            }
        } else {
            $styles[] = 'width: ' . self::format_css_percent($layerwidth, self::DEFAULT_CANVAS_WIDTH) . ';';
            $styles[] = 'height: auto;';
        }

        if ($fitmode !== self::FIT_MODE_BANNER) {
            $styles = array_merge($styles, self::get_html_overlay_position_styles($record, $anchor));
        } else {
            $styles[] = 'left: 0;';
            $styles[] = 'top: 0;';
        }
        return implode(' ', $styles);
    }

    /**
     * Build responsive CSS for one native Moodle HTML overlay.
     *
     * @param \stdClass $record
     * @param \stored_file $file
     * @param string $fitmode
     * @return string
     */
    protected static function build_native_html_positioned_layer_style(
        \stdClass $record,
        \stored_file $file,
        string $fitmode
    ): string {
        $box = self::get_native_overlay_box_percentages($record, $file, $fitmode);
        $imageinfo = $file->get_imageinfo();
        $layerwidth = (int)($imageinfo['width'] ?? 0);
        $layerheight = (int)($imageinfo['height'] ?? 0);
        if ($layerwidth <= 0 || $layerheight <= 0) {
            return '';
        }

        if (!$box) {
            return self::build_html_positioned_layer_style($record, $file, $fitmode);
        }

        $anchor = self::normalise_position_anchor((string)($record->positionanchor ?? self::POSITION_CENTER));
        $positionstyles = self::get_native_html_overlay_position_styles($record, $anchor, $box['width'], $box['height']);
        $styles = [
            'aspect-ratio: ' . $layerwidth . ' / ' . $layerheight . ';',
        ];

        if ($fitmode === self::FIT_MODE_BANNER) {
            $styles[] = 'width: 100%;';
            $styles[] = 'height: 100%;';
            $styles[] = 'object-fit: cover;';
            $styles[] = 'left: 0;';
            $styles[] = 'top: 0;';
            return implode(' ', $styles);
        }

        if ($fitmode === self::FIT_MODE_COVER) {
            $styles[] = 'width: ' . self::format_css_percentage($box['width']) . ';';
            $styles[] = 'height: ' . self::format_css_percentage($box['height']) . ';';
            $styles[] = 'object-fit: fill;';
        } else if ($fitmode === self::FIT_MODE_CUSTOM) {
            $styles[] = 'width: ' . self::format_css_percentage($box['width']) . ';';
            $styles[] = 'height: ' . self::format_css_percentage($box['height']) . ';';
            $styles[] = 'object-fit: contain;';
        } else {
            $styles[] = 'width: ' . self::format_css_percentage($box['width']) . ';';
            $styles[] = 'height: auto;';
            $styles[] = 'object-fit: none;';
        }

        if (!empty($positionstyles)) {
            $styles = array_merge($styles, $positionstyles);
        }

        return implode(' ', $styles);
    }

    /**
     * Build native course-header overlay styles aligned with the admin preview.
     *
     * @param \stdClass $record
     * @param \stored_file $file
     * @param string $fitmode
     * @return array{wrapperstyle:string,imagestyle:string}
     */
    protected static function build_native_course_header_overlay_styles(
        \stdClass $record,
        \stored_file $file,
        string $fitmode
    ): array {
        if ($fitmode !== self::FIT_MODE_CUSTOM) {
            return [
                'wrapperstyle' => 'position: absolute; inset: 0; overflow: hidden;',
                'imagestyle' => 'position: absolute; display: block; max-width: none; pointer-events: none; ' .
                    self::build_native_html_positioned_layer_style($record, $file, $fitmode),
            ];
        }

        $styles = self::build_modal_preview_image_layer_styles($record, $fitmode, $file);
        $wrapperstyle = trim((string)($styles['wrapperstyle'] ?? ''));
        $imagestyle = trim((string)($styles['imagestyle'] ?? ''));
        if ($wrapperstyle === '' || $imagestyle === '') {
            return ['wrapperstyle' => '', 'imagestyle' => ''];
        }

        return [
            'wrapperstyle' => $wrapperstyle,
            'imagestyle' => $imagestyle,
        ];
    }

    /**
     * Build CSS position rules for one responsive HTML overlay.
     *
     * @param \stdClass $record
     * @param string $anchor
     * @return array
     */
    protected static function get_html_overlay_position_styles(
        \stdClass $record,
        string $anchor
    ): array {
        $offsettop = self::format_css_percentage((float)($record->offsettoppercent ?? 0));
        $offsetright = self::format_css_percentage((float)($record->offsetrightpercent ?? 0));
        $offsetbottom = self::format_css_percentage((float)($record->offsetbottompercent ?? 0));
        $offsetleft = self::format_css_percentage((float)($record->offsetleftpercent ?? 0));

        return match ($anchor) {
            self::POSITION_TOP => [
                'left: 50%;',
                'top: ' . $offsettop . ';',
                'transform: translateX(-50%);',
            ],
            self::POSITION_BOTTOM => [
                'left: 50%;',
                'bottom: ' . $offsetbottom . ';',
                'transform: translateX(-50%);',
            ],
            self::POSITION_LEFT => [
                'left: ' . $offsetleft . ';',
                'top: 50%;',
                'transform: translateY(-50%);',
            ],
            self::POSITION_RIGHT => [
                'right: ' . $offsetright . ';',
                'top: 50%;',
                'transform: translateY(-50%);',
            ],
            self::POSITION_TOP_LEFT => [
                'left: ' . $offsetleft . ';',
                'top: ' . $offsettop . ';',
            ],
            self::POSITION_TOP_RIGHT => [
                'right: ' . $offsetright . ';',
                'top: ' . $offsettop . ';',
            ],
            self::POSITION_BOTTOM_LEFT => [
                'left: ' . $offsetleft . ';',
                'bottom: ' . $offsetbottom . ';',
            ],
            self::POSITION_BOTTOM_RIGHT => [
                'right: ' . $offsetright . ';',
                'bottom: ' . $offsetbottom . ';',
            ],
            default => [
                'left: 50%;',
                'top: 50%;',
                'transform: translate(-50%, -50%);',
            ],
        };
    }

    /**
     * Estimate the visible box size of one native HTML overlay as banner percentages.
     *
     * @param \stdClass $record
     * @param \stored_file $file
     * @param string $fitmode
     * @return array{width:float,height:float}|null
     */
    protected static function get_native_overlay_box_percentages(
        \stdClass $record,
        \stored_file $file,
        string $fitmode
    ): ?array {
        $imageinfo = $file->get_imageinfo();
        $layerwidth = (int)($imageinfo['width'] ?? 0);
        $layerheight = (int)($imageinfo['height'] ?? 0);
        if ($layerwidth <= 0 || $layerheight <= 0) {
            return null;
        }

        if ($fitmode === self::FIT_MODE_BANNER) {
            return ['width' => 100.0, 'height' => 100.0];
        }

        if ($fitmode === self::FIT_MODE_COVER) {
            $imageaspect = $layerwidth / $layerheight;
            $banneraspect = self::DEFAULT_CANVAS_WIDTH / self::DEFAULT_CANVAS_HEIGHT;
            if ($imageaspect >= $banneraspect) {
                return [
                    'width' => 100.0,
                    'height' => 100.0 * ($banneraspect / $imageaspect),
                ];
            }
            return [
                'width' => 100.0 * ($imageaspect / $banneraspect),
                'height' => 100.0,
            ];
        }

        if ($fitmode !== self::FIT_MODE_CUSTOM) {
            return [
                'width' => ($layerwidth / self::DEFAULT_CANVAS_WIDTH) * 100,
                'height' => ($layerheight / self::DEFAULT_CANVAS_HEIGHT) * 100,
            ];
        }

        $widthlimit = self::normalise_percentage((float)($record->customwidthpercent ?? 100));
        $heightlimit = self::normalise_percentage((float)($record->customheightpercent ?? 100));
        if (empty($record->customsizekeepaspect)) {
            return ['width' => $widthlimit, 'height' => $heightlimit];
        }

        $imageaspect = $layerwidth / $layerheight;
        $banneraspect = self::DEFAULT_CANVAS_WIDTH / self::DEFAULT_CANVAS_HEIGHT;
        $heightfromwidth = $widthlimit * ($banneraspect / $imageaspect);

        return ['width' => $widthlimit, 'height' => $heightfromwidth];
    }

    /**
     * Build native overlay positioning rules clamped inside the visible frame.
     *
     * @param \stdClass $record
     * @param string $anchor
     * @param float $widthpercent
     * @param float $heightpercent
     * @return array
     */
    protected static function get_native_html_overlay_position_styles(
        \stdClass $record,
        string $anchor,
        float $widthpercent,
        float $heightpercent
    ): array {
        $top = self::normalise_percentage((float)($record->offsettoppercent ?? 0), -300.0, 300.0);
        $right = self::normalise_percentage((float)($record->offsetrightpercent ?? 0), -300.0, 300.0);
        $bottom = self::normalise_percentage((float)($record->offsetbottompercent ?? 0), -300.0, 300.0);
        $left = self::normalise_percentage((float)($record->offsetleftpercent ?? 0), -300.0, 300.0);

        return match ($anchor) {
            self::POSITION_TOP_LEFT => [
                'left: ' . self::format_css_percentage($left) . ';',
                'top: ' . self::format_css_percentage($top) . ';',
            ],
            self::POSITION_TOP_RIGHT => [
                'right: ' . self::format_css_percentage($right) . ';',
                'top: ' . self::format_css_percentage($top) . ';',
            ],
            self::POSITION_BOTTOM_LEFT => [
                'left: ' . self::format_css_percentage($left) . ';',
                'bottom: ' . self::format_css_percentage($bottom) . ';',
            ],
            self::POSITION_BOTTOM_RIGHT => [
                'right: ' . self::format_css_percentage($right) . ';',
                'bottom: ' . self::format_css_percentage($bottom) . ';',
            ],
            self::POSITION_LEFT => [
                'left: ' . self::format_css_percentage($left) . ';',
                'top: 50%;',
                'transform: translateY(-50%);',
            ],
            self::POSITION_RIGHT => [
                'right: ' . self::format_css_percentage($right) . ';',
                'top: 50%;',
                'transform: translateY(-50%);',
            ],
            self::POSITION_TOP => [
                'left: 50%;',
                'top: ' . self::format_css_percentage($top) . ';',
                'transform: translateX(-50%);',
            ],
            self::POSITION_BOTTOM => [
                'left: 50%;',
                'bottom: ' . self::format_css_percentage($bottom) . ';',
                'transform: translateX(-50%);',
            ],
            default => [
                'left: 50%;',
                'top: 50%;',
                'transform: translate(-50%, -50%);',
            ],
        };
    }

    /**
     * Format a numeric percentage for CSS output.
     *
     * @param float $value
     * @return string
     */
    protected static function format_css_percentage(float $value): string {
        return rtrim(rtrim(sprintf('%.6F', self::normalise_percentage($value, -300.0, 300.0)), '0'), '.') . '%';
    }

    /**
     * Return a CSS object-position string from one anchor.
     *
     * @param string $anchor
     * @return string
     */
    protected static function get_css_object_position_for_anchor(string $anchor): string {
        return match ($anchor) {
            self::POSITION_TOP => 'center top',
            self::POSITION_BOTTOM => 'center bottom',
            self::POSITION_LEFT => 'left center',
            self::POSITION_RIGHT => 'right center',
            self::POSITION_TOP_LEFT => 'left top',
            self::POSITION_TOP_RIGHT => 'right top',
            self::POSITION_BOTTOM_LEFT => 'left bottom',
            self::POSITION_BOTTOM_RIGHT => 'right bottom',
            default => 'center center',
        };
    }

    /**
     * Format a pixel value as a percentage of the generated banner canvas.
     *
     * @param int $value
     * @param int $base
     * @return string
     */
    protected static function format_css_percent(int $value, int $base): string {
        if ($base <= 0) {
            return '0%';
        }

        return rtrim(rtrim(sprintf('%.6F', ($value / $base) * 100), '0'), '.') . '%';
    }

    /**
     * Render one layer to the target canvas and return its visible bounds.
     *
     * @param resource|\GdImage $canvas
     * @param resource|\GdImage $layer
     * @param \stdClass $record
     * @param string $fitmode
     * @param int $width
     * @param int $height
     * @param int $layerwidth
     * @param int $layerheight
     * @param bool $cardmode
     * @return array{x:int,y:int,width:int,height:int}
     */
    protected static function render_layer_on_canvas(
        $canvas,
        $layer,
        \stdClass $record,
        string $fitmode,
        int $width,
        int $height,
        int $layerwidth,
        int $layerheight,
        bool $cardmode
    ): array {
        $anchor = self::normalise_position_anchor((string)($record->positionanchor ?? self::POSITION_CENTER));
        if ($fitmode === self::FIT_MODE_BANNER) {
            imagecopyresampled($canvas, $layer, 0, 0, 0, 0, $width, $height, $layerwidth, $layerheight);
            return ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height];
        }

        if ($fitmode === self::FIT_MODE_COVER) {
            return self::copy_layer_contain(
                $canvas,
                $layer,
                $width,
                $height,
                $layerwidth,
                $layerheight,
                $anchor,
                $record
            );
        }

        if ($fitmode === self::FIT_MODE_CUSTOM) {
            return self::copy_layer_custom($canvas, $layer, $record, $width, $height, $layerwidth, $layerheight, $anchor);
        }

        [$destinationx, $destinationy] = self::calculate_anchor_position(
            $width,
            $height,
            $layerwidth,
            $layerheight,
            $anchor,
            $record
        );
        imagecopy($canvas, $layer, $destinationx, $destinationy, 0, 0, $layerwidth, $layerheight);

        return [
            'x' => $destinationx,
            'y' => $destinationy,
            'width' => $layerwidth,
            'height' => $layerheight,
        ];
    }

    /**
     * Copy a layer at a custom percentage size.
     *
     * @param resource|\GdImage $canvas
     * @param resource|\GdImage $layer
     * @param \stdClass $record
     * @param int $canvaswidth
     * @param int $canvasheight
     * @param int $layerwidth
     * @param int $layerheight
     * @param string $anchor
     * @return array{x:int,y:int,width:int,height:int}
     */
    protected static function copy_layer_custom(
        $canvas,
        $layer,
        \stdClass $record,
        int $canvaswidth,
        int $canvasheight,
        int $layerwidth,
        int $layerheight,
        string $anchor
    ): array {
        if ($layerwidth <= 0 || $layerheight <= 0) {
            return ['x' => 0, 'y' => 0, 'width' => 0, 'height' => 0];
        }

        $targetwidth = max(1, (int)round($canvaswidth * self::normalise_percentage((float)($record->customwidthpercent ?? 100)) / 100));
        $targetheight = max(1, (int)round($canvasheight * self::normalise_percentage((float)($record->customheightpercent ?? 100)) / 100));
        $keepaspect = !empty($record->customsizekeepaspect);

        if ($keepaspect) {
            $scalex = $targetwidth / $layerwidth;
            $scale = $scalex;
            $targetwidth = max(1, (int)round($layerwidth * $scale));
            $targetheight = max(1, (int)round($layerheight * $scale));
        }

        [$destinationx, $destinationy] = self::calculate_anchor_position(
            $canvaswidth,
            $canvasheight,
            $targetwidth,
            $targetheight,
            $anchor,
            $record
        );

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

        return [
            'x' => $destinationx,
            'y' => $destinationy,
            'width' => $targetwidth,
            'height' => $targetheight,
        ];
    }

    /**
     * Copy a layer fully inside the target canvas without distorting its ratio.
     *
     * @param resource|\GdImage $canvas
     * @param resource|\GdImage $layer
     * @param int $canvaswidth
     * @param int $canvasheight
     * @param int $layerwidth
     * @param int $layerheight
     * @param string $anchor
     * @param \stdClass|null $record
     * @return array{x:int,y:int,width:int,height:int}
     */
    protected static function copy_layer_contain(
        $canvas,
        $layer,
        int $canvaswidth,
        int $canvasheight,
        int $layerwidth,
        int $layerheight,
        string $anchor,
        ?\stdClass $record = null
    ): array {
        if ($layerwidth <= 0 || $layerheight <= 0) {
            return ['x' => 0, 'y' => 0, 'width' => 0, 'height' => 0];
        }

        $scale = min($canvaswidth / $layerwidth, $canvasheight / $layerheight);
        $targetwidth = max(1, (int)round($layerwidth * $scale));
        $targetheight = max(1, (int)round($layerheight * $scale));
        [$destinationx, $destinationy] = self::calculate_anchor_position(
            $canvaswidth,
            $canvasheight,
            $targetwidth,
            $targetheight,
            $anchor,
            $record
        );

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

        return [
            'x' => $destinationx,
            'y' => $destinationy,
            'width' => $targetwidth,
            'height' => $targetheight,
        ];
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
    protected static function copy_layer_cover(
        $canvas,
        $layer,
        int $width,
        int $height,
        int $layerwidth,
        int $layerheight,
        string $anchor = self::POSITION_CENTER,
        ?\stdClass $record = null
    ): array {
        if ($layerwidth <= 0 || $layerheight <= 0) {
            return ['x' => 0, 'y' => 0, 'width' => 0, 'height' => 0];
        }

        $scale = max($width / $layerwidth, $height / $layerheight);
        $targetwidth = (int)ceil($layerwidth * $scale);
        $targetheight = (int)ceil($layerheight * $scale);
        [$destinationx, $destinationy] = self::calculate_anchor_position(
            $width,
            $height,
            $targetwidth,
            $targetheight,
            $anchor,
            $record
        );

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

        return ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height];
    }

    /**
     * Compute one anchored placement inside the banner canvas.
     *
     * @param int $canvaswidth
     * @param int $canvasheight
     * @param int $contentwidth
     * @param int $contentheight
     * @param string $anchor
     * @param \stdClass|null $record
     * @return array{0:int,1:int}
     */
    protected static function calculate_anchor_position(
        int $canvaswidth,
        int $canvasheight,
        int $contentwidth,
        int $contentheight,
        string $anchor,
        ?\stdClass $record = null
    ): array {
        $anchor = self::normalise_position_anchor($anchor);
        $offsettop = (int)round($canvasheight * self::normalise_percentage((float)($record->offsettoppercent ?? 0), -300.0, 300.0) / 100);
        $offsetright = (int)round($canvaswidth * self::normalise_percentage((float)($record->offsetrightpercent ?? 0), -300.0, 300.0) / 100);
        $offsetbottom = (int)round($canvasheight * self::normalise_percentage((float)($record->offsetbottompercent ?? 0), -300.0, 300.0) / 100);
        $offsetleft = (int)round($canvaswidth * self::normalise_percentage((float)($record->offsetleftpercent ?? 0), -300.0, 300.0) / 100);

        $centerx = (int)floor(($canvaswidth - $contentwidth) / 2);
        $centery = (int)floor(($canvasheight - $contentheight) / 2);
        $leftx = $offsetleft;
        $rightx = $canvaswidth - $contentwidth - $offsetright;
        $topy = $offsettop;
        $bottomy = $canvasheight - $contentheight - $offsetbottom;

        return match ($anchor) {
            self::POSITION_TOP => [$centerx, $topy],
            self::POSITION_BOTTOM => [$centerx, $bottomy],
            self::POSITION_LEFT => [$leftx, $centery],
            self::POSITION_RIGHT => [$rightx, $centery],
            self::POSITION_TOP_LEFT => [$leftx, $topy],
            self::POSITION_TOP_RIGHT => [$rightx, $topy],
            self::POSITION_BOTTOM_LEFT => [$leftx, $bottomy],
            self::POSITION_BOTTOM_RIGHT => [$rightx, $bottomy],
            default => [$centerx, $centery],
        };
    }

    /**
     * Draw a configurable border around the visible bounds of one rendered layer.
     *
     * @param resource|\GdImage $canvas
     * @param \stdClass $record
     * @param array $bounds
     * @param int $canvaswidth
     * @param int $canvasheight
     * @return void
     */
    protected static function draw_layer_border($canvas, \stdClass $record, array $bounds, int $canvaswidth, int $canvasheight): void {
        if (empty($record->borderenabled)) {
            return;
        }

        $sides = self::normalise_border_sides(explode(',', (string)($record->bordersides ?? 'top,right,bottom,left')));
        $borderwidth = self::get_border_width_pixels(
            (float)($record->borderwidth ?? 0),
            (int)($bounds['width'] ?? 0),
            (int)($bounds['height'] ?? 0)
        );
        if ($borderwidth <= 0 || empty($sides) || empty($bounds['width']) || empty($bounds['height'])) {
            return;
        }

        $color = self::allocate_color_with_opacity(
            $canvas,
            (string)($record->bordercolor ?? '#FFFFFF'),
            self::get_effective_border_opacity((float)($record->borderopacity ?? 0))
        );
        if ($color === null) {
            return;
        }

        $style = self::normalise_border_style((string)($record->borderstyle ?? self::BORDER_STYLE_SOLID));
        $fade = self::normalise_unit_float((float)($record->borderfade ?? 0), 0);
        $dashspec = null;
        if ($style === self::BORDER_STYLE_DASHED) {
            $dashlength = max(1, min(80, (int)round((float)($record->borderdashlength ?? 24))));
            $dashgap = max(1, (int)round($dashlength * 0.7));
            $dashspec = [$dashlength, $dashgap];
        }

        $left = max(0, (int)$bounds['x']);
        $top = max(0, (int)$bounds['y']);
        $right = min($canvaswidth - 1, $left + max(0, (int)$bounds['width']) - 1);
        $bottom = min($canvasheight - 1, $top + max(0, (int)$bounds['height']) - 1);
        $insetstart = (int)floor($borderwidth / 2);
        $insetend = (int)ceil($borderwidth / 2) - 1;
        $left = min($right, $left + $insetstart);
        $top = min($bottom, $top + $insetstart);
        $right = max($left, $right - $insetend);
        $bottom = max($top, $bottom - $insetend);

        $rounded = !empty($record->borderinnerrounded);
        $cutoutsize = $rounded ? ($borderwidth * 2) : $borderwidth;
        $hastopleftcorner = $rounded && in_array('top', $sides, true) && in_array('left', $sides, true);
        $hastoprightcorner = $rounded && in_array('top', $sides, true) && in_array('right', $sides, true);
        $hasbottomrightcorner = $rounded && in_array('bottom', $sides, true) && in_array('right', $sides, true);
        $hasbottomleftcorner = $rounded && in_array('bottom', $sides, true) && in_array('left', $sides, true);

        if (in_array('top', $sides, true)) {
            self::draw_border_line(
                $canvas,
                $left + ($hastopleftcorner ? $cutoutsize : 0),
                $top,
                $right - ($hastoprightcorner ? $cutoutsize : 0),
                $top,
                'top',
                $style,
                $color,
                $fade,
                $borderwidth,
                $dashspec
            );
        }
        if (in_array('right', $sides, true)) {
            self::draw_border_line(
                $canvas,
                $right,
                $top + ($hastoprightcorner ? $cutoutsize : 0),
                $right,
                $bottom - ($hasbottomrightcorner ? $cutoutsize : 0),
                'right',
                $style,
                $color,
                $fade,
                $borderwidth,
                $dashspec
            );
        }
        if (in_array('bottom', $sides, true)) {
            self::draw_border_line(
                $canvas,
                $left + ($hasbottomleftcorner ? $cutoutsize : 0),
                $bottom,
                $right - ($hasbottomrightcorner ? $cutoutsize : 0),
                $bottom,
                'bottom',
                $style,
                $color,
                $fade,
                $borderwidth,
                $dashspec
            );
        }
        if (in_array('left', $sides, true)) {
            self::draw_border_line(
                $canvas,
                $left,
                $top + ($hastopleftcorner ? $cutoutsize : 0),
                $left,
                $bottom - ($hasbottomleftcorner ? $cutoutsize : 0),
                'left',
                $style,
                $color,
                $fade,
                $borderwidth,
                $dashspec
            );
        }

        if ($hastopleftcorner) {
            self::draw_inner_rounded_border_corner($canvas, $left, $top, $cutoutsize, $borderwidth, $color, 'top-left', $fade);
        }
        if ($hastoprightcorner) {
            self::draw_inner_rounded_border_corner($canvas, $right - $cutoutsize + 1, $top, $cutoutsize, $borderwidth, $color, 'top-right', $fade);
        }
        if ($hasbottomrightcorner) {
            self::draw_inner_rounded_border_corner($canvas, $right - $cutoutsize + 1, $bottom - $cutoutsize + 1, $cutoutsize, $borderwidth, $color, 'bottom-right', $fade);
        }
        if ($hasbottomleftcorner) {
            self::draw_inner_rounded_border_corner($canvas, $left, $bottom - $cutoutsize + 1, $cutoutsize, $borderwidth, $color, 'bottom-left', $fade);
        }
    }

    /**
     * Draw one square border corner with a rounded inner cut-out.
     *
     * Outer banner corners stay square; only the inner hole is rounded.
     *
     * @param resource|\GdImage $canvas
     * @param int $x
     * @param int $y
     * @param int $size
     * @param int $color
     * @param string $corner
     * @param float $fade
     * @return void
     */
    protected static function draw_inner_rounded_border_corner(
        $canvas,
        int $x,
        int $y,
        int $size,
        int $innerradius,
        int $color,
        string $corner,
        float $fade
    ): void {
        if ($size <= 0 || $innerradius <= 0) {
            return;
        }

        $cornercanvas = imagecreatetruecolor($size, $size);
        if (!$cornercanvas) {
            return;
        }

        imagealphablending($cornercanvas, false);
        imagesavealpha($cornercanvas, true);
        $transparent = imagecolorallocatealpha($cornercanvas, 0, 0, 0, 127);
        imagefilledrectangle($cornercanvas, 0, 0, $size, $size, $transparent);
        $centerx = $size;
        $centery = $size;
        if ($corner === 'top-right' || $corner === 'bottom-right') {
            $centerx = 0;
        }
        if ($corner === 'bottom-left' || $corner === 'bottom-right') {
            $centery = 0;
        }

        [$red, $green, $blue, $basealpha] = self::extract_gd_color_channels($color);
        $colorcache = [];
        $fadewidth = max(1, $size - $innerradius);

        for ($pixelx = 0; $pixelx < $size; $pixelx++) {
            for ($pixely = 0; $pixely < $size; $pixely++) {
                $dx = ($pixelx + 0.5) - $centerx;
                $dy = ($pixely + 0.5) - $centery;
                $distance = sqrt(($dx * $dx) + ($dy * $dy));
                if ($distance <= $innerradius) {
                    continue;
                }

                $progress = min(1, max(0, ($distance - $innerradius) / $fadewidth));
                $alpha = (int)round($basealpha + ((127 - $basealpha) * (1 - $progress) * $fade));
                $pixelcolor = self::allocate_cached_gd_alpha_color($cornercanvas, $red, $green, $blue, $alpha, $colorcache);
                imagesetpixel($cornercanvas, $pixelx, $pixely, $pixelcolor);
            }
        }

        imagecopy($canvas, $cornercanvas, $x, $y, 0, 0, $size, $size);
        imagedestroy($cornercanvas);
    }

    /**
     * Draw one border side, optionally fading it from start to end.
     *
     * @param resource|\GdImage $canvas
     * @return void
     */
    protected static function draw_border_line(
        $canvas,
        int $x1,
        int $y1,
        int $x2,
        int $y2,
        string $side,
        string $style,
        int $basecolor,
        float $fade,
        int $borderwidth,
        ?array $dashspec = null
    ): void {
        if ($borderwidth <= 0) {
            return;
        }

        [$red, $green, $blue, $basealpha] = self::extract_gd_color_channels($basecolor);
        $colorcache = [];
        $isvertical = in_array($side, ['left', 'right'], true);
        $outerstart = match ($side) {
            'top' => $y1 - (int)floor($borderwidth / 2),
            'bottom' => $y1 + (int)ceil($borderwidth / 2) - 1,
            'left' => $x1 - (int)floor($borderwidth / 2),
            'right' => $x1 + (int)ceil($borderwidth / 2) - 1,
            default => 0,
        };
        $thicknesssteps = max(1, $borderwidth - 1);
        [$dashlength, $dashgap] = $dashspec ?? [0, 0];
        $isdashed = ($style === self::BORDER_STYLE_DASHED && $dashlength > 0);

        for ($offset = 0; $offset < $borderwidth; $offset++) {
            $progress = $offset / $thicknesssteps;
            $alpha = (int)round($basealpha + ((127 - $basealpha) * $fade * $progress));
            $linecolor = self::allocate_cached_gd_alpha_color($canvas, $red, $green, $blue, $alpha, $colorcache);

            if ($isvertical) {
                $linex = ($side === 'left') ? ($outerstart + $offset) : ($outerstart - $offset);
                if ($isdashed) {
                    $cursor = min($y1, $y2);
                    $max = max($y1, $y2);
                    while ($cursor <= $max) {
                        $segmentend = min($max, $cursor + $dashlength - 1);
                        imageline($canvas, $linex, $cursor, $linex, $segmentend, $linecolor);
                        $cursor += $dashlength + $dashgap;
                    }
                } else {
                    imageline($canvas, $linex, $y1, $linex, $y2, $linecolor);
                }
            } else {
                $liney = ($side === 'top') ? ($outerstart + $offset) : ($outerstart - $offset);
                if ($isdashed) {
                    $cursor = min($x1, $x2);
                    $max = max($x1, $x2);
                    while ($cursor <= $max) {
                        $segmentend = min($max, $cursor + $dashlength - 1);
                        imageline($canvas, $cursor, $liney, $segmentend, $liney, $linecolor);
                        $cursor += $dashlength + $dashgap;
                    }
                } else {
                    imageline($canvas, $x1, $liney, $x2, $liney, $linecolor);
                }
            }
        }
    }

    /**
     * Extract RGBA channels from a GD truecolor value.
     *
     * @param int $gdcolor
     * @return array{0:int,1:int,2:int,3:int}
     */
    protected static function extract_gd_color_channels(int $gdcolor): array {
        return [
            ($gdcolor >> 16) & 0xFF,
            ($gdcolor >> 8) & 0xFF,
            $gdcolor & 0xFF,
            ($gdcolor >> 24) & 0x7F,
        ];
    }

    /**
     * Allocate one alpha variant once and reuse it while drawing.
     *
     * @param resource|\GdImage $canvas
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $alpha
     * @param array $cache
     * @return int
     */
    protected static function allocate_cached_gd_alpha_color($canvas, int $red, int $green, int $blue, int $alpha, array &$cache): int {
        $alpha = max(0, min(127, $alpha));
        if (!array_key_exists($alpha, $cache)) {
            $cache[$alpha] = imagecolorallocatealpha($canvas, $red, $green, $blue, $alpha);
        }

        return $cache[$alpha];
    }

    /**
     * Parse a CSS-like color string and allocate it for GD.
     *
     * @param resource|\GdImage $canvas
     * @param string $color
     * @param float $opacity
     * @return int|null
     */
    protected static function allocate_color_with_opacity($canvas, string $color, float $opacity): ?int {
        $rgb = self::parse_color_to_rgba($color);
        if ($rgb === null) {
            return null;
        }
        [$red, $green, $blue, $alpha] = $rgb;
        $opacity = self::normalise_unit_float($opacity, 1);
        $gdalpha = (int)round($alpha + ((127 - $alpha) * (1 - $opacity)));

        return imagecolorallocatealpha($canvas, $red, $green, $blue, max(0, min(127, $gdalpha)));
    }

    /**
     * Convert the stored border transparency value into an effective opacity.
     *
     * The admin UI exposes this setting as a transparency percentage:
     * 0 means fully visible, 1 means fully transparent.
     *
     * @param float $transparency
     * @return float
     */
    protected static function get_effective_border_opacity(float $transparency): float {
        $transparency = self::normalise_unit_float($transparency, 0);
        return max(0.0, min(1.0, 1.0 - $transparency));
    }

    /**
     * Parse a supported HEX or RGB/RGBA color string.
     *
     * @param string $color
     * @return array<int,int>|null
     */
    protected static function parse_color_to_rgba(string $color): ?array {
        $color = trim($color);
        if (preg_match('/^#([0-9a-fA-F]{3})$/', $color, $matches)) {
            return [
                hexdec(str_repeat($matches[1][0], 2)),
                hexdec(str_repeat($matches[1][1], 2)),
                hexdec(str_repeat($matches[1][2], 2)),
                0,
            ];
        }
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $color, $matches)) {
            return [
                hexdec(substr($matches[1], 0, 2)),
                hexdec(substr($matches[1], 2, 2)),
                hexdec(substr($matches[1], 4, 2)),
                0,
            ];
        }
        if (preg_match('/^#([0-9a-fA-F]{8})$/', $color, $matches)) {
            return [
                hexdec(substr($matches[1], 0, 2)),
                hexdec(substr($matches[1], 2, 2)),
                hexdec(substr($matches[1], 4, 2)),
                (int)round(127 * (1 - (hexdec(substr($matches[1], 6, 2)) / 255))),
            ];
        }
        if (preg_match('/^rgba?\((.+)\)$/i', $color, $matches)) {
            $parts = array_map('trim', explode(',', $matches[1]));
            if (count($parts) < 3) {
                return null;
            }
            $red = max(0, min(255, (int)$parts[0]));
            $green = max(0, min(255, (int)$parts[1]));
            $blue = max(0, min(255, (int)$parts[2]));
            $opacity = isset($parts[3]) ? self::normalise_unit_float((float)$parts[3], 1) : 1.0;
            $alpha = (int)round(127 * (1 - $opacity));
            return [$red, $green, $blue, $alpha];
        }
        return null;
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
