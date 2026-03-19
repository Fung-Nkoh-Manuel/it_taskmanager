<?php

class CalendarController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::require();
        $this->view('calendar/index', []);
    }

    /**
     * GET /calendar/events?start=YYYY-MM-DD&end=YYYY-MM-DD
     * Returns FullCalendar-compatible JSON events.
     */
    public function events(): void
    {
        AuthMiddleware::require();

        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end']   ?? date('Y-m-t');

        // Basic date validation
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $start)) $start = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $end))   $end   = date('Y-m-t');

        $taskModel = new TaskModel();
        $tasks = $taskModel->getForCalendar(
            substr($start, 0, 10),
            substr($end, 0, 10),
            $_SESSION['user_id'],
            $_SESSION['user_role']
        );

        $colors = [
            'critique' => '#ef4444',
            'haute'    => '#f97316',
            'moyenne'  => '#3b82f6',
            'basse'    => '#22c55e',
        ];

        $borderColors = [
            'a_faire'  => '#64748b',
            'en_cours' => '#3b82f6',
            'termine'  => '#22c55e',
            'bloque'   => '#ef4444',
        ];

        $events = [];
        foreach ($tasks as $t) {
            $end = $t['due_date']
                ? date('Y-m-d', strtotime($t['due_date'] . ' +1 day'))  // FullCalendar end is exclusive
                : null;

            $events[] = [
                'id'    => $t['id'],
                'title' => $t['title'],
                'start' => $t['start_date'],
                'end'   => $end,
                'backgroundColor' => $colors[$t['priority']]     ?? '#94a3b8',
                'borderColor'     => $borderColors[$t['status']] ?? '#64748b',
                'extendedProps'   => [
                    'priority'      => $t['priority'],
                    'status'        => $t['status'],
                    'assigned_name' => $t['assigned_name'],
                    'url'           => APP_URL . '/tasks/' . $t['id'],
                ],
            ];
        }

        $this->json($events);
    }
}
