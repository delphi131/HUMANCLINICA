<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

$messages = new MessageRepository();
$notice = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_template') {
            $name = trim((string)($_POST['name'] ?? ''));
            $value = (string)($_POST['value'] ?? '');
            if ($name === '') {
                throw new RuntimeException('Nome template mancante.');
            }
            $messages->saveTemplate($name, $value);
            $notice = 'Template "' . $name . '" salvato.';
        } elseif ($action === 'save_smtp') {
            $messages->saveSmtpConfig([
                'host' => trim((string)($_POST['smtp_host'] ?? '')),
                'port' => trim((string)($_POST['smtp_port'] ?? '')),
                'encryption' => trim((string)($_POST['smtp_encryption'] ?? 'tls')),
                'username' => trim((string)($_POST['smtp_username'] ?? '')),
                'password' => (string)($_POST['smtp_password'] ?? ''),
                'from_email' => trim((string)($_POST['smtp_from_email'] ?? '')),
                'from_name' => trim((string)($_POST['smtp_from_name'] ?? '')),
            ]);
            $notice = 'Impostazioni SMTP salvate.';
        } elseif ($action === 'new_template') {
            $name = trim((string)($_POST['new_name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Assegna un nome al nuovo template.');
            }
            $messages->saveTemplate($name, '<p>Ciao @NOME,</p><p>...</p>');
            $notice = 'Template "' . $name . '" creato.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$templates = $messages->listTemplates();
$smtp = $messages->getSmtpConfig();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Messaggi — <?= htmlspecialchars(Config::get('app.name', 'HumanClinica Admin')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card">
        <h2>Modelli di messaggio</h2>
        <p class="hint">
            Modelli prenotazioni (CONFERMA_PRENOTAZIONE, PRESA_IN_CARICO): usa @NOME, @COGNOME, @DATA, @ORA, @TELEFONO, @EMAIL.<br>
            Modello contratto (CONTRATTO_COLLABORAZIONE, dalla pagina Aziende): usa @NOME_AZIENDA, @PIVA, @SEDE, @CITTA, @CAP, @RAPPRESENTANTE, @TELEFONO, @DATA.<br>
            <strong>Attenzione:</strong> qui sotto compaiono anche i modelli già usati dal sito ASP.NET esistente
            (es. CONFERMA_PRENOTAZIONE, ACCOUNT, WHATSAPP...) — modificarli qui cambia anche quello che invia il sito
            pubblico. I placeholder @NOME ecc. funzionano solo per i messaggi inviati da questo pannello: se il sito
            ASP.NET usa una sintassi diversa per i suoi segnaposto, non toccarla per errore.
        </p>

        <?php foreach ($templates as $t): ?>
        <div class="template-block">
            <h3><?= htmlspecialchars($t['name']) ?> <small style="color:#999">(<?= htmlspecialchars($t['lang']) ?>)</small></h3>
            <form method="post">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="save_template">
                <input type="hidden" name="name" value="<?= htmlspecialchars($t['name']) ?>">
                <textarea name="value" data-html-editor><?= htmlspecialchars($t['value']) ?></textarea>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>

        <?php if (empty($templates)): ?>
            <p class="hint">Nessun template presente. Crea "CONFERMA_PRENOTAZIONE" e "PRESA_IN_CARICO" per usarli dal calendario.</p>
        <?php endif; ?>

        <form method="post" style="display:flex; gap:8px; align-items:flex-end; margin-top:10px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="new_template">
            <label style="flex:1">Nuovo template
                <input type="text" name="new_name" placeholder="es. PRESA_IN_CARICO">
            </label>
            <button type="submit" class="btn btn-outline">Crea</button>
        </form>
    </div>

    <div class="card">
        <h2>Impostazioni SMTP</h2>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="save_smtp">
            <div class="smtp-grid">
                <label>Host
                    <input type="text" name="smtp_host" value="<?= htmlspecialchars($smtp['host']) ?>" placeholder="smtp-relay.brevo.com">
                </label>
                <label>Porta
                    <input type="text" name="smtp_port" value="<?= htmlspecialchars((string)$smtp['port']) ?>" placeholder="587">
                </label>
                <label>Cifratura
                    <select name="smtp_encryption">
                        <?php foreach (['tls' => 'STARTTLS', 'ssl' => 'SSL', 'none' => 'Nessuna'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $smtp['encryption'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Utente
                    <input type="text" name="smtp_username" value="<?= htmlspecialchars($smtp['username']) ?>">
                </label>
                <label>Password
                    <input type="password" name="smtp_password" value="<?= htmlspecialchars($smtp['password']) ?>">
                </label>
                <label>Email mittente
                    <input type="email" name="smtp_from_email" value="<?= htmlspecialchars($smtp['from_email']) ?>">
                </label>
                <label>Nome mittente
                    <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($smtp['from_name']) ?>">
                </label>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn btn-primary">Salva impostazioni SMTP</button>
            </div>
        </form>
    </div>
</div>
<script src="assets/js/htmleditor.js"></script>
</body>
</html>
