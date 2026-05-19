-- Membuat database dan menggunakannya
CREATE DATABASE IF NOT EXISTS db_siak;
USE db_siak;

-- Tabel Users
-- Digunakan untuk login semua aktor aplikasi
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Disarankan menggunakan password_hash() di PHP
    role ENUM('admin', 'dosen', 'mahasiswa') NOT NULL
) ENGINE=InnoDB;

-- Tabel Mahasiswa
-- Menyimpan profil mahasiswa, berelasi dengan tabel users
CREATE TABLE mahasiswa (
    nim VARCHAR(20) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    id_user INT NOT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Tabel Mata Kuliah
-- Menyimpan daftar mata kuliah yang tersedia
CREATE TABLE matakuliah (
    kode_mk VARCHAR(20) PRIMARY KEY,
    nama_mk VARCHAR(100) NOT NULL,
    sks INT NOT NULL,
    semester INT NOT NULL
) ENGINE=InnoDB;

-- Tabel KRS (Kartu Rencana Studi)
-- Mencatat mata kuliah yang diambil oleh mahasiswa (Relasi Many-to-Many)
CREATE TABLE krs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL,
    kode_mk VARCHAR(20) NOT NULL,
    FOREIGN KEY (nim) REFERENCES mahasiswa(nim) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (kode_mk) REFERENCES matakuliah(kode_mk) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Tabel Nilai
-- Mencatat nilai yang diperoleh mahasiswa untuk mata kuliah tertentu
CREATE TABLE nilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(20) NOT NULL,
    kode_mk VARCHAR(20) NOT NULL,
    nilai_angka DECIMAL(5,2) DEFAULT NULL,
    nilai_huruf VARCHAR(2) DEFAULT NULL,
    FOREIGN KEY (nim) REFERENCES mahasiswa(nim) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (kode_mk) REFERENCES matakuliah(kode_mk) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;