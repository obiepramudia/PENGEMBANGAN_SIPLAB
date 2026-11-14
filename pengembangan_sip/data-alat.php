<?php
require_once "auth.php";
wajibRole("pengelola");
$judul = "Manajemen Alat";

$edit = null;
if (!empty($_GET["edit"])) {
    $edit = qRow($conn, "SELECT * FROM alat WHERE id_alat = ?", "i", [(int)$_GET["edit"]]);
    if (!$edit) { notif("Alat tidak ditemukan.", "error"); ke("data-alat.php"); }
}

$q = trim($_GET["q"] ?? "");
$kat = trim($_GET["kategori"] ?? "");
$w = [];
$t = "";
$p = [];
if ($q !== "") { $w[] = "(nama_alat LIKE ? OR kode_alat LIKE ?)"; $t .= "ss"; $l = "%$q%"; array_push($p, $l, $l); }
if ($kat !== "") { $w[] = "kategori = ?"; $t .= "s"; $p[] = $kat; }
$where = $w ? "WHERE " . implode(" AND ", $w) : "";
$per = 15;
$hal = halamanSekarang();
$total = (int)qVal($conn, "SELECT COUNT(*) FROM alat $where", $t, $p);
$rows = qAll($conn, "SELECT * FROM alat $where ORDER BY nama_alat LIMIT $per OFFSET " . (($hal - 1) * $per), $t, $p);
$kategori = qAll($conn, "SELECT DISTINCT kategori FROM alat WHERE kategori <> '' ORDER BY kategori");
$ringkas = qRow($conn, "SELECT COUNT(*) jenis, COALESCE(SUM(stok_total),0) total, COALESCE(SUM(jumlah),0) sedia, COALESCE(SUM(jumlah_rusak),0) rusak FROM alat");

$f = $edit ?: ["id_alat" => 0, "nama_alat" => "", "kode_alat" => "", "kategori" => "Umum", "lokasi" => "", "deskripsi" => "", "stok_total" => 1, "kondisi" => "baik", "foto" => null, "jumlah_rusak" => 0, "jumlah" => 0];
$dipinjam = $edit ? jumlahDipinjam($conn, (int)$edit["id_alat"]) : 0;
require "includes/header.php";

function formAlat(array $f, bool $edit): void { ?>
<form method="post" action="proses-alat.php" enctype="multipart/form-data">
  <?= csrfField() ?><input type="hidden" name="aksi" value="<?= $edit ? "ubah" : "tambah" ?>"><input type="hidden" name="id" value="<?= (int)$f["id_alat"] ?>">
  <div class="form-grid g3">
    <div class="field"><label>Nama alat</label><input name="nama_alat" required maxlength="100" value="<?= e($f["nama_alat"]) ?>"></div>
    <div class="field"><label>Kode alat</label><input name="kode_alat" maxlength="30" value="<?= e($f["kode_alat"]) ?>" placeholder="Kosongkan untuk kode otomatis"></div>
    <div class="field"><label>Kategori</label><input name="kategori" required maxlength="60" value="<?= e($f["kategori"]) ?>" list="daftar-kategori"></div>
    <div class="field"><label>Total unit</label><input type="number" name="stok_total" min="0" required value="<?= (int)$f["stok_total"] ?>"></div>
    <div class="field"><label>Kondisi umum</label><select name="kondisi"><?php foreach (["baik", "rusak_ringan", "rusak_berat"] as $k): ?><option value="<?= $k ?>" <?= $f["kondisi"] === $k ? "selected" : "" ?>><?= e(label($k)) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Lokasi simpan</label><input name="lokasi" maxlength="100" value="<?= e($f["lokasi"]) ?>" placeholder="Contoh: Lemari B2"></div>
    <div class="field full"><label>Deskripsi</label><textarea name="deskripsi" maxlength="1000"><?= e($f["deskripsi"]) ?></textarea></div>
    <div class="field"><label>Foto alat (JPG, PNG, WEBP, maks 2 MB)</label><input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></div>
  </div>
  <div class="actions"><button><?= $edit ? "Simpan perubahan" : "Tambah alat" ?></button><?php if ($edit): ?><a class="btn secondary" href="data-alat.php">Batal</a><?php endif; ?></div>
</form>
<?php }
?>
<datalist id="daftar-kategori"><?php foreach ($kategori as $k): ?><option value="<?= e($k["kategori"]) ?>"><?php endforeach; ?></datalist>
<div class="grid">
  <div class="stat"><span>Jenis alat</span><strong><?= (int)$ringkas["jenis"] ?></strong></div>
  <div class="stat"><span>Total unit</span><strong><?= (int)$ringkas["total"] ?></strong></div>
  <div class="stat"><span>Unit tersedia</span><strong><?= (int)$ringkas["sedia"] ?></strong></div>
  <div class="stat bad"><span>Unit rusak</span><strong><?= (int)$ringkas["rusak"] ?></strong></div>
</div>

<?php if ($edit): ?>
<div class="form-card"><h2>Ubah alat: <?= e($edit["nama_alat"]) ?></h2>
  <p class="muted">Tersedia <?= (int)$edit["jumlah"] ?> | Sedang dipinjam atau dipesan <?= $dipinjam ?> | Rusak <?= (int)$edit["jumlah_rusak"] ?>. Stok tersedia dihitung otomatis dari total unit.</p>
  <?php if ($edit["foto"]): ?><p><img src="<?= e($edit["foto"]) ?>" alt="" style="max-height:110px;border-radius:8px"></p><?php endif; ?>
  <?php formAlat($f, true); ?>
  <?php if ((int)$edit["jumlah_rusak"] > 0): ?>
  <hr style="border:0;border-top:1px solid #edf0f5;margin:18px 0">
  <form method="post" action="proses-alat.php" class="actions" data-confirm="Pindahkan unit rusak ke stok tersedia?"><?= csrfField() ?><input type="hidden" name="aksi" value="perbaiki"><input type="hidden" name="id" value="<?= (int)$edit["id_alat"] ?>">
    <label>Unit selesai diperbaiki:</label><input type="number" name="jumlah" min="1" max="<?= (int)$edit["jumlah_rusak"] ?>" value="1" style="width:90px"><button class="success">Pindahkan ke stok</button></form>
  <?php endif; ?>
</div>
<?php else: ?>
<details class="collapse"><summary>Tambah alat baru</summary><div><?php formAlat($f, false); ?></div></details>
<?php endif; ?>

<section class="panel"><div class="panel-head"><h2>Daftar inventaris</h2></div>
<form class="filters" method="get"><div style="flex:2"><input name="q" value="<?= e($q) ?>" placeholder="Cari nama atau kode alat"></div>
  <div><select name="kategori"><option value="">Semua kategori</option><?php foreach ($kategori as $k): ?><option value="<?= e($k["kategori"]) ?>" <?= $kat === $k["kategori"] ? "selected" : "" ?>><?= e($k["kategori"]) ?></option><?php endforeach; ?></select></div>
  <div class="fit"><button>Cari</button><a class="btn secondary" href="data-alat.php">Atur ulang</a></div></form>
<div class="table-wrap"><table><thead><tr><th></th><th>Alat</th><th>Kategori</th><th>Lokasi</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if ($rows): foreach ($rows as $r): ?><tr>
<td><?php if ($r["foto"]): ?><img class="thumb-sm" src="<?= e($r["foto"]) ?>" alt=""><?php else: ?><div class="thumb-sm"><?= e(strtoupper(substr($r["nama_alat"], 0, 1))) ?></div><?php endif; ?></td>
<td><b><?= e($r["nama_alat"]) ?></b><br><small class="muted"><?= e($r["kode_alat"]) ?> | <?= e(label($r["kondisi"])) ?></small></td>
<td><?= e($r["kategori"]) ?></td><td><?= e($r["lokasi"] ?: "-") ?></td>
<td><?= (int)$r["jumlah"] ?> / <?= (int)$r["stok_total"] ?><?= (int)$r["jumlah_rusak"] ? "<br><small class='muted'>" . (int)$r["jumlah_rusak"] . " rusak</small>" : "" ?></td>
<td><?= badge($r["status"]) ?></td>
<td><div class="actions"><a class="btn sm secondary" href="data-alat.php?edit=<?= (int)$r["id_alat"] ?>">Ubah</a>
<form method="post" action="proses-alat.php" class="inline-form" data-confirm="Hapus alat ini?"><?= csrfField() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$r["id_alat"] ?>"><button class="sm danger">Hapus</button></form></div></td></tr>
<?php endforeach; else: ?><tr><td colspan="7" class="empty">Belum ada data alat.</td></tr><?php endif; ?></tbody></table></div><?= paginasi($total, $per, $hal) ?></section>
<?php require "includes/footer.php"; ?>
