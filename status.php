<?php
/**
 * status.php — endpoint odpytywany przez przeglądarkę (setInterval).
 *
 * Celowo NIE woła Asteriska. Tylko czyta plik zapisany przez collector.php.
 * Dzięki temu odpowiedź jest natychmiastowa niezależnie od obciążenia
 * Asteriska, a proces www-data nie potrzebuje żadnych uprawnień do rpt/AMI.
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$path = '/run/asl-dashboard/state.json';

if (!is_readable($path)) {
    http_response_code(503);
    echo json_encode([
        'error'   => 'collector_not_running',
        'message' => 'Kolektor jeszcze nie zapisał stanu — sprawdź: systemctl status asl-dashboard-collector',
    ]);
    exit;
}

$mtime = filemtime($path);
if ((time() - $mtime) > 10) {
    // Plik istnieje, ale jest nieświeży — kolektor prawdopodobnie padł.
    // Zwracamy jego zawartość + flagę, żeby frontend mógł pokazać
    // "RPT OFFLINE" zamiast prezentować stare dane jako aktualne.
    $data = json_decode((string) file_get_contents($path), true) ?: [];
    $data['rpt_ok'] = false;
    $data['stale_seconds'] = time() - $mtime;
    echo json_encode($data);
    exit;
}

readfile($path);
