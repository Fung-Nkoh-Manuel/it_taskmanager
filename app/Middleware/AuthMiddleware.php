<?php

class AuthMiddleware
{
    // ── Authentication gate ───────────────────────────────────────────────────

    public static function require(): void
    {
        if (empty($_SESSION['user_id'])) {
            $_SESSION['intended'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }

    public static function guest(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
    }

    // ── Role checks ──────────────────────────────────────────────────────────

    public static function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function isTechOrAdmin(): bool
    {
        return in_array($_SESSION['user_role'] ?? '', ['admin', 'technicien'], true);
    }

    public static function requireAdmin(): void
    {
        self::require();
        if (!self::isAdmin()) {
            http_response_code(403);
            $message = 'Access denied. Administrator privileges required.';
            require_once VIEW_PATH . '/partials/error.php';
            exit;
        }
    }

    public static function requireTechOrAdmin(): void
    {
        self::require();
        if (!self::isTechOrAdmin()) {
            http_response_code(403);
            $message = 'Access denied.';
            require_once VIEW_PATH . '/partials/error.php';
            exit;
        }
    }

    // ── CSRF ─────────────────────────────────────────────────────────────────

    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            http_response_code(419);
            $message = 'CSRF token mismatch. Please go back and try again.';
            require_once VIEW_PATH . '/partials/error.php';
            exit;
        }
    }

    // ── Session helpers ───────────────────────────────────────────────────────

    public static function setUser(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
    }

    public static function clear(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
