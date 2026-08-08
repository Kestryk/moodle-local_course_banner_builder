<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Normalized Course Banner Builder Navigation context.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_banner_builder\output;

/**
 * Prepares the product-owned Navigation destinations for the shared template.
 */
final class navigation {
    /** @var array<string, array{label: string, url: string, icon: string}> */
    private const DESTINATIONS = [
        'course' => [
            'label' => 'managecoursebannersquick',
            'url' => '/local/course_banner_builder/admin_manage.php',
            'icon' => 'fa fa-image',
        ],
        'site' => [
            'label' => 'managesitebannerquick',
            'url' => '/local/course_banner_builder/admin_site.php',
            'icon' => 'fa fa-desktop',
        ],
        'slideshow' => [
            'label' => 'manageslideshowquick',
            'url' => '/local/course_banner_builder/admin_slideshow.php',
            'icon' => 'fa fa-images',
        ],
        'transfer' => [
            'label' => 'transferconfig',
            'url' => '/local/course_banner_builder/admin_transfer.php',
            'icon' => 'fa fa-right-left',
        ],
    ];

    /**
     * Creates the rendering context for one CCB administration route.
     *
     * The optional HTML is rendered by Moodle before it reaches the triple
     * Mustache slot. It must stay product-owned so the Navigation bridge only
     * projects its launcher and never copies the Guide content.
     *
     * @param string $current Current CCB destination key.
     * @param string $guidehtml Product-owned rendered Guide root, if available.
     * @return array<string, mixed>
     */
    public static function context(string $current, string $guidehtml = ''): array {
        if (!array_key_exists($current, self::DESTINATIONS)) {
            throw new \coding_exception('Unknown Course Banner Builder navigation destination.');
        }

        $items = [];
        foreach (self::DESTINATIONS as $id => $destination) {
            $label = get_string($destination['label'], 'local_course_banner_builder');
            $items[] = [
                'id' => $id,
                'kind' => 'destination',
                'label' => $label,
                'accessiblelabel' => $label,
                'url' => (new \moodle_url($destination['url']))->out(false),
                'icon' => $destination['icon'],
                'islink' => true,
                'current' => $id === $current,
                'disabled' => false,
                'destructive' => false,
                'badge' => '',
                'haschildren' => false,
            ];
        }

        $rootid = 'local-course-banner-builder-navigation-' . $current;
        return [
            'rootid' => $rootid,
            'panelid' => $rootid . '-panel',
            'anchorselector' => '[data-region="drawer-toggle"]',
            'guidetarget' => 'adminNav',
            'navigationlabel' => get_string('navigationlabel', 'local_course_banner_builder'),
            'triggerlabel' => get_string('navigationopenlabel', 'local_course_banner_builder'),
            'closelabel' => get_string('navigationcloselabel', 'local_course_banner_builder'),
            'triggericon' => 'fa fa-bars',
            'closeicon' => 'fa fa-times',
            'emptylabel' => get_string('navigationemptylabel', 'local_course_banner_builder'),
            'hasguide' => $guidehtml !== '',
            'guidehtml' => $guidehtml,
            'hasitems' => true,
            'sections' => [
                [
                    'id' => 'destinations',
                    'label' => get_string('navigationsectionlabel', 'local_course_banner_builder'),
                    'items' => $items,
                ],
            ],
        ];
    }
}
