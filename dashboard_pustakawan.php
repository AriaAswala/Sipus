<?php
/**
 * File: dashboard_pustakawan.php
 * Lokasi: sipus/dashboard_pustakawan.php
 * Deskripsi: Halaman utama dashboard untuk peran Pustakawan dengan tampilan sidebar modern.
 */

require_once 'cek_login.php';
require_once 'config/koneksi.php';
// Proteksi halaman: Peran 'pustakawan' dan 'admin' yang diperbolehkan masuk
cek_akses(['pustakawan', 'admin']);

// =============================================
// Query Statistik Dashboard Pustakawan
// =============================================

// Total buku tersedia
$total_buku = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM books"))['total'];

// Total anggota aktif
$total_anggota = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM members WHERE status='aktif'"))['total'];

// Pinjaman aktif
$pinjaman_aktif = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM loans WHERE status='dipinjam'"))['total'];

// Buku terlambat
$buku_terlambat = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM loans WHERE status='dipinjam' AND due_date < CURDATE()"))['total'];

// Pengembalian hari ini
$kembali_hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM loans WHERE status='dikembalikan' AND return_date = CURDATE()"))['total'];

// 5 pinjaman aktif terbaru (prioritas: yang hampir/sudah terlambat)
$sql_recent = "SELECT l.*, b.title as book_title, u.full_name as member_name, m.member_code,
                      DATEDIFF(CURDATE(), l.due_date) as late_days
               FROM loans l
               JOIN books b ON l.book_id = b.id
               JOIN members m ON l.member_id = m.id
               JOIN users u ON m.user_id = u.id
               WHERE l.status = 'dipinjam'
               ORDER BY l.due_date ASC LIMIT 5";
$result_recent = mysqli_query($koneksi, $sql_recent);

// Waktu sapaan
$hour = (int)date('H');
if ($hour < 12) $sapaan = 'Selamat Pagi';
elseif ($hour < 15) $sapaan = 'Selamat Siang';
elseif ($hour < 18) $sapaan = 'Selamat Sore';
else $sapaan = 'Selamat Malam';

// Pengaturan template
$page_title  = 'Dashboard Pustakawan';
$active_page = 'dashboard';
$base_url    = '';
$breadcrumb  = '<a href="dashboard_pustakawan.php">Beranda</a> / <strong>Dashboard</strong>';

include 'templates/header.php';
include 'templates/sidebar_staff.php';
?>

<div class="main-content">
    <?php include 'templates/topnav.php'; ?>
    <div class="content-wrapper">

        <!-- Welcome Banner -->
        <div class="welcome-banner mb-4">
            <h3>👋 <?= $sapaan; ?>, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?>!</h3>
            <p>Selamat datang di panel Pustakawan SIPUS. Kelola transaksi peminjaman, pengembalian, dan data koleksi buku perpustakaan.</p>
        </div>

        <!-- Alert Buku Terlambat -->
        <?php if ($buku_terlambat > 0): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert" style="border-radius:10px; border-left:4px solid #F39C12;">
            <span style="font-size:24px; margin-right:12px;">⏰</span>
            <div>
                <strong><?= $buku_terlambat; ?> buku melewati jatuh tempo!</strong><br>
                <span class="small">Hubungi anggota terkait dan segera proses pengembalian.</span>
            </div>
            <a href="peminjaman/index.php" class="btn btn-sm btn-warning ms-auto fw-bold">Lihat →</a>
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
                                <div class="stat-value text-info"><?= $kembali_hari_ini; ?></div>
                                <div class="stat-label">Dikembalikan Hari Ini</div>
                            </div>
                            <div class="stat-icon" style="background:#EBF5FB; color:#17A2B8;">↩️</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-6">
                <a href="peminjaman/tambah.php" class="quick-action w-100">
                    <div class="qa-icon" style="background:#EBF5FB; color:#2980B9;">📖</div>
                    <div><div>Proses Pinjam Baru</div><small class="text-muted fw-normal">Transaksi peminjaman</small></div>
                </a>
            </div>
            <div class="col-md-4 col-6">
                <a href="peminjaman/index.php" class="quick-action w-100">
                    <div class="qa-icon" style="background:#EAFAF1; color:#27AE60;">↩️</div>
                    <div><div>Proses Pengembalian</div><small class="text-muted fw-normal">Sirkulasi aktif</small></div>
                </a>
            </div>
            <div class="col-md-4 col-6">
                <a href="buku/index.php" class="quick-action w-100">
                    <div class="qa-icon" style="background:#FEF9E7; color:#F39C12;">📚</div>
                    <div><div>Data Buku</div><small class="text-muted fw-normal">Kelola koleksi</small></div>
                </a>
            </div>
        </div>

        <!-- Tabel Pinjaman Aktif (Prioritas Jatuh Tempo) -->
        <div class="card content-card">
            <div class="card-header">
                <span>📋 Pinjaman Aktif (Urut Jatuh Tempo)</span>
                <a href="peminjaman/index.php" class="btn btn-sm btn-outline-primary">Lihat Semua →</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>Anggota</th>
                                <th>Judul Buku</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_recent && mysqli_num_rows($result_recent) > 0):
                                while ($row = mysqli_fetch_assoc($result_recent)):
                                    $is_late = ($row['late_days'] > 0);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['member_name']); ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($row['member_code']); ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['book_title']); ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($row['due_date'])); ?>
                                    <?php if ($is_late): ?>
                                        <br><small class="text-danger fw-bold">⏰ Terlambat <?= $row['late_days']; ?> hari</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-status" style="background:<?= $is_late ? '#FDEDEC' : '#FEF9E7'; ?>; color:<?= $is_late ? '#E74C3C' : '#F39C12'; ?>;">
                                        <?= $is_late ? 'TERLAMBAT' : 'AKTIF'; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="peminjaman/kembali.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-success fw-bold">↩️ Kembalikan</a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">📭 Tidak ada pinjaman aktif saat ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /content-wrapper -->
</div><!-- /main-content -->

<?php include 'templates/footer.php'; ?>
