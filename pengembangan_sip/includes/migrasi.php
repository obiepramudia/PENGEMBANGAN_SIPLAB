<?php
/*
 * Pembaruan struktur database otomatis.
 * Hanya menambah kolom dan tabel baru. Data lama tidak dihapus.
 * Naikkan SKEMA_VERSI jika Anda menambah langkah baru.
 */
const SKEMA_VERSI = 1;

function mg_kolomAda(mysqli $c, string $tabel, string $kolom): bool {
    $q = $c->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $q->bind_param("ss", $tabel, $kolom);
    $q->execute();
    $ada = (int)$q->get_result()->fetch_row()[0] > 0;
    $q->close();
    return $ada;
}

function mg_tambah(mysqli $c, string $tabel, string $kolom, string $definisi): void {
    if (!mg_kolomAda($c, $tabel, $kolom)) {
        $c->query("ALTER TABLE `$tabel` ADD COLUMN `$kolom` $definisi");
    }
}

function mg_enum(mysqli $c, string $tabel, string $kolom, array $nilaiBaru): void {
    $q = $c->prepare("SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $q->bind_param("ss", $tabel, $kolom);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    if (!$row || stripos($row["COLUMN_TYPE"], "enum(") !== 0) return;

    preg_match_all("/'((?:[^']|'')*)'/", $row["COLUMN_TYPE"], $m);
    $nilai = $m[1];
    $berubah = false;
    foreach ($nilaiBaru as $b) {
        if (!in_array($b, $nilai, true)) { $nilai[] = $b; $berubah = true; }
    }
    if (!$berubah) return;

    $daftar = implode(",", array_map(fn($v) => "'" . $v . "'", $nilai));
    $null = $row["IS_NULLABLE"] === "YES" ? "NULL" : "NOT NULL";
    $d = $row["COLUMN_DEFAULT"];
    if ($d === null || strtoupper((string)$d) === "NULL") $def = "";
    elseif ($d !== "" && $d[0] === "'") $def = " DEFAULT $d";
    else $def = " DEFAULT '" . $c->real_escape_string($d) . "'";
    $c->query("ALTER TABLE `$tabel` MODIFY `$kolom` ENUM($daftar) $null$def");
}

function jalankanMigrasi(mysqli $c): void {
    $c->query("CREATE TABLE IF NOT EXISTS pengaturan (kunci VARCHAR(50) NOT NULL PRIMARY KEY, nilai VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $r = $c->query("SELECT nilai FROM pengaturan WHERE kunci = 'skema_versi'")->fetch_row();
    if ($r && (int)$r[0] >= SKEMA_VERSI) return;

    try {
        // Pengguna
        $c->query("ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL");
        mg_tambah($c, "users", "status", "ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'");
        mg_tambah($c, "users", "email", "VARCHAR(100) NULL");
        mg_tambah($c, "users", "no_hp", "VARCHAR(20) NULL");
        mg_tambah($c, "users", "terakhir_login", "DATETIME NULL");

        // Alat
        $stokBaru = !mg_kolomAda($c, "alat", "stok_total");
        mg_tambah($c, "alat", "stok_total", "INT NOT NULL DEFAULT 0");
        mg_tambah($c, "alat", "jumlah_rusak", "INT NOT NULL DEFAULT 0");
        mg_tambah($c, "alat", "kode_alat", "VARCHAR(30) NULL");
        mg_tambah($c, "alat", "kategori", "VARCHAR(60) NOT NULL DEFAULT 'Umum'");
        mg_tambah($c, "alat", "lokasi", "VARCHAR(100) NULL");
        mg_tambah($c, "alat", "deskripsi", "TEXT NULL");
        mg_tambah($c, "alat", "foto", "VARCHAR(255) NULL");
        if ($stokBaru) {
            $c->query("UPDATE alat a SET a.stok_total = a.jumlah + COALESCE((SELECT SUM(d.jumlah) FROM detail_peminjaman d JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman WHERE d.id_alat = a.id_alat AND p.status_peminjaman IN ('disetujui','sedang_dipinjam')), 0)");
            $c->query("UPDATE alat SET kode_alat = CONCAT('ALT-', LPAD(id_alat, 3, '0')) WHERE kode_alat IS NULL OR kode_alat = ''");
        }

        // Peminjaman dan pengembalian
        mg_tambah($c, "peminjaman", "keperluan", "VARCHAR(255) NULL");
        mg_tambah($c, "peminjaman", "catatan_pengelola", "VARCHAR(255) NULL");
        mg_enum($c, "peminjaman", "status_peminjaman", ["dibatalkan"]);
        mg_tambah($c, "pengembalian", "catatan", "VARCHAR(255) NULL");
        mg_tambah($c, "pengembalian", "jumlah_rusak", "INT NOT NULL DEFAULT 0");

        // Jadwal
        mg_enum($c, "jadwal", "status", ["tersedia", "tidak_tersedia"]);

        // Log aktivitas
        $c->query("CREATE TABLE IF NOT EXISTS log_aktivitas (
            id_log INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NULL,
            aksi VARCHAR(50) NOT NULL,
            detail VARCHAR(255) NULL,
            ip VARCHAR(45) NULL,
            waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_aksi_ip_waktu (aksi, ip, waktu)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $c->query("REPLACE INTO pengaturan (kunci, nilai) VALUES ('skema_versi', '" . SKEMA_VERSI . "')");
    } catch (Throwable $e) {
        http_response_code(500);
        exit("Pembaruan struktur database gagal. Detail: " . htmlspecialchars($e->getMessage()));
    }
}
