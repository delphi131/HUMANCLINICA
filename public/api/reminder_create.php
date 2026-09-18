<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo non consentito.', 405);
}

require_csrf();

$title = trim((string)($_POST['title'] ?? ''));
if ($title === '') {
    json_error('Titolo obbligatorio.');
}
if (empty($_POST['day'])) {
    json_error('Data obbligatoria.');
}
try {
    $day = new DateTime((string)$_POST['day']);
} catch (Exception $e) {
    json_error('Data non valida.');
}

$note = trim((string)($_POST['note'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$sendEmail = !empty($_POST['send_email']);

$reminders = new ReminderRepository();
$id = $reminders->create([
    'title' => $title,
    'note' => $note,
    'day' => $day,
    'email' => $email,
    'send_email' => $sendEmail,
], Auth::user()['username'] ?? null);

// The email is entirely optional (checkbox in the UI) — most reminders are
// just an internal note on the calendar with nothing to notify anyone about.
$warning = null;
if ($sendEmail) {
    if ($email === '') {
        $warning = 'Promemoria creato, ma email non inviata: indirizzo email mancante.';
    } else {
        $templateNames = Config::schema()['template_names'];
        $messages = new MessageRepository();
        $templateValue = $messages->getTemplate($templateNames['reminder']);
        if ($templateValue === null) {
            $warning = 'Promemoria creato, ma email non inviata: template PROMEMORIA non trovato in tMessages. Creane uno dalla pagina Messaggi.';
        } else {
            $html = $messages->render($templateValue, [
                'TITOLO' => $title,
                'NOTE' => $note,
                'DATA' => $day->format('d/m/Y'),
            ]);
            try {
                $mailer = new SmtpMailer($messages->getSmtpConfig());
                $mailer->send([$email], 'Human Clinica', $html);
                $reminders->markSent($id);
            } catch (Throwable $e) {
                $warning = 'Promemoria creato, ma invio email fallito: ' . $e->getMessage();
            }
        }
    }
}

json_ok(['id' => $id, 'warning' => $warning]);
