<?php
/**
 * Halaman Monitoring Realtime Supervisor
 * Menampilkan posisi realtime semua admin lapangan pada peta
 */

require_once '../config/database.php';
checkRole('supervisor');

$user = getCurrentUser();

// Get all project locations untuk ditampilkan di peta
$projectsQuery = "SELECT * FROM project_locations WHERE status = 'active'";
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
    <title>Monitoring Realtime - Supervisor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">GPS Tracking System - Supervisor</div>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="monitoring.php" class="active">Monitoring Realtime</a>
            <a href="laporan.php">Laporan</a>
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
        <h2 class="mb-3">Monitoring Realtime Admin Lapangan</h2>

        <!-- Status Info -->
        <div class="alert alert-info">
            <strong>Monitoring Aktif</strong> - Data diperbarui otomatis setiap 30 detik. Admin dengan update terakhir &gt; 5 menit akan ditandai offline.
        </div>

        <!-- Admin Status List -->
        <div class="card mb-3">
            <div class="card-header">Status Admin Lapangan</div>
            <div class="card-body">
                <div id="admin-status-list">
                    <div class="text-center">
                        <div class="spinner"></div>
                        <p>Memuat data...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Map -->
        <div class="card">
            <div class="card-header">Peta Tracking Realtime</div>
            <div class="card-body" style="padding: 0;">
                <div class="map-container" style="height: 600px;">
                    <div id="map"></div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="card mt-3">
            <div class="card-header">Keterangan</div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div>
                        <strong>Marker Biru:</strong> Posisi Admin Lapangan (Online)
                    </div>
                    <div>
                        <strong>Marker Abu-abu:</strong> Posisi Admin Lapangan (Offline)
                    </div>
                    <div>
                        <strong>Marker Merah + Lingkaran:</strong> Lokasi Project + Radius Valid
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Initialize map
        let map = L.map('map').setView([-6.2088, 106.8456], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let adminMarkers = {};
        let projectMarkers = [];

        // Add project locations to map
        const projects = <?php echo json_encode($projects); ?>;

        projects.forEach(project => {
            const lat = parseFloat(project.latitude);
            const lng = parseFloat(project.longitude);
            const radius = parseInt(project.radius_valid);

            // Add marker
            const marker = L.marker([lat, lng], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map);

            marker.bindPopup(`<b>${project.nama_project}</b><br>${project.alamat}<br>Radius: ${radius}m`);

            // Add radius circle
            L.circle([lat, lng], {
                radius: radius,
                color: '#ef4444',
                fillColor: '#ef4444',
                fillOpacity: 0.1
            }).addTo(map);

            projectMarkers.push(marker);
        });

        // Function to update admin positions
        function updateAdminPositions() {
            fetch('../api/get_tracking_data.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        updateMap(result.data);
                        updateStatusList(result.data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching tracking data:', error);
                });
        }

        function updateMap(admins) {
            // Remove existing admin markers
            Object.values(adminMarkers).forEach(marker => {
                map.removeLayer(marker);
            });
            adminMarkers = {};

            // Add new markers
            admins.forEach(admin => {
                if (!admin.latitude || !admin.longitude) return;

                const position = [admin.latitude, admin.longitude];
                const isOnline = admin.is_online;

                const marker = L.marker(position, {
                    icon: L.icon({
                        iconUrl: isOnline
                            ? 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png'
                            : 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);

                const popupContent = `
                    <b>${admin.nama}</b><br>
                    Status: ${isOnline ? '<span style="color: green;">Online</span>' : '<span style="color: gray;">Offline</span>'}<br>
                    Akurasi: ${admin.accuracy.toFixed(2)}m<br>
                    Update: ${admin.minutes_ago} menit lalu<br>
                    Signal: ${admin.signal_strength}
                `;

                marker.bindPopup(popupContent);

                // Add accuracy circle
                const accColor = admin.accuracy <= 20 ? '#10b981' : (admin.accuracy <= 50 ? '#f59e0b' : '#ef4444');
                L.circle(position, {
                    radius: admin.accuracy,
                    color: accColor,
                    fillColor: accColor,
                    fillOpacity: 0.2,
                    weight: 1
                }).addTo(map);

                adminMarkers[admin.id] = marker;
            });
        }

        function updateStatusList(admins) {
            const container = document.getElementById('admin-status-list');

            if (admins.length === 0) {
                container.innerHTML = '<p class="text-center text-secondary">Tidak ada admin yang sedang tracking</p>';
                return;
            }

            let html = '<div class="table-responsive"><table><thead><tr>';
            html += '<th>Nama</th><th>Status</th><th>Lokasi</th><th>Akurasi GPS</th><th>Update Terakhir</th>';
            html += '</tr></thead><tbody>';

            admins.forEach(admin => {
                const statusClass = admin.is_online ? 'badge-success' : 'badge-secondary';
                const statusText = admin.is_online ? 'Online' : 'Offline';
                const accClass = admin.accuracy <= 20 ? 'text-success' : (admin.accuracy <= 50 ? 'text-warning' : 'text-danger');

                html += `<tr>
                    <td>${admin.nama}</td>
                    <td><span class="badge ${statusClass}">${statusText}</span></td>
                    <td><small>${admin.latitude.toFixed(6)}, ${admin.longitude.toFixed(6)}</small></td>
                    <td><span class="${accClass}">${admin.accuracy.toFixed(2)} m</span></td>
                    <td>${admin.minutes_ago} menit lalu</td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        // Initial load
        updateAdminPositions();

        // Update every 30 seconds
        setInterval(updateAdminPositions, 30000);
    </script>
</body>
</html>
