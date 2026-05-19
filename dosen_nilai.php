<?php
session_start();
require_once 'koneksi.php';

// Proteksi: Hanya Dosen yang boleh mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
    header("Location: dashboard.php");
    exit;
}

$selected_mk = isset($_GET['kode_mk']) ? $_GET['kode_mk'] : '';
$daftar_mahasiswa = null;

// Mengambil daftar semua mata kuliah untuk dropdown
$query_mk = mysqli_query($koneksi, "SELECT * FROM matakuliah");

// Jika Dosen menekan tombol Cari
if ($selected_mk != '') {
    // Join tabel krs dan mahasiswa untuk mendapatkan daftar peserta
    $query_peserta = "SELECT m.nim, m.nama 
                      FROM krs k 
                      JOIN mahasiswa m ON k.nim = m.nim 
                      WHERE k.kode_mk = '$selected_mk'";
    $daftar_mahasiswa = mysqli_query($koneksi, $query_peserta);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Nilai - Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Input Nilai Mahasiswa</h2>
        <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
    </div>

    <!-- Filter Mata Kuliah -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Pilih Mata Kuliah</label>
                    <select name="kode_mk" class="form-select" required>
                        <option value="">-- Pilih Mata Kuliah --</option>
                        <?php while ($mk = mysqli_fetch_assoc($query_mk)): ?>
                            <option value="<?= $mk['kode_mk']; ?>" <?= ($selected_mk == $mk['kode_mk']) ? 'selected' : ''; ?>>
                                <?= $mk['kode_mk'] . ' - ' . $mk['nama_mk']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Mahasiswa -->
    <?php if ($selected_mk != ''): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Daftar Mahasiswa Peserta</h5>
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>NIM</th>
                        <th>Nama</th>
                        <th>Nilai Angka (0-100)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($daftar_mahasiswa && mysqli_num_rows($daftar_mahasiswa) > 0): ?>
                        <?php while ($mhs = mysqli_fetch_assoc($daftar_mahasiswa)): ?>
                        <tr>
                            <td><?= $mhs['nim']; ?></td>
                            <td><?= $mhs['nama']; ?></td>
                            <td>
                                <input type="number" class="form-control form-control-sm" style="width: 100px;" placeholder="0">
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">Tidak ada mahasiswa yang mengambil MK ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <button class="btn btn-success">Simpan Semua Nilai</button>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>