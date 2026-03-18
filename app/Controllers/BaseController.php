<?php

class BaseController
{
    // ── Render a view inside the main layout ──────────────────────────────────

    protected function view(string $view, array $data = []): void
    {
        // Trigger deadline notifications on every page load for logged-in users
        if (!empty($_SESSION['user_id'])) {
            $notifModel = new NotificationModel();
            $notifModel->generateDeadlineAlerts();
            $data['unreadCount'] = $notifModel->countUnread($_SESSION['user_id']);
        } else {
            $data['unreadCount'] = 0;
        }

        $data['csrfToken']   = AuthMiddleware::generateCsrfToken();
        $data['currentUser'] = $this->getCurrentUser();

        // Make all data keys available as variables in the view
        extract($data);

        $content = VIEW_PATH . '/' . $view . '.php';

        if (!file_exists($content)) {
            http_response_code(500);
            error_log("View not found: $content");
            exit("View '$view' not found.");
        }

        require_once VIEW_PATH . '/partials/layout.php';
    }

    // ── Render a standalone view (no layout) ─────────────────────────────────

    protected function viewRaw(string $view, array $data = []): void
    {
        $data['csrfToken'] = AuthMiddleware::generateCsrfToken();
        extract($data);

        $file = VIEW_PATH . '/' . $view . '.php';
        if (!file_exists($file)) {
            exit("View '$view' not found.");
        }
        require_once $file;
    }

    // ── Redirects ─────────────────────────────────────────────────────────────

    protected function redirect(string $path): void
    {
        header('Location: ' . APP_URL . $path);
        exit;
    }

    protected function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? APP_URL . '/dashboard';
        header('Location: ' . $ref);
        exit;
    }

    // ── JSON response ─────────────────────────────────────────────────────────

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ── Flash messages ────────────────────────────────────────────────────────

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    // ── Current user ─────────────────────────────────────────────────────────

    protected function getCurrentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $userModel = new UserModel();
        return $userModel->find($_SESSION['user_id']);
    }

    // ── Input helpers ─────────────────────────────────────────────────────────

    protected function input(string $key, mixed $default = ''): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function query(string $key, mixed $default = ''): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}
