<?php

class BaseModel
{
    protected PDO $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Generic finders ───────────────────────────────────────────────────────

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findAll(string $orderBy = 'id DESC'): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }

    // ── Query helpers ─────────────────────────────────────────────────────────

    /**
     * Run a raw SELECT and return all rows.
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Run a raw SELECT and return the first row.
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    /**
     * Run an INSERT / UPDATE / DELETE and return affected rows.
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Run an INSERT and return the new row's ID.
     */
    protected function insert(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $this->db->lastInsertId();
    }

    // ── Pagination helper ─────────────────────────────────────────────────────

    protected function paginate(string $sql, array $params, int $page, int $perPage = ITEMS_PER_PAGE): array
    {
        // Count total
        $countSql  = preg_replace('/SELECT .+? FROM /is', 'SELECT COUNT(*) FROM ', $sql);
        // Strip ORDER BY for count
        $countSql  = preg_replace('/ORDER BY .+$/is', '', $countSql);
        $stmt      = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total     = (int) $stmt->fetchColumn();

        // Fetch page
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->db->prepare($sql . " LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $items  = $stmt->fetchAll();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => (int) ceil($total / $perPage),
            'page'  => $page,
        ];
    }
}
