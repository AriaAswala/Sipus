<?php
/**
 * File: anggota/status.php
 * Lokasi: sipus/anggota/status.php
 * Deskripsi: Mengubah status keanggotaan (Aktif <-> Nonaktif).
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $sql = "UPDATE members SET status = IF(status = 'aktif', 'nonaktif', 'aktif') WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: index.php?status=sukses_status");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
}

header("Location: index.php?status=gagal");
exit();
