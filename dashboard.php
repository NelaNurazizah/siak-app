<?php
session_start();

// Proteksi Session: Cek apakah user sudah login
// Jika belum ada session 'role', paksa kembali ke halaman login (index.php)
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

// Mengambil data dari session untuk ditampilkan
$username = $_SESSION['username'];
$role     = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SIAK - <?= ucfirst($role); ?></title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (Opsional untuk ikon menu) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        /* Memastikan sidebar full height */
        .sidebar {
            min-height: 100vh;
            width: 250px;
        }
        .main-content {
            width: 100%;
        }
    </style>
</head>
<body>

<div class="d-flex flex-nowrap">
    
    <!-- Sidebar -->
    <div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar">
        <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <span class="fs-4 fw-bold"><i class="bi bi-mortarboard-fill me-2"></i>SIAK App</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <!-- Menu Dashboard (Tampil untuk semua role) -->
            <li class="nav-item mb-1">
                <a href="dashboard.php" class="nav-link active" aria-current="page">
                    <i class="bi bi-house-door me-2"></i> Dashboard
                </a>
            </li>

            <!-- Menu Khusus Mahasiswa -->
            <?php if ($role == 'mahasiswa'): ?>
                <li class="nav-item mb-1">
                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-journal-text me-2"></i> KRS
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-file-earmark-check me-2"></i> KHS
                    </a>
                </li>
            <?php endif; ?>

            <!-- Menu Khusus Dosen -->
            <?php if ($role == 'dosen'): ?>
                <li class="nav-item mb-1">
                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-pencil-square me-2"></i> Input Nilai
                    </a>
                </li>
            <?php endif; ?>

            <!-- Menu Khusus Admin -->
            <?php if ($role == 'admin'): ?>
                <li class="nav-item mb-1">
                    <a href="#" class="nav-link text-white">
                        <i class="bi bi-database-gear me-2"></i> Kelola Data
                    </a>
                </li>
            <?php endif; ?>
        </ul>
        <hr>
        <!-- Tombol Logout -->
        <div>
            <a href="logout.php" class="btn btn-danger w-100 text-start">
                <i class="bi bi-box-arrow-left me-2"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content bg-light">
        <!-- Navbar Atas (Opsional untuk estetika) -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3">
            <div class="container-fluid">
                <span class="navbar-text ms-auto fw-bold">
                    Halo, <?= htmlspecialchars($username); ?>!
                </span>
            </div>
        </nav>

        <!-- Konten Utama Dashboard -->
        <div class="container-fluid p-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="card-title">Selamat Datang di Sistem Informasi Akademik</h2>
                    <p class="card-text fs-5">
                        Anda saat ini login menggunakan hak akses sebagai: 
                        <span class="badge bg-primary fs-6"><?= strtoupper($role); ?></span>
                    </p>
                    <hr>
                    <p class="text-muted">
                        Silakan gunakan menu di sebelah kiri untuk menavigasi sistem sesuai dengan wewenang Anda.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Bootstrap 5 JS Bundle via CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>