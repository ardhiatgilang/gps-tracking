-- Tambah tabel office_location (titik koordinat kantor pusat / central point)
CREATE TABLE IF NOT EXISTS office_location (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kantor VARCHAR(100) NOT NULL DEFAULT 'Kantor Pusat',
    alamat TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius_valid INT DEFAULT 100 COMMENT 'Radius validasi dari kantor dalam meter',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data awal kantor pusat (bisa diubah lewat halaman Supervisor > Pengaturan Kantor)
INSERT INTO office_location (nama_kantor, alamat, latitude, longitude, radius_valid)
SELECT 'Kantor Pusat',
       'Jakarta Mori Tower, Jl. Jend. Sudirman No.40-41 19th floor, RT.14/RW.1, Bend. Hilir, Kecamatan Tanah Abang, Kota Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10210',
       -6.21545, 106.81732, 100
WHERE NOT EXISTS (SELECT 1 FROM office_location);
