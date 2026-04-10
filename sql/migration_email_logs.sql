-- ============================================================
--  IT TaskManager — Email Logs Migration
--  Creates table to track queued/sent/failed email notifications
-- ============================================================

CREATE TABLE IF NOT EXISTS email_logs (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    queue_id      VARCHAR(50)     NOT NULL,
    recipient     VARCHAR(190)    NOT NULL,
    subject       VARCHAR(255)    NOT NULL,
    email_type    VARCHAR(80)     NOT NULL DEFAULT 'generic',
    context_key   VARCHAR(190)    NULL,
    status        ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    error_message TEXT            NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at       DATETIME        NULL,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_email_logs_status (status),
    KEY idx_email_logs_recipient (recipient),
    KEY idx_email_logs_context (context_key),
    UNIQUE KEY uq_email_logs_queue_id (queue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
