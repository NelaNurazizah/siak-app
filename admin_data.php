<?php
session_start();
require_once 'koneksi.php';

// Proteksi: Hanya admin yang boleh mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// LOGIKA CRUD (CREATE & DELETE)
// ==========================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Tambah Mahasiswa
    if (isset($_POST['tambah_mhs'])) {
        $nim = mysqli_real_escape_string($koneksi, $_POST['nim']);
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $username = $nim; // Username disamakan dengan NIM
        $password = 'mhs123'; // Password default
        
        // Memulai Transaksi Database
        mysqli_begin_transaction($koneksi);
        
        try {
            // Insert ke tabel users
            $query_user = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'mahasiswa')";
            mysqli_query($koneksi, $query_user);
            
            // Ambil ID user yang baru saja dibuat
            $id_user = mysqli_insert_id($koneksi);
            
            // Insert ke tabel mahasiswa
            $query_mhs = "INSERT INTO mahasiswa (nim, nama, id_user) VALUES ('$nim', '$nama', $id_user)";
            mysqli_query($koneksi, $query_mhs);
            
            // Commit transaksi jika semua berhasil
            mysqli_commit($koneksi);
            $pesan_sukses = "Data Mahasiswa dan Akun berhasil ditambahkan!";
        } catch (Exception $e) {
            // Rollback jika terjadi kesalahan (misal: NIM sudah ada/Primary Key bentrok)
            mysqli_rollback($koneksi);
            $pesan_error = "Gagal menambahkan mahasiswa. Pastikan NIM belum terdaftar.";
        }
    }
    
    // 2. Hapus Mahasiswa (dan Akunnya)
    if (isset($_POST['hapus_mhs'])) {
        $id_user = $_POST['id_user'];
        // Berkat ON DELETE CASCADE, menghapus dari tabel users akan otomatis menghapus dari tabel mahasiswa
        $query_hapus = "DELETE FROM users WHERE id = $id_user AND role = 'mahasiswa'";
        if (mysqli_query($koneksi, $query_hapus)) {
            $pesan_sukses = "Data Mahasiswa berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus data.";
        }
    }

    // 3. Tambah Dosen
    if (isset($_POST['tambah_dosen'])) {
        $username = mysqli_real_escape_string($koneksi, $_POST['username_dosen']);
        $password = 'dosen123'; // Password default
        
        $query_dosen = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'dosen')";
        if (mysqli_query($koneksi, $query_dosen)) {
            $pesan_sukses = "Akun Dosen berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menambahkan dosen. Username mungkin sudah dipakai.";
        }
    }

    // 4. Hapus Dosen
    if (isset($_POST['hapus_dosen'])) {
        $id_user = $_POST['id_user'];
        $query_hapus = "DELETE FROM users WHERE id = $id_user AND role = 'dosen'";
        if (mysqli_query($koneksi, $query_hapus)) {
            $pesan_sukses = "Akun Dosen berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus akun dosen.";
        }
    }
}

// ==========================================
// MENGAMBIL DATA UNTUK DITAMPILKAN
// ==========================================
// Mengambil data mahasiswa beserta ID usernya dengan teknik JOIN
$result_mhs = mysqli_query($koneksi, "SELECT m.nim, m.nama, u.id as id_user, u.username FROM mahasiswa m JOIN users u ON m.id_user = u.id");

// Mengambil data dosen dari tabel users
$result_dosen = mysqli_query($koneksi, "SELECT id, username FROM users WHERE role = 'dosen'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Data Pengguna</h2>
        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success"><?= $pesan_sukses; ?></div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="alert alert-danger"><?= $pesan_error; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Kolom Data Mahasiswa -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Mahasiswa</h5>
                    <!-- Tombol Triger Modal Tambah Mahasiswa -->
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMhs">
                        + Tambah
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>NIM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Username Akun</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result_mhs)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nim']); ?></td>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td><?= htmlspecialchars($row['username']); ?></td>
                                    <td>
                                        <form action="" method="POST" onsubmit="return confirm('Yakin ingin menghapus mahasiswa ini?');">
                                            <input type="hidden" name="id_user" value="<?= $row['id_user']; ?>">
                                            <button type="submit" name="hapus_mhs" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Data Dosen -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Dosen</h5>
                    <!-- Tombol Triger Modal Tambah Dosen -->
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahDosen">
                        + Tambah
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Username Dosen</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result_dosen)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['username']); ?></td>
                                    <td>
                                        <form action="" method="POST" onsubmit="return confirm('Yakin ingin menghapus dosen ini?');">
                                            <input type="hidden" name="id_user" value="<?= $row['id']; ?>">
                                            <button type="submit" name="hapus_dosen" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL TAMBAH MAHASISWA ================= -->
<div class="modal fade" id="modalTambahMhs" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Mahasiswa Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">NIM (Digunakan sbg Username)</label>
                    <input type="text" name="nim" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="alert alert-info">
                    <small>Password default yang akan digenerate otomatis adalah: <strong>mhs123</strong></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_mhs" class="btn btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL TAMBAH DOSEN ================= -->
<div class="modal fade" id="modalTambahDosen" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Dosen Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Username Dosen</label>
                    <input type="text" name="username_dosen" class="form-control" required>
                </div>
                <div class="alert alert-info">
                    <small>Password default yang akan digenerate otomatis adalah: <strong>dosen123</strong></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_dosen" class="btn btn-success">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>