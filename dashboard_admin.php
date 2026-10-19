<?php
/**
 * File: dashboard_admin.php
 * Lokasi: sipus/dashboard_admin.php
 * Deskripsi: Halaman utama dashboard untuk peran Admin.
 */

require_once 'cek_login.php';
// Proteksi halaman: Hanya peran 'admin' yang diperbolehkan masuk
cek_akses(['admin']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">📚 SIPUS RPL - Admin</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Halo, <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong> (Admin)</span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="card-title fw-bold text-primary">Selamat Datang di Dashboard Admin</h2>
                        <p class="card-text text-muted">
                            Anda memiliki akses penuh untuk mengelola pengguna (Pustakawan & Anggota), serta melihat seluruh laporan transaksi perpustakaan.
                        </p>
                        <div class="alert alert-info" role="alert">
                            ℹ️ <strong>Status Login:</strong> Anda berhasil masuk sebagai <strong><?= htmlspecialchars($_SESSION['username']); ?></strong> dengan Peran: <span class="badge bg-primary"><?= uppercase($_SESSION['role']); ?></span>.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-white bg-primary">
                    <div class="card-body p-4">
                        <h5>Manajemen Pengguna</h5>
                        <p class="small opacity-75">Kelola akun Admin, Pustakawan, dan Anggota.</p>
                        <a href="anggota/index.php" class="btn btn-light btn-sm fw-bold">Buka Fitur &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-white bg-success">
                    <div class="card-body p-4">
                        <h5>Katalog Buku</h5>
                        <p class="small opacity-75">Lihat data koleksi buku & eksemplar.</p>
                        <a href="buku/index.php" class="btn btn-light btn-sm fw-bold">Buka Fitur &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-white bg-warning text-dark">
                    <div class="card-body p-4">
                        <h5>Sirkulasi Peminjaman</h5>
                        <p class="small opacity-75">Transaksi peminjaman dan denda.</p>
                        <a href="peminjaman/index.php" class="btn btn-warning btn-sm fw-bold text-dark">Sirkulasi &rarr;</a>
                        <a href="laporan/index.php" class="btn btn-dark btn-sm fw-bold text-white ms-1">Laporan &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    function uppercase($str) {
        return strtoupper($str);
    }
    ?>
</body>
</html>
