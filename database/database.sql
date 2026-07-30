-- =============================================================
-- DATABASE: db_sia
-- Sistem Informasi Akademik Sederhana
-- Proyek UAS Pemrograman Internet
--
-- Cara import:
-- 1. Buka phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Buat database baru bernama db_sia
-- 3. Klik tab "Import", pilih file ini, lalu klik "Go"
-- Atau via CLI:
--   mysql -u root -p db_sia < database.sql
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `db_sia` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_sia`;

-- =============================================================
-- Hapus tabel jika sudah ada (urutan menghindari error FK)
-- =============================================================
DROP TABLE IF EXISTS `nilai`;
DROP TABLE IF EXISTS `krs`;
DROP TABLE IF EXISTS `kelas`;
DROP TABLE IF EXISTS `mata_kuliah`;
DROP TABLE IF EXISTS `tahun_akademik`;
DROP TABLE IF EXISTS `mahasiswa`;
DROP TABLE IF EXISTS `dosen`;
DROP TABLE IF EXISTS `admin`;
DROP TABLE IF EXISTS `users`;

-- =============================================================
-- TABEL: users
-- Menyimpan akun login untuk semua role (admin, dosen, mahasiswa)
-- =============================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'dosen', 'mahasiswa') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABEL: admin
-- Data profil untuk role admin
-- =============================================================
CREATE TABLE `admin` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABEL: dosen
-- Data profil dosen
-- =============================================================
CREATE TABLE `dosen` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `nidn` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('L', 'P') NOT NULL,
  `no_hp` VARCHAR(20) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_dosen_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABEL: mahasiswa
-- Data profil mahasiswa
-- =============================================================
CREATE TABLE `mahasiswa` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `nim` VARCHAR(20) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('L', 'P') NOT NULL,
  `angkatan` YEAR NOT NULL,
  `no_hp` VARCHAR(20) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mahasiswa_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABEL: tahun_akademik
-- Data tahun ajaran dan semester
-- =============================================================
CREATE TABLE `tahun_akademik` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `tahun` VARCHAR(20) NOT NULL COMMENT 'contoh: 2024/2025',
  `semester` ENUM('Ganjil', 'Genap') NOT NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'nonaktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_tahun_semester` (`tahun`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABEL: mata_kuliah
-- Master data mata kuliah
-- =============================================================
CREATE TABLE `mata_kuliah` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_mk` VARCHAR(20) NOT NULL UNIQUE,
  `nama_mk` VARCHAR(150) NOT NULL,
  `sks` TINYINT UNSIGNED NOT NULL,
  `semester` TINYINT UNSIGNED NOT NULL COMMENT 'semester ke berapa mk ini ditawarkan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABEL: kelas
-- Kelas perkuliahan: relasi mata kuliah - dosen - tahun akademik
-- =============================================================
CREATE TABLE `kelas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `mata_kuliah_id` INT UNSIGNED NOT NULL,
  `dosen_id` INT UNSIGNED NOT NULL,
  `tahun_akademik_id` INT UNSIGNED NOT NULL,
  `nama_kelas` VARCHAR(10) NOT NULL COMMENT 'contoh: A, B, C',
  `hari` VARCHAR(20) DEFAULT NULL,
  `jam` VARCHAR(30) DEFAULT NULL,
  `ruang` VARCHAR(20) DEFAULT NULL,
  `kuota` INT UNSIGNED NOT NULL DEFAULT 30,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_kelas_matkul` FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliah`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kelas_dosen` FOREIGN KEY (`dosen_id`) REFERENCES `dosen`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kelas_tahun` FOREIGN KEY (`tahun_akademik_id`) REFERENCES `tahun_akademik`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABEL: krs
-- Kartu Rencana Studi: relasi mahasiswa - kelas per tahun akademik
-- =============================================================
CREATE TABLE `krs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `mahasiswa_id` INT UNSIGNED NOT NULL,
  `kelas_id` INT UNSIGNED NOT NULL,
  `tahun_akademik_id` INT UNSIGNED NOT NULL,
  `tanggal_input` DATE NOT NULL,
  `status` ENUM('diajukan', 'disetujui', 'ditolak') NOT NULL DEFAULT 'diajukan',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_krs_mahasiswa` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_krs_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_krs_tahun` FOREIGN KEY (`tahun_akademik_id`) REFERENCES `tahun_akademik`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `uniq_krs_mhs_kelas` (`mahasiswa_id`, `kelas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABEL: nilai
-- Nilai akhir mahasiswa untuk setiap data KRS
-- =============================================================
CREATE TABLE `nilai` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `krs_id` INT UNSIGNED NOT NULL UNIQUE,
  `nilai_angka` DECIMAL(5,2) NOT NULL COMMENT 'nilai 0-100',
  `nilai_huruf` ENUM('A','A-','B+','B','B-','C+','C','D','E') NOT NULL,
  `bobot` DECIMAL(3,2) NOT NULL COMMENT 'bobot IPK: A=4.00 ... E=0.00',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_nilai_krs` FOREIGN KEY (`krs_id`) REFERENCES `krs`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- DATA DUMMY
-- 1 Admin, 5 Dosen, 20 Mahasiswa, 10 Mata Kuliah, 5 Kelas,
-- 3 Tahun Akademik, 100 KRS, 100 Nilai
-- =============================================================
-- =========================================================
-- DUMMY DATA: USERS
-- Semua password dummy = 'password123' (sudah di-hash bcrypt)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G
-- =========================================================
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'admin');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (2, 'dosen1', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'dosen');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (3, 'dosen2', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'dosen');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (4, 'dosen3', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'dosen');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (5, 'dosen4', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'dosen');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (6, 'dosen5', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'dosen');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (7, '20230001', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (8, '20230002', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (9, '20230003', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (10, '20230004', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (11, '20230005', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (12, '20230006', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (13, '20230007', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (14, '20230008', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (15, '20230009', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (16, '20230010', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (17, '20230011', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (18, '20230012', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (19, '20230013', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (20, '20230014', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (21, '20230015', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (22, '20230016', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (23, '20230017', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (24, '20230018', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (25, '20230019', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES (26, '20230020', '$2y$10$92IXUNpkjO0rOQ5byMi.YeYS2t.n34x7ykr9jUj3n6d5X1w4z9j0G', 'mahasiswa');

-- =========================================================
-- DUMMY DATA: ADMIN
-- =========================================================
INSERT INTO `admin` (`id`, `user_id`, `nama`) VALUES (1, 1, 'Administrator Utama');

-- =========================================================
-- DUMMY DATA: DOSEN
-- =========================================================
INSERT INTO `dosen` (`id`, `user_id`, `nidn`, `nama`, `jenis_kelamin`, `no_hp`, `alamat`) VALUES (1, 2, '0011058501', 'Dr. Budi Santoso, M.Kom.', 'L', '08100111111', 'Jl. Merdeka No. 1, Bandung');
INSERT INTO `dosen` (`id`, `user_id`, `nidn`, `nama`, `jenis_kelamin`, `no_hp`, `alamat`) VALUES (2, 3, '0022067602', 'Siti Aminah, S.Kom., M.T.', 'P', '08100222222', 'Jl. Merdeka No. 2, Jakarta');
INSERT INTO `dosen` (`id`, `user_id`, `nidn`, `nama`, `jenis_kelamin`, `no_hp`, `alamat`) VALUES (3, 4, '0033078703', 'Ir. Ahmad Fauzi, M.Eng.', 'L', '08100333333', 'Jl. Merdeka No. 3, Surabaya');
INSERT INTO `dosen` (`id`, `user_id`, `nidn`, `nama`, `jenis_kelamin`, `no_hp`, `alamat`) VALUES (4, 5, '0044089804', 'Dewi Kartika, S.Si., M.Cs.', 'P', '08100444444', 'Jl. Merdeka No. 4, Yogyakarta');
INSERT INTO `dosen` (`id`, `user_id`, `nidn`, `nama`, `jenis_kelamin`, `no_hp`, `alamat`) VALUES (5, 6, '0055099905', 'Rudi Hartono, S.Kom., M.Kom.', 'L', '08100555555', 'Jl. Merdeka No. 5, Semarang');

-- =========================================================
-- DUMMY DATA: MAHASISWA
-- =========================================================
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (1, 7, '20230001', 'Andi Pratama', 'L', 2023, '08200111111', 'Jl. Pendidikan No. 1, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (2, 8, '20230002', 'Budi Saputra', 'P', 2023, '08200222222', 'Jl. Pendidikan No. 2, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (3, 9, '20230003', 'Citra Wijaya', 'L', 2023, '08200333333', 'Jl. Pendidikan No. 3, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (4, 10, '20230004', 'Dian Kusuma', 'P', 2023, '08200444444', 'Jl. Pendidikan No. 4, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (5, 11, '20230005', 'Eka Ramadhan', 'L', 2023, '08200555555', 'Jl. Pendidikan No. 5, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (6, 12, '20230006', 'Fajar Utami', 'P', 2023, '08200666666', 'Jl. Pendidikan No. 6, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (7, 13, '20230007', 'Gita Nugroho', 'L', 2023, '08200777777', 'Jl. Pendidikan No. 7, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (8, 14, '20230008', 'Hadi Permata', 'P', 2023, '08200888888', 'Jl. Pendidikan No. 8, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (9, 15, '20230009', 'Indah Setiawan', 'L', 2023, '08200999999', 'Jl. Pendidikan No. 9, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (10, 16, '20230010', 'Joko Anggraini', 'P', 2023, '08201111110', 'Jl. Pendidikan No. 10, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (11, 17, '20230011', 'Kiki Firmansyah', 'L', 2023, '08201222221', 'Jl. Pendidikan No. 11, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (12, 18, '20230012', 'Lina Lestari', 'P', 2023, '08201333332', 'Jl. Pendidikan No. 12, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (13, 19, '20230013', 'Made Hidayat', 'L', 2023, '08201444443', 'Jl. Pendidikan No. 13, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (14, 20, '20230014', 'Nanda Wulandari', 'P', 2023, '08201555554', 'Jl. Pendidikan No. 14, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (15, 21, '20230015', 'Oki Syahputra', 'L', 2023, '08201666665', 'Jl. Pendidikan No. 15, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (16, 22, '20230016', 'Putri Rahmawati', 'P', 2023, '08201777776', 'Jl. Pendidikan No. 16, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (17, 23, '20230017', 'Qori Gunawan', 'L', 2023, '08201888887', 'Jl. Pendidikan No. 17, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (18, 24, '20230018', 'Rian Safitri', 'P', 2023, '08201999998', 'Jl. Pendidikan No. 18, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (19, 25, '20230019', 'Sinta Maulana', 'L', 2023, '08202111109', 'Jl. Pendidikan No. 19, Bandung');
INSERT INTO `mahasiswa` (`id`, `user_id`, `nim`, `nama`, `jenis_kelamin`, `angkatan`, `no_hp`, `alamat`) VALUES (20, 26, '20230020', 'Tono Puspita', 'P', 2023, '08202222220', 'Jl. Pendidikan No. 20, Bandung');

-- =========================================================
-- DUMMY DATA: TAHUN AKADEMIK
-- =========================================================
INSERT INTO `tahun_akademik` (`id`, `tahun`, `semester`, `status`) VALUES (1, '2023/2024', 'Ganjil', 'nonaktif');
INSERT INTO `tahun_akademik` (`id`, `tahun`, `semester`, `status`) VALUES (2, '2023/2024', 'Genap', 'nonaktif');
INSERT INTO `tahun_akademik` (`id`, `tahun`, `semester`, `status`) VALUES (3, '2024/2025', 'Ganjil', 'aktif');

-- =========================================================
-- DUMMY DATA: MATA KULIAH
-- =========================================================
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (1, 'MK001', 'Algoritma dan Pemrograman', 3, 1);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (2, 'MK002', 'Struktur Data', 3, 2);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (3, 'MK003', 'Basis Data', 3, 3);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (4, 'MK004', 'Pemrograman Web', 3, 3);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (5, 'MK005', 'Jaringan Komputer', 2, 4);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (6, 'MK006', 'Sistem Operasi', 3, 4);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (7, 'MK007', 'Rekayasa Perangkat Lunak', 3, 5);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (8, 'MK008', 'Kecerdasan Buatan', 3, 6);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (9, 'MK009', 'Pemrograman Mobile', 3, 5);
INSERT INTO `mata_kuliah` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`) VALUES (10, 'MK010', 'Interaksi Manusia dan Komputer', 2, 4);

-- =========================================================
-- DUMMY DATA: KELAS
-- (5 kelas, mengambil 5 dari 10 mata kuliah yang tersedia)
-- =========================================================
INSERT INTO `kelas` (`id`, `mata_kuliah_id`, `dosen_id`, `tahun_akademik_id`, `nama_kelas`, `hari`, `jam`, `ruang`, `kuota`) VALUES (1, 1, 1, 3, 'A', 'Senin', '08:00-10:30', 'R101', 30);
INSERT INTO `kelas` (`id`, `mata_kuliah_id`, `dosen_id`, `tahun_akademik_id`, `nama_kelas`, `hari`, `jam`, `ruang`, `kuota`) VALUES (2, 2, 2, 3, 'A', 'Selasa', '10:30-13:00', 'R102', 30);
INSERT INTO `kelas` (`id`, `mata_kuliah_id`, `dosen_id`, `tahun_akademik_id`, `nama_kelas`, `hari`, `jam`, `ruang`, `kuota`) VALUES (3, 3, 3, 3, 'A', 'Rabu', '08:00-10:30', 'R103', 30);
INSERT INTO `kelas` (`id`, `mata_kuliah_id`, `dosen_id`, `tahun_akademik_id`, `nama_kelas`, `hari`, `jam`, `ruang`, `kuota`) VALUES (4, 4, 4, 3, 'A', 'Kamis', '13:00-15:30', 'R104', 30);
INSERT INTO `kelas` (`id`, `mata_kuliah_id`, `dosen_id`, `tahun_akademik_id`, `nama_kelas`, `hari`, `jam`, `ruang`, `kuota`) VALUES (5, 7, 5, 3, 'A', 'Jumat', '08:00-10:30', 'R105', 30);

-- =========================================================
-- DUMMY DATA: KRS
-- (20 mahasiswa x 5 kelas = 100 data KRS)
-- =========================================================
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (1, 1, 1, 3, '2024-08-11', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (2, 1, 2, 3, '2024-08-12', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (3, 1, 3, 3, '2024-08-13', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (4, 1, 4, 3, '2024-08-14', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (5, 1, 5, 3, '2024-08-15', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (6, 2, 1, 3, '2024-08-16', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (7, 2, 2, 3, '2024-08-17', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (8, 2, 3, 3, '2024-08-18', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (9, 2, 4, 3, '2024-08-19', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (10, 2, 5, 3, '2024-08-20', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (11, 3, 1, 3, '2024-08-21', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (12, 3, 2, 3, '2024-08-22', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (13, 3, 3, 3, '2024-08-23', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (14, 3, 4, 3, '2024-08-24', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (15, 3, 5, 3, '2024-08-10', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (16, 4, 1, 3, '2024-08-11', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (17, 4, 2, 3, '2024-08-12', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (18, 4, 3, 3, '2024-08-13', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (19, 4, 4, 3, '2024-08-14', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (20, 4, 5, 3, '2024-08-15', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (21, 5, 1, 3, '2024-08-16', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (22, 5, 2, 3, '2024-08-17', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (23, 5, 3, 3, '2024-08-18', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (24, 5, 4, 3, '2024-08-19', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (25, 5, 5, 3, '2024-08-20', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (26, 6, 1, 3, '2024-08-21', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (27, 6, 2, 3, '2024-08-22', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (28, 6, 3, 3, '2024-08-23', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (29, 6, 4, 3, '2024-08-24', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (30, 6, 5, 3, '2024-08-10', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (31, 7, 1, 3, '2024-08-11', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (32, 7, 2, 3, '2024-08-12', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (33, 7, 3, 3, '2024-08-13', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (34, 7, 4, 3, '2024-08-14', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (35, 7, 5, 3, '2024-08-15', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (36, 8, 1, 3, '2024-08-16', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (37, 8, 2, 3, '2024-08-17', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (38, 8, 3, 3, '2024-08-18', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (39, 8, 4, 3, '2024-08-19', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (40, 8, 5, 3, '2024-08-20', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (41, 9, 1, 3, '2024-08-21', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (42, 9, 2, 3, '2024-08-22', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (43, 9, 3, 3, '2024-08-23', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (44, 9, 4, 3, '2024-08-24', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (45, 9, 5, 3, '2024-08-10', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (46, 10, 1, 3, '2024-08-11', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (47, 10, 2, 3, '2024-08-12', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (48, 10, 3, 3, '2024-08-13', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (49, 10, 4, 3, '2024-08-14', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (50, 10, 5, 3, '2024-08-15', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (51, 11, 1, 3, '2024-08-16', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (52, 11, 2, 3, '2024-08-17', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (53, 11, 3, 3, '2024-08-18', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (54, 11, 4, 3, '2024-08-19', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (55, 11, 5, 3, '2024-08-20', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (56, 12, 1, 3, '2024-08-21', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (57, 12, 2, 3, '2024-08-22', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (58, 12, 3, 3, '2024-08-23', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (59, 12, 4, 3, '2024-08-24', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (60, 12, 5, 3, '2024-08-10', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (61, 13, 1, 3, '2024-08-11', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (62, 13, 2, 3, '2024-08-12', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (63, 13, 3, 3, '2024-08-13', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (64, 13, 4, 3, '2024-08-14', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (65, 13, 5, 3, '2024-08-15', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (66, 14, 1, 3, '2024-08-16', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (67, 14, 2, 3, '2024-08-17', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (68, 14, 3, 3, '2024-08-18', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (69, 14, 4, 3, '2024-08-19', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (70, 14, 5, 3, '2024-08-20', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (71, 15, 1, 3, '2024-08-21', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (72, 15, 2, 3, '2024-08-22', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (73, 15, 3, 3, '2024-08-23', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (74, 15, 4, 3, '2024-08-24', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (75, 15, 5, 3, '2024-08-10', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (76, 16, 1, 3, '2024-08-11', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (77, 16, 2, 3, '2024-08-12', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (78, 16, 3, 3, '2024-08-13', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (79, 16, 4, 3, '2024-08-14', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (80, 16, 5, 3, '2024-08-15', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (81, 17, 1, 3, '2024-08-16', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (82, 17, 2, 3, '2024-08-17', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (83, 17, 3, 3, '2024-08-18', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (84, 17, 4, 3, '2024-08-19', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (85, 17, 5, 3, '2024-08-20', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (86, 18, 1, 3, '2024-08-21', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (87, 18, 2, 3, '2024-08-22', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (88, 18, 3, 3, '2024-08-23', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (89, 18, 4, 3, '2024-08-24', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (90, 18, 5, 3, '2024-08-10', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (91, 19, 1, 3, '2024-08-11', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (92, 19, 2, 3, '2024-08-12', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (93, 19, 3, 3, '2024-08-13', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (94, 19, 4, 3, '2024-08-14', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (95, 19, 5, 3, '2024-08-15', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (96, 20, 1, 3, '2024-08-16', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (97, 20, 2, 3, '2024-08-17', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (98, 20, 3, 3, '2024-08-18', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (99, 20, 4, 3, '2024-08-19', 'disetujui');
INSERT INTO `krs` (`id`, `mahasiswa_id`, `kelas_id`, `tahun_akademik_id`, `tanggal_input`, `status`) VALUES (100, 20, 5, 3, '2024-08-20', 'disetujui');

-- =========================================================
-- DUMMY DATA: NILAI
-- (100 data nilai, 1 untuk setiap data KRS)
-- =========================================================
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (1, 1, 70, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (2, 2, 66, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (3, 3, 80, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (4, 4, 69, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (5, 5, 98, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (6, 6, 87, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (7, 7, 84, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (8, 8, 74, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (9, 9, 98, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (10, 10, 84, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (11, 11, 80, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (12, 12, 61, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (13, 13, 67, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (14, 14, 81, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (15, 15, 45, 'D', 1.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (16, 16, 97, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (17, 17, 96, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (18, 18, 70, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (19, 19, 69, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (20, 20, 97, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (21, 21, 94, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (22, 22, 64, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (23, 23, 57, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (24, 24, 70, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (25, 25, 92, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (26, 26, 60, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (27, 27, 55, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (28, 28, 78, 'B+', 3.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (29, 29, 72, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (30, 30, 96, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (31, 31, 82, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (32, 32, 65, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (33, 33, 71, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (34, 34, 71, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (35, 35, 97, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (36, 36, 84, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (37, 37, 82, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (38, 38, 60, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (39, 39, 80, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (40, 40, 63, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (41, 41, 81, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (42, 42, 59, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (43, 43, 57, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (44, 44, 83, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (45, 45, 78, 'B+', 3.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (46, 46, 89, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (47, 47, 84, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (48, 48, 74, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (49, 49, 79, 'B+', 3.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (50, 50, 76, 'B+', 3.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (51, 51, 8, 'E', 0.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (52, 52, 70, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (53, 53, 60, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (54, 54, 90, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (55, 55, 63, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (56, 56, 73, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (57, 57, 78, 'B+', 3.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (58, 58, 74, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (59, 59, 55, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (60, 60, 65, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (61, 61, 69, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (62, 62, 62, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (63, 63, 98, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (64, 64, 85, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (65, 65, 54, 'D', 1.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (66, 66, 69, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (67, 67, 64, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (68, 68, 57, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (69, 69, 64, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (70, 70, 71, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (71, 71, 76, 'B+', 3.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (72, 72, 74, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (73, 73, 59, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (74, 74, 80, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (75, 75, 96, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (76, 76, 57, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (77, 77, 81, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (78, 78, 55, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (79, 79, 100, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (80, 80, 64, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (81, 81, 61, 'C+', 2.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (82, 82, 69, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (83, 83, 98, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (84, 84, 54, 'D', 1.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (85, 85, 71, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (86, 86, 68, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (87, 87, 23, 'E', 0.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (88, 88, 79, 'B+', 3.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (89, 89, 76, 'B+', 3.50);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (90, 90, 82, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (91, 91, 92, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (92, 92, 70, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (93, 93, 86, 'A', 4.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (94, 94, 80, 'A-', 3.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (95, 95, 55, 'C', 2.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (96, 96, 72, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (97, 97, 66, 'B-', 2.75);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (98, 98, 74, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (99, 99, 71, 'B', 3.00);
INSERT INTO `nilai` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`) VALUES (100, 100, 63, 'C+', 2.50);