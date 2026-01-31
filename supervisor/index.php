<?php
/**
 * Dashboard Supervisor
 * Halaman utama untuk supervisor - Overview semua admin lapangan
 */

require_once '../config/database.php';
checkRole('supervisor');

$user = getCurrentUser();
$today = date('Y-m-d');

// Get overall statistics
$statsQuery = "SELECT
                    COUNT(DISTINCT admin_id) as total_admin_aktif,
                    COUNT(*) as total_kunjungan_hari_ini,
                    SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as kunjungan_valid
               FROM visit_reports
               WHERE DATE(waktu_kunjungan) = ?";
$statsResult = executeQuery($statsQuery, "s", [$today]);
$stats = $statsResult['data']->fetch_assoc();

// Get total admin
$totalAdminQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND status = 'active'";
$totalAdminResult = executeQuery($totalAdminQuery);
$totalAdmin = $totalAdminResult['data']->fetch_assoc()['total'];

// Get today's summary by admin
$summaryQuery = "SELECT
                    u.nama_lengkap,
                    ds.*
                 FROM daily_summary ds
                 JOIN users u ON ds.admin_id = u.id
                 WHERE ds.tanggal = ?
                 ORDER BY ds.total_jarak_tempuh DESC";
$summaryResult = executeQuery($summaryQuery, "s", [$today]);

// Get recent reports
$recentQuery = "SELECT
                    vr.*,
                    u.nama_lengkap as admin_name,
                    pl.nama_project
                FROM visit_reports vr
                JOIN users u ON vr.admin_id = u.id
                JOIN project_locations pl ON vr.project_id = pl.id
                ORDER BY vr.waktu_kunjungan DESC
                LIMIT 10";
$recentResult = executeQuery($recentQuery);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Supervisor - GPS Tracking</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">GPS Tracking System - Supervisor</div>
        <div class="navbar-menu">
            <a href="index.php" class="active">Dashboard</a>
            <a href="monitoring.php">Monitoring Realtime</a>
            <a href="laporan.php">Laporan</a>
            <a href="analisis.php">Analisis Produktivitas</a>
        </div>
        <div class="navbar-user">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                <div class="user-role">Supervisor</div>
            </div>
            <a href="../logout.php" class="btn btn-secondary btn-sm">Logout</a>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container-fluid">
        <h2 class="mb-3">Dashboard Supervisor</h2>

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card info">
                <div class="stat-label">Total Admin Lapangan</div>
                <div class="stat-value"><?php echo $totalAdmin; ?></div>
            </div>

            <div class="stat-card success">
                <div class="stat-label">Admin Aktif Hari Ini</div>
                <div class="stat-value"><?php echo $stats['total_admin_aktif'] ?? 0; ?></div>
            </div>

            <div class="stat-card warning">
                <div class="stat-label">Total Kunjungan Hari Ini</div>
                <div class="stat-value"><?php echo $stats['total_kunjungan_hari_ini'] ?? 0; ?></div>
            </div>

            <div class="stat-card primary">
                <div class="stat-label">Kunjungan Valid</div>
                <div class="stat-value"><?php echo $stats['kunjungan_valid'] ?? 0; ?></div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Produktivitas Admin Hari Ini -->
            <div class="card">
                <div class="card-header">Produktivitas Admin Hari Ini</div>
                <div class="card-body">
                    <?php if ($summaryResult['data']->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Admin</th>
                                        <th>Kunjungan</th>
                                        <th>Jarak (km)</th>
                                        <th>Success Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($summary = $summaryResult['data']->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($summary['nama_lengkap']); ?></td>
                                            <td>
                                                <?php echo $summary['jumlah_kunjungan_valid']; ?> /
                                                <?php echo $summary['jumlah_kunjungan']; ?>
                                            </td>
                                            <td><?php echo number_format($summary['total_jarak_tempuh'], 2); ?></td>
                                            <td>
                                                <?php
                                                $rate = $summary['success_rate'];
                                                $class = $rate >= 80 ? 'text-success' : ($rate >= 60 ? 'text-warning' : 'text-danger');
                                                echo "<span class='$class'>" . number_format($rate, 1) . "%</span>";
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-secondary">Belum ada data hari ini</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Laporan Terbaru -->
            <div class="card">
                <div class="card-header">Laporan Kunjungan Terbaru</div>
                <div class="card-body">
                    <?php if ($recentResult['data']->num_rows > 0): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table style="font-size: 13px;">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Admin</th>
                                        <th>Project</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($recent = $recentResult['data']->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('H:i', strtotime($recent['waktu_kunjungan'])); ?></td>
                                            <td><?php echo htmlspecialchars($recent['admin_name']); ?></td>
                                            <td><?php echo htmlspecialchars($recent['nama_project']); ?></td>
                                            <td>
                                                <?php if ($recent['is_valid']): ?>
                                                    <span class="badge badge-success">Valid</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Invalid</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-secondary">Belum ada laporan</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="card">
            <div class="card-header">Menu Utama</div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="monitoring.php" class="btn btn-primary">Monitoring Realtime</a>
                    <a href="laporan.php" class="btn btn-success">Lihat Semua Laporan</a>
                    <a href="analisis.php" class="btn btn-info">Analisis Produktivitas</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
