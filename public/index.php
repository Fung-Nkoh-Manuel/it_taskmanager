<?php

// ─── Bootstrap ───────────────────────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

// ─── Autoloader (simple PSR-4-style) ─────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $map = [
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
        'AuthMiddleware'          => ROOT_PATH . '/app/Middleware/AuthMiddleware.php',
        'SubtaskModel'            => ROOT_PATH . '/app/Models/SubtaskModel.php',
        'BaseModel'               => ROOT_PATH . '/app/Models/BaseModel.php',
        'UserModel'               => ROOT_PATH . '/app/Models/UserModel.php',
        'TaskModel'               => ROOT_PATH . '/app/Models/TaskModel.php',
        'NotificationModel'       => ROOT_PATH . '/app/Models/NotificationModel.php',
        'LogModel'                => ROOT_PATH . '/app/Models/LogModel.php',
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

// ─── Load routes & dispatch ──────────────────────────────────────────────────
require_once ROOT_PATH . '/routes/Router.php';
require_once ROOT_PATH . '/routes/web.php';

Router::dispatch();