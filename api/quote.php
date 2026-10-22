<?php
require dirname(__DIR__).'/bootstrap.php';
try{post_only();verify_csrf();$p=json_decode(file_get_contents('php://input'),true);if(!is_array($p))$p=$_POST;foreach(['pickup','dropoff','pickup_lat','pickup_lng','dropoff_lat','dropoff_lng','when'] as $k){if(!isset($p[$k]))throw new InvalidArgumentException('Donnée manquante');}
 foreach(['pickup_lat','pickup_lng','dropoff_lat','dropoff_lng'] as $k)if(!is_numeric($p[$k]))throw new InvalidArgumentException('Coordonnées invalides');
 $when=(string)$p['when'];$d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$when,new DateTimeZone('Europe/Paris'));if(!$d||$d<new DateTimeImmutable('now',new DateTimeZone('Europe/Paris')))throw new InvalidArgumentException('Date future obligatoire');
 $route=google_route((float)$p['pickup_lat'],(float)$p['pickup_lng'],(float)$p['dropoff_lat'],(float)$p['dropoff_lng'],$when);$price=calculate_price($route,clean((string)$p['dropoff']),$when);json_reply(['ok'=>true,'price'=>$price]);
}catch(Throwable $ex){json_reply(['error'=>$ex->getMessage()],422);}
