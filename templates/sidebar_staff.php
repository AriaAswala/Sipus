<?php
/**
 * File: templates/sidebar_staff.php
 * Deskripsi: Sidebar navigasi untuk role Admin & Pustakawan.
 * 
 * Variabel yang harus diset SEBELUM include:
 *   $active_page - Halaman aktif: 'dashboard','buku','anggota','peminjaman','laporan'
 *   $base_url    - Path relatif ke root sipus
 */
$active_page = $active_page ?? 'dashboard';
$base_url = $base_url ?? '';

// Inisial nama untuk avatar
$_initials = '';
$_name_parts = explode(' ', $_SESSION['full_name'] ?? 'A');
$_initials .= strtoupper(substr($_name_parts[0], 0, 1));
if (isset($_name_parts[1])) $_initials .= strtoupper(substr($_name_parts[1], 0, 1));

$_role_label = $_SESSION['role'] === 'admin' ? 'Administrator' : 'Pustakawan';
$_role_color = $_SESSION['role'] === 'admin' ? '#E74C3C' : '#27AE60';
$_dashboard_url = $_SESSION['role'] === 'admin' ? $base_url . 'dashboard_admin.php' : $base_url . 'dashboard_pustakawan.php';
?>

<!-- ============ SIDEBAR STAFF ============ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">📚</span>
        <div>
            <span class="brand-text">SIPUS</span>
            <span class="brand-sub">Sistem Informasi Perpustakaan</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar"><?= $_initials; ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']); ?></div>
            <div class="user-role" style="color: <?= $_role_color; ?>;"><?= $_role_label; ?></div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-label">Menu Utama</div>
        <a href="<?= $_dashboard_url; ?>" class="<?= $active_page === 'dashboard' ? 'active' : ''; ?>">
            <span class="menu-icon">🏠</span> Dashboard
        </a>

        <div class="menu-label">Master Data</div>
        <a href="<?= $base_url; ?>buku/index.php" class="<?= $active_page === 'buku' ? 'active' : ''; ?>">
            <span class="menu-icon">📖</span> Data Buku
        </a>
        <a href="<?= $base_url; ?>anggota/index.php" class="<?= $active_page === 'anggota' ? 'active' : ''; ?>">
            <span class="menu-icon">👥</span> Data Anggota
        </a>

        <div class="menu-label">Transaksi</div>
        <a href="<?= $base_url; ?>peminjaman/index.php" class="<?= $active_page === 'peminjaman' ? 'active' : ''; ?>">
            <span class="menu-icon">🔄</span> Sirkulasi Peminjaman
        </a>
        <a href="<?= $base_url; ?>laporan/index.php" class="<?= $active_page === 'laporan' ? 'active' : ''; ?>">
            <span class="menu-icon">📊</span> Laporan & Denda
        </a>

        <div class="menu-label">Lainnya</div>
        <a href="<?= $base_url; ?>index.php">
            <span class="menu-icon">🌐</span> Katalog Publik
        </a>
        <a href="<?= $base_url; ?>logout.php" onclick="return confirm('Yakin ingin logout?');">
            <span class="menu-icon">🚪</span> Logout
        </a>
    </nav>
</aside>
