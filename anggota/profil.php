<?php
/**
 * File: anggota/profil.php
 * Lokasi: sipus/anggota/profil.php
 * Deskripsi: Halaman Ubah Profil mandiri untuk Anggota yang sedang login (REQ-05).
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Hanya Anggota
cek_akses(['anggota']);

$user_id = (int)$_SESSION['user_id'];
$error   = '';
$success = '';

// Ambil data profil anggota saat ini
function get_profil_anggota($koneksi, $user_id) {
    $sql = "SELECT m.id as member_id, m.member_code, m.phone, m.address, m.status,
                   u.username, u.full_name
            FROM members m
            JOIN users u ON m.user_id = u.id
            WHERE u.id = ? LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $data;
}

$profil = get_profil_anggota($koneksi, $user_id);

if (!$profil) {
    header("Location: ../dashboard_anggota.php");
    exit();
}

$member_id = (int)$profil['member_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name        = trim($_POST['full_name'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $password_baru    = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($full_name)) {
        $error = 'Nama Lengkap wajib diisi!';
    } elseif (!empty($phone) && !preg_match('/^[0-9+\-\s]{9,18}$/', $phone)) {
        $error = 'Format nomor telepon tidak valid (gunakan 9–15 digit angka).';
    } elseif (!empty($password_baru) && strlen($password_baru) < 6) {
        $error = 'Password baru minimal 6 karakter!';
    } elseif (!empty($password_baru) && $password_baru !== $confirm_password) {
        $error = 'Konfirmasi password baru tidak cocok!';
    } else {
        // 1. Update tabel `users` (nama lengkap + password jika diisi)
        if (!empty($password_baru)) {
            $hashed = password_hash($password_baru, PASSWORD_DEFAULT);
            $sql_u  = "UPDATE users SET full_name = ?, password = ? WHERE id = ?";
            $stmt_u = mysqli_prepare($koneksi, $sql_u);
            mysqli_stmt_bind_param($stmt_u, "ssi", $full_name, $hashed, $user_id);
        } else {
            $sql_u  = "UPDATE users SET full_name = ? WHERE id = ?";
            $stmt_u = mysqli_prepare($koneksi, $sql_u);
            mysqli_stmt_bind_param($stmt_u, "si", $full_name, $user_id);
        }
        $ok_u = mysqli_stmt_execute($stmt_u);
        mysqli_stmt_close($stmt_u);

        // 2. Update tabel `members` (telepon & alamat)
        $sql_m  = "UPDATE members SET phone = ?, address = ? WHERE id = ?";
        $stmt_m = mysqli_prepare($koneksi, $sql_m);
        mysqli_stmt_bind_param($stmt_m, "ssi", $phone, $address, $member_id);
        $ok_m = mysqli_stmt_execute($stmt_m);
        mysqli_stmt_close($stmt_m);

        if ($ok_u && $ok_m) {
            // Perbarui nama di session agar langsung berubah di sidebar & dashboard
            $_SESSION['full_name'] = $full_name;
            $profil  = get_profil_anggota($koneksi, $user_id);
            $success = 'Profil Anda berhasil diperbarui!';
        } else {
            $error = 'Gagal menyimpan perubahan profil. Silakan coba lagi.';
        }
    }
}

// Data badge untuk sidebar anggota
$stmt_a = mysqli_prepare($koneksi, "SELECT COUNT(*) as total FROM loans WHERE member_id = ? AND status = 'dipinjam'");
mysqli_stmt_bind_param($stmt_a, "i", $member_id);
mysqli_stmt_execute($stmt_a);
$active_loans = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_a))['total'];
mysqli_stmt_close($stmt_a);

$total_buku_q = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM books WHERE available_stock > 0");
$total_buku   = $total_buku_q ? mysqli_fetch_assoc($total_buku_q)['total'] : 0;

$stmt_f = mysqli_prepare($koneksi, "SELECT COALESCE(SUM(f.fine_amount), 0) as total FROM fines f JOIN loans l ON f.loan_id = l.id WHERE l.member_id = ? AND f.status = 'belum_dibayar'");
mysqli_stmt_bind_param($stmt_f, "i", $member_id);
mysqli_stmt_execute($stmt_f);
$total_denda = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_f))['total'];
mysqli_stmt_close($stmt_f);

// Pengaturan template
$page_title  = 'Ubah Profil Saya';
$active_page = 'profil';
$base_url    = '../';
$breadcrumb  = '<a href="../dashboard_anggota.php">Dashboard</a> / <strong>Ubah Profil</strong>';

include '../templates/header.php';
include '../templates/sidebar_anggota.php';
?>

<div class="main-content">
    <?php include '../templates/topnav.php'; ?>
    <div class="content-wrapper">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card content-card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                        <h5 class="mb-0 fw-bold">👤 Ubah Profil Anggota</h5>
                        <a href="../dashboard_anggota.php" class="btn btn-light btn-sm fw-semibold">&larr; Kembali</a>
                    </div>
                    <div class="card-body p-3 p-md-4">

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                ✅ <strong>Berhasil!</strong> <?= htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                ⚠️ <strong>Peringatan!</strong> <?= htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="profil.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Kode Anggota</label>
                                    <input type="text" class="form-control bg-light fw-bold" value="<?= htmlspecialchars($profil['member_code']); ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Username Login</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($profil['username']); ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Status Keanggotaan</label>
                                    <input type="text" class="form-control bg-light fw-bold <?= $profil['status'] === 'aktif' ? 'text-success' : 'text-danger'; ?>" value="<?= strtoupper($profil['status']); ?>" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label for="full_name" class="form-label fw-bold small">Nama Lengkap</label>
                                    <input type="text" name="full_name" id="full_name" class="form-control" value="<?= htmlspecialchars($profil['full_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label fw-bold small">No. Telepon / WhatsApp</label>
                                    <input type="text" name="phone" id="phone" class="form-control" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($profil['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-12">
                                    <label for="address" class="form-label fw-bold small">Alamat Tempat Tinggal</label>
                                    <textarea name="address" id="address" class="form-control" rows="3" placeholder="Alamat lengkap"><?= htmlspecialchars($profil['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="col-12">
                                    <hr class="my-2">
                                    <div class="small text-muted fw-semibold">Ganti Password (Opsional — kosongkan jika tidak ingin mengganti password)</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label fw-bold small">Password Baru</label>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 6 karakter" minlength="6">
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label fw-bold small">Konfirmasi Password Baru</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Ulangi password baru" minlength="6">
                                </div>

                                <div class="col-md-12 mt-4 d-flex justify-content-end flex-wrap gap-2">
                                    <a href="../dashboard_anggota.php" class="btn btn-secondary px-4">Batal</a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold">💾 Simpan Perubahan</button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content-wrapper -->
</div><!-- /main-content -->

<?php include '../templates/footer.php'; ?>
