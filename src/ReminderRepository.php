<?php

declare(strict_types=1);

/**
 * Standalone calendar reminders ("promemoria") an admin/Beauty Advisor can
 * put on the calendar — not real bookings, so they live in their own table
 * (tPHPReminders) rather than tReservations. Optionally send an email when
 * created (see api/reminder_create.php), never on a recurring schedule.
 */
final class ReminderRepository
{
    private const EDITABLE = ['title', 'note', 'day', 'email', 'send_email'];

    private PDO $pdo;
    private array $map;
    private string $table;

    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->map = Config::schema()['reminders'];
        $this->table = Database::quoteIdent($this->map['table']);
    }

    private function col(string $key): string
    {
        return Database::quoteIdent($this->map[$key]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByRange(DateTimeInterface $from, DateTimeInterface $to): array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s >= :from AND %s < :to ORDER BY %s ASC',
            $this->table,
            $this->col('day'),
            $this->col('day'),
            $this->col('day')
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'from' => ReservationRepository::toDayInt($from),
            'to' => ReservationRepository::toDayInt($to),
        ]);
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
     * @param array<string, mixed> $fields Logical field name => value ('day' may be a DateTimeInterface or an already-converted int).
     */
    public function create(array $fields, ?string $createdBy = null): int
    {
        [$cols, $params] = $this->buildColumns($fields);
        $cols[$this->col('created_by')] = ':p_created_by';
        $params['p_created_by'] = $createdBy;

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
     * @param array<string, mixed> $fields Logical field name => new value.
     */
    public function update($id, array $fields): bool
    {
        [$cols, $params] = $this->buildColumns($fields);
        if (empty($cols)) {
            return false;
        }
        $sets = [];
        foreach ($cols as $column => $placeholder) {
            $sets[] = "$column = $placeholder";
        }
        $params['id'] = $id;
        $sql = sprintf('UPDATE %s SET %s WHERE %s = :id', $this->table, implode(', ', $sets), $this->col('pk'));
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function markSent($id): void
    {
        $sql = sprintf('UPDATE %s SET %s = :now WHERE %s = :id', $this->table, $this->col('sent_at'), $this->col('pk'));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['now' => (new DateTime())->format('Y-m-d H:i:s'), 'id' => $id]);
    }

    public function delete($id): bool
    {
        $sql = sprintf('DELETE FROM %s WHERE %s = :id', $this->table, $this->col('pk'));
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
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
        $out['day'] = ReservationRepository::fromDayInt($out['day'] ?? null);
        $out['send_email'] = !empty($out['send_email']);
        return $out;
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, mixed>}
     */
    private function buildColumns(array $fields): array
    {
        $cols = [];
        $params = [];
        foreach ($fields as $logicalName => $value) {
            if (!in_array($logicalName, self::EDITABLE, true) || !isset($this->map[$logicalName])) {
                continue;
            }
            if ($logicalName === 'day' && $value instanceof DateTimeInterface) {
                $value = ReservationRepository::toDayInt($value);
            }
            if ($logicalName === 'send_email') {
                $value = $value ? 1 : 0;
            }
            $paramName = 'p_' . $logicalName;
            $cols[$this->col($logicalName)] = ':' . $paramName;
            $params[$paramName] = $value;
        }
        return [$cols, $params];
    }
}
