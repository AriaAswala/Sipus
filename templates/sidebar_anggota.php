<?php
/**
 * File: templates/sidebar_anggota.php
 * Deskripsi: Sidebar navigasi untuk role Anggota.
 * 
 * Variabel yang harus diset SEBELUM include:
 *   $active_page   - Halaman aktif: 'dashboard','katalog','riwayat'
 *   $base_url      - Path relatif ke root sipus
 *   $active_loans  - (opsional) jumlah pinjaman aktif
 *   $total_buku    - (opsional) jumlah buku tersedia
 *   $total_denda   - (opsional) total denda tertunggak
 */
$active_page = $active_page ?? 'dashboard';
$base_url = $base_url ?? '';

// Inisial nama untuk avatar
$_initials_a = '';
$_name_parts_a = explode(' ', $_SESSION['full_name'] ?? 'A');
$_initials_a .= strtoupper(substr($_name_parts_a[0], 0, 1));
if (isset($_name_parts_a[1])) $_initials_a .= strtoupper(substr($_name_parts_a[1], 0, 1));
?>

<!-- ============ SIDEBAR ANGGOTA ============ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">📚</span>
        <div>
            <span class="brand-text">SIPUS</span>
            <span class="brand-sub">Sistem Informasi Perpustakaan</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= $_initials_a; ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="user-role">Anggota Perpustakaan</div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-label">Menu Utama</div>
        <a href="<?= $base_url; ?>dashboard_anggota.php" class="<?= $active_page === 'dashboard' ? 'active' : ''; ?>">
            <span class="menu-icon">🏠</span> Dashboard
        </a>
        <a href="<?= $base_url; ?>index.php" class="<?= $active_page === 'katalog' ? 'active' : ''; ?>">
            <span class="menu-icon">📖</span> Katalog Buku
            <?php if (isset($total_buku) && $total_buku > 0): ?>
                <span class="menu-badge" style="background: #2980B9;"><?= $total_buku; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= $base_url; ?>peminjaman/riwayat.php" class="<?= $active_page === 'riwayat' ? 'active' : ''; ?>">
            <span class="menu-icon">📋</span> Riwayat Peminjaman
        </a>

        <div class="menu-label">Informasi</div>
        <a href="<?= $base_url; ?>peminjaman/riwayat.php">
            <span class="menu-icon">📦</span> Buku Dipinjam
            <?php if (isset($active_loans) && $active_loans > 0): ?>
                <span class="menu-badge" style="background: #F39C12;"><?= $active_loans; ?></span>
            <?php endif; ?>
        </a>
        <?php if (isset($total_denda) && $total_denda > 0): ?>
        <a href="<?= $base_url; ?>peminjaman/riwayat.php">
            <span class="menu-icon">💰</span> Denda Saya
            <span class="menu-badge">!</span>
        </a>
        <?php endif; ?>

        <div class="menu-label">Akun</div>
        <a href="<?= $base_url; ?>anggota/profil.php" class="<?= $active_page === 'profil' ? 'active' : ''; ?>">
            <span class="menu-icon">👤</span> Ubah Profil
        </a>
        <a href="<?= $base_url; ?>logout.php" onclick="return confirm('Yakin ingin logout?');">
            <span class="menu-icon">🚪</span> Logout
        </a>
    </nav>
</aside>
