<?php
/**
 * File: index.php
 * Lokasi: sipus/index.php
 * Deskripsi: Halaman Utama (Public Landing Page & Katalog Buku) dengan Menu Login.
 */

session_start();
require_once 'config/koneksi.php';

// Menangani Pencarian Katalog Buku untuk Pengunjung Umum
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!empty($keyword)) {
    $search_term = "%" . $keyword . "%";
    $sql = "SELECT * FROM books 
            WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? OR category LIKE ? 
            ORDER BY id DESC";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $search_term, $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "SELECT * FROM books ORDER BY id DESC";
    $result = mysqli_query($koneksi, $sql);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Perpustakaan (SIPUS)</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 60px 0 50px 0;
            border-radius: 0 0 25px 25px;
        }
        .search-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Header Navbar dengan Menu Login -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="index.php">📚 SIPUS RPL</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPublic">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarPublic">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" href="index.php">Katalog Buku</a>
                    </li>
                </ul>

                <!-- Menu Login / Status Akun -->
                <div class="d-flex align-items-center gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Jika Sudah Login -->
                        <span class="text-white me-2 small">Halo, <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                        <?php 
                        $dashboard_url = 'dashboard_admin.php';
                        if ($_SESSION['role'] === 'pustakawan') $dashboard_url = 'dashboard_pustakawan.php';
                        if ($_SESSION['role'] === 'anggota') $dashboard_url = 'dashboard_anggota.php';
                        ?>
                        <a href="<?= $dashboard_url; ?>" class="btn btn-primary btn-sm fw-bold">Dashboard Saya &rarr;</a>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                    <?php else: ?>
                        <!-- Jika Belum Login (Tampilkan Menu Login) -->
                        <a href="login.php" class="btn btn-outline-light btn-sm px-3 fw-semibold">
                            🔑 Masuk / Login
                        </a>
                        <a href="login.php" class="btn btn-primary btn-sm px-3 fw-bold">
                            Portal Login &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section Banner -->
    <div class="hero-section text-center mb-5">
        <div class="container">
            <h1 class="fw-bold mb-2">Selamat Datang di Perpustakaan Digital</h1>
            <p class="lead opacity-75 mb-4">Cari koleksi buku, cek ketersediaan stok fisik, dan lakukan peminjaman mudah.</p>
            
            <!-- Form Pencarian Buku Publik -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="search-box">
                        <form action="index.php" method="GET" class="row g-2">
                            <div class="col-md-9">
                                <input type="text" name="q" class="form-control form-control-lg" placeholder="Ketik judul buku, pengarang, ISBN, atau kategori..." value="<?= htmlspecialchars($keyword); ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold">🔍 Cari Buku</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        
        <!-- Alert Notifikasi Error Peminjaman -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                ⚠️ <strong>Peminjaman Gagal!</strong> <?= htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-dark mb-0">📚 Koleksi Katalog Buku</h4>
            <?php if (!empty($keyword)): ?>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset Pencarian</a>
            <?php endif; ?>
        </div>

        <!-- Tabel Katalog Buku untuk Umum & Anggota -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>ISBN</th>
                                <th>Judul Buku</th>
                                <th>Pengarang</th>
                                <th>Kategori</th>
                                <th class="text-center">Stok Tersedia</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 160px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result && mysqli_num_rows($result) > 0): 
                                while ($row = mysqli_fetch_assoc($result)):
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td><code><?= htmlspecialchars($row['isbn']); ?></code></td>
                                    <td>
                                        <strong class="text-primary fs-6"><?= htmlspecialchars($row['title']); ?></strong><br>
                                        <small class="text-muted">Penerbit: <?= htmlspecialchars($row['publisher']); ?> (<?= htmlspecialchars($row['publication_year']); ?>)</small>
                                    </td>
                                    <td><?= htmlspecialchars($row['author']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['category']); ?></span></td>
                                    <td class="text-center fw-bold fs-6">
                                        <?= $row['available_stock']; ?> / <?= $row['total_stock']; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['available_stock'] > 0): ?>
                                            <span class="badge bg-success px-3 py-2">TERSEDIA</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-3 py-2">DIPINJAM SEMUA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <?php if ($_SESSION['role'] === 'anggota'): ?>
                                                <?php if ($row['available_stock'] > 0): ?>
                                                    <a href="anggota/pinjam_buku.php?book_id=<?= $row['id']; ?>" class="btn btn-success btn-sm fw-bold" onclick="return confirm('Apakah Anda yakin ingin meminjam buku ini?');">
                                                        📖 Pinjam Buku
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>Stok Habis</button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="peminjaman/tambah.php" class="btn btn-primary btn-sm fw-semibold">Proses Pinjam</a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-outline-primary btn-sm fw-semibold">🔑 Login Pinjam</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Data buku tidak ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-auto">
        <div class="container">
            <p class="mb-0 small opacity-75">&copy; <?= date('Y'); ?> Sistem Informasi Perpustakaan (SIPUS) - Tugas Kuliah Rekayasa Perangkat Lunak.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
