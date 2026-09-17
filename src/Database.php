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
}
