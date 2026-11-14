<?php
require_once "auth.php";
wajibRole("guru", "pengelola");

$dari = $_GET["dari"] ?? date("Y-m-01");
$sampai = $_GET["sampai"] ?? date("Y-m-d");
$status = $_GET["status"] ?? "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date("Y-m-01");
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date("Y-m-d");
$valid = ["menunggu", "disetujui", "sedang_dipinjam", "selesai", "ditolak", "dibatalkan"];

$where = "WHERE p.tanggal_peminjaman BETWEEN ? AND ?";
$t = "ss";
$p = [$dari, $sampai];
if (in_array($status, $valid, true)) { $where .= " AND p.status_peminjaman = ?"; $t .= "s"; $p[] = $status; }

$rows = qAll($conn, SQL_PINJAM . " $where ORDER BY p.tanggal_peminjaman, p.id_peminjaman", $t, $p);

if (isset($_GET["csv"])) {
    catatLog("ekspor_laporan", "$dari s.d. $sampai");
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=laporan-peminjaman-$dari-sd-$sampai.csv");
    $out = fopen("php://output", "w");
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ["ID", "Peminjam", "Role", "Alat", "Tanggal", "Jam", "Keperluan", "Status"]);
    foreach ($rows as $r) fputcsv($out, [$r["id_peminjaman"], $r["nama"], $r["role"], $r["daftar_alat"], $r["tanggal_peminjaman"], jamRange($r), $r["keperluan"], terlambat($r) ? "terlambat" : $r["status_peminjaman"]]);
    fclose($out);
    exit;
}

$ringkas = array_fill_keys($valid, 0);
$telat = 0;
foreach ($rows as $r) {
    $ringkas[$r["status_peminjaman"]] = ($ringkas[$r["status_peminjaman"]] ?? 0) + 1;
    if (terlambat($r)) $telat++;
}
$rekap = qAll($conn, "SELECT a.nama_alat, a.kode_alat, SUM(d.jumlah) total, COUNT(DISTINCT p.id_peminjaman) kali
    FROM detail_peminjaman d JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman JOIN alat a ON a.id_alat = d.id_alat
    WHERE p.tanggal_peminjaman BETWEEN ? AND ? AND p.status_peminjaman IN ('disetujui','sedang_dipinjam','selesai')
    GROUP BY a.id_alat, a.nama_alat, a.kode_alat ORDER BY total DESC", "ss", [$dari, $sampai]);
$kondisi = qAll($conn, "SELECT k.kondisi_kembali, COUNT(*) n, COALESCE(SUM(k.jumlah_rusak), 0) unit FROM pengembalian k JOIN peminjaman p ON p.id_peminjaman = k.id_peminjaman WHERE p.tanggal_peminjaman BETWEEN ? AND ? GROUP BY k.kondisi_kembali", "ss", [$dari, $sampai]);

$judul = "Laporan Peminjaman";
require "includes/header.php";
?>
<form class="filters panel" method="get">
  <div><label>Dari tanggal</label><input type="date" name="dari" value="<?= e($dari) ?>"></div>
  <div><label>Sampai tanggal</label><input type="date" name="sampai" value="<?= e($sampai) ?>"></div>
  <div><label>Status</label><select name="status"><option value="">Semua status</option><?php foreach ($valid as $v): ?><option value="<?= $v ?>" <?= $status === $v ? "selected" : "" ?>><?= e(label($v)) ?></option><?php endforeach; ?></select></div>
  <div class="fit"><button>Tampilkan</button><a class="btn secondary" href="?<?= e(http_build_query(["dari" => $dari, "sampai" => $sampai, "status" => $status, "csv" => 1])) ?>">Unduh CSV</a><button type="button" class="secondary js-print">Cetak</button></div>
</form>
<p class="muted">Periode: <?= e(tglIndo($dari)) ?> sampai <?= e(tglIndo($sampai)) ?>. Dicetak oleh <?= e($_SESSION["nama"]) ?> pada <?= e(tglIndo(date("Y-m-d H:i:s"), true)) ?>.</p>
<div class="grid g6">
  <div class="stat"><span>Total pengajuan</span><strong><?= count($rows) ?></strong></div>
  <div class="stat warn"><span>Menunggu</span><strong><?= $ringkas["menunggu"] ?></strong></div>
  <div class="stat"><span>Aktif</span><strong><?= $ringkas["disetujui"] + $ringkas["sedang_dipinjam"] ?></strong></div>
  <div class="stat"><span>Selesai</span><strong><?= $ringkas["selesai"] ?></strong></div>
  <div class="stat bad"><span>Ditolak atau batal</span><strong><?= $ringkas["ditolak"] + $ringkas["dibatalkan"] ?></strong></div>
  <div class="stat bad"><span>Terlambat</span><strong><?= $telat ?></strong></div>
</div>
<div class="grid g2" style="align-items:start">
<section class="panel"><h2>Rekap pemakaian alat</h2><div class="table-wrap"><table><thead><tr><th>Alat</th><th>Kali dipinjam</th><th>Total unit</th></tr></thead><tbody>
<?php if ($rekap): foreach ($rekap as $a): ?><tr><td><?= e($a["nama_alat"]) ?><br><small class="muted"><?= e($a["kode_alat"]) ?></small></td><td><?= (int)$a["kali"] ?></td><td><?= (int)$a["total"] ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="3" class="empty">Tidak ada data.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="panel"><h2>Kondisi saat pengembalian</h2><div class="table-wrap"><table><thead><tr><th>Kondisi</th><th>Transaksi</th><th>Unit rusak</th></tr></thead><tbody>
<?php if ($kondisi): foreach ($kondisi as $k): ?><tr><td><?= badge($k["kondisi_kembali"]) ?></td><td><?= (int)$k["n"] ?></td><td><?= (int)$k["unit"] ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="3" class="empty">Belum ada pengembalian pada periode ini.</td></tr><?php endif; ?></tbody></table></div></section>
</div>
<section class="panel"><h2>Rincian transaksi</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>Peminjam</th><th>Alat</th><th>Tanggal</th><th>Keperluan</th><th>Status</th></tr></thead><tbody>
<?php if ($rows): foreach ($rows as $r): ?><tr><td>#<?= (int)$r["id_peminjaman"] ?></td><td><?= e($r["nama"]) ?><br><small class="muted"><?= e(ucfirst($r["role"])) ?></small></td><td><?= e($r["daftar_alat"]) ?></td><td><?= e(tglIndo($r["tanggal_peminjaman"])) ?><br><small class="muted"><?= e(jamRange($r)) ?></small></td><td><?= e($r["keperluan"] ?: "-") ?></td><td><?= statusTampil($r) ?></td></tr>
<?php endforeach; else: ?><tr><td colspan="6" class="empty">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require "includes/footer.php"; ?>
