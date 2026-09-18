<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

Auth::requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(404);
    die('Documento non trovato.');
}

$contracts = new ContractRepository();
$row = $contracts->find($id);
if (!$row) {
    http_response_code(404);
    die('Documento non trovato.');
}
$contract = $contracts->toLogical($row);

if ($contract['source'] !== 'generated' || empty($contract['content'])) {
    http_response_code(404);
    die('Documento non disponibile per la visualizzazione diretta.');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contratto di collaborazione</title>
<style>
    body { font-family: Georgia, 'Times New Roman', serif; max-width: 800px; margin: 40px auto; padding: 0 20px; color: #222; line-height: 1.6; }
    .print-bar { text-align: right; margin-bottom: 20px; }
    .print-bar button { padding: 8px 16px; border-radius: 6px; border: 1px solid #ccc; background: #f5f5f5; cursor: pointer; }
    @media print { .print-bar { display: none; } }
</style>
</head>
<body>
<div class="print-bar"><button onclick="window.print()">Stampa / Salva PDF</button></div>
<?= $contract['content'] ?>
</body>
</html>
