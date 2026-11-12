<?php
/**
 * Security, Cryptography (AES-256-GCM), Encrypted Document Vault,
 * CSRF Protection, Rate Limiting & Onboarding Access Control
 */

/**
 * Send strict privacy, anti-caching, and anti-indexing HTTP headers
 */
function care_send_private_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

/**
 * Generate or retrieve session CSRF token
 */
function csrf_token(): string
{
    if (empty($_SESSION['CARE_CSRF_TOKEN']) || !is_string($_SESSION['CARE_CSRF_TOKEN'])) {
        $_SESSION['CARE_CSRF_TOKEN'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['CARE_CSRF_TOKEN'];
}

/**
 * Render hidden HTML CSRF input field
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Constant-time verification of submitted CSRF token
 */
function verify_csrf_token(?string $submittedToken): bool
{
    if (empty($submittedToken) || empty($_SESSION['CARE_CSRF_TOKEN'])) {
        return false;
    }
    return hash_equals($_SESSION['CARE_CSRF_TOKEN'], (string)$submittedToken);
}

/**
 * Inspect and load 256-bit AES-256-GCM master key (auto-provisions into .env if missing & writable)
 */
function care_encryption_key_status(): array
{
    $raw = $_ENV['ONBOARDING_ENCRYPTION_KEY'] ?? (defined('ONBOARDING_ENCRYPTION_KEY') ? ONBOARDING_ENCRYPTION_KEY : '');
    $raw = trim((string)$raw);

    if ($raw === '') {
        $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envPath) && is_readable($envPath)) {
            $envContent = (string)file_get_contents($envPath);
            if (preg_match('/^ONBOARDING_ENCRYPTION_KEY\s*=\s*([^\r\n#]+)/m', $envContent, $m)) {
                $raw = trim($m[1], " \t\"'");
                $_ENV['ONBOARDING_ENCRYPTION_KEY'] = $raw;
            } elseif (is_writable($envPath)) {
                $generatedHex = bin2hex(random_bytes(32));
                $append = "\n# Auto-provisioned 256-bit AES-256-GCM Vault Key\nONBOARDING_ENCRYPTION_KEY=" . $generatedHex . "\n";
                if (@file_put_contents($envPath, $append, FILE_APPEND | LOCK_EX) !== false) {
                    $raw = $generatedHex;
                    $_ENV['ONBOARDING_ENCRYPTION_KEY'] = $raw;
                }
            }
        }
    }

    if ($raw !== '' && strlen($raw) >= 32) {
        if (ctype_xdigit($raw) && strlen($raw) === 64) {
            $keyBytes = (string)hex2bin($raw);
        } else {
            $keyBytes = hash('sha256', $raw, true);
        }
        return [
            'key'          => $keyBytes,
            'is_dedicated' => true,
            'cipher'       => 'AES-256-GCM',
        ];
    }

    // Emergency deterministic fallback if .env is read-only and missing ONBOARDING_ENCRYPTION_KEY
    $seed = (__DIR__) . '|' . ($_ENV['DB_DATABASE'] ?? 'care_db') . '|' . ($_ENV['MAIL_KEY'] ?? '') . '|' . ($_ENV['RECAPTCHA_KEY'] ?? '');
    return [
        'key'          => hash_hmac('sha256', $seed, 'care_vault_fallback_v1', true),
        'is_dedicated' => false,
        'cipher'       => 'AES-256-GCM',
    ];
}

function care_get_encryption_key(): string
{
    $info = care_encryption_key_status();
    return $info['key'];
}

/**
 * Encrypt sensitive database PII field with AES-256-GCM (12-byte random IV + 16-byte auth tag + AAD)
 */
function care_encrypt_pii(?string $plaintext): ?string
{
    if ($plaintext === null) {
        return null;
    }
    $trimmed = trim($plaintext);
    if ($trimmed === '') {
        return null;
    }
    if (strpos($trimmed, 'ENCv1:') === 0) {
        return $trimmed; // Already encrypted
    }

    $key = care_get_encryption_key();
    $iv  = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt(
        $trimmed,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        'care_pii_v1',
        16
    );
    if ($ciphertext === false || strlen($tag) !== 16) {
        throw new \RuntimeException('Failed to encrypt sensitive PII data.');
    }
    return 'ENCv1:' . base64_encode($iv . $tag . $ciphertext);
}

/**
 * Decrypt AES-256-GCM database PII field (transparently handles legacy unmigrated plaintext)
 */
function care_decrypt_pii(?string $stored): string
{
    if ($stored === null || $stored === '') {
        return '';
    }
    if (strpos($stored, 'ENCv1:') !== 0) {
        return $stored;
    }

    $b64 = substr($stored, 6);
    $raw = base64_decode($b64, true);
    if ($raw === false || strlen($raw) < 29) {
        return '[Corrupted Ciphertext]';
    }

    $iv         = substr($raw, 0, 12);
    $tag        = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);
    $key        = care_get_encryption_key();

    $plain = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        'care_pii_v1'
    );
    return ($plain !== false) ? $plain : '[Decryption Key Mismatch]';
}

/**
 * Mask PII value for safe default UI display (e.g. •••-••-1111)
 */
function care_mask_pii(?string $plaintext, string $type = 'generic'): string
{
    $val = trim((string)$plaintext);
    if ($val === '' || $val === '—') {
        return '—';
    }
    if (strpos($val, '[') === 0) {
        return $val;
    }

    if ($type === 'ssn') {
        $digits = preg_replace('/\D/', '', $val);
        if (strlen($digits) >= 4) {
            return '•••-••-' . substr($digits, -4);
        }
        return '•••-••-••••';
    }

    if (strlen($val) <= 4) {
        return str_repeat('•', strlen($val));
    }
    return str_repeat('•', min(8, strlen($val) - 4)) . substr($val, -4);
}

/**
 * Return and initialize the locked non-public vault directory
 */
function care_vault_dir(): string
{
    $baseVault = __DIR__ . DIRECTORY_SEPARATOR . 'secure_vault';
    $obVault   = $baseVault . DIRECTORY_SEPARATOR . 'onboarding';

    $htaccessContent = "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
    $indexContent    = "<?php http_response_code(403); exit('403 Forbidden');\n";

    foreach ([$baseVault, $obVault] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        if (!is_file($dir . DIRECTORY_SEPARATOR . '.htaccess')) {
            @file_put_contents($dir . DIRECTORY_SEPARATOR . '.htaccess', $htaccessContent);
        }
        if (!is_file($dir . DIRECTORY_SEPARATOR . 'index.php')) {
            @file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.php', $indexContent);
        }
    }

    return $obVault;
}

/**
 * Encrypt raw file bytes + metadata envelope into a random .vault blob using AES-256-GCM
 */
function care_write_vault_blob(string $rawBytes, string $origName, string $mimeType, string $ext): ?string
{
    $vaultDir = care_vault_dir();
    $safeOrig = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($origName));
    if ($safeOrig === '' || $safeOrig[0] === '.') {
        $safeOrig = 'document.' . $ext;
    }

    $metaJson = json_encode([
        'orig_name' => $safeOrig,
        'mime'      => $mimeType,
        'ext'       => $ext,
        'size'      => strlen($rawBytes),
        'sha256'    => hash('sha256', $rawBytes),
        'uploaded'  => gmdate('c'),
    ], JSON_UNESCAPED_SLASHES);

    $payload = pack('N', strlen($metaJson)) . $metaJson . $rawBytes;
    $key     = care_get_encryption_key();
    $iv      = random_bytes(12);
    $tag     = '';

    $ciphertext = openssl_encrypt(
        $payload,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        'care_vault_v1',
        16
    );
    if ($ciphertext === false || strlen($tag) !== 16) {
        return null;
    }

    $vaultFilename = bin2hex(random_bytes(16)) . '.vault';
    $destPath      = $vaultDir . DIRECTORY_SEPARATOR . $vaultFilename;
    $envelope      = 'CVLT1' . $iv . $tag . $ciphertext;

    if (@file_put_contents($destPath, $envelope, LOCK_EX) === false) {
        return null;
    }
    @chmod($destPath, 0640);

    return 'vault:' . $vaultFilename;
}

/**
 * Validate uploaded document (extension + finfo magic bytes + size) and encrypt at rest into secure_vault
 * Returns:
 *   null   -> No file uploaded for this field
 *   false  -> Invalid file type, magic-byte mismatch, or > 10MB
 *   string -> 'vault:<32-hex>.vault'
 */
function care_store_encrypted_upload(string $fieldKey)
{
    if (!isset($_FILES[$fieldKey]) || $_FILES[$fieldKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$fieldKey]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $tmpName  = $_FILES[$fieldKey]['tmp_name'] ?? '';
    $origName = $_FILES[$fieldKey]['name'] ?? '';
    $fileSize = (int)($_FILES[$fieldKey]['size'] ?? 0);

    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return false;
    }
    if ($fileSize <= 0 || $fileSize > 10 * 1024 * 1024) {
        return false;
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowedMimeMap = [
        'application/pdf'                                                         => ['pdf'],
        'image/jpeg'                                                              => ['jpg', 'jpeg'],
        'image/png'                                                               => ['png'],
        'image/webp'                                                              => ['webp'],
        'application/msword'                                                      => ['doc'],
        'application/CDFV2'                                                       => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
    ];

    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
    if (!in_array($ext, $allowedExts, true)) {
        return false;
    }

    // Inspect magic bytes via finfo
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $detectedMime = (string)$finfo->file($tmpName);

    // Some docx files report application/zip on older magic dbs; verify zip contains word/document.xml
    if ($detectedMime === 'application/zip' && $ext === 'docx') {
        $rawCheck = (string)file_get_contents($tmpName, false, null, 0, 4096);
        if (strpos($rawCheck, 'word/') !== false || strpos($rawCheck, '[Content_Types].xml') !== false) {
            $detectedMime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }
    }

    if (!isset($allowedMimeMap[$detectedMime]) || !in_array($ext, $allowedMimeMap[$detectedMime], true)) {
        return false;
    }

    $rawBytes = @file_get_contents($tmpName);
    @unlink($tmpName);
    if ($rawBytes === false || $rawBytes === '') {
        return false;
    }

    $vaultRef = care_write_vault_blob($rawBytes, $origName, $detectedMime, $ext);
    return $vaultRef !== null ? $vaultRef : false;
}

/**
 * Migrate an existing legacy disk file into the AES-256-GCM vault and delete the plaintext file
 */
function care_encrypt_existing_file_to_vault(string $legacyRelPath): ?string
{
    if (strpos($legacyRelPath, 'vault:') === 0) {
        return $legacyRelPath;
    }
    $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($legacyRelPath, '/\\'));
    if (!is_file($fullPath) || !is_readable($fullPath)) {
        return null;
    }

    $rawBytes = @file_get_contents($fullPath);
    if ($rawBytes === false || $rawBytes === '') {
        return null;
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime  = (string)$finfo->buffer($rawBytes);
    $ext   = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) ?: 'bin';

    $vaultRef = care_write_vault_blob($rawBytes, basename($fullPath), $mime, $ext);
    if ($vaultRef !== null) {
        @unlink($fullPath);
        return $vaultRef;
    }
    return null;
}

/**
 * Read and decrypt an onboarding document in memory from secure_vault
 */
function care_read_encrypted_upload(string $storedRef): ?array
{
    $storedRef = trim($storedRef);
    if ($storedRef === '') {
        return null;
    }

    if (strpos($storedRef, 'vault:') === 0) {
        $vaultFile = basename(substr($storedRef, 6));
        if (!preg_match('/^[a-f0-9]{32}\.vault$/', $vaultFile)) {
            return null;
        }
        $fullPath = care_vault_dir() . DIRECTORY_SEPARATOR . $vaultFile;
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            return null;
        }

        $envelope = @file_get_contents($fullPath);
        if ($envelope === false || strlen($envelope) < 38 || substr($envelope, 0, 5) !== 'CVLT1') {
            return null;
        }

        $iv         = substr($envelope, 5, 12);
        $tag        = substr($envelope, 17, 16);
        $ciphertext = substr($envelope, 33);
        $key        = care_get_encryption_key();

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'care_vault_v1'
        );
        if ($decrypted === false || strlen($decrypted) < 6) {
            return null;
        }

        $unpacked = unpack('Nlen', substr($decrypted, 0, 4));
        $metaLen  = (int)($unpacked['len'] ?? 0);
        if ($metaLen <= 0 || strlen($decrypted) < (4 + $metaLen)) {
            return null;
        }

        $metaJson = substr($decrypted, 4, $metaLen);
        $rawBytes = substr($decrypted, 4 + $metaLen);
        $meta     = json_decode($metaJson, true);
        if (!is_array($meta)) {
            return null;
        }

        if (!empty($meta['sha256']) && !hash_equals($meta['sha256'], hash('sha256', $rawBytes))) {
            return null;
        }

        return [
            'bytes'    => $rawBytes,
            'mime'     => $meta['mime'] ?? 'application/octet-stream',
            'filename' => $meta['orig_name'] ?? 'onboarding_document',
            'size'     => strlen($rawBytes),
            'encrypted'=> true,
        ];
    }

    // Legacy fallback if any unmigrated file exists
    $legacyFull = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($storedRef, '/\\'));
    $realRoot   = realpath(dirname(__DIR__));
    $realFile   = realpath($legacyFull);
    if ($realRoot && $realFile && strpos($realFile, $realRoot) === 0 && is_file($realFile)) {
        $bytes = @file_get_contents($realFile);
        if ($bytes !== false) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            return [
                'bytes'     => $bytes,
                'mime'      => (string)$finfo->buffer($bytes),
                'filename'  => basename($realFile),
                'size'      => strlen($bytes),
                'encrypted' => false,
            ];
        }
    }

    return null;
}

/**
 * Permanently delete an encrypted .vault file (or legacy file)
 */
function care_delete_vault_file(?string $storedRef): void
{
    $storedRef = trim((string)$storedRef);
    if ($storedRef === '') {
        return;
    }

    if (strpos($storedRef, 'vault:') === 0) {
        $vaultFile = basename(substr($storedRef, 6));
        if (preg_match('/^[a-f0-9]{32}\.vault$/', $vaultFile)) {
            $path = care_vault_dir() . DIRECTORY_SEPARATOR . $vaultFile;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        return;
    }

    $legacyFull = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($storedRef, '/\\'));
    if (is_file($legacyFull)) {
        @unlink($legacyFull);
    }
}

/**
 * Resolve visitor IP safely
 */
function care_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return substr(trim((string)$ip), 0, 64);
}

/**
 * Get/Set Onboarding Security Settings
 */
function care_get_onboarding_setting(\PDO $db, string $key, ?string $default = null): ?string
{
    $stmt = $db->prepare("SELECT setting_value FROM care_onboarding_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return ($val !== false && $val !== null) ? (string)$val : $default;
}

function care_set_onboarding_setting(\PDO $db, string $key, ?string $value): void
{
    $stmt = $db->prepare("
        INSERT INTO care_onboarding_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([$key, $value]);
}

/**
 * Audit & rate-limit event logger
 */
function care_log_onboarding_event(\PDO $db, string $eventType, ?string $details = null): void
{
    try {
        $stmt = $db->prepare("
            INSERT INTO care_onboarding_attempts (ip_address, event_type, details)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([care_client_ip(), substr($eventType, 0, 40), $details ? substr($details, 0, 255) : null]);
    } catch (\Throwable $e) {
        // Ignore logging failure silently
    }
}

/**
 * Check brute-force rate limit (5 failed attempts within 15 minutes locks IP for 15 minutes)
 */
function care_is_onboarding_rate_limited(\PDO $db): array
{
    $ip = care_client_ip();
    $stmt = $db->prepare("
        SELECT COUNT(*) AS cnt, MAX(created_at) AS last_attempt
        FROM care_onboarding_attempts
        WHERE ip_address = ?
          AND event_type = 'auth_fail'
          AND created_at >= (NOW() - INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$ip]);
    $row = $stmt->fetch();
    $attempts = (int)($row['cnt'] ?? 0);

    if ($attempts >= 5 && !empty($row['last_attempt'])) {
        $elapsed = max(0, time() - strtotime((string)$row['last_attempt']));
        $remMins = max(1, (int)ceil((900 - $elapsed) / 60));
        return ['locked' => true, 'remaining_minutes' => $remMins, 'attempts' => $attempts];
    }

    return ['locked' => false, 'remaining_minutes' => 0, 'attempts' => $attempts];
}

/**
 * Generate a single-use or expiring Staff Onboarding Invite Code (returns plaintext ONCE)
 */
function care_create_onboarding_invite(
    \PDO $db,
    string $staffName,
    string $staffEmail,
    int $expiryHours = 72,
    int $maxUses = 1,
    string $createdBy = 'Admin'
): array {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $alphaLen = strlen($alphabet);
    $part1 = '';
    $part2 = '';
    for ($i = 0; $i < 4; $i++) {
        $part1 .= $alphabet[random_int(0, $alphaLen - 1)];
        $part2 .= $alphabet[random_int(0, $alphaLen - 1)];
    }

    $prefix    = 'CTFLH-' . $part1;
    $fullCode  = $prefix . '-' . $part2;
    $codeHint  = $prefix . '-••••';
    $codeHash  = password_hash($fullCode, PASSWORD_BCRYPT, ['cost' => 12]);
    $inviteId  = guidv4();
    $expiresAt = date('Y-m-d H:i:s', time() + max(1, $expiryHours) * 3600);
    $maxUses   = max(1, min(100, $maxUses));

    $stmt = $db->prepare("
        INSERT INTO care_onboarding_invites (
            invite_id, staff_name, staff_email, code_prefix, code_hint, code_hash,
            max_uses, used_count, expires_at, is_revoked, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 0, ?)
    ");
    $stmt->execute([
        $inviteId,
        $staffName !== '' ? $staffName : null,
        $staffEmail !== '' ? $staffEmail : null,
        $prefix,
        $codeHint,
        $codeHash,
        $maxUses,
        $expiresAt,
        $createdBy,
    ]);

    return [
        'invite_id'  => $inviteId,
        'code'       => $fullCode,
        'expires_at' => $expiresAt,
        'max_uses'   => $maxUses,
        'staff_name' => $staffName,
        'staff_email'=> $staffEmail,
    ];
}

/**
 * Verify either an Individual Invite Code (CTFLH-XXXX-YYYY) or the HR Master Portal Password
 */
function care_verify_onboarding_passcode(\PDO $db, string $rawInput): array
{
    $trimmed = trim($rawInput);
    if ($trimmed === '') {
        return ['valid' => false, 'reason' => 'Please enter your HR Onboarding Access Passcode.'];
    }

    // 1. Check Individual Staff Invite Passcode (case-insensitive normalized)
    $upperNormalized = strtoupper(str_replace(' ', '', $trimmed));
    if (preg_match('/^(CTFLH-[A-Z0-9]{4})-[A-Z0-9]{4}$/', $upperNormalized, $m)) {
        $prefix = $m[1];
        $stmt = $db->prepare("
            SELECT * FROM care_onboarding_invites
            WHERE code_prefix = ?
              AND is_revoked = 0
              AND expires_at > NOW()
              AND used_count < max_uses
            ORDER BY id DESC
        ");
        $stmt->execute([$prefix]);
        $candidates = $stmt->fetchAll();

        foreach ($candidates as $inv) {
            if (password_verify($upperNormalized, $inv['code_hash'])) {
                return [
                    'valid'       => true,
                    'mode'        => 'invite',
                    'invite_id'   => (int)$inv['id'],
                    'code_hint'   => $inv['code_hint'],
                    'staff_name'  => $inv['staff_name'] ?? '',
                    'staff_email' => $inv['staff_email'] ?? '',
                ];
            }
        }
    }

    // 2. Check Master Onboarding Portal Password (if enabled by HR)
    $allowMaster = care_get_onboarding_setting($db, 'allow_master_passcode', '1');
    if ($allowMaster === '1') {
        $masterHash = care_get_onboarding_setting($db, 'master_passcode_hash', '');
        if ($masterHash !== '' && password_verify($trimmed, $masterHash)) {
            return [
                'valid'       => true,
                'mode'        => 'master',
                'invite_id'   => null,
                'code_hint'   => 'Master Portal Password',
                'staff_name'  => '',
                'staff_email' => '',
            ];
        }
    }

    return ['valid' => false, 'reason' => 'Invalid or expired Onboarding Access Passcode. Please contact HR for an active invite code.'];
}

/**
 * Idempotent schema initialization + automatic encryption migration of any legacy plaintext records/files
 */
function ensure_onboarding_security_schema(\PDO $db): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;

    // Ensure vault directory & .htaccess locks exist
    care_vault_dir();

    // Create settings, invites, and rate-limit/audit tables if missing
    $db->exec("
        CREATE TABLE IF NOT EXISTS `care_onboarding_settings` (
            `setting_key` varchar(64) NOT NULL,
            `setting_value` text DEFAULT NULL,
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `care_onboarding_invites` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `invite_id` varchar(100) NOT NULL,
            `staff_name` varchar(120) DEFAULT NULL,
            `staff_email` varchar(120) DEFAULT NULL,
            `code_prefix` varchar(20) NOT NULL,
            `code_hint` varchar(30) NOT NULL,
            `code_hash` varchar(255) NOT NULL,
            `max_uses` int(11) NOT NULL DEFAULT 1,
            `used_count` int(11) NOT NULL DEFAULT 0,
            `expires_at` datetime NOT NULL,
            `is_revoked` tinyint(1) NOT NULL DEFAULT 0,
            `created_by` varchar(100) DEFAULT NULL,
            `last_used_at` datetime DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_ob_invite_id` (`invite_id`),
            KEY `idx_ob_code_prefix` (`code_prefix`),
            KEY `idx_ob_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `care_onboarding_attempts` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `ip_address` varchar(64) NOT NULL,
            `event_type` varchar(40) NOT NULL,
            `details` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_ob_attempt_lookup` (`ip_address`, `event_type`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // Seed initial master passcode if none exists yet
    $existingHash = care_get_onboarding_setting($db, 'master_passcode_hash');
    if ($existingHash === null || $existingHash === '') {
        care_set_onboarding_setting($db, 'master_passcode_hash', password_hash('CareHome#2026!', PASSWORD_BCRYPT, ['cost' => 12]));
        care_set_onboarding_setting($db, 'allow_master_passcode', '1');
        care_set_onboarding_setting($db, 'master_passcode_is_default', '1');
    }

    // Run one-time column widening & legacy plaintext -> AES-256-GCM vault migration
    if (care_get_onboarding_setting($db, 'schema_migrated_v2') !== '1') {
        try {
            $db->exec("
                ALTER TABLE `care_staff_onboarding`
                    MODIFY `gov_id_number` varchar(512) DEFAULT NULL,
                    MODIFY `ssn_number` varchar(512) DEFAULT NULL,
                    MODIFY `routing_number` varchar(512) DEFAULT NULL,
                    MODIFY `account_number` varchar(512) DEFAULT NULL,
                    MODIFY `drivers_license_number` varchar(512) DEFAULT NULL,
                    MODIFY `drivers_insurance_policy` varchar(512) DEFAULT NULL
            ");
        } catch (\Throwable $e) {
            // Table might not exist yet or already widened
        }

        try {
            $rows = $db->query("SELECT * FROM care_staff_onboarding")->fetchAll();
            $piiCols = [
                'gov_id_number',
                'ssn_number',
                'routing_number',
                'account_number',
                'drivers_license_number',
                'drivers_insurance_policy',
            ];
            $fileCols = [
                'gov_id_file',
                'ssn_file',
                'background_check_file',
                'fwa_certificate_file',
                'credential_file',
                'direct_deposit_file',
                'w4_file',
                'i9_file',
                'drivers_license_file',
                'drivers_insurance_file',
            ];

            foreach ($rows as $row) {
                $updates = [];
                $params  = [];

                foreach ($piiCols as $col) {
                    if (!empty($row[$col]) && strpos((string)$row[$col], 'ENCv1:') !== 0) {
                        $updates[] = "`$col` = ?";
                        $params[]  = care_encrypt_pii((string)$row[$col]);
                    }
                }

                foreach ($fileCols as $fCol) {
                    if (!empty($row[$fCol]) && strpos((string)$row[$fCol], 'vault:') !== 0) {
                        $migratedRef = care_encrypt_existing_file_to_vault((string)$row[$fCol]);
                        if ($migratedRef !== null) {
                            $updates[] = "`$fCol` = ?";
                            $params[]  = $migratedRef;
                        }
                    }
                }

                if (!empty($updates)) {
                    $params[] = (int)$row['id'];
                    $upSql = "UPDATE care_staff_onboarding SET " . implode(', ', $updates) . " WHERE id = ?";
                    $upStmt = $db->prepare($upSql);
                    $upStmt->execute($params);
                }
            }

            care_set_onboarding_setting($db, 'schema_migrated_v2', '1');
        } catch (\Throwable $e) {
            // Will retry safely on next load if table wasn't ready
        }
    }

    // Add the 4 signed final-onboarding document columns (Item 11 uploads) if not yet present
    if (care_get_onboarding_setting($db, 'schema_migrated_v3') !== '1') {
        $newFileCols = [
            'policy_declaration_file' => "ALTER TABLE `care_staff_onboarding` ADD COLUMN `policy_declaration_file` varchar(255) DEFAULT NULL AFTER `policy_declaration`",
            'compliance_file'         => "ALTER TABLE `care_staff_onboarding` ADD COLUMN `compliance_file` varchar(255) DEFAULT NULL AFTER `compliance_agreed`",
            'policy_procedures_file'  => "ALTER TABLE `care_staff_onboarding` ADD COLUMN `policy_procedures_file` varchar(255) DEFAULT NULL AFTER `policy_procedures_agreed`",
            'reporting_file'          => "ALTER TABLE `care_staff_onboarding` ADD COLUMN `reporting_file` varchar(255) DEFAULT NULL AFTER `reporting_agreed`",
        ];
        foreach ($newFileCols as $colName => $alterSql) {
            try {
                $chk = $db->query("SHOW COLUMNS FROM `care_staff_onboarding` LIKE '$colName'")->fetch();
                if (!$chk) {
                    $db->exec($alterSql);
                }
            } catch (\Throwable $e) {
                // Ignore if column already exists
            }
        }
        care_set_onboarding_setting($db, 'schema_migrated_v3', '1');
    }
}

/**
 * Resolve one of the 6 official blank onboarding PDF templates from assets/media/documents/
 */
function care_resolve_onboarding_template(string $key): ?array
{
    $docsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . 'documents';
    if (!is_dir($docsDir)) {
        return null;
    }

    $catalog = [
        'w4' => [
            'exact'         => 'W-4 . Employee’s Withholding Certificate.pdf',
            'prefix'        => 'w-4',
            'download_name' => 'W-4_Employees_Withholding_Certificate.pdf',
            'title'         => 'Form W-4 — Employee’s Withholding Certificate',
        ],
        'i9' => [
            'exact'         => 'Form I-9, Employment Eligibility Verification.pdf',
            'prefix'        => 'form i-9',
            'download_name' => 'Form_I-9_Employment_Eligibility_Verification.pdf',
            'title'         => 'Form I-9 — Employment Eligibility Verification',
        ],
        'policy_declaration' => [
            'exact'         => 'Policy Declaration - Care That Feels Like Home.pdf',
            'prefix'        => 'policy declaration',
            'download_name' => 'Policy_Declaration_Care_That_Feels_Like_Home.pdf',
            'title'         => 'Policy Declaration — Care That Feels Like Home',
        ],
        'compliance' => [
            'exact'         => 'Compliance - Care That Feels Like Home.pdf',
            'prefix'        => 'compliance',
            'download_name' => 'Compliance_Care_That_Feels_Like_Home.pdf',
            'title'         => 'Compliance — Care That Feels Like Home',
        ],
        'policy_procedures' => [
            'exact'         => 'Policy and Procedures  - Care That Feels Like Home.pdf',
            'prefix'        => 'policy and procedures',
            'download_name' => 'Policy_and_Procedures_Care_That_Feels_Like_Home.pdf',
            'title'         => 'Policy and Procedures — Care That Feels Like Home',
        ],
        'reporting' => [
            'exact'         => 'Report - Care That Feels Like Home.pdf',
            'prefix'        => 'report -',
            'download_name' => 'Reporting_Care_That_Feels_Like_Home.pdf',
            'title'         => 'Reporting — Care That Feels Like Home',
        ],
    ];

    if (!isset($catalog[$key])) {
        return null;
    }

    $entry = $catalog[$key];
    $exactPath = $docsDir . DIRECTORY_SEPARATOR . $entry['exact'];
    if (is_file($exactPath) && is_readable($exactPath)) {
        return [
            'path'          => $exactPath,
            'download_name' => $entry['download_name'],
            'title'         => $entry['title'],
        ];
    }

    // Fallback scan for cross-OS Unicode filename differences (e.g. curly apostrophe on Linux vs Windows)
    $files = @scandir($docsDir);
    if (is_array($files)) {
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            if (stripos($f, $entry['prefix']) === 0 && strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf') {
                $candidate = $docsDir . DIRECTORY_SEPARATOR . $f;
                if (is_file($candidate) && is_readable($candidate)) {
                    return [
                        'path'          => $candidate,
                        'download_name' => $entry['download_name'],
                        'title'         => $entry['title'],
                    ];
                }
            }
        }
    }

    return null;
}

