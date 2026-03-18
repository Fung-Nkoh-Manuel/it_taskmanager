<?php

class AuthController extends BaseController
{
    private UserModel $users;
    private LogModel  $log;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->log   = new LogModel();
    }

    // ── GET /login ────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        AuthMiddleware::guest();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $this->viewRaw('auth/login', ['flash' => $flash]);
    }

    // ── POST /login ───────────────────────────────────────────────────────────

    public function login(): void
    {
        AuthMiddleware::guest();
        AuthMiddleware::verifyCsrf();

        $login    = trim($_POST['login']    ?? '');
        $password = trim($_POST['password'] ?? '');

        $user = $this->users->findByLogin($login);

        if (!$user || !$this->users->verifyPassword($password, $user['password'])) {
            $this->log->log('login_failed', null, null, null, "Attempt for: $login");
            $this->viewRaw('auth/login', ['error' => 'Invalid credentials. Please try again.']);
            return;
        }

        AuthMiddleware::setUser($user);
        $this->users->updateLastLogin($user['id']);
        $this->log->log('login', $user['id']);

        $intended = $_SESSION['intended'] ?? APP_URL . '/dashboard';
        unset($_SESSION['intended']);
        header('Location: ' . $intended);
        exit;
    }

    // ── GET /register ─────────────────────────────────────────────────────────

    public function registerForm(): void
    {
        AuthMiddleware::guest();
        $this->viewRaw('auth/register');
    }

    // ── POST /register ────────────────────────────────────────────────────────

    public function register(): void
    {
        AuthMiddleware::guest();
        AuthMiddleware::verifyCsrf();

        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username'  => trim($_POST['username']  ?? ''),
            'email'     => trim($_POST['email']      ?? ''),
            'password'  => $_POST['password']        ?? '',
        ];
        $confirm = $_POST['password_confirm'] ?? '';

        $errors = $this->validateRegister($data, $confirm);

        if (!empty($errors)) {
            $this->viewRaw('auth/register', ['errors' => $errors, 'old' => $data]);
            return;
        }

        $id = $this->users->create($data);
        $this->log->log('register', $id);

        $this->flash('success', 'Account created successfully. You can now log in.');
        $this->redirect('/login');
    }

    // ── GET /logout ───────────────────────────────────────────────────────────

    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $this->log->log('logout', $userId);
        AuthMiddleware::clear();
        $this->redirect('/login');
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function validateRegister(array $data, string $confirm): array
    {
        $e = [];

        if (empty($data['full_name'])) $e['full_name'] = 'Full name is required.';
        if (empty($data['username']))  $e['username']  = 'Username is required.';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'A valid email address is required.';
        }
        if (strlen($data['password']) < 6) {
            $e['password'] = 'Password must be at least 6 characters.';
        }
        if ($data['password'] !== $confirm) {
            $e['password2'] = 'Passwords do not match.';
        }
        if (empty($e['username']) && $this->users->findByUsername($data['username'])) {
            $e['username'] = 'This username is already taken.';
        }
        if (empty($e['email']) && $this->users->findByEmail($data['email'])) {
            $e['email'] = 'This email address is already registered.';
        }

        return $e;
    }
}
