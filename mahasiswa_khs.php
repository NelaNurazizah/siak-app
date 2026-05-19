<?php
session_start();
require_once 'koneksi.php';

// Proteksi: Hanya mahasiswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: dashboard.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$query_mhs = mysqli_query($koneksi, "SELECT nim, nama FROM mahasiswa WHERE id_user = $id_user");
$data_mhs = mysqli_fetch_assoc($query_mhs);
$nim = $data_mhs['nim'];

// Query untuk mengambil data KHS (Join Matakuliah, KRS, dan Nilai)
$query_khs = "SELECT mk.nama_mk, mk.sks, n.nilai_angka, n.nilai_huruf 
              FROM krs k 
              JOIN matakuliah mk ON k.kode_mk = mk.kode_mk 
              LEFT JOIN nilai n ON k.nim = n.nim AND k.kode_mk = n.kode_mk 
              WHERE k.nim = '$nim'";
$result_khs = mysqli_query($koneksi, $query_khs);

// Fungsi konversi huruf ke bobot
function getBobot($huruf) {
    switch ($huruf) {
        case 'A': return 4;
        case 'B': return 3;
        case 'C': return 2;
        case 'D': return 1;
        default: return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Hasil Studi (KHS)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2>Kartu Hasil Studi (KHS)</h2>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <p><strong>Nama:</strong> <?= $data_mhs['nama']; ?> | <strong>NIM:</strong> <?= $nim; ?></p>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr><th>Mata Kuliah</th><th>SKS</th><th>Nilai</th><th>Bobot x SKS</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $total_bobot_sks = 0;
                    $total_sks = 0;
                    while ($row = mysqli_fetch_assoc($result_khs)): 
                        $bobot = getBobot($row['nilai_huruf']);
                        $total_sks += $row['sks'];
                        $total_bobot_sks += ($bobot * $row['sks']);
                    ?>
                    <tr>
                        <td><?= $row['nama_mk']; ?></td>
                        <td><?= $row['sks']; ?></td>
                        <td><?= $row['nilai_huruf'] ?? '-'; ?> (<?= $row['nilai_angka'] ?? '0'; ?>)</td>
                        <td><?= ($row['nilai_huruf']) ? ($bobot * $row['sks']) : '-'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary fw-bold">
                        <td colspan="1">Total</td>
                        <td><?= $total_sks; ?></td>
                        <td>IP:</td>
                        <td><?= ($total_sks > 0) ? number_format($total_bobot_sks / $total_sks, 2) : '0.00'; ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
</div>
</body>
</html>