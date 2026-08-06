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

namespace local_course_banner_builder;

defined('MOODLE_INTERNAL') || die();

/**
 * Pure canonical geometry projections for Course Banner Builder.
 *
 * This class deliberately has no dependency on stored configuration, Moodle
 * globals, files, GD, or HTML. Renderers will opt into it in a later batch.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class banner_geometry {
    /** Canonical authoring canvas width. */
    public const CANVAS_WIDTH = 1600.0;
    /** Canonical authoring canvas height. */
    public const CANVAS_HEIGHT = 400.0;
    /** Preview policy. */
    public const POLICY_PREVIEW = 'preview';
    /** Strict public banner policy. */
    public const POLICY_PUBLIC_BANNER = 'public_banner';
    /** Intentional course-card/thumbnail adaptation policy. */
    public const POLICY_THUMBNAIL = 'thumbnail';
    /** Final projection precision; calculations retain precision until output. */
    public const ROUNDING_PRECISION = 6;
    /** Existing intentional thumbnail scale for eligible positioned layers. */
    public const THUMBNAIL_LAYER_SCALE = 1.16;
    /** Existing intentional thumbnail border reduction multiplier. */
    public const THUMBNAIL_BORDER_SCALE = 0.55;
    /** Existing maximum thumbnail border percentage. */
    public const THUMBNAIL_BORDER_PERCENT_CAP = 11.0;

    /**
     * Return the supported banner format ratios.
     *
     * @return array<string, float>
     */
    public static function get_format_aspect_ratios(): array {
        return [
            'standard' => 4.0,
            'contentwide' => 5.0,
            'fullwidthtop' => 5.0,
            'fullwidthtopcompact' => 8.0,
            'fullwidthtopinset' => 6.1,
        ];
    }

    /**
     * Project canonical source geometry into an explicit target container.
     *
     * Required input: target.width and target.height. Optional input is grouped
     * under format, policy, layer, image, title, and border. Coordinates and
     * dimensions in layer/title are canonical pixels unless marked percent.
     *
     * @param array $input Explicit, immutable-by-convention source data.
     * @return array<string, mixed> Deterministic projected geometry.
     * @throws \InvalidArgumentException For invalid contract input.
     */
    public static function project(array $input): array {
        $format = (string)($input['format'] ?? 'standard');
        $policy = (string)($input['policy'] ?? self::POLICY_PREVIEW);
        $formats = self::get_format_aspect_ratios();
        if (!array_key_exists($format, $formats)) {
            throw new \InvalidArgumentException('Unsupported banner format: ' . $format);
        }
        if (!in_array($policy, self::get_policies(), true)) {
            throw new \InvalidArgumentException('Unsupported banner geometry policy: ' . $policy);
        }

        $target = is_array($input['target'] ?? null) ? $input['target'] : [];
        $targetwidth = self::positive_number($target['width'] ?? null, 'target.width');
        $targetheight = self::positive_number($target['height'] ?? null, 'target.height');
        $xscale = $targetwidth / self::CANVAS_WIDTH;
        $yscale = $targetheight / self::CANVAS_HEIGHT;

        $layer = is_array($input['layer'] ?? null) ? $input['layer'] : [];
        $layerx = self::number($layer['x'] ?? 0.0, 'layer.x');
        $layery = self::number($layer['y'] ?? 0.0, 'layer.y');
        $layerwidth = self::nonnegative_number($layer['width'] ?? self::CANVAS_WIDTH, 'layer.width');
        $layerheight = self::nonnegative_number($layer['height'] ?? self::CANVAS_HEIGHT, 'layer.height');
        $layerscale = self::positive_number($layer['scale'] ?? 1.0, 'layer.scale');
        $rotation = self::number($layer['rotation'] ?? 0.0, 'layer.rotation');
        if ($rotation != 0.0) {
            throw new \InvalidArgumentException('Layer rotation is reserved by the geometry contract but is not supported.');
        }
        $origin = self::normalise_origin($layer['transformorigin'] ?? ['x' => 0.5, 'y' => 0.5]);
        $fit = (string)($layer['fit'] ?? 'contain');
        if (!in_array($fit, ['contain', 'cover', 'fill', 'original'], true)) {
            throw new \InvalidArgumentException('Unsupported image fit policy: ' . $fit);
        }

        $thumbnailadaptable = !empty($layer['thumbnailadaptable']);
        $policylayerscale = $policy === self::POLICY_THUMBNAIL && $thumbnailadaptable ?
            self::THUMBNAIL_LAYER_SCALE : 1.0;
        $effectivescale = $layerscale * $policylayerscale;
        $projectedlayer = [
            'x' => self::round($layerx * $xscale),
            'y' => self::round($layery * $yscale),
            'width' => self::round($layerwidth * $xscale * $effectivescale),
            'height' => self::round($layerheight * $yscale * $effectivescale),
            'scale' => self::round($effectivescale),
            'transformorigin' => $origin,
            'rotation' => 0.0,
            'fixedposition' => !empty($layer['fixedposition']),
            'zindex' => (int)($layer['zindex'] ?? 0),
        ];

        $crop = self::normalise_crop(is_array($layer['crop'] ?? null) ? $layer['crop'] : []);
        $image = is_array($input['image'] ?? null) ? $input['image'] : [];
        $media = self::project_image_fit($image, $crop, $fit, $projectedlayer);
        $border = self::project_border(is_array($input['border'] ?? null) ? $input['border'] : [], $policy, $targetheight);
        $title = self::project_title(is_array($input['title'] ?? null) ? $input['title'] : [], $targetwidth, $targetheight, $border);

        return [
            'contract' => [
                'canvas' => ['width' => self::CANVAS_WIDTH, 'height' => self::CANVAS_HEIGHT],
                'coordinatespace' => 'canonical_pixels_top_left',
                'roundingprecision' => self::ROUNDING_PRECISION,
                'policy' => $policy,
                'format' => $format,
                'formataspectratio' => $formats[$format],
            ],
            'target' => [
                'width' => self::round($targetwidth),
                'height' => self::round($targetheight),
                'aspectratio' => self::round($targetwidth / $targetheight),
                'xscale' => self::round($xscale),
                'yscale' => self::round($yscale),
            ],
            'normalized' => [
                'layerx' => self::round($layerx / self::CANVAS_WIDTH),
                'layery' => self::round($layery / self::CANVAS_HEIGHT),
                'layerwidth' => self::round($layerwidth / self::CANVAS_WIDTH),
                'layerheight' => self::round($layerheight / self::CANVAS_HEIGHT),
            ],
            'layer' => $projectedlayer,
            'crop' => $crop,
            'image' => $media,
            'title' => $title,
            'border' => $border,
            'adaptations' => [
                'thumbnaillayerscale' => self::round($policylayerscale),
                'thumbnailborderreduction' => $policy === self::POLICY_THUMBNAIL,
            ],
        ];
    }

    /**
     * Return the supported policy identifiers.
     *
     * @return string[]
     */
    public static function get_policies(): array {
        return [self::POLICY_PREVIEW, self::POLICY_PUBLIC_BANNER, self::POLICY_THUMBNAIL];
    }

    /** @param mixed $value @return float */
    private static function number($value, string $name): float {
        if (!is_numeric($value) || !is_finite((float)$value)) {
            throw new \InvalidArgumentException('Expected a finite number for ' . $name . '.');
        }
        return (float)$value;
    }

    /** @param mixed $value @return float */
    private static function positive_number($value, string $name): float {
        $number = self::number($value, $name);
        if ($number <= 0.0) {
            throw new \InvalidArgumentException('Expected a positive number for ' . $name . '.');
        }
        return $number;
    }

    /** @param mixed $value @return float */
    private static function nonnegative_number($value, string $name): float {
        $number = self::number($value, $name);
        if ($number < 0.0) {
            throw new \InvalidArgumentException('Expected a non-negative number for ' . $name . '.');
        }
        return $number;
    }

    /** @return array{x:float,y:float} */
    private static function normalise_origin($origin): array {
        if (!is_array($origin)) {
            throw new \InvalidArgumentException('Layer transform origin must be an array.');
        }
        return [
            'x' => self::round(max(0.0, min(1.0, self::number($origin['x'] ?? 0.5, 'layer.transformorigin.x')))),
            'y' => self::round(max(0.0, min(1.0, self::number($origin['y'] ?? 0.5, 'layer.transformorigin.y')))),
        ];
    }

    /**
     * Normalise one crop rectangle using CCB's existing persisted-value rules.
     *
     * @param array $crop Crop percentages from a legacy or canonical caller.
     * @return array{enabled:bool,x:float,y:float,width:float,height:float}
     */
    public static function normalise_crop(array $crop): array {
        $width = max(1.0, min(100.0, self::number($crop['width'] ?? 100.0, 'layer.crop.width')));
        $height = max(1.0, min(100.0, self::number($crop['height'] ?? 100.0, 'layer.crop.height')));
        $x = max(0.0, min(100.0 - $width, self::number($crop['x'] ?? 0.0, 'layer.crop.x')));
        $y = max(0.0, min(100.0 - $height, self::number($crop['y'] ?? 0.0, 'layer.crop.y')));
        return [
            'enabled' => $x > 0.0 || $y > 0.0 || $width < 100.0 || $height < 100.0,
            'x' => self::round($x),
            'y' => self::round($y),
            'width' => self::round($width),
            'height' => self::round($height),
        ];
    }

    /** @return array<string, float|string|bool> */
    private static function project_image_fit(array $image, array $crop, string $fit, array $layer): array {
        $sourcewidth = self::positive_number($image['width'] ?? self::CANVAS_WIDTH, 'image.width');
        $sourceheight = self::positive_number($image['height'] ?? self::CANVAS_HEIGHT, 'image.height');
        $croppedwidth = $sourcewidth * ($crop['width'] / 100.0);
        $croppedheight = $sourceheight * ($crop['height'] / 100.0);
        $focalx = max(0.0, min(1.0, self::number($image['focalx'] ?? 0.5, 'image.focalx')));
        $focaly = max(0.0, min(1.0, self::number($image['focaly'] ?? 0.5, 'image.focaly')));
        $boxwidth = (float)$layer['width'];
        $boxheight = (float)$layer['height'];
        $scale = $fit === 'cover' ? max($boxwidth / $croppedwidth, $boxheight / $croppedheight) :
            ($fit === 'contain' ? min($boxwidth / $croppedwidth, $boxheight / $croppedheight) : 1.0);
        if ($fit === 'fill') {
            return [
                'fit' => $fit, 'sourcewidth' => self::round($croppedwidth), 'sourceheight' => self::round($croppedheight),
                'width' => self::round($boxwidth), 'height' => self::round($boxheight), 'x' => 0.0, 'y' => 0.0,
                'scale' => 1.0, 'focalx' => self::round($focalx), 'focaly' => self::round($focaly),
                'transparent' => !empty($image['transparent']),
            ];
        }
        $renderedwidth = $croppedwidth * $scale;
        $renderedheight = $croppedheight * $scale;
        return [
            'fit' => $fit,
            'sourcewidth' => self::round($croppedwidth),
            'sourceheight' => self::round($croppedheight),
            'width' => self::round($renderedwidth),
            'height' => self::round($renderedheight),
            'x' => self::round(($boxwidth - $renderedwidth) * $focalx),
            'y' => self::round(($boxheight - $renderedheight) * $focaly),
            'scale' => self::round($scale),
            'focalx' => self::round($focalx),
            'focaly' => self::round($focaly),
            'transparent' => !empty($image['transparent']),
        ];
    }

    /** @return array<string, float> */
    private static function project_border(array $border, string $policy, float $targetheight): array {
        $sourcepercent = max(0.0, min(100.0, self::number($border['thicknesspercent'] ?? 0.0, 'border.thicknesspercent')));
        $projectedpercent = $sourcepercent;
        if ($policy === self::POLICY_THUMBNAIL) {
            $projectedpercent = min(self::THUMBNAIL_BORDER_PERCENT_CAP, $sourcepercent * self::THUMBNAIL_BORDER_SCALE);
        }
        return [
            'sourcepercent' => self::round($sourcepercent),
            'projectedpercent' => self::round($projectedpercent),
            'thickness' => self::round($targetheight * $projectedpercent / 100.0),
        ];
    }

    /** @return array<string, float|bool|string|int> */
    private static function project_title(array $title, float $targetwidth, float $targetheight, array $border): array {
        $x = self::number($title['x'] ?? self::CANVAS_WIDTH / 2, 'title.x');
        $y = self::number($title['y'] ?? self::CANVAS_HEIGHT / 2, 'title.y');
        $align = (string)($title['align'] ?? 'center');
        if (!in_array($align, ['left', 'center', 'right'], true)) {
            throw new \InvalidArgumentException('Unsupported title alignment: ' . $align);
        }
        $aboveborder = !empty($title['aboveborder']);
        $horizontalpadding = self::nonnegative_number($title['horizontalpadding'] ?? 0.0, 'title.horizontalpadding') *
            ($targetheight / self::CANVAS_HEIGHT);
        $verticalpadding = self::nonnegative_number($title['verticalpadding'] ?? 0.0, 'title.verticalpadding') *
            ($targetheight / self::CANVAS_HEIGHT);
        $fontsize = self::nonnegative_number($title['fontsize'] ?? 0.0, 'title.fontsize') * ($targetheight / self::CANVAS_HEIGHT);
        $lineheight = self::positive_number($title['lineheightpercent'] ?? 100.0, 'title.lineheightpercent');
        $text = (string)($title['text'] ?? '');
        $linecount = $text === '' ? 1 : substr_count($text, "\n") + 1;
        $anchorx = $x * ($targetwidth / self::CANVAS_WIDTH);
        $safeleft = $aboveborder ? 0.0 : (float)$border['thickness'];
        $saferight = $safeleft;
        $available = $align === 'left' ? $targetwidth - $anchorx - $saferight :
            ($align === 'right' ? $anchorx - $safeleft : 2.0 * min($anchorx - $safeleft, $targetwidth - $saferight - $anchorx));
        $available = max(0.0, $available);
        $maxcontentwidth = max(0.0, $available - (2.0 * $horizontalpadding));
        return [
            'x' => self::round($anchorx),
            'y' => self::round($y * ($targetheight / self::CANVAS_HEIGHT)),
            'align' => $align,
            'aboveborder' => $aboveborder,
            'fontSize' => self::round($fontsize),
            'lineHeightPercent' => self::round($lineheight),
            'horizontalPadding' => self::round($horizontalpadding),
            'verticalPadding' => self::round($verticalpadding),
            'maximumContentWidth' => self::round($maxcontentwidth),
            'maximumFrameWidth' => self::round($maxcontentwidth + (2.0 * $horizontalpadding)),
            'lineCount' => $linecount,
            'minimumFrameHeight' => self::round(($linecount * $fontsize * $lineheight / 100.0) + (2.0 * $verticalpadding)),
        ];
    }

    /** @return float */
    private static function round(float $value): float {
        return round($value, self::ROUNDING_PRECISION);
    }
}
