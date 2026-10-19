<?php
/**
 * File: peminjaman/tambah.php
 * Lokasi: sipus/peminjaman/tambah.php
 * Deskripsi: Form transaksi peminjaman buku baru dengan 4 tahap validasi aturan bisnis.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$error = '';

// Ambil daftar anggota aktif untuk dropdown
$sql_members = "SELECT m.id, m.member_code, u.full_name, m.status 
                FROM members m 
                JOIN users u ON m.user_id = u.id 
                ORDER BY u.full_name ASC";
$res_members = mysqli_query($koneksi, $sql_members);

// Ambil daftar buku yang memiliki stok tersedia
$sql_books = "SELECT id, title, isbn, available_stock 
              FROM books 
              WHERE available_stock > 0 
              ORDER BY title ASC";
$res_books = mysqli_query($koneksi, $sql_books);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)$_POST['member_id'];
    $book_id   = (int)$_POST['book_id'];

    if ($member_id <= 0 || $book_id <= 0) {
        $error = 'Silakan pilih Anggota dan Buku yang akan dipinjam!';
    } else {

        // ========================================================
        // VALIDASI 1: Cek Status Anggota (Harus Aktif)
        // ========================================================
        $sql_v1 = "SELECT status FROM members WHERE id = ? LIMIT 1";
        $stmt_v1 = mysqli_prepare($koneksi, $sql_v1);
        mysqli_stmt_bind_param($stmt_v1, "i", $member_id);
        mysqli_stmt_execute($stmt_v1);
        $res_v1 = mysqli_stmt_get_result($stmt_v1);
        $m_data = mysqli_fetch_assoc($res_v1);
        mysqli_stmt_close($stmt_v1);

        if (!$m_data || $m_data['status'] !== 'aktif') {
            $error = '❌ VALIDASI GAGAL: Anggota berstatus NONAKTIF dan tidak diizinkan meminjam buku!';
        } else {

            // ========================================================
            // VALIDASI 2: Cek Denda Tertunggak (Belum Lunas)
            // ========================================================
            $sql_v2 = "SELECT COUNT(*) as total_unpaid 
                       FROM fines f 
                       JOIN loans l ON f.loan_id = l.id 
                       WHERE l.member_id = ? AND f.status = 'belum_dibayar'";
            $stmt_v2 = mysqli_prepare($koneksi, $sql_v2);
            mysqli_stmt_bind_param($stmt_v2, "i", $member_id);
            mysqli_stmt_execute($stmt_v2);
            $res_v2 = mysqli_stmt_get_result($stmt_v2);
            $v2_data = mysqli_fetch_assoc($res_v2);
            mysqli_stmt_close($stmt_v2);

            if ($v2_data['total_unpaid'] > 0) {
                $error = '❌ VALIDASI GAGAL: Anggota memiliki denda tertunggak yang belum dilunasi! Harap lunasi denda terlebih dahulu.';
            } else {

                // ========================================================
                // VALIDASI 3: Maksimal 3 Pinjaman Aktif
                // ========================================================
                $sql_v3 = "SELECT COUNT(*) as active_loans FROM loans WHERE member_id = ? AND status = 'dipinjam'";
                $stmt_v3 = mysqli_prepare($koneksi, $sql_v3);
                mysqli_stmt_bind_param($stmt_v3, "i", $member_id);
                mysqli_stmt_execute($stmt_v3);
                $res_v3 = mysqli_stmt_get_result($stmt_v3);
                $v3_data = mysqli_fetch_assoc($res_v3);
                mysqli_stmt_close($stmt_v3);

                if ($v3_data['active_loans'] >= 3) {
                    $error = '❌ VALIDASI GAGAL: Anggota sudah mencapai batas maksimal 3 buku yang sedang dipinjam!';
                } else {

                    // ========================================================
                    // VALIDASI 4: Stok Buku Tersedia & Eksemplar Siap Pinjam
                    // ========================================================
                    $sql_v4 = "SELECT id FROM book_copies WHERE book_id = ? AND status = 'tersedia' LIMIT 1";
                    $stmt_v4 = mysqli_prepare($koneksi, $sql_v4);
                    mysqli_stmt_bind_param($stmt_v4, "i", $book_id);
                    mysqli_stmt_execute($stmt_v4);
                    $res_v4 = mysqli_stmt_get_result($stmt_v4);
                    $copy_data = mysqli_fetch_assoc($res_v4);
                    mysqli_stmt_close($stmt_v4);

                    if (!$copy_data) {
                        $error = '❌ VALIDASI GAGAL: Eksemplar fisik buku ini sedang habis!';
                    } else {
                        // ========================================================
                        // EKSEKUSI PEMINJAMAN BUKU (BERHASIL)
                        // ========================================================
                        $copy_id   = $copy_data['id'];
                        $loan_date = date('Y-m-d');
                        // Ambil tanggal jatuh tempo dari input form atau default 7 hari
                        $due_date  = !empty($_POST['due_date']) ? trim($_POST['due_date']) : date('Y-m-d', strtotime('+7 days'));
                        
                        // Pastikan jatuh tempo tidak boleh kurang dari tanggal pinjam
                        if ($due_date < $loan_date) {
                            $due_date = $loan_date;
                        }
                        
                        $status    = 'dipinjam';

                        // 1. Insert ke tabel `loans`
                        $sql_ins = "INSERT INTO loans (member_id, book_id, copy_id, loan_date, due_date, status) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_ins = mysqli_prepare($koneksi, $sql_ins);
                        mysqli_stmt_bind_param($stmt_ins, "iiisss", $member_id, $book_id, $copy_id, $loan_date, $due_date, $status);
                        mysqli_stmt_execute($stmt_ins);
                        mysqli_stmt_close($stmt_ins);

                        // 2. Update status eksemplar di `book_copies` -> 'dipinjam'
                        $sql_u_copy = "UPDATE book_copies SET status = 'dipinjam' WHERE id = ?";
                        $stmt_u_copy = mysqli_prepare($koneksi, $sql_u_copy);
                        mysqli_stmt_bind_param($stmt_u_copy, "i", $copy_id);
                        mysqli_stmt_execute($stmt_u_copy);
                        mysqli_stmt_close($stmt_u_copy);

                        // 3. Kurangi stok tersedia di `books`
                        $sql_u_book = "UPDATE books SET available_stock = available_stock - 1 WHERE id = ?";
                        $stmt_u_book = mysqli_prepare($koneksi, $sql_u_book);
                        mysqli_stmt_bind_param($stmt_u_book, "i", $book_id);
                        mysqli_stmt_execute($stmt_u_book);
                        mysqli_stmt_close($stmt_u_book);

                        header("Location: index.php?status=sukses_pinjam");
                        exit();
                    }
                }
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
    <title>Transaksi Peminjaman - SIPUS</title>
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
                        <h5 class="mb-0 fw-bold">📖 Transaksi Peminjaman Buku Baru</h5>
                        <a href="index.php" class="btn btn-light btn-sm fw-semibold">&larr; Kembali</a>
                    </div>
                    <div class="card-body p-4">

                        <!-- Pesan Error Validasi Bisnis -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Peringatan Validasi!</strong><br><?= htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-info small" role="alert">
                            ℹ️ <strong>Ketentuan Peminjaman:</strong>
                            <ul class="mb-0 ps-3">
                                <li>Anggota wajib berstatus <strong>AKTIF</strong>.</li>
                                <li>Maksimal <strong>3 buku</strong> dipinjam secara bersamaan.</li>
                                <li>Bebas dari denda tertunggak.</li>
                                <li>Lama peminjaman standar adalah <strong>7 Hari</strong>.</li>
                            </ul>
                        </div>

                        <form action="tambah.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Pilih Anggota Peminjam</label>
                                    <select name="member_id" class="form-select" required>
                                        <option value="">-- Pilih Anggota --</option>
                                        <?php 
                                        if ($res_members && mysqli_num_rows($res_members) > 0) {
                                            while ($m = mysqli_fetch_assoc($res_members)) {
                                                $status_text = ($m['status'] === 'aktif') ? '' : ' [NONAKTIF]';
                                                echo "<option value='{$m['id']}'>{$m['full_name']} (Kode: {$m['member_code']}){$status_text}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Pilih Buku yang Dipinjam</label>
                                    <select name="book_id" class="form-select" required>
                                        <option value="">-- Pilih Judul Buku --</option>
                                        <?php 
                                        if ($res_books && mysqli_num_rows($res_books) > 0) {
                                            while ($b = mysqli_fetch_assoc($res_books)) {
                                                echo "<option value='{$b['id']}'>{$b['title']} (ISBN: {$b['isbn']}) - Tersedia: {$b['available_stock']} Stok</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Tanggal Peminjaman</label>
                                    <input type="text" class="form-control bg-light" value="<?= date('d/m/Y'); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Tanggal Jatuh Tempo (Kustom / Bebas Atur)</label>
                                    <input type="date" name="due_date" class="form-control fw-bold border-primary" value="<?= date('Y-m-d', strtotime('+7 days')); ?>" min="<?= date('Y-m-d'); ?>" required>
                                    <span class="form-text text-muted small">Default: 7 hari kedepan. Dapat disesuaikan sesuai kebutuhan.</span>
                                </div>
                                <div class="col-md-12 mt-4 d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">Proses Peminjaman</button>
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
