<?php
/**
 * File: proses_login.php
 * Lokasi: sipus/proses_login.php
 * Deskripsi: Memproses verifikasi login menggunakan PHP Session, Prepared Statement (MySQLi),
 *            serta penundaan sementara setelah 5 kali gagal login berturut-turut (REQ-03 / TC-03).
 */

session_start();
require_once 'config/koneksi.php';

// Konfigurasi batas percobaan login
$max_attempts    = 5;
$lockout_seconds = 300; // Ditunda 5 menit (300 detik) setelah 5x gagal berturut-turut

// Pastikan data dikirim via metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Cek apakah akun/sesi sedang dalam masa penundaan login sementara
    if (isset($_SESSION['login_lockout_until']) && time() < $_SESSION['login_lockout_until']) {
        $sisa = $_SESSION['login_lockout_until'] - time();
        header("Location: login.php?pesan=terkunci&sisa=" . $sisa);
        exit();
    }

    // Jika masa penundaan sudah berakhir, bersihkan lockout
    if (isset($_SESSION['login_lockout_until']) && time() >= $_SESSION['login_lockout_until']) {
        unset($_SESSION['login_lockout_until']);
        $_SESSION['login_attempts'] = 0;
    }

    // Ambil data dari form dan bersihkan spasi tambahan
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi field tidak boleh kosong
    if (empty($username) || empty($password)) {
        header("Location: login.php?pesan=gagal");
        exit();
    }

    // 1. Siapkan query SQL dengan Prepared Statement (Aman dari SQL Injection)
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Jika data user ditemukan
        if ($user = mysqli_fetch_assoc($result)) {

            // 2. Verifikasi password dengan hash password_verify()
            if (password_verify($password, $user['password'])) {

                // Login berhasil -> reset penghitung gagal & lockout
                unset($_SESSION['login_attempts'], $_SESSION['login_lockout_until']);

                // 3. Jika peran adalah 'anggota', ambil member_id & status keanggotaannya
                if ($user['role'] === 'anggota') {
                    $sql_member = "SELECT id, status FROM members WHERE user_id = ? LIMIT 1";
                    $stmt_member = mysqli_prepare($koneksi, $sql_member);
                    mysqli_stmt_bind_param($stmt_member, "i", $user['id']);
                    mysqli_stmt_execute($stmt_member);
                    $res_member = mysqli_stmt_get_result($stmt_member);

                    if ($member = mysqli_fetch_assoc($res_member)) {
                        $_SESSION['member_id']     = $member['id'];
                        $_SESSION['member_status'] = $member['status'];
                    }
                    mysqli_stmt_close($stmt_member);
                }

                // 4. Simpan data penting pengguna & waktu aktivitas ke dalam PHP Session
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['full_name']     = $user['full_name'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['last_activity'] = time();

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

        mysqli_stmt_close($stmt);
    }

    // Jika username tidak ditemukan atau password salah -> tambah hitungan gagal
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

    if ($_SESSION['login_attempts'] >= $max_attempts) {
        $_SESSION['login_lockout_until'] = time() + $lockout_seconds;
        $_SESSION['login_attempts'] = 0;
        header("Location: login.php?pesan=terkunci&sisa=" . $lockout_seconds);
        exit();
    }

    $sisa_coba = $max_attempts - $_SESSION['login_attempts'];
    header("Location: login.php?pesan=gagal&sisa_coba=" . $sisa_coba);
    exit();

} else {
    header("Location: login.php");
    exit();
}
