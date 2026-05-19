<?php
session_start();
require_once 'koneksi.php';

// Proteksi: Hanya mahasiswa yang boleh mengakses halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: dashboard.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 1. Mengambil data mahasiswa yang sedang login (Nama dan NIM)
$query_mhs = mysqli_query($koneksi, "SELECT nim, nama FROM mahasiswa WHERE id_user = $id_user");
$data_mhs = mysqli_fetch_assoc($query_mhs);

// Jika profil mahasiswa belum ada di tabel mahasiswa (misal karena error relasi data)
if (!$data_mhs) {
    die("<div style='padding:20px; font-family:sans-serif;'>Data profil mahasiswa Anda belum lengkap. Silakan hubungi Admin. <a href='dashboard.php'>Kembali</a></div>");
}

$nim = $data_mhs['nim'];
$nama = $data_mhs['nama'];

$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// LOGIKA MENYIMPAN KRS
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_krs'])) {
    // Mengecek apakah ada checkbox mata kuliah yang dicentang
    if (!empty($_POST['mk_pilih'])) {
        $mk_pilih = $_POST['mk_pilih']; // Berupa array dari value checkbox
        $berhasil = 0;
        
        foreach ($mk_pilih as $kode_mk) {
            $kode_mk = mysqli_real_escape_string($koneksi, $kode_mk);
            
            // Validasi tambahan: cek apakah mata kuliah sudah ada di KRS (mencegah bypass inspect element)
            $cek_krs = mysqli_query($koneksi, "SELECT * FROM krs WHERE nim = '$nim' AND kode_mk = '$kode_mk'");
            if (mysqli_num_rows($cek_krs) == 0) {
                // Jika belum ada, masukkan ke database
                $query_insert = "INSERT INTO krs (nim, kode_mk) VALUES ('$nim', '$kode_mk')";
                if (mysqli_query($koneksi, $query_insert)) {
                    $berhasil++;
                }
            }
        }
        
        if ($berhasil > 0) {
            $pesan_sukses = "$berhasil mata kuliah berhasil ditambahkan ke KRS Anda!";
        } else {
            $pesan_error = "Mata kuliah yang dipilih sudah ada di KRS Anda.";
        }
    } else {
        $pesan_error = "Anda belum memilih mata kuliah satupun!";
    }
}

// ==========================================
// MENGAMBIL DATA MATA KULIAH & KRS LAMA
// ==========================================
// Mengambil semua daftar mata kuliah yang tersedia
$result_mk = mysqli_query($koneksi, "SELECT * FROM matakuliah ORDER BY semester ASC, nama_mk ASC");

// Mengambil daftar kode_mk yang sudah ada di KRS mahasiswa ini
$krs_diambil = [];
$query_krs_lama = mysqli_query($koneksi, "SELECT kode_mk FROM krs WHERE nim = '$nim'");
while ($row = mysqli_fetch_assoc($query_krs_lama)) {
    $krs_diambil[] = $row['kode_mk'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengisian KRS - Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Pengisian Kartu Rencana Studi (KRS)</h2>
        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <!-- Alert Notifikasi -->
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

    <!-- Informasi Mahasiswa -->
    <div class="card shadow-sm mb-4 border-0 border-start border-primary border-5">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 fw-bold text-muted">NIM</div>
                <div class="col-md-10">: <?= htmlspecialchars($nim); ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-2 fw-bold text-muted">Nama Mahasiswa</div>
                <div class="col-md-10">: <?= htmlspecialchars($nama); ?></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-2 fw-bold text-muted">Program Studi</div>
                <div class="col-md-10">: Teknik Informatika</div>
            </div>
        </div>
    </div>

    <!-- Form Pemilihan Mata Kuliah -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Daftar Mata Kuliah Tersedia</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th>Pilih</th>
                                <th>Kode MK</th>
                                <th>Nama Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Semester</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($result_mk) > 0):
                                while ($row = mysqli_fetch_assoc($result_mk)): 
                                    // Cek apakah mata kuliah ini sudah ada di array KRS yang sudah diambil
                                    $sudah_diambil = in_array($row['kode_mk'], $krs_diambil);
                            ?>
                            <tr>
                                <td class="text-center">
                                    <input class="form-check-input fs-5" type="checkbox" name="mk_pilih[]" value="<?= $row['kode_mk']; ?>" 
                                        <?= $sudah_diambil ? 'checked disabled' : ''; ?>>
                                </td>
                                <td class="text-center fw-bold"><?= htmlspecialchars($row['kode_mk']); ?></td>
                                <td><?= htmlspecialchars($row['nama_mk']); ?></td>
                                <td class="text-center"><?= $row['sks']; ?></td>
                                <td class="text-center"><?= $row['semester']; ?></td>
                                <td class="text-center">
                                    <?php if ($sudah_diambil): ?>
                                        <span class="badge bg-success">Telah Diambil</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum Diambil</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Mata kuliah belum tersedia. Hubungi Admin.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" name="simpan_krs" class="btn btn-primary px-4 py-2 fw-bold">
                        Simpan KRS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>