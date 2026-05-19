# Sistem Informasi Akademik (SIAK) - PHP Native

Aplikasi ini merupakan sistem informasi akademik sederhana yang dibangun menggunakan **PHP Native** dan **MySQL**, dirancang untuk memenuhi tugas pengembangan aplikasi web.

## Fitur Utama
*   **RBAC (Role-Based Access Control):** Pengaturan akses berbasis peran (Admin, Dosen, Mahasiswa).
*   **CRUD Data:** Pengelolaan pengguna dan mata kuliah oleh Admin.
*   **Pengisian KRS:** Mahasiswa dapat memilih mata kuliah secara interaktif.
*   **Input Nilai:** Dosen dapat menginput nilai dan sistem melakukan konversi huruf (A-E) secara otomatis.
*   **KHS:** Mahasiswa dapat melihat hasil studi dan perhitungan IP semester.

## Persyaratan
*   **XAMPP** (Versi PHP 7.x atau 8.x).
*   Browser (Chrome/Firefox/Edge).

## Cara Menjalankan Aplikasi
1.  **Instalasi:**
    *   Pastikan **XAMPP Control Panel** sudah terinstal.
    *   Letakkan folder proyek Anda ke dalam direktori `C:\xampp\htdocs\siak\`.
2.  **Konfigurasi Database:**
    *   Jalankan **Apache** dan **MySQL** melalui XAMPP Control Panel.
    *   Buka browser dan akses `http://localhost/phpmyadmin`.
    *   Buat database baru dengan nama `db_siak`.
    *   Pilih database `db_siak`, klik tab **SQL**, lalu *copy-paste* seluruh kode dari file `database.sql` dan klik **Go**.
3.  **Akses Aplikasi:**
    *   Buka browser dan akses `http://localhost/siak/`.

## Akun Demo untuk Demonstrasi
Gunakan akun berikut untuk mendemonstrasikan hak akses masing-masing peran:

| Peran | Username | Password |
| :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` |
| **Dosen** | `dosen1` | `dosen123` |
| **Mahasiswa** | `mhs1` | `mhs123` |

## Struktur Database
*   **`users`**: Menyimpan data akun login (id, username, password, role).
*   **`mahasiswa`**: Profil mahasiswa (nim, nama, id_user).
*   **`matakuliah`**: Daftar mata kuliah (kode_mk, nama_mk, sks, semester).
*   **`krs`**: Data pengambilan mata kuliah mahasiswa (id, nim, kode_mk).
*   **`nilai`**: Data nilai akademik (id, nim, kode_mk, nilai_angka, nilai_huruf).

## Catatan Tambahan
*   Aplikasi ini menggunakan **Bootstrap 5 CDN** untuk tampilan antarmuka. Pastikan koneksi internet aktif saat menjalankan aplikasi agar *styling* dapat dimuat dengan sempurna.
*   Keamanan: Sistem telah diimplementasikan dengan **Prepared Statements** untuk mencegah *SQL Injection*.