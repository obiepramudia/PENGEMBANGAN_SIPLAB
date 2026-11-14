<?php
require_once "auth.php";
wajibRole("pengelola");
$judul = "Pengembalian Alat";

$aktif = qAll($conn, SQL_PINJAM . " WHERE p.status_peminjaman = 'sedang_dipinjam' ORDER BY p.tanggal_peminjaman, j.waktu_selesai");
$detail = [];
foreach (qAll($conn, "SELECT d.id_peminjaman, d.id_alat, d.jumlah, a.nama_alat FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman WHERE p.status_peminjaman = 'sedang_dipinjam'") as $d) {
    $detail[(int)$d["id_peminjaman"]][] = $d;
}
$riwayat = qAll($conn, "SELECT k.tanggal_pengembalian, k.kondisi_kembali, k.jumlah_rusak, k.catatan, p.id_peminjaman, u.nama,
    (SELECT GROUP_CONCAT(CONCAT(a.nama_alat, ' (', d.jumlah, ')') SEPARATOR ', ') FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat WHERE d.id_peminjaman = p.id_peminjaman) AS daftar_alat
    FROM pengembalian k JOIN peminjaman p ON p.id_peminjaman = k.id_peminjaman JOIN users u ON u.id_user = p.id_user ORDER BY k.tanggal_pengembalian DESC LIMIT 15");
require "includes/header.php";
?>
<section class="panel"><h2>Alat yang sedang dipinjam (<?= count($aktif) ?>)</h2>
<?php if (!$aktif): ?><div class="empty">Tidak ada alat yang menunggu pengembalian.</div><?php endif; ?>
<?php foreach ($aktif as $r): $id = (int)$r["id_peminjaman"]; $tl = terlambat($r); ?>
<div class="loan-card <?= $tl ? "late" : "" ?>">
  <div class="loan-head"><div><b>#<?= $id ?> | <?= e($r["nama"]) ?></b> <small class="muted">(<?= e(ucfirst($r["role"])) ?>)</small><br>
    <small class="muted">Batas kembali: <?= e(tglIndo(date("Y-m-d H:i:s", jatuhTempo($r)), true)) ?></small></div>
    <div><?= statusTampil($r) ?><?= $tl ? " <small class='muted'>" . hariTerlambat($r) . " hari</small>" : "" ?></div></div>
  <form method="post" action="proses-pengembalian.php">
    <?= csrfField() ?><input type="hidden" name="aksi" value="kembali"><input type="hidden" name="id" value="<?= $id ?>">
    <div class="loan-items">
      <?php foreach ($detail[$id] ?? [] as $d): ?>
        <div class="li"><span><?= e($d["nama_alat"]) ?> <b>x<?= (int)$d["jumlah"] ?></b></span>
          <input type="number" name="rusak[<?= (int)$d["id_alat"] ?>]" min="0" max="<?= (int)$d["jumlah"] ?>" value="0" title="Jumlah unit rusak" placeholder="Rusak"></div>
      <?php endforeach; ?>
    </div>
    <small class="muted">Isi kolom di kanan dengan jumlah unit yang rusak. Biarkan 0 jika semua baik.</small>
    <div class="form-grid" style="margin-top:10px">
      <div class="field"><label>Kondisi saat kembali</label><select name="kondisi"><option value="baik">Baik</option><option value="rusak_ringan">Rusak ringan</option><option value="rusak_berat">Rusak berat</option></select></div>
      <div class="field"><label>Catatan (opsional)</label><input name="catatan" maxlength="250" placeholder="Contoh: kabel putus"></div>
    </div>
    <div class="actions"><button class="success">Catat pengembalian</button></div>
  </form>
  <?php if ($tl): ?>
  <form method="post" action="proses-pengembalian.php" style="margin-top:8px"><?= csrfField() ?><input type="hidden" name="aksi" value="ingatkan"><input type="hidden" name="id" value="<?= $id ?>"><button class="sm secondary">Kirim pengingat ke peminjam</button></form>
  <?php endif; ?>
</div>
<?php endforeach; ?></section>

<section class="panel"><h2>Riwayat pengembalian terbaru</h2><div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Peminjam</th><th>Alat</th><th>Waktu kembali</th><th>Kondisi</th><th>Catatan</th></tr></thead><tbody>
<?php if ($riwayat): foreach ($riwayat as $k): ?><tr><td><a href="detail-peminjaman.php?id=<?= (int)$k["id_peminjaman"] ?>">#<?= (int)$k["id_peminjaman"] ?></a></td><td><?= e($k["nama"]) ?></td><td><?= e($k["daftar_alat"]) ?></td><td><?= e(tglIndo($k["tanggal_pengembalian"], true)) ?></td><td><?= badge($k["kondisi_kembali"]) ?><?= (int)$k["jumlah_rusak"] > 0 ? "<br><small class='muted'>" . (int)$k["jumlah_rusak"] . " unit rusak</small>" : "" ?></td><td><?= e($k["catatan"] ?: "-") ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="6" class="empty">Belum ada pengembalian.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require "includes/footer.php"; ?>
