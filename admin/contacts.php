<?php 
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    // Build current query string for redirects
    $returnQuery = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';

    // Handle POST actions before HTML output (PRG pattern)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Run Smart Auto-Scan & Grouping across all messages
        if (isset($_POST['run_auto_scan'])) {
            $scanStats = auto_scan_contact_messages($dbConnection);
            $_SESSION['flash_success'] = "Smart Scan complete: {$scanStats['important']} Important, {$scanStats['inbox']} Primary Inbox, and {$scanStats['spam']} Spam messages grouped.";
            redirect(PROOT . 'admin/contacts' . $returnQuery);
        }

        // 2. Empty entire Spam folder
        if (isset($_POST['empty_spam'])) {
            $delSpam = $dbConnection->query("DELETE FROM care_contact_messages WHERE is_spam = 1");
            $deletedCount = $delSpam->rowCount();
            $_SESSION['flash_success'] = "Emptied Spam folder ($deletedCount spam messages permanently deleted).";
            redirect(PROOT . 'admin/contacts?group=spam');
        }

        // 3. Toggle Important flag on a single message
        if (isset($_POST['toggle_important']) && !empty($_POST['message_id'])) {
            $id = (int)$_POST['message_id'];
            $newImp = (int)($_POST['is_important'] ?? 1);
            $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET is_important = ?, is_spam = CASE WHEN ? = 1 THEN 0 ELSE is_spam END WHERE id = ?");
            $stmt->execute([$newImp, $newImp, $id]);
            $_SESSION['flash_success'] = $newImp ? "Message #$id marked as Important." : "Message #$id removed from Important.";
            redirect(PROOT . 'admin/contacts' . $returnQuery);
        }

        // 4. Toggle Spam / Not Spam on a single message
        if (isset($_POST['toggle_spam']) && !empty($_POST['message_id'])) {
            $id = (int)$_POST['message_id'];
            $newSpam = (int)($_POST['is_spam'] ?? 1);
            if ($newSpam === 1) {
                $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET is_spam = 1, is_important = 0, spam_reason = 'Manual Admin Flag' WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['flash_success'] = "Message #$id moved to Spam group.";
            } else {
                $markImp = !empty($_POST['restore_important']) ? 1 : 0;
                $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET is_spam = 0, is_important = ?, spam_reason = NULL WHERE id = ?");
                $stmt->execute([$markImp, $id]);
                $_SESSION['flash_success'] = "Message #$id restored to Primary Inbox.";
            }
            redirect(PROOT . 'admin/contacts' . $returnQuery);
        }

        // 5. Bulk Actions on selected messages
        if (isset($_POST['apply_bulk_action']) && !empty($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $_POST['selected_ids'])));
            $action = $_POST['bulk_action'] ?? '';

            if (!empty($ids) && $action !== '') {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $cnt = count($ids);

                switch ($action) {
                    case 'mark_important':
                        $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET is_important = 1, is_spam = 0, spam_reason = NULL WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        $_SESSION['flash_success'] = "$cnt message(s) marked as Important.";
                        break;
                    case 'unmark_important':
                        $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET is_important = 0 WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        $_SESSION['flash_success'] = "$cnt message(s) removed from Important.";
                        break;
                    case 'move_spam':
                        $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET is_spam = 1, is_important = 0, spam_reason = 'Manual Admin Flag' WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        $_SESSION['flash_success'] = "$cnt message(s) moved to Spam group.";
                        break;
                    case 'not_spam':
                        $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET is_spam = 0, spam_reason = NULL WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        $_SESSION['flash_success'] = "$cnt message(s) restored to Primary Inbox.";
                        break;
                    case 'mark_read':
                        $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET message_status = 'read' WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        $_SESSION['flash_success'] = "$cnt message(s) marked as Read.";
                        break;
                    case 'delete':
                        $stmt = $dbConnection->prepare("DELETE FROM care_contact_messages WHERE id IN ($placeholders)");
                        $stmt->execute($ids);
                        $_SESSION['flash_success'] = "$cnt message(s) deleted.";
                        break;
                }
            }
            redirect(PROOT . 'admin/contacts' . $returnQuery);
        }

        // 6. Update single message read/unread/replied status
        if (isset($_POST['update_message_status']) && !empty($_POST['message_id'])) {
            $id = (int)$_POST['message_id'];
            $newStatus = $_POST['message_status'] ?? 'read';
            if (in_array($newStatus, ['unread', 'read', 'replied'], true)) {
                $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET message_status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $_SESSION['flash_success'] = "Message #$id marked as $newStatus.";
            }
            redirect(PROOT . 'admin/contacts' . $returnQuery);
        }

        // 7. Mark all unread in current group as read
        if (isset($_POST['mark_all_read'])) {
            $dbConnection->query("UPDATE care_contact_messages SET message_status = 'read' WHERE message_status = 'unread' AND is_spam = 0");
            $_SESSION['flash_success'] = "All unread inbox messages have been marked as read.";
            redirect(PROOT . 'admin/contacts' . $returnQuery);
        }

        // 8. Send email reply and mark as replied
        if (isset($_POST['send_reply']) && !empty($_POST['message_id'])) {
            $id = (int)$_POST['message_id'];
            $toEmail = trim($_POST['reply_to_email'] ?? '');
            $toName  = trim($_POST['reply_to_name'] ?? 'Valued Client');
            $subject = trim($_POST['reply_subject'] ?? 'Re: Your Inquiry - Care That Feels Like Home');
            $bodyTxt = trim($_POST['reply_body'] ?? '');

            if (!empty($toEmail) && !empty($bodyTxt)) {
                $htmlBody = "
                    <div style='font-family: Arial, sans-serif; color: #1e293b; line-height: 1.6;'>
                        <p>Dear " . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ",</p>
                        <p>" . nl2br(htmlspecialchars($bodyTxt, ENT_QUOTES, 'UTF-8')) . "</p>
                        <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'>
                        <p style='font-size:13px;color:#64748b;'>
                            <strong>Care That Feels Like Home Corporation</strong><br>
                            121 E Arikara Ave, Bismarck, ND Apt 307<br>
                            Phone: +1 (701) 934-4903 | Email: info@carethatfeelslikehome.com
                        </p>
                    </div>
                ";
                $sent = send_email($toName, $toEmail, $subject, $htmlBody);
                $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET message_status = 'replied', is_spam = 0 WHERE id = ?");
                $stmt->execute([$id]);

                if ($sent) {
                    $_SESSION['flash_success'] = "Reply sent to $toEmail and message marked as replied.";
                } else {
                    $_SESSION['flash_success'] = "Message #$id marked as replied (Note: SMTP delivery could not be verified in local environment).";
                }
            } else {
                $_SESSION['flash_error'] = "Please provide a reply message body.";
            }
            redirect(PROOT . 'admin/contacts' . $returnQuery);
        }

        // 9. Delete single contact message
        if (isset($_POST['delete_message']) && !empty($_POST['message_id'])) {
            $id = (int)$_POST['message_id'];
            $stmt = $dbConnection->prepare("DELETE FROM care_contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_success'] = "Message #$id deleted.";
            redirect(PROOT . 'admin/contacts' . $returnQuery);
        }
    }

    // Group & Status Filters
    $validGroups = ['inbox', 'important', 'spam', 'all'];
    $groupFilter = isset($_GET['group']) && in_array($_GET['group'], $validGroups, true) ? $_GET['group'] : 'inbox';

    $validStatuses = ['unread', 'read', 'replied'];
    $statusFilter = isset($_GET['status']) && in_array($_GET['status'], $validStatuses, true) ? $_GET['status'] : '';
    $searchQuery  = trim($_GET['search'] ?? '');

    // Compute Folder Group Counts
    $groupCounts = [
        'inbox'     => (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE is_spam = 0")->fetchColumn(),
        'important' => (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE is_important = 1 AND is_spam = 0")->fetchColumn(),
        'spam'      => (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE is_spam = 1")->fetchColumn(),
        'all'       => (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages")->fetchColumn(),
    ];

    $whereClauses = [];
    $params = [];

    if ($groupFilter === 'inbox') {
        $whereClauses[] = "is_spam = 0";
    } elseif ($groupFilter === 'important') {
        $whereClauses[] = "is_important = 1 AND is_spam = 0";
    } elseif ($groupFilter === 'spam') {
        $whereClauses[] = "is_spam = 1";
    }

    if ($statusFilter !== '') {
        $whereClauses[] = "message_status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($searchQuery !== '') {
        $whereClauses[] = "(message_name LIKE :search OR message_email LIKE :search OR message_phone LIKE :search OR message_subject LIKE :search OR message_message LIKE :search OR spam_reason LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $exportStmt = $dbConnection->prepare("SELECT * FROM care_contact_messages $whereSql ORDER BY is_important DESC, id DESC");
        $exportStmt->execute($params);
        $rows = $exportStmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contact_messages_' . $groupFilter . '_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Group', 'Important', 'Spam Reason', 'Sender Name', 'Email', 'Phone', 'Subject', 'Message', 'Status', 'Date Received']);
        foreach ($rows as $r) {
            $grpLabel = $r['is_spam'] ? 'Spam' : ($r['is_important'] ? 'Important' : 'Primary Inbox');
            fputcsv($out, [
                $r['id'],
                $grpLabel,
                $r['is_important'] ? 'Yes' : 'No',
                $r['spam_reason'] ?? '',
                html_entity_decode((string)$r['message_name'], ENT_QUOTES, 'UTF-8'),
                $r['message_email'],
                $r['message_phone'],
                html_entity_decode((string)$r['message_subject'], ENT_QUOTES, 'UTF-8'),
                html_entity_decode((string)$r['message_message'], ENT_QUOTES, 'UTF-8'),
                $r['message_status'],
                $r['created_at']
            ]);
        }
        fclose($out);
        exit;
    }

    // Status tab counts within the selected group
    $groupWhereOnly = match($groupFilter) {
        'inbox'     => 'WHERE is_spam = 0',
        'important' => 'WHERE is_important = 1 AND is_spam = 0',
        'spam'      => 'WHERE is_spam = 1',
        default     => ''
    };
    $statusCounts = ['all' => 0, 'unread' => 0, 'read' => 0, 'replied' => 0];
    $countStmt = $dbConnection->query("SELECT message_status, COUNT(*) as cnt FROM care_contact_messages $groupWhereOnly GROUP BY message_status");
    foreach ($countStmt->fetchAll() as $cRow) {
        if (isset($statusCounts[$cRow['message_status']])) {
            $statusCounts[$cRow['message_status']] = (int)$cRow['cnt'];
            $statusCounts['all'] += (int)$cRow['cnt'];
        }
    }

    // Pagination
    $limit = 12;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalStmt = $dbConnection->prepare("SELECT COUNT(*) FROM care_contact_messages $whereSql");
    $totalStmt->execute($params);
    $totalRecords = (int)$totalStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $limit));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;

    // Fetch contacts (Important messages pinned first in Inbox)
    $orderBy = ($groupFilter === 'inbox') ? "ORDER BY is_important DESC, id DESC" : "ORDER BY id DESC";
    $query = "SELECT * FROM care_contact_messages $whereSql $orderBy LIMIT $offset, $limit";
    $stmt = $dbConnection->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    $pageTitle = "Contact Inbox";
    include 'includes/header.php';
    include 'includes/sidebar.php';

    function getMsgStatusBadge($status) {
        return match($status) {
            'unread'  => 'badge-soft-danger',
            'read'    => 'badge-soft-info',
            'replied' => 'badge-soft-success',
            default   => 'badge-soft-secondary'
        };
    }
?>

<style>
    .group-tab-card {
        border: 1px solid var(--admin-border);
        border-radius: 14px;
        padding: 1rem 1.15rem;
        background: #ffffff;
        text-decoration: none;
        color: var(--admin-text);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.18s ease;
    }
    .group-tab-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        color: var(--admin-text);
    }
    .group-tab-card.active-inbox {
        border-color: #0d9488;
        background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 100%);
        box-shadow: 0 4px 14px rgba(13, 148, 136, 0.14);
    }
    .group-tab-card.active-important {
        border-color: #f59e0b;
        background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
        box-shadow: 0 4px 14px rgba(245, 158, 11, 0.15);
    }
    .group-tab-card.active-spam {
        border-color: #ef4444;
        background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
        box-shadow: 0 4px 14px rgba(239, 68, 68, 0.14);
    }
    .group-tab-card.active-all {
        border-color: #6366f1;
        background: linear-gradient(135deg, #eef2ff 0%, #ffffff 100%);
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.14);
    }
    .star-btn {
        background: transparent;
        border: none;
        padding: 0.25rem;
        font-size: 1.05rem;
        color: #cbd5e1;
        transition: transform 0.15s ease, color 0.15s ease;
    }
    .star-btn:hover {
        transform: scale(1.18);
        color: #fbbf24;
    }
    .star-btn.starred {
        color: #f59e0b;
    }
</style>

<!-- Top Message Group Cards (Important vs Primary Inbox vs Spam) -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="?group=important" class="group-tab-card <?= $groupFilter === 'important' ? 'active-important' : ''; ?>">
            <div>
                <div class="small fw-bold text-uppercase text-muted mb-1">Important</div>
                <div class="h4 fw-extrabold mb-0"><?= number_format($groupCounts['important']); ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">Priority care &amp; job inquiries</div>
            </div>
            <div class="stat-icon" style="width: 44px; height: 44px; background: #fef3c7; color: #d97706;">
                <i class="fas fa-star"></i>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="?group=inbox" class="group-tab-card <?= $groupFilter === 'inbox' ? 'active-inbox' : ''; ?>">
            <div>
                <div class="small fw-bold text-uppercase text-muted mb-1">Primary Inbox</div>
                <div class="h4 fw-extrabold mb-0"><?= number_format($groupCounts['inbox']); ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">Legitimate non-spam messages</div>
            </div>
            <div class="stat-icon" style="width: 44px; height: 44px; background: #ccfbf1; color: #0f766e;">
                <i class="fas fa-inbox"></i>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="?group=spam" class="group-tab-card <?= $groupFilter === 'spam' ? 'active-spam' : ''; ?>">
            <div>
                <div class="small fw-bold text-uppercase text-muted mb-1">Spammed Messages</div>
                <div class="h4 fw-extrabold mb-0 text-danger"><?= number_format($groupCounts['spam']); ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">SEO, marketing &amp; bot solicitations</div>
            </div>
            <div class="stat-icon" style="width: 44px; height: 44px; background: #fee2e2; color: #dc2626;">
                <i class="fas fa-shield-virus"></i>
            </div>
        </a>
    </div>

    <div class="col-sm-6 col-xl-3">
        <a href="?group=all" class="group-tab-card <?= $groupFilter === 'all' ? 'active-all' : ''; ?>">
            <div>
                <div class="small fw-bold text-uppercase text-muted mb-1">All Messages</div>
                <div class="h4 fw-extrabold mb-0"><?= number_format($groupCounts['all']); ?></div>
                <div class="text-muted" style="font-size: 0.75rem;">Combined archive</div>
            </div>
            <div class="stat-icon" style="width: 44px; height: 44px; background: #e0e7ff; color: #4338ca;">
                <i class="fas fa-layer-group"></i>
            </div>
        </a>
    </div>
</div>

<!-- Filter Pills, Smart Auto-Scan & Search Toolbar -->
<div class="card-modern p-3 p-md-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <!-- Status Filter Pills within current Group -->
        <div class="filter-pills">
            <a href="<?= PROOT; ?>admin/contacts?group=<?= urlencode($groupFilter); ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
               class="filter-pill <?= $statusFilter === '' ? 'active' : ''; ?>">
                All in <?= ucfirst($groupFilter); ?> <span class="pill-count"><?= $statusCounts['all']; ?></span>
            </a>
            <?php foreach ($validStatuses as $st): ?>
                <a href="<?= PROOT; ?>admin/contacts?group=<?= urlencode($groupFilter); ?>&status=<?= $st; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="filter-pill <?= $statusFilter === $st ? 'active' : ''; ?>">
                    <?= ucfirst($st); ?> <span class="pill-count"><?= $statusCounts[$st]; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search, Smart Scan & Actions -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="get" action="" class="d-flex gap-2 flex-grow-1">
                <input type="hidden" name="group" value="<?= e($groupFilter); ?>">
                <?php if ($statusFilter !== ''): ?>
                    <input type="hidden" name="status" value="<?= e($statusFilter); ?>">
                <?php endif; ?>
                <div class="input-group input-group-sm" style="min-width: 210px;">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search sender, subject, text..." value="<?= e($searchQuery); ?>">
                    <?php if ($searchQuery !== ''): ?>
                        <a href="<?= PROOT; ?>admin/contacts?group=<?= urlencode($groupFilter); ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-outline-secondary" title="Clear">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-sm btn-teal px-3">Search</button>
            </form>

            <!-- Smart Auto-Scan Button -->
            <form method="post" action="" class="d-inline">
                <button type="submit" name="run_auto_scan" class="btn btn-sm btn-light border fw-semibold" title="Automatically detect SEO/marketing spam and highlight important care inquiries">
                    <i class="fas fa-wand-magic-sparkles text-warning me-1"></i>Auto-Group Spam
                </button>
            </form>

            <?php if ($groupFilter === 'spam' && $groupCounts['spam'] > 0): ?>
                <form method="post" action="" class="d-inline" onsubmit="return confirm('Permanently delete all <?= $groupCounts['spam']; ?> messages in the Spam folder?');">
                    <button type="submit" name="empty_spam" class="btn btn-sm btn-danger fw-semibold">
                        <i class="fas fa-fire-burner me-1"></i>Empty Spam (<?= $groupCounts['spam']; ?>)
                    </button>
                </form>
            <?php elseif ($statusCounts['unread'] > 0 && $groupFilter !== 'spam'): ?>
                <form method="post" action="" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-sm btn-light border fw-semibold">
                        <i class="fas fa-check-double me-1 text-primary"></i>Mark Inbox Read
                    </button>
                </form>
            <?php endif; ?>

            <a href="?group=<?= urlencode($groupFilter); ?>&export=csv<?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
               class="btn btn-sm btn-light border fw-semibold">
                <i class="fas fa-file-csv me-1 text-success"></i>Export CSV
            </a>
        </div>
    </div>
</div>

<!-- Bulk Action Form Wrapping Table -->
<form method="post" action="" id="bulkMessagesForm">
    <div class="card-modern overflow-hidden">
        <!-- Contextual Bulk Action Bar -->
        <div class="px-4 py-2 bg-light border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="selectAllMsgs">
                    <label class="form-check-label small fw-semibold text-secondary" for="selectAllMsgs">
                        Select All (<span id="selectedCountLabel">0</span> selected)
                    </label>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="bulk_action" id="bulkActionSelect" class="form-select form-select-sm" style="width: 210px;">
                    <option value="">-- Bulk Action on Selected --</option>
                    <option value="mark_important">⭐ Mark as Important</option>
                    <option value="unmark_important">Remove from Important</option>
                    <option value="move_spam">🚫 Move to Spam Group</option>
                    <option value="not_spam">✅ Not Spam (Move to Inbox)</option>
                    <option value="mark_read">📩 Mark as Read</option>
                    <option value="delete">🗑️ Delete Permanently</option>
                </select>
                <button type="submit" name="apply_bulk_action" class="btn btn-sm btn-dark px-3" id="applyBulkBtn" disabled>
                    Apply
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th style="width: 38px;"></th>
                        <th style="width: 42px;" title="Mark as Important"><i class="far fa-star text-warning"></i></th>
                        <th>Sender</th>
                        <th>Subject &amp; Message Preview</th>
                        <th>Group / Classification</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $row): ?>
                            <?php
                                $isUnread    = ($row['message_status'] === 'unread');
                                $isImportant = !empty($row['is_important']);
                                $isSpam      = !empty($row['is_spam']);
                            ?>
                            <tr class="<?= $isUnread && !$isSpam ? 'bg-light' : ''; ?>">
                                <td>
                                    <input class="form-check-input msg-row-check" type="checkbox" name="selected_ids[]" value="<?= (int)$row['id']; ?>">
                                </td>
                                <td>
                                    <button type="button" class="star-btn <?= $isImportant ? 'starred' : ''; ?> quick-star-btn"
                                            data-id="<?= (int)$row['id']; ?>"
                                            data-next="<?= $isImportant ? 0 : 1; ?>"
                                            title="<?= $isImportant ? 'Important (Click to unstar)' : 'Mark as Important'; ?>">
                                        <i class="<?= $isImportant ? 'fas' : 'far'; ?> fa-star"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle" style="<?= $isSpam ? 'background:#fee2e2;color:#b91c1c;' : ($isImportant ? 'background:#fef3c7;color:#d97706;' : ''); ?>">
                                            <?= strtoupper(substr(e($row['message_name']), 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="<?= $isUnread ? 'fw-bold text-dark' : 'fw-semibold text-dark'; ?>">
                                                <?= e($row['message_name']); ?>
                                                <span class="text-muted fw-normal small">#<?= (int)$row['id']; ?></span>
                                            </div>
                                            <div class="text-muted small">
                                                <?= e($row['message_email']); ?>
                                                <?= !empty($row['message_phone']) ? ' &bull; ' . e($row['message_phone']) : ''; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="max-width: 310px;">
                                    <div class="<?= $isUnread ? 'fw-bold text-dark' : 'fw-medium text-dark'; ?> small">
                                        <?= e($row['message_subject'] ?: 'General Inquiry'); ?>
                                    </div>
                                    <div class="text-muted small text-truncate">
                                        <?= e($row['message_message']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isSpam): ?>
                                        <span class="badge-soft badge-soft-danger" title="<?= e($row['spam_reason'] ?: 'Flagged as Spam'); ?>">
                                            <i class="fas fa-ban"></i> Spam
                                        </span>
                                        <?php if (!empty($row['spam_reason'])): ?>
                                            <div class="text-muted mt-1" style="font-size: 0.7rem;"><?= e($row['spam_reason']); ?></div>
                                        <?php endif; ?>
                                    <?php elseif ($isImportant): ?>
                                        <span class="badge-soft badge-soft-warning">
                                            <i class="fas fa-star"></i> Important
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-soft badge-soft-primary">
                                            <i class="fas fa-inbox"></i> Primary
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-soft <?= getMsgStatusBadge($row['message_status']); ?>">
                                        <?= ucfirst(e($row['message_status'])); ?>
                                    </span>
                                </td>
                                <td class="small text-muted text-nowrap">
                                    <?= date('M d, Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <!-- View & Reply Modal Trigger -->
                                    <button type="button" class="btn btn-light border btn-action me-1 view-msg-btn"
                                            title="Read & Reply"
                                            data-msg='<?= htmlspecialchars(json_encode([
                                                'id' => $row['id'],
                                                'name' => html_entity_decode((string)$row['message_name'], ENT_QUOTES, 'UTF-8'),
                                                'email' => $row['message_email'],
                                                'phone' => $row['message_phone'] ?: 'Not provided',
                                                'subject' => html_entity_decode((string)($row['message_subject'] ?: 'General Inquiry'), ENT_QUOTES, 'UTF-8'),
                                                'message' => html_entity_decode((string)$row['message_message'], ENT_QUOTES, 'UTF-8'),
                                                'status' => $row['message_status'],
                                                'is_important' => $isImportant ? 1 : 0,
                                                'is_spam' => $isSpam ? 1 : 0,
                                                'spam_reason' => $row['spam_reason'] ?? '',
                                                'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
                                            ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                        <i class="fas fa-envelope-open text-primary"></i>
                                    </button>

                                    <!-- Quick Spam / Not Spam Toggle -->
                                    <?php if ($isSpam): ?>
                                        <button type="button" class="btn btn-light border btn-action me-1 quick-spam-btn"
                                                data-id="<?= (int)$row['id']; ?>"
                                                data-next="0"
                                                title="Not Spam — Restore to Primary Inbox">
                                            <i class="fas fa-rotate-left text-success"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-light border btn-action me-1 quick-spam-btn"
                                                data-id="<?= (int)$row['id']; ?>"
                                                data-next="1"
                                                title="Mark as Spam">
                                            <i class="fas fa-ban text-warning"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Quick Delete -->
                                    <button type="button" class="btn btn-light border btn-action quick-del-btn"
                                            data-id="<?= (int)$row['id']; ?>"
                                            title="Delete Message">
                                        <i class="fas fa-trash-can text-danger"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted mb-2">
                                    <i class="fas <?= $groupFilter === 'spam' ? 'fa-shield-check text-success' : ($groupFilter === 'important' ? 'fa-star text-warning' : 'fa-envelope-open'); ?> fa-2x opacity-50"></i>
                                </div>
                                <div class="fw-semibold">
                                    <?php if ($groupFilter === 'important'): ?>
                                        No messages marked as Important yet
                                    <?php elseif ($groupFilter === 'spam'): ?>
                                        No spam messages in this folder
                                    <?php else: ?>
                                        No contact messages found in this view
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">
                                    Use the star icon to highlight important inquiries or switch tabs above.
                                </div>
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
                    Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= $totalRecords; ?> messages in <?= ucfirst($groupFilter); ?>)
                </span>
                <nav aria-label="Contacts pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?group=<?= urlencode($groupFilter); ?>&page=<?= $page - 1; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>">&laquo; Prev</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?group=<?= urlencode($groupFilter); ?>&page=<?= $i; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?group=<?= urlencode($groupFilter); ?>&page=<?= $page + 1; ?><?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>">Next &raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- Hidden single-action forms triggered by row buttons -->
<form method="post" action="" id="singleImportantForm" class="d-none">
    <input type="hidden" name="toggle_important" value="1">
    <input type="hidden" name="message_id" id="si_msg_id">
    <input type="hidden" name="is_important" id="si_val">
</form>

<form method="post" action="" id="singleSpamForm" class="d-none">
    <input type="hidden" name="toggle_spam" value="1">
    <input type="hidden" name="message_id" id="ss_msg_id">
    <input type="hidden" name="is_spam" id="ss_val">
    <input type="hidden" name="restore_important" id="ss_restore_imp" value="0">
</form>

<form method="post" action="" id="singleDeleteForm" class="d-none">
    <input type="hidden" name="delete_message" value="1">
    <input type="hidden" name="message_id" id="sd_msg_id">
</form>

<!-- Shared Read, Classify & Reply Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold mb-0" id="vm_subject"></h5>
                        <span id="vm_group_badge"></span>
                    </div>
                    <span class="text-muted small">From <strong id="vm_name"></strong> &bull; <span id="vm_created_at"></span></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center p-3 rounded-3 bg-light mb-3 gap-2">
                    <div class="small">
                        <div><strong>Email:</strong> <a href="#" id="vm_email_link"><span id="vm_email"></span></a></div>
                        <div><strong>Phone:</strong> <span id="vm_phone"></span></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Mark / Unmark Important inside Modal -->
                        <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-semibold" id="vm_toggle_imp_btn">
                            <i class="fas fa-star text-warning me-1"></i><span id="vm_imp_label">Mark Important</span>
                        </button>

                        <!-- Move to Spam / Restore from Spam inside Modal -->
                        <button type="button" class="btn btn-sm btn-outline-danger fw-semibold" id="vm_toggle_spam_btn">
                            <i class="fas fa-ban me-1"></i><span id="vm_spam_label">Move to Spam</span>
                        </button>

                        <form method="post" action="" class="d-inline">
                            <input type="hidden" name="message_id" id="vm_status_id">
                            <input type="hidden" name="message_status" value="read">
                            <button type="submit" name="update_message_status" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-check me-1"></i>Mark Read
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="text-uppercase small fw-bold text-muted mb-2">Message Content</h6>
                    <div class="p-3 rounded-3 border bg-white" id="vm_message" style="white-space: pre-wrap; min-height: 90px;"></div>
                </div>

                <!-- Direct Reply Form -->
                <div class="border-top pt-3">
                    <h6 class="fw-bold mb-3"><i class="fas fa-reply text-teal me-2" style="color:#0d9488;"></i>Send Email Reply</h6>
                    <form method="post" action="">
                        <input type="hidden" name="message_id" id="reply_msg_id">
                        <input type="hidden" name="reply_to_email" id="reply_to_email">
                        <input type="hidden" name="reply_to_name" id="reply_to_name">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Subject</label>
                            <input type="text" name="reply_subject" id="reply_subject" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Reply Message</label>
                            <textarea name="reply_body" class="form-control" rows="4" placeholder="Write your response to the sender..." required></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="send_reply" class="btn btn-teal px-4">
                                <i class="fas fa-paper-plane me-1"></i>Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick Star / Important button
    document.querySelectorAll('.quick-star-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('si_msg_id').value = this.dataset.id;
            document.getElementById('si_val').value = this.dataset.next;
            document.getElementById('singleImportantForm').submit();
        });
    });

    // Quick Spam / Not Spam button
    document.querySelectorAll('.quick-spam-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('ss_msg_id').value = this.dataset.id;
            document.getElementById('ss_val').value = this.dataset.next;
            document.getElementById('singleSpamForm').submit();
        });
    });

    // Quick Delete button
    document.querySelectorAll('.quick-del-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Permanently delete message #' + this.dataset.id + '?')) {
                document.getElementById('sd_msg_id').value = this.dataset.id;
                document.getElementById('singleDeleteForm').submit();
            }
        });
    });

    // Bulk Checkbox Selection logic
    const selectAll = document.getElementById('selectAllMsgs');
    const rowChecks = document.querySelectorAll('.msg-row-check');
    const countLabel = document.getElementById('selectedCountLabel');
    const applyBtn = document.getElementById('applyBulkBtn');
    const actionSelect = document.getElementById('bulkActionSelect');

    function updateBulkState() {
        let checkedCount = 0;
        rowChecks.forEach(c => { if (c.checked) checkedCount++; });
        if (countLabel) countLabel.textContent = checkedCount;
        if (applyBtn) applyBtn.disabled = (checkedCount === 0 || !actionSelect.value);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            rowChecks.forEach(c => { c.checked = selectAll.checked; });
            updateBulkState();
        });
    }
    rowChecks.forEach(c => c.addEventListener('change', updateBulkState));
    if (actionSelect) actionSelect.addEventListener('change', updateBulkState);

    // View Message Modal
    const viewModal = new bootstrap.Modal(document.getElementById('viewMessageModal'));
    let activeMsgData = null;

    document.querySelectorAll('.view-msg-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = JSON.parse(this.getAttribute('data-msg'));
            activeMsgData = d;

            document.getElementById('vm_subject').textContent = d.subject;
            document.getElementById('vm_name').textContent = d.name;
            document.getElementById('vm_created_at').textContent = d.created_at;
            document.getElementById('vm_email').textContent = d.email;
            document.getElementById('vm_email_link').href = 'mailto:' + d.email;
            document.getElementById('vm_phone').textContent = d.phone;
            document.getElementById('vm_message').textContent = d.message;

            document.getElementById('vm_status_id').value = d.id;
            document.getElementById('reply_msg_id').value = d.id;
            document.getElementById('reply_to_email').value = d.email;
            document.getElementById('reply_to_name').value = d.name;
            document.getElementById('reply_subject').value = 'Re: ' + d.subject + ' — Care That Feels Like Home';

            // Badge & buttons state
            const badgeEl = document.getElementById('vm_group_badge');
            if (d.is_spam) {
                badgeEl.innerHTML = '<span class="badge-soft badge-soft-danger"><i class="fas fa-ban"></i> Spam (' + (d.spam_reason || 'Flagged') + ')</span>';
            } else if (d.is_important) {
                badgeEl.innerHTML = '<span class="badge-soft badge-soft-warning"><i class="fas fa-star"></i> Important</span>';
            } else {
                badgeEl.innerHTML = '<span class="badge-soft badge-soft-primary">Primary Inbox</span>';
            }

            document.getElementById('vm_imp_label').textContent = d.is_important ? 'Unmark Important' : 'Mark Important';
            document.getElementById('vm_spam_label').textContent = d.is_spam ? 'Not Spam (Restore)' : 'Move to Spam';

            viewModal.show();
        });
    });

    document.getElementById('vm_toggle_imp_btn').addEventListener('click', function() {
        if (!activeMsgData) return;
        document.getElementById('si_msg_id').value = activeMsgData.id;
        document.getElementById('si_val').value = activeMsgData.is_important ? 0 : 1;
        document.getElementById('singleImportantForm').submit();
    });

    document.getElementById('vm_toggle_spam_btn').addEventListener('click', function() {
        if (!activeMsgData) return;
        document.getElementById('ss_msg_id').value = activeMsgData.id;
        document.getElementById('ss_val').value = activeMsgData.is_spam ? 0 : 1;
        document.getElementById('singleSpamForm').submit();
    });
});
</script>

<?php include 'includes/footer.php'; ?>
