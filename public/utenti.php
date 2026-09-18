<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Auth::requireAdmin();

$users = new UserRepository();
$rows = $users->listAll();
$schema = Config::schema()['users'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Utenti — <?= htmlspecialchars(Config::get('app.name', 'HumanClinica Admin')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="card">
        <h2>Utenti</h2>
        <p class="hint">Include sia gli amministratori sia i Beauty Advisor (distinti dal campo "Ruolo").</p>
        <div class="modal-actions" style="justify-content:flex-start; margin-bottom:14px;">
            <a href="utente_form.php" class="btn btn-primary">+ Nuovo utente</a>
        </div>
        <div style="overflow-x:auto">
        <table class="reservations">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefono</th>
                    <th>Azienda</th>
                    <th>Ruolo</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): $u = $users->toLogical($row); ?>
                <tr>
                    <td><?= htmlspecialchars((string)$u['username']) ?></td>
                    <td><?= htmlspecialchars($users->displayName($u)) ?></td>
                    <td><?= htmlspecialchars((string)($u['email'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($u['telephone'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($u['id_azienda'] ?? '')) ?></td>
                    <td><span class="status-badge"><?= $users->isAdmin($u) ? 'Admin' : 'Beauty Advisor' ?></span></td>
                    <td class="actions-cell">
                        <a class="btn btn-sm btn-outline" href="utente_form.php?id=<?= urlencode((string)$u['pk']) ?>">Modifica</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="7">Nessun utente trovato.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
