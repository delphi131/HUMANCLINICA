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

$fields = [];
foreach (['nome', 'cognome', 'telefono', 'email', 'status', 'beauty_advisor'] as $key) {
    if (isset($_POST[$key])) {
        $fields[$key] = trim((string)$_POST[$key]);
    }
}

if (!empty($_POST['day'])) {
    try {
        $day = new DateTime((string)$_POST['day']);
        $fields['day'] = ReservationRepository::toDayInt($day);
    } catch (Exception $e) {
        json_error('Data non valida.');
    }
}

$repo = new ReservationRepository();
if (!$repo->find($id)) {
    json_error('Prenotazione non trovata.', 404);
}

$repo->update($id, $fields);

json_ok();
