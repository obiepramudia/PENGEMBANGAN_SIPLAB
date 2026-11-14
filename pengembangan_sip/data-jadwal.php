<?php
require_once "auth.php";
wajibRole("pengelola");
$judul = "Manajemen Jadwal";

$edit = null;
if (!empty($_GET["edit"])) {
    $edit = qRow($conn, "SELECT * FROM jadwal WHERE id_jadwal = ?", "i", [(int)$_GET["edit"]]);
    if (!$edit) { notif("Jadwal tidak ditemukan.", "error"); ke("data-jadwal.php"); }
}
$tampil = $_GET["tampil"] ?? "mendatang";
$where = match ($tampil) {
    "lalu" => "WHERE j.tanggal < CURDATE()",
    "semua" => "",
    default => "WHERE j.tanggal >= CURDATE()"
};
$per = 20;
$hal = halamanSekarang();
$total = (int)qVal($conn, "SELECT COUNT(*) FROM jadwal j $where");
$order = $tampil === "lalu" ? "DESC" : "ASC";
$rows = qAll($conn, "SELECT j.*, (SELECT COUNT(*) FROM peminjaman p WHERE p.id_jadwal = j.id_jadwal) AS dipakai FROM jadwal j $where ORDER BY j.tanggal $order, j.waktu_mulai LIMIT $per OFFSET " . (($hal - 1) * $per));
require "includes/header.php";
?>
<?php if ($edit): ?>
<div class="form-card"><h2>Ubah jadwal</h2>
<form method="post" action="proses-jadwal.php"><?= csrfField() ?><input type="hidden" name="aksi" value="ubah"><input type="hidden" name="id" value="<?= (int)$edit["id_jadwal"] ?>">
  <div class="form-grid g3">
    <div class="field"><label>Tanggal</label><input type="date" name="tanggal" required value="<?= e($edit["tanggal"]) ?>"></div>
    <div class="field"><label>Jam mulai</label><input type="time" name="waktu_mulai" required value="<?= e(substr($edit["waktu_mulai"], 0, 5)) ?>"></div>
    <div class="field"><label>Jam selesai</label><input type="time" name="waktu_selesai" required value="<?= e(substr($edit["waktu_selesai"], 0, 5)) ?>"></div>
    <div class="field"><label>Status</label><select name="status"><option value="tersedia" <?= $edit["status"] === "tersedia" ? "selected" : "" ?>>Tersedia</option><option value="tidak_tersedia" <?= $edit["status"] !== "tersedia" ? "selected" : "" ?>>Tidak tersedia</option></select></div>
  </div>
  <div class="actions"><button>Simpan perubahan</button><a class="btn secondary" href="data-jadwal.php">Batal</a></div></form></div>
<?php else: ?>
<div class="grid g2" style="align-items:start">
<section class="form-card"><h2>Tambah satu jadwal</h2>
<form method="post" action="proses-jadwal.php"><?= csrfField() ?><input type="hidden" name="aksi" value="tambah">
  <div class="form-grid g3">
    <div class="field"><label>Tanggal</label><input type="date" name="tanggal" required min="<?= date("Y-m-d") ?>"></div>
    <div class="field"><label>Jam mulai</label><input type="time" name="waktu_mulai" required value="07:00"></div>
    <div class="field"><label>Jam selesai</label><input type="time" name="waktu_selesai" required value="15:00"></div>
  </div><button>Tambah jadwal</button></form></section>
<section class="form-card"><h2>Buat banyak jadwal sekaligus</h2>
<form method="post" action="proses-jadwal.php"><?= csrfField() ?><input type="hidden" name="aksi" value="massal">
  <div class="form-grid g3">
    <div class="field"><label>Dari tanggal</label><input type="date" name="dari" required min="<?= date("Y-m-d") ?>"></div>
    <div class="field"><label>Sampai tanggal</label><input type="date" name="sampai" required></div>
    <div class="field"><label>Hari</label><select name="hari"><option value="kerja">Senin sampai Jumat</option><option value="semua">Semua hari</option></select></div>
    <div class="field"><label>Jam mulai</label><input type="time" name="waktu_mulai" required value="07:00"></div>
    <div class="field"><label>Jam selesai</label><input type="time" name="waktu_selesai" required value="15:00"></div>
  </div><button>Buat jadwal</button> <small class="muted">Maksimal 62 hari. Jadwal yang sama dilewati.</small></form></section>
</div>
<?php endif; ?>

<section class="panel"><div class="panel-head"><h2>Daftar jadwal</h2>
  <div class="chips" style="margin:0"><?php foreach (["mendatang" => "Mendatang", "lalu" => "Sudah lewat", "semua" => "Semua"] as $k => $v): ?><a class="<?= $tampil === $k ? "on" : "" ?>" href="?tampil=<?= $k ?>"><?= $v ?></a><?php endforeach; ?></div></div>
<div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Waktu</th><th>Status</th><th>Dipakai</th><th>Aksi</th></tr></thead><tbody>
<?php if ($rows): foreach ($rows as $r): ?><tr>
<td><?= e(tglIndo($r["tanggal"])) ?></td><td><?= e(jamRange($r)) ?></td><td><?= badge($r["status"]) ?></td><td><?= (int)$r["dipakai"] ?> pengajuan</td>
<td><div class="actions"><a class="btn sm secondary" href="data-jadwal.php?edit=<?= (int)$r["id_jadwal"] ?>">Ubah</a>
<form method="post" action="proses-jadwal.php" class="inline-form"><?= csrfField() ?><input type="hidden" name="aksi" value="toggle"><input type="hidden" name="id" value="<?= (int)$r["id_jadwal"] ?>"><button class="sm secondary"><?= $r["status"] === "tersedia" ? "Tutup" : "Buka" ?></button></form>
<form method="post" action="proses-jadwal.php" class="inline-form" data-confirm="Hapus jadwal ini?"><?= csrfField() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$r["id_jadwal"] ?>"><button class="sm danger">Hapus</button></form></div></td></tr>
<?php endforeach; else: ?><tr><td colspan="5" class="empty">Belum ada jadwal.</td></tr><?php endif; ?></tbody></table></div><?= paginasi($total, $per, $hal) ?></section>
<?php require "includes/footer.php"; ?>
