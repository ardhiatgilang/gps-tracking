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
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = ["1=1"];
$params = [];
$types = "";

if (!empty($filter_date)) {
    $where[] = "DATE(vr.waktu_kunjungan) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if ($filter_admin > 0) {
    $where[] = "vr.admin_id = ?";
    $params[] = $filter_admin;
    $types .= "i";
}

$whereClause = implode(" AND ", $where);

// Get total records
$countQuery = "SELECT COUNT(*) as total FROM visit_reports vr WHERE $whereClause";
if (!empty($types)) {
    $countResult = executeQuery($countQuery, $types, $params);
} else {
    $countResult = executeQuery($countQuery);
}
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
            <a href="monitoring.php">Monitoring Realtime</a>
            <a href="laporan.php" class="active">Laporan</a>
            <a href="analisis.php">Analisis Produktivitas</a>
        </div>
        <div class="navbar-user">
            <?php
            $initials = strtoupper(substr($user['nama_lengkap'], 0, 1));
            if (strpos($user['nama_lengkap'], ' ') !== false) {
                $parts = explode(' ', $user['nama_lengkap']);
                $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
            }
            ?>
            <div class="profile-trigger" onclick="toggleProfileDropdown()">
                <div class="profile-avatar">
                    <?php if (!empty($user['foto_profil']) && file_exists('../uploads/profile/' . $user['foto_profil'])): ?>
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
                        Supervisor
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
                    <a href="laporan.php" class="profile-dropdown-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        Laporan
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
    <div class="container-fluid">
        <h2 class="mb-3">Laporan Kunjungan</h2>

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <form method="GET" class="d-flex gap-2 align-center" style="flex-wrap: wrap; flex: 1;">
                    <label>Tanggal:</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>" style="width: auto;" placeholder="Semua tanggal">

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

                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="laporan.php" class="btn btn-secondary">Reset</a>
                </form>

                <!-- Export Button -->
                <a href="../api/export_laporan.php?date=<?php echo urlencode($filter_date); ?>&admin=<?php echo $filter_admin; ?>"
                   class="btn btn-success" style="margin-left: auto;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Export Excel
                </a>
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
                                    <th>Jenis Pekerjaan</th>
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
                                            <?php echo !empty($report['jenis_pekerjaan']) ? htmlspecialchars($report['jenis_pekerjaan']) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($report['foto_laporan']): ?>
                                                <button type="button" class="btn btn-sm btn-primary"
                                                        onclick="showPhotoModal(
                                                            '<?php echo $report['foto_laporan']; ?>',
                                                            '<?php echo htmlspecialchars($report['nama_project'], ENT_QUOTES); ?>',
                                                            '<?php echo htmlspecialchars($report['alamat'], ENT_QUOTES); ?>',
                                                            '<?php echo date('d/m/Y H:i:s', strtotime($report['waktu_kunjungan'])); ?>',
                                                            '<?php echo $report['latitude']; ?>',
                                                            '<?php echo $report['longitude']; ?>',
                                                            '<?php echo htmlspecialchars($report['admin_name'], ENT_QUOTES); ?>',
                                                            <?php echo $report['is_valid'] ? 'true' : 'false'; ?>,
                                                            '<?php echo htmlspecialchars($report['jenis_pekerjaan'] ?? '', ENT_QUOTES); ?>'
                                                        )">
                                                    Lihat
                                                </button>
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

    <!-- Photo Modal -->
    <div id="photoModal" class="modal" style="display: none;">
        <div class="modal-overlay" onclick="closePhotoModal()"></div>
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3 style="margin: 0;">Detail Foto Laporan</h3>
                <button type="button" class="btn btn-sm btn-secondary" onclick="closePhotoModal()">&times;</button>
            </div>
            <div class="modal-body" style="display: flex; gap: 20px; flex-wrap: wrap;">
                <!-- Photo -->
                <div style="flex: 1; min-width: 300px;">
                    <img id="modalPhoto" src="" alt="Foto Laporan"
                         style="width: 100%; max-height: 400px; object-fit: contain; border-radius: 8px; background: #f0f0f0;">
                </div>
                <!-- Info -->
                <div style="flex: 1; min-width: 250px;">
                    <div class="info-card" style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <h4 style="margin-top: 0; color: #333; border-bottom: 2px solid #3b82f6; padding-bottom: 8px;">
                            Informasi Kunjungan
                        </h4>

                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: bold; color: #666; font-size: 12px;">ADMIN</label>
                            <div id="modalAdmin" style="font-size: 14px;"></div>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: bold; color: #666; font-size: 12px;">LOKASI PROJECT</label>
                            <div id="modalProject" style="font-size: 14px; font-weight: bold;"></div>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: bold; color: #666; font-size: 12px;">ALAMAT/WILAYAH</label>
                            <div id="modalAlamat" style="font-size: 14px;"></div>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: bold; color: #666; font-size: 12px;">WAKTU KUNJUNGAN</label>
                            <div id="modalWaktu" style="font-size: 14px; font-weight: bold; color: #3b82f6;"></div>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: bold; color: #666; font-size: 12px;">KOORDINAT GPS</label>
                            <div id="modalKoordinat" style="font-size: 13px; font-family: monospace;"></div>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: bold; color: #666; font-size: 12px;">JENIS PEKERJAAN</label>
                            <div id="modalJenisPekerjaan" style="font-size: 14px; font-weight: bold; color: #059669;"></div>
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="font-weight: bold; color: #666; font-size: 12px;">STATUS VALIDASI</label>
                            <div id="modalValidasi"></div>
                        </div>

                        <a id="modalDownload" href="" download class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                            Download Foto
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
        }
        .modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            padding: 20px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Responsive Filter & Export */
        @media (max-width: 768px) {
            .card-body form {
                width: 100%;
                margin-bottom: 10px;
            }
            .card-body form .form-control {
                width: 100% !important;
                margin-bottom: 8px;
            }
            .card-body form label {
                width: 100%;
                margin-bottom: 4px;
            }
            .card-body form .btn {
                width: 48%;
            }
            .btn-success[href*="export"] {
                width: 100%;
                margin-left: 0 !important;
                margin-top: 10px;
            }
        }
    </style>

    <script>
        function showPhotoModal(foto, project, alamat, waktu, lat, lng, admin, isValid, jenisPekerjaan) {
            document.getElementById('modalPhoto').src = '../uploads/laporan/' + foto;
            document.getElementById('modalProject').textContent = project;
            document.getElementById('modalAlamat').textContent = alamat;
            document.getElementById('modalWaktu').textContent = waktu;
            document.getElementById('modalKoordinat').textContent = lat + ', ' + lng;
            document.getElementById('modalAdmin').textContent = admin;
            document.getElementById('modalJenisPekerjaan').textContent = jenisPekerjaan || '-';
            document.getElementById('modalDownload').href = '../uploads/laporan/' + foto;

            if (isValid) {
                document.getElementById('modalValidasi').innerHTML = '<span class="badge badge-success">Valid - Dalam Radius</span>';
            } else {
                document.getElementById('modalValidasi').innerHTML = '<span class="badge badge-danger">Invalid - Di Luar Radius</span>';
            }

            document.getElementById('photoModal').style.display = 'flex';
        }

        function closePhotoModal() {
            document.getElementById('photoModal').style.display = 'none';
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoModal();
            }
        });

        // Profile dropdown toggle
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }

        // Close dropdown when clicking outside
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
