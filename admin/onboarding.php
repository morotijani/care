<?php
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    // Handle POST actions (PRG pattern)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Update Onboarding Status, Admin Notes & Optional Sync to Caregivers Directory
        if (isset($_POST['update_onboarding']) && !empty($_POST['onboarding_id'])) {
            $id = (int)$_POST['onboarding_id'];
            $newStatus = $_POST['onboarding_status'] ?? 'pending';
            $adminNotes = trim($_POST['admin_notes'] ?? '');
            $syncCaregiver = !empty($_POST['sync_caregiver']);

            $validStatuses = ['pending', 'in_review', 'approved', 'rejected'];
            if (!in_array($newStatus, $validStatuses, true)) {
                $newStatus = 'pending';
            }

            try {
                $stmt = $dbConnection->prepare("
                    UPDATE care_staff_onboarding
                    SET onboarding_status = ?, admin_notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([$newStatus, $adminNotes ?: null, $id]);

                $extraMsg = '';
                if ($syncCaregiver && $newStatus === 'approved') {
                    $rowStmt = $dbConnection->prepare("SELECT * FROM care_staff_onboarding WHERE id = ? LIMIT 1");
                    $rowStmt->execute([$id]);
                    $ob = $rowStmt->fetch();

                    if ($ob) {
                        $email = trim($ob['staff_email']);
                        $name  = $ob['staff_name'];
                        $phone = $ob['staff_phone'];
                        $addr  = $ob['staff_address'];
                        $svc   = trim(($ob['credential_type'] ?: '') . ($ob['chore_labor'] ? ' | ' . $ob['chore_labor'] : ''), ' |');
                        $bgPassed = (stripos((string)$ob['background_check_status'], 'Passed') !== false) ? 'passed' : 'pending';

                        $uChk = $dbConnection->prepare("SELECT user_id FROM care_users WHERE user_email = ? LIMIT 1");
                        $uChk->execute([$email]);
                        $existingUid = $uChk->fetchColumn();

                        if (!$existingUid) {
                            $newUid = guidv4();
                            $pwd = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
                            $insU = $dbConnection->prepare("
                                INSERT INTO care_users (user_id, user_fullname, user_email, user_phone, user_password, user_type, user_status, user_address)
                                VALUES (?, ?, ?, ?, ?, 'caregiver', 'active', ?)
                            ");
                            $insU->execute([$newUid, $name, $email, $phone, $pwd, $addr ?: null]);
                            $targetUid = $newUid;
                        } else {
                            $targetUid = $existingUid;
                            $upU = $dbConnection->prepare("UPDATE care_users SET user_status = 'active', user_phone = ?, user_address = ? WHERE user_id = ?");
                            $upU->execute([$phone, $addr ?: null, $targetUid]);
                        }

                        $pChk = $dbConnection->prepare("SELECT profile_id FROM care_caregiver_profiles WHERE profile_user_id = ? LIMIT 1");
                        $pChk->execute([$targetUid]);
                        if ($pChk->fetchColumn()) {
                            $upP = $dbConnection->prepare("
                                UPDATE care_caregiver_profiles
                                SET profile_service_type = ?, profile_background_check_status = ?
                                WHERE profile_user_id = ?
                            ");
                            $upP->execute([$svc ?: 'Home Care Staff', $bgPassed, $targetUid]);
                        } else {
                            $insP = $dbConnection->prepare("
                                INSERT INTO care_caregiver_profiles (profile_id, profile_user_id, profile_experience, profile_service_type, profile_availability, profile_background_check_status)
                                VALUES (?, ?, 'Onboarded Staff', ?, 'Full-time', ?)
                            ");
                            $insP->execute([guidv4(), $targetUid, $svc ?: 'Home Care Staff', $bgPassed]);
                        }
                        $extraMsg = " Staff member has also been synced to the active Caregivers Directory!";
                    }
                }

                $_SESSION['flash_success'] = "Onboarding packet #$id updated." . $extraMsg;
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error updating onboarding record: " . $e->getMessage();
            }
            redirect(PROOT . 'admin/onboarding' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }

        // 2. Delete Onboarding Submission
        if (isset($_POST['delete_onboarding']) && !empty($_POST['onboarding_id'])) {
            $id = (int)$_POST['onboarding_id'];
            try {
                // Remove uploaded files if present
                $fStmt = $dbConnection->prepare("SELECT * FROM care_staff_onboarding WHERE id = ? LIMIT 1");
                $fStmt->execute([$id]);
                $fRow = $fStmt->fetch();
                if ($fRow) {
                    $fileCols = ['gov_id_file','ssn_file','background_check_file','fwa_certificate_file','credential_file','direct_deposit_file','w4_file','i9_file','drivers_license_file','drivers_insurance_file'];
                    foreach ($fileCols as $col) {
                        if (!empty($fRow[$col])) {
                            $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fRow[$col]);
                            if (is_file($fullPath)) {
                                @unlink($fullPath);
                            }
                        }
                    }
                }

                $del = $dbConnection->prepare("DELETE FROM care_staff_onboarding WHERE id = ?");
                $del->execute([$id]);
                $_SESSION['flash_success'] = "Onboarding packet #$id deleted.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Error deleting record: " . $e->getMessage();
            }
            redirect(PROOT . 'admin/onboarding');
        }
    }

    // Filters & Search
    $validStatuses = ['pending', 'in_review', 'approved', 'rejected'];
    $statusFilter = isset($_GET['status']) && in_array($_GET['status'], $validStatuses, true) ? $_GET['status'] : '';
    $searchQuery  = trim($_GET['search'] ?? '');

    $whereClauses = [];
    $params = [];

    if ($statusFilter !== '') {
        $whereClauses[] = "onboarding_status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($searchQuery !== '') {
        $whereClauses[] = "(staff_name LIKE :search OR staff_email LIKE :search OR staff_phone LIKE :search OR credential_type LIKE :search OR chore_labor LIKE :search OR drivers_license_number LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $exportStmt = $dbConnection->prepare("SELECT * FROM care_staff_onboarding $whereSql ORDER BY id DESC");
        $exportStmt->execute($params);
        $rows = $exportStmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="staff_onboarding_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ID', 'Staff Name', 'Email', 'Phone', 'Address',
            '1. Gov ID Type', '1. Gov ID Number',
            '3. Background Check', '4. FWA Status',
            '5. Credentials (SFN749/CNA/LPN/RN)', '5. Credential Number',
            '6. Chore Labor',
            '7. Bank Name', '7. Account Type',
            '8. W-4 Status', '9. I-9 Status',
            '10. Therap Status', '10. Therap Username',
            '11. Digital Signature', '11. Signature Date',
            '12. Drivers License #', '12. License State', '12. License Expiry',
            '13. Insurance Provider', '13. Insurance Policy #',
            'Status', 'Submitted At'
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['staff_name'], $r['staff_email'], $r['staff_phone'], $r['staff_address'],
                $r['gov_id_type'], $r['gov_id_number'],
                $r['background_check_status'], $r['fwa_status'],
                $r['credential_type'], $r['credential_number'],
                $r['chore_labor'],
                $r['bank_name'], $r['account_type'],
                $r['w4_filing_status'], $r['i9_work_status'],
                $r['therap_status'], $r['therap_username'],
                $r['digital_signature'], $r['signature_date'],
                $r['drivers_license_number'], $r['drivers_license_state'], $r['drivers_license_expiry'],
                $r['drivers_insurance_provider'], $r['drivers_insurance_policy'],
                $r['onboarding_status'], $r['created_at']
            ]);
        }
        fclose($out);
        exit;
    }

    // Status tab counts
    $statusCounts = ['all' => 0, 'pending' => 0, 'in_review' => 0, 'approved' => 0, 'rejected' => 0];
    $countStmt = $dbConnection->query("SELECT onboarding_status, COUNT(*) as cnt FROM care_staff_onboarding GROUP BY onboarding_status");
    foreach ($countStmt->fetchAll() as $cRow) {
        if (isset($statusCounts[$cRow['onboarding_status']])) {
            $statusCounts[$cRow['onboarding_status']] = (int)$cRow['cnt'];
            $statusCounts['all'] += (int)$cRow['cnt'];
        }
    }

    // Pagination
    $limit = 10;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalStmt = $dbConnection->prepare("SELECT COUNT(*) FROM care_staff_onboarding $whereSql");
    $totalStmt->execute($params);
    $totalRecords = (int)$totalStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $limit));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;

    // Fetch records
    $stmt = $dbConnection->prepare("SELECT * FROM care_staff_onboarding $whereSql ORDER BY id DESC LIMIT $offset, $limit");
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    $pageTitle = "Staff Onboarding";
    include 'includes/header.php';
    include 'includes/sidebar.php';

    function getObStatusBadge($status) {
        return match($status) {
            'pending'   => 'badge-soft-warning',
            'in_review' => 'badge-soft-info',
            'approved'  => 'badge-soft-success',
            'rejected'  => 'badge-soft-danger',
            default     => 'badge-soft-secondary'
        };
    }

    function formatObStatusLabel($status) {
        return match($status) {
            'pending'   => 'Pending Review',
            'in_review' => 'In Review',
            'approved'  => 'Approved',
            'rejected'  => 'Rejected',
            default     => ucfirst($status)
        };
    }
?>

<!-- Top Banner: Public Onboarding Form Link & Filter Bar -->
<div class="card-modern p-3 p-md-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-3 border-bottom gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge-soft badge-soft-primary"><i class="fas fa-clipboard-check"></i> 13-Point Onboarding Packet</span>
            <span class="text-muted small d-none d-md-inline">Share the Google-Forms-style onboarding page with new hires:</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-light border fw-semibold" id="copyFormLinkBtn" data-url="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . PROOT . 'onboarding'; ?>">
                <i class="far fa-copy me-1 text-teal" style="color:#0d9488;"></i>Copy Onboarding Link
            </button>
            <a href="<?= PROOT; ?>onboarding" target="_blank" class="btn btn-sm btn-teal">
                <i class="fas fa-arrow-up-right-from-square me-1"></i>Open Public Onboarding Form
            </a>
        </div>
    </div>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <!-- Status Filter Pills -->
        <div class="filter-pills">
            <a href="<?= PROOT; ?>admin/onboarding<?= $searchQuery ? '?search=' . urlencode($searchQuery) : ''; ?>" 
               class="filter-pill <?= $statusFilter === '' ? 'active' : ''; ?>">
                All <span class="pill-count"><?= $statusCounts['all']; ?></span>
            </a>
            <?php foreach ($validStatuses as $st): ?>
                <a href="<?= PROOT; ?>admin/onboarding?status=<?= $st; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="filter-pill <?= $statusFilter === $st ? 'active' : ''; ?>">
                    <?= formatObStatusLabel($st); ?> <span class="pill-count"><?= $statusCounts[$st]; ?></span>
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
                    <input type="text" name="search" class="form-control" placeholder="Search staff, license, credentials..." value="<?= e($searchQuery); ?>">
                    <?php if ($searchQuery !== ''): ?>
                        <a href="<?= PROOT; ?>admin/onboarding<?= $statusFilter ? '?status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-outline-secondary" title="Clear">
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

<!-- Staff Onboarding Submissions Table -->
<div class="card-modern overflow-hidden">
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Staff Member</th>
                    <th>Credentials & Chore</th>
                    <th>Compliance & Screening</th>
                    <th>Files Uploaded</th>
                    <th>Signed Policies</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $row): ?>
                        <?php
                            $fileCols = [
                                'gov_id_file'            => '1. Government ID',
                                'ssn_file'               => '2. SSN Card',
                                'background_check_file'  => '3. Background Check',
                                'fwa_certificate_file'   => '4. FWA Certificate',
                                'credential_file'        => '5. SFN749 / CNA / LPN / RN',
                                'direct_deposit_file'    => '7. Direct Deposit / Voided Check',
                                'w4_file'                => '8. W-4 Form',
                                'i9_file'                => '9. I-9 Form',
                                'drivers_license_file'   => '12. Driver\'s License',
                                'drivers_insurance_file' => '13. Driver\'s Insurance',
                            ];
                            $attachedFiles = [];
                            foreach ($fileCols as $colKey => $colLabel) {
                                if (!empty($row[$colKey])) {
                                    $attachedFiles[] = [
                                        'label' => $colLabel,
                                        'url'   => PROOT . $row[$colKey],
                                        'name'  => basename($row[$colKey])
                                    ];
                                }
                            }
                            $signedCount = (int)$row['policy_declaration'] + (int)$row['compliance_agreed'] + (int)$row['policy_procedures_agreed'] + (int)$row['reporting_agreed'];
                        ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= (int)$row['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle">
                                        <?= strtoupper(substr(e($row['staff_name']), 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= e($row['staff_name']); ?></div>
                                        <div class="text-muted small">
                                            <?= e($row['staff_email']); ?> &bull; <?= e($row['staff_phone']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium small text-dark"><?= e($row['credential_type'] ?: 'Not specified'); ?></div>
                                <div class="text-muted small text-truncate" style="max-width: 190px;" title="<?= e($row['chore_labor']); ?>">
                                    Chore: <?= e($row['chore_labor'] ?: '—'); ?>
                                </div>
                            </td>
                            <td>
                                <div class="small"><strong>BG:</strong> <?= e($row['background_check_status'] ?: '—'); ?></div>
                                <div class="small text-muted"><strong>FWA:</strong> <?= e($row['fwa_status'] ?: '—'); ?> | <strong>Therap:</strong> <?= e($row['therap_status'] ?: '—'); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-semibold">
                                    <i class="fas fa-paperclip text-secondary me-1"></i><?= count($attachedFiles); ?> / 10 files
                                </span>
                            </td>
                            <td>
                                <span class="badge-soft <?= $signedCount === 4 ? 'badge-soft-success' : 'badge-soft-warning'; ?>">
                                    <i class="fas fa-signature"></i> <?= $signedCount; ?>/4 Signed
                                </span>
                            </td>
                            <td>
                                <span class="badge-soft <?= getObStatusBadge($row['onboarding_status']); ?>">
                                    <?= formatObStatusLabel($row['onboarding_status']); ?>
                                </span>
                            </td>
                            <td class="small text-muted text-nowrap">
                                <?= date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-light border btn-action me-1 view-ob-btn"
                                        title="Inspect Full 13-Point Onboarding Packet"
                                        data-ob='<?= htmlspecialchars(json_encode([
                                            'id' => $row['id'],
                                            'staff_name' => $row['staff_name'],
                                            'staff_email' => $row['staff_email'],
                                            'staff_phone' => $row['staff_phone'],
                                            'staff_address' => $row['staff_address'] ?: 'Not provided',
                                            'gov_id_type' => $row['gov_id_type'] ?: '—',
                                            'gov_id_number' => $row['gov_id_number'] ?: '—',
                                            'ssn_number' => $row['ssn_number'] ?: '—',
                                            'background_check_status' => $row['background_check_status'] ?: '—',
                                            'fwa_status' => $row['fwa_status'] ?: '—',
                                            'credential_type' => $row['credential_type'] ?: '—',
                                            'credential_number' => $row['credential_number'] ?: '—',
                                            'chore_labor' => $row['chore_labor'] ?: '—',
                                            'chore_labor_notes' => $row['chore_labor_notes'] ?: '',
                                            'bank_name' => $row['bank_name'] ?: '—',
                                            'account_type' => $row['account_type'] ?: '—',
                                            'routing_number' => $row['routing_number'] ?: '—',
                                            'account_number' => $row['account_number'] ?: '—',
                                            'w4_filing_status' => $row['w4_filing_status'] ?: '—',
                                            'i9_work_status' => $row['i9_work_status'] ?: '—',
                                            'therap_status' => $row['therap_status'] ?: '—',
                                            'therap_username' => $row['therap_username'] ?: '—',
                                            'policy_declaration' => (int)$row['policy_declaration'],
                                            'compliance_agreed' => (int)$row['compliance_agreed'],
                                            'policy_procedures_agreed' => (int)$row['policy_procedures_agreed'],
                                            'reporting_agreed' => (int)$row['reporting_agreed'],
                                            'digital_signature' => $row['digital_signature'] ?: '—',
                                            'signature_date' => $row['signature_date'] ?: '—',
                                            'drivers_license_number' => $row['drivers_license_number'] ?: '—',
                                            'drivers_license_state' => $row['drivers_license_state'] ?: '—',
                                            'drivers_license_expiry' => $row['drivers_license_expiry'] ?: '—',
                                            'drivers_insurance_provider' => $row['drivers_insurance_provider'] ?: '—',
                                            'drivers_insurance_policy' => $row['drivers_insurance_policy'] ?: '—',
                                            'additional_notes' => $row['additional_notes'] ?: '',
                                            'admin_notes' => $row['admin_notes'] ?: '',
                                            'status' => formatObStatusLabel($row['onboarding_status']),
                                            'created_at' => date('M d, Y h:i A', strtotime($row['created_at'])),
                                            'files' => $attachedFiles
                                        ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <i class="fas fa-folder-open text-primary"></i>
                                </button>

                                <button type="button" class="btn btn-light border btn-action me-1 edit-ob-btn"
                                        title="Update Status & Approve"
                                        data-id="<?= (int)$row['id']; ?>"
                                        data-name="<?= e($row['staff_name']); ?>"
                                        data-status="<?= e($row['onboarding_status']); ?>"
                                        data-notes="<?= e($row['admin_notes'] ?? ''); ?>">
                                    <i class="fas fa-user-check text-success"></i>
                                </button>

                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Permanently delete onboarding packet #<?= (int)$row['id']; ?> and its uploaded files?');">
                                    <input type="hidden" name="onboarding_id" value="<?= (int)$row['id']; ?>">
                                    <button type="submit" name="delete_onboarding" class="btn btn-light border btn-action" title="Delete Packet">
                                        <i class="fas fa-trash-can text-danger"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="fas fa-clipboard-list fa-2x opacity-50"></i></div>
                            <div class="fw-semibold">No staff onboarding submissions found</div>
                            <div class="text-muted small mb-3">Share the public onboarding form link with new staff members to collect their 13-point onboarding packet.</div>
                            <a href="<?= PROOT; ?>onboarding" target="_blank" class="btn btn-sm btn-teal">
                                <i class="fas fa-external-link-alt me-1"></i>Preview Public Onboarding Form
                            </a>
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
                Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= $totalRecords; ?> total records)
            </span>
            <nav aria-label="Onboarding pagination">
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

<!-- Shared Full 13-Point Onboarding Dossier Modal -->
<div class="modal fade" id="viewObModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Staff Onboarding Dossier <span id="vo_id" class="text-muted"></span></h5>
                    <span class="text-muted small">Submitted by <strong id="vo_staff_name_top"></strong> on <span id="vo_created_at"></span></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Staff Contact Banner -->
                <div class="p-3 rounded-3 bg-light border mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="text-muted small">Full Legal Name</div>
                            <div class="fw-bold" id="vo_staff_name"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Email Address</div>
                            <div class="fw-semibold" id="vo_staff_email"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Phone Number</div>
                            <div class="fw-semibold" id="vo_staff_phone"></div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small">Residential Address</div>
                            <div class="fw-semibold" id="vo_staff_address"></div>
                        </div>
                    </div>
                </div>

                <!-- 13 Checklist Items Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border h-100">
                            <h6 class="fw-bold small text-uppercase text-teal mb-3" style="color:#0d9488;">1–6. Identification, Credentials & Chore</h6>
                            <p class="mb-2 small"><strong>1. Government ID:</strong> <span id="vo_gov_id_type"></span> (#<span id="vo_gov_id_number"></span>)</p>
                            <p class="mb-2 small"><strong>2. Social Security Number (SSN):</strong> <code class="fs-6 text-dark" id="vo_ssn_number"></code></p>
                            <p class="mb-2 small"><strong>3. Pass Background Check:</strong> <span id="vo_bg_status"></span></p>
                            <p class="mb-2 small"><strong>4. FWA Certificate:</strong> <span id="vo_fwa_status"></span></p>
                            <p class="mb-2 small"><strong>5. SFN749 / CNA / LPN / RN:</strong> <span id="vo_cred_type"></span> (#<span id="vo_cred_number"></span>)</p>
                            <p class="mb-0 small"><strong>6. Chore Labor:</strong> <span id="vo_chore_labor"></span> <span id="vo_chore_notes" class="text-muted"></span></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border h-100">
                            <h6 class="fw-bold small text-uppercase text-teal mb-3" style="color:#0d9488;">7–10, 12–13. Payroll, Tax, Therap & Driving</h6>
                            <p class="mb-2 small"><strong>7. Direct Deposit:</strong> <span id="vo_bank_name"></span> (<span id="vo_account_type"></span>) &bull; Routing: <code id="vo_routing"></code> &bull; Acct: <code id="vo_account"></code></p>
                            <p class="mb-2 small"><strong>8. W-4 Filing Status:</strong> <span id="vo_w4_status"></span></p>
                            <p class="mb-2 small"><strong>9. I-9 Work Eligibility:</strong> <span id="vo_i9_status"></span></p>
                            <p class="mb-2 small"><strong>10. Therap Account:</strong> <span id="vo_therap_status"></span> (User: <span id="vo_therap_user"></span>)</p>
                            <p class="mb-2 small"><strong>12. Driver's License:</strong> #<span id="vo_dl_number"></span> (<span id="vo_dl_state"></span>) &bull; Exp: <span id="vo_dl_expiry"></span></p>
                            <p class="mb-0 small"><strong>13. Driver's Insurance:</strong> <span id="vo_ins_provider"></span> (Policy #<span id="vo_ins_policy"></span>)</p>
                        </div>
                    </div>
                </div>

                <!-- 11. Final Onboarding Policy Declarations & Electronic Signature -->
                <div class="p-3 rounded-3 border bg-light mb-4">
                    <h6 class="fw-bold small text-uppercase mb-2">11. Sign Final Onboarding for New Staff</h6>
                    <div class="row g-2 small mb-3">
                        <div class="col-sm-6 col-md-3" id="vo_chk_policy"></div>
                        <div class="col-sm-6 col-md-3" id="vo_chk_compliance"></div>
                        <div class="col-sm-6 col-md-3" id="vo_chk_procedures"></div>
                        <div class="col-sm-6 col-md-3" id="vo_chk_reporting"></div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center pt-2 border-top small">
                        <div><strong>Electronic Signature:</strong> <span id="vo_signature" class="fst-italic fs-6 ms-1" style="font-family: Georgia, serif;"></span></div>
                        <div><strong>Date Signed:</strong> <span id="vo_sig_date"></span></div>
                    </div>
                </div>

                <!-- Uploaded Documents List -->
                <div class="mb-3">
                    <h6 class="fw-bold small text-uppercase mb-2"><i class="fas fa-paperclip me-1"></i>Uploaded Onboarding Documents</h6>
                    <div class="row g-2" id="vo_files_container"></div>
                </div>

                <div id="vo_additional_wrap" class="mt-3" style="display:none;">
                    <h6 class="fw-bold small text-uppercase text-muted mb-1">Staff Additional Comments</h6>
                    <div class="p-3 rounded-3 border bg-white small" id="vo_additional_notes"></div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Shared Review / Update Status Modal -->
<div class="modal fade" id="editObModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Review Onboarding Packet <span id="eo_id_label" class="text-muted"></span></h5>
                    <span class="text-muted small" id="eo_name_label"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body p-4">
                    <input type="hidden" name="onboarding_id" id="eo_onboarding_id">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Onboarding Verification Status</label>
                        <select name="onboarding_status" id="eo_status" class="form-select" required>
                            <option value="pending">Pending Review</option>
                            <option value="in_review">In Review (Verifying Documents)</option>
                            <option value="approved">Approved / Onboarding Complete</option>
                            <option value="rejected">Rejected / Incomplete</option>
                        </select>
                    </div>

                    <div class="p-3 rounded-3 border bg-light mb-3" id="eo_sync_box" style="display:none;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="sync_caregiver" value="1" id="syncCgCheck" checked>
                            <label class="form-check-label fw-semibold small" for="syncCgCheck">
                                Sync / Activate in Caregivers &amp; Staff Directory
                            </label>
                        </div>
                        <div class="small text-muted mt-1">Automatically creates or updates this staff member's active profile in the Caregivers Directory.</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold small">HR / Compliance Notes</label>
                        <textarea name="admin_notes" id="eo_notes" class="form-control" rows="3" placeholder="Record document verification notes, missing items, or HR comments..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_onboarding" class="btn btn-teal px-4">Save Verification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy onboarding link button
    const copyBtn = document.getElementById('copyFormLinkBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(() => {
                const origHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check text-success me-1"></i>Link Copied!';
                setTimeout(() => { this.innerHTML = origHtml; }, 2000);
            });
        });
    }

    function renderCheckItem(agreed, label) {
        return agreed
            ? '<span class="text-success fw-semibold"><i class="fas fa-circle-check me-1"></i>' + label + '</span>'
            : '<span class="text-danger fw-semibold"><i class="fas fa-circle-xmark me-1"></i>' + label + '</span>';
    }

    const viewModal = new bootstrap.Modal(document.getElementById('viewObModal'));
    document.querySelectorAll('.view-ob-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = JSON.parse(this.getAttribute('data-ob'));
            document.getElementById('vo_id').textContent = '#' + d.id;
            document.getElementById('vo_staff_name_top').textContent = d.staff_name;
            document.getElementById('vo_created_at').textContent = d.created_at;
            document.getElementById('vo_staff_name').textContent = d.staff_name;
            document.getElementById('vo_staff_email').textContent = d.staff_email;
            document.getElementById('vo_staff_phone').textContent = d.staff_phone;
            document.getElementById('vo_staff_address').textContent = d.staff_address;

            document.getElementById('vo_gov_id_type').textContent = d.gov_id_type;
            document.getElementById('vo_gov_id_number').textContent = d.gov_id_number;
            document.getElementById('vo_ssn_number').textContent = d.ssn_number;
            document.getElementById('vo_bg_status').textContent = d.background_check_status;
            document.getElementById('vo_fwa_status').textContent = d.fwa_status;
            document.getElementById('vo_cred_type').textContent = d.credential_type;
            document.getElementById('vo_cred_number').textContent = d.credential_number;
            document.getElementById('vo_chore_labor').textContent = d.chore_labor;
            document.getElementById('vo_chore_notes').textContent = d.chore_labor_notes ? '(' + d.chore_labor_notes + ')' : '';

            document.getElementById('vo_bank_name').textContent = d.bank_name;
            document.getElementById('vo_account_type').textContent = d.account_type;
            document.getElementById('vo_routing').textContent = d.routing_number;
            document.getElementById('vo_account').textContent = d.account_number;
            document.getElementById('vo_w4_status').textContent = d.w4_filing_status;
            document.getElementById('vo_i9_status').textContent = d.i9_work_status;
            document.getElementById('vo_therap_status').textContent = d.therap_status;
            document.getElementById('vo_therap_user').textContent = d.therap_username;
            document.getElementById('vo_dl_number').textContent = d.drivers_license_number;
            document.getElementById('vo_dl_state').textContent = d.drivers_license_state;
            document.getElementById('vo_dl_expiry').textContent = d.drivers_license_expiry;
            document.getElementById('vo_ins_provider').textContent = d.drivers_insurance_provider;
            document.getElementById('vo_ins_policy').textContent = d.drivers_insurance_policy;

            document.getElementById('vo_chk_policy').innerHTML = renderCheckItem(d.policy_declaration, 'Policy declaration');
            document.getElementById('vo_chk_compliance').innerHTML = renderCheckItem(d.compliance_agreed, 'Compliance');
            document.getElementById('vo_chk_procedures').innerHTML = renderCheckItem(d.policy_procedures_agreed, 'Policy & procedures');
            document.getElementById('vo_chk_reporting').innerHTML = renderCheckItem(d.reporting_agreed, 'Reporting');
            document.getElementById('vo_signature').textContent = d.digital_signature;
            document.getElementById('vo_sig_date').textContent = d.signature_date;

            const filesContainer = document.getElementById('vo_files_container');
            filesContainer.innerHTML = '';
            if (d.files && d.files.length > 0) {
                d.files.forEach(f => {
                    const col = document.createElement('div');
                    col.className = 'col-sm-6 col-md-4';
                    col.innerHTML = `
                        <a href="${f.url}" target="_blank" class="d-flex align-items-center justify-content-between p-2 px-3 rounded-3 border bg-white text-decoration-none text-dark small">
                            <span class="text-truncate fw-medium"><i class="fas fa-file-lines text-primary me-2"></i>${f.label}</span>
                            <i class="fas fa-download text-muted ms-2"></i>
                        </a>
                    `;
                    filesContainer.appendChild(col);
                });
            } else {
                filesContainer.innerHTML = '<div class="col-12"><div class="text-muted small fst-italic">No files attached to this submission.</div></div>';
            }

            const addWrap = document.getElementById('vo_additional_wrap');
            if (d.additional_notes && d.additional_notes.trim() !== '') {
                document.getElementById('vo_additional_notes').textContent = d.additional_notes;
                addWrap.style.display = 'block';
            } else {
                addWrap.style.display = 'none';
            }

            viewModal.show();
        });
    });

    const editModal = new bootstrap.Modal(document.getElementById('editObModal'));
    const statusSel = document.getElementById('eo_status');
    const syncBox = document.getElementById('eo_sync_box');

    function toggleSyncBox() {
        syncBox.style.display = statusSel.value === 'approved' ? 'block' : 'none';
    }
    statusSel.addEventListener('change', toggleSyncBox);

    document.querySelectorAll('.edit-ob-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('eo_onboarding_id').value = this.dataset.id;
            document.getElementById('eo_id_label').textContent = '#' + this.dataset.id;
            document.getElementById('eo_name_label').textContent = 'Staff: ' + this.dataset.name;
            statusSel.value = this.dataset.status;
            document.getElementById('eo_notes').value = this.dataset.notes;
            toggleSyncBox();
            editModal.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

