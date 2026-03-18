<?php

class LogController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requireAdmin();

        $page   = max(1, (int)$this->query('page', 1));
        $result = (new LogModel())->paginated($page);

        $this->view('logs/index', [
            'logs'  => $result['items'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page'  => $page,
        ]);
    }
}
