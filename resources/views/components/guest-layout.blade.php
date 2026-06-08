<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SportBet</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/favicon.svg') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.0.1/dist/cerulean/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('/css/custom.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body style="min-height:100vh; background:linear-gradient(135deg, var(--sb-nav) 0%, var(--sb-hero) 100%);">

    <div class="d-flex align-items-center justify-content-center" style="min-height:100vh; padding:24px 16px;">
        <div style="width:100%; max-width:400px;">

            <div class="text-center mb-4">
                <a href="{{ url('/') }}" class="text-decoration-none d-inline-flex align-items-center gap-2">
                    <img src="{{ asset('img/favicon.svg') }}" alt="SportBet" style="height:2rem;">
                    <span class="fw-bold fs-4" style="color:#fff; letter-spacing:.5px;">SportBet</span>
                </a>
            </div>

            <div class="card border-0 shadow-lg" style="border-radius:12px;">
                <div class="card-body p-4">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </div>

</body>
</html>
