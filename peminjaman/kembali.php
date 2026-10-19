<?php
/**
 * File: peminjaman/kembali.php
 * Lokasi: sipus/peminjaman/kembali.php
 * Deskripsi: Memproses pengembalian buku, menghitung denda keterlambatan (Rp1.000/hari),
 *            serta mengembalikan status stok eksemplar buku.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch loan record
$sql = "SELECT l.*, b.title as book_title, c.copy_code, u.full_name as member_name 
        FROM loans l
        JOIN books b ON l.book_id = b.id
        JOIN book_copies c ON l.copy_id = c.id
        JOIN members m ON l.member_id = m.id
        JOIN users u ON m.user_id = u.id
        WHERE l.id = ? AND l.status = 'dipinjam' LIMIT 1";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$loan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$loan) {
    header("Location: index.php");
    exit();
}

// Calculate fine amount automatically
$return_date = date('Y-m-d');
$due_date    = $loan['due_date'];

$ts_return = strtotime($return_date);
$ts_due    = strtotime($due_date);

$late_days = 0;
$fine_amount = 0;

if ($ts_return > $ts_due) {
    $diff_seconds = $ts_return - $ts_due;
    $late_days    = floor($diff_seconds / (60 * 60 * 24));
    $fine_amount  = $late_days * 1000; // Rp 1.000 per hari keterlambatan
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Update status transaksi peminjaman di `loans`
    $sql_u_loan = "UPDATE loans SET return_date = ?, status = 'dikembalikan' WHERE id = ?";
    $stmt_u_loan = mysqli_prepare($koneksi, $sql_u_loan);
    mysqli_stmt_bind_param($stmt_u_loan, "si", $return_date, $id);
    mysqli_stmt_execute($stmt_u_loan);
    mysqli_stmt_close($stmt_u_loan);

    // 2. Kembalikan status eksemplar di `book_copies` -> 'tersedia'
    $sql_u_copy = "UPDATE book_copies SET status = 'tersedia' WHERE id = ?";
    $stmt_u_copy = mysqli_prepare($koneksi, $sql_u_copy);
    mysqli_stmt_bind_param($stmt_u_copy, "i", $loan['copy_id']);
    mysqli_stmt_execute($stmt_u_copy);
    mysqli_stmt_close($stmt_u_copy);

    // 3. Tambahkan kembali stok tersedia di `books`
    $sql_u_book = "UPDATE books SET available_stock = available_stock + 1 WHERE id = ?";
    $stmt_u_book = mysqli_prepare($koneksi, $sql_u_book);
    mysqli_stmt_bind_param($stmt_u_book, "i", $loan['book_id']);
    mysqli_stmt_execute($stmt_u_book);
    mysqli_stmt_close($stmt_u_book);

    // 4. Jika ada keterlambatan, simpan catatan ke tabel `fines`
    if ($late_days > 0 && $fine_amount > 0) {
        $fine_status = isset($_POST['bayar_langsung']) ? 'lunas' : 'belum_dibayar';
        $paid_at     = ($fine_status === 'lunas') ? date('Y-m-d H:i:s') : NULL;

        $sql_f = "INSERT INTO fines (loan_id, late_days, fine_amount, status, paid_at) VALUES (?, ?, ?, ?, ?)";
        $stmt_f = mysqli_prepare($koneksi, $sql_f);
        mysqli_stmt_bind_param($stmt_f, "iidss", $id, $late_days, $fine_amount, $fine_status, $paid_at);
        mysqli_stmt_execute($stmt_f);
        mysqli_stmt_close($stmt_f);
    }

    header("Location: index.php?status=sukses_kembali");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pengembalian Buku - SIPUS</title>
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
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold">↩️ Konfirmasi Pengembalian Buku</h5>
                        <a href="index.php" class="btn btn-light btn-sm fw-semibold">&larr; Kembali</a>
                    </div>
                    <div class="card-body p-4">

                        <form action="kembali.php?id=<?= $id; ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">Nama Anggota</label>
                                <div class="fw-bold fs-5 text-dark"><?= htmlspecialchars($loan['member_name']); ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted small mb-1">Judul Buku & Eksemplar</label>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($loan['book_title']); ?></div>
                                <small class="text-secondary">Kode Eksemplar: <?= htmlspecialchars($loan['copy_code']); ?></small>
                            </div>

                            <hr>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small text-muted">Tanggal Pinjam</label>
                                    <input type="text" class="form-control bg-light" value="<?= date('d/m/Y', strtotime($loan['loan_date'])); ?>" disabled>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">Tanggal Jatuh Tempo</label>
                                    <input type="text" class="form-control bg-light" value="<?= date('d/m/Y', strtotime($loan['due_date'])); ?>" disabled>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">Tanggal Pengembalian (Hari Ini)</label>
                                    <input type="text" class="form-control bg-light fw-bold" value="<?= date('d/m/Y'); ?>" disabled>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">Keterlambatan</label>
                                    <input type="text" class="form-control <?= $late_days > 0 ? 'bg-danger text-white' : 'bg-light'; ?> fw-bold" value="<?= $late_days; ?> Hari" disabled>
                                </div>
                            </div>

                            <!-- Perhitungan Denda Otomatis -->
                            <?php if ($late_days > 0): ?>
                                <div class="alert alert-warning mt-4 mb-3 border-warning">
                                    <h6 class="fw-bold text-dark mb-1">⚠️ Terdeteksi Keterlambatan Pengembalian!</h6>
                                    <p class="small mb-2">Terlambat <?= $late_days; ?> Hari &times; Rp 1.000 = <strong class="fs-5 text-danger">Rp <?= number_format($fine_amount, 0, ',', '.'); ?></strong></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="bayar_langsung" id="bayar_langsung" value="1" checked>
                                        <label class="form-check-label fw-bold small" for="bayar_langsung">
                                            Tandai Denda Langsung LUNAS Dibayar Sekarang
                                        </label>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success mt-4 mb-3">
                                    ✅ Pengembalian tepat waktu! Tidak dikenakan denda keterlambatan.
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                                <button type="submit" class="btn btn-success px-4 fw-bold">Proses Pengembalian Buku</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
