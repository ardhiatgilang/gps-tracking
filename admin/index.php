<?php
/**
 * Dashboard Admin Lapangan
 * Halaman utama untuk admin lapangan
 */

require_once '../config/database.php';
checkRole('admin');

$user = getCurrentUser();
$today = date('Y-m-d');

// Get today's statistics
$statsQuery = "SELECT
                    COUNT(*) as total_kunjungan,
                    SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as kunjungan_valid
               FROM visit_reports
               WHERE admin_id = ? AND DATE(waktu_kunjungan) = ?";
$statsResult = executeQuery($statsQuery, "is", [$user['id'], $today]);
$stats = $statsResult['data']->fetch_assoc();

// Get daily summary
$summaryQuery = "SELECT * FROM daily_summary WHERE admin_id = ? AND tanggal = ?";
$summaryResult = executeQuery($summaryQuery, "is", [$user['id'], $today]);
$summary = $summaryResult['data']->num_rows > 0 ? $summaryResult['data']->fetch_assoc() : null;

// Get recent reports
$reportsQuery = "SELECT vr.*, pl.nama_project
                 FROM visit_reports vr
                 JOIN project_locations pl ON vr.project_id = pl.id
                 WHERE vr.admin_id = ?
                 ORDER BY vr.waktu_kunjungan DESC
                 LIMIT 5";
$reportsResult = executeQuery($reportsQuery, "i", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - GPS Tracking</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">GPS Tracking System</div>
        <div class="navbar-menu">
            <a href="index.php" class="active">Dashboard</a>
            <a href="tracking.php">Tracking GPS</a>
            <a href="laporan.php">Buat Laporan</a>
            <a href="riwayat.php">Riwayat</a>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                <div class="user-role">Admin Lapangan</div>
            </div>
            <a href="../logout.php" class="btn btn-secondary btn-sm">Logout</a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <h2 class="mb-3">Dashboard Admin Lapangan</h2>

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card success">
                <div class="stat-label">Kunjungan Hari Ini</div>
                <div class="stat-value"><?php echo $stats['total_kunjungan'] ?? 0; ?></div>
            </div>

            <div class="stat-card info">
                <div class="stat-label">Kunjungan Valid</div>
                <div class="stat-value"><?php echo $stats['kunjungan_valid'] ?? 0; ?></div>
            </div>

            <div class="stat-card warning">
                <div class="stat-label">Total Jarak Tempuh</div>
                <div class="stat-value">
                    <?php
                    if ($summary && $summary['total_jarak_tempuh']) {
                        echo number_format($summary['total_jarak_tempuh'], 2) . ' km';
                    } else {
                        echo '0 km';
                    }
                    ?>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-label">Durasi Kerja</div>
                <div class="stat-value">
                    <?php
                    if ($summary && $summary['durasi_kerja_menit']) {
                        $hours = floor($summary['durasi_kerja_menit'] / 60);
                        $minutes = $summary['durasi_kerja_menit'] % 60;
                        echo $hours . 'j ' . $minutes . 'm';
                    } else {
                        echo '0j 0m';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">Aksi Cepat</div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="tracking.php" class="btn btn-primary">Mulai Tracking GPS</a>
                    <a href="laporan.php" class="btn btn-success">Buat Laporan Kunjungan</a>
                    <a href="riwayat.php" class="btn btn-secondary">Lihat Riwayat</a>
                </div>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="card">
            <div class="card-header">Laporan Terbaru</div>
            <div class="card-body">
                <?php if ($reportsResult['data']->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Project</th>
                                    <th>Jarak</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($report = $reportsResult['data']->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($report['waktu_kunjungan'])); ?></td>
                                        <td><?php echo htmlspecialchars($report['nama_project']); ?></td>
                                        <td><?php echo number_format($report['jarak_dari_project'], 2); ?> m</td>
                                        <td>
                                            <?php if ($report['is_valid']): ?>
                                                <span class="badge badge-success">Valid</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Di Luar Radius</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-secondary">Belum ada laporan kunjungan</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Produktivitas Info -->
        <?php if ($summary): ?>
        <div class="card">
            <div class="card-header">Produktivitas Hari Ini</div>
            <div class="card-body">
                <div class="gps-details">
                    <div class="gps-detail-item">
                        <span class="gps-detail-label">Efisiensi (kunjungan/jam):</span>
                        <span class="gps-detail-value"><?php echo number_format($summary['efisiensi_score'], 2); ?></span>
                    </div>
                    <div class="gps-detail-item">
                        <span class="gps-detail-label">Success Rate:</span>
                        <span class="gps-detail-value"><?php echo number_format($summary['success_rate'], 2); ?>%</span>
                    </div>
                    <div class="gps-detail-item">
                        <span class="gps-detail-label">Rata-rata Jarak/Kunjungan:</span>
                        <span class="gps-detail-value"><?php echo number_format($summary['rata_rata_jarak_per_kunjungan'], 2); ?> km</span>
                    </div>
                    <div class="gps-detail-item">
                        <span class="gps-detail-label">Waktu Mulai:</span>
                        <span class="gps-detail-value"><?php echo $summary['waktu_mulai'] ? date('H:i', strtotime($summary['waktu_mulai'])) : '-'; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
