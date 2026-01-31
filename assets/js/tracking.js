/**
 * GPS Tracking Script untuk Admin Lapangan
 * Mengirim data GPS secara realtime ke server menggunakan AJAX polling
 */

class GPSTracker {
    constructor() {
        this.watchId = null;
        this.isTracking = false;
        this.updateInterval = 30000; // 30 detik
        this.currentPosition = null;
        this.accuracy = null;
        this.lastUpdate = null;
    }

    /**
     * Mulai tracking GPS
     */
    startTracking() {
        if (!navigator.geolocation) {
            this.showAlert('GPS tidak didukung oleh browser Anda', 'danger');
            return false;
        }

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.onSuccess(position),
            (error) => this.onError(error),
            options
        );

        this.isTracking = true;
        this.updateUI();
        this.showAlert('GPS tracking dimulai', 'success');
        return true;
    }

    /**
     * Stop tracking GPS
     */
    stopTracking() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        this.isTracking = false;
        this.updateUI();
        this.showAlert('GPS tracking dihentikan', 'warning');
    }

    /**
     * Callback ketika GPS berhasil
     */
    onSuccess(position) {
        this.currentPosition = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            altitude: position.coords.altitude,
            speed: position.coords.speed,
            heading: position.coords.heading,
            timestamp: new Date(position.timestamp).toISOString()
        };

        this.accuracy = position.coords.accuracy;
        this.lastUpdate = new Date();

        // Update UI
        this.updateUI();

        // Send to server
        this.sendLocationToServer();

        // Update map if exists
        if (typeof window.updateMapPosition === 'function') {
            window.updateMapPosition(this.currentPosition.latitude, this.currentPosition.longitude);
        }
    }

    /**
     * Callback ketika GPS error
     */
    onError(error) {
        let message = '';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message = 'Izin akses lokasi ditolak. Mohon aktifkan GPS dan izinkan akses lokasi.';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Informasi lokasi tidak tersedia. Pastikan GPS aktif.';
                break;
            case error.TIMEOUT:
                message = 'Permintaan lokasi timeout. Coba lagi.';
                break;
            default:
                message = 'Error tidak diketahui: ' + error.message;
        }

        console.error('GPS Error:', error);
        this.showAlert(message, 'danger');
    }

    /**
     * Kirim lokasi ke server via AJAX
     */
    sendLocationToServer() {
        if (!this.currentPosition) return;

        // Determine location type and signal strength based on accuracy
        let locationType = 'outdoor';
        let signalStrength = 'high';

        if (this.accuracy > 50) {
            locationType = 'indoor';
            signalStrength = 'low';
        } else if (this.accuracy > 20) {
            signalStrength = 'medium';
        }

        const data = {
            ...this.currentPosition,
            location_type: locationType,
            signal_strength: signalStrength,
            device_info: navigator.userAgent,
            network_type: navigator.connection ? navigator.connection.effectiveType : 'unknown'
        };

        // Get battery level if available
        if (navigator.getBattery) {
            navigator.getBattery().then(battery => {
                data.battery_level = Math.round(battery.level * 100);
                this.sendAjaxRequest(data);
            });
        } else {
            this.sendAjaxRequest(data);
        }
    }

    /**
     * Kirim AJAX request
     */
    sendAjaxRequest(data) {
        fetch('../api/update_location.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('Location updated:', result.data);
                this.updateLastSyncTime();
            } else {
                console.error('Failed to update location:', result.message);
            }
        })
        .catch(error => {
            console.error('Network error:', error);
        });
    }

    /**
     * Update UI elements
     */
    updateUI() {
        // Update status indicator
        const statusIndicator = document.getElementById('status-indicator');
        if (statusIndicator) {
            statusIndicator.className = 'status-indicator ' + (this.isTracking ? 'active' : 'inactive');
        }

        // Update status text
        const statusText = document.getElementById('status-text');
        if (statusText) {
            statusText.textContent = this.isTracking ? 'GPS Aktif' : 'GPS Nonaktif';
        }

        // Update position info
        if (this.currentPosition) {
            const latEl = document.getElementById('current-latitude');
            const lonEl = document.getElementById('current-longitude');
            const accEl = document.getElementById('current-accuracy');

            if (latEl) latEl.textContent = this.currentPosition.latitude.toFixed(6);
            if (lonEl) lonEl.textContent = this.currentPosition.longitude.toFixed(6);
            if (accEl) {
                accEl.textContent = this.accuracy.toFixed(2) + ' m';
                // Set color based on accuracy
                if (this.accuracy <= 20) {
                    accEl.className = 'text-success';
                } else if (this.accuracy <= 50) {
                    accEl.className = 'text-warning';
                } else {
                    accEl.className = 'text-danger';
                }
            }
        }

        // Update button state
        const btnStart = document.getElementById('btn-start-tracking');
        const btnStop = document.getElementById('btn-stop-tracking');

        if (btnStart && btnStop) {
            if (this.isTracking) {
                btnStart.style.display = 'none';
                btnStop.style.display = 'inline-block';
            } else {
                btnStart.style.display = 'inline-block';
                btnStop.style.display = 'none';
            }
        }
    }

    /**
     * Update last sync time display
     */
    updateLastSyncTime() {
        const lastSyncEl = document.getElementById('last-sync');
        if (lastSyncEl) {
            const now = new Date();
            lastSyncEl.textContent = now.toLocaleTimeString('id-ID');
        }
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert-auto-dismiss');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-auto-dismiss`;
        alertDiv.textContent = message;
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '20px';
        alertDiv.style.right = '20px';
        alertDiv.style.zIndex = '9999';
        alertDiv.style.minWidth = '300px';

        document.body.appendChild(alertDiv);

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    /**
     * Get current position untuk submit laporan
     */
    getCurrentPosition() {
        return this.currentPosition;
    }

    /**
     * Check if accuracy is good enough
     */
    isAccuracyGood() {
        return this.accuracy !== null && this.accuracy <= 50;
    }
}

// Initialize tracker
const gpsTracker = new GPSTracker();

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const btnStart = document.getElementById('btn-start-tracking');
    const btnStop = document.getElementById('btn-stop-tracking');

    if (btnStart) {
        btnStart.addEventListener('click', function() {
            gpsTracker.startTracking();
        });
    }

    if (btnStop) {
        btnStop.addEventListener('click', function() {
            gpsTracker.stopTracking();
        });
    }
});
