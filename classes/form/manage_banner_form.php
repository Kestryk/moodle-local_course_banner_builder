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
        global $SITE;

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
        $issitebanneradmin = !empty($this->_customdata['issitebanneradmin']);
        $slideshowoverlaydefault = is_array($this->_customdata['slideshowoverlaydefault'] ?? null)
            ? $this->_customdata['slideshowoverlaydefault']
            : [];
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
            'advcheckbox',
            'imagecenterfixed',
            get_string('imagecenterfixed', 'local_course_banner_builder')
            );
            $mform->setDefault('imagecenterfixed', 0);
            $mform->addHelpButton('imagecenterfixed', 'imagecenterfixed', 'local_course_banner_builder');

            $mform->addElement(
            'advcheckbox',
            'imageaboveoverlayenabled',
            get_string('imageaboveoverlayenabled', 'local_course_banner_builder')
            );
            $mform->setDefault('imageaboveoverlayenabled', 0);
            $mform->addHelpButton('imageaboveoverlayenabled', 'imageaboveoverlayenabled', 'local_course_banner_builder');

            $mform->addElement(
            'advcheckbox',
            'imagebelowinheritedenabled',
            get_string('imagebelowinheritedenabled', 'local_course_banner_builder')
            );
            $mform->setDefault('imagebelowinheritedenabled', 0);
            $mform->addHelpButton('imagebelowinheritedenabled', 'imagebelowinheritedenabled', 'local_course_banner_builder');

            $mform->addElement(
            'advcheckbox',
            'imageaboveinheritedenabled',
            get_string('imageaboveinheritedenabled', 'local_course_banner_builder')
            );
            $mform->setDefault('imageaboveinheritedenabled', 0);
            $mform->addHelpButton('imageaboveinheritedenabled', 'imageaboveinheritedenabled', 'local_course_banner_builder');

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
            $overlaydetailsattrs['data-overlay-site-only'] = $issitebanneradmin ? '1' : '0';
            $overlaydetailsattrs['data-overlay-inherit-color'] = (string)($slideshowoverlaydefault['overlaycolor'] ?? '#000000');
            $overlaydetailsattrs['data-overlay-inherit-opacity'] = (string)round(
                max(0, min(0.85, (float)($slideshowoverlaydefault['overlayopacity'] ?? 0.38))) * 100
            );
            $titlecontext = $issitebanneradmin ? 'site' :
                ((bool)get_config('local_course_banner_builder', 'bannertitle_course_enabled') ? 'course' : 'activity');
            $overlaydetailsattrs['data-overlay-title-preview-enabled'] =
                (bool)get_config('local_course_banner_builder', 'bannertitle_' . $titlecontext . '_enabled') ? '1' : '0';
            if ($titlecontext === 'site') {
                $sitetitle = trim((string)($SITE->fullname ?? ''));
                $overlaydetailsattrs['data-overlay-title-preview-text'] = $sitetitle === ''
                    ? get_string('previewsitetitle', 'local_course_banner_builder')
                    : $sitetitle;
            } else {
                $overlaydetailsattrs['data-overlay-title-preview-text'] = get_string(
                    $titlecontext === 'activity' ? 'previewactivitytitle' : 'previewcoursetitle',
                    'local_course_banner_builder'
                );
            }
            $titleprefix = 'bannertitle_' . $titlecontext . '_';
            $titlegetconfig = static function(string $name, $default) use ($titleprefix) {
                $value = get_config('local_course_banner_builder', $titleprefix . $name);
                return $value === false || $value === null || $value === '' ? $default : $value;
            };
            $titlehex = static function($value, string $default = '#FFFFFF'): string {
                $value = (string)$value;
                return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtoupper($value) : $default;
            };
            $titlenum = static function(string $name, float $default, float $min, float $max) use ($titlegetconfig): float {
                return max($min, min($max, (float)$titlegetconfig($name, $default)));
            };
            $titlebool = static function(string $name, bool $default = false) use ($titlegetconfig): bool {
                return (bool)$titlegetconfig($name, $default ? 1 : 0);
            };
            $titlestyle = [
                'enabled' => $titlebool('enabled', true),
                'x' => $titlenum('x', 50, 0, 100),
                'y' => $titlenum('y', 50, 0, 100),
                'fontsize' => $titlenum('fontsize', 100, 25, 480),
                'lineheight' => $titlenum('lineheight', 105, 40, 540),
                'fontfamily' => (string)$titlegetconfig('fontfamily', ''),
                'color' => $titlehex($titlegetconfig('color', '#FFFFFF')),
                'align' => in_array((string)$titlegetconfig('align', 'center'), ['left', 'center', 'right'], true)
                    ? (string)$titlegetconfig('align', 'center')
                    : 'center',
                'bold' => $titlebool('bold', true),
                'italic' => $titlebool('italic'),
                'underline' => $titlebool('underline'),
                'strike' => $titlebool('strike'),
                'allcaps' => $titlebool('allcaps'),
                'aboveoverlay' => $titlebool('aboveoverlay', true),
                'frameenabled' => $titlebool('frameenabled'),
                'frametype' => in_array((string)$titlegetconfig('frametype', 'box'), ['box', 'highlight'], true)
                    ? (string)$titlegetconfig('frametype', 'box')
                    : 'box',
                'framecolor' => $titlehex($titlegetconfig('framecolor', '#000000'), '#000000'),
                'frameopacity' => $titlenum('frameopacity', 35, 0, 100),
                'framebordercolor' => $titlehex($titlegetconfig('framebordercolor', '#FFFFFF')),
                'frameborderwidth' => $titlenum('frameborderwidth', 0, 0, 10),
                'frameradius' => $titlenum('frameradius', 12, 0, 80),
                'framepadding' => $titlenum('framepadding', 18, 0, 240),
                'frameshadowenabled' => $titlebool('frameshadowenabled'),
                'frameshadowcolor' => $titlehex($titlegetconfig('frameshadowcolor', '#000000'), '#000000'),
                'frameshadowopacity' => $titlenum('frameshadowopacity', 25, 0, 100),
                'frameshadowblur' => $titlenum('frameshadowblur', 14, 0, 80),
                'frameshadowdistance' => $titlenum('frameshadowdistance', 6, 0, 50),
                'frameshadowdirection' => $titlenum('frameshadowdirection', 135, 0, 360),
                'shadowenabled' => $titlebool('shadowenabled', true),
                'shadowcolor' => $titlehex($titlegetconfig('shadowcolor', '#000000'), '#000000'),
                'shadowopacity' => $titlenum('shadowopacity', 55, 0, 100),
                'shadowblur' => $titlenum('shadowblur', 10, 0, 60),
                'shadowdistance' => $titlenum('shadowdistance', 4, 0, 40),
                'shadowdirection' => $titlenum('shadowdirection', 135, 0, 360),
            ];
            $overlaydetailsattrs['data-overlay-title-preview-style'] = json_encode($titlestyle);
            $mform->addElement('html', \html_writer::start_tag('details', $overlaydetailsattrs));
            $summarycontent = self::render_collapse_expand_icon(!$currentisoverlaylayer) .
                \html_writer::span(
                    get_string('overlaysettings', 'local_course_banner_builder'),
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
            if ($issitebanneradmin) {
                $mform->addElement('hidden', 'overlaytarget', \local_course_banner_builder\manager::OVERLAY_TARGET_BANNER, [
                    'id' => 'id_overlaytarget',
                    'data-overlay-site-target' => '1',
                ]);
            } else {
                $mform->addElement(
                    'select',
                    'overlaytarget',
                    get_string('overlaytarget', 'local_course_banner_builder'),
                    \local_course_banner_builder\manager::get_overlay_target_options(),
                    ['data-overlay-target-select' => '1']
                );
            }
            $mform->setType('overlaytarget', PARAM_ALPHA);
            $mform->setDefault(
                'overlaytarget',
                $issitebanneradmin
                    ? \local_course_banner_builder\manager::OVERLAY_TARGET_BANNER
                    : \local_course_banner_builder\manager::OVERLAY_TARGET_BOTH
            );

            $mform->addElement('static', 'overlayappearanceheading', '', \html_writer::div(
                get_string('overlayappearance', 'local_course_banner_builder'),
                'local-course-banner-builder-slideshow-side-title mt-2'
            ));
            $colorgroup = [];
            $colorgroup[] = $mform->createElement('html', \html_writer::empty_tag('input', [
                'type' => 'color',
                'id' => 'id_overlaybannercolor_picker',
                'class' => 'form-control form-control-color local-course-banner-builder-color-picker',
                'value' => '#000000',
                'data-overlay-color-picker' => 'overlaybanner',
                'aria-label' => get_string('overlaycolor', 'local_course_banner_builder'),
            ]));
            $colorgroup[] = $mform->createElement('text', 'overlaybannercolor', '', [
                'data-overlay-color-text' => 'overlaybanner',
            ]);
            $mform->addGroup(
                $colorgroup,
                'overlaybannercolorgroup',
                get_string('overlaycolor', 'local_course_banner_builder'),
                '',
                false
            );
            $mform->setType('overlaybannercolor', PARAM_RAW_TRIMMED);
            $mform->setDefault('overlaybannercolor', '#000000');
            $mform->addElement('text', 'overlaybanneropacity', get_string('overlayopacity', 'local_course_banner_builder'), [
                'size' => 6,
                'data-upgrade-number' => '1',
                'data-number-min' => '0',
                'data-number-max' => '100',
                'data-number-step' => '1',
                'data-field-suffix' => '%',
                'data-percent-slider-input' => '1',
            ]);
            $mform->setType('overlaybanneropacity', PARAM_FLOAT);
            $mform->setDefault('overlaybanneropacity', 25);
            $this->add_percent_slider_static($mform, 'overlaybanneropacity', 0, 100, 1);
            $mform->addElement('hidden', 'overlayslideshowcolor', '#000000', ['id' => 'id_overlayslideshowcolor']);
            $mform->setType('overlayslideshowcolor', PARAM_RAW_TRIMMED);
            $mform->setDefault('overlayslideshowcolor', '#000000');
            $mform->addElement('hidden', 'overlayslideshowopacity', 38, ['id' => 'id_overlayslideshowopacity']);
            $mform->setType('overlayslideshowopacity', PARAM_FLOAT);
            $mform->setDefault('overlayslideshowopacity', 38);
            $mform->addElement('advcheckbox', 'overlaytitleabove', get_string('overlaytitleabove', 'local_course_banner_builder'));
            $mform->setDefault('overlaytitleabove', 1);
            $mform->addElement('advcheckbox', 'overlayborderabove', get_string('overlayborderabove', 'local_course_banner_builder'));
            $mform->setDefault('overlayborderabove', 1);
            $mform->addElement('html', '</details>');

            if ($formmode === 'editoverlay') {
                $mform->addElement('static', 'layerpreview', '', $this->render_banner_preview_panel(
                    'layerpreview',
                    $previewdefinition,
                    $showmoodlepreview,
                    false,
                    false,
                    true,
                    get_string('borderpreviewhelp', 'local_course_banner_builder')
                ));
            }
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
                false,
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
     * @param bool $showcurrentoverlay
     * @return string
     */
    protected function render_border_preview_frame(
        string $variant,
        string $bound,
        array $previewdefinition = [],
        bool $showcurrentimage = false,
        bool $showcurrentborder = false,
        bool $showcurrentoverlay = false
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
        $currentoverlaylayer = $showcurrentoverlay ? $this->render_preview_overlay_layer(
            $currentlayer,
            'local-course-banner-builder-banner-overlay-layer local-course-banner-builder-preview-overlay-layer--current',
            [
                'data-layer-overlay-preview' => '1',
                'data-preview-current-overlay' => '1',
                'data-preview-overlay-title-above' => !empty($currentlayer['overlaytitleabove']) ? '1' : '0',
                'data-preview-overlay-border-above' => !empty($currentlayer['overlayborderabove']) ? '1' : '0',
                'data-preview-sortorder' => (string)($currentlayer['sortorder'] ?? 0),
                'data-preview-zindex' => (string)($currentlayer['zindex'] ?? (1000 + (int)($currentlayer['sortorder'] ?? 0))),
            ],
            (int)($currentlayer['zindex'] ?? (1000 + (int)($currentlayer['sortorder'] ?? 0)))
        ) : '';
        $slideshowpreviewlayer = $showcurrentoverlay ? $this->render_overlay_slideshow_target_preview() : '';

        $bannerformat = \local_course_banner_builder\manager::normalise_banner_format(
            (string)($previewdefinition['bannerformat'] ?? \local_course_banner_builder\manager::BANNER_FORMAT_STANDARD)
        );

        return \html_writer::div(
            \html_writer::div('', 'local-course-banner-builder-banner-preview-base') .
            $contextlayershtml .
            $currentimagelayer .
            $currentborderlayer .
            $currentoverlaylayer .
            $slideshowpreviewlayer,
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
     * @param bool $showcurrentoverlay
     * @param string $helptext
     * @return string
     */
    protected function render_banner_preview_panel(
        string $fieldname,
        array $previewdefinition,
        bool $showmoodlepreview,
        bool $showcurrentimage,
        bool $showcurrentborder,
        bool $showcurrentoverlay,
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
        $previewsource = null;
        $sourcekey = trim((string)($this->_customdata['sourcekey'] ?? ''));
        if ($sourcekey !== '') {
            $previewsource = \local_course_banner_builder\manager::resolve_source($sourcekey);
        }
        if (!$previewsource && !empty($this->_customdata['issitebanneradmin'])) {
            $previewsource = \local_course_banner_builder\manager::get_site_source();
        }

        $titlecontextdefinition = null;
        if ($previewsource) {
            $titlelayer = \local_course_banner_builder\manager::export_banner_title_preview_layer($previewsource);
            if ($titlelayer) {
                $titlecontextdefinition = [
                    'text' => (string)($titlelayer['text'] ?? ''),
                    'style' => (string)($titlelayer['style'] ?? ''),
                    'framestyle' => (string)($titlelayer['framestyle'] ?? ''),
                    'frametype' => (string)($titlelayer['frametype'] ?? 'box'),
                    'zindex' => (int)($titlelayer['zindex'] ?? 3010),
                ];
            }
        }
        if ($titlecontextdefinition === null) {
            foreach (($previewdefinition['contextlayers'] ?? []) as $layer) {
                if (($layer['type'] ?? '') !== 'title') {
                    continue;
                }
                $titlecontextdefinition = [
                    'text' => (string)($layer['text'] ?? ''),
                    'style' => (string)($layer['style'] ?? ''),
                    'framestyle' => (string)($layer['framestyle'] ?? ''),
                    'frametype' => (string)($layer['frametype'] ?? 'box'),
                    'zindex' => (int)($layer['zindex'] ?? 3010),
                ];
                break;
            }
        }
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
                        $showcurrentborder,
                        $showcurrentoverlay
                    ),
                    'local-course-banner-builder-border-preview-variant'
                ),
                'local-course-banner-builder-border-preview',
                [
                    'data-border-preview' => $showcurrentborder ? '1' : '0',
                    'data-layer-banner-preview' => '1',
                    'data-preview-source-context' => (string)($previewdefinition['sourcecontext'] ?? ''),
                    'data-default-fitmode' => (string)($previewdefinition['defaultfitmode'] ?? \local_course_banner_builder\manager::FIT_MODE_BANNER),
                    'data-preview-title-context-definition' => $titlecontextdefinition ? json_encode($titlecontextdefinition) : '',
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

        if (($layer['type'] ?? '') === 'overlay') {
            return $this->render_preview_overlay_layer(
                $layer,
                'local-course-banner-builder-banner-overlay-layer local-course-banner-builder-preview-overlay-layer--context',
                [
                    'data-preview-context-layer' => '1',
                    'data-preview-layer-id' => (string)($layer['id'] ?? 0),
                    'data-preview-inherited' => !empty($layer['isinherited']) ? '1' : '0',
                    'data-preview-overlay-title-above' => !empty($layer['overlaytitleabove']) ? '1' : '0',
                    'data-preview-overlay-border-above' => !empty($layer['overlayborderabove']) ? '1' : '0',
                    'data-preview-sortorder' => (string)($layer['sortorder'] ?? 0),
                    'data-preview-zindex' => (string)($layer['zindex'] ?? (1000 + (int)($layer['sortorder'] ?? 0))),
                ],
                (int)($layer['zindex'] ?? (1000 + (int)($layer['sortorder'] ?? 0)))
            );
        }

        if (($layer['type'] ?? '') === 'title') {
            return $this->render_preview_title_layer($layer, [
                'data-preview-context-layer' => '1',
                'data-preview-title-context-layer' => '1',
                'data-preview-zindex' => (string)($layer['zindex'] ?? 3010),
            ]);
        }

        return '';
    }

    /**
     * Render one contextual title preview layer.
     *
     * @param array $layer
     * @param array $attributes
     * @return string
     */
    protected function render_preview_title_layer(array $layer, array $attributes = []): string {
        $text = (string)($layer['text'] ?? '');
        $framestyle = trim((string)($layer['framestyle'] ?? ''));
        $ishighlight = (string)($layer['frametype'] ?? 'box') === 'highlight';
        $content = '';

        if ($ishighlight && $framestyle !== '') {
            $lines = preg_split('/\R/u', $text);
            $lines = $lines === false ? [$text] : $lines;
            foreach ($lines as $index => $line) {
                $content .= \html_writer::span($line === '' ? '&nbsp;' : s($line), '', ['style' => $framestyle]);
                if ($index < count($lines) - 1) {
                    $content .= \html_writer::empty_tag('br');
                }
            }
        } else {
            $content = s($text);
        }

        $classes = 'local-course-banner-builder-banner-title-overlay ' .
            'local-course-banner-builder-preview-title-layer local-course-banner-builder-preview-title-layer--context';
        if ($ishighlight) {
            $classes .= ' local-course-banner-builder-banner-title-overlay--highlight-frame';
        }

        return \html_writer::div($content, $classes, $attributes + [
            'style' => (string)($layer['style'] ?? ''),
            'aria-hidden' => 'true',
        ]);
    }

    /**
     * Render the non-editable slideshow example used when an overlay targets the slideshow only.
     *
     * @return string
     */
    protected function render_overlay_slideshow_target_preview(): string {
        $issitebanneradmin = !empty($this->_customdata['issitebanneradmin']);
        $context = $issitebanneradmin
            ? \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE
            : \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_COURSE;
        $config = is_array($this->_customdata['slideshowoverlaydefault'] ?? null)
            ? $this->_customdata['slideshowoverlaydefault']
            : \local_course_banner_builder\manager::get_slideshow_config($context);
        $bannerformat = \local_course_banner_builder\manager::normalise_banner_format(
            $issitebanneradmin
                ? \local_course_banner_builder\manager::get_site_banner_format()
                : \local_course_banner_builder\manager::get_course_banner_format()
        );
        $styledefaults = \local_course_banner_builder\manager::get_default_slideshow_style_values();
        $stylenumber = static function(string $field) use ($config, $styledefaults): int {
            return (int)($config[$field] ?? $styledefaults[$field]);
        };
        $stylestring = static function(string $field) use ($config, $styledefaults): string {
            return (string)($config[$field] ?? $styledefaults[$field]);
        };
        $stylergb = static function(string $hex): string {
            $hex = ltrim($hex, '#');
            if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
                return '0, 0, 0';
            }
            return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' .
                hexdec(substr($hex, 4, 2));
        };
        $fontclamp = static function(string $kind, int $percent, string $format): string {
            $scale = max(25, min(100, $percent)) / 100;
            if ($format === \local_course_banner_builder\manager::BANNER_FORMAT_STANDARD) {
                $scale *= 1.24;
            } else if ($format === \local_course_banner_builder\manager::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT) {
                $scale *= $kind === 'label' ? 1.0 : 0.78;
            }
            if ($kind === 'title') {
                return 'clamp(' . round(10 * $scale, 3) . 'cqh, min(' . round(28 * $scale, 3) . 'cqh, ' .
                    round(3.4 * $scale, 3) . 'cqw), ' . round(36 * $scale, 3) . 'cqh)';
            }
            if ($kind === 'label') {
                return 'clamp(' . round(3.5 * $scale, 3) . 'cqh, min(' . round(6.4 * $scale, 3) . 'cqh, ' .
                    round(0.82 * $scale, 3) . 'cqw), ' . round(8.4 * $scale, 3) . 'cqh)';
            }
            if ($kind === 'action') {
                return 'clamp(' . round(6 * $scale, 3) . 'cqh, min(' . round(13 * $scale, 3) . 'cqh, ' .
                    round(1.6 * $scale, 3) . 'cqw), ' . round(18 * $scale, 3) . 'cqh)';
            }
            if ($kind === 'actionwidth') {
                return 'clamp(' . round(10 * $scale, 3) . 'cqw, ' . round(18 * $scale, 3) . 'cqw, ' .
                    round(34 * $scale, 3) . 'cqw)';
            }
            if ($kind === 'actionheight') {
                return 'clamp(' . round(10 * $scale, 3) . 'cqh, min(' . round(22 * $scale, 3) . 'cqh, ' .
                    round(2.7 * $scale, 3) . 'cqw), ' . round(34 * $scale, 3) . 'cqh)';
            }
            return 'clamp(' . round(5.5 * $scale, 3) . 'cqh, min(' . round(14 * $scale, 3) . 'cqh, ' .
                round(1.7 * $scale, 3) . 'cqw), ' . round(19 * $scale, 3) . 'cqh)';
        };

        $titlefontsize = max(25, min(100, (int)($config['titlefontsize'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_TITLE_FONT_PERCENT)));
        $bodyfontsize = max(25, min(100, (int)($config['bodyfontsize'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_BODY_FONT_PERCENT)));
        $actionsize = max(25, min(100, (int)($config['actionsize'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_ACTION_SIZE_PERCENT)));
        $actionwidth = max(25, min(100, (int)($config['actionwidth'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_ACTION_WIDTH_PERCENT)));
        $actionheight = max(25, min(100, (int)($config['actionheight'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_ACTION_HEIGHT_PERCENT)));
        $labelsize = max(25, min(100, (int)($config['labelsize'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_LABEL_SIZE_PERCENT)));
        $labeltextsize = max(25, min(160, (int)($config['labeltextsize'] ?? 100)));
        $bodylineheight = max(80, min(200, (float)($config['bodylineheight'] ?? 135)));
        $labelorientation = (string)($config['labelorientation'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_LABEL_ORIENTATION);
        $labelorientation = $labelorientation === \local_course_banner_builder\manager::SLIDESHOW_LABEL_ORIENTATION_COLUMN
            ? \local_course_banner_builder\manager::SLIDESHOW_LABEL_ORIENTATION_COLUMN
            : \local_course_banner_builder\manager::SLIDESHOW_LABEL_ORIENTATION_ROW;
        $labelalign = (string)($config['labelalign'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_LABEL_ALIGN);
        $labelalign = in_array($labelalign, ['left', 'center', 'right'], true) ? $labelalign : 'center';
        $labelitemsalign = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'][$labelalign];
        $labelcorners = (string)($config['labelcorners'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_LABEL_CORNERS);
        $actioncorners = (string)($config['actioncorners'] ??
            \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_ACTION_CORNERS);
        $labelcolors = $config['labelcolors'] ?? \local_course_banner_builder\manager::get_default_slideshow_label_colors();

        $previewstyle = '--local-course-banner-builder-slideshow-overlay-rgb: ' .
            s((string)($config['overlayrgb'] ?? '0, 0, 0')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-overlay-opacity: ' .
            number_format((float)($config['overlayopacity'] ??
                \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_OVERLAY_OPACITY), 2, '.', '') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-size: ' .
            $fontclamp('title', $titlefontsize, $bannerformat) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-size: ' .
            $fontclamp('body', $bodyfontsize, $bannerformat) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-body-line-height: ' . $bodylineheight . '%;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-action-font-size: ' .
            $fontclamp('action', $actionsize, $bannerformat) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-action-width: ' .
            $fontclamp('actionwidth', $actionwidth, $bannerformat) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-action-height: ' .
            $fontclamp('actionheight', $actionheight, $bannerformat) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-font-size: ' .
            $fontclamp('label', $labelsize, $bannerformat) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-text-scale: ' .
            number_format($labeltextsize / 100, 2, '.', '') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-orientation: ' . s($labelorientation) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-radius: ' .
            ($labelcorners === \local_course_banner_builder\manager::SLIDESHOW_CORNER_SQUARE ? '0.28rem' : '999px') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-action-radius: ' .
            ($actioncorners === \local_course_banner_builder\manager::SLIDESHOW_CORNER_SQUARE ? '0.28rem' : '999px') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-title-color: ' .
            s((string)($config['titlecolor'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_TITLE_COLOR)) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-body-color: ' .
            s((string)($config['bodycolor'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_BODY_COLOR)) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-title-font-family: ' .
            ((string)($config['titlefontfamily'] ?? '') !== '' ? s((string)$config['titlefontfamily']) : 'inherit') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-body-font-family: ' .
            ((string)($config['bodyfontfamily'] ?? '') !== '' ? s((string)$config['bodyfontfamily']) : 'inherit') . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-title-text-align: ' .
            s((string)($config['titlealign'] ?? 'center')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-body-text-align: ' .
            s((string)($config['bodyalign'] ?? 'center')) . ';';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-translate-x: -50%;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-items-align: ' . s($labelitemsalign) . ';';
        foreach (['title' => '800', 'body' => '700', 'action' => '700', 'label' => '700'] as $target => $boldweight) {
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-font-weight: ' .
                (!empty($config[$target . 'bold']) ? $boldweight : '400') . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-font-style: ' .
                (!empty($config[$target . 'italic']) ? 'italic' : 'normal') . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-text-decoration: ' .
                ((!empty($config[$target . 'underline']) || !empty($config[$target . 'strike'])) ?
                    trim((!empty($config[$target . 'underline']) ? 'underline ' : '') .
                        (!empty($config[$target . 'strike']) ? 'line-through' : '')) : 'none') . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-text-transform: ' .
                (!empty($config[$target . 'allcaps']) || ($target === 'label' && !array_key_exists('labelallcaps', $config))
                    ? 'uppercase'
                    : 'none') . ';';
        }
        $previewstyle .= ' --local-course-banner-builder-slideshow-title-x: ' .
            number_format((float)($config['titlex'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_TITLE_X), 3, '.', '') . '%;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-title-y: ' .
            number_format((float)($config['titley'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_TITLE_Y), 3, '.', '') . '%;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-body-x: ' .
            number_format((float)($config['bodyx'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_BODY_X), 3, '.', '') . '%;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-body-y: ' .
            number_format((float)($config['bodyy'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_BODY_Y), 3, '.', '') . '%;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-action-x: ' .
            number_format((float)($config['actionx'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_ACTION_X), 3, '.', '') . '%;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-action-y: ' .
            number_format((float)($config['actiony'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_ACTION_Y), 3, '.', '') . '%;';
        $labely = (float)($config['labely'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_LABEL_Y);
        if ($bannerformat === \local_course_banner_builder\manager::BANNER_FORMAT_FULLWIDTH_TOP_COMPACT &&
                abs($labely - \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_LABEL_Y) < 0.001) {
            $labely = 18.0;
        }
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-x: ' .
            number_format((float)($config['labelx'] ?? \local_course_banner_builder\manager::SLIDESHOW_DEFAULT_LABEL_X), 3, '.', '') . '%;';
        $previewstyle .= ' --local-course-banner-builder-slideshow-label-y: ' . number_format($labely, 3, '.', '') . '%;';
        foreach (['action', 'label'] as $target) {
            $shadowdirection = deg2rad($stylenumber($target . 'shadowdirection'));
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-opacity: ' .
                number_format($stylenumber($target . 'opacity') / 100, 2, '.', '') . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-border-width: ' .
                $stylenumber($target . 'borderwidth') . 'px;';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-padding: ' .
                $stylenumber($target . 'padding') . 'px;';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-opacity: ' .
                number_format($stylenumber($target . 'shadowopacity') / 100, 2, '.', '') . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-blur: ' .
                $stylenumber($target . 'shadowblur') . 'px;';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-x: ' .
                number_format(cos($shadowdirection) * $stylenumber($target . 'shadowdistance'), 2, '.', '') . 'px;';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-y: ' .
                number_format(sin($shadowdirection) * $stylenumber($target . 'shadowdistance'), 2, '.', '') . 'px;';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-background: ' .
                s($stylestring($target . 'backgroundcolor')) . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-border-color: ' .
                s($stylestring($target . 'bordercolor')) . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-shadow-rgb: ' .
                s($stylergb($stylestring($target . 'shadowcolor'))) . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-font-family: ' .
                ($stylestring($target . 'fontfamily') !== '' ? s($stylestring($target . 'fontfamily')) : 'inherit') . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-' . $target . '-text-color: ' .
                s($stylestring($target . 'textcolor')) . ';';
        }
        foreach ($labelcolors as $type => $colors) {
            $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-bg: ' .
                s((string)($colors['background'] ?? '#000000')) . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-color: ' .
                s((string)($colors['text'] ?? '#FFFFFF')) . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-border: ' .
                s((string)($colors['border'] ?? '#FFFFFF')) . ';';
            $previewstyle .= ' --local-course-banner-builder-slideshow-label-' . s($type) . '-shadow-rgb: ' .
                s($stylergb((string)($colors['shadow'] ?? '#000000'))) . ';';
        }

        $previewlabelicon = $context === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE ? 'fa-bullhorn' : 'fa-comments';
        $previewlabelkey = $context === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE
            ? 'slideshow:type:siteannouncements'
            : 'slideshow:type:courseforum';
        $previewlabelclass = $context === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE ? 'siteannouncements' : 'forums';
        $previewsecondarylabel = $context === \local_course_banner_builder\manager::SLIDESHOW_CONTEXT_SITE ? 'CAT2' : 'COURSE101';
        $previewcontent = \html_writer::div('', 'local-course-banner-builder-slideshow-admin-preview-backdrop') .
            \html_writer::div('', 'local-course-banner-builder-slideshow-admin-preview-overlay') .
            \html_writer::div(
                \html_writer::div(
                    \html_writer::span(
                        \html_writer::tag('i', '', [
                            'class' => 'fa ' . $previewlabelicon . ' local-course-banner-builder-slideshow-label-icon',
                            'aria-hidden' => 'true',
                        ]) . \html_writer::span(get_string($previewlabelkey, 'local_course_banner_builder')),
                        'local-course-banner-builder-slideshow-label local-course-banner-builder-slideshow-label--' .
                            $previewlabelclass
                    ) .
                    \html_writer::span(
                        \html_writer::span($previewsecondarylabel),
                        'local-course-banner-builder-slideshow-label local-course-banner-builder-slideshow-label--course-shortname'
                    ),
                    'local-course-banner-builder-slideshow-labels'
                ) .
                \html_writer::div(
                    \html_writer::tag('h3', get_string('slideshowpreviewtitle', 'local_course_banner_builder'), [
                        'class' => 'local-course-banner-builder-slideshow-title',
                    ]),
                    'local-course-banner-builder-slideshow-title-block'
                ) .
                \html_writer::div(
                    \html_writer::tag('p', get_string('slideshowpreviewmeta', 'local_course_banner_builder'), [
                        'class' => 'local-course-banner-builder-slideshow-meta',
                    ]) .
                    \html_writer::tag('p', get_string('slideshowpreviewbody', 'local_course_banner_builder'), [
                        'class' => 'local-course-banner-builder-slideshow-body',
                    ]),
                    'local-course-banner-builder-slideshow-body-block'
                ) .
                \html_writer::div(
                    \html_writer::tag('button', get_string('slideshowview', 'local_course_banner_builder'), [
                        'type' => 'button',
                        'class' => 'btn local-course-banner-builder-slideshow-action',
                        'tabindex' => '-1',
                    ]),
                    'local-course-banner-builder-slideshow-action-wrap'
                ),
                'local-course-banner-builder-slideshow-admin-preview-content local-course-banner-builder-slideshow-slide is-active'
            );

        return \html_writer::div(
            $previewcontent,
            'local-course-banner-builder-slideshow-admin-preview local-course-banner-builder-layer-overlay-slideshow-preview ' .
                'local-course-banner-builder-slideshow-admin-preview--format-' .
                preg_replace('/[^a-z0-9_-]+/i', '', $bannerformat),
            [
                'data-layer-overlay-slideshow-preview' => '1',
                'data-banner-format' => $bannerformat,
                'style' => $previewstyle,
                'hidden' => 'hidden',
                'aria-hidden' => 'true',
            ]
        );
    }

    /**
     * Render one overlay layer in a live preview.
     *
     * @param array $layer
     * @param string $classes
     * @param array $attributes
     * @param int $zindex
     * @return string
     */
    protected function render_preview_overlay_layer(array $layer, string $classes, array $attributes, int $zindex): string {
        $style = trim((string)($layer['wrapperstyle'] ?? ''));
        if ($style !== '' && !str_ends_with($style, ';')) {
            $style .= ';';
        }
        return \html_writer::div('', $classes, $attributes + [
            'style' => $style . ' z-index: ' . $zindex . ';',
        ]);
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
            'data-preview-center-fixed' => !empty($currentlayer['imagecenterfixed']) ? '1' : '0',
            'data-preview-above-overlay' => !empty($currentlayer['imageaboveoverlayenabled']) ? '1' : '0',
            'data-preview-below-inherited' => !empty($currentlayer['imagebelowinheritedenabled']) ? '1' : '0',
            'data-preview-above-inherited' => !empty($currentlayer['imageaboveinheritedenabled']) ? '1' : '0',
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
        if (!isset($attributes['data-preview-above-overlay'])) {
            $attributes['data-preview-above-overlay'] = !empty($layer['imageaboveoverlayenabled']) ? '1' : '0';
        }
        if (!isset($attributes['data-preview-below-inherited'])) {
            $attributes['data-preview-below-inherited'] = !empty($layer['imagebelowinheritedenabled']) ? '1' : '0';
        }
        if (!isset($attributes['data-preview-above-inherited'])) {
            $attributes['data-preview-above-inherited'] = !empty($layer['imageaboveinheritedenabled']) ? '1' : '0';
        }
        if (!isset($attributes['data-preview-center-fixed'])) {
            $attributes['data-preview-center-fixed'] = !empty($layer['imagecenterfixed']) ? '1' : '0';
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
