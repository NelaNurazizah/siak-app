<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dosen') {
    header("Location: dashboard.php");
    exit;
}

$selected_mk = isset($_GET['kode_mk']) ? $_GET['kode_mk'] : '';
$pesan = '';

// 1. PROSES SIMPAN NILAI
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_nilai'])) {
    foreach ($_POST['nilai_angka'] as $nim => $angka) {
        $angka = (float)$angka;
        
        // Logika Konversi Nilai
        if ($angka >= 80) $huruf = 'A';
        elseif ($angka >= 70) $huruf = 'B';
        elseif ($angka >= 60) $huruf = 'C';
        elseif ($angka >= 50) $huruf = 'D';
        else $huruf = 'E';

        // Cek apakah data nilai sudah ada
        $cek = mysqli_query($koneksi, "SELECT id FROM nilai WHERE nim = '$nim' AND kode_mk = '$selected_mk'");
        
        if (mysqli_num_rows($cek) > 0) {
            // Update jika sudah ada
            mysqli_query($koneksi, "UPDATE nilai SET nilai_angka = $angka, nilai_huruf = '$huruf' WHERE nim = '$nim' AND kode_mk = '$selected_mk'");
        } else {
            // Insert jika belum ada
            mysqli_query($koneksi, "INSERT INTO nilai (nim, kode_mk, nilai_angka, nilai_huruf) VALUES ('$nim', '$selected_mk', $angka, '$huruf')");
        }
    }
    $pesan = "Nilai berhasil disimpan dan dikonversi!";
}

// 2. MENGAMBIL DAFTAR PESERTA & NILAI (JIKA SUDAH ADA)
$query_mk = mysqli_query($koneksi, "SELECT * FROM matakuliah");
$daftar_mahasiswa = null;

if ($selected_mk != '') {
    $query_peserta = "SELECT m.nim, m.nama, n.nilai_angka 
                      FROM krs k 
                      JOIN mahasiswa m ON k.nim = m.nim 
                      LEFT JOIN nilai n ON k.nim = n.nim AND k.kode_mk = n.kode_mk
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
    <h2>Input Nilai Mahasiswa</h2>
    <?php if ($pesan): ?><div class="alert alert-success"><?= $pesan; ?></div><?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-10">
                    <select name="kode_mk" class="form-select" required>
                        <option value="">-- Pilih Mata Kuliah --</option>
                        <?php while ($mk = mysqli_fetch_assoc($query_mk)): ?>
                            <option value="<?= $mk['kode_mk']; ?>" <?= ($selected_mk == $mk['kode_mk']) ? 'selected' : ''; ?>>
                                <?= $mk['kode_mk'] . ' - ' . $mk['nama_mk']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Cari</button></div>
            </form>
        </div>
    </div>

    <?php if ($selected_mk != ''): ?>
    <form action="" method="POST">
        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr><th>NIM</th><th>Nama</th><th>Nilai (0-100)</th></tr>
                    </thead>
                    <tbody>
                        <?php while ($mhs = mysqli_fetch_assoc($daftar_mahasiswa)): ?>
                        <tr>
                            <td><?= $mhs['nim']; ?></td>
                            <td><?= $mhs['nama']; ?></td>
                            <td>
                                <input type="number" 
                                       name="nilai_angka[<?= $mhs['nim']; ?>]" 
                                       class="form-control" 
                                       value="<?= $mhs['nilai_angka'] ?? ''; ?>" 
                                       min="0" max="100" step="0.01" required 
                                       oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;">
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button type="submit" name="simpan_nilai" class="btn btn-success">Simpan Nilai</button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>
</body>
</html>