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
 * English strings.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['bannerimage'] = 'Banner layer';
$string['bannerimage_help'] = 'Upload one or more layer images. When several files are uploaded together, layer names and sort order are created automatically.';
$string['bannerimagedisabledforborder'] = 'File upload is disabled for a border-only layer.';
$string['bannerdeleted'] = 'Layer deleted.';
$string['bulkuploadnote'] = 'When you upload several images at once, layer names and sort order are assigned automatically. You can refine them in the table below right after saving.';
$string['category'] = 'Source';
$string['categoryimagesdeleted'] = 'Source layers deleted. Source rules were kept.';
$string['categorycontentdeleted'] = 'Source layers and rules deleted.';
$string['categorysettings'] = 'Source settings';
$string['categories'] = 'Categories';
$string['choosecategory'] = 'Choose a source';
$string['configuredcategories'] = 'Configured sources';
$string['course_banner_builder:manage'] = 'Manage course banner layers';
$string['coursecustomfields'] = 'Course custom fields';
$string['currentbanner'] = 'Current banner';
$string['customfieldpriority'] = 'Priority over other sources';
$string['customfieldpriority_help'] = 'Choose what happens when a course matches this custom-field-value source and also has a configured category source.';
$string['customfieldpriority:category'] = 'Category source wins when it has an image';
$string['customfieldpriority:customfield'] = 'Custom field source wins and ignores category rules';
$string['customfieldpriority:append'] = 'Apply category chain first, then add this custom field source last';
$string['customfieldtype:select'] = 'Dropdown';
$string['customfieldtype:text'] = 'Text';
$string['deletebanner'] = 'Delete layer';
$string['deletecategorycontent'] = 'Delete source';
$string['deletecategorycontentconfirm'] = 'Are you sure you want to delete all layers and rules for this source?';
$string['deletecategoryimages'] = 'Delete layers only';
$string['deletecategoryimagesconfirm'] = 'Are you sure you want to delete all layers in this source while keeping its rules?';
$string['deleteselectedlayers'] = 'Delete selected layers';
$string['deleteselectedlayersconfirm'] = 'Are you sure you want to delete the selected layers?';
$string['deselectsource'] = 'Deselect';
$string['dragreorderlayer'] = 'Drag and drop to reorder the layer';
$string['editimage'] = 'Edit layer';
$string['enabled'] = 'Enabled';
$string['enabledcustomfields'] = 'Custom fields available as sources';
$string['enabledcustomfields_desc'] = 'Select course custom fields of type text or dropdown that can be offered as layer sources in banner management.';
$string['exportimport'] = 'Export / import configuration';
$string['exportconfig'] = 'Export configuration';
$string['exportconfigdesc'] = 'Download the current source rules and layers as JSON. The export already includes image data and archive paths so it can evolve into a ZIP transfer later.';
$string['hierarchy'] = 'Hierarchy';
$string['inheritance'] = 'Inheritance';
$string['inheritance_help'] = 'If a course source has no enabled banner image, the plugin searches its parent sources until it finds one.';
$string['managebanners'] = 'Manage course banners';
$string['actions'] = 'Actions';
$string['addlayer'] = 'Add one or more layer(s)';
$string['compositionmode'] = 'Source composition mode';
$string['compositionmode_help'] = 'Choose how this source contributes to the final course image. Cumulative stacks all enabled images in sort order. Random picks one enabled image at random from this source.';
$string['compositionmode:cumulative'] = 'Cumulative';
$string['compositionmode:random'] = 'Random';
$string['fitmode'] = 'Image sizing mode';
$string['fitmode_help'] = 'Choose whether images from this source are stretched to the banner canvas, cropped in cover mode without distortion, or keep their original dimensions when the final image is assembled.';
$string['fitmode:bannerfit'] = 'Fill banner';
$string['fitmode:cover'] = 'Keep proportions';
$string['fitmode:original'] = 'Do not modify layer size / ratio';
$string['fitapplyscope'] = 'Apply source rules and images to';
$string['fitapplyscope_help'] = 'Choose whether this source stays isolated, using only its own images and rules, or whether it also participates in inheritance for child sources together with parent layers above it.';
$string['fitapplyscope:self'] = 'Selected source only, isolated from parent and child sources';
$string['fitapplyscope:descendants'] = 'Selected source and children, with inherited parent layers';
$string['fitoverride'] = 'Layer size override';
$string['fitoverride:categorydefault'] = 'Use source default';
$string['fitoverridehelp'] = 'This layer overrides the source sizing rule for this image only.';
$string['layeradvanced'] = 'Advanced layer options';
$string['layeradvanced_help'] = 'Fine-tune how this layer is sized and placed without changing the source defaults.';
$string['layerborder'] = 'Add a border';
$string['layercontentexclusive'] = 'Choose either an image or a border for this layer, not both.';
$string['layercontentrequired'] = 'Add an image or enable a border before saving this layer.';
$string['dynamicimagesizeenabled'] = 'Dynamic-size image';
$string['dynamicimagesizeenabled_help'] = 'When enabled, this image is no longer rendered inside the generated banner image. It is added as an HTML/CSS layer above the banner and keeps scaling responsively while using the configured size, placement, and spacing settings.';
$string['positionanchor'] = 'Image placement';
$string['positionanchor_help'] = 'Choose where the image is placed. Positions other than Center can use percentage spacing from the relevant banner edges.';
$string['positionanchor:center'] = 'Centered';
$string['positionanchor:top'] = 'Top center';
$string['positionanchor:bottom'] = 'Bottom center';
$string['positionanchor:left'] = 'Left center';
$string['positionanchor:right'] = 'Right center';
$string['positionanchor:top-left'] = 'Top left';
$string['positionanchor:top-right'] = 'Top right';
$string['positionanchor:bottom-left'] = 'Bottom left';
$string['positionanchor:bottom-right'] = 'Bottom right';
$string['layeroffsettoppercent'] = 'Top spacing (%)';
$string['layeroffsetrightpercent'] = 'Right spacing (%)';
$string['layeroffsetbottompercent'] = 'Bottom spacing (%)';
$string['layeroffsetleftpercent'] = 'Left spacing (%)';
$string['layeroffsethelp'] = 'Use percentages so the image spacing stays proportional to the banner size.';
$string['borderenabled'] = 'Add a border';
$string['bordercolor'] = 'Border color';
$string['bordercolor_help'] = 'Accepts HEX or rgba() values.';
$string['borderwidth'] = 'Border thickness';
$string['borderwidthhelp'] = 'Thickness is stored as a percentage of the rendered layer size on each side.';
$string['borderopacity'] = 'Border opacity';
$string['borderfade'] = 'Border fade';
$string['borderinnerrounded'] = 'Round inner corners';
$string['borderpreviewhelp'] = 'Live preview of the current border settings.';
$string['imagepreviewhelp'] = 'Live preview of the final banner render for the current image layer.';
$string['bannerpreviewtitle'] = 'Final banner preview';
$string['bannerpreviewtogglecontext'] = 'Show other existing and inherited layers';
$string['bannerpreviewthemeused'] = 'Theme used: {$a}';
$string['borderstyle'] = 'Border style';
$string['borderstyle:solid'] = 'Block';
$string['borderstyle:dashed'] = 'Dashed';
$string['borderdashlength'] = 'Dash length';
$string['bordertitle'] = 'Border';
$string['borderaddedsingular'] = 'border added';
$string['borderaddedplural'] = 'borders added';
$string['borderlayersingular'] = 'border layer added';
$string['borderlayerplural'] = 'border layers added';
$string['additionalborderlayers'] = '+{$a} border layer';
$string['bordersides'] = 'Border sides';
$string['bordersides:all'] = 'All';
$string['bordersides:top'] = 'Top';
$string['bordersides:right'] = 'Right';
$string['bordersides:bottom'] = 'Bottom';
$string['bordersides:left'] = 'Left';
$string['rangevalue'] = 'Current value: {$a}';
$string['importconfig'] = 'Import configuration';
$string['importconfigdesc'] = 'Paste a previously exported JSON configuration. Category matching uses idnumber first, then the category path. Missing categories are created automatically.';
$string['importconfigreplaceall'] = 'Delete existing plugin configuration before import';
$string['importedconfig'] = 'Configuration imported.';
$string['invalidimportpayload'] = 'The import payload is invalid.';
$string['nobannerconfigured'] = 'No layer is configured for this source yet.';
$string['selectedcategoryempty'] = 'No rules or layers are configured for this source yet.';
$string['selectedcategoryemptycta'] = 'Click here or use Settings to add some.';
$string['selectedcategorystatus'] = 'Selected source';
$string['layername'] = 'Layer name';
$string['layers'] = 'Layers';
$string['nocategoryselected'] = 'Choose a source to edit its banner image.';
$string['noconfiguredcategories'] = 'No banner layers have been configured yet.';
$string['nocustomfieldsourceenabled'] = 'No custom field enabled in the plugin settings.';
$string['pluginname'] = 'Course banner builder';
$string['privacy:metadata'] = 'The Course banner builder plugin stores banner source configuration only. It does not store personal data.';
$string['savebanner'] = 'Save banner';
$string['savebannerlayers'] = 'Save layer(s)';
$string['savecategorysettings'] = 'Save source settings';
$string['savelayerchanges'] = 'Save layer changes';
$string['selectedlayersdeleted'] = '{$a} selected layer(s) deleted.';
$string['nolayeradded'] = 'No layer added';
$string['sortorder'] = 'Sort order';
$string['sortordercumulativeonly'] = 'Used in cumulative mode';
$string['sourcelayerslist'] = 'Source layer list';
$string['sourcesettingsshort'] = 'Settings';
$string['sourceshortcircuithelp'] = 'This source short-circuits the source chain: its children do not inherit its rules or layers.';
$string['sourceshortcircuited'] = 'Short circuited source';
$string['selectcategory'] = 'Select source';
$string['selectcustomfieldsource'] = 'Select custom field value';
$string['choosecategorydefault'] = 'Choose a category';
$string['choosecustomfielddefault'] = 'Choose a custom field value';
$string['searchcategories'] = 'Search categories';
$string['searchcategoriesplaceholder'] = 'Type a category name...';
$string['searchcustomfields'] = 'Search a custom field';
$string['searchcustomfieldsplaceholder'] = 'Type a field name...';
$string['settings'] = 'Course banner builder';
$string['transferconfig'] = 'Transfer configuration';
$string['rootcategory'] = 'Root source';
$string['hierarchychildbase'] = 'source';
$string['hierarchychild'] = 'Sub source';
$string['hierarchychildprefix'] = 'Sub ';
$string['hierarchydescendant'] = '{$a}';
$string['source'] = 'Layer source';
$string['viewconfigured'] = 'Configured banner layers';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['bulkselectedlayers'] = 'Selected layer actions';
$string['deselectall'] = 'Deselect all';
$string['renderratio:default'] = 'The final course image is stored as a Moodle course overview file. The displayed ratio depends on the active theme layout.';
$string['renderratio:easyedu'] = 'The active EasyEdu theme displays the course header in .page-header-banner with background-size: cover and a desktop height of 18.75rem. The generated banner canvas is therefore optimized for a wide 4:1 header.';
$string['selectall'] = 'Select all';
$string['unknown'] = 'Unknown';
$string['matchingcategories'] = 'Displayed categories:';
$string['matchingcustomfields'] = 'Displayed field values:';
$string['selectedcustomfieldstatus'] = 'Selected custom field value';
$string['managecategorieslink'] = 'Manage Moodle categories';
$string['managecategorieslink_help'] = 'Opens Moodle native category and subcategory management.';
$string['uploadguidance'] = '<div class="local-course-banner-builder-upload-guidance"><p><strong>Moodle upload limit:</strong> {$a->maxbytes}</p><p><strong>Course image limit:</strong> {$a->overviewmaxbytes}; accepted course image types: {$a->overviewtypes}</p><p><strong>Generated banner:</strong> {$a->canvas}; ratio {$a->ratio}</p><p><strong>Detected theme:</strong> {$a->theme}. {$a->themedetails}</p></div>';
$string['uploadguidancetitle'] = 'Upload and display constraints';
$string['usedsourceprefix'] = '✓';
$string['webimages'] = 'Web images';
$string['sourcealreadyhasborder'] = 'A border already exists in this source.';
$string['sourcealreadyhasborderinline'] = 'A border layer already exists in this source';
$string['sourcechainalreadyhasborder'] = 'A border already exists in this source chain (parent or child source).';
$string['sourcechainalreadyhasborderinline'] = 'A border layer already exists in the source chain (parent or child source)';
$string['chainborderexistinglabel'] = 'Existing border in the chain';
$string['editsourceoflayer'] = 'Edit the source of this layer';
$string['inherited'] = 'Inherited';
$string['borderlayerlockedorderlabel'] = 'Locked border layer order';
$string['borderlayerlockedorderhelp'] = 'This border layer always stays above the other layers from the source and inherited chain. Its sort order is therefore locked.';
$string['dynamiclayerlockedorderlabel'] = 'Locked dynamic layer order';
$string['dynamiclayerlockedorderhelp'] = 'This dynamic image layer always stays just below the border and above the other layers. Its sort order is therefore locked.';
$string['fitmode:custom'] = 'Custom size';
$string['customwidthpercent'] = 'Custom width (%)';
$string['customheightpercent'] = 'Custom height (%)';
$string['customsizekeepaspect'] = 'Keep original dimensions';
$string['customsizesummary'] = 'Custom size';
$string['layeroffsetsummary'] = 'Spacing';
$string['customsizerequiredwidth'] = 'Enter a custom width greater than 0%.';
$string['customsizerequiredheight'] = 'Enter a custom height greater than 0%.';
