<?php

////////////////////////////////////////////////////// FOR USER

// Sessions For login
function userLogin($user_id) {
	$_SESSION['LVNUser'] = $user_id;
    $_SESSION['flash_success'] = 'You are now logged in!';
    redirect(PROOT . 'app/');
}

function user_is_logged_in() {
	return !empty($_SESSION['LVNUser']);
}

// Redirect user if !logged in
function user_login_redirect($url = 'auth/signin') {
	$_SESSION['flash_error'] = 'You must be logged in to access that page.';
	redirect(PROOT . $url);
}

// Safe HTML output that avoids double-encoding strings saved via sanitize()
function e($value, $default = '') {
	if ($value === null || $value === '') {
		return htmlspecialchars((string)$default, ENT_QUOTES, 'UTF-8');
	}
	$decoded = html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	return htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
}

/////////////////////////////////////////////////// FOR ADMIN

// Sessions For login
function adminLogin($user_id) {
	$_SESSION['CTFLHADMIN'] = $user_id;
    $_SESSION['flash_success'] = 'Welcome back! You are now logged in.';
    redirect(PROOT . 'admin/dashboard');
}

function admin_is_logged_in() {
	return !empty($_SESSION['CTFLHADMIN']);
}

// Redirect admin if !logged in
function admin_login_redirect($url = 'admin/') {
	$_SESSION['flash_error'] = 'Please sign in to access the admin console.';
	redirect(PROOT . $url);
}

// Get live badge counts for the admin sidebar & header
function get_admin_WaitCounts($dbConnection) {
	$counts = [
		'pending_requests'     => 0,
		'pending_applications' => 0,
		'unread_messages'      => 0,
		'active_caregivers'    => 0,
		'active_assignments'   => 0,
	];
	try {
		$counts['pending_requests'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_service_requests WHERE request_status = 'pending'")->fetchColumn();
		$counts['pending_applications'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_applications WHERE application_status = 'pending'")->fetchColumn();
		$counts['unread_messages'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE message_status = 'unread'")->fetchColumn();
		$counts['active_caregivers'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_users WHERE user_type = 'caregiver' AND user_status = 'active'")->fetchColumn();
		$counts['active_assignments'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_assignments WHERE assignment_status = 'active'")->fetchColumn();
	} catch (\Throwable $e) {
		// Ignore silently if table not ready
	}
	return $counts;
}

// Get user by user_id
function get_user($user_id) {
    global $dbConnection;
    $query = "SELECT * FROM care_users WHERE user_id = ? LIMIT 1";
    $statement = $dbConnection->prepare($query);
    $statement->execute([$user_id]);
    return $statement->fetch(PDO::FETCH_OBJ);
}

// Get user by email
function get_user_by_email($user_email) {
    global $dbConnection;
    $query = "SELECT * FROM care_users WHERE user_email = :user_email LIMIT 1";
    $statement = $dbConnection->prepare($query);
    $statement->execute([':user_email' => $user_email]);
    return $statement->fetch(PDO::FETCH_OBJ);
}

// Get all caregivers with their profile info
function get_all_caregivers($dbConnection) {
    $sql = "
        SELECT u.*, 
               p.profile_id, p.profile_experience, p.profile_service_type, 
               p.profile_availability, p.profile_bio, p.profile_hourly_rate, 
               p.profile_background_check_status
        FROM care_users u
        LEFT JOIN care_caregiver_profiles p ON u.user_id = p.profile_user_id
        WHERE u.user_type = 'caregiver'
        ORDER BY u.created_at DESC
    ";
    return $dbConnection->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}
