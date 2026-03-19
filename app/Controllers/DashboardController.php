<?php

class DashboardController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::require();

        $taskModel = new TaskModel();
        $userId    = $_SESSION['user_id'];
        $role      = $_SESSION['user_role'];
        $isAdmin   = AuthMiddleware::isAdmin();

        $this->view('dashboard/index', [
            'user'         => $this->getCurrentUser(),
            'stats'        => $taskModel->getStats($userId, $role),
            'monthlyStats' => $taskModel->getMonthlyStats(),
            'byPriority'   => $taskModel->getByPriority(),
            'overdue'      => $taskModel->getOverdueTasks(10, $userId, $role),
            'recentTasks'  => $taskModel->getRecentTasks(6, $userId, $role),
            'statsByUser'  => $isAdmin ? $taskModel->getStatsByUser() : [],
            'isAdmin'      => $isAdmin,
        ]);
    }
}
