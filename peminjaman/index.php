<?php
/**
 * File: peminjaman/index.php
 * Lokasi: sipus/peminjaman/index.php
 * Deskripsi: Menampilkan daftar transaksi peminjaman aktif & opsi pengembalian buku.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

// Ambil daftar peminjaman aktif (status = 'dipinjam')
$sql = "SELECT l.*, b.title as book_title, c.copy_code, u.full_name as member_name, m.member_code,
               DATEDIFF(CURDATE(), l.due_date) as late_days
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN book_copies c ON l.copy_id = c.id
        JOIN members m ON l.member_id = m.id
        JOIN users u ON m.user_id = u.id
        ORDER BY l.id DESC";
$result = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sirkulasi Peminjaman - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar Navigasi -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard_admin.php">📚 SIPUS RPL</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $_SESSION['role'] === 'admin' ? '../dashboard_admin.php' : '../dashboard_pustakawan.php'; ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../buku/index.php">Data Buku</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../anggota/index.php">Data Anggota</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="index.php">Sirkulasi Peminjaman</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3 small">Halo, <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong> (<?= strtoupper($_SESSION['role']); ?>)</span>
                    <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-2">
        
        <!-- Header & Tombol Tambah Peminjaman -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold text-dark mb-0">Sirkulasi & Transaksi Buku</h3>
                <p class="text-muted small">Kelola proses peminjaman baru, pengembalian buku, dan denda keterlambatan.</p>
            </div>
            <a href="tambah.php" class="btn btn-primary fw-semibold">
                📖 Transaksi Pinjam Baru
            </a>
        </div>

        <!-- Pesan Alert Notifikasi -->
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'sukses_pinjam'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    ✅ Transaksi peminjaman buku berhasil disimpan! Jatuh tempo 7 hari dari sekarang.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] == 'sukses_kembali'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    🔄 Buku berhasil dikembalikan! Stok eksemplar telah diperbarui.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] == 'sukses_bayar'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    💵 Pembayaran denda berhasil dilunasi!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Tabel Daftar Peminjaman -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Anggota</th>
                                <th>Judul Buku & Kode</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Tgl Kembali</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 170px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result && mysqli_num_rows($result) > 0): 
                                while ($row = mysqli_fetch_assoc($result)):
                                    $is_late = ($row['status'] === 'dipinjam' && $row['late_days'] > 0);
                                    $est_fine = $is_late ? ($row['late_days'] * 1000) : 0;
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['member_name']); ?></strong><br>
                                        <small class="text-muted">Kode: <?= htmlspecialchars($row['member_code']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['book_title']); ?></strong><br>
                                        <small class="badge bg-secondary">Eksemplar: <?= htmlspecialchars($row['copy_code']); ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($row['loan_date'])); ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($row['due_date'])); ?>
                                        <?php if ($is_late): ?>
                                            <br><span class="badge bg-danger">Terlambat <?= $row['late_days']; ?> Hari (Est. Denda: Rp<?= number_format($est_fine, 0, ',', '.'); ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $row['return_date'] ? date('d/m/Y', strtotime($row['return_date'])) : '<span class="text-muted">-</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'dipinjam'): ?>
                                            <span class="badge bg-warning text-dark">DIPINJAM</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">DIKEMBALIKAN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'dipinjam'): ?>
                                            <a href="kembali.php?id=<?= $row['id']; ?>" class="btn btn-success btn-sm fw-bold" onclick="return confirm('Proses pengembalian buku ini?');">
                                                ↩️ Kembalikan
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada transaksi peminjaman.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
