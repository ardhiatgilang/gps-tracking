<?php
/**
 * API untuk update status laporan oleh Supervisor
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once '../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start session and check if user is logged in
startSecureSession();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
if (!$user || $user['role'] !== 'supervisor') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    // Try regular POST
    $data = $_POST;
}

$report_id = isset($data['report_id']) ? intval($data['report_id']) : 0;
$status = isset($data['status']) ? $data['status'] : '';

// Validate
if ($report_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID laporan tidak valid']);
    exit;
}

$valid_statuses = ['pending', 'proses', 'reject', 'verified', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Status tidak valid: ' . $status]);
    exit;
}

// Map UI status to database status
// UI "proses" -> DB "verified", UI "reject" -> DB "rejected"
$dbStatusMap = [
    'pending' => 'pending',
    'proses' => 'verified',
    'reject' => 'rejected',
    'verified' => 'verified',
    'rejected' => 'rejected'
];
$dbStatus = $dbStatusMap[$status] ?? $status;

// Check if report exists first
$checkQuery = "SELECT id, status FROM visit_reports WHERE id = ?";
$checkResult = executeQuery($checkQuery, "i", [$report_id]);

if (!$checkResult['success'] || $checkResult['data']->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan']);
    exit;
}

// Get database connection for direct query
$conn = getDBConnection();

// Update status using direct query with error handling
$updateQuery = "UPDATE visit_reports SET status = ? WHERE id = ?";
$stmt = $conn->prepare($updateQuery);

if ($stmt === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("si", $dbStatus, $report_id);
$executeResult = $stmt->execute();

if ($executeResult) {
    $affected = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Status berhasil diubah menjadi ' . ucfirst($status),
        'new_status' => $status,
        'db_status' => $dbStatus,
        'report_id' => $report_id,
        'affected_rows' => $affected
    ]);
} else {
    $error = $stmt->error;
    $stmt->close();

    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengupdate: ' . $error,
        'tried_status' => $status
    ]);
}
