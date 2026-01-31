-- Database untuk Sistem Pelacakan Admin Lapangan Berbasis GPS
-- Skripsi: Analisis Sistem Pelacakan Realtime dengan Haversine

CREATE DATABASE IF NOT EXISTS gps_tracking_system;
USE gps_tracking_system;

-- Tabel Users (Admin Lapangan dan Supervisor)
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

-- Tabel Lokasi Project
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

-- Tabel GPS Tracking (untuk analisis akurasi GPS)
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

-- Tabel Laporan Kunjungan
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

-- Tabel Rekap Harian (untuk analisis produktivitas)
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

-- Tabel Validasi Haversine (untuk analisis akurasi metode)
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

-- Tabel Session Tracking
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

-- Insert default users
INSERT INTO users (username, password, nama_lengkap, email, role) VALUES
('admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 1', 'admin1@example.com', 'admin'),
('admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 2', 'admin2@example.com', 'admin'),
('admin3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 3', 'admin3@example.com', 'admin'),
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor Utama', 'supervisor@example.com', 'supervisor');

-- Password untuk semua user di atas adalah: password123

-- Insert sample project locations (sesuaikan dengan lokasi real Anda)
INSERT INTO project_locations (nama_project, alamat, latitude, longitude, radius_valid) VALUES
('Project Site A', 'Jl. Contoh No. 1, Jakarta', -6.200000, 106.816666, 50),
('Project Site B', 'Jl. Contoh No. 2, Jakarta', -6.210000, 106.826666, 50),
('Project Site C', 'Jl. Contoh No. 3, Jakarta', -6.220000, 106.836666, 50),
('Project Site D', 'Jl. Contoh No. 4, Jakarta', -6.230000, 106.846666, 50),
('Project Site E', 'Jl. Contoh No. 5, Jakarta', -6.240000, 106.856666, 50);

-- View untuk analisis produktivitas
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

-- View untuk analisis akurasi GPS
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

-- View untuk monitoring realtime
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

-- Stored Procedure untuk menghitung jarak Haversine
DELIMITER //

CREATE FUNCTION calculate_haversine_distance(
    lat1 DECIMAL(10,8),
    lon1 DECIMAL(11,8),
    lat2 DECIMAL(10,8),
    lon2 DECIMAL(11,8)
) RETURNS FLOAT
DETERMINISTIC
BEGIN
    DECLARE earth_radius FLOAT DEFAULT 6371000; -- dalam meter
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
