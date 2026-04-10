<?php

// ════════════════════════════════════════════════════════════
//  EmailNotifier
//  Centralises every transactional email the app needs to send.
//  Called by controllers / models right after the DB action.
// ════════════════════════════════════════════════════════════

class EmailNotifier
{
    // ── Task assigned to a user ───────────────────────────────────────────────

    public static function taskAssigned(array $user, array $task, string $assignedByName): void
    {
        if (empty($user['email'])) return;

        $subject = '[' . APP_NAME . '] Task assigned to you: ' . $task['title'];
        $html    = self::layout($subject, "
            <p>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p>A task has been assigned to you by <strong>" . htmlspecialchars($assignedByName) . "</strong>:</p>
            " . self::taskCard($task) . "
            <p style='margin-top:24px;'>
                <a href='" . APP_URL . "/tasks/" . (int)$task['id'] . "' class='btn'>View Task</a>
            </p>
        ");

        Mailer::send($user['email'], $user['full_name'], $subject, $html);
    }

    // ── Subtask assigned to a user ────────────────────────────────────────────

    public static function subtaskAssigned(array $user, array $subtask, array $parentTask, string $assignedByName): void
    {
        if (empty($user['email'])) return;

        $subject = '[' . APP_NAME . '] Subtask assigned to you: ' . $subtask['title'];
        $html    = self::layout($subject, "
            <p>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p>A subtask has been assigned to you by <strong>" . htmlspecialchars($assignedByName) . "</strong>:</p>
            " . self::infoBox('Subtask', $subtask['title'], $subtask['description'] ?? '') . "
            <p><strong>Parent task:</strong> " . htmlspecialchars($parentTask['title']) . "</p>
            <p style='margin-top:24px;'>
                <a href='" . APP_URL . "/tasks/" . (int)$parentTask['id'] . "#subtasks' class='btn'>View Task</a>
            </p>
        ");

        Mailer::send($user['email'], $user['full_name'], $subject, $html);
    }

    // ── Task status changed ───────────────────────────────────────────────────

    public static function taskStatusChanged(array $user, array $task, string $oldStatus, string $newStatus, string $changedByName): void
    {
        if (empty($user['email'])) return;

        $labels = [
            'a_faire'   => 'To Do',
            'en_cours'  => 'In Progress',
            'termine'   => 'Completed',
            'bloque'    => 'Blocked',
        ];

        $oldLabel = $labels[$oldStatus] ?? $oldStatus;
        $newLabel = $labels[$newStatus] ?? $newStatus;

        $subject = '[' . APP_NAME . '] Task status changed: ' . $task['title'];
        $html    = self::layout($subject, "
            <p>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p>The status of a task was updated by <strong>" . htmlspecialchars($changedByName) . "</strong>:</p>
            " . self::taskCard($task) . "
            <table style='margin:16px 0;border-collapse:collapse;'>
                <tr>
                    <td style='padding:8px 16px;background:#f1f5f9;border-radius:6px 0 0 6px;font-weight:600;color:#64748b;'>
                        Previous status
                    </td>
                    <td style='padding:8px 16px;background:#fee2e2;border-radius:0 6px 6px 0;color:#dc2626;font-weight:700;'>
                        " . htmlspecialchars($oldLabel) . "
                    </td>
                </tr>
                <tr>
                    <td style='padding:8px 16px;background:#f1f5f9;border-radius:6px 0 0 6px;font-weight:600;color:#64748b;'>
                        New status
                    </td>
                    <td style='padding:8px 16px;background:#dcfce7;border-radius:0 6px 6px 0;color:#16a34a;font-weight:700;'>
                        " . htmlspecialchars($newLabel) . "
                    </td>
                </tr>
            </table>
            <p style='margin-top:24px;'>
                <a href='" . APP_URL . "/tasks/" . (int)$task['id'] . "' class='btn'>View Task</a>
            </p>
        ");

        Mailer::send($user['email'], $user['full_name'], $subject, $html);
    }

    // ── User status changed (active/inactive) ─────────────────────────────────

    public static function userStatusChanged(array $user, bool $isNowActive, string $changedByName): void
    {
        if (empty($user['email'])) return;

        $action  = $isNowActive ? 'activated' : 'deactivated';
        $subject = '[' . APP_NAME . '] Your account has been ' . $action;
        $color   = $isNowActive ? '#16a34a' : '#dc2626';
        $bg      = $isNowActive ? '#dcfce7'  : '#fee2e2';
        $html    = self::layout($subject, "
            <p>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p>Your account has been <strong style='color:{$color};'>" . $action . "</strong>
               by <strong>" . htmlspecialchars($changedByName) . "</strong>.</p>
            <div style='background:{$bg};border-radius:8px;padding:16px;margin:16px 0;color:{$color};font-weight:700;font-size:16px;'>
                Account status: " . strtoupper($action) . "
            </div>
            " . ($isNowActive ? "
            <p>You can now log in at:</p>
            <p style='margin-top:12px;'>
                <a href='" . APP_URL . "/login' class='btn'>Log in</a>
            </p>" : "
            <p>If you believe this is an error, please contact your administrator.</p>") . "
        ");

        Mailer::send($user['email'], $user['full_name'], $subject, $html);
    }

    // ── User role changed ─────────────────────────────────────────────────────

    public static function userRoleChanged(array $user, string $oldRole, string $newRole, string $changedByName): void
    {
        if (empty($user['email'])) return;

        $roleLabels = [
            'admin'        => 'Administrator',
            'technicien'   => 'Technician',
            'utilisateur'  => 'User',
        ];

        $oldLabel = $roleLabels[$oldRole] ?? $oldRole;
        $newLabel = $roleLabels[$newRole] ?? $newRole;

        $subject = '[' . APP_NAME . '] Your role has been updated';
        $html    = self::layout($subject, "
            <p>Hello <strong>" . htmlspecialchars($user['full_name']) . "</strong>,</p>
            <p>Your role in <strong>" . APP_NAME . "</strong> has been changed
               by <strong>" . htmlspecialchars($changedByName) . "</strong>:</p>
            <table style='margin:16px 0;border-collapse:collapse;'>
                <tr>
                    <td style='padding:8px 16px;background:#f1f5f9;border-radius:6px 0 0 6px;font-weight:600;color:#64748b;'>
                        Previous role
                    </td>
                    <td style='padding:8px 16px;background:#f1f5f9;border-radius:0 6px 6px 0;color:#475569;'>
                        " . htmlspecialchars($oldLabel) . "
                    </td>
                </tr>
                <tr>
                    <td style='padding:8px 16px;background:#f1f5f9;border-radius:6px 0 0 6px;font-weight:600;color:#64748b;'>
                        New role
                    </td>
                    <td style='padding:8px 16px;background:#dbeafe;border-radius:0 6px 6px 0;color:#1d4ed8;font-weight:700;'>
                        " . htmlspecialchars($newLabel) . "
                    </td>
                </tr>
            </table>
            <p style='margin-top:24px;'>
                <a href='" . APP_URL . "/dashboard' class='btn'>Go to Dashboard</a>
            </p>
        ");

        Mailer::send($user['email'], $user['full_name'], $subject, $html);
    }

    // ── Admin summary: notify admin on important system events ────────────────

    public static function adminAlert(string $event, string $detail): void
    {
        if (!defined('MAIL_ADMIN') || empty(MAIL_ADMIN)) return;

        $subject = '[' . APP_NAME . '] System event: ' . $event;
        $html    = self::layout($subject, "
            <p>An important system event has occurred:</p>
            <div style='background:#fefce8;border-left:4px solid #eab308;padding:16px;border-radius:0 8px 8px 0;margin:16px 0;'>
                <strong>" . htmlspecialchars($event) . "</strong><br>
                <span style='color:#713f12;'>" . nl2br(htmlspecialchars($detail)) . "</span>
            </div>
            <p style='color:#64748b;font-size:13px;'>Sent at: " . date('Y-m-d H:i:s') . "</p>
        ");

        Mailer::send(MAIL_ADMIN, 'Administrator', $subject, $html);
    }

    // ── HTML email layout ─────────────────────────────────────────────────────

    private static function layout(string $title, string $bodyHtml): string
    {
        $appName = defined('APP_NAME') ? APP_NAME : 'IT TaskManager';
        $appUrl  = defined('APP_URL')  ? APP_URL  : '#';
        $year    = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<style>
  body { margin:0; padding:0; background:#f8fafc; font-family: 'Segoe UI', Arial, sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08); overflow:hidden; }
  .header  { background:linear-gradient(135deg,#1e40af 0%,#0f172a 100%); padding:28px 32px; }
  .header h1 { margin:0; color:#fff; font-size:20px; font-weight:700; letter-spacing:.5px; }
  .header p  { margin:4px 0 0; color:#93c5fd; font-size:13px; }
  .body    { padding:32px; }
  .body p  { line-height:1.7; margin:0 0 12px; }
  .footer  { background:#f1f5f9; padding:20px 32px; text-align:center; font-size:12px; color:#94a3b8; }
  .footer a { color:#3b82f6; text-decoration:none; }
  .btn {
      display:inline-block; background:#1e40af; color:#fff !important; padding:12px 28px;
      border-radius:8px; text-decoration:none; font-weight:600; font-size:14px;
      margin-top:4px;
  }
  .task-card {
      border:1px solid #e2e8f0; border-radius:8px; padding:16px; margin:16px 0;
      background:#f8fafc;
  }
  .task-card .task-title { font-size:16px; font-weight:700; margin:0 0 8px; color:#0f172a; }
  .task-card .badge {
      display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px;
      font-weight:600; margin-right:6px;
  }
  .badge-haute    { background:#fee2e2; color:#dc2626; }
  .badge-moyenne  { background:#fef9c3; color:#92400e; }
  .badge-faible   { background:#dcfce7; color:#15803d; }
  .badge-a_faire  { background:#f1f5f9; color:#475569; }
  .badge-en_cours { background:#dbeafe; color:#1d4ed8; }
  .badge-termine  { background:#dcfce7; color:#15803d; }
  .badge-bloque   { background:#fee2e2; color:#dc2626; }
  .info-box { border-left:4px solid #3b82f6; background:#eff6ff; padding:14px; border-radius:0 8px 8px 0; margin:16px 0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{$appName}</h1>
    <p>IT Task Management System</p>
  </div>
  <div class="body">
    {$bodyHtml}
  </div>
  <div class="footer">
    &copy; {$year} <a href="{$appUrl}">{$appName}</a> &middot;
    This is an automated message, please do not reply.
  </div>
</div>
</body>
</html>
HTML;
    }

    // ── Reusable task card component ──────────────────────────────────────────

    private static function taskCard(array $task): string
    {
        $priority = htmlspecialchars($task['priority'] ?? 'moyenne');
        $status   = htmlspecialchars($task['status']   ?? 'a_faire');
        $due      = !empty($task['due_date'])
            ? '<br><span style="font-size:13px;color:#64748b;">Due: ' . htmlspecialchars($task['due_date']) . '</span>'
            : '';

        $desc = !empty($task['description'])
            ? '<p style="margin:8px 0 0;font-size:13px;color:#475569;">' . nl2br(htmlspecialchars(mb_substr($task['description'], 0, 200))) . '</p>'
            : '';

        $statusLabels   = ['a_faire'=>'To Do','en_cours'=>'In Progress','termine'=>'Completed','bloque'=>'Blocked'];
        $priorityLabels = ['haute'=>'High','moyenne'=>'Medium','faible'=>'Low'];

        $sLabel = $statusLabels[$task['status']   ?? ''] ?? ($task['status']   ?? '');
        $pLabel = $priorityLabels[$task['priority'] ?? ''] ?? ($task['priority'] ?? '');

        return "
        <div class='task-card'>
            <div class='task-title'>" . htmlspecialchars($task['title']) . "</div>
            <span class='badge badge-{$priority}'>" . htmlspecialchars($pLabel) . "</span>
            <span class='badge badge-{$status}'>"   . htmlspecialchars($sLabel) . "</span>
            {$due}
            {$desc}
        </div>";
    }

    // ── Generic info box ──────────────────────────────────────────────────────

    private static function infoBox(string $label, string $title, string $description): string
    {
        $desc = !empty($description)
            ? '<p style="margin:6px 0 0;font-size:13px;color:#475569;">' . nl2br(htmlspecialchars(mb_substr($description, 0, 200))) . '</p>'
            : '';

        return "
        <div class='info-box'>
            <div style='font-size:11px;font-weight:700;text-transform:uppercase;color:#3b82f6;margin-bottom:4px;'>{$label}</div>
            <strong>" . htmlspecialchars($title) . "</strong>
            {$desc}
        </div>";
    }
}