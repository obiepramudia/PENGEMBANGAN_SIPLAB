SIP LAB VERSI 2
Sistem Informasi Peminjaman Alat Pembelajaran

PERSYARATAN
- XAMPP dengan Apache dan MySQL/MariaDB aktif
- PHP 8.0 atau lebih baru
- Database db_peminjaman_alat yang sudah diimpor dari file SQL awal

INSTALASI
1. Cadangkan database lama dulu lewat phpMyAdmin (Export).
2. Salin seluruh isi folder ke C:\xampp\htdocs\sip_lab\ dan timpa file lama.
3. Pastikan koneksi.php memakai host localhost, user root, password kosong, database db_peminjaman_alat.
4. Jalankan Apache dan MySQL, lalu buka http://localhost/sip_lab/
5. Saat halaman pertama dibuka, sistem menambah kolom dan tabel baru secara otomatis.
   Data lama tidak dihapus. Anda tidak perlu menjalankan SQL apa pun.
6. Pastikan folder uploads/alat bisa ditulis (default XAMPP di Windows sudah bisa).

AKUN AWAL
siswa01, guru01, admin01. Password awal: password123.
Segera ganti lewat menu Profil dan Password. Password lama yang masih teks biasa
diubah otomatis menjadi hash saat akun itu login.

FITUR UNTUK PENGELOLA (tanpa membuka database)
- Dashboard: antrean persetujuan, alat siap diserahkan, daftar terlambat, stok menipis,
  grafik 7 hari, alat terpopuler
- Data Peminjaman: cari, saring status dan tanggal, setujui, tolak dengan alasan,
  serahkan alat, batalkan peminjaman yang sudah disetujui (stok kembali otomatis)
- Pengembalian: catat per alat, jumlah unit rusak, kondisi, catatan, pengingat
  keterlambatan, riwayat pengembalian
- Data Alat: tambah, ubah, hapus, kode alat, kategori, lokasi, deskripsi, foto,
  stok total, stok rusak, perbaikan alat rusak kembali ke stok
- Data Jadwal: tambah satu jadwal, buat banyak jadwal sekaligus, ubah, buka atau tutup, hapus
- Data Pengguna: tambah, ubah, cari, saring, reset password, nonaktifkan, hapus
- Laporan: periode bebas, rekap pemakaian alat, kondisi pengembalian, unduh CSV, cetak
- Log Aktivitas: semua tindakan penting dan percobaan login gagal
- Backup Data: unduh file .sql lewat satu klik

FITUR UNTUK SISWA DAN GURU
- Katalog alat dengan foto, pencarian, filter kategori, indikator stok
- Satu pengajuan boleh memuat beberapa jenis alat
- Isi keperluan, pilih jadwal, batalkan pengajuan yang masih menunggu
- Detail dan bukti peminjaman yang bisa dicetak
- Notifikasi dengan penanda belum dibaca
- Ubah profil dan password sendiri
- Guru dapat meminjam alat dan memantau semua peminjaman serta laporan

KEAMANAN
- Token CSRF di semua formulir
- Batas 5 login gagal per 10 menit untuk tiap alamat IP
- Akun nonaktif langsung ditolak di semua halaman
- Password disimpan dengan password_hash
- Unggahan foto diperiksa tipe dan ukurannya, dan folder uploads tidak boleh menjalankan skrip
- Saat dipakai di server sungguhan, ganti password root MySQL dan ubah koneksi.php

ATURAN STOK
- Jumlah tersedia dikurangi saat pengajuan disetujui.
- Saat dikembalikan, unit baik masuk ke stok tersedia, unit rusak masuk ke stok rusak.
- Status alat mengikuti stok: tersedia jika jumlah lebih dari 0.
- Peminjaman terlambat jika alat belum kembali setelah jam selesai jadwal.
  Peminjam yang terlambat tidak bisa mengajukan peminjaman baru.

CATATAN
- Struktur tabel jadwal, alat, dan peminjaman dibaca dari database Anda.
  Jika proses tambah jadwal atau alat gagal, tabel Anda mungkin punya kolom wajib tambahan.
  Kirim pesan error-nya agar bisa disesuaikan.
