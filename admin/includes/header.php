<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>Care Admin Console</title>
    <link rel="icon" type="image/jpg" href="<?= PROOT; ?>assets/media/logo/logo.jpg" sizes="32x32">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --admin-bg: #f1f5f9;
            --admin-surface: #ffffff;
            --admin-sidebar-bg: #0f172a;
            --admin-sidebar-hover: rgba(255, 255, 255, 0.07);
            --admin-sidebar-active: #0d9488;
            --admin-primary: #0d9488;
            --admin-primary-hover: #0f766e;
            --admin-primary-soft: rgba(13, 148, 136, 0.12);
            --admin-text: #0f172a;
            --admin-muted: #64748b;
            --admin-border: #e2e8f0;
            --sidebar-width: 268px;
        }

        * {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--admin-bg);
            color: var(--admin-text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Layout */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.08);
        }

        .sidebar-brand {
            padding: 1.35rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.85rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-decoration: none;
            color: #fff;
        }

        .sidebar-brand img {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.15);
        }

        .sidebar-brand-text {
            line-height: 1.2;
        }

        .sidebar-brand-title {
            font-weight: 700;
            font-size: 0.98rem;
            color: #ffffff;
            display: block;
            letter-spacing: -0.01em;
        }

        .sidebar-brand-sub {
            font-size: 0.72rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }

        .sidebar-nav {
            padding: 1.25rem 0.9rem;
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-section-label {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            font-weight: 700;
            padding: 0.5rem 0.85rem 0.4rem;
            margin-top: 0.5rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.72rem 0.95rem;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            transition: all 0.18s ease;
        }

        .sidebar-link-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-link i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
            color: #94a3b8;
            transition: color 0.18s ease;
        }

        .sidebar-link:hover {
            background: var(--admin-sidebar-hover);
            color: #ffffff;
        }

        .sidebar-link:hover i {
            color: #5eead4;
        }

        .sidebar-link.active {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35);
        }

        .sidebar-link.active i {
            color: #ffffff;
        }

        .sidebar-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            color: #fff;
        }

        .sidebar-badge.badge-alert {
            background: #f59e0b;
            color: #0f172a;
        }

        .sidebar-badge.badge-danger-soft {
            background: #ef4444;
            color: #ffffff;
        }

        .sidebar-footer {
            padding: 1rem 1.1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.45);
        }

        /* Main Area */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Topbar */
        .admin-topbar {
            height: 70px;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--admin-border);
            padding: 0 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .admin-content {
            padding: 1.75rem;
            flex: 1;
        }

        /* Cards */
        .card-modern {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03), 0 4px 12px rgba(15, 23, 42, 0.02);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .stat-card {
            padding: 1.35rem 1.4rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .stat-value {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
            color: var(--admin-text);
        }

        .stat-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--admin-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Soft Badges */
        .badge-soft {
            padding: 0.38em 0.78em;
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 999px;
            letter-spacing: 0.01em;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .badge-soft-warning { background: #fef3c7; color: #b45309; }
        .badge-soft-info    { background: #e0f2fe; color: #0369a1; }
        .badge-soft-primary { background: #ccfbf1; color: #0f766e; }
        .badge-soft-indigo  { background: #e0e7ff; color: #4338ca; }
        .badge-soft-success { background: #dcfce7; color: #15803d; }
        .badge-soft-danger  { background: #fee2e2; color: #b91c1c; }
        .badge-soft-secondary { background: #f1f5f9; color: #475569; }

        /* Modern Table */
        .table-modern {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table-modern thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--admin-border);
            white-space: nowrap;
        }

        .table-modern tbody td {
            padding: 0.95rem 1.1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.89rem;
            color: #1e293b;
        }

        .table-modern tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Filter Pills */
        .filter-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .filter-pill {
            padding: 0.42rem 0.9rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .filter-pill:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .filter-pill.active {
            background: var(--admin-primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
        }

        .filter-pill .pill-count {
            background: rgba(15, 23, 42, 0.1);
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            font-size: 0.72rem;
        }

        .filter-pill.active .pill-count {
            background: rgba(255, 255, 255, 0.22);
            color: #fff;
        }

        /* Avatar Circle */
        .avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.88rem;
            background: var(--admin-primary-soft);
            color: var(--admin-primary);
            flex-shrink: 0;
        }

        /* Buttons */
        .btn-teal {
            background-color: var(--admin-primary);
            border-color: var(--admin-primary);
            color: #fff;
            font-weight: 600;
        }
        .btn-teal:hover, .btn-teal:focus {
            background-color: var(--admin-primary-hover);
            border-color: var(--admin-primary-hover);
            color: #fff;
        }

        .btn-action {
            width: 34px;
            height: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        /* Mobile Backdrop */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(2px);
            z-index: 1035;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .sidebar-backdrop.show {
                display: block;
            }
            .admin-main {
                margin-left: 0;
            }
            .admin-topbar {
                padding: 0 1rem;
            }
            .admin-content {
                padding: 1.15rem;
            }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
