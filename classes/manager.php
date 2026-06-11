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

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalNotNeeded
defined('MOODLE_INTERNAL') || die();
// phpcs:enable moodle.Files.MoodleInternal.MoodleInternalNotNeeded
// phpcs:disable moodle.Files.LineLength.TooLong -- Rendering and export helpers use long Moodle identifiers and CSS variables.

/**
 * Banner manager helpers.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
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
    public const MANAGED_CARD_SQUARE_PREFIX = 'course_banner_builder_squarecard';
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
    /** @var float Maximum custom layer size percentage accepted by editors and renderers. */
    public const CUSTOM_SIZE_PERCENT_MAX = 1000.0;
    /** @var string */
    public const BANNER_FORMAT_STANDARD = 'standard';
    /** @var string */
    public const BANNER_FORMAT_CONTENT_WIDE = 'contentwide';
    /** @var string */
    public const BANNER_FORMAT_FULLWIDTH_TOP = 'fullwidthtop';
    /** @var string */
    public const BANNER_FORMAT_FULLWIDTH_TOP_COMPACT = 'fullwidthtopcompact';
    /** @var string */
    public const BANNER_FORMAT_FULLWIDTH_TOP_INSET = 'fullwidthtopinset';
    /** @var string */
    public const FIT_SCOPE_SELF = 'self';
    /** @var string */
    public const FIT_SCOPE_DESCENDANTS = 'descendants';
    /** @var string */
    public const SOURCE_TYPE_CATEGORY = 'category';
    /** @var string */
    public const SOURCE_TYPE_CUSTOMFIELD = 'customfield';
    /** @var string */
    public const SOURCE_TYPE_SITE = 'site';
    /** @var string */
    public const SITE_SOURCE_KEY = 'site:main';
    /** @var string */
    public const OVERLAY_TARGET_BANNER = 'banner';
    /** @var string */
    public const OVERLAY_TARGET_SLIDESHOW = 'slideshow';
    /** @var string */
    public const OVERLAY_TARGET_BOTH = 'both';
    /** @var string */
    public const SLIDESHOW_CONTEXT_COURSE = 'course';
    /** @var string */
    public const SLIDESHOW_CONTEXT_SITE = 'site';
    /** @var string */
    public const SLIDESHOW_TYPE_FORUMS = 'forums';
    /** @var string */
    public const SLIDESHOW_TYPE_SITEANNOUNCEMENTS = 'siteannouncements';
    /** @var string */
    public const SLIDESHOW_TYPE_ASSIGNMENTS = 'assignments';
    /** @var string */
    public const SLIDESHOW_TYPE_QUIZZES = 'quizzes';
    /** @var int */
    public const SLIDESHOW_DEFAULT_DELAY = 7000;
    /** @var int */
    public const SLIDESHOW_DEFAULT_MAX_SLIDES = 15;
    /** @var int */
    public const SLIDESHOW_MAX_SLIDES = 15;
    /** @var int */
    public const SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS = 60;
    /** @var string */
    public const SLIDESHOW_DEFAULT_OVERLAY_COLOR = '#000000';
    /** @var float */
    public const SLIDESHOW_DEFAULT_OVERLAY_OPACITY = 0.38;
    /** @var int */
    public const SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT = 58;
    /** @var int */
    public const SLIDESHOW_DEFAULT_BODY_FONT_PERCENT = 64;
    /** @var int */
    public const SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT = 66;
    /** @var int */
    public const SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT = 39;
    /** @var int */
    public const SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT = 93;
    /** @var int */
    public const SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT = 82;
    /** @var string */
    public const SLIDESHOW_CORNER_ROUNDED = 'rounded';
    /** @var string */
    public const SLIDESHOW_CORNER_SQUARE = 'square';
    /** @var string */
    public const SLIDESHOW_DEFAULT_LABEL_CORNERS = self::SLIDESHOW_CORNER_ROUNDED;
    /** @var string */
    public const SLIDESHOW_DEFAULT_ACTION_CORNERS = self::SLIDESHOW_CORNER_ROUNDED;
    /** @var string */
    public const SLIDESHOW_LABEL_ORIENTATION_ROW = 'row';
    /** @var string */
    public const SLIDESHOW_LABEL_ORIENTATION_COLUMN = 'column';
    /** @var string */
    public const SLIDESHOW_DEFAULT_LABEL_ORIENTATION = self::SLIDESHOW_LABEL_ORIENTATION_ROW;
    /** @var string */
    public const SLIDESHOW_ALIGN_LEFT = 'left';
    /** @var string */
    public const SLIDESHOW_ALIGN_CENTER = 'center';
    /** @var string */
    public const SLIDESHOW_ALIGN_RIGHT = 'right';
    /** @var string */
    public const SLIDESHOW_DEFAULT_TITLE_ALIGN = self::SLIDESHOW_ALIGN_CENTER;
    /** @var string */
    public const SLIDESHOW_DEFAULT_BODY_ALIGN = self::SLIDESHOW_ALIGN_CENTER;
    /** @var string */
    public const SLIDESHOW_DEFAULT_LABEL_ALIGN = self::SLIDESHOW_ALIGN_CENTER;
    /** @var string */
    public const SLIDESHOW_DEFAULT_TITLE_COLOR = '#FFFFFF';
    /** @var string */
    public const SLIDESHOW_DEFAULT_BODY_COLOR = '#FFFFFF';
    /** @var string */
    public const SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY = '';
    /** @var string */
    public const SLIDESHOW_DEFAULT_BODY_FONT_FAMILY = '';
    /** @var bool */
    public const SLIDESHOW_DEFAULT_TITLE_BOLD = true;
    /** @var bool */
    public const SLIDESHOW_DEFAULT_TITLE_ITALIC = false;
    /** @var bool */
    public const SLIDESHOW_DEFAULT_TITLE_UNDERLINE = false;
    /** @var bool */
    public const SLIDESHOW_DEFAULT_TITLE_STRIKE = false;
    /** @var bool */
    public const SLIDESHOW_DEFAULT_BODY_BOLD = false;
    /** @var bool */
    public const SLIDESHOW_DEFAULT_BODY_ITALIC = false;
    /** @var bool */
    public const SLIDESHOW_DEFAULT_BODY_UNDERLINE = false;
    /** @var bool */
    public const SLIDESHOW_DEFAULT_BODY_STRIKE = false;
    /** @var float */
    public const SLIDESHOW_DEFAULT_TITLE_X = 50.0;
    /** @var float */
    public const SLIDESHOW_DEFAULT_TITLE_Y = 24.0;
    /** @var float */
    public const SLIDESHOW_DEFAULT_BODY_X = 50.0;
    /** @var float */
    public const SLIDESHOW_DEFAULT_BODY_Y = 54.0;
    /** @var float */
    public const SLIDESHOW_DEFAULT_ACTION_X = 50.0;
    /** @var float */
    public const SLIDESHOW_DEFAULT_ACTION_Y = 84.0;
    /** @var float */
    public const SLIDESHOW_DEFAULT_LABEL_X = 14.0;
    /** @var float */
    public const SLIDESHOW_DEFAULT_LABEL_Y = 10.0;
    /** @var array */
    protected const SLIDESHOW_DEFAULT_LABEL_COLORS = [
        'forums' => ['background' => '#0DCAF0', 'text' => '#07212A', 'border' => '#B6F0FF', 'shadow' => '#000000'],
        'siteannouncements' => ['background' => '#20C997', 'text' => '#06281F', 'border' => '#B5F4DF', 'shadow' => '#000000'],
        'assignments' => ['background' => '#FFC107', 'text' => '#2B2100', 'border' => '#FFE69C', 'shadow' => '#000000'],
        'quizzes' => ['background' => '#DC3545', 'text' => '#FFFFFF', 'border' => '#F1AEB5', 'shadow' => '#000000'],
        'courseorigin' => ['background' => '#FFFFFF', 'text' => '#111827', 'border' => '#E5E7EB', 'shadow' => '#000000'],
    ];
    /** @var array */
    protected const SLIDESHOW_FONT_FAMILY_OPTIONS = [
        '' => 'Theme default',
        'Arial, Helvetica, sans-serif' => 'Arial',
        '"Trebuchet MS", Helvetica, sans-serif' => 'Trebuchet MS',
        'Verdana, Geneva, sans-serif' => 'Verdana',
        'Tahoma, Geneva, sans-serif' => 'Tahoma',
        'Georgia, serif' => 'Georgia',
        '"Times New Roman", Times, serif' => 'Times New Roman',
        'Garamond, Baskerville, serif' => 'Garamond',
        '"Palatino Linotype", "Book Antiqua", Palatino, serif' => 'Palatino',
        '"Segoe UI", Tahoma, Geneva, sans-serif' => 'Segoe UI',
        '"Helvetica Neue", Helvetica, Arial, sans-serif' => 'Helvetica Neue',
        '"Courier New", Courier, monospace' => 'Courier New',
        '"Lucida Console", Monaco, monospace' => 'Lucida Console',
        '"Open Sans", Arial, sans-serif' => 'Open Sans',
        '"Lato", Arial, sans-serif' => 'Lato',
        '"Montserrat", Arial, sans-serif' => 'Montserrat',
        '"Poppins", Arial, sans-serif' => 'Poppins',
        '"Merriweather", Georgia, serif' => 'Merriweather',
        '"Playfair Display", Georgia, serif' => 'Playfair Display',
    ];
    /** @var string */
    public const CUSTOMFIELD_PRIORITY_CATEGORY = 'category';
    /** @var string */
    public const CUSTOMFIELD_PRIORITY_CUSTOMFIELD = 'customfield';
    /** @var string */
    public const CUSTOMFIELD_PRIORITY_APPEND = 'append';
    /** @var string */
    public const CUSTOMFIELD_PRIORITY_PREPEND = 'prepend';
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
    protected const CARD_CANVAS_WIDTH = 1200;
    /** @var int */
    protected const CARD_CANVAS_HEIGHT = 540;
    /** @var int */
    protected const CARD_SQUARE_CANVAS_SIZE = 960;
    /** @var int */
    public const CONFIG_EXPORT_VERSION = 2;
    /** @var string */
    public const EXPORT_SECTION_PLUGIN_SETTINGS = 'pluginsettings';
    /** @var string */
    public const EXPORT_SECTION_COURSE_BANNERS = 'coursebanners';
    /** @var string */
    public const EXPORT_SECTION_SLIDESHOW = 'slideshow';
    /** @var string */
    public const EXPORT_SECTION_SITE_BANNERS = 'sitebanners';
    /** @var string */
    public const EXPORT_OPTION_INCLUDE_CATEGORIES = 'includecategories';
    /** @var string */
    public const EXPORT_OPTION_INCLUDE_CUSTOMFIELDS = 'includecustomfields';
    /** @var string */
    public const IMPORT_OPTION_CREATE_CATEGORIES = 'createcategories';
    /** @var string */
    public const IMPORT_OPTION_CREATE_CUSTOMFIELDS = 'createcustomfields';

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
     * Banner format choices shared by course and site banner managers.
     *
     * @return array
     */
    public static function get_banner_format_options(): array {
        return [
            self::BANNER_FORMAT_STANDARD => get_string('bannerformat:standard', 'local_course_banner_builder'),
            self::BANNER_FORMAT_CONTENT_WIDE => get_string('bannerformat:contentwide', 'local_course_banner_builder'),
            self::BANNER_FORMAT_FULLWIDTH_TOP => get_string('bannerformat:fullwidthtop', 'local_course_banner_builder'),
            self::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT => get_string('bannerformat:fullwidthtopcompact', 'local_course_banner_builder'),
            self::BANNER_FORMAT_FULLWIDTH_TOP_INSET => get_string('bannerformat:fullwidthtopinset', 'local_course_banner_builder'),
        ];
    }

    /**
     * Keep only supported banner format values.
     *
     * @param string $format
     * @return string
     */
    public static function normalise_banner_format(string $format): string {
        return array_key_exists($format, self::get_banner_format_options()) ? $format : self::BANNER_FORMAT_STANDARD;
    }

    /**
     * Banner frame aspect ratio used by admin previews and front-end rendering.
     *
     * @param string $format
     * @return float
     */
    protected static function get_banner_format_aspect_ratio(string $format): float {
        return match (self::normalise_banner_format($format)) {
            self::BANNER_FORMAT_CONTENT_WIDE,
            self::BANNER_FORMAT_FULLWIDTH_TOP => 5.0,
            self::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT => 8.0,
            self::BANNER_FORMAT_FULLWIDTH_TOP_INSET => 6.1,
            default => self::DEFAULT_CANVAS_WIDTH / self::DEFAULT_CANVAS_HEIGHT,
        };
    }

    /**
     * Current public course banner placement format.
     *
     * @return string
     */
    public static function get_course_banner_format(): string {
        return self::normalise_banner_format((string)get_config('local_course_banner_builder', 'coursebannerformat'));
    }

    /**
     * Current public site banner placement format.
     *
     * @return string
     */
    public static function get_site_banner_format(): string {
        return self::normalise_banner_format((string)get_config('local_course_banner_builder', 'sitebannerformat'));
    }

    /**
     * Store the public course banner placement format.
     *
     * @param string $format
     * @return void
     */
    public static function set_course_banner_format(string $format): void {
        set_config('coursebannerformat', self::normalise_banner_format($format), 'local_course_banner_builder');
    }

    /**
     * Store the public site banner placement format.
     *
     * @param string $format
     * @return void
     */
    public static function set_site_banner_format(string $format): void {
        set_config('sitebannerformat', self::normalise_banner_format($format), 'local_course_banner_builder');
    }

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
     * Build the pre-text-cleanup custom field source key for existing stored rows.
     *
     * @param int $fieldid
     * @param string $value
     * @return string
     */
    protected static function get_legacy_customfield_source_key(int $fieldid, string $value): string {
        return self::SOURCE_TYPE_CUSTOMFIELD . ':' . max(0, $fieldid) . ':' .
            sha1(self::normalise_customfield_legacy_value($value));
    }

    /**
     * Return the stable site-banner source.
     *
     * @return \stdClass
     */
    public static function get_site_source(): \stdClass {
        return (object)[
            'type' => self::SOURCE_TYPE_SITE,
            'sourcekey' => self::SITE_SOURCE_KEY,
            'categoryid' => null,
            'customfieldid' => null,
            'customfieldvalue' => null,
            'label' => get_string('sitebanner', 'local_course_banner_builder'),
        ];
    }

    /**
     * Whether a source is the site-banner source.
     *
     * @param \stdClass $source
     * @return bool
     */
    public static function is_site_source(\stdClass $source): bool {
        return ($source->type ?? '') === self::SOURCE_TYPE_SITE ||
            (string)($source->sourcekey ?? '') === self::SITE_SOURCE_KEY;
    }

    /**
     * Normalise custom field values before comparing/storing them.
     *
     * @param string $value
     * @return string
     */
    protected static function normalise_customfield_value(string $value): string {
        return self::clean_customfield_display_value($value);
    }

    /**
     * Normalise custom field values the way older source keys did.
     *
     * @param string $value
     * @return string
     */
    protected static function normalise_customfield_legacy_value(string $value): string {
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
        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcekey') && !empty($record->sourcekey)) {
            return (string)$record->sourcekey;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'sourcekey') && !empty($record->sourcekey)) {
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
            'compositionmode' => self::MODE_CUMULATIVE,
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
        if (self::is_site_source($source)) {
            $settings->sourceparentkey = '';
            $settings->sourceisroot = 1;
            $settings->sourceinheritchildren = 0;
        }
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
     * Add scalar DB fields that are not already represented by a structured export.
     *
     * This keeps exports complete when new persistent settings are added to a source or
     * layer table, while still letting explicit fields above control normalisation.
     *
     * @param array $data
     * @param \stdClass $record
     * @param array $excluded
     * @return array
     */
    protected static function append_extra_persistent_export_fields(
        array $data,
        \stdClass $record,
        array $excluded = []
    ): array {
        $excluded = array_fill_keys($excluded, true);
        foreach (get_object_vars($record) as $field => $value) {
            if (isset($excluded[$field]) || array_key_exists($field, $data)) {
                continue;
            }
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $data[$field] = $value;
        }
        return $data;
    }

    /**
     * Import scalar DB fields that were exported by the generic persistent field pass.
     *
     * @param \stdClass $record
     * @param array $data
     * @param array $handled
     * @param array $excluded
     * @param string $table
     * @return \stdClass
     */
    protected static function apply_extra_persistent_import_fields(
        \stdClass $record,
        array $data,
        array $handled,
        array $excluded,
        string $table
    ): \stdClass {
        $handled = array_fill_keys($handled, true);
        $excluded = array_fill_keys($excluded, true);
        foreach ($data as $field => $value) {
            if (isset($handled[$field]) || isset($excluded[$field]) || !preg_match('/^[a-z][a-z0-9_]*$/', (string)$field)) {
                continue;
            }
            if (!self::table_field_exists($table, (string)$field) && !property_exists($record, (string)$field)) {
                continue;
            }
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $current = property_exists($record, (string)$field) ? $record->{$field} : null;
            if (is_int($current)) {
                $record->{$field} = (int)$value;
            } else if (is_float($current)) {
                $record->{$field} = (float)$value;
            } else {
                $record->{$field} = $value;
            }
        }
        return $record;
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
            $pathsort = implode('/', array_map(static function (int $pathid): string {
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

        usort($items, static function (array $a, array $b): int {
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
            'local_course_banner_builder_elements',
            'DISTINCT categoryid',
            'categoryid IS NOT NULL'
        );
        $settingids = $DB->get_fieldset_select(
            'local_course_banner_builder_order',
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
                $legacykey = self::get_legacy_customfield_source_key((int)$field->id, (string)$rawvalue);
                $displaylabel = self::clean_customfield_display_value((string)$displayvalue);
                $label = $fieldname . ': ' . $displaylabel;
                $isused = isset($usedkeys[$sourcekey]) || isset($usedkeys[$legacykey]);

                $items[] = [
                    'id' => $sourcekey,
                    'sourcekey' => $sourcekey,
                    'fieldid' => (int)$field->id,
                    'value' => self::normalise_customfield_value((string)$rawvalue),
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
        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcekey')) {
            foreach ($DB->get_fieldset_select('local_course_banner_builder_elements', 'DISTINCT sourcekey', 'sourcekey IS NOT NULL') as $key) {
                if ($key !== '') {
                    $keys[(string)$key] = true;
                }
            }
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            foreach ($DB->get_fieldset_select('local_course_banner_builder_order', 'DISTINCT sourcekey', 'sourcekey IS NOT NULL') as $key) {
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
     * Return only already-configured source options for parent-chain selectors.
     *
     * @param string $excludekey
     * @return array
     */
    public static function get_configured_source_parent_options(string $excludekey = ''): array {
        $options = ['' => get_string('sourcechain:none', 'local_course_banner_builder')];
        $keys = array_keys(self::get_used_source_keys());
        sort($keys, SORT_NATURAL);

        foreach ($keys as $key) {
            if ($key === $excludekey || $key === self::SITE_SOURCE_KEY) {
                continue;
            }
            $source = self::resolve_source((string)$key);
            if (!$source) {
                continue;
            }
            $options[(string)$key] = (string)($source->label ?? $key);
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
            $options = self::get_select_field_options($field);
            $records = $DB->get_records(
                'customfield_data',
                ['fieldid' => (int)$field->id],
                '',
                'id,fieldid,intvalue,value,charvalue,shortcharvalue'
            );
            foreach ($records as $record) {
                $rawvalue = self::extract_customfield_data_value($field, $record);
                if ($rawvalue === '' || isset($values[$rawvalue])) {
                    continue;
                }
                $values[$rawvalue] = $options[$rawvalue] ?? $rawvalue;
            }
            asort($values, SORT_NATURAL | SORT_FLAG_CASE);
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
        $seencleanvalues = [];
        foreach ($records as $record) {
            $rawvalue = self::normalise_customfield_legacy_value((string)$record->fieldvalue);
            $displayvalue = self::normalise_customfield_value($rawvalue);
            if ($displayvalue !== '' && !isset($seencleanvalues[$displayvalue])) {
                $values[$rawvalue] = $displayvalue;
                $seencleanvalues[$displayvalue] = true;
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
     * Whether course banners should be displayed on course pages.
     *
     * @return bool
     */
    public static function is_course_banner_enabled(): bool {
        $enabled = get_config('local_course_banner_builder', 'coursebannerenabled');
        return self::is_display_enabled() && ($enabled === false ? true : (bool)$enabled);
    }

    /**
     * Whether course pages without matching banner rules should show Moodle's generated course image.
     *
     * @return bool
     */
    public static function course_default_image_banners_enabled(): bool {
        return self::is_course_banner_enabled() &&
            (bool)get_config('local_course_banner_builder', 'coursebannerdefaultimageenabled');
    }

    /**
     * Whether teacher-managed course overview images should override generated course banners.
     *
     * @return bool
     */
    public static function course_custom_overview_images_enabled(): bool {
        $enabled = get_config('local_course_banner_builder', 'coursecustomoverviewimagesenabled');
        return $enabled === false ? true : (bool)$enabled;
    }

    /**
     * Whether the site-wide banner should be displayed.
     *
     * @return bool
     */
    public static function is_site_banner_enabled(): bool {
        return self::is_display_enabled() && (bool)get_config('local_course_banner_builder', 'sitebannerenabled');
    }

    /**
     * Whether course banners should also be displayed on course activity pages.
     *
     * @return bool
     */
    public static function course_banners_on_activity_pages_enabled(): bool {
        return self::is_course_banner_enabled() && (bool)get_config('local_course_banner_builder', 'coursebanneractivitiesenabled');
    }

    /**
     * Normalise one slideshow context key.
     *
     * @param string $context
     * @return string
     */
    protected static function normalise_slideshow_context(string $context): string {
        return $context === self::SLIDESHOW_CONTEXT_SITE ? self::SLIDESHOW_CONTEXT_SITE : self::SLIDESHOW_CONTEXT_COURSE;
    }

    /**
     * Normalise a hex colour value for slideshow overlays.
     *
     * @param string $color
     * @return string
     */
    protected static function normalise_slideshow_overlay_color(string $color): string {
        $color = trim($color);
        if (preg_match('/^#?[0-9a-f]{6}$/i', $color)) {
            return '#' . strtoupper(ltrim($color, '#'));
        }
        return self::SLIDESHOW_DEFAULT_OVERLAY_COLOR;
    }

    /**
     * Convert a hex colour to RGB channels.
     *
     * @param string $color
     * @return string
     */
    protected static function slideshow_overlay_rgb(string $color): string {
        $color = ltrim(self::normalise_slideshow_overlay_color($color), '#');
        return hexdec(substr($color, 0, 2)) . ', ' .
            hexdec(substr($color, 2, 2)) . ', ' .
            hexdec(substr($color, 4, 2));
    }

    /**
     * Return default slideshow label colour settings.
     *
     * @return array
     */
    public static function get_default_slideshow_label_colors(): array {
        return self::SLIDESHOW_DEFAULT_LABEL_COLORS;
    }

    /**
     * Return default advanced slideshow shape settings.
     *
     * @return array
     */
    public static function get_default_slideshow_style_values(): array {
        return [
            'actionopacity' => 100,
            'actionborderwidth' => 1,
            'actionradius' => 80,
            'actionpadding' => 12,
            'actionshadowopacity' => 28,
            'actionshadowblur' => 16,
            'actionshadowdistance' => 6,
            'actionshadowdirection' => 90,
            'actionbackgroundcolor' => '#FFFFFF',
            'actionbordercolor' => '#FFFFFF',
            'actionshadowcolor' => '#000000',
            'actionfontfamily' => '',
            'actiontextcolor' => '#111827',
            'labelopacity' => 100,
            'labelborderwidth' => 1,
            'labelradius' => 80,
            'labelpadding' => 12,
            'labelshadowopacity' => 16,
            'labelshadowblur' => 8,
            'labelshadowdistance' => 2,
            'labelshadowdirection' => 90,
            'labelbackgroundcolor' => '#FFFFFF',
            'labelbordercolor' => '#FFFFFF',
            'labelshadowcolor' => '#000000',
            'labelfontfamily' => '',
            'labeltextcolor' => '#111827',
            'labeltextsize' => 100,
            'bodylineheight' => 135,
        ];
    }

    /**
     * Return available slideshow font family options.
     *
     * @return array
     */
    public static function get_slideshow_font_family_options(): array {
        return self::SLIDESHOW_FONT_FAMILY_OPTIONS;
    }

    /**
     * Normalise a slideshow font family value.
     *
     * @param string $family
     * @return string
     */
    protected static function normalise_slideshow_font_family(string $family): string {
        $family = trim($family);
        if ($family === '' || array_key_exists($family, self::SLIDESHOW_FONT_FAMILY_OPTIONS)) {
            return $family;
        }
        return self::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY;
    }

    /**
     * Normalise slideshow label orientation.
     *
     * @param string $orientation
     * @return string
     */
    protected static function normalise_slideshow_label_orientation(string $orientation): string {
        return $orientation === self::SLIDESHOW_LABEL_ORIENTATION_COLUMN
            ? self::SLIDESHOW_LABEL_ORIENTATION_COLUMN
            : self::SLIDESHOW_LABEL_ORIENTATION_ROW;
    }

    /**
     * Normalise slideshow alignment values.
     *
     * @param string $alignment
     * @return string
     */
    protected static function normalise_slideshow_alignment(string $alignment): string {
        $alignment = strtolower(trim($alignment));
        if (in_array($alignment, [
            self::SLIDESHOW_ALIGN_LEFT,
            self::SLIDESHOW_ALIGN_CENTER,
            self::SLIDESHOW_ALIGN_RIGHT,
        ], true)) {
            return $alignment;
        }

        return self::SLIDESHOW_ALIGN_CENTER;
    }

    /**
     * Normalise slideshow corner style.
     *
     * @param string $corners
     * @return string
     */
    protected static function normalise_slideshow_corner_style(string $corners): string {
        return $corners === self::SLIDESHOW_CORNER_SQUARE ? self::SLIDESHOW_CORNER_SQUARE : self::SLIDESHOW_CORNER_ROUNDED;
    }

    /**
     * Clamp a slideshow preview position in percent.
     *
     * @param mixed $value
     * @param float $default
     * @return float
     */
    protected static function normalise_slideshow_position_percent($value, float $default): float {
        if ($value === '' || $value === null || $value === false) {
            return $default;
        }
        return max(0.0, min(100.0, (float)$value));
    }

    /**
     * Ensure course records coming from module pages expose source-resolution fields.
     *
     * @param \stdClass $course
     * @return \stdClass
     */
    protected static function ensure_course_context_fields(\stdClass $course): \stdClass {
        global $DB;

        if (!empty($course->id) && empty($course->category)) {
            $fullcourse = $DB->get_record('course', ['id' => (int)$course->id], '*', IGNORE_MISSING);
            if ($fullcourse) {
                foreach ((array)$fullcourse as $key => $value) {
                    if (!property_exists($course, $key) || $course->{$key} === null || $course->{$key} === '') {
                        $course->{$key} = $value;
                    }
                }
            }
        }

        return $course;
    }

    /**
     * Return the stored slideshow configuration for one banner context.
     *
     * @param string $context
     * @return array
     */
    public static function get_slideshow_config(string $context): array {
        $context = self::normalise_slideshow_context($context);
        $prefix = 'slideshow_' . $context . '_';
        $delay = (int)get_config('local_course_banner_builder', $prefix . 'delay');
        if ($delay < 1000) {
            $delay = self::SLIDESHOW_DEFAULT_DELAY;
        }
        $maxslides = (int)get_config('local_course_banner_builder', $prefix . 'maxslides');
        if ($maxslides < 1) {
            $maxslides = self::SLIDESHOW_DEFAULT_MAX_SLIDES;
        }
        $maxslides = max(1, min(self::SLIDESHOW_MAX_SLIDES, $maxslides));
        $siteannouncementdays = (int)get_config('local_course_banner_builder', $prefix . 'siteannouncementdays');
        if ($siteannouncementdays < 1) {
            $siteannouncementdays = self::SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS;
        }
        $color = self::normalise_slideshow_overlay_color((string)get_config('local_course_banner_builder', $prefix . 'overlaycolor'));
        $opacity = get_config('local_course_banner_builder', $prefix . 'overlayopacity');
        $opacity = $opacity === false ? self::SLIDESHOW_DEFAULT_OVERLAY_OPACITY : (float)$opacity;
        $opacity = max(0, min(0.85, $opacity));
        $labelcolors = [];
        foreach (self::SLIDESHOW_DEFAULT_LABEL_COLORS as $type => $defaults) {
            $labelcolors[$type] = [];
            foreach (['background', 'text', 'border', 'shadow'] as $role) {
                $value = get_config('local_course_banner_builder', $prefix . 'label_' . $type . '_' . $role);
                $labelcolors[$type][$role] = $value === false
                    ? $defaults[$role]
                    : self::normalise_slideshow_overlay_color((string)$value);
            }
        }
        $styledefaults = self::get_default_slideshow_style_values();
        $percentstyle = static function (string $field) use ($prefix, $styledefaults): int {
            $value = get_config('local_course_banner_builder', $prefix . $field);
            return max(0, min(100, (int)($value === false ? $styledefaults[$field] : $value)));
        };
        $rangestyle = static function (string $field, int $max) use ($prefix, $styledefaults): int {
            $value = get_config('local_course_banner_builder', $prefix . $field);
            return max(0, min($max, (int)($value === false ? $styledefaults[$field] : $value)));
        };
        $colourstyle = static function (string $field) use ($prefix, $styledefaults): string {
            $value = get_config('local_course_banner_builder', $prefix . $field);
            return self::normalise_slideshow_overlay_color((string)($value === false ? $styledefaults[$field] : $value));
        };
        $fontstyle = static function (string $field) use ($prefix, $styledefaults): string {
            $value = get_config('local_course_banner_builder', $prefix . $field);
            return self::normalise_slideshow_font_family((string)($value === false ? $styledefaults[$field] : $value));
        };

        return [
            'context' => $context,
            'enabled' => (bool)get_config('local_course_banner_builder', $prefix . 'enabled'),
            'forums' => get_config('local_course_banner_builder', $prefix . 'forums') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'forums'),
            'siteannouncements' => get_config('local_course_banner_builder', $prefix . 'siteannouncements') === false
                ? false
                : (bool)get_config('local_course_banner_builder', $prefix . 'siteannouncements'),
            'assignments' => get_config('local_course_banner_builder', $prefix . 'assignments') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'assignments'),
            'quizzes' => get_config('local_course_banner_builder', $prefix . 'quizzes') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'quizzes'),
            'autoplay' => get_config('local_course_banner_builder', $prefix . 'autoplay') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'autoplay'),
            'delay' => $delay,
            'maxslides' => $maxslides,
            'siteannouncementdays' => $siteannouncementdays,
            'arrows' => get_config('local_course_banner_builder', $prefix . 'arrows') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'arrows'),
            'dots' => get_config('local_course_banner_builder', $prefix . 'dots') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'dots'),
            'overlaycolor' => $color,
            'overlayopacity' => $opacity,
            'overlayrgb' => self::slideshow_overlay_rgb($color),
            'labelcolors' => $labelcolors,
            'actionopacity' => $percentstyle('actionopacity'),
            'actionborderwidth' => $rangestyle('actionborderwidth', 20),
            'actionradius' => $rangestyle('actionradius', 120),
            'actionpadding' => $rangestyle('actionpadding', 48),
            'actionshadowopacity' => $percentstyle('actionshadowopacity'),
            'actionshadowblur' => $rangestyle('actionshadowblur', 80),
            'actionshadowdistance' => $rangestyle('actionshadowdistance', 60),
            'actionshadowdirection' => $rangestyle('actionshadowdirection', 360),
            'actionbackgroundcolor' => $colourstyle('actionbackgroundcolor'),
            'actionbordercolor' => $colourstyle('actionbordercolor'),
            'actionshadowcolor' => $colourstyle('actionshadowcolor'),
            'actionfontfamily' => $fontstyle('actionfontfamily'),
            'actiontextcolor' => $colourstyle('actiontextcolor'),
            'labelopacity' => $percentstyle('labelopacity'),
            'labelborderwidth' => $rangestyle('labelborderwidth', 20),
            'labelradius' => $rangestyle('labelradius', 120),
            'labelpadding' => $rangestyle('labelpadding', 48),
            'labelshadowopacity' => $percentstyle('labelshadowopacity'),
            'labelshadowblur' => $rangestyle('labelshadowblur', 80),
            'labelshadowdistance' => $rangestyle('labelshadowdistance', 60),
            'labelshadowdirection' => $rangestyle('labelshadowdirection', 360),
            'labelbackgroundcolor' => $colourstyle('labelbackgroundcolor'),
            'labelbordercolor' => $colourstyle('labelbordercolor'),
            'labelshadowcolor' => $colourstyle('labelshadowcolor'),
            'labelfontfamily' => $fontstyle('labelfontfamily'),
            'labeltextcolor' => $colourstyle('labeltextcolor'),
            'labeltextsize' => $rangestyle('labeltextsize', 160),
            'bodylineheight' => $rangestyle('bodylineheight', 200),
            'titlefontsize' => max(25, min(100, (int)(get_config('local_course_banner_builder', $prefix . 'titlefontsize') ?: self::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT))),
            'bodyfontsize' => max(25, min(100, (int)(get_config('local_course_banner_builder', $prefix . 'bodyfontsize') ?: self::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT))),
            'actionsize' => max(25, min(100, (int)(get_config('local_course_banner_builder', $prefix . 'actionsize') ?: self::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT))),
            'actionwidth' => max(25, min(100, (int)(get_config('local_course_banner_builder', $prefix . 'actionwidth') ?: self::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT))),
            'actionheight' => max(25, min(100, (int)(get_config('local_course_banner_builder', $prefix . 'actionheight') ?: self::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT))),
            'labelsize' => max(25, min(100, (int)(get_config('local_course_banner_builder', $prefix . 'labelsize') ?: self::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT))),
            'labelorientation' => self::normalise_slideshow_label_orientation(
                (string)(get_config('local_course_banner_builder', $prefix . 'labelorientation') ?: self::SLIDESHOW_DEFAULT_LABEL_ORIENTATION)
            ),
            'labelcorners' => self::normalise_slideshow_corner_style(
                (string)(get_config('local_course_banner_builder', $prefix . 'labelcorners') ?: self::SLIDESHOW_DEFAULT_LABEL_CORNERS)
            ),
            'actioncorners' => self::normalise_slideshow_corner_style(
                (string)(get_config('local_course_banner_builder', $prefix . 'actioncorners') ?: self::SLIDESHOW_DEFAULT_ACTION_CORNERS)
            ),
            'titlecolor' => self::normalise_slideshow_overlay_color(
                (string)(get_config('local_course_banner_builder', $prefix . 'titlecolor') ?: self::SLIDESHOW_DEFAULT_TITLE_COLOR)
            ),
            'bodycolor' => self::normalise_slideshow_overlay_color(
                (string)(get_config('local_course_banner_builder', $prefix . 'bodycolor') ?: self::SLIDESHOW_DEFAULT_BODY_COLOR)
            ),
            'titlefontfamily' => self::normalise_slideshow_font_family(
                (string)(get_config('local_course_banner_builder', $prefix . 'titlefontfamily') ?: self::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY)
            ),
            'bodyfontfamily' => self::normalise_slideshow_font_family(
                (string)(get_config('local_course_banner_builder', $prefix . 'bodyfontfamily') ?: self::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY)
            ),
            'titlealign' => self::normalise_slideshow_alignment(
                (string)(get_config('local_course_banner_builder', $prefix . 'titlealign') ?: self::SLIDESHOW_DEFAULT_TITLE_ALIGN)
            ),
            'bodyalign' => self::normalise_slideshow_alignment(
                (string)(get_config('local_course_banner_builder', $prefix . 'bodyalign') ?: self::SLIDESHOW_DEFAULT_BODY_ALIGN)
            ),
            'labelalign' => self::normalise_slideshow_alignment(
                (string)(get_config('local_course_banner_builder', $prefix . 'labelalign') ?: self::SLIDESHOW_DEFAULT_LABEL_ALIGN)
            ),
            'titlebold' => get_config('local_course_banner_builder', $prefix . 'titlebold') === false
                ? self::SLIDESHOW_DEFAULT_TITLE_BOLD
                : (bool)get_config('local_course_banner_builder', $prefix . 'titlebold'),
            'titleitalic' => get_config('local_course_banner_builder', $prefix . 'titleitalic') === false
                ? self::SLIDESHOW_DEFAULT_TITLE_ITALIC
                : (bool)get_config('local_course_banner_builder', $prefix . 'titleitalic'),
            'titleunderline' => get_config('local_course_banner_builder', $prefix . 'titleunderline') === false
                ? self::SLIDESHOW_DEFAULT_TITLE_UNDERLINE
                : (bool)get_config('local_course_banner_builder', $prefix . 'titleunderline'),
            'titlestrike' => get_config('local_course_banner_builder', $prefix . 'titlestrike') === false
                ? self::SLIDESHOW_DEFAULT_TITLE_STRIKE
                : (bool)get_config('local_course_banner_builder', $prefix . 'titlestrike'),
            'titleallcaps' => (bool)get_config('local_course_banner_builder', $prefix . 'titleallcaps'),
            'bodybold' => get_config('local_course_banner_builder', $prefix . 'bodybold') === false
                ? self::SLIDESHOW_DEFAULT_BODY_BOLD
                : (bool)get_config('local_course_banner_builder', $prefix . 'bodybold'),
            'bodyitalic' => get_config('local_course_banner_builder', $prefix . 'bodyitalic') === false
                ? self::SLIDESHOW_DEFAULT_BODY_ITALIC
                : (bool)get_config('local_course_banner_builder', $prefix . 'bodyitalic'),
            'bodyunderline' => get_config('local_course_banner_builder', $prefix . 'bodyunderline') === false
                ? self::SLIDESHOW_DEFAULT_BODY_UNDERLINE
                : (bool)get_config('local_course_banner_builder', $prefix . 'bodyunderline'),
            'bodystrike' => get_config('local_course_banner_builder', $prefix . 'bodystrike') === false
                ? self::SLIDESHOW_DEFAULT_BODY_STRIKE
                : (bool)get_config('local_course_banner_builder', $prefix . 'bodystrike'),
            'bodyallcaps' => (bool)get_config('local_course_banner_builder', $prefix . 'bodyallcaps'),
            'actionbold' => get_config('local_course_banner_builder', $prefix . 'actionbold') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'actionbold'),
            'actionitalic' => (bool)get_config('local_course_banner_builder', $prefix . 'actionitalic'),
            'actionunderline' => (bool)get_config('local_course_banner_builder', $prefix . 'actionunderline'),
            'actionstrike' => (bool)get_config('local_course_banner_builder', $prefix . 'actionstrike'),
            'actionallcaps' => (bool)get_config('local_course_banner_builder', $prefix . 'actionallcaps'),
            'labelbold' => get_config('local_course_banner_builder', $prefix . 'labelbold') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'labelbold'),
            'labelitalic' => (bool)get_config('local_course_banner_builder', $prefix . 'labelitalic'),
            'labelunderline' => (bool)get_config('local_course_banner_builder', $prefix . 'labelunderline'),
            'labelstrike' => (bool)get_config('local_course_banner_builder', $prefix . 'labelstrike'),
            'labelallcaps' => get_config('local_course_banner_builder', $prefix . 'labelallcaps') === false
                ? true
                : (bool)get_config('local_course_banner_builder', $prefix . 'labelallcaps'),
            'titlex' => self::normalise_slideshow_position_percent(
                get_config('local_course_banner_builder', $prefix . 'titlex'),
                self::SLIDESHOW_DEFAULT_TITLE_X
            ),
            'titley' => self::normalise_slideshow_position_percent(
                get_config('local_course_banner_builder', $prefix . 'titley'),
                self::SLIDESHOW_DEFAULT_TITLE_Y
            ),
            'bodyx' => self::normalise_slideshow_position_percent(
                get_config('local_course_banner_builder', $prefix . 'bodyx'),
                self::SLIDESHOW_DEFAULT_BODY_X
            ),
            'bodyy' => self::normalise_slideshow_position_percent(
                get_config('local_course_banner_builder', $prefix . 'bodyy'),
                self::SLIDESHOW_DEFAULT_BODY_Y
            ),
            'actionx' => self::normalise_slideshow_position_percent(
                get_config('local_course_banner_builder', $prefix . 'actionx'),
                self::SLIDESHOW_DEFAULT_ACTION_X
            ),
            'actiony' => self::normalise_slideshow_position_percent(
                get_config('local_course_banner_builder', $prefix . 'actiony'),
                self::SLIDESHOW_DEFAULT_ACTION_Y
            ),
            'labelx' => self::normalise_slideshow_position_percent(
                get_config('local_course_banner_builder', $prefix . 'labelx'),
                self::SLIDESHOW_DEFAULT_LABEL_X
            ),
            'labely' => self::normalise_slideshow_position_percent(
                get_config('local_course_banner_builder', $prefix . 'labely'),
                self::SLIDESHOW_DEFAULT_LABEL_Y
            ),
        ];
    }

    /**
     * Store slideshow configuration for one banner context.
     *
     * @param string $context
     * @param array $values
     * @return void
     */
    public static function set_slideshow_config(string $context, array $values): void {
        $context = self::normalise_slideshow_context($context);
        $prefix = 'slideshow_' . $context . '_';
        $delay = max(1000, min(60000, (int)($values['delay'] ?? self::SLIDESHOW_DEFAULT_DELAY)));
        $maxslides = max(1, min(self::SLIDESHOW_MAX_SLIDES, (int)($values['maxslides'] ?? self::SLIDESHOW_DEFAULT_MAX_SLIDES)));
        $siteannouncementdays = max(1, min(3650, (int)($values['siteannouncementdays'] ??
            self::SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS)));
        $color = self::normalise_slideshow_overlay_color((string)($values['overlaycolor'] ?? self::SLIDESHOW_DEFAULT_OVERLAY_COLOR));
        $opacity = max(0, min(0.85, ((float)($values['overlayopacity'] ?? self::SLIDESHOW_DEFAULT_OVERLAY_OPACITY)) / 100));
        foreach (['enabled', 'forums', 'siteannouncements', 'assignments', 'quizzes', 'autoplay', 'arrows', 'dots'] as $field) {
            set_config($prefix . $field, empty($values[$field]) ? 0 : 1, 'local_course_banner_builder');
        }
        set_config($prefix . 'delay', $delay, 'local_course_banner_builder');
        set_config($prefix . 'maxslides', $maxslides, 'local_course_banner_builder');
        set_config($prefix . 'siteannouncementdays', $siteannouncementdays, 'local_course_banner_builder');
        set_config($prefix . 'overlaycolor', $color, 'local_course_banner_builder');
        set_config($prefix . 'overlayopacity', $opacity, 'local_course_banner_builder');
        set_config(
            $prefix . 'titlefontsize',
            max(25, min(100, (int)($values['titlefontsize'] ?? self::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT))),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'bodyfontsize',
            max(25, min(100, (int)($values['bodyfontsize'] ?? self::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT))),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'actionsize',
            max(25, min(100, (int)($values['actionsize'] ?? self::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT))),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'actionwidth',
            max(25, min(100, (int)($values['actionwidth'] ?? self::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT))),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'actionheight',
            max(25, min(100, (int)($values['actionheight'] ?? self::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT))),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'labelsize',
            max(25, min(100, (int)($values['labelsize'] ?? self::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT))),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'labeltextsize',
            max(25, min(160, (int)($values['labeltextsize'] ?? 100))),
            'local_course_banner_builder'
        );
        $styledefaults = self::get_default_slideshow_style_values();
        foreach ([
            'actionopacity' => [0, 100],
            'actionborderwidth' => [0, 20],
            'actionradius' => [0, 120],
            'actionpadding' => [0, 48],
            'actionshadowopacity' => [0, 100],
            'actionshadowblur' => [0, 80],
            'actionshadowdistance' => [0, 60],
            'actionshadowdirection' => [0, 360],
            'labelopacity' => [0, 100],
            'labelborderwidth' => [0, 20],
            'labelradius' => [0, 120],
            'labelpadding' => [0, 48],
            'labelshadowopacity' => [0, 100],
            'labelshadowblur' => [0, 80],
            'labelshadowdistance' => [0, 60],
            'labelshadowdirection' => [0, 360],
            'bodylineheight' => [80, 200],
        ] as $field => $bounds) {
            set_config(
                $prefix . $field,
                max($bounds[0], min($bounds[1], (int)($values[$field] ?? $styledefaults[$field]))),
                'local_course_banner_builder'
            );
        }
        foreach ([
            'actionbackgroundcolor',
            'actionbordercolor',
            'actionshadowcolor',
            'actiontextcolor',
            'labelbackgroundcolor',
            'labelbordercolor',
            'labelshadowcolor',
            'labeltextcolor',
        ] as $field) {
            set_config(
                $prefix . $field,
                self::normalise_slideshow_overlay_color((string)($values[$field] ?? $styledefaults[$field])),
                'local_course_banner_builder'
            );
        }
        foreach (['actionfontfamily', 'labelfontfamily'] as $field) {
            set_config(
                $prefix . $field,
                self::normalise_slideshow_font_family((string)($values[$field] ?? $styledefaults[$field])),
                'local_course_banner_builder'
            );
        }
        set_config(
            $prefix . 'labelorientation',
            self::normalise_slideshow_label_orientation((string)($values['labelorientation'] ?? self::SLIDESHOW_DEFAULT_LABEL_ORIENTATION)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'labelcorners',
            self::normalise_slideshow_corner_style((string)($values['labelcorners'] ?? self::SLIDESHOW_DEFAULT_LABEL_CORNERS)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'actioncorners',
            self::normalise_slideshow_corner_style((string)($values['actioncorners'] ?? self::SLIDESHOW_DEFAULT_ACTION_CORNERS)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'titlecolor',
            self::normalise_slideshow_overlay_color((string)($values['titlecolor'] ?? self::SLIDESHOW_DEFAULT_TITLE_COLOR)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'bodycolor',
            self::normalise_slideshow_overlay_color((string)($values['bodycolor'] ?? self::SLIDESHOW_DEFAULT_BODY_COLOR)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'titlefontfamily',
            self::normalise_slideshow_font_family((string)($values['titlefontfamily'] ?? self::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'bodyfontfamily',
            self::normalise_slideshow_font_family((string)($values['bodyfontfamily'] ?? self::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'titlealign',
            self::normalise_slideshow_alignment((string)($values['titlealign'] ?? self::SLIDESHOW_DEFAULT_TITLE_ALIGN)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'bodyalign',
            self::normalise_slideshow_alignment((string)($values['bodyalign'] ?? self::SLIDESHOW_DEFAULT_BODY_ALIGN)),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'labelalign',
            self::normalise_slideshow_alignment((string)($values['labelalign'] ?? self::SLIDESHOW_DEFAULT_LABEL_ALIGN)),
            'local_course_banner_builder'
        );
        foreach (['titlebold', 'titleitalic', 'titleunderline', 'titlestrike',
            'titleallcaps', 'bodybold', 'bodyitalic', 'bodyunderline', 'bodystrike',
            'bodyallcaps', 'actionbold', 'actionitalic', 'actionunderline', 'actionstrike',
            'actionallcaps', 'labelbold', 'labelitalic', 'labelunderline', 'labelstrike',
            'labelallcaps'] as $field) {
            set_config($prefix . $field, empty($values[$field]) ? 0 : 1, 'local_course_banner_builder');
        }
        set_config(
            $prefix . 'titlex',
            self::normalise_slideshow_position_percent($values['titlex'] ?? null, self::SLIDESHOW_DEFAULT_TITLE_X),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'titley',
            self::normalise_slideshow_position_percent($values['titley'] ?? null, self::SLIDESHOW_DEFAULT_TITLE_Y),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'bodyx',
            self::normalise_slideshow_position_percent($values['bodyx'] ?? null, self::SLIDESHOW_DEFAULT_BODY_X),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'bodyy',
            self::normalise_slideshow_position_percent($values['bodyy'] ?? null, self::SLIDESHOW_DEFAULT_BODY_Y),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'actionx',
            self::normalise_slideshow_position_percent($values['actionx'] ?? null, self::SLIDESHOW_DEFAULT_ACTION_X),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'actiony',
            self::normalise_slideshow_position_percent($values['actiony'] ?? null, self::SLIDESHOW_DEFAULT_ACTION_Y),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'labelx',
            self::normalise_slideshow_position_percent($values['labelx'] ?? null, self::SLIDESHOW_DEFAULT_LABEL_X),
            'local_course_banner_builder'
        );
        set_config(
            $prefix . 'labely',
            self::normalise_slideshow_position_percent($values['labely'] ?? null, self::SLIDESHOW_DEFAULT_LABEL_Y),
            'local_course_banner_builder'
        );
        foreach (self::SLIDESHOW_DEFAULT_LABEL_COLORS as $type => $defaults) {
            foreach (['background', 'text', 'border', 'shadow'] as $role) {
                $value = self::normalise_slideshow_overlay_color(
                    (string)($values['label_' . $type . '_' . $role] ?? $defaults[$role])
                );
                set_config($prefix . 'label_' . $type . '_' . $role, $value, 'local_course_banner_builder');
            }
        }
    }

    /**
     * Restore slideshow defaults for one context.
     *
     * @param string $context
     * @return void
     */
    public static function reset_slideshow_config(string $context): void {
        self::set_slideshow_config($context, self::get_default_slideshow_style_values() + [
            'enabled' => 0,
            'forums' => 1,
            'siteannouncements' => 0,
            'assignments' => 1,
            'quizzes' => 1,
            'autoplay' => 1,
            'delay' => self::SLIDESHOW_DEFAULT_DELAY,
            'maxslides' => self::SLIDESHOW_DEFAULT_MAX_SLIDES,
            'siteannouncementdays' => self::SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS,
            'arrows' => 1,
            'dots' => 1,
            'overlaycolor' => self::SLIDESHOW_DEFAULT_OVERLAY_COLOR,
            'overlayopacity' => self::SLIDESHOW_DEFAULT_OVERLAY_OPACITY * 100,
            'titlefontsize' => self::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT,
            'bodyfontsize' => self::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT,
            'bodylineheight' => 135,
            'actionsize' => self::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT,
            'actionwidth' => self::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT,
            'actionheight' => self::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT,
            'labelsize' => self::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT,
            'labeltextsize' => 100,
            'labelorientation' => self::SLIDESHOW_DEFAULT_LABEL_ORIENTATION,
            'labelcorners' => self::SLIDESHOW_DEFAULT_LABEL_CORNERS,
            'actioncorners' => self::SLIDESHOW_DEFAULT_ACTION_CORNERS,
            'titlecolor' => self::SLIDESHOW_DEFAULT_TITLE_COLOR,
            'bodycolor' => self::SLIDESHOW_DEFAULT_BODY_COLOR,
            'titlefontfamily' => self::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY,
            'bodyfontfamily' => self::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY,
            'titlealign' => self::SLIDESHOW_DEFAULT_TITLE_ALIGN,
            'bodyalign' => self::SLIDESHOW_DEFAULT_BODY_ALIGN,
            'labelalign' => self::SLIDESHOW_DEFAULT_LABEL_ALIGN,
            'titlebold' => self::SLIDESHOW_DEFAULT_TITLE_BOLD,
            'titleitalic' => self::SLIDESHOW_DEFAULT_TITLE_ITALIC,
            'titleunderline' => self::SLIDESHOW_DEFAULT_TITLE_UNDERLINE,
            'titlestrike' => self::SLIDESHOW_DEFAULT_TITLE_STRIKE,
            'titleallcaps' => false,
            'bodybold' => self::SLIDESHOW_DEFAULT_BODY_BOLD,
            'bodyitalic' => self::SLIDESHOW_DEFAULT_BODY_ITALIC,
            'bodyunderline' => self::SLIDESHOW_DEFAULT_BODY_UNDERLINE,
            'bodystrike' => self::SLIDESHOW_DEFAULT_BODY_STRIKE,
            'bodyallcaps' => false,
            'actionbold' => true,
            'actionitalic' => false,
            'actionunderline' => false,
            'actionstrike' => false,
            'actionallcaps' => false,
            'labelbold' => true,
            'labelitalic' => false,
            'labelunderline' => false,
            'labelstrike' => false,
            'labelallcaps' => true,
            'titlex' => self::SLIDESHOW_DEFAULT_TITLE_X,
            'titley' => self::SLIDESHOW_DEFAULT_TITLE_Y,
            'bodyx' => self::SLIDESHOW_DEFAULT_BODY_X,
            'bodyy' => self::SLIDESHOW_DEFAULT_BODY_Y,
            'actionx' => self::SLIDESHOW_DEFAULT_ACTION_X,
            'actiony' => self::SLIDESHOW_DEFAULT_ACTION_Y,
            'labelx' => self::SLIDESHOW_DEFAULT_LABEL_X,
            'labely' => self::SLIDESHOW_DEFAULT_LABEL_Y,
            'label_forums_background' => self::SLIDESHOW_DEFAULT_LABEL_COLORS['forums']['background'],
            'label_forums_text' => self::SLIDESHOW_DEFAULT_LABEL_COLORS['forums']['text'],
            'label_siteannouncements_background' => self::SLIDESHOW_DEFAULT_LABEL_COLORS['siteannouncements']['background'],
            'label_siteannouncements_text' => self::SLIDESHOW_DEFAULT_LABEL_COLORS['siteannouncements']['text'],
            'label_assignments_background' => self::SLIDESHOW_DEFAULT_LABEL_COLORS['assignments']['background'],
            'label_assignments_text' => self::SLIDESHOW_DEFAULT_LABEL_COLORS['assignments']['text'],
            'label_quizzes_background' => self::SLIDESHOW_DEFAULT_LABEL_COLORS['quizzes']['background'],
            'label_quizzes_text' => self::SLIDESHOW_DEFAULT_LABEL_COLORS['quizzes']['text'],
        ]);
    }

    /**
     * Build the public slideshow payload for a course banner.
     *
     * @param \stdClass $course
     * @return array
     */
    public static function get_course_slideshow_payload(\stdClass $course): array {
        $config = self::get_slideshow_config(self::SLIDESHOW_CONTEXT_COURSE);
        $config = self::apply_course_source_overlay_to_slideshow_config($config, $course);
        if (empty($config['enabled']) || empty($course->id) || (int)$course->id <= SITEID || isguestuser()) {
            return self::build_slideshow_payload(self::SLIDESHOW_CONTEXT_COURSE, $config, []);
        }

        $slides = [];
        $siteannouncementslides = [];
        if (!empty($config['forums'])) {
            $slides = array_merge($slides, self::get_forum_slideshow_slides((int)$course->id, false));
        }
        if (!empty($config['siteannouncements'])) {
            $siteannouncementslides = self::get_forum_slideshow_slides(
                SITEID,
                true,
                true,
                (int)($config['siteannouncementdays'] ?? self::SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS)
            );
        }
        if (!empty($config['assignments'])) {
            $slides = array_merge($slides, self::get_assignment_slideshow_slides([(int)$course->id], (int)$course->id));
        }
        if (!empty($config['quizzes'])) {
            $slides = array_merge($slides, self::get_quiz_slideshow_slides([(int)$course->id], (int)$course->id));
        }
        if (!empty($siteannouncementslides)) {
            $slides = array_merge($siteannouncementslides, $slides);
        }

        return self::build_slideshow_payload(self::SLIDESHOW_CONTEXT_COURSE, $config, $slides);
    }

    /**
     * Build the public slideshow payload for the site banner.
     *
     * @return array
     */
    public static function get_site_slideshow_payload(): array {
        $config = self::get_slideshow_config(self::SLIDESHOW_CONTEXT_SITE);
        $config = self::apply_site_source_overlay_to_slideshow_config($config);
        if (empty($config['enabled']) || isguestuser()) {
            return self::build_slideshow_payload(self::SLIDESHOW_CONTEXT_SITE, $config, []);
        }

        $slides = [];
        if (!empty($config['forums'])) {
            $slides = array_merge($slides, self::get_forum_slideshow_slides(
                SITEID,
                true,
                false,
                (int)($config['siteannouncementdays'] ?? self::SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS)
            ));
        }
        $courseids = self::get_current_user_student_course_ids();
        if (!empty($config['assignments'])) {
            $slides = array_merge($slides, self::get_assignment_slideshow_slides($courseids, 0));
        }
        if (!empty($config['quizzes'])) {
            $slides = array_merge($slides, self::get_quiz_slideshow_slides($courseids, 0));
        }

        return self::build_slideshow_payload(self::SLIDESHOW_CONTEXT_SITE, $config, $slides);
    }

    /**
     * Apply an overlay layer from the effective course source chain to slideshow config.
     *
     * @param array $config
     * @param \stdClass $course
     * @return array
     */
    protected static function apply_course_source_overlay_to_slideshow_config(array $config, \stdClass $course): array {
        if (empty($course->id) || (int)$course->id <= SITEID) {
            return $config;
        }

        foreach (self::sort_layer_specs(self::get_enabled_category_elements_for_course($course)) as $layerspec) {
            $record = $layerspec['record'] ?? null;
            if ($record && self::record_overlay_targets_slideshow($record)) {
                return self::apply_overlay_record_to_slideshow_config($config, $record);
            }
        }

        return $config;
    }

    /**
     * Apply the site source overlay layer to slideshow config.
     *
     * @param array $config
     * @return array
     */
    protected static function apply_site_source_overlay_to_slideshow_config(array $config): array {
        $source = self::resolve_source(self::SITE_SOURCE_KEY);
        if (!$source) {
            return $config;
        }

        foreach (self::sort_layer_specs(self::get_layer_specs_for_source_chain($source, 0, 0)) as $layerspec) {
            $record = $layerspec['record'] ?? null;
            if ($record && self::record_overlay_targets_slideshow($record)) {
                return self::apply_overlay_record_to_slideshow_config($config, $record);
            }
        }

        return $config;
    }

    /**
     * Apply one overlay layer's slideshow appearance to slideshow config.
     *
     * @param array $config
     * @param \stdClass $record
     * @return array
     */
    protected static function apply_overlay_record_to_slideshow_config(array $config, \stdClass $record): array {
        $color = self::normalise_slideshow_overlay_color((string)($record->overlayslideshowcolor ?? '#000000'));
        $opacity = self::normalise_percentage((float)($record->overlayslideshowopacity ?? 38), 0.0, 100.0) / 100;
        $config['overlaycolor'] = $color;
        $config['overlayrgb'] = self::slideshow_overlay_rgb($color);
        $config['overlayopacity'] = max(0, min(0.85, $opacity));
        return $config;
    }

    /**
     * Package slideshow slides and controls for front-end rendering.
     *
     * @param string $context
     * @param array $config
     * @param array $slides
     * @return array
     */
    protected static function build_slideshow_payload(string $context, array $config, array $slides): array {
        array_unshift($slides, [
            'type' => 'banner',
            'label' => '',
            'title' => '',
            'meta' => '',
            'body' => '',
            'url' => '',
            'time' => PHP_INT_MAX,
            'empty' => true,
        ]);
        $maxslides = max(1, min(self::SLIDESHOW_MAX_SLIDES, (int)($config['maxslides'] ?? self::SLIDESHOW_DEFAULT_MAX_SLIDES)));
        $slides = array_slice($slides, 0, $maxslides);
        $styledefaults = self::get_default_slideshow_style_values();
        $labely = self::normalise_slideshow_position_percent(
            $config['labely'] ?? null,
            self::SLIDESHOW_DEFAULT_LABEL_Y
        );
        $bannerformat = $context === self::SLIDESHOW_CONTEXT_SITE ?
            self::get_site_banner_format() :
            self::get_course_banner_format();
        if ($bannerformat === self::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT &&
                abs($labely - self::SLIDESHOW_DEFAULT_LABEL_Y) < 0.001) {
            $labely = 18.0;
        }

        return [
            'context' => self::normalise_slideshow_context($context),
            'enabled' => !empty($config['enabled']),
            'hasSlides' => !empty($slides),
            'autoplay' => !empty($config['autoplay']),
            'delay' => max(1000, (int)($config['delay'] ?? self::SLIDESHOW_DEFAULT_DELAY)),
            'arrows' => !empty($config['arrows']),
            'dots' => !empty($config['dots']),
            'overlayColor' => (string)($config['overlaycolor'] ?? self::SLIDESHOW_DEFAULT_OVERLAY_COLOR),
            'overlayRgb' => (string)($config['overlayrgb'] ?? self::slideshow_overlay_rgb(self::SLIDESHOW_DEFAULT_OVERLAY_COLOR)),
            'overlayOpacity' => (float)($config['overlayopacity'] ?? self::SLIDESHOW_DEFAULT_OVERLAY_OPACITY),
            'labelColors' => $config['labelcolors'] ?? self::SLIDESHOW_DEFAULT_LABEL_COLORS,
            'actionOpacity' => max(0, min(100, (int)($config['actionopacity'] ?? $styledefaults['actionopacity']))),
            'actionBorderWidth' => max(0, min(20, (int)($config['actionborderwidth'] ?? $styledefaults['actionborderwidth']))),
            'actionRadius' => max(0, min(120, (int)($config['actionradius'] ?? $styledefaults['actionradius']))),
            'actionPadding' => max(0, min(48, (int)($config['actionpadding'] ?? $styledefaults['actionpadding']))),
            'actionShadowOpacity' => max(0, min(100, (int)($config['actionshadowopacity'] ?? $styledefaults['actionshadowopacity']))),
            'actionShadowBlur' => max(0, min(80, (int)($config['actionshadowblur'] ?? $styledefaults['actionshadowblur']))),
            'actionShadowDistance' => max(0, min(60, (int)($config['actionshadowdistance'] ?? $styledefaults['actionshadowdistance']))),
            'actionShadowDirection' => max(0, min(360, (int)($config['actionshadowdirection'] ?? $styledefaults['actionshadowdirection']))),
            'actionBackgroundColor' => (string)($config['actionbackgroundcolor'] ?? $styledefaults['actionbackgroundcolor']),
            'actionBorderColor' => (string)($config['actionbordercolor'] ?? $styledefaults['actionbordercolor']),
            'actionShadowColor' => (string)($config['actionshadowcolor'] ?? $styledefaults['actionshadowcolor']),
            'actionFontFamily' => (string)($config['actionfontfamily'] ?? $styledefaults['actionfontfamily']),
            'actionTextColor' => (string)($config['actiontextcolor'] ?? $styledefaults['actiontextcolor']),
            'labelOpacity' => max(0, min(100, (int)($config['labelopacity'] ?? $styledefaults['labelopacity']))),
            'labelBorderWidth' => max(0, min(20, (int)($config['labelborderwidth'] ?? $styledefaults['labelborderwidth']))),
            'labelRadius' => max(0, min(120, (int)($config['labelradius'] ?? $styledefaults['labelradius']))),
            'labelPadding' => max(0, min(48, (int)($config['labelpadding'] ?? $styledefaults['labelpadding']))),
            'labelShadowOpacity' => max(0, min(100, (int)($config['labelshadowopacity'] ?? $styledefaults['labelshadowopacity']))),
            'labelShadowBlur' => max(0, min(80, (int)($config['labelshadowblur'] ?? $styledefaults['labelshadowblur']))),
            'labelShadowDistance' => max(0, min(60, (int)($config['labelshadowdistance'] ?? $styledefaults['labelshadowdistance']))),
            'labelShadowDirection' => max(0, min(360, (int)($config['labelshadowdirection'] ?? $styledefaults['labelshadowdirection']))),
            'labelBackgroundColor' => (string)($config['labelbackgroundcolor'] ?? $styledefaults['labelbackgroundcolor']),
            'labelBorderColor' => (string)($config['labelbordercolor'] ?? $styledefaults['labelbordercolor']),
            'labelShadowColor' => (string)($config['labelshadowcolor'] ?? $styledefaults['labelshadowcolor']),
            'labelFontFamily' => (string)($config['labelfontfamily'] ?? $styledefaults['labelfontfamily']),
            'labelTextColor' => (string)($config['labeltextcolor'] ?? $styledefaults['labeltextcolor']),
            'labelTextSizePercent' => max(25, min(160, (int)($config['labeltextsize'] ?? $styledefaults['labeltextsize']))),
            'titleFontPercent' => max(25, min(100, (int)($config['titlefontsize'] ?? self::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT))),
            'bodyFontPercent' => max(25, min(100, (int)($config['bodyfontsize'] ?? self::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT))),
            'bodyLineHeightPercent' => max(80, min(200, (float)($config['bodylineheight'] ?? $styledefaults['bodylineheight']))),
            'actionSizePercent' => max(25, min(100, (int)($config['actionsize'] ?? self::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT))),
            'actionWidthPercent' => max(25, min(100, (int)($config['actionwidth'] ?? self::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT))),
            'actionHeightPercent' => max(25, min(100, (int)($config['actionheight'] ?? self::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT))),
            'labelSizePercent' => max(25, min(100, (int)($config['labelsize'] ?? self::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT))),
            'labelOrientation' => self::normalise_slideshow_label_orientation(
                (string)($config['labelorientation'] ?? self::SLIDESHOW_DEFAULT_LABEL_ORIENTATION)
            ),
            'labelCorners' => self::normalise_slideshow_corner_style(
                (string)($config['labelcorners'] ?? self::SLIDESHOW_DEFAULT_LABEL_CORNERS)
            ),
            'actionCorners' => self::normalise_slideshow_corner_style(
                (string)($config['actioncorners'] ?? self::SLIDESHOW_DEFAULT_ACTION_CORNERS)
            ),
            'titleColor' => (string)($config['titlecolor'] ?? self::SLIDESHOW_DEFAULT_TITLE_COLOR),
            'bodyColor' => (string)($config['bodycolor'] ?? self::SLIDESHOW_DEFAULT_BODY_COLOR),
            'titleFontFamily' => (string)($config['titlefontfamily'] ?? self::SLIDESHOW_DEFAULT_TITLE_FONT_FAMILY),
            'bodyFontFamily' => (string)($config['bodyfontfamily'] ?? self::SLIDESHOW_DEFAULT_BODY_FONT_FAMILY),
            'titleAlign' => self::normalise_slideshow_alignment(
                (string)($config['titlealign'] ?? self::SLIDESHOW_DEFAULT_TITLE_ALIGN)
            ),
            'bodyAlign' => self::normalise_slideshow_alignment(
                (string)($config['bodyalign'] ?? self::SLIDESHOW_DEFAULT_BODY_ALIGN)
            ),
            'labelAlign' => self::normalise_slideshow_alignment(
                (string)($config['labelalign'] ?? self::SLIDESHOW_DEFAULT_LABEL_ALIGN)
            ),
            'titleBold' => !empty($config['titlebold']),
            'titleItalic' => !empty($config['titleitalic']),
            'titleUnderline' => !empty($config['titleunderline']),
            'titleStrike' => !empty($config['titlestrike']),
            'titleAllCaps' => !empty($config['titleallcaps']),
            'bodyBold' => !empty($config['bodybold']),
            'bodyItalic' => !empty($config['bodyitalic']),
            'bodyUnderline' => !empty($config['bodyunderline']),
            'bodyStrike' => !empty($config['bodystrike']),
            'bodyAllCaps' => !empty($config['bodyallcaps']),
            'actionBold' => !empty($config['actionbold']),
            'actionItalic' => !empty($config['actionitalic']),
            'actionUnderline' => !empty($config['actionunderline']),
            'actionStrike' => !empty($config['actionstrike']),
            'actionAllCaps' => !empty($config['actionallcaps']),
            'labelBold' => !empty($config['labelbold']),
            'labelItalic' => !empty($config['labelitalic']),
            'labelUnderline' => !empty($config['labelunderline']),
            'labelStrike' => !empty($config['labelstrike']),
            'labelAllCaps' => array_key_exists('labelallcaps', $config) ? !empty($config['labelallcaps']) : true,
            'titleX' => self::normalise_slideshow_position_percent($config['titlex'] ?? null, self::SLIDESHOW_DEFAULT_TITLE_X),
            'titleY' => self::normalise_slideshow_position_percent($config['titley'] ?? null, self::SLIDESHOW_DEFAULT_TITLE_Y),
            'bodyX' => self::normalise_slideshow_position_percent($config['bodyx'] ?? null, self::SLIDESHOW_DEFAULT_BODY_X),
            'bodyY' => self::normalise_slideshow_position_percent($config['bodyy'] ?? null, self::SLIDESHOW_DEFAULT_BODY_Y),
            'actionX' => self::normalise_slideshow_position_percent($config['actionx'] ?? null, self::SLIDESHOW_DEFAULT_ACTION_X),
            'actionY' => self::normalise_slideshow_position_percent($config['actiony'] ?? null, self::SLIDESHOW_DEFAULT_ACTION_Y),
            'labelX' => self::normalise_slideshow_position_percent($config['labelx'] ?? null, self::SLIDESHOW_DEFAULT_LABEL_X),
            'labelY' => $labely,
            'slides' => array_values($slides),
            'strings' => [
                'previous' => get_string('slideshowprevious', 'local_course_banner_builder'),
                'next' => get_string('slideshownext', 'local_course_banner_builder'),
                'view' => get_string('slideshowview', 'local_course_banner_builder'),
                'slide' => get_string('slideshowslide', 'local_course_banner_builder'),
            ],
        ];
    }

    /**
     * Return latest forum-post slides from one Moodle course.
     *
     * @param int $courseid
     * @param bool $issite
     * @param bool $assiteannouncements
     * @param int $siteannouncementdays
     * @return array
     */
    protected static function get_forum_slideshow_slides(
        int $courseid,
        bool $issite,
        bool $assiteannouncements = false,
        int $siteannouncementdays = self::SLIDESHOW_DEFAULT_SITE_ANNOUNCEMENT_DAYS
    ): array {
        global $DB, $USER;

        if (!$DB->get_manager()->table_exists('forum')) {
            return [];
        }

        $params = ['modname' => 'forum', 'courseid' => $courseid];
        $typesql = '';
        if ($assiteannouncements) {
            $typesql = ' AND f.type = :forumtype';
            $params['forumtype'] = 'news';
        }
        $freshnesssql = '';
        if ($issite || $assiteannouncements) {
            $siteannouncementdays = max(1, min(3650, $siteannouncementdays));
            $freshnesssql = ' AND p.created >= :siteannouncementcutoff';
            $params['siteannouncementcutoff'] = time() - ($siteannouncementdays * DAYSECS);
        }

        $records = $DB->get_records_sql("
            SELECT p.id AS postid,
                   p.subject,
                   p.message,
                   p.created,
                   d.id AS discussionid,
                   d.name AS discussionname,
                   f.name AS forumname,
                   f.course,
                   cm.id AS cmid
              FROM {forum_posts} p
              JOIN {forum_discussions} d ON d.id = p.discussion
              JOIN {forum} f ON f.id = d.forum
              JOIN {modules} m ON m.name = :modname
              JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = f.id
             WHERE f.course = :courseid
                   {$typesql}
                   {$freshnesssql}
          ORDER BY p.created DESC
        ", $params, 0, 16);
        if ($assiteannouncements && empty($records)) {
            unset($params['forumtype']);
            $records = $DB->get_records_sql("
                SELECT p.id AS postid,
                       p.subject,
                       p.message,
                       p.created,
                       d.id AS discussionid,
                       d.name AS discussionname,
                       f.name AS forumname,
                       f.course,
                       cm.id AS cmid
                  FROM {forum_posts} p
                  JOIN {forum_discussions} d ON d.id = p.discussion
                  JOIN {forum} f ON f.id = d.forum
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = f.id
                 WHERE f.course = :courseid
                       {$freshnesssql}
              ORDER BY p.created DESC
            ", $params, 0, 16);
        }

        $slides = [];
        $modinfo = get_fast_modinfo($courseid, $USER->id);
        foreach ($records as $record) {
            try {
                $cm = $modinfo->get_cm((int)$record->cmid);
            } catch (\moodle_exception $e) {
                continue;
            }
            $modulecontext = \context_module::instance((int)$record->cmid, IGNORE_MISSING);
            $canviewsiteannouncement = $assiteannouncements && $modulecontext &&
                has_capability('mod/forum:viewdiscussion', $modulecontext);
            if (!$cm->uservisible && !$canviewsiteannouncement) {
                continue;
            }

            $title = trim((string)$record->subject) !== '' ? (string)$record->subject : (string)$record->discussionname;
            $body = self::normalise_slideshow_text((string)$record->message, 180);
            $slides[] = [
                'type' => ($assiteannouncements || $issite)
                    ? self::SLIDESHOW_TYPE_SITEANNOUNCEMENTS
                    : self::SLIDESHOW_TYPE_FORUMS,
                'label' => ($assiteannouncements || $issite)
                    ? get_string('slideshow:type:siteannouncements', 'local_course_banner_builder')
                    : get_string('slideshow:type:courseforum', 'local_course_banner_builder'),
                'title' => format_string($title, true, ['context' => $modulecontext ?: \context_system::instance()]),
                'meta' => format_string((string)$record->forumname) . ' · ' . userdate((int)$record->created, get_string('strftimedatetimeshort', 'core_langconfig')),
                'body' => $body,
                'url' => (new \moodle_url('/mod/forum/discuss.php', ['d' => (int)$record->discussionid], 'p' . (int)$record->postid))->out(false),
                'time' => (int)$record->created,
            ];
            if (count($slides) >= 6) {
                break;
            }
        }

        return $slides;
    }

    /**
     * Return course ids where the current user behaves like a student.
     *
     * @return int[]
     */
    protected static function get_current_user_student_course_ids(): array {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/lib/enrollib.php');
        $courseids = [];
        foreach (enrol_get_users_courses($USER->id, true, 'id') as $course) {
            if (!self::current_user_has_student_role((int)$course->id)) {
                continue;
            }
            $courseids[] = (int)$course->id;
        }

        return $courseids;
    }

    /**
     * Whether the current user has a student role assignment in this course context chain.
     *
     * Site administrators are included when they are explicitly enrolled/assigned as students.
     *
     * @param int $courseid
     * @return bool
     */
    protected static function current_user_has_student_role(int $courseid): bool {
        global $USER;

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return false;
        }

        foreach (get_user_roles($context, $USER->id, true) as $role) {
            if (($role->archetype ?? '') === 'student' || ($role->shortname ?? '') === 'student') {
                return true;
            }
        }

        return false;
    }

    /**
     * Return upcoming assignment slides for the current user.
     *
     * @param int[] $courseids
     * @param int $currentcourseid
     * @return array
     */
    protected static function get_assignment_slideshow_slides(array $courseids, int $currentcourseid = 0): array {
        global $DB, $USER;

        $courseids = array_values(array_unique(array_filter(array_map('intval', $courseids))));
        if (empty($courseids) || !$DB->get_manager()->table_exists('assign')) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
        $params['modname'] = 'assign';
        $params['now'] = time();
        $params['userid'] = $USER->id;
        $records = $DB->get_records_sql("
            SELECT a.id,
                   a.name,
                   a.course,
                   a.duedate,
                   c.fullname AS coursename,
                   c.shortname AS courseshortname,
                   cm.id AS cmid
              FROM {assign} a
              JOIN {course} c ON c.id = a.course
              JOIN {modules} m ON m.name = :modname
              JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = a.id
             WHERE a.course {$insql}
               AND a.duedate > :now
               AND NOT EXISTS (
                   SELECT 1
                     FROM {assign_submission} s
                    WHERE s.assignment = a.id
                      AND s.userid = :userid
                      AND s.status = 'submitted'
               )
          ORDER BY a.duedate ASC
        ", $params, 0, 12);

        $slides = [];
        foreach ($records as $record) {
            if (!self::current_user_can_use_activity((int)$record->course, (int)$record->cmid, 'mod/assign:submit')) {
                continue;
            }
            $slides[] = [
                'type' => self::SLIDESHOW_TYPE_ASSIGNMENTS,
                'label' => get_string('slideshow:type:assignment', 'local_course_banner_builder'),
                'secondaryLabel' => (int)$record->course !== $currentcourseid
                    ? format_string((string)$record->courseshortname)
                    : '',
                'title' => format_string((string)$record->name, true, ['context' => \context_module::instance((int)$record->cmid)]),
                'meta' => format_string((string)$record->coursename) . ' · ' .
                    get_string('slideshowdue', 'local_course_banner_builder', userdate((int)$record->duedate, get_string('strftimedatetimeshort', 'core_langconfig'))),
                'body' => get_string('slideshowassignmentbody', 'local_course_banner_builder'),
                'url' => (new \moodle_url('/mod/assign/view.php', ['id' => (int)$record->cmid]))->out(false),
                'time' => (int)$record->duedate,
            ];
            if (count($slides) >= 6) {
                break;
            }
        }

        return $slides;
    }

    /**
     * Return upcoming quiz slides for the current user.
     *
     * @param int[] $courseids
     * @param int $currentcourseid
     * @return array
     */
    protected static function get_quiz_slideshow_slides(array $courseids, int $currentcourseid = 0): array {
        global $DB, $USER;

        $courseids = array_values(array_unique(array_filter(array_map('intval', $courseids))));
        if (empty($courseids) || !$DB->get_manager()->table_exists('quiz')) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'courseid');
        $params['modname'] = 'quiz';
        $params['now'] = time();
        $params['userid'] = $USER->id;
        $records = $DB->get_records_sql("
            SELECT q.id,
                   q.name,
                   q.course,
                   q.timeclose,
                   c.fullname AS coursename,
                   c.shortname AS courseshortname,
                   cm.id AS cmid
              FROM {quiz} q
              JOIN {course} c ON c.id = q.course
              JOIN {modules} m ON m.name = :modname
              JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = q.id
             WHERE q.course {$insql}
               AND q.timeclose > :now
               AND NOT EXISTS (
                   SELECT 1
                     FROM {quiz_attempts} qa
                    WHERE qa.quiz = q.id
                      AND qa.userid = :userid
                      AND qa.state = 'finished'
               )
          ORDER BY q.timeclose ASC
        ", $params, 0, 12);

        $slides = [];
        foreach ($records as $record) {
            if (!self::current_user_can_use_activity((int)$record->course, (int)$record->cmid, 'mod/quiz:attempt')) {
                continue;
            }
            $slides[] = [
                'type' => self::SLIDESHOW_TYPE_QUIZZES,
                'label' => get_string('slideshow:type:quiz', 'local_course_banner_builder'),
                'secondaryLabel' => (int)$record->course !== $currentcourseid
                    ? format_string((string)$record->courseshortname)
                    : '',
                'title' => format_string((string)$record->name, true, ['context' => \context_module::instance((int)$record->cmid)]),
                'meta' => format_string((string)$record->coursename) . ' · ' .
                    get_string('slideshowdue', 'local_course_banner_builder', userdate((int)$record->timeclose, get_string('strftimedatetimeshort', 'core_langconfig'))),
                'body' => get_string('slideshowquizbody', 'local_course_banner_builder'),
                'url' => (new \moodle_url('/mod/quiz/view.php', ['id' => (int)$record->cmid]))->out(false),
                'time' => (int)$record->timeclose,
            ];
            if (count($slides) >= 6) {
                break;
            }
        }

        return $slides;
    }

    /**
     * Whether the current user may use an activity as a student-like participant.
     *
     * @param int $courseid
     * @param int $cmid
     * @param string $capability
     * @return bool
     */
    protected static function current_user_can_use_activity(int $courseid, int $cmid, string $capability): bool {
        global $USER;

        try {
            $modinfo = get_fast_modinfo($courseid, $USER->id);
            $cm = $modinfo->get_cm($cmid);
        } catch (\moodle_exception $e) {
            return false;
        }
        if (!$cm->uservisible) {
            return false;
        }

        if (!self::current_user_has_student_role($courseid)) {
            return false;
        }

        $modulecontext = \context_module::instance($cmid, IGNORE_MISSING);
        return $modulecontext && has_capability($capability, $modulecontext);
    }

    /**
     * Clean and shorten text for slideshow display.
     *
     * @param string $text
     * @param int $limit
     * @return string
     */
    protected static function normalise_slideshow_text(string $text, int $limit): string {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        return shorten_text($text, $limit);
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
        if ($sourcekey === self::SITE_SOURCE_KEY) {
            return self::get_site_source();
        }

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
                $currentkey = self::get_customfield_source_key($fieldid, (string)$rawvalue);
                $legacykey = self::get_legacy_customfield_source_key($fieldid, (string)$rawvalue);
                if ($currentkey !== $sourcekey && $legacykey !== $sourcekey) {
                    continue;
                }
                return (object)[
                    'type' => self::SOURCE_TYPE_CUSTOMFIELD,
                    'sourcekey' => $sourcekey,
                    'categoryid' => null,
                    'customfieldid' => $fieldid,
                    'customfieldvalue' => self::normalise_customfield_value((string)$rawvalue),
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
        if (self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            $record = $DB->get_record('local_course_banner_builder_order', ['sourcekey' => $sourcekey], '*', IGNORE_MISSING);
        }
        if (!$record && self::table_field_exists('local_course_banner_builder_elements', 'sourcekey')) {
            $record = $DB->get_record('local_course_banner_builder_elements', ['sourcekey' => $sourcekey], '*', IGNORE_MISSING);
        }
        if (!$record) {
            return null;
        }

        $fieldid = (int)($record->customfieldid ?? $record->coursecustomfieldid ?? 0);
        if (empty($fieldid)) {
            return null;
        }

        $field = $DB->get_record('customfield_field', ['id' => $fieldid], 'id,name,type,configdata', IGNORE_MISSING);
        $rawvalue = (string)($record->customfieldvalue ?? '');
        $label = $field ? format_string($field->name) . ': ' . self::get_customfield_value_label($field, $rawvalue) : $rawvalue;
        return (object)[
            'type' => self::SOURCE_TYPE_CUSTOMFIELD,
            'sourcekey' => $sourcekey,
            'categoryid' => null,
            'customfieldid' => $fieldid,
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
            self::CUSTOMFIELD_PRIORITY_PREPEND => get_string('customfieldpriority:prepend', 'local_course_banner_builder'),
            self::CUSTOMFIELD_PRIORITY_APPEND => get_string('customfieldpriority:append', 'local_course_banner_builder'),
            self::CUSTOMFIELD_PRIORITY_CUSTOMFIELD => get_string('customfieldpriority:customfield', 'local_course_banner_builder'),
            self::CUSTOMFIELD_PRIORITY_CATEGORY => get_string('customfieldpriority:category', 'local_course_banner_builder'),
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
     * @param bool $includecustom
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
     * Overlay target options.
     *
     * @return array
     */
    public static function get_overlay_target_options(): array {
        return [
            self::OVERLAY_TARGET_BANNER => get_string('overlaytarget:banner', 'local_course_banner_builder'),
            self::OVERLAY_TARGET_SLIDESHOW => get_string('overlaytarget:slideshow', 'local_course_banner_builder'),
            self::OVERLAY_TARGET_BOTH => get_string('overlaytarget:both', 'local_course_banner_builder'),
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
     * Convert a border width for generated course-card thumbnails.
     *
     * Course-card borders are decorative thumbnails, so the authored percent is
     * scaled down and capped against the thumbnail's shortest side. This keeps a
     * visible frame without allowing an admin banner border to swallow the card.
     *
     * @param float $percent
     * @param int $width
     * @param int $height
     * @return int
     */
    protected static function get_course_card_border_width_pixels(float $percent, int $width, int $height): int {
        $percent = self::normalise_border_width_percent($percent);
        if ($percent <= 0 || $width <= 0 || $height <= 0) {
            return 0;
        }

        $reference = max(1, min($width, $height));
        $thumbnailpercent = min(8.0, $percent * 0.35);
        return max(1, (int)round($reference * $thumbnailpercent / 100));
    }

    /**
     * Format a border width as a CSS length relative to the rendered banner height.
     *
     * @param float $percent
     * @return string
     */
    protected static function format_border_height_relative_length(float $percent): string {
        $percent = self::normalise_border_width_percent($percent);
        if ($percent <= 0) {
            return '0px';
        }

        return rtrim(rtrim(sprintf('%.6F', $percent), '0'), '.') . 'cqh';
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
        $postedgroup = optional_param_array('bordersidesgroup', [], PARAM_RAW);
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

        $postedvalue = optional_param('bordersidesvalue', null, PARAM_RAW_TRIMMED);
        if ($postedvalue !== null && $postedvalue !== '') {
            return self::normalise_border_sides(array_filter(array_map('trim', explode(',', (string)$postedvalue))));
        }
        if ($postedvalue !== null) {
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

        if (self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            $record = $DB->get_record('local_course_banner_builder_order', ['sourcekey' => self::get_category_source_key($categoryid)]);
        } else {
            $record = $DB->get_record('local_course_banner_builder_order', ['categoryid' => $categoryid]);
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
        if (self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            $record = $DB->get_record('local_course_banner_builder_order', ['sourcekey' => $source->sourcekey], '*', IGNORE_MISSING);
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
        if (!self::table_field_exists('local_course_banner_builder_order', 'sourcetype')) {
            unset($record->sourcetype);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            unset($record->sourcekey);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'customfieldvalue')) {
            unset($record->customfieldvalue);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'customfieldpriority')) {
            unset($record->customfieldpriority);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'compositionmode')) {
            unset($record->compositionmode);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'fitmode')) {
            unset($record->fitmode);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'fitapplyscope')) {
            unset($record->fitapplyscope);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'sourceparentkey')) {
            unset($record->sourceparentkey);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'sourceisroot')) {
            unset($record->sourceisroot);
        }
        if (!self::table_field_exists('local_course_banner_builder_order', 'sourceinheritchildren')) {
            unset($record->sourceinheritchildren);
        }

        $record->id = $DB->insert_record('local_course_banner_builder_order', $record);
        return self::normalise_source_settings($record, $source);
    }

    /**
     * Save category settings.
     *
     * @param int $categoryid
     * @param string $compositionmode
     * @param string $fitmode
     * @param string $fitapplyscope
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
     * @param string $sourceparentkey
     * @param bool $sourceisroot
     * @param bool $sourceinheritchildren
     * @param bool $sync
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
        bool $sourceinheritchildren = false,
        bool $sync = true
    ): void {
        global $DB;

        $record = self::get_or_create_source_settings($source);
        $modes = array_keys(self::get_composition_mode_options());
        if (!in_array($compositionmode, $modes, true)) {
            $compositionmode = self::MODE_CUMULATIVE;
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

        if (self::table_field_exists('local_course_banner_builder_order', 'compositionmode')) {
            $record->compositionmode = $compositionmode;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'fitmode')) {
            $record->fitmode = $fitmode;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'fitapplyscope')) {
            $record->fitapplyscope = $fitapplyscope;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'sourcetype')) {
            $record->sourcetype = $source->type;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            $record->sourcekey = $source->sourcekey;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'customfieldvalue')) {
            $record->customfieldvalue = $source->customfieldvalue ?? null;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'customfieldpriority')) {
            $record->customfieldpriority = $customfieldpriority;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'sourceparentkey')) {
            $record->sourceparentkey = $sourceparentkey;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'sourceisroot')) {
            $record->sourceisroot = $sourceisroot ? 1 : 0;
        }
        if (self::table_field_exists('local_course_banner_builder_order', 'sourceinheritchildren')) {
            $record->sourceinheritchildren = 0;
        }
        $record->timemodified = time();
        $DB->update_record('local_course_banner_builder_order', $record);

        if ($sync) {
            self::sync_courses_for_source($source);
        }
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
        $compositionmode = $settings->compositionmode ?? self::MODE_CUMULATIVE;
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

        $record = $DB->get_record('local_course_banner_builder_elements', ['id' => $elementid]);
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
        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcekey')) {
            $params['sourcekey'] = $source->sourcekey;
        } else {
            $params['categoryid'] = (int)($source->categoryid ?? 0);
        }
        if ($enabledonly) {
            $params['isenabled'] = 1;
        }

        $records = $DB->get_records('local_course_banner_builder_elements', $params, 'sortorder ASC, id ASC');
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
        if (self::table_field_exists('local_course_banner_builder_elements', 'fitmodeoverride')) {
            $record->fitmodeoverride = null;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'positionanchor')) {
            $record->positionanchor = self::POSITION_CENTER;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'offsettoppercent')) {
            $record->offsettoppercent = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'offsetrightpercent')) {
            $record->offsetrightpercent = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'offsetbottompercent')) {
            $record->offsetbottompercent = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'offsetleftpercent')) {
            $record->offsetleftpercent = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'customwidthpercent')) {
            $record->customwidthpercent = 100;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'customheightpercent')) {
            $record->customheightpercent = 100;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'customsizekeepaspect')) {
            $record->customsizekeepaspect = 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'dynamicimagesizeenabled')) {
            $record->dynamicimagesizeenabled = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imagecenterfixed')) {
            $record->imagecenterfixed = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveoverlayenabled')) {
            $record->imageaboveoverlayenabled = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imagebelowinheritedenabled')) {
            $record->imagebelowinheritedenabled = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveinheritedenabled')) {
            $record->imageaboveinheritedenabled = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imageopacity')) {
            $record->imageopacity = 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imagecropenabled')) {
            $record->imagecropenabled = 0;
            $record->imagecropleftpercent = 0;
            $record->imagecroptoppercent = 0;
            $record->imagecropwidthpercent = 100;
            $record->imagecropheightpercent = 100;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderenabled')) {
            $record->borderenabled = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'bordercolor')) {
            $record->bordercolor = '#56B9C0';
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderwidth')) {
            $record->borderwidth = 2.5;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderopacity')) {
            $record->borderopacity = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderfade')) {
            $record->borderfade = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderstyle')) {
            $record->borderstyle = self::BORDER_STYLE_SOLID;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderdashlength')) {
            $record->borderdashlength = 24;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'bordersides')) {
            $record->bordersides = 'top,right,bottom,left';
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderinnerrounded')) {
            $record->borderinnerrounded = 0;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'overlayenabled')) {
            $record->overlayenabled = 0;
            $record->overlaytarget = self::OVERLAY_TARGET_BOTH;
            $record->overlaybannercolor = '#000000';
            $record->overlaybanneropacity = 25;
            $record->overlayslideshowcolor = '#000000';
            $record->overlayslideshowopacity = 38;
            $record->overlaytitleabove = 1;
            $record->overlayborderabove = 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcetype')) {
            $record->sourcetype = $source->type;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcekey')) {
            $record->sourcekey = $source->sourcekey;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'customfieldvalue')) {
            $record->customfieldvalue = $source->customfieldvalue ?? null;
        }
        $record->id = $DB->insert_record('local_course_banner_builder_elements', $record);
        $record->fileitemid = $record->id;
        $DB->update_record('local_course_banner_builder_elements', $record);

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

        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcekey')) {
            $max = $DB->get_field_sql(
                'SELECT MAX(sortorder) FROM {local_course_banner_builder_elements} WHERE sourcekey = :sourcekey',
                ['sourcekey' => $source->sourcekey]
            );
        } else {
            $max = $DB->get_field_sql(
                'SELECT MAX(sortorder) FROM {local_course_banner_builder_elements} WHERE categoryid = :categoryid',
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
            $DB->update_record('local_course_banner_builder_elements', $record);
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
            $DB->update_record('local_course_banner_builder_elements', $record);
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
     * Build an automatic overlay layer name for one source.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return string
     */
    protected static function get_automatic_overlay_name(\stdClass $source, int $excludeid = 0): string {
        $count = 0;
        foreach (self::get_source_elements($source) as $element) {
            if ($excludeid > 0 && (int)$element->id === $excludeid) {
                continue;
            }
            if (!empty($element->overlayenabled)) {
                $count++;
            }
        }
        $sourcelabel = trim((string)($source->label ?? $source->sourcekey ?? 'Source'));
        return $sourcelabel . ' Overlay ' . ($count + 1);
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
     * Detect whether a source already contains another overlay layer.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return bool
     */
    public static function source_has_overlay_layer(\stdClass $source, int $excludeid = 0): bool {
        return self::get_source_overlay_layer_record($source, $excludeid) !== null;
    }

    /**
     * Get the first border layer stored directly in one source.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @param bool $enabledonly
     * @return \stdClass|null
     */
    protected static function get_source_border_layer_record(
        \stdClass $source,
        int $excludeid = 0,
        bool $enabledonly = false
    ): ?\stdClass {
        foreach (self::get_source_elements($source) as $element) {
            if ($excludeid > 0 && (int)$element->id === $excludeid) {
                continue;
            }
            if ($enabledonly && empty($element->isenabled)) {
                continue;
            }
            if (!empty($element->borderenabled)) {
                return $element;
            }
        }
        return null;
    }

    /**
     * Get the first overlay layer stored directly in one source.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @param bool $enabledonly
     * @return \stdClass|null
     */
    protected static function get_source_overlay_layer_record(
        \stdClass $source,
        int $excludeid = 0,
        bool $enabledonly = false
    ): ?\stdClass {
        foreach (self::get_source_elements($source) as $element) {
            if ($excludeid > 0 && (int)$element->id === $excludeid) {
                continue;
            }
            if ($enabledonly && empty($element->isenabled)) {
                continue;
            }
            if (!empty($element->overlayenabled)) {
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
        $sourcevalue = self::normalise_customfield_value((string)($source->customfieldvalue ?? ''));
        foreach ($datarecords as $datarecord) {
            if (self::extract_customfield_data_value($field, $datarecord) !== $sourcevalue) {
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
            $borderrecord = self::get_source_border_layer_record($chainsource, $excludeid, true);
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
     * Find an overlay layer in the inherited source chain.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return array|null
     */
    public static function get_source_chain_overlay_layer(\stdClass $source, int $excludeid = 0): ?array {
        foreach (self::get_explicit_source_parent_chain($source, false) as $chainsource) {
            $overlayrecord = self::get_source_overlay_layer_record($chainsource, $excludeid);
            if ($overlayrecord) {
                return [
                    'source' => $chainsource,
                    'record' => $overlayrecord,
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
     * Detect whether a source chain already contains an overlay layer.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return bool
     */
    public static function source_chain_has_overlay_layer(\stdClass $source, int $excludeid = 0): bool {
        return self::get_source_chain_overlay_layer($source, $excludeid) !== null;
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
     * Return overlay conflict state for one source.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return array
     */
    public static function get_source_overlay_conflict_state(\stdClass $source, int $excludeid = 0): array {
        $insource = self::source_has_overlay_layer($source, $excludeid);
        $inchain = !$insource && self::source_chain_has_overlay_layer($source, $excludeid);

        return [
            'blocked' => $insource || $inchain,
            'isinchain' => $inchain,
            'messagekey' => $inchain ? 'sourcechainalreadyhasoverlay' : 'sourcealreadyhasoverlay',
            'inlinekey' => $inchain ? 'sourcechainalreadyhasoverlayinline' : 'sourcealreadyhasoverlayinline',
        ];
    }

    /**
     * Return descendants that explicitly inherit from a source.
     *
     * @param \stdClass $source
     * @return \stdClass[]
     */
    protected static function get_explicit_source_descendants(\stdClass $source): array {
        global $DB;

        if (empty($source->sourcekey) || !self::table_field_exists('local_course_banner_builder_order', 'sourceparentkey') ||
                !self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            return [];
        }

        $descendants = [];
        $queue = [(string)$source->sourcekey];
        $visited = [(string)$source->sourcekey => true];

        while (!empty($queue)) {
            $parentkey = array_shift($queue);
            $records = $DB->get_records(
                'local_course_banner_builder_order',
                ['sourceparentkey' => $parentkey],
                '',
                'id,sourcekey,sourceparentkey'
            );

            foreach ($records as $record) {
                $childkey = trim((string)($record->sourcekey ?? ''));
                if ($childkey === '' || !empty($visited[$childkey])) {
                    continue;
                }

                $visited[$childkey] = true;
                $queue[] = $childkey;
                $childsource = self::resolve_source($childkey);
                if ($childsource) {
                    $descendants[] = $childsource;
                }
            }
        }

        return $descendants;
    }

    /**
     * Disable active child-source borders once a parent source owns the chain border.
     *
     * @param \stdClass $source
     * @return int
     */
    protected static function disable_child_source_border_layers(\stdClass $source): int {
        global $DB;

        $disabledcount = 0;
        foreach (self::get_explicit_source_descendants($source) as $childsource) {
            foreach (self::get_source_elements($childsource) as $element) {
                if (empty($element->borderenabled) || empty($element->isenabled)) {
                    continue;
                }
                $element->isenabled = 0;
                $element->timemodified = time();
                $DB->update_record('local_course_banner_builder_elements', $element);
                $disabledcount++;
            }
        }

        return $disabledcount;
    }

    /**
     * Disable active child-source overlays once a parent source owns the chain overlay.
     *
     * @param \stdClass $source
     * @return int
     */
    protected static function disable_child_source_overlay_layers(\stdClass $source): int {
        global $DB;

        $disabledcount = 0;
        foreach (self::get_explicit_source_descendants($source) as $childsource) {
            foreach (self::get_source_elements($childsource) as $element) {
                if (empty($element->overlayenabled) || empty($element->isenabled)) {
                    continue;
                }
                $element->isenabled = 0;
                $element->timemodified = time();
                $DB->update_record('local_course_banner_builder_elements', $element);
                $disabledcount++;
            }
        }

        return $disabledcount;
    }

    /**
     * Count active child-source borders that would be disabled by a parent border.
     *
     * @param \stdClass $source
     * @return int
     */
    public static function count_active_child_source_border_layers(\stdClass $source): int {
        $count = 0;
        foreach (self::get_explicit_source_descendants($source) as $childsource) {
            foreach (self::get_source_elements($childsource) as $element) {
                if (!empty($element->borderenabled) && !empty($element->isenabled)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Count active child-source overlays that would be disabled by a parent overlay.
     *
     * @param \stdClass $source
     * @return int
     */
    public static function count_active_child_source_overlay_layers(\stdClass $source): int {
        $count = 0;
        foreach (self::get_explicit_source_descendants($source) as $childsource) {
            foreach (self::get_source_elements($childsource) as $element) {
                if (!empty($element->overlayenabled) && !empty($element->isenabled)) {
                    $count++;
                }
            }
        }

        return $count;
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
     * Whether one record is an overlay-only layer.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function is_overlay_only_layer(\stdClass $record): bool {
        return !empty($record->overlayenabled);
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
        return !self::is_border_only_layer($record) &&
            !self::is_overlay_only_layer($record) &&
            !empty($record->dynamicimagesizeenabled);
    }

    /**
     * Whether one image layer should be rendered above the active overlay.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function is_overlay_top_image_layer(\stdClass $record): bool {
        return !self::is_border_only_layer($record) &&
            !self::is_overlay_only_layer($record) &&
            !empty($record->imageaboveoverlayenabled);
    }

    /**
     * Whether one image layer should be rendered below inherited layers.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function is_below_inherited_image_layer(\stdClass $record): bool {
        return !self::is_border_only_layer($record) &&
            !self::is_overlay_only_layer($record) &&
            !empty($record->imagebelowinheritedenabled);
    }

    /**
     * Whether one image layer should be rendered above inherited image layers.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function is_above_inherited_image_layer(\stdClass $record): bool {
        return !self::is_border_only_layer($record) &&
            !self::is_overlay_only_layer($record) &&
            !empty($record->imageaboveinheritedenabled);
    }

    /**
     * Return the special render priority for one layer.
     *
     * @param \stdClass $record
     * @return int
     */
    protected static function get_layer_priority(\stdClass $record): int {
        if (self::is_below_inherited_image_layer($record)) {
            return -1;
        }
        if (self::is_overlay_top_image_layer($record)) {
            return 4;
        }
        if (self::is_overlay_only_layer($record)) {
            return empty($record->overlayborderabove) ? 3 : 1;
        }
        if (self::is_border_only_layer($record)) {
            return 2;
        }
        if (self::is_top_image_layer($record)) {
            return 4;
        }
        if (self::is_above_inherited_image_layer($record)) {
            return 1;
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
        return self::is_border_only_layer($record) ||
            self::is_overlay_only_layer($record) ||
            self::is_top_image_layer($record) ||
            self::is_overlay_top_image_layer($record) ||
            self::is_below_inherited_image_layer($record) ||
            self::is_above_inherited_image_layer($record);
    }

    /**
     * Convert "above inherited" image layers to the established above-border rule.
     *
     * @param \stdClass $source
     * @param int $excludeid
     * @return void
     */
    protected static function convert_above_inherited_layers_to_above_border(\stdClass $source, int $excludeid = 0): void {
        global $DB;

        if (!self::table_field_exists('local_course_banner_builder_elements', 'imageaboveinheritedenabled') ||
                !self::table_field_exists('local_course_banner_builder_elements', 'dynamicimagesizeenabled')) {
            return;
        }

        foreach (self::get_source_elements($source) as $record) {
            if ((int)$record->id === $excludeid || empty($record->imageaboveinheritedenabled) ||
                    !self::get_banner_image_file($record)) {
                continue;
            }
            $record->imageaboveinheritedenabled = 0;
            $record->dynamicimagesizeenabled = 1;
            $record->timemodified = time();
            $DB->update_record('local_course_banner_builder_elements', $record);
        }
    }

    /**
     * Return a stable z-index for preview rendering.
     *
     * @param \stdClass $record
     * @return int
     */
    protected static function get_preview_layer_zindex(\stdClass $record): int {
        $priority = self::get_layer_priority($record);
        if ($priority < 0) {
            return $priority * 1000;
        }
        return ($priority * 1000) + (int)($record->sortorder ?? 0) + 1;
    }

    /**
     * Return the active overlay record from one layer-spec collection.
     *
     * @param array $layerspecs
     * @return \stdClass|null
     */
    protected static function get_active_overlay_record_from_layer_specs(array $layerspecs): ?\stdClass {
        foreach ($layerspecs as $layerspec) {
            $record = $layerspec['record'] ?? null;
            if ($record instanceof \stdClass && self::record_overlay_targets_banner($record)) {
                return $record;
            }
        }
        return null;
    }

    /**
     * Return the exact draw-order band for one rendered layer.
     *
     * @param \stdClass $record
     * @param \stdClass|null $overlayrecord
     * @return int
     */
    protected static function get_layer_draw_band(\stdClass $record, ?\stdClass $overlayrecord = null): int {
        if (self::is_overlay_only_layer($record)) {
            return empty($record->overlayborderabove) ? 3000 : 1000;
        }
        if (self::is_border_only_layer($record)) {
            return 2000;
        }

        $overlayisaboveborder = $overlayrecord && empty($overlayrecord->overlayborderabove);
        $aboveborder = !empty($record->dynamicimagesizeenabled);
        $aboveoverlay = !empty($record->imageaboveoverlayenabled) && $overlayrecord !== null;
        if (!empty($record->imagebelowinheritedenabled)) {
            return -1000;
        }

        if ($aboveoverlay) {
            if ($overlayisaboveborder) {
                return 4000;
            }
            return $aboveborder ? 2500 : 1500;
        }
        if ($aboveborder) {
            return 2500;
        }
        if (!empty($record->imageaboveinheritedenabled)) {
            return 900;
        }
        return 0;
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

        self::apply_request_crop_state($data);
        $elementid = (int)($data->elementid ?? 0);
        $rawdraftfiles = array_values(self::get_draft_files((int)($data->bannerimage_filemanager ?? 0)));
        $multidraftsettings = self::extract_multi_draft_settings($data->multilayerdraftsettings ?? '');
        $draftfiles = [];
        foreach ($rawdraftfiles as $draftindex => $draftfile) {
            if (!empty($multidraftsettings[$draftindex]['deleted'])) {
                $deletedfilename = (string)($multidraftsettings[$draftindex]['deletedfilename'] ?? '');
                if ($deletedfilename !== '' && $deletedfilename !== $draftfile->get_filename()) {
                    $draftfiles[$draftindex] = $draftfile;
                    continue;
                }
                continue;
            }
            $draftfiles[$draftindex] = $draftfile;
        }
        if ($elementid && !empty($data->currentisborderlayer)) {
            $data->borderenabled = 1;
        }
        if ($elementid && !empty($data->currentisoverlaylayer)) {
            $data->overlayenabled = 1;
        }
        $hasborder = !empty($data->borderenabled) && !empty($data->isenabled);
        $hasoverlay = !empty($data->overlayenabled) && !empty($data->isenabled);
        self::debug_log('save_source_banner_input', [
            'sourcekey' => $source->sourcekey ?? '',
            'elementid' => $elementid,
            'hasborder' => $hasborder ? 1 : 0,
            'hasoverlay' => $hasoverlay ? 1 : 0,
            'bordersidesvalue_data' => $data->bordersidesvalue ?? null,
            'bordersidesgroup_data' => $data->bordersidesgroup ?? null,
            'post_bordersidesvalue' => optional_param('bordersidesvalue', null, PARAM_RAW_TRIMMED),
            'post_bordersidesgroup' => optional_param_array('bordersidesgroup', [], PARAM_RAW),
        ]);
        $borderconflict = self::get_source_border_conflict_state($source, $elementid);
        if ($hasborder && !empty($borderconflict['blocked'])) {
            throw new \moodle_exception((string)$borderconflict['messagekey'], 'local_course_banner_builder');
        }
        $overlayconflict = self::get_source_overlay_conflict_state($source, $elementid);
        if ($hasoverlay && !empty($overlayconflict['blocked'])) {
            throw new \moodle_exception((string)$overlayconflict['messagekey'], 'local_course_banner_builder');
        }
        if (!$elementid && !$hasborder && !$hasoverlay && empty($draftfiles)) {
            throw new \moodle_exception('layercontentrequired', 'local_course_banner_builder');
        }

        if (!$elementid && !$hasoverlay && count($draftfiles) > 1) {
            $createdids = [];
            $nextsortorder = self::get_next_sortorder_for_source($source);
            foreach ($draftfiles as $draftindex => $draftfile) {
                $record = self::create_source_element($source);
                $record->name = self::get_automatic_layer_name($draftfile);
                $record->sortorder = $nextsortorder++;
                $record->isenabled = empty($data->isenabled) ? 0 : 1;
                $layerdata = self::apply_multi_draft_layer_settings($data, $multidraftsettings[$draftindex] ?? []);
                self::apply_element_display_settings($record, $layerdata, $source);
                $record->timemodified = time();
                $DB->update_record('local_course_banner_builder_elements', $record);
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
        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcetype')) {
            $record->sourcetype = $source->type;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcekey')) {
            $record->sourcekey = $source->sourcekey;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'customfieldvalue')) {
            $record->customfieldvalue = $source->customfieldvalue ?? null;
        }
        $record->name = trim((string)($data->name ?? ''));
        if ($record->name === '' && !empty($draftfiles)) {
            $record->name = self::get_automatic_layer_name(reset($draftfiles));
        } else if ($record->name === '' && $hasborder) {
            $record->name = self::get_automatic_border_name($source, $elementid);
        } else if ($record->name === '' && $hasoverlay) {
            $record->name = self::get_automatic_overlay_name($source, $elementid);
        }
        $record->sortorder = ($hasborder || $hasoverlay)
            ? self::get_next_sortorder_for_source($source)
            : max(0, (int)($data->sortorder ?? 0));
        if (!$elementid && !$hasborder && !$hasoverlay) {
            self::make_source_sortorder_room($source, $record->sortorder);
        }
        $record->isenabled = empty($data->isenabled) ? 0 : 1;
        $displaydata = $data;
        if (!$elementid && !$hasborder && count($draftfiles) === 1) {
            $onlydraftindex = array_key_first($draftfiles);
            $draftsettings = $multidraftsettings[$onlydraftindex] ?? [];
            foreach (['enabled', 'leftpercent', 'toppercent', 'widthpercent', 'heightpercent'] as $cropfield) {
                $property = 'imagecrop' . $cropfield;
                if (property_exists($data, $property)) {
                    $draftsettings[$property] = $data->{$property};
                }
            }
            $displaydata = self::apply_multi_draft_layer_settings($data, $draftsettings);
        }
        self::apply_element_display_settings($record, $displaydata, $source);
        $record->timemodified = time();
        $DB->update_record('local_course_banner_builder_elements', $record);
        if ($hasborder && !empty($record->isenabled)) {
            $data->disabledchildborderlayers = self::disable_child_source_border_layers($source);
            self::convert_above_inherited_layers_to_above_border($source, (int)$record->id);
        }
        if ($hasoverlay && !empty($record->isenabled)) {
            $data->disabledchildoverlaylayers = self::disable_child_source_overlay_layers($source);
        }

        if (isset($data->bannerimage_filemanager)) {
            if (!$elementid && !$hasborder && !$hasoverlay && count($draftfiles) === 1) {
                self::copy_draft_file_to_element($record, reset($draftfiles));
            } else if (!$hasoverlay) {
                file_save_draft_area_files(
                    $data->bannerimage_filemanager,
                    $context->id,
                    'local_course_banner_builder',
                    self::FILEAREA,
                    $record->fileitemid,
                    self::get_filemanager_options()
                );
            }
        }

        self::normalize_element_sortorders(self::get_source_elements($source));
        self::sync_courses_for_source($source);
        return (int)$record->id;
    }

    /**
     * Merge the live preview crop state submitted by the modal into form data.
     *
     * @param \stdClass $data
     * @return void
     */
    protected static function apply_request_crop_state(\stdClass $data): void {
        $cropstate = [];
        $rawstate = optional_param('previewcropstate', '', PARAM_RAW_TRIMMED);
        if ($rawstate !== '') {
            $decoded = json_decode($rawstate, true);
            if (is_array($decoded)) {
                $cropstate = $decoded;
            }
        }

        foreach (['enabled', 'leftpercent', 'toppercent', 'widthpercent', 'heightpercent'] as $cropfield) {
            $property = 'imagecrop' . $cropfield;
            if (array_key_exists($property, $cropstate)) {
                $data->{$property} = $cropfield === 'enabled' ? (empty($cropstate[$property]) ? 0 : 1) :
                    (float)$cropstate[$property];
                continue;
            }
            if (optional_param($property, null, PARAM_RAW) === null) {
                continue;
            }
            $default = $cropfield === 'widthpercent' || $cropfield === 'heightpercent' ? 100.0 : 0.0;
            $data->{$property} = $cropfield === 'enabled' ? optional_param($property, 0, PARAM_BOOL) :
                optional_param($property, $default, PARAM_FLOAT);
        }
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
        if (array_key_exists('imagecenterfixed', $settings)) {
            $layerdata->imagecenterfixed = empty($settings['imagecenterfixed']) ? 0 : 1;
        }
        if (array_key_exists('imageaboveoverlayenabled', $settings)) {
            $layerdata->imageaboveoverlayenabled = empty($settings['imageaboveoverlayenabled']) ? 0 : 1;
        }
        if (array_key_exists('imagebelowinheritedenabled', $settings)) {
            $layerdata->imagebelowinheritedenabled = empty($settings['imagebelowinheritedenabled']) ? 0 : 1;
        }
        if (array_key_exists('imageaboveinheritedenabled', $settings)) {
            $layerdata->imageaboveinheritedenabled = empty($settings['imageaboveinheritedenabled']) ? 0 : 1;
        }
        if (array_key_exists('imageopacity', $settings)) {
            $layerdata->imageopacity = (float)$settings['imageopacity'];
        }
        foreach (['enabled', 'leftpercent', 'toppercent', 'widthpercent', 'heightpercent'] as $cropfield) {
            $property = 'imagecrop' . $cropfield;
            if (array_key_exists($property, $settings)) {
                $layerdata->{$property} = $cropfield === 'enabled' ? (empty($settings[$property]) ? 0 : 1) :
                    (float)$settings[$property];
            }
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

        if (self::table_field_exists('local_course_banner_builder_elements', 'fitmodeoverride') && property_exists($data, 'fitmodeoverride')) {
            $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
        }

        if (self::table_field_exists('local_course_banner_builder_elements', 'positionanchor') && property_exists($data, 'positionanchor')) {
            $record->positionanchor = self::normalise_position_anchor((string)($data->positionanchor ?? self::POSITION_CENTER));
        }

        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $property = 'offset' . $side . 'percent';
            if (self::table_field_exists('local_course_banner_builder_elements', $property) && property_exists($data, $property)) {
                $record->{$property} = self::normalise_percentage((float)($data->{$property} ?? 0), -1000.0, 1000.0);
            }
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'customwidthpercent') &&
                property_exists($data, 'customwidthpercent')) {
            $record->customwidthpercent = self::normalise_percentage(
                (float)($data->customwidthpercent ?? 100),
                0.0,
                self::CUSTOM_SIZE_PERCENT_MAX
            );
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'customheightpercent') &&
                property_exists($data, 'customheightpercent')) {
            $record->customheightpercent = self::normalise_percentage(
                (float)($data->customheightpercent ?? 100),
                0.0,
                self::CUSTOM_SIZE_PERCENT_MAX
            );
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'customsizekeepaspect') &&
                property_exists($data, 'customsizekeepaspect')) {
            $record->customsizekeepaspect = empty($data->customsizekeepaspect) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'dynamicimagesizeenabled') &&
                property_exists($data, 'dynamicimagesizeenabled')) {
            $record->dynamicimagesizeenabled = empty($data->dynamicimagesizeenabled) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imagecenterfixed') &&
                property_exists($data, 'imagecenterfixed')) {
            $record->imagecenterfixed = empty($data->imagecenterfixed) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveoverlayenabled') &&
                property_exists($data, 'imageaboveoverlayenabled')) {
            $record->imageaboveoverlayenabled = empty($data->imageaboveoverlayenabled) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imagebelowinheritedenabled') &&
                property_exists($data, 'imagebelowinheritedenabled')) {
            $record->imagebelowinheritedenabled = empty($data->imagebelowinheritedenabled) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveinheritedenabled') &&
                property_exists($data, 'imageaboveinheritedenabled')) {
            $record->imageaboveinheritedenabled = empty($data->imageaboveinheritedenabled) ? 0 : 1;
            if (!empty($record->imageaboveinheritedenabled) &&
                    (self::source_has_border_layer($source) || self::source_has_overlay_layer($source))) {
                $record->imageaboveinheritedenabled = 0;
                $record->dynamicimagesizeenabled = 1;
            }
        }
        if (!empty($record->imagebelowinheritedenabled)) {
            if (self::table_field_exists('local_course_banner_builder_elements', 'dynamicimagesizeenabled')) {
                $record->dynamicimagesizeenabled = 0;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveoverlayenabled')) {
                $record->imageaboveoverlayenabled = 0;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveinheritedenabled')) {
                $record->imageaboveinheritedenabled = 0;
            }
        } else if (!empty($record->imageaboveinheritedenabled)) {
            if (self::table_field_exists('local_course_banner_builder_elements', 'dynamicimagesizeenabled')) {
                $record->dynamicimagesizeenabled = 0;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveoverlayenabled')) {
                $record->imageaboveoverlayenabled = 0;
            }
        } else if (!empty($record->dynamicimagesizeenabled) || !empty($record->imageaboveoverlayenabled)) {
            if (self::table_field_exists('local_course_banner_builder_elements', 'imagebelowinheritedenabled')) {
                $record->imagebelowinheritedenabled = 0;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveinheritedenabled')) {
                $record->imageaboveinheritedenabled = 0;
            }
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imageopacity') &&
                property_exists($data, 'imageopacity')) {
            $imageopacity = $data->imageopacity ?? 100;
            $imageopacity = $imageopacity === '' || !is_numeric($imageopacity) ? 100 : (float)$imageopacity;
            $record->imageopacity = self::normalise_unit_float($imageopacity / 100, 1);
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imagecropenabled')) {
            if (property_exists($data, 'imagecropenabled')) {
                $record->imagecropenabled = empty($data->imagecropenabled) ? 0 : 1;
            }
            foreach (['left', 'top', 'width', 'height'] as $cropfield) {
                $property = 'imagecrop' . $cropfield . 'percent';
                if (self::table_field_exists('local_course_banner_builder_elements', $property) && property_exists($data, $property)) {
                    $minimum = in_array($cropfield, ['width', 'height'], true) ? 1.0 : 0.0;
                    $default = in_array($cropfield, ['width', 'height'], true) ? 100.0 : 0.0;
                    $record->{$property} = self::normalise_percentage((float)($data->{$property} ?? $default), $minimum, 100.0);
                }
            }
            $record->imagecropenabled = self::normalise_image_crop($record)['enabled'] ? 1 : 0;
        }

        if (self::table_field_exists('local_course_banner_builder_elements', 'borderenabled') && property_exists($data, 'borderenabled')) {
            $record->borderenabled = empty($data->borderenabled) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'bordercolor') && property_exists($data, 'bordercolor')) {
            $record->bordercolor = self::normalise_color_string((string)($data->bordercolor ?? '#FFFFFF'));
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderwidth') && property_exists($data, 'borderwidth')) {
            $record->borderwidth = self::normalise_border_width_percent((float)($data->borderwidth ?? 0));
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderopacity') && property_exists($data, 'borderopacity')) {
            $record->borderopacity = self::normalise_unit_float(((float)($data->borderopacity ?? 0)) / 100, 0);
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderfade') && property_exists($data, 'borderfade')) {
            $record->borderfade = self::normalise_unit_float(((float)($data->borderfade ?? 0)) / 100, 0);
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderstyle') && property_exists($data, 'borderstyle')) {
            $record->borderstyle = self::normalise_border_style((string)($data->borderstyle ?? self::BORDER_STYLE_SOLID));
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderdashlength') &&
                property_exists($data, 'borderdashlength')) {
            $record->borderdashlength = max(4, min(80, (int)round((float)($data->borderdashlength ?? 24))));
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'bordersides') &&
                (property_exists($data, 'bordersidesvalue') || property_exists($data, 'bordersidesgroup'))) {
            $sides = self::extract_border_sides_from_form_data($data);
            self::debug_log('save_source_banner_sides', [
                'sourcekey' => $source->sourcekey ?? '',
                'elementid' => (int)($record->id ?? 0),
                'resolvedsides' => $sides,
            ]);
            $record->bordersides = implode(',', self::normalise_border_sides($sides));
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'borderinnerrounded') &&
                property_exists($data, 'borderinnerrounded')) {
            $record->borderinnerrounded = empty($data->borderinnerrounded) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'overlayenabled') &&
                property_exists($data, 'overlayenabled')) {
            $record->overlayenabled = empty($data->overlayenabled) ? 0 : 1;
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'overlaytarget') &&
                property_exists($data, 'overlaytarget')) {
            $record->overlaytarget = self::normalise_overlay_target((string)($data->overlaytarget ?? self::OVERLAY_TARGET_BOTH));
        }
        if (!empty($record->overlayenabled) && self::is_site_source($source)) {
            $record->overlaytarget = self::OVERLAY_TARGET_BANNER;
        }
        if (!empty($data->overlayenabled) && property_exists($data, 'overlaybannercolor')) {
            $data->overlayslideshowcolor = $data->overlaybannercolor;
        }
        if (!empty($data->overlayenabled) && property_exists($data, 'overlaybanneropacity')) {
            $data->overlayslideshowopacity = $data->overlaybanneropacity;
        }
        foreach (['overlaybannercolor', 'overlayslideshowcolor'] as $fieldname) {
            if (self::table_field_exists('local_course_banner_builder_elements', $fieldname) && property_exists($data, $fieldname)) {
                $record->{$fieldname} = self::normalise_color_string((string)($data->{$fieldname} ?? '#000000'));
            }
        }
        foreach (['overlaybanneropacity', 'overlayslideshowopacity'] as $fieldname) {
            if (self::table_field_exists('local_course_banner_builder_elements', $fieldname) && property_exists($data, $fieldname)) {
                $record->{$fieldname} = self::normalise_percentage((float)($data->{$fieldname} ?? 0), 0.0, 100.0);
            }
        }
        foreach (['overlaytitleabove', 'overlayborderabove'] as $fieldname) {
            if (self::table_field_exists('local_course_banner_builder_elements', $fieldname) && property_exists($data, $fieldname)) {
                $record->{$fieldname} = empty($data->{$fieldname}) ? 0 : 1;
            }
        }
    }

    /**
     * Normalise overlay target.
     *
     * @param string $target
     * @return string
     */
    protected static function normalise_overlay_target(string $target): string {
        return in_array($target, [self::OVERLAY_TARGET_BANNER, self::OVERLAY_TARGET_SLIDESHOW, self::OVERLAY_TARGET_BOTH], true)
            ? $target
            : self::OVERLAY_TARGET_BOTH;
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
        $record->sortorder = self::is_locked_order_layer($record)
            ? self::get_next_sortorder_for_source($source ?: self::resolve_source(self::get_record_source_key($record)))
            : max(0, $sortorder);
        $record->isenabled = $enabled ? 1 : 0;
        $record->timemodified = time();
        $DB->update_record('local_course_banner_builder_elements', $record);
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
        $record->sortorder = self::is_locked_order_layer($record)
            ? self::get_next_sortorder_for_source($source ?: self::resolve_source(self::get_record_source_key($record)))
            : max(0, $sortorder);
        $record->isenabled = $enabled ? 1 : 0;
        if (self::table_field_exists('local_course_banner_builder_elements', 'fitmodeoverride')) {
            $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
        }
        $record->timemodified = time();
        $DB->update_record('local_course_banner_builder_elements', $record);
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
            $currentfitmodeoverride = self::table_field_exists('local_course_banner_builder_elements', 'fitmodeoverride') ?
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
            $record->sortorder = self::is_locked_order_layer($record)
                ? self::get_next_sortorder_for_source($source)
                : max(0, (int)($sortorders[$elementid] ?? $record->sortorder));
            $record->isenabled = !empty($enabled[$elementid]) ? 1 : 0;
            if (self::table_field_exists('local_course_banner_builder_elements', 'fitmodeoverride')) {
                $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
            }
            $record->timemodified = $now;
            $DB->update_record('local_course_banner_builder_elements', $record);
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
            if (array_key_exists('isenabled', $layer)) {
                $record->isenabled = empty($layer['isenabled']) ? 0 : 1;
            }
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

            if (self::table_field_exists('local_course_banner_builder_elements', 'fitmodeoverride')) {
                $record->fitmodeoverride = ($fitmodeoverride === '') ? null : $fitmodeoverride;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'positionanchor')) {
                $record->positionanchor = self::normalise_position_anchor(
                    (string)($layer['positionanchor'] ?? self::POSITION_TOP_LEFT)
                );
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'customwidthpercent')) {
                $record->customwidthpercent = self::normalise_percentage(
                    (float)($layer['customwidthpercent'] ?? 100),
                    0.0,
                    self::CUSTOM_SIZE_PERCENT_MAX
                );
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'customheightpercent')) {
                $record->customheightpercent = self::normalise_percentage(
                    (float)($layer['customheightpercent'] ?? 100),
                    0.0,
                    self::CUSTOM_SIZE_PERCENT_MAX
                );
            }
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $property = 'offset' . $side . 'percent';
                if (!self::table_field_exists('local_course_banner_builder_elements', $property)) {
                    continue;
                }
                $defaultvalue = in_array($side, ['top', 'left'], true) ? 0 : 0;
                $record->{$property} = self::normalise_percentage((float)($layer[$property] ?? $defaultvalue), -1000.0, 1000.0);
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'customsizekeepaspect') &&
                    array_key_exists('customsizekeepaspect', $layer)) {
                $record->customsizekeepaspect = empty($layer['customsizekeepaspect']) ? 0 : 1;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'dynamicimagesizeenabled') &&
                    array_key_exists('dynamicimagesizeenabled', $layer)) {
                $record->dynamicimagesizeenabled = empty($layer['dynamicimagesizeenabled']) ? 0 : 1;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imagecenterfixed') &&
                    array_key_exists('imagecenterfixed', $layer)) {
                $record->imagecenterfixed = empty($layer['imagecenterfixed']) ? 0 : 1;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveoverlayenabled') &&
                    array_key_exists('imageaboveoverlayenabled', $layer)) {
                $record->imageaboveoverlayenabled = empty($layer['imageaboveoverlayenabled']) ? 0 : 1;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imagebelowinheritedenabled') &&
                    array_key_exists('imagebelowinheritedenabled', $layer)) {
                $record->imagebelowinheritedenabled = empty($layer['imagebelowinheritedenabled']) ? 0 : 1;
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveinheritedenabled') &&
                    array_key_exists('imageaboveinheritedenabled', $layer)) {
                $record->imageaboveinheritedenabled = empty($layer['imageaboveinheritedenabled']) ? 0 : 1;
                if (!empty($record->imageaboveinheritedenabled) &&
                        (self::source_has_border_layer($source) || self::source_has_overlay_layer($source))) {
                    $record->imageaboveinheritedenabled = 0;
                    $record->dynamicimagesizeenabled = 1;
                }
            }
            if (!empty($record->imagebelowinheritedenabled)) {
                if (self::table_field_exists('local_course_banner_builder_elements', 'dynamicimagesizeenabled')) {
                    $record->dynamicimagesizeenabled = 0;
                }
                if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveoverlayenabled')) {
                    $record->imageaboveoverlayenabled = 0;
                }
                if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveinheritedenabled')) {
                    $record->imageaboveinheritedenabled = 0;
                }
            } else if (!empty($record->imageaboveinheritedenabled)) {
                if (self::table_field_exists('local_course_banner_builder_elements', 'dynamicimagesizeenabled')) {
                    $record->dynamicimagesizeenabled = 0;
                }
                if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveoverlayenabled')) {
                    $record->imageaboveoverlayenabled = 0;
                }
            } else if (!empty($record->dynamicimagesizeenabled) || !empty($record->imageaboveoverlayenabled)) {
                if (self::table_field_exists('local_course_banner_builder_elements', 'imagebelowinheritedenabled')) {
                    $record->imagebelowinheritedenabled = 0;
                }
                if (self::table_field_exists('local_course_banner_builder_elements', 'imageaboveinheritedenabled')) {
                    $record->imageaboveinheritedenabled = 0;
                }
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imageopacity') &&
                    array_key_exists('imageopacity', $layer)) {
                $imageopacity = $layer['imageopacity'] ?? 100;
                $imageopacity = $imageopacity === '' || !is_numeric($imageopacity) ? 100 : (float)$imageopacity;
                $record->imageopacity = self::normalise_unit_float($imageopacity / 100, 1);
            }
            if (self::table_field_exists('local_course_banner_builder_elements', 'imagecropenabled')) {
                $record->imagecropenabled = empty($layer['imagecropenabled']) ? 0 : 1;
                foreach (['left', 'top', 'width', 'height'] as $cropfield) {
                    $property = 'imagecrop' . $cropfield . 'percent';
                    if (!self::table_field_exists('local_course_banner_builder_elements', $property)) {
                        continue;
                    }
                    $minimum = in_array($cropfield, ['width', 'height'], true) ? 1.0 : 0.0;
                    $default = in_array($cropfield, ['width', 'height'], true) ? 100.0 : 0.0;
                    $record->{$property} = self::normalise_percentage((float)($layer[$property] ?? $default), $minimum, 100.0);
                }
                $record->imagecropenabled = self::normalise_image_crop($record)['enabled'] ? 1 : 0;
            }

            $record->timemodified = $now;
            $DB->update_record('local_course_banner_builder_elements', $record);
        }

        self::normalize_element_sortorders(self::get_source_elements($source));
        self::sync_courses_for_source($source);
    }

    /**
     * Delete one banner element and its files.
     *
     * @param int $elementid
     * @param bool $sync
     * @return void
     */
    public static function delete_banner_element(int $elementid, bool $sync = true): void {
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
        $DB->delete_records('local_course_banner_builder_elements', ['id' => $record->id]);

        $source = self::resolve_source(self::get_record_source_key($record));
        if ($sync && $source) {
            self::sync_courses_for_source($source);
        }
    }

    /**
     * Delete all content and rules for a category.
     *
     * @param int $categoryid
     * @param bool $sync
     * @return void
     */
    public static function delete_category_content(int $categoryid, bool $sync = true): void {
        global $DB;

        self::delete_category_images($categoryid, false);
        if (self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            $DB->delete_records('local_course_banner_builder_order', ['sourcekey' => self::get_category_source_key($categoryid)]);
        } else {
            $DB->delete_records('local_course_banner_builder_order', ['categoryid' => $categoryid]);
        }
        if ($sync) {
            self::sync_courses_for_category_tree($categoryid);
        }
    }

    /**
     * Delete all content and rules for a source.
     *
     * @param \stdClass $source
     * @param bool $sync
     * @return void
     */
    public static function delete_source_content(\stdClass $source, bool $sync = true): void {
        global $DB;

        if ($source->type === self::SOURCE_TYPE_CATEGORY) {
            self::delete_category_content((int)$source->categoryid, $sync);
            return;
        }

        self::delete_source_images($source, false);
        if (self::table_field_exists('local_course_banner_builder_order', 'sourcekey')) {
            $DB->delete_records('local_course_banner_builder_order', ['sourcekey' => $source->sourcekey]);
        }
        if ($sync) {
            self::sync_courses_for_source($source);
        }
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

        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcekey')) {
            $DB->delete_records('local_course_banner_builder_elements', ['sourcekey' => $source->sourcekey]);
        } else {
            $DB->delete_records('local_course_banner_builder_elements', ['categoryid' => (int)($source->categoryid ?? 0)]);
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
     * @param bool $includetitlepreviewoverlays
     * @return array
     */
    public static function export_modal_preview_definition(
        \stdClass $source,
        int $elementid = 0,
        bool $includetitlepreviewoverlays = false
    ): array {
        $contextlayers = [];
        $currentlayer = null;
        $sourcesettings = self::get_source_settings($source);
        $defaultfitmode = (string)($sourcesettings->fitmode ?? self::FIT_MODE_BANNER);
        $bannerformat = self::is_site_source($source) ? self::get_site_banner_format() : self::get_course_banner_format();
        $banneraspect = self::get_banner_format_aspect_ratio($bannerformat);

        foreach (self::get_preview_layer_specs_for_source($source, true) as $layerspec) {
            $record = $layerspec['record'];
            $isinherited = (string)self::get_record_source_key($record) !== (string)$source->sourcekey;
            if ($elementid > 0 && (int)$record->id === $elementid) {
                $currentlayer = self::export_modal_preview_layer($layerspec, false, false, $banneraspect);
                continue;
            }

            $layer = self::export_modal_preview_layer($layerspec, true, $isinherited, $banneraspect, $includetitlepreviewoverlays);
            if ($layer !== null) {
                $contextlayers[] = $layer;
            }
        }
        $titlelayer = self::export_banner_title_preview_layer($source);
        if ($titlelayer !== null) {
            $contextlayers[] = $titlelayer;
        }
        if ($elementid > 0 && $currentlayer === null) {
            foreach (self::get_source_elements($source) as $record) {
                if ((int)$record->id !== $elementid) {
                    continue;
                }
                $currentlayer = self::export_modal_preview_layer([
                    'record' => $record,
                    'fitmode' => self::get_effective_fit_mode_for_record($record, (int)($source->categoryid ?? 0)),
                    'categoryorder' => 0,
                    'source' => $source,
                ], false, false, $banneraspect, $includetitlepreviewoverlays);
                break;
            }
        }

        return [
            'bannerformat' => $bannerformat,
            'sourcecontext' => self::is_site_source($source) ? 'site' : 'course',
            'defaultfitmode' => $defaultfitmode,
            'hascontextlayers' => !empty($contextlayers),
            'contextlayers' => $contextlayers,
            'currentlayer' => $currentlayer,
        ];
    }

    /**
     * Whether the source context has at least one enabled banner title.
     *
     * @param \stdClass $source
     * @return bool
     */
    public static function source_has_active_banner_title(\stdClass $source): bool {
        return self::get_banner_title_preview_context_for_source($source) !== null;
    }

    /**
     * Export the contextual banner title preview layer.
     *
     * @param \stdClass $source
     * @param bool $iscontext
     * @return array|null
     */
    public static function export_banner_title_preview_layer(\stdClass $source, bool $iscontext = true): ?array {
        global $SITE;

        $context = self::get_banner_title_preview_context_for_source($source);
        if ($context === null) {
            return null;
        }

        $config = self::export_banner_title_configuration([$context])[$context] ?? null;
        if (empty($config) || empty($config['enabled'])) {
            return null;
        }

        if ($context === 'site') {
            $text = trim((string)($SITE->fullname ?? ''));
            if ($text === '') {
                $text = get_string('previewsitetitle', 'local_course_banner_builder');
            }
        } else {
            $textkey = $context === 'activity' ? 'previewactivitytitle' : 'previewcoursetitle';
            $text = get_string($textkey, 'local_course_banner_builder');
        }
        $style = self::build_banner_title_preview_style($config);

        return [
            'type' => 'title',
            'enabled' => true,
            'iscontext' => $iscontext,
            'context' => $context,
            'text' => $text,
            'style' => $style['style'],
            'framestyle' => $style['framestyle'],
            'frametype' => (string)($config['frametype'] ?? 'box'),
            'textscale' => $style['textscale'],
            'align' => $style['align'],
            'zindex' => $style['zindex'],
        ];
    }

    /**
     * Pick the title context to preview for a source.
     *
     * @param \stdClass $source
     * @return string|null
     */
    protected static function get_banner_title_preview_context_for_source(\stdClass $source): ?string {
        if (self::is_site_source($source)) {
            return (bool)get_config('local_course_banner_builder', 'bannertitle_site_enabled') ? 'site' : null;
        }

        if ((bool)get_config('local_course_banner_builder', 'bannertitle_course_enabled')) {
            return 'course';
        }
        if ((bool)get_config('local_course_banner_builder', 'bannertitle_activity_enabled')) {
            return 'activity';
        }
        return null;
    }

    /**
     * Build CSS for a banner title preview layer.
     *
     * @param array $config
     * @return array{style:string,framestyle:string,zindex:int}
     */
    protected static function build_banner_title_preview_style(array $config): array {
        $fontsize = max(25, min(480, (float)($config['fontsize'] ?? 100)));
        $lineheight = max(40, min(540, (float)($config['lineheight'] ?? 105)));
        $x = max(0, min(100, (float)($config['x'] ?? 50)));
        $y = max(0, min(100, (float)($config['y'] ?? 50)));
        $align = self::normalise_slideshow_alignment((string)($config['align'] ?? self::SLIDESHOW_ALIGN_CENTER));
        $flexalign = match ($align) {
            self::SLIDESHOW_ALIGN_LEFT => 'flex-start',
            self::SLIDESHOW_ALIGN_RIGHT => 'flex-end',
            default => 'center',
        };
        $aboveborder = !empty($config['aboveborder']);
        $aboveoverlay = !empty($config['aboveoverlay']);
        $zindex = self::get_banner_title_preview_zindex($aboveborder, $aboveoverlay);
        $fontfamily = (string)($config['fontfamily'] ?? '');
        if ($fontfamily !== '' && !array_key_exists($fontfamily, self::get_slideshow_font_family_options())) {
            $fontfamily = '';
        }

        $style = array_merge(self::build_banner_title_preview_position_rules($x, $align, $aboveborder), [
            'top: ' . $y . '%;',
            'z-index: ' . $zindex . ';',
            'display: inline-flex;',
            'flex-direction: column;',
            'width: max-content;',
            'min-width: 0;',
            'color: ' . self::normalise_banner_title_hex((string)($config['color'] ?? '#FFFFFF'), '#FFFFFF') . ';',
            'font-size: clamp(0.62rem, min(7.4cqh, 2.65cqw), 2.35rem);',
            'font-family: ' . ($fontfamily !== '' ? s($fontfamily) : 'inherit') . ';',
            'font-weight: ' . (!empty($config['bold']) ? '800' : '500') . ';',
            'font-style: ' . (!empty($config['italic']) ? 'italic' : 'normal') . ';',
            'text-decoration: ' . (implode(' ', array_filter([
                !empty($config['underline']) ? 'underline' : '',
                !empty($config['strike']) ? 'line-through' : '',
            ])) ?: 'none') . ';',
            'text-transform: ' . (!empty($config['allcaps']) ? 'uppercase' : 'none') . ';',
            'text-align: ' . $align . ';',
            'align-items: ' . $flexalign . ';',
            'justify-content: flex-start;',
            'line-height: ' . $lineheight . '%;',
            'white-space: pre-wrap;',
            'overflow-wrap: normal;',
            'word-break: normal;',
            '--local-course-banner-builder-inline-frame-gap: ' .
                round(self::banner_title_inline_frame_gap_em((float)$lineheight), 3) . 'em;',
        ]);

        $framestyle = [];
        if (!empty($config['frameenabled'])) {
            $framerules = [];
            $framerules[] = 'background: ' . self::banner_title_preview_rgba(
                (string)($config['framecolor'] ?? '#000000'),
                (float)($config['frameopacity'] ?? 35)
            ) . ';';
            $framerules[] = 'border: ' . max(0, min(10, (float)($config['frameborderwidth'] ?? 0))) .
                'px solid ' . self::normalise_banner_title_hex((string)($config['framebordercolor'] ?? '#FFFFFF'), '#FFFFFF') . ';';
            $framerules[] = 'border-radius: ' . max(0, min(80, (float)($config['frameradius'] ?? 12))) . 'px;';
            $padding = max(0, min(240, (float)($config['framepadding'] ?? 18)));
            $framerules[] = 'padding: ' . self::banner_title_frame_padding_css($padding) . ';';
            $framerules[] = 'line-height: ' . $lineheight . '%;';
            $framerules[] = 'text-align: ' . $align . ';';
            $framerules[] = 'justify-content: ' . $flexalign . ';';
            if (!empty($config['frameshadowenabled'])) {
                $framerules[] = 'box-shadow: ' . self::build_banner_title_preview_shadow(
                    $config,
                    'frameshadow',
                    6,
                    135,
                    14,
                    '#000000',
                    25
                ) . ';';
            }
            if ((string)($config['frametype'] ?? 'box') === 'highlight') {
                $framestyle = $framerules;
            } else {
                $style = array_merge($style, $framerules);
            }
        }

        if (!empty($config['shadowenabled'])) {
            $style[] = 'text-shadow: ' . self::build_banner_title_preview_shadow(
                $config,
                'shadow',
                4,
                135,
                10,
                '#000000',
                55
            ) . ';';
        } else {
            $style[] = 'text-shadow: none;';
        }

        return [
            'style' => implode(' ', $style),
            'framestyle' => implode(' ', $framestyle),
            'textscale' => max(0.25, min(4.8, $fontsize / 100)),
            'align' => $align,
            'zindex' => $zindex,
        ];
    }

    /**
     * Build title preview positioning and wrap-width rules.
     *
     * @param float $x
     * @param string $align
     * @param bool $aboveborder
     * @return array
     */
    protected static function build_banner_title_preview_position_rules(float $x, string $align, bool $aboveborder): array {
        $safeleftrule = $aboveborder ? '0px' : 'var(--local-course-banner-builder-preview-left-width, 0px)';
        $saferightrule = $aboveborder ? '0px' : 'var(--local-course-banner-builder-preview-right-width, 0px)';
        $transform = match ($align) {
            self::SLIDESHOW_ALIGN_LEFT => 'translate(0, -50%)',
            self::SLIDESHOW_ALIGN_RIGHT => 'translate(-100%, -50%)',
            default => 'translate(calc(var(--local-course-banner-builder-title-raw-x) * -1), -50%)',
        };
        $available = match ($align) {
            self::SLIDESHOW_ALIGN_LEFT => 'calc(100% - var(--local-course-banner-builder-title-anchor-x) - ' .
                'var(--local-course-banner-builder-title-safe-right) - 1.25rem)',
            self::SLIDESHOW_ALIGN_RIGHT => 'calc(var(--local-course-banner-builder-title-anchor-x) - ' .
                'var(--local-course-banner-builder-title-safe-left) - 1.25rem)',
            default => 'calc(100% - var(--local-course-banner-builder-title-safe-left) - ' .
                'var(--local-course-banner-builder-title-safe-right) - 1.25rem)',
        };
        return [
            '--local-course-banner-builder-title-safe-left: ' . $safeleftrule . ';',
            '--local-course-banner-builder-title-safe-right: ' . $saferightrule . ';',
            '--local-course-banner-builder-title-raw-x: ' . $x . '%;',
            '--local-course-banner-builder-title-anchor-x: clamp(calc(' . $safeleftrule . ' + 0.625rem), ' .
                $x . '%, calc(100% - ' . $saferightrule . ' - 0.625rem));',
            'left: var(--local-course-banner-builder-title-anchor-x);',
            'transform: ' . $transform . ';',
            'max-width: max(1rem, ' . $available . ');',
        ];
    }

    /**
     * Build a title preview z-index that respects both border and overlay ordering.
     *
     * @param bool $aboveborder
     * @param bool $aboveoverlay
     * @return int
     */
    protected static function get_banner_title_preview_zindex(bool $aboveborder, bool $aboveoverlay): int {
        if (!$aboveborder) {
            return $aboveoverlay ? 1500 : 900;
        }
        return $aboveoverlay ? 4010 : 2500;
    }

    /**
     * Normalise a title colour.
     *
     * @param string $color
     * @param string $default
     * @return string
     */
    protected static function normalise_banner_title_hex(string $color, string $default): string {
        return preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtoupper($color) : $default;
    }

    /**
     * Build a title rgba() colour.
     *
     * @param string $color
     * @param float $opacity
     * @return string
     */
    protected static function banner_title_preview_rgba(string $color, float $opacity): string {
        return self::build_css_color_pair(
            self::normalise_banner_title_hex($color, '#000000'),
            max(0, min(100, $opacity)) / 100
        )['solid'];
    }

    /**
     * Compute the extra gap between inline highlight frames from line-height.
     *
     * @param float $lineheightpercent
     * @return float
     */
    protected static function banner_title_inline_frame_gap_em(float $lineheightpercent): float {
        $lineheightpercent = max(40.0, min(540.0, $lineheightpercent));
        return max(0.14, min(2.1, 0.14 + ((($lineheightpercent - 105.0) / 100.0) * 0.45)));
    }

    /**
     * Convert title frame padding control value to responsive CSS.
     *
     * @param float $padding
     * @return string
     */
    protected static function banner_title_frame_padding_css(float $padding): string {
        $padding = max(0.0, min(240.0, $padding));
        return round(($padding / 2.0) / 16.0, 4) . 'em ' . round($padding / 16.0, 4) . 'em';
    }

    /**
     * Build a title shadow declaration.
     *
     * @param array $config
     * @param string $prefix
     * @param float $defaultdistance
     * @param float $defaultdirection
     * @param float $defaultblur
     * @param string $defaultcolor
     * @param float $defaultopacity
     * @return string
     */
    protected static function build_banner_title_preview_shadow(
        array $config,
        string $prefix,
        float $defaultdistance,
        float $defaultdirection,
        float $defaultblur,
        string $defaultcolor,
        float $defaultopacity
    ): string {
        $distance = max(0, min(50, (float)($config[$prefix . 'distance'] ?? $defaultdistance)));
        $angle = deg2rad(max(0, min(360, (float)($config[$prefix . 'direction'] ?? $defaultdirection))));
        $xoffset = round(cos($angle) * $distance, 2);
        $yoffset = round(sin($angle) * $distance, 2);
        $blur = max(0, min(80, (float)($config[$prefix . 'blur'] ?? $defaultblur)));
        $color = self::banner_title_preview_rgba(
            (string)($config[$prefix . 'color'] ?? $defaultcolor),
            (float)($config[$prefix . 'opacity'] ?? $defaultopacity)
        );
        return $xoffset . 'px ' . $yoffset . 'px ' . $blur . 'px ' . $color;
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
                continue;
            }

            if (self::record_overlay_targets_banner($record)) {
                $layer = self::export_modal_preview_overlay_layer($record, false, false);
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
     * Export the full inherited source chain for the read-only configured-source preview modal.
     *
     * @param \stdClass $source
     * @return array
     */
    public static function export_source_chain_visual_editor_definition(\stdClass $source): array {
        $layers = [];
        $defaultfitmode = (string)(self::get_source_settings($source)->fitmode ?? self::FIT_MODE_BANNER);
        $bannerformat = self::is_site_source($source) ? self::get_site_banner_format() : self::get_course_banner_format();
        $banneraspect = self::get_banner_format_aspect_ratio($bannerformat);

        foreach (self::get_preview_layer_specs_for_source($source) as $layerspec) {
            $record = $layerspec['record'];
            $isinherited = (string)self::get_record_source_key($record) !== (string)$source->sourcekey;
            $layer = self::export_modal_preview_layer($layerspec, false, $isinherited, $banneraspect);
            if ($layer === null) {
                continue;
            }
            $layer['enabled'] = !empty($record->isenabled);
            $layer['editable'] = false;
            $layers[] = $layer;
        }

        return [
            'defaultfitmode' => $defaultfitmode,
            'haslayers' => !empty($layers),
            'layers' => $layers,
        ];
    }

    /**
     * Export enabled site-banner layers for front-end rendering.
     *
     * @return array
     */
    public static function export_site_banner_render_definition(): array {
        $source = self::get_site_source();
        $layers = [];
        $settings = self::get_source_settings($source);
        $defaultfitmode = (string)($settings->fitmode ?? self::FIT_MODE_BANNER);
        $banneraspect = self::get_banner_format_aspect_ratio(self::get_site_banner_format());

        foreach (self::get_layer_specs_for_source_chain($source, 0, 0) as $layerspec) {
            $record = $layerspec['record'];
            if (empty($record->isenabled)) {
                continue;
            }
            $layer = self::export_modal_preview_layer($layerspec, false, false, $banneraspect);
            if ($layer === null) {
                continue;
            }
            $layer['enabled'] = true;
            $layers[] = $layer;
        }

        return [
            'defaultfitmode' => $defaultfitmode,
            'haslayers' => !empty($layers),
            'layers' => self::sort_exported_preview_layers($layers),
        ];
    }

    /**
     * Sort exported preview layers by their already-computed visual order.
     *
     * @param array $layers
     * @return array
     */
    protected static function sort_exported_preview_layers(array $layers): array {
        usort($layers, static function (array $a, array $b): int {
            $zcompare = ((int)($a['zindex'] ?? 0)) <=> ((int)($b['zindex'] ?? 0));
            if ($zcompare !== 0) {
                return $zcompare;
            }
            $sortcompare = ((int)($a['sortorder'] ?? 0)) <=> ((int)($b['sortorder'] ?? 0));
            if ($sortcompare !== 0) {
                return $sortcompare;
            }
            return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        });

        return $layers;
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
                'imagecenterfixed' => 0,
                'imageaboveoverlayenabled' => 0,
                'imagebelowinheritedenabled' => 0,
                'imageaboveinheritedenabled' => 0,
                'imageopacity' => 1,
                'imagecropenabled' => 0,
                'imagecropleftpercent' => 0,
                'imagecroptoppercent' => 0,
                'imagecropwidthpercent' => 100,
                'imagecropheightpercent' => 100,
                'borderenabled' => 0,
                'bordercolor' => '#56B9C0',
                'borderwidth' => 2.5,
                'borderopacity' => 0,
                'borderfade' => 0,
                'borderstyle' => self::BORDER_STYLE_SOLID,
                'borderdashlength' => 24,
                'bordersides' => 'top,right,bottom,left',
                'borderinnerrounded' => 0,
                'overlayenabled' => 0,
                'overlaytarget' => self::OVERLAY_TARGET_BOTH,
                'overlaybannercolor' => '#000000',
                'overlaybanneropacity' => 25,
                'overlayslideshowcolor' => '#000000',
                'overlayslideshowopacity' => 38,
                'overlaytitleabove' => 1,
                'overlayborderabove' => 1,
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
            'currentisoverlaylayer' => !empty($record->overlayenabled) ? 1 : 0,
            'sourcehasborderlayer' => !empty(self::get_source_border_conflict_state($source, (int)$record->id)['blocked']) ? 1 : 0,
            'sourcehasoverlaylayer' => !empty(self::get_source_overlay_conflict_state($source, (int)$record->id)['blocked']) ? 1 : 0,
            'bordersidesvalue' => implode(',', array_values(array_filter($bordersides, static function (string $side): bool {
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
            'imagecenterfixed' => (int)($record->imagecenterfixed ?? 0),
            'imageaboveoverlayenabled' => (int)($record->imageaboveoverlayenabled ?? 0),
            'imagebelowinheritedenabled' => (int)($record->imagebelowinheritedenabled ?? 0),
            'imageaboveinheritedenabled' => (int)($record->imageaboveinheritedenabled ?? 0),
            'imageopacity' => (float)round(((float)($record->imageopacity ?? 1)) * 100, 2),
            'imagecropenabled' => (int)($record->imagecropenabled ?? 0),
            'imagecropleftpercent' => (float)($record->imagecropleftpercent ?? 0),
            'imagecroptoppercent' => (float)($record->imagecroptoppercent ?? 0),
            'imagecropwidthpercent' => (float)($record->imagecropwidthpercent ?? 100),
            'imagecropheightpercent' => (float)($record->imagecropheightpercent ?? 100),
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
            'overlayenabled' => (int)($record->overlayenabled ?? 0),
            'overlaytarget' => self::normalise_overlay_target((string)($record->overlaytarget ?? self::OVERLAY_TARGET_BOTH)),
            'overlaybannercolor' => (string)($record->overlaybannercolor ?? '#000000'),
            'overlaybanneropacity' => (float)($record->overlaybanneropacity ?? 25),
            'overlayslideshowcolor' => (string)($record->overlayslideshowcolor ?? '#000000'),
            'overlayslideshowopacity' => (float)($record->overlayslideshowopacity ?? 38),
            'overlaytitleabove' => (int)($record->overlaytitleabove ?? 1),
            'overlayborderabove' => (int)($record->overlayborderabove ?? 1),
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
     * @param bool $allowmultiple
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
     * @param string $selectedsourcekey
     * @return array
     */
    public static function export_configured_categories(string $selectedsourcekey = ''): array {
        global $DB;

        $categoryids = array_unique(array_merge(
            array_map('intval', $DB->get_fieldset_select('local_course_banner_builder_elements', 'DISTINCT categoryid', 'categoryid IS NOT NULL')),
            array_map('intval', $DB->get_fieldset_select('local_course_banner_builder_order', 'DISTINCT categoryid', 'categoryid IS NOT NULL'))
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
            $pathsort = implode('/', array_map(static function (int $pathid): string {
                return sprintf('%010d', $pathid);
            }, $pathids));
            $depth = max(0, count($pathids) - 1);
            $rowclasses = self::HIERARCHY_ROW_CLASSES;
            $rowclass = $rowclasses[min($depth, count($rowclasses) - 1)];
            $compositionmode = $settings->compositionmode ?? self::MODE_CUMULATIVE;
            $fitmode = $settings->fitmode ?? self::FIT_MODE_ORIGINAL;
            $thumbnails = self::export_element_thumbnails($elements);
            $bordercount = self::count_border_elements($elements);
            $overlaycount = self::count_overlay_elements($elements);
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
                'hasbordercaption' => !empty($thumbnails) && ($bordercount > 0 || $overlaycount > 0),
                'bordercaption' => self::format_additional_layer_label($bordercount, $overlaycount),
                'hasmorethumbnails' => count($elements) > self::ADMIN_THUMB_LIMIT,
                'morethumbnailscount' => max(0, count($elements) - self::ADMIN_THUMB_LIMIT),
                'nothumbnailslabel' => self::format_no_thumbnail_label($bordercount, $overlaycount),
                'nothumbnailsisborderlabel' => $bordercount > 0 || $overlaycount > 0,
                'sesskey' => sesskey(),
                'editurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'categoryid' => $categoryid,
                ]))->out(false),
                'previewurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                    'categoryid' => $categoryid,
                    'sourcechainpreview' => 1,
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

        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcekey')) {
            $sourcekeys = array_unique(array_merge(
                $DB->get_fieldset_select(
                    'local_course_banner_builder_elements',
                    'DISTINCT sourcekey',
                    'sourcetype = :sourcetype AND sourcekey IS NOT NULL',
                    ['sourcetype' => self::SOURCE_TYPE_CUSTOMFIELD]
                ),
                $DB->get_fieldset_select(
                    'local_course_banner_builder_order',
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
                $compositionmode = $settings->compositionmode ?? self::MODE_CUMULATIVE;
                $fitmode = $settings->fitmode ?? self::FIT_MODE_ORIGINAL;
                $thumbnails = self::export_element_thumbnails($elements);
                $bordercount = self::count_border_elements($elements);
                $overlaycount = self::count_overlay_elements($elements);
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
                    'hasbordercaption' => !empty($thumbnails) && ($bordercount > 0 || $overlaycount > 0),
                    'bordercaption' => self::format_additional_layer_label($bordercount, $overlaycount),
                    'hasmorethumbnails' => count($elements) > self::ADMIN_THUMB_LIMIT,
                    'morethumbnailscount' => max(0, count($elements) - self::ADMIN_THUMB_LIMIT),
                    'nothumbnailslabel' => self::format_no_thumbnail_label($bordercount, $overlaycount),
                    'nothumbnailsisborderlabel' => $bordercount > 0 || $overlaycount > 0,
                    'sesskey' => sesskey(),
                    'editurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                        'sourcekey' => $source->sourcekey,
                    ]))->out(false),
                    'previewurl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                        'sourcekey' => $source->sourcekey,
                        'sourcechainpreview' => 1,
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
        self::mark_selected_configured_source($items, $selectedsourcekey);

        return [
            'hasitems' => !empty($items),
            'items' => $items,
        ];
    }

    /**
     * Add visual state for the source being edited in the configured source list.
     *
     * @param array $items
     * @param string $selectedsourcekey
     * @return void
     */
    protected static function mark_selected_configured_source(array &$items, string $selectedsourcekey): void {
        $selectedsourcekey = trim($selectedsourcekey);
        if ($selectedsourcekey === '') {
            return;
        }

        foreach ($items as &$item) {
            if ((string)($item['sourcekey'] ?? '') !== $selectedsourcekey) {
                continue;
            }
            $item['isselectedsource'] = true;
            $item['currentlyeditinglabel'] = get_string('currentlyediting', 'local_course_banner_builder');
            $item['rowclass'] = trim((string)($item['rowclass'] ?? '') . ' is-current-source');
            break;
        }
        unset($item);
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

            $styles = self::build_native_course_header_overlay_styles(
                $record,
                $file,
                $fitmode,
                self::get_banner_format_aspect_ratio(self::get_course_banner_format())
            );
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

        $sortkeys = static function (array &$keys) use ($bykey): void {
            usort($keys, static function (string $a, string $b) use ($bykey): int {
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
        $branchheads = [];
        $assignbranch = function (string $parentkey, string $label) use (&$assignbranch, &$branchlabels, $children): void {
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
                $branchheads[$childkey] = true;
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
        $walk = function (string $key, int $level, int $rootid) use (
            &$walk,
            &$ordered,
            &$visited,
            $children,
            $bykey,
            $branchlabels,
            $branchheads
        ): void {
            if (isset($visited[$key]) || empty($bykey[$key])) {
                return;
            }
            $visited[$key] = true;
            $item = $bykey[$key];
            $childkeys = $children[$key] ?? [];
            $parentkey = (string)($item['sourceparentkey'] ?? '');
            $item['hierarchylevel'] = $level;
            $item['hierarchyarrows'] = $level > 0 ? implode(' ', array_fill(0, $level, '|')) : '';
            $item['hierarchylevelmarkers'] = [];
            for ($levelmarker = 0; $levelmarker < $level; $levelmarker++) {
                $item['hierarchylevelmarkers'][] = [
                    'style' => 'left: ' . number_format(0.92 + ($levelmarker * 0.48), 2, '.', '') . 'rem;',
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
            $item['hasbranchhead'] = !empty($branchheads[$key]);
            $item['hasparentconnector'] = $level > 0 && empty($branchheads[$key]);
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
        $course = self::ensure_course_context_fields($course);
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
        $course = self::ensure_course_context_fields($course);
        if (empty($course->id) || empty($course->category)) {
            return false;
        }

        foreach (self::get_enabled_category_elements_for_course($course) as $layerspec) {
            $record = $layerspec['record'];
            if (self::get_banner_image_file($record) || !empty($record->borderenabled) ||
                    self::record_overlay_targets_banner($record) || self::record_overlay_targets_slideshow($record)) {
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
        foreach (self::get_courses_in_category_tree($categoryid) as $course) {
            try {
                self::sync_course_overview_image($course);
            } catch (\Throwable $e) {
                debugging('Course banner sync failed for course ' . $course->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Get all courses in one category tree.
     *
     * @param int $categoryid
     * @return \stdClass[]
     */
    protected static function get_courses_in_category_tree(int $categoryid): array {
        global $DB;

        if (!$categoryid) {
            return [];
        }

        $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id,path', IGNORE_MISSING);
        if (!$category) {
            return [];
        }

        $categorypath = trim((string)$category->path, '/');
        if ($categorypath === '') {
            return [];
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

        return array_values($DB->get_records_select('course', 'category ' . $insql, $params, '', 'id, category'));
    }

    /**
     * Sync courses affected by a source.
     *
     * @param \stdClass $source
     * @return void
     */
    public static function sync_courses_for_source(\stdClass $source): void {
        if (self::is_site_source($source)) {
            self::purge_course_caches();
            return;
        }

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
            'local_course_banner_builder_elements',
            'DISTINCT categoryid',
            'categoryid IS NOT NULL'
        );

        foreach ($categoryids as $categoryid) {
            self::sync_courses_for_category_tree((int)$categoryid);
        }

        $managedcourses = self::get_courses_with_managed_overview_images();
        foreach ($managedcourses as $course) {
            try {
                self::sync_course_overview_image($course);
            } catch (\Throwable $e) {
                debugging('Course banner sync failed for course ' . $course->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcetype')) {
            $hascustomfieldsources = $DB->record_exists('local_course_banner_builder_elements', [
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
     * Replace custom course overview images by generated plugin images on affected courses.
     *
     * @return int Number of courses processed.
     */
    public static function force_course_overview_image_replacement(): int {
        global $DB;

        $courses = [];
        $addcourse = static function (\stdClass $course) use (&$courses): void {
            if (!empty($course->id)) {
                $courses[(int)$course->id] = $course;
            }
        };

        $categoryids = $DB->get_fieldset_select(
            'local_course_banner_builder_elements',
            'DISTINCT categoryid',
            'categoryid IS NOT NULL'
        );
        foreach ($categoryids as $categoryid) {
            foreach (self::get_courses_in_category_tree((int)$categoryid) as $course) {
                $addcourse($course);
            }
        }

        foreach (self::get_courses_with_managed_overview_images() as $course) {
            $addcourse($course);
        }
        foreach (self::get_courses_with_custom_overview_images() as $course) {
            $addcourse($course);
        }

        if (self::table_field_exists('local_course_banner_builder_elements', 'sourcetype')) {
            $hascustomfieldsources = $DB->record_exists('local_course_banner_builder_elements', [
                'sourcetype' => self::SOURCE_TYPE_CUSTOMFIELD,
            ]);
            if ($hascustomfieldsources) {
                foreach ($DB->get_records('course', null, '', 'id, category') as $course) {
                    $addcourse($course);
                }
            }
        }

        $processed = 0;
        foreach ($courses as $course) {
            if (!self::course_has_applicable_banner_layers($course) && !self::course_default_image_banners_enabled()) {
                continue;
            }
            try {
                self::sync_course_overview_image($course, true);
                $processed++;
            } catch (\Throwable $e) {
                debugging('Forced course banner sync failed for course ' . $course->id . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $processed;
    }

    /**
     * Get courses that already use a plugin-managed overview image.
     *
     * @return \stdClass[]
     */
    protected static function get_courses_with_managed_overview_images(): array {
        global $DB;

        $filename = $DB->sql_like('f.filename', ':filename', false);
        $params = [
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'contextlevel' => CONTEXT_COURSE,
            'filename' => self::MANAGED_OVERVIEW_PREFIX . '%',
        ];
        $sql = "SELECT c.id, c.category, c.fullname
                  FROM {course} c
                  JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                  JOIN {files} f ON f.contextid = ctx.id
                 WHERE f.component = :component
                   AND f.filearea = :filearea
                   AND {$filename}
              ORDER BY c.id ASC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Get courses that use a teacher-managed overview image.
     *
     * @return \stdClass[]
     */
    protected static function get_courses_with_custom_overview_images(): array {
        global $DB;

        $filename = $DB->sql_like('f.filename', ':filename', false);
        $params = [
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'contextlevel' => CONTEXT_COURSE,
            'filename' => self::MANAGED_OVERVIEW_PREFIX . '%',
            'directory' => '.',
        ];
        $sql = "SELECT DISTINCT c.id, c.category, c.fullname
                  FROM {course} c
                  JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :contextlevel
                  JOIN {files} f ON f.contextid = ctx.id
                 WHERE f.component = :component
                   AND f.filearea = :filearea
                   AND f.filename <> :directory
                   AND NOT ({$filename})
              ORDER BY c.id ASC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Synchronise the managed overview image for one course.
     *
     * @param \stdClass $course
     * @param bool $forcecustomreplacement
     * @return void
     */
    public static function sync_course_overview_image(\stdClass $course, bool $forcecustomreplacement = false): void {
        if (empty($course->id) || empty($course->category)) {
            return;
        }

        $context = \context_course::instance($course->id);
        self::delete_managed_course_overview_images($context->id);
        self::delete_managed_course_card_images($context->id);

        if ($forcecustomreplacement || !self::course_custom_overview_images_enabled()) {
            self::delete_custom_course_overview_images($context->id);
        } else if (self::course_has_custom_overview_image($context->id)) {
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
        $squarecardfilepath = self::build_course_card_square_image($records, $course->id);
        if ($squarecardfilepath) {
            $fs->create_file_from_pathname([
                'contextid' => $context->id,
                'component' => 'local_course_banner_builder',
                'filearea' => self::CARD_FILEAREA,
                'itemid' => 0,
                'filepath' => '/',
                'filename' => self::MANAGED_CARD_SQUARE_PREFIX . '_' . $revision . '.png',
            ], $squarecardfilepath);
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
        $files = $fs->get_area_files($contextid, 'course', 'overviewfiles', false, 'filename', false);
        foreach ($files as $file) {
            if (str_starts_with($file->get_filename(), self::MANAGED_OVERVIEW_PREFIX)) {
                $file->delete();
            }
        }
    }

    /**
     * Delete non-plugin course overview images from a course.
     *
     * @param int $contextid
     * @return void
     */
    public static function delete_custom_course_overview_images(int $contextid): void {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'course', 'overviewfiles', false, 'filename', false);
        foreach ($files as $file) {
            if ($file->is_directory() || str_starts_with($file->get_filename(), self::MANAGED_OVERVIEW_PREFIX)) {
                continue;
            }
            $file->delete();
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
     * @param bool $square
     * @return \moodle_url|null
     */
    public static function get_course_card_image_url(int $courseid, bool $square = false): ?\moodle_url {
        if (!$courseid) {
            return null;
        }

        global $DB;
        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }
        if (self::course_custom_overview_images_enabled() && self::course_has_custom_overview_image($context->id)) {
            return null;
        }

        $course = $DB->get_record('course', ['id' => $courseid], 'id,category', IGNORE_MISSING);
        if (!$course) {
            return null;
        }
        $records = self::get_enabled_category_elements_for_course($course);
        if (empty($records)) {
            return null;
        }

        $fs = get_file_storage();
        $prefix = $square ? self::MANAGED_CARD_SQUARE_PREFIX : self::MANAGED_CARD_PREFIX;
        $expected = $prefix . '_' . self::get_layers_revision($records) . '.png';
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $file = $fs->get_file($context->id, 'local_course_banner_builder', self::CARD_FILEAREA, 0, '/', $expected);
            if ($file && $file->is_valid_image()) {
                return \moodle_url::make_pluginfile_url(
                    $context->id,
                    'local_course_banner_builder',
                    self::CARD_FILEAREA,
                    0,
                    '/',
                    $file->get_filename()
                );
            }
            self::sync_course_overview_image($course);
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
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename DESC', false);
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
     * Get the first custom Moodle course overview image URL when it should override generated banners.
     *
     * @param int $courseid
     * @return \moodle_url|null
     */
    public static function get_custom_course_overview_image_url(int $courseid): ?\moodle_url {
        if (!$courseid || !self::course_custom_overview_images_enabled()) {
            return null;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename DESC', false);
        foreach ($files as $file) {
            if (!$file->is_valid_image() || str_starts_with($file->get_filename(), self::MANAGED_OVERVIEW_PREFIX)) {
                continue;
            }
            return \moodle_url::make_pluginfile_url(
                $context->id,
                'course',
                'overviewfiles',
                null,
                '/',
                $file->get_filename()
            );
        }

        return null;
    }

    /**
     * Get Moodle's generated course pattern image URL.
     *
     * @param int $courseid
     * @return \moodle_url|null
     */
    public static function get_generated_course_image_url(int $courseid): ?\moodle_url {
        if ($courseid <= SITEID) {
            return null;
        }

        $context = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return null;
        }

        return \moodle_url::make_pluginfile_url(
            $context->id,
            'course',
            'generated',
            null,
            '/',
            'course.svg'
        );
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
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename DESC', false);
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
        $files = $fs->get_area_files($contextid, 'course', 'overviewfiles', false, 'filename', false);
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
        $parts = ['render:11:' . self::CARD_CANVAS_WIDTH . 'x' . self::CARD_CANVAS_HEIGHT];
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
        $adminpath = self::is_site_source($source)
            ? '/local/course_banner_builder/admin_site.php'
            : '/local/course_banner_builder/admin_manage.php';
        $settings = self::get_source_settings($source);
        $hasstoredsettings = !empty($settings->id);
        $compositionmode = $settings->compositionmode ?? self::MODE_CUMULATIVE;
        $elements = [];
        foreach (self::get_source_elements($source) as $record) {
            $imageurl = self::get_banner_image_url($record);
            $formid = 'layer-inline-' . $record->id;
            $fitoverride = '';
            if (self::table_field_exists('local_course_banner_builder_elements', 'fitmodeoverride')) {
                $fitoverride = (string)($record->fitmodeoverride ?? '');
            }
            $sourcefitmode = $settings->fitmode ?? self::FIT_MODE_BANNER;
            if ($fitoverride === $sourcefitmode) {
                $fitoverride = '';
            }
            $bordersummary = self::export_border_summary($record);
            $overlaysummary = self::export_overlay_summary($record);
            $layersummary = self::export_layer_display_summary($record);
            $isborderlayer = self::is_border_only_layer($record);
            $isoverlaylayer = !empty($record->overlayenabled);
            $layerpreviewhtml = self::render_admin_layer_visual_preview($record);
            $isdynamiclayer = self::is_top_image_layer($record);
            $isaboveoverlaylayer = self::is_overlay_top_image_layer($record);
            $isbelowinheritedlayer = self::is_below_inherited_image_layer($record);
            $isaboveinheritedlayer = self::is_above_inherited_image_layer($record);
            $iscroppedlayer = !empty(self::normalise_image_crop($record)['enabled']);
            $iscenterfixedlayer = !empty($record->imagecenterfixed);
            $islockedlayer = self::is_border_only_layer($record) || $isoverlaylayer ||
                $isdynamiclayer || $isaboveoverlaylayer || $isbelowinheritedlayer || $isaboveinheritedlayer;
            $rowclass = 'local-course-banner-builder-layer-row';
            if ($isborderlayer) {
                $rowclass .= ' local-course-banner-builder-layer-row--border local-course-banner-builder-layer-row--order-locked';
            } else if ($isoverlaylayer) {
                $rowclass .= ' local-course-banner-builder-layer-row--border local-course-banner-builder-layer-row--overlay local-course-banner-builder-layer-row--order-locked';
            } else if ($isdynamiclayer || $isaboveoverlaylayer || $isbelowinheritedlayer || $isaboveinheritedlayer) {
                $rowclass .= ' local-course-banner-builder-layer-row--order-locked';
                if ($isdynamiclayer) {
                    $rowclass .= ' local-course-banner-builder-layer-row--dynamic';
                }
                if ($isaboveoverlaylayer) {
                    $rowclass .= ' local-course-banner-builder-layer-row--above-overlay';
                }
                if ($isbelowinheritedlayer) {
                    $rowclass .= ' local-course-banner-builder-layer-row--below-inherited';
                }
                if ($isaboveinheritedlayer) {
                    $rowclass .= ' local-course-banner-builder-layer-row--above-inherited';
                }
            }
            $isabovebothlayer = $isdynamiclayer && $isaboveoverlaylayer;
            $orderlocklabel = $isborderlayer
                ? get_string('borderlayerlockedorderlabel', 'local_course_banner_builder')
                : ($isoverlaylayer
                    ? get_string('overlaylayerlockedorderlabel', 'local_course_banner_builder')
                    : ($isbelowinheritedlayer
                        ? get_string('imagebelowinheritedenabled', 'local_course_banner_builder')
                    : ($isaboveinheritedlayer
                        ? get_string('imageaboveinheritedenabled', 'local_course_banner_builder')
                    : ($isabovebothlayer
                        ? get_string('imageaboveborderandoverlayenabled', 'local_course_banner_builder')
                        : ($isaboveoverlaylayer
                        ? get_string('imageaboveoverlayenabled', 'local_course_banner_builder')
                            : get_string('imageaboveborderenabled', 'local_course_banner_builder'))))));
            $orderlockhelp = $isborderlayer
                ? get_string('borderlayerlockedorderhelp', 'local_course_banner_builder')
                : ($isoverlaylayer
                    ? get_string('overlaylayerlockedorderhelp', 'local_course_banner_builder')
                    : ($isbelowinheritedlayer
                        ? get_string('imagebelowinheritedenabled_help', 'local_course_banner_builder')
                    : ($isaboveinheritedlayer
                        ? get_string('imageaboveinheritedenabled_help', 'local_course_banner_builder')
                    : ($isabovebothlayer
                        ? get_string('imageaboveborderandoverlayenabled_help', 'local_course_banner_builder')
                        : ($isaboveoverlaylayer
                        ? get_string('imageaboveoverlayenabled_help', 'local_course_banner_builder')
                            : get_string('imageaboveborderenabled_help', 'local_course_banner_builder'))))));
            $elements[] = [
                'id' => (int)$record->id,
                'rowclass' => $rowclass,
                'formid' => $formid,
                'name' => $record->name ?: get_string('bannerimage', 'local_course_banner_builder') . ' #' . $record->id,
                'sortorder' => (int)$record->sortorder,
                'isborderlayer' => $isborderlayer,
                'isoverlaylayer' => $isoverlaylayer,
                'isdynamiclayer' => $isdynamiclayer,
                'isaboveoverlaylayer' => $isaboveoverlaylayer,
                'isbelowinheritedlayer' => $isbelowinheritedlayer,
                'isaboveinheritedlayer' => $isaboveinheritedlayer,
                'iscenterfixedlayer' => $iscenterfixedlayer,
                'isreorderable' => !$islockedlayer,
                'hasorderlockhelp' => $islockedlayer,
                'orderlocklabel' => $orderlocklabel,
                'orderlockicon' => $isborderlayer
                    ? 'fa-lock'
                    : ($isoverlaylayer
                        ? 'fa-lock'
                        : ($isbelowinheritedlayer
                            ? 'fa-level-down'
                            : ($isaboveinheritedlayer
                                ? 'fa-layer-group'
                                : ($isabovebothlayer ? 'fa-clone' : ($isaboveoverlaylayer ? 'fa-layer-group' : 'fa-level-up'))))),
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
                'aboveborderlabel' => get_string('imageaboveborderenabled', 'local_course_banner_builder'),
                'aboveborderpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imageaboveborderenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'aboveoverlaylabel' => get_string('imageaboveoverlayenabled', 'local_course_banner_builder'),
                'aboveoverlaypopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imageaboveoverlayenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'belowinheritedlabel' => get_string('imagebelowinheritedenabled', 'local_course_banner_builder'),
                'belowinheritedpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imagebelowinheritedenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'aboveinheritedlabel' => get_string('imageaboveinheritedenabled', 'local_course_banner_builder'),
                'aboveinheritedpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imageaboveinheritedenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'centerfixedlabel' => get_string('imagecenterfixed', 'local_course_banner_builder'),
                'centerfixedpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imagecenterfixed_help', 'local_course_banner_builder') .
                    '</p></div>',
                'iscroppedlayer' => $iscroppedlayer,
                'croppedlabel' => get_string('croppedlayerthumbnail', 'local_course_banner_builder'),
                'croppedpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('croppedlayerthumbnail_help', 'local_course_banner_builder') .
                    '</p></div>',
                'categoryid' => $categoryid,
                'sourcekey' => $source->sourcekey,
                'sesskey' => sesskey(),
                'showfitoverrideselect' => !$isborderlayer && !$isoverlaylayer,
                'fitoverrideoptions' => ($isborderlayer || $isoverlaylayer) ? [] : self::export_fit_override_options($fitoverride, $sourcefitmode),
                'hasfitoverride' => !$isborderlayer && !$isoverlaylayer && $fitoverride !== '',
                'fitoverridehelp' => get_string('fitoverridehelp', 'local_course_banner_builder'),
                'fitoverridecellclass' => (!$isborderlayer && !$isoverlaylayer && $fitoverride !== '') ? 'local-course-banner-builder-override-cell' : '',
                'fitoverridecellstyle' => self::get_layer_override_cell_style(
                    $record,
                    $fitoverride !== '' || $isborderlayer || $isoverlaylayer
                ),
                'fitoverridedisplay' => '',
                'haslayerpreviewhtml' => $layerpreviewhtml !== '',
                'layerpreviewhtml' => $layerpreviewhtml,
                'haslayersummary' => !empty($layersummary),
                'layersummaryitems' => $layersummary,
                'hasbordersummary' => !empty($bordersummary),
                'bordersummarytitle' => get_string('bordertitle', 'local_course_banner_builder'),
                'bordersummaryitems' => $bordersummary,
                'hasoverlaysummary' => !empty($overlaysummary),
                'overlaysummarytitle' => get_string('overlaytitle', 'local_course_banner_builder'),
                'overlaysummaryitems' => $overlaysummary,
                'editlabel' => $isborderlayer
                    ? get_string('editborderlayer', 'local_course_banner_builder')
                    : ($isoverlaylayer
                        ? get_string('editoverlaylayer', 'local_course_banner_builder')
                        : get_string('editimage', 'local_course_banner_builder')),
                'editurl' => (new \moodle_url($adminpath, [
                    'sourcekey' => $source->sourcekey,
                    'elementid' => $record->id,
                ]))->out(false),
                'deleteurl' => (new \moodle_url($adminpath, [
                    'sourcekey' => $source->sourcekey,
                    'deleteelementid' => $record->id,
                    'sesskey' => sesskey(),
                ]))->out(false),
            ];
        }
        $chainborderlayer = self::source_has_border_layer($source) ? [] : self::export_source_chain_border_row($source);
        $chainoverlaylayer = self::source_has_overlay_layer($source) ? [] : self::export_source_chain_overlay_row($source);
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

        if (!self::is_site_source($source)) {
            $parentkey = (string)($settings->sourceparentkey ?? '');
            $parentsource = $parentkey !== '' ? self::resolve_source($parentkey) : null;
            $parentoptions = self::get_configured_source_parent_options($source->sourcekey);
            if ($parentkey !== '' && !array_key_exists($parentkey, $parentoptions)) {
                $parentkey = '';
                $parentsource = null;
            }
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
                'isreadonly' => count($parentoptions) <= 1,
                'options' => self::export_inline_setting_options(
                    $parentoptions,
                    $parentkey
                ),
            ];
        }

        if ($source->type === self::SOURCE_TYPE_CUSTOMFIELD) {
            $summaryfields[] = [
                'fieldname' => 'customfieldpriority',
                'fieldid' => 'local-course-banner-builder-summary-customfieldpriority',
                'label' => get_string('customfieldpriority', 'local_course_banner_builder'),
                'displayvalue' => self::get_customfield_priority_options()[$settings->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY] ?? '',
                'helptext' => get_string('customfieldpriority_help', 'local_course_banner_builder'),
                'options' => self::export_inline_setting_options(
                    self::get_customfield_priority_options(),
                    $settings->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY
                ),
            ];
        }

        $issitesource = self::is_site_source($source);

        return [
            'hasselection' => true,
            'categoryid' => $categoryid,
            'sourcekey' => $source->sourcekey,
            'categoryname' => $source->label,
            'hasconfiguration' => $hasconfiguration,
            'compositionmodelabel' => self::get_source_mode_label($source),
            'iscumulative' => $compositionmode === self::MODE_CUMULATIVE,
            'fitmodelabel' => self::get_source_fit_mode_label($source),
            'deletecategoryurl' => (new \moodle_url($adminpath, [
                'sourcekey' => $source->sourcekey,
                'deletesourcecontent' => $source->sourcekey,
            ]))->out(false),
            'deletecategoryimagesurl' => (new \moodle_url($adminpath, [
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
            'haschainoverlaylayer' => !empty($chainoverlaylayer),
            'chainoverlaylayer' => $chainoverlaylayer,
            'summaryfields' => $summaryfields,
            'hassummaryfields' => !empty($summaryfields),
            'sourcelayerslistlabel' => get_string(
                $issitesource ? 'sitebannersourcelayerslist' : 'sourcelayerslist',
                'local_course_banner_builder'
            ),
            'nosourcelayerslabel' => get_string(
                $issitesource ? 'nositebannersourcelayers' : 'nosourcelayers',
                'local_course_banner_builder'
            ),
            'selectedsourcestatuslabel' => get_string(
                $issitesource ? 'selectedsitebannersourcestatus' : 'selectedcategorystatus',
                'local_course_banner_builder'
            ),
            'sourcesettingsshortlabel' => get_string(
                $issitesource ? 'sitebannersourcesettingsshort' : 'sourcesettingsshort',
                'local_course_banner_builder'
            ),
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
        $layerpreviewhtml = self::render_admin_layer_visual_preview($borderrecord);

        return [
            'rowclass' => 'local-course-banner-builder-layer-row--border local-course-banner-builder-layer-row--chain-border',
            'actionlabel' => get_string('chainborderexistinglabel', 'local_course_banner_builder'),
            'name' => $borderrecord->name ?: get_string('bannerimage', 'local_course_banner_builder') . ' #' . $borderrecord->id,
            'sourcelabel' => $bordersource->label ?? $bordersource->sourcekey,
            'enabledlabel' => $borderrecord->isenabled ? get_string('yes') : get_string('no'),
            'haslayerpreviewhtml' => $layerpreviewhtml !== '',
            'layerpreviewhtml' => $layerpreviewhtml,
            'hasbordersummary' => !empty($bordersummary),
            'bordersummarytitle' => get_string('bordertitle', 'local_course_banner_builder'),
            'bordersummaryitems' => $bordersummary,
            'sourceediturl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                'sourcekey' => $bordersource->sourcekey,
            ]))->out(false),
        ];
    }

    /**
     * Export the inherited overlay row shown in the selected source layer table.
     *
     * @param \stdClass $source
     * @return array
     */
    protected static function export_source_chain_overlay_row(\stdClass $source): array {
        $chainoverlay = self::get_source_chain_overlay_layer($source);
        if (!$chainoverlay) {
            return [];
        }

        $overlaysource = $chainoverlay['source'];
        $overlayrecord = $chainoverlay['record'];
        $overlaysummary = self::export_overlay_summary($overlayrecord);
        $layerpreviewhtml = self::render_admin_layer_visual_preview($overlayrecord);

        return [
            'rowclass' => 'local-course-banner-builder-layer-row--border local-course-banner-builder-layer-row--overlay ' .
                'local-course-banner-builder-layer-row--chain-border',
            'actionlabel' => get_string('chainoverlayexistinglabel', 'local_course_banner_builder'),
            'orderlocklabel' => get_string('overlaylayerlockedorderlabel', 'local_course_banner_builder'),
            'orderlockpopovercontent' => '<div class="no-overflow"><p>' .
                get_string('overlaylayerlockedorderhelp', 'local_course_banner_builder') .
                '</p></div>',
            'name' => $overlayrecord->name ?: get_string('layeroverlay', 'local_course_banner_builder') . ' #' .
                $overlayrecord->id,
            'sourcelabel' => $overlaysource->label ?? $overlaysource->sourcekey,
            'enabledlabel' => $overlayrecord->isenabled ? get_string('yes') : get_string('no'),
            'haslayerpreviewhtml' => $layerpreviewhtml !== '',
            'layerpreviewhtml' => $layerpreviewhtml,
            'hasoverlaysummary' => !empty($overlaysummary),
            'overlaysummarytitle' => get_string('overlaytitle', 'local_course_banner_builder'),
            'overlaysummaryitems' => $overlaysummary,
            'sourceediturl' => (new \moodle_url('/local/course_banner_builder/admin_manage.php', [
                'sourcekey' => $overlaysource->sourcekey,
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

        if (!empty($record->overlayenabled)) {
            $rgb = self::parse_color_to_rgba((string)($record->overlaybannercolor ?? '#000000'));
            if ($rgb) {
                return 'background-color: rgba(' . $rgb[0] . ', ' . $rgb[1] . ', ' . $rgb[2] . ', 0.10);';
            }
        }

        return 'background-color: rgba(201, 102, 26, 0.12);';
    }

    /**
     * Render the compact visual preview used in the selected source layer table.
     *
     * @param \stdClass $record
     * @return string
     */
    protected static function render_admin_layer_visual_preview(\stdClass $record): string {
        if (self::is_border_only_layer($record)) {
            $layer = self::export_modal_preview_border_layer($record, false, false);
            $parts = '';
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                $parts .= \html_writer::span(
                    '',
                    'local-course-banner-builder-border-preview-side local-course-banner-builder-border-preview-side-' . $side,
                    ['style' => (string)(($layer['sidestyles'] ?? [])[$side] ?? '')]
                );
            }
            foreach (['top-left', 'top-right', 'bottom-right', 'bottom-left'] as $corner) {
                $parts .= \html_writer::span(
                    '',
                    'local-course-banner-builder-border-preview-corner local-course-banner-builder-border-preview-corner-' . $corner
                );
            }
            $parts .= \html_writer::span('', 'local-course-banner-builder-border-preview-hole');
            $preview = \html_writer::div($parts, 'local-course-banner-builder-preview-border-layer', [
                'style' => (string)($layer['wrapperstyle'] ?? ''),
                'aria-hidden' => 'true',
            ]);
            return \html_writer::div(
                \html_writer::div($preview, 'local-course-banner-builder-border-preview-frame'),
                'local-course-banner-builder-admin-layer-visual local-course-banner-builder-admin-layer-visual--border'
            );
        }

        if (!empty($record->overlayenabled)) {
            $layer = self::export_modal_preview_overlay_layer($record, false, false);
            $preview = \html_writer::div('', 'local-course-banner-builder-banner-overlay-layer', [
                'style' => (string)($layer['wrapperstyle'] ?? ''),
                'aria-hidden' => 'true',
            ]);
            return \html_writer::div(
                \html_writer::div($preview, 'local-course-banner-builder-admin-layer-overlay-frame'),
                'local-course-banner-builder-admin-layer-visual local-course-banner-builder-admin-layer-visual--overlay'
            );
        }

        return '';
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
        $course = self::ensure_course_context_fields($course);
        $categoryrecords = self::get_category_layer_specs_for_course($course);
        $customfieldrecords = self::get_customfield_layer_specs_for_course($course);
        if (empty($customfieldrecords)) {
            return $categoryrecords;
        }

        $settings = self::get_source_settings($customfieldrecords[0]['source']);
        $priority = $settings->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY;
        if ($priority === self::CUSTOMFIELD_PRIORITY_CUSTOMFIELD) {
            return $customfieldrecords;
        }
        if ($priority === self::CUSTOMFIELD_PRIORITY_PREPEND) {
            if (empty($categoryrecords)) {
                return $customfieldrecords;
            }

            $mincategoryorder = 0;
            foreach ($categoryrecords as $layerspec) {
                $mincategoryorder = min($mincategoryorder, (int)($layerspec['categoryorder'] ?? 0));
            }

            foreach ($customfieldrecords as $index => $layerspec) {
                $customfieldrecords[$index]['categoryorder'] = $mincategoryorder - 1;
            }

            return self::sort_layer_specs(array_merge($customfieldrecords, $categoryrecords));
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
            self::get_layer_specs_for_source_chain($source, (int)($course->category ?? 0), 0, [], (int)$course->id)
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

            $sourcekeys = [self::get_customfield_source_key($fieldid, $rawvalue)];
            foreach ([self::extract_customfield_data_raw_value($field, $record), $rawvalue] as $legacyvalue) {
                $legacykey = self::get_legacy_customfield_source_key($fieldid, (string)$legacyvalue);
                if (!in_array($legacykey, $sourcekeys, true)) {
                    $sourcekeys[] = $legacykey;
                }
            }
            foreach ($sourcekeys as $sourcekey) {
                $source = self::resolve_source($sourcekey);
                if (!$source || empty(self::get_source_elements($source, true))) {
                    continue;
                }
                return $source;
            }
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
        $hastop = in_array('top', $sides, true);
        $hasright = in_array('right', $sides, true);
        $hasbottom = in_array('bottom', $sides, true);
        $hasleft = in_array('left', $sides, true);
        $rounded = !empty($borderrecord->borderinnerrounded);
        $topleftoffset = ($hastop && $hasleft) ? ($rounded ? $cutout : $squareoffset) : $zerowidth;
        $toprightoffset = ($hastop && $hasright) ? ($rounded ? $cutout : $squareoffset) : $zerowidth;
        $bottomleftoffset = ($hasbottom && $hasleft) ? ($rounded ? $cutout : $squareoffset) : $zerowidth;
        $bottomrightoffset = ($hasbottom && $hasright) ? ($rounded ? $cutout : $squareoffset) : $zerowidth;
        $topleftcorner = ($hastop && $hasleft) ? ($rounded ? $cutout : $activewidth) : $zerowidth;
        $toprightcorner = ($hastop && $hasright) ? ($rounded ? $cutout : $activewidth) : $zerowidth;
        $bottomrightcorner = ($hasbottom && $hasright) ? ($rounded ? $cutout : $activewidth) : $zerowidth;
        $bottomleftcorner = ($hasbottom && $hasleft) ? ($rounded ? $cutout : $activewidth) : $zerowidth;

        $stylestring = implode(' ', [
            '--page-header-border-top-width: ' . ($hastop ? $activewidth : $zerowidth) . ';',
            '--page-header-border-right-width: ' . ($hasright ? $activewidth : $zerowidth) . ';',
            '--page-header-border-bottom-width: ' . ($hasbottom ? $activewidth : $zerowidth) . ';',
            '--page-header-border-left-width: ' . ($hasleft ? $activewidth : $zerowidth) . ';',
            '--page-header-border-top-left-offset: ' . $topleftoffset . ';',
            '--page-header-border-top-right-offset: ' . $toprightoffset . ';',
            '--page-header-border-bottom-left-offset: ' . $bottomleftoffset . ';',
            '--page-header-border-bottom-right-offset: ' . $bottomrightoffset . ';',
            '--page-header-border-right-top-offset: ' . $toprightoffset . ';',
            '--page-header-border-right-bottom-offset: ' . $bottomrightoffset . ';',
            '--page-header-border-left-top-offset: ' . $topleftoffset . ';',
            '--page-header-border-left-bottom-offset: ' . $bottomleftoffset . ';',
            '--page-header-border-top-left-corner-size: ' . $topleftcorner . ';',
            '--page-header-border-top-right-corner-size: ' . $toprightcorner . ';',
            '--page-header-border-bottom-right-corner-size: ' . $bottomrightcorner . ';',
            '--page-header-border-bottom-left-corner-size: ' . $bottomleftcorner . ';',
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
            $value = self::extract_customfield_data_raw_value($field, $data);
            return ((int)$value > 0) ? (string)(int)$value : '';
        }

        if (in_array($field->type, ['text', 'textarea'], true)) {
            return self::normalise_customfield_value(self::extract_customfield_data_raw_value($field, $data));
        }

        return '';
    }

    /**
     * Extract the raw stored value from Moodle custom field data.
     *
     * @param \stdClass $field
     * @param \stdClass $data
     * @return string
     */
    protected static function extract_customfield_data_raw_value(\stdClass $field, \stdClass $data): string {
        if ($field->type === 'select') {
            $value = $data->intvalue ?? null;
            if ((int)$value > 0) {
                return (string)(int)$value;
            }
            return (string)($data->charvalue ?? $data->shortcharvalue ?? $data->value ?? '');
        }

        if (in_array($field->type, ['text', 'textarea'], true)) {
            return (string)($data->charvalue ?? $data->shortcharvalue ?? $data->value ?? '');
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
                self::get_layer_specs_for_source_chain(
                    $chainsource,
                    $targetcategoryid,
                    $categoryindex,
                    [],
                    (int)($course->id ?? 0)
                )
            );
        }

        return self::sort_layer_specs($records);
    }

    /**
     * Return the resolved layer specs for one source and its parent chain.
     *
     * @param \stdClass $source Source record.
     * @param int $targetcategoryid Target course category id.
     * @param int $categoryorder Order offset inherited from the target category.
     * @param array $visited Source keys already visited.
     * @param int $courseid Course id used for random layer selection.
     * @param bool $expandrandom Whether to expose all random-mode image layers for admin previews.
     * @return array
     */
    protected static function get_layer_specs_for_source_chain(
        \stdClass $source,
        int $targetcategoryid,
        int $categoryorder,
        array $visited = [],
        int $courseid = 0,
        bool $expandrandom = false
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
                    self::get_layer_specs_for_source_chain(
                        $parentsource,
                        $targetcategoryid,
                        $categoryorder - 1,
                        $visited,
                        $courseid,
                        $expandrandom
                    )
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
        if ($compositionmode === self::MODE_RANDOM && !$expandrandom) {
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
                $selectedrecord = self::select_random_source_record($imagerecords, $source, $targetcategoryid, $courseid);
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
     * @param int $courseid
     * @return \stdClass
     */
    protected static function select_random_source_record(
        array $imagerecords,
        \stdClass $source,
        int $targetcategoryid,
        int $courseid = 0
    ): \stdClass {
        $count = count($imagerecords);
        if ($count <= 1) {
            return reset($imagerecords);
        }

        usort($imagerecords, static function (\stdClass $a, \stdClass $b): int {
            $sortcompare = ((int)$a->sortorder) <=> ((int)$b->sortorder);
            if ($sortcompare !== 0) {
                return $sortcompare;
            }
            return ((int)$a->id) <=> ((int)$b->id);
        });

        $seedparts = [
            (string)($source->sourcekey ?? ''),
            'category:' . $targetcategoryid,
        ];
        if ($courseid > 0) {
            $seedparts[] = 'course:' . $courseid;
        }
        $seed = implode(':', $seedparts);
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
        $overlayrecord = self::get_active_overlay_record_from_layer_specs($layerspecs);

        usort($layerspecs, static function (array $a, array $b) use ($overlayrecord): int {
            $arecord = $a['record'];
            $brecord = $b['record'];
            $aband = self::get_layer_draw_band($arecord, $overlayrecord);
            $bband = self::get_layer_draw_band($brecord, $overlayrecord);
            if ($aband !== $bband) {
                return $aband <=> $bband;
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
        return $labels[$mode] ?? $labels[self::MODE_CUMULATIVE];
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
        return $labels[$mode] ?? $labels[self::MODE_CUMULATIVE];
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

            $isaboveborder = self::is_top_image_layer($element);
            $isaboveoverlay = self::is_overlay_top_image_layer($element);
            $isaboveboth = $isaboveborder && $isaboveoverlay;
            $isbelowinherited = self::is_below_inherited_image_layer($element);
            $isaboveinherited = self::is_above_inherited_image_layer($element);

            $thumbnails[] = [
                'imageurl' => $imageurl->out(false),
                'name' => $element->name ?: get_string('bannerimage', 'local_course_banner_builder') . ' #' . $element->id,
                'isaboveborder' => $isaboveborder && !$isaboveboth,
                'aboveborderlabel' => get_string('imageaboveborderenabled', 'local_course_banner_builder'),
                'aboveborderpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imageaboveborderenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'isaboveoverlay' => $isaboveoverlay && !$isaboveboth,
                'aboveoverlaylabel' => get_string('imageaboveoverlayenabled', 'local_course_banner_builder'),
                'aboveoverlaypopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imageaboveoverlayenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'isaboveboth' => $isaboveboth,
                'abovebothlabel' => get_string('imageaboveborderandoverlayenabled', 'local_course_banner_builder'),
                'abovebothpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imageaboveborderandoverlayenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'isbelowinherited' => $isbelowinherited,
                'belowinheritedlabel' => get_string('imagebelowinheritedenabled', 'local_course_banner_builder'),
                'belowinheritedpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imagebelowinheritedenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'isaboveinherited' => $isaboveinherited,
                'aboveinheritedlabel' => get_string('imageaboveinheritedenabled', 'local_course_banner_builder'),
                'aboveinheritedpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imageaboveinheritedenabled_help', 'local_course_banner_builder') .
                    '</p></div>',
                'iscenterfixed' => !empty($element->imagecenterfixed),
                'centerfixedlabel' => get_string('imagecenterfixed', 'local_course_banner_builder'),
                'centerfixedpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('imagecenterfixed_help', 'local_course_banner_builder') .
                    '</p></div>',
                'iscropped' => !empty(self::normalise_image_crop($element)['enabled']),
                'croppedlabel' => get_string('croppedlayerthumbnail', 'local_course_banner_builder'),
                'croppedpopovercontent' => '<div class="no-overflow"><p>' .
                    get_string('croppedlayerthumbnail_help', 'local_course_banner_builder') .
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
     * Determine effective fit mode for one layer record on a target course category.
     *
     * @param \stdClass $record
     * @param int $targetcategoryid
     * @return string
     */
    protected static function get_effective_fit_mode_for_record(\stdClass $record, int $targetcategoryid): string {
        $fitmodeoverride = '';
        if (self::table_field_exists('local_course_banner_builder_elements', 'fitmodeoverride')) {
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
     * @param string $sourcefitmode
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
                $value = self::normalise_percentage((float)($record->{'offset' . $side . 'percent'} ?? 0), -1000.0, 1000.0);
                if ($value <= 0) {
                    continue;
                }
                $offsets[] = get_string('bordersides:' . $side, 'local_course_banner_builder') . ' ' .
                    self::format_css_offset_percentage($value);
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
        if (!empty($record->imageaboveoverlayenabled)) {
            $items[] = [
                'label' => get_string('imageaboveoverlayenabled', 'local_course_banner_builder'),
                'value' => get_string('yes'),
            ];
        }
        if (!empty($record->imagebelowinheritedenabled)) {
            $items[] = [
                'label' => get_string('imagebelowinheritedenabled', 'local_course_banner_builder'),
                'value' => get_string('yes'),
            ];
        }
        if (!empty($record->imageaboveinheritedenabled)) {
            $items[] = [
                'label' => get_string('imageaboveinheritedenabled', 'local_course_banner_builder'),
                'value' => get_string('yes'),
            ];
        }
        if (!empty($record->imagecenterfixed)) {
            $items[] = [
                'label' => get_string('imagecenterfixed', 'local_course_banner_builder'),
                'value' => get_string('yes'),
            ];
        }
        $imageopacity = (float)($record->imageopacity ?? 1);
        if ($imageopacity < 0.999) {
            $items[] = [
                'label' => get_string('imageopacity', 'local_course_banner_builder'),
                'value' => self::format_css_percentage(round($imageopacity * 100, 2)),
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
            : implode(', ', array_map(static function (string $side): string {
                return get_string('bordersides:' . $side, 'local_course_banner_builder');
            }, $sides));
        $bordercolor = self::normalise_color_string((string)($record->bordercolor ?? '#FFFFFF'));

        return [
            [
                'label' => get_string('bordercolor', 'local_course_banner_builder'),
                'value' => $bordercolor,
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
     * Export a readable overlay summary for one layer row.
     *
     * @param \stdClass $record
     * @return array
     */
    protected static function export_overlay_summary(\stdClass $record): array {
        if (empty($record->overlayenabled)) {
            return [];
        }

        $target = self::normalise_overlay_target((string)($record->overlaytarget ?? self::OVERLAY_TARGET_BOTH));
        return [
            [
                'label' => get_string('overlaytarget', 'local_course_banner_builder'),
                'value' => self::get_overlay_target_options()[$target] ?? $target,
            ],
            [
                'label' => get_string('overlaycolor', 'local_course_banner_builder'),
                'value' => self::normalise_color_string((string)($record->overlaybannercolor ?? '#000000')),
            ],
            [
                'label' => get_string('overlayopacity', 'local_course_banner_builder'),
                'value' => self::format_css_percentage((float)($record->overlaybanneropacity ?? 25)),
            ],
            [
                'label' => get_string('overlaytitleabove', 'local_course_banner_builder'),
                'value' => !empty($record->overlaytitleabove) ? get_string('yes') : get_string('no'),
            ],
            [
                'label' => get_string('overlayborderabove', 'local_course_banner_builder'),
                'value' => !empty($record->overlayborderabove) ? get_string('yes') : get_string('no'),
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
     * @param bool $expandrandom
     * @return array
     */
    protected static function get_preview_layer_specs_for_source(\stdClass $source, bool $expandrandom = false): array {
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
                        self::get_layer_specs_for_source_chain(
                            $chainsource,
                            $targetcategoryid,
                            $categoryindex,
                            [],
                            0,
                            $expandrandom
                        )
                    );
                }
            }

            return self::sort_layer_specs($layers);
        }

        return self::sort_layer_specs(self::get_layer_specs_for_source_chain($source, 0, 0, [], 0, $expandrandom));
    }

    /**
     * Export one preview layer.
     *
     * @param array $layerspec
     * @param bool $iscontext
     * @param bool $isinherited
     * @param float|null $banneraspect
     * @param bool $includealloverlays
     * @return array|null
     */
    protected static function export_modal_preview_layer(
        array $layerspec,
        bool $iscontext,
        bool $isinherited,
        ?float $banneraspect = null,
        bool $includealloverlays = false
    ): ?array {
        $record = $layerspec['record'];
        if (self::get_banner_image_file($record)) {
            return self::export_modal_preview_image_layer(
                $record,
                (string)($layerspec['fitmode'] ?? self::FIT_MODE_BANNER),
                $iscontext,
                $isinherited,
                $banneraspect
            );
        }

        if (!empty($record->borderenabled)) {
            return self::export_modal_preview_border_layer($record, $iscontext, $isinherited);
        }

        if (self::record_overlay_targets_banner($record) ||
                ($includealloverlays && self::record_overlay_targets_slideshow($record))) {
            return self::export_modal_preview_overlay_layer($record, $iscontext, $isinherited);
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
     * @param float|null $banneraspect
     * @return array|null
     */
    protected static function export_modal_preview_image_layer(
        \stdClass $record,
        string $fitmode,
        bool $iscontext,
        bool $isinherited,
        ?float $banneraspect = null
    ): ?array {
        $file = self::get_banner_image_file($record);
        $imageurl = self::get_banner_image_url($record);
        if (!$file || !$imageurl) {
            return null;
        }

        $styles = self::build_modal_preview_image_layer_styles($record, $fitmode, $file, $banneraspect);
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
            'imagecenterfixed' => !empty($record->imagecenterfixed),
            'imageaboveoverlayenabled' => !empty($record->imageaboveoverlayenabled),
            'imagebelowinheritedenabled' => !empty($record->imagebelowinheritedenabled),
            'imageaboveinheritedenabled' => !empty($record->imageaboveinheritedenabled),
            'imageopacity' => (float)round(((float)($record->imageopacity ?? 1)) * 100, 2),
            'imagecropenabled' => !empty($record->imagecropenabled),
            'imagecropleftpercent' => (float)($record->imagecropleftpercent ?? 0),
            'imagecroptoppercent' => (float)($record->imagecroptoppercent ?? 0),
            'imagecropwidthpercent' => (float)($record->imagecropwidthpercent ?? 100),
            'imagecropheightpercent' => (float)($record->imagecropheightpercent ?? 100),
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
     * Export one overlay preview layer.
     *
     * @param \stdClass $record
     * @param bool $iscontext
     * @param bool $isinherited
     * @return array
     */
    protected static function export_modal_preview_overlay_layer(
        \stdClass $record,
        bool $iscontext,
        bool $isinherited
    ): array {
        $targetsbanner = self::record_overlay_targets_banner($record);
        $colorfield = $targetsbanner ? 'overlaybannercolor' : 'overlayslideshowcolor';
        $opacityfield = $targetsbanner ? 'overlaybanneropacity' : 'overlayslideshowopacity';
        $color = self::normalise_color_string((string)($record->{$colorfield} ?? '#000000'));
        $opacity = self::normalise_percentage((float)($record->{$opacityfield} ?? 25), 0.0, 100.0) / 100;

        return [
            'type' => 'overlay',
            'id' => (int)($record->id ?? 0),
            'name' => trim((string)($record->name ?? '')),
            'sortorder' => (int)($record->sortorder ?? 0),
            'zindex' => self::get_preview_layer_zindex($record),
            'wrapperstyle' => 'background: ' . self::css_rgba_from_color($color, $opacity) . ';',
            'target' => self::normalise_overlay_target((string)($record->overlaytarget ?? self::OVERLAY_TARGET_BOTH)),
            'overlaytitleabove' => !empty($record->overlaytitleabove),
            'overlayborderabove' => !empty($record->overlayborderabove),
            'iscontext' => $iscontext,
            'isinherited' => $isinherited,
            'enabled' => !empty($record->isenabled),
        ];
    }

    /**
     * Get the visible contained image box as percentages of the canonical banner.
     *
     * @param int $imagewidth
     * @param int $imageheight
     * @param float|null $banneraspect
     * @return array{width:float,height:float}
     */
    protected static function get_contained_overlay_box_percentages(
        int $imagewidth,
        int $imageheight,
        ?float $banneraspect = null
    ): array {
        if ($imagewidth <= 0 || $imageheight <= 0) {
            return ['width' => 100.0, 'height' => 100.0];
        }

        $imageaspect = $imagewidth / $imageheight;
        $banneraspect = $banneraspect && $banneraspect > 0
            ? $banneraspect
            : self::DEFAULT_CANVAS_WIDTH / self::DEFAULT_CANVAS_HEIGHT;
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

    /**
     * Build responsive admin preview styles for one image layer.
     *
     * @param \stdClass $record
     * @param string $fitmode
     * @param \stored_file|null $file
     * @param float|null $banneraspect
     * @return array{wrapperstyle:string,imagestyle:string}
     */
    protected static function build_modal_preview_image_layer_styles(
        \stdClass $record,
        string $fitmode,
        ?\stored_file $file,
        ?float $banneraspect = null
    ): array {
        $banneraspect = $banneraspect && $banneraspect > 0
            ? $banneraspect
            : self::DEFAULT_CANVAS_WIDTH / self::DEFAULT_CANVAS_HEIGHT;
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
            'opacity: ' . self::format_css_opacity((float)($record->imageopacity ?? 1)) . ';',
        ];

        if ($fitmode === self::FIT_MODE_BANNER) {
            $wrapperstyles[] = 'inset: 0;';
            $imagestyles[] = 'object-fit: fill;';
        } else if ($fitmode === self::FIT_MODE_COVER) {
            $imageinfo = $file ? $file->get_imageinfo() : [];
            $imagewidth = (int)($imageinfo['width'] ?? 0);
            $imageheight = (int)($imageinfo['height'] ?? 0);
            if ($imagewidth > 0 && $imageheight > 0) {
                $effectivedimensions = self::get_effective_image_dimensions_for_crop($record, $imagewidth, $imageheight);
                $box = self::get_contained_overlay_box_percentages(
                    $effectivedimensions['width'],
                    $effectivedimensions['height'],
                    $banneraspect
                );
                $wrapperstyles[] = 'width: ' . self::format_css_percentage($box['width']) . ';';
                $wrapperstyles[] = 'height: ' . self::format_css_percentage($box['height']) . ';';
                $wrapperstyles = array_merge(
                    $wrapperstyles,
                    self::get_image_layer_position_styles($record, $anchor, $box['width'], $box['height'])
                );
                $imagestyles[] = 'object-fit: fill;';
            } else {
                $wrapperstyles[] = 'inset: 0;';
                $imagestyles[] = 'object-fit: contain;';
            }
            $imagestyles[] = 'object-position: ' . self::get_css_object_position_for_anchor($anchor) . ';';
        } else if ($fitmode === self::FIT_MODE_CUSTOM) {
            $imageinfo = $file ? $file->get_imageinfo() : [];
            $imagewidth = (int)($imageinfo['width'] ?? 0);
            $imageheight = (int)($imageinfo['height'] ?? 0);
            $renderkeepaspect = !empty($record->customsizekeepaspect);
            $custombox = [
                'width' => self::normalise_percentage(
                    (float)($record->customwidthpercent ?? 100),
                    0.0,
                    self::CUSTOM_SIZE_PERCENT_MAX
                ),
                'height' => self::normalise_percentage(
                    (float)($record->customheightpercent ?? 100),
                    0.0,
                    self::CUSTOM_SIZE_PERCENT_MAX
                ),
            ];
            if ($renderkeepaspect && $imagewidth > 0 && $imageheight > 0) {
                $effectivedimensions = self::get_effective_image_dimensions_for_crop($record, $imagewidth, $imageheight);
                $custombox['height'] = $custombox['width'] *
                    ($banneraspect / ($effectivedimensions['width'] / $effectivedimensions['height']));
                $wrapperstyles[] = 'width: ' . self::format_css_percentage($custombox['width']) . ';';
                $wrapperstyles[] = 'height: auto;';
                $wrapperstyles[] = 'aspect-ratio: ' . $effectivedimensions['width'] . ' / ' .
                    $effectivedimensions['height'] . ';';
            } else {
                $wrapperstyles[] = 'width: ' . self::format_css_percentage($custombox['width']) . ';';
                $wrapperstyles[] = 'height: ' . self::format_css_percentage($custombox['height']) . ';';
            }
            $wrapperstyles = array_merge(
                $wrapperstyles,
                self::get_image_layer_position_styles($record, $anchor, $custombox['width'], $custombox['height'])
            );
            $imagestyles[] = 'object-fit: ' . ($renderkeepaspect ? 'contain' : 'fill') . ';';
            $imagestyles[] = 'object-position: ' . self::get_css_object_position_for_anchor($anchor) . ';';
        } else {
            $imageinfo = $file ? $file->get_imageinfo() : [];
            $imagewidth = (int)($imageinfo['width'] ?? 0);
            $imageheight = (int)($imageinfo['height'] ?? 0);
            if ($imagewidth <= 0) {
                $imagewidth = self::DEFAULT_CANVAS_WIDTH;
            }
            if ($imageheight <= 0) {
                $imageheight = self::DEFAULT_CANVAS_HEIGHT;
            }

            $effectivedimensions = self::get_effective_image_dimensions_for_crop($record, $imagewidth, $imageheight);
            $wrapperstyles[] = 'width: ' . self::format_css_percent($effectivedimensions['width'], self::DEFAULT_CANVAS_WIDTH) . ';';
            $originalwidth = ($effectivedimensions['width'] / self::DEFAULT_CANVAS_WIDTH) * 100;
            $originalheight = $originalwidth * ($banneraspect / ($effectivedimensions['width'] / $effectivedimensions['height']));
            $wrapperstyles = array_merge(
                $wrapperstyles,
                self::get_image_layer_position_styles($record, $anchor, $originalwidth, $originalheight)
            );
            $imagestyles[] = 'height: auto;';
            $imagestyles[] = 'object-fit: none;';
            $imagestyles[] = 'object-position: ' . self::get_css_object_position_for_anchor($anchor) . ';';
        }

        self::append_image_crop_styles($imagestyles, $record, $fitmode === self::FIT_MODE_BANNER);

        return [
            'wrapperstyle' => implode(' ', $wrapperstyles),
            'imagestyle' => implode(' ', $imagestyles),
        ];
    }

    /**
     * Append non-destructive crop styles to an image inside its layer wrapper.
     *
     * @param array $imagestyles
     * @param \stdClass $record
     * @param bool $fillwrapper
     * @return void
     */
    protected static function append_image_crop_styles(
        array &$imagestyles,
        \stdClass $record,
        bool $fillwrapper = false
    ): void {
        if (empty($record->imagecropenabled)) {
            return;
        }

        $crop = self::normalise_image_crop($record);
        if (!$crop['enabled']) {
            return;
        }

        $imagestyles[] = 'position: absolute;';
        $imagestyles[] = 'top: 0;';
        $imagestyles[] = 'left: 0;';
        $imagestyles[] = 'right: auto;';
        $imagestyles[] = 'bottom: auto;';
        $imagestyles[] = 'flex: 0 0 auto;';
        $imagestyles[] = 'width: ' . self::format_css_percentage(10000.0 / $crop['width']) . ';';
        $imagestyles[] = 'height: ' . self::format_css_percentage(10000.0 / $crop['height']) . ';';
        $imagestyles[] = 'max-width: none;';
        $imagestyles[] = 'object-fit: fill;';
        $imagestyles[] = 'object-position: left top;';
        $imagestyles[] = 'transform: translate(-' . self::format_css_percentage($crop['left']) . ', -' .
            self::format_css_percentage($crop['top']) . ');';
        $imagestyles[] = 'transform-origin: top left;';
    }

    /**
     * Get the natural dimensions that should drive placement after a stored crop.
     *
     * @param \stdClass $record
     * @param int $imagewidth
     * @param int $imageheight
     * @return array{width:int,height:int}
     */
    protected static function get_effective_image_dimensions_for_crop(
        \stdClass $record,
        int $imagewidth,
        int $imageheight
    ): array {
        $imagewidth = max(1, $imagewidth);
        $imageheight = max(1, $imageheight);
        $crop = self::normalise_image_crop($record);
        if (!$crop['enabled']) {
            return [
                'width' => $imagewidth,
                'height' => $imageheight,
            ];
        }

        return [
            'width' => max(1, (int)round($imagewidth * ($crop['width'] / 100.0))),
            'height' => max(1, (int)round($imageheight * ($crop['height'] / 100.0))),
        ];
    }

    /**
     * Normalise the stored image crop rectangle.
     *
     * @param \stdClass $record
     * @return array{enabled:bool,left:float,top:float,width:float,height:float}
     */
    protected static function normalise_image_crop(\stdClass $record): array {
        $width = self::normalise_percentage((float)($record->imagecropwidthpercent ?? 100), 1.0, 100.0);
        $height = self::normalise_percentage((float)($record->imagecropheightpercent ?? 100), 1.0, 100.0);
        $left = self::normalise_percentage((float)($record->imagecropleftpercent ?? 0), 0.0, 100.0 - $width);
        $top = self::normalise_percentage((float)($record->imagecroptoppercent ?? 0), 0.0, 100.0 - $height);

        return [
            'enabled' => !empty($record->imagecropenabled) && ($left > 0.0 || $top > 0.0 || $width < 100.0 || $height < 100.0),
            'left' => $left,
            'top' => $top,
            'width' => $width,
            'height' => $height,
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
        $sides = self::normalise_border_sides(explode(',', (string)($record->bordersides ?? 'top,right,bottom,left')));
        $opacity = self::get_effective_border_opacity((float)($record->borderopacity ?? 0));
        $fade = self::normalise_unit_float((float)($record->borderfade ?? 0), 0);
        $color = self::normalise_color_string((string)($record->bordercolor ?? '#FFFFFF'));
        $csscolors = self::build_css_color_pair($color, $opacity);
        $zerowidth = '0px';
        $haswidth = $widthpercent > 0;
        $width = self::format_border_height_relative_length($widthpercent);
        $topwidth = in_array('top', $sides, true) ? $width : $zerowidth;
        $rightwidth = in_array('right', $sides, true) ? $width : $zerowidth;
        $bottomwidth = in_array('bottom', $sides, true) ? $width : $zerowidth;
        $leftwidth = in_array('left', $sides, true) ? $width : $zerowidth;
        $verticalradiuslimit = 'max(0px, calc((100cqh - ' . $topwidth . ' - ' . $bottomwidth . ') / 2))';
        $horizontalradiuslimit = 'max(0px, calc((100cqw - ' . $leftwidth . ' - ' . $rightwidth . ') / 2))';
        $radius = !empty($record->borderinnerrounded) && $haswidth
            ? 'min(max(8px, ' . $width . '), ' . $verticalradiuslimit . ', ' . $horizontalradiuslimit . ')'
            : $zerowidth;
        $cutout = !empty($record->borderinnerrounded) && $haswidth ? 'calc(' . $width . ' + ' . $radius . ')' : $width;
        $squareoffset = $width;
        $fadestart = 'calc(' . $radius . ' + ' . self::format_border_height_relative_length($widthpercent * $fade) . ')';
        $hastop = in_array('top', $sides, true);
        $hasright = in_array('right', $sides, true);
        $hasbottom = in_array('bottom', $sides, true);
        $hasleft = in_array('left', $sides, true);
        $rounded = !empty($record->borderinnerrounded);
        $topleftradius = ($rounded && $hastop && $hasleft) ? $radius : '0px';
        $toprightradius = ($rounded && $hastop && $hasright) ? $radius : '0px';
        $bottomrightradius = ($rounded && $hasbottom && $hasright) ? $radius : '0px';
        $bottomleftradius = ($rounded && $hasbottom && $hasleft) ? $radius : '0px';
        $topleftoffset = ($hastop && $hasleft) ? ($rounded ? $cutout : $squareoffset) : $zerowidth;
        $toprightoffset = ($hastop && $hasright) ? ($rounded ? $cutout : $squareoffset) : $zerowidth;
        $bottomleftoffset = ($hasbottom && $hasleft) ? ($rounded ? $cutout : $squareoffset) : $zerowidth;
        $bottomrightoffset = ($hasbottom && $hasright) ? ($rounded ? $cutout : $squareoffset) : $zerowidth;
        $topleftcorner = ($hastop && $hasleft) ? ($rounded ? $cutout : $width) : $zerowidth;
        $toprightcorner = ($hastop && $hasright) ? ($rounded ? $cutout : $width) : $zerowidth;
        $bottomrightcorner = ($hasbottom && $hasright) ? ($rounded ? $cutout : $width) : $zerowidth;
        $bottomleftcorner = ($hasbottom && $hasleft) ? ($rounded ? $cutout : $width) : $zerowidth;

        return implode(' ', [
            'position: absolute;',
            'inset: 0;',
            '--local-course-banner-builder-preview-top-width: ' . $topwidth . ';',
            '--local-course-banner-builder-preview-right-width: ' . $rightwidth . ';',
            '--local-course-banner-builder-preview-bottom-width: ' . $bottomwidth . ';',
            '--local-course-banner-builder-preview-left-width: ' . $leftwidth . ';',
            '--local-course-banner-builder-preview-top-left-radius: ' . $topleftradius . ';',
            '--local-course-banner-builder-preview-top-right-radius: ' . $toprightradius . ';',
            '--local-course-banner-builder-preview-bottom-right-radius: ' . $bottomrightradius . ';',
            '--local-course-banner-builder-preview-bottom-left-radius: ' . $bottomleftradius . ';',
            '--local-course-banner-builder-preview-top-left-offset: ' . $topleftoffset . ';',
            '--local-course-banner-builder-preview-top-right-offset: ' . $toprightoffset . ';',
            '--local-course-banner-builder-preview-bottom-left-offset: ' . $bottomleftoffset . ';',
            '--local-course-banner-builder-preview-bottom-right-offset: ' . $bottomrightoffset . ';',
            '--local-course-banner-builder-preview-right-top-offset: ' . $toprightoffset . ';',
            '--local-course-banner-builder-preview-right-bottom-offset: ' . $bottomrightoffset . ';',
            '--local-course-banner-builder-preview-left-top-offset: ' . $topleftoffset . ';',
            '--local-course-banner-builder-preview-left-bottom-offset: ' . $bottomleftoffset . ';',
            '--local-course-banner-builder-preview-top-left-corner-size: ' . $topleftcorner . ';',
            '--local-course-banner-builder-preview-top-right-corner-size: ' . $toprightcorner . ';',
            '--local-course-banner-builder-preview-bottom-right-corner-size: ' . $bottomrightcorner . ';',
            '--local-course-banner-builder-preview-bottom-left-corner-size: ' . $bottomleftcorner . ';',
            '--local-course-banner-builder-preview-top-left-fade-start: ' . $fadestart . ';',
            '--local-course-banner-builder-preview-top-right-fade-start: ' . $fadestart . ';',
            '--local-course-banner-builder-preview-bottom-right-fade-start: ' . $fadestart . ';',
            '--local-course-banner-builder-preview-bottom-left-fade-start: ' . $fadestart . ';',
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
            if (!empty($element->borderenabled) && !empty($element->isenabled)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Count overlay-enabled elements inside one source.
     *
     * @param array $elements
     * @return int
     */
    protected static function count_overlay_elements(array $elements): int {
        $count = 0;
        foreach ($elements as $element) {
            if (!empty($element->overlayenabled) && !empty($element->isenabled)) {
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
     * @param int $overlaycount
     * @return string
     */
    protected static function format_no_thumbnail_label(int $bordercount, int $overlaycount = 0): string {
        if ($bordercount > 0 && $overlaycount > 0) {
            return get_string('borderandoverlaylayercount', 'local_course_banner_builder', (object)[
                'borders' => $bordercount,
                'overlays' => $overlaycount,
            ]);
        }
        if ($overlaycount > 0) {
            return $overlaycount . ' ' . get_string(
                $overlaycount > 1 ? 'overlaylayerplural' : 'overlaylayersingular',
                'local_course_banner_builder'
            );
        }
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
     * Build the compact non-image layer caption shown below image thumbnails.
     *
     * @param int $bordercount
     * @param int $overlaycount
     * @return string
     */
    protected static function format_additional_layer_label(int $bordercount, int $overlaycount): string {
        if ($bordercount > 0 && $overlaycount > 0) {
            return get_string('additionalborderandoverlaylayers', 'local_course_banner_builder', (object)[
                'borders' => $bordercount,
                'overlays' => $overlaycount,
            ]);
        }
        if ($overlaycount > 0) {
            return get_string('additionaloverlaylayers', 'local_course_banner_builder', $overlaycount);
        }
        return self::format_additional_border_label($bordercount);
    }

    /**
     * Delete all plugin configuration.
     *
     * @param bool $sync
     * @return void
     */
    public static function delete_all_configuration(bool $sync = true): void {
        global $DB;

        $categoryids = $DB->get_fieldset_select('local_course_banner_builder_elements', 'DISTINCT categoryid', 'categoryid IS NOT NULL');
        $managedcourses = self::get_courses_with_managed_overview_images();
        $hadcustomfieldsources = self::table_field_exists('local_course_banner_builder_elements', 'sourcetype') &&
            $DB->record_exists('local_course_banner_builder_elements', ['sourcetype' => self::SOURCE_TYPE_CUSTOMFIELD]);
        foreach ($DB->get_records('local_course_banner_builder_elements') as $element) {
            self::delete_banner_element((int)$element->id, false);
        }
        $DB->delete_records('local_course_banner_builder_order');
        if (!$sync) {
            return;
        }
        foreach (array_unique(array_map('intval', $categoryids)) as $categoryid) {
            self::sync_courses_for_category_tree($categoryid);
        }
        foreach ($managedcourses as $course) {
            self::sync_course_overview_image($course);
        }
        if ($hadcustomfieldsources) {
            foreach ($DB->get_records('course', null, '', 'id, category') as $course) {
                self::sync_course_overview_image($course);
            }
        }
    }

    /**
     * Delete all runtime configuration owned by this plugin, keeping the installed plugin version intact.
     *
     * @return void
     */
    public static function delete_all_plugin_configuration(): void {
        global $DB;

        self::delete_all_configuration();
        self::delete_source_content(self::get_site_source());

        $context = \context_system::instance();
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_course_banner_builder', self::FILEAREA);
        $fs->delete_area_files($context->id, 'local_course_banner_builder', self::CARD_FILEAREA);

        $DB->delete_records_select(
            'config_plugins',
            'plugin = :plugin AND name <> :versionname',
            [
                'plugin' => 'local_course_banner_builder',
                'versionname' => 'version',
            ]
        );
        set_config('slideshow_course_enabled', 0, 'local_course_banner_builder');
        set_config('slideshow_site_enabled', 0, 'local_course_banner_builder');

        theme_reset_all_caches();
    }

    /**
     * Available export sections.
     *
     * @return array
     */
    protected static function get_export_section_keys(): array {
        return [
            self::EXPORT_SECTION_PLUGIN_SETTINGS,
            self::EXPORT_SECTION_COURSE_BANNERS,
            self::EXPORT_SECTION_SLIDESHOW,
            self::EXPORT_SECTION_SITE_BANNERS,
        ];
    }

    /**
     * Available export sections with labels.
     *
     * @return array
     */
    public static function get_export_section_options(): array {
        return [
            self::EXPORT_SECTION_PLUGIN_SETTINGS => self::get_local_string_or_fallback(
                'exportsectionpluginsettings',
                'Global plugin settings'
            ),
            self::EXPORT_SECTION_COURSE_BANNERS => self::get_local_string_or_fallback(
                'exportsectioncoursebanners',
                'All course banner settings'
            ),
            self::EXPORT_SECTION_SLIDESHOW => self::get_local_string_or_fallback(
                'exportsectionslideshow',
                'All slideshow settings'
            ),
            self::EXPORT_SECTION_SITE_BANNERS => self::get_local_string_or_fallback(
                'exportsectionsitebanners',
                'Site banner settings, images and borders'
            ),
        ];
    }

    /**
     * Return a plugin string without raising debugging notices before caches are rebuilt.
     *
     * @param string $identifier
     * @param string $fallback
     * @return string
     */
    protected static function get_local_string_or_fallback(string $identifier, string $fallback): string {
        $manager = get_string_manager();
        return $manager->string_exists($identifier, 'local_course_banner_builder')
            ? get_string($identifier, 'local_course_banner_builder')
            : $fallback;
    }

    /**
     * Normalise requested export sections.
     *
     * @param array $sections
     * @return array
     */
    public static function normalise_export_sections(array $sections = []): array {
        $allowed = self::get_export_section_keys();
        if (empty($sections)) {
            return $allowed;
        }

        $sections = array_values(array_intersect($allowed, array_map('strval', $sections)));
        return $sections;
    }

    /**
     * Normalise optional export behaviour flags.
     *
     * @param array $options
     * @return array
     */
    public static function normalise_export_options(array $options = []): array {
        return [
            self::EXPORT_OPTION_INCLUDE_CATEGORIES => array_key_exists(
                self::EXPORT_OPTION_INCLUDE_CATEGORIES,
                $options
            ) ? !empty($options[self::EXPORT_OPTION_INCLUDE_CATEGORIES]) : true,
            self::EXPORT_OPTION_INCLUDE_CUSTOMFIELDS => array_key_exists(
                self::EXPORT_OPTION_INCLUDE_CUSTOMFIELDS,
                $options
            ) ? !empty($options[self::EXPORT_OPTION_INCLUDE_CUSTOMFIELDS]) : true,
        ];
    }

    /**
     * Normalise optional import behaviour flags.
     *
     * @param array $options
     * @return array
     */
    public static function normalise_import_options(array $options = []): array {
        return [
            self::IMPORT_OPTION_CREATE_CATEGORIES => array_key_exists(
                self::IMPORT_OPTION_CREATE_CATEGORIES,
                $options
            ) ? !empty($options[self::IMPORT_OPTION_CREATE_CATEGORIES]) : true,
            self::IMPORT_OPTION_CREATE_CUSTOMFIELDS => array_key_exists(
                self::IMPORT_OPTION_CREATE_CUSTOMFIELDS,
                $options
            ) ? !empty($options[self::IMPORT_OPTION_CREATE_CUSTOMFIELDS]) : true,
        ];
    }

    /**
     * Export current configuration as a versioned array.
     *
     * @param array $sections
     * @param array $options
     * @return array
     */
    public static function export_configuration(array $sections = [], array $options = []): array {
        global $CFG;

        $sections = self::normalise_export_sections($sections);
        $options = self::normalise_export_options($options);
        $export = [
            'schema' => self::CONFIG_EXPORT_VERSION,
            'archiveformat' => 'json-with-embedded-files',
            'ziparchiveplanned' => true,
            'moodleversion' => $CFG->version,
            'exportedat' => time(),
            'selectedsections' => $sections,
            'exportoptions' => $options,
            'sections' => [],
        ];

        if (in_array(self::EXPORT_SECTION_COURSE_BANNERS, $sections, true)) {
            $coursebannerdata = self::export_course_banner_configuration($options);
            $export['sections'][self::EXPORT_SECTION_COURSE_BANNERS] = $coursebannerdata;
            $export['categories'] = $coursebannerdata['legacycategories'];
        }
        if (in_array(self::EXPORT_SECTION_PLUGIN_SETTINGS, $sections, true)) {
            $export['sections'][self::EXPORT_SECTION_PLUGIN_SETTINGS] = self::export_plugin_settings_configuration();
        }
        if (in_array(self::EXPORT_SECTION_SLIDESHOW, $sections, true)) {
            $export['sections'][self::EXPORT_SECTION_SLIDESHOW] = self::export_slideshow_configuration();
        }
        if (in_array(self::EXPORT_SECTION_SITE_BANNERS, $sections, true)) {
            $export['sections'][self::EXPORT_SECTION_SITE_BANNERS] = self::export_site_banner_configuration();
        }

        return $export;
    }

    /**
     * Create a ZIP export containing a manifest and every referenced image file.
     *
     * @param array $sections
     * @param array $options
     * @return string Absolute path to the temporary ZIP file.
     */
    public static function create_configuration_export_zip(array $sections = [], array $options = []): string {
        global $CFG;

        if (!class_exists('\ZipArchive')) {
            throw new \coding_exception('The PHP ZipArchive extension is required to export banner settings with files.');
        }

        make_temp_directory('local_course_banner_builder');
        $filepath = tempnam($CFG->tempdir . DIRECTORY_SEPARATOR . 'local_course_banner_builder', 'cbb_export_');
        if ($filepath === false) {
            throw new \coding_exception('Could not create a temporary export file.');
        }
        $zippath = $filepath . '.zip';
        @rename($filepath, $zippath);

        $export = self::export_configuration($sections, $options);
        $export['archiveformat'] = 'zip-with-manifest-and-files';
        $files = [];
        self::extract_embedded_export_files($export, $files);
        $export['filecount'] = count($files);

        $zip = new \ZipArchive();
        if ($zip->open($zippath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($zippath);
            throw new \coding_exception('Could not create the banner settings export archive.');
        }

        $zip->addFromString('manifest.json', json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        foreach ($files as $archivepath => $content) {
            $zip->addFromString($archivepath, $content);
        }
        $zip->close();

        return $zippath;
    }

    /**
     * Import a ZIP export archive.
     *
     * @param string $archivepath
     * @param bool $replaceall
     * @param array $sections
     * @param array $options
     * @return array
     */
    public static function import_configuration_archive(
        string $archivepath,
        bool $replaceall = false,
        array $sections = [],
        array $options = []
    ): array {
        if (!class_exists('\ZipArchive')) {
            throw new \coding_exception('The PHP ZipArchive extension is required to import banner settings with files.');
        }
        if (!is_readable($archivepath)) {
            throw new \coding_exception('The banner settings import archive is not readable.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivepath) !== true) {
            throw new \coding_exception('The banner settings import archive could not be opened.');
        }
        $manifest = $zip->getFromName('manifest.json');
        if ($manifest === false) {
            $zip->close();
            throw new \coding_exception('The banner settings import archive does not contain a manifest.json file.');
        }

        $data = json_decode($manifest, true);
        if (!is_array($data)) {
            $zip->close();
            throw new \coding_exception('The banner settings import manifest is invalid.');
        }

        self::hydrate_export_files_from_archive($data, $zip);
        $zip->close();

        return self::import_configuration(json_encode($data), $replaceall, $sections, $options);
    }

    /**
     * Recursively move base64 file payloads out of an export tree for ZIP storage.
     *
     * @param mixed $node
     * @param array $files
     * @return void
     */
    protected static function extract_embedded_export_files(&$node, array &$files): void {
        if (!is_array($node)) {
            return;
        }

        if (!empty($node['archivepath']) && !empty($node['contentbase64'])) {
            $archivepath = self::normalise_export_archive_path((string)$node['archivepath']);
            if ($archivepath !== '') {
                $files[$archivepath] = base64_decode((string)$node['contentbase64']);
                $node['archivepath'] = $archivepath;
            }
        }
        unset($node['contentbase64']);

        foreach ($node as &$value) {
            self::extract_embedded_export_files($value, $files);
        }
        unset($value);
    }

    /**
     * Recursively add base64 payloads from a ZIP archive back into one import tree.
     *
     * @param mixed $node
     * @param \ZipArchive $zip
     * @return void
     */
    protected static function hydrate_export_files_from_archive(&$node, \ZipArchive $zip): void {
        if (!is_array($node)) {
            return;
        }

        if (!empty($node['archivepath']) && empty($node['contentbase64'])) {
            $archivepath = self::normalise_export_archive_path((string)$node['archivepath']);
            if ($archivepath !== '') {
                $content = $zip->getFromName($archivepath);
                if ($content !== false) {
                    $node['archivepath'] = $archivepath;
                    $node['contentbase64'] = base64_encode($content);
                }
            }
        }

        foreach ($node as &$value) {
            self::hydrate_export_files_from_archive($value, $zip);
        }
        unset($value);
    }

    /**
     * Keep archive paths relative and safe.
     *
     * @param string $path
     * @return string
     */
    protected static function normalise_export_archive_path(string $path): string {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                continue;
            }
            $parts[] = clean_param($part, PARAM_FILE);
        }
        return implode('/', array_filter($parts, static function (string $part): bool {
            return $part !== '';
        }));
    }

    /**
     * Export banner title settings for the requested contexts.
     *
     * @param array $contexts
     * @return array
     */
    protected static function export_banner_title_configuration(array $contexts): array {
        $boolfields = [
            'enabled' => 0,
            'bold' => 1,
            'italic' => 0,
            'underline' => 0,
            'strike' => 0,
            'allcaps' => 0,
            'aboveborder' => 1,
            'aboveoverlay' => 1,
            'replacemoodletitle' => 0,
            'frameenabled' => 0,
            'frameshadowenabled' => 0,
            'shadowenabled' => 1,
            'overlayenabled' => 0,
        ];
        $numericlimits = [
            'x' => [50, 0, 100],
            'y' => [50, 0, 100],
            'fontsize' => [100, 25, 480],
            'lineheight' => [105, 40, 540],
            'frameopacity' => [35, 0, 100],
            'frameborderwidth' => [0, 0, 10],
            'frameradius' => [12, 0, 80],
            'framepadding' => [18, 0, 240],
            'frameshadowopacity' => [25, 0, 100],
            'frameshadowblur' => [14, 0, 80],
            'frameshadowdistance' => [6, 0, 50],
            'frameshadowdirection' => [135, 0, 360],
            'shadowopacity' => [55, 0, 100],
            'shadowblur' => [10, 0, 60],
            'shadowdistance' => [4, 0, 40],
            'shadowdirection' => [135, 0, 360],
            'overlayopacity' => [25, 0, 100],
        ];
        $hexfields = [
            'color' => '#FFFFFF',
            'framecolor' => '#000000',
            'framebordercolor' => '#FFFFFF',
            'frameshadowcolor' => '#000000',
            'shadowcolor' => '#000000',
            'overlaycolor' => '#000000',
        ];
        $out = [];
        foreach ($contexts as $context) {
            $context = in_array($context, ['course', 'activity', 'site'], true) ? $context : '';
            if ($context === '') {
                continue;
            }
            $prefix = 'bannertitle_' . $context . '_';
            $getconfig = static function (string $field, $default) use ($prefix) {
                $value = get_config('local_course_banner_builder', $prefix . $field);
                return $value === false || $value === null || $value === '' ? $default : $value;
            };

            $out[$context] = [
                'fontfamily' => '',
                'frametype' => 'box',
                'stylemode' => 'custom',
                'align' => self::normalise_slideshow_alignment(
                    (string)$getconfig('align', self::SLIDESHOW_ALIGN_CENTER)
                ),
            ];

            foreach ($boolfields as $field => $default) {
                $out[$context][$field] = empty($getconfig($field, $default)) ? 0 : 1;
            }
            foreach ($numericlimits as $field => $limits) {
                $value = (float)$getconfig($field, $limits[0]);
                $out[$context][$field] = max($limits[1], min($limits[2], $value));
            }
            foreach ($hexfields as $field => $default) {
                $value = (string)$getconfig($field, $default);
                $out[$context][$field] = preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtoupper($value) : $default;
            }

            $fontfamily = (string)$getconfig('fontfamily', '');
            if (array_key_exists($fontfamily, self::get_slideshow_font_family_options())) {
                $out[$context]['fontfamily'] = $fontfamily;
            }
            $frametype = (string)$getconfig('frametype', 'box');
            $out[$context]['frametype'] = in_array($frametype, ['box', 'highlight'], true) ? $frametype : 'box';
            $stylemode = (string)$getconfig('stylemode', 'custom');
            $out[$context]['stylemode'] = in_array($stylemode, ['site', 'course', 'activity', 'custom'], true)
                ? $stylemode
                : 'custom';
            if ($context === 'activity') {
                $mode = (string)$getconfig('activitytitlemode', 'activity');
                $out[$context]['activitytitlemode'] = in_array($mode, ['activity', 'course', 'both', 'none'], true)
                    ? $mode
                    : 'activity';
            }
        }
        return $out;
    }

    /**
     * Import banner title settings for known contexts.
     *
     * @param array $configs
     * @param array $contexts
     * @return void
     */
    protected static function import_banner_title_configuration(array $configs, array $contexts): void {
        $boolfields = [
            'enabled',
            'bold',
            'italic',
            'underline',
            'strike',
            'allcaps',
            'aboveborder',
            'aboveoverlay',
            'replacemoodletitle',
            'frameenabled',
            'frameshadowenabled',
            'shadowenabled',
            'overlayenabled',
        ];
        $numericlimits = [
            'x' => [50, 0, 100],
            'y' => [50, 0, 100],
            'fontsize' => [100, 25, 480],
            'lineheight' => [105, 40, 540],
            'frameopacity' => [35, 0, 100],
            'frameborderwidth' => [0, 0, 10],
            'frameradius' => [12, 0, 80],
            'framepadding' => [18, 0, 240],
            'frameshadowopacity' => [25, 0, 100],
            'frameshadowblur' => [14, 0, 80],
            'frameshadowdistance' => [6, 0, 50],
            'frameshadowdirection' => [135, 0, 360],
            'shadowopacity' => [55, 0, 100],
            'shadowblur' => [10, 0, 60],
            'shadowdistance' => [4, 0, 40],
            'shadowdirection' => [135, 0, 360],
            'overlayopacity' => [25, 0, 100],
        ];
        $hexfields = [
            'color' => '#FFFFFF',
            'framecolor' => '#000000',
            'framebordercolor' => '#FFFFFF',
            'frameshadowcolor' => '#000000',
            'shadowcolor' => '#000000',
            'overlaycolor' => '#000000',
        ];
        foreach ($contexts as $context) {
            if (empty($configs[$context]) || !is_array($configs[$context])) {
                continue;
            }
            $prefix = 'bannertitle_' . $context . '_';
            foreach ($boolfields as $field) {
                if (array_key_exists($field, $configs[$context])) {
                    set_config($prefix . $field, empty($configs[$context][$field]) ? 0 : 1, 'local_course_banner_builder');
                }
            }
            foreach ($numericlimits as $field => $limits) {
                if (array_key_exists($field, $configs[$context])) {
                    $value = $configs[$context][$field];
                    $value = $value === false || $value === null || $value === '' ? $limits[0] : $value;
                    set_config(
                        $prefix . $field,
                        max($limits[1], min($limits[2], (float)$value)),
                        'local_course_banner_builder'
                    );
                }
            }
            foreach ($hexfields as $field => $default) {
                if (array_key_exists($field, $configs[$context])) {
                    $value = $configs[$context][$field];
                    $value = $value === false || $value === null || $value === '' ? $default : (string)$value;
                    set_config(
                        $prefix . $field,
                        preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtoupper($value) : $default,
                        'local_course_banner_builder'
                    );
                }
            }
            if (array_key_exists('fontfamily', $configs[$context])) {
                $fontfamily = $configs[$context]['fontfamily'];
                $fontfamily = $fontfamily === false || $fontfamily === null ? '' : (string)$fontfamily;
                set_config(
                    $prefix . 'fontfamily',
                    array_key_exists($fontfamily, self::get_slideshow_font_family_options()) ? $fontfamily : '',
                    'local_course_banner_builder'
                );
            }
            if (array_key_exists('frametype', $configs[$context])) {
                $frametype = (string)($configs[$context]['frametype'] ?? 'box');
                set_config(
                    $prefix . 'frametype',
                    in_array($frametype, ['box', 'highlight'], true) ? $frametype : 'box',
                    'local_course_banner_builder'
                );
            }
            if (array_key_exists('stylemode', $configs[$context])) {
                $stylemode = (string)($configs[$context]['stylemode'] ?? 'custom');
                set_config(
                    $prefix . 'stylemode',
                    in_array($stylemode, ['site', 'course', 'activity', 'custom'], true) ? $stylemode : 'custom',
                    'local_course_banner_builder'
                );
            }
            if (array_key_exists('align', $configs[$context])) {
                set_config(
                    $prefix . 'align',
                    self::normalise_slideshow_alignment((string)$configs[$context]['align']),
                    'local_course_banner_builder'
                );
            } else {
                set_config($prefix . 'align', self::SLIDESHOW_ALIGN_CENTER, 'local_course_banner_builder');
            }
            if ($context === 'activity' && array_key_exists('activitytitlemode', $configs[$context])) {
                $mode = $configs[$context]['activitytitlemode'];
                $mode = $mode === false || $mode === null || $mode === '' ? 'activity' : (string)$mode;
                set_config(
                    $prefix . 'activitytitlemode',
                    in_array($mode, ['activity', 'course', 'both', 'none'], true) ? $mode : 'activity',
                    'local_course_banner_builder'
                );
            }
        }
    }

    /**
     * Export every course banner source, rule and layer.
     *
     * @param array $options
     * @return array
     */
    protected static function export_course_banner_configuration(array $options = []): array {
        global $DB;

        $options = self::normalise_export_options($options);
        $settings = $DB->get_records('local_course_banner_builder_order', null, 'id ASC');
        $elements = $DB->get_records('local_course_banner_builder_elements', null, 'sortorder ASC, id ASC');
        $sourcekeys = [];
        foreach ($settings as $record) {
            $sourcekeys[self::get_record_source_key($record)] = true;
        }
        foreach ($elements as $record) {
            $sourcekeys[self::get_record_source_key($record)] = true;
        }

        $sources = [];
        $legacycategories = [];
        $customfieldids = [];
        foreach (array_keys($sourcekeys) as $sourcekey) {
            $source = self::resolve_source($sourcekey);
            if ($source && self::is_site_source($source)) {
                continue;
            }
            $sourceexport = self::export_source_configuration($sourcekey, $source, $settings, $elements, 'coursebanners', $options);
            if (!$sourceexport) {
                continue;
            }
            $sources[] = $sourceexport;
            if (($sourceexport['sourcetype'] ?? '') === self::SOURCE_TYPE_CUSTOMFIELD && !empty($sourceexport['customfield']['shortname'])) {
                $customfieldids[(int)$sourceexport['customfield']['legacyid']] = true;
            }
            if (($sourceexport['sourcetype'] ?? '') === self::SOURCE_TYPE_CATEGORY) {
                $legacycategories[] = [
                    'idnumber' => $sourceexport['category']['idnumber'] ?? '',
                    'name' => $sourceexport['category']['name'] ?? '',
                    'pathnames' => $sourceexport['category']['pathnames'] ?? [],
                    'settings' => $sourceexport['settings'] ?? [],
                    'elements' => $sourceexport['elements'] ?? [],
                ];
            }
        }

        return [
            'settings' => [
                'enabled' => self::is_display_enabled(),
                'coursebannerenabled' => self::is_course_banner_enabled(),
                'bannerformat' => self::get_course_banner_format(),
                'activitypagesenabled' => self::course_banners_on_activity_pages_enabled(),
                'defaultimagebannersenabled' => self::course_default_image_banners_enabled(),
                'customoverviewimagesenabled' => self::course_custom_overview_images_enabled(),
                'enabledcustomfields' => (string)get_config('local_course_banner_builder', 'enabledcustomfields'),
            ],
            'titleoverlays' => self::export_banner_title_configuration(['course', 'activity']),
            'customfieldcategories' => !empty($options[self::EXPORT_OPTION_INCLUDE_CUSTOMFIELDS]) ?
                self::export_course_customfield_definitions(array_keys($customfieldids)) : [],
            'sources' => $sources,
            'legacycategories' => !empty($options[self::EXPORT_OPTION_INCLUDE_CATEGORIES]) ? $legacycategories : [],
        ];
    }

    /**
     * Export one configured source.
     *
     * @param string $sourcekey
     * @param \stdClass|null $source
     * @param array $settingsrecords
     * @param array $elementrecords
     * @param string $archiveprefix
     * @param array $options
     * @return array|null
     */
    protected static function export_source_configuration(
        string $sourcekey,
        ?\stdClass $source,
        array $settingsrecords,
        array $elementrecords,
        string $archiveprefix = 'coursebanners',
        array $options = []
    ): ?array {
        $options = self::normalise_export_options($options);
        $settings = null;
        foreach ($settingsrecords as $record) {
            if (self::get_record_source_key($record) === $sourcekey) {
                $settings = $record;
                break;
            }
        }

        $elements = [];
        foreach ($elementrecords as $record) {
            if (self::get_record_source_key($record) !== $sourcekey) {
                continue;
            }
            $elements[] = self::export_element_record($record, $archiveprefix);
        }

        if (!$source && !$settings && empty($elements)) {
            return null;
        }

        $sourcetype = $source->type ?? ($settings->sourcetype ?? self::SOURCE_TYPE_CATEGORY);
        $settingsdata = $settings ? self::export_settings_record($settings) : [];
        if (!empty($settingsdata['sourceparentkey'])) {
            $parentsource = self::resolve_source((string)$settingsdata['sourceparentkey']);
            if ($parentsource) {
                $settingsdata['sourceparent'] = self::export_source_reference(
                    (string)$settingsdata['sourceparentkey'],
                    $parentsource,
                    $options
                );
            }
        }

        $export = [
            'sourcekey' => $sourcekey,
            'sourcetype' => $sourcetype,
            'label' => $source->label ?? '',
            'settings' => $settingsdata,
            'elements' => $elements,
        ];

        if ($sourcetype === self::SOURCE_TYPE_SITE) {
            $export['site'] = [
                'key' => self::SITE_SOURCE_KEY,
                'label' => $source->label ?? get_string('sitebanner', 'local_course_banner_builder'),
            ];
        } else if ($sourcetype === self::SOURCE_TYPE_CATEGORY) {
            $categoryid = (int)($source->categoryid ?? $settings->categoryid ?? 0);
            $export['category'] = !empty($options[self::EXPORT_OPTION_INCLUDE_CATEGORIES]) ?
                self::export_course_category_identity($categoryid) : [];
        } else {
            $fieldid = (int)($source->customfieldid ?? $settings->coursecustomfieldid ?? $settings->customfieldid ?? 0);
            $export['customfield'] = self::export_customfield_identity($fieldid, (string)($source->customfieldvalue ?? $settings->customfieldvalue ?? ''));
        }

        return $export;
    }

    /**
     * Export enough source identity data to resolve a referenced source during import.
     *
     * @param string $sourcekey
     * @param \stdClass $source
     * @param array $options
     * @return array
     */
    protected static function export_source_reference(string $sourcekey, \stdClass $source, array $options = []): array {
        $options = self::normalise_export_options($options);
        $reference = [
            'sourcekey' => $sourcekey,
            'sourcetype' => $source->type ?? self::SOURCE_TYPE_CATEGORY,
            'label' => $source->label ?? '',
        ];

        if (($source->type ?? '') === self::SOURCE_TYPE_CUSTOMFIELD) {
            $reference['customfield'] = self::export_customfield_identity(
                (int)($source->customfieldid ?? 0),
                (string)($source->customfieldvalue ?? '')
            );
            return $reference;
        }

        if (($source->type ?? '') === self::SOURCE_TYPE_SITE) {
            $reference['site'] = [
                'key' => self::SITE_SOURCE_KEY,
                'label' => $source->label ?? get_string('sitebanner', 'local_course_banner_builder'),
            ];
            return $reference;
        }

        $reference['category'] = !empty($options[self::EXPORT_OPTION_INCLUDE_CATEGORIES]) ?
            self::export_course_category_identity((int)($source->categoryid ?? 0)) : [];
        return $reference;
    }

    /**
     * Export source settings row.
     *
     * @param \stdClass $record
     * @return array
     */
    protected static function export_settings_record(\stdClass $record): array {
        $data = [
            'categoryid' => isset($record->categoryid) ? (int)$record->categoryid : null,
            'sourcetype' => $record->sourcetype ?? self::SOURCE_TYPE_CATEGORY,
            'sourcekey' => self::get_record_source_key($record),
            'elementids' => $record->elementids ?? null,
            'coursecustomfieldid' => isset($record->coursecustomfieldid) ? (int)$record->coursecustomfieldid : null,
            'customfieldvalue' => $record->customfieldvalue ?? null,
            'compositionmode' => $record->compositionmode ?? self::MODE_CUMULATIVE,
            'fitmode' => $record->fitmode ?? self::FIT_MODE_ORIGINAL,
            'fitapplyscope' => $record->fitapplyscope ?? self::FIT_SCOPE_DESCENDANTS,
            'customfieldpriority' => $record->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY,
            'sourceparentkey' => $record->sourceparentkey ?? '',
            'sourceisroot' => (int)($record->sourceisroot ?? 0),
            'sourceinheritchildren' => (int)($record->sourceinheritchildren ?? 0),
        ];
        return self::append_extra_persistent_export_fields($data, $record, [
            'id',
            'timemodified',
        ]);
    }

    /**
     * Export one layer, including image content when present.
     *
     * @param \stdClass $record
     * @param string $archiveprefix
     * @return array
     */
    protected static function export_element_record(\stdClass $record, string $archiveprefix = 'coursebanners'): array {
        $file = self::get_banner_image_file($record);
        $data = [
            'elementtype' => $record->elementtype ?? 'background_image',
            'name' => $record->name ?? '',
            'sortorder' => (int)($record->sortorder ?? 0),
            'isenabled' => (int)($record->isenabled ?? 0),
            'fitmodeoverride' => $record->fitmodeoverride ?? null,
            'positionanchor' => $record->positionanchor ?? self::POSITION_CENTER,
            'offsettoppercent' => (float)($record->offsettoppercent ?? 0),
            'offsetrightpercent' => (float)($record->offsetrightpercent ?? 0),
            'offsetbottompercent' => (float)($record->offsetbottompercent ?? 0),
            'offsetleftpercent' => (float)($record->offsetleftpercent ?? 0),
            'customwidthpercent' => (float)($record->customwidthpercent ?? 100),
            'customheightpercent' => (float)($record->customheightpercent ?? 100),
            'customsizekeepaspect' => (int)($record->customsizekeepaspect ?? 1),
            'dynamicimagesizeenabled' => (int)($record->dynamicimagesizeenabled ?? 0),
            'imagecenterfixed' => (int)($record->imagecenterfixed ?? 0),
            'imageaboveoverlayenabled' => (int)($record->imageaboveoverlayenabled ?? 0),
            'imagebelowinheritedenabled' => (int)($record->imagebelowinheritedenabled ?? 0),
            'imageaboveinheritedenabled' => (int)($record->imageaboveinheritedenabled ?? 0),
            'imageopacity' => (float)($record->imageopacity ?? 1),
            'imageopacitypercent' => (float)round(((float)($record->imageopacity ?? 1)) * 100, 2),
            'imagecropenabled' => (int)($record->imagecropenabled ?? 0),
            'imagecropleftpercent' => (float)($record->imagecropleftpercent ?? 0),
            'imagecroptoppercent' => (float)($record->imagecroptoppercent ?? 0),
            'imagecropwidthpercent' => (float)($record->imagecropwidthpercent ?? 100),
            'imagecropheightpercent' => (float)($record->imagecropheightpercent ?? 100),
            'borderenabled' => (int)($record->borderenabled ?? 0),
            'bordercolor' => $record->bordercolor ?? '#56B9C0',
            'borderwidth' => (float)($record->borderwidth ?? 2.5),
            'borderopacity' => (float)($record->borderopacity ?? 0),
            'borderfade' => (float)($record->borderfade ?? 0),
            'borderstyle' => $record->borderstyle ?? self::BORDER_STYLE_SOLID,
            'borderdashlength' => (int)($record->borderdashlength ?? 24),
            'bordersides' => $record->bordersides ?? 'top,right,bottom,left',
            'borderinnerrounded' => (int)($record->borderinnerrounded ?? 0),
            'overlayenabled' => (int)($record->overlayenabled ?? 0),
            'overlaytarget' => $record->overlaytarget ?? self::OVERLAY_TARGET_BOTH,
            'overlaybannercolor' => $record->overlaybannercolor ?? '#000000',
            'overlaybanneropacity' => (float)($record->overlaybanneropacity ?? 25),
            'overlayslideshowcolor' => $record->overlayslideshowcolor ?? '#000000',
            'overlayslideshowopacity' => (float)($record->overlayslideshowopacity ?? 38),
            'overlaytitleabove' => (int)($record->overlaytitleabove ?? 1),
            'overlayborderabove' => (int)($record->overlayborderabove ?? 1),
            'filename' => $file ? $file->get_filename() : null,
            'archivepath' => $file ? $archiveprefix . '/elements/' . (int)$record->id . '_' .
                clean_filename($file->get_filename()) : null,
            'contentbase64' => $file ? base64_encode($file->get_content()) : null,
        ];
        return self::append_extra_persistent_export_fields($data, $record, [
            'id',
            'categoryid',
            'customfieldid',
            'customfieldvalue',
            'sourcetype',
            'sourcekey',
            'fileitemid',
            'timecreated',
            'timemodified',
        ]);
    }

    /**
     * Export a Moodle course category identity with its path.
     *
     * @param int $categoryid
     * @return array
     */
    protected static function export_course_category_identity(int $categoryid): array {
        global $DB;

        $category = $categoryid > 0 ? $DB->get_record(
            'course_categories',
            ['id' => $categoryid],
            'id,name,idnumber,path,parent,sortorder',
            IGNORE_MISSING
        ) : null;
        if (!$category) {
            return [];
        }

        $pathnames = [];
        $pathidnumbers = [];
        $pathids = array_filter(array_map('intval', explode('/', trim((string)$category->path, '/'))));
        foreach ($pathids as $pathid) {
            $pathcategory = $DB->get_record('course_categories', ['id' => $pathid], 'id,name,idnumber', IGNORE_MISSING);
            if ($pathcategory) {
                $pathnames[] = (string)$pathcategory->name;
                $pathidnumbers[] = (string)$pathcategory->idnumber;
            }
        }

        return [
            'legacyid' => (int)$category->id,
            'name' => (string)$category->name,
            'idnumber' => (string)$category->idnumber,
            'pathnames' => $pathnames,
            'pathidnumbers' => $pathidnumbers,
            'sortorder' => (int)$category->sortorder,
        ];
    }

    /**
     * Export one custom field identity used as a source.
     *
     * @param int $fieldid
     * @param string $value
     * @return array
     */
    protected static function export_customfield_identity(int $fieldid, string $value): array {
        global $DB;

        $field = $fieldid > 0 ? $DB->get_record('customfield_field', ['id' => $fieldid], '*', IGNORE_MISSING) : null;
        if (!$field) {
            return [
                'legacyid' => $fieldid,
                'value' => $value,
            ];
        }

        $category = $DB->get_record('customfield_category', ['id' => $field->categoryid], '*', IGNORE_MISSING);
        return [
            'legacyid' => (int)$field->id,
            'shortname' => (string)$field->shortname,
            'name' => (string)$field->name,
            'type' => (string)$field->type,
            'value' => $value,
            'valuehash' => sha1(self::normalise_customfield_value($value)),
            'categoryname' => $category ? (string)$category->name : '',
        ];
    }

    /**
     * Export Moodle course custom field definitions used by sources.
     *
     * @param array $fieldids
     * @return array
     */
    protected static function export_course_customfield_definitions(array $fieldids = []): array {
        global $DB;

        if (empty($fieldids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal(array_map('intval', $fieldids), SQL_PARAMS_NAMED);
        $records = $DB->get_records_sql(
            "SELECT f.*
               FROM {customfield_field} f
              WHERE f.id $insql
           ORDER BY f.categoryid, f.sortorder, f.id",
            $params
        );
        $categoryids = array_unique(array_map(static function (\stdClass $record): int {
            return (int)$record->categoryid;
        }, $records));

        $categories = [];
        foreach ($categoryids as $categoryid) {
            $category = $DB->get_record('customfield_category', ['id' => $categoryid], '*', IGNORE_MISSING);
            if (!$category) {
                continue;
            }
            $categories[(int)$category->id] = [
                'name' => (string)$category->name,
                'description' => (string)($category->description ?? ''),
                'descriptionformat' => (int)($category->descriptionformat ?? FORMAT_HTML),
                'sortorder' => (int)($category->sortorder ?? 0),
                'fields' => [],
            ];
        }

        foreach ($records as $field) {
            if (empty($categories[(int)$field->categoryid])) {
                continue;
            }
            $categories[(int)$field->categoryid]['fields'][] = [
                'legacyid' => (int)$field->id,
                'shortname' => (string)$field->shortname,
                'name' => (string)$field->name,
                'type' => (string)$field->type,
                'description' => (string)($field->description ?? ''),
                'descriptionformat' => (int)($field->descriptionformat ?? FORMAT_HTML),
                'sortorder' => (int)($field->sortorder ?? 0),
                'configdata' => (string)($field->configdata ?? ''),
            ];
        }

        return array_values($categories);
    }

    /**
     * Export every global Moodle config value owned by this plugin.
     *
     * Structured sections below still export normalised data and files. This section keeps
     * newer/global plugin settings portable even when they do not belong to one source table.
     *
     * @return array
     */
    protected static function export_plugin_settings_configuration(): array {
        global $DB;

        $settings = [];
        $records = $DB->get_records('config_plugins', ['plugin' => 'local_course_banner_builder'], 'name ASC');
        foreach ($records as $record) {
            $name = (string)$record->name;
            if ($name === 'version') {
                continue;
            }
            $settings[$name] = $record->value;
        }

        return [
            'settings' => $settings,
        ];
    }

    /**
     * Export native course/site slideshow settings, plus legacy EasyEdu settings for compatibility.
     *
     * @return array
     */
    protected static function export_slideshow_configuration(): array {
        return [
            'native' => [
                self::SLIDESHOW_CONTEXT_COURSE => self::prepare_slideshow_config_for_export(
                    self::get_slideshow_config(self::SLIDESHOW_CONTEXT_COURSE)
                ),
                self::SLIDESHOW_CONTEXT_SITE => self::prepare_slideshow_config_for_export(
                    self::get_slideshow_config(self::SLIDESHOW_CONTEXT_SITE)
                ),
            ],
            'legacy_easyedu' => self::export_easyedu_slideshow_configuration(),
        ];
    }

    /**
     * Keep slideshow exports explicit about opacity units.
     *
     * @param array $config
     * @return array
     */
    protected static function prepare_slideshow_config_for_export(array $config): array {
        unset($config['overlayrgb']);
        $config['overlayopacitypercent'] = round(((float)($config['overlayopacity'] ?? 0)) * 100, 2);
        return $config;
    }

    /**
     * Export native site banner settings, layers and legacy EasyEdu site banner settings.
     *
     * @return array
     */
    protected static function export_site_banner_configuration(): array {
        global $DB;

        $settings = $DB->get_records('local_course_banner_builder_order', null, 'id ASC');
        $elements = $DB->get_records('local_course_banner_builder_elements', null, 'sortorder ASC, id ASC');
        $source = self::get_site_source();

        return [
            'settings' => [
                'enabled' => (bool)get_config('local_course_banner_builder', 'sitebannerenabled'),
                'displayenabled' => self::is_display_enabled(),
                'bannerformat' => self::get_site_banner_format(),
            ],
            'titleoverlays' => self::export_banner_title_configuration(['site']),
            'source' => self::export_source_configuration(
                self::SITE_SOURCE_KEY,
                $source,
                $settings,
                $elements,
                'sitebanners'
            ),
            'legacy_easyedu' => self::export_easyedu_site_banner_configuration(),
        ];
    }

    /**
     * Export EasyEdu slideshow settings.
     *
     * @return array
     */
    protected static function export_easyedu_slideshow_configuration(): array {
        $keys = ['carouselheight'];
        $slidecount = defined('SLIDES_NUMBER') ? SLIDES_NUMBER : 3;
        for ($i = 1; $i <= $slidecount; $i++) {
            array_push($keys, 'carouseltitle' . $i, 'carouseltext' . $i, 'carousellink' . $i);
        }
        $fileareas = [];
        for ($i = 1; $i <= $slidecount; $i++) {
            $fileareas[] = 'carouselbgimage' . $i;
        }

        return [
            'settings' => self::export_theme_settings($keys),
            'files' => self::export_theme_fileareas($fileareas, 'slideshow'),
        ];
    }

    /**
     * Export EasyEdu site banner/frontpage visual settings.
     *
     * @return array
     */
    protected static function export_easyedu_site_banner_configuration(): array {
        $keys = ['frontpageblockvideo', 'frontpageblocktext'];
        $fileareas = ['bannerbgimage', 'coursebgimage', 'categorybgimage', 'frontpageblockimage'];
        $adcount = defined('ADS_NUMBER') ? ADS_NUMBER : 6;
        $adlinkcount = defined('AD_LINKS_NUMBER') ? AD_LINKS_NUMBER : 1;
        for ($i = 1; $i <= $adcount; $i++) {
            array_push($keys, 'adtitle' . $i, 'adorder' . $i, 'adhide' . $i);
            $fileareas[] = 'adbgimage' . $i;
            if ($adlinkcount === 1) {
                $keys[] = 'adbtnlink' . $i;
            } else {
                for ($j = 1; $j <= $adlinkcount; $j++) {
                    $keys[] = 'adlink' . $i . '_' . $j;
                }
            }
        }

        return [
            'settings' => self::export_theme_settings($keys),
            'files' => self::export_theme_fileareas($fileareas, 'sitebanners'),
        ];
    }

    /**
     * Export theme config values.
     *
     * @param array $keys
     * @return array
     */
    protected static function export_theme_settings(array $keys): array {
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = get_config('theme_easyedu', $key);
        }
        return $settings;
    }

    /**
     * Export stored files from theme EasyEdu fileareas.
     *
     * @param array $fileareas
     * @param string $section
     * @return array
     */
    protected static function export_theme_fileareas(array $fileareas, string $section): array {
        $context = \context_system::instance();
        $fs = get_file_storage();
        $files = [];
        foreach ($fileareas as $filearea) {
            $storedfiles = $fs->get_area_files($context->id, 'theme_easyedu', $filearea, 0, 'id ASC', false);
            foreach ($storedfiles as $file) {
                $files[] = [
                    'filearea' => $filearea,
                    'filename' => $file->get_filename(),
                    'filepath' => $file->get_filepath(),
                    'archivepath' => $section . '/' . $filearea . '/' . clean_filename($file->get_filename()),
                    'contentbase64' => base64_encode($file->get_content()),
                ];
            }
        }
        return $files;
    }

    /**
     * Resolve one exported category to a local category id.
     *
     * @param array $categorydata
     * @param bool $createifmissing
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
     * @param array $sections
     * @param array $options
     * @return array
     */
    public static function import_configuration(
        string $json,
        bool $replaceall = false,
        array $sections = [],
        array $options = []
    ): array {
        global $DB;

        $options = self::normalise_import_options($options);
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['schema'])) {
            throw new \coding_exception('Invalid course banner builder import payload.');
        }

        if (!empty($data['sections']) && is_array($data['sections'])) {
            $sections = self::normalise_import_sections($sections, array_keys($data['sections']));
            $summary = [];
            $transaction = $DB->start_delegated_transaction();
            try {
                if (in_array(self::EXPORT_SECTION_PLUGIN_SETTINGS, $sections, true) &&
                        !empty($data['sections'][self::EXPORT_SECTION_PLUGIN_SETTINGS])) {
                    if ($replaceall) {
                        self::delete_plugin_config_values();
                    }
                    $summary += self::import_plugin_settings_configuration(
                        $data['sections'][self::EXPORT_SECTION_PLUGIN_SETTINGS]
                    );
                }
                if (in_array(self::EXPORT_SECTION_COURSE_BANNERS, $sections, true) &&
                        !empty($data['sections'][self::EXPORT_SECTION_COURSE_BANNERS])) {
                    if ($replaceall) {
                        self::delete_all_configuration(false);
                    }
                    $summary += self::import_course_banner_configuration(
                        $data['sections'][self::EXPORT_SECTION_COURSE_BANNERS],
                        $options
                    );
                }
                if (in_array(self::EXPORT_SECTION_SLIDESHOW, $sections, true) &&
                        !empty($data['sections'][self::EXPORT_SECTION_SLIDESHOW])) {
                    $summary += self::import_slideshow_configuration($data['sections'][self::EXPORT_SECTION_SLIDESHOW]);
                }
                if (in_array(self::EXPORT_SECTION_SITE_BANNERS, $sections, true) &&
                        !empty($data['sections'][self::EXPORT_SECTION_SITE_BANNERS])) {
                    $summary += self::import_site_banner_configuration($data['sections'][self::EXPORT_SECTION_SITE_BANNERS]);
                }
                $transaction->allow_commit();
            } catch (\Throwable $e) {
                $transaction->rollback($e);
            }
            theme_reset_all_caches();
            return $summary;
        }

        if (empty($data['categories']) || !is_array($data['categories'])) {
            throw new \coding_exception('Invalid course banner builder import payload.');
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            if ($replaceall) {
                self::delete_all_configuration(false);
            }
            $summary = self::import_legacy_course_banner_configuration($data['categories'], $options);
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }
        theme_reset_all_caches();
        return $summary;
    }

    /**
     * Normalise requested import sections against sections actually present in the archive.
     *
     * @param array $sections
     * @param array $presentsections
     * @return array
     */
    protected static function normalise_import_sections(array $sections, array $presentsections): array {
        $allowed = self::get_export_section_keys();
        $presentsections = array_values(array_intersect($allowed, array_map('strval', $presentsections)));
        if (empty($sections)) {
            return $presentsections;
        }

        return array_values(array_intersect($presentsections, array_map('strval', $sections)));
    }

    /**
     * Delete global plugin config values without touching the installed version marker.
     *
     * @return void
     */
    protected static function delete_plugin_config_values(): void {
        global $DB;

        $DB->delete_records_select(
            'config_plugins',
            'plugin = :plugin AND name <> :versionname',
            [
                'plugin' => 'local_course_banner_builder',
                'versionname' => 'version',
            ]
        );
    }

    /**
     * Import global plugin config values.
     *
     * @param array $data
     * @return array
     */
    protected static function import_plugin_settings_configuration(array $data): array {
        $imported = 0;
        foreach (($data['settings'] ?? []) as $name => $value) {
            $name = (string)$name;
            if ($name === 'version' || !preg_match('/^[a-z0-9_]+$/i', $name)) {
                continue;
            }
            set_config($name, $value, 'local_course_banner_builder');
            $imported++;
        }

        return ['importedpluginsettings' => $imported];
    }

    /**
     * Import the current full course banner export format.
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    protected static function import_course_banner_configuration(array $data, array $options = []): array {
        $options = self::normalise_import_options($options);
        $fieldmap = self::import_course_customfield_definitions(
            $data['customfieldcategories'] ?? [],
            !empty($options[self::IMPORT_OPTION_CREATE_CUSTOMFIELDS])
        );
        if (!empty($data['settings']) && is_array($data['settings'])) {
            if (array_key_exists('enabled', $data['settings'])) {
                set_config('enabled', empty($data['settings']['enabled']) ? 0 : 1, 'local_course_banner_builder');
            }
            if (array_key_exists('coursebannerenabled', $data['settings'])) {
                $enabled = empty($data['settings']['coursebannerenabled']) ? 0 : 1;
                set_config('coursebannerenabled', $enabled, 'local_course_banner_builder');
                if (!$enabled) {
                    set_config('coursebanneractivitiesenabled', 0, 'local_course_banner_builder');
                }
            }
            if (!empty($data['settings']['bannerformat'])) {
                self::set_course_banner_format((string)$data['settings']['bannerformat']);
            }
            if (array_key_exists('defaultimagebannersenabled', $data['settings'])) {
                set_config(
                    'coursebannerdefaultimageenabled',
                    empty($data['settings']['defaultimagebannersenabled']) ? 0 : 1,
                    'local_course_banner_builder'
                );
            }
            if (array_key_exists('activitypagesenabled', $data['settings'])) {
                $coursebannersenabled = self::is_course_banner_enabled();
                set_config(
                    'coursebanneractivitiesenabled',
                    !$coursebannersenabled || empty($data['settings']['activitypagesenabled']) ? 0 : 1,
                    'local_course_banner_builder'
                );
            }
            if (array_key_exists('customoverviewimagesenabled', $data['settings'])) {
                set_config(
                    'coursecustomoverviewimagesenabled',
                    empty($data['settings']['customoverviewimagesenabled']) ? 0 : 1,
                    'local_course_banner_builder'
                );
            }
            if (array_key_exists('enabledcustomfields', $data['settings'])) {
                $enabledcustomfields = array_filter(array_map('intval', explode(',', (string)$data['settings']['enabledcustomfields'])));
                $enabledcustomfields = array_values(array_unique(array_filter(array_map(
                    static function (int $fieldid) use ($fieldmap): int {
                        return (int)($fieldmap[$fieldid] ?? $fieldid);
                    },
                    $enabledcustomfields
                ))));
                set_config(
                    'enabledcustomfields',
                    implode(',', $enabledcustomfields),
                    'local_course_banner_builder'
                );
            }
        }
        if (!empty($data['titleoverlays']) && is_array($data['titleoverlays'])) {
            self::import_banner_title_configuration($data['titleoverlays'], ['course', 'activity']);
        }
        $resolvedsources = [];
        $sourcekeymap = [];
        foreach (($data['sources'] ?? []) as $sourcedata) {
            $source = self::resolve_import_source(
                $sourcedata,
                $fieldmap,
                !empty($options[self::IMPORT_OPTION_CREATE_CATEGORIES])
            );
            if (!$source) {
                continue;
            }
            $resolvedsources[] = [$sourcedata, $source];
            if (!empty($sourcedata['sourcekey'])) {
                $sourcekeymap[(string)$sourcedata['sourcekey']] = (string)$source->sourcekey;
            }
        }
        self::enable_customfields_from_imported_sources($resolvedsources);

        $imported = 0;
        foreach ($resolvedsources as [$sourcedata, $source]) {
            $parentkey = self::resolve_import_parent_key($sourcedata, $sourcekeymap, $fieldmap, $options);

            self::delete_source_content($source, false);
            self::save_source_settings(
                $source,
                (string)($sourcedata['settings']['compositionmode'] ?? self::MODE_CUMULATIVE),
                (string)($sourcedata['settings']['fitmode'] ?? self::FIT_MODE_ORIGINAL),
                (string)($sourcedata['settings']['fitapplyscope'] ?? self::FIT_SCOPE_DESCENDANTS),
                (string)($sourcedata['settings']['customfieldpriority'] ?? self::CUSTOMFIELD_PRIORITY_CATEGORY),
                $parentkey,
                !empty($sourcedata['settings']['sourceisroot']),
                !empty($sourcedata['settings']['sourceinheritchildren']),
                false
            );
            self::import_extra_source_settings_fields($source, $sourcedata['settings'] ?? []);

            foreach (($sourcedata['elements'] ?? []) as $elementdata) {
                self::import_source_element($source, $elementdata);
            }

            $imported++;
        }

        foreach ($resolvedsources as [$sourcedata, $source]) {
            $parentkey = self::resolve_import_parent_key($sourcedata, $sourcekeymap, $fieldmap, $options);
            if ($parentkey === '') {
                continue;
            }
            $settings = self::get_source_settings($source);
            if (empty($settings->id) || (string)($settings->sourceparentkey ?? '') === $parentkey) {
                continue;
            }
            self::save_source_settings(
                $source,
                (string)($settings->compositionmode ?? self::MODE_CUMULATIVE),
                (string)($settings->fitmode ?? self::FIT_MODE_ORIGINAL),
                (string)($settings->fitapplyscope ?? self::FIT_SCOPE_DESCENDANTS),
                (string)($settings->customfieldpriority ?? self::CUSTOMFIELD_PRIORITY_CATEGORY),
                $parentkey,
                false,
                !empty($settings->sourceinheritchildren),
                false
            );
            self::import_extra_source_settings_fields($source, $sourcedata['settings'] ?? []);
        }

        return ['importedsources' => $imported];
    }

    /**
     * Ensure imported custom field sources are selectable in the admin source dropdown.
     *
     * @param array $resolvedsources
     * @return void
     */
    protected static function enable_customfields_from_imported_sources(array $resolvedsources): void {
        $enabled = array_filter(array_map('intval', explode(',', (string)get_config(
            'local_course_banner_builder',
            'enabledcustomfields'
        ))));

        foreach ($resolvedsources as $entry) {
            $source = $entry[1] ?? null;
            if (($source->type ?? '') === self::SOURCE_TYPE_CUSTOMFIELD && !empty($source->customfieldid)) {
                $enabled[] = (int)$source->customfieldid;
            }
        }

        $enabled = array_values(array_unique(array_filter($enabled)));
        set_config('enabledcustomfields', implode(',', $enabled), 'local_course_banner_builder');
    }

    /**
     * Resolve the imported parent source key using local source mappings and exported source identity data.
     *
     * @param array $sourcedata
     * @param array $sourcekeymap
     * @param array $fieldmap
     * @param array $options
     * @return string
     */
    protected static function resolve_import_parent_key(
        array $sourcedata,
        array $sourcekeymap,
        array $fieldmap,
        array $options = []
    ): string {
        $options = self::normalise_import_options($options);
        if (!empty($sourcedata['settings']['sourceisroot'])) {
            return '';
        }

        $parentkey = (string)($sourcedata['settings']['sourceparentkey'] ?? '');
        if ($parentkey === '') {
            return '';
        }

        if (isset($sourcekeymap[$parentkey])) {
            return (string)$sourcekeymap[$parentkey];
        }

        if (!empty($sourcedata['settings']['sourceparent']) && is_array($sourcedata['settings']['sourceparent'])) {
            $parentsource = self::resolve_import_source(
                $sourcedata['settings']['sourceparent'],
                $fieldmap,
                !empty($options[self::IMPORT_OPTION_CREATE_CATEGORIES])
            );
            if ($parentsource) {
                return (string)$parentsource->sourcekey;
            }
        }

        return $parentkey;
    }

    /**
     * Import native slideshow settings and optional legacy EasyEdu slideshow settings.
     *
     * @param array $data
     * @return array
     */
    protected static function import_slideshow_configuration(array $data): array {
        $imported = 0;
        if (!empty($data['native']) && is_array($data['native'])) {
            foreach ([self::SLIDESHOW_CONTEXT_COURSE, self::SLIDESHOW_CONTEXT_SITE] as $context) {
                if (empty($data['native'][$context]) || !is_array($data['native'][$context])) {
                    continue;
                }
                self::import_slideshow_context_config($context, $data['native'][$context]);
                $imported++;
            }
        } else {
            $legacy = self::import_easyedu_theme_configuration($data);
            return ['importedslideshowcontexts' => 0] + $legacy;
        }

        if (!empty($data['legacy_easyedu']) && is_array($data['legacy_easyedu'])) {
            $legacy = self::import_easyedu_theme_configuration($data['legacy_easyedu']);
            return ['importedslideshowcontexts' => $imported] + $legacy;
        }

        return ['importedslideshowcontexts' => $imported];
    }

    /**
     * Import one native slideshow context.
     *
     * @param string $context
     * @param array $config
     * @return void
     */
    protected static function import_slideshow_context_config(string $context, array $config): void {
        $values = $config;
        unset($values['context'], $values['overlayrgb']);
        if (isset($values['overlayopacitypercent'])) {
            $values['overlayopacity'] = (float)$values['overlayopacitypercent'];
        } else if (isset($values['overlayopacity']) && (float)$values['overlayopacity'] <= 1) {
            $values['overlayopacity'] = (float)$values['overlayopacity'] * 100;
        }
        if (!empty($values['labelcolors']) && is_array($values['labelcolors'])) {
            foreach ($values['labelcolors'] as $type => $colours) {
                if (!is_array($colours)) {
                    continue;
                }
                foreach (['background', 'text', 'border', 'shadow'] as $role) {
                    if (array_key_exists($role, $colours)) {
                        $values['label_' . $type . '_' . $role] = $colours[$role];
                    }
                }
            }
        }
        unset($values['labelcolors'], $values['overlayopacitypercent']);
        self::set_slideshow_config($context, $values);
    }

    /**
     * Import native site banner settings/layers and optional legacy EasyEdu settings.
     *
     * @param array $data
     * @return array
     */
    protected static function import_site_banner_configuration(array $data): array {
        if (!empty($data['settings']) && is_array($data['settings'])) {
            if (array_key_exists('displayenabled', $data['settings'])) {
                set_config('enabled', empty($data['settings']['displayenabled']) ? 0 : 1, 'local_course_banner_builder');
            }
            if (array_key_exists('enabled', $data['settings'])) {
                set_config('sitebannerenabled', empty($data['settings']['enabled']) ? 0 : 1, 'local_course_banner_builder');
            }
            if (!empty($data['settings']['bannerformat'])) {
                self::set_site_banner_format((string)$data['settings']['bannerformat']);
            }
        }
        if (!empty($data['titleoverlays']) && is_array($data['titleoverlays'])) {
            self::import_banner_title_configuration($data['titleoverlays'], ['site']);
        }

        $imported = 0;
        if (!empty($data['source']) && is_array($data['source'])) {
            $source = self::get_site_source();
            self::delete_source_content($source, false);
            self::save_source_settings(
                $source,
                (string)($data['source']['settings']['compositionmode'] ?? self::MODE_CUMULATIVE),
                (string)($data['source']['settings']['fitmode'] ?? self::FIT_MODE_ORIGINAL),
                (string)($data['source']['settings']['fitapplyscope'] ?? self::FIT_SCOPE_SELF),
                (string)($data['source']['settings']['customfieldpriority'] ?? self::CUSTOMFIELD_PRIORITY_CATEGORY),
                '',
                true,
                false,
                false
            );
            self::import_extra_source_settings_fields($source, $data['source']['settings'] ?? []);
            foreach (($data['source']['elements'] ?? []) as $elementdata) {
                self::import_source_element($source, $elementdata);
            }
            $imported = 1;
        } else {
            $legacy = self::import_easyedu_theme_configuration($data);
            return ['importedsitebannersources' => 0] + $legacy;
        }

        if (!empty($data['legacy_easyedu']) && is_array($data['legacy_easyedu'])) {
            $legacy = self::import_easyedu_theme_configuration($data['legacy_easyedu']);
            return ['importedsitebannersources' => $imported] + $legacy;
        }

        return ['importedsitebannersources' => $imported];
    }

    /**
     * Import Moodle custom field categories/fields and return legacy-id map.
     *
     * @param array $categories
     * @param bool $createifmissing
     * @return array
     */
    protected static function import_course_customfield_definitions(array $categories, bool $createifmissing = true): array {
        global $DB;

        $handler = \core_course\customfield\course_handler::create();
        $context = $handler->get_configuration_context();
        $fieldmap = [];
        $now = time();
        foreach ($categories as $categorydata) {
            $categoryname = trim((string)($categorydata['name'] ?? ''));
            if ($categoryname === '') {
                continue;
            }
            $category = $DB->get_record('customfield_category', [
                'component' => 'core_course',
                'area' => 'course',
                'itemid' => 0,
                'name' => $categoryname,
            ], '*', IGNORE_MISSING);
            if (!$category) {
                if (!$createifmissing) {
                    continue;
                }
                $category = (object)[
                    'name' => $categoryname,
                    'description' => (string)($categorydata['description'] ?? ''),
                    'descriptionformat' => (int)($categorydata['descriptionformat'] ?? FORMAT_HTML),
                    'sortorder' => (int)($categorydata['sortorder'] ?? 0),
                    'component' => 'core_course',
                    'area' => 'course',
                    'itemid' => 0,
                    'contextid' => $context->id,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $category->id = $DB->insert_record('customfield_category', $category);
            }

            foreach (($categorydata['fields'] ?? []) as $fielddata) {
                $shortname = trim((string)($fielddata['shortname'] ?? ''));
                $type = (string)($fielddata['type'] ?? '');
                if ($shortname === '' || $type === '') {
                    continue;
                }
                $field = $DB->get_record('customfield_field', [
                    'categoryid' => $category->id,
                    'shortname' => $shortname,
                ], '*', IGNORE_MISSING);
                if (!$field) {
                    if (!$createifmissing) {
                        continue;
                    }
                    $field = (object)[
                        'shortname' => $shortname,
                        'name' => (string)($fielddata['name'] ?? $shortname),
                        'type' => $type,
                        'description' => (string)($fielddata['description'] ?? ''),
                        'descriptionformat' => (int)($fielddata['descriptionformat'] ?? FORMAT_HTML),
                        'sortorder' => (int)($fielddata['sortorder'] ?? 0),
                        'categoryid' => $category->id,
                        'configdata' => (string)($fielddata['configdata'] ?? ''),
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ];
                    $field->id = $DB->insert_record('customfield_field', $field);
                } else {
                    $field->name = (string)($fielddata['name'] ?? $field->name);
                    $field->description = (string)($fielddata['description'] ?? $field->description);
                    $field->descriptionformat = (int)($fielddata['descriptionformat'] ?? $field->descriptionformat);
                    $field->configdata = (string)($fielddata['configdata'] ?? $field->configdata);
                    $field->timemodified = $now;
                    $DB->update_record('customfield_field', $field);
                }
                if (!empty($fielddata['legacyid'])) {
                    $fieldmap[(int)$fielddata['legacyid']] = (int)$field->id;
                }
            }
        }

        return $fieldmap;
    }

    /**
     * Resolve an imported source to a local source object.
     *
     * @param array $sourcedata
     * @param array $fieldmap
     * @param bool $createcategories
     * @return \stdClass|null
     */
    protected static function resolve_import_source(
        array $sourcedata,
        array $fieldmap,
        bool $createcategories = true
    ): ?\stdClass {
        if (($sourcedata['sourcetype'] ?? '') === self::SOURCE_TYPE_SITE ||
                (string)($sourcedata['sourcekey'] ?? '') === self::SITE_SOURCE_KEY) {
            return self::get_site_source();
        }

        if (($sourcedata['sourcetype'] ?? '') === self::SOURCE_TYPE_CUSTOMFIELD) {
            $customfield = $sourcedata['customfield'] ?? [];
            $legacyid = (int)($customfield['legacyid'] ?? 0);
            $fieldid = $fieldmap[$legacyid] ?? 0;
            if (!$fieldid && !empty($customfield['shortname'])) {
                $fieldid = self::find_course_customfield_id((string)$customfield['shortname']);
            }
            if (!$fieldid) {
                return null;
            }
            $value = (string)($customfield['value'] ?? $sourcedata['settings']['customfieldvalue'] ?? '');
            return (object)[
                'type' => self::SOURCE_TYPE_CUSTOMFIELD,
                'sourcekey' => self::get_customfield_source_key($fieldid, $value),
                'categoryid' => null,
                'customfieldid' => $fieldid,
                'customfieldvalue' => $value,
                'label' => (string)($sourcedata['label'] ?? ''),
            ];
        }

        $categoryid = self::resolve_import_category_id($sourcedata['category'] ?? [], $createcategories);
        return $categoryid ? self::resolve_source(self::get_category_source_key($categoryid)) : null;
    }

    /**
     * Find a course custom field by shortname.
     *
     * @param string $shortname
     * @return int
     */
    protected static function find_course_customfield_id(string $shortname): int {
        global $DB;

        $record = $DB->get_record_sql(
            "SELECT f.id
               FROM {customfield_field} f
               JOIN {customfield_category} c ON c.id = f.categoryid
              WHERE c.component = :component
                AND c.area = :area
                AND c.itemid = 0
                AND f.shortname = :shortname",
            ['component' => 'core_course', 'area' => 'course', 'shortname' => $shortname],
            IGNORE_MISSING
        );
        return $record ? (int)$record->id : 0;
    }

    /**
     * Import one layer into a source.
     *
     * @param \stdClass $source
     * @param array $elementdata
     * @return void
     */
    protected static function import_source_element(\stdClass $source, array $elementdata): void {
        global $DB;

        $record = self::create_source_element($source);
        $textfields = [
            'elementtype', 'name', 'fitmodeoverride', 'positionanchor', 'bordercolor', 'borderstyle', 'bordersides',
            'overlaytarget', 'overlaybannercolor', 'overlayslideshowcolor',
        ];
        foreach ($textfields as $field) {
            if (self::table_field_exists('local_course_banner_builder_elements', $field) || property_exists($record, $field)) {
                $record->{$field} = $elementdata[$field] ?? $record->{$field};
            }
        }
        $intfields = [
            'sortorder', 'isenabled', 'customsizekeepaspect', 'dynamicimagesizeenabled', 'imagecenterfixed',
            'imageaboveoverlayenabled', 'imagebelowinheritedenabled', 'imageaboveinheritedenabled',
            'imagecropenabled', 'borderenabled', 'borderdashlength', 'borderinnerrounded', 'overlayenabled',
            'overlaytitleabove', 'overlayborderabove',
        ];
        foreach ($intfields as $field) {
            if (self::table_field_exists('local_course_banner_builder_elements', $field) || property_exists($record, $field)) {
                $record->{$field} = (int)($elementdata[$field] ?? $record->{$field});
            }
        }
        $floatfields = [
            'offsettoppercent', 'offsetrightpercent', 'offsetbottompercent', 'offsetleftpercent',
            'customwidthpercent', 'customheightpercent', 'imageopacity', 'imagecropleftpercent',
            'imagecroptoppercent', 'imagecropwidthpercent', 'imagecropheightpercent', 'borderwidth',
            'borderopacity', 'borderfade', 'overlaybanneropacity', 'overlayslideshowopacity',
        ];
        foreach ($floatfields as $field) {
            if (self::table_field_exists('local_course_banner_builder_elements', $field) || property_exists($record, $field)) {
                $record->{$field} = (float)($elementdata[$field] ?? $record->{$field});
            }
        }
        if (self::table_field_exists('local_course_banner_builder_elements', 'imageopacity') ||
                property_exists($record, 'imageopacity')) {
            if (array_key_exists('imageopacitypercent', $elementdata)) {
                $record->imageopacity = self::normalise_unit_float((float)$elementdata['imageopacitypercent'] / 100, 1);
            } else {
                $imageopacity = (float)($record->imageopacity ?? 1);
                $record->imageopacity = self::normalise_unit_float($imageopacity > 1 ? $imageopacity / 100 : $imageopacity, 1);
            }
        }
        $record = self::apply_extra_persistent_import_fields(
            $record,
            $elementdata,
            array_merge($textfields, $intfields, $floatfields, ['imageopacitypercent']),
            [
                'id',
                'categoryid',
                'customfieldid',
                'customfieldvalue',
                'sourcetype',
                'sourcekey',
                'fileitemid',
                'timecreated',
                'timemodified',
                'filename',
                'archivepath',
                'contentbase64',
            ],
            'local_course_banner_builder_elements'
        );
        $record->timemodified = time();
        $DB->update_record('local_course_banner_builder_elements', $record);

        if (!empty($elementdata['contentbase64']) && !empty($elementdata['filename'])) {
            self::store_content_in_element(
                $record,
                (string)$elementdata['filename'],
                base64_decode((string)$elementdata['contentbase64'])
            );
        }
    }

    /**
     * Import any extra persisted source setting fields that are not part of save_source_settings().
     *
     * @param \stdClass $source
     * @param array $settingsdata
     * @return void
     */
    protected static function import_extra_source_settings_fields(\stdClass $source, array $settingsdata): void {
        global $DB;

        if (empty($settingsdata)) {
            return;
        }
        $record = self::get_or_create_source_settings($source);
        $record = self::apply_extra_persistent_import_fields(
            $record,
            $settingsdata,
            [
                'categoryid',
                'sourcetype',
                'sourcekey',
                'elementids',
                'coursecustomfieldid',
                'customfieldvalue',
                'compositionmode',
                'fitmode',
                'fitapplyscope',
                'customfieldpriority',
                'sourceparentkey',
                'sourceisroot',
                'sourceinheritchildren',
                'sourceparent',
            ],
            [
                'id',
                'timemodified',
            ],
            'local_course_banner_builder_order'
        );
        $record->timemodified = time();
        $DB->update_record('local_course_banner_builder_order', $record);
    }

    /**
     * Import EasyEdu theme settings and stored files.
     *
     * @param array $data
     * @return array
     */
    protected static function import_easyedu_theme_configuration(array $data): array {
        foreach (($data['settings'] ?? []) as $key => $value) {
            set_config((string)$key, $value, 'theme_easyedu');
        }

        $context = \context_system::instance();
        $fs = get_file_storage();
        $touchedfileareas = [];
        foreach (($data['files'] ?? []) as $filedata) {
            $filearea = (string)($filedata['filearea'] ?? '');
            $filename = (string)($filedata['filename'] ?? '');
            if ($filearea === '' || $filename === '' || empty($filedata['contentbase64'])) {
                continue;
            }
            if (empty($touchedfileareas[$filearea])) {
                $fs->delete_area_files($context->id, 'theme_easyedu', $filearea, 0);
                $touchedfileareas[$filearea] = true;
            }
            $fs->create_file_from_string([
                'contextid' => $context->id,
                'component' => 'theme_easyedu',
                'filearea' => $filearea,
                'itemid' => 0,
                'filepath' => (string)($filedata['filepath'] ?? '/'),
                'filename' => $filename,
            ], base64_decode((string)$filedata['contentbase64']));
            set_config($filearea, $filename, 'theme_easyedu');
        }

        return ['importedthemesettings' => count($data['settings'] ?? [])];
    }

    /**
     * Import the legacy category-only export format.
     *
     * @param array $categories
     * @param array $options
     * @return array
     */
    protected static function import_legacy_course_banner_configuration(array $categories, array $options = []): array {
        $options = self::normalise_import_options($options);
        $imported = 0;
        foreach ($categories as $categorydata) {
            $source = self::resolve_import_source([
                'sourcetype' => self::SOURCE_TYPE_CATEGORY,
                'category' => $categorydata,
                'settings' => $categorydata['settings'] ?? [],
            ], [], !empty($options[self::IMPORT_OPTION_CREATE_CATEGORIES]));
            if (!$source) {
                continue;
            }
            self::delete_source_content($source, false);
            self::save_source_settings(
                $source,
                (string)($categorydata['settings']['compositionmode'] ?? self::MODE_CUMULATIVE),
                (string)($categorydata['settings']['fitmode'] ?? self::FIT_MODE_ORIGINAL),
                (string)($categorydata['settings']['fitapplyscope'] ?? self::FIT_SCOPE_DESCENDANTS),
                self::CUSTOMFIELD_PRIORITY_CATEGORY,
                '',
                false,
                false,
                false
            );
            self::import_extra_source_settings_fields($source, $categorydata['settings'] ?? []);
            foreach (($categorydata['elements'] ?? []) as $elementdata) {
                self::import_source_element($source, $elementdata);
            }
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

        $overviewlayers = [];
        foreach (self::sort_layer_specs($layerspecs) as $layerspec) {
            $record = $layerspec['record'];
            if (self::get_banner_image_file($record) || !empty($record->borderenabled) ||
                    self::record_overlay_targets_banner($record)) {
                $overviewlayers[] = $layerspec;
            }
        }

        if (empty($overviewlayers)) {
            return self::build_blank_image(
                self::CARD_CANVAS_WIDTH,
                self::CARD_CANVAS_HEIGHT,
                'course_' . $courseid . '_banner.png'
            );
        }

        return self::build_course_card_composite_image(
            $overviewlayers,
            self::CARD_CANVAS_WIDTH,
            self::CARD_CANVAS_HEIGHT,
            'course_' . $courseid . '_banner.png'
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

        return self::build_course_card_composite_image(
            $layerspecs,
            self::CARD_CANVAS_WIDTH,
            self::CARD_CANVAS_HEIGHT,
            'course_' . $courseid . '_card.png'
        );
    }

    /**
     * Build a square thumbnail for frontpage course boxes.
     *
     * @param array $layerspecs
     * @param int $courseid
     * @return string|null
     */
    protected static function build_course_card_square_image(array $layerspecs, int $courseid): ?string {
        if (!function_exists('imagecreatetruecolor') || empty($layerspecs)) {
            return null;
        }
        raise_memory_limit(MEMORY_EXTRA);

        return self::build_course_card_composite_image(
            $layerspecs,
            self::CARD_SQUARE_CANVAS_SIZE,
            self::CARD_SQUARE_CANVAS_SIZE,
            'course_' . $courseid . '_squarecard.png'
        );
    }

    /**
     * Build a card thumbnail by cropping a logical 4:1 banner into the card surface.
     *
     * This keeps layer placement visually consistent with the real banner while
     * avoiding image deformation inside Moodle's rectangular and square cards.
     *
     * @param array $layerspecs
     * @param int $width
     * @param int $height
     * @param string $filename
     * @return string|null
     */
    protected static function build_course_card_composite_image(
        array $layerspecs,
        int $width,
        int $height,
        string $filename
    ): ?string {
        $layerspecs = self::sort_layer_specs($layerspecs);
        $imagelayers = [];
        $borderrecords = [];
        $overlayrecords = [];

        foreach ($layerspecs as $layerspec) {
            $record = $layerspec['record'];
            if (self::record_overlay_targets_banner($record)) {
                $overlayrecords[] = $record;
            }
            if (!empty($record->borderenabled)) {
                $borderrecord = clone $record;
                $borderrecord->borderinnerrounded = 0;
                $borderrecord->bordersides = 'top,right,bottom,left';
                $borderrecords[] = $borderrecord;
            }
            if (self::get_banner_image_file($record)) {
                $imagerecord = clone $record;
                $imagerecord->borderenabled = 0;
                $imagelayers[] = $layerspec;
                $imagelayers[array_key_last($imagelayers)]['record'] = $imagerecord;
            }
        }

        if (empty($imagelayers) && empty($borderrecords) && empty($overlayrecords)) {
            return null;
        }

        $canvas = imagecreatetruecolor($width, $height);
        if (!$canvas) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        imagealphablending($canvas, true);

        if (!empty($imagelayers)) {
            $bannerpath = self::build_composite_image(
                $imagelayers,
                self::DEFAULT_CANVAS_WIDTH,
                self::DEFAULT_CANVAS_HEIGHT,
                'course_card_banner_' . sha1($filename) . '.png',
                false
            );
            if ($bannerpath) {
                $banner = imagecreatefrompng($bannerpath);
                @unlink($bannerpath);
                if ($banner) {
                    self::copy_layer_cover(
                        $canvas,
                        $banner,
                        $width,
                        $height,
                        imagesx($banner),
                        imagesy($banner)
                    );
                    imagedestroy($banner);
                }
            }
        }

        foreach ($overlayrecords as $overlayrecord) {
            self::draw_layer_overlay($canvas, $overlayrecord, $width, $height);
        }

        foreach ($borderrecords as $borderrecord) {
            self::draw_layer_border(
                $canvas,
                $borderrecord,
                ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height],
                $width,
                $height,
                true
            );
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
        $cardborderrecords = [];

        foreach ($layerspecs as $layerspec) {
            $record = $layerspec['record'];
            $fitmode = $layerspec['fitmode'] ?? self::FIT_MODE_BANNER;
            $file = self::get_banner_image_file($record);
            $isoverlay = self::record_overlay_targets_banner($record);
            if (!$file && empty($record->borderenabled) && !$isoverlay) {
                continue;
            }

            if (!$canvas) {
                $canvas = imagecreatetruecolor($width, $height);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            }

            if ($cardmode && !empty($record->borderenabled)) {
                $cardborderrecords[] = $record;
            }

            if (!$file) {
                if ($isoverlay) {
                    self::draw_layer_overlay($canvas, $record, $width, $height);
                }
                if (!empty($record->borderenabled) && !$cardmode) {
                    self::draw_layer_border(
                        $canvas,
                        $record,
                        ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height],
                        $width,
                        $height,
                        $cardmode
                    );
                }
                continue;
            }

            $loadedlayer = self::load_layer_image($file);
            $layer = $loadedlayer['image'] ?? null;
            if (!$layer) {
                if (!empty($record->borderenabled) && !$cardmode) {
                    self::draw_layer_border(
                        $canvas,
                        $record,
                        ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height],
                        $width,
                        $height,
                        $cardmode
                    );
                }
                continue;
            }

            $layerwidth = imagesx($layer);
            $layerheight = imagesy($layer);
            $croppedlayer = self::crop_layer_image($layer, $record, $layerwidth, $layerheight);
            if ($croppedlayer) {
                imagedestroy($layer);
                $layer = $croppedlayer;
                $layerwidth = imagesx($layer);
                $layerheight = imagesy($layer);
            }

            imagealphablending($canvas, true);
            self::apply_image_layer_opacity($layer, (float)($record->imageopacity ?? 1));
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
            if (!$cardmode) {
                self::draw_layer_border($canvas, $record, $bounds, $width, $height, $cardmode);
            }
            imagedestroy($layer);
            if (!empty($loadedlayer['tempfile'])) {
                @unlink($loadedlayer['tempfile']);
            }
        }

        if (!$canvas) {
            return null;
        }

        if ($cardmode && !empty($cardborderrecords)) {
            foreach ($cardborderrecords as $borderrecord) {
                self::draw_layer_border(
                    $canvas,
                    $borderrecord,
                    ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height],
                    $width,
                    $height,
                    true
                );
            }
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
        $effectivedimensions = self::get_effective_image_dimensions_for_crop($record, $layerwidth, $layerheight);
        $box = null;
        $styles[] = 'aspect-ratio: ' . $effectivedimensions['width'] . ' / ' . $effectivedimensions['height'] . ';';
        $styles[] = 'opacity: ' . self::format_css_opacity((float)($record->imageopacity ?? 1)) . ';';

        if ($fitmode === self::FIT_MODE_BANNER) {
            $styles[] = 'width: 100%;';
            $styles[] = 'height: 100%;';
            $styles[] = 'object-fit: fill;';
        } else if ($fitmode === self::FIT_MODE_COVER) {
            $box = self::get_contained_overlay_box_percentages($effectivedimensions['width'], $effectivedimensions['height']);
            $styles[] = 'width: ' . self::format_css_percentage($box['width']) . ';';
            $styles[] = 'height: ' . self::format_css_percentage($box['height']) . ';';
            $styles[] = 'object-fit: fill;';
            $styles[] = 'object-position: ' . self::get_css_object_position_for_anchor($anchor) . ';';
        } else if ($fitmode === self::FIT_MODE_CUSTOM) {
            $renderkeepaspect = !empty($record->customsizekeepaspect);
            $box = [
                'width' => self::normalise_percentage(
                    (float)($record->customwidthpercent ?? 100),
                    0.0,
                    self::CUSTOM_SIZE_PERCENT_MAX
                ),
                'height' => self::normalise_percentage(
                    (float)($record->customheightpercent ?? 100),
                    0.0,
                    self::CUSTOM_SIZE_PERCENT_MAX
                ),
            ];
            if ($renderkeepaspect) {
                $box['height'] = $box['width'] * ((self::DEFAULT_CANVAS_WIDTH / self::DEFAULT_CANVAS_HEIGHT) /
                    ($effectivedimensions['width'] / $effectivedimensions['height']));
                $styles[] = 'width: ' . self::format_css_percentage($box['width']) . ';';
                $styles[] = 'height: auto;';
            } else {
                $styles[] = 'width: ' . self::format_css_percentage($box['width']) . ';';
                $styles[] = 'height: ' . self::format_css_percentage($box['height']) . ';';
            }
        } else {
            $box = [
                'width' => ($effectivedimensions['width'] / self::DEFAULT_CANVAS_WIDTH) * 100,
                'height' => ($effectivedimensions['height'] / self::DEFAULT_CANVAS_HEIGHT) * 100,
            ];
            $styles[] = 'width: ' . self::format_css_percentage($box['width']) . ';';
            $styles[] = 'height: auto;';
        }

        if ($box !== null) {
            $styles = array_merge($styles, self::get_image_layer_position_styles($record, $anchor, $box['width'], $box['height']));
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
            'opacity: ' . self::format_css_opacity((float)($record->imageopacity ?? 1)) . ';',
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
     * @param float|null $banneraspect
     * @return array{wrapperstyle:string,imagestyle:string}
     */
    protected static function build_native_course_header_overlay_styles(
        \stdClass $record,
        \stored_file $file,
        string $fitmode,
        ?float $banneraspect = null
    ): array {
        $styles = self::build_modal_preview_image_layer_styles($record, $fitmode, $file, $banneraspect);
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
        $offsettop = self::format_css_offset_percentage((float)($record->offsettoppercent ?? 0));
        $offsetright = self::format_css_offset_percentage((float)($record->offsetrightpercent ?? 0));
        $offsetbottom = self::format_css_offset_percentage((float)($record->offsetbottompercent ?? 0));
        $offsetleft = self::format_css_offset_percentage((float)($record->offsetleftpercent ?? 0));

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
     * Build image layer positioning, optionally locked relative to its authored centre.
     *
     * @param \stdClass $record
     * @param string $anchor
     * @param float $widthpercent
     * @param float $heightpercent
     * @return array
     */
    protected static function get_image_layer_position_styles(
        \stdClass $record,
        string $anchor,
        float $widthpercent,
        float $heightpercent
    ): array {
        if (empty($record->imagecenterfixed)) {
            return self::get_html_overlay_position_styles($record, $anchor);
        }

        $centre = self::calculate_layer_centre_percentages($record, $anchor, $widthpercent, $heightpercent);
        return [
            'left: ' . self::format_css_percentage($centre['x']) . ';',
            'top: ' . self::format_css_percentage($centre['y']) . ';',
            'transform: translate(-50%, -50%);',
        ];
    }

    /**
     * Calculate a layer centre from the authored anchor and offsets.
     *
     * @param \stdClass $record
     * @param string $anchor
     * @param float $widthpercent
     * @param float $heightpercent
     * @return array{x:float,y:float}
     */
    protected static function calculate_layer_centre_percentages(
        \stdClass $record,
        string $anchor,
        float $widthpercent,
        float $heightpercent
    ): array {
        $top = self::normalise_percentage((float)($record->offsettoppercent ?? 0), -1000.0, 1000.0);
        $right = self::normalise_percentage((float)($record->offsetrightpercent ?? 0), -1000.0, 1000.0);
        $bottom = self::normalise_percentage((float)($record->offsetbottompercent ?? 0), -1000.0, 1000.0);
        $left = self::normalise_percentage((float)($record->offsetleftpercent ?? 0), -1000.0, 1000.0);
        $halfwidth = $widthpercent / 2;
        $halfheight = $heightpercent / 2;

        return match (self::normalise_position_anchor($anchor)) {
            self::POSITION_TOP => ['x' => 50.0, 'y' => $top + $halfheight],
            self::POSITION_BOTTOM => ['x' => 50.0, 'y' => 100.0 - $bottom - $halfheight],
            self::POSITION_LEFT => ['x' => $left + $halfwidth, 'y' => 50.0],
            self::POSITION_RIGHT => ['x' => 100.0 - $right - $halfwidth, 'y' => 50.0],
            self::POSITION_TOP_LEFT => ['x' => $left + $halfwidth, 'y' => $top + $halfheight],
            self::POSITION_TOP_RIGHT => ['x' => 100.0 - $right - $halfwidth, 'y' => $top + $halfheight],
            self::POSITION_BOTTOM_LEFT => ['x' => $left + $halfwidth, 'y' => 100.0 - $bottom - $halfheight],
            self::POSITION_BOTTOM_RIGHT => ['x' => 100.0 - $right - $halfwidth, 'y' => 100.0 - $bottom - $halfheight],
            default => ['x' => 50.0, 'y' => 50.0],
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

        $widthlimit = self::normalise_percentage(
            (float)($record->customwidthpercent ?? 100),
            0.0,
            self::CUSTOM_SIZE_PERCENT_MAX
        );
        if (!empty($record->customsizekeepaspect)) {
            $effectivedimensions = self::get_effective_image_dimensions_for_crop($record, $layerwidth, $layerheight);
            $imageaspect = $effectivedimensions['width'] / $effectivedimensions['height'];
            $canvasaspect = self::DEFAULT_CANVAS_WIDTH / self::DEFAULT_CANVAS_HEIGHT;
            return [
                'width' => $widthlimit,
                'height' => $widthlimit * ($canvasaspect / $imageaspect),
            ];
        }

        $heightlimit = self::normalise_percentage(
            (float)($record->customheightpercent ?? 100),
            0.0,
            self::CUSTOM_SIZE_PERCENT_MAX
        );
        return ['width' => $widthlimit, 'height' => $heightlimit];
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
        if (!empty($record->imagecenterfixed)) {
            $centre = self::calculate_layer_centre_percentages($record, $anchor, $widthpercent, $heightpercent);
            return [
                'left: ' . self::format_css_percentage($centre['x']) . ';',
                'top: ' . self::format_css_percentage($centre['y']) . ';',
                'transform: translate(-50%, -50%);',
            ];
        }

        $top = self::normalise_percentage((float)($record->offsettoppercent ?? 0), -1000.0, 1000.0);
        $right = self::normalise_percentage((float)($record->offsetrightpercent ?? 0), -1000.0, 1000.0);
        $bottom = self::normalise_percentage((float)($record->offsetbottompercent ?? 0), -1000.0, 1000.0);
        $left = self::normalise_percentage((float)($record->offsetleftpercent ?? 0), -1000.0, 1000.0);

        return match ($anchor) {
            self::POSITION_TOP_LEFT => [
                'left: ' . self::format_css_offset_percentage($left) . ';',
                'top: ' . self::format_css_offset_percentage($top) . ';',
            ],
            self::POSITION_TOP_RIGHT => [
                'right: ' . self::format_css_offset_percentage($right) . ';',
                'top: ' . self::format_css_offset_percentage($top) . ';',
            ],
            self::POSITION_BOTTOM_LEFT => [
                'left: ' . self::format_css_offset_percentage($left) . ';',
                'bottom: ' . self::format_css_offset_percentage($bottom) . ';',
            ],
            self::POSITION_BOTTOM_RIGHT => [
                'right: ' . self::format_css_offset_percentage($right) . ';',
                'bottom: ' . self::format_css_offset_percentage($bottom) . ';',
            ],
            self::POSITION_LEFT => [
                'left: ' . self::format_css_offset_percentage($left) . ';',
                'top: 50%;',
                'transform: translateY(-50%);',
            ],
            self::POSITION_RIGHT => [
                'right: ' . self::format_css_offset_percentage($right) . ';',
                'top: 50%;',
                'transform: translateY(-50%);',
            ],
            self::POSITION_TOP => [
                'left: 50%;',
                'top: ' . self::format_css_offset_percentage($top) . ';',
                'transform: translateX(-50%);',
            ],
            self::POSITION_BOTTOM => [
                'left: 50%;',
                'bottom: ' . self::format_css_offset_percentage($bottom) . ';',
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
        if (!is_finite($value)) {
            $value = 0.0;
        }
        return rtrim(rtrim(sprintf('%.6F', $value), '0'), '.') . '%';
    }

    /**
     * Format a stored opacity value for CSS.
     *
     * @param float $value
     * @return string
     */
    protected static function format_css_opacity(float $value): string {
        return rtrim(rtrim(sprintf('%.3F', self::normalise_unit_float($value, 1)), '0'), '.');
    }

    /**
     * Format a numeric offset percentage for CSS output.
     *
     * @param float $value
     * @return string
     */
    protected static function format_css_offset_percentage(float $value): string {
        return rtrim(rtrim(sprintf('%.6F', self::normalise_percentage($value, -1000.0, 1000.0)), '0'), '.') . '%';
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
     * Apply a uniform opacity to a GD layer before compositing.
     *
     * @param resource|\GdImage $layer
     * @param float $opacity
     * @return void
     */
    protected static function apply_image_layer_opacity($layer, float $opacity): void {
        $opacity = self::normalise_unit_float($opacity, 1);
        if ($opacity >= 0.999) {
            return;
        }
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefilter($layer, IMG_FILTER_COLORIZE, 0, 0, 0, (int)round(127 * (1 - $opacity)));
    }

    /**
     * Create a cropped copy of a GD layer when a non-destructive crop is stored.
     *
     * @param resource|\GdImage $layer
     * @param \stdClass $record
     * @param int $layerwidth
     * @param int $layerheight
     * @return resource|\GdImage|null
     */
    protected static function crop_layer_image($layer, \stdClass $record, int $layerwidth, int $layerheight) {
        if ($layerwidth <= 0 || $layerheight <= 0) {
            return null;
        }
        $crop = self::normalise_image_crop($record);
        if (!$crop['enabled']) {
            return null;
        }

        $sourcex = max(0, min($layerwidth - 1, (int)floor(($crop['left'] / 100) * $layerwidth)));
        $sourcey = max(0, min($layerheight - 1, (int)floor(($crop['top'] / 100) * $layerheight)));
        $sourcewidth = max(1, min($layerwidth - $sourcex, (int)round(($crop['width'] / 100) * $layerwidth)));
        $sourceheight = max(1, min($layerheight - $sourcey, (int)round(($crop['height'] / 100) * $layerheight)));

        $cropped = imagecreatetruecolor($sourcewidth, $sourceheight);
        if (!$cropped) {
            return null;
        }
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        imagefill($cropped, 0, 0, imagecolorallocatealpha($cropped, 0, 0, 0, 127));
        imagecopy(
            $cropped,
            $layer,
            0,
            0,
            $sourcex,
            $sourcey,
            $sourcewidth,
            $sourceheight
        );

        return $cropped;
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

        $targetwidth = max(1, (int)round($canvaswidth * self::normalise_percentage(
            (float)($record->customwidthpercent ?? 100),
            0.0,
            self::CUSTOM_SIZE_PERCENT_MAX
        ) / 100));
        $targetheight = max(1, (int)round($canvasheight * self::normalise_percentage(
            (float)($record->customheightpercent ?? 100),
            0.0,
            self::CUSTOM_SIZE_PERCENT_MAX
        ) / 100));
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
     * @param string $anchor
     * @param \stdClass|null $record
     * @return array
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
        if ($record !== null && !empty($record->imagecenterfixed)) {
            $widthpercent = ($contentwidth / max(1, $canvaswidth)) * 100;
            $heightpercent = ($contentheight / max(1, $canvasheight)) * 100;
            $centre = self::calculate_layer_centre_percentages($record, $anchor, $widthpercent, $heightpercent);

            return [
                (int)round(($canvaswidth * $centre['x'] / 100) - ($contentwidth / 2)),
                (int)round(($canvasheight * $centre['y'] / 100) - ($contentheight / 2)),
            ];
        }

        $anchor = self::normalise_position_anchor($anchor);
        $offsettop = (int)round($canvasheight * self::normalise_percentage((float)($record->offsettoppercent ?? 0), -1000.0, 1000.0) / 100);
        $offsetright = (int)round($canvaswidth * self::normalise_percentage((float)($record->offsetrightpercent ?? 0), -1000.0, 1000.0) / 100);
        $offsetbottom = (int)round($canvasheight * self::normalise_percentage((float)($record->offsetbottompercent ?? 0), -1000.0, 1000.0) / 100);
        $offsetleft = (int)round($canvaswidth * self::normalise_percentage((float)($record->offsetleftpercent ?? 0), -1000.0, 1000.0) / 100);

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
     * Whether an overlay layer should affect the generated banner/card image.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function record_overlay_targets_banner(\stdClass $record): bool {
        if (empty($record->overlayenabled) || empty($record->isenabled)) {
            return false;
        }

        $target = self::normalise_overlay_target((string)($record->overlaytarget ?? self::OVERLAY_TARGET_BOTH));
        return in_array($target, [self::OVERLAY_TARGET_BANNER, self::OVERLAY_TARGET_BOTH], true);
    }

    /**
     * Whether an overlay layer should override slideshow overlay settings.
     *
     * @param \stdClass $record
     * @return bool
     */
    protected static function record_overlay_targets_slideshow(\stdClass $record): bool {
        if (empty($record->overlayenabled) || empty($record->isenabled)) {
            return false;
        }

        $target = self::normalise_overlay_target((string)($record->overlaytarget ?? self::OVERLAY_TARGET_BOTH));
        return in_array($target, [self::OVERLAY_TARGET_SLIDESHOW, self::OVERLAY_TARGET_BOTH], true);
    }

    /**
     * Draw a full-surface overlay on the banner canvas.
     *
     * @param resource|\GdImage $canvas
     * @param \stdClass $record
     * @param int $canvaswidth
     * @param int $canvasheight
     * @return void
     */
    protected static function draw_layer_overlay($canvas, \stdClass $record, int $canvaswidth, int $canvasheight): void {
        if (!self::record_overlay_targets_banner($record)) {
            return;
        }

        $rgba = self::parse_color_to_rgba(self::normalise_color_string((string)($record->overlaybannercolor ?? '#000000')));
        if (!$rgba) {
            $rgba = [0, 0, 0, 0];
        }
        $opacity = self::normalise_percentage((float)($record->overlaybanneropacity ?? 25), 0.0, 100.0) / 100;
        $alpha = 127 - (int)round($opacity * 127);
        if (isset($rgba[3]) && $rgba[3] > 0) {
            $alpha = max($alpha, (int)$rgba[3]);
        }

        imagealphablending($canvas, true);
        $color = imagecolorallocatealpha($canvas, (int)$rgba[0], (int)$rgba[1], (int)$rgba[2], $alpha);
        if ($color !== false) {
            imagefilledrectangle($canvas, 0, 0, max(0, $canvaswidth - 1), max(0, $canvasheight - 1), $color);
        }
    }

    /**
     * Convert a stored colour and opacity to a CSS rgba() value.
     *
     * @param string $color
     * @param float $opacity
     * @return string
     */
    protected static function css_rgba_from_color(string $color, float $opacity): string {
        $rgba = self::parse_color_to_rgba($color);
        if (!$rgba) {
            $rgba = [0, 0, 0, 0];
        }
        $baseopacity = 1 - (((int)($rgba[3] ?? 0)) / 127);
        $effectiveopacity = self::normalise_unit_float($opacity, 0.25) * $baseopacity;
        return 'rgba(' . (int)$rgba[0] . ', ' . (int)$rgba[1] . ', ' . (int)$rgba[2] . ', ' .
            round($effectiveopacity, 3) . ')';
    }

    /**
     * Draw a configurable border around the visible bounds of one rendered layer.
     *
     * @param resource|\GdImage $canvas
     * @param \stdClass $record
     * @param array $bounds
     * @param int $canvaswidth
     * @param int $canvasheight
     * @param bool $cardmode
     * @return void
     */
    protected static function draw_layer_border(
        $canvas,
        \stdClass $record,
        array $bounds,
        int $canvaswidth,
        int $canvasheight,
        bool $cardmode = false
    ): void {
        if (empty($record->borderenabled)) {
            return;
        }

        $sides = self::normalise_border_sides(explode(',', (string)($record->bordersides ?? 'top,right,bottom,left')));
        if ($cardmode) {
            $borderwidth = self::get_course_card_border_width_pixels(
                (float)($record->borderwidth ?? 0),
                $canvaswidth,
                $canvasheight
            );
        } else {
            $borderwidth = self::get_border_width_pixels(
                (float)($record->borderwidth ?? 0),
                (int)($bounds['width'] ?? 0),
                (int)($bounds['height'] ?? 0)
            );
        }
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

        $outerleft = max(0, (int)$bounds['x']);
        $outertop = max(0, (int)$bounds['y']);
        $outerright = min($canvaswidth - 1, $outerleft + max(0, (int)$bounds['width']) - 1);
        $outerbottom = min($canvasheight - 1, $outertop + max(0, (int)$bounds['height']) - 1);
        $insetstart = (int)floor($borderwidth / 2);
        $insetend = (int)ceil($borderwidth / 2) - 1;
        $left = min($outerright, $outerleft + $insetstart);
        $top = min($outerbottom, $outertop + $insetstart);
        $right = max($left, $outerright - $insetend);
        $bottom = max($top, $outerbottom - $insetend);

        $rounded = !empty($record->borderinnerrounded);
        $separatesquarecorners = $cardmode;
        $activewidths = [
            'top' => in_array('top', $sides, true) ? $borderwidth : 0,
            'right' => in_array('right', $sides, true) ? $borderwidth : 0,
            'bottom' => in_array('bottom', $sides, true) ? $borderwidth : 0,
            'left' => in_array('left', $sides, true) ? $borderwidth : 0,
        ];
        $innerwidth = max(0, ($outerright - $outerleft + 1) - $activewidths['left'] - $activewidths['right']);
        $innerheight = max(0, ($outerbottom - $outertop + 1) - $activewidths['top'] - $activewidths['bottom']);
        $innerradius = $rounded ? max(0, min($borderwidth, (int)floor($innerwidth / 2), (int)floor($innerheight / 2))) : 0;
        if ($rounded && $innerradius <= 0) {
            $rounded = false;
        }
        $separatecorners = $rounded || $separatesquarecorners;
        $cutoutsize = $rounded ? ($borderwidth + $innerradius) : ($separatesquarecorners ? $borderwidth : 0);
        $hastopleftcorner = $separatecorners && in_array('top', $sides, true) && in_array('left', $sides, true);
        $hastoprightcorner = $separatecorners && in_array('top', $sides, true) && in_array('right', $sides, true);
        $hasbottomrightcorner = $separatecorners && in_array('bottom', $sides, true) && in_array('right', $sides, true);
        $hasbottomleftcorner = $separatecorners && in_array('bottom', $sides, true) && in_array('left', $sides, true);

        if (in_array('top', $sides, true)) {
            self::draw_border_line(
                $canvas,
                $outerleft + ($hastopleftcorner ? $cutoutsize : 0),
                $top,
                $outerright - ($hastoprightcorner ? $cutoutsize : 0),
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
                $outertop + ($hastoprightcorner ? $cutoutsize : 0),
                $right,
                $outerbottom - ($hasbottomrightcorner ? $cutoutsize : 0),
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
                $outerleft + ($hasbottomleftcorner ? $cutoutsize : 0),
                $bottom,
                $outerright - ($hasbottomrightcorner ? $cutoutsize : 0),
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
                $outertop + ($hastopleftcorner ? $cutoutsize : 0),
                $left,
                $outerbottom - ($hasbottomleftcorner ? $cutoutsize : 0),
                'left',
                $style,
                $color,
                $fade,
                $borderwidth,
                $dashspec
            );
        }

        if ($rounded && $hastopleftcorner) {
            self::draw_inner_rounded_border_corner($canvas, $outerleft, $outertop, $cutoutsize, $innerradius, $color, 'top-left', $fade);
        } else if ($separatesquarecorners && $hastopleftcorner) {
            self::draw_square_border_corner($canvas, $outerleft, $outertop, $cutoutsize, $color, 'top-left', $fade);
        }
        if ($rounded && $hastoprightcorner) {
            self::draw_inner_rounded_border_corner($canvas, $outerright - $cutoutsize + 1, $outertop, $cutoutsize, $innerradius, $color, 'top-right', $fade);
        } else if ($separatesquarecorners && $hastoprightcorner) {
            self::draw_square_border_corner($canvas, $outerright - $cutoutsize + 1, $outertop, $cutoutsize, $color, 'top-right', $fade);
        }
        if ($rounded && $hasbottomrightcorner) {
            self::draw_inner_rounded_border_corner(
                $canvas,
                $outerright - $cutoutsize + 1,
                $outerbottom - $cutoutsize + 1,
                $cutoutsize,
                $innerradius,
                $color,
                'bottom-right',
                $fade
            );
        } else if ($separatesquarecorners && $hasbottomrightcorner) {
            self::draw_square_border_corner(
                $canvas,
                $outerright - $cutoutsize + 1,
                $outerbottom - $cutoutsize + 1,
                $cutoutsize,
                $color,
                'bottom-right',
                $fade
            );
        }
        if ($rounded && $hasbottomleftcorner) {
            self::draw_inner_rounded_border_corner($canvas, $outerleft, $outerbottom - $cutoutsize + 1, $cutoutsize, $innerradius, $color, 'bottom-left', $fade);
        } else if ($separatesquarecorners && $hasbottomleftcorner) {
            self::draw_square_border_corner($canvas, $outerleft, $outerbottom - $cutoutsize + 1, $cutoutsize, $color, 'bottom-left', $fade);
        }
    }

    /**
     * Draw one square card-thumbnail border corner without overlapping side alpha.
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
    protected static function draw_square_border_corner(
        $canvas,
        int $x,
        int $y,
        int $size,
        int $color,
        string $corner,
        float $fade
    ): void {
        if ($size <= 0) {
            return;
        }

        if ($fade <= 0) {
            imagefilledrectangle($canvas, $x, $y, $x + $size - 1, $y + $size - 1, $color);
            return;
        }

        [$red, $green, $blue, $basealpha] = self::extract_gd_color_channels($color);
        $colorcache = [];
        $steps = max(1, $size - 1);
        $canvaswidth = imagesx($canvas);
        $canvasheight = imagesy($canvas);
        for ($pixelx = 0; $pixelx < $size; $pixelx++) {
            for ($pixely = 0; $pixely < $size; $pixely++) {
                $targetx = $x + $pixelx;
                $targety = $y + $pixely;
                if ($targetx < 0 || $targetx >= $canvaswidth || $targety < 0 || $targety >= $canvasheight) {
                    continue;
                }
                $horizontalprogress = match ($corner) {
                    'top-right', 'bottom-right' => ($size - 1 - $pixelx) / $steps,
                    default => $pixelx / $steps,
                };
                $verticalprogress = match ($corner) {
                    'bottom-left', 'bottom-right' => ($size - 1 - $pixely) / $steps,
                    default => $pixely / $steps,
                };
                $progress = min(1, max(0, min($horizontalprogress, $verticalprogress)));
                $alpha = (int)round($basealpha + ((127 - $basealpha) * $fade * $progress));
                $pixelcolor = self::allocate_cached_gd_alpha_color($canvas, $red, $green, $blue, $alpha, $colorcache);
                imagesetpixel($canvas, $targetx, $targety, $pixelcolor);
            }
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
     * @param int $innerradius
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
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     * @param string $side
     * @param string $style
     * @param int $basecolor
     * @param float $fade
     * @param int $borderwidth
     * @param array|null $dashspec
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
