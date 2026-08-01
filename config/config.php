<?php
/**
 * File Konfigurasi Utama Aplikasi
 * Sistem Informasi Akademik Sederhana
 *
 * File ini berisi konfigurasi dasar aplikasi seperti:
 * - Pengaturan session
 * - Timezone
 * - Base URL
 * - Mode error reporting
 *
 * Catatan: Konfigurasi koneksi database akan ditambahkan
 * pada tahap berikutnya (Tahap 3 - Koneksi Database PDO).
 */

// Mencegah file diakses langsung tanpa melalui aplikasi
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Mengaktifkan error reporting untuk lingkungan development
// Pada production, sebaiknya diubah menjadi 0 / false
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone aplikasi
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi session sebelum session_start dipanggil
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Base URL aplikasi (sesuaikan dengan folder project di htdocs XAMPP)
define('BASE_URL', '/siak-app/');

// Nama aplikasi
define('APP_NAME', 'Sistem Informasi Akademik');

// Path direktori penting
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
