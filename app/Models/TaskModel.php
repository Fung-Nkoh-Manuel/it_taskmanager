<?php

class TaskModel extends BaseModel
{
    protected string $table = 'tasks';

    // ── Task CRUD ─────────────────────────────────────────────────────────────

    public function paginated(array $filters, int $page, int $userId, string $role): array
    {
        [$sql, $params] = $this->buildFilterQuery($filters, $userId, $role);

        // Build a clean count query that avoids GROUP_CONCAT and ORDER BY issues
        $countSql = $this->buildCountQuery($filters, $userId, $role);

        return $this->paginate($sql, $params, $page, ITEMS_PER_PAGE, $countSql);
    }

    /**
     * Separate lightweight count query — no GROUP_CONCAT, no ORDER BY.
     */
    private function buildCountQuery(array $f, int $userId, string $role): string
    {
        $restricted = in_array($role, ['utilisateur', 'technicien'], true);

        // Use INNER JOIN with user filter baked in when role is restricted
        if ($restricted) {
            $joinSql = 'INNER JOIN task_assignments ta ON ta.task_id = t.id
                        AND (ta.user_id = ' . (int)$userId . ' OR t.created_by = ' . (int)$userId . ')';
        } else {
            $joinSql = 'LEFT JOIN task_assignments ta ON ta.task_id = t.id';
        }

        $sql = "
            SELECT COUNT(DISTINCT t.id)
            FROM tasks t
            {$joinSql}
            WHERE 1=1
        ";

        if (!empty($f['search'])) {
            $like = addslashes('%' . $f['search'] . '%');
            $sql .= " AND (t.title LIKE '{$like}' OR t.description LIKE '{$like}')";
        }
        if (!empty($f['status'])) {
            $sql .= " AND t.status = '" . addslashes($f['status']) . "'";
        }
        if (!empty($f['priority'])) {
            $sql .= " AND t.priority = '" . addslashes($f['priority']) . "'";
        }
        if (!empty($f['assigned_to'])) {
            $sql .= ' AND ta.user_id = ' . (int)$f['assigned_to'];
        }

        return $sql;
    }

    public function countFiltered(array $filters = [], ?int $userId = null, string $role = 'admin'): int
    {
        $sql  = $this->buildCountQuery($filters, $userId ?? 0, $role);
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function buildFilterQuery(array $f, int $userId, string $role): array
    {
        // For filtered roles, use INNER JOIN so only assigned tasks appear
        $assignJoin = in_array($role, ['utilisateur', 'technicien'], true)
            ? 'INNER JOIN task_assignments ta ON ta.task_id = t.id AND (ta.user_id = ? OR t.created_by = ?)'
            : 'LEFT JOIN task_assignments ta ON ta.task_id = t.id';

        $sql = "
            SELECT t.*,
                   u.full_name  AS assigned_name,
                   c.full_name  AS creator_name,
                   GROUP_CONCAT(DISTINCT ua.full_name ORDER BY ua.full_name SEPARATOR '|') AS all_assignees
            FROM tasks t
            LEFT JOIN users u   ON u.id  = t.assigned_to
            LEFT JOIN users c   ON c.id  = t.created_by
            {$assignJoin}
            LEFT JOIN users ua  ON ua.id = ta.user_id
            WHERE 1=1
        ";

        $params = [];

        // Bind the INNER JOIN params first when role is restricted
        if (in_array($role, ['utilisateur', 'technicien'], true)) {
            $params[] = $userId;
            $params[] = $userId;
        }

        if (!empty($f['search'])) {
            $sql     .= ' AND (t.title LIKE ? OR t.description LIKE ?)';
            $like     = '%' . $f['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($f['status'])) {
            $sql     .= ' AND t.status = ?';
            $params[] = $f['status'];
        }

        if (!empty($f['priority'])) {
            $sql     .= ' AND t.priority = ?';
            $params[] = $f['priority'];
        }

        if (!empty($f['assigned_to'])) {
            $sql     .= ' AND ta.user_id = ?';
            $params[] = $f['assigned_to'];
        }

        $sql .= ' GROUP BY t.id';
        $sql .= ' ORDER BY FIELD(t.priority,"critique","haute","moyenne","basse"), t.created_at DESC';

        return [$sql, $params];
    }

    public function findWithUsers(int $id): ?array
    {
        return $this->queryOne("
            SELECT t.*,
                   u.full_name AS assigned_name,
                   c.full_name AS creator_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
            LEFT JOIN users c ON c.id = t.created_by
            WHERE t.id = ?
        ", [$id]);
    }

    public function create(array $data, int $createdBy): int
    {
        return $this->insert("
            INSERT INTO tasks (title, description, priority, status, created_by, assigned_to, start_date, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['title'],
            $data['description'] ?? null,
            $data['priority']    ?? 'moyenne',
            $data['status']      ?? 'a_faire',
            $createdBy,
            $data['assigned_to'] ?: null,
            $data['start_date']  ?: null,
            $data['due_date']    ?: null,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $completedAt = ($data['status'] === 'termine') ? 'NOW()' : 'NULL';

        $this->execute("
            UPDATE tasks
            SET title       = ?,
                description = ?,
                priority    = ?,
                status      = ?,
                assigned_to = ?,
                start_date  = ?,
                due_date    = ?,
                completed_at = {$completedAt}
            WHERE id = ?
        ", [
            $data['title'],
            $data['description'] ?? null,
            $data['priority'],
            $data['status'],
            $data['assigned_to'] ?: null,
            $data['start_date']  ?: null,
            $data['due_date']    ?: null,
            $id,
        ]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $completedAt = ($status === 'termine') ? ', completed_at = NOW()' : '';
        $this->execute(
            "UPDATE tasks SET status = ? {$completedAt} WHERE id = ?",
            [$status, $id]
        );
    }

    public function updateDates(int $id, string $startDate, string $dueDate): void
    {
        $this->execute(
            'UPDATE tasks SET start_date = ?, due_date = ? WHERE id = ?',
            [$startDate, $dueDate, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM tasks WHERE id = ?', [$id]);
    }

    // ── Assignments (many-to-many) ────────────────────────────────────────────

    /**
     * Get all users assigned to a task.
     */
    public function getAssignees(int $taskId): array
    {
        return $this->query("
            SELECT u.id, u.full_name, u.role, u.username
            FROM task_assignments ta
            JOIN users u ON u.id = ta.user_id
            WHERE ta.task_id = ?
            ORDER BY u.full_name
        ", [$taskId]);
    }

    /**
     * Sync assignees — replaces all existing assignments with the new list.
     * Also updates tasks.assigned_to to the first assignee for backward compat.
     */
    public function syncAssignees(int $taskId, array $userIds): void
    {
        // Remove all existing
        $this->execute('DELETE FROM task_assignments WHERE task_id = ?', [$taskId]);

        // Insert new ones
        foreach ($userIds as $uid) {
            $uid = (int)$uid;
            if ($uid > 0) {
                $this->execute(
                    'INSERT IGNORE INTO task_assignments (task_id, user_id) VALUES (?, ?)',
                    [$taskId, $uid]
                );
            }
        }

        // Keep tasks.assigned_to as first assignee for calendar/legacy queries
        $first = !empty($userIds) ? (int)$userIds[0] : null;
        $this->execute(
            'UPDATE tasks SET assigned_to = ? WHERE id = ?',
            [$first, $taskId]
        );
    }

    /**
     * Get assignee IDs only — useful for form pre-selection.
     */
    public function getAssigneeIds(int $taskId): array
    {
        $rows = $this->query(
            'SELECT user_id FROM task_assignments WHERE task_id = ? ORDER BY created_at ASC',
            [$taskId]
        );
        return array_column($rows, 'user_id');
    }

    /**
     * Check if a user is assigned to a task.
     */
    public function isAssigned(int $taskId, int $userId): bool
    {
        return (bool) $this->queryOne(
            'SELECT id FROM task_assignments WHERE task_id = ? AND user_id = ?',
            [$taskId, $userId]
        );
    }

    // ── Dashboard stats ───────────────────────────────────────────────────────

    public function getStats(?int $userId = null, string $role = 'admin'): array
    {
        $join   = '';
        $where  = '';
        $params = [];

        if (in_array($role, ['utilisateur', 'technicien'], true) && $userId) {
            $join   = 'LEFT JOIN task_assignments ta ON ta.task_id = t.id';
            $where  = 'WHERE (ta.user_id = ? OR t.created_by = ?)';
            $params = [$userId, $userId];
        }

        $row = $this->queryOne("
            SELECT
                COUNT(DISTINCT t.id) AS total,
                SUM(t.status = 'en_cours') AS en_cours,
                SUM(t.status = 'termine')  AS termine,
                SUM(t.status = 'a_faire')  AS a_faire,
                SUM(t.status = 'bloque')   AS bloque,
                SUM(t.status != 'termine' AND t.due_date < CURDATE()) AS en_retard,
                SUM(t.status != 'termine' AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)) AS urgentes
            FROM tasks t {$join} {$where}
        ", $params);

        return $row ?? [];
    }

    public function getMonthlyStats(int $userId = 0, string $role = 'admin'): array
    {
        $restricted = in_array($role, ['utilisateur', 'technicien'], true) && $userId;

        if ($restricted) {
            return $this->query("
                SELECT
                    DATE_FORMAT(t.created_at, '%b %Y') AS month,
                    COUNT(DISTINCT t.id)               AS created,
                    SUM(t.status = 'termine')          AS completed
                FROM tasks t
                INNER JOIN task_assignments ta ON ta.task_id = t.id
                    AND (ta.user_id = ? OR t.created_by = ?)
                WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
                ORDER BY DATE_FORMAT(t.created_at, '%Y-%m')
            ", [$userId, $userId]);
        }

        return $this->query("
            SELECT
                DATE_FORMAT(created_at, '%b %Y') AS month,
                COUNT(*)                          AS created,
                SUM(status = 'termine')           AS completed
            FROM tasks
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY DATE_FORMAT(created_at, '%Y-%m')
        ");
    }

    public function getByPriority(int $userId = 0, string $role = 'admin'): array
    {
        $restricted = in_array($role, ['utilisateur', 'technicien'], true) && $userId;

        if ($restricted) {
            return $this->query("
                SELECT t.priority, COUNT(DISTINCT t.id) AS total
                FROM tasks t
                INNER JOIN task_assignments ta ON ta.task_id = t.id
                    AND (ta.user_id = ? OR t.created_by = ?)
                GROUP BY t.priority
            ", [$userId, $userId]);
        }

        return $this->query("
            SELECT priority, COUNT(*) AS total FROM tasks GROUP BY priority
        ");
    }

    public function getOverdueTasks(int $limit = 10, ?int $userId = null, string $role = 'admin'): array
    {
        $join   = '';
        $where  = '';
        $params = [];

        if (in_array($role, ['utilisateur', 'technicien'], true) && $userId) {
            $join     = 'LEFT JOIN task_assignments ta ON ta.task_id = t.id';
            $where    = 'AND (ta.user_id = ? OR t.created_by = ?)';
            $params[] = $userId;
            $params[] = $userId;
        }

        $params[] = $limit;

        return $this->query("
            SELECT DISTINCT t.*, u.full_name AS assigned_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
            {$join}
            WHERE t.status != 'termine'
              AND t.due_date < CURDATE()
              {$where}
            ORDER BY t.due_date ASC
            LIMIT ?
        ", $params);
    }

    public function getRecentTasks(int $limit = 6, ?int $userId = null, string $role = 'admin'): array
    {
        $join   = '';
        $where  = '';
        $params = [];

        if (in_array($role, ['utilisateur', 'technicien'], true) && $userId) {
            $join     = 'LEFT JOIN task_assignments ta ON ta.task_id = t.id';
            $where    = 'WHERE ta.user_id = ? OR t.created_by = ?';
            $params[] = $userId;
            $params[] = $userId;
        }

        $params[] = $limit;

        return $this->query("
            SELECT DISTINCT t.*, u.full_name AS assigned_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
            {$join}
            {$where}
            ORDER BY t.created_at DESC
            LIMIT ?
        ", $params);
    }

    public function getStatsByUser(): array
    {
        return $this->query("
            SELECT
                u.full_name,
                COUNT(t.id) AS total,
                SUM(t.status = 'en_cours') AS en_cours,
                SUM(t.status = 'termine')  AS terminees,
                SUM(t.status != 'termine' AND t.due_date < CURDATE()) AS en_retard
            FROM users u
            LEFT JOIN tasks t ON t.assigned_to = u.id
            WHERE u.role IN ('technicien','admin')
            GROUP BY u.id, u.full_name
            ORDER BY total DESC
        ");
    }

    public function getForCalendar(string $start, string $end, int $userId = 0, string $role = 'admin'): array
    {
        $join   = '';
        $where  = '';
        $params = [$end, $start];

        if (in_array($role, ['utilisateur', 'technicien'], true) && $userId) {
            $join     = 'LEFT JOIN task_assignments ta ON ta.task_id = t.id';
            $where    = 'AND (ta.user_id = ? OR t.created_by = ?)';
            $params[] = $userId;
            $params[] = $userId;
        }

        return $this->query("
            SELECT DISTINCT t.*, u.full_name AS assigned_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
            {$join}
            WHERE t.start_date IS NOT NULL
              AND t.start_date <= ?
              AND (t.due_date IS NULL OR t.due_date >= ?)
              {$where}
        ", $params);
    }

    // ── Comments ──────────────────────────────────────────────────────────────

    public function getComments(int $taskId): array
    {
        return $this->query("
            SELECT tc.*, u.full_name
            FROM task_comments tc
            JOIN users u ON u.id = tc.user_id
            WHERE tc.task_id = ?
            ORDER BY tc.created_at ASC
        ", [$taskId]);
    }

    public function addComment(int $taskId, int $userId, string $content): int
    {
        return $this->insert(
            'INSERT INTO task_comments (task_id, user_id, content) VALUES (?, ?, ?)',
            [$taskId, $userId, $content]
        );
    }

    // ── Attachments ───────────────────────────────────────────────────────────

    public function getAttachments(int $taskId): array
    {
        return $this->query("
            SELECT ta.*, u.full_name
            FROM task_attachments ta
            JOIN users u ON u.id = ta.user_id
            WHERE ta.task_id = ?
            ORDER BY ta.created_at DESC
        ", [$taskId]);
    }

    public function addAttachment(int $taskId, int $userId, array $file): int
    {
        return $this->insert(
            'INSERT INTO task_attachments (task_id, user_id, filename, original_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$taskId, $userId, $file['filename'], $file['original_name'], $file['mime_type'], $file['file_size']]
        );
    }

    public function findAttachment(int $id): ?array
    {
        return $this->queryOne('SELECT * FROM task_attachments WHERE id = ?', [$id]);
    }

    public function deleteAttachment(int $id): void
    {
        $this->execute('DELETE FROM task_attachments WHERE id = ?', [$id]);
    }

    // ── History ───────────────────────────────────────────────────────────────

    public function getHistory(int $taskId): array
    {
        return $this->query("
            SELECT th.*, u.full_name
            FROM task_history th
            JOIN users u ON u.id = th.user_id
            WHERE th.task_id = ?
            ORDER BY th.created_at DESC
        ", [$taskId]);
    }

    public function logHistory(int $taskId, int $userId, string $action, ?string $field = null, ?string $old = null, ?string $new = null): void
    {
        $this->insert(
            'INSERT INTO task_history (task_id, user_id, action, field_name, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?)',
            [$taskId, $userId, $action, $field, $old, $new]
        );
    }
}