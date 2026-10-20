<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'LCMS Core') . ' — Administration')</title>

    <!-- Google Font: Source Sans 3 (AdminLTE 4) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">

    <!-- Overlayscrollbars CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- AdminLTE 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta3/dist/css/adminlte.min.css">

    <!-- Compatibility & Tweaks Layer -->
    <style>
        :root {
            --primary: #0d6efd;
            --primary-hover: #0b5ed7;
            --danger: #dc3545;
            --danger-hover: #bb2d3b;
            --success: #198754;
            --warning: #ffc107;
            --muted-color: #6c757d;
            --border-color: #dee2e6;
        }

        /* Layout & Header Compatibility */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--bs-border-color, #dee2e6);
        }
        .admin-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--bs-heading-color, #212529);
        }

        /* Inline action forms in tables */
        .actions-form {
            display: inline-block;
            margin-right: 0.25rem;
            margin-bottom: 0;
        }

        /* Legacy badge mapping */
        .badge-available { background-color: #6c757d; color: #ffffff; }
        .badge-installed { background-color: #0d6efd; color: #ffffff; }
        .badge-active { background-color: #198754; color: #ffffff; }
        .badge-disabled { background-color: #ffc107; color: #000000; }
        .badge-error { background-color: #dc3545; color: #ffffff; }

        /* Legacy alert mapping */
        .alert-error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        /* Sidebar brand & item refinement */
        .app-sidebar .sidebar-brand {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .app-sidebar .nav-header {
            letter-spacing: 0.05em;
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
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-error alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
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
        document.addEventListener("DOMContentLoaded", function () {
            const sidebarWrapper = document.querySelector(".sidebar-wrapper");
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== "undefined") {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: "os-theme-light",
                        autoHide: "leave",
                        clickScroll: true,
                    },
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
