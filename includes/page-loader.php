<!-- Page Loading Overlay -->
<div id="page-loader" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.98);display:none;align-items:center;justify-content:center;z-index:9999;flex-direction:column;">
    <div style="text-align:center;">
        <svg width="60" height="60" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="animation:pulse 1.5s ease-in-out infinite;">
            <defs>
                <linearGradient id="loaderPinGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#3b82f6"/>
                    <stop offset="100%" style="stop-color:#1d4ed8"/>
                </linearGradient>
                <linearGradient id="loaderCheckGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#10b981"/>
                    <stop offset="100%" style="stop-color:#059669"/>
                </linearGradient>
            </defs>
            <path d="M50 5 C30 5 15 22 15 40 C15 60 50 95 50 95 C50 95 85 60 85 40 C85 22 70 5 50 5 Z"
                  fill="url(#loaderPinGrad)" stroke="#1e40af" stroke-width="2"/>
            <circle cx="50" cy="38" r="22" fill="white"/>
            <path d="M38 38 L46 46 L62 30"
                  stroke="url(#loaderCheckGrad)" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div style="margin-top:15px;font-size:18px;font-weight:600;color:#2563eb;letter-spacing:2px;">LACAKIN</div>
        <div style="display:flex;gap:8px;margin-top:20px;justify-content:center;">
            <span style="width:10px;height:10px;background:#2563eb;border-radius:50%;animation:loaderBounce 1.4s ease-in-out infinite;animation-delay:-0.32s;"></span>
            <span style="width:10px;height:10px;background:#2563eb;border-radius:50%;animation:loaderBounce 1.4s ease-in-out infinite;animation-delay:-0.16s;"></span>
            <span style="width:10px;height:10px;background:#2563eb;border-radius:50%;animation:loaderBounce 1.4s ease-in-out infinite;"></span>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
@keyframes loaderBounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}
</style>

<script>
(function() {
    const pageLoader = document.getElementById('page-loader');

    function showLoader() {
        pageLoader.style.display = 'flex';
    }

    function hideLoader() {
        pageLoader.style.display = 'none';
    }

    // Show loader when clicking on navigation links
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (link) {
            const href = link.getAttribute('href');
            if (href &&
                !href.startsWith('#') &&
                !href.startsWith('javascript:') &&
                !link.hasAttribute('download') &&
                !link.getAttribute('target') &&
                !link.classList.contains('no-loader') &&
                !link.closest('.modal')) {
                showLoader();
            }
        }
    });

    // Show loader on form submit (except forms with no-loader class)
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form.classList.contains('no-loader') && !form.hasAttribute('data-ajax')) {
            showLoader();
        }
    });

    // Hide loader when page is fully loaded
    window.addEventListener('load', function() {
        hideLoader();
    });

    // Hide loader if user navigates back (browser cache)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            hideLoader();
        }
    });
})();
</script>
