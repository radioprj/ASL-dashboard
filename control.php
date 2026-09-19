<?php

require __DIR__ . '/lib/ipaccess.php';

$buttons = parse_ini_file('buttons.ini', true);
$config = parse_ini_file('config.ini', true);
$nodeNumber = $config['node']['number'];

// --- Kontrola dostępu: Macros/Function Keys tylko z sieci wewnętrznej ---
// To jest jedyne realne zabezpieczenie - ukrycie przycisków w index.php
// to tylko kosmetyka. Bez tej blokady każdy, kto zna URL, mógłby wywołać
// control.php?cmd=... bezpośrednio, z pominięciem interfejsu.
$allowedNetworks = load_allowed_networks($config);
if (!is_internal_client($allowedNetworks)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Forbidden: this control endpoint is only available from the internal network.\n";
    exit;
}

function process_command($nodeNumber, $cmd) {
    if (strpos($cmd, 'cop:') === 0) {
        $cop = substr($cmd, 4);
        if (!ctype_digit($cop)) {
            return;
        }
        $sh = 'sudo /usr/sbin/asterisk -rx "rpt cmd ' . $nodeNumber . ' cop ' . $cop . '"';
    } else {
        $sh = 'sudo /usr/sbin/asterisk -rx "rpt fun ' . $nodeNumber . ' ' . $cmd . '"';
    }
    shell_exec($sh);
}

// --- Ręcznie wpisana komenda (Manual Command) ---
// Osobna ścieżka, NIE przechodzi przez buttons.ini - stąd rygorystyczna
// walidacja regexem PRZED przekazaniem czegokolwiek do process_command()/shell_exec().
// Dozwolone WYŁĄCZNIE dwa formaty:
//   cop:NN / cop NN / cop:NN   -> kod operatora (Control Operator)
//   *NNNN...                   -> zwykły kod funkcji DTMF
// Cokolwiek innego (spacje, cudzysłowy, średniki, backticki, $, itd.) jest odrzucane,
// bo trafia bezpośrednio do shell_exec() wewnątrz cudzysłowów powłoki.
if (isset($_GET['cmd']) && $_GET['cmd'] === 'manual') {
    $raw = isset($_GET['code']) ? trim($_GET['code']) : '';

    if (preg_match('/^cop[:\s]*([0-9]{1,3})$/i', $raw, $m)) {
        process_command($nodeNumber, 'cop:' . $m[1]);
        exit(0);
    }

    if (preg_match('/^\*[0-9]{1,20}$/', $raw)) {
        process_command($nodeNumber, $raw);
        exit(0);
    }

    http_response_code(400);
    header('Content-Type: text/plain');
    echo "Invalid command format. Use *NNNN or cop NN.\n";
    exit;
}

if(!isset($_GET['cmd'])) {
    exit(0);
}

$cmd = $_GET['cmd'];
if(!isset($buttons[$cmd])) {
    exit(0);
}

$btn = $buttons[$cmd];
if(!isset($btn['cmds'])) {
    exit(0);
}

foreach($btn['cmds'] as $cmd) {
    process_command($nodeNumber, $cmd);
}
