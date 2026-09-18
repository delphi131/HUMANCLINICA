<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Auth::requireAdmin();

$aziende = new AziendaRepository();

$id = $_GET['id'] ?? $_POST['id'] ?? null;
$existing = $id ? $aziende->find($id) : null;
$isEdit = $existing !== null;

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete' && $isEdit) {
            $aziende->delete($id);
            header('Location: aziende.php');
            exit;
        }

        $fields = [
            'nome' => trim((string)($_POST['nome'] ?? '')),
            'piva' => trim((string)($_POST['piva'] ?? '')),
            'sede' => trim((string)($_POST['sede'] ?? '')),
            'citta' => trim((string)($_POST['citta'] ?? '')),
            'cap' => trim((string)($_POST['cap'] ?? '')),
            'rappresentante' => trim((string)($_POST['rappresentante'] ?? '')),
            'telephone' => trim((string)($_POST['telephone'] ?? '')),
            'enabled' => isset($_POST['enabled']) ? 1 : 0,
        ];

        if ($fields['nome'] === '') {
            throw new RuntimeException('Il nome azienda è obbligatorio.');
        }

        if ($isEdit) {
            $aziende->update($id, $fields);
            header('Location: aziende.php');
            exit;
        }

        $newId = $aziende->create($fields);
        header('Location: aziende.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$a = $existing ? $aziende->toLogical($existing) : [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $isEdit ? 'Modifica azienda' : 'Nuova azienda' ?> — <?= htmlspecialchars(Config::get('app.name', 'HumanClinica Admin')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="card" style="max-width:560px">
        <h2><?= $isEdit ? 'Modifica azienda' : 'Nuova azienda' ?></h2>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="post">
            <?= Csrf::field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>"><?php endif; ?>

            <label>Nome
                <input type="text" name="nome" value="<?= htmlspecialchars((string)($a['nome'] ?? '')) ?>" required>
            </label>
            <label>P.IVA
                <input type="text" name="piva" value="<?= htmlspecialchars((string)($a['piva'] ?? '')) ?>">
            </label>
            <label>Sede (indirizzo)
                <input type="text" name="sede" value="<?= htmlspecialchars((string)($a['sede'] ?? '')) ?>">
            </label>
            <label>Città
                <input type="text" name="citta" value="<?= htmlspecialchars((string)($a['citta'] ?? '')) ?>">
            </label>
            <label>CAP
                <input type="text" name="cap" value="<?= htmlspecialchars((string)($a['cap'] ?? '')) ?>">
            </label>
            <label>Rappresentante
                <input type="text" name="rappresentante" value="<?= htmlspecialchars((string)($a['rappresentante'] ?? '')) ?>">
            </label>
            <label>Telefono
                <input type="text" name="telephone" value="<?= htmlspecialchars((string)($a['telephone'] ?? '')) ?>">
            </label>
            <label style="flex-direction:row; align-items:center; gap:8px;">
                <input type="checkbox" name="enabled" value="1" <?= !empty($a['enabled']) ? 'checked' : '' ?> style="width:auto">
                Azienda attiva
            </label>

            <div class="modal-actions">
                <a href="aziende.php" class="btn btn-secondary">Annulla</a>
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Salva' : 'Crea azienda' ?></button>
            </div>
        </form>

        <?php if ($isEdit): ?>
        <form method="post" onsubmit="return confirm('Eliminare definitivamente questa azienda?');" style="margin-top:16px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger">Elimina azienda</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
