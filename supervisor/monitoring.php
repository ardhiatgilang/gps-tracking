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

// Get office (central point) location
$office = getOfficeLocation();
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

            /* Custom Photo Marker */
            .photo-marker {
                position: relative;
                width: 46px;
                height: 56px;
            }
            .photo-marker-pin {
                width: 46px;
                height: 56px;
                position: relative;
            }
            .photo-marker-pin svg {
                width: 46px;
                height: 56px;
                filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
            }
            .photo-marker-img {
                position: absolute;
                top: 4px;
                left: 50%;
                transform: translateX(-50%);
                width: 34px;
                height: 34px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid white;
            }
            .photo-marker-initials {
                position: absolute;
                top: 4px;
                left: 50%;
                transform: translateX(-50%);
                width: 34px;
                height: 34px;
                border-radius: 50%;
                background: #3b82f6;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 13px;
                font-weight: 700;
                border: 2px solid white;
            }
            .photo-marker-pulse {
                position: absolute;
                bottom: -2px;
                left: 50%;
                transform: translateX(-50%);
                width: 10px;
                height: 10px;
                background: #22c55e;
                border-radius: 50%;
                border: 2px solid white;
                animation: markerPulse 2s infinite;
            }
            @keyframes markerPulse {
                0%, 100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
                50% { box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
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
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="position:relative;width:30px;height:36px;flex-shrink:0;">
                            <svg viewBox="0 0 46 56" width="30" height="36"><path d="M23 0 C10.3 0 0 10.3 0 23 C0 35.7 23 56 23 56 C23 56 46 35.7 46 23 C46 10.3 35.7 0 23 0 Z" fill="#3b82f6"/><circle cx="23" cy="21" r="16" fill="white"/></svg>
                        </div>
                        <span><strong>Foto Profil Admin:</strong> Posisi Admin (Online)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png" style="height:28px;flex-shrink:0;">
                        <span><strong>Marker Merah + Lingkaran:</strong> Lokasi Project + Radius Valid</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png" style="height:28px;flex-shrink:0;">
                        <span><strong>Marker Hijau + Lingkaran:</strong> Kantor Pusat (Central Point) + Radius Valid</span>
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

        // Supervisor location
        let myLat = null;
        let myLng = null;

        // Get supervisor's current location
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(
                function(pos) {
                    myLat = pos.coords.latitude;
                    myLng = pos.coords.longitude;
                },
                function(err) { console.log('Geolocation error:', err.message); },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
            );
        }

        // Haversine formula - distance in meters
        function haversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // Road factor: jalan nyata biasanya 1.5x lebih jauh dari garis lurus (koreksi Haversine untuk Pulau Jawa)
        const ROAD_FACTOR = 1.5;

        // Format distance (sudah dikoreksi dengan road factor)
        function formatDistance(meters) {
            const roadMeters = meters * ROAD_FACTOR;
            if (roadMeters < 1000) return Math.round(roadMeters) + ' m';
            return (roadMeters / 1000).toFixed(1) + ' km';
        }

        // Estimate travel time with road distance correction
        function estimateTime(meters) {
            const roadDistance = meters * ROAD_FACTOR;

            // Kecepatan rata-rata berdasarkan jarak (kota Jakarta)
            const distKm = roadDistance / 1000;
            let avgSpeedKmh;
            if (distKm < 5) avgSpeedKmh = 20;
            else if (distKm < 15) avgSpeedKmh = 25;
            else avgSpeedKmh = 30;

            const minutes = distKm / avgSpeedKmh * 60;
            if (minutes < 1) return '< 1 menit';
            if (minutes < 60) return Math.round(minutes) + ' menit';
            const hours = Math.floor(minutes / 60);
            const mins = Math.round(minutes % 60);
            return hours + ' jam ' + mins + ' menit';
        }

        // Get distance info HTML between supervisor and admin
        function getDistanceInfo(adminLat, adminLng) {
            if (myLat === null || myLng === null) return { html: '', distance: null, time: '' };
            const dist = haversineDistance(myLat, myLng, adminLat, adminLng);
            const time = estimateTime(dist);
            return {
                html: `<div style="margin-top:6px;padding:6px 8px;background:#f0fdf4;border-radius:6px;border:1px solid #bbf7d0;clear:both;">
                    <span style="font-size:12px;color:#15803d;">
                        <strong>Jarak dari Anda:</strong> ${formatDistance(dist)}<br>
                        <strong>Estimasi waktu:</strong> ~${time}
                    </span>
                </div>`,
                distance: dist,
                time: time,
                formatted: formatDistance(dist)
            };
        }

        // Add office (central point) location to map
        const office = <?php echo json_encode($office); ?>;

        if (office) {
            const officeLat = parseFloat(office.latitude);
            const officeLng = parseFloat(office.longitude);
            const officeRadius = parseInt(office.radius_valid);

            const officeMarker = L.marker([officeLat, officeLng], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map);

            officeMarker.bindPopup(`
                <b>${office.nama_kantor}</b><br>
                ${office.alamat || ''}<br>
                Radius: ${officeRadius}m<br>
                <a href="https://www.google.com/maps?q=${officeLat},${officeLng}" target="_blank" style="display:inline-block;margin-top:8px;padding:5px 10px;background:#10b981;color:white;border-radius:4px;text-decoration:none;font-size:12px;">
                    📍 Buka di Google Maps
                </a>
            `);

            L.circle([officeLat, officeLng], {
                radius: officeRadius,
                color: '#10b981',
                fillColor: '#10b981',
                fillOpacity: 0.1
            }).addTo(map);
        }

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

        // Create photo marker icon
        function createPhotoIcon(admin) {
            // Get initials
            const nameParts = admin.nama.split(' ');
            let initials = nameParts[0].charAt(0).toUpperCase();
            if (nameParts.length > 1) {
                initials += nameParts[nameParts.length - 1].charAt(0).toUpperCase();
            }

            // Photo or initials
            let innerHtml = '';
            if (admin.foto_profil) {
                innerHtml = `<img class="photo-marker-img" src="../uploads/profile/${admin.foto_profil}" alt="${admin.nama}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                             <div class="photo-marker-initials" style="display:none;">${initials}</div>`;
            } else {
                innerHtml = `<div class="photo-marker-initials">${initials}</div>`;
            }

            const html = `
                <div class="photo-marker">
                    <div class="photo-marker-pin">
                        <svg viewBox="0 0 46 56" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23 0 C10.3 0 0 10.3 0 23 C0 35.7 23 56 23 56 C23 56 46 35.7 46 23 C46 10.3 35.7 0 23 0 Z" fill="#3b82f6"/>
                            <circle cx="23" cy="21" r="19" fill="white"/>
                        </svg>
                        ${innerHtml}
                    </div>
                    <div class="photo-marker-pulse"></div>
                </div>
            `;

            return L.divIcon({
                html: html,
                className: '',
                iconSize: [46, 56],
                iconAnchor: [23, 56],
                popupAnchor: [0, -50]
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
                    icon: createPhotoIcon(admin)
                }).addTo(map);

                const fotoPopup = admin.foto_profil
                    ? `<img src="../uploads/profile/${admin.foto_profil}" style="width:45px;height:45px;border-radius:50%;object-fit:cover;border:2px solid #3b82f6;margin-right:10px;float:left;">`
                    : '';

                const distInfo = getDistanceInfo(admin.latitude, admin.longitude);

                let kantorHtml = '';
                if (admin.jarak_dari_kantor !== null && admin.jarak_dari_kantor !== undefined) {
                    const kantorLabel = admin.jarak_dari_kantor >= 1000
                        ? (admin.jarak_dari_kantor / 1000).toFixed(2) + ' km'
                        : admin.jarak_dari_kantor.toFixed(2) + ' m';
                    const kantorColor = admin.dalam_radius_kantor ? '#15803d' : '#334155';
                    kantorHtml = `<div style="margin-top:6px;padding:6px 8px;background:#f0f9ff;border-radius:6px;border:1px solid #bae6fd;clear:both;">
                        <span style="font-size:12px;color:${kantorColor};">
                            <strong>Jarak dari Kantor:</strong> ${kantorLabel}
                            ${admin.dalam_radius_kantor ? ' <span class="badge badge-success" style="font-size:10px;">Di Kantor</span>' : ''}
                        </span>
                    </div>`;
                }

                const popupContent = `
                    <div style="min-width: 220px;">
                        ${fotoPopup}
                        <b>${admin.nama}</b><br>
                        Status: <span style="color: green; font-weight: bold;">Online</span><br>
                        Akurasi: ${admin.accuracy.toFixed(2)}m<br>
                        Update: ${admin.minutes_ago} menit lalu<br>
                        Signal: ${admin.signal_strength}<br style="clear:both;">
                        ${kantorHtml}
                        ${distInfo.html}
                        <a href="https://www.google.com/maps/dir/?api=1&destination=${admin.latitude},${admin.longitude}" target="_blank" style="display:inline-block;margin-top:8px;padding:6px 12px;background:#10b981;color:white;border-radius:4px;text-decoration:none;font-size:12px;clear:both;width:100%;text-align:center;box-sizing:border-box;">
                            Navigasi ke Lokasi
                        </a>
                    </div>
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
            html += '<th>Nama</th><th>Status</th><th>Jarak & Estimasi</th><th>Jarak dari Kantor</th><th>Akurasi GPS</th><th>Update Terakhir</th><th>Aksi</th>';
            html += '</tr></thead><tbody>';

            onlineAdmins.forEach(admin => {
                const accClass = admin.accuracy <= 20 ? 'text-success' : (admin.accuracy <= 50 ? 'text-warning' : 'text-danger');

                let jarakKantorHtml = '<small style="color:#999;">-</small>';
                if (admin.jarak_dari_kantor !== null && admin.jarak_dari_kantor !== undefined) {
                    const jarakClass = admin.dalam_radius_kantor ? 'text-success' : 'text-secondary';
                    const jarakLabel = admin.jarak_dari_kantor >= 1000
                        ? (admin.jarak_dari_kantor / 1000).toFixed(2) + ' km'
                        : admin.jarak_dari_kantor.toFixed(2) + ' m';
                    jarakKantorHtml = `<strong class="${jarakClass}">${jarakLabel}</strong>` +
                        (admin.dalam_radius_kantor ? '<br><span class="badge badge-success">Di Kantor</span>' : '');
                }

                // Get initials
                const nameParts = admin.nama.split(' ');
                let initials = nameParts[0].charAt(0).toUpperCase();
                if (nameParts.length > 1) initials += nameParts[nameParts.length - 1].charAt(0).toUpperCase();

                const fotoHtml = admin.foto_profil
                    ? `<img src="../uploads/profile/${admin.foto_profil}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid #3b82f6;" onerror="this.outerHTML='<div style=\\'width:32px;height:32px;border-radius:50%;background:#3b82f6;color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;\\'>${initials}</div>'">`
                    : `<div style="width:32px;height:32px;border-radius:50%;background:#3b82f6;color:white;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;">${initials}</div>`;

                // Distance & time estimate
                const distInfo = getDistanceInfo(admin.latitude, admin.longitude);
                let distHtml = '<small style="color:#999;">Lokasi Anda belum tersedia</small>';
                if (distInfo.distance !== null) {
                    distHtml = `<strong style="color:#15803d;">${distInfo.formatted}</strong><br><small style="color:#666;">~${distInfo.time}</small>`;
                }

                html += `<tr style="cursor: pointer;" onclick="focusOnAdmin(${admin.id}, ${admin.latitude}, ${admin.longitude})">
                    <td style="display:flex;align-items:center;gap:10px;">${fotoHtml} ${admin.nama}</td>
                    <td><span class="badge badge-success">Online</span></td>
                    <td>${distHtml}</td>
                    <td>${jarakKantorHtml}</td>
                    <td><span class="${accClass}">${admin.accuracy.toFixed(2)} m</span></td>
                    <td>${admin.minutes_ago} menit lalu</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); window.open('https://www.google.com/maps/dir/?api=1&destination=${admin.latitude},${admin.longitude}', '_blank')" title="Navigasi ke Lokasi">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
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
