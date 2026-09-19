<?php
/**
 * ipaccess.php — prosta kontrola dostępu wg adresu IP klienta.
 *
 * Zamiennik login/hasło dla sekcji Macros & Function Keys: jeśli
 * żądanie przychodzi spoza sieci wewnętrznej (LAN), sekcja jest
 * ukrywana w index.php ORAZ blokowana w control.php.
 *
 * WAŻNE: to zabezpieczenie działa na poziomie adresu IP, nie tożsamości
 * użytkownika. Jeśli dashboard stoi za reverse proxy / load balancerem,
 * $_SERVER['REMOTE_ADDR'] będzie adresem PROXY, nie realnego klienta —
 * w takiej sytuacji trzeba by zaufać nagłówkowi X-Forwarded-For, ale
 * TYLKO jeśli proxy jest zaufane i nadpisuje ten nagłówek (inaczej
 * klient może go sfałszować). Domyślnie ta funkcja NIE ufa nagłówkom
 * proxy - jeśli potrzebujesz tego, dopisz jawnie w konfiguracji.
 */

function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Sprawdza czy $ip mieści się w zakresie $cidr (np. "192.168.1.0/24").
 * Obsługuje IPv4 i IPv6. Bez "/" traktuje $cidr jako pojedynczy adres.
 */
function ip_in_cidr(string $ip, string $cidr): bool {
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }

    [$subnet, $bitsStr] = explode('/', $cidr, 2);
    $bits = (int) $bitsStr;

    $isIpv6 = strpos($ip, ':') !== false || strpos($subnet, ':') !== false;

    if ($isIpv6) {
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainderBits = $bits % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }
        if ($remainderBits > 0) {
            $mask = (~(0xFF >> $remainderBits)) & 0xFF;
            if ((ord($ipBin[$fullBytes]) & $mask) !== (ord($subnetBin[$fullBytes]) & $mask)) {
                return false;
            }
        }
        return true;
    }

    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false || $bits < 0 || $bits > 32) {
        return false;
    }
    if ($bits === 0) {
        return true; // 0.0.0.0/0 - dopasuj wszystko (jeśli ktoś świadomie tak skonfiguruje)
    }

    $mask = -1 << (32 - $bits);
    return ($ipLong & $mask) === ($subnetLong & $mask);
}

/**
 * Czy bieżący klient należy do jednej z podanych sieci/adresów.
 * $allowedNetworks to tablica stringów w formacie CIDR lub pojedynczych IP.
 */
function is_internal_client(array $allowedNetworks): bool {
    if (empty($allowedNetworks)) {
        // Brak konfiguracji = bezpieczny domyślny wybór: NIKT nie jest "wewnętrzny".
        // Lepiej żeby admin świadomie dodał swoją sieć, niż żeby literówka
        // w configu przypadkiem otworzyła panel na cały internet.
        return false;
    }

    $ip = client_ip();
    foreach ($allowedNetworks as $cidr) {
        $cidr = trim((string) $cidr);
        if ($cidr === '') {
            continue;
        }
        if (ip_in_cidr($ip, $cidr)) {
            return true;
        }
    }
    return false;
}

/**
 * Odczytuje listę dozwolonych sieci z config.ini, sekcja [security],
 * klucz internal_networks[] (można powtórzyć wielokrotnie).
 */
function load_allowed_networks(array $config): array {
    return isset($config['security']['internal_networks'])
        ? (array) $config['security']['internal_networks']
        : [];
}
