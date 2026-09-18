<?php

declare(strict_types=1);

final class AziendaRepository
{
    private const EDITABLE = ['nome', 'piva', 'sede', 'citta', 'cap', 'rappresentante', 'telephone', 'enabled'];

    private PDO $pdo;
    private array $map;
    private string $table;

    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->map = Config::schema()['azienda'];
        $this->table = Database::quoteIdent($this->map['table']);
    }

    private function col(string $key): string
    {
        return Database::quoteIdent($this->map[$key]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAll(): array
    {
        $sql = sprintf('SELECT * FROM %s ORDER BY %s ASC', $this->table, $this->col('nome'));
        return $this->pdo->query($sql)->fetchAll();
    }

    public function find($id): ?array
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s = :id', $this->table, $this->col('pk'));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function toLogical(array $row): array
    {
        $out = [];
        foreach ($this->map as $logicalName => $column) {
            if ($logicalName === 'table') {
                continue;
            }
            $out[$logicalName] = $row[$column] ?? null;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function create(array $fields)
    {
        $cols = [];
        $params = [];
        foreach ($fields as $logicalName => $value) {
            if (!in_array($logicalName, self::EDITABLE, true) || !isset($this->map[$logicalName])) {
                continue;
            }
            $paramName = 'p_' . $logicalName;
            $cols[$this->col($logicalName)] = ':' . $paramName;
            $params[$paramName] = $value;
        }
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', array_keys($cols)),
            implode(', ', array_values($cols))
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return Database::lastInsertId($this->pdo);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function update($id, array $fields): bool
    {
        $sets = [];
        $params = ['id' => $id];
        foreach ($fields as $logicalName => $value) {
            if (!in_array($logicalName, self::EDITABLE, true) || !isset($this->map[$logicalName])) {
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

    /**
     * @return array<int, array{pk: mixed, nome: string}> For dropdowns.
     */
    public function listForDropdown(): array
    {
        $sql = sprintf('SELECT %s AS pk, %s AS nome FROM %s ORDER BY %s ASC', $this->col('pk'), $this->col('nome'), $this->table, $this->col('nome'));
        return $this->pdo->query($sql)->fetchAll();
    }
}
