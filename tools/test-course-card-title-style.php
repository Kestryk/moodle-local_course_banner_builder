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
 * Isolated course-card typography transport contract.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('MOODLE_INTERNAL', true);
defined('MOODLE_INTERNAL') || die();

/**
 * Read the isolated configuration double.
 *
 * @param string $component Component name.
 * @param string $name Configuration key.
 * @return mixed
 */
function get_config($component, $name) {
    global $fixtureconfig;
    return $fixtureconfig[$name] ?? false;
}

require_once(__DIR__ . '/../classes/manager.php');

foreach ([false, true] as $bold) {
    $fixtureconfig = [
        'bannertitle_course_enabled' => 1,
        'bannertitle_course_fontfamily' => '"Trebuchet MS", Helvetica, sans-serif',
        'bannertitle_course_color' => '#123456',
        'bannertitle_course_bold' => $bold,
        'bannertitle_course_allcaps' => $bold,
    ];
    $options = \local_course_banner_builder\manager::get_course_card_title_options();
    $expectedrules = [
        'font-family: "Trebuchet MS", Helvetica, sans-serif;',
        'color: #123456;',
        'font-weight: ' . ($bold ? '800' : '500') . ';',
        'text-transform: ' . ($bold ? 'uppercase' : 'none') . ';',
    ];
    foreach ($expectedrules as $expected) {
        if (strpos($options['style'], $expected) === false) {
            throw new \RuntimeException('Missing exact CSS token: ' . $expected);
        }
    }
    $fixtureconfig['bannertitle_course_fontfamily'] = 'invalid; color:red';
    $options = \local_course_banner_builder\manager::get_course_card_title_options();
    if (strpos($options['style'], 'font-family: inherit;') === false || strpos($options['style'], 'color:red') !== false) {
        throw new \RuntimeException('Invalid font family escaped the allowlist');
    }
}
$fixtureconfig['bannertitle_course_enabled'] = false;
if (\local_course_banner_builder\manager::get_course_card_title_options() !== ['enabled' => false]) {
    throw new \RuntimeException('Disabled title options changed');
}
echo "PASS quoted CSS families, configured typography, allowlist and disabled titles\n";
