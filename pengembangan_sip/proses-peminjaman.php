<?php
require_once "auth.php";
wajibRole("siswa", "guru");
wajibPost("peminjaman-saya.php");
$idUser = (int)$_SESSION["id_user"];
$aksi = $_POST["aksi"] ?? "";

try {
    if ($aksi === "ajukan") {
        $idJadwal = (int)($_POST["id_jadwal"] ?? 0);
        $keperluan = trim($_POST["keperluan"] ?? "");
        $ids = $_POST["id_alat"] ?? [];
        $qtys = $_POST["jumlah"] ?? [];
        if (!is_array($ids) || !is_array($qtys)) throw new Exception("Data alat tidak valid.");
        if ($idJadwal < 1) throw new Exception("Pilih jadwal peminjaman.");
        if (mb_strlen($keperluan) < 5) throw new Exception("Isi keperluan peminjaman minimal 5 karakter.");

        // Gabungkan baris yang memilih alat sama
        $item = [];
        foreach ($ids as $i => $aid) {
            $aid = (int)$aid;
            $qty = (int)($qtys[$i] ?? 0);
            if ($aid < 1 || $qty < 1) continue;
            $item[$aid] = ($item[$aid] ?? 0) + $qty;
        }
        if (!$item) throw new Exception("Pilih minimal satu alat dengan jumlah yang benar.");
        ksort($item);

        $conn->begin_transaction();
        $late = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman p JOIN jadwal j ON j.id_jadwal = p.id_jadwal WHERE p.id_user = ? AND " . SQL_TERLAMBAT, "i", [$idUser]);
        if ($late > 0) throw new Exception("Anda masih punya peminjaman yang terlambat. Kembalikan alat terlebih dahulu.");

        $j = qRow($conn, "SELECT tanggal, waktu_selesai, status FROM jadwal WHERE id_jadwal = ? FOR UPDATE", "i", [$idJadwal]);
        if (!$j || $j["status"] !== "tersedia" || strtotime($j["tanggal"] . " " . $j["waktu_selesai"]) < time()) throw new Exception("Jadwal tidak tersedia.");

        foreach ($item as $aid => $qty) {
            $a = qRow($conn, "SELECT nama_alat, jumlah, status FROM alat WHERE id_alat = ? FOR UPDATE", "i", [$aid]);
            if (!$a || $a["status"] !== "tersedia" || $qty > (int)$a["jumlah"]) throw new Exception("Stok " . ($a["nama_alat"] ?? "alat") . " tidak mencukupi.");
        }
        qExec($conn, "INSERT INTO peminjaman (id_user, id_jadwal, tanggal_peminjaman, status_peminjaman, keperluan) VALUES (?, ?, ?, 'menunggu', ?)", "iiss", [$idUser, $idJadwal, $j["tanggal"], $keperluan]);
        $idPinjam = (int)$conn->insert_id;
        foreach ($item as $aid => $qty) {
            qExec($conn, "INSERT INTO detail_peminjaman (id_peminjaman, id_alat, jumlah) VALUES (?, ?, ?)", "iii", [$idPinjam, $aid, $qty]);
        }
        notifikasiPengelola($conn, "Pengajuan baru #$idPinjam dari " . $_SESSION["nama"] . " menunggu persetujuan.");
        buatNotifikasi($conn, $idUser, "Pengajuan #$idPinjam berhasil dikirim dan menunggu persetujuan.");
        catatLog("ajukan", "Peminjaman #$idPinjam");
        $conn->commit();
        notif("Pengajuan #$idPinjam berhasil dikirim.");
    } elseif ($aksi === "batal") {
        $id = (int)($_POST["id"] ?? 0);
        $conn->begin_transaction();
        $p = qRow($conn, "SELECT status_peminjaman FROM peminjaman WHERE id_peminjaman = ? AND id_user = ? FOR UPDATE", "ii", [$id, $idUser]);
        if (!$p || $p["status_peminjaman"] !== "menunggu") throw new Exception("Hanya pengajuan berstatus menunggu yang bisa dibatalkan.");
        qExec($conn, "UPDATE peminjaman SET status_peminjaman = 'dibatalkan' WHERE id_peminjaman = ?", "i", [$id]);
        catatLog("batal", "Peminjaman #$id");
        $conn->commit();
        notif("Pengajuan #$id dibatalkan.");
    } else {
        throw new Exception("Aksi tidak dikenal.");
    }
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $x) {}
    notif($e->getMessage(), "error");
}
ke("peminjaman-saya.php");
