<?php
/**
 * File: buku/index.php
 * Lokasi: sipus/buku/index.php
 * Deskripsi: Menampilkan daftar katalog buku, pencarian, dan opsi aksi CRUD.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

// Menangani Pencarian Buku
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

// Pengaturan template
$page_title  = 'Data Buku';
$active_page = 'buku';
$base_url    = '../';
$breadcrumb  = '<a href="../dashboard_admin.php">Beranda</a> / Master Data / <strong>Data Buku</strong>';

include '../templates/header.php';
include '../templates/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../templates/topnav.php'; ?>
    <div class="content-wrapper">

        <!-- Header & Tombol Tambah -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <p class="text-muted small mb-0">Kelola koleksi buku dan ketersediaan stok di perpustakaan.</p>
            </div>
            <a href="tambah.php" class="btn btn-primary fw-semibold">➕ Tambah Buku Baru</a>
        </div>

        <!-- Pesan Alert Notifikasi Sukses/Gagal -->
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'sukses_tambah'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    ✅ Data buku baru dan eksemplar fisik berhasil ditambahkan!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] == 'sukses_edit'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    ℹ️ Data buku berhasil diperbarui.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] == 'sukses_hapus'): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    🗑️ Data buku berhasil dihapus dari sistem.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif ($_GET['status'] == 'gagal'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ❌ Gagal memproses data buku. Pastikan ISBN tidak duplikat.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Form Pencarian Buku -->
        <div class="card content-card mb-3">
            <div class="card-body p-3">
                <form action="index.php" method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="q" class="form-control" placeholder="Cari berdasarkan judul, pengarang, ISBN, atau kategori..." value="<?= htmlspecialchars($keyword); ?>">
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

        <!-- Tabel Daftar Buku -->
        <div class="card content-card">
            <div class="card-header">
                <span>📖 Daftar Koleksi Buku</span>
                <span class="text-muted small">
                    <?php if (!empty($keyword)): ?>
                        Hasil pencarian: "<?= htmlspecialchars($keyword); ?>"
                    <?php else: ?>
                        Semua koleksi
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:50px;">No</th>
                                <th>ISBN</th>
                                <th>Judul Buku</th>
                                <th>Pengarang</th>
                                <th>Penerbit / Thn</th>
                                <th>Kategori</th>
                                <th class="text-center">Stok (Tersedia/Total)</th>
                                <th class="text-center" style="width:150px;">Aksi</th>
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
                                    <td><strong><?= htmlspecialchars($row['title']); ?></strong></td>
                                    <td><?= htmlspecialchars($row['author']); ?></td>
                                    <td><?= htmlspecialchars($row['publisher']); ?> (<?= htmlspecialchars($row['publication_year']); ?>)</td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['category']); ?></span></td>
                                    <td class="text-center">
                                        <?php if ($row['available_stock'] > 0): ?>
                                            <span class="badge-status" style="background:#EAFAF1; color:#27AE60;"><?= $row['available_stock']; ?> / <?= $row['total_stock']; ?></span>
                                        <?php else: ?>
                                            <span class="badge-status" style="background:#FDEDEC; color:#E74C3C;">Habis (0 / <?= $row['total_stock']; ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm fw-semibold">Edit</a>
                                        <a href="hapus.php?id=<?= $row['id']; ?>" class="btn btn-danger btn-sm fw-semibold" onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini beserta seluruh salinannya?');">Hapus</a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">📭 Data buku tidak ditemukan.</td>
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
