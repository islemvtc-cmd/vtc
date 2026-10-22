# ISLEM VTC — Design accueil V2 validé

- `index.php` : page d’accueil à colonnes inspirée de la capture approuvée : formulaire à gauche, Tesla à droite et cinq cartes suggestions.
- `assets/home.css`, `assets/home.js` : styles et interactions de l’accueil, sans modification de l’administration `/is`.
- `assets/tesla-hero-reference.jpg`, `assets/choice-*.jpg` : découpes des éléments visuels de la capture fournie par le client, pour la fidélité graphique ; leur qualité dépend de la résolution de la capture.
- Champs Google Places et bouton GPS : intégration existante dans `assets/app.js`; l’API navigateur doit être configurée dans `/is` pour obtenir de vrais résultats.
- Date et heure affichées sur la page d’accueil : transmises au calcul d’estimation.
- `reserver.php` et les autres pages sont conservées ; elles n’ont pas encore été harmonisées avec ce nouveau thème de la page d’accueil.

## Mise en ligne
Envoyer les fichiers sur un serveur PHP/MySQL avec HTTPS; ouvrir `/install/` pour la configuration seulement si installation neuve, ou effectuer une mise à jour prudente de la base existante après sauvegarde. Changer tout mot de passe partagé auparavant. Tester les API et les paiements avant toute mise en production.
