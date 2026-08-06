<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_course_banner_builder;

defined('MOODLE_INTERNAL') || die();

/**
 * Compatibility adapter from persisted course-layer values to public geometry.
 *
 * This is deliberately limited to public course-header HTML image overlays.
 * It normalises legacy fields in memory and never reads or writes Moodle state.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_header_overlay_geometry_adapter {
    /** @var string */
    public const FIT_BANNER = 'bannerfit';
    /** @var string */
    public const FIT_COVER = 'cover';
    /** @var string */
    public const FIT_ORIGINAL = 'original';
    /** @var string */
    public const FIT_CUSTOM = 'custom';
    /** @var float */
    public const CUSTOM_SIZE_PERCENT_MAX = 1000.0;

    /**
     * Build public HTML-overlay styles through the canonical geometry contract.
     *
     * @param array $record Legacy source-element values.
     * @param array $image Image dimensions from the stored file.
     * @param string $fitmode Effective persisted fit mode.
     * @param string $format Current public course banner format.
     * @return array{wrapperstyle:string,imagestyles:array,geometry:array,zindex:int}
     */
    public static function build_styles(array $record, array $image, string $fitmode, string $format): array {
        $format = self::normalise_format($format);
        $fitmode = self::normalise_fitmode($fitmode);
        $formatratio = banner_geometry::get_format_aspect_ratios()[$format];
        $imagewidth = self::positive_integer($image['width'] ?? 0);
        $imageheight = self::positive_integer($image['height'] ?? 0);
        $crop = self::normalise_legacy_crop($record);
        $effectivedimensions = self::get_effective_dimensions($imagewidth, $imageheight, $crop);
        $box = self::get_legacy_box_percentages(
            $record,
            $fitmode,
            $formatratio,
            $imagewidth,
            $imageheight,
            $effectivedimensions
        );
        $position = self::get_top_left_position_percentages($record, $box['width'], $box['height']);

        // The virtual target retains the selected format ratio; emitted CSS stays percentage-based.
        $targetwidth = banner_geometry::CANVAS_WIDTH;
        $targetheight = $targetwidth / $formatratio;
        $geometry = banner_geometry::project([
            'format' => $format,
            'policy' => banner_geometry::POLICY_PUBLIC_BANNER,
            'target' => ['width' => $targetwidth, 'height' => $targetheight],
            'layer' => [
                'x' => $position['x'] * banner_geometry::CANVAS_WIDTH / 100.0,
                'y' => $position['y'] * banner_geometry::CANVAS_HEIGHT / 100.0,
                'width' => $box['width'] * banner_geometry::CANVAS_WIDTH / 100.0,
                'height' => $box['height'] * banner_geometry::CANVAS_HEIGHT / 100.0,
                'scale' => 1.0,
                'transformorigin' => ['x' => 0.0, 'y' => 0.0],
                'fixedposition' => !empty($record['imagecenterfixed']),
                'zindex' => (int)self::number($record, 'geometryzindex', 0.0),
                'fit' => self::get_geometry_fit($fitmode, !empty($record['customsizekeepaspect'])),
                'crop' => $crop,
            ],
            'image' => [
                'width' => $imagewidth > 0 ? $imagewidth : banner_geometry::CANVAS_WIDTH,
                'height' => $imageheight > 0 ? $imageheight : banner_geometry::CANVAS_HEIGHT,
            ],
        ]);

        $layer = $geometry['layer'];
        $target = $geometry['target'];
        $wrapperstyles = [
            'position: absolute;',
            'display: flex;',
            'align-items: stretch;',
            'justify-content: stretch;',
            'overflow: hidden;',
            'left: ' . self::format_percentage(((float)$layer['x'] / (float)$target['width']) * 100.0) . ';',
            'top: ' . self::format_percentage(((float)$layer['y'] / (float)$target['height']) * 100.0) . ';',
            'width: ' . self::format_percentage(((float)$layer['width'] / (float)$target['width']) * 100.0) . ';',
            'height: ' . self::format_percentage(((float)$layer['height'] / (float)$target['height']) * 100.0) . ';',
        ];
        if ($fitmode === self::FIT_CUSTOM && !empty($record['customsizekeepaspect']) &&
                $imagewidth > 0 && $imageheight > 0) {
            $wrapperstyles[] = 'aspect-ratio: ' . $effectivedimensions['width'] . ' / ' .
                $effectivedimensions['height'] . ';';
        }

        $imagestyles = [
            'display: block;',
            'width: 100%;',
            'height: 100%;',
            'opacity: ' . self::format_opacity(self::number($record, 'imageopacity', 1.0)) . ';',
            'object-fit: ' . self::get_css_object_fit(
                $fitmode,
                !empty($record['customsizekeepaspect']),
                $imagewidth > 0 && $imageheight > 0
            ) . ';',
        ];
        if ($fitmode !== self::FIT_BANNER) {
            $imagestyles[] = 'object-position: ' . self::get_css_object_position(
                self::normalise_anchor((string)($record['positionanchor'] ?? 'center'))
            ) . ';';
        }
        if ($fitmode === self::FIT_ORIGINAL) {
            $imagestyles[] = 'height: auto;';
        }

        return [
            'wrapperstyle' => implode(' ', $wrapperstyles),
            'imagestyles' => $imagestyles,
            'geometry' => $geometry,
            'zindex' => (int)$geometry['layer']['zindex'],
        ];
    }

    /** @return string */
    private static function normalise_format(string $format): string {
        $formats = banner_geometry::get_format_aspect_ratios();
        return array_key_exists($format, $formats) ? $format : 'standard';
    }

    /** @return string */
    private static function normalise_fitmode(string $fitmode): string {
        return in_array($fitmode, [self::FIT_BANNER, self::FIT_COVER, self::FIT_ORIGINAL, self::FIT_CUSTOM], true) ?
            $fitmode : self::FIT_ORIGINAL;
    }

    /** @return array{enabled:bool,x:float,y:float,width:float,height:float} */
    private static function normalise_legacy_crop(array $record): array {
        if (empty($record['imagecropenabled'])) {
            return banner_geometry::normalise_crop([]);
        }
        return banner_geometry::normalise_crop([
            'x' => self::number($record, 'imagecropleftpercent', 0.0),
            'y' => self::number($record, 'imagecroptoppercent', 0.0),
            'width' => self::number($record, 'imagecropwidthpercent', 100.0),
            'height' => self::number($record, 'imagecropheightpercent', 100.0),
        ]);
    }

    /** @return array{width:int,height:int} */
    private static function get_effective_dimensions(int $width, int $height, array $crop): array {
        $width = max(1, $width ?: (int)banner_geometry::CANVAS_WIDTH);
        $height = max(1, $height ?: (int)banner_geometry::CANVAS_HEIGHT);
        return [
            'width' => max(1, (int)round($width * ((float)$crop['width'] / 100.0))),
            'height' => max(1, (int)round($height * ((float)$crop['height'] / 100.0))),
        ];
    }

    /** @return array{width:float,height:float} */
    private static function get_legacy_box_percentages(
        array $record,
        string $fitmode,
        float $banneraspect,
        int $imagewidth,
        int $imageheight,
        array $effectivedimensions
    ): array {
        if ($fitmode === self::FIT_BANNER || ($fitmode === self::FIT_COVER && ($imagewidth <= 0 || $imageheight <= 0))) {
            return ['width' => 100.0, 'height' => 100.0];
        }
        if ($fitmode === self::FIT_COVER) {
            return self::get_contained_box_percentages(
                $effectivedimensions['width'],
                $effectivedimensions['height'],
                $banneraspect
            );
        }
        if ($fitmode === self::FIT_CUSTOM) {
            $width = self::percentage($record, 'customwidthpercent', 100.0, 0.0, self::CUSTOM_SIZE_PERCENT_MAX);
            $height = self::percentage($record, 'customheightpercent', 100.0, 0.0, self::CUSTOM_SIZE_PERCENT_MAX);
            if (!empty($record['customsizekeepaspect']) && $imagewidth > 0 && $imageheight > 0) {
                $height = $width * ($banneraspect / ($effectivedimensions['width'] / $effectivedimensions['height']));
            }
            return ['width' => $width, 'height' => $height];
        }

        $width = ($effectivedimensions['width'] / banner_geometry::CANVAS_WIDTH) * 100.0;
        return [
            'width' => $width,
            'height' => $width * ($banneraspect / ($effectivedimensions['width'] / $effectivedimensions['height'])),
        ];
    }

    /** @return array{width:float,height:float} */
    private static function get_contained_box_percentages(int $width, int $height, float $banneraspect): array {
        if ($width <= 0 || $height <= 0) {
            return ['width' => 100.0, 'height' => 100.0];
        }
        $imageaspect = $width / $height;
        if ($imageaspect >= $banneraspect) {
            return ['width' => 100.0, 'height' => 100.0 * ($banneraspect / $imageaspect)];
        }
        return ['width' => 100.0 * ($imageaspect / $banneraspect), 'height' => 100.0];
    }

    /** @return array{x:float,y:float} */
    private static function get_top_left_position_percentages(array $record, float $width, float $height): array {
        $anchor = self::normalise_anchor((string)($record['positionanchor'] ?? 'center'));
        $top = self::percentage($record, 'offsettoppercent', 0.0, -1000.0, 1000.0);
        $right = self::percentage($record, 'offsetrightpercent', 0.0, -1000.0, 1000.0);
        $bottom = self::percentage($record, 'offsetbottompercent', 0.0, -1000.0, 1000.0);
        $left = self::percentage($record, 'offsetleftpercent', 0.0, -1000.0, 1000.0);
        if (!empty($record['imagecenterfixed'])) {
            $centre = self::get_anchor_centre($anchor, $top, $right, $bottom, $left, $width, $height);
            return ['x' => $centre['x'] - ($width / 2.0), 'y' => $centre['y'] - ($height / 2.0)];
        }
        return match ($anchor) {
            'top' => ['x' => 50.0 - ($width / 2.0), 'y' => $top],
            'bottom' => ['x' => 50.0 - ($width / 2.0), 'y' => 100.0 - $bottom - $height],
            'left' => ['x' => $left, 'y' => 50.0 - ($height / 2.0)],
            'right' => ['x' => 100.0 - $right - $width, 'y' => 50.0 - ($height / 2.0)],
            'top-left' => ['x' => $left, 'y' => $top],
            'top-right' => ['x' => 100.0 - $right - $width, 'y' => $top],
            'bottom-left' => ['x' => $left, 'y' => 100.0 - $bottom - $height],
            'bottom-right' => ['x' => 100.0 - $right - $width, 'y' => 100.0 - $bottom - $height],
            default => ['x' => 50.0 - ($width / 2.0), 'y' => 50.0 - ($height / 2.0)],
        };
    }

    /** @return array{x:float,y:float} */
    private static function get_anchor_centre(
        string $anchor,
        float $top,
        float $right,
        float $bottom,
        float $left,
        float $width,
        float $height
    ): array {
        $halfwidth = $width / 2.0;
        $halfheight = $height / 2.0;
        return match ($anchor) {
            'top' => ['x' => 50.0, 'y' => $top + $halfheight],
            'bottom' => ['x' => 50.0, 'y' => 100.0 - $bottom - $halfheight],
            'left' => ['x' => $left + $halfwidth, 'y' => 50.0],
            'right' => ['x' => 100.0 - $right - $halfwidth, 'y' => 50.0],
            'top-left' => ['x' => $left + $halfwidth, 'y' => $top + $halfheight],
            'top-right' => ['x' => 100.0 - $right - $halfwidth, 'y' => $top + $halfheight],
            'bottom-left' => ['x' => $left + $halfwidth, 'y' => 100.0 - $bottom - $halfheight],
            'bottom-right' => ['x' => 100.0 - $right - $halfwidth, 'y' => 100.0 - $bottom - $halfheight],
            default => ['x' => 50.0, 'y' => 50.0],
        };
    }

    /** @return string */
    private static function normalise_anchor(string $anchor): string {
        $anchors = ['center', 'top', 'bottom', 'left', 'right', 'top-left', 'top-right', 'bottom-left', 'bottom-right'];
        return in_array($anchor, $anchors, true) ? $anchor : 'center';
    }

    /** @return string */
    private static function get_geometry_fit(string $fitmode, bool $keepaspect): string {
        if ($fitmode === self::FIT_CUSTOM && $keepaspect) {
            return 'contain';
        }
        return 'fill';
    }

    /** @return string */
    private static function get_css_object_fit(string $fitmode, bool $keepaspect, bool $hasimage): string {
        if ($fitmode === self::FIT_ORIGINAL) {
            return 'none';
        }
        if ($fitmode === self::FIT_CUSTOM && $keepaspect) {
            return 'contain';
        }
        if ($fitmode === self::FIT_COVER) {
            return $hasimage ? 'fill' : 'contain';
        }
        return 'fill';
    }

    /** @return string */
    private static function get_css_object_position(string $anchor): string {
        return match ($anchor) {
            'top' => 'center top',
            'bottom' => 'center bottom',
            'left' => 'left center',
            'right' => 'right center',
            'top-left' => 'left top',
            'top-right' => 'right top',
            'bottom-left' => 'left bottom',
            'bottom-right' => 'right bottom',
            default => 'center center',
        };
    }

    /** @return float */
    private static function percentage(array $record, string $field, float $default, float $min, float $max): float {
        return max($min, min($max, self::number($record, $field, $default)));
    }

    /** @return float */
    private static function number(array $record, string $field, float $default): float {
        if (!array_key_exists($field, $record)) {
            return $default;
        }
        $value = $record[$field];
        return is_numeric($value) && is_finite((float)$value) ? (float)$value : 0.0;
    }

    /** @return int */
    private static function positive_integer($value): int {
        return is_numeric($value) && is_finite((float)$value) ? max(0, (int)$value) : 0;
    }

    /** @return string */
    private static function format_percentage(float $value): string {
        return rtrim(rtrim(sprintf('%.6F', $value), '0'), '.') . '%';
    }

    /** @return string */
    private static function format_opacity(float $value): string {
        return rtrim(rtrim(sprintf('%.3F', max(0.0, min(1.0, $value))), '0'), '.') ?: '0';
    }
}
