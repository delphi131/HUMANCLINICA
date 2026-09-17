<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Questo script va eseguito da riga di comando: php tools/check_schema.php');
}

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Database.php';

/**
 * Verifies that every table/column referenced in config/schema.php actually
 * exists in the live database, since the mapping was reconstructed by
 * scanning the raw bytes of a .bak backup (no live DB was reachable while
 * building this app). Run this after filling in config/config.php.
 */

$schema = Config::schema();
$pdo = Database::pdo();

$stmt = $pdo->query('SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS');
$actual = [];
foreach ($stmt->fetchAll() as $row) {
    $actual[strtolower($row['TABLE_NAME'])][] = $row['COLUMN_NAME'];
}

$problems = 0;

foreach (['users', 'reservations', 'messages'] as $group) {
    $map = $schema[$group];
    $table = $map['table'];
    $tableKey = strtolower($table);

    echo "== {$group} ({$table}) ==\n";

    if (!isset($actual[$tableKey])) {
        echo "  [ERRORE] Tabella '{$table}' non trovata nel database.\n";
        $problems++;
        continue;
    }

    $columnsInDb = $actual[$tableKey];

    foreach ($map as $logicalName => $columnName) {
        if ($logicalName === 'table' || str_starts_with($logicalName, 'type_profile_admin_value')) {
            continue;
        }
        $found = in_array($columnName, $columnsInDb, true);
        printf("  %-20s -> %-20s %s\n", $logicalName, $columnName, $found ? 'OK' : '[MANCANTE]');
        if (!$found) {
            $problems++;
        }
    }
    echo "\n";
}

if ($problems === 0) {
    echo "Tutto ok: tutte le tabelle/colonne mappate esistono.\n";
} else {
    echo "{$problems} problema/i trovati. Correggi config/schema.php di conseguenza.\n";
    exit(1);
}
