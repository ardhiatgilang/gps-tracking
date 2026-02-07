<?php
/**
 * API: Export Laporan Kunjungan ke Excel
 * Mengexport data laporan ke format Excel (CSV dengan UTF-8 BOM)
 */

require_once '../config/database.php';

// Check session and role
startSecureSession();
if (!isLoggedIn() || $_SESSION['role'] !== 'supervisor') {
    header('Location: ../index.php');
    exit;
}

// Get filter parameters
$filter_admin = isset($_GET['admin']) ? intval($_GET['admin']) : 0;
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';

// Build query
$where = ["1=1"];
$params = [];
$types = "";

if (!empty($filter_date)) {
    if (!empty($filter_date_end)) {
        // Date range
        $where[] = "DATE(vr.waktu_kunjungan) BETWEEN ? AND ?";
        $params[] = $filter_date;
        $params[] = $filter_date_end;
        $types .= "ss";
    } else {
        // Single date
        $where[] = "DATE(vr.waktu_kunjungan) = ?";
        $params[] = $filter_date;
        $types .= "s";
    }
}

if ($filter_admin > 0) {
    $where[] = "vr.admin_id = ?";
    $params[] = $filter_admin;
    $types .= "i";
}

$whereClause = implode(" AND ", $where);

// Get all reports (no pagination for export)
$reportsQuery = "SELECT vr.*, u.nama_lengkap as admin_name, pl.nama_project, pl.alamat, pl.radius_valid
                 FROM visit_reports vr
                 JOIN users u ON vr.admin_id = u.id
                 JOIN project_locations pl ON vr.project_id = pl.id
                 WHERE $whereClause
                 ORDER BY vr.waktu_kunjungan DESC";

if (!empty($types)) {
    $reportsResult = executeQuery($reportsQuery, $types, $params);
} else {
    $reportsResult = executeQuery($reportsQuery);
}

// Generate filename
$filename = 'Laporan_Kunjungan_';
if (!empty($filter_date)) {
    $filename .= date('Ymd', strtotime($filter_date));
    if (!empty($filter_date_end)) {
        $filename .= '_sd_' . date('Ymd', strtotime($filter_date_end));
    }
} else {
    $filename .= date('Ymd_His');
}
$filename .= '.xls';

// Get base URL for foto links
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = str_replace('/api', '', dirname($_SERVER['REQUEST_URI']));
$baseUrl = $protocol . '://' . $host . $basePath . '/uploads/laporan/';

// Set headers for Excel download (HTML format)
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output HTML table that Excel can read
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<!--[if gte mso 9]>';
echo '<xml>';
echo '<x:ExcelWorkbook>';
echo '<x:ExcelWorksheets>';
echo '<x:ExcelWorksheet>';
echo '<x:Name>Laporan Kunjungan</x:Name>';
echo '<x:WorksheetOptions>';
echo '<x:Print>';
echo '<x:ValidPrinterInfo/>';
echo '</x:Print>';
echo '</x:WorksheetOptions>';
echo '</x:ExcelWorksheet>';
echo '</x:ExcelWorksheets>';
echo '</x:ExcelWorkbook>';
echo '</xml>';
echo '<![endif]-->';
echo '<style>';
echo 'table { border-collapse: collapse; }';
echo 'th { background-color: #4472C4; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; }';
echo 'td { padding: 6px; border: 1px solid #ccc; vertical-align: top; }';
echo 'tr:nth-child(even) { background-color: #D9E2F3; }';
echo '.valid { color: green; font-weight: bold; }';
echo '.invalid { color: red; font-weight: bold; }';
echo 'a { color: #0563C1; text-decoration: underline; }';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<table>';

// Header row
echo '<tr>';
echo '<th>No</th>';
echo '<th>Tanggal</th>';
echo '<th>Waktu</th>';
echo '<th>Nama Admin</th>';
echo '<th>Nama Project</th>';
echo '<th>Alamat Project</th>';
echo '<th>Akurasi GPS (m)</th>';
echo '<th>Jarak dari Project (m)</th>';
echo '<th>Radius Valid (m)</th>';
echo '<th>Status Validasi</th>';
echo '<th>Jenis Pekerjaan</th>';
echo '<th>Catatan</th>';
echo '<th>Foto</th>';
echo '</tr>';

// Data rows
$no = 1;
while ($report = $reportsResult['data']->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $no++ . '</td>';
    echo '<td>' . date('d/m/Y', strtotime($report['waktu_kunjungan'])) . '</td>';
    echo '<td>' . date('H:i:s', strtotime($report['waktu_kunjungan'])) . '</td>';
    echo '<td>' . htmlspecialchars($report['admin_name']) . '</td>';
    echo '<td>' . htmlspecialchars($report['nama_project']) . '</td>';
    echo '<td>' . htmlspecialchars($report['alamat']) . '</td>';
    echo '<td>' . number_format($report['accuracy'], 2) . '</td>';
    echo '<td>' . number_format($report['jarak_dari_project'], 2) . '</td>';
    echo '<td>' . $report['radius_valid'] . '</td>';
    echo '<td class="' . ($report['is_valid'] ? 'valid' : 'invalid') . '">' . ($report['is_valid'] ? 'Valid' : 'Invalid') . '</td>';
    echo '<td>' . htmlspecialchars($report['jenis_pekerjaan'] ?? '-') . '</td>';
    echo '<td>' . htmlspecialchars($report['catatan'] ?? '-') . '</td>';

    // Foto with clickable link
    if (!empty($report['foto_laporan'])) {
        $fotoUrl = $baseUrl . $report['foto_laporan'];
        echo '<td><a href="' . $fotoUrl . '" target="_blank">Lihat Foto</a></td>';
    } else {
        echo '<td>-</td>';
    }
    echo '</tr>';
}

echo '</table>';
echo '</body>';
echo '</html>';
exit;
?>
