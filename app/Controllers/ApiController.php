<?php

class ApiController extends BaseController
{
    private TaskModel $tasks;

    public function __construct()
    {
        $this->tasks = new TaskModel();
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    private function authenticate(): void
    {
        // Accept active web session
        if (!empty($_SESSION['user_id'])) return;

        // Accept Bearer token: md5(id + email + password_hash)
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
            $token    = trim($m[1]);
            $userModel = new UserModel();
            // We iterate through users — in production use a dedicated api_tokens table
            $users = $userModel->findAll();
            foreach ($users as $u) {
                if (md5($u['id'] . $u['email'] . $u['password']) === $token) {
                    $_SESSION['user_id']   = $u['id'];
                    $_SESSION['user_role'] = $u['role'];
                    return;
                }
            }
        }

        $this->json(['error' => 'Unauthorized'], 401);
    }

    // ── GET /api/tasks ────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->authenticate();

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'search'      => $_GET['search']      ?? '',
            'status'      => $_GET['status']       ?? '',
            'priority'    => $_GET['priority']     ?? '',
            'assigned_to' => $_GET['assigned_to']  ?? '',
        ];

        $result = $this->tasks->paginated(
            $filters, $page,
            $_SESSION['user_id'],
            $_SESSION['user_role']
        );

        $this->json([
            'data'  => $result['items'],
            'meta'  => [
                'total' => $result['total'],
                'pages' => $result['pages'],
                'page'  => $result['page'],
            ],
        ]);
    }

    // ── GET /api/tasks/{id} ───────────────────────────────────────────────────

    public function show(array $params): void
    {
        $this->authenticate();

        $task = $this->tasks->findWithUsers((int)$params['id']);
        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
            return;
        }

        $this->json([
            'task'        => $task,
            'comments'    => $this->tasks->getComments($task['id']),
            'attachments' => $this->tasks->getAttachments($task['id']),
            'history'     => $this->tasks->getHistory($task['id']),
        ]);
    }

    // ── POST /api/tasks ───────────────────────────────────────────────────────

    public function store(): void
    {
        $this->authenticate();

        if (!AuthMiddleware::isTechOrAdmin()) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $body = $this->jsonBody();

        if (empty($body['title'])) {
            $this->json(['error' => 'Title is required'], 422);
            return;
        }

        $id = $this->tasks->create($body, $_SESSION['user_id']);

        if (!empty($body['assigned_to'])) {
            try {
                EmailService::sendTaskAssigned((int)$body['assigned_to'], $id);
            } catch (Throwable $e) {
                error_log('API assignment email failed: ' . $e->getMessage());
            }
        }

        if (in_array((string)($body['priority'] ?? ''), ['critique', 'haute'], true)) {
            try {
                EmailService::sendHighPriorityTaskCreatedAdminSummary($id);
            } catch (Throwable $e) {
                error_log('API high priority summary email failed: ' . $e->getMessage());
            }
        }

        $this->json(['id' => $id, 'message' => 'Task created'], 201);
    }

    // ── POST /api/tasks/{id} ──────────────────────────────────────────────────

    public function update(array $params): void
    {
        $this->authenticate();

        if (!AuthMiddleware::isTechOrAdmin()) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $id   = (int)$params['id'];
        $task = $this->tasks->findWithUsers($id);

        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
            return;
        }

        $body = $this->jsonBody();

        // Support partial date-only updates (from calendar drag & drop)
        if (isset($body['start_date']) || isset($body['due_date'])) {
            $this->tasks->updateDates(
                $id,
                $body['start_date'] ?? $task['start_date'],
                $body['due_date']   ?? $task['due_date']
            );
            $this->json(['message' => 'Dates updated']);
            return;
        }

        // Full update
        $merged = array_merge([
            'title'       => $task['title'],
            'description' => $task['description'],
            'priority'    => $task['priority'],
            'status'      => $task['status'],
            'assigned_to' => $task['assigned_to'],
            'start_date'  => $task['start_date'],
            'due_date'    => $task['due_date'],
        ], $body);

        $this->tasks->update($id, $merged);

        if (($merged['status'] ?? '') !== ($task['status'] ?? '')) {
            try {
                EmailService::sendTaskStatusChanged($id, (string)$task['status'], (string)$merged['status']);
            } catch (Throwable $e) {
                error_log('API status email failed: ' . $e->getMessage());
            }
        }

        $this->json(['message' => 'Task updated']);
    }

    // ── POST /api/tasks/{id}/status ───────────────────────────────────────────

    public function updateStatus(array $params): void
    {
        $this->authenticate();

        if (!AuthMiddleware::isTechOrAdmin()) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $id     = (int)$params['id'];
        $status = $_POST['status'] ?? ($this->jsonBody()['status'] ?? '');

        $allowed = ['a_faire', 'en_cours', 'termine', 'bloque'];
        if (!in_array($status, $allowed, true)) {
            $this->json(['error' => 'Invalid status'], 422);
            return;
        }

        $task = $this->tasks->find($id);
        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
            return;
        }

        $oldStatus = (string)($task['status'] ?? '');
        $this->tasks->updateStatus($id, $status);

        $notifs = new NotificationModel();
        $notifs->notifyStatusChange($id, $task['title'], $status, $_SESSION['user_id']);

        try {
            EmailService::sendTaskStatusChanged($id, $oldStatus, $status);
        } catch (Throwable $e) {
            error_log('API status-change email failed: ' . $e->getMessage());
        }

        $this->json(['message' => 'Status updated', 'status' => $status]);
    }

    // ── POST /api/tasks/{id}/delete ───────────────────────────────────────────

    public function destroy(array $params): void
    {
        $this->authenticate();

        if (!AuthMiddleware::isAdmin()) {
            $this->json(['error' => 'Forbidden'], 403);
            return;
        }

        $id   = (int)$params['id'];
        $task = $this->tasks->find($id);

        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
            return;
        }

        $this->tasks->delete($id);
        $this->json(['message' => 'Task deleted']);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return $_POST;
    }
}
