            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="d-flex flex-column p-3">
                    <h4 class="text-center mb-4">Admin Panel</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item mb-2">
                            <a href="dashboard.php" class="nav-link <?php echo PROOT  . 'admin/dashboard.php' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a href="contacts.php" class="nav-link <?php echo PROOT  . 'admin/contacts.php' ? 'active' : ''; ?>">
                                <i class="fas fa-address-book me-2"></i> Contacts
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a href="applications.php" class="nav-link <?php echo PROOT  . 'admin/applications.php' ? 'active' : ''; ?>">
                                <i class="fas fa-file-alt me-2"></i> Applications
                            </a>
                        </li>
                        <li class="nav-item mb-2">
                            <a href="requests.php" class="nav-link <?php echo PROOT  . 'admin/requests.php' ? 'active' : ''; ?>">
                                <i class="fas fa-tasks me-2"></i> Requests
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a href="logout.php" class="nav-link text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo ucfirst(str_replace('.php', '', basename($_SERVER['PHP_SELF']))); ?></h2>
                    <div>
                        <span class="me-3">Welcome, <?= $admin_data['first']; ?></span>
                    </div>
                </div>