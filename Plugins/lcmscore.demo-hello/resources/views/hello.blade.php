<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LCMS Core Demo Plugin</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #2563eb;
            font-size: 1.75rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
        }
        p {
            font-size: 1.15rem;
            color: #475569;
            margin: 1.25rem 0;
        }
        .badge {
            display: inline-block;
            background-color: #dcfce7;
            color: #166534;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>LCMS Core Demo Plugin</h1>
        <p>{{ $message }}</p>
        <span class="badge">Plugin État : ACTIF</span>
    </div>
</body>
</html>

