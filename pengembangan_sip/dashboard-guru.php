<?php
require_once "auth.php";
wajibRole("guru");
$judul = "Dashboard Guru";
$total = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman");
$pending = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'menunggu'");
$aktif = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman IN ('disetujui','sedang_dipinjam')");
$late = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman p JOIN jadwal j ON j.id_jadwal = p.id_jadwal WHERE " . SQL_TERLAMBAT);
$saya = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE id_user = ?", "i", [(int)$_SESSION["id_user"]]);
$terbaru = qAll($conn, SQL_PINJAM . " ORDER BY p.id_peminjaman DESC LIMIT 8");
require "includes/header.php";
?>
<section class="welcome"><h2>Selamat datang, <?= e($_SESSION["nama"]) ?>.</h2><p>Pantau aktivitas peminjaman alat, atau ajukan peminjaman untuk kegiatan belajar mengajar Anda.</p></section>
<div class="grid g5">
  <div class="stat"><span>Seluruh pengajuan</span><strong><?= $total ?></strong></div>
  <div class="stat warn"><span>Menunggu</span><strong><?= $pending ?></strong></div>
  <div class="stat"><span>Aktif</span><strong><?= $aktif ?></strong></div>
  <div class="stat bad"><span>Terlambat</span><strong><?= $late ?></strong></div>
  <div class="stat"><span>Pengajuan saya</span><strong><?= $saya ?></strong></div>
</div>
<section class="panel"><div class="panel-head"><h2>Aktivitas terbaru</h2><div class="actions"><a class="btn" href="peminjaman-saya.php">Ajukan peminjaman</a><a class="btn secondary" href="data-peminjaman.php">Semua peminjaman</a><a class="btn secondary" href="laporan.php">Laporan</a></div></div>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Peminjam</th><th>Alat</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>
<?php if ($terbaru): foreach ($terbaru as $r): ?><tr><td><a href="detail-peminjaman.php?id=<?= (int)$r["id_peminjaman"] ?>">#<?= (int)$r["id_peminjaman"] ?></a></td><td><?= e($r["nama"]) ?></td><td><?= e($r["daftar_alat"] ?: "-") ?></td><td><?= e(tglIndo($r["tanggal_peminjaman"])) ?></td><td><?= statusTampil($r) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="empty">Belum ada data.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require "includes/footer.php"; ?>
