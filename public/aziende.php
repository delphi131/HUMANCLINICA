<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Auth::requireAdmin();

$aziende = new AziendaRepository();
$rows = $aziende->listAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Aziende — <?= htmlspecialchars(Config::get('app.name', 'HumanClinica Admin')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="card">
        <h2>Aziende</h2>
        <div class="modal-actions" style="justify-content:flex-start; margin-bottom:14px;">
            <a href="azienda_form.php" class="btn btn-primary">+ Nuova azienda</a>
        </div>
        <div style="overflow-x:auto">
        <table class="reservations">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>P.IVA</th>
                    <th>Sede</th>
                    <th>Rappresentante</th>
                    <th>Contatti</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): $a = $aziende->toLogical($row); ?>
                <tr>
                    <td><?= htmlspecialchars((string)$a['nome']) ?></td>
                    <td><?= htmlspecialchars((string)($a['piva'] ?? '')) ?></td>
                    <td><?= htmlspecialchars(trim(($a['sede'] ?? '') . ' ' . ($a['citta'] ?? '') . ' ' . ($a['cap'] ?? ''))) ?></td>
                    <td><?= htmlspecialchars((string)($a['rappresentante'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($a['telephone'] ?? '')) ?><br><small><?= htmlspecialchars((string)($a['email'] ?? '')) ?></small></td>
                    <td><span class="status-badge"><?= !empty($a['enabled']) ? 'Attiva' : 'Non attiva' ?></span></td>
                    <td class="actions-cell">
                        <a class="btn btn-sm btn-outline" href="azienda_form.php?id=<?= urlencode((string)$a['pk']) ?>">Modifica</a>
                        <a class="btn btn-sm btn-outline" href="azienda_contratto.php?id=<?= urlencode((string)$a['pk']) ?>">Contratto</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="7">Nessuna azienda trovata.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html>
