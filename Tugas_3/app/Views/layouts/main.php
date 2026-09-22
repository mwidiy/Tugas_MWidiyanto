<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'CMS Toko Online') ?> - Sistem Simulasi Pembelian</title>
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --bs-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --sidebar-width: 260px;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            --card-radius: 16px;
        }

        body {
            font-family: var(--bs-font-sans-serif);
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 1.5rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .sidebar-menu {
            padding: 1.25rem 0.75rem;
            flex: 1;
        }

        .menu-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.05em;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.25rem;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #64748b;
            font-weight: 500;
            font-size: 0.925rem;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }

        .nav-link-custom i {
            font-size: 1.2rem;
            transition: transform 0.2s ease;
        }

        .nav-link-custom:hover {
            color: #4f46e5;
            background-color: #f5f3ff;
            transform: translateX(4px);
        }

        .nav-link-custom.active {
            color: #ffffff;
            background: var(--primary-gradient);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        /* Main Content Wrapper */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            padding: 1.75rem 2rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .top-bar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--card-radius);
            padding: 0.85rem 1.5rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        /* Cards */
        .card-custom {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: var(--card-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-stat {
            position: relative;
            overflow: hidden;
        }

        .card-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .stat-icon-wrapper {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Tables */
        .table-custom {
            margin-bottom: 0;
        }

        .table-custom thead th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.825rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.85rem 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-custom tbody td {
            padding: 1rem;
            vertical-align: middle;
            color: #334155;
            font-size: 0.9rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-custom tbody tr:hover td {
            background-color: #fafafa;
        }

        /* Custom Badge */
        .badge-pill {
            border-radius: 30px;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
            font-size: 0.8rem;
        }

        /* Footer */
        .footer-custom {
            margin-top: auto;
            padding-top: 2rem;
            color: #94a3b8;
            font-size: 0.85rem;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebarNav">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <i class="bi bi-cart-check-fill"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-dark">CMS Store</h6>
                <small class="text-muted" style="font-size: 0.75rem;">Simulasi Transaksi CI4</small>
            </div>
        </div>

        <div class="sidebar-menu">
            <div class="menu-label">Menu Utama</div>
            <a href="<?= base_url('/') ?>" class="nav-link-custom <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= base_url('/transactions') ?>" class="nav-link-custom <?= ($activeMenu ?? '') === 'transactions' ? 'active' : '' ?>">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Data Transaksi</span>
            </a>

            <div class="menu-label mt-3">Master Data (CRUD)</div>
            <a href="<?= base_url('/products') ?>" class="nav-link-custom <?= ($activeMenu ?? '') === 'products' ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i>
                <span>Data Produk & Stok</span>
            </a>
            <a href="<?= base_url('/users') ?>" class="nav-link-custom <?= ($activeMenu ?? '') === 'users' ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Data Pelanggan</span>
            </a>
        </div>

        <div class="p-3 border-top bg-light">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-success rounded-circle" style="width: 10px; height: 10px;"></div>
                <small class="text-muted fw-semibold" style="font-size: 0.78rem;">CodeIgniter 4 Active</small>
            </div>
            <small class="text-secondary d-block mt-1" style="font-size: 0.72rem;">MySQL Database Connected</small>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="top-bar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button" onclick="document.getElementById('sidebarNav').classList.toggle('show')">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><?= esc($title ?? 'Dashboard') ?></h5>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="text-muted small d-none d-md-flex align-items-center gap-1">
                    <i class="bi bi-calendar3 text-primary"></i>
                    <span><?= date('d M Y') ?></span>
                </div>
            </div>
        </header>

        <!-- Flash Alert Messages -->
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div><?= session()->getFlashdata('success') ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                <div><?= session()->getFlashdata('error') ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Dynamic Content Section -->
        <?= $this->renderSection('content') ?>

        <!-- Footer -->
        <footer class="footer-custom">
            <p class="mb-0">&copy; <?= date('Y') ?> <strong>CMS Simulasi Pembelian Produk</strong> &mdash; Built with CodeIgniter 4 & MySQL</p>
        </footer>
    </div>

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
