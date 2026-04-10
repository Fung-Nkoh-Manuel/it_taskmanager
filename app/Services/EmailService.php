<?php

class EmailService
{
    private static bool $shutdownRegistered = false;
    private static bool $phpMailerBootstrapped = false;

    // ── Public triggers ───────────────────────────────────────────────────────

    public static function sendActivityLogAlert(array $log): void
    {
        $admins = self::adminRecipients();
        if (empty($admins)) return;

        $subject = 'Activity Log Alert: ' . strtoupper((string)($log['action'] ?? 'event'));
        $actor   = self::escape((string)($log['actor_name'] ?? 'System'));
        $action  = self::escape((string)($log['action'] ?? 'unknown'));
        $entity  = self::escape((string)($log['entity'] ?? 'n/a'));
        $details = self::escape((string)($log['details'] ?? ''));
        $ip      = self::escape((string)($log['ip_address'] ?? 'n/a'));

        $html = self::layout('Activity Log Alert', "
            <p>A new activity log entry was recorded.</p>
            <ul>
                <li><strong>Actor:</strong> {$actor}</li>
                <li><strong>Action:</strong> {$action}</li>
                <li><strong>Entity:</strong> {$entity}</li>
                <li><strong>Details:</strong> {$details}</li>
                <li><strong>IP:</strong> {$ip}</li>
                <li><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</li>
            </ul>
        ");

        foreach ($admins as $admin) {
            self::queueEmail([
                'to'          => $admin['email'],
                'subject'     => $subject,
                'html'        => $html,
                'email_type'  => 'activity_log_alert',
                'context_key' => 'activity_log_' . (int)($log['id'] ?? 0) . '_' . (int)$admin['id'],
            ]);
        }
    }

    public static function sendRoleChanged(array $user, string $oldRole, string $newRole): void
    {
        if (empty($user['email'])) return;

        $subject = 'Your role has been updated';
        $html = self::layout('Role Change Notification', "
            <p>Hello " . self::escape((string)$user['full_name']) . ",</p>
            <p>Your account role has been changed by an administrator.</p>
            <ul>
                <li><strong>Previous role:</strong> " . self::escape($oldRole) . "</li>
                <li><strong>New role:</strong> " . self::escape($newRole) . "</li>
            </ul>
            <p>You can log in here: <a href='" . APP_URL . "/login'>" . APP_URL . "/login</a></p>
        ");

        self::queueEmail([
            'to'          => $user['email'],
            'subject'     => $subject,
            'html'        => $html,
            'email_type'  => 'role_changed',
            'context_key' => 'role_changed_' . (int)$user['id'] . '_' . time(),
        ]);
    }

    public static function sendTaskAssigned(int $userId, int $taskId): void
    {
        $user = self::userById($userId);
        $task = self::taskById($taskId);
        if (!$user || !$task || empty($user['email'])) return;

        $subject = 'Task Assigned: ' . (string)$task['title'];
        $html = self::layout('New Task Assignment', "
            <p>Hello " . self::escape((string)$user['full_name']) . ",</p>
            <p>You have been assigned to a task.</p>
            <ul>
                <li><strong>Title:</strong> " . self::escape((string)$task['title']) . "</li>
                <li><strong>Priority:</strong> " . self::escape((string)$task['priority']) . "</li>
                <li><strong>Due date:</strong> " . self::escape((string)($task['due_date'] ?: 'N/A')) . "</li>
            </ul>
            <p><a href='" . APP_URL . '/tasks/' . (int)$taskId . "'>Open task</a></p>
        ");

        self::queueEmail([
            'to'          => $user['email'],
            'subject'     => $subject,
            'html'        => $html,
            'email_type'  => 'task_assignment',
            'context_key' => 'task_assignment_' . $taskId . '_' . $userId . '_' . date('YmdHis'),
        ]);
    }

    public static function sendTaskStatusChanged(int $taskId, string $oldStatus, string $newStatus): void
    {
        $task = self::taskById($taskId);
        if (!$task) return;

        $recipients = self::taskCreatorAndAssignees($taskId);
        if (empty($recipients)) return;

        $subject = 'Task Status Updated: ' . (string)$task['title'];
        $html = self::layout('Task Status Change', "
            <p>The status of task <strong>" . self::escape((string)$task['title']) . "</strong> has changed.</p>
            <ul>
                <li><strong>Previous status:</strong> " . self::escape($oldStatus) . "</li>
                <li><strong>New status:</strong> " . self::escape($newStatus) . "</li>
            </ul>
            <p><a href='" . APP_URL . '/tasks/' . (int)$taskId . "'>View task</a></p>
        ");

        foreach ($recipients as $r) {
            self::queueEmail([
                'to'          => $r['email'],
                'subject'     => $subject,
                'html'        => $html,
                'email_type'  => 'task_status_changed',
                'context_key' => 'task_status_' . $taskId . '_' . (int)$r['id'] . '_' . date('YmdHis'),
            ]);
        }
    }

    public static function sendDueDateReminders(): int
    {
        $rows = self::db()->query(" 
            SELECT DISTINCT
                t.id AS task_id,
                t.title,
                t.due_date,
                u.id AS user_id,
                u.email,
                u.full_name
            FROM tasks t
            JOIN task_assignments ta ON ta.task_id = t.id
            JOIN users u ON u.id = ta.user_id
            WHERE t.status != 'termine'
              AND t.due_date IS NOT NULL
              AND t.due_date <= DATE_ADD(NOW(), INTERVAL 1 DAY)
              AND t.due_date >= CURDATE()
              AND u.is_active = 1
              AND u.email IS NOT NULL
              AND u.email != ''
        ")->fetchAll();

        $queued = 0;
        foreach ($rows as $row) {
            $contextKey = 'due_reminder_' . (int)$row['task_id'] . '_' . (int)$row['user_id'] . '_' . date('Ymd');
            if (self::isContextAlreadyQueued($contextKey)) {
                continue;
            }

            $subject = 'Task due reminder: ' . (string)$row['title'];
            $html = self::layout('Task Due Reminder', "
                <p>Hello " . self::escape((string)$row['full_name']) . ",</p>
                <p>Your task is due within 24 hours.</p>
                <ul>
                    <li><strong>Task:</strong> " . self::escape((string)$row['title']) . "</li>
                    <li><strong>Due date:</strong> " . self::escape((string)$row['due_date']) . "</li>
                </ul>
                <p><a href='" . APP_URL . '/tasks/' . (int)$row['task_id'] . "'>Open task</a></p>
            ");

            self::queueEmail([
                'to'          => $row['email'],
                'subject'     => $subject,
                'html'        => $html,
                'email_type'  => 'due_reminder',
                'context_key' => $contextKey,
            ]);
            $queued++;
        }

        return $queued;
    }

    public static function sendNewComment(int $taskId, int $commenterId, string $comment): void
    {
        $task = self::taskById($taskId);
        if (!$task) return;

        $recipients = self::taskCreatorAndAssignees($taskId, $commenterId);
        if (empty($recipients)) return;

        $preview = self::escape(mb_substr(trim($comment), 0, 180));
        $subject = 'New comment on task: ' . (string)$task['title'];
        $html = self::layout('New Task Comment', "
            <p>A new comment was posted on task <strong>" . self::escape((string)$task['title']) . "</strong>.</p>
            <p><em>\"{$preview}\"</em></p>
            <p><a href='" . APP_URL . '/tasks/' . (int)$taskId . "#comments'>View comment</a></p>
        ");

        foreach ($recipients as $r) {
            self::queueEmail([
                'to'          => $r['email'],
                'subject'     => $subject,
                'html'        => $html,
                'email_type'  => 'new_comment',
                'context_key' => 'comment_' . $taskId . '_' . (int)$r['id'] . '_' . date('YmdHis'),
            ]);
        }
    }

    public static function sendAccountCreated(array $user, string $temporaryPassword): void
    {
        if (empty($user['email'])) return;

        $subject = 'Welcome to ' . APP_NAME;
        $html = self::layout('Account Created', "
            <p>Hello " . self::escape((string)$user['full_name']) . ",</p>
            <p>An administrator created your account.</p>
            <ul>
                <li><strong>Username:</strong> " . self::escape((string)$user['username']) . "</li>
                <li><strong>Temporary password:</strong> " . self::escape($temporaryPassword) . "</li>
            </ul>
            <p>Please sign in and change your password immediately.</p>
            <p><a href='" . APP_URL . "/login'>Login</a></p>
        ");

        self::queueEmail([
            'to'          => $user['email'],
            'subject'     => $subject,
            'html'        => $html,
            'email_type'  => 'account_created',
            'context_key' => 'account_created_' . (int)$user['id'] . '_' . date('YmdHis'),
        ]);
    }

    public static function sendAccountToggled(array $user, bool $isActive): void
    {
        if (empty($user['email'])) return;

        $subject = $isActive ? 'Your account has been reactivated' : 'Your account has been deactivated';
        $state   = $isActive ? 'reactivated' : 'deactivated';

        $html = self::layout('Account Status Update', "
            <p>Hello " . self::escape((string)$user['full_name']) . ",</p>
            <p>Your account has been <strong>{$state}</strong> by an administrator.</p>
            <p>If this change looks unexpected, contact your administrator.</p>
        ");

        self::queueEmail([
            'to'          => $user['email'],
            'subject'     => $subject,
            'html'        => $html,
            'email_type'  => 'account_status_changed',
            'context_key' => 'account_toggle_' . (int)$user['id'] . '_' . date('YmdHis'),
        ]);
    }

    public static function sendHighPriorityTaskCreatedAdminSummary(int $taskId): void
    {
        $task = self::taskById($taskId);
        if (!$task) return;

        if (!in_array((string)$task['priority'], ['critique', 'haute'], true)) {
            return;
        }

        $admins = self::adminRecipients();
        if (empty($admins)) return;

        $subject = 'High priority task created: ' . (string)$task['title'];
        $html = self::layout('High Priority Task Alert', "
            <p>A high priority task has been created.</p>
            <ul>
                <li><strong>Title:</strong> " . self::escape((string)$task['title']) . "</li>
                <li><strong>Priority:</strong> " . self::escape((string)$task['priority']) . "</li>
                <li><strong>Due date:</strong> " . self::escape((string)($task['due_date'] ?: 'N/A')) . "</li>
            </ul>
            <p><a href='" . APP_URL . '/tasks/' . (int)$taskId . "'>Open task</a></p>
        ");

        foreach ($admins as $admin) {
            self::queueEmail([
                'to'          => $admin['email'],
                'subject'     => $subject,
                'html'        => $html,
                'email_type'  => 'high_priority_task_summary',
                'context_key' => 'high_priority_' . $taskId . '_' . (int)$admin['id'] . '_' . date('YmdHis'),
            ]);
        }
    }

    public static function sendSubtasksCompletedReadyForReview(int $taskId): void
    {
        $task = self::taskById($taskId);
        if (!$task) return;

        $creator = self::userById((int)$task['created_by']);
        if (!$creator || empty($creator['email'])) return;

        $subject = 'Task ready for review: ' . (string)$task['title'];
        $html = self::layout('Subtasks Completed', "
            <p>Hello " . self::escape((string)$creator['full_name']) . ",</p>
            <p>All subtasks are completed for task <strong>" . self::escape((string)$task['title']) . "</strong>.</p>
            <p>The task is now ready for your review.</p>
            <p><a href='" . APP_URL . '/tasks/' . (int)$taskId . "'>Review task</a></p>
        ");

        self::queueEmail([
            'to'          => $creator['email'],
            'subject'     => $subject,
            'html'        => $html,
            'email_type'  => 'subtasks_completed',
            'context_key' => 'subtasks_completed_' . $taskId . '_' . (int)$creator['id'] . '_' . date('YmdHis'),
        ]);
    }

    public static function sendFileAttached(int $taskId, string $filename, int $uploaderId): void
    {
        $task = self::taskById($taskId);
        if (!$task) return;

        $recipients = self::taskAssignees($taskId, $uploaderId);
        if (empty($recipients)) return;

        $subject = 'New file attached to task: ' . (string)$task['title'];
        $html = self::layout('Task Attachment Added', "
            <p>A new file has been attached to task <strong>" . self::escape((string)$task['title']) . "</strong>.</p>
            <p><strong>File:</strong> " . self::escape($filename) . "</p>
            <p><a href='" . APP_URL . '/tasks/' . (int)$taskId . "#attachments'>View attachments</a></p>
        ");

        foreach ($recipients as $r) {
            self::queueEmail([
                'to'          => $r['email'],
                'subject'     => $subject,
                'html'        => $html,
                'email_type'  => 'file_attached',
                'context_key' => 'file_attached_' . $taskId . '_' . (int)$r['id'] . '_' . date('YmdHis'),
            ]);
        }
    }

    public static function sendPasswordChangedAlert(int $userId): void
    {
        $user = self::userById($userId);
        if (!$user || empty($user['email'])) return;

        $subject = 'Security alert: password changed';
        $html = self::layout('Password Change Alert', "
            <p>Hello " . self::escape((string)$user['full_name']) . ",</p>
            <p>This is a security alert to confirm your password was updated.</p>
            <p>If you did not perform this change, contact an administrator immediately.</p>
        ");

        self::queueEmail([
            'to'          => $user['email'],
            'subject'     => $subject,
            'html'        => $html,
            'email_type'  => 'password_changed',
            'context_key' => 'password_changed_' . (int)$user['id'] . '_' . date('YmdHis'),
        ]);
    }

    // ── Queue & delivery ──────────────────────────────────────────────────────

    public static function queueEmail(array $payload): void
    {
        if (!self::isEnabled()) return;

        $to      = trim((string)($payload['to'] ?? ''));
        $subject = trim((string)($payload['subject'] ?? ''));
        $html    = (string)($payload['html'] ?? '');

        if ($to === '' || $subject === '' || $html === '') {
            return;
        }

        $queueId = bin2hex(random_bytes(12));
        $item = [
            'queue_id'    => $queueId,
            'to'          => $to,
            'subject'     => $subject,
            'html'        => $html,
            'email_type'  => (string)($payload['email_type'] ?? 'generic'),
            'context_key' => (string)($payload['context_key'] ?? ''),
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        try {
            if (!is_dir(EMAIL_QUEUE_PATH)) {
                mkdir(EMAIL_QUEUE_PATH, 0755, true);
            }

            file_put_contents(
                EMAIL_QUEUE_PATH . '/' . $queueId . '.json',
                json_encode($item, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );

            self::insertEmailLog($item, 'queued', null, null);
            self::registerShutdownProcessor();
        } catch (Throwable $e) {
            error_log('Email queue failed: ' . $e->getMessage());
        }
    }

    public static function processQueue(int $max = 20): int
    {
        if (!self::isEnabled()) return 0;

        $files = glob(EMAIL_QUEUE_PATH . '/*.json') ?: [];
        sort($files);

        $processed = 0;

        foreach ($files as $file) {
            if ($processed >= $max) break;

            try {
                $raw = file_get_contents($file);
                $item = json_decode((string)$raw, true);
                if (!is_array($item)) {
                    @unlink($file);
                    continue;
                }

                $result = self::deliver((string)$item['to'], (string)$item['subject'], (string)$item['html']);
                if ($result['ok']) {
                    self::updateEmailLog((string)$item['queue_id'], 'sent', null, date('Y-m-d H:i:s'));
                } else {
                    self::updateEmailLog((string)$item['queue_id'], 'failed', (string)$result['error'], null);
                }

                @unlink($file);
                $processed++;
            } catch (Throwable $e) {
                error_log('Email queue processing failed: ' . $e->getMessage());
            }
        }

        return $processed;
    }

    // ── Transport ─────────────────────────────────────────────────────────────

    private static function deliver(string $to, string $subject, string $html): array
    {
        try {
            if (self::canUsePhpMailer()) {
                self::sendWithPhpMailer($to, $subject, $html);
                return ['ok' => true, 'error' => null];
            }

            $ok = self::sendWithMail($to, $subject, $html);
            return ['ok' => $ok, 'error' => $ok ? null : 'mail() returned false'];
        } catch (Throwable $e) {
            error_log('Email delivery error: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private static function canUsePhpMailer(): bool
    {
        if (!self::$phpMailerBootstrapped) {
            self::$phpMailerBootstrapped = true;
            $autoload = BASE_PATH . '/vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }

        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    private static function sendWithPhpMailer(string $to, string $subject, string $html): void
    {
        $mailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
        $mail = new $mailerClass(true);
        try {
            if (EMAIL_DRIVER === 'smtp') {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->Port = SMTP_PORT;
                $mail->SMTPAuth = SMTP_USERNAME !== '';
                if ($mail->SMTPAuth) {
                    $mail->Username = SMTP_USERNAME;
                    $mail->Password = SMTP_PASSWORD;
                }

                if (SMTP_ENCRYPTION === 'ssl') {
                    $mail->SMTPSecure = $mailerClass::ENCRYPTION_SMTPS;
                } elseif (SMTP_ENCRYPTION === 'tls') {
                    $mail->SMTPSecure = $mailerClass::ENCRYPTION_STARTTLS;
                }
            }

            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();
        } catch (Throwable $e) {
            throw new RuntimeException('PHPMailer send failed: ' . $e->getMessage());
        }
    }

    private static function sendWithMail(string $to, string $subject, string $html): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        ];

        return mail($to, $subject, $html, implode("\r\n", $headers));
    }

    // ── Data helpers ──────────────────────────────────────────────────────────

    private static function userById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT id, username, full_name, email, role, is_active FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private static function taskById(int $taskId): ?array
    {
        $stmt = self::db()->prepare('SELECT id, title, priority, status, due_date, created_by, assigned_to FROM tasks WHERE id = ?');
        $stmt->execute([$taskId]);
        return $stmt->fetch() ?: null;
    }

    private static function adminRecipients(): array
    {
        $stmt = self::db()->query("SELECT id, full_name, email FROM users WHERE role = 'admin' AND is_active = 1 AND email IS NOT NULL AND email != ''");
        return $stmt->fetchAll();
    }

    private static function taskCreatorAndAssignees(int $taskId, ?int $excludeUserId = null): array
    {
        $sql = "
            SELECT DISTINCT u.id, u.full_name, u.email
            FROM users u
            JOIN (
                SELECT t.created_by AS user_id
                FROM tasks t
                WHERE t.id = ?

                UNION

                SELECT ta.user_id
                FROM task_assignments ta
                WHERE ta.task_id = ?

                UNION

                SELECT t.assigned_to AS user_id
                FROM tasks t
                WHERE t.id = ? AND t.assigned_to IS NOT NULL
            ) r ON r.user_id = u.id
            WHERE u.is_active = 1
              AND u.email IS NOT NULL
              AND u.email != ''
        ";

        $params = [$taskId, $taskId, $taskId];

        if ($excludeUserId !== null) {
            $sql .= ' AND u.id != ?';
            $params[] = $excludeUserId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private static function taskAssignees(int $taskId, ?int $excludeUserId = null): array
    {
        $sql = "
            SELECT DISTINCT u.id, u.full_name, u.email
            FROM users u
            JOIN task_assignments ta ON ta.user_id = u.id
            WHERE ta.task_id = ?
              AND u.is_active = 1
              AND u.email IS NOT NULL
              AND u.email != ''
        ";

        $params = [$taskId];

        if ($excludeUserId !== null) {
            $sql .= ' AND u.id != ?';
            $params[] = $excludeUserId;
        }

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Logging ───────────────────────────────────────────────────────────────

    private static function insertEmailLog(array $item, string $status, ?string $error, ?string $sentAt): void
    {
        try {
            $stmt = self::db()->prepare('
                INSERT INTO email_logs (queue_id, recipient, subject, email_type, context_key, status, error_message, created_at, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ');
            $stmt->execute([
                (string)($item['queue_id'] ?? ''),
                (string)($item['to'] ?? ''),
                (string)($item['subject'] ?? ''),
                (string)($item['email_type'] ?? 'generic'),
                (string)($item['context_key'] ?? ''),
                $status,
                $error,
                $sentAt,
            ]);
        } catch (Throwable $e) {
            error_log('email_logs insert skipped: ' . $e->getMessage());
        }
    }

    private static function updateEmailLog(string $queueId, string $status, ?string $error, ?string $sentAt): void
    {
        try {
            $stmt = self::db()->prepare('
                UPDATE email_logs
                SET status = ?, error_message = ?, sent_at = ?, updated_at = NOW()
                WHERE queue_id = ?
            ');
            $stmt->execute([$status, $error, $sentAt, $queueId]);
        } catch (Throwable $e) {
            error_log('email_logs update skipped: ' . $e->getMessage());
        }
    }

    private static function isContextAlreadyQueued(string $contextKey): bool
    {
        if ($contextKey === '') return false;

        try {
            $stmt = self::db()->prepare('
                SELECT COUNT(*)
                FROM email_logs
                WHERE context_key = ?
                  AND DATE(created_at) = CURDATE()
                  AND status IN (\'queued\', \'sent\')
            ');
            $stmt->execute([$contextKey]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    // ── Utilities ─────────────────────────────────────────────────────────────

    private static function isEnabled(): bool
    {
        return EMAIL_ENABLED && MAIL_FROM_ADDRESS !== '';
    }

    private static function registerShutdownProcessor(): void
    {
        if (self::$shutdownRegistered) return;

        self::$shutdownRegistered = true;
        register_shutdown_function(function (): void {
            try {
                EmailService::processQueue(10);
            } catch (Throwable $e) {
                error_log('Email shutdown processor failed: ' . $e->getMessage());
            }
        });
    }

    private static function db(): PDO
    {
        return Database::getInstance();
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function layout(string $title, string $body): string
    {
        return "
            <div style='font-family:Arial,Helvetica,sans-serif;background:#f8fafc;padding:24px;color:#0f172a;'>
                <div style='max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;'>
                    <h2 style='margin-top:0;margin-bottom:16px;color:#1e3a8a;'>" . self::escape($title) . "</h2>
                    <div style='font-size:14px;line-height:1.6;'>" . $body . "</div>
                    <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'>
                    <p style='font-size:12px;color:#64748b;margin:0;'>" . self::escape(APP_NAME) . "</p>
                </div>
            </div>
        ";
    }
}
