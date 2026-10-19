<?php
/**
 * File: anggota/edit.php
 * Lokasi: sipus/anggota/edit.php
 * Deskripsi: Form dan proses perbaikan/edit data anggota perpustakaan.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Ambil data anggota dan akun user terkait
$sql_get = "SELECT m.*, u.full_name, u.username, u.id as uid 
            FROM members m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.id = ? LIMIT 1";
$stmt_get = mysqli_prepare($koneksi, $sql_get);
mysqli_stmt_bind_param($stmt_get, "i", $id);
mysqli_stmt_execute($stmt_get);
$result = mysqli_stmt_get_result($stmt_get);
$member = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_get);

if (!$member) {
    header("Location: index.php?status=gagal");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);
    $address   = trim($_POST['address']);
    $status    = trim($_POST['status']);
    $password  = trim($_POST['password']);

    if (empty($full_name)) {
        $error = 'Nama Lengkap wajib diisi!';
    } else {
        // 1. Update tabel `users` (Nama & opsional password baru)
        if (!empty($password)) {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $sql_u = "UPDATE users SET full_name = ?, password = ? WHERE id = ?";
            $stmt_u = mysqli_prepare($koneksi, $sql_u);
            mysqli_stmt_bind_param($stmt_u, "ssi", $full_name, $hashed_pass, $member['uid']);
        } else {
            $sql_u = "UPDATE users SET full_name = ? WHERE id = ?";
            $stmt_u = mysqli_prepare($koneksi, $sql_u);
            mysqli_stmt_bind_param($stmt_u, "si", $full_name, $member['uid']);
        }
        mysqli_stmt_execute($stmt_u);
        mysqli_stmt_close($stmt_u);

        // 2. Update tabel `members`
        $sql_m = "UPDATE members SET phone = ?, address = ?, status = ? WHERE id = ?";
        $stmt_m = mysqli_prepare($koneksi, $sql_m);
        mysqli_stmt_bind_param($stmt_m, "sssi", $phone, $address, $status, $id);

        if (mysqli_stmt_execute($stmt_m)) {
            mysqli_stmt_close($stmt_m);
            header("Location: index.php?status=sukses_edit");
            exit();
        } else {
            $error = 'Gagal memperbarui data anggota!';
            mysqli_stmt_close($stmt_m);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Anggota - SIPUS</title>
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
                    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold">✏️ Edit Data Anggota</h5>
                        <a href="index.php" class="btn btn-dark btn-sm fw-semibold">&larr; Kembali</a>
                    </div>
                    <div class="card-body p-4">

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Peringatan!</strong> <?= htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="edit.php?id=<?= $id; ?>" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Kode Anggota</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($member['member_code']); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Username Login</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($member['username']); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Nama Lengkap</label>
                                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($member['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Status Keanggotaan</label>
                                    <select name="status" class="form-select" required>
                                        <option value="aktif" <?= $member['status'] === 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                        <option value="nonaktif" <?= $member['status'] === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">No. Telepon / WhatsApp</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Password Baru (Opsional)</label>
                                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small">Alamat Tempat Tinggal</label>
                                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($member['address']); ?></textarea>
                                </div>
                                <div class="col-md-12 mt-4 d-flex justify-content-end gap-2">
                                    <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                                    <button type="submit" class="btn btn-warning px-4 fw-bold">Update Data</button>
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
