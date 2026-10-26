<?php

declare(strict_types=1);

/**
 * Logowanie/sesje/role dla panelu.
 *
 * Role: "admin"     — wszystkie strefy, zamówienia, menu i pracownicy;
 *       "pracownik" — wyłącznie zamówienia ze swojej strefy lokalu (zone_id).
 */
final class Auth
{
    private const SESSION_USER_KEY = 'admin_user_id';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 900; // 15 min

    /**
     * Próba logowania. Zwraca true przy sukcesie, false przy złych danych
     * LUB przy przekroczonym limicie prób z tego IP (celowo ten sam wynik —
     * nie zdradzamy atakującemu, który z tych dwóch przypadków zaszedł).
     */
    public static function attempt(string $username, string $password, string $ip): bool
    {
        Session::start();

        $bucket = 'login:' . $ip;
        if (Throttle::tooMany($bucket, self::MAX_LOGIN_ATTEMPTS, self::LOGIN_WINDOW_SECONDS)) {
            return false;
        }
        Throttle::hit($bucket);

        $stmt = Database::pdo()->prepare(
            'SELECT id, password_hash, is_active FROM admin_users WHERE username = ?'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_USER_KEY] = (int) $user['id'];

        $update = Database::pdo()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?');
        $update->execute([$user['id']]);

        return true;
    }

    public static function logout(): void
    {
        Session::start();
        $_SESSION = [];
        session_destroy();
    }

    /** Zalogowany użytkownik (id, username, role, full_name, zone_id, zone_name) albo null. */
    public static function user(): ?array
    {
        static $cached = false; // false = jeszcze nie sprawdzone; null = sprawdzone, brak usera

        if ($cached !== false) {
            return $cached;
        }

        Session::start();

        $id = $_SESSION[self::SESSION_USER_KEY] ?? null;
        if ($id === null) {
            return $cached = null;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.username, u.role, u.full_name, u.zone_id, z.name AS zone_name
             FROM admin_users u
             LEFT JOIN zones z ON z.id = u.zone_id
             WHERE u.id = ? AND u.is_active = 1'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $cached = ($user ?: null);
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    /** Wywołać na starcie każdej chronionej strony panelu. */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /panel/login.php');
            exit;
        }
    }

    /** @param string[] $roles np. ['admin'] albo ['admin', 'pracownik'] */
    public static function requireRole(array $roles): void
    {
        self::requireLogin();

        $user = self::user();
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo '403 — brak uprawnień do tej sekcji panelu.';
            exit;
        }
        if ($user['role'] === 'pracownik' && $user['zone_id'] === null) {
            http_response_code(403);
            echo '403 — konto nie ma przypisanej strefy lokalu. Poproś admina o przypisanie.';
            exit;
        }
    }

    /**
     * Strefa, do której ogranicza się widok zalogowanego użytkownika:
     * null = bez ograniczeń (admin), int = id strefy pracownika.
     * Każde zapytanie o zamówienia w panelu MUSI przejść przez tę wartość.
     */
    public static function zoneScope(): ?int
    {
        $user = self::user();
        if ($user === null) {
            throw new LogicException('zoneScope() wywołane bez zalogowanego użytkownika.');
        }

        return $user['role'] === 'admin' ? null : (int) $user['zone_id'];
    }
}
