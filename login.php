<?php
/**
 * File: login.php
 * Lokasi: sipus/login.php
 * Deskripsi: Halaman antarmuka form login untuk masuk ke Sistem Informasi Perpustakaan.
 */

session_start();

// Jika pengguna sudah login, langsung alihkan ke dashboard sesuai peran masing-masing
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard_admin.php");
    } elseif ($_SESSION['role'] === 'pustakawan') {
        header("Location: dashboard_pustakawan.php");
    } elseif ($_SESSION['role'] === 'anggota') {
        header("Location: dashboard_anggota.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Perpustakaan</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-login {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            background: #ffffff;
            padding: 30px 30px 10px 30px;
            text-align: center;
        }
        .login-header h3 {
            font-weight: 700;
            color: #1e3c72;
        }
        .login-body {
            background: #ffffff;
            padding: 20px 30px 40px 30px;
        }
        .btn-custom {
            background: #1e3c72;
            color: #ffffff;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background: #2a5298;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="card card-login">
        <div class="login-header">
            <div class="mb-3 fs-1 text-primary">📚</div>
            <h3>SIPUS RPL</h3>
            <p class="text-muted small">Sistem Informasi Perpustakaan</p>
        </div>
        <div class="login-body">

            <!-- Pesan Notifikasi Alert -->
            <?php if (isset($_GET['pesan'])): ?>
                <?php if ($_GET['pesan'] == 'gagal'): ?>
                    <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                        <strong>Login Gagal!</strong> Username atau password salah.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'belum_login'): ?>
                    <div class="alert alert-warning alert-dismissible fade show small mb-3" role="alert">
                        <strong>Peringatan!</strong> Silakan login terlebih dahulu.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'logout'): ?>
                    <div class="alert alert-success alert-dismissible fade show small mb-3" role="alert">
                        <strong>Berhasil!</strong> Anda telah keluar dari sistem.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($_GET['pesan'] == 'akses_ditolak'): ?>
                    <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                        <strong>Akses Ditolak!</strong> Anda tidak berhak mengakses halaman tersebut.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="proses_login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label small fw-bold">Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                </div>
                <button type="submit" class="btn btn-custom w-100 mb-3">Masuk ke Sistem</button>
            </form>

            <div class="text-center mt-3 text-muted small">
                <p class="mb-0">Peran: <strong>Admin | Pustakawan | Anggota</strong></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle via CDN -->
    <script href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
