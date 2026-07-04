<?php

use PHPUnit\Framework\TestCase;

/**
 * Pengujian Unit: Fungsi-fungsi Utilitas
 *
 * Menguji tiga fungsi utilitas inti dari config/database.php:
 *  - sanitizeInput()   : Sanitasi input untuk keamanan (baris 160-165)
 *  - formatDistance()  : Format jarak meter/kilometer (baris 168-174)
 *  - formatDuration()  : Format durasi menit/jam (baris 177-186)
 */
class UtilityFunctionsTest extends TestCase
{
    // =========================================================================
    // sanitizeInput() - 5 Test Cases
    // =========================================================================

    /**
     * TC-UTL-01: Input normal tidak mengalami perubahan
     */
    public function testSanitizeNormalStringUnchanged(): void
    {
        $result = sanitizeInput('Laporan Kunjungan Proyek');

        $this->assertEquals('Laporan Kunjungan Proyek', $result);
    }

    /**
     * TC-UTL-02: Whitespace di awal dan akhir harus dihapus (trim)
     */
    public function testSanitizeTrimsLeadingAndTrailingWhitespace(): void
    {
        $result = sanitizeInput('  Admin GPS  ');

        $this->assertEquals('Admin GPS', $result);
    }

    /**
     * TC-UTL-03: Backslash harus dihapus (stripslashes)
     */
    public function testSanitizeRemovesBackslashes(): void
    {
        $result = sanitizeInput("O\\'Brien");

        $this->assertEquals("O'Brien", $result);
    }

    /**
     * TC-UTL-04: Tag HTML berbahaya harus di-encode (XSS prevention)
     */
    public function testSanitizeEncodesDangerousHtmlTags(): void
    {
        $result = sanitizeInput('<script>alert("xss")</script>');

        $this->assertStringNotContainsString('<script>', $result,
            'Tag <script> harus di-encode untuk mencegah XSS');
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    /**
     * TC-UTL-05: Karakter < dan > harus di-encode menjadi &lt; dan &gt;
     */
    public function testSanitizeEncodesAngleBrackets(): void
    {
        $result = sanitizeInput('<b>teks tebal</b>');

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
        $this->assertStringNotContainsString('<b>', $result);
    }

    // =========================================================================
    // formatDistance() - 5 Test Cases
    // =========================================================================

    /**
     * TC-FMT-01: Jarak di bawah 1000 meter ditampilkan dalam satuan meter
     */
    public function testFormatDistanceShowsMetersBelow1000(): void
    {
        $result = formatDistance(500);

        $this->assertStringContainsString('m', $result);
        $this->assertStringNotContainsString('km', $result);
        $this->assertEquals('500 m', $result);
    }

    /**
     * TC-FMT-02: Jarak 1000 meter ke atas ditampilkan dalam kilometer
     */
    public function testFormatDistanceShowsKilometersAt1500(): void
    {
        $result = formatDistance(1500);

        $this->assertStringContainsString('km', $result);
        $this->assertEquals('1.5 km', $result);
    }

    /**
     * TC-FMT-03: Jarak tepat 999 meter (batas bawah threshold)
     */
    public function testFormatDistanceJustBelowThreshold(): void
    {
        $result = formatDistance(999);

        $this->assertEquals('999 m', $result);
    }

    /**
     * TC-FMT-04: Jarak tepat 1000 meter (titik threshold, harus tampil km)
     */
    public function testFormatDistanceExactlyAtThreshold(): void
    {
        $result = formatDistance(1000);

        $this->assertEquals('1 km', $result);
    }

    /**
     * TC-FMT-05: Jarak 0 meter (titik yang sama)
     */
    public function testFormatDistanceZeroMeters(): void
    {
        $result = formatDistance(0);

        $this->assertEquals('0 m', $result);
    }

    // =========================================================================
    // formatDuration() - 5 Test Cases
    // =========================================================================

    /**
     * TC-DUR-01: Durasi di bawah 60 menit ditampilkan dalam menit
     */
    public function testFormatDurationShowsMinutesOnly(): void
    {
        $result = formatDuration(45);

        $this->assertEquals('45 menit', $result);
        $this->assertStringNotContainsString('jam', $result);
    }

    /**
     * TC-DUR-02: Durasi tepat 60 menit = 1 jam 0 menit
     */
    public function testFormatDurationExactlyOneHour(): void
    {
        $result = formatDuration(60);

        $this->assertEquals('1 jam 0 menit', $result);
    }

    /**
     * TC-DUR-03: Durasi 90 menit = 1 jam 30 menit
     */
    public function testFormatDurationOneHourThirtyMinutes(): void
    {
        $result = formatDuration(90);

        $this->assertEquals('1 jam 30 menit', $result);
    }

    /**
     * TC-DUR-04: Durasi 0 menit
     */
    public function testFormatDurationZeroMinutes(): void
    {
        $result = formatDuration(0);

        $this->assertEquals('0 menit', $result);
    }

    /**
     * TC-DUR-05: Durasi lebih dari 2 jam (150 menit = 2 jam 30 menit)
     */
    public function testFormatDurationMultipleHours(): void
    {
        $result = formatDuration(150);

        $this->assertEquals('2 jam 30 menit', $result);
    }
}
