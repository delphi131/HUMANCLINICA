<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Questo script va eseguito da riga di comando: php tools/list_columns.php <NomeTabella>');
}

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Database.php';

$table = $argv[1] ?? null;
if (!$table) {
    fwrite(STDERR, "Uso: php tools/list_columns.php <NomeTabella>\n");
    exit(1);
}

try {
    $pdo = Database::pdo();
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :table ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute(['table' => $table]);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        echo "Nessuna colonna trovata per la tabella '{$table}' (controlla il nome esatto, maiuscole/minuscole comprese).\n";
        exit(1);
    }

    foreach ($rows as $row) {
        printf("%-30s %s\n", $row['COLUMN_NAME'], $row['DATA_TYPE']);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Errore: ' . $e->getMessage() . "\n");
    exit(1);
}
