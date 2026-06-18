<?php
/**
 * API: Delete Visit Report
 * Admin dapat menghapus laporan kunjungan miliknya
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    startSecureSession();

    if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
        jsonResponse(false, 'Unauthorized', null);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Method not allowed', null);
    }

    $admin_id = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $report_id = isset($data['report_id']) ? intval($data['report_id']) : 0;

    if ($report_id <= 0) {
        jsonResponse(false, 'ID laporan tidak valid', null);
    }

    // Check report exists and belongs to this admin
    $checkQuery = "SELECT id, foto_laporan, admin_id, waktu_kunjungan FROM visit_reports WHERE id = ? AND admin_id = ?";
    $checkResult = executeQuery($checkQuery, "ii", [$report_id, $admin_id]);

    if (!$checkResult['success'] || $checkResult['data']->num_rows === 0) {
        jsonResponse(false, 'Laporan tidak ditemukan atau bukan milik Anda', null);
    }

    $report = $checkResult['data']->fetch_assoc();

    // Delete foto file if exists
    if (!empty($report['foto_laporan'])) {
        $foto_path = '../uploads/laporan/' . $report['foto_laporan'];
        if (file_exists($foto_path)) {
            unlink($foto_path);
        }
    }

    // Delete report from database
    $deleteQuery = "DELETE FROM visit_reports WHERE id = ? AND admin_id = ?";
    $result = executeQuery($deleteQuery, "ii", [$report_id, $admin_id]);

    if ($result['success']) {
        // Update daily summary after deletion
        $tanggal = date('Y-m-d', strtotime($report['waktu_kunjungan']));
        updateDailySummaryAfterDelete($admin_id, $tanggal);

        jsonResponse(true, 'Laporan berhasil dihapus', [
            'report_id' => $report_id
        ]);
    } else {
        jsonResponse(false, 'Gagal menghapus laporan: ' . $result['error'], null);
    }

} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage(), null);
} catch (Error $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage(), null);
}

/**
 * Update daily summary after report deletion
 */
function updateDailySummaryAfterDelete($admin_id, $tanggal) {
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

        if ($data['total_kunjungan'] == 0) {
            // No more reports for this day, delete summary
            executeQuery("DELETE FROM daily_summary WHERE admin_id = ? AND tanggal = ?", "is", [$admin_id, $tanggal]);
        } else {
            // Recalculate summary
            $durasi_menit = 0;
            if ($data['waktu_mulai'] && $data['waktu_selesai']) {
                $start = strtotime($data['waktu_mulai']);
                $end = strtotime($data['waktu_selesai']);
                $durasi_menit = round(($end - $start) / 60);
            }

            $efisiensi = $durasi_menit > 0 ? ($data['total_kunjungan'] / ($durasi_menit / 60)) : 0;
            $success_rate = $data['total_kunjungan'] > 0 ? ($data['kunjungan_valid'] / $data['total_kunjungan'] * 100) : 0;

            $summaryQuery = "UPDATE daily_summary SET
                             jumlah_kunjungan = ?,
                             jumlah_kunjungan_valid = ?,
                             durasi_kerja_menit = ?,
                             waktu_mulai = ?,
                             waktu_selesai = ?,
                             efisiensi_score = ?,
                             success_rate = ?
                             WHERE admin_id = ? AND tanggal = ?";

            executeQuery($summaryQuery, "iiissddis", [
                $data['total_kunjungan'],
                $data['kunjungan_valid'],
                $durasi_menit,
                $data['waktu_mulai'],
                $data['waktu_selesai'],
                $efisiensi,
                $success_rate,
                $admin_id,
                $tanggal
            ]);
        }
    }
}
?>
