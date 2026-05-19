<?php
// Deklarasi variabel untuk konfigurasi database
$host       = "localhost";
$username   = "root"; // Username bawaan XAMPP
$password   = "";     // Password bawaan XAMPP (kosong)
$database   = "db_siak";

// Membuat koneksi menggunakan ekstensi MySQLi
$koneksi = mysqli_connect($host, $username, $password, $database);

// Mengecek apakah koneksi berhasil
if (!$koneksi) {
    // Jika gagal, tampilkan pesan error dan hentikan eksekusi
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Opsional: Hapus atau jadikan komentar baris di bawah ini saat aplikasi sudah berjalan
// echo "Koneksi ke database db_siak berhasil!";
?>