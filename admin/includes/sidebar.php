<?php
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    $navCounts = get_admin_WaitCounts($dbConnection);
    $totalAlerts = $navCounts['pending_requests'] + $navCounts['pending_applications'] + $navCounts['pending_onboarding'] + $navCounts['unread_messages'];

    $pageMeta = [
        'dashboard'    => ['title' => 'Executive Dashboard', 'sub' => 'Overview of care operations, applications & inquiries'],
        'requests'     => ['title' => 'Service Requests', 'sub' => 'Manage client home care requests and caregiver assignments'],
        'applications' => ['title' => 'Caregiver Applications', 'sub' => 'Review, vet, and onboard prospective caregivers'],
        'onboarding'   => ['title' => 'Staff Onboarding Packets', 'sub' => 'Verify 13-point staff onboarding documents, tax forms & signed declarations'],
        'caregivers'   => ['title' => 'Caregivers & Assignments', 'sub' => 'Manage active care staff, rates, background checks & schedules'],
        'contacts'     => ['title' => 'Contact Inbox', 'sub' => 'Read and respond to website inquiries and messages'],
        'profile'      => ['title' => 'Account & Admin Settings', 'sub' => 'Manage your administrator profile, security & team access'],
    ];
    $activeMeta = $pageMeta[$currentPage] ?? ['title' => ucfirst($currentPage), 'sub' => 'Care That Feels Like Home Admin Console'];
?>
    <!-- Mobile Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="<?= PROOT; ?>admin/dashboard" class="sidebar-brand">
            <img src="<?= PROOT; ?>assets/media/logo/logo.jpg" alt="Logo">
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-title">Care Console</span>
                <span class="sidebar-brand-sub">Admin Portal</span>
            </div>
        </a>

        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Overview</div>
            <a href="<?= PROOT; ?>admin/dashboard" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </span>
            </a>

            <div class="sidebar-section-label mt-3">Care Operations</div>
            <a href="<?= PROOT; ?>admin/requests" class="sidebar-link <?= $currentPage === 'requests' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <i class="fas fa-hand-holding-medical"></i>
                    <span>Service Requests</span>
                </span>
                <?php if ($navCounts['pending_requests'] > 0): ?>
                    <span class="sidebar-badge badge-alert" title="Pending requests"><?= $navCounts['pending_requests']; ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= PROOT; ?>admin/applications" class="sidebar-link <?= $currentPage === 'applications' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <i class="fas fa-user-nurse"></i>
                    <span>Applications</span>
                </span>
                <?php if ($navCounts['pending_applications'] > 0): ?>
                    <span class="sidebar-badge badge-alert" title="Pending applications"><?= $navCounts['pending_applications']; ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= PROOT; ?>admin/onboarding" class="sidebar-link <?= $currentPage === 'onboarding' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Staff Onboarding</span>
                </span>
                <?php if ($navCounts['pending_onboarding'] > 0): ?>
                    <span class="sidebar-badge badge-alert" title="Pending onboarding packets"><?= $navCounts['pending_onboarding']; ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= PROOT; ?>admin/caregivers" class="sidebar-link <?= $currentPage === 'caregivers' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <i class="fas fa-users-gear"></i>
                    <span>Caregivers & Staff</span>
                </span>
                <?php if ($navCounts['active_caregivers'] > 0): ?>
                    <span class="sidebar-badge"><?= $navCounts['active_caregivers']; ?></span>
                <?php endif; ?>
            </a>

            <a href="<?= PROOT; ?>admin/contacts" class="sidebar-link <?= $currentPage === 'contacts' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Contact Inbox</span>
                </span>
                <?php if ($navCounts['unread_messages'] > 0): ?>
                    <span class="sidebar-badge badge-danger-soft" title="Unread messages"><?= $navCounts['unread_messages']; ?></span>
                <?php endif; ?>
            </a>

            <div class="sidebar-section-label mt-3">Administration</div>
            <a href="<?= PROOT; ?>admin/profile" class="sidebar-link <?= $currentPage === 'profile' ? 'active' : ''; ?>">
                <span class="sidebar-link-left">
                    <i class="fas fa-user-shield"></i>
                    <span>Profile & Admins</span>
                </span>
            </a>

            <a href="<?= PROOT; ?>" target="_blank" class="sidebar-link">
                <span class="sidebar-link-left">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    <span>Live Website</span>
                </span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <div class="avatar-circle" style="background: rgba(13, 148, 136, 0.25); color: #5eead4;">
                        <?= strtoupper(substr($admin_data['first'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="text-truncate">
                        <div class="fw-semibold text-white text-truncate" style="font-size: 0.86rem;">
                            <?= e($admin_data['user_fullname'] ?? 'Administrator'); ?>
                        </div>
                        <div class="text-secondary text-truncate" style="font-size: 0.73rem;">
                            <?= e($admin_data['user_email'] ?? ''); ?>
                        </div>
                    </div>
                </div>
                <a href="<?= PROOT; ?>admin/logout" class="btn btn-sm btn-outline-danger border-0" title="Sign Out">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Column -->
    <div class="admin-main">
        <!-- Sticky Topbar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light border d-lg-none" id="sidebarToggleBtn" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1 class="h5 fw-bold mb-0 text-dark"><?= e($activeMeta['title']); ?></h1>
                    <p class="text-muted mb-0 d-none d-md-block" style="font-size: 0.78rem;"><?= e($activeMeta['sub']); ?></p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <!-- Quick Action Alerts Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light border position-relative rounded-3 px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="far fa-bell text-secondary"></i>
                        <?php if ($totalAlerts > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                <?= $totalAlerts > 99 ? '99+' : $totalAlerts; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2" style="min-width: 280px;">
                        <li class="px-3 py-2 border-bottom">
                            <span class="fw-bold small text-uppercase text-muted">Pending Attention</span>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 d-flex justify-content-between align-items-center" href="<?= PROOT; ?>admin/requests?status=pending">
                                <span><i class="fas fa-hand-holding-medical text-warning me-2"></i>Pending Requests</span>
                                <span class="badge bg-warning text-dark rounded-pill"><?= $navCounts['pending_requests']; ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 d-flex justify-content-between align-items-center" href="<?= PROOT; ?>admin/applications?status=pending">
                                <span><i class="fas fa-user-nurse text-info me-2"></i>Pending Applications</span>
                                <span class="badge bg-info text-dark rounded-pill"><?= $navCounts['pending_applications']; ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 d-flex justify-content-between align-items-center" href="<?= PROOT; ?>admin/onboarding?status=pending">
                                <span><i class="fas fa-clipboard-check text-primary me-2"></i>Pending Onboarding</span>
                                <span class="badge bg-primary rounded-pill"><?= $navCounts['pending_onboarding']; ?></span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 d-flex justify-content-between align-items-center" href="<?= PROOT; ?>admin/contacts?status=unread">
                                <span><i class="fas fa-envelope text-danger me-2"></i>Unread Messages</span>
                                <span class="badge bg-danger rounded-pill"><?= $navCounts['unread_messages']; ?></span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Admin Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light border rounded-3 d-flex align-items-center gap-2 py-1 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-circle" style="width: 30px; height: 30px; font-size: 0.78rem;">
                            <?= strtoupper(substr($admin_data['first'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span class="fw-semibold d-none d-sm-inline small"><?= e($admin_data['first'] ?? 'Admin'); ?></span>
                        <i class="fas fa-chevron-down text-muted" style="font-size: 0.7rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2" style="min-width: 210px;">
                        <li class="px-3 py-2 border-bottom mb-1">
                            <div class="fw-bold small"><?= e($admin_data['user_fullname'] ?? 'Admin'); ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?= e($admin_data['user_email'] ?? ''); ?></div>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 small" href="<?= PROOT; ?>admin/profile">
                                <i class="fas fa-user-cog me-2 text-secondary"></i>Profile & Settings
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 small" href="<?= PROOT; ?>" target="_blank">
                                <i class="fas fa-globe me-2 text-secondary"></i>View Public Website
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 small text-danger fw-semibold" href="<?= PROOT; ?>admin/logout">
                                <i class="fas fa-sign-out-alt me-2"></i>Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Body Content -->
        <main class="admin-content">
