<?php
/**
 * File: anggota/index.php
 * Lokasi: sipus/anggota/index.php
 * Deskripsi: Menampilkan daftar anggota perpustakaan, status aktif/nonaktif, dan fitur pencarian.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

// Pencarian data anggota
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!empty($keyword)) {
    $search_term = "%" . $keyword . "%";
    $sql = "SELECT m.*, u.username, u.full_name 
            FROM members m
            JOIN users u ON m.user_id = u.id
            WHERE m.member_code LIKE ? OR u.full_name LIKE ? OR u.username LIKE ? OR m.phone LIKE ?
            ORDER BY m.id DESC";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ssss", $search_term, $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "SELECT m.*, u.username, u.full_name 
            FROM members m
            JOIN users u ON m.user_id = u.id
            ORDER BY m.id DESC";
    $result = mysqli_query($koneksi, $sql);
}

// Pengaturan template
$page_title  = 'Data Anggota';
$active_page = 'anggota';
$base_url    = '../';
$breadcrumb  = '<a href="../dashboard_admin.php">Beranda</a> / Master Data / <strong>Data Anggota</strong>';

include '../templates/header.php';
include '../templates/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../templates/topnav.php'; ?>
    <div class="content-wrapper">

        <!-- Header & Tombol Tambah -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <p class="text-muted small mb-0">Kelola pendaftaran anggota, kontak, dan status keanggotaan aktif/nonaktif.</p>
            <a href="tambah.php" class="btn btn-primary fw-semibold">👤 Tambah Anggota Baru</a>
        </div>

        <!-- Pesan Alert Notifikasi -->
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'sukses_tambah'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    ✅ Anggota baru dan akun login berhasil dibuat!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] == 'sukses_edit'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    ℹ️ Data anggota berhasil diperbarui.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] == 'sukses_status'): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    🔄 Status keanggotaan berhasil diubah!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] == 'sukses_hapus'): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    🗑️ Anggota berhasil dihapus dari sistem.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Form Pencarian Anggota -->
        <div class="card content-card mb-3">
            <div class="card-body p-3">
                <form action="index.php" method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="q" class="form-control" placeholder="Cari Kode Anggota, Nama, Username, atau Telepon..." value="<?= htmlspecialchars($keyword); ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-dark w-100">🔍 Cari</button>
                        <?php if (!empty($keyword)): ?>
                            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Daftar Anggota -->
        <div class="card content-card">
            <div class="card-header">
                <span>👥 Daftar Anggota Perpustakaan</span>
                <span class="text-muted small">
                    <?php if (!empty($keyword)): ?>
                        Hasil pencarian: "<?= htmlspecialchars($keyword); ?>"
                    <?php else: ?>
                        Semua anggota
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:50px;">No</th>
                                <th>Kode Anggota</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>No. Telepon</th>
                                <th>Alamat</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:200px;">Aksi</th>
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
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['member_code']); ?></span></td>
                                    <td><strong><?= htmlspecialchars($row['full_name']); ?></strong></td>
                                    <td><code><?= htmlspecialchars($row['username']); ?></code></td>
                                    <td><?= htmlspecialchars($row['phone'] ?: '-'); ?></td>
                                    <td><?= htmlspecialchars($row['address'] ?: '-'); ?></td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'aktif'): ?>
                                            <span class="badge-status" style="background:#EAFAF1; color:#27AE60;">AKTIF</span>
                                        <?php else: ?>
                                            <span class="badge-status" style="background:#FDEDEC; color:#E74C3C;">NONAKTIF</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="status.php?id=<?= $row['id']; ?>" class="btn btn-sm <?= $row['status'] === 'aktif' ? 'btn-outline-danger' : 'btn-outline-success'; ?>" onclick="return confirm('Ubah status keanggotaan ini?');">
                                            <?= $row['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                        </a>
                                        <a href="edit.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm fw-semibold">Edit</a>
                                        <a href="hapus.php?id=<?= $row['id']; ?>" class="btn btn-danger btn-sm fw-semibold" onclick="return confirm('Hapus anggota ini beserta riwayat peminjamannya?');">Hapus</a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">📭 Data anggota tidak ditemukan.</td>
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
