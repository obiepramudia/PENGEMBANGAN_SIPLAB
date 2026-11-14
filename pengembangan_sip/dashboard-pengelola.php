<?php
require_once "auth.php";
wajibRole("pengelola");
$judul = "Dashboard Pengelola";

$pengguna = (int)qVal($conn, "SELECT COUNT(*) FROM users WHERE status = 'aktif'");
$jenis = (int)qVal($conn, "SELECT COUNT(*) FROM alat");
$menunggu = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'menunggu'");
$dipinjam = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE status_peminjaman = 'sedang_dipinjam'");
$late = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman p JOIN jadwal j ON j.id_jadwal = p.id_jadwal WHERE " . SQL_TERLAMBAT);
$rusak = (int)qVal($conn, "SELECT COALESCE(SUM(jumlah_rusak), 0) FROM alat");

$antrian = qAll($conn, SQL_PINJAM . " WHERE p.status_peminjaman = 'menunggu' ORDER BY p.id_peminjaman LIMIT 5");
$siapDiserahkan = qAll($conn, SQL_PINJAM . " WHERE p.status_peminjaman = 'disetujui' ORDER BY p.tanggal_peminjaman, j.waktu_mulai LIMIT 5");
$telat = qAll($conn, SQL_PINJAM . " WHERE " . SQL_TERLAMBAT . " ORDER BY p.tanggal_peminjaman LIMIT 5");
$menipis = qAll($conn, "SELECT nama_alat, jumlah, stok_total FROM alat WHERE stok_total > 0 AND (jumlah <= 2 OR jumlah <= stok_total * 0.2) ORDER BY jumlah, nama_alat LIMIT 6");

$harian = [];
for ($i = 6; $i >= 0; $i--) $harian[date("Y-m-d", strtotime("-$i day"))] = 0;
foreach (qAll($conn, "SELECT DATE(tanggal_pengajuan) d, COUNT(*) n FROM peminjaman WHERE tanggal_pengajuan >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(tanggal_pengajuan)") as $h) {
    if (isset($harian[$h["d"]])) $harian[$h["d"]] = (int)$h["n"];
}
$maks = max(1, max($harian));
$populer = qAll($conn, "SELECT a.nama_alat, SUM(d.jumlah) n FROM detail_peminjaman d JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman JOIN alat a ON a.id_alat = d.id_alat
    WHERE p.status_peminjaman IN ('disetujui','sedang_dipinjam','selesai') AND p.tanggal_peminjaman >= CURDATE() - INTERVAL 30 DAY GROUP BY a.id_alat ORDER BY n DESC LIMIT 5");
$mp = max(1, (int)($populer[0]["n"] ?? 1));
require "includes/header.php";

function barisAksi(array $r, string $aksi, string $teks, string $kelas = ""): void { ?>
<form method="post" action="proses-status.php" class="inline-form"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$r["id_peminjaman"] ?>"><input type="hidden" name="aksi" value="<?= e($aksi) ?>"><button class="sm <?= e($kelas) ?>"><?= e($teks) ?></button></form>
<?php }
?>
<section class="welcome"><h2>Selamat datang, <?= e($_SESSION["nama"]) ?>!</h2><p>Kelola pengguna, alat, jadwal, peminjaman, dan pengembalian dari satu tempat. Tidak perlu membuka database.</p></section>
<div class="grid g6">
  <div class="stat"><span>Pengguna aktif</span><strong><?= $pengguna ?></strong></div>
  <div class="stat"><span>Jenis alat</span><strong><?= $jenis ?></strong></div>
  <div class="stat warn"><span>Menunggu</span><strong><?= $menunggu ?></strong></div>
  <div class="stat"><span>Sedang dipinjam</span><strong><?= $dipinjam ?></strong></div>
  <div class="stat bad"><span>Terlambat</span><strong><?= $late ?></strong></div>
  <div class="stat"><span>Unit rusak</span><strong><?= $rusak ?></strong></div>
</div>

<div class="grid g2" style="align-items:start">
<section class="panel"><div class="panel-head"><h2>Menunggu persetujuan</h2><a class="btn sm secondary" href="data-peminjaman.php?status=menunggu">Lihat semua</a></div>
<?php if ($antrian): ?><ul class="list-plain"><?php foreach ($antrian as $r): ?><li><div><b><a href="detail-peminjaman.php?id=<?= (int)$r["id_peminjaman"] ?>">#<?= (int)$r["id_peminjaman"] ?></a> <?= e($r["nama"]) ?></b><br><small class="muted"><?= e($r["daftar_alat"]) ?> | <?= e(tglIndo($r["tanggal_peminjaman"])) ?></small></div>
  <div class="actions"><?php barisAksi($r, "setujui", "Setujui", "success"); ?></div></li><?php endforeach; ?></ul>
<?php else: ?><div class="empty">Tidak ada pengajuan yang menunggu.</div><?php endif; ?></section>

<section class="panel"><div class="panel-head"><h2>Siap diserahkan</h2></div>
<?php if ($siapDiserahkan): ?><ul class="list-plain"><?php foreach ($siapDiserahkan as $r): ?><li><div><b>#<?= (int)$r["id_peminjaman"] ?> <?= e($r["nama"]) ?></b><br><small class="muted"><?= e($r["daftar_alat"]) ?> | <?= e(tglIndo($r["tanggal_peminjaman"])) ?>, <?= e(jamRange($r)) ?></small></div>
  <div class="actions"><?php barisAksi($r, "mulai", "Serahkan"); ?></div></li><?php endforeach; ?></ul>
<?php else: ?><div class="empty">Tidak ada alat yang menunggu diserahkan.</div><?php endif; ?></section>

<section class="panel"><div class="panel-head"><h2>Terlambat dikembalikan</h2><a class="btn sm secondary" href="pengembalian.php">Proses pengembalian</a></div>
<?php if ($telat): ?><ul class="list-plain"><?php foreach ($telat as $r): ?><li><div><b>#<?= (int)$r["id_peminjaman"] ?> <?= e($r["nama"]) ?></b><br><small class="muted"><?= e($r["daftar_alat"]) ?></small></div><?= badge("terlambat", hariTerlambat($r) . " hari") ?></li><?php endforeach; ?></ul>
<?php else: ?><div class="empty">Tidak ada peminjaman yang terlambat.</div><?php endif; ?></section>

<section class="panel"><div class="panel-head"><h2>Stok menipis</h2><a class="btn sm secondary" href="data-alat.php">Kelola alat</a></div>
<?php if ($menipis): ?><ul class="list-plain"><?php foreach ($menipis as $a): ?><li><span><?= e($a["nama_alat"]) ?></span><span><?= badge((int)$a["jumlah"] === 0 ? "tidak_tersedia" : "menunggu", (int)$a["jumlah"] . " dari " . (int)$a["stok_total"] . " unit") ?></span></li><?php endforeach; ?></ul>
<?php else: ?><div class="empty">Semua stok dalam batas aman.</div><?php endif; ?></section>

<section class="panel"><h2>Pengajuan 7 hari terakhir</h2><div class="chart">
<?php foreach ($harian as $tgl => $n): ?><div class="col"><b><?= $n ?></b><i style="height:<?= round($n / $maks * 100) ?>%"></i><span><?= e(date("d/m", strtotime($tgl))) ?></span></div><?php endforeach; ?></div></section>

<section class="panel"><h2>Alat terpopuler (30 hari)</h2>
<?php if ($populer): foreach ($populer as $a): ?><div class="hbar"><span><?= e($a["nama_alat"]) ?></span><div class="bar"><i style="width:<?= round($a["n"] / $mp * 100) ?>%"></i></div><b><?= (int)$a["n"] ?></b></div><?php endforeach; else: ?><div class="empty">Belum ada data peminjaman.</div><?php endif; ?></section>
</div>
<?php require "includes/footer.php"; ?>
