<?php
// Memulai session untuk mengakses data session yang sedang aktif
session_start();

// Menghapus semua variabel session
session_unset();

// Menghancurkan session sepenuhnya
session_destroy();

// Mengarahkan pengguna kembali ke form login (index.php)
header("Location: index.php");
exit;
?>