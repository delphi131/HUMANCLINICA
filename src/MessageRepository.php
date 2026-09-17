<?php

declare(strict_types=1);

final class MessageRepository
{
    private const DEFAULT_LANG = 'IT';
    private const TYPE_TEMPLATE = 'HTML';
    private const TYPE_CONFIG = 'CONFIG';

    private PDO $pdo;
    private array $map;
    private string $table;

    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->map = Config::schema()['messages'];
        $this->table = Database::quoteIdent($this->map['table']);
    }

    private function col(string $key): string
    {
        return Database::quoteIdent($this->map[$key]);
    }

    private function getValue(string $name, string $type, string $lang = self::DEFAULT_LANG): ?string
    {
        $sql = sprintf(
            'SELECT %s AS v FROM %s WHERE %s = :name AND %s = :type AND %s = :lang',
            $this->col('value'),
            $this->table,
            $this->col('name'),
            $this->col('type'),
            $this->col('lang')
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name, 'type' => $type, 'lang' => $lang]);
        $row = $stmt->fetch();
        return $row ? (string)$row['v'] : null;
    }

    private function setValue(string $name, string $type, string $value, string $lang = self::DEFAULT_LANG): void
    {
        $update = sprintf(
            'UPDATE %s SET %s = :value WHERE %s = :name AND %s = :type AND %s = :lang',
            $this->table,
            $this->col('value'),
            $this->col('name'),
            $this->col('type'),
            $this->col('lang')
        );
        $stmt = $this->pdo->prepare($update);
        $stmt->execute(['value' => $value, 'name' => $name, 'type' => $type, 'lang' => $lang]);

        if ($stmt->rowCount() > 0) {
            return;
        }

        $insert = sprintf(
            'INSERT INTO %s (%s, %s, %s, %s) VALUES (:name, :type, :lang, :value)',
            $this->table,
            $this->col('name'),
            $this->col('type'),
            $this->col('lang'),
            $this->col('value')
        );
        $stmt = $this->pdo->prepare($insert);
        $stmt->execute(['name' => $name, 'type' => $type, 'lang' => $lang, 'value' => $value]);
    }

    public function getTemplate(string $name, string $lang = self::DEFAULT_LANG): ?string
    {
        return $this->getValue($name, self::TYPE_TEMPLATE, $lang);
    }

    public function saveTemplate(string $name, string $value, string $lang = self::DEFAULT_LANG): void
    {
        $this->setValue($name, self::TYPE_TEMPLATE, $value, $lang);
    }

    /**
     * @return array<int, array{name: string, lang: string, value: string}>
     */
    public function listTemplates(): array
    {
        $sql = sprintf(
            'SELECT %s AS name, %s AS lang, %s AS value FROM %s WHERE %s = :type ORDER BY %s ASC',
            $this->col('name'),
            $this->col('lang'),
            $this->col('value'),
            $this->table,
            $this->col('type'),
            $this->col('name')
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['type' => self::TYPE_TEMPLATE]);
        return $stmt->fetchAll();
    }

    /**
     * Substitute @PLACEHOLDER tokens in a template with values from a reservation row.
     * @param array<string, string> $placeholders e.g. ['NOME' => 'Mario', 'DATA' => '12/05/2026']
     */
    public function render(string $template, array $placeholders): string
    {
        $search = [];
        $replace = [];
        foreach ($placeholders as $key => $value) {
            $search[] = '@' . strtoupper($key);
            $replace[] = htmlspecialchars((string)$value, ENT_QUOTES);
        }
        return str_replace($search, $replace, $template);
    }

    public function getSmtpConfig(): array
    {
        $names = Config::schema()['smtp_config_names'];
        $fallback = Config::get('smtp_fallback', []);
        $result = [];

        foreach ($names as $key => $rowName) {
            $value = $this->getValue($rowName, self::TYPE_CONFIG);
            $result[$key] = $value !== null && $value !== '' ? $value : ($fallback[$key] ?? '');
        }

        return $result;
    }

    /**
     * @param array<string, string> $settings Keys matching smtp_config_names in schema.php.
     */
    public function saveSmtpConfig(array $settings): void
    {
        $names = Config::schema()['smtp_config_names'];
        foreach ($names as $key => $rowName) {
            if (array_key_exists($key, $settings)) {
                $this->setValue($rowName, self::TYPE_CONFIG, (string)$settings[$key]);
            }
        }
    }
}
