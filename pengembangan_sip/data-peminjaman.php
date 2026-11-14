<?php
require_once "auth.php";
wajibRole("guru", "pengelola");
$admin = $_SESSION["role"] === "pengelola";
$judul = $admin ? "Data Peminjaman" : "Pemantauan Peminjaman";

$q = trim($_GET["q"] ?? "");
$status = $_GET["status"] ?? "";
$dari = $_GET["dari"] ?? "";
$sampai = $_GET["sampai"] ?? "";
$valid = ["menunggu", "disetujui", "sedang_dipinjam", "selesai", "ditolak", "dibatalkan"];

$w = [];
$t = "";
$p = [];
if ($q !== "") {
    $w[] = "(u.nama LIKE ? OR p.id_peminjaman = ? OR p.keperluan LIKE ? OR EXISTS (SELECT 1 FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat WHERE d.id_peminjaman = p.id_peminjaman AND a.nama_alat LIKE ?))";
    $like = "%" . $q . "%";
    $t .= "siss";
    array_push($p, $like, (int)ltrim($q, "#"), $like, $like);
}
if ($status === "terlambat") $w[] = SQL_TERLAMBAT;
elseif (in_array($status, $valid, true)) { $w[] = "p.status_peminjaman = ?"; $t .= "s"; $p[] = $status; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) { $w[] = "p.tanggal_peminjaman >= ?"; $t .= "s"; $p[] = $dari; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) { $w[] = "p.tanggal_peminjaman <= ?"; $t .= "s"; $p[] = $sampai; }
$where = $w ? "WHERE " . implode(" AND ", $w) : "";

$per = 15;
$hal = halamanSekarang();
$total = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman p JOIN users u ON u.id_user = p.id_user JOIN jadwal j ON j.id_jadwal = p.id_jadwal $where", $t, $p);
$rows = qAll($conn, SQL_PINJAM . " $where ORDER BY p.id_peminjaman DESC LIMIT $per OFFSET " . (($hal - 1) * $per), $t, $p);

$hitung = [];
foreach (qAll($conn, "SELECT status_peminjaman s, COUNT(*) n FROM peminjaman GROUP BY status_peminjaman") as $h) $hitung[$h["s"]] = (int)$h["n"];
$hitung["terlambat"] = (int)qVal($conn, "SELECT COUNT(*) FROM peminjaman p JOIN jadwal j ON j.id_jadwal = p.id_jadwal WHERE " . SQL_TERLAMBAT);

require "includes/header.php";
$qs = fn(string $s) => "?" . http_build_query(array_filter(["status" => $s, "q" => $q, "dari" => $dari, "sampai" => $sampai]));
?>
<div class="chips">
  <a class="<?= $status === "" ? "on" : "" ?>" href="<?= e($qs("")) ?>">Semua<b><?= array_sum(array_diff_key($hitung, ["terlambat" => 1])) ?></b></a>
  <?php foreach (array_merge($valid, ["terlambat"]) as $s): ?>
    <a class="<?= $status === $s ? "on" : "" ?>" href="<?= e($qs($s)) ?>"><?= e(label($s)) ?><b><?= (int)($hitung[$s] ?? 0) ?></b></a>
  <?php endforeach; ?>
</div>
<form class="filters panel" method="get">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <div style="flex:2"><label>Cari</label><input name="q" value="<?= e($q) ?>" placeholder="Nama peminjam, ID, alat, atau keperluan"></div>
  <div><label>Dari tanggal</label><input type="date" name="dari" value="<?= e($dari) ?>"></div>
  <div><label>Sampai tanggal</label><input type="date" name="sampai" value="<?= e($sampai) ?>"></div>
  <div class="fit"><button>Terapkan</button><a class="btn secondary" href="data-peminjaman.php">Atur ulang</a></div>
</form>
<section class="panel"><h2><?= $admin ? "Pengajuan dan persetujuan" : "Pemantauan seluruh peminjaman" ?></h2><div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Peminjam</th><th>Alat</th><th>Jadwal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if ($rows): foreach ($rows as $r): $id = (int)$r["id_peminjaman"]; ?>
<tr class="<?= terlambat($r) ? "row-late" : "" ?>">
<td>#<?= $id ?></td>
<td><?= e($r["nama"]) ?><br><small class="muted"><?= e(ucfirst($r["role"])) ?></small></td>
<td><?= e($r["daftar_alat"] ?: "-") ?><?php if ($r["keperluan"]): ?><br><small class="muted"><?= e($r["keperluan"]) ?></small><?php endif; ?></td>
<td><?= e(tglIndo($r["tanggal_peminjaman"])) ?><br><small class="muted"><?= e(jamRange($r)) ?></small></td>
<td><?= statusTampil($r) ?><?php if (terlambat($r)): ?><br><small class="muted"><?= hariTerlambat($r) ?> hari</small><?php endif; ?></td>
<td><div class="actions"><a class="btn sm secondary" href="detail-peminjaman.php?id=<?= $id ?>">Detail</a>
<?php if ($admin): ?>
  <?php if ($r["status_peminjaman"] === "menunggu"): ?>
    <form class="inline-form" method="post" action="proses-status.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="setujui"><button class="sm success">Setujui</button></form>
    <form class="inline-form" method="post" action="proses-status.php" data-prompt="Tulis alasan penolakan (boleh dikosongkan):" data-prompt-field="catatan"><?= csrfField() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="tolak"><input type="hidden" name="catatan" value=""><button class="sm danger">Tolak</button></form>
  <?php elseif ($r["status_peminjaman"] === "disetujui"): ?>
    <form class="inline-form" method="post" action="proses-status.php" data-confirm="Alat sudah diserahkan ke peminjam?"><?= csrfField() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="mulai"><button class="sm">Serahkan alat</button></form>
    <form class="inline-form" method="post" action="proses-status.php" data-confirm="Batalkan peminjaman ini dan kembalikan stok?"><?= csrfField() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="aksi" value="batalkan"><button class="sm danger">Batalkan</button></form>
  <?php endif; ?>
<?php endif; ?></div></td></tr>
<?php endforeach; else: ?><tr><td colspan="6" class="empty">Tidak ada data yang cocok.</td></tr><?php endif; ?>
</tbody></table></div><?= paginasi($total, $per, $hal) ?></section>
<?php require "includes/footer.php"; ?>
