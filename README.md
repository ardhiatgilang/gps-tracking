# Sistem Pelacakan Admin Lapangan Berbasis GPS

## Judul Skripsi
**ANALISIS SISTEM PELACAKAN REALTIME BERBASIS GPS DENGAN HAVERSINE UNTUK EVALUASI PRODUKTIVITAS ADMIN LAPANGAN**

---

## Deskripsi Proyek

Sistem pelacakan admin lapangan berbasis website yang memanfaatkan teknologi GPS pada smartphone untuk memantau aktivitas admin lapangan secara realtime. Sistem ini digunakan untuk memastikan kehadiran admin di lokasi project serta mengevaluasi produktivitas kerja berdasarkan data pergerakan dan kunjungan lapangan.

### Fokus Penelitian
1. **Akurasi sistem pelacakan GPS** - Menganalisis variasi akurasi GPS pada berbagai kondisi lingkungan
2. **Validasi metode Haversine** - Mengevaluasi efektivitas metode Haversine dalam menghitung jarak dan memvalidasi kehadiran
3. **Evaluasi produktivitas** - Menganalisis kinerja admin lapangan berdasarkan statistik deskriptif (jarak tempuh, jumlah kunjungan, durasi kerja)

---

## Teknologi yang Digunakan

- **Frontend**: HTML5, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **GPS**: HTML5 Geolocation API
- **Peta**: Leaflet.js + OpenStreetMap (gratis & open-source)
- **Perhitungan Jarak**: Metode Haversine
- **Realtime Tracking**: AJAX Polling
- **Server Lokal**: XAMPP
- **Charts**: Chart.js

Semua teknologi yang digunakan bersifat **gratis dan open-source**.

---

## Fitur Utama

### 1. Admin Lapangan
- Login menggunakan smartphone
- Mengaktifkan GPS tracking realtime
- Mengirim koordinat lokasi setiap 30 detik ke server
- Membuat laporan kunjungan harian dengan:
  - Koordinat GPS (latitude & longitude)
  - Foto aktual dari kamera smartphone
  - Validasi jarak menggunakan Haversine
  - Timestamp otomatis
- Melihat riwayat kunjungan
- Dashboard produktivitas personal

### 2. Supervisor
- Login melalui desktop/web
- Monitoring realtime posisi semua admin pada peta
- Melihat status online/offline admin
- Melihat laporan kunjungan beserta foto dan lokasi
- Analisis produktivitas dengan statistik deskriptif:
  - Akurasi GPS berdasarkan kondisi lingkungan
  - Validasi metode Haversine
  - Evaluasi produktivitas (jarak, kunjungan, efisiensi)
- Export dan cetak laporan

### 3. Validasi Kunjungan
- Perhitungan jarak menggunakan **metode Haversine**
- Validasi radius (default: ≤ 50 meter)
- Ekstraksi EXIF data dari foto (GPS coordinates, timestamp)
- Warning untuk akurasi GPS yang buruk (> 50m)

---

## Struktur Folder

```
Website Pelacakan Admin Berbasis GPS/
├── admin/                      # Halaman admin lapangan
│   ├── index.php              # Dashboard admin
│   ├── tracking.php           # GPS tracking realtime
│   ├── laporan.php            # Submit laporan kunjungan
│   └── riwayat.php            # Riwayat laporan
├── supervisor/                 # Halaman supervisor
│   ├── index.php              # Dashboard supervisor
│   ├── monitoring.php         # Monitoring realtime
│   ├── laporan.php            # Lihat semua laporan
│   └── analisis.php           # Analisis produktivitas
├── api/                        # API endpoints
│   ├── update_location.php    # Update GPS tracking
│   ├── submit_report.php      # Submit laporan kunjungan
│   └── get_tracking_data.php  # Get data tracking realtime
├── assets/
│   ├── css/style.css          # Stylesheet responsive
│   └── js/tracking.js         # JavaScript GPS tracking
├── config/
│   └── database.php           # Konfigurasi database & helper functions
├── uploads/
│   └── laporan/               # Folder untuk foto laporan
├── database.sql               # Schema database lengkap
├── index.php                  # Halaman login
├── logout.php                 # Logout handler
└── README.md                  # Dokumentasi (file ini)
```

---

## Instalasi & Setup

### 1. Persyaratan Sistem
- XAMPP (PHP 7.4+ dan MySQL 5.7+)
- Browser modern (Chrome, Firefox, Safari, Edge)
- Smartphone dengan GPS untuk testing admin lapangan

### 2. Langkah Instalasi

#### A. Setup Database
1. Buka **phpMyAdmin** (http://localhost/phpmyadmin)
2. Buat database baru atau import langsung file `database.sql`:
   - Klik tab **Import**
   - Pilih file `database.sql`
   - Klik **Go**

Database akan otomatis dibuat dengan nama `gps_tracking_system` beserta:
- Tabel-tabel yang diperlukan
- User default untuk testing
- Sample data lokasi project
- View dan stored procedure untuk analisis

#### B. Konfigurasi Database (Opsional)
Jika kredensial database berbeda, edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Ubah sesuai user MySQL Anda
define('DB_PASS', '');          // Ubah sesuai password MySQL Anda
define('DB_NAME', 'gps_tracking_system');
```

#### C. Setup Folder
1. Copy folder proyek ke `C:\xampp\htdocs\`
2. Pastikan folder `uploads/laporan/` memiliki permission write (777 di Linux)
3. Di Windows/XAMPP biasanya sudah otomatis

#### D. Jalankan Aplikasi
1. Start **Apache** dan **MySQL** di XAMPP Control Panel
2. Buka browser dan akses:
   ```
   http://localhost/Website Pelacakan Admin Berbasis GPS/
   ```

### 3. Login Credentials Default

**Admin Lapangan:**
- Username: `admin1`
- Password: `password123`

**Supervisor:**
- Username: `supervisor`
- Password: `password123`

> **Note**: Password di-hash menggunakan bcrypt untuk keamanan. Untuk membuat user baru, gunakan `password_hash()` di PHP.

---

## Penggunaan Sistem

### Untuk Admin Lapangan (via Smartphone)

1. **Login**
   - Buka aplikasi di browser smartphone
   - Login dengan kredensial admin

2. **Aktifkan GPS Tracking**
   - Masuk ke menu **Tracking GPS**
   - Izinkan akses lokasi ketika diminta browser
   - Klik **Mulai Tracking**
   - Posisi akan dikirim ke server setiap 30 detik

3. **Submit Laporan Kunjungan**
   - Masuk ke menu **Buat Laporan**
   - Klik **Dapatkan Lokasi Saya** untuk mengambil GPS
   - Pilih project yang dikunjungi
   - Ambil foto menggunakan kamera smartphone
   - Isi catatan (opsional)
   - Klik **Submit Laporan**
   - Sistem akan validasi jarak menggunakan Haversine

4. **Tips untuk Akurasi GPS Optimal**
   - Pastikan berada di area **outdoor**
   - Hindari gedung tinggi atau pohon besar
   - Tunggu beberapa detik hingga GPS stabil (akurasi ≤ 20m)
   - Gunakan koneksi internet yang stabil

### Untuk Supervisor (via Desktop/Laptop)

1. **Dashboard**
   - Lihat overview aktivitas semua admin
   - Statistik kunjungan hari ini
   - Produktivitas admin

2. **Monitoring Realtime**
   - Lihat posisi semua admin pada peta
   - Status online/offline
   - Update otomatis setiap 30 detik

3. **Lihat Laporan**
   - Filter berdasarkan tanggal, admin, status
   - Lihat detail kunjungan dan foto
   - Verifikasi laporan

4. **Analisis Produktivitas**
   - Pilih periode analisis
   - Lihat statistik deskriptif:
     - Akurasi GPS berdasarkan kondisi lingkungan
     - Validasi metode Haversine
     - Produktivitas admin (jarak, kunjungan, efisiensi)
   - Visualisasi dengan grafik (Chart.js)
   - Cetak laporan untuk dokumentasi skripsi

---

## Metodologi Penelitian

### 1. Pengukuran Akurasi GPS

**Data yang Dicatat:**
- Latitude & Longitude
- Accuracy (meter)
- Timestamp
- Location type (outdoor, indoor, urban, rural)
- Signal strength (high, medium, low)

**Analisis yang Dilakukan:**
- Mean accuracy per kondisi lingkungan
- Minimum, maximum, standard deviation
- Persentase akurasi baik (≤ 20m)

### 2. Validasi Metode Haversine

**Formula Haversine:**
```
a = sin²(Δlat/2) + cos(lat1) × cos(lat2) × sin²(Δlon/2)
c = 2 × atan2(√a, √(1-a))
d = R × c
```
Dimana R = radius bumi (6,371 km)

**Validasi:**
- Bandingkan jarak Haversine vs jarak aktual
- Hitung Mean Absolute Error (MAE)
- Hitung Root Mean Square Error (RMSE)
- Analisis distribusi jarak kunjungan

### 3. Evaluasi Produktivitas

**Metrik yang Digunakan:**
- **Total Jarak Tempuh** (km)
- **Jumlah Kunjungan** (valid vs total)
- **Durasi Kerja** (menit)
- **Efisiensi Score** = Kunjungan per jam
- **Success Rate** = (Kunjungan valid / Total kunjungan) × 100%
- **Rata-rata Jarak per Kunjungan** (km)

---

## Sample Data untuk Testing

### Project Locations (Sample)
Sistem sudah include 5 lokasi project sample dengan koordinat di Jakarta. Untuk penelitian, **ganti dengan koordinat lokasi real**:

1. Edit langsung di database (tabel `project_locations`)
2. Atau tambahkan via SQL:

```sql
INSERT INTO project_locations (nama_project, alamat, latitude, longitude, radius_valid)
VALUES ('Nama Project', 'Alamat Lengkap', -6.xxxxx, 106.xxxxx, 50);
```

### Testing Scenario

**Untuk pengumpulan data skripsi:**
1. Rekrut 3-5 admin lapangan
2. Tentukan 5-10 lokasi project
3. Lakukan tracking selama 5-7 hari
4. Kumpulkan minimal 15-25 kunjungan total
5. Variasikan kondisi:
   - Waktu (pagi, siang, sore)
   - Lokasi (outdoor, indoor)
   - Cuaca berbeda

---

## Troubleshooting

### GPS Tidak Berfungsi
- **Solusi 1**: Pastikan browser memiliki izin akses lokasi
- **Solusi 2**: Gunakan HTTPS (GPS API memerlukan secure context)
- **Solusi 3**: Coba browser lain (Chrome biasanya paling baik)

### Foto Tidak Ter-upload
- **Solusi 1**: Cek folder `uploads/laporan/` memiliki permission write
- **Solusi 2**: Cek ukuran file (maksimal 5MB)
- **Solusi 3**: Pastikan format JPG/PNG

### Database Error
- **Solusi 1**: Pastikan MySQL service running
- **Solusi 2**: Cek kredensial di `config/database.php`
- **Solusi 3**: Import ulang `database.sql`

### Peta Tidak Muncul
- **Solusi 1**: Pastikan ada koneksi internet (untuk load tiles OpenStreetMap)
- **Solusi 2**: Cek console browser untuk error JavaScript
- **Solusi 3**: Clear cache browser

---

## Batasan Sistem

1. **Akurasi GPS bergantung pada:**
   - Perangkat smartphone yang digunakan
   - Kondisi lingkungan (outdoor vs indoor)
   - Cuaca dan kondisi atmosfer

2. **Realtime tracking menggunakan polling:**
   - Bukan realtime murni (delay 30 detik)
   - Konsumsi bandwidth lebih tinggi dibanding WebSocket

3. **Keamanan:**
   - Belum dilengkapi anti-GPS spoofing
   - Untuk production, tambahkan HTTPS dan validasi tambahan

4. **Sample size:**
   - Data penelitian terbatas pada 3-5 admin
   - Hasil mungkin tidak generalisable untuk skala besar

---

## Pengembangan Lebih Lanjut

Untuk meningkatkan sistem (saran untuk penelitian lanjutan):

1. **Keamanan:**
   - Implementasi HTTPS
   - CSRF token
   - Rate limiting API
   - Anti-GPS spoofing detection

2. **Realtime:**
   - Ganti AJAX polling dengan WebSocket
   - Server-Sent Events (SSE)

3. **Analisis Lanjutan:**
   - Machine Learning untuk prediksi produktivitas
   - Clustering admin berdasarkan performa
   - Rekomendasi rute optimal

4. **Mobile App:**
   - Konversi ke Progressive Web App (PWA)
   - Native mobile app (Android/iOS)

---

## Lisensi

Proyek ini dibuat untuk keperluan **penelitian skripsi** dan bersifat open-source untuk tujuan pendidikan.

---

## Kontak & Support

Untuk pertanyaan terkait sistem ini, silakan hubungi:
- **Nama**: [Nama Mahasiswa]
- **NIM**: [NIM]
- **Prodi**: Teknik Informatika
- **Email**: [Email]

---

## Referensi

1. Haversine Formula: https://en.wikipedia.org/wiki/Haversine_formula
2. Leaflet.js Documentation: https://leafletjs.com/
3. HTML5 Geolocation API: https://developer.mozilla.org/en-US/docs/Web/API/Geolocation_API
4. Chart.js Documentation: https://www.chartjs.org/

---

**Selamat menggunakan sistem ini untuk penelitian skripsi Anda!** 🎓
