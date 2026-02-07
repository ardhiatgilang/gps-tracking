<?php
/**
 * Database Migration Script
 * Jalankan file ini di browser untuk menambahkan kolom yang diperlukan
 */

require_once '../config/database.php';

echo "<h2>Database Migration</h2>";
echo "<pre>";

// Check and add jenis_pekerjaan column
echo "Checking 'jenis_pekerjaan' column in visit_reports table...\n";

$checkColumn = "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'visit_reports'
                AND COLUMN_NAME = 'jenis_pekerjaan'";

$result = executeQuery($checkColumn);

if ($result['success'] && $result['data']->num_rows === 0) {
    // Column doesn't exist, add it
    $addColumn = "ALTER TABLE visit_reports ADD COLUMN jenis_pekerjaan VARCHAR(50) DEFAULT NULL AFTER foto_timestamp";
    $addResult = executeQuery($addColumn);

    if ($addResult['success']) {
        echo "✅ SUCCESS: Column 'jenis_pekerjaan' has been added to visit_reports table.\n";
    } else {
        echo "❌ ERROR: Failed to add column. " . ($addResult['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "✅ Column 'jenis_pekerjaan' already exists. No action needed.\n";
}

echo "\n";
echo "Migration completed!\n";
echo "</pre>";

echo "<br><a href='../admin/laporan.php'>← Back to Laporan</a>";
?>
