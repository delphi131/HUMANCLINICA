<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Non autenticato.']);
    exit;
}

function json_ok(array $data = []): void
{
    echo json_encode(['ok' => true] + $data);
    exit;
}

function json_error(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!Csrf::check($token)) {
        json_error('Token CSRF non valido, ricarica la pagina.', 403);
    }
}
