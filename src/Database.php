<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $cfg = Config::get('db');
            $driver = $cfg['driver'] ?? 'sqlsrv';

            if ($driver === 'dblib') {
                // Linux hosting via FreeTDS.
                $dsn = sprintf(
                    'dblib:host=%s:%d;dbname=%s;charset=UTF-8',
                    $cfg['host'],
                    $cfg['port'] ?? 1433,
                    $cfg['database']
                );
            } elseif ($driver === 'odbc') {
                // Fallback for a PHP version newer than the latest Microsoft
                // sqlsrv/pdo_sqlsrv driver release: goes through the
                // system-wide "ODBC Driver 17/18 for SQL Server" instead,
                // which is independent of the PHP build. Requires PDO_ODBC
                // (usually already compiled into official Windows PHP
                // builds — enable with extension=pdo_odbc in php.ini) and
                // that ODBC driver installed separately on the server.
                // ODBC Driver 18 defaults to Encrypt=yes and will refuse to
                // connect without a trusted certificate on the SQL Server;
                // set odbc_extra (e.g. 'TrustServerCertificate=yes') in
                // config.php if you hit an SSL/certificate error.
                $odbcDriverName = $cfg['odbc_driver_name'] ?? 'ODBC Driver 17 for SQL Server';
                $odbcExtra = trim((string)($cfg['odbc_extra'] ?? ''));
                $dsn = sprintf(
                    'odbc:Driver={%s};Server=%s,%d;Database=%s%s',
                    $odbcDriverName,
                    $cfg['host'],
                    $cfg['port'] ?? 1433,
                    $cfg['database'],
                    $odbcExtra !== '' ? ';' . $odbcExtra : ''
                );
            } else {
                // Microsoft Drivers for PHP for SQL Server (typical on Windows/IIS).
                $dsn = sprintf(
                    'sqlsrv:Server=%s,%d;Database=%s',
                    $cfg['host'],
                    $cfg['port'] ?? 1433,
                    $cfg['database']
                );
            }

            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        return self::$pdo;
    }

    /**
     * Wrap an identifier (table or column name) in SQL Server brackets,
     * required for names like "link-meet" that contain a hyphen.
     */
    public static function quoteIdent(string $identifier): string
    {
        return '[' . str_replace(']', ']]', $identifier) . ']';
    }

    /**
     * Id of the row just INSERTed, right after execute() on the same
     * connection. PDO::lastInsertId() throws "SQLSTATE[IM001]: Driver does
     * not support this function" on PDO_ODBC (db.driver = 'odbc') — the
     * Microsoft ODBC Driver for SQL Server just doesn't implement it. This
     * queries SCOPE_IDENTITY() instead, which is plain T-SQL and works the
     * same way regardless of which PDO driver is in use.
     */
    public static function lastInsertId(PDO $pdo): int
    {
        $stmt = $pdo->query('SELECT CAST(SCOPE_IDENTITY() AS BIGINT) AS id');
        $row = $stmt->fetch();
        return (int)($row['id'] ?? 0);
    }
}
