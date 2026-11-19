<?php
/**
 * File: dashboard_admin.php
 * Lokasi: sipus/dashboard_admin.php
 * Deskripsi: Halaman utama dashboard untuk peran Admin dengan tampilan sidebar modern.
 */

require_once 'cek_login.php';
require_once 'config/koneksi.php';
// Proteksi halaman: Hanya peran 'admin' yang diperbolehkan masuk
cek_akses(['admin']);

// =============================================
// Query Statistik Dashboard Admin
// =============================================

// Total buku
$total_buku = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM books"))['total'];

// Total anggota aktif
$total_anggota = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM members WHERE status='aktif'"))['total'];

// Pinjaman aktif hari ini
$pinjaman_aktif = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM loans WHERE status='dipinjam'"))['total'];

// Buku terlambat
$buku_terlambat = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM loans WHERE status='dipinjam' AND due_date < CURDATE()"))['total'];

// Total denda belum lunas
$total_denda = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(fine_amount),0) as total FROM fines WHERE status='belum_dibayar'"))['total'];

// 5 pinjaman terbaru
$sql_recent = "SELECT l.*, b.title as book_title, u.full_name as member_name, m.member_code,
                      DATEDIFF(CURDATE(), l.due_date) as late_days
               FROM loans l
               JOIN books b ON l.book_id = b.id
               JOIN members m ON l.member_id = m.id
               JOIN users u ON m.user_id = u.id
               ORDER BY l.id DESC LIMIT 5";
$result_recent = mysqli_query($koneksi, $sql_recent);

// Waktu sapaan
$hour = (int)date('H');
if ($hour < 12) $sapaan = 'Selamat Pagi';
elseif ($hour < 15) $sapaan = 'Selamat Siang';
elseif ($hour < 18) $sapaan = 'Selamat Sore';
else $sapaan = 'Selamat Malam';

// Pengaturan template
$page_title  = 'Dashboard Admin';
$active_page = 'dashboard';
$base_url    = '';
$breadcrumb  = '<a href="dashboard_admin.php">Beranda</a> / <strong>Dashboard</strong>';

include 'templates/header.php';
include 'templates/sidebar_staff.php';
?>

<div class="main-content">
    <?php include 'templates/topnav.php'; ?>
    <div class="content-wrapper">

        <!-- Welcome Banner -->
        <div class="welcome-banner mb-4">
            <h3>👋 <?= $sapaan; ?>, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?>!</h3>
            <p>Selamat datang di panel Administrator SIPUS. Anda memiliki akses penuh untuk mengelola seluruh data perpustakaan.</p>
        </div>

        <!-- Alert Buku Terlambat -->
        <?php if ($buku_terlambat > 0): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert" style="border-radius:10px; border-left:4px solid #F39C12;">
            <span style="font-size:24px; margin-right:12px;">⏰</span>
            <div>
                <strong><?= $buku_terlambat; ?> buku melewati jatuh tempo!</strong><br>
                <span class="small">Segera proses pengembalian melalui menu Sirkulasi Peminjaman.</span>
            </div>
            <a href="peminjaman/index.php" class="btn btn-sm btn-warning ms-auto fw-bold">Proses →</a>
        </div>
        <?php endif; ?>

        <!-- Statistik Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value text-primary"><?= $total_buku; ?></div>
                                <div class="stat-label">Total Judul Buku</div>
                            </div>
                            <div class="stat-icon" style="background:#EBF5FB; color:#2980B9;">📖</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value text-success"><?= $total_anggota; ?></div>
                                <div class="stat-label">Anggota Aktif</div>
                            </div>
                            <div class="stat-icon" style="background:#EAFAF1; color:#27AE60;">👥</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" style="color:#F39C12;"><?= $pinjaman_aktif; ?></div>
                                <div class="stat-label">Sedang Dipinjam</div>
                            </div>
                            <div class="stat-icon" style="background:#FEF9E7; color:#F39C12;">📦</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" style="color:#E74C3C; font-size:20px;">Rp <?= number_format($total_denda, 0, ',', '.'); ?></div>
                                <div class="stat-label">Denda Tertunggak</div>
                            </div>
                            <div class="stat-icon" style="background:#FDEDEC; color:#E74C3C;">💰</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <a href="buku/index.php" class="quick-action w-100">
                    <div class="qa-icon" style="background:#EBF5FB; color:#2980B9;">📖</div>
                    <div><div>Data Buku</div><small class="text-muted fw-normal">Kelola koleksi</small></div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="anggota/index.php" class="quick-action w-100">
                    <div class="qa-icon" style="background:#EAFAF1; color:#27AE60;">👥</div>
                    <div><div>Data Anggota</div><small class="text-muted fw-normal">Kelola anggota</small></div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="peminjaman/index.php" class="quick-action w-100">
                    <div class="qa-icon" style="background:#FEF9E7; color:#F39C12;">🔄</div>
                    <div><div>Sirkulasi</div><small class="text-muted fw-normal">Pinjam & Kembali</small></div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="laporan/index.php" class="quick-action w-100">
                    <div class="qa-icon" style="background:#F5EEF8; color:#8E44AD;">📊</div>
                    <div><div>Laporan</div><small class="text-muted fw-normal">Rekap & cetak</small></div>
                </a>
            </div>
        </div>

        <!-- Tabel Transaksi Terbaru -->
        <div class="card content-card">
            <div class="card-header">
                <span>🔄 Transaksi Peminjaman Terbaru</span>
                <a href="peminjaman/index.php" class="btn btn-sm btn-outline-primary">Lihat Semua →</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>Anggota</th>
                                <th>Judul Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_recent && mysqli_num_rows($result_recent) > 0):
                                while ($row = mysqli_fetch_assoc($result_recent)):
                                    $is_late = ($row['status'] === 'dipinjam' && $row['late_days'] > 0);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['member_name']); ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['member_code']); ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['book_title']); ?></td>
                                <td><?= date('d/m/Y', strtotime($row['loan_date'])); ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($row['due_date'])); ?>
                                    <?php if ($is_late): ?>
                                        <br><small class="text-danger">⏰ Terlambat <?= $row['late_days']; ?> hari</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($row['status'] === 'dipinjam'): ?>
                                        <span class="badge-status" style="background:<?= $is_late ? '#FDEDEC' : '#FEF9E7'; ?>; color:<?= $is_late ? '#E74C3C' : '#F39C12'; ?>;">
                                            <?= $is_late ? 'TERLAMBAT' : 'DIPINJAM'; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-status" style="background:#EAFAF1; color:#27AE60;">DIKEMBALIKAN</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi peminjaman.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /content-wrapper -->
</div><!-- /main-content -->

<?php include 'templates/footer.php'; ?>
