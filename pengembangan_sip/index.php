<?php
require_once "auth.php";
if (!empty($_SESSION["id_user"])) ke(tujuanDashboard($_SESSION["role"]));

$error = "";
$info = ($_GET["info"] ?? "") === "nonaktif" ? "Akun Anda tidak aktif. Hubungi pengelola lab." : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $ip = $_SERVER["REMOTE_ADDR"] ?? "";
    if (!hash_equals(csrfToken(), (string)($_POST["csrf"] ?? ""))) {
        $error = "Sesi formulir tidak valid. Muat ulang halaman.";
    } elseif ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } else {
        $gagal = (int)qVal($conn, "SELECT COUNT(*) FROM log_aktivitas WHERE aksi = 'login_gagal' AND ip = ? AND waktu > (NOW() - INTERVAL 10 MINUTE)", "s", [$ip]);
        if ($gagal >= 5) {
            $error = "Terlalu banyak percobaan gagal. Coba lagi dalam 10 menit.";
        } else {
            $u = qRow($conn, "SELECT id_user, username, password, nama, role, status FROM users WHERE username = ? LIMIT 1", "s", [$username]);
            $ok = false;
            $ganti = false;
            if ($u) {
                if (password_verify($password, $u["password"])) {
                    $ok = true;
                    $ganti = password_needs_rehash($u["password"], PASSWORD_DEFAULT);
                } elseif (hash_equals((string)$u["password"], $password)) {
                    $ok = true;
                    $ganti = true;
                }
            }
            if ($ok && $u["status"] !== "aktif") {
                $error = "Akun Anda tidak aktif. Hubungi pengelola lab.";
            } elseif ($ok) {
                if ($ganti) qExec($conn, "UPDATE users SET password = ? WHERE id_user = ?", "si", [password_hash($password, PASSWORD_DEFAULT), (int)$u["id_user"]]);
                qExec($conn, "UPDATE users SET terakhir_login = NOW() WHERE id_user = ?", "i", [(int)$u["id_user"]]);
                session_regenerate_id(true);
                $_SESSION["id_user"] = (int)$u["id_user"];
                $_SESSION["role"] = $u["role"];
                $_SESSION["nama"] = $u["nama"];
                catatLog("login", $u["username"]);
                ke(tujuanDashboard($u["role"]));
            } else {
                catatLog("login_gagal", $username);
                $error = "Username atau password tidak sesuai.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk | SIP LAB</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<section class="login-card">
  <div class="login-brand">SIP LAB</div>
  <h1>Selamat datang</h1>
  <p>Masuk untuk memakai sistem peminjaman alat pembelajaran.</p>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <?php if ($info && !$error): ?><div class="alert warning"><?= e($info) ?></div><?php endif; ?>
  <form method="post" autocomplete="on">
    <?= csrfField() ?>
    <div class="field"><label for="username">Username</label><input id="username" name="username" required autofocus autocomplete="username" value="<?= e($_POST["username"] ?? "") ?>"></div>
    <div class="field"><label for="password">Password</label>
      <div class="pw-wrap"><input id="password" type="password" name="password" required autocomplete="current-password"><button type="button" class="pw-toggle" data-toggle-pw="password">Lihat</button></div>
    </div>
    <button class="block">Masuk ke sistem</button>
  </form>
  <div class="hint">Lupa password? Minta pengelola lab untuk mengatur ulang akun Anda.</div>
</section>
<script src="assets/app.js"></script>
</body>
</html>
