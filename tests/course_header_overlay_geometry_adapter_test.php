<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

use local_course_banner_builder\course_header_overlay_geometry_adapter;

/**
 * Covers legacy-to-canonical public course-header overlay adaptation.
 */
final class local_course_banner_builder_course_header_overlay_geometry_adapter_test extends advanced_testcase {
    /** @return array */
    protected function base_record(): array {
        return [
            'positionanchor' => 'center',
            'offsettoppercent' => 0,
            'offsetrightpercent' => 0,
            'offsetbottompercent' => 0,
            'offsetleftpercent' => 0,
            'customwidthpercent' => 100,
            'customheightpercent' => 100,
            'customsizekeepaspect' => 1,
            'imagecenterfixed' => 0,
            'imagecropenabled' => 0,
            'imageopacity' => 1,
            'geometryzindex' => 0,
        ];
    }

    /**
     * Every public format preserves the same normalised custom-layer position.
     *
     * @dataProvider format_provider
     */
    public function test_projects_supported_formats(string $format, float $expectedheight): void {
        $record = $this->base_record();
        $record = array_merge($record, [
            'positionanchor' => 'top-right',
            'offsettoppercent' => 20,
            'offsetrightpercent' => 10,
            'customwidthpercent' => 50,
            'geometryzindex' => 2500,
        ]);
        $actual = course_header_overlay_geometry_adapter::build_styles(
            $record,
            ['width' => 400, 'height' => 200],
            'custom',
            $format
        );
        $this->assertStringContainsString('left: 40%;', $actual['wrapperstyle']);
        $this->assertStringContainsString('top: 20%;', $actual['wrapperstyle']);
        $this->assertStringContainsString('width: 50%;', $actual['wrapperstyle']);
        $this->assertStringContainsString('height: ' . $expectedheight . '%;', $actual['wrapperstyle']);
        $this->assertSame('public_banner', $actual['geometry']['contract']['policy']);
        $this->assertSame(0.4, $actual['geometry']['normalized']['layerx']);
        $this->assertSame(2500, $actual['zindex']);
    }

    /** @return array */
    public static function format_provider(): array {
        return [
            'standard' => ['standard', 100.0],
            'content wide' => ['contentwide', 125.0],
            'full width top' => ['fullwidthtop', 125.0],
            'compact' => ['fullwidthtopcompact', 200.0],
            'inset' => ['fullwidthtopinset', 152.5],
        ];
    }

    public function test_accepts_legacy_strings_and_normalises_out_of_range_offsets(): void {
        $record = $this->base_record();
        $record = array_merge($record, [
            'positionanchor' => 'top-left',
            'offsettoppercent' => '20.25',
            'offsetleftpercent' => '-2000',
            'customwidthpercent' => '25.5',
            'customheightpercent' => '10.25',
            'customsizekeepaspect' => '0',
            'imageopacity' => '0.45',
        ]);
        $actual = course_header_overlay_geometry_adapter::build_styles(
            $record,
            ['width' => 400, 'height' => 200],
            'custom',
            'standard'
        );
        $this->assertStringContainsString('left: -1000%;', $actual['wrapperstyle']);
        $this->assertStringContainsString('top: 20.25%;', $actual['wrapperstyle']);
        $this->assertStringContainsString('width: 25.5%;', $actual['wrapperstyle']);
        $this->assertStringContainsString('height: 10.25%;', $actual['wrapperstyle']);
        $this->assertContains('opacity: 0.45;', $actual['imagestyles']);
    }

    public function test_handles_missing_image_dimensions_and_legacy_fit_fallbacks(): void {
        $record = $this->base_record();
        $missing = course_header_overlay_geometry_adapter::build_styles($record, [], 'cover', 'standard');
        $this->assertStringContainsString('left: 0%;', $missing['wrapperstyle']);
        $this->assertStringContainsString('width: 100%;', $missing['wrapperstyle']);
        $this->assertContains('object-fit: contain;', $missing['imagestyles']);

        $original = course_header_overlay_geometry_adapter::build_styles(
            $record,
            ['width' => 320, 'height' => 160],
            'unexpected-legacy-fit',
            'unexpected-format'
        );
        $this->assertSame('standard', $original['geometry']['contract']['format']);
        $this->assertStringContainsString('width: 20%;', $original['wrapperstyle']);
        $this->assertContains('object-fit: none;', $original['imagestyles']);
    }

    public function test_preserves_fixed_anchor_geometry_and_asymmetric_crop(): void {
        $record = $this->base_record();
        $record = array_merge($record, [
            'positionanchor' => 'bottom-right',
            'offsetrightpercent' => 10,
            'offsetbottompercent' => 20,
            'customwidthpercent' => 50,
            'customheightpercent' => 25,
            'customsizekeepaspect' => 0,
            'imagecenterfixed' => 1,
            'imagecropenabled' => 1,
            'imagecropleftpercent' => 10,
            'imagecroptoppercent' => 20,
            'imagecropwidthpercent' => 50,
            'imagecropheightpercent' => 40,
        ]);
        $actual = course_header_overlay_geometry_adapter::build_styles(
            $record,
            ['width' => 800, 'height' => 400],
            'custom',
            'standard'
        );
        $this->assertStringContainsString('left: 40%;', $actual['wrapperstyle']);
        $this->assertStringContainsString('top: 55%;', $actual['wrapperstyle']);
        $this->assertSame(['enabled' => true, 'x' => 10.0, 'y' => 20.0, 'width' => 50.0, 'height' => 40.0], $actual['geometry']['crop']);
        $this->assertTrue($actual['geometry']['layer']['fixedposition']);
    }

    public function test_is_deterministic_for_complete_public_overlay_input(): void {
        $record = $this->base_record();
        $record['positionanchor'] = 'left';
        $record['offsetleftpercent'] = 12.5;
        $first = course_header_overlay_geometry_adapter::build_styles(
            $record,
            ['width' => 1280, 'height' => 720],
            'cover',
            'fullwidthtopinset'
        );
        $second = course_header_overlay_geometry_adapter::build_styles(
            $record,
            ['width' => 1280, 'height' => 720],
            'cover',
            'fullwidthtopinset'
        );
        $this->assertSame($first, $second);
    }
}
