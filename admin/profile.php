<?php
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    $currentAdminId = $admin_data['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Update logged-in admin's profile info
        if (isset($_POST['update_profile'])) {
            $fullname = trim($_POST['user_fullname'] ?? '');
            $email    = trim($_POST['user_email'] ?? '');
            $phone    = trim($_POST['user_phone'] ?? '');
            $address  = trim($_POST['user_address'] ?? '');

            if (empty($fullname) || empty($email)) {
                $_SESSION['flash_error'] = "Full name and email address are required.";
            } else {
                try {
                    $chk = $dbConnection->prepare("SELECT id FROM care_users WHERE user_email = ? AND user_id != ? LIMIT 1");
                    $chk->execute([$email, $currentAdminId]);
                    if ($chk->fetchColumn()) {
                        $_SESSION['flash_error'] = "That email address is already in use by another account.";
                    } else {
                        $stmt = $dbConnection->prepare("
                            UPDATE care_users 
                            SET user_fullname = ?, user_email = ?, user_phone = ?, user_address = ? 
                            WHERE user_id = ?
                        ");
                        $stmt->execute([$fullname, $email, $phone ?: null, $address ?: null, $currentAdminId]);
                        $_SESSION['flash_success'] = "Your administrator profile has been updated.";
                    }
                } catch (PDOException $e) {
                    $_SESSION['flash_error'] = "Error updating profile: " . $e->getMessage();
                }
            }
            redirect(PROOT . 'admin/profile');
        }

        // 2. Change logged-in admin's password
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $_SESSION['flash_error'] = "Please fill in all password fields.";
            } elseif (strlen($newPassword) < 6) {
                $_SESSION['flash_error'] = "New password must be at least 6 characters long.";
            } elseif ($newPassword !== $confirmPassword) {
                $_SESSION['flash_error'] = "New password and confirmation do not match.";
            } elseif (!password_verify($currentPassword, $admin_data['user_password'])) {
                $_SESSION['flash_error'] = "Your current password is incorrect.";
            } else {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $dbConnection->prepare("UPDATE care_users SET user_password = ? WHERE user_id = ?");
                $stmt->execute([$newHash, $currentAdminId]);
                $_SESSION['flash_success'] = "Password updated successfully!";
            }
            redirect(PROOT . 'admin/profile');
        }

        // 3. Create new administrator account
        if (isset($_POST['create_admin'])) {
            $newAdminName  = trim($_POST['new_admin_name'] ?? '');
            $newAdminEmail = trim($_POST['new_admin_email'] ?? '');
            $newAdminPhone = trim($_POST['new_admin_phone'] ?? '');
            $newAdminPass  = $_POST['new_admin_password'] ?? '';

            if (empty($newAdminName) || empty($newAdminEmail) || empty($newAdminPass)) {
                $_SESSION['flash_error'] = "Name, email, and password are required to create an admin.";
            } elseif (strlen($newAdminPass) < 6) {
                $_SESSION['flash_error'] = "Admin password must be at least 6 characters.";
            } else {
                try {
                    $chk = $dbConnection->prepare("SELECT id FROM care_users WHERE user_email = ? LIMIT 1");
                    $chk->execute([$newAdminEmail]);
                    if ($chk->fetchColumn()) {
                        $_SESSION['flash_error'] = "An account with email $newAdminEmail already exists.";
                    } else {
                        $hash = password_hash($newAdminPass, PASSWORD_BCRYPT);
                        $ins = $dbConnection->prepare("
                            INSERT INTO care_users (user_id, user_fullname, user_email, user_phone, user_password, user_type, user_status)
                            VALUES (?, ?, ?, ?, ?, 'admin', 'active')
                        ");
                        $ins->execute([guidv4(), $newAdminName, $newAdminEmail, $newAdminPhone ?: null, $hash]);
                        $_SESSION['flash_success'] = "Administrator account created for $newAdminName.";
                    }
                } catch (PDOException $e) {
                    $_SESSION['flash_error'] = "Error creating admin: " . $e->getMessage();
                }
            }
            redirect(PROOT . 'admin/profile');
        }

        // 4. Toggle or remove another admin
        if (isset($_POST['delete_admin']) && !empty($_POST['target_user_id'])) {
            $targetId = $_POST['target_user_id'];
            if ($targetId === $currentAdminId) {
                $_SESSION['flash_error'] = "You cannot delete your own active administrator account.";
            } else {
                $del = $dbConnection->prepare("DELETE FROM care_users WHERE user_id = ? AND user_type = 'admin'");
                $del->execute([$targetId]);
                $_SESSION['flash_success'] = "Administrator account removed.";
            }
            redirect(PROOT . 'admin/profile');
        }
    }

    $admins = $dbConnection->query("SELECT * FROM care_users WHERE user_type = 'admin' ORDER BY id ASC")->fetchAll();

    $pageTitle = "Profile & Admin Settings";
    include 'includes/header.php';
    include 'includes/sidebar.php';
?>

<div class="row g-4">
    <!-- Left Column: My Profile & Security -->
    <div class="col-lg-6">
        <!-- Profile Details Card -->
        <div class="card-modern p-4 mb-4">
            <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                <div class="avatar-circle" style="width: 56px; height: 56px; font-size: 1.35rem;">
                    <?= strtoupper(substr($admin_data['first'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <h5 class="fw-bold mb-0"><?= e($admin_data['user_fullname']); ?></h5>
                    <span class="badge-soft badge-soft-primary mt-1">
                        <i class="fas fa-user-shield"></i> System Administrator
                    </span>
                </div>
            </div>

            <h6 class="fw-bold mb-3">Personal Information</h6>
            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Full Name</label>
                    <input type="text" name="user_fullname" class="form-control" value="<?= e($admin_data['user_fullname']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email Address</label>
                    <input type="email" name="user_email" class="form-control" value="<?= e($admin_data['user_email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Phone Number</label>
                    <input type="text" name="user_phone" class="form-control" value="<?= e($admin_data['user_phone'] ?? ''); ?>" placeholder="+1 (701) 000-0000">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Office / Mailing Address</label>
                    <input type="text" name="user_address" class="form-control" value="<?= e($admin_data['user_address'] ?? ''); ?>" placeholder="121 E Arikara Ave, Bismarck, ND">
                </div>
                <button type="submit" name="update_profile" class="btn btn-teal px-4">
                    <i class="fas fa-save me-2"></i>Update Profile
                </button>
            </form>
        </div>

        <!-- Change Password Card -->
        <div class="card-modern p-4">
            <h6 class="fw-bold mb-1"><i class="fas fa-key text-teal me-2" style="color:#0d9488;"></i>Change Password</h6>
            <p class="text-muted small mb-3">Ensure your account uses a strong, unique password.</p>
            <form method="post" action="">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="6" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn btn-outline-dark fw-semibold px-4">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Administrator Team Directory -->
    <div class="col-lg-6">
        <div class="card-modern overflow-hidden mb-4">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0">Administrator Accounts</h6>
                    <span class="text-muted small">Users with full access to this console</span>
                </div>
                <button type="button" class="btn btn-sm btn-teal" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fas fa-user-plus me-1"></i>Add Admin
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Administrator</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $adm): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <?= e($adm['user_fullname']); ?>
                                        <?php if ($adm['user_id'] === $currentAdminId): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small"><?= e($adm['user_email']); ?></div>
                                </td>
                                <td class="small"><?= e($adm['user_phone'] ?: '—'); ?></td>
                                <td>
                                    <span class="badge-soft badge-soft-success"><?= ucfirst(e($adm['user_status'])); ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if ($adm['user_id'] !== $currentAdminId): ?>
                                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Remove administrator access for <?= e($adm['user_fullname']); ?>?');">
                                            <input type="hidden" name="target_user_id" value="<?= e($adm['user_id']); ?>">
                                            <button type="submit" name="delete_admin" class="btn btn-light border btn-action" title="Remove Admin">
                                                <i class="fas fa-trash-can text-danger"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Active Session</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold mb-0">Add New Administrator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="new_admin_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="new_admin_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Phone Number</label>
                        <input type="text" name="new_admin_phone" class="form-control">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Temporary Password <span class="text-danger">*</span></label>
                        <input type="password" name="new_admin_password" class="form-control" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_admin" class="btn btn-teal px-4">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

