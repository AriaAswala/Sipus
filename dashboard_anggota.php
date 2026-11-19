<?php
/**
 * File: dashboard_anggota.php
 * Lokasi: sipus/dashboard_anggota.php
 * Deskripsi: Dashboard utama anggota perpustakaan dengan tampilan modern sidebar layout.
 */

require_once 'cek_login.php';
require_once 'config/koneksi.php';

// Proteksi halaman: Peran 'anggota' dan 'admin' yang diperbolehkan masuk
cek_akses(['anggota', 'admin']);

// =============================================
// Query Data Statistik Dashboard Anggota
// =============================================

$member_id = $_SESSION['member_id'] ?? 0;

// 1. Data Profil Anggota
$sql_member = "SELECT m.*, u.full_name 
               FROM members m 
               JOIN users u ON m.user_id = u.id 
               WHERE m.id = ?";
$stmt_m = mysqli_prepare($koneksi, $sql_member);
mysqli_stmt_bind_param($stmt_m, "i", $member_id);
mysqli_stmt_execute($stmt_m);
$member = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_m));
mysqli_stmt_close($stmt_m);

// 2. Jumlah Pinjaman Aktif
$sql_active = "SELECT COUNT(*) as total FROM loans WHERE member_id = ? AND status = 'dipinjam'";
$stmt_a = mysqli_prepare($koneksi, $sql_active);
mysqli_stmt_bind_param($stmt_a, "i", $member_id);
mysqli_stmt_execute($stmt_a);
$active_loans = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_a))['total'];
mysqli_stmt_close($stmt_a);

// 3. Total Seluruh Pinjaman (Riwayat)
$sql_total = "SELECT COUNT(*) as total FROM loans WHERE member_id = ?";
$stmt_t = mysqli_prepare($koneksi, $sql_total);
mysqli_stmt_bind_param($stmt_t, "i", $member_id);
mysqli_stmt_execute($stmt_t);
$total_loans = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_t))['total'];
mysqli_stmt_close($stmt_t);

// 4. Total Denda Belum Lunas
$sql_fines = "SELECT COALESCE(SUM(f.fine_amount), 0) as total_denda, COUNT(f.id) as jumlah_denda
              FROM fines f 
              JOIN loans l ON f.loan_id = l.id 
              WHERE l.member_id = ? AND f.status = 'belum_dibayar'";
$stmt_f = mysqli_prepare($koneksi, $sql_fines);
mysqli_stmt_bind_param($stmt_f, "i", $member_id);
mysqli_stmt_execute($stmt_f);
$fines_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_f));
mysqli_stmt_close($stmt_f);

// 5. Estimasi denda berjalan (buku terlambat yang belum dikembalikan)
$sql_est = "SELECT COALESCE(SUM(DATEDIFF(CURDATE(), due_date) * 2000), 0) as estimasi
            FROM loans 
            WHERE member_id = ? AND status = 'dipinjam' AND due_date < CURDATE()";
$stmt_e = mysqli_prepare($koneksi, $sql_est);
mysqli_stmt_bind_param($stmt_e, "i", $member_id);
mysqli_stmt_execute($stmt_e);
$estimasi_denda = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_e))['estimasi'];
mysqli_stmt_close($stmt_e);

$total_denda = $fines_data['total_denda'] + $estimasi_denda;

// 6. Daftar Pinjaman Aktif (untuk tabel)
$sql_loans = "SELECT l.*, b.title as book_title, c.copy_code, b.author,
                     DATEDIFF(CURDATE(), l.due_date) as late_days
              FROM loans l
              JOIN books b ON l.book_id = b.id
              JOIN book_copies c ON l.copy_id = c.id
              WHERE l.member_id = ? AND l.status = 'dipinjam'
              ORDER BY l.due_date ASC";
$stmt_l = mysqli_prepare($koneksi, $sql_loans);
mysqli_stmt_bind_param($stmt_l, "i", $member_id);
mysqli_stmt_execute($stmt_l);
$result_loans = mysqli_stmt_get_result($stmt_l);

// 7. Jumlah buku tersedia di perpustakaan
$sql_books = "SELECT COUNT(*) as total FROM books WHERE available_stock > 0";
$res_books = mysqli_query($koneksi, $sql_books);
$total_buku = mysqli_fetch_assoc($res_books)['total'];

// Waktu sapaan
$hour = (int)date('H');
if ($hour < 12) $sapaan = 'Selamat Pagi';
elseif ($hour < 15) $sapaan = 'Selamat Siang';
elseif ($hour < 18) $sapaan = 'Selamat Sore';
else $sapaan = 'Selamat Malam';

// Pengaturan template
$page_title  = 'Dashboard Anggota';
$active_page = 'dashboard';
$base_url    = '';
$breadcrumb  = '<a href="dashboard_anggota.php">Beranda</a> / <strong>Dashboard Anggota</strong>';

include 'templates/header.php';
include 'templates/sidebar_anggota.php';
?>

    <!-- ============ MAIN CONTENT ============ -->
    <div class="main-content">
        <?php include 'templates/topnav.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">

            <!-- Welcome Banner -->
            <div class="welcome-banner mb-4">
                <h3>👋 <?= $sapaan; ?>, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?>!</h3>
                <p>Selamat datang di Portal Anggota Perpustakaan. Anda memiliki <strong><?= $active_loans; ?> buku</strong> yang sedang dipinjam dan sisa kuota <strong><?= max(0, 3 - $active_loans); ?> buku</strong> lagi.</p>
            </div>

            <!-- Alert Denda Berjalan -->
            <?php if ($total_denda > 0): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="border-radius: 10px; border-left: 4px solid #E74C3C;">
                <span style="font-size:24px; margin-right:12px;">⚠️</span>
                <div>
                    <strong>Anda memiliki denda tertunggak!</strong><br>
                    <span class="small">Total denda: <strong>Rp <?= number_format($total_denda, 0, ',', '.'); ?></strong>. Silakan segera lunasi agar bisa meminjam buku kembali.</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status Anggota Alert -->
            <?php if (isset($member['status']) && $member['status'] !== 'aktif'): ?>
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert" style="border-radius: 10px; border-left: 4px solid #F39C12;">
                <span style="font-size:24px; margin-right:12px;">🚫</span>
                <div>
                    <strong>Status keanggotaan Anda: NONAKTIF</strong><br>
                    <span class="small">Hubungi petugas perpustakaan untuk mengaktifkan kembali keanggotaan Anda.</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistik Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value text-primary"><?= $active_loans; ?></div>
                                    <div class="stat-label">Sedang Dipinjam</div>
                                </div>
                                <div class="stat-icon" style="background: #EBF5FB; color: #2980B9;">📦</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value text-success"><?= $total_loans; ?></div>
                                    <div class="stat-label">Total Riwayat</div>
                                </div>
                                <div class="stat-icon" style="background: #EAFAF1; color: #27AE60;">📊</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value" style="color: #E74C3C;">
                                        Rp <?= number_format($total_denda, 0, ',', '.'); ?>
                                    </div>
                                    <div class="stat-label">Denda Tertunggak</div>
                                </div>
                                <div class="stat-icon" style="background: #FDEDEC; color: #E74C3C;">💰</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-value" style="color: #8E44AD;"><?= max(0, 3 - $active_loans); ?></div>
                                    <div class="stat-label">Sisa Kuota Pinjam</div>
                                </div>
                                <div class="stat-icon" style="background: #F5EEF8; color: #8E44AD;">🎫</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <a href="index.php" class="quick-action w-100">
                        <div class="qa-icon" style="background: #EBF5FB; color: #2980B9;">🔍</div>
                        <div>
                            <div>Cari & Pinjam Buku</div>
                            <small class="text-muted fw-normal"><?= $total_buku; ?> judul tersedia</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="peminjaman/riwayat.php" class="quick-action w-100">
                        <div class="qa-icon" style="background: #EAFAF1; color: #27AE60;">📋</div>
                        <div>
                            <div>Riwayat Peminjaman</div>
                            <small class="text-muted fw-normal"><?= $total_loans; ?> transaksi</small>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="index.php" class="quick-action w-100">
                        <div class="qa-icon" style="background: #FEF9E7; color: #F39C12;">📚</div>
                        <div>
                            <div>Katalog Perpustakaan</div>
                            <small class="text-muted fw-normal">Jelajahi koleksi buku</small>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Tabel Buku yang Sedang Dipinjam -->
            <div class="card content-card">
                <div class="card-header">
                    <span>📦 Buku yang Sedang Dipinjam</span>
                    <a href="peminjaman/riwayat.php" class="btn btn-sm btn-outline-primary">Lihat Semua →</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width:40px;">No</th>
                                    <th>Judul Buku</th>
                                    <th>Eksemplar</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Estimasi Denda</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if ($result_loans && mysqli_num_rows($result_loans) > 0):
                                    while ($row = mysqli_fetch_assoc($result_loans)):
                                        $is_late = ($row['late_days'] > 0);
                                        $est_fine = $is_late ? ($row['late_days'] * 2000) : 0;
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['book_title']); ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($row['author']); ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['copy_code']); ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($row['loan_date'])); ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($row['due_date'])); ?>
                                        <?php if ($is_late): ?>
                                            <br><small class="text-danger fw-bold">⏰ <?= $row['late_days']; ?> hari terlambat</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($is_late): ?>
                                            <span class="badge-status" style="background: #FDEDEC; color: #E74C3C;">TERLAMBAT</span>
                                        <?php else: ?>
                                            <span class="badge-status" style="background: #EAFAF1; color: #27AE60;">AKTIF</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($est_fine > 0): ?>
                                            <span class="fw-bold text-danger">Rp <?= number_format($est_fine, 0, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div style="font-size: 40px; margin-bottom: 8px;">📭</div>
                                        <div class="text-muted">Tidak ada buku yang sedang dipinjam.</div>
                                        <a href="index.php" class="btn btn-primary btn-sm mt-2">Cari & Pinjam Buku →</a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer & Scripts -->
        </div>
    </div>

<?php include 'templates/footer.php'; ?>
