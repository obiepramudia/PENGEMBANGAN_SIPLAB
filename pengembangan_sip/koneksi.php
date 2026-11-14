<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_pengembangan_sip";

date_default_timezone_set("Asia/Jakarta");

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+07:00'");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit("Koneksi database gagal. Pastikan MySQL XAMPP aktif dan database db_peminjaman_alat sudah diimpor. Detail: " . htmlspecialchars($e->getMessage()));
}

require_once __DIR__ . "/includes/migrasi.php";
jalankanMigrasi($conn);
