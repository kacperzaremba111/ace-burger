<?php

declare(strict_types=1);

/**
 * Wspólny start dla każdego wejścia do aplikacji (www/index.php, api/*.php,
 * panel/*.php, cron/*.php): autoloading klas z src/ + helpery + strefa czasowa.
 *
 * Autoload bez Composera — celowo: wdrożenie na hostingu współdzielonym OVH
 * ma działać zwykłym FTP, bez zależności od `composer install`/SSH.
 * Nazwa klasy = nazwa pliku w src/ (bez namespace'ów).
 */

spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/helpers.php';

$config = Database::loadConfig();
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Warsaw');
