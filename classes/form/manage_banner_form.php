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

namespace local_course_banner_builder\form;

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.Files.LineLength.TooLong -- Form definitions keep related element attributes together.

require_once($CFG->libdir . '/formslib.php');

/**
 * Category banner management form.
 *
 * @package    local_course_banner_builder
 * @copyright  2026 Kevin Jarniac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_banner_form extends \moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition(): void {
        $mform = $this->_form;
        $selectedcategoryid = (int)($this->_customdata['selectedcategoryid'] ?? 0);
        $filemanageroptions = $this->_customdata['filemanageroptions'] ?? [];
        $elementid = (int)($this->_customdata['elementid'] ?? 0);
        $showmoodlepreview = !empty($this->_customdata['showmoodlepreview']);
        $previewdefinition = $this->_customdata['previewdefinition'] ?? [];
        $borderconflictmessage = (string)($this->_customdata['borderconflictmessage'] ?? get_string('sourcealreadyhasborder', 'local_course_banner_builder'));
        $borderconflictmessageinline = (string)($this->_customdata['borderconflictmessageinline'] ?? get_string('sourcealreadyhasborderinline', 'local_course_banner_builder'));
        $overlayconflictmessage = (string)($this->_customdata['overlayconflictmessage'] ?? get_string('sourcealreadyhasoverlay', 'local_course_banner_builder'));
        $overlayconflictmessageinline = (string)($this->_customdata['overlayconflictmessageinline'] ?? get_string('sourcealreadyhasoverlayinline', 'local_course_banner_builder'));
        $sourcehasborderlayer = !empty($this->_customdata['sourcehasborderlayer']);
        $sourcehasoverlaylayer = !empty($this->_customdata['sourcehasoverlaylayer']);
        $currentisborderlayer = !empty($this->_customdata['currentisborderlayer']);
        $currentisoverlaylayer = !empty($this->_customdata['currentisoverlaylayer']);
        $activechildborderlayers = (int)($this->_customdata['activechildborderlayers'] ?? 0);
        $activechildoverlaylayers = (int)($this->_customdata['activechildoverlaylayers'] ?? 0);
        $formmode = (string)($this->_customdata['formmode'] ?? 'create');
        $showfilemanager = !in_array($formmode, ['editborder', 'editimage', 'editoverlay'], true);
        $showadvanced = !in_array($formmode, ['editborder', 'editoverlay'], true);
        $showborder = !in_array($formmode, ['editimage', 'editoverlay'], true);
        $showoverlay = !in_array($formmode, ['editimage', 'editborder'], true);
        $iscreatewithborderconflict = $sourcehasborderlayer && !$currentisborderlayer;
        $iscreatewithoverlayconflict = $sourcehasoverlaylayer && !$currentisoverlaylayer;

        $mform->addElement('hidden', 'categoryid', $selectedcategoryid, ['id' => 'id_categoryid']);
        $mform->setType('categoryid', PARAM_INT);
        $mform->setDefault('categoryid', $selectedcategoryid);

        $mform->addElement('hidden', 'sourcekey', (string)($this->_customdata['sourcekey'] ?? ''), ['id' => 'id_sourcekey']);
        $mform->setType('sourcekey', PARAM_RAW_TRIMMED);

        $mform->addElement('hidden', 'elementid', $elementid, ['id' => 'id_elementid']);
        $mform->setType('elementid', PARAM_INT);

        $mform->addElement('hidden', 'multilayerdraftsettings', '', ['id' => 'id_multilayerdraftsettings']);
        $mform->setType('multilayerdraftsettings', PARAM_RAW);

        $mform->addElement('hidden', 'hasexistingimage', 0, ['id' => 'id_hasexistingimage']);
        $mform->setType('hasexistingimage', PARAM_INT);

        $mform->addElement('hidden', 'sourcehasborderlayer', $sourcehasborderlayer ? 1 : 0, ['id' => 'id_sourcehasborderlayer']);
        $mform->setType('sourcehasborderlayer', PARAM_INT);
        $mform->addElement('hidden', 'sourcehasoverlaylayer', $sourcehasoverlaylayer ? 1 : 0, ['id' => 'id_sourcehasoverlaylayer']);
        $mform->setType('sourcehasoverlaylayer', PARAM_INT);

        $mform->addElement('hidden', 'activechildborderlayers', $activechildborderlayers, [
            'id' => 'id_activechildborderlayers',
        ]);
        $mform->setType('activechildborderlayers', PARAM_INT);
        $mform->addElement('hidden', 'activechildoverlaylayers', $activechildoverlaylayers, [
            'id' => 'id_activechildoverlaylayers',
        ]);
        $mform->setType('activechildoverlaylayers', PARAM_INT);

        $mform->addElement('hidden', 'currentisborderlayer', 0, ['id' => 'id_currentisborderlayer']);
        $mform->setType('currentisborderlayer', PARAM_INT);
        $mform->addElement('hidden', 'currentisoverlaylayer', 0, ['id' => 'id_currentisoverlaylayer']);
        $mform->setType('currentisoverlaylayer', PARAM_INT);

        $mform->addElement('hidden', 'bordersidesvalue', '', ['id' => 'id_bordersidesvalue']);
        $mform->setType('bordersidesvalue', PARAM_RAW_TRIMMED);
        $mform->setDefault('bordersidesvalue', 'top,right,bottom,left');

        $mform->addElement('text', 'name', get_string('layername', 'local_course_banner_builder'));
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_course_banner_builder'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);
        $mform->updateElementAttr('sortorder', [
            'data-upgrade-number' => '1',
            'data-number-min' => '0',
            'data-number-step' => '1',
        ]);

        if (!$elementid) {
            $mform->addElement('static', 'bulkuploadnote', '', get_string('bulkuploadnote', 'local_course_banner_builder'));
        }

        $mform->addElement('advcheckbox', 'isenabled', get_string('enabled', 'local_course_banner_builder'));
        $mform->setDefault('isenabled', 0);

        if ($formmode === 'create' && $showfilemanager && $showborder) {
            $mform->addElement(
                'static',
                'layertypechoice',
                '',
                self::render_layer_type_choice(
                    $iscreatewithborderconflict,
                    $borderconflictmessageinline,
                    $iscreatewithoverlayconflict,
                    $overlayconflictmessageinline
                )
            );
        }

        if ($showfilemanager) {
            $mform->addElement(
                'filemanager',
                'bannerimage_filemanager',
                get_string('bannerimage', 'local_course_banner_builder'),
                ['data-banner-filemanager' => '1'],
                $filemanageroptions
            );
            $mform->addHelpButton('bannerimage_filemanager', 'bannerimage', 'local_course_banner_builder');
            $mform->addElement('static', 'bannerimage_disabled_notice', '', \html_writer::div(
                get_string('bannerimagedisabledforborder', 'local_course_banner_builder'),
                'alert alert-light border d-none mb-0 local-course-banner-builder-filemanager-disabled-note',
                ['data-border-filemanager-note' => '1']
            ));
        } else {
            $mform->addElement('hidden', 'bannerimage_filemanager', 0);
            $mform->setType('bannerimage_filemanager', PARAM_INT);
        }

        $this->add_advanced_options(
            $mform,
            $iscreatewithborderconflict,
            $currentisborderlayer,
            $showadvanced,
            $showborder,
            $showoverlay,
            $showmoodlepreview,
            is_array($previewdefinition) ? $previewdefinition : [],
            $formmode,
            $borderconflictmessage,
            $borderconflictmessageinline,
            $overlayconflictmessage,
            $overlayconflictmessageinline,
            $iscreatewithoverlayconflict,
            $currentisoverlaylayer
        );
        $mform->addElement('static', 'layervalidationwarning', '', \html_writer::div(
            get_string('layercontentrequired', 'local_course_banner_builder'),
            'alert alert-warning d-none',
            ['data-layer-validation-warning' => '1']
        ));

        $this->add_action_buttons(false, get_string('savebannerlayers', 'local_course_banner_builder'));
    }

    /**
     * Add advanced display and border options.
     *
     * @param \MoodleQuickForm $mform
     * @param bool $iscreatewithborderconflict
     * @param bool $currentisborderlayer
     * @param bool $showadvanced
     * @param bool $showborder
     * @param bool $showoverlay
     * @param bool $showmoodlepreview
     * @param array $previewdefinition
     * @param string $borderconflictmessage
     * @param string $borderconflictmessageinline
     * @return void
     */
    protected function add_advanced_options(
        \MoodleQuickForm $mform,
        bool $iscreatewithborderconflict = false,
        bool $currentisborderlayer = false,
        bool $showadvanced = true,
        bool $showborder = true,
        bool $showoverlay = true,
        bool $showmoodlepreview = false,
        array $previewdefinition = [],
        string $formmode = 'create',
        string $borderconflictmessage = '',
        string $borderconflictmessageinline = '',
        string $overlayconflictmessage = '',
        string $overlayconflictmessageinline = '',
        bool $iscreatewithoverlayconflict = false,
        bool $currentisoverlaylayer = false
    ): void {
        $usesharedcreatepreview = $formmode === 'create' && $showadvanced && $showborder;
        $fitoptions = ['' => get_string('fitoverride:categorydefault', 'local_course_banner_builder')]
            + \local_course_banner_builder\manager::get_editable_fit_mode_options(true);

        if ($showadvanced) {
            $imageattrs = [
                'class' => 'local-course-banner-builder-advanced-accordion ' .
                    'local-course-banner-builder-image-options-accordion',
                'data-image-options-section' => '1',
                'open' => 'open',
            ];
            $mform->addElement('html', \html_writer::start_tag('details', $imageattrs));
            $summary = self::render_collapse_expand_icon(false) .
                \html_writer::span(
                    get_string('imagelayeroptions', 'local_course_banner_builder'),
                    'local-course-banner-builder-border-summary-title'
                );
            $mform->addElement('html', \html_writer::tag('summary', $summary));

            $mform->addElement(
                'static',
                'layeradvancedhelp',
                '',
                \html_writer::div(get_string('layeradvanced_help', 'local_course_banner_builder'),
                    'form-text text-muted local-course-banner-builder-advanced-help')
            );
        }

        if ($showadvanced) {
            $customsizemax = (string)(int)\local_course_banner_builder\manager::CUSTOM_SIZE_PERCENT_MAX;
            $mform->addElement('select', 'fitmodeoverride', get_string('fitoverride', 'local_course_banner_builder'), $fitoptions);
            $mform->setType('fitmodeoverride', PARAM_ALPHA);
            $mform->addElement('static', 'fitoverridehelp', '', \html_writer::div(
            get_string('fitoverridehelp', 'local_course_banner_builder'),
            'form-text text-muted'
            ));

            $customsizeattrs = [
            'size' => 6,
            'data-upgrade-number' => '1',
            'data-number-min' => '0',
            'data-number-max' => $customsizemax,
            'data-number-step' => '0.1',
            'data-field-suffix' => '%',
            'data-layer-custom-size' => '1',
            ];
            $mform->addElement('text', 'customwidthpercent', get_string('customwidthpercent', 'local_course_banner_builder'), $customsizeattrs + [
            'data-custom-size-dimension' => 'width',
            'data-percent-slider-input' => '1',
            ]);
            $mform->setType('customwidthpercent', PARAM_FLOAT);
            $mform->setDefault('customwidthpercent', 100);
            $this->add_percent_slider_static($mform, 'customwidthpercent', 0, (float)$customsizemax, 0.1);

            $mform->addElement('text', 'customheightpercent', get_string('customheightpercent', 'local_course_banner_builder'), $customsizeattrs + [
            'data-custom-size-dimension' => 'height',
            'data-percent-slider-input' => '1',
            ]);
            $mform->setType('customheightpercent', PARAM_FLOAT);
            $mform->setDefault('customheightpercent', 100);
            $this->add_percent_slider_static($mform, 'customheightpercent', 0, (float)$customsizemax, 0.1);

            $mform->addElement('advcheckbox', 'customsizekeepaspect', get_string('customsizekeepaspect', 'local_course_banner_builder'), '', [
            'data-custom-size-keep-aspect' => '1',
            ]);
            $mform->setDefault('customsizekeepaspect', 1);

            $mform->addElement(
            'advcheckbox',
            'dynamicimagesizeenabled',
            get_string('dynamicimagesizeenabled', 'local_course_banner_builder')
            );
            $mform->setDefault('dynamicimagesizeenabled', 0);
            $mform->addHelpButton('dynamicimagesizeenabled', 'dynamicimagesizeenabled', 'local_course_banner_builder');

            $mform->addElement(
            'select',
            'positionanchor',
            get_string('positionanchor', 'local_course_banner_builder'),
            \local_course_banner_builder\manager::get_position_anchor_options(),
            ['data-layer-position-anchor' => '1']
            );
            $mform->setType('positionanchor', PARAM_ALPHAEXT);
            $mform->setDefault('positionanchor', \local_course_banner_builder\manager::POSITION_CENTER);
            $mform->addHelpButton('positionanchor', 'positionanchor', 'local_course_banner_builder');

            $offsetattrs = [
            'size' => 6,
            'data-layer-offset-input' => '1',
            'data-upgrade-number' => '1',
            'data-number-min' => '-1000',
            'data-number-max' => '1000',
            'data-number-step' => '0.1',
            'data-field-suffix' => '%',
            'data-percent-slider-input' => '1',
            ];
            $this->add_offset_field($mform, 'offsettoppercent', get_string('layeroffsettoppercent', 'local_course_banner_builder'), 'top', $offsetattrs);
            $this->add_offset_field($mform, 'offsetrightpercent', get_string('layeroffsetrightpercent', 'local_course_banner_builder'), 'right', $offsetattrs);
            $this->add_offset_field($mform, 'offsetbottompercent', get_string('layeroffsetbottompercent', 'local_course_banner_builder'), 'bottom', $offsetattrs);
            $this->add_offset_field($mform, 'offsetleftpercent', get_string('layeroffsetleftpercent', 'local_course_banner_builder'), 'left', $offsetattrs);
            $mform->addElement('static', 'layeroffsethelp', '', \html_writer::div(
            get_string('layeroffsethelp', 'local_course_banner_builder'),
            'form-text text-muted'
            ));

        }
        if ($showadvanced) {
            $mform->addElement('hidden', 'imageopacity', 100, [
                'data-image-opacity-input' => '1',
            ]);
            $mform->setType('imageopacity', PARAM_FLOAT);
            $mform->setDefault('imageopacity', 100);
            $mform->addElement('hidden', 'imagecropenabled', 0, [
                'data-image-crop-enabled-input' => '1',
            ]);
            $mform->setType('imagecropenabled', PARAM_BOOL);
            $mform->setDefault('imagecropenabled', 0);
            foreach (['left', 'top', 'width', 'height'] as $cropfield) {
                $fieldname = 'imagecrop' . $cropfield . 'percent';
                $defaultvalue = in_array($cropfield, ['width', 'height'], true) ? 100 : 0;
                $mform->addElement('hidden', $fieldname, $defaultvalue, [
                    'data-image-crop-input' => $cropfield,
                ]);
                $mform->setType($fieldname, PARAM_FLOAT);
                $mform->setDefault($fieldname, $defaultvalue);
            }
            $mform->addElement('html', '</details>');
        }
        if ($showadvanced && !$usesharedcreatepreview) {
            $mform->addElement('static', 'imagepreview', '', $this->render_banner_preview_panel(
                'imagepreview',
                $previewdefinition,
                $showmoodlepreview,
                true,
                false,
                get_string('imagepreviewhelp', 'local_course_banner_builder')
            ));
        }
        if (!$showborder && !$showoverlay) {
            return;
        }

        if ($showoverlay) {
            $overlaydetailsattrs = [
                'class' => 'local-course-banner-builder-advanced-accordion local-course-banner-builder-overlay-accordion',
                'data-overlay-section' => '1',
                'data-overlay-accordion' => '1',
                'data-create-overlay-locked' => $iscreatewithoverlayconflict ? '1' : '0',
            ];
            if ($iscreatewithoverlayconflict) {
                $overlaydetailsattrs['class'] .= ' local-course-banner-builder-disabled';
            }
            if ($currentisoverlaylayer) {
                $overlaydetailsattrs['open'] = 'open';
            }
            $mform->addElement('html', \html_writer::start_tag('details', $overlaydetailsattrs));
            $summarycontent = self::render_collapse_expand_icon(!$currentisoverlaylayer) .
                \html_writer::span(
                    get_string('layeroverlay', 'local_course_banner_builder'),
                    'local-course-banner-builder-border-summary-title'
                );
            $summarycontent .= \html_writer::span(
                $overlayconflictmessageinline,
                'local-course-banner-builder-border-summary-note text-danger ms-2' .
                    ($iscreatewithoverlayconflict ? '' : ' d-none'),
                ['data-overlay-existing-note-inline' => '1']
            );
            $mform->addElement('html', \html_writer::tag('summary', $summarycontent));
            if ($formmode === 'editoverlay') {
                $mform->addElement('hidden', 'overlayenabled', 1, [
                    'id' => 'id_overlayenabled',
                    'data-overlay-toggle' => '1',
                ]);
                $mform->setType('overlayenabled', PARAM_BOOL);
            } else {
                $mform->addElement('advcheckbox', 'overlayenabled', get_string('overlayenabled', 'local_course_banner_builder'), '', [
                    'data-overlay-toggle' => '1',
                ]);
                $mform->setDefault('overlayenabled', $currentisoverlaylayer ? 1 : 0);
                if ($iscreatewithoverlayconflict || $currentisoverlaylayer) {
                    $mform->updateElementAttr('overlayenabled', [
                        'disabled' => 'disabled',
                        'aria-disabled' => 'true',
                    ]);
                }
            }
            $mform->addElement('static', 'overlayenabled_existing_notice', '', \html_writer::div(
                $overlayconflictmessage,
                'local-course-banner-builder-border-existing-inline text-danger d-none',
                ['data-overlay-existing-note' => '1']
            ));
            $mform->addElement(
                'select',
                'overlaytarget',
                get_string('overlaytarget', 'local_course_banner_builder'),
                \local_course_banner_builder\manager::get_overlay_target_options()
            );
            $mform->setType('overlaytarget', PARAM_ALPHA);
            $mform->setDefault('overlaytarget', \local_course_banner_builder\manager::OVERLAY_TARGET_BOTH);

            foreach ([
                'overlaybanner' => get_string('overlaybannerappearance', 'local_course_banner_builder'),
                'overlayslideshow' => get_string('overlayslideshowappearance', 'local_course_banner_builder'),
            ] as $prefix => $label) {
                $mform->addElement('static', $prefix . 'heading', '', \html_writer::div(
                    $label,
                    'local-course-banner-builder-slideshow-side-title mt-2'
                ));
                $colorgroup = [];
                $colorgroup[] = $mform->createElement('text', $prefix . 'color', '', [
                    'data-overlay-color-text' => $prefix,
                ]);
                $colorgroup[] = $mform->createElement('html', \html_writer::empty_tag('input', [
                    'type' => 'color',
                    'id' => 'id_' . $prefix . 'color_picker',
                    'class' => 'form-control form-control-color local-course-banner-builder-color-picker',
                    'value' => '#000000',
                    'data-overlay-color-picker' => $prefix,
                    'aria-label' => get_string('overlaycolor', 'local_course_banner_builder'),
                ]));
                $mform->addGroup($colorgroup, $prefix . 'colorgroup', get_string('overlaycolor', 'local_course_banner_builder'), '', false);
                $mform->setType($prefix . 'color', PARAM_RAW_TRIMMED);
                $mform->setDefault($prefix . 'color', '#000000');
                $mform->addElement('text', $prefix . 'opacity', get_string('overlayopacity', 'local_course_banner_builder'), [
                    'size' => 6,
                    'data-upgrade-number' => '1',
                    'data-number-min' => '0',
                    'data-number-max' => '100',
                    'data-number-step' => '1',
                    'data-field-suffix' => '%',
                    'data-percent-slider-input' => '1',
                ]);
                $mform->setType($prefix . 'opacity', PARAM_FLOAT);
                $mform->setDefault($prefix . 'opacity', $prefix === 'overlaybanner' ? 25 : 38);
                $this->add_percent_slider_static($mform, $prefix . 'opacity', 0, 100, 1);
            }
            $mform->addElement('advcheckbox', 'overlaytitleabove', get_string('overlaytitleabove', 'local_course_banner_builder'));
            $mform->setDefault('overlaytitleabove', 1);
            $mform->addElement('advcheckbox', 'overlayborderabove', get_string('overlayborderabove', 'local_course_banner_builder'));
            $mform->setDefault('overlayborderabove', 1);
            $mform->addElement('html', '</details>');
        }

        if (!$showborder) {
            return;
        }

        $borderdetailsattrs = [
            'class' => 'local-course-banner-builder-advanced-accordion local-course-banner-builder-border-accordion',
            'data-border-section' => '1',
            'data-border-accordion' => '1',
            'data-create-border-locked' => $iscreatewithborderconflict ? '1' : '0',
        ];
        if ($iscreatewithborderconflict) {
            $borderdetailsattrs['class'] .= ' local-course-banner-builder-disabled';
        }
        if ($currentisborderlayer) {
            $borderdetailsattrs['open'] = 'open';
        }
        $mform->addElement('html', \html_writer::start_tag('details', $borderdetailsattrs));
        $summarycontent = \html_writer::span(
            get_string('layerborder', 'local_course_banner_builder'),
            'local-course-banner-builder-border-summary-title'
        );
        $summarycontent = self::render_collapse_expand_icon(!$currentisborderlayer) . $summarycontent;
        $summarycontent .= \html_writer::span(
            $borderconflictmessageinline,
            'local-course-banner-builder-border-summary-note text-danger ms-2' . ($iscreatewithborderconflict ? '' : ' d-none'),
            ['data-border-existing-note-inline' => '1']
        );
        $mform->addElement(
            'html',
            \html_writer::tag('summary', $summarycontent, $iscreatewithborderconflict ? [
                'aria-disabled' => 'true',
                'tabindex' => '-1',
            ] : [])
        );

        if ($formmode === 'editborder') {
            $mform->addElement('hidden', 'borderenabled', 1, [
                'id' => 'id_borderenabled',
                'data-border-toggle' => '1',
            ]);
            $mform->setType('borderenabled', PARAM_BOOL);
        } else {
            $mform->addElement('advcheckbox', 'borderenabled', get_string('borderenabled', 'local_course_banner_builder'), '', [
                'data-border-toggle' => '1',
            ]);
            $mform->setDefault('borderenabled', $currentisborderlayer ? 1 : 0);
            if ($iscreatewithborderconflict || $currentisborderlayer) {
                $mform->updateElementAttr('borderenabled', [
                    'disabled' => 'disabled',
                    'aria-disabled' => 'true',
                ]);
            }
        }
        $mform->addElement('static', 'borderenabled_existing_notice', '', \html_writer::div(
            $borderconflictmessage,
            'local-course-banner-builder-border-existing-inline text-danger d-none',
            ['data-border-existing-note' => '1']
        ));

        $bordercolorgroup = [];
        $bordercolorgroup[] = $mform->createElement('text', 'bordercolor', '', [
            'data-border-color-text' => '1',
        ]);
        $bordercolorgroup[] = $mform->createElement('html',
            \html_writer::empty_tag('input', [
                'type' => 'color',
                'id' => 'id_bordercolor_picker',
                'class' => 'form-control form-control-color local-course-banner-builder-color-picker',
                'value' => '#56B9C0',
                'data-border-color-picker' => '1',
                'aria-label' => get_string('bordercolor', 'local_course_banner_builder'),
                'title' => get_string('bordercolor', 'local_course_banner_builder'),
                'disabled' => $iscreatewithborderconflict ? 'disabled' : null,
            ])
        );
        $mform->addGroup(
            $bordercolorgroup,
            'bordercolorgroup',
            get_string('bordercolor', 'local_course_banner_builder'),
            '',
            false
        );
        $mform->setType('bordercolor', PARAM_RAW_TRIMMED);
        $mform->setDefault('bordercolor', '#56B9C0');
        $mform->addHelpButton('bordercolorgroup', 'bordercolor', 'local_course_banner_builder');

        $mform->addElement(
            'select',
            'borderstyle',
            get_string('borderstyle', 'local_course_banner_builder'),
            \local_course_banner_builder\manager::get_border_style_options()
        );
        $mform->setType('borderstyle', PARAM_ALPHA);
        $mform->setDefault('borderstyle', \local_course_banner_builder\manager::BORDER_STYLE_SOLID);

        $mform->addElement('text', 'borderwidth', get_string('borderwidth', 'local_course_banner_builder'), [
            'size' => 6,
            'data-upgrade-number' => '1',
            'data-number-min' => '0',
            'data-number-max' => '100',
            'data-number-step' => '0.1',
            'data-field-suffix' => '%',
            'data-percent-slider-input' => '1',
        ]);
        $mform->setType('borderwidth', PARAM_FLOAT);
        $mform->setDefault('borderwidth', 2.5);
        $this->add_percent_slider_static($mform, 'borderwidth', 0, 100, 0.1);

        $mform->addElement('text', 'borderopacity', get_string('borderopacity', 'local_course_banner_builder'), [
            'size' => 6,
            'data-upgrade-number' => '1',
            'data-number-min' => '0',
            'data-number-max' => '100',
            'data-number-step' => '1',
            'data-field-suffix' => '%',
            'data-percent-slider-input' => '1',
        ]);
        $mform->setType('borderopacity', PARAM_FLOAT);
        $mform->setDefault('borderopacity', 0);
        $this->add_percent_slider_static($mform, 'borderopacity', 0, 100, 1);

        $mform->addElement('text', 'borderfade', get_string('borderfade', 'local_course_banner_builder'), [
            'size' => 6,
            'data-upgrade-number' => '1',
            'data-number-min' => '0',
            'data-number-max' => '100',
            'data-number-step' => '1',
            'data-field-suffix' => '%',
            'data-percent-slider-input' => '1',
        ]);
        $mform->setType('borderfade', PARAM_FLOAT);
        $mform->setDefault('borderfade', 0);
        $this->add_percent_slider_static($mform, 'borderfade', 0, 100, 1);

        $mform->addElement('text', 'borderdashlength', get_string('borderdashlength', 'local_course_banner_builder'), [
            'size' => 6,
            'data-upgrade-number' => '1',
            'data-number-min' => '4',
            'data-number-max' => '80',
            'data-number-step' => '1',
            'data-field-suffix' => 'px',
            'data-percent-slider-input' => '1',
            'data-border-dash-length' => '1',
        ]);
        $mform->setType('borderdashlength', PARAM_FLOAT);
        $mform->setDefault('borderdashlength', 24);
        $this->add_percent_slider_static($mform, 'borderdashlength', 4, 80, 1, [
            'data-range-suffix' => 'px',
            'data-border-dash-length' => '1',
        ]);

        $sidegroup = [];
        $sidegroup[] = $mform->createElement(
            'advcheckbox',
            'bordersides[all]',
            '',
            get_string('bordersides:all', 'local_course_banner_builder'),
            ['data-border-side-all' => '1']
        );
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $sidegroup[] = $mform->createElement(
                'advcheckbox',
                'bordersides[' . $side . ']',
                '',
                get_string('bordersides:' . $side, 'local_course_banner_builder'),
                ['data-border-side' => $side]
            );
        }
        $mform->addGroup($sidegroup, 'bordersidesgroup', get_string('bordersides', 'local_course_banner_builder'), '<br>');
        $mform->setDefault('bordersides[all]', 1);
        $mform->setDefault('bordersides[top]', 1);
        $mform->setDefault('bordersides[right]', 1);
        $mform->setDefault('bordersides[bottom]', 1);
        $mform->setDefault('bordersides[left]', 1);

        $mform->addElement('advcheckbox', 'borderinnerrounded', get_string('borderinnerrounded', 'local_course_banner_builder'), '', [
            'data-border-inner-rounded' => '1',
        ]);
        $mform->setDefault('borderinnerrounded', 0);

        if ($iscreatewithborderconflict) {
            if ($formmode !== 'editborder') {
                $mform->updateElementAttr('borderenabled', [
                    'disabled' => 'disabled',
                    'aria-disabled' => 'true',
                ]);
            }
            $mform->updateElementAttr('bordercolor', ['disabled' => 'disabled']);
            $mform->updateElementAttr('borderwidth', ['disabled' => 'disabled']);
            $mform->updateElementAttr('borderopacity', ['disabled' => 'disabled']);
            $mform->updateElementAttr('borderfade', ['disabled' => 'disabled']);
            $mform->updateElementAttr('borderstyle', ['disabled' => 'disabled']);
            $mform->updateElementAttr('borderdashlength', ['disabled' => 'disabled']);
            $mform->updateElementAttr('borderinnerrounded', [
                'disabled' => 'disabled',
                'aria-disabled' => 'true',
            ]);
        }

        $mform->addElement('html', '</details>');

        if (!$usesharedcreatepreview) {
            $mform->addElement('static', 'borderpreview', '', $this->render_banner_preview_panel(
                'borderpreview',
                $previewdefinition,
                $showmoodlepreview,
                false,
                true,
                get_string('borderpreviewhelp', 'local_course_banner_builder')
            ));
        }

        if ($usesharedcreatepreview) {
            $mform->addElement('static', 'layerpreview', '', $this->render_banner_preview_panel(
                'layerpreview',
                $previewdefinition,
                $showmoodlepreview,
                true,
                true,
                get_string('imagepreviewhelp', 'local_course_banner_builder')
            ));
        }
    }

    /**
     * Add one offset field with anchor-based visibility metadata.
     *
     * @param \MoodleQuickForm $mform
     * @param string $name
     * @param string $label
     * @param string $side
     * @param array $attributes
     * @return void
     */
    protected function add_offset_field(\MoodleQuickForm $mform, string $name, string $label, string $side, array $attributes = []): void {
        $mform->addElement(
            'text',
            $name,
            $label,
            $attributes + ['data-offset-side' => $side]
        );
        $mform->setType($name, PARAM_FLOAT);
        $mform->setDefault($name, 0);
        $this->add_percent_slider_static($mform, $name, -1000, 1000, 0.1, [
            'data-offset-side' => $side,
        ]);
    }

    /**
     * Render a synced percent slider under one numeric field.
     *
     * @param \MoodleQuickForm $mform
     * @param string $targetname
     * @param int|float $min
     * @param int|float $max
     * @param int|float $step
     * @param array $attributes
     * @return void
     */
    protected function add_percent_slider_static(
        \MoodleQuickForm $mform,
        string $targetname,
        $min,
        $max,
        $step,
        array $attributes = []
    ): void {
        $slidername = $targetname . '_slider';
        $sliderid = 'id_' . $slidername;
        $targetid = 'id_' . $targetname;

        $mform->addElement('static', $slidername, '', \html_writer::div(
            \html_writer::empty_tag('input', [
                'type' => 'range',
                'id' => $sliderid,
                'class' => 'local-course-banner-builder-range theme-easyedu-range local-course-banner-builder-linked-range',
                'min' => (string)$min,
                'max' => (string)$max,
                'step' => (string)$step,
                'data-percent-slider-for' => $targetid,
                'data-range-suffix' => (string)($attributes['data-range-suffix'] ?? '%'),
            ] + $attributes) .
            \html_writer::tag('span', '', [
                'class' => 'local-course-banner-builder-range-output theme-easyedu-range-output',
                'data-percent-slider-output-for' => $targetid,
                'aria-live' => 'polite',
            ]),
            'local-course-banner-builder-linked-range-wrapper',
            [
                'data-percent-slider-wrapper-for' => $targetid,
            ]
        ));
    }

    /**
     * Render one banner preview frame.
     *
     * @param string $variant
     * @param string $bound
     * @param array $previewdefinition
     * @param bool $showcurrentimage
     * @param bool $showcurrentborder
     * @return string
     */
    protected function render_border_preview_frame(
        string $variant,
        string $bound,
        array $previewdefinition = [],
        bool $showcurrentimage = false,
        bool $showcurrentborder = false
    ): string {
        $contextlayershtml = '';
        foreach (($previewdefinition['contextlayers'] ?? []) as $layer) {
            if (is_array($layer)) {
                $contextlayershtml .= $this->render_preview_context_layer($layer);
            }
        }

        $currentlayer = is_array($previewdefinition['currentlayer'] ?? null) ? $previewdefinition['currentlayer'] : [];
        $currentborderstyle = trim((string)($currentlayer['wrapperstyle'] ?? ''));
        if ($currentborderstyle !== '' && !str_ends_with($currentborderstyle, ';')) {
            $currentborderstyle .= ';';
        }
        $currentimagelayer = $showcurrentimage ? $this->render_preview_current_image_layer($currentlayer) : '';
        $currentborderlayer = $showcurrentborder ? $this->render_preview_border_overlay([
            'class' => 'local-course-banner-builder-preview-border-layer local-course-banner-builder-preview-border-layer--current',
            'attributes' => [
                'data-preview-current-border' => '1',
                'data-preview-sortorder' => (string)($currentlayer['sortorder'] ?? 0),
                'data-preview-zindex' => (string)($currentlayer['zindex'] ?? (1000 + (int)($currentlayer['sortorder'] ?? 0))),
            ],
            'style' => $currentborderstyle . ' z-index: ' .
                (int)($currentlayer['zindex'] ?? (1000 + (int)($currentlayer['sortorder'] ?? 0))) . ';',
            'sidestyles' => (array)($currentlayer['sidestyles'] ?? []),
            'dynamic' => true,
        ]) : '';

        $bannerformat = \local_course_banner_builder\manager::normalise_banner_format(
            (string)($previewdefinition['bannerformat'] ?? \local_course_banner_builder\manager::BANNER_FORMAT_STANDARD)
        );

        return \html_writer::div(
            \html_writer::div('', 'local-course-banner-builder-banner-preview-base') .
            $contextlayershtml .
            $currentimagelayer .
            $currentborderlayer,
            'local-course-banner-builder-border-preview-frame local-course-banner-builder-border-preview-frame--' . $variant .
                ' local-course-banner-builder-border-preview-frame--format-' . $bannerformat,
            [
                'data-border-preview-frame' => $showcurrentborder ? $bound : '0',
                'data-banner-preview-frame' => '1',
                'data-banner-format' => $bannerformat,
            ]
        );
    }

    /**
     * Render the shared live banner preview panel.
     *
     * @param string $fieldname
     * @param array $previewdefinition
     * @param bool $showmoodlepreview
     * @param bool $showcurrentimage
     * @param bool $showcurrentborder
     * @param string $helptext
     * @return string
     */
    protected function render_banner_preview_panel(
        string $fieldname,
        array $previewdefinition,
        bool $showmoodlepreview,
        bool $showcurrentimage,
        bool $showcurrentborder,
        string $helptext
    ): string {
        global $PAGE;

        $toggleid = 'id_' . $fieldname . '_show_context_layers';
        $activethemename = (string)($PAGE->theme->name ?? 'EasyEdu');
        $activethemename = $activethemename !== '' ? ucfirst($activethemename) : 'EasyEdu';
        $toolbarcontent = !empty($previewdefinition['hascontextlayers']) ? \html_writer::div(
                \html_writer::tag(
                    'label',
                    \html_writer::empty_tag('input', [
                        'type' => 'checkbox',
                        'id' => $toggleid,
                        'data-preview-context-toggle' => '1',
                    ]) . \html_writer::span('', 'local-course-banner-builder-toggle-slider easyedu-toggle-switch__slider'),
                    [
                        'class' => 'local-course-banner-builder-toggle easyedu-toggle-switch easyedu-toggle-switch--32px mb-0',
                    ]
                ) .
                \html_writer::tag('label', get_string('bannerpreviewtogglecontext', 'local_course_banner_builder'), [
                    'class' => 'mb-0',
                    'for' => $toggleid,
                ]),
                'local-course-banner-builder-banner-preview-toolbar-toggle'
            ) : '';

        $toolbar = $toolbarcontent === '' ? '' : \html_writer::div(
            $toolbarcontent,
            'local-course-banner-builder-banner-preview-toolbar'
        );
        $previewvariant = $showmoodlepreview ? 'moodle' : 'easyedu';
        $previewactions = $showcurrentimage ? \html_writer::div(
            \html_writer::tag('button', get_string('recenterpreviewimage', 'local_course_banner_builder'), [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary btn-sm local-course-banner-builder-preview-recenter',
                'data-action' => 'local-course-banner-builder-recenter-preview-image',
            ]),
            'local-course-banner-builder-banner-preview-actions'
        ) : '';

        return \html_writer::div(
            $toolbar .
            \html_writer::div(
                \html_writer::div(
                    $this->render_border_preview_frame(
                        $previewvariant,
                        '1',
                        $previewdefinition,
                        $showcurrentimage,
                        $showcurrentborder
                    ),
                    'local-course-banner-builder-border-preview-variant'
                ),
                'local-course-banner-builder-border-preview',
                [
                    'data-border-preview' => $showcurrentborder ? '1' : '0',
                    'data-layer-banner-preview' => '1',
                    'data-default-fitmode' => (string)($previewdefinition['defaultfitmode'] ?? \local_course_banner_builder\manager::FIT_MODE_BANNER),
                ]
            ) .
            $previewactions .
            \html_writer::div($helptext, 'form-text text-muted'),
            'local-course-banner-builder-banner-preview-panel'
        );
    }

    /**
     * Render one persisted context layer.
     *
     * @param array $layer
     * @return string
     */
    protected function render_preview_context_layer(array $layer): string {
        if (($layer['type'] ?? '') === 'image') {
            return $this->render_preview_image_layer($layer, true, false);
        }

        if (($layer['type'] ?? '') === 'border') {
            $zindex = (int)($layer['zindex'] ?? (1000 + (int)($layer['sortorder'] ?? 0)));
            return $this->render_preview_border_overlay([
                'class' => 'local-course-banner-builder-preview-border-layer local-course-banner-builder-preview-border-layer--context',
                'attributes' => [
                    'data-preview-context-layer' => '1',
                    'data-preview-layer-id' => (string)($layer['id'] ?? 0),
                    'data-preview-inherited' => !empty($layer['isinherited']) ? '1' : '0',
                    'data-preview-sortorder' => (string)($layer['sortorder'] ?? 0),
                    'data-preview-zindex' => (string)$zindex,
                ],
                'style' => trim((string)($layer['wrapperstyle'] ?? '')) . ' z-index: ' . $zindex . ';',
                'sidestyles' => (array)($layer['sidestyles'] ?? []),
                'dynamic' => false,
            ]);
        }

        return '';
    }

    /**
     * Render the editable image layer placeholder.
     *
     * @param array $currentlayer
     * @return string
     */
    protected function render_preview_current_image_layer(array $currentlayer): string {
        $attributes = [
            'data-preview-current-image' => '1',
            'data-preview-fitmode' => (string)($currentlayer['fitmode'] ?? ''),
            'data-preview-anchor' => (string)($currentlayer['positionanchor'] ?? \local_course_banner_builder\manager::POSITION_CENTER),
            'data-preview-custom-width' => (string)($currentlayer['customwidthpercent'] ?? 100),
            'data-preview-custom-height' => (string)($currentlayer['customheightpercent'] ?? 100),
            'data-preview-keep-aspect' => !empty($currentlayer['customsizekeepaspect']) ? '1' : '0',
            'data-preview-dynamic-image' => !empty($currentlayer['dynamicimagesizeenabled']) ? '1' : '0',
            'data-preview-image-opacity' => (string)($currentlayer['imageopacity'] ?? 100),
            'data-preview-crop-enabled' => !empty($currentlayer['imagecropenabled']) ? '1' : '0',
            'data-preview-crop-left' => (string)($currentlayer['imagecropleftpercent'] ?? 0),
            'data-preview-crop-top' => (string)($currentlayer['imagecroptoppercent'] ?? 0),
            'data-preview-crop-width' => (string)($currentlayer['imagecropwidthpercent'] ?? 100),
            'data-preview-crop-height' => (string)($currentlayer['imagecropheightpercent'] ?? 100),
            'data-preview-offset-top' => (string)($currentlayer['offsettoppercent'] ?? 0),
            'data-preview-offset-right' => (string)($currentlayer['offsetrightpercent'] ?? 0),
            'data-preview-offset-bottom' => (string)($currentlayer['offsetbottompercent'] ?? 0),
            'data-preview-offset-left' => (string)($currentlayer['offsetleftpercent'] ?? 0),
            'data-preview-natural-width' => (string)($currentlayer['imagewidth'] ?? 0),
            'data-preview-natural-height' => (string)($currentlayer['imageheight'] ?? 0),
            'data-preview-current-url' => (string)($currentlayer['url'] ?? ''),
            'data-preview-zindex' => (string)($currentlayer['zindex'] ?? ((int)($currentlayer['sortorder'] ?? 0) + 1)),
        ];

        return $this->render_preview_image_layer($currentlayer, false, true, $attributes);
    }

    /**
     * Render one image layer.
     *
     * @param array $layer
     * @param bool $iscontext
     * @param bool $iscurrent
     * @param array $attributes
     * @return string
     */
    protected function render_preview_image_layer(
        array $layer,
        bool $iscontext = true,
        bool $iscurrent = false,
        array $attributes = []
    ): string {
        $classes = 'local-course-banner-builder-preview-image-layer';
        if ($iscontext) {
            $classes .= ' local-course-banner-builder-preview-image-layer--context';
            $attributes['data-preview-context-layer'] = '1';
            $attributes['data-preview-layer-id'] = (string)($layer['id'] ?? 0);
            $attributes['data-preview-inherited'] = !empty($layer['isinherited']) ? '1' : '0';
        }
        if ($iscurrent) {
            $classes .= ' local-course-banner-builder-preview-image-layer--current';
            $attributes['data-preview-layer-id'] = (string)($layer['id'] ?? 0);
        }

        $zindex = (int)($layer['zindex'] ?? ((int)($layer['sortorder'] ?? 0) + 1));
        $style = trim((string)($layer['wrapperstyle'] ?? ''));
        $attributes['style'] = trim($style . ' z-index: ' . $zindex . ';');
        $attributes['data-preview-sortorder'] = (string)($layer['sortorder'] ?? 0);
        $attributes['data-preview-zindex'] = (string)$zindex;
        if (!isset($attributes['data-preview-image-opacity'])) {
            $attributes['data-preview-image-opacity'] = (string)($layer['imageopacity'] ?? 100);
        }
        $attributes['data-preview-crop-enabled'] = !empty($layer['imagecropenabled']) ? '1' : '0';
        $attributes['data-preview-crop-left'] = (string)($layer['imagecropleftpercent'] ?? 0);
        $attributes['data-preview-crop-top'] = (string)($layer['imagecroptoppercent'] ?? 0);
        $attributes['data-preview-crop-width'] = (string)($layer['imagecropwidthpercent'] ?? 100);
        $attributes['data-preview-crop-height'] = (string)($layer['imagecropheightpercent'] ?? 100);

        if ($iscurrent && empty($layer['url'])) {
            $attributes['hidden'] = 'hidden';
        }

        $content = \html_writer::empty_tag('img', [
                'src' => (string)($layer['url'] ?? ''),
                'alt' => '',
                'class' => 'local-course-banner-builder-preview-image',
                'style' => (string)($layer['imagestyle'] ?? ''),
                'data-preview-image-tag' => '1',
                'draggable' => 'false',
            ]);

        if ($iscurrent) {
            $aspectlocklabel = !empty($layer['customsizekeepaspect'])
                ? get_string('allowstretchpreviewimage', 'local_course_banner_builder')
                : get_string('keepaspectpreviewimage', 'local_course_banner_builder');
            $content .= \html_writer::tag('button', \html_writer::tag('i', '', [
                'class' => 'fa ' . (!empty($layer['customsizekeepaspect']) ? 'fa-lock' : 'fa-unlock'),
                'aria-hidden' => 'true',
            ]), [
                'type' => 'button',
                'class' => 'btn btn-link p-0 icon-no-margin local-course-banner-builder-preview-aspect-lock',
                'data-action' => 'local-course-banner-builder-toggle-preview-aspect-lock',
                'data-preview-aspect-lock' => '1',
                'data-local-course-banner-builder-hover-popover' => '1',
                'data-placement' => 'top',
                'data-content' => '<div class="no-overflow"><p>' . s($aspectlocklabel) . '</p></div>',
                'data-html' => 'true',
                'aria-label' => $aspectlocklabel,
            ]);
            $attributes['data-preview-current-layer'] = '1';
            $content .= \html_writer::span('', 'local-course-banner-builder-preview-resize-handle', [
                'data-preview-resize-handle' => '1',
                'data-preview-resize-mode' => 'corner',
                'data-preview-resize-edge' => 'bottom-right',
                'aria-hidden' => 'true',
            ]);
            foreach ([
                'top' => 'local-course-banner-builder-preview-resize-handle--top',
                'right' => 'local-course-banner-builder-preview-resize-handle--right',
                'bottom' => 'local-course-banner-builder-preview-resize-handle--bottom',
                'left' => 'local-course-banner-builder-preview-resize-handle--left',
            ] as $edge => $class) {
                $content .= \html_writer::span('', 'local-course-banner-builder-preview-resize-handle ' . $class, [
                    'data-preview-resize-handle' => '1',
                    'data-preview-resize-mode' => 'edge',
                    'data-preview-resize-edge' => $edge,
                    'aria-hidden' => 'true',
                ]);
            }
        }

        return \html_writer::tag(
            'div',
            $content,
            ['class' => $classes] + $attributes
        );
    }

    /**
     * Render one border overlay.
     *
     * @param array $options
     * @return string
     */
    protected function render_preview_border_overlay(array $options = []): string {
        $sidestyles = (array)($options['sidestyles'] ?? []);
        $dynamic = !empty($options['dynamic']);
        $wrapperattributes = $options['attributes'] ?? [];
        if (!empty($options['style'])) {
            $wrapperattributes['style'] = (string)$options['style'];
        }

        $renderpart = function(string $type, string $name, string $class) use ($sidestyles, $dynamic): string {
            $attributes = [];
            if ($dynamic) {
                $attributes['data-border-preview-' . $type] = $name;
            }
            if ($type === 'side' && !empty($sidestyles[$name])) {
                $attributes['style'] = $sidestyles[$name];
            }
            return \html_writer::span('', $class, $attributes);
        };

        $wrapperattributes['class'] = trim(
            (string)($options['class'] ?? 'local-course-banner-builder-preview-border-layer') .
            ' ' . (string)($wrapperattributes['class'] ?? '')
        );

        return \html_writer::tag(
            'div',
            $renderpart('side', 'top', 'local-course-banner-builder-border-preview-side local-course-banner-builder-border-preview-side-top') .
            $renderpart('side', 'right', 'local-course-banner-builder-border-preview-side local-course-banner-builder-border-preview-side-right') .
            $renderpart('side', 'bottom', 'local-course-banner-builder-border-preview-side local-course-banner-builder-border-preview-side-bottom') .
            $renderpart('side', 'left', 'local-course-banner-builder-border-preview-side local-course-banner-builder-border-preview-side-left') .
            $renderpart('corner', 'top-left', 'local-course-banner-builder-border-preview-corner local-course-banner-builder-border-preview-corner-top-left') .
            $renderpart('corner', 'top-right', 'local-course-banner-builder-border-preview-corner local-course-banner-builder-border-preview-corner-top-right') .
            $renderpart('corner', 'bottom-right', 'local-course-banner-builder-border-preview-corner local-course-banner-builder-border-preview-corner-bottom-right') .
            $renderpart('corner', 'bottom-left', 'local-course-banner-builder-border-preview-corner local-course-banner-builder-border-preview-corner-bottom-left') .
            \html_writer::span('', 'local-course-banner-builder-border-preview-hole', $dynamic ? ['data-border-preview-hole' => '1'] : []),
            $wrapperattributes
        );
    }

    /**
     * Render the standard Moodle collapse/expand icon wrapper.
     *
     * @param bool $collapsed
     * @return string
     */
    protected static function render_collapse_expand_icon(bool $collapsed = true): string {
        global $OUTPUT;

        return \html_writer::span(
            \html_writer::span(
                $OUTPUT->pix_icon('t/expandedchevron', get_string('collapse', 'core')),
                'expanded-icon icon-no-margin p-2'
            ) .
            \html_writer::span(
                \html_writer::span($OUTPUT->pix_icon('t/collapsedchevron', get_string('expand', 'core')), 'dir-rtl-hide') .
                \html_writer::span($OUTPUT->pix_icon('t/collapsedchevron_rtl', get_string('expand', 'core')), 'dir-ltr-hide'),
                'collapsed-icon icon-no-margin p-2'
            ),
            'btn btn-icon me-2 icons-collapse-expand local-course-banner-builder-collapse-icon' . ($collapsed ? ' collapsed' : ''),
            ['aria-hidden' => 'true', 'data-local-details-toggle-icon' => '1']
        );
    }

    /**
     * Render the create-layer type switch.
     *
     * @param bool $borderlocked
     * @param string $borderlockmessage
     * @param bool $overlaylocked
     * @param string $overlaylockmessage
     * @return string
     */
    protected static function render_layer_type_choice(
        bool $borderlocked,
        string $borderlockmessage,
        bool $overlaylocked = false,
        string $overlaylockmessage = ''
    ): string {
        $imagebutton = \html_writer::tag('button', \html_writer::tag('i', '', [
            'class' => 'fa fa-image me-2',
            'aria-hidden' => 'true',
        ]) . \html_writer::span(get_string('layertype:image', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-primary active',
            'data-layer-type-option' => 'image',
            'aria-pressed' => 'true',
        ]);
        $borderbutton = \html_writer::tag('button', \html_writer::tag('i', '', [
            'class' => 'fa fa-border-all me-2',
            'aria-hidden' => 'true',
        ]) . \html_writer::span(get_string('layertype:border', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary',
            'data-layer-type-option' => 'border',
            'aria-pressed' => 'false',
            'disabled' => $borderlocked ? 'disabled' : null,
            'aria-disabled' => $borderlocked ? 'true' : 'false',
        ]);
        $overlaybutton = \html_writer::tag('button', \html_writer::tag('i', '', [
            'class' => 'fa fa-adjust me-2',
            'aria-hidden' => 'true',
        ]) . \html_writer::span(get_string('layertype:overlay', 'local_course_banner_builder')), [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary',
            'data-layer-type-option' => 'overlay',
            'aria-pressed' => 'false',
            'disabled' => $overlaylocked ? 'disabled' : null,
            'aria-disabled' => $overlaylocked ? 'true' : 'false',
        ]);

        return \html_writer::div(
            \html_writer::div(
                \html_writer::div(get_string('layertypechoice', 'local_course_banner_builder'),
                    'local-course-banner-builder-slideshow-side-title') .
                \html_writer::div(
                    $imagebutton . $borderbutton . $overlaybutton,
                    'btn-group local-course-banner-builder-layer-type-toggle',
                    ['role' => 'group', 'data-layer-type-toggle' => '1']
                ) .
                \html_writer::span(
                    $borderlockmessage,
                    'local-course-banner-builder-layer-type-warning text-danger' . ($borderlocked ? '' : ' d-none'),
                    ['data-layer-type-border-warning' => '1']
                ) .
                \html_writer::span(
                    $overlaylockmessage,
                    'local-course-banner-builder-layer-type-warning text-danger' . ($overlaylocked ? '' : ' d-none'),
                    ['data-layer-type-overlay-warning' => '1']
                ),
                'local-course-banner-builder-layer-type-choice'
            ),
            'local-course-banner-builder-layer-type-choice-wrap'
        );
    }

    /**
     * Validate layer creation modes.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $draftfiles = \local_course_banner_builder\manager::get_draft_files((int)($data['bannerimage_filemanager'] ?? 0));
        $hasimage = !empty($draftfiles) || !empty($data['hasexistingimage']);
        $hasborder = !empty($data['borderenabled']) || (!empty($data['elementid']) && !empty($data['currentisborderlayer']));
        $hasoverlay = !empty($data['overlayenabled']) ||
            (!empty($data['elementid']) && !empty($data['currentisoverlaylayer']));

        if (!$hasimage && !$hasborder && !$hasoverlay) {
            $errors['bannerimage_filemanager'] = get_string('layercontentrequired', 'local_course_banner_builder');
        }

        if (($hasimage ? 1 : 0) + ($hasborder ? 1 : 0) + ($hasoverlay ? 1 : 0) > 1) {
            $errors['borderenabled'] = get_string('layercontentexclusive', 'local_course_banner_builder');
        }

        if ($hasborder && !empty($data['sourcehasborderlayer']) && empty($data['elementid'])) {
            $errors['borderenabled'] = (string)($this->_customdata['borderconflictmessage'] ??
                get_string('sourcealreadyhasborder', 'local_course_banner_builder'));
        }

        if ($hasoverlay && !empty($data['sourcehasoverlaylayer']) && empty($data['elementid'])) {
            $errors['overlayenabled'] = (string)($this->_customdata['overlayconflictmessage'] ??
                get_string('sourcealreadyhasoverlay', 'local_course_banner_builder'));
        }

        if (($data['fitmodeoverride'] ?? '') === \local_course_banner_builder\manager::FIT_MODE_CUSTOM) {
            $width = (float)($data['customwidthpercent'] ?? 0);
            $height = (float)($data['customheightpercent'] ?? 0);
            $keepaspect = !empty($data['customsizekeepaspect']);
            if ($width <= 0) {
                $errors['customwidthpercent'] = get_string('customsizerequiredwidth', 'local_course_banner_builder');
            }
            if (!$keepaspect && $height <= 0) {
                $errors['customheightpercent'] = get_string('customsizerequiredheight', 'local_course_banner_builder');
            }
        }

        return $errors;
    }
}
