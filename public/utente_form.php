<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Auth::requireAdmin();

$schema = Config::schema()['users'];
$users = new UserRepository();
$aziende = (new AziendaRepository())->listForDropdown();

$id = $_GET['id'] ?? $_POST['id'] ?? null;
$existing = $id ? $users->findById($id) : null;
$isEdit = $existing !== null;

$error = null;
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete' && $isEdit) {
            $users->delete($id);
            header('Location: utenti.php');
            exit;
        }

        $fields = [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'cognome' => trim((string)($_POST['cognome'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'telephone' => trim((string)($_POST['telephone'] ?? '')),
            'id_azienda' => $_POST['id_azienda'] !== '' ? (int)$_POST['id_azienda'] : null,
            'type_profile' => (int)($_POST['type_profile'] ?? 0),
        ];

        if ($isEdit) {
            $users->update($id, $fields);
            $password = trim((string)($_POST['password'] ?? ''));
            if ($password !== '') {
                if (strlen($password) < 8) {
                    throw new RuntimeException('La password deve avere almeno 8 caratteri.');
                }
                $users->setPasswordHash((string)$existing[$schema['username']], $password);
            }
            $notice = 'Utente aggiornato.';
            $existing = $users->findById($id);
        } else {
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            if ($username === '') {
                throw new RuntimeException('Username obbligatorio.');
            }
            if (strlen($password) < 8) {
                throw new RuntimeException('La password deve avere almeno 8 caratteri.');
            }
            if ($users->findByUsername($username) !== null) {
                throw new RuntimeException('Esiste già un utente con questo username.');
            }
            $fields['username'] = $username;
            $users->create($fields, $password);
            header('Location: utenti.php');
            exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$u = $existing ? $users->toLogical($existing) : [];
$adminValue = (int)$schema['type_profile_admin_value'];
$baValue = (int)$schema['type_profile_beauty_advisor_value'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $isEdit ? 'Modifica utente' : 'Nuovo utente' ?> — <?= htmlspecialchars(Config::get('app.name', 'HumanClinica Admin')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="card" style="max-width:560px">
        <h2><?= $isEdit ? 'Modifica utente' : 'Nuovo utente' ?></h2>

        <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post">
            <?= Csrf::field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>"><?php endif; ?>

            <label>Username
                <input type="text" name="username" value="<?= htmlspecialchars((string)($u['username'] ?? '')) ?>" <?= $isEdit ? 'readonly' : 'required' ?>>
            </label>
            <label><?= $isEdit ? 'Nuova password (lascia vuoto per non cambiarla)' : 'Password' ?>
                <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="8">
            </label>
            <label>Nome
                <input type="text" name="nome" value="<?= htmlspecialchars((string)($u['nome'] ?? '')) ?>">
            </label>
            <label>Cognome
                <input type="text" name="cognome" value="<?= htmlspecialchars((string)($u['cognome'] ?? '')) ?>">
            </label>
            <label>Email
                <input type="email" name="email" value="<?= htmlspecialchars((string)($u['email'] ?? '')) ?>">
            </label>
            <label>Telefono
                <input type="text" name="telephone" value="<?= htmlspecialchars((string)($u['telephone'] ?? '')) ?>">
            </label>
            <label>Azienda
                <select name="id_azienda">
                    <option value="">— Nessuna —</option>
                    <?php foreach ($aziende as $az): ?>
                        <option value="<?= htmlspecialchars((string)$az['pk']) ?>" <?= (string)($u['id_azienda'] ?? '') === (string)$az['pk'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$az['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Ruolo
                <select name="type_profile">
                    <option value="<?= $adminValue ?>" <?= (int)($u['type_profile'] ?? 0) === $adminValue ? 'selected' : '' ?>>Amministratore</option>
                    <option value="<?= $baValue ?>" <?= (int)($u['type_profile'] ?? $baValue) !== $adminValue ? 'selected' : '' ?>>Beauty Advisor</option>
                </select>
            </label>

            <div class="modal-actions">
                <a href="utenti.php" class="btn btn-secondary">Annulla</a>
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salva' : 'Crea utente' ?></button>
            </div>
        </form>

        <?php if ($isEdit): ?>
        <form method="post" onsubmit="return confirm('Eliminare definitivamente questo utente?');" style="margin-top:16px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger">Elimina utente</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
