<?php
/**
 * collector.php — długo działający proces (systemd, User=asterisk)
 *
 * Odpytuje Asteriska co POLL_INTERVAL sekund, łączy dane z `rpt lstats`
 * (tabela połączeń) i `rpt xnode` (stan keyed per link), dokłada
 * callsign/description z astdb.txt i zapisuje wynik atomowo do
 * /run/asl-dashboard/state.json.
 *
 * status.php (wywoływany z przeglądarki) TYLKO czyta ten plik —
 * nie woła Asteriska bezpośrednio. Dzięki temu odświeżanie strony
 * jest natychmiastowe i niezależne od tego, jak długo odpowiada CLI.
 */

declare(strict_types=1);

$base = __DIR__;
require $base . '/lib/astdb.php';
require $base . '/lib/rpt.php';
require $base . '/lib/sysstats.php';

$config = parse_ini_file($base . '/config.ini', true);
$node = $config['node']['number'] ?? null;

if (!$node) {
    fwrite(STDERR, "collector: brak numeru nodu w config.ini\n");
    exit(1);
}

const POLL_INTERVAL   = 2;   // sekundy między odpytaniami Asteriska
const LASTHEARD_MAX   = 20;  // ile wpisów trzymamy w historii
const PERSIST_EVERY   = 30;  // co ile sekund zapisujemy lastheard na dysk

$runtimeDir    = '/run/asl-dashboard';
$stateFile     = $runtimeDir . '/state.json';
$persistDir    = '/var/lib/asl-dashboard';
$lastheardFile = $persistDir . '/lastheard.json';

foreach ([$runtimeDir, $persistDir] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Wczytaj historię z poprzedniego uruchomienia (przeżywa restart usługi)
$lastheard = [];
if (is_readable($lastheardFile)) {
    $decoded = json_decode((string) file_get_contents($lastheardFile), true);
    if (is_array($decoded)) {
        $lastheard = $decoded;
    }
}

$prevKeyed = []; // node => bool — do wykrywania zbocza K->U
$keyStart  = []; // node => timestamp startu kluczowania
$lastPersist = 0;

function atomic_write_json(string $path, array $data): bool {
    $tmp = $path . '.tmp.' . getmypid();
    $fh = fopen($tmp, 'w');
    if (!$fh) {
        return false;
    }
    fwrite($fh, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    fclose($fh);
    return rename($tmp, $path);
}

/**
 * Wstawia/aktualizuje wpis last-heard dla danego noda.
 *
 * Jeśli node JUŻ jest na liście, jego stary wpis jest usuwany i
 * doklejany na nowo na końcu (czyli staje się najnowszym) zamiast
 * tworzyć kolejny duplikat przy każdym PTT. Dzięki temu lista pokazuje
 * N OSTATNIO AKTYWNYCH NODÓW (każdy raz, z najświeższą aktywnością),
 * a nie N ostatnich zdarzeń kluczowania — jeden gadatliwy node już nie
 * zapcha całej historii.
 */
function lastheard_upsert(array &$lastheard, int $maxItems, $node, string $callsign, string $desc, int $t, int $dur): void {
    foreach ($lastheard as $i => $entry) {
        if ((string) $entry['node'] === (string) $node) {
            unset($lastheard[$i]);
            break;
        }
    }
    $lastheard = array_values($lastheard);

    $lastheard[] = [
        't'        => $t,
        'node'     => $node,
        'callsign' => $callsign,
        'desc'     => $desc,
        'dur'      => $dur,
    ];

    if (count($lastheard) > $maxItems) {
        $lastheard = array_slice($lastheard, -$maxItems);
    }
}

// Obsługa SIGTERM od systemd, żeby dało się cleanowo zatrzymać usługę
pcntl_async_signals(true);
$running = true;
pcntl_signal(SIGTERM, function () use (&$running) { $running = false; });
pcntl_signal(SIGINT,  function () use (&$running) { $running = false; });

while ($running) {
    $astdb = astdb_load();
    $table = rpt_lstats($node);
    $vars  = rpt_xnode_vars($node);

    $links = [];

    foreach ($table as $n => $row) {
        $info  = astdb_lookup($n, $astdb);
        $keyed = $vars['keyed'][$n] ?? false;
        $mode  = $vars['mode'][$n]  ?? '?';
        $prev  = $prevKeyed[$n] ?? false;

        if ($keyed && !$prev) {
            $keyStart[$n] = time();
        }
        if (!$keyed && $prev) {
            $dur = time() - ($keyStart[$n] ?? time());
            lastheard_upsert($lastheard, LASTHEARD_MAX, $n, $info['callsign'], $info['desc'], time(), $dur);
        }
        $prevKeyed[$n] = $keyed;

        $links[] = [
            'node'       => $n,
            'callsign'   => $info['callsign'],
            'desc'       => $info['desc'],
            'loc'        => $info['loc'],
            'ip'         => $row['ip'],
            'dir'        => $row['dir'],
            'state'      => $row['state'],
            'mode'       => $mode,
            'keyed'      => $keyed,
            'uptime'     => $row['uptime'],
            'reconnects' => $row['reconnects'],
        ];
    }

    $state = [
        'ts'        => time(),
        'node'      => $node,
        'rpt_ok'    => true,
        'ptt'       => $vars['txkeyed'],
        'cos'       => $vars['rxkeyed'],
        'links'     => $links,
        'numlinks'  => $vars['numlinks'],
        'lastheard' => array_reverse($lastheard), // najnowsze pierwsze
        'sys'       => sys_stats_collect(),
        'astdb'     => [
            'mtime'   => $astdb['mtime'],
            'count'   => $astdb['count'],
            'stale'   => $astdb['stale'],
            'missing' => $astdb['missing'] ?? false,
        ],
    ];

    atomic_write_json($stateFile, $state);

    if (time() - $lastPersist > PERSIST_EVERY) {
        file_put_contents($lastheardFile, json_encode($lastheard));
        $lastPersist = time();
    }

    sleep(POLL_INTERVAL);
}

// Ostatni zapis przy czystym zamknięciu
file_put_contents($lastheardFile, json_encode($lastheard));
