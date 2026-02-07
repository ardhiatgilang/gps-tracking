-- Tambah kolom foto_profil ke tabel users
ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) DEFAULT NULL AFTER password;

-- Contoh update foto profil (opsional)
-- UPDATE users SET foto_profil = 'profile_1.jpg' WHERE id = 1;
