<?php

use PHPUnit\Framework\TestCase;

/**
 * Pengujian Unit: Fungsi calculateHaversineDistance()
 *
 * Menguji formula Haversine untuk perhitungan jarak GPS
 * yang menjadi inti dari penelitian skripsi LACAKIN.
 *
 * Fungsi: calculateHaversineDistance($lat1, $lon1, $lat2, $lon2)
 * File  : config/database.php (baris 142-157)
 */
class HaversineTest extends TestCase
{
    /**
     * TC-HAV-01: Titik yang sama harus menghasilkan jarak 0
     */
    public function testSameLocationReturnsZero(): void
    {
        $lat = -6.2088;
        $lon = 106.8456;

        $result = calculateHaversineDistance($lat, $lon, $lat, $lon);

        $this->assertEquals(0.0, $result, 'Titik identik harus menghasilkan jarak 0 meter');
    }

    /**
     * TC-HAV-02: Jarak Jakarta ke Bandung (~115 km)
     * Koordinat: Jakarta (-6.2088, 106.8456) | Bandung (-6.9147, 107.6098)
     */
    public function testJakartaToBandungDistance(): void
    {
        $result = calculateHaversineDistance(-6.2088, 106.8456, -6.9147, 107.6098);

        // Toleransi ±5 km dari nilai referensi 115.3 km
        $this->assertEqualsWithDelta(115300, $result, 5000,
            'Jarak Jakarta-Bandung harus sekitar 115 km (±5 km)');
    }

    /**
     * TC-HAV-03: Jarak pendek ~100 meter
     * Menggeser lintang 0.0009° ke utara dari titik Jakarta ≈ 100.08 meter
     */
    public function testShortDistanceApproximately100Meters(): void
    {
        $lat1 = -6.2088;
        $lon  = 106.8456;
        $lat2 = -6.2079; // +0.0009° ≈ 100 meter ke utara

        $result = calculateHaversineDistance($lat1, $lon, $lat2, $lon);

        // Toleransi ±5 meter
        $this->assertEqualsWithDelta(100, $result, 5,
            'Selisih 0.0009° lintang harus menghasilkan jarak ~100 meter');
    }

    /**
     * TC-HAV-04: Titik admin berada DALAM radius validasi proyek (50 m < 100 m)
     */
    public function testPointWithinProjectRadius(): void
    {
        $projectLat = -7.2575;
        $projectLon = 112.7521; // Surabaya
        $adminLat   = -7.2577;  // ~22 meter dari titik proyek
        $adminLon   = 112.7521;

        $distance      = calculateHaversineDistance($projectLat, $projectLon, $adminLat, $adminLon);
        $projectRadius = 100; // meter

        $this->assertLessThanOrEqual($projectRadius, $distance,
            'Admin dalam radius 100m harus diterima sebagai kunjungan valid');
    }

    /**
     * TC-HAV-05: Titik admin berada DI LUAR radius validasi proyek (990 m > 100 m)
     */
    public function testPointOutsideProjectRadius(): void
    {
        $projectLat = -7.2575;
        $projectLon = 112.7521;
        $adminLat   = -7.2665;  // ~990 meter dari titik proyek
        $adminLon   = 112.7521;

        $distance      = calculateHaversineDistance($projectLat, $projectLon, $adminLat, $adminLon);
        $projectRadius = 100; // meter

        $this->assertGreaterThan($projectRadius, $distance,
            'Admin di luar radius 100m harus ditolak sebagai kunjungan tidak valid');
    }

    /**
     * TC-HAV-06: Fungsi harus mengembalikan tipe data float
     */
    public function testReturnTypeIsFloat(): void
    {
        $result = calculateHaversineDistance(-6.2088, 106.8456, -6.9147, 107.6098);

        $this->assertIsFloat($result, 'Haversine harus mengembalikan nilai float (dalam meter)');
    }

    /**
     * TC-HAV-07: Simetri - jarak A→B harus sama dengan B→A
     */
    public function testDistanceIsSymmetric(): void
    {
        $lat1 = -6.2088; $lon1 = 106.8456; // Jakarta
        $lat2 = -7.2575; $lon2 = 112.7521; // Surabaya

        $distAB = calculateHaversineDistance($lat1, $lon1, $lat2, $lon2);
        $distBA = calculateHaversineDistance($lat2, $lon2, $lat1, $lon1);

        $this->assertEqualsWithDelta($distAB, $distBA, 0.001,
            'Jarak A→B harus sama dengan jarak B→A (sifat simetri)');
    }

    /**
     * TC-HAV-08: Koordinat bumi belahan selatan (Sydney ke Melbourne ~714 km)
     */
    public function testNegativeCoordinatesSydneyToMelbourne(): void
    {
        // Sydney: -33.8688, 151.2093 | Melbourne: -37.8136, 144.9631
        $result = calculateHaversineDistance(-33.8688, 151.2093, -37.8136, 144.9631);

        // Toleransi ±10 km dari referensi 714 km
        $this->assertEqualsWithDelta(714000, $result, 10000,
            'Jarak Sydney-Melbourne harus sekitar 714 km (±10 km)');
    }
}
