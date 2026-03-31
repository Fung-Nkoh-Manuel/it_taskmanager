<?php

class NotificationModel extends BaseModel
{
    protected string $table = 'notifications';

    public function forUser(int $userId): array
    {
        return $this->query(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50',
            [$userId]
        );
    }

    public function countUnread(int $userId): int
    {
        return (int) $this->queryOne(
            'SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0',
            [$userId]
        )['cnt'] ?? 0;
    }

    public function create(int $userId, string $type, string $message, ?int $taskId = null): int
    {
        return $this->insert(
            'INSERT INTO notifications (user_id, type, message, task_id) VALUES (?, ?, ?, ?)',
            [$userId, $type, $message, $taskId]
        );
    }

    public function markRead(int $id, int $userId): void
    {
        $this->execute(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->execute(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ?',
            [$userId]
        );
    }

    /**
     * Generate deadline notifications for tasks due today, tomorrow, in 3 days,
     * or already overdue. Called on every authenticated page load (idempotent).
     */
    public function generateDeadlineAlerts(): void
    {
        if (empty($_SESSION['user_id'])) return;

        // ── Upcoming deadline alerts (today, tomorrow, 3 days) ────────────────
        $dueSoon = $this->query("
            SELECT t.id, t.title, t.due_date,
                   ta.user_id AS assigned_to,
                   t.created_by
            FROM tasks t
            JOIN task_assignments ta ON ta.task_id = t.id
            WHERE t.status NOT IN ('termine')
              AND t.due_date IN (
                  CURDATE(),
                  DATE_ADD(CURDATE(), INTERVAL 1 DAY),
                  DATE_ADD(CURDATE(), INTERVAL 3 DAY)
              )
        ");

        foreach ($dueSoon as $task) {
            $this->sendDeadlineNotif(
                $task['assigned_to'],
                $task['id'],
                $task['title'],
                $task['due_date'],
                false
            );
        }

        // ── Overdue alerts (past due date, not completed) ─────────────────────
        // Notify both assignees and the task creator, once per day per user
        $overdue = $this->query("
            SELECT DISTINCT t.id, t.title, t.due_date,
                   ta.user_id AS notif_user
            FROM tasks t
            JOIN task_assignments ta ON ta.task_id = t.id
            WHERE t.status NOT IN ('termine')
              AND t.due_date < CURDATE()

            UNION

            SELECT DISTINCT t.id, t.title, t.due_date,
                   t.created_by AS notif_user
            FROM tasks t
            WHERE t.status NOT IN ('termine')
              AND t.due_date < CURDATE()
        ");

        foreach ($overdue as $task) {
            $this->sendDeadlineNotif(
                $task['notif_user'],
                $task['id'],
                $task['title'],
                $task['due_date'],
                true
            );
        }
    }

    /**
     * Send a single deadline or overdue notification.
     * Idempotent — fires at most once per task per user per day.
     */
    private function sendDeadlineNotif(
        int    $userId,
        int    $taskId,
        string $taskTitle,
        string $dueDate,
        bool   $isOverdue
    ): void {
        $exists = $this->queryOne(
            "SELECT id FROM notifications
             WHERE task_id = ? AND user_id = ? AND type = 'echeance'
               AND DATE(created_at) = CURDATE()",
            [$taskId, $userId]
        );

        if ($exists) return;

        if ($isOverdue) {
            $daysLate = (new DateTime())->diff(new DateTime($dueDate))->days;
            $label    = $daysLate === 1 ? '1 day ago' : "{$daysLate} days ago";
            $message  = "⚠️ Task \"{$taskTitle}\" is overdue — due date was {$label}.";
        } else {
            $diff    = (new DateTime($dueDate))->diff(new DateTime())->days;
            $label   = match($diff) {
                0       => 'today',
                1       => 'tomorrow',
                default => "in {$diff} days",
            };
            $message = "Task \"{$taskTitle}\" is due {$label}.";
        }

        $this->create($userId, 'echeance', $message, $taskId);
    }

    /**
     * Notify a user when a task is assigned to them.
     */
    public function notifyAssignment(int $userId, string $taskTitle, int $taskId): void
    {
        $this->create($userId, 'assignation', "You have been assigned to: \"{$taskTitle}\".", $taskId);
    }

    /**
     * Notify watchers when a comment is posted.
     */
    public function notifyComment(int $taskId, string $taskTitle, int $commenterId): void
    {
        // Notify all assignees and creator, but not the commenter
        $users = $this->query("
            SELECT DISTINCT u.id
            FROM users u
            LEFT JOIN task_assignments ta ON ta.user_id = u.id AND ta.task_id = ?
            LEFT JOIN tasks t ON t.id = ?
            WHERE (ta.task_id = ? OR u.id = t.created_by)
              AND u.id != ?
        ", [$taskId, $taskId, $taskId, $commenterId]);

        foreach ($users as $u) {
            $this->create($u['id'], 'commentaire', "New comment on task \"{$taskTitle}\".", $taskId);
        }
    }

    /**
     * Notify relevant users when a task status changes.
     */
    public function notifyStatusChange(int $taskId, string $taskTitle, string $newStatus, int $actorId): void
    {
        // Notify all assignees and creator, but not the actor
        $users = $this->query("
            SELECT DISTINCT u.id
            FROM users u
            LEFT JOIN task_assignments ta ON ta.user_id = u.id AND ta.task_id = ?
            LEFT JOIN tasks t ON t.id = ?
            WHERE (ta.task_id = ? OR u.id = t.created_by)
              AND u.id != ?
        ", [$taskId, $taskId, $taskId, $actorId]);

        $label = match($newStatus) {
            'en_cours' => 'In Progress',
            'termine'  => 'Completed',
            'bloque'   => 'Blocked',
            default    => 'To Do',
        };

        foreach ($users as $u) {
            $this->create($u['id'], 'statut', "Task \"{$taskTitle}\" moved to: {$label}.", $taskId);
        }
    }
    
    public function notifyRoleChange(int $userId, string $oldRole, string $newRole): void
    {
        $labels = [
            'utilisateur' => 'User',
            'technicien'  => 'Technician',
            'admin'       => 'Administrator',
        ];

        $old = $labels[$oldRole] ?? $oldRole;
        $new = $labels[$newRole] ?? $newRole;

        $this->create(
            $userId,
            'systeme',
            "Your account role has been changed from {$old} to {$new}.",
            null
        );
    }

}