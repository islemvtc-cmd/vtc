<?php
declare(strict_types=1);
namespace Islem\Extension;
use PDO;
use DateTimeImmutable;
use DateTimeZone;
final class Availability {
    public function __construct(private PDO $db) {}
    private function utc(DateTimeImmutable $d): string {return $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
    /** Vérifie la disponibilité du chauffeur. Les créneaux doivent englober trajet + marge de battement. */
    public function isAvailable(int $driverId, DateTimeImmutable $from, DateTimeImmutable $to, ?int $ignoreBookingId=null):bool {
        if($from >= $to) throw new \InvalidArgumentException('Créneau non valide');
        $sql="SELECT COUNT(*) FROM islem_driver_time_slots WHERE driver_id=? AND state='active' AND starts_at < ? AND ends_at > ?";
        $params=[$driverId,$this->utc($to),$this->utc($from)];
        if($ignoreBookingId!==null){$sql.=' AND booking_id <> ?';$params[]=$ignoreBookingId;}
        $s=$this->db->prepare($sql); $s->execute($params); return (int)$s->fetchColumn()===0;
    }
    /** Attribution atomique par verrou MySQL nommé, même sous deux clics simultanés. */
    public function assign(int $bookingId,int $driverId,int $vehicleId,DateTimeImmutable $from,DateTimeImmutable $to):void {
        if(min($bookingId,$driverId,$vehicleId)<1 || $from >= $to)throw new \InvalidArgumentException('Données invalides');
        $key='islem:driver:'.$driverId;
        $lock=$this->db->prepare('SELECT GET_LOCK(?, 10)');$lock->execute([$key]);
        if((int)$lock->fetchColumn()!==1)throw new \RuntimeException('Chauffeur occupé, veuillez réessayer');
        try {
            $this->db->beginTransaction();
            $bk=$this->db->prepare('SELECT id FROM islem_bookings WHERE id=? FOR UPDATE');$bk->execute([$bookingId]);
            if(!$bk->fetch())throw new \RuntimeException('Réservation absente');
            $dup=$this->db->prepare('SELECT id FROM islem_driver_time_slots WHERE booking_id=? FOR UPDATE');$dup->execute([$bookingId]);
            if($dup->fetch())throw new \RuntimeException('Réservation déjà attribuée');
            if(!$this->isAvailable($driverId,$from,$to))throw new \RuntimeException('Chauffeur indisponible sur ce créneau');
            $s=$this->db->prepare("INSERT INTO islem_driver_time_slots(booking_id,driver_id,vehicle_id,starts_at,ends_at) VALUES(?,?,?,?,?)");
            $s->execute([$bookingId,$driverId,$vehicleId,$this->utc($from),$this->utc($to)]);
            $assign=$this->db->prepare('INSERT INTO islem_booking_assignments (booking_id,driver_id,vehicle_id) VALUES (?,?,?)');
            $assign->execute([$bookingId,$driverId,$vehicleId]);
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
        finally{$unlock=$this->db->prepare('SELECT RELEASE_LOCK(?)');$unlock->execute([$key]);}
    }
    /** Annulation/libération en conservant l'historique. A appeler depuis la logique admin. */
    public function release(int $bookingId):void {
        $s=$this->db->prepare("UPDATE islem_driver_time_slots SET state='released' WHERE booking_id=? AND state='active'");$s->execute([$bookingId]);
        $s=$this->db->prepare('UPDATE islem_booking_assignments SET released_at=UTC_TIMESTAMP() WHERE booking_id=? AND released_at IS NULL');$s->execute([$bookingId]);
    }
}
