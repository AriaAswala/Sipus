<?php
/**
 * File: laporan/index.php
 * Lokasi: sipus/laporan/index.php
 * Deskripsi: Halaman laporan transaksi peminjaman, rekapitulasi denda, serta fitur cetak.
 */

require_once '../cek_login.php';
require_once '../config/koneksi.php';

// Hak akses: Admin dan Pustakawan
cek_akses(['admin', 'pustakawan']);

// Filter Tanggal
$tgl_mulai = isset($_GET['tgl_mulai']) ? trim($_GET['tgl_mulai']) : date('Y-m-01');
$tgl_selesai = isset($_GET['tgl_selesai']) ? trim($_GET['tgl_selesai']) : date('Y-m-t');
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// 1. Hitung Statistik Ringkasan Peminjaman
$sql_stat = "SELECT 
                COUNT(*) as total_loans,
                SUM(CASE WHEN l.status = 'dipinjam' THEN 1 ELSE 0 END) as total_active,
                SUM(CASE WHEN l.status = 'dikembalikan' THEN 1 ELSE 0 END) as total_returned,
                COALESCE(SUM(CASE WHEN f.status = 'lunas' THEN f.fine_amount ELSE 0 END), 0) as fine_paid,
                COALESCE(SUM(CASE WHEN f.status = 'belum_dibayar' THEN f.fine_amount ELSE 0 END), 0) as fine_unpaid
             FROM loans l
             LEFT JOIN fines f ON f.loan_id = l.id";
$res_stat = mysqli_query($koneksi, $sql_stat);
$stat = mysqli_fetch_assoc($res_stat);

// 2. Query Data Laporan berdasarkan Filter Tanggal
$query_where = " WHERE l.loan_date BETWEEN ? AND ?";
$params = [$tgl_mulai, $tgl_selesai];
$types = "ss";

if (!empty($status_filter)) {
    $query_where .= " AND l.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql_laporan = "SELECT l.*, b.title as book_title, b.isbn, c.copy_code, u.full_name as member_name, m.member_code,
                       f.id as fine_id, f.fine_amount, f.status as fine_status, f.late_days
                FROM loans l
                JOIN books b ON l.book_id = b.id
                JOIN book_copies c ON l.copy_id = c.id
                JOIN members m ON l.member_id = m.id
                JOIN users u ON m.user_id = u.id
                LEFT JOIN fines f ON f.loan_id = l.id
                {$query_where}
                ORDER BY l.id DESC";

$stmt_lap = mysqli_prepare($koneksi, $sql_laporan);
mysqli_stmt_bind_param($stmt_lap, $types, ...$params);
mysqli_stmt_execute($stmt_lap);
$result_laporan = mysqli_stmt_get_result($stmt_lap);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi & Denda - SIPUS</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-light">

    <!-- Navbar Navigasi (Sembunyi saat dicetak) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4 no-print">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard_admin.php">📚 SIPUS RPL</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $_SESSION['role'] === 'admin' ? '../dashboard_admin.php' : '../dashboard_pustakawan.php'; ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../buku/index.php">Data Buku</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../anggota/index.php">Data Anggota</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../peminjaman/index.php">Sirkulasi Peminjaman</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="index.php">Laporan</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3 small">Halo, <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong> (<?= strtoupper($_SESSION['role']); ?>)</span>
                    <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-2">
        
        <!-- Header & Tombol Cetak -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold text-dark mb-0">Laporan Sirkulasi & Denda</h3>
                <p class="text-muted small mb-0">Periode: <strong><?= date('d/m/Y', strtotime($tgl_mulai)); ?></strong> s/d <strong><?= date('d/m/Y', strtotime($tgl_selesai)); ?></strong></p>
            </div>
            <button onclick="window.print();" class="btn btn-secondary fw-semibold no-print">
                🖨️ Cetak Laporan
            </button>
        </div>

        <!-- Kartu Statistik Rekapitulasi (No Print Filter, But Keep Summary Cards) -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body p-3">
                        <div class="small opacity-75">Total Transaksi</div>
                        <div class="fs-4 fw-bold"><?= $stat['total_loans']; ?> Peminjaman</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-warning text-dark">
                    <div class="card-body p-3">
                        <div class="small opacity-75">Sedang Dipinjam</div>
                        <div class="fs-4 fw-bold"><?= $stat['total_active']; ?> Buku</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body p-3">
                        <div class="small opacity-75">Denda Terkumpul (Lunas)</div>
                        <div class="fs-5 fw-bold">Rp <?= number_format($stat['fine_paid'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-danger text-white">
                    <div class="card-body p-3">
                        <div class="small opacity-75">Denda Tertunggak</div>
                        <div class="fs-5 fw-bold">Rp <?= number_format($stat['fine_unpaid'], 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Filter Periode (Sembunyi saat cetak) -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body p-3">
                <form action="index.php" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Dari Tanggal Pinjam</label>
                        <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Sampai Tanggal Pinjam</label>
                        <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Status Peminjaman</label>
                        <select name="status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="dipinjam" <?= $status_filter === 'dipinjam' ? 'selected' : ''; ?>>Sedang Dipinjam</option>
                            <option value="dikembalikan" <?= $status_filter === 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Filter Laporan</button>
                        <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center" style="width: 40px;">No</th>
                                <th>Anggota</th>
                                <th>Judul Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Tgl Kembali</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Denda</th>
                                <th class="text-center no-print" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result_laporan && mysqli_num_rows($result_laporan) > 0): 
                                while ($row = mysqli_fetch_assoc($result_laporan)):
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['member_name']); ?></strong><br>
                                        <small class="text-muted">Kode: <?= htmlspecialchars($row['member_code']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['book_title']); ?></strong><br>
                                        <small class="text-muted">Eksemplar: <?= htmlspecialchars($row['copy_code']); ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($row['loan_date'])); ?></td>
                                    <td><?= date('d/m/Y', strtotime($row['due_date'])); ?></td>
                                    <td><?= $row['return_date'] ? date('d/m/Y', strtotime($row['return_date'])) : '-'; ?></td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'dipinjam'): ?>
                                            <span class="badge bg-warning text-dark">DIPINJAM</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">DIKEMBALIKAN</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['fine_amount'] > 0): ?>
                                            <?php if ($row['fine_status'] === 'lunas'): ?>
                                                <span class="badge bg-success">LUNAS (Rp<?= number_format($row['fine_amount'], 0, ',', '.'); ?>)</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">RP <?= number_format($row['fine_amount'], 0, ',', '.'); ?> (TERTUNGGAK)</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Rp 0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center no-print">
                                        <?php if ($row['fine_amount'] > 0 && $row['fine_status'] === 'belum_dibayar'): ?>
                                            <a href="../peminjaman/bayar_denda.php?id=<?= $row['fine_id']; ?>&from=laporan" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Tandai denda ini lunas?');">
                                                💵 Lunasi
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Tidak ada data transaksi peminjaman dalam periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
