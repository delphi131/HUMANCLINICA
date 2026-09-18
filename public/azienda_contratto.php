<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Auth::requireAdmin();

$aziendaId = $_GET['id'] ?? $_POST['id'] ?? null;
if (!$aziendaId) {
    header('Location: aziende.php');
    exit;
}

$aziende = new AziendaRepository();
$aziendaRow = $aziende->find($aziendaId);
if (!$aziendaRow) {
    header('Location: aziende.php');
    exit;
}
$azienda = $aziende->toLogical($aziendaRow);

$contracts = new ContractRepository();
$messages = new MessageRepository();
$user = Auth::user();

$error = null;
$notice = null;

$uploadDir = __DIR__ . '/../storage/contracts/' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$aziendaId);
$allowedExt = ['pdf', 'doc', 'docx'];
$maxUploadBytes = 10 * 1024 * 1024; // 10MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'generate') {
            $templateName = Config::schema()['template_names']['collaboration_contract'];
            $template = $messages->getTemplate($templateName);
            if ($template === null) {
                throw new RuntimeException("Template \"{$templateName}\" non trovato. Creane uno dalla pagina Messaggi.");
            }
            $html = $messages->render($template, [
                'NOME_AZIENDA' => $azienda['nome'] ?? '',
                'PIVA' => $azienda['piva'] ?? '',
                'SEDE' => $azienda['sede'] ?? '',
                'CITTA' => $azienda['citta'] ?? '',
                'CAP' => $azienda['cap'] ?? '',
                'RAPPRESENTANTE' => $azienda['rappresentante'] ?? '',
                'TELEFONO' => $azienda['telephone'] ?? '',
                'DATA' => date('d/m/Y'),
            ]);
            $contracts->createGenerated($aziendaId, $html, (string)$user['username']);
            $notice = 'Contratto generato.';
        } elseif ($action === 'upload') {
            if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('Seleziona un file da caricare.');
            }
            if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Errore durante il caricamento del file.');
            }
            if ($_FILES['file']['size'] > $maxUploadBytes) {
                throw new RuntimeException('File troppo grande (massimo 10MB).');
            }
            $originalName = $_FILES['file']['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                throw new RuntimeException('Formato non consentito. Usa PDF, DOC o DOCX.');
            }

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0750, true);
            }
            $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
            $destination = $uploadDir . '/' . $safeName;
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
                throw new RuntimeException('Impossibile salvare il file caricato.');
            }

            $relativePath = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$aziendaId) . '/' . $safeName;
            $contracts->createUploaded($aziendaId, $originalName, $relativePath, (string)$user['username']);
            $notice = 'File caricato.';
        } elseif ($action === 'delete') {
            $contractId = $_POST['contract_id'] ?? null;
            $contract = $contractId ? $contracts->find($contractId) : null;
            if ($contract) {
                $logical = $contracts->toLogical($contract);
                if (!empty($logical['file_path'])) {
                    $fullPath = __DIR__ . '/../storage/contracts/' . $logical['file_path'];
                    if (is_file($fullPath)) {
                        unlink($fullPath);
                    }
                }
                $contracts->delete($contractId);
                $notice = 'Documento eliminato.';
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$list = array_map(fn($row) => $contracts->toLogical($row), $contracts->listForAzienda($aziendaId));
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contratto — <?= htmlspecialchars((string)$azienda['nome']) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<?php require __DIR__ . '/partials/nav.php'; ?>

<div class="container">
    <div class="card">
        <h2>Contratto di collaborazione — <?= htmlspecialchars((string)$azienda['nome']) ?></h2>
        <p class="hint"><a href="aziende.php">&larr; torna alle aziende</a></p>

        <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display:flex; gap:24px; flex-wrap:wrap;">
            <form method="post" style="flex:1; min-width:220px;">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$aziendaId) ?>">
                <input type="hidden" name="action" value="generate">
                <p class="hint">Genera un contratto a partire dal template "CONTRATTO_COLLABORAZIONE" (modificabile dalla pagina Messaggi), compilato con i dati di questa azienda.</p>
                <button type="submit" class="btn btn-primary">Genera da template</button>
            </form>

            <form method="post" enctype="multipart/form-data" style="flex:1; min-width:220px;">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)$aziendaId) ?>">
                <input type="hidden" name="action" value="upload">
                <p class="hint">Oppure carica un contratto già firmato (PDF, DOC, DOCX — max 10MB).</p>
                <input type="file" name="file" accept=".pdf,.doc,.docx" required>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-outline">Carica file</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h2>Documenti</h2>
        <table class="reservations">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Nome file</th>
                    <th>Creato</th>
                    <th>Da</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $c): ?>
                <tr>
                    <td><span class="status-badge"><?= $c['source'] === 'uploaded' ? 'Caricato' : 'Generato' ?></span></td>
                    <td><?= htmlspecialchars((string)($c['file_name'] ?? 'contratto.html')) ?></td>
                    <td><?= htmlspecialchars((string)$c['created_at']) ?></td>
                    <td><?= htmlspecialchars((string)($c['created_by'] ?? '')) ?></td>
                    <td class="actions-cell">
                        <?php if ($c['source'] === 'uploaded'): ?>
                            <a class="btn btn-sm btn-outline" target="_blank" rel="noopener" href="api/contract_download.php?id=<?= urlencode((string)$c['pk']) ?>">Scarica</a>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline" target="_blank" rel="noopener" href="contratto_view.php?id=<?= urlencode((string)$c['pk']) ?>">Visualizza / Stampa</a>
                        <?php endif; ?>
                        <form method="post" onsubmit="return confirm('Eliminare questo documento?');" style="display:inline">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$aziendaId) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="contract_id" value="<?= htmlspecialchars((string)$c['pk']) ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Elimina</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($list)): ?>
                <tr><td colspan="5">Nessun documento presente per questa azienda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
