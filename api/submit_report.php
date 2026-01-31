<?php
/**
 * API: Submit Visit Report
 * Menerima laporan kunjungan dari admin dengan validasi Haversine
 * Termasuk upload foto dan ekstraksi EXIF data
 */

require_once '../config/database.php';

header('Content-Type: application/json');
startSecureSession();

// Check if user is logged in dan role admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    jsonResponse(false, 'Unauthorized', null);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null);
}

$admin_id = $_SESSION['user_id'];
$project_id = intval($_POST['project_id']);
$latitude = floatval($_POST['latitude']);
$longitude = floatval($_POST['longitude']);
$accuracy = floatval($_POST['accuracy']);
$catatan = sanitizeInput($_POST['catatan']);
$waktu_kunjungan = $_POST['waktu_kunjungan'];

// Validasi input
if (empty($project_id) || empty($latitude) || empty($longitude)) {
    jsonResponse(false, 'Data tidak lengkap', null);
}

// Get project location
$projectQuery = "SELECT * FROM project_locations WHERE id = ? AND status = 'active'";
$projectResult = executeQuery($projectQuery, "i", [$project_id]);

if (!$projectResult['success'] || $projectResult['data']->num_rows === 0) {
    jsonResponse(false, 'Project tidak ditemukan', null);
}

$project = $projectResult['data']->fetch_assoc();

// Calculate distance using Haversine
$jarak_dari_project = calculateHaversineDistance(
    $latitude,
    $longitude,
    $project['latitude'],
    $project['longitude']
);

// Validasi apakah dalam radius
$is_valid = $jarak_dari_project <= $project['radius_valid'];

// Handle foto upload
$foto_filename = null;
$foto_latitude = null;
$foto_longitude = null;
$foto_timestamp = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    $file_type = $_FILES['foto']['type'];

    if (!in_array($file_type, $allowed_types)) {
        jsonResponse(false, 'Tipe file tidak diizinkan. Hanya JPG dan PNG.', null);
    }

    // Check file size (max 5MB)
    if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
        jsonResponse(false, 'Ukuran file terlalu besar. Maksimal 5MB.', null);
    }

    // Generate unique filename
    $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $foto_filename = 'laporan_' . $admin_id . '_' . time() . '.' . $extension;
    $upload_path = '../uploads/laporan/' . $foto_filename;

    // Upload file
    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
        jsonResponse(false, 'Gagal mengupload foto', null);
    }

    // Extract EXIF data (optional, untuk validasi tambahan)
    if (function_exists('exif_read_data') && in_array($extension, ['jpg', 'jpeg'])) {
        $exif = @exif_read_data($upload_path);

        if ($exif && isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
            // Convert GPS coordinates from EXIF format
            $foto_latitude = getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
            $foto_longitude = getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
        }

        if ($exif && isset($exif['DateTime'])) {
            $foto_timestamp = date('Y-m-d H:i:s', strtotime($exif['DateTime']));
        }
    }
}

// Insert report
$insertQuery = "INSERT INTO visit_reports
                (admin_id, project_id, latitude, longitude, accuracy, jarak_dari_project,
                 is_valid, foto_laporan, foto_latitude, foto_longitude, foto_timestamp,
                 catatan, waktu_kunjungan)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$params = [
    $admin_id,
    $project_id,
    $latitude,
    $longitude,
    $accuracy,
    $jarak_dari_project,
    $is_valid ? 1 : 0,
    $foto_filename,
    $foto_latitude,
    $foto_longitude,
    $foto_timestamp,
    $catatan,
    $waktu_kunjungan
];

$result = executeQuery($insertQuery, "iiddddssddsss", $params);

if ($result['success']) {
    // Update daily summary
    updateDailySummary($admin_id, date('Y-m-d', strtotime($waktu_kunjungan)));

    jsonResponse(true, 'Laporan berhasil disimpan', [
        'report_id' => $result['insert_id'],
        'jarak' => round($jarak_dari_project, 2),
        'is_valid' => $is_valid,
        'radius_valid' => $project['radius_valid'],
        'message' => $is_valid
            ? 'Kunjungan valid - Anda berada di lokasi project'
            : 'Kunjungan di luar radius - Jarak: ' . round($jarak_dari_project, 2) . ' meter'
    ]);
} else {
    // Delete uploaded file if database insert failed
    if ($foto_filename && file_exists($upload_path)) {
        unlink($upload_path);
    }
    jsonResponse(false, 'Gagal menyimpan laporan: ' . $result['error'], null);
}

/**
 * Helper function to convert GPS coordinates from EXIF
 */
function getGps($exifCoord, $hemi) {
    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
}

function gps2Num($coordPart) {
    $parts = explode('/', $coordPart);
    if (count($parts) <= 0)
        return 0;
    if (count($parts) == 1)
        return $parts[0];
    return floatval($parts[0]) / floatval($parts[1]);
}

/**
 * Update daily summary untuk admin
 */
function updateDailySummary($admin_id, $tanggal) {
    // Get today's visits
    $query = "SELECT
                COUNT(*) as total_kunjungan,
                SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as kunjungan_valid,
                MIN(waktu_kunjungan) as waktu_mulai,
                MAX(waktu_kunjungan) as waktu_selesai
              FROM visit_reports
              WHERE admin_id = ? AND DATE(waktu_kunjungan) = ?";

    $result = executeQuery($query, "is", [$admin_id, $tanggal]);

    if ($result['success']) {
        $data = $result['data']->fetch_assoc();

        // Calculate total distance from GPS tracking
        $distQuery = "SELECT
                        SUM(
                            6371000 * 2 * ASIN(
                                SQRT(
                                    POWER(SIN((RADIANS(t2.latitude) - RADIANS(t1.latitude)) / 2), 2) +
                                    COS(RADIANS(t1.latitude)) * COS(RADIANS(t2.latitude)) *
                                    POWER(SIN((RADIANS(t2.longitude) - RADIANS(t1.longitude)) / 2), 2)
                                )
                            )
                        ) / 1000 as total_jarak
                      FROM gps_tracking t1
                      JOIN gps_tracking t2 ON t2.id = t1.id + 1 AND t2.admin_id = t1.admin_id
                      WHERE t1.admin_id = ? AND DATE(t1.timestamp) = ?";

        $distResult = executeQuery($distQuery, "is", [$admin_id, $tanggal]);
        $total_jarak = 0;

        if ($distResult['success'] && $distResult['data']->num_rows > 0) {
            $distData = $distResult['data']->fetch_assoc();
            $total_jarak = $distData['total_jarak'] ?? 0;
        }

        // Calculate duration
        $durasi_menit = 0;
        if ($data['waktu_mulai'] && $data['waktu_selesai']) {
            $start = strtotime($data['waktu_mulai']);
            $end = strtotime($data['waktu_selesai']);
            $durasi_menit = round(($end - $start) / 60);
        }

        // Calculate metrics
        $efisiensi = $durasi_menit > 0 ? ($data['total_kunjungan'] / ($durasi_menit / 60)) : 0;
        $success_rate = $data['total_kunjungan'] > 0 ? ($data['kunjungan_valid'] / $data['total_kunjungan'] * 100) : 0;
        $rata_jarak = $data['total_kunjungan'] > 0 ? ($total_jarak / $data['total_kunjungan']) : 0;

        // Insert or update daily summary
        $summaryQuery = "INSERT INTO daily_summary
                         (admin_id, tanggal, total_jarak_tempuh, jumlah_kunjungan, jumlah_kunjungan_valid,
                          durasi_kerja_menit, waktu_mulai, waktu_selesai, efisiensi_score,
                          rata_rata_jarak_per_kunjungan, success_rate)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                         total_jarak_tempuh = VALUES(total_jarak_tempuh),
                         jumlah_kunjungan = VALUES(jumlah_kunjungan),
                         jumlah_kunjungan_valid = VALUES(jumlah_kunjungan_valid),
                         durasi_kerja_menit = VALUES(durasi_kerja_menit),
                         waktu_mulai = VALUES(waktu_mulai),
                         waktu_selesai = VALUES(waktu_selesai),
                         efisiensi_score = VALUES(efisiensi_score),
                         rata_rata_jarak_per_kunjungan = VALUES(rata_rata_jarak_per_kunjungan),
                         success_rate = VALUES(success_rate)";

        executeQuery($summaryQuery, "isdiisssddd", [
            $admin_id,
            $tanggal,
            $total_jarak,
            $data['total_kunjungan'],
            $data['kunjungan_valid'],
            $durasi_menit,
            $data['waktu_mulai'],
            $data['waktu_selesai'],
            $efisiensi,
            $rata_jarak,
            $success_rate
        ]);
    }
}
?>
