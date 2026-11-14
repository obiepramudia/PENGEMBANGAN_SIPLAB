<?php
require_once "auth.php";
wajibRole("siswa");
$id = (int)$_SESSION["id_user"];
$judul = "Dashboard Siswa";

$total = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE id_user = ?", "i", [$id]);
$menunggu = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE id_user = ? AND status_peminjaman = 'menunggu'", "i", [$id]);
$aktifRows = qAll($conn, SQL_PINJAM . " WHERE p.id_user = ? AND p.status_peminjaman IN ('disetujui','sedang_dipinjam') ORDER BY p.tanggal_peminjaman", "i", [$id]);
$tersedia = (int)qVal($conn, "SELECT COUNT(*) FROM alat WHERE status = 'tersedia' AND jumlah > 0");
$late = array_filter($aktifRows, "terlambat");
$terbaru = qAll($conn, SQL_PINJAM . " WHERE p.id_user = ? ORDER BY p.id_peminjaman DESC LIMIT 6", "i", [$id]);

require "includes/header.php";
?>
<section class="welcome"><h2>Halo, <?= e($_SESSION["nama"]) ?>!</h2><p>Ajukan peminjaman alat pembelajaran dan pantau status pengajuan Anda di sini.</p></section>
<?php if ($late): ?><div class="alert error">Anda punya <?= count($late) ?> peminjaman yang melewati batas waktu. Kembalikan alat ke pengelola sekarang. Pengajuan baru ditahan sampai alat kembali.</div><?php endif; ?>
<div class="grid">
  <div class="stat"><span>Total pengajuan</span><strong><?= $total ?></strong></div>
  <div class="stat warn"><span>Menunggu persetujuan</span><strong><?= $menunggu ?></strong></div>
  <div class="stat"><span>Peminjaman aktif</span><strong><?= count($aktifRows) ?></strong></div>
  <div class="stat"><span>Jenis alat tersedia</span><strong><?= $tersedia ?></strong></div>
</div>
<?php if ($aktifRows): ?>
<section class="panel"><h2>Alat yang sedang Anda pegang atau akan diambil</h2><div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Alat</th><th>Jadwal</th><th>Batas kembali</th><th>Status</th></tr></thead><tbody>
<?php foreach ($aktifRows as $r): ?><tr class="<?= terlambat($r) ? "row-late" : "" ?>">
<td><a href="detail-peminjaman.php?id=<?= (int)$r["id_peminjaman"] ?>">#<?= (int)$r["id_peminjaman"] ?></a></td><td><?= e($r["daftar_alat"]) ?></td>
<td><?= e(tglIndo($r["tanggal_peminjaman"])) ?><br><small class="muted"><?= e(jamRange($r)) ?></small></td>
<td><?= e(date("j M Y, H:i", jatuhTempo($r))) ?></td><td><?= statusTampil($r) ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php endif; ?>
<section class="panel"><div class="panel-head"><h2>Pengajuan terbaru</h2><div class="actions"><a class="btn" href="peminjaman-saya.php">Ajukan peminjaman</a><a class="btn secondary" href="daftar-alat.php">Lihat katalog</a></div></div>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Alat</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>
<?php if ($terbaru): foreach ($terbaru as $r): ?><tr><td><a href="detail-peminjaman.php?id=<?= (int)$r["id_peminjaman"] ?>">#<?= (int)$r["id_peminjaman"] ?></a></td><td><?= e($r["daftar_alat"] ?: "-") ?></td><td><?= e(tglIndo($r["tanggal_peminjaman"])) ?></td><td><?= statusTampil($r) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="4" class="empty">Belum ada pengajuan peminjaman.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php require "includes/footer.php"; ?>
