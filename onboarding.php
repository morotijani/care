<?php
include_once "system/DatabaseConnector.php";
care_send_private_headers();
ensure_onboarding_security_schema($dbConnection);

// Explicit portal lock / sign-out action
if (isset($_GET['lock']) && $_GET['lock'] === '1') {
    unset(
        $_SESSION['ONBOARDING_AUTH_VERIFIED'],
        $_SESSION['ONBOARDING_AUTH_AT'],
        $_SESSION['ONBOARDING_INVITE_ID'],
        $_SESSION['ONBOARDING_PREFILL_NAME'],
        $_SESSION['ONBOARDING_PREFILL_EMAIL'],
        $_SESSION['ONBOARDING_JUST_SUBMITTED']
    );
    $_SESSION['flash_success'] = "Your onboarding session has been securely locked.";
    redirect(PROOT . "onboarding");
}

// Enforce 60-minute inactivity timeout on unlocked onboarding sessions
if (!empty($_SESSION['ONBOARDING_AUTH_VERIFIED'])) {
    $lastActive = (int)($_SESSION['ONBOARDING_AUTH_AT'] ?? 0);
    if ((time() - $lastActive) > 3600) {
        unset(
            $_SESSION['ONBOARDING_AUTH_VERIFIED'],
            $_SESSION['ONBOARDING_AUTH_AT'],
            $_SESSION['ONBOARDING_INVITE_ID'],
            $_SESSION['ONBOARDING_PREFILL_NAME'],
            $_SESSION['ONBOARDING_PREFILL_EMAIL']
        );
        $_SESSION['flash_error'] = "Your secure onboarding session timed out due to inactivity. Please re-enter your passcode.";
        redirect(PROOT . "onboarding");
    }
    $_SESSION['ONBOARDING_AUTH_AT'] = time();
}

// 0. Stream Official Blank Onboarding PDF Documents (W-4, I-9, Policy Declaration, Compliance, Policy & Procedures, Reporting)
if (isset($_GET['download_template'])) {
    if (empty($_SESSION['ONBOARDING_AUTH_VERIFIED']) && !admin_is_logged_in()) {
        http_response_code(403);
        exit('403 Forbidden — Please unlock the Staff Onboarding Portal first.');
    }
    $tplKey  = trim((string)$_GET['download_template']);
    $tplInfo = care_resolve_onboarding_template($tplKey);
    if (!$tplInfo) {
        http_response_code(404);
        exit('404 Not Found — Onboarding template document not found.');
    }

    $isPreview = isset($_GET['preview']) && $_GET['preview'] === '1';
    $disp      = $isPreview ? 'inline' : 'attachment';
    $fileSize  = (int)filesize($tplInfo['path']);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $disp . '; filename="' . $tplInfo['download_name'] . '"');
    header('Content-Length: ' . $fileSize);
    header('X-Content-Type-Options: nosniff');
    readfile($tplInfo['path']);
    exit;
}

$rateLimitInfo = care_is_onboarding_rate_limited($dbConnection);
$authError = '';

// 1. Handle Portal Unlock (Passcode / Invite Code Authentication)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_onboarding'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $authError = "Security session expired. Please try submitting your passcode again.";
    } elseif ($rateLimitInfo['locked']) {
        $authError = "Too many failed passcode attempts from your network. Portal access is temporarily locked for " . $rateLimitInfo['remaining_minutes'] . " minute(s).";
    } else {
        $passcodeInput = $_POST['access_passcode'] ?? '';
        $verify = care_verify_onboarding_passcode($dbConnection, $passcodeInput);

        if ($verify['valid']) {
            session_regenerate_id(true);
            $_SESSION['CARE_CSRF_TOKEN']          = bin2hex(random_bytes(32));
            $_SESSION['ONBOARDING_AUTH_VERIFIED'] = true;
            $_SESSION['ONBOARDING_AUTH_AT']       = time();
            $_SESSION['ONBOARDING_INVITE_ID']     = $verify['invite_id'];
            $_SESSION['ONBOARDING_PREFILL_NAME']  = $verify['staff_name'] ?? '';
            $_SESSION['ONBOARDING_PREFILL_EMAIL'] = $verify['staff_email'] ?? '';

            care_log_onboarding_event($dbConnection, 'auth_success', 'Unlocked via ' . ($verify['code_hint'] ?? 'passcode'));
            redirect(PROOT . "onboarding");
        } else {
            care_log_onboarding_event($dbConnection, 'auth_fail', 'Invalid passcode attempt');
            $rateLimitInfo = care_is_onboarding_rate_limited($dbConnection);
            if ($rateLimitInfo['locked']) {
                $authError = "Too many invalid attempts. Access locked for " . $rateLimitInfo['remaining_minutes'] . " minutes.";
            } else {
                $remainingTries = max(1, 5 - $rateLimitInfo['attempts']);
                $authError = $verify['reason'] . " (" . $remainingTries . " attempt" . ($remainingTries === 1 ? '' : 's') . " remaining before temporary lockout.)";
            }
        }
    }
}

// 2. Handle Encrypted Onboarding Packet Submission (Strictly Gated Behind Verified Session + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_onboarding'])) {
    if (empty($_SESSION['ONBOARDING_AUTH_VERIFIED'])) {
        http_response_code(403);
        $_SESSION['flash_error'] = "Unauthorized submission blocked. Please unlock the Staff Onboarding Portal with your HR passcode first.";
        redirect(PROOT . "onboarding");
    }

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Invalid or expired security token. Please review and submit again.";
        redirect(PROOT . "onboarding");
    }

    // Re-check invite validity if session unlocked via an individual invite
    $activeInviteId = !empty($_SESSION['ONBOARDING_INVITE_ID']) ? (int)$_SESSION['ONBOARDING_INVITE_ID'] : null;
    if ($activeInviteId) {
        $invChk = $dbConnection->prepare("
            SELECT id FROM care_onboarding_invites
            WHERE id = ? AND is_revoked = 0 AND expires_at > NOW() AND used_count < max_uses
            LIMIT 1
        ");
        $invChk->execute([$activeInviteId]);
        if (!$invChk->fetchColumn()) {
            unset($_SESSION['ONBOARDING_AUTH_VERIFIED'], $_SESSION['ONBOARDING_INVITE_ID']);
            $_SESSION['flash_error'] = "This single-use invite passcode has expired or already been used.";
            redirect(PROOT . "onboarding");
        }
    }

    $staff_name    = trim($_POST['staff_name'] ?? '');
    $staff_email   = trim($_POST['staff_email'] ?? '');
    $staff_phone   = trim($_POST['staff_phone'] ?? '');
    $staff_address = trim($_POST['staff_address'] ?? '');

    // 1. Government ID
    $gov_id_type   = trim($_POST['gov_id_type'] ?? '');
    $gov_id_number = trim($_POST['gov_id_number'] ?? '');

    // 2. SSN
    $ssn_number    = trim($_POST['ssn_number'] ?? '');

    // 3. Background check
    $background_check_status = trim($_POST['background_check_status'] ?? '');

    // 4. FWA certificate
    $fwa_status    = trim($_POST['fwa_status'] ?? '');

    // 5. SFN749/CNA/LPN/RN
    $cred_types = isset($_POST['credential_type']) && is_array($_POST['credential_type'])
        ? implode(', ', array_map('trim', $_POST['credential_type']))
        : trim($_POST['credential_type'] ?? '');
    $credential_number = trim($_POST['credential_number'] ?? '');

    // 6. Chore labor
    $chore_labor = isset($_POST['chore_labor']) && is_array($_POST['chore_labor'])
        ? implode(', ', array_map('trim', $_POST['chore_labor']))
        : trim($_POST['chore_labor'] ?? '');
    $chore_labor_notes = trim($_POST['chore_labor_notes'] ?? '');

    // 7. Direct deposit
    $bank_name      = trim($_POST['bank_name'] ?? '');
    $account_type   = trim($_POST['account_type'] ?? '');
    $routing_number = trim($_POST['routing_number'] ?? '');
    $account_number = trim($_POST['account_number'] ?? '');

    // 8. W-4
    $w4_filing_status = trim($_POST['w4_filing_status'] ?? '');

    // 9. I-9
    $i9_work_status   = trim($_POST['i9_work_status'] ?? '');

    // 10. Therap account
    $therap_status   = trim($_POST['therap_status'] ?? '');
    $therap_username = trim($_POST['therap_username'] ?? '');

    // 11. Sign final Onboarding for new staff (4 signed documents + electronic signature)
    $digital_signature = trim($_POST['digital_signature'] ?? '');
    $signature_date    = !empty($_POST['signature_date']) ? $_POST['signature_date'] : date('Y-m-d');

    // 12. Driver's license
    $drivers_license_number = trim($_POST['drivers_license_number'] ?? '');
    $drivers_license_state  = trim($_POST['drivers_license_state'] ?? '');
    $drivers_license_expiry = !empty($_POST['drivers_license_expiry']) ? $_POST['drivers_license_expiry'] : null;

    // 13. Copy of driver's insurance
    $drivers_insurance_provider = trim($_POST['drivers_insurance_provider'] ?? '');
    $drivers_insurance_policy   = trim($_POST['drivers_insurance_policy'] ?? '');

    $additional_notes = trim($_POST['additional_notes'] ?? '');

    // Check required document uploads for W-4 (8), I-9 (9), and the 4 Final Onboarding Documents (11)
    $mandatoryUploadLabels = [
        'w4_file'                 => 'Signed Form W-4 (Item 8)',
        'i9_file'                 => 'Signed Form I-9 (Item 9)',
        'policy_declaration_file' => 'Policy Declaration Document (Item 11)',
        'compliance_file'         => 'Compliance Document (Item 11)',
        'policy_procedures_file'  => 'Policy and Procedures Document (Item 11)',
        'reporting_file'          => 'Reporting Document (Item 11)',
    ];
    $missingMandatoryDocs = [];
    foreach ($mandatoryUploadLabels as $mKey => $mLabel) {
        if (!isset($_FILES[$mKey]) || $_FILES[$mKey]['error'] === UPLOAD_ERR_NO_FILE) {
            $missingMandatoryDocs[] = $mLabel;
        }
    }

    if (empty($staff_name) || empty($staff_email) || empty($staff_phone)) {
        $_SESSION['flash_error'] = "Please fill in your required contact details (Name, Email, Phone).";
        redirect(PROOT . "onboarding");
    } elseif (!empty($missingMandatoryDocs)) {
        $_SESSION['flash_error'] = "Please download, fill out, and upload all required onboarding documents before submitting: " . implode(', ', $missingMandatoryDocs) . ".";
        redirect(PROOT . "onboarding");
    } elseif (empty($digital_signature)) {
        $_SESSION['flash_error'] = "Please provide your full legal name as your electronic signature in Section 11.";
        redirect(PROOT . "onboarding");
    } else {
        $fileFields = [
            'gov_id_file',
            'ssn_file',
            'background_check_file',
            'fwa_certificate_file',
            'credential_file',
            'direct_deposit_file',
            'w4_file',
            'i9_file',
            'policy_declaration_file',
            'compliance_file',
            'policy_procedures_file',
            'reporting_file',
            'drivers_license_file',
            'drivers_insurance_file'
        ];

        $uploadedPaths = [];
        foreach ($fileFields as $ff) {
            $res = care_store_encrypted_upload($ff);
            if ($res === false) {
                // Roll back any vault blobs already encrypted in this request
                foreach ($uploadedPaths as $savedVaultRef) {
                    if ($savedVaultRef) {
                        care_delete_vault_file($savedVaultRef);
                    }
                }
                $_SESSION['flash_error'] = "One of your uploaded documents failed security validation or exceeds 10MB. Allowed genuine formats: PDF, JPG, PNG, WEBP, DOC, DOCX.";
                redirect(PROOT . "onboarding");
            }
            $uploadedPaths[$ff] = $res;
        }

        // All 4 Section 11 documents are verified & uploaded
        $policy_declaration       = !empty($uploadedPaths['policy_declaration_file']) ? 1 : 0;
        $compliance_agreed        = !empty($uploadedPaths['compliance_file']) ? 1 : 0;
        $policy_procedures_agreed = !empty($uploadedPaths['policy_procedures_file']) ? 1 : 0;
        $reporting_agreed         = !empty($uploadedPaths['reporting_file']) ? 1 : 0;

        try {
            // Encrypt all sensitive PII fields at rest with AES-256-GCM before SQL INSERT
            $encGovIdNumber     = care_encrypt_pii($gov_id_number);
            $encSsnNumber       = care_encrypt_pii($ssn_number);
            $encRoutingNumber   = care_encrypt_pii($routing_number);
            $encAccountNumber   = care_encrypt_pii($account_number);
            $encLicenseNumber   = care_encrypt_pii($drivers_license_number);
            $encInsurancePolicy = care_encrypt_pii($drivers_insurance_policy);

            $sql = "
                INSERT INTO care_staff_onboarding (
                    onboarding_id, staff_name, staff_email, staff_phone, staff_address,
                    gov_id_type, gov_id_number, gov_id_file,
                    ssn_number, ssn_file,
                    background_check_status, background_check_file,
                    fwa_status, fwa_certificate_file,
                    credential_type, credential_number, credential_file,
                    chore_labor, chore_labor_notes,
                    bank_name, account_type, routing_number, account_number, direct_deposit_file,
                    w4_filing_status, w4_file,
                    i9_work_status, i9_file,
                    therap_status, therap_username,
                    policy_declaration, policy_declaration_file,
                    compliance_agreed, compliance_file,
                    policy_procedures_agreed, policy_procedures_file,
                    reporting_agreed, reporting_file,
                    digital_signature, signature_date,
                    drivers_license_number, drivers_license_state, drivers_license_expiry, drivers_license_file,
                    drivers_insurance_provider, drivers_insurance_policy, drivers_insurance_file,
                    additional_notes
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?
                )
            ";
            $stmt = $dbConnection->prepare($sql);
            $stmt->execute([
                guidv4(), $staff_name, $staff_email, $staff_phone, $staff_address ?: null,
                $gov_id_type ?: null, $encGovIdNumber, $uploadedPaths['gov_id_file'],
                $encSsnNumber, $uploadedPaths['ssn_file'],
                $background_check_status ?: null, $uploadedPaths['background_check_file'],
                $fwa_status ?: null, $uploadedPaths['fwa_certificate_file'],
                $cred_types ?: null, $credential_number ?: null, $uploadedPaths['credential_file'],
                $chore_labor ?: null, $chore_labor_notes ?: null,
                $bank_name ?: null, $account_type ?: null, $encRoutingNumber, $encAccountNumber, $uploadedPaths['direct_deposit_file'],
                $w4_filing_status ?: null, $uploadedPaths['w4_file'],
                $i9_work_status ?: null, $uploadedPaths['i9_file'],
                $therap_status ?: null, $therap_username ?: null,
                $policy_declaration, $uploadedPaths['policy_declaration_file'],
                $compliance_agreed, $uploadedPaths['compliance_file'],
                $policy_procedures_agreed, $uploadedPaths['policy_procedures_file'],
                $reporting_agreed, $uploadedPaths['reporting_file'],
                $digital_signature, $signature_date,
                $encLicenseNumber, $drivers_license_state ?: null, $drivers_license_expiry, $uploadedPaths['drivers_license_file'],
                $drivers_insurance_provider ?: null, $encInsurancePolicy, $uploadedPaths['drivers_insurance_file'],
                $additional_notes ?: null
            ]);

            // Consume single-use / limited-use invite code if applicable
            if ($activeInviteId) {
                $upInv = $dbConnection->prepare("
                    UPDATE care_onboarding_invites
                    SET used_count = used_count + 1, last_used_at = NOW()
                    WHERE id = ?
                ");
                $upInv->execute([$activeInviteId]);
            }

            care_log_onboarding_event($dbConnection, 'packet_submitted', 'Encrypted onboarding submitted by ' . $staff_name);

            // Send confirmation email (zero sensitive PII included in email body)
            $body = "
                <p>Dear " . htmlspecialchars($staff_name, ENT_QUOTES, 'UTF-8') . ",</p>
                <p>Thank you for submitting your encrypted <strong>Staff Onboarding</strong> packet to <strong>Care That Feels Like Home</strong>. Your documentation has been securely stored in our encrypted HR vault and will be reviewed shortly.</p>
                <p>Best regards,<br>Care That Feels Like Home Corporation</p>
            ";
            @send_email($staff_name, $staff_email, "Encrypted Onboarding Received - Care That Feels Like Home", $body);

            // Immediately re-lock onboarding session after successful submission
            unset(
                $_SESSION['ONBOARDING_AUTH_VERIFIED'],
                $_SESSION['ONBOARDING_AUTH_AT'],
                $_SESSION['ONBOARDING_INVITE_ID'],
                $_SESSION['ONBOARDING_PREFILL_NAME'],
                $_SESSION['ONBOARDING_PREFILL_EMAIL']
            );
            $_SESSION['ONBOARDING_JUST_SUBMITTED'] = true;

            redirect(PROOT . "onboarding?submitted=1");
        } catch (PDOException $e) {
            foreach ($uploadedPaths as $savedVaultRef) {
                if ($savedVaultRef) {
                    care_delete_vault_file($savedVaultRef);
                }
            }
            error_log("Onboarding submission DB error: " . $e->getMessage());
            $_SESSION['flash_error'] = "Unable to save your onboarding submission right now. Please try again or contact HR.";
            redirect(PROOT . "onboarding");
        }
    }
}

$isPortalUnlocked = !empty($_SESSION['ONBOARDING_AUTH_VERIFIED']);
$isSubmitted = false;
if (isset($_GET['submitted']) && $_GET['submitted'] == '1' && !empty($_SESSION['ONBOARDING_JUST_SUBMITTED'])) {
    $isSubmitted = true;
    unset($_SESSION['ONBOARDING_JUST_SUBMITTED']);
}

$prefillName  = $_SESSION['ONBOARDING_PREFILL_NAME'] ?? '';
$prefillEmail = $_SESSION['ONBOARDING_PREFILL_EMAIL'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="referrer" content="no-referrer">
    <title><?= $isPortalUnlocked ? 'Encrypted Staff Onboarding — Care That Feels Like Home' : 'Protected HR Portal — Care That Feels Like Home'; ?></title>
    <link rel="icon" type="image/jpg" href="<?= PROOT; ?>assets/media/logo/logo.jpg" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --gf-theme: #007958;
            --gf-theme-dark: #005c43;
            --gf-theme-light: #e3f3ee;
            --gf-accent: #a68b6b;
            --gf-bg: #ebf4f1;
            --gf-border: #dadce0;
            --gf-text: #202124;
            --gf-muted: #5f6368;
            --gf-danger: #d93025;
        }

        body {
            background-color: var(--gf-bg);
            color: var(--gf-text);
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
            padding-bottom: 4rem;
            min-height: 100vh;
        }

        .gf-topbar {
            background: #ffffff;
            border-bottom: 1px solid var(--gf-border);
            padding: 0.65rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .gf-container {
            max-width: 720px;
            margin: 1.5rem auto;
            padding: 0 0.85rem;
        }

        .gf-banner-card {
            background: #ffffff;
            border: 1px solid var(--gf-border);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(60, 64, 67, 0.08);
            position: relative;
        }

        .gf-roof-header {
            height: 95px;
            background: var(--gf-theme);
            position: relative;
            overflow: hidden;
        }

        .gf-roof-header::after {
            content: "";
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 75px;
            background: #ffffff;
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
        }

        .gf-brand-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            padding: 0.5rem 2rem 1.5rem;
            border-bottom: 1px solid var(--gf-border);
        }

        .gf-brand-logo {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
            border: 3px solid #f3d7bd;
        }

        .gf-brand-words {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #a68b6b;
            line-height: 0.95;
            text-transform: uppercase;
            text-align: left;
        }

        .gf-brand-words .w-care {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            display: block;
        }

        .gf-brand-words .w-sub {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            display: block;
            margin: 3px 0;
        }

        .gf-brand-words .w-home {
            font-size: 2.75rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            display: block;
        }

        .gf-section-banner {
            background: var(--gf-theme);
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 8px 8px 0 0;
            letter-spacing: 0.01em;
        }

        .gf-stepper {
            display: flex;
            gap: 0.65rem;
            margin-bottom: 12px;
        }

        .gf-step-pill {
            flex: 1;
            background: #ffffff;
            border: 1px solid var(--gf-border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            opacity: 0.65;
        }

        .gf-step-pill.active {
            opacity: 1;
            border-color: var(--gf-theme);
            box-shadow: 0 2px 6px rgba(0, 121, 88, 0.12);
        }

        .gf-step-pill.completed {
            opacity: 0.9;
            border-color: #a7f3d0;
            background: #f8fdfb;
        }

        .gf-step-badge {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #475569;
            font-weight: 700;
            font-size: 0.82rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .gf-step-pill.active .gf-step-badge {
            background: var(--gf-theme);
            color: #ffffff;
        }

        .gf-step-pill.completed .gf-step-badge {
            background: #10b981;
            color: #ffffff;
        }

        .gf-card {
            background: #ffffff;
            border: 1px solid var(--gf-border);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 12px;
            position: relative;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            overflow: hidden;
        }

        .gf-card:focus-within {
            box-shadow: 0 2px 8px rgba(60, 64, 67, 0.16);
        }

        .gf-card.gf-card-error {
            border-color: var(--gf-danger);
        }

        .gf-card::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 6px;
            background: transparent;
            transition: background-color 0.2s ease;
        }

        .gf-card:focus-within::before {
            background: var(--gf-theme);
        }

        .gf-card.gf-card-error::before {
            background: var(--gf-danger);
        }

        .gf-title-card {
            border-top: 10px solid var(--gf-theme);
        }

        .gf-q-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--gf-theme-light);
            color: var(--gf-theme);
            font-weight: 700;
            font-size: 0.82rem;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .gf-q-title {
            font-size: 1rem;
            font-weight: 500;
            color: var(--gf-text);
            margin-bottom: 0.35rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .gf-q-desc {
            font-size: 0.84rem;
            color: var(--gf-muted);
            margin-bottom: 1.1rem;
        }

        .gf-req {
            color: var(--gf-danger);
            margin-left: 4px;
            font-weight: 600;
        }

        .gf-error-msg {
            display: none;
            color: var(--gf-danger);
            font-size: 0.8rem;
            margin-top: 0.65rem;
            font-weight: 500;
        }

        .gf-card.gf-card-error .gf-error-msg {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .gf-input {
            border: none;
            border-bottom: 1px solid #9aa0a6;
            border-radius: 0;
            padding: 8px 0;
            font-size: 0.95rem;
            color: var(--gf-text);
            background: transparent;
            width: 100%;
            transition: border-color 0.2s ease;
        }

        .gf-input:focus {
            outline: none;
            border-bottom: 2px solid var(--gf-theme);
            box-shadow: none;
        }

        .gf-input::placeholder {
            color: #80868b;
        }

        .gf-choice {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.45rem 0.25rem;
            cursor: pointer;
            font-size: 0.94rem;
            color: var(--gf-text);
        }

        .gf-choice input[type="radio"],
        .gf-choice input[type="checkbox"] {
            width: 1.18rem;
            height: 1.18rem;
            accent-color: var(--gf-theme);
            cursor: pointer;
            flex-shrink: 0;
        }

        .gf-upload-box {
            margin-top: 0.75rem;
            padding: 0.85rem 1rem;
            border: 1px dashed #bdc1c6;
            border-radius: 6px;
            background: #f8f9fa;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .gf-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 1rem;
            border: 1px solid #dadce0;
            border-radius: 4px;
            background: #ffffff;
            color: var(--gf-theme);
            font-weight: 500;
            font-size: 0.88rem;
            cursor: pointer;
            transition: background 0.15s ease, box-shadow 0.15s ease;
        }

        .gf-upload-btn:hover {
            background: #f1f8f6;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
        }

        .gf-file-name {
            font-size: 0.82rem;
            color: var(--gf-muted);
        }

        .btn-gf-submit,
        .btn-gf-next {
            background-color: var(--gf-theme);
            color: #ffffff;
            font-weight: 500;
            font-size: 0.92rem;
            padding: 0.55rem 1.65rem;
            border-radius: 4px;
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: background 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-gf-submit:hover,
        .btn-gf-next:hover {
            background-color: var(--gf-theme-dark);
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }

        .btn-gf-back {
            background-color: #ffffff;
            color: var(--gf-theme);
            font-weight: 500;
            font-size: 0.92rem;
            padding: 0.55rem 1.4rem;
            border-radius: 4px;
            border: 1px solid var(--gf-border);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            transition: background 0.15s ease;
        }

        .btn-gf-back:hover {
            background-color: #f8faf9;
            color: var(--gf-theme-dark);
        }

        .btn-gf-clear {
            background: transparent;
            border: none;
            color: var(--gf-theme);
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
        }

        .btn-gf-clear:hover {
            background: rgba(0, 121, 88, 0.08);
        }

        .gf-progress-bar {
            height: 8px;
            border-radius: 999px;
            background: #c8e6dc;
            overflow: hidden;
            width: 150px;
        }

        .gf-progress-fill {
            height: 100%;
            background: var(--gf-theme);
            width: 0%;
            transition: width 0.25s ease;
        }

        .vault-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.73rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
    </style>
</head>
<body>

    <!-- Minimal Top Navigation Bar -->
    <header class="gf-topbar">
        <div class="container d-flex justify-content-between align-items-center" style="max-width: 960px;">
            <a href="<?= PROOT; ?>" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                <img src="<?= PROOT; ?>assets/media/logo/logo.jpg" alt="Logo" width="32" height="32" style="border-radius: 6px;">
                <span class="fw-bold small">Care That Feels Like Home</span>
            </a>
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <?php if ($isPortalUnlocked && !$isSubmitted): ?>
                    <div class="d-none d-md-flex align-items-center gap-2">
                        <div class="gf-progress-bar">
                            <div class="gf-progress-fill" id="topProgressFill"></div>
                        </div>
                        <span class="small text-muted fw-medium" id="topProgressLabel">0 / 13 completed</span>
                    </div>
                    <span class="vault-badge d-none d-sm-inline-flex">
                        <i class="fas fa-shield-halved"></i> AES-256 Encrypted
                    </span>
                    <a href="<?= PROOT; ?>onboarding?lock=1" class="btn btn-sm btn-outline-danger" style="font-size: 0.78rem;">
                        <i class="fas fa-lock me-1"></i>Lock Portal
                    </a>
                <?php else: ?>
                    <span class="badge bg-dark-subtle text-dark border fw-semibold" style="font-size: 0.75rem;">
                        <i class="fas fa-lock me-1 text-secondary"></i>Restricted HR Portal
                    </span>
                    <a href="<?= PROOT; ?>" class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem;">
                        <i class="fas fa-arrow-left me-1"></i>Main Site
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="gf-container" id="formTopAnchor">

        <?php if ($isSubmitted): ?>
            <!-- =========================================================
                 CONFIRMATION SCREEN (SESSION ALREADY RE-LOCKED)
                 ========================================================= -->
            <div class="gf-banner-card">
                <div class="gf-roof-header"></div>
                <div class="gf-brand-hero">
                    <img src="<?= PROOT; ?>assets/media/logo/logo.jpg" alt="Care That Feels Like Home" class="gf-brand-logo">
                    <div class="gf-brand-words">
                        <span class="w-care">CARE</span>
                        <span class="w-sub">THAT FEELS LIKE</span>
                        <span class="w-home">HOME</span>
                    </div>
                </div>
            </div>

            <div class="gf-card gf-title-card p-4 p-md-5">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px; background: var(--gf-theme-light); color: var(--gf-theme);">
                        <i class="fas fa-shield-check fa-lg"></i>
                    </div>
                    <div>
                        <h2 class="h4 mb-1">Your encrypted packet has been securely stored.</h2>
                        <p class="text-muted small mb-0">Your onboarding session has been automatically locked to protect your data.</p>
                    </div>
                </div>
                <p class="small text-secondary mb-4">
                    All sensitive identification numbers and uploaded documents have been encrypted at rest with <strong>AES-256-GCM</strong> inside our restricted HR vault. Our Compliance team will review your onboarding packet and follow up if anything else is needed.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= PROOT; ?>" class="btn btn-sm btn-gf-next text-decoration-none">
                        Return to Home Page
                    </a>
                </div>
            </div>

        <?php elseif (!$isPortalUnlocked): ?>
            <!-- =========================================================
                 PASSWORD & INVITE-CODE PROTECTED GATE SCREEN
                 (NO FORM FIELDS OR UPLOAD INPUTS RENDERED TO THE PUBLIC)
                 ========================================================= -->
            <div class="gf-banner-card" style="max-width: 560px; margin: 1.5rem auto;">
                <div class="gf-roof-header"></div>
                <div class="gf-brand-hero pb-3">
                    <img src="<?= PROOT; ?>assets/media/logo/logo.jpg" alt="Care That Feels Like Home" class="gf-brand-logo" style="width: 96px; height: 96px;">
                    <div class="gf-brand-words">
                        <span class="w-care" style="font-size: 2.5rem;">CARE</span>
                        <span class="w-sub" style="font-size: 0.85rem;">THAT FEELS LIKE</span>
                        <span class="w-home" style="font-size: 2.3rem;">HOME</span>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="vault-badge">
                            <i class="fas fa-lock"></i> Password Protected &bull; AES-256-GCM Encrypted
                        </span>
                    </div>
                    <h1 class="h4 fw-bold mb-2" style="color: #202124;">Restricted Staff Onboarding Vault</h1>
                    <p class="text-muted small mb-4">
                        This portal is strictly restricted to authorized new hires of <strong>Care That Feels Like Home Corporation</strong>. Please enter the <strong>Single-Use Invite Passcode</strong> (e.g. <code>CTFLH-XXXX-XXXX</code>) or HR Portal Password issued to you by Human Resources.
                    </p>

                    <?php if ($authError !== ''): ?>
                        <div class="alert alert-danger d-flex align-items-start gap-2 py-2 px-3 small mb-4" role="alert">
                            <i class="fas fa-triangle-exclamation mt-1"></i>
                            <div><?= e($authError); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="" autocomplete="off">
                        <?= csrf_field(); ?>
                        <div class="mb-3">
                            <label for="accessPasscodeInput" class="form-label fw-semibold small text-dark">
                                HR Onboarding Access Passcode <span class="gf-req">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-secondary">
                                    <i class="fas fa-key"></i>
                                </span>
                                <input type="password"
                                       name="access_passcode"
                                       id="accessPasscodeInput"
                                       class="form-control border-start-0 py-2"
                                       placeholder="Enter CTFLH-XXXX-XXXX or HR Password"
                                       required
                                       autofocus
                                       <?= $rateLimitInfo['locked'] ? 'disabled' : ''; ?>>
                                <button type="button" class="btn btn-outline-secondary" id="toggleGatePassBtn" title="Show/Hide Passcode">
                                    <i class="far fa-eye" id="toggleGatePassIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit"
                                name="unlock_onboarding"
                                class="btn-gf-submit w-100 py-2 mt-2"
                                <?= $rateLimitInfo['locked'] ? 'disabled' : ''; ?>>
                            <i class="fas fa-unlock-keyhole me-2"></i>Verify &amp; Unlock Onboarding Portal
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top">
                        <div class="row g-2 text-center" style="font-size: 0.76rem; color: #5f6368;">
                            <div class="col-4">
                                <i class="fas fa-user-lock d-block mb-1 text-success"></i>
                                Authorized Access Only
                            </div>
                            <div class="col-4">
                                <i class="fas fa-file-shield d-block mb-1 text-success"></i>
                                AES-256-GCM Vault
                            </div>
                            <div class="col-4">
                                <i class="fas fa-eye-slash d-block mb-1 text-success"></i>
                                Zero Public Links
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- =========================================================
                 AUTHENTICATED 2-STEP ENCRYPTED ONBOARDING FORM
                 ========================================================= -->
            <div class="gf-banner-card">
                <div class="gf-roof-header"></div>
                <div class="gf-brand-hero">
                    <img src="<?= PROOT; ?>assets/media/logo/logo.jpg" alt="Care That Feels Like Home" class="gf-brand-logo">
                    <div class="gf-brand-words">
                        <span class="w-care">CARE</span>
                        <span class="w-sub">THAT FEELS LIKE</span>
                        <span class="w-home">HOME</span>
                    </div>
                </div>
                <div class="p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h1 class="h3 fw-normal mb-0" style="color: #202124;">Staff Onboarding</h1>
                        <span class="vault-badge">
                            <i class="fas fa-lock"></i> End-to-End Encrypted HR Vault
                        </span>
                    </div>
                    <p class="text-muted small mb-3">
                        Welcome to the <strong>Care That Feels Like Home</strong> team! Your session is authenticated and protected. All uploaded documents and sensitive identification numbers are automatically encrypted at rest using <strong>AES-256-GCM</strong> in a non-public vault accessible only to authorized HR administrators.
                    </p>
                    <div class="small" style="color: var(--gf-danger);">* Indicates required question</div>
                </div>
            </div>

            <!-- 2-Step Visual Stepper Indicator -->
            <div class="gf-stepper">
                <div class="gf-step-pill active" id="stepPill1">
                    <span class="gf-step-badge" id="stepBadge1">1</span>
                    <div>
                        <div class="fw-bold small text-dark">Step 1: Identity &amp; Credentials</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Contact Info &amp; Items 1–6</div>
                    </div>
                </div>
                <div class="gf-step-pill" id="stepPill2">
                    <span class="gf-step-badge" id="stepBadge2">2</span>
                    <div>
                        <div class="fw-bold small text-dark">Step 2: Payroll, Driving &amp; Sign-Off</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Items 7–13 &amp; Final Signature</div>
                    </div>
                </div>
            </div>

            <form method="post" action="" enctype="multipart/form-data" id="onboardingForm" autocomplete="off" novalidate>
                <?= csrf_field(); ?>

                <!-- =========================================================
                     STEP 1 (PAGE 1 OF 2): STAFF CONTACT + ITEMS 1 TO 6
                     ========================================================= -->
                <div id="formStep1">

                    <!-- Staff Basic Contact Information Card -->
                    <div class="gf-card step1-card">
                        <div class="gf-q-title">
                            <span class="gf-q-number"><i class="fas fa-user" style="font-size: 0.72rem;"></i></span>
                            <span>Staff Member Contact Information <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">Please provide your legal name and active contact details for your employee file.</div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="small text-muted fw-medium mb-1">Full Legal Name <span class="gf-req">*</span></label>
                                <input type="text" name="staff_name" class="gf-input" placeholder="Your answer" value="<?= e($prefillName); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-medium mb-1">Email Address <span class="gf-req">*</span></label>
                                <input type="email" name="staff_email" class="gf-input" placeholder="your.email@example.com" value="<?= e($prefillEmail); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-medium mb-1">Phone Number <span class="gf-req">*</span></label>
                                <input type="tel" name="staff_phone" class="gf-input" placeholder="+1 (701) 000-0000" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-medium mb-1">Current Residential Address <span class="gf-req">*</span></label>
                                <input type="text" name="staff_address" class="gf-input" placeholder="Street, City, State, ZIP" required>
                            </div>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please complete all required contact fields.</div>
                    </div>

                    <!-- 1. Government ID -->
                    <div class="gf-card onboarding-section step1-card" data-section="1">
                        <div class="gf-q-title">
                            <span class="gf-q-number">1</span>
                            <span>Government ID <span class="gf-req">*</span></span>
                            <span class="vault-badge ms-auto"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
                        </div>
                        <div class="gf-q-desc">Select your valid government-issued photo identification and upload a clear copy to the encrypted vault.</div>

                        <div class="mb-3">
                            <label class="gf-choice">
                                <input type="radio" name="gov_id_type" value="State ID / Driver's License" required>
                                <span>State-Issued ID / Driver's License</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="gov_id_type" value="U.S. Passport">
                                <span>U.S. Passport or Passport Card</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="gov_id_type" value="Permanent Resident Card">
                                <span>Permanent Resident Card (Green Card)</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="gov_id_type" value="Military ID">
                                <span>U.S. Military Identification Card</span>
                            </label>
                        </div>

                        <div class="mb-3" style="max-width: 360px;">
                            <input type="text" name="gov_id_number" class="gf-input" placeholder="Government ID Number" autocomplete="off" required>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Government ID Copy (Front/Back)</div>
                                <div class="gf-file-name" id="fn_gov_id_file">Encrypted on upload &bull; PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="gov_id_file" class="d-none gf-file-input" data-target="fn_gov_id_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select an ID type and enter your Government ID number.</div>
                    </div>

                    <!-- 2. SSN -->
                    <div class="gf-card onboarding-section step1-card" data-section="2">
                        <div class="gf-q-title">
                            <span class="gf-q-number">2</span>
                            <span>SSN (Social Security Number) <span class="gf-req">*</span></span>
                            <span class="vault-badge ms-auto"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
                        </div>
                        <div class="gf-q-desc">Enter your 9-digit Social Security Number for payroll &amp; tax verification, and optionally attach a copy of your SSN card.</div>

                        <div class="d-flex align-items-center gap-2 mb-3" style="max-width: 340px;">
                            <input type="password" name="ssn_number" id="ssnInput" class="gf-input" placeholder="XXX-XX-XXXX" maxlength="11" autocomplete="new-password" required>
                            <button type="button" class="btn btn-sm btn-light border" id="toggleSsnBtn" title="Show/Hide SSN">
                                <i class="far fa-eye" id="toggleSsnIcon"></i>
                            </button>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Copy of Social Security Card</div>
                                <div class="gf-file-name" id="fn_ssn_file">Encrypted on upload &bull; PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="ssn_file" class="d-none gf-file-input" data-target="fn_ssn_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please enter your Social Security Number.</div>
                    </div>

                    <!-- 3. Pass background check -->
                    <div class="gf-card onboarding-section step1-card" data-section="3">
                        <div class="gf-q-title">
                            <span class="gf-q-number">3</span>
                            <span>Pass background check <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">Indicate your background check status or authorize Care That Feels Like Home to conduct a criminal background screening.</div>

                        <div class="mb-3">
                            <label class="gf-choice">
                                <input type="radio" name="background_check_status" value="Passed / Clearance Attached" required>
                                <span>Passed — I have a current background check clearance</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="background_check_status" value="Submitted & Pending">
                                <span>Submitted — Fingerprinting / Background check is currently pending</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="background_check_status" value="Authorized for Screening">
                                <span>I authorize Care That Feels Like Home to initiate my background check</span>
                            </label>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Background Check Clearance / Authorization Form (if available)</div>
                                <div class="gf-file-name" id="fn_background_check_file">Encrypted on upload &bull; PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="background_check_file" class="d-none gf-file-input" data-target="fn_background_check_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select a background check option.</div>
                    </div>

                    <!-- 4. FWA certificate -->
                    <div class="gf-card onboarding-section step1-card" data-section="4">
                        <div class="gf-q-title">
                            <span class="gf-q-number">4</span>
                            <span>FWA certificate <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">Fraud, Waste, and Abuse (FWA) training certification status and certificate upload.</div>

                        <div class="mb-3">
                            <label class="gf-choice">
                                <input type="radio" name="fwa_status" value="Completed" required>
                                <span>Completed — FWA Certificate attached below</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="fwa_status" value="In Progress">
                                <span>In Progress — Currently completing FWA training</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="fwa_status" value="Needs Training Link">
                                <span>Not Yet Started — Requesting FWA training portal access</span>
                            </label>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload FWA Certificate</div>
                                <div class="gf-file-name" id="fn_fwa_certificate_file">Encrypted on upload &bull; PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="fwa_certificate_file" class="d-none gf-file-input" data-target="fn_fwa_certificate_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select your FWA certificate status.</div>
                    </div>

                    <!-- 5. SFN749/CNA/LPN/RN -->
                    <div class="gf-card onboarding-section step1-card" data-section="5" data-checkbox-group="credential_type[]">
                        <div class="gf-q-title">
                            <span class="gf-q-number">5</span>
                            <span>SFN749 / CNA / LPN / RN <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">Select all applicable North Dakota forms, nursing licenses, or caregiver credentials you hold and attach your certificate/form.</div>

                        <div class="mb-3">
                            <label class="gf-choice">
                                <input type="checkbox" name="credential_type[]" value="SFN 749">
                                <span>SFN 749 (Qualified Service Provider / QSP Documentation)</span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="credential_type[]" value="CNA">
                                <span>CNA (Certified Nursing Assistant)</span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="credential_type[]" value="LPN">
                                <span>LPN (Licensed Practical Nurse)</span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="credential_type[]" value="RN">
                                <span>RN (Registered Nurse)</span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="credential_type[]" value="Non-Medical / Personal Care">
                                <span>Non-Medical / Personal Care / Direct Support</span>
                            </label>
                        </div>

                        <div class="mb-3" style="max-width: 400px;">
                            <input type="text" name="credential_number" class="gf-input" placeholder="License / Registry / SFN749 Number (if applicable)">
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload SFN 749 Form or CNA / LPN / RN License</div>
                                <div class="gf-file-name" id="fn_credential_file">Encrypted on upload &bull; PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="credential_file" class="d-none gf-file-input" data-target="fn_credential_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select at least one credential or role option.</div>
                    </div>

                    <!-- 6. Chore labor -->
                    <div class="gf-card onboarding-section step1-card" data-section="6" data-checkbox-group="chore_labor[]">
                        <div class="gf-q-title">
                            <span class="gf-q-number">6</span>
                            <span>Chore labor <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">Select the Chore Labor and Home Service tasks you are available and able to perform.</div>

                        <div class="mb-3">
                            <label class="gf-choice">
                                <input type="checkbox" name="chore_labor[]" value="Chore - Labor (General)">
                                <span>Chore - Labor (General Heavy/Light Household Chores)</span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="chore_labor[]" value="Chore - Lawn Care">
                                <span>Chore - Lawn Care</span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="chore_labor[]" value="Chore - Snow Removal">
                                <span>Chore - Snow Removal</span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="chore_labor[]" value="Home Service & Housekeeping">
                                <span>Home Service &amp; Routine Housekeeping</span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="chore_labor[]" value="Not Applicable">
                                <span>Not Applicable (Clinical / Nursing / Transport Only)</span>
                            </label>
                        </div>

                        <div>
                            <input type="text" name="chore_labor_notes" class="gf-input" placeholder="Any notes or equipment availability for chore labor (optional)">
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select at least one chore labor option.</div>
                    </div>

                    <!-- Step 1 Google Forms Action Bar -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 mb-4">
                        <button type="button" class="btn-gf-next" id="goToStep2Btn">
                            Next <i class="fas fa-arrow-right ms-1 small"></i>
                        </button>

                        <div class="d-flex align-items-center gap-2 my-2">
                            <div class="gf-progress-bar">
                                <div class="gf-progress-fill" style="width: 50%;"></div>
                            </div>
                            <span class="small text-muted">Page 1 of 2</span>
                        </div>

                        <button type="reset" class="btn-gf-clear" onclick="return confirm('Clear all answers on this form?');">
                            Clear form
                        </button>
                    </div>

                </div> <!-- End of #formStep1 -->


                <!-- =========================================================
                     STEP 2 (PAGE 2 OF 2): PAYROLL, TAX, DRIVING & SIGN-OFF (ITEMS 7 TO 13)
                     ========================================================= -->
                <div id="formStep2" style="display: none;">

                    <!-- Google Forms Section 2 Banner Card -->
                    <div class="gf-banner-card mb-3">
                        <div class="gf-section-banner">
                            Section 2 of 2 &mdash; Payroll, Tax Forms, Driving &amp; Final Onboarding Sign-Off
                        </div>
                        <div class="p-3 px-4 small text-muted">
                            Please complete items 7 through 13 below, review and check all four mandatory onboarding policy declarations, and sign electronically to encrypt and submit your packet.
                        </div>
                    </div>

                    <!-- 7. Direct deposit -->
                    <div class="gf-card onboarding-section step2-card" data-section="7">
                        <div class="gf-q-title">
                            <span class="gf-q-number">7</span>
                            <span>Direct deposit <span class="gf-req">*</span></span>
                            <span class="vault-badge ms-auto"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
                        </div>
                        <div class="gf-q-desc">Provide your banking details for payroll direct deposit, or attach a voided check / direct deposit authorization letter.</div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="small text-muted">Bank / Financial Institution Name <span class="gf-req">*</span></label>
                                <input type="text" name="bank_name" class="gf-input" placeholder="e.g., Bank of North Dakota, US Bank" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted d-block mb-1">Account Type <span class="gf-req">*</span></label>
                                <div class="d-flex gap-4 pt-1">
                                    <label class="gf-choice py-0">
                                        <input type="radio" name="account_type" value="Checking" required>
                                        <span>Checking</span>
                                    </label>
                                    <label class="gf-choice py-0">
                                        <input type="radio" name="account_type" value="Savings">
                                        <span>Savings</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">ABA Routing Number (9 digits) <span class="gf-req">*</span></label>
                                <input type="text" name="routing_number" class="gf-input" placeholder="9-digit routing number" maxlength="9" autocomplete="off" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">Account Number <span class="gf-req">*</span></label>
                                <input type="password" name="account_number" id="acctInput" class="gf-input" placeholder="Account number" autocomplete="new-password" required>
                            </div>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Voided Check or Bank Direct Deposit Form (Optional)</div>
                                <div class="gf-file-name" id="fn_direct_deposit_file">Encrypted on upload &bull; PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="direct_deposit_file" class="d-none gf-file-input" data-target="fn_direct_deposit_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please complete your direct deposit banking details.</div>
                    </div>

                    <!-- 8. W4 form -->
                    <div class="gf-card onboarding-section step2-card" data-section="8">
                        <div class="gf-q-title">
                            <span class="gf-q-number">8</span>
                            <span>W-4 Form (Employee's Withholding Certificate) <span class="gf-req">*</span></span>
                            <span class="vault-badge ms-auto"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
                        </div>
                        <div class="gf-q-desc">Download the official Form W-4 below, fill it out and sign it, select your federal tax withholding status, and upload your completed Form W-4.</div>

                        <!-- Step 1: Download Blank W-4 PDF -->
                        <div class="p-3 rounded-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-file-pdf text-danger fs-4"></i>
                                <div>
                                    <div class="fw-semibold small text-dark">W-4 . Employee’s Withholding Certificate.pdf</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">Step 1: Download, read, and complete this form</div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= PROOT; ?>onboarding?download_template=w4&amp;preview=1" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-medium">
                                    <i class="far fa-eye me-1 text-secondary"></i>Read PDF
                                </a>
                                <a href="<?= PROOT; ?>onboarding?download_template=w4" class="btn btn-sm btn-success fw-medium">
                                    <i class="fas fa-download me-1"></i>Download W-4 PDF
                                </a>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="gf-choice">
                                <input type="radio" name="w4_filing_status" value="Single or Married filing separately" required>
                                <span>Single or Married filing separately</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="w4_filing_status" value="Married filing jointly or Qualifying surviving spouse">
                                <span>Married filing jointly or Qualifying surviving spouse</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="w4_filing_status" value="Head of household">
                                <span>Head of household</span>
                            </label>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Step 2: Upload Completed &amp; Signed Form W-4 <span class="gf-req">*</span></div>
                                <div class="gf-file-name" id="fn_w4_file">Required &bull; Encrypted on upload (PDF, JPG, PNG, DOC, DOCX)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Upload W-4
                                <input type="file" name="w4_file" class="d-none gf-file-input" data-target="fn_w4_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required>
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select your W-4 filing status and upload your completed Form W-4.</div>
                    </div>

                    <!-- 9. I-9 form -->
                    <div class="gf-card onboarding-section step2-card" data-section="9">
                        <div class="gf-q-title">
                            <span class="gf-q-number">9</span>
                            <span>I-9 Form (Employment Eligibility Verification) <span class="gf-req">*</span></span>
                            <span class="vault-badge ms-auto"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
                        </div>
                        <div class="gf-q-desc">Download the official Form I-9 below, fill it out and sign it, attest to your employment authorization status, and upload your completed Form I-9.</div>

                        <!-- Step 1: Download Blank I-9 PDF -->
                        <div class="p-3 rounded-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-file-pdf text-danger fs-4"></i>
                                <div>
                                    <div class="fw-semibold small text-dark">Form I-9, Employment Eligibility Verification.pdf</div>
                                    <div class="text-muted" style="font-size: 0.78rem;">Step 1: Download, read, and complete this form</div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= PROOT; ?>onboarding?download_template=i9&amp;preview=1" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-medium">
                                    <i class="far fa-eye me-1 text-secondary"></i>Read PDF
                                </a>
                                <a href="<?= PROOT; ?>onboarding?download_template=i9" class="btn btn-sm btn-success fw-medium">
                                    <i class="fas fa-download me-1"></i>Download I-9 PDF
                                </a>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="gf-choice">
                                <input type="radio" name="i9_work_status" value="A citizen of the United States" required>
                                <span>A citizen of the United States</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="i9_work_status" value="A noncitizen national of the United States">
                                <span>A noncitizen national of the United States</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="i9_work_status" value="A lawful permanent resident">
                                <span>A lawful permanent resident</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="i9_work_status" value="A noncitizen authorized to work">
                                <span>A noncitizen authorized to work</span>
                            </label>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Step 2: Upload Completed &amp; Signed Form I-9 <span class="gf-req">*</span></div>
                                <div class="gf-file-name" id="fn_i9_file">Required &bull; Encrypted on upload (PDF, JPG, PNG, DOC, DOCX)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Upload I-9
                                <input type="file" name="i9_file" class="d-none gf-file-input" data-target="fn_i9_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required>
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select your I-9 work eligibility status and upload your completed Form I-9.</div>
                    </div>

                    <!-- 10. Therap account -->
                    <div class="gf-card onboarding-section step2-card" data-section="10">
                        <div class="gf-q-title">
                            <span class="gf-q-number">10</span>
                            <span>Therap account <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">Indicate your Therap Electronic Health Record / documentation portal account status.</div>

                        <div class="mb-3">
                            <label class="gf-choice">
                                <input type="radio" name="therap_status" value="Active Account" required>
                                <span>I have an active Therap account</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="therap_status" value="Need Account Created">
                                <span>I need a new Therap account created for me</span>
                            </label>
                            <label class="gf-choice">
                                <input type="radio" name="therap_status" value="Pending Invitation">
                                <span>I have received an invitation and am completing setup</span>
                            </label>
                        </div>

                        <div style="max-width: 380px;">
                            <input type="text" name="therap_username" class="gf-input" placeholder="Existing Therap Username / Login ID (if applicable)">
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select your Therap account status.</div>
                    </div>

                    <!-- 11. Sign final Onboarding for new staff (4 Document Downloads & Required Uploads) -->
                    <div class="gf-card onboarding-section step2-card" data-section="11">
                        <div class="gf-q-title">
                            <span class="gf-q-number">11</span>
                            <span>Sign final Onboarding for new staff <span class="gf-req">*</span></span>
                            <span class="vault-badge ms-auto"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
                        </div>
                        <div class="gf-q-desc">
                            Please <strong>download, read, and fill/sign</strong> all four mandatory onboarding documents below, then <strong>upload each completed document</strong> and provide your binding electronic signature before submitting.
                        </div>

                        <!-- 11a. Policy Declaration -->
                        <div class="p-3 rounded-3 mb-3" style="background: #f8faf9; border: 1px solid #d4e8e1;">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-bold small text-dark">
                                        1. Policy declaration <span class="gf-req">*</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.82rem;">
                                        Read, complete, and sign <em>Policy Declaration - Care That Feels Like Home.pdf</em>, then upload your completed copy.
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="<?= PROOT; ?>onboarding?download_template=policy_declaration&amp;preview=1" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-medium">
                                        <i class="far fa-eye me-1 text-secondary"></i>Read PDF
                                    </a>
                                    <a href="<?= PROOT; ?>onboarding?download_template=policy_declaration" class="btn btn-sm btn-outline-success fw-medium">
                                        <i class="fas fa-download me-1"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                            <div class="gf-upload-box mt-2 bg-white">
                                <div>
                                    <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Signed Policy Declaration <span class="gf-req">*</span></div>
                                    <div class="gf-file-name" id="fn_policy_declaration_file">Required &bull; Encrypted on upload (Max 10MB)</div>
                                </div>
                                <label class="gf-upload-btn">
                                    <i class="fas fa-arrow-up-from-bracket"></i> Upload Completed File
                                    <input type="file" name="policy_declaration_file" class="d-none gf-file-input" data-target="fn_policy_declaration_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required>
                                </label>
                            </div>
                        </div>

                        <!-- 11b. Compliance -->
                        <div class="p-3 rounded-3 mb-3" style="background: #f8faf9; border: 1px solid #d4e8e1;">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-bold small text-dark">
                                        2. Compliance <span class="gf-req">*</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.82rem;">
                                        Read, complete, and sign <em>Compliance - Care That Feels Like Home.pdf</em>, then upload your completed copy.
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="<?= PROOT; ?>onboarding?download_template=compliance&amp;preview=1" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-medium">
                                        <i class="far fa-eye me-1 text-secondary"></i>Read PDF
                                    </a>
                                    <a href="<?= PROOT; ?>onboarding?download_template=compliance" class="btn btn-sm btn-outline-success fw-medium">
                                        <i class="fas fa-download me-1"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                            <div class="gf-upload-box mt-2 bg-white">
                                <div>
                                    <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Signed Compliance Document <span class="gf-req">*</span></div>
                                    <div class="gf-file-name" id="fn_compliance_file">Required &bull; Encrypted on upload (Max 10MB)</div>
                                </div>
                                <label class="gf-upload-btn">
                                    <i class="fas fa-arrow-up-from-bracket"></i> Upload Completed File
                                    <input type="file" name="compliance_file" class="d-none gf-file-input" data-target="fn_compliance_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required>
                                </label>
                            </div>
                        </div>

                        <!-- 11c. Policy and procedures -->
                        <div class="p-3 rounded-3 mb-3" style="background: #f8faf9; border: 1px solid #d4e8e1;">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-bold small text-dark">
                                        3. Policy and procedures <span class="gf-req">*</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.82rem;">
                                        Read, complete, and sign <em>Policy and Procedures - Care That Feels Like Home.pdf</em>, then upload your completed copy.
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="<?= PROOT; ?>onboarding?download_template=policy_procedures&amp;preview=1" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-medium">
                                        <i class="far fa-eye me-1 text-secondary"></i>Read PDF
                                    </a>
                                    <a href="<?= PROOT; ?>onboarding?download_template=policy_procedures" class="btn btn-sm btn-outline-success fw-medium">
                                        <i class="fas fa-download me-1"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                            <div class="gf-upload-box mt-2 bg-white">
                                <div>
                                    <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Signed Policy &amp; Procedures <span class="gf-req">*</span></div>
                                    <div class="gf-file-name" id="fn_policy_procedures_file">Required &bull; Encrypted on upload (Max 10MB)</div>
                                </div>
                                <label class="gf-upload-btn">
                                    <i class="fas fa-arrow-up-from-bracket"></i> Upload Completed File
                                    <input type="file" name="policy_procedures_file" class="d-none gf-file-input" data-target="fn_policy_procedures_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required>
                                </label>
                            </div>
                        </div>

                        <!-- 11d. Reporting -->
                        <div class="p-3 rounded-3 mb-3" style="background: #f8faf9; border: 1px solid #d4e8e1;">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-bold small text-dark">
                                        4. Reporting <span class="gf-req">*</span>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.82rem;">
                                        Read, complete, and sign <em>Report - Care That Feels Like Home.pdf</em>, then upload your completed copy.
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="<?= PROOT; ?>onboarding?download_template=reporting&amp;preview=1" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-medium">
                                        <i class="far fa-eye me-1 text-secondary"></i>Read PDF
                                    </a>
                                    <a href="<?= PROOT; ?>onboarding?download_template=reporting" class="btn btn-sm btn-outline-success fw-medium">
                                        <i class="fas fa-download me-1"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                            <div class="gf-upload-box mt-2 bg-white">
                                <div>
                                    <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Signed Reporting Document <span class="gf-req">*</span></div>
                                    <div class="gf-file-name" id="fn_reporting_file">Required &bull; Encrypted on upload (Max 10MB)</div>
                                </div>
                                <label class="gf-upload-btn">
                                    <i class="fas fa-arrow-up-from-bracket"></i> Upload Completed File
                                    <input type="file" name="reporting_file" class="d-none gf-file-input" data-target="fn_reporting_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" required>
                                </label>
                            </div>
                        </div>

                        <div class="row g-3 pt-2">
                            <div class="col-md-7">
                                <label class="small text-muted fw-medium">Electronic Signature (Type Full Legal Name) <span class="gf-req">*</span></label>
                                <input type="text" name="digital_signature" class="gf-input" placeholder="e.g., Jane Marie Doe" style="font-family: Georgia, serif; font-style: italic; font-size: 1.05rem;" required>
                            </div>
                            <div class="col-md-5">
                                <label class="small text-muted fw-medium">Date Signed <span class="gf-req">*</span></label>
                                <input type="date" name="signature_date" class="gf-input" value="<?= date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please upload all four completed onboarding documents and provide your electronic signature.</div>
                    </div>

                    <!-- 12. Driver's license -->
                    <div class="gf-card onboarding-section step2-card" data-section="12">
                        <div class="gf-q-title">
                            <span class="gf-q-number">12</span>
                            <span>Driver's license <span class="gf-req">*</span></span>
                            <span class="vault-badge ms-auto"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
                        </div>
                        <div class="gf-q-desc">Provide your Driver's License details and upload a clear photo or scan of your license.</div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="small text-muted">Driver's License Number <span class="gf-req">*</span></label>
                                <input type="text" name="drivers_license_number" class="gf-input" placeholder="License number" autocomplete="off" required>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">Issuing State <span class="gf-req">*</span></label>
                                <input type="text" name="drivers_license_state" class="gf-input" placeholder="e.g., ND" value="ND" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Expiration Date <span class="gf-req">*</span></label>
                                <input type="date" name="drivers_license_expiry" class="gf-input" required>
                            </div>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Copy of Driver's License</div>
                                <div class="gf-file-name" id="fn_drivers_license_file">Encrypted on upload &bull; PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="drivers_license_file" class="d-none gf-file-input" data-target="fn_drivers_license_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please enter your driver's license number, state, and expiration date.</div>
                    </div>

                    <!-- 13. Copy of driver's insurance -->
                    <div class="gf-card onboarding-section step2-card" data-section="13">
                        <div class="gf-q-title">
                            <span class="gf-q-number">13</span>
                            <span>Copy of driver's insurance <span class="gf-req">*</span></span>
                            <span class="vault-badge ms-auto"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
                        </div>
                        <div class="gf-q-desc">Provide your current auto insurance carrier &amp; policy number and attach a copy of your proof of insurance.</div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="small text-muted">Insurance Carrier / Provider <span class="gf-req">*</span></label>
                                <input type="text" name="drivers_insurance_provider" class="gf-input" placeholder="e.g., State Farm, Progressive, Geico" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">Policy Number <span class="gf-req">*</span></label>
                                <input type="text" name="drivers_insurance_policy" class="gf-input" placeholder="Policy number" autocomplete="off" required>
                            </div>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small"><i class="fas fa-file-shield text-success me-1"></i>Upload Copy of Driver's Insurance Card / Declarations</div>
                                <div class="gf-file-name" id="fn_drivers_insurance_file">Encrypted on upload &bull; PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="drivers_insurance_file" class="d-none gf-file-input" data-target="fn_drivers_insurance_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please enter your insurance carrier and policy number.</div>
                    </div>

                    <!-- Optional Additional Comments Card -->
                    <div class="gf-card">
                        <div class="gf-q-title">
                            <span>Additional Comments or Notes (Optional)</span>
                        </div>
                        <div class="gf-q-desc">Let us know if any documents are pending renewal or if you have questions for HR.</div>
                        <input type="text" name="additional_notes" class="gf-input" placeholder="Your answer">
                    </div>

                    <!-- Step 2 Google Forms Footer Action Bar -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn-gf-back" id="backToStep1Btn">
                                Back
                            </button>
                            <button type="submit" name="submit_onboarding" class="btn-gf-submit">
                                <i class="fas fa-lock me-1"></i>Encrypt &amp; Submit
                            </button>
                        </div>

                        <div class="d-flex align-items-center gap-2 my-2">
                            <div class="gf-progress-bar">
                                <div class="gf-progress-fill" style="width: 100%;"></div>
                            </div>
                            <span class="small text-muted">Page 2 of 2</span>
                        </div>

                        <button type="reset" class="btn-gf-clear" id="clearFormBtn">
                            Clear form
                        </button>
                    </div>

                </div> <!-- End of #formStep2 -->

                <div class="text-center text-muted small mt-4">
                    <p class="mb-1"><i class="fas fa-shield-halved text-success me-1"></i>Protected by Password Authentication &amp; AES-256-GCM Encrypted Vault Storage.</p>
                    <p class="mb-0"><strong>Care That Feels Like Home Corporation</strong> &mdash; Bismarck, ND</p>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?= $flash_user; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gate Passcode Show/Hide Toggle
        const gatePassInput = document.getElementById('accessPasscodeInput');
        const toggleGateBtn = document.getElementById('toggleGatePassBtn');
        if (toggleGateBtn && gatePassInput) {
            toggleGateBtn.addEventListener('click', function() {
                const icon = document.getElementById('toggleGatePassIcon');
                if (gatePassInput.type === 'password') {
                    gatePassInput.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    gatePassInput.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        }

        // Display selected filename on upload buttons
        document.querySelectorAll('.gf-file-input').forEach(input => {
            input.addEventListener('change', function() {
                const labelEl = document.getElementById(this.dataset.target);
                if (this.files && this.files.length > 0) {
                    labelEl.innerHTML = '<i class="fas fa-lock text-success me-1"></i><strong>' + this.files[0].name + '</strong> <span class="text-success">(Ready for AES-256 encryption)</span>';
                } else {
                    labelEl.textContent = 'Encrypted on upload • PDF, JPG, or PNG (Max 10MB)';
                }
                updateProgress();
            });
        });

        // Format SSN as XXX-XX-XXXX and toggle visibility
        const ssnInput = document.getElementById('ssnInput');
        const toggleSsnBtn = document.getElementById('toggleSsnBtn');
        if (ssnInput) {
            ssnInput.addEventListener('input', function() {
                let digits = this.value.replace(/\D/g, '').substring(0, 9);
                if (digits.length > 5) {
                    this.value = digits.slice(0, 3) + '-' + digits.slice(3, 5) + '-' + digits.slice(5);
                } else if (digits.length > 3) {
                    this.value = digits.slice(0, 3) + '-' + digits.slice(3);
                } else {
                    this.value = digits;
                }
            });
        }
        if (toggleSsnBtn && ssnInput) {
            toggleSsnBtn.addEventListener('click', function() {
                const icon = document.getElementById('toggleSsnIcon');
                if (ssnInput.type === 'password') {
                    ssnInput.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    ssnInput.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        }

        // Validate cards inside a given step container
        function validateStepCards(cardsSelector) {
            let firstInvalidCard = null;
            document.querySelectorAll(cardsSelector).forEach(card => {
                let cardValid = true;

                const reqInputs = card.querySelectorAll('input[required], select[required], textarea[required]');
                reqInputs.forEach(inp => {
                    if (inp.type === 'radio') {
                        const groupChecked = card.querySelector('input[name="' + inp.name + '"]:checked');
                        if (!groupChecked) cardValid = false;
                    } else if (inp.type === 'checkbox') {
                        if (!inp.checked) cardValid = false;
                    } else if (inp.type === 'file') {
                        if (!inp.files || inp.files.length === 0) cardValid = false;
                    } else {
                        if (!inp.value.trim() || !inp.checkValidity()) cardValid = false;
                    }
                });

                const chkGroupName = card.getAttribute('data-checkbox-group');
                if (chkGroupName) {
                    const anyChecked = card.querySelector('input[name="' + chkGroupName + '"]:checked');
                    if (!anyChecked) cardValid = false;
                }

                if (!cardValid) {
                    card.classList.add('gf-card-error');
                    if (!firstInvalidCard) firstInvalidCard = card;
                } else {
                    card.classList.remove('gf-card-error');
                }
            });

            if (firstInvalidCard) {
                firstInvalidCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            return true;
        }

        document.querySelectorAll('.gf-card').forEach(card => {
            card.addEventListener('input', () => card.classList.remove('gf-card-error'));
            card.addEventListener('change', () => card.classList.remove('gf-card-error'));
        });

        const step1Div = document.getElementById('formStep1');
        const step2Div = document.getElementById('formStep2');
        const pill1 = document.getElementById('stepPill1');
        const pill2 = document.getElementById('stepPill2');
        const badge1 = document.getElementById('stepBadge1');
        const goToStep2Btn = document.getElementById('goToStep2Btn');
        const backToStep1Btn = document.getElementById('backToStep1Btn');
        const formEl = document.getElementById('onboardingForm');

        function showStep(stepNum) {
            if (!step1Div || !step2Div) return;
            if (stepNum === 2) {
                step1Div.style.display = 'none';
                step2Div.style.display = 'block';
                pill1.classList.remove('active');
                pill1.classList.add('completed');
                badge1.innerHTML = '<i class="fas fa-check"></i>';
                pill2.classList.add('active');
            } else {
                step2Div.style.display = 'none';
                step1Div.style.display = 'block';
                pill2.classList.remove('active');
                pill1.classList.remove('completed');
                pill1.classList.add('active');
                badge1.textContent = '1';
            }
            document.getElementById('formTopAnchor').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        if (goToStep2Btn) {
            goToStep2Btn.addEventListener('click', function() {
                if (validateStepCards('.step1-card')) {
                    showStep(2);
                }
            });
        }

        if (backToStep1Btn) {
            backToStep1Btn.addEventListener('click', function() {
                showStep(1);
            });
        }

        if (formEl) {
            formEl.addEventListener('submit', function(e) {
                if (!validateStepCards('.step2-card')) {
                    e.preventDefault();
                }
            });
        }

        const clearBtn = document.getElementById('clearFormBtn');
        if (clearBtn && formEl) {
            clearBtn.addEventListener('click', function(e) {
                if (!confirm('Clear all answers on this form?')) {
                    e.preventDefault();
                    return;
                }
                setTimeout(() => {
                    updateProgress();
                    showStep(1);
                }, 50);
            });
        }

        function updateProgress() {
            const sections = document.querySelectorAll('.onboarding-section');
            if (!sections.length) return;
            let completed = 0;

            sections.forEach(sec => {
                const inputs = sec.querySelectorAll('input, select, textarea');
                let hasVal = false;
                inputs.forEach(inp => {
                    if ((inp.type === 'radio' || inp.type === 'checkbox') && inp.checked) {
                        hasVal = true;
                    } else if (inp.type === 'file' && inp.files && inp.files.length > 0) {
                        hasVal = true;
                    } else if (!['radio', 'checkbox', 'file', 'date', 'hidden'].includes(inp.type) && inp.value.trim() !== '') {
                        hasVal = true;
                    }
                });
                if (hasVal) completed++;
            });

            const pct = Math.round((completed / sections.length) * 100);
            const topFill = document.getElementById('topProgressFill');
            const topLabel = document.getElementById('topProgressLabel');
            if (topFill) topFill.style.width = pct + '%';
            if (topLabel) topLabel.textContent = completed + ' / ' + sections.length + ' completed';
        }

        if (formEl) {
            formEl.addEventListener('input', updateProgress);
            formEl.addEventListener('change', updateProgress);
        }
    </script>
</body>
</html>
