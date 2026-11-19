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

// Pengaturan template
$page_title  = 'Sirkulasi Peminjaman';
$active_page = 'peminjaman';
$base_url    = '../';
$breadcrumb  = '<a href="../dashboard_admin.php">Beranda</a> / Transaksi / <strong>Sirkulasi Peminjaman</strong>';

include '../templates/header.php';
include '../templates/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../templates/topnav.php'; ?>
    <div class="content-wrapper">

        <!-- Header & Tombol Tambah Peminjaman -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <p class="text-muted small mb-0">Kelola proses peminjaman baru, pengembalian buku, dan denda keterlambatan.</p>
            <a href="tambah.php" class="btn btn-primary fw-semibold">📖 Transaksi Pinjam Baru</a>
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
        <div class="card content-card">
            <div class="card-header">
                <span>🔄 Daftar Transaksi Peminjaman</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:50px;">No</th>
                                <th>Anggota</th>
                                <th>Judul Buku & Kode</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Tgl Kembali</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:170px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result && mysqli_num_rows($result) > 0): 
                                while ($row = mysqli_fetch_assoc($result)):
                                    $is_late = ($row['status'] === 'dipinjam' && $row['late_days'] > 0);
                                    $est_fine = $is_late ? ($row['late_days'] * 2000) : 0;
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
                                            <span class="badge-status" style="background:<?= $is_late ? '#FDEDEC' : '#FEF9E7'; ?>; color:<?= $is_late ? '#E74C3C' : '#F39C12'; ?>;">
                                                <?= $is_late ? 'TERLAMBAT' : 'DIPINJAM'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status" style="background:#EAFAF1; color:#27AE60;">DIKEMBALIKAN</span>
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
                                    <td colspan="8" class="text-center py-4 text-muted">📭 Belum ada transaksi peminjaman.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /content-wrapper -->
</div><!-- /main-content -->

<?php include '../templates/footer.php'; ?>
