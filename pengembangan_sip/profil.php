<?php
require_once "auth.php";
wajibLogin();
$id = (int)$_SESSION["id_user"];
$role = $_SESSION["role"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajibPost("profil.php");
    try {
        $aksi = $_POST["aksi"] ?? "";
        if ($aksi === "profil") {
            $nama = trim($_POST["nama"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $hp = trim($_POST["no_hp"] ?? "");
            if ($nama === "") throw new Exception("Nama wajib diisi.");
            if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Format email tidak valid.");
            if ($hp !== "" && !preg_match('/^[0-9+\- ]{6,20}$/', $hp)) throw new Exception("Nomor HP hanya boleh berisi angka, spasi, tanda + atau -.");
            $conn->begin_transaction();
            qExec($conn, "UPDATE users SET nama = ?, email = ?, no_hp = ? WHERE id_user = ?", "sssi", [$nama, $email ?: null, $hp ?: null, $id]);
            if ($role === "siswa") qExec($conn, "UPDATE siswa SET nama_siswa = ? WHERE id_user = ?", "si", [$nama, $id]);
            if ($role === "guru") qExec($conn, "UPDATE guru SET nama_guru = ? WHERE id_user = ?", "si", [$nama, $id]);
            if ($role === "pengelola") qExec($conn, "UPDATE pengelola SET nama_pengelola = ? WHERE id_user = ?", "si", [$nama, $id]);
            $conn->commit();
            $_SESSION["nama"] = $nama;
            catatLog("ubah_profil", $nama);
            notif("Profil berhasil diperbarui.");
        } elseif ($aksi === "password") {
            $lama = $_POST["lama"] ?? "";
            $baru = $_POST["baru"] ?? "";
            $ulang = $_POST["ulang"] ?? "";
            $u = qRow($conn, "SELECT password FROM users WHERE id_user = ?", "i", [$id]);
            if (!(password_verify($lama, $u["password"]) || hash_equals((string)$u["password"], $lama))) throw new Exception("Password lama tidak sesuai.");
            if (strlen($baru) < 8) throw new Exception("Password baru minimal 8 karakter.");
            if ($baru !== $ulang) throw new Exception("Konfirmasi password tidak sama.");
            qExec($conn, "UPDATE users SET password = ? WHERE id_user = ?", "si", [password_hash($baru, PASSWORD_DEFAULT), $id]);
            catatLog("ganti_password", "");
            notif("Password berhasil diganti.");
        }
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $x) {}
        notif($e->getMessage(), "error");
    }
    ke("profil.php");
}

$u = qRow($conn, "SELECT username, nama, role, email, no_hp, terakhir_login FROM users WHERE id_user = ?", "i", [$id]);
$judul = "Profil dan Password";
require "includes/header.php";
?>
<div class="grid g2" style="align-items:start">
<section class="form-card"><h2>Data profil</h2>
<form method="post"><?= csrfField() ?><input type="hidden" name="aksi" value="profil">
  <div class="field"><label>Username</label><input value="<?= e($u["username"]) ?>" disabled></div>
  <div class="field"><label>Nama lengkap</label><input name="nama" required maxlength="100" value="<?= e($u["nama"]) ?>"></div>
  <div class="field"><label>Email</label><input type="email" name="email" maxlength="100" value="<?= e($u["email"]) ?>"></div>
  <div class="field"><label>Nomor HP</label><input name="no_hp" maxlength="20" value="<?= e($u["no_hp"]) ?>"></div>
  <p class="muted">Role: <?= e(ucfirst($u["role"])) ?> | Login terakhir: <?= e(tglIndo($u["terakhir_login"], true)) ?></p>
  <button>Simpan profil</button></form></section>
<section class="form-card"><h2>Ganti password</h2>
<form method="post"><?= csrfField() ?><input type="hidden" name="aksi" value="password">
  <div class="field"><label>Password lama</label><input type="password" name="lama" required autocomplete="current-password"></div>
  <div class="field"><label>Password baru</label><input type="password" name="baru" required minlength="8" autocomplete="new-password"><small>Minimal 8 karakter.</small></div>
  <div class="field"><label>Ulangi password baru</label><input type="password" name="ulang" required minlength="8" autocomplete="new-password"></div>
  <button>Ganti password</button></form></section>
</div>
<?php require "includes/footer.php"; ?>
