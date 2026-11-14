<?php
require_once "auth.php";
wajibRole("pengelola");
wajibPost("data-alat.php");
$aksi = $_POST["aksi"] ?? "";
$id = (int)($_POST["id"] ?? 0);
$balik = "data-alat.php";
$fotoBaru = null;

function bacaForm(): array {
    $nama = trim($_POST["nama_alat"] ?? "");
    $kode = trim($_POST["kode_alat"] ?? "");
    $kategori = trim($_POST["kategori"] ?? "") ?: "Umum";
    $lokasi = trim($_POST["lokasi"] ?? "");
    $desk = trim($_POST["deskripsi"] ?? "");
    $total = (int)($_POST["stok_total"] ?? -1);
    $kondisi = $_POST["kondisi"] ?? "baik";
    if ($nama === "" || $total < 0 || !in_array($kondisi, ["baik", "rusak_ringan", "rusak_berat"], true)) throw new Exception("Data alat tidak valid.");
    return [$nama, $kode, $kategori, $lokasi, $desk, $total, $kondisi];
}

try {
    if ($aksi === "tambah") {
        [$nama, $kode, $kategori, $lokasi, $desk, $total, $kondisi] = bacaForm();
        $fotoBaru = simpanFoto($_FILES["foto"] ?? []);
        $status = $total > 0 ? "tersedia" : "tidak_tersedia";
        qExec($conn, "INSERT INTO alat (nama_alat, jumlah, kondisi, status, stok_total, kategori, lokasi, deskripsi, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "sississss", [$nama, $total, $kondisi, $status, $total, $kategori, $lokasi ?: null, $desk ?: null, $fotoBaru]);
        $idBaru = (int)$conn->insert_id;
        if ($kode === "") $kode = "ALT-" . str_pad((string)$idBaru, 3, "0", STR_PAD_LEFT);
        qExec($conn, "UPDATE alat SET kode_alat = ? WHERE id_alat = ?", "si", [$kode, $idBaru]);
        catatLog("alat_tambah", $nama);
        notif("Alat berhasil ditambahkan.");
    } elseif ($aksi === "ubah") {
        [$nama, $kode, $kategori, $lokasi, $desk, $total, $kondisi] = bacaForm();
        $conn->begin_transaction();
        $a = qRow($conn, "SELECT jumlah_rusak, foto, kode_alat FROM alat WHERE id_alat = ? FOR UPDATE", "i", [$id]);
        if (!$a) throw new Exception("Alat tidak ditemukan.");
        $sedia = $total - (int)$a["jumlah_rusak"] - jumlahDipinjam($conn, $id);
        if ($sedia < 0) throw new Exception("Total unit lebih kecil dari jumlah unit yang rusak dan sedang dipinjam.");
        $fotoBaru = simpanFoto($_FILES["foto"] ?? []);
        $fotoPakai = $fotoBaru ?: $a["foto"];
        if ($kode === "") $kode = $a["kode_alat"] ?: "ALT-" . str_pad((string)$id, 3, "0", STR_PAD_LEFT);
        qExec($conn, "UPDATE alat SET nama_alat = ?, kode_alat = ?, kategori = ?, lokasi = ?, deskripsi = ?, kondisi = ?, stok_total = ?, jumlah = ?, foto = ? WHERE id_alat = ?",
            "ssssssiisi", [$nama, $kode, $kategori, $lokasi ?: null, $desk ?: null, $kondisi, $total, $sedia, $fotoPakai, $id]);
        segarkanStatusAlat($conn, $id);
        $conn->commit();
        if ($fotoBaru) hapusFoto($a["foto"]);
        catatLog("alat_ubah", "$nama (#$id)");
        notif("Data alat berhasil diperbarui.");
    } elseif ($aksi === "perbaiki") {
        $jml = (int)($_POST["jumlah"] ?? 0);
        $conn->begin_transaction();
        $a = qRow($conn, "SELECT nama_alat, jumlah_rusak FROM alat WHERE id_alat = ? FOR UPDATE", "i", [$id]);
        if (!$a || $jml < 1 || $jml > (int)$a["jumlah_rusak"]) throw new Exception("Jumlah perbaikan tidak valid.");
        qExec($conn, "UPDATE alat SET jumlah_rusak = jumlah_rusak - ?, jumlah = jumlah + ? WHERE id_alat = ?", "iii", [$jml, $jml, $id]);
        segarkanStatusAlat($conn, $id);
        $conn->commit();
        catatLog("alat_perbaiki", $a["nama_alat"] . " x$jml");
        notif("$jml unit dipindahkan ke stok tersedia.");
        $balik = "data-alat.php?edit=$id";
    } elseif ($aksi === "hapus") {
        $a = qRow($conn, "SELECT nama_alat, foto FROM alat WHERE id_alat = ?", "i", [$id]);
        if (!$a) throw new Exception("Alat tidak ditemukan.");
        if ((int)qVal($conn, "SELECT COUNT(*) FROM detail_peminjaman WHERE id_alat = ?", "i", [$id]) > 0) throw new Exception("Alat sudah punya riwayat transaksi, jadi tidak bisa dihapus. Ubah total unit menjadi 0 jika alat tidak dipakai lagi.");
        qExec($conn, "DELETE FROM alat WHERE id_alat = ?", "i", [$id]);
        hapusFoto($a["foto"]);
        catatLog("alat_hapus", $a["nama_alat"]);
        notif("Alat berhasil dihapus.");
    } else {
        throw new Exception("Aksi tidak dikenal.");
    }
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $x) {}
    if ($fotoBaru) hapusFoto($fotoBaru);
    notif($e->getMessage(), "error");
    if ($aksi === "ubah") $balik = "data-alat.php?edit=$id";
}
ke($balik);
