<?php
/**
 * API: Get Dashboard Statistics
 * Returns real-time statistics for supervisor dashboard
 */

require_once '../config/database.php';

header('Content-Type: application/json');
startSecureSession();

// Check if user is logged in and is supervisor
if (!isLoggedIn() || $_SESSION['role'] !== 'supervisor') {
    jsonResponse(false, 'Unauthorized', null);
}

$today = date('Y-m-d');

// Get overall statistics
$statsQuery = "SELECT
                    COUNT(DISTINCT admin_id) as total_admin_aktif,
                    COUNT(*) as total_kunjungan_hari_ini,
                    SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as kunjungan_valid
               FROM visit_reports
               WHERE DATE(waktu_kunjungan) = ?";
$statsResult = executeQuery($statsQuery, "s", [$today]);
$stats = $statsResult['data']->fetch_assoc();

// Get total admin
$totalAdminQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND status = 'active'";
$totalAdminResult = executeQuery($totalAdminQuery);
$totalAdmin = $totalAdminResult['data']->fetch_assoc()['total'];

// Get today's summary by admin
$summaryQuery = "SELECT
                    u.nama_lengkap,
                    ds.*
                 FROM daily_summary ds
                 JOIN users u ON ds.admin_id = u.id
                 WHERE ds.tanggal = ?
                 ORDER BY ds.total_jarak_tempuh DESC";
$summaryResult = executeQuery($summaryQuery, "s", [$today]);
$summaries = [];
while ($row = $summaryResult['data']->fetch_assoc()) {
    $summaries[] = $row;
}

// Get recent reports
$recentQuery = "SELECT
                    vr.*,
                    u.nama_lengkap as admin_name,
                    pl.nama_project
                FROM visit_reports vr
                JOIN users u ON vr.admin_id = u.id
                JOIN project_locations pl ON vr.project_id = pl.id
                ORDER BY vr.waktu_kunjungan DESC
                LIMIT 10";
$recentResult = executeQuery($recentQuery);
$recentReports = [];
while ($row = $recentResult['data']->fetch_assoc()) {
    $row['is_today'] = (date('Y-m-d', strtotime($row['waktu_kunjungan'])) == $today);
    $row['waktu_format'] = $row['is_today']
        ? date('H:i', strtotime($row['waktu_kunjungan']))
        : date('d/m H:i', strtotime($row['waktu_kunjungan']));
    $recentReports[] = $row;
}

jsonResponse(true, 'Data retrieved', [
    'total_admin' => intval($totalAdmin),
    'admin_aktif' => intval($stats['total_admin_aktif'] ?? 0),
    'total_kunjungan' => intval($stats['total_kunjungan_hari_ini'] ?? 0),
    'kunjungan_valid' => intval($stats['kunjungan_valid'] ?? 0),
    'summaries' => $summaries,
    'recent_reports' => $recentReports,
    'last_update' => date('H:i:s')
]);
?>
