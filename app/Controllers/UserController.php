<?php
// ════════════════════════════════════════════════════════════
//  UserController
// ════════════════════════════════════════════════════════════
class UserController extends BaseController
{
    private UserModel $users;
    private LogModel  $log;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->log   = new LogModel();
    }

    public function index(): void
    {
        AuthMiddleware::requireAdmin();
        $page   = max(1, (int)$this->query('page', 1));
        $result = $this->users->paginated($page);

        $this->view('users/index', [
            'users' => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page'  => $page,
        ]);
    }

    public function create(): void
    {
        AuthMiddleware::requireAdmin();
        $this->view('users/form', ['user' => []]);
    }

    public function store(): void
    {
        AuthMiddleware::requireAdmin();
        AuthMiddleware::verifyCsrf();

        $data   = $this->collectInput();
        $errors = $this->validate($data, false);

        if (!empty($errors)) {
            $this->view('users/form', ['user' => $data, 'errors' => $errors]);
            return;
        }

        $id = $this->users->create($data);
        $this->log->log('user_created', $_SESSION['user_id'], 'user', $id, $data['username']);
        $this->flash('success', 'User created successfully.');
        $this->redirect('/users');
    }

    public function edit(array $p): void
    {
        AuthMiddleware::requireAdmin();
        $user = $this->users->find((int)$p['id']);
        if (!$user) { $this->abort(404); return; }
        $this->view('users/form', ['user' => $user]);
    }

    public function update(array $p): void
    {
        AuthMiddleware::requireAdmin();
        AuthMiddleware::verifyCsrf();

        $id   = (int)$p['id'];
        $user = $this->users->find($id);
        if (!$user) { $this->abort(404); return; }

        $data             = $this->collectInput();
        $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
        $errors           = $this->validate($data, true, $id);

        if (!empty($errors)) {
            $this->view('users/form', ['user' => array_merge($user, $data), 'errors' => $errors]);
            return;
        }

        // Check if role changed before updating
        $oldRole = $user['role'];
        $newRole = $data['role'];

        $this->users->update($id, $data);
        $this->log->log('user_updated', $_SESSION['user_id'], 'user', $id);

        // Notify user if their role was changed
        if ($oldRole !== $newRole) {
            $notifs = new NotificationModel();
            $notifs->notifyRoleChange($id, $oldRole, $newRole);
        }

        $this->flash('success', 'User updated.');
        $this->redirect('/users');
    }

    public function destroy(array $p): void
    {
        AuthMiddleware::requireAdmin();
        AuthMiddleware::verifyCsrf();

        $id = (int)$p['id'];
        if ($id === $_SESSION['user_id']) {
            $this->flash('error', 'You cannot delete your own account.');
            $this->redirect('/users');
            return;
        }
        $this->users->delete($id);
        $this->log->log('user_deleted', $_SESSION['user_id'], 'user', $id);
        $this->flash('success', 'User deleted.');
        $this->redirect('/users');
    }

    public function toggle(array $p): void
    {
        AuthMiddleware::requireAdmin();
        AuthMiddleware::verifyCsrf();

        $id = (int)$p['id'];
        if ($id !== $_SESSION['user_id']) {
            $this->users->toggle($id);
            $this->log->log('user_toggled', $_SESSION['user_id'], 'user', $id);
        }
        $this->redirect('/users');
    }

    private function collectInput(): array
    {
        return [
            'username'  => trim($_POST['username']  ?? ''),
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email'     => trim($_POST['email']      ?? ''),
            'role'      => $_POST['role']             ?? 'utilisateur',
            'password'  => $_POST['password']         ?? '',
        ];
    }

    private function validate(array $d, bool $isEdit, ?int $excludeId = null): array
    {
        $e = [];
        if (empty($d['full_name'])) $e['full_name'] = 'Full name is required.';
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Valid email required.';
        if (!$isEdit && empty($d['username'])) $e['username'] = 'Username is required.';
        if (!$isEdit && strlen($d['password']) < 6) $e['password'] = 'Password must be at least 6 characters.';
        if (!$isEdit && empty($e['username']) && $this->users->findByUsername($d['username'])) {
            $e['username'] = 'Username already taken.';
        }
        if (empty($e['email']) && $this->users->findByEmail($d['email'], $excludeId)) {
            $e['email'] = 'Email already in use.';
        }
        return $e;
    }

    private function abort(int $code): void
    {
        http_response_code($code);
        $message = $code === 404 ? 'User not found.' : 'Access denied.';
        require_once VIEW_PATH . '/partials/error.php';
        exit;
    }
}
