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
            <label>Data e ora
                <input type="datetime-local" name="day" id="edit-day">
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

<script src="assets/js/app.js"></script>
</body>
</html>
