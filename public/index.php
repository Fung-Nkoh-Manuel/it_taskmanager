<?php

// ─── Bootstrap ───────────────────────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));

// ─── Load .env file if it exists ─────────────────────────────────────────────
$envFile = ROOT_PATH . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Strip quotes if present
            if (($value[0] ?? null) === '"' && ($value[-1] ?? null) === '"') {
                $value = substr($value, 1, -1);
            } elseif (($value[0] ?? null) === "'" && ($value[-1] ?? null) === "'") {
                $value = substr($value, 1, -1);
            }
            
            // Set as environment variable
            putenv("{$key}={$value}");
        }
    }
}

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

// ─── Autoloader (simple PSR-4-style) ─────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $map = [
        // Controllers
        'BaseController'          => ROOT_PATH . '/app/Controllers/BaseController.php',
        'SubtaskController'       => ROOT_PATH . '/app/Controllers/SubtaskController.php',
        'AuthController'          => ROOT_PATH . '/app/Controllers/AuthController.php',
        'DashboardController'     => ROOT_PATH . '/app/Controllers/DashboardController.php',
        'TaskController'          => ROOT_PATH . '/app/Controllers/TaskController.php',
        'CalendarController'      => ROOT_PATH . '/app/Controllers/CalendarController.php',
        'UserController'          => ROOT_PATH . '/app/Controllers/UserController.php',
        'NotificationController'  => ROOT_PATH . '/app/Controllers/NotificationController.php',
        'ProfileController'       => ROOT_PATH . '/app/Controllers/ProfileController.php',
        'LogController'           => ROOT_PATH . '/app/Controllers/LogController.php',
        'ApiController'           => ROOT_PATH . '/app/Controllers/ApiController.php',
        // Middleware
        'AuthMiddleware'          => ROOT_PATH . '/app/Middleware/AuthMiddleware.php',
        // Models
        'SubtaskModel'            => ROOT_PATH . '/app/Models/SubtaskModel.php',
        'BaseModel'               => ROOT_PATH . '/app/Models/BaseModel.php',
        'UserModel'               => ROOT_PATH . '/app/Models/UserModel.php',
        'TaskModel'               => ROOT_PATH . '/app/Models/TaskModel.php',
        'NotificationModel'       => ROOT_PATH . '/app/Models/NotificationModel.php',
        'LogModel'                => ROOT_PATH . '/app/Models/LogModel.php',
        // Services
        'Mailer'                  => ROOT_PATH . '/app/Services/Mailer.php',
        'EmailNotifier'           => ROOT_PATH . '/app/Services/EmailNotifier.php',
        // Infrastructure
        'Database'                => ROOT_PATH . '/config/database.php',
        'Router'                  => ROOT_PATH . '/routes/Router.php',
    ];

    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

// ─── Session ─────────────────────────────────────────────────────────────────
session_start();

// ─── Ensure upload/log dirs exist ────────────────────────────────────────────
foreach ([UPLOAD_PATH, LOG_PATH] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ─── Ensure Services dir exists ──────────────────────────────────────────────
$servicesDir = ROOT_PATH . '/app/Services';
if (!is_dir($servicesDir)) {
    mkdir($servicesDir, 0755, true);
}

// ─── Load routes & dispatch ──────────────────────────────────────────────────
require_once ROOT_PATH . '/routes/Router.php';
require_once ROOT_PATH . '/routes/web.php';

Router::dispatch();