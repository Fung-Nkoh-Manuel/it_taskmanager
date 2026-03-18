-- ============================================================
--  IT TaskManager — Full Database Schema
--  Charset: utf8mb4 | Engine: InnoDB
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables (safe re-import)
DROP TABLE IF EXISTS task_attachments;
DROP TABLE IF EXISTS task_comments;
DROP TABLE IF EXISTS task_history;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;

-- ─── users ───────────────────────────────────────────────────────────────────
CREATE TABLE users (
    id           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    username     VARCHAR(50)      NOT NULL,
    email        VARCHAR(150)     NOT NULL,
    password     VARCHAR(255)     NOT NULL,          -- bcrypt
    full_name    VARCHAR(100)     NOT NULL,
    role         ENUM('admin','technicien','utilisateur') NOT NULL DEFAULT 'utilisateur',
    is_active    TINYINT(1)       NOT NULL DEFAULT 1,
    avatar       VARCHAR(255)     NULL,
    last_login   DATETIME         NULL,
    created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_username (username),
    UNIQUE KEY uq_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── tasks ───────────────────────────────────────────────────────────────────
CREATE TABLE tasks (
    id           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    title        VARCHAR(200)     NOT NULL,
    description  TEXT             NULL,
    priority     ENUM('basse','moyenne','haute','critique') NOT NULL DEFAULT 'moyenne',
    status       ENUM('a_faire','en_cours','termine','bloque') NOT NULL DEFAULT 'a_faire',
    created_by   INT UNSIGNED     NOT NULL,
    assigned_to  INT UNSIGNED     NULL,
    start_date   DATE             NULL,
    due_date     DATE             NULL,
    completed_at DATETIME         NULL,
    created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status      (status),
    KEY idx_priority    (priority),
    KEY idx_assigned_to (assigned_to),
    KEY idx_due_date    (due_date),
    KEY idx_created_by  (created_by),
    CONSTRAINT fk_tasks_created_by  FOREIGN KEY (created_by)  REFERENCES users (id) ON DELETE RESTRICT,
    CONSTRAINT fk_tasks_assigned_to FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── task_comments ───────────────────────────────────────────────────────────
CREATE TABLE task_comments (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    content    TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tc_task (task_id),
    CONSTRAINT fk_tc_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_tc_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── task_attachments ────────────────────────────────────────────────────────
CREATE TABLE task_attachments (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    task_id       INT UNSIGNED  NOT NULL,
    user_id       INT UNSIGNED  NOT NULL,
    filename      VARCHAR(255)  NOT NULL,   -- stored UUID name
    original_name VARCHAR(255)  NOT NULL,   -- original upload name
    mime_type     VARCHAR(100)  NOT NULL,
    file_size     INT UNSIGNED  NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ta_task (task_id),
    CONSTRAINT fk_ta_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_ta_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── task_history ────────────────────────────────────────────────────────────
CREATE TABLE task_history (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    task_id    INT UNSIGNED  NOT NULL,
    user_id    INT UNSIGNED  NOT NULL,
    action     VARCHAR(100)  NOT NULL,
    field_name VARCHAR(100)  NULL,
    old_value  TEXT          NULL,
    new_value  TEXT          NULL,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_th_task (task_id),
    CONSTRAINT fk_th_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE,
    CONSTRAINT fk_th_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── notifications ───────────────────────────────────────────────────────────
CREATE TABLE notifications (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED  NOT NULL,
    task_id    INT UNSIGNED  NULL,
    type       ENUM('assignation','echeance','commentaire','statut','systeme') NOT NULL DEFAULT 'systeme',
    message    VARCHAR(255)  NOT NULL,
    is_read    TINYINT(1)    NOT NULL DEFAULT 0,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notif_user (user_id),
    KEY idx_notif_read (is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── activity_logs ───────────────────────────────────────────────────────────
CREATE TABLE activity_logs (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED  NULL,
    action     VARCHAR(100)  NOT NULL,
    entity     VARCHAR(50)   NULL,
    entity_id  INT UNSIGNED  NULL,
    details    TEXT          NULL,
    ip_address VARCHAR(45)   NULL,
    created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_log_user   (user_id),
    KEY idx_log_action (action),
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  Demo data  (passwords are all bcrypt of "password")
-- ============================================================

INSERT INTO users (username, email, password, full_name, role) VALUES
('admin',       'admin@ittasks.local',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator',  'admin'),
('technicien1', 'tech1@ittasks.local',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Dupont',    'technicien'),
('technicien2', 'tech2@ittasks.local',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Martin',     'technicien'),
('user1',       'user1@ittasks.local',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carol Lambert',  'utilisateur');

INSERT INTO tasks (title, description, priority, status, created_by, assigned_to, start_date, due_date) VALUES
('Set up production server',      'Install and configure the Nginx + PHP-FPM stack.',       'critique', 'en_cours', 1, 2, CURDATE() - INTERVAL 3 DAY, CURDATE() + INTERVAL 4 DAY),
('Database backup automation',    'Write a cron job to back up MySQL daily.',                'haute',    'a_faire',  1, 3, CURDATE(),                   CURDATE() + INTERVAL 7 DAY),
('SSL certificate renewal',       'Renew Let\'s Encrypt cert before expiry.',               'haute',    'a_faire',  1, 2, CURDATE(),                   CURDATE() + INTERVAL 2 DAY),
('Network audit',                 'Review firewall rules and open ports.',                  'moyenne',  'termine',  2, 2, CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 2 DAY),
('Update antivirus definitions',  'Push new definitions to all endpoints.',                 'basse',    'termine',  2, 3, CURDATE() - INTERVAL 5 DAY,  CURDATE() - INTERVAL 1 DAY),
('Migrate file server',           'Move shared drives to new NAS.',                         'critique', 'bloque',   1, 2, CURDATE() - INTERVAL 1 DAY,  CURDATE() + INTERVAL 1 DAY),
('Deploy monitoring dashboard',   'Set up Grafana + Prometheus.',                           'haute',    'a_faire',  1, 3, CURDATE() + INTERVAL 1 DAY,  CURDATE() + INTERVAL 14 DAY),
('Clean up user accounts',        'Remove stale AD accounts from Q1.',                     'basse',    'a_faire',  3, 3, CURDATE(),                   CURDATE() + INTERVAL 10 DAY);

-- Mark task 4 and 5 as completed
UPDATE tasks SET completed_at = NOW() - INTERVAL 1 DAY WHERE id IN (4, 5);
