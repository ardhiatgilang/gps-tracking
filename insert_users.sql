-- Insert User Data untuk Sistem GPS Tracking
-- Jalankan query ini di phpMyAdmin

USE gps_tracking_system;

-- Hapus data user lama jika ada
TRUNCATE TABLE users;

-- Insert users dengan password yang sudah di-hash
INSERT INTO users (username, password, nama_lengkap, email, role, status) VALUES
('admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 1', 'admin1@example.com', 'admin', 'active'),
('admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 2', 'admin2@example.com', 'admin', 'active'),
('admin3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Lapangan 3', 'admin3@example.com', 'admin', 'active'),
('supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor Utama', 'supervisor@example.com', 'supervisor', 'active');

-- Verifikasi data
SELECT id, username, nama_lengkap, role, status FROM users;
