-- Tambah kolom jenis_pekerjaan ke tabel visit_reports
ALTER TABLE visit_reports ADD COLUMN jenis_pekerjaan VARCHAR(50) DEFAULT NULL AFTER foto_timestamp;

-- Contoh update jenis pekerjaan (opsional)
-- UPDATE visit_reports SET jenis_pekerjaan = 'Antar Invoice' WHERE id = 1;
