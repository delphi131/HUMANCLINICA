<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo non consentito.', 405);
}

require_csrf();

$id = $_POST['id'] ?? null;
if (!$id) {
    json_error('ID mancante.');
}

$repo = new ReservationRepository();
if (!$repo->find($id)) {
    json_error('Prenotazione non trovata.', 404);
}

$repo->delete($id);

json_ok();
