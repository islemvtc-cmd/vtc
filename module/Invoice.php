<?php
declare(strict_types=1);
namespace Islem\Extension;
use PDO;
final class Invoice {
 public function __construct(private PDO $db,private string $privateDirectory){}
 private static function pdfEscape(string $s):string {
   $s=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$s)?:'';
   return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)',' ',' '],$s);
 }
 /** Petit PDF texte natif; TVA et mentions obligatoires doivent être configurées/validées. */
 private function pdf(array $lines):string {
   $ops="BT /F1 11 Tf 45 796 Td 15 TL\n";
   foreach($lines as $i=>$line){ if($i>0)$ops.="T*\n"; $ops.='('.self::pdfEscape($line).") Tj\n"; }
   $ops.="ET\n"; $objects=[];
   $objects[1]='<< /Type /Catalog /Pages 2 0 R >>';
   $objects[2]='<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
   $objects[3]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
   $objects[4]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
   $objects[5]='<< /Length '.strlen($ops).' >>' . "\nstream\n".$ops."endstream";
   $pdf="%PDF-1.4\n";$offsets=[0];
   for($i=1;$i<=5;$i++){$offsets[$i]=strlen($pdf);$pdf.="$i 0 obj\n{$objects[$i]}\nendobj\n";}
   $xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";
   for($i=1;$i<=5;$i++)$pdf.=sprintf('%010d 00000 n ',$offsets[$i])."\n";
   return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
 }
 public function create(int $bookingId, array $company):array {
   foreach(['name','address','registration','vat_notice'] as $key)if(empty($company[$key]))throw new \InvalidArgumentException("Mention facture manquante : $key");
   if(!is_dir($this->privateDirectory)||!is_writable($this->privateDirectory))throw new \RuntimeException('Stockage privé indisponible');
   $s=$this->db->prepare('SELECT b.*, c.first_name,c.last_name,c.email FROM islem_bookings b JOIN islem_customers c ON b.customer_id=c.id WHERE b.id=?');$s->execute([$bookingId]);$b=$s->fetch();
   if(!$b||!in_array($b['status'],['completed','confirmed'],true)||$b['confirmed_total']===null||empty($b['email']))throw new \RuntimeException('Réservation incomplète ou non facturable');
   $existing=$this->db->prepare('SELECT * FROM islem_invoices WHERE booking_id=?');$existing->execute([$bookingId]);
   if($row=$existing->fetch())return $row;
   $number='IV-'.gmdate('Y').'-'.str_pad((string)$bookingId,7,'0',STR_PAD_LEFT);
   $fileName=$number.'.pdf';$path=rtrim($this->privateDirectory,'/').'/'.$fileName;
   $lines=['FACTURE '.$number,'Émise le '.gmdate('d/m/Y'),'',(string)$company['name'],(string)$company['address'],(string)$company['registration'],(string)$company['vat_notice'],'','CLIENT : '.trim(($b['first_name']??'').' '.($b['last_name']??'')),'Adresse : '.($b['customer_address']?:'Non renseignée'),'E-mail : '.$b['email'],'','Prestation VTC n° '.$b['booking_code'],'Départ : '.$b['pickup_address'],'Arrivée : '.$b['dropoff_address'],'','TOTAL TTC : '.number_format((float)$b['confirmed_total'],2,',',' ').' EUR','', 'Paiement et TVA selon les informations contractuelles'];
   $content=$this->pdf($lines);
   if(file_put_contents($path,$content,LOCK_EX)===false)throw new \RuntimeException('Ecriture PDF impossible');chmod($path,0600);
   try {$s=$this->db->prepare('INSERT INTO islem_invoices(booking_id,invoice_number,issued_on,customer_email,customer_name,amount_total,pdf_path) VALUES(?,?,UTC_DATE(),?,?,?,?)');
        $s->execute([$bookingId,$number,$b['email'],trim(($b['first_name']??'').' '.($b['last_name']??'')),$b['confirmed_total'],$path]);
   }catch(\Throwable $e){if($this->db->errorCode()!=='00000'){} throw $e;}
   return ['id'=>(int)$this->db->lastInsertId(),'invoice_number'=>$number,'pdf_path'=>$path,'email_status'=>'pending'];
 }
 /** Le mailer doit envoyer le PDF via SMTP/API sécurisée, et lever une exception en cas d'échec. */
 public function send(int $invoiceId,callable $mailer):void {
   $s=$this->db->prepare('SELECT * FROM islem_invoices WHERE id=?');$s->execute([$invoiceId]);$invoice=$s->fetch();
   if(!$invoice)throw new \RuntimeException('Facture introuvable');
   if($invoice['email_status']==='sent')return;
   if(!is_file($invoice['pdf_path']))throw new \RuntimeException('PDF introuvable');
   try{$mailer($invoice['customer_email'],'Votre facture ISLEM VTC '.$invoice['invoice_number'],'Veuillez trouver votre facture en pièce jointe.',$invoice['pdf_path']);
      $q=$this->db->prepare("UPDATE islem_invoices SET email_status='sent',emailed_at=UTC_TIMESTAMP() WHERE id=?");$q->execute([$invoiceId]);
   }catch(\Throwable $e){$q=$this->db->prepare("UPDATE islem_invoices SET email_status='failed' WHERE id=?");$q->execute([$invoiceId]);throw $e;}
 }
}
