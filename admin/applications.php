<?php 
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    // Handle POST actions before HTML output (PRG pattern)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Update status, notes, and optionally onboard into care_users + care_caregiver_profiles
        if (isset($_POST['update_application']) && !empty($_POST['application_id'])) {
            $id = (int)$_POST['application_id'];
            $newStatus = $_POST['status'] ?? 'pending';
            $notes = trim($_POST['application_notes'] ?? '');
            $onboard = !empty($_POST['onboard_caregiver']);
            $hourlyRate = !empty($_POST['hourly_rate']) ? (float)$_POST['hourly_rate'] : null;

            $validStatuses = ['pending', 'reviewed', 'approved', 'rejected'];
            if (!in_array($newStatus, $validStatuses, true)) {
                $newStatus = 'pending';
            }

            try {
                $stmt = $dbConnection->prepare("
                    UPDATE care_applications 
                    SET application_status = ?, application_notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$newStatus, $notes ?: null, $id]);

                $msgExtra = '';
                if ($onboard && $newStatus === 'approved') {
                    $appStmt = $dbConnection->prepare("SELECT * FROM care_applications WHERE id = ? LIMIT 1");
                    $appStmt->execute([$id]);
                    $appRow = $appStmt->fetch();

                    if ($appRow) {
                        $email = trim($appRow['application_email']);
                        $name  = html_entity_decode((string)$appRow['application_name'], ENT_QUOTES, 'UTF-8');
                        $phone = $appRow['application_phone'];
                        $addr  = html_entity_decode((string)($appRow['application_address'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $exp   = html_entity_decode((string)$appRow['application_experience'], ENT_QUOTES, 'UTF-8');
                        $svc   = html_entity_decode((string)$appRow['application_service_type'], ENT_QUOTES, 'UTF-8');
                        $avail = html_entity_decode((string)$appRow['application_availability'], ENT_QUOTES, 'UTF-8');
                        $bio   = html_entity_decode((string)($appRow['application_message'] ?? ''), ENT_QUOTES, 'UTF-8');

                        // Check if user already exists by email
                        $uCheck = $dbConnection->prepare("SELECT user_id FROM care_users WHERE user_email = ? LIMIT 1");
                        $uCheck->execute([$email]);
                        $existingUserId = $uCheck->fetchColumn();

                        if (!$existingUserId) {
                            $newUserId = guidv4();
                            $defaultPwd = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
                            $insUser = $dbConnection->prepare("
                                INSERT INTO care_users (user_id, user_fullname, user_email, user_phone, user_password, user_type, user_status, user_address)
                                VALUES (?, ?, ?, ?, ?, 'caregiver', 'active', ?)
                            ");
                            $insUser->execute([$newUserId, $name, $email, $phone, $defaultPwd, $addr ?: null]);
                            $targetUserId = $newUserId;
                        } else {
                            $targetUserId = $existingUserId;
                        }

                        // Check if caregiver profile exists
                        $pCheck = $dbConnection->prepare("SELECT profile_id FROM care_caregiver_profiles WHERE profile_user_id = ? LIMIT 1");
                        $pCheck->execute([$targetUserId]);
                        if (!$pCheck->fetchColumn()) {
                            $insProf = $dbConnection->prepare("
                                INSERT INTO care_caregiver_profiles (profile_id, profile_user_id, profile_experience, profile_service_type, profile_availability, profile_bio, profile_hourly_rate, profile_background_check_status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                            ");
                            $insProf->execute([guidv4(), $targetUserId, $exp, $svc, $avail, $bio ?: null, $hourlyRate]);
                        }
                        $msgExtra = " Applicant has also been onboarded to the Caregivers Directory!";
                    }
                }

                $_SESSION['flash_success'] = "Application #$id updated to " . ucfirst($newStatus) . "." . $msgExtra;
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error updating application: " . $e->getMessage();
            }
            redirect(PROOT . 'admin/applications' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }

        // 2. Delete application
        if (isset($_POST['delete_application']) && !empty($_POST['application_id'])) {
            $id = (int)$_POST['application_id'];
            try {
                $stmt = $dbConnection->prepare("DELETE FROM care_applications WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash_success'] = "Application #$id deleted.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error deleting application: " . $e->getMessage();
            }
            redirect(PROOT . 'admin/applications');
        }
    }

    // Filters & Search
    $validStatuses = ['pending', 'reviewed', 'approved', 'rejected'];
    $statusFilter = isset($_GET['status']) && in_array($_GET['status'], $validStatuses, true) ? $_GET['status'] : '';
    $searchQuery  = trim($_GET['search'] ?? '');

    $whereClauses = [];
    $params = [];

    if ($statusFilter !== '') {
        $whereClauses[] = "application_status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($searchQuery !== '') {
        $whereClauses[] = "(application_name LIKE :search OR application_email LIKE :search OR application_phone LIKE :search OR application_service_type LIKE :search OR application_experience LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $exportStmt = $dbConnection->prepare("SELECT * FROM care_applications $whereSql ORDER BY id DESC");
        $exportStmt->execute($params);
        $rows = $exportStmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="caregiver_applications_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Applicant Name', 'Email', 'Phone', 'Experience', 'Service Type', 'Availability', 'Address', 'Status', 'Message / Bio', 'Admin Notes', 'Applied Date']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                html_entity_decode((string)$r['application_name'], ENT_QUOTES, 'UTF-8'),
                $r['application_email'],
                $r['application_phone'],
                html_entity_decode((string)$r['application_experience'], ENT_QUOTES, 'UTF-8'),
                html_entity_decode((string)$r['application_service_type'], ENT_QUOTES, 'UTF-8'),
                html_entity_decode((string)$r['application_availability'], ENT_QUOTES, 'UTF-8'),
                html_entity_decode((string)($r['application_address'] ?? ''), ENT_QUOTES, 'UTF-8'),
                $r['application_status'],
                html_entity_decode((string)($r['application_message'] ?? ''), ENT_QUOTES, 'UTF-8'),
                $r['application_notes'] ?? '',
                $r['created_at']
            ]);
        }
        fclose($out);
        exit;
    }

    // Status tab counts
    $statusCounts = ['all' => 0, 'pending' => 0, 'reviewed' => 0, 'approved' => 0, 'rejected' => 0];
    $countStmt = $dbConnection->query("SELECT application_status, COUNT(*) as cnt FROM care_applications GROUP BY application_status");
    foreach ($countStmt->fetchAll() as $cRow) {
        if (isset($statusCounts[$cRow['application_status']])) {
            $statusCounts[$cRow['application_status']] = (int)$cRow['cnt'];
            $statusCounts['all'] += (int)$cRow['cnt'];
        }
    }

    // Pagination
    $limit = 10;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalStmt = $dbConnection->prepare("SELECT COUNT(*) FROM care_applications $whereSql");
    $totalStmt->execute($params);
    $totalRecords = (int)$totalStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $limit));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;

    // Fetch applications
    $query = "SELECT * FROM care_applications $whereSql ORDER BY id DESC LIMIT $offset, $limit";
    $stmt = $dbConnection->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    $pageTitle = "Caregiver Applications";
    include 'includes/header.php'; 
    include 'includes/sidebar.php';

    function getAppStatusBadge($status) {
        return match($status) {
            'pending'  => 'badge-soft-warning',
            'reviewed' => 'badge-soft-info',
            'approved' => 'badge-soft-success',
            'rejected' => 'badge-soft-danger',
            default    => 'badge-soft-secondary'
        };
    }
?>

<!-- Filter Pills & Toolbar -->
<div class="card-modern p-3 p-md-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <!-- Status Filter Pills -->
        <div class="filter-pills">
            <a href="<?= PROOT; ?>admin/applications<?= $searchQuery ? '?search=' . urlencode($searchQuery) : ''; ?>" 
               class="filter-pill <?= $statusFilter === '' ? 'active' : ''; ?>">
                All <span class="pill-count"><?= $statusCounts['all']; ?></span>
            </a>
            <?php foreach ($validStatuses as $st): ?>
                <a href="<?= PROOT; ?>admin/applications?status=<?= $st; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="filter-pill <?= $statusFilter === $st ? 'active' : ''; ?>">
                    <?= ucfirst($st); ?> <span class="pill-count"><?= $statusCounts[$st]; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search & Export -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="get" action="" class="d-flex gap-2 flex-grow-1">
                <?php if ($statusFilter !== ''): ?>
                    <input type="hidden" name="status" value="<?= e($statusFilter); ?>">
                <?php endif; ?>
                <div class="input-group input-group-sm" style="min-width: 230px;">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search applicant, email, specialty..." value="<?= e($searchQuery); ?>">
                    <?php if ($searchQuery !== ''): ?>
                        <a href="<?= PROOT; ?>admin/applications<?= $statusFilter ? '?status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-outline-secondary" title="Clear search">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-sm btn-teal px-3">Filter</button>
            </form>

            <a href="?export=csv<?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
               class="btn btn-sm btn-light border fw-semibold">
                <i class="fas fa-file-csv me-1 text-success"></i>Export CSV
            </a>
        </div>
    </div>
</div>

<!-- Applications Table Card -->
<div class="card-modern overflow-hidden">
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Applicant</th>
                    <th>Experience</th>
                    <th>Preferred Service</th>
                    <th>Availability</th>
                    <th>Status</th>
                    <th>Date Applied</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($applications)): ?>
                    <?php foreach ($applications as $row): ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= (int)$row['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle" style="background:#e0e7ff; color:#4338ca;">
                                        <?= strtoupper(substr(e($row['application_name']), 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= e($row['application_name']); ?></div>
                                        <div class="text-muted small">
                                            <a href="mailto:<?= e($row['application_email']); ?>" class="text-decoration-none text-muted"><?= e($row['application_email']); ?></a>
                                            &bull; <?= e($row['application_phone']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-medium"><?= e($row['application_experience']); ?></span>
                            </td>
                            <td>
                                <div class="text-truncate fw-medium" style="max-width: 220px;" title="<?= e($row['application_service_type']); ?>">
                                    <?= e($row['application_service_type']); ?>
                                </div>
                            </td>
                            <td><?= e($row['application_availability']); ?></td>
                            <td>
                                <span class="badge-soft <?= getAppStatusBadge($row['application_status']); ?>">
                                    <?= ucfirst(e($row['application_status'])); ?>
                                </span>
                            </td>
                            <td class="small text-muted text-nowrap">
                                <?= date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-light border btn-action me-1 view-app-btn"
                                        title="View Application Details"
                                        data-app='<?= htmlspecialchars(json_encode([
                                            'id' => $row['id'],
                                            'name' => html_entity_decode((string)$row['application_name'], ENT_QUOTES, 'UTF-8'),
                                            'email' => $row['application_email'],
                                            'phone' => $row['application_phone'],
                                            'address' => html_entity_decode((string)($row['application_address'] ?: 'Not provided'), ENT_QUOTES, 'UTF-8'),
                                            'experience' => html_entity_decode((string)$row['application_experience'], ENT_QUOTES, 'UTF-8'),
                                            'service_type' => html_entity_decode((string)$row['application_service_type'], ENT_QUOTES, 'UTF-8'),
                                            'availability' => html_entity_decode((string)$row['application_availability'], ENT_QUOTES, 'UTF-8'),
                                            'status' => ucfirst($row['application_status']),
                                            'message' => html_entity_decode((string)($row['application_message'] ?: 'No cover message provided.'), ENT_QUOTES, 'UTF-8'),
                                            'notes' => html_entity_decode((string)($row['application_notes'] ?? ''), ENT_QUOTES, 'UTF-8'),
                                            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
                                        ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <i class="fas fa-eye text-primary"></i>
                                </button>

                                <button type="button" class="btn btn-light border btn-action me-1 edit-app-btn"
                                        title="Review & Update Status"
                                        data-id="<?= (int)$row['id']; ?>"
                                        data-name="<?= e($row['application_name']); ?>"
                                        data-status="<?= e($row['application_status']); ?>"
                                        data-notes="<?= e($row['application_notes'] ?? ''); ?>">
                                    <i class="fas fa-user-check text-success"></i>
                                </button>

                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Delete application #<?= (int)$row['id']; ?> permanently?');">
                                    <input type="hidden" name="application_id" value="<?= (int)$row['id']; ?>">
                                    <button type="submit" name="delete_application" class="btn btn-light border btn-action" title="Delete Application">
                                        <i class="fas fa-trash-can text-danger"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="fas fa-user-nurse fa-2x opacity-50"></i></div>
                            <div class="fw-semibold">No caregiver applications found</div>
                            <div class="text-muted small">Try adjusting your status filter or search criteria.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex flex-wrap justify-content-between align-items-center bg-light">
            <span class="small text-muted">
                Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= $totalRecords; ?> total applications)
            </span>
            <nav aria-label="Applications pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?= $page - 1; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>">&laquo; Prev</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?= $i; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?= $page + 1; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>">Next &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Shared View Application Modal -->
<div class="modal fade" id="viewAppModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Caregiver Application <span id="va_id" class="text-muted"></span></h5>
                    <span class="text-muted small">Applied on <span id="va_created_at"></span></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <h6 class="text-uppercase small fw-bold text-muted mb-3">Applicant Contact</h6>
                            <p class="mb-2"><strong>Full Name:</strong> <span id="va_name"></span></p>
                            <p class="mb-2"><strong>Email:</strong> <a href="#" id="va_email_link"><span id="va_email"></span></a></p>
                            <p class="mb-2"><strong>Phone:</strong> <a href="#" id="va_phone_link"><span id="va_phone"></span></a></p>
                            <p class="mb-0"><strong>Address:</strong> <span id="va_address"></span></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <h6 class="text-uppercase small fw-bold text-muted mb-3">Qualifications & Preferences</h6>
                            <p class="mb-2"><strong>Experience:</strong> <span id="va_experience"></span></p>
                            <p class="mb-2"><strong>Preferred Service:</strong> <span id="va_service_type"></span></p>
                            <p class="mb-2"><strong>Availability:</strong> <span id="va_availability"></span></p>
                            <p class="mb-0"><strong>Status:</strong> <span id="va_status" class="fw-bold"></span></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="text-uppercase small fw-bold text-muted mb-2">Applicant Statement & Experience</h6>
                    <div class="p-3 rounded-3 border bg-white" id="va_message" style="white-space: pre-wrap;"></div>
                </div>

                <div id="va_notes_wrap" style="display:none;">
                    <h6 class="text-uppercase small fw-bold text-muted mb-2">Internal Reviewer Notes</h6>
                    <div class="p-3 rounded-3 border bg-warning-subtle text-dark" id="va_notes" style="white-space: pre-wrap;"></div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Shared Edit/Review Application Modal -->
<div class="modal fade" id="editAppModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Review Application <span id="ea_id_label" class="text-muted"></span></h5>
                    <span class="text-muted small" id="ea_name_label"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body p-4">
                    <input type="hidden" name="application_id" id="ea_application_id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Application Status</label>
                        <select name="status" id="ea_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="p-3 rounded-3 border bg-light mb-3" id="onboardBox" style="display: none;">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="onboard_caregiver" value="1" id="onboardCheck" checked>
                            <label class="form-check-label fw-semibold small" for="onboardCheck">
                                Automatically onboard into Caregivers Directory
                            </label>
                        </div>
                        <div class="small text-muted mb-2">Creates an active caregiver profile from this application if not already registered.</div>
                        <div>
                            <label class="form-label small fw-semibold mb-1">Starting Hourly Rate ($/hr, optional)</label>
                            <input type="number" step="0.01" min="0" name="hourly_rate" class="form-control form-control-sm" placeholder="e.g. 22.50">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Internal Reviewer Notes</label>
                        <textarea name="application_notes" id="ea_notes" class="form-control" rows="3" placeholder="Add interview notes, reference checks, or screening comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_application" class="btn btn-teal px-4">Save Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewModal = new bootstrap.Modal(document.getElementById('viewAppModal'));
    document.querySelectorAll('.view-app-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = JSON.parse(this.getAttribute('data-app'));
            document.getElementById('va_id').textContent = '#' + d.id;
            document.getElementById('va_created_at').textContent = d.created_at;
            document.getElementById('va_name').textContent = d.name;
            document.getElementById('va_email').textContent = d.email;
            document.getElementById('va_email_link').href = 'mailto:' + d.email;
            document.getElementById('va_phone').textContent = d.phone;
            document.getElementById('va_phone_link').href = 'tel:' + d.phone;
            document.getElementById('va_address').textContent = d.address;
            document.getElementById('va_experience').textContent = d.experience;
            document.getElementById('va_service_type').textContent = d.service_type;
            document.getElementById('va_availability').textContent = d.availability;
            document.getElementById('va_status').textContent = d.status;
            document.getElementById('va_message').textContent = d.message;

            const notesWrap = document.getElementById('va_notes_wrap');
            if (d.notes && d.notes.trim() !== '') {
                document.getElementById('va_notes').textContent = d.notes;
                notesWrap.style.display = 'block';
            } else {
                notesWrap.style.display = 'none';
            }
            viewModal.show();
        });
    });

    const editModal = new bootstrap.Modal(document.getElementById('editAppModal'));
    const statusSelect = document.getElementById('ea_status');
    const onboardBox = document.getElementById('onboardBox');

    function toggleOnboardBox() {
        onboardBox.style.display = statusSelect.value === 'approved' ? 'block' : 'none';
    }
    statusSelect.addEventListener('change', toggleOnboardBox);

    document.querySelectorAll('.edit-app-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('ea_application_id').value = this.dataset.id;
            document.getElementById('ea_id_label').textContent = '#' + this.dataset.id;
            document.getElementById('ea_name_label').textContent = 'Applicant: ' + this.dataset.name;
            statusSelect.value = this.dataset.status;
            document.getElementById('ea_notes').value = this.dataset.notes;
            toggleOnboardBox();
            editModal.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
