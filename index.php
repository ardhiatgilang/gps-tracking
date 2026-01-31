<?php
/**
 * Login Page
 * Halaman login untuk Admin Lapangan dan Supervisor
 */

require_once 'config/database.php';

startSecureSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: supervisor/index.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $query = "SELECT id, username, password, nama_lengkap, role, status FROM users WHERE username = ?";
        $result = executeQuery($query, "s", [$username]);

        if ($result['success'] && $result['data']->num_rows > 0) {
            $user = $result['data']->fetch_assoc();

            if ($user['status'] !== 'active') {
                $error = 'Akun Anda tidak aktif. Hubungi administrator.';
            } elseif (password_verify($password, $user['password'])) {
                // Login berhasil
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];

                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: supervisor/index.php');
                }
                exit();
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pelacakan Admin GPS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>Sistem Pelacakan Admin GPS</h1>
                <p>Analisis Produktivitas dengan Metode Haversine</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="Masukkan username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 13px; color: var(--text-secondary);">
                <strong>Demo Credentials:</strong><br>
                Admin: <code>admin1 / password123</code><br>
                Supervisor: <code>supervisor / password123</code>
            </div>
        </div>
    </div>
</body>
</html>
