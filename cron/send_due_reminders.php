<?php

// CLI script for cron: php cron/send_due_reminders.php

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

spl_autoload_register(function (string $class): void {
    $map = [
        'BaseModel'     => ROOT_PATH . '/app/Models/BaseModel.php',
        'UserModel'     => ROOT_PATH . '/app/Models/UserModel.php',
        'TaskModel'     => ROOT_PATH . '/app/Models/TaskModel.php',
        'EmailService'  => ROOT_PATH . '/app/Services/EmailService.php',
        'Database'      => ROOT_PATH . '/config/database.php',
    ];

    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}
if (!is_dir(EMAIL_QUEUE_PATH)) {
    mkdir(EMAIL_QUEUE_PATH, 0755, true);
}

try {
    $queued = EmailService::sendDueDateReminders();
    $processed = EmailService::processQueue(100);

    echo 'Due reminders queued: ' . $queued . PHP_EOL;
    echo 'Emails processed: ' . $processed . PHP_EOL;
} catch (Throwable $e) {
    error_log('Cron due reminders failed: ' . $e->getMessage());
    fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
