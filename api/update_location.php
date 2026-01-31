<?php
/**
 * API: Update Location
 * Menerima data GPS dari admin lapangan dan menyimpan ke database
 * Untuk analisis akurasi GPS dan tracking realtime
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validasi input
$required = ['latitude', 'longitude', 'accuracy', 'timestamp'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        jsonResponse(false, "Field $field is required", null);
    }
}

$admin_id = $_SESSION['user_id'];
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$accuracy = floatval($input['accuracy']);
$altitude = isset($input['altitude']) ? floatval($input['altitude']) : null;
$speed = isset($input['speed']) ? floatval($input['speed']) : null;
$heading = isset($input['heading']) ? floatval($input['heading']) : null;
$timestamp = $input['timestamp'];

// Optional fields
$location_type = isset($input['location_type']) ? $input['location_type'] : 'outdoor';
$signal_strength = isset($input['signal_strength']) ? $input['signal_strength'] : 'medium';
$device_info = isset($input['device_info']) ? $input['device_info'] : null;
$battery_level = isset($input['battery_level']) ? intval($input['battery_level']) : null;
$network_type = isset($input['network_type']) ? $input['network_type'] : null;

// Insert to database - Gunakan NOW() untuk timestamp server yang akurat
$query = "INSERT INTO gps_tracking
          (admin_id, latitude, longitude, accuracy, altitude, speed, heading, timestamp,
           location_type, signal_strength, device_info, battery_level, network_type)
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";

$result = executeQuery($query, "iddddddsssis", [
    $admin_id,
    $latitude,
    $longitude,
    $accuracy,
    $altitude,
    $speed,
    $heading,
    $location_type,
    $signal_strength,
    $device_info,
    $battery_level,
    $network_type
]);

if ($result['success']) {
    // Update atau create tracking session
    $sessionQuery = "SELECT id FROM tracking_sessions
                     WHERE admin_id = ? AND is_active = 1
                     ORDER BY id DESC LIMIT 1";
    $sessionResult = executeQuery($sessionQuery, "i", [$admin_id]);

    if ($sessionResult['success'] && $sessionResult['data']->num_rows > 0) {
        // Update existing session
        $session = $sessionResult['data']->fetch_assoc();
        $updateSession = "UPDATE tracking_sessions
                          SET total_tracking_points = total_tracking_points + 1,
                              session_end = NOW()
                          WHERE id = ?";
        executeQuery($updateSession, "i", [$session['id']]);
    } else {
        // Create new session
        $createSession = "INSERT INTO tracking_sessions
                          (admin_id, session_start, is_active, total_tracking_points)
                          VALUES (?, ?, 1, 1)";
        executeQuery($createSession, "is", [$admin_id, $timestamp]);
    }

    jsonResponse(true, 'Location updated successfully', [
        'tracking_id' => $result['insert_id'],
        'accuracy' => $accuracy,
        'timestamp' => $timestamp
    ]);
} else {
    jsonResponse(false, 'Failed to update location: ' . $result['error'], null);
}
?>
