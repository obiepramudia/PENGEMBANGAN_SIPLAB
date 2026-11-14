<?php
require_once "auth.php";
wajibRole("pengelola");
wajibPost("data-jadwal.php");
$aksi = $_POST["aksi"] ?? "";
$id = (int)($_POST["id"] ?? 0);
$balik = "data-jadwal.php";

function cekTanggal(string $d): void {
    $x = DateTime::createFromFormat("Y-m-d", $d);
    if (!$x || $x->format("Y-m-d") !== $d) throw new Exception("Format tanggal tidak valid.");
}
function cekJam(string $m, string $s): array {
    if (!preg_match('/^\d{2}:\d{2}$/', $m) || !preg_match('/^\d{2}:\d{2}$/', $s)) throw new Exception("Format jam tidak valid.");
    if ($s <= $m) throw new Exception("Jam selesai harus lebih besar dari jam mulai.");
    return [$m . ":00", $s . ":00"];
}
function jadwalAda(mysqli $c, string $tgl, string $m, string $s, int $kecuali = 0): bool {
    return (int)qVal($c, "SELECT COUNT(*) FROM jadwal WHERE tanggal = ? AND waktu_mulai = ? AND waktu_selesai = ? AND id_jadwal <> ?", "sssi", [$tgl, $m, $s, $kecuali]) > 0;
}

try {
    if ($aksi === "tambah") {
        $tgl = trim($_POST["tanggal"] ?? "");
        cekTanggal($tgl);
        [$m, $s] = cekJam($_POST["waktu_mulai"] ?? "", $_POST["waktu_selesai"] ?? "");
        if ($tgl < date("Y-m-d")) throw new Exception("Tanggal jadwal tidak boleh di masa lalu.");
        if (jadwalAda($conn, $tgl, $m, $s)) throw new Exception("Jadwal yang sama sudah ada.");
        qExec($conn, "INSERT INTO jadwal (tanggal, waktu_mulai, waktu_selesai, status) VALUES (?, ?, ?, 'tersedia')", "sss", [$tgl, $m, $s]);
        catatLog("jadwal_tambah", "$tgl $m-$s");
        notif("Jadwal berhasil ditambahkan.");
    } elseif ($aksi === "massal") {
        $dari = trim($_POST["dari"] ?? "");
        $sampai = trim($_POST["sampai"] ?? "");
        cekTanggal($dari);
        cekTanggal($sampai);
        [$m, $s] = cekJam($_POST["waktu_mulai"] ?? "", $_POST["waktu_selesai"] ?? "");
        if ($dari < date("Y-m-d")) throw new Exception("Tanggal awal tidak boleh di masa lalu.");
        if ($sampai < $dari) throw new Exception("Tanggal akhir harus setelah tanggal awal.");
        $kerja = ($_POST["hari"] ?? "kerja") === "kerja";
        $d = new DateTime($dari);
        $akhir = new DateTime($sampai);
        if ($d->diff($akhir)->days > 62) throw new Exception("Rentang tanggal maksimal 62 hari.");
        $dibuat = 0;
        $dilewati = 0;
        $conn->begin_transaction();
        for (; $d <= $akhir; $d->modify("+1 day")) {
            if ($kerja && (int)$d->format("N") >= 6) continue;
            $tgl = $d->format("Y-m-d");
            if (jadwalAda($conn, $tgl, $m, $s)) { $dilewati++; continue; }
            qExec($conn, "INSERT INTO jadwal (tanggal, waktu_mulai, waktu_selesai, status) VALUES (?, ?, ?, 'tersedia')", "sss", [$tgl, $m, $s]);
            $dibuat++;
        }
        $conn->commit();
        catatLog("jadwal_massal", "$dari s.d. $sampai, $dibuat dibuat");
        notif("$dibuat jadwal dibuat, $dilewati dilewati karena sudah ada.");
    } elseif ($aksi === "ubah") {
        $tgl = trim($_POST["tanggal"] ?? "");
        cekTanggal($tgl);
        [$m, $s] = cekJam($_POST["waktu_mulai"] ?? "", $_POST["waktu_selesai"] ?? "");
        $status = ($_POST["status"] ?? "") === "tidak_tersedia" ? "tidak_tersedia" : "tersedia";
        if (jadwalAda($conn, $tgl, $m, $s, $id)) throw new Exception("Jadwal yang sama sudah ada.");
        qExec($conn, "UPDATE jadwal SET tanggal = ?, waktu_mulai = ?, waktu_selesai = ?, status = ? WHERE id_jadwal = ?", "ssssi", [$tgl, $m, $s, $status, $id]);
        catatLog("jadwal_ubah", "#$id");
        notif("Jadwal berhasil diperbarui.");
    } elseif ($aksi === "toggle") {
        qExec($conn, "UPDATE jadwal SET status = IF(status = 'tersedia', 'tidak_tersedia', 'tersedia') WHERE id_jadwal = ?", "i", [$id]);
        catatLog("jadwal_status", "#$id");
        notif("Status jadwal diperbarui.");
    } elseif ($aksi === "hapus") {
        if ((int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE id_jadwal = ?", "i", [$id]) > 0) throw new Exception("Jadwal sudah dipakai pengajuan, jadi tidak bisa dihapus. Gunakan tombol Tutup.");
        qExec($conn, "DELETE FROM jadwal WHERE id_jadwal = ?", "i", [$id]);
        catatLog("jadwal_hapus", "#$id");
        notif("Jadwal dihapus.");
    } else {
        throw new Exception("Aksi tidak dikenal.");
    }
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $x) {}
    notif($e->getMessage(), "error");
    if ($aksi === "ubah") $balik = "data-jadwal.php?edit=$id";
}
ke($balik);
