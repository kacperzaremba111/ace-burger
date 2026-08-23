<?php

declare(strict_types=1);

/**
 * Prosty rate-limiting oparty o liczbę "trafień" w danym oknie czasowym,
 * przechowywany w DB — działa na hostingu współdzielonym (bez Redis/pamięci
 * współdzielonej między procesami PHP-FPM).
 *
 * Użycie:
 *   if (Throttle::tooMany("login:$ip", 5, 900)) { ... zablokuj ... }
 *   Throttle::hit("login:$ip"); // po każdej faktycznej próbie
 */
final class Throttle
{
    public static function tooMany(string $bucket, int $maxAttempts, int $windowSeconds): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) AS c FROM rate_limit_hits
             WHERE bucket = ? AND created_at >= (NOW() - INTERVAL ? SECOND)'
        );
        $stmt->execute([$bucket, $windowSeconds]);

        return (int) $stmt->fetch()['c'] >= $maxAttempts;
    }

    public static function hit(string $bucket): void
    {
        $stmt = Database::pdo()->prepare('INSERT INTO rate_limit_hits (bucket) VALUES (?)');
        $stmt->execute([$bucket]);
    }
}
