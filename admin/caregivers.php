<?php
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    // Handle POST actions (PRG pattern)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Add new Caregiver
        if (isset($_POST['add_caregiver'])) {
            $fullname = trim($_POST['fullname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $experience = trim($_POST['experience'] ?? '');
            $serviceType = trim($_POST['service_type'] ?? '');
            $availability = trim($_POST['availability'] ?? '');
            $hourlyRate = !empty($_POST['hourly_rate']) ? (float)$_POST['hourly_rate'] : null;
            $bgStatus = $_POST['bg_status'] ?? 'pending';
            $bio = trim($_POST['bio'] ?? '');

            if (empty($fullname) || empty($email)) {
                $_SESSION['flash_error'] = "Full name and email are required to add a caregiver.";
            } else {
                try {
                    $chk = $dbConnection->prepare("SELECT id FROM care_users WHERE user_email = ? LIMIT 1");
                    $chk->execute([$email]);
                    if ($chk->fetchColumn()) {
                        $_SESSION['flash_error'] = "A user with email $email already exists.";
                    } else {
                        $newUserId = guidv4();
                        $pwdHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
                        $insU = $dbConnection->prepare("
                            INSERT INTO care_users (user_id, user_fullname, user_email, user_phone, user_password, user_type, user_status, user_address)
                            VALUES (?, ?, ?, ?, ?, 'caregiver', 'active', ?)
                        ");
                        $insU->execute([$newUserId, $fullname, $email, $phone ?: null, $pwdHash, $address ?: null]);

                        $insP = $dbConnection->prepare("
                            INSERT INTO care_caregiver_profiles (profile_id, profile_user_id, profile_experience, profile_service_type, profile_availability, profile_bio, profile_hourly_rate, profile_background_check_status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insP->execute([guidv4(), $newUserId, $experience ?: null, $serviceType ?: null, $availability ?: null, $bio ?: null, $hourlyRate, $bgStatus]);

                        $_SESSION['flash_success'] = "Caregiver $fullname added to directory.";
                    }
                } catch (PDOException $e) {
                    $_SESSION['flash_error'] = "Database error: " . $e->getMessage();
                }
            }
            redirect(PROOT . 'admin/caregivers');
        }

        // 2. Update existing Caregiver
        if (isset($_POST['update_caregiver']) && !empty($_POST['user_id'])) {
            $userId = $_POST['user_id'];
            $fullname = trim($_POST['fullname'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $userStatus = $_POST['user_status'] ?? 'active';
            $experience = trim($_POST['experience'] ?? '');
            $serviceType = trim($_POST['service_type'] ?? '');
            $availability = trim($_POST['availability'] ?? '');
            $hourlyRate = $_POST['hourly_rate'] !== '' ? (float)$_POST['hourly_rate'] : null;
            $bgStatus = $_POST['bg_status'] ?? 'pending';
            $bio = trim($_POST['bio'] ?? '');

            try {
                $upU = $dbConnection->prepare("UPDATE care_users SET user_fullname = ?, user_phone = ?, user_status = ? WHERE user_id = ?");
                $upU->execute([$fullname, $phone ?: null, $userStatus, $userId]);

                $chkP = $dbConnection->prepare("SELECT profile_id FROM care_caregiver_profiles WHERE profile_user_id = ? LIMIT 1");
                $chkP->execute([$userId]);
                if ($chkP->fetchColumn()) {
                    $upP = $dbConnection->prepare("
                        UPDATE care_caregiver_profiles
                        SET profile_experience = ?, profile_service_type = ?, profile_availability = ?, profile_hourly_rate = ?, profile_background_check_status = ?, profile_bio = ?
                        WHERE profile_user_id = ?
                    ");
                    $upP->execute([$experience ?: null, $serviceType ?: null, $availability ?: null, $hourlyRate, $bgStatus, $bio ?: null, $userId]);
                } else {
                    $insP = $dbConnection->prepare("
                        INSERT INTO care_caregiver_profiles (profile_id, profile_user_id, profile_experience, profile_service_type, profile_availability, profile_hourly_rate, profile_background_check_status, profile_bio)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insP->execute([guidv4(), $userId, $experience ?: null, $serviceType ?: null, $availability ?: null, $hourlyRate, $bgStatus, $bio ?: null]);
                }

                $_SESSION['flash_success'] = "Caregiver profile updated.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Failed to update caregiver: " . $e->getMessage();
            }
            redirect(PROOT . 'admin/caregivers');
        }

        // 3. Delete Caregiver
        if (isset($_POST['delete_caregiver']) && !empty($_POST['user_id'])) {
            $userId = $_POST['user_id'];
            try {
                $del = $dbConnection->prepare("DELETE FROM care_users WHERE user_id = ? AND user_type = 'caregiver'");
                $del->execute([$userId]);
                $_SESSION['flash_success'] = "Caregiver removed from directory.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Failed to remove caregiver: " . $e->getMessage();
            }
            redirect(PROOT . 'admin/caregivers');
        }

        // 4. Create / Update Care Assignment
        if (isset($_POST['create_assignment'])) {
            $requestId = $_POST['assignment_request_id'] ?? '';
            $caregiverId = $_POST['assignment_caregiver_id'] ?? '';
            $startDate = $_POST['assignment_start_date'] ?? date('Y-m-d');
            $endDate = !empty($_POST['assignment_end_date']) ? $_POST['assignment_end_date'] : null;
            $notes = trim($_POST['assignment_notes'] ?? '');

            if (!empty($requestId) && !empty($caregiverId)) {
                try {
                    $insA = $dbConnection->prepare("
                        INSERT INTO care_assignments (assignment_id, assignment_request_id, assignment_caregiver_id, assignment_start_date, assignment_end_date, assignment_status, assignment_notes)
                        VALUES (?, ?, ?, ?, ?, 'active', ?)
                    ");
                    $insA->execute([guidv4(), $requestId, $caregiverId, $startDate, $endDate, $notes ?: null]);

                    // Also update care_service_requests status & assigned_to name
                    $cgStmt = $dbConnection->prepare("SELECT user_fullname FROM care_users WHERE user_id = ? LIMIT 1");
                    $cgStmt->execute([$caregiverId]);
                    $cgName = $cgStmt->fetchColumn();

                    $upReq = $dbConnection->prepare("UPDATE care_service_requests SET request_status = 'assigned', request_assigned_to = ? WHERE request_id = ?");
                    $upReq->execute([$cgName ?: 'Assigned Caregiver', $requestId]);

                    $_SESSION['flash_success'] = "Care assignment created and service request marked as assigned.";
                } catch (PDOException $e) {
                    $_SESSION['flash_error'] = "Error creating assignment: " . $e->getMessage();
                }
            }
            redirect(PROOT . 'admin/caregivers?tab=assignments');
        }

        // 5. Update Assignment Status
        if (isset($_POST['update_assignment_status']) && !empty($_POST['assignment_id'])) {
            $assignId = $_POST['assignment_id'];
            $newSt = $_POST['assignment_status'] ?? 'active';
            $endDate = !empty($_POST['assignment_end_date']) ? $_POST['assignment_end_date'] : null;
            if (in_array($newSt, ['active', 'completed', 'cancelled'], true)) {
                $upA = $dbConnection->prepare("UPDATE care_assignments SET assignment_status = ?, assignment_end_date = ? WHERE assignment_id = ?");
                $upA->execute([$newSt, $endDate, $assignId]);
                $_SESSION['flash_success'] = "Assignment status updated to $newSt.";
            }
            redirect(PROOT . 'admin/caregivers?tab=assignments');
        }
    }

    $activeTab = ($_GET['tab'] ?? 'staff') === 'assignments' ? 'assignments' : 'staff';

    // Fetch Caregivers
    $caregivers = get_all_caregivers($dbConnection);

    // Fetch Assignments joined with Caregivers & Service Requests
    $assignmentsSql = "
        SELECT a.*, 
               u.user_fullname AS caregiver_name, u.user_phone AS caregiver_phone,
               r.id AS req_num_id, r.request_name AS client_name, r.request_service_type, r.request_frequency
        FROM care_assignments a
        LEFT JOIN care_users u ON a.assignment_caregiver_id = u.user_id
        LEFT JOIN care_service_requests r ON a.assignment_request_id = r.request_id
        ORDER BY a.created_at DESC
    ";
    $assignments = $dbConnection->query($assignmentsSql)->fetchAll();

    // Fetch open/assignable service requests
    $openRequests = $dbConnection->query("SELECT id, request_id, request_name, request_service_type, request_start_date FROM care_service_requests WHERE request_status IN ('pending', 'approved', 'assigned') ORDER BY id DESC")->fetchAll();

    $pageTitle = "Caregivers & Assignments";
    include 'includes/header.php';
    include 'includes/sidebar.php';
?>

<!-- Top Navigation Tabs & Actions -->
<div class="card-modern p-3 p-md-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="filter-pills">
            <a href="<?= PROOT; ?>admin/caregivers?tab=staff" class="filter-pill <?= $activeTab === 'staff' ? 'active' : ''; ?>">
                <i class="fas fa-user-nurse"></i> Caregivers Directory <span class="pill-count"><?= count($caregivers); ?></span>
            </a>
            <a href="<?= PROOT; ?>admin/caregivers?tab=assignments" class="filter-pill <?= $activeTab === 'assignments' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i> Care Assignments <span class="pill-count"><?= count($assignments); ?></span>
            </a>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-teal px-3" data-bs-toggle="modal" data-bs-target="#addCaregiverModal">
                <i class="fas fa-plus me-1"></i>Add Caregiver
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark px-3" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                <i class="fas fa-link me-1"></i>New Assignment
            </button>
        </div>
    </div>
</div>

<?php if ($activeTab === 'staff'): ?>
    <!-- Caregivers Directory Table -->
    <div class="card-modern overflow-hidden">
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>Caregiver</th>
                        <th>Experience & Specialty</th>
                        <th>Availability</th>
                        <th>Hourly Rate</th>
                        <th>Background Check</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($caregivers)): ?>
                        <?php foreach ($caregivers as $cg): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle">
                                            <?= strtoupper(substr(e($cg['user_fullname']), 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark"><?= e($cg['user_fullname']); ?></div>
                                            <div class="text-muted small">
                                                <?= e($cg['user_email']); ?>
                                                <?= !empty($cg['user_phone']) ? ' &bull; ' . e($cg['user_phone']) : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium text-truncate" style="max-width: 220px;"><?= e($cg['profile_service_type'] ?: 'General Home Care'); ?></div>
                                    <div class="text-muted small">Exp: <?= e($cg['profile_experience'] ?: 'Not specified'); ?></div>
                                </td>
                                <td><?= e($cg['profile_availability'] ?: 'Flexible'); ?></td>
                                <td class="fw-semibold">
                                    <?= $cg['profile_hourly_rate'] !== null ? '$' . number_format((float)$cg['profile_hourly_rate'], 2) . '/hr' : '<span class="text-muted small">Not set</span>'; ?>
                                </td>
                                <td>
                                    <?php
                                        $bg = $cg['profile_background_check_status'] ?? 'pending';
                                        $bgClass = $bg === 'passed' ? 'badge-soft-success' : ($bg === 'failed' ? 'badge-soft-danger' : 'badge-soft-warning');
                                    ?>
                                    <span class="badge-soft <?= $bgClass; ?>">
                                        <i class="fas <?= $bg === 'passed' ? 'fa-shield-check' : 'fa-clock'; ?>"></i>
                                        <?= ucfirst(e($bg)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-soft <?= $cg['user_status'] === 'active' ? 'badge-soft-success' : 'badge-soft-secondary'; ?>">
                                        <?= ucfirst(e($cg['user_status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-light border btn-action me-1 edit-cg-btn"
                                            title="Edit Caregiver Profile"
                                            data-cg='<?= htmlspecialchars(json_encode([
                                                'user_id' => $cg['user_id'],
                                                'fullname' => $cg['user_fullname'],
                                                'email' => $cg['user_email'],
                                                'phone' => $cg['user_phone'] ?? '',
                                                'user_status' => $cg['user_status'],
                                                'experience' => $cg['profile_experience'] ?? '',
                                                'service_type' => $cg['profile_service_type'] ?? '',
                                                'availability' => $cg['profile_availability'] ?? '',
                                                'hourly_rate' => $cg['profile_hourly_rate'] ?? '',
                                                'bg_status' => $cg['profile_background_check_status'] ?? 'pending',
                                                'bio' => $cg['profile_bio'] ?? ''
                                            ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                        <i class="fas fa-pen-to-square text-primary"></i>
                                    </button>

                                    <form method="post" action="" class="d-inline" onsubmit="return confirm('Remove caregiver <?= e($cg['user_fullname']); ?> from directory?');">
                                        <input type="hidden" name="user_id" value="<?= e($cg['user_id']); ?>">
                                        <button type="submit" name="delete_caregiver" class="btn btn-light border btn-action" title="Remove Caregiver">
                                            <i class="fas fa-trash-can text-danger"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted mb-2"><i class="fas fa-users-gear fa-2x opacity-50"></i></div>
                                <div class="fw-semibold">No caregivers onboarded yet</div>
                                <div class="text-muted small mb-3">You can add a caregiver directly or approve &amp; onboard applicants from the Applications tab.</div>
                                <a href="<?= PROOT; ?>admin/applications" class="btn btn-sm btn-outline-secondary">Review Applications</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <!-- Care Assignments Table -->
    <div class="card-modern overflow-hidden">
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>Client & Service Request</th>
                        <th>Assigned Caregiver</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th class="text-end">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($assignments)): ?>
                        <?php foreach ($assignments as $as): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        <?= e($as['client_name'] ?: 'Client Request'); ?>
                                        <?php if ($as['req_num_id']): ?>
                                            <span class="text-muted small">(#<?= (int)$as['req_num_id']; ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small"><?= e($as['request_service_type'] ?: 'Home Care'); ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e($as['caregiver_name'] ?: 'Unknown'); ?></div>
                                    <div class="text-muted small"><?= e($as['caregiver_phone'] ?: ''); ?></div>
                                </td>
                                <td class="small text-nowrap"><?= date('M d, Y', strtotime($as['assignment_start_date'])); ?></td>
                                <td class="small text-nowrap"><?= !empty($as['assignment_end_date']) ? date('M d, Y', strtotime($as['assignment_end_date'])) : '<span class="text-muted">Ongoing</span>'; ?></td>
                                <td class="small text-muted" style="max-width: 240px;"><?= e($as['assignment_notes'] ?: '—'); ?></td>
                                <td>
                                    <?php
                                        $ast = $as['assignment_status'];
                                        $astClass = $ast === 'active' ? 'badge-soft-success' : ($ast === 'completed' ? 'badge-soft-info' : 'badge-soft-danger');
                                    ?>
                                    <span class="badge-soft <?= $astClass; ?>"><?= ucfirst(e($ast)); ?></span>
                                </td>
                                <td class="text-end">
                                    <form method="post" action="" class="d-inline-flex align-items-center gap-1">
                                        <input type="hidden" name="assignment_id" value="<?= e($as['assignment_id']); ?>">
                                        <select name="assignment_status" class="form-select form-select-sm" style="width: 120px;">
                                            <option value="active" <?= $ast === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="completed" <?= $ast === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?= $ast === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_assignment_status" class="btn btn-sm btn-teal">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted mb-2"><i class="fas fa-calendar-check fa-2x opacity-50"></i></div>
                                <div class="fw-semibold">No active care assignments yet</div>
                                <div class="text-muted small">Click "New Assignment" above or assign a caregiver directly from Service Requests.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Add Caregiver Modal -->
<div class="modal fade" id="addCaregiverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold mb-0">Add Caregiver to Directory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Experience</label>
                            <select name="experience" class="form-select">
                                <option value="Less than 1 year">Less than 1 year</option>
                                <option value="1-2 years">1-2 years</option>
                                <option value="3-5 years">3-5 years</option>
                                <option value="5+ years">5+ years</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primary Service Specialty</label>
                            <input type="text" name="service_type" class="form-control" placeholder="e.g. Extended Personal Care, Companionship">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Availability</label>
                            <select name="availability" class="form-select">
                                <option value="Full-time">Full-time</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Flexible">Flexible</option>
                                <option value="Weekends only">Weekends only</option>
                                <option value="Evenings only">Evenings only</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Hourly Rate ($/hr)</label>
                            <input type="number" step="0.01" min="0" name="hourly_rate" class="form-control" placeholder="25.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Background Check Status</label>
                            <select name="bg_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="passed">Passed</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Caregiver Bio / Certifications</label>
                            <textarea name="bio" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_caregiver" class="btn btn-teal px-4">Add Caregiver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Caregiver Modal -->
<div class="modal fade" id="editCaregiverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold mb-0">Edit Caregiver Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body p-4">
                    <input type="hidden" name="user_id" id="ecg_user_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="fullname" id="ecg_fullname" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" id="ecg_phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Account Status</label>
                            <select name="user_status" id="ecg_user_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Background Check Status</label>
                            <select name="bg_status" id="ecg_bg_status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="passed">Passed</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primary Service Specialty</label>
                            <input type="text" name="service_type" id="ecg_service_type" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Experience</label>
                            <input type="text" name="experience" id="ecg_experience" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Availability</label>
                            <input type="text" name="availability" id="ecg_availability" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Hourly Rate ($/hr)</label>
                            <input type="number" step="0.01" min="0" name="hourly_rate" id="ecg_hourly_rate" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Bio / Notes</label>
                            <textarea name="bio" id="ecg_bio" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_caregiver" class="btn btn-teal px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Assignment Modal -->
<div class="modal fade" id="newAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold mb-0">Create Care Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Service Request <span class="text-danger">*</span></label>
                        <select name="assignment_request_id" class="form-select" required>
                            <option value="">-- Select a Client Request --</option>
                            <?php foreach ($openRequests as $or): ?>
                                <option value="<?= e($or['request_id']); ?>">
                                    #<?= (int)$or['id']; ?> — <?= e($or['request_name']); ?> (<?= e($or['request_service_type']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Caregiver <span class="text-danger">*</span></label>
                        <select name="assignment_caregiver_id" class="form-select" required>
                            <option value="">-- Select Caregiver --</option>
                            <?php foreach ($caregivers as $cg): ?>
                                <option value="<?= e($cg['user_id']); ?>">
                                    <?= e($cg['user_fullname']); ?> (<?= e($cg['profile_service_type'] ?: 'General Care'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="assignment_start_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">End Date (Optional)</label>
                            <input type="date" name="assignment_end_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Assignment Notes</label>
                        <textarea name="assignment_notes" class="form-control" rows="2" placeholder="Shift instructions or care plan summary..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_assignment" class="btn btn-teal px-4">Assign Caregiver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editCgModal = new bootstrap.Modal(document.getElementById('editCaregiverModal'));
    document.querySelectorAll('.edit-cg-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = JSON.parse(this.getAttribute('data-cg'));
            document.getElementById('ecg_user_id').value = d.user_id;
            document.getElementById('ecg_fullname').value = d.fullname;
            document.getElementById('ecg_phone').value = d.phone;
            document.getElementById('ecg_user_status').value = d.user_status;
            document.getElementById('ecg_bg_status').value = d.bg_status;
            document.getElementById('ecg_service_type').value = d.service_type;
            document.getElementById('ecg_experience').value = d.experience;
            document.getElementById('ecg_availability').value = d.availability;
            document.getElementById('ecg_hourly_rate').value = d.hourly_rate;
            document.getElementById('ecg_bio').value = d.bio;
            editCgModal.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

