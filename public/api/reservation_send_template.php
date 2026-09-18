<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo non consentito.', 405);
}

require_csrf();

$id = $_POST['id'] ?? null;
$templateKey = $_POST['template'] ?? null; // 'taken_in_charge' | 'booking_confirmation'

if (!$id || !$templateKey) {
    json_error('Parametri mancanti.');
}

$templateNames = Config::schema()['template_names'];
if (!isset($templateNames[$templateKey])) {
    json_error('Template sconosciuto.');
}

$reservations = new ReservationRepository();
$row = $reservations->find($id);
if (!$row) {
    json_error('Prenotazione non trovata.', 404);
}
$r = $reservations->toLogical($row);

if (empty($r['email'])) {
    json_error('La prenotazione non ha un indirizzo email.');
}

$messages = new MessageRepository();
$templateValue = $messages->getTemplate($templateNames[$templateKey]);

if ($templateValue === null) {
    json_error('Template "' . $templateNames[$templateKey] . '" non trovato in tMessages. Creane uno dalla pagina Messaggi.');
}

$dayObj = null;
try {
    $dayObj = $r['day'] ? new DateTime((string)$r['day']) : null;
} catch (Exception $e) {
    // ignore malformed date
}

$html = $messages->render($templateValue, [
    'NOME' => $r['nome'] ?? '',
    'COGNOME' => $r['cognome'] ?? '',
    // Reservations only carry a day, not a specific time (the beauty
    // advisor calls back the same day) — @ORA is intentionally not offered.
    'DATA' => $dayObj ? $dayObj->format('d/m/Y') : '',
    'TELEFONO' => $r['telefono'] ?? '',
    'EMAIL' => $r['email'] ?? '',
]);

try {
    $mailer = new SmtpMailer($messages->getSmtpConfig());
    $mailer->send([$r['email']], 'Human Clinica', $html);
} catch (Throwable $e) {
    json_error('Invio email fallito: ' . $e->getMessage(), 502);
}

if ($templateKey === 'taken_in_charge') {
    $currentStatus = strtoupper((string)($r['status'] ?? ''));
    if (!in_array($currentStatus, ['CONFERMATO', 'ANNULLATO'], true)) {
        $reservations->update($id, ['status' => 'CONTATTATO']);
    }
}

json_ok();
