<?php
/**
 * Logout Script
 * Menghapus session dan redirect ke halaman login
 */

require_once 'config/database.php';

startSecureSession();

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke login
header('Location: index.php');
exit();
?>
