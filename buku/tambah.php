<?php
/**
 * File: buku/tambah.php
 * Lokasi: sipus/buku/tambah.php
 * Deskripsi: Form dan proses tambah data buku baru beserta pembuatan eksemplar fisik.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dan bersihkan dari whitespace
    $isbn             = trim($_POST['isbn']);
    $title            = trim($_POST['title']);
    $author           = trim($_POST['author']);
    $publisher        = trim($_POST['publisher']);
    $publication_year = (int)$_POST['publication_year'];
    $category         = trim($_POST['category']);
    $total_stock      = (int)$_POST['total_stock'];

    // Validasi sederhana
    if (empty($isbn) || empty($title) || empty($author) || empty($publisher) || empty($publication_year) || empty($category) || $total_stock < 1) {
        $error = 'Semua field wajib diisi dan stok minimal 1!';
    } else {
        // Cek ISBN duplikat via prepared statement
        $sql_check = "SELECT id FROM books WHERE isbn = ? LIMIT 1";
        $stmt_check = mysqli_prepare($koneksi, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $isbn);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $error = 'Nomor ISBN sudah terdaftar! Gunakan ISBN lain.';
            mysqli_stmt_close($stmt_check);
        } else {
            mysqli_stmt_close($stmt_check);

            // 1. Simpan data buku ke tabel `books`
            $sql_insert = "INSERT INTO books (isbn, title, author, publisher, publication_year, category, total_stock, available_stock) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($koneksi, $sql_insert);
            mysqli_stmt_bind_param($stmt, "ssssisii", $isbn, $title, $author, $publisher, $publication_year, $category, $total_stock, $total_stock);

            if (mysqli_stmt_execute($stmt)) {
                $book_id = mysqli_insert_id($koneksi);
                mysqli_stmt_close($stmt);

                // 2. Otomatis buat salinan eksemplar fisik di `book_copies`
                $sql_copy = "INSERT INTO book_copies (book_id, copy_code, status) VALUES (?, ?, 'tersedia')";
                $stmt_copy = mysqli_prepare($koneksi, $sql_copy);

                for ($i = 1; $i <= $total_stock; $i++) {
                    $copy_code = "BK" . str_pad($book_id, 3, "0", STR_PAD_LEFT) . "-" . str_pad($i, 2, "0", STR_PAD_LEFT);
                    mysqli_stmt_bind_param($stmt_copy, "is", $book_id, $copy_code);
                    mysqli_stmt_execute($stmt_copy);
                }
                mysqli_stmt_close($stmt_copy);

                header("Location: index.php?status=sukses_tambah");
                exit();
            } else {
                $error = 'Gagal menyimpan data buku!';
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku Baru - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard_admin.php">📚 SIPUS RPL</a>
        </div>
    </nav>

    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold">➕ Tambah Data Buku Baru</h5>
                        <a href="index.php" class="btn btn-light btn-sm fw-semibold">&larr; Kembali</a>
                    </div>
                    <div class="card-body p-4">

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Peringatan!</strong> <?= htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="tambah.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Nomor ISBN</label>
                                    <input type="text" name="isbn" class="form-control" placeholder="Contoh: 978-602-0006" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Kategori Buku</label>
                                    <select name="category" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <option value="Pemrograman">Pemrograman</option>
                                        <option value="Rekayasa Perangkat Lunak">Rekayasa Perangkat Lunak</option>
                                        <option value="Database">Database</option>
                                        <option value="Sistem Informasi">Sistem Informasi</option>
                                        <option value="Web Design">Web Design</option>
                                        <option value="Jaringan & Keamanan">Jaringan & Keamanan</option>
                                        <option value="Umum">Umum</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Judul Buku Lengkap</label>
                                    <input type="text" name="title" class="form-control" placeholder="Masukkan judul buku" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Pengarang / Penulis</label>
                                    <input type="text" name="author" class="form-control" placeholder="Nama penulis" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Penerbit</label>
                                    <input type="text" name="publisher" class="form-control" placeholder="Nama penerbit" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Tahun Terbit</label>
                                    <input type="number" name="publication_year" class="form-control" placeholder="Contoh: 2024" min="1900" max="2099" value="2024" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Stok Total Buku (Eksemplar)</label>
                                    <input type="number" name="total_stock" class="form-control" placeholder="Jumlah fisik buku" min="1" max="100" value="3" required>
                                </div>
                                <div class="col-md-12 mt-4 d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Buku</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
