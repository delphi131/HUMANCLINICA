<?php

declare(strict_types=1);

final class ContractRepository
{
    private PDO $pdo;
    private array $map;
    private string $table;

    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->map = Config::schema()['azienda_contratti'];
        $this->table = Database::quoteIdent($this->map['table']);
    }

    private function col(string $key): string
    {
        return Database::quoteIdent($this->map[$key]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForAzienda($aziendaId): array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = :id ORDER BY %s DESC',
            $this->table,
            $this->col('id_azienda'),
            $this->col('created_at')
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $aziendaId]);
        return $stmt->fetchAll();
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

    public function createGenerated($aziendaId, string $html, string $createdBy)
    {
        return $this->insert([
            'id_azienda' => $aziendaId,
            'source' => 'generated',
            'content' => $html,
            'created_by' => $createdBy,
        ]);
    }

    public function createUploaded($aziendaId, string $fileName, string $filePath, string $createdBy)
    {
        return $this->insert([
            'id_azienda' => $aziendaId,
            'source' => 'uploaded',
            'file_name' => $fileName,
            'file_path' => $filePath,
            'created_by' => $createdBy,
        ]);
    }

    private function insert(array $fields)
    {
        $cols = [];
        $params = [];
        foreach ($fields as $logicalName => $value) {
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

    public function delete($id): bool
    {
        $sql = sprintf('DELETE FROM %s WHERE %s = :id', $this->table, $this->col('pk'));
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
