<?php

    // Auto-detect BASEURL & PROOT for both local XAMPP (/care/) and production root (/)
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
    $appRoot = rtrim(str_replace('\\', '/', __DIR__), '/');

    if (!empty($_ENV['APP_PROOT'])) {
        $detectedProot = '/' . trim($_ENV['APP_PROOT'], '/') . '/';
        if ($detectedProot === '//') {
            $detectedProot = '/';
        }
    } elseif ($docRoot !== '' && strpos(strtolower($appRoot), strtolower($docRoot)) === 0) {
        $subPath = trim(substr($appRoot, strlen($docRoot)), '/');
        $detectedProot = ($subPath === '') ? '/' : '/' . $subPath . '/';
    } else {
        $detectedProot = '/care/';
    }

    define('BASEURL', $appRoot . '/');
    define('PROOT', $detectedProot);

    // Load environment variables from .env safely
    define('RECAPTCHA_SITE_KEY_PUBLIC', $_ENV['RECAPTCHA_SITE_KEY_PUBLIC'] ?? '');
    define('RECAPTCHA_SITE_KEY_SECRETE', $_ENV['RECAPTCHA_SITE_KEY_SECRETE'] ?? '');
    define('RECAPTCHA_KEY', $_ENV['RECAPTCHA_KEY'] ?? '');

    define('MAIL_EMAIL', $_ENV['MAIL_EMAIL'] ?? '');
    define('MAIL_KEY', $_ENV['MAIL_KEY'] ?? '');
    define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? '');
    define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? '465');

    define('IPINFO_KEY', $_ENV['IPINFO_KEY'] ?? '');
    define('ONBOARDING_ENCRYPTION_KEY', $_ENV['ONBOARDING_ENCRYPTION_KEY'] ?? '');
