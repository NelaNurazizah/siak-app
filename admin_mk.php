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
    
    // 1. Tambah Mata Kuliah
    if (isset($_POST['tambah_mk'])) {
        $kode_mk = mysqli_real_escape_string($koneksi, $_POST['kode_mk']);
        $nama_mk = mysqli_real_escape_string($koneksi, $_POST['nama_mk']);
        $sks = (int)$_POST['sks'];
        $semester = (int)$_POST['semester'];
        
        // Cek apakah kode_mk sudah ada untuk mencegah duplikasi (Primary Key)
        $cek_kode = mysqli_query($koneksi, "SELECT kode_mk FROM matakuliah WHERE kode_mk = '$kode_mk'");
        
        if (mysqli_num_rows($cek_kode) > 0) {
            $pesan_error = "Gagal! Kode Mata Kuliah '$kode_mk' sudah terdaftar.";
        } else {
            $query_tambah = "INSERT INTO matakuliah (kode_mk, nama_mk, sks, semester) 
                             VALUES ('$kode_mk', '$nama_mk', $sks, $semester)";
            
            if (mysqli_query($koneksi, $query_tambah)) {
                $pesan_sukses = "Mata kuliah berhasil ditambahkan!";
            } else {
                $pesan_error = "Terjadi kesalahan saat menyimpan data: " . mysqli_error($koneksi);
            }
        }
    }
    
    // 2. Hapus Mata Kuliah
    if (isset($_POST['hapus_mk'])) {
        $kode_mk = mysqli_real_escape_string($koneksi, $_POST['kode_mk']);
        
        // Menghapus data, berkat ON DELETE CASCADE di database.sql, data KRS dan Nilai 
        // yang terkait dengan mata kuliah ini juga akan ikut terhapus otomatis.
        $query_hapus = "DELETE FROM matakuliah WHERE kode_mk = '$kode_mk'";
        if (mysqli_query($koneksi, $query_hapus)) {
            $pesan_sukses = "Mata kuliah berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus mata kuliah.";
        }
    }
}

// ==========================================
// MENGAMBIL DATA UNTUK DITAMPILKAN
// ==========================================
// Menampilkan mata kuliah diurutkan berdasarkan semester terkecil lalu nama mata kuliah
$result_mk = mysqli_query($koneksi, "SELECT * FROM matakuliah ORDER BY semester ASC, nama_mk ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Kuliah - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Kelola Data Mata Kuliah</h2>
        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <?php if ($pesan_sukses): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $pesan_sukses; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($pesan_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $pesan_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Card Tabel Mata Kuliah -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Mata Kuliah</h5>
            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMK">
                + Tambah Mata Kuliah
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>No</th>
                            <th>Kode MK</th>
                            <th>Nama Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Semester</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (mysqli_num_rows($result_mk) > 0):
                            while ($row = mysqli_fetch_assoc($result_mk)): 
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td class="text-center fw-bold"><?= htmlspecialchars($row['kode_mk']); ?></td>
                            <td><?= htmlspecialchars($row['nama_mk']); ?></td>
                            <td class="text-center"><?= $row['sks']; ?></td>
                            <td class="text-center"><?= $row['semester']; ?></td>
                            <td class="text-center">
                                <form action="" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus mata kuliah <?= $row['nama_mk']; ?>? Semua data KRS dan nilai yang terkait juga akan hilang.');">
                                    <input type="hidden" name="kode_mk" value="<?= $row['kode_mk']; ?>">
                                    <button type="submit" name="hapus_mk" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada data mata kuliah.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL TAMBAH MATA KULIAH ================= -->
<div class="modal fade" id="modalTambahMK" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Mata Kuliah Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Mata Kuliah</label>
                    <input type="text" name="kode_mk" class="form-control" placeholder="Contoh: TIF601" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Mata Kuliah</label>
                    <input type="text" name="nama_mk" class="form-control" placeholder="Contoh: Machine Learning" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jumlah SKS</label>
                        <select name="sks" class="form-select" required>
                            <option value="">Pilih SKS</option>
                            <option value="1">1 SKS</option>
                            <option value="2">2 SKS</option>
                            <option value="3">3 SKS</option>
                            <option value="4">4 SKS</option>
                            <option value="6">6 SKS</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Semester</label>
                        <input type="number" name="semester" class="form-control" min="1" max="8" value="6" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_mk" class="btn btn-primary">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>