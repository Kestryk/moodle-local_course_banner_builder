# CCB codechecker — lot 1, 18/10/2026

Base 872ea1ab, branche dédiée depuis preview/moodle51/ccb-wave20, copie principale laissée propre. Dix-sept PHP : espaces, indentation et présentation des appels ; seules huit familles de sniffs de formatage appliquées. Chaque fichier a la même suite de jetons PHP exécutables et passe php -l. Aucun JS, gabarit, SCSS, rendu ou logique modifié ; ESLint n’augmente pas car toutes les sources JS sont identiques. Aucun build ni runtime.

MoodleCS : 859 erreurs/28 avertissements → 117 erreurs/27 avertissements. Restent docblocks, boilerplates, noms de variables, classes de test, gardes et trois virgules finales : à classer avant correction, aucun correcteur global.

Correction du périmètre de la pré-passe : les deux erreurs non-PHP et trois avertissements supplémentaires provenaient exclusivement des licences et sorties Playwright **dans node_modules**. Ne pas modifier les notices de tiers pour satisfaire le checker. L’objectif produit est donc 859 erreurs PHP initiales, non 861 erreurs de code appartenant au plugin. Les fichiers suivis du worktree dédié n’ont pas ces dépendances installées ; checker autres fichiers à rejouer séparément.

Preuves : C:/dev/artifacts/k16-17-mechanical-20261018/ccb-format.json, ccb-parity1.json, ccb-after1.json. Les sniffs, fichiers, avant/après sont archivés. Aucune API, préférence, chaîne ou propriété publique renommée. Aucun nouveau contrat IA : les règles existantes de parité couvrent ce formatage. Revenir au parent annule ce lot ; aucun état runtime à restaurer. Aucun changement inter-dépôt de composant.
