<?php
/**
 * index.php
 * Entry point utama aplikasi.
 * Mengarahkan pengunjung ke halaman login.
 *
 * Nanti pada tahap autentikasi, file ini akan diperbarui
 * agar mengecek session dan mengarahkan ke dashboard
 * sesuai role (admin/dosen/mahasiswa) jika sudah login.
 */

require_once __DIR__ . '/config/config.php';

header('Location: ' . BASE_URL . 'login.php');
exit;
