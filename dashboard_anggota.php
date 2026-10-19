<?php
/**
 * File: dashboard_anggota.php
 * Lokasi: sipus/dashboard_anggota.php
 * Deskripsi: Halaman utama dashboard untuk peran Anggota Perpustakaan.
 */

require_once 'cek_login.php';
// Proteksi halaman: Peran 'anggota' dan 'admin' yang diperbolehkan masuk
cek_akses(['anggota', 'admin']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Anggota - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-info shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark" href="#">📚 SIPUS RPL - Anggota</a>
            <div class="d-flex align-items-center">
                <span class="text-dark me-3">Halo, <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                <a href="logout.php" class="btn btn-outline-dark btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="card-title fw-bold text-info-emphasis">Selamat Datang di Portal Anggota</h2>
                        <p class="card-text text-muted">
                            Di sini Anda dapat melihat katalog buku yang tersedia dan memantau status peminjaman serta riwayat denda Anda.
                        </p>
                        <div class="alert alert-info" role="alert">
                            ℹ️ <strong>Status Anggota:</strong> <?= isset($_SESSION['member_status']) && $_SESSION['member_status'] === 'aktif' ? '<span class="badge bg-success">AKTIF</span>' : '<span class="badge bg-danger">NONAKTIF</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 text-white bg-primary">
                    <div class="card-body p-4">
                        <h5>Cari Katalog Buku</h5>
                        <p class="small opacity-75">Jelajahi judul dan stok buku yang tersedia di perpustakaan.</p>
                        <a href="index.php" class="btn btn-light btn-sm fw-bold">Lihat Katalog &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100 text-white bg-secondary">
                    <div class="card-body p-4">
                        <h5>Riwayat Peminjaman Saya</h5>
                        <p class="small opacity-75">Lihat daftar buku yang sedang Anda pinjam & jatuh tempo.</p>
                        <a href="peminjaman/riwayat.php" class="btn btn-light btn-sm fw-bold">Lihat Peminjaman &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
