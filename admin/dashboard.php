<?php 
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    $pageTitle = "Dashboard";
    include 'includes/header.php';
    include 'includes/sidebar.php'; 

    // Accurate counts using fetchColumn() (fixes previous rowCount() on SELECT COUNT(*) bug)
    $requestsCount     = (int)$dbConnection->query("SELECT COUNT(*) FROM care_service_requests")->fetchColumn();
    $applicationsCount = (int)$dbConnection->query("SELECT COUNT(*) FROM care_applications")->fetchColumn();
    $contactsCount     = (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE is_spam = 0")->fetchColumn();
    $spamContactsCount = (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE is_spam = 1")->fetchColumn();
    $caregiversCount   = (int)$dbConnection->query("SELECT COUNT(*) FROM care_users WHERE user_type = 'caregiver'")->fetchColumn();
    $assignmentsCount  = (int)$dbConnection->query("SELECT COUNT(*) FROM care_assignments WHERE assignment_status = 'active'")->fetchColumn();
    $onboardingCount   = (int)$dbConnection->query("SELECT COUNT(*) FROM care_staff_onboarding")->fetchColumn();
    $pendingOnboarding = (int)$dbConnection->query("SELECT COUNT(*) FROM care_staff_onboarding WHERE onboarding_status = 'pending'")->fetchColumn();

    // Status counts for applications
    $appStatus = ['pending' => 0, 'reviewed' => 0, 'approved' => 0, 'rejected' => 0];
    $appStatusStmt = $dbConnection->query("SELECT application_status, COUNT(*) as count FROM care_applications GROUP BY application_status");
    foreach ($appStatusStmt->fetchAll() as $row) {
        if (isset($appStatus[$row['application_status']])) {
            $appStatus[$row['application_status']] = (int)$row['count'];
        }
    }

    // Status counts for requests
    $reqStatus = ['pending' => 0, 'approved' => 0, 'assigned' => 0, 'completed' => 0, 'cancelled' => 0];
    $reqStatusStmt = $dbConnection->query("SELECT request_status, COUNT(*) as count FROM care_service_requests GROUP BY request_status");
    foreach ($reqStatusStmt->fetchAll() as $row) {
        if (isset($reqStatus[$row['request_status']])) {
            $reqStatus[$row['request_status']] = (int)$row['count'];
        }
    }

    // Status counts for legitimate contact messages
    $msgStatus = ['unread' => 0, 'read' => 0, 'replied' => 0];
    $msgStatusStmt = $dbConnection->query("SELECT message_status, COUNT(*) as count FROM care_contact_messages WHERE is_spam = 0 GROUP BY message_status");
    foreach ($msgStatusStmt->fetchAll() as $row) {
        if (isset($msgStatus[$row['message_status']])) {
            $msgStatus[$row['message_status']] = (int)$row['count'];
        }
    }

    // Fetch 5 most recent service requests
    $recentRequests = $dbConnection->query("SELECT * FROM care_service_requests ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // Fetch 5 most recent applications
    $recentApplications = $dbConnection->query("SELECT * FROM care_applications ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // Fetch 5 most recent legitimate contact messages (prioritizing important)
    $recentMessages = $dbConnection->query("SELECT * FROM care_contact_messages WHERE is_spam = 0 ORDER BY is_important DESC, created_at DESC LIMIT 5")->fetchAll();

    function reqBadgeClass($status) {
        return match($status) {
            'pending'   => 'badge-soft-warning',
            'approved'  => 'badge-soft-info',
            'assigned'  => 'badge-soft-indigo',
            'completed' => 'badge-soft-success',
            'cancelled' => 'badge-soft-danger',
            default     => 'badge-soft-secondary'
        };
    }

    function appBadgeClass($status) {
        return match($status) {
            'pending'  => 'badge-soft-warning',
            'reviewed' => 'badge-soft-info',
            'approved' => 'badge-soft-success',
            'rejected' => 'badge-soft-danger',
            default    => 'badge-soft-secondary'
        };
    }
?>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card-modern stat-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="stat-label mb-1">Service Requests</div>
                    <div class="stat-value"><?= number_format($requestsCount); ?></div>
                </div>
                <div class="stat-icon" style="background: #ccfbf1; color: #0f766e;">
                    <i class="fas fa-hand-holding-medical"></i>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between pt-2 border-top small">
                <span class="text-muted">
                    <strong class="text-warning-emphasis"><?= $reqStatus['pending']; ?></strong> awaiting review
                </span>
                <a href="<?= PROOT; ?>admin/requests" class="text-decoration-none fw-semibold" style="color: #0d9488;">
                    Manage <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-modern stat-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="stat-label mb-1">Applications</div>
                    <div class="stat-value"><?= number_format($applicationsCount); ?></div>
                </div>
                <div class="stat-icon" style="background: #e0e7ff; color: #4338ca;">
                    <i class="fas fa-user-nurse"></i>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between pt-2 border-top small">
                <span class="text-muted">
                    <strong class="text-primary"><?= $appStatus['pending']; ?></strong> pending vetting
                </span>
                <a href="<?= PROOT; ?>admin/applications" class="text-decoration-none fw-semibold" style="color: #4338ca;">
                    Review <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-modern stat-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="stat-label mb-1">Contact Inquiries</div>
                    <div class="stat-value"><?= number_format($contactsCount); ?></div>
                </div>
                <div class="stat-icon" style="background: #fef3c7; color: #b45309;">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between pt-2 border-top small">
                <span class="text-muted">
                    <strong class="text-danger"><?= $msgStatus['unread']; ?></strong> unread &bull; <span class="text-secondary"><?= $spamContactsCount; ?> spam</span>
                </span>
                <a href="<?= PROOT; ?>admin/contacts" class="text-decoration-none fw-semibold" style="color: #b45309;">
                    Open Inbox <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-modern stat-card h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="stat-label mb-1">Caregivers & Staff</div>
                    <div class="stat-value"><?= number_format($caregiversCount); ?></div>
                </div>
                <div class="stat-icon" style="background: #dcfce7; color: #15803d;">
                    <i class="fas fa-users-gear"></i>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between pt-2 border-top small">
                <span class="text-muted">
                    <strong class="text-success"><?= $assignmentsCount; ?></strong> active assignments
                </span>
                <a href="<?= PROOT; ?>admin/caregivers" class="text-decoration-none fw-semibold" style="color: #15803d;">
                    Directory <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Staff Onboarding Quick Access Bar -->
<div class="card-modern p-3 px-4 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3" style="border-left: 4px solid #0d9488;">
    <div class="d-flex align-items-center gap-3">
        <div class="stat-icon" style="width: 42px; height: 42px; background: #ccfbf1; color: #0f766e;">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0">Encrypted Staff Onboarding Vault (13-Point Packet)</h6>
            <div class="text-muted small">
                <strong><?= number_format($onboardingCount); ?></strong> encrypted submissions &bull;
                <strong class="text-warning-emphasis"><?= number_format($pendingOnboarding); ?></strong> awaiting HR verification &bull; AES-256-GCM Protected
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= PROOT; ?>onboarding" target="_blank" class="btn btn-sm btn-light border fw-semibold">
            <i class="fas fa-lock me-1 text-success"></i>View Portal Gate
        </a>
        <a href="<?= PROOT; ?>admin/onboarding" class="btn btn-sm btn-teal">
            Manage Vault &amp; Invites <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
</div>

<!-- Analytics Charts Row -->
<div class="row g-4 mb-4">
    <!-- Service Requests Breakdown -->
    <div class="col-lg-6">
        <div class="card-modern h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0">Service Requests Pipeline</h6>
                    <span class="text-muted small">Distribution by fulfillment status</span>
                </div>
                <a href="<?= PROOT; ?>admin/requests" class="btn btn-sm btn-light border">View All</a>
            </div>
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div style="max-height: 220px; position: relative;">
                        <canvas id="requestsChart"></canvas>
                    </div>
                </div>
                <div class="col-sm-6 mt-3 mt-sm-0">
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><i class="fas fa-circle me-2" style="color: #f59e0b;"></i>Pending</span>
                            <span class="fw-bold"><?= $reqStatus['pending']; ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><i class="fas fa-circle me-2" style="color: #0ea5e9;"></i>Approved</span>
                            <span class="fw-bold"><?= $reqStatus['approved']; ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><i class="fas fa-circle me-2" style="color: #6366f1;"></i>Assigned</span>
                            <span class="fw-bold"><?= $reqStatus['assigned']; ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><i class="fas fa-circle me-2" style="color: #10b981;"></i>Completed</span>
                            <span class="fw-bold"><?= $reqStatus['completed']; ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2">
                            <span><i class="fas fa-circle me-2" style="color: #ef4444;"></i>Cancelled</span>
                            <span class="fw-bold"><?= $reqStatus['cancelled']; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Caregiver Applications Pipeline -->
    <div class="col-lg-6">
        <div class="card-modern h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0">Caregiver Applications Pipeline</h6>
                    <span class="text-muted small">Applicant screening & onboarding status</span>
                </div>
                <a href="<?= PROOT; ?>admin/applications" class="btn btn-sm btn-light border">View All</a>
            </div>
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div style="max-height: 220px; position: relative;">
                        <canvas id="applicationsChart"></canvas>
                    </div>
                </div>
                <div class="col-sm-6 mt-3 mt-sm-0">
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><i class="fas fa-circle me-2" style="color: #f59e0b;"></i>Pending Review</span>
                            <span class="fw-bold"><?= $appStatus['pending']; ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><i class="fas fa-circle me-2" style="color: #0ea5e9;"></i>Reviewed</span>
                            <span class="fw-bold"><?= $appStatus['reviewed']; ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span><i class="fas fa-circle me-2" style="color: #10b981;"></i>Approved</span>
                            <span class="fw-bold"><?= $appStatus['approved']; ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-2">
                            <span><i class="fas fa-circle me-2" style="color: #ef4444;"></i>Rejected</span>
                            <span class="fw-bold"><?= $appStatus['rejected']; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Tables -->
<div class="row g-4">
    <!-- Recent Service Requests -->
    <div class="col-xl-7">
        <div class="card-modern overflow-hidden h-100">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0">Recent Service Requests</h6>
                    <span class="text-muted small">Latest client care requests submitted</span>
                </div>
                <a href="<?= PROOT; ?>admin/requests" class="btn btn-sm btn-teal">Manage Requests</a>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Service Type</th>
                            <th>Start Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentRequests)): ?>
                            <?php foreach ($recentRequests as $req): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($req['request_name']); ?></div>
                                        <div class="text-muted small"><?= e($req['request_phone']); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?= e($req['request_service_type']); ?>">
                                            <?= e($req['request_service_type']); ?>
                                        </div>
                                        <span class="text-muted small">For: <?= e($req['request_care_for']); ?> (<?= e($req['request_frequency']); ?>)</span>
                                    </td>
                                    <td class="small text-nowrap"><?= date('M d, Y', strtotime($req['request_start_date'])); ?></td>
                                    <td>
                                        <span class="badge-soft <?= reqBadgeClass($req['request_status']); ?>">
                                            <?= ucfirst(e($req['request_status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No service requests yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Latest Contact Messages -->
    <div class="col-xl-5">
        <div class="card-modern overflow-hidden h-100">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0">Latest Contact Inquiries</h6>
                    <span class="text-muted small">Recent messages from the website</span>
                </div>
                <a href="<?= PROOT; ?>admin/contacts" class="btn btn-sm btn-light border">Open Inbox</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if (!empty($recentMessages)): ?>
                    <?php foreach ($recentMessages as $msg): ?>
                        <a href="<?= PROOT; ?>admin/contacts?search=<?= urlencode($msg['message_email']); ?>" class="list-group-item list-group-item-action px-4 py-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($msg['message_status'] === 'unread'): ?>
                                        <span class="badge bg-danger rounded-circle p-1" title="Unread"></span>
                                    <?php endif; ?>
                                    <span class="fw-semibold text-dark small"><?= e($msg['message_name']); ?></span>
                                </div>
                                <span class="text-muted" style="font-size: 0.74rem;"><?= date('M d, Y', strtotime($msg['created_at'])); ?></span>
                            </div>
                            <div class="small fw-medium text-secondary mb-1"><?= e($msg['message_subject'] ?: 'General Inquiry'); ?></div>
                            <div class="text-muted small text-truncate"><?= e($msg['message_message']); ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">No contact inquiries found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Caregiver Applications -->
    <div class="col-12">
        <div class="card-modern overflow-hidden">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0">Recent Caregiver Applications</h6>
                    <span class="text-muted small">Newest candidates applying to join the care team</span>
                </div>
                <a href="<?= PROOT; ?>admin/applications" class="btn btn-sm btn-light border">All Applications</a>
            </div>
            <div class="table-responsive">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Contact</th>
                            <th>Experience</th>
                            <th>Preferred Service</th>
                            <th>Availability</th>
                            <th>Applied On</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentApplications)): ?>
                            <?php foreach ($recentApplications as $app): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($app['application_name']); ?></td>
                                    <td>
                                        <div class="small"><?= e($app['application_email']); ?></div>
                                        <div class="text-muted small"><?= e($app['application_phone']); ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= e($app['application_experience']); ?></span></td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 240px;" title="<?= e($app['application_service_type']); ?>">
                                            <?= e($app['application_service_type']); ?>
                                        </div>
                                    </td>
                                    <td><?= e($app['application_availability']); ?></td>
                                    <td class="small text-nowrap"><?= date('M d, Y', strtotime($app['created_at'])); ?></td>
                                    <td>
                                        <span class="badge-soft <?= appBadgeClass($app['application_status']); ?>">
                                            <?= ucfirst(e($app['application_status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No applications found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reqData = [
        <?= $reqStatus['pending']; ?>,
        <?= $reqStatus['approved']; ?>,
        <?= $reqStatus['assigned']; ?>,
        <?= $reqStatus['completed']; ?>,
        <?= $reqStatus['cancelled']; ?>
    ];
    const reqTotal = reqData.reduce((a, b) => a + b, 0);

    new Chart(document.getElementById('requestsChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Approved', 'Assigned', 'Completed', 'Cancelled'],
            datasets: [{
                data: reqTotal > 0 ? reqData : [1],
                backgroundColor: reqTotal > 0 ? ['#f59e0b', '#0ea5e9', '#6366f1', '#10b981', '#ef4444'] : ['#e2e8f0'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: reqTotal > 0 }
            }
        }
    });

    const appData = [
        <?= $appStatus['pending']; ?>,
        <?= $appStatus['reviewed']; ?>,
        <?= $appStatus['approved']; ?>,
        <?= $appStatus['rejected']; ?>
    ];
    const appTotal = appData.reduce((a, b) => a + b, 0);

    new Chart(document.getElementById('applicationsChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Reviewed', 'Approved', 'Rejected'],
            datasets: [{
                data: appTotal > 0 ? appData : [1],
                backgroundColor: appTotal > 0 ? ['#f59e0b', '#0ea5e9', '#10b981', '#ef4444'] : ['#e2e8f0'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: appTotal > 0 }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
