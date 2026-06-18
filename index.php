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
    <title>Login - LACAKIN</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Splash Screen -->
    <div id="splash-screen">
        <div class="splash-content">
            <svg class="splash-logo" width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="splashPinGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3b82f6"/>
                        <stop offset="100%" style="stop-color:#1d4ed8"/>
                    </linearGradient>
                    <linearGradient id="splashCheckGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#10b981"/>
                        <stop offset="100%" style="stop-color:#059669"/>
                    </linearGradient>
                </defs>
                <path d="M50 5 C30 5 15 22 15 40 C15 60 50 95 50 95 C50 95 85 60 85 40 C85 22 70 5 50 5 Z"
                      fill="url(#splashPinGrad)" stroke="#1e40af" stroke-width="2"/>
                <circle cx="50" cy="38" r="22" fill="white"/>
                <path class="checkmark" d="M38 38 L46 46 L62 30"
                      stroke="url(#splashCheckGrad)" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1 class="splash-title">LACAKIN</h1>
            <div class="splash-loader"></div>
        </div>
    </div>

    <style>
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        #splash-screen.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .splash-content {
            text-align: center;
            animation: fadeInUp 0.8s ease;
        }

        .splash-logo {
            animation: bounceIn 1s ease;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));
        }

        .splash-title {
            color: white;
            font-size: 42px;
            font-weight: 800;
            margin: 20px 0 30px 0;
            letter-spacing: 4px;
            text-shadow: 0 4px 15px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease 0.3s both;
        }

        .splash-loader {
            width: 50px;
            height: 4px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            margin: 0 auto;
            overflow: hidden;
            animation: fadeInUp 0.8s ease 0.5s both;
        }

        .splash-loader::after {
            content: '';
            display: block;
            width: 50%;
            height: 100%;
            background: white;
            border-radius: 2px;
            animation: loading 1.2s ease-in-out infinite;
        }

        .checkmark {
            stroke-dasharray: 50;
            stroke-dashoffset: 50;
            animation: drawCheck 0.8s ease 0.5s forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes loading {
            0% {
                transform: translateX(-100%);
            }
            50% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(200%);
            }
        }
    </style>

    <div class="login-container">
        <div class="login-box">
            <div class="login-header" style="display: flex; align-items: center; justify-content: center; gap: 12px; padding: 20px 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px;">
                <!-- Logo LACAKIN -->
                <svg width="45" height="45" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="pinGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6"/>
                            <stop offset="100%" style="stop-color:#1d4ed8"/>
                        </linearGradient>
                        <linearGradient id="checkGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#10b981"/>
                            <stop offset="100%" style="stop-color:#059669"/>
                        </linearGradient>
                    </defs>
                    <path d="M50 5 C30 5 15 22 15 40 C15 60 50 95 50 95 C50 95 85 60 85 40 C85 22 70 5 50 5 Z"
                          fill="url(#pinGradient)" stroke="#1e40af" stroke-width="2"/>
                    <circle cx="50" cy="38" r="22" fill="white"/>
                    <path d="M38 38 L46 46 L62 30"
                          stroke="url(#checkGradient)" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 style="margin: 0; font-size: 28px; font-weight: 700; color: #1f2937;">LACAKIN</h1>
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
                    <div style="position: relative;">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Masukkan password" required style="padding-right: 44px;">
                        <button type="button" onclick="togglePassword()" id="toggleBtn"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#6b7280;padding:4px;">
                            <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="eyeOffIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

        </div>
    </div>

    <script>
        // Hide splash screen after animation completes
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('splash-screen').classList.add('hidden');
            }, 2000); // 2 seconds splash duration
        });

        // Toggle show/hide password
        function togglePassword() {
            const input = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                input.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }
    </script>
</body>
</html>
