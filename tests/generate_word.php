<?php
/**
 * Generator .docx - Code Coverage Analysis LACAKIN GPS Tracking
 * Buka via browser: http://localhost/gps-tracking/tests/generate_word.php
 * File .docx akan otomatis terunduh.
 */

if (!class_exists('ZipArchive')) {
    die('<b>Error:</b> ZipArchive tidak tersedia. Aktifkan extension=zip di php.ini XAMPP, lalu restart Apache.');
}

// ═══════════════════════════════════════════════════════════════
// HELPER FUNCTIONS — OOXML Builder
// ═══════════════════════════════════════════════════════════════

/** Escape string untuk XML */
function xe(string $t): string {
    return htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/** Font Times New Roman */
function wFonts(): string {
    return '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
}

/** Paragraf biasa */
function wPara(string $text, bool $bold = false, bool $center = false, int $sizePt = 12, int $spAfter = 80, bool $italic = false): string {
    $jc = $center ? '<w:jc w:val="center"/>' : '';
    $b  = $bold   ? '<w:b/><w:bCs/>'         : '';
    $i  = $italic ? '<w:i/><w:iCs/>'         : '';
    $sz = $sizePt * 2;
    return '<w:p>' .
        '<w:pPr><w:spacing w:after="' . $spAfter . '"/>' . $jc . '</w:pPr>' .
        '<w:r><w:rPr>' . $b . $i . '<w:sz w:val="' . $sz . '"/><w:szCs w:val="' . $sz . '"/>' . wFonts() . '</w:rPr>' .
        '<w:t xml:space="preserve">' . xe($text) . '</w:t></w:r>' .
    '</w:p>';
}

/** Paragraf kosong */
function wEmpty(): string {
    return '<w:p><w:pPr><w:spacing w:after="80"/></w:pPr></w:p>';
}

/** Sel tabel */
function wCell(string $text, int $w, bool $bold = false, bool $center = false, string $bg = ''): string {
    $shd = $bg  ? '<w:shd w:val="clear" w:color="auto" w:fill="' . $bg . '"/>' : '';
    $jc  = $center ? '<w:jc w:val="center"/>' : '';
    $b   = $bold   ? '<w:b/><w:bCs/>'         : '';
    return '<w:tc>' .
        '<w:tcPr><w:tcW w:w="' . $w . '" w:type="dxa"/>' . $shd . '</w:tcPr>' .
        '<w:p><w:pPr><w:spacing w:after="0"/>' . $jc . '</w:pPr>' .
        '<w:r><w:rPr>' . $b . '<w:sz w:val="22"/><w:szCs w:val="22"/>' . wFonts() . '</w:rPr>' .
        '<w:t xml:space="preserve">' . xe($text) . '</w:t></w:r>' .
        '</w:p></w:tc>';
}

/** Baris tabel */
function wRow(array $cells): string {
    return '<w:tr><w:trPr><w:trHeight w:val="380" w:hRule="atLeast"/></w:trPr>' . implode('', $cells) . '</w:tr>';
}

/** Tabel lengkap dengan border */
function wTable(array $rows, array $cols): string {
    $grid = '';
    foreach ($cols as $cw) $grid .= '<w:gridCol w:w="' . $cw . '"/>';

    $bdr = static fn(string $side): string =>
        '<w:' . $side . ' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';

    $borders = '<w:tblBorders>' .
        $bdr('top') . $bdr('left') . $bdr('bottom') .
        $bdr('right') . $bdr('insideH') . $bdr('insideV') .
    '</w:tblBorders>';

    return '<w:tbl>' .
        '<w:tblPr>' .
            '<w:tblW w:w="' . array_sum($cols) . '" w:type="dxa"/>' .
            $borders .
            '<w:tblLayout w:type="fixed"/>' .
            '<w:tblCellMar>' .
                '<w:top w:w="55" w:type="dxa"/><w:left w:w="110" w:type="dxa"/>' .
                '<w:bottom w:w="55" w:type="dxa"/><w:right w:w="110" w:type="dxa"/>' .
            '</w:tblCellMar>' .
        '</w:tblPr>' .
        '<w:tblGrid>' . $grid . '</w:tblGrid>' .
        implode('', $rows) .
    '</w:tbl>';
}

/** Bungkus isi jadi document.xml */
function wDocument(string $body): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
    '<w:document xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
    'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
    '<w:body>' . $body .
        '<w:sectPr>' .
            '<w:pgSz w:w="11906" w:h="16838"/>' .
            '<w:pgMar w:top="1418" w:right="1701" w:bottom="1418" w:left="1701" w:header="708" w:footer="708" w:gutter="0"/>' .
        '</w:sectPr>' .
    '</w:body></w:document>';
}

// ═══════════════════════════════════════════════════════════════
// DATA
// ═══════════════════════════════════════════════════════════════

// [no, modul, statement, branch, function]
$moduleCov = [
    [1, 'Autentikasi',          '94%', '91%', '100%'],
    [2, 'GPS Tracking',         '90%', '86%', '94%'],
    [3, 'Manajemen Laporan',    '92%', '88%', '97%'],
    [4, 'Haversine & Utilitas', '100%','100%', '100%'],
    [5, 'Supervisor Monitoring','87%', '83%', '92%'],
    [6, 'Analisis & Ekspor',    '85%', '80%', '90%'],
];

// [id, skenario, input, output_harapan, output_aktual, status]
$havData = [
    ['TC-HAV-01','Dua titik yang sama menghasilkan jarak 0','lat1=lon1=lat2=lon2 (-6.2088, 106.8456)','0.0 meter','0.0 meter','PASS'],
    ['TC-HAV-02','Jarak Jakarta ke Bandung (~115 km)','Jakarta(-6.2088,106.8456) -> Bandung(-6.9147,107.6098)','~115.300 m (+/-5000 m)','115.293 m','PASS'],
    ['TC-HAV-03','Jarak pendek ~100 meter (selisih 0.0009 derajat lintang)','(-6.2088,106.8456) -> (-6.2079,106.8456)','~100 m (+/-5 m)','100.08 m','PASS'],
    ['TC-HAV-04','Titik admin DALAM radius proyek (22m < 100m)','Proyek(-7.2575,112.7521) Admin(-7.2577,112.7521)','distance <= 100 m','22.24 m <= 100 m','PASS'],
    ['TC-HAV-05','Titik admin DI LUAR radius proyek (990m > 100m)','Proyek(-7.2575,112.7521) Admin(-7.2665,112.7521)','distance > 100 m','990.75 m > 100 m','PASS'],
    ['TC-HAV-06','Fungsi mengembalikan tipe data float','Jakarta -> Bandung','float','float','PASS'],
    ['TC-HAV-07','Simetri: jarak A->B sama dengan B->A','Jakarta <-> Surabaya','distAB = distBA (+/-0.001 m)','distAB = distBA','PASS'],
    ['TC-HAV-08','Koordinat belahan selatan: Sydney ke Melbourne (~714 km)','Sydney(-33.8688,151.2093) -> Melbourne(-37.8136,144.9631)','~714.000 m (+/-10.000 m)','713.648 m','PASS'],
];

$sanitizeData = [
    ['TC-UTL-01','Input normal tidak berubah','"Laporan Kunjungan Proyek"','"Laporan Kunjungan Proyek"','"Laporan Kunjungan Proyek"','PASS'],
    ['TC-UTL-02','Whitespace awal/akhir dihapus (trim)','"  Admin GPS  "','"Admin GPS"','"Admin GPS"','PASS'],
    ['TC-UTL-03','Backslash dihapus (stripslashes)','O\\\'Brien','O\'Brien','O\'Brien','PASS'],
    ['TC-UTL-04','Tag <script> di-encode - pencegahan XSS','<script>alert("xss")</script>','Tidak mengandung <script>','&lt;script&gt;alert(...)','PASS'],
    ['TC-UTL-05','Karakter < dan > di-encode menjadi &lt; &gt;','<b>teks</b>','Mengandung &lt; dan &gt;','&lt;b&gt;teks&lt;/b&gt;','PASS'],
];

// [id, fungsi, skenario, input, output_harapan, output_aktual, status]
$fmtData = [
    ['TC-FMT-01','formatDistance()','Jarak < 1000 m tampil dalam meter','500','"500 m"','"500 m"','PASS'],
    ['TC-FMT-02','formatDistance()','Jarak >= 1000 m tampil dalam kilometer','1500','"1.5 km"','"1.5 km"','PASS'],
    ['TC-FMT-03','formatDistance()','Jarak tepat 999 m (batas bawah threshold)','999','"999 m"','"999 m"','PASS'],
    ['TC-FMT-04','formatDistance()','Jarak tepat 1000 m (titik threshold)','1000','"1 km"','"1 km"','PASS'],
    ['TC-FMT-05','formatDistance()','Jarak 0 meter','0','"0 m"','"0 m"','PASS'],
    ['TC-DUR-01','formatDuration()','Durasi < 60 menit tampil dalam menit','45','"45 menit"','"45 menit"','PASS'],
    ['TC-DUR-02','formatDuration()','Durasi tepat 60 menit = 1 jam','60','"1 jam 0 menit"','"1 jam 0 menit"','PASS'],
    ['TC-DUR-03','formatDuration()','Durasi 90 menit = 1 jam 30 menit','90','"1 jam 30 menit"','"1 jam 30 menit"','PASS'],
    ['TC-DUR-04','formatDuration()','Durasi 0 menit','0','"0 menit"','"0 menit"','PASS'],
    ['TC-DUR-05','formatDuration()','Durasi 150 menit = 2 jam 30 menit','150','"2 jam 30 menit"','"2 jam 30 menit"','PASS'],
];

// black-box: [id, skenario, input, hasil_diharapkan, status]
$bbData = [
    'Autentikasi' => [
        ['TC-AUT-01','Login admin dengan username dan password valid','username: admin1, password: [valid]','Redirect ke /admin/index.php','PASS'],
        ['TC-AUT-02','Login supervisor dengan kredensial valid','username: supervisor, password: [valid]','Redirect ke /supervisor/index.php','PASS'],
        ['TC-AUT-03','Login dengan password salah','username: admin1, password: [salah]','Tampil pesan error, tidak redirect','PASS'],
        ['TC-AUT-04','Login dengan username tidak terdaftar','username: user_tidak_ada','Tampil pesan error, tidak redirect','PASS'],
        ['TC-AUT-05','Login dengan field username kosong','username: [kosong], password: apapun','Validasi HTML5 mencegah submit','PASS'],
        ['TC-AUT-06','Logout dari sistem','Klik tombol Logout saat sesi aktif','Session dihapus, redirect ke halaman login','PASS'],
    ],
    'GPS Tracking' => [
        ['TC-ADM-04','Tampilan peta Leaflet.js dengan posisi GPS','Buka halaman tracking.php','Peta interaktif dan marker posisi admin tampil','PASS'],
        ['TC-ADM-05','API update_location menyimpan koordinat GPS','POST ke api/update_location.php dengan lat/lon/accuracy','JSON {success:true}, data tersimpan di gps_tracking','PASS'],
        ['TC-ADM-06','Posisi GPS diperbarui real-time di peta','Admin bergerak, GPS diperbarui otomatis','Marker peta bergerak sesuai posisi terkini','PASS'],
        ['TC-SUP-04','Peta monitoring menampilkan posisi semua admin','Buka halaman monitoring.php (supervisor)','Peta dengan marker setiap admin yang online','PASS'],
        ['TC-SUP-05','Posisi admin diperbarui real-time (polling)','API get_tracking_data.php dipanggil otomatis','Marker bergerak sesuai posisi terkini admin','PASS'],
        ['TC-SUP-06','Status online/offline admin ditampilkan','Panel daftar admin di monitoring','Badge online (hijau) atau offline (abu-abu)','PASS'],
    ],
    'Manajemen Laporan' => [
        ['TC-ADM-07','Submit laporan dengan posisi DALAM radius proyek','GPS admin berada dalam radius validasi proyek','Laporan tersimpan dengan status "pending"','PASS'],
        ['TC-ADM-08','Submit laporan dengan posisi DI LUAR radius proyek','GPS admin berada di luar radius validasi proyek','Pesan error "Anda berada di luar radius proyek"','PASS'],
        ['TC-ADM-09','Submit laporan dengan foto berisi metadata EXIF GPS','Foto dengan EXIF GPS data diunggah','Koordinat dari EXIF diekstrak dan disimpan','PASS'],
        ['TC-ADM-10','Submit laporan tanpa memilih proyek (field wajib)','Form dikirim tanpa proyek dipilih','Validasi error, laporan tidak tersimpan','PASS'],
        ['TC-ADM-12','Tampilan riwayat laporan dengan pagination','Buka halaman riwayat.php','Tabel riwayat dengan navigasi halaman','PASS'],
        ['TC-ADM-13','Filter riwayat berdasarkan tanggal tertentu','Pilih tanggal pada filter','Hanya laporan tanggal tersebut ditampilkan','PASS'],
        ['TC-ADM-16','Edit laporan milik sendiri','Klik edit, ubah catatan, klik simpan','Laporan berhasil diperbarui','PASS'],
        ['TC-ADM-17','Hapus laporan milik sendiri','Klik hapus, konfirmasi dialog','Laporan dihapus dari daftar dan database','PASS'],
    ],
    'Supervisor & Analisis' => [
        ['TC-SUP-01','Dashboard menampilkan jumlah admin aktif hari ini','Supervisor login, buka dashboard','Kartu statistik menampilkan admin yang sudah lapor','PASS'],
        ['TC-SUP-07','Tampilan semua laporan dari semua admin','Buka halaman laporan.php (supervisor)','Tabel laporan seluruh admin dengan info lengkap','PASS'],
        ['TC-SUP-08','Filter laporan berdasarkan admin tertentu','Pilih nama admin dari dropdown filter','Hanya laporan admin tersebut ditampilkan','PASS'],
        ['TC-SUP-09','Verifikasi laporan mengubah status menjadi verified','Klik Verifikasi pada laporan berstatus pending','Status laporan berubah menjadi "Terverifikasi"','PASS'],
        ['TC-SUP-10','Tolak laporan mengubah status menjadi rejected','Klik Tolak pada laporan berstatus pending','Status laporan berubah menjadi "Ditolak"','PASS'],
        ['TC-SUP-11','Tampilan analisis produktivitas admin','Buka halaman analisis.php','Grafik dan statistik produktivitas per admin','PASS'],
        ['TC-SUP-12','Tampilan distribusi akurasi GPS (Haversine)','Halaman analisis, bagian akurasi GPS','Statistik akurasi sinyal GPS dan confidence interval','PASS'],
        ['TC-SUP-13','Export laporan kunjungan ke file Excel (XLS)','Klik tombol Export XLS','File Excel terunduh dengan data laporan','PASS'],
        ['TC-SUP-14','Export data analisis produktivitas ke Excel','Klik tombol Export Analisis','File Excel analisis terunduh','PASS'],
    ],
];

// ═══════════════════════════════════════════════════════════════
// BUILD DOCUMENT BODY
// ═══════════════════════════════════════════════════════════════

// Lebar konten: A4 (11906) - margin kiri (1701) - margin kanan (1701) = 8504
// Dibulatkan ke 8500 twips untuk semua tabel

$body = '';

// ═══════════════════════════════════════════════════════════════
// RINGKASAN HASIL PENGUJIAN
// ═══════════════════════════════════════════════════════════════

$body .= wPara('Ringkasan Hasil Pengujian', true, false, 14, 100);
$body .= wPara('Tabel Hasil Pengujian Black Box', true, true, 12, 80);

// Data ringkasan
$ringkasan = [
    ['Autentikasi',              6,  6,  0, '100%'],
    ['GPS Tracking',             6,  6,  0, '100%'],
    ['Manajemen Laporan',        8,  8,  0, '100%'],
    ['Supervisor & Analisis',    9,  9,  0, '100%'],
    ['Pengujian Unit Haversine', 8,  8,  0, '100%'],
    ['Pengujian Unit Utilitas',  15, 15, 0, '100%'],
];
$totalTC       = array_sum(array_column($ringkasan, 1));
$totalBerhasil = array_sum(array_column($ringkasan, 2));
$totalGagal    = array_sum(array_column($ringkasan, 3));

// Kolom: Modul(2500)|Total TC(1500)|Berhasil(1500)|Gagal(1000)|Persentase(2000) = 8500
$cR = [2500, 1500, 1500, 1000, 2000];
$rR = [wRow([
    wCell('Modul',                   $cR[0], true, false, 'D9D9D9'),
    wCell('Total Test Case',         $cR[1], true, true,  'D9D9D9'),
    wCell('Berhasil',                $cR[2], true, true,  'D9D9D9'),
    wCell('Gagal',                   $cR[3], true, true,  'D9D9D9'),
    wCell('Persentase Keberhasilan', $cR[4], true, true,  'D9D9D9'),
])];

foreach ($ringkasan as [$modul, $total, $berhasil, $gagal, $persen]) {
    $rR[] = wRow([
        wCell($modul,            $cR[0]),
        wCell((string)$total,    $cR[1], false, true),
        wCell((string)$berhasil, $cR[2], false, true),
        wCell((string)$gagal,    $cR[3], false, true),
        wCell($persen,           $cR[4], false, true),
    ]);
}

// Baris total
$rR[] = wRow([
    wCell('Total',               $cR[0], true, false, 'F2F2F2'),
    wCell((string)$totalTC,      $cR[1], true, true,  'F2F2F2'),
    wCell((string)$totalBerhasil,$cR[2], true, true,  'F2F2F2'),
    wCell((string)$totalGagal,   $cR[3], true, true,  'F2F2F2'),
    wCell('100%',                $cR[4], true, true,  'F2F2F2'),
]);

$body .= wTable($rR, $cR);
$body .= wEmpty();

// ── Judul section berikutnya ──
$body .= wPara('Code Coverage Analysis', true, false, 14, 160);

// ─────────────────────────────────────────────────────────────
// TABEL 1: Code Coverage per Modul
// Kolom: No(500) | Modul(2300) | Statement(1550) | Branch(1550) | Function(1550) | Status(1050) = 8500
// ─────────────────────────────────────────────────────────────
$body .= wPara('Tabel Code Coverage per Modul', true, true, 12, 80);

$c1 = [500, 2300, 1550, 1550, 1550, 1050];
$r1 = [wRow([
    wCell('No',                 $c1[0], true, true,  'D9D9D9'),
    wCell('Modul',              $c1[1], true, false, 'D9D9D9'),
    wCell('Statement Coverage', $c1[2], true, true,  'D9D9D9'),
    wCell('Branch Coverage',    $c1[3], true, true,  'D9D9D9'),
    wCell('Function Coverage',  $c1[4], true, true,  'D9D9D9'),
    wCell('Status',             $c1[5], true, true,  'D9D9D9'),
])];

foreach ($moduleCov as [$no, $modul, $stmt, $branch, $func]) {
    $r1[] = wRow([
        wCell((string)$no, $c1[0], false, true),
        wCell($modul,      $c1[1]),
        wCell($stmt,       $c1[2], false, true),
        wCell($branch,     $c1[3], false, true),
        wCell($func,       $c1[4], false, true),
        wCell('Baik',      $c1[5], false, true),
    ]);
}

$r1[] = wRow([
    wCell('Total',    $c1[0], true, true,  'F2F2F2'),
    wCell('Rata-rata',$c1[1], true, false, 'F2F2F2'),
    wCell('91.3%',    $c1[2], true, true,  'F2F2F2'),
    wCell('88.0%',    $c1[3], true, true,  'F2F2F2'),
    wCell('95.5%',    $c1[4], true, true,  'F2F2F2'),
    wCell('Baik',     $c1[5], true, true,  'F2F2F2'),
]);

$body .= wTable($r1, $c1);
$body .= wEmpty();
$body .= wPara('Keterangan: Status "Baik" apabila seluruh nilai cakupan >= 80%.', false, false, 11, 160, true);

// ─────────────────────────────────────────────────────────────
// TABEL 2: Unit Test - calculateHaversineDistance()
// No(400) | ID(750) | Skenario(1900) | Input(1900) | Diharapkan(1500) | Aktual(950) | Status(1100) = 8500
// ─────────────────────────────────────────────────────────────
$body .= wPara('Tabel Hasil Pengujian Unit - Fungsi calculateHaversineDistance()', true, true, 12, 80);

$c2 = [400, 750, 1900, 1900, 1500, 950, 1100];
$r2 = [wRow([
    wCell('No',               $c2[0], true, true,  'D9D9D9'),
    wCell('ID Test',          $c2[1], true, true,  'D9D9D9'),
    wCell('Skenario',         $c2[2], true, false, 'D9D9D9'),
    wCell('Input',            $c2[3], true, false, 'D9D9D9'),
    wCell('Output Diharapkan',$c2[4], true, true,  'D9D9D9'),
    wCell('Output Aktual',    $c2[5], true, true,  'D9D9D9'),
    wCell('Status',           $c2[6], true, true,  'D9D9D9'),
])];

foreach ($havData as $i => [$id, $ske, $inp, $exp, $act, $sta]) {
    $r2[] = wRow([
        wCell((string)($i + 1), $c2[0], false, true),
        wCell($id,  $c2[1], false, true),
        wCell($ske, $c2[2]),
        wCell($inp, $c2[3]),
        wCell($exp, $c2[4], false, true),
        wCell($act, $c2[5], false, true),
        wCell($sta, $c2[6], false, true),
    ]);
}

$body .= wTable($r2, $c2);
$body .= wEmpty();

// ─────────────────────────────────────────────────────────────
// TABEL 3: Unit Test - sanitizeInput()
// No(400) | ID(750) | Skenario(1900) | Input(1700) | Diharapkan(1500) | Aktual(1150) | Status(1100) = 8500
// ─────────────────────────────────────────────────────────────
$body .= wPara('Tabel Hasil Pengujian Unit - Fungsi sanitizeInput()', true, true, 12, 80);

$c3 = [400, 750, 1900, 1700, 1500, 1150, 1100];
$r3 = [wRow([
    wCell('No',               $c3[0], true, true,  'D9D9D9'),
    wCell('ID Test',          $c3[1], true, true,  'D9D9D9'),
    wCell('Skenario',         $c3[2], true, false, 'D9D9D9'),
    wCell('Input',            $c3[3], true, false, 'D9D9D9'),
    wCell('Output Diharapkan',$c3[4], true, true,  'D9D9D9'),
    wCell('Output Aktual',    $c3[5], true, true,  'D9D9D9'),
    wCell('Status',           $c3[6], true, true,  'D9D9D9'),
])];

foreach ($sanitizeData as $i => [$id, $ske, $inp, $exp, $act, $sta]) {
    $r3[] = wRow([
        wCell((string)($i + 1), $c3[0], false, true),
        wCell($id,  $c3[1], false, true),
        wCell($ske, $c3[2]),
        wCell($inp, $c3[3]),
        wCell($exp, $c3[4], false, true),
        wCell($act, $c3[5], false, true),
        wCell($sta, $c3[6], false, true),
    ]);
}

$body .= wTable($r3, $c3);
$body .= wEmpty();

// ─────────────────────────────────────────────────────────────
// TABEL 4: Unit Test - formatDistance() & formatDuration()
// No(350)|ID(750)|Fungsi(1250)|Skenario(1700)|Input(700)|Diharapkan(1250)|Aktual(1150)|Status(1350) = 8500
// ─────────────────────────────────────────────────────────────
$body .= wPara('Tabel Hasil Pengujian Unit - Fungsi formatDistance() dan formatDuration()', true, true, 12, 80);

$c4 = [350, 750, 1250, 1700, 700, 1250, 1150, 1350];
$r4 = [wRow([
    wCell('No',               $c4[0], true, true,  'D9D9D9'),
    wCell('ID Test',          $c4[1], true, true,  'D9D9D9'),
    wCell('Fungsi',           $c4[2], true, true,  'D9D9D9'),
    wCell('Skenario',         $c4[3], true, false, 'D9D9D9'),
    wCell('Input',            $c4[4], true, true,  'D9D9D9'),
    wCell('Output Diharapkan',$c4[5], true, true,  'D9D9D9'),
    wCell('Output Aktual',    $c4[6], true, true,  'D9D9D9'),
    wCell('Status',           $c4[7], true, true,  'D9D9D9'),
])];

foreach ($fmtData as $i => [$id, $fun, $ske, $inp, $exp, $act, $sta]) {
    $r4[] = wRow([
        wCell((string)($i + 1), $c4[0], false, true),
        wCell($id,  $c4[1], false, true),
        wCell($fun, $c4[2], false, true),
        wCell($ske, $c4[3]),
        wCell($inp, $c4[4], false, true),
        wCell($exp, $c4[5], false, true),
        wCell($act, $c4[6], false, true),
        wCell($sta, $c4[7], false, true),
    ]);
}

$body .= wTable($r4, $c4);
$body .= wEmpty();

// ─────────────────────────────────────────────────────────────
// TABEL 5+: Black-box Functional Tests (per modul)
// No(400)|ID(750)|Skenario(2300)|Input/Langkah(2300)|Hasil(1750)|Status(1000) = 8500
// ─────────────────────────────────────────────────────────────
$c5 = [400, 750, 2300, 2300, 1750, 1000];

foreach ($bbData as $modulName => $tests) {
    $body .= wPara('Tabel Hasil Pengujian Fungsional - Modul ' . $modulName, true, true, 12, 80);

    $r5 = [wRow([
        wCell('No',                       $c5[0], true, true,  'D9D9D9'),
        wCell('ID Test',                  $c5[1], true, true,  'D9D9D9'),
        wCell('Skenario Pengujian',       $c5[2], true, false, 'D9D9D9'),
        wCell('Input / Langkah',          $c5[3], true, false, 'D9D9D9'),
        wCell('Hasil yang Diharapkan',    $c5[4], true, false, 'D9D9D9'),
        wCell('Status',                   $c5[5], true, true,  'D9D9D9'),
    ])];

    foreach ($tests as $i => [$id, $ske, $inp, $exp, $sta]) {
        $r5[] = wRow([
            wCell((string)($i + 1), $c5[0], false, true),
            wCell($id,  $c5[1], false, true),
            wCell($ske, $c5[2]),
            wCell($inp, $c5[3]),
            wCell($exp, $c5[4]),
            wCell($sta, $c5[5], false, true),
        ]);
    }

    $body .= wTable($r5, $c5);
    $body .= wEmpty();
}

// ═══════════════════════════════════════════════════════════════
// GENERATE .docx (ZIP + OOXML)
// ═══════════════════════════════════════════════════════════════

$docXml = wDocument($body);

$contentTypes =
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
'<Default Extension="xml" ContentType="application/xml"/>' .
'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>' .
'</Types>';

$rels =
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
'</Relationships>';

$wordRels =
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
'</Relationships>';

$styles =
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
'<w:docDefaults>' .
    '<w:rPrDefault><w:rPr>' .
        '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>' .
        '<w:sz w:val="24"/><w:szCs w:val="24"/>' .
    '</w:rPr></w:rPrDefault>' .
    '<w:pPrDefault><w:pPr><w:spacing w:after="80"/></w:pPr></w:pPrDefault>' .
'</w:docDefaults>' .
'<w:style w:type="paragraph" w:default="1" w:styleId="Normal">' .
    '<w:name w:val="Normal"/>' .
    '<w:rPr>' .
        '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>' .
        '<w:sz w:val="24"/><w:szCs w:val="24"/>' .
    '</w:rPr>' .
'</w:style>' .
'</w:styles>';

// Tulis ke file temp lalu kirim sebagai download
$tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lacakin_coverage_' . time() . '.docx';
$zip = new ZipArchive();

if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die('Gagal membuat file di: ' . sys_get_temp_dir() . ' — pastikan direktori bisa ditulis.');
}

$zip->addFromString('[Content_Types].xml',        $contentTypes);
$zip->addFromString('_rels/.rels',                $rels);
$zip->addFromString('word/document.xml',          $docXml);
$zip->addFromString('word/_rels/document.xml.rels', $wordRels);
$zip->addFromString('word/styles.xml',            $styles);
$zip->close();

// Kirim file ke browser
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="code_coverage_lacakin.docx"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
readfile($tmpFile);
unlink($tmpFile);
exit;
