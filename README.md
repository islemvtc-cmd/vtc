# ISLEM VTC

Sources PHP du site ISLEM VTC, importées depuis l'archive `islemvtc.fr.zip` fournie le 22 octobre 2026.

Cette base de travail est utilisée pour poursuivre le site V3. Les documents d'origine mentionnent encore V2 ; cet import ne constitue pas une nouvelle version fonctionnelle.

## Configuration et hébergement

La racine du dépôt correspond au dossier `public_html` de l'archive. Le site utilise PHP 8.1 ou supérieur et MySQL, avec PDO MySQL, mbstring et cURL pour les appels Google. Les paramètres métier et les clés Google sont lus depuis la base de données existante.

Les identifiants MySQL, le verrou d'installation et l'ancienne archive imbriquée sont exclus du dépôt. Copier `config/database.example.php` vers `config/database.php` et renseigner les valeurs localement. Sur un hébergement existant, conserver la configuration et le fichier `config/installed.lock` déjà en place.

## Limites de cette sauvegarde

L'archive ne contient ni export MySQL ni dossier `install/`. Elle ne permet donc pas une installation autonome sur une base vide. Le guide `README_DEPLOIEMENT.md` et le document `README_DESIGN_V2.md` sont conservés comme documentation d'origine ; leurs instructions d'installation ne sont pas toutes applicables à cette sauvegarde.

L'import GitHub ne modifie pas l'hébergement ni les données existantes. Le fonctionnement complet devra être vérifié avec la base et les intégrations de l'hébergement.
