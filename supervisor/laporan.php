<?php
/**
 * Halaman Laporan Supervisor
 * Melihat semua laporan kunjungan dari semua admin
 */

require_once '../config/database.php';
checkRole('supervisor');

$user = getCurrentUser();

// Filter
$filter_admin = isset($_GET['admin']) ? intval($_GET['admin']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = ["DATE(vr.waktu_kunjungan) = ?"];
$params = [$filter_date];
$types = "s";

if ($filter_admin > 0) {
    $where[] = "vr.admin_id = ?";
    $params[] = $filter_admin;
    $types .= "i";
}

if (!empty($filter_status)) {
    $where[] = "vr.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$whereClause = implode(" AND ", $where);

// Get total records
$countQuery = "SELECT COUNT(*) as total FROM visit_reports vr WHERE $whereClause";
$countResult = executeQuery($countQuery, $types, $params);
$total = $countResult['data']->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Get reports
$reportsQuery = "SELECT vr.*, u.nama_lengkap as admin_name, pl.nama_project, pl.alamat
                 FROM visit_reports vr
                 JOIN users u ON vr.admin_id = u.id
                 JOIN project_locations pl ON vr.project_id = pl.id
                 WHERE $whereClause
                 ORDER BY vr.waktu_kunjungan DESC
                 LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";
$reportsResult = executeQuery($reportsQuery, $types, $params);

// Get admin list for filter
$adminQuery = "SELECT id, nama_lengkap FROM users WHERE role = 'admin' ORDER BY nama_lengkap";
$adminResult = executeQuery($adminQuery);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kunjungan - Supervisor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">GPS Tracking System - Supervisor</div>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="monitoring.php">Monitoring Realtime</a>
            <a href="laporan.php" class="active">Laporan</a>
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
        <h2 class="mb-3">Laporan Kunjungan</h2>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap: wrap;">
                    <label>Tanggal:</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>" style="width: auto;">

                    <label>Admin:</label>
                    <select name="admin" class="form-control" style="width: auto;">
                        <option value="0">Semua Admin</option>
                        <?php while ($admin = $adminResult['data']->fetch_assoc()): ?>
                            <option value="<?php echo $admin['id']; ?>"
                                    <?php echo $filter_admin == $admin['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin['nama_lengkap']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label>Status:</label>
                    <select name="status" class="form-control" style="width: auto;">
                        <option value="">Semua Status</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="verified" <?php echo $filter_status == 'verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="laporan.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>

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
                                    <th>Waktu</th>
                                    <th>Admin</th>
                                    <th>Project</th>
                                    <th>Koordinat</th>
                                    <th>Jarak</th>
                                    <th>Akurasi GPS</th>
                                    <th>Validasi</th>
                                    <th>Status</th>
                                    <th>Foto</th>
                                    <th>Catatan</th>
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
                                        <td><?php echo htmlspecialchars($report['admin_name']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($report['nama_project']); ?></strong><br>
                                            <small class="text-secondary"><?php echo htmlspecialchars($report['alamat']); ?></small>
                                        </td>
                                        <td>
                                            <small>
                                                <?php echo number_format($report['latitude'], 6); ?>,
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
                                                <span class="badge badge-danger">Invalid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
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
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $report['catatan'] ? htmlspecialchars(substr($report['catatan'], 0, 50)) . '...' : '-'; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-3 text-center">
                            <?php
                            $query_string = http_build_query([
                                'date' => $filter_date,
                                'admin' => $filter_admin,
                                'status' => $filter_status
                            ]);
                            ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>"
                                   class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>"
                                   style="margin: 2px;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="text-center text-secondary">Tidak ada laporan ditemukan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
