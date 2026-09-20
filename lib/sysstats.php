<?php
/**
 * sysstats.php — lekkie metryki systemowe (Raspberry Pi / Linux ogólnie)
 * Bez zewnętrznych zależności/pakietów — same wbudowane odczyty z /proc i /sys.
 */

/**
 * Procent obciążenia CPU liczony z delty /proc/stat między kolejnymi
 * wywołaniami (a nie z chwilowego snapshotu, który nic nie mówi).
 * Ponieważ collector.php woła tę funkcję raz na pętlę (co POLL_INTERVAL
 * sekund), delta naturalnie obejmuje dokładnie ten odstęp czasu.
 * Pierwsze wywołanie po starcie procesu zwraca null (brak punktu odniesienia).
 */
function sys_cpu_pct(): ?float {
    static $prevTotal = null;
    static $prevIdle  = null;

    $raw = @file_get_contents('/proc/stat');
    if ($raw === false) {
        return null;
    }

    $line = strtok($raw, "\n");
    if (strpos($line, 'cpu ') !== 0) {
        return null;
    }

    $parts = preg_split('/\s+/', trim($line));
    array_shift($parts); // odrzuć etykietę "cpu"
    $parts = array_map('intval', $parts);

    // kolejność pól: user nice system idle iowait irq softirq steal ...
    $idle  = ($parts[3] ?? 0) + ($parts[4] ?? 0);
    $total = array_sum($parts);

    if ($prevTotal === null) {
        $prevTotal = $total;
        $prevIdle  = $idle;
        return null;
    }

    $totalDelta = $total - $prevTotal;
    $idleDelta  = $idle - $prevIdle;

    $prevTotal = $total;
    $prevIdle  = $idle;

    if ($totalDelta <= 0) {
        return null;
    }

    $usage = (1 - $idleDelta / $totalDelta) * 100;
    return max(0, min(100, (int)round($usage)));
}

/** Procent zajętej pamięci RAM (liczony wg MemAvailable, nie samego MemFree). */
function sys_mem_pct(): ?float {
    $raw = @file_get_contents('/proc/meminfo');
    if ($raw === false) {
        return null;
    }

    $vals = [];
    foreach (['MemTotal', 'MemAvailable'] as $key) {
        if (preg_match('/^' . $key . ':\s+(\d+)/m', $raw, $m)) {
            $vals[$key] = (int) $m[1];
        }
    }
    if (empty($vals['MemTotal']) || !isset($vals['MemAvailable'])) {
        return null;
    }

    $usedPct = (1 - $vals['MemAvailable'] / $vals['MemTotal']) * 100;
    return max(0, min(100, (int)round($usedPct)));
}

/** Procent zajętości dysku dla podanej ścieżki (domyślnie root filesystem). */
function sys_disk_pct(string $path = '/'): ?float {
    // Tryb read-only (overlay) na Raspberry Pi: '/' to wtedy tmpfs overlay
    // (zwykle mały, w RAM), a NIE prawdziwa karta SD. Rzeczywisty rozmiar
    // widać pod /media/root-ro, które raspi-config montuje jako dostęp
    // do prawdziwej partycji. Bez tego procent liczyłby się z tmpfs, nie z SD.
    if ($path === '/' && is_dir('/media/root-ro')) {
        $path = '/media/root-ro';
    }

    $free  = @disk_free_space($path);
    $total = @disk_total_space($path);
    if ($free === false || $total === false || $total <= 0) {
        return null;
    }

    $usedPct = (1 - $free / $total) * 100;
    return max(0, min(100, (int) round($usedPct)));
}
/**
 * Temperatura CPU w °C.
 * Główne źródło: /sys/class/thermal/thermal_zone0/temp (world-readable
 * na Raspberry Pi OS, wartość w milistopniach). Fallback: vcgencmd,
 * gdyby zone0 nie był dostępny (wymaga grupy 'video' - stąd traktowany
 * jako zapasowa opcja, nie główna).
 */
function sys_cpu_temp_c(): ?float {
    $raw = @file_get_contents('/sys/class/thermal/thermal_zone0/temp');
    if ($raw !== false && is_numeric(trim($raw))) {
        return (int)round(((int) trim($raw)) / 1000);
    }

    $out = @shell_exec('vcgencmd measure_temp 2>/dev/null');
    if ($out && preg_match('/temp=([\d.]+)/', $out, $m)) {
        return (int)$m[1];
    }

    return null;
}

function sys_stats_collect(): array {
    return [
        'cpu_pct'  => sys_cpu_pct(),
        'mem_pct'  => sys_mem_pct(),
        'disk_pct' => sys_disk_pct('/'),
        'temp_c'   => sys_cpu_temp_c(),
    ];
}
