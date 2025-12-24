<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="/css/main.css" rel="stylesheet">
    <link href="/css/modern-theme.css" rel="stylesheet">
    <link href="/css/forms.css" rel="stylesheet">
    <link href="/css/dashboard-modern.css" rel="stylesheet">
    <link href="/css/mobile.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 70px;
            /* Space for fixed navbar */
        }

        .sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            z-index: 1020;
            padding: 48px 0 0;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
            width: 240px;
            background-color: white;
            transition: transform 0.3s ease-in-out;
            transform: translateX(0);
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1019;
        }

        .sidebar-overlay.show {
            display: block;
        }

        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
            padding: 10px 20px;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .sidebar .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        main.main-content-area,
        .main-content-area {
            margin-left: 240px !important;
            width: calc(100% - 240px);
            padding: 20px 20px 20px 50px !important;
            /* Added left padding (50px) for spacing from sidebar */
            padding-top: 30px !important;
            /* Extra spacing from navbar */
            transition: margin-left 0.3s ease-in-out, width 0.3s ease-in-out;
        }

        .sidebar.collapsed~main.main-content-area,
        .sidebar.collapsed~.main-content-area {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .container-fluid {
            padding-left: 0 !important;
            /* Remove extra left padding from container */
        }

        .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .container-fluid {
            padding-top: 25px;
            /* Additional spacing */
        }

        .d-flex.justify-content-between.flex-wrap.flex-md-nowrap.align-items-center {
            margin-top: 10px;
            padding-top: 15px;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 56px;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            main.main-content-area,
            .main-content-area {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 20px 15px !important;
            }
        }
    </style>
</head>

<body class="with-navbar">
    <nav class="navbar navbar-dark bg-primary fixed-top shadow-sm">
        <div class="container-fluid">
            <button class="btn btn-link text-white me-2" id="sidebarToggle" type="button" aria-label="Toggle sidebar"
                style="text-decoration: none;">
                <i class="fas fa-bars fa-lg"></i>
            </button>
            <a class="navbar-brand" href="/dashboard.php">
                <i class="fas fa-bus me-2"></i><?php echo SITE_NAME; ?>
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    <?php
                    $role = getUserRole();
                    $badgeClass = $role === 'admin' ? 'bg-danger' : ($role === 'driver' ? 'bg-success' : 'bg-info');
                    ?>
                    <span class="badge <?php echo $badgeClass; ?> ms-2">
                        <i
                            class="fas fa-<?php echo $role === 'admin' ? 'shield-alt' : ($role === 'driver' ? 'user-tie' : 'users'); ?> me-1"></i>
                        <?php echo ucfirst($role); ?>
                    </span>
                </span>
                <a href="/logout.php" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>