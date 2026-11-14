<?php
require_once "auth.php";
wajibRole("siswa", "guru", "pengelola");
$id = (int)($_GET["id"] ?? 0);
$r = qRow($conn, SQL_PINJAM . " WHERE p.id_peminjaman = ?", "i", [$id]);
if (!$r || ($_SESSION["role"] === "siswa" && (int)$r["id_user"] !== (int)$_SESSION["id_user"])) {
    notif("Data peminjaman tidak ditemukan.", "error");
    ke(tujuanDashboard($_SESSION["role"]));
}
$items = qAll($conn, "SELECT a.kode_alat, a.nama_alat, a.kategori, d.jumlah FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat WHERE d.id_peminjaman = ?", "i", [$id]);
$kembali = qRow($conn, "SELECT * FROM pengembalian WHERE id_peminjaman = ? ORDER BY id_pengembalian DESC LIMIT 1", "i", [$id]);
$kontak = qRow($conn, "SELECT email, no_hp FROM users WHERE id_user = ?", "i", [(int)$r["id_user"]]);
$judul = "Detail Peminjaman #" . $id;
require "includes/header.php";
?>
<section class="panel">
  <div class="panel-head"><h2>Peminjaman #<?= $id ?> <?= statusTampil($r) ?></h2>
    <div class="actions no-print"><button type="button" class="secondary js-print">Cetak bukti</button><a class="btn secondary" href="javascript:history.back()">Kembali</a></div></div>
  <dl class="detail-grid">
    <div><dt>Peminjam</dt><dd><?= e($r["nama"]) ?> (<?= e(ucfirst($r["role"])) ?>)</dd></div>
    <div><dt>Kontak</dt><dd><?= e(($kontak["email"] ?? "") ?: "-") ?> | <?= e(($kontak["no_hp"] ?? "") ?: "-") ?></dd></div>
    <div><dt>Tanggal pengajuan</dt><dd><?= e(tglIndo($r["tanggal_pengajuan"], true)) ?></dd></div>
    <div><dt>Jadwal pinjam</dt><dd><?= e(tglIndo($r["tanggal_peminjaman"])) ?>, <?= e(jamRange($r)) ?></dd></div>
    <div><dt>Keperluan</dt><dd><?= e($r["keperluan"] ?: "-") ?></dd></div>
    <div><dt>Batas pengembalian</dt><dd><?= e(tglIndo(date("Y-m-d H:i:s", jatuhTempo($r)), true)) ?></dd></div>
    <?php if ($r["catatan_pengelola"]): ?><div><dt>Catatan pengelola</dt><dd><?= e($r["catatan_pengelola"]) ?></dd></div><?php endif; ?>
    <?php if (terlambat($r)): ?><div><dt>Keterlambatan</dt><dd style="color:#b91c1c"><?= hariTerlambat($r) ?> hari</dd></div><?php endif; ?>
  </dl>
</section>
<section class="panel"><h2>Daftar alat</h2><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Nama alat</th><th>Kategori</th><th>Jumlah</th></tr></thead><tbody>
<?php foreach ($items as $it): ?><tr><td><?= e($it["kode_alat"]) ?></td><td><?= e($it["nama_alat"]) ?></td><td><?= e($it["kategori"]) ?></td><td><?= (int)$it["jumlah"] ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php if ($kembali): ?>
<section class="panel"><h2>Pengembalian</h2><dl class="detail-grid">
  <div><dt>Waktu kembali</dt><dd><?= e(tglIndo($kembali["tanggal_pengembalian"], true)) ?></dd></div>
  <div><dt>Kondisi</dt><dd><?= badge($kembali["kondisi_kembali"]) ?></dd></div>
  <div><dt>Jumlah rusak</dt><dd><?= (int)$kembali["jumlah_rusak"] ?></dd></div>
  <div><dt>Catatan</dt><dd><?= e($kembali["catatan"] ?: "-") ?></dd></div>
</dl></section>
<?php endif; ?>
<?php require "includes/footer.php"; ?>
