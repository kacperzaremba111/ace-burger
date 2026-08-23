<?php

declare(strict_types=1);

/**
 * Jednorazowa wiadomość w sesji, do wzorca Post/Redirect/Get w panelu
 * (żeby odświeżenie strony po zapisie nie wysyłało formularza ponownie).
 */
final class Flash
{
    private const SESSION_KEY = 'flash';

    public static function set(string $type, string $message): void
    {
        Session::start();
        $_SESSION[self::SESSION_KEY] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type:string,message:string}|null */
    public static function take(): ?array
    {
        Session::start();
        $flash = $_SESSION[self::SESSION_KEY] ?? null;
        unset($_SESSION[self::SESSION_KEY]);

        return $flash;
    }
}
