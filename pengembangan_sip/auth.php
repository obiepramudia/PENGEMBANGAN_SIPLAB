<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(["httponly" => true, "samesite" => "Lax"]);
    session_start();
}
require_once __DIR__ . "/koneksi.php";

/* ---------- Dasar ---------- */
function e($value): string {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
function ke(string $url): void {
    header("Location: " . $url);
    exit;
}

/* ---------- Akses data (prepared statement) ---------- */
function qAll(mysqli $c, string $sql, string $types = "", array $params = []): array {
    $s = $c->prepare($sql);
    if ($params) $s->bind_param($types, ...$params);
    $s->execute();
    $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $s->close();
    return $rows;
}
function qRow(mysqli $c, string $sql, string $types = "", array $params = []): ?array {
    return qAll($c, $sql, $types, $params)[0] ?? null;
}
function qVal(mysqli $c, string $sql, string $types = "", array $params = []) {
    $r = qRow($c, $sql, $types, $params);
    return $r ? array_values($r)[0] : null;
}
function qExec(mysqli $c, string $sql, string $types = "", array $params = []): int {
    $s = $c->prepare($sql);
    if ($params) $s->bind_param($types, ...$params);
    $s->execute();
    $n = $s->affected_rows;
    $s->close();
    return (int)$n;
}

/* ---------- Sesi dan hak akses ---------- */
function wajibLogin(): void {
    global $conn;
    if (empty($_SESSION["id_user"])) ke("index.php");
    $status = qVal($conn, "SELECT status FROM users WHERE id_user = ?", "i", [(int)$_SESSION["id_user"]]);
    if ($status !== "aktif") {
        $_SESSION = [];
        session_destroy();
        ke("index.php?info=nonaktif");
    }
}
function wajibRole(...$roles): void {
    wajibLogin();
    if (!in_array($_SESSION["role"] ?? "", $roles, true)) {
        http_response_code(403);
        exit("Akses ditolak. Role akun Anda tidak memiliki izin membuka halaman ini.");
    }
}
function tujuanDashboard(string $role): string {
    return match ($role) {
        "siswa" => "dashboard.php",
        "guru" => "dashboard-guru.php",
        "pengelola" => "dashboard-pengelola.php",
        default => "index.php"
    };
}

/* ---------- Keamanan formulir ---------- */
function csrfToken(): string {
    if (empty($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(32));
    return $_SESSION["csrf"];
}
function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}
function wajibPost(string $kembali): void {
    if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") ke($kembali);
    $t = $_POST["csrf"] ?? "";
    if (!is_string($t) || !hash_equals(csrfToken(), $t)) {
        notif("Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.", "error");
        ke($kembali);
    }
}

/* ---------- Pesan, notifikasi, log ---------- */
function notif($pesan, $jenis = "success", bool $tetap = false): void {
    $_SESSION["flash"] = ["pesan" => $pesan, "jenis" => $jenis, "tetap" => $tetap];
}
function buatNotifikasi(mysqli $conn, int $idUser, string $pesan): void {
    $q = $conn->prepare("INSERT INTO notifikasi (id_user, pesan) VALUES (?, ?)");
    $q->bind_param("is", $idUser, $pesan);
    $q->execute();
    $q->close();
}
function notifikasiPengelola(mysqli $conn, string $pesan): void {
    foreach (qAll($conn, "SELECT id_user FROM users WHERE role = 'pengelola' AND status = 'aktif'") as $a) {
        buatNotifikasi($conn, (int)$a["id_user"], $pesan);
    }
}
function catatLog(string $aksi, string $detail = ""): void {
    global $conn;
    try {
        qExec($conn, "INSERT INTO log_aktivitas (id_user, aksi, detail, ip) VALUES (?, ?, ?, ?)", "isss",
            [$_SESSION["id_user"] ?? null, $aksi, mb_substr($detail, 0, 250), $_SERVER["REMOTE_ADDR"] ?? ""]);
    } catch (Throwable $x) {
    }
}

/* ---------- Format tampilan ---------- */
function tglIndo(?string $d, bool $jam = false): string {
    if (!$d || str_starts_with($d, "0000")) return "-";
    $t = strtotime($d);
    $b = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    $s = date("j", $t) . " " . $b[(int)date("n", $t) - 1] . " " . date("Y", $t);
    return $jam ? $s . ", " . date("H:i", $t) : $s;
}
function label(?string $s): string {
    return ucfirst(str_replace("_", " ", (string)$s));
}
function badge(string $s, ?string $teks = null): string {
    return '<span class="badge ' . e($s) . '">' . e($teks ?? label($s)) . '</span>';
}
function jamRange(array $r): string {
    return substr($r["waktu_mulai"], 0, 5) . " - " . substr($r["waktu_selesai"], 0, 5);
}

/* ---------- Peminjaman ---------- */
const SQL_PINJAM = "SELECT p.id_peminjaman, p.id_user, p.tanggal_pengajuan, p.tanggal_peminjaman, p.status_peminjaman,
    p.keperluan, p.catatan_pengelola, u.nama, u.role, j.waktu_mulai, j.waktu_selesai,
    (SELECT GROUP_CONCAT(CONCAT(a.nama_alat, ' (', d.jumlah, ')') SEPARATOR ', ')
       FROM detail_peminjaman d JOIN alat a ON a.id_alat = d.id_alat
      WHERE d.id_peminjaman = p.id_peminjaman) AS daftar_alat
    FROM peminjaman p
    JOIN users u ON u.id_user = p.id_user
    JOIN jadwal j ON j.id_jadwal = p.id_jadwal";

function jatuhTempo(array $r): int {
    return strtotime($r["tanggal_peminjaman"] . " " . $r["waktu_selesai"]);
}
function terlambat(array $r): bool {
    return $r["status_peminjaman"] === "sedang_dipinjam" && jatuhTempo($r) < time();
}
function statusTampil(array $r): string {
    return terlambat($r) ? badge("terlambat", "Terlambat") : badge($r["status_peminjaman"]);
}
function hariTerlambat(array $r): int {
    return max(1, (int)ceil((time() - jatuhTempo($r)) / 86400));
}
const SQL_TERLAMBAT = "p.status_peminjaman = 'sedang_dipinjam' AND TIMESTAMP(p.tanggal_peminjaman, j.waktu_selesai) < NOW()";

/* ---------- Stok alat ---------- */
function segarkanStatusAlat(mysqli $c, int $idAlat): void {
    qExec($c, "UPDATE alat SET status = IF(jumlah > 0, 'tersedia', 'tidak_tersedia') WHERE id_alat = ?", "i", [$idAlat]);
}
function jumlahDipinjam(mysqli $c, int $idAlat): int {
    return (int)qVal($c, "SELECT COALESCE(SUM(d.jumlah), 0) FROM detail_peminjaman d JOIN peminjaman p ON p.id_peminjaman = d.id_peminjaman
        WHERE d.id_alat = ? AND p.status_peminjaman IN ('disetujui','sedang_dipinjam')", "i", [$idAlat]);
}

/* ---------- Foto alat ---------- */
function simpanFoto(array $f): ?string {
    if (!isset($f["error"]) || $f["error"] === UPLOAD_ERR_NO_FILE) return null;
    if ($f["error"] !== UPLOAD_ERR_OK) throw new Exception("Unggah foto gagal. Coba file lain.");
    if ($f["size"] > 2 * 1024 * 1024) throw new Exception("Ukuran foto maksimal 2 MB.");
    $info = @getimagesize($f["tmp_name"]);
    $mime = $info ? $info["mime"] : "";
    $ext = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"][$mime] ?? null;
    if (!$ext) throw new Exception("Format foto harus JPG, PNG, atau WEBP.");
    $dir = __DIR__ . "/uploads/alat";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $nama = bin2hex(random_bytes(8)) . "." . $ext;
    if (!move_uploaded_file($f["tmp_name"], $dir . "/" . $nama)) throw new Exception("Foto tidak dapat disimpan. Periksa izin folder uploads.");
    return "uploads/alat/" . $nama;
}
function hapusFoto(?string $path): void {
    if ($path && str_starts_with($path, "uploads/alat/") && is_file(__DIR__ . "/" . $path)) @unlink(__DIR__ . "/" . $path);
}

/* ---------- Halaman ---------- */
function halamanSekarang(): int {
    return max(1, (int)($_GET["hal"] ?? 1));
}
function paginasi(int $total, int $per, int $hal): string {
    $n = (int)ceil($total / $per);
    if ($n <= 1) return "";
    $q = $_GET;
    $mk = function (int $p, string $teks, string $cls = "") use ($q) {
        $q["hal"] = $p;
        return '<a class="' . $cls . '" href="?' . e(http_build_query($q)) . '">' . $teks . '</a>';
    };
    $out = '<nav class="pager">';
    if ($hal > 1) $out .= $mk($hal - 1, "Sebelumnya");
    for ($i = max(1, $hal - 2); $i <= min($n, $hal + 2); $i++) $out .= $mk($i, (string)$i, $i === $hal ? "on" : "");
    if ($hal < $n) $out .= $mk($hal + 1, "Berikutnya");
    return $out . '<span class="muted">Total ' . $total . ' data</span></nav>';
}
