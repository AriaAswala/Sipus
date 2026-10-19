<?php
/**
 * File: cek_login.php
 * Lokasi: sipus/cek_login.php
 * Deskripsi: File middleware pelindung halaman agar hanya pengguna yang sudah login
 *            dan memiliki hak akses (role) sesuai yang bisa mengakses halaman.
 */

// Jalankan session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cek apakah pengguna sudah login (apabila variabel session user_id tidak ada)
if (!isset($_SESSION['user_id'])) {
    // Kembalikan pengguna ke halaman login dengan pesan peringatan
    header("Location: login.php?pesan=belum_login");
    exit();
}

/**
 * Fungsi untuk memvalidasi peran pengguna yang diizinkan mengakses halaman tertentu
 * @param array $allowed_roles Daftar peran yang diizinkan (contoh: ['admin', 'pustakawan'])
 */
function cek_akses($allowed_roles = []) {
    // Jika role user tidak ada atau tidak berada dalam daftar role yang diizinkan
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        // Alihkan ke halaman login dengan pesan akses ditolak
        header("Location: login.php?pesan=akses_ditolak");
        exit();
    }
}
