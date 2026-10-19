<?php
/**
 * File: dashboard_pustakawan.php
 * Lokasi: sipus/dashboard_pustakawan.php
 * Deskripsi: Halaman utama dashboard untuk peran Pustakawan.
 */

require_once 'cek_login.php';
// Proteksi halaman: Peran 'pustakawan' dan 'admin' yang diperbolehkan masuk
cek_akses(['pustakawan', 'admin']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pustakawan - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">📚 SIPUS RPL - Pustakawan</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Halo, <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="card-title fw-bold text-success">Selamat Datang di Dashboard Pustakawan</h2>
                        <p class="card-text text-muted">
                            Anda memiliki akses untuk mengelola data buku, data anggota, serta memproses transaksi peminjaman & pengembalian buku.
                        </p>
                        <div class="alert alert-success" role="alert">
                            ℹ️ <strong>Status Login:</strong> Anda masuk sebagai <strong><?= htmlspecialchars($_SESSION['username']); ?></strong> dengan Peran: <span class="badge bg-success">PUSTAKAWAN</span>.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-white bg-primary">
                    <div class="card-body p-4">
                        <h5>Manajemen Buku</h5>
                        <p class="small opacity-75">Tambah, ubah, hapus & stok buku.</p>
                        <a href="buku/index.php" class="btn btn-light btn-sm fw-bold">Kelola Buku &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-white bg-info">
                    <div class="card-body p-4">
                        <h5>Transaksi Peminjaman</h5>
                        <p class="small opacity-75">Proses pinjam buku untuk anggota.</p>
                        <a href="peminjaman/tambah.php" class="btn btn-dark btn-sm fw-bold">Proses Pinjam &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-white bg-warning text-dark">
                    <div class="card-body p-4">
                        <h5>Pengembalian & Denda</h5>
                        <p class="small opacity-75">Hitung denda & pengembalian buku.</p>
                        <a href="peminjaman/index.php" class="btn btn-warning btn-sm fw-bold text-dark">Sirkulasi &rarr;</a>
                        <a href="laporan/index.php" class="btn btn-dark btn-sm fw-bold text-white ms-1">Laporan &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
