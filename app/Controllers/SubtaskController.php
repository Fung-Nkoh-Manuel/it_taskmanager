<?php

class SubtaskController extends BaseController
{
    private SubtaskModel      $subtasks;
    private NotificationModel $notifs;
    private LogModel          $log;
    private TaskModel         $tasks;

    public function __construct()
    {
        $this->subtasks = new SubtaskModel();
        $this->notifs   = new NotificationModel();
        $this->log      = new LogModel();
        $this->tasks    = new TaskModel();
    }

    // ── POST /tasks/{taskId}/subtasks ─────────────────────────────────────────

    public function store(array $params): void
    {
        AuthMiddleware::requireTechOrAdmin();
        AuthMiddleware::verifyCsrf();

        $taskId = (int)$params['taskId'];
        $task   = $this->tasks->find($taskId);
        if (!$task) { $this->abort(404); return; }

        $data = [
            'title'       => trim($_POST['title']       ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'assigned_to' => $_POST['assigned_to']       ?: null,
        ];

        if (empty($data['title'])) {
            $this->flash('error', 'Subtask title is required.');
            $this->redirect('/tasks/' . $taskId);
            return;
        }

        $id = $this->subtasks->create($taskId, $data, $_SESSION['user_id']);

        // Notify + email assigned user
        if (!empty($data['assigned_to']) && $data['assigned_to'] != $_SESSION['user_id']) {
            $assignedUid = (int)$data['assigned_to'];

            // In-app notification
            $this->notifs->create(
                $assignedUid,
                'assignation',
                "You have been assigned a subtask: \"{$data['title']}\" on task \"{$task['title']}\".",
                $taskId
            );

            // Email notification
            $assignedUser = (new UserModel())->find($assignedUid);
            if ($assignedUser) {
                $creatorName = $_SESSION['user_name'] ?? 'An administrator';
                EmailNotifier::subtaskAssigned(
                    $assignedUser,
                    array_merge($data, ['id' => $id]),
                    $task,
                    $creatorName
                );
            }
        }

        $this->tasks->logHistory($taskId, $_SESSION['user_id'], 'Subtask added', 'subtask', null, $data['title']);
        $this->log->log('subtask_created', $_SESSION['user_id'], 'subtask', $id, $data['title']);

        // Move parent task to in progress when first subtask is added
        $this->subtasks->checkAutoComplete($taskId);

        $this->flash('success', 'Subtask added.');
        $this->redirect('/tasks/' . $taskId . '#subtasks');
    }

    // ── POST /tasks/{taskId}/subtasks/{id}/complete ───────────────────────────

    public function complete(array $params): void
    {
        AuthMiddleware::require();
        AuthMiddleware::verifyCsrf();

        $taskId = (int)$params['taskId'];
        $id     = (int)$params['id'];

        $subtask = $this->subtasks->findWithUsers($id);
        if (!$subtask || $subtask['task_id'] != $taskId) { $this->abort(404); return; }

        // Only assigned user, technician, or admin can complete
        $userId = $_SESSION['user_id'];
        $role   = $_SESSION['user_role'];
        if (
            $subtask['assigned_to'] != $userId &&
            !in_array($role, ['admin', 'technicien'], true)
        ) {
            $this->flash('error', 'You are not allowed to complete this subtask.');
            $this->redirect('/tasks/' . $taskId);
            return;
        }

        $reportText   = trim($_POST['report_text'] ?? '');
        $uploadedFile = null;

        // Handle optional report file upload
        if (!empty($_FILES['report_file']['name']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $file  = $_FILES['report_file'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);

            if ($file['size'] > MAX_UPLOAD_SIZE) {
                $this->flash('error', 'Report file exceeds 10 MB limit.');
                $this->redirect('/tasks/' . $taskId . '#subtasks');
                return;
            }

            if (!in_array($mime, ALLOWED_MIME_TYPES, true)) {
                $this->flash('error', 'File type not allowed.');
                $this->redirect('/tasks/' . $taskId . '#subtasks');
                return;
            }

            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'report_' . bin2hex(random_bytes(12)) . ($ext ? '.' . strtolower($ext) : '');
            move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $filename);

            $uploadedFile = [
                'filename'      => $filename,
                'original_name' => $file['name'],
            ];
        }

        $this->subtasks->complete($id, $userId, $reportText, $uploadedFile);

        // Log history on parent task
        $this->tasks->logHistory(
            $taskId,
            $userId,
            'Subtask completed',
            'subtask',
            null,
            $subtask['title']
        );

        // Notify task creator and assigned user (not the completer)
        $task        = $this->tasks->find($taskId);
        $notifyUsers = array_unique(array_filter([
            $task['created_by'],
            $task['assigned_to'],
        ]));
        foreach ($notifyUsers as $uid) {
            if ($uid != $userId) {
                $this->notifs->create(
                    $uid,
                    'statut',
                    "Subtask \"{$subtask['title']}\" was completed on task \"{$subtask['task_title']}\".",
                    $taskId
                );
            }
        }

        // Auto-complete parent task if all subtasks are done
        $autoCompleted = $this->subtasks->checkAutoComplete($taskId);
        if ($autoCompleted) {
            $this->notifs->notifyStatusChange($taskId, $subtask['task_title'], 'termine', $userId);
            $this->flash('success', 'Subtask completed. All subtasks done — task marked as completed automatically!');
        } else {
            $progress = $this->subtasks->progressForTask($taskId);
            $this->flash('success', "Subtask completed. Task progress: {$progress['percent']}% ({$progress['done']}/{$progress['total']}).");
        }

        $this->redirect('/tasks/' . $taskId . '#subtasks');
    }

    // ── POST /tasks/{taskId}/subtasks/{id}/reopen ─────────────────────────────

    public function reopen(array $params): void
    {
        AuthMiddleware::requireTechOrAdmin();
        AuthMiddleware::verifyCsrf();

        $taskId = (int)$params['taskId'];
        $id     = (int)$params['id'];

        $subtask = $this->subtasks->findWithUsers($id);
        if (!$subtask || $subtask['task_id'] != $taskId) { $this->abort(404); return; }

        // Delete stored report file if exists
        if ($subtask['report_file']) {
            $path = UPLOAD_PATH . '/' . $subtask['report_file'];
            if (file_exists($path)) unlink($path);
        }

        $this->subtasks->reopen($id);
        $this->tasks->logHistory($taskId, $_SESSION['user_id'], 'Subtask reopened', 'subtask', 'termine', 'a_faire');
        $this->flash('success', 'Subtask reopened.');
        $this->redirect('/tasks/' . $taskId . '#subtasks');
    }

    // ── POST /tasks/{taskId}/subtasks/{id}/delete ─────────────────────────────

    public function destroy(array $params): void
    {
        AuthMiddleware::requireTechOrAdmin();
        AuthMiddleware::verifyCsrf();

        $taskId = (int)$params['taskId'];
        $id     = (int)$params['id'];

        $subtask = $this->subtasks->find($id);
        if (!$subtask || $subtask['task_id'] != $taskId) { $this->abort(404); return; }

        // Delete report file if exists
        if ($subtask['report_file']) {
            $path = UPLOAD_PATH . '/' . $subtask['report_file'];
            if (file_exists($path)) unlink($path);
        }

        $this->subtasks->delete($id);

        $this->tasks->logHistory(
            $taskId,
            $_SESSION['user_id'],
            'Subtask deleted',
            'subtask',
            $subtask['title'],
            null
        );

        $this->log->log(
            'subtask_deleted',
            $_SESSION['user_id'],
            'subtask',
            $id,
            $subtask['title']
        );

        // If task was completed and now has unfinished subtasks, revert status
        $this->subtasks->checkAutoComplete($taskId);

        $this->flash('success', 'Subtask deleted.');
        $this->redirect('/tasks/' . $taskId . '#subtasks');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function abort(int $code): void
    {
        http_response_code($code);
        $message = $code === 404 ? 'Not found.' : 'Access denied.';
        require_once VIEW_PATH . '/partials/error.php';
        exit;
    }
}