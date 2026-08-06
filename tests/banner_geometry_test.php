<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

use local_course_banner_builder\banner_geometry;

/**
 * Covers the pure, non-rendering CCB geometry contract.
 */
final class local_course_banner_builder_banner_geometry_test extends advanced_testcase {
    /** @return array */
    protected function base_input(): array {
        return [
            'format' => 'standard',
            'policy' => banner_geometry::POLICY_PREVIEW,
            'target' => ['width' => 1600, 'height' => 400],
            'layer' => [
                'x' => 0, 'y' => 0, 'width' => 1600, 'height' => 400,
                'scale' => 1, 'transformorigin' => ['x' => 0.5, 'y' => 0.5],
            ],
            'image' => ['width' => 1600, 'height' => 400],
        ];
    }

    /**
     * Every published format has the audited ratio.
     *
     * @dataProvider format_provider
     */
    public function test_projects_every_supported_format(string $format, float $ratio): void {
        $input = $this->base_input();
        $input['format'] = $format;
        $actual = banner_geometry::project($input);
        $this->assertSame($ratio, $actual['contract']['formataspectratio']);
    }

    /** @return array */
    public static function format_provider(): array {
        return [
            'standard' => ['standard', 4.0],
            'content wide' => ['contentwide', 5.0],
            'full width top' => ['fullwidthtop', 5.0],
            'compact' => ['fullwidthtopcompact', 8.0],
            'inset' => ['fullwidthtopinset', 6.1],
        ];
    }

    public function test_projects_canonical_coordinates_and_preserves_transform_origin(): void {
        $input = $this->base_input();
        $input['target'] = ['width' => 800, 'height' => 200];
        $input['layer'] = [
            'x' => 0, 'y' => 0, 'width' => 200, 'height' => 100, 'scale' => 1.5,
            'transformorigin' => ['x' => 0.25, 'y' => 0.75], 'fixedposition' => true,
        ];
        $top = banner_geometry::project($input);
        $this->assertSame(0.0, $top['layer']['x']);
        $this->assertSame(0.0, $top['layer']['y']);
        $this->assertSame(150.0, $top['layer']['width']);
        $this->assertSame(['x' => 0.25, 'y' => 0.75], $top['layer']['transformorigin']);
        $this->assertTrue($top['layer']['fixedposition']);

        $input['layer']['x'] = 800;
        $input['layer']['y'] = 200;
        $centre = banner_geometry::project($input);
        $this->assertSame(400.0, $centre['layer']['x']);
        $this->assertSame(100.0, $centre['layer']['y']);

        $input['layer']['x'] = 1600;
        $input['layer']['y'] = 400;
        $input['layer']['width'] = 123.456;
        $input['layer']['height'] = 78.9;
        $bottomright = banner_geometry::project($input);
        $this->assertSame(800.0, $bottomright['layer']['x']);
        $this->assertSame(200.0, $bottomright['layer']['y']);
        $this->assertSame(92.592, $bottomright['layer']['width']);
        $this->assertSame(1.0, $bottomright['normalized']['layerx']);
    }

    public function test_preserves_supported_out_of_bounds_layer_coordinates(): void {
        $input = $this->base_input();
        $input['layer']['x'] = -160;
        $input['layer']['y'] = 520;
        $actual = banner_geometry::project($input);
        $this->assertSame(-160.0, $actual['layer']['x']);
        $this->assertSame(520.0, $actual['layer']['y']);
    }

    public function test_preview_and_public_banner_are_geometry_equivalent(): void {
        $input = $this->base_input();
        $input['target'] = ['width' => 1220, 'height' => 200];
        $input['layer'] = ['x' => 333.3, 'y' => 111.1, 'width' => 540.5, 'height' => 88.8, 'scale' => 0.75];
        $preview = banner_geometry::project($input);
        $input['policy'] = banner_geometry::POLICY_PUBLIC_BANNER;
        $public = banner_geometry::project($input);
        $this->assertSame($preview['layer'], $public['layer']);
        $this->assertSame($preview['title'], $public['title']);
        $this->assertSame($preview['border'], $public['border']);
    }

    public function test_thumbnail_policy_exposes_named_layer_and_border_adaptations(): void {
        $input = $this->base_input();
        $input['policy'] = banner_geometry::POLICY_THUMBNAIL;
        $input['layer'] = [
            'x' => 100, 'y' => 100, 'width' => 100, 'height' => 50, 'thumbnailadaptable' => true,
        ];
        $input['border'] = ['thicknesspercent' => 30];
        $actual = banner_geometry::project($input);
        $this->assertSame(1.16, $actual['adaptations']['thumbnaillayerscale']);
        $this->assertSame(116.0, $actual['layer']['width']);
        $this->assertSame(11.0, $actual['border']['projectedpercent']);
        $this->assertTrue($actual['adaptations']['thumbnailborderreduction']);
    }

    public function test_projects_contain_cover_asymmetric_crop_and_focal_point(): void {
        $input = $this->base_input();
        $input['layer'] = ['x' => 0, 'y' => 0, 'width' => 400, 'height' => 100, 'fit' => 'contain'];
        $input['image'] = [
            'width' => 800, 'height' => 400, 'focalx' => 0.2, 'focaly' => 0.8, 'transparent' => true,
        ];
        $input['layer']['crop'] = ['x' => 10, 'y' => 20, 'width' => 50, 'height' => 50];
        $contain = banner_geometry::project($input);
        $this->assertSame(200.0, $contain['image']['width']);
        $this->assertSame(100.0, $contain['image']['height']);
        $this->assertSame(40.0, $contain['image']['x']);
        $this->assertSame(0.0, $contain['image']['y']);
        $this->assertTrue($contain['image']['transparent']);

        $input['layer']['fit'] = 'cover';
        $cover = banner_geometry::project($input);
        $this->assertSame(400.0, $cover['image']['width']);
        $this->assertSame(200.0, $cover['image']['height']);
        $this->assertSame(-80.0, $cover['image']['y']);
        $this->assertSame(10.0, $cover['crop']['x']);
    }

    public function test_title_projection_accounts_for_border_and_frame_padding(): void {
        $input = $this->base_input();
        $input['border'] = ['thicknesspercent' => 10];
        $input['title'] = [
            'x' => 800, 'y' => 200, 'align' => 'center', 'fontsize' => 100,
            'lineheightpercent' => 120, 'horizontalpadding' => 20, 'verticalpadding' => 10,
            'aboveborder' => false, 'text' => "First line\nSecond line",
        ];
        $inside = banner_geometry::project($input);
        $this->assertSame(40.0, $inside['border']['thickness']);
        $this->assertSame(100.0, $inside['title']['fontSize']);
        $this->assertSame(120.0, $inside['title']['lineHeightPercent']);
        $this->assertSame(1480.0, $inside['title']['maximumContentWidth']);
        $this->assertSame(1520.0, $inside['title']['maximumFrameWidth']);
        $this->assertSame(2, $inside['title']['lineCount']);
        $this->assertSame(260.0, $inside['title']['minimumFrameHeight']);

        $input['title']['aboveborder'] = true;
        $above = banner_geometry::project($input);
        $this->assertSame(1560.0, $above['title']['maximumContentWidth']);
    }

    public function test_crop_normalisation_matches_existing_persistence_rules(): void {
        $input = $this->base_input();
        $input['layer']['crop'] = ['x' => -5, 'y' => 95, 'width' => 150, 'height' => 3];
        $actual = banner_geometry::project($input);
        $this->assertSame(['enabled' => true, 'x' => 0.0, 'y' => 95.0, 'width' => 100.0, 'height' => 3.0], $actual['crop']);
    }

    public function test_repeated_execution_is_deterministic_and_invalid_input_is_rejected(): void {
        $input = $this->base_input();
        $input['target'] = ['width' => 976.25, 'height' => 160.04];
        $input['layer']['x'] = 123.456789;
        $this->assertSame(banner_geometry::project($input), banner_geometry::project($input));

        $input['target']['width'] = 0;
        $this->expectException(InvalidArgumentException::class);
        banner_geometry::project($input);
    }
}
