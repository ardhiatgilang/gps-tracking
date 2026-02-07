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
    <?php include '../includes/page-loader.php'; ?>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="navbar-brand" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: inherit;">
            <svg width="28" height="28" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="pinGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3b82f6"/><stop offset="100%" style="stop-color:#1d4ed8"/>
                    </linearGradient>
                    <linearGradient id="checkGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#10b981"/><stop offset="100%" style="stop-color:#059669"/>
                    </linearGradient>
                </defs>
                <path d="M50 5 C30 5 15 22 15 40 C15 60 50 95 50 95 C50 95 85 60 85 40 C85 22 70 5 50 5 Z" fill="url(#pinGrad)" stroke="#1e40af" stroke-width="2"/>
                <circle cx="50" cy="38" r="22" fill="white"/>
                <path d="M38 38 L46 46 L62 30" stroke="url(#checkGrad)" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>LACAKIN</span>
        </a>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="laporan.php">Buat Laporan</a>
            <a href="riwayat.php" class="active">Riwayat</a>
        </div>
        <div class="navbar-user">
            <div class="profile-trigger" onclick="toggleProfileDropdown()">
                <div class="profile-avatar">
                    <?php
                    $initials = strtoupper(substr($user['nama_lengkap'], 0, 1));
                    if (strpos($user['nama_lengkap'], ' ') !== false) {
                        $parts = explode(' ', $user['nama_lengkap']);
                        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                    }
                    if (!empty($user['foto_profil']) && file_exists('../uploads/profile/' . $user['foto_profil'])):
                    ?>
                        <img src="../uploads/profile/<?php echo htmlspecialchars($user['foto_profil']); ?>" alt="Foto">
                    <?php else: echo $initials; endif; ?>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                    <div class="profile-dropdown-avatar">
                        <?php if (!empty($user['foto_profil']) && file_exists('../uploads/profile/' . $user['foto_profil'])): ?>
                            <img src="../uploads/profile/<?php echo htmlspecialchars($user['foto_profil']); ?>" alt="Foto">
                        <?php else: echo $initials; endif; ?>
                    </div>
                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                    <div class="profile-dropdown-role">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Admin Lapangan
                    </div>
                </div>
                <div class="profile-dropdown-body">
                    <a href="profil.php" class="profile-dropdown-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit Profil
                    </a>
                    <a href="index.php" class="profile-dropdown-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Dashboard
                    </a>
                    <div class="profile-dropdown-divider"></div>
                    <a href="../logout.php" class="profile-dropdown-item logout">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
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

    <script>
        // Profile dropdown toggle
        function toggleProfileDropdown() {
            document.getElementById('profileDropdown').classList.toggle('active');
        }
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profileDropdown');
            const trigger = document.querySelector('.profile-trigger');
            if (trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>
