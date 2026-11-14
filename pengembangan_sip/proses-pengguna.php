<?php
require_once "auth.php";
wajibRole("pengelola");
wajibPost("data-pengguna.php");
$aksi = $_POST["aksi"] ?? "";
$id = (int)($_POST["id"] ?? 0);
$balik = "data-pengguna.php";
$diri = (int)$_SESSION["id_user"];

function bacaProfil(): array {
    $email = trim($_POST["email"] ?? "");
    $hp = trim($_POST["no_hp"] ?? "");
    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Format email tidak valid.");
    if ($hp !== "" && !preg_match('/^[0-9+\- ]{6,20}$/', $hp)) throw new Exception("Nomor HP hanya boleh berisi angka, spasi, tanda + atau -.");
    return [$email ?: null, $hp ?: null];
}
function sandiAcak(int $n = 10): string {
    $huruf = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $s = "";
    for ($i = 0; $i < $n; $i++) $s .= $huruf[random_int(0, strlen($huruf) - 1)];
    return $s;
}

try {
    if ($aksi === "tambah") {
        $nama = trim($_POST["nama"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "";
        $kelas = trim($_POST["kelas"] ?? "");
        $mapel = trim($_POST["mata_pelajaran"] ?? "");
        [$email, $hp] = bacaProfil();
        if ($nama === "" || $username === "" || strlen($password) < 8 || !in_array($role, ["siswa", "guru", "pengelola"], true)) throw new Exception("Lengkapi data. Password minimal 8 karakter.");
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) throw new Exception("Username 3 sampai 50 karakter. Boleh huruf, angka, titik, garis bawah, dan strip.");
        if ($role === "siswa" && $kelas === "") throw new Exception("Kelas siswa wajib diisi.");
        if ((int)qVal($conn, "SELECT COUNT(*) FROM users WHERE username = ?", "s", [$username]) > 0) throw new Exception("Username sudah dipakai.");
        $conn->begin_transaction();
        qExec($conn, "INSERT INTO users (username, password, nama, role, email, no_hp) VALUES (?, ?, ?, ?, ?, ?)", "ssssss", [$username, password_hash($password, PASSWORD_DEFAULT), $nama, $role, $email, $hp]);
        $uid = (int)$conn->insert_id;
        if ($role === "siswa") qExec($conn, "INSERT INTO siswa (id_user, nama_siswa, kelas) VALUES (?, ?, ?)", "iss", [$uid, $nama, $kelas]);
        if ($role === "guru") qExec($conn, "INSERT INTO guru (id_user, nama_guru, mata_pelajaran) VALUES (?, ?, ?)", "iss", [$uid, $nama, $mapel]);
        if ($role === "pengelola") qExec($conn, "INSERT INTO pengelola (id_user, nama_pengelola) VALUES (?, ?)", "is", [$uid, $nama]);
        catatLog("user_tambah", "$username ($role)");
        $conn->commit();
        notif("Pengguna berhasil ditambahkan.");
    } elseif ($aksi === "ubah") {
        $u = qRow($conn, "SELECT role FROM users WHERE id_user = ?", "i", [$id]);
        if (!$u) throw new Exception("Pengguna tidak ditemukan.");
        $nama = trim($_POST["nama"] ?? "");
        $kelas = trim($_POST["kelas"] ?? "");
        $mapel = trim($_POST["mata_pelajaran"] ?? "");
        [$email, $hp] = bacaProfil();
        if ($nama === "") throw new Exception("Nama wajib diisi.");
        if ($u["role"] === "siswa" && $kelas === "") throw new Exception("Kelas siswa wajib diisi.");
        $conn->begin_transaction();
        qExec($conn, "UPDATE users SET nama = ?, email = ?, no_hp = ? WHERE id_user = ?", "sssi", [$nama, $email, $hp, $id]);
        if ($u["role"] === "siswa") qExec($conn, "UPDATE siswa SET nama_siswa = ?, kelas = ? WHERE id_user = ?", "ssi", [$nama, $kelas, $id]);
        if ($u["role"] === "guru") qExec($conn, "UPDATE guru SET nama_guru = ?, mata_pelajaran = ? WHERE id_user = ?", "ssi", [$nama, $mapel, $id]);
        if ($u["role"] === "pengelola") qExec($conn, "UPDATE pengelola SET nama_pengelola = ? WHERE id_user = ?", "si", [$nama, $id]);
        $conn->commit();
        if ($id === $diri) $_SESSION["nama"] = $nama;
        catatLog("user_ubah", "$nama (#$id)");
        notif("Data pengguna diperbarui.");
    } elseif ($aksi === "reset") {
        if ($id === $diri) throw new Exception("Ganti password akun Anda sendiri lewat menu Profil.");
        $u = qRow($conn, "SELECT username FROM users WHERE id_user = ?", "i", [$id]);
        if (!$u) throw new Exception("Pengguna tidak ditemukan.");
        $baru = sandiAcak();
        qExec($conn, "UPDATE users SET password = ? WHERE id_user = ?", "si", [password_hash($baru, PASSWORD_DEFAULT), $id]);
        catatLog("user_reset", $u["username"]);
        notif("Password baru untuk " . $u["username"] . ": " . $baru . ". Catat sekarang. Pesan ini tidak muncul lagi.", "success", true);
    } elseif ($aksi === "status") {
        if ($id === $diri) throw new Exception("Anda tidak bisa menonaktifkan akun sendiri.");
        $n = qExec($conn, "UPDATE users SET status = IF(status = 'aktif', 'nonaktif', 'aktif') WHERE id_user = ?", "i", [$id]);
        if (!$n) throw new Exception("Pengguna tidak ditemukan.");
        catatLog("user_status", "#$id");
        notif("Status akun diperbarui.");
    } elseif ($aksi === "hapus") {
        if ($id === $diri) throw new Exception("Anda tidak bisa menghapus akun sendiri.");
        $u = qRow($conn, "SELECT username, role FROM users WHERE id_user = ?", "i", [$id]);
        if (!$u) throw new Exception("Pengguna tidak ditemukan.");
        if ((int)qVal($conn, "SELECT COUNT(*) FROM peminjaman WHERE id_user = ?", "i", [$id]) > 0) throw new Exception("Pengguna punya riwayat peminjaman, jadi tidak bisa dihapus. Nonaktifkan akunnya.");
        $conn->begin_transaction();
        qExec($conn, "DELETE FROM notifikasi WHERE id_user = ?", "i", [$id]);
        qExec($conn, "DELETE FROM siswa WHERE id_user = ?", "i", [$id]);
        qExec($conn, "DELETE FROM guru WHERE id_user = ?", "i", [$id]);
        qExec($conn, "DELETE FROM pengelola WHERE id_user = ?", "i", [$id]);
        qExec($conn, "DELETE FROM users WHERE id_user = ?", "i", [$id]);
        catatLog("user_hapus", $u["username"]);
        $conn->commit();
        notif("Pengguna dihapus.");
    } else {
        throw new Exception("Aksi tidak dikenal.");
    }
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $x) {}
    $msg = $e->getMessage();
    if (str_contains($msg, "Duplicate entry")) $msg = "Data sudah terdaftar.";
    notif($msg, "error");
    if ($aksi === "ubah") $balik = "data-pengguna.php?edit=$id";
}
ke($balik);
