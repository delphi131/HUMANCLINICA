<?php

declare(strict_types=1);

final class ReservationRepository
{
    private PDO $pdo;
    private array $map;
    private string $table;

    /** Logical fields an admin is allowed to edit from the UI. */
    private const EDITABLE = ['day', 'status', 'beauty_advisor', 'nome', 'cognome', 'telefono', 'email', 'price', 'pacchetto'];

    /**
     * "day" is stored as an int in YYYYMMDD format (e.g. 20260917), no time
     * component — confirmed live: reservations aren't booked for a specific
     * hour, the beauty advisor calls back the same day. These helpers
     * convert to/from that format at the repository boundary so the rest of
     * the app (JS, email placeholders, forms) only ever deals with plain
     * 'Y-m-d' date strings.
     */
    public static function toDayInt(DateTimeInterface $date): int
    {
        return (int)$date->format('Ymd');
    }

    public static function fromDayInt($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = DateTime::createFromFormat('Ymd', (string)$value);
        return $date ? $date->format('Y-m-d') : null;
    }

    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->map = Config::schema()['reservations'];
        $this->table = Database::quoteIdent($this->map['table']);
    }

    private function col(string $key): string
    {
        return Database::quoteIdent($this->map[$key]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByRange(DateTimeInterface $from, DateTimeInterface $to, ?string $status = null, ?string $search = null): array
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s >= :from AND %s < :to', $this->table, $this->col('day'), $this->col('day'));
        $params = [
            'from' => self::toDayInt($from),
            'to' => self::toDayInt($to),
        ];

        if ($status !== null && $status !== '') {
            $sql .= sprintf(' AND %s = :status', $this->col('status'));
            $params['status'] = $status;
        }

        if ($search !== null && $search !== '') {
            $sql .= sprintf(
                ' AND (%s LIKE :search OR %s LIKE :search OR %s LIKE :search OR %s LIKE :search)',
                $this->col('nome'),
                $this->col('cognome'),
                $this->col('email'),
                $this->col('telefono')
            );
            $params['search'] = '%' . $search . '%';
        }

        $sql .= sprintf(' ORDER BY %s ASC', $this->col('day'));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
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

    /**
     * @param array<string, mixed> $fields Logical field name => new value.
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

    public function logicalField(array $row, string $logicalName)
    {
        return $row[$this->map[$logicalName]] ?? null;
    }

    /**
     * Convert a raw DB row into a logical-keyed array, so the front-end and
     * API layer never need to know the real (possibly wrong-guessed) column names.
     */
    public function toLogical(array $row): array
    {
        $out = [];
        foreach ($this->map as $logicalName => $column) {
            if ($logicalName === 'table') {
                continue;
            }
            $out[$logicalName] = $row[$column] ?? null;
        }
        $out['day'] = self::fromDayInt($out['day'] ?? null);
        return $out;
    }
}
