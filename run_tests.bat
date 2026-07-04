@echo off
echo ============================================================
echo   LACAKIN GPS Tracking - Code Coverage Analysis
echo   PHPUnit Test Runner
echo ============================================================
echo.

REM Cek apakah Composer sudah terinstall
where composer >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Composer tidak ditemukan.
    echo.
    echo Silakan install Composer dari: https://getcomposer.org/download/
    echo Setelah install, jalankan kembali file ini.
    echo.
    pause
    exit /b 1
)

REM Install dependencies jika vendor/ belum ada
if not exist "vendor\" (
    echo [INFO] Menginstall PHPUnit via Composer...
    composer install
    echo.
)

REM Cek apakah Xdebug terinstall untuk coverage
php -r "echo extension_loaded('xdebug') ? '' : 'NO_XDEBUG';" > temp_check.txt 2>&1
set /p XDEBUG_CHECK=<temp_check.txt
del temp_check.txt

echo ============================================================
echo   Menjalankan Unit Tests...
echo ============================================================
echo.
vendor\bin\phpunit --testdox
echo.

if "%XDEBUG_CHECK%"=="NO_XDEBUG" (
    echo ============================================================
    echo   [PERHATIAN] Xdebug tidak aktif.
    echo   Coverage report tidak dapat digenerate.
    echo.
    echo   Untuk mengaktifkan Xdebug di XAMPP:
    echo   1. Buka C:\xampp\php\php.ini
    echo   2. Cari [xdebug] section atau tambahkan di bagian akhir:
    echo      zend_extension=xdebug
    echo      xdebug.mode=coverage
    echo   3. Restart Apache XAMPP
    echo ============================================================
) else (
    echo ============================================================
    echo   Menggenerate Coverage Report...
    echo ============================================================
    vendor\bin\phpunit --coverage-html tests/coverage-report --coverage-text=tests/coverage.txt
    echo.
    echo [SUKSES] Coverage report tersedia di:
    echo   - HTML : tests\coverage-report\index.html
    echo   - Text : tests\coverage.txt
)

echo.
echo ============================================================
echo   Laporan Coverage Skripsi tersedia di:
echo   http://localhost/gps-tracking/tests/laporan_coverage.php
echo ============================================================
echo.
pause
