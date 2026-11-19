<?php
/**
 * File: templates/topnav.php
 * Deskripsi: Top navbar bersama untuk semua halaman sidebar layout.
 * 
 * Variabel yang harus diset SEBELUM include:
 *   $page_title    - Judul halaman
 *   $breadcrumb    - Breadcrumb text (misal: 'Master Data / Data Buku')
 *   $base_url      - Path relatif ke root sipus
 */
$page_title = $page_title ?? 'Dashboard';
$breadcrumb = $breadcrumb ?? '';
$base_url = $base_url ?? '';
?>

<!-- ============ TOP NAVBAR ============ -->
<header class="top-navbar">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Buka Menu">☰</button>
        <div>
            <div class="page-title"><?= htmlspecialchars($page_title); ?></div>
            <?php if (!empty($breadcrumb)): ?>
                <div class="breadcrumb-nav"><?= $breadcrumb; ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="nav-right">
        <span class="nav-date" style="font-size:13px; color:#7F8C8D;">
            <?= date('l, d M Y'); ?>
        </span>
        <a href="<?= $base_url; ?>logout.php" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin logout?');">Logout</a>
    </div>
</header>
