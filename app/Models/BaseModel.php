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

    /**
     * @param string      $sql       Full SELECT query with ORDER BY
     * @param array       $params    Bound parameters for both count and data query
     * @param int         $page      Current page number
     * @param int         $perPage   Items per page
     * @param string|null $countSql  Optional explicit COUNT query (recommended when
     *                               the main query uses GROUP BY or complex SELECTs)
     */
    protected function paginate(
        string  $sql,
        array   $params,
        int     $page,
        int     $perPage  = ITEMS_PER_PAGE,
        ?string $countSql = null
    ): array {
        // If explicit countSql provided it uses no bound params (built with interpolation)
        // If not provided, use the main SQL as a subquery wrapper
        if ($countSql !== null) {
            $countParams = [];
        } else {
            $countSql    = "SELECT COUNT(*) FROM (" . $sql . ") AS _pcount";
            $countParams = $params;
        }

        $stmt = $this->db->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int) $stmt->fetchColumn();

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