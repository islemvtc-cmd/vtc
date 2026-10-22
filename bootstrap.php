<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
require_once __DIR__.'/module/autoload.php';
function db(): PDO {static $pdo=null;if($pdo instanceof PDO)return $pdo;$path=__DIR__.'/config/database.php';if(!is_file($path)||!is_file(__DIR__.'/config/installed.lock'))throw new RuntimeException('Installation requise');return $pdo=\Islem\Extension\Database::connect(require $path);}
function setting(string $key,string $default=''):string {static $cache=[];if(!array_key_exists($key,$cache)){ $s=db()->prepare('SELECT setting_value FROM islem_settings WHERE setting_key=?');$s->execute([$key]);$v=$s->fetchColumn();$cache[$key]=$v===false?$default:(string)$v;}return $cache[$key];}
function e(?string $value):string{return htmlspecialchars($value??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf():string {return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function verify_csrf():void {if(!hash_equals(csrf(),(string)($_POST['csrf']??$_SERVER['HTTP_X_CSRF_TOKEN']??'')))throw new RuntimeException('Session expirée, rechargez la page');}
function json_reply(array $data,int $status=200):never {http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function require_admin():void {if(empty($_SESSION['admin_id'])){header('Location: /is/login.php');exit;}}
function post_only():void {if(($_SERVER['REQUEST_METHOD']??'')!=='POST')json_reply(['error'=>'Méthode non autorisée'],405);}
function clean(string $s,int $max=500):string {$s=trim($s);if($s===''||mb_strlen($s)>$max)throw new InvalidArgumentException('Champ manquant ou trop long');return $s;}
function booking_code():string{return strtoupper(bin2hex(random_bytes(6)));}
function google_route(float $lat1,float $lng1,float $lat2,float $lng2,?string $departure=null):array {
 $key=setting('google_routes_server_key');if(setting('google_routes_enabled')!=='1'||$key==='')throw new RuntimeException('API Google Routes non configurée dans /is');
 if(!function_exists('curl_init'))throw new RuntimeException('Extension cURL absente');
 $body=['origin'=>['location'=>['latLng'=>['latitude'=>$lat1,'longitude'=>$lng1]]],'destination'=>['location'=>['latLng'=>['latitude'=>$lat2,'longitude'=>$lng2]]],'travelMode'=>'DRIVE','routingPreference'=>'TRAFFIC_AWARE','languageCode'=>'fr-FR','units'=>'METRIC'];
 if($departure!==null){$body['departureTime']=(new DateTimeImmutable($departure,new DateTimeZone('Europe/Paris')))->format('Y-m-d\TH:i:sP');}
 $ch=curl_init('https://routes.googleapis.com/directions/v2:computeRoutes');curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-Goog-Api-Key: '.$key,'X-Goog-FieldMask: routes.distanceMeters,routes.duration,routes.staticDuration'],CURLOPT_POSTFIELDS=>json_encode($body),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12]);$response=curl_exec($ch);$http=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
 $parsed=is_string($response)?json_decode($response,true):null;if($http!==200||empty($parsed['routes'][0]))throw new RuntimeException('Itinéraire Google indisponible, veuillez réessayer');$r=$parsed['routes'][0];return ['distance'=>(int)$r['distanceMeters'],'duration'=>(int)round((float)rtrim($r['staticDuration']??$r['duration'],'s')),'traffic'=>(int)round((float)rtrim($r['duration'],'s'))];
}
function calculate_price(array $route,string $destination,string $when):array {
 $km=$route['distance']/1000;$distance=round($km*(float)setting('distance_rate','1.20'),2);
 $isAirport=(bool)preg_match('/(CDG|Charles.de.Gaulle|Roissy|Orly|aéroport)/iu',$destination);
 $isStation=(bool)preg_match('/(gare du nord|gare de lyon|gare de l.est|gare montparnasse)/iu',$destination);
 $extra=$isAirport?(float)setting('airport_extra','5'):($isStation?(float)setting('station_extra','5'):0);
 $delay=max(0,($route['traffic']-$route['duration'])/60);$traffic=round($delay*(float)setting('traffic_eur_per_30min','10')/30,2);
 $d=new DateTimeImmutable($when,new DateTimeZone('Europe/Paris'));$day=(int)$d->format('N');$minute=((int)$d->format('H'))*60+(int)$d->format('i');$minutes=(int)ceil($route['traffic']/60);$rush=0;
 if($minutes>30){if($day<=6&&$minute>=420&&$minute<570)$rush=(float)setting('morning_surcharge','8');$end=$day===5||$day===6?1350:1170;if($day<=6&&$minute>=930&&$minute<$end)$rush=(float)setting('evening_surcharge','10');}
 $total=round($distance+$extra+$traffic+$rush,2);return ['km'=>round($km,1),'minutes'=>$minutes,'distance'=>$distance,'extra'=>$extra,'traffic'=>$traffic,'rush'=>$rush,'total'=>$total,'delay_minutes'=>round($delay,1)];
}
