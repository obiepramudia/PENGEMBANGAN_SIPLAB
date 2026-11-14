<?php
require_once "auth.php";
wajibRole("siswa", "guru", "pengelola");
$judul = "Katalog Alat";

$q = trim($_GET["q"] ?? "");
$kat = trim($_GET["kategori"] ?? "");
$hanyaTersedia = !empty($_GET["tersedia"]);
$w = [];
$t = "";
$p = [];
if ($q !== "") {
    $w[] = "(nama_alat LIKE ? OR kode_alat LIKE ? OR deskripsi LIKE ?)";
    $t .= "sss";
    $like = "%" . $q . "%";
    array_push($p, $like, $like, $like);
}
if ($kat !== "") { $w[] = "kategori = ?"; $t .= "s"; $p[] = $kat; }
if ($hanyaTersedia) $w[] = "jumlah > 0 AND status = 'tersedia'";
$where = $w ? "WHERE " . implode(" AND ", $w) : "";

$per = 12;
$hal = halamanSekarang();
$total = (int)qVal($conn, "SELECT COUNT(*) FROM alat $where", $t, $p);
$rows = qAll($conn, "SELECT * FROM alat $where ORDER BY nama_alat LIMIT $per OFFSET " . (($hal - 1) * $per), $t, $p);
$kategori = qAll($conn, "SELECT DISTINCT kategori FROM alat WHERE kategori IS NOT NULL AND kategori <> '' ORDER BY kategori");
$bisaPinjam = in_array($_SESSION["role"], ["siswa", "guru"], true);

require "includes/header.php";
?>
<form class="filters panel" method="get">
  <div><label>Cari alat</label><input name="q" value="<?= e($q) ?>" placeholder="Nama, kode, atau deskripsi"></div>
  <div><label>Kategori</label><select name="kategori"><option value="">Semua kategori</option>
    <?php foreach ($kategori as $k): ?><option value="<?= e($k["kategori"]) ?>" <?= $kat === $k["kategori"] ? "selected" : "" ?>><?= e($k["kategori"]) ?></option><?php endforeach; ?>
  </select></div>
  <div class="fit"><label style="margin:0;align-self:center"><input type="checkbox" name="tersedia" value="1" style="width:auto" <?= $hanyaTersedia ? "checked" : "" ?>> Hanya yang tersedia</label></div>
  <div class="fit"><button>Terapkan</button><a class="btn secondary" href="daftar-alat.php">Atur ulang</a></div>
</form>

<?php if (!$rows): ?>
  <div class="panel"><div class="empty">Tidak ada alat yang cocok dengan pencarian Anda.</div></div>
<?php else: ?>
<div class="catalog">
  <?php foreach ($rows as $a):
      $tot = max(1, (int)$a["stok_total"]);
      $sisa = (int)$a["jumlah"];
      $pct = min(100, round($sisa / $tot * 100));
      $cls = $sisa <= 0 ? "out" : ($pct <= 25 ? "low" : "");
  ?>
  <article class="card-alat">
    <div class="thumb"><?php if ($a["foto"]): ?><img src="<?= e($a["foto"]) ?>" alt="<?= e($a["nama_alat"]) ?>"><?php else: ?><?= e(strtoupper(substr($a["nama_alat"], 0, 1))) ?><?php endif; ?></div>
    <div class="body">
      <div class="actions" style="justify-content:space-between"><span class="meta"><?= e($a["kode_alat"]) ?> | <?= e($a["kategori"]) ?></span><?= badge($a["status"]) ?></div>
      <h3><?= e($a["nama_alat"]) ?></h3>
      <?php if ($a["deskripsi"]): ?><div class="meta"><?= e(mb_strimwidth($a["deskripsi"], 0, 90, "...")) ?></div><?php endif; ?>
      <div class="meta">Lokasi: <?= e($a["lokasi"] ?: "-") ?> | Kondisi: <?= e(label($a["kondisi"])) ?></div>
      <div class="bar <?= $cls ?>"><i style="width:<?= $pct ?>%"></i></div>
      <div class="meta"><?= $sisa ?> tersedia dari <?= (int)$a["stok_total"] ?> unit<?= (int)$a["jumlah_rusak"] > 0 ? " (" . (int)$a["jumlah_rusak"] . " rusak)" : "" ?></div>
    </div>
    <div class="foot">
      <?php if ($bisaPinjam): ?>
        <?php if ($sisa > 0 && $a["status"] === "tersedia"): ?><a class="btn" href="peminjaman-saya.php?alat=<?= (int)$a["id_alat"] ?>">Ajukan peminjaman</a>
        <?php else: ?><button class="secondary" disabled>Stok habis</button><?php endif; ?>
      <?php elseif ($_SESSION["role"] === "pengelola"): ?><a class="btn secondary" href="data-alat.php?edit=<?= (int)$a["id_alat"] ?>">Kelola alat</a><?php endif; ?>
    </div>
  </article>
  <?php endforeach; ?>
</div>
<?= paginasi($total, $per, $hal) ?>
<?php endif; ?>
<?php require "includes/footer.php"; ?>
