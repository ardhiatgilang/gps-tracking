<?php
/**
 * Test Tracking GPS - Debug Tool
 */

require_once '../config/database.php';
checkRole('admin');

// Simulate GPS data
$testData = [
    'latitude' => -6.366579,
    'longitude' => 106.772236,
    'accuracy' => 15.5,
    'altitude' => 100,
    'speed' => 0,
    'heading' => 0,
    'timestamp' => date('Y-m-d H:i:s'),
    'location_type' => 'outdoor',
    'signal_strength' => 'high',
    'device_info' => 'Test Device',
    'battery_level' => 80,
    'network_type' => '4G'
];

echo "<h2>Test GPS Tracking API</h2>";
echo "<pre>";
echo "Sending test data to API...\n\n";
print_r($testData);

// Send to API
$ch = curl_init('http://localhost/gps-tracking/api/update_location.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . $_SERVER['HTTP_COOKIE']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\n--- RESPONSE ---\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

$result = json_decode($response, true);

if ($result['success']) {
    echo "\n✓ SUCCESS! Data berhasil dikirim ke database.\n";

    // Verify database
    $conn = getDBConnection();
    $checkQuery = "SELECT * FROM gps_tracking ORDER BY id DESC LIMIT 1";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        echo "\n--- DATA DI DATABASE ---\n";
        $row = $checkResult->fetch_assoc();
        print_r($row);
    }
} else {
    echo "\n✗ FAILED! " . $result['message'] . "\n";
}

echo "</pre>";
echo '<br><a href="index.php">Kembali ke Dashboard</a>';
?>
