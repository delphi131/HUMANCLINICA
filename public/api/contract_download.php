<?php

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

if (!Auth::check()) {
    http_response_code(401);
    die('Non autenticato.');
}

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

if ($contract['source'] !== 'uploaded' || empty($contract['file_path'])) {
    http_response_code(404);
    die('Documento non disponibile per il download.');
}

$storageRoot = realpath(__DIR__ . '/../../storage/contracts');
$fullPath = realpath($storageRoot . '/' . $contract['file_path']);

// Defend against path traversal: the resolved path must stay inside storage/contracts.
if ($fullPath === false || $storageRoot === false || !str_starts_with($fullPath, $storageRoot)) {
    http_response_code(404);
    die('Documento non trovato.');
}

$fileName = $contract['file_name'] ?? basename($fullPath);
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $fileName) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
