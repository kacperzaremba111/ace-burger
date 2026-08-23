<?php

declare(strict_types=1);

/**
 * Token CSRF trzymany w sesji — jeden token na sesję, weryfikowany
 * przy każdym POST w panelu (formularze admina/kuchni).
 */
final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        Session::start();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /** Gotowe pole <input> do wklejenia w formularzu POST. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . h(self::token()) . '">';
    }

    public static function verify(?string $submittedToken): bool
    {
        Session::start();

        if (empty($_SESSION[self::SESSION_KEY]) || $submittedToken === null || $submittedToken === '') {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $submittedToken);
    }
}
