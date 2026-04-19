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
 * French strings.
 *
 * @package    local_course_banner_builder
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actions'] = 'Actions';
$string['addlayer'] = 'Ajouter un calque';
$string['bannerdeleted'] = 'Bannière supprimée.';
$string['bannerimage'] = 'Image de bannière';
$string['bannerimage_help'] = 'Déposez une ou plusieurs images. Si plusieurs images sont envoyées en une fois, les noms de calque et l’ordre de tri sont créés automatiquement.';
$string['bulkuploadnote'] = 'Lorsque vous envoyez plusieurs images en même temps, les noms de calque et l’ordre de tri sont attribués automatiquement. Vous pourrez les ajuster juste après dans le tableau ci-dessous.';
$string['category'] = 'Source';
$string['categorycontentdeleted'] = 'Les images et règles de la source ont été supprimées.';
$string['categoryimagesdeleted'] = 'Les images de la source ont été supprimées. Les règles ont été conservées.';
$string['categorysettings'] = 'Réglages de la source';
$string['categories'] = 'Catégories';
$string['choosecategory'] = 'Choisir une source';
$string['compositionmode'] = 'Mode de composition de la source';
$string['compositionmode_help'] = 'Choisissez la manière dont cette source contribue à l’image finale du cours. Cumulatif empile toutes les images actives selon l’ordre de tri. Aléatoire choisit une image active au hasard dans cette source.';
$string['compositionmode:cumulative'] = 'Cumulatif';
$string['compositionmode:random'] = 'Aléatoire';
$string['configuredcategories'] = 'Sources configurées';
$string['course_banner_builder:manage'] = 'Gérer les images de bannière des cours';
$string['coursecustomfields'] = 'Champs personnalisés de cours';
$string['currentbanner'] = 'Bannière actuelle';
$string['customfieldpriority'] = 'Prioritaire sur les autres sources';
$string['customfieldpriority_help'] = 'Disponible avec les sources de champs personnalisés : si des règles existent sur des catégories de ces cours, celles-ci seront prioritaires.';
$string['customfieldscomingsoon'] = 'Préparé pour la prochaine étape : les champs personnalisés ne sont pas encore appliqués aux bannières.';
$string['customfieldtype:select'] = 'Liste déroulante';
$string['customfieldtype:text'] = 'Texte';
$string['deletebanner'] = 'Supprimer la bannière';
$string['deletecategorycontent'] = 'Tout supprimer de cette source';
$string['deletecategorycontentconfirm'] = 'Êtes-vous sûr de vouloir supprimer toutes les images et toutes les règles de cette source ?';
$string['deletecategoryimages'] = 'Supprimer uniquement les images';
$string['deletecategoryimagesconfirm'] = 'Êtes-vous sûr de vouloir supprimer toutes les images de cette source en conservant ses règles ?';
$string['deleteselectedlayers'] = 'Supprimer les calques sélectionnés';
$string['deleteselectedlayersconfirm'] = 'Êtes-vous sûr de vouloir supprimer les calques sélectionnés ?';
$string['deselectsource'] = 'Désélectionner';
$string['dragreorderlayer'] = 'Glisser-déposer pour réordonner le calque';
$string['editimage'] = 'Modifier image';
$string['enabled'] = 'Activé';
$string['enabledcustomfields'] = 'Champs personnalisés utilisables comme sources';
$string['enabledcustomfields_desc'] = 'Sélectionnez les champs personnalisés de cours de type texte ou liste déroulante qui pourront être proposés comme sources de calques dans la gestion des bannières.';
$string['exportconfig'] = 'Exporter la configuration';
$string['exportconfigdesc'] = 'Télécharge la configuration actuelle des règles et des calques de source au format JSON. L’export inclut déjà les données d’image et des chemins d’archive pour évoluer ensuite vers un transfert ZIP.';
$string['exportimport'] = 'Export / import de configuration';
$string['fitapplyscope'] = 'Appliquer les règles et images de la source à';
$string['fitapplyscope_help'] = 'Choisissez si cette source reste isolée, avec uniquement ses propres images et règles, ou si elle participe aussi à l’héritage pour les sources enfants avec les calques hérités des parents.';
$string['fitapplyscope:descendants'] = 'Source sélectionnée et enfants, avec héritage des parents';
$string['fitapplyscope:self'] = 'Source sélectionnée uniquement, isolée des sources parentes et enfants';
$string['fitmode'] = 'Mode de taille des images';
$string['fitmode_help'] = 'Choisissez si les images de cette source sont redimensionnées au format de la bannière ou si elles conservent leur taille d’origine lors de l’assemblage final.';
$string['fitmode:bannerfit'] = 'Ajuster à la bannière';
$string['fitmode:original'] = 'Conserver la taille d’origine';
$string['fitoverride'] = 'Surcharge de taille du calque';
$string['fitoverride:categorydefault'] = 'Utiliser le réglage de la source';
$string['fitoverridehelp'] = 'Ce calque surcharge uniquement pour cette image la règle de taille définie sur la source.';
$string['hierarchy'] = 'Hiérarchie';
$string['hierarchychildbase'] = 'source';
$string['hierarchychild'] = 'Sous source';
$string['hierarchychildprefix'] = 'sous ';
$string['hierarchydescendant'] = '{$a}';
$string['importconfig'] = 'Importer une configuration';
$string['importconfigdesc'] = 'Collez un JSON exporté précédemment. La correspondance des catégories utilise d’abord l’idnumber, puis le chemin de catégories. Les catégories manquantes sont créées automatiquement.';
$string['importconfigreplaceall'] = 'Supprimer la configuration actuelle du plugin avant l’import';
$string['importedconfig'] = 'Configuration importée.';
$string['inheritance'] = 'Héritage';
$string['inheritance_help'] = 'Si une source de cours n’a pas d’image de bannière active, le plugin recherche dans les sources parentes jusqu’à en trouver une.';
$string['invalidimportpayload'] = 'Le contenu importé n’est pas valide.';
$string['layername'] = 'Nom du calque';
$string['layers'] = 'Calques';
$string['managebanners'] = 'Gérer les bannières de cours';
$string['nobannerconfigured'] = 'Aucune bannière n’est encore configurée pour cette source.';
$string['nocategoryselected'] = 'Choisissez une source pour modifier sa bannière.';
$string['noconfiguredcategories'] = 'Aucune image de bannière n’a encore été configurée.';
$string['nocustomfieldsourceenabled'] = 'Aucun champ personnalisé activé dans les réglages du plugin.';
$string['no'] = 'Non';
$string['pluginname'] = 'Constructeur de bannières de cours';
$string['privacy:metadata'] = 'Le plugin Course banner builder stocke uniquement la configuration des sources de bannière. Il ne stocke pas de données personnelles.';
$string['rootcategory'] = 'Source racine';
$string['savebanner'] = 'Enregistrer le calque';
$string['savebannerlayers'] = 'Enregistrer le ou les calque(s)';
$string['savecategorysettings'] = 'Enregistrer les réglages de la source';
$string['savelayerchanges'] = 'Enregistrer modification des calques';
$string['selectedcategoryempty'] = 'Aucune règle ni aucun calque d’image n’est encore configuré pour cette source.';
$string['selectedcategorystatus'] = 'Source sélectionnée';
$string['selectedlayersdeleted'] = '{$a} calque(s) sélectionné(s) supprimé(s).';
$string['selectcategory'] = 'Sélectionner la source';
$string['selectcustomfieldsource'] = 'Sélectionner un champ personnalisé';
$string['choosecategorydefault'] = 'Choisir une catégorie';
$string['choosecustomfielddefault'] = 'Choisir un champ personnalisé';
$string['searchcategories'] = 'Rechercher une catégorie';
$string['searchcategoriesplaceholder'] = 'Tapez un nom de catégorie...';
$string['searchcustomfields'] = 'Rechercher un champ personnalisé';
$string['searchcustomfieldsplaceholder'] = 'Tapez un nom de champ...';
$string['source'] = 'Source de calques';
$string['settings'] = 'Constructeur de bannières de cours';
$string['sortorder'] = 'Ordre de tri';
$string['sortordercumulativeonly'] = 'Utilisé en mode cumulatif';
$string['sourcelayerslist'] = 'Liste des calques de la source';
$string['sourcesettingsshort'] = 'Réglages';
$string['sourceshortcircuithelp'] = 'Cette source court-circuite l’enchaînement de sources : ses enfants n’héritent pas de ses règles ni de ses calques.';
$string['sourceshortcircuited'] = 'Source court circuitée';
$string['transferconfig'] = 'Transférer la configuration';
$string['viewconfigured'] = 'Images de bannière configurées';
$string['yes'] = 'Oui';
$string['bulkselectedlayers'] = 'Actions sur les calques sélectionnés';
$string['deselectall'] = 'Tout désélectionner';
$string['renderratio:default'] = 'L’image finale du cours est stockée comme image de cours Moodle. Le ratio affiché dépend de la mise en page du thème actif.';
$string['renderratio:easyedu'] = 'Le thème actif EasyEdu affiche l’en-tête de cours dans .page-header-banner avec background-size: cover et une hauteur desktop de 18.75rem. Le canvas généré est donc optimisé pour une bannière large en 4:1.';
$string['selectall'] = 'Tout sélectionner';
$string['unknown'] = 'Inconnu';
$string['matchingcategories'] = 'Catégories affichées :';
$string['matchingcustomfields'] = 'Champs affichés :';
$string['managecategorieslink'] = 'Gérer les catégories Moodle';
$string['managecategorieslink_help'] = 'Ouvre la gestion native Moodle des catégories et sous-catégories.';
$string['uploadguidance'] = '<div class="local-course-banner-builder-upload-guidance"><p><strong>Limite d’upload Moodle :</strong> {$a->maxbytes}</p><p><strong>Limite image de cours :</strong> {$a->overviewmaxbytes} ; types acceptés pour l’image de cours : {$a->overviewtypes}</p><p><strong>Bannière générée :</strong> {$a->canvas} ; ratio {$a->ratio}</p><p><strong>Thème détecté :</strong> {$a->theme}. {$a->themedetails}</p></div>';
$string['uploadguidancetitle'] = 'Contraintes d’upload et d’affichage';
$string['usedsourceprefix'] = '✓';
$string['webimages'] = 'Images web';
