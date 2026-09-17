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

    public function isAdmin(array $user): bool
    {
        $value = $user[$this->map['type_profile']] ?? null;
        return is_string($value)
            && strcasecmp(trim($value), $this->map['type_profile_admin_value']) === 0;
    }

    public function displayName(array $user): string
    {
        $nome = trim((string)($user[$this->map['nome']] ?? ''));
        $cognome = trim((string)($user[$this->map['cognome']] ?? ''));
        $full = trim($nome . ' ' . $cognome);
        return $full !== '' ? $full : (string)($user[$this->map['username']] ?? '');
    }
}
