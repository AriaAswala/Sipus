<?php
/**
 * File: anggota/hapus.php
 * Lokasi: sipus/anggota/hapus.php
 * Deskripsi: Menghapus data anggota dan akun user terkait.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Ambil user_id dari tabel members
    $sql_get = "SELECT user_id FROM members WHERE id = ? LIMIT 1";
    $stmt_get = mysqli_prepare($koneksi, $sql_get);
    mysqli_stmt_bind_param($stmt_get, "i", $id);
    mysqli_stmt_execute($stmt_get);
    $res = mysqli_stmt_get_result($stmt_get);
    
    if ($m = mysqli_fetch_assoc($res)) {
        $user_id = $m['user_id'];
        mysqli_stmt_close($stmt_get);

        // Hapus akun dari tabel `users` (Cascade akan menghapus data di `members`)
        $sql_del = "DELETE FROM users WHERE id = ?";
        $stmt_del = mysqli_prepare($koneksi, $sql_del);
        mysqli_stmt_bind_param($stmt_del, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt_del)) {
            mysqli_stmt_close($stmt_del);
            header("Location: index.php?status=sukses_hapus");
            exit();
        }
        mysqli_stmt_close($stmt_del);
    }
}

header("Location: index.php?status=gagal");
exit();
