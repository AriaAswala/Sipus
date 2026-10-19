<?php
/**
 * File: peminjaman/riwayat.php
 * Lokasi: sipus/peminjaman/riwayat.php
 * Deskripsi: Halaman riwayat peminjaman buku mandiri untuk peran Anggota.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Anggota, Admin, Pustakawan
cek_akses(['anggota', 'admin', 'pustakawan']);

// Ambil ID Anggota milik pengguna yang sedang login
$user_id = $_SESSION['user_id'];
$member_id = 0;

if (isset($_SESSION['member_id'])) {
    $member_id = $_SESSION['member_id'];
} else {
    $sql_m = "SELECT id FROM members WHERE user_id = ? LIMIT 1";
    $stmt_m = mysqli_prepare($koneksi, $sql_m);
    mysqli_stmt_bind_param($stmt_m, "i", $user_id);
    mysqli_stmt_execute($stmt_m);
    $res_m = mysqli_stmt_get_result($stmt_m);
    if ($m = mysqli_fetch_assoc($res_m)) {
        $member_id = $m['id'];
    }
    mysqli_stmt_close($stmt_m);
}

// Ambil riwayat peminjaman milik anggota tersebut
$sql = "SELECT l.*, b.title as book_title, b.isbn, b.author, c.copy_code,
               f.fine_amount, f.status as fine_status,
               DATEDIFF(CURDATE(), l.due_date) as late_days
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN book_copies c ON l.copy_id = c.id
        LEFT JOIN fines f ON f.loan_id = l.id
        WHERE l.member_id = ?
        ORDER BY l.id DESC";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $member_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman Saya - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar Navigasi -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-info shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark" href="../dashboard_anggota.php">📚 SIPUS RPL</a>
            <div class="d-flex align-items-center">
                <span class="text-dark me-3">Halo, <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong> (Anggota)</span>
                <a href="../logout.php" class="btn btn-outline-dark btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold text-dark mb-0">Riwayat Peminjaman Saya</h3>
                <p class="text-muted small">Daftar seluruh buku yang pernah dan sedang Anda pinjam.</p>
            </div>
            <a href="../dashboard_anggota.php" class="btn btn-secondary btn-sm">&larr; Kembali ke Dashboard</a>
        </div>

        <!-- Alert Notifikasi Peminjaman Berhasil -->
        <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses_pinjam'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                🎉 <strong>Peminjaman Berhasil!</strong> Buku telah terdaftar di akun Anda. Silakan datang ke loket perpustakaan dan tunjukkan nama Anda ke Pustakawan untuk mengambil fisik buku.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Judul Buku</th>
                                <th>Pengarang</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Tgl Kembali</th>
                                <th class="text-center">Status Buku</th>
                                <th class="text-center">Info Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result && mysqli_num_rows($result) > 0): 
                                while ($row = mysqli_fetch_assoc($result)):
                                    $is_late = ($row['status'] === 'dipinjam' && $row['late_days'] > 0);
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['book_title']); ?></strong><br>
                                        <small class="text-muted">ISBN: <?= htmlspecialchars($row['isbn']); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['author']); ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['loan_date'])); ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($row['due_date'])); ?>
                                        <?php if ($is_late): ?>
                                            <br><span class="badge bg-danger">Terlambat <?= $row['late_days']; ?> Hari</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $row['return_date'] ? date('d/m/Y', strtotime($row['return_date'])) : '<span class="text-muted">Masih Dipinjam</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'dipinjam'): ?>
                                            <span class="badge bg-warning text-dark">SEDANG DIPINJAM</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">SUDAH DIKEMBALIKAN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['fine_amount'] > 0): ?>
                                            <?php if ($row['fine_status'] === 'lunas'): ?>
                                                <span class="badge bg-success">Denda Lunas (Rp<?= number_format($row['fine_amount'], 0, ',', '.'); ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Belum Lunas: Rp<?= number_format($row['fine_amount'], 0, ',', '.'); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Tidak Ada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Anda belum memiliki riwayat peminjaman buku.</td>
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
