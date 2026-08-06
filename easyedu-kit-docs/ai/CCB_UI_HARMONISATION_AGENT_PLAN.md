# Course Banner Builder UI Harmonisation Agent Plan

## Extension des demandes UI — 2026-07-30

Cette extension transforme les nouveaux retours en lots vérifiables. Elle ne
vaut pas autorisation de modifier EasyStud, le dépôt EasyEdu UI Kit, Moodle,
les fixtures ou le runtime partagé. Chaque lot reste CCB-owned jusqu'à ce
qu'un motif soit confirmé comme réutilisable dans le Kit.

### Lot A — aperçu général et actions latérales

- Conserver une hauteur de bouton stable entre Desktop authoring et Mobile
  public simulation ; le changement de mode ne doit pas réduire les boutons.
- Faire occuper au panneau d'actions la largeur réellement disponible entre le
  cadre d'aperçu et le bord droit, sans colonne collée ni débordement.
- Regrouper canvas, mode, filmstrip, visibilité et actions dans des régions
  stables ; le filmstrip et les icônes restent indépendants de la largeur
  logique du canvas.
- Remplacer les libellés de mode trop longs par des libellés courts et
  explicites, après vérification des chaînes anglaises et françaises.
- Ajouter une aide contextuelle près de « Preview mode » avec le contrat de
  bulle du Kit ; elle doit rester clavier-accessible et ne pas modifier l'état.
- Ajouter une transition courte entre les modes, avec respect de
  `prefers-reduced-motion` et sans animation nécessaire à la compréhension.

### Lot B — contrat global des boutons et icônes

- Aligner les boutons Save/Delete sur le même contrat visuel que les autres
  actions : rôle, hauteur, icône, espacement, focus, états destructifs et
  ordre clavier.
- Appliquer le même emplacement d'icône, la même ligne de base et le même
  comportement de retour à la ligne dans les aperçus généraux, modales,
  diaporamas, listes Sources et listes de calques.
- Dans la liste Sources et dans chaque liste de calques, garder l'icône à
  gauche et centrer le texte dans l'espace restant ; les traductions longues
  doivent se replier sans déplacer l'icône.
- Ne pas ajouter de nouvelle décision de placement locale : tout nouveau
  bouton doit déclarer sa zone, son rôle, son slot d'icône et son token de
  taille dans le contrat d'action CCB.

### Lot C — aides contextuelles et bulles

- Auditer tous les `?` de CCB, en prenant comme référence le style des aides
  du paramétrage Slideshow.
- Utiliser la bulle du Kit (surface, padding, flèche, largeur, focus et
  positionnement) pour les listes de calques, sources, modales, Slideshow et
  les autres écrans CCB.
- Déterminer ce qui est CCB-only et ce qui constitue un primitive réutilisable.
  Une extension du Kit doit être proposée séparément avec contrat et capture
  avant toute synchronisation ; ne pas copier un worktree Kit dirty.
- Vérifier les chaînes Moodle, les noms accessibles, Escape, focus et absence
  de débordement aux cinq cellules responsive.

### Lot D — liste des calques de source

- Retirer du rendu d'administration le dropdown de choix « Custom size »,
  « Fill banner », etc., sans supprimer le modèle de données ni les valeurs
  déjà persistées avant décision produit explicite.
- Transformer les informations secondaires sous chaque calque en un accordéon
  dédié, contenu dans la cellule et compatible avec le style EasyEdu.
- Préserver les hooks de tri, sélection, édition, suppression, héritage,
  confirmation et les attributs `data-*` existants.
- Donner aux lignes non déplaçables un état rayé explicite ; conserver le
  motif existant pour les verrous génériques et définir des variantes de
  couleur distinctes pour bordure et overlay (et le cas titre si présent).
- Auditer les lignes héritées et les états vide/chargement avant de modifier
  le markup, afin de ne pas casser les chaînes de sources.

### Lot E — modales d'ajout et d'édition de calques

- Refaire le panneau d'actions à droite dans les modales d'ajout d'image,
  bordure et overlay, puis dans les modales d'édition ; il doit rester dans sa
  boîte, utiliser la largeur disponible et éviter toute superposition.
- Corriger le slot icône/label et la hauteur des actions sans renommer les
  cibles de modal ni les contrats AMD.
- Corriger l'ouverture/fermeture des accordéons internes et leur focus,
  notamment lorsqu'un aperçu sticky ou un footer sticky est présent.
- Corriger la bordure supérieure masquée du dropdown dans l'ajout d'image en
  respectant le stacking context existant plutôt qu'en ajoutant un z-index
  global.
- Après chaque correctif modal, exécuter une vérification ciblée de la modal
  concernée avant de passer à la suivante.

### Lot F — cartes et drag/drop

- Comparer les états drag/drop CCB avec les cartes Participant, Groupe et
  Groupement d'EasyStud.
- Vérifier d'abord si le motif est déjà templétisé dans le Kit. S'il est
  réutilisable, ouvrir un handoff UI Kit séparé ; sinon appliquer uniquement
  un adaptateur CCB documenté.
- Préserver les alternatives clavier/non-drag, les lignes verrouillées, les
  états hover/focus/dragging et l'ordre de sauvegarde.

### Lot G — accordéons, titres et surfaces de lecture

- Auditer les trois familles d'accordéons CCB et les ramener à un seul contrat
  visuel, en prenant `Course banner options` comme référence.
- Placer le chevron à gauche, exposer `aria-expanded`/`aria-controls`, garder
  une zone de titre entièrement cliquable et ajouter une transition pliage /
  dépliage cohérente avec le Kit.
- Respecter `prefers-reduced-motion` et vérifier que l'animation ne déplace pas
  le focus ni ne coupe le contenu lors de l'ouverture dans une modal.
- Refaire une passe de hiérarchie des titres : niveaux, poids, tailles,
  marges, contrastes, lignes de base et cohérence entre administration,
  preview, listes, modales et slideshow. Préserver le contrat H1 Moodle et
  les H2 CCB déjà validés.

### Lot H — liste Sources, sticky et débordements

- Rétablir et tester le comportement sticky de `Selected source` : il doit
  rester accroché en haut de la vue lorsque la page défile sous son seuil,
  sans masquer le contenu ni le header Moodle.
- Vérifier le contexte de stacking, les ancres de scroll, les conteneurs
  `overflow` et le comportement à 100 % et 200 % avant de choisir une règle
  CSS ou une adaptation JS.
- Fermer correctement les quatre côtés des tableaux Sources et des chaînes,
  avec des rayons de bordure cohérents, sans couper les menus ou popovers qui
  doivent sortir du shell.
- Corriger les dropdowns de sélection de source pour les intitulés longs :
  largeur bornée, retour à la ligne/ellipsis contrôlé, zone d'icône protégée,
  scroll interne et absence de débordement horizontal.
- Tester titres anglais/français longs, options sélectionnées, état vide,
  filtre et clavier sans modifier les valeurs persistées.

#### Lot H.1 — stabilisation définitive du sticky `Selected source`

- Reproduire le clignotement dans les deux sens de scroll, à l’entrée et à la
  sortie du seuil d’origine, avec les états container et pleine largeur. Noter
  le scroll parent réel, les sentinelles, le holder/portal, les classes et les
  écritures `position`/largeur qui alternent.
- Réduire le comportement à une machine d’état unique : `inline`, `stuck` et
  `restored`. Une seule source d’autorité peut promouvoir ou restituer le
  bandeau ; elle ne doit pas se déclencher à nouveau sur ses propres mutations
  de DOM ou de géométrie.
- Préserver la largeur pleine vue quand l’état est `stuck`, le retour exact à
  l’emplacement d’origine quand le scroll remonte au-dessus du seuil, le header
  Moodle, le focus, le bouton Deselect et le centrage responsive déjà validé.
- Valider des oscillations lentes et rapides autour du seuil aux cinq cellules
  responsive, sans saut de largeur, scintillement, double bandeau, débordement
  ou erreur console. Ce lot ne modifie pas la géométrie des previews.

**État au 2026-08-05 — terminé et accepté visuellement.** Le fallback CSS `sticky` reste
utilisable sans JavaScript ; dès que le runtime AMD est actif, une machine
d'état unique gère le portal `inline`/`stuck` et sa restitution. Le placeholder
de la position initiale est recalculé après la mise en page finale du bandeau :
il reste donc une sentinelle stable pendant les passages autour du seuil. La
validation supervisée Moodle 5.1 couvre les cinq cellules (1600×900, 1024×768
et 390×844 à 100 %, puis 1600×900 et 390×844 à 200 %) et les cinq transitions
autour du seuil. Les preuves externes sont dans
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260804T203520059Z-3572`.
La revue humaine du 2026-08-05 a confirmé que le bandeau passe en pleine
largeur en descendant, revient exactement dans son emplacement d'origine en
remontant et ne clignote pas à proximité du seuil. Ce lot ne modifie ni le
rendu public, ni la géométrie des aperçus, ni la politique 128 px.

### Lot I — sélection et survol dans les aperçus

- Ajouter un cadre de sélection visible autour des éléments qui dépassent la
  zone d'aperçu lorsqu'ils sont sélectionnés ; le cadre doit rester dans le
  conteneur, suivre la géométrie réelle et ne pas modifier le rendu public.
- Ajouter un état de surbrillance au survol, plus discret que l'état sélectionné,
  avec contraste suffisant, support clavier (`:focus-visible`) et respect de
  `prefers-reduced-motion`.
- Harmoniser ces états pour l'aperçu général, les modales, le slideshow et les
  éléments hérités, sans intercepter les événements de drag/resize ni changer
  l'ordre des calques.

### Lot J — chargement des modales et annulation du crop

- Centrer le logo/cercle de chargement dans la surface vide de chaque modal,
  y compris avec aperçu sticky, viewport étroit, 200 % et contenu lent.
  Vérifier le stacking context et le focus sans ajouter de z-index global.
- Reproduire exactement le bug signalé : ajout d'image, ouverture du crop,
  modification, validation du crop, action Retour/Annuler de la barre d'actions
  (Undo), puis enregistrement. Le résultat sauvegardé doit être l'image entière,
  pas le rectangle de crop validé juste avant.
- Distinguer les deux contrats : Cancel dans l'éditeur de crop restaure une
  session ouverte ; Undo après Apply restaure un snapshot de formulaire et de
  draft. Les deux doivent écrire `false, 0, 0, 100, 100` dans le calque actif,
  les champs cachés, le calque visuel et `multilayerdraftsettings`.
- Ajouter un test ciblé de régression couvrant ajout, crop, Apply, Undo,
  sauvegarde, rechargement et comparaison preview/rendu, avec nettoyage dans
  `finally`. Ce lot est fonctionnel et nécessite PHP/AMD/SCSS ou tests selon
  le point d'intégration trouvé.

### Lot K — lisibilité des listes Sources et calques

- Donner à l'état vide de la liste des calques une surface de cellule pleine
  largeur, centrée et alignée avec les colonnes réelles ; il ne doit pas prendre
  la géométrie d'une ligne de calque existante.
- Remplacer les seules bordures haut/bas de la ligne Source en cours d'édition
  par une surface légèrement teintée selon son code couleur, un rail latéral
  et un focus visible. Le statut reste explicite sans reposer uniquement sur
  la couleur et sans déplacer les actions.
- Vérifier l'aperçu de bordure avec des mesures calculées des quatre côtés.
  Corriger seulement si les épaisseurs diffèrent dans la même configuration,
  en préservant les bordures, overlays, titres, ratios et rendu public.
- Faire ouvrir les accordéons d'action sur toute leur hauteur de contenu :
  aucune contrainte `max-height`, grille ou `overflow` ne doit couper le dernier
  champ. Tester les contenus courts et longs, les modales et le zoom natif 200 %.
- Reprendre la liste de calques déplaçables à partir des conventions EasyStud
  et de la primitive Kit additive `object-row-cells`, sans modifier le Kit :
  grip `::` accessible, curseur grab/grabbing, survol discret, état drag clair
  et focus de ligne unique. Retirer les séparateurs entre toutes les cellules
  et le contour cellule-par-cellule qui masquent la lecture, sans changer les
  hooks de tri, les lignes verrouillées ni les alternatives clavier.
- Pour `Layer infos & overrides`, donner au contrôle `?` un curseur pointeur,
  une aide clavier-accessible et la même transition de disclosure que les
  autres accordéons. Le panneau doit pouvoir se déplier entièrement.
- Retirer seulement l'affichage de `image sizing mode` et son dropdown des
  calques d'image, y compris dans les modales. Conserver le modèle de données,
  les valeurs existantes et les champs nécessaires à la compatibilité tant
  qu'une décision produit de migration n'est pas prise.

#### Lot K.1 — états drag/drop de la liste de calques — terminé le 2026-08-01

- Adaptateur CCB appliqué sur la primitive additive du Kit, sans modifier le
  Kit ni EasyStud : poignée native à six points, rail unique dans la première
  cellule, survol/focus de ligne discret et curseurs `grab`/`grabbing`.
- Les cellules restantes ne reçoivent plus de contour de focus ni de séparateur
  visuel. Les calques verrouillés gardent leur ordre et leurs alternatives
  clavier ; Border et Overlay utilisent des rails et hachures distincts.
- La validation supervisée Moodle 5.1 a sélectionné un unique scénario et a
  passé à 100 % et 200 % natif, sans débordement, erreur console/requête ni
  résidu de fixture ou de profil. Le reste du lot K demeure à réaliser.

#### Lot K.2 — identité, surfaces verrouillées et cases à cocher — terminé le 2026-08-02

- Les hachures des lignes verrouillées sont désormais peintes une seule fois
  sur la surface de ligne, avec une opacité réduite. Les cellules restent
  transparentes ; l’échantillon Overlay conserve seul sa surface opaque sans
  hachures derrière lui.
- L’identité visuelle remplace l’ancien pictogramme de verrou dans la première
  cellule : type Border, Overlay ou Image. Une image réellement déplaçable
  affiche à sa place la poignée à six points ; la raison du verrou reste dans
  la colonne d’ordre, où son aide est la plus logique.
- Les sélecteurs de table et les cases à cocher des modales de titres utilisent
  la primitive `selection-checkbox` du Kit. Un adaptateur CSS limité aux mforms
  CCB applique les mêmes états aux autres cases natives sans modifier les
  interrupteurs.
- La validation Moodle 5.1 couvre les surfaces verrouillées, l’échantillon
  Overlay, les poignées, l’ordre au clavier et les cases de table à 100 % et
  200 % natif. Une revue visuelle des formulaires mform hors tableau reste à
  planifier dans leur lot modal respectif.

#### Lot K.3 — raffinement de surface Sources/Layers — terminé le 2026-08-05

Ce sous-lot est le seul périmètre d’implémentation ouvert par
`EED-CCB-2026-0001`. Il ne réouvre pas les contrats déjà validés de K.1/K.2.

- Aligner le motif rayé des lignes verrouillées entre deux lignes consécutives
  sans fusionner leurs états sémantiques : chaque type conserve sa couleur,
  son rail et sa raison de verrou. Ajouter une séparation interligne légère,
  stable et compatible avec la lecture de tableau, sans réintroduire de
  bordure cellule-par-cellule.
- Conserver l’icône de type Image au repos pour une ligne déplaçable et la
  remplacer visuellement par la poignée `::` au survol/focus de ligne, sans
  supprimer le nom accessible, le tri clavier ou l’alternative non-drag.
- Épaissir modérément le rail gauche des lignes de calque, y intégrer les
  icônes blanches de type/poignée, et réserver un padding interne suffisant :
  le tableau reste rectangulaire, sans adopter les coins de carte EasyStud.
- Redistribuer les colonnes de liste Sources/Layers lorsque les mesures le
  permettent, afin que les actions ne soient pas tassées, sans modifier les
  hooks d’action, les valeurs, le tri ou les confirmations.
- Redessiner l’état replié/déplié de `Layer infos & overrides` : alignement
  intérieur, chevron, aide locale, surface et disclosure cohérents avec le
  Kit embarqué. Le contenu entier reste mesurable et accessible au clavier.
- Retirer l’interface `Image sizing mode` des accords de source et des modales
  de réglages de source comme des calques Image ; ne supprimer aucune donnée,
  valeur cachée ni comportement de compatibilité.
- Vérifier l’état vide, la ligne Source en cours d’édition, l’aperçu Border et
  les accordéons d’action selon le périmètre déjà enregistré, sans étendre le
  lot aux modales générales.

La surface K.3 et ses retours P1 ont passé les contrôles statiques, build SCSS
et un scénario Moodle 5.1 unique à 100 %/200 % natif le 2026-08-04. La
validation prouve un rail de largeur constante, l’échange Image/poignée dans
la même boîte centrée, les rayures et séparateurs, l’aide locale, le disclosure,
la répartition à huit colonnes et l’ordre drag/clavier. La dernière assertion
de formulaire modal (contrôle caché de compatibilité, sans dropdown) reste un
contrôle distinct. Les captures CDP K.3 attendent la revue visuelle humaine
avant de déclarer le sous-lot accepté.

Retours P1 validés le 2026-08-04, inclus dans K.3 sans élargissement de lot :

- superposer géométriquement l'icône Image au repos et la poignée `::` au
  survol/focus, avec une boîte visuelle, un centrage et un poids identiques ;
- rendre la largeur du rail gauche strictement constante entre repos, focus,
  drag et types verrouillés, tout en gardant seulement le séparateur horizontal
  léger entre les lignes ;
- remplacer l'aide locale de l'accordéon par le composant d'aide déjà présent
  dans l'administration Slideshow, avec spacing, curseur et surface de bulle
  cohérents ; la généralisation Kit reste un handoff P2 séparé ;
- aérer les paires libellé/valeur dans `Layer infos & overrides` sans cacher de
  métadonnée ;
- recalculer explicitement les huit colonnes Sources après retrait de `fit`, en
  réservant 20 % à Actions et en conservant le repli responsive.

Retours P1 de revue visuelle du 2026-08-04, testés et en attente de revue humaine dans K.3 :

- corriger le centrage optique de l’icône Image sans déplacer la boîte commune
  de l’icône et de la poignée `::`, puis rendre leur transition croisée plus
  lisible tout en respectant `prefers-reduced-motion` — appliqué : chaque
  élément Font Awesome est contraint à la boîte centrée de 1rem ;
- conserver les rayures Border légères et donner aux rayures Overlay une teinte
  vert pétrole identifiable, sans peindre l’échantillon d’Overlay lui-même —
  appliqué : rail `#2f7d73`, motif translucide à 8 % ;
- garder le `?` dans l’en-tête de `Layer infos & overrides`, le séparer du
  titre et le rendre disponible même lorsque le disclosure est replié. Sa
  surface provient du Kit embarqué ; le centrage de ce court texte reste une
  adaptation CCB temporaire jusqu’au contrat Kit de variante dédiée — appliqué
  via un sibling `details`, sans imbrication interactive dans le `summary`.
  Le sous-lot consomme désormais aussi le corps, le padding et la graisse de
  la popover Slideshow, avec flèche pointant vers le trigger ;
- aligner chaque libellé de métadonnée à gauche et sa valeur à droite ;
- supprimer aussi la ligne éditable `Image sizing mode` de l’accordéon de la
  source sélectionnée. La valeur stockée et le champ caché de soumission sont
  préservés — validé humainement le 2026-08-04.
- Porter le `?` de `Layer infos & overrides` dans le popover CCB attaché à
  `document.body`, car un descendant de `.table-responsive` ne peut pas sortir
  de son clipping horizontal. Appliqué : le même contrat compact-centré est
  aussi utilisé par le `?` `Sort order` du header. Le scénario K.3 Moodle 5.1
  a vérifié les deux portails réels, leurs flèches, leur texte centré, à 100 %
  et 200 % natif, avec nettoyage complet :
  `%LOCALAPPDATA%\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260804T151431227Z-23876`.

L’animation d’ouverture/fermeture de cet accordéon est planifiée dans le lot
P2 d’accordéons partagés : elle ne doit pas être ajoutée isolément ici avant
l’adoption de la primitive Kit correspondante.

**Clôture locale K.3 — 2026-08-05.** La dernière assertion de formulaire modal
est désormais couverte par le même scénario unique : aucun
`select#id_fitmodeoverride` n’est rendu et le champ de compatibilité éventuel
reste caché. Le run supervisé a sélectionné exactement un test, passé à 100 %
et 200 % natif, puis supprimé catégorie, éléments temporaires et profils. La
revue visuelle des corrections K.3 a été acceptée dans cette fenêtre. Evidence
externe :
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260805T051114204Z-37756`.
Le batch canonique `EED-CCB-2026-0001` reste à réconcilier par son owner
Platform ; cette clôture locale ne modifie ni son statut ni son index.

#### P2 — contrat partagé Kit pour les aides contextuelles

- Propriétaire proposé : UI Kit ; consommateurs ultérieurs : CCB, EasyStud et
  toute administration Moodle EasyEdu. Le Kit ne fournit aujourd’hui que les
  mixins SCSS `help-tooltip` et `popover-surface`, pas un contrat complet de
  markup, ARIA, déclenchement et variante de contenu.
- Définir une primitive documentée (markup Mustache/ARIA, surface, flèche,
  focus/clavier, `pointer`, overflow et variantes de texte court centré ou de
  texte long lisible). Les consommateurs ne doivent plus recopier la surface
  ou le padding par élément.
- Conserver localement le seul placement contextuel d’un trigger dans un
  en-tête CCB : la structure de page, l’ordre clavier et le positionnement
  d’un header restent la responsabilité du consommateur. L’animation de
  disclosure reste le lot P2 d’accordéons partagé.

Le scénario unique Moodle 5.1 a repassé ces retours à 100 % et 200 % natif le
2026-08-04, avec nettoyage de fixture et de profils. Les preuves courantes sont
`%LOCALAPPDATA%\EasyEdu\artifacts\ccb\layer-object-row\supervised\ccb-layer-object-row-20260804T151431227Z-23876`.
La revue humaine reste le gate d’acceptation : elle doit confirmer le mouvement,
le centrage optique, les couleurs, la disponibilité de la bulle repliée, son
corps Slideshow non gras, son padding et sa flèche, ainsi que la disparition de
l’éditeur de taille visible.

#### P2 — surface homogène de la cellule `Layer infos & overrides`

- Retirer le fond propre à la cellule `Layer infos & overrides` sur les lignes
  Image réellement déplaçables : elle doit reprendre exactement la surface de
  ligne, y compris hover/focus et reduced-motion. Préserver l’accent propre aux
  lignes Border/Overlay verrouillées, les rayures et les états de disclosure.
- Mesurer à 100 % et 200 % les contrastes, séparateurs et focus avant/après ;
  aucune règle ne doit recolorer une autre cellule, l’aperçu Banner layer ou
  les lignes héritées.

#### P2 — interaction native de déplacement de lignes — proposition de lot UI Kit puis adoption CCB

- Propriétaire proposé : UI Kit pour la primitive, puis CCB UI Sources/Layers
  pour l’adaptation AMD et la validation Moodle 5.1. Dépendance : handoff
  explicite d’un owner UI Kit ; ce lot K.3 ne modifie pas le Kit.
- Étendre `object-row-cells` par un contrat opt-in de déplacement natif :
  surface de ligne déplacée cohérente avec les cartes EasyStud, emplacement
  source visuellement réservé et indicateur d’insertion entre deux lignes
  seulement quand le dépôt est autorisé.
- Préserver le flux réel d’un `<tr>`, l’ordre DOM, les contrôles clavier, les
  types verrouillés, `table-layout`, les dimensions de cellule et le fallback
  sans drag. Ne pas maquiller un placeholder par une transformation CSS locale.
- Valider à 100 % et 200 % natif, reduced motion, forced colors, source,
  destination, annulation, drag vers une ligne verrouillée et absence de débordement.

### Lot L — transitions CCB et préférence d'accessibilité

- Inventorier les états visuels propres à l'administration CCB (aperçus,
  boutons, panels, accordéons, modales, listes, drag/drop et chargements) avant
  d'ajouter des transitions. Chaque mouvement doit être décoratif, court et
  ne jamais être nécessaire pour comprendre une action.
- Harmoniser les transitions réellement utiles, en priorité le passage Desktop
  authoring / Mobile public simulation, la disclosure et les changements d'état
  de sélection. Respecter `prefers-reduced-motion`, forced colors, le focus et
  les opérations de drag.
- Ajouter dans les réglages d'administration du plugin une préférence CCB
  explicitement nommée pour désactiver les animations d'administration. Elle
  doit désactiver les animations et transitions CCB sans toucher aux animations
  Moodle, EasyStud, UI Kit ou rendu public, ni imposer une nouvelle préférence
  utilisateur persistante.
- Documenter la portée, les valeurs par défaut, la compatibilité Moodle 4.5 et
  les contrôles clavier. Ce lot demande une revue séparée des APIs de réglages
  Moodle et un scénario ciblé ; il ne doit pas être glissé dans un correctif CSS.

### Backlog de planification — demandes consolidées le 2026-08-03

Les éléments suivants sont conservés sans élargir `EED-CCB-2026-0001`. Les
identifiants des futurs batches EED seront alloués par l’owner Platform au
moment de leur ouverture.

#### P2 — preview, actions et boutons (Lots A/B)

- Utiliser pour les boutons latéraux du preview la hauteur compacte jugée
  acceptable en Mobile authoring, puis la garder identique en Desktop
  authoring et Mobile public simulation. Le rail d’actions doit remplir toute
  la largeur disponible entre cadre preview et bord droit, au lieu d’être
  collé au bord.
- Uniformiser Save, Delete, Delete selected layer, Delete all layers et Save
  layer changes : même hauteur, même alignement, rouge destructif plein pour
  toute suppression, et même élévation légère au survol que les actions sous
  le preview. Adapter le retour à la ligne et le responsive sans réduire les
  cibles tactiles.
- Revoir le bouton `Source settings`, le titre de source sélectionnée et les
  actions de source afin qu’ils consomment des rôles de bouton et de titre
  cohérents avec le Kit embarqué.

#### P2 — aides, curseurs et dropdowns (Lots C/H, handoff Kit requis)

- Faire de l’aide `?` de l’administration Slideshow la référence CCB : forme,
  padding, bulle, flèche, focus, nom accessible, curseur `pointer` et fermeture
  clavier. Auditer toutes les aides, miniatures et affordances de popover CCB
  afin d’éliminer les curseurs non cohérents.
- Proposer au UI Kit la primitive réutilisable correspondante avant toute
  standardisation inter-projets ; ne modifier ni le Kit canonique ni EasyStud
  dans un lot CCB.
- Auditer tous les dropdowns CCB, notamment `Source composition mode` et les
  sources parentes : trigger, liste d’options, option sélectionnée, textes
  longs, scroll, responsive et clavier. Le style de liste doit provenir d’un
  contrat Kit validé, pas d’une copie locale d’EasyStud.

#### P2 — modales, chargements et contrôles de calque (Lots E/G)

- Dans toutes les modales Image, Border, Overlay et titre, faire déplier les
  accords du panneau droit sur leur hauteur réelle, avec chevrons alignés,
  focus correct et aucune option cachée.
- Harmoniser tous les contrôles `Enable layer` avec le rôle, la couleur et les
  états employés par l’administration Slideshow, après inventaire des contrats
  de formulaire et des états désactivés.
- Étendre le chargement non bloquant déjà validé aux actions dynamiques et aux
  modales qui n’ont pas encore de surface de chargement cohérente. L’adoption
  du composant Loading/Skeleton EasyStud doit d’abord être proposée et
  documentée dans le UI Kit ; elle est exclue du batch Sources/Layers.

#### P2 — structure de page, titres et accordéons (Lots G/H)

- Harmoniser `Course banner options`, `Selected source` et `Configured
  sources` : même hauteur d’en-tête, même surface, chevron à gauche,
  `aria-expanded`, transition et contenu complet.
- Standardiser les intertitres `Content` / `Control` avec leur point d’accent
  lorsque leur sémantique est applicable, via une proposition Kit distincte.
- Auditer les titres de page CCB afin d’adopter, lorsque pertinent, le modèle
  EasyStud Mass Import : nom du produit bleu, puis titre de vue et description.
  La typographie et le composant partagé relèvent d’un handoff Kit.

#### P2 — interaction de source et héritage (batch CCB distinct)

- Remplacer l’édition directe de la source d’héritage par une modale dédiée,
  avec dropdown de source conforme au contrat Kit. Préserver sélection de
  source, composition, capacité, sesskey, chaînes d’héritage et annulation.
- Cette évolution modifie le flux utilisateur et les contrôles Moodle ; elle
  requiert un audit PHP/AMD/Modal, chaînes localisées et un scénario ciblé. Elle
  ne fait pas partie du sous-lot K.

#### P2 — transitions et accessibilité (Lot L)

- Oui : le plan comprend déjà des transitions d’ouverture/fermeture et de
  changement d’état sur tous les éléments compatibles. L’inventaire couvrira
  preview Desktop/Mobile, boutons, panneaux, accordéons, modales, listes,
  drag/drop et chargements.
- Les transitions resteront courtes, décoratives et optionnelles ; aucune ne
  doit piloter une information, déplacer le focus, gêner le drag ou ignorer
  `prefers-reduced-motion`/forced colors. Une préférence d’administration CCB
  désactivera ces animations sans toucher à Moodle, EasyStud, UI Kit ou public.

#### P3 — réduction des rechargements de page et notifications

- Inventorier toutes les mutations CCB déclenchant actuellement une navigation
  complète. Classer chaque action : conserver le rechargement lorsqu’il protège
  une transition majeure ; sinon proposer un flux AMD/AJAX avec capability,
  sesskey, rollback, toast accessible et rafraîchissement local déterministe.
- Remplacer progressivement les alertes navigateur par des confirmations et
  notifications CCB accessibles, sans masquer une erreur serveur ni casser les
  actions destructives. Ce programme transversal nécessite un batch séparé,
  une revue sécurité Moodle et une matrice de régression étendue.

### Séquence de validation commune

Pour chaque lot implémenté : lint/build des sources concernées, `git diff
--check`, découverte Playwright avec exactement un test, lease Moodle exclusif,
profil Chromium isolé, captures CDP externes et restauration de fixture dans
`finally`. La matrice couvre 1600x900, 1024x768, 390x844, 1600x900 à 200 % et
390x844 à 200 %. Les preuves doivent inclure débordement, superposition,
focus, ordre clavier, erreurs console/requêtes et nettoyage.

Les lots sont séquentiels : A/B avant C, D/E après l'audit des hooks, G/H
avant I, puis J après reproduction du bug crop ; F reste conditionné à la
comparaison Kit/EasyStud. Aucun lot ne lance 2F-B, 2A/2B, PHPUnit, GroupImport
ou une opération EasyStud. Les menus Moodle en arrière-plan restent des
anomalies à transmettre au chantier UI global.

### Narrow portrait orientation hint - 2026-07-30

Approved CCB-only UX addition for Moodle 5.1 administration editing surfaces.
When a `[data-source-visual-editor="1"]` editing root is present at a CSS
viewport width of 576px or less in portrait orientation, the root may show a
localized, dismissible hint suggesting landscape orientation. The hint is
non-blocking, keyboard accessible and scoped out of public pages, landscape
views, wide views and read-only source-chain previews. It uses CSS
`matchMedia`, not the Screen Orientation API, and does not change banner
geometry, public rendering, ratios or the 128px policy.

Implementation files are `admin_manage.php`, `amd/src/admin_manage.js`,
`scss/components/_admin-layout.scss`, the English/French language files, the
generated CSS/AMD assets, one targeted Playwright specification and the CCB
validation documentation. Runtime validation remains a single supervised
scenario with 100%/200% portrait, landscape, wide and public-page assertions.

The supervised scenario passed on 2026-07-30 after a lease-protected local
Moodle language-cache refresh. The final evidence is external to Git, with
visible 100% and 200% portrait CDP captures pinned by its artifact manifest;
the full five-cell responsive gate remains a separate pending batch.

### Narrow source-preview correction - 2026-07-29

The CCB-owned source-preview surface now uses a centered viewport-width
treatment below 576 CSS pixels when the Moodle admin column becomes narrower
than the effective viewport at genuine 200% zoom. The server-provided banner
ratio remains authoritative; this rule changes only the administration surface
width. The isolated 390x844/200% cell passed after the correction with root
width 148.2 CSS px (previously 132.8), desktop frame 106.6 x 17.5 CSS px and
mobile frame 106.6 x 35.0 CSS px, with no overflow, overlap, console error or
failed request. Evidence is retained under
`%LOCALAPPDATA%\\EasyEdu\\artifacts\\ccb\\responsive\\supervised\\ccb-source-preview-mobile-200-fixture-20260729T150737792Z`.
The visual gate remains limited to this CCB surface; the full five-cell Gate 2
matrix and downstream batches are not advanced by this single-cell result.

Status: `GATE_2_OVERLAP_DETECTED`

Owner: CCB DevOps/QA handoff on the active branch. This file is the working
plan for the next UI harmonisation batches; it is not a product implementation
and it must not be treated as permission to edit EasyStud, the EasyEdu UI Kit,
or Moodle/Boost.

## Authoritative execution context

- Machine: `PORT4719PG3`
- CCB checkout: `C:\dev\Moodle 51\MoodleWindowsInstaller-latest-501\server\moodle\local\course_banner_builder`
- Branch: `wip/desktop-k1gsrvt/ccb-continuity-2026-07`
- WIP consolidation checkpoint: `0a3c7a041320a4fff0a83d980aedb877415dde7a`
  (`WIP(ccb): checkpoint concurrent UI and QA work`).
- Upstream: `origin/wip/desktop-k1gsrvt/ccb-continuity-2026-07`, synchronized
  at `0/0` with the WIP checkpoint.
- Worktree: clean at handoff; no staged, unstaged or untracked changes remain.
  The consolidation commit is the new point of reprise and must be preserved.
- Stashes: three preserved entries; never reset, clean, stash, checkout,
  rebase, unstage, or rewrite them.

### 2026-08-06 WIP consolidation checkpoint

The concurrent CCB UI and QA material was consolidated in the WIP commit
`0a3c7a041320a4fff0a83d980aedb877415dde7a` on the active branch above. This
checkpoint is synchronized with its upstream and is the only valid restart
point for the next CCB lot. Do not return to the historical feature branches
or to `c5f33c8` without an explicit instruction. The future CCB Loading/Skeleton
consumer remains a separate scope and is not implemented by this checkpoint.

The active runtime, Moodle database, QA fixtures, Playwright credentials, and
external browser artifacts remain outside this source/build phase.

## Implementation log â€” 2026-07-30

The first bounded CCB-owned UI lot is now implemented in the dirty checkout:
source dropdown labels use an explicit wrapping slot, the selected-source
holder has a native sticky fallback, source-table corners are rounded without
clipping menus, and the three modal layer accordions share one animated,
keyboard-addressable disclosure contract. No EasyStud, UI Kit, fixture,
database, runtime or public banner rendering files were changed. Browser
validation remains pending for the dedicated CCB scenario after static checks.

## Gate 2 protected-hash checkpoint

Captured after the source patch and CSS rebuild (2026-07-28):

| File | SHA-256 | Expected change |
| --- | --- | --- |
| `classes/hook_callbacks.php` | `C158A6C9790CE55EE14F1C463334709B053C3180F83B40D46C369FD1990F41AF` | unchanged |
| `styles.css` | `7E16AD6F182C4B67E4AE2F770B1F63A5635F00A528D15E3B30928E2338964492` | intentional generated CSS update |
| `tools/playwright/ccb-banner-public-title-accessibility-2fa.spec.js` | `39625ED3A113E36505D7C66440C02F343A5728CC11FB04FBE9B0C17AED2D3E63` | unchanged |
| `tools/playwright/playwright.config.js` | `39A138E7264AAF7CD84C2967F2E2BD9CC5258F58A4CD40797287DAFD29889FC` | unchanged |
| active Moodle `config.php` | `A3D6D54451165CE04BAFF8D840C18C3300F70BEB6C5D79FB5AD55D284AA56CA9` | unchanged |

The pre-patch `styles.css` checkpoint was `79A7D498794FCAEC74DE575B9E47FA4596AB5F873E7C577F5C597AC1BF6516B7`; that difference predates this step's source edit and is now superseded by the intentional rebuild.

## Why this plan exists

The next UI work must be repeatable instead of making one-off placement
decisions per screen. The plan covers the CCB admin preview family first, then
the EasyStud and shared UI Kit handoffs. It incorporates the following newly
reported issues:

1. Switching desktop/mobile in the general preview must not resize, reset, or
   otherwise interfere with the layer-selection filmstrip or its action icons.
2. The preview action panel must use the width available in its layout area,
   rather than being visually glued to the far right, and its buttons must
   regain the readable preferred height.
3. Action icons under general previews, modal previews, and slideshow previews
   must use one visual and interaction contract in every state.
4. Source-list action icons and labels must share one left-aligned layout. New
   button placement decisions must follow a documented contract rather than
   ad-hoc local CSS.

## Baseline audit findings (read-only, 2026-07-28)

### CCB ownership

- `amd/src/admin_manage.js`: `localCourseBannerBuilderSetSourcePreviewMode`
  only changes the root mode attribute, the transient per-source mode map, and
  the mode buttons. It does not intentionally mutate filmstrip selection or
  action state.
- `admin_manage.php`: the source editor renders mode control, preview surface,
  visibility row, filmstrip, and primary actions as siblings inside the canvas
  wrapper. This is the correct structural boundary to preserve.
- `amd/src/admin_manage.js`: filmstrip thumbnails are regenerated separately,
  but the visibility control is currently a focusable `span[role=button]` nested
  inside a thumbnail `button`. This is an invalid nested interactive pattern
  and is an accessibility/double-action audit item for the implementation lot.
- `localCourseBannerBuilderSetActionButtonContent` creates a hard-coded
  `fa` + `me-2` icon/label pair. Static PHP buttons and modal/slideshow
  variants do not all share the same wrapper or size rules.
- `scss/components/_modal-preview-actions.scss`: modal side action lists use a
  fixed `18rem` absolute rail and `2.15rem` minimum button height. This is the
  primary candidate for the glued/short action-panel report.
- `scss/components/_slideshow-admin.scss`: slideshow side actions repeat the
  fixed `18rem` absolute rail and inherit the modal action-list class.
- `scss/components/_admin-controls.scss` and `_preview-editor.scss`: source
  table action lists are full-width grids with centred content and small
  padding; icon/label alignment needs one explicit contract.
- Mobile preview CSS currently narrows the filmstrip and visibility row to the
  logical mobile width. The mode switch itself is state-safe, but this shared
  width rule is a layout-coupling risk and must be isolated in the next audit.

### Gate 1 selector/state matrix (drafted)

| Surface | Structural selectors | State that must survive layout changes | Main risk |
| --- | --- | --- | --- |
| General source preview | `.local-course-banner-builder-source-preview-panel`, `.source-preview-mode-control`, `.source-preview-surface`, `[data-source-preview-frame]` | `data-source-preview-mode`, `aria-pressed` on mode buttons, selected layer, preview geometry | Mode width rules can resize sibling controls or clip the canvas |
| Visibility and filmstrip | `.source-preview-visibility-toggle-row`, `[data-source-preview-filmstrip]`, `[data-source-preview-filmstrip-track]`, `.source-preview-thumbnail`, filmstrip nav actions | thumbnail selection, hidden/visible state, scroll position, nav disabled state, focus | Nested interactive visibility control and mobile-width coupling |
| General preview actions | `.source-preview-primary-actions`, `.source-preview-controls`, `.source-preview-button` | pressed/expanded/disabled states, icon/label content, keyboard order | Mixed widths and context-specific icon/label alignment |
| Modal preview | `.modal-preview-action-list`, `.modal-preview-icon-row`, `.layer-modal-body`, `form[data-preview...]` | modal focus return, sticky row, action visibility, crop/undo/redo state | Fixed absolute `18rem` rail and short button minimum height |
| Slideshow preview | `.slideshow-side-actions.modal-preview-action-list`, `.slideshow-side-panel`, slideshow toolbar buttons | active/expanded action, scroll, slide selection, reduced motion | Reused modal rail rules and independent overflow context |
| Configured source list | `.configured-sources-table`, `.source-actions-cell`, `.action-list`, inline edit controls | current row, chain expansion, form submit/cancel, action labels | Full-width grid plus centred content causes icon/label drift |

For every row, the implementation test must assert both semantic state and
geometry before/after desktop/mobile switching. A visual pass alone is not
enough to prove that the filmstrip or delegated actions were not reset.

### External ownership and dependencies

| Area | Current owner | Current boundary |
| --- | --- | --- |
| CCB admin preview, source list, modal/slideshow consumers | CCB checkout above | In scope for this plan and first implementation lot |
| EasyEdu UI Kit button/icon mixins | `C:\dev\easyedu-ui-kit`, branch `feature/easyedu-visual-parity-2026-07`, HEAD `1819371b140cfc9cb8a79b70a702bace0baaac69` | Read-only dependency; dirty worktree; no edit without separate handoff |
| EasyStud plugin | `C:\dev\Professional-PhaseB-2026-07\local_groupimport`, branch `feature/easystud-skeleton-v3-motion`, HEAD `04240e81432e1f9151f5d7fb71050c1f0e8b6303` | Read-only parallel workstream; no edit in CCB lot |
| Moodle/Boost buttons, modal, dropdown, action-menu styles | Active Moodle runtime checkout | Shared platform behavior; report defects to the global UI workstream |

No CCB change may silently promote a local fix into the UI Kit or Moodle
theme. Each candidate is classified as: CCB-only anomaly, reusable pattern
candidate, UI Kit change request, or Moodle/global defect.

## Non-negotiable contracts

### Preview-mode isolation

- Desktop/mobile selection changes only the transient preview mode and its
  selected control state.
- The filmstrip, visibility row, selected layer, action icons, and keyboard
  focus remain stable unless the user explicitly invokes their action.
- Filmstrip and visibility widths are controlled by their own wrapper contract;
  they must not inherit an accidental width from the banner frame.
- No preview-mode change may write payload, form fields, local storage, or
  server state.

### Action placement and icon contract

Every CCB preview/source action must declare:

1. semantic role (`primary`, `secondary`, `destructive`, `toggle`, `icon-only`,
   or `navigation`);
2. placement zone (toolbar, under-preview row, side panel, modal footer, or
   source-table action cell);
3. icon slot and accessible name (visible label or `aria-label`);
4. state attributes (`aria-pressed`, `aria-expanded`, `disabled`, or
   `aria-disabled`) where applicable;
5. size token and wrapping policy; and
6. keyboard order and focus-return rule.

The implementation should centralise the icon slot, gap, line-height,
vertical centring, focus ring, hover/active/disabled states, and reduced-motion
behavior. Existing `data-action`, modal targets, guide targets, and AMD event
contracts remain unchanged.

### Responsive and zoom contract

The CCB matrix is mandatory for each affected surface:

- 1600x900 at 100%;
- 1024x768 at 100%;
- 390x844 at 100%;
- 1600x900 at native 200%; and
- 390x844 at native 200%.

At every cell: no horizontal document/preview overflow, no overlap, no
floating action, readable labels, consistent icon alignment, complete focus
visibility, and deterministic tab order. At 200%, use an isolated Chromium
profile and external CDP captures only; never capture the desktop.

### Accessibility contract

- No nested interactive controls. The filmstrip visibility affordance must be
  restructured before claiming the keyboard audit complete.
- Every icon-only control has an accessible name; decorative icons are hidden
  from assistive technology.
- Focus is visible at 100% and 200%; modal open/close/Escape returns focus to
  the invoking control.
- Toggle/accordion state is exposed with the correct ARIA attribute and does
  not double-fire through a parent control.
- The tab sequence follows visual/task order in general preview, filmstrip,
  source list, modal action panel, and slideshow actions.
- Respect `prefers-reduced-motion`; no feedback animation is required for
  understanding the state.

## Ordered workstream

### Gate 0 - baseline and ownership (current, complete)

Read-only inventory of machine, checkout, branch, HEAD, upstream, dirty state,
stashes, CCB files, UI Kit, EasyStud, and Moodle/Boost dependencies. Record
findings before any product edit. Do not rerun already validated 2A.1,
2F-A.1, or 2F-B.1.

### Gate 1 - CCB structural audit (next)

Inspect all general-preview, source-chain, modal-preview, title-preview, and
slideshow action markup. Produce a selector/state matrix and classify each
action by the contract above. Identify every placement rule that depends on
`position:absolute`, fixed width, `justify-content:flex-end`, or a context-only
icon override. No UI Kit/EasyStud/Moodle edits.

### Gate 2 - CCB implementation lot A: preview shell and mode isolation

- Keep the visible preview surface and its wrapper.
- Separate mode control, canvas, visibility row, filmstrip, and primary actions
  into stable layout regions.
- Make side action panels consume available grid width and reflow predictably.
- Restore the preferred button height through one CCB token/contract, verified
  against desktop, compact, and 200% layouts.
- Preserve geometry, 128px policy, H1/H2 semantics, AMD wiring, and all
  `data-action` attributes.

### Gate 3 - CCB implementation lot B: action/icon parity

- Apply one icon slot and label slot to general preview, modal preview,
  slideshow, filmstrip navigation, and source-table actions.
- Align source-list icons and labels to the leading edge while keeping action
  groups visually grouped and responsive.
- Harmonise hover, focus, active, pressed, expanded, disabled, and destructive
  states without changing semantics.
- Replace nested interactive filmstrip visibility markup with an accessible
  sibling/compound pattern and update only the CCB delegated event handling
  needed to preserve behavior.

### Gate 4 - verification and evidence

Before any fixture or Moodle mutation:

1. Acquire the exclusive EasyEdu lease.
2. Run `playwright test --list` with the isolated config and verify exactly one
   selected test.
3. Use process-local runtime variables only; confirm they are absent outside
   the test process.
4. Run targeted CCB tests for each affected surface, one worker, one profile.
5. Capture external CDP screenshots/geometry/accessibility evidence and record
   console errors, request failures, overflow, overlap, focus order, and
   cleanup status in a manifest.
6. Restore any temporary fixture in `finally`; do not remove unmanifested
   artifacts.

Static checks for an implementation lot: PHP lint for touched PHP, AMD syntax
when JS changes, SCSS/CSS build when SCSS changes, and `git diff --check`.
No build or browser run is part of this documentation-only turn.

### Gate 5 - handoffs

- CCB-only fixes stay in CCB.
- Reusable icon/action primitives are proposed to the UI Kit owner with a
  selector contract, before/after captures, and no automatic copy.
- EasyStud receives a separate audit prompt and ownership lease; its dirty
  worktree is not touched by CCB.
- Moodle/Boost menu or background overflow issues are reported as global UI
  work, with exact selectors and reproduction dimensions, not patched locally.

### Gate 6 - downstream batches

Only after the CCB UI gates and evidence are green: continue the authorized
2A/2B path. PHPUnit remains a parallel infrastructure track and does not gate
the Playwright UI work unless a shared contract is demonstrably affected.

## Acceptance checklist

- No horizontal overflow or overlap at all five responsive/zoom cells.
- Mode switching leaves filmstrip, selected layer, action icons, and focus
  stable.
- Action panels use available width; no right-glued orphan column.
- Button heights, icon slots, label baselines, and spacing are consistent.
- Source-list actions are left-aligned and grouped; long translations wrap
  without floating icons.
- Keyboard navigation is complete and deterministic; no nested interactive
  controls remain.
- Geometry, 128px policy, H1/H2 semantics, AMD/data-action contracts, and
  existing dirty handoff remain intact.
- `git diff --check`, targeted tests, and required builds are recorded for the
  implementation lot.
- Documentation, changelog, batch history, AI contract decision, evidence,
  rollback, and next handoff are recorded.

## Rollback and stop conditions

Do not reset or discard the dirty worktree. Before implementation, capture
read-only hashes for protected files. During implementation stop immediately
on an unexpected conflict, concurrent writer, protected hash divergence,
fixture/lease failure, or a change outside the declared CCB file set. Rollback
is by a reviewed inverse patch or handoff snapshot, never by `git reset`,
`clean`, or broad file deletion.

## Current next action

Gate 1's selector/state matrix is captured and the first Gate 2 source patch is
prepared in `_action-contract.scss`, `scss/styles.scss`, and
`templates/admin_manage.mustache`; `styles.css` was rebuilt from that source.
The controlled Gate 2 browser run selected exactly one test and executed all
five cells sequentially, but two 100% cells failed during Moodle navigation
before any CCB geometry assertion (desktop login/navigation and tablet
`page.goto`). The three remaining cells, including both native-200% cells,
passed. The fixture, category, course format, profiles, lease and runtime
variables were restored/cleared. Read-only Apache correlation now shows valid
200/303 responses arriving after the protected scenario's 20–30 second
navigation budgets; the local PHP log also contains existing CCB/Moodle
warnings but no observed fatal response. The gate remains blocked pending a
runtime remediation decision and a later authorized rerun; do not advance to
downstream UI batches from this evidence alone.

Latest evidence: external artifact run
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260728T125908577Z-22268`
(`playwright.discovery.txt` reports exactly one test; `runner-result.json`
reports 3/5 pass; `cleanup.json` reports `complete=true`). A preceding
pre-mutation attempt stopped because the EasyStud writer held the shared lease:
`ccb-source-preview-responsive-20260728T125737520Z-21356`.

Read-only runtime evidence is the local Apache access/error log window around
the latest run. It is not copied into Git or QA artifacts because it contains
machine/runtime request data.

### Gate 2 overlap update — 2026-07-29

The latest one-test, five-cell run is retained at
`C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\responsive\supervised\ccb-source-preview-responsive-20260729T092709891Z-12348`.
Four cells passed (all 100% cells and mobile native 200%); desktop native 200%
failed before CCB geometry assertions because `/my/` returned HTTP 500 and
`page.goto` exceeded 20 seconds. Apache/PHP recorded concurrent GroupImport
requests and repeated cache-store rename `Access denied` warnings. The final
lease audit showed live EasyStud and CCB runtime owners at the same time.
This supersedes the latency-only diagnosis: the gate is blocked on global
Moodle runtime lease exclusivity. No further rerun, timeout change or cache
mutation is permitted until both owners release and the orchestration decision
is recorded.

### Lot E.1 modal rail and disclosures - complete 2026-07-31

- The Image, Border and Overlay action rails share their reserved body width.
  On small widths they return to normal flow, so labels remain readable and
  the modal does not overlap the preview or form.
- The existing side-panel button remains the accessible disclosure controller;
  its AMD and ARIA contracts are unchanged. The visible state chevron now
  precedes the type icon and label for all three modal disclosures.
- One supervised test selected exactly one scenario and passed the five
  100%/200% cells for all three modals with external CDP evidence, no overflow,
  no console/request errors, and complete cleanup:
  `C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\modal-action-rail\supervised\ccb-layer-modal-action-rail-20260731T151208176Z-26896`.
- Remaining Lot E work is limited to diagnosing the hidden top border of the
  image-add dropdown and modal surfaces that do not use this action rail.

### Lot J.1 crop cancellation persistence - complete 2026-08-01

- The add-image modal now restores the canonical Moodle crop inputs and the
  `previewcropstate` payload from the restored draft layer after action-rail
  Undo. This covers the user-reported flow: apply a changed crop, Undo, then
  save. Direct in-editor Cancel remains a distinct, already-covered path.
- The one-test supervised Moodle 5.1 run proved the restored `false, 0, 0,
  100, 100` state in the active preview, visual draft, canonical form inputs,
  `multilayerdraftsettings` and submitted crop payload, then proved that no
  crop-enabled layer persisted after Moodle save. The run ID is retained by
  the external artifact manifest; no browser artifact is versioned.

### Lot E.2 layer-modal loading centering - complete 2026-08-01

- Only the temporary `data-layer-modal-loading="1"` state changes: the body
  fills the available modal surface, does not reserve an absent action rail,
  and centres its non-interactive status indicator without moving the loaded
  form or action rail.
- The one-test Moodle 5.1 modal scenario delayed an actual Overlay-modal
  response, recorded a 0px horizontal and 1.59px vertical spinner offset, and
  then passed all Image/Border/Overlay 100% and native-200% cells. It retained
  no console/request error, fixture, profile or lease residue. Evidence:
  `C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\modal-action-rail\supervised\ccb-layer-modal-action-rail-20260801T120821393Z-31188`.
- The next isolated modal concern remains the hidden top border of the
  add-image source dropdown; it requires a separate selector and behaviour
  audit before any source change.

### Lot E.3 modal side-panel boundaries - complete 2026-08-01

- The visual defect reported as a hidden dropdown border was the action-rail
  side-panel boundary, not a native Moodle select. The CCB embedded Kit
  contract now keeps the expanded trigger rounded and draws the panel's full
  top border instead of joining both surfaces with a negative overlap.
- The existing one-test matrix asserts the actual Image, Border and Overlay
  panels have a solid top edge and a positive trigger-to-panel gap at every
  100%/native-200% cell. Final evidence reports 1px and 2.875px respectively,
  with no console/request failure or fixture/profile/lease residue:
  `C:\Users\kj220291\AppData\Local\EasyEdu\artifacts\ccb\modal-action-rail\supervised\ccb-layer-modal-action-rail-20260801T121945530Z-37688`.
- A genuine native dropdown in the image upload/file-manager UI was not
  changed by this lot. It remains a separate, evidence-led audit if a distinct
  defect is reproduced.
