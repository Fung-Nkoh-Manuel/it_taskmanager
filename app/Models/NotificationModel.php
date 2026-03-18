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
     * Generate deadline notifications for tasks due today, tomorrow, or in 3 days.
     * Called on every authenticated page load (idempotent via duplicate check).
     */
    public function generateDeadlineAlerts(): void
    {
        if (empty($_SESSION['user_id'])) return;

        $dueSoon = $this->query("
            SELECT t.id, t.title, t.due_date, t.assigned_to
            FROM tasks t
            WHERE t.status NOT IN ('termine')
              AND t.due_date IN (CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY))
              AND t.assigned_to IS NOT NULL
        ");

        foreach ($dueSoon as $task) {
            // Avoid duplicate notifications per task per day
            $exists = $this->queryOne(
                "SELECT id FROM notifications
                 WHERE task_id = ? AND user_id = ? AND type = 'echeance'
                   AND DATE(created_at) = CURDATE()",
                [$task['id'], $task['assigned_to']]
            );

            if (!$exists) {
                $diff    = (new DateTime($task['due_date']))->diff(new DateTime())->days;
                $label   = match($diff) {
                    0       => 'today',
                    1       => 'tomorrow',
                    default => "in {$diff} days",
                };
                $this->create(
                    $task['assigned_to'],
                    'echeance',
                    "Task \"{$task['title']}\" is due {$label}.",
                    $task['id']
                );
            }
        }
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
        // Notify assignee and creator, but not the commenter
        $users = $this->query("
            SELECT DISTINCT u.id
            FROM users u
            JOIN tasks t ON (t.assigned_to = u.id OR t.created_by = u.id)
            WHERE t.id = ? AND u.id != ?
        ", [$taskId, $commenterId]);

        foreach ($users as $u) {
            $this->create($u['id'], 'commentaire', "New comment on task \"{$taskTitle}\".", $taskId);
        }
    }

    /**
     * Notify relevant users when a task status changes.
     */
    public function notifyStatusChange(int $taskId, string $taskTitle, string $newStatus, int $actorId): void
    {
        $users = $this->query("
            SELECT DISTINCT u.id
            FROM users u
            JOIN tasks t ON (t.assigned_to = u.id OR t.created_by = u.id)
            WHERE t.id = ? AND u.id != ?
        ", [$taskId, $actorId]);

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
}
