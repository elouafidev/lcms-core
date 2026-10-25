<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'LCMS Core') . ' — ' . __('Administration'))</title>

    <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Overlayscrollbars CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- AdminLTE 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">

    <!-- Modern Dark & Slate Theme Integration -->
    <style>
        :root {
            --bs-font-sans-serif: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --bs-font-monospace: 'JetBrains Mono', monospace;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --danger: #e11d48;
            --danger-hover: #be123c;
            --success: #10b981;
            --warning: #f59e0b;
            --muted-color: #64748b;
            --border-color: #e2e8f0;
            --lcms-dark: #050914;
            --lcms-slate-900: #0f172a;
            --lcms-slate-800: #1e293b;
        }

        body.layout-fixed {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: var(--bs-font-sans-serif);
        }

        .app-main.admin-main {
            background-color: #f8fafc;
            min-height: calc(100vh - 4.5rem);
        }

        /* Sidebar Styling (Matches Login & Welcome) */
        .app-sidebar.admin-sidebar {
            background-color: #050914 !important;
            border-right: 1px solid #1e293b !important;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15) !important;
        }

        .app-sidebar .sidebar-brand {
            border-bottom: 1px solid #1e293b !important;
            background-color: #050914 !important;
            height: 4.5rem;
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
        }

        .app-sidebar .nav-header {
            color: #64748b !important;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 1rem 1.25rem 0.35rem 1.25rem;
        }

        .app-sidebar .nav-link {
            color: #94a3b8 !important;
            border-radius: 0.75rem;
            margin: 0.15rem 0.65rem;
            padding: 0.6rem 0.85rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.15s ease-in-out;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .app-sidebar .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(30, 41, 59, 0.7) !important;
        }

        .app-sidebar .nav-link.active {
            color: #ffffff !important;
            background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
            font-weight: 600;
        }

        .app-sidebar .nav-link .nav-icon {
            font-size: 1.15rem;
            color: #60a5fa;
            transition: color 0.15s ease;
        }

        .app-sidebar .nav-link.active .nav-icon {
            color: #ffffff !important;
        }

        /* Navbar Header */
        .app-header.navbar {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
            height: 4.5rem;
        }

        /* Modern Card Styling */
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            background-color: #ffffff;
            margin-bottom: 1.5rem;
        }

        .card-header {
            border-bottom: 1px solid #f1f5f9;
            background-color: #ffffff;
            border-top-left-radius: 1rem !important;
            border-top-right-radius: 1rem !important;
            padding: 1rem 1.25rem;
        }

        .card-outline.card-primary {
            border-top: 3px solid #2563eb;
        }

        .card-outline.card-secondary {
            border-top: 3px solid #64748b;
        }

        /* Modern Buttons */
        .btn {
            border-radius: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            border-radius: 0.6rem;
            font-size: 0.85rem;
        }

        .btn-primary {
            background-color: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: #1d4ed8;
            border-color: #1d4ed8;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }

        /* Modern Pill Badges */
        .badge {
            font-weight: 600;
            border-radius: 0.5rem;
            padding: 0.35rem 0.65rem;
            font-size: 0.8rem;
        }

        .badge-active {
            background-color: #ecfdf5 !important;
            color: #047857 !important;
            border: 1px solid #a7f3d0;
        }

        .badge-available {
            background-color: #f8fafc !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0;
        }

        .badge-disabled {
            background-color: #fffbeb !important;
            color: #b45309 !important;
            border: 1px solid #fde68a;
        }

        .badge-error {
            background-color: #fff1f2 !important;
            color: #be123c !important;
            border: 1px solid #fecdd3;
        }

        /* Alerts */
        .alert {
            border-radius: 0.85rem;
            border: 1px solid transparent;
        }

        .alert-error, .alert-danger {
            background-color: #fff1f2;
            color: #be123c;
            border-color: #fecdd3;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .alert-warning {
            background-color: #fffbeb;
            color: #b45309;
            border-color: #fde68a;
        }

        /* Layout & Header Compatibility */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }

        /* Inline action forms in tables */
        .actions-form {
            display: inline-block;
            margin-right: 0.25rem;
            margin-bottom: 0;
        }
    </style>

    @stack('styles')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <!-- Navbar Header -->
        @include('core::admin.layouts.navbar')

        <!-- Main Sidebar -->
        @include('core::admin.layouts.sidebar')

        <!-- App Main Body -->
        <main class="app-main admin-main">
            <div class="app-content py-4">
                <div class="container-fluid">
                    <!-- Session Notifications -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-error alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                        </div>
                    @endif

                    @if(isset($errors) && $errors->any())
                        <div class="alert alert-danger alert-error alert-dismissible fade show shadow-sm" role="alert">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                                <strong>{{ __('Please correct the following errors:') }}</strong>
                            </div>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                        </div>
                    @endif

                    <!-- Page Content -->
                    @yield('content')
                </div>
            </div>
        </main>

        <!-- Footer -->
        @include('core::admin.layouts.footer')
    </div>

    <!-- Scripts: Overlayscrollbars, Popper, Bootstrap 5, AdminLTE 4 -->
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/js/adminlte.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarWrapper = document.querySelector('.sidebar-wrapper');
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: 'os-theme-light',
                        autoHide: 'leave',
                        clickScroll: true,
                    },
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>