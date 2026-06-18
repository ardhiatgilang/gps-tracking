<?php
/**
 * Halaman Riwayat Laporan Admin Lapangan
 * Menampilkan semua laporan kunjungan yang pernah dibuat
 */

require_once '../config/database.php';
checkRole('admin');

$user = getCurrentUser();

// Filter parameters
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_project = isset($_GET['project']) ? intval($_GET['project']) : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Build WHERE clause
$where = ["vr.admin_id = ?"];
$params = [$user['id']];
$types = "i";

if (!empty($filter_date)) {
    $where[] = "DATE(vr.waktu_kunjungan) = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if ($filter_project > 0) {
    $where[] = "vr.project_id = ?";
    $params[] = $filter_project;
    $types .= "i";
}

if (!empty($filter_status)) {
    if ($filter_status === 'valid') {
        $where[] = "vr.is_valid = 1";
    } elseif ($filter_status === 'invalid') {
        $where[] = "vr.is_valid = 0";
    }
}

$whereClause = implode(" AND ", $where);

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records
$countQuery = "SELECT COUNT(*) as total FROM visit_reports vr WHERE $whereClause";
$countResult = executeQuery($countQuery, $types, $params);
$total = $countResult['data']->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Get reports with pagination
$reportsQuery = "SELECT vr.*, pl.nama_project, pl.alamat
                 FROM visit_reports vr
                 JOIN project_locations pl ON vr.project_id = pl.id
                 WHERE $whereClause
                 ORDER BY vr.waktu_kunjungan DESC
                 LIMIT ? OFFSET ?";
$queryParams = array_merge($params, [$limit, $offset]);
$queryTypes = $types . "ii";
$reportsResult = executeQuery($reportsQuery, $queryTypes, $queryParams);

// Get project list for filter dropdown
$projectsQuery = "SELECT DISTINCT pl.id, pl.nama_project
                  FROM visit_reports vr
                  JOIN project_locations pl ON vr.project_id = pl.id
                  WHERE vr.admin_id = ?
                  ORDER BY pl.nama_project";
$projectsResult = executeQuery($projectsQuery, "i", [$user['id']]);
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

        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label>Tanggal:</label>
                            <input type="date" name="date" class="form-control" value="<?php echo $filter_date; ?>">
                        </div>
                        <div class="filter-item">
                            <label>Project:</label>
                            <select name="project" class="form-control">
                                <option value="0">Semua Project</option>
                                <?php while ($proj = $projectsResult['data']->fetch_assoc()): ?>
                                    <option value="<?php echo $proj['id']; ?>"
                                            <?php echo $filter_project == $proj['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($proj['nama_project']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label>Status:</label>
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="valid" <?php echo $filter_status === 'valid' ? 'selected' : ''; ?>>Valid</option>
                                <option value="invalid" <?php echo $filter_status === 'invalid' ? 'selected' : ''; ?>>Di Luar Radius</option>
                            </select>
                        </div>
                        <div class="filter-buttons">
                            <button type="submit" class="btn btn-primary">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                                </svg>
                                Cari
                            </button>
                            <a href="riwayat.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
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
                                    <th>Waktu Kunjungan</th>
                                    <th>Project</th>
                                    <th>Lokasi</th>
                                    <th>Jarak</th>
                                    <th>Akurasi GPS</th>
                                    <th>Status</th>
                                    <th>Foto</th>
                                    <th>Aksi</th>
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
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-warning btn-edit"
                                                        onclick="openEditModal(<?php echo htmlspecialchars(json_encode([
                                                            'id' => $report['id'],
                                                            'jenis_pekerjaan' => $report['jenis_pekerjaan'] ?? '',
                                                            'catatan' => $report['catatan'] ?? '',
                                                            'foto_laporan' => $report['foto_laporan'] ?? '',
                                                            'nama_project' => $report['nama_project'],
                                                            'waktu' => date('d/m/Y H:i', strtotime($report['waktu_kunjungan']))
                                                        ])); ?>)">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-delete"
                                                        onclick="confirmDelete(<?php echo $report['id']; ?>, '<?php echo htmlspecialchars($report['nama_project'], ENT_QUOTES); ?>')">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                </button>
                                            </div>
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
                                'project' => $filter_project,
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
                    <p class="text-center text-secondary">Belum ada laporan kunjungan</p>
                <?php endif; ?>
            </div>
        </div>
        <!-- Alert container -->
        <div id="alert-container" style="position: fixed; top: 80px; right: 20px; z-index: 2000; max-width: 400px;"></div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Laporan</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="edit-info" id="editInfo"></div>
                <form id="editForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_report_id" name="report_id">
                    <div class="form-group">
                        <label for="edit_jenis_pekerjaan">Jenis Pekerjaan</label>
                        <select class="form-control" id="edit_jenis_pekerjaan" name="jenis_pekerjaan">
                            <option value="">-- Pilih Jenis Pekerjaan --</option>
                            <option value="Antar Berita Acara">Antar Berita Acara</option>
                            <option value="Antar Invoice">Antar Invoice</option>
                            <option value="Tarik GR">Tarik GR</option>
                            <option value="Antar Giro">Antar Giro</option>
                            <option value="Ambil Giro">Ambil Giro</option>
                            <option value="Antar Revisi Berita Acara">Antar Revisi Berita Acara</option>
                            <option value="Antar Revisi Invoice">Antar Revisi Invoice</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_catatan">Catatan</label>
                        <textarea class="form-control" id="edit_catatan" name="catatan" rows="3" placeholder="Catatan kunjungan..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Foto Laporan</label>
                        <div class="edit-foto-preview" id="editFotoPreview"></div>
                        <input type="file" id="edit_foto" name="foto" accept="image/jpeg,image/jpg,image/png" style="margin-top: 8px;">
                        <small class="text-secondary">Kosongkan jika tidak ingin mengubah foto. Format: JPG, PNG. Maks 5MB.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveEdit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>Hapus Laporan</h3>
                <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="delete-warning">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <p>Apakah Anda yakin ingin menghapus laporan ini?</p>
                    <p class="delete-detail" id="deleteDetail"></p>
                    <small class="text-danger">Tindakan ini tidak dapat dibatalkan.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete" onclick="executeDelete()">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .filter-form .filter-row {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .filter-form .filter-item {
            flex: 1;
            min-width: 150px;
        }
        .filter-form .filter-item label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 4px;
        }
        .filter-form .filter-item .form-control {
            width: 100%;
        }
        .filter-form .filter-buttons {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }
        @media (max-width: 768px) {
            .filter-form .filter-item {
                min-width: 100%;
            }
            .filter-form .filter-buttons {
                width: 100%;
                margin-top: 4px;
            }
            .filter-form .filter-buttons .btn {
                flex: 1;
            }
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 6px;
            justify-content: center;
        }
        .btn-edit, .btn-delete {
            padding: 6px 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-warning {
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn-warning:hover {
            background: #d97706;
        }

        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1500;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideUp 0.3s ease;
        }
        .modal-sm {
            max-width: 400px;
        }
        @keyframes modalSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1f2937;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .modal-close:hover {
            color: #1f2937;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        .edit-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #0369a1;
        }
        .edit-foto-preview img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
            object-fit: contain;
            border: 1px solid #e5e7eb;
        }
        .delete-warning {
            text-align: center;
            padding: 10px 0;
        }
        .delete-warning svg {
            margin-bottom: 12px;
        }
        .delete-warning p {
            margin: 0 0 8px;
            font-size: 15px;
            color: #374151;
        }
        .delete-detail {
            font-weight: 600;
            color: #1f2937 !important;
        }

        /* Alert notification */
        #alert-container .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>

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

        // ===== EDIT FUNCTIONS =====
        function openEditModal(data) {
            document.getElementById('edit_report_id').value = data.id;
            document.getElementById('edit_jenis_pekerjaan').value = data.jenis_pekerjaan || '';
            document.getElementById('edit_catatan').value = data.catatan || '';
            document.getElementById('edit_foto').value = '';

            // Show info
            document.getElementById('editInfo').innerHTML =
                '<strong>' + data.nama_project + '</strong> &mdash; ' + data.waktu;

            // Show current photo preview
            const previewDiv = document.getElementById('editFotoPreview');
            if (data.foto_laporan) {
                previewDiv.innerHTML = '<img src="../uploads/laporan/' + data.foto_laporan + '" alt="Foto saat ini">';
            } else {
                previewDiv.innerHTML = '<span class="text-secondary">Tidak ada foto</span>';
            }

            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // Handle edit form submission
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const btn = document.getElementById('btnSaveEdit');
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            const formData = new FormData(this);

            fetch('../api/edit_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                try { return JSON.parse(text); }
                catch(e) { throw new Error('Response bukan JSON'); }
            })
            .then(result => {
                if (result.success) {
                    showAlert(result.message, 'success');
                    closeEditModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message, 'danger');
                }
            })
            .catch(error => {
                showAlert(error.message || 'Terjadi kesalahan', 'danger');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Simpan';
            });
        });

        // ===== DELETE FUNCTIONS =====
        let deleteReportId = null;

        function confirmDelete(id, projectName) {
            deleteReportId = id;
            document.getElementById('deleteDetail').textContent = projectName;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteReportId = null;
        }

        function executeDelete() {
            if (!deleteReportId) return;

            const btn = document.getElementById('btnConfirmDelete');
            btn.disabled = true;
            btn.textContent = 'Menghapus...';

            fetch('../api/delete_report.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ report_id: deleteReportId })
            })
            .then(response => response.text())
            .then(text => {
                try { return JSON.parse(text); }
                catch(e) { throw new Error('Response bukan JSON'); }
            })
            .then(result => {
                if (result.success) {
                    showAlert(result.message, 'success');
                    closeDeleteModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message, 'danger');
                }
            })
            .catch(error => {
                showAlert(error.message || 'Terjadi kesalahan', 'danger');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Hapus';
            });
        }

        // ===== ALERT FUNCTION =====
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-' + type;
            alertDiv.textContent = message;

            const container = document.getElementById('alert-container');
            container.innerHTML = '';
            container.appendChild(alertDiv);

            setTimeout(() => { alertDiv.remove(); }, 4000);
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
                closeDeleteModal();
            }
        });

        // Close modals when clicking overlay
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</body>
</html>
