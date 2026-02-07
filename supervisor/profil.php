<?php
/**
 * Halaman Edit Profil Supervisor
 * Supervisor dapat mengubah foto profil dan nama
 */

require_once '../config/database.php';
checkRole('supervisor');

$user = getCurrentUser();
$success = '';
$error = '';

/**
 * Cek apakah GD Library tersedia
 */
function isGdAvailable() {
    return extension_loaded('gd') && function_exists('imagecreatefromjpeg');
}

/**
 * Fungsi untuk mengkompress dan meresize gambar
 */
function compressImage($source, $destination, $maxWidth = 400, $maxHeight = 400, $quality = 80) {
    if (!isGdAvailable()) {
        return ['success' => false, 'compressed' => false, 'extension' => '', 'error' => 'GD not available'];
    }

    if (!file_exists($source)) {
        return ['success' => false, 'compressed' => false, 'extension' => '', 'error' => 'Source file not found'];
    }

    $imageInfo = @getimagesize($source);
    if (!$imageInfo) {
        return ['success' => false, 'compressed' => false, 'extension' => '', 'error' => 'Cannot read image info'];
    }

    $mime = $imageInfo['mime'];
    $width = $imageInfo[0];
    $height = $imageInfo[1];

    $image = null;
    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($source);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = @imagecreatefromwebp($source);
            }
            break;
    }

    if (!$image) {
        return ['success' => false, 'compressed' => false, 'extension' => '', 'error' => 'Cannot create image from source'];
    }

    $ratio = min($maxWidth / $width, $maxHeight / $height);

    if ($ratio < 1) {
        $newWidth = round($width * $ratio);
        $newHeight = round($height * $ratio);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    if ($mime === 'image/png') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $result = @imagejpeg($newImage, $destination, $quality);

    imagedestroy($image);
    imagedestroy($newImage);

    if (!$result) {
        return ['success' => false, 'compressed' => false, 'extension' => '', 'error' => 'Failed to save compressed image'];
    }

    return ['success' => true, 'compressed' => true, 'extension' => 'jpg', 'error' => ''];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $nip = trim($_POST['nip'] ?? '');
    $divisi = trim($_POST['divisi'] ?? '');

    if (empty($nama_lengkap)) {
        $error = 'Nama lengkap tidak boleh kosong';
    } else {
        $foto_profil = $user['foto_profil'] ?? null;

        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 10 * 1024 * 1024;

            $file_type = $_FILES['foto_profil']['type'];
            $file_size = $_FILES['foto_profil']['size'];

            if (!in_array($file_type, $allowed_types)) {
                $error = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
            } elseif ($file_size > $max_size) {
                $error = 'Ukuran file terlalu besar. Maksimal 10MB.';
            } else {
                $uploadSuccess = false;
                $new_filename = '';

                if (isGdAvailable()) {
                    $new_filename = 'profile_' . $user['id'] . '_' . time() . '.jpg';
                    $upload_dir = dirname(__DIR__) . '/uploads/profile/';

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $upload_path = $upload_dir . $new_filename;

                    $compressResult = compressImage($_FILES['foto_profil']['tmp_name'], $upload_path, 400, 400, 85);
                    $uploadSuccess = $compressResult['success'];

                    if (!$uploadSuccess && !empty($compressResult['error'])) {
                        $error = 'Gagal mengkompress foto: ' . $compressResult['error'];
                    }
                } else {
                    if ($file_size > 2 * 1024 * 1024) {
                        $error = 'Ukuran file terlalu besar. Maksimal 2MB (kompresi tidak tersedia).';
                    } else {
                        $extension = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
                        $new_filename = 'profile_' . $user['id'] . '_' . time() . '.' . $extension;
                        $upload_dir = dirname(__DIR__) . '/uploads/profile/';

                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }

                        $upload_path = $upload_dir . $new_filename;
                        $uploadSuccess = move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path);
                    }
                }

                if ($uploadSuccess && empty($error)) {
                    $old_foto = $user['foto_profil'] ?? null;
                    if ($old_foto) {
                        $old_photo_path = dirname(__DIR__) . '/uploads/profile/' . $old_foto;
                        if (file_exists($old_photo_path)) {
                            unlink($old_photo_path);
                        }
                    }
                    $foto_profil = $new_filename;
                } elseif (empty($error)) {
                    $error = 'Gagal mengupload foto. Silakan coba lagi.';
                }
            }
        }

        if (empty($error)) {
            $updateQuery = "UPDATE users SET nama_lengkap = ?, foto_profil = ?, nip = ?, divisi = ? WHERE id = ?";
            $result = executeQuery($updateQuery, "ssssi", [$nama_lengkap, $foto_profil, $nip, $divisi, $user['id']]);

            if ($result['success']) {
                $success = 'Profil berhasil diperbarui!';
                $user = getCurrentUser();
            } else {
                $error = 'Gagal menyimpan perubahan. Silakan coba lagi.';
            }
        }
    }
}

$initials = strtoupper(substr($user['nama_lengkap'], 0, 1));
if (strpos($user['nama_lengkap'], ' ') !== false) {
    $parts = explode(' ', $user['nama_lengkap']);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Supervisor</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-edit-container {
            max-width: 500px;
            margin: 0 auto;
        }
        .profile-photo-section {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px 12px 0 0;
        }
        .profile-photo-wrapper {
            position: relative;
            display: inline-block;
        }
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 600;
            border: 4px solid white;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.3);
            overflow: hidden;
        }
        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-upload-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #3b82f6;
            border: 3px solid white;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .photo-upload-btn:hover {
            background: #1d4ed8;
            transform: scale(1.1);
        }
        .profile-form-section {
            padding: 25px;
        }
        .photo-preview {
            display: none;
            margin-top: 15px;
            padding: 10px;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            font-size: 13px;
            color: #166534;
        }
        .photo-preview.active {
            display: block;
        }
        .btn-save {
            width: 100%;
            padding: 14px;
            font-size: 16px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #6b7280;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .back-link:hover {
            color: #3b82f6;
        }
        .photo-options-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .photo-options-modal.active {
            display: flex;
        }
        .photo-options-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 320px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideUp 0.3s ease;
        }
        @keyframes modalSlideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .photo-options-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #1f2937;
        }
        .photo-options-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .photo-options-close:hover {
            color: #1f2937;
        }
        .photo-options-body {
            padding: 12px;
        }
        .photo-option-btn {
            display: flex;
            align-items: center;
            gap: 14px;
            width: 100%;
            padding: 14px 16px;
            background: #f9fafb;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
        }
        .photo-option-btn:last-child {
            margin-bottom: 0;
        }
        .photo-option-btn:hover {
            background: #e0f2fe;
        }
        .photo-option-btn svg {
            color: #3b82f6;
        }
        .photo-option-btn span {
            font-size: 15px;
            font-weight: 500;
            color: #374151;
        }
        .camera-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #000;
            z-index: 1001;
            flex-direction: column;
        }
        .camera-modal.active {
            display: flex;
        }
        .camera-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: rgba(0,0,0,0.8);
            color: white;
        }
        .camera-header span {
            font-weight: 600;
        }
        .camera-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .camera-preview {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .camera-preview video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .camera-preview canvas {
            display: none;
        }
        .camera-controls {
            padding: 20px;
            background: rgba(0,0,0,0.8);
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .camera-capture-btn {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: white;
            border: 4px solid #3b82f6;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .camera-capture-btn:hover {
            transform: scale(1.1);
            background: #e0f2fe;
        }
        .camera-capture-btn:active {
            transform: scale(0.95);
        }
        .camera-switch-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            align-self: center;
        }
        .camera-switch-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .camera-error {
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        .camera-error svg {
            margin-bottom: 15px;
            opacity: 0.7;
        }
        .camera-error p {
            margin: 0 0 20px;
            opacity: 0.9;
        }
        .camera-error button {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .photo-lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 1002;
            align-items: center;
            justify-content: center;
            cursor: zoom-out;
        }
        .photo-lightbox.active {
            display: flex;
        }
        .photo-lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            animation: lightboxZoomIn 0.3s ease;
        }
        @keyframes lightboxZoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .photo-lightbox-content img {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .photo-lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: white;
            font-size: 32px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .photo-lightbox-close:hover {
            opacity: 1;
        }
        .photo-lightbox-name {
            position: absolute;
            bottom: -40px;
            left: 0;
            right: 0;
            text-align: center;
            color: white;
            font-size: 14px;
            opacity: 0.8;
        }
        .profile-photo.has-photo {
            cursor: zoom-in;
        }
        .profile-photo.has-photo:hover {
            transform: scale(1.02);
            transition: transform 0.2s;
        }
    </style>
</head>
<body>
    <?php include '../includes/page-loader.php'; ?>

    <!-- Navbar -->
    <nav class="navbar">
        <a href="index.php" class="navbar-brand" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: inherit;">
            <svg width="28" height="28" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="pinGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#3b82f6"/><stop offset="100%" style="stop-color:#1d4ed8"/>
                    </linearGradient>
                    <linearGradient id="checkGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#10b981"/><stop offset="100%" style="stop-color:#059669"/>
                    </linearGradient>
                </defs>
                <path d="M50 5 C30 5 15 22 15 40 C15 60 50 95 50 95 C50 95 85 60 85 40 C85 22 70 5 50 5 Z" fill="url(#pinGrad)" stroke="#1e40af" stroke-width="2"/>
                <circle cx="50" cy="38" r="22" fill="white"/>
                <path d="M38 38 L46 46 L62 30" stroke="url(#checkGrad)" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span>LACAKIN</span>
        </a>
        <div class="navbar-menu">
            <a href="index.php">Dashboard</a>
            <a href="monitoring.php">Monitoring Realtime</a>
            <a href="laporan.php">Laporan</a>
            <a href="analisis.php">Analisis Produktivitas</a>
        </div>
        <div class="navbar-user">
            <div class="profile-trigger" onclick="toggleProfileDropdown()">
                <div class="profile-avatar">
                    <?php if (!empty($user['foto_profil']) && file_exists('../uploads/profile/' . $user['foto_profil'])): ?>
                        <img src="../uploads/profile/<?php echo htmlspecialchars($user['foto_profil']); ?>" alt="Foto Profil">
                    <?php else: ?>
                        <?php echo $initials; ?>
                    <?php endif; ?>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-dropdown-header">
                    <div class="profile-dropdown-avatar">
                        <?php if (!empty($user['foto_profil']) && file_exists('../uploads/profile/' . $user['foto_profil'])): ?>
                            <img src="../uploads/profile/<?php echo htmlspecialchars($user['foto_profil']); ?>" alt="Foto Profil">
                        <?php else: ?>
                            <?php echo $initials; ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-dropdown-name"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                    <div class="profile-dropdown-role">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Supervisor
                    </div>
                </div>
                <div class="profile-dropdown-body">
                    <a href="profil.php" class="profile-dropdown-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit Profil
                    </a>
                    <div class="profile-dropdown-divider"></div>
                    <a href="../logout.php" class="profile-dropdown-item logout">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container">
        <a href="index.php" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Kembali ke Dashboard
        </a>

        <div class="profile-edit-container">
            <div class="card">
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin: 20px 20px 0;">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger" style="margin: 20px 20px 0;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-photo-section">
                        <div class="profile-photo-wrapper">
                            <div class="profile-photo <?php echo (!empty($user['foto_profil']) && file_exists('../uploads/profile/' . $user['foto_profil'])) ? 'has-photo' : ''; ?>" id="photoPreviewContainer" onclick="openPhotoLightbox()">
                                <?php if (!empty($user['foto_profil']) && file_exists('../uploads/profile/' . $user['foto_profil'])): ?>
                                    <img src="../uploads/profile/<?php echo htmlspecialchars($user['foto_profil']); ?>" alt="Foto Profil" id="photoPreview">
                                <?php else: ?>
                                    <span id="initialsDisplay"><?php echo $initials; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="photo-upload-btn" onclick="showPhotoOptions()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                            </div>
                            <input type="file" name="foto_profil" id="fotoInputGallery" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                        </div>

                        <!-- Photo Options Modal -->
                        <div class="photo-options-modal" id="photoOptionsModal">
                            <div class="photo-options-content">
                                <div class="photo-options-header">
                                    <span>Pilih Foto</span>
                                    <button type="button" class="photo-options-close" onclick="hidePhotoOptions()">&times;</button>
                                </div>
                                <div class="photo-options-body">
                                    <button type="button" class="photo-option-btn" onclick="selectFromGallery()">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                        <span>Pilih dari Galeri</span>
                                    </button>
                                    <button type="button" class="photo-option-btn" onclick="takeFromCamera()">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                            <circle cx="12" cy="13" r="4"></circle>
                                        </svg>
                                        <span>Ambil dari Kamera</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Camera Modal -->
                        <div class="camera-modal" id="cameraModal">
                            <div class="camera-header">
                                <span>Ambil Foto</span>
                                <button type="button" class="camera-close" onclick="closeCamera()">&times;</button>
                            </div>
                            <div class="camera-preview" id="cameraPreview">
                                <video id="cameraVideo" autoplay playsinline></video>
                                <canvas id="cameraCanvas"></canvas>
                                <div class="camera-error" id="cameraError" style="display:none;">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                        <circle cx="12" cy="13" r="4"></circle>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                    <p id="cameraErrorMsg">Tidak dapat mengakses kamera</p>
                                    <button type="button" onclick="closeCamera()">Tutup</button>
                                </div>
                            </div>
                            <div class="camera-controls" id="cameraControls">
                                <button type="button" class="camera-switch-btn" onclick="switchCamera()" id="switchCameraBtn" title="Ganti Kamera">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 2v6h-6"></path>
                                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                                        <path d="M3 22v-6h6"></path>
                                        <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                                    </svg>
                                </button>
                                <button type="button" class="camera-capture-btn" onclick="capturePhoto()" title="Ambil Foto">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                        <circle cx="12" cy="13" r="4"></circle>
                                    </svg>
                                </button>
                                <div style="width: 50px;"></div>
                            </div>
                        </div>

                        <!-- Photo Lightbox Preview -->
                        <div class="photo-lightbox" id="photoLightbox" onclick="closePhotoLightbox(event)">
                            <div class="photo-lightbox-content">
                                <button type="button" class="photo-lightbox-close" onclick="closePhotoLightbox(event)">&times;</button>
                                <img src="" alt="Preview Foto Profil" id="lightboxImage">
                                <div class="photo-lightbox-name" id="lightboxName"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                            </div>
                        </div>

                        <div class="photo-preview" id="photoInfo">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Foto baru dipilih: <span id="photoName"></span>
                        </div>
                    </div>

                    <div class="profile-form-section">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" name="nip" class="form-control" value="<?php echo htmlspecialchars($user['nip'] ?? ''); ?>" placeholder="Masukkan NIP">
                        </div>

                        <div class="form-group">
                            <label>Divisi</label>
                            <input type="text" name="divisi" class="form-control" value="<?php echo htmlspecialchars($user['divisi'] ?? ''); ?>" placeholder="Masukkan Divisi">
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: #f3f4f6;">
                            <small style="color: #6b7280; font-size: 12px;">Username tidak dapat diubah</small>
                        </div>

                        <div class="form-group">
                            <label>Jabatan</label>
                            <input type="text" class="form-control" value="Supervisor" disabled style="background: #f3f4f6;">
                        </div>

                        <button type="submit" class="btn btn-primary btn-save">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 8px;">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleProfileDropdown() {
            document.getElementById('profileDropdown').classList.toggle('active');
        }
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profileDropdown');
            const trigger = document.querySelector('.profile-trigger');
            if (trigger && !trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        function showPhotoOptions() {
            document.getElementById('photoOptionsModal').classList.add('active');
        }

        function hidePhotoOptions() {
            document.getElementById('photoOptionsModal').classList.remove('active');
        }

        function selectFromGallery() {
            hidePhotoOptions();
            document.getElementById('fotoInputGallery').click();
        }

        let cameraStream = null;
        let currentFacingMode = 'user';

        function takeFromCamera() {
            hidePhotoOptions();
            openCamera();
        }

        async function openCamera() {
            const modal = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');
            const errorDiv = document.getElementById('cameraError');
            const controls = document.getElementById('cameraControls');

            modal.classList.add('active');
            errorDiv.style.display = 'none';
            video.style.display = 'block';
            controls.style.display = 'flex';

            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: currentFacingMode,
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                });

                video.srcObject = cameraStream;

                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(d => d.kind === 'videoinput');
                document.getElementById('switchCameraBtn').style.display = videoDevices.length > 1 ? 'flex' : 'none';

            } catch (err) {
                console.error('Camera error:', err);
                video.style.display = 'none';
                controls.style.display = 'none';
                errorDiv.style.display = 'block';

                let errorMsg = 'Tidak dapat mengakses kamera';
                if (err.name === 'NotAllowedError') {
                    errorMsg = 'Akses kamera ditolak. Silakan izinkan akses kamera di pengaturan browser.';
                } else if (err.name === 'NotFoundError') {
                    errorMsg = 'Kamera tidak ditemukan pada perangkat ini.';
                } else if (err.name === 'NotReadableError') {
                    errorMsg = 'Kamera sedang digunakan aplikasi lain.';
                }
                document.getElementById('cameraErrorMsg').textContent = errorMsg;
            }
        }

        function closeCamera() {
            const modal = document.getElementById('cameraModal');
            const video = document.getElementById('cameraVideo');

            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }

            video.srcObject = null;
            modal.classList.remove('active');
        }

        async function switchCamera() {
            currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';

            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }

            await openCamera();
        }

        function capturePhoto() {
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const ctx = canvas.getContext('2d');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            ctx.drawImage(video, 0, 0);

            canvas.toBlob(function(blob) {
                const file = new File([blob], 'camera_photo_' + Date.now() + '.jpg', { type: 'image/jpeg' });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                document.getElementById('fotoInputGallery').files = dataTransfer.files;

                handleFileSelect(file);

                closeCamera();
            }, 'image/jpeg', 0.9);
        }

        document.getElementById('photoOptionsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hidePhotoOptions();
            }
        });

        document.getElementById('fotoInputGallery').addEventListener('change', function(e) {
            handleFileSelect(e.target.files[0]);
        });

        function handleFileSelect(file) {
            if (file) {
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                const fileSizeKB = (file.size / 1024).toFixed(0);
                const sizeText = file.size > 1024 * 1024 ? `${fileSizeMB} MB` : `${fileSizeKB} KB`;

                document.getElementById('photoName').textContent = `${file.name} (${sizeText})`;
                document.getElementById('photoInfo').classList.add('active');

                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.getElementById('photoPreviewContainer');
                    const initials = document.getElementById('initialsDisplay');
                    let img = document.getElementById('photoPreview');

                    if (initials) initials.style.display = 'none';

                    if (!img) {
                        img = document.createElement('img');
                        img.id = 'photoPreview';
                        img.alt = 'Preview';
                        container.appendChild(img);
                    }
                    img.src = e.target.result;
                    img.style.display = 'block';

                    container.classList.add('has-photo');
                };
                reader.readAsDataURL(file);
            }
        }

        function openPhotoLightbox() {
            const photoPreview = document.getElementById('photoPreview');
            const lightbox = document.getElementById('photoLightbox');
            const lightboxImage = document.getElementById('lightboxImage');

            if (photoPreview && photoPreview.src) {
                lightboxImage.src = photoPreview.src;
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closePhotoLightbox(event) {
            if (event && event.target.tagName === 'IMG') {
                return;
            }
            const lightbox = document.getElementById('photoLightbox');
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePhotoLightbox();
                closeCamera();
                hidePhotoOptions();
            }
        });
    </script>
</body>
</html>
