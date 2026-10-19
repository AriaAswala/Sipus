<?php
/**
 * File: logout.php
 * Lokasi: sipus/logout.php
 * Deskripsi: Menghapus seluruh session autentikasi pengguna dan mengalihkan ke login.
 */

// Memulai session
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Jika ingin menghapus cookie session di browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session secara keseluruhan
session_destroy();

// Redirect ke halaman login dengan pesan sukses logout
header("Location: login.php?pesan=logout");
exit();
