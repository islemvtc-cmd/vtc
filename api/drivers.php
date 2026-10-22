<?php
require dirname(__DIR__).'/bootstrap.php';
try {
 $sql="SELECT d.id,d.first_name,d.last_name,d.photo_path,d.verified,v.id vehicle_id,v.make,v.model,v.photo_path vehicle_photo,v.compliant, EXISTS(SELECT 1 FROM islem_driver_documents x WHERE x.driver_id=d.id AND x.document_type='vtc_card' AND x.validated=1) vtc_card, EXISTS(SELECT 1 FROM islem_driver_documents x WHERE x.driver_id=d.id AND x.document_type='passenger_insurance' AND x.validated=1) passenger_insurance, EXISTS(SELECT 1 FROM islem_driver_documents x WHERE x.driver_id=d.id AND x.document_type='transport_registration' AND x.validated=1) transport_registration FROM islem_drivers d INNER JOIN islem_vehicles v ON v.driver_id=d.id AND v.active=1 WHERE d.active=1 ORDER BY d.id LIMIT 30";
 $s=db()->query($sql);json_reply(['drivers'=>$s->fetchAll()]);
}catch(Throwable $ex){json_reply(['drivers'=>[]],500);}
