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

// Get all project locations for search
$projectsQuery = "SELECT * FROM project_locations WHERE status = 'active' ORDER BY nama_project";
$projectsResult = executeQuery($projectsQuery);
$projects = [];
while ($project = $projectsResult['data']->fetch_assoc()) {
    $projects[] = $project;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - GPS Tracking</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../assets/js/tracking.js"></script>
</head>
<body>
    <?php include '../includes/page-loader.php'; ?>

    <!-- GPS Consent Modal -->
    <div class="gps-consent-modal" id="gpsConsentModal">
        <div class="gps-consent-content">
            <div class="gps-consent-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">
                    <circle cx="12" cy="10" r="3"/>
                    <path d="M12 2a8 8 0 0 0-8 8c0 5 8 12 8 12s8-7 8-12a8 8 0 0 0-8-8z"/>
                </svg>
            </div>
            <h3>Aktifkan Pelacakan GPS</h3>
            <p>Aplikasi ini memerlukan akses lokasi GPS Anda untuk:</p>
            <ul>
                <li>Melacak posisi saat kunjungan project</li>
                <li>Memvalidasi kehadiran di lokasi</li>
                <li>Menghitung jarak perjalanan</li>
            </ul>
            <p class="gps-consent-note">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                    <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                </svg>
                Data lokasi hanya digunakan untuk keperluan kerja
            </p>
            <div class="gps-consent-buttons">
                <button type="button" class="btn btn-primary" onclick="acceptGpsConsent()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Izinkan & Aktifkan GPS
                </button>
                <button type="button" class="btn btn-secondary" onclick="declineGpsConsent()">Nanti Saja</button>
            </div>
        </div>
    </div>

    <!-- GPS Status Floating Indicator -->
    <div class="gps-status-float" id="gpsStatusFloat" style="display: none;">
        <div class="gps-status-dot" id="gpsStatusDot"></div>
        <span id="gpsStatusText">GPS Aktif</span>
    </div>

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
            <a href="laporan.php">Buat Laporan</a>
            <a href="riwayat.php">Riwayat</a>
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
                    <a href="riwayat.php" class="profile-dropdown-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        Riwayat Laporan
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
                    <button type="button" class="btn btn-danger" id="btnStopTracking" onclick="toggleGpsTracking()" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;">
                            <rect x="6" y="6" width="12" height="12" rx="2"/>
                        </svg>
                        Stop Tracking GPS
                    </button>
                    <button type="button" class="btn btn-primary" id="btnStartTracking" onclick="toggleGpsTracking()" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 5px;">
                            <circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5 8 12 8 12s8-7 8-12a8 8 0 0 0-8-8z"/>
                        </svg>
                        Mulai Tracking GPS
                    </button>
                    <a href="laporan.php" class="btn btn-success">Buat Laporan Kunjungan</a>
                    <a href="riwayat.php" class="btn btn-secondary">Lihat Riwayat</a>
                </div>
            </div>
        </div>

        <!-- Search Project Location -->
        <div class="card">
            <div class="card-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                Cari Lokasi Project
            </div>
            <div class="card-body">
                <div style="margin-bottom: 15px;">
                    <input type="text" id="search-project" class="form-control" placeholder="Ketik nama project..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;">
                </div>
                <div id="project-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; display: none;">
                    <?php foreach ($projects as $project): ?>
                    <div class="project-item"
                         data-name="<?php echo htmlspecialchars(strtolower($project['nama_project'])); ?>"
                         data-lat="<?php echo $project['latitude']; ?>"
                         data-lng="<?php echo $project['longitude']; ?>"
                         data-radius="<?php echo $project['radius_valid']; ?>"
                         data-alamat="<?php echo htmlspecialchars($project['alamat']); ?>"
                         style="padding: 12px 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: background 0.2s;"
                         onmouseover="this.style.background='#f0f9ff'"
                         onmouseout="this.style.background='white'">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="font-weight: 600; color: #1e40af;"><?php echo htmlspecialchars($project['nama_project']); ?></div>
                            <div class="project-distance" style="font-size: 11px; color: #10b981; font-weight: 600; background: #ecfdf5; padding: 2px 8px; border-radius: 10px; display: none;">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 2px;">
                                    <circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5 8 12 8 12s8-7 8-12a8 8 0 0 0-8-8z"/>
                                </svg>
                                ± <span class="distance-value">-</span>
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 3px;"><?php echo htmlspecialchars($project['alamat']); ?></div>
                        <div style="font-size: 11px; color: #999; margin-top: 2px;">Radius: <?php echo $project['radius_valid']; ?>m</div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Selected Project Info -->
                <div id="selected-project" style="display: none; margin-top: 15px; padding: 15px; background: #f0f9ff; border-radius: 8px; border: 1px solid #bfdbfe;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div>
                            <div id="selected-name" style="font-weight: 600; font-size: 16px; color: #1e40af;"></div>
                            <div id="selected-alamat" style="font-size: 13px; color: #666; margin-top: 3px;"></div>
                            <div style="display: flex; gap: 15px; margin-top: 5px;">
                                <div id="selected-radius" style="font-size: 12px; color: #999;"></div>
                                <div id="selected-distance" style="font-size: 12px; color: #10b981; font-weight: 600; display: none;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 3px;">
                                        <circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5 8 12 8 12s8-7 8-12a8 8 0 0 0-8-8z"/>
                                    </svg>
                                    ± <span id="selected-distance-value">-</span>
                                </div>
                            </div>
                        </div>
                        <a href="#" onclick="openGoogleMaps(); return false;" style="display:inline-block;padding:8px 14px;background:#10b981;color:white;border-radius:6px;text-decoration:none;font-size:13px;font-weight:500;">
                            📍 Buka di Google Maps
                        </a>
                    </div>
                    <div id="preview-map" style="height: 200px; border-radius: 8px; overflow: hidden;"></div>
                </div>

                <?php if (empty($projects)): ?>
                <p class="text-center text-secondary" style="margin-top: 15px;">Tidak ada project tersedia</p>
                <?php endif; ?>
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

    <style>
        /* GPS Consent Modal */
        .gps-consent-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .gps-consent-modal.active {
            display: flex;
        }
        .gps-consent-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .gps-consent-icon {
            margin-bottom: 20px;
        }
        .gps-consent-content h3 {
            margin: 0 0 15px;
            color: #1f2937;
            font-size: 22px;
        }
        .gps-consent-content p {
            color: #6b7280;
            margin: 0 0 15px;
            font-size: 14px;
        }
        .gps-consent-content ul {
            text-align: left;
            margin: 0 0 20px;
            padding-left: 25px;
            color: #4b5563;
            font-size: 14px;
        }
        .gps-consent-content ul li {
            margin-bottom: 8px;
        }
        .gps-consent-note {
            background: #fef3c7;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px !important;
            color: #92400e !important;
        }
        .gps-consent-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 25px;
        }
        .gps-consent-buttons .btn {
            padding: 14px 20px;
            font-size: 15px;
        }

        /* GPS Status Floating Indicator */
        .gps-status-float {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 10px 16px;
            border-radius: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            z-index: 1000;
            cursor: pointer;
            transition: all 0.3s;
        }
        .gps-status-float:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.2);
        }
        .gps-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #22c55e;
            animation: gpsPulse 2s infinite;
        }
        .gps-status-dot.inactive {
            background: #ef4444;
            animation: none;
        }
        .gps-status-dot.searching {
            background: #f59e0b;
            animation: gpsPulse 1s infinite;
        }
        @keyframes gpsPulse {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
            50% { opacity: 0.8; box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
        }
    </style>

    <script>
        // GPS Consent and Auto-Tracking
        const GPS_CONSENT_KEY = 'gps_consent_<?php echo $user['id']; ?>';
        const GPS_CONSENT_DATE_KEY = 'gps_consent_date_<?php echo $user['id']; ?>';

        // Check if GPS consent was given today
        function hasGpsConsent() {
            const consent = sessionStorage.getItem(GPS_CONSENT_KEY);
            return consent === 'true';
        }

        // Show GPS consent modal
        function showGpsConsentModal() {
            document.getElementById('gpsConsentModal').classList.add('active');
        }

        // Accept GPS consent
        function acceptGpsConsent() {
            sessionStorage.setItem(GPS_CONSENT_KEY, 'true');
            document.getElementById('gpsConsentModal').classList.remove('active');
            startGpsTracking();
        }

        // Decline GPS consent
        function declineGpsConsent() {
            sessionStorage.setItem(GPS_CONSENT_KEY, 'declined');
            document.getElementById('gpsConsentModal').classList.remove('active');
            isTrackingActive = false;
            updateGpsStatusIndicator(false, 'GPS Nonaktif');
            updateTrackingButtons();
        }

        // Start GPS tracking
        function startGpsTracking() {
            updateGpsStatusIndicator('searching', 'Mencari GPS...');
            document.getElementById('gpsStatusFloat').style.display = 'flex';
            sessionStorage.setItem(GPS_CONSENT_KEY, 'true');

            if (typeof gpsTracker !== 'undefined') {
                gpsTracker.startTracking();

                // Update status when position is obtained
                setTimeout(function checkPosition() {
                    const pos = gpsTracker.getCurrentPosition();
                    if (pos) {
                        isTrackingActive = true;
                        updateGpsStatusIndicator(true, 'GPS Aktif');
                        updateTrackingButtons();
                        // Update user location for distance calculation
                        userLat = pos.latitude;
                        userLng = pos.longitude;
                        updateProjectDistances();
                    } else {
                        setTimeout(checkPosition, 1000);
                    }
                }, 1000);
            } else {
                // Fallback if tracking.js not loaded
                navigator.geolocation.watchPosition(
                    function(position) {
                        isTrackingActive = true;
                        updateGpsStatusIndicator(true, 'GPS Aktif');
                        updateTrackingButtons();
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        updateProjectDistances();

                        // Send position to server
                        sendPositionToServer(position.coords.latitude, position.coords.longitude, position.coords.accuracy);
                    },
                    function(error) {
                        isTrackingActive = false;
                        updateGpsStatusIndicator(false, 'GPS Error');
                        updateTrackingButtons();
                        console.error('GPS Error:', error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 5000
                    }
                );
            }
        }

        // Send position to server
        function sendPositionToServer(lat, lng, accuracy) {
            fetch('../api/update_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `latitude=${lat}&longitude=${lng}&accuracy=${accuracy}`
            }).catch(err => console.log('Error sending location:', err));
        }

        // Update GPS status indicator
        function updateGpsStatusIndicator(status, text) {
            const dot = document.getElementById('gpsStatusDot');
            const statusText = document.getElementById('gpsStatusText');
            const floatEl = document.getElementById('gpsStatusFloat');

            floatEl.style.display = 'flex';
            statusText.textContent = text;

            dot.classList.remove('inactive', 'searching');
            if (status === false) {
                dot.classList.add('inactive');
            } else if (status === 'searching') {
                dot.classList.add('searching');
            }
        }

        // Click on GPS status to toggle tracking
        document.getElementById('gpsStatusFloat').addEventListener('click', function() {
            toggleGpsTracking();
        });

        // Toggle GPS Tracking (Stop/Start)
        let isTrackingActive = false;

        function toggleGpsTracking() {
            if (isTrackingActive) {
                stopGpsTracking();
            } else {
                startGpsTracking();
            }
        }

        // Stop GPS tracking
        function stopGpsTracking() {
            if (typeof gpsTracker !== 'undefined') {
                gpsTracker.stopTracking();
            }
            isTrackingActive = false;
            sessionStorage.setItem(GPS_CONSENT_KEY, 'stopped');
            updateGpsStatusIndicator(false, 'GPS Dihentikan');
            updateTrackingButtons();
        }

        // Update button visibility
        function updateTrackingButtons() {
            const btnStop = document.getElementById('btnStopTracking');
            const btnStart = document.getElementById('btnStartTracking');

            if (isTrackingActive) {
                btnStop.style.display = 'inline-flex';
                btnStart.style.display = 'none';
            } else {
                btnStop.style.display = 'none';
                btnStart.style.display = 'inline-flex';
            }
        }

        // Check consent on page load
        document.addEventListener('DOMContentLoaded', function() {
            const consent = sessionStorage.getItem(GPS_CONSENT_KEY);

            if (consent === null) {
                // First time or new session - show consent modal
                setTimeout(showGpsConsentModal, 500);
                updateTrackingButtons();
            } else if (consent === 'true') {
                // Already consented - start tracking
                startGpsTracking();
            } else if (consent === 'stopped') {
                // Manually stopped - show start button
                isTrackingActive = false;
                updateGpsStatusIndicator(false, 'GPS Dihentikan');
                updateTrackingButtons();
            } else {
                // Declined - show inactive status
                isTrackingActive = false;
                updateGpsStatusIndicator(false, 'GPS Nonaktif');
                updateTrackingButtons();
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
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // User location
        let userLat = null;
        let userLng = null;

        // Haversine formula to calculate distance between two coordinates
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth's radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c; // Distance in meters
        }

        // Format distance for display
        function formatDistance(meters) {
            if (meters < 1000) {
                return Math.round(meters) + ' m';
            } else {
                return (meters / 1000).toFixed(1) + ' km';
            }
        }

        // Update all project distances
        function updateProjectDistances() {
            if (userLat === null || userLng === null) return;

            document.querySelectorAll('.project-item').forEach(item => {
                const projectLat = parseFloat(item.dataset.lat);
                const projectLng = parseFloat(item.dataset.lng);
                const distance = calculateDistance(userLat, userLng, projectLat, projectLng);

                const distanceEl = item.querySelector('.project-distance');
                const distanceValueEl = item.querySelector('.distance-value');

                if (distanceEl && distanceValueEl) {
                    distanceValueEl.textContent = formatDistance(distance);
                    distanceEl.style.display = 'inline-flex';
                    distanceEl.style.alignItems = 'center';
                }

                // Store distance for sorting
                item.dataset.distance = distance;
            });

            // Sort projects by distance (optional)
            sortProjectsByDistance();
        }

        // Sort projects by distance
        function sortProjectsByDistance() {
            const list = document.getElementById('project-list');
            const items = Array.from(list.querySelectorAll('.project-item'));

            items.sort((a, b) => {
                const distA = parseFloat(a.dataset.distance) || Infinity;
                const distB = parseFloat(b.dataset.distance) || Infinity;
                return distA - distB;
            });

            items.forEach(item => list.appendChild(item));
        }

        // Get user location
        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        console.log('User location:', userLat, userLng);
                        updateProjectDistances();
                    },
                    function(error) {
                        console.log('Geolocation error:', error.message);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            }
        }

        // Get location on page load
        getUserLocation();

        // Search project functionality
        const searchInput = document.getElementById('search-project');
        const projectList = document.getElementById('project-list');
        const projectItems = document.querySelectorAll('.project-item');
        const selectedProject = document.getElementById('selected-project');

        let previewMap = null;
        let previewMarker = null;
        let previewCircle = null;
        let selectedLat = null;
        let selectedLng = null;

        // Show list when typing
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();

            if (query.length > 0) {
                projectList.style.display = 'block';
                let hasResults = false;

                projectItems.forEach(item => {
                    const name = item.dataset.name;
                    if (name.includes(query)) {
                        item.style.display = 'block';
                        hasResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (!hasResults) {
                    projectList.innerHTML = '<div style="padding: 15px; text-align: center; color: #999;">Tidak ditemukan</div>';
                }
            } else {
                projectList.style.display = 'none';
                projectItems.forEach(item => item.style.display = 'block');
            }
        });

        // Focus show all
        searchInput.addEventListener('focus', function() {
            if (this.value.length === 0) {
                projectList.style.display = 'block';
            }
        });

        // Click outside to close
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#search-project') && !e.target.closest('#project-list')) {
                projectList.style.display = 'none';
            }
        });

        // Select project
        projectItems.forEach(item => {
            item.addEventListener('click', function() {
                const name = this.querySelector('div:first-child').textContent.trim();
                const alamat = this.dataset.alamat;
                const radius = this.dataset.radius;
                selectedLat = parseFloat(this.dataset.lat);
                selectedLng = parseFloat(this.dataset.lng);

                // Update input
                searchInput.value = name;
                projectList.style.display = 'none';

                // Show selected info
                document.getElementById('selected-name').textContent = name;
                document.getElementById('selected-alamat').textContent = alamat;
                document.getElementById('selected-radius').textContent = 'Radius: ' + radius + 'm';

                // Show distance if available
                if (userLat !== null && userLng !== null) {
                    const distance = calculateDistance(userLat, userLng, selectedLat, selectedLng);
                    document.getElementById('selected-distance-value').textContent = formatDistance(distance);
                    document.getElementById('selected-distance').style.display = 'inline-flex';
                }

                selectedProject.style.display = 'block';

                // Initialize or update map
                setTimeout(() => {
                    if (!previewMap) {
                        previewMap = L.map('preview-map').setView([selectedLat, selectedLng], 17);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap'
                        }).addTo(previewMap);
                    } else {
                        previewMap.setView([selectedLat, selectedLng], 17);
                    }

                    // Remove old marker and circle
                    if (previewMarker) previewMap.removeLayer(previewMarker);
                    if (previewCircle) previewMap.removeLayer(previewCircle);

                    // Add new marker
                    previewMarker = L.marker([selectedLat, selectedLng], {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    }).addTo(previewMap);

                    previewMarker.bindPopup(`<b>${name}</b><br>${alamat}`).openPopup();

                    // Add radius circle
                    previewCircle = L.circle([selectedLat, selectedLng], {
                        radius: parseInt(radius),
                        color: '#ef4444',
                        fillColor: '#ef4444',
                        fillOpacity: 0.15
                    }).addTo(previewMap);

                    // Invalidate size to fix rendering
                    previewMap.invalidateSize();
                }, 100);
            });
        });

        // Open Google Maps
        function openGoogleMaps() {
            if (selectedLat && selectedLng) {
                window.open(`https://www.google.com/maps?q=${selectedLat},${selectedLng}`, '_blank');
            }
        }
    </script>
</body>
</html>
