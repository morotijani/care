<?php 
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }
    include 'includes/header.php';
    include 'includes/sidebar.php';

    // Process status update
    if (isset($_POST['update_status']) && isset($_POST['request_id'])) {
        $requestId = $_POST['request_id'];
        $newStatus = $_POST['status'];
        
        $updateQuery = "UPDATE care_service_requests SET status = ? WHERE id = ?";
        $stmt = $dbConnection->prepare($updateQuery);
        $stmt->bind_param("si", $newStatus, $requestId);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Status updated successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error updating status: " . $dbConnection->error . "</div>";
        }
        
        $stmt->close();
    }

    // Pagination setup
    $limit = 10; // Records per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Filter by status if provided
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
    $whereClause = $statusFilter ? "WHERE status = '$statusFilter'" : "";

    // Get total records
    $totalQuery = "SELECT COUNT(*) as total FROM care_service_requests $whereClause";
    $totalResult = $dbConnection->query($totalQuery);
    $totalRecords = $totalResult->fetchAll()[0]['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Get requests with pagination and filtering
    $query = "SELECT * FROM care_service_requests $whereClause ORDER BY id DESC LIMIT $offset, $limit";
    $result = $dbConnection->query($query);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Requests</h5>
        <div class="d-flex">
            <div class="me-2">
                <select id="statusFilter" class="form-select" onchange="window.location.href='?status='+this.value">
                    <option value="" <?php echo $statusFilter == '' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="assigned" <?php echo $statusFilter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                    <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <input type="text" id="searchInput" class="form-control" placeholder="Search requests...">
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Request Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->rowCount() > 0): ?>
                        <?php foreach ($result->fetchAll() as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['request_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['request_email']); ?></td>
                                <td><?php echo htmlspecialchars($row['request_phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['request_service_type']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $row['request_status'] == 'pending' ? 'warning' : 
                                            ($row['request_status'] == 'approved' ? 'info' : 
                                                ($row['request_status'] == 'assigned' ? 'primary' : 
                                                    ($row['request_status'] == 'completed' ? 'success' : 'danger'))); 
                                    ?>">
                                        <?php echo ucfirst($row['request_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $row['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $row['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <!-- Details Modal -->
                                    <div class="modal fade" id="detailsModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Request Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($row['request_name']); ?></p>
                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['request_email']); ?></p>
                                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['request_phone']); ?></p>
                                                            <p><strong>Care for:</strong> <?php echo htmlspecialchars($row['request_care_for']); ?></p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Request Type:</strong> <?php echo htmlspecialchars($row['request_service_type']); ?></p>
                                                            <p><strong>Request Start Date:</strong> <?php echo $row['request_start_date']; ?></p>
                                                            <p><strong>Frequency:</strong> <?php echo $row['request_frequency']; ?></p>
                                                            <p><strong>Hours:</strong> <?php echo $row['request_hours']; ?></p>
                                                            <p><strong>Status:</strong> <?php echo ucfirst($row['request_status']); ?></p>
                                                            <p><strong>Date Requested:</strong> <?php echo date('M d, Y', strtotime($row['created_at'])); ?></p>
                                                            <?php if (!empty($row['assigned_to'])): ?>
                                                                <p><strong>Assigned To:</strong> <?php echo htmlspecialchars($row['assigned_to']); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <h6>Request Special Requirement</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($row['request_special_requirements'])); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Update Modal -->
                                    <div class="modal fade" id="statusModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Request Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="status" class="form-label">Status</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="pending" <?php echo $row['request_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                <option value="approved" <?php echo $row['request_status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                                <option value="assigned" <?php echo $row['request_status'] == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                                                <option value="completed" <?php echo $row['request_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                <option value="cancelled" <?php echo $row['request_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3" id="assignedToField<?php echo $row['id']; ?>" style="display: <?php echo $row['request_status'] == 'assigned' ? 'block' : 'none'; ?>">
                                                            <label for="assigned_to" class="form-label">Assigned To</label>
                                                            <input type="text" name="assigned_to" class="form-control" value="<?php echo htmlspecialchars($row['assigned_to']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No requests found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $statusFilter ? '&status='.$statusFilter : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $statusFilter ? '&status='.$statusFilter : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $statusFilter ? '&status='.$statusFilter : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const input = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
});

// Show/hide assigned to field based on status
document.querySelectorAll('select[name="status"]').forEach(select => {
    select.addEventListener('change', function() {
        const requestId = this.closest('form').querySelector('input[name="request_id"]').value;
        const assignedToField = document.getElementById('assignedToField' + requestId);
        
        if (this.value === 'assigned') {
            assignedToField.style.display = 'block';
        } else {
            assignedToField.style.display = 'none';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>