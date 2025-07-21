<?php 
    include_once ("../system/DatabaseConnector.php");
    if (!admin_is_logged_in()) {
        admin_login_redirect();
    }

    include 'includes/header.php';
    include 'includes/sidebar.php'; 

    // Get counts for dashboard
    $contactsQuery = "SELECT COUNT(*) as count FROM care_contact_messages";
    $applicationsQuery = "SELECT COUNT(*) as count FROM care_applications";
    $requestsQuery = "SELECT COUNT(*) as count FROM care_service_requests";

    $contactsResult =$dbConnection->query($contactsQuery);
    $applicationsResult =$dbConnection->query($applicationsQuery);
    $requestsResult =$dbConnection->query($requestsQuery);

    $contactsCount = ($contactsResult) ? $contactsResult->rowCount() : 0;
    $applicationsCount = ($applicationsResult) ? $applicationsResult->rowCount() : 0;
    $requestsCount = ($requestsResult) ? $requestsResult->rowCount() : 0;

    // Get status counts for applications
    $appStatusQuery = "SELECT application_status, COUNT(*) as count FROM care_applications GROUP BY application_status";
    $appStatusResult =$dbConnection->query($appStatusQuery);
    $appStatus = [];
    if ($appStatusResult) {
        while ($row = $appStatusResult->fetchAll()) {
            $appStatus[$row['application_status']] = $row['count'];
        }
    }

// Get status counts for requests
$reqStatusQuery = "SELECT request_status, COUNT(*) as count FROM care_service_requests GROUP BY request_status";
$reqStatusResult =$dbConnection->query($reqStatusQuery);
$reqStatus = [];
if ($reqStatusResult) {
    while ($row = $reqStatusResult->fetchAll()) {
        $reqStatus[$row['request_status']] = $row['count'];
    }
}
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Contacts</h6>
                        <h1 class="display-4"><?php echo $contactsCount; ?></h1>
                    </div>
                    <i class="fas fa-address-book fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="contacts.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-arrow-circle-right text-white"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Applications</h6>
                        <h1 class="display-4"><?php echo $applicationsCount; ?></h1>
                    </div>
                    <i class="fas fa-file-alt fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="applications.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-arrow-circle-right text-white"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Requests</h6>
                        <h1 class="display-4"><?php echo $requestsCount; ?></h1>
                    </div>
                    <i class="fas fa-tasks fa-3x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="requests.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-arrow-circle-right text-white"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Application Status</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Pending</td>
                                <td><?php echo isset($appStatus['pending']) ? $appStatus['pending'] : 0; ?></td>
                            </tr>
                            <tr>
                                <td>Reviewed</td>
                                <td><?php echo isset($appStatus['reviewed']) ? $appStatus['reviewed'] : 0; ?></td>
                            </tr>
                            <tr>
                                <td>Approved</td>
                                <td><?php echo isset($appStatus['approved']) ? $appStatus['approved'] : 0; ?></td>
                            </tr>
                            <tr>
                                <td>Rejected</td>
                                <td><?php echo isset($appStatus['rejected']) ? $appStatus['rejected'] : 0; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Request Status</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Pending</td>
                                <td><?php echo isset($reqStatus['pending']) ? $reqStatus['pending'] : 0; ?></td>
                            </tr>
                            <tr>
                                <td>Approved</td>
                                <td><?php echo isset($reqStatus['approved']) ? $reqStatus['approved'] : 0; ?></td>
                            </tr>
                            <tr>
                                <td>Assigned</td>
                                <td><?php echo isset($reqStatus['assigned']) ? $reqStatus['assigned'] : 0; ?></td>
                            </tr>
                            <tr>
                                <td>Completed</td>
                                <td><?php echo isset($reqStatus['completed']) ? $reqStatus['completed'] : 0; ?></td>
                            </tr>
                            <tr>
                                <td>Cancelled</td>
                                <td><?php echo isset($reqStatus['cancelled']) ? $reqStatus['cancelled'] : 0; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>