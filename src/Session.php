<?php

declare(strict_types=1);

/**
 * Wspólny start sesji PHP z bezpiecznymi ustawieniami ciasteczka
 * (httponly, samesite=Strict, secure automatycznie wykrywane po HTTPS —
 * działa poprawnie zarówno lokalnie po HTTP, jak i na produkcji po HTTPS).
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        session_start();
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? null) === '443';
    }
}
