<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Confirmation de commande — LCMS Core') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; }
        .success-box { border: none; border-radius: 1.25rem; background: #ffffff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="py-5">
    <main class="container" style="max-width: 680px;">
        <div class="card success-box p-4 p-md-5 text-center mb-4">
            <div class="mx-auto mb-3 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                <i class="bi bi-check-lg fs-1"></i>
            </div>
            <h1 class="h3 fw-bold text-dark mb-2">{{ __('Merci pour votre commande !') }}</h1>
            <p class="text-muted mb-4">
                {{ __('Votre commande a été validée avec succès. Un e-mail récapitulatif a été envoyé à') }} <strong>{{ $order->customer_email }}</strong>.
            </p>

            <div class="card border rounded-4 p-4 text-start bg-light mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted small">{{ __('Numéro de commande :') }}</span>
                    <span class="font-monospace fw-bold text-primary">{{ $order->order_number }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted small">{{ __('Statut :') }}</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                        <i class="bi bi-check-circle-fill me-1"></i> {{ __('Payée') }}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted small">{{ __('Client :') }}</span>
                    <span class="fw-semibold text-dark">{{ $order->customer_name }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <span class="text-muted small">{{ __('Adresse de livraison :') }}</span>
                    <span class="small text-dark text-end">{{ $order->shipping_address }}</span>
                </div>

                <h6 class="fw-bold mt-3 mb-2 small text-uppercase text-muted">{{ __('Articles commandés :') }}</h6>
                <ul class="list-group list-group-flush mb-2">
                    @foreach($order->items as $item)
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2 small">
                            <span>{{ $item->product_name }} &times; {{ $item->quantity }}</span>
                            <span class="font-monospace fw-bold">{{ $item->formattedTotal() }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <span class="fw-bold text-dark">{{ __('Total réglé :') }}</span>
                    <span class="fs-4 fw-bold text-success font-monospace">{{ $order->formattedTotal() }}</span>
                </div>
            </div>

            <div>
                <a href="{{ url('/shop') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-bag me-1"></i> {{ __('Retourner à la boutique') }}
                </a>
            </div>
        </div>
    </main>
</body>
</html>
