# ISLEM VTC

Sources PHP du site ISLEM VTC, importÃ©es depuis l'archive `islemvtc.fr.zip` fournie le 22 octobre 2026.

Cette base de travail est utilisÃ©e pour poursuivre le site V3. Les documents d'origine mentionnent encore V2 ; cet import ne constitue pas une nouvelle version fonctionnelle.

## Configuration et hÃ©bergement

La racine du dÃ©pÃ´t correspond au dossier `public_html` de l'archive. Le site utilise PHP 8.1 ou supÃ©rieur et MySQL, avec PDO MySQL, mbstring et cURL pour les appels Google. Les paramÃ¨tres mÃ©tier et les clÃ©s Google sont lus depuis la base de donnÃ©es existante.

Les identifiants MySQL, le verrou d'installation et l'ancienne archive imbriquÃ©e sont exclus du dÃ©pÃ´t. Copier `config/database.example.php` vers `config/database.php` et renseigner les valeurs localement. Sur un hÃ©bergement existant, conserver la configuration et le fichier `config/installed.lock` dÃ©jÃ  en place.

## Limites de cette sauvegarde

L'archive ne contient ni export MySQL ni dossier `install/`. Elle ne permet donc pas une installation autonome sur une base vide. Le guide `README_DEPLOIEMENT.md` et le document `README_DESIGN_V2.md` sont conservÃ©s comme documentation d'origine ; leurs instructions d'installation ne sont pas toutes applicables Ã  cette sauvegarde.

L'import GitHub ne modifie pas l'hÃ©bergement ni les donnÃ©es existantes. Le fonctionnement complet devra Ãªtre vÃ©rifiÃ© avec la base et les intÃ©grations de l'hÃ©bergement.