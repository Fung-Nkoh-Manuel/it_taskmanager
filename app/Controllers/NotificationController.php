<?php

class NotificationController extends BaseController
{
    private NotificationModel $notifs;

    public function __construct()
    {
        $this->notifs = new NotificationModel();
    }

    public function index(): void
    {
        AuthMiddleware::require();
        $this->view('notifications/index', [
            'notifications' => $this->notifs->forUser($_SESSION['user_id']),
        ]);
    }

    public function markRead(array $params): void
    {
        AuthMiddleware::require();
        AuthMiddleware::verifyCsrf();
        $this->notifs->markRead((int)$params['id'], $_SESSION['user_id']);
        $this->redirect('/notifications');
    }

    public function readAll(): void
    {
        AuthMiddleware::require();
        AuthMiddleware::verifyCsrf();
        $this->notifs->markAllRead($_SESSION['user_id']);
        $this->redirect('/notifications');
    }

    public function sendDueReminders(): void
    {
        AuthMiddleware::requireAdmin();
        AuthMiddleware::verifyCsrf();

        $queued = 0;
        try {
            $queued = EmailService::sendDueDateReminders();
        } catch (Throwable $e) {
            error_log('Due reminder trigger failed: ' . $e->getMessage());
        }

        $this->flash('success', "Due date reminder emails queued: {$queued}.");
        $this->redirect('/notifications');
    }
}
