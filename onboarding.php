<?php
include_once "system/DatabaseConnector.php";

// Helper to handle individual onboarding document uploads safely
function uploadOnboardingFile($fieldKey, $uploadDir) {
    if (!isset($_FILES[$fieldKey]) || $_FILES[$fieldKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$fieldKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
    $origName = $_FILES[$fieldKey]['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return false; // Invalid file type
    }
    if ($_FILES[$fieldKey]['size'] > 10 * 1024 * 1024) { // 10MB max per file
        return false;
    }
    $safeFilename = $fieldKey . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeFilename;
    if (move_uploaded_file($_FILES[$fieldKey]['tmp_name'], $destPath)) {
        return 'assets/media/onboarding/' . $safeFilename;
    }
    return null;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_onboarding'])) {
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

    // 11. Sign final Onboarding for new staff
    $policy_declaration       = !empty($_POST['policy_declaration']) ? 1 : 0;
    $compliance_agreed        = !empty($_POST['compliance_agreed']) ? 1 : 0;
    $policy_procedures_agreed = !empty($_POST['policy_procedures_agreed']) ? 1 : 0;
    $reporting_agreed         = !empty($_POST['reporting_agreed']) ? 1 : 0;
    $digital_signature        = trim($_POST['digital_signature'] ?? '');
    $signature_date           = !empty($_POST['signature_date']) ? $_POST['signature_date'] : date('Y-m-d');

    // 12. Driver's license
    $drivers_license_number = trim($_POST['drivers_license_number'] ?? '');
    $drivers_license_state  = trim($_POST['drivers_license_state'] ?? '');
    $drivers_license_expiry = !empty($_POST['drivers_license_expiry']) ? $_POST['drivers_license_expiry'] : null;

    // 13. Copy of driver's insurance
    $drivers_insurance_provider = trim($_POST['drivers_insurance_provider'] ?? '');
    $drivers_insurance_policy   = trim($_POST['drivers_insurance_policy'] ?? '');

    $additional_notes = trim($_POST['additional_notes'] ?? '');

    if (empty($staff_name) || empty($staff_email) || empty($staff_phone)) {
        $_SESSION['flash_error'] = "Please fill in your required contact details (Name, Email, Phone).";
        redirect(PROOT . "onboarding");
    } elseif (!$policy_declaration || !$compliance_agreed || !$policy_procedures_agreed || !$reporting_agreed || empty($digital_signature)) {
        $_SESSION['flash_error'] = "Please acknowledge all four Final Onboarding policy declarations and provide your digital signature.";
        redirect(PROOT . "onboarding");
    } else {
        // Prepare upload directory
        $uploadDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'onboarding';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            file_put_contents($uploadDir . DIRECTORY_SEPARATOR . 'index.php', "<?php // Silence is golden\n");
        }

        $fileFields = [
            'gov_id_file',
            'ssn_file',
            'background_check_file',
            'fwa_certificate_file',
            'credential_file',
            'direct_deposit_file',
            'w4_file',
            'i9_file',
            'drivers_license_file',
            'drivers_insurance_file'
        ];
        $uploadedPaths = [];
        foreach ($fileFields as $ff) {
            $res = uploadOnboardingFile($ff, $uploadDir);
            if ($res === false) {
                $_SESSION['flash_error'] = "One of your uploaded files has an unsupported format or exceeds 10MB. Allowed: PDF, JPG, PNG, DOC, DOCX.";
                redirect(PROOT . "onboarding");
            }
            $uploadedPaths[$ff] = $res;
        }

        try {
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
                    policy_declaration, compliance_agreed, policy_procedures_agreed, reporting_agreed,
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
                    ?, ?, ?, ?,
                    ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?
                )
            ";
            $stmt = $dbConnection->prepare($sql);
            $stmt->execute([
                guidv4(), $staff_name, $staff_email, $staff_phone, $staff_address ?: null,
                $gov_id_type ?: null, $gov_id_number ?: null, $uploadedPaths['gov_id_file'],
                $ssn_number ?: null, $uploadedPaths['ssn_file'],
                $background_check_status ?: null, $uploadedPaths['background_check_file'],
                $fwa_status ?: null, $uploadedPaths['fwa_certificate_file'],
                $cred_types ?: null, $credential_number ?: null, $uploadedPaths['credential_file'],
                $chore_labor ?: null, $chore_labor_notes ?: null,
                $bank_name ?: null, $account_type ?: null, $routing_number ?: null, $account_number ?: null, $uploadedPaths['direct_deposit_file'],
                $w4_filing_status ?: null, $uploadedPaths['w4_file'],
                $i9_work_status ?: null, $uploadedPaths['i9_file'],
                $therap_status ?: null, $therap_username ?: null,
                $policy_declaration, $compliance_agreed, $policy_procedures_agreed, $reporting_agreed,
                $digital_signature, $signature_date,
                $drivers_license_number ?: null, $drivers_license_state ?: null, $drivers_license_expiry, $uploadedPaths['drivers_license_file'],
                $drivers_insurance_provider ?: null, $drivers_insurance_policy ?: null, $uploadedPaths['drivers_insurance_file'],
                $additional_notes ?: null
            ]);

            // Send confirmation email if configured
            $body = "
                <p>Dear " . htmlspecialchars($staff_name, ENT_QUOTES, 'UTF-8') . ",</p>
                <p>Thank you for submitting your <strong>Staff Onboarding</strong> packet to <strong>Care That Feels Like Home</strong>. Our HR and Compliance team has received your documentation and will review it shortly.</p>
                <p>Best regards,<br>Care That Feels Like Home Corporation</p>
            ";
            @send_email($staff_name, $staff_email, "Staff Onboarding Received - Care That Feels Like Home", $body);

            redirect(PROOT . "onboarding?submitted=1");
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Submission error: " . $e->getMessage();
            redirect(PROOT . "onboarding");
        }
    }
}

$isSubmitted = isset($_GET['submitted']) && $_GET['submitted'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Onboarding Form — Care That Feels Like Home</title>
    <link rel="icon" type="image/jpg" href="<?= PROOT; ?>assets/media/logo/logo.jpg" sizes="32x32">
    <!-- Google Fonts: Roboto & Plus Jakarta Sans (Google Forms typography) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5.2 -->
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

        /* Top minimal nav bar */
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

        /* Google Forms Banner Header Card (matching uploaded image) */
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

        /* White peak roof cutout matching the image's top green roof */
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
            width: 115px;
            height: 115px;
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
            font-size: 3.1rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            display: block;
        }

        .gf-brand-words .w-sub {
            font-size: 0.98rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            display: block;
            margin: 3px 0;
        }

        .gf-brand-words .w-home {
            font-size: 2.85rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            display: block;
        }

        /* Google Forms Section Header Tab (for Step 2) */
        .gf-section-banner {
            background: var(--gf-theme);
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 8px 8px 0 0;
            letter-spacing: 0.01em;
        }

        /* Stepper Pills */
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

        /* Google Forms Question Card */
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

        /* Left focus bar like Google Forms */
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

        /* Google Forms Bottom-Line Text Input */
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

        /* Google Forms Radio & Checkbox Items */
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

        /* Google Forms "Add file" Upload Box */
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

        /* Google Forms Navigation Buttons (Next / Back / Submit / Clear) */
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
            <div class="d-flex align-items-center gap-3">
                <?php if (!$isSubmitted): ?>
                    <div class="d-none d-sm-flex align-items-center gap-2">
                        <div class="gf-progress-bar">
                            <div class="gf-progress-fill" id="topProgressFill"></div>
                        </div>
                        <span class="small text-muted fw-medium" id="topProgressLabel">0 / 13 completed</span>
                    </div>
                <?php endif; ?>
                <a href="<?= PROOT; ?>" class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem;">
                    <i class="fas fa-arrow-left me-1"></i>Back to Website
                </a>
            </div>
        </div>
    </header>

    <div class="gf-container" id="formTopAnchor">
        <!-- Brand Banner Header Card (mirrors the attached Staff Onboarding image) -->
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
                <h1 class="h3 fw-normal mb-2" style="color: #202124;">Staff Onboarding</h1>
                <p class="text-muted small mb-3">
                    Welcome to the <strong>Care That Feels Like Home</strong> team! This onboarding packet is divided into <strong>2 simple steps</strong> covering all 13 onboarding requirements. Please complete each section and attach your documents.
                </p>
                <div class="small" style="color: var(--gf-danger);">* Indicates required question</div>
            </div>
        </div>

        <?php if ($isSubmitted): ?>
            <!-- Google Forms Style Confirmation Card -->
            <div class="gf-card gf-title-card p-4 p-md-5">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: var(--gf-theme-light); color: var(--gf-theme);">
                        <i class="fas fa-check fa-lg"></i>
                    </div>
                    <div>
                        <h2 class="h4 mb-1">Your response has been recorded.</h2>
                        <p class="text-muted small mb-0">Thank you for completing both steps of your Staff Onboarding packet.</p>
                    </div>
                </div>
                <p class="small text-secondary mb-4">
                    Our HR &amp; Compliance team will verify your documents, background check, and policy declarations. If any additional documentation is needed, we will contact you promptly.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= PROOT; ?>onboarding" class="text-decoration-none small fw-medium" style="color: var(--gf-theme);">
                        Submit another response
                    </a>
                    <span class="text-muted">&bull;</span>
                    <a href="<?= PROOT; ?>" class="text-decoration-none small fw-medium text-secondary">
                        Return to Home Page
                    </a>
                </div>
            </div>
        <?php else: ?>

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

            <form method="post" action="" enctype="multipart/form-data" id="onboardingForm" novalidate>

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
                                <input type="text" name="staff_name" class="gf-input" placeholder="Your answer" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-medium mb-1">Email Address <span class="gf-req">*</span></label>
                                <input type="email" name="staff_email" class="gf-input" placeholder="your.email@example.com" required>
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
                        </div>
                        <div class="gf-q-desc">Select your valid government-issued photo identification and upload a clear copy.</div>

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
                            <input type="text" name="gov_id_number" class="gf-input" placeholder="Government ID Number" required>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small">Upload Government ID Copy (Front/Back)</div>
                                <div class="gf-file-name" id="fn_gov_id_file">PDF, JPG, or PNG (Max 10MB)</div>
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
                        </div>
                        <div class="gf-q-desc">Enter your 9-digit Social Security Number for payroll &amp; tax verification, and optionally attach a copy of your SSN card.</div>

                        <div class="d-flex align-items-center gap-2 mb-3" style="max-width: 340px;">
                            <input type="password" name="ssn_number" id="ssnInput" class="gf-input" placeholder="XXX-XX-XXXX" maxlength="11" required>
                            <button type="button" class="btn btn-sm btn-light border" id="toggleSsnBtn" title="Show/Hide SSN">
                                <i class="far fa-eye" id="toggleSsnIcon"></i>
                            </button>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small">Upload Copy of Social Security Card</div>
                                <div class="gf-file-name" id="fn_ssn_file">PDF, JPG, or PNG (Max 10MB)</div>
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
                                <div class="fw-medium small">Upload Background Check Clearance / Authorization Form (if available)</div>
                                <div class="gf-file-name" id="fn_background_check_file">PDF, JPG, or PNG (Max 10MB)</div>
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
                                <div class="fw-medium small">Upload FWA Certificate</div>
                                <div class="gf-file-name" id="fn_fwa_certificate_file">PDF, JPG, or PNG (Max 10MB)</div>
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
                                <div class="fw-medium small">Upload SFN 749 Form or CNA / LPN / RN License</div>
                                <div class="gf-file-name" id="fn_credential_file">PDF, JPG, or PNG (Max 10MB)</div>
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
                            Please complete items 7 through 13 below, review and check all four mandatory onboarding policy declarations, and sign electronically to submit your packet.
                        </div>
                    </div>

                    <!-- 7. Direct deposit -->
                    <div class="gf-card onboarding-section step2-card" data-section="7">
                        <div class="gf-q-title">
                            <span class="gf-q-number">7</span>
                            <span>Direct deposit <span class="gf-req">*</span></span>
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
                                <input type="text" name="routing_number" class="gf-input" placeholder="9-digit routing number" maxlength="9" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">Account Number <span class="gf-req">*</span></label>
                                <input type="text" name="account_number" class="gf-input" placeholder="Account number" required>
                            </div>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small">Upload Voided Check or Bank Direct Deposit Form (Optional)</div>
                                <div class="gf-file-name" id="fn_direct_deposit_file">PDF, JPG, or PNG (Max 10MB)</div>
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
                        </div>
                        <div class="gf-q-desc">Select your federal tax withholding filing status and attach your completed IRS Form W-4.</div>

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
                                <div class="fw-medium small">Upload Signed Form W-4</div>
                                <div class="gf-file-name" id="fn_w4_file">PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="w4_file" class="d-none gf-file-input" data-target="fn_w4_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select your W-4 filing status.</div>
                    </div>

                    <!-- 9. I-9 form -->
                    <div class="gf-card onboarding-section step2-card" data-section="9">
                        <div class="gf-q-title">
                            <span class="gf-q-number">9</span>
                            <span>I-9 Form (Employment Eligibility Verification) <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">Attest to your employment authorization status under USCIS Form I-9 and upload your signed I-9 form.</div>

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
                                <div class="fw-medium small">Upload Signed Form I-9</div>
                                <div class="gf-file-name" id="fn_i9_file">PDF, JPG, or PNG (Max 10MB)</div>
                            </div>
                            <label class="gf-upload-btn">
                                <i class="fas fa-arrow-up-from-bracket"></i> Add file
                                <input type="file" name="i9_file" class="d-none gf-file-input" data-target="fn_i9_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                            </label>
                        </div>
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please select your I-9 work eligibility status.</div>
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

                    <!-- 11. Sign final Onboarding for new staff -->
                    <div class="gf-card onboarding-section step2-card" data-section="11">
                        <div class="gf-q-title">
                            <span class="gf-q-number">11</span>
                            <span>Sign final Onboarding for new staff <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">
                            Please review and check all four mandatory onboarding acknowledgments below, then type your full legal name as your binding electronic signature.
                        </div>

                        <div class="p-3 rounded-3 mb-3" style="background: #f8faf9; border: 1px solid #d4e8e1;">
                            <label class="gf-choice">
                                <input type="checkbox" name="policy_declaration" value="1" required>
                                <span><strong>Policy declaration</strong> — I have read, understood, and agree to abide by the Care That Feels Like Home Policy Declaration. <span class="gf-req">*</span></span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="compliance_agreed" value="1" required>
                                <span><strong>Compliance</strong> — I acknowledge and commit to upholding all state, federal, HIPAA, and organizational compliance requirements. <span class="gf-req">*</span></span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="policy_procedures_agreed" value="1" required>
                                <span><strong>Policy and procedures</strong> — I have reviewed and agree to follow all operational Policies and Procedures for client care and workplace safety. <span class="gf-req">*</span></span>
                            </label>
                            <label class="gf-choice">
                                <input type="checkbox" name="reporting_agreed" value="1" required>
                                <span><strong>Reporting</strong> — I understand my mandatory reporting duties for incidents, abuse/neglect prevention, and shift documentation. <span class="gf-req">*</span></span>
                            </label>
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
                        <div class="gf-error-msg"><i class="fas fa-circle-exclamation"></i> Please check all four policy declarations and provide your electronic signature.</div>
                    </div>

                    <!-- 12. Driver's license -->
                    <div class="gf-card onboarding-section step2-card" data-section="12">
                        <div class="gf-q-title">
                            <span class="gf-q-number">12</span>
                            <span>Driver's license <span class="gf-req">*</span></span>
                        </div>
                        <div class="gf-q-desc">Provide your Driver's License details and upload a clear photo or scan of your license.</div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="small text-muted">Driver's License Number <span class="gf-req">*</span></label>
                                <input type="text" name="drivers_license_number" class="gf-input" placeholder="License number" required>
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
                                <div class="fw-medium small">Upload Copy of Driver's License</div>
                                <div class="gf-file-name" id="fn_drivers_license_file">PDF, JPG, or PNG (Max 10MB)</div>
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
                        </div>
                        <div class="gf-q-desc">Provide your current auto insurance carrier &amp; policy number and attach a copy of your proof of insurance.</div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="small text-muted">Insurance Carrier / Provider <span class="gf-req">*</span></label>
                                <input type="text" name="drivers_insurance_provider" class="gf-input" placeholder="e.g., State Farm, Progressive, Geico" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">Policy Number <span class="gf-req">*</span></label>
                                <input type="text" name="drivers_insurance_policy" class="gf-input" placeholder="Policy number" required>
                            </div>
                        </div>

                        <div class="gf-upload-box">
                            <div>
                                <div class="fw-medium small">Upload Copy of Driver's Insurance Card / Declarations</div>
                                <div class="gf-file-name" id="fn_drivers_insurance_file">PDF, JPG, or PNG (Max 10MB)</div>
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
                                Submit
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
                    <p class="mb-1">Never submit passwords through forms. Secure Staff Onboarding Portal.</p>
                    <p class="mb-0"><strong>Care That Feels Like Home Corporation</strong> &mdash; Bismarck, ND</p>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?= $flash_user; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Display selected filename on upload buttons
        document.querySelectorAll('.gf-file-input').forEach(input => {
            input.addEventListener('change', function() {
                const labelEl = document.getElementById(this.dataset.target);
                if (this.files && this.files.length > 0) {
                    labelEl.innerHTML = '<i class="fas fa-file-circle-check text-success me-1"></i><strong>' + this.files[0].name + '</strong>';
                } else {
                    labelEl.textContent = 'PDF, JPG, or PNG (Max 10MB)';
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

                // Check required text/email/tel/password/date inputs & radio groups
                const reqInputs = card.querySelectorAll('input[required], select[required], textarea[required]');
                reqInputs.forEach(inp => {
                    if (inp.type === 'radio') {
                        const groupChecked = card.querySelector('input[name="' + inp.name + '"]:checked');
                        if (!groupChecked) cardValid = false;
                    } else if (inp.type === 'checkbox') {
                        if (!inp.checked) cardValid = false;
                    } else {
                        if (!inp.value.trim() || !inp.checkValidity()) cardValid = false;
                    }
                });

                // Check multi-checkbox groups (e.g. Question 5 & Question 6)
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

        // Clear error border as soon as user interacts with a card
        document.querySelectorAll('.gf-card').forEach(card => {
            card.addEventListener('input', () => card.classList.remove('gf-card-error'));
            card.addEventListener('change', () => card.classList.remove('gf-card-error'));
        });

        // Two-Step Navigation (Step 1 <-> Step 2)
        const step1Div = document.getElementById('formStep1');
        const step2Div = document.getElementById('formStep2');
        const pill1 = document.getElementById('stepPill1');
        const pill2 = document.getElementById('stepPill2');
        const badge1 = document.getElementById('stepBadge1');
        const goToStep2Btn = document.getElementById('goToStep2Btn');
        const backToStep1Btn = document.getElementById('backToStep1Btn');
        const formEl = document.getElementById('onboardingForm');

        function showStep(stepNum) {
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

        // Live completion progress tracker across the 13 sections
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
                    } else if (!['radio', 'checkbox', 'file', 'date'].includes(inp.type) && inp.value.trim() !== '') {
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
