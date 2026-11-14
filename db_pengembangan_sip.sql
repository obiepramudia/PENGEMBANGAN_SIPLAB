-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2026 at 10:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_pengembangan_sip`
--

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
  `id_alat` int(11) NOT NULL,
  `nama_alat` varchar(100) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 0,
  `kondisi` enum('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `status` enum('tersedia','tidak_tersedia') NOT NULL DEFAULT 'tersedia',
  `stok_total` int(11) NOT NULL DEFAULT 0,
  `jumlah_rusak` int(11) NOT NULL DEFAULT 0,
  `kode_alat` varchar(30) DEFAULT NULL,
  `kategori` varchar(60) NOT NULL DEFAULT 'Umum',
  `lokasi` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alat`
--

INSERT INTO `alat` (`id_alat`, `nama_alat`, `jumlah`, `kondisi`, `status`, `stok_total`, `jumlah_rusak`, `kode_alat`, `kategori`, `lokasi`, `deskripsi`, `foto`) VALUES
(1, 'Mikroskop', 10, 'baik', 'tersedia', 10, 0, 'ALT-001', 'Biologi', 'Lemari A1', 'Mikroskop cahaya untuk praktikum biologi.', NULL),
(2, 'Gelas Ukur 100 ml', 20, 'baik', 'tersedia', 20, 0, 'ALT-002', 'Kimia', 'Lemari B1', 'Gelas ukur kaca untuk mengukur volume cairan.', NULL),
(3, 'Multimeter Digital', 8, 'baik', 'tersedia', 8, 0, 'ALT-003', 'Fisika', 'Lemari C2', 'Alat ukur tegangan, arus, dan hambatan.', NULL),
(4, 'Neraca Digital', 5, 'baik', 'tersedia', 5, 0, 'ALT-004', 'Fisika', 'Lemari C1', 'Neraca ketelitian 0,01 gram.', NULL),
(5, 'Proyektor', 2, 'baik', 'tersedia', 2, 0, 'ALT-005', 'Multimedia', 'Ruang Guru', 'Proyektor untuk presentasi kelas.', NULL),
(6, 'Laptop Praktikum', 15, 'baik', 'tersedia', 15, 0, 'ALT-006', 'Komputer', 'Lab Komputer', 'Laptop untuk pembelajaran TIK.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `detail_peminjaman`
--

CREATE TABLE `detail_peminjaman` (
  `id_detail` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `id_alat` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_peminjaman`
--

INSERT INTO `detail_peminjaman` (`id_detail`, `id_peminjaman`, `id_alat`, `jumlah`) VALUES
(1, 1, 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id_guru` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `mata_pelajaran` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id_guru`, `id_user`, `nama_guru`, `mata_pelajaran`) VALUES
(1, 2, 'Guru Contoh', 'IPA');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id_jadwal` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time NOT NULL,
  `status` enum('tersedia','tidak_tersedia') NOT NULL DEFAULT 'tersedia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id_jadwal`, `tanggal`, `waktu_mulai`, `waktu_selesai`, `status`) VALUES
(1, '2026-11-16', '07:00:00', '15:00:00', 'tersedia'),
(2, '2026-11-17', '07:00:00', '15:00:00', 'tersedia'),
(3, '2026-11-18', '07:00:00', '15:00:00', 'tersedia'),
(4, '2026-11-19', '07:00:00', '15:00:00', 'tersedia'),
(5, '2026-11-20', '07:00:00', '15:00:00', 'tersedia'),
(6, '2026-11-23', '07:00:00', '15:00:00', 'tersedia'),
(7, '2026-11-24', '07:00:00', '15:00:00', 'tersedia'),
(8, '2026-11-25', '07:00:00', '15:00:00', 'tersedia'),
(9, '2026-11-26', '07:00:00', '15:00:00', 'tersedia'),
(10, '2026-11-27', '07:00:00', '15:00:00', 'tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `aksi` varchar(50) NOT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `waktu` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_log`, `id_user`, `aksi`, `detail`, `ip`, `waktu`) VALUES
(1, 3, 'login', 'siswa01', '::1', '2026-11-14 16:16:02'),
(2, 3, 'ajukan', 'Peminjaman #1', '::1', '2026-11-14 16:17:01'),
(3, 3, 'logout', 'Siswa Contoh', '::1', '2026-11-14 16:17:07'),
(4, 1, 'login', 'admin01', '::1', '2026-11-14 16:17:20'),
(5, 1, 'status_setujui', 'Peminjaman #1', '::1', '2026-11-14 16:18:06'),
(6, 1, 'logout', 'Pengelola Lab', '::1', '2026-11-14 16:18:45'),
(7, 3, 'login', 'siswa01', '::1', '2026-11-14 16:18:47'),
(8, 3, 'logout', 'Siswa Contoh', '::1', '2026-11-14 16:20:09'),
(9, 1, 'login', 'admin01', '::1', '2026-11-14 16:20:18'),
(10, 1, 'status_mulai', 'Peminjaman #1', '::1', '2026-11-14 16:20:26'),
(11, 1, 'pengembalian', 'Peminjaman #1, kondisi baik', '::1', '2026-11-14 16:20:53');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `pesan` varchar(255) NOT NULL,
  `waktu` datetime NOT NULL DEFAULT current_timestamp(),
  `status_baca` enum('belum_dibaca','sudah_dibaca') NOT NULL DEFAULT 'belum_dibaca'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_user`, `pesan`, `waktu`, `status_baca`) VALUES
(1, 1, 'Pengajuan baru #1 dari Siswa Contoh menunggu persetujuan.', '2026-11-14 16:17:01', 'sudah_dibaca'),
(2, 3, 'Pengajuan #1 berhasil dikirim dan menunggu persetujuan.', '2026-11-14 16:17:01', 'sudah_dibaca'),
(3, 3, 'Pengajuan peminjaman #1 disetujui. Ambil alat pada jadwal yang dipilih.', '2026-11-14 16:18:06', 'sudah_dibaca'),
(4, 3, 'Alat untuk peminjaman #1 sudah diserahkan. Kembalikan tepat waktu.', '2026-11-14 16:20:26', 'belum_dibaca'),
(5, 3, 'Pengembalian peminjaman #1 sudah dicatat. Kondisi: baik.', '2026-11-14 16:20:53', 'belum_dibaca');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_peminjaman` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_jadwal` int(11) NOT NULL,
  `tanggal_pengajuan` datetime NOT NULL DEFAULT current_timestamp(),
  `tanggal_peminjaman` date NOT NULL,
  `status_peminjaman` enum('menunggu','disetujui','ditolak','sedang_dipinjam','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `keperluan` varchar(255) DEFAULT NULL,
  `catatan_pengelola` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id_peminjaman`, `id_user`, `id_jadwal`, `tanggal_pengajuan`, `tanggal_peminjaman`, `status_peminjaman`, `keperluan`, `catatan_pengelola`) VALUES
(1, 3, 1, '2026-11-14 16:17:01', '2026-11-16', 'selesai', 'untuk presentasi', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `kunci` varchar(50) NOT NULL,
  `nilai` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES
('skema_versi', '1');

-- --------------------------------------------------------

--
-- Table structure for table `pengelola`
--

CREATE TABLE `pengelola` (
  `id_pengelola` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_pengelola` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengelola`
--

INSERT INTO `pengelola` (`id_pengelola`, `id_user`, `nama_pengelola`) VALUES
(1, 1, 'Pengelola Lab');

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id_pengembalian` int(11) NOT NULL,
  `id_peminjaman` int(11) NOT NULL,
  `tanggal_pengembalian` datetime NOT NULL DEFAULT current_timestamp(),
  `kondisi_kembali` enum('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `catatan` varchar(255) DEFAULT NULL,
  `jumlah_rusak` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengembalian`
--

INSERT INTO `pengembalian` (`id_pengembalian`, `id_peminjaman`, `tanggal_pengembalian`, `kondisi_kembali`, `catatan`, `jumlah_rusak`) VALUES
(1, 1, '2026-11-14 16:20:53', 'baik', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `kelas` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `id_user`, `nama_siswa`, `kelas`) VALUES
(1, 3, 'Siswa Contoh', 'IX A');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('siswa','guru','pengelola') NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `terakhir_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama`, `role`, `status`, `email`, `no_hp`, `terakhir_login`) VALUES
(1, 'admin01', '$2y$10$joVs0ydFqeXI8zqHqNo8j.DHzN0O3grpt4AMEqda83BKkNYQj/ieK', 'Pengelola Lab', 'pengelola', 'aktif', NULL, NULL, '2026-11-14 16:20:18'),
(2, 'guru01', 'password123', 'Guru Contoh', 'guru', 'aktif', NULL, NULL, NULL),
(3, 'siswa01', '$2y$10$btfP7yXb30TwMr9qoBfsDONgxqwucnA//2p52zcRcMDTpWDzlHfvu', 'Siswa Contoh', 'siswa', 'aktif', NULL, NULL, '2026-11-14 16:18:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id_alat`),
  ADD KEY `idx_alat_kategori` (`kategori`);

--
-- Indexes for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `idx_detail_pinjam` (`id_peminjaman`),
  ADD KEY `idx_detail_alat` (`id_alat`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_guru`),
  ADD UNIQUE KEY `uk_guru_user` (`id_user`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `idx_jadwal_tanggal` (`tanggal`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_aksi_ip_waktu` (`aksi`,`ip`,`waktu`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `idx_notif_user` (`id_user`,`status_baca`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD KEY `idx_pinjam_user` (`id_user`),
  ADD KEY `idx_pinjam_jadwal` (`id_jadwal`),
  ADD KEY `idx_pinjam_status` (`status_peminjaman`),
  ADD KEY `idx_pinjam_tanggal` (`tanggal_peminjaman`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`kunci`);

--
-- Indexes for table `pengelola`
--
ALTER TABLE `pengelola`
  ADD PRIMARY KEY (`id_pengelola`),
  ADD UNIQUE KEY `uk_pengelola_user` (`id_user`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id_pengembalian`),
  ADD KEY `idx_kembali_pinjam` (`id_peminjaman`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `uk_siswa_user` (`id_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uk_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alat`
--
ALTER TABLE `alat`
  MODIFY `id_alat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id_guru` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengelola`
--
ALTER TABLE `pengelola`
  MODIFY `id_pengelola` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id_pengembalian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD CONSTRAINT `fk_detail_alat` FOREIGN KEY (`id_alat`) REFERENCES `alat` (`id_alat`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_pinjam` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `guru`
--
ALTER TABLE `guru`
  ADD CONSTRAINT `fk_guru_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_pinjam_jadwal` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal` (`id_jadwal`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pinjam_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `pengelola`
--
ALTER TABLE `pengelola`
  ADD CONSTRAINT `fk_pengelola_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `fk_kembali_pinjam` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
