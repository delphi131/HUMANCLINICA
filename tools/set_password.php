<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Questo script va eseguito da riga di comando: php tools/set_password.php <username> <password>');
}

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/UserRepository.php';

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$username || !$password) {
    fwrite(STDERR, "Uso: php tools/set_password.php <username> <nuova-password>\n");
    exit(1);
}

if (strlen($password) < 8) {
    fwrite(STDERR, "La password deve avere almeno 8 caratteri.\n");
    exit(1);
}

try {
    $users = new UserRepository();
    $user = $users->findByUsername($username);

    if ($user === null) {
        fwrite(STDERR, "Utente '{$username}' non trovato in tUsers.\n");
        exit(1);
    }

    $users->setPasswordHash($username, $password);
    echo "Password del pannello PHP impostata per '{$username}'.\n";
    echo "(La password dell'app ASP.NET esistente non è stata modificata.)\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Errore: ' . $e->getMessage() . "\n");
    exit(1);
}
