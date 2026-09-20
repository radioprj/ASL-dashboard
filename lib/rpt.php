<?php
/**
 * rpt.php — wywołania i parsery komend Asteriska (app_rpt)
 *
 * WAŻNE: uruchamiane z konta 'asterisk' (przez systemd User=asterisk),
 * BEZ sudo. Kolektor nie potrzebuje żadnych dodatkowych uprawnień ponad
 * te, które i tak ma proces Asteriska.
 */

function rpt_exec($cmd) {
    $out = shell_exec($cmd . ' 2>/dev/null');
    return $out === null ? '' : $out;
}

/**
 * Tabela połączeń: NODE  PEER(IP)  RECONNECTS  DIRECTION  CONNECT-TIME  STATE
 * Źródło: `rpt lstats <node>` — ma nagłówek, co potwierdza znaczenie kolumn,
 * i czas połączenia z dokładnością do milisekund (H:M:S:ms).
 *
 * Przykładowy surowy output:
 *   NODE      PEER                RECONNECTS  DIRECTION  CONNECT TIME        CONNECT STATE
 *   ----      ----                ----------  ---------  ------------        -------------
 *   65750     66.179.81.74        0           OUT        00:01:16:473        ESTABLISHED
 */
function rpt_lstats($node) {
    $raw = rpt_exec('asterisk -rx ' . escapeshellarg('rpt lstats ' . $node));
    $rows = [];

    foreach (preg_split('/\r\n|\r|\n/', trim($raw)) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (stripos($line, 'NODE') === 0) {
            continue; // wiersz nagłówka
        }
        if (preg_match('/^-{2,}/', $line)) {
            continue; // wiersz z myślnikami-separatorem
        }

        $fields = preg_split('/\s+/', $line);
        if (count($fields) >= 6 && ctype_digit($fields[0])) {
            $rows[$fields[0]] = [
                'node'       => $fields[0],
                'ip'         => $fields[1],
                'reconnects' => (int) $fields[2],
                'dir'        => $fields[3],
                'uptime'     => rpt_time_to_seconds($fields[4]),
                'state'      => $fields[5],
            ];
        }
    }

    return $rows;
}

/**
 * Zmienne stanu w stylu dialplanowym.
 * Źródło: `rpt xnode <node>`.
 *
 * RPT_ALINKS=<licznik>,<node><T|R|C><K|U>,...
 *   T/R/C = transceive / receive-only (monitor) / connecting
 *   K/U   = aktualnie kluczowany / nie kluczowany
 *
 * UWAGA DO WERYFIKACJI: znaczenie K/U jest zgodne z dokumentacją app_rpt,
 * ale warto to potwierdzić na żywym ruchu — złap moment, gdy zdalny node
 * faktycznie nadaje, i sprawdź czy w RPT_ALINKS pojawia się <node>TK.
 */
function rpt_xnode_vars($node) {
    $raw = rpt_exec('asterisk -rx ' . escapeshellarg('rpt xnode ' . $node));
    $vars = [];

    foreach (preg_split('/\r\n|\r|\n/', trim($raw)) as $line) {
        if (preg_match('/^([A-Z_]+)=(.*)$/', trim($line), $m)) {
            $vars[$m[1]] = $m[2];
        }
    }

    $result = [
        'txkeyed'  => (($vars['RPT_TXKEYED']  ?? '0') === '1'),
        'etxkeyed' => (($vars['RPT_ETXKEYED'] ?? '0') === '1'),
        'rxkeyed'  => (($vars['RPT_RXKEYED']  ?? '0') === '1'),
        'numlinks' => (int) ($vars['RPT_NUMLINKS'] ?? 0),
        'mode'     => [],  // node => 'T' | 'R' | 'C'
        'keyed'    => [],  // node => bool
    ];
    if (!empty($vars['RPT_ALINKS'])) {
        $parts = explode(',', $vars['RPT_ALINKS']);
        array_shift($parts); // odrzuć wiodący licznik

        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            if (preg_match('/^(\d+)([TRC])([KU])$/', $p, $m)) {
                $result['mode'][$m[1]]  = $m[2];
                $result['keyed'][$m[1]] = ($m[3] === 'K');
            }
        }
    }

    return $result;
}

/**
 * Konwersja "HH:MM:SS" lub "HH:MM:SS:mmm" na liczbę sekund.
 * Pole milisekund (jeśli obecne, jak w `rpt lstats`) jest odrzucane —
 * na wyświetlaczu i tak pokazujemy pełne sekundy/HH:MM:SS.
 */
function rpt_time_to_seconds($t) {
    $parts = array_map('intval', explode(':', $t));
    if (count($parts) === 4) {
        array_pop($parts);
    }
    while (count($parts) < 3) {
        array_unshift($parts, 0);
    }
    [$h, $m, $s] = $parts;
    return $h * 3600 + $m * 60 + $s;
}
