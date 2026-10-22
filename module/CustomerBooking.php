<?php
declare(strict_types=1);
namespace Islem\Extension;
use PDO;
final class CustomerBooking {
  public function __construct(private PDO $db){}
  /** N'afficher le jeton qu'au client authentifié ; ne jamais le journaliser. */
  public function createAccessLink(int $bookingId, int $days=30):string {
    if($days<1||$days>90)throw new \InvalidArgumentException('Durée incorrecte');
    $token=bin2hex(random_bytes(32));
    $s=$this->db->prepare('INSERT INTO islem_customer_access(booking_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? DAY))');
    $s->execute([$bookingId,hash('sha256',$token),$days]);return $token;
  }
  public function lookup(string $token):?array {
    if(!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
    $s=$this->db->prepare("SELECT b.id,b.booking_code,b.pickup_address,b.dropoff_address,b.pickup_at,b.status,b.confirmed_total FROM islem_customer_access a JOIN islem_bookings b ON b.id=a.booking_id WHERE a.token_hash=? AND a.revoked_at IS NULL AND a.expires_at>UTC_TIMESTAMP() LIMIT 1");
    $s->execute([hash('sha256',$token)]);return $s->fetch()?:null;
  }
  public function requestChange(string $token,string $message,?string $newPickupAt=null,?string $newPickup=null,?string $newDropoff=null):int {
    $booking=$this->lookup($token);if(!$booking)throw new \RuntimeException('Lien invalide ou expiré');
    $message=trim($message);
    if(mb_strlen($message)>2000 || mb_strlen($message)<5)throw new \InvalidArgumentException('Message invalide');
    if(in_array($booking['status'],['cancelled','completed'],true))throw new \RuntimeException('Réservation non modifiable');
    if($newPickupAt!==null && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$newPickupAt))throw new \InvalidArgumentException('Date UTC attendue');
    foreach([$newPickup,$newDropoff] as $a){if($a!==null && mb_strlen($a)>500)throw new \InvalidArgumentException('Adresse trop longue');}
    $s=$this->db->prepare('INSERT INTO islem_booking_change_requests (booking_id,customer_message,proposed_pickup_at,proposed_pickup_address,proposed_dropoff_address) VALUES (?,?,?,?,?)');
    $s->execute([$booking['id'],$message,$newPickupAt,$newPickup,$newDropoff]);return (int)$this->db->lastInsertId();
  }
  /** Appeler après validation administrative : la demande seule ne modifie jamais le trajet. */
  public function review(int $requestId,int $adminId,bool $approve):void {
    $this->db->beginTransaction();
    try {
      $s=$this->db->prepare('SELECT * FROM islem_booking_change_requests WHERE id=? FOR UPDATE');$s->execute([$requestId]);$r=$s->fetch();
      if(!$r || $r['state']!=='pending')throw new \RuntimeException('Demande déjà traitée');
      // IMPORTANT: pour une date modifiée, vérifier et réattribuer les créneaux AVANT approbation.
      if($approve && $r['proposed_pickup_at']!==null)throw new \RuntimeException('Changement horaire : validation manuelle des disponibilités obligatoire');
      if($approve){
        $sets=[];$params=[];
        if($r['proposed_pickup_address']!==null){$sets[]='pickup_address=?';$params[]=$r['proposed_pickup_address'];}
        if($r['proposed_dropoff_address']!==null){$sets[]='dropoff_address=?';$params[]=$r['proposed_dropoff_address'];}
        if($sets){$params[]=$r['booking_id'];$s=$this->db->prepare('UPDATE islem_bookings SET '.implode(',',$sets).' WHERE id=?');$s->execute($params);}
      }
      $s=$this->db->prepare('UPDATE islem_booking_change_requests SET state=?,reviewed_by=?,reviewed_at=UTC_TIMESTAMP() WHERE id=?');
      $s->execute([$approve?'approved':'rejected',$adminId,$requestId]);$this->db->commit();
    }catch(\Throwable $e){$this->db->rollBack();throw $e;}
  }
}
