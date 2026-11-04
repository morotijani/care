<?php 
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    // Handle POST actions before any HTML output (PRG pattern)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Update status, caregiver assignment, and internal notes
        if (isset($_POST['update_request']) && !empty($_POST['request_id'])) {
            $id = (int)$_POST['request_id'];
            $newStatus = $_POST['status'] ?? 'pending';
            $assignedTo = trim($_POST['request_assigned_to'] ?? '');
            $caregiverUserId = trim($_POST['caregiver_user_id'] ?? '');
            $notes = trim($_POST['request_notes'] ?? '');

            $validStatuses = ['pending', 'approved', 'assigned', 'completed', 'cancelled'];
            if (!in_array($newStatus, $validStatuses, true)) {
                $newStatus = 'pending';
            }

            try {
                // If a registered caregiver was chosen from dropdown, resolve their name
                if (!empty($caregiverUserId)) {
                    $cgStmt = $dbConnection->prepare("SELECT user_fullname FROM care_users WHERE user_id = ? LIMIT 1");
                    $cgStmt->execute([$caregiverUserId]);
                    $cgName = $cgStmt->fetchColumn();
                    if ($cgName) {
                        $assignedTo = $cgName;
                    }
                }

                $stmt = $dbConnection->prepare("
                    UPDATE care_service_requests 
                    SET request_status = ?, request_assigned_to = ?, request_notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$newStatus, $assignedTo ?: null, $notes ?: null, $id]);

                // Sync with care_assignments if a registered caregiver is selected
                if (!empty($caregiverUserId) && in_array($newStatus, ['assigned', 'completed', 'cancelled'], true)) {
                    $reqRowStmt = $dbConnection->prepare("SELECT request_id, request_start_date FROM care_service_requests WHERE id = ? LIMIT 1");
                    $reqRowStmt->execute([$id]);
                    $reqRow = $reqRowStmt->fetch();

                    if ($reqRow) {
                        $assignStatus = $newStatus === 'completed' ? 'completed' : ($newStatus === 'cancelled' ? 'cancelled' : 'active');
                        $existingStmt = $dbConnection->prepare("SELECT assignment_id FROM care_assignments WHERE assignment_request_id = ? LIMIT 1");
                        $existingStmt->execute([$reqRow['request_id']]);
                        $existingAssignId = $existingStmt->fetchColumn();

                        if ($existingAssignId) {
                            $upAssign = $dbConnection->prepare("
                                UPDATE care_assignments 
                                SET assignment_caregiver_id = ?, assignment_status = ?, assignment_notes = ?
                                WHERE assignment_id = ?
                            ");
                            $upAssign->execute([$caregiverUserId, $assignStatus, $notes ?: null, $existingAssignId]);
                        } else {
                            $insAssign = $dbConnection->prepare("
                                INSERT INTO care_assignments (assignment_id, assignment_request_id, assignment_caregiver_id, assignment_start_date, assignment_status, assignment_notes)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $insAssign->execute([guidv4(), $reqRow['request_id'], $caregiverUserId, $reqRow['request_start_date'], $assignStatus, $notes ?: null]);
                        }
                    }
                }

                $_SESSION['flash_success'] = "Service request #$id updated successfully.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Failed to update request: " . $e->getMessage();
            }
            redirect(PROOT . 'admin/requests' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }

        // 2. Delete service request
        if (isset($_POST['delete_request']) && !empty($_POST['request_id'])) {
            $id = (int)$_POST['request_id'];
            try {
                $stmt = $dbConnection->prepare("DELETE FROM care_service_requests WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash_success'] = "Service request #$id deleted.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error deleting request: " . $e->getMessage();
            }
            redirect(PROOT . 'admin/requests');
        }
    }

    // Filters & Search
    $validStatuses = ['pending', 'approved', 'assigned', 'completed', 'cancelled'];
    $statusFilter = isset($_GET['status']) && in_array($_GET['status'], $validStatuses, true) ? $_GET['status'] : '';
    $searchQuery  = trim($_GET['search'] ?? '');

    $whereClauses = [];
    $params = [];

    if ($statusFilter !== '') {
        $whereClauses[] = "request_status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($searchQuery !== '') {
        $whereClauses[] = "(request_name LIKE :search OR request_email LIKE :search OR request_phone LIKE :search OR request_service_type LIKE :search OR request_assigned_to LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $exportStmt = $dbConnection->prepare("SELECT * FROM care_service_requests $whereSql ORDER BY id DESC");
        $exportStmt->execute($params);
        $rows = $exportStmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="service_requests_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Client Name', 'Email', 'Phone', 'Service Type', 'Care For', 'Start Date', 'Frequency', 'Hours', 'Status', 'Assigned Caregiver', 'Special Requirements', 'Admin Notes', 'Created At']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                html_entity_decode((string)$r['request_name'], ENT_QUOTES, 'UTF-8'),
                $r['request_email'],
                $r['request_phone'],
                html_entity_decode((string)$r['request_service_type'], ENT_QUOTES, 'UTF-8'),
                $r['request_care_for'],
                $r['request_start_date'],
                $r['request_frequency'],
                $r['request_hours'],
                $r['request_status'],
                $r['request_assigned_to'],
                html_entity_decode((string)$r['request_special_requirements'], ENT_QUOTES, 'UTF-8'),
                $r['request_notes'] ?? '',
                $r['created_at']
            ]);
        }
        fclose($out);
        exit;
    }

    // Status tab counts
    $statusCounts = ['all' => 0, 'pending' => 0, 'approved' => 0, 'assigned' => 0, 'completed' => 0, 'cancelled' => 0];
    $countStmt = $dbConnection->query("SELECT request_status, COUNT(*) as cnt FROM care_service_requests GROUP BY request_status");
    foreach ($countStmt->fetchAll() as $cRow) {
        if (isset($statusCounts[$cRow['request_status']])) {
            $statusCounts[$cRow['request_status']] = (int)$cRow['cnt'];
            $statusCounts['all'] += (int)$cRow['cnt'];
        }
    }

    // Pagination
    $limit = 10;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalStmt = $dbConnection->prepare("SELECT COUNT(*) FROM care_service_requests $whereSql");
    $totalStmt->execute($params);
    $totalRecords = (int)$totalStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $limit));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;

    // Fetch paginated requests
    $query = "SELECT * FROM care_service_requests $whereSql ORDER BY id DESC LIMIT $offset, $limit";
    $stmt = $dbConnection->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    // Fetch active caregivers for assignment dropdown
    $caregivers = get_all_caregivers($dbConnection);

    $pageTitle = "Service Requests";
    include 'includes/header.php';
    include 'includes/sidebar.php';

    function getReqStatusBadge($status) {
        return match($status) {
            'pending'   => 'badge-soft-warning',
            'approved'  => 'badge-soft-info',
            'assigned'  => 'badge-soft-indigo',
            'completed' => 'badge-soft-success',
            'cancelled' => 'badge-soft-danger',
            default     => 'badge-soft-secondary'
        };
    }
?>

<!-- Filter Pills & Toolbar -->
<div class="card-modern p-3 p-md-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <!-- Status Filter Pills -->
        <div class="filter-pills">
            <a href="<?= PROOT; ?>admin/requests<?= $searchQuery ? '?search=' . urlencode($searchQuery) : ''; ?>" 
               class="filter-pill <?= $statusFilter === '' ? 'active' : ''; ?>">
                All <span class="pill-count"><?= $statusCounts['all']; ?></span>
            </a>
            <?php foreach ($validStatuses as $st): ?>
                <a href="<?= PROOT; ?>admin/requests?status=<?= $st; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
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
                    <input type="text" name="search" class="form-control" placeholder="Search client, service, phone..." value="<?= e($searchQuery); ?>">
                    <?php if ($searchQuery !== ''): ?>
                        <a href="<?= PROOT; ?>admin/requests<?= $statusFilter ? '?status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-outline-secondary" title="Clear search">
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

<!-- Requests Table Card -->
<div class="card-modern overflow-hidden">
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Info</th>
                    <th>Service Requested</th>
                    <th>Schedule & Care</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $row): ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= (int)$row['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle">
                                        <?= strtoupper(substr(e($row['request_name']), 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= e($row['request_name']); ?></div>
                                        <div class="text-muted small">
                                            <a href="mailto:<?= e($row['request_email']); ?>" class="text-decoration-none text-muted"><?= e($row['request_email']); ?></a>
                                            &bull; <?= e($row['request_phone']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-truncate" style="max-width: 220px;" title="<?= e($row['request_service_type']); ?>">
                                    <?= e($row['request_service_type']); ?>
                                </div>
                                <div class="text-muted small">Recipient: <strong><?= e($row['request_care_for']); ?></strong></div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><i class="far fa-calendar-alt text-muted me-1"></i><?= date('M d, Y', strtotime($row['request_start_date'])); ?></div>
                                <div class="text-muted small"><?= e($row['request_frequency']); ?><?= !empty($row['request_hours']) ? ' (' . e($row['request_hours']) . ' hrs)' : ''; ?></div>
                            </td>
                            <td>
                                <?php if (!empty($row['request_assigned_to'])): ?>
                                    <span class="badge bg-light text-dark border fw-medium">
                                        <i class="fas fa-user-check text-teal me-1" style="color:#0d9488;"></i><?= e($row['request_assigned_to']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-soft <?= getReqStatusBadge($row['request_status']); ?>">
                                    <?= ucfirst(e($row['request_status'])); ?>
                                </span>
                            </td>
                            <td class="small text-muted text-nowrap">
                                <?= date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-light border btn-action me-1 view-req-btn"
                                        title="View Full Details"
                                        data-req='<?= htmlspecialchars(json_encode([
                                            'id' => $row['id'],
                                            'name' => html_entity_decode((string)$row['request_name'], ENT_QUOTES, 'UTF-8'),
                                            'email' => $row['request_email'],
                                            'phone' => $row['request_phone'],
                                            'service_type' => html_entity_decode((string)$row['request_service_type'], ENT_QUOTES, 'UTF-8'),
                                            'care_for' => html_entity_decode((string)$row['request_care_for'], ENT_QUOTES, 'UTF-8'),
                                            'start_date' => date('M d, Y', strtotime($row['request_start_date'])),
                                            'frequency' => html_entity_decode((string)$row['request_frequency'], ENT_QUOTES, 'UTF-8'),
                                            'hours' => html_entity_decode((string)($row['request_hours'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'),
                                            'status' => ucfirst($row['request_status']),
                                            'assigned_to' => html_entity_decode((string)($row['request_assigned_to'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'),
                                            'requirements' => html_entity_decode((string)($row['request_special_requirements'] ?? 'None specified'), ENT_QUOTES, 'UTF-8'),
                                            'notes' => html_entity_decode((string)($row['request_notes'] ?? ''), ENT_QUOTES, 'UTF-8'),
                                            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
                                        ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <i class="fas fa-eye text-primary"></i>
                                </button>

                                <button type="button" class="btn btn-light border btn-action me-1 edit-req-btn"
                                        title="Update Status & Assign Caregiver"
                                        data-id="<?= (int)$row['id']; ?>"
                                        data-name="<?= e($row['request_name']); ?>"
                                        data-status="<?= e($row['request_status']); ?>"
                                        data-assigned="<?= e($row['request_assigned_to'] ?? ''); ?>"
                                        data-notes="<?= e($row['request_notes'] ?? ''); ?>">
                                    <i class="fas fa-pen-to-square text-success"></i>
                                </button>

                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete request #<?= (int)$row['id']; ?>?');">
                                    <input type="hidden" name="request_id" value="<?= (int)$row['id']; ?>">
                                    <button type="submit" name="delete_request" class="btn btn-light border btn-action" title="Delete Request">
                                        <i class="fas fa-trash-can text-danger"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="fas fa-inbox fa-2x opacity-50"></i></div>
                            <div class="fw-semibold">No service requests found</div>
                            <div class="text-muted small">Try clearing your filters or search query.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex flex-wrap justify-content-between align-items-center bg-light">
            <span class="small text-muted">
                Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= $totalRecords; ?> total records)
            </span>
            <nav aria-label="Requests pagination">
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

<!-- Single Shared Details Modal (Outside Table) -->
<div class="modal fade" id="viewRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Service Request <span id="v_id" class="text-muted"></span></h5>
                    <span class="text-muted small">Submitted on <span id="v_created_at"></span></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <h6 class="text-uppercase small fw-bold text-muted mb-3">Client Details</h6>
                            <p class="mb-2"><strong>Name:</strong> <span id="v_name"></span></p>
                            <p class="mb-2"><strong>Email:</strong> <a href="#" id="v_email_link"><span id="v_email"></span></a></p>
                            <p class="mb-2"><strong>Phone:</strong> <a href="#" id="v_phone_link"><span id="v_phone"></span></a></p>
                            <p class="mb-0"><strong>Care Recipient:</strong> <span id="v_care_for"></span></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light h-100">
                            <h6 class="text-uppercase small fw-bold text-muted mb-3">Care Plan & Status</h6>
                            <p class="mb-2"><strong>Service Type:</strong> <span id="v_service_type"></span></p>
                            <p class="mb-2"><strong>Preferred Start:</strong> <span id="v_start_date"></span></p>
                            <p class="mb-2"><strong>Schedule:</strong> <span id="v_frequency"></span> (<span id="v_hours"></span> hrs)</p>
                            <p class="mb-2"><strong>Status:</strong> <span id="v_status" class="fw-bold"></span></p>
                            <p class="mb-0"><strong>Assigned Caregiver:</strong> <span id="v_assigned_to"></span></p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="text-uppercase small fw-bold text-muted mb-2">Special Care Requirements</h6>
                    <div class="p-3 rounded-3 border bg-white" id="v_requirements" style="white-space: pre-wrap;"></div>
                </div>

                <div id="v_notes_wrap" style="display:none;">
                    <h6 class="text-uppercase small fw-bold text-muted mb-2">Internal Admin Notes</h6>
                    <div class="p-3 rounded-3 border bg-warning-subtle text-dark" id="v_notes" style="white-space: pre-wrap;"></div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Single Shared Status & Assignment Modal (Outside Table) -->
<div class="modal fade" id="editRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Manage Request <span id="e_id_label" class="text-muted"></span></h5>
                    <span class="text-muted small" id="e_client_label"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body p-4">
                    <input type="hidden" name="request_id" id="e_request_id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Request Status</label>
                        <select name="status" id="e_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="assigned">Assigned</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <?php if (!empty($caregivers)): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Select Registered Caregiver (Optional)</label>
                            <select name="caregiver_user_id" id="e_caregiver_select" class="form-select">
                                <option value="">-- Choose from active staff or type below --</option>
                                <?php foreach ($caregivers as $cg): ?>
                                    <option value="<?= e($cg['user_id']); ?>" data-fullname="<?= e($cg['user_fullname']); ?>">
                                        <?= e($cg['user_fullname']); ?> (<?= e($cg['profile_service_type'] ?: 'General Care'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Assigned Caregiver Name</label>
                        <input type="text" name="request_assigned_to" id="e_assigned_to" class="form-control" placeholder="Enter caregiver full name">
                        <div class="form-text">Saved on the service request and visible in details.</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold small">Internal Admin Notes</label>
                        <textarea name="request_notes" id="e_notes" class="form-control" rows="3" placeholder="Add internal notes, follow-up call outcomes, or scheduling details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_request" class="btn btn-teal px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View Modal logic
    const viewModal = new bootstrap.Modal(document.getElementById('viewRequestModal'));
    document.querySelectorAll('.view-req-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = JSON.parse(this.getAttribute('data-req'));
            document.getElementById('v_id').textContent = '#' + d.id;
            document.getElementById('v_created_at').textContent = d.created_at;
            document.getElementById('v_name').textContent = d.name;
            document.getElementById('v_email').textContent = d.email;
            document.getElementById('v_email_link').href = 'mailto:' + d.email;
            document.getElementById('v_phone').textContent = d.phone;
            document.getElementById('v_phone_link').href = 'tel:' + d.phone;
            document.getElementById('v_care_for').textContent = d.care_for;
            document.getElementById('v_service_type').textContent = d.service_type;
            document.getElementById('v_start_date').textContent = d.start_date;
            document.getElementById('v_frequency').textContent = d.frequency;
            document.getElementById('v_hours').textContent = d.hours || 'N/A';
            document.getElementById('v_status').textContent = d.status;
            document.getElementById('v_assigned_to').textContent = d.assigned_to || 'Unassigned';
            document.getElementById('v_requirements').textContent = d.requirements || 'None specified';

            const notesWrap = document.getElementById('v_notes_wrap');
            if (d.notes && d.notes.trim() !== '') {
                document.getElementById('v_notes').textContent = d.notes;
                notesWrap.style.display = 'block';
            } else {
                notesWrap.style.display = 'none';
            }
            viewModal.show();
        });
    });

    // Edit Modal logic
    const editModal = new bootstrap.Modal(document.getElementById('editRequestModal'));
    const cgSelect = document.getElementById('e_caregiver_select');
    const assignedInput = document.getElementById('e_assigned_to');
    const statusSelect = document.getElementById('e_status');

    if (cgSelect) {
        cgSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const fn = opt ? opt.getAttribute('data-fullname') : '';
            if (fn) {
                assignedInput.value = fn;
                statusSelect.value = 'assigned';
            }
        });
    }

    document.querySelectorAll('.edit-req-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('e_request_id').value = this.dataset.id;
            document.getElementById('e_id_label').textContent = '#' + this.dataset.id;
            document.getElementById('e_client_label').textContent = 'Client: ' + this.dataset.name;
            statusSelect.value = this.dataset.status;
            assignedInput.value = this.dataset.assigned;
            document.getElementById('e_notes').value = this.dataset.notes;
            if (cgSelect) cgSelect.value = '';
            editModal.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
