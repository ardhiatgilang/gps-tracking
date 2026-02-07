<?php
/**
 * Halaman Analisis Produktivitas
 * Menampilkan statistik deskriptif dan analisis data GPS untuk penelitian skripsi
 * - Analisis akurasi GPS
 * - Validasi metode Haversine
 * - Evaluasi produktivitas admin lapangan
 */

require_once '../config/database.php';
checkRole('supervisor');

$user = getCurrentUser();

// Date filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get akurasi GPS statistics
$gpsAccuracyQuery = "SELECT
                        location_type,
                        COUNT(*) as total_readings,
                        ROUND(AVG(accuracy), 2) as mean_accuracy,
                        ROUND(MIN(accuracy), 2) as min_accuracy,
                        ROUND(MAX(accuracy), 2) as max_accuracy,
                        ROUND(STDDEV(accuracy), 2) as std_dev,
                        ROUND(SUM(CASE WHEN accuracy <= 20 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as persen_akurasi_baik
                     FROM gps_tracking
                     WHERE DATE(timestamp) BETWEEN ? AND ?
                     GROUP BY location_type";
$gpsAccuracyResult = executeQuery($gpsAccuracyQuery, "ss", [$start_date, $end_date]);

// Get produktivitas summary
$produktivitasQuery = "SELECT
                        u.nama_lengkap,
                        COUNT(DISTINCT ds.tanggal) as total_hari_kerja,
                        SUM(ds.jumlah_kunjungan) as total_kunjungan,
                        SUM(ds.jumlah_kunjungan_valid) as total_kunjungan_valid,
                        ROUND(SUM(ds.total_jarak_tempuh), 2) as total_jarak,
                        ROUND(AVG(ds.efisiensi_score), 2) as avg_efisiensi,
                        ROUND(AVG(ds.success_rate), 2) as avg_success_rate,
                        ROUND(AVG(ds.rata_rata_jarak_per_kunjungan), 2) as avg_jarak_per_kunjungan
                       FROM daily_summary ds
                       JOIN users u ON ds.admin_id = u.id
                       WHERE ds.tanggal BETWEEN ? AND ?
                       GROUP BY u.id, u.nama_lengkap
                       ORDER BY total_kunjungan DESC";
$produktivitasResult = executeQuery($produktivitasQuery, "ss", [$start_date, $end_date]);

// Get validasi Haversine (jarak kunjungan vs radius valid)
$haversineQuery = "SELECT
                    CASE
                        WHEN jarak_dari_project <= 25 THEN '0-25m'
                        WHEN jarak_dari_project <= 50 THEN '26-50m'
                        WHEN jarak_dari_project <= 100 THEN '51-100m'
                        ELSE '>100m'
                    END as range_jarak,
                    COUNT(*) as jumlah,
                    ROUND(AVG(accuracy), 2) as avg_accuracy
                   FROM visit_reports
                   WHERE DATE(waktu_kunjungan) BETWEEN ? AND ?
                   GROUP BY range_jarak
                   ORDER BY range_jarak";
$haversineResult = executeQuery($haversineQuery, "ss", [$start_date, $end_date]);

// Get overall statistics
$overallQuery = "SELECT
                    COUNT(*) as total_tracking_points,
                    ROUND(AVG(accuracy), 2) as overall_avg_accuracy,
                    COUNT(DISTINCT admin_id) as total_admin
                 FROM gps_tracking
                 WHERE DATE(timestamp) BETWEEN ? AND ?";
$overallResult = executeQuery($overallQuery, "ss", [$start_date, $end_date]);
$overall = $overallResult['data']->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Produktivitas - Supervisor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="monitoring.php">Monitoring Realtime</a>
            <a href="laporan.php">Laporan</a>
            <a href="analisis.php" class="active">Analisis Produktivitas</a>
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
        <h2 class="mb-3">Analisis Produktivitas & Akurasi GPS</h2>

        <!-- Date Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="d-flex gap-2 align-center">
                    <label>Periode:</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" style="width: auto;">
                    <span>s/d</span>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" style="width: auto;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">Cetak Laporan</button>
                </form>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="dashboard-grid">
            <div class="stat-card info">
                <div class="stat-label">Total Data GPS</div>
                <div class="stat-value"><?php echo number_format($overall['total_tracking_points']); ?></div>
            </div>
            <div class="stat-card success">
                <div class="stat-label">Rata-rata Akurasi GPS</div>
                <div class="stat-value"><?php echo $overall['overall_avg_accuracy']; ?> m</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">Admin Terlibat</div>
                <div class="stat-value"><?php echo $overall['total_admin']; ?></div>
            </div>
        </div>

        <!-- Section 1: Analisis Akurasi GPS -->
        <div class="card">
            <div class="card-header">
                <strong>1. Analisis Akurasi GPS Berdasarkan Kondisi Lingkungan</strong>
            </div>
            <div class="card-body">
                <p><strong>Tujuan Analisis:</strong> Mengukur variasi akurasi GPS pada berbagai kondisi lingkungan untuk validasi sistem tracking.</p>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kondisi Lingkungan</th>
                                <th>Total Readings</th>
                                <th>Mean Accuracy (m)</th>
                                <th>Min (m)</th>
                                <th>Max (m)</th>
                                <th>Std Dev (m)</th>
                                <th>% Akurasi Baik (≤20m)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $chartLabels = [];
                            $chartData = [];
                            while ($gps = $gpsAccuracyResult['data']->fetch_assoc()):
                                $chartLabels[] = ucfirst($gps['location_type']);
                                $chartData[] = $gps['mean_accuracy'];
                            ?>
                                <tr>
                                    <td><strong><?php echo ucfirst($gps['location_type']); ?></strong></td>
                                    <td><?php echo number_format($gps['total_readings']); ?></td>
                                    <td><?php echo $gps['mean_accuracy']; ?></td>
                                    <td><?php echo $gps['min_accuracy']; ?></td>
                                    <td><?php echo $gps['max_accuracy']; ?></td>
                                    <td><?php echo $gps['std_dev']; ?></td>
                                    <td>
                                        <?php
                                        $persen = $gps['persen_akurasi_baik'];
                                        $class = $persen >= 70 ? 'text-success' : ($persen >= 50 ? 'text-warning' : 'text-danger');
                                        echo "<span class='$class'><strong>{$persen}%</strong></span>";
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div style="max-width: 600px; margin: 20px auto;">
                    <canvas id="chartAccuracy"></canvas>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Interpretasi:</strong>
                    <ul>
                        <li>Akurasi GPS <strong>outdoor</strong> umumnya lebih baik (mean accuracy lebih rendah)</li>
                        <li>Akurasi GPS <strong>indoor</strong> lebih buruk karena lemahnya sinyal satelit</li>
                        <li>Standar deviasi menunjukkan konsistensi akurasi GPS</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Section 2: Validasi Haversine -->
        <div class="card">
            <div class="card-header">
                <strong>2. Validasi Metode Haversine untuk Verifikasi Kunjungan</strong>
            </div>
            <div class="card-body">
                <p><strong>Tujuan Analisis:</strong> Mengevaluasi efektivitas metode Haversine dalam menghitung jarak dan memvalidasi kehadiran admin di lokasi project.</p>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Range Jarak dari Project</th>
                                <th>Jumlah Kunjungan</th>
                                <th>Rata-rata Akurasi GPS (m)</th>
                                <th>Interpretasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $haversineLabels = [];
                            $haversineData = [];
                            while ($hav = $haversineResult['data']->fetch_assoc()):
                                $haversineLabels[] = $hav['range_jarak'];
                                $haversineData[] = $hav['jumlah'];

                                $interpretation = '';
                                if ($hav['range_jarak'] == '0-25m') {
                                    $interpretation = 'Sangat Valid - Admin berada di lokasi';
                                } elseif ($hav['range_jarak'] == '26-50m') {
                                    $interpretation = 'Valid - Dalam radius acceptable';
                                } elseif ($hav['range_jarak'] == '51-100m') {
                                    $interpretation = 'Marginal - Perlu verifikasi manual';
                                } else {
                                    $interpretation = 'Invalid - Di luar radius valid';
                                }
                            ?>
                                <tr>
                                    <td><strong><?php echo $hav['range_jarak']; ?></strong></td>
                                    <td><?php echo $hav['jumlah']; ?></td>
                                    <td><?php echo $hav['avg_accuracy']; ?></td>
                                    <td><?php echo $interpretation; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div style="max-width: 600px; margin: 20px auto;">
                    <canvas id="chartHaversine"></canvas>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Kesimpulan:</strong>
                    <ul>
                        <li>Metode Haversine efektif untuk menghitung jarak antara posisi admin dan lokasi project</li>
                        <li>Threshold radius 50m memberikan balance antara akurasi dan usability</li>
                        <li>Akurasi GPS mempengaruhi validitas hasil perhitungan Haversine</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Section 3: Evaluasi Produktivitas -->
        <div class="card">
            <div class="card-header">
                <strong>3. Evaluasi Produktivitas Admin Lapangan</strong>
            </div>
            <div class="card-body">
                <p><strong>Tujuan Analisis:</strong> Mengevaluasi kinerja admin lapangan berdasarkan metrik jarak tempuh, jumlah kunjungan, dan efisiensi kerja.</p>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Admin</th>
                                <th>Hari Kerja</th>
                                <th>Total Kunjungan</th>
                                <th>Kunjungan Valid</th>
                                <th>Total Jarak (km)</th>
                                <th>Avg Efisiensi (kunjungan/jam)</th>
                                <th>Success Rate (%)</th>
                                <th>Avg Jarak/Kunjungan (km)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($prod = $produktivitasResult['data']->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prod['nama_lengkap']); ?></strong></td>
                                    <td><?php echo $prod['total_hari_kerja']; ?></td>
                                    <td><?php echo $prod['total_kunjungan']; ?></td>
                                    <td><?php echo $prod['total_kunjungan_valid']; ?></td>
                                    <td><?php echo $prod['total_jarak']; ?></td>
                                    <td><?php echo $prod['avg_efisiensi']; ?></td>
                                    <td>
                                        <?php
                                        $rate = $prod['avg_success_rate'];
                                        $class = $rate >= 80 ? 'text-success' : ($rate >= 60 ? 'text-warning' : 'text-danger');
                                        echo "<span class='$class'><strong>{$rate}%</strong></span>";
                                        ?>
                                    </td>
                                    <td><?php echo $prod['avg_jarak_per_kunjungan']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Metrik Produktivitas:</strong>
                    <ul>
                        <li><strong>Efisiensi:</strong> Jumlah kunjungan per jam kerja (semakin tinggi = semakin produktif)</li>
                        <li><strong>Success Rate:</strong> Persentase kunjungan valid (≥80% = baik, 60-79% = cukup, <60% = perlu perbaikan)</li>
                        <li><strong>Jarak per Kunjungan:</strong> Rata-rata jarak tempuh per kunjungan (indikator coverage area)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Section 4: Rekomendasi -->
        <div class="card no-print">
            <div class="card-header">
                <strong>4. Rekomendasi & Tindak Lanjut</strong>
            </div>
            <div class="card-body">
                <h4>Berdasarkan analisis data GPS dan produktivitas:</h4>
                <ol>
                    <li><strong>Akurasi GPS:</strong> Pastikan admin berada di area outdoor saat melakukan tracking untuk akurasi optimal</li>
                    <li><strong>Validasi Kunjungan:</strong> Radius valid 50m sudah sesuai, namun perlu verifikasi manual untuk jarak 51-100m</li>
                    <li><strong>Produktivitas:</strong> Admin dengan success rate <60% perlu coaching dan pelatihan</li>
                    <li><strong>Efisiensi:</strong> Optimalkan rute kunjungan untuk meningkatkan jumlah kunjungan per jam</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- JavaScript Charts -->
    <script>
        // Chart Akurasi GPS
        const ctxAccuracy = document.getElementById('chartAccuracy').getContext('2d');
        new Chart(ctxAccuracy, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Mean Accuracy (meter)',
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                    borderColor: ['#2563eb', '#059669', '#d97706', '#dc2626'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Akurasi GPS Berdasarkan Kondisi Lingkungan'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Akurasi (meter)'
                        }
                    }
                }
            }
        });

        // Chart Haversine
        const ctxHaversine = document.getElementById('chartHaversine').getContext('2d');
        new Chart(ctxHaversine, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($haversineLabels); ?>,
                datasets: [{
                    label: 'Distribusi Jarak Kunjungan',
                    data: <?php echo json_encode($haversineData); ?>,
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribusi Jarak Kunjungan dari Project (Haversine)'
                    }
                }
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
            if (trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>
