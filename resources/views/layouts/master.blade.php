<html>
<head>
    <script>(function(){try{if(localStorage.getItem('sb-theme')==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})()</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow"/>
    <title>SportBet</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/favicon-180.png') }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('/css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="sb-layout">
<script>
    function sbToggleTheme() {
        var d = document.documentElement;
        var dark = d.getAttribute('data-theme') === 'dark';
        d.setAttribute('data-theme', dark ? 'light' : 'dark');
        try { localStorage.setItem('sb-theme', dark ? 'light' : 'dark'); } catch(e) {}
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
            new bootstrap.Popover(el, {
                html: el.dataset.bsHtml === 'true',
                container: 'body'
            });
        });
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    });
</script>

@include('partials.header')

<main class="sb-main">
    <div class="sb-container @yield('containerClass')">
        @yield('content')
    </div>
</main>

@include('partials.bottom-nav')
@include('partials.cookie-consent')

{{-- Head-to-head comparison modal --}}
<div class="modal fade" id="cmpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">Žaidėjų palyginimas</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cmpModalBody">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    var link = e.target.closest('.lb-name-link');
    if (!link) return;
    e.preventDefault();
    e.stopPropagation();
    var url = link.getAttribute('href');
    var modal = new bootstrap.Modal(document.getElementById('cmpModal'));
    var body  = document.getElementById('cmpModalBody');
    body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
    modal.show();
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.text(); })
        .then(function (html) { body.innerHTML = html; })
        .catch(function () { body.innerHTML = '<p class="text-danger text-center py-3">Klaida. Bandykite dar kartą.</p>'; });
}, true);
</script>

</body>
</html>
