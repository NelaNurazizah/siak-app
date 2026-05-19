<?php
session_start();
require_once 'koneksi.php'; // Memanggil koneksi database

// Jika user sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['role'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Proses form login ketika tombol submit ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengamankan input dari SQL Injection
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Mencari user di database
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // "s" berarti variabel username bertipe string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Pengecekan password 
        // Catatan: Untuk testing ini kita menggunakan plain-text. 
        // Untuk produksi sungguhan, sangat disarankan menggunakan password_verify()
        if ($password === $user['password']) {
            // Jika login sukses, set session
            $_SESSION['id_user']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            
            // Arahkan ke halaman dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Password yang Anda masukkan salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}

/* 
========================================================================
AKUN DEFAULT UNTUK TESTING
========================================================================
Untuk mengetes fungsi login ini, jalankan query SQL berikut di phpMyAdmin 
agar Anda memiliki data user awal:

INSERT INTO users (username, password, role) VALUES
('admin', 'admin123', 'admin'),
('dosen1', 'dosen123', 'dosen'),
('mhs1', 'mhs123', 'mahasiswa');
========================================================================
*/
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIAK</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container login-container">
    <div class="col-md-5 col-lg-4">
        <div class="card p-4">
            <div class="card-body">
                <h3 class="text-center mb-4">Login SIAK</h3>
                
                <!-- Menampilkan Pesan Error jika login gagal -->
                <?php if ($error != ''): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Form Login -->
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="Masukkan username">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Masukkan password">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
                    </div>
                </form>
                
            </div>
        </div>
        <p class="text-center mt-3 text-muted">&copy; 2026 Sistem Informasi Akademik</p>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle via CDN (Opsional untuk interaktivitas komponen) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelector("form").addEventListener("submit", function(e) {
    const user = document.getElementById("username").value.trim();
    const pass = document.getElementById("password").value.trim();
    
    if (user === "" || pass === "") {
        alert("Username dan Password tidak boleh kosong!");
        e.preventDefault(); // Mencegah submit
    }
});
</script>
</body>
</html>