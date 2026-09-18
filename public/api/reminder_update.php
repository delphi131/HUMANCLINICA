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

$fields = [];
foreach (['title', 'note', 'email'] as $key) {
    if (isset($_POST[$key])) {
        $fields[$key] = trim((string)$_POST[$key]);
    }
}
if (isset($_POST['send_email'])) {
    $fields['send_email'] = !empty($_POST['send_email']);
}
if (!empty($_POST['day'])) {
    try {
        $fields['day'] = new DateTime((string)$_POST['day']);
    } catch (Exception $e) {
        json_error('Data non valida.');
    }
}

$reminders->update($id, $fields);

json_ok();
