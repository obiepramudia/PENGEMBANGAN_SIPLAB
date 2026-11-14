<?php
require_once "auth.php";
wajibRole("pengelola");
$judul = "Manajemen Pengguna";

$SQL = "SELECT u.id_user, u.username, u.nama, u.role, u.status, u.email, u.no_hp, u.terakhir_login, s.kelas, g.mata_pelajaran FROM users u LEFT JOIN siswa s ON s.id_user = u.id_user LEFT JOIN guru g ON g.id_user = u.id_user";
$edit = null;
if (!empty($_GET["edit"])) {
    $edit = qRow($conn, "$SQL WHERE u.id_user = ?", "i", [(int)$_GET["edit"]]);
    if (!$edit) { notif("Pengguna tidak ditemukan.", "error"); ke("data-pengguna.php"); }
}

$q = trim($_GET["q"] ?? "");
$role = $_GET["role"] ?? "";
$st = $_GET["status"] ?? "";
$w = [];
$t = "";
$p = [];
if ($q !== "") { $w[] = "(u.nama LIKE ? OR u.username LIKE ?)"; $t .= "ss"; $l = "%$q%"; array_push($p, $l, $l); }
if (in_array($role, ["siswa", "guru", "pengelola"], true)) { $w[] = "u.role = ?"; $t .= "s"; $p[] = $role; }
if (in_array($st, ["aktif", "nonaktif"], true)) { $w[] = "u.status = ?"; $t .= "s"; $p[] = $st; }
$where = $w ? "WHERE " . implode(" AND ", $w) : "";
$per = 15;
$hal = halamanSekarang();
$total = (int)qVal($conn, "SELECT COUNT(*) FROM users u $where", $t, $p);
$rows = qAll($conn, "$SQL $where ORDER BY u.role, u.nama LIMIT $per OFFSET " . (($hal - 1) * $per), $t, $p);
$hitung = [];
foreach (qAll($conn, "SELECT role, COUNT(*) n FROM users GROUP BY role") as $h) $hitung[$h["role"]] = (int)$h["n"];
$f = $edit ?: ["id_user" => 0, "nama" => "", "username" => "", "role" => "siswa", "email" => "", "no_hp" => "", "kelas" => "", "mata_pelajaran" => ""];
require "includes/header.php";
?>
<div class="grid g3">
  <div class="stat"><span>Siswa</span><strong><?= $hitung["siswa"] ?? 0 ?></strong></div>
  <div class="stat"><span>Guru</span><strong><?= $hitung["guru"] ?? 0 ?></strong></div>
  <div class="stat"><span>Pengelola</span><strong><?= $hitung["pengelola"] ?? 0 ?></strong></div>
</div>

<?php
function formPengguna(array $f, bool $edit): void { ?>
<form method="post" action="proses-pengguna.php"><?= csrfField() ?><input type="hidden" name="aksi" value="<?= $edit ? "ubah" : "tambah" ?>"><input type="hidden" name="id" value="<?= (int)$f["id_user"] ?>">
  <div class="form-grid g3">
    <div class="field"><label>Nama lengkap</label><input name="nama" required maxlength="100" value="<?= e($f["nama"]) ?>"></div>
    <div class="field"><label>Username</label><input name="username" required maxlength="50" value="<?= e($f["username"]) ?>" <?= $edit ? "readonly" : "" ?>></div>
    <?php if (!$edit): ?><div class="field"><label>Password awal</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div><?php endif; ?>
    <div class="field"><label>Role</label>
      <?php if ($edit): ?><input value="<?= e(ucfirst($f["role"])) ?>" disabled><?php else: ?>
      <select name="role" id="role" onchange="document.getElementById('kelas-box').style.display=this.value==='siswa'?'grid':'none';document.getElementById('mapel-box').style.display=this.value==='guru'?'grid':'none'"><option value="siswa">Siswa</option><option value="guru">Guru</option><option value="pengelola">Pengelola</option></select><?php endif; ?></div>
    <div class="field" id="kelas-box" style="<?= $f["role"] === "siswa" ? "" : "display:none" ?>"><label>Kelas</label><input name="kelas" maxlength="30" value="<?= e($f["kelas"]) ?>" placeholder="Contoh: IX A"></div>
    <div class="field" id="mapel-box" style="<?= $f["role"] === "guru" ? "" : "display:none" ?>"><label>Mata pelajaran</label><input name="mata_pelajaran" maxlength="60" value="<?= e($f["mata_pelajaran"]) ?>"></div>
    <div class="field"><label>Email</label><input type="email" name="email" maxlength="100" value="<?= e($f["email"]) ?>"></div>
    <div class="field"><label>Nomor HP</label><input name="no_hp" maxlength="20" value="<?= e($f["no_hp"]) ?>"></div>
  </div>
  <div class="actions"><button><?= $edit ? "Simpan perubahan" : "Tambah pengguna" ?></button><?php if ($edit): ?><a class="btn secondary" href="data-pengguna.php">Batal</a><?php endif; ?></div>
</form>
<?php }
if ($edit): ?><div class="form-card"><h2>Ubah pengguna: <?= e($edit["nama"]) ?></h2><?php formPengguna($f, true); ?></div>
<?php else: ?><details class="collapse"><summary>Tambah pengguna baru</summary><div><?php formPengguna($f, false); ?></div></details><?php endif; ?>

<section class="panel"><h2>Daftar pengguna</h2>
<form class="filters" method="get"><div style="flex:2"><input name="q" value="<?= e($q) ?>" placeholder="Cari nama atau username"></div>
  <div><select name="role"><option value="">Semua role</option><?php foreach (["siswa", "guru", "pengelola"] as $r): ?><option value="<?= $r ?>" <?= $role === $r ? "selected" : "" ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select></div>
  <div><select name="status"><option value="">Semua status</option><option value="aktif" <?= $st === "aktif" ? "selected" : "" ?>>Aktif</option><option value="nonaktif" <?= $st === "nonaktif" ? "selected" : "" ?>>Nonaktif</option></select></div>
  <div class="fit"><button>Cari</button><a class="btn secondary" href="data-pengguna.php">Atur ulang</a></div></form>
<div class="table-wrap"><table><thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Kontak</th><th>Login terakhir</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach ($rows as $r): $diri = (int)$r["id_user"] === (int)$_SESSION["id_user"]; ?><tr>
<td><b><?= e($r["nama"]) ?></b><?php if ($r["kelas"] || $r["mata_pelajaran"]): ?><br><small class="muted"><?= e($r["kelas"] ?: $r["mata_pelajaran"]) ?></small><?php endif; ?></td>
<td><?= e($r["username"]) ?></td><td><?= e(ucfirst($r["role"])) ?></td>
<td><small><?= e($r["email"] ?: "-") ?><br><?= e($r["no_hp"] ?: "-") ?></small></td>
<td><small><?= e(tglIndo($r["terakhir_login"], true)) ?></small></td><td><?= badge($r["status"]) ?></td>
<td><div class="actions"><a class="btn sm secondary" href="data-pengguna.php?edit=<?= (int)$r["id_user"] ?>">Ubah</a>
<?php if (!$diri): ?>
<form method="post" action="proses-pengguna.php" class="inline-form" data-confirm="Atur ulang password pengguna ini?"><?= csrfField() ?><input type="hidden" name="aksi" value="reset"><input type="hidden" name="id" value="<?= (int)$r["id_user"] ?>"><button class="sm secondary">Reset password</button></form>
<form method="post" action="proses-pengguna.php" class="inline-form" data-confirm="Ubah status akun ini?"><?= csrfField() ?><input type="hidden" name="aksi" value="status"><input type="hidden" name="id" value="<?= (int)$r["id_user"] ?>"><button class="sm secondary"><?= $r["status"] === "aktif" ? "Nonaktifkan" : "Aktifkan" ?></button></form>
<form method="post" action="proses-pengguna.php" class="inline-form" data-confirm="Hapus pengguna ini secara permanen?"><?= csrfField() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$r["id_user"] ?>"><button class="sm danger">Hapus</button></form>
<?php endif; ?></div></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty">Tidak ada pengguna yang cocok.</td></tr><?php endif; ?></tbody></table></div><?= paginasi($total, $per, $hal) ?></section>
<?php require "includes/footer.php"; ?>
