# CCB codechecker — lot 2, 18/10/2026

Branche work/codex1/ccb-codechecker-2-20261018, au-dessus du lot 1 `1a526f4`. Dix-huit PHP modifiés : métadonnées et descriptions, blancs, quatre noms de variables locales de fixtures (sourcesettings, cleanupexception, capturedcourse, categoryexists), virgules finales de tableaux et repli de deux littéraux SVG. Aucune fonction publique, propriété, classe de test, gabarit, chaîne de langue ou API renommée. Aucun visuel ni JS touché ; ESLint sans augmentation car ses sources restent identiques.

MoodleCS complet : **117 erreurs/27 avertissements → 5 erreurs/27 avertissements**. Aucun sniff désactivé. Les fichiers tiers ne sont pas présents dans ce worktree dédié ; contrôle officiel des autres fichiers : 0 erreur/0 avertissement. L’objectif zéro erreur n’est pas encore atteint.

Chaque PHP touché passe php -l. Les jetons sont comparés au parent après normalisations explicitement bornées : commentaires et blancs ignorés ; quatre correspondances de noms de variables ; virgules finales devant fermeture de tableau ; valeur exacte des concaténations de littéraux constants. Les SVG et autres chaînes gardent leurs octets. Absence de compact/extract/get_defined_vars/variables variables et de clés chaînes correspondant aux noms renommés vérifiée dans les fichiers concernés. Aucune fixture Moodle ni PHPUnit native exécutée : elles peuvent écrire dans le runtime. Aucun build requis.

## Erreurs laissées à risque

Ces harnais CLI valident des arguments ou un chemin de configuration **avant** de charger Moodle. Le sniff demande une garde MOODLE_INTERNAL avant cet état global ; l’ajouter ferait mourir un processus CLI sain avant le bootstrap. Déplacer le bootstrap changerait les erreurs d’entrée et pourrait charger Moodle avant validation. Ne pas contourner le sniff par annotation : préparer une décision de structure des harnais et ses tests CLI. Aucun correctif de logique appliqué.

| Fichier:ligne | Règle | Statut |
|---|---|---|
| tools/playwright/ccb-crop-history-fixture.php:31 | moodle.Files.MoodleInternal.MoodleInternalGlobalState | à risque — bootstrap CLI |
| tools/playwright/ccb-image-modal-transform-fixture.php:28 | moodle.Files.MoodleInternal.MoodleInternalGlobalState | à risque — bootstrap CLI |
| tools/playwright/ccb-readable-banner-fixture.php:29 | moodle.Files.MoodleInternal.MoodleInternalGlobalState | à risque — bootstrap CLI |
| tools/playwright/ccb-slideshow-rendering-fixture.php:32 | moodle.Files.MoodleInternal.MoodleInternalGlobalState | à risque — bootstrap CLI |
| tools/playwright/ccb-wave-0042-0050-fixture.php:31 | moodle.Files.MoodleInternal.MoodleInternalGlobalState | à risque — bootstrap CLI |

Les avertissements restants comprennent les anciens noms des classes PHPUnit, des gardes jugées superflues et des lignes longues. Les classes de tests sont un contrat de découverte : les noms publics ne sont pas changés sans revalidation native. La documentation du lot 1 conserve les comptes avant/après. Preuves détaillées et décompte par fichier : C:/dev/artifacts/k16-17-mechanical-20261018/ccb-final.json, ccb-parity2.json, ccb-fixtures.json, ccb-docs.json, ccb-other-final.json.

La copie principale CCB preview/moodle51/ccb-wave20 reste inchangée à 872ea1ab. Aucun runtime, base, cache, navigateur, publication ou service touché. Les contrats IA existants couvrent cette passe de style ; aucune nouvelle règle ajoutée à AGENTS.md. Revenir au parent de ce lot suffit au rollback des fichiers.
