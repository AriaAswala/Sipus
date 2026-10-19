<?php
/**
 * File: peminjaman/bayar_denda.php
 * Lokasi: sipus/peminjaman/bayar_denda.php
 * Deskripsi: Handler melunasi pembayaran denda tertunggak.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE fines SET status = 'lunas', paid_at = ? WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $now, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            $redirect = isset($_GET['from']) && $_GET['from'] === 'laporan' ? '../laporan/index.php' : 'index.php';
            header("Location: {$redirect}?status=sukses_bayar");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

header("Location: index.php");
exit();
