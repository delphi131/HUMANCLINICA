<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo non consentito.', 405);
}

require_csrf();

$id = $_POST['id'] ?? null;
$subject = trim((string)($_POST['subject'] ?? 'Human Clinica'));
$message = trim((string)($_POST['message'] ?? ''));

if (!$id || $message === '') {
    json_error('Messaggio vuoto.');
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

$html = nl2br(htmlspecialchars($message, ENT_QUOTES));

$messages = new MessageRepository();

try {
    $mailer = new SmtpMailer($messages->getSmtpConfig());
    $mailer->send([$r['email']], $subject, $html);
} catch (Throwable $e) {
    json_error('Invio email fallito: ' . $e->getMessage(), 502);
}

json_ok();
