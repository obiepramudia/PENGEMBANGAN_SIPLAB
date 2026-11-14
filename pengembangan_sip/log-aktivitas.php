<?php
require_once "auth.php";
wajibRole("pengelola");
$judul = "Log Aktivitas";

$q = trim($_GET["q"] ?? "");
$aksi = trim($_GET["aksi"] ?? "");
$w = [];
$t = "";
$p = [];
if ($q !== "") { $w[] = "(u.nama LIKE ? OR l.detail LIKE ? OR l.ip LIKE ?)"; $t .= "sss"; $l = "%$q%"; array_push($p, $l, $l, $l); }
if ($aksi !== "") { $w[] = "l.aksi = ?"; $t .= "s"; $p[] = $aksi; }
$where = $w ? "WHERE " . implode(" AND ", $w) : "";
$per = 25;
$hal = halamanSekarang();
$total = (int)qVal($conn, "SELECT COUNT(*) FROM log_aktivitas l LEFT JOIN users u ON u.id_user = l.id_user $where", $t, $p);
$rows = qAll($conn, "SELECT l.waktu, l.aksi, l.detail, l.ip, u.nama FROM log_aktivitas l LEFT JOIN users u ON u.id_user = l.id_user $where ORDER BY l.id_log DESC LIMIT $per OFFSET " . (($hal - 1) * $per), $t, $p);
$daftarAksi = qAll($conn, "SELECT DISTINCT aksi FROM log_aktivitas ORDER BY aksi");
require "includes/header.php";
?>
<section class="panel"><h2>Riwayat aktivitas sistem</h2>
<form class="filters" method="get"><div style="flex:2"><input name="q" value="<?= e($q) ?>" placeholder="Cari nama, detail, atau IP"></div>
<div><select name="aksi"><option value="">Semua aktivitas</option><?php foreach ($daftarAksi as $a): ?><option value="<?= e($a["aksi"]) ?>" <?= $aksi === $a["aksi"] ? "selected" : "" ?>><?= e(label($a["aksi"])) ?></option><?php endforeach; ?></select></div>
<div class="fit"><button>Cari</button><a class="btn secondary" href="log-aktivitas.php">Atur ulang</a></div></form>
<div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Detail</th><th>IP</th></tr></thead><tbody>
<?php if ($rows): foreach ($rows as $r): ?><tr><td><?= e(tglIndo($r["waktu"], true)) ?></td><td><?= e($r["nama"] ?: "-") ?></td><td><?= badge($r["aksi"] === "login_gagal" ? "ditolak" : "disetujui", label($r["aksi"])) ?></td><td><?= e($r["detail"]) ?></td><td><small class="muted"><?= e($r["ip"]) ?></small></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="empty">Belum ada aktivitas.</td></tr><?php endif; ?></tbody></table></div><?= paginasi($total, $per, $hal) ?></section>
<?php require "includes/footer.php"; ?>
