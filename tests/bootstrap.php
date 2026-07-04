<?php
/**
 * Bootstrap untuk PHPUnit - LACAKIN GPS Tracking System
 * Memuat fungsi-fungsi inti tanpa memicu koneksi database
 */

define('TESTING_MODE', true);

// Memuat fungsi-fungsi dari config/database.php (Haversine, sanitize, format, dll)
// Koneksi database tidak akan terbuat selama unit test karena getDBConnection()
// hanya dipanggil saat dibutuhkan (lazy initialization)
require_once dirname(__DIR__) . '/config/database.php';
