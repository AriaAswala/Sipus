<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Footer Info -->
<div class="app-footer text-center text-muted small mt-4 pb-3 no-print">
    SIPUS &copy; <?= date('Y'); ?> — Sistem Informasi Perpustakaan | Projek RPL
</div>

<!-- Bootstrap 5 JS Bundle via CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    var sidebar = document.querySelector('.sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    if (sidebar && overlay) {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
    }
}
window.addEventListener('resize', function () {
    if (window.innerWidth > 768) {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }
});
</script>
</body>
</html>
