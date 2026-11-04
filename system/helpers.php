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

// Intelligent spam & importance classifier for contact inquiries
function classify_contact_message($name, $email, $phone, $subject, $message) {
	$rawMsg   = strtolower(html_entity_decode((string)$message, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
	$rawEmail = strtolower(trim((string)$email));
	$rawName  = strtolower(trim((string)$name));
	$rawSubj  = strtolower(trim((string)$subject));
	$combined = $rawName . ' ' . $rawEmail . ' ' . $rawMsg;

	// 1. Check for SEO / Search Engine / Ranking solicitation spam
	$seoPatterns = [
		'page 1 of google', 'google 1st page', 'first page of google', 'top in google',
		'search engine rankings', 'ranking on google', 'ranking is not good',
		'not ranking well on google', 'seo check', 'seo issues', 'seo setup',
		'seo manager', 'seo performance', 'seo expert', 'discuss seo',
		'search rankings', 'visibility on google', 'collection of links',
		'bunch of links', 'backlinks', 'google searches', 'deserves more traffic',
		'website’s traffic', 'website\'s traffic', 'website&rsquo;s traffic'
	];
	foreach ($seoPatterns as $pat) {
		if (strpos($combined, $pat) !== false) {
			return ['is_spam' => 1, 'is_important' => 0, 'spam_reason' => 'SEO / Search Ranking Solicitation'];
		}
	}

	// 2. Check for Web Design / Marketing / Cold Sales solicitation spam
	$webMarketingPatterns = [
		'website design and development', 'website redesign', 'web design and marketing',
		'senior web developer', 'following the completion of your website',
		'quick backend check', '40% black friday', 'affiliate links',
		'attract 10 to 20 motivated clients', 'monkey digital', 'strictlydigital',
		'bestaiseocompany', 'rocketdigitaltech', 'dgtlsolution', 'trumarketers',
		'easyrankingsolution', 'speed-seo', 'websolution', 'start.cards',
		'business proposal totally legal', 'lawfully and effectively',
		'high-performing website', 'potential in your website', 'launch of your new website'
	];
	foreach ($webMarketingPatterns as $pat) {
		if (strpos($combined, $pat) !== false) {
			return ['is_spam' => 1, 'is_important' => 0, 'spam_reason' => 'Web Design / Marketing Cold Pitch'];
		}
	}

	// 3. Check for Proxy / Investor / Directory / Scam bots
	$scamPatterns = [
		'dreamproxies', 'private proxies', 'eu business register',
		'investment consultancy', 'welcomes investors', 'vampire attack',
		'seek your business cooperation', 'mutual business deal'
	];
	foreach ($scamPatterns as $pat) {
		if (strpos($combined, $pat) !== false) {
			return ['is_spam' => 1, 'is_important' => 0, 'spam_reason' => 'Bulk / Scam Solicitation Bot'];
		}
	}

	// 4. Check for automated bot URL greetings (e.g., "Hello https://carethatfeelslikehome.com" or "Hey team carethatfeelslikehome.com")
	if (preg_match('/(hello|hi|hey|dear|howdy|salutations)[^,\n]{0,30}carethatfeelslikehome\.com/i', $rawMsg) ||
	    preg_match('/https?:\/\/(app\.|h5\.)?carethatfeelslikehome\.com/i', $rawMsg)) {
		return ['is_spam' => 1, 'is_important' => 0, 'spam_reason' => 'Automated Domain Bot Greeting'];
	}

	// 5. Otherwise legitimate! Determine if high-priority / Important
	$importantKeywords = [
		'care', 'caregiver', 'care giving', 'nursing', 'nurse', 'home care',
		'elderly', 'parent', 'mother', 'father', 'patient', 'spouse', 'respite',
		'transport', 'service', 'hire', 'apply', 'employment', 'job', 'bismarck', 'nd'
	];
	$isImportant = 0;
	foreach ($importantKeywords as $kw) {
		if (strpos($rawMsg, $kw) !== false) {
			$isImportant = 1;
			break;
		}
	}
	if (!$isImportant && in_array($rawSubj, ['service request', 'employment'], true) && strlen(trim($rawMsg)) >= 15) {
		$isImportant = 1;
	}

	return ['is_spam' => 0, 'is_important' => $isImportant, 'spam_reason' => null];
}

// Run smart classification across all contact messages in the database
function auto_scan_contact_messages($dbConnection) {
	$stmt = $dbConnection->query("SELECT id, message_name, message_email, message_phone, message_subject, message_message FROM care_contact_messages");
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$upStmt = $dbConnection->prepare("UPDATE care_contact_messages SET is_spam = ?, is_important = ?, spam_reason = ? WHERE id = ?");

	$stats = ['spam' => 0, 'important' => 0, 'inbox' => 0];
	foreach ($rows as $r) {
		$res = classify_contact_message($r['message_name'], $r['message_email'], $r['message_phone'], $r['message_subject'], $r['message_message']);
		$upStmt->execute([$res['is_spam'], $res['is_important'], $res['spam_reason'], $r['id']]);
		if ($res['is_spam']) {
			$stats['spam']++;
		} else {
			$stats['inbox']++;
			if ($res['is_important']) {
				$stats['important']++;
			}
		}
	}
	return $stats;
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
		'pending_onboarding'   => 0,
		'unread_messages'      => 0,
		'important_messages'   => 0,
		'spam_messages'        => 0,
		'active_caregivers'    => 0,
		'active_assignments'   => 0,
	];
	try {
		$counts['pending_requests'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_service_requests WHERE request_status = 'pending'")->fetchColumn();
		$counts['pending_applications'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_applications WHERE application_status = 'pending'")->fetchColumn();
		$counts['pending_onboarding'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_staff_onboarding WHERE onboarding_status = 'pending'")->fetchColumn();
		// Only alert on legitimate (non-spam) unread messages
		$counts['unread_messages'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE message_status = 'unread' AND is_spam = 0")->fetchColumn();
		$counts['important_messages'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE is_important = 1 AND is_spam = 0")->fetchColumn();
		$counts['spam_messages'] = (int)$dbConnection->query("SELECT COUNT(*) FROM care_contact_messages WHERE is_spam = 1")->fetchColumn();
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
