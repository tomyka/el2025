<div id="sb-cookie-banner" class="sb-cookie-banner" style="display:none">
    <div class="sb-cookie-text">
        <i class="bi bi-cookie"></i>
        Naudojame slapukus reklamai ir statistikai. Daugiau informacijos: <a href="{{ route('privacy') }}">privatumo politika</a>.
    </div>
    <div class="sb-cookie-actions">
        <button id="sb-cookie-decline" class="sb-cookie-btn sb-cookie-btn-secondary">Tik būtini</button>
        <button id="sb-cookie-accept" class="sb-cookie-btn sb-cookie-btn-primary">Sutinku</button>
    </div>
</div>
<script>
(function () {
    var KEY = 'sb_cookie_consent';

    function loadAdsense() {
        if (document.querySelector('script[src*="adsbygoogle"]')) return;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-7290396604686794';
        s.crossOrigin = 'anonymous';
        document.head.appendChild(s);
    }

    var consent = localStorage.getItem(KEY);
    if (consent === 'accepted') {
        loadAdsense();
    } else if (!consent) {
        document.getElementById('sb-cookie-banner').style.display = 'flex';
    }

    document.getElementById('sb-cookie-accept').addEventListener('click', function () {
        localStorage.setItem(KEY, 'accepted');
        document.getElementById('sb-cookie-banner').style.display = 'none';
        loadAdsense();
    });

    document.getElementById('sb-cookie-decline').addEventListener('click', function () {
        localStorage.setItem(KEY, 'declined');
        document.getElementById('sb-cookie-banner').style.display = 'none';
    });
}());
</script>
