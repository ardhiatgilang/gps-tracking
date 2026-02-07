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
            <a href="monitoring.php" class="active">Monitoring Realtime</a>
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
        <h2 class="mb-3">Monitoring Realtime Admin Lapangan</h2>

        <!-- Status Info -->
        <div class="alert alert-info">
            <strong>Monitoring Aktif</strong> - Data diperbarui otomatis setiap 30 detik. Klik nama admin untuk melihat lokasi di peta.
        </div>

        <style>
            #admin-status-list tr:hover {
                background-color: #e0f2fe !important;
                transition: background-color 0.2s ease;
            }
            #admin-status-list tr {
                cursor: pointer;
            }
        </style>

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
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div>
                        <strong>Marker Biru:</strong> Posisi Admin Lapangan (Online)
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
        let adminCircles = [];
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

            marker.bindPopup(`
                <b>${project.nama_project}</b><br>
                ${project.alamat}<br>
                Radius: ${radius}m<br>
                <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" style="display:inline-block;margin-top:8px;padding:5px 10px;background:#10b981;color:white;border-radius:4px;text-decoration:none;font-size:12px;">
                    📍 Buka di Google Maps
                </a>
            `);

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

            // Remove existing accuracy circles
            adminCircles.forEach(circle => {
                map.removeLayer(circle);
            });
            adminCircles = [];

            // Filter only online admins
            const onlineAdmins = admins.filter(admin => admin.is_online);

            // Add new markers (only online admins)
            onlineAdmins.forEach(admin => {
                if (!admin.latitude || !admin.longitude) return;

                const position = [admin.latitude, admin.longitude];

                const marker = L.marker(position, {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);

                const popupContent = `
                    <b>${admin.nama}</b><br>
                    Status: <span style="color: green; font-weight: bold;">Online</span><br>
                    Akurasi: ${admin.accuracy.toFixed(2)}m<br>
                    Update: ${admin.minutes_ago} menit lalu<br>
                    Signal: ${admin.signal_strength}<br>
                    <a href="https://www.google.com/maps?q=${admin.latitude},${admin.longitude}" target="_blank" style="display:inline-block;margin-top:8px;padding:5px 10px;background:#10b981;color:white;border-radius:4px;text-decoration:none;font-size:12px;">
                        📍 Buka di Google Maps
                    </a>
                `;

                marker.bindPopup(popupContent);

                // Add accuracy circle
                const accColor = admin.accuracy <= 20 ? '#10b981' : (admin.accuracy <= 50 ? '#f59e0b' : '#ef4444');
                const accCircle = L.circle(position, {
                    radius: admin.accuracy,
                    color: accColor,
                    fillColor: accColor,
                    fillOpacity: 0.2,
                    weight: 1
                }).addTo(map);

                adminCircles.push(accCircle);
                adminMarkers[admin.id] = marker;
            });
        }

        function updateStatusList(admins) {
            const container = document.getElementById('admin-status-list');

            // Filter only online admins
            const onlineAdmins = admins.filter(a => a.is_online);

            if (onlineAdmins.length === 0) {
                container.innerHTML = '<p class="text-center text-secondary">Tidak ada admin yang sedang online</p>';
                return;
            }

            let html = '<div class="table-responsive"><table><thead><tr>';
            html += '<th>Nama</th><th>Status</th><th>Lokasi</th><th>Akurasi GPS</th><th>Update Terakhir</th><th>Aksi</th>';
            html += '</tr></thead><tbody>';

            onlineAdmins.forEach(admin => {
                const accClass = admin.accuracy <= 20 ? 'text-success' : (admin.accuracy <= 50 ? 'text-warning' : 'text-danger');

                html += `<tr style="cursor: pointer;" onclick="focusOnAdmin(${admin.id}, ${admin.latitude}, ${admin.longitude})">
                    <td>${admin.nama}</td>
                    <td><span class="badge badge-success">Online</span></td>
                    <td><small>${admin.latitude.toFixed(6)}, ${admin.longitude.toFixed(6)}</small></td>
                    <td><span class="${accClass}">${admin.accuracy.toFixed(2)} m</span></td>
                    <td>${admin.minutes_ago} menit lalu</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); openGoogleMaps(${admin.latitude}, ${admin.longitude}, '${admin.nama}')" title="Buka di Google Maps">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 1.74.5 3.37 1.41 4.84.95 1.54 2.2 2.86 3.16 4.4.47.75.81 1.45 1.17 2.26.26.55.47 1.5 1.26 1.5s1-.95 1.26-1.5c.37-.81.7-1.51 1.17-2.26.96-1.53 2.21-2.85 3.16-4.4C18.5 12.37 19 10.74 19 9c0-3.87-3.13-7-7-7zm0 9.75c-1.52 0-2.75-1.23-2.75-2.75S10.48 6.25 12 6.25 14.75 7.48 14.75 9 13.52 11.75 12 11.75z"/></svg>
                        </button>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        // Function to open Google Maps in new tab
        function openGoogleMaps(lat, lng, name) {
            const url = `https://www.google.com/maps?q=${lat},${lng}`;
            window.open(url, '_blank');
        }

        // Function to focus map on specific admin location
        function focusOnAdmin(adminId, lat, lng) {
            // Zoom and pan to admin location
            map.setView([lat, lng], 18, {
                animate: true,
                duration: 0.5
            });

            // Open the popup for this admin
            if (adminMarkers[adminId]) {
                adminMarkers[adminId].openPopup();
            }

            // Scroll to map smoothly
            document.getElementById('map').scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        // Initial load
        updateAdminPositions();

        // Update every 30 seconds
        setInterval(updateAdminPositions, 30000);

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
