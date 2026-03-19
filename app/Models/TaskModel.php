<?php

class TaskModel extends BaseModel
{
    protected string $table = 'tasks';

    // ── Task CRUD ─────────────────────────────────────────────────────────────

    public function paginated(array $filters, int $page, int $userId, string $role): array
    {
        [$sql, $params] = $this->buildFilterQuery($filters, $userId, $role);
        return $this->paginate($sql, $params, $page);
    }

    public function countFiltered(array $filters = [], ?int $userId = null, string $role = 'admin'): int
    {
        [$sql, $params] = $this->buildFilterQuery($filters, $userId ?? 0, $role);
        $countSql = preg_replace('/SELECT .+? FROM /is', 'SELECT COUNT(*) FROM ', $sql);
        $countSql = preg_replace('/ORDER BY .+$/is', '', $countSql);
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    private function buildFilterQuery(array $f, int $userId, string $role): array
    {
        $sql = "
            SELECT t.*,
                   u.full_name AS assigned_name,
                   c.full_name AS creator_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
            LEFT JOIN users c ON c.id = t.created_by
            WHERE 1=1
        ";
        $params = [];

        // Non-admins only see tasks assigned to them or created by them
        if ($role === 'utilisateur') {
            $sql     .= ' AND (t.assigned_to = ? OR t.created_by = ?)';
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
            $sql     .= ' AND t.assigned_to = ?';
            $params[] = $f['assigned_to'];
        }

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

    // ── Dashboard stats ───────────────────────────────────────────────────────

    public function getStats(?int $userId = null, string $role = 'admin'): array
    {
        $where  = '';
        $params = [];

        if ($role === 'utilisateur' && $userId) {
            $where  = 'WHERE assigned_to = ? OR created_by = ?';
            $params = [$userId, $userId];
        }

        $row = $this->queryOne("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'en_cours') AS en_cours,
                SUM(status = 'termine')  AS termine,
                SUM(status = 'a_faire')  AS a_faire,
                SUM(status = 'bloque')   AS bloque,
                SUM(status != 'termine' AND due_date < CURDATE()) AS en_retard,
                SUM(status != 'termine' AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)) AS urgentes
            FROM tasks {$where}
        ", $params);

        return $row ?? [];
    }

    public function getMonthlyStats(): array
    {
        return $this->query("
            SELECT
                DATE_FORMAT(created_at, '%b %Y') AS month,
                COUNT(*) AS created,
                SUM(status = 'termine') AS completed
            FROM tasks
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY DATE_FORMAT(created_at, '%Y-%m')
        ");
    }

    public function getByPriority(): array
    {
        return $this->query("
            SELECT priority, COUNT(*) AS total FROM tasks GROUP BY priority
        ");
    }

    public function getOverdueTasks(int $limit = 10, ?int $userId = null, string $role = 'admin'): array
    {
        $where  = '';
        $params = [];

        if ($role === 'utilisateur' && $userId) {
            $where    = 'AND (t.assigned_to = ? OR t.created_by = ?)';
            $params[] = $userId;
            $params[] = $userId;
        }

        $params[] = $limit;

        return $this->query("
            SELECT t.*, u.full_name AS assigned_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
            WHERE t.status != 'termine'
            AND t.due_date < CURDATE()
            {$where}
            ORDER BY t.due_date ASC
            LIMIT ?
        ", $params);
    }

    public function getRecentTasks(int $limit = 6, ?int $userId = null, string $role = 'admin'): array
    {
        $where  = '';
        $params = [];

        if ($role === 'utilisateur' && $userId) {
            $where    = 'WHERE t.assigned_to = ? OR t.created_by = ?';
            $params[] = $userId;
            $params[] = $userId;
        }

        $params[] = $limit;

        return $this->query("
            SELECT t.*, u.full_name AS assigned_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
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
        $where  = '';
        $params = [$end, $start];

        if ($role === 'utilisateur') {
            $where    = 'AND (t.assigned_to = ? OR t.created_by = ?)';
            $params[] = $userId;
            $params[] = $userId;
        }

        return $this->query("
            SELECT t.*, u.full_name AS assigned_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
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
