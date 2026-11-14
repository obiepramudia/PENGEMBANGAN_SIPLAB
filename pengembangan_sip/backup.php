<?php
require_once "auth.php";
wajibRole("pengelola");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajibPost("backup.php");
    @set_time_limit(120);
    $nama = "backup-sip-lab-" . date("Ymd-His") . ".sql";
    header("Content-Type: application/sql; charset=utf-8");
    header("Content-Disposition: attachment; filename=$nama");
    echo "-- Backup SIP LAB\n-- Dibuat: " . date("Y-m-d H:i:s") . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    $tabel = [];
    $r = $conn->query("SHOW TABLES");
    while ($x = $r->fetch_row()) $tabel[] = $x[0];
    foreach ($tabel as $tb) {
        $c = $conn->query("SHOW CREATE TABLE `$tb`")->fetch_row();
        if (!isset($c[1])) continue;
        echo "DROP TABLE IF EXISTS `$tb`;\n" . $c[1] . ";\n\n";
        $res = $conn->query("SELECT * FROM `$tb`");
        while ($row = $res->fetch_assoc()) {
            $nilai = array_map(fn($v) => $v === null ? "NULL" : "'" . $conn->real_escape_string((string)$v) . "'", array_values($row));
            echo "INSERT INTO `$tb` VALUES (" . implode(",", $nilai) . ");\n";
        }
        echo "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS=1;\n";
    catatLog("backup", $nama);
    exit;
}

$judul = "Backup Data";
$tabelInfo = qAll($conn, "SELECT TABLE_NAME n, TABLE_ROWS r FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
$terakhir = qVal($conn, "SELECT MAX(waktu) FROM log_aktivitas WHERE aksi = 'backup'");
require "includes/header.php";
?>
<section class="form-card"><h2>Unduh cadangan database</h2>
<p>Satu klik menghasilkan file .sql berisi semua tabel dan data. Simpan file di tempat aman. File ini memuat data pengguna dan password dalam bentuk hash.</p>
<p class="muted">Backup terakhir: <?= e($terakhir ? tglIndo($terakhir, true) : "belum pernah") ?>. Lakukan backup rutin, misalnya seminggu sekali.</p>
<form method="post"><?= csrfField() ?><button>Unduh backup (.sql)</button></form>
<p class="muted" style="margin-top:14px">Untuk memulihkan, impor file ini lewat menu Import di phpMyAdmin.</p></section>
<section class="panel"><h2>Isi database</h2><div class="table-wrap"><table><thead><tr><th>Tabel</th><th>Perkiraan jumlah baris</th></tr></thead><tbody>
<?php foreach ($tabelInfo as $t): ?><tr><td><?= e($t["n"]) ?></td><td><?= (int)$t["r"] ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require "includes/footer.php"; ?>
