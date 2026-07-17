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
 * Crop-focused tests for Course Banner Builder manager helpers.
 *
 * @package    local_course_banner_builder
 * @category   test
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/course_banner_builder/classes/manager.php');

/**
 * Crop regression tests for local_course_banner_builder manager helpers.
 */
final class local_course_banner_builder_manager_crop_test extends advanced_testcase {
    /**
     * Invoke one protected static manager helper.
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
     * Build a minimal crop-capable layer record.
     *
     * @param array $overrides
     * @return stdClass
     */
    protected function make_crop_record(array $overrides = []): stdClass {
        $record = (object)[
            'imagecropenabled' => 0,
            'imagecropleftpercent' => 0.0,
            'imagecroptoppercent' => 0.0,
            'imagecropwidthpercent' => 100.0,
            'imagecropheightpercent' => 100.0,
        ];
        foreach ($overrides as $key => $value) {
            $record->{$key} = $value;
        }
        return $record;
    }

    /**
     * The normaliser must clamp values and disable a full-image crop.
     *
     * @return void
     */
    public function test_normalise_image_crop_clamps_and_disables_full_frame(): void {
        $crop = $this->invoke_manager_helper(
            'normalise_image_crop',
            $this->make_crop_record([
                'imagecropenabled' => 1,
                'imagecropleftpercent' => 90.0,
                'imagecroptoppercent' => -5.0,
                'imagecropwidthpercent' => 30.0,
                'imagecropheightpercent' => 150.0,
            ])
        );

        $this->assertTrue($crop['enabled']);
        $this->assertSame(70.0, $crop['left']);
        $this->assertSame(0.0, $crop['top']);
        $this->assertSame(30.0, $crop['width']);
        $this->assertSame(100.0, $crop['height']);

        $fullframe = $this->invoke_manager_helper(
            'normalise_image_crop',
            $this->make_crop_record([
                'imagecropenabled' => 1,
                'imagecropleftpercent' => 0.0,
                'imagecroptoppercent' => 0.0,
                'imagecropwidthpercent' => 100.0,
                'imagecropheightpercent' => 100.0,
            ])
        );

        $this->assertFalse($fullframe['enabled']);
        $this->assertSame(100.0, $fullframe['width']);
        $this->assertSame(100.0, $fullframe['height']);
    }

    /**
     * Effective dimensions must follow the visible cropped portion.
     *
     * @return void
     */
    public function test_get_effective_image_dimensions_for_crop_uses_visible_crop(): void {
        $dimensions = $this->invoke_manager_helper(
            'get_effective_image_dimensions_for_crop',
            $this->make_crop_record([
                'imagecropenabled' => 1,
                'imagecropleftpercent' => 10.0,
                'imagecroptoppercent' => 20.0,
                'imagecropwidthpercent' => 65.0,
                'imagecropheightpercent' => 40.0,
            ]),
            500,
            300
        );

        $this->assertSame(['width' => 325, 'height' => 120], $dimensions);

        $uncropped = $this->invoke_manager_helper(
            'get_effective_image_dimensions_for_crop',
            $this->make_crop_record(),
            500,
            300
        );

        $this->assertSame(['width' => 500, 'height' => 300], $uncropped);
    }

    /**
     * The GD crop helper must return a layer matching the stored visible crop.
     *
     * @return void
     */
    public function test_crop_layer_image_returns_expected_dimensions(): void {
        if (!function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is required.');
        }

        $layer = imagecreatetruecolor(100, 80);
        imagealphablending($layer, false);
        imagesavealpha($layer, true);
        imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));

        $cropped = $this->invoke_manager_helper(
            'crop_layer_image',
            $layer,
            $this->make_crop_record([
                'imagecropenabled' => 1,
                'imagecropleftpercent' => 10.0,
                'imagecroptoppercent' => 25.0,
                'imagecropwidthpercent' => 50.0,
                'imagecropheightpercent' => 50.0,
            ]),
            100,
            80
        );

        $this->assertNotNull($cropped);
        $this->assertSame(50, imagesx($cropped));
        $this->assertSame(40, imagesy($cropped));

        imagedestroy($cropped);
        imagedestroy($layer);
    }
}
