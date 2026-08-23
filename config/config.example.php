<?php
/**
 * Szablon konfiguracji ACE BURGER.
 *
 * Skopiuj ten plik jako "config.local.php" (w tym samym katalogu) i uzupełnij
 * prawdziwymi wartościami — osobno lokalnie i na serwerze produkcyjnym.
 * "config.local.php" jest w .gitignore i NIGDY nie powinien trafić do repo.
 */

return [

    // ---------- Baza danych ----------
    'db' => [
        'host'    => '127.0.0.1',
        'name'    => 'ace_burger',
        'user'    => 'ace_burger_user',
        'pass'    => 'ZMIEN_MNIE',
        'charset' => 'utf8mb4',
    ],

    // ---------- Przelewy24 ----------
    // Dane testowe: https://sandbox.przelewy24.pl (panel merchant testowy)
    // Dane produkcyjne: https://secure.przelewy24.pl (po weryfikacji konta)
    'przelewy24' => [
        'sandbox'     => true, // false dopiero po pełnych testach end-to-end
        'merchant_id' => 0,
        'pos_id'      => 0,
        'crc_key'     => 'ZMIEN_MNIE',
        'api_key'     => 'ZMIEN_MNIE',
    ],

    // ---------- Aplikacja ----------
    'app' => [
        'base_url'          => 'https://ace-burger.pl', // bez końcowego "/"
        'timezone'          => 'Europe/Warsaw',
        'order_number_prefix' => 'ACE',
    ],

];
