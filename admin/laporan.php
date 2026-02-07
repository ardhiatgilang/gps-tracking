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
            <a href="laporan.php" class="active">Buat Laporan</a>
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
                <form id="form-laporan" enctype="multipart/form-data" class="no-loader">
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
                        <label>Upload Foto <span class="text-danger">*</span></label>
                        <div class="photo-upload-area" id="photoUploadArea" onclick="showPhotoOptions()">
                            <div class="photo-upload-placeholder" id="photoPlaceholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>Klik untuk pilih foto</span>
                            </div>
                            <div class="photo-upload-preview" id="photoPreviewContainer" style="display:none;">
                                <img id="photoPreview" src="" alt="Preview">
                                <button type="button" class="photo-remove-btn" onclick="event.stopPropagation(); removePhoto();">&times;</button>
                            </div>
                        </div>
                        <input type="file" id="foto" name="foto" accept="image/jpeg,image/jpg,image/png" style="display:none;" required>
                        <div class="photo-info" id="photoInfo" style="display:none;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span id="photoName"></span>
                        </div>
                        <small class="text-secondary">Format: JPG, PNG. Maksimal 5MB.</small>
                    </div>

                    <div class="form-group">
                        <label for="jenis_pekerjaan">Jenis Pekerjaan <span class="text-danger">*</span></label>
                        <select class="form-control" id="jenis_pekerjaan" name="jenis_pekerjaan" required>
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

    <!-- Photo Options Modal -->
    <div class="photo-options-modal" id="photoOptionsModal">
        <div class="photo-options-content">
            <div class="photo-options-header">
                <span>Pilih Foto</span>
                <button type="button" class="photo-options-close" onclick="hidePhotoOptions()">&times;</button>
            </div>
            <div class="photo-options-body">
                <button type="button" class="photo-option-btn" onclick="selectFromGallery()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span>Pilih dari Galeri</span>
                </button>
                <button type="button" class="photo-option-btn" onclick="takeFromCamera()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    <span>Ambil dari Kamera</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Camera Modal -->
    <div class="camera-modal" id="cameraModal">
        <div class="camera-header">
            <span>Ambil Foto Laporan</span>
            <button type="button" class="camera-close" onclick="closeCamera()">&times;</button>
        </div>
        <div class="camera-preview" id="cameraPreview">
            <video id="cameraVideo" autoplay playsinline></video>
            <canvas id="cameraCanvas"></canvas>
            <div class="camera-error" id="cameraError" style="display:none;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
                <p id="cameraErrorMsg">Tidak dapat mengakses kamera</p>
                <button type="button" onclick="closeCamera()">Tutup</button>
            </div>
        </div>
        <div class="camera-controls" id="cameraControls">
            <button type="button" class="camera-switch-btn" onclick="switchCamera()" id="switchCameraBtn" title="Ganti Kamera">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 2v6h-6"></path>
                    <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                    <path d="M3 22v-6h6"></path>
                    <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                </svg>
            </button>
            <button type="button" class="camera-capture-btn" onclick="capturePhoto()" title="Ambil Foto">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
            </button>
            <div style="width: 50px;"></div>
        </div>
    </div>

    <style>
        .photo-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f9fafb;
        }
        .photo-upload-area:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .photo-upload-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: #6b7280;
        }
        .photo-upload-placeholder span {
            font-size: 14px;
        }
        .photo-upload-preview {
            position: relative;
            display: inline-block;
        }
        .photo-upload-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            object-fit: contain;
        }
        .photo-remove-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ef4444;
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .photo-remove-btn:hover {
            background: #dc2626;
        }
        .photo-info {
            margin-top: 10px;
            padding: 8px 12px;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 6px;
            font-size: 13px;
            color: #166534;
        }
        .photo-options-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .photo-options-modal.active {
            display: flex;
        }
        .photo-options-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 320px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideUp 0.3s ease;
        }
        @keyframes modalSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .photo-options-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #1f2937;
        }
        .photo-options-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .photo-options-close:hover {
            color: #1f2937;
        }
        .photo-options-body {
            padding: 12px;
        }
        .photo-option-btn {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            padding: 14px 16px;
            background: #f9fafb;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
        }
        .photo-option-btn:last-child {
            margin-bottom: 0;
        }
        .photo-option-btn:hover {
            background: #e0f2fe;
        }
        .photo-option-btn svg {
            color: #3b82f6;
        }
        .photo-option-btn span {
            font-size: 15px;
            font-weight: 500;
            color: #374151;
        }
        .camera-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #000;
            z-index: 1001;
            flex-direction: column;
        }
        .camera-modal.active {
            display: flex;
        }
        .camera-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: rgba(0,0,0,0.8);
            color: white;
        }
        .camera-header span {
            font-weight: 600;
        }
        .camera-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .camera-preview {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .camera-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .camera-preview canvas {
            display: none;
        }
        .camera-controls {
            padding: 20px;
            background: rgba(0,0,0,0.8);
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .camera-capture-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: white;
            border: 4px solid #3b82f6;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .camera-capture-btn:hover {
            transform: scale(1.1);
            background: #e0f2fe;
        }
        .camera-capture-btn:active {
            transform: scale(0.95);
        }
        .camera-switch-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            align-self: center;
        }
        .camera-switch-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .camera-error {
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        .camera-error svg {
            margin-bottom: 15px;
            opacity: 0.7;
        }
        .camera-error p {
            margin: 0 0 20px;
            opacity: 0.9;
        }
        .camera-error button {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>

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
            // Use local time instead of UTC
            const now = new Date();
            const localTime = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');
            document.getElementById('waktu_kunjungan').value = localTime;

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
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Raw response:', text);
                        throw new Error('Response bukan JSON: ' + text.substring(0, 200));
                    }
                });
            })
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
                showAlert(error.message || 'Terjadi kesalahan jaringan', 'danger');
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

        // Photo Options Functions
        function showPhotoOptions() {
            document.getElementById('photoOptionsModal').classList.add('active');
        }

        function hidePhotoOptions() {
            document.getElementById('photoOptionsModal').classList.remove('active');
        }

        function selectFromGallery() {
            hidePhotoOptions();
            document.getElementById('foto').click();
        }

        // Camera variables
        let cameraStream = null;
        let currentFacingMode = 'environment'; // Default to back camera for report photos

        function takeFromCamera() {
            hidePhotoOptions();
            openCamera();
        }

        async function openCamera() {
            const modal = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');
            const errorDiv = document.getElementById('cameraError');
            const controls = document.getElementById('cameraControls');

            modal.classList.add('active');
            errorDiv.style.display = 'none';
            video.style.display = 'block';
            controls.style.display = 'flex';

            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: currentFacingMode,
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    },
                    audio: false
                });

                video.srcObject = cameraStream;

                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(d => d.kind === 'videoinput');
                document.getElementById('switchCameraBtn').style.display = videoDevices.length > 1 ? 'flex' : 'none';

            } catch (err) {
                console.error('Camera error:', err);
                video.style.display = 'none';
                controls.style.display = 'none';
                errorDiv.style.display = 'block';

                let errorMsg = 'Tidak dapat mengakses kamera';
                if (err.name === 'NotAllowedError') {
                    errorMsg = 'Akses kamera ditolak. Silakan izinkan akses kamera di pengaturan browser.';
                } else if (err.name === 'NotFoundError') {
                    errorMsg = 'Kamera tidak ditemukan pada perangkat ini.';
                } else if (err.name === 'NotReadableError') {
                    errorMsg = 'Kamera sedang digunakan aplikasi lain.';
                }
                document.getElementById('cameraErrorMsg').textContent = errorMsg;
            }
        }

        function closeCamera() {
            const modal = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');

            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }

            video.srcObject = null;
            modal.classList.remove('active');
        }

        async function switchCamera() {
            currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }

            await openCamera();
        }

        function capturePhoto() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const ctx = canvas.getContext('2d');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            ctx.drawImage(video, 0, 0);

            canvas.toBlob(function(blob) {
                const file = new File([blob], 'laporan_' + Date.now() + '.jpg', { type: 'image/jpeg' });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('foto').files = dataTransfer.files;

                handleFileSelect(file);

                closeCamera();
            }, 'image/jpeg', 0.9);
        }

        // Close modal when clicking outside
        document.getElementById('photoOptionsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hidePhotoOptions();
            }
        });

        // Handle file selection from gallery
        document.getElementById('foto').addEventListener('change', function(e) {
            if (e.target.files[0]) {
                handleFileSelect(e.target.files[0]);
            }
        });

        function handleFileSelect(file) {
            if (file) {
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                const fileSizeKB = (file.size / 1024).toFixed(0);
                const sizeText = file.size > 1024 * 1024 ? `${fileSizeMB} MB` : `${fileSizeKB} KB`;

                document.getElementById('photoName').textContent = `${file.name} (${sizeText})`;
                document.getElementById('photoInfo').style.display = 'block';

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPlaceholder').style.display = 'none';
                    document.getElementById('photoPreviewContainer').style.display = 'inline-block';
                    document.getElementById('photoPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function removePhoto() {
            document.getElementById('foto').value = '';
            document.getElementById('photoPlaceholder').style.display = 'flex';
            document.getElementById('photoPreviewContainer').style.display = 'none';
            document.getElementById('photoPreview').src = '';
            document.getElementById('photoInfo').style.display = 'none';
        }

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hidePhotoOptions();
                closeCamera();
            }
        });
    </script>
</body>
</html>
