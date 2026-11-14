<?php
require_once "auth.php";
wajibRole("siswa", "guru");
$id = (int)$_SESSION["id_user"];
$judul = "Peminjaman Saya";

$alat = qAll($conn, "SELECT id_alat, nama_alat, jumlah FROM alat WHERE status = 'tersedia' AND jumlah > 0 ORDER BY nama_alat");
$jadwal = qAll($conn, "SELECT id_jadwal, tanggal, waktu_mulai, waktu_selesai FROM jadwal WHERE status = 'tersedia' AND (tanggal > CURDATE() OR (tanggal = CURDATE() AND waktu_selesai > CURTIME())) ORDER BY tanggal, waktu_mulai");
$pilih = (int)($_GET["alat"] ?? 0);
$jumlahTerlambat = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman p JOIN jadwal j ON j.id_jadwal = p.id_jadwal WHERE p.id_user = ? AND " . SQL_TERLAMBAT, "i", [$id]);

$status = $_GET["status"] ?? "";
$valid = ["menunggu", "disetujui", "sedang_dipinjam", "selesai", "ditolak", "dibatalkan"];
$where = "WHERE p.id_user = ?";
$t = "i";
$pr = [$id];
if (in_array($status, $valid, true)) { $where .= " AND p.status_peminjaman = ?"; $t .= "s"; $pr[] = $status; }
$per = 10;
$hal = halamanSekarang();
$total = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman p $where", $t, $pr);
$riwayat = qAll($conn, SQL_PINJAM . " $where ORDER BY p.id_peminjaman DESC LIMIT $per OFFSET " . (($hal - 1) * $per), $t, $pr);

require "includes/header.php";
?>
<?php if ($jumlahTerlambat): ?><div class="alert error">Anda punya peminjaman yang terlambat dikembalikan. Pengajuan baru belum bisa dikirim sampai alat dikembalikan.</div><?php endif; ?>
<section class="form-card"><h2>Ajukan peminjaman baru</h2>
<?php if (!$alat || !$jadwal): ?>
  <div class="empty"><?= !$alat ? "Belum ada alat yang tersedia." : "Belum ada jadwal yang tersedia. Hubungi pengelola lab." ?></div>
<?php else: ?>
<form method="post" action="proses-peminjaman.php">
  <?= csrfField() ?><input type="hidden" name="aksi" value="ajukan">
  <div class="form-grid">
    <div class="field"><label>Jadwal peminjaman</label><select name="id_jadwal" required><option value="">Pilih jadwal</option>
      <?php foreach ($jadwal as $j): ?><option value="<?= (int)$j["id_jadwal"] ?>"><?= e(tglIndo($j["tanggal"])) ?> | <?= e(jamRange($j)) ?></option><?php endforeach; ?></select>
      <small>Alat harus kembali sebelum jam selesai jadwal.</small></div>
    <div class="field"><label>Keperluan</label><input name="keperluan" required minlength="5" maxlength="255" placeholder="Contoh: Praktikum IPA bab pengukuran"></div>
  </div>
  <label style="font-weight:700;font-size:12px;color:#465873;display:block;margin-bottom:8px">Alat yang dipinjam</label>
  <div id="item-wadah">
    <div class="item-row">
      <select name="id_alat[]" required><option value="">Pilih alat</option>
        <?php foreach ($alat as $a): ?><option value="<?= (int)$a["id_alat"] ?>" data-stok="<?= (int)$a["jumlah"] ?>" <?= $pilih === (int)$a["id_alat"] ? "selected" : "" ?>><?= e($a["nama_alat"]) ?> (stok <?= (int)$a["jumlah"] ?>)</option><?php endforeach; ?></select>
      <input type="number" name="jumlah[]" min="1" value="1" required>
      <button type="button" class="secondary hapus-item">Hapus</button>
    </div>
  </div>
  <p><button type="button" class="secondary" id="tambah-item">Tambah alat lain</button></p>
  <button type="submit" <?= $jumlahTerlambat ? "disabled" : "" ?>>Kirim pengajuan</button>
</form>
<?php endif; ?>
</section>

<section class="panel"><div class="panel-head"><h2>Riwayat pengajuan</h2>
  <form method="get" class="filters" style="margin:0"><div><select name="status" onchange="this.form.submit()"><option value="">Semua status</option>
    <?php foreach ($valid as $v): ?><option value="<?= $v ?>" <?= $status === $v ? "selected" : "" ?>><?= e(label($v)) ?></option><?php endforeach; ?></select></div></form></div>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Alat</th><th>Jadwal</th><th>Keperluan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if ($riwayat): foreach ($riwayat as $r): ?><tr class="<?= terlambat($r) ? "row-late" : "" ?>">
<td>#<?= (int)$r["id_peminjaman"] ?></td><td><?= e($r["daftar_alat"] ?: "-") ?></td>
<td><?= e(tglIndo($r["tanggal_peminjaman"])) ?><br><small class="muted"><?= e(jamRange($r)) ?></small></td>
<td><?= e($r["keperluan"] ?: "-") ?><?php if ($r["catatan_pengelola"]): ?><br><small class="muted">Catatan pengelola: <?= e($r["catatan_pengelola"]) ?></small><?php endif; ?></td>
<td><?= statusTampil($r) ?></td>
<td><div class="actions"><a class="btn sm secondary" href="detail-peminjaman.php?id=<?= (int)$r["id_peminjaman"] ?>">Detail</a>
<?php if ($r["status_peminjaman"] === "menunggu"): ?><form method="post" action="proses-peminjaman.php" class="inline-form" data-confirm="Batalkan pengajuan ini?"><?= csrfField() ?><input type="hidden" name="aksi" value="batal"><input type="hidden" name="id" value="<?= (int)$r["id_peminjaman"] ?>"><button class="sm danger">Batalkan</button></form><?php endif; ?></div></td></tr>
<?php endforeach; else: ?><tr><td colspan="6" class="empty">Belum ada riwayat.</td></tr><?php endif; ?>
</tbody></table></div><?= paginasi($total, $per, $hal) ?></section>
<?php require "includes/footer.php"; ?>
