<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

$dateFrom = $_GET['from'] ?? date('Y-m-d');
$dateTo = $_GET['to'] ?? $dateFrom;
$status = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));

try {
    $from = new DateTime($dateFrom . ' 00:00:00');
    $to = (new DateTime($dateTo . ' 00:00:00'))->modify('+1 day');
} catch (Exception $e) {
    json_error('Date non valide.');
}

$repo = new ReservationRepository();
$rows = $repo->findByRange($from, $to, $status ?: null, $search ?: null);

$data = array_map(fn($row) => $repo->toLogical($row), $rows);

json_ok(['reservations' => $data]);
