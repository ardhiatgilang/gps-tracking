<?php
/**
 * API: Edit Visit Report
 * Admin dapat mengedit jenis_pekerjaan, catatan, dan foto laporan miliknya
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
    $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $jenis_pekerjaan = isset($_POST['jenis_pekerjaan']) ? sanitizeInput($_POST['jenis_pekerjaan']) : null;
    $catatan = isset($_POST['catatan']) ? sanitizeInput($_POST['catatan']) : '';

    if ($report_id <= 0) {
        jsonResponse(false, 'ID laporan tidak valid', null);
    }

    // Check report exists and belongs to this admin
    $checkQuery = "SELECT * FROM visit_reports WHERE id = ? AND admin_id = ?";
    $checkResult = executeQuery($checkQuery, "ii", [$report_id, $admin_id]);

    if (!$checkResult['success'] || $checkResult['data']->num_rows === 0) {
        jsonResponse(false, 'Laporan tidak ditemukan atau bukan milik Anda', null);
    }

    $existingReport = $checkResult['data']->fetch_assoc();

    // Handle foto upload (optional)
    $foto_filename = $existingReport['foto_laporan'];

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $file_type = $_FILES['foto']['type'];

        if (!in_array($file_type, $allowed_types)) {
            jsonResponse(false, 'Tipe file tidak diizinkan. Hanya JPG dan PNG.', null);
        }

        if ($_FILES['foto']['size'] > 5 * 1024 * 1024) {
            jsonResponse(false, 'Ukuran file terlalu besar. Maksimal 5MB.', null);
        }

        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $new_foto = 'laporan_' . $admin_id . '_' . time() . '.' . $extension;
        $upload_path = '../uploads/laporan/' . $new_foto;

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
            jsonResponse(false, 'Gagal mengupload foto', null);
        }

        // Delete old foto if exists
        if (!empty($existingReport['foto_laporan'])) {
            $old_path = '../uploads/laporan/' . $existingReport['foto_laporan'];
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }

        $foto_filename = $new_foto;
    }

    // Update report
    $updateQuery = "UPDATE visit_reports SET jenis_pekerjaan = ?, catatan = ?, foto_laporan = ? WHERE id = ? AND admin_id = ?";
    $result = executeQuery($updateQuery, "sssii", [$jenis_pekerjaan, $catatan, $foto_filename, $report_id, $admin_id]);

    if ($result['success']) {
        jsonResponse(true, 'Laporan berhasil diperbarui', [
            'report_id' => $report_id
        ]);
    } else {
        jsonResponse(false, 'Gagal memperbarui laporan: ' . $result['error'], null);
    }

} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage(), null);
} catch (Error $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage(), null);
}
?>
