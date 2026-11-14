<?php
require_once __DIR__ . "/../auth.php";
wajibLogin();
$role = $_SESSION["role"];
$nama = $_SESSION["nama"] ?? "Pengguna";
$judul = $judul ?? "SIP LAB";
$belumBaca = (int)qVal($conn, "SELECT COUNT(*) FROM notifikasi WHERE id_user = ? AND status_baca <> 'sudah_dibaca'", "i", [(int)$_SESSION["id_user"]]);

$menu = match ($role) {
    "siswa" => [
        "Utama" => [["dashboard.php", "Dashboard"], ["daftar-alat.php", "Katalog Alat"], ["peminjaman-saya.php", "Peminjaman Saya"]],
        "Akun" => [["notifikasi.php", "Notifikasi"], ["profil.php", "Profil dan Password"]],
    ],
    "guru" => [
        "Utama" => [["dashboard-guru.php", "Dashboard"], ["daftar-alat.php", "Katalog Alat"], ["peminjaman-saya.php", "Peminjaman Saya"]],
        "Pemantauan" => [["data-peminjaman.php", "Semua Peminjaman"], ["laporan.php", "Laporan"]],
        "Akun" => [["notifikasi.php", "Notifikasi"], ["profil.php", "Profil dan Password"]],
    ],
    default => [
        "Utama" => [["dashboard-pengelola.php", "Dashboard"]],
        "Transaksi" => [["data-peminjaman.php", "Data Peminjaman"], ["pengembalian.php", "Pengembalian"]],
        "Data Master" => [["data-alat.php", "Data Alat"], ["data-jadwal.php", "Data Jadwal"], ["data-pengguna.php", "Data Pengguna"]],
        "Sistem" => [["laporan.php", "Laporan"], ["log-aktivitas.php", "Log Aktivitas"], ["backup.php", "Backup Data"]],
        "Akun" => [["notifikasi.php", "Notifikasi"], ["profil.php", "Profil dan Password"]],
    ]
};
$halamanIni = basename($_SERVER["PHP_SELF"]);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> | SIP LAB</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<aside class="sidebar" id="sidebar">
  <div class="brand"><span class="brand-mark">S</span><div><b>SIP LAB</b><small>Peminjaman Alat Pembelajaran</small></div></div>
  <div class="profile"><div class="avatar"><?= e(strtoupper(substr($nama, 0, 1))) ?></div><div><b><?= e($nama) ?></b><small><?= e(ucfirst($role)) ?></small></div></div>
  <nav>
    <?php foreach ($menu as $grup => $items): ?>
      <span class="nav-group"><?= e($grup) ?></span>
      <?php foreach ($items as [$url, $teks]): ?>
        <a class="<?= $halamanIni === $url ? "active" : "" ?>" href="<?= e($url) ?>"><?= e($teks) ?>
          <?php if ($url === "notifikasi.php" && $belumBaca > 0): ?><span class="count"><?= $belumBaca ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <a class="logout-link" href="logout.php">Keluar dari akun</a>
</aside>
<main class="main">
<header class="topbar">
  <div class="topbar-left">
    <button type="button" class="menu-btn" id="menuBtn" aria-label="Buka menu">Menu</button>
    <div><span class="eyebrow">SISTEM INFORMASI PEMINJAMAN</span><h1><?= e($judul) ?></h1></div>
  </div>
  <a class="top-user" href="profil.php"><?= e($nama) ?></a>
</header>
<?php if (!empty($_SESSION["flash"])): $f = $_SESSION["flash"]; unset($_SESSION["flash"]); ?>
<div class="alert <?= e($f["jenis"]) ?>" <?= empty($f["tetap"]) ? "data-autohide" : "" ?>><?= e($f["pesan"]) ?></div>
<?php endif; ?>
