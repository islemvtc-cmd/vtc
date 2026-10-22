# ISLEM VTC — Version d'intégration (22 tables)

**Cette version est un socle fonctionnel à tester, pas un site certifié prêt pour le public.**

## Déploiement sur un hébergement PHP/MySQL
1. Changer tout mot de passe FTP précédemment partagé et activer HTTPS avant d'entrer des identifiants ou données clients.
2. Sauvegarder le site et la base existants. Ne pas écraser l'ancienne base par une base vide.
3. Envoyer le contenu de cette archive dans la racine web via le gestionnaire de fichiers de l'hébergeur.
4. Accéder à `/install/` via HTTPS, saisir hôte, port, base, utilisateur, mot de passe MySQL, et créer ou authentifier l'administrateur.
5. L'installateur n'efface aucune table et crée celles qui manquent (22 tables prévues). En présence de tables préexistantes inconnues, il interrompt l'opération pour éviter de détruire des données. Il ne migre pas les anciennes colonnes.
6. Après succès, vérifier que `/install/` a été supprimé et que `config/installed.lock` existe. Si nécessaire, supprimer manuellement le dossier install.
7. Se connecter à `/is/`, configurer Google Maps navigateur / Places, Google Geocoding serveur, Google Routes serveur puis activer Routes avec 1.
8. Tester la recherche des adresses, l'estimation, l'envoi d'une réservation, le suivi via son lien et l'écran admin.

## Ce qui fonctionne à ce stade dans le code
- Page d'accueil responsive avec formulaire et bouton GPS (Google Places et Geocoding à configurer).
- Calcul d'itinéraire Google Routes côté serveur et majorations de base.
- Demande de réservation en MySQL, récapitulatif client, accès au suivi avec jeton secret.
- Administrateur : connexion, liste des réservations, changements de statut, saisie de chauffeurs et de véhicules, paramètres API et tarifs, examen des demandes de modification.
- Modules backend complémentaires : disponibilité, facture PDF, sauvegarde et suivi client.

## Limitations importantes avant production
- **L'attribution automatique des chauffeurs, l'anti-chevauchement, l'édition des zones, Stripe, la génération/envoi SMTP des factures et les sauvegardes planifiées ne sont PAS encore intégrés au parcours web complet.** Les modules disponibles devront être raccordés et testés sur l'hébergement.
- Cette version ne présente pas de fausses disponibilités. La réservation envoyée est une demande à confirmer.
- La clé navigateur Google Maps doit être restreinte au domaine ; les clés serveur doivent être protégées et les requêtes/API soumises à quotas.
- Vérifier les limites FreeHosting (SSL, cURL, extensions PHP, quotas, tâche cron, SMTP) avant toute ouverture publique.
- L'installateur n'effectue pas automatiquement une sauvegarde : l'utilisateur doit en disposer avant de commencer.
- Ne jamais envoyer des identifiants FTP/MySQL par message ; changer ceux déjà partagés.

## Structure
`index.php` page d'accueil ; `reserver.php` réservation ; `confirmation.php` confirmation ; `suivi.php` suivi client ; `api/` requêtes ; `is/` administration ; `install/` installation ; `module/` logique métier ; `config/` configuration ; `assets/` thème et photo.
