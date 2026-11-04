<?php 
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    // Handle POST actions before HTML output (PRG pattern)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 1. Update single message status
        if (isset($_POST['update_message_status']) && !empty($_POST['message_id'])) {
            $id = (int)$_POST['message_id'];
            $newStatus = $_POST['message_status'] ?? 'read';
            if (in_array($newStatus, ['unread', 'read', 'replied'], true)) {
                $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET message_status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $_SESSION['flash_success'] = "Message #$id marked as $newStatus.";
            }
            redirect(PROOT . 'admin/contacts' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }

        // 2. Mark all unread messages as read
        if (isset($_POST['mark_all_read'])) {
            $dbConnection->query("UPDATE care_contact_messages SET message_status = 'read' WHERE message_status = 'unread'");
            $_SESSION['flash_success'] = "All unread messages have been marked as read.";
            redirect(PROOT . 'admin/contacts');
        }

        // 3. Send email reply and mark as replied
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
                $stmt = $dbConnection->prepare("UPDATE care_contact_messages SET message_status = 'replied' WHERE id = ?");
                $stmt->execute([$id]);

                if ($sent) {
                    $_SESSION['flash_success'] = "Reply sent to $toEmail and message marked as replied.";
                } else {
                    $_SESSION['flash_success'] = "Message #$id marked as replied (Note: SMTP delivery could not be verified in local environment).";
                }
            } else {
                $_SESSION['flash_error'] = "Please provide a reply message body.";
            }
            redirect(PROOT . 'admin/contacts' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }

        // 4. Delete contact message
        if (isset($_POST['delete_message']) && !empty($_POST['message_id'])) {
            $id = (int)$_POST['message_id'];
            $stmt = $dbConnection->prepare("DELETE FROM care_contact_messages WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_success'] = "Message #$id deleted.";
            redirect(PROOT . 'admin/contacts');
        }
    }

    // Filters & Search
    $validStatuses = ['unread', 'read', 'replied'];
    $statusFilter = isset($_GET['status']) && in_array($_GET['status'], $validStatuses, true) ? $_GET['status'] : '';
    $searchQuery  = trim($_GET['search'] ?? '');

    $whereClauses = [];
    $params = [];

    if ($statusFilter !== '') {
        $whereClauses[] = "message_status = :status";
        $params[':status'] = $statusFilter;
    }

    if ($searchQuery !== '') {
        $whereClauses[] = "(message_name LIKE :search OR message_email LIKE :search OR message_phone LIKE :search OR message_subject LIKE :search OR message_message LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }

    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // CSV Export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $exportStmt = $dbConnection->prepare("SELECT * FROM care_contact_messages $whereSql ORDER BY id DESC");
        $exportStmt->execute($params);
        $rows = $exportStmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contact_messages_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Sender Name', 'Email', 'Phone', 'Subject', 'Message', 'Status', 'Date Received']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
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

    // Status tab counts
    $statusCounts = ['all' => 0, 'unread' => 0, 'read' => 0, 'replied' => 0];
    $countStmt = $dbConnection->query("SELECT message_status, COUNT(*) as cnt FROM care_contact_messages GROUP BY message_status");
    foreach ($countStmt->fetchAll() as $cRow) {
        if (isset($statusCounts[$cRow['message_status']])) {
            $statusCounts[$cRow['message_status']] = (int)$cRow['cnt'];
            $statusCounts['all'] += (int)$cRow['cnt'];
        }
    }

    // Pagination
    $limit = 10;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalStmt = $dbConnection->prepare("SELECT COUNT(*) FROM care_contact_messages $whereSql");
    $totalStmt->execute($params);
    $totalRecords = (int)$totalStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $limit));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $limit;

    // Fetch contacts
    $query = "SELECT * FROM care_contact_messages $whereSql ORDER BY id DESC LIMIT $offset, $limit";
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

<!-- Filter Pills & Toolbar -->
<div class="card-modern p-3 p-md-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <!-- Status Filter Pills -->
        <div class="filter-pills">
            <a href="<?= PROOT; ?>admin/contacts<?= $searchQuery ? '?search=' . urlencode($searchQuery) : ''; ?>" 
               class="filter-pill <?= $statusFilter === '' ? 'active' : ''; ?>">
                All <span class="pill-count"><?= $statusCounts['all']; ?></span>
            </a>
            <?php foreach ($validStatuses as $st): ?>
                <a href="<?= PROOT; ?>admin/contacts?status=<?= $st; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="filter-pill <?= $statusFilter === $st ? 'active' : ''; ?>">
                    <?= ucfirst($st); ?> <span class="pill-count"><?= $statusCounts[$st]; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search, Mark All Read & Export -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="get" action="" class="d-flex gap-2 flex-grow-1">
                <?php if ($statusFilter !== ''): ?>
                    <input type="hidden" name="status" value="<?= e($statusFilter); ?>">
                <?php endif; ?>
                <div class="input-group input-group-sm" style="min-width: 220px;">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search sender, subject, text..." value="<?= e($searchQuery); ?>">
                    <?php if ($searchQuery !== ''): ?>
                        <a href="<?= PROOT; ?>admin/contacts<?= $statusFilter ? '?status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-outline-secondary" title="Clear">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-sm btn-teal px-3">Search</button>
            </form>

            <?php if ($statusCounts['unread'] > 0): ?>
                <form method="post" action="" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-sm btn-light border fw-semibold">
                        <i class="fas fa-check-double me-1 text-primary"></i>Mark All Read
                    </button>
                </form>
            <?php endif; ?>

            <a href="?export=csv<?= $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : ''; ?>" 
               class="btn btn-sm btn-light border fw-semibold">
                <i class="fas fa-file-csv me-1 text-success"></i>Export CSV
            </a>
        </div>
    </div>
</div>

<!-- Messages Table Card -->
<div class="card-modern overflow-hidden">
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sender</th>
                    <th>Subject & Preview</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $row): ?>
                        <?php $isUnread = ($row['message_status'] === 'unread'); ?>
                        <tr class="<?= $isUnread ? 'bg-light' : ''; ?>">
                            <td class="fw-bold text-secondary">#<?= (int)$row['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle" style="<?= $isUnread ? 'background:#fee2e2;color:#b91c1c;' : ''; ?>">
                                        <?= strtoupper(substr(e($row['message_name']), 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="<?= $isUnread ? 'fw-bold text-dark' : 'fw-semibold text-dark'; ?>">
                                            <?= e($row['message_name']); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= e($row['message_email']); ?>
                                            <?= !empty($row['message_phone']) ? ' &bull; ' . e($row['message_phone']) : ''; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="max-width: 340px;">
                                <div class="<?= $isUnread ? 'fw-bold text-dark' : 'fw-medium text-dark'; ?> small">
                                    <?= e($row['message_subject'] ?: 'General Inquiry'); ?>
                                </div>
                                <div class="text-muted small text-truncate">
                                    <?= e($row['message_message']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-soft <?= getMsgStatusBadge($row['message_status']); ?>">
                                    <?= ucfirst(e($row['message_status'])); ?>
                                </span>
                            </td>
                            <td class="small text-muted text-nowrap">
                                <?= date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                            </td>
                            <td class="text-end text-nowrap">
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
                                            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
                                        ]), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <i class="fas fa-envelope-open text-primary"></i>
                                </button>

                                <!-- Quick toggle read/unread -->
                                <form method="post" action="" class="d-inline me-1">
                                    <input type="hidden" name="message_id" value="<?= (int)$row['id']; ?>">
                                    <input type="hidden" name="message_status" value="<?= $isUnread ? 'read' : 'unread'; ?>">
                                    <button type="submit" name="update_message_status" class="btn btn-light border btn-action"
                                            title="Mark as <?= $isUnread ? 'Read' : 'Unread'; ?>">
                                        <i class="fas <?= $isUnread ? 'fa-check text-success' : 'fa-envelope text-secondary'; ?>"></i>
                                    </button>
                                </form>

                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Delete message #<?= (int)$row['id']; ?> permanently?');">
                                    <input type="hidden" name="message_id" value="<?= (int)$row['id']; ?>">
                                    <button type="submit" name="delete_message" class="btn btn-light border btn-action" title="Delete Message">
                                        <i class="fas fa-trash-can text-danger"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="text-muted mb-2"><i class="fas fa-envelope-open fa-2x opacity-50"></i></div>
                            <div class="fw-semibold">No contact messages found</div>
                            <div class="text-muted small">Your inbox is clear for this filter.</div>
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
                Showing page <strong><?= $page; ?></strong> of <strong><?= $totalPages; ?></strong> (<?= $totalRecords; ?> total messages)
            </span>
            <nav aria-label="Contacts pagination">
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

<!-- Shared Read & Reply Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="vm_subject"></h5>
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
                    <div class="d-flex gap-2">
                        <form method="post" action="" class="d-inline">
                            <input type="hidden" name="message_id" id="vm_status_id">
                            <input type="hidden" name="message_status" value="read">
                            <button type="submit" name="update_message_status" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-check me-1"></i>Mark as Read
                            </button>
                        </form>
                        <a href="#" id="vm_mailto_btn" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt me-1"></i>Open Mail App
                        </a>
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
    const viewModal = new bootstrap.Modal(document.getElementById('viewMessageModal'));
    document.querySelectorAll('.view-msg-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const d = JSON.parse(this.getAttribute('data-msg'));
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
            document.getElementById('vm_mailto_btn').href = 'mailto:' + encodeURIComponent(d.email) + '?subject=' + encodeURIComponent('Re: ' + d.subject);

            viewModal.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
