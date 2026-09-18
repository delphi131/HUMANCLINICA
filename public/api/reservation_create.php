<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo non consentito.', 405);
}

require_csrf();

$nome = trim((string)($_POST['nome'] ?? ''));
$cognome = trim((string)($_POST['cognome'] ?? ''));
if ($nome === '' && $cognome === '') {
    json_error('Nome o cognome obbligatorio.');
}
if (empty($_POST['day'])) {
    json_error('Data obbligatoria.');
}
try {
    $day = new DateTime((string)$_POST['day']);
} catch (Exception $e) {
    json_error('Data non valida.');
}

$email = trim((string)($_POST['email'] ?? ''));

$fields = [
    'nome' => $nome,
    'cognome' => $cognome,
    'telefono' => trim((string)($_POST['telefono'] ?? '')),
    'email' => $email,
    'status' => trim((string)($_POST['status'] ?? '')) ?: 'CONFERMATO',
    'beauty_advisor' => trim((string)($_POST['beauty_advisor'] ?? '')),
    'day' => ReservationRepository::toDayInt($day),
    // Columns the manual-reservation form has no input for, but that the
    // public booking flow always fills in — safe placeholder values in case
    // any of them are NOT NULL on the live DB (see ReservationRepository::EDITABLE).
    'settore' => '',
    'sottosettore' => '',
    'price' => 0,
    'pacchetto' => '',
    'package_id' => 0,
    'payment_id' => 0,
    'token' => bin2hex(random_bytes(16)),
    'link_meet' => '',
];

$repo = new ReservationRepository();
try {
    $id = $repo->create($fields);
} catch (Throwable $e) {
    json_error('Creazione prenotazione fallita: ' . $e->getMessage(), 500);
}

// Sending the confirmation email is optional (checkbox in the UI) — a
// manual reservation is often just a note, not something the client needs
// notified about.
$warning = null;
if (!empty($_POST['send_email'])) {
    if ($email === '') {
        $warning = 'Prenotazione creata, ma email non inviata: indirizzo email mancante.';
    } else {
        $templateNames = Config::schema()['template_names'];
        $messages = new MessageRepository();
        $templateValue = $messages->getTemplate($templateNames['booking_confirmation']);
        if ($templateValue === null) {
            $warning = 'Prenotazione creata, ma email non inviata: template CONFERMA_PRENOTAZIONE non trovato in tMessages.';
        } else {
            $html = $messages->render($templateValue, [
                'NOME' => $nome,
                'COGNOME' => $cognome,
                'DATA' => $day->format('d/m/Y'),
                'TELEFONO' => $fields['telefono'],
                'EMAIL' => $email,
            ]);
            try {
                $mailer = new SmtpMailer($messages->getSmtpConfig());
                $mailer->send([$email], 'Human Clinica', $html);
            } catch (Throwable $e) {
                $warning = 'Prenotazione creata, ma invio email fallito: ' . $e->getMessage();
            }
        }
    }
}

json_ok(['id' => $id, 'warning' => $warning]);
