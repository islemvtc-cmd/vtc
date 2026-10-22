<?php
declare(strict_types=1);
namespace Islem\Extension;
use PDO;
final class Backup {
 public function __construct(private PDO $db,private string $directory){}
 /** Lancé par CRON/CLI, stockage impérativement HORS de public_html et non accessible via HTTP. */
 public function run():string {
   if(PHP_SAPI!=='cli')throw new \RuntimeException('Sauvegarde accessible uniquement en CLI');
   if(!is_dir($this->directory)||!is_writable($this->directory))throw new \RuntimeException('Répertoire privé non disponible');
   if(realpath($this->directory)===false)throw new \RuntimeException('Chemin incorrect');
   $file=rtrim($this->directory,'/').'/islem-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sql';
   $fh=fopen($file,'xb');if(!$fh)throw new \RuntimeException('Impossible de créer la sauvegarde');chmod($file,0600);
   try {
     fwrite($fh,"-- ISLEM VTC MySQL UTC backup. Ne pas diffuser.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
     $tables=$this->db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
     foreach($tables as $row){$table=$row[0];if(!preg_match('/^[a-zA-Z0-9_]+$/',$table))throw new \RuntimeException('Nom de table invalide');
       $quoted='`'.$table.'`';$ddl=$this->db->query("SHOW CREATE TABLE $quoted")->fetch(PDO::FETCH_NUM)[1];
       fwrite($fh,"\n-- $table\n".$ddl.";\n");
       $stmt=$this->db->query("SELECT * FROM $quoted",PDO::FETCH_ASSOC);
       while($record=$stmt->fetch(PDO::FETCH_ASSOC)){
         $fields=array_map(fn($x)=>'`'.$x.'`',array_keys($record));
         $values=[];foreach($record as $value){$values[]=$value===null?'NULL':'0x'.bin2hex((string)$value);}
         fwrite($fh,"INSERT INTO $quoted (".implode(',',$fields).") VALUES (".implode(',',$values).");\n");
       }
     }
     fwrite($fh,"SET FOREIGN_KEY_CHECKS=1;\n");fflush($fh);fclose($fh);
     $digest=hash_file('sha256',$file);$bytes=filesize($file);
     $s=$this->db->prepare('INSERT INTO islem_backup_runs(file_name,sha256,bytes) VALUES(?,?,?)');$s->execute([basename($file),$digest,$bytes]);
     return $file;
   }catch(\Throwable $e){if(is_resource($fh))fclose($fh);@unlink($file);throw $e;}
 }
}
