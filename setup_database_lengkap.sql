-- ============================================================
-- SETUP DATABASE LENGKAP - GPS TRACKING SYSTEM
-- Hapus database lama dan buat ulang dengan data lengkap
-- ============================================================

-- HAPUS DATABASE LAMA JIKA ADA
DROP DATABASE IF EXISTS gps_tracking_system;

-- BUAT DATABASE BARU
CREATE DATABASE gps_tracking_system;
USE gps_tracking_system;

-- ============================================================
-- TABEL 1: USERS
-- ============================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    no_hp VARCHAR(15),
    role ENUM('admin', 'supervisor') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 2: PROJECT LOCATIONS
-- ============================================================
CREATE TABLE project_locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_project VARCHAR(100) NOT NULL,
    alamat TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius_valid INT DEFAULT 50 COMMENT 'Radius validasi dalam meter',
    deskripsi TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 3: GPS TRACKING
-- ============================================================
CREATE TABLE gps_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy FLOAT COMMENT 'Akurasi GPS dalam meter',
    altitude FLOAT,
    speed FLOAT COMMENT 'Kecepatan dalam m/s',
    heading FLOAT COMMENT 'Arah pergerakan dalam derajat',
    timestamp DATETIME NOT NULL,
    location_type ENUM('outdoor', 'indoor', 'urban', 'rural') DEFAULT 'outdoor',
    signal_strength ENUM('high', 'medium', 'low') DEFAULT 'medium',
    device_info VARCHAR(100),
    battery_level INT COMMENT 'Level baterai dalam persen',
    network_type VARCHAR(20) COMMENT 'WiFi, 4G, 3G, etc',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_timestamp (admin_id, timestamp),
    INDEX idx_timestamp (timestamp),
    INDEX idx_accuracy (accuracy)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 4: VISIT REPORTS
-- ============================================================
CREATE TABLE visit_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    project_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy FLOAT,
    jarak_dari_project FLOAT COMMENT 'Jarak dari titik project (Haversine) dalam meter',
    is_valid BOOLEAN DEFAULT FALSE COMMENT 'Apakah dalam radius valid',
    foto_laporan VARCHAR(255),
    foto_latitude DECIMAL(10, 8) COMMENT 'GPS dari EXIF foto',
    foto_longitude DECIMAL(11, 8) COMMENT 'GPS dari EXIF foto',
    foto_timestamp DATETIME COMMENT 'Timestamp dari EXIF foto',
    catatan TEXT,
    waktu_kunjungan DATETIME NOT NULL,
    waktu_submit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    durasi_kunjungan INT COMMENT 'Durasi dalam menit',
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at DATETIME,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES project_locations(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_admin_date (admin_id, waktu_kunjungan),
    INDEX idx_project (project_id),
    INDEX idx_valid (is_valid),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 5: DAILY SUMMARY
-- ============================================================
CREATE TABLE daily_summary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    tanggal DATE NOT NULL,
    total_jarak_tempuh FLOAT COMMENT 'Total jarak dalam kilometer',
    jumlah_kunjungan INT DEFAULT 0,
    jumlah_kunjungan_valid INT DEFAULT 0,
    durasi_kerja_menit INT COMMENT 'Durasi kerja dalam menit',
    waktu_mulai DATETIME,
    waktu_selesai DATETIME,
    efisiensi_score FLOAT COMMENT 'Kunjungan per jam',
    rata_rata_jarak_per_kunjungan FLOAT,
    success_rate FLOAT COMMENT 'Persentase kunjungan valid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_admin_date (admin_id, tanggal),
    INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 6: HAVERSINE VALIDATION
-- ============================================================
CREATE TABLE haversine_validation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_rute VARCHAR(100),
    lat1 DECIMAL(10, 8) NOT NULL,
    lon1 DECIMAL(11, 8) NOT NULL,
    lat2 DECIMAL(10, 8) NOT NULL,
    lon2 DECIMAL(11, 8) NOT NULL,
    jarak_aktual FLOAT COMMENT 'Jarak ground truth dalam meter',
    jarak_haversine FLOAT COMMENT 'Jarak hasil perhitungan Haversine',
    error_meter FLOAT COMMENT 'Selisih absolut',
    error_persen FLOAT COMMENT 'Persentase error',
    kategori_jarak ENUM('pendek', 'menengah', 'jauh') COMMENT 'Pendek <100m, Menengah 100m-5km, Jauh >5km',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kategori (kategori_jarak)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABEL 7: TRACKING SESSIONS
-- ============================================================
CREATE TABLE tracking_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    session_start DATETIME NOT NULL,
    session_end DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    total_tracking_points INT DEFAULT 0,
    avg_accuracy FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin_active (admin_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INSERT DATA USERS
-- Password untuk semua user: password123
-- ============================================================
INSERT INTO users (username, password, nama_lengkap, email, no_hp, role) VALUES
('admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 1', 'admin1@example.com', '081234567801', 'admin'),
('admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 2', 'admin2@example.com', '081234567802', 'admin'),
('admin3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 3', 'admin3@example.com', '081234567803', 'admin'),
('admin4', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 4', 'admin4@example.com', '081234567804', 'admin'),
('admin5', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 5', 'admin5@example.com', '081234567805', 'admin'),
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor Utama', 'supervisor@example.com', '081234567890', 'supervisor');

-- ============================================================
-- INSERT DATA PROJECT LOCATIONS
-- PENTING: Ganti koordinat ini dengan lokasi REAL Anda!
-- ============================================================
INSERT INTO project_locations (nama_project, alamat, latitude, longitude, radius_valid) VALUES
('Project Site A - Monas', 'Jl. Medan Merdeka, Jakarta Pusat', -6.1753924, 106.8271528, 50),
('Project Site B - Taman Mini', 'Jl. Taman Mini, Jakarta Timur', -6.3024934, 106.8951540, 50),
('Project Site C - Ancol', 'Jl. Lodan Timur, Jakarta Utara', -6.1223548, 106.8425931, 50),
('Project Site D - Ragunan', 'Jl. Harsono RM, Jakarta Selatan', -6.3108421, 106.8201534, 50),
('Project Site E - Kota Tua', 'Jl. Pintu Besar Utara, Jakarta Barat', -6.1343625, 106.8132476, 50),
('Project Site F - GBK', 'Jl. Pintu Satu Senayan, Jakarta Pusat', -6.2187924, 106.8020203, 50),
('Project Site G - TMII', 'Jl. Taman Mini Indonesia Indah, Jakarta Timur', -6.3027281, 106.8967204, 50),
('Project Site H - Sarinah', 'Jl. MH Thamrin, Jakarta Pusat', -6.1932469, 106.8229198, 50),
('Project Site I - Blok M', 'Jl. Blok M, Jakarta Selatan', -6.2443826, 106.7992463, 50),
('Project Site J - Kelapa Gading', 'Jl. Boulevard Barat, Jakarta Utara', -6.1580936, 106.8999342, 50);

-- ============================================================
-- VIEWS UNTUK ANALISIS
-- ============================================================

-- View Produktivitas Admin
CREATE VIEW v_produktivitas_admin AS
SELECT
    u.id AS admin_id,
    u.nama_lengkap,
    ds.tanggal,
    ds.total_jarak_tempuh,
    ds.jumlah_kunjungan,
    ds.jumlah_kunjungan_valid,
    ds.durasi_kerja_menit,
    ds.efisiensi_score,
    ds.success_rate,
    ROUND(ds.total_jarak_tempuh / NULLIF(ds.jumlah_kunjungan, 0), 2) AS avg_jarak_per_kunjungan
FROM daily_summary ds
JOIN users u ON ds.admin_id = u.id
WHERE u.role = 'admin'
ORDER BY ds.tanggal DESC, u.nama_lengkap;

-- View Akurasi GPS
CREATE VIEW v_akurasi_gps AS
SELECT
    location_type,
    signal_strength,
    COUNT(*) AS total_readings,
    ROUND(AVG(accuracy), 2) AS mean_accuracy,
    ROUND(MIN(accuracy), 2) AS min_accuracy,
    ROUND(MAX(accuracy), 2) AS max_accuracy,
    ROUND(STDDEV(accuracy), 2) AS std_dev_accuracy,
    ROUND(SUM(CASE WHEN accuracy <= 20 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS persen_akurasi_baik
FROM gps_tracking
GROUP BY location_type, signal_strength;

-- View Admin Realtime
CREATE VIEW v_admin_realtime AS
SELECT
    u.id,
    u.nama_lengkap,
    u.no_hp,
    gt.latitude,
    gt.longitude,
    gt.accuracy,
    gt.timestamp,
    gt.location_type,
    gt.signal_strength,
    TIMESTAMPDIFF(MINUTE, gt.timestamp, NOW()) AS menit_terakhir_update
FROM users u
LEFT JOIN gps_tracking gt ON u.id = gt.admin_id
WHERE u.role = 'admin'
    AND u.status = 'active'
    AND gt.id = (
        SELECT MAX(id)
        FROM gps_tracking
        WHERE admin_id = u.id
    );

-- ============================================================
-- STORED FUNCTION: HAVERSINE
-- ============================================================
DELIMITER //

CREATE FUNCTION calculate_haversine_distance(
    lat1 DECIMAL(10,8),
    lon1 DECIMAL(11,8),
    lat2 DECIMAL(10,8),
    lon2 DECIMAL(11,8)
) RETURNS FLOAT
DETERMINISTIC
BEGIN
    DECLARE earth_radius FLOAT DEFAULT 6371000;
    DECLARE dlat FLOAT;
    DECLARE dlon FLOAT;
    DECLARE a FLOAT;
    DECLARE c FLOAT;
    DECLARE distance FLOAT;

    SET dlat = RADIANS(lat2 - lat1);
    SET dlon = RADIANS(lon2 - lon1);

    SET a = SIN(dlat/2) * SIN(dlat/2) +
            COS(RADIANS(lat1)) * COS(RADIANS(lat2)) *
            SIN(dlon/2) * SIN(dlon/2);

    SET c = 2 * ATAN2(SQRT(a), SQRT(1-a));
    SET distance = earth_radius * c;

    RETURN distance;
END//

DELIMITER ;

-- ============================================================
-- VERIFIKASI DATA
-- ============================================================
SELECT '=== DATA USERS ===' AS info;
SELECT id, username, nama_lengkap, role, status FROM users;

SELECT '=== DATA PROJECT LOCATIONS ===' AS info;
SELECT id, nama_project, latitude, longitude, radius_valid FROM project_locations;

SELECT 'DATABASE SETUP BERHASIL!' AS status;
