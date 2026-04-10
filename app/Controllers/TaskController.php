<?php

class TaskController extends BaseController
{
    private TaskModel         $tasks;
    private NotificationModel $notifs;
    private LogModel          $log;

    public function __construct()
    {
        $this->tasks  = new TaskModel();
        $this->notifs = new NotificationModel();
        $this->log    = new LogModel();
    }

    // ── GET /tasks ────────────────────────────────────────────────────────────

    public function index(): void
    {
        AuthMiddleware::require();

        $userId  = $_SESSION['user_id'];
        $role    = $_SESSION['user_role'];
        $isTech  = AuthMiddleware::isTechOrAdmin();
        $page    = max(1, (int)($this->query('page') ?: 1));

        $filters = [
            'search'      => $this->query('search'),
            'status'      => $this->query('status'),
            'priority'    => $this->query('priority'),
            'assigned_to' => $this->query('assigned_to'),
        ];

        $result = $this->tasks->paginated($filters, $page, $userId, $role);

        // Fetch subtask progress for all tasks on this page
        $taskIds  = array_column($result['items'], 'id');
        $progress = (new SubtaskModel())->progressForTasks($taskIds);

        $this->view('tasks/index', [
            'tasks'    => $result['items'],
            'total'    => $result['total'],
            'pages'    => $result['pages'],
            'page'     => $page,
            'filters'  => $filters,
            'isTech'   => $isTech,
            'users'    => $isTech ? (new UserModel())->allForSelect() : [],
            'progress' => $progress,
        ]);
    }

    // ── GET /tasks/{id} ───────────────────────────────────────────────────────

    public function show(array $params): void
    {
        AuthMiddleware::require();

        $task = $this->tasks->findWithUsers((int)$params['id']);
        if (!$task) { $this->notFound(); return; }

        $this->view('tasks/show', [
            'task'        => $task,
            'comments'    => $this->tasks->getComments($task['id']),
            'attachments' => $this->tasks->getAttachments($task['id']),
            'history'     => $this->tasks->getHistory($task['id']),
            'subtasks'    => (new SubtaskModel())->forTask($task['id']),
            'progress'    => (new SubtaskModel())->progressForTask($task['id']),
            'users'       => (new UserModel())->allForSelect(),
            'assignees'   => $this->tasks->getAssignees($task['id']),
        ]);
    }

    // ── GET /tasks/create ─────────────────────────────────────────────────────

    public function create(): void
    {
        AuthMiddleware::requireTechOrAdmin();

        $this->view('tasks/form', [
            'task'        => [],
            'users'       => (new UserModel())->allForSelect(),
            'assigneeIds' => [],
        ]);
    }

    // ── POST /tasks ───────────────────────────────────────────────────────────

    public function store(): void
    {
        AuthMiddleware::requireTechOrAdmin();
        AuthMiddleware::verifyCsrf();

        $data   = $this->collectTaskInput();
        $errors = $this->validateTask($data);

        if (!empty($errors)) {
            $this->view('tasks/form', [
                'task'        => $data,
                'users'       => (new UserModel())->allForSelect(),
                'assigneeIds' => $this->collectAssignees(),
                'errors'      => $errors,
            ]);
            return;
        }

        $id = $this->tasks->create($data, $_SESSION['user_id']);

        // Sync multiple assignees
        $assignees = $this->collectAssignees();
        $this->tasks->syncAssignees($id, $assignees);

        $this->tasks->logHistory($id, $_SESSION['user_id'], 'Task created');

        // Notify all assigned users except the creator
        foreach ($assignees as $uid) {
            if ((int)$uid !== (int)$_SESSION['user_id']) {
                $this->notifs->notifyAssignment((int)$uid, $data['title'], $id);
                try {
                    EmailService::sendTaskAssigned((int)$uid, $id);
                } catch (Throwable $e) {
                    error_log('Task assignment email failed: ' . $e->getMessage());
                }
            }
        }

        if (in_array($data['priority'], ['critique', 'haute'], true)) {
            try {
                EmailService::sendHighPriorityTaskCreatedAdminSummary($id);
            } catch (Throwable $e) {
                error_log('High-priority admin summary email failed: ' . $e->getMessage());
            }
        }

        $this->log->log('task_created', $_SESSION['user_id'], 'task', $id, $data['title']);
        $this->flash('success', 'Task created successfully.');
        $this->redirect('/tasks/' . $id);
    }

    // ── GET /tasks/{id}/edit ──────────────────────────────────────────────────

    public function edit(array $params): void
    {
        AuthMiddleware::requireTechOrAdmin();

        $id   = (int)$params['id'];
        $task = $this->tasks->findWithUsers($id);
        if (!$task) { $this->notFound(); return; }

        $this->view('tasks/form', [
            'task'        => $task,
            'users'       => (new UserModel())->allForSelect(),
            'assigneeIds' => $this->tasks->getAssigneeIds($id),
        ]);
    }

    // ── POST /tasks/{id} ─────────────────────────────────────────────────────

    public function update(array $params): void
    {
        AuthMiddleware::requireTechOrAdmin();
        AuthMiddleware::verifyCsrf();

        $id   = (int)$params['id'];
        $old  = $this->tasks->findWithUsers($id);
        if (!$old) { $this->notFound(); return; }

        $data   = $this->collectTaskInput();
        $errors = $this->validateTask($data);

        if (!empty($errors)) {
            $this->view('tasks/form', [
                'task'        => array_merge($old, $data),
                'users'       => (new UserModel())->allForSelect(),
                'assigneeIds' => $this->collectAssignees(),
                'errors'      => $errors,
            ]);
            return;
        }

        // Get old assignees BEFORE any changes — cast to int for reliable array_diff
        $oldAssignees = array_map('intval', $this->tasks->getAssigneeIds($id));

        // Track changes for history
        $this->trackChanges($id, $old, $data);

        $this->tasks->update($id, $data);

        // Sync multiple assignees
        $assignees = $this->collectAssignees();
        $this->tasks->syncAssignees($id, $assignees);

        // Notify only users who were NOT previously assigned
        $newlyAdded = array_diff($assignees, $oldAssignees);
        foreach ($newlyAdded as $uid) {
            if ((int)$uid !== (int)$_SESSION['user_id']) {
                $this->notifs->notifyAssignment((int)$uid, $data['title'], $id);
                try {
                    EmailService::sendTaskAssigned((int)$uid, $id);
                } catch (Throwable $e) {
                    error_log('Task reassignment email failed: ' . $e->getMessage());
                }
            }
        }

        // Notify on status change
        if ($data['status'] !== $old['status']) {
            $this->notifs->notifyStatusChange($id, $data['title'], $data['status'], $_SESSION['user_id']);
            try {
                EmailService::sendTaskStatusChanged($id, (string)$old['status'], (string)$data['status']);
            } catch (Throwable $e) {
                error_log('Task status email failed: ' . $e->getMessage());
            }
        }

        $this->log->log('task_updated', $_SESSION['user_id'], 'task', $id, $data['title']);
        $this->flash('success', 'Task updated successfully.');
        $this->redirect('/tasks/' . $id);
    }

    // ── POST /tasks/{id}/delete ───────────────────────────────────────────────

    public function destroy(array $params): void
    {
        AuthMiddleware::requireAdmin();
        AuthMiddleware::verifyCsrf();

        $id   = (int)$params['id'];
        $task = $this->tasks->find($id);
        if (!$task) { $this->notFound(); return; }

        // Delete physical attachments
        foreach ($this->tasks->getAttachments($id) as $att) {
            $file = UPLOAD_PATH . '/' . $att['filename'];
            if (file_exists($file)) unlink($file);
        }

        $this->tasks->delete($id);
        $this->log->log('task_deleted', $_SESSION['user_id'], 'task', $id, $task['title']);
        $this->flash('success', 'Task deleted.');
        $this->redirect('/tasks');
    }

    // ── POST /tasks/{id}/comment ──────────────────────────────────────────────

    public function addComment(array $params): void
    {
        AuthMiddleware::require();
        AuthMiddleware::verifyCsrf();

        $id      = (int)$params['id'];
        $task    = $this->tasks->findWithUsers($id);
        $content = trim($_POST['content'] ?? '');

        if (!$task || empty($content)) {
            $this->flash('error', 'Comment cannot be empty.');
            $this->redirect('/tasks/' . $id);
            return;
        }

        $this->tasks->addComment($id, $_SESSION['user_id'], $content);
        $this->notifs->notifyComment($id, $task['title'], $_SESSION['user_id']);
        $this->tasks->logHistory($id, $_SESSION['user_id'], 'Comment added');

        try {
            EmailService::sendNewComment($id, (int)$_SESSION['user_id'], $content);
        } catch (Throwable $e) {
            error_log('Comment email failed: ' . $e->getMessage());
        }

        $this->redirect('/tasks/' . $id . '#comments');
    }

    // ── POST /tasks/{id}/upload ───────────────────────────────────────────────

    public function uploadAttachment(array $params): void
    {
        AuthMiddleware::require();
        AuthMiddleware::verifyCsrf();

        $id   = (int)$params['id'];
        $task = $this->tasks->find($id);

        if (!$task) { $this->notFound(); return; }

        if (empty($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'File upload failed.');
            $this->redirect('/tasks/' . $id . '#attachments');
            return;
        }

        $file = $_FILES['attachment'];

        // Validate size
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            $this->flash('error', 'File exceeds the 10 MB limit.');
            $this->redirect('/tasks/' . $id . '#attachments');
            return;
        }

        // Validate MIME
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
            $this->flash('error', 'File type not allowed.');
            $this->redirect('/tasks/' . $id . '#attachments');
            return;
        }

        // Store with UUID filename
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . ($ext ? '.' . strtolower($ext) : '');
        move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $filename);

        $this->tasks->addAttachment($id, $_SESSION['user_id'], [
            'filename'      => $filename,
            'original_name' => $file['name'],
            'mime_type'     => $mimeType,
            'file_size'     => $file['size'],
        ]);

        $this->tasks->logHistory($id, $_SESSION['user_id'], 'Attachment added', null, null, $file['name']);

        try {
            EmailService::sendFileAttached($id, $file['name'], (int)$_SESSION['user_id']);
        } catch (Throwable $e) {
            error_log('Attachment email failed: ' . $e->getMessage());
        }

        $this->flash('success', 'Attachment uploaded.');
        $this->redirect('/tasks/' . $id . '#attachments');
    }

    // ── POST /tasks/{id}/delete-attachment/{attId} ────────────────────────────

    public function deleteAttachment(array $params): void
    {
        AuthMiddleware::requireTechOrAdmin();
        AuthMiddleware::verifyCsrf();

        $taskId = (int)$params['id'];
        $attId  = (int)$params['attId'];
        $att    = $this->tasks->findAttachment($attId);

        if ($att) {
            $file = UPLOAD_PATH . '/' . $att['filename'];
            if (file_exists($file)) unlink($file);
            $this->tasks->deleteAttachment($attId);
            $this->tasks->logHistory($taskId, $_SESSION['user_id'], 'Attachment deleted', null, $att['original_name']);
        }

        $this->flash('success', 'Attachment deleted.');
        $this->redirect('/tasks/' . $taskId . '#attachments');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function collectAssignees(): array
    {
        // Support both multi-select array and single select
        $raw = $_POST['assigned_users'] ?? [];
        if (!is_array($raw)) $raw = [$raw];
        return array_filter(array_map('intval', $raw));
    }

    private function collectTaskInput(): array
    {
        // Derive primary assigned_to from first selected user in the multi-select
        $assignedUsers   = $_POST['assigned_users'] ?? [];
        if (!is_array($assignedUsers)) $assignedUsers = [$assignedUsers];
        $assignedUsers   = array_values(array_filter(array_map('intval', $assignedUsers)));
        $primaryAssignee = !empty($assignedUsers) ? $assignedUsers[0] : null;

        return [
            'title'       => trim($_POST['title']       ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'priority'    => $_POST['priority']          ?? 'moyenne',
            'status'      => $_POST['status']            ?? 'a_faire',
            'assigned_to' => $primaryAssignee,
            'start_date'  => $_POST['start_date']        ?: null,
            'due_date'    => $_POST['due_date']          ?: null,
        ];
    }

    private function validateTask(array $data): array
    {
        $e = [];
        if (empty($data['title'])) $e['title'] = 'Title is required.';
        if (!empty($data['due_date']) && !empty($data['start_date'])
            && $data['due_date'] < $data['start_date']) {
            $e['due_date'] = 'Due date cannot be before start date.';
        }
        return $e;
    }

    private function trackChanges(int $taskId, array $old, array $new): void
    {
        $tracked = ['title', 'status', 'priority', 'assigned_to', 'due_date'];
        foreach ($tracked as $field) {
            if (($old[$field] ?? '') != ($new[$field] ?? '')) {
                $this->tasks->logHistory(
                    $taskId,
                    $_SESSION['user_id'],
                    'Field updated',
                    $field,
                    (string)($old[$field] ?? ''),
                    (string)($new[$field] ?? '')
                );
            }
        }
    }

    private function notFound(): void
    {
        http_response_code(404);
        $message = 'Task not found.';
        require_once VIEW_PATH . '/partials/error.php';
        exit;
    }
}