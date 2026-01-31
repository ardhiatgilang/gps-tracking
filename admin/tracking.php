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
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">GPS Tracking System</div>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="tracking.php" class="active">Tracking GPS</a>
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
