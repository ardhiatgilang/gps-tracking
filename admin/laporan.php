<?php
/**
 * Halaman Submit Laporan Kunjungan
 * Admin lapangan membuat laporan dengan foto dan validasi Haversine
 */

require_once '../config/database.php';
checkRole('admin');

$user = getCurrentUser();

// Get all active projects
$projectsQuery = "SELECT * FROM project_locations WHERE status = 'active' ORDER BY nama_project";
$projectsResult = executeQuery($projectsQuery);

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_laporan'])) {
    // Form validation happens in JavaScript and API
    // This is just a fallback
    $error = 'Gunakan form di bawah untuk submit laporan.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan - Admin Lapangan</title>
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
            <a href="tracking.php">Tracking GPS</a>
            <a href="laporan.php" class="active">Buat Laporan</a>
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
        <h2 class="mb-3">Buat Laporan Kunjungan</h2>

        <div id="alert-container"></div>

        <!-- GPS Status -->
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
            </div>

            <button id="btn-get-location" class="btn btn-primary mt-2">Dapatkan Lokasi Saya</button>
        </div>

        <!-- Report Form -->
        <div class="card">
            <div class="card-header">Form Laporan Kunjungan</div>
            <div class="card-body">
                <form id="form-laporan" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="project_id">Pilih Project <span class="text-danger">*</span></label>
                        <select class="form-control" id="project_id" name="project_id" required>
                            <option value="">-- Pilih Project --</option>
                            <?php while ($project = $projectsResult['data']->fetch_assoc()): ?>
                                <option value="<?php echo $project['id']; ?>"
                                        data-lat="<?php echo $project['latitude']; ?>"
                                        data-lng="<?php echo $project['longitude']; ?>"
                                        data-radius="<?php echo $project['radius_valid']; ?>">
                                    <?php echo htmlspecialchars($project['nama_project']); ?>
                                    (<?php echo htmlspecialchars($project['alamat']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="foto">Upload Foto <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="foto" name="foto"
                               accept="image/jpeg,image/jpg,image/png" required>
                        <small class="text-secondary">Format: JPG, PNG. Maksimal 5MB. Foto diambil dari kamera untuk EXIF data.</small>
                    </div>

                    <div class="form-group">
                        <label for="catatan">Catatan Kunjungan</label>
                        <textarea class="form-control" id="catatan" name="catatan"
                                  rows="4" placeholder="Catatan tambahan tentang kunjungan..."></textarea>
                    </div>

                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    <input type="hidden" id="accuracy" name="accuracy">
                    <input type="hidden" id="waktu_kunjungan" name="waktu_kunjungan">

                    <button type="submit" class="btn btn-success" id="btn-submit">Submit Laporan</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Batal</button>
                </form>
            </div>
        </div>

        <!-- Map Preview -->
        <div class="card">
            <div class="card-header">Peta Lokasi</div>
            <div class="card-body" style="padding: 0;">
                <div class="map-container">
                    <div id="map"></div>
                </div>
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
        let projectMarker = null;
        let projectCircle = null;

        // Get location button
        document.getElementById('btn-get-location').addEventListener('click', function() {
            if (!gpsTracker.isTracking) {
                gpsTracker.startTracking();
            }

            // Wait for position
            setTimeout(() => {
                const pos = gpsTracker.getCurrentPosition();
                if (pos) {
                    updateMapWithCurrentPosition();
                    showAlert('Lokasi berhasil didapatkan!', 'success');
                } else {
                    showAlert('Gagal mendapatkan lokasi. Pastikan GPS aktif.', 'danger');
                }
            }, 2000);
        });

        // Update map when position changes
        window.updateMapPosition = function(lat, lng) {
            updateMapWithCurrentPosition();
        };

        function updateMapWithCurrentPosition() {
            const pos = gpsTracker.getCurrentPosition();
            if (!pos) return;

            const position = [pos.latitude, pos.longitude];

            // Remove existing marker
            if (currentMarker) {
                map.removeLayer(currentMarker);
            }

            // Add current position marker
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

            currentMarker.bindPopup(`<b>Lokasi Anda</b><br>Akurasi: ${pos.accuracy.toFixed(2)} m`);

            map.setView(position, 16);
        }

        // When project is selected, show on map
        document.getElementById('project_id').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];

            if (!option.value) return;

            const lat = parseFloat(option.dataset.lat);
            const lng = parseFloat(option.dataset.lng);
            const radius = parseInt(option.dataset.radius);

            // Remove existing project marker and circle
            if (projectMarker) map.removeLayer(projectMarker);
            if (projectCircle) map.removeLayer(projectCircle);

            // Add project marker
            projectMarker = L.marker([lat, lng], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map);

            projectMarker.bindPopup(`<b>${option.text}</b><br>Radius Valid: ${radius}m`).openPopup();

            // Add radius circle
            projectCircle = L.circle([lat, lng], {
                radius: radius,
                color: '#ef4444',
                fillColor: '#ef4444',
                fillOpacity: 0.1
            }).addTo(map);

            map.setView([lat, lng], 16);
        });

        // Form submission
        document.getElementById('form-laporan').addEventListener('submit', function(e) {
            e.preventDefault();

            const pos = gpsTracker.getCurrentPosition();
            if (!pos) {
                showAlert('Mohon dapatkan lokasi Anda terlebih dahulu!', 'danger');
                return;
            }

            if (!gpsTracker.isAccuracyGood()) {
                if (!confirm('Akurasi GPS Anda kurang baik (' + pos.accuracy.toFixed(2) + 'm). Lanjutkan submit?')) {
                    return;
                }
            }

            // Set hidden fields
            document.getElementById('latitude').value = pos.latitude;
            document.getElementById('longitude').value = pos.longitude;
            document.getElementById('accuracy').value = pos.accuracy;
            document.getElementById('waktu_kunjungan').value = new Date().toISOString().slice(0, 19).replace('T', ' ');

            // Create FormData
            const formData = new FormData(this);

            // Disable submit button
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Mengirim...';

            // Send via AJAX
            fetch('../api/submit_report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    showAlert('Error: ' + result.message, 'danger');
                    btnSubmit.disabled = false;
                    btnSubmit.textContent = 'Submit Laporan';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Terjadi kesalahan jaringan', 'danger');
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Submit Laporan';
            });
        });

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;

            const container = document.getElementById('alert-container');
            container.innerHTML = '';
            container.appendChild(alertDiv);

            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
