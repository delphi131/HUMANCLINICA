<?php

declare(strict_types=1);

final class MessageRepository
{
    private const DEFAULT_LANG = 'IT';
    // TYPE is varchar(2) on the live DB. 'M' ("Messaggio") is the value the
    // existing ASP.NET app already uses for every real row (confirmed live:
    // ACCOUNT, CONFERMA_PRENOTAZIONE, WHATSAPP, ...) — reusing it means our
    // saveTemplate() updates the SAME row the public site reads from,
    // instead of creating a duplicate under a type value nothing else reads.
    private const TYPE_TEMPLATE = 'M';
    // No existing rows use a config-only type; 'CF' is our own 2-char code.
    private const TYPE_CONFIG = 'CF';

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
        // VALUE is varchar(MAX): with the ODBC driver (db.driver = 'odbc'),
        // PDO_ODBC can raise "String data, right truncated" on long content
        // because SQL Server reports MAX columns with an unknown length. An
        // explicit large output buffer works around it regardless of driver
        // (harmless no-op on sqlsrv/dblib, and still requires odbc.defaultlrl
        // in php.ini to be large enough — see README).
        $stmt->bindColumn('v', $value, PDO::PARAM_STR, 8 * 1024 * 1024);
        $row = $stmt->fetch(PDO::FETCH_BOUND);
        return $row ? (string)$value : null;
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
        // Bind+loop instead of fetchAll(): PDO::FETCH_BOUND only rebinds on
        // each fetch(), not on fetchAll(), and the large explicit buffer is
        // what avoids pdo_odbc's "String data, right truncated" on the
        // varchar(MAX) value column (see getValue() above).
        $stmt->bindColumn('name', $name);
        $stmt->bindColumn('lang', $lang);
        $stmt->bindColumn('value', $value, PDO::PARAM_STR, 8 * 1024 * 1024);
        $rows = [];
        while ($stmt->fetch(PDO::FETCH_BOUND)) {
            $rows[] = ['name' => $name, 'lang' => $lang, 'value' => $value];
        }
        return $rows;
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
