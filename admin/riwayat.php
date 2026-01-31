<?php
/**
 * Halaman Riwayat Laporan Admin Lapangan
 * Menampilkan semua laporan kunjungan yang pernah dibuat
 */

require_once '../config/database.php';
checkRole('admin');

$user = getCurrentUser();

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records
$countQuery = "SELECT COUNT(*) as total FROM visit_reports WHERE admin_id = ?";
$countResult = executeQuery($countQuery, "i", [$user['id']]);
$total = $countResult['data']->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Get reports with pagination
$reportsQuery = "SELECT vr.*, pl.nama_project, pl.alamat
                 FROM visit_reports vr
                 JOIN project_locations pl ON vr.project_id = pl.id
                 WHERE vr.admin_id = ?
                 ORDER BY vr.waktu_kunjungan DESC
                 LIMIT ? OFFSET ?";
$reportsResult = executeQuery($reportsQuery, "iii", [$user['id'], $limit, $offset]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan - Admin Lapangan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">GPS Tracking System</div>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="tracking.php">Tracking GPS</a>
            <a href="laporan.php">Buat Laporan</a>
            <a href="riwayat.php" class="active">Riwayat</a>
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
        <h2 class="mb-3">Riwayat Laporan Kunjungan</h2>

        <!-- Reports Table -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-between align-center">
                    <span>Total: <?php echo $total; ?> laporan</span>
                </div>
            </div>
            <div class="card-body">
                <?php if ($reportsResult['data']->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Waktu Kunjungan</th>
                                    <th>Project</th>
                                    <th>Lokasi</th>
                                    <th>Jarak</th>
                                    <th>Akurasi GPS</th>
                                    <th>Status</th>
                                    <th>Foto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                while ($report = $reportsResult['data']->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($report['waktu_kunjungan'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($report['nama_project']); ?></strong><br>
                                            <small class="text-secondary"><?php echo htmlspecialchars($report['alamat']); ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo number_format($report['latitude'], 6); ?>,<br>
                                                <?php echo number_format($report['longitude'], 6); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $jarak = $report['jarak_dari_project'];
                                            $class = $jarak <= 50 ? 'text-success' : 'text-danger';
                                            echo "<span class='$class'>" . number_format($jarak, 2) . " m</span>";
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $acc = $report['accuracy'];
                                            $accClass = $acc <= 20 ? 'text-success' : ($acc <= 50 ? 'text-warning' : 'text-danger');
                                            echo "<span class='$accClass'>" . number_format($acc, 2) . " m</span>";
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($report['is_valid']): ?>
                                                <span class="badge badge-success">Valid</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Di Luar Radius</span>
                                            <?php endif; ?>
                                            <br>
                                            <?php
                                            $statusClass = [
                                                'pending' => 'badge-warning',
                                                'verified' => 'badge-success',
                                                'rejected' => 'badge-danger'
                                            ];
                                            $statusText = [
                                                'pending' => 'Pending',
                                                'verified' => 'Verified',
                                                'rejected' => 'Rejected'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $statusClass[$report['status']]; ?>">
                                                <?php echo $statusText[$report['status']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($report['foto_laporan']): ?>
                                                <a href="../uploads/laporan/<?php echo $report['foto_laporan']; ?>"
                                                   target="_blank" class="btn btn-sm btn-primary">Lihat</a>
                                            <?php else: ?>
                                                <span class="text-secondary">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-3 text-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>"
                                   class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"
                                   style="margin: 2px;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="text-center text-secondary">Belum ada laporan kunjungan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
