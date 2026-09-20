<?php
/**
 * astdb.php — loader i cache dla /var/lib/asterisk/astdb.txt
 *
 * Format pliku (pipe-delimited, jedna linia na node):
 *   NODE|CALLSIGN|DESCRIPTION|LOCATION
 * np: 2031|W7AOR|HF Remote|Las Vegas
 *
 * Plik ma dziesiątki tysięcy linii, więc wczytujemy go raz i trzymamy
 * w pamięci procesu kolektora, przeładowując tylko gdy zmieni się mtime.
 * W ASL3 timer aktualizujący ten plik (asl3-update-astdb.timer) jest
 * DOMYŚLNIE WYŁĄCZONY — jeśli plik nie istnieje lub jest bardzo stary,
 * astdb_load() to zgłasza przez pole 'stale'.
 */

function astdb_load($path = '/var/lib/asterisk/astdb.txt') {
    static $cache = [];
    static $cachedMtime = 0;
    static $cachedPath = null;

    if (!is_readable($path)) {
        return [
            'mtime' => 0,
            'count' => count($cache),
            'stale' => true,
            'missing' => true,
            'data'  => $cache,
        ];
    }

    $mtime = filemtime($path);

    if ($path !== $cachedPath || $mtime !== $cachedMtime) {
        $data = [];
        $fh = fopen($path, 'r');
        if ($fh) {
            while (($line = fgets($fh)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === '') {
                    continue;
                }
                $parts = explode('|', $line);
                $node = trim($parts[0] ?? '');
                if ($node === '' || !ctype_digit($node)) {
                    continue;
                }
                $data[$node] = [
                    'callsign' => trim($parts[1] ?? ''),
                    'desc'     => trim($parts[2] ?? ''),
                    'loc'      => trim($parts[3] ?? ''),
                ];
            }
            fclose($fh);
        }
        $cache = $data;
        $cachedMtime = $mtime;
        $cachedPath = $path;
    }

    $staleThreshold = 7 * 86400; // 7 dni — dostosuj wg tego, jak często odpalasz timer
    $stale = (time() - $cachedMtime) > $staleThreshold;

    return [
        'mtime'   => $cachedMtime,
        'count'   => count($cache),
        'stale'   => $stale,
        'missing' => false,
        'data'    => $cache,
    ];
}

/**
 * Zwraca callsign/desc/loc dla danego numeru nodu, z rozsądnym fallbackiem
 * dla wpisów spoza astdb (np. EchoLink, IRLP, nody prywatne).
 */
function astdb_lookup($node, $astdb) {
    if (isset($astdb['data'][$node])) {
        return $astdb['data'][$node];
    }

    // Heurystyka numeracji: EchoLink to zwykle 3xxxxxx, IRLP to 4xxxxx.
    // Identyfikator nienumeryczny = najpewniej połączenie telefoniczne/IAX
    // (np. z aplikacji QSO One), gdzie zamiast numeru noda widnieje znak wywoławczy.
    if (!ctype_digit((string) $node)) {
        return ['callsign' => (string) $node, 'desc' => 'Phone/IAX connection', 'loc' => ''];
    }

    // Heurystyka numeracji: EchoLink to zwykle 3xxxxxx, IRLP to 4xxxxx.
    if (preg_match('/^3\d{6}$/', $node)) {
        return ['callsign' => 'EchoLink', 'desc' => '', 'loc' => ''];
    }
    if (preg_match('/^4\d{5}$/', $node)) {
        return ['callsign' => 'IRLP', 'desc' => '', 'loc' => ''];
    }

    return ['callsign' => 'Unknown', 'desc' => '', 'loc' => ''];
}
