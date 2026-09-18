<?php

declare(strict_types=1);

final class UserRepository
{
    private PDO $pdo;
    private array $map;
    private string $table;

    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->map = Config::schema()['users'];
        $this->table = Database::quoteIdent($this->map['table']);
    }

    private function col(string $key): string
    {
        return Database::quoteIdent($this->map[$key]);
    }

    public function findByUsername(string $username): ?array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :username',
            $this->table,
            $this->col('username')
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById($id): ?array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :id',
            $this->table,
            $this->col('pk')
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Set/replace the bcrypt password used by this PHP panel, without
     * touching the legacy Password column used by the ASP.NET app.
     */
    public function setPasswordHash(string $username, string $plainPassword): bool
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $sql = sprintf(
            'UPDATE %s SET %s = :hash WHERE %s = :username',
            $this->table,
            $this->col('password_hash'),
            $this->col('username')
        );
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['hash' => $hash, 'username' => $username]);
    }

    /**
     * @param array $user A raw DB row (physical column keys), e.g. from
     *   findByUsername()/findById() — NOT the toLogical() output.
     */
    public function isAdmin(array $user): bool
    {
        $value = $user[$this->map['type_profile']] ?? null;
        if ($value === null) {
            return false;
        }
        // type_profile can be numeric (tinyint) or text depending on the
        // deployment, so compare loosely as trimmed, case-insensitive strings.
        return strcasecmp(trim((string)$value), (string)$this->map['type_profile_admin_value']) === 0;
    }

    /**
     * @param array $user A raw DB row (physical column keys), e.g. from
     *   findByUsername()/findById() — NOT the toLogical() output.
     */
    public function displayName(array $user): string
    {
        $nome = trim((string)($user[$this->map['nome']] ?? ''));
        $cognome = trim((string)($user[$this->map['cognome']] ?? ''));
        $full = trim($nome . ' ' . $cognome);
        return $full !== '' ? $full : (string)($user[$this->map['username']] ?? '');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        $sql = sprintf('SELECT * FROM %s ORDER BY %s ASC', $this->table, $this->col('username'));
        return $this->pdo->query($sql)->fetchAll();
    }

    public function toLogical(array $row): array
    {
        $out = [];
        foreach ($this->map as $logicalName => $column) {
            if ($logicalName === 'table' || !is_string($column)) {
                continue;
            }
            $out[$logicalName] = $row[$column] ?? null;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $fields Logical field name => value (username, nome, cognome, email, telephone, id_azienda, type_profile).
     */
    public function create(array $fields, string $plainPassword): bool
    {
        $editable = ['username', 'nome', 'cognome', 'email', 'telephone', 'id_azienda', 'type_profile'];
        $cols = [];
        $params = [];
        foreach ($fields as $logicalName => $value) {
            if (!in_array($logicalName, $editable, true) || !isset($this->map[$logicalName])) {
                continue;
            }
            $paramName = 'p_' . $logicalName;
            $cols[$this->col($logicalName)] = ':' . $paramName;
            $params[$paramName] = $value;
        }
        $cols[$this->col('password_hash')] = ':p_hash';
        $params['p_hash'] = password_hash($plainPassword, PASSWORD_BCRYPT);

        // The legacy Password column is NOT NULL on the live DB, but its
        // reversible-cipher format/key is unknown (see README) so we can't
        // write a value the ASP.NET app could ever decrypt correctly. A
        // random placeholder just satisfies the constraint — users created
        // here can only log into this PHP panel, not the ASP.NET site,
        // until an admin sets a real legacy password for them there.
        $cols[$this->col('password_legacy')] = ':p_legacy';
        $params['p_legacy'] = bin2hex(random_bytes(16));

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', array_keys($cols)),
            implode(', ', array_values($cols))
        );
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * @param array<string, mixed> $fields Logical field name => value.
     */
    public function update($id, array $fields): bool
    {
        $editable = ['nome', 'cognome', 'email', 'telephone', 'id_azienda', 'type_profile'];
        $sets = [];
        $params = ['id' => $id];
        foreach ($fields as $logicalName => $value) {
            if (!in_array($logicalName, $editable, true) || !isset($this->map[$logicalName])) {
                continue;
            }
            $paramName = 'p_' . $logicalName;
            $sets[] = sprintf('%s = :%s', $this->col($logicalName), $paramName);
            $params[$paramName] = $value;
        }
        if (empty($sets)) {
            return false;
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s = :id', $this->table, implode(', ', $sets), $this->col('pk'));
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id): bool
    {
        $sql = sprintf('DELETE FROM %s WHERE %s = :id', $this->table, $this->col('pk'));
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
