<?php
/**
 * File: proses_login.php
 * Lokasi: sipus/proses_login.php
 * Deskripsi: Memproses verifikasi login menggunakan PHP Session & Prepared Statement (MySQLi)
 */

session_start();
require_once 'config/koneksi.php';

// Pastikan data dikirim via metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil data dari form dan bersihkan spasi tambahan
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validasi field tidak boleh kosong
    if (empty($username) || empty($password)) {
        header("Location: login.php?pesan=gagal");
        exit();
    }

    // 1. Siapkan query SQL dengan Prepared Statement (Aman dari SQL Injection)
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        // Bind parameter username ke query (s = string)
        mysqli_stmt_bind_param($stmt, "s", $username);
        
        // Eksekusi statement
        mysqli_stmt_execute($stmt);
        
        // Ambil hasil query
        $result = mysqli_stmt_get_result($stmt);

        // Jika data user ditemukan
        if ($user = mysqli_fetch_assoc($result)) {
            
            // 2. Verifikasi password dengan hash password_verify()
            if (password_verify($password, $user['password'])) {

                // 3. Jika peran adalah 'anggota', periksa apakah status keanggotaannya aktif
                if ($user['role'] === 'anggota') {
                    $sql_member = "SELECT id, status FROM members WHERE user_id = ? LIMIT 1";
                    $stmt_member = mysqli_prepare($koneksi, $sql_member);
                    mysqli_stmt_bind_param($stmt_member, "i", $user['id']);
                    mysqli_stmt_execute($stmt_member);
                    $res_member = mysqli_stmt_get_result($stmt_member);
                    
                    if ($member = mysqli_fetch_assoc($res_member)) {
                        // Simpan member_id ke session
                        $_SESSION['member_id'] = $member['id'];
                        $_SESSION['member_status'] = $member['status'];
                    }
                    mysqli_stmt_close($stmt_member);
                }

                // 4. Simpan data penting pengguna ke dalam PHP Session
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // Close statement
                mysqli_stmt_close($stmt);

                // 5. Alihkan ke halaman dashboard sesuai peran (role)
                if ($user['role'] === 'admin') {
                    header("Location: dashboard_admin.php");
                } elseif ($user['role'] === 'pustakawan') {
                    header("Location: dashboard_pustakawan.php");
                } elseif ($user['role'] === 'anggota') {
                    header("Location: dashboard_anggota.php");
                }
                exit();
            }
        }

        // Tutup statement jika user tidak ditemukan atau password salah
        mysqli_stmt_close($stmt);
    }

    // Jika username tidak ditemukan atau password tidak cocok
    header("Location: login.php?pesan=gagal");
    exit();

} else {
    // Jika mencoba akses langsung tanpa form POST
    header("Location: login.php");
    exit();
}
