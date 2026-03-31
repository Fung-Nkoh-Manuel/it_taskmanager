-- ============================================================
--  IT TaskManager — Task Assignments Migration
--  Run this in phpMyAdmin SQL tab
--  Does NOT wipe existing data
-- ============================================================

-- ─── task_assignments (many-to-many) ─────────────────────────
CREATE TABLE IF NOT EXISTS task_assignments (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_task_user (task_id, user_id),
    KEY idx_ta_task (task_id),
    KEY idx_ta_user (user_id),
    CONSTRAINT fk_task_assignments_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_task_assignments_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing single assignments into the new table
INSERT IGNORE INTO task_assignments (task_id, user_id)
SELECT id, assigned_to
FROM tasks
WHERE assigned_to IS NOT NULL;
