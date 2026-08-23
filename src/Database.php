<?php

declare(strict_types=1);

/**
 * Pojedyncze, współdzielone połączenie PDO do MySQL.
 * Tryb wyjątków włączony — każdy błąd zapytania rzuca PDOException zamiast
 * cichego zwrócenia false (łatwiej złapać błędy zamiast przeoczyć je w produkcji).
 */
final class Database
{
    private static ?PDO $instance = null;
    private static ?array $config = null;

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }

        return self::$instance;
    }

    /**
     * Wczytuje config/config.local.php (poza repo, patrz .gitignore).
     * Rzuca czytelny błąd, jeśli plik jeszcze nie istnieje — zamiast
     * niejasnego "Undefined array key" gdzieś dalej w kodzie.
     */
    public static function loadConfig(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $localFile = dirname(__DIR__) . '/config/config.local.php';

        if (!is_file($localFile)) {
            throw new RuntimeException(
                'Brak config/config.local.php — skopiuj config/config.example.php jako ' .
                'config.local.php i uzupełnij prawdziwymi danymi (patrz README.md).'
            );
        }

        $config = require $localFile;

        if (!is_array($config)) {
            throw new RuntimeException('config/config.local.php musi zwracać tablicę (return [...];).');
        }

        return self::$config = $config;
    }

    private static function connect(): PDO
    {
        $db = self::loadConfig()['db'];

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $db['charset']);

        return new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
