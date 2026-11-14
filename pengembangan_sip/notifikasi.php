<?php
require_once "auth.php";
wajibLogin();
$id = (int)$_SESSION["id_user"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    wajibPost("notifikasi.php");
    qExec($conn, "DELETE FROM notifikasi WHERE id_user = ?", "i", [$id]);
    notif("Semua notifikasi dihapus.");
    ke("notifikasi.php");
}

$per = 20;
$hal = halamanSekarang();
$total = (int)qVal($conn, "SELECT COUNT(*) FROM notifikasi WHERE id_user = ?", "i", [$id]);
$rows = qAll($conn, "SELECT pesan, waktu, status_baca FROM notifikasi WHERE id_user = ? ORDER BY waktu DESC LIMIT $per OFFSET " . (($hal - 1) * $per), "i", [$id]);
qExec($conn, "UPDATE notifikasi SET status_baca = 'sudah_dibaca' WHERE id_user = ?", "i", [$id]);

$judul = "Notifikasi";
require "includes/header.php";
?>
<section class="panel"><div class="panel-head"><h2>Pemberitahuan</h2>
<?php if ($total): ?><form method="post" data-confirm="Hapus semua notifikasi?"><?= csrfField() ?><button class="sm danger">Hapus semua</button></form><?php endif; ?></div>
<?php if ($rows): foreach ($rows as $r): ?>
<div class="notif-item <?= $r["status_baca"] !== "sudah_dibaca" ? "baru" : "" ?>"><?= $r["status_baca"] !== "sudah_dibaca" ? '<span class="dot"></span>' : '<span style="width:9px"></span>' ?>
<div><b><?= e($r["pesan"]) ?></b><div class="muted"><?= e(tglIndo($r["waktu"], true)) ?></div></div></div>
<?php endforeach; else: ?><div class="empty">Belum ada notifikasi.</div><?php endif; ?>
<?= paginasi($total, $per, $hal) ?></section>
<?php require "includes/footer.php"; ?>
