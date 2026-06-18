<?php
/**
 * API: Export Analisis Produktivitas ke Excel
 */

require_once '../config/database.php';
checkRole('supervisor');

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date   = isset($_GET['end_date'])   ? $_GET['end_date']   : date('Y-m-d');

// Query Haversine distribution
$haversineQuery = "SELECT
                    CASE
                        WHEN jarak_dari_project <= 25  THEN '0-25m'
                        WHEN jarak_dari_project <= 50  THEN '26-50m'
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

// Query produktivitas
$produktivitasQuery = "SELECT
                        u.id,
                        u.nama_lengkap,
                        COUNT(DISTINCT DATE(vr.waktu_kunjungan)) as total_hari_kerja,
                        COUNT(vr.id) as total_kunjungan,
                        SUM(CASE WHEN vr.is_valid = 1 THEN 1 ELSE 0 END) as total_kunjungan_valid,
                        ROUND(COALESCE((
                            SELECT SUM(
                                6371 * 2 * ASIN(SQRT(
                                    POWER(SIN((RADIANS(pl2.latitude) - RADIANS(pl1.latitude))/2), 2) +
                                    COS(RADIANS(pl1.latitude)) * COS(RADIANS(pl2.latitude)) *
                                    POWER(SIN((RADIANS(pl2.longitude) - RADIANS(pl1.longitude))/2), 2)
                                )) * 1.5
                            )
                            FROM visit_reports a
                            JOIN project_locations pl1 ON a.project_id = pl1.id
                            JOIN visit_reports b ON b.admin_id = a.admin_id
                                AND DATE(b.waktu_kunjungan) = DATE(a.waktu_kunjungan)
                                AND b.waktu_kunjungan = (
                                    SELECT MIN(c.waktu_kunjungan) FROM visit_reports c
                                    WHERE c.admin_id = a.admin_id
                                    AND DATE(c.waktu_kunjungan) = DATE(a.waktu_kunjungan)
                                    AND c.waktu_kunjungan > a.waktu_kunjungan
                                )
                            JOIN project_locations pl2 ON b.project_id = pl2.id
                            WHERE a.admin_id = u.id
                            AND DATE(a.waktu_kunjungan) BETWEEN ? AND ?
                        ), 0), 2) as total_jarak,
                        ROUND(AVG(ds.efisiensi_score), 2) as avg_efisiensi,
                        ROUND(AVG(ds.success_rate), 2) as avg_success_rate,
                        ROUND(COALESCE((
                            SELECT SUM(
                                6371 * 2 * ASIN(SQRT(
                                    POWER(SIN((RADIANS(pl2.latitude) - RADIANS(pl1.latitude))/2), 2) +
                                    COS(RADIANS(pl1.latitude)) * COS(RADIANS(pl2.latitude)) *
                                    POWER(SIN((RADIANS(pl2.longitude) - RADIANS(pl1.longitude))/2), 2)
                                )) * 1.5
                            ) / NULLIF(COUNT(DISTINCT DATE(a2.waktu_kunjungan)), 0)
                            FROM visit_reports a2
                            JOIN project_locations pl1 ON a2.project_id = pl1.id
                            JOIN visit_reports b2 ON b2.admin_id = a2.admin_id
                                AND DATE(b2.waktu_kunjungan) = DATE(a2.waktu_kunjungan)
                                AND b2.waktu_kunjungan = (
                                    SELECT MIN(c2.waktu_kunjungan) FROM visit_reports c2
                                    WHERE c2.admin_id = a2.admin_id
                                    AND DATE(c2.waktu_kunjungan) = DATE(a2.waktu_kunjungan)
                                    AND c2.waktu_kunjungan > a2.waktu_kunjungan
                                )
                            JOIN project_locations pl2 ON b2.project_id = pl2.id
                            WHERE a2.admin_id = u.id
                            AND DATE(a2.waktu_kunjungan) BETWEEN ? AND ?
                        ), 0), 2) as avg_jarak_per_hari
                       FROM visit_reports vr
                       JOIN users u ON vr.admin_id = u.id
                       LEFT JOIN daily_summary ds ON ds.admin_id = u.id AND ds.tanggal BETWEEN ? AND ?
                       WHERE DATE(vr.waktu_kunjungan) BETWEEN ? AND ?
                       GROUP BY u.id, u.nama_lengkap
                       ORDER BY total_kunjungan DESC";
$produktivitasResult = executeQuery($produktivitasQuery, "ssssssss", [
    $start_date, $end_date,
    $start_date, $end_date,
    $start_date, $end_date,
    $start_date, $end_date
]);

// Generate filename
$filename = 'Analisis_Produktivitas_' . date('Ymd_His') . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
?>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { color: #1e3a8a; font-size: 14px; }
    h3 { color: #1e40af; font-size: 12px; background: #dbeafe; padding: 6px; margin-top: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 10px; }
    th { background: #1e3a8a; color: white; padding: 6px 10px; text-align: left; font-size: 11px; }
    td { padding: 5px 10px; border: 1px solid #d1d5db; font-size: 11px; }
    tr:nth-child(even) td { background: #f0f9ff; }
    .badge-valid   { color: #059669; font-weight: bold; }
    .badge-invalid { color: #dc2626; font-weight: bold; }
    .badge-warn    { color: #d97706; font-weight: bold; }
    .info-box { background: #f0f9ff; border: 1px solid #bae6fd; padding: 8px 12px; margin-bottom: 16px; font-size: 11px; }
</style>
</head>
<body>

<h2>Analisis Produktivitas &amp; Evaluasi Aktivitas Kerja</h2>
<p style="color:#6b7280; font-size:11px;">
    Periode: <strong><?php echo date('d/m/Y', strtotime($start_date)); ?></strong>
    s/d <strong><?php echo date('d/m/Y', strtotime($end_date)); ?></strong>
    &nbsp;|&nbsp; Dicetak: <?php echo date('d/m/Y H:i'); ?>
</p>

<!-- SECTION 1: HAVERSINE -->
<h3>1. Validasi Metode Haversine untuk Verifikasi Kunjungan</h3>
<p style="font-size:11px;"><strong>Tujuan:</strong> Mengevaluasi efektivitas metode Haversine dalam menghitung jarak dan memvalidasi kehadiran admin di lokasi project.</p>
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
        <?php while ($hav = $haversineResult['data']->fetch_assoc()):
            $interp = '';
            $class  = '';
            if ($hav['range_jarak'] == '0-25m')   { $interp = 'Sangat Valid - Admin berada di lokasi';    $class = 'badge-valid'; }
            elseif ($hav['range_jarak'] == '26-50m')  { $interp = 'Valid - Dalam radius acceptable';          $class = 'badge-valid'; }
            elseif ($hav['range_jarak'] == '51-100m') { $interp = 'Marginal - Perlu verifikasi manual';       $class = 'badge-warn'; }
            else                                       { $interp = 'Invalid - Di luar radius valid';           $class = 'badge-invalid'; }
        ?>
        <tr>
            <td><strong><?php echo $hav['range_jarak']; ?></strong></td>
            <td><?php echo $hav['jumlah']; ?></td>
            <td><?php echo $hav['avg_accuracy']; ?></td>
            <td class="<?php echo $class; ?>"><?php echo $interp; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- SECTION 2: PRODUKTIVITAS -->
<h3>2. Evaluasi Produktivitas Admin Lapangan</h3>
<p style="font-size:11px;"><strong>Tujuan:</strong> Mengevaluasi kinerja admin lapangan berdasarkan metrik jarak tempuh, jumlah kunjungan, dan efisiensi kerja.</p>
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Admin</th>
            <th>Hari Kerja</th>
            <th>Total Kunjungan</th>
            <th>Kunjungan Valid</th>
            <th>Total Jarak Antar Site (km)</th>
            <th>Avg Efisiensi (kunjungan/jam)</th>
            <th>Success Rate (%)</th>
            <th>Avg Jarak/Hari (km)</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while ($prod = $produktivitasResult['data']->fetch_assoc()):
            $rate  = $prod['avg_success_rate'];
            $rateClass = $rate >= 80 ? 'badge-valid' : ($rate >= 60 ? 'badge-warn' : 'badge-invalid');
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><strong><?php echo htmlspecialchars($prod['nama_lengkap']); ?></strong></td>
            <td><?php echo $prod['total_hari_kerja']; ?></td>
            <td><?php echo $prod['total_kunjungan']; ?></td>
            <td><?php echo $prod['total_kunjungan_valid']; ?></td>
            <td><?php echo $prod['total_jarak']; ?></td>
            <td><?php echo $prod['avg_efisiensi']; ?></td>
            <td class="<?php echo $rateClass; ?>"><?php echo $rate; ?>%</td>
            <td><?php echo $prod['avg_jarak_per_hari']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="info-box">
    <strong>Keterangan Metrik:</strong><br>
    &bull; <strong>Efisiensi:</strong> Jumlah kunjungan per jam kerja (semakin tinggi = semakin produktif)<br>
    &bull; <strong>Success Rate:</strong> <span class="badge-valid">≥80% = Baik</span> &nbsp;|&nbsp; <span class="badge-warn">60-79% = Cukup</span> &nbsp;|&nbsp; <span class="badge-invalid">&lt;60% = Perlu Perbaikan</span><br>
    &bull; <strong>Avg Jarak/Hari:</strong> Rata-rata total jarak antar site per hari kerja<br>
    &bull; <strong>Jarak</strong> dihitung dengan formula Haversine &times; faktor koreksi jalan 1,5
</div>

</body>
</html>
