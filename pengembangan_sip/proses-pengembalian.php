<?php
require_once "auth.php";
wajibRole("pengelola");
wajibPost("pengembalian.php");
$id = (int)($_POST["id"] ?? 0);
$aksi = $_POST["aksi"] ?? "";

try {
    if ($aksi === "ingatkan") {
        $p = qRow($conn, "SELECT id_user, status_peminjaman FROM peminjaman WHERE id_peminjaman = ?", "i", [$id]);
        if (!$p || $p["status_peminjaman"] !== "sedang_dipinjam") throw new Exception("Peminjaman tidak dalam status sedang dipinjam.");
        buatNotifikasi($conn, (int)$p["id_user"], "Pengingat: peminjaman #$id sudah melewati batas waktu. Segera kembalikan alat ke pengelola.");
        catatLog("pengingat", "Peminjaman #$id");
        notif("Pengingat dikirim.");
        ke("pengembalian.php");
    }
    if ($aksi !== "kembali") throw new Exception("Aksi tidak dikenal.");

    $kondisi = $_POST["kondisi"] ?? "";
    $catatan = mb_substr(trim($_POST["catatan"] ?? ""), 0, 250);
    $rusakInput = $_POST["rusak"] ?? [];
    if (!in_array($kondisi, ["baik", "rusak_ringan", "rusak_berat"], true)) throw new Exception("Kondisi alat tidak valid.");
    if (!is_array($rusakInput)) $rusakInput = [];

    $conn->begin_transaction();
    $p = qRow($conn, "SELECT id_user, status_peminjaman FROM peminjaman WHERE id_peminjaman = ? FOR UPDATE", "i", [$id]);
    if (!$p || $p["status_peminjaman"] !== "sedang_dipinjam") throw new Exception("Peminjaman tidak dalam status sedang dipinjam.");
    $items = qAll($conn, "SELECT id_alat, jumlah FROM detail_peminjaman WHERE id_peminjaman = ? ORDER BY id_alat", "i", [$id]);

    $totalRusak = 0;
    $rusak = [];
    foreach ($items as $it) {
        $aid = (int)$it["id_alat"];
        $r = (int)($rusakInput[$aid] ?? 0);
        if ($r < 0 || $r > (int)$it["jumlah"]) throw new Exception("Jumlah rusak tidak boleh melebihi jumlah yang dipinjam.");
        $rusak[$aid] = $r;
        $totalRusak += $r;
    }
    if ($kondisi === "baik" && $totalRusak > 0) throw new Exception("Kondisi dipilih Baik, tetapi ada unit rusak. Ubah kondisi atau kosongkan jumlah rusak.");
    if ($kondisi !== "baik" && $totalRusak === 0) throw new Exception("Isi jumlah unit yang rusak, atau pilih kondisi Baik.");

    $terlambat = false;
    $jt = qRow($conn, "SELECT TIMESTAMP(p.tanggal_peminjaman, j.waktu_selesai) < NOW() AS tl FROM peminjaman p JOIN jadwal j ON j.id_jadwal = p.id_jadwal WHERE p.id_peminjaman = ?", "i", [$id]);
    if ($jt && (int)$jt["tl"] === 1) $terlambat = true;

    qExec($conn, "INSERT INTO pengembalian (id_peminjaman, tanggal_pengembalian, kondisi_kembali, catatan, jumlah_rusak) VALUES (?, NOW(), ?, ?, ?)", "issi", [$id, $kondisi, $catatan ?: null, $totalRusak]);
    qExec($conn, "UPDATE peminjaman SET status_peminjaman = 'selesai' WHERE id_peminjaman = ?", "i", [$id]);
    foreach ($items as $it) {
        $aid = (int)$it["id_alat"];
        $r = $rusak[$aid];
        $baik = (int)$it["jumlah"] - $r;
        qExec($conn, "UPDATE alat SET jumlah = jumlah + ?, jumlah_rusak = jumlah_rusak + ? WHERE id_alat = ?", "iii", [$baik, $r, $aid]);
        if ($r > 0) qExec($conn, "UPDATE alat SET kondisi = ? WHERE id_alat = ?", "si", [$kondisi, $aid]);
        segarkanStatusAlat($conn, $aid);
    }
    buatNotifikasi($conn, (int)$p["id_user"], "Pengembalian peminjaman #$id sudah dicatat. Kondisi: " . strtolower(label($kondisi)) . ($terlambat ? ". Pengembalian melewati batas waktu." : "."));
    catatLog("pengembalian", "Peminjaman #$id, kondisi $kondisi");
    $conn->commit();
    notif("Pengembalian #$id berhasil dicatat.");
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $x) {}
    notif($e->getMessage(), "error");
}
ke("pengembalian.php");
