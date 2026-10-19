<?php
/**
 * File: anggota/tambah.php
 * Lokasi: sipus/anggota/tambah.php
 * Deskripsi: Form dan proses pendaftaran anggota perpustakaan baru beserta pembuatan akun login.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_code = trim($_POST['member_code']);
    $full_name   = trim($_POST['full_name']);
    $username    = trim($_POST['username']);
    $password    = trim($_POST['password']);
    $phone       = trim($_POST['phone']);
    $address     = trim($_POST['address']);

    if (empty($member_code) || empty($full_name) || empty($username) || empty($password)) {
        $error = 'Kode Anggota, Nama Lengkap, Username, dan Password wajib diisi!';
    } else {
        // 1. Cek duplikasi username
        $sql_u = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt_u = mysqli_prepare($koneksi, $sql_u);
        mysqli_stmt_bind_param($stmt_u, "s", $username);
        mysqli_stmt_execute($stmt_u);
        mysqli_stmt_store_result($stmt_u);

        if (mysqli_stmt_num_rows($stmt_u) > 0) {
            $error = 'Username sudah digunakan oleh akun lain!';
            mysqli_stmt_close($stmt_u);
        } else {
            mysqli_stmt_close($stmt_u);

            // 2. Cek duplikasi kode anggota
            $sql_m = "SELECT id FROM members WHERE member_code = ? LIMIT 1";
            $stmt_m = mysqli_prepare($koneksi, $sql_m);
            mysqli_stmt_bind_param($stmt_m, "s", $member_code);
            mysqli_stmt_execute($stmt_m);
            mysqli_stmt_store_result($stmt_m);

            if (mysqli_stmt_num_rows($stmt_m) > 0) {
                $error = 'Kode Anggota sudah terdaftar!';
                mysqli_stmt_close($stmt_m);
            } else {
                mysqli_stmt_close($stmt_m);

                // 3. Simpan ke tabel `users` (Role = anggota)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'anggota';

                $sql_ins_u = "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)";
                $stmt_ins_u = mysqli_prepare($koneksi, $sql_ins_u);
                mysqli_stmt_bind_param($stmt_ins_u, "ssss", $username, $hashed_password, $full_name, $role);

                if (mysqli_stmt_execute($stmt_ins_u)) {
                    $user_id = mysqli_insert_id($koneksi);
                    mysqli_stmt_close($stmt_ins_u);

                    // 4. Simpan ke tabel `members`
                    $status = 'aktif';
                    $sql_ins_m = "INSERT INTO members (user_id, member_code, phone, address, status) VALUES (?, ?, ?, ?, ?)";
                    $stmt_ins_m = mysqli_prepare($koneksi, $sql_ins_m);
                    mysqli_stmt_bind_param($stmt_ins_m, "issss", $user_id, $member_code, $phone, $address, $status);
                    
                    if (mysqli_stmt_execute($stmt_ins_m)) {
                        mysqli_stmt_close($stmt_ins_m);
                        header("Location: index.php?status=sukses_tambah");
                        exit();
                    } else {
                        $error = 'Gagal menyimpan detail anggota!';
                        mysqli_stmt_close($stmt_ins_m);
                    }
                } else {
                    $error = 'Gagal membuat akun user!';
                    mysqli_stmt_close($stmt_ins_u);
                }
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
    <title>Tambah Anggota Baru - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard_admin.php">📚 SIPUS RPL</a>
        </div>
    </nav>

    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold">👤 Tambah Anggota Baru</h5>
                        <a href="index.php" class="btn btn-light btn-sm fw-semibold">&larr; Kembali</a>
                    </div>
                    <div class="card-body p-4">

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Peringatan!</strong> <?= htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="tambah.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Kode Anggota</label>
                                    <input type="text" name="member_code" class="form-control" placeholder="Contoh: ANG002" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Nama Lengkap</label>
                                    <input type="text" name="full_name" class="form-control" placeholder="Nama lengkap anggota" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Username Login</label>
                                    <input type="text" name="username" class="form-control" placeholder="Username untuk login" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Password Login</label>
                                    <input type="password" name="password" class="form-control" placeholder="Password akun" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">No. Telepon / WhatsApp</label>
                                    <input type="text" name="phone" class="form-control" placeholder="Contoh: 08123456789">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Role Akses</label>
                                    <input type="text" class="form-control bg-light" value="Anggota Perpustakaan" disabled>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Alamat Tempat Tinggal</label>
                                    <textarea name="address" class="form-control" rows="3" placeholder="Alamat lengkap"></textarea>
                                </div>
                                <div class="col-md-12 mt-4 d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">Daftarkan Anggota</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
