<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Désabonnement effectué — LCMS Core') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .card-unsub { border: none; border-radius: 1.25rem; background: #ffffff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-5">
    <div class="container" style="max-width: 540px;">
        <div class="card card-unsub p-4 p-md-5 text-center">
            <div class="mx-auto mb-4 bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                <i class="bi bi-envelope-x-fill fs-1"></i>
            </div>
            <h1 class="h3 fw-bold text-dark mb-2">{{ __('Désabonnement pris en compte') }}</h1>
            <p class="text-muted mb-4">
                {{ __('Vous avez été désabonné(e) avec succès. Vous ne recevrez plus nos lettres d\'information par e-mail.') }}
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ url('/') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold">
                    <i class="bi bi-house me-1"></i> {{ __('Retour au site') }}
                </a>
            </div>
        </div>
    </div>
</body>
</html>
