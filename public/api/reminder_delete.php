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

$reminders = new ReminderRepository();
if (!$reminders->find($id)) {
    json_error('Promemoria non trovato.', 404);
}

$reminders->delete($id);

json_ok();
