<?php

class SubtaskModel extends BaseModel
{
    protected string $table = 'task_subtasks';

    // ── Fetch ─────────────────────────────────────────────────────────────────

    public function forTask(int $taskId): array
    {
        return $this->query("
            SELECT s.*,
                   u.full_name  AS assigned_name,
                   cb.full_name AS completed_by_name,
                   cr.full_name AS created_by_name
            FROM task_subtasks s
            LEFT JOIN users u  ON u.id  = s.assigned_to
            LEFT JOIN users cb ON cb.id = s.completed_by
            LEFT JOIN users cr ON cr.id = s.created_by
            WHERE s.task_id = ?
            ORDER BY s.order_index ASC, s.created_at ASC
        ", [$taskId]);
    }

    public function findWithUsers(int $id): ?array
    {
        return $this->queryOne("
            SELECT s.*,
                   u.full_name  AS assigned_name,
                   cb.full_name AS completed_by_name,
                   t.title      AS task_title,
                   t.created_by AS task_creator_id
            FROM task_subtasks s
            LEFT JOIN users u  ON u.id  = s.assigned_to
            LEFT JOIN users cb ON cb.id = s.completed_by
            LEFT JOIN tasks t  ON t.id  = s.task_id
            WHERE s.id = ?
        ", [$id]);
    }

    // ── Progress ──────────────────────────────────────────────────────────────

    /**
     * Returns ['total'=>N, 'done'=>N, 'percent'=>N] for a task.
     */
    public function progressForTask(int $taskId): array
    {
        $row = $this->queryOne("
            SELECT
                COUNT(*)                          AS total,
                SUM(status = 'termine')           AS done
            FROM task_subtasks
            WHERE task_id = ?
        ", [$taskId]);

        $total   = (int)($row['total'] ?? 0);
        $done    = (int)($row['done']  ?? 0);
        $percent = $total > 0 ? (int)round($done / $total * 100) : 0;

        return compact('total', 'done', 'percent');
    }

    /**
     * Batch progress for multiple task IDs — used by task list view.
     * Returns [ task_id => ['total'=>N,'done'=>N,'percent'=>N], ... ]
     */
    public function progressForTasks(array $taskIds): array
    {
        if (empty($taskIds)) return [];

        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
        $rows = $this->query("
            SELECT task_id,
                   COUNT(*)               AS total,
                   SUM(status='termine')  AS done
            FROM task_subtasks
            WHERE task_id IN ({$placeholders})
            GROUP BY task_id
        ", $taskIds);

        $result = [];
        foreach ($rows as $row) {
            $total   = (int)$row['total'];
            $done    = (int)$row['done'];
            $percent = $total > 0 ? (int)round($done / $total * 100) : 0;
            $result[$row['task_id']] = compact('total', 'done', 'percent');
        }
        return $result;
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function create(int $taskId, array $data, int $createdBy): int
    {
        // Put new subtask at the end
        $maxOrder = $this->queryOne(
            'SELECT COALESCE(MAX(order_index), 0) AS mx FROM task_subtasks WHERE task_id = ?',
            [$taskId]
        )['mx'] ?? 0;

        return $this->insert("
            INSERT INTO task_subtasks
                (task_id, title, description, assigned_to, order_index, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [
            $taskId,
            trim($data['title']),
            trim($data['description'] ?? '') ?: null,
            $data['assigned_to'] ?: null,
            (int)$maxOrder + 1,
            $createdBy,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $this->execute("
            UPDATE task_subtasks
            SET title       = ?,
                description = ?,
                assigned_to = ?
            WHERE id = ?
        ", [
            trim($data['title']),
            trim($data['description'] ?? '') ?: null,
            $data['assigned_to'] ?: null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM task_subtasks WHERE id = ?', [$id]);
    }

    // ── Complete ──────────────────────────────────────────────────────────────

    /**
     * Mark a subtask as complete, saving the report text and optional file.
     */
    public function complete(int $id, int $userId, string $reportText, ?array $uploadedFile = null): void
    {
        $file         = null;
        $originalName = null;

        if ($uploadedFile) {
            $file         = $uploadedFile['filename'];
            $originalName = $uploadedFile['original_name'];
        }

        $this->execute("
            UPDATE task_subtasks
            SET status           = 'termine',
                report_text      = ?,
                report_file      = ?,
                report_filename  = ?,
                completed_by     = ?,
                completed_at     = NOW()
            WHERE id = ?
        ", [$reportText ?: null, $file, $originalName, $userId, $id]);
    }

    /**
     * Reopen a completed subtask (admin/tech only).
     */
    public function reopen(int $id): void
    {
        $this->execute("
            UPDATE task_subtasks
            SET status = 'a_faire', report_text = NULL,
                report_file = NULL, report_filename = NULL,
                completed_by = NULL, completed_at = NULL
            WHERE id = ?
        ", [$id]);
    }

    // ── Reorder ───────────────────────────────────────────────────────────────

    public function reorder(int $id, int $newIndex): void
    {
        $this->execute('UPDATE task_subtasks SET order_index = ? WHERE id = ?', [$newIndex, $id]);
    }

    // ── Auto-complete parent task ─────────────────────────────────────────────

    /**
     * If all subtasks for a task are done, auto-mark the parent task as complete.
     * Returns true if the parent was auto-completed.
     */
    public function checkAutoComplete(int $taskId): bool
    {
        $row = $this->queryOne("
            SELECT
                COUNT(*)                    AS total,
                SUM(status = 'termine')     AS done
            FROM task_subtasks
            WHERE task_id = ?
        ", [$taskId]);

        $total = (int)($row['total'] ?? 0);
        $done  = (int)($row['done']  ?? 0);

        if ($total === 0) return false;

        if ($done === $total) {
            // All subtasks done — mark parent as completed
            $this->execute("
                UPDATE tasks
                SET status = 'termine', completed_at = NOW()
                WHERE id = ? AND status != 'termine'
            ", [$taskId]);
            return true;
        }

        if ($done > 0 && $done < $total) {
            // Some done but not all — move to in progress
            // Also handles reopening a completed task when new subtasks are added
            $this->execute("
                UPDATE tasks
                SET status = 'en_cours',
                    completed_at = NULL
                WHERE id = ? AND status IN ('a_faire', 'bloque', 'termine')
            ", [$taskId]);
        }

        if ($done === 0 && $total > 0) {
            // No subtasks done yet — move back to a_faire if it was completed
            $this->execute("
                UPDATE tasks
                SET status = 'a_faire',
                    completed_at = NULL
                WHERE id = ? AND status = 'termine'
            ", [$taskId]);
        }
        return false;
    }
}