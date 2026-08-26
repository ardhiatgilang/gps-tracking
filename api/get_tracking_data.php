<?php
/**
 * API: Get Tracking Data
 * Mengambil data tracking realtime untuk monitoring supervisor
 */

require_once '../config/database.php';

header('Content-Type: application/json');
startSecureSession();

// Check if user is logged in
if (!isLoggedIn()) {
    jsonResponse(false, 'Unauthorized', null);
}

// Get all active admins with latest position
$query = "SELECT
            u.id,
            u.nama_lengkap,
            u.no_hp,
            u.foto_profil,
            gt.latitude,
            gt.longitude,
            gt.accuracy,
            gt.timestamp,
            gt.location_type,
            gt.signal_strength,
            TIMESTAMPDIFF(MINUTE, gt.timestamp, NOW()) AS menit_terakhir_update
          FROM users u
          LEFT JOIN gps_tracking gt ON u.id = gt.admin_id
          WHERE u.role = 'admin'
            AND u.status = 'active'
            AND gt.id = (
                SELECT MAX(id)
                FROM gps_tracking
                WHERE admin_id = u.id
            )";

$result = executeQuery($query);

if ($result['success']) {
    $office = getOfficeLocation();
    $admins = [];

    while ($row = $result['data']->fetch_assoc()) {
        $latitude = floatval($row['latitude']);
        $longitude = floatval($row['longitude']);

        $jarak_dari_kantor = null;
        $dalam_radius_kantor = null;

        if ($office && $row['latitude'] !== null && $row['longitude'] !== null) {
            $jarak_dari_kantor = round(calculateHaversineDistance(
                $latitude,
                $longitude,
                $office['latitude'],
                $office['longitude']
            ), 2);
            $dalam_radius_kantor = $jarak_dari_kantor <= $office['radius_valid'];
        }

        $admins[] = [
            'id' => $row['id'],
            'nama' => $row['nama_lengkap'],
            'no_hp' => $row['no_hp'],
            'foto_profil' => $row['foto_profil'],
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => floatval($row['accuracy']),
            'timestamp' => $row['timestamp'],
            'location_type' => $row['location_type'],
            'signal_strength' => $row['signal_strength'],
            'minutes_ago' => intval($row['menit_terakhir_update']),
            'is_online' => intval($row['menit_terakhir_update']) <= 5,
            'jarak_dari_kantor' => $jarak_dari_kantor,
            'dalam_radius_kantor' => $dalam_radius_kantor
        ];
    }

    jsonResponse(true, 'Data retrieved successfully', $admins);
} else {
    jsonResponse(false, 'Failed to get tracking data', null);
}
?>
