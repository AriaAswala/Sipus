<?php
/**
 * File: register.php
 * Lokasi: sipus/register.php
 * Deskripsi: Halaman pendaftaran anggota perpustakaan secara mandiri oleh pengunjung umum (REQ-04 / UC-02).
 */

session_start();
require_once 'config/koneksi.php';

// Jika sudah login, alihkan ke dashboard masing-masing
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard_admin.php");
    } elseif ($_SESSION['role'] === 'pustakawan') {
        header("Location: dashboard_pustakawan.php");
    } else {
        header("Location: dashboard_anggota.php");
    }
    exit();
}

// Fungsi membuat Kode Anggota otomatis (ANG001, ANG002, dst.)
function generate_member_code_register($koneksi) {
    $res = mysqli_query($koneksi, "SELECT MAX(id) as max_id FROM members");
    $row = $res ? mysqli_fetch_assoc($res) : null;
    $next_num = ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
    while (true) {
        $candidate = 'ANG' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
        $stmt_chk = mysqli_prepare($koneksi, "SELECT id FROM members WHERE member_code = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_chk, "s", $candidate);
        mysqli_stmt_execute($stmt_chk);
        mysqli_stmt_store_result($stmt_chk);
        $exists = mysqli_stmt_num_rows($stmt_chk) > 0;
        mysqli_stmt_close($stmt_chk);
        if (!$exists) {
            return $candidate;
        }
        $next_num++;
    }
}

$error   = '';
$success_data = null;

// Pertahankan input jika terjadi error validasi
$full_name = '';
$phone     = '';
$address   = '';
$username  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $username         = trim($_POST['username'] ?? '');
    $password         = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validasi kelengkapan & format data (REQ-04 / TC-05)
    if (empty($full_name) || empty($phone) || empty($address) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Semua kolom wajib diisi lengkap!';
    } elseif (!preg_match('/^[0-9+\-\s]{9,18}$/', $phone)) {
        $error = 'Format nomor telepon tidak valid (gunakan 9–15 digit angka, contoh: 081234567890).';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,30}$/', $username)) {
        $error = 'Username harus 4–30 karakter dan hanya boleh huruf, angka, atau garis bawah (_).';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        // Cek keunikan username
        $sql_u = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt_u = mysqli_prepare($koneksi, $sql_u);
        mysqli_stmt_bind_param($stmt_u, "s", $username);
        mysqli_stmt_execute($stmt_u);
        mysqli_stmt_store_result($stmt_u);

        if (mysqli_stmt_num_rows($stmt_u) > 0) {
            $error = 'Username "' . $username . '" sudah terpakai! Silakan pilih username lain.';
            mysqli_stmt_close($stmt_u);
        } else {
            mysqli_stmt_close($stmt_u);

            // Generate nomor keanggotaan otomatis
            $member_code     = generate_member_code_register($koneksi);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role            = 'anggota';
            $status          = 'aktif';

            // 1. Simpan ke tabel `users`
            $sql_ins_u = "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)";
            $stmt_ins_u = mysqli_prepare($koneksi, $sql_ins_u);
            mysqli_stmt_bind_param($stmt_ins_u, "ssss", $username, $hashed_password, $full_name, $role);

            if (mysqli_stmt_execute($stmt_ins_u)) {
                $user_id = mysqli_insert_id($koneksi);
                mysqli_stmt_close($stmt_ins_u);

                // 2. Simpan ke tabel `members`
                $sql_ins_m = "INSERT INTO members (user_id, member_code, phone, address, status) VALUES (?, ?, ?, ?, ?)";
                $stmt_ins_m = mysqli_prepare($koneksi, $sql_ins_m);
                mysqli_stmt_bind_param($stmt_ins_m, "issss", $user_id, $member_code, $phone, $address, $status);

                if (mysqli_stmt_execute($stmt_ins_m)) {
                    mysqli_stmt_close($stmt_ins_m);
                    $success_data = [
                        'member_code' => $member_code,
                        'full_name'   => $full_name,
                        'username'    => $username,
                    ];
                    // Kosongkan input setelah sukses
                    $full_name = $phone = $address = $username = '';
                } else {
                    mysqli_stmt_close($stmt_ins_m);
                    // Rollback user jika gagal simpan detail member
                    $stmt_rb = mysqli_prepare($koneksi, "DELETE FROM users WHERE id = ?");
                    mysqli_stmt_bind_param($stmt_rb, "i", $user_id);
                    mysqli_stmt_execute($stmt_rb);
                    mysqli_stmt_close($stmt_rb);
                    $error = 'Gagal menyimpan data keanggotaan. Silakan coba lagi.';
                }
            } else {
                mysqli_stmt_close($stmt_ins_u);
                $error = 'Gagal membuat akun. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Anggota Baru - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-register {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 520px;
        }
        .register-header {
            background: #ffffff;
            padding: 28px 30px 10px 30px;
            text-align: center;
        }
        .register-header h3 {
            font-weight: 700;
            color: #1e3c72;
        }
        .register-body {
            background: #ffffff;
            padding: 18px 30px 32px 30px;
        }
        .btn-custom {
            background: #1e3c72;
            color: #ffffff;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background: #2a5298;
            color: #ffffff;
        }
        .member-code-box {
            background: #EBF5FB;
            border: 2px dashed #2980B9;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }
        @media (max-width: 576px) {
            .register-header {
                padding: 22px 20px 8px;
            }
            .register-body {
                padding: 14px 20px 26px;
            }
        }
    </style>
</head>
<body>

    <div class="card card-register">
        <div class="register-header">
            <div class="mb-2 fs-1 text-primary">📝</div>
            <h3>Daftar Anggota SIPUS</h3>
            <p class="text-muted small mb-0">Lengkapi data diri Anda untuk menjadi anggota perpustakaan</p>
        </div>
        <div class="register-body">

            <?php if ($success_data): ?>
                <!-- Tampilan Sukses & Nomor Keanggotaan (TC-04) -->
                <div class="alert alert-success text-center mb-3" role="alert">
                    🎉 <strong>Pendaftaran Berhasil!</strong><br>
                    Akun anggota Anda sudah aktif dan siap digunakan untuk login.
                </div>

                <div class="member-code-box mb-4">
                    <div class="small text-muted mb-1">Nomor Keanggotaan Anda:</div>
                    <div class="fs-2 fw-bold text-primary"><?= htmlspecialchars($success_data['member_code']); ?></div>
                    <div class="small text-dark mt-2">
                        Nama: <strong><?= htmlspecialchars($success_data['full_name']); ?></strong><br>
                        Username: <code><?= htmlspecialchars($success_data['username']); ?></code>
                    </div>
                </div>

                <a href="login.php" class="btn btn-custom w-100 mb-2">🔑 Login Sekarang &rarr;</a>
                <div class="text-center mt-2">
                    <a href="index.php" class="text-decoration-none small">&larr; Kembali ke Katalog Buku</a>
                </div>
            <?php else: ?>

                <!-- Alert Validasi Error (TC-05) -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                        ⚠️ <strong>Pendaftaran Gagal!</strong> <?= htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="full_name" class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Masukkan nama lengkap" value="<?= htmlspecialchars($full_name); ?>" required autofocus>
                        </div>
                        <div class="col-sm-6">
                            <label for="phone" class="form-label small fw-bold">No. Telepon / WhatsApp</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($phone); ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="username" class="form-label small fw-bold">Username Login</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Minimal 4 karakter" value="<?= htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label small fw-bold">Alamat Lengkap</label>
                            <textarea name="address" id="address" class="form-control" rows="2" placeholder="Masukkan alamat lengkap" required><?= htmlspecialchars($address); ?></textarea>
                        </div>
                        <div class="col-sm-6">
                            <label for="password" class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="confirm_password" class="form-label small fw-bold">Konfirmasi Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ulangi password" minlength="6" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-custom w-100 mt-4 mb-3">Daftar Sebagai Anggota</button>
                </form>

                <div class="text-center border-top pt-3 small">
                    Sudah punya akun? <a href="login.php" class="fw-bold text-decoration-none">Masuk / Login</a><br>
                    <a href="index.php" class="text-decoration-none text-muted d-inline-block mt-2">&larr; Kembali ke Katalog Buku</a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
