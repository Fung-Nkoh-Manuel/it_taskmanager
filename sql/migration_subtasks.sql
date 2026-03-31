-- ============================================================
--  IT TaskManager — Subtasks Migration
--  Run this in phpMyAdmin SQL tab on your existing database
--  DO NOT re-import database.sql — that wipes all your data
-- ============================================================

-- ─── task_subtasks ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS task_subtasks (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    task_id         INT UNSIGNED  NOT NULL,
    title           VARCHAR(200)  NOT NULL,
    description     TEXT          NULL,
    assigned_to     INT UNSIGNED  NULL,
    status          ENUM('a_faire','en_cours','termine') NOT NULL DEFAULT 'a_faire',
    order_index     INT UNSIGNED  NOT NULL DEFAULT 0,

    -- Completion report
    report_text     TEXT          NULL,
    report_file     VARCHAR(255)  NULL,   -- stored UUID filename
    report_filename VARCHAR(255)  NULL,   -- original filename shown to user
    completed_by    INT UNSIGNED  NULL,
    completed_at    DATETIME      NULL,

    created_by      INT UNSIGNED  NOT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_st_task       (task_id),
    KEY idx_st_assigned   (assigned_to),
    KEY idx_st_status     (status),

    CONSTRAINT fk_st_task       FOREIGN KEY (task_id)     REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_st_assigned   FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_st_completed  FOREIGN KEY (completed_by)REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_st_created    FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
