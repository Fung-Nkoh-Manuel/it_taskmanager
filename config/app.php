<?php

// ─── Environment ────────────────────────────────────────────────────────────
define('APP_ENV',     getenv('APP_ENV')  ?: 'development');  // 'production' on server
define('APP_NAME',    'IT TaskManager');
define('APP_VERSION', '1.0.0');
define('APP_URL',     getenv('APP_URL')  ?: 'http://localhost/it-taskmanager/public');

// ─── Paths ───────────────────────────────────────────────────────────────────
define('BASE_PATH',    dirname(__DIR__));
define('APP_PATH',     BASE_PATH . '/app');
define('VIEW_PATH',    BASE_PATH . '/views');
define('CONFIG_PATH',  BASE_PATH . '/config');
define('PUBLIC_PATH',  BASE_PATH . '/public');
define('UPLOAD_PATH',  PUBLIC_PATH . '/uploads');
define('LOG_PATH',     BASE_PATH . '/logs');
define('UPLOADS_URL',  APP_URL . '/uploads');
define('EMAIL_QUEUE_PATH', LOG_PATH . '/email_queue');

// ─── Pagination ───────────────────────────────────────────────────────────────
define('ITEMS_PER_PAGE', 15);

// ─── Upload limits ────────────────────────────────────────────────────────────
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);   // 10 MB
define('ALLOWED_MIME_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/zip', 'application/x-zip-compressed',
    'text/plain', 'text/csv',
]);

// ─── Email (SMTP or mail()) ─────────────────────────────────────────────────
define('EMAIL_ENABLED', filter_var(getenv('EMAIL_ENABLED') ?: '0', FILTER_VALIDATE_BOOL));
define('EMAIL_DRIVER', getenv('EMAIL_DRIVER') ?: 'smtp');
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 587));
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls'); // tls, ssl, none
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: '');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);

// ─── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Douala');

// ─── Error reporting ──────────────────────────────────────────────────────────
if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// ─── PHP error log ────────────────────────────────────────────────────────────
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . '/php_errors.log');

// ─── Session hardening ────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
// Set session.cookie_secure to 1 only when running over HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
