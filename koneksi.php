<?php
// koneksi.php - 100% Native Local Database
error_reporting(E_ALL);
ini_set('display_errors', '1');

$host = '127.0.0.1';
$port = '5432';
$db   = 'votesecure_db';
$user = 'postgres';
$pass = 'MbakAudrya96'; // Masukkan password master PostgreSQL laptopmu

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Lokal Gagal: " . $e->getMessage());
}

function catatSecurityLog($pdo, $eventType, $payload, $responseStatus) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $stmt = $pdo->prepare("INSERT INTO security_logs (event_type, ip_address, payload_input, status_response, created_at) VALUES (:ev, :ip, :pl, :st, NOW())");
        $stmt->execute([
            'ev' => substr($eventType, 0, 50),
            'ip' => substr($ip, 0, 45),
            'pl' => substr($payload, 0, 255),
            'st' => substr($responseStatus, 0, 50)
        ]);
    } catch (Exception $e) {
        // Log hening
    }
}
?>