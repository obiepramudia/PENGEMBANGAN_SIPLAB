<?php
require_once "auth.php";
wajibRole("pengelola");
wajibPost("data-peminjaman.php");
$id = (int)($_POST["id"] ?? 0);
$aksi = $_POST["aksi"] ?? "";
$catatan = mb_substr(trim($_POST["catatan"] ?? ""), 0, 250);

try {
    $conn->begin_transaction();
    $p = qRow($conn, "SELECT id_user, status_peminjaman FROM peminjaman WHERE id_peminjaman = ? FOR UPDATE", "i", [$id]);
    if (!$p) throw new Exception("Data peminjaman tidak ditemukan.");
    $uid = (int)$p["id_user"];
    $st = $p["status_peminjaman"];
    $detail = qAll($conn, "SELECT d.id_alat, d.jumlah, a.nama_alat, a.jumlah AS stok, a.status FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat WHERE d.id_peminjaman = ? ORDER BY d.id_alat FOR UPDATE", "i", [$id]);

    if ($aksi === "setujui" && $st === "menunggu") {
        if (!$detail) throw new Exception("Detail alat pada pengajuan tidak ditemukan.");
        foreach ($detail as $it) {
            if ($it["status"] !== "tersedia" || (int)$it["stok"] < (int)$it["jumlah"]) throw new Exception("Stok " . $it["nama_alat"] . " tidak mencukupi. Pengajuan belum bisa disetujui.");
        }
        foreach ($detail as $it) {
            qExec($conn, "UPDATE alat SET jumlah = jumlah - ? WHERE id_alat = ?", "ii", [(int)$it["jumlah"], (int)$it["id_alat"]]);
            segarkanStatusAlat($conn, (int)$it["id_alat"]);
        }
        qExec($conn, "UPDATE peminjaman SET status_peminjaman = 'disetujui', catatan_pengelola = ? WHERE id_peminjaman = ?", "si", [$catatan ?: null, $id]);
        buatNotifikasi($conn, $uid, "Pengajuan peminjaman #$id disetujui. Ambil alat pada jadwal yang dipilih.");
        $msg = "Pengajuan #$id disetujui dan stok diperbarui.";
    } elseif ($aksi === "tolak" && $st === "menunggu") {
        qExec($conn, "UPDATE peminjaman SET status_peminjaman = 'ditolak', catatan_pengelola = ? WHERE id_peminjaman = ?", "si", [$catatan ?: null, $id]);
        buatNotifikasi($conn, $uid, "Pengajuan peminjaman #$id ditolak." . ($catatan ? " Alasan: $catatan" : ""));
        $msg = "Pengajuan #$id ditolak.";
    } elseif ($aksi === "mulai" && $st === "disetujui") {
        qExec($conn, "UPDATE peminjaman SET status_peminjaman = 'sedang_dipinjam' WHERE id_peminjaman = ?", "i", [$id]);
        buatNotifikasi($conn, $uid, "Alat untuk peminjaman #$id sudah diserahkan. Kembalikan tepat waktu.");
        $msg = "Peminjaman #$id berstatus sedang dipinjam.";
    } elseif ($aksi === "batalkan" && $st === "disetujui") {
        foreach ($detail as $it) {
            qExec($conn, "UPDATE alat SET jumlah = jumlah + ? WHERE id_alat = ?", "ii", [(int)$it["jumlah"], (int)$it["id_alat"]]);
            segarkanStatusAlat($conn, (int)$it["id_alat"]);
        }
        qExec($conn, "UPDATE peminjaman SET status_peminjaman = 'dibatalkan' WHERE id_peminjaman = ?", "i", [$id]);
        buatNotifikasi($conn, $uid, "Peminjaman #$id dibatalkan oleh pengelola.");
        $msg = "Peminjaman #$id dibatalkan dan stok dikembalikan.";
    } else {
        throw new Exception("Aksi tidak sesuai dengan status peminjaman.");
    }
    catatLog("status_" . $aksi, "Peminjaman #$id");
    $conn->commit();
    notif($msg);
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $x) {}
    notif($e->getMessage(), "error");
}
$balik = $_SERVER["HTTP_REFERER"] ?? "";
ke(str_contains($balik, "dashboard-pengelola.php") ? "dashboard-pengelola.php" : "data-peminjaman.php");
