<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Calendario prenotazioni — <?= htmlspecialchars(Config::get('app.name', 'HumanClinica Admin')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="card">
        <div class="calendar-actions">
            <button type="button" id="btn-new-reservation" class="btn btn-primary">+ Prenotazione manuale</button>
            <button type="button" id="btn-new-reminder" class="btn btn-outline">+ Promemoria</button>
        </div>
        <div id="calendar"></div>
    </div>

    <div class="card">
        <h2>Prenotazioni</h2>
        <form id="filters-form" class="filters">
            <label>Dal
                <input type="date" name="from" id="filter-from" value="<?= htmlspecialchars($today) ?>">
            </label>
            <label>Al
                <input type="date" name="to" id="filter-to" value="<?= htmlspecialchars($today) ?>">
            </label>
            <label>Stato
                <select name="status" id="filter-status">
                    <option value="">Tutti</option>
                    <option value="NUOVO">Nuovo</option>
                    <option value="CONTATTATO">Contattato</option>
                    <option value="CONFERMATO">Confermato</option>
                    <option value="ANNULLATO">Annullato</option>
                </select>
            </label>
            <label>Cerca
                <input type="text" name="search" id="filter-search" placeholder="nome, email, telefono">
            </label>
            <button type="submit" class="btn btn-primary">Filtra</button>
            <button type="button" id="btn-today" class="btn btn-outline">Oggi</button>
        </form>

        <div style="overflow-x:auto">
        <table class="reservations">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Contatti</th>
                    <th>Data</th>
                    <th>Stato</th>
                    <th>Beauty Advisor</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody id="reservations-body">
                <tr><td colspan="6">Caricamento…</td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal: modifica prenotazione -->
<div class="modal-backdrop" id="modal-edit">
    <div class="modal">
        <h3>Modifica prenotazione</h3>
        <form id="form-edit">
            <input type="hidden" name="id" id="edit-id">
            <label>Nome
                <input type="text" name="nome" id="edit-nome">
            </label>
            <label>Cognome
                <input type="text" name="cognome" id="edit-cognome">
            </label>
            <label>Telefono
                <input type="text" name="telefono" id="edit-telefono">
            </label>
            <label>Email
                <input type="email" name="email" id="edit-email">
            </label>
            <label>Data
                <input type="date" name="day" id="edit-day">
            </label>
            <label>Stato
                <select name="status" id="edit-status">
                    <option value="NUOVO">Nuovo</option>
                    <option value="CONTATTATO">Contattato</option>
                    <option value="CONFERMATO">Confermato</option>
                    <option value="ANNULLATO">Annullato</option>
                </select>
            </label>
            <label>Beauty Advisor
                <input type="text" name="beauty_advisor" id="edit-beauty-advisor">
            </label>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal>Annulla</button>
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: messaggio personalizzato -->
<div class="modal-backdrop" id="modal-custom">
    <div class="modal">
        <h3>Invia messaggio personalizzato</h3>
        <form id="form-custom">
            <input type="hidden" name="id" id="custom-id">
            <label>Oggetto
                <input type="text" name="subject" id="custom-subject" value="Human Clinica">
            </label>
            <label>Messaggio
                <textarea name="message" id="custom-message" rows="6"></textarea>
            </label>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal>Annulla</button>
                <button type="submit" class="btn btn-primary">Invia email</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: nuova prenotazione manuale -->
<div class="modal-backdrop" id="modal-new-reservation">
    <div class="modal">
        <h3>Nuova prenotazione manuale</h3>
        <p class="hint">Creata direttamente qui, come una prenotazione normale.</p>
        <form id="form-new-reservation">
            <label>Nome
                <input type="text" name="nome" id="new-res-nome">
            </label>
            <label>Cognome
                <input type="text" name="cognome" id="new-res-cognome">
            </label>
            <label>Telefono
                <input type="text" name="telefono" id="new-res-telefono">
            </label>
            <label>Email
                <input type="email" name="email" id="new-res-email">
            </label>
            <label>Data
                <input type="date" name="day" id="new-res-day" required>
            </label>
            <label>Stato
                <select name="status" id="new-res-status">
                    <option value="NUOVO">Nuovo</option>
                    <option value="CONTATTATO">Contattato</option>
                    <option value="CONFERMATO" selected>Confermato</option>
                    <option value="ANNULLATO">Annullato</option>
                </select>
            </label>
            <label>Beauty Advisor
                <input type="text" name="beauty_advisor" id="new-res-beauty-advisor">
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="send_email" id="new-res-send-email">
                Invia email di conferma prenotazione
            </label>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" data-close-modal>Annulla</button>
                <button type="submit" class="btn btn-primary">Crea prenotazione</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: promemoria (crea/modifica) -->
<div class="modal-backdrop" id="modal-reminder">
    <div class="modal">
        <h3 id="reminder-modal-title">Nuovo promemoria</h3>
        <form id="form-reminder">
            <input type="hidden" name="id" id="reminder-id">
            <label>Titolo
                <input type="text" name="title" id="reminder-title" required>
            </label>
            <label>Nota
                <textarea name="note" id="reminder-note" rows="4"></textarea>
            </label>
            <label>Data
                <input type="date" name="day" id="reminder-day" required>
            </label>
            <label>Email (opzionale)
                <input type="email" name="email" id="reminder-email">
            </label>
            <label class="checkbox-row">
                <input type="checkbox" name="send_email" id="reminder-send-email">
                Invia email di promemoria
            </label>
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" id="btn-delete-reminder" style="display:none">Elimina</button>
                <button type="button" class="btn btn-secondary" data-close-modal>Annulla</button>
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
