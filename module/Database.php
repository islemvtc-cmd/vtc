<?php
declare(strict_types=1);
namespace Islem\Extension;
use PDO;
final class Database {
    public static function connect(array $c): PDO {
        foreach (['host','database','username','password'] as $key) {
            if (!array_key_exists($key,$c)) throw new \InvalidArgumentException("Configuration MySQL absente: $key");
        }
        $host=(string)$c['host']; $database=(string)$c['database']; $port=(int)($c['port']??3306);
        if(!preg_match('/^[a-zA-Z0-9_.:-]+$/',$host) || !preg_match('/^[a-zA-Z0-9_]+$/',$database) || $port<1 || $port>65535) throw new \InvalidArgumentException('Hôte, port ou base invalides');
        $pdo=new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",(string)$c['username'],(string)$c['password'],[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false
        ]);
        $pdo->exec("SET time_zone = '+00:00'"); // Toutes les heures doivent être fournies en UTC.
        return $pdo;
    }
}
