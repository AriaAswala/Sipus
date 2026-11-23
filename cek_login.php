<?php
/**
 * File: cek_login.php
 * Lokasi: sipus/cek_login.php
 * Deskripsi: File middleware pelindung halaman (cek login, auto-logout 30 menit tanpa aktivitas,
 *            dan validasi hak akses sesuai peran/role).
 */

// Jalankan session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tentukan path relatif menuju login.php (mendukung halaman di root maupun subfolder)
$_login_path = file_exists('login.php') ? 'login.php' : '../login.php';

// 1. Cek apakah pengguna sudah login (apabila variabel session user_id tidak ada)
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $_login_path . "?pesan=belum_login");
    exit();
}

// 2. Auto-logout jika tidak ada aktivitas selama 30 menit (1800 detik) - REQ-03
$_session_timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $_session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: " . $_login_path . "?pesan=session_expired");
    exit();
}
// Perbarui waktu aktivitas terakhir
$_SESSION['last_activity'] = time();

/**
 * Fungsi untuk memvalidasi peran pengguna yang diizinkan mengakses halaman tertentu
 * @param array $allowed_roles Daftar peran yang diizinkan (contoh: ['admin', 'pustakawan'])
 */
function cek_akses($allowed_roles = []) {
    global $_login_path;
    // Jika role user tidak ada atau tidak berada dalam daftar role yang diizinkan
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: " . $_login_path . "?pesan=akses_ditolak");
        exit();
    }
}
