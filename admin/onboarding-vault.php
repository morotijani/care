<?php
/**
 * Admin-Only Encrypted Vault Endpoint for Staff Onboarding PII & Documents
 * - Strictly requires active Admin authentication
 * - Decrypts AES-256-GCM files & PII on-the-fly in memory (never writes plaintext to disk)
 * - Records every PII reveal & document download in the security audit trail
 */

include_once("../system/DatabaseConnector.php");
care_send_private_headers();

if (!admin_is_logged_in()) {
    http_response_code(403);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Unauthorized. Admin authentication required.']);
        exit;
    }
    exit('403 Forbidden — Authenticated HR/Admin session required.');
}

ensure_onboarding_security_schema($dbConnection);
$adminActor = $admin_data['user_fullname'] ?? 'Admin';

// 1. POST: On-demand AES-256-GCM PII decryption for a single onboarding dossier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reveal_pii') {
    header('Content-Type: application/json; charset=utf-8');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid onboarding packet ID.']);
        exit;
    }

    $stmt = $dbConnection->prepare("
        SELECT id, staff_name, gov_id_number, ssn_number, routing_number, account_number,
               drivers_license_number, drivers_insurance_policy
        FROM care_staff_onboarding
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Onboarding packet not found.']);
        exit;
    }

    care_log_onboarding_event(
        $dbConnection,
        'admin_pii_reveal',
        $adminActor . ' decrypted PII for packet #' . $id . ' (' . $row['staff_name'] . ')'
    );

    echo json_encode([
        'ok'   => true,
        'data' => [
            'gov_id_number'            => care_decrypt_pii($row['gov_id_number']) ?: '—',
            'ssn_number'               => care_decrypt_pii($row['ssn_number']) ?: '—',
            'routing_number'           => care_decrypt_pii($row['routing_number']) ?: '—',
            'account_number'           => care_decrypt_pii($row['account_number']) ?: '—',
            'drivers_license_number'   => care_decrypt_pii($row['drivers_license_number']) ?: '—',
            'drivers_insurance_policy' => care_decrypt_pii($row['drivers_insurance_policy']) ?: '—',
        ],
    ]);
    exit;
}

// 2. GET: Decrypt and stream an onboarding document from system/secure_vault/onboarding/
$id       = (int)($_GET['id'] ?? 0);
$field    = trim($_GET['field'] ?? '');
$download = isset($_GET['download']) && $_GET['download'] === '1';

$allowedFileFields = [
    'gov_id_file'            => 'GovID',
    'ssn_file'               => 'SSN_Card',
    'background_check_file'  => 'Background_Check',
    'fwa_certificate_file'   => 'FWA_Certificate',
    'credential_file'        => 'Credential_License',
    'direct_deposit_file'    => 'Direct_Deposit',
    'w4_file'                => 'W4_Form',
    'i9_file'                => 'I9_Form',
    'drivers_license_file'   => 'Drivers_License',
    'drivers_insurance_file' => 'Drivers_Insurance',
];

if ($id <= 0 || !isset($allowedFileFields[$field])) {
    http_response_code(400);
    exit('400 Bad Request — Invalid document parameters.');
}

$stmt = $dbConnection->prepare("SELECT id, staff_name, `$field` AS vault_ref FROM care_staff_onboarding WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || empty($row['vault_ref'])) {
    http_response_code(404);
    exit('404 Not Found — Document does not exist for this onboarding packet.');
}

$doc = care_read_encrypted_upload((string)$row['vault_ref']);
if (!$doc) {
    http_response_code(404);
    exit('404 Not Found — Encrypted vault blob could not be read or failed integrity verification.');
}

care_log_onboarding_event(
    $dbConnection,
    'admin_file_access',
    $adminActor . ($download ? ' downloaded ' : ' viewed ') . $allowedFileFields[$field] . ' for packet #' . $id
);

$inlineSafeMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
$disposition = (!$download && in_array($doc['mime'], $inlineSafeMimes, true)) ? 'inline' : 'attachment';

$ext = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION)) ?: 'bin';
$cleanStaff = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$row['staff_name']);
$outFilename = 'Packet_' . $id . '_' . $cleanStaff . '_' . $allowedFileFields[$field] . '.' . $ext;

// Clean any accidental output buffers before streaming binary bytes
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: ' . $doc['mime']);
header('Content-Disposition: ' . $disposition . '; filename="' . $outFilename . '"');
header('Content-Length: ' . $doc['size']);
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; object-src 'self'; plugin-types application/pdf");
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo $doc['bytes'];
exit;

