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
            <a href="index.php" class="active">Dashboard</a>
            <a href="monitoring.php">Monitoring Realtime</a>
            <a href="laporan.php">Laporan</a>
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
        <h2 class="mb-3">Dashboard Supervisor</h2>

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card info">
                <div class="stat-label">Total Admin Lapangan</div>
                <div class="stat-value" id="statTotalAdmin"><?php echo $totalAdmin; ?></div>
            </div>

            <div class="stat-card success">
                <div class="stat-label">Admin Aktif Hari Ini</div>
                <div class="stat-value" id="statAdminAktif"><?php echo $stats['total_admin_aktif'] ?? 0; ?></div>
            </div>

            <div class="stat-card warning">
                <div class="stat-label">Total Kunjungan Hari Ini</div>
                <div class="stat-value" id="statTotalKunjungan"><?php echo $stats['total_kunjungan_hari_ini'] ?? 0; ?></div>
            </div>

            <div class="stat-card primary">
                <div class="stat-label">Kunjungan Valid</div>
                <div class="stat-value" id="statKunjunganValid"><?php echo $stats['kunjungan_valid'] ?? 0; ?></div>
            </div>
        </div>

        <div class="dashboard-content-grid">
            <!-- Produktivitas Admin Hari Ini -->
            <div class="card">
                <div class="card-header">Produktivitas Admin Hari Ini</div>
                <div class="card-body" id="produktivitasBody">
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
                                <tbody id="produktivitasTable">
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
                <div class="card-body" id="recentBody">
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
                                <tbody id="recentTable">
                                    <?php while ($recent = $recentResult['data']->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $reportDate = date('Y-m-d', strtotime($recent['waktu_kunjungan']));
                                                if ($reportDate == $today) {
                                                    echo '<span class="text-success">' . date('H:i', strtotime($recent['waktu_kunjungan'])) . '</span>';
                                                } else {
                                                    echo '<span class="text-secondary">' . date('d/m H:i', strtotime($recent['waktu_kunjungan'])) . '</span>';
                                                }
                                                ?>
                                            </td>
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
                <div class="menu-utama-buttons">
                    <a href="monitoring.php" class="btn btn-primary">Monitoring Realtime</a>
                    <a href="laporan.php" class="btn btn-success">Lihat Semua Laporan</a>
                    <a href="analisis.php" class="btn btn-info">Analisis Produktivitas</a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .dashboard-content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .menu-utama-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .menu-utama-buttons .btn {
            flex: 1;
            min-width: 150px;
            text-align: center;
        }

        /* Responsive untuk mobile/gadget */
        @media (max-width: 768px) {
            .dashboard-content-grid {
                grid-template-columns: 1fr;
            }

            .menu-utama-buttons {
                flex-direction: column;
            }

            .menu-utama-buttons .btn {
                width: 100%;
                min-width: unset;
            }
        }

        .stat-value.updated {
            animation: highlight 0.5s ease;
        }
        @keyframes highlight {
            0% { color: #3b82f6; transform: scale(1.1); }
            100% { color: inherit; transform: scale(1); }
        }
    </style>

    <script>
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

        // Auto-refresh functionality
        const REFRESH_INTERVAL = 30000; // 30 seconds

        function updateDashboard() {
            fetch('../api/get_dashboard_stats.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const data = result.data;

                        // Update statistics with animation
                        updateStatValue('statTotalAdmin', data.total_admin);
                        updateStatValue('statAdminAktif', data.admin_aktif);
                        updateStatValue('statTotalKunjungan', data.total_kunjungan);
                        updateStatValue('statKunjunganValid', data.kunjungan_valid);

                        // Update produktivitas table
                        updateProduktivitasTable(data.summaries);

                        // Update recent reports table
                        updateRecentTable(data.recent_reports);
                    }
                })
                .catch(error => {
                    console.error('Auto-refresh error:', error);
                });
        }

        function updateStatValue(elementId, newValue) {
            const element = document.getElementById(elementId);
            if (element && element.textContent != newValue) {
                element.textContent = newValue;
                element.classList.add('updated');
                setTimeout(() => element.classList.remove('updated'), 500);
            }
        }

        function updateProduktivitasTable(summaries) {
            const body = document.getElementById('produktivitasBody');
            if (!body) return;

            if (summaries.length > 0) {
                let html = `<div class="table-responsive"><table><thead><tr>
                    <th>Admin</th><th>Kunjungan</th><th>Jarak (km)</th><th>Success Rate</th>
                </tr></thead><tbody id="produktivitasTable">`;

                summaries.forEach(s => {
                    const rate = parseFloat(s.success_rate);
                    const rateClass = rate >= 80 ? 'text-success' : (rate >= 60 ? 'text-warning' : 'text-danger');
                    html += `<tr>
                        <td>${escapeHtml(s.nama_lengkap)}</td>
                        <td>${s.jumlah_kunjungan_valid} / ${s.jumlah_kunjungan}</td>
                        <td>${parseFloat(s.total_jarak_tempuh).toFixed(2)}</td>
                        <td><span class="${rateClass}">${rate.toFixed(1)}%</span></td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                body.innerHTML = html;
            } else {
                body.innerHTML = '<p class="text-center text-secondary">Belum ada data hari ini</p>';
            }
        }

        function updateRecentTable(reports) {
            const body = document.getElementById('recentBody');
            if (!body) return;

            if (reports.length > 0) {
                let html = `<div style="max-height: 400px; overflow-y: auto;">
                    <table style="font-size: 13px;"><thead><tr>
                    <th>Waktu</th><th>Admin</th><th>Project</th><th>Status</th>
                </tr></thead><tbody id="recentTable">`;

                reports.forEach(r => {
                    const timeClass = r.is_today ? 'text-success' : 'text-secondary';
                    const statusBadge = r.is_valid == 1
                        ? '<span class="badge badge-success">Valid</span>'
                        : '<span class="badge badge-danger">Invalid</span>';

                    html += `<tr>
                        <td><span class="${timeClass}">${r.waktu_format}</span></td>
                        <td>${escapeHtml(r.admin_name)}</td>
                        <td>${escapeHtml(r.nama_project)}</td>
                        <td>${statusBadge}</td>
                    </tr>`;
                });

                html += '</tbody></table></div>';
                body.innerHTML = html;
            } else {
                body.innerHTML = '<p class="text-center text-secondary">Belum ada laporan</p>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Start auto-refresh
        setInterval(updateDashboard, REFRESH_INTERVAL);

        // Also refresh on visibility change (when tab becomes active)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                updateDashboard();
            }
        });
    </script>
</body>
</html>
