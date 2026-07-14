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

/**
 * Crop helper regression tests for Course Banner Builder.
 *
 * @package   local_course_banner_builder
 * @copyright 2026 Kevin J.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/filelib.php');

/**
 * Covers crop normalisation and effective dimension helpers.
 */
final class local_course_banner_builder_manager_crop_test extends advanced_testcase {
    /**
     * Invoke a protected manager helper.
     *
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    protected function invoke_manager_helper(string $method, ...$args) {
        $reflection = new ReflectionMethod(\local_course_banner_builder\manager::class, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs(null, $args);
    }

    /**
     * Create a crop-ready layer record.
     *
     * @param array $overrides
     * @return stdClass
     */
    protected function make_crop_record(array $overrides = []): stdClass {
        $record = (object) [
            'imagecropenabled' => 1,
            'imagecropleftpercent' => 10.0,
            'imagecroptoppercent' => 20.0,
            'imagecropwidthpercent' => 60.0,
            'imagecropheightpercent' => 50.0,
        ];
        foreach ($overrides as $key => $value) {
            $record->{$key} = $value;
        }
        return $record;
    }

    /**
     * Normalisation should clamp values and disable the full-frame crop state.
     *
     * @covers ::normalise_image_crop
     */
    public function test_normalise_image_crop_clamps_and_disables_full_frame(): void {
        $record = $this->make_crop_record([
            'imagecropleftpercent' => -5,
            'imagecroptoppercent' => 95,
            'imagecropwidthpercent' => 150,
            'imagecropheightpercent' => 3,
        ]);

        $crop = $this->invoke_manager_helper('normalise_image_crop', $record);

        $this->assertSame(0.0, $crop['left']);
        $this->assertSame(95.0, $crop['top']);
        $this->assertSame(100.0, $crop['width']);
        $this->assertSame(5.0, $crop['height']);
        $this->assertTrue($crop['enabled']);

        $fullframe = $this->make_crop_record([
            'imagecropleftpercent' => 0,
            'imagecroptoppercent' => 0,
            'imagecropwidthpercent' => 100,
            'imagecropheightpercent' => 100,
        ]);

        $fullframecrop = $this->invoke_manager_helper('normalise_image_crop', $fullframe);
        $this->assertFalse($fullframecrop['enabled']);
    }

    /**
     * Effective dimensions should reflect the visible cropped area.
     *
     * @covers ::get_effective_image_dimensions_for_crop
     */
    public function test_get_effective_image_dimensions_for_crop_uses_visible_crop(): void {
        $record = $this->make_crop_record([
            'imagecropwidthpercent' => 25,
            'imagecropheightpercent' => 40,
        ]);

        [$width, $height] = $this->invoke_manager_helper(
            'get_effective_image_dimensions_for_crop',
            $record,
            800,
            500
        );

        $this->assertSame(200.0, $width);
        $this->assertSame(200.0, $height);
    }

    /**
     * Raster crop helper should generate an image matching the effective crop size.
     *
     * @covers ::crop_layer_image
     */
    public function test_crop_layer_image_returns_expected_dimensions(): void {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $record = $this->make_crop_record([
            'imagecropleftpercent' => 25,
            'imagecroptoppercent' => 10,
            'imagecropwidthpercent' => 50,
            'imagecropheightpercent' => 40,
        ]);

        $image = imagecreatetruecolor(400, 300);
        $cropped = $this->invoke_manager_helper('crop_layer_image', $image, $record);

        $this->assertInstanceOf(GdImage::class, $cropped);
        $this->assertSame(200, imagesx($cropped));
        $this->assertSame(120, imagesy($cropped));

        imagedestroy($image);
        imagedestroy($cropped);
    }
}
