<?php
/**
 * File: buku/edit.php
 * Lokasi: sipus/buku/edit.php
 * Deskripsi: Form dan proses ubah (update) data buku.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Ambil data buku saat ini
$sql_get = "SELECT * FROM books WHERE id = ? LIMIT 1";
$stmt_get = mysqli_prepare($koneksi, $sql_get);
mysqli_stmt_bind_param($stmt_get, "i", $id);
mysqli_stmt_execute($stmt_get);
$result = mysqli_stmt_get_result($stmt_get);
$buku = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_get);

if (!$buku) {
    header("Location: index.php?status=gagal");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn             = $buku['isbn'];
    $title            = trim($_POST['title']);
    $author           = trim($_POST['author']);
    $publisher        = trim($_POST['publisher']);
    $publication_year = (int)$_POST['publication_year'];
    $category         = trim($_POST['category']);

    if (empty($isbn) || empty($title) || empty($author) || empty($publisher) || empty($publication_year) || empty($category)) {
        $error = 'Semua field wajib diisi!';
    } else {
        // Cek apakah ISBN diubah dan bentrok dengan buku lain
        $sql_check = "SELECT id FROM books WHERE isbn = ? AND id != ? LIMIT 1";
        $stmt_check = mysqli_prepare($koneksi, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "si", $isbn, $id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = 'Nomor ISBN sudah digunakan oleh buku lain!';
            mysqli_stmt_close($stmt_check);
        } else {
            mysqli_stmt_close($stmt_check);

            // Update data buku
            $sql_update = "UPDATE books SET isbn = ?, title = ?, author = ?, publisher = ?, publication_year = ?, category = ? WHERE id = ?";
            $stmt = mysqli_prepare($koneksi, $sql_update);
            mysqli_stmt_bind_param($stmt, "ssssisi", $isbn, $title, $author, $publisher, $publication_year, $category, $id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: index.php?status=sukses_edit");
                exit();
            } else {
                $error = 'Gagal memperbarui data buku!';
                mysqli_stmt_close($stmt);
            }
        }
    }
}
$page_title  = 'Edit Data Buku';
$active_page = 'buku';
$base_url    = '../';
$_dash       = $_SESSION['role'] === 'admin' ? '../dashboard_admin.php' : '../dashboard_pustakawan.php';
$breadcrumb  = '<a href="' . $_dash . '">Beranda</a> / <a href="index.php">Data Buku</a> / <strong>Edit</strong>';

include '../templates/header.php';
include '../templates/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../templates/topnav.php'; ?>
    <div class="content-wrapper">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card content-card">
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                        <h5 class="mb-0 fw-bold">✏️ Edit Data Buku</h5>
                        <a href="index.php" class="btn btn-dark btn-sm fw-semibold">&larr; Kembali</a>
                    </div>
                    <div class="card-body p-3 p-md-4">

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Peringatan!</strong> <?= htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="edit.php?id=<?= $id; ?>" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Kode Buku (Otomatis)</label>
                                    <input type="text" name="isbn" class="form-control bg-light fw-bold" value="<?= htmlspecialchars($buku['isbn']); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Kategori Buku</label>
                                    <select name="category" class="form-select" required>
                                        <?php 
                                        $categories = ['Pemrograman', 'Rekayasa Perangkat Lunak', 'Database', 'Sistem Informasi', 'Web Design', 'Jaringan & Keamanan', 'Umum'];
                                        foreach ($categories as $cat) {
                                            $selected = ($buku['category'] === $cat) ? 'selected' : '';
                                            echo "<option value='$cat' $selected>$cat</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Judul Buku Lengkap</label>
                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($buku['title']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Pengarang / Penulis</label>
                                    <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($buku['author']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Penerbit</label>
                                    <input type="text" name="publisher" class="form-control" value="<?= htmlspecialchars($buku['publisher']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Tahun Terbit</label>
                                    <input type="number" name="publication_year" class="form-control" value="<?= htmlspecialchars($buku['publication_year']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Info Stok (Fisik)</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($buku['available_stock']); ?> Tersedia / <?= htmlspecialchars($buku['total_stock']); ?> Total" disabled>
                                    <span class="form-text text-muted small">Stok diubah otomatis melalui transaksi atau pencatatan eksemplar.</span>
                                </div>
                                <div class="col-md-12 mt-4 d-flex justify-content-end flex-wrap gap-2">
                                    <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                                    <button type="submit" class="btn btn-warning px-4 fw-bold">Update Buku</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content-wrapper -->
</div><!-- /main-content -->

<?php include '../templates/footer.php'; ?>
