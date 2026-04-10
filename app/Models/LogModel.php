<?php

class LogModel extends BaseModel
{
    protected string $table = 'activity_logs';

    public function log(string $action, ?int $userId = null, ?string $entity = null, ?int $entityId = null, ?string $details = null): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        // Support X-Forwarded-For behind Nginx proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }

        $id = $this->insert(
            'INSERT INTO activity_logs (user_id, action, entity, entity_id, details, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $action, $entity, $entityId, $details, trim($ip ?? '')]
        );

        // Email failures should never block the business action.
        try {
            $actor = null;
            if ($userId) {
                $actor = $this->queryOne('SELECT full_name FROM users WHERE id = ?', [$userId])['full_name'] ?? null;
            }

            EmailService::sendActivityLogAlert([
                'id'         => $id,
                'action'     => $action,
                'entity'     => $entity,
                'entity_id'  => $entityId,
                'details'    => $details,
                'ip_address' => trim($ip ?? ''),
                'actor_name' => $actor,
            ]);
        } catch (Throwable $e) {
            error_log('Activity log email failed: ' . $e->getMessage());
        }
    }

    public function paginated(int $page): array
    {
        return $this->paginate(
            'SELECT al.*, u.full_name
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC',
            [],
            $page
        );
    }
}
