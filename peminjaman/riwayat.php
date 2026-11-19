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

// Pengaturan template
$page_title  = 'Riwayat Peminjaman';
$active_page = 'riwayat';
$base_url    = '../';
$breadcrumb  = '<a href="../dashboard_anggota.php">Dashboard</a> / <strong>Riwayat Peminjaman</strong>';

// Hitung data untuk sidebar anggota
$active_loans = 0;
$total_denda  = 0;
$total_buku_query = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM books WHERE available_stock > 0"));
$total_buku = $total_buku_query['total'];

include '../templates/header.php';
include '../templates/sidebar_anggota.php';
?>

<div class="main-content">
    <?php include '../templates/topnav.php'; ?>
    <div class="content-wrapper">

        <!-- Navigasi Kembali -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <p class="text-muted small mb-0">Daftar seluruh buku yang pernah dan sedang Anda pinjam.</p>
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
    </div><!-- /content-wrapper -->
</div><!-- /main-content -->

<?php include '../templates/footer.php'; ?>
