<?php
// COPIER ce fichier hors de public_html, adapter les chemins et renommer en backup.php.
// Exécuter depuis une tâche CRON PHP CLI (si hébergeur compatible).
require_once '/CHEMIN/DU/SITE/module/autoload.php';
$config=require '/CHEMIN/PRIVE/database-credentials.php'; // array: host,port,database,username,password
$pdo=\Islem\Extension\Database::connect($config);
$backup=new \Islem\Extension\Backup($pdo,'/CHEMIN/PRIVE/islem-backups');
echo $backup->run().PHP_EOL;
