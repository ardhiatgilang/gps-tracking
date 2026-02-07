<?php
/**
 * Halaman Tracking GPS untuk Admin Lapangan
 * Menampilkan posisi realtime dan mengaktifkan GPS tracking
 */

require_once '../config/database.php';
checkRole('admin');

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking GPS - Admin Lapangan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        <h2 class="mb-3">GPS Tracking Realtime</h2>

        <!-- GPS Control Panel -->
        <div class="gps-info">
            <div class="gps-status">
                <span id="status-indicator" class="status-indicator inactive"></span>
                <strong id="status-text">GPS Nonaktif</strong>
            </div>

            <div class="gps-details">
                <div class="gps-detail-item">
                    <span class="gps-detail-label">Latitude:</span>
                    <span id="current-latitude" class="gps-detail-value">-</span>
                </div>
                <div class="gps-detail-item">
                    <span class="gps-detail-label">Longitude:</span>
                    <span id="current-longitude" class="gps-detail-value">-</span>
                </div>
                <div class="gps-detail-item">
                    <span class="gps-detail-label">Akurasi:</span>
                    <span id="current-accuracy" class="gps-detail-value">-</span>
                </div>
                <div class="gps-detail-item">
                    <span class="gps-detail-label">Terakhir Sync:</span>
                    <span id="last-sync" class="gps-detail-value">-</span>
                </div>
            </div>

            <div style="margin-top: 15px;">
                <button id="btn-start-tracking" class="btn btn-success">Mulai Tracking</button>
                <button id="btn-stop-tracking" class="btn btn-danger" style="display: none;">Stop Tracking</button>
            </div>

            <div class="alert alert-info mt-2">
                <strong>Petunjuk:</strong> Klik "Mulai Tracking" untuk mengaktifkan GPS. Posisi Anda akan dikirim ke server setiap 30 detik. Pastikan GPS dan izin lokasi sudah aktif.
            </div>
        </div>

        <!-- Map -->
        <div class="card">
            <div class="card-header">Peta Lokasi Anda</div>
            <div class="card-body" style="padding: 0;">
                <div class="map-container">
                    <div id="map"></div>
                </div>
            </div>
        </div>

        <!-- Accuracy Info -->
        <div class="card">
            <div class="card-header">Informasi Akurasi GPS</div>
            <div class="card-body">
                <p><strong>Kategori Akurasi:</strong></p>
                <ul>
                    <li><span class="text-success">Baik (≤ 20m)</span> - Cocok untuk validasi kunjungan</li>
                    <li><span class="text-warning">Sedang (21-50m)</span> - Cukup akurat, perlu kehati-hatian</li>
                    <li><span class="text-danger">Buruk (> 50m)</span> - Tidak disarankan untuk submit laporan</li>
                </ul>
                <p class="mt-2"><strong>Tips Meningkatkan Akurasi:</strong></p>
                <ul>
                    <li>Pastikan berada di area terbuka (outdoor)</li>
                    <li>Hindari gedung tinggi yang menghalangi sinyal GPS</li>
                    <li>Tunggu beberapa saat hingga GPS stabil</li>
                    <li>Gunakan koneksi internet yang stabil</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/tracking.js"></script>
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

        // Initialize map
        let map = L.map('map').setView([-6.2088, 106.8456], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let currentMarker = null;
        let accuracyCircle = null;

        // Function to update map position (called from tracking.js)
        window.updateMapPosition = function(lat, lng) {
            const position = [lat, lng];

            // Remove existing marker and circle
            if (currentMarker) {
                map.removeLayer(currentMarker);
            }
            if (accuracyCircle) {
                map.removeLayer(accuracyCircle);
            }

            // Add new marker
            currentMarker = L.marker(position, {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map);

            currentMarker.bindPopup(`<b>Lokasi Anda</b><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();

            // Add accuracy circle
            const accuracy = gpsTracker.accuracy || 50;
            accuracyCircle = L.circle(position, {
                radius: accuracy,
                color: accuracy <= 20 ? '#10b981' : (accuracy <= 50 ? '#f59e0b' : '#ef4444'),
                fillColor: accuracy <= 20 ? '#10b981' : (accuracy <= 50 ? '#f59e0b' : '#ef4444'),
                fillOpacity: 0.2
            }).addTo(map);

            // Center map
            map.setView(position, 16);
        };

        // Auto-start tracking when page loads (optional)
        // gpsTracker.startTracking();
    </script>
</body>
</html>
