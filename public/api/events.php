<?php

declare(strict_types=1);

require __DIR__ . '/_init.php';

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

if (!$start || !$end) {
    json_error('Parametri start/end mancanti.');
}

try {
    $from = new DateTime($start);
    $to = new DateTime($end);
} catch (Exception $e) {
    json_error('Date non valide.');
}

$statusColors = [
    'NUOVO' => '#7b3ff2',
    'CONTATTATO' => '#e8a33d',
    'CONFERMATO' => '#2f9e5b',
    'ANNULLATO' => '#d64545',
];

$repo = new ReservationRepository();
$rows = $repo->findByRange($from, $to);

$events = [];
foreach ($rows as $row) {
    $r = $repo->toLogical($row);
    $status = strtoupper((string)($r['status'] ?? ''));
    $title = trim(($r['nome'] ?? '') . ' ' . ($r['cognome'] ?? ''));
    if ($title === '') {
        $title = '(senza nome)';
    }

    $events[] = [
        'id' => $r['pk'] ?? null,
        'title' => $title,
        'start' => $r['day'],
        'color' => $statusColors[$status] ?? '#7b3ff2',
    ];
}

echo json_encode($events);
