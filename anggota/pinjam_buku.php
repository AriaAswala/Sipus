<?php
/**
 * File: anggota/pinjam_buku.php
 * Lokasi: sipus/anggota/pinjam_buku.php
 * Deskripsi: Handler peminjaman mandiri buku oleh Anggota dari katalog web.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Anggota
cek_akses(['anggota']);

$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($book_id <= 0) {
    header("Location: ../index.php");
    exit();
}

// 1. Ambil member_id dari user_id session
$sql_m = "SELECT id, status FROM members WHERE user_id = ? LIMIT 1";
$stmt_m = mysqli_prepare($koneksi, $sql_m);
mysqli_stmt_bind_param($stmt_m, "i", $user_id);
mysqli_stmt_execute($stmt_m);
$res_m = mysqli_stmt_get_result($stmt_m);
$member = mysqli_fetch_assoc($res_m);
mysqli_stmt_close($stmt_m);

if (!$member) {
    header("Location: ../index.php?pesan=member_not_found");
    exit();
}

$member_id = $member['id'];

// ========================================================
// VALIDASI 1: Cek Status Anggota (Harus Aktif)
// ========================================================
if ($member['status'] !== 'aktif') {
    header("Location: ../index.php?error=" . urlencode("Status keanggotaan Anda NONAKTIF. Harap hubungi pustakawan."));
    exit();
}

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
    header("Location: ../index.php?error=" . urlencode("Anda memiliki denda tertunggak yang belum dilunasi. Harap lunasi denda terlebih dahulu di perpustakaan."));
    exit();
}

// ========================================================
// VALIDASI 3: Cek Maksimal 3 Pinjaman Aktif
// ========================================================
$sql_v3 = "SELECT COUNT(*) as active_loans FROM loans WHERE member_id = ? AND status = 'dipinjam'";
$stmt_v3 = mysqli_prepare($koneksi, $sql_v3);
mysqli_stmt_bind_param($stmt_v3, "i", $member_id);
mysqli_stmt_execute($stmt_v3);
$res_v3 = mysqli_stmt_get_result($stmt_v3);
$v3_data = mysqli_fetch_assoc($res_v3);
mysqli_stmt_close($stmt_v3);

if ($v3_data['active_loans'] >= 3) {
    header("Location: ../index.php?error=" . urlencode("Anda sudah mencapai batas maksimal 3 buku yang sedang dipinjam!"));
    exit();
}

// ========================================================
// VALIDASI 4: Stok Buku & Eksemplar Tersedia
// ========================================================
$sql_v4 = "SELECT id FROM book_copies WHERE book_id = ? AND status = 'tersedia' LIMIT 1";
$stmt_v4 = mysqli_prepare($koneksi, $sql_v4);
mysqli_stmt_bind_param($stmt_v4, "i", $book_id);
mysqli_stmt_execute($stmt_v4);
$res_v4 = mysqli_stmt_get_result($stmt_v4);
$copy_data = mysqli_fetch_assoc($res_v4);
mysqli_stmt_close($stmt_v4);

if (!$copy_data) {
    header("Location: ../index.php?error=" . urlencode("Maaf, stok eksemplar fisik buku ini sedang tidak tersedia."));
    exit();
}

// ========================================================
// EKSEKUSI PEMINJAMAN
// ========================================================
$copy_id   = $copy_data['id'];
$loan_date = date('Y-m-d');
$due_date  = date('Y-m-d', strtotime('+7 days'));
$status    = 'dipinjam';

// 1. Insert ke tabel `loans`
$sql_ins = "INSERT INTO loans (member_id, book_id, copy_id, loan_date, due_date, status) VALUES (?, ?, ?, ?, ?, ?)";
$stmt_ins = mysqli_prepare($koneksi, $sql_ins);
mysqli_stmt_bind_param($stmt_ins, "iiisss", $member_id, $book_id, $copy_id, $loan_date, $due_date, $status);
mysqli_stmt_execute($stmt_ins);
mysqli_stmt_close($stmt_ins);

// 2. Update status eksemplar di `book_copies`
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

// Alihkan ke riwayat peminjaman dengan pesan sukses
header("Location: ../peminjaman/riwayat.php?status=sukses_pinjam");
exit();
