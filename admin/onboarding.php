<?php
include_once("../system/DatabaseConnector.php");
care_send_private_headers();

if (!admin_is_logged_in()) {
    admin_login_redirect();
}

ensure_onboarding_security_schema($dbConnection);
$adminActor = $admin_data['user_fullname'] ?? 'Admin';

// Build portal URL cleanly for invite sharing
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'carethatfeelslikehome.com';
$portalUrl = $scheme . '://' . $host . PROOT . 'onboarding';

// Handle POST actions (PRG pattern, all CSRF-protected)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid security token. Please try your action again.";
        redirect(PROOT . 'admin/onboarding');
    }

    // A. Generate New Single-Use / Expiring Staff Invite Passcode
    if (isset($_POST['create_invite'])) {
        $invName = trim($_POST['invite_staff_name'] ?? '');
        $invEmail = trim($_POST['invite_staff_email'] ?? '');
        $expHours = (int) ($_POST['invite_expiry_hours'] ?? 72);
        $maxUses = (int) ($_POST['invite_max_uses'] ?? 1);
        $sendInvite = !empty($_POST['send_invite_email']) && $invEmail !== '';

        try {
            $created = care_create_onboarding_invite(
                $dbConnection,
                $invName,
                $invEmail,
                $expHours,
                $maxUses,
                $adminActor
            );

            care_log_onboarding_event(
                $dbConnection,
                'admin_invite_created',
                $adminActor . ' generated invite for ' . ($invName ?: ($invEmail ?: 'New Staff'))
            );

            if ($sendInvite) {
                $mailBody = "
                        <p>Hello " . htmlspecialchars($invName ?: 'Team Member', ENT_QUOTES, 'UTF-8') . ",</p>
                        <p>Welcome to <strong>Care That Feels Like Home Corporation</strong>! Please complete your encrypted 13-point Staff Onboarding packet using the private link and access passcode below:</p>
                        <p>
                            <strong>Protected Portal:</strong> <a href=\"" . htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8') . "</a><br>
                            <strong>Access Passcode:</strong> <code>" . htmlspecialchars($created['code'], ENT_QUOTES, 'UTF-8') . "</code><br>
                            <strong>Expires:</strong> " . date('M d, Y h:i A', strtotime($created['expires_at'])) . "
                        </p>
                        <p>For your security, do not forward this passcode. All documents and sensitive details are encrypted with AES-256-GCM upon upload.</p>
                        <p>Best regards,<br>HR &amp; Compliance — Care That Feels Like Home</p>
                    ";
                @send_email($invName ?: 'New Staff Member', $invEmail, "Your Secure Staff Onboarding Invite &#45; Care That Feels Like Home", $mailBody);
            }

            // Store one-time plaintext code in session so HR can copy it once after redirect
            $_SESSION['NEW_ONBOARDING_INVITE'] = $created;
            $_SESSION['flash_success'] = "Secure Staff Invite Passcode generated" . ($sendInvite ? " and emailed to $invEmail!" : "!");
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Failed to generate invite code: " . $e->getMessage();
        }
        redirect(PROOT . 'admin/onboarding');
    }

    // B. Revoke an Invite Passcode
    if (isset($_POST['revoke_invite']) && !empty($_POST['invite_row_id'])) {
        $invId = (int) $_POST['invite_row_id'];
        $stmt = $dbConnection->prepare("UPDATE care_onboarding_invites SET is_revoked = 1 WHERE id = ?");
        $stmt->execute([$invId]);
        care_log_onboarding_event($dbConnection, 'admin_invite_revoked', $adminActor . ' revoked invite #' . $invId);
        $_SESSION['flash_success'] = "Invite passcode revoked immediately.";
        redirect(PROOT . 'admin/onboarding');
    }

    // C. Update Portal Master Password & Access Policy
    if (isset($_POST['update_portal_security'])) {
        $allowMaster = isset($_POST['allow_master_passcode']) && $_POST['allow_master_passcode'] === '1' ? '1' : '0';
        $newMasterPw = trim($_POST['new_master_password'] ?? '');

        care_set_onboarding_setting($dbConnection, 'allow_master_passcode', $allowMaster);

        if ($newMasterPw !== '') {
            if (strlen($newMasterPw) < 8) {
                $_SESSION['flash_error'] = "Master Portal Password must be at least 8 characters long.";
                redirect(PROOT . 'admin/onboarding');
            }
            $newHash = password_hash($newMasterPw, PASSWORD_BCRYPT, ['cost' => 12]);
            care_set_onboarding_setting($dbConnection, 'master_passcode_hash', $newHash);
            care_set_onboarding_setting($dbConnection, 'master_passcode_is_default', '0');
            care_log_onboarding_event($dbConnection, 'admin_master_pw_rotated', $adminActor . ' rotated master portal password');
            $_SESSION['flash_success'] = "Onboarding Master Password rotated and security policy updated.";
        } else {
            care_log_onboarding_event($dbConnection, 'admin_policy_updated', $adminActor . ' set allow_master_passcode=' . $allowMaster);
            $_SESSION['flash_success'] = $allowMaster === '1'
                ? "Portal security policy saved (Invite Passcodes + Master Password enabled)."
                : "Strict Invite-Only Mode enabled! Only single-use/expiring staff invite codes can unlock the portal.";
        }
        redirect(PROOT . 'admin/onboarding');
    }

    // D. Update Onboarding Status, Admin Notes & Optional Sync to Caregivers Directory
    if (isset($_POST['update_onboarding']) && !empty($_POST['onboarding_id'])) {
        $id = (int) $_POST['onboarding_id'];
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
                    $name = $ob['staff_name'];
                    $phone = $ob['staff_phone'];
                    $addr = $ob['staff_address'];
                    $svc = trim(($ob['credential_type'] ?: '') . ($ob['chore_labor'] ? ' | ' . $ob['chore_labor'] : ''), ' |');
                    $bgPassed = (stripos((string) $ob['background_check_status'], 'Passed') !== false) ? 'passed' : 'pending';

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

    // E. Delete Onboarding Submission & Shred Encrypted Vault Files
    if (isset($_POST['delete_onboarding']) && !empty($_POST['onboarding_id'])) {
        $id = (int) $_POST['onboarding_id'];
        try {
            $fStmt = $dbConnection->prepare("SELECT * FROM care_staff_onboarding WHERE id = ? LIMIT 1");
            $fStmt->execute([$id]);
            $fRow = $fStmt->fetch();
            if ($fRow) {
                $fileCols = [
                    'gov_id_file', 'ssn_file', 'background_check_file', 'fwa_certificate_file',
                    'credential_file', 'direct_deposit_file', 'w4_file', 'i9_file',
                    'policy_declaration_file', 'compliance_file', 'policy_procedures_file', 'reporting_file',
                    'drivers_license_file', 'drivers_insurance_file'
                ];
                foreach ($fileCols as $col) {
                    if (!empty($fRow[$col])) {
                        care_delete_vault_file((string) $fRow[$col]);
                    }
                }
            }

            $del = $dbConnection->prepare("DELETE FROM care_staff_onboarding WHERE id = ?");
            $del->execute([$id]);
            care_log_onboarding_event($dbConnection, 'admin_packet_deleted', $adminActor . ' deleted packet #' . $id . ' and vault files');
            $_SESSION['flash_success'] = "Onboarding packet #$id and its encrypted vault documents permanently deleted.";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error deleting record: " . $e->getMessage();
        }
        redirect(PROOT . 'admin/onboarding');
    }
}

// Filters & Search
$validStatuses = ['pending', 'in_review', 'approved', 'rejected'];
$statusFilter = isset($_GET['status']) && in_array($_GET['status'], $validStatuses, true) ? $_GET['status'] : '';
$searchQuery = trim($_GET['search'] ?? '');

$whereClauses = [];
$params = [];

if ($statusFilter !== '') {
    $whereClauses[] = "onboarding_status = :status";
    $params[':status'] = $statusFilter;
}

if ($searchQuery !== '') {
    $whereClauses[] = "(staff_name LIKE :search OR staff_email LIKE :search OR staff_phone LIKE :search OR credential_type LIKE :search OR chore_labor LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// CSV Export (Masks Gov ID; omits raw SSN & Bank Account numbers to prevent spreadsheet PII leakage)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStmt = $dbConnection->prepare("SELECT * FROM care_staff_onboarding $whereSql ORDER BY id DESC");
    $exportStmt->execute($params);
    $rows = $exportStmt->fetchAll();

    care_log_onboarding_event($dbConnection, 'admin_csv_export', $adminActor . ' exported onboarding compliance CSV');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="staff_onboarding_compliance_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'ID',
        'Staff Name',
        'Email',
        'Phone',
        'Address',
        '1. Gov ID Type',
        '1. Gov ID (Masked)',
        '2. SSN (Masked)',
        '3. Background Check',
        '4. FWA Status',
        '5. Credentials (SFN749/CNA/LPN/RN)',
        '5. Credential Number',
        '6. Chore Labor',
        '7. Bank Name',
        '7. Account Type',
        '7. Account (Masked)',
        '8. W-4 Status',
        '9. I-9 Status',
        '10. Therap Status',
        '10. Therap Username',
        '11. Digital Signature',
        '11. Signature Date',
        '12. License State',
        '12. License Expiry',
        '13. Insurance Provider',
        'Status',
        'Submitted At'
    ]);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['staff_name'],
            $r['staff_email'],
            $r['staff_phone'],
            $r['staff_address'],
            $r['gov_id_type'],
            care_mask_pii(care_decrypt_pii($r['gov_id_number'])),
            care_mask_pii(care_decrypt_pii($r['ssn_number']), 'ssn'),
            $r['background_check_status'],
            $r['fwa_status'],
            $r['credential_type'],
            $r['credential_number'],
            $r['chore_labor'],
            $r['bank_name'],
            $r['account_type'],
            care_mask_pii(care_decrypt_pii($r['account_number'])),
            $r['w4_filing_status'],
            $r['i9_work_status'],
            $r['therap_status'],
            $r['therap_username'],
            $r['digital_signature'],
            $r['signature_date'],
            $r['drivers_license_state'],
            $r['drivers_license_expiry'],
            $r['drivers_insurance_provider'],
            $r['onboarding_status'],
            $r['created_at']
        ]);
    }
    fclose($out);
    exit;
}

// Security & Access Control Metadata
$keyStatus = care_encryption_key_status();
$allowMaster = care_get_onboarding_setting($dbConnection, 'allow_master_passcode', '1') === '1';
$masterIsDefault = care_get_onboarding_setting($dbConnection, 'master_passcode_is_default', '0') === '1';

$newInviteBanner = null;
if (!empty($_SESSION['NEW_ONBOARDING_INVITE'])) {
    $newInviteBanner = $_SESSION['NEW_ONBOARDING_INVITE'];
    unset($_SESSION['NEW_ONBOARDING_INVITE']);
}

// Recent Invites & Security Audit Events
$invitesStmt = $dbConnection->query("SELECT * FROM care_onboarding_invites ORDER BY id DESC LIMIT 20");
$recentInvites = $invitesStmt->fetchAll();

$activeInvitesCount = 0;
foreach ($recentInvites as $invItem) {
    if (!$invItem['is_revoked'] && $invItem['used_count'] < $invItem['max_uses'] && strtotime($invItem['expires_at']) > time()) {
        $activeInvitesCount++;
    }
}

$auditStmt = $dbConnection->query("SELECT * FROM care_onboarding_attempts ORDER BY id DESC LIMIT 15");
$recentAuditLogs = $auditStmt->fetchAll();

// Status tab counts
$statusCounts = ['all' => 0, 'pending' => 0, 'in_review' => 0, 'approved' => 0, 'rejected' => 0];
$countStmt = $dbConnection->query("SELECT onboarding_status, COUNT(*) as cnt FROM care_staff_onboarding GROUP BY onboarding_status");
foreach ($countStmt->fetchAll() as $cRow) {
    if (isset($statusCounts[$cRow['onboarding_status']])) {
        $statusCounts[$cRow['onboarding_status']] = (int) $cRow['cnt'];
        $statusCounts['all'] += (int) $cRow['cnt'];
    }
}

// Pagination
$limit = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalStmt = $dbConnection->prepare("SELECT COUNT(*) FROM care_staff_onboarding $whereSql");
$totalStmt->execute($params);
$totalRecords = (int) $totalStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRecords / $limit));
if ($page > $totalPages)
    $page = $totalPages;
$offset = ($page - 1) * $limit;

// Fetch records
$stmt = $dbConnection->prepare("SELECT * FROM care_staff_onboarding $whereSql ORDER BY id DESC LIMIT $offset, $limit");
$stmt->execute($params);
$records = $stmt->fetchAll();

$pageTitle = "Staff Onboarding Vault";
include 'includes/header.php';
include 'includes/sidebar.php';

function getObStatusBadge($status)
{
    return match ($status) {
        'pending' => 'badge-soft-warning',
        'in_review' => 'badge-soft-info',
        'approved' => 'badge-soft-success',
        'rejected' => 'badge-soft-danger',
        default => 'badge-soft-secondary'
    };
}

function formatObStatusLabel($status)
{
    return match ($status) {
        'pending' => 'Pending Review',
        'in_review' => 'In Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => ucfirst($status)
    };
}
?>

<?php if (!$keyStatus['is_dedicated']): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center gap-3 mb-4 rounded-3 shadow-sm">
        <i class="fas fa-key fa-lg text-warning-emphasis"></i>
        <div class="small">
            <strong>Dedicated Encryption Key Recommended:</strong> Add
            <code>ONBOARDING_ENCRYPTION_KEY=&lt;64-hex-chars&gt;</code> to your server's <code>.env</code> file so vault
            encryption is tied to an explicit key you can back up safely.
        </div>
    </div>
<?php endif; ?>

<?php if ($allowMaster && $masterIsDefault): ?>
    <div
        class="alert alert-info border-info d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 rounded-3 shadow-sm">
        <div class="d-flex align-items-center gap-3 small">
            <i class="fas fa-shield-halved fa-lg text-primary"></i>
            <div>
                <strong>Security Notice — Rotate Default Master Passcode:</strong> The onboarding portal is protected! For
                maximum security, click <strong>Portal Security &amp; Passcodes</strong> to set your own custom Master
                Password (or enable <em>Strict Single-Use Invite Codes Only</em>).
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-primary fw-semibold" data-bs-toggle="modal"
            data-bs-target="#securityAccessModal">
            <i class="fas fa-sliders me-1"></i>Configure Passcodes
        </button>
    </div>
<?php endif; ?>

<?php if ($newInviteBanner): ?>
    <?php
    $inviteClipText = "Care That Feels Like Home — Encrypted Staff Onboarding Invite\n"
        . "Portal Link: " . $portalUrl . "\n"
        . "Single-Use Access Passcode: " . $newInviteBanner['code'] . "\n"
        . "Expires: " . date('M d, Y h:i A', strtotime($newInviteBanner['expires_at'])) . "\n"
        . "(Do not share this passcode publicly.)";
    ?>
    <div class="card-modern p-3 p-md-4 mb-4 border-success" style="background: #f0fdf4; border: 1px solid #86efac;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success"><i class="fas fa-key me-1"></i>New Invite Passcode Generated (Shown
                        Once)</span>
                    <?php if (!empty($newInviteBanner['staff_name'])): ?>
                        <span class="small fw-semibold text-dark">for <?= e($newInviteBanner['staff_name']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                    <code class="fs-5 fw-bold px-3 py-1 rounded bg-white border text-dark"
                        id="generatedInviteCodeText"><?= e($newInviteBanner['code']); ?></code>
                    <span class="small text-muted">
                        Max uses: <strong><?= (int) $newInviteBanner['max_uses']; ?></strong> &bull;
                        Expires: <strong><?= date('M d, Y h:i A', strtotime($newInviteBanner['expires_at'])); ?></strong>
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-success fw-semibold" id="copyFullInviteBtn"
                    data-invite="<?= htmlspecialchars($inviteClipText, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="far fa-copy me-1"></i>Copy Invite Link + Passcode
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Top Security Status & Filter Bar -->
<div class="card-modern p-3 p-md-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-3 border-bottom gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge-soft badge-soft-success">
                <i class="fas fa-lock"></i> AES-256-GCM Vault Active
            </span>
            <span class="badge-soft badge-soft-info">
                <i class="fas fa-user-shield"></i>
                <?= $allowMaster ? 'Invites + Master Password' : 'Strict Single-Use Invites Only'; ?>
            </span>
            <span class="text-muted small d-none d-xl-inline">
                Unlisted portal &bull; Zero public file URLs &bull; <?= $activeInvitesCount; ?> active
                invite<?= $activeInvitesCount === 1 ? '' : 's'; ?>
            </span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-teal fw-semibold" data-bs-toggle="modal"
                data-bs-target="#generateInviteModal">
                <i class="fas fa-key me-1"></i>Generate Staff Invite Code
            </button>
            <button type="button" class="btn btn-sm btn-light border fw-semibold" data-bs-toggle="modal"
                data-bs-target="#securityAccessModal">
                <i class="fas fa-shield-halved me-1 text-primary"></i>Portal Security &amp; Audit
            </button>
            <a href="<?= PROOT; ?>onboarding" target="_blank" class="btn btn-sm btn-light border"
                title="View Password-Protected Portal Gate">
                <i class="fas fa-arrow-up-right-from-square me-1 text-secondary"></i>View Portal Gate
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
                    <input type="text" name="search" class="form-control"
                        placeholder="Search staff, email, credentials..." value="<?= e($searchQuery); ?>">
                    <?php if ($searchQuery !== ''): ?>
                        <a href="<?= PROOT; ?>admin/onboarding<?= $statusFilter ? '?status=' . urlencode($statusFilter) : ''; ?>"
                            class="btn btn-outline-secondary" title="Clear">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-sm btn-teal px-3">Filter</button>
            </form>

            <a href="?export=csv<?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>"
                class="btn btn-sm btn-light border fw-semibold" title="Exports masked PII compliance summary">
                <i class="fas fa-file-csv me-1 text-success"></i>Export Masked CSV
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
                    <th>Credentials &amp; Chore</th>
                    <th>Compliance &amp; Screening</th>
                    <th>Vault Files</th>
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
                            'gov_id_file' => '1. Government ID',
                            'ssn_file' => '2. SSN Card',
                            'background_check_file' => '3. Background Check',
                            'fwa_certificate_file' => '4. FWA Certificate',
                            'credential_file' => '5. SFN749 / CNA / LPN / RN',
                            'direct_deposit_file' => '7. Direct Deposit / Voided Check',
                            'w4_file' => '8. W-4 Form',
                            'i9_file' => '9. I-9 Form',
                            'policy_declaration_file' => '11a. Policy Declaration',
                            'compliance_file' => '11b. Compliance',
                            'policy_procedures_file' => '11c. Policy & Procedures',
                            'reporting_file' => '11d. Reporting',
                            'drivers_license_file' => '12. Driver\'s License',
                            'drivers_insurance_file' => '13. Driver\'s Insurance',
                        ];
                        $attachedFiles = [];
                        foreach ($fileCols as $colKey => $colLabel) {
                            if (!empty($row[$colKey])) {
                                $baseVaultUrl = PROOT . 'admin/onboarding-vault.php?id=' . (int) $row['id'] . '&field=' . urlencode($colKey);
                                $attachedFiles[] = [
                                    'label' => $colLabel,
                                    'view_url' => $baseVaultUrl,
                                    'download_url' => $baseVaultUrl . '&download=1',
                                ];
                            }
                        }
                        $signedCount = (int) $row['policy_declaration'] + (int) $row['compliance_agreed'] + (int) $row['policy_procedures_agreed'] + (int) $row['reporting_agreed'];

                        // Mask sensitive PII in HTML source — decrypted values are ONLY fetched on-demand via authenticated POST
                        $maskedGovId = care_mask_pii(care_decrypt_pii($row['gov_id_number']));
                        $maskedSsn = care_mask_pii(care_decrypt_pii($row['ssn_number']), 'ssn');
                        $maskedRouting = care_mask_pii(care_decrypt_pii($row['routing_number']));
                        $maskedAccount = care_mask_pii(care_decrypt_pii($row['account_number']));
                        $maskedLicense = care_mask_pii(care_decrypt_pii($row['drivers_license_number']));
                        $maskedInsurance = care_mask_pii(care_decrypt_pii($row['drivers_insurance_policy']));
                        ?>
                        <tr>
                            <td class="fw-bold text-secondary">#<?= (int) $row['id']; ?></td>
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
                                <div class="fw-medium small text-dark"><?= e($row['credential_type'] ?: 'Not specified'); ?>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 190px;"
                                    title="<?= e($row['chore_labor']); ?>">
                                    Chore: <?= e($row['chore_labor'] ?: '—'); ?>
                                </div>
                            </td>
                            <td>
                                <div class="small"><strong>BG:</strong> <?= e($row['background_check_status'] ?: '—'); ?></div>
                                <div class="small text-muted"><strong>FWA:</strong> <?= e($row['fwa_status'] ?: '—'); ?> |
                                    <strong>Therap:</strong> <?= e($row['therap_status'] ?: '—'); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-semibold">
                                    <i class="fas fa-file-shield text-success me-1"></i><?= count($attachedFiles); ?> / 14
                                    encrypted
                                </span>
                            </td>
                            <td>
                                <span
                                    class="badge-soft <?= $signedCount === 4 ? 'badge-soft-success' : 'badge-soft-warning'; ?>">
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
                                    title="Inspect Encrypted 13-Point Dossier" data-ob='<?= htmlspecialchars(json_encode([
                                        'id' => (int) $row['id'],
                                        'staff_name' => $row['staff_name'],
                                        'staff_email' => $row['staff_email'],
                                        'staff_phone' => $row['staff_phone'],
                                        'staff_address' => $row['staff_address'] ?: 'Not provided',
                                        'gov_id_type' => $row['gov_id_type'] ?: '—',
                                        'gov_id_number' => $maskedGovId,
                                        'ssn_number' => $maskedSsn,
                                        'background_check_status' => $row['background_check_status'] ?: '—',
                                        'fwa_status' => $row['fwa_status'] ?: '—',
                                        'credential_type' => $row['credential_type'] ?: '—',
                                        'credential_number' => $row['credential_number'] ?: '—',
                                        'chore_labor' => $row['chore_labor'] ?: '—',
                                        'chore_labor_notes' => $row['chore_labor_notes'] ?: '',
                                        'bank_name' => $row['bank_name'] ?: '—',
                                        'account_type' => $row['account_type'] ?: '—',
                                        'routing_number' => $maskedRouting,
                                        'account_number' => $maskedAccount,
                                        'w4_filing_status' => $row['w4_filing_status'] ?: '—',
                                        'i9_work_status' => $row['i9_work_status'] ?: '—',
                                        'therap_status' => $row['therap_status'] ?: '—',
                                        'therap_username' => $row['therap_username'] ?: '—',
                                        'policy_declaration' => (int) $row['policy_declaration'],
                                        'compliance_agreed' => (int) $row['compliance_agreed'],
                                        'policy_procedures_agreed' => (int) $row['policy_procedures_agreed'],
                                        'reporting_agreed' => (int) $row['reporting_agreed'],
                                        'digital_signature' => $row['digital_signature'] ?: '—',
                                        'signature_date' => $row['signature_date'] ?: '—',
                                        'drivers_license_number' => $maskedLicense,
                                        'drivers_license_state' => $row['drivers_license_state'] ?: '—',
                                        'drivers_license_expiry' => $row['drivers_license_expiry'] ?: '—',
                                        'drivers_insurance_provider' => $row['drivers_insurance_provider'] ?: '—',
                                        'drivers_insurance_policy' => $maskedInsurance,
                                        'additional_notes' => $row['additional_notes'] ?: '',
                                        'admin_notes' => $row['admin_notes'] ?: '',
                                        'status' => formatObStatusLabel($row['onboarding_status']),
                                        'created_at' => date('M d, Y h:i A', strtotime($row['created_at'])),
                                        'files' => $attachedFiles
                                    ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <i class="fas fa-folder-open text-primary"></i>
                                </button>

                                <button type="button" class="btn btn-light border btn-action me-1 edit-ob-btn"
                                    title="Update Status & Approve" data-id="<?= (int) $row['id']; ?>"
                                    data-name="<?= e($row['staff_name']); ?>" data-status="<?= e($row['onboarding_status']); ?>"
                                    data-notes="<?= e($row['admin_notes'] ?? ''); ?>">
                                    <i class="fas fa-user-check text-success"></i>
                                </button>

                                <form method="post" action="" class="d-inline"
                                    onsubmit="return confirm('Permanently delete onboarding packet #<?= (int) $row['id']; ?> and shred all encrypted vault files?');">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="onboarding_id" value="<?= (int) $row['id']; ?>">
                                    <button type="submit" name="delete_onboarding" class="btn btn-light border btn-action"
                                        title="Delete Packet & Vault Files">
                                        <i class="fas fa-trash-can text-danger"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="fas fa-shield-halved fa-2x opacity-50"></i></div>
                            <div class="fw-semibold">No staff onboarding submissions found</div>
                            <div class="text-muted small mb-3">Generate a secure Single-Use Invite Passcode to invite a new
                                hire to complete their encrypted onboarding packet.</div>
                            <button type="button" class="btn btn-sm btn-teal" data-bs-toggle="modal"
                                data-bs-target="#generateInviteModal">
                                <i class="fas fa-key me-1"></i>Generate Staff Invite Code
                            </button>
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
                Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= $totalRecords; ?>
                total records)
            </span>
            <nav aria-label="Onboarding pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?= $page - 1; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>">&laquo;
                            Prev</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?= $i; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?page=<?= $page + 1; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>">Next
                            &raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- =====================================================================
     MODAL 1: GENERATE SINGLE-USE / EXPIRING STAFF INVITE CODE
     ===================================================================== -->
<div class="modal fade" id="generateInviteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="fas fa-key text-teal me-2"
                            style="color:#0d9488;"></i>Generate Staff Invite Passcode</h5>
                    <span class="text-muted small">Issue a time-limited, single-use passcode for a new hire</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <?= csrf_field(); ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">New Hire Name (Optional — Pre-fills Form)</label>
                        <input type="text" name="invite_staff_name" class="form-control"
                            placeholder="e.g., Sarah Jenkins">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">New Hire Email Address (Optional)</label>
                        <input type="email" name="invite_staff_email" id="invEmailInput" class="form-control"
                            placeholder="sarah@example.com">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">Expiration Window</label>
                            <select name="invite_expiry_hours" class="form-select">
                                <option value="24">24 Hours</option>
                                <option value="48">48 Hours</option>
                                <option value="72" selected>72 Hours (3 Days)</option>
                                <option value="168">7 Days</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold small">Usage Limit</label>
                            <select name="invite_max_uses" class="form-select">
                                <option value="1" selected>Single-Use (1 Submission)</option>
                                <option value="2">2 Submissions</option>
                                <option value="5">5 Submissions (Batch Hiring)</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-3 rounded-3 border bg-light">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="send_invite_email" value="1"
                                id="sendInviteEmailCheck" checked>
                            <label class="form-check-label fw-semibold small" for="sendInviteEmailCheck">
                                Email invite link &amp; passcode directly to staff member (if email entered)
                            </label>
                        </div>
                        <div class="small text-muted mt-1">You will also see the passcode once on-screen to copy via
                            SMS/WhatsApp.</div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_invite" class="btn btn-teal px-4">
                        <i class="fas fa-bolt me-1"></i>Generate Passcode
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================================
     MODAL 2: PORTAL SECURITY SETTINGS, INVITE LIST & AUDIT LOG
     ===================================================================== -->
<div class="modal fade" id="securityAccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i
                            class="fas fa-shield-halved text-primary me-2"></i>Onboarding Vault Security &amp; Access
                        Audit</h5>
                    <span class="text-muted small">Manage portal authentication policy, active invite codes, and
                        real-time security logs</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 mb-4">
                    <!-- Master Password & Policy Configuration -->
                    <div class="col-lg-5">
                        <div class="p-3 rounded-3 border bg-light h-100">
                            <h6 class="fw-bold mb-3"><i class="fas fa-sliders me-1 text-secondary"></i>Portal
                                Authentication Policy</h6>
                            <form method="post" action="">
                                <?= csrf_field(); ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Allowed Unlock Methods</label>
                                    <select name="allow_master_passcode" class="form-select form-select-sm">
                                        <option value="1" <?= $allowMaster ? 'selected' : ''; ?>>Individual Invite Codes
                                            + HR Master Password</option>
                                        <option value="0" <?= !$allowMaster ? 'selected' : ''; ?>>Strict Single-Use
                                            Invite Codes ONLY (Maximum Security)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Rotate HR Master Portal Password</label>
                                    <input type="password" name="new_master_password"
                                        class="form-control form-control-sm" minlength="8"
                                        placeholder="Leave blank to keep current password" autocomplete="new-password">
                                    <div class="form-text" style="font-size: 0.75rem;">
                                        Stored as a cost-12 bcrypt hash. Minimum 8 characters.
                                        <?php if ($masterIsDefault): ?>
                                            <span class="text-danger fw-semibold d-block mt-1">Default initial password
                                                (<code>CareHome#2026!</code>) is still set — please rotate or switch to
                                                Invite-Only mode.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button type="submit" name="update_portal_security"
                                    class="btn btn-sm btn-primary w-100 fw-semibold">
                                    <i class="fas fa-floppy-disk me-1"></i>Save Security Policy
                                </button>
                            </form>

                            <hr class="my-3">
                            <div class="small text-muted">
                                <div class="fw-semibold text-dark mb-1">Active Cryptographic Controls:</div>
                                <ul class="ps-3 mb-0" style="font-size: 0.78rem;">
                                    <li><strong>Cipher:</strong> OpenSSL <code>AES-256-GCM</code> (AEAD + 128-bit Auth
                                        Tag)</li>
                                    <li><strong>Vault Path:</strong> Non-public
                                        <code>system/secure_vault/onboarding/</code></li>
                                    <li><strong>Brute-Force Lock:</strong> 5 failed attempts / 15 min IP cooldown</li>
                                    <li><strong>MIME Verification:</strong> Magic-byte <code>finfo</code> inspection
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Issued Invite Passcodes List -->
                    <div class="col-lg-7">
                        <div class="p-3 rounded-3 border h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0"><i class="fas fa-ticket me-1 text-teal"
                                        style="color:#0d9488;"></i>Issued Staff Invite Passcodes</h6>
                                <span class="badge bg-light text-dark border"><?= count($recentInvites); ?>
                                    recent</span>
                            </div>
                            <div class="table-responsive" style="max-height: 270px;">
                                <table class="table table-sm align-middle small mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code Hint</th>
                                            <th>Issued For</th>
                                            <th>Uses</th>
                                            <th>Expires</th>
                                            <th>State</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentInvites)): ?>
                                            <?php foreach ($recentInvites as $inv): ?>
                                                <?php
                                                $isExpired = strtotime($inv['expires_at']) <= time();
                                                $isUsedUp = (int) $inv['used_count'] >= (int) $inv['max_uses'];
                                                if ($inv['is_revoked']) {
                                                    $invBadge = '<span class="badge bg-danger-subtle text-danger">Revoked</span>';
                                                } elseif ($isUsedUp) {
                                                    $invBadge = '<span class="badge bg-secondary-subtle text-secondary">Consumed</span>';
                                                } elseif ($isExpired) {
                                                    $invBadge = '<span class="badge bg-warning-subtle text-warning-emphasis">Expired</span>';
                                                } else {
                                                    $invBadge = '<span class="badge bg-success-subtle text-success">Active</span>';
                                                }
                                                ?>
                                                <tr>
                                                    <td><code><?= e($inv['code_hint']); ?></code></td>
                                                    <td>
                                                        <div class="fw-medium"><?= e($inv['staff_name'] ?: 'Any New Hire'); ?>
                                                        </div>
                                                        <?php if (!empty($inv['staff_email'])): ?>
                                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                                <?= e($inv['staff_email']); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= (int) $inv['used_count']; ?>/<?= (int) $inv['max_uses']; ?></td>
                                                    <td class="text-nowrap">
                                                        <?= date('M d, H:i', strtotime($inv['expires_at'])); ?></td>
                                                    <td><?= $invBadge; ?></td>
                                                    <td class="text-end">
                                                        <?php if (!$inv['is_revoked'] && !$isUsedUp && !$isExpired): ?>
                                                            <form method="post" action="" class="d-inline"
                                                                onsubmit="return confirm('Revoke invite <?= e($inv['code_hint']); ?> immediately?');">
                                                                <?= csrf_field(); ?>
                                                                <input type="hidden" name="invite_row_id"
                                                                    value="<?= (int) $inv['id']; ?>">
                                                                <button type="submit" name="revoke_invite"
                                                                    class="btn btn-xs btn-outline-danger py-0 px-2"
                                                                    style="font-size: 0.72rem;">Revoke</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">No individual invite
                                                    codes generated yet.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Audit Log -->
                <div class="p-3 rounded-3 border">
                    <h6 class="fw-bold mb-2"><i class="fas fa-clipboard-list me-1 text-secondary"></i>Recent Vault
                        Security &amp; Access Audit Log</h6>
                    <div class="table-responsive" style="max-height: 210px;">
                        <table class="table table-sm align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Event Type</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentAuditLogs)): ?>
                                    <?php foreach ($recentAuditLogs as $log): ?>
                                        <tr>
                                            <td class="text-nowrap text-muted">
                                                <?= date('M d, Y h:i:s A', strtotime($log['created_at'])); ?></td>
                                            <td><code><?= e($log['event_type']); ?></code></td>
                                            <td><code><?= e($log['ip_address']); ?></code></td>
                                            <td><?= e($log['details'] ?? '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No security audit events
                                            recorded yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- =====================================================================
     MODAL 3: ENCRYPTED 13-POINT ONBOARDING DOSSIER MODAL
     ===================================================================== -->
<div class="modal fade" id="viewObModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        Encrypted Onboarding Dossier <span id="vo_id" class="text-muted"></span>
                    </h5>
                    <span class="text-muted small">Submitted by <strong id="vo_staff_name_top"></strong> on <span
                            id="vo_created_at"></span></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-dark fw-semibold" id="revealPiiBtn">
                        <i class="fas fa-unlock-keyhole me-1 text-warning-emphasis"></i>Reveal Decrypted PII
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
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
                            <h6 class="fw-bold small text-uppercase text-teal mb-3" style="color:#0d9488;">1–6.
                                Identification, Credentials &amp; Chore</h6>
                            <p class="mb-2 small"><strong>1. Government ID:</strong> <span id="vo_gov_id_type"></span>
                                (<code id="vo_gov_id_number"></code>)</p>
                            <p class="mb-2 small"><strong>2. Social Security Number (SSN):</strong> <code
                                    class="fs-6 text-dark" id="vo_ssn_number"></code></p>
                            <p class="mb-2 small"><strong>3. Pass Background Check:</strong> <span
                                    id="vo_bg_status"></span></p>
                            <p class="mb-2 small"><strong>4. FWA Certificate:</strong> <span id="vo_fwa_status"></span>
                            </p>
                            <p class="mb-2 small"><strong>5. SFN749 / CNA / LPN / RN:</strong> <span
                                    id="vo_cred_type"></span> (#<span id="vo_cred_number"></span>)</p>
                            <p class="mb-0 small"><strong>6. Chore Labor:</strong> <span id="vo_chore_labor"></span>
                                <span id="vo_chore_notes" class="text-muted"></span></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-3 border h-100">
                            <h6 class="fw-bold small text-uppercase text-teal mb-3" style="color:#0d9488;">7–10, 12–13.
                                Payroll, Tax, Therap &amp; Driving</h6>
                            <p class="mb-2 small"><strong>7. Direct Deposit:</strong> <span id="vo_bank_name"></span>
                                (<span id="vo_account_type"></span>) &bull; Routing: <code id="vo_routing"></code>
                                &bull; Acct: <code id="vo_account"></code></p>
                            <p class="mb-2 small"><strong>8. W-4 Filing Status:</strong> <span id="vo_w4_status"></span>
                            </p>
                            <p class="mb-2 small"><strong>9. I-9 Work Eligibility:</strong> <span
                                    id="vo_i9_status"></span></p>
                            <p class="mb-2 small"><strong>10. Therap Account:</strong> <span
                                    id="vo_therap_status"></span> (User: <span id="vo_therap_user"></span>)</p>
                            <p class="mb-2 small"><strong>12. Driver's License:</strong> <code id="vo_dl_number"></code>
                                (<span id="vo_dl_state"></span>) &bull; Exp: <span id="vo_dl_expiry"></span></p>
                            <p class="mb-0 small"><strong>13. Driver's Insurance:</strong> <span
                                    id="vo_ins_provider"></span> (Policy <code id="vo_ins_policy"></code>)</p>
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
                        <div><strong>Electronic Signature:</strong> <span id="vo_signature" class="fst-italic fs-6 ms-1"
                                style="font-family: Georgia, serif;"></span></div>
                        <div><strong>Date Signed:</strong> <span id="vo_sig_date"></span></div>
                    </div>
                </div>

                <!-- Encrypted Vault Documents List -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold small text-uppercase mb-0">
                            <i class="fas fa-file-shield text-success me-1"></i>Encrypted Vault Documents (Decrypted
                            In-Memory for Admin Only)
                        </h6>
                        <span class="badge bg-success-subtle text-success border small">AES-256-GCM Authenticated</span>
                    </div>
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

<!-- =====================================================================
     MODAL 4: REVIEW / UPDATE STATUS MODAL
     ===================================================================== -->
<div class="modal fade" id="editObModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">Review Onboarding Packet <span id="eo_id_label"
                            class="text-muted"></span></h5>
                    <span class="text-muted small" id="eo_name_label"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <?= csrf_field(); ?>
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
                            <input class="form-check-input" type="checkbox" name="sync_caregiver" value="1"
                                id="syncCgCheck" checked>
                            <label class="form-check-label fw-semibold small" for="syncCgCheck">
                                Sync / Activate in Caregivers &amp; Staff Directory
                            </label>
                        </div>
                        <div class="small text-muted mt-1">Automatically creates or updates this staff member's active
                            profile in the Caregivers Directory.</div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold small">HR / Compliance Notes</label>
                        <textarea name="admin_notes" id="eo_notes" class="form-control" rows="3"
                            placeholder="Record document verification notes, missing items, or HR comments..."></textarea>
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
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = <?= json_encode(csrf_token()); ?>;
        const vaultEndpoint = <?= json_encode(PROOT . 'admin/onboarding-vault.php'); ?>;

        // Copy generated invite block button
        const copyFullInviteBtn = document.getElementById('copyFullInviteBtn');
        if (copyFullInviteBtn) {
            copyFullInviteBtn.addEventListener('click', function () {
                const text = this.getAttribute('data-invite');
                navigator.clipboard.writeText(text).then(() => {
                    const origHtml = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check me-1"></i>Invite & Passcode Copied!';
                    setTimeout(() => { this.innerHTML = origHtml; }, 2500);
                });
            });
        }

        function renderCheckItem(agreed, label) {
            return agreed
                ? '<span class="text-success fw-semibold"><i class="fas fa-circle-check me-1"></i>' + label + '</span>'
                : '<span class="text-danger fw-semibold"><i class="fas fa-circle-xmark me-1"></i>' + label + '</span>';
        }

        let activeDossier = null;
        let piiRevealed = false;
        const revealBtn = document.getElementById('revealPiiBtn');
        const viewModalEl = document.getElementById('viewObModal');
        const viewModal = new bootstrap.Modal(viewModalEl);

        function applyMaskedPii() {
            if (!activeDossier) return;
            piiRevealed = false;
            document.getElementById('vo_gov_id_number').textContent = activeDossier.gov_id_number;
            document.getElementById('vo_ssn_number').textContent = activeDossier.ssn_number;
            document.getElementById('vo_routing').textContent = activeDossier.routing_number;
            document.getElementById('vo_account').textContent = activeDossier.account_number;
            document.getElementById('vo_dl_number').textContent = activeDossier.drivers_license_number;
            document.getElementById('vo_ins_policy').textContent = activeDossier.drivers_insurance_policy;
            if (revealBtn) {
                revealBtn.disabled = false;
                revealBtn.innerHTML = '<i class="fas fa-unlock-keyhole me-1 text-warning-emphasis"></i>Reveal Decrypted PII';
            }
        }

        if (revealBtn) {
            revealBtn.addEventListener('click', function () {
                if (!activeDossier) return;
                if (piiRevealed) {
                    applyMaskedPii();
                    return;
                }

                revealBtn.disabled = true;
                revealBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Decrypting...';

                const body = new URLSearchParams();
                body.append('action', 'reveal_pii');
                body.append('id', String(activeDossier.id));
                body.append('csrf_token', csrfToken);

                fetch(vaultEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                    .then(r => r.json())
                    .then(res => {
                        revealBtn.disabled = false;
                        if (res && res.ok && res.data) {
                            piiRevealed = true;
                            document.getElementById('vo_gov_id_number').textContent = res.data.gov_id_number;
                            document.getElementById('vo_ssn_number').textContent = res.data.ssn_number;
                            document.getElementById('vo_routing').textContent = res.data.routing_number;
                            document.getElementById('vo_account').textContent = res.data.account_number;
                            document.getElementById('vo_dl_number').textContent = res.data.drivers_license_number;
                            document.getElementById('vo_ins_policy').textContent = res.data.drivers_insurance_policy;
                            revealBtn.innerHTML = '<i class="fas fa-eye-slash me-1 text-secondary"></i>Mask PII';
                        } else {
                            alert((res && res.error) ? res.error : 'Unable to decrypt PII.');
                            applyMaskedPii();
                        }
                    })
                    .catch(() => {
                        alert('Network error while contacting encrypted vault.');
                        applyMaskedPii();
                    });
            });
        }

        // Always wipe decrypted PII from DOM when closing modal
        viewModalEl.addEventListener('hidden.bs.modal', function () {
            applyMaskedPii();
            activeDossier = null;
        });

        document.querySelectorAll('.view-ob-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const d = JSON.parse(this.getAttribute('data-ob'));
                activeDossier = d;
                applyMaskedPii();

                document.getElementById('vo_id').textContent = '#' + d.id;
                document.getElementById('vo_staff_name_top').textContent = d.staff_name;
                document.getElementById('vo_created_at').textContent = d.created_at;
                document.getElementById('vo_staff_name').textContent = d.staff_name;
                document.getElementById('vo_staff_email').textContent = d.staff_email;
                document.getElementById('vo_staff_phone').textContent = d.staff_phone;
                document.getElementById('vo_staff_address').textContent = d.staff_address;

                document.getElementById('vo_gov_id_type').textContent = d.gov_id_type;
                document.getElementById('vo_bg_status').textContent = d.background_check_status;
                document.getElementById('vo_fwa_status').textContent = d.fwa_status;
                document.getElementById('vo_cred_type').textContent = d.credential_type;
                document.getElementById('vo_cred_number').textContent = d.credential_number;
                document.getElementById('vo_chore_labor').textContent = d.chore_labor;
                document.getElementById('vo_chore_notes').textContent = d.chore_labor_notes ? '(' + d.chore_labor_notes + ')' : '';

                document.getElementById('vo_bank_name').textContent = d.bank_name;
                document.getElementById('vo_account_type').textContent = d.account_type;
                document.getElementById('vo_w4_status').textContent = d.w4_filing_status;
                document.getElementById('vo_i9_status').textContent = d.i9_work_status;
                document.getElementById('vo_therap_status').textContent = d.therap_status;
                document.getElementById('vo_therap_user').textContent = d.therap_username;
                document.getElementById('vo_dl_state').textContent = d.drivers_license_state;
                document.getElementById('vo_dl_expiry').textContent = d.drivers_license_expiry;
                document.getElementById('vo_ins_provider').textContent = d.drivers_insurance_provider;

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

                        const wrap = document.createElement('div');
                        wrap.className = 'd-flex align-items-center justify-content-between p-2 px-3 rounded-3 border bg-white small gap-2';

                        const viewLink = document.createElement('a');
                        viewLink.href = f.view_url;
                        viewLink.target = '_blank';
                        viewLink.rel = 'noopener noreferrer';
                        viewLink.className = 'text-truncate fw-medium text-decoration-none text-dark flex-grow-1';
                        viewLink.innerHTML = '<i class="fas fa-file-shield text-success me-2"></i>';
                        viewLink.appendChild(document.createTextNode(f.label));

                        const dlLink = document.createElement('a');
                        dlLink.href = f.download_url;
                        dlLink.className = 'btn btn-sm btn-light border py-0 px-2 flex-shrink-0';
                        dlLink.title = 'Download Decrypted Copy';
                        dlLink.innerHTML = '<i class="fas fa-download text-secondary" style="font-size:0.75rem;"></i>';

                        wrap.appendChild(viewLink);
                        wrap.appendChild(dlLink);
                        col.appendChild(wrap);
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
            btn.addEventListener('click', function () {
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