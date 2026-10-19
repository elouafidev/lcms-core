<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'LCMS Core') . ' — Administration')</title>
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #0f172a;
            --muted-color: #64748b;
            --border-color: #e2e8f0;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success: #10b981;
            --warning: #f59e0b;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }
        .admin-main {
            flex: 1;
            padding: 2rem;
            max-width: 1200px;
            box-sizing: border-box;
        }
        header.admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        h1.admin-title {
            font-size: 1.75rem;
            margin: 0;
            font-weight: 700;
        }
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-warning {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .table th, .table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .table th {
            background-color: #f1f5f9;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted-color);
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-available { background-color: #e2e8f0; color: #475569; }
        .badge-installed { background-color: #dbeafe; color: #1e40af; }
        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-disabled { background-color: #fef3c7; color: #92400e; }
        .badge-error { background-color: #fee2e2; color: #991b1b; }
        .btn {
            display: inline-block;
            padding: 0.4rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 500;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
        }
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        .btn-danger:hover {
            background-color: var(--danger-hover);
        }
        .btn-secondary {
            background-color: #64748b;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #475569;
        }
        .actions-form {
            display: inline-block;
            margin-right: 0.25rem;
        }
    </style>
    @stack('styles')
</head>
<body>
    @include('core::admin.layouts.sidebar')

    <main class="admin-main">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning">
                {{ session('warning') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
